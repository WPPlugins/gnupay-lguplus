<?php
if( ! defined( 'ABSPATH' ) ) return;

$order_id = isset($_POST['order_id']) ? sanitize_text_field($_POST['order_id']) : '';

if( GNUPAY_LGUPLUS_MOBILE && isset($_POST['oid']) ){     //모바일이면
    $order_id = sanitize_text_field($_POST['oid']);
}

if( !$order_id ){
    wp_die( __('주문번호 값이 없습니다.', GNUPAY_LGUPLUS) );
}

$check_keys = array(
'CST_PLATFORM',
'CST_MID',
'LGD_MID',
'LGD_OID',
'LGD_BUYER',
'LGD_PRODUCTINFO',
'LGD_AMOUNT',
'LGD_CUSTOM_FIRSTPAY',
'LGD_BUYEREMAIL',
'LGD_CUSTOM_SKIN',
'LGD_WINDOW_VER',
'LGD_CUSTOM_PROCESSTYPE',
'LGD_TIMESTAMP',
'LGD_HASHDATA',
'LGD_PAYKEY',
'LGD_VERSION',
'LGD_TAXFREEAMOUNT',
'LGD_BUYERIP',
'LGD_BUYERID',
'LGD_CUSTOM_USABLEPAY',
'LGD_CASHRECEIPTYN',
'LGD_BUYERADDRESS',
'LGD_BUYERPHONE',
'LGD_RECEIVER',
'LGD_RECEIVERPHONE',
'LGD_EASYPAY_ONLY',
'LGD_CASNOTEURL',
'LGD_RETURNURL',
'LGD_ENCODING',
'LGD_ENCODING_RETURNURL',
'good_mny',
'order_key',
);

$params = array();

foreach( $check_keys as $v ){
    $params[$v] = isset($_POST[$v]) ? sanitize_text_field($_POST[$v]) : '';
}

extract($params);

$config = gnupay_lguplus_get_config_payment($order_id);

if( !$config ){
    wp_die(__('주문번호 값과 LGU+ 결제 설정값이 맞지 않습니다.', GNUPAY_LGUPLUS));
}

$pid = $payment_method = get_post_meta( $order_id, '_payment_method', true );
$payment_title = get_post_meta( $order_id, '_payment_method_title', true );
$pay_ids = gnupay_lguplus_get_settings('pay_ids');

if( !$pid || !in_array($pid, $pay_ids) ){
    wp_die(__('해당 주문번호가 LGUPLUS 결제로 등록되지 않았습니다.', GNUPAY_LGUPLUS));
}

if( $LGD_CUSTOM_FIRSTPAY ){

    $real_payment = '';

    switch( $LGD_CUSTOM_FIRSTPAY ){
        case 'SC0010' :     //신용카드
            $real_payment = $pay_ids['card'];
            break;
        case 'SC0030' :     //계좌이체
            $real_payment = $pay_ids['bank'];
            break;
        case 'SC0060' :     //휴대폰
            $real_payment = $pay_ids['phone'];
            break;
        case 'SC0040' :     //가상계좌
            $real_payment = $pay_ids['vbank'];
            break;
        default :

            if( 'PAYNOW' == $LGD_EASYPAY_ONLY ){
                $real_payment = $pay_ids['easy'];
            }

            break;
    }

    if( $real_payment && $real_payment != $payment_method ){    //이전것과 비교해서 틀리면

        $available_gateways = WC()->payment_gateways->get_available_payment_gateways();
        $pid = $payment_method = $real_payment;

        // Update meta
        update_post_meta( $order_id, '_payment_method', $payment_method );

        $before_pay_title = $payment_title;

        if ( isset( $available_gateways[ $payment_method ] ) ) {
            $payment_title = $available_gateways[ $payment_method ]->get_title();
        } else {
            $payment_title = '';
        }

        update_post_meta( $order_id, '_payment_method_title', $payment_title );

        $current_user = wp_get_current_user();
        $user_name = $current_user->ID ? $current_user->user_login.' ( '.$current_user->ID.' ) ' : __('비회원', GNUPAY_LGUPLUS);

		$order->add_order_note( sprintf(__( '결제 방법이 %s 에서 %s 으로 변경되었습니다. : 사용자 %s ', GNUPAY_LGUPLUS ), 
			$payment_title ? esc_html($payment_title) : esc_html( $payment_method ),
			$before_pay_title,
            $user_name
		) );
    }
}

//변수 초기화
$od_receipt_time = '';
$od_tno = '';
$od_app_no  = '';   //가상계좌
$od_deposit_name = '';
$od_bank_account = '';

include GNUPAY_LGUPLUS_PATH.'lgu/xpay_result.php';

$od_tno             = $tno;

if( $payment_method == $pay_ids['bank'] ){  //계좌이체

    $od_receipt_price   = $amount;
    $od_receipt_time    = preg_replace("/([0-9]{4})([0-9]{2})([0-9]{2})([0-9]{2})([0-9]{2})([0-9]{2})/", "\\1-\\2-\\3 \\4:\\5:\\6", $app_time);
    $od_deposit_name    = esc_attr($LGD_BUYER);
    $od_bank_account    = '';
    $pg_price           = $amount;
    //$od_misu            = $i_price - $od_receipt_price;

} else if( $payment_method == $pay_ids['vbank'] ){  //가상계좌

    $od_receipt_price   = 0;
    $od_bank_account    = $bank_name.' '.$account;
    $od_deposit_name    = $depositor;
    $pg_price           = $amount;
    //$od_misu            = $i_price - $od_receipt_price;
} else if( $payment_method == $pay_ids['phone'] ) {    //휴대폰

    $od_receipt_price   = $amount;
    $od_receipt_time    = preg_replace("/([0-9]{4})([0-9]{2})([0-9]{2})([0-9]{2})([0-9]{2})([0-9]{2})/", "\\1-\\2-\\3 \\4:\\5:\\6", $app_time);
    $od_bank_account    = $commid . ($commid ? ' ' : '').$mobile_no;
    $pg_price           = $amount;
    //$od_misu            = $i_price - $od_receipt_price;

} else if( $payment_method == $pay_ids['card']) {

    $od_tno             = $tno;
    $od_app_no          = $app_no;
    $od_receipt_price   = $amount;
    $od_receipt_time    = preg_replace("/([0-9]{4})([0-9]{2})([0-9]{2})([0-9]{2})([0-9]{2})([0-9]{2})/", "\\1-\\2-\\3 \\4:\\5:\\6", $app_time);
    $od_bank_account    = $card_name;
    $pg_price           = $amount;
    //$od_misu            = $i_price - $od_receipt_price;

} else if( $payment_method == $pay_ids['easy'] ) {   //간편결제

    $od_app_no          = $app_no;
    $od_receipt_price   = $amount;
    $od_receipt_time    = preg_replace("/([0-9]{4})([0-9]{2})([0-9]{2})([0-9]{2})([0-9]{2})([0-9]{2})/", "\\1-\\2-\\3 \\4:\\5:\\6", $app_time);
    $od_bank_account    = $card_name;
    $pg_price           = $amount;
    //$od_misu            = $i_price - $od_receipt_price;

} else {
    wp_die("od_settle_case Error!!!");
}

// 주문금액과 결제금액이 일치하는지 체크
if(isset($tno) && !empty($tno)) {
    $order = wc_get_order( $order_id );

    if($order->get_total() != $pg_price) {
        $cancel_msg = __('결제금액 불일치', GNUPAY_LGUPLUS);
        include( __DIR__.'/xpay_cancel.php' );

        wp_die("Receipt Amount Error");
    }

}

$pay_result = array(
    'tot_price' => $pg_price, //결제완료금액
    'tno' => $tno, //PG 거래번호
    'mid' => $LGD_MID, //가맹점 ID
    'vactbankname' => $bank_name,  // 입금할 은행 이름
    'vactname' => $od_deposit_name,  // 입금할 계좌 예금주
    'vactnum' => $od_bank_account, // 입금할 계좌 번호
    'vactdate' => '',     // 가상계좌 입금마감시간
);

//결제 처리 공통
include( __DIR__ ."/pay_success_common.php");

$return_url = $this->get_return_url( $order );

gp_goto_url($return_url);
?>
<?php
if( ! defined( 'ABSPATH' ) ) return;

// 결제가 성공 했다면 공통 처리

$pay_options = get_option( $return_plugin_id . $pid . '_settings' );
$order = wc_get_order( $order_id );

if( empty($pay_options['de_pay_complete_status']) ){    //지정한 값이 없다면 default 값으로 넣어준다.
    $pay_options['de_pay_complete_status'] = 'wc-processing';

    if( $pay_ids['vbank'] == $pid ){    //가상계좌이면
        $pay_options['de_pay_complete_status'] = 'wc-pending';
    }
}

$msg = __(sprintf('%s 로 처리되었습니다.', $pay_options['title'] ), GNUPAY_LGUPLUS);

// Mark as on-hold (we're awaiting the payment)
$order->update_status( sir_lguplus_get_order_status($pay_options['de_pay_complete_status']), __( 'Awaiting process payment', 'woocommerce' ) );

$amount = $pay_result['tot_price'];   //결제완료금액
$tno = $pay_result['tno'];   //PG 거래번호
$mid = $pay_result['mid'];   //가맹점 ID ( 테스트 결제인 경우 INIpayTest )

$current_user = wp_get_current_user();
$user_name = $current_user->ID ? $current_user->user_login.' ( '.$current_user->ID.' ) ' : __('비회원', GNUPAY_LGUPLUS);

if( isset($is_noti) && $is_noti ){

	if( ! isset( $noti_tno ) || empty($noti_tno) ){
		$order->add_order_note( sprintf(__( '모바일 noti %s 가격 %s 결제 하였습니다.', GNUPAY_LGUPLUS ), 
			$payment_title ? esc_html($payment_title) : esc_html( $payment_method ),
			wc_price($amount)
		) );
	}
} else {

	if( $pay_ids['vbank'] == $pid ){    //가상계좌이면

		$order->add_order_note( sprintf(__( '%s 님이 %s로 주문하였습니다.', GNUPAY_LGUPLUS ), 
			$user_name,
			$payment_title ? esc_html($payment_title) : esc_html( $payment_method ),
			$amount
		) );

	} else {    //그 외에는 (신용카드, 계좌이체, 휴대폰, 간편결제)

		$order->add_order_note( sprintf(__( '%s 님이 %s 가격 %s 결제 하였습니다.', GNUPAY_LGUPLUS ), 
			$user_name,
			$payment_title ? esc_html($payment_title) : esc_html( $payment_method ),
			wc_price($amount)
		) );

	}

}

// 복합과세 금액
if( wc_tax_enabled() ){
    $od_tax_mny = $amount - $order->get_total_tax();
    $od_vat_mny = $order->get_total_tax();
    $od_free_mny = 0;
} else {
    $od_tax_mny = round($amount / 1.1);
    $od_vat_mny = $amount - $od_tax_mny;
    $od_free_mny = 0;
}


if( wc_tax_enabled() && $config['de_tax_flag_use'] == 'yes' ) {

    if( GNUPAY_LGUPLUS_MOBILE ){

        if( isset($_POST['P_TAX']) )
            $od_vat_mny = (int)$_POST['P_TAX'];
        if( isset($_POST['P_TAXFREE']) )
            $od_free_mny = (int)$_POST['P_TAXFREE'];

    } else {

        if( isset($_POST['tax']) )
            $od_vat_mny = (int)$_POST['tax'];
        if( isset($_POST['taxfree']) )
            $od_free_mny = (int)$_POST['taxfree'];

    }

    update_post_meta($order_id, '_od_tax_flag', 1);   //복합과세로설정
}

update_post_meta($order_id, '_od_pg', 'lguplus');   //결제 pg사를 저장
update_post_meta($order_id, '_od_tno', isset($tno) ? $tno : '');   //결제 pg사를 주문번호
update_post_meta($order_id, '_od_app_no', isset($app_no) ? $app_no : '');   //결제 승인 번호
update_post_meta($order_id, '_od_currency', get_woocommerce_currency() );

if ( $payment_method == $pay_ids['vbank'] ){   //가상계좌이면
    update_post_meta($order_id, '_od_receipt_price', 0);
} else {
    update_post_meta($order_id, '_od_receipt_price', isset($amount) ? $amount : 0);   //결제금액
}

update_post_meta($order_id, '_od_tax_mny', $od_tax_mny);
update_post_meta($order_id, '_od_vat_mny', $od_vat_mny);
update_post_meta($order_id, '_od_free_mny', $od_free_mny);

if( $config['de_card_test'] ){
    update_post_meta($order_id, '_od_test', 1);   //test 결제이면
}

if( $escw_yn == 'Y' ){
    update_post_meta($order_id, '_od_escrow', 1);   //에스크로 결제시
}

if ( $payment_method == $pay_ids['vbank'] || $payment_method == $pay_ids['bank'] ){   //가상계좌이거나 계좌이체이면
    update_post_meta($order_id, '_od_bankname', $pay_result['vactbankname']);   // 입금할 은행 이름
    update_post_meta($order_id, '_od_depositor', $pay_result['vactname']);   // 입금할 계좌 예금주
    update_post_meta($order_id, '_od_account', $pay_result['vactnum']);   // 입금할 계좌 번호
    update_post_meta($order_id, '_od_va_date', $pay_result['vactdate']);   // 가상계좌 입금마감시간
}

WC()->session->set( 'gp_lguplus_'.$order_id , true );

do_action('gnupay_lguplus_order_success', $order_id, $payment_method );

//주문이 끝나면
//$order->payment_complete();

// Reduce stock levels
$order->reduce_order_stock();       //재고 처리

// Remove cart
WC()->cart->empty_cart();   //장바구니 삭제

?>
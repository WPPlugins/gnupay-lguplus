<?php
if( ! defined( 'ABSPATH' ) ) return; // 개별 페이지 접근 불가

$is_testmode = get_post_meta($order_id, '_is_test', true);

$order = wc_get_order($order_id);
$config = gnupay_lguplus_get_config_payment( $order_id );
$pay_ids = gnupay_lguplus_get_settings('pay_ids');
$payment_method = $order->payment_method;

include_once(GNUPAY_LGUPLUS_PATH.'lgu/settle_lguplus.inc.php');

switch($payment_method) {
    case $pay_ids['vbank'] :    //가상계좌
        $pay_type = 'SC0040';
        break;
    case $pay_ids['bank'] :   //계좌이체
        $pay_type = 'SC0030';
        break;
    default:
        wp_die('<p id="scash_empty">'.__('현금영수증은 가상계좌, 계좌이체에 한해 발급요청이 가능합니다.', GNUPAY_LGUPLUS).'</p>');
        break;
}

$od_cash = get_post_meta($order->id, '_od_cash', true);

if( $od_cash ){
	wp_die( __('이미 등록된 현금영수증 입니다.', GNUPAY_LGUPLUS) );
}

$oinfo = gnupay_lguplus_process_payment($order, $config);

$od_tno       = get_post_meta( $order_id, '_od_tno', true );
$od_casseqno  = get_post_meta( $order_id, '_od_casseqno', true );

$buyername = esc_attr($order->billing_last_name.$order->billing_first_name);
$goods_name  = $oinfo['goods'];
$order_price = $order->get_total();
$amt_tot   = $order->get_total() - $order->get_total_refunded();      // 현재 금액
$amt_sup   = $amt_tot - $order->get_total_tax();
$amt_svc   = 0;
$amt_tax   = $order->get_total_tax();
$od_tax_flag = get_post_meta($order->id, '_od_tax_flag', true);
$od_free_mny = get_post_meta($order->id, '_od_free_mny', true);

/*
    $amt_tot   = (int)$od['od_tax_mny'] + (int)$od['od_vat_mny'] + (int)$od['od_free_mny'];
    $amt_sup   = (int)$od['od_tax_mny'] + (int)$od['od_free_mny'];
    $amt_tax   = (int)$od['od_vat_mny'];
*/

$id_info    = isset($_POST[ "id_info"    ]) ? sanitize_text_field($_POST[ "id_info"     ]) : '';                             // 신분확인 ID
$tr_code    = isset($_POST[ "tr_code"    ]) ? sanitize_text_field($_POST[ "tr_code"     ]) : '';                             // 발행용도


$reg_num  = $id_info;
$useopt   = $tr_code;
$currency = 'WON';

$od_currency = get_post_meta($order->id, '_od_currency', true);

if( in_array( $od_currency, array('AUD', 'ARS', 'CAD', 'CLP', 'COP', 'HKD', 'MXN', 'NZD', 'SGD', 'USD') ) ){   //달러
    $currency = 'USD';
}

$LGD_METHOD                 = 'AUTH';                                   //메소드('AUTH':승인, 'CANCEL' 취소)
$LGD_OID                    = $order_id;                                   //주문번호(상점정의 유니크한 주문번호를 입력하세요)
$LGD_PAYTYPE                = $pay_type;                                //결제수단 코드 (SC0030:계좌이체, SC0040:가상계좌, SC0100:무통장입금 단독)
$LGD_AMOUNT                 = $order_price;                             //금액("," 를 제외한 금액을 입력하세요)
$LGD_PRODUCTINFO            = $goods_name;                              //상품명
$LGD_TID                    = $od_tno;                                  //LG유플러스 거래번호
$LGD_CUSTOM_MERTNAME        = apply_filters('gp_lguplus_de_admin_company_name', get_bloginfo('name'));        //상점명
$LGD_CUSTOM_CEONAME         = isset($config['de_admin_company_owner']) ? $config['de_admin_company_owner'] : '';       //대표자명
$LGD_CUSTOM_BUSINESSNUM     = isset($config['de_admin_company_saupja_no']) ? $config['de_admin_company_saupja_no'] : '';   //사업자등록번호
$LGD_CUSTOM_MERTPHONE       = isset($config['de_admin_company_tel']) ? $config['de_admin_company_tel'] : '';         //상점 전화번호
$LGD_CASHCARDNUM            = isset($_POST['id_info']) ? sanitize_text_field($_POST['id_info']) : '';                        //발급번호(주민등록번호,현금영수증카드번호,휴대폰번호 등등)
$LGD_CASHRECEIPTUSE         = isset($_POST['tr_code']) ? sanitize_text_field($_POST['tr_code']) : '';                        //현금영수증발급용도('1':소득공제, '2':지출증빙)

$xpay = new XPay($configPath, $CST_PLATFORM);

// Mert Key 설정
$xpay->set_config_value('t'.$LGD_MID, $config['de_lguplus_mert_key']);
$xpay->set_config_value($LGD_MID, $config['de_lguplus_mert_key']);

$xpay->Init_TX($LGD_MID);
$xpay->Set("LGD_TXNAME", "CashReceipt");
$xpay->Set("LGD_METHOD", $LGD_METHOD);
$xpay->Set("LGD_PAYTYPE", $LGD_PAYTYPE);

if ($LGD_METHOD == "AUTH") {                 // 현금영수증 발급 요청
    $xpay->Set("LGD_OID", $LGD_OID);
    $xpay->Set("LGD_AMOUNT", $LGD_AMOUNT);
    $xpay->Set("LGD_CASHCARDNUM", $LGD_CASHCARDNUM);
    $xpay->Set("LGD_CUSTOM_MERTNAME", $LGD_CUSTOM_MERTNAME);
    $xpay->Set("LGD_CUSTOM_CEONAME", $LGD_CUSTOM_CEONAME);
    $xpay->Set("LGD_CUSTOM_BUSINESSNUM", $LGD_CUSTOM_BUSINESSNUM);
    $xpay->Set("LGD_CUSTOM_MERTPHONE", $LGD_CUSTOM_MERTPHONE);
    $xpay->Set("LGD_CASHRECEIPTUSE", $LGD_CASHRECEIPTUSE);
    $xpay->Set("LGD_ENCODING",    "UTF-8");

    if($od_tax_flag && (float) $od_free_mny > 0) {
        $xpay->Set("LGD_TAXFREEAMOUNT", $od_free_mny); //비과세 금액
    }

    if ($LGD_PAYTYPE == "SC0030"){              //기결제된 계좌이체건 현금영수증 발급요청시 필수
        $xpay->Set("LGD_TID", $LGD_TID);
    }
    else if ($LGD_PAYTYPE == "SC0040"){         //기결제된 가상계좌건 현금영수증 발급요청시 필수
        $xpay->Set("LGD_TID", $LGD_TID);
        $xpay->Set("LGD_SEQNO", $od_casseqno);
    }
    else {                                      //무통장입금 단독건 발급요청
        //$xpay->Set("LGD_PRODUCTINFO", $LGD_PRODUCTINFO);
    }
}

/*
 * 1. 현금영수증 발급/취소 요청 결과처리
 *
 * 결과 리턴 파라미터는 연동메뉴얼을 참고하시기 바랍니다.
 */
if ($xpay->TX()) {
    //1)현금영수증 발급/취소결과 화면처리(성공,실패 결과 처리를 하시기 바랍니다.)
    /*
    echo "현금영수증 발급/취소 요청처리가 완료되었습니다.  <br>";
    echo "TX Response_code = " . $xpay->Response_Code() . "<br>";
    echo "TX Response_msg = " . $xpay->Response_Msg() . "<p>";

    echo "결과코드 : " . $xpay->Response("LGD_RESPCODE",0) . "<br>";
    echo "결과메세지 : " . $xpay->Response("LGD_RESPMSG",0) . "<br>";
    echo "거래번호 : " . $xpay->Response("LGD_TID",0) . "<p>";

    $keys = $xpay->Response_Names();
    foreach($keys as $name) {
        echo $name . " = " . $xpay->Response($name, 0) . "<br>";
    }
    */

    if($xpay->Response_Code() == '0000') {
        $LGD_OID = $xpay->Response("LGD_OID",0);
        $cash_no = $xpay->Response("LGD_CASHRECEIPTNUM",0);

        $cash = array();
        $cash['LGD_TID']        = $xpay->Response("LGD_TID",0);
        $cash['LGD_TIMESTAMP']  = $xpay->Response("LGD_TIMESTAMP",0);
        $cash['LGD_RESPDATE']   = $xpay->Response("LGD_RESPDATE",0);
        $cash_info = $cash;

        update_post_meta($order_id, '_od_cash', 1);
        update_post_meta($order_id, '_od_cash_no', $cash_no);
        update_post_meta($order_id, '_od_cash_info', $cash_info);

        $result = true;

        if(!$result) { // DB 정보갱신 실패시 취소
            $xpay->Set("LGD_TXNAME", "CashReceipt");
            $xpay->Set("LGD_METHOD", "CANCEL");
            $xpay->Set("LGD_PAYTYPE", $LGD_PAYTYPE);
            $xpay->Set("LGD_TID", $LGD_TID);

            if ($LGD_PAYTYPE == "SC0040"){				//가상계좌건 현금영수증 발급취소시 필수
                $xpay->Set("LGD_SEQNO", $od_casseqno);
            }

            if ($xpay->TX()) {
                /*
                echo "현금영수증 취소 요청처리가 완료되었습니다.  <br>";
                echo "TX Response_code = " . $xpay->Response_Code() . "<br>";
                echo "TX Response_msg = " . $xpay->Response_Msg() . "<p>";

                echo "결과코드 : " . $xpay->Response("LGD_RESPCODE",0) . "<br>";
                echo "결과메세지 : " . $xpay->Response("LGD_RESPMSG",0) . "<br>";
                echo "거래번호 : " . $xpay->Response("LGD_TID",0) . "<p>";
                */
            } else {
                $msg = __('현금영수증 취소 요청처리가 정상적으로 완료되지 않았습니다.', GNUPAY_LGUPLUS);
                if(!$is_admin)
                    $msg .= __('쇼핑몰 관리자에게 문의해 주십시오.', GNUPAY_LGUPLUS);

                gp_alert($msg);
            }
        }
    }

} else {
    //2)API 요청 실패 화면처리
    /*
    echo "현금영수증 발급/취소 요청처리가 실패되었습니다.  <br>";
    echo "TX Response_code = " . $xpay->Response_Code() . "<br>";
    echo "TX Response_msg = " . $xpay->Response_Msg() . "<p>";
    */

    $msg = __('현금영수증 발급 요청처리가 정상적으로 완료되지 않았습니다.', GNUPAY_LGUPLUS);
    $msg .= '\\nTX Response_code = '.$xpay->Response_Code();
    $msg .= '\\nTX Response_msg = '.$xpay->Response_Msg();

    gp_alert($msg);
}

if($config['de_card_test']) {
    echo '<script language="JavaScript" src="http://pgweb.uplus.co.kr:7085/WEB_SERVER/js/receipt_link.js"></script>'.PHP_EOL;
} else {
    echo '<script language="JavaScript" src="http://pgweb.uplus.co.kr/WEB_SERVER/js/receipt_link.js"></script>'.PHP_EOL;
}

switch($LGD_PAYTYPE) {
    case 'SC0030':
        $trade_type = 'BANK';
        break;
    case 'SC0040':
        $trade_type = 'CAS';
        break;
    default:
        $trade_type = 'CR';
        break;
}
?>

<?php lguplus_new_html_header(); ?>

<div id="lg_req_tx" class="new_win">
    <h1 id="win_title"><?php _e('현금영수증 - LG U+', GNUPAY_LGUPLUS); ?></h1>

    <div class="tbl_head01 tbl_wrap">
        <table>
        <colgroup>
            <col class="grid_4">
            <col>
        </colgroup>
        <tbody>
        <tr>
            <th scope="row"><?php _e('결과코드', GNUPAY_LGUPLUS); ?></th>
            <td><?php echo $xpay->Response_Code(); ?></td>
        </tr>
        <tr>
            <th scope="row"><?php _e('결과 메세지', GNUPAY_LGUPLUS); ?></th>
            <td><?php echo $xpay->Response_Msg(); ?></td>
        </tr>
        <tr>
            <th scope="row"><?php _e('현금영수증 거래번호', GNUPAY_LGUPLUS); ?></th>
            <td><?php echo $xpay->Response("LGD_TID",0); ?></td>
        </tr>
        <tr>
            <th scope="row"<?php _e('>현금영수증 승인번호', GNUPAY_LGUPLUS); ?></th>
            <td><?php echo $xpay->Response("LGD_CASHRECEIPTNUM",0); ?></td>
        </tr>
        <tr>
            <th scope="row"><?php _e('승인시간', GNUPAY_LGUPLUS); ?></th>
            <td><?php echo preg_replace("/([0-9]{4})([0-9]{2})([0-9]{2})([0-9]{2})([0-9]{2})([0-9]{2})/", "\\1-\\2-\\3 \\4:\\5:\\6",$xpay->Response("LGD_RESPDATE",0)); ?></td>
        </tr>
        <tr>
            <th scope="row"><?php _e('현금영수증 URL', GNUPAY_LGUPLUS); ?></th>
            <td>
                <button type="button" name="receiptView" class="btn_frmline" onClick="javascript:showCashReceipts('<?php echo $LGD_MID; ?>','<?php echo $LGD_OID; ?>','<?php echo $od_casseqno; ?>','<?php echo $trade_type; ?>','<?php echo isset($CST_PLATFROM) ? $CST_PLATFROM : ''; ?>');"><?php _e('영수증 확인', GNUPAY_LGUPLUS);?></button>
                <p><?php _e('영수증 확인은 실 등록의 경우에만 가능합니다.', GNUPAY_LGUPLUS);?></p>
            </td>
        </tr>
        <tr>
            <td colspan="2"></td>
        </tr>
        </tbody>
        </table>
    </div>

</div>
<?php lguplus_new_html_footer(); ?>
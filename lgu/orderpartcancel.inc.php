<?php
if( ! defined( 'ABSPATH' ) ) return;

global $wpdb;

if( ! is_admin() ) return;

require_once(GNUPAY_LGUPLUS_PATH.'lgu/settle_lguplus.inc.php');

/*
 * [결제 부분취소 요청 페이지]
 *
 * LG유플러스으로 부터 내려받은 거래번호(LGD_TID)를 가지고 취소 요청을 합니다.(파라미터 전달시 POST를 사용하세요)
 * (승인시 LG유플러스으로 부터 내려받은 PAYKEY와 혼동하지 마세요.)
 */

$LGD_TID              		= $od_tno;			  		                            //LG유플러스으로 부터 내려받은 거래번호(LGD_TID)
$LGD_CANCELAMOUNT     		= (int)$tax_mny;                                                //부분취소 금액
$LGD_REMAINAMOUNT     		= (int)$remain_before_price;   //취소전 남은금액

$LGD_CANCELTAXFREEAMOUNT    = (int)$free_mny;                                               //면세대상 부분취소 금액 (과세/면세 혼용상점만 적용)
$LGD_CANCELREASON     		= $mod_memo;                                                    //취소사유
$LGD_RFACCOUNTNUM           = isset($_POST['LGD_RFACCOUNTNUM']) ? sanitize_text_field($_POST['LGD_RFACCOUNTNUM']) : '';	 		                        //환불계좌 번호(가상계좌 환불인경우만 필수)
$LGD_RFBANKCODE             = isset($_POST['LGD_RFBANKCODE']) ? sanitize_text_field($_POST['LGD_RFBANKCODE']) : '';	 		                            //환불계좌 은행코드(가상계좌 환불인경우만 필수)
$LGD_RFCUSTOMERNAME         = isset($_POST['LGD_RFCUSTOMERNAME']) ? sanitize_text_field($_POST['LGD_RFCUSTOMERNAME']) : ''; 		                        //환불계좌 예금주(가상계좌 환불인경우만 필수)
$LGD_RFPHONE                = isset($_POST['LGD_RFPHONE']) ? sanitize_text_field($_POST['LGD_RFPHONE']) : '';		 		                        //요청자 연락처(가상계좌 환불인경우만 필수)

$xpay = new XPay($configPath, $CST_PLATFORM);

// Mert Key 설정
$xpay->set_config_value('t'.$LGD_MID, $config['de_lguplus_mert_key']);
$xpay->set_config_value($LGD_MID, $config['de_lguplus_mert_key']);

$xpay->Init_TX($LGD_MID);

$xpay->Set("LGD_TXNAME",                "PartialCancel");
$xpay->Set("LGD_TID",                   $LGD_TID);
$xpay->Set("LGD_CANCELAMOUNT",          $LGD_CANCELAMOUNT);
$xpay->Set("LGD_REMAINAMOUNT",          $LGD_REMAINAMOUNT);
$xpay->Set("LGD_CANCELTAXFREEAMOUNT",   $LGD_CANCELTAXFREEAMOUNT);
$xpay->Set("LGD_CANCELREASON",          $LGD_CANCELREASON);
$xpay->Set("LGD_RFACCOUNTNUM",          $LGD_RFACCOUNTNUM);
$xpay->Set("LGD_RFBANKCODE",            $LGD_RFBANKCODE);
$xpay->Set("LGD_RFCUSTOMERNAME",        $LGD_RFCUSTOMERNAME);
$xpay->Set("LGD_RFPHONE",               $LGD_RFPHONE);
$xpay->Set("LGD_REQREMAIN",             "0");
$xpay->Set("LGD_ENCODING",              "UTF-8");

/*
 * 1. 결제 부분취소 요청 결과처리
 *
 */

$return_error = '';

if ($xpay->TX()) {
    //1)결제 부분취소결과 화면처리(성공,실패 결과 처리를 하시기 바랍니다.)
    /*
    echo "결제 부분취소 요청이 완료되었습니다.  <br>";
    echo "TX Response_code = " . $xpay->Response_Code() . "<br>";
    echo "TX Response_msg = " . $xpay->Response_Msg() . "<p>";

    $keys = $xpay->Response_Names();
        foreach($keys as $name) {
            echo $name . " = " . $xpay->Response($name, 0) . "<br>";
        }
    echo "<p>";
    */

    if( '0000' == $xpay->Response_Code() ) {
        // 환불금액기록

        //성공 처리 생략
        $return_error = '';

    } else {
        $return_error = $xpay->Response_Msg().' 코드 : '.$xpay->Response_Code();
    }
} else {
    //2)API 요청 실패 화면처리
    /*
    echo "결제 부분취소 요청이 실패하였습니다.  <br>";
    echo "TX Response_code = " . $xpay->Response_Code() . "<br>";
    echo "TX Response_msg = " . $xpay->Response_Msg() . "<p>";
    */

    $return_error = '결제 부분취소 요청이 실패하였습니다.\\n\\n'.$xpay->Response_Code().' : '.$xpay->Response_Msg();
}
?>
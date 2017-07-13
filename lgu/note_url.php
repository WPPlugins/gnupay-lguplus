<?php
if( ! defined( 'ABSPATH' ) ) exit;

/*
 * 공통결제결과 정보
 */
$LGD_RESPCODE = "";           			// 응답코드: 0000(성공) 그외 실패
$LGD_RESPMSG = "";            			// 응답메세지
$LGD_MID = "";                			// 상점아이디
$LGD_OID = "";                			// 주문번호
$LGD_AMOUNT = "";             			// 거래금액
$LGD_TID = "";                			// LG유플러스에서 부여한 거래번호
$LGD_PAYTYPE = "";            			// 결제수단코드
$LGD_PAYDATE = "";            			// 거래일시(승인일시/이체일시)
$LGD_HASHDATA = "";           			// 해쉬값
$LGD_FINANCECODE = "";        			// 결제기관코드(카드종류/은행코드/이통사코드)
$LGD_FINANCENAME = "";        			// 결제기관이름(카드이름/은행이름/이통사이름)
$LGD_ESCROWYN = "";           			// 에스크로 적용여부
$LGD_TIMESTAMP = "";          			// 타임스탬프
$LGD_FINANCEAUTHNUM = "";     			// 결제기관 승인번호(신용카드, 계좌이체, 상품권)

/*
 * 신용카드 결제결과 정보
 */
$LGD_CARDNUM = "";            			// 카드번호(신용카드)
$LGD_CARDINSTALLMONTH = "";   			// 할부개월수(신용카드)
$LGD_CARDNOINTYN = "";        			// 무이자할부여부(신용카드) - '1'이면 무이자할부 '0'이면 일반할부
$LGD_TRANSAMOUNT = "";        			// 환율적용금액(신용카드)
$LGD_EXCHANGERATE = "";       			// 환율(신용카드)

/*
 * 휴대폰
 */
$LGD_PAYTELNUM = "";          			// 결제에 이용된전화번호

/*
 * 계좌이체, 무통장
 */
$LGD_ACCOUNTNUM = "";         			// 계좌번호(계좌이체, 무통장입금)
$LGD_CASTAMOUNT = "";         			// 입금총액(무통장입금)
$LGD_CASCAMOUNT = "";         			// 현입금액(무통장입금)
$LGD_CASFLAG = "";            			// 무통장입금 플래그(무통장입금) - 'R':계좌할당, 'I':입금, 'C':입금취소
$LGD_CASSEQNO = "";           			// 입금순서(무통장입금)
$LGD_CASHRECEIPTNUM = "";     			// 현금영수증 승인번호
$LGD_CASHRECEIPTSELFYN = "";  			// 현금영수증자진발급제유무 Y: 자진발급제 적용, 그외 : 미적용
$LGD_CASHRECEIPTKIND = "";    			// 현금영수증 종류 0: 소득공제용 , 1: 지출증빙용

/*
 * OK캐쉬백
 */
$LGD_OCBSAVEPOINT = "";       			// OK캐쉬백 적립포인트
$LGD_OCBTOTALPOINT = "";      			// OK캐쉬백 누적포인트
$LGD_OCBUSABLEPOINT = "";     			// OK캐쉬백 사용가능 포인트

/*
 * 구매정보
 */
$LGD_BUYER = "";              			// 구매자
$LGD_PRODUCTINFO = "";        			// 상품명
$LGD_BUYERID = "";            			// 구매자 ID
$LGD_BUYERADDRESS = "";       			// 구매자 주소
$LGD_BUYERPHONE = "";         			// 구매자 전화번호
$LGD_BUYEREMAIL = "";         			// 구매자 이메일
$LGD_BUYERSSN = "";           			// 구매자 주민번호
$LGD_PRODUCTCODE = "";        			// 상품코드
$LGD_RECEIVER = "";           			// 수취인
$LGD_RECEIVERPHONE = "";      			// 수취인 전화번호
$LGD_DELIVERYINFO = "";       			// 배송지


$LGD_RESPCODE            = isset($_POST["LGD_RESPCODE"]) ? sanitize_text_field($_POST["LGD_RESPCODE"]) : '';
$LGD_RESPMSG             = isset($_POST["LGD_RESPMSG"]) ? sanitize_text_field($_POST["LGD_RESPMSG"]) : '';
$LGD_MID                 = isset($_POST["LGD_MID"]) ? sanitize_text_field($_POST["LGD_MID"]) : '';
$LGD_OID                 = isset($_POST["LGD_OID"]) ? sanitize_text_field($_POST["LGD_OID"]) : '';
$LGD_AMOUNT              = isset($_POST["LGD_AMOUNT"]) ? sanitize_text_field($_POST["LGD_AMOUNT"]) : '';
$LGD_TID                 = isset($_POST["LGD_TID"]) ? sanitize_text_field($_POST["LGD_TID"]) : '';
$LGD_PAYTYPE             = isset($_POST["LGD_PAYTYPE"]) ? sanitize_text_field($_POST["LGD_PAYTYPE"]) : '';
$LGD_PAYDATE             = isset($_POST["LGD_PAYDATE"]) ? sanitize_text_field($_POST["LGD_PAYDATE"]) : '';
$LGD_HASHDATA            = isset($_POST["LGD_HASHDATA"]) ? sanitize_text_field($_POST["LGD_HASHDATA"]) : '';
$LGD_FINANCECODE         = isset($_POST["LGD_FINANCECODE"]) ? sanitize_text_field($_POST["LGD_FINANCECODE"]) : '';
$LGD_FINANCENAME         = isset($_POST["LGD_FINANCENAME"]) ? sanitize_text_field($_POST["LGD_FINANCENAME"]) : '';
$LGD_ESCROWYN            = isset($_POST["LGD_ESCROWYN"]) ? sanitize_text_field($_POST["LGD_ESCROWYN"]) : '';
$LGD_TRANSAMOUNT         = isset($_POST["LGD_TRANSAMOUNT"]) ? sanitize_text_field($_POST["LGD_TRANSAMOUNT"]) : '';
$LGD_EXCHANGERATE        = isset($_POST["LGD_EXCHANGERATE"]) ? sanitize_text_field($_POST["LGD_EXCHANGERATE"]) : '';
$LGD_CARDNUM             = isset($_POST["LGD_CARDNUM"]) ? sanitize_text_field($_POST["LGD_CARDNUM"]) : '';
$LGD_CARDINSTALLMONTH    = isset($_POST["LGD_CARDINSTALLMONTH"]) ? sanitize_text_field($_POST["LGD_CARDINSTALLMONTH"]) : '';
$LGD_CARDNOINTYN         = isset($_POST["LGD_CARDNOINTYN"]) ? sanitize_text_field($_POST["LGD_CARDNOINTYN"]) : '';
$LGD_TIMESTAMP           = isset($_POST["LGD_TIMESTAMP"]) ? sanitize_text_field($_POST["LGD_TIMESTAMP"]) : '';
$LGD_FINANCEAUTHNUM      = isset($_POST["LGD_FINANCEAUTHNUM"]) ? sanitize_text_field($_POST["LGD_FINANCEAUTHNUM"]) : '';
$LGD_PAYTELNUM           = isset($_POST["LGD_PAYTELNUM"]) ? sanitize_text_field($_POST["LGD_PAYTELNUM"]) : '';
$LGD_ACCOUNTNUM          = isset($_POST["LGD_ACCOUNTNUM"]) ? sanitize_text_field($_POST["LGD_ACCOUNTNUM"]) : '';
$LGD_CASTAMOUNT          = isset($_POST["LGD_CASTAMOUNT"]) ? sanitize_text_field($_POST["LGD_CASTAMOUNT"]) : '';
$LGD_CASCAMOUNT          = isset($_POST["LGD_CASCAMOUNT"]) ? sanitize_text_field($_POST["LGD_CASCAMOUNT"]) : '';
$LGD_CASFLAG             = isset($_POST["LGD_CASFLAG"]) ? sanitize_text_field($_POST["LGD_CASFLAG"]) : '';
$LGD_CASSEQNO            = isset($_POST["LGD_CASSEQNO"]) ? sanitize_text_field($_POST["LGD_CASSEQNO"]) : '';
$LGD_CASHRECEIPTNUM      = isset($_POST["LGD_CASHRECEIPTNUM"]) ? sanitize_text_field($_POST["LGD_CASHRECEIPTNUM"]) : '';
$LGD_CASHRECEIPTSELFYN   = isset($_POST["LGD_CASHRECEIPTSELFYN"]) ? sanitize_text_field($_POST["LGD_CASHRECEIPTSELFYN"]) : '';
$LGD_CASHRECEIPTKIND     = isset($_POST["LGD_CASHRECEIPTKIND"]) ? sanitize_text_field($_POST["LGD_CASHRECEIPTKIND"]) : '';
$LGD_OCBSAVEPOINT        = isset($_POST["LGD_OCBSAVEPOINT"]) ? sanitize_text_field($_POST["LGD_OCBSAVEPOINT"]) : '';
$LGD_OCBTOTALPOINT       = isset($_POST["LGD_OCBTOTALPOINT"]) ? sanitize_text_field($_POST["LGD_OCBTOTALPOINT"]) : '';
$LGD_OCBUSABLEPOINT      = isset($_POST["LGD_OCBUSABLEPOINT"]) ? sanitize_text_field($_POST["LGD_OCBUSABLEPOINT"]) : '';

$LGD_BUYER               = isset($_POST["LGD_BUYER"]) ? sanitize_text_field($_POST["LGD_BUYER"]) : '';
$LGD_PRODUCTINFO         = isset($_POST["LGD_PRODUCTINFO"]) ? sanitize_text_field($_POST["LGD_PRODUCTINFO"]) : '';
$LGD_BUYERID             = isset($_POST["LGD_BUYERID"]) ? sanitize_text_field($_POST["LGD_BUYERID"]) : '';
$LGD_BUYERADDRESS        = isset($_POST["LGD_BUYERADDRESS"]) ? sanitize_text_field($_POST["LGD_BUYERADDRESS"]) : '';
$LGD_BUYERPHONE          = isset($_POST["LGD_BUYERPHONE"]) ? sanitize_text_field($_POST["LGD_BUYERPHONE"]) : '';
$LGD_BUYEREMAIL          = isset($_POST["LGD_BUYEREMAIL"]) ? sanitize_text_field($_POST["LGD_BUYEREMAIL"]) : '';
$LGD_BUYERSSN            = isset($_POST["LGD_BUYERSSN"]) ? sanitize_text_field($_POST["LGD_BUYERSSN"]) : '';
$LGD_PRODUCTCODE         = isset($_POST["LGD_PRODUCTCODE"]) ? sanitize_text_field($_POST["LGD_PRODUCTCODE"]) : '';
$LGD_RECEIVER            = isset($_POST["LGD_RECEIVER"]) ? sanitize_text_field($_POST["LGD_RECEIVER"]) : '';
$LGD_RECEIVERPHONE       = isset($_POST["LGD_RECEIVERPHONE"]) ? sanitize_text_field($_POST["LGD_RECEIVERPHONE"]) : '';
$LGD_DELIVERYINFO        = isset($_POST["LGD_DELIVERYINFO"]) ? sanitize_text_field($_POST["LGD_DELIVERYINFO"]) : '';

global $woocommerce;

$order_id = $LGD_OID;
$config = gnupay_lguplus_get_config_payment( $order_id );
$pay_ids = gnupay_lguplus_get_settings('pay_ids');

$LGD_MERTKEY = $config['de_lguplus_mert_key'];  //LG유플러스에서 발급한 상점키로 변경해 주시기 바랍니다.

$LGD_HASHDATA2 = md5($LGD_MID.$LGD_OID.$LGD_AMOUNT.$LGD_RESPCODE.$LGD_TIMESTAMP.$LGD_MERTKEY);

/*
 * 상점 처리결과 리턴메세지
 *
 * OK   : 상점 처리결과 성공
 * 그외 : 상점 처리결과 실패
 *
 * ※ 주의사항 : 성공시 'OK' 문자이외의 다른문자열이 포함되면 실패처리 되오니 주의하시기 바랍니다.
 */
$resultMSG = __('결제결과 상점 DB처리(NOTE_URL) 결과값을 입력해 주시기 바랍니다.', GNUPAY_LGUPLUS);

if ($LGD_HASHDATA2 == $LGD_HASHDATA) {      //해쉬값 검증이 성공하면
    if($LGD_RESPCODE == "0000"){            //결제가 성공이면
        /*
         * 거래성공 결과 상점 처리(DB) 부분
         * 상점 결과 처리가 정상이면 "OK"
         */
        //if( 결제성공 상점처리결과 성공 )
        $resultMSG = "OK";
    }else {                                 //결제가 실패이면
        /*
         * 거래실패 결과 상점 처리(DB) 부분
         * 상점결과 처리가 정상이면 "OK"
         */
       //if( 결제실패 상점처리결과 성공 )
       $resultMSG = "OK";
    }
} else {                                    //해쉬값 검증이 실패이면
    /*
     * hashdata검증 실패 로그를 처리하시기 바랍니다.
     */
    $resultMSG = __('결제결과 상점 DB처리(NOTE_URL) 해쉬값 검증이 실패하였습니다.', GNUPAY_LGUPLUS);
}

echo $resultMSG;
exit;
?>
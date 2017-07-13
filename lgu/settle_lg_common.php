<?php
if( ! defined( 'ABSPATH' ) ) return;

if( !isset($plugin_id) || !isset($payment_id) ){
    return;
}

global $woocommerce;

/*
 * [상점 결제결과처리(DB) 페이지]
 *
 * 1) 위변조 방지를 위한 hashdata값 검증은 반드시 적용하셔야 합니다.
 *
 */
$LGD_RESPCODE            = isset($_POST["LGD_RESPCODE"]) ? sanitize_text_field($_POST["LGD_RESPCODE"]) : '';    // 응답코드: 0000(성공) 그외 실패
$LGD_RESPMSG             = isset($_POST["LGD_RESPMSG"]) ? sanitize_text_field($_POST["LGD_RESPMSG"]) : '';              // 응답메세지
$LGD_MID                 = isset($_POST["LGD_MID"]) ? sanitize_text_field($_POST["LGD_MID"]) : '';                   // 상점아이디
$LGD_OID                 = isset($_POST["LGD_OID"]) ? sanitize_text_field($_POST["LGD_OID"]) : '';                   // 주문번호
$LGD_AMOUNT              = isset($_POST["LGD_AMOUNT"]) ? sanitize_text_field($_POST["LGD_AMOUNT"]) : '';                // 거래금액
$LGD_TID                 = isset($_POST["LGD_TID"]) ? sanitize_text_field($_POST["LGD_TID"]) : '';                   // LG유플러스에서 부여한 거래번호
$LGD_PAYTYPE             = isset($_POST["LGD_PAYTYPE"]) ? sanitize_text_field($_POST["LGD_PAYTYPE"]) : '';               // 결제수단코드
$LGD_PAYDATE             = isset($_POST["LGD_PAYDATE"]) ? sanitize_text_field($_POST["LGD_PAYDATE"]) : '';              // 거래일시(승인일시/이체일시)
$LGD_HASHDATA            = isset($_POST["LGD_HASHDATA"]) ? sanitize_text_field($_POST["LGD_HASHDATA"]) : '';             // 해쉬값
$LGD_FINANCECODE         = isset($_POST["LGD_FINANCECODE"]) ? sanitize_text_field($_POST["LGD_FINANCECODE"]) : '';          // 결제기관코드(은행코드)
$LGD_FINANCENAME         = isset($_POST["LGD_FINANCENAME"]) ? sanitize_text_field($_POST["LGD_FINANCENAME"]) : '';          // 결제기관이름(은행이름)
$LGD_ESCROWYN            = isset($_POST["LGD_ESCROWYN"]) ? sanitize_text_field($_POST["LGD_ESCROWYN"]) : '';              // 에스크로 적용여부
$LGD_TIMESTAMP           = isset($_POST["LGD_TIMESTAMP"]) ?  sanitize_text_field($_POST["LGD_TIMESTAMP"]) : '';           // 타임스탬프
$LGD_ACCOUNTNUM          = isset($_POST["LGD_ACCOUNTNUM"]) ? sanitize_text_field($_POST["LGD_ACCOUNTNUM"]) : '';           // 계좌번호(무통장입금)
$LGD_CASTAMOUNT          = isset($_POST["LGD_CASTAMOUNT"]) ? sanitize_text_field($_POST["LGD_CASTAMOUNT"]) : '';           // 입금총액(무통장입금)
$LGD_CASCAMOUNT          = isset($_POST["LGD_CASCAMOUNT"]) ? sanitize_text_field($_POST["LGD_CASCAMOUNT"]) : '';           // 현입금액(무통장입금)
$LGD_CASFLAG             = isset($_POST["LGD_CASFLAG"]) ? sanitize_text_field($_POST["LGD_CASFLAG"]) : '';              // 무통장입금 플래그(무통장입금) - 'R':계좌할당, 'I':입금, 'C':입금취소
$LGD_CASSEQNO            = isset($_POST["LGD_CASSEQNO"]) ? sanitize_text_field($_POST["LGD_CASSEQNO"]) : '';             // 입금순서(무통장입금)
$LGD_CASHRECEIPTNUM      = isset($_POST["LGD_CASHRECEIPTNUM"]) ? sanitize_text_field($_POST["LGD_CASHRECEIPTNUM"]) : '';       // 현금영수증 승인번호
$LGD_CASHRECEIPTSELFYN   = isset($_POST["LGD_CASHRECEIPTSELFYN"]) ? sanitize_text_field($_POST["LGD_CASHRECEIPTSELFYN"]) : '';    // 현금영수증자진발급제유무 Y: 자진발급제 적용, 그외 : 미적용
$LGD_CASHRECEIPTKIND     = isset($_POST["LGD_CASHRECEIPTKIND"]) ? sanitize_text_field($_POST["LGD_CASHRECEIPTKIND"]) : '';      // 현금영수증 종류 0: 소득공제용 , 1: 지출증빙용
$LGD_PAYER     			 = isset($_POST["LGD_PAYER"]) ? sanitize_text_field($_POST["LGD_PAYER"]) : '';      			// 입금자명

/*
 * 구매정보
 */
$LGD_BUYER               = isset($_POST["LGD_BUYER"]) ? sanitize_text_field($_POST["LGD_BUYER"]) : '';                // 구매자
$LGD_PRODUCTINFO         = isset($_POST["LGD_PRODUCTINFO"]) ? sanitize_text_field($_POST["LGD_PRODUCTINFO"]) : '';          // 상품명
$LGD_BUYERID             = isset($_POST["LGD_BUYERID"]) ? sanitize_text_field($_POST["LGD_BUYERID"]) : '';              // 구매자 ID
$LGD_BUYERADDRESS        = isset($_POST["LGD_BUYERADDRESS"]) ? sanitize_text_field($_POST["LGD_BUYERADDRESS"]) : '';         // 구매자 주소
$LGD_BUYERPHONE          = isset($_POST["LGD_BUYERPHONE"]) ? sanitize_text_field($_POST["LGD_BUYERPHONE"]) : '';           // 구매자 전화번호
$LGD_BUYEREMAIL          = isset($_POST["LGD_BUYEREMAIL"]) ? sanitize_text_field($_POST["LGD_BUYEREMAIL"]) : '';            // 구매자 이메일
$LGD_BUYERSSN            = isset($_POST["LGD_BUYERSSN"]) ? sanitize_text_field($_POST["LGD_BUYERSSN"]) : '';             // 구매자 주민번호
$LGD_PRODUCTCODE         = isset($_POST["LGD_PRODUCTCODE"]) ? sanitize_text_field($_POST["LGD_PRODUCTCODE"]) : '';          // 상품코드
$LGD_RECEIVER            = isset($_POST["LGD_RECEIVER"]) ? sanitize_text_field($_POST["LGD_RECEIVER"]) : '';             // 수취인
$LGD_RECEIVERPHONE       = isset($_POST["LGD_RECEIVERPHONE"]) ? sanitize_text_field($_POST["LGD_RECEIVERPHONE"]) : '';        // 수취인 전화번호
$LGD_DELIVERYINFO        = isset($_POST["LGD_DELIVERYINFO"]) ? sanitize_text_field($_POST["LGD_DELIVERYINFO"]) : '';         // 배송지


$order_id = $LGD_OID;
$config = gnupay_lguplus_get_config_payment( $order_id );
$pay_ids = gnupay_lguplus_get_settings('pay_ids');

$order = wc_get_order( $order_id );

if( !$order ){  //해당 주문이 없다면
    $resultMSG = "EXISTS NOT ORDER";
    exit;
}

$LGD_MERTKEY             = $config['de_lguplus_mert_key'];          //LG유플러스에서 발급한 상점키로 변경해 주시기 바랍니다.

$LGD_HASHDATA2 = md5($LGD_MID.$LGD_OID.$LGD_AMOUNT.$LGD_RESPCODE.$LGD_TIMESTAMP.$LGD_MERTKEY);

/*
 * 상점 처리결과 리턴메세지
 *
 * OK  : 상점 처리결과 성공
 * 그외 : 상점 처리결과 실패
 *
 * ※ 주의사항 : 성공시 'OK' 문자이외의 다른문자열이 포함되면 실패처리 되오니 주의하시기 바랍니다.
 */
$resultMSG = __("결제결과 상점 DB처리(LGD_CASNOTEURL) 결과값을 입력해 주시기 바랍니다.", GNUPAY_LGUPLUS);

if ( $LGD_HASHDATA2 == $LGD_HASHDATA ) { //해쉬값 검증이 성공이면
    if ( "0000" == $LGD_RESPCODE ){ //결제가 성공이면
        if( "R" == $LGD_CASFLAG ) {
            /*
             * 무통장 할당 성공 결과 상점 처리(DB) 부분
             * 상점 결과 처리가 정상이면 "OK"
             */
            //if( 무통장 할당 성공 상점처리결과 성공 )
            $resultMSG = "OK";
        }else if( "I" == $LGD_CASFLAG ) {
            /*
             * 무통장 입금 성공 결과 상점 처리(DB) 부분
             * 상점 결과 처리가 정상이면 "OK"
             */

            $od_casseqno = get_post_meta( $order_id, '_od_casseqno', true );
            $order_tno = get_post_meta( $order_id, '_od_tno', true );
            $pay_options = get_option( $plugin_id . $payment_id . '_settings' );

            if( !$od_casseqno ){    //한번만 실행 되도록

                update_post_meta($order_id, '_od_receipt_price', isset($LGD_AMOUNT) ? $LGD_AMOUNT : '');   //입금한 금액
                update_post_meta($order_id, '_od_receipt_time', isset($LGD_PAYDATE) ? $LGD_PAYDATE : '');   //입금처리한 시간
                update_post_meta($order_id, '_od_casseqno', isset($LGD_CASSEQNO) ? $LGD_CASSEQNO : '');
                
                do_action( 'gnupay_lgu_virtualaccount_deposit', $order_id, $order_tno );
            }

            if( isset($LGD_TID) && $order_tno != $LGD_TID ){
                $order->add_order_note( __('해당 주문번호랑 틀린 잘못된 pg 요청이 들어왔습니다.', GNUPAY_LGUPLUS) );
                return;
            }

            // 주문정보 체크 ( 업데이트 처리 )
            $order->update_status( sir_lguplus_get_order_status($pay_options['de_deposit_after_status']), sprintf(__( '가상계좌에 금액 %s 이 입금 되었습니다.', GNUPAY_LGUPLUS ), (float) $LGD_AMOUNT ) );

            //if( 무통장 입금 성공 상점처리결과 성공 )
            $resultMSG = "OK";

        }else if( "C" == $LGD_CASFLAG ) {
            /*
             * 무통장 입금취소 성공 결과 상점 처리(DB) 부분
             * 상점 결과 처리가 정상이면 "OK"
             */
            //if( 무통장 입금취소 성공 상점처리결과 성공 )
            $resultMSG = "OK";
        }
    } else { //결제가 실패이면
        /*
         * 거래실패 결과 상점 처리(DB) 부분
         * 상점결과 처리가 정상이면 "OK"
         */
        //if( 결제실패 상점처리결과 성공 )
        $resultMSG = "OK";
    }
} else { //해쉬값이 검증이 실패이면
    /*
     * hashdata검증 실패 로그를 처리하시기 바랍니다.
     */
    $resultMSG = __('결제결과 상점 DB처리(LGD_CASNOTEURL) 해쉬값 검증이 실패하였습니다.', GNUPAY_LGUPLUS);
}

echo $resultMSG;

exit;

?>
<?php
if( ! defined( 'ABSPATH' ) ) exit;

global $woocommerce;

$page_return_url  = $woocommerce->cart->get_checkout_url();

/*
xpay_approval.php 에서 세션에 저장했던 파라미터 값이 유효한지 체크
세션 유지 시간(로그인 유지시간)을 적당히 유지 하거나 세션을 사용하지 않는 경우 DB처리 하시기 바랍니다.
*/

$payReqMap = WC()->session->get('PAYREQ_MAP');  //결제 요청시, Session에 저장했던 파라미터 MAP

if( ! $payReqMap ){
    gp_alert('세션이 만료 되었거나 유효하지 않은 요청 입니다.', $page_return_url);
}

$title = __('LG유플러스 eCredit 서비스 결제', GNUPAY_LGUPLUS);

$LGD_RESPCODE = isset($_REQUEST['LGD_RESPCODE']) ? sanitize_text_field($_REQUEST['LGD_RESPCODE']) : '';
$LGD_RESPMSG  = isset($_REQUEST['LGD_RESPMSG']) ? sanitize_text_field(@iconv("EUC-KR","UTF-8//IGNORE", $_REQUEST['LGD_RESPMSG'])) : '';

$LGD_PAYKEY   = '';

$LGD_OID          = $payReqMap['LGD_OID'];

$data = maybe_unserialize(base64_decode(get_post_meta( $LGD_OID, '_order_tmp_lguplus', true )));

$form_action_url = add_query_arg(array('wc-api'=>'gnupay_lguplus_returnurl', 'lgupay'=>'actionpay'), home_url( '/' ));

if($LGD_RESPCODE == '0000') {
    $LGD_PAYKEY                = isset($_REQUEST['LGD_PAYKEY']) ? sanitize_text_field($_REQUEST['LGD_PAYKEY']) : '';
    $payReqMap['LGD_RESPCODE'] = $LGD_RESPCODE;
    $payReqMap['LGD_RESPMSG']  = $LGD_RESPMSG;
    $payReqMap['LGD_PAYKEY']   = $LGD_PAYKEY;
} else {
    gp_alert('LGD_RESPCODE:' . $LGD_RESPCODE . ' ,LGD_RESPMSG:' . iconv('EUC-KR', 'UTF-8', $LGD_RESPMSG), $page_return_url); //인증 실패에 대한 처리 로직 추가
}
?>

<?php
$exclude = array('res_cd', 'LGD_PAYKEY');

lguplus_new_html_header('', $title);

echo '<form name="forderform" method="post" action="'.$form_action_url.'" autocomplete="off">'.PHP_EOL;

echo gp_make_order_field($data, $exclude);

echo '<input type="hidden" name="res_cd" value="'.$LGD_RESPCODE.'">'.PHP_EOL;
echo '<input type="hidden" name="LGD_PAYKEY" value="'.$LGD_PAYKEY.'">'.PHP_EOL;

echo '</form>'.PHP_EOL;
?>

<div>
    <div id="show_progress">
        <span style="display:block; text-align:center;margin-top:120px"><img src="<?php echo GNUPAY_LGUPLUS_URL; ?>img/loading.gif" alt=""></span>
        <span style="display:block; text-align:center;margin-top:10px; font-size:14px"><?php _e('주문완료 중입니다. 잠시만 기다려 주십시오.', GNUPAY_LGUPLUS); ?></span>
    </div>
</div>

<script type="text/javascript">
    setTimeout( function() {
        document.forderform.submit();
    }, 300);
</script>
<?php
lguplus_new_html_footer();
exit;
?>
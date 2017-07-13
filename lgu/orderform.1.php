<?php
if( ! defined( 'ABSPATH' ) ) exit; // 개별 페이지 접근 불가

if( GNUPAY_LGUPLUS_MOBILE ){  //모바일이면
    include( __DIR__ ."/m_orderform.1.php");
    return;
}

$xpay_crossplatform_js = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']=='on') ? 'https' : 'http';
$xpay_crossplatform_js .= '://xpay.uplus.co.kr/xpay/js/xpay_crossplatform.js';

wp_enqueue_script( 'gp_xpay_crossplatform_js', $xpay_crossplatform_js );
?>
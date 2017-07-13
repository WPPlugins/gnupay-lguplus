<?php
if( ! defined( 'ABSPATH' ) ) exit; // 개별 페이지 접근 불가

add_action( 'wp_footer', 'gnupay_lguplus_orderform_hidden', 10 );

if( !function_exists('gnupay_lguplus_orderform_hidden') ){
function gnupay_lguplus_orderform_hidden(){

    $config = GNUPAY_LGUPLUS()->config;
    extract(GNUPAY_LGUPLUS()->goodsinfo);

    $LGD_CUSTOM_PROCESSTYPE = 'TWOTR';
    $currency_string = 'WON';   //한국 WON ( 기본 )

    if( in_array( get_woocommerce_currency(), array('AUD', 'ARS', 'CAD', 'CLP', 'COP', 'HKD', 'MXN', 'NZD', 'SGD', 'USD') ) ){   //달러
        $currency_string = 'USD';
    }
    //$useescrow = isset($useescrow) ? $useescrow : '';
?>
<div id="LGD_PAYREQUEST" style="display:none">
<form id="gnupay_lguplus_form" name="gnupay_lguplus_form" method="POST" action="<?php echo add_query_arg(array('wc-api'=>'gnupay_lguplus_returnurl', 'lgupay'=>'xpay_approval'), home_url( '/' )); ?>">
<input type="hidden" name="LGD_OID"                     id="LGD_OID"            value="">                                   <!-- 주문번호 -->
<input type="hidden" name="LGD_BUYER"                   id="LGD_BUYER"          value="">                                  <!-- 구매자 -->
<input type="hidden" name="LGD_PRODUCTINFO"             id="LGD_PRODUCTINFO"    value="<?php echo $goods; ?>">             <!-- 상품정보 -->
<input type="hidden" name="LGD_AMOUNT"                  id="LGD_AMOUNT"         value="">                                  <!-- 결제금액 -->
<input type="hidden" name="LGD_CUSTOM_FIRSTPAY"         id="LGD_CUSTOM_FIRSTPAY" value="">                                 <!-- 결제수단 -->
<input type="hidden" name="LGD_BUYEREMAIL"              id="LGD_BUYEREMAIL"     value="">                                  <!-- 구매자 이메일 -->
<input type="hidden" name="LGD_TAXFREEAMOUNT"           id="LGD_TAXFREEAMOUNT"  value="<?php echo $comm_free_mny; ?>">     <!-- 결제금액 중 면세금액 -->
<input type="hidden" name="LGD_BUYERID"                 id="LGD_BUYERID"        value="<?php echo $LGD_BUYERID; ?>">       <!-- 구매자ID -->
<input type="hidden" name="LGD_CASHRECEIPTYN"           id="LGD_CASHRECEIPTYN"  value="N">                                 <!-- 현금영수증 사용 설정 -->
<input type="hidden" name="LGD_BUYERPHONE"              id="LGD_BUYERPHONE"     value="">                                  <!-- 구매자 휴대폰번호 -->
<input type="hidden" name="LGD_EASYPAY_ONLY"            id="LGD_EASYPAY_ONLY"   value="">                                  <!-- 페이나우 결제 호출 -->

<input type="hidden" name="good_mny"          value="<?php echo $tot_price ?>" >

<input type="oid"   name="oid" value="" >
</form>
</div>
<?php } //end function
}
?>
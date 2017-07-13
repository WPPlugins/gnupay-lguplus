<?php
if( ! defined( 'ABSPATH' ) ) exit;

//https://docs.woothemes.com/wc-apidocs/class-WC_Order.html

$get_items = $order->get_items();
$od_tno = get_post_meta( $order_id, '_od_tno', true );
$od_receipt_price = get_post_meta( $order_id, '_od_receipt_price', true );
$od_receipt_time = get_post_meta( $order_id, '_od_receipt_time', true );
$od_test = get_post_meta( $order_id, '_od_test', true );
$config = $payoptions;

if( $od_test ){ //테스트이면
    wp_enqueue_script( 'gp_uplus_receipt_link_js', 'http://pgweb.uplus.co.kr:7085/WEB_SERVER/js/receipt_link.js' );
} else {
    wp_enqueue_script( 'gp_uplus_receipt_link_js', 'http://pgweb.uplus.co.kr/WEB_SERVER/js/receipt_link.js' );
}
wp_enqueue_script( 'gp_uplus_user_detail_page', GNUPAY_LGUPLUS_URL.'js/user_detail.js' );
?>
<h2><?php _e('결제정보', GNUPAY_LGUPLUS); ?></h2>
<table class="shop_table order_details">
<tbody>

<?php if( $od_test ){   //테스트이면 ?>
<tr>
    <th scope="row"><?php _e('테스트결제', GNUPAY_LGUPLUS); ?></th>
    <td>
    <?php
    _e('해당 결제건은 테스트로 결제되었습니다.( 실결제가 아닙니다. )', GNUPAY_LGUPLUS);
    ?>
    </td>
</tr>
<?php } ?>

<?php if($app_no_subj && $od_tno){ ?>
<tr>
    <th scope="row"><?php echo $app_no_subj; ?></th>
    <td><?php echo $app_no; ?></td>
</tr>
<?php } ?>

<tr>
    <th scope="row"><?php _e('결제금액', GNUPAY_LGUPLUS); ?></th>
    <td>
    <?php
    if( $od_receipt_price > 0 ){
        echo wc_price($od_receipt_price);
    } else {
        _e('아직 입금되지 않았거나 입금정보를 입력하지 못하였습니다.', GNUPAY_LGUPLUS);
    }
    ?>
    </td>
</tr>

<?php if( $payment_method == $pay_ids['vbank'] && $od_receipt_time ){   //가상계좌이고 입금처리된 시간이 있으면 ?>
<tr>
    <th scope="row"><?php _e('입금처리된시간', GNUPAY_LGUPLUS); ?></th>
    <td><?php echo date('Y-m-d H:i:s' , strtotime($od_receipt_time)); ?></td>
</tr>
<?php } ?>

<?php if($disp_bank && $od_tno){ ?>
<?php if($od_bankname){ ?>
<tr>
    <th scope="row"><?php _e('입금은행', GNUPAY_LGUPLUS); ?></th>
    <td><?php echo esc_attr($od_bankname); ?></td>
</tr>
<?php } ?>
<?php if($od_deposit_name){ ?>
<tr>
    <th scope="row"><?php _e('입금자명', GNUPAY_LGUPLUS); ?></th>
    <td><?php echo esc_attr($od_deposit_name); ?></td>
</tr>
<?php } ?>
<?php if($od_bank_account){ ?>
<tr>
    <th scope="row"><?php _e('입금계좌', GNUPAY_LGUPLUS); ?></th>
    <td><?php echo esc_attr($od_bank_account); ?></td>
</tr>
<?php } ?>
<?php } ?>

<?php if( $disp_receipt && $od_tno ){ ?>
<tr>
    <th scope="row"><?php _e('영수증', GNUPAY_LGUPLUS); ?></th>
    <td>
    <?php
    if($payment_method == $pay_ids['phone']){   //휴대폰

    require_once( GNUPAY_LGUPLUS_PATH.'lgu/settle_lguplus.inc.php' );
    $LGD_TID      = $od_tno;
    $LGD_MERTKEY      = $config['de_lguplus_mert_key'];
    $LGD_HASHDATA = md5($LGD_MID.$LGD_TID.$LGD_MERTKEY);

    $hp_receipt_script = 'showReceiptByTID(\''.$LGD_MID.'\', \''.$LGD_TID.'\', \''.$LGD_HASHDATA.'\');';

    ?>
    <a href="#" class="gp_user_receipt_url" onclick="<?php echo $hp_receipt_script; ?>"><?php _e('영수증 출력', GNUPAY_LGUPLUS); ?></a>
    <?php } ?>
    <?php
    if($payment_method == $pay_ids['card'])  //신용카드
    {
        require_once( GNUPAY_LGUPLUS_PATH.'lgu/settle_lguplus.inc.php' );
        $LGD_TID      = $od_tno;
        $LGD_MERTKEY      = $config['de_lguplus_mert_key'];
        $LGD_HASHDATA = md5($LGD_MID.$LGD_TID.$LGD_MERTKEY);

        $card_receipt_script = 'showReceiptByTID(\''.$LGD_MID.'\', \''.$LGD_TID.'\', \''.$LGD_HASHDATA.'\');';
    ?>
    <a href="#" class="gp_user_receipt_url" onclick="<?php echo $card_receipt_script; ?>"><?php _e('영수증 출력', GNUPAY_LGUPLUS); ?></a>
    <?php
    }
    ?>
    </td>
</tr>
<?php } ?>

<?php
// 현금영수증 발급을 사용하는 경우에만
// 환불된 금액이 없고 현금일 경우에만 현금영수증을 발급 할 수 있습니다. 계좌이체, 가상계좌
if( $payoptions['de_taxsave_use'] && !$order->get_total_refunded() && ($od_receipt_price > 0) && in_array($payment_method, array('lguplus_virtualaccount', 'lguplus_accounttransfer')) ){

require_once(GNUPAY_LGUPLUS_PATH.'lgu/settle_lguplus.inc.php');

switch($payment_method) {
    case $pay_ids['bank'] :    //계좌이체
        $trade_type = 'BANK';
        break;
    case $pay_ids['vbank'] :    //가상계좌
        $trade_type = 'CAS';
        break;
    default:
        $trade_type = 'CR';
        break;
}

$od_cash = get_post_meta( $order_id, '_od_cash', true );
$cash = get_post_meta( $order_id, '_od_cash_info', true );
?>
<tr>
    <th scope="row"><?php _e('현금영수증', GNUPAY_LGUPLUS); ?></th>
    <td>
        <?php if( $od_cash ){   //현금 영수증을 이미 발급 받았다면
        $default_cash = array('receipt_no'=>'');

        $od_cash_info = maybe_unserialize(get_post_meta($order_id, '_od_cash_info', true));
        
        $od_casseqno = get_post_meta( $order_id, '_od_casseqno', true );

        $cash = wp_parse_args($od_cash_info, $default_cash);
        $cash_receipt_script = 'javascript:showCashReceipts(\''.$LGD_MID.'\',\''.$order_id.'\',\''.$od_casseqno.'\',\''.$trade_type.'\',\''.$CST_PLATFORM.'\');';

        ?>
        <a href="#" onclick="<?php echo $cash_receipt_script; ?>"><?php _e('현금영수증 확인하기', GNUPAY_LGUPLUS); ?></a>
        <?php } else { ?>
        <a href="#" onclick="window.open('<?php echo add_query_arg(array('wc-api'=>'gnupay_lguplus_tax', 'order_id'=>$order->id, 'tx'=>'taxsave'), home_url( '/' )); ?>', 'taxsave', 'width=550,height=600,scrollbars=1,menus=0');"><?php _e('현금영수증 발급', GNUPAY_LGUPLUS); ?></a>
        <?php } ?>
    </td>
</tr>
<?php } ?>
</tbody>
</table>
<?php
if( ! defined( 'ABSPATH' ) ) return;

class gnupay_lguplus_metabox{

	/**
	 * Constructor.
	 */
	public function __construct() {
        add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ), 31, 2 );
    }

	/**
	 * Add WC Meta boxes.
	 */
	public function add_meta_boxes($post_type, $post) {

        if( empty($post->ID) ) return;

        if( $payment_method = get_post_meta( $post->ID, '_payment_method', true ) ){
            if( in_array($payment_method, gnupay_lguplus_get_settings('pay_ids')) ){
                foreach ( wc_get_order_types( 'order-meta-boxes' ) as $type ) {
                    $order_type_object = get_post_type_object( $type );

                    add_meta_box( 'woocommerce-lguplus-order-log', __( 'PG 결재', GNUPAY_LGUPLUS ), array($this, 'output'), $type, 'side', 'default' );
                }
            }
        }
    }

    public function output($post){
        global $post;
        
        $order = wc_get_order( $post->ID );
        
		if ( WC()->payment_gateways() ) {
			$payment_gateways = WC()->payment_gateways->payment_gateways();
		} else {
			$payment_gateways = array();
		}

        $pay_ids = gnupay_lguplus_get_settings('pay_ids');

        if( !empty($order->payment_method) && in_array($order->payment_method, $pay_ids) ){
            $config = gnupay_lguplus_get_config_payment( $order->id );

            $payment_method = $order->payment_method;
            $pg_url  = 'http://pgweb.uplus.co.kr/';

            $od_pg = get_post_meta($order->id, '_od_pg', true);   //결제 pg사를 저장
            $od_pay_method = get_post_meta($order->id, '_od_pay_method', true);   //결제 pg사를 저장
            $od_tno = get_post_meta($order->id, '_od_tno', true);   //결제 pg사를 주문번호
            $od_app_no = get_post_meta($order->id, '_od_app_no', true);   //결제 승인 번호
            $od_receipt_price = get_post_meta($order->id, '_od_receipt_price', true);   //결제 금액
			$od_test = get_post_meta($order->id, '_od_test', true);   //테스트체크
			$od_escrow = get_post_meta($order->id, '_od_escrow', true);   //에스크로

            if( $od_test ){ //테스트이면
                wp_enqueue_script( 'gp_uplus_receipt_link_js', 'http://pgweb.uplus.co.kr:7085/WEB_SERVER/js/receipt_link.js' );
            } else {
                wp_enqueue_script( 'gp_uplus_receipt_link_js', 'http://pgweb.uplus.co.kr/WEB_SERVER/js/receipt_link.js' );
            }
        ?>
        <div class="gp_lguplus_admin">
            <table>
                <colgroup>
                <col width="30%">
                <col width="70%">
                </colgroup>
                <tr>
                    <th><?php _e('결제승인', GNUPAY_LGUPLUS); ?></th>
                    <td>
                        <?php echo isset( $payment_gateways[ $payment_method ] ) ? esc_html( $payment_gateways[ $payment_method ]->get_title() ) : esc_html( $payment_method ); ?>
						<br>
						( <?php echo $od_test ? __('테스트결제', GNUPAY_LGUPLUS) : __('실결제', GNUPAY_LGUPLUS); ?> )
						<?php if( $od_escrow ) {
						echo "<br><span style='color:green'>( ".__('에스크로', GNUPAY_LGUPLUS)." )</span>";
						} ?>
                    </td>
                </tr>
                <tr>
                    <th><?php echo __('결제대행사 링크', GNUPAY_LGUPLUS); ?></th>
                    <td><?php echo "<a href=\"{$pg_url}\" target=\"_blank\">".__('LG U+ 바로가기', GNUPAY_LGUPLUS)."</a><br>"; ?></td>
                </tr>
                <tr>
                    <th><?php echo __('pg 거래번호', GNUPAY_LGUPLUS); ?></th>
                    <td><?php echo $od_tno; ?></td>
                </tr>
                <?php
                if( $od_receipt_price && !$order->get_total_refunded() && ($order->get_total() > 0) && in_array($payment_method, array($pay_ids['vbank'], $pay_ids['bank'])) ){     //가상계좌, 계좌이체
                
                $order_id = $order->id;

                // LG유플러스 공통 설정
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

                $cash = get_post_meta( $order_id, '_od_cash_info', true );

                $od_cash = get_post_meta($order_id, '_od_cash', true);
                ?>
                    <tr>
                        <th><?php _e('현금영수증', GNUPAY_LGUPLUS); ?></th>
                        <td>
                        <?php
                        if( $od_cash ){
                            $default_cash = array('receipt_no'=>'');
                            
                            $od_cash_info = maybe_unserialize(get_post_meta($order_id, '_od_cash_info', true));
                            
                            $od_casseqno = get_post_meta( $order_id, '_od_casseqno', true );

                            $cash = wp_parse_args($od_cash_info, $default_cash);
                            $cash_receipt_script = 'javascript:showCashReceipts(\''.$LGD_MID.'\',\''.$order_id.'\',\''.$od_casseqno.'\',\''.$trade_type.'\',\''.$CST_PLATFORM.'\');';

                            ?>
                            <a href="javascript:;" onclick="<?php echo $cash_receipt_script; ?>"><?php _e('현금영수증 확인', GNUPAY_LGUPLUS); ?></a>
                            <?php } else { ?>
                            <a href="#" onclick="window.open('<?php echo add_query_arg(array('wc-api'=>'gnupay_lguplus_tax', 'order_id'=>$order->id, 'tx'=>'taxsave'), home_url( '/' )); ?>', 'taxsave', 'width=550,height=600,scrollbars=1,menus=0');"><?php _e('현금영수증 발급', GNUPAY_LGUPLUS); ?></a>
                            <?php
                            }   //end if $od_cash
                        }   //end if
                        ?>
                        </td>
                    </tr>
            </table>
        </div>
        <?php
        }   //end if
    }
    
}   //end class gnupay_lguplus_metabox

new gnupay_lguplus_metabox();
?>
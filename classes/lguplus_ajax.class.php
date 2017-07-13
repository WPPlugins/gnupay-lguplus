<?php
if( ! defined( 'ABSPATH' ) ) return;

class LGUPLUS_ajax_register {

    public $plugin_id;

    public function __construct(){
        $this->plugin_id = 'woocommerce';

        //LG결제
        add_action("wp_ajax_lgu_xpay_request", array($this, "xpay_request"));
        add_action("wp_ajax_nopriv_lgu_xpay_request", array($this, "xpay_request"));

        add_action("wp_ajax_lguplus_orderdatasave", array($this, "orderdatasave"));
        add_action("wp_ajax_nopriv_lguplus_orderdatasave", array($this, "orderdatasave"));

        add_action("wp_ajax_lguplus_pay_for_order", array($this, "lguplus_pay_for_order"));
        add_action("wp_ajax_nopriv_lguplus_pay_for_order", array($this, "lguplus_pay_for_order"));
    }

    public function xpay_request(){
        global $wpdb;
        
        $order_id = isset($_POST['order_id']) ? (int) $_POST['order_id'] : '';
        
        $config = $this->get_pay_options($order_id);

        include(GNUPAY_LGUPLUS_PATH.'lgu/xpay_request.php');
        exit;
    }

    public function get_pay_options($order_id){

        return gnupay_lguplus_get_config_payment($order_id);

    }

    public function lguplus_pay_ajax(){
        include_once(GNUPAY_LGUPLUS_PATH.'lguplus/m_order_approval.php');
    }

    public function lguplus_pay_for_order(){

        $order_id = isset($_POST['order_id']) ? (int) $_POST['order_id'] : '';

        if( ! gp_lguplus_order_can_view($order_id) ){
            return false;
        }

        $config = $this->get_pay_options($order_id);

        $order = wc_get_order( $order_id );

        $res = array(
            'result'    => false,
            'payment_method'    =>  isset($_POST['payment_method']) ? wc_clean($_POST['payment_method']) : false,
            );

        // Update payment method
        if ( $order->needs_payment() ) {
            $available_gateways = WC()->payment_gateways->get_available_payment_gateways();
            $payment_method = $res['payment_method'];

            if ( ! $payment_method ) {
                $res['error_msg'] = __('유효한 payment gateway 가 아닙니다.', GNUPAY_LGUPLUS);
                die(wp_json_encode($res));
            }

            // Update meta
            update_post_meta( $order_id, '_payment_method', $payment_method );

            if ( isset( $available_gateways[ $payment_method ] ) ) {
                $payment_method_title = $available_gateways[ $payment_method ]->get_title();
            } else {
                $payment_method_title = '';
            }

            update_post_meta( $order_id, '_payment_method_title', $payment_method_title );

            $res['result'] = 'success';
            
            $res = wp_parse_args($res, gnupay_lguplus_process_payment($order, $config));

        }

        echo wp_json_encode($res);
        exit;
    }

    public function orderdatasave(){
        global $wpdb;

        $order_id = isset($_POST['oid']) ? (int) $_POST['oid'] : '';

        if( !$order_id ){
            return false;
        }

        if( ! gp_lguplus_order_can_view($order_id) ){
            return false;
        }

        $dt_data = base64_encode(maybe_serialize($_POST));

        update_post_meta($order_id, '_order_tmp_lguplus', $dt_data);   //에스크로 결제시

        exit;
    }
}   //end class

new LGUPLUS_ajax_register();
?>
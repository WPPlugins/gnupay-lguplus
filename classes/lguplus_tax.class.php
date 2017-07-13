<?php
if( ! defined( 'ABSPATH' ) ) return;

//https://docs.woothemes.com/wc-apidocs/class-WC_Order.html
//세금관련
// https://docs.woothemes.com/document/setting-up-taxes-in-woocommerce/
class gnupay_lguplus_tax extends WC_Settings_API { 
    public function __construct() {
        add_action('woocommerce_api_'.__CLASS__, array($this, 'api_request') );
    }

    function tax_result($params){
        extract($params);
        
        $config = gnupay_lguplus_get_config_payment($order_id);

        include_once(GNUPAY_LGUPLUS_PATH.'template/tax_result.php');
        exit;
    }

    function api_request(){

        //권한을 체크해야 한다.
        $check_param = array('tx', 'order_id', 'lguplus_nonce', 'lguplus_cli_nonce');
        $params = array();

        foreach($check_param as $v){
            $params[$v] = isset($_REQUEST[$v]) ? sanitize_text_field($_REQUEST[$v]) : '';
        }

        extract($params);

        if( ! gp_lguplus_order_can_view($order_id) ){
            gp_alert(__('권한이 없습니다.', GNUPAY_LGUPLUS));
        }

        if( $tx == 'tax_result' && wp_verify_nonce( $lguplus_nonce, 'lguplus_taxsave' ) ){
            return $this->tax_result($params);
        }

		$order = wc_get_order($order_id);

        if (!$order)
            wp_die(__('주문서가 존재하지 않습니다.', GNUPAY_LGUPLUS));

        $config = gnupay_lguplus_get_config_payment($order_id);

        $oinfo = gnupay_lguplus_process_payment($order, $config);

        $goods_name = $oinfo['goods'];
        
        //$amt_tot = (int)($od['od_receipt_price'] - $od['od_refund_price']);
        //$amt_tot = $order->get_formatted_order_total();

        $amount = $order->get_total() - $order->get_total_refunded();      // 현재 금액

        $dir = 'lguplus';
        $od_name = esc_attr($order->billing_last_name.$order->billing_first_name);
        $od_email = $order->billing_email;
        $od_tel = $order->billing_phone;

        $od_tax_mny = get_post_meta($order->id, '_od_tax_mny', true);
        $od_vat_mny = get_post_meta($order->id, '_od_vat_mny', true);
        $od_free_mny = get_post_meta($order->id, '_od_free_mny', true);
        $od_tax_flag = get_post_meta($order->id, '_od_tax_flag', true);

        $amt_sup = 0;   //공급가액 초기화
        $amt_tax = 0;   //부가가치세 초기화

        if($od_tax_flag){   //복합과세로 결제했다면
            $amt_tot = (int)$od_tax_mny + (int)$od_vat_mny + (int)$od_free_mny;     //거래금액 총합
            $amt_sup = (int)$od_tax_mny + (int)$od_free_mny;        //공급가액
            $amt_tax = (int)$od_vat_mny;        //부가가치세
        } else {
            $amt_tot = $amount;
            $amt_sup = round($amount / 1.1);
            $amt_tax = $amt_tot - $amt_sup;
        }

        $amt_svc = 0;   //봉사료 ( 봉사료는 뭘까? )
        
        $trad_time = date("YmdHis");

        $title = sprintf(__("주문번호 %s 현금영수증 발행", GNUPAY_LGUPLUS), $order_id);

        lguplus_new_html_header('', $title);
        include_once(GNUPAY_LGUPLUS_PATH.'template/taxsave_form.php');
        lguplus_new_html_footer();

        exit;
    }
}

new gnupay_lguplus_tax();
?>
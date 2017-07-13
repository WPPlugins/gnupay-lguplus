<?php
if( ! defined( 'ABSPATH' ) ) return;

class lguplus_virtualaccount extends lguplus_card_gateway
{

    public function get_the_id() {
        $pay_ids = gnupay_lguplus_get_settings('pay_ids');

        return $pay_ids['vbank'];   //가상계좌
    }

    public function __construct() {
        parent::__construct();

        add_action('woocommerce_api_'.__CLASS__, array($this, 'api_request') );
    }

    public function get_the_title(){
        return __('LG U+ 가상계좌', GNUPAY_LGUPLUS);
    }

    public function get_the_description() {

        /*
        if( $error = $this->pay_bin_check() ){
        }
        */

        return '';
    }

    public function is_valid_for_use(){

        $is_vaild = true;

        if( ! in_array( get_woocommerce_currency(), array('KRW') ) ){
            return false;
        }

        $lguplus_options = gp_lguplus_get_card_options();

        if( !$lguplus_options['de_lguplus_mid'] ){     //실결제일때 사이트키가 없으면
            return false;
        }

        return $is_vaild;
    }

    public function init_form_fields(){
        $config = $this->config;

        $this->form_fields = array(
            'enabled' => array(
                'title' => __('Enable/Disable', 'woocommerce'),
                'type' => 'checkbox',
                'label' => sprintf(__('%s 를 활성화합니다.', GNUPAY_LGUPLUS), $this->get_the_title()),
                'default' => 'no',
            ),
            'title' => array(
                'title' => __('Title', 'woocommerce'),
                'type' => 'text',
                'default' => $this->get_the_title(),
            ),
            'description' => array(
                'title' => __('Description', 'woocommerce'),
                'type' => 'textarea',
                'default' => sprintf(__('%s 로 결제합니다.', GNUPAY_LGUPLUS), $this->get_the_title()),
            ),
            'instructions' => array(
                'title' => __('Instructions', 'woocommerce'),
                'type' => 'textarea',
                'description' => __('Instructions that will be added to the thank you page.', 'woocommerce'),
                'default' => '',
                'desc_tip' => true,
            ),
            'de_pay_complete_status'     =>  array(
                'title' => __( '가상계좌 입금 요청전 주문상태', GNUPAY_LGUPLUS ),
                'description'   =>  __( 'LG U+ 가상계좌 입금 요청전 주문상태를 지정합니다.', GNUPAY_LGUPLUS ),
                'type'              => 'select',
                'class'             => 'wc-enhanced-select',
                'css'               => 'width: 450px;',
                'label' => __( '가상계좌 입금 요청전 주문상태', GNUPAY_LGUPLUS ),
                'default' => 'wc-on-hold',
                'options' => wc_get_order_statuses(),
				'desc_tip'          => true,
				'custom_attributes' => array(
					'data-placeholder' => __('선택해 주세요.', GNUPAY_LGUPLUS )
				)
            ),
            'de_deposit_after_status'     =>  array(
                'title' => __( '가상계좌 입금 후 주문상태', GNUPAY_LGUPLUS ),
                'description'   =>  __( '가상계좌 입금 후 주문상태를 지정합니다.', GNUPAY_LGUPLUS ),
                'type'              => 'select',
                'class'             => 'wc-enhanced-select',
                'css'               => 'width: 450px;',
                'label' => __( '가상계좌 입금 후 주문상태 주문상태', GNUPAY_LGUPLUS ),
                'default' => 'wc-processing',
                'options' => wc_get_order_statuses(),
				'desc_tip'          => true,
				'custom_attributes' => array(
					'data-placeholder' => __('선택해 주세요.', GNUPAY_LGUPLUS )
				)
            ),
            'de_deposit_period'     =>  array(
                'title' => __( '가상계좌 입금 기간 설정', GNUPAY_LGUPLUS ),
                'description'   =>  __( '가상계좌 입금 기간을 설정합니다.', GNUPAY_LGUPLUS ),
                'type'              => 'select',
                'class'             => 'wc-enhanced-select',
                'css'               => 'width: 450px;',
                'label' => __( '가상계좌 입금 후 주문상태 주문상태', GNUPAY_LGUPLUS ),
                'default' => '6',
                'options' => array( '2'=>'2', '3'=>'3', '4'=>'4', '5'=>'5', '6'=>'6', '7'=>'7' ),
				'desc_tip'          => true,
				'custom_attributes' => array(
					'data-placeholder' => __('선택해 주세요.', GNUPAY_LGUPLUS )
				)
            ),
        );
    }

    //가상계좌 입금 처리

    public function api_request(){

        if( $config = $this->config ){
            
            $plugin_id = $this->plugin_id;
            $payment_id = $this->id;

            require GNUPAY_LGUPLUS_PATH.'lgu/settle_lg_common.php';

        }   //end if config
    }   //end function api_request

    public function process_admin_options(){

        $result = parent::process_admin_options();
    
        if( $result ){
            $options = apply_filters( 'woocommerce_settings_api_sanitized_fields_' . $this->id, $this->sanitized_fields );

            $lguplus_options = get_option( $this->plugin_id . $this->gnupay_lguplus_card . '_settings' );
            
            $op_enabled = $options['enabled'] == 'yes' ? 1 : 0;

            if(isset($lguplus_options['de_vbank_use']) && $op_enabled != $lguplus_options['de_vbank_use']){

                $lguplus_options['de_vbank_use'] = $op_enabled;

                update_option( $this->plugin_id . $this->gnupay_lguplus_card . '_settings', $lguplus_options );
            }
        }

        return $result;

    }

}   //end class lguplus_virtualaccount
?>
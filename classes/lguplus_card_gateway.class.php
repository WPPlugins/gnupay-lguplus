<?php
if( ! defined( 'ABSPATH' ) ) return;

//https://docs.woothemes.com/document/payment-gateway-api/

if ( class_exists( 'WC_Payment_Gateway' ) ) :

class lguplus_card_gateway extends WC_Payment_Gateway {

	/** @var array Array of locales */
	public $locale;
    public $locale_setting = array();
    public $config = array();
    public $checkout;
    public $gnupay_lguplus_card;
    public $log_enabled;
	public $gnu_errors = array();

    public function get_the_id() {
        return $this->gnupay_lguplus_card;
    }

    public function get_the_icon() {
        return apply_filters('gnupay_lguplus_icon', '');
    }

    public function get_the_title() {
        return __( 'LG U+ 카드결제', GNUPAY_LGUPLUS );
    }

    public function get_the_description() {
        
        $return_html = '';

        $return_html = __( 'LG U+ 카드결제입니다.', GNUPAY_LGUPLUS );
        
        return $return_html;
    }

	/**
	 * Constructor for the gateway.
	 */
	public function __construct() {
        $pay_ids = gnupay_lguplus_get_settings('pay_ids');
        $this->gnupay_lguplus_card = $pay_ids['card'];

        $this->id                 = $this->get_the_id();
        $this->icon               = $this->get_the_icon();
        $this->has_fields         = false;
        $this->method_title       = $this->get_the_title();
		$this->method_description = $this->get_the_description();
        $this->log_enabled = true;

		// Define user set variables
		$this->title        = $this->get_option( 'title' );
		$this->description  = $this->get_option( 'description' );
		$this->instructions = $this->get_option( 'instructions', $this->description );
        $this->de_pay_complete_status   = $this->get_option( 'de_pay_complete_status' );
        $this->de_cancel_possible_status   = $this->get_option( 'de_cancel_possible_status' );

        $this->supports           = array(
            'refunds',   //환불관련
            'products', 
            'subscriptions',
            'subscription_cancellation', 
        );

        $keys = array(
                'de_iche_use',	//계좌이체 결제사용
                'de_vbank_use',	//가상계좌 결제사용
                'de_hp_use',	//휴대폰결제사용
                'de_card_use',	//신용카드결제사용
                'de_card_noint_use',	//시용카드 무이자할부사용
                'de_easy_pay_use',	//PG사 간편결제 버튼 사용
                'de_taxsave_use',	//현금 영수증 발급 사용
                'de_lguplus_mid',	//LGUPLUS KEY CODE
                'de_lguplus_mert_key',	//LGUPLUS MERT KEY
                'de_escrow_use',	//에스크로 사용여부
                'de_card_test',		//결제테스트
                'de_tax_flag_use',	//복합과세 결제
                'de_order_after_action',    //주문
                'de_refund_after_status',   //환불 후 주문상태
            );

        foreach($keys as $key){
            $this->config[$key] = $this->get_lguplus_option( $key );
        }

        if( $error = $this->pay_bin_check() ){
        }

		//그누페이 폴더 및 키 파일 체크
		GNUPAY_LGUPLUS()->lguplus_folder_check();

		// Load the settings.
		$this->init_form_fields();
        $this->init_settings();

		// Actions
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
    	add_action( 'woocommerce_thankyou_'. $this->id, array( $this, 'thankyou_page' ) );

        // 경로 /woocommerce/templates/checkout/form-checkout.php
        add_action( 'woocommerce_before_checkout_form' , array($this, 'lguplus_pay_load') );
        add_action( 'woocommerce_checkout_before_order_review' , array($this, 'lguplus_checkout_order') );
        // 경로 /woocommerce/templates/checkout/form-pay.php
        add_action( 'woocommerce_pay_order_before_submit' , array($this, 'lguplus_pay_load') );
        add_action( 'woocommerce_pay_order_after_submit' , array($this, 'lguplus_checkout_order') );

		if ( ! $this->is_valid_for_use() )
			$this->enabled = 'no';
    }

	public function get_lguplus_common_fields() {
		return apply_filters( 'woocommerce_settings_api_form_fields_' . $this->gnupay_lguplus_card, $this->form_fields );
	}

    public function process_admin_options(){
        $result = parent::process_admin_options();
    
        if( $result ){
            if( $this->id == $this->gnupay_lguplus_card ){     //카드일때만 실행;
                $options = apply_filters( 'woocommerce_settings_api_sanitized_fields_' . $this->id, $this->sanitized_fields );
                
                $pay_ids = gnupay_lguplus_get_settings('pay_ids');
                $store_options = array();

                foreach( $pay_ids as $key=>$pid ){
                    $store_options[$key] = get_option( $this->plugin_id . $pid . '_settings' );
                }
                
                if( isset($options['de_iche_use']) && !empty( $store_options['bank'] ) ){   //계좌이체
                    $store_options['bank']['enabled'] = $options['de_iche_use'] ? 'yes' : 'no';
                    update_option( $this->plugin_id . $pay_ids['bank'] . '_settings', $store_options['bank'] );
                }

                if( isset($options['de_vbank_use']) && !empty( $store_options['vbank'] ) ){   //가상계좌
                    $store_options['vbank']['enabled'] = $options['de_vbank_use'] ? 'yes' : 'no';
                    update_option( $this->plugin_id . $pay_ids['vbank'] . '_settings', $store_options['vbank'] );
                }

                if( isset($options['de_hp_use']) && !empty( $store_options['phone'] ) ){   //핸드폰
                    $store_options['phone']['enabled'] = $options['de_hp_use'] ? 'yes' : 'no';
                    update_option( $this->plugin_id . $pay_ids['phone'] . '_settings', $store_options['phone'] );
                }

                if( isset($options['de_easy_pay_use']) && !empty( $store_options['easy'] ) ){   //간편결제
                    $store_options['easy']['enabled'] = $options['de_easy_pay_use'] ? 'yes' : 'no';
                    update_option( $this->plugin_id . $pay_ids['easy'] . '_settings', $store_options['easy'] );
                }

            }
        }

        return $result;
    }

	public function lguplus_common_settings() {

		// Load form_field settings.
		$this->locale_setting = get_option( $this->plugin_id . $this->gnupay_lguplus_card . '_settings', null );

		if ( ! $this->locale_setting || ! is_array( $this->locale_setting ) ) {

			$this->locale_setting = array();

			// If there are no settings defined, load defaults.
			if ( $form_fields = $this->get_form_fields() ) {

				foreach ( $form_fields as $k => $v ) {
					$this->locale_setting[ $k ] = isset( $v['default'] ) ? $v['default'] : '';
				}
			}
		}

		if ( ! empty( $this->locale_setting ) && is_array( $this->locale_setting ) ) {
			$this->locale_setting = array_map( array( $this, 'format_settings' ), $this->locale_setting );
			$this->enabled  = isset( $this->locale_setting['enabled'] ) && $this->locale_setting['enabled'] == 'yes' ? 'yes' : 'no';
		}
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
    
	public function get_lguplus_option( $key, $empty_value = null ) {

		if ( empty( $this->locale_setting ) ) {
			$this->lguplus_common_settings();
		}

		// Get option default if unset.
		if ( ! isset( $this->locale_setting[ $key ] ) ) {
			$form_fields            = $this->get_lguplus_common_fields();
			$this->locale_setting[ $key ] = isset( $form_fields[ $key ]['default'] ) ? $form_fields[ $key ]['default'] : '';
		}

		if ( ! is_null( $empty_value ) && empty( $this->locale_setting[ $key ] ) && '' === $this->locale_setting[ $key ] ) {
			$this->locale_setting[ $key ] = $empty_value;
		}

		return $this->locale_setting[ $key ];
	}

	public function can_refund_order( $order ) {
		return $order && $order->get_transaction_id();
	}

	/**
	 * Logging method.
	 * @param string $message
	 */
	public function log( $message ) {
		if ( $this->log_enabled ) {
			if ( empty( $this->log ) ) {
				$this->log = new WC_Logger();
			}
			$this->log->add( 'lguplus', $message );
		}
	}

    public function process_refund( $order_id, $amount = null, $reason = '' ) { //환불관련 ( 부분환불 ) 관리자 페이지에서만 사용가능

        if( !is_admin() ){
            return false;
        }

        if( !$reason ){
            return new WP_Error( 'error', __( '환불요청사유를 입력해 주세요.', GNUPAY_LGUPLUS ) );
        }

        $order = wc_get_order( $order_id );

        if( !in_array($order->payment_method, gnupay_lguplus_get_settings('pay_ids')) ){     //그누페이 LGUPLUS 결제가 아닌경우 리턴 
            return;
        }

        try {
            
            if ( ! $this->can_refund_order( $order ) ) {
                //$this->log( 'Refund Failed: No transaction ID' );
                //return new WP_Error( 'error', __( 'Refund Failed: No transaction ID', 'woocommerce' ) );
            }

            $config = $this->config;
            $mod_memo = $reason;    //취소사유
            $tax_mny = $amount;  //과세금액
            $free_mny = 0;     //비과세금액
            $payment_method = get_post_meta( $order_id, '_payment_method', true );
            $od_tax_flag    = get_post_meta( $order_id, '_od_tax_flag', true );   //과세 및 비과세 사용시
			$pay_ids = gnupay_lguplus_get_settings('pay_ids');

            if( $od_tax_flag ){     //복합과세이면 다시 계산

                $line_item_qtys         = json_decode( sanitize_text_field( stripslashes( $_POST['line_item_qtys'] ) ), true );
                $line_item_totals       = json_decode( sanitize_text_field( stripslashes( $_POST['line_item_totals'] ) ), true );
                $line_item_tax_totals   = json_decode( sanitize_text_field( stripslashes( $_POST['line_item_tax_totals'] ) ), true );

                // Prepare line items which we are refunding
                $line_items = array();
                $item_ids   = array_unique( array_merge( array_keys( $line_item_qtys, $line_item_totals ) ) );

                foreach ( $item_ids as $item_id ) {
                    $line_items[ $item_id ] = array( 'qty' => 0, 'refund_total' => 0, 'refund_tax' => array() );
                }
                foreach ( $line_item_qtys as $item_id => $qty ) {
                    $line_items[ $item_id ]['qty'] = max( $qty, 0 );
                }
                foreach ( $line_item_totals as $item_id => $total ) {
                    $line_items[ $item_id ]['refund_total'] = wc_format_decimal( $total );
                }
                foreach ( $line_item_tax_totals as $item_id => $tax_totals ) {
                    $line_items[ $item_id ]['refund_tax'] = array_map( 'wc_format_decimal', $tax_totals );
                }

                foreach( $line_items as $key=>$v ){
                    if( !isset($v['qty']) || empty($v['qty']) || empty($v['refund_total']) )
                        continue;

                    $refund_tax = 0;

                    foreach($v['refund_tax'] as $rex){
                        if( empty($rex) ) continue;

                        $refund_tax = $refund_tax + (int) $rex;
                    }

                    if( $v['refund_total'] && empty($refund_tax) ){    //세금이 붙지 않으면 비과세
                        $free_mny = $free_mny + (int) $v['refund_total'];       //비과세 금액을 구합니다.
                    }
                }

                $tax_mny = $amount - $free_mny;  //과세금액을 다시 구합니다.

                $get_total_qty_refunded = $order->get_total_qty_refunded();

                if( !$get_total_qty_refunded ){
                    //return new WP_Error( 'error', __( '복합과세로 결제한 주문은 반드시 수량을 체크해야 합니다.', GNUPAY_LGUPLUS ) );
                }
            }

            $currency      = 'WON';

            $od_tno        = get_post_meta( $order_id, '_od_tno', true );
            $price         = $amount;
            $confirm_price = $order->get_remaining_refund_amount();  // 부분취소 잔액
            $remain_before_price = $confirm_price + $amount;  //환불 가능한 총계 ( 나중에 이부분을 다시 볼것 )
            $buyeremail    = $order->billing_email;
            $tax           = isset($refund_tax) ? (int)$refund_tax : 0;
            $taxfree       = (int)$free_mny;

            //return new WP_Error('lguplus_refund_error', $confirm_price.' '.$tax.' '.$taxfree );

            include_once(GNUPAY_LGUPLUS_PATH.'lgu/orderpartcancel.inc.php');

            if( $return_error ){    // failed part refund
                return new WP_Error('lguplus_refund_error', $return_error );
            } else {    // success
                $payment_gateways = gnupay_lguplus_get_gateways();

                $current_user = wp_get_current_user();
                $order->add_order_note( sprintf(__( '%s 님이 %s, ( %s ) 이유로 가격 %s 를 취소하셨습니다. ( PG )', GNUPAY_LGUPLUS ), 
                $current_user->user_login.' ( '.$current_user->ID.' ) ',
                isset( $payment_gateways[ $payment_method ] ) ? esc_html( $payment_gateways[ $payment_method ]->get_title() ) : esc_html( $payment_method ),
                $reason,
                wc_price($amount)
                ) );
            }

            return true;

        } catch ( Exception $e ) {

			return new WP_Error( 'lguplus_refund_error', $e->getMessage() );

        }

		return false;

    }

    public function order_lguplus_refund( $order_id, $amount = null, $reason = '' ) { //환불관련 ( 주문건을 환불처리 합니다. )

        $config = $this->config;
        $lguplus_order = wc_get_order( $order_id );

        try {

            $tno = get_post_meta( $order_id, '_od_tno', true );
            if( ! $tno ){
                return new WP_Error('lguplus_refund_error', __('pg 거래번호가 저장되지 않았거나 없습니다.', GNUPAY_LGUPLUS));
            }

            include_once(GNUPAY_LGUPLUS_PATH.'lgu/settle_lguplus.inc.php');

            $LGD_TID    = $tno;        //LG유플러스으로 부터 내려받은 거래번호(LGD_TID)

            $xpay = new XPay($configPath, $CST_PLATFORM);

            // Mert Key 설정
            $xpay->set_config_value('t'.$LGD_MID, $config['de_lguplus_mert_key']);
            $xpay->set_config_value($LGD_MID, $config['de_lguplus_mert_key']);
            $xpay->Init_TX($LGD_MID);

            $xpay->Set("LGD_TXNAME", "Cancel");
            $xpay->Set("LGD_TID", $LGD_TID);

            if ($xpay->TX()) {
                //1)결제취소결과 화면처리(성공,실패 결과 처리를 하시기 바랍니다.)
                /*
                echo "결제 취소요청이 완료되었습니다.  <br>";
                echo "TX Response_code = " . $xpay->Response_Code() . "<br>";
                echo "TX Response_msg = " . $xpay->Response_Msg() . "<p>";
                */
                $current_user = wp_get_current_user();
                $lguplus_order->add_order_note( sprintf(__( '%s 님의 요청으로 인해 %s 이 환불되었습니다.', GNUPAY_LGUPLUS ), 
                    $current_user->user_login.' ( '.$current_user->ID.' ) ',
                    wc_price($amount)
                ) );

                return true;

            } else {
                //2)API 요청 실패 화면처리
                $msg = __("결제 취소요청이 실패하였습니다.<br>", GNUPAY_LGUPLUS_URL);
                $msg .= "TX Response_code = " . $xpay->Response_Code() . "<br>";
                $msg .= "TX Response_msg = " . $xpay->Response_Msg();

                return new WP_Error( 'lguplus_refund_error', $msg );
            }

            return new WP_Error( 'lguplus_refund_error', __('알수 없는 이유로 부분 환불이 되지 않았습니다', GNUPAY_LGUPLUS_URL) );

        } catch ( Exception $e ) {

			return new WP_Error( 'lguplus_refund_error', $e->getMessage() );

        }

		return false;

    }

    public function lguplus_config(){
        $args = wp_parser_args( $this->config, array(

            ));
        return $args;
    }

    public function lguplus_pay_load( $checkout ){
        global $wp, $woocommerce;
        
        //체크아웃일때만 작동
        if( !is_checkout() )
            return;

        if( GNUPAY_LGUPLUS()->is_lguplus_pay_load ){   //한번만 실행되게 한다.
            return;
        }

        GNUPAY_LGUPLUS()->is_lguplus_pay_load = true;

        $config = $this->config;
        $this->checkout = $checkout;

        $goods = '';
        $goods_count = -1;
        $good_info = '';

        $comm_tax_mny = 0; // 과세금액
        $comm_vat_mny = 0; // 부가세
        $comm_free_mny = 0; // 면세금액
        $tot_tax_mny = 0;

        $send_cost  = 0;    //배송비

        $i = 0;

        foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
            if( empty($cart_item) ) continue;

            $_product        = wc_get_product( $cart_item['product_id'] );
            $product_title = $_product->get_title();
            $quantity = $cart_item['quantity'];
            $line_total = $cart_item['line_total'];
			$_tax_stats = $_product->get_tax_status();

            if( $quantity > 1 ){
                $product_title .= ' x '.$quantity;
            }

            if (!$goods){
                $goods = preg_replace("/\'|\"|\||\,|\&|\;/", "", $product_title);
            }

            $goods_count++;

            // 에스크로 상품정보
            if($config['de_escrow_use']) {
                if ($i>0)
                    $good_info .= chr(30);
                $good_info .= "seq=".($i+1).chr(31);
                $good_info .= "ordr_numb=#order_replace#_".sprintf("%04d", $i).chr(31);
                $good_info .= "good_name=".addslashes($product_title).chr(31);
                $good_info .= "good_cntx=".$quantity.chr(31);
                $good_info .= "good_amtx=".$line_total.chr(31);
            }

            // 복합과세금액
            if( wc_tax_enabled() && $config['de_tax_flag_use'] == 'yes' ) {
				if($_tax_stats == 'none'){
					$comm_free_mny += $line_total;;
				} else {
					$tot_tax_mny += $line_total;
				}
            }

            $i++;
        }

        if ($goods_count) $goods = sprintf(__('%s 외 %d 건', GNUPAY_LGUPLUS), $goods, $goods_count);

        // 복합과세처리
        if( wc_tax_enabled() && $config['de_tax_flag_use'] == 'yes' ) {
            $comm_tax_mny = round( WC()->cart->cart_contents_total + WC()->cart->shipping_total - $comm_free_mny ); // 과세금액 ( 카트 )
            $comm_vat_mny = round(WC()->cart->get_taxes_total(false, false));    //부가세( 카트 )
        }

        if ($config['de_escrow_use'] == 1) {
            // 에스크로결제 테스트
            $useescrow = '1';
            if( GNUPAY_LGUPLUS_MOBILE ){    //모바일이면
                $useescrow = '1';
            }
        }
        else {
            // 일반결제 테스트
            $useescrow = '';
        }

        $info = array(
                'od_id' =>  '',
                'goods' =>  $goods,
                'tot_price' =>  WC()->cart->total,
                'useescrow' => $useescrow,
                'goods_count'   =>  ($goods_count + 1),
                'good_info'     =>  $good_info,
                'comm_tax_mny'      =>  $comm_tax_mny,
                'comm_vat_mny'      =>  $comm_vat_mny,
                'comm_free_mny'  =>  $comm_free_mny,
            );

        require_once(GNUPAY_LGUPLUS_PATH.'lgu/settle_lguplus.inc.php');

        GNUPAY_LGUPLUS()->goodsinfo=$info;

        require_once(GNUPAY_LGUPLUS_PATH.'lgu/orderform.1.php');
        
        // 결제대행사별 코드 include (결제대행사 정보 필드)
        require_once(GNUPAY_LGUPLUS_PATH.'lgu/orderform.2.php');

        wp_enqueue_script('jquery');

        if( GNUPAY_LGUPLUS_MOBILE ){ // 모바일이면
            wp_register_script('gnupay_lguplus_pay_js', GNUPAY_LGUPLUS_URL.'js/lguplus_pay.js', array('jquery'), GNUPAY_LGUPLUS_VERSION, true);
        } else {
            wp_register_script('gnupay_lguplus_pay_js', GNUPAY_LGUPLUS_URL.'js/lguplus_pay.js', array('jquery', 'gp_xpay_crossplatform_js'), GNUPAY_LGUPLUS_VERSION , true);
        }


        // Localize the script with new data
        $translation_array = array(
            'is_mobile'=>GNUPAY_LGUPLUS_MOBILE,
            'ajaxurl'=>admin_url('admin-ajax.php'),
            'cst_platform'=>$CST_PLATFORM,
            'lgd_window_type'=>$LGD_WINDOW_TYPE,
            'order_action_url'=>add_query_arg(array('wc-api'=>'gnupay_lguplus_returnurl', 'lgupay'=>'actionpay'), home_url( '/' )),
        );

        if ( ! empty( $wp->query_vars['order-pay'] ) ) {
            $translation_array['order_id'] = $wp->query_vars['order-pay'];
        }

        wp_localize_script( 'gnupay_lguplus_pay_js', 'gnupay_lguplus_object', $translation_array );

        wp_enqueue_script('gnupay_lguplus_pay_js');
    }

    public function lguplus_checkout_order(){
        global $wp, $woocommerce;
        
        $config = $this->config;
    }

    public function thankyou_page(){
        echo apply_filters('gnupay_lguplus_thankyou_msg', __('결제해 주셔서 감사합니다.', GNUPAY_LGUPLUS), $this->id );
    }

    public function init_form_fields(){
        $this->form_fields = array(
            'enabled' => array(
                'title' => __( 'Enable/Disable', 'woocommerce' ),
                'type' => 'checkbox',
                'label' => __( 'LG U+ 카드결제를 활성화합니다.', GNUPAY_LGUPLUS ),
                'default' => ''
            ),
            'title' => array(
                'title' => __( 'Title', 'woocommerce' ),
                'type' => 'text',
                'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce' ),
                'default' => __( 'LG U+ 카드결제', GNUPAY_LGUPLUS ),
                'desc_tip'      => true,
            ),
            'description' => array(
                'title' => __( 'Description', 'woocommerce' ),
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
            'de_card_noint_use' => array(
                'title' => __( '신용카드 무이자할부사용', GNUPAY_LGUPLUS ),
                'type' => 'select',
                'options'	=> array(
                    '0'			=> __( '사용안함', GNUPAY_LGUPLUS ),
                    '1'			=> __( '사용', GNUPAY_LGUPLUS ),
                ),
                'description'   =>  __( '주문시 신용카드 무이자할부를 가능하게 할것인지를 설정합니다.<br>사용으로 설정하시면 PG사 가맹점 관리자 페이지에서 설정하신 무이자할부 설정이 적용됩니다.<br>사용안함으로 설정하시면 PG사 무이자 이벤트 카드를 제외한 모든 카드의 무이자 설정이 적용되지 않습니다.', GNUPAY_LGUPLUS ),
            ),
            'de_taxsave_use'    =>  array(
                'title' => __( '현금영수증 발급사용', GNUPAY_LGUPLUS ),
                'type' => 'select',
                'options'	=> array(
                    '0'			=> __( '사용안함', GNUPAY_LGUPLUS ),
                    '1'			=> __( '사용', GNUPAY_LGUPLUS ),
                ),
                'description'   =>  __( '관리자는 설정에 관계없이 주문 보기에서 발급이 가능합니다.<br>현금영수증 발급 취소는 PG사에서 지원하는 현금영수증 취소 기능을 사용하시기 바랍니다.', GNUPAY_LGUPLUS ),
            ),
            'de_lguplus_mid' => array(
                'title' => __('LG유플러스 상점아이디', GNUPAY_LGUPLUS), 
                'type' => 'lguplus_mid', 
                'description' => __('LG유플러스에서 받은 si_ 로 시작하는 상점 ID를 입력하세요.( 영문자, 숫자 혼용 )<br>만약, 상점 ID가 si_로 시작하지 않는다면 LG유플러스에 사이트코드 변경 요청을 하십시오. 예) si_lguplus', GNUPAY_LGUPLUS),
            ),
            'de_lguplus_mert_key'     =>  array(
                'title' => __( 'LG유플러스 MERT KEY', GNUPAY_LGUPLUS ),
                'type' => 'input',
                'description'   =>  __( 'LG유플러스 상점MertKey는 상점관리자 -> 계약정보 -> 상점정보관리에서 확인하실 수 있습니다.<br>예) 95160cce09854ef44d2edb2bfb05f9f3', GNUPAY_LGUPLUS ),
                'default' => '',
				'custom_attributes' => array(
					'data-placeholder' => __('MERT KEY 를 입력해주세요.', GNUPAY_LGUPLUS )
				)
            ),
            'de_escrow_use'     =>  array(
                'title' => __( '에스크로 사용', GNUPAY_LGUPLUS ),
                'type' => 'select',
                'options'	=> array(
                    '0'			=> __( '일반결제 사용', GNUPAY_LGUPLUS ),
                    '1'			=> __( '에스크로결제 사용', GNUPAY_LGUPLUS ),
                ),
                'description'   =>  __( '에스크로 결제를 사용하시려면, 반드시 결제대행사 상점 관리자 페이지에서 에스크로 서비스를 신청하신 후 사용하셔야 합니다.<br>에스크로 사용시 배송과의 연동은 되지 않으며 에스크로 결제만 지원됩니다.', GNUPAY_LGUPLUS ),
            ),
            'de_card_test'     =>  array(
                'title' => __( '결제테스트', GNUPAY_LGUPLUS ),
                'type' => 'select',
                'options'	=> array(
                    '0'			=> __( '실결제', GNUPAY_LGUPLUS ),
                    '1'			=> __( '테스트 결제', GNUPAY_LGUPLUS ),
                ),
                'default' => '1',
                'description'   =>  __( '신용카드를 테스트 하실 경우에 체크하세요. 결제단위 최소 1,000원', GNUPAY_LGUPLUS ),
            ),
            'de_tax_flag_use'     =>  array(
                'title' => __( '복합과세 결제', GNUPAY_LGUPLUS ),
                'type' => 'checkbox',
                'label' => __( '복합결제 사용', GNUPAY_LGUPLUS ),
                'default' => 'no',
                'description'   =>  __( '복합과세(과세, 비과세) 결제를 사용하려면 체크하십시오.<br >( 우커머스 -> 설정 -> 세금 옵션 -> 세금 활성화 도 같이 체크되어야 합니다. )<br>복합과세 결제를 사용하기 전 PG사에 별도로 결제 신청을 해주셔야 합니다. 사용시 PG사로 문의하여 주시기 바랍니다.', GNUPAY_LGUPLUS ),
            ),
            'de_refund_after_status'     =>  array(
                'title' => __( '환불 후 주문상태', GNUPAY_LGUPLUS ),
                'description'   =>  __( '환불 후 주문상태를 지정합니다.', GNUPAY_LGUPLUS ),
                'type'              => 'select',
                'class'             => 'wc-enhanced-select',
                'css'               => 'width: 450px;',
                'label' => __( '환불 후 주문상태', GNUPAY_LGUPLUS ),
                'default' => 'wc-cancelled',
                'options' => wc_get_order_statuses(),
				'desc_tip'          => true,
				'custom_attributes' => array(
					'data-placeholder' => __('환불 후 주문상태를 선택해 주세요.', GNUPAY_LGUPLUS )
				)
            ),
            'de_pay_complete_status'     =>  array(
                'title' => __( '사용자 결제 후 주문상태', GNUPAY_LGUPLUS ),
                'description'   =>  __( 'LGUPLUS 결제시 사용자의 주문상태를 지정합니다.', GNUPAY_LGUPLUS ),
                'type'              => 'select',
                'class'             => 'wc-enhanced-select',
                'css'               => 'width: 450px;',
                'label' => __( '사용자 결제 후 주문상태', GNUPAY_LGUPLUS ),
                'default' => 'wc-processing',
                'options' => wc_get_order_statuses(),
				'desc_tip'          => true,
				'custom_attributes' => array(
					'data-placeholder' => __('선택해 주세요.', GNUPAY_LGUPLUS )
				)
            ),
            'de_cancel_possible_status'     =>  array(
                'title' => __( '주문취소 가능한 상태 ( 사용자 )', GNUPAY_LGUPLUS ),
                'description'   =>  __( 'LGUPLUS 결제시 사용자가 주문취소 할 수 있는 상태를 지정합니다.', GNUPAY_LGUPLUS ),
                'type'              => 'multiselect',
                'class'             => 'wc-enhanced-select',
                'css'               => 'width: 450px;',
                'label' => __( '주문취소 가능한 상태 ( 사용자 )', GNUPAY_LGUPLUS ),
                'default' => '',
                'options' => wc_get_order_statuses(),
				'desc_tip'          => true,
				'custom_attributes' => array(
					'data-placeholder' => __('주문취소 가능한 상태를 선택해 주세요.', GNUPAY_LGUPLUS )
				)
            ),
        );
    }

    /**
     * Generate key file HTML.
     *
     * @access public
     * @param mixed $key
     * @param mixed $data
     * @since 1.0.0
     * @return string
     */
    public function generate_lguplus_mid_html( $key, $data ) {

		$field    = $this->get_field_key( $key );
		$defaults = array(
			'title'             => '',
			'disabled'          => false,
			'class'             => '',
			'css'               => '',
			'placeholder'       => '',
			'type'              => 'text',
			'desc_tip'          => false,
			'description'       => '',
			'custom_attributes' => array()
		);

        $data = wp_parse_args( $data, $defaults );

        $key_value = $this->get_option( $key );
        ob_start();
        ?>
        <tr valign="top">
            <th scope="row" class="titledesc">
                <label for="<?php echo esc_attr( $field ); ?>"><?php echo wp_kses_post( $data['title'] ); ?></label>
                <?php echo $this->get_tooltip_html( $data ); ?>
                <br>
                <br>
                <a href="http://sir.kr/main/service/lg_pg.php" target="_blank" class="button"><?php _e('LG유플러스 서비스신청하기', GNUPAY_LGUPLUS); ?></a>
            </th>
			<td class="forminp">
				<fieldset>
					<legend class="screen-reader-text"><span><?php echo wp_kses_post( $data['title'] ); ?></span></legend>
					<span class="sitecode" style="font-weight:bold">si_</span> <input class="input-text regular-input <?php echo esc_attr( $data['class'] ); ?>" type="<?php echo esc_attr( $data['type'] ); ?>" name="<?php echo esc_attr( $field ); ?>" id="<?php echo esc_attr( $field ); ?>" style="<?php echo esc_attr( $data['css'] ); ?>" value="<?php echo esc_attr( $this->get_option( $key ) ); ?>" placeholder="<?php echo esc_attr( $data['placeholder'] ); ?>" <?php disabled( $data['disabled'], true ); ?> <?php echo $this->get_custom_attribute_html( $data ); ?> />
					<?php echo $this->get_description_html( $data ); ?>
				</fieldset>
			</td>
        </tr>
        <?php
        return ob_get_clean();

    }

    public function validate_lguplus_key_field($key) {

		$text  = $this->get_option( $key );
		$field = $this->get_field_key( $key );

		if ( isset( $_POST[ $field ] ) ) {
			$text = wp_kses_post( trim( stripslashes( $_POST[ $field ] ) ) );
		}

		return $text;

    }

    public function pay_bin_check(){

        if( ! is_admin() ){
            return;
        }

        if( GNUPAY_LGUPLUS()->credentials_check ){      //중복방지
            return;
        }
    
        GNUPAY_LGUPLUS()->credentials_check = true;     //중복방지

        $config = $this->config;

        // loop through each error and display it
        foreach ( $this->gnu_errors as $value ) {
            ?>
            <div class="error">
                <p><?php echo $value; ?></p>
            </div>
            <?php
        }
    }

	/**
	 * Process the payment and return the result.
	 *
	 * @param int $order_id
	 * @return array
	 */
	public function process_payment( $order_id ) {
        global $woocommerce;
        $order = new WC_Order( $order_id );

        $res = wp_parse_args(array(
			'result'    => 'success',
            'order_id'  =>  $order->id,
            'order_key' =>  $order->order_key,
			'redirect'  => $this->get_return_url( $order )
		), gnupay_lguplus_process_payment($order, $this->config));

		return $res;

	}

    /**
     * Display errors by overriding the display_errors() method
     * @see display_errors()
     */
    public function display_errors( ) {
		
        // loop through each error and display it
        foreach ( $this->errors as $key => $value ) {
            ?>
            <div class="error">
                <p><?php echo $value; ?></p>
            </div>
            <?php
        }
    }


    //키파일 업로드
    public function key_file_upload($files=array()){
        global $wp_filesystem;

        if( !isset($files['tmp_name']) || empty($files['tmp_name']) ){
            return;
        }

        if( substr($files['name'], 0, 3) != 'SIR' ){
            return new WP_Error( GNUPAY_LGUPLUS.'_upload_fail', __( '상점 키파일은 SIR로 시작되는 파일만 업로드 할수 있습니다.', GNUPAY_LGUPLUS) );
        }

        $key_upload_path = '';
        if( $upload_path = gp_lguplus_upload_path() ){

            $key_upload_path = $upload_path.'/keys/';

            if(! is_dir($key_upload_path) ){
                wp_mkdir_p( $key_upload_path );
            }
        } else {
            return new WP_Error( GNUPAY_LGUPLUS.'_upload_pemission', __( '업로드 폴더에 쓰기 권한이 없습니다.', GNUPAY_LGUPLUS) );
        }

        $z = new ZipArchive();
        $zopen = $z->open( $files['tmp_name'], ZIPARCHIVE::CHECKCONS );

        if ( true !== $zopen )
            return new WP_Error( 'incompatible_archive', __( '호환되지 않는 압축파일 입니다.', GNUPAY_LGUPLUS ), array( 'ziparchive_error' => $zopen ) );

        for ( $i = 0; $i < $z->numFiles; $i++ ) {
            $filename = $z->getNameIndex($i);
            if( !in_array( $filename, apply_filters('gp_lguplus_key_files', array('keypass.enc', 'mcert.pem', 'mpriv.pem', 'readme.txt'))) ) {
                return new WP_Error( GNUPAY_LGUPLUS.'_lguplus_keyfile_wrong', __( '키 파일이 잘못되었습니다.', GNUPAY_LGUPLUS ) );
            }
        }

        $upload_file = null;
        if( isset($files['tmp_name']) && !empty($files['tmp_name']) ){
            $upload_file = move_uploaded_file($files['tmp_name'], $key_upload_path.$files['name'] );
        }

        if($upload_file){
            WP_Filesystem();
            
            $filepath = pathinfo( $key_upload_path.$files['name'] );
            $unzipfile = unzip_file( $key_upload_path.$files['name'], GNUPAY_LGUPLUS_KEY_PATH.'/key/'.$filepath['filename'].'/');
            
            if( is_wp_error($unzipfile) ){
                //return new WP_Error( GNUPAY_LGUPLUS.'_keyfile_unzip_error', __( '에러가 발생했습니다.', GNUPAY_LGUPLUS ), array( 'keyfile_unzip_error' => $unzipfile ) );
            } else {
                return $filepath['filename'];
            }

            return $filepath['filename'];
        }

        return '';

    }

}   //end class

endif;  //Class exists lguplus_card_gateway end if
?>
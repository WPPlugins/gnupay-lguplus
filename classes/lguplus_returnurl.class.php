<?php
if( ! defined( 'ABSPATH' ) ) return;

class gnupay_lguplus_returnurl extends WC_Settings_API {

    public function __construct() {
        add_action('woocommerce_api_'.__CLASS__, array($this, 'return_process') );
    }

	public function get_return_url( $order = null ) {

		if ( $order ) {
			$return_url = $order->get_checkout_order_received_url();
		} else {
			$return_url = wc_get_endpoint_url( 'order-received', '', wc_get_page_permalink( 'checkout' ) );
		}

		if ( is_ssl() || get_option('woocommerce_force_ssl_checkout') == 'yes' ) {
			$return_url = str_replace( 'http:', 'https:', $return_url );
		}

		return apply_filters( 'woocommerce_get_return_url', $return_url, $order );
	}

    public function return_process(){
        
        $lgupay = isset($_REQUEST['lgupay']) ? sanitize_text_field($_REQUEST['lgupay']) : '';

        $return_plugin_id = $this->plugin_id;

        switch ($lgupay) {
            case 'actionpay' :
                include_once(GNUPAY_LGUPLUS_PATH.'lgu/actionpay.php');
                break;
            case 'xpay_approval' :
                include_once(GNUPAY_LGUPLUS_PATH.'lgu/xpay_approval.php');
                break;
            case 'returnurl' :
                include_once(GNUPAY_LGUPLUS_PATH.'lgu/m_returnurl.php');
                break;
            case 'return' :
                include_once(GNUPAY_LGUPLUS_PATH.'lgu/returnurl.php');
                break;
            case 'note_url' :
                include_once(GNUPAY_LGUPLUS_PATH.'lgu/note_url.php');
                break;
            case 'mispwapurl' :
                include_once(GNUPAY_LGUPLUS_PATH.'lgu/mispwapurl.php');
                break;
            case 'cancel_url' :
                include_once(GNUPAY_LGUPLUS_PATH.'lgu/cancel_url.php');
                break;
        }

        /*
		//잘못된 파라미터가 있다면 수정
		if( isset($_REQUEST['lgupluspay']) && strstr($_REQUEST['lgupluspay'], '?') ){
			$tmp = explode('?', $_REQUEST['lgupluspay']);
			
			for($i=0,$tmp_count=count($tmp);$i<$tmp_count;$i++){
				if($i === 0){
					$_REQUEST['lgupluspay'] = $tmp[$i];
				} else {
					$arr = explode('=', $tmp[$i]);
					if( $arr[0] ){
						$_REQUEST[$arr[0]] = isset($arr[1]) ? (string)sanitize_text_field($arr[1]) : '';
					}
				}
			}
		}

        $lgupluspay = isset($_REQUEST['lgupluspay']) ? sanitize_text_field($_REQUEST['lgupluspay']) : '';

        $return_plugin_id = $this->plugin_id;

        switch ($lgupluspay) {
            case 'next' :
                include_once(GNUPAY_LGUPLUS_PATH.'lguplus/m_next.php');
                break;
            case 'noti' :
                include_once(GNUPAY_LGUPLUS_PATH.'lguplus/m_noti.php');
                break;
            case 'return' :
                include_once(GNUPAY_LGUPLUS_PATH.'lguplus/returnurl.php');
                break;
            case 'close' :
                echo '<script language="javascript" type="text/javascript" src="https://stdpay.lguplus.com/stdjs/INIStdPay_close.js" charset="UTF-8"></script>';
                break;
            case 'popup' :
                echo '<script language="javascript" type="text/javascript" src="https://stdpay.lguplus.com/stdjs/INIStdPay_popup.js" charset="UTF-8"></script>';
                break;
        }
        */

        exit;
    }
}

new gnupay_lguplus_returnurl();
?>
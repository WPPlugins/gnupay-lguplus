<?php
/**
 *  Plugin Name: GNUPAY - LGUPLUS
 *  Plugin URI: http://sir.kr/main/gnupay/
 *  Description: 우커머스에서 PG사 LG U+ 와 연동하여 결제할수 있는 플러그인입니다.
 *  Author: SIR Soft
 *  Author URI: http://sir.kr
 *  Version: 1.0
 *  Tested up to: 4.5
 *  Text Domain: gnupay-lguplus
 */

if( ! defined( 'ABSPATH' ) ) exit;

if ( !class_exists( 'GNUPAY_LGUPLUS_PAYMENT' ) ) :

Class GNUPAY_LGUPLUS_PAYMENT {
    public $is_pg_load = false;
    public $config;
    public $goodsinfo;
    public $credentials_url;
    public $credentials_check;
    public $is_plugin_active = false;
    public $is_lguplus_pay_load = false;
    public $errs = array();

    public function __construct() {
        //설정 파일을 불러옴

        include_once( plugin_dir_path( __FILE__ ).'config.php' );

        include_once('lib/functions.php');
        include_once('classes/setting.class.php');
        include_once('classes/lguplus_ajax.class.php');

        add_action( 'plugins_loaded', array($this, 'init_gateway_class') );
        add_action( 'init', array($this, 'add_include') );
        add_action( 'init', array($this, 'init_after'), 20 );

        add_filter( 'woocommerce_payment_gateways', array($this, 'add_gateway_class') );

        register_activation_hook( __FILE__  , array( $this, 'install' ) );  //install

        add_action('woocommerce_loaded', array($this, 'loaded_class') );
        
        //번역파일 불러옴
        add_action( 'plugins_loaded', array( $this, 'plugin_load_textdomain') );
        add_action('load_textdomain', array($this, 'load_custom_language'), 10, 2);

        //해당 플러그인이 있는 체크
        add_action( 'admin_notices', array($this, 'plugin_activation_message'), 0 ) ;

        add_action( 'admin_notices', array( $this, 'admin_error_msg') );
        add_action( 'admin_enqueue_scripts', array( $this, 'admin_styles' ) );
    }

    public function admin_error_msg(){

        if( !empty($this->errs) ){
            $msg = join('', $this->errs);
            echo '<div class="fade error"><p>'.$msg.'</p></div>';
        }
    }

    public function loaded_class(){

        if (!headers_sent() && !isset($_SESSION))
            @session_start();

        if( !is_admin() ){
            include_once('classes/lguplus_returnurl.class.php');
            include_once('classes/lguplus_user_order_details.class.php');
            include_once('classes/lguplus_user_cancel.class.php');
            include_once('classes/lguplus_tax.class.php');
        }
    }

	/**
	 * Load Localisation files.
	 *
	 * 아래 위치에 번역 파일을 넣어두면 오버라이드 (덮어쓰기) 됩니다. LOCALE 은 언어셋을 뜻합니다.
	 *
	 *      - WP_LANG_DIR/gnupay/gnupay-LOCALE.mo

     * 아래 위치에 번역 파일을 넣어두면 해당 언어셋이 없는 경우 실행합니다. LOCALE 은 언어셋을 뜻합니다.
	 *      - WP_LANG_DIR/gnupay/gnupay-LOCALE.mo
	 */
    public function plugin_load_textdomain(){
        //번역파일
        $locale = apply_filters( 'plugin_locale', get_locale(), GNUPAY_LGUPLUS );

        load_plugin_textdomain( GNUPAY_LGUPLUS, false, plugin_basename( dirname( __FILE__ ) ) . '/languages' );
    }

    public function load_custom_language($domain, $mofile)
    {
		if ( GNUPAY_LGUPLUS === $domain ) {
			remove_action( 'load_textdomain', array( $this, 'load_custom_language' ), 10, 2 );

			$mofile = basename( $mofile );
			if ( file_exists( WP_LANG_DIR . '/'.GNUPAY_LGUPLUS.'/' . $mofile ) ) {
				load_textdomain( GNUPAY_LGUPLUS, WP_LANG_DIR . '/'.GNUPAY_LGUPLUS.'/' . $mofile );
			}

			add_action( 'load_textdomain', array( $this, 'load_custom_language' ), 10, 2 );
		}
    }

    public function admin_styles(){
        wp_enqueue_style( 'gp_lguplus_admin_styles', GNUPAY_LGUPLUS_URL . '/js/admin.css', array(), GNUPAY_LGUPLUS_VERSION );
    }

    public function replace_textdomain($mofile, $domain)
    {
        if ( GNUPAY_LGUPLUS === $domain )
            return WP_LANG_DIR.'/my-plugin/'.$domain.'-'.get_locale().'.mo';

        return $mofile;
    }

    public function override_textdomain($override, $domain, $mofile){
        if (GNUPAY_LGUPLUS === $domain && plugin_dir_path($mofile) === WP_PLUGIN_DIR.'/'.GNUPAY_LGUPLUS.'/languages/')
            return TRUE;

        return $override;
    }

	/**
	 * What type of request is this?
	 * string $type ajax, frontend or admin.
	 *
	 * @return bool
	 */
	private function is_request( $type ) {
		switch ( $type ) {
			case 'admin' :
				return is_admin();
			case 'ajax' :
				return defined( 'DOING_AJAX' );
			case 'cron' :
				return defined( 'DOING_CRON' );
			case 'frontend' :
				return ( ! is_admin() || defined( 'DOING_AJAX' ) ) && ! defined( 'DOING_CRON' );
		}
	}

    public function install(){
        $upload_dir = array('error'=>'error');
        try{
            $upload_dir = wp_upload_dir();
        } catch (Exception $e) {
        }

        if( empty($upload_dir['error']) ){
            // 디렉토리 생성
            $dir_arr = array (
                $upload_dir['basedir'].'/lguplus',
                $upload_dir['basedir'].'/lguplus/log',
                $upload_dir['basedir'].'/lguplus/log/refund',
            );

            foreach($dir_arr as $dir_name){
                if(wp_mkdir_p($dir_name)){
                    @chmod($dir_name, 0755);
                }
                $htaccess_file = $dir_name.'/.htaccess';
                if ( !file_exists( $htaccess_file ) ) {
                    if ( $handle = @fopen( $htaccess_file, 'w' ) ) {
                        fwrite( $handle, 'Order deny,allow' . "\n" );
                        fwrite( $handle, 'Deny from all' . "\n" );
                        fclose( $handle );
                    }
                }
            }
        }

		$this->lguplus_folder_check();

    }

	//http://wordpress.stackexchange.com/questions/124900/using-wp-filesystem-in-plugins
	public function lguplus_folder_check(){
		if ( is_writable( WP_CONTENT_DIR ) ){

		}	//end if
	}	//end function

	public function recurse_copy($src, $dst) { 
		$dir = opendir($src); 

		if( wp_mkdir_p( $dst) ) {
			while(false !== ( $file = readdir($dir)) ) { 
				if (( $file != '.' ) && ( $file != '..' )) { 
					if ( is_dir($src . '/' . $file) ) { 
						$this->recurse_copy($src . '/' . $file,$dst . '/' . $file); 
					} 
					else { 
						copy($src . '/' . $file,$dst . '/' . $file); 
					} 
				} 
			}

		}
		closedir($dir); 
	} 

    public function plugin_activation_message(){
        if ( ! $this->is_plugin_active ) :
            deactivate_plugins( plugin_basename( __FILE__ ) );			
            $html = '<div class="error">';
                $html .= '<p>';
                    $html .= __( '그누페이 LGUPLUS 플러그인은 우커머스 플러그인을 활성화를 필요로 합니다.', GNUPAY_LGUPLUS );
                $html .= '</p>';
            $html .= '</div><!-- /.updated -->';
            echo $html;
            
        endif;
    }

    public function add_include(){
        if ( $this->is_request( 'admin' ) ) {
            include_once('classes/admin_metabox.class.php');
        } else if( $this->is_request( 'ajax' ) ){

        }

        if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) || class_exists( 'woocommerce' ) ) {
            $this->is_plugin_active = true;
        }
    }

    public function init_after(){
        
        $basename = plugin_basename( __FILE__ );
        add_filter( 'plugin_action_links_' . $basename, array( $this, 'plugin_action_links' ) );

    }

	public function plugin_action_links( $links ) {

        if( $this->is_plugin_active ){
            $action_links = array(
                'settings' => '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=checkout&section=lguplus_card_gateway' ) . '" title="' . esc_attr( __( '그누페이 LGUPLUS 설정', GNUPAY_LGUPLUS ) ) . '">' . __( '설정', GNUPAY_LGUPLUS ) . '</a>',
            );

            $links = array_merge( $action_links, $links );
        }

		return $links;
	}

    public function init_gateway_class(){

        if( !CLASS_EXISTS('WC_Payment_Gateway') )
            return;

        $gateways = gnupay_lguplus_get_settings();

        foreach($gateways as $gateway){
            if( empty($gateway) ) continue;

            $file_path = plugin_dir_path( __FILE__ ).'classes/'.$gateway.'.class.php';

            if( file_exists($file_path) )
                include_once($file_path);
        }

    }

    public function add_gateway_class( $methods ){

        $gateways = gnupay_lguplus_get_settings();

        $methods = wp_parse_args($gateways, $methods);

        return $methods;
    }

}   //end Class;

$GLOBALS['gnupay_lguplus_payment'] = new GNUPAY_LGUPLUS_PAYMENT();

function GNUPAY_LGUPLUS(){
    global $gnupay_lguplus_payment;

    return $gnupay_lguplus_payment;
}

endif;  //Class exists GNUPAY_LGUPLUS_PAYMENT end if
?>
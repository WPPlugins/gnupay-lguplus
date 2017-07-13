<?php
if( ! defined( 'ABSPATH' ) ) exit;

Class GNUPAY_LGUPLUS_WOO_CONSTANTS {
    public function __construct() {

        define( 'GNUPAY_LGUPLUS_VERSION', '1.0' );
        define( 'GNUPAY_LGUPLUS', 'gnupay-lguplus' );
        define( 'GNUPAY_LGUPLUS_ORDER_TMP', '_order_tmp_lguplus' );
        define('GNUPAY_LGUPLUS_DEBUG',  true );

        add_action( 'init', array( $this, 'init' ), 1 );
        add_action( 'init', array( $this, 'init_after' ),21 );

    }

    public function init(){
        //중복 선언 방지
        if ( defined('GNUPAY_LGUPLUS_URL') ) return;

        // 함수 호출을 처음부터 호출하면 에러나기 때문에 적당한 때에 호출한다.
        // 경로 상수
        define('GNUPAY_LGUPLUS_URL',  plugin_dir_url ( __FILE__ ) );
        define('GNUPAY_LGUPLUS_PATH', plugin_dir_path( __FILE__ ) );
        define('GNUPAY_LGUPLUS_SERVER_TIME',    current_time( 'timestamp' ) );
        define('GNUPAY_LGUPLUS_KEY_PATH', WP_CONTENT_DIR.'/gnupay-lguplus');
        define('GNUPAY_LGUPLUS_MOBILE',    wp_is_mobile() );
        //define('GNUPAY_LGUPLUS_MOBILE',    1 );
    }

    public function init_after(){

    }
}

new GNUPAY_LGUPLUS_WOO_CONSTANTS();
?>
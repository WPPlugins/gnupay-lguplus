<?php
if( ! defined( 'ABSPATH' ) ) exit; // 개별 페이지 접근 불가

if( ! function_exists('gp_check_array_sanitize') ){
    //sanitize_text_field check array
    function gp_check_array_sanitize($msg){

        if( is_array($msg) ){
            return array_map( 'sanitize_text_field', $msg );
        }
        return sanitize_text_field($msg);
    }
}

if( ! function_exists('gp_alert') ){
    // 경고메세지를 경고창으로
    function gp_alert($msg='', $url='', $referer_check=false)
    {
        if (!$msg) $msg = __('올바른 방법으로 이용해 주십시오.', GNUPAY_LGUPLUS);

        if ( !$url && $referer_check ){
            $url = wp_get_referer();
        }

        $html = '<meta charset="utf-8">';
        $html .= '<script type="text/javascript">alert("'.$msg.'");';
        if (!$url){
            $html .= 'history.go(-1);';
        }
        $html .= "</script>";

        do_action( 'gp_alert', $msg, $url );
        $html = apply_filters( 'gp_alert', $html, $url );

        echo $html;

        if ($url){
            gp_goto_url($url, true);
        }
        exit;
    }
}

if( ! function_exists('gp_goto_url') ){
    function gp_goto_url($url, $noscript=false)
    {
        $url = str_replace("&amp;", "&", $url);

        if (!headers_sent() && !$noscript)
            header('Location: '.$url);
        else {
            echo '<script>';
            echo 'location.replace("'.$url.'");';
            echo '</script>';
            echo '<noscript>';
            echo '<meta http-equiv="refresh" content="0;url='.$url.'" />';
            echo '</noscript>';
        }
        exit;
    }
}

if( ! function_exists('gp_check_array_sanitize') ){
    //sanitize_text_field check array
    function gp_check_array_sanitize($msg){

        if( is_array($msg) ){
            return array_map( 'sanitize_text_field', $msg );
        }
        return sanitize_text_field($msg);
    }
}

if( ! function_exists('gnupay_lguplus_get_settings') ){
    function gnupay_lguplus_get_settings($options='gateways'){

        return GNUPAY_LGUPLUS_SETTING::getInstance()->get_options($options);

    }
}

if( ! function_exists('gp_lguplus_get_card_options') ){
    function gp_lguplus_get_card_options(){

        $plugin_id= apply_filters('gnupay_lguplus_parent_plugin_id', 'woocommerce_');
        $pay_ids = gnupay_lguplus_get_settings('pay_ids');
        $gnupay_lguplus_card_payname = $pay_ids['card'];
        $payment_options = get_option( $plugin_id . $gnupay_lguplus_card_payname . '_settings', null );
        
        return $payment_options;
    }
}

if( ! function_exists('gnupay_lguplus_get_config_payment') ){
    function gnupay_lguplus_get_config_payment($order_id){

        $plugin_id=apply_filters('gnupay_lguplus_parent_plugin_id', 'woocommerce_');

        $payment_method = get_post_meta( $order_id, '_payment_method', true );

        $pay_ids = gnupay_lguplus_get_settings('pay_ids');
        $gnupay_lguplus_card_payname = $pay_ids['card'];
        $payment_options = get_option( $plugin_id . $gnupay_lguplus_card_payname . '_settings', null );

        $method_options = get_option( $plugin_id . $payment_method . '_settings', null );

        
        return wp_parse_args($method_options, $payment_options);
    }
}

if( ! function_exists('gnupay_lguplus_process_payment') ){
    function gnupay_lguplus_process_payment($order, $config){
        $res = array();
        $crr = array(
                'order_id',
                'order_key',
                'payment_method',
                'payment_method_title',
                'billing_last_name',
                'billing_first_name',
                'billing_company',
                'billing_address_1',
                'billing_address_2',
                'billing_city',
                'billing_state',
                'billing_postcode',
                'billing_country',
                'billing_email',
                'billing_phone',
                'shipping_last_name',
                'shipping_first_name',
                'shipping_company',
                'shipping_address_1',
                'shipping_address_2',
                'shipping_city',
                'shipping_state',
                'shipping_postcode',
                'shipping_country',
                'shipping_email',
                'shipping_phone',
                'timestamp',
                'signature',
                'returnurl',
                'mKey',
            );

        foreach($crr as $v){
            $res[$v] = isset($order->$v) ? $order->$v : '';
        }
        
        $res['order_id'] = $order->id;

        $goods = '';
        $goods_count = -1;
        $good_info = '';

        $comm_tax_mny = 0; // 과세금액
        $comm_vat_mny = 0; // 부가세
        $comm_free_mny = 0; // 면세금액
        $tot_tax_mny = 0;

        $send_cost  = 0;    //배송비

        $i = 0;

        foreach ( $order->get_items() as $item_key => $item ) {
            if( empty($item) ) continue;
            
            $_product        = wc_get_product( $item['product_id'] );
            $goods_count += (int) $item['qty'];
            $quantity = $item['qty'];

			$_tax_stats = $_product->get_tax_status();

            if (!$goods){
                $goods = preg_replace("/\'|\"|\||\,|\&|\;/", "", esc_attr($item['name']));
            }

            if( $quantity > 1 ){
                $goods .= ' x '.$quantity;
            }
            $line_total = $item['line_total'];

            // 에스크로 상품정보
            if(!empty($config['de_escrow_use'])) {
                if ($i>0)
                    $good_info .= chr(30);
                $good_info .= "seq=".($i+1).chr(31);
                $good_info .= "ordr_numb=#order_replace#_".sprintf("%04d", $i).chr(31);
                $good_info .= "good_name=".addslashes($goods).chr(31);
                $good_info .= "good_cntx=".$quantity.chr(31);
                $good_info .= "good_amtx=".$line_total.chr(31);
            }

            // 복합과세금액
            if( wc_tax_enabled() && $config['de_tax_flag_use'] == 'yes' ) {
				if($_tax_stats == 'none'){  //비과세이면
					$comm_free_mny += $line_total;
				} else {
					$tot_tax_mny += $line_total;
				}
            }

            $i++;
        }

        if ($goods_count) $goods = sprintf(__('%s 외 %d 건', GNUPAY_LGUPLUS), $goods, $goods_count);

        $res['de_tax_flag_use'] = 0;    //복합과세 체크 초기화

        // 복합과세처리
        if( wc_tax_enabled() && $config['de_tax_flag_use'] == 'yes' ) {     //복합과세이면
            //이게 맞는지 나중에 다시다시 확인할것
            $comm_tax_mny = round($order->get_subtotal() - $comm_free_mny);
            $comm_vat_mny = $order->get_total_tax();
            $res['de_tax_flag_use'] = 1;
        }

        $res['goods'] = $goods;
        $res['tot_price'] = $order->get_total();
        $res['goods_count'] = $goods_count + 1;
        $res['good_info'] = $good_info;
        $res['comm_tax_mny'] = $comm_tax_mny;
        $res['comm_vat_mny'] = $comm_vat_mny;
        $res['comm_free_mny'] = $comm_free_mny;


        if ($config['de_escrow_use'] == 1) {
            // 에스크로결제 테스트
            $useescrow = '1';
        }
        else {
            // 일반결제 테스트
            $useescrow = '';
        }

        $res['useescrow'] = isset($useescrow) ? $useescrow : '';

        $BANK_CODE = array(
            '03' => '기업은행',
            '04' => '국민은행',
            '05' => '외환은행',
            '07' => '수협중앙회',
            '11' => '농협중앙회',
            '20' => '우리은행',
            '23' => 'SC 제일은행',
            '31' => '대구은행',
            '32' => '부산은행',
            '34' => '광주은행',
            '37' => '전북은행',
            '39' => '경남은행',
            '53' => '한국씨티은행',
            '71' => '우체국',
            '81' => '하나은행',
            '88' => '신한은행',
            'D1' => '동양종합금융증권',
            'D2' => '현대증권',
            'D3' => '미래에셋증권',
            'D4' => '한국투자증권',
            'D5' => '우리투자증권',
            'D6' => '하이투자증권',
            'D7' => 'HMC 투자증권',
            'D8' => 'SK 증권',
            'D9' => '대신증권',
            'DA' => '하나대투증권',
            'DB' => '굿모닝신한증권',
            'DC' => '동부증권',
            'DD' => '유진투자증권',
            'DE' => '메리츠증권',
            'DF' => '신영증권'
        );

        $CARD_CODE = array(
            '01' => '외환',
            '03' => '롯데',
            '04' => '현대',
            '06' => '국민',
            '11' => 'BC',
            '12' => '삼성',
            '14' => '신한',
            '15' => '한미',
            '16' => 'NH',
            '17' => '하나 SK',
            '21' => '해외비자',
            '22' => '해외마스터',
            '23' => 'JCB',
            '24' => '해외아멕스',
            '25' => '해외다이너스'
        );

        $PAY_METHOD = array(
            'VCard'      => '신용카드',
            'Card'       => '신용카드',
            'DirectBank' => '계좌이체',
            'HPP'        => '휴대폰',
            'VBank'      => '가상계좌'
        );

        
        $p_mname = apply_filters('gp_lguplus_get_p_mname', get_bloginfo('name')); //상점이름

        if( GNUPAY_LGUPLUS_MOBILE ){    //모바일이면

        } else {
            
            if( $config['de_card_test'] ){  //테스트결제이면
            }
 
            $payment_method = get_post_meta( $order->id, '_payment_method', true );
            $pay_ids = gnupay_lguplus_get_settings('pay_ids');
            $lguplus_create = array();

            if( $payment_method == $pay_ids['vbank'] ){ //가상계좌이면

                $plugin_id= apply_filters('gnupay_lguplus_parent_plugin_id', 'woocommerce_');
                $pay_options = get_option( $plugin_id . $payment_method . '_settings' );

                if( isset($pay_options['de_deposit_period']) && ! empty($pay_options['de_deposit_period']) ){  //가상계좌 입금 기간 설정

                }
            }

            $res = apply_filters('gnupay_lguplus_create_res', wp_parse_args($lguplus_create, $res));
        }

        return $res;
    }
}

function sir_lguplus_get_order_status($status){
    $status = preg_replace("/^wc-/i", "", trim($status));

    return $status;
}

function gnupay_lguplus_get_vbankurl($pay_ids=array()){

    if( !$pay_ids ){
        $pay_ids = gnupay_lguplus_get_settings('pay_ids');
    }
    
    return str_replace( 'https:', 'http:', add_query_arg( 'wc-api', $pay_ids['vbank'], home_url( '/' ) ) );

}

if( !function_exists('gp_lguplus_order_can_view') ){
    function gp_lguplus_order_can_view($order_id){

        $is_can_view = false;

        // Check user has permission to edit
        if ( current_user_can( 'view_order', $order_id ) ) {
            $is_can_view = true;
        }

        if( $retrive_data = WC()->session->get( 'gp_lguplus_'.$order_id ) ){
            $is_can_view = true;
        }

        return $is_can_view;
    }
}

function gp_lguplus_upload_path($type='dir'){
    $upload_dir = array('error'=>'error');
    try{
        $upload_dir = wp_upload_dir();
    } catch (Exception $e) {
    }
    $path = '';
    if( empty($upload_dir['error']) ){
        $path = apply_filters('sir_get_upload_'.$type, $upload_dir['base'.$type].'/lguplus', $upload_dir);
    }
    return $path;
}

// 세션변수 생성
function gp_lguplus_set_session($session_name, $value)
{
    if (PHP_VERSION < '5.3.0')
        session_register($session_name);
    // PHP 버전별 차이를 없애기 위한 방법
    $$session_name = $_SESSION[$session_name] = $value;
}

// CHARSET 변경 : euc-kr -> utf-8
function gp_lguplus_iconv_utf8($str)
{
    return @iconv('euc-kr', 'utf-8', $str);
}

// CHARSET 변경 : utf-8 -> euc-kr
function gp_lguplus_iconv_euckr($str)
{
    return @iconv('utf-8', 'euc-kr', $str);
}

function gp_lguplus_replace_oid($oid){

    if( strpos($oid, '_') !== false ){
        $get_order_num = explode('_', $oid);
        $oid = end($get_order_num);
    } else {
        $oid = preg_replace('/\D/', '', $oid);
    }

    return $oid;
}

function gp_lguplus_loading_msg($return_url){
    ?>
        <div id="show_progress">
            <span style="display:block; text-align:center;margin-top:120px"><img src="<?php echo GNUPAY_LGUPLUS_URL; ?>/img/loading.gif" alt=""></span>
            <span style="display:block; text-align:center;margin-top:10px; font-size:14px"><?php _e('주문완료 중입니다. 잠시만 기다려 주십시오.', GNUPAY_LGUPLUS); ?></span>
        </div>
        <script type="text/javascript">
            setTimeout( function() {
                location.replace('<?php echo $return_url; ?>');
            }, 300);
        </script>
    <?php
}


if( !function_exists('gp_lguplus_request_filesystem_credentials_modal') ){
    function gp_lguplus_request_filesystem_credentials_modal(){
        if( !is_admin() ){
            return;
        }

        if( $url = GNUPAY_LGUPLUS()->credentials_url ){
            $filesystem_method = get_filesystem_method();
            ob_start();
            $filesystem_credentials_are_stored = request_filesystem_credentials($url);
            ob_end_clean();
            $request_filesystem_credentials = ( $filesystem_method != 'direct' && ! $filesystem_credentials_are_stored );
            if ( ! $request_filesystem_credentials ) {
                return;
            }
        ?>
        <div id="request-filesystem-credentials-dialog" class="notification-dialog-wrap request-filesystem-credentials-dialog gp-credentials-dialog">
            <div class="notification-dialog-background"></div>
            <div class="notification-dialog" role="dialog" aria-labelledby="request-filesystem-credentials-title" tabindex="0">
                <div class="request-filesystem-credentials-dialog-content">
                    <?php request_filesystem_credentials( $url ); ?>
                <div>
            </div>
        </div>
        <script>
        jQuery(document).ready(function($) {
            wp.updates.requestForCredentialsModalOpen();

            $(document).on("click", '.gp-credentials-dialog [data-js-action="close"]', function(e){
                e.preventDefault();
                wp.updates.requestForCredentialsModalClose();
            });
        });
        </script>
        <?php
        }   //end if
    }
}

// 모바일 PG 주문 필드 생성
if(! function_exists('gp_make_order_field') ){
    function gp_make_order_field($data, $exclude)
    {
        $field = '';

        foreach((array) $data as $key=>$value) {
            if(empty($value)) continue;

            if(in_array($key, $exclude))
                continue;

            if(is_array($value)) {
                foreach($value as $k=>$v) {
                    $field .= '<input type="hidden" name="'.$key.'['.$k.']" value="'.$v.'">'.PHP_EOL;
                }
            } else {
                $field .= '<input type="hidden" name="'.$key.'" value="'.$value.'">'.PHP_EOL;
            }
        }

        return $field;
    }
}

// rm -rf 옵션 : exec(), system() 함수를 사용할 수 없는 서버 또는 win32용 대체
// www.php.net 참고 : pal at degerstrom dot com
function gp_lguplus_rm_rf($file)
{
    if (file_exists($file)) {
        if (is_dir($file)) {
            $handle = opendir($file);
            while($filename = readdir($handle)) {
                if ($filename != '.' && $filename != '..')
                    gp_lguplus_rm_rf($file.'/'.$filename);
            }
            closedir($handle);

            @chmod($file, 0755);
            @rmdir($file);
        } else {
            @chmod($file, 0644);
            @unlink($file);
        }
    }
}

function gnupay_lguplus_get_gateways(){

    $payment_gateways = array();

    if ( WC()->payment_gateways() ) {
        $payment_gateways = WC()->payment_gateways->get_available_payment_gateways();
    }

    return $payment_gateways;
}

if( ! function_exists('lguplus_new_html_header') ){
    function lguplus_new_html_header($page_mode='', $title=''){
        if( !$title ){
            $title = get_bloginfo('name');
        }
    ?>
    <!DOCTYPE html>
    <!--[if IE 8]>
        <html xmlns="http://www.w3.org/1999/xhtml" class="ie8" <?php language_attributes(); ?>>
    <![endif]-->
    <!--[if !(IE 8) ]><!-->
        <html xmlns="http://www.w3.org/1999/xhtml" <?php language_attributes(); ?>>
    <!--<![endif]-->
    <head>
    <meta http-equiv="X-UA-Compatible" content="IE=Edge">
    <meta http-equiv="Content-Type" content="<?php bloginfo('html_type'); ?>; charset=<?php bloginfo('charset'); ?>" />
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php $title; ?></title>
    <?php do_action( 'lguplus_head_new_'.$page_mode ); ?>
    <link rel='stylesheet' id='gnupay-lguplus-new'  href='<?php echo GNUPAY_LGUPLUS_URL.'template/new.css'; ?>' type='text/css' media='all' />
    </head>
    <div class="lguplus_new_shortcode">
    <?php
    }
}

if( ! function_exists('lguplus_new_html_footer') ){
    function lguplus_new_html_footer($page_mode=''){
    ?>
    </div>
    <?php do_action( 'lguplus_footer_new_'.$page_mode ); ?>
    </body>
    </html>
    <?php
    }
}

// unescape nl 얻기
function gplgu_conv_unescape_nl($str)
{
    $search = array('\\r', '\r', '\\n', '\n');
    $replace = array('', '', "\n", "\n");

    return str_replace($search, $replace, $str);
}
?>
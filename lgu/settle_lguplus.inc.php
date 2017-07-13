<?php
if( ! defined( 'ABSPATH' ) ) return;

require_once(GNUPAY_LGUPLUS_PATH.'lgu/lgdacom/XPayClient.php');

if ( !class_exists( 'XPay' ) ) :

class XPay extends XPayClient
{
    public function set_config_value($key, $val)
    {
        $this->config[$key] = $val;
    }
}
endif;  //Class exists XPayClient end if

global $LGD_WINDOW_TYPE, $CST_PLATFORM;

/*
 * 1. 기본결제 인증요청 정보 변경
 *
 * 기본정보를 변경하여 주시기 바랍니다.(파라미터 전달시 POST를 사용하세요)
 */

$info = (isset($info) && is_array($info)) ? $info : array();

$CST_PLATFORM           = $config['de_card_test'] ? 'test' : 'service';    //LG유플러스 결제 서비스 선택(test:테스트, service:서비스)
$CST_MID                = 'si_'.$config['de_lguplus_mid'];                       //상점아이디(LG유플러스으로 부터 발급받으신 상점아이디를 입력하세요)
                                                                        //테스트 아이디는 't'를 반드시 제외하고 입력하세요.
$LGD_MID                = (('test' == $CST_PLATFORM) ? 't' : '').$CST_MID;  //상점아이디(자동생성)
$LGD_TIMESTAMP          = date('YmdHis', GNUPAY_LGUPLUS_SERVER_TIME);                                   //타임스탬프
$LGD_BUYERIP            = $_SERVER['REMOTE_ADDR'];                          //구매자IP
$LGD_BUYERID            = '';                                               //구매자ID
$LGD_CUSTOM_SKIN        = 'red';                                            //상점정의 결제창 스킨 (red, purple, yellow)
$LGD_WINDOW_VER         = '2.5';                                            //결제창 버젼정보
$LGD_MERTKEY            = '';                                               //상점MertKey(mertkey는 상점관리자 -> 계약정보 -> 상점정보관리에서 확인하실수 있습니다)
$LGD_WINDOW_TYPE        = 'iframe';                                         //결제창 호출 방식
$LGD_CUSTOM_SWITCHINGTYPE = 'IFRAME';                                       //신용카드 카드사 인증 페이지 연동 방식
$LGD_RETURNURL          = add_query_arg(array('wc-api'=>'gnupay_lguplus_returnurl', 'lgupay'=>'return'), home_url( '/' ));                  //LGD_RETURNURL 을 설정하여 주시기 바랍니다. 반드시 현재 페이지와 동일한 프로트콜 및  호스트이어야 합니다. 아래 부분을 반드시 수정하십시요.
$LGD_VERSION            = 'PHP_Non-ActiveX_Standard';                       // 버전정보 (삭제하지 마세요)
$LGD_CASNOTEURL         = gnupay_lguplus_get_vbankurl();   //가상계좌 수신 url

$lgu_infos = array(
'CST_PLATFORM'  =>  $CST_PLATFORM,    //LG유플러스 결제 서비스 선택(test:테스트, service:서비스)
'CST_MID'   =>  $CST_MID,                       //상점아이디(LG유플러스으로 부터 발급받으신 상점아이디를 입력하세요)
'LGD_MID'   =>  $LGD_MID,    //테스트 아이디는 't'를 반드시 제외하고 입력하세요. 상점아이디(자동생성)
'LGD_TIMESTAMP' =>  $LGD_TIMESTAMP, //타임스탬프
'LGD_BUYERIP'  =>  $LGD_BUYERIP, //구매자IP
'LGD_BUYERID'   => $LGD_BUYERID,  //구매자ID
'LGD_CUSTOM_SKIN'  =>  $LGD_CUSTOM_SKIN,      //상점정의 결제창 스킨 (red, purple, yellow)
'LGD_WINDOW_VER'    =>  $LGD_WINDOW_VER,      //결제창 버젼정보
'LGD_MERTKEY'   =>  $LGD_MERTKEY,             //상점MertKey(mertkey는 상점관리자 -> 계약정보 -> 상점정보관리에서 확인하실수 있습니다)
'LGD_WINDOW_TYPE'   =>  $LGD_WINDOW_TYPE,           //결제창 호출 방식
'LGD_CUSTOM_SWITCHINGTYPE'  =>  $LGD_CUSTOM_SWITCHINGTYPE,   //신용카드 카드사 인증 페이지 연동 방식
'LGD_RETURNURL'  =>  $LGD_RETURNURL,   //LGD_RETURNURL 을 설정하여 주시기 바랍니다. 반드시 현재 페이지와 동일한 프로트콜 및  호스트이어야 합니다. 아래 부분을 반드시 수정하십시요.
'LGD_VERSION'  =>  $LGD_VERSION,  //버전정보 (삭제하지 마세요)
'configPath'    => GNUPAY_LGUPLUS_PATH.'lgu/lgdacom',
'LGD_CUSTOM_USABLEPAY'  =>  '',
'LGD_CASNOTEURL'    => $LGD_CASNOTEURL,
);


if( GNUPAY_LGUPLUS_MOBILE ){    //모바일이면
    $lgu_infos['LGD_CUSTOM_SKIN']        = 'SMART_XPAY2';                                    //상점정의 결제창 스킨 (red, purple, yellow)
}

if( GNUPAY_LGUPLUS_MOBILE ){ //모바일이면
    $LGD_CUSTOM_SKIN        = 'SMART_XPAY2';                                    //상점정의 결제창 스킨 (red, purple, yellow)
}

if ( WC()->payment_gateways() ) {
    $available_gateways = WC()->payment_gateways->get_available_payment_gateways();
}

// 결제가능 수단
$useablepay = array();
$LGD_CUSTOM_USABLEPAY = '';

$pay_ids = gnupay_lguplus_get_settings('pay_ids');

if( isset($available_gateways[ $pay_ids['bank'] ]) ){  //계좌이체
    $useablepay[] = 'SC0030';
}
if( isset($available_gateways[ $pay_ids['vbank'] ]) ){  //가상계좌
    $useablepay[] = 'SC0040';
}
if( isset($available_gateways[ $pay_ids['card'] ]) ){  //신용카드
    $useablepay[] = 'SC0010';
}
if( isset($available_gateways[ $pay_ids['phone'] ]) ){  //폰
    $useablepay[] = 'SC0060';
}

if(count($useablepay) > 0){
   $lgu_infos['LGD_CUSTOM_USABLEPAY'] = $LGD_CUSTOM_USABLEPAY = implode("-", $useablepay);
}

$configPath             = GNUPAY_LGUPLUS_PATH.'lgu/lgdacom';                               //LG유플러스에서 제공한 환경파일("/conf/lgdacom.conf") 위치 지정.

$info = wp_parse_args($info, $lgu_infos);
?>
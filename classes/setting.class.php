<?php
if( ! defined( 'ABSPATH' ) ) exit;

class GNUPAY_LGUPLUS_SETTING {
    public $gateways = array();
    public $pay_ids = array();

    public static function getInstance()
    {
        static $instance = null;
        if (null === $instance) {
            $instance = new self();
        }

        return $instance;
    }

    protected function __construct() {
    }

    protected function get_gateways() {     //class 명
        $this->gateways = array(
                'lguplus_card_gateway',
                'lguplus_virtualaccount',
                'lguplus_accounttransfer',
                'lguplus_phonepay',
                'lguplus_easypay',
            );
    }

    protected function get_pay_ids() {
        $this->pay_ids = array(
                'card' => 'lguplus_creditcard',   //신용카드 id
                'vbank' => 'lguplus_virtualaccount', //가상계좌 id
                'bank' => 'lguplus_accounttransfer', //계좌이체 id
                'phone' => 'lguplus_phonepay', //휴대폰 id
                'easy' => 'lguplus_easypay', //간편결제 id
            );
    }

    public function get_options($options='gateways') {
        if( ! $this->gateways && $options == 'gateways' ){
            $this->get_gateways();
        } else if ( ! $this->pay_ids && $options == 'pay_ids' ){
            $this->get_pay_ids();
        }

        return $this->$options;
    }
}
?>
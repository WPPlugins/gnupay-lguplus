/* global wc_checkout_params */
jQuery( function( $ ) {

	// wc_checkout_params is required to continue, ensure the object exists
	if ( typeof wc_checkout_params === 'undefined' ) {
		return false;
	}
    
    var lguplus_checkout_form = {
        $checkout_form: $( 'form.checkout' ),
        $order_review: $( '#order_review' ),
        $lguplus_pay_form: $( '#gnupay_lguplus_form' ),
		submit_error: function( error_message ) {

            var $form = this.$checkout_form;

			$( '.woocommerce-error, .woocommerce-message' ).remove();
			$form.prepend( error_message );
			$form.removeClass( 'processing' ).unblock();
			$form.find( '.input-text, select' ).blur();

			$( 'html, body' ).animate({
				scrollTop: ( $form.offset().top - 100 )
			}, 1000 );
			$( document.body ).trigger( 'checkout_error' );

		},
        form_set : function(f, json){

            if(json.shipping_last_name == ''){
                json.shipping_last_name = json.billing_last_name;
            }
            if(json.shipping_first_name == ''){
                json.shipping_first_name = json.billing_first_name;
            }
            if(json.shipping_company == ''){
                json.shipping_company = json.billing_company;
            }
            if(json.shipping_address_1 == ''){
                json.shipping_address_1 = json.billing_address_1;
            }
            if(json.shipping_address_2 == ''){
                json.shipping_address_2 = json.billing_address_2;
            }
            if(json.shipping_city == ''){
                json.shipping_city = json.billing_city;
            }
            if(json.shipping_state == ''){
                json.shipping_state = json.billing_state;
            }
            if(json.shipping_postcode == ''){
                json.shipping_postcode = json.billing_postcode;
            }
            if(json.shipping_country == ''){
                json.shipping_country = json.billing_country;
            }
            if(json.shipping_email == ''){
                json.shipping_email = json.billing_email;
            }
            if(json.shipping_phone == ''){
                json.shipping_phone = json.billing_phone;
            }

            f.order_id.value = json.order_id;    //우커머스 주문번호
            f.LGD_OID.value = json.order_id;    //주문번호
            f.order_key.value = json.order_key;     //우커머스 주문키
            f.LGD_PRODUCTINFO.value = json.goods;   //상품정보
            f.LGD_AMOUNT.value = json.tot_price;    //결제금액

            if(f.comm_free_mny !== undefined) f.comm_free_mny.value = json.comm_free_mny;
            if(f.comm_tax_mny !== undefined) f.comm_tax_mny.value = json.comm_tax_mny;
            if(f.comm_vat_mny !== undefined) f.comm_vat_mny.value = json.comm_vat_mny;

            var address_1 = '';
            if( json.shipping_city ){
                address_1 = json.shipping_city + ' ';
            }

            f.LGD_BUYER.value = json.billing_last_name + json.billing_first_name;
            f.LGD_BUYEREMAIL.value = json.billing_email;
            f.LGD_BUYERPHONE.value = json.billing_phone;

            //escrow
            if ( json.useescrow !== undefined && json.useescrow ){

                if(f.LGD_ESCROW_ZIPCODE !== undefined)   f.LGD_ESCROW_ZIPCODE.value = json.shipping_postcode;
                if(f.LGD_ESCROW_ADDRESS1 !== undefined)   f.LGD_ESCROW_ADDRESS1.value = address_1 + json.shipping_address_1;
                if(f.LGD_ESCROW_ADDRESS2 !== undefined)   f.LGD_ESCROW_ADDRESS2.value = json.shipping_address_2;
                if(f.LGD_ESCROW_BUYERPHONE !== undefined)   f.LGD_ESCROW_BUYERPHONE.value = json.shipping_phone;

            }

            f.LGD_RECEIVER.value = json.shipping_last_name + json.shipping_first_name
            f.LGD_RECEIVERPHONE.value = json.shipping_phone;

            if( json.de_tax_flag_use ){
                if(f.LGD_TAXFREEAMOUNT !== undefined) f.LGD_TAXFREEAMOUNT.value = f.comm_free_mny.value;
            }

        },
        lguplus_mobile_submit : function(f, json){

            var othis = this,
                $woo_form = othis.$checkout_form,
                data_array = $woo_form.serializeArray(),
                result = {};

            $.each( data_array, function() {
                result[this.name] = this.value;
            });

            var payment_method = json.payment_method ? json.payment_method : result.payment_method;
            var pay_method = '',
                easy_pay = '';

            switch(payment_method) {
                case "lguplus_accounttransfer":    //계좌이체
                    pay_method = "SC0030";
                    break;
                case "lguplus_virtualaccount": //가상계좌
                    pay_method = "SC0040";
                    break;
                case "lguplus_phonepay":   //휴대폰
                    pay_method = "SC0060";
                    break;
                case "lguplus_creditcard":     //신용카드
                    pay_method = "SC0010";
                    break;
                case "lguplus_easypay":     //간편결제
                    easy_pay = "PAYNOW";
                    break;
                default :
                    break;
            }

            f.LGD_OID.value = f.oid.value = json.order_id;
            f.LGD_BUYER.value = json.billing_last_name + json.billing_first_name;
            f.LGD_PRODUCTINFO.value = json.goods;   //상품정보
            f.LGD_AMOUNT.value = json.tot_price;    //결제금액

            f.LGD_CUSTOM_FIRSTPAY.value = pay_method;
            f.LGD_BUYEREMAIL.value = json.billing_email;
            f.LGD_BUYERPHONE.value = json.billing_phone;
            f.LGD_EASYPAY_ONLY.value = easy_pay;

            if( json.de_tax_flag_use ){
                if(f.LGD_TAXFREEAMOUNT !== undefined) f.LGD_TAXFREEAMOUNT.value = json.comm_free_mny;
            }

            // 주문 정보 임시저장
            var order_data = jQuery(f).serialize();
            
            order_data += "&action="+encodeURIComponent('lguplus_orderdatasave');

            var save_result = "";

            jQuery.ajax({
                type: "POST",
                data: order_data,
                url: gnupay_lguplus_object.ajaxurl,
                cache: false,
                async: false,
                success: function(data) {
                    save_result = data;
                }
            });

            $woo_form.removeClass( 'processing' ).unblock();

            if(save_result) {
                alert(save_result);
                return false;
            }

            f.submit();

        },
        lguplus_pay_submit: function(f, json){

            // 금액체크
            var othis = this,
                $woo_form = othis.$checkout_form,
                data_array = $woo_form.serializeArray(),
                result = {};

            $.each( data_array, function() {
                result[this.name] = this.value;
            });

            var payment_method = json.payment_method ? json.payment_method : result.payment_method;

            // pay_method 설정
            f.LGD_EASYPAY_ONLY.value = "";

            if(typeof f.LGD_CUSTOM_USABLEPAY === "undefined") {
                var input = document.createElement("input");
                input.setAttribute("type", "hidden");
                input.setAttribute("name", "LGD_CUSTOM_USABLEPAY");
                input.setAttribute("value", "");
                f.LGD_EASYPAY_ONLY.parentNode.insertBefore(input, f.LGD_EASYPAY_ONLY);
            }

            switch(payment_method) {
                case "lguplus_accounttransfer":    //계좌이체
                    f.LGD_CUSTOM_FIRSTPAY.value = "SC0030";
                    f.LGD_CUSTOM_USABLEPAY.value = "SC0030";
                    break;
                case "lguplus_virtualaccount": //가상계좌
                    f.LGD_CUSTOM_FIRSTPAY.value = "SC0040";
                    f.LGD_CUSTOM_USABLEPAY.value = "SC0040";
                    break;
                case "lguplus_phonepay":   //휴대폰
                    f.LGD_CUSTOM_FIRSTPAY.value = "SC0060";
                    f.LGD_CUSTOM_USABLEPAY.value = "SC0060";
                    break;
                case "lguplus_creditcard":     //신용카드
                    f.LGD_CUSTOM_FIRSTPAY.value = "SC0010";
                    f.LGD_CUSTOM_USABLEPAY.value = "SC0010";
                    break;
                case "lguplus_easypay":     //간편결제
                    var elm = f.LGD_CUSTOM_USABLEPAY;
                    if(elm.parentNode)
                        elm.parentNode.removeChild(elm);
                    f.LGD_EASYPAY_ONLY.value = "PAYNOW";
                    break;
                default :
                    break;
            }

            lguplus_checkout_form.form_set(f, json);

            $woo_form.removeClass( 'processing' ).unblock();

            othis.launchCrossPlatform(f);

            return false;

        },
        launchCrossPlatform : function(form){
            
            var ajax_data = $(form).serialize()+"&action=lgu_xpay_request";

            $.ajax({
                url: gnupay_lguplus_object.ajaxurl,
                type: "POST",
                data: ajax_data,
                dataType: "json",
                async: false,
                cache: false,
                success: function(data) {

                    form.LGD_HASHDATA.value = data.LGD_HASHDATA;

                    lgdwin = openXpay(form, gnupay_lguplus_object.cst_platform, gnupay_lguplus_object.lgd_window_type, null, "", "");
                },
                error: function(data) {
                    try { console.log(data) } catch (e) { alert(data.error) };
                }
            });

        }
    }

    // Trigger a handler to let gateways manipulate the checkout if needed
    // lguplus 결제를 선택했을 경우에만 실행
    lguplus_checkout_form.$checkout_form.on("checkout_place_order_gnupay_lguplus checkout_place_order_lguplus_creditcard checkout_place_order_lguplus_virtualaccount checkout_place_order_lguplus_accounttransfer checkout_place_order_lguplus_phonepay checkout_place_order_lguplus_easypay", gnupay_lguplus_checkout_submit);

    lguplus_checkout_form.$order_review.on('submit', function(e){

        var payment_method = lguplus_checkout_form.$order_review.find( 'input[name="payment_method"]:checked' ).val();
        var methods = ['lguplus_creditcard', 'lguplus_virtualaccount', 'lguplus_accounttransfer', 'lguplus_phonepay', 'lguplus_easypay'];

        if ( $.inArray(payment_method, methods) !== -1 ){
            e.preventDefault();

            var $form = $( this ),
            formdata = $form.serialize();

            formdata = formdata+'&action=lguplus_pay_for_order&order_id='+gnupay_lguplus_object.order_id;

            $.ajax({
                type:		'POST',
                url:		gnupay_lguplus_object.ajaxurl,
                data:		formdata,
                dataType:   'json',
                success:	function( result ) {

                    if( result.result === 'success' ){
                        if( gnupay_lguplus_object.is_mobile ){   //모바일결제

                            lguplus_checkout_form.lguplus_mobile_submit( lguplus_checkout_form.$lguplus_pay_form[0], result );

                        } else {    //pc 결제

                            lguplus_checkout_form.lguplus_pay_submit( lguplus_checkout_form.$lguplus_pay_form[0], result );

                        }
                    } else {
                        if( result.error_msg !== undefined ){
                            alert( result.error_msg );
                        } else {
                            alert( 'error' );
                        }
                    }
                },
                error:	function( jqXHR, textStatus, errorThrown ) {
                    alert( errorThrown );
                }
            });

            return false;
        }

    });

    function gnupay_lguplus_checkout_submit(){
        var $form = $( this );

        if ( $form.is( '.processing' ) ) {
            return false;
        }

        $form.addClass( 'processing' );

        var form_data = $form.data();

        if ( 1 !== form_data['blockUI.isBlocked'] ) {
            $form.block({
                message: null,
                overlayCSS: {
                    background: '#fff',
                    opacity: 0.6
                }
            });
        }

        // ajaxSetup is global, but we use it to ensure JSON is valid once returned.
        $.ajaxSetup( {
            dataFilter: function( raw_response, dataType ) {
                // We only want to work with JSON
                if ( 'json' !== dataType ) {
                    return raw_response;
                }

                try {
                    // check for valid JSON
                    var data = $.parseJSON( raw_response );

                    if ( data && 'object' === typeof data ) {

                        // Valid - return it so it can be parsed by Ajax handler
                        return raw_response;
                    }

                } catch ( e ) {

                    // attempt to fix the malformed JSON
                    var valid_json = raw_response.match( /{"result.*"}/ );

                    if ( null === valid_json ) {
                        //console.log( 'Unable to fix malformed JSON' );
                    } else {
                        //console.log( 'Fixed malformed JSON. Original:' );
                        //console.log( raw_response );
                        raw_response = valid_json[0];
                    }
                }

                return raw_response;
            }
        } );

        $.ajax({
            type:		'POST',
            url:		wc_checkout_params.checkout_url,
            data:		$form.serialize(),
            dataType:   'json',
            success:	function( result ) {

                try {
                    if ( result.result == 'success' ) {

                        if( gnupay_lguplus_object.is_mobile ){   //모바일결제

                            lguplus_checkout_form.lguplus_mobile_submit( lguplus_checkout_form.$lguplus_pay_form[0], result );

                        } else {    //pc 결제
                            
                            lguplus_checkout_form.lguplus_pay_submit( lguplus_checkout_form.$lguplus_pay_form[0], result );

                        }

                    } else if ( result.result === 'failure' ) {
                        throw 'Result failure';
                    } else {
                        throw 'Invalid response';
                    }
                } catch( err ) {
                    // Reload page
                    if ( result.reload === 'true' ) {
                        window.location.reload();
                        return;
                    }

                    // Trigger update in case we need a fresh nonce
                    if ( result.refresh === 'true' ) {
                        $( document.body ).trigger( 'update_checkout' );
                    }

                    if ( result.messages ) {
                        lguplus_checkout_form.submit_error( result.messages );
                    }

                }
            },
            error:	function( jqXHR, textStatus, errorThrown ) {
                lguplus_checkout_form.submit_error( '<div class="woocommerce-error">' + errorThrown + '</div>' );
            }
        });

        return false;
    }
});

/*
 * 인증결과 처리
 */
function lg_payment_return() {
    var fDoc;

    fDoc = lgdwin.contentWindow || lgdwin.contentDocument;

    var order_action_url = gnupay_lguplus_object.order_action_url,
        payform = 'gnupay_lguplus_form';

    if (fDoc.document.getElementById('LGD_RESPCODE').value == "0000") {
        document.getElementById("LGD_PAYKEY").value = fDoc.document.getElementById('LGD_PAYKEY').value;
        document.getElementById(payform).target = "_self";
        document.getElementById(payform).action = order_action_url;
        document.getElementById(payform).submit();
    } else {
        document.getElementById(payform).target = "_self";
        document.getElementById(payform).action = order_action_url;
        alert("LGD_RESPCODE (결과코드) : " + fDoc.document.getElementById('LGD_RESPCODE').value + "\n" + "LGD_RESPMSG (결과메시지): " + fDoc.document.getElementById('LGD_RESPMSG').value);
        closeIframe();
    }
}
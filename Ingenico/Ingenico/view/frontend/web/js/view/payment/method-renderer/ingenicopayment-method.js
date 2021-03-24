define(
    [
        'Magento_Checkout/js/view/payment/default',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/model/payment/additional-validators',
        'ingenico',
        'jquery',
        'mage/url',
        'Magento_Checkout/js/model/full-screen-loader',
        'Magento_Ui/js/model/messageList'
    ],
    function (Component, quote, additionalValidators, ingenico, $, url, fullScreenLoader, messageContainer) {
        'use strict';
        return Component.extend({
            defaults: {
                template: 'Ingenico_Ingenico/payment/ingenicopayment',
                redirectAfterPlaceOrder: false
            },
            getMailingAddress: function () {
                return window.checkoutConfig.payment.checkmo.mailingAddress;
            },
            preparePayment: function () {
                fullScreenLoader.startLoader();
                if (!additionalValidators.validate()) {   //For email validation
                    return false;
                }

                let orderCreated = this.placeOrder();
                let intervalId = setInterval(function () {
                    if (!orderCreated) {
                        return false;
                    }
                    clearInterval(intervalId);
                    var isTokenGenerated = this.generateToken();

                    let intervalTokenId = setInterval(function () {
                        if (!isTokenGenerated) {
                            return false;
                        }
                        clearInterval(intervalTokenId);
                        var configJson = {
                            'tarCall': false,
                            'features': {
                                'showLoader': true,
                                'showPGResponseMsg': true,
                                'enableNewWindowFlow': this.getenableNewWindowFlow(), //for hybrid applications please disable this by passing false
                                'enableExpressPay': this.getenableExpressPay(),
                                'enableAbortResponse': false,
                                'enableMerTxnDetails': true,
                                'hideSavedInstruments': this.gethideSavedInstruments(),
                                'enableInstrumentDeRegistration': this.getenableInstrumentDeRegistration(),
                                'separateCardMode': this.getseparateCardMode()
                            },
                            'consumerData': {
                                'deviceId': 'WEBSH2',   //possible values 'WEBSH1', 'WEBSH2' and 'WEBMD5'
                                'token': this.getGeneratedToken(),
                                'authKey': '',
                                'responseStr': '',
                                'payOptionsHandler': '',
                                'responseHandler': this.handleResponse.bind(this),
                                'redirectOnClose': false,
                                'paymentMode': this.getPaymentMode(),
                                'merchantLogoUrl': this.getMerchantLogoUrl(),  //provided merchant logo will be displayed
                                'merchantId': this.getMerchantID(),
                                'currency': this.getCurrency(),
                                'merchantMsg': this.getMerchantMsg(),
                                'saveInstrument': this.getSaveInstrument(),
                                'txnType': this.getTxnType(),
                                'txnSubType': 'DEBIT',
                                'paymentModeOrder': this.getPaymentModeOrderArray(),
                                'disclaimerMsg': this.getDisclaimerMsg(),
                                'consumerId': this.getCustomerID(),
                                'consumerMobileNo': this.getCustomerMobileNo(),
                                'consumerEmailId': this.getCustomerEmail(),
                                'txnId': this.getmerchantTxnRefNumber(),   //Unique merchant transaction ID
                                'items': [{
                                    'itemId': this.getSchemeCode(),
                                    'amount': this.getAmount(),
                                    'comAmt': '0'
                                }],
                                'merRefDetails': [
                                    { "name": "Txn. Ref. ID", "value": this.getmerchantTxnRefNumber() }
                                ],
                                'cartDescription': '}{custname:' + this.getCustomerName() + '}{orderid:' + this.getOrderId(),
                                'customStyle': {
                                    'PRIMARY_COLOR_CODE': this.getPrimaryColourCode(),   //merchant primary color code
                                    'SECONDARY_COLOR_CODE': this.getSecondaryColourCode(),   //provide merchant's suitable color code
                                    'BUTTON_COLOR_CODE_1': this.getButtonColourCode1(),   //merchant's button background color code
                                    'BUTTON_COLOR_CODE_2': this.getButtonColourCode2()   //provide merchant's suitable color code for button text
                                }
                            }
                        };

                        configJson = this.updateConfigIfEmbedEnabled(configJson);
                        configJson = this.updateConfigIfReturnURLEnabled(configJson);
                        fullScreenLoader.stopLoader();
                        $.pnCheckout(configJson);

                        if (configJson.features.enableNewWindowFlow) {
                            pnCheckoutShared.openNewWindow();
                        }

                        $(".checkout-detail-box-inner .popup-close,.confirmBox .errBtnCancel").live("click", function () {
                            $.ajax({
                                type: 'POST',
                                url: url.build('ingenico/redirectonclose'),
                                async: false,
                                data: {},
                                success: function (response) {
                                    fullScreenLoader.stopLoader();
                                    return true;
                                },

                                error: function (response) {
                                    fullScreenLoader.stopLoader();
                                    return false;
                                }
                            });
                        });
                    }.bind(this), 500);

                }.bind(this), 500);

            },
            getMerchantID: function () {
                return window.checkoutConfig.payment.ingenico.merchantId;
            },
            getSchemeCode: function () {
                return window.checkoutConfig.payment.ingenico.schemeCode;
            },
            getGeneratedToken: function () {
                return window.checkoutConfig.payment.ingenico.token;
            },
            getCustomerID: function () {
                return window.checkoutConfig.payment.ingenico.customerId;
            },
            getCustomerEmail: function () {
                return window.checkoutConfig.payment.ingenico.customerEmail;
            },
            getCustomerMobileNo: function () {
                return window.checkoutConfig.payment.ingenico.consumerMobileNo
            },
            getAmount: function () {
                return window.checkoutConfig.payment.ingenico.amount;
            },
            getCurrency: function () {
                return window.checkoutConfig.payment.ingenico.currency;
            },
            getmerchantTxnRefNumber: function () {
                return window.checkoutConfig.payment.ingenico.txnId;
            },
            getCustomerName: function () {
                return window.checkoutConfig.payment.ingenico.customerName;
            },
            getOrderId: function () {
                return window.checkoutConfig.payment.ingenico.orderId;
            },
            getPrimaryColourCode: function () {
                return window.checkoutConfig.payment.ingenico.primaryColourCode;
            },
            getSecondaryColourCode: function () {
                return window.checkoutConfig.payment.ingenico.secondaryColourCode;
            },
            getButtonColourCode1: function () {
                return window.checkoutConfig.payment.ingenico.buttonColourCode1;
            },
            getButtonColourCode2: function () {
                return window.checkoutConfig.payment.ingenico.buttonColourCode2;
            },
            getDisclaimerMsg: function () {
                return window.checkoutConfig.payment.ingenico.disclaimerMsg;
            },
            getMerchantMsg: function () {
                return window.checkoutConfig.payment.ingenico.merchantMsg;
            },
            getMerchantLogoUrl: function () {
                return window.checkoutConfig.payment.ingenico.merchantLogoUrl;
            },
            getshowLoader: function () {
                return window.checkoutConfig.payment.ingenico.showLoader;
            },
            getenableExpressPay: function () {
                return window.checkoutConfig.payment.ingenico.enableExpressPay;
            },
            getenableNewWindowFlow: function () {
                return window.checkoutConfig.payment.ingenico.enableNewWindowFlow;
            },
            getseparateCardMode: function () {
                return window.checkoutConfig.payment.ingenico.separateCardMode;
            },
            getPaymentModeOrderArray: function () {
                return JSON.parse(window.checkoutConfig.payment.ingenico.paymentOrderArray);
            },
            getPaymentMode: function () {
                return window.checkoutConfig.payment.ingenico.paymentmodes;
            },
            getTxnType: function () {
                return window.checkoutConfig.payment.ingenico.txnType;
            },
            getenableInstrumentDeRegistration: function () {
                return window.checkoutConfig.payment.ingenico.enableInstrumentDeRegistration;
            },
            gethideSavedInstruments: function () {
                return window.checkoutConfig.payment.ingenico.hideSavedInstruments;
            },
            getSaveInstrument: function () {
                return window.checkoutConfig.payment.ingenico.saveInstrument;
            },
            handleResponse: function (res) {
                if (typeof res != 'undefined' && typeof res.paymentMethod != 'undefined' && typeof res.paymentMethod.paymentTransaction != 'undefined' && typeof res.paymentMethod.paymentTransaction.statusCode != 'undefined' && res.paymentMethod.paymentTransaction.statusCode == '0300') {
                    let stringResponse = res.stringResponse;
                    $("#response-string").val(stringResponse);
                    $("#response-form").submit();
                } else if (typeof res != 'undefined' && typeof res.paymentMethod != 'undefined' && typeof res.paymentMethod.paymentTransaction != 'undefined' && typeof res.paymentMethod.paymentTransaction.statusCode != 'undefined' && res.paymentMethod.paymentTransaction.statusCode == '0398') {
                    // initiated block
                } else {
                    // error block
                }

            },
            generateToken: function () {
                let email = quote.guestEmail;
                let mobileNo = quote.billingAddress().telephone;
                var result = false;

                fullScreenLoader.startLoader();
                $.ajax({
                    type: 'POST',
                    url: url.build('ingenico/token'),
                    async: false,
                    data: {
                        email: email,
                        mobileNo: mobileNo
                    },
                    success: function (response) {
                        result = true;
                        fullScreenLoader.stopLoader();
                        window.checkoutConfig.payment.ingenico = response;
                    },

                    error: function (response) {
                        fullScreenLoader.stopLoader();
                    }
                });
                return result;
            },
            updateConfigIfEmbedEnabled: function (configJson) {
                if (window.checkoutConfig.payment.ingenico.embedPaymentGatewayOnPage) {
                    configJson.consumerData.checkoutElement = "#ingenico-payment-gateway-embed"
                }

                return configJson;
            },
            updateConfigIfReturnURLEnabled: function (configJson) {
                if (!this.getenableNewWindowFlow()) {
                    configJson.consumerData.returnUrl = url.build("ingenico/response");
                } else {
                    if (!window.checkoutConfig.payment.ingenico.displayErrorMessageOnPopup) {
                        configJson.consumerData.returnUrl = url.build("ingenico/response");
                    }
                }
                return configJson;
            },
            getReturnUrl: function () {
                return url.build("ingenico/response");
            }
        });
    }
);

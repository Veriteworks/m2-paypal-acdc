/*browser:true*/
/*global define*/
define(
    [
        'jquery',
        'Magento_Payment/js/view/payment/cc-form',
        'Magento_Checkout/js/action/place-order',
        'Magento_Checkout/js/model/full-screen-loader',
        'Magento_Checkout/js/model/payment/additional-validators',
        'Magento_Payment/js/model/credit-card-validation/validator',
        'mage/url',
        'mage/translate',
        'Magento_Ui/js/modal/alert',
        'ko'
    ],
    function ($, Component, placeOrderAction, fullScreenLoader, additionalValidators, ccValidator, url, $t, alert, ko) {
        'use strict';
        return Component.extend({
            defaults: {
                template: 'Veriteworks_Paypal/payment/paypal'
            },
            isVisible: ko.observable(false),
            use3DSMessage: ko.observable(false),
            paypalForm: function (a,b,c) {
                let paymentAction = this.getPaymentAction();
                let use3DS = this.getUse3DS();
                if (paypal.HostedFields.isEligible() === true) {
                    var self = this;
                    this.isVisible(true);
                    if (use3DS) {
                        this.use3DSMessage(true);
                    }
                    paypal.HostedFields.render({
                        createOrder: function (data, actions) {
                            self.isPlaceOrderActionAllowed(false);
                            fullScreenLoader.startLoader();
                            let defer = $.Deferred();
                            self.getPlaceOrderDeferredObject()
                                .fail(
                                    function () {
                                        self.isPlaceOrderActionAllowed(true);
                                    }
                                ).done(function (res) {
                                fullScreenLoader.startLoader();
                                $.ajax({
                                    type: 'POST',
                                    url: url.build('rest/V1/veriteworks-paypal/get-trans-id'),
                                    data: JSON.stringify({"param": {"orderId": res}}),
                                    dataType: "text",
                                    contentType: "application/json",
                                    success: function (json) {
                                        let data = eval(json);
                                        if (!data[0].err) {
                                            defer.resolve(data);
                                        } else {
                                            self.processError(data);
                                        }
                                        fullScreenLoader.stopLoader();
                                    },
                                    error: function (err) {
                                        self.processError(err);
                                        fullScreenLoader.stopLoader();
                                    }
                                });
                            });
                            let a = defer.promise(this);
                            return defer.promise(this);
                        },
                        styles: {
                            'input': {
                                'font-size': '14px',
                                'font-family': 'Product Sans',
                                'color': '#3a3a3a'
                            },
                            ':focus': {
                                'color': 'black'
                            },
                            '.invalid': {
                                'color': '#FF0000'
                            }
                        },
                        fields: {
                            number: {
                                selector: '#card-number',
                                placeholder: 'Credit Card Number',
                            },
                            cvv: {
                                selector: '#cvv',
                                placeholder: 'Security Code',
                            },
                            expirationDate: {
                                selector: '#expiration-date',
                                placeholder: 'MM/YYYY',
                            }
                        }
                    }).then(function (hf) {
                        document.querySelector('#' + self.getCode() + '-form').addEventListener('submit', event => {
                            event.preventDefault();
                            fullScreenLoader.stopLoader();
                            if (use3DS) {
                                hf.submit({
                                    contingencies: ['SCA_ALWAYS']
                                }).then(function (payload) {
                                    self.paymentApi(paymentAction, payload, use3DS);
                                }).catch(function (err) {
                                    self.processError(err);
                                });
                            } else {
                                hf.submit().then(function (payload) {
                                    self.paymentApi(paymentAction, payload, use3DS);                                });
                            }
                        });
                    });
                }
                else {
                    /*
                    * Handle experience when
                    * Custom Card Fields is not eligible
                    */
                }
            },
            paymentApi: function (paymentAction, payload, use3DS) {
                let self = this;
                fullScreenLoader.startLoader();
                $.ajax({
                    type: 'POST',
                    url: url.build('rest/V1/veriteworks-paypal/' + paymentAction),
                    data: JSON.stringify({"param": {'payload': payload}}),
                    dataType : "text",
                    contentType : "application/json",
                    success: function (json) {
                        fullScreenLoader.stopLoader();
                        let data = eval(json);
                        if (data[0].err_intent !== undefined) {
                            alert({content: 'Error happened. Please try again later.'});
                            self.isPlaceOrderActionAllowed(true);
                        } else {
                            if (use3DS) {
                                window.location.replace(url.build('paypal/paypal/sendsecure/'));
                            } else {
                                window.location.replace(url.build('paypal/paypal/send/'));
                            }
                        }
                    },
                    error: function (err) {
                        fullScreenLoader.stopLoader();
                        self.processError(err);
                    },
                    always: function () {
                        fullScreenLoader.stopLoader();
                    }
                });
            },

            processError: function (err) {
                fullScreenLoader.startLoader();
                let self = this;
                $.ajax({
                    type: 'POST',
                    url: url.build('rest/V1/veriteworks-paypal/process-error'),
                    data: JSON.stringify({"param": {"error" : err}}),
                    dataType : "text",
                    contentType : "application/json",
                    success: function (json) {
                        fullScreenLoader.stopLoader();
                        alert({content: 'Error happened. Please try again later.'});
                        self.isPlaceOrderActionAllowed(true);
                    },
                    error: function (err) {
                        fullScreenLoader.stopLoader();
                        alert({content: err});
                    }
                });
            },

            initObservable: function () {
                this._super().observe([
                    'creditCardExpYear',
                    'creditCardExpMonth',
                    'creditCardNumber',
                    'creditCardVerificationNumber',
                    'creditCardToken'
                ]);
                return this;
            },

            getCode: function () {
                return 'veriteworks_paypal';
            },

            /**
             * Get data
             * @returns {Object}
             */
            getData: function () {
                var additional;

                additional = {
                    'cc_cid': this.creditCardVerificationNumber(),
                    'cc_exp_year': this.creditCardExpYear(),
                    'cc_exp_month': this.creditCardExpMonth(),
                    'cc_number': this.creditCardNumber(),
                    'token': this.getAccessToken()
                };

                return {
                    'method': this.item.method,
                    'additional_data': additional
                };
            },

            getClientId: function () {
                return window.checkoutConfig.payment.veriteworks_paypal.client_id;
            },
            getPassword: function () {
                return window.checkoutConfig.payment.veriteworks_paypal.password;
            },
            getAccessToken: function () {
                return window.checkoutConfig.payment.veriteworks_paypal.access_token;
            },

            getPaymentAction: function () {
                let action = window.checkoutConfig.payment.veriteworks_paypal.payment_action;
                if (action === 'authorize') {
                    return 'authorize';
                } else {
                    return 'capture';
                }

            },

            getUse3DS: function () {
                return window.checkoutConfig.payment.veriteworks_paypal.use_3dsecure === '1';
            },

            isActive: function() {
                return true;
            },
            getCcAvailableTypes: function() {
                return window.checkoutConfig.payment.veriteworks_paypal.availableTypes['veriteworks_paypal'];
            },

            getCcMonths: function() {
                return window.checkoutConfig.payment.veriteworks_paypal.months['veriteworks_paypal'];
            },

            getCcYears: function() {
                return window.checkoutConfig.payment.veriteworks_paypal.years['veriteworks_paypal'];
            },

            hasVerification: function() {
                return window.checkoutConfig.payment.veriteworks_paypal.hasVerification['veriteworks_paypal'];
            },

            getCcAvailableTypesValues: function() {
                return _.map(this.getCcAvailableTypes(), function(value, key) {
                    return {
                        'value': key,
                        'type': value
                    }
                });
            },
            getCcMonthsValues: function() {
                return _.map(this.getCcMonths(), function(value, key) {
                    return {
                        'value': key,
                        'month': value
                    }
                });
            },
            getCcYearsValues: function() {
                return _.map(this.getCcYears(), function(value, key) {
                    return {
                        'value': key,
                        'year': value
                    }
                });
            }

        });

    }
);

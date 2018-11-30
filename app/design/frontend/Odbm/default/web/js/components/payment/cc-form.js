/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/* @api */
define([
    'underscore',
    'Magento_Checkout/js/view/payment/default',
    'Magento_Checkout/js/view/billing-address',
    'Magento_Payment/js/model/credit-card-validation/credit-card-data',
    'Magento_Payment/js/model/credit-card-validation/credit-card-number-validator',
    'mage/translate'
], function (_, Component, billingAddressComponent, creditCardData, cardNumberValidator, $t) {
    'use strict';

    return Component.extend({
        defaults: {
            creditCardType: '',
            creditCardExpYear: '',
            creditCardExpMonth: '',
            creditCardNumber: '',
            creditCardSsStartMonth: '',
            creditCardSsStartYear: '',
            creditCardSsIssue: '',
            creditCardVerificationNumber: '',
            creditCardToken: 'test123',
            selectedCardType: null
        },

        /** @inheritdoc */
        initObservable: function () {
            this._super()
                .observe([
                    'creditCardType',
                    'creditCardExpYear',
                    'creditCardExpMonth',
                    'creditCardNumber',
                    'creditCardToken',
                    'creditCardVerificationNumber',
                    'creditCardSsStartMonth',
                    'creditCardSsStartYear',
                    'creditCardSsIssue',
                    'selectedCardType'
                ]);

            return this;
        },

        /**
         * Init component
         */
        initialize: function () {
            var self = this;

            this._super();

            //Set credit card number to credit card data object
            this.creditCardNumber.subscribe(function (value) {
                var result;
                console.log("result: " + result);

                self.selectedCardType(null);

                if (value === '' || value === null) {
                    return false;
                }
                result = cardNumberValidator(value);

                if (!result.isPotentiallyValid && !result.isValid) {
                    return false;
                }

                if (result.card !== null) {
                    self.selectedCardType(result.card.type);
                    creditCardData.creditCard = result.card;
                }

                if (result.isValid) {
                    creditCardData.creditCardNumber = value;
                    self.creditCardType(result.card.type);
                }
            });

            // Set expiration year to credit card data object
            this.creditCardExpYear.subscribe(function (value) {
                creditCardData.expirationYear = value;
                console.log("ccY: " + creditCardData.expirationYear);
            });

            // Set expiration month to credit card data object
            this.creditCardExpMonth.subscribe(function (value) {
                creditCardData.expirationMonth = value;
                console.log("ccM: " + creditCardData.expirationMonth);
            });

            // Add credit card token to credit card data
            this.creditCardToken.subscribe(function (value) {
                creditCardData.creditCardToken = value;
                console.log("Token: " + creditCardData.creditCardToken);
            });

            //Set cvv code to credit card data object
            this.creditCardVerificationNumber.subscribe(function (value) {
                creditCardData.cvvCode = value;
                console.log("cvv: " + creditCardData.cvvCode);
            });
        },

        //load the injected paperless fields afterRender
        afterFormRenders: function(){
            function onStateChanged(state) {
                for (let key in state) {
                  var field = state[key];
        
                  var requiredMsg = document.getElementById(`${key}_required`);
                  var invalidMsg = document.getElementById(`${key}_invalid`);
        
                  invalidMsg.style.display = "none";
                  requiredMsg.style.display = "none";
        
                  if (field.touched && !field.valid) {
                    var msgToShow = field.empty ? requiredMsg : invalidMsg;
                    msgToShow.style.display = "block";
                  }
                }
              }
        
              function onCardInfo(info) {
                document.getElementById("brand").innerText = info.brand || "";
                document.getElementById("lastFour").innerText = info.lastFour || "";
                document.getElementById("expiration").innerText = info.expiration || "";
              }
        
              function onCardToken(token) {
                document.getElementById("paperless_token").value = token || "";
              }
        
                const options = {
                  containerId: "card-form",
                  stylesId: "card-styles",
                  labels: {
                    cardNumber: "Card #"
                  },
                  acceptedBrands: ["amex", "visa", "mastercard", "discover"]
                };
        
                var form = new ptc.PaymentForm();
        
                form.load(options);
        
                form.onStateChanged(onStateChanged);
                form.onCardInfo(onCardInfo);
                form.onCardToken(onCardToken);
            },

        onStateChanged: function(state) {
            for (let key in state) {
                var field = state[key];

                var requiredMsg = document.getElementById(`${key}_required`);
                var invalidMsg = document.getElementById(`${key}_invalid`);

                invalidMsg.style.display = "none";
                requiredMsg.style.display = "none";

                if (field.touched && !field.valid) {
                var msgToShow = field.empty ? requiredMsg : invalidMsg;
                msgToShow.style.display = "block";
                }
            }
        },

        onCardInfo: function(info) {
            document.getElementById("brand").innerText = info.brand || "";
            document.getElementById("lastFour").innerText = info.lastFour || "";
            document.getElementById("expiration").innerText = info.expiration || "";
        },

        onCardToken: function(token) {
            document.getElementById("paperless_token").value = token || "";
        },

        /**
         * Get code
         * @returns {String}
         */
        getCode: function () {
            return 'cc';
        },

        /**
         * Get data
         * @returns {Object}
         */
        getData: function () {
            return {
                'method': this.item.method,
                'additional_data': {
                    'cc_cid': this.creditCardVerificationNumber(),
                    'cc_ss_start_month': this.creditCardSsStartMonth(),
                    'cc_ss_start_year': this.creditCardSsStartYear(),
                    'cc_ss_issue': this.creditCardSsIssue(),
                    'cc_type': this.creditCardType(),
                    'cc_exp_year': this.creditCardExpYear(),
                    'cc_exp_month': this.creditCardExpMonth(),
                    'cc_number': this.creditCardNumber(),
                    'cc_token': this.creditCardToken()
                }
            };
        },

        /**
         * Get list of available credit card types
         * @returns {Object}
         */
        getCcAvailableTypes: function () {
            return window.checkoutConfig.payment.ccform.availableTypes[this.getCode()];
        },

        /**
         * Get payment icons
         * @param {String} type
         * @returns {Boolean}
         */
        getIcons: function (type) {
            return window.checkoutConfig.payment.ccform.icons.hasOwnProperty(type) ?
                window.checkoutConfig.payment.ccform.icons[type]
                : false;
        },

        /**
         * Get list of months
         * @returns {Object}
         */
        getCcMonths: function () {
            return window.checkoutConfig.payment.ccform.months[this.getCode()];
        },

        /**
         * Get list of years
         * @returns {Object}
         */
        getCcYears: function () {
            return window.checkoutConfig.payment.ccform.years[this.getCode()];
        },

        /**
         * Check if current payment has verification
         * @returns {Boolean}
         */
        hasVerification: function () {
            return window.checkoutConfig.payment.ccform.hasVerification[this.getCode()];
        },

        /**
         * @deprecated
         * @returns {Boolean}
         */
        hasSsCardType: function () {
            return window.checkoutConfig.payment.ccform.hasSsCardType[this.getCode()];
        },

        /**
         * Get image url for CVV
         * @returns {String}
         */
        getCvvImageUrl: function () {
            return window.checkoutConfig.payment.ccform.cvvImageUrl[this.getCode()];
        },

        /**
         * Get image for CVV
         * @returns {String}
         */
        getCvvImageHtml: function () {
            return '<a href="https://www.cvvnumber.com/cvv.html" target="_blank" style="font-size:11px">What is my CVV code?</a>';
        },

        /**
         * @deprecated
         * @returns {Object}
         */
        getSsStartYears: function () {
            return window.checkoutConfig.payment.ccform.ssStartYears[this.getCode()];
        },

        /**
         * Get list of available credit card types values
         * @returns {Object}
         */
        getCcAvailableTypesValues: function () {
            return _.map(this.getCcAvailableTypes(), function (value, key) {
                return {
                    'value': key,
                    'type': value
                };
            });
        },

        /**
         * Get list of available month values
         * @returns {Object}
         */
        getCcMonthsValues: function () {
            return _.map(this.getCcMonths(), function (value, key) {
                return {
                    'value': key,
                    'month': value
                };
            });
        },

        /**
         * Get list of available year values
         * @returns {Object}
         */
        getCcYearsValues: function () {
            return _.map(this.getCcYears(), function (value, key) {
                return {
                    'value': key,
                    'year': value
                };
            });
        },

        /**
         * @deprecated
         * @returns {Object}
         */
        getSsStartYearsValues: function () {
            return _.map(this.getSsStartYears(), function (value, key) {
                return {
                    'value': key,
                    'year': value
                };
            });
        },

        /**
         * Is legend available to display
         * @returns {Boolean}
         */
        isShowLegend: function () {
            return false;
        },

        /**
         * Get available credit card type by code
         * @param {String} code
         * @returns {String}
         */
        getCcTypeTitleByCode: function (code) {
            var title = '',
                keyValue = 'value',
                keyType = 'type';

            _.each(this.getCcAvailableTypesValues(), function (value) {
                if (value[keyValue] === code) {
                    title = value[keyType];
                }
            });

            return title;
        },

        /**
         * Prepare credit card number to output
         * @param {String} number
         * @returns {String}
         */
        formatDisplayCcNumber: function (number) {
            return 'xxxx-' + number.substr(-4);
        },

        /**
         * Get credit card details
         * @returns {Array}
         */
        getInfo: function () {
            return [
                {
                    'name': 'Credit Card Type', value: this.getCcTypeTitleByCode(this.creditCardType())
                },
                {
                    'name': 'Credit Card Number', value: this.formatDisplayCcNumber(this.creditCardNumber())
                }
            ];
        }
    });
});

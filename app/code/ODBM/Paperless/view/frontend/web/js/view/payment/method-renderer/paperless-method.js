/**
 * ODBM_Paperless Magento JS component
 *
 */
/*browser:true*/
/*global define*/
define(
    [
        'Magento_Payment/js/view/payment/cc-form',
        'jquery',
        'Magento_Payment/js/model/credit-card-validation/validator'
    ],
    function (Component, $) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'ODBM_Paperless/payment/form'
            },

            isChecked: function() {
                return this.getCode();
            },

            getCode: function() {
                return 'odbm_paperless';
            },

            getDisposableTerminalKey: function() {
                return window.checkoutConfig.payment.odbm_paperless.disposableTerminalKey;
            },

            getCcAvailableTypes: function() {
                return window.checkoutConfig.payment.odbm_paperless.availableTypes;
            },

            getCcMonths: function() {
                return window.checkoutConfig.payment.odbm_paperless.months;
            },

            getCcYears: function() {
                return window.checkoutConfig.payment.odbm_paperless.years;
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
            },

            hasVerification: function() {
                return window.checkoutConfig.payment.odbm_paperless.hasVerification;
            },

            isActive: function() {
                return true;
            },

            validate: function() {
                var $form = $('#' + this.getCode() + '-form');
                return $form.validation() && $form.validation('isValid');
            },

        });
    }
);
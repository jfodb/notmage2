/**
 * ODBM_PaperlessCC Magento JS component
 *
 * @category    ODBM
 * @package     ODBM_PaperlessCC
 * @author      Our Daily Bread Ministries
 * @copyright   Our Daily Bread Ministries (https://ourdailybread.org)
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
                template: 'ODBM_PaperlessCC/payment/paperless-form'
            },


            getCode: function() {
                return 'odbm_paperlesscc';
            },

            getCcAvailableTypes: function() {
                return window.checkoutConfig.payment.odbm_paperlesscc.availableTypes;
            },

            getCcMonths: function() {
                return window.checkoutConfig.payment.odbm_paperlesscc.months;
            },

            getCcYears: function() {
                return window.checkoutConfig.payment.odbm_paperlesscc.years;
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

            isActive: function() {
                return true;
            },

            validate: function() {
                var $form = $('#' + this.getCode() + '-form');
                return $form.validation() && $form.validation('isValid');
            },

            getTransactionResults: function() {
                return _.map(window.checkoutConfig.payment.paperlesscc.transactionResults, function(value, key) {
                    return {
                        'value': key,
                        'transaction_result': value
                    }
                });
            }
        });
    }
);
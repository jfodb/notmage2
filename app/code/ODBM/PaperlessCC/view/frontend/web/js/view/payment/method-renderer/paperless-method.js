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

            getCcMonthsValues: function() {
                return [{month: '01', value: '01'}, {month: '02', value: '02'}, {month: '08', value: '08'}];
            },

            getCcYearsValues: function() {
                return [{ year: '2018', value: '2018' }]
            },

            isActive: function() {
                return true;
            },

            validate: function() {
                var $form = $('#' + this.getCode() + '-form');
                return $form.validation() && $form.validation('isValid');
            }
        });
    }
);
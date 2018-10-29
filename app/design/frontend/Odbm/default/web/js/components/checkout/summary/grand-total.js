/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'Magento_Checkout/js/view/summary/abstract-total',
    'Magento_Checkout/js/model/quote'
], function (Component, quote) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Magento_Checkout/summary/grand-total'
        },
        
        /**
         * Use data from donation module to determine if item is recurring
         * 
         *  @return bool isRecurring whether or not the cart contains a recurring donation
         */
        isRecurring: function() {
            return window.checkoutConfig.isRecurring;
        },

        /**
         * @param {*} price
         * @return {*|String}
         */
        getFormattedPrice: function (price) {
            console.log(quote.getPriceFormat());
            let priceFormat = quote.getPriceFormat();

            // Remove sign from pattern so we can inject it with html
            let sign = priceFormat.pattern.substr(0, priceFormat.pattern.indexOf('%s')); 
            priceFormat.pattern = "%s";

            if ( num % 1 != 0 ) {
                price = price;
            } else {
                // Convert to 2 decimal
                price = price.toFixed(2);
            }
            
            return '<span class="sign"> ' + sign + ' </span>' +  price.replace(/\d(?=(\d{3})+\.)/g, '$&,'); ///, priceUtils.formatPrice(price, quote.getPriceFormat());
        },

        /**
         * @return {*}
         */
        isDisplayed: function () {
            return this.isFullMode();
        },

        /**
         * Get pure value.
         */
        getPureValue: function () {
            var totals = quote.getTotals()();

            if (totals) {
                return totals['grand_total'];
            }

            return quote['grand_total'];
        },

        /**
         * @return {*|String}
         */
        getValue: function () {
            return this.getFormattedPrice(this.getPureValue());
        }
    });
});

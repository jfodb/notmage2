/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * @api
 */
define([
    'ko',
    'jquery',
    'uiComponent',
    'Magento_Ui/js/model/messageList'
], function (ko, $, Component, globalMessages) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Magento_Ui/messages',
            selector: '[data-role=checkout-messages]',
            isHidden: false,
            listens: {
                isHidden: 'onHiddenChange'
            }
        },

        /** @inheritdoc */
        initialize: function (config, messageContainer) {
            this._super()
                .initObservable();

            this.messageContainer = messageContainer || config.messageContainer || globalMessages;

            return this;
        },

        /** @inheritdoc */
        initObservable: function () {
            this._super()
                .observe('isHidden');

            return this;
        },

        /**
         * Checks visibility.
         *
         * @return {Boolean}
         */
        isVisible: function () {
            return this.isHidden(this.messageContainer.hasMessages());
        },

        /**
         * Remove all messages.
         */
        removeAll: function () {
            this.messageContainer.clear();
        },

        /**
         * @param {Boolean} isHidden
         */
        onHiddenChange: function (isHidden) {
            var self = this;

            // Hide message block if needed
            if (isHidden) {
                setTimeout(function () {
                    $(self.selector).hide('blind', {}, 500);
                }, 15000);

                try {
                    // Scroll to top error
                    $('html, body').animate({
                        scrollTop: 0
                    }, 2000);

                } catch (error) {
                    // Do nothing
                    console.log(error);
                }
            }
        }
    });
});
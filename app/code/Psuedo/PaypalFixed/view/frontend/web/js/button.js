/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
define([
  'uiComponent',
  'jquery',
  'Magento_Paypal/js/in-context/express-checkout-wrapper',
  'Magento_Customer/js/customer-data'
], function (Component, $, Wrapper, customerData) {
  'use strict';

  return Component.extend(Wrapper).extend({
    defaults: {
      declinePayment: false
    },

    /** @inheritdoc */
    initialize: function (config, element) {
      var cart = customerData.get('cart'),
          customer = customerData.get('customer');

      this._super();
      this.renderPayPalButtons(element);
      this.declinePayment = false;


      // 8/6/2019 - Magento 2.3.2 - Fixes guest cart issue
      console.error("JAVASCRIPT OVERRIDE!");
      if (cart().isGuestCheckoutAllowed === undefined) {
        cart.subscribe(function (updatedCart) {
          this.declinePayment = !customer().firstname && !cart().isGuestCheckoutAllowed;
          return updatedCart;
        }.bind(this));
      }
      // END CHANGE

      return this;
    },

    /** @inheritdoc */
    beforePayment: function (resolve, reject) {
      var promise = $.Deferred();

      if (this.declinePayment) {
        this.addError(this.signInMessage, 'warning');

        reject();
      } else {
        promise.resolve();
      }

      return promise;
    },

    /** @inheritdoc */
    prepareClientConfig: function () {
      this._super();
      this.clientConfig.commit = false;

      return this.clientConfig;
    }
  });
});
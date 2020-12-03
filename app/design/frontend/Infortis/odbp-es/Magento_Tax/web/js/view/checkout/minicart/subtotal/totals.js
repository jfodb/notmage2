/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * @api
 */

define([
	'ko',
	'uiComponent',
	'Magento_Customer/js/customer-data',
	'Magento/Framework/Locale/Resolver'
], function (ko, Component, customerData) {
	'use strict';

	return Component.extend({
		displaySubtotal: ko.observable(true),

		/**
		 * @override
		 */


		initialize: function () {
			this._super();
			this.cart = customerData.get('cart');
		},

		getLocale: function () {
			console.log("MDthis: ", this);
			return this.getLocale(); //eslint-disable-line eqeqeq
		}
	});
});

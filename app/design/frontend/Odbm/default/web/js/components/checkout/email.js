/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'uiComponent',
    'ko',
    'Magento_Customer/js/model/customer',
    'Magento_Customer/js/action/check-email-availability',
    'Magento_Customer/js/action/login',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/checkout-data',
    'Magento_Checkout/js/model/full-screen-loader',
    'mage/validation'
], function ($, Component, ko, customer, checkEmailAvailability, loginAction, quote, checkoutData, fullScreenLoader) {
    'use strict';

    var validatedEmail = checkoutData.getValidatedEmailValue();

    if (validatedEmail && !customer.isLoggedIn()) {
        quote.guestEmail = validatedEmail;
    }

    return Component.extend({
        defaults: {
            template: 'Magento_Checkout/form/element/email',
            email: checkoutData.getInputFieldEmailValue(),
            emailFocused: false,
            isLoading: false,
            isPasswordVisible: false,
            listens: {
                email: 'emailHasChanged',
                emailFocused: 'validateEmail'
            }
        },
        checkDelay: 2000,
        checkRequest: null,
        isEmailCheckComplete: null,
        isCustomerLoggedIn: customer.isLoggedIn,
        forgotPasswordUrl: window.checkoutConfig.forgotPasswordUrl,
        emailCheckTimeout: 0,

        /**
         * Initializes observable properties of instance
         *
         * @returns {Object} Chainable.
         */
        initObservable: function () {
            this._super()
                .observe(['email', 'emailFocused', 'isLoading', 'isPasswordVisible']);

            return this;
        },

        /** @inheritdoc */
        initConfig: function () {
            this._super();

            this.isPasswordVisible = this.resolveInitialPasswordVisibility();

            return this;
        },

        /**
         * Callback on changing email property
         */
        emailHasChanged: function () {
            var self = this;

            clearTimeout(this.emailCheckTimeout);

            if (self.validateEmail()) {
                quote.guestEmail = self.email();
                checkoutData.setValidatedEmailValue(self.email());
            } else {

            }
            this.emailCheckTimeout = setTimeout(function () {
                if (self.validateEmail()) {
                    self.checkEmailAvailability();
                } else {
                    self.isPasswordVisible(false);
                }
            }, self.checkDelay);

            checkoutData.setInputFieldEmailValue(self.email());
        },

        /**
         * Check email existing.
         */
        checkEmailAvailability: function () {
            // NOTE: we are tempoararily disabling this feature,
            // because we are only doing guest checkout for MVP release
            this.isPasswordVisible(false);
            this.isLoading(false);

            // this.validateRequest();
            // this.isEmailCheckComplete = $.Deferred();
            // this.isLoading(true);
            // this.checkRequest = checkEmailAvailability(this.isEmailCheckComplete, this.email());

            // $.when(this.isEmailCheckComplete).done(function () {
            //     this.isPasswordVisible(false);
            // }.bind(this)).fail(function () {
            //     this.isPasswordVisible(true);
            //     checkoutData.setCheckedEmailValue(this.email());
            // }.bind(this)).always(function () {
            //     this.isLoading(false);
            // }.bind(this));
        },

        /**
         * If request has been sent -> abort it.
         * ReadyStates for request aborting:
         * 1 - The request has been set up
         * 2 - The request has been sent
         * 3 - The request is in process
         */
        validateRequest: function () {
            if (this.checkRequest != null && $.inArray(this.checkRequest.readyState, [1, 2, 3])) {
                this.checkRequest.abort();
                this.checkRequest = null;
            }
        },

        scrollToError: function() {
            var loginFormSelector = 'form[data-role=email-with-possible-login]',
            usernameSelector = loginFormSelector + ' input[name=username]';

            // Scroll to validation result
            var scrollTo = 0;

            // Get the location of top error message
            $(usernameSelector).each(function(index) {
                if ( scrollTo == 0 || ( $(this).offset().top < scrollTo ) ) {
                    scrollTo = $(this).offset().top - 90;
                }
            });
 
            // Scroll to top error
            $('html, body').animate({
                scrollTop: scrollTo
            }, 2000);       
        },

        /**
         * Local email validation.
         *
         * @param {Boolean} focused - input focus.
         * @returns {Boolean} - validation result.
         */
        validateEmail: function (focused) {
            var loginFormSelector = 'form[data-role=email-with-possible-login]',
                usernameSelector = loginFormSelector + ' input[name=username]',
                loginForm = $(loginFormSelector),
                validator;

            loginForm.validation();

            if (focused === false && !!this.email()) {
                return !!$(usernameSelector).valid();
            }

            validator = loginForm.validate();

            var isValid = validator.check(usernameSelector) || false;

            if ( !isValid ) {
                $('#checkout button.action.action-update, #checkout button.action.checkout').click(this.scrollToError);
            } else {
                $('#checkout button.action.action-update, #checkout button.action.checkout').off('click',this.scrollToError);
            }

            return isValid;
        },

        /**
         * Log in form submitting callback.
         *
         * @param {HTMLElement} loginForm - form element.
         */
        login: function (loginForm) {
            var loginData = {},
                formDataArray = $(loginForm).serializeArray();

            formDataArray.forEach(function (entry) {
                loginData[entry.name] = entry.value;
            });

            if (this.isPasswordVisible() && $(loginForm).validation() && $(loginForm).validation('isValid')) {
                fullScreenLoader.startLoader();
                loginAction(loginData).always(function () {
                    fullScreenLoader.stopLoader();
                });
            }
        },

        /**
         * Resolves an initial sate of a login form.
         *
         * @returns {Boolean} - initial visibility state.
         */
        resolveInitialPasswordVisibility: function () {
            if (checkoutData.getInputFieldEmailValue() !== '') {
                return checkoutData.getInputFieldEmailValue() === checkoutData.getCheckedEmailValue();
            }

            return false;
        }
    });
});

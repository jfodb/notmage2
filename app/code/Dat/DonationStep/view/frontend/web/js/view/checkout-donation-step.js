define(
    [
        'ko',
        'uiComponent',
        'underscore',
        'Magento_Checkout/js/model/step-navigator'
    ],
    function (
        ko,
        Component,
        _,
        stepNavigator,
    ) {
        'use strict';
        /**
         *  the name of the component's .html template
         */
        return Component.extend({
            defaults: {
                template: 'Dat_DonationStep/check-donation'
            },

            getDonation: function () {
                let donationBlock = window.checkoutConfig.checkout_donation_block;

                const donationBlockReg = donationBlock.replace(/(\r\n|\n|\r)/gm,"");
                console.log("donationBlock: ", donationBlockReg)
                console.log("donationBlock equals?: ", donationBlock===donationBlockReg)
                return donationBlockReg;
            },

            //add here your logic to display step,
            isVisible: ko.observable(true),

            // TODO: Check cart for donation, and skip if it does
            // isDonating: ko.observable(false),

            //step code will be used as step content id in the component template
            stepCode: 'hasDonatedCheck',

            //step title value
            stepTitle: 'Optional Donation',

            /**
             *
             * @returns {*}
             */
            initialize: function () {
                var self = this;
                this._super();
                // register your step
                stepNavigator.registerStep(
                    this.stepCode,
                    //step alias
                    null,
                    this.stepTitle,
                    //observable property with logic when display step or hide step
                    this.isVisible,

                    _.bind(this.navigate, this),

                    /**
                     * sort order value
                     * 'sort order value' < 10: step displays before shipping step;
                     * 10 < 'sort order value' < 20 : step displays between shipping and payment step
                     * 'sort order value' > 20 : step displays after payment step
                     */
                    1
                );

                return this;
            },

            /**
             * The navigate() method is responsible for navigation between checkout step
             * during checkout. You can add custom logic, for example some conditions
             * for switching to your custom step
             */
            navigate: function () {

            },

            /**
             * @returns void
             */
            navigateToNextStep: function () {
                stepNavigator.next();
            }
        });
    }
);

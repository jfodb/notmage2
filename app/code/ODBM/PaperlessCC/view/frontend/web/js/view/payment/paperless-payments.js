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
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (
        Component,
        rendererList
    ) {
        'use strict';
        rendererList.push(
            {
                type: 'odbm_paperlesscc',
                component: 'ODBM_PaperlessCC/js/view/payment/method-renderer/paperless-method'
            }
        );
        /** Add view logic here if needed */
        return Component.extend({});
    }
);

/**
 * Added during 2.3.2 (8/13/19) upgrade to bypass one-page checkout issues
 */
var config = {
  map: {
    '*': {
      'Magento_Checkout/js/view/billing-address/list':'ODBM_Paperless/js/view/billing-address/list'
    }
  }
};
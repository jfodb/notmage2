<?php

namespace ODBM\Donation\Model;
use Magento\Checkout\Model\Session;

/**
 * Add value to checkout config to show where there is a recurrring
 * item in the cart
 */
class AdditionalConfigProvider implements \Magento\Checkout\Model\ConfigProviderInterface
{
    protected $_session;

    public function __construct(  Session $session) {
        $this->_session = $session;
    }
    
    public function getConfig()
    {
        $output['isRecurring'] = $this->isRecurring();
        return $output;
    }
    
    public function isRecurring() {
        $is_recurring = false;

        $mainitems = $this->_session->getQuote()->getAllItems();
        //$items = $this->_session->getQuote()->getAllVisibleItems();

        foreach ($mainitems as $mitem) {
        	//->getProductOptionByCode('info_buyRequest')  is not set, so the request gets dumped go get() _data['product_options_by_code']

	        //root out the product to get the details
	        $product = $mitem->getProduct();
	        if(!empty($product)) {
		        //get product options
		        $options_deep = $product->getCustomOptions();
		        if (isset($options_deep['info_buyRequest'])) {
			        $json = $options_deep['info_buyRequest']->getData('value');
			        if ($json) {
				        $fields = json_decode($json, true);
				        if (!empty($fields['_recurring']) && $fields['_recurring'] == 'true') {
					        $is_recurring = true;
					        break;
				        }
			        }
		        }
	        }
        }


        return $is_recurring;
    }
}
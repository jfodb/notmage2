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

        $items = $this->_session->getQuote()->getAllVisibleItems();

        foreach( $items as $item ) {
            $product_options = $item->getProductOptionByCode('info_buyRequest');
            $is_recurring = $product_options['_recurring'] ?? $is_recurring;
        }
     
        return $is_recurring;
    }
}
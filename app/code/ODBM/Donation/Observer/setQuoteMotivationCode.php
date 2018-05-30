<?php
namespace ODBM\Donation\Observer;

use Magento\Framework\Event\ObserverInterface;

use Magento\Catalog\Model\Product;
use Magento\Checkout\Model\Cart;

class setQuoteMotivationCode implements ObserverInterface
{
   protected $_objectManager;

    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Checkout\Model\Cart $cart,
        \Magento\Catalog\Model\Product $product,
        \Magento\Framework\ObjectManagerInterface $interface,
        \Magento\Quote\Model\Quote\Item $quote
    ) {
        $this->_objectManager = $objectManager;
        $this->cart = $cart;
        $this->product = $product;
        $this->objectManager = $interface;
        $this->quote = $quote;
    }

    /**
    * Add attribute based on motivation code passed in via url
    *
    * @todo Sanitization of input
    */
    public function execute(\Magento\Framework\Event\Observer $observer) {
        $product = $observer->getProduct();
        $quoteItem = $observer->getQuoteItem();

        $_motivation_code;

        if ( !empty( $_REQUEST['_motivation_code'] ) ) {
            $_motivation_code = $_REQUEST['_motivation_code'];
        } else {
            $_motivation_code = $product->getSku();
        }

        $quoteItem->setCustomAttribute('_motivation_code', $_motivation_code);
    }
}
<?php
namespace ODBM\Donation\Observer;

use Magento\Framework\Event\ObserverInterface;

use Magento\Catalog\Model\Product;
use Magento\Checkout\Model\Cart;

class setQuoteRecurring implements ObserverInterface
{
	protected $_objectManager;

	public function __construct(
		\Magento\Framework\ObjectManagerInterface $objectManager,
		\Magento\Checkout\Model\Cart $cart,
		\Magento\Catalog\Model\Product $product,
		\Magento\Framework\ObjectManagerInterface $interface,
		\Magento\Quote\Model\Quote\Item $quote,
		\Magento\Framework\App\RequestInterface $request
	) {
		$this->_objectManager = $objectManager;
		$this->cart = $cart;
		$this->product = $product;
		$this->objectManager = $interface;
		$this->quote = $quote;
		$this->_request = $request;
	}

	/**
	 * Add attribute based on recurring passed in via url
	 *
	 * @todo Sanitization of input
	 */
	public function execute(\Magento\Framework\Event\Observer $observer) {
		$quoteItem = $observer->getQuoteItem();

		$_recurring = false;

		if ( !empty( $this->_request->getParam('_recurring') ) ) {
			$_recurring = $this->_request->getParam('_recurring');
		}

		$quoteItem->setCustomAttribute('_recurring', $_recurring);
	}
}
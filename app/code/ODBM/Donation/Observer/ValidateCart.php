<?php
/**
* Add extra validation when user adds to cart
*
* Currently enforces one donation per cart. If a user has more than one
* donation in the cart, only the latest one is kept.
*/
namespace ODBM\Donation\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\App\ObjectManager;

class ValidateCart implements ObserverInterface {
	// Set to true to alert user that a cart item was removed
	const DISPLAY_NOTICES = false;

	protected $_cart;
	protected $_messageManager;

	public function __construct(
	 \Magento\Checkout\Model\Cart $cart,
		\Magento\Framework\Message\ManagerInterface $messageManager
	) {
		$this->_cart = $cart;
		$this->_messageManager = $messageManager;
	}

	public function execute(\Magento\Framework\Event\Observer $observer) {
		$cartItemsCount = $this->_cart->getQuote()->getItemsCount();
		$cartItemsAll = $this->_cart->getQuote()->getAllItems();

		if ( $cartItemsCount > 0 ) {
			// Remove all other donation items
			foreach( $cartItemsAll as $quoteItem ) {
				if ( 'donation' === $quoteItem->getProductType() ) {
					$quoteItem->delete();
				}
			}

			// Add notice if necessary
			if ( self::DISPLAY_NOTICES ) {
				$this->_messageManager->addNotice(__('Only one donation allowed in cart at a time. All other donations have been removed'));
			}
		}
	}
}
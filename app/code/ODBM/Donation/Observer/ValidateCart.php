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

	protected $_urlManager;
	protected $_checkoutSession;
	protected $_cart;
	protected $_messageManager;
	protected $_redirect;
	protected $_request;
	protected $_response;
	protected $_responseFactory;
	protected $_resultFactory;
	protected $_scopeConfig;
	protected $_product;

	public function __construct(\Magento\Framework\UrlInterface $urlManager, \Magento\Checkout\Model\Session $checkoutSession, \Magento\Framework\App\Response\RedirectInterface $redirect, \Magento\Checkout\Model\Cart $cart, \Magento\Framework\Message\ManagerInterface $messageManager, \Magento\Framework\App\RequestInterface $request, \Magento\Framework\App\ResponseInterface $response, \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig, \Magento\Catalog\Model\Product $product, \Magento\Framework\App\ResponseFactory $responseFactory, \Magento\Framework\Controller\ResultFactory $resultFactory
	) {
		$this->_urlManager = $urlManager;
		$this->_checkoutSession = $checkoutSession;
		$this->_redirect = $redirect;
		$this->_cart = $cart;
		$this->_messageManager = $messageManager;
		$this->_request = $request;
		$this->_response = $response;
		$this->_responseFactory = $responseFactory;
		$this->_resultFactory = $resultFactory;
		$this->_scopeConfig = $scopeConfig;
		$this->_product = $product;
	}

	public function execute(\Magento\Framework\Event\Observer $observer) {

		$controller = $observer->getControllerAction();
		$postValues = $this->_request->getPostValue();
		$cartQuote = $this->_cart->getQuote()->getData();
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
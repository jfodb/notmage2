<?php
namespace ODBM\Donation\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class RedirectEmptyCart implements ObserverInterface
{
    /**
     * @var \Magento\Framework\App\ResponseFactory
     */
    private $responseFactory;

    /**
     * @var \Magento\Framework\UrlInterface
     */
    private $_url;
    private $_cart;

    public function __construct(
        \Magento\Framework\UrlInterface $url,
        \Magento\Checkout\Model\Cart $cart
    ) {
        $this->_url = $url;
        $this->_cart = $cart;
    }

    public function execute(Observer $observer) {

        if ( $this->isCartEmpty() ) {
            $redirectionUrl = $this->_url->getUrl('donation/donate/cause');
        } else {
            $redirectionUrl = $this->_url->getUrl('checkout');  
        }

        $observer->getControllerAction()
        ->getResponse()
        ->setRedirect($redirectionUrl);

        return $this;
    }

    protected function isCartEmpty() {
        return (int)$this->_cart->getQuote()->getItemsCount() === 0;
    }
}
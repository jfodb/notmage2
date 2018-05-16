<?php
namespace ODBM\Donation\Controller\Donate;

use Magento\Framework\App\Action\Context;

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

class Cause extends \Magento\Framework\App\Action\Action
{
	protected $_productRepository;
//	protected $_scopeConfig;

	public function __construct(
		Context $context,
		\Magento\Catalog\Model\ProductRepository $productRepository,
		\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
	) {

		$this->_productRepository = $productRepository;
		$this->_scopeConfig = $scopeConfig;

		parent::__construct( $context );
	}

	public function execute() {
		$motivation = $this->getRequest()->getParam('motivation');

		$product_url = $this->get_product_url_by_motivation( $motivation );

		// Create redirect to product url
		$resultRedirect = $this->resultRedirectFactory->create();
		$resultRedirect->setUrl( $product_url );

		return $resultRedirect;
	}

	protected function get_default_sku() {
		// Get the product to return if no sku is set
		$sku = 'default_donate';

		// Get sku from configuration
		try {
			$sku = $this->_scopeConfig->getValue('odbmdonations/general/default_donate', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
		} catch ( \Exception $e ) {
			echo ($e->getMessage());
			die('Exception in Cause::get_default_sku()');
		}

		return $sku;
	}

	protected function get_product_url_by_motivation( $motivation_code = false ) {
		$product_url = false;

		if ( !$motivation_code ) {
			// Get default motivation code
			$motivation_code = $this->get_default_sku();
		}

		if ( $motivation_code ) {
			// Get product from catalog
			// No product with that sku throughts exception
			try {
				$_product = $this->_productRepository->get( $motivation_code );

				if ( $_product ) {
					$product_url = $_product->getProductUrl();
				} else {
					// If product doesn't exist, we want to call this
					// function again to get the default product url
					$product_url = $this->get_product_url_by_motivation();
				}
			} catch( \Exception $e ) {
				// If product doesn't exist, we want to call this
				// function again to get the default product url
				$product_url = $this->get_product_url_by_motivation();
			}
		}

		return $product_url;
	}
}
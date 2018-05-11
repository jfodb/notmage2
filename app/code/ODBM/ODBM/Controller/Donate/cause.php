<?php
namespace ODBM\ODBM\Controller\Cause;

use Magento\Framework\App\Action\Context;

class Cause extends \Magento\Framework\App\Action\Action
{
	protected $_productRepository;

	public function __construct(
		Context $context,
		\Magento\Catalog\Model\ProductRepository $productRepository
	) {
		$this->_productRepository = $productRepository;
		parent::__construct( $context );
	}

	public function execute() {
		$motivation = $this->getRequest()->getParam('motivation');

		$product_url = $this->get_product_by_motivation( $motivation );

		// Redirect to login URL
		/** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
		$resultRedirect = $this->resultRedirectFactory->create();
		$resultRedirect->setUrl( $product_url );

		return $resultRedirect;
	}

	protected function get_default_product() {
		// Get the product to return if no sku is set
	}

	protected function get_product_by_motivation( $motivation_code ) {
		$product_url = false;

		if ( $motivation_code ) {
			$_product = $this->_productRepository->get( $sku );

			if ( $_product ) {
				$product_url = $_product->getProductUrl();
			}
		}

		return $product_url;
	}
}
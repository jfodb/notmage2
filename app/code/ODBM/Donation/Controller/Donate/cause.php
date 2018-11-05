<?php
namespace ODBM\Donation\Controller\Donate;

use Magento\Framework\App\Action\Context;

class Cause extends \Magento\Framework\App\Action\Action
{
	protected $_productRepository;
	protected $_scopeConfig;
	protected $_productCollectionFactory;
	protected $productVisibility;
	protected $productStatus;

	public function __construct(
		Context $context,
		\Magento\Catalog\Model\ProductRepository $productRepository,
		\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
		\Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
		\Magento\Catalog\Model\Product\Attribute\Source\Status $productStatus,
		\Magento\Catalog\Model\Product\Visibility $productVisibility
	) {

		$this->_productRepository = $productRepository;
		$this->_scopeConfig = $scopeConfig;
		$this->_productCollectionFactory = $productCollectionFactory;

		$this->productStatus = $productStatus;
		$this->productVisibility = $productVisibility;

		parent::__construct( $context );
	}

	public function execute() {
		$motivation = $this->getRequest()->getParam('motivation');

		$product_url = $this->get_product_url_by_motivation( $motivation, empty($motivation) );

		if ( !empty($motivation) ) {
			$product_url .= "?motivation=$motivation";
		}

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
			$collection = $this->_productCollectionFactory->create();

			$collection->addAttributeToSelect('*');
			$collection->addAttributeToFilter('in_cause_pool', 1);

			$collection->addAttributeToFilter('status', ['in' => $this->productStatus->getVisibleStatusIds()]);
			$collection->setVisibility($this->productVisibility->getVisibleInSiteIds());

			$products = $collection->getData();

			if ( !empty($products) ) {
				$sku = trim( $products[mt_rand(0, count( $products ) - 1)]['sku'] );
			} else {
				$sku = $this->_scopeConfig->getValue('odbmdonations/general/default_donate', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);

			// support for multiple default options, as
			// comma-separated list
				if ( !empty( $sku_arr = explode( ',', $sku ) ) ) {
				// Note: not using array_rand()
					$sku = trim( $sku_arr[mt_rand(0, count( $sku_arr ) - 1)] );
				}
			}

		} catch ( \Exception $e ) {
			echo( $e->getMessage() );
			die('Exception in Cause::get_default_sku()');
		}

		return $sku;
	}

	protected function get_product_url_by_motivation( $motivation_code = false,  $add_query_string = false ) {
		$product_url = false;
		$used_default = false;

		if ( !$motivation_code ) {
			// Get default motivation code
			$motivation_code = $this->get_default_sku();

			$used_default = true;
		}

		if ( $motivation_code ) {
			// Get product from catalog
			// No product with that sku throughts exception
			try {
				$_product = $this->_productRepository->get( $motivation_code );

				if ( $_product ) {
					$product_url = $_product->getProductUrl();

					if ( $add_query_string ) {
						$product_url .= "?motivation=$motivation_code";
					}
				} else {
					// If product doesn't exist, we want to call this
					// function again to get the default product url
					if ( !$used_default ) {
						$product_url = $this->get_product_url_by_motivation();
					} else {
						throw new \Exception( 'Default sku ({$motivation_code}) is not a product!' );
					}
				}
			} catch( \Exception $e ) {
				// If product doesn't exist, we want to call this
				// function again to get the default product url
				if ( !$used_default ) {
					$product_url = $this->get_product_url_by_motivation();
				} else {
					throw new \Exception( "Default sku ({$motivation_code}) is not a product!" );
				}
			}
		}

		return $product_url;
	}
}
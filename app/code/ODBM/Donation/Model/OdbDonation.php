<?php
namespace ODBM\Donation\Model;

use ODBM\Donation\Api\OdbDonationInterface;
use Magento\Framework\App\Action\Context;

class OdbDonation implements OdbDonationInterface {
	protected $_productRepository;
	protected $_scopeConfig;
	protected $_productCollectionFactory;
	protected $_resultJsonFactory;

	public function __construct(
		\Magento\Catalog\Model\ProductRepository $productRepository,
		\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
		\Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
		\Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
	) {

		$this->_productRepository = $productRepository;
		$this->_scopeConfig = $scopeConfig;
		$this->_productCollectionFactory = $productCollectionFactory;
		$this->_resultJsonFactory = $resultJsonFactory;
	}

	protected function get_product_response( $motivation_code ) {
		$response = false;

		try {
			$_product = $this->_productRepository->get( $motivation_code );

			if ( $_product ) {
				$url = $_product->getProductUrl();
				$response = compact('motivation_code', 'url');
			}

		} catch ( \Exception $e ) {}

		return $response;
	}

	/**
     * Returns an array of possible motivation codes in the random pool.
     * If a motivaiton code is given, then it will return data for a specific motivaiton code
     *
     * @api
     * @param mixed $motivation Motivation code supplied.
     * @return array {
     *              float raised
     *              float goal
     *              int total order
     *              float offlineGiving
     *         }
     */
    public function get_fundraiser_data( $motivation = '' ) {
		$data = [];
		return $data;
	}

	/**
	* Returns an array of possible motivation codes in the random pool.
	* If a motivaiton code is given, then it will return data for a specific motivaiton code
	*
	* @api
	* @param string $motivation Motivation code supplied.
	* @return mixed List of products in random pool.
	*/
	public function get_cause_pool( $motivation_code = '' ) {
		$collection = $this->_productCollectionFactory->create();

		$collection->addAttributeToSelect('*');
		$collection->addAttributeToFilter('in_cause_pool', 1);

		$products = $collection->getData();

		$response = array();

		if ( !empty($motivation_code) ) {
			// get one
			if ( $element = $this->get_product_response( $motivation_code ) ) {
				$response[] = $element;
			}
		} else {
			// get all
			foreach( $products as $product ) {

				$motivation_code = $product['sku'];

				if ( $element = $this->get_product_response( $motivation_code ) ) {
					$response[] = $element;
				}
			}
		}

		return $response;

		//$result = $this->_resultJsonFactory->create();
	//	return $result->setData($response);
	}
}
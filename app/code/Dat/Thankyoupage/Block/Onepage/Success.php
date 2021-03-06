<?php

namespace Dat\Thankyoupage\Block\Onepage;

/**
 * One page checkout success page
 */
class Success extends \Magento\Checkout\Block\Onepage\Success
{

	protected $orderItemsDetails;
	protected $_productRepositoryFactory;

	/**
	 * @param \Magento\Framework\View\Element\Template\Context $context
	 * @param \Magento\Sales\Model\Order $orderItemsDetails
	 * @param array $data
	 */
	public function __construct(
		\Magento\Framework\View\Element\Template\Context $context,
		\Magento\Checkout\Model\Session $checkoutSession,
		\Magento\Sales\Model\Order\Config $orderConfig,
		\Magento\Framework\App\Http\Context $httpContext,
		\Magento\Sales\Model\Order $orderItemsDetails,
		\Magento\Catalog\Api\ProductRepositoryInterfaceFactory $productRepositoryFactory,
		array $data = []
	) {
		parent::__construct($context, $checkoutSession, $orderConfig, $httpContext, $data);
		$this->orderItemsDetails = $orderItemsDetails;
		$this->_productRepositoryFactory = $productRepositoryFactory;
	}

	public function getOrderItemsDetails() {
		$increment_id  = $this->_checkoutSession->getLastRealOrder()->getIncrementId();
		$order_information = $this->orderItemsDetails->loadByIncrementId($increment_id);

		return $order_information;
	}

	// Get custom Thank You page 
	public function getCustomPage() {
		// Get order item
		$order = $this->getOrderItemsDetails();
		$items = $order->getAllItems();
		$order_item = $items[0];
		//get ID of CMS block
		$product = $this->_productRepositoryFactory->create()->getById($order_item->getProductId())->getData('thank_you_page_block');
		return (int)$product;
	}

	public function isOrderRecurring() {
		// Get order item
		$order = $this->getOrderItemsDetails();
		$items = $order->getAllItems();
		//$order_item = $items[0];

		$is_recurring = false;

		foreach ( $items as $order_item ) {
			// Get stored product info
			$product_options = $order_item->getProductOptionByCode('info_buyRequest');
			$is_recurring = $product_options['_recurring'] ?? false;

			$is_recurring = !empty( $is_recurring ) && ($is_recurring !== 'false');

			if ( $is_recurring ) {
				break;
			}
		}

		return $is_recurring;
	}
	
	public function hasGaData() {
		if(
			$this->_session->getGaAct() &&
			$this->_session->getGaLab() &&
			$this->_session->getGaVal()
		)
			return true;
		else
			return false;
	}
	
	public function getGaForm() {

		
		$cat = $this->_session->getGaCat();
		$act = $this->_session->getGaAct();
		$label = $this->_session->getGaLab();
		$value = $this->_session->getGaVal();
		
		$this->_session->unsGaCat();
		$this->_session->unsGaAct();
		$this->_session->unsGaLab();
		$this->_session->unsGaVal();

		
		return sprintf('<form id="gadata"><input type="hidden" name="gacat" id="gacat" value="%s" /><input type="hidden" name="gaact" id="gaact" value="%s" /><input type="hidden" name="galab" id="galab" value="%s" /><input type="hidden" name="gaval" id="gaval" value="%s" /> </form>', $cat, $act, $label, $value);
		
	}
}
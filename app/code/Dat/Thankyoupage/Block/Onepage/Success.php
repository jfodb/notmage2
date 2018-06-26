<?php

namespace Dat\Thankyoupage\Block\Onepage;

/**
 * One page checkout success page
 */
class Success extends \Magento\Checkout\Block\Onepage\Success
{

	protected $orderItemsDetails;

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
		array $data = []
	) {
		parent::__construct($context, $checkoutSession, $orderConfig, $httpContext, $data);
		$this->orderItemsDetails = $orderItemsDetails;
	}

	public function getOrderItemsDetails() {
		$increment_id  = $this->_checkoutSession->getLastRealOrder()->getIncrementId();
		$order_information = $this->orderItemsDetails->loadByIncrementId($increment_id);

		return $order_information;
	}

	public function isOrderRecurring() {
		// Get order item
		$order = $this->getOrderItemsDetails();
		$items = $order->getAllItems();
		$order_item = $items[0];

		$is_recurring = false;

		foreach ( $items as $order_item ) {
			// Get stored product info
			$product_options = $order_item->getProductOptionByCode('info_buyRequest');
			$is_recurring = $product_options['_recurring'] ?? false;

			$is_recurring = !empty( $is_recurring ) && ($is_recurring !== 'false');

			if ( $is_recurring )
				break;
		}

		return $is_recurring;
	}
}
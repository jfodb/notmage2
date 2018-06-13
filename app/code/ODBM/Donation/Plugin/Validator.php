<?php
/**
 * Created by PhpStorm.
 * User: twilson
 * Date: 6/13/18
 * Time: 10:07 AM
 */

namespace ODBM\Donation\Plugin;

use Magento\Checkout\Model\Session;


class Validator
{
	/**
	 * @var \Magento\Paypal\Model\ConfigFactory
	 */
	private $_paypalConfigFactory;

	/**
	 * @var \Magento\Framework\Registry
	 */
	private $_registry;

	/**
	 * @var \Magento\Catalog\Model\ProductTypes\ConfigInterface
	 */
	private $_productTypeConfig;

	/**
	 * @var \Magento\Payment\Helper\Data
	 */
	private $_paymentData;

	/**
	 * @var \Magento\Checkout\Model\Session
	 */
	private $_session;

	/**
	 * @param \Magento\Paypal\Model\ConfigFactory $paypalConfigFactory
	 * @param \Magento\Framework\Registry $registry
	 * @param \Magento\Catalog\Model\ProductTypes\ConfigInterface $productTypeConfig
	 * @param \Magento\Payment\Helper\Data $paymentData
	 * @param \Magento\Checkout\Model\Session
	 */
	public function __construct(
		\Magento\Paypal\Model\ConfigFactory $paypalConfigFactory,
		\Magento\Framework\Registry $registry,
		\Magento\Catalog\Model\ProductTypes\ConfigInterface $productTypeConfig,
		\Magento\Payment\Helper\Data $paymentData,
		\Magento\Checkout\Model\Session $session
	) {
		$this->_paypalConfigFactory = $paypalConfigFactory;
		$this->_registry = $registry;
		$this->_productTypeConfig = $productTypeConfig;
		$this->_paymentData = $paymentData;
		$this->_session = $session;
	}

	public function afterIsPriceOrSetAvailable(\Magento\Paypal\Helper\Shortcut\Validator $subject, $isInCatalog)
	{
		if ($isInCatalog) {
			// Show PayPal shortcut on a product view page only if product has nonzero price
			/** @var $currentProduct \Magento\Catalog\Model\Product */
			$currentProduct = $this->_registry->registry('current_product');

			if ($currentProduct !== null) {
				$productPrice = (double)$currentProduct->getFinalPrice();
				$typeInstance = $currentProduct->getTypeInstance();
				if (empty($productPrice)
					&& !$this->_productTypeConfig->isProductSet($currentProduct->getTypeId())
					&& !$typeInstance->canConfigure($currentProduct)
					&& !$currentProduct->getTypeId() === "donation"
				) {
					return  false;
				}
			}
		}
		return true;
	}

//	public function afterIsContextAvailable(\Magento\Paypal\Helper\Shortcut\Validator $subject, $paymentCode, $isInCatalog)
//	{
//		$items = $this->_session->getQuote()->getAllItems();
//		foreach ($items as $item) {
//			$options = $item->getOptions();
//			$infoBuyRequest = json_decode($options[1]->getValue());
//			if (isset($infoBuyRequest->_recurring) && $infoBuyRequest->_recurring == true) {
//				return false;
//			}
//		}
//
//	}

}
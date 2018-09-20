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

	public function afterIsPriceOrSetAvailable(\Magento\Paypal\Helper\Shortcut\Validator $subject, $result)
	{
		if ($result) {
			$currentProduct = $this->_registry->registry('current_product');
			if ($currentProduct !== null) {
				if ($currentProduct->getTypeId() === "donation") {
					return true;
				} else {
					return $result;
				}
			}
		}
		return true;
	}

}
<?php
/**
 * Created by PhpStorm.
 * User: twilson
 * Date: 6/15/18
 * Time: 10:47 AM
 */

namespace ODBM\Donation\Plugin;

use Magento\Store\Model\ScopeInterface;

class Donation
{

	/*
	 * @var \Magento\Framework\App\Config\ScopeConfigInterface
	 */
	private $_scopeConfig;

	public function __construct(\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig)
	{
		$this->_scopeConfig = $scopeConfig;
	}

	public function afterGetFixedAmounts(\Experius\DonationProduct\Helper\Data $subject, $result)
	{
		$fixedAmountsConfig = $this->_scopeConfig->getValue($subject::DONATION_CONFIGURATION_FIXED_AMOUNTS, ScopeInterface::SCOPE_STORE);
		if (empty($fixedAmountsConfig)) {
			return "";
		} else {
			return $result;
		}

	}
}
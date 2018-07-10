<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace ODBM\PaperlessCC\Model;

use Magento\Checkout\Model\ConfigProviderInterface;
/**
 * Class ConfigProvider
 */
final class ConfigProvider implements ConfigProviderInterface
{
	public function __construct(
		\Magento\Payment\Model\CcConfig $ccConfig
	) {
		$this->ccConfig = $ccConfig;
	}

	const CODE = 'odbm_paperlesscc';
	/**
	 * Retrieve assoc array of checkout configuration
	 *
	 * @return array
	 */
	public function getConfig()
	{
		$output['payment'][self::CODE] = array(
			'availableTypes' => $this->ccConfig->getCcAvailableTypes(),
			'months' => $this->ccConfig->getCcMonths(),
			'years' => $this->ccConfig->getCcYears(),
			'hasVerification' => true //$this->ccConfig->hasVerification(),
		);
		return $output;
	}
}
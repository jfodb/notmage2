<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace ODBM\PaperlessCC\Model;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\View\Asset\Source;
/**
 * Class ConfigProvider
 */
final class ConfigProvider implements ConfigProviderInterface
{
	public function __construct(
		\Magento\Payment\Model\CcConfig $ccConfig,
		Source $assetSource
	) {
		$this->ccConfig = $ccConfig;
		$this->assetSource = $assetSource;
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
			'test' => '1234'
		);
		return $output;
	}
}
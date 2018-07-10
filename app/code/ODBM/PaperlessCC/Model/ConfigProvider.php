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
	const CODE = 'odbm_paperlesscc';
	/**
	 * Retrieve assoc array of checkout configuration
	 *
	 * @return array
	 */
	public function getConfig()
	{
		return [
			'payment' => [
				self::CODE => [
					'testConfig' => '123',
					'transactionResults' => [
						's' => 'Success',
						'f' => 'Failure'
					 ]
				]
			]
		];
	}
}
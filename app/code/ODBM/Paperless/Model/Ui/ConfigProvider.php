<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace ODBM\Paperless\Model\Ui;
use Magento\Checkout\Model\ConfigProviderInterface;
use ODBM\Paperless\Gateway\Http\Client\ClientMock;
/**
 * Class ConfigProvider
 */
final class ConfigProvider implements ConfigProviderInterface
{
	const CODE = 'paperless';
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
					'transactionResults' => [
						ClientMock::SUCCESS => __('Success'),
						ClientMock::FAILURE => __('Fraud')
					]
				]
			]
		];
	}
}
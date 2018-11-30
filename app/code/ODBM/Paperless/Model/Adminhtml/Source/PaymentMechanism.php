<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace ODBM\Paperless\Model\Adminhtml\Source;

/**
 * Class PaymentAction
 */
class PaymentMechanism implements \Magento\Framework\Option\ArrayInterface
{
	/**
	 * {@inheritdoc}
	 */
	public function toOptionArray()
	{
		return [
			[
				'value' => 'direct',
				'label' => __('Gateway Direct')
			],
			[
				'value' => 'iframe',
				'label' => __('Iframe PCI')
			]
		];
	}
}
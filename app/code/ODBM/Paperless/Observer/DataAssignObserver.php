<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace ODBM\Paperless\Observer;
use Magento\Framework\Event\Observer;
use Magento\Payment\Observer\AbstractDataAssignObserver;
use Magento\Quote\Api\Data\PaymentInterface;

class DataAssignObserver extends AbstractDataAssignObserver
{
	/**
	 * @param Observer $observer
	 * @return void
	 */
	public function execute(Observer $observer)
	{
		$method = $this->readMethodArgument($observer);
		$data = $this->readDataArgument($observer);
		$paymentInfo = $method->getInfoInstance();
		if ($data->getDataByKey('transaction_result') !== null) {
			$paymentInfo->setAdditionalInformation(
				'transaction_result',
				$data->getDataByKey('transaction_result')
			);
		}

		$additionalData = $data->getData(PaymentInterface::KEY_ADDITIONAL_DATA);
		if (!is_array($additionalData)) {
			return;
		}

		$paymentModel = $this->readPaymentModelArgument($observer);

		$paymentModel->setAdditionalInformation(
			$additionalData
		);
	}
}
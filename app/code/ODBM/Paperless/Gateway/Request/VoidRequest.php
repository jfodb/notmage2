<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace ODBM\Paperless\Gateway\Request;
use Magento\Payment\Gateway\ConfigInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
class VoidRequest extends PaperlessRequest
{
	
	/**
	 * Builds ENV request
	 *
	 * @param array $buildSubject
	 * @return array
	 */
	public function build(array $buildSubject)
	{
		if (!isset($buildSubject['payment'])
			|| !$buildSubject['payment'] instanceof PaymentDataObjectInterface
		) {

			$this->_logger->critical("Paperless Void request experienced an invalid argument");
			throw new \InvalidArgumentException('Payment data object should be provided', 500);
		}

		$base_req = parent::build($buildSubject);
		$base_req['req']['uri'] = '/transactions/refund';


		/** @var PaymentDataObjectInterface $paymentDO */
		$paymentDO = $buildSubject['payment'];
		$order = $paymentDO->getOrder();
		$payment = $paymentDO->getPayment();
		if (!$payment instanceof OrderPaymentInterface) {
			$this->_logger->critical("Paperless Void request experienced non-order Payment Interface");
			throw new \LogicException('Order payment should be provided.', 500);
		}

		$additional = [
			'amount' => [
				'currency' => 'USD',
				'value' => $buildSubject['amount']
			],
			'source' => ['approvalNumber' => $payment->getCcApproval()]
		];
		
		
		return array_merge($base_req, $additional);
	}
}
<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace ODBM\Paperless\Gateway\Response;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Response\HandlerInterface;
class TxnIdHandler implements HandlerInterface
{
	const TXN_ID = 'referenceId';
	const AUTHAPRV_ID = 'approvalNumber';
	const CAPAPRV_ID = 'authorizationNumber';
	const PROAPPRV_ID = 'profileNumber';
	/**
	 * Handles transaction id
	 *
	 * @param array $handlingSubject
	 * @param array $response
	 * @return void
	 */
	public function handle(array $handlingSubject, array $response)
	{
		if (!isset($handlingSubject['payment'])
			|| !$handlingSubject['payment'] instanceof PaymentDataObjectInterface
		) {
			throw new \InvalidArgumentException('Payment data object should be provided');
		}

		if(is_string($response))
			$response = json_decode($response,true);

		/** @var PaymentDataObjectInterface $paymentDO */
		$paymentDO = $handlingSubject['payment'];
		$payment = $paymentDO->getPayment();
		/** @var $payment \Magento\Sales\Model\Order\Payment */
		$payment->setTransactionId($response[self::TXN_ID]);
		if(!empty($response['authorization'])) {
			$payment->setCcApproval($response['authorization'][self::AUTHAPRV_ID]);

			/*$payment->setAmountAuthorized($response['authorization']['amount']['value']);
			if(!empty($response['authorization']['accountType']) && $response['authorization']['accountType'] == 'CC' && preg_match('/([A-Z])+ xxxx([0-9]+)/', $response['authorization']['accountDescription'], $tags)){
				if(empty($payment->getCcType()))
					$payment->setCcType($tags[1]);
				if(empty($payment->getCcLast4()))
					$payment->setCcLast4(substr($tags[2], 4));

			}*/
		} else if(!empty($response['transaction'])) {

			$payment->setCcApproval($response['transaction'][self::CAPAPRV_ID]);
			/*$payment->setAmountPaid($response['transaction']['amount']['value']);
			if(!empty($response['transaction']['accountType']) && $response['transaction']['accountType'] == 'CC' && preg_match('/([A-Z])+ xxxx([0-9]+)/', $response['transaction']['accountDescription'], $tags)){
				if(empty($payment->getCcType()))
					$payment->setCcType($tags[1]);
				if(empty($payment->getCcLast4()))
					$payment->setCcLast4(substr($tags[2], 4));
			}*/
		}
		else if(!empty($response['profile'])) {
			$payment->setCcApproval($response['profile'][self::PROAPPRV_ID]);
		}

		$payment->setIsTransactionClosed(false);
	}
}
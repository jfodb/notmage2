<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace ODBM\Paperless\Gateway\Request;
use Magento\Payment\Gateway\ConfigInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
class CaptureRequest extends PaperlessRequest
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
			throw new \InvalidArgumentException('Payment data object should be provided');
		}

		$base_req = parent::build($buildSubject);
		$base_req['req']['uri'] = '/transactions/capture';
		
		
		/** @var PaymentDataObjectInterface $paymentDO */
		$paymentDO = $buildSubject['payment'];
		$order = $paymentDO->getOrder();
		$payment = $paymentDO->getPayment();
		if (!$payment instanceof OrderPaymentInterface) {
			throw new \LogicException('Order payment should be provided.');
		}

		$additional = [
			'amount' => [
				'currency' => $order->getStoreCurrencyCode(),
				'value' => $payment->getBaseAmountAuthorized()  //is this the correct field? should I find a $buildSubject['amount'] ??
			]
		];
		
		if($payment->getBaseAmountAuthorized() && !empty($payment->getCcApproval())){
			$additional['source'] = ['approvalNumber' => $payment->getCcApproval()];
		} else
		if ($this->is_tokenized()) {
			$additional['source'] = ['profileNumber' => $payment->getUserCardToken()];
			$additional['metadata'] = $this->customfields;
		} else {

			$address = $order->getBillingAddress();
			
			$cardname = $payment->getCcOwner();
			if(empty($cardname))
				$cardname = $address->getFirstname() . ' ' . $address->getLastname();
			$civ = $payment->getCcCid();
			if(empty($civ))
				$civ = $payment->getCcSecureVerify();
			
			$additional['source'] = [
				'card' => [
					'accountNumber' => $payment->getCcNumber(),
					'expiration' => $payment->getCcExpMonth() . $payment->getCcExpYear(),
					'nameOnAccount' => $cardname,
					'securityCode' => $civ,
					'billingAddress'=> [
						'street' => $address->getStreet(),
				        'city' => $address->getCity(),
				        'state' => $address->getRegionCode(),
				        'postal' => $address->getPostcode(),
				        'country' => $address->getCountryId()
					]
				]
			];
			$additional['metadata'] = $this->customfields;
		} 
		
		return array_merge($base_req, $additional);
	}
}
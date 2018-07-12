<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace ODBM\Paperless\Gateway\Request;

use Magento\Payment\Gateway\ConfigInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;

class AuthorizationRequest extends PaperlessRequest
{
	/**
	 * @var ConfigInterface
	 */
	
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
		$base_req['req']['uri'] = '/transactions/authorize';


		$payment = $buildSubject['payment'];
		$order = $payment->getOrder();
		$address = $order->getBillingAddress();
		


		$addition = [
			'amount' => [
				'currency' => $order->getStoreCurrencyCode(),
				'value' => $payment->getBaseAmountAuthorized()  //is this the correct field? is there a $buildSubject['amount'] ?
			]
		];
		
		
		
		if($this->is_tokenized()){
			$addition['source'] = ['profileNumber' => $payment->getUserCardToken()];  //how do we get the user card token?
			$addition['metadata'] = $this->customfields;
		} else {

			$cardname = $payment->getCcOwner();
			if(empty($cardname))
				$cardname = $address->getFirstname() . ' ' . $address->getLastname();
			$civ = $payment->getCcCid();  //this is deprecated, how do we get it??
			if(empty($civ))
				$civ = $payment->getCcSecureVerify();
			

			$expmonth = $payment->getCcExpMonth();
			if( strlen($payment->getCcExpMonth()) === 1)
				$expmonth = sprintf('%2$d', $expmonth);

			$expyear = $payment->getCcExpYear();
			if( strlen($payment->getCcExpYear()) === 2)
				$expyear = '20'.$expyear;
			
			
			$addition['source'] = [
				'card' => [
					'accountNumber' => $payment->getCcNumber(),
					'expiration' => $expmonth . '/' . $expyear,
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
			$addition['metadata'] = $this->customfields;
		}
		
		/** @var PaymentDataObjectInterface $payment */
		
		return array_merge($base_req, $addition);
	}
}
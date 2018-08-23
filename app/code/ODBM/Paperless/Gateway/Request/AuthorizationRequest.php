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

		/** @var PaymentDataObjectInterface $paymentDO */
		$paymentDO = $buildSubject['payment'];
		$order = $paymentDO->getOrder();
		$payment = $paymentDO->getPayment();
		$address = $order->getBillingAddress();

		$addition = [
			'amount' => [
				'currency' => 'USD',
				'value' => $buildSubject['amount']  //is this the correct field? is there a $buildSubject['amount'] ?
			]
		];

		if($this->is_tokenized()){
			$addition['source'] = ['profileNumber' => $payment->getUserCardToken()];  //how do we get the user card token?
			$addition['metadata'] = $this->customfields;
		} elseif($this->is_recurring($paymentDO)){
			$base_req['_recurring'] = true;

			$profile_information = $this->getProfileInformation($buildSubject);
			$profile_information['profile']['profileNumber'];
		} else {

			$cardname = $payment->getCcOwner();
			if(empty($cardname)) {
				$cardname = $address->getFirstname() . ' ' . $address->getLastname();
			}

			$civ = $this->_encryptor->decrypt( $payment->getCcCid() );

			if(empty($civ))
				$civ = $payment->getCcSecureVerify();

			$expmonth = $payment->getCcExpMonth();
			$expyear = $payment->getCcExpYear();

			if(strlen($expmonth) === 2) {
				$expmonth = sprintf('%\'.02d', $expmonth);
			}

			$addition['source'] = [
				'card' => [
					'accountNumber' => $payment->getCcNumber(),
					'expiration' => $expmonth . '/' . $expyear,
					'nameOnAccount' => $cardname,
					'securityCode' => $civ,
					'billingAddress'=> [
						'street' => $address->getStreetLine1(),
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
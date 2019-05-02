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
			$this->_logger->critical("Paperless Capture request experienced an invalid argument");
			throw new \InvalidArgumentException('Payment data object should be provided');
	}

	$base_req = parent::build($buildSubject);
	$base_req['req']['uri'] = '/transactions/capture';

	$GLOBALS['_FLAGS']['payment']['method'] = 'paperless';

	//$payment_action = $this->_config->getValue('payment/odbm_paperless/payment_action',\Magento\Store\Model\ScopeInterface::SCOPE_STORE);

	/** @var PaymentDataObjectInterface $paymentDO */
	$paymentDO = $buildSubject['payment'];
	$order = $paymentDO->getOrder();
	$payment = $paymentDO->getPayment();

	if (!$payment instanceof OrderPaymentInterface) {
		$this->_logger->critical("Paperless Capture request experienced an non-order payment");
		throw new \LogicException('Order payment should be provided.');
	}

	$additional = [
		'amount' => [
			'currency' => 'USD', //$order->getOrderCurrencyCode(),
				'value' => $buildSubject['amount']  //is this the correct field? should I find a $buildSubject['amount'] ??
			]
		];

		if(!empty((string)$payment->getCcApproval())){
			$additional['source'] = ['approvalNumber' => $payment->getCcApproval()];
		} else {
			if ($profile = $this->is_profiled($payment)){
				if($profile === true)
					$additional['source'] = ['profileNumber' => $payment->getCcStatusDescription()];
				else
					$additional['source'] = ['profileNumber' => $profile];

				$additional['metadata'] = $this->customfields;
			}
			else
			if ($token = $this->is_tokenized($payment)) {
				$token = $payment->getAdditionalInformation('cc_token');
				if(preg_match('/^[\'"]".*[\'"]$/', $token))
					$token = json_decode($token);
				$cardname = $payment->getCcOwner();

				if(empty($cardname)) {
					$address = $order->getBillingAddress();
					$cardname = $address->getFirstname() . ' ' . $address->getLastname();
				}
				$additional['source'] = [
					'card' => [
						'accountNumber' => '',
						'nameOnAccount' => $cardname,
						'expiration' => ''
					],
					'token' => $token
				];

				$additional['metadata'] = $this->customfields;
			} else if($this->is_recurring($paymentDO)){
				$base_req['_recurring'] = true;

				$profile_information = $this->getProfileInformation($buildSubject);
				$profileNumber = $profile_information['profile']['profileNumber'];

				$additional['source'] = ['profileNumber' => $profileNumber];
				$additional['metadata'] = $this->customfields;
			} else {
				$address = $order->getBillingAddress();

				$cardname = $payment->getCcOwner();
				if(empty($cardname))
					$cardname = $address->getFirstname() . ' ' . $address->getLastname();
				
				$civ = $payment->getCcCid();
				if(empty($civ))
					$civ = $payment->getCcSecureVerify();
				if(!empty($civ) && strlen($civ) > 4)
					$civ = $this->_encryptor->decrypt( $civ );

				$expmonth = $payment->getCcExpMonth();
				if( strlen($payment->getCcExpMonth()) === 1)
					$expmonth = sprintf('%\'.02d', $expmonth);

				$expyear = $payment->getCcExpYear();
				if( strlen($payment->getCcExpYear()) === 2)
					$expyear = '20'.$expyear;

				$additional['source'] = [
					'card' => [
						'accountNumber' => $this->_encryptor->decrypt( $payment->getCcNumberEnc() ) ,
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
				$additional['metadata'] = $this->customfields;
			}
		}
		
		if($payment)
			$payment->setCcNumberEnc('');

		return array_merge($base_req, $additional);
	}


}
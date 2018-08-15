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

	$payment_action = $this->config->getValue('payment/odbm_paperless/payment_action',\Magento\Store\Model\ScopeInterface::SCOPE_STORE);

	/** @var PaymentDataObjectInterface $paymentDO */
	$paymentDO = $buildSubject['payment'];
	$order = $paymentDO->getOrder();
	$payment = $paymentDO->getPayment();

	if (!$payment instanceof OrderPaymentInterface) {
		throw new \LogicException('Order payment should be provided.');
	}
	
	if(empty($_POST) && empty($payment->getCcNumberEnc())){
		ob_start();
		$STDIN = fopen('php://input', 'r');
		while($f = fgets($STDIN)){
			echo $f;
		}
		$in = ob_get_clean(); /**/
		
		if(strlen($in) > 5 && $in[0] == '{'){
			$stdin = json_decode($in, true);
		}
	}

	$additional = [
		'amount' => [
			'currency' => 'USD', //$order->getOrderCurrencyCode(),
				'value' => $buildSubject['amount']  //is this the correct field? should I find a $buildSubject['amount'] ??
			]
		];

		if(!empty($payment->getBaseAmountAuthorized()) && !empty($payment->getCcApproval())){
			$additional['source'] = ['approvalNumber' => $payment->getCcApproval()];
		} else {
			if ($this->is_tokenized()) {
				$additional['source'] = ['profileNumber' => $payment->getUserCardToken()];
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

				$expmonth = $payment->getCcExpMonth();
				if( strlen($payment->getCcExpMonth()) === 1)
					$expmonth = sprintf('%2$d', $expmonth);

				$expyear = $payment->getCcExpYear();
				if( strlen($payment->getCcExpYear()) === 2)
					$expyear = '20'.$expyear;

				$additional['source'] = [
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
				$additional['metadata'] = $this->customfields;
			}
		}

		return array_merge($base_req, $additional);
	}
}
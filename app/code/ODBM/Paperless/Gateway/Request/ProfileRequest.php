<?php
/**
 * Created by PhpStorm.
 * User: ppostma
 * Date: 7/11/18
 * Time: 10:04 PM
 */

namespace ODBM\Paperless\Gateway\Request;

use Magento\Payment\Gateway\ConfigInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;

class ProfileRequest extends PaperlessRequest
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
		$base_req['req']['uri'] = '/profiles/create';


		$payment = $buildSubject['payment'];
		$order = $payment->getOrder();
		$address = $order->getBillingAddress();


		

		$cardname = $payment->getCcOwner();
		if(empty($cardname))
			$cardname = $address->getFirstname() . ' ' . $address->getLastname();
		$civ = $payment->getCcCid();  //this is deprecated, how do we get it??
		if(empty($civ))
			$civ = $payment->getCcSecureVerify();



		
		$addition['source'] = [
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
			],
			'email' => $address->getEmail()
		];
		

		/** @var PaymentDataObjectInterface $payment */

		return array_merge($base_req, $addition);
	}
}
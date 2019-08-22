<?php
/**
 * Created by PhpStorm.
 * User: ppostma
 * Date: 9/12/18
 * Time: 10:00 AM
 */

namespace ODBM\Donation\Model\Paypal\Express;
use Magento\Paypal\Model\Express;


class Checkout extends \Magento\Paypal\Model\Express\Checkout {



	//always save the address. Do not just discard what we recieved
	public function returnFromPaypal($token)
	{
		$this->_getApi()
			->setToken($token)
			->callGetExpressCheckoutDetails();
		$quote = $this->_quote;

		$this->ignoreAddressValidation();

		// import shipping address
		$exportedShippingAddress = $this->_getApi()->getExportedShippingAddress();


		$shippingAddress = $quote->getShippingAddress();
		if ($shippingAddress) {
			if ($exportedShippingAddress
				&& $quote->getPayment()->getAdditionalInformation(self::PAYMENT_INFO_BUTTON) == 1
			) {
				$this->_setExportedAddressData($shippingAddress, $exportedShippingAddress);

				$shippingAddress->setCollectShippingRates(true);
				//$shippingAddress->setSameAsBilling(0);
			}

			// import shipping method
			$code = '';
			if ($this->_getApi()->getShippingRateCode()) {
				$code = $this->_matchShippingMethodCode($shippingAddress, $this->_getApi()->getShippingRateCode());
				if ($code) {
					// possible bug of double collecting rates :-/
					$shippingAddress->setShippingMethod($code)->setCollectShippingRates(true);
				}
			}
			$quote->getPayment()->setAdditionalInformation(
				self::PAYMENT_INFO_TRANSPORT_SHIPPING_METHOD,
				$code
			);
		}


		// import billing address
		$portBillingFromShipping = $quote->getPayment()->getAdditionalInformation(self::PAYMENT_INFO_BUTTON) == 1
			&& $this->_config->getValue(
				'requireBillingAddress'
			) != \Magento\Paypal\Model\Config::REQUIRE_BILLING_ADDRESS_ALL
			&& !empty($shippingAddress) && !empty($shippingAddress->getCountryId());

		if ($portBillingFromShipping) {
			$billingAddress = clone $shippingAddress;
			$billingAddress->unsAddressId()->unsAddressType()->setCustomerAddressId(null);
			$data = $billingAddress->getData();
			$data['save_in_address_book'] = 0;
			$quote->getBillingAddress()->addData($data);
			$quote->getShippingAddress()->setSameAsBilling(1);
		} else {
			$billingAddress = $quote->getBillingAddress();
		}
		$exportedBillingAddress = $this->_getApi()->getExportedBillingAddress();

		$this->_setExportedAddressData($billingAddress, $exportedBillingAddress);
		$billingAddress->setCustomerNote($exportedBillingAddress->getData('note'));
		$quote->setBillingAddress($billingAddress);
		$quote->setCheckoutMethod($this->getCheckoutMethod());

		// import payment info
		$payment = $quote->getPayment();
		$payment->setMethod($this->_methodType);
		$this->_paypalInfo->importToPayment($this->_getApi(), $payment);
		$payment->setAdditionalInformation(self::PAYMENT_INFO_TRANSPORT_PAYER_ID, $this->_getApi()->getPayerId())
			->setAdditionalInformation(self::PAYMENT_INFO_TRANSPORT_TOKEN, $token);
		$quote->collectTotals();
		$this->quoteRepository->save($quote);
	}


	//thanks for making this private
	protected function ignoreAddressValidation()
	{
		$this->_quote->getBillingAddress()->setShouldIgnoreValidation(true);

		$this->_quote->getShippingAddress()->setShouldIgnoreValidation(true);

		if (!$this->_config->getValue('requireBillingAddress')
			&& !$this->_quote->getBillingAddress()->getEmail()
		) {
			$this->_quote->getShippingAddress()->setSameAsBilling(1);
		}

	}
}
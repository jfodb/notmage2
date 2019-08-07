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
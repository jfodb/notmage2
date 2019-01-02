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

		/** @var PaymentDataObjectInterface $payment */
		return $this->getProfileRequestBody($buildSubject);
	}
}
<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace ODBM\Paperless\Gateway\Validator;
use Magento\Payment\Gateway\Validator\AbstractValidator;
use Magento\Payment\Gateway\Validator\ResultInterface;
use ODBM\Paperless\Gateway\Http\Client\ClientMock;
class ResponseCodeValidator extends AbstractValidator
{
	const RESULT_CODE = 'isApproved';
	/**
	 * Performs validation of result code
	 *
	 * @param array $validationSubject
	 * @return ResultInterface
	 */
	public function validate(array $validationSubject)
	{
		if (!isset($validationSubject['response']) || !(is_array($validationSubject['response']) || is_string($validationSubject['response']))) {
			throw new \InvalidArgumentException('Response does not exist as expected object type');
		}
		if(is_string($validationSubject['response']))
			$response = json_decode($validationSubject['response'],true);
		else
			$response = $validationSubject['response'];
		
		if ($this->isSuccessfulTransaction($response)) {
			return $this->createResult(
				true,
				[]
			);
		} else {
			return $this->createResult(
				false,
				[__('Gateway rejected the transaction.')]
			);
		}
	}
	/**
	 * @param array $response
	 * @return bool
	 */
	private function isSuccessfulTransaction(array $response)
	{
		return isset($response[self::RESULT_CODE])
			&& $response[self::RESULT_CODE] !== ClientMock::FAILURE;
	}
}
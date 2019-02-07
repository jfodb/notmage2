<?php
/**
 * Created by PhpStorm.
 * User: ppostma
 * Date: 2019-01-30
 * Time: 11:50
 */

namespace ODBM\Paperless\Plugin;


class ProfileCapture
{
	protected $encryptor;


	public function __construct(
		\Magento\Framework\Encryption\EncryptorInterface $encryptor
	) {
		$this->encryptor = $encryptor;

	}

	public function beforeBuild($request, array $buildSubject) {
		$paymentDO = $buildSubject['payment'];
		$isrecurring = $request->is_recurring($paymentDO);

		$request->improptu_profile($buildSubject['payment']);

	}
}
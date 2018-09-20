<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace ODBM\Paperless\Gateway\Http;
use Magento\Payment\Gateway\Http\TransferBuilder;
use Magento\Payment\Gateway\Http\TransferFactoryInterface;
use Magento\Payment\Gateway\Http\TransferInterface;
use ODBM\Paperless\Gateway\Request\MockDataRequest;
class TransferFactory implements TransferFactoryInterface
{
	/**
	 * @var TransferBuilder
	 */
	private $transferBuilder;
	/**
	 * @param TransferBuilder $transferBuilder
	 */
	public function __construct(
		TransferBuilder $transferBuilder
	) {
		$this->transferBuilder = $transferBuilder;
	}
	/**
	 * Builds gateway transfer object
	 *
	 * @param array $request
	 * @return TransferInterface
	 */
	public function create(array $request)
	{
		$request_details = $request['req'];
		unset($request['req']);

		$domain = /*from configs*/ 'https://api.paperlesstrans.com';
		$url = $domain . $request_details['uri'];

		$headrs = [
			'Content-Type' => 'application/json',
			'TerminalKey'  => $request_details['Token']['TerminalKey']
		];

		if($request_details['TestMode']) {
			$headrs['TestFlag'] = 'true';
		}


		return $this->transferBuilder
			->setUri($url)
			->setBody( json_encode($request) )
			->setMethod('POST')
			->setHeaders($headrs )
			->build();
	}
}
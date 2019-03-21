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
	private $_config;

	/**
	 * @param TransferBuilder $transferBuilder
	 */
	public function __construct(
		TransferBuilder $transferBuilder,
		\Magento\Framework\App\Config\ScopeConfigInterface $config
	) {
		$this->transferBuilder = $transferBuilder;
		$this->_config = $config;
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

		//merchant_gateway_key
		//payment_domain
		$config_value = $this->_config->getValue('payment/odbm_paperless/payment_domain', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
		if(!empty($config_value))
			$domain = $config_value;
		else
			$domain = 'https://api.paperlesstrans.com';
		
		$url = $domain . $request_details['uri'];

		$headrs = [
			'Content-Type: application/json',
			'TerminalKey: ' . $request_details['Token']['TerminalKey']
		];

		if( !empty( $request_details['TestMode'] ) ) {
			$headrs[] = 'TestFlag: true';
		}


		return $this->transferBuilder
			->setUri($url)
			->setBody( json_encode($request) )
			->setMethod('POST')
			->setHeaders($headrs )
			->build();
	}
}
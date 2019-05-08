<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace ODBM\Paperless\Gateway\Http\Client;
use Magento\Payment\Gateway\Http\ClientInterface;
use Magento\Payment\Gateway\Http\TransferInterface;
use Magento\Payment\Model\Method\Logger;

class TransactClient extends \Magento\Payment\Gateway\Http\Client\Zend
{

	protected $clientFactory;
	protected $converter;
	protected $logger;

	public function __construct(
		\Magento\Framework\HTTP\ZendClientFactory $clientFactory,
		\Psr\Log\LoggerInterface $mylogger,
		Logger $theirlogger,
		\Magento\Payment\Gateway\Http\ConverterInterface $converter = null
	) {
		$this->clientFactory = $clientFactory;
		$this->converter = $converter;
		$this->logger = $mylogger;


		parent::__construct($clientFactory, $theirlogger, $converter);
	}

	public function placeRequest(TransferInterface $transferObject)
	{
		$log = [
			'request' => $transferObject->getBody(),
			'request_uri' => $transferObject->getUri()
		];
		$result = [];
		/** @var ZendClient $client */
		$client = $this->clientFactory->create();

		$configs = $transferObject->getClientConfig();
		$configs['timeout'] = 180; //seconds
		$configs['maxredirects'] = 2;

		$client->setConfig($configs)
		->setMethod($transferObject->getMethod())
		->setRawData($transferObject->getBody(), 'application/json')
		->setHeaders($transferObject->getHeaders())
		->setUrlEncodeBody($transferObject->shouldEncode())
		->setUri($transferObject->getUri())
		;

		try {
			$response = $client->request();

			$resbody = $response->getBody();

			if(is_array($resbody))
				$result = $resbody;
			else
				$result = json_decode($resbody, true);

			$result['httpcode'] = $response->getStatus();

		} catch (\Zend_Http_Client_Exception $e) {
			$this->logger->critical("Payment Gateway Error HTTP Client Exception");
			$this->logger->critical($e);
			throw new \Magento\Payment\Gateway\Http\ClientException(
				__('Error connecting to payment gateway. Your payment might have gone through but we cannot see the results'), 0, $e
			);
		} catch (\Magento\Payment\Gateway\Http\ConverterException $e) {
			$this->logger->critical("Payment Gateway Error HTTP Client Converter");
			$this->logger->critical($e);
			throw $e;
		} catch (\Exception $e) {
			$this->logger->critical("Paperless transacton general exception");
			throw $e;
		}

		return $result;
	}
}
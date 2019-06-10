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
	protected $session;

	public function __construct(
		\Magento\Framework\HTTP\ZendClientFactory $clientFactory,
		\Psr\Log\LoggerInterface $mylogger,
		Logger $theirlogger,
		\Magento\Customer\Model\Session $sessionManager,
		\Magento\Payment\Gateway\Http\ConverterInterface $converter = null
	) {
		$this->clientFactory = $clientFactory;
		$this->converter = $converter;
		$this->logger = $mylogger;
		$this->session = $sessionManager;

		parent::__construct($clientFactory, $theirlogger, $converter);
	}

	public function placeRequest(TransferInterface $transferObject)
	{
		//check for cardhash
		if(method_exists($transferObject, 'getCardHash')){
			$cardhash = $transferObject->getCardHash();
		} else if (!empty($GLOBALS['currentCardHash'])) {
			$cardhash = $GLOBALS['currentCardHash'];
		} else {
			$cardhash = false;
		}
		$cacheresult = false;

		//check session for previous transaction on this card
		if($cardhash) {
			$possibleduplicated = $this->session->getTransactionList();
			if ($possibleduplicated && isset($possibleduplicated[$cardhash])) {
				$cacheresult = $possibleduplicated[$cardhash];
				$cacheresult['fromHash'] = $cardhash;
			}
		}

		//if cache and is not expired by 5+ minutes
		if($cacheresult && $cacheresult['tag']['ctime']+320 > time()) {
			if(isset($GLOBALS['currentTransAmont'], $cacheresult['tag']['amount'])){
				if($GLOBALS['currentTransAmont'] == $cacheresult['tag']['amount'])
					$result = $cacheresult;
			} else {
				$result = $cacheresult;

				if(empty($GLOBALS['_FLAGS']))
					$GLOBALS['_FLAGS'] = array();
				$GLOBALS['_FLAGS']['isdejavu'] = true;
			}

			if(isset($result))
				unset($result['tag']); //remove cache set time and amount
		}
		if(empty($result)) {

			//clear possible expired cache
			if($cacheresult && $cacheresult['tag']['ctime']+320 < time()){
				unset($possibleduplicated[$cardhash]);
				$this->session->setTransactionList($possibleduplicated);
			}


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
				__('Error connecting to payment gateway. Your payment might have gone through but we cannot see the results'), $e, 502
			);
		} catch (\Magento\Payment\Gateway\Http\ConverterException $e) {
			$this->logger->critical("Payment Gateway Error HTTP Client Converter");
			$this->logger->critical($e);
			throw $e;
		} catch (\Exception $e) {
			$this->logger->critical("Paperless transacton general exception");
			throw $e;
		}

			if($result['httpcode'] == 200 && $cardhash) {
				//cache only good responses.
				$transactions = $this->session->getTransactionList();
				if (empty($transactions))
					$transactions = array();
				$transactions[$cardhash] = $result;
				$transactions[$cardhash]['tag'] = ['ctime' => time()];
				if(isset($GLOBALS['currentTransAmont']))
					$transactions[$cardhash]['tag']['amount'] = $GLOBALS['currentTransAmont'];
				$this->session->setTransactionList($transactions);
			}

		}

		return $result;
	}
}
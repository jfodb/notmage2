<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace ODBM\Paperless\Model\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;
/**
 * Class ConfigProvider
 */
final class ConfigProvider implements ConfigProviderInterface
{
	private $_clientFactory;
	private $_encryptor;
	private $_config;

	const CODE = 'odbm_paperless';

	public function __construct(
		\Magento\Payment\Model\CcConfig $ccConfig,
		\Magento\Framework\App\Config\ScopeConfigInterface $config,
		\Magento\Framework\Encryption\EncryptorInterface $encryptor,
		\Magento\Framework\HTTP\ZendClientFactory $clientFactory
	) {
		$this->ccConfig = $ccConfig;
		$this->_config = $config;
		$this->_clientFactory = $clientFactory;
		$this->_encryptor = $encryptor;
	}

	/**
	 * Retrieve assoc array of checkout configuration
	 *
	 * @return array
	 */
	public function getConfig() {
		$output['payment'][self::CODE] = array(
			'availableTypes' => $this->ccConfig->getCcAvailableTypes(),
			'months' => $this->ccConfig->getCcMonths(),
			'years' => $this->ccConfig->getCcYears(),
			'hasVerification' => $this->ccConfig->hasVerification(),
			'disposableTerminalKey' => $this->getTemporaryToken(),
			'isiFrame' =>  $this->getIsPaperlessiFrame()
		);
		return $output;
	}

	/**
	 * Get system config value that tells us whether we want to display the form
	 * in an iframe
	 * 
	 * @return boolean
	 */
	private function getIsPaperlessiFrame() {
		$config_value = $this->_config->getValue('payment/odbm_paperless/payment_mechanism', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);

		return ($config_value !== 'direct');
	}

	/**
	 * Get temporary token for authenticating using iFrame
	 * 
	 * Uses Paperless's keygen utility in their REST API
	 * 
	 * @todo    Remove the staging url, once Paperless goes live.
	 * @return  string $token The temporary token to authenicate with.
	 */
	private function getTemporaryToken() {		
		// Token is false if we are not using an iframe
		if ( !$this->getIsPaperlessiFrame() )
			return false;

		$terminal_key_enc = $this->_config->getValue('payment/odbm_paperless/merchant_gateway_key', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
		$terminal_key = $this->_encryptor->decrypt($terminal_key_enc);

		$domain = /*from configs*/ 'https://staging-api.paperlesstrans.com';
		$url = '/util/keygen';

		$headers = [
			'Content-Type: application/json',
			'TerminalKey: ' . $terminal_key
		];

		$token = '';

		/** @var ZendClient $client */
		$client = $this->_clientFactory->create();
		$client->setMethod(\Zend\Http\Request::METHOD_GET);

		$client->setHeaders($headers);
		$client->setUri( $domain . $url);

		try {
			$response = $client->request();	
			$response_body = $response->getBody();

			// Should be 202: Accepted or 200: OK
			$status = $response->getStatus();
			if ( !( $status === 202 || $status === 200 ) ) {
				// Should throw exception here?
				return false;
			}

			if( is_array($response_body) )
				$body = $response_body;
			else
				$body = json_decode($response_body, true);

			$token = $body['authenticationKey'];
			$token = $body;

			if ( empty($token) ) {
				throw new \Exception( 'Could not get Paperless token: no token data.' );
			}
		} catch (\Zend_Http_Client_Exception $e) {
			throw new \Magento\Payment\Gateway\Http\ClientException(
				__($e->getMessage(), print_r($response, true))
			);
		} catch (\Magento\Payment\Gateway\Http\ConverterException $e) {
			throw $e;
		}

		return $token;
	}
}
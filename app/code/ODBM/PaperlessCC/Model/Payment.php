<?php
/**
 * Paperless payment method model
 *
 * @category    ODBM
 * @package     ODBM_PaperlessCC
 * @author      Our Daily Bread Ministries
 * @copyright   Our Daily Bread Ministries (https://ourdailybread.org)
 */

namespace ODBM\PaperlessCC\Model;

class Payment extends \Magento\Payment\Model\Method\Cc
{
	const CODE = 'odbm_paperlesscc';
	const API_BASE_URL = 'https://api.paperlesstrans.com/transactions/';

	protected $_code = self::CODE;

	protected $_isGateway                   = true;
	protected $_canCapture                  = true;
	protected $_canCapturePartial           = true;
	protected $_canRefund                   = true;
	protected $_canRefundInvoicePartial     = true;

	private $_apiKey;

	protected $_countryFactory;

	protected $_minAmount = null;
	protected $_maxAmount = null;
	protected $_supportedCurrencyCodes = array('USD');

	protected $_debugReplacePrivateDataKeys = ['number', 'exp_month', 'exp_year', 'cvc'];

	public function __construct(
		\Magento\Framework\Model\Context $context,
		\Magento\Framework\Registry $registry,
		\Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
		\Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
		\Magento\Payment\Helper\Data $paymentData,
		\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
		\Magento\Payment\Model\Method\Logger $logger,
		\Magento\Framework\Module\ModuleListInterface $moduleList,
		\Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
		\Magento\Directory\Model\CountryFactory $countryFactory,
		\Zend\Http\Client $zend_client,
		array $data = array()
	) {
		parent::__construct(
			$context,
			$registry,
			$extensionFactory,
			$customAttributeFactory,
			$paymentData,
			$scopeConfig,
			$logger,
			$moduleList,
			$localeDate,
			null,
			null,
			$data
		);

		$this->_countryFactory = $countryFactory;

		$this->_apiKey = $this->getConfigData('api_key');

		$this->_minAmount = $this->getConfigData('min_order_total');
		$this->_maxAmount = $this->getConfigData('max_order_total');

		$this->_client = $zend_client;
	}

	/**
	 * Payment authorization
	 *
	 * @param \Magento\Payment\Model\InfoInterface $payment
	 * @param float $amount
	 * @return $this
	 * @throws \Magento\Framework\Validator\Exception
	 */
	public function authorize(\Magento\Payment\Model\InfoInterface $payment, $amount)
	{
		$response = false;

		try {
			$response = $this->callPaperlessApi('authorize', $payment, $amount);

			// Status is 200 OK
			if ( $response['success'] ) {
				$response = $response['data'];

				$payment
				->setTransactionId($response['referenceId'])
				->setCcApproval($response['authorization']['approvalNumber'])
				->setIsTransactionClosed(0);
			} else {
				throw new \Magento\Framework\Validator\Exception(__( 'Error calling Paperless API (authorize).' ));
			}
		} catch (\Exception $e) {
			$this->debugData(['request' => $requestData, 'exception' => $e->getMessage()]);
			$this->_logger->error(__('Payment authorization error.'));
			throw new \Magento\Framework\Validator\Exception(__('Payment authorization error.'));
		}

		return $this;
	}

	/**
	 * Payment capturing
	 *
	 * @param \Magento\Payment\Model\InfoInterface $payment
	 * @param float $amount
	 * @return $this
	 * @throws \Magento\Framework\Validator\Exception
	 */
	public function capture(\Magento\Payment\Model\InfoInterface $payment, $amount)
	{
		$response = false;

		try {
			$response = $this->callPaperlessApi('capture', $payment, $amount);

			// Status is 200 OK
			if ( $response['success'] ) {
				$response = $response['data'];

				$payment
				->setTransactionId($response['referenceId'])
				->setIsTransactionClosed(0);
			} else {
				throw new \Magento\Framework\Validator\Exception(__( 'Error calling Paperless API (capture).' . $response['response'] ));
			}

		} catch (\Exception $e) {
			$this->debugData(['exception' => $e->getMessage()]);

			$this->_logger->error(__('Payment capturing error.' . $e->getMessage() . print_r( $response, true ) ) );
			throw new \Magento\Framework\Validator\Exception(__( 'Payment capturing error.' ));
		}

		return $this;
	}

	/**
	 * Payment refund
	 *
	 * @param \Magento\Payment\Model\InfoInterface $payment
	 * @param float $amount
	 * @return $this
	 * @throws \Magento\Framework\Validator\Exception
	 */
	public function refund(\Magento\Payment\Model\InfoInterface $payment, $amount)
	{
		$transactionId = $payment->getParentTransactionId();

		$response = $this->callPaperlessApi('refund', $payment, $amount);

		// Status is 200 OK
		if ( $response['success'] ) {
			$payment
			->setTransactionId($transactionId . '-' . \Magento\Sales\Model\Order\Payment\Transaction::TYPE_REFUND)
			->setParentTransactionId($transactionId)
			->setIsTransactionClosed(1)
			->setShouldCloseParentTransaction(1);
		} else {
			// Throw error
			throw new \Magento\Framework\Validator\Exception(__( 'Error calling Paperless API (refund).' ));
		}

		return $this;
	}

	/**
	* Call Paperless's REST API based on payment type
	*
	* @param  string $request_type   Payment type (authorize, capture, or refund)
	* @param  \Magento\Payment\Model\InfoInterface $payment
	* @param  float  $amount         Amount that payment is for.
	*
  *
	* @link https://api.paperlesstrans.com/ Documentation and test client.
	* @todo Return error message from API
	*
	* @return array $response Associative array with JSON reponse.
	*/
	protected function callPaperlessApi( $request_type, $payment, $amount ) {
		// Get body to send
		$requestData = $this->buildRequestData($request_type, $payment, $amount);
		$request = $this->buildRequestObject($request_type, $requestData);

		// Send out, get response
		/** @var \Zend\Http\Response **/
		$response = $this->_client->send($request);

		if ( $response->isOk() ) {
			$data = json_decode($response->getContent(), true);
			$success = true;
		} else {
			$success = false;
			$data = $response->toString();
		}

		return compact( 'success', 'data' );
	}

	/**
	* Build out body to send to API
	*
	* @param  string $request_type   Payment type (authorize, capture, or refund)
	* @param  \Magento\Payment\Model\InfoInterface $payment
	* @param  float  $amount         Amount that payment is for.
	*
	* @return array  $requestData
	*/
	protected function buildRequestData( $request_type, $payment, $amount ) {
		try {
			$requestData = array(
				'amount' => array(
					'value' => $amount,
					'currency' => 'USD' // $payment->getCurrencyCode()
				),
				'source' => array()
			);

			if ( $request_type === 'authorize' || $request_type === 'capture' ) {
				/** @var \Magento\Sales\Model\Order $order */
				$order = $payment->getOrder();

				/** @var \Magento\Sales\Model\Order\Address $billing */
				$billing = $order->getBillingAddress();

				// Format data
				$requestData['source']['card'] = $this->getCardInformation($payment, $billing->getName());
				$requestData['source']['billingAddress'] = $this->getBillingInformation($billing);

				$requestData['identification'] = $order->getIncrementId();
				$requestData['email'] = $order->getCustomerEmail();

				$requestData['metadata'] = array(
					array(
						'Description' => sprintf('#%s, %s', $order->getIncrementId(), $order->getCustomerEmail())
					)
				);
			}

			if ( $request_type === 'refund' || $request_type === 'capture' ) {
				$requestData['source']['approvalNumber'] = $payment->getCcApproval();
			}
		} catch(\Exception $e) {
			$this->debugData(['exception' => $e->getMessage()]);
			$this->_logger->error(__('Error building request data for ' . $request_type . ': ' . $e->getMessage()));
			throw new \Magento\Framework\Validator\Exception(__('Error building request data for ' . $request_type ));
		}

		return $requestData;
	}

	/**
	* Build request with body and headers
	*
	* @return \Zend\Http\Request request
	*/
	protected function buildRequestObject( $request_type, $body ) {
		// Make call to paperless
		$request = new \Zend\Http\Request();

		$request->setHeaders( $this->getHeaders() );
		$request->setUri( $this->getEndpoint( $request_type ) );

		$request->setMethod(\Zend\Http\Request::METHOD_POST);

		if ( !is_string($body) ) {
			$body = json_encode($body);
		}

		$request->setContent($body);

		return $request;
	}

	/**
	* Format Biling address information for calling API
	*
	* @param  \Magento\Sales\Model\Order\Address $billing
	*
	* @return array $billingInformation Formatted data.
	*/
	public function getBillingInformation( $billing ) {
		return array(
			'street' => $billing->getStreetLine(1),
			'city'   => $billing->getCity(),
			'state'  => $billing->getRegionCode(),
			'postal' => $billing->getPostcode(),
			'country' => $billing->getCountryId()
		);
	}

	/**
	* Format Card information for calling API
	*
	* @param  \Magento\Payment\Model\InfoInterface $payment
	* @param  string $nameOnAccount Optional. If not provided, name is retrieved from billing address
	*
	* @return array $cardInformation Formatted data.
	*/
	public function getCardInformation( $payment, $nameOnAccount = '' ) {
		if ( empty($nameOnAccount) ) {
			$order = $payment->getOrder();
			$billing = $order->getBillingAddress();
			$nameOnAccount = $billing->getName();
		}

		$exp_month = sprintf('%02d',$payment->getCcExpMonth());

		return  array(
			"accountNumber" => $payment->getCcNumber(),
			"expiration" => "{$exp_month}/{$payment->getCcExpYear()}",
			"nameOnAccount" => $nameOnAccount,
			"securityCode" => $payment->getCcCid(),
		);
	}

	/**
	* Get Paperless API key from Config
	*/
	private function getApiKey() {
		return $this->_apiKey;
	}

	/**
	* Get Request headers for calling Paperless API
	*
	* @return \Zend\Http\Headers $httpHeaders
	*/
	private function getHeaders() {
		$httpHeaders = new \Zend\Http\Headers();

		$httpHeaders->addHeaders([
			'Content-Type' => 'application/json',
			'TerminalKey'  => $this->getApiKey(),
			'TestFlag'     => 'true' // remove when done testing
		]);

		return $httpHeaders;
	}

	/**
	* Get Paperless's REST API endpoint based on payment type
	*
	* @param  string  $type      Payment type (authorize, capture, or refund)
	* @return string  $endpoint  Url of API endpoint to call.
	*/
	private function getEndpoint( $type = '' ) {
		return self::API_BASE_URL . $type;
	}

	/**
	 * Determine method availability based on quote amount and config data
	 *
	 * @param \Magento\Quote\Api\Data\CartInterface|null $quote
	 * @return bool
	 */
	public function isAvailable(\Magento\Quote\Api\Data\CartInterface $quote = null)
	{
		if ($quote && (
			$quote->getBaseGrandTotal() < $this->_minAmount
			|| ($this->_maxAmount && $quote->getBaseGrandTotal() > $this->_maxAmount))
	) {
			return false;
		}

		if (!$this->getConfigData('api_key')) {
			return false;
		}

		return parent::isAvailable($quote);
	}

	/**
	 * Availability for currency
	 *
	 * @param string $currencyCode
	 * @return bool
	 */
	public function canUseForCurrency($currencyCode) {
		return in_array($currencyCode, $this->_supportedCurrencyCodes);
	}
}

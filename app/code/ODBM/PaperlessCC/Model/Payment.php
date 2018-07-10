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
	 * Payment authoization
	 *
	 * @param \Magento\Payment\Model\InfoInterface $payment
	 * @param float $amount
	 * @return $this
	 * @throws \Magento\Framework\Validator\Exception
	 */
	public function authorize(\Magento\Payment\Model\InfoInterface $payment, $amount)
	{
		//throw new \Magento\Framework\Validator\Exception(__('Inside Stripe, throwing donuts :]'));

		/** @var \Magento\Sales\Model\Order $order */
		$order = $payment->getOrder();

		/** @var \Magento\Sales\Model\Order\Address $billing */
		$billing = $order->getBillingAddress();

		$exp_month = sprintf('%02d',$payment->getCcExpMonth());

		try {
			$requestData = [
				'amount' => array(
					'value' => $amount,
					'currency' => $order->getCurrencyCode(), // Default to USD
				),
				'source' => array(
					'card' => array(
						"accountNumber" => $payment->getCcNumber(),
						"expiration" => "{$exp_month}/{$payment->getCcExpYear()}",
						"nameOnAccount" => $billing->getName(),
						"securityCode" => $payment->getCcCid(),
				), // card information,
					'billingAddress' => array(
						'street' => $billing->getStreetLine(1),
						'city'   => $billing->getCity(),
						'state'  => $billing->getRegionCode(),
						'postal' => $billing->getPostcode(),
						'country' => $billing->getCountryId(),
						// To get full localized country name, use this instead:
						// 'address_country'   => $this->_countryFactory->create()->loadByCode($billing->getCountryId())->getName(),
					)
				),
				'identification' => $order->getIncrementId(),
				'email' => $order->getCustomerEmail(),
				'metadata' => array(
					array(
						'Description' => sprintf('#%s, %s', $order->getIncrementId(), $order->getCustomerEmail())
					)
				)
			];

			// Make call to paperless
			$request = new \Zend\Http\Request();
			$request->setHeaders( $this->getHeaders() );
			$request->setUri( $this->getEndpoint('authorize') );
			$request->setMethod(\Zend\Http\Request::METHOD_POST);

			$response = $this->_client->send($request);

			if ( is_string($response) ) {
				$response = json_decode($response, true);
			}

			$payment
			->setTransactionId($response['referenceId'])
			->setCcApproval($response['authorization']['approvalNumber'])
			->setIsTransactionClosed(0);

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
		//throw new \Magento\Framework\Validator\Exception(__('Inside Stripe, throwing donuts :]'));

		/** @var \Magento\Sales\Model\Order $order */
		$order = $payment->getOrder();

		/** @var \Magento\Sales\Model\Order\Address $billing */
		$billing = $order->getBillingAddress();

		$exp_month = sprintf('%02d',$payment->getCcExpMonth());

		try {
			$requestData = array(
				'amount' => array(
					'value' => $amount,
					'currency' => $order->getCurrencyCode(),
				),
				'source' => array(
					'card' => array(
						"accountNumber" => $payment->getCcNumber(),
						"expiration" => "{$exp_month}/{$payment->getCcExpYear()}",
						"nameOnAccount" => $billing->getName(),
						"securityCode" => $payment->getCcCid(),
				), // card information,
					'billingAddress' => array(
						'street' => $billing->getStreetLine(1),
						'city'   => $billing->getCity(),
						'state'  => $billing->getRegionCode(),
						'postal' => $billing->getPostcode(),
						'country' => $billing->getCountryId(),
						// To get full localized country name, use this instead:
						// 'address_country'   => $this->_countryFactory->create()->loadByCode($billing->getCountryId())->getName(),
					),
					'approvalNumber' => $payment->getCcApproval(),
				),
				'identification' => $order->getIncrementId(),
				'email' => $order->getCustomerEmail(),
				'metadata' => array(
					array(
						'Description' => sprintf('#%s, %s', $order->getIncrementId(), $order->getCustomerEmail())
					)
				)
			);

			// Make call to paperless
			$request = new \Zend\Http\Request();
			$request->setHeaders( $this->getHeaders() );
			$request->setUri( $this->getEndpoint('capture') );
			$request->setMethod(\Zend\Http\Request::METHOD_POST);

			$response = $this->_client->send($request);

			if ( is_string($response) ) {
				$response = json_decode($response);
			}

			$payment
			->setTransactionId($response->referenceId)
			->setIsTransactionClosed(0);

		} catch (\Exception $e) {
			$this->debugData(['request' => $requestData, 'exception' => $e->getMessage()]);
			$this->_logger->error(__('Payment capturing error.'));
			throw new \Magento\Framework\Validator\Exception(__('Payment capturing error.'));
		}

		return $this;
	}

	private function getApiKey() {
		return '926fde32e9cf47c7862c7e0a5409'; // $this->_apiKey;
	}
	private function getHeaders() {
		return [
			'Content-Type' => 'application/json',
			'TerminalKey'  => $this->getApiKey(),
			'TestFlag'     => 'true' // remove when done testing
		];
	}

	private function getEndpoint( $type = '' ) {
		return "https://api.paperlesstrans.com/transactions/{$type}";
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

		try {
			$requestData = array(
				'amount' => array(
					'value' => $amount,
					'currency' => $order->getCurrencyCode(),
				),
				'source' => array(
					'approvalNumber' => $payment->getCcApproval()
				)
			);
		} catch (\Exception $e) {
			$this->debugData(['transaction_id' => $transactionId, 'exception' => $e->getMessage()]);
			$this->_logger->error(__('Payment refunding error.'));
			throw new \Magento\Framework\Validator\Exception(__('Payment refunding error.'));
		}

		// Make call to paperless
		$request = new \Zend\Http\Request();
		$request->setHeaders( $this->getHeaders() );
		$request->setUri( $this->getEndpoint('refund') );
		$request->setMethod(\Zend\Http\Request::METHOD_POST);

		$response = $this->_client->send($request);

		if ( is_string($response) ) {
			$response = json_decode($response);
		}

		$payment
		->setTransactionId($transactionId . '-' . \Magento\Sales\Model\Order\Payment\Transaction::TYPE_REFUND)
		->setParentTransactionId($transactionId)
		->setIsTransactionClosed(1)
		->setShouldCloseParentTransaction(1);

		return $this;
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
	public function canUseForCurrency($currencyCode)
	{
		if (!in_array($currencyCode, $this->_supportedCurrencyCodes)) {
			return false;
		}
		return true;
	}
}
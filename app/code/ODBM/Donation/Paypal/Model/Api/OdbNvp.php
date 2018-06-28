<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

// @codingStandardsIgnoreFile

namespace ODBM\Donation\Paypal\Model\Api;

use Magento\Payment\Model\Cart;
use Magento\Payment\Model\Method\Logger;

/**
 * ODB's implementation of Nvp
 *
 * Extends Paypal model to allow for adding custom data
 *
 * @method string getToken()
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class OdbNvp extends \Magento\Paypal\Model\Api\Nvp
{
	/**
	 * Paypal methods definition
	 */
	const DO_DIRECT_PAYMENT = 'DoDirectPayment';
	const DO_CAPTURE = 'DoCapture';
	const DO_AUTHORIZATION = 'DoAuthorization';
	const DO_VOID = 'DoVoid';
	const REFUND_TRANSACTION = 'RefundTransaction';
	const SET_EXPRESS_CHECKOUT = 'SetExpressCheckout';
	const GET_EXPRESS_CHECKOUT_DETAILS = 'GetExpressCheckoutDetails';
	const DO_EXPRESS_CHECKOUT_PAYMENT = 'DoExpressCheckoutPayment';
	const CALLBACK_RESPONSE = 'CallbackResponse';

	const DONATION_TYPE = 'ODBDonations';

	/**
	 * Paypal ManagePendingTransactionStatus actions
	 */
	const PENDING_TRANSACTION_ACCEPT = 'Accept';
	const PENDING_TRANSACTION_DENY = 'Deny';

	/**
	 * Global public interface map
	 *
	 * @var array
	 */
	protected $_globalMap = [
		// each call
		'VERSION' => 'version',
		'USER' => 'api_username',
		'PWD' => 'api_password',
		'SIGNATURE' => 'api_signature',
		'BUTTONSOURCE' => 'build_notation_code',
		// for Unilateral payments
		'SUBJECT' => 'business_account',

		// commands
		'PAYMENTACTION' => 'payment_action',
		'RETURNURL' => 'return_url',
		'CANCELURL' => 'cancel_url',
		'INVNUM' => 'inv_num',
		'TOKEN' => 'token',
		'CORRELATIONID' => 'correlation_id',
		'SOLUTIONTYPE' => 'solution_type',
		'GIROPAYCANCELURL' => 'giropay_cancel_url',
		'GIROPAYSUCCESSURL' => 'giropay_success_url',
		'BANKTXNPENDINGURL' => 'giropay_bank_txn_pending_url',
		'IPADDRESS' => 'ip_address',
		'NOTIFYURL' => 'notify_url',
		'RETURNFMFDETAILS' => 'fraud_management_filters_enabled',
		'NOTE' => 'note',
		'REFUNDTYPE' => 'refund_type',
		'ACTION' => 'action',
		'REDIRECTREQUIRED' => 'redirect_required',
		'SUCCESSPAGEREDIRECTREQUESTED' => 'redirect_requested',
		'REQBILLINGADDRESS' => 'require_billing_address',
		// style settings
		'PAGESTYLE' => 'page_style',
		'HDRIMG' => 'hdrimg',
		'HDRBORDERCOLOR' => 'hdrbordercolor',
		'HDRBACKCOLOR' => 'hdrbackcolor',
		'PAYFLOWCOLOR' => 'payflowcolor',
		'LOCALECODE' => 'locale_code',
		'PAL' => 'pal',
		'USERSELECTEDFUNDINGSOURCE' => 'funding_source',

		// transaction info
		'TRANSACTIONID' => 'transaction_id',
		'AUTHORIZATIONID' => 'authorization_id',
		'REFUNDTRANSACTIONID' => 'refund_transaction_id',
		'COMPLETETYPE' => 'complete_type',
		'AMT' => 'amount',
		'ITEMAMT' => 'subtotal_amount',
		'GROSSREFUNDAMT' => 'refunded_amount', // possible mistake, check with API reference

		// payment/billing info
		'CURRENCYCODE' => 'currency_code',
		'PAYMENTSTATUS' => 'payment_status',
		'PENDINGREASON' => 'pending_reason',
		'PROTECTIONELIGIBILITY' => 'protection_eligibility',
		'PAYERID' => 'payer_id',
		'PAYERSTATUS' => 'payer_status',
		'ADDRESSID' => 'address_id',
		'ADDRESSSTATUS' => 'address_status',
		'EMAIL' => 'email',

		// backwards compatibility
		'FIRSTNAME' => 'firstname',
		'LASTNAME' => 'lastname',

		// shipping rate
		'SHIPPINGOPTIONNAME' => 'shipping_rate_code',
		'NOSHIPPING' => '2',// 'suppress_shipping',

		// paypal direct credit card information
		'CREDITCARDTYPE' => 'credit_card_type',
		'ACCT' => 'credit_card_number',
		'EXPDATE' => 'credit_card_expiration_date',
		'CVV2' => 'credit_card_cvv2',
		'STARTDATE' => 'maestro_solo_issue_date',
		'ISSUENUMBER' => 'maestro_solo_issue_number',
		'CVV2MATCH' => 'cvv2_check_result',
		'AVSCODE' => 'avs_result',

		'SHIPPINGAMT' => 'shipping_amount',
		'TAXAMT' => 'tax_amount',
		'INITAMT' => 'init_amount',
		'STATUS' => 'status',

		//Next two fields are used for Brazil only
		'TAXID' => 'buyer_tax_id',
		'TAXIDTYPE' => 'buyer_tax_id_type',

		'BILLINGAGREEMENTID' => 'billing_agreement_id',
		'REFERENCEID' => 'reference_id',
		'BILLINGAGREEMENTSTATUS' => 'billing_agreement_status',
		'BILLINGTYPE' => 'billing_type',
		'SREET' => 'street',
		'CITY' => 'city',
		'STATE' => 'state',
		'COUNTRYCODE' => 'countrycode',
		'ZIP' => 'zip',
		'PAYERBUSINESS' => 'payer_business',
	];

	/**
	 * SetExpressCheckout request map
	 *
	 * @var string[]
	 */
	protected $_setExpressCheckoutRequest = [
		'PAYMENTACTION',
		'AMT',
		'CURRENCYCODE',
		'RETURNURL',
		'CANCELURL',
		'INVNUM',
		'SOLUTIONTYPE',
		'NOSHIPPING',
		'GIROPAYCANCELURL',
		'GIROPAYSUCCESSURL',
		'BANKTXNPENDINGURL',
		'PAGESTYLE',
		'HDRIMG',
		'HDRBORDERCOLOR',
		'HDRBACKCOLOR',
		'PAYFLOWCOLOR',
		'LOCALECODE',
		'BILLINGTYPE',
		'SUBJECT',
		'ITEMAMT',
		'SHIPPINGAMT',
		'TAXAMT',
		'REQBILLINGADDRESS',
		'USERSELECTEDFUNDINGSOURCE',
		'CUSTOM'
	];

	protected $custom;
	protected $cart;
	protected $referer;
	protected $is_recurring;

	/**
	 * @param \Magento\Customer\Helper\Address $customerAddress
	 * @param \Psr\Log\LoggerInterface $logger
	 * @param Logger $customLogger
	 * @param \Magento\Framework\Locale\ResolverInterface $localeResolver
	 * @param \Magento\Directory\Model\RegionFactory $regionFactory
	 * @param \Magento\Directory\Model\CountryFactory $countryFactory
	 * @param ProcessableExceptionFactory $processableExceptionFactory
	 * @param \Magento\Framework\Exception\LocalizedExceptionFactory $frameworkExceptionFactory
	 * @param \Magento\Framework\HTTP\Adapter\CurlFactory $curlFactory
	 * @param array $data
	 * @SuppressWarnings(PHPMD.ExcessiveParameterList)
	 */
	public function __construct(
		\Magento\Customer\Helper\Address $customerAddress,
		\Psr\Log\LoggerInterface $logger,
		Logger $customLogger,
		\Magento\Framework\Locale\ResolverInterface $localeResolver,
		\Magento\Directory\Model\RegionFactory $regionFactory,
		\Magento\Directory\Model\CountryFactory $countryFactory,
		\Magento\Paypal\Model\Api\ProcessableExceptionFactory $processableExceptionFactory,
		\Magento\Framework\Exception\LocalizedExceptionFactory $frameworkExceptionFactory,
		\Magento\Framework\HTTP\Adapter\CurlFactory $curlFactory,
		\Magento\Checkout\Model\Cart $cart,
		array $data = []
	) {
		parent::__construct($customerAddress, $logger, $customLogger, $localeResolver, $regionFactory, $countryFactory, $processableExceptionFactory,  $frameworkExceptionFactory, $curlFactory, $data);

		$this->cart = $cart;

		// Initalize properties
		$this->resetValues();
	}

	/**
	* Set custom data to pass to automation
	*/
	protected function setCustomData() {
		$referer = $this->getItemReferer();

		$paypal_ministry = $this->getMinistry();
		$recurring_type = $this->isItemRecurring() ? 'monthly' : 'onetime';

		$custom_field = '~' . self::DONATION_TYPE . "||~{$recurring_type}|{$referer}|{$paypal_ministry}";

		$this->custom = $custom_field;
	}

	/**
	* Get custom data pass to automation
	*
	* @return string  $this->custom  Custom field to pass on.
	*/
	protected function getCustomData() {
		if ( empty($this->custom) ) {
			$this->setCustomData();
		}

		return $this->custom;
	}

	/**
	* Get referer that was set in the quote
	*
	* @todo   Validate URL, ensure data is how ODB automation expects it.
	* @return string  $this->referer  Refererer field in quote.
	*/
	protected function getItemReferer() {
		if ( empty($this->referer) ) {
			$this->referer = $this->getOption( '_referer' );
		}

		return $this->referer;
	}

	/**
	* Get ministry that this is associated with
	*
	* @return string  $this->ministry  Ministry name.
	*/
	protected function getMinistry() {
		if ( empty($this->ministry) ) {
			$ministry = $this->getOption( '_ministry' );

			$this->ministry = !empty($ministry) ? $ministry : 'odb';
		}

		return $this->ministry;
	}

	/**
	* Get quote option by key.
	*
	* If no option supplied, return all options
	*
	* NOTE: This is only designed to handle one product in the cart,
	*       as seen by how it returns in the foreach.
	*
	* @param   string          $option         Optional. Key of option to return.
	* @return  string|boolean  $option_value   Value of option. If no cart items found, then false.
	*                                          If option is empty, empty string returned
	*/
	protected function getOption( $option = '' ) {
		if ( $items = $this->cart->getItems() ) {
			foreach( $items as $item ) {
				$buyRequest = $item->getBuyRequest();

				if ( $buyRequest && is_object( $buyRequest ) ) {
					$options = $item->getBuyRequest()->_data;

					if ( empty($option) ) {
						return $options;
					} else {
						return $options[ $option ] ?? '';
					}
				}
			}
		}

		return false;
	}

	/**
	* Reset properties, so they have to be called again if express checkout
	* is called more than once, to ensure that we have the best data
	*/
	protected function resetValues() {
		$this->referer = '';
		$this->is_recurring = NULL;
		$this->custom = '';
	}

	/**
	* Check if item is listed as recurring from quote
	*
	* @return boolean $is_recurring  Whether order in cart is set to be recurring.
	*/
	protected function isItemRecurring() {
		if ( is_null( $this->is_recurring ) ) {
			$is_recurring = $this->getOption( '_recurring' );
			$this->is_recurring = !empty( $is_recurring ) && ($is_recurring !== 'false');
		}

		return $this->is_recurring;
	}

	/**
	 * SetExpressCheckout call
	 *
	 * TODO: put together style and giropay settings
	 *
	 * @return void
	 * @link https://cms.paypal.com/us/cgi-bin/?&cmd=_render-content&content_ID=developer/e_howto_api_nvp_r_SetExpressCheckout
	 */
	public function callSetExpressCheckout() {
		$this->_prepareExpressCheckoutCallRequest($this->_setExpressCheckoutRequest);
		$request = $this->_exportToRequest($this->_setExpressCheckoutRequest);
		$this->_exportLineItems($request);

		// import/suppress shipping address, if any
		$options = $this->getShippingOptions();
		if ($this->getAddress()) {
			$request = $this->_importAddresses($request);
			$request['ADDROVERRIDE'] = 1;
		} elseif ($options && count($options) <= 10) {
			// doesn't support more than 10 shipping options
			$request['CALLBACK'] = $this->getShippingOptionsCallbackUrl();
			$request['CALLBACKTIMEOUT'] = 6;

			// max value
			$request['MAXAMT'] = $request['AMT'] + 999.00;

			// it is impossible to calculate max amount
			$this->_exportShippingOptions($request);
		}

		$request['CUSTOM'] = $this->getCustomData();
		$request['NOSHIPPING'] = 2;
		// $request['ADDROVERRIDE'] = 0; // Use this to allow user to override address with Paypal address

		$response = $this->call(self::SET_EXPRESS_CHECKOUT, $request);
		$this->_importFromResponse($this->_setExpressCheckoutResponse, $response);

		$this->resetValues();
	}
}

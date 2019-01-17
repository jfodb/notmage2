<?php
/**
* Created by PhpStorm.
* User: ppostma
* Date: 7/3/18
* Time: 1:17 PM
*/

namespace ODBM\Paperless\Gateway\Request;

use Magento\Payment\Gateway\ConfigInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;

class PaperlessRequest implements BuilderInterface
{
	
	//protected $config;
	protected $customfields = array();
	protected $_encryptor;
	protected $_subjectReader;
	protected $_rawsource;
	protected $_internalpost;
	protected $_clientFactory;
	
	/**
	* @param ConfigInterface $config
	*/
	public function __construct(
		\Magento\Framework\App\Config\ScopeConfigInterface $config,
		\Magento\Framework\Encryption\EncryptorInterface $encryptor,
		\Magento\Customer\Model\Session $customerSession,
		\Magento\Framework\HTTP\ZendClientFactory $clientFactory
		) {
			$this->config = $config;
			$this->_encryptor = $encryptor;
			$this->_clientFactory = $clientFactory;
		}
		
		public function is_tokenized() {
			//$user_data->payment->token;
			
			return false;
		}
		
		public function is_recurring($paymentDO) {
			if (!isset($paymentDO) || !$paymentDO instanceof PaymentDataObjectInterface) {
				throw new \InvalidArgumentException('Payment data object should be provided');
			}
			
			$payment = $paymentDO->getPayment();
			$order = $payment->getOrder();
			
			$items = $order->getAllItems();
			$order_item = $items[0];
			
			$is_recurring = false;
			
			foreach ( $items as $order_item ) {
				// Get stored product info
				$product_options = $order_item->getProductOptionByCode('info_buyRequest');
				$is_recurring = $product_options['_recurring'] ?? false;
				
				$is_recurring = !empty( $is_recurring ) && ($is_recurring !== 'false');
				
				if ( $is_recurring ) {
					break;
				}
			}
			
			return $is_recurring;
		}
		
		
		public function build(array $buildSubject)
		{
			$payment = /*(\Magento\Payment\Gateway\Data\PaymentDataObject::class)*/ $buildSubject['payment'];
			
			$order = $payment->getOrder();
			$payment = $payment->getPayment();
			$this->customfields = array();
			$storid = $order->getStoreId();
			
			//yeah, we're not getting the data
			if(empty($_POST) && empty($payment->getCcNumberEnc())){
				ob_start();
				$STDIN = fopen('php://input', 'r');
				while($f = fgets($STDIN)){
					echo $f;
				}
				$this->_rawsource = ob_get_clean(); /**/
				fclose($STDIN);
				
				if(strlen($this->_rawsource) > 5 && $this->_rawsource[0] == '{'){
					$this->_internalpost = json_decode($this->_rawsource, true);
				}
				
				if(!empty($this->_internalpost)){
					if(!empty($this->_internalpost['paymentMethod']) && !empty($this->_internalpost['paymentMethod']['additional_data'])) {
						if(!empty($this->_internalpost['paymentMethod']['additional_data']['cc_number'])) {
							$payment->setCcLast4(substr($this->_internalpost['paymentMethod']['additional_data']['cc_number'], -4));
							$payment->setCcNumberEnc(
								$this->_encryptor->encrypt(	$this->_internalpost['paymentMethod']['additional_data']['cc_number'] )
							);
							unset($this->_internalpost['paymentMethod']['additional_data']['cc_number']);
						}
						
						if(!empty($this->_internalpost['paymentMethod']['additional_data']['cc_cid'])) {
							$payment->setCcCid( $this->_encryptor->encrypt($this->_internalpost['paymentMethod']['additional_data']['cc_cid']));
							unset($this->_internalpost['paymentMethod']['additional_data']['cc_cid']);
						}
						
						if(!empty($this->_internalpost['paymentMethod']['additional_data']['cc_type'])) {
							$payment->setCcType($this->_internalpost['paymentMethod']['additional_data']['cc_type']);
							unset($this->_internalpost['paymentMethod']['additional_data']['cc_type']);
						}
						
						if(!empty($this->_internalpost['paymentMethod']['additional_data']['cc_exp_year'])) {
							$payment->setCcExpYear($this->_internalpost['paymentMethod']['additional_data']['cc_exp_year']);
							unset($this->_internalpost['paymentMethod']['additional_data']['cc_exp_year']);
						}
						
						if(!empty($this->_internalpost['paymentMethod']['additional_data']['cc_exp_month'])) {
							$payment->setCcExpMonth($this->_internalpost['paymentMethod']['additional_data']['cc_exp_month']);
							unset($this->_internalpost['paymentMethod']['additional_data']['cc_exp_month']);
						}
					}
					if(!empty($this->_internalpost['billingAddress']))
					if(!empty($this->_internalpost['billingAddress']['firstname']) && !empty($this->_internalpost['billingAddress']['lastname']))
					$payment->setCcOwner($this->_internalpost['billingAddress']['firstname'] . ' ' . $this->_internalpost['billingAddress']['lastname']);
					
					unset($this->_rawsource);
				}
			}
			
			
			$merchant_gateway_key_enc = $this->config->getValue('payment/odbm_paperless/merchant_gateway_key',\Magento\Store\Model\ScopeInterface::SCOPE_STORE);
			
			$merchant_gateway_key = $this->_encryptor->decrypt($merchant_gateway_key_enc);
			
			// $mode = $this->config->getValue('payment/odbm_paperless/sandbox', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
			
			// if(!empty($mode) && ( $mode == 'Production' ||  )) {
			// 	//$terminal = $this->config->getValue('MerchantID', $order->getStoreId());
			// 	$test = 'False';
			// } else {
			// 	//$terminal = $this->config->getValue('test_MerchantID', $order->getStoreId());
			// 	$test = 'True';
			// }
			
			$debug = $this->config->getValue('payment/odbm_paperless/debug', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
		
			$d = $_SERVER['HTTP_HOST'];
			
			//$auto_type = $this->config->getValue('jobtype', $order->getStoreId());
			$tmp = 'psuedo_mpxdownload/runtime/motivation_code/jobtype/'.$d;
			$auto_type = $this->config->getValue('psuedo_mpxdownload/runtime/jobtype/'.$d, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
			if(empty($auto_type))
			$auto_type = $this->config->getValue($tmp = 'psuedo_mpxdownload/runtime/jobtype/store_'.$storid, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
			
			if(!empty($auto_type)) {
				$this->customfields[] = [
					'key' => 1,
					'value' => $auto_type
				];
			}
			
			if(!empty($order->getCustomerId())){
				$this->customfields[] = [
					'key' => 2,
					'value' => $order->getCustomerId()
				];
			} else {
				$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
				$customerSession = $objectManager->get('Magento\Customer\Model\Session');
				
				if($customerSession->isLoggedIn()) {
					// customer login action
					$this->customfields[] = [
						'key' => 2,
						'value' => $customerSession->getCustomer()->getId()
					];
				}
			}
			
			$this->customfields[] = [
				'key' => 4,
				'value' => $order->getOrderIncrementId()
			];
			
			$fields =  [ "req" => array(
				'Token' => array(
					'TerminalKey' => $merchant_gateway_key
				)				
			)];

			// Only set test flag if it is being used
			if ( !empty($debug) && $debug != '0' ) {
				$fields['req']['TestMode'] = true;
			}
				
			return $fields;
		}
		/**
		 * Create new profile for recurring transactions and saving to vault
		 *
		 * @param  array $buildSubject
		 * @return string $profile_token Token generated by paperless and used by our processing systems
		 */
		public function getProfileInformation( $buildSubject, $base_req ) {
			$profile_token = false;

			// Build data
			$body = $this->getProfileRequestBody( $buildSubject, $base_req );

			// Call Paperless REST API, /profile/create
			$response = $this->callPaperlessAPI( $body );

			// Ensure 200 response
			if ( (int)$response['status'] === 200 ) {
				if ( !empty( $response['profile']['profileNumber'] ) ) {
					$profile_token = $response['profile']['profileNumber'];
				}
			}

			// Do we want to return meaningful data here if not 200? Or throw error
			return $profile_token; 
		}

		/**
		 * Call API to return with provided parameters
		 *
		 * @todo Integrate with TransferFactory in used in this module
		 * @return mixed $response
		 */
		public function callPaperlessAPI( array $request_body ) {
			$request_details = $request_body['req'];
			unset($request_body['req']);
	
			$domain = /*from configs*/ 'https://api.paperlesstrans.com';
			$url = $domain . $request_details['uri'];
	
			$headers = [
				'Content-Type' => 'application/json',
				'TerminalKey'  => $request_details['Token']['TerminalKey']
			];
	
			if( !empty( $request_details['TestMode'] ) ) {
				$headers['TestFlag'] = 'true';
			}

			$result = [];

			/** @var ZendClient $client */
			$client = $this->_clientFactory->create();
	
			$client->setMethod(\Zend\Http\Request::METHOD_POST);
			$client->setRawData(json_encode($request_body), 'application/json');
	
			$client->setHeaders($headers);
			$client->setUri($url);

			try {

				$response = $client->request();	
				$response_body = $response->getBody();
	
				if(is_array($response_body))
					$result = $response_body;
				else
					$result = json_decode($response_body, true);
	
				$result['status'] = $response->getStatus();

				return $result;
	
			} catch (\Zend_Http_Client_Exception $e) {
				throw new \Magento\Payment\Gateway\Http\ClientException(
					__($e->getMessage())
				);
			} catch (\Magento\Payment\Gateway\Http\ConverterException $e) {
				throw $e;
			}
		}

		/**
		 * Get Request Body to send to Paperless API
		 *
		 * @param array $buildSubject
		 * @return array $body
		 */
		public function getProfileRequestBody( array $buildSubject, array $base_req ) {
			$base_req['req']['uri'] = '/profiles/create';
	
			$paymentDO = $buildSubject['payment'];

			$payment = $paymentDO->getPayment();

			$order = $payment->getOrder();
			$address = $order->getBillingAddress();
	
			$cardname = $payment->getCcOwner();
			if( empty($cardname) )
				$cardname = $address->getFirstname() . ' ' . $address->getLastname();
			
			$civ = $payment->getCcCid();
			if(empty($civ))
				$civ = $payment->getCcSecureVerify();
			if(!empty($civ) && strlen($civ) > 4)
				$civ = $this->_encryptor->decrypt( $civ );
	
			$expmonth = $payment->getCcExpMonth();
			if( strlen($expmonth) === 1 ) 
				$expmonth = sprintf('%\'.02d', $expmonth);
	
			$expyear = $payment->getCcExpYear();
			if( strlen($payment->getCcExpYear()) === 2 )
				$expyear = '20'.$expyear;
	
			/**
			 * @todo refactor to allow for both CC Number and token passed in
			 */
			$addition['source'] = [
				'card' => [
					'accountNumber' => $this->_encryptor->decrypt( $payment->getCcNumberEnc() ),
					'expiration' => $expmonth . '/' . $expyear,
					'nameOnAccount' => $cardname,
					'securityCode' => $civ,
					'billingAddress'=> [
						'street' => $address->getStreetLine1(),
						'city' => $address->getCity(),
						'state' => $address->getRegionCode(),
						'postal' => $address->getPostcode(),
						'country' => $address->getCountryId()
					]
				],
				'email' => $address->getEmail()
			];

			return array_merge( $base_req, $addition );
		}

	}
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
use Magento\Framework\Phrase;

class PaperlessRequest implements BuilderInterface
{
	
	//protected $config;
	protected $customfields = array();
	protected $_encryptor;
	protected $_subjectReader;
	protected $_rawsource;
	protected $_internalpost;
	protected $_config;
	protected $_logger;
	protected $_session;

	/**
	* @param ConfigInterface $config
	*/
	public function __construct(
		\Magento\Framework\App\Config\ScopeConfigInterface $config,
		\Magento\Framework\Encryption\EncryptorInterface $encryptor,
		\Psr\Log\LoggerInterface $logger,
		\Magento\Customer\Model\Session $sessionManager
		) {
			$this->_config = $config;
			$this->_encryptor = $encryptor;
			$this->_logger = $logger;
			$this->_session = $sessionManager;
			
			if(!isset($GLOBALS['_FLAGS'])){
				$GLOBALS['_FLAGS'] = array('payment'=>array('method' => 'paperless'));
			}
			if(!isset($GLOBALS['_FLAGS']['payment']))
				$GLOBALS['_FLAGS']['payment'] = array('method' => 'paperless');

		}
		
	public function is_profiled($payment) {
		return $payment->getCcStatusDescription();

		//return false;
	}

	public function is_tokenized($payment) {
		$is_tokenized = false;
		$token = $payment->getAdditionalInformation('cc_token');

		if ( !empty($token) && $token !== 'false' ) {
			$is_tokenized = json_decode($token);
		}
		return $is_tokenized;
	}
		
		public function is_recurring($paymentDO) {
			if (!isset($paymentDO) || !$paymentDO instanceof PaymentDataObjectInterface) {
				$this->_logger->critical("Paperless PaperlessRequest experienced an invalid argument");
				throw new \InvalidArgumentException('Payment data object should be provided', 500);
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
			
			
			$merchant_gateway_key_enc = $this->_config->getValue('payment/odbm_paperless/merchant_gateway_key',\Magento\Store\Model\ScopeInterface::SCOPE_STORE);
			$merchant_gateway_key = $this->_encryptor->decrypt($merchant_gateway_key_enc);
			
			// $mode = $this->_config->getValue('payment/odbm_paperless/sandbox', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
			
			// if(!empty($mode) && ( $mode == 'Production' ||  )) {
			// 	//$terminal = $this->_config->getValue('MerchantID', $order->getStoreId());
			// 	$test = 'False';
			// } else {
			// 	//$terminal = $this->_config->getValue('test_MerchantID', $order->getStoreId());
			// 	$test = 'True';
			// }
			
			$debug = $this->_config->getValue('payment/odbm_paperless/debug', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
		
			$d = $_SERVER['HTTP_HOST'];
			
			//$auto_type = $this->_config->getValue('jobtype', $order->getStoreId());
			$tmp = 'psuedo_mpxdownload/runtime/motivation_code/jobtype/'.$d;
			$auto_type = $this->_config->getValue('psuedo_mpxdownload/runtime/jobtype/'.$d, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
			if(empty($auto_type))
			$auto_type = $this->_config->getValue($tmp = 'psuedo_mpxdownload/runtime/jobtype/store_'.$storid, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
			
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
				$fields['req']['TestMode'] = 'true';
			}
				
			return $fields;
		}
			
		public function getProfileInformation( $buildSubject ) {
			/**
			* Implementation of this will be completed in @link https://ourdailybread.atlassian.net/browse/DT-94
			*/
			$this->_logger->critical('Paperless getProfileInformation was called');
			throw new \Exception('PaperlessRequest::getProfileInformation() not implemented', 501);
		}
		public function improptu_profile($paymentDO) {
			require_once (__DIR__.'/ProfileRequest.php');


			$profileData = new ProfileRequest($this->_config, $this->_encryptor, $this->_logger, $this->_session);

			$data = $profileData->build(['payment' => $paymentDO]);

			if (isset($data['cardhash'])) {
				$cardhash = $data['cardhash'];
				unset($data['cardhash']);
			} else {
				$cardhash = false;
			}

			$cacheresult = false;
			if ($cardhash) {
				$transactions = $this->_session->getTransactionList();
				if (isset($transactions['profile'], $transactions['profile'][$cardhash]))
					$cacheresult = $transactions['profile'][$cardhash];
			}

			if($cacheresult && isset($cacheresult['profile']['profileNumber'])){
				$resp = $cacheresult;
				//no expiration time on profile numbers. If we have it, use it.
				$payment = $paymentDO->getPayment();
			} else {

			$request_details = $data['req'];
			unset($data['req']);

			$config_value = $this->_config->getValue('payment/odbm_paperless/payment_domain', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
			if(!empty($config_value))
				$domain = $config_value;
			else
				$domain = 'https://api.paperlesstrans.com';
			$url = $domain . $request_details['uri'];

			$jsondata = json_encode($data);
			$headrs = [
				'Content-Type: application/json',
				'Content-Length: ' . strlen($jsondata),
				'TerminalKey: '  . $request_details['Token']['TerminalKey'],

			];

			if( !empty( $request_details['TestMode'] ) ) {
				$headrs[] = 'TestFlag: true';
			}


			$selfconnect = curl_init($url);
			curl_setopt_array($selfconnect, [
				CURLOPT_POST            =>      true,
				CURLOPT_POSTFIELDS      =>      $jsondata,
				CURLOPT_CUSTOMREQUEST   =>      'POST',
				CURLOPT_HTTPHEADER      =>      $headrs,
				CURLOPT_RETURNTRANSFER  =>      true,
				CURLOPT_CONNECTTIMEOUT  =>      10,
				CURLOPT_TIMEOUT         =>      40
			]);


			$response = curl_exec($selfconnect);
			$responseInfo = curl_getinfo($selfconnect);
			curl_close($selfconnect);


			if($responseInfo['http_code'] == 0 ){
				$this->_logger->critical("PaperlessRequest was unable to connect to Paperless");
				//HTTP 502, unable to reach upstream server
				throw new \Magento\Payment\Gateway\Http\ClientException(new Phrase("Failed to connect to card processor"), null, 502);
			}

			$resp = json_decode($response, true);
			$payment = $paymentDO->getPayment();


			if($responseInfo['http_code'] != 200 || empty($resp) || empty($resp['profile']) || empty($resp['profile']['profileNumber'])){
				$payment->setEcheckAccountType($response);  //cc_debug_response_serialized, but its only 32 chars!!
				$this->_logger->critical("Paperless request received ".$responseInfo['http_code']);
				throw new \Magento\Payment\Gateway\Http\ClientException(new Phrase("Transaction declined"), null, 200);
			}


			//from session above
			if($cardhash) {
				if (empty($transactions))
					$transactions = array();
				if (empty($transactions['profile']))
					$transactions['profile'] = array();

				$transactions['profile'][$cardhash] = $resp;
				$this->_session->setTransactionList($transactions);
			}
			}
			$payment->setCcStatusDescription($resp['profile']['profileNumber']);
			if(!empty($resp['profile']['accountDescription']))
				$payment->setCcSsStartYear($resp['profile']['accountDescription']);
			if(!empty($resp['referenceId']))
				$payment->setCcSsStartMonth($resp['referenceId']);

			
		}

		protected function cardHash($cardnum, $expstr, $cvv){
			return sha1( 'QwErTyUiOp1@3$5^7*9)' . $cardnum . $expstr . $cvv . 'qWeRtYuIoP!2#4%6&8(0');
		}
	}
		
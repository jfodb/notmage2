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
	
	/**
	 * @param ConfigInterface $config
	 */
	public function __construct(
		\Magento\Framework\App\Config\ScopeConfigInterface $config,
		\Magento\Framework\Encryption\EncryptorInterface $encryptor,
		\Magento\Customer\Model\Session $customerSession
	) {
		$this->config = $config;
		$this->_encryptor = $encryptor;
		
		
		
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
		
		
		
		$mode = $this->config->getValue('payment/odbm_paperless/sandbox', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);

		if(!empty($mode) && $mode == 'Production') {
			//$terminal = $this->config->getValue('MerchantID', $order->getStoreId());
			$test = 'False';
		} else {
			//$terminal = $this->config->getValue('test_MerchantID', $order->getStoreId());
			$test = 'True';
		}


		$d = $_SERVER['HTTP_HOST'];
		
		//$auto_type = $this->config->getValue('jobtype', $order->getStoreId());
		$tmp = 'psuedo_mpxdownload/runtime/motivation_code/jobtype/'.$d;
		$auto_type = $this->config->getValue('psuedo_mpxdownload/runtime/jobtype/'.$d, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
		if(empty($auto_type))
			$auto_type = $this->config->getValue($tmp = 'psuedo_mpxdownload/runtime/jobtype/store_'.$storid, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);


		if(!empty($auto_type))
			$this->customfields[] = [1=>$auto_type];

		if(!empty($order->getCustomerId())){
			$this->customfields[] = [2 => $order->getCustomerId];
		} else {
			$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
			$customerSession = $objectManager->get('Magento\Customer\Model\Session');
			if($customerSession->isLoggedIn()) {
				// customer login action
				$this->customfields[] = [2 => $customerSession->getCustomer()->getId()];
			}
			
			
		}

		$this->customfields[] = [4=>$order->getOrderIncrementId()];

		$fields =  [ "req" => array(
			'Token' => array(
				'TerminalKey' => $merchant_gateway_key
			),

			'TestMode' => $test,
		)];

		return $fields;
	}

	public function getProfileInformation( $buildSubject ) {
		/**
		* Implementation of this will be completed in @link https://ourdailybread.atlassian.net/browse/DT-94
		*/
		 throw new Exception('PaperlessRequest::getProfileInformation() not implemented');
	}
}

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

	protected $config;
	protected $customfields = array();
	/**
	 * @param ConfigInterface $config
	 */
	public function __construct(
		ConfigInterface $config
	) {
		$this->config = $config;
	}
	
	public function is_tokenized() {
		//$user_data->payment->token;
		
		return false;
	}

	public function build(array $buildSubject)
	{
		$payment = $buildSubject['payment'];
		$order = $payment->getOrder();
		$this->customfields = array();
		
		$mode = $this->config->getValue('payment_mode', $order->getStoreId());
		

		if($mode == 'Production') {
			//$terminal = $this->config->getValue('MerchantID', $order->getStoreId());
			$key = $this->config->getValue('terminalkey', $order->getStoreId());
			$test = 'False';
		} else {
			//$terminal = $this->config->getValue('test_MerchantID', $order->getStoreId());
			$key = $this->config->getValue('test_TerminalID', $order->getStoreId());
			$test = 'True';
		}

		
		$d = $_SERVER['HTTP_HOST'];
		$auto_type = Mage::getStoreConfig("mpx/jobtype/$d");
		$auto_type = $this->config->getValue('jobtype', $order->getStoreId());
		
		if(!empty($auto_type))
			$this->customfields[] = [1=>$auto_type];
		
		if(!empty($order->getCustomerId)){
			$this->customfields[] = [2=>$order->getCustomerId];
		} else if(Mage::getSingleton('customer/session')->isLoggedIn()){
			$this->customfields[] = [2=>
				Mage::getSingleton('customer/session')->getCustomer()->getId()];
		}
		
		$this->customfields[] = [4=>$order->getIncrementId()];

		
		
		return
		[   "req" => array(
				'Token' => array(
					'TerminalKey' => $key
				), 

			'TestMode' => $test,
			)
		];
	}
}

<?php
/**
 * Created by PhpStorm.
 * User: ppostma
 * Date: 2019-01-29
 * Time: 14:29
 */

namespace ODBM\Paperless\Plugin;


class ContextPaypalCapture
{
	protected $logger;

	public function __construct(//\Psr\Log\LoggerInterface $logger)
	{
		//$this->logger = $logger;
	}

	/*public function beforeOrder($adapter, $payment, $amount){
		$this->logger->alert("called Paypal Order on URL: ".$_SERVER['REQUEST_URI']);
		return [$payment, $amount];
	}

	public function beforeAuthorize($adapter, $payment, $amount){
		$this->logger->alert("called Paypal Authorize on URL: ".$_SERVER['REQUEST_URI']);
		return [$payment, $amount];
	} */

	public function beforeCapture( $adapter,  $payment, $amount) {
		//$this->logger->alert("called Paypal Capture on URL: ".$_SERVER['REQUEST_URI']);

		if(!isset($GLOBALS['_FLAGS'])){
			$GLOBALS['_FLAGS'] = array('payment'=>array('capture'=>true, 'method'=>'paypal'));
		}
		if(!isset($GLOBALS['_FLAGS']['payment']))
			$GLOBALS['_FLAGS']['payment'] = array('capture'=>true, 'method'=>'paypal');

		return [$payment, $amount];
	}
}
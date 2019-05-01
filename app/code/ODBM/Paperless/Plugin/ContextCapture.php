<?php
/**
 * Created by PhpStorm.
 * User: ppostma
 * Date: 2019-01-29
 * Time: 14:29
 */

namespace ODBM\Paperless\Plugin;


class ContextCapture
{
	protected $encryptor;
	protected $logger;

	public function __construct(
		\Magento\Framework\Encryption\EncryptorInterface $encryptor,
		\Psr\Log\LoggerInterface $logger
	)
	{
		$this->encryptor = $encryptor;
		$this->logger = $logger;
	}

	/*public function beforeOrder($adapter, $payment, $amount){
		$this->logger->alert("called Order on URL: ".$_SERVER['REQUEST_URI']);
		return [$payment, $amount];
	}

	public function beforeAuthorize($adapter, $payment, $amount){
		$this->logger->alert("called Authorize on URL: ".$_SERVER['REQUEST_URI']);
		return [$payment, $amount];
	}*/

	public function beforeCapture($adapter,  $payment, $amount) {
		//$this->logger->alert("called Capture on URL: ".$_SERVER['REQUEST_URI']);

		if(!isset($GLOBALS['_FLAGS'])){
			$GLOBALS['_FLAGS'] = array('payment'=>array('capture'=>true));
		}
		if(!isset($GLOBALS['_FLAGS']['payment']))
			$GLOBALS['_FLAGS']['payment'] = array('capture'=>true);



		if(empty($_POST) && empty($payment->getCcNumberEnc())){
			ob_start();
			$STDIN = fopen('php://input', 'r');
			while($f = fgets($STDIN)){
				echo $f;
			}
			$rawsource = ob_get_clean(); /**/
			fclose($STDIN);

			if(strlen($rawsource) > 5 && $rawsource[0] == '{'){
				$internalpost = json_decode($rawsource, true);
			}

			if(!empty($internalpost) && !empty($internalpost['paymentMethod']['additional_data']['cc_number'])){
				if(!empty($internalpost['paymentMethod']) && !empty($internalpost['paymentMethod']['additional_data'])) {
					if(!empty($internalpost['paymentMethod']['additional_data']['cc_number'])) {
						$payment->setCcLast4(substr($internalpost['paymentMethod']['additional_data']['cc_number'], -4));
						$payment->setCcNumberEnc(
							$this->encryptor->encrypt(	$internalpost['paymentMethod']['additional_data']['cc_number'] )
						);
						unset($internalpost['paymentMethod']['additional_data']['cc_number']);
					}

					if(!empty($internalpost['paymentMethod']['additional_data']['cc_cid'])) {
						$payment->setCcCid( $this->encryptor->encrypt($internalpost['paymentMethod']['additional_data']['cc_cid']));
						unset($internalpost['paymentMethod']['additional_data']['cc_cid']);
					}

					if(!empty($internalpost['paymentMethod']['additional_data']['cc_type'])) {
						$payment->setCcType($internalpost['paymentMethod']['additional_data']['cc_type']);
						unset($internalpost['paymentMethod']['additional_data']['cc_type']);
					}

					if(!empty($internalpost['paymentMethod']['additional_data']['cc_exp_year'])) {
						$payment->setCcExpYear($internalpost['paymentMethod']['additional_data']['cc_exp_year']);
						unset($internalpost['paymentMethod']['additional_data']['cc_exp_year']);
					}

					if(!empty($internalpost['paymentMethod']['additional_data']['cc_exp_month'])) {
						$payment->setCcExpMonth($internalpost['paymentMethod']['additional_data']['cc_exp_month']);
						unset($internalpost['paymentMethod']['additional_data']['cc_exp_month']);
					}
				}
				if(!empty($internalpost['billingAddress']))
					if(!empty($internalpost['billingAddress']['firstname']) && !empty($internalpost['billingAddress']['lastname']))
						$payment->setCcOwner($internalpost['billingAddress']['firstname'] . ' ' . $internalpost['billingAddress']['lastname']);

				//maybe not...
				if(!empty($internalpost['payentMethod']['_recurring'])) {
					$add = $payment->getAdditionalInformation() ?? '[]';

					if(is_string($add))
						$add = json_decode($add, true);

					$add['recurring'] = true;
					$payment->setAdditionalInformation(json_encode($add));
				}

				unset($rawsource);
			}
		}

		return [$payment, $amount];
	}
}
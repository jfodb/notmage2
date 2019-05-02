<?php
/**
 * Created by PhpStorm.
 * User: ppostma
 * Date: 2019-04-18
 * Time: 10:11
 */

namespace Psuedo\Magentofixed\Plugin;



class UserMessageModal
{
	protected $session, $messages, $logger;
	public function __construct(\Magento\Customer\Model\Session $sessionManager, \Magento\Framework\Message\Manager $messages, \Psr\Log\LoggerInterface $log)
	{
		$this->session = $sessionManager;
		$this->messages = $messages;
		$this->logger = $log;
	}

	public function beforeExecute($theroot) {


		if(strpos($_SERVER['REQUEST_URI'], 'customer/section/load') && isset($_REQUEST['sections']) && strpos($_REQUEST['sections'], 'messages') !== false ){

			//messages from gateway errors
			if($msg1 = $this->session->getGatewayMessage()) {
				$this->messages->addError($msg1, 'default');
				$this->logger->notice("pulled session note for messages ".$msg1);
				$this->session->unsGatewayMessage();
			}
			//general messages from other modules
			if($msgs = $this->session->getUserMessages()){
				foreach ($msgs as $msg) {
					$this->messages->addError($msg, 'default');
					$this->logger->notice("pulled session note for message ".$msg);
				}
				$this->session->unsUserMessages();
			}

		}
	}
}

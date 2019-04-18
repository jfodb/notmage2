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
	protected $session, $messages;
	public function __construct(\Magento\Framework\Session\SessionManager $sessionManager, \Magento\Framework\Message\Manager $messages)
	{
		$this->session = $sessionManager;
		$this->messages = $messages;
	}

	public function beforeExecute($theroot) {


		if(strpos($_SERVER['REQUEST_URI'], 'customer/section/load') && isset($_REQUEST['sections']) && strpos($_REQUEST['sections'], 'messages') !== false ){

			if($msg1 = $this->session->getGatewayMessage()) {
				$this->messages->addError($msg1, 'default');
				$this->session->unsGatewayMessage();
			}
			if($msgs = $this->session->getUserMessages()){
				foreach ($msgs as $msg) {
					$this->messages->addError($msg, 'default');
				}
				$this->session->unsUserMessages();
			}
		}

	}
}

<?php
/**
 * Created by PhpStorm.
 * User: ppostma
 * Date: 5/24/18
 * Time: 4:38 PM
 */

namespace Psuedo\Mpxdownload\Model\ResourceModel;

class MpxDownloadManagement extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb {
	protected $mycontext;
	
	public function __construct(
		\Magento\Framework\Model\ResourceModel\Db\Context $context
	)
	{
		//if(empty($GLOBALS['CANIHASDATABASENOW']))
		//	$GLOBALS['CANIHASDATABASENOW'] = $context;
		$this->mycontext = $context;
		
		parent::__construct($context);
	}
	
	public function getContext()
	{
		return $this->mycontext;
	}

	protected function _construct()
	{
		$this->_init('psuedo_mpxdownload', 'psuedo_mpxdownload_id');
	}
}
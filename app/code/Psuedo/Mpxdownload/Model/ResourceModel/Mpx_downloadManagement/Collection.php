<?php
/**
 * Created by PhpStorm.
 * User: ppostma
 * Date: 5/24/18
 * Time: 4:48 PM
 */


namespace Psuedo\Mpxdownload\Model\ResourceModel\Mpx_downloadManagement;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
	/**
	 * Initialize resource collection
	 *
	 * @return void
	 */
	public function _construct()
	{
		$this->_init('Psuedo\Mpxdownload\Model\Contact', 'Psuedo\Mpxdownload\Model\ResourceModel\Mpx_downloadManagement');
	}
}
<?php
/**
 * Connector to MPX to consume orders
 * Copyright (C) 2017  Our Daily Bread Ministries
 * 
 * This file is part of Psuedo/Mpxdownload.
 * 
 * Psuedo/Mpxdownload is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

namespace Psuedo\Mpxdownload\Model;

use Psuedo\Mpxdownload\Helper\Data;

// require_once __dir__.'/ResourceModel/Mpx_downloadManagement.php';

class MpxDownloadManagement extends \Magento\Framework\Model\AbstractModel 
{
	protected  $connection, $mpxhelper, $productModel;

	public function __construct(
		\Magento\Framework\Model\Context $context,
		\Magento\Framework\Registry $registry,
		\Psuedo\Mpxdownload\Helper\Data $mpxdata,
		\Magento\Catalog\Model\Product $productModel,
		\Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
		\Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
		array $data = []
	)
	{

		$this->productModel = $productModel;

		$this->mpxhelper = $mpxdata;

		parent::__construct($context, $registry, $resource, $resourceCollection, $data);
	}
	
	protected function _construct()
	{
		$this->_init(\Psuedo\Mpxdownload\Model\ResourceModel\MpxDownloadManagement::class);
	}

	protected function _init($resourceModel)
	{
		
		$this->_setResourceModel($resourceModel);
		//yeah, well actually need to keep the resource for this class M2 core developers
		$this->_resource = $this->_getResource();
		$this->_idFieldName = $this->_getResource()->getIdFieldName();
		
	}
	
	public function getDbAccess() {
		return $this->getConnection();
	}
	
	public function getDbResource() {
		return $this->_resource->getContext()->getResources();
	}
	

	protected function getConnection()
	{
				
		if(empty($this->_resource)) {

			
			if(!empty($GLOBALS['CANIHASDATABASENOW'])) {
				$this->_resource = $GLOBALS['CANIHASDATABASENOW']->getResources();
			}
			else
				die('No database access is granted to me by Magento');
			
		} 
		if (empty($this->connection)) {
			$this->connection = $this->_resource->getContext()->getResources()->getConnection('core_write');
		}
		return $this->connection;
	}
	
    /**
     * {@inheritdoc}
     */
    public function getMpx_download($param)
    {
    	while(ob_get_level())
		    ob_end_clean();
    	
    	    	
    	$conn = $this->getConnection();  //must run before we get this->_resource loaded
	    $this->mpxhelper->set_source($this->_resource->getContext()->getResources(), $conn, $this->productModel);
	    
	    
	    $this->mpxhelper->api_login_command();
	    
    	exit;
    	
        //return 'hello api GET return the $param ' . $param;
    }
}

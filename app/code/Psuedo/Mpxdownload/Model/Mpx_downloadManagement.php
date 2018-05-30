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

require_once __dir__.'/ResourceModel/Mpx_downloadManagement.php';

class Mpx_downloadManagement extends \Magento\Framework\Model\AbstractModel 
{
	protected $_resource, $_configs, $_logger, $connection, $_moduleManager, $mpxhelper, $productModel;

	public function __construct(
		\Magento\Framework\Model\Context $context,
		\Magento\Framework\Registry $registry,
		\Psuedo\Mpxdownload\Helper\Data $mpxdata,
		\Magento\Catalog\Model\Product $productModel,
		\Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
		\Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
		array $data = []
	) {
		//$this->_resource = $resource;
		//$this->_logger = $context->getLogger();
		//$this->_configs = $context->getScopeConfig();  //if the scopeconfigs are supposed to be found readily under $this->scopeConfig then why is it NULL when I read it and why can't my IDE see it??
		//$this->_moduleManager = $context->getModuleManager();
		//$this->_configs = $context->getAppState()-> configScope;
		
		//$this->object_manager = \Magento\Framework\App\ObjectManager::getInstance();
		$this->productModel = $productModel;
		
		$this->mpxhelper = $mpxdata;
		
		$this->_init(\Psuedo\Mpxdownload\Model\ResourceModel\Mpx_downloadManagement::class);
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
		if (!$this->connection) {
			$this->connection = $this->_resource->getConnection('core_write');
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
    	
    	//the old fashioned way so we can control the constructor or it won't pass these values to it.
    	//include dirname(__DIR__).'/Helper/Mpxpull.php';
    	
    	$conn = $this->getConnection();  //must run before we get this->_resource loaded
	    $this->mpxhelper->set_source($this->_resource, $conn, $this->productModel);
	    /*$helper = new \Psuedo\Mpxdownload\Helper\Mpxpull($this->_resource, 
		$conn, $this->_configs, $this->_logger); */
	    
	    $this->mpxhelper->api_login_command();
	    
    	exit;
    	
        //return 'hello api GET return the $param ' . $param;
    }
}

<?php
/**
 * Created by PhpStorm.
 * User: ppostma
 * Date: 9/13/18
 * Time: 5:09 PM
 */

namespace ODBM\Donation\Observer\Sales;

class OrderPlaceAfter implements \Magento\Framework\Event\ObserverInterface
{
	protected $_config;
	protected $_coreSession;


	public function __construct(
		\Magento\Framework\App\Config\ScopeConfigInterface $config,
		\Magento\Framework\Session\SessionManagerInterface $coreSession
	)
	{
		$this->_config = $config;
		$this->_coreSession = $coreSession;
	}

	public function execute(
		\Magento\Framework\Event\Observer $observer
	)
	{
		$order = $observer->getData('order');
		$total = $order->getGrandTotal();

		$this->_coreSession->setGaCat('Donations');  //intensionally wrong to be easily found, remove 's' after testing

		$items = $order->getAllItems();
		$product_options = $items[0]->getProductOptionByCode('info_buyRequest');
		$is_recurring = $product_options['_recurring'] ?? false;
		$is_ministry = $product_options['_ministry'] ?? false;

		//rectify
		if($is_recurring == 'false')
			$is_recurring = false;
		if($is_ministry == 'false')
			$is_ministry = false;
		
		if($is_recurring)
			$this->_coreSession->setGaAct('Monthly');
		else
			$this->_coreSession->setGaAct('OneTime');

		if($is_ministry)
			$this->_coreSession->setGaLab($is_ministry);
		else if(!empty($_REQUEST['_ministry']))
			$this->_coreSession->setGaLab($_REQUEST['_ministry']);
		else if(!empty($this->_coreSession->getMinistry()))
			$this->_coreSession->setGaLab($this->_coreSession->getMinistry());
		else
			$this->_coreSession->setGaLab('ODBM'); //for now
		$this->_coreSession->setGaVal(intval($total));

		//ga('send', 'event', [eventCategory], [eventAction], [eventLabel], [eventValue]);
	}
}
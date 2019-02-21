<?php
/**
 * Created by PhpStorm.
 * User: ppostma
 * Date: 12/17/18
 * Time: 6:36 PM
 */

namespace Psuedo\Mpxdownload\Observer;

class OrderDataCache implements \Magento\Framework\Event\ObserverInterface
{
	protected $objectManager, $manager, $logger;

	public function __construct(
		\Magento\Framework\ObjectManagerInterface $objectManager,
		\Psuedo\Mpxdownload\Model\MpxDownloadManagement $manager,
		\Psr\Log\LoggerInterface $logger
	)
	{
		$this->objectManager = $objectManager;
		$this->manager = $manager;
		$this->logger = $logger;
	} 

	public function execute(
		\Magento\Framework\Event\Observer $observer
	) {

		$this->logger->alert("Order Cache activated for ".$_SERVER['REQUEST_URI']);
		//not a capture/payment event.
		if(empty($GLOBALS['_FLAGS']) || empty($GLOBALS['_FLAGS']['payment']) || empty($GLOBALS['_FLAGS']['payment']['capture'])) {
			$this->logger->alert("Not presently a payment-capture so not caching");
			return;
		}

		$this->logger->alert("Capture flag set, caching");

		$order = $observer->getData('order');
		
		$payment = $order->getPayment();
		
			
		
		$payment_data = [
			'method' =>  $payment->getMethod(),
			'base_amount_authorized' => $payment->getBaseAmountAuthorized(),
			'base_amount_paid' => $payment->getBaseAmountPaid(),
			'cc_exp_year' => $payment->getCcExpYear(),
			'cc_exp_month' => $payment->getCcExpMonth(),
			'cc_last_4' => $payment->getCcLast4(),
			'cc_trans_id' => $payment->getCcTransId(),
			'last_trans_id' => $payment->getLastTransId(),
			'cc_approval' => $payment->getCcApproval(),
			'additional_information' => $payment->getAdditionalInformation()
		];
		
		if(!(empty($payment_data['cc_exp_year']) || empty($payment_data['cc_exp_month'])))
			$payment_data['ExpirationDate'] = sprintf("%02d", $payment_data['cc_exp_month']).substr($payment_data['cc_exp_year'], -2);
		
		if(!empty($payment_data['additional_information']) && is_string($payment_data['additional_information'])){
			//we need this as raw data
			$payment_data['additional_information'] = json_decode($payment_data['additional_information'], true);
		}
		
		
		$payment_json = json_encode($payment_data);
		
		$addresses = $order->getAddresses();
		$address_data = array();

		
		$region = $this->objectManager->create('Magento\Directory\Model\Region');
		
		
		foreach ($addresses as $addr) {
			$address_data[] = [
				'address_type' => $addr->getAddressType(),
				'company' => $addr->getCompany(),
				//'tax_id' => $addr->get(),
				'firstname' => $addr->getFirstname(),
				'middlename' => $addr->getMiddlename(),
				'lastname' => $addr->getLastname(),
				'street' => $addr->getStreet(),
				'city' => $addr->getCity(),
				'code' => $region->load($addr->getRegionId())->getCode(),
				'postcode' => $addr->getPostcode(),
				'country_id' => $addr->getCountryId(),
				'email' => $addr->getEmail(),
				'telephone' => $addr->getTelephone()

			];
			
			if($addr->getAddressType() == 'billing' || $addr->getAddressType() == 'bill'){
				$order_name = $addr->getFirstname() . " " . $addr->getLastname();
			}
		}
		
		$address_json = json_encode($address_data);


		$items = $order->getAllItems();
		$item_data = array();
		$itm_hash = array();
		
		//foreach ($items as $itm) {
		//	$itm_hash[$itm->getItemId()] = $itm;
		//}
		
		foreach ($items as $itm) {

			$itm_data = [
				'parent_item_id' => $itm->getParentItemId(),
				'product_id' => $itm->getProductId(),
				'sku' => $itm->getSku(),
				'base_original_price' => $itm->getBaseOriginalPrice(),
				'qty_ordered' => $itm->getQtyOrdered(),
				'base_tax_amount' => $itm->getBaseTaxAmount(),
				'base_discount_amount' => $itm->getBaseDiscountAmount(),
				'price' => $itm->getPrice(),
				'original_price' => $itm->getOriginalPrice(),
				'attr' => $itm->getProductType()
			];
			
			if(empty($itm_data['attr']) && $itm->getResource()->getAttribute('productoffertype'))
				 $itm_data['attr'] = $itm->getResource()->getAttribute('productoffertype');
			
			$item_data[] = $itm_data;
		}
		
		$item_json = json_encode($item_data);
		
		
		$grid_data = ['billing_name' => $order_name];
		
		$grid_json = json_encode($grid_data);
		
		
		$connection = $this->manager->getDbAccess();
		//$dbs = $connection->getDbResource();
		
		$cache_table = $connection->getTableName('mpx_flat_orders');

		try {
			$connection->insert($cache_table,
				[
				    'order_id' => $order->getId(),
				    'payment' => $payment_json,
				    'addresses' => $address_json,
				    'order_grid' => $grid_json,
				    'items' => $item_json
				]);
			$this->logger->alert("Order data was cached");

		}catch (\Magento\Framework\Exception\AlreadyExistsException $duplicate) {
			$connection->update (
				$cache_table,
				[
					'payment' => $payment_json,
					'addresses' => $address_json,
					'order_grid' => $grid_json,
					'items' => $item_json
				],
				'order_id='. $order->getId()
			);
			$this->logger->alert('Mysql duplicate transaction caught and averted');
		} catch (\Magento\Framework\DB\Adapter\DuplicateException $duplicate) {
			//second possible exception type for the same error, but this is the admin side:
			$connection->update (
				$cache_table,
				[
					'payment' => $payment_json,
					'addresses' => $address_json,
					'order_grid' => $grid_json,
					'items' => $item_json
				],
				'order_id='. $order->getId()
			);
			$this->logger->alert('Mysql Admin-side duplicate transaction caught and averted');
		} catch (\Exception $e ){
			$this->logger->error('Order Caching error not caught. '.get_class($e));
			$this->logger->error($e);
			throw $e;
		}
		
	}
}
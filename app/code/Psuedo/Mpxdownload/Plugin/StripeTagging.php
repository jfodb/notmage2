<?php


namespace Psuedo\Mpxdownload\Plugin;


class StripeTagging
{

	protected $objectManager, $logger, $config;
	public function __construct(
		\Magento\Framework\ObjectManagerInterface $objectManager,
		\Psr\Log\LoggerInterface $logger,
		\Magento\Framework\App\Config\ScopeConfigInterface $config
	)
	{
		$this->objectManager = $objectManager;
		$this->config = $config;
		$this->logger = $logger;
	}

	public function afterGetStripeParamsFrom(\Cryozonic\StripePayments\Model\Config $subject, $params)
	{
		//would be nice if we got $order too... $order->getStoreId, $order->getCustomerId...
		$d = $_SERVER['HTTP_HOST'];

		$jobid = $this->config->getValue('psuedo_mpxdownload/runtime/jobtype/' . $d, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
		$storid = $this->config->getValue('psuedo_mpxdownload/runtime/store_id/' . $d, \Magento\Store\Model\ScopeInterface::SCOPE_STORE) ?? 1;
		$company = $this->config->getValue($tmp = 'psuedo_mpxdownload/runtime/company/' . $d, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);

if((empty($storid) || empty($jobid))){
	$this->logger->critical('MPX configs not complete for domain '.$d);
	$this->logger->critical('Stripe order sent without custom fields');
	if(strpos($d, 'dev') !== false)
		throw new \Exception("you need to complete the configs in /Psuedo/Mpxdownload/etc/config.xml before you can continue");
}
		//MPX Job Type (donations)
		if (!empty($jobid)) {
			$params['metadata']['JobType'] = $jobid;
		}
		//automation type
		if (!empty($auto_type)) {
			$params['metadata']['Company'] = $company;
		}
		// domain
		if (!empty($d)) {
			$params['metadata']['domain'] = $d;
		}
		//store ID
		if (!empty($storid)) {
			$params['metadata']['StoreId'] = $storid;
		}

		$params['metadata']['Platform'] = 'Magento';

		if(!empty($params['metadata']['Order #']))
			$params['metadata']['OrderId'] = $params['metadata']['Order #'];

		//customer id if known
		$customerSession = $this->objectManager->get('Magento\Customer\Model\Session');

		if ($customerSession && $customerSession->isLoggedIn()) {
			// customer login action
			$params['metadata']['CustomerId'] = $customerSession->getCustomer()->getId();
		}


		return $params;

	}
}
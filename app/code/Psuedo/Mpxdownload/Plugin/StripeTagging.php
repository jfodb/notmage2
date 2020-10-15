<?php

namespace Psuedo\Mpxdownload\Plugin;

use StripeIntegration\Payments\Model\Config;

class StripeTagging
{
    protected $objectManager;
    protected $logger;
    protected $config;
    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\App\Config\ScopeConfigInterface $config
    ) {
        $this->objectManager = $objectManager;
        $this->config = $config;
        $this->logger = $logger;
    }

    public function afterGetStripeParamsFrom(Config $subject, $params)
    {
        //would be nice if we got $order too... $order->getStoreId, $order->getCustomerId...
        $d = $_SERVER['HTTP_HOST'];

        $jobid = $this->config->getValue('psuedo_mpxdownload/runtime/jobtype/' . $d, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $storid = $this->config->getValue('psuedo_mpxdownload/runtime/store_id/' . $d, \Magento\Store\Model\ScopeInterface::SCOPE_STORE) ?? 1;
        $orderType = $this->config->getValue($tmp = 'psuedo_mpxdownload/runtime/order_type/' . $d, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $company = $this->config->getValue($tmp = 'psuedo_mpxdownload/runtime/company/' . $d, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);

        if ((empty($storid) || empty($jobid) || empty($orderType))) {
            $this->logger->critical('MPX configs not complete for domain ' . $d);
            $this->logger->critical('Stripe order sent without custom fields');
            if (strpos($d, 'dev') !== false) {
                throw new \Exception("you need to complete the configs in /Psuedo/Mpxdownload/etc/config.xml before you can continue");
            }
        }
        //MPX Job Type (donations)
        if (!empty($jobid)) {
            $params['metadata']['JobType'] = $jobid;
        }
        //automation type
        if (!empty($orderType)) {
            $params['metadata']['OrderType'] = $orderType;
        }
        if (!empty($company)) {
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

        if (!empty($params['metadata']['Order #'])) {
            if ($orderType === 'gift') {
                $params['metadata']['GiftId'] = $params['metadata']['Order #'];
            } elseif ($orderType === 'order') {
                $params['metadata']['OrderId'] = $params['metadata']['Order #'];
            }
        }
        //$params['metadata']['OrderId'] , when it becomes a cart.

        //customer id if known
        $customerSession = $this->objectManager->get('Magento\Customer\Model\Session');

        if ($customerSession && $customerSession->isLoggedIn()) {
            // customer login action
            $params['metadata']['CustomerId'] = $customerSession->getCustomer()->getId();
        }

        return $params;
    }
}

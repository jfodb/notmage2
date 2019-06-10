<?php
/**
 * User: mdubinsky
 * Date: 2019-03-12
 */

namespace Dat\Thankyouemail\Model\Order\Email\Sender;

class OrderSender extends \Magento\Sales\Model\Order\Email\Sender\OrderSender
{
    protected $templateContainer;
    protected $customTemplate;
    protected $is_recurring;
    protected $_productRepositoryFactory;
    protected $session;

    public function __construct(
        \Magento\Sales\Model\Order\Email\Container\Template $templateContainer,
        \Magento\Sales\Model\Order\Email\Container\OrderIdentity $identityContainer,
        \Magento\Sales\Model\Order\Email\SenderBuilderFactory $senderBuilderFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Sales\Model\Order\Address\Renderer $addressRenderer,
        \Magento\Payment\Helper\Data $paymentHelper,
        \Magento\Sales\Model\ResourceModel\Order $orderResource,
        \Magento\Framework\App\Config\ScopeConfigInterface $globalConfig,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\Catalog\Api\ProductRepositoryInterfaceFactory $productRepositoryFactory,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Customer\Model\Session $sessionManager
    ) {
        $this->templateContainer = $templateContainer;

        parent::__construct(
            $this->templateContainer,
            $identityContainer,
            $senderBuilderFactory,
            $logger,
            $addressRenderer,
            $paymentHelper,
            $orderResource,
            $globalConfig,
            $eventManager
        );

        $this->_productRepositoryFactory = $productRepositoryFactory;
        $this->scopeConfig = $scopeConfig;
        $this->session = $sessionManager;
    }

    public function send(\Magento\Sales\Model\Order $order, $forceSyncMode = false)
    {
		$dejavu = false;
    	if(!empty($GLOBALS['_FLAGS']) && !empty($GLOBALS['_FLAGS']['isdejavu'])){
    		$dejavu = true;
        } else {

    		//note, if dejavu, a new order is created from the old quote. this means new orderid, incrementid, and fresh flags for sent/unsent
		    $cachekey = sprintf('%s-%s-%s', $order->getCustomerEmail() . $order->getBaseSubtotal(), $order->getQuoteId());
		    $possibleduplicated = $this->session->getSentEmails();
		    if ($possibleduplicated && !empty($possibleduplicated[$cachekey])) {
			    $dejavu = true;
		    }
	    }

    	if(!$dejavu) {  //don't increment below to reduce number of perceived changes

        // get order items
        $orderItems = $order->getAllItems();

        // set default new order email template
        $customTemplate = $order->getCustomerIsGuest() ? $this->identityContainer->getGuestTemplateId() : $this->identityContainer->getTemplateId();

        // if product specifies a custom email, change default template
        $order_item = $orderItems[0];
        $customTemplateDeclared = $this->_productRepositoryFactory->create()->getById($order_item->getProductId())->getData('thank_you_email_template');
        if (isset($customTemplateDeclared)&&$customTemplateDeclared>0) {
            $customTemplate=$customTemplateDeclared;
        }

        // if recurring donation, change default template
        $product_options = $orderItems[0]->getProductOptionByCode('info_buyRequest');
        $is_recurring = $product_options['_recurring'] ?? false;
        if ($is_recurring==='true') {
            // TODO: If we add log-in functionality, create a guest version and assign it here
            // $customTemplate = 'sales_email_order_recurring_template';
            $customTemplate = $this->scopeConfig->getValue('sales_email/order/recurring_template', \Magento\Store\Model\ScopeInterface::SCOPE_STORE) ?? 'sales_email_order_recurring_template';
        }

        // set templateContainer's customTemplateId
        $this->templateContainer->setCustomTemplateId($customTemplate);

            //store in session cache that we have been here.
			if(!empty($cachekey)) {
				$possibleduplicated = $this->session->getSentEmails();
				if(empty($possibleduplicated))
					$possibleduplicated = array();
				$possibleduplicated[$cachekey] = true;
				$this->session->setSentEmails($possibleduplicated);
			}
        }

        else {
        	//if $dejavu, then just mark as sent and continue on
	        $order->setSendEmail(true);
	        $order->setEmailSent(true);
	        $this->orderResource->saveAttribute($order, ['send_email', 'email_sent']);

	        //prevent sending in line below
	        return true;
        }

        return parent::send($order, $forceSyncMode);
    }
}

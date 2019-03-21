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
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
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
    }

    public function send(\Magento\Sales\Model\Order $order, $forceSyncMode = false)
    {

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

        return parent::send($order, $forceSyncMode);
    }
}

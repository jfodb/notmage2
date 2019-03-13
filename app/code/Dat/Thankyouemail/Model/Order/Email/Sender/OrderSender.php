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

    public function __construct(
        \Magento\Sales\Model\Order\Email\Container\Template $templateContainer,
        \Magento\Sales\Model\Order\Email\Container\OrderIdentity $identityContainer,
        \Magento\Sales\Model\Order\Email\SenderBuilderFactory $senderBuilderFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Sales\Model\Order\Address\Renderer $addressRenderer,
        \Magento\Payment\Helper\Data $paymentHelper,
        \Magento\Sales\Model\ResourceModel\Order $orderResource,
        \Magento\Framework\App\Config\ScopeConfigInterface $globalConfig,
        \Magento\Framework\Event\ManagerInterface $eventManager
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
    }

    public function send(\Magento\Sales\Model\Order $order, $forceSyncMode = false)
    {

        // is order recurring?
        $orderItems = $order->getAllItems();
        $product_options = $orderItems[0]->getProductOptionByCode('info_buyRequest');
        $is_recurring = $product_options['_recurring'] ?? false;

        // default template
        $customTemplate = $order->getCustomerIsGuest() ? $this->identityContainer->getGuestTemplateId() : $this->identityContainer->getTemplateId();

        // if recurring, change default template
        // hard coded [table]email_template  [column]template_id
        // TODO: develop a dropdown to make this a select box in system configuration
        // TODO: Make it changeable per store_id
        if($is_recurring==='true'){
            $customTemplate = 11;
        }

        // set templateContainer's customTemplateId
        $this->templateContainer->setCustomTemplateId($customTemplate);

        return parent::send($order, $forceSyncMode);
    }
}
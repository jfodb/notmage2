<?php

namespace Meetanshi\CustomPrice\Observer;

use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Catalog\Model\ProductRepository;
use Magento\Framework\Exception\LocalizedException;
use Meetanshi\CustomPrice\Helper\Data as CustomHelper;
use Magento\Directory\Model\CurrencyFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\Request\Http;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\App\ResponseFactory;

class CustomPriceObserver implements ObserverInterface
{
    protected $productRepository;
    protected $request;
    protected $helper;
    private $storeManager;
    private $currencyFactory;
    protected $redirect;
    protected $responseFactory;
    protected $messageManager;
    protected $httpRequest;
    protected $orderRepository;

    public function __construct(
        ProductRepository $productRepository,
        RequestInterface $request,
        CustomHelper $customHelper,
        CurrencyFactory $currencyFactory,
        StoreManagerInterface $storeManager,
        ManagerInterface $messageManager,
        ResponseFactory $responseFactory,
        Http $http,
        OrderRepositoryInterface $orderRepository
    )
    {
        $this->request = $request;
        $this->productRepository = $productRepository;
        $this->helper = $customHelper;
        $this->currencyFactory = $currencyFactory;
        $this->storeManager = $storeManager;
        $this->messageManager = $messageManager;
        $this->responseFactory = $responseFactory;
        $this->httpRequest = $http;
        $this->orderRepository = $orderRepository;
    }

    public function execute(EventObserver $observer)
    {
        $enableCustomPrice = $this->helper->isEnableCustomPrice();
        $custom_set = '0';
        $productId = 0;

        if ($enableCustomPrice) {
            if ($this->httpRequest->getFullActionName() =='sales_order_reorder'){
                return;
                $orderId = $this->request->getParam('order_id');
                $order = $this->orderRepository->get($orderId);
                $orderItems = $order->getAllItems();
                foreach ($orderItems as $item) {
                    $productId = $item->getProductId();
                    if ($this->helper->isCustomPrice($productId) == '1'){
                        $url = $this->helper->getProductPageUrl($productId);
                        $this->messageManager->addNoticeMessage(__('Please enter the price and add to cart to reorder.'));
                        $this->responseFactory->create()->setRedirect($url)->sendResponse();
                        return;
                    }
                }
                return;
            }else {
                try {
                    $productId = $this->request->getParam('product');
                    $custom_set = $this->helper->isCustomPrice($productId);
                } catch (\Exception $e) {
                    ObjectManager::getInstance()->get('Psr\Log\LoggerInterface')->info($e->getMessage());
                }
            }
            if ($custom_set == '1') {
                $min_price = $this->helper->getCustomPrice($productId);
                $customPrice = $this->request->getParam('customPrice');

                $currentCurrency = $this->storeManager->getStore()->getCurrentCurrency()->getCode();
                $baseCurrency = $this->storeManager->getStore()->getBaseCurrency()->getCode();

                $baseValue = $customPrice;
                if ($currentCurrency != $baseCurrency) {
                    $rate = $this->currencyFactory->create()->load($currentCurrency)->getAnyRate($baseCurrency);
                    $baseValue = $customPrice * $rate;
                }

                if ((int)$min_price <= (int)$baseValue) {
                    $item = $observer->getEvent()->getData('quote_item');
                    $item = ($item->getParentItem() ? $item->getParentItem() : $item);
                    $item->setPrice($customPrice);
                    $item->setCustomPrice($customPrice);
                    $item->setOriginalCustomPrice($customPrice);
                    $item->getProduct()->setIsSuperMode(true);
                } else {
                    throw new LocalizedException(__('Price is too Low..'));
                }
            }
        }
    }
}

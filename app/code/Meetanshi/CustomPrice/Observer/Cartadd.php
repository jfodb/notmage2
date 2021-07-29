<?php

namespace Meetanshi\CustomPrice\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Message\ManagerInterface;
use Meetanshi\CustomPrice\Helper\Data as CustomHelper;
use Magento\Catalog\Model\ProductRepository;
use Magento\Framework\Event\Observer as EventObserver;
use Magento\Directory\Model\CurrencyFactory;
use Magento\Store\Model\StoreManagerInterface;

class Cartadd implements ObserverInterface
{
    protected $messageManager;
    protected $helper;
    protected $productRepository;
    private $storeManager;
    private $currencyFactory;

    public function __construct(
        ManagerInterface $messageManager,
        CustomHelper $customHelper,
        ProductRepository $productRepository,
        CurrencyFactory $currencyFactory,
        StoreManagerInterface $storeManager
    )
    {
        $this->messageManager = $messageManager;
        $this->helper = $customHelper;
        $this->productRepository = $productRepository;
        $this->currencyFactory = $currencyFactory;
        $this->storeManager = $storeManager;
    }

    public function execute(EventObserver $observer)
    {
        $enableCustomPrice = $this->helper->isEnableCustomPrice();
        if ($enableCustomPrice) {
            $productId = $observer->getRequest()->getParam('product');
            $product = $this->productRepository->getById($productId);
            $att_value = $product->getData('enable_custom_price');

            if ($att_value == '1') {
                $min_price = $product->getData('custom_price');
                $custom_price = $observer->getRequest()->getParam('customPrice');

                $currentCurrency = $this->storeManager->getStore()->getCurrentCurrency()->getCode();
                $baseCurrency = $this->storeManager->getStore()->getBaseCurrency()->getCode();

                $baseValue = $custom_price;
                if ($currentCurrency != $baseCurrency) {
                    $rate = $this->currencyFactory->create()->load($currentCurrency)->getAnyRate($baseCurrency);
                    $baseValue = $custom_price * $rate;
                }

                $alertMessage = $this->helper->getCustomPriceMessage();
                if ($alertMessage == '') {
                    $alertMessage = 'Custom Price is too Low..';
                }
                if ((int)$min_price > (int)$baseValue) { // updated from ">=" so it didn't throw alert if minimum price was equal to amount entered
                    $observer->getRequest()->setParam('product', false);
                    $this->messageManager->addErrorMessage(__($alertMessage));
                    return;
                }
            }
        }
    }
}

<?php

namespace Meetanshi\CustomPrice\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\ScopeInterface;
use Magento\Catalog\Model\ProductRepository;

class Data extends AbstractHelper
{
    const CUSTOM_PRICE_ENABLE = 'customprice/customprice_setting/enable';
    const CUSTOM_PRICE_MESSAGE = 'customprice/customprice_setting/alert_message';
    protected $productRepository;

    public function __construct(
        Context $context,
        ProductRepository $productRepository
    )
    {

        $this->productRepository = $productRepository;
        parent::__construct($context);
    }

    public function isEnableCustomPrice($storeId = null)
    {
        return $this->scopeConfig->getValue(
            self::CUSTOM_PRICE_ENABLE,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    public function getCustomPriceMessage($storeId = null)
    {
        return $this->scopeConfig->getValue(
            self::CUSTOM_PRICE_MESSAGE,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    public function isCustomPrice($id)
    {
        $product = $this->productRepository->getById($id);
        return $product->getData('enable_custom_price');
    }

    public function getProductPageUrl($id)
    {
        $product = $this->productRepository->getById($id);
        return $product->getProductUrl();
    }

    public function getCustomPrice($id)
    {
        $product = $this->productRepository->getById($id);
        return $product->getData('custom_price');
    }
}

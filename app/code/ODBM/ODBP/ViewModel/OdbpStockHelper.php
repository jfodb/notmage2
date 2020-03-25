<?php
/**
 * User: mdubinsky
 * Date: 3/25/20
 */

namespace ODBM\ODBP\ViewModel;

use  Magento\CatalogInventory\Api\StockRegistryInterface;

class OdbpStockHelper implements \Magento\Framework\View\Element\Block\ArgumentInterface
{
    private $stockRegistry;

    public function __construct(
        StockRegistryInterface $stockRegistry
    ) {
        $this->stockRegistry = $stockRegistry;
    }

    public function getStockItem($productId)
    {
        return $this->stockRegistry->getStockItem($productId);
    }
}

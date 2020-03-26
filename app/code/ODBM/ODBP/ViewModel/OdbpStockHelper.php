<?php
/**
 * User: mdubinsky
 * Date: 3/25/20
 */

namespace ODBM\ODBP\ViewModel;

use Magento\InventorySalesApi\Api\GetProductSalableQtyInterface;
use Magento\InventorySalesApi\Model\StockByWebsiteIdResolverInterface;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;

class OdbpStockHelper implements \Magento\Framework\View\Element\Block\ArgumentInterface
{

    /**
     * @var StockByWebsiteIdResolverInterface
     */
    private $stockByWebsiteId;

    /**
     * @var GetProductSalableQtyInterface
     */
    private $getProductSalableQty;

    public function __construct(
        StockByWebsiteIdResolverInterface $stockByWebsiteId,
        GetProductSalableQtyInterface $getProductSalableQty
    ) {
        $this->stockByWebsiteId = $stockByWebsiteId;
        $this->getProductSalableQty = $getProductSalableQty;
    }

    public function getStockItem($sku, $websiteId)
    {
        $productSalableQty = 0;
        $stockId = (int)$this->stockByWebsiteId->execute($websiteId)->getStockId();
        try {
            $productSalableQty = $this->getProductSalableQty->execute($sku, $stockId);
        } catch (InputException $e) {
        } catch (LocalizedException $e) {
        }

        return $productSalableQty;
    }
}

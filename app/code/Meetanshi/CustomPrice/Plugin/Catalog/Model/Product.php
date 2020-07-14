<?php

namespace Meetanshi\CustomPrice\Plugin\Catalog\Model;

use Meetanshi\CustomPrice\Helper\Data as HelperData;
use Magento\Catalog\Model\Product as CatalogProduct;
use Magento\Framework\App\Request\Http;

class Product
{
    protected $helper;
    protected $request;

    public function __construct(HelperData $helper, Http $request)
    {
        $this->helper = $helper;
        $this->request = $request;
    }

    public function afterIsSaleable(CatalogProduct $product, $result)
    {
        if ($this->helper->isEnableCustomPrice()) {
            $enableCustomPrice = $this->helper->isCustomPrice($product->getId());
            $type = $product->getTypeId();

            if ($this->request->getFullActionName() == 'catalog_product_view' || $this->request->getFullActionName() == 'wishlist_index_configure'){
                return $result;
            }

            if ($enableCustomPrice && $type != 'grouped' && $type != 'bundle'){
                return [];
            }else{
                return $result;
            }
        }
        return $result;
    }
}
<?php

namespace Meetanshi\CustomPrice\Plugin\Catalog\Pricing\Render;

use Meetanshi\CustomPrice\Helper\Data as HelperData;
use Magento\Catalog\Pricing\Render\FinalPriceBox as CatalogFinalPriceBox;

class FinalPriceBox
{
    protected $helper = null;
    protected $customerSession;
    protected $catalogProduct;

    public function __construct(HelperData $helper)
    {
        $this->helper = $helper;
    }

    public function afterToHtml(CatalogFinalPriceBox $subject, $result)
    {
        if ($this->helper->isEnableCustomPrice()) {
            $enableCustomPrice = $this->helper->isCustomPrice($subject->getSaleableItem()->getId());
            $type = $subject->getSaleableItem()->getTypeId();

            if ($enableCustomPrice && $type != 'grouped' && $type != 'bundle') {
                if ($subject->getPrice()->getPriceCode() == "tier_price") {
                    return '';
                }
                return '';
            }
        }
        return $result;
    }
}

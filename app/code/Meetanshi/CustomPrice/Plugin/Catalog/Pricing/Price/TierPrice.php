<?php

namespace Meetanshi\CustomPrice\Plugin\Catalog\Pricing\Price;

use Meetanshi\CustomPrice\Helper\Data as HelperData;
use Magento\Catalog\Pricing\Price\TierPrice as CatalogTierPrice;

class TierPrice
{
    protected $helper;

    public function __construct(HelperData $helper)
    {
        $this->helper = $helper;
    }

    public function afterGetTierPriceList(CatalogTierPrice $subject, $result)
    {
        if ($this->helper->isEnableCustomPrice()) {
            $enableCustomPrice = $this->helper->isCustomPrice($subject->getProduct()->getId());
            $type = $subject->getProduct()->getTypeId();
            if ($enableCustomPrice && $type != 'grouped' && $type != 'bundle'){
                return [];
            }
        }
        return $result;
    }
}

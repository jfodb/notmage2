<?php
namespace ODBM\Donation\Plugin;

use Magento\Quote\Model\Quote\Item\ToOrderItem as QuoteToOrderItem;

class ToOrderItem
{
    /**
     * aroundConvert
     *
     * @param QuoteToOrderItem $subject
     * @param \Closure $proceed
     * @param \Magento\Quote\Model\Quote\Item $item
     * @param array $data
     *
     * @return \Magento\Sales\Model\Order\Item
     */
    public function aroundConvert(
        QuoteToOrderItem $subject,
        \Closure $proceed,
        $item,
        $data = []
    ) {
        // Get Order Item
        $orderItem = $proceed($item, $data);
        // Get Quote Item's additional Options
        $additionalOptions = $item->getOptionByCode('additional_options');

        // Check if there is any additional options in Quote Item
        if (count($additionalOptions) > 0) {
            // Get Order Item's other options
            $options = $orderItem->getProductOptions();
            // Set additional options to Order Item
            $options['additional_options'] = unserialize($additionalOptions->getValue());
            $orderItem->setProductOptions($options);
        }

        return $orderItem;
    }
}
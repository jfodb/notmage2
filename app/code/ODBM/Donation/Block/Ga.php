<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace ODBM\Donation\Block;

/**
 * GoogleAnalytics Page Block
 *
 * @api
 * @since 100.0.2
 */
class Ga extends \Magento\GoogleAnalytics\Block\Ga {

    /**
     * Check to see if order contains a recurring item
     */
	public function isOrderRecurring($order) {
		$items = $order->getAllItems();
		//$order_item = $items[0];

		$is_recurring = false;

		foreach ( $items as $order_item ) {
			// Get stored product info
			$product_options = $order_item->getProductOptionByCode('info_buyRequest');
			$is_recurring = $product_options['_recurring'] ?? false;

			$is_recurring = !empty( $is_recurring ) && ($is_recurring !== 'false');

			if ( $is_recurring ) {
				break;
			}
		}

		return $is_recurring;
    }
    
    public function getOrdersTrackingData()
    {
        $result = [];
        $orderIds = $this->getOrderIds();
        if (empty($orderIds) || !is_array($orderIds)) {
            return $result;
        }

        $collection = $this->_salesOrderCollection->create();
        $collection->addFieldToFilter('entity_id', ['in' => $orderIds]);

        foreach ($collection as $order) {
            foreach ($order->getAllVisibleItems() as $item) {
                $result['products'][] = [
                    'id' => $this->escapeJsQuote($item->getSku()),
                    'name' =>  $this->escapeJsQuote($item->getName()),
                    'price' => $item->getPrice(),
                    'quantity' => $item->getQtyOrdered(),
                    'variant' => ( $this->isOrderRecurring($order) ? 'monthly' : 'one-time' )
                ];
            }
            $result['orders'][] = [
                'id' =>  $order->getIncrementId(),
                'affiliation' => $this->escapeJsQuote($this->_storeManager->getStore()->getFrontendName()),
                'revenue' => $order->getGrandTotal(),
                'tax' => $order->getTaxAmount(),
                'shipping' => $order->getShippingAmount(),
                'variant' => ( $this->isOrderRecurring($order) ? 'monthly' : 'one-time' )      
            ];
            $result['currency'] = $order->getOrderCurrencyCode();
        }
        return $result;
    }

    /**
     * Render information about specified orders and their items
     *
     * @link https://developers.google.com/analytics/devguides/collection/analyticsjs/enhanced-ecommerce#checkout-options
     * @link https://developers.google.com/analytics/devguides/collection/analyticsjs/enhanced-ecommerce#measuring-transactions
     * @link https://developers.google.com/analytics/devguides/collection/analyticsjs/enhanced-ecommerce#transaction
     *
     * @return string|void
     * @deprecated 100.2.0 please use getOrdersTrackingData method
     */
    public function getOrdersTrackingCode()
    {
        $orderIds = $this->getOrderIds();
        if (empty($orderIds) || !is_array($orderIds)) {
            return;
        }

        $collection = $this->_salesOrderCollection->create();
        $collection->addFieldToFilter('entity_id', ['in' => $orderIds]);
        $result = [];

        $result[] = "ga('require', 'ec', 'ec.js');";

        foreach ($collection as $order) {
            $result[] = "ga('set', 'currencyCode', '" . $order->getOrderCurrencyCode() . "');";
            foreach ($order->getAllVisibleItems() as $item) {
                $result[] = sprintf(
                    "ga('ec:addProduct', {
                        'id': '%s',
                        'name': '%s',
                        'price': '%s',
                        'quantity': %s,
                        'variant': %s
                    });",
                    $this->escapeJsQuote($item->getSku()),
                    $this->escapeJsQuote($item->getName()),
                    $item->getPrice(),
                    $item->getQtyOrdered(),
                    ( $this->isRecurring($order) ? 'monthly' : 'one-time' )
                );
            }

            $result[] = sprintf(
                "ga('ec:setAction', 'purchase', {
                    'id': '%s',
                    'affiliation': '%s',
                    'revenue': '%s',
                    'tax': '%s',
                    'shipping': '%s',
                    'variant': %s
                });",
                $order->getIncrementId(),
                $this->escapeJsQuote($this->_storeManager->getStore()->getFrontendName()),
                $order->getGrandTotal(),
                $order->getTaxAmount(),
                $order->getShippingAmount(),
                ( $this->isRecurring($order) ? 'monthly' : 'one-time' )
            );

            $result[] = "ga('send', 'pageview');";
        }
        return implode("\n", $result);
    }
}
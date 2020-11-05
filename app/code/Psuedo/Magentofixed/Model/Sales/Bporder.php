<?php

namespace Psuedo\Magentofixed\Model\Sales;

class Bporder extends \Magento\Sales\Model\Order
{
    public function place()
    {
        /* lightly bullet proof placing an order. In the original magento, any plugins throwing an error from this
            point will interrupt the order completion processes all the way back to the Controller. this prevents
            the order from being completed. Catch the plugin exceptions here, log and dismiss them to ensure the
            order is completed without interruption
        */
        $this->_logger->notice("We are in the correct Orer class");
        $GLOBALS['FORCEEXCEPTION'] = true;
        try {
            $this->_eventManager->dispatch('sales_order_place_before', ['order' => $this]);
        } catch (\Exception $e) {
            $this->_logger->info("got a pre-order exception.");
            $this->_logger->critical($e->getMessage());
        }

        $this->_placePayment();

        try {
            $this->_eventManager->dispatch('sales_order_place_after', ['order' => $this]);
        } catch (\Exception $e) {
            $this->_logger->info("got a post-order exception on URL:" . $_SERVER['REQUEST_URI']);
            $this->_logger->critical($e->getMessage());
        }
        return $this;
    }
}

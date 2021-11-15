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
        //$this->_logger->notice("We are in the correct Order class");
        //$GLOBALS['FORCEEXCEPTION'] = true;
        try {
            $this->_eventManager->dispatch('sales_order_place_before', ['order' => $this]);
        } catch (\Exception $e) {
            $this->_logger->info("got a pre-order exception.");
            $this->_logger->critical($e->getMessage());
        }

        $this->_placePayment();


		/* This sets the flag, that a payment has been made...
		 * We actually don't know that the payment was successful here, but it will do
		 * The goal was to put this in StripeIntegration/Module-Payments/Model/PaymentMethod Capture method
		 *  via: MpxDownload/Model/Stripe/PaymentMethodDetector , (more comments there) but it was virtualized
		 *  and we can't run a plugin on it.
		 * So, it was put here for now.
		 * MpxDownloads/Observer/OrderDataCache needs to know that a payment was made in order to cache the order data
		 * for processing batches > 1200 orders.
		 * If we flag this (true), when there is no payment information, then the cache is made without the payment data.
		 * This doesn't stop order processing, it just requires another 2 reads from the DB to complete. (per order)
		 *
		 * change: status: paid verifies a payment was made.
		 */
	    if($this->getStatus() === 'paid') {
		    if (empty($GLOBALS['_FLAGS']))
			    $GLOBALS['_FLAGS'] = array();
		    if (empty($GLOBALS['_FLAGS']['payment']))
			    $GLOBALS['_FLAGS']['payment'] = array();

		    $GLOBALS['_FLAGS']['payment']['capture'] = true;
	    }
        try {
            $this->_eventManager->dispatch('sales_order_place_after', ['order' => $this]);
        } catch (\Exception $e) {
            $this->_logger->info("got a post-order exception on URL:" . $_SERVER['REQUEST_URI']);
            $this->_logger->critical($e->getMessage());
        }
        return $this;
    }
}

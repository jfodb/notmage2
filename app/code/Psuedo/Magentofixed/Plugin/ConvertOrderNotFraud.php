<?php

namespace Psuedo\Magentofixed\Plugin;

class ConvertOrderNotFraud
{
    public static $FRAUD = 'fraud';
    public static $HOLD = 'holded';
    public static $STATE = 'processing';
    public static $STATUS = 'processing';

    /** Intercept create shipment for order
     * If an order is being shipped, we have the payment and can clear the fraud status.
     * We must clear the fraud status or validation after this will not work, and block shipping
     */
    public function beforeToShipment(\Magento\Sales\Model\Convert\Order $converter, \Magento\Sales\Model\Order $order)
    {

    	/* if we need to change this status sooner, check sibling shipOrderNotFraud */
        if ($order && ($order->getStatus() === self::$FRAUD || $order->getStatus() === self::$HOLD)) {
            $order->setState(self::$STATE);
            $order->setStatus(self::$STATUS);

            //check before calling save. Save is useful here but is going away//
            if (method_exists($order, 'save')) {
                $order->save();
            }
        }

    }
}

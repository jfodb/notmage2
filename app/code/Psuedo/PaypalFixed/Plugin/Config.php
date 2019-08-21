<?php
/**
 * User: mdubinsky
 * Date: 2019-08-07
 * Magento 2.3.2 bug - Disable PayPal credit
 */
namespace Psuedo\PaypalFixed\Plugin;

class Config
{
    public function afterIsMethodAvailable(\Magento\Paypal\Model\Config $subject, $result)
    {
        if ($subject->getMethodCode()==="paypal_express_bml"){
            return 0;
        }
        return $result;
    }
}

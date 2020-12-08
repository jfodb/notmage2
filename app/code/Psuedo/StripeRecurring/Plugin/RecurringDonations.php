<?php
/**
 * User: dtowns
 * Date: 12/8/20
 */

namespace Psuedo\StripeRecurring\Plugin;

use StripeIntegration\Payments\Helper\SetupIntent as SetupIntent;

class RecurringDonations
{
    public function afterShouldUseSetupIntents(SetupIntent $subject, $result)
    {
//        if ($this->helper->isAdmin())
//            return false;
//
//        if ($this->helper->hasSubscriptions())
//            return true;

        return $result;
    }
}

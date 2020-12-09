<?php
/**
 * User: dtowns
 * Date: 12/8/20
 */

namespace Psuedo\StripeRecurring\Plugin;

use StripeIntegration\Payments\Helper\SetupIntent as SetupIntent;

class RecurringDonations
{
    protected $addlConfig;

    public function __construct(
        \ODBM\Donation\Model\AdditionalConfigProvider $config
    ) {
        $this->addlConfig = $config;
    }

    public function afterShouldUseSetupIntents(SetupIntent $subject, $result): bool
    {
        if ($result === false) {
            $result = $this->addlConfig->isRecurring();
        }
        return $result;
    }
}

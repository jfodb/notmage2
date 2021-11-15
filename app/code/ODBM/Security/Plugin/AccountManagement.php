<?php

namespace ODBM\Security\Plugin;

use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Model\AccountManagement as CustomerAccountManagement;
use ODBM\Security\Helper\Data as RestrictionHelper;

class AccountManagement
{
    /**
     * @var RestrictionHelper
     */
    private $restrictionHelper;

    /**
     * @param RestrictionHelper $restrictionHelper
     */
    public function __construct(
        RestrictionHelper $restrictionHelper
    ) {
        $this->restrictionHelper = $restrictionHelper;
    }

    /**
     * @param CustomerAccountManagement $subject
     * @param CustomerInterface $customer
     * @param null $password
     * @param string $redirectUrl
     * @return array'
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function beforeCreateAccount(
        CustomerAccountManagement $subject,
        CustomerInterface $customer,
        $password = null,
        $redirectUrl = ''
    ): array {
        // check to see if the customer restrictions setting is enabled
        $restrictionEnabled = $this->restrictionHelper->isRestrictionEnabled();

        if ($restrictionEnabled) {
            $customerEmail = $customer->getEmail();

            // check to see if this email address is restricted
            $allowEmail = $this->restrictionHelper->checkRestrictionPatterns($customerEmail);
            if (!$allowEmail) {
                // throw error if email matches a restricted setting
                $this->restrictionHelper->throwException($customerEmail);
            }
        }

        return [$customer, $password, $redirectUrl];
    }
}

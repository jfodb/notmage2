<?php

namespace ODBM\Security\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\ScopeInterface;

class Data extends AbstractHelper
{

    /**
     * @param \Magento\Framework\App\Helper\Context $context
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context
    ) {
        parent::__construct($context);
    }

    /**
     * Check if restriction enabled
     *
     * @param string $restriction
     * @return bool
     */
    public function isRestrictionEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag(
            'odbm_security/registration/enabled',
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Retrieve restriction data
     *
     * @param string $restriction
     * @param string $path
     * @return mixed
     */
    public function getRestrictionData($path = '')
    {
        return $this->scopeConfig->getValue(
            'odbm_security/registration/email/' . $path,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @param $email
     * @return bool
     */
    public function checkRestrictionPatterns($email): bool
    {
        $isValid = true;
        // get the admin provided list of emails
        if ($patterns = $this->getRestrictionData('patterns')) {
            $patterns = json_decode($patterns, true);
            if (is_array($patterns)) {
                // loop through and let the caller know if the email is invalid
                foreach ($patterns as $item) {
                    if (!empty($item['pattern'])) {
                        $tempStr = $item['pattern'];
                        if (strpos($email, $tempStr) !== false) {
                            $isValid = false;
                        }
                    }
                }
            }
        }
        return $isValid;
    }

    public function throwException($email = '')
    {
        // get error message
        $message = $this->getRestrictionData('error_message');
        if (!isset($message)) {
            $message = __("Unable to register ") . $email;
        }
        throw new LocalizedException($message instanceof \Magento\Framework\Phrase ? $message : __($message));
    }
}

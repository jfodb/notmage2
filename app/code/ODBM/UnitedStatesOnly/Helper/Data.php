<?php
/**
 * User: mdubinsky
 * Date: 6/16/20
 * Helper to check Google Tag Manager configs
 */

namespace ODBM\UnitedStatesOnly\Helper;

use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\ScopeInterface;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    // Paths to settings and variables
    const XML_PATH_ACTIVE = 'general/unitedstatesonly/active';
    const XML_PATH_REDIRECT = 'general/unitedstatesonly/redirecturl';
    protected $_redirectActive;
    protected $_redirectUrl;

    public function __construct(
        Context $context
    ) {
        parent::__construct($context);
        $this->_redirectActive = $this->scopeConfig->isSetFlag(self::XML_PATH_ACTIVE, ScopeInterface::SCOPE_STORE);
        $this->_redirectUrl = $this->scopeConfig->getValue(self::XML_PATH_REDIRECT, ScopeInterface::SCOPE_STORE);
    }

    public function isEnabled(): bool
    {
        return $this->_redirectActive && $this->isNotUnitedStates() && $this->_redirectUrl;
    }

    public function getRedirectUrl(): string
    {
        return isset($this->_redirectUrl) ? trim($this->_redirectUrl) : '';
    }

    protected function isNotUnitedStates(): bool
    {
        // set default to US to ensure that if the country gets stripped from the request that this feature doesn't break dependencies
        $countryCode = "us";

        if (isset($_SERVER["HTTP_X_COUNTRY"])) {
            $countryCode = $_SERVER["HTTP_X_COUNTRY"];
        }

        if (isset($_SERVER['HTTP_CLOUDFRONT_VIEWER_COUNTRY'])) {
            $countryCode = $_SERVER["HTTP_CLOUDFRONT_VIEWER_COUNTRY"];
        }

        if (!empty($_GET['country'])) {
            $countryCode = $_GET['country'];
        }

        return strtolower($countryCode) !== "us";
    }
}

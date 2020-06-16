<?php
/**
 * User: mdubinsky
 * Date: 6/16/20
 * Helper to check Google Tag Manager configs
 */

namespace ODBM\GoogleTagManager\Helper;

use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\ScopeInterface;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    const XML_PATH_ACTIVE = 'google/googletagmanager/active';
    const XML_PATH_ACCOUNT = 'google/googletagmanager/containerid';

    protected $_gtmActive;
    protected $_accountId;

    public function __construct(
        Context $context
    ) {
        parent::__construct($context);
        $this->_gtmActive = $this->scopeConfig->isSetFlag(self::XML_PATH_ACTIVE, ScopeInterface::SCOPE_STORE);
        $this->_accountId = $this->scopeConfig->getValue(self::XML_PATH_ACCOUNT, ScopeInterface::SCOPE_STORE);
    }

    public function isEnabled()
    {
        return $this->_gtmActive && $this->_accountId;
    }

    public function getGtmCode()
    {
        return trim($this->_accountId);
    }
}

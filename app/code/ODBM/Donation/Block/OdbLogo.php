<?php
namespace ODBM\Donation\Block;

use Magento\Theme\Block\Html\Header\Logo;

class OdbLogo extends Logo
{

    /**
     * @var DonationHelper
     */
    protected $_scope;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\MediaStorage\Helper\File\Storage\Database $fileStorageHelper
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\MediaStorage\Helper\File\Storage\Database $fileStorageHelper,
        array $data = []
    ) {
    //    $this->_fileStorageHelper = $fileStorageHelper;
        $this->_scope = $scopeConfig;
        parent::__construct($context, $fileStorageHelper, $data);
    }

    public function getWelcome() {
        return $this->_scope->getValue('design/header/welcome');
    }
}
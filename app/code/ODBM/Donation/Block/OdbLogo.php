<?php
namespace ODBM\Donation\Block;

use Magento\Theme\Block\Html\Header\Logo;
use Magento\Store\Model\StoreManagerInterface;


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
        StoreManagerInterface $storeManager,
        array $data = []
    ) {
    //    $this->_fileStorageHelper = $fileStorageHelper;
        $this->_scope = $scopeConfig;
        $this->_storeManager = $storeManager;

        parent::__construct($context, $fileStorageHelper, $data);
    }

    public function getWelcome() {

        //get our current store
        $store = $this->_storeManager->getStore();

        //get value for store
        return $this->_scope->getValue(
            'design/header/welcome',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            //but you can put here another store(not current)
            $store
        );
    }
}
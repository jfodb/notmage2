<?php

namespace Dat\Thankyoupage\Block;
 
use Magento\Framework\App\ObjectManager;
 
/**
 * Cms block content block
 */
class CmsBlock extends \Magento\Cms\Block\Block
{ 
    protected function getDefaultData()
    {
        //throw new \Exception( print_r($this->getData(), true));
        // Your Data Logic
        return $this->getData();
    }
 
    /**
     * Prepare Content HTML
     * @return string
     */
    protected function _toHtml()
    {
        $blockId = $this->getBlockId();
        $html = '';
        if ($blockId) {
            $storeId = $this->_storeManager->getStore()->getId();
            $block = $this->_blockFactory->create();
            $block->setStoreId($storeId)->load($blockId);
            if ($block->isActive()) {
                if (!isset($this->_filter)) {
                    $this->_filter = ObjectManager::getInstance()->get('\Magento\Email\Model\Template\Filter');
                }
                
                $data = $this->getDefaultData();

                $this->_filter->setVariables($data);

                $html = $this->_filter->filter($block->getContent());
            }
        }
        
        return $html;
    }
}
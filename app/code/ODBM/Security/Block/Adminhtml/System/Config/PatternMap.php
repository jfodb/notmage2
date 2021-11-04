<?php

namespace ODBM\Security\Block\Adminhtml\System\Config;

use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;

class PatternMap extends AbstractFieldArray
{
    protected function _construct()
    {
        $this->addColumn('pattern', ['label' => __('Block emails that contain:')]);
        $this->_addAfter = false;
        $this->_addButtonLabel = __('Add');
        parent::_construct();
    }
}

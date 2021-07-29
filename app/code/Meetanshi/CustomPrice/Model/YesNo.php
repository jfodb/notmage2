<?php

namespace Meetanshi\CustomPrice\Model;

use Magento\Eav\Model\Entity\Attribute\Source\AbstractSource;

class YesNo extends AbstractSource
{
    protected $_options;

    public function getAllOptions()
    {
        if ($this->_options === null) {
            $this->_options = [
                ['value' => '0', 'label' => __('No')],
                ['value' => '1', 'label' => __('Yes')]
            ];
        }
        return $this->_options;
    }

    public function toOptionArray()
    {
        return array(
            array('value' => '0', 'label' => __('No')),
            array('value' => '1', 'label' => __('Yes'))
        );
    }
}
<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace ODBM\Donation\Block;

/**
 * Block representing link with two possible states.
 * "Current" state means link leads to URL equivalent to URL of currently displayed page.
 *
 * @api
 * @method string                          getLabel()
 * @method string                          getPath()
 * @method string                          getTitle()
 * @method null|array                      getAttributes()
 * @method null|bool                       getCurrent()
 * @method \Magento\Framework\View\Element\Html\Link\Current setCurrent(bool $value)
 */
class HeaderLink extends \Magento\Framework\View\Element\Html\Link\Current
{
    /**
     * Render block HTML
     *
     * @return string
     */
    protected function _toHtml()
    {
        // if (false != $this->getTemplate()) {
        //     return parent::_toHtml();
        // }

        $childHtml = $this->getChildHtml();
        $highlight = '';

        if ($this->getIsHighlighted()) {
            $highlight = ' current';
        }

        if ($this->isCurrent()) {
            $html = '<li class="nav item current">';
            $html .= '<strong>'
                . $this->escapeHtml((string)new \Magento\Framework\Phrase($this->getLabel()))
                . '</strong>';
            $html .= '</li>';
        } elseif ( !empty($childHtml) ) {
            $html = '<li class="has-submenu"> <a href="#"> <i class="submenu-arrow icon-chevron_left"></i>';
            $html .=  $this->escapeHtml((string)new \Magento\Framework\Phrase($this->getLabel())) . '</a>';
            $html .= '<ul class="submenu">' . $childHtml . '</ul></li>';
        }else {
            $html = '<li class="nav item' . $highlight . '"><a href="' . $this->escapeHtml($this->getHref()) . '"';

            $html .= $this->getTitle()
                ? ' title="' . $this->escapeHtml((string)new \Magento\Framework\Phrase($this->getTitle())) . '"'
                : '';
            $html .= $this->getAttributesHtml() .'>';

            if ($this->getIsHighlighted()) {
                $html .= '<strong>';
            }

            // Add icon if possible
            if ( !empty( $icon = $this->getData('icon') ) ) {
                $html .= '<i class="icon-' . $icon . '"></i>';
            }

            $html .= $this->escapeHtml((string)new \Magento\Framework\Phrase($this->getLabel()));

            if ($this->getIsHighlighted()) {
                $html .= '</strong>';
            }

            $html .= '</a></li>';
        }

        return $html;
    }

    /**
     * Generate attributes' HTML code
     *
     * @return string
     */
    private function getAttributesHtml() {
        $attributesHtml = '';
        $attributes = $this->getAttributes();
        if ($attributes) {
            foreach ($attributes as $attribute => $value) {
                $attributesHtml .= ' ' . $attribute . '="' . $this->escapeHtml($value) . '"';
            }
        }

        return $attributesHtml;
    }
}

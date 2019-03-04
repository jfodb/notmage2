<?php
/**
 * User: mdubinsky
 * Date: 2019-02-27
 */

namespace ODBM\Customtinymce\Plugin;

class Config
{

    protected $activeEditor;

    public function __construct(\Magento\Ui\Block\Wysiwyg\ActiveEditor $activeEditor)
    {
        $this->activeEditor = $activeEditor;
    }

    /**
     * Return WYSIWYG configuration
     *
     * @param \Magento\Ui\Component\Wysiwyg\ConfigInterface $configInterface
     * @param \Magento\Framework\DataObject $result
     * @return \Magento\Framework\DataObject
     */
    public function afterGetConfig(
        \Magento\Ui\Component\Wysiwyg\ConfigInterface $configInterface,
        \Magento\Framework\DataObject $result
    ) {

        // Get current wysiwyg adapter's path
        $editor = $this->activeEditor->getWysiwygAdapterPath();

        // Is the current wysiwyg tinymce v4?
        if(strpos($editor,'tinymce4Adapter')){
            if (($result->getDataByPath('settings/toolbar')) || ($result->getDataByPath('settings/plugins'))|| ($result->getDataByPath('settings/extended_valid_elements')) || ($result->getDataByPath('settings/valid_children')) || ($result->getDataByPath('settings/verify_html'))){
                // do not override ui_element config
                return $result;
            }

            $settings = $result->getData('settings');

            if (!is_array($settings)) {
                $settings = [];
            }

            // configure tinymce settings
            // configure toolbar (loaded toolbar with color selector, image inserter, link creator, and code button
            $settings['toolbar'] = 'undo redo | styleselect | fontsizeselect | forecolor backcolor | bold italic underline strikethrough | numlist bullist | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | table | link image | code';
            $settings['plugins'] = 'textcolor lists image link code';
            // allow <script> and <style> tags
            $settings['extended_valid_elements'] = 'script[*],style[*]';
            $settings['valid_children'] = '+body[script|style]';
            $settings['valid_elements'] = '*[*]';
            // to stop wrapping everything in <p> tags (including <scripts>). Also, fixes error if <style> is first element in HTML editor.
            // $settings['forced_root_block'] ='';

            $result->setData('settings', $settings);
            return $result;
    }
        else{ // don't make any changes if the current wysiwyg editor is not tinymce 4
            return $result;
        }
    }
}
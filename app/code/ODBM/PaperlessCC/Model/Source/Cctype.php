<?php
/**
 * Payment CC Types Source Model for Paperless
 *
 * @category    ODBM
 * @package     ODBM_PaperlessCC
 * @author      Our Daily Bread Ministries
 * @copyright   Our Daily Bread Ministries (https://ourdailybread.org)
 */
namespace ODBM\PaperlessCC\Model\Source;
class Cctype extends \Magento\Payment\Model\Source\Cctype
{
    /**
     * @return array
     */
    public function getAllowedTypes()
    {
        return array('VI', 'MC', 'AE', 'DI', 'JCB', 'OT');
    }
}
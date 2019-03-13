<?php
/**
 * User: mdubinsky
 * Date: 2019-03-13
 */

namespace Dat\Thankyouemail\Model\Order\Email\Container;


class Template extends \Magento\Sales\Model\Order\Email\Container\Template
{

    protected $customTemplateId;

    /**
     * @param $tempID
     * set customTemplateId
     */
    public function setCustomTemplateId($tempID)
    {
        $this->customTemplateId = $tempID;
    }


    /**
     * @return int of customTemplateId
     */
    public function getCustomTemplateId()
    {
        return $this->customTemplateId;
    }

}
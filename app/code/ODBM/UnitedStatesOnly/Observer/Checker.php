<?php
/*
 * USLF-2079
 * Setting in Magento Admin (Stores > Configuration > General > General > Allow United States Only),
 * If enabled, Observer redirects traffic to the URL specified
 */

namespace ODBM\UnitedStatesOnly\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use ODBM\UnitedStatesOnly\Helper\Data;
use Magento\Framework\App\ResponseFactory;

class Checker implements ObserverInterface
{
    protected $helper;
    protected $_responseFactory;

    public function __construct(
        Data $helper,
        ResponseFactory $responseFactory
    ) {
        $this->helper = $helper;
        $this->_responseFactory = $responseFactory;
    }

    public function execute(Observer $observer)
    {
        if ($this->helper->isEnabled()) {
            $getRedirectUrl = $this->helper->getRedirectUrl();

            if (!empty($getRedirectUrl)) {
                $redirect = $this->_responseFactory->create();
                $redirect->setRedirect($getRedirectUrl)->sendResponse();
                die();
            }
        }

        return $this;
    }
}

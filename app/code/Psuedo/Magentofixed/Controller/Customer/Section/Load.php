<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Psuedo\Magentofixed\Controller\Customer\Section;

use Magento\Framework\App\Action\HttpGetActionInterface as HttpGetActionInterface;
use Magento\Customer\CustomerData\Section\Identifier;
use Magento\Customer\CustomerData\SectionPoolInterface;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;

/**
 * Customer section controller
 */
class Load extends \Magento\Customer\Controller\Section\Load
{
    /**
     * @var \Magento\Framework\Escaper
     */
    protected $_logger;
    protected $escaper;

    /**
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param Identifier $sectionIdentifier
     * @param SectionPoolInterface $sectionPool
     * @param Escaper $escaper
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        Identifier $sectionIdentifier,
        SectionPoolInterface $sectionPool,
        \Psr\Log\LoggerInterface $log,
        \Magento\Framework\Escaper $escaper = null
    ) {
	    parent::__construct($context, $resultJsonFactory, $sectionIdentifier, $sectionPool, $escaper);

        $this->_logger = $log;
        $this->escaper = $escaper ?: $this->_objectManager->get(\Magento\Framework\Escaper::class);
    }

    /**
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        /** @var \Magento\Framework\Controller\Result\Json $resultJson */
        $resultJson = $this->resultJsonFactory->create();
        $resultJson->setHeader('Cache-Control', 'max-age=0, must-revalidate, no-cache, no-store', true);
        $resultJson->setHeader('Pragma', 'no-cache', true);
        try {
            $sectionNames = $this->getRequest()->getParam('sections');
            $sectionNames = $sectionNames ? array_unique(\explode(',', $sectionNames)) : null;

            //magento 2.2 flat
            $updateSectionId = $this->getRequest()->getParam('update_section_id');
            if ('false' === $updateSectionId) {
                $updateSectionId = false;
            }

            //magento 2.3 flag
	        $forceNewSectionTimestamp = $this->getRequest()->getParam('force_new_section_timestamp');
	        if ('false' === $forceNewSectionTimestamp) {
		        $forceNewSectionTimestamp = false;
	        }

	        $updateSectionId = $forceNewSectionTimestamp || $updateSectionId;

            $response = $this->sectionPool->getSectionsData($sectionNames, (bool)$updateSectionId);
        } catch (\Exception $e) {
            $resultJson->setStatusHeader(
                //we just caught general exception in database operations, lets not blame the browser below.
                \Zend\Http\Response::STATUS_CODE_500,
                \Zend\Http\AbstractMessage::VERSION_11,
                'Internal Server Error'
            );
            //don't hide that this happened, report it
            $this->_logger->critical("ERR loading Customer Section");
            $this->_logger->critical($e);
            $response = ['message' => $this->escaper->escapeHtml($e->getMessage())];
        }

        return $resultJson->setData($response);
    }
}

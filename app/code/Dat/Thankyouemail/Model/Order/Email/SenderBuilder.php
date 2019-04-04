<?php
/**
 * User: mdubinsky
 * Date: 2019-03-12
 */

namespace Dat\Thankyouemail\Model\Order\Email;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Sales\Model\Order\Email\Container\IdentityInterface;
use Magento\Sales\Model\Order\Email\Container\Template;
use Magento\Framework\Mail\Template\TransportBuilderByStore;

class SenderBuilder extends \Magento\Sales\Model\Order\Email\SenderBuilder
{
    /**
     * @var Template
     */
    protected $templateContainer;

    /**
     * @var IdentityInterface
     */
    protected $identityContainer;

    /**
     * @var TransportBuilder
     */
    protected $transportBuilder;

    /**
     * @var TransportBuilderByStore
     */
    protected $transportBuilderByStore;

    /**
     * @var customTemplateId
     */
    protected $customTemplateId;

    /**
     * @param Template $templateContainer
     * @param IdentityInterface $identityContainer
     * @param TransportBuilder $transportBuilder
     * @param TransportBuilderByStore|null $transportBuilderByStore
     */
    public function __construct(
        Template $templateContainer,
        IdentityInterface $identityContainer,
        TransportBuilder $transportBuilder,
        TransportBuilderByStore $transportBuilderByStore = null
    ) {
        $this->templateContainer = $templateContainer;
        $this->identityContainer = $identityContainer;
        $this->transportBuilder = $transportBuilder;
        $this->transportBuilderByStore = $transportBuilderByStore ?: ObjectManager::getInstance()->get(
            TransportBuilderByStore::class
        );
    }

    /**
     * Prepare and send email message
     *
     * @return void
     */
    protected function configureEmailTemplate()
    {
        // if exists, get the customTemplateId from the templateContainer
        $customTemplateId = empty($this->templateContainer->getCustomTemplateId())?$this->templateContainer->getTemplateId():$this->templateContainer->getCustomTemplateId();
        // set the transportBuilder's template
        $this->transportBuilder->setTemplateIdentifier($customTemplateId);
        $this->transportBuilder->setTemplateOptions($this->templateContainer->getTemplateOptions());
        $this->transportBuilder->setTemplateVars($this->templateContainer->getTemplateVars());
        $this->transportBuilderByStore->setFromByStore(
            $this->identityContainer->getEmailIdentity(),
            $this->identityContainer->getStore()->getId()
        );
    }
}
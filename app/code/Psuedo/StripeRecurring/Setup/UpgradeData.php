<?php

namespace Psuedo\StripeRecurring\Setup;

use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Eav\Setup\EavSetupFactory as EavSetupFactory;

class UpgradeData implements UpgradeDataInterface
{
    /**
     * @var   \Magento\Eav\Setup\EavSetupFactory
     */
    private $eavSetupFactory;

    public function __construct(
        \Magento\Eav\Setup\EavSetupFactory $eavEavSetupFactory
    ) {
        $this->eavSetupFactory = $eavEavSetupFactory;
    }

    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        if (version_compare($context->getVersion(), '0.1.1') < 0) {
            $this->updateSubscriptionAttributes($setup);
        }

        $setup->endSetup();
    }

    public function updateSubscriptionAttributes($setup)
    {
        $eavSetup = $this->eavSetupFactory->create();
        $eavSetup->updateAttribute('catalog_product', 'stripe_sub_enabled', 'apply_to', 'donation');
        $eavSetup->updateAttribute('catalog_product', 'stripe_sub_interval', 'apply_to', 'donation');
        $eavSetup->updateAttribute('catalog_product', 'stripe_sub_interval_count', 'apply_to', 'donation');
        $eavSetup->updateAttribute('catalog_product', 'stripe_sub_trial', 'apply_to', 'donation');
        $eavSetup->updateAttribute('catalog_product', 'stripe_sub_initial_fee', 'apply_to', 'donation');
    }
}

<?php
namespace Dat\Thankyouemail\Setup;

use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

class UpgradeData implements UpgradeDataInterface
{
    private $eavSetupFactory;

    public function __construct(EavSetupFactory $eavSetupFactory)
    {
        $this->eavSetupFactory = $eavSetupFactory;
    }

    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {

        $eavSetup = $this->eavSetupFactory->create(['setup' => $setup]);


        if (version_compare($context->getVersion(), '0.1.2', '<')) {

            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY,
                'thank_you_email_template',
                [
                    'backend' => '',
                    'type' => 'int',
                    'frontend' => '',
                    'input' => 'select',
                    'label' => 'Choose a custom "Thank You" email',
                    'class' => '',
                    'source' => 'Magento\Config\Model\Config\Source\Email\Template',
                    'required' => false,
                    'user_defined' => true,
                    'unique' => false,
                    'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
                    'input_renderer' => '',
                    'searchable' => false,
                    'filterable' => false,
                    'comparable' => false,
                    'visible_on_front' => false,
                    'visible' => true,
                    'is_html_allowed_on_front' => false,
                    'visible_in_advanced_search' => false,
                    'used_in_product_listing' => false,
                    'used_for_sort_by' => false,
                    'apply_to' => 'donation',
                    'used_for_promo_rules' => false,
                    'is_used_in_grid' => false,
                    'is_visible_in_grid' => false,
                    'is_filterable_in_grid' => false,
                    'group' => 'Content'
                ]
            );
        }
    }
}
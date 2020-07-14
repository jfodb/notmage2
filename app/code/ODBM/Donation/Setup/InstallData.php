<?php
namespace ODBM\Donation\Setup;

use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

class InstallData implements InstallDataInterface
{
	private $eavSetupFactory;

	public function __construct(EavSetupFactory $eavSetupFactory)
	{
		$this->eavSetupFactory = $eavSetupFactory;
	}

	public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
	{
		$eavSetup = $this->eavSetupFactory->create(['setup' => $setup]);

		$eavSetup->addAttribute(
			\Magento\Catalog\Model\Product::ENTITY,
			'in_cause_pool',
			[
				'type' => 'int',
				'backend' => '',
				'frontend' => '',
				'label' => 'Use in Pool',
				'input' => 'boolean',
				'note'  => 'Should this product be a part of the random pool of donations?',
				'class' => '',
				'source' => '',
				'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
				'visible' => true,
				'required' => false,
				'user_defined' => false,
				'default' => '',
				'searchable' => false,
				'filterable' => false,
				'comparable' => false,
				'visible_on_front' => false,
				'used_in_product_listing' => true,
				'unique' => false,
				'apply_to' => 'donation'
			]	
		);
		//Add one_time_donation option to product
		$eavSetup->addAttribute(
			\Magento\Catalog\Model\Product::ENTITY,
			'one_time_donation',
			[
				'type' => 'int',
				'backend' => '',
				'frontend' => '',
				'label' => 'One time donation',
				'input' => 'boolean',
				'note'  => 'Should this product remove the monthly donation option?',
				'class' => '',
				'source' => '',
				'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
				'visible' => true,
				'required' => false,
				'user_defined' => false,
				'default' => '',
				'searchable' => false,
				'filterable' => false,
				'comparable' => false,
				'visible_on_front' => false,
				'used_in_product_listing' => true,
				'unique' => false,
				'apply_to' => 'donation'
			]
		);

		$eavSetup->addAttribute(
			\Magento\Catalog\Model\Product::ENTITY,
			'use_custom_template',
			[
				'type' => 'int',
				'backend' => '',
				'frontend' => '',
				'label' => 'Use Custom Template',
				'input' => 'boolean',
				'note'  => 'Are you overriding the template with a CMS Block?',
				'class' => '',
				'source' => '',
				'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
				'visible' => true,
				'required' => false,
				'user_defined' => false,
				'default' => '',
				'searchable' => false,
				'filterable' => false,
				'comparable' => false,
				'visible_on_front' => false,
				'used_in_product_listing' => true,
				'unique' => false,
				'apply_to' => 'donation'
			]	
		);

		$eavSetup->addAttribute(
			\Magento\Catalog\Model\Product::ENTITY,
			'promo_text',
			[
				'type' => 'text',
				'backend' => '',
				'frontend' => '',
				'label' => 'Promo Text',
				'input' => 'text',
				'class' => '',
				'note' => 'Use this value to override the title on the template',
				'source' => '',
				'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
				'visible' => true,
				'required' => false,
				'user_defined' => false,
				'default' => '',
				'searchable' => true,
				'filterable' => false,
				'comparable' => false,
				'visible_on_front' => false,
				'used_in_product_listing' => true,
				'unique' => false,
				'apply_to' => 'donation'
			]
		);

		$eavSetup->addAttribute(
            \Magento\Catalog\Model\Product::ENTITY,
            'thank_you_page_block',
					[
						'backend' => '',
						'type' => 'int',
						'frontend' => '',
						'input' => 'select',
						'label' => 'Choose a custom "Thank You" page',
						'class' => '',
						'source' => 'Magento\Catalog\Model\Category\Attribute\Source\Page',
						'required' => false,
						'user_defined' => true,
						'unique' => false,
						'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
						'input_renderer' => '',
						'searchable' => true,
						'filterable' => false,
						'comparable' => false,
						'visible_on_front' => false,
						'is_html_allowed_on_front' => false,
						'visible_in_advanced_search' => false,
						'used_in_product_listing' => true,
						'used_for_sort_by' => false,
						'apply_to' => '',						
						'used_for_promo_rules' => false,
						'is_used_in_grid' => false,
						'is_visible_in_grid' => false,
						'is_filterable_in_grid' => false,
						'group' => 'Content'
            ]
        );
	}
}
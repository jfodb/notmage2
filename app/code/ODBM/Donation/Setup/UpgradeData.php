<?php
namespace ODBM\Donation\Setup;

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
		
		if (version_compare($context->getVersion(), '1.0.2', '<')) {
			

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
		}

			if (version_compare($context->getVersion(), '1.0.3', '<')) {
				$eavSetup->updateAttribute(
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

			//Add one-time-donation option to product
			$eavSetup->addAttribute(
				\Magento\Catalog\Model\Product::ENTITY,
				'one-time-donation',
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
		}
	}
}
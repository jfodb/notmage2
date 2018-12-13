<?php
/**
 * Created by PhpStorm.
 * User: ppostma
 * Date: 12/12/18
 * Time: 3:16 PM
 */

namespace Psuedo\Mpxdownload\Setup;

use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

class UpgradeData implements UpgradeDataInterface
{
	/**
	 * {@inheritdoc}
	 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
	 */
	public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
	{
		$setup->startSetup();
		if ($context->getVersion()
			&& version_compare($context->getVersion(), '1.1.0') < 0
		) {
			$table = 
			$setup->getConnection()
				->newTable($setup->getTable('mpx_flat_orders'))
				
				->addColumn(
					'order_id',
					\Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
					null,
					['identity' => true, 'unsigned' => true, 'nullable'=> false, 'primary'=>true],
					'order parent entity ID'
				)
				->addColumn(
					'payment',
					\Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
					'2M',
					['nullable'=>false, 'default'=> ''],
					'json encoded data from the payment record'
				)
				->addColumn(
					'addresses',
					\Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
					'2M',
					['nullable'=>false, 'default'=> ''],
					'json encoded data of the addresses'
				)
				->addColumn(
					'order_grid',
					\Magento\Framework\Db\Ddl\Table::TYPE_TEXT,
					255,
					['nullable' => false, 'default' => ''],
					'Information from the sales_order_grid'
				)
				->addColumn(
					'items',
					\Magento\Framework\Db\Ddl\Table::TYPE_TEXT,
					'2M',
					['nullable' => false, 'default' => ''],
					'the json data of ordered items'
				)
			-> setComment('Cache for order data to reduce load time for MPX');
			
			$setup->getConnection()->createTable($table);
		}
		$setup->endSetup();
	}
}
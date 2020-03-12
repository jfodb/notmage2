<?php
/**
 * User: mdubinsky
 * Date: 3/11/20
 *
 * Upgrade script simply "resaves" all products. For timeout purposes, it is limited to what is scoped in the $i values
 * Defualted to not run. Must change below $i values, increase module version in /etc/module.xml, and run bin/magento setup:upgrade
 * ??? Consider turning off "index on save" to see if it improves performance ???
 *
 */

namespace Vendor\Migration\Setup;

use Magento\Framework\App\State;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Framework\Setup\{UpgradeDataInterface, ModuleDataSetupInterface, ModuleContextInterface};


class UpgradeData implements UpgradeDataInterface
{
    protected $productCollection;
    protected $state;

    public function __construct(Collection $productCollection, State $state) {
        $this->productCollection = $productCollection;
        $this->state = $state;
    }

    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();
        $this->state->setAreaCode('frontend');

        $products = $this->productCollection->load();
        $i = 1;
        foreach ($products as $product) {
            // configure $i values and when to "break", so the script doesn't timeout. Previously ran successfully for 500.
            if ($i > 0) {
                break;
            }

            // configure $i values here on where to start the re-saving products
            //
            // received error if no url_key was set - Consider filtering for values with url key
            // (possible resource: https://magento.stackexchange.com/questions/244496/how-to-get-product-by-url-key-and-store-id-magento-2_
            if ($i > 0) {
                echo 'Counter: ' . $i;
                echo "\r\nProduct: " . $product->getName() . ' ID: ' . $product->getId() . "\r\n";
                $product->save();
            }
            $i++;
        }
        $setup->endSetup();
    }

}

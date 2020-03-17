<?php
/**
 * User: mdubinsky
 * Date: 3/17/20
 */

namespace ODBM\ODBP\ViewModel;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;

class ProductsBySku
{
    protected $productRepository;
    protected $product;
    protected $productArray = [];

    public function __construct(
        ProductRepositoryInterface $productRepository
    ) {
        $this->productRepository = $productRepository;
    }

    public function getProductsBySku($_productFormatSkus)
    {
        foreach ($_productFormatSkus as $_sku) {

            try{
                $product = $this->productRepository->get($_sku);
            } catch (NoSuchEntityException $e) {
                // do nothing and continue
            }

            if($product){
                $productArray[] = $product;
            }
        }
        return $productArray;
    }
}

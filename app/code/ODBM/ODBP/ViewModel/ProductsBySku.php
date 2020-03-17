<?php
/**
 * User: mdubinsky
 * Date: 3/17/20
 */

namespace ODBM\ODBP\ViewModel;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Psr\Log\LoggerInterface;

class ProductsBySku implements \Magento\Framework\View\Element\Block\ArgumentInterface
{
    protected $productRepository;
    protected $product;
    protected $_logger;
    protected $productArray = [];

    public function __construct(
        ProductRepositoryInterface $productRepository,
        LoggerInterface $logger
    ) {
        $this->productRepository = $productRepository;
        $this->_logger = $logger;
    }

    public function getProductsBySku($_productFormatSkus)
    {
        $this->_logger->debug('Enter getProductsBySku: ');

        foreach ($_productFormatSkus as $_sku) {
            try{
                $product = $this->productRepository->get($_sku);
                $this->_logger->debug($product);
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

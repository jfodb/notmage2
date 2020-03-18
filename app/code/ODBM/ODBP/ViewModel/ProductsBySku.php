<?php
/**
 * User: mdubinsky
 * Date: 3/17/20
 */

namespace ODBM\ODBP\ViewModel;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\FilterGroupBuilder;
use Magento\Framework\Api\SearchCriteriaBuilder;

class ProductsBySku implements \Magento\Framework\View\Element\Block\ArgumentInterface
{
    protected $productRepository;
    protected $searchCriteriaBuilder;
    protected $filterBuilder;
    protected $filterGroupBuilder;
    protected $product;
    protected $productFormats = [];

    public function __construct(
        ProductRepositoryInterface $productRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        FilterBuilder $filterBuilder,
        FilterGroupBuilder $filterGroupBuilder
    ) {
        $this->productRepository = $productRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->filterBuilder = $filterBuilder;
        $this->filterGroupBuilder = $filterGroupBuilder;
    }

    public function getProductsBySku($_productFormatSkus)
    {

        // TODO: "AND" isn't working
        // TODO: Showing disabled products

        $filters = [];
        $filter_group = [];

        // build basic search for enabled products
        $this->searchCriteriaBuilder->addFilter('status', Status::STATUS_ENABLED, 'eq');

        // add "or" statements for each SKU
        foreach ($_productFormatSkus as $_sku) {
            $filters[] = $this->filterBuilder->setField('sku')
                ->setValue($_sku)
                ->setConditionType('eq')
                ->create();
        }

        $filter_group[] = $this->filterGroupBuilder->setFilters($filters)->create();
        $searchCriteria = $this->searchCriteriaBuilder->create()->setFilterGroups($filter_group);

        $searchResults = $this->productRepository->getList($searchCriteria);
        $productFormats = $searchResults->getItems();

        if (isset($productFormats) && is_array($productFormats)) {
            return $productFormats;
        } else {
            return [];
        }
    }
}

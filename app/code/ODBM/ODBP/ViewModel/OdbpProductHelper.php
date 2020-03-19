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

class OdbpProductHelper implements \Magento\Framework\View\Element\Block\ArgumentInterface
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
    )
    {
        $this->productRepository = $productRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->filterBuilder = $filterBuilder;
        $this->filterGroupBuilder = $filterGroupBuilder;
    }

    public function getProductsBySku($_productFormatSkus)
    {
        // filter to get enabled products
        $filter1 = $this->filterBuilder
            ->setField("status")
            ->setValue(Status::STATUS_ENABLED)
            ->setConditionType("eq")
            ->create();

        $filterGroup1 = $this->filterGroupBuilder->setFilters([$filter1])->create();

        // filter to get matching skus
        $filter2 = $this->filterBuilder->setField('sku')
            ->setValue($_productFormatSkus)
            ->setConditionType('in')
            ->create();

        $filterGroup2 = $this->filterGroupBuilder->setFilters([$filter2])->create();

        // build search criteria by setting filter groups (AND)
        $searchCriteria = $this->searchCriteriaBuilder->create()->setFilterGroups([$filterGroup1, $filterGroup2]);

        $searchResults = $this->productRepository->getList($searchCriteria);

        $productFormats = $searchResults->getItems();

        // return valid format or empty array
        if (isset($productFormats) && is_array($productFormats)) {
            return $productFormats;
        } else {
            return [];
        }
    }

    public function getSongSamples($songsString)
    {
        if ($songsString) {
            $songsArray = explode("\n", $songsString);
            $titleAndLink = array();
            foreach ($songsArray as $song) {
                $titleAndLink[] = str_getcsv($song, ";", "", "\n");
            }
            return $titleAndLink;
        } else {
            return false;
        }

    }
}

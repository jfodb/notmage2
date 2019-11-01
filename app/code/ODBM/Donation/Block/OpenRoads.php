<?php
/**
 * User: mdubinsky
 * Date: 2019-10-31
 */

namespace ODBM\Donation\Block;
//use Magento\Sales\Model\Order\ItemFactory as DonationsCollectionFactory;
use Experius\DonationProduct\Model\ResourceModel\Donations\CollectionFactory as DonationsCollectionFactory;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Magento\Framework\View\Element\Template\Context;

class OpenRoads extends \Magento\Framework\View\Element\Template
{
    /**
     * @var DonationsCollectionFactory
     */
    protected $donationsCollectionFactory;

    public function __construct(
        DonationsCollectionFactory $donationsCollectionFactory,
        Context $context,
        array $data = []
    ){
        $this->donationsCollectionFactory = $donationsCollectionFactory;

        parent::__construct(
            $context,
            $data
        );
    }

    public function getTotalDonationsBySku($skuId)
    {
        $sum = 0;

        /** @var \Experius\DonationProduct\Model\Donations $donations */
        $collectionFactory = $this->donationsCollectionFactory->create()->addFieldToFilter('sku', $skuId);
        foreach ($collectionFactory as $donation){
            $sum = $sum + $donation->getAmount();
        }

        return $sum;
    }
}
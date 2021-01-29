<?php

namespace Dat\DonationStep\Block\Checkout;

use Experius\DonationProduct\Helper\Data as DonationHelper;
use Experius\DonationProduct\Block\Donation\ListProductFactory as DonationProductsFactory;

class LayoutProcessor implements \Magento\Checkout\Block\Checkout\LayoutProcessorInterface
{
    /**
     * @var DonationHelper
     */
    private $donationHelper;

    /**
     * @var \Experius\DonationProduct\Block\Donation\ListProduct
     */
    private $donationProductsFactory;

    /**
     * LayoutProcessor constructor.
     * @param DonationHelper $donationHelper
     * @param DonationProducts $donationProducts
     */
    public function __construct(
        DonationHelper $donationHelper,
        DonationProductsFactory $donationProductsFactory
    ) {
        $this->donationHelper = $donationHelper;
        $this->donationProductsFactory = $donationProductsFactory;
    }

    public function process($result)
    {

        if (isset($result['components']['checkout']['children']['steps']['children']
                ['check-donation-step'])) {
            $result['components']['checkout']['children']['steps']['children']
            ['check-donation-step']['children']['donation-list-form'] = $this->getDonationForm('checkout.donation.list');
        }

        return $result;
    }

    /**
     * @param $scope
     * @return array
     */
    public function getDonationForm($nameInLayout)
    {
        $donationProductsBlock = $this->donationProductsFactory->create();
        $donationProductsBlock->setTemplate('donation.phtml');
        $donationProductsBlock->setNameInLayout($nameInLayout);
        $donationProductsBlock->setAjaxRefreshOnSuccess(true);

        $content = $donationProductsBlock->toHtml();
        $content .= "<script type=\"text/javascript\">jQuery('body').trigger('contentUpdated');</script>";

        $donationForm =
            [
                'component' => 'Magento_Ui/js/form/components/html',
                'config' => [
                    'content'=> $content
                ]
            ];

        return $donationForm;
    }
}
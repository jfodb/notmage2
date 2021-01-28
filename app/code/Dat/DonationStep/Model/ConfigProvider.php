<?php

namespace Dat\DonationStep\Model;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\View\LayoutInterface;

class ConfigProvider implements ConfigProviderInterface
{
    /** @var LayoutInterface  */
    protected $_layout;
    protected $cmsBlock;

    public function __construct(LayoutInterface $layout)
    {
        $this->_layout = $layout;
    }

    public function getConfig()
    {
        $block = $this->_layout->createBlock('Experius\DonationProduct\Block\Donation\ListProduct')
            ->setTemplate('Experius_DonationProduct::donation.phtml')
            ->toHtml();

        return [
            'checkout_donation_block' => $block
        ];
    }
}

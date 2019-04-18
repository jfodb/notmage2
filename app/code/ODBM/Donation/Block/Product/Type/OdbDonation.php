<?php
/**
 * User: mdubinsky
 * Date: 2019-03-05
 */

namespace ODBM\Donation\Block\Product\Type;

use Magento\Catalog\Block\Product\AbstractProduct;
use Magento\Catalog\Block\Product\Context;
use Experius\DonationProduct\Helper\Data as DonationHelper;
use Magento\Store\Model\StoreManagerInterface;

class OdbDonation extends AbstractProduct
{

    /**
     * @var DonationHelper
     */
    protected $donationHelper;
    protected $_storeManager;

    /**
     * Donation constructor.
     * @param Context $context
     * @param DonationHelper $donationHelper
     * @param array $data
     */
    public function __construct(
        Context $context,
        DonationHelper $donationHelper,
        StoreManagerInterface $storeManager,
        array $data = []
    ) {
        $this->donationHelper = $donationHelper;
        $this->_storeManager = $storeManager;
        parent::__construct(
            $context,
            $data
        );
    }

	public function getBackgroundImage($type) {
		$product = $this->getProduct();
		$image = $product->getData('image');

        switch($type) {
            case 'small':
                $type = 'product_page_background_image_small';
                break;
            case 'medium':
                $type = 'product_page_background_image_medium';
                break;
            case 'large':
            default:
             $type = 'product_page_background_image_large';
                break;
        }

		$imageUrl = $this->_imageHelper
			->init($product, $type)
			->setImageFile($product->getFile())
			->getUrl();

		$image = $product->getImage();

		return $imageUrl;
    }

    /**
     * Check to see whether to use our template
     * @return boolean 
     */
    public function useCustomTemplate() {
        $product = $this->getProduct();

        return ( $product->getData( 'use_custom_template') == 1 );
    }

    /**
     * Get message
     * 
     * Defaults to title, but can also be promo attibute field
     */
    public function getPromoTitle() {
        $product = $this->getProduct();

        $promo_text = $product->getData( 'promo_text' );

        if ( !empty($promo_text) ) {
            $title = $promo_text;
        } else {
            $title = $product->getName();
        }

        return $title;
    }
    
    /**
     * @return int
     */
    public function getMinimalAmount()
    {
        return $this->donationHelper->getMinimalAmount($this->getProduct());
    }

    /**
     * @return mixed
     */
    public function getConfiguratorCode()
    {
        return $this->donationHelper->getConfiguratorCode($this->getProduct());
    }

    /**
     * @return mixed
     */
    public function getCurrencySymbol()
    {
        return $this->donationHelper->getCurrencySymbol();
    }

    /**
     * @return array
     */
    public function getFixedAmounts()
    {
        return $this->donationHelper->getFixedAmounts();
    }

    /**
     * @return string
     */
    public function getMinimalDonationAmount()
    {
        return $this->donationHelper->getCurrencySymbol() . ' ' . $this->donationHelper->getMinimalAmount($this->getProduct());
    }

    /**
     * Get current store currency code
     * @return string
     */
    public function getCurrentCurrencyCode()
    {
        return $this->_storeManager->getStore()->getCurrentCurrencyCode();
    }
}
<?php
namespace Dat\BillingAddress\Plugin\Checkout\Block\Checkout\AttributeMerger;

use Magento\Customer\Model\AttributeMetadataDataProvider;
use Magento\Ui\Component\Form\AttributeMapper;
use Magento\Checkout\Block\Checkout\AttributeMerger;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\StoreManagerInterface;

class Plugin
{
  /**
  * @var AttributeMetadataDataProvider
  */
  public $attributeMetadataDataProvider;

  /**
  * @var AttributeMapper
  */
  public $attributeMapper;

  /**
  * @var AttributeMerger
  */
  public $merger;

  /**
  * @var CheckoutSession
  */
  public $checkoutSession;

  /**
  * @var null
  */
  public $quote = null;

  protected $scopeConfig;

  protected $_storeManager;

  /**
  * LayoutProcessor constructor.
  *
  * @param AttributeMetadataDataProvider $attributeMetadataDataProvider
  * @param AttributeMapper $attributeMapper
  * @param AttributeMerger $merger
  * @param CheckoutSession $checkoutSession
  */
  public function __construct(
    AttributeMetadataDataProvider $attributeMetadataDataProvider,
    AttributeMapper $attributeMapper,
    AttributeMerger $merger,
    CheckoutSession $checkoutSession,
    ScopeConfigInterface $scopeConfig,
    StoreManagerInterface $storeManager
    ) {
      $this->attributeMetadataDataProvider = $attributeMetadataDataProvider;
      $this->attributeMapper = $attributeMapper;
      $this->merger = $merger;
      $this->checkoutSession = $checkoutSession;
      $this->scopeConfig = $scopeConfig;
      $this->_storeManager = $storeManager;
    }

    /**
    * Get Quote
    *
    * @return \Magento\Quote\Model\Quote|null
    */
    public function getQuote()
    {
      if (null === $this->quote) {
        $this->quote = $this->checkoutSession->getQuote();
      }


	    //spread a missing  email address around.
	    //https://github.com/magento/magento2/issues/27681
	    if($this->quote->getBillingAddress()->getEmail() === null && $this->quote->getShippingAddress()->getEmail() !== null)
		    $this->quote->getBillingAddress()->setEmail( $this->quote->getShippingAddress()->getEmail() );
	    if($this->quote->getCustomerEmail() === null && $this->quote->getBillingAddress()->getEmail() !== null)
		    $this->quote->setCustomerEmail( $this->quote->getBillingAddress()->getEmail() );


      return $this->quote;
    }

    /**
    * @param \Magento\Checkout\Block\Checkout\LayoutProcessor $subject
    * @param array $jsLayout
    * @return array
    */
    public function aroundProcess(
      \Magento\Checkout\Block\Checkout\LayoutProcessor $subject,
      \Closure $proceed,
      array $jsLayout
      ) {

        $jsLayoutResult = $proceed($jsLayout);

        // USLF-1796: Fix billing address bug on non-donation store views
        $store = (int)$this->_storeManager->getStore()->getId() ?? 0;

        // Only change billing address of Donations as this was the module's initial intention
        if ($store === 13) {
            if (isset($jsLayoutResult['components']['checkout']['children']['steps']['children']['billing-step']['children']
                ['billingAddress']['children']['billing-address-fieldset'])) {

                $jsLayoutResult['components']['checkout']['children']['steps']['children']['billing-step']
                ['children']['billingAddress']['children']['billing-address-fieldset']['children']['street']['children'][0]['label'] = __('Address');
                $jsLayoutResult['components']['checkout']['children']['steps']['children']['billing-step']
                ['children']['billingAddress']['children']['billing-address-fieldset']['children']['street']['children'][1]['label'] = __('Address 2');

                $jsLayoutResult['components']['checkout']['children']['steps']['children']['billing-step']
                ['children']['billingAddress']['children']['billing-address-fieldset']['children']['postcode']['label'] = __('Zip');

                $elements = $this->getAddressAttributes();
                $jsLayoutResult['components']['checkout']['children']['steps']['children']['billing-step']
                ['children']['billingAddress']['children']['billing-address'] = $this->getCustomBillingAddressComponent($elements);
            } else {
                $jsLayoutResult['components']['checkout']['children']['steps']['children']['billing-step']['children']['payment']['children']['payments-list']['children']['stripe_payments-form']['children']['form-fields']['children']['street']['children'][0]['label'] = __('Address');
                $jsLayoutResult['components']['checkout']['children']['steps']['children']['billing-step']['children']['payment']['children']['payments-list']['children']['stripe_payments-form']['children']['form-fields']['children']['street']['children'][1]['label'] = __('Address 2');

                $jsLayoutResult['components']['checkout']['children']['steps']['children']['billing-step']['children']['payment']['children']['payments-list']['children']['stripe_payments-form']['children']['form-fields']['children']['postcode']['label'] = __('Zip');
            }
        }
        
        return $jsLayoutResult;
      }
}
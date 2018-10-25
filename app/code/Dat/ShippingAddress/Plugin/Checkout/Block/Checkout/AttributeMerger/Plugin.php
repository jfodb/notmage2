<?php
namespace Dat\ShippingAddress\Plugin\Checkout\Block\Checkout\AttributeMerger;

class Plugin
{
  public function afterMerge(\Magento\Checkout\Block\Checkout\AttributeMerger $subject, $result)
  {
    if (array_key_exists('street', $result)) {
      $result['street']['children'][0]['placeholder'] = __('Street Address');
      $result['street']['children'][1]['placeholder'] = __('Apt, suite, building');
    }

    return $result;
  }
}

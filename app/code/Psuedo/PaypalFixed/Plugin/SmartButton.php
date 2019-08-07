<?php
/**
 * User: mdubinsky
 * Date: 2019-08-06
 * Description: Plugin sets standard PayPal button styles that aren't available in Magento Admin and align with our existing design
 * DISABLED in app/code/Psuedo/PaypalFixed/etc/di.xml because in-context experience is set to 'OFF"
 */

namespace Psuedo\PaypalFixed\Plugin;

class SmartButton
{
    /**
     * Get smart button config
     * Hard codes the desired payPal button style since these dynamic options are not available in the Admin
     * @return array
     */
    public function afterGetConfig(\Magento\Paypal\Model\SmartButtonConfig $subject, $result): array
    {
        $result['styles'] = $this->getButtonStylesPlugin();
        return $result;
    }

    private function getButtonStylesPlugin(): array
    {
            $styles['layout'] = 'horizontal';
            $styles['color'] = 'white';
            $styles['shape'] =  'pill';
            $styles['size'] =  'responsive';
            $styles['label'] = 'paypal';
            $styles['height'] = 38;
            $styles['tagline'] = false;
            $styles['fundingicons'] = false;

        return $styles;
    }
}
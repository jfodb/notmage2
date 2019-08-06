<?php
/**
 * User: mdubinsky
 * Date: 2019-08-06
 */

namespace Psuedo\PaypalFixed\Plugin;
use Psr\Log\LoggerInterface;

class SmartButton
{
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }
    /**
     * Get smart button config
     *
     * @param string $page
     * @return array
     */
    public function afterGetConfig(\Magento\Paypal\Model\SmartButtonConfig $subject, $result, $page): array
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
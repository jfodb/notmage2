<?php
/**
 * User: mdubinsky
 * Date: 3/17/20
 * Helper function to return a user's country
 */
namespace ODBM\ODBP\ViewModel;

class CountryHelper implements \Magento\Framework\View\Element\Block\ArgumentInterface
{
    public function getCountry()
    {
        $code = "";

        if (isset($_SERVER["HTTP_X_COUNTRY"])) {
            $code = $_SERVER["HTTP_X_COUNTRY"];
        }

        if (isset($_SERVER['HTTP_CLOUDFRONT_VIEWER_COUNTRY'])) {
            $code = $_SERVER["HTTP_CLOUDFRONT_VIEWER_COUNTRY"];
        }

        if (!empty($_GET['country'])) {
            $code = $_GET['country'];
        }
        return strtolower($code);
    }
}

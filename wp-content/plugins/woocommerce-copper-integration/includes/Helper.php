<?php
namespace IwantToBelive\Wc\Copper\Integration\Includes;

class Helper
{
    public static function isVerify()
    {
        $value = get_site_option(Bootstrap::PURCHASE_CODE_OPTIONS_KEY);

        if (!empty($value)) {
            return true;
        }

        return false;
    }

    public static function nonVerifyText()
    {
        return esc_html__(
            'Please verify the purchase code on the plugin integration settings page - ',
            'wc-copper-integration'
        )
            . admin_url()
            . 'admin.php?page=wc-copper-integration-settings#wccopper-license-verify';
    }

    private function __construct()
    {
    }

    private function __clone()
    {
    }
}

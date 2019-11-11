<?php
namespace IwantToBelive\Wc\Copper\Integration\Includes;

class Bootstrap
{
    const OPTIONS_KEY = 'wc-copper-integration-settings';
    const PURCHASE_CODE_OPTIONS_KEY = 'wc-copper-purchase-code';

    const CRON_TASK = 'wc-copper-cron-task';
    const CRON_TASK_BULK_ORDERS = 'wc-copper-bulk-order-sent-to-crm';

    const USER_LIST_KEY = 'wc-copper-user-list';

    const LEAD_STATUSES_KEY = 'wc-copper-lead-statuses';
    const CUSTOMER_SOURCES_KEY = 'wc-copper-customer-sources';
    const CONTACT_TYPES_KEY = 'wc-copper-contact-types';
    const PIPELINES_KEY = 'wc-copper-pipelines';
    const CUSTOM_FIELDS_KEY = 'wc-copper-custom-fields';

    const UTM_COOKIE = 'wc-copper-utm-cookie';

    public static $plugin = '';

    private static $instance = false;

    protected function __construct($file)
    {
        self::$plugin = $file;

        register_activation_hook(
            self::$plugin,
            ['IwantToBelive\Wc\Copper\Integration\Includes\Bootstrap', 'pluginActivation']
        );
        register_deactivation_hook(
            self::$plugin,
            ['IwantToBelive\Wc\Copper\Integration\Includes\Bootstrap', 'pluginDeactivation']
        );

        register_uninstall_hook(
            self::$plugin,
            ['IwantToBelive\Wc\Copper\Integration\Includes\Bootstrap', 'pluginUninstall']
        );

        add_action('init', [$this, 'utmCookies']);
    }

    public static function getInstance($file)
    {
        if (!self::$instance) {
            self::$instance = new self($file);
        }

        return self::$instance;
    }

    public function utmCookies()
    {
        if (isset($_GET['utm_source'])) {
            setcookie(
                self::UTM_COOKIE,
                wp_json_encode([
                    'utm_source' => isset($_GET['utm_source']) ? wp_unslash($_GET['utm_source']) : '',
                    'utm_medium' => isset($_GET['utm_medium']) ? wp_unslash($_GET['utm_medium']) : '',
                    'utm_campaign' => isset($_GET['utm_campaign']) ? wp_unslash($_GET['utm_campaign']) : '',
                    'utm_content' => isset($_GET['utm_content']) ? wp_unslash($_GET['utm_content']) : '',
                    'utm_term' => isset($_GET['utm_term']) ? wp_unslash($_GET['utm_term']) : ''
                ]),
                time() + 86400,
                '/'
            );
        }
    }

    public static function pluginActivation()
    {
        if (!is_plugin_active('woocommerce/woocommerce.php')) {
            wp_die(
                esc_html__(
                    'To run the plug-in, you must first install and activate the WooCommerce plugin.',
                    'wc-copper-integration'
                ),
                esc_html__(
                    'Error while activating the WooCommerce - Copper CRM - Integration',
                    'wc-copper-integration'
                ),
                [
                    'back_link' => true
                ]
            );
            // Escape ok
        }

        $roles = new \WP_Roles();

        foreach (self::capabilities() as $capGroup) {
            foreach ($capGroup as $cap) {
                $roles->add_cap('administrator', $cap);

                if (is_multisite()) {
                    $roles->add_cap('super_admin', $cap);
                }
            }
        }
    }

    public static function pluginDeactivation()
    {
        wp_clear_scheduled_hook(self::CRON_TASK);
    }


    public static function pluginUninstall()
    {
        // Nothing
    }

    public static function capabilities()
    {
        $capabilities = [];
        $capabilities['core'] = ['manage_' . self::OPTIONS_KEY];
        flush_rewrite_rules(true);

        return $capabilities;
    }

    private function __clone()
    {
    }
}

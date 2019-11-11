<?php
/**
 * Plugin Name: WooCommerce - Copper CRM - Integration
 * Description: Allows you to integrate your WooCommerce and Copper CRM
 * Version: 1.0.0
 * Author: itgalaxycompany
 * Author URI: https://codecanyon.net/user/itgalaxycompany
 * License: GPLv3
 * Text Domain: wc-copper-integration
 * Domain Path: /languages/
 */

use IwantToBelive\Wc\Copper\Integration\Admin\WcBulkOrderToCrm;
use IwantToBelive\Wc\Copper\Integration\Admin\WcSettingsPage;
use IwantToBelive\Wc\Copper\Integration\Includes\Bootstrap;
use IwantToBelive\Wc\Copper\Integration\Includes\Cron;
use IwantToBelive\Wc\Copper\Integration\Includes\OrderToCrm;

if (!defined('ABSPATH')) {
    exit();
}

/*
 * Require for `is_plugin_active` function.
 */
require_once ABSPATH . 'wp-admin/includes/plugin.php';

load_theme_textdomain('wc-copper-integration', __DIR__ . '/languages');

define('WC_COPPER_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WC_COPPER_PLUGIN_VERSION', '1.0.0');
define('WC_COPPER_PLUGIN_DIR', plugin_dir_path(__FILE__));

require plugin_dir_path(__FILE__) . '/vendor/autoload.php';

require plugin_dir_path(__FILE__) . '/includes/Bootstrap.php';
require plugin_dir_path(__FILE__) . '/includes/Helper.php';
require plugin_dir_path(__FILE__) . '/includes/CrmFields.php';
require plugin_dir_path(__FILE__) . '/includes/Crm.php';
require plugin_dir_path(__FILE__) . '/includes/OrderToCrm.php';

Bootstrap::getInstance(__FILE__);
OrderToCrm::getInstance();

if (defined('DOING_CRON') && DOING_CRON) {
    include plugin_dir_path(__FILE__) . '/includes/Cron.php';

    Cron::getInstance();
}

if (is_admin() && is_plugin_active('woocommerce/woocommerce.php')) {
    add_action('plugins_loaded', function () {
        include plugin_dir_path(__FILE__) . '/admin/RenderFields.php';
        include plugin_dir_path(__FILE__) . '/admin/LeadSettings.php';
        include plugin_dir_path(__FILE__) . '/admin/PersonSettings.php';
        include plugin_dir_path(__FILE__) . '/admin/OrganizationSettings.php';
        include plugin_dir_path(__FILE__) . '/admin/OpportunitySettings.php';
        include plugin_dir_path(__FILE__) . '/admin/WcBulkOrderToCrm.php';
        include plugin_dir_path(__FILE__) . '/admin/WcSettingsPage.php';

        WcBulkOrderToCrm::getInstance();
        WcSettingsPage::getInstance();
    });
}

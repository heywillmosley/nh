<?php

/**
 * The plugin bootstrap file
 *
 *
 * @link              http://woocommerce.db-dzine.de
 * @since             1.0.
 * @package           WooCommerce_Print_Products
 *
 * @wordpress-plugin
 * Plugin Name:       WooCommerce Print Products
 * Plugin URI:        http://woocommerce.db-dzine.de
 * Description:       Give your visitors the option to create a PDF or Word-File from your Products.
 * Version:           1.3.6
 * Author:            DB-Dzine
 * Author URI:        http://www.db-dzine.de
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       woocommerce-print-products
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-woocommerce-print-products-activator.php
 */
function activate_woocommerce_print_products() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-woocommerce-print-products-activator.php';
	WooCommerce_Print_Products_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-woocommerce-print-products-deactivator.php
 */
function deactivate_woocommerce_print_products() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-woocommerce-print-products-deactivator.php';
	WooCommerce_Print_Products_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_woocommerce_print_products' );
register_deactivation_hook( __FILE__, 'deactivate_woocommerce_print_products' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-woocommerce-print-products.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_woocommerce_print_products() {

	$plugin_data = get_plugin_data( __FILE__ );
	$version = $plugin_data['Version'];

	$plugin = new WooCommerce_Print_Products($version);
	$plugin->run();

	return $plugin;

}

include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

// Load the TGM init if it exists
if ( file_exists( plugin_dir_path( __FILE__ ) . 'admin/tgm/tgm-init.php' ) ) {
    require_once plugin_dir_path( __FILE__ ) . 'admin/tgm/tgm-init.php';
}

if ( is_plugin_active( 'woocommerce/woocommerce.php') && is_plugin_active('redux-framework/redux-framework.php') ){
	$WooCommerce_Print_Products = run_woocommerce_print_products();
} else {
	add_action( 'admin_notices', 'woocommerce_print_products_installed_notice' );
}

function woocommerce_print_products_installed_notice()
{
	?>
    <div class="error">
      <p><?php _e( 'WooCommerce Print Products requires the WooCommerce and Redux Framework plugin. Please install or activate them before!', 'woocommerce-print-products'); ?></p>
    </div>
    <?php
}
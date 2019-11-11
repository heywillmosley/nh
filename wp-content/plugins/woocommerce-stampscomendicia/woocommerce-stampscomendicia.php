<?php
/**
 * Plugin Name: WooCommerce - StampscomEndicia Integration
 * Plugin URI: http://www.woothemes.com/products/stampscomendicia-integration/
 * Version: 2.0.0
 * Description: Adds Stamps Orders label printing support to WooCommerce. Requires server DomDocument support.
 * Author: Stamps.comEndicia
 * Author URI: http://www.stamps.com/
 * Text Domain: woocommerce-stampscomendicia
 *
 * @todo Investigate feasibility of line item tracking before marking order complete.
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// include plugin.php file where the is_plugin_active() function is defined
include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

// WC active check
if ( ! is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
	return;
}

/**
 * Include stampscomendicia class
 */
function __woocommerce_stampscomendicia_init() {
	define( 'WC_STAMPSCOMENDICIA_VERSION', '2.0.0' );
	define( 'WC_STAMPSCOMENDICIA_FILE', __FILE__ );

	if ( ! defined( 'WC_STAMPSCOMENDICIA_EXPORT_LIMIT' ) ) {
		define( 'WC_STAMPSCOMENDICIA_EXPORT_LIMIT', 100 );
	}

	load_plugin_textdomain( 'woocommerce-stampscomendicia', false, basename( dirname( __FILE__ ) ) . '/languages' );

	include_once( 'includes/class-wc-stampscomendicia-integration.php' );
}

add_action( 'plugins_loaded', '__woocommerce_stampscomendicia_init' );

/**
 * Define integration
 * @param  array $integrations
 * @return array
 */
function __woocommerce_stampscomendicia_load_integration( $integrations ) {
	$integrations[] = 'WC_StampscomEndicia_Integration';

	return $integrations;
}

add_filter( 'woocommerce_integrations', '__woocommerce_stampscomendicia_load_integration' );

/**
 * Listen for API requests
 */
function __woocommerce_stampscomendicia_api() {
	include_once( 'includes/class-wc-stampscomendicia-api.php' );
}

add_action( 'woocommerce_api_wc_stampsorders', '__woocommerce_stampscomendicia_api' );

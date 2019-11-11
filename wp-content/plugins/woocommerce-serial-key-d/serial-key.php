<?php 
/**
 * Plugin Name: WooCommerce Serial Key
 * Plugin URI: https://www.storeapps.org/product/woocommerce-serial-key/
 * Description: Automatically create, distribute & manage serial keys with each downloadable product purchases. Easy for both - you & your customers.
 * Version: 2.0.0
 * Author: StoreApps
 * Author URI: https://www.storeapps.org/
 * Requires at least: 3.5
 * Tested up to: 4.9.1
 * WC requires at least: 2.5.0
 * WC tested up to: 3.2.6
 * Text Domain: woocommerce-serial-key
 * Domain Path: /languages/
 * Copyright (c) 2013-2017 StoreApps All rights reserved.
 */

if ( !defined( 'ABSPATH' ) ) exit;

$active_plugins = (array) get_option( 'active_plugins', array() );

if ( is_multisite() ) {
    $active_plugins = array_merge( $active_plugins, get_site_option( 'active_sitewide_plugins', array() ) );
}

if ( ! ( in_array( 'woocommerce/woocommerce.php', $active_plugins ) || array_key_exists( 'woocommerce/woocommerce.php', $active_plugins ) ) ) return;

register_activation_hook ( __FILE__, 'woocommerce_serial_key_activate' );

function woocommerce_serial_key_activate() {
	global $wpdb;

	$collate = '';

	if ( $wpdb->has_cap( 'collation' ) ) {
		if ( ! empty( $wpdb->charset ) ) {
			$collate .= "DEFAULT CHARACTER SET $wpdb->charset";
		}
		if ( ! empty( $wpdb->collate ) ) {
			$collate .= " COLLATE $wpdb->collate";
		}
	}

	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

	$create_table_query = "
				CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}woocommerce_serial_key` (
				  `serial_key_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				  `order_id` bigint(20) unsigned NOT NULL default '0',
				  `product_id` bigint(20) unsigned NOT NULL default '0',
				  `serial_key` varchar(50),
				  `valid_till` datetime,
				  `limit` int(11) NOT NULL default '0',
				  `uuid` longtext NOT NULL default '',
				  KEY `product_id` (`product_id`),
				  KEY `order_id` (`order_id`),
				  PRIMARY KEY (`serial_key_id`)
				  ) $collate";

	dbDelta( $create_table_query );

	if ( ! is_network_admin() && ! isset( $_GET['activate-multi'] ) ) {
        set_transient( '_sk_activation_redirect', 1, 300 );            
    }

}

function initialize_woocommerce_serial_key() {

	define( 'SK_PLUGIN_FILE', __FILE__ );
	define( 'SK_PLUGIN_DIRNAME', dirname( plugin_basename(__FILE__) ) );

	require_once 'includes/wc-compat/version-2-5.php';
	require_once 'includes/wc-compat/version-2-6.php';
	require_once 'includes/wc-compat/version-3-0.php';

	if ( ! class_exists( 'SA_Serial_Key' ) ) {
		require_once 'includes/class-sa-serial-key.php';

	}

	$GLOBALS['sa_serial_key'] = new SA_Serial_Key( __FILE__ );

	if ( is_admin() ) {
		require_once 'includes/class-sk-admin-welcome.php' ;
		require_once 'includes/class-sk-admin-dashboard.php' ;

	}

	if ( ! class_exists( 'Validate_Serial_Key' ) ) {
		require_once 'includes/class-validate-serial-key.php';

	}

	if ( ! class_exists( 'StoreApps_Upgrade_2_2' ) ) {
		require_once 'sa-includes/class-storeapps-upgrade-2-2.php';

	}

	$latest_upgrade_class = $GLOBALS['sa_serial_key']->get_latest_upgrade_class();

	$sku = 'wcsk';
	$prefix = 'sa_serial_key';
	$plugin_name = 'WooCommerce Serial Key';
	$documentation_link = 'https://www.storeapps.org/knowledgebase_category/woocommerce-serial-key/';
	$GLOBALS['serial_key_upgrade'] = new $latest_upgrade_class( __FILE__, $sku, $prefix, $plugin_name, SA_Serial_Key::$text_domain, $documentation_link );

}
add_action( 'plugins_loaded', 'initialize_woocommerce_serial_key' );
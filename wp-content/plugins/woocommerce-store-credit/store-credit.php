<?php
/**
 * Backwards compat.
 *
 *
 * @since 2.1.11
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$active_plugins = get_option( 'active_plugins', array() );
foreach ( $active_plugins as $key => $active_plugin ) {
	if ( strstr( $active_plugin, '/store-credit.php' ) ) {
		$active_plugins[ $key ] = str_replace( '/store-credit.php', '/woocommerce-store-credit.php', $active_plugin );
	}
}
update_option( 'active_plugins', $active_plugins );

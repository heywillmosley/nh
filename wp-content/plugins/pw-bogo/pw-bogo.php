<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Backwards compatible, changed the plugin file name.
 */
$active_plugins = get_option( 'active_plugins', array() );
foreach ( $active_plugins as $key => $active_plugin ) {
    if ( strstr( $active_plugin, '/pw-bogo.php' ) ) {
        $active_plugins[ $key ] = str_replace( '/pw-bogo.php', '/pw-woocommerce-bogo-free.php', $active_plugin );
    }
}
update_option( 'active_plugins', $active_plugins );

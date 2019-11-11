<?php
/*
Plugin Name:    WooCommerce Wholesale Order Form
Plugin URI:     https://wholesalesuiteplugin.com/
Description:    WooCommerce Extension to Provide Wholesale Product Listing Functionality
Author:         Rymera Web Co
Version:        1.11
Author URI:     http://rymera.com.au/
Text Domain:    woocommerce-wholesale-order-form
WC requires at least: 3.0.9
WC tested up to: 3.5.4
*/

require_once ( 'includes/class-wwof-functions.php' );

// Delete code activation flag on plugin deactivate.
register_deactivation_hook( __FILE__ , array( new WWOF_Functions , 'wwof_global_plugin_deactivate' ) );

// Check if WooCommerce is active
if ( count( WWOF_Functions::wwof_check_plugin_dependencies() ) <= 0 ) {

    // Include Necessary Files
    require_once ( 'woocommerce-wholesale-order-form.options.php' );
    require_once ( 'woocommerce-wholesale-order-form.plugin.php' );

    // Get Instance of Main Plugin Class
    $wc_wholesale_order_form = WooCommerce_WholeSale_Order_Form::instance();
    $GLOBALS[ 'wc_wholesale_order_form' ] = $wc_wholesale_order_form;

    /*
    |-------------------------------------------------------------------------------------------------------------------
    | Settings
    |-------------------------------------------------------------------------------------------------------------------
    */

    // Register Settings Page
    add_filter( 'woocommerce_get_settings_pages' , array( $wc_wholesale_order_form , 'wwof_plugin_settings' ) );

    /*
    |---------------------------------------------------------------------------------------------------------------
    | Execute WWOF
    |---------------------------------------------------------------------------------------------------------------
    */

    $wc_wholesale_order_form->run();

} else {

    /**
     * Provide admin notice to users that a required plugin dependency of WooCommerce Wholesale Order Form plugin is missing.
     *
     * @since 1.6.3
     * @since 1.6.6 Underscore cased the function name and variables.
     */
    function wwof_admin_notices () {

        $plugins = WWOF_Functions::wwof_check_plugin_dependencies();
        $admin_notice_msg = '';

        foreach ( $plugins as $plugin ) {

            $plugin_file    = $plugin[ 'plugin-base' ];
            $file           = trailingslashit( WP_PLUGIN_DIR ) . plugin_basename( $plugin_file );

            $install_text = '<a href="' . wp_nonce_url( 'update.php?action=install-plugin&plugin=' . $plugin[ 'plugin-key' ], 'install-plugin_' . $plugin[ 'plugin-key' ] ) . '">' . __( 'Click here to install from WordPress.org repo &rarr;' , 'woocommerce-wholesale-order-form' ) . '</a>';
            if ( file_exists( $file ) )
                $install_text = '<a href="' . wp_nonce_url( 'plugins.php?action=activate&amp;plugin=' . $plugin_file . '&amp;plugin_status=all&amp;s', 'activate-plugin_' . $plugin_file ) . '" title="' . __( 'Activate this plugin', 'woocommerce-wholesale-order-form' ) . '" class="edit">' . __( 'Click here to activate &rarr;', 'woocommerce-wholesale-order-form' ) . '</a>';

            $admin_notice_msg .= sprintf( __( '<br/>Please ensure you have the <a href="%1$s" target="_blank">%2$s</a> plugin installed and activated.<br/>', 'woocommerce-wholesale-order-form' ), 'http://wordpress.org/plugins/' . $plugin[ 'plugin-key' ] . '/', $plugin[ 'plugin-name' ] );
            $admin_notice_msg .= $install_text . '<br/>';

        } ?>

        <div class="error">
            <p>
                <?php _e( '<b>WooCommerce Wholesale Order Form</b> plugin missing dependency.<br/>', 'woocommerce-wholesale-order-form' ); ?>
                <?php echo $admin_notice_msg; ?>
            </p>
        </div><?php

    }

    add_action( 'admin_notices', 'wwof_admin_notices' );

}

<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly

// This is where you set various options affecting the plugin

// Path Constants ======================================================================================================
define( 'WWOF_PLUGIN_BASE_PATH' ,	    basename( dirname( __FILE__ ) ) . '/' );
define( 'WWOF_PLUGIN_URL' ,             plugins_url() . '/woocommerce-wholesale-order-form/' );
define( 'WWOF_PLUGIN_DIR' ,             plugin_dir_path( __FILE__ ) );
define( 'WWOF_CSS_ROOT_URL' ,           WWOF_PLUGIN_URL . 'css/' );
define( 'WWOF_CSS_ROOT_DIR' ,           WWOF_PLUGIN_DIR . 'css/' );
define( 'WWOF_IMAGES_ROOT_URL' ,        WWOF_PLUGIN_URL . 'images/' );
define( 'WWOF_IMAGES_ROOT_DIR' ,        WWOF_PLUGIN_DIR . 'images/' );
define( 'WWOF_INCLUDES_ROOT_URL' ,      WWOF_PLUGIN_URL . 'includes/' );
define( 'WWOF_INCLUDES_ROOT_DIR' ,      WWOF_PLUGIN_DIR . 'includes/' );
define( 'WWOF_JS_ROOT_URL' ,            WWOF_PLUGIN_URL . 'js/' );
define( 'WWOF_JS_ROOT_DIR' ,            WWOF_PLUGIN_DIR . 'js/' );
define( 'WWOF_TEMPLATES_ROOT_URL' ,     WWOF_PLUGIN_URL . 'templates/' );
define( 'WWOF_TEMPLATES_ROOT_DIR' ,     WWOF_PLUGIN_DIR . 'templates/' );
define( 'WWOF_VIEWS_ROOT_URL' ,         WWOF_PLUGIN_URL . 'views/' );
define( 'WWOF_VIEWS_ROOT_DIR' ,         WWOF_PLUGIN_DIR . 'views/' );
define( 'WWOF_LANGUAGES_ROOT_URL' ,     WWOF_PLUGIN_URL . 'languages/' );
define( 'WWOF_LANGUAGES_ROOT_DIR' ,     WWOF_PLUGIN_DIR . 'languages/' );

// Option Constants ====================================================================================================
define( 'WWOF_ACTIVATION_CODE_TRIGGERED' ,  'wwof_activation_code_triggered' );
define( 'WWOF_OPTION_INSTALLED_VERSION' ,   'wwof_option_installed_version' );
define( 'WWOF_SETTINGS' ,                   'wwof_settings' );
define( 'WWOF_SETTINGS_WHOLESALE_PAGE_ID' , 'wwof_settings_wholesale_page_id' );
define( 'WWOF_OPTION_LICENSE_EMAIL' ,       'wwof_option_license_email' );
define( 'WWOF_OPTION_LICENSE_KEY' ,         'wwof_option_license_key' );

// Settings Options ====================================================================================================
$WWOF_SETTINGS_DEFAULT_PPP = 12;
$WWOF_SETTINGS_DEFAULT_SORT_BY = 'menu_order';
$WWOF_SETTINGS_DEFAULT_SORT_ORDER = 'asc';

$WWOF_SETTINGS_SORT_BY = null;

function wwofInitializeGlobalVariables() {

    global $WWOF_SETTINGS_SORT_BY;

    if ( !isset( $WWOF_SETTINGS_SORT_BY ) )
        $WWOF_SETTINGS_SORT_BY = array(
                                    'default'    => __( 'Default Sorting' , 'woocommerce-wholesale-order-form' ),
                                    'menu_order' => __( 'Custom Ordering (menu order) + Name' , 'woocommerce-wholesale-order-form' ),
                                    'name'       => __( 'Name' , 'woocommerce-wholesale-order-form' ),
                                    //'popularity'    =>  'Popularity (sales)',
                                    //'rating'        =>  'Average Rating',
                                    'date'       => __( 'Sort by Date' , 'woocommerce-wholesale-order-form' ),
                                    'sku'        => __( 'SKU' , 'woocommerce-wholesale-order-form' )
                                    //'price'         =>  'Sort by price',
                                );

}

add_action( 'init' , 'wwofInitializeGlobalVariables' , 1 );

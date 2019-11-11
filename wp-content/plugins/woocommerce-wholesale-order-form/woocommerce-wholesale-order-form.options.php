<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly

// This is where you set various options affecting the plugin

// Path Constants ======================================================================================================

define( 'WWOF_MAIN_PLUGIN_FILE_PATH' , WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . 'woocommerce-wholesale-order-form' . DIRECTORY_SEPARATOR . 'woocommerce-wholesale-order-form.bootstrap.php' );
define( 'WWOF_PLUGIN_BASE_NAME' , 	   plugin_basename( WWOF_MAIN_PLUGIN_FILE_PATH ) );
define( 'WWOF_PLUGIN_BASE_PATH' ,	   basename( dirname( __FILE__ ) ) . '/' );
define( 'WWOF_PLUGIN_URL' ,            plugins_url() . '/woocommerce-wholesale-order-form/' );
define( 'WWOF_PLUGIN_DIR' ,            plugin_dir_path( __FILE__ ) );
define( 'WWOF_CSS_ROOT_URL' ,          WWOF_PLUGIN_URL . 'css/' );
define( 'WWOF_CSS_ROOT_DIR' ,          WWOF_PLUGIN_DIR . 'css/' );
define( 'WWOF_IMAGES_ROOT_URL' ,       WWOF_PLUGIN_URL . 'images/' );
define( 'WWOF_IMAGES_ROOT_DIR' ,       WWOF_PLUGIN_DIR . 'images/' );
define( 'WWOF_INCLUDES_ROOT_URL' ,     WWOF_PLUGIN_URL . 'includes/' );
define( 'WWOF_INCLUDES_ROOT_DIR' ,     WWOF_PLUGIN_DIR . 'includes/' );
define( 'WWOF_JS_ROOT_URL' ,           WWOF_PLUGIN_URL . 'js/' );
define( 'WWOF_JS_ROOT_DIR' ,           WWOF_PLUGIN_DIR . 'js/' );
define( 'WWOF_TEMPLATES_ROOT_URL' ,    WWOF_PLUGIN_URL . 'templates/' );
define( 'WWOF_TEMPLATES_ROOT_DIR' ,    WWOF_PLUGIN_DIR . 'templates/' );
define( 'WWOF_VIEWS_ROOT_URL' ,        WWOF_PLUGIN_URL . 'views/' );
define( 'WWOF_VIEWS_ROOT_DIR' ,        WWOF_PLUGIN_DIR . 'views/' );
define( 'WWOF_LANGUAGES_ROOT_URL' ,    WWOF_PLUGIN_URL . 'languages/' );
define( 'WWOF_LANGUAGES_ROOT_DIR' ,    WWOF_PLUGIN_DIR . 'languages/' );




// SLMW ===============================================================================================

define( 'WWOF_PLUGIN_SITE_URL' , 		'https://wholesalesuiteplugin.com' );
define( 'WWOF_LICENSE_ACTIVATION_URL' , WWOF_PLUGIN_SITE_URL . '/wp-admin/admin-ajax.php?action=slmw_activate_license' );
define( 'WWOF_UPDATE_DATA_URL' , 		WWOF_PLUGIN_SITE_URL . '/wp-admin/admin-ajax.php?action=slmw_get_update_data' );
define( 'WWOF_STATIC_PING_FILE' , 		WWOF_PLUGIN_SITE_URL . '/WWOF.json' );

define( 'WWOF_OPTION_LICENSE_EMAIL' ,     'wwof_option_license_email' );
define( 'WWOF_OPTION_LICENSE_KEY' ,       'wwof_option_license_key' );
define( 'WWOF_LICENSE_ACTIVATED' , 	      'wwof_license_activated' );
define( 'WWOF_UPDATE_DATA' , 			  'wwof_update_data' ); // Option that holds retrieved software product update data
define( 'WWOF_RETRIEVING_UPDATE_DATA' ,   'wwof_retrieving_update_data' );
define( 'WWOF_OPTION_INSTALLED_VERSION' , 'wwof_option_installed_version' );
define( 'WWOF_ACTIVATE_LICENSE_NOTICE' ,  'wwof_activate_license_notice' );
define( 'WWOF_LICENSE_EXPIRED' ,          'wwof_license_expired' );




// Option Constants ====================================================================================================

define( 'WWOF_ACTIVATION_CODE_TRIGGERED' ,  'wwof_activation_code_triggered' );
define( 'WWOF_SETTINGS' ,                   'wwof_settings' );
define( 'WWOF_SETTINGS_WHOLESALE_PAGE_ID' , 'wwof_settings_wholesale_page_id' );




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

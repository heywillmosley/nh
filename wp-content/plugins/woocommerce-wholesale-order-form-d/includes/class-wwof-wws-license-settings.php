<?php
if ( !defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( !class_exists( 'WWOF_WWS_License_Settings' ) ) {

    class WWOF_WWS_License_Settings {

        /*
        |--------------------------------------------------------------------------
        | Class Properties
        |--------------------------------------------------------------------------
        */

        /**
         * Property that holds the single main instance of WWOF_AJAX.
         *
         * @since 1.6.6
         * @access private
         * @var WWOF_AJAX
         */
        private static $_instance;

        /*
        |--------------------------------------------------------------------------
        | Class Methods
        |--------------------------------------------------------------------------
        */

        /**
         * WWOF_WWS_License_Settings constructor.
         *
         * @since 1.6.6
         */
        public function __construct( $dependencies ) {}

        /**
         * Singleton Pattern.
         *
         * @since 1.6.6
         *
         * @return WWOF_WWS_License_Settings
         */
        public static function instance( $dependencies = null ) {

            if ( !self::$_instance instanceof self )
                self::$_instance = new self( $dependencies );

            return self::$_instance;

        }

        /**
         * Save wwof license details.
         *
         * @param null $license_details
         * @return bool
         *
         * @since 1.0.1
         */
        public function wwof_save_license_details ( $license_details = null ) {

            if ( defined( 'DOING_AJAX' ) && DOING_AJAX )
                $license_details = $_POST[ 'license_details' ];

            $license_email  = sanitize_text_field( $license_details[ 'license_email' ] );
            $license_key    = sanitize_text_field( $license_details[ 'license_key' ] );

            update_option( WWOF_OPTION_LICENSE_EMAIL , trim( $license_email ) );
            update_option( WWOF_OPTION_LICENSE_KEY , trim( $license_key ) );

            if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {

                header( 'Content-Type: application/json' ); // specify we return json
                echo json_encode( array( 'status' => 'success' ) );
                die();

            } else
                return true;

        }

        /**
        * Register general wws license settings page.
        *
        * @since 1.0.1
        * @since 1.6.6 Refactor codebase and move to its proper model
        */
        public function wwof_register_wws_license_settings_menu() {

            /*
             * Since we don't have a primary plugin to add this license settings, we have to check first if other plugins
             * belonging to the WWS plugin suite has already added a license settings page.
             */
            if ( !defined( 'WWS_LICENSE_SETTINGS_PAGE' ) ) {

                if ( !defined( 'WWS_LICENSE_SETTINGS_DEFAULT_PLUGIN' ) )
                    define( 'WWS_LICENSE_SETTINGS_DEFAULT_PLUGIN' , 'wwof' );

                // Register WWS Settings Menu
                add_submenu_page(
                    'options-general.php', // Settings
                    __( 'WooCommerce WholeSale Suit License Settings' , 'woocommerce-wholesale-order-form' ),
                    __( 'WWS License' , 'woocommerce-wholesale-order-form' ),
                    'manage_options',
                    'wwc_license_settings',
                    array( self::instance() , 'wwof_general_license_settings_page' )
                );

                /*
                 * We define this constant with the text domain of the plugin who added the settings page.
                 */
                define( 'WWS_LICENSE_SETTINGS_PAGE' , 'woocommerce-wholesale-order-form' );

            }

        }

        /**
         * General WWS license settings page template.
         *
         * @since 1.0.1
         * @since 1.6.6 Refactor codebase and move to its proper model
         */
        public function wwof_general_license_settings_page() {

            require_once( WWOF_VIEWS_ROOT_DIR . 'wws-license-settings/view-wwof-general-wws-settings-page.php' );

        }

        /**
         * WWOF WWC license settings header tab item.
         *
         * @since 1.0.1
         * @since 1.6.6 Refactor codebase and move to its proper model
         */
        public function wwof_license_settings_header() {

            ob_start();

            if ( isset( $_GET[ 'tab' ] ) )
                $tab = $_GET[ 'tab' ];
            else
                $tab = WWS_LICENSE_SETTINGS_DEFAULT_PLUGIN;

            global $wp;
            $current_url = add_query_arg( $wp->query_string , '?' , home_url( $wp->request ) );
            $wwof_license_settings_url = $current_url . "/wp-admin/options-general.php?page=wwc_license_settings&tab=wwof"; ?>
            
            <a href="<?php echo $wwof_license_settings_url; ?>" class="nav-tab <?php echo ( $tab == "wwof" ) ? "nav-tab-active" : ""; ?>"><?php _e( 'Wholesale Ordering' , 'woocommerce-wholesale-order-form' ); ?></a><?php

            echo ob_get_clean();

        }

        /**
         * WWOF WWS license settings page.
         *
         * @since 1.0.1
         * @since 1.6.6 Refactor codebase and move to its proper model
         */
        public function wwof_license_settings_page() {

            ob_start();

            require_once( WWOF_VIEWS_ROOT_DIR . 'wws-license-settings/view-wwof-wss-settings-page.php' );

            echo ob_get_clean();

        }

        /**
         * Execute model.
         *
         * @since 1.6.6
         * @access public
         */
        public function run() {

            // Add WooCommerce Wholesale Suit License Settings
            add_action( 'admin_menu' , array( $this , 'wwof_register_wws_license_settings_menu' ) );

            // Add WWS License Settings Header Tab Item
            add_action( 'wws_action_license_settings_tab' , array( $this , 'wwof_license_settings_header' ) );

            // Add WWS License Settings Page (WWOF)
            add_action( 'wws_action_license_settings_wwof' , array( $this , 'wwof_license_settings_page' ) );
        
        }
    }

}
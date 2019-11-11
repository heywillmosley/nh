<?php if ( !defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( !class_exists( 'WWOF_Bootstrap' ) ) {

	class WWOF_Bootstrap {

        /*
        |--------------------------------------------------------------------------
        | Class Properties
        |--------------------------------------------------------------------------
        */

		/**
         * Property that holds the single main instance of WWOF_Bootstrap.
         *
         * @since 1.6.6
         * @access private
         * @var WWOF_Bootstrap
         */
		private static $_instance;

        /**
         * Current WWOF version.
         *
         * @since 1.6.6
         * @access private
         * @var int
         */
        private $_wwof_current_version;

        /**
         * WWOF_AJAX object.
         *
         * @since 1.6.6
         * @access private
         * @var WWOF_AJAX
         */
        private $_wwof_ajax;

		/*
        |--------------------------------------------------------------------------
        | Class Methods
        |--------------------------------------------------------------------------
        */

        /**
         * WWOF_Bootstrap constructor.
         *
         * @param array $dependencies Array of instance objects of all dependencies of WWOF_Bootstrap model.
         *
         * @access public
         * @since 1.6.6
         */
		public function __construct( $dependencies ) {

            $this->_wwof_current_version = $dependencies[ 'WWOF_CURRENT_VERSION' ];
            $this->_wwof_ajax = $dependencies[ 'WWOF_AJAX' ];

        }

        /**
         * Ensure that only one instance of WWOF_Bootstrap is loaded or can be loaded (Singleton Pattern).
         *
         * @param array $dependencies Array of instance objects of all dependencies of WWOF_Bootstrap model.
         *
         * @return WWOF_Bootstrap
         * @since 1.6.6
         */
        public static function instance( $dependencies = null ) {

            if ( !self::$_instance instanceof self )
                self::$_instance = new self( $dependencies );

            return self::$_instance;

        }

        /**
         * Load plugin text domain.
         *
         * @since 1.2.0
         * @since 1.6.6 Refactor codebase and move to its proper model
         */
        public function wwof_load_plugin_text_domain() {

            load_plugin_textdomain( 'woocommerce-wholesale-order-form' , false , WWOF_PLUGIN_BASE_PATH . 'languages/' );

        }

        /*
         |------------------------------------------------------------------------------------------------------------------
         | Bootstrap/Shutdown Functions
         |------------------------------------------------------------------------------------------------------------------
         */

        /**
         * Plugin activation hook callback.
         *
         * @param bool $network_wide
         *
         * @since 1.0.0
         * @since 1.6.4 Multisite Compatibility
         * @since 1.6.6 Refactor codebase and move to its proper model
         */
        public function wwof_activate( $network_wide ) {

            global $wpdb;

            if( is_multisite() ){

                if( $network_wide ){

                    // get ids of all sites
                    $blogIDs = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" );

                    foreach( $blogIDs as $blogID ){

                        switch_to_blog( $blogID );
                        $this->wwof_activate_action( $blogID );

                    }

                    restore_current_blog();

                }else{

                    // activated on a single site, in a multi-site
                    $this->wwof_activate_action( $wpdb->blogid );

                }

            }else{

                // activated on a single site
                $this->wwof_activate_action( $wpdb->blogid );

            }

        }

        /**
         * Perform actions on plugin activation.
         *
         * @since 1.6.4
         * @since 1.6.6 Refactor codebase and move to its proper model
         */
        private function wwof_activate_action(){

            // Set initial settings
            global $WWOF_SETTINGS_DEFAULT_PPP, $WWOF_SETTINGS_DEFAULT_SORT_BY, $WWOF_SETTINGS_DEFAULT_SORT_ORDER;

            // General section settings
            if ( get_option( 'wwof_general_products_per_page' ) === false )
                update_option( 'wwof_general_products_per_page' , $WWOF_SETTINGS_DEFAULT_PPP );

            if ( get_option( 'wwof_general_sort_by') === false )
                update_option( 'wwof_general_sort_by' , $WWOF_SETTINGS_DEFAULT_SORT_BY );

            if ( get_option( 'wwof_general_sort_order' ) === false )
                update_option( 'wwof_general_sort_order' , $WWOF_SETTINGS_DEFAULT_SORT_ORDER );

            // Create wholesale pages
            $this->_wwof_ajax->wwof_create_wholesale_page( null , false );

            flush_rewrite_rules();

            update_option( WWOF_ACTIVATION_CODE_TRIGGERED , 'yes' );

            update_option( WWOF_OPTION_INSTALLED_VERSION , $this->_wwof_current_version );

        }

        /**
         * Plugin deactivation hook callback.
         *
         * @param bool $network_wide
         *
         * @since 1.0.0
         * @since 1.6.4 Multisite Compatibility
         * @since 1.6.6 Refactor codebase and move to its proper model
         */
        public function wwof_deactivate( $network_wide ) {

            global $wpdb;

            // check if it is a multisite network
            if ( is_multisite() ) {

                // check if the plugin has been deactivated on the network or on a single site
                if ( $network_wide ) {

                    // get ids of all sites
                    $blogIDs = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" );

                    foreach ( $blogIDs as $blogID ) {

                        switch_to_blog( $blogID );
                        $this->wwof_deactivate_action();

                    }

                    restore_current_blog();

                } else {

                    // deactivated on a single site, in a multi-site
                    $this->wwof_deactivate_action();

                }

            } else {

                // deactivated on a single site
                $this->wwof_deactivate_action();

            }

        }
        
        /**
         * Perform actions on plugin deactivation.
         *
         * @since 1.6.4
         * @since 1.6.6 Refactor codebase and move to its proper model
         */
        private function wwof_deactivate_action(){
            
            flush_rewrite_rules();

        }

        /**
         * Plugin initialization.
         *
         * @since 1.0.1
         * @since 1.6.4 Multisite Compatibility
         * @since 1.6.6 Refactor codebase and move to its proper model
         */
        public function wwof_initialize() {

            $activation_flag = get_option( WWOF_ACTIVATION_CODE_TRIGGERED , false );
            $installed_version = get_option( WWOF_OPTION_INSTALLED_VERSION , false );

            if ( version_compare( $installed_version , $this->_wwof_current_version , '!=' ) || $activation_flag != 'yes' ) {

                if ( ! function_exists( 'is_plugin_active_for_network' ) )
                    require_once( ABSPATH . '/wp-admin/includes/plugin.php' );

                $network_wide = is_plugin_active_for_network( 'woocommerce-wholesale-order-form/woocommerce-wholesale-order-form.bootstrap.php' );

                $this->wwof_activate( $network_wide );

            }

        }

        /**
         * Execute plugin initialization ( plugin activation ) on every newly created site in a multi site set up
         *
         * @since 1.6.4
         * @since 1.6.6 Refactor codebase and move to its proper model
         */
        public function wwof_multisite_init(){

            if ( is_plugin_active_for_network( 'woocommerce-wholesale-order-form/woocommerce-wholesale-order-form.bootstrap.php' ) ) {

                switch_to_blog( $blogID );
                $this->wwof_activate( $blogID );
                restore_current_blog();

            }

        }

        /**
         * Add plugin listing custom action link ( settings ).
         *
         * @param $links
         * @param $file
         * @return mixed
         *
         * @since 1.0.2
         * @since 1.6.6 Refactor codebase and move to its proper model
         */
        public function wwof_add_actions_links_in_plugin_listing( $links , $file ) {

            if ( $file == plugin_basename( WWOF_PLUGIN_DIR . 'woocommerce-wholesale-order-form.bootstrap.php' ) ) {

                $settings_link  = '<a href="admin.php?page=wc-settings&tab=wwof_settings">' . __( 'Settings' , 'woocommerce-wholesale-order-form' ) . '</a>';
                $license_link   = '<a href="options-general.php?page=wwc_license_settings&tab=wwof">' . __( 'License' , 'woocommerce-wholesale-order-form' ) . '</a>';
                array_unshift( $links , $license_link );
                array_unshift( $links , $settings_link );

            }

            return $links;

        }

	    /**
	     * Execute model.
	     *
	     * @since 1.6.6
	     * @access public
	     */
	    public function run() {

            // Load Plugin Text Domain
            add_action( 'plugins_loaded' , array( $this , 'wwof_load_plugin_text_domain' ) );

            // Register Activation Hook
            register_activation_hook( WWOF_PLUGIN_DIR . 'woocommerce-wholesale-order-form.bootstrap.php' , array( $this , 'wwof_activate' ) );

            // Register Deactivation Hook
            register_deactivation_hook( WWOF_PLUGIN_DIR . 'woocommerce-wholesale-order-form.bootstrap.php' , array( $this , 'wwof_deactivate' ) );

            // Initialize Plugin
            add_action( 'init' , array( $this , 'wwof_initialize' ) );

            // Execute plugin initialization ( plugin activation ) on every newly created site in a multi site set up
            add_action( 'wpmu_new_blog', array( $this , 'wwof_multisite_init' ) , 10 , 6 );

            // Plugin action links
            add_filter( 'plugin_action_links' , array( $this , 'wwof_add_actions_links_in_plugin_listing' ) , 10 , 2 );

	    }
	}
}

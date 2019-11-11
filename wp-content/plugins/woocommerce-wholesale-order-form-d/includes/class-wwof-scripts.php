<?php if ( !defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( !class_exists( 'WWOF_Scripts' ) ) {

	class WWOF_Scripts {

        /*
        |--------------------------------------------------------------------------
        | Class Properties
        |--------------------------------------------------------------------------
        */

		/**
         * Property that holds the single main instance of WWOF_Scripts.
         *
         * @since 1.6.6
         * @access private
         * @var WWOF_Scripts
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

		/*
        |--------------------------------------------------------------------------
        | Class Methods
        |--------------------------------------------------------------------------
        */

        /**
         * WWOF_Scripts constructor.
         *
         * @param array $dependencies Array of instance objects of all dependencies of WWOF_Scripts model.
         *
         * @access public
         * @since 1.6.6
         */
		public function __construct( $dependencies ) {

            $this->_wwof_current_version = $dependencies[ 'WWOF_CURRENT_VERSION' ];

        }

        /**
         * Ensure that only one instance of WWOF_Scripts is loaded or can be loaded (Singleton Pattern).
         *
         * @param array $dependencies Array of instance objects of all dependencies of WWOF_Scripts model.
         *
         * @return WWOF_Scripts
         * @since 1.6.6
         */
        public static function instance( $dependencies = null ) {

            if ( !self::$_instance instanceof self )
                self::$_instance = new self( $dependencies );

            return self::$_instance;

        }

        /**
         * Load Admin or Backend Related Styles and Scripts.
         *
         * @since 1.0.0
         * @since 1.6.6 Refactor codebase and move to its proper model
         */
        public function wwof_load_back_end_styles_and_scripts() {

            $screen = get_current_screen();

            // Settings
            if ( in_array( $screen->id , array( 'woocommerce_page_wc-settings' ) ) ) {

                // General styles to be used on all settings sections
                wp_enqueue_style( 'wwof_toastr_css' , WWOF_JS_ROOT_URL . 'lib/toastr/toastr.min.css' , array() , $this->_wwof_current_version , 'all' );

                // General scripts to be used on all settings sections
                wp_enqueue_script( 'wwof_BackEndAjaxServices_js' , WWOF_JS_ROOT_URL.'app/modules/BackEndAjaxServices.js' , array( 'jquery' ) , $this->_wwof_current_version );
                wp_enqueue_script( 'wwof_toastr_js' , WWOF_JS_ROOT_URL . 'lib/toastr/toastr.min.js' , array( 'jquery' ) , $this->_wwof_current_version );

                if( !isset( $_GET[ 'section' ] ) || $_GET[ 'section' ] == '' ) {

                    // General

                } elseif ( isset( $_GET[ 'section' ] ) && $_GET[ 'section' ] == 'wwof_setting_filters_section' ) {

                    // Filters

                } elseif( isset( $_GET[ 'section' ] ) && $_GET[ 'section' ] == 'wwof_settings_permissions_section' ) {

                    // Permissions

                } elseif( isset( $_GET[ 'section' ] ) && $_GET[ 'section' ] == 'wwof_settings_help_section' ) {

                    // Help
                    wp_enqueue_style( 'wwof_HelpSettings_css' , WWOF_CSS_ROOT_URL . 'HelpSettings.css' , array() , $this->_wwof_current_version , 'all' );

                    wp_enqueue_script( 'wwof_HelpSettings_js' , WWOF_JS_ROOT_URL . 'app/HelpSettings.js' , array( 'jquery' ) , $this->_wwof_current_version );
                    wp_localize_script( 'wwof_HelpSettings_js',
                                        'WPMessages',
                                        array(
                                            'success_message'   =>  __( 'Wholesale Ordering Page Created Successfully' , 'woocommerce-wholesale-order-form' ),
                                            'failure_message'   =>  __( 'Failed To Create Wholesale Ordering Page' , 'woocommerce-wholesale-order-form' )
                                        )
                                    );

                }

            } elseif ( isset( $_GET[ 'page' ] ) && $_GET[ 'page' ] == 'wwc_license_settings' &&
                       ( ( isset( $_GET[ 'tab' ] ) && $_GET[ 'tab' ] == 'wwof' ) || WWS_LICENSE_SETTINGS_DEFAULT_PLUGIN == 'wwof' ) ) {

                // CSS
                wp_enqueue_style( 'wwof_toastr_css' , WWOF_JS_ROOT_URL . 'lib/toastr/toastr.min.css' , array() , $this->_wwof_current_version , 'all' );
                wp_enqueue_style( 'wwof_WWSLicenseSettings_css' , WWOF_CSS_ROOT_URL . 'WWSLicenseSettings.css' , array() , $this->_wwof_current_version , 'all' );

                // JS
                wp_enqueue_script( 'wwof_toastr_js' , WWOF_JS_ROOT_URL . 'lib/toastr/toastr.min.js' , array( 'jquery' ) , $this->_wwof_current_version );
                wp_enqueue_script( 'wwof_BackEndAjaxServices_js' , WWOF_JS_ROOT_URL . 'app/modules/BackEndAjaxServices.js' , array( 'jquery' ) , $this->_wwof_current_version );
                wp_enqueue_script( 'wwof_WWSLicenseSettings_js' , WWOF_JS_ROOT_URL . 'app/WWSLicenseSettings.js' , array( 'jquery' ) , $this->_wwof_current_version );
                wp_localize_script( 'wwof_WWSLicenseSettings_js',
                                'WPMessages',
                                array(
                                    'success_message'   =>  __( 'Wholesale Ordering License Details Successfully Saved' , 'woocommerce-wholesale-order-form' ),
                                    'failure_message'   =>  __( 'Failed To Save Wholesale Ordering License Details' , 'woocommerce-wholesale-order-form' )
                                )
                            );

            }

        }

        /**
         * Load Frontend Related Styles and Scripts.
         *
         * @since 1.0.0
         * @since 1.6.6 Refactor codebase and move to its proper model
         */
        public function wwof_load_front_end_styles_and_scripts(){

            global $post , $WWOF_SETTINGS_DEFAULT_PPP;

			$products_per_page = get_option( 'wwof_general_products_per_page' );

            if ( isset( $post->post_content ) && has_shortcode( $post->post_content , 'wwof_product_listing' ) ) {

                // Styles
                wp_enqueue_style( 'wwof_fancybox_css' , WWOF_JS_ROOT_URL . 'lib/fancybox/jquery.fancybox.css' , array() , $this->_wwof_current_version , 'all' );
                wp_enqueue_style( 'wwof_vex_css' , WWOF_JS_ROOT_URL . 'lib/vex/css/vex.css' , array() , $this->_wwof_current_version , 'all' );
                wp_enqueue_style( 'wwof_vex-theme-plain_css' , WWOF_JS_ROOT_URL . 'lib/vex/css/vex-theme-plain.css' , array() , $this->_wwof_current_version , 'all' );
                wp_enqueue_style( 'wwof_WholesalePage_css' , WWOF_CSS_ROOT_URL . 'WholesalePage.css' , array( 'dashicons' ) , $this->_wwof_current_version , 'all' );
                wp_enqueue_style( 'wwof_lightbox' , WWOF_CSS_ROOT_URL . 'Lightbox.css' , array() , $this->_wwof_current_version , 'all' );

                // Scripts
                wp_enqueue_script( 'wwof_ajaxq_js' , WWOF_JS_ROOT_URL . 'lib/ajaxq.js' , array( 'jquery' ) , $this->_wwof_current_version );
                wp_enqueue_script( 'wwof_fancybox_js' , WWOF_JS_ROOT_URL . 'lib/fancybox/jquery.fancybox.pack.js' , array( 'jquery' ) , $this->_wwof_current_version );
                wp_enqueue_script( 'wwof_vex_js' , WWOF_JS_ROOT_URL . 'lib/vex/js/vex.combined.min.js' , array( 'jquery' ) , $this->_wwof_current_version );
                wp_enqueue_script( 'wwof_FrontEndAjaxServices_js' , WWOF_JS_ROOT_URL . 'app/modules/FrontEndAjaxServices.js' , array( 'jquery' ) , $this->_wwof_current_version );
                wp_localize_script( 'wwof_FrontEndAjaxServices_js' , 'Ajax' , array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );
                wp_enqueue_script( 'wwof_WholesalePage_js' , WWOF_JS_ROOT_URL . 'app/WholesalePage.js' , array( 'jquery' ) , $this->_wwof_current_version );
				wp_localize_script( 'wwof_WholesalePage_js',
                                    'Options',
                                    array(
										'disable_pagination'        => get_option( 'wwof_general_disable_pagination' ),
                                        'display_details_on_popup'  => get_option( 'wwof_general_display_product_details_on_popup' ),
										'products_per_page'         => ( $products_per_page ) ? $products_per_page : $WWOF_SETTINGS_DEFAULT_PPP,
                                        'no_variation_message'      => __( 'No variation selected' , 'woocommerce-wholesale-order-form' ),
                                        'errors_on_adding_products' => __( 'Errors occured while adding selected products.' , 'woocommerce-wholesale-order-form' ),
                                        'error_quantity'            => __( 'Please choose the quantity of items you wish to add to your cart…' , 'woocommerce-wholesale-order-form' ),
										'no_quantity_inputted'      => __( 'Please enter a valid value.' , 'woocommerce-wholesale-order-form' ),
										'invalid_quantity'          => __( 'Please enter a valid value. The two nearest valid values are {low} and {high}' , 'woocommerce-wholesale-order-form' ),
										'invalid_quantity_min_max'  => __( 'Please enter a valid value. The entered value is either lower than the allowed minimum ({min}) or higher than the allowed maximum ({max}).' , 'woocommerce-wholesale-order-form' ),
										'view_cart'                 => __( 'View Cart' , 'woocommerce-wholesale-order-form' ),
										'cart_url'                  => wc_get_cart_url()
                                    )
                                );

            }

        }

	    /**
	     * Execute model.
	     *
	     * @since 1.6.6
	     * @access public
	     */
	    public function run() {

            // Load Backend CSS and JS
            add_action( 'admin_enqueue_scripts' , array( $this , 'wwof_load_back_end_styles_and_scripts' ) );

            // Load Frontend CSS and JS
            add_action( 'wp_enqueue_scripts' , array( $this , 'wwof_load_front_end_styles_and_scripts' ) );

	    }
	}
}

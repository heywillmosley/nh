<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit ; // Exit if accessed directly
}

/**
 * Handle payment plans enqueues.
 * 
 * @class SUMO_PP_Enqueues
 * @category Class
 */
class SUMO_PP_Enqueues {

    /**
     * SUMO_PP_Enqueues constructor.
     */
    public function __construct() {
        add_action( 'admin_enqueue_scripts' , array ( $this , 'admin_script' ) ) ;
        add_action( 'admin_enqueue_scripts' , array ( $this , 'admin_style' ) ) ;
        add_action( 'wp_enqueue_scripts' , array ( $this , 'frontend_script' ) ) ;
        add_filter( 'woocommerce_screen_ids' , array ( $this , 'load_woocommerce_enqueues' ) , 1 ) ;
    }

    /**
     * Register and enqueue a script for use.
     *
     * @uses   wp_enqueue_script()
     * @access public
     * @param  string   $handle
     * @param  string   $path
     * @param  array   $localize_data
     * @param  string[] $deps
     * @param  string   $version
     * @param  boolean  $in_footer
     */
    public function enqueue_script( $handle , $path = '' , $localize_data = array () , $deps = array ( 'jquery' ) , $version = SUMO_PP_PLUGIN_VERSION , $in_footer = false ) {
        wp_register_script( $handle , $path , $deps , $version , $in_footer ) ;

        $name = str_replace( '-' , '_' , $handle ) ;
        wp_localize_script( $handle , $name , $localize_data ) ;
        wp_enqueue_script( $handle ) ;
    }

    /**
     * Register and enqueue a styles for use.
     *
     * @uses   wp_enqueue_style()
     * @access public
     * @param  string   $handle
     * @param  string   $path
     * @param  string[] $deps
     * @param  string   $version
     * @param  string   $media
     * @param  boolean  $has_rtl
     */
    public function enqueue_style( $handle , $path = '' , $deps = array () , $version = SUMO_PP_PLUGIN_VERSION , $media = 'all' , $has_rtl = false ) {
        wp_register_style( $handle , $path , $deps , $version , $media , $has_rtl ) ;
        wp_enqueue_style( $handle ) ;
    }

    /**
     * Return asset URL.
     *
     * @param string $path
     * @return string
     */
    public function get_asset_url( $path ) {
        return SUMO_PP_PLUGIN_URL . "/assets/{$path}" ;
    }

    /**
     * Enqueue jQuery UI events
     */
    public function enqueue_jQuery_ui() {
        $this->enqueue_script( 'sumo-pp-jquery-ui' , $this->get_asset_url( 'js/jquery-ui/jquery-ui.js' ) ) ;
        $this->enqueue_style( 'sumo-pp-jquery-ui' , $this->get_asset_url( 'css/jquery-ui.css' ) ) ;
    }

    /**
     * Enqueue Footable.
     */
    public function enqueue_footable_scripts() {

        $this->enqueue_script( 'sumo-pp-footable' , $this->get_asset_url( 'js/footable/footable.js' ) ) ;
        $this->enqueue_script( 'sumo-pp-footable-sort' , $this->get_asset_url( 'js/footable/footable.sort.js' ) ) ;
        $this->enqueue_script( 'sumo-pp-footable-paginate' , $this->get_asset_url( 'js/footable/footable.paginate.js' ) ) ;
        $this->enqueue_script( 'sumo-pp-footable-filter' , $this->get_asset_url( 'js/footable/footable.filter.js' ) ) ;
        $this->enqueue_script( 'sumo-pp-footable-action' , $this->get_asset_url( 'js/footable/sumo-pp-footable.js' ) ) ;

        $this->enqueue_style( 'sumo-pp-footable-core' , $this->get_asset_url( 'css/footable/footable.core.css' ) ) ;
        $this->enqueue_style( 'sumo-pp-footable-standalone' , $this->get_asset_url( 'css/footable/footable.standalone.css' ) ) ;
        $this->enqueue_style( 'sumo-pp-footable-bootstrap' , $this->get_asset_url( 'css/footable/bootstrap.css' ) ) ;
        $this->enqueue_style( 'sumo-pp-footable-chosen' , $this->get_asset_url( 'css/footable/chosen.css' ) ) ;
    }

    /**
     * Enqueue WC Multiselect field
     */
    public function enqueue_wc_multiselect() {
        wp_enqueue_script( 'wc-enhanced-select' ) ;
    }

    /**
     * Enqueue Jquery tipTip
     */
    public function enqueue_jquery_tiptip() {
        $this->enqueue_script( 'sumo-pp-jquery-tiptip-lib' , $this->get_asset_url( 'js/jquery-tiptip/jquery.tipTip.js' ) ) ;
        $this->enqueue_script( 'sumo-pp-jquery-tiptip' , $this->get_asset_url( 'js/jquery-tiptip/sumo-pp-my-tipTip.js' ) ) ;
        $this->enqueue_style( 'sumo-pp-jquery-tiptip' , $this->get_asset_url( 'css/sumo-pp-jquery.tipTip.css' ) ) ;
    }

    /**
     * Perform script localization in backend.
     */
    public function admin_script() {

        //Welcome page
        if ( isset( $_GET[ 'page' ] ) && $_GET[ 'page' ] === 'sumopaymentplans-welcome-page' ) {
            $this->enqueue_script( 'sumo-pp-admin-welcome-page' , $this->get_asset_url( 'js/admin/sumo-pp-admin-welcome-page.js' ) ) ;
        }

        //Admin Page.
        switch ( get_post_type() ) {
            case 'sumo_payment_plans':
            case 'sumo_pp_payments':
                $this->enqueue_script( 'sumo-pp-admin-dashboard' , $this->get_asset_url( 'js/admin/sumo-pp-admin-dashboard.js' ) , array (
                    'wp_ajax_url'        => admin_url( 'admin-ajax.php' ) ,
                    'get_period_options' => _sumo_pp_get_period_options() ,
                    'get_post_type'      => get_post_type() ,
                    'add_note_nonce'     => wp_create_nonce( 'sumo-pp-add-payment-note' ) ,
                    'delete_note_nonce'  => wp_create_nonce( 'sumo-pp-delete-payment-note' ) ,
                    'admin_notice'       => __( 'Total payment amount should be greater than or equal to 100!!' , _sumo_pp()->text_domain ) ,
                ) ) ;

                $this->enqueue_jQuery_ui() ;
                $this->enqueue_footable_scripts() ;
                $this->enqueue_jquery_tiptip() ;
                // Disable WP Auto Save on Edit Page.
                wp_dequeue_script( 'autosave' ) ;
                break ;
            case 'sumo_pp_masterlog':
                $this->enqueue_jquery_tiptip() ;
                break ;
            case 'product':
                $this->enqueue_script( 'sumo-pp-admin-product-settings' , $this->get_asset_url( 'js/admin/sumo-pp-admin-product-settings.js' ) , array (
                    'decimal_sep'         => get_option( 'woocommerce_price_decimal_sep' , '.' ) ,
                    'get_html_data_nonce' => wp_create_nonce( 'sumo-pp-get-payment-plan-search-field' ) ,
                ) ) ;
                $this->enqueue_wc_multiselect() ;
                $this->enqueue_footable_scripts() ;
                break ;
        }

        //Admin Tab Settings Page.
        if ( isset( $_GET[ 'page' ] ) && 'sumo_pp_settings' === $_GET[ 'page' ] ) {
            switch ( isset( $_GET[ 'tab' ] ) ? $_GET[ 'tab' ] : '' ) {
                case 'order_payment_plan':
                    $this->enqueue_script( 'sumo-pp-admin-order-payment-plan-settings' , $this->get_asset_url( 'js/admin/sumo-pp-admin-order-payment-plan-settings.js' ) ) ;
                    break ;
                case 'bulk_action':
                    $this->enqueue_script( 'sumo-pp-admin-bulk-action-settings' , $this->get_asset_url( 'js/admin/sumo-pp-admin-bulk-action-settings.js' ) , array (
                        'wp_ajax_url'         => admin_url( 'admin-ajax.php' ) ,
                        'update_nonce'        => wp_create_nonce( 'bulk-update-payment-plans' ) ,
                        'optimization_nonce'  => wp_create_nonce( 'bulk-update-optimization' ) ,
                        'wp_create_nonce'     => wp_create_nonce( 'search-products' ) ,
                        'get_html_data_nonce' => wp_create_nonce( 'sumo-pp-get-payment-plan-search-field' ) ,
                    ) ) ;
                    break ;
                default :
                    $this->enqueue_script( 'sumo-pp-admin-general-settings' , $this->get_asset_url( 'js/admin/sumo-pp-admin-general-settings.js' ) , array (
                        'get_html_data_nonce' => wp_create_nonce( 'sumo-pp-get-payment-plan-search-field' ) ,
                    ) ) ;
                    break ;
            }
            $this->enqueue_jQuery_ui() ;
            $this->enqueue_wc_multiselect() ;
        }
    }

    /**
     * Load style in backend.
     */
    public function admin_style() {
        //Welcome page
        if ( isset( $_GET[ 'page' ] ) && $_GET[ 'page' ] === 'sumopaymentplans-welcome-page' ) {
            $this->enqueue_style( 'sumo-pp-admin-welcome-page' , $this->get_asset_url( 'css/sumo-pp-admin-welcome-page.css' ) ) ;
        }

        if ( in_array( get_post_type() , array ( 'sumo_payment_plans' , 'sumo_pp_payments' ) ) ) {
            $this->enqueue_style( 'sumo-pp-admin-dashboard' , $this->get_asset_url( 'css/sumo-pp-admin-dashboard.css' ) ) ;
        }
    }

    /**
     * Perform script localization in frontend.
     * @global object $post
     */
    public function frontend_script() {
        global $post ;

        if ( apply_filters( 'sumopaymentplans_enqueue_payment_type_selector' , is_product() ) ) {
            $this->enqueue_script( 'sumo-pp-single-product-page' , $this->get_asset_url( 'js/frontend/sumo-pp-single-product-page.js' ) , array (
                'wp_ajax_url'                         => admin_url( 'admin-ajax.php' ) ,
                'product'                             => isset( $post->ID ) ? $post->ID : false ,
                'get_wc_booking_deposit_fields_nonce' => wp_create_nonce( 'sumo-pp-get-payment-type-fields' ) ,
            ) ) ;
        }
        if ( is_checkout() ) {
            $this->enqueue_script( 'sumo-pp-checkout-page' , $this->get_asset_url( 'js/frontend/sumo-pp-checkout-page.js' ) , array (
                'wp_ajax_url'                          => admin_url( 'admin-ajax.php' ) ,
                'current_user_id'                      => get_current_user_id() ,
                'checkout_order_payment_plan_nonce'    => wp_create_nonce( 'sumo-pp-checkout-order-payment-plan' ) ,
                'can_user_deposit_payment_in_checkout' => SUMO_PP_Order_Payment_Plan::can_user_deposit_payment_in_checkout() ,
                'force_guest'                          => 'yes' === get_option( 'woocommerce_enable_guest_checkout' ) ,
            ) ) ;
        }
        if ( is_account_page() ) {
            $this->enqueue_script( 'sumo-pp-my-account-page' , $this->get_asset_url( 'js/frontend/sumo-pp-my-account-page.js' ) , array (
                'wp_ajax_url'           => admin_url( 'admin-ajax.php' ) ,
                'show_more_notes_label' => __( 'Show More' , _sumo_pp()->text_domain ) ,
                'show_less_notes_label' => __( 'Show Less' , _sumo_pp()->text_domain ) ,
            ) ) ;

            $this->enqueue_footable_scripts() ;
            $this->enqueue_jquery_tiptip() ;
        }
    }

    /**
     * Load WooCommerce enqueues.
     * @global object $typenow
     * @param array $screen_ids
     * @return array
     */
    public function load_woocommerce_enqueues( $screen_ids ) {
        global $typenow ;

        $new_screen = get_current_screen() ;

        if ( in_array( $typenow , array ( 'sumo_pp_payments' ) ) || (isset( $_GET[ 'page' ] ) && 'sumo_pp_settings' === $_GET[ 'page' ] ) ) {
            $screen_ids[] = $new_screen->id ;
        }
        return $screen_ids ;
    }

}

return new SUMO_PP_Enqueues() ;

<?php

/**
 * Plugin Name: SUMO Payment Plans
 * Plugin URI:
 * Description: SUMO Payment Plans is a Comprehensive WooCommerce Payment Plan plugin using which you can configure multiple Payment Plans like Deposits with Balance Payment, Fixed Amount Installments, Variable Amount Installments, Down Payments with Installments, etc in your WooCommerce Shop.
 * Version: 3.0
 * Author: Fantastic Plugins
 * Author URI: http://fantasticplugins.com
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit ; // Exit if accessed directly
}

/** Initiate Payment Plans class.
 * 
 * @class SUMOPaymentPlans
 * @category Class
 */
final class SUMOPaymentPlans {

    /**
     * Payment Plans version.
     * 
     * @var string 
     */

    public $version = '3.0' ;

    /**
     * Payment Plans prefix.
     * 
     * @var string 
     */
    public $prefix = '_sumo_pp_' ;

    /**
     * Payment Plans Text domain.
     * 
     * @var string 
     */
    public $text_domain = 'sumopaymentplans' ;

    /**
     * Get loaded enqueues instance.
     * @var SUMO_PP_Enqueues object 
     */
    public $enqueues ;

    /**
     * Get Query instance.
     * @var SUMO_PP_Query object 
     */
    public $query ;

    /**
     * The single instance of the class.
     */
    protected static $instance = null ;

    /**
     * SUMOPaymentPlans constructor.
     */
    public function __construct() {

        //Prevent fatal error by load the files when you might call init hook.
        include_once( ABSPATH . 'wp-admin/includes/plugin.php' ) ;

        if ( ! $this->is_woocommerce_active() ) {
            return ;  // Return to stop the existing function to be call 
        }

        $this->define_constants() ;
        $this->include_files() ;
        $this->init_hooks() ;
    }

    /**
     * Main SUMOPaymentPlans Instance.
     * Ensures only one instance of SUMOPaymentPlans is loaded or can be loaded.
     * 
     * @return SUMOPaymentPlans - Main instance.
     */
    public static function instance() {
        if ( is_null( self::$instance ) ) {
            self::$instance = new self() ;
        }
        return self::$instance ;
    }

    /**
     * Check WooCommerce Plugin is Active.
     * @return boolean
     */
    public function is_woocommerce_active() {
        //Prevent Header Problem.
        add_action( 'init' , array ( $this , 'prevent_header_already_sent_problem' ) , 1 ) ;
        //Display warning if woocommerce is not active.
        add_action( 'init' , array ( $this , 'woocommerce_dependency_warning_message' ) ) ;

        if ( is_multisite() && is_plugin_active_for_network( 'woocommerce/woocommerce.php' ) && is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
            return true ;
        } else if ( is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
            return true ;
        }
        return false ;
    }

    /**
     * Prevent header problem while plugin activates.
     */
    public function prevent_header_already_sent_problem() {
        ob_start() ;
    }

    public function woocommerce_dependency_warning_message() {
        if ( ! $this->is_woocommerce_active() && is_admin() ) {
            $error = "<div class='error'><p> SUMO Payment Plans Plugin requires WooCommerce Plugin should be Active !!! </p></div>" ;
            echo $error ;
        }
        return ;
    }

    /**
     * Define constants.
     */
    private function define_constants() {
        $this->define( 'SUMO_PP_PLUGIN_FILE' , __FILE__ ) ;
        $this->define( 'SUMO_PP_PLUGIN_BASENAME' , plugin_basename( __FILE__ ) ) ;
        $this->define( 'SUMO_PP_PLUGIN_BASENAME_DIR' , dirname( plugin_basename( __FILE__ ) ) . '/' ) ;
        $this->define( 'SUMO_PP_PLUGIN_DIR' , plugin_dir_path( __FILE__ ) ) ;
        $this->define( 'SUMO_PP_PLUGIN_TEMPLATE_PATH' , plugin_dir_path( __FILE__ ) . 'templates/' ) ;
        $this->define( 'SUMO_PP_PLUGIN_URL' , untrailingslashit( plugins_url( '/' , __FILE__ ) ) ) ;
        $this->define( 'SUMO_PP_PLUGIN_VERSION' , $this->version ) ;
        $this->define( 'SUMO_PP_PLUGIN_PREFIX' , $this->prefix ) ;
        $this->define( 'SUMO_PP_PLUGIN_TEXT_DOMAIN' , $this->text_domain ) ;
        $this->define( 'SUMO_PP_PLUGIN_CRON_INTERVAL' , 300 ) ; //in seconds
    }

    /**
     * Define constant if not already set.
     *
     * @param string      $name  Constant name.
     * @param string|bool $value Constant value.
     */
    private function define( $name , $value ) {
        if ( ! defined( $name ) ) {
            define( $name , $value ) ;
        }
    }

    /**
     * Include required core files used in admin and on the frontend.
     */
    private function include_files() {
        //Welcome page upon plugin activation
        include_once('includes/welcome.php') ;
        //Abstracts
        include_once('includes/abstracts/abstract-sumo-pp-admin-settings.php') ;
        include_once('includes/abstracts/abstract-sumo-pp-payment-cron-job.php') ;

        include_once('includes/class-sumo-pp-order.php') ;
        include_once('includes/class-sumo-pp-payment-cron-job.php') ;

        include_once('includes/sumo-pp-functions.php') ;
        include_once('includes/sumo-pp-product-functions.php') ;
        include_once('includes/sumo-pp-conditional-functions.php') ;
        include_once('includes/sumo-pp-template-functions.php') ;
        include_once('includes/sumo-pp-payment-functions.php') ;
        include_once('includes/sumo-pp-load-emails.php') ;

        $this->query = include_once('includes/class-sumo-pp-query.php') ;

        include_once('includes/admin/class-sumo-pp-admin-settings.php') ;
        include_once('includes/admin/class-sumo-pp-admin-post-types.php') ;
        include_once('includes/admin/class-sumo-pp-admin-meta-boxes.php') ;
        include_once('includes/admin/class-sumo-pp-admin-product-settings.php') ;

        include_once('includes/class-sumo-pp-comments.php') ;
        include_once('includes/class-sumo-pp-frontend.php') ;
        include_once('includes/class-sumo-pp-order-payment-plan.php') ;
        include_once('includes/class-sumo-pp-payment-order.php') ;
        include_once('includes/class-sumo-pp-my-account.php') ;

        $this->enqueues = include_once('includes/class-sumo-pp-enqueues.php') ;

        include_once('includes/class-sumo-pp-ajax.php') ;
    }

    /**
     * Hook into actions and filters.
     */
    private function init_hooks() {
        register_activation_hook( __FILE__ , array ( $this , 'welcome_screen_upon_activate' ) ) ;
        register_deactivation_hook( __FILE__ , array ( $this , 'init_upon_deactivation' ) ) ;
        add_action( 'plugins_loaded' , array ( $this , 'set_language_to_translate' ) ) ; //Register String Translation
        add_action( 'init' , array ( $this , 'init' ) ) ;
        add_filter( 'cron_schedules' , array ( $this , 'cron_schedules' ) , 9999 ) ;
        add_action( 'admin_head' , array ( $this , 'style_inline' ) ) ;
        add_action( 'wp_head' , array ( $this , 'style_inline' ) ) ;
    }

    /**
     *  Init Welcome page
     */
    public function welcome_screen_upon_activate() {
        set_transient( '_welcome_screen_activation_redirect_payment_plans' , true , 30 ) ;
    }

    /**
     * Fire upon deactivating SUMO Payment Plans
     */
    public function init_upon_deactivation() {
        wp_clear_scheduled_hook( 'sumopaymentplans_cron_interval' ) ;
    }

    /**
     *  Load language files. 
     */
    public function set_language_to_translate() {
        load_plugin_textdomain( $this->text_domain , false , SUMO_PP_PLUGIN_BASENAME_DIR . 'languages' ) ;
    }

    /**
     * Schedule Payment Cron interval for recurrence
     * @param array $schedules
     * @return array
     */
    public function cron_schedules( $schedules ) {

        $schedules[ 'sumopaymentplans_cron_interval' ] = array (
            'interval' => SUMO_PP_PLUGIN_CRON_INTERVAL ,
            'display'  => sprintf( __( 'Every %d Minutes' , $this->text_domain ) , SUMO_PP_PLUGIN_CRON_INTERVAL / 60 )
                ) ;

        return $schedules ;
    }

    /**
     * Init SUMOPaymentPlans when WordPress Initialises. 
     */
    public function init() {
        $this->update_plugin_version() ;

        //Init Payment backgound process
        include_once( 'includes/payment-background-process/class-sumo-pp-payment-background-updater.php' ) ;
        include_once('includes/privacy/class-sumo-pp-privacy.php') ;

        if ( class_exists( 'WC_Bookings' ) ) {
            include_once( 'includes/compatibilities/class-sumo-pp-wc-bookings.php' ) ;
        }
        if ( class_exists( 'SUMO_Bookings' ) ) {
            include_once( 'includes/compatibilities/class-sumo-pp-sumo-bookings.php' ) ;
        }
        if ( class_exists( 'SUMOPreOrders' ) ) {
            include_once( 'includes/compatibilities/class-sumo-pp-sumo-preorders.php' ) ;
        }
        if ( class_exists( 'Tribe__Tickets__Main' ) ) {
            include_once( 'includes/compatibilities/class-sumo-pp-event-tickets.php' ) ;
        }
    }

    /**
     * Check SUMO Payment Plans version and run updater
     */
    private function update_plugin_version() {
        if ( $this->version !== get_option( $this->prefix . 'version' ) ) {
            delete_option( $this->prefix . 'version' ) ;
            add_option( $this->prefix . 'version' , $this->version ) ;

            SUMO_PP_Admin_Settings::save_default_options() ;
        }
    }

    /**
     * Apply inline CSS.
     */
    public function style_inline() {
        global $wp ;

        $is_user_subscriptions_table = (is_callable( 'is_account_page' ) && is_callable( '_sumo_pp_is_wc_version' ) && is_account_page() && ((_sumo_pp_is_wc_version( '<' , '2.6' ) && isset( $_GET[ 'payment-id' ] )) || isset( $wp->query_vars[ 'sumo-pp-my-payments' ] ) || isset( $wp->query_vars[ 'sumo-pp-view-payment' ] ))) ;

        if ( 'sumo_pp_payments' === get_post_type() || $is_user_subscriptions_table ) {
            echo '<style type="text/css">' ;

            ob_start() ;

            _sumo_pp_get_template( 'sumo-pp-dynamic-css.php' ) ;

            ob_get_contents() ;

            echo '</style>' ;
        }
    }

}

/**
 * Main instance of SUMOPaymentPlans.
 * Returns the main instance of SUMOPaymentPlans.
 *
 * @return SUMOPaymentPlans
 */
function _sumo_pp() {
    return SUMOPaymentPlans::instance() ;
}

/**
 * Run SUMO Payment Plans
 */
_sumo_pp() ;

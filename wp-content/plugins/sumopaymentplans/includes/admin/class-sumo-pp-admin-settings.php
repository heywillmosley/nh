<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit ; // Exit if accessed directly
}

/**
 * Handle Admin menus, post types and settings.
 * 
 * @class SUMO_PP_Admin_Settings
 * @category Class
 */
class SUMO_PP_Admin_Settings {

    /**
     * Setting pages.
     *
     * @var array
     */
    private static $settings = array () ;

    /**
     * Init SUMO_PP_Admin_Settings.
     */
    public static function init() {
        add_action( 'init' , __CLASS__ . '::register_post_types' ) ;
        add_action( 'admin_menu' , __CLASS__ . '::admin_menus' ) ;
        add_filter( 'plugin_row_meta' , __CLASS__ . '::plugin_row_meta' , 10 , 2 ) ;
        add_filter( 'plugin_action_links_' . SUMO_PP_PLUGIN_BASENAME , __CLASS__ . '::plugin_action_links' ) ;
        add_action( 'sumopaymentplans_reset_options' , __CLASS__ . '::reset_options' ) ;
        add_filter( 'woocommerce_account_settings' , __CLASS__ . '::add_wc_account_settings' ) ;
    }

    /**
     * Show action links on the plugin screen.
     *
     * @param	mixed $links Plugin Action links
     * @return	array
     */
    public static function plugin_action_links( $links ) {
        $setting_page_link = '<a  href="' . admin_url( 'admin.php?page=sumo_pp_settings' ) . '">Settings</a>' ;
        array_unshift( $links , $setting_page_link ) ;
        return $links ;
    }

    /**
     * Show row meta on the plugin screen.
     *
     * @param	mixed $links Plugin Row Meta
     * @param	mixed $file  Plugin Base file
     * @return	array
     */
    public static function plugin_row_meta( $links , $file ) {
        if ( SUMO_PP_PLUGIN_BASENAME == $file ) {
            $row_meta = array (
                'about'   => '<a href="' . esc_url( admin_url( 'admin.php?page=sumopaymentplans-welcome-page' ) ) . '" aria-label="' . esc_attr__( 'About' , _sumo_pp()->text_domain ) . '">' . esc_html__( 'About' , _sumo_pp()->text_domain ) . '</a>' ,
                'support' => '<a href="' . esc_url( 'http://fantasticplugins.com/support/' ) . '" aria-label="' . esc_attr__( 'Support' , _sumo_pp()->text_domain ) . '">' . esc_html__( 'Support' , _sumo_pp()->text_domain ) . '</a>' ,
                    ) ;

            return array_merge( $links , $row_meta ) ;
        }

        return ( array ) $links ;
    }

    /**
     * Register Custom Post Types.
     */
    public static function register_post_types() {

        //For Payments.
        register_post_type( 'sumo_pp_payments' , array (
            'labels'       => array (
                'name'               => _x( 'Payments' , 'general name' , _sumo_pp()->text_domain ) ,
                'singular_name'      => _x( 'Payment' , 'singular name' , _sumo_pp()->text_domain ) ,
                'menu_name'          => _x( 'Payments' , 'admin menu' , _sumo_pp()->text_domain ) ,
                'name_admin_bar'     => _x( 'Payment' , 'add new on admin bar' , _sumo_pp()->text_domain ) ,
                'add_new'            => _x( 'Add New' , 'Payment' , _sumo_pp()->text_domain ) ,
                'add_new_item'       => __( 'Add New Payment' , _sumo_pp()->text_domain ) ,
                'new_item'           => __( 'New Payment' , _sumo_pp()->text_domain ) ,
                'edit_item'          => __( 'Edit Payment' , _sumo_pp()->text_domain ) ,
                'view_item'          => __( 'View Payment' , _sumo_pp()->text_domain ) ,
                'all_items'          => __( 'Payments' , _sumo_pp()->text_domain ) ,
                'search_items'       => __( 'Search Payment' , _sumo_pp()->text_domain ) ,
                'parent_item_colon'  => __( 'Parent Payments:' , _sumo_pp()->text_domain ) ,
                'not_found'          => __( 'No Payment Found.' , _sumo_pp()->text_domain ) ,
                'not_found_in_trash' => __( 'No Payment found in Trash.' , _sumo_pp()->text_domain )
            ) ,
            'description'  => __( 'This is where store payments are stored.' , _sumo_pp()->text_domain ) ,
            'public'       => false ,
            'show_ui'      => true ,
            'show_in_menu' => _sumo_pp()->text_domain ,
            'rewrite'      => false ,
            'has_archive'  => false ,
            'supports'     => false ,
            'capabilities' => array (
                'create_posts' => 'do_not_allow' ,
                'delete_post'  => 'manage_options' ,
                'edit_post'    => 'manage_options' ,
                'delete_posts' => true ,
            ) ,
        ) ) ;

        //For Payment Plans.
        register_post_type( 'sumo_payment_plans' , array (
            'labels'              => array (
                'name'               => _x( 'Payment Plans' , 'general name' , _sumo_pp()->text_domain ) ,
                'singular_name'      => _x( 'Payment Plan' , 'singular name' , _sumo_pp()->text_domain ) ,
                'menu_name'          => _x( 'Payment Plans' , 'admin menu' , _sumo_pp()->text_domain ) ,
                'name_admin_bar'     => _x( 'Payment Plan' , 'add new on admin bar' , _sumo_pp()->text_domain ) ,
                'add_new'            => _x( 'Add New' , 'Payment Plan' , _sumo_pp()->text_domain ) ,
                'add_new_item'       => __( 'Add New Payment Plan' , _sumo_pp()->text_domain ) ,
                'new_item'           => __( 'New Payment Plan' , _sumo_pp()->text_domain ) ,
                'edit_item'          => __( 'Edit Payment Plan' , _sumo_pp()->text_domain ) ,
                'view_item'          => __( 'View Payment Plan' , _sumo_pp()->text_domain ) ,
                'all_items'          => __( 'Payment Plans' , _sumo_pp()->text_domain ) ,
                'search_items'       => __( 'Search Payment Plan' , _sumo_pp()->text_domain ) ,
                'parent_item_colon'  => __( 'Parent Payment Plans:' , _sumo_pp()->text_domain ) ,
                'not_found'          => __( 'No Payment Plan Found.' , _sumo_pp()->text_domain ) ,
                'not_found_in_trash' => __( 'No Payment Plan found in Trash.' , _sumo_pp()->text_domain )
            ) ,
            'description'         => __( 'This is where payment plans are stored.' , _sumo_pp()->text_domain ) ,
            'public'              => false ,
            'show_ui'             => true ,
            'capability_type'     => 'sumo_payment_plans' ,
            'publicly_queryable'  => false ,
            'exclude_from_search' => true ,
            'show_in_menu'        => _sumo_pp()->text_domain ,
            'show_in_admin_bar'   => false ,
            'show_in_nav_menus'   => false ,
            'rewrite'             => false ,
            'hierarchical'        => false ,
            'query_var'           => false ,
            'supports'            => array ( 'title' ) ,
            'has_archive'         => false ,
            'capabilities'        => array (
                'edit_post'          => 'manage_options' ,
                'edit_posts'         => 'manage_options' ,
                'edit_others_posts'  => 'manage_options' ,
                'publish_posts'      => 'manage_options' ,
                'read_post'          => 'manage_options' ,
                'read_private_posts' => 'manage_options' ,
                'delete_post'        => 'manage_options' ,
                'delete_posts'       => true ,
            )
        ) ) ;

        //For Payment Cron Jobs
        register_post_type( 'sumo_pp_cron_jobs' , array (
            'labels'       => array (
                'name'               => _x( 'Cron Jobs' , 'general name' , _sumo_pp()->text_domain ) ,
                'singular_name'      => _x( 'Cron Jobs' , 'singular name' , _sumo_pp()->text_domain ) ,
                'menu_name'          => _x( 'Cron Jobs' , 'admin menu' , _sumo_pp()->text_domain ) ,
                'name_admin_bar'     => _x( 'Cron Jobs' , 'add new on admin bar' , _sumo_pp()->text_domain ) ,
                'add_new'            => _x( 'Add New' , 'Payment Plans' , _sumo_pp()->text_domain ) ,
                'add_new_item'       => __( 'Add New Cron Job' , _sumo_pp()->text_domain ) ,
                'new_item'           => __( 'New Cron Job' , _sumo_pp()->text_domain ) ,
                'edit_item'          => __( 'Edit Cron Job' , _sumo_pp()->text_domain ) ,
                'view_item'          => __( 'View Cron Job' , _sumo_pp()->text_domain ) ,
                'all_items'          => __( 'Scheduled Cron Jobs' , _sumo_pp()->text_domain ) ,
                'search_items'       => __( 'Search Cron Job' , _sumo_pp()->text_domain ) ,
                'parent_item_colon'  => __( 'Parent Cron Jobs:' , _sumo_pp()->text_domain ) ,
                'not_found'          => __( 'No Cron Job Found.' , _sumo_pp()->text_domain ) ,
                'not_found_in_trash' => __( 'No Cron Job found in Trash.' , _sumo_pp()->text_domain )
            ) ,
            'description'  => __( 'This is where payment cron jobs are stored.' , _sumo_pp()->text_domain ) ,
            'public'       => false ,
            'show_ui'      => apply_filters( 'sumopaymentplans_show_cron_jobs_post_type_ui' , false ) ,
            'show_in_menu' => _sumo_pp()->text_domain ,
            'rewrite'      => false ,
            'has_archive'  => false ,
            'supports'     => false ,
            'capabilities' => array (
                'create_posts' => 'do_not_allow' ,
                'delete_post'  => 'manage_options' ,
                'edit_post'    => 'manage_options' ,
                'delete_posts' => true ,
            ) ,
        ) ) ;

        //For Master Log.
        register_post_type( 'sumo_pp_masterlog' , array (
            'labels'       => array (
                'name'               => _x( 'Master Log' , 'general name' , _sumo_pp()->text_domain ) ,
                'singular_name'      => _x( 'Master Log' , 'singular name' , _sumo_pp()->text_domain ) ,
                'menu_name'          => _x( 'Master Log' , 'admin menu' , _sumo_pp()->text_domain ) ,
                'name_admin_bar'     => _x( 'Master Log' , 'add new on admin bar' , _sumo_pp()->text_domain ) ,
                'add_new'            => _x( 'Add New' , 'payment plans' , _sumo_pp()->text_domain ) ,
                'add_new_item'       => __( 'Add New Log' , _sumo_pp()->text_domain ) ,
                'new_item'           => __( 'New Log' , _sumo_pp()->text_domain ) ,
                'edit_item'          => __( 'Edit Log' , _sumo_pp()->text_domain ) ,
                'view_item'          => __( 'View Log' , _sumo_pp()->text_domain ) ,
                'all_items'          => __( 'Master Log' , _sumo_pp()->text_domain ) ,
                'search_items'       => __( 'Search Log' , _sumo_pp()->text_domain ) ,
                'parent_item_colon'  => __( 'Parent Log:' , _sumo_pp()->text_domain ) ,
                'not_found'          => __( 'No Logs Found.' , _sumo_pp()->text_domain ) ,
                'not_found_in_trash' => __( 'No Logs found in Trash.' , _sumo_pp()->text_domain )
            ) ,
            'description'  => __( 'This is where payment transaction logs are stored.' , _sumo_pp()->text_domain ) ,
            'public'       => false ,
            'show_ui'      => true ,
            'show_in_menu' => _sumo_pp()->text_domain ,
            'rewrite'      => false ,
            'has_archive'  => false ,
            'supports'     => false ,
            'capabilities' => array (
                'create_posts' => 'do_not_allow' ,
                'delete_post'  => 'manage_options' ,
                'edit_post'    => 'manage_options' ,
                'delete_posts' => true ,
            )
        ) ) ;
    }

    /**
     * Add admin menu pages.
     */
    public static function admin_menus() {

        add_menu_page( __( 'SUMO Payment Plans' , _sumo_pp()->text_domain ) , __( 'SUMO Payment Plans' , _sumo_pp()->text_domain ) , 'manage_options' , _sumo_pp()->text_domain , null , SUMO_PP_PLUGIN_URL . '/assets/images/payments.png' , '56' ) ;
        add_submenu_page( _sumo_pp()->text_domain , __( 'Settings' , _sumo_pp()->text_domain ) , __( 'Settings' , _sumo_pp()->text_domain ) , 'manage_options' , 'sumo_pp_settings' , __CLASS__ . '::output' ) ;
    }

    /**
     * Include the settings page classes.
     */
    public static function get_settings_pages() {
        if ( empty( self::$settings ) ) {

            self::$settings[] = include( 'settings-page/class-sumo-pp-admin-general-settings.php' ) ;
            self::$settings[] = include( 'settings-page/class-sumo-pp-admin-order-payment-plan-settings.php' ) ;
            self::$settings[] = include( 'settings-page/class-sumo-pp-admin-message-settings.php' ) ;
            self::$settings[] = include( 'settings-page/class-sumo-pp-admin-advance-settings.php' ) ;
            self::$settings[] = include( 'settings-page/class-sumo-pp-admin-bulk-action-settings.php' ) ;
            self::$settings[] = include( 'settings-page/class-sumo-pp-admin-help-settings.php' ) ;
        }

        return self::$settings ;
    }

    /**
     * Settings page.
     *
     * Handles the display of the main SUMO Payment Plans settings page in admin.
     */
    public static function output() {
        global $current_section , $current_tab ;

        do_action( 'sumopaymentplans_settings_start' ) ;

        $current_tab     = ( empty( $_GET[ 'tab' ] ) ) ? 'general' : sanitize_text_field( urldecode( $_GET[ 'tab' ] ) ) ;
        $current_section = ( empty( $_REQUEST[ 'section' ] ) ) ? '' : sanitize_text_field( urldecode( $_REQUEST[ 'section' ] ) ) ;

        // Include settings pages
        self::get_settings_pages() ;

        do_action( 'sumopaymentplans_add_options_' . $current_tab ) ;
        do_action( 'sumopaymentplans_add_options' ) ;

        if ( $current_section ) {
            do_action( 'sumopaymentplans_add_options_' . $current_tab . '_' . $current_section ) ;
        }

        if ( ! empty( $_POST[ 'save' ] ) ) {
            if ( empty( $_REQUEST[ '_wpnonce' ] ) || ! wp_verify_nonce( $_REQUEST[ '_wpnonce' ] , 'sumo-payment-plans-settings' ) )
                die( __( 'Action failed. Please refresh the page and retry.' , _sumo_pp()->text_domain ) ) ;

            // Save settings if data has been posted
            do_action( 'sumopaymentplans_update_options_' . $current_tab ) ;
            do_action( 'sumopaymentplans_update_options' ) ;

            if ( $current_section ) {
                do_action( 'sumopaymentplans_update_options_' . $current_tab . '_' . $current_section ) ;
            }

            wp_safe_redirect( esc_url_raw( add_query_arg( array ( 'saved' => 'true' ) ) ) ) ;
            exit ;
        }
        if ( ! empty( $_POST[ 'reset' ] ) || ! empty( $_POST[ 'reset_all' ] ) ) {
            if ( empty( $_REQUEST[ '_wpnonce' ] ) || ! wp_verify_nonce( $_REQUEST[ '_wpnonce' ] , 'sumo-payment-plans-reset-settings' ) )
                die( __( 'Action failed. Please refresh the page and retry.' , _sumo_pp()->text_domain ) ) ;

            do_action( 'sumopaymentplans_reset_options_' . $current_tab ) ;

            if ( ! empty( $_POST[ 'reset_all' ] ) ) {
                do_action( 'sumopaymentplans_reset_options' ) ;
            }
            if ( $current_section ) {
                do_action( 'sumopaymentplans_reset_options_' . $current_tab . '_' . $current_section ) ;
            }

            wp_safe_redirect( esc_url_raw( add_query_arg( array ( 'saved' => 'true' ) ) ) ) ;
            exit ;
        }
        // Get any returned messages
        $error   = ( empty( $_GET[ 'wc_error' ] ) ) ? '' : urldecode( stripslashes( $_GET[ 'wc_error' ] ) ) ;
        $message = ( empty( $_GET[ 'wc_message' ] ) ) ? '' : urldecode( stripslashes( $_GET[ 'wc_message' ] ) ) ;

        if ( $error || $message ) {
            if ( $error ) {
                echo '<div id="message" class="error fade"><p><strong>' . esc_html( $error ) . '</strong></p></div>' ;
            } else {
                echo '<div id="message" class="updated fade"><p><strong>' . esc_html( $message ) . '</strong></p></div>' ;
            }
        } elseif ( ! empty( $_GET[ 'saved' ] ) ) {
            echo '<div id="message" class="updated fade"><p><strong>' . __( 'Your settings have been saved.' , _sumo_pp()->text_domain ) . '</strong></p></div>' ;
        }
        ?>
        <div class="wrap woocommerce">
            <form method="post" id="mainform" action="" enctype="multipart/form-data">
                <div class="icon32 icon32-woocommerce-settings" id="icon-woocommerce"><br /></div>
                <h2 class="nav-tab-wrapper woo-nav-tab-wrapper">
                    <?php
                    $tabs = apply_filters( 'sumopaymentplans_settings_tabs_array' , array () ) ;

                    foreach ( $tabs as $name => $label ) {
                        echo '<a href="' . admin_url( 'admin.php?page=sumo_pp_settings&tab=' . $name ) . '" class="nav-tab ' . ( $current_tab == $name ? 'nav-tab-active' : '' ) . '">' . $label . '</a>' ;
                    }
                    do_action( 'sumopaymentplans_settings_tabs' ) ;
                    ?>
                </h2>
                <?php
                switch ( $current_tab ) :
                    default :
                        do_action( 'sumopaymentplans_sections_' . $current_tab ) ;
                        do_action( 'sumopaymentplans_settings_' . $current_tab ) ;
                        break ;
                endswitch ;
                ?>
                <?php if ( apply_filters( 'sumopaymentplans_submit_' . $current_tab , true ) ) : ?>
                    <p class="submit">
                        <?php if ( ! isset( $GLOBALS[ 'hide_save_button' ] ) ) : ?>
                            <input name="save" class="button-primary" type="submit" value="<?php _e( 'Save changes' , _sumo_pp()->text_domain ) ; ?>" />
                        <?php endif ; ?>
                        <input type="hidden" name="subtab" id="last_tab" />
                        <?php wp_nonce_field( 'sumo-payment-plans-settings' ) ; ?>
                    </p>
                <?php endif ; ?>
            </form>
            <?php if ( apply_filters( 'sumopaymentplans_reset_' . $current_tab , true ) ) : ?>
                <form method="post" id="reset_mainform" action="" enctype="multipart/form-data" style="float: left; margin-top: -52px; margin-left: 159px;">
                    <input name="reset" class="button-secondary" type="submit" value="<?php _e( 'Reset' , _sumo_pp()->text_domain ) ; ?>"/>
                    <input name="reset_all" class="button-secondary" type="submit" value="<?php _e( 'Reset All' , _sumo_pp()->text_domain ) ; ?>"/>
                    <?php wp_nonce_field( 'sumo-payment-plans-reset-settings' ) ; ?>
                </form>    
            <?php endif ; ?>
        </div>
        <?php
    }

    /**
     * Default options.
     *
     * Sets up the default options used on the settings page.
     */
    public static function save_default_options( $reset_all = false ) {

        if ( empty( self::$settings ) ) {
            self::get_settings_pages() ;
        }

        foreach ( self::$settings as $tab ) {
            if ( ! isset( $tab->settings ) || ! is_array( $tab->settings ) ) {
                continue ;
            }

            $tab->add_options( $reset_all ) ;
        }
    }

    /**
     * Reset All settings
     */
    public static function reset_options() {

        self::save_default_options( true ) ;
    }

    /**
     * Add privacy setings under WooCommerce Privacy
     * @param array $settings
     * @return array
     */
    public static function add_wc_account_settings( $settings ) {
        $original_settings = $settings ;

        if ( ! empty( $original_settings ) ) {
            $new_settings = array () ;

            foreach ( $original_settings as $pos => $setting ) {
                if ( ! isset( $setting[ 'id' ] ) ) {
                    continue ;
                }

                switch ( $setting[ 'id' ] ) {
                    case 'woocommerce_erasure_request_removes_order_data':
                        $new_settings[ $pos + 1 ] = array (
                            'title'         => __( 'Account erasure requests' , _sumo_pp()->text_domain ) ,
                            'desc'          => __( 'Remove personal data from SUMO Payment Plans and its related Orders' , _sumo_pp()->text_domain ) ,
                            /* Translators: %s URL to erasure request screen. */
                            'desc_tip'      => sprintf( __( 'When handling an <a href="%s">account erasure request</a>, should personal data within SUMO Payment Plans be retained or removed?' , _sumo_pp()->text_domain ) , esc_url( admin_url( 'tools.php?page=remove_personal_data' ) ) ) ,
                            'id'            => _sumo_pp()->prefix . 'erasure_request_removes_payment_data' ,
                            'type'          => 'checkbox' ,
                            'default'       => 'no' ,
                            'checkboxgroup' => '' ,
                            'autoload'      => false ,
                                ) ;
                        break ;
                }
            }
            if ( ! empty( $new_settings ) ) {
                foreach ( $new_settings as $pos => $new_setting ) {
                    array_splice( $settings , $pos , 0 , array ( $new_setting ) ) ;
                }
            }
        }
        return $settings ;
    }

}

SUMO_PP_Admin_Settings::init() ;

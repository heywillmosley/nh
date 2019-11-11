<?php
/**
 * Plugin Name: PW WooCommerce BOGO Pro
 * Plugin URI: https://www.pimwick.com/pw-bogo/
 * Description: Makes Buy One, Get One promotions so easy!
 * Version: 2.105
 * Author: Pimwick, LLC
 * Author URI: https://www.pimwick.com
 * Text Domain: pw-woocommerce-bogo-free
 *
 * WC requires at least: 2.6.13
 * WC tested up to: 3.7.0
 *
 * Copyright: © Pimwick, LLC
*/

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) {
    exit;
}

// Verify this isn't called directly.
if ( !function_exists( 'add_action' ) ) {
    echo 'Hi there!  I\'m just a plugin, not much I can do when called directly.';
    exit;
}

if ( ! class_exists( 'PW_BOGO' ) ) :

final class PW_BOGO {

    private $use_coupons = true;
    private $active_bogos_cache = null;

    function __construct() {
        add_action( 'plugins_loaded', array( $this, 'plugins_loaded' ) );
        add_action( 'woocommerce_init', array( $this, 'woocommerce_init' ) );
    }

    function plugins_loaded() {
        load_plugin_textdomain( 'pw-woocommerce-bogo-free', false, basename( dirname( __FILE__ ) ) . '/languages' );
    }

    function woocommerce_init() {

        defined( 'PW_BOGO_FIXED_CART_COUPON' ) or define( 'PW_BOGO_FIXED_CART_COUPON', false );
        defined( 'PW_BOGO_REQUIRES_PRIVILEGE' ) or define( 'PW_BOGO_REQUIRES_PRIVILEGE', 'manage_woocommerce' );
        defined( 'PW_BOGO_DISCOUNT_PRICE_INCLUDES_TAX' ) or define( 'PW_BOGO_DISCOUNT_PRICE_INCLUDES_TAX', false );
        defined( 'PW_BOGO_ALLOW_HIGHER_PRICED_DISCOUNTED_PRODUCTS' ) or define( 'PW_BOGO_ALLOW_HIGHER_PRICED_DISCOUNTED_PRODUCTS', true );
        defined( 'PW_BOGO_ADJUST_OTHER_PERCENTAGE_COUPONS' ) or define( 'PW_BOGO_ADJUST_OTHER_PERCENTAGE_COUPONS', false );

        $this->use_coupons = boolval( get_option( 'pw_bogo_use_coupons', true ) );

        // If WooCommerce does not have Coupons enabled, we can't utilize them.
        if ( 'no' === get_option( 'woocommerce_enable_coupons', 'no' ) ) {
            $this->use_coupons = false;
        }

        add_action( 'init', array( $this, 'register_post_types' ), 9 );

        if ( is_admin() ) {

            require_once( 'includes/class-pimwick-license-manager.php' );
            $this->license = new Pimwick_License_Manager( __FILE__ );

            add_action( 'admin_menu', array( $this, 'admin_menu' ) );
            add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
            add_filter( 'manage_edit-pw_bogo_columns', array( $this, 'edit_pw_bogo_columns' ) );
            add_action( 'manage_pw_bogo_posts_custom_column', array( $this, 'pw_bogo_posts_custom_column' ) );
            add_action( 'post_row_actions', array( $this, 'post_row_actions' ), 10, 2 );
            add_action( 'add_meta_boxes_pw_bogo', array( $this, 'meta_boxes' ) );
            add_action( 'save_post', array( $this, 'save_post' ), 1, 2 );
            add_action( 'wp_ajax_pw-bogo-activation', array( $this, 'ajax_activation' ) );
            add_action( 'pre_get_posts', array( $this, 'pre_get_posts') );
            add_filter( 'wp_count_posts', array( $this, 'wp_count_posts' ), 10, 3 );
            add_filter( 'woocommerce_order_get_items', array( $this, 'woocommerce_order_get_items' ), 10, 2 );
        }

        add_action( 'woocommerce_after_calculate_totals', array( $this, 'woocommerce_after_calculate_totals' ) );
        add_action( 'woocommerce_checkout_create_order', array( $this, 'woocommerce_checkout_create_order' ), 10, 2 );

        if ( true === $this->use_coupons ) {
            add_filter( 'woocommerce_coupon_discount_types', array( $this, 'woocommerce_coupon_discount_types' ), 10, 2 );
            add_filter( 'woocommerce_coupon_is_valid_for_product', array( $this, 'woocommerce_coupon_is_valid_for_product' ), 10, 4 );
            add_filter( 'woocommerce_coupon_get_apply_quantity', array( $this, 'woocommerce_coupon_get_apply_quantity' ), 10, 4 );
            add_filter( 'woocommerce_coupon_get_discount_amount', array( $this, 'woocommerce_coupon_get_discount_amount' ), 99, 5 );
            add_filter( 'woocommerce_get_shop_coupon_data', array( $this, 'woocommerce_get_shop_coupon_data' ), 10, 2 );
            add_action( 'woocommerce_add_to_cart', array( $this, 'maybe_apply_bogo_coupon' ) );
            add_action( 'woocommerce_check_cart_items', array( $this, 'maybe_apply_bogo_coupon' ) );
            add_filter( 'woocommerce_coupon_message', array( $this, 'woocommerce_coupon_message' ), 10, 3 );
            add_filter( 'woocommerce_coupon_is_valid', array( $this, 'woocommerce_coupon_is_valid' ), 99, 2 );
            add_filter( 'woocommerce_cart_totals_coupon_label', array( $this, 'woocommerce_cart_totals_coupon_label' ), 10, 2 );
            add_action( 'woocommerce_applied_coupon', array( $this, 'woocommerce_applied_coupon' ), 99, 1 );
            add_filter( 'woocommerce_apply_with_individual_use_coupon', array( $this, 'woocommerce_apply_with_individual_use_coupon' ), 10, 4 );

            if ( $this->wc_min_version( '3.0' ) ) {
                add_action( 'woocommerce_new_order_item', array( $this, 'woocommerce_new_order_item' ), 10, 3 );
            } else {
                add_action( 'woocommerce_order_add_coupon', array( $this, 'woocommerce_order_add_coupon' ), 10, 5 );
            }
        } else {
            add_action( 'woocommerce_cart_calculate_fees' , array( $this, 'woocommerce_cart_calculate_fees' ) );
            add_action( 'woocommerce_cart_contents_total' , array( $this, 'woocommerce_cart_contents_total' ) );
        }
    }

    function register_post_types() {
        if ( post_type_exists('pw_bogo') ) {
            return;
        }

        $labels = array(
            'name'                  => _x( 'PW BOGO', 'Post Type General Name', 'pw-woocommerce-bogo-free' ),
            'singular_name'         => _x( 'PW BOGO', 'Post Type Singular Name', 'pw-woocommerce-bogo-free' ),
            'menu_name'             => __( 'PW BOGO', 'pw-woocommerce-bogo-free' ),
            'name_admin_bar'        => __( 'PW BOGO', 'pw-woocommerce-bogo-free' ),
            'archives'              => __( 'PW BOGO Archives', 'pw-woocommerce-bogo-free' ),
            'parent_item_colon'     => __( 'Parent PW BOGO:', 'pw-woocommerce-bogo-free' ),
            'all_items'             => __( 'PW BOGO', 'pw-woocommerce-bogo-free' ),
            'add_new_item'          => __( 'Add New PW BOGO', 'pw-woocommerce-bogo-free' ),
            'add_new'               => __( 'Create New PW BOGO', 'pw-woocommerce-bogo-free' ),
            'new_item'              => __( 'New PW BOGO', 'pw-woocommerce-bogo-free' ),
            'edit_item'             => __( 'Edit PW BOGO', 'pw-woocommerce-bogo-free' ),
            'update_item'           => __( 'Update PW BOGO', 'pw-woocommerce-bogo-free' ),
            'view_item'             => __( 'View PW BOGO', 'pw-woocommerce-bogo-free' ),
            'search_items'          => __( 'Search PW BOGO', 'pw-woocommerce-bogo-free' ),
            'not_found'             => __( 'Not found', 'pw-woocommerce-bogo-free' ),
            'not_found_in_trash'    => __( 'Not found in Trash', 'pw-woocommerce-bogo-free' ),
            'featured_image'        => __( 'PW BOGO Logo', 'pw-woocommerce-bogo-free' ),
            'set_featured_image'    => __( 'Set PW BOGO Logo', 'pw-woocommerce-bogo-free' ),
            'remove_featured_image' => __( 'Remove PW BOGO Logo', 'pw-woocommerce-bogo-free' ),
            'use_featured_image'    => __( 'Use as PW BOGO Logo', 'pw-woocommerce-bogo-free' ),
            'insert_into_item'      => __( 'Insert into item', 'pw-woocommerce-bogo-free' ),
            'uploaded_to_this_item' => __( 'Uploaded to this item', 'pw-woocommerce-bogo-free' ),
            'items_list'            => __( 'PW BOGO list', 'pw-woocommerce-bogo-free' ),
            'items_list_navigation' => __( 'PW BOGO list navigation', 'pw-woocommerce-bogo-free' ),
            'filter_items_list'     => __( 'Filter PW BOGO list', 'pw-woocommerce-bogo-free' ),
        );

        $args = array(
            'label'                 => __( 'PW BOGO', 'pw-woocommerce-bogo-free' ),
            'description'           => __( 'PW BOGO', 'pw-woocommerce-bogo-free' ),
            'labels'                => $labels,
            'supports'              => array( 'title' ),
            'show_ui'               => true,
            'show_in_menu'          => current_user_can( PW_BOGO_REQUIRES_PRIVILEGE ) ? 'pimwick' : false,
            'has_archive'           => true
        );

        register_post_type( 'pw_bogo', $args );
    }

    public static function version() {
        $data = get_plugin_data( __FILE__ );
        return $data['Version'];
    }

    public static function wc_min_version( $version ) {
        return version_compare( WC()->version, $version, ">=" );
    }

    function meta_boxes( $post ) {
        require( 'ui/meta-boxes.php' );

        add_meta_box( 'pw-bogo-about', __( 'About', 'pw-woocommerce-bogo-free' ), 'PW_BOGO_Meta_Boxes::about', 'pw_bogo', 'side', 'default' );

        if ( $this->license->is_premium() ) {
            if ( $this->license->is_expired() ) {
                add_meta_box( 'pw-bogo-renew', '<span style="color: red;">' . __( 'Expired license', 'pw-woocommerce-bogo-free' ) . '</span>', 'PW_BOGO_Meta_Boxes::renew', 'pw_bogo', 'normal', 'high' );
            }
            add_meta_box( 'pw-bogo-discount', __( 'Discount', 'pw-woocommerce-bogo-free' ), 'PW_BOGO_Meta_Boxes::discount', 'pw_bogo', 'normal', 'default' );
            add_meta_box( 'pw-bogo-products', __( 'Eligible Products', 'pw-woocommerce-bogo-free' ), 'PW_BOGO_Meta_Boxes::products', 'pw_bogo', 'normal', 'default' );
            add_meta_box( 'pw-bogo-discounted-products', __( 'Discounted Products', 'pw-woocommerce-bogo-free' ), 'PW_BOGO_Meta_Boxes::discounted_products', 'pw_bogo', 'normal', 'default' );
            add_meta_box( 'pw-bogo-dates', __( 'Dates', 'pw-woocommerce-bogo-free' ), 'PW_BOGO_Meta_Boxes::dates', 'pw_bogo', 'normal', 'default' );
            add_meta_box( 'pw-bogo-restrictions', __( 'Restrictions', 'pw-woocommerce-bogo-free' ), 'PW_BOGO_Meta_Boxes::restrictions', 'pw_bogo', 'normal', 'default' );
        } else {
            add_meta_box( 'pw-bogo-activation', __( 'Activation Required', 'pw-woocommerce-bogo-free' ), 'PW_BOGO_Meta_Boxes::activation', 'pw_bogo', 'normal', 'default' );

        }
    }

    function admin_menu() {
        if ( empty ( $GLOBALS['admin_page_hooks']['pimwick'] ) ) {
            add_menu_page(
                __( 'PW BOGO', 'pw-woocommerce-bogo-free' ),
                __( 'Pimwick Plugins', 'pw-woocommerce-bogo-free' ),
                PW_BOGO_REQUIRES_PRIVILEGE,
                'pimwick',
                '',
                plugins_url( '/assets/images/pimwick-icon-120x120.png', __FILE__ ),
                6
            );

            add_submenu_page(
                'pimwick',
                __( 'PW BOGO', 'pw-woocommerce-bogo-free' ),
                __( 'Pimwick Plugins', 'pw-woocommerce-bogo-free' ),
                PW_BOGO_REQUIRES_PRIVILEGE,
                'pimwick',
                ''
            );

            remove_submenu_page( 'pimwick', 'pimwick' );
        }

        remove_submenu_page( 'pimwick', 'pimwick-plugins' );
        add_submenu_page(
            'pimwick',
            __( 'Pimwick Plugins', 'pw-woocommerce-bogo-free' ),
            __( 'Our Plugins', 'pw-woocommerce-bogo-free' ),
            PW_BOGO_REQUIRES_PRIVILEGE,
            'pimwick-plugins',
            array( $this, 'other_plugins_page' )
        );
    }

    function other_plugins_page() {
        global $pimwick_more_handled;

        if ( !$pimwick_more_handled ) {
            $pimwick_more_handled = true;
            require( 'ui/more.php' );
        }
    }

    function admin_enqueue_scripts() {
        global $post_type;

        $data = get_plugin_data( __FILE__ );
        $version = $data['Version'];

        if ( 'pw_bogo' == $post_type ) {
            wp_register_style( 'jquery-ui-style', plugins_url( '/assets/css/jquery-ui-style.min.css', __FILE__ ), array(), $version );
            wp_enqueue_style( 'jquery-ui-style' );

            wp_enqueue_script( 'wc-admin-meta-boxes' );
            wp_enqueue_style( 'woocommerce_admin_styles' );

            wp_register_style( 'pwbogo-font-awesome', plugins_url( '/assets/css/font-awesome.min.css', __FILE__ ), array(), $version ); // 4.6.3
            wp_enqueue_style( 'pwbogo-font-awesome' );

            wp_register_style( 'pw-bogo', plugins_url( '/assets/css/style.css', __FILE__ ), array( 'woocommerce_admin_styles' ), $version );
            wp_enqueue_style( 'pw-bogo' );

            wp_enqueue_script( 'pw-bogo-admin-script', plugins_url( '/assets/js/script.js', __FILE__ ), array( 'wc-admin-meta-boxes' ), $version );
        }

        wp_register_style( 'pw-bogo-icon', plugins_url( '/assets/css/icon-style.css', __FILE__ ), array(), $version );
        wp_enqueue_style( 'pw-bogo-icon' );
    }

    function edit_pw_bogo_columns( $gallery_columns ) {
        $new_columns['cb'] = '<input type="checkbox" />';

        $new_columns['title'] = _x( 'Name', 'pw-woocommerce-bogo-free' );
        $new_columns['type'] = __( 'Buy One, Get One', 'pw-woocommerce-bogo-free' );
        $new_columns['begin_date'] = __( 'Begin Date', 'pw-woocommerce-bogo-free' );
        $new_columns['end_date'] = __( 'End Date', 'pw-woocommerce-bogo-free' );

        return $new_columns;
    }

    function pw_bogo_posts_custom_column( $column ) {
        global $post;

        switch ( $column ) {
            case 'type':
                $type = $post->type;
                if ( 'free' === $type || empty( $type )) {
                    echo __( 'Free', 'pw-woocommerce-bogo-free' );
                } else {
                    $percentage = $post->percentage;
                    echo $percentage . __( '% off', 'pw-woocommerce-bogo-free' );
                }
            break;

            case 'begin_date':
            case 'end_date':
                echo $post->{$column};
            break;
        }
    }

    function post_row_actions( $actions, $post ) {
        if ( $post->post_type == 'pw_bogo' ) {
            unset($actions['inline hide-if-no-js']);
        }

        return $actions;
    }

    function save_post( $post_id, $post ) {
        // $post_id and $post are required
        if ( empty( $post_id ) || empty( $post ) ) {
            return;
        }

        // Dont' save meta boxes for revisions or autosaves
        if ( defined( 'DOING_AUTOSAVE' ) || is_int( wp_is_post_revision( $post ) ) || is_int( wp_is_post_autosave( $post ) ) ) {
            return;
        }

        // Check the nonce
        if ( empty( $_POST['pw_bogo_meta_nonce'] ) || ! wp_verify_nonce( $_POST['pw_bogo_meta_nonce'], 'pw_bogo_save_data' ) ) {
            return;
        }

        // Check the post being saved == the $post_id to prevent triggering this call for other save_post events
        if ( empty( $_POST['post_ID'] ) || $_POST['post_ID'] != $post_id ) {
            return;
        }

        // Check user has permission to edit
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        $type                                   = wc_clean( $_POST['type'] );
        $buy_type                               = wc_clean( $_POST['buy_type'] );
        $buy_limit                              = wc_clean( $_POST['buy_limit'] );
        $get_limit                              = wc_clean( $_POST['get_limit'] );
        $percentage                             = wc_format_decimal( $_POST['percentage'] );
        $spend_aftertax                         = isset( $_POST['spend_aftertax'] ) ? 'yes' : 'no';

        if ( PW_BOGO::wc_min_version( '3.0' ) ) {
            $product_ids                        = isset( $_POST['product_ids'] ) ? implode( ',', $_POST['product_ids'] ) : '';
            $exclude_product_ids                = isset( $_POST['exclude_product_ids'] ) ? implode( ',', $_POST['exclude_product_ids'] ) : '';
            $discounted_product_ids             = isset( $_POST['discounted_product_ids'] ) ? implode( ',', $_POST['discounted_product_ids'] ) : '';
            $discounted_exclude_product_ids     = isset( $_POST['discounted_exclude_product_ids'] ) ? implode( ',', $_POST['discounted_exclude_product_ids'] ) : '';
        } else {
            $product_ids                        = implode( ',', array_filter( array_map( 'intval', explode( ',', $_POST['product_ids'] ) ) ) );
            $exclude_product_ids                = implode( ',', array_filter( array_map( 'intval', explode( ',', $_POST['exclude_product_ids'] ) ) ) );
            $discounted_product_ids             = implode( ',', array_filter( array_map( 'intval', explode( ',', $_POST['discounted_product_ids'] ) ) ) );
            $discounted_exclude_product_ids     = implode( ',', array_filter( array_map( 'intval', explode( ',', $_POST['discounted_exclude_product_ids'] ) ) ) );
        }

        $product_categories                     = isset( $_POST['product_categories'] ) ? array_map( 'intval', $_POST['product_categories'] ) : array();
        $exclude_product_categories             = isset( $_POST['exclude_product_categories'] ) ? array_map( 'intval', $_POST['exclude_product_categories'] ) : array();

        $identical_products_only                = isset( $_POST['identical_products_only'] ) ? 'yes' : 'no';
        $identical_variations_only              = isset( $_POST['identical_variations_only'] ) ? 'yes' : 'no';
        $ignore_discounted_products             = isset( $_POST['ignore_discounted_products'] ) ? 'yes' : 'no';
        $auto_add_discounted_products           = isset( $_POST['auto_add_discounted_products'] ) ? 'yes' : 'no';
        $restrict_discount_quantity             = isset( $_POST['restrict_discount_quantity'] ) ? 'yes' : 'no';
        $discounted_product_categories          = isset( $_POST['discounted_product_categories'] ) ? array_map( 'intval', $_POST['discounted_product_categories'] ) : array();
        $discounted_exclude_product_categories  = isset( $_POST['discounted_exclude_product_categories'] ) ? array_map( 'intval', $_POST['discounted_exclude_product_categories'] ) : array();

        $begin_date                             = wc_clean( $_POST['begin_date'] );
        $end_date                               = wc_clean( $_POST['end_date'] );

        $discount_limit                         = isset( $_POST['discount_limit'] ) ? absint( $_POST['discount_limit'] ) : '';
        $redemption_limit                       = isset( $_POST['redemption_limit'] ) ? absint( $_POST['redemption_limit'] ) : '';
        $redemption_count                       = isset( $_POST['redemption_count'] ) ? absint( $_POST['redemption_count'] ) : '';
        $coupon_code                            = isset( $_POST['coupon_code'] ) ? wc_clean( trim( $_POST['coupon_code'] ) ) : '';
        $free_shipping                          = isset( $_POST['free_shipping'] ) ? 'yes' : 'no';
        $individual_use                         = isset( $_POST['individual_use'] ) ? 'yes' : 'no';
        $apply_with_individual_use_coupons      = isset( $_POST['apply_with_individual_use_coupons'] ) ? 'yes' : 'no';
        $exclude_sale_items                     = isset( $_POST['exclude_sale_items'] ) ? 'yes' : 'no';

        update_post_meta( $post_id, 'type', $type );
        update_post_meta( $post_id, 'buy_type', $buy_type );
        update_post_meta( $post_id, 'buy_limit', $buy_limit );
        update_post_meta( $post_id, 'get_limit', $get_limit );
        update_post_meta( $post_id, 'percentage', $percentage );
        update_post_meta( $post_id, 'spend_aftertax', $spend_aftertax );

        update_post_meta( $post_id, 'product_ids', $product_ids );
        update_post_meta( $post_id, 'exclude_product_ids', $exclude_product_ids );
        update_post_meta( $post_id, 'product_categories', $product_categories );
        update_post_meta( $post_id, 'exclude_product_categories', $exclude_product_categories );

        update_post_meta( $post_id, 'identical_products_only', $identical_products_only );
        update_post_meta( $post_id, 'identical_variations_only', $identical_variations_only );
        update_post_meta( $post_id, 'ignore_discounted_products', $ignore_discounted_products );
        update_post_meta( $post_id, 'auto_add_discounted_products', $auto_add_discounted_products );
        update_post_meta( $post_id, 'restrict_discount_quantity', $restrict_discount_quantity );
        update_post_meta( $post_id, 'discounted_product_ids', $discounted_product_ids );
        update_post_meta( $post_id, 'discounted_exclude_product_ids', $discounted_exclude_product_ids );
        update_post_meta( $post_id, 'discounted_product_categories', $discounted_product_categories );
        update_post_meta( $post_id, 'discounted_exclude_product_categories', $discounted_exclude_product_categories );

        update_post_meta( $post_id, 'begin_date', $begin_date );
        update_post_meta( $post_id, 'end_date', $end_date );

        update_post_meta( $post_id, 'discount_limit', $discount_limit );
        update_post_meta( $post_id, 'redemption_limit', $redemption_limit );
        update_post_meta( $post_id, 'redemption_count', $redemption_count );
        update_post_meta( $post_id, 'coupon_code', $coupon_code );
        update_post_meta( $post_id, 'free_shipping', $free_shipping );
        update_post_meta( $post_id, 'individual_use', $individual_use );
        update_post_meta( $post_id, 'apply_with_individual_use_coupons', $apply_with_individual_use_coupons );
        update_post_meta( $post_id, 'exclude_sale_items', $exclude_sale_items );
    }

    function woocommerce_cart_calculate_fees( $cart ) {
        $discounts = $this->get_discounts( $cart );
        $active_bogos = $this->get_active_bogos();

        foreach ( $discounts['totals'] as $bogo_id => $discount ) {

            // Get the coupon title.
            $bogo_title = 'BOGO';
            foreach ( $active_bogos as $bogo ) {
                if ( $bogo->ID == $bogo_id ) {
                    $bogo_title = $bogo->post_title;
                    break;
                }
            }

            $cart->add_fee( $bogo_title, ( $discount * -1 ) );
        }
    }

    function get_discounts( $cart ) {
        // Expand the list of cart items, one element per quantity.
        $cart_items = $this->flatten_cart( $cart );

        // Sort the cart by price from higest to lowest for the Eligible products, lowest to highest for the Discounted products.
        $cart_items_desc = $cart_items;
        $cart_items_asc = $cart_items;
        usort( $cart_items_desc, function( $a, $b ) { return ( floatval( $a['price'] ) > floatval( $b['price'] ) ) ? -1 : 1; } );
        usort( $cart_items_asc, function( $a, $b ) { return ( floatval( $a['price'] ) < floatval( $b['price'] ) ) ? -1 : 1; } );

        $discounts = array();
        $discounts['totals'] = array();
        $discounts['cart_item_totals'] = array();
        $discounts['cart_item_quantities'] = array();
        $already_applied_cart_items = array();

        foreach ( $this->get_active_bogos() as $bogo ) {
            $bogo_total_discount = 0;
            $cart_item_totals = array();
            $cart_item_quantities = array();
            $considered_for_bogo = apply_filters( 'pw_bogo_already_applied_cart_items', $already_applied_cart_items );
            $bogo_percentage = !empty( $bogo->percentage ) ? $bogo->percentage : 100;
            $percentage = $bogo_percentage / 100;
            $discount_limit = absint( $bogo->discount_limit );
            $identical_products_only = ( 'yes' === $bogo->identical_products_only );
            $identical_variations_only = ( 'yes' === $bogo->identical_variations_only );
            $buy_type = !empty( $bogo->buy_type ) ? $bogo->buy_type : 'quantity';
            $buy_limit = !empty( $bogo->buy_limit ) ? $bogo->buy_limit : 1;
            $get_limit = !empty( $bogo->get_limit ) ? $bogo->get_limit : 1;
            $spend_aftertax = ( 'yes' === $bogo->spend_aftertax );

            if ( 'spend' == $buy_type ) {
                $spent_amount = $this->get_spent_amount( $bogo, $cart );
                if ( $spent_amount < $buy_limit ) {
                    continue;
                }
            }

            // When considering eligible items, add the non-discounted items first. This will make it so that we can discount more expensive
            // items if necessary, when the Eligible Products are wide-open and the Discounted Products are for a specific category.
            $eligible_items = array();
            foreach ( $cart_items_desc as $ci ) {
                if ( $this->is_cart_item_valid_for_bogo( $ci['cart_item'], $bogo ) && !$this->is_cart_item_valid_for_bogo( $ci['cart_item'], $bogo, true ) ) {
                    $eligible_items[ $ci['key'] ] = $ci['cart_item'];
                }
            }
            foreach ( $cart_items_desc as $ci ) {
                if ( $this->is_cart_item_valid_for_bogo( $ci['cart_item'], $bogo ) ) {
                    $eligible_items[ $ci['key'] ] = $ci['cart_item'];
                }
            }

            $discounted_items = array();
            foreach ( $cart_items_asc as $ci ) {
                if ( $this->is_cart_item_valid_for_bogo( $ci['cart_item'], $bogo, true ) ) {
                    $discounted_items[ $ci['key'] ] = $ci['cart_item'];
                }
            }

            if ( false === PW_BOGO_ALLOW_HIGHER_PRICED_DISCOUNTED_PRODUCTS ) {
                $highest_price = 0;
                foreach ( $eligible_items as $ci ) {
                    $highest_price = max( $highest_price, $this->get_product_price( $ci['data'] ) );
                }

                foreach ( $discounted_items as $discounted_item_key => $discounted_item ) {
                    $price = $this->get_product_price( $discounted_item['data'] );
                    if ( $price > $highest_price ) {
                        unset( $discounted_items[ $discounted_item_key ] );
                    }
                }
            }

            $id = '0';
            $item_index[ $id ] = 0;
            $discounted_item_count[ $id ] = 0;
            $discount_iterations = 0;

            foreach ( $eligible_items as $eligible_cart_item_key => $cart_item ) {
                if ( in_array( $eligible_cart_item_key, $considered_for_bogo ) ) {
                    continue;
                }

                $considered_for_bogo[] = $eligible_cart_item_key;

                if ( $identical_products_only === true ) {
                    if ( $identical_variations_only === true && $cart_item['variation_id'] != '0' ) {
                        $id = (string) $cart_item['variation_id'];
                    } else {
                        $id = (string) $cart_item['product_id'];
                    }
                }

                if ( !isset( $item_index[ $id ] ) ) { $item_index[ $id ] = 0; }
                if ( !isset( $discounted_item_count[ $id ] ) ) { $discounted_item_count[ $id ] = 0; }

                if ( $buy_type == 'quantity' ) {
                    $item_index[ $id ]++;
                    if ( $item_index[ $id ] < $buy_limit ) {
                        continue;
                    } else {
                        $discount_iterations++;
                        $item_index[ $id ] = 0;
                        $discounted_item_count[ $id ] = 0;
                    }
                }

                // Maximum number of times this deal can be used per order.
                if ( !empty( $discount_limit ) && $discount_iterations > $discount_limit ) {
                    break;
                }

                foreach ( $discounted_items as $discounted_cart_item_key => $discounted_cart_item ) {
                    if ( in_array( $discounted_cart_item_key, $considered_for_bogo ) ) {
                        continue;
                    }

                    if ( $identical_products_only === true ) {
                        if ( $identical_variations_only === true && $cart_item['variation_id'] != '0'  ) {
                            if ( $discounted_cart_item['product_id'] != $id ) {
                                continue;
                            }
                        } else {
                            if ( $discounted_cart_item['product_id'] != $id ) {
                                continue;
                            }
                        }
                    }

                    if ( $discounted_item_count[ $id ] >= $get_limit ) {
                        break;
                    }

                    $price = $this->get_product_price( $discounted_cart_item['data'], $spend_aftertax );
                    if ( !empty( $bogo->type ) && $bogo->type != 'free' ) {
                        $price *= $percentage;
                    }

                    if ( !isset( $cart_item_totals[ $discounted_cart_item['key'] ] ) ) {
                        $cart_item_totals[ $discounted_cart_item['key'] ] = 0;
                        $cart_item_quantities[ $discounted_cart_item['key'] ] = 0;
                    }

                    $cart_item_totals[ $discounted_cart_item['key'] ] += $price;
                    $cart_item_quantities[ $discounted_cart_item['key'] ] += 1;

                    $discounted_item_count[ $id ]++;
                    $considered_for_bogo[] = $discounted_cart_item_key;

                    foreach ( $considered_for_bogo as $cart_item_key ) {
                        if ( !in_array( $cart_item_key, $already_applied_cart_items ) ) {
                            $already_applied_cart_items[] = $cart_item_key;
                        }
                    }

                    $bogo_total_discount += $price;
                }
            }

            if ( !empty( $bogo_total_discount ) ) {
                $discounts['totals'][ $bogo->ID ] = $bogo_total_discount;
                $discounts['cart_item_totals'][ $bogo->ID ] = $cart_item_totals;
                $discounts['cart_item_quantities'][ $bogo->ID ] = $cart_item_quantities;
            }
        }

        return $discounts;
    }

    function get_product_price( $product, $force_exclude_tax = false ) {
        $price = 0;

        if ( function_exists( 'wc_get_price_excluding_tax' ) && function_exists( 'wc_get_price_including_tax' ) ) {
            if ( PW_BOGO_DISCOUNT_PRICE_INCLUDES_TAX === true || $force_exclude_tax === true ) {
                $product_price = wc_get_price_excluding_tax( $product );
            } else {
                if ( 'incl' === WC()->cart->tax_display_cart ) {
                    $product_price = wc_get_price_including_tax( $product );
                } else {
                    $product_price = wc_get_price_excluding_tax( $product );
                }
            }

            $price = apply_filters( 'woocommerce_cart_product_price', $product_price, $product );
        }

        // Old way of getting price.
        if ( empty( $price ) ) {
            $price = $product->get_price();
        }

        return $price;
    }

    function woocommerce_cart_contents_total( $cart_contents_total ) {
        WC()->cart->calculate_fees();
        $fees = WC()->cart->get_fees();
        foreach ( $this->get_active_bogos() as $bogo ) {
            $fee_id = sanitize_title( $bogo->post_title );
            foreach ( $fees as $fee ) {
                if ( $fee->id == $fee_id ) {
                    return wc_price( WC()->cart->cart_contents_total + $fee->amount );
                }
            }
        }

        return $cart_contents_total;
    }

    function woocommerce_applied_coupon( $coupon_code ) {
        remove_action( 'woocommerce_applied_coupon', array( $this, 'woocommerce_applied_coupon' ) );

        // After adding a coupon we will want to re-run this discount recalculation.
        add_filter( 'woocommerce_coupon_get_discount_amount', array( $this, 'woocommerce_coupon_get_discount_amount' ), 99, 5 );

        $active_bogos = $this->get_active_bogos( true );
        foreach ( $active_bogos as $bogo ) {
            if ( strtolower( $coupon_code ) == strtolower( $bogo->coupon_code ) ) {
                $success = false;
                $this->maybe_apply_bogo_coupon();

                foreach ( WC()->cart->get_applied_coupons() as $applied_coupon ) {
                    if ( $this->is_bogo_coupon( $applied_coupon, $bogo ) ) {
                        $success = true;
                        break;
                    }
                }

                // Remove the original 'Coupon applied' message so we're just left with the BOGO Coupon Applied message.
                if ( !$success ) {
                    $notices = wc_get_notices();
                    unset($notices['success'][0]);
                    $notices['success'] = array_values( $notices['success'] );
                    $coupon_temp = new WC_Coupon();
                    $notices['error'][] = $coupon_temp->get_coupon_error( WC_Coupon::E_WC_COUPON_NOT_APPLICABLE );
                    wc_set_notices( $notices );
                }
                break;
            }
        }

        add_action( 'woocommerce_applied_coupon', array( $this, 'woocommerce_applied_coupon' ), 99, 1 );
    }

    function woocommerce_apply_with_individual_use_coupon( $apply, $the_coupon, $coupon, $applied_coupons ) {
        if ( false === $apply && $this->wc_min_version( '3.0' ) ) {
            $active_bogos = $this->get_active_bogos( true );
            foreach ( $active_bogos as $bogo ) {
                if ( $this->is_bogo_coupon( $the_coupon->get_code(), $bogo ) ) {
                    if ( 'yes' === $bogo->apply_with_individual_use_coupons ) {
                        $apply = true;
                    }
                    break;
                }
            }
        }

        return $apply;
    }

    function woocommerce_coupon_is_valid( $valid_for_cart, $coupon ) {
        // Fix for interfering plugins such as WooCommerce Coupon Schedule.
        if ( !$valid_for_cart ) {
            if ( $this->wc_min_version( '3.0' ) ) {
                $coupon_code = $coupon->get_code();
            } else {
                $coupon_code = $coupon->code;
            }

            if ( $this->is_bogo_coupon( $coupon_code ) ) {
                $valid_for_cart = true;
            }
        }

        return $valid_for_cart;
    }

    function ajax_activation() {
        $this->license->activate_license( wc_clean( $_POST['license-key'] ) );

        $registration['active'] = $this->license->is_premium();
        $registration['error'] = $this->license->error;

        wp_send_json( $registration );
    }

    function is_cart_item_valid_for_bogo( $cart_item, $bogo, $discounted = false ) {
        // WPC Product Bundles for WooCommerce - This later may become configuration option.
        if ( isset( $cart_item['woosb_parent_id'] ) && !empty( $cart_item['woosb_parent_id'] ) ) {
            return false;
        }

        $product = $cart_item['data'];

        $product_id = $product->get_id();
        $variation_id = $product->get_id();
        $parent_id = !empty( $product->get_parent_id() ) ? $product->get_parent_id() : $product_id;

        return $this->is_product_valid_for_bogo( $product_id, $variation_id, $parent_id, $bogo, $discounted );
    }

    function is_product_valid_for_bogo( $product_id, $variation_id, $parent_id, $bogo, $discounted = false ) {
        $prefix = '';
        if ( true === $discounted && 'yes' !== $bogo->ignore_discounted_products ) {
            $prefix = 'discounted_';
        }

        // Exclude any on-sale items, if we need to.
        if ( 'yes' === $bogo->exclude_sale_items ) {
            $product_ids_on_sale = wc_get_product_ids_on_sale();

            if ( ! empty( $variation_id ) ) {
                if ( in_array( $variation_id, $product_ids_on_sale, true ) ) {
                    return false;
                }
            } else if ( in_array( $product_id, $product_ids_on_sale, true ) ) {
                return false;
            }
        }

        // If we have a coupon applied and that's not allowed, we can't use this BOGO deal.
        if ( 'yes' === $bogo->individual_use ) {
            foreach ( WC()->cart->get_applied_coupons() as $coupon ) {
                if ( !$this->is_bogo_coupon( $coupon, $bogo ) ) {
                    return false;
                }
            }
        }

        $included_products = array_filter( array_map( 'absint', explode( ',', get_post_meta( $bogo->ID, $prefix . 'product_ids', true ) ) ) );
        if ( count( $included_products ) > 0 ) {
            if ( !in_array( $product_id, $included_products ) && !in_array( $variation_id, $included_products ) && ( empty( $parent_id ) || !in_array( $parent_id, $included_products ) ) ) {
                return false;
            }
        }

        $excluded_products = array_filter( array_map( 'absint', explode( ',', get_post_meta( $bogo->ID, $prefix . 'exclude_product_ids', true ) ) ) );
        if ( count( $excluded_products ) > 0 ) {
            if ( in_array( $product_id, $excluded_products ) || in_array( $variation_id, $excluded_products ) || ( !empty( $parent_id ) && in_array( $parent_id, $excluded_products ) ) ) {
                return false;
            }
        }

        $product_cats = wc_get_product_cat_ids( $parent_id );
        $included_categories = get_post_meta( $bogo->ID, $prefix . 'product_categories', true );

        if ( count( $product_cats ) > 0 ) {
            if ( !empty( $included_categories ) ) {
                if ( count( array_intersect( $product_cats, $included_categories ) ) == 0 ) {
                    return false;
                }
            }

            $excluded_categories = get_post_meta( $bogo->ID, $prefix . 'exclude_product_categories', true );
            if ( !empty( $excluded_categories ) ) {
                if ( count( array_intersect( $product_cats, $excluded_categories ) ) > 0 ) {
                    return false;
                }
            }
        } else if ( count( $included_categories ) > 0 ) {
            return false;
        }

        return true;
    }

    function get_bogo( $bogo ) {
        if ( is_numeric( $bogo ) ) {
            $bogo = get_post( absint( $bogo ) );
            if ( !$bogo || $bogo->post_type != 'pw_bogo' ) {
                wp_die( __( 'Invalid bogo parameter for get_bogo().', 'pw-woocommerce-bogo-free' ) );
            }

        } elseif ( !$bogo instanceof WP_Post ) {
            wp_die( sprintf( __( '%s is not a valid type for get_bogo().', 'pw-woocommerce-bogo-free' ), gettype( $bogo ) ) );
        }

        return $bogo;
    }

    function get_active_bogos( $ignore_coupon_code = false ) {
        if ( is_null( $this->active_bogos_cache ) ) {
            $this->active_bogos_cache = array();

            $all_bogos = get_posts( array(
                'posts_per_page' => -1,
                'nopaging' => true,
                'post_type' => 'pw_bogo',
                'post_status' => 'publish'
            ) );

            // Switch to their local timezone.
            $configured_timezone = wc_timezone_string();
            if ( !empty( $configured_timezone ) ) {
                $original_timezone = date_default_timezone_get();
                date_default_timezone_set( $configured_timezone );
            }

            foreach ( $all_bogos as $bogo ) {
                if ( !empty( $bogo->begin_date ) && strtotime( $bogo->begin_date ) > time() ) {
                    continue;
                }

                if ( !empty( $bogo->end_date ) && strtotime( $bogo->end_date ) < time() ) {
                    continue;
                }

                if ( !empty( $bogo->redemption_limit ) && absint( $bogo->redemption_count ) >= absint( $bogo->redemption_limit ) ) {
                    continue;
                }

                $this->active_bogos_cache[] = $bogo;
            }

            // Now that we're done formatting, switch it back.
            if ( isset( $original_timezone ) ) {
                date_default_timezone_set( $original_timezone );
            }

            if ( !empty( $this->active_bogos_cache ) ) {
                // Sort the bogos so when we go looking they're in order.
                $this->sort_bogos( $this->active_bogos_cache );
            }
        }

        $bogos = array();

        if ( true === $ignore_coupon_code ) {
            $bogos = $this->active_bogos_cache;
        } else {
            foreach ( $this->active_bogos_cache as $bogo ) {
                if ( !empty( $bogo->coupon_code ) ) {
                    foreach ( WC()->cart->get_applied_coupons() as $coupon_code ) {
                        if ( strtolower( $coupon_code ) == strtolower( $bogo->coupon_code ) || $this->is_bogo_coupon( $coupon_code, $bogo ) ) {
                            $bogos[] = $bogo;
                            break;
                        }
                    }
                } else {
                    $bogos[] = $bogo;
                }
            }
        }

        return apply_filters( 'pw_bogo_active_bogos', $bogos );
    }

    function sort_bogos( &$bogos ) {
        usort( $bogos, function( $a, $b ) {
            $percentage_a = (int) ( $a->type == 'free' || empty( $a->type ) ) ? '100' : $a->percentage;
            $percentage_b = (int) ( $b->type == 'free' || empty( $b->type ) ) ? '100' : $b->percentage;

            if ( empty( $percentage_a ) ) { $percentage_a = 100; }
            if ( empty( $percentage_b ) ) { $percentage_b = 100; }

            // $scrooge means get the worst deal. If there are 2 active BOGOs, one is 50% off and the other
            // is 25% off, we'd return the 25% deal if $scrooge = true.
            $scrooge = false;
            if ( true === $scrooge ) {
                return ( $percentage_a > $percentage_b ) ? 1 : -1;
            } else {
                return ( $percentage_a < $percentage_b ) ? 1 : -1;
            }
        });
    }

    function validate_product_ids( $bogo, $cart ) {
        $product_ids = array_filter( array_map( 'absint', explode( ',', $bogo->product_ids ) ) );

        if ( count( $product_ids ) > 0 ) {
            $valid_for_cart = false;

            foreach( $cart->get_cart() as $cart_item_key => $cart_item ) {
                if ( in_array( $cart_item['product_id'], $product_ids ) || in_array( $cart_item['variation_id'], $product_ids ) || in_array( $cart_item['data']->get_parent(), $product_ids ) ) {
                    $valid_for_cart = true;
                    break;
                }
            }

            return $valid_for_cart;
        }
    }

    function validate_excluded_product_ids( $bogo, $cart ) {
        $exclude_product_ids = array_filter( array_map( 'absint', explode( ',', $bogo->exclude_product_ids ) ) );

        if ( count( $bogo->exclude_product_ids ) > 0 ) {
            $valid_for_cart = false;

            foreach( $cart->get_cart() as $cart_item_key => $cart_item ) {
                if ( in_array( $cart_item['product_id'], $bogo->product_ids ) || in_array( $cart_item['variation_id'], $bogo->product_ids ) || in_array( $cart_item['data']->get_parent(), $bogo->product_ids ) ) {
                    $valid_for_cart = true;
                    break;
                }
            }

            return $valid_for_cart;
        }
    }

    function is_bogo_coupon( $code, $bogo = '' ) {
        if ( !empty( $bogo ) ) {
            if ( strtolower( $code ) == strtolower( $bogo->coupon_code ) ) {
                return true;
            }

            if ( strtolower( $code ) == strtolower( $this->get_bogo_coupon_code( $bogo ) ) ) {
                return true;
            }

        } else {
            foreach ( $this->get_active_bogos( true ) as $active_bogo ) {
                if ( $this->is_bogo_coupon( $code, $active_bogo ) ) {
                    return true;
                }
            }
        }

        return false;
    }

    function get_bogo_coupon_code( $bogo ) {
        return wc_sanitize_taxonomy_name( $bogo->post_title ) . '-' . $bogo->ID;
    }

    function pre_get_posts( $query ) {
        // Prevents our internal BOGO coupons from appearing in the admin area.
        if ( $query->is_main_query() || $query->is_search() ) {
            if ( $query->query['post_type'] == 'shop_coupon' ) {
                $query->set( 'meta_query', array(
                    'relation' => 'AND',
                     array(
                        'key' => '_pw_bogo_id',
                        'compare' => 'NOT EXISTS',
                     ),
                ) );
            }
        }
    }

    function wp_count_posts( $counts, $type, $perm ) {
        global $wpdb;

        // Subtract the total number of published Store Credit coupons from the counter.
        if ( 'shop_coupon' === $type && property_exists( $counts, 'publish' ) ) {
            $store_credit_coupons = $wpdb->get_var( "SELECT COUNT(*) AS total FROM {$wpdb->posts} AS p JOIN {$wpdb->postmeta} AS m ON (m.post_id = p.ID AND m.meta_key = '_pw_bogo_id') WHERE p.post_type = 'shop_coupon' AND p.post_status = 'publish'" );
            $counts->publish -= $store_credit_coupons;
        }

        return $counts;
    }

    function woocommerce_order_get_items( $items, $order ) {
        foreach ( $items as $order_item_id => &$order_item ) {
            if ( is_a( $order_item, 'WC_Order_Item_Coupon' ) && isset( $order_item['item_meta']['pw_bogo_id'] ) ) {
                $bogo_id = is_array( $order_item['item_meta']['pw_bogo_id'] ) ? $order_item['item_meta']['pw_bogo_id'][0] : $order_item['item_meta']['pw_bogo_id'];
                $bogo = get_post( absint( $bogo_id ) );
                $order_item['name'] = sprintf( __( 'PW BOGO: %s', 'pw-woocommerce-bogo-free' ), $bogo->post_title );
            }
        }
        return $items;
    }

    function woocommerce_after_calculate_totals( $cart ) {

        remove_action( 'woocommerce_after_calculate_totals', array( $this, 'woocommerce_after_calculate_totals' ) );

        foreach ( $this->get_active_bogos() as $bogo ) {

            $ignore_discounted_products = $bogo->ignore_discounted_products;
            $auto_add_discounted_products = $bogo->auto_add_discounted_products;

            if ( 'yes' !== $auto_add_discounted_products ) {
                continue;
            }

            $buy_type = !empty( $bogo->buy_type ) ? $bogo->buy_type : 'quantity';
            $buy_limit = !empty( $bogo->buy_limit ) ? $bogo->buy_limit : 1;
            $identical_products_only = ( 'yes' === $bogo->identical_products_only );
            $identical_variations_only = ( 'yes' === $bogo->identical_variations_only );

            if ( $buy_type == 'spend' ) {
                $spent_amount = $this->get_spent_amount( $bogo, $cart );
                if ( $spent_amount < $buy_limit ) {
                    $this->maybe_add_discounted_item( $cart, $bogo, 0, 0 );
                    continue;
                }
            }

            if ( $identical_products_only === true ) {
                foreach ( $cart->get_cart() as $cart_item ) {
                    $product_id = $cart_item['product_id'];
                    $variation_id = ( $identical_variations_only === true ) ? $cart_item['variation_id'] : 0;

                    $this->maybe_add_discounted_item( $cart, $bogo, $product_id, $variation_id );
                }
            } else {
                $this->maybe_add_discounted_item( $cart, $bogo, 0, 0 );
            }
        }

        add_action( 'woocommerce_after_calculate_totals', array( $this, 'woocommerce_after_calculate_totals' ) );
    }

    function woocommerce_checkout_create_order( $order, $data ) {
        $all_bogos = get_posts( array(
            'posts_per_page' => -1,
            'post_type' => 'pw_bogo',
            'post_status' => 'publish'
        ) );

        foreach ( $order->get_items( 'coupon' ) as $coupon ) {
            foreach ( $all_bogos as $bogo ) {
                if ( $this->is_bogo_coupon( $coupon->get_code(), $bogo ) ) {
                    $redemption_count = absint( get_post_meta( $bogo->ID, 'redemption_count', true ) ) + 1;
                    update_post_meta( $bogo->ID, 'redemption_count', $redemption_count );
                }
            }
        }
    }

    function maybe_add_discounted_item( $cart, $bogo, $identical_product_id = 0, $identical_variation_id = 0 ) {

        $buy_type = !empty( $bogo->buy_type ) ? $bogo->buy_type : 'quantity';
        $buy_limit = !empty( $bogo->buy_limit ) ? $bogo->buy_limit : 1;
        $get_limit = !empty( $bogo->get_limit ) ? $bogo->get_limit : 1;
        $spend_aftertax = ( 'yes' === $bogo->spend_aftertax );
        $discount_limit = absint( $bogo->discount_limit );
        $identical_products_only = ( 'yes' === $bogo->identical_products_only );
        $identical_variations_only = ( 'yes' === $bogo->identical_variations_only );

        // Expand the list of cart items, one element per quantity.
        $cart_items = $this->flatten_cart( $cart, $identical_product_id, $identical_variation_id );

        // Sort the cart by price from higest to lowest for the Eligible products, lowest to highest for the Discounted products.
        $cart_items_desc = $cart_items;
        $cart_items_asc = $cart_items;
        usort( $cart_items_desc, function( $a, $b ) { return ( floatval( $a['price'] ) > floatval( $b['price'] ) ) ? -1 : 1; } );
        usort( $cart_items_asc, function( $a, $b ) { return ( floatval( $a['price'] ) < floatval( $b['price'] ) ) ? -1 : 1; } );

        $eligible_items = array();
        // For calculation purposes, add the Eligible-only items first.
        foreach ( $cart_items_desc as $ci ) {
            if ( $this->is_cart_item_valid_for_bogo( $ci['cart_item'], $bogo ) && ! $this->is_cart_item_valid_for_bogo( $ci['cart_item'], $bogo, true ) ) {
                $eligible_items[ $ci['key'] ] = $ci['cart_item'];
            }
        }
        // Now add the Discounted + Eligible items.
        foreach ( $cart_items_desc as $ci ) {
            if ( $this->is_cart_item_valid_for_bogo( $ci['cart_item'], $bogo ) && $this->is_cart_item_valid_for_bogo( $ci['cart_item'], $bogo, true ) ) {
                $eligible_items[ $ci['key'] ] = $ci['cart_item'];
            }
        }

        $discounted_items = array();
        foreach ( $cart_items_asc as $ci ) {
            if ( $this->is_cart_item_valid_for_bogo( $ci['cart_item'], $bogo, true ) ) {
                $discounted_items[ $ci['key'] ] = $ci['cart_item'];
            }
        }

        $products_to_add = array();
        $discount_iterations = 0;
        $processed_eligible_item_keys = array();

        $buy_amount = 0;

        while ( $buy_amount < $buy_limit && ( empty( $discount_limit ) || $discount_iterations < $discount_limit ) ) {

            // Get the next eligible item and remove it from the array.
            reset( $eligible_items );
            $eligible_item_key = key( $eligible_items );
            $eligible_item = array_shift( $eligible_items );
            if ( is_null( $eligible_item ) ) {
                break;
            }

            if ( 'quantity' === $buy_type ) {
                $buy_amount++;
            } else {
                if ( $eligible_item['quantity'] != 0 ) {
                    if ( true === $spend_aftertax ) {
                        $buy_amount += ( ( $eligible_item['line_subtotal'] + $eligible_item['line_subtotal_tax'] ) / $eligible_item['quantity'] );
                    } else {
                        $buy_amount += ( $eligible_item['line_subtotal'] / $eligible_item['quantity'] );
                    }
                } else {
                    $buy_amount += 0;
                }
            }

            $processed_eligible_item_keys[] = $eligible_item_key;

            // Once we're at our buy limit, begin adding items as necessary.
            if ( $buy_amount >= $buy_limit ) {
                $discount_iterations++;

                // reset the outer loop.
                $buy_amount = 0;

                // Remove the appropriate number of discounted+eligible items from the discounted pool.
                $purge_counter = 0;
                foreach ( $discounted_items as $discounted_item_key => $discounted_item ) {
                    if ( in_array( $discounted_item_key, $processed_eligible_item_keys ) ) {
                        $purge_counter++;
                        unset( $discounted_items[ $discounted_item_key ] );

                        if ( $purge_counter == $buy_limit ) {
                            break;
                        }
                    }
                }

                // Automatically add discounted items.
                $get_counter = 0;
                $get_quantity = $get_limit;
                while ( $get_counter < $get_quantity ) {
                    $get_counter++;

                    if ( count( $discounted_items ) > 0 ) {
                        reset( $discounted_items );
                        $discounted_item_key = key( $discounted_items );
                        $discounted_item = array_shift( $discounted_items );

                        // If we're using an eligible item as the qualifier, then we need to
                        // take that into account for the quantity that are discounted.
                        if ( in_array( $discounted_item_key, $processed_eligible_item_keys ) ) {
                            $get_quantity = $get_limit + 1;
                        }

                        // Remove the Discounted Item from the Eligible Item array (if it exists)
                        foreach ( $eligible_items as $ei_key => $ei_item ) {
                            if ( $discounted_item_key == $ei_key ) {
                                unset( $eligible_items[ $ei_key ] );
                                $processed_eligible_item_keys[] = $ei_key;
                            }
                        }

                    } else {
                        $products_to_add[] = $eligible_item;
                    }
                }
            }
        }

        if ( count( $products_to_add ) > 0 ) {
            if ( 'yes' === $bogo->ignore_discounted_products ) {
                $auto_add_product_ids = array_filter( array_map( 'absint', explode( ',', $bogo->product_ids ) ) );
            } else {
                $auto_add_product_ids = array_filter( array_map( 'absint', explode( ',', $bogo->discounted_product_ids ) ) );
            }

            if ( empty( $auto_add_product_ids ) ) {
                foreach ( $cart_items_asc as $ci ) {
                    $cart_item = $ci['cart_item'];
                    if ( $this->is_cart_item_valid_for_bogo( $cart_item, $bogo, true ) ) {
                        $auto_add_product_ids[] = !empty( $cart_item['variation_id'] ) ? $cart_item['variation_id'] : $cart_item['product_id'];
                        break;
                    }
                }
            }

            if ( count( $auto_add_product_ids ) > 0 ) {
                foreach ( $products_to_add as $x => $eligible_item ) {
                    if ( isset( $auto_add_product_ids[ $x ] ) ) {
                        $product_id = $auto_add_product_ids[ $x ];
                    } else {
                        $product_id = end( $auto_add_product_ids );
                    }

                    if ( $identical_variations_only === true && $this->is_cart_item_valid_for_bogo( $eligible_item, $bogo, true ) ) {
                        $product_id = !empty( $eligible_item['variation_id'] ) ? $eligible_item['variation_id'] : $eligible_item['product_id'];
                    } else if ( $identical_products_only === true && $this->is_cart_item_valid_for_bogo( $eligible_item, $bogo, true ) ) {
                        $product_id = $eligible_item['product_id'];
                    }

                    $variation_id = 0;
                    $variation_attributes = array();

                    if ( 'product_variation' === get_post_type( $product_id ) ) {
                        $variation_id = $product_id;
                        $product_id   = wp_get_post_parent_id( $variation_id );

                        $product = wc_get_product( $variation_id );
                        $variation_attributes = $product->get_variation_attributes();

                    } else {
                        $product = wc_get_product( $product_id );
                    }

                    $cart->add_to_cart( $product_id, 1, $variation_id, $variation_attributes );
                }
            }
        } else {

            // We have more discounted items in the cart than what applies to this promo. Optionally limit that quantity.
            if ( count( $discounted_items ) > 0 ) {
                $restrict_discount_quantity = !empty( $bogo->restrict_discount_quantity ) ? ( 'yes' === $bogo->restrict_discount_quantity ) : false;

                if ( true === $restrict_discount_quantity ) {
                    $discounted_item = end( $discounted_items );
                    $new_quantity = $discounted_item['quantity'] - count( $discounted_items );

                    $cart->set_quantity( $discounted_item['key'], $new_quantity );
                }
            }
        }
    }

    function woocommerce_coupon_discount_types( $discount_types ) {
        if ( !is_admin() ) {
            $discount_types['pw_bogo'] = 'PW BOGO';
        }

        return $discount_types;
    }

    function woocommerce_coupon_is_valid_for_product( $valid, $product, $coupon, $values ) {
        $code = $coupon->get_code();

        foreach ( $this->get_active_bogos( true ) as $bogo ) {
            if ( $this->is_bogo_coupon( $code, $bogo ) ) {
                $product_id = $product->get_id();
                $variation_id = $product->get_id();
                $parent_id = !empty( $product->get_parent_id() ) ? $product->get_parent_id() : $product_id;

                return $this->is_product_valid_for_bogo( $product_id, $variation_id, $parent_id, $bogo, true );
            }
        }

        return $valid;
    }

    function woocommerce_coupon_get_apply_quantity( $apply_quantity, $cart_item, $coupon, $wc_discount ) {
        if ( PW_BOGO_FIXED_CART_COUPON === true ) {
            return $apply_quantity;
        }

        $discounts = $this->get_discounts( WC()->cart );
        foreach ( $this->get_active_bogos( true ) as $bogo ) {
            if ( $this->is_bogo_coupon( $coupon->get_code(), $bogo ) ) {
                if ( isset( $discounts['cart_item_quantities'][ $bogo->ID ] ) && isset( $discounts['cart_item_quantities'][ $bogo->ID ][ $cart_item->key ] ) ) {
                    return $discounts['cart_item_quantities'][ $bogo->ID ][ $cart_item->key ];
                }
            }
        }

        return $apply_quantity;
    }

    function woocommerce_coupon_get_discount_amount( $discount_amount, $discounting_amount, $cart_item, $single, $coupon ) {
        if ( PW_BOGO_ADJUST_OTHER_PERCENTAGE_COUPONS && $coupon->is_type( array( 'percent' ) ) ) {
            foreach ( WC()->cart->get_applied_coupons() as $applied_coupon ) {
                if ( $this->is_bogo_coupon( $applied_coupon ) ) {
                    $discounting_amount -= WC()->cart->get_coupon_discount_amount( $applied_coupon, WC()->cart->display_cart_ex_tax );
                }
            }

            // Don't run this multiple times for each product.
            remove_filter( 'woocommerce_coupon_get_discount_amount', array( $this, 'woocommerce_coupon_get_discount_amount' ), 99, 5 );

            return (float) $coupon->get_amount() * ( $discounting_amount / 100 );
        }

        if ( $coupon->is_type( array( 'pw_bogo' ) ) ) {
            $discounts = $this->get_discounts( WC()->cart );
            foreach ( WC()->cart->get_applied_coupons() as $applied_coupon ) {
                foreach ( $this->get_active_bogos( true ) as $bogo ) {
                    if ( $this->is_bogo_coupon( $applied_coupon, $bogo ) ) {

                        if ( isset( $discounts['cart_item_totals'][ $bogo->ID ] ) && isset( $discounts['cart_item_totals'][ $bogo->ID ][ $cart_item['key'] ] ) ) {
                            $total = $discounts['cart_item_totals'][ $bogo->ID ][ $cart_item['key'] ];
                            $quantity = $discounts['cart_item_quantities'][ $bogo->ID ][ $cart_item['key'] ];
                            $discount_amount = ( $total / $quantity );
                            return $discount_amount;
                        }
                    }
                }
            }
        }

        return $discount_amount;
    }

    function woocommerce_get_shop_coupon_data( $data, $code ) {
        if ( empty( $code ) || empty( WC()->cart ) ) {
            return $data;
        }

        foreach ( $this->get_active_bogos( true ) as $bogo ) {
            if ( $this->is_bogo_coupon( $code, $bogo ) ) {

                if ( !isset( $discounts ) ) {
                    $discounts = $this->get_discounts( WC()->cart );
                }

                $free_shipping = ( $bogo->free_shipping == 'yes' );

                $amount = isset( $discounts['totals'][ $bogo->ID ] ) ? $discounts['totals'][ $bogo->ID ] : 0;
                $discount_type = 'pw_bogo';

                if ( PW_BOGO_FIXED_CART_COUPON === true ) {
                    $discount_type = 'fixed_cart';
                }

                // Creates a virtual coupon
                $data = array(
                    'id' => -1,
                    'code' => $code,
                    'description' => $bogo->post_title,
                    'amount' => $amount,
                    'coupon_amount' => $amount,
                    'free_shipping' => $free_shipping,
                    'discount_type' => $discount_type
                );
                break;
            }
        }

        return $data;
    }

    function maybe_apply_bogo_coupon() {
        if ( false === $this->use_coupons ) {
            return;
        }

        $cart = WC()->cart;

        $discounts = $this->get_discounts( $cart );
        $active_bogos = $this->get_active_bogos();

        // Delete any invalid coupons.
        foreach ( $cart->get_applied_coupons() as $coupon_code ) {
            foreach ( $active_bogos as $active_bogo ) {
                if ( $this->is_bogo_coupon( $coupon_code, $active_bogo ) ) {
                    if ( !isset( $discounts['totals'][ $active_bogo->ID ] ) ) {
                        if ( empty( $active_bogo->coupon_code ) || strtolower( trim( $active_bogo->coupon_code ) ) != strtolower( trim( $coupon_code ) ) ) {
                            $cart->remove_coupon( $coupon_code );
                        }
                    }
                }
            }
        }

        asort( $discounts['totals'] );
        foreach ( $discounts['totals'] as $bogo_id => $bogo_discount ) {
            $bogo = $this->get_bogo( $bogo_id );
            $coupon_exists = false;

            // Use the existing bogo coupon if it exists.
            foreach ( $cart->get_applied_coupons() as $coupon_code ) {
                if ( $this->is_bogo_coupon( $coupon_code, $bogo ) ) {
                    $coupon_exists = true;
                    break;
                }
            }

            // If it doesn't exist, we need to create one for the BOGO.
            if ( !$coupon_exists ) {
                $bogo_coupon_code = $this->get_bogo_coupon_code( $bogo );
                $cart->add_discount( $bogo_coupon_code );
                WC()->session->set( 'refresh_totals', true );
            }
        }
    }

    function woocommerce_coupon_message( $msg, $msg_code, $coupon ) {
        if ( $msg_code == WC_Coupon::WC_COUPON_SUCCESS ) {
            if ( PW_BOGO::wc_min_version( '3.0' ) ) {
                $coupon_code = $coupon->get_code();
            } else {
                $coupon_code = $coupon->code;
            }

            foreach ( $this->get_active_bogos() as $bogo ) {
                if ( $this->is_bogo_coupon( $coupon_code, $bogo ) ) {
                    if ( function_exists( 'mb_strtolower' ) ) {
                        $msg = $bogo->post_title . ' ' . mb_strtolower( $msg, 'UTF-8' );
                    } else {
                        $msg = $bogo->post_title . ' ' . strtolower( $msg );
                    }
                }
            }
        }

        return $msg;
    }

    function woocommerce_cart_totals_coupon_label( $label, $coupon ) {
        if ( PW_BOGO::wc_min_version( '3.0' ) ) {
            if ( $this->is_bogo_coupon( $coupon->get_code() ) ) {
                $label = sprintf( __( 'Coupon: %s', 'woocommerce' ), $coupon->get_description() );
            }
        } else {
            foreach ( $this->get_active_bogos() as $bogo ) {
                if ( $this->is_bogo_coupon( $coupon->code, $bogo ) ) {
                    $label = sprintf( __( 'Coupon: %s', 'woocommerce' ), $bogo->post_title );
                    break;
                }
            }
        }

        return $label;
    }

    function woocommerce_new_order_item( $order_item_id, $item, $order_id ) {
        if ( is_a( $item, 'WC_Order_Item_Coupon' ) ) {
            if ( PW_BOGO::wc_min_version( '3.0' ) ) {
                $coupon_code = $item->get_code();
            } else {
                $coupon_code = $item->code;
            }

            $this->maybe_add_coupon_to_order_item( $order_item_id, $coupon_code );
        }
    }

    function woocommerce_order_add_coupon( $order_id, $order_item_id, $code, $discount_amount, $discount_amount_tax ) {
        $this->maybe_add_coupon_to_order_item( $order_item_id, $code );
    }

    function maybe_add_coupon_to_order_item( $order_item_id, $code ) {
        foreach ( $this->get_active_bogos() as $bogo ) {
            if ( $this->is_bogo_coupon( $code, $bogo ) ) {
                wc_add_order_item_meta( $order_item_id, 'pw_bogo_id', $bogo->ID );
                break;
            }
        }
    }

    function flatten_cart( $cart, $product_id = 0, $variation_id = 0 ) {
        // Expand the list of cart items, one element per quantity.
        $cart_items = array();
        foreach ( $cart->get_cart() as $cart_item_index => $cart_item ) {
            if ( !empty( $variation_id ) && $cart_item['variation_id'] != $variation_id ) {
                continue;
            }

            if ( !empty( $product_id ) && $cart_item['product_id'] != $product_id ) {
                continue;
            }

            if ( empty( $cart_item['data'] ) || !is_a( $cart_item['data'], 'WC_Product' ) ) {
                continue;
            }

            for ( $i = 0; $i < $cart_item['quantity']; $i++ ) {
                $price = $cart_item['data']->get_price();

                $cart_items[] = array(
                    'key'       => $cart_item_index . '_' . $i,
                    'cart_item' => $cart_item,
                    'price'     => $price
                );
            }
        }

        return apply_filters( 'pw_bogo_cart_items', $cart_items );
    }

    function get_spent_amount( $bogo, $cart ) {
        if ( $this->wc_min_version( '3.2' ) ) {
            $buy_limit = !empty( $bogo->buy_limit ) ? $bogo->buy_limit : 1;
            $spend_aftertax = ( 'yes' === $bogo->spend_aftertax );

            $spent_amount = 0;
            foreach ( $cart->get_cart() as $cart_item_index => $cart_item ) {
                if ( $this->is_cart_item_valid_for_bogo( $cart_item, $bogo ) && isset( $cart_item['line_subtotal' ] ) ) {
                    if ( true === $spend_aftertax ) {
                        $spent_amount += $cart_item['line_subtotal'] + $cart_item['line_subtotal_tax'];
                    } else {
                        $spent_amount += $cart_item['line_subtotal'];
                    }
                }
            }
        } else {
            $spent_amount = $cart->cart_contents_total;
        }

        return apply_filters( 'pw_bogo_get_spent_amount', $spent_amount, $bogo, $cart );
    }
}

global $pw_bogo;
$pw_bogo = new PW_BOGO();

endif;

if ( !function_exists( 'boolval' ) ) {
    function boolval( $val ) {
        return (bool) $val;
    }
}

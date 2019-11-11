<?php
/**
 * The template for displaying product listing
 *
 * Override this template by copying it to yourtheme/woocommerce/wwof-product-listing-filter.php
 *
 * @author 		Rymera Web Co
 * @package 	WooCommerceWholeSaleOrderForm/Templates
 * @version     1.6.6
 */

if ( !defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// NOTE: Don't Remove any ID or Classes inside this template when overriding it.
// Some JS Files Depend on it. You are free to add ID and Classes without any problem.

if ( $wwof_permissions->wwof_user_has_access() ) {

    do_action( 'wwof_action_before_product_listing_filter' ); ?>

    <div id="wwof_product_listing_filter">
        <input type="text" id="wwof_product_search_form" class="filter_field" placeholder="<?php echo $search_placeholder_text; ?>"/>

        <select id="wwof_product_search_category_filter">

            <option value=""><?php echo apply_filters( 'wwof_filter_listing_no_category_filter_text' , __( '-- No Category Filter --' , 'woocommerce-wholesale-order-form' ) ); ?></option>
            <?php echo $product_category_options; ?>

        </select>

        <input type="button" id="wwof_product_search_btn" class="button button-primary" value="<?php echo apply_filters( 'wwof_filter_listing_search_text' , __( 'Search' , 'woocommerce-wholesale-order-form' ) ); ?>"/>
        <input type="button" id="wwof_product_displayall_btn" class="button button-secondary" value="<?php echo apply_filters( 'wwof_filter_listing_show_all_products_text' , __( 'Show All Products' , 'woocommerce-wholesale-order-form' ) ); ?>"/>
    </div><!--#wwof_product_listing_filter--><?php

    do_action( 'wwof_action_after_product_listing_filter' );

}

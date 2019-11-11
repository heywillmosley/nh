<?php
if ( !defined( 'WP_UNINSTALL_PLUGIN' ) ) exit();

require_once ( 'woocommerce-wholesale-order-form.options.php' );

delete_option( WWOF_SETTINGS_WHOLESALE_PAGE_ID );

// General section settings
delete_option( 'wwof_general_products_per_page' );
delete_option( 'wwof_general_show_product_sku' );
delete_option( 'wwof_general_allow_product_sku_search' );
delete_option( 'wwof_general_show_product_thumbnail' );
delete_option( 'wwof_general_display_product_details_on_popup' );
delete_option( 'wwof_general_display_zero_products' );
delete_option( 'wwof_general_sort_by' );
delete_option( 'wwof_general_sort_order' );

// Filters section settings
delete_option( 'wwof_filters_product_category_filter' );
delete_option( 'wwof_filters_exclude_product_filter' );

// Permissions section settings
delete_option( 'wwof_permissions_user_role_filter' );
delete_option( 'wwof_permissions_noaccess_section_title' );
delete_option( 'wwof_permissions_noaccess_message' );
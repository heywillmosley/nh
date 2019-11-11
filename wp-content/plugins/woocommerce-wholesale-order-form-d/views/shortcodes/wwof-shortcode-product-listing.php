<?php if (!defined('ABSPATH')) exit; // Exit if accessed directly ?>

<div id="wwof_product_listing_container" data-categories="<?php echo $atts[ 'categories' ]; ?>" data-products="<?php echo $atts[ 'products' ]; ?>"><?php

    if ( $atts[ 'show_search' ] ) {

        $allowSKUSearch = get_option( 'wwof_general_allow_product_sku_search' );

        if( $allowSKUSearch !== false && $allowSKUSearch == 'yes' )
            $search_placeholder_text = __( 'Search by name or SKU ...' , 'woocommerce-wholesale-order-form' );
        else
            $search_placeholder_text = __( 'Search by name' , 'woocommerce-wholesale-order-form' );

        $this->_wwof_product_listings->wwof_get_product_listing_filter( apply_filters( 'wwof_filter_search_placeholder_text' , $search_placeholder_text , $allowSKUSearch ) , $atts );

    } ?>

    <div id="wwof_product_listing_ajax_content" style="position: relative;">
        <!--AJAX Content Goes Here -->
    </div><!--#wwof_product_listing_ajax_content-->

</div><!--#wwof_product_listing_container-->
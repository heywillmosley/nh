<?php
/**
 * The template for displaying product listing
 *
 * Override this template by copying it to yourtheme/woocommerce/wwof-product-listing.php
 *
 * @author 		Rymera Web Co
 * @package 	WooCommerceWholeSaleOrderForm/Templates
 * @version     1.8.1
 */

if ( !defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// NOTE: Don't Remove any ID or Classes inside this template when overriding it.
// Some JS Files Depend on it. You are free to add ID and Classes without any problem.

$labels = array(
    'product'         =>  __( 'Product' , 'woocommerce-wholesale-order-form' ),
    'sku'             =>  __( 'SKU' , 'woocommerce-wholesale-order-form' ),
    'price'           =>  __( 'Price' , 'woocommerce-wholesale-order-form' ),
    'stock_quantity'  =>  __( 'In Stock' , 'woocommerce-wholesale-order-form' ),
    'quantity'        =>  __( 'Quantity' , 'woocommerce-wholesale-order-form' )
);
?>
<div id="wwof_product_listing_table_container" style="position: relative;">
    <table id="wwof_product_listing_table">
        <thead>
            <tr>
                <th><?php echo $labels[ 'product' ]; ?></th>
                <th class="<?php echo $product_listing->wwof_get_product_sku_visibility_class(); ?>"><?php echo $labels[ 'sku' ]; ?></th>
                <th><?php echo $labels[ 'price' ]; ?></th>
                <th class="<?php echo $product_listing->wwof_get_product_stock_quantity_visibility_class(); ?>"><?php echo $labels[ 'stock_quantity' ]; ?></th>
                <th><?php echo $labels[ 'quantity' ]; ?></th>
                <th></th>
            </tr>
        </thead>
        <tbody><?php

        $_REQUEST[ 'tab_index_counter' ] = 1;
        $thumbnail_size = $product_listing->wwof_get_product_thumbnail_dimension();

        if ( $product_loop->have_posts() ) {

            // Ensure that available variations always show their price HTML even if the prices are the same
            add_filter( 'woocommerce_show_variation_price', function() { return true; } );

            while ( $product_loop->have_posts() ) { $product_loop->the_post();

                global $product;

                $post_id = get_the_ID();
                $product = wc_get_product( $post_id );

                if ( WWOF_Functions::wwof_get_product_type( $product ) == 'variable' ) {

                    $available_variations = WWOF_Product_Listing_Helper::wwof_get_available_variations( $product );

                    if ( class_exists( 'WWP_Wholesale_Prices' ) ) {

                        global $wc_wholesale_prices;

                        $wholesale_role = $wc_wholesale_prices->wwp_wholesale_roles->getUserWholesaleRole();

                        // get wholesale price for all variations
                        WWOF_Product_Listing_Helper::wwof_get_variations_wholesale_price( $available_variations , $wholesale_role );

                    }

                    // update available variations input arguments
                    WWOF_Product_Listing_Helper::wwof_update_variations_input_args( $available_variations );

                }

                if( WWOF_Functions::wwof_get_product_type( $product ) == 'grouped' )
                    continue;
                else{ ?>

                    <tr>
                        <td class="product_meta_col" style="display: none !important;" data-product_variations="<?php if ( isset( $available_variations ) ) echo htmlspecialchars( wp_json_encode( $available_variations ) ); ?>">
                            <?php echo $product_listing->wwof_get_product_meta( $product ); ?>
                        </td>
                        <td class="product_title_col">
                            <span class="mobile-label"><?php echo $labels[ 'product' ]; ?></span>
                            <?php echo $product_listing->wwof_get_product_image( $product , get_the_permalink( $post_id ) , $thumbnail_size ); ?>
                            <?php echo $product_listing->wwof_get_product_title( $product , get_the_permalink( $post_id ) ); ?>
                            <br />
                            <?php echo $product_listing->wwof_get_product_variation_field( $product ); ?>
                            <?php echo $product_listing->wwof_get_product_variation_selected_options( $product ); ?>
                            <?php echo $product_listing->wwof_get_product_addons( $product ); ?>
                        </td>
                        <td class="product_sku_col <?php echo $product_listing->wwof_get_product_sku_visibility_class(); ?>">
                            <span class="mobile-label"><?php echo $labels[ 'sku' ]; ?></span>
                            <?php echo $product_listing->wwof_get_product_sku( $product ); ?>
                        </td>
                        <td class="product_price_col">
                            <span class="mobile-label"><?php echo $labels[ 'price' ]; ?></span>
                            <?php echo $wholesale_prices->wwof_get_product_price( $product ); ?>
                        </td>
                        <td class="product_stock_quantity_col <?php echo $product_listing->wwof_get_product_stock_quantity_visibility_class(); ?>">
                            <span class="mobile-label"><?php echo $labels[ 'stock_quantity' ]; ?></span>
                            <?php echo $product_listing->wwof_get_product_stock_quantity( $product ); ?>
                        </td>
                        <td class="product_quantity_col">
                            <span class="mobile-label"><?php echo $labels[ 'quantity' ]; ?></span>
                            <?php echo $wholesale_prices->wwof_get_product_quantity_field( $product ); ?>
                        </td>
                        <td class="product_row_action">
                            <?php echo $product_listing->wwof_get_product_row_action_fields( $product ); ?>
                        </td>
                    </tr><?php

                }

                $_REQUEST[ 'tab_index_counter' ] += 1;

            }// End while loop

        } else { ?>

            <tr class="no-products">
                <td colspan="4">
                    <span><?php _e( 'No Products Found' , 'woocommerce-wholesale-order-form' ); ?></span>
                </td>
            </tr> <?php

        } ?>
        </tbody>
        <tfoot>
            <tr>
                <th><?php echo $labels[ 'product' ]; ?></th>
                <th class="<?php echo $product_listing->wwof_get_product_sku_visibility_class(); ?>"><?php echo $labels[ 'sku' ]; ?></th>
                <th><?php echo $labels[ 'price' ]; ?></th>
                <th class="<?php echo $product_listing->wwof_get_product_stock_quantity_visibility_class(); ?>"><?php echo $labels[ 'stock_quantity' ]; ?></th>
                <th><?php echo $labels[ 'quantity' ]; ?></th>
                <th></th>
            </tr>
        </tfoot>
    </table><!--#wwof_product_listing_table-->
</div><!--#wwof_product_listing_table_container-->

<?php echo $product_listing->wwof_get_cart_subtotal(); ?>

<div id="wwof_product_listing_pagination" data-max-pages="<?php echo $product_loop->max_num_pages; ?>">

    <div class="total_products_container">
        <span class="total_products"><?php
            echo sprintf( __( '%1$s Product/s Found' , 'woocommerce-wholesale-order-form' ) , $product_loop->found_posts ); ?>
        </span>
    </div>

    <?php echo $product_listing->wwof_get_gallery_listing_pagination( $paged , $product_loop->max_num_pages , $search , $cat_filter ); ?>

</div><!--#wwof_product_listing_pagination-->

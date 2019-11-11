<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit ; // Exit if accessed directly
}
?>
<tr class="item <?php echo apply_filters( 'woocommerce_admin_html_order_item_class' , ( ! empty( $class ) ? $class : '' ) , $item ) ; ?>" data-order_item_id="<?php echo $item_id ; ?>">
    <td class="thumb">
        <?php if ( $_product ) : ?>
            <a href="<?php echo esc_url( admin_url( 'post.php?post=' . absint( _sumo_pp_get_product_id( $_product ) ) . '&action=edit' ) ) ; ?>" class="tips" data-tip="<?php
            echo '<strong>' . __( 'Product ID:' , _sumo_pp()->text_domain ) . '</strong> ' . absint( $item[ 'product_id' ] ) ;

            if ( ! empty( $item[ 'variation_id' ] ) && 'product_variation' === get_post_type( $item[ 'variation_id' ] ) ) {
                echo '<br/><strong>' . __( 'Variation ID:' , _sumo_pp()->text_domain ) . '</strong> ' . absint( $item[ 'variation_id' ] ) ;
            } elseif ( ! empty( $item[ 'variation_id' ] ) ) {
                echo '<br/><strong>' . __( 'Variation ID:' , _sumo_pp()->text_domain ) . '</strong> ' . absint( $item[ 'variation_id' ] ) . ' (' . __( 'No longer exists' , _sumo_pp()->text_domain ) . ')' ;
            }

            if ( $_product && $_product->get_sku() ) {
                echo '<br/><strong>' . __( 'Product SKU:' , _sumo_pp()->text_domain ) . '</strong> ' . esc_html( $_product->get_sku() ) ;
            }

            if ( $_product && 'variation' === _sumo_pp_get_product_type( $_product ) ) {
                echo '<br/>' . wc_get_formatted_variation( $_product->get_variation_attributes() , true ) ;
            }
            ?>">
                   <?php echo $_product->get_image( array ( 40 , 40 ) , array ( 'title' => '' ) ) ; ?>
            </a>
        <?php else : ?>
            <?php echo wc_placeholder_img( 'shop_thumbnail' ) ; ?>
        <?php endif ; ?>
    </td>
    <td class="name" data-sort-value="<?php echo esc_attr( $item[ 'name' ] ) ; ?>">

        <?php echo ( $_product && $_product->get_sku() ) ? esc_html( $_product->get_sku() ) . ' &ndash; ' : '' ; ?>

        <?php if ( $_product ) : ?>
            <a target="_blank" href="<?php echo esc_url( admin_url( 'post.php?post=' . absint( _sumo_pp_get_product_id( $_product , true ) ) . '&action=edit' ) ) ; ?>">
                <?php echo esc_html( $item[ 'name' ] ) ; ?>
            </a>
        <?php else : ?>
            <?php echo esc_html( $item[ 'name' ] ) ; ?>
        <?php endif ; ?>
        <input type="hidden" class="order_item_id" name="order_item_id[]" value="<?php echo esc_attr( $item_id ) ; ?>" />
        <input type="hidden" name="order_item_tax_class[<?php echo absint( $item_id ) ; ?>]" value="<?php echo isset( $item[ 'tax_class' ] ) ? esc_attr( $item[ 'tax_class' ] ) : '' ; ?>" />
        <div class="view">
            <?php
            echo $order->get_item_metadata( array (
                'order_item_id' => $item_id ,
                'order_item'    => $item ,
                'product'       => $_product ,
                'to_display'    => true
            ) ) ;
            ?>
        </div>
    </td>

    <td class="item_cost" width="1%" data-sort-value="<?php echo esc_attr( $order->order->get_item_subtotal( $item , false , true ) ) ; ?>">
        <div class="view">
            <?php
            if ( isset( $item[ 'line_total' ] ) ) {
                if ( isset( $item[ 'line_subtotal' ] ) && round( ( float ) $item[ 'line_subtotal' ] , 2 ) != round( ( float ) $item[ 'line_total' ] , 2 ) ) {
                    echo '<del>' . wc_price( $order->order->get_item_subtotal( $item , false , true ) , array ( 'currency' => $order->get_currency() ) ) . '</del> ' ;
                }

                echo '<b>' . wc_price( $order->order->get_item_total( $item , false , true ) , array ( 'currency' => $order->get_currency() ) ) . '</b>' ;
            }
            ?>
        </div>
    </td>

    <td class="quantity" width="1%">
        <div class="view">
            <?php
            echo '<small class="times">&times;</small> ' . ( isset( $item[ 'qty' ] ) ? esc_html( $item[ 'qty' ] ) : '1' ) ;
            ?>
        </div>
    </td>

    <td class="line_cost" width="1%" data-sort-value="<?php echo esc_attr( isset( $item[ 'line_total' ] ) ? $item[ 'line_total' ] : ''  ) ; ?>">
        <div class="view">
            <?php
            if ( isset( $item[ 'line_total' ] ) ) {
                echo '<b>' . wc_price( $item[ 'line_total' ] , array ( 'currency' => $order->get_currency() ) ) . '</b>' ;
            }
            if ( isset( $item[ 'line_subtotal' ] ) && round( ( float ) $item[ 'line_subtotal' ] , 2 ) != round( ( float ) $item[ 'line_total' ] , 2 ) ) {
                echo '<br><span class="wc-order-item-discount">-' . wc_price( wc_format_decimal( $item[ 'line_subtotal' ] - $item[ 'line_total' ] , '' ) , array ( 'currency' => $order->get_currency() ) ) . '</span>' ;
            }
            ?>
        </div>
    </td>

    <?php
    if ( empty( $legacy_order ) && wc_tax_enabled() ) :
        $line_tax_data = isset( $item[ 'line_tax_data' ] ) ? $item[ 'line_tax_data' ] : '' ;
        $tax_data      = maybe_unserialize( $line_tax_data ) ;

        foreach ( $order_taxes as $tax_item ) :
            $tax_item_id        = $tax_item[ 'rate_id' ] ;
            $shipping_tax_total = $calculate_shipping && isset( $tax_item[ 'shipping_tax_total' ] ) ? $tax_item[ 'shipping_tax_total' ] : 0 ;
            $tax_item_total     = isset( $tax_data[ 'total' ][ $tax_item_id ] ) ? $tax_data[ 'total' ][ $tax_item_id ] : 0 ;
            $tax_item_subtotal  = isset( $tax_data[ 'subtotal' ][ $tax_item_id ] ) ? $tax_data[ 'subtotal' ][ $tax_item_id ] : 0 ;
            ?>
            <td class="line_tax" width="1%">
                <div class="view">
                    <?php
                    if ( $tax_item_total ) {
                        if ( isset( $tax_item_subtotal ) && round( ( float ) $tax_item_subtotal , 2 ) != round( ( float ) $tax_item_total , 2 ) ) {
                            echo '<del>' . wc_price( wc_round_tax_total( $tax_item_subtotal ) , array ( 'currency' => $order->get_currency() ) ) . '</del> ' ;
                        }

                        $tax_total += $tax_item_total + $shipping_tax_total ;
                        echo '<b>' . wc_price( wc_round_tax_total( $tax_item_total ) , array ( 'currency' => $order->get_currency() ) ) . '</b>' ;
                    } else {
                        echo '&ndash;' ;
                    }
                    ?>
                </div>
            </td>
            <?php
            $calculate_shipping = false ;
        endforeach ;
    endif ;
    ?>
</tr>

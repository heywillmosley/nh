<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit ; // Exit if accessed directly
}

global $wpdb ;

// Get the payment gateway
$payment_gateway = wc_get_payment_gateway_by_order( $order->order ) ;

// Get line items
$line_items          = $order->order->get_items( 'line_item' ) ;
$line_items_fee      = $order->order->get_items( 'fee' ) ;
$line_items_shipping = $order->order->get_items( 'shipping' ) ;

if ( wc_tax_enabled() ) {
    $order_taxes           = $order->order->get_taxes() ;
    $tax_classes           = WC_Tax::get_tax_classes() ;
    $classes_options       = array () ;
    $classes_options[ '' ] = __( 'Standard' , _sumo_pp()->text_domain ) ;

    if ( ! empty( $tax_classes ) ) {
        foreach ( $tax_classes as $class ) {
            $classes_options[ sanitize_title( $class ) ] = $class ;
        }
    }

    // Older orders won't have line taxes so we need to handle them differently :(
    $tax_data = '' ;
    if ( $line_items ) {
        $check_item = current( $line_items ) ;
        $tax_data   = maybe_unserialize( isset( $check_item[ 'line_tax_data' ] ) ? $check_item[ 'line_tax_data' ] : ''  ) ;
    } elseif ( $line_items_shipping ) {
        $check_item = current( $line_items_shipping ) ;
        $tax_data   = maybe_unserialize( isset( $check_item[ 'taxes' ] ) ? $check_item[ 'taxes' ] : ''  ) ;
    } elseif ( $line_items_fee ) {
        $check_item = current( $line_items_fee ) ;
        $tax_data   = maybe_unserialize( isset( $check_item[ 'line_tax_data' ] ) ? $check_item[ 'line_tax_data' ] : ''  ) ;
    }

    $legacy_order     = ! empty( $order_taxes ) && empty( $tax_data ) && ! is_array( $tax_data ) ;
    $show_tax_columns = ! $legacy_order || sizeof( $order_taxes ) === 1 ;
}
?>
<div class="woocommerce_order_items_wrapper">
    <table cellpadding="0" cellspacing="10" class="woocommerce_order_items">
        <thead>
            <tr>
                <th class="item sortable" colspan="2" data-sort="string-ins"><?php _e( 'Item(s)' , _sumo_pp()->text_domain ) ; ?></th>
                <th class="item_cost sortable payment_item" data-sort="float"><?php _e( 'Cost' , _sumo_pp()->text_domain ) ; ?></th>
                <th class="quantity sortable payment_item" data-sort="int"><?php _e( 'Qty' , _sumo_pp()->text_domain ) ; ?></th>
                <th class="line_cost sortable payment_item" data-sort="float"><?php _e( 'Total' , _sumo_pp()->text_domain ) ; ?></th>

                <?php
                if ( empty( $legacy_order ) && ! empty( $order_taxes ) ) :
                    foreach ( $order_taxes as $tax_id => $tax_item ) :
                        $tax_class      = wc_get_tax_class_by_tax_id( $tax_item[ 'rate_id' ] ) ;
                        $tax_class_name = isset( $classes_options[ $tax_class ] ) ? $classes_options[ $tax_class ] : __( 'Tax' , _sumo_pp()->text_domain ) ;
                        $column_label   = ! empty( $tax_item[ 'label' ] ) ? $tax_item[ 'label' ] : __( 'Tax' , _sumo_pp()->text_domain ) ;
                        $column_tip     = $tax_item[ 'name' ] . ' (' . $tax_class_name . ')' ;
                        ?>
                        <th class="line_tax tips payment_item" data-tip="<?php echo esc_attr( $column_tip ) ; ?>">
                            <?php echo esc_attr( $column_label ) ; ?>
                            <input type="hidden" class="order-tax-id" name="order_taxes[<?php echo $tax_id ; ?>]" value="<?php echo esc_attr( $tax_item[ 'rate_id' ] ) ; ?>">
                            <a class="delete-order-tax" href="#" data-rate_id="<?php echo $tax_id ; ?>"></a>
                        </th>
                        <?php
                    endforeach ;
                endif ;
                ?>
            </tr>
        </thead>
        <tbody id="order_line_items">
            <?php
            $item_total         = 0 ;
            $tax_total          = 0 ;
            $calculate_shipping = true ;

            foreach ( $line_items as $item_id => $item ) {
                if ( ! isset( $item[ 'product_id' ] ) ) {
                    continue ;
                }

                $product_id = $item[ 'variation_id' ] > 0 ? $item[ 'variation_id' ] : $item[ 'product_id' ] ;

                if ( $payment_product_id == $product_id || 'order' === $payment_product_type ) {
                    $_product = $order->order->get_product_from_item( $item ) ;
                    $item_total += $item[ 'line_total' ] ;

                    include( 'html-order-item.php' ) ;
                }
            }
            ?>
        </tbody>
        <tbody id="order_shipping_line_items">
            <?php
            $shipping_methods = WC()->shipping() ? WC()->shipping->load_shipping_methods() : array () ;
            foreach ( $line_items_shipping as $item_id => $item ) {
                include( 'html-order-shipping.php' ) ;
            }
            ?>
        </tbody>
        <tbody id="order_fee_line_items">
            <?php
            foreach ( $line_items_fee as $item_id => $item ) {
                include( 'html-order-fee.php' ) ;
            }
            ?>
        </tbody>
    </table>
</div>
<div class="wc-order-data-row wc-order-totals-items wc-order-items-editable">
    <?php
    $coupons = $order->order->get_items( array ( 'coupon' ) ) ;
    if ( $coupons ) {
        ?>
        <div class="wc-used-coupons">
            <ul class="wc_coupon_list">
                <?php
                echo '<li><strong>' . __( 'Coupon(s) Used' , _sumo_pp()->text_domain ) . '</strong></li>' ;

                foreach ( $coupons as $item_id => $item ) {
                    $coupon_post_id = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE post_title = %s AND post_type = 'shop_coupon' AND post_status = 'publish' LIMIT 1;" , $item[ 'name' ] ) ) ;

                    $link = $coupon_post_id ? esc_url_raw( add_query_arg( array ( 'post' => $coupon_post_id , 'action' => 'edit' ) , admin_url( 'post.php' ) ) ) : esc_url_raw( add_query_arg( array ( 's' => $item[ 'name' ] , 'post_status' => 'all' , 'post_type' => 'shop_coupon' ) , admin_url( 'edit.php' ) ) ) ;

                    echo '<li class="code"><a href="' . esc_url( $link ) . '" class="tips" data-tip="' . esc_attr( wc_price( $item[ 'discount_amount' ] , array ( 'currency' => $order->get_currency() ) ) ) . '"><span>' . esc_html( $item[ 'name' ] ) . '</span></a></li>' ;
                }
                ?>
            </ul>
        </div>
        <?php
    }
    ?>
    <table class="wc-order-totals">
        <tr>
            <td class="label"><?php _e( 'Discount' , _sumo_pp()->text_domain ) ; ?> <span class="tips" data-tip="<?php esc_attr_e( 'This is the total discount. Discounts are defined per line item.' , _sumo_pp()->text_domain ) ; ?>">[?]</span>:</td>
            <td width="1%"></td>
            <td class="total">
                <?php
                $discount_total = 0 ;
                foreach ( $line_items as $item_id => $item ) {
                    $product_id = $item[ 'variation_id' ] > 0 ? $item[ 'variation_id' ] : $item[ 'product_id' ] ;

                    if ( $payment_product_id == $product_id ) {
                        $discount_total = $item[ 'line_subtotal' ] - $item[ 'line_total' ] ;
                    }
                }
                echo '<b>' . wc_price( $discount_total , array ( 'currency' => $order->get_currency() ) ) . '</b>' ;
                ?>
            </td>
        </tr>
        <tr>
            <td class="label"><?php _e( 'Shipping' , _sumo_pp()->text_domain ) ; ?> <span class="tips" data-tip="<?php esc_attr_e( 'This is the shipping and handling total costs for the order.' , _sumo_pp()->text_domain ) ; ?>">[?]</span>:</td>
            <td width="1%"></td>
            <td class="total">
                <?php
                $shipping_total = $order->order->get_total_shipping() ;
                echo '<b>' . wc_price( $shipping_total , array ( 'currency' => $order->get_currency() ) ) . '</b>' ;
                ?>
            </td>
        </tr>
        <?php
        if ( wc_tax_enabled() ) :
            foreach ( $order->order->get_tax_totals() as $code => $tax ) :
                ?>
                <tr>
                    <td class="label"><?php echo $tax->label ; ?>:</td>
                    <td width="1%"></td>
                    <td class="total" style="font-weight: bold">
                        <?php
                        echo '<b>' . wc_price( $tax_total , array ( 'currency' => $order->get_currency() ) ) . '</b>' ;
                        ?>
                    </td>
                </tr>
                <?php
            endforeach ;
        endif ;
        ?>
        <tr>
            <td class="label"><?php _e( 'Order Total' , _sumo_pp()->text_domain ) ; ?>:</td>
            <td width="1%"></td>
            <td class="total">
                <div class="view">
                    <?php
                    echo '<b>' . wc_price( $order->order->get_total() , array ( 'currency' => $order->get_currency() ) ) . '</b>' ;
                    ?>
                </div>
            </td>
        </tr>
    </table>
    <div class="clear"></div>
</div>


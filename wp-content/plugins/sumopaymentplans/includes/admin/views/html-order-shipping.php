<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit ; // Exit if accessed directly
}
?>
<tr class="shipping <?php echo ( ! empty( $class ) ) ? $class : '' ; ?>" data-order_item_id="<?php echo $item_id ; ?>">

    <td class="thumb"><div></div></td>

    <td class="name">
        <div class="view">
            <?php echo ! empty( $item[ 'name' ] ) ? esc_html( $item[ 'name' ] ) : __( 'Shipping' , _sumo_pp()->text_domain ) ; ?>
        </div>
    </td>

    <td class="item_cost" width="1%">&nbsp;</td>
    <td class="quantity" width="1%">&nbsp;</td>

    <td class="line_cost" width="1%">
        <div class="view">
            <?php
            echo ( isset( $item[ 'cost' ] ) ) ? '<b>' . wc_price( wc_round_tax_total( $item[ 'cost' ] ) , array ( 'currency' => $order->get_currency() ) ) . '</b>' : '' ;
            ?>
        </div>
    </td>

    <?php
    if ( empty( $legacy_order ) && wc_tax_enabled() ) :
        $shipping_taxes = isset( $item[ 'taxes' ] ) ? $item[ 'taxes' ] : '' ;
        $tax_data       = maybe_unserialize( $shipping_taxes ) ;

        foreach ( $order_taxes as $tax_item ) :
            $tax_item_id    = $tax_item[ 'rate_id' ] ;
            $tax_item_total = isset( $tax_item[ 'shipping_tax_total' ] ) ? $tax_item[ 'shipping_tax_total' ] : (isset( $tax_data[ $tax_item_id ] ) ? $tax_data[ $tax_item_id ] : '') ;
            ?>
            <td class="line_tax" width="1%">
                <div class="view">
                    <?php
                    echo ( '' != $tax_item_total ) ? '<b>' . wc_price( wc_round_tax_total( $tax_item_total ) , array ( 'currency' => $order->get_currency() ) ) . '</b>' : '&ndash;' ;
                    ?>
                </div>
            </td>
            <?php
        endforeach ;
    endif ;
    ?>
</tr>

<?php
 if ( ! defined( 'ABSPATH' ) ) {
     exit ;
 }
?>
<tr class="fee <?php echo ( ! empty( $class ) ) ? $class : '' ; ?>" data-order_item_id="<?php echo $item_id ; ?>">

    <td class="thumb"><div></div></td>

    <td class="name">
        <div class="view">
            <?php echo ! empty( $item[ 'name' ] ) ? esc_html( $item[ 'name' ] ) : __( 'Fee' , _sumo_pp()->text_domain ) ; ?>
        </div>
    </td>

    <td class="item_cost" width="1%">&nbsp;</td>
    <td class="quantity" width="1%">&nbsp;</td>

    <td class="line_cost" width="1%">
        <div class="view">
            <?php
             echo ( isset( $item[ 'line_total' ] ) ) ? '<b>' . wc_price( wc_round_tax_total( $item[ 'line_total' ] ) ) . '</b>' : '' ;
            ?>
        </div>
    </td>

    <?php
     if ( empty( $legacy_order ) && wc_tax_enabled() ) :
         $line_tax_data = isset( $item[ 'line_tax_data' ] ) ? $item[ 'line_tax_data' ] : '' ;
         $tax_data      = maybe_unserialize( $line_tax_data ) ;

         foreach ( $order_taxes as $tax_item ) :
             $tax_item_id    = $tax_item[ 'rate_id' ] ;
             $tax_item_total = isset( $tax_data[ 'total' ][ $tax_item_id ] ) ? $tax_data[ 'total' ][ $tax_item_id ] : '' ;
             ?>
             <td class="line_tax" width="1%">
                 <div class="view">
                     <?php
                     echo ( '' != $tax_item_total ) ? '<b>' . wc_price( wc_round_tax_total( $tax_item_total ) ) . '</b>' : '&ndash;' ;
                     ?>
                 </div>
             </td>
             <?php
         endforeach ;
     endif ;
    ?>
</tr>

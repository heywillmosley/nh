<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit ; // Exit if accessed directly
}
$product_title_with_installment = sprintf( __( 'Installment #%s of %s' , _sumo_pp()->text_domain ) , sizeof( _sumo_pp_get_balance_paid_orders( $payment_id ) ) , _sumo_pp_get_formatted_payment_product_title( $payment_id , array (
    'tips' => false ,
    'qty'  => false ,
        ) ) ) ;
?>

<?php do_action( 'woocommerce_email_header' , $email_heading ) ; ?>

<p><?php printf( __( 'Hi, <br>Your Payment for %s from Payment #%s has been received successfully.' , _sumo_pp()->text_domain ) , $product_title_with_installment , _sumo_pp_get_payment_number( $payment_id ) ) ; ?></p>

<?php do_action( 'woocommerce_email_before_order_table' , $order->order , $sent_to_admin , $plain_text ) ; ?>

<h2><?php printf( __( 'Payment #%s' , _sumo_pp()->text_domain ) , _sumo_pp_get_payment_number( $payment_id ) ) ; ?></h2>

<table class="td" cellspacing="0" cellpadding="6" style="width: 100%; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif;" border="1">
    <thead>
        <tr>
            <th class="td" scope="col" style="text-align:left;"><?php _e( 'Product' , _sumo_pp()->text_domain ) ; ?></th>
            <th class="td" scope="col" style="text-align:left;"><?php _e( 'Quantity' , _sumo_pp()->text_domain ) ; ?></th>
            <th class="td" scope="col" style="text-align:left;"><?php _e( 'Price' , _sumo_pp()->text_domain ) ; ?></th>
        </tr>
    </thead>
    <tbody>
        <?php echo $order->get_email_order_items_table() ; ?>
    </tbody>
    <tfoot>
        <?php echo $order->get_email_order_item_totals() ; ?>
    </tfoot>
</table>

<?php do_action( 'woocommerce_email_footer' ) ; ?>
<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit ; // Exit if accessed directly
}

$payment_due_date               = get_post_meta( $payment_id , '_next_payment_date' , true ) ;
$payment_count                  = sizeof( _sumo_pp_get_balance_paid_orders( $payment_id ) ) + 1 ;
$product_title_with_installment = sprintf( __( 'Installment #%s of %s' , _sumo_pp()->text_domain ) , $payment_count , _sumo_pp_get_formatted_payment_product_title( $payment_id , array (
    'tips' => false ,
    'qty'  => false ,
        ) ) ) ;
?>

<?php do_action( 'woocommerce_email_header' , $email_heading ) ; ?>

<?php if ( $order->has_status( 'pending' ) ) : ?>

    <p><?php printf( __( 'Hi, <br>Your Invoice for %s from payment #%s has been generated. The Payment details are as follows' , _sumo_pp()->text_domain ) , $product_title_with_installment , _sumo_pp_get_payment_number( $payment_id ) ) ; ?></p>

<?php endif ; ?>

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

<p><?php printf( __( 'Please make the payment using the payment link %s on or before <strong>%s</strong>' , _sumo_pp()->text_domain ) , '<a href="' . esc_url( $order->order->get_checkout_payment_url() ) . '">' . __( 'pay' , _sumo_pp()->text_domain ) . '</a>' , _sumo_pp_get_date_to_display( $payment_due_date ) ) ; ?></p>

<?php do_action( 'woocommerce_email_footer' ) ; ?>
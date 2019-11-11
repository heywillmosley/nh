<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit ; // Exit if accessed directly
}

$payment_start_date = get_post_meta( $payment_id , '_payment_start_date' , true ) ;
$product_title      = _sumo_pp_get_formatted_payment_product_title( $payment_id , array (
    'tips' => false ,
    'qty'  => false ,
        ) ) ;
?>

<?php do_action( 'woocommerce_email_header' , $email_heading ) ; ?>

<p><?php printf( __( 'Hi, <br>Your Payment Schedule for Purchase of %s on %s from Payment #%s is as Follows' , _sumo_pp()->text_domain ) , $product_title , _sumo_pp_get_date_to_display( $payment_start_date ) , _sumo_pp_get_payment_number( $payment_id ) ) ; ?></p>

<h2><?php _e( 'Payment Schedule' , _sumo_pp()->text_domain ) ; ?></h2>

<?php
_sumo_pp_get_payment_orders_table( $payment_id , array (
    'class'          => 'td' ,
    'custom_attr'    => 'cellspacing=0 cellpadding=6 border=1' ,
    'css'            => "width: 100%;font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif;" ,
    'th_class'       => 'td' ,
    'th_css'         => 'text-align:left;' ,
    'th_custom_attr' => 'scope=col' ,
    'th_elements'    => array (
        'payments'              => __( 'Payments' , _sumo_pp()->text_domain ) ,
        'installment-amount'    => __( 'Installment Amount' , _sumo_pp()->text_domain ) ,
        'expected-payment-date' => __( 'Expected Payment Date' , _sumo_pp()->text_domain ) ,
    ) ,
    'page'           => 'frontend' ,
) ) ;
?>

<p><?php _e( 'Thanks' , _sumo_pp()->text_domain ) ; ?></p>

<?php do_action( 'woocommerce_email_footer' ) ; ?>
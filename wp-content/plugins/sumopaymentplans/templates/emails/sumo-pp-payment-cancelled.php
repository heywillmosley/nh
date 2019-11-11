<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit ; // Exit if accessed directly
}

$product_title = _sumo_pp_get_formatted_payment_product_title( $payment_id , array (
    'tips' => false ,
    'qty'  => false ,
        ) ) ;
?>

<?php do_action( 'woocommerce_email_header' , $email_heading ) ; ?>

<p><?php printf( __( 'Hi, <br>Your payment #%s for product %s has been cancelled since you have not paid the balance payments within the due date' , _sumo_pp()->text_domain ) , _sumo_pp_get_payment_number( $payment_id ) , $product_title ) ; ?></p>

<h2><?php _e( 'Payment Schedule' , _sumo_pp()->text_domain ) ; ?></h2>

<?php
_sumo_pp_get_payment_orders_table( $payment_id , array (
    'class'          => 'td' ,
    'custom_attr'    => 'cellspacing=0 cellpadding=6 border=1' ,
    'css'            => "width: 100%;font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif;" ,
    'th_class'       => 'td' ,
    'th_css'         => 'text-align:left;' ,
    'th_custom_attr' => 'scope=col' ,
    'page'           => 'frontend' ,
) ) ;
?>

<p><?php _e( 'Thanks' , _sumo_pp()->text_domain ) ; ?></p>

<?php do_action( 'woocommerce_email_footer' ) ; ?>
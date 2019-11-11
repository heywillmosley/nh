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

<p><?php printf( __( 'Hi, <br>The Balance Payment for your purchase of %s from payment #%s has been paid Successfully' , _sumo_pp()->text_domain ) , $product_title , _sumo_pp_get_payment_number( $payment_id ) ) ; ?></p>

<p><?php _e( 'Thanks' , _sumo_pp()->text_domain ) ; ?></p>

<?php do_action( 'woocommerce_email_footer' ) ; ?>
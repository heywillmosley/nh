<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit ; // Exit if accessed directly
}
?>

<?php do_action( 'woocommerce_email_header' , $email_heading ) ; ?>

<p><?php printf( __( 'Hi, <br>You have successfully completed the Payment Schedule for Payment #%s. Your Payment details are as follows.' , _sumo_pp()->text_domain ) , _sumo_pp_get_payment_number( $payment_id ) ) ; ?></p>

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
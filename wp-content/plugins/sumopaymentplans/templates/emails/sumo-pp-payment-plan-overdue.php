<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit ; // Exit if accessed directly
}

$payment_count                  = sizeof( _sumo_pp_get_balance_paid_orders( $payment_id ) ) + 1 ;
$product_title_with_installment = sprintf( __( 'Installment #%s of %s' , _sumo_pp()->text_domain ) , $payment_count , _sumo_pp_get_formatted_payment_product_title( $payment_id , array (
    'tips' => false ,
    'qty'  => false ,
        ) ) ) ;

$scheduled_timestamp = 0 ;
if ( $payment_cron        = _sumo_pp_get_payment_cron( $payment_id ) ) {
    $payment_jobs = $payment_cron->jobs ;

    if ( isset( $payment_jobs[ 'notify_overdue' ] ) && is_array( $payment_jobs[ 'notify_overdue' ] ) && $payment_jobs[ 'notify_overdue' ] ) {
        foreach ( $payment_jobs[ 'notify_overdue' ] as $args ) {
            if ( isset( $args[ 'overdue_date_till' ] ) ) {
                $scheduled_timestamp = $args[ 'overdue_date_till' ] ;
                break ;
            }
        }
    } else if ( isset( $payment_jobs[ 'notify_cancelled' ] ) && is_array( $payment_jobs[ 'notify_cancelled' ] ) && $payment_jobs[ 'notify_cancelled' ] ) {
        $scheduled_timestamp = array_keys( $payment_jobs[ 'notify_cancelled' ] ) ;
        $scheduled_timestamp = isset( $scheduled_timestamp[ 0 ] ) ? $scheduled_timestamp[ 0 ] : 0 ;
    }
}
$overdue_date = _sumo_pp_get_date_to_display( $scheduled_timestamp ) ;
?>

<?php do_action( 'woocommerce_email_header' , $email_heading ) ; ?>

<p><?php printf( __( 'Hi, <br>Your payment for %s from payment #%s is currently Overdue. Please make the payment using the payment link %s before <strong>%s</strong>. If Payment is not received within <strong>%s</strong>, the Payment Plan will be Cancelled.' , _sumo_pp()->text_domain ) , $product_title_with_installment , _sumo_pp_get_payment_number( $payment_id ) , '<a href="' . esc_url( $order->order->get_checkout_payment_url() ) . '">' . __( 'pay' , _sumo_pp()->text_domain ) . '</a>' , $overdue_date , $overdue_date ) ; ?></p>

<p><?php _e( 'Thanks' , _sumo_pp()->text_domain ) ; ?></p>

<?php do_action( 'woocommerce_email_footer' ) ; ?>
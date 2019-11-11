<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit ; // Exit if accessed directly
}

/**
 * Load SUMO Payment Plans email classes.
 * 
 * @param array $emails
 * @return array
 */
function _sumo_pp_load_emails( $emails ) {
    include_once 'abstracts/abstract-sumo-pp-email.php' ;

    $emails[ 'SUMO_PP_Payment_Schedule_Email' ]                  = include( 'emails/class-sumo-pp-payment-schedule-email.php' ) ;
    $emails[ 'SUMO_PP_Payment_Plan_Invoice_Email' ]              = include( 'emails/class-sumo-pp-payment-plan-invoice-email.php' ) ;
    $emails[ 'SUMO_PP_Payment_Plan_Success_Email' ]              = include( 'emails/class-sumo-pp-payment-plan-success-email.php' ) ;
    $emails[ 'SUMO_PP_Payment_Plan_Completed_Email' ]            = include( 'emails/class-sumo-pp-payment-plan-completed-email.php' ) ;
    $emails[ 'SUMO_PP_Payment_Plan_Overdue_Email' ]              = include( 'emails/class-sumo-pp-payment-plan-overdue-email.php' ) ;
    $emails[ 'SUMO_PP_Deposit_Balance_Payment_Invoice_Email' ]   = include( 'emails/class-sumo-pp-deposit-balance-payment-invoice-email.php' ) ;
    $emails[ 'SUMO_PP_Deposit_Balance_Payment_Completed_Email' ] = include( 'emails/class-sumo-pp-deposit-balance-payment-completed-email.php' ) ;
    $emails[ 'SUMO_PP_Deposit_Balance_Payment_Overdue_Email' ]   = include( 'emails/class-sumo-pp-deposit-balance-payment-overdue-email.php' ) ;
    $emails[ 'SUMO_PP_Payment_Cancelled_Email' ]                 = include( 'emails/class-sumo-pp-payment-cancelled-email.php' ) ;

    return $emails ;
}

add_filter( 'woocommerce_email_classes' , '_sumo_pp_load_emails' , 10 ) ;

function _sumo_pp_hide_plain_text_template() {
    $prefix = 'sumo_pp_' ;

    if ( isset( $_GET[ 'section' ] ) && in_array( $_GET[ 'section' ] , array (
                "{$prefix}payment_schedule_email" ,
                "{$prefix}payment_plan_invoice_email" ,
                "{$prefix}payment_plan_success_email" ,
                "{$prefix}payment_plan_completed_email" ,
                "{$prefix}payment_cancelled_email" ,
                "{$prefix}payment_plan_overdue_email" ,
                "{$prefix}deposit_balance_payment_invoice_email" ,
                "{$prefix}deposit_balance_payment_completed_email" ,
                "{$prefix}deposit_balance_payment_overdue_email" ,
            ) )
    ) {
        echo '<style>div.template_plain{display:none;}</style>' ;
    }
}

add_action( 'admin_head' , '_sumo_pp_hide_plain_text_template' ) ;

function _sumo_pp_wc_email_handler( $bool , $order ) {
    if ( ! $order = _sumo_pp_get_order( $order ) ) {
        return $bool ;
    }

    if (
            $order->has_status( get_option( '_sumo_pp_disabled_wc_order_emails' , array () ) ) &&
            $order->is_invoice() &&
            _sumo_pp_is_payment_order( $order->order_id )
    ) {
        return false ;
    }
    return $bool ;
}

add_filter( 'woocommerce_email_enabled_customer_completed_order' , '_sumo_pp_wc_email_handler' , 99 , 2 ) ;
add_filter( 'woocommerce_email_enabled_customer_processing_order' , '_sumo_pp_wc_email_handler' , 99 , 2 ) ;

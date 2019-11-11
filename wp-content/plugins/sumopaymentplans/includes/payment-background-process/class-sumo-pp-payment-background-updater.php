<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit ; // Exit if accessed directly
}

/**
 * Handle when Recurrence cron gets elapsed
 * 
 * @class SUMO_PP_Background_Updater
 * @category Class
 */
class SUMO_PP_Background_Updater {

    /**
     * Cron Interval in Seconds.
     * 
     * @var int
     * @access private
     */
    private static $cron_interval = SUMO_PP_PLUGIN_CRON_INTERVAL ;

    /**
     * Cron hook identifier
     *
     * @var mixed
     * @access protected
     */
    protected static $cron_hook_identifier ;

    /**
     * Cron interval identifier
     *
     * @var mixed
     * @access protected
     */
    protected static $cron_interval_identifier ;

    /**
     * Init SUMO_PP_Background_Updater
     */
    public static function init() {

        self::$cron_hook_identifier     = 'sumopaymentplans_background_updater' ;
        self::$cron_interval_identifier = 'sumopaymentplans_cron_interval' ;

        self::create_cron_job() ;
        self::init_background_updater() ;
    }

    /**
     * Create Recurrence Cron Job.
     */
    public static function create_cron_job() {

        //may be preventing the recurrence Cron interval not to be greater than SUMO_PP_PLUGIN_CRON_INTERVAL
        if ( (wp_next_scheduled( self::$cron_hook_identifier ) - _sumo_pp_get_timestamp()) > self::$cron_interval ) {
            wp_clear_scheduled_hook( self::$cron_hook_identifier ) ;
        }

        //Schedule Recurrence Cron job
        if ( ! wp_next_scheduled( self::$cron_hook_identifier ) ) {
            wp_schedule_event( _sumo_pp_get_timestamp() + self::$cron_interval , self::$cron_interval_identifier , self::$cron_hook_identifier ) ;
        }
    }

    /**
     * Init background updater.
     */
    public static function init_background_updater() {
        //Fire when Recurrence cron gets elapsed
        add_action( self::$cron_hook_identifier , array ( __CLASS__ , 'background_updater' ) ) ;

        // Fire Scheduled Cron Hooks. $payment_jobs as job name => do some job
        $payment_jobs = array (
            'create_balance_payable_order' => 'create_balance_payable_order' ,
            'notify_reminder'              => 'notify_reminder' ,
            'notify_overdue'               => 'set_overdue' ,
            'notify_cancelled'             => 'set_cancelled' ,
                ) ;

        foreach ( $payment_jobs as $job_name => $do_job ) {
            add_action( "sumopaymentplans_fire_{$job_name}" , __CLASS__ . "::{$do_job}" ) ;
        }
    }

    /**
     * Fire when recurrence Cron gets Elapsed
     * Background Updater.
     */
    public static function background_updater() {
        $cron_jobs = _sumo_pp()->query->get( array (
            'type'   => 'sumo_pp_cron_jobs' ,
            'status' => 'publish' ,
                ) ) ;

        if ( empty( $cron_jobs ) ) {
            return ;
        }

        //Loop through each Scheduled Job Query post and check whether time gets elapsed
        foreach ( $cron_jobs as $job_id ) {
            $jobs = get_post_meta( $job_id , '_scheduled_jobs' , true ) ;

            if ( ! is_array( $jobs ) ) {
                continue ;
            }

            foreach ( $jobs as $payment_id => $payment_jobs ) {
                foreach ( $payment_jobs as $job_name => $job_args ) {
                    if ( ! is_array( $job_args ) ) {
                        continue ;
                    }

                    foreach ( $job_args as $job_timestamp => $args ) {
                        if ( ! is_int( $job_timestamp ) || ! $job_timestamp ) {
                            continue ;
                        }
                        //When the time gets elapsed then do the corresponding job.
                        if ( _sumo_pp_get_timestamp() >= $job_timestamp ) {
                            do_action( "sumopaymentplans_fire_{$job_name}" , array_merge( array (
                                'payment_id' => $payment_id
                                            ) , $args ) ) ;

                            //Refresh job.
                            $jobs = get_post_meta( $job_id , '_scheduled_jobs' , true ) ;

                            //Clear the Job when the corresponding job is done.
                            if ( did_action( "sumopaymentplans_fire_{$job_name}" ) ) {
                                unset( $jobs[ $payment_id ][ $job_name ][ $job_timestamp ] ) ;
                            }
                        }
                    }
                    //Flush the meta once the timestamp is not available for the specific job
                    if ( empty( $jobs[ $payment_id ][ $job_name ] ) ) {
                        unset( $jobs[ $payment_id ][ $job_name ] ) ;
                    }
                }
            }
            //Get updated scheduled jobs.
            if ( is_array( $jobs ) ) {
                update_post_meta( $job_id , '_scheduled_jobs' , $jobs ) ;
            }
        }
    }

    /**
     * Create Balance Payable Order for the Payment
     * @param array $args
     */
    public static function create_balance_payable_order( $args ) {

        $args = wp_parse_args( $args , array (
            'payment_id'      => 0 ,
            'next_payment_on' => '' ,
                ) ) ;

        if ( _sumo_pp_payment_has_status( $args[ 'payment_id' ] , 'in_progress' ) ) {
            if ( _sumo_pp_is_balance_payable_order_exists( $args[ 'payment_id' ] ) ) {
                $balance_payable_order_id = get_post_meta( $args[ 'payment_id' ] , '_balance_payable_order_id' , true ) ;
            } else {
                $balance_payable_order_id = SUMO_PP_Payment_Order::create_balance_payable_order( $args[ 'payment_id' ] ) ;
            }

            if ( $payment_cron = _sumo_pp_get_payment_cron( $args[ 'payment_id' ] ) ) {
                $payment_cron->unset_jobs() ;

                if ( $overdue_time_till = _sumo_pp_get_overdue_time_till( $args[ 'next_payment_on' ] ) ) {
                    $payment_cron->schedule_overdue_notify( $balance_payable_order_id , $args[ 'next_payment_on' ] , $overdue_time_till ) ;
                } else {
                    $payment_cron->schedule_cancelled_notify( $balance_payable_order_id , $args[ 'next_payment_on' ] ) ;
                }

                if ( 'payment-plans' === get_post_meta( $args[ 'payment_id' ] , '_payment_type' , true ) ) {
                    $payment_cron->schedule_reminder( $balance_payable_order_id , $args[ 'next_payment_on' ] , 'payment_plan_invoice' ) ;
                } else {
                    $payment_cron->schedule_reminder( $balance_payable_order_id , $args[ 'next_payment_on' ] , 'deposit_balance_payment_invoice' ) ;
                }
            }
        }
    }

    /**
     * Create Single/Multiple Reminder
     * @param array $args
     */
    public static function notify_reminder( $args ) {

        $args = wp_parse_args( $args , array (
            'payment_id'               => 0 ,
            'balance_payable_order_id' => 0 ,
            'mail_template_id'         => ''
                ) ) ;

        if ( ! $balance_payable_order = _sumo_pp_get_order( $args[ 'balance_payable_order_id' ] ) ) {
            return ;
        }
        if ( $balance_payable_order->has_status( 'pending' ) ) {

            switch ( $args[ 'mail_template_id' ] ) {
                case 'payment_plan_invoice':
                case 'deposit_balance_payment_invoice':
                    if ( _sumo_pp_payment_has_status( $args[ 'payment_id' ] , 'in_progress' ) ) {
                        //Trigger email
                        _sumo_pp_send_payment_email( $args[ 'payment_id' ] , $args[ 'mail_template_id' ] , $args[ 'balance_payable_order_id' ] ) ;
                    }
                    break ;
                case 'payment_plan_overdue':
                case 'deposit_balance_payment_overdue':
                    if ( _sumo_pp_payment_has_status( $args[ 'payment_id' ] , 'overdue' ) ) {
                        //Trigger email
                        _sumo_pp_send_payment_email( $args[ 'payment_id' ] , $args[ 'mail_template_id' ] , $args[ 'balance_payable_order_id' ] ) ;
                    }
                    break ;
            }
        }
    }

    /**
     * Set Payment status as Overdue
     * @param array $args
     */
    public static function set_overdue( $args ) {

        $args = wp_parse_args( $args , array (
            'payment_id'               => 0 ,
            'balance_payable_order_id' => 0 ,
            'overdue_date_till'        => 0 ,
                ) ) ;

        if ( ! $balance_payable_order = _sumo_pp_get_order( $args[ 'balance_payable_order_id' ] ) ) {
            return ;
        }
        if ( $balance_payable_order->has_status( 'pending' ) ) {
            if ( _sumo_pp_payment_has_status( $args[ 'payment_id' ] , 'in_progress' ) && _sumo_pp_update_payment_status( $args[ 'payment_id' ] , 'overdue' ) ) {

                _sumo_pp_add_payment_note( sprintf( __( 'Balance payment of order#%s is not paid so far. Payment is in Overdue.' , _sumo_pp()->text_domain ) , $args[ 'balance_payable_order_id' ] ) , $args[ 'payment_id' ] , 'pending' , __( 'Overdue Payment' , _sumo_pp()->text_domain ) ) ;

                update_post_meta( $args[ 'payment_id' ] , '_next_payment_date' , '' ) ;

                //Schedule multiple Overdue reminder email
                if ( $payment_cron = _sumo_pp_get_payment_cron( $args[ 'payment_id' ] ) ) {
                    $payment_closing_date = _sumo_pp_get_timestamp( '+1 day' , $args[ 'overdue_date_till' ] ) ;

                    if ( _sumo_pp_get_remaining_installments( $args[ 'payment_id' ] ) > 1 ) {
                        $next_installment_date = _sumo_pp_get_timestamp( _sumo_pp_get_next_payment_date( $args[ 'payment_id' ] , true ) ) ;

                        if ( $payment_closing_date >= $next_installment_date ) {
                            $payment_closing_date = $next_installment_date ;
                        }
                    }

                    $payment_cron->unset_jobs() ;
                    $payment_cron->schedule_cancelled_notify( $args[ 'balance_payable_order_id' ] , $payment_closing_date ) ;

                    if ( 'payment-plans' === get_post_meta( $args[ 'payment_id' ] , '_payment_type' , true ) ) {
                        $payment_cron->schedule_reminder( $args[ 'balance_payable_order_id' ] , $payment_closing_date , 'payment_plan_overdue' ) ;
                    } else {
                        $payment_cron->schedule_reminder( $args[ 'balance_payable_order_id' ] , $payment_closing_date , 'deposit_balance_payment_overdue' ) ;
                    }
                }
                if ( 'payment-plans' === get_post_meta( $args[ 'payment_id' ] , '_payment_type' , true ) ) {
                    _sumo_pp_send_payment_email( $args[ 'payment_id' ] , 'payment_plan_overdue' , $args[ 'balance_payable_order_id' ] ) ;
                } else {
                    _sumo_pp_send_payment_email( $args[ 'payment_id' ] , 'deposit_balance_payment_overdue' , $args[ 'balance_payable_order_id' ] ) ;
                }

                do_action( 'sumopaymentplans_payment_is_overdue' , $args[ 'payment_id' ] , $args[ 'balance_payable_order_id' ] , 'balance-payment-order' ) ;
            }
        }
    }

    /**
     * Set Payment status as Cancelled
     * @param array $args
     */
    public static function set_cancelled( $args ) {

        $args = wp_parse_args( $args , array (
            'payment_id'               => 0 ,
            'balance_payable_order_id' => 0 ,
                ) ) ;

        if ( _sumo_pp_payment_has_status( $args[ 'payment_id' ] , 'in_progress' , 'overdue' ) ) {
            if ( _sumo_pp_update_payment_status( $args[ 'payment_id' ] , 'cancelled' ) ) {

                _sumo_pp_add_payment_note( sprintf( __( 'Balance payment of order#%s is not paid so far. Payment is Cancelled.' , _sumo_pp()->text_domain ) , $args[ 'balance_payable_order_id' ] ) , $args[ 'payment_id' ] , 'success' , __( 'Payment Cancelled' , _sumo_pp()->text_domain ) ) ;

                update_post_meta( $args[ 'payment_id' ] , '_next_payment_date' , '' ) ;
                update_post_meta( $args[ 'payment_id' ] , '_next_installment_amount' , '0' ) ;
                update_post_meta( $args[ 'payment_id' ] , '_remaining_payable_amount' , '0' ) ;
                update_post_meta( $args[ 'payment_id' ] , '_remaining_installments' , '0' ) ;

                if ( $payment_cron = _sumo_pp_get_payment_cron( $args[ 'payment_id' ] ) ) {
                    $payment_cron->unset_jobs() ;
                }

                _sumo_pp_send_payment_email( $args[ 'payment_id' ] , 'payment_cancelled' , $args[ 'balance_payable_order_id' ] ) ;

                do_action( 'sumopaymentplans_payment_is_cancelled' , $args[ 'payment_id' ] , $args[ 'balance_payable_order_id' ] , 'balance-payment-order' ) ;
            }
        }
    }

}

SUMO_PP_Background_Updater::init() ;

<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit ; // Exit if accessed directly
}

/**
 * Handle payment order
 * 
 * @class SUMO_PP_Payment_Order
 * @category Class
 */
class SUMO_PP_Payment_Order {

    public static $customer_checkout_transient ;

    /**
     * Init SUMO_PP_Payment_Order.
     */
    public static function init() {
        add_action( 'woocommerce_checkout_update_order_meta' , __CLASS__ . '::checkout_update_customer_meta' , 10 , 2 ) ;
        add_action( 'woocommerce_order_status_changed' , __CLASS__ . '::create_new_payments' , 19 , 3 ) ;
        add_action( 'woocommerce_order_status_changed' , __CLASS__ . '::update_payments' , 20 , 3 ) ;
        add_filter( 'woocommerce_can_reduce_order_stock' , __CLASS__ . '::prevent_stock_reduction' , 20 , 2 ) ;
    }

    public static function customer_has_payment_items_in_checkout( $customer_id ) {
        $customer_has_payment_items        = false ;
        self::$customer_checkout_transient = get_user_meta( $customer_id , _sumo_pp()->prefix . 'checkout_transient' , true ) ;

        if ( is_array( self::$customer_checkout_transient ) ) {
            foreach ( self::$customer_checkout_transient as $payment_item => $props ) {
                if ( _sumo_pp_get_product( $payment_item ) && isset( $props[ 'payment_product_props' ] ) ) {
                    $customer_has_payment_items = true ;
                    break ;
                }
            }
        }
        return apply_filters( 'sumopaymentplans_customer_has_payment_items_in_checkout' , $customer_has_payment_items , $customer_id ) ;
    }

    /**
     * Save Customer checkout information
     * @param int $order_id The Order post ID
     * @param array $posted
     */
    public static function checkout_update_customer_meta( $order_id , $posted = array () ) {
        if ( ! $payment_order = _sumo_pp_get_order( $order_id ) ) {
            return ;
        }

        if ( $customer_id = $payment_order->get_customer_id() ) {

            delete_user_meta( $customer_id , _sumo_pp()->prefix . 'checkout_transient' ) ;
            update_user_meta( $customer_id , _sumo_pp()->prefix . 'checkout_transient' , SUMO_PP_Frontend::get_cart_session() ) ;

            do_action( 'sumopaymentplans_checkout_update_customer_meta' , $payment_order , $customer_id ) ;
        }
    }

    /**
     * Create new payment orders after the subscriber successfully placed the initial payment order.
     * Fire only for the Initial Payment order.
     * 
     * @param int $order_id The Order post ID
     * @param string $old_order_status
     * @param string $new_order_status
     */
    public static function create_new_payments( $order_id , $old_order_status , $new_order_status ) {

        if ( ! $order = _sumo_pp_get_order( $order_id ) ) {
            return ;
        }
        if ( ! self::customer_has_payment_items_in_checkout( $order->get_customer_id() ) ) {
            return ;
        }

        if ( apply_filters( 'sumopaymentplans_add_new_payments' , true , $order->order_id , $old_order_status , $new_order_status ) ) :

            do_action( 'sumopaymentplans_before_adding_new_payments' , $order->order_id , $old_order_status , $new_order_status ) ;

            if ( isset( self::$customer_checkout_transient[ 'product_type' ] ) && 'order' === self::$customer_checkout_transient[ 'product_type' ] ) {
                self::add_new_payment( $order ) ;
            } else {
                $saved_items = array () ;
                foreach ( $order->get_item_meta( 'item' ) as $item ) :
                    if ( ! isset( $item[ 'product_id' ] ) ) {
                        continue ;
                    }

                    $item_id = $item[ 'product_id' ] ;

                    if ( is_numeric( $item[ 'variation_id' ] ) && $item[ 'variation_id' ] ) {
                        $item_id = $item[ 'variation_id' ] ;
                    }
                    //may be prevent duplication.
                    if ( in_array( $item_id , $saved_items ) ) {
                        continue ;
                    }
                    //may be add new payment entry.
                    if ( isset( self::$customer_checkout_transient[ $item_id ] ) ) {
                        self::add_new_payment( $order , $item_id ) ;
                        $saved_items[] = $item_id ;
                    }
                endforeach ;
            }
            do_action( 'sumopaymentplans_after_new_payments_added' , $order->order_id , $old_order_status , $new_order_status ) ;

            //Clear checkout transient after successfully saved meta values
            delete_user_meta( $order->get_customer_id() , _sumo_pp()->prefix . 'checkout_transient' ) ;
        endif ;
    }

    /**
     * Add new Payments.
     * @param int | object $order The Order post ID
     * @param int $product_id The Product post ID
     */
    public static function add_new_payment( $order , $product_id = 0 ) {
        $payment_status = _sumo_pp_get_payment_status() ;

        try {
            //Insert new payment post
            $payment_id = wp_insert_post( array (
                'post_type'     => 'sumo_pp_payments' ,
                'post_date'     => _sumo_pp_get_date() ,
                'post_date_gmt' => _sumo_pp_get_date() ,
                'post_status'   => $payment_status[ 'name' ] ,
                'post_author'   => 1 ,
                'post_title'    => __( 'Payments' , _sumo_pp()->text_domain ) ,
                    ) , true ) ;

            if ( is_wp_error( $payment_id ) ) {
                throw new Exception( $payment_id->get_error_message() ) ;
            }

            if ( isset( self::$customer_checkout_transient[ 'product_type' ] ) && 'order' === self::$customer_checkout_transient[ 'product_type' ] ) {
                foreach ( self::$customer_checkout_transient as $meta_key => $value ) {
                    if ( ! is_null( $value ) ) {
                        if ( 'payment_plan_props' === $meta_key ) {
                            foreach ( $value as $_meta_key => $_value ) {
                                add_post_meta( $payment_id , "_{$_meta_key}" , $_value ) ;
                            }
                        } else {
                            add_post_meta( $payment_id , "_{$meta_key}" , $value ) ;
                        }
                    }
                }
            } else {
                foreach ( self::$customer_checkout_transient[ $product_id ] as $meta_key => $value ) {
                    if ( ! is_null( $value ) ) {
                        if ( 'payment_product_props' === $meta_key ) {
                            foreach ( $value as $_meta_key => $_value ) {
                                if ( ! is_null( $_value ) ) {
                                    add_post_meta( $payment_id , "_{$_meta_key}" , $_value ) ;
                                }
                            }
                        } else if ( 'payment_plan_props' === $meta_key ) {
                            foreach ( $value as $_meta_key => $_value ) {
                                add_post_meta( $payment_id , "_{$_meta_key}" , $_value ) ;
                            }
                        } else {
                            add_post_meta( $payment_id , "_{$meta_key}" , $value ) ;
                        }
                    }
                }
            }

            add_post_meta( $payment_id , '_initial_payment_order_id' , $order->order_id ) ;
            add_post_meta( $payment_id , '_customer_id' , $order->get_customer_id() ) ;
            add_post_meta( $payment_id , '_customer_email' , $order->get_billing_email() ) ;
            add_post_meta( $payment_id , '_payment_number' , _sumo_pp_get_payment_serial_number() ) ;
            add_post_meta( $payment_id , '_version' , SUMO_PP_PLUGIN_VERSION ) ;
            add_post_meta( $payment_id , '_get_customer' , get_user_by( 'id' , $order->get_customer_id() ) ) ; //set Customer props
            //Save global settings
            add_post_meta( $payment_id , '_charge_tax_during' , get_option( _sumo_pp()->prefix . 'charge_tax_during' , 'initial-payment' ) ) ;

            add_post_meta( $order->order_id , 'is' . _sumo_pp()->prefix . 'order' , 'yes' ) ;

            _sumo_pp_add_payment_note( __( 'New payment order created.' , _sumo_pp()->text_domain ) , $payment_id , 'pending' , __( 'New Payment Order' , _sumo_pp()->text_domain ) ) ;

            do_action( 'sumopaymentplans_new_payment_order' , $payment_id , $order->order_id ) ;
        } catch ( Exception $e ) {
            return ;
        }
    }

    /**
     * Update each payment data based upon Order status.
     * @param int $order_id The Order post ID
     * @param string $old_order_status
     * @param string $new_order_status
     */
    public static function update_payments( $order_id , $old_order_status , $new_order_status ) {

        if ( ! $order = _sumo_pp_get_order( $order_id ) ) {
            return ;
        }
        //Check whether this order is already placed
        if ( ! in_array( $old_order_status , array ( 'pending' , 'on-hold' ) ) ) {
            return ;
        }

        $payments = _sumo_pp()->query->get( array (
            'type'       => 'sumo_pp_payments' ,
            'status'     => array ( _sumo_pp()->prefix . 'pending' , _sumo_pp()->prefix . 'in_progress' , _sumo_pp()->prefix . 'overdue' ) ,
            'meta_key'   => '_initial_payment_order_id' ,
            'meta_value' => $order->get_parent_id() ,
                ) ) ;

        foreach ( $payments as $payment_id ) :
            //may be balance payment is paying.
            if ( $order->is_invoice() ) {
                //Check which balance payment is paying.
                if ( $order->order_id != get_post_meta( $payment_id , '_balance_payable_order_id' , true ) ) {
                    continue ;
                }

                //Check payment status is valid to change.
                if ( ! _sumo_pp_payment_has_status( $payment_id , array ( 'in_progress' , 'overdue' ) ) ) {
                    continue ;
                }

                //Proceed this payment based upon the Balance payment Order status.
                switch ( $new_order_status ) {
                    case 'pending':
                    case 'on-hold':
                        _sumo_pp_add_payment_note( sprintf( __( 'Waiting for balance payment order#%s to complete.' , _sumo_pp()->text_domain ) , $order->order_id ) , $payment_id , 'pending' , __( 'Waiting For Balance Payment' , _sumo_pp()->text_domain ) ) ;

                        do_action( 'sumopaymentplans_payment_in_pending' , $payment_id , $order->order_id , 'balance-payment-order' ) ;
                        break ;
                    case 'completed':
                    case 'processing':
                        _sumo_pp_update_as_paid_order( $payment_id , $order->order_id ) ;

                        if ( _sumo_pp_payment_has_next_installment( $payment_id ) ) {
                            if ( _sumo_pp_update_payment_status( $payment_id , 'in_progress' ) ) {

                                _sumo_pp_update_actual_payments_date( $payment_id ) ;
                                _sumo_pp_add_payment_note( sprintf( __( 'Balance payment of order#%s made successful. Remaining payment is in progress.' , _sumo_pp()->text_domain ) , $order->order_id ) , $payment_id , 'success' , __( 'Balance Payment Success' , _sumo_pp()->text_domain ) ) ;

                                $next_payment_date = _sumo_pp_get_next_payment_date( $payment_id ) ;

                                update_post_meta( $payment_id , '_last_payment_date' , _sumo_pp_get_date() ) ;
                                update_post_meta( $payment_id , '_next_payment_date' , $next_payment_date ) ;
                                update_post_meta( $payment_id , '_next_installment_amount' , _sumo_pp_get_next_installment_amount( $payment_id ) ) ;
                                update_post_meta( $payment_id , '_remaining_payable_amount' , _sumo_pp_get_remaining_payable_amount( $payment_id ) ) ;
                                update_post_meta( $payment_id , '_remaining_installments' , _sumo_pp_get_remaining_installments( $payment_id ) ) ;

                                if ( 'immediately_after_payment' === get_post_meta( $payment_id , '_balance_payable_orders_creation' , true ) ) {
                                    self::create_balance_payable_order( $payment_id ) ;
                                }
                                if ( $payment_cron = _sumo_pp_get_payment_cron( $payment_id ) ) {
                                    $payment_cron->unset_jobs() ;
                                    $payment_cron->schedule_balance_payable_order( $next_payment_date ) ;
                                }

                                _sumo_pp_send_payment_email( $payment_id , 'payment_plan_success' , $order->order_id ) ;

                                do_action( 'sumopaymentplans_payment_in_progress' , $payment_id , $order->order_id , 'balance-payment-order' ) ;
                            }
                        } else {
                            if ( _sumo_pp_update_payment_status( $payment_id , 'completed' ) ) {

                                _sumo_pp_update_actual_payments_date( $payment_id ) ;
                                _sumo_pp_add_payment_note( sprintf( __( 'Balance payment of order#%s made successful. Payment is completed' , _sumo_pp()->text_domain ) , $order->order_id ) , $payment_id , 'success' , __( 'Balance Payment Complete' , _sumo_pp()->text_domain ) ) ;

                                update_post_meta( $payment_id , '_last_payment_date' , _sumo_pp_get_date() ) ;
                                update_post_meta( $payment_id , '_next_payment_date' , '' ) ;
                                update_post_meta( $payment_id , '_next_installment_amount' , '0' ) ;
                                update_post_meta( $payment_id , '_remaining_payable_amount' , '0' ) ;
                                update_post_meta( $payment_id , '_remaining_installments' , '0' ) ;

                                if ( $payment_cron = _sumo_pp_get_payment_cron( $payment_id ) ) {
                                    $payment_cron->unset_jobs() ;
                                }

                                if ( 'pay-in-deposit' === get_post_meta( $payment_id , '_payment_type' , true ) ) {
                                    _sumo_pp_send_payment_email( $payment_id , 'deposit_balance_payment_completed' , $order->order_id ) ;
                                } else {
                                    _sumo_pp_send_payment_email( $payment_id , 'payment_plan_success' , $order->order_id ) ;
                                    _sumo_pp_send_payment_email( $payment_id , 'payment_plan_completed' , $order->order_id ) ;
                                }

                                do_action( 'sumopaymentplans_payment_is_completed' , $payment_id , $order->order_id , 'balance-payment-order' ) ;
                            }
                        }
                        break ;
                    case 'failed':
                        if ( _sumo_pp_update_payment_status( $payment_id , 'failed' ) ) {
                            _sumo_pp_add_payment_note( sprintf( __( 'Failed to pay the balance payment of order#%s . Payment is failed.' , _sumo_pp()->text_domain ) , $order->order_id ) , $payment_id , 'failure' , __( 'Balance Payment Failed' , _sumo_pp()->text_domain ) ) ;

                            update_post_meta( $payment_id , '_next_payment_date' , '' ) ;
                            update_post_meta( $payment_id , '_next_installment_amount' , '0' ) ;
                            update_post_meta( $payment_id , '_remaining_payable_amount' , '0' ) ;
                            update_post_meta( $payment_id , '_remaining_installments' , '0' ) ;

                            if ( $payment_cron = _sumo_pp_get_payment_cron( $payment_id ) ) {
                                $payment_cron->unset_jobs() ;
                            }

                            do_action( 'sumopaymentplans_payment_is_failed' , $payment_id , $order->order_id , 'balance-payment-order' ) ;
                        }
                        break ;
                    case 'cancelled':
                        if ( _sumo_pp_update_payment_status( $payment_id , 'cancelled' ) ) {
                            _sumo_pp_add_payment_note( sprintf( __( 'Failed to pay the balance payment of order#%s. Payment is cancelled.' , _sumo_pp()->text_domain ) , $order->order_id ) , $payment_id , 'failure' , __( 'Balance Payment Cancelled' , _sumo_pp()->text_domain ) ) ;

                            update_post_meta( $payment_id , '_next_payment_date' , '' ) ;
                            update_post_meta( $payment_id , '_next_installment_amount' , '0' ) ;
                            update_post_meta( $payment_id , '_remaining_payable_amount' , '0' ) ;
                            update_post_meta( $payment_id , '_remaining_installments' , '0' ) ;

                            if ( $payment_cron = _sumo_pp_get_payment_cron( $payment_id ) ) {
                                $payment_cron->unset_jobs() ;
                            }

                            _sumo_pp_send_payment_email( $payment_id , 'payment_cancelled' , $order->order_id ) ;

                            do_action( 'sumopaymentplans_payment_is_cancelled' , $payment_id , $order->order_id , 'balance-payment-order' ) ;
                        }
                        break ;
                }
                //may be Initial Payment is paying.
            } else if ( $order->is_parent() && '' === get_post_meta( $payment_id , '_payment_start_date' , true ) ) {
                //Proceed this payment based upon the Initial payment Order status.
                switch ( $new_order_status ) {
                    case 'pending':
                    case 'on-hold':
                        _sumo_pp_add_payment_note( sprintf( __( 'Waiting for initial payment order#%s to complete.' , _sumo_pp()->text_domain ) , $order->order_id ) , $payment_id , 'pending' , __( 'Waiting For Initial Payment' , _sumo_pp()->text_domain ) ) ;

                        do_action( 'sumopaymentplans_payment_in_pending' , $payment_id , $order->order_id , 'initial-payment-order' ) ;
                        break ;
                    case 'completed':
                    case 'processing':
                        if ( 'before' !== get_post_meta( $payment_id , '_pay_balance_type' , true ) && 'after_admin_approval' === get_post_meta( $payment_id , '_activate_payment' , true ) ) {
                            if ( _sumo_pp_update_payment_status( $payment_id , 'await_aprvl' ) ) {

                                add_post_meta( $payment_id , '_next_installment_amount' , _sumo_pp_get_next_installment_amount( $payment_id ) ) ;
                                add_post_meta( $payment_id , '_remaining_payable_amount' , _sumo_pp_get_remaining_payable_amount( $payment_id ) ) ;
                                add_post_meta( $payment_id , '_remaining_installments' , _sumo_pp_get_remaining_installments( $payment_id ) ) ;
                                _sumo_pp_add_payment_note( __( 'Awaiting Admin to approve the payment.' , _sumo_pp()->text_domain ) , $payment_id , 'pending' , __( 'Awaiting Admin Approval' , _sumo_pp()->text_domain ) ) ;

                                do_action( 'sumopaymentplans_payment_awaiting_approval' , $payment_id , $order->order_id , 'initial-payment-order' ) ;
                            }
                        } else if ( _sumo_pp_update_payment_status( $payment_id , 'in_progress' ) ) {

                            _sumo_pp_update_scheduled_payments_date( $payment_id ) ;
                            _sumo_pp_add_payment_note( sprintf( __( 'Initial payment of order#%s is paid. Payment is in progress' , _sumo_pp()->text_domain ) , $order->order_id ) , $payment_id , 'success' , __( 'Initial Payment Success' , _sumo_pp()->text_domain ) ) ;

                            $payment_type      = get_post_meta( $payment_id , '_payment_type' , true ) ;
                            $next_payment_date = _sumo_pp_get_next_payment_date( $payment_id ) ;

                            add_post_meta( $payment_id , '_payment_start_date' , _sumo_pp_get_date() ) ;
                            add_post_meta( $payment_id , '_last_payment_date' , _sumo_pp_get_date() ) ;
                            add_post_meta( $payment_id , '_payment_end_date' , _sumo_pp_get_payment_end_date( $payment_id ) ) ;
                            add_post_meta( $payment_id , '_next_payment_date' , $next_payment_date ) ;
                            add_post_meta( $payment_id , '_next_installment_amount' , _sumo_pp_get_next_installment_amount( $payment_id ) ) ;
                            add_post_meta( $payment_id , '_remaining_payable_amount' , _sumo_pp_get_remaining_payable_amount( $payment_id ) ) ;
                            add_post_meta( $payment_id , '_remaining_installments' , _sumo_pp_get_remaining_installments( $payment_id ) ) ;

                            if ( 'pay-in-deposit' === $payment_type ) {
                                $balance_payable_order_id = self::create_balance_payable_order( $payment_id ) ;

                                if ( $next_payment_date && ($payment_cron = _sumo_pp_get_payment_cron( $payment_id ) ) ) {
                                    if ( 'before' === get_post_meta( $payment_id , '_pay_balance_type' , true ) ) {
                                        $payment_item_meta = get_post_meta( $payment_id , '_item_meta' , true ) ;

                                        if (
                                                isset( $payment_item_meta[ 'sumo_bookings' ][ 'booking_id' ] ) ||
                                                'yes' === get_post_meta( $payment_id , '_sumopreorder_product' , true )
                                        ) {
                                            //May be if it is SUMO Booking payment, Payment End date = Next Payment date
                                            //May be if it is SUMO Pre-Order payment, Product Release Date = Next Payment date
                                            $payment_cron->schedule_cancelled_notify( $balance_payable_order_id , $next_payment_date ) ;
                                        } else {
                                            if ( $overdue_time_till = _sumo_pp_get_overdue_time_till( $next_payment_date ) ) {
                                                $payment_cron->schedule_overdue_notify( $balance_payable_order_id , $next_payment_date , $overdue_time_till ) ;
                                            } else {
                                                $payment_cron->schedule_cancelled_notify( $balance_payable_order_id , $next_payment_date ) ;
                                            }
                                        }
                                    } else {
                                        if ( $overdue_time_till = _sumo_pp_get_overdue_time_till( $next_payment_date ) ) {
                                            $payment_cron->schedule_overdue_notify( $balance_payable_order_id , $next_payment_date , $overdue_time_till ) ;
                                        } else {
                                            $payment_cron->schedule_cancelled_notify( $balance_payable_order_id , $next_payment_date ) ;
                                        }
                                    }
                                    $payment_cron->schedule_reminder( $balance_payable_order_id , $next_payment_date , 'deposit_balance_payment_invoice' ) ;
                                }
                            } else {
                                if ( 'immediately_after_payment' === get_post_meta( $payment_id , '_balance_payable_orders_creation' , true ) ) {
                                    self::create_balance_payable_order( $payment_id ) ;
                                }
                                if ( $payment_cron = _sumo_pp_get_payment_cron( $payment_id ) ) {
                                    $payment_cron->schedule_balance_payable_order( $next_payment_date ) ;
                                }
                            }

                            _sumo_pp_send_payment_email( $payment_id , 'payment_schedule' , $order->order_id ) ;

                            do_action( 'sumopaymentplans_payment_in_progress' , $payment_id , $order->order_id , 'initial-payment-order' ) ;
                        }
                        break ;
                    case 'failed':
                        if ( _sumo_pp_update_payment_status( $payment_id , 'failed' ) ) {
                            _sumo_pp_add_payment_note( sprintf( __( 'Failed to pay the initial payment of order#%s . Payment is failed.' , _sumo_pp()->text_domain ) , $order->order_id ) , $payment_id , 'failure' , __( 'Initial Payment Failed' , _sumo_pp()->text_domain ) ) ;

                            do_action( 'sumopaymentplans_payment_is_failed' , $payment_id , $order->order_id , 'initial-payment-order' ) ;
                        }
                        break ;
                    case 'cancelled':
                        if ( _sumo_pp_update_payment_status( $payment_id , 'cancelled' ) ) {
                            _sumo_pp_add_payment_note( sprintf( __( 'Failed to pay the initial payment of order#%s. Payment is cancelled.' , _sumo_pp()->text_domain ) , $order->order_id ) , $payment_id , 'failure' , __( 'Initial Payment Cancelled' , _sumo_pp()->text_domain ) ) ;

                            _sumo_pp_send_payment_email( $payment_id , 'payment_cancelled' , $order->order_id ) ;

                            do_action( 'sumopaymentplans_payment_is_cancelled' , $payment_id , $order->order_id , 'initial-payment-order' ) ;
                        }
                        break ;
                }
            }
        endforeach ;
    }

    public static function prevent_stock_reduction( $bool , $order ) {
        if ( ! $order = _sumo_pp_get_order( $order ) ) {
            return $bool ;
        }

        if ( $order->is_invoice() ) {
            $payment_id = get_post_meta( $order->order_id , '_payment_id' , true ) ;

            if ( _sumo_pp_is_payment_order( $order->order_id ) && _sumo_pp_payment_exists( $payment_id ) ) {
                return false ;
            }
        }
        return $bool ;
    }

    /**
     * Create Balance payable Order.
     * @param int $payment_id The Payment post ID.
     * @return int
     */
    public static function create_balance_payable_order( $payment_id ) {

        if ( ! $initial_payment_order = _sumo_pp_get_order( get_post_meta( $payment_id , '_initial_payment_order_id' , true ) ) ) {
            return ;
        }

        //Create Order.
        $order_id = wp_insert_post( array (
            'post_type'   => 'shop_order' ,
            'post_status' => 'publish' ,
            'post_author' => 1 ,
            'post_parent' => $initial_payment_order->order_id ,
                ) , true ) ;

        if ( is_wp_error( $order_id ) ) {
            _sumo_pp_add_payment_note( __( 'Error while creating balance payable order.' , _sumo_pp()->text_domain ) , $payment_id , 'failure' , __( 'Balance Payable Order Creation Error' , _sumo_pp()->text_domain ) ) ;
            return 0 ;
        }

        //populate Order
        $balance_payable_order = _sumo_pp_get_order( $order_id ) ;

        //set billing address
        self::set_address_details( $initial_payment_order , $balance_payable_order , 'billing' ) ;
        //set shipping address
        self::set_address_details( $initial_payment_order , $balance_payable_order , 'shipping' ) ;
        //set order meta
        self::set_order_details( $initial_payment_order , $balance_payable_order ) ;

        //repopulate Order
        $balance_payable_order = _sumo_pp_get_order( $balance_payable_order->order_id ) ;

        //Add Payment items
        self::add_order_item( $initial_payment_order , $balance_payable_order , $payment_id ) ;

        $tax_enabled = false ;
        if ( 'order' !== get_post_meta( $payment_id , '_product_type' , true ) && 'each-payment' === get_post_meta( $payment_id , '_charge_tax_during' , true ) ) {
            self::set_tax( $initial_payment_order , $balance_payable_order ) ;
            $tax_enabled = true ;
        }

        if ( is_callable( array ( $balance_payable_order->order , 'save' ) ) ) {
            $balance_payable_order->order->save() ;
        }

        // Updates tax totals
        if ( is_callable( array ( $balance_payable_order->order , 'update_taxes' ) ) ) {
            $balance_payable_order->order->update_taxes() ;
        }

        // Calc totals - this also triggers save
        $balance_payable_order->order->calculate_totals( $tax_enabled ) ;

        //Update Default Order status
        $balance_payable_order->order->update_status( 'pending' ) ;

        add_post_meta( $balance_payable_order->order_id , 'is' . _sumo_pp()->prefix . 'order' , 'yes' ) ;
        add_post_meta( $balance_payable_order->order_id , '_payment_id' , $payment_id ) ;
        add_post_meta( $payment_id , '_balance_payable_order_id' , $balance_payable_order->order_id ) ;

        _sumo_pp_add_payment_note( sprintf( __( 'Balance payable order#%s is created.' , _sumo_pp()->text_domain ) , $balance_payable_order->order_id ) , $payment_id , 'pending' , __( 'Balance Payable Order Created' , _sumo_pp()->text_domain ) ) ;

        return $balance_payable_order->order_id ;
    }

    /**
     * Extract billing and shipping information from Initial payment Order and set in Balance payable Order 
     * 
     * @param int $initial_payment_order
     * @param int $balance_payable_order
     * @param string $type valid values are 'billing' | 'shipping 
     * @return boolean
     */
    public static function set_address_details( $initial_payment_order , $balance_payable_order , $type ) {

        $data = array (
            'first_name' => array ( 'billing' , 'shipping' ) ,
            'last_name'  => array ( 'billing' , 'shipping' ) ,
            'company'    => array ( 'billing' , 'shipping' ) ,
            'address_1'  => array ( 'billing' , 'shipping' ) ,
            'address_2'  => array ( 'billing' , 'shipping' ) ,
            'city'       => array ( 'billing' , 'shipping' ) ,
            'postcode'   => array ( 'billing' , 'shipping' ) ,
            'country'    => array ( 'billing' , 'shipping' ) ,
            'state'      => array ( 'billing' , 'shipping' ) ,
            'email'      => array ( 'billing' ) ,
            'phone'      => array ( 'billing' ) ,
                ) ;

        foreach ( $data as $key => $applicable_to ) {
            $value = '' ;

            if ( _sumo_pp_is_wc_version( '<' , '3.0' ) ) {
                $value = get_post_meta( $initial_payment_order->order_id , "_{$type}_{$key}" , true ) ;
            }

            if ( is_callable( array ( $initial_payment_order->order , "get_{$type}_{$key}" ) ) ) {
                $value = $initial_payment_order->order->{"get_{$type}_{$key}"}() ;
            }

            if ( '' === $value ) {
                //may be useful if shipping address is empty
                if ( _sumo_pp_is_wc_version( '<' , '3.0' ) ) {
                    $value = get_post_meta( $initial_payment_order->order_id , "_billing_{$key}" , true ) ;
                }

                if ( is_callable( array ( $initial_payment_order->order , "get_billing_{$key}" ) ) ) {
                    $value = $initial_payment_order->order->{"get_billing_{$key}"}() ;
                }
            }

            if ( in_array( $type , $applicable_to ) ) {
                update_post_meta( $balance_payable_order->order_id , "_{$type}_{$key}" , $value ) ;
            }

            if ( is_callable( array ( $balance_payable_order->order , "set_{$type}_{$key}" ) ) ) {
                $balance_payable_order->order->{"set_{$type}_{$key}"}( $value ) ;
            }
        }
    }

    /**
     * Extract Initial payment Order details other than shipping/billing and set in Balance payable Order 
     * @param int $initial_payment_order
     * @param int $balance_payable_order
     * @return boolean
     */
    public static function set_order_details( $initial_payment_order , $balance_payable_order ) {

        $data = array (
            'version'            => 'order_version' ,
            'currency'           => 'order_currency' ,
            'order_key'          => 'order_key' ,
            'shipping_total'     => 'order_shipping' ,
            'shipping_tax'       => 'order_shipping_tax' ,
            'total_tax'          => 'order_tax' ,
            'customer_id'        => 'customer_user' ,
            'prices_include_tax' => 'prices_include_tax' ,
                ) ;

        foreach ( $data as $method_key => $meta_key ) {
            $value = '' ;

            if ( _sumo_pp_is_wc_version( '<' , '3.0' ) ) {
                $value = get_post_meta( $initial_payment_order->order_id , "_{$meta_key}" , true ) ;
            }

            if ( is_callable( array ( $initial_payment_order->order , "get_{$method_key}" ) ) ) {
                $value = $initial_payment_order->order->{"get_{$method_key}"}() ;
            }

            update_post_meta( $balance_payable_order->order_id , "_{$meta_key}" , $value ) ;

            if ( is_callable( array ( $balance_payable_order->order , "set_{$method_key}" ) ) ) {
                $balance_payable_order->order->{"set_{$method_key}"}( $value ) ;
            }
        }
    }

    /**
     * Add Payment order Item in balance payable Order.
     * @param int $initial_payment_order
     * @param int $balance_payable_order
     * @param int $payment_id
     * @return boolean
     */
    public static function add_order_item( $initial_payment_order , $balance_payable_order , $payment_id ) {

        do_action( 'sumopaymentplans_before_adding_balance_payable_order_item' , $initial_payment_order->order_id , $balance_payable_order->order_id , $payment_id ) ;

        if ( _sumo_pp_is_wc_version( '<' , '3.0' ) ) {
            return ;
        }
        $payment_type = get_post_meta( $payment_id , '_payment_type' , true ) ;
        $balance_type = get_post_meta( $payment_id , '_pay_balance_type' , true ) ;

        foreach ( $initial_payment_order->get_item_meta( 'item' ) as $_item_id => $_item ) {
            $product_id = $_item[ 'variation_id' ] > 0 ? $_item[ 'variation_id' ] : $_item[ 'product_id' ] ;

            if ( ! $_product = wc_get_product( $product_id ) ) {
                continue ;
            }

            if ( 'order' === get_post_meta( $payment_id , '_product_type' , true ) ) {
                $line_total = wc_format_decimal( get_post_meta( $payment_id , '_next_installment_amount' , true ) ) ;

                $item_id = $balance_payable_order->order->add_product( $_product , 1 , array (
                    'name'     => get_option( _sumo_pp()->prefix . 'order_payment_plan_label' ) ,
                    'subtotal' => wc_get_price_excluding_tax( $_product , array (
                        'qty'   => 1 ,
                        'price' => $line_total ,
                    ) ) ,
                    'total'    => wc_get_price_excluding_tax( $_product , array (
                        'qty'   => 1 ,
                        'price' => $line_total ,
                    ) ) ,
                        ) ) ;

                if ( ! is_wp_error( $item_id ) && is_numeric( $item_id ) && $item_id ) {
                    $order_items = get_post_meta( $payment_id , '_order_items' , true ) ;

                    if ( is_array( $order_items ) ) {
                        foreach ( $order_items as $product_id => $data ) {
                            if ( ! $product = _sumo_pp_get_product( $product_id ) ) {
                                continue ;
                            }
                            $product_title = _sumo_pp_get_product_title( $product ) ;
                            wc_add_order_item_meta( $item_id , $product_title , 'x' . $data[ 'qty' ] ) ;
                        }
                    }
                    break ;
                }
            } else if ( $product_id == get_post_meta( $payment_id , '_product_id' , true ) ) {
                $next_installment_amount = floatval( get_post_meta( $payment_id , '_next_installment_amount' , true ) ) ;
                $product_qty             = absint( get_post_meta( $payment_id , '_product_qty' , true ) ) ;
                $product_qty             = $product_qty ? $product_qty : 1 ;
                $line_total              = wc_format_decimal( $next_installment_amount / $product_qty ) ;

                $item_id = $balance_payable_order->order->add_product( $_product , $product_qty , array (
                    'subtotal' => wc_get_price_excluding_tax( $_product , array (
                        'qty'   => $product_qty ,
                        'price' => $line_total
                    ) ) ,
                    'total'    => wc_get_price_excluding_tax( $_product , array (
                        'qty'   => $product_qty ,
                        'price' => $line_total
                    ) ) ,
                        ) ) ;

                if ( isset( $_item[ 'item_meta' ] ) && is_array( $_item[ 'item_meta' ] ) ) {
                    foreach ( $_item[ 'item_meta' ] as $key => $value ) {
                        $due_date_label = str_replace( ':' , '' , (('pay-in-deposit' === $payment_type && 'before' === $balance_type) ? get_option( _sumo_pp()->prefix . 'balance_payment_due_date_label' ) : get_option( _sumo_pp()->prefix . 'next_payment_date_label' ) ) ) ;

                        switch ( $key ) {
                            case $due_date_label:
                                wc_delete_order_item_meta( $item_id , $key , $value ) ;

                                if ( _sumo_pp_get_remaining_installments( $payment_id ) > 1 ) {//Consider > 1 upon validate, since we excluding this unpaid order
                                    wc_add_order_item_meta( $item_id , __( 'Next installment amount' , _sumo_pp()->text_domain ) , wc_price( _sumo_pp_get_next_installment_amount( $payment_id , true ) ) , true ) ;
                                    wc_add_order_item_meta( $item_id , $key , _sumo_pp_get_date_to_display( _sumo_pp_get_next_payment_date( $payment_id , true ) ) , true ) ;
                                }
                                break ;
                            default :
                                wc_add_order_item_meta( $item_id , $key , $value , true ) ;
                        }
                    }
                }
            }
        }
    }

    /**
     * Extract Taxes from Initial payment Order and set in balance payable Order 
     * @param int $initial_payment_order
     * @param int $balance_payable_order
     * @return boolean
     */
    public static function set_tax( $initial_payment_order , $balance_payable_order ) {
        if ( _sumo_pp_is_wc_version( '<' , '3.0' ) ) {
            return ;
        }
        if ( ! $taxes = $initial_payment_order->order->get_taxes() ) {
            return ;
        }

        $item = new WC_Order_Item_Tax() ;
        foreach ( $taxes as $key => $tax ) {

            $item->set_props( array (
                'rate_id'            => $tax[ 'rate_id' ] ,
                'tax_total'          => $tax[ 'tax_total' ] ,
                'shipping_tax_total' => 0 ,
            ) ) ;

            $item->set_rate( $tax[ 'rate_id' ] ) ;
            $item->set_order_id( $balance_payable_order->order_id ) ;
            $item->save() ;
            $balance_payable_order->order->add_item( $item ) ;
        }
    }

}

SUMO_PP_Payment_Order::init() ;

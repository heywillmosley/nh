<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit ; // Exit if accessed directly
}

/**
 * Admin Dashboard.
 * 
 * @class SUMO_PP_Admin_Post_Types
 * @category Class
 */
class SUMO_PP_Admin_Post_Types {

    protected $custom_post_types = array (
        'sumo_payment_plans' => 'payment_plans' ,
        'sumo_pp_payments'   => 'payments' ,
        'sumo_pp_masterlog'  => 'masterlog' ,
        'sumo_pp_cron_jobs'  => 'cron_jobs' ,
            ) ;

    /**
     * SUMO_PP_Admin_Post_Types constructor.
     */
    public function __construct() {

        add_action( 'init' , array ( $this , 'register_post_status' ) ) ;

        foreach ( $this->custom_post_types as $post_type => $meant_for ) {
            add_filter( "manage_{$post_type}_posts_columns" , array ( $this , "output_{$meant_for}_columns" ) ) ;
            add_action( "manage_{$post_type}_posts_custom_column" , array ( $this , "output_{$meant_for}_datas" ) , 10 , 2 ) ;
            add_filter( "manage_edit-{$post_type}_sortable_columns" , array ( $this , 'sort_columns' ) ) ;
            add_filter( "bulk_actions-edit-{$post_type}" , array ( $this , 'remove_bulk_actions' ) ) ;
        }

        add_filter( 'manage_shop_order_posts_columns' , array ( $this , 'shop_order_columns' ) , 11 ) ;
        add_action( 'manage_shop_order_posts_custom_column' , array ( $this , 'render_shop_order_columns' ) , 11 , 2 ) ;

        add_filter( 'enter_title_here' , array ( $this , 'enter_title_here' ) , 1 , 2 ) ;
        add_filter( 'post_row_actions' , array ( $this , 'remove_row_actions' ) , 10 , 2 ) ;
        add_action( 'admin_init' , array ( $this , 'approve_payment' ) ) ;

        add_filter( 'get_search_query' , array ( $this , 'search_label' ) ) ;
        add_filter( 'query_vars' , array ( $this , 'add_custom_query_var' ) ) ;
        add_action( 'parse_query' , array ( $this , 'search_custom_fields' ) ) ;
    }

    /**
     * Register our custom post statuses, used for payment status.
     */
    public function register_post_status() {

        $payment_statuses = _sumo_pp_get_payment_statuses() ;

        foreach ( $payment_statuses as $payment_status => $payment_status_display_name ) {

            register_post_status( $payment_status , array (
                'label'                     => $payment_status_display_name ,
                'public'                    => true ,
                'exclude_from_search'       => false ,
                'show_in_admin_status_list' => true ,
                'show_in_admin_all_list'    => true ,
                'label_count'               => _n_noop( $payment_status_display_name . ' <span class="count">(%s)</span>' , $payment_status_display_name . ' <span class="count">(%s)</span>' ) ,
            ) ) ;
        }
    }

    /**
     * Display Payment Plan columns.
     * @param array $existing_columns
     * @return array
     */
    public function output_payment_plans_columns( $existing_columns ) {
        $columns = array (
            'cb'               => $existing_columns[ 'cb' ] ,
            'plan_name'        => __( 'Payment Plan Name' , _sumo_pp()->text_domain ) ,
            'plan_description' => __( 'Payment Plan Description' , _sumo_pp()->text_domain ) ,
                ) ;
        return $columns ;
    }

    /**
     * Display Payments columns.
     * @param array $existing_columns
     * @return array
     */
    public function output_payments_columns( $existing_columns ) {
        $columns = array (
            'cb'                       => $existing_columns[ 'cb' ] ,
            'payment_status'           => __( 'Payment Status' , _sumo_pp()->text_domain ) ,
            'payment_number'           => __( 'Payment Identification Number' , _sumo_pp()->text_domain ) ,
            'product_name'             => __( 'Product Name' , _sumo_pp()->text_domain ) ,
            'order_id'                 => __( 'Order ID' , _sumo_pp()->text_domain ) ,
            'buyer_email'              => __( 'Buyer Email' , _sumo_pp()->text_domain ) ,
            'billing_name'             => __( 'Billing Name' , _sumo_pp()->text_domain ) ,
            'payment_type'             => __( 'Payment Type' , _sumo_pp()->text_domain ) ,
            'payment_plan'             => __( 'Payment Plan' , _sumo_pp()->text_domain ) ,
            'remaining_installments'   => __( 'Remaining Installments' , _sumo_pp()->text_domain ) ,
            'remaining_payable_amount' => __( 'Remaining Payable Amount' , _sumo_pp()->text_domain ) ,
            'next_installment_amount'  => __( 'Next Installment Amount' , _sumo_pp()->text_domain ) ,
            'payment_start_date'       => __( 'Payment Start Date' , _sumo_pp()->text_domain ) ,
            'next_payment_date'        => __( 'Next Payment Date' , _sumo_pp()->text_domain ) ,
            'payment_ending_date'      => __( 'Payment Ending Date' , _sumo_pp()->text_domain ) ,
            'last_payment_date'        => __( 'Previous Payment Date' , _sumo_pp()->text_domain ) ,
                ) ;
        return $columns ;
    }

    /**
     * Display Masterlog columns.
     * @param array $existing_columns
     * @return array
     */
    public function output_masterlog_columns( $existing_columns ) {
        $columns = array (
            'cb'             => $existing_columns[ 'cb' ] ,
            'status'         => __( 'Status' , _sumo_pp()->text_domain ) ,
            'message'        => __( 'Message' , _sumo_pp()->text_domain ) ,
            'user_name'      => __( 'User Name' , _sumo_pp()->text_domain ) ,
            'payment_number' => __( 'Payment Number' , _sumo_pp()->text_domain ) ,
            'product_name'   => __( 'Product Name' , _sumo_pp()->text_domain ) ,
            'payment_plan'   => __( 'Payment Plan' , _sumo_pp()->text_domain ) ,
            'order_id'       => __( 'Order ID' , _sumo_pp()->text_domain ) ,
            'log'            => __( 'Log' , _sumo_pp()->text_domain ) ,
            'posted_on'      => __( 'Date' , _sumo_pp()->text_domain ) ,
                ) ;
        return $columns ;
    }

    /**
     * Display Cron Jobs columns.
     * @param array $existing_columns
     * @return array
     */
    public function output_cron_jobs_columns( $existing_columns ) {
        $columns = array (
            'cb'             => $existing_columns[ 'cb' ] ,
            'job_id'         => __( 'Job ID' , _sumo_pp()->text_domain ) ,
            'payment_number' => __( 'Payment Number' , _sumo_pp()->text_domain ) ,
            'job_name'       => __( 'Job Name' , _sumo_pp()->text_domain ) ,
            'next_run'       => __( 'Next Run' , _sumo_pp()->text_domain ) ,
            'args'           => __( 'Arguments' , _sumo_pp()->text_domain )
                ) ;
        return $columns ;
    }

    public function shop_order_columns( $columns ) {
        $columns[ _sumo_pp()->prefix . 'payment_info' ] = __( 'Payment Info' , _sumo_pp()->text_domain ) ;
        return $columns ;
    }

    /**
     * Display Payment Plan post entries.
     * @param string $column
     * @param int $post_id
     */
    public function output_payment_plans_datas( $column , $post_id ) {

        switch ( $column ) {
            case 'plan_name':
                echo '<a href="' . admin_url( "post.php?post={$post_id}&action=edit" ) . '">' . get_post( $post_id )->post_title . '</a>' ;
                break ;
            case 'plan_description':
                echo get_post_meta( $post_id , '_plan_description' , true ) ;
                break ;
        }
    }

    /**
     * Display Payments post entries.
     * @param string $column
     * @param int $post_id
     */
    public function output_payments_datas( $column , $post_id ) {

        switch ( $column ) {
            case 'payment_status':
                $payment_status = _sumo_pp_get_payment_status( $post_id ) ;
                printf( '<mark class="%s"/>%s</mark>' , $payment_status[ 'name' ] , esc_attr( $payment_status[ 'label' ] ) ) ;
                break ;
            case 'payment_number':
                $payment_number = get_post_meta( $post_id , '_payment_number' , true ) ;

                echo '<a href="' . admin_url( "post.php?post={$post_id}&action=edit" ) . '">#' . $payment_number . '</a>' ;
                break ;
            case 'product_name':
                echo _sumo_pp_get_formatted_payment_product_title( $post_id ) ;
                break ;
            case 'order_id':
                $order_id = get_post_meta( $post_id , '_initial_payment_order_id' , true ) ;
                echo '<a href="' . admin_url( "post.php?post={$order_id}&action=edit" ) . '">#' . $order_id . '</a>' ;
                break ;
            case 'buyer_email':
                echo get_post_meta( $post_id , '_customer_email' , true ) ;
                break ;
            case 'billing_name':
                if ( $order    = _sumo_pp_get_order( get_post_meta( $post_id , '_initial_payment_order_id' , true ) ) ) {
                    echo $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() ;
                } else {
                    echo 'N/A' ;
                }
                break ;
            case 'payment_type':
                echo ucfirst( str_replace( '-' , ' ' , get_post_meta( $post_id , '_payment_type' , true ) ) ) ;
                break ;
            case 'payment_plan':
                if ( 'payment-plans' === get_post_meta( $post_id , '_payment_type' , true ) ) {
                    $plan_id = get_post_meta( $post_id , '_plan_id' , true ) ;

                    echo '<a href="' . admin_url( "post.php?post={$plan_id}&action=edit" ) . '">' . get_post( $plan_id )->post_title . '</a>' ;
                } else {
                    echo '--' ;
                }
                break ;
            case 'remaining_installments':
                echo get_post_meta( $post_id , '_remaining_installments' , true ) ;
                break ;
            case 'remaining_payable_amount':
                echo wc_price( get_post_meta( $post_id , '_remaining_payable_amount' , true ) ) ;
                break ;
            case 'next_installment_amount':
                echo wc_price( get_post_meta( $post_id , '_next_installment_amount' , true ) ) ;
                break ;
            case 'payment_start_date':
                echo get_post_meta( $post_id , '_payment_start_date' , true ) ? _sumo_pp_get_date_to_display( get_post_meta( $post_id , '_payment_start_date' , true ) ) : '--' ;
                break ;
            case 'next_payment_date':
                $next_payment_date = get_post_meta( $post_id , '_next_payment_date' , true ) ;

                if ( $next_payment_date ) {
                    echo _sumo_pp_get_date_to_display( $next_payment_date ) ;
                } else {
                    echo '--' ;
                }
                break ;
            case 'payment_ending_date':
                if ( 'payment-plans' === get_post_meta( $post_id , '_payment_type' , true ) ) {
                    echo get_post_meta( $post_id , '_payment_end_date' , true ) ? _sumo_pp_get_date_to_display( get_post_meta( $post_id , '_payment_end_date' , true ) ) : '--' ;
                } else {
                    echo '--' ;
                }
                break ;
            case 'last_payment_date':
                echo get_post_meta( $post_id , '_last_payment_date' , true ) ? _sumo_pp_get_date_to_display( get_post_meta( $post_id , '_last_payment_date' , true ) ) : '--' ;
                break ;
        }
    }

    /**
     * Display Masterlog post entries.
     * @param string $column
     * @param int $post_id
     */
    public function output_masterlog_datas( $column , $post_id ) {

        switch ( $column ) {
            case 'status':
                $status = get_post_meta( $post_id , '_status' , true ) ;

                if ( in_array( $status , array ( 'success' , 'pending' ) ) ) {
                    printf( __( '<div style="background-color: #259e12;width:50px;height:20px;text-align:center;color:#ffffff;padding:5px;">%s</div>' ) , 'Success' ) ;
                } else {
                    printf( __( '<div style="background-color: #ef381c;width:50px;height:20px;text-align:center;color:#ffffff;padding:5px;">%s</div>' ) , 'Failure' ) ;
                }
                break ;
            case 'message':
                echo get_post_meta( $post_id , '_message' , true ) ;
                break ;
            case 'user_name':
                echo get_post_meta( $post_id , '_user_name' , true ) ;
                break ;
            case 'payment_number':
                $payment_id     = get_post_meta( $post_id , '_payment_id' , true ) ;
                $payment_number = get_post_meta( $post_id , '_payment_number' , true ) ;

                echo '<a href="' . admin_url( "post.php?post={$payment_id}&action=edit" ) . '">#' . $payment_number . '</a>' ;
                break ;
            case 'payment_plan':
                $payment_id = get_post_meta( $post_id , '_payment_id' , true ) ;

                if ( 'payment-plans' === get_post_meta( $payment_id , '_payment_type' , true ) ) {
                    $plan_id = get_post_meta( $payment_id , '_plan_id' , true ) ;

                    echo '<a href="' . admin_url( "post.php?post={$plan_id}&action=edit" ) . '">' . get_post( $plan_id )->post_title . '</a>' ;
                } else {
                    echo '--' ;
                }
                break ;
            case 'product_name':
                echo _sumo_pp_get_formatted_payment_product_title( get_post_meta( $post_id , '_payment_id' , true ) ) ;
                break ;
            case 'order_id':
                $payment_order_id = get_post_meta( $post_id , '_payment_order_id' , true ) ;

                echo '<a href="' . admin_url( "post.php?post={$payment_order_id}&action=edit" ) . '">#' . $payment_order_id . '</a>' ;
                break ;
            case 'log':
                echo get_post_meta( $post_id , '_log' , true ) ;
                break ;
            case 'posted_on':
                echo get_post_meta( $post_id , '_log_posted_on' , true ) ;
                break ;
        }
    }

    /**
     * Display Cron Scheduled jobs post entries.
     * @param string $column
     * @param int $post_id
     */
    public function output_cron_jobs_datas( $column , $post_id ) {
        $payment_id = absint( get_post_meta( $post_id , '_payment_id' , true ) ) ;
        $jobs       = get_post_meta( $post_id , '_scheduled_jobs' , true ) ;

        $job_name  = array () ;
        $next_run  = array () ;
        $arguments = array () ;

        if ( isset( $jobs[ $payment_id ] ) && is_array( $jobs[ $payment_id ] ) ) {
            foreach ( $jobs[ $payment_id ] as $_job_name => $args ) {
                if ( ! is_array( $args ) ) {
                    continue ;
                }

                $job_name[] = $_job_name ;

                $job_time = '' ;
                foreach ( $args as $job_timestamp => $job_args ) {
                    if ( ! is_numeric( $job_timestamp ) ) {
                        continue ;
                    }

                    $job_time .= _sumo_pp_get_date( $job_timestamp ) . nl2br( "\n[" . _sumo_pp_get_date_difference( $job_timestamp ) . "]\n\n" ) ;
                }
                $next_run[] = $job_time ;

                $arg = '' ;
                foreach ( $args as $job_timestamp => $job_args ) {
                    if ( ! is_array( $job_args ) ) {
                        continue ;
                    }
                    $arg .= '"' . implode( ', ' , $job_args ) . '",&nbsp;<br>' ;
                }
                if ( '' !== $arg ) {
                    $arguments[] = $arg ;
                }
            }
        }

        switch ( $column ) {
            case 'job_id':
                echo '#' . $post_id ;
                break ;
            case 'payment_number':
                $payment_number = get_post_meta( $payment_id , '_payment_number' , true ) ;

                echo '<a href="' . admin_url( "post.php?post={$payment_id}&action=edit" ) . '">#' . $payment_number . '</a>' ;
                break ;
            case 'job_name':
                echo $job_name ? implode( ',' . str_repeat( "</br>" , 4 ) , $job_name ) : 'None' ;
                break ;
            case 'next_run':
                echo $next_run ? '<b>*</b>' . implode( '<b>*</b> ' , $next_run ) : 'None' ;
                break ;
            case 'args':
                echo $arguments ? implode( str_repeat( "</br>" , 4 ) , $arguments ) : 'None' ;
                break ;
        }
    }

    /**
     * Output custom columns for shop order.
     * @param  string $column
     */
    public function render_shop_order_columns( $column , $post_id ) {

        switch ( $column ) {
            case _sumo_pp()->prefix . 'payment_info':
                if ( _sumo_pp_is_payment_order( $post_id ) ) {
                    $payment = _sumo_pp()->query->get( array (
                        'type'       => 'sumo_pp_payments' ,
                        'status'     => array_keys( _sumo_pp_get_payment_statuses() ) ,
                        'limit'      => 1 ,
                        'meta_query' => array (
                            'relation' => 'OR' ,
                            array (
                                'key'     => '_initial_payment_order_id' ,
                                'value'   => $post_id ,
                                'compare' => 'LIKE' ,
                            ) ,
                            array (
                                'key'     => '_balance_payable_order_id' ,
                                'value'   => $post_id ,
                                'compare' => 'LIKE' ,
                            ) ,
                        ) ,
                            ) ) ;

                    if ( $payment_id = (isset( $payment[ 0 ] ) && $payment[ 0 ] ? $payment[ 0 ] : 0 ) ) {
                        printf( __( 'This Order is linked with payment%s' , _sumo_pp()->text_domain ) , "<a href='" . admin_url( "post.php?post={$payment_id}&action=edit" ) . "'>#{$payment_id}</a>" ) ;
                    } else {
                        echo '--' ;
                    }
                } else {
                    echo '--' ;
                }
                break ;
        }
    }

    /**
     * Change title boxes in admin.
     * @param  string $text
     * @param  object $post
     * @return string
     */
    public function enter_title_here( $text , $post ) {
        switch ( $post->post_type ) {
            case 'sumo_payment_plans' :
                $text = __( 'Plan name' , _sumo_pp()->text_domain ) ;
                break ;
        }

        return $text ;
    }

    /**
     * Remove row actions from our CPT's
     * @global object $current_screen
     * @param array $actions
     * @param object $post
     * @return array
     */
    public function remove_row_actions( $actions , $post ) {
        global $current_screen ;

        if ( ! isset( $current_screen->post_type ) ) {
            return $actions ;
        }

        switch ( $current_screen->post_type ) {
            case 'sumo_pp_payments':
                unset( $actions[ 'inline hide-if-no-js' ] , $actions[ 'view' ] ) ;

                if ( _sumo_pp_payment_has_status( $post->ID , 'await_aprvl' ) ) {
                    $actions[ 'approve-payment' ] = sprintf( '<span class="edit"><a href="%s" aria-label="Approve">Approve</a></span>' , admin_url( "edit.php?post_type=sumo_pp_payments&payment_id={$post->ID}&action=approve&_sumo_pp_nonce=" . wp_create_nonce( "{$post->ID}" ) ) ) ;
                }
                break ;
            case 'sumo_payment_plans':
            case 'sumo_pp_masterlog':
            case 'sumo_pp_cron_jobs':
                unset( $actions[ 'inline hide-if-no-js' ] , $actions[ 'view' ] , $actions[ 'edit' ] ) ;
                break ;
        }

        return $actions ;
    }

    public function approve_payment() {
        if ( empty( $_GET[ '_sumo_pp_nonce' ] ) || empty( $_GET[ 'action' ] ) || empty( $_GET[ 'payment_id' ] ) || ! wp_verify_nonce( $_GET[ '_sumo_pp_nonce' ] , $_GET[ 'payment_id' ] ) ) {
            return ;
        }

        if ( 'approve' === $_GET[ 'action' ] ) {
            $initial_payment_order_id = absint( get_post_meta( $_GET[ 'payment_id' ] , '_initial_payment_order_id' , true ) ) ;

            if ( _sumo_pp_update_payment_status( $_GET[ 'payment_id' ] , 'in_progress' ) ) {

                _sumo_pp_update_scheduled_payments_date( $_GET[ 'payment_id' ] ) ;
                _sumo_pp_add_payment_note( sprintf( __( 'Initial payment of order#%s is paid. Payment is in progress' , _sumo_pp()->text_domain ) , $initial_payment_order_id ) , $_GET[ 'payment_id' ] , 'success' , __( 'Initial Payment Success' , _sumo_pp()->text_domain ) ) ;

                $payment_type      = get_post_meta( $_GET[ 'payment_id' ] , '_payment_type' , true ) ;
                $next_payment_date = _sumo_pp_get_next_payment_date( $_GET[ 'payment_id' ] ) ;

                add_post_meta( $_GET[ 'payment_id' ] , '_payment_start_date' , _sumo_pp_get_date() ) ;
                add_post_meta( $_GET[ 'payment_id' ] , '_last_payment_date' , _sumo_pp_get_date() ) ;
                add_post_meta( $_GET[ 'payment_id' ] , '_payment_end_date' , _sumo_pp_get_payment_end_date( $_GET[ 'payment_id' ] ) ) ;
                add_post_meta( $_GET[ 'payment_id' ] , '_next_payment_date' , $next_payment_date ) ;

                foreach ( _sumo_pp_get_order( $initial_payment_order_id )->get_item_meta( 'item' ) as $item_id => $item ) {
                    $product_id = $item[ 'variation_id' ] > 0 ? $item[ 'variation_id' ] : $item[ 'product_id' ] ;

                    if ( $product_id == get_post_meta( $_GET[ 'payment_id' ] , '_product_id' , true ) && isset( $item[ 'item_meta' ] ) && is_array( $item[ 'item_meta' ] ) ) {
                        foreach ( $item[ 'item_meta' ] as $key => $value ) {
                            $due_date_label = str_replace( ':' , '' , get_option( _sumo_pp()->prefix . 'next_payment_date_label' ) ) ;

                            if ( $key == $due_date_label ) {
                                wc_delete_order_item_meta( $item_id , $key , $value ) ;
                                wc_add_order_item_meta( $item_id , $key , _sumo_pp_get_date_to_display( $next_payment_date ) , true ) ;
                            }
                        }
                    }
                }

                if ( 'pay-in-deposit' === $payment_type ) {
                    $balance_payable_order_id = SUMO_PP_Payment_Order::create_balance_payable_order( $_GET[ 'payment_id' ] ) ;

                    if ( $next_payment_date && ($payment_cron = _sumo_pp_get_payment_cron( $_GET[ 'payment_id' ] ) ) ) {
                        if ( 'before' === get_post_meta( $_GET[ 'payment_id' ] , '_pay_balance_type' , true ) ) {
                            if ( $overdue_time_till = _sumo_pp_get_overdue_time_till( $next_payment_date ) ) {
                                $payment_cron->schedule_overdue_notify( $balance_payable_order_id , $next_payment_date , $overdue_time_till ) ;
                            } else {
                                $payment_cron->schedule_cancelled_notify( $balance_payable_order_id , $next_payment_date ) ;
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
                    if ( $payment_cron = _sumo_pp_get_payment_cron( $_GET[ 'payment_id' ] ) ) {
                        $payment_cron->schedule_balance_payable_order( $next_payment_date ) ;
                    }
                }

                _sumo_pp_send_payment_email( $_GET[ 'payment_id' ] , 'payment_schedule' , $initial_payment_order_id ) ;

                do_action( 'sumopaymentplans_payment_in_progress' , $_GET[ 'payment_id' ] , $initial_payment_order_id , 'initial-payment-order' ) ;
            }
            wp_safe_redirect( esc_url_raw( admin_url( 'edit.php?post_type=sumo_pp_payments' ) ) ) ;
            exit ;
        }
    }

    /**
     * Remove bulk actions from our CPT's
     * @param array $actions
     * @return array
     */
    public function remove_bulk_actions( $actions ) {
        unset( $actions[ 'edit' ] ) ;
        return $actions ;
    }

    /**
     * Sort our CPT's columns.
     * @param array $existing_columns
     * @return array
     */
    public function sort_columns( $existing_columns ) {
        global $current_screen ;

        if ( ! isset( $current_screen->post_type ) ) {
            return $existing_columns ;
        }

        $columns = array () ;
        switch ( $current_screen->post_type ) {
            case 'sumo_payment_plans':
                $columns = array (
                    'plan_name' => 'title' ,
                        ) ;
                break ;
            case 'sumo_pp_payments':
                $columns = array (
                    'payment_number'          => 'ID' ,
                    'payment_type'            => 'title' ,
                    'payment_plan'            => 'title' ,
                    'order_id'                => 'ID' ,
                    'remaining_installments'  => 'ID' ,
                    'next_installment_amount' => 'ID' ,
                    'buyer_email'             => 'title' ,
                    'billing_name'            => 'title' ,
                    'next_payment_date'       => 'date' ,
                    'last_payment_date'       => 'date' ,
                    'payment_ending_date'     => 'date' ,
                        ) ;
                break ;
            case 'sumo_pp_masterlog':
                $columns = array (
                    'payment_number' => 'ID' ,
                    'order_id'       => 'ID' ,
                    'posted_on'      => 'date' ,
                    'user_name'      => 'title' ,
                    'payment_plan'   => 'title' ,
                        ) ;
                break ;
            case 'sumo_pp_cron_jobs':
                $columns = array (
                    'job_id'         => 'ID' ,
                    'payment_number' => 'ID' ,
                        ) ;
                break ;
        }

        return wp_parse_args( $columns , $existing_columns ) ;
    }

    /**
     * Change the label when searching payments.
     *
     * @param mixed $query Current search query.
     * @return string
     */
    public function search_label( $query ) {
        global $pagenow , $typenow ;

        if ( 'edit.php' !== $pagenow || ! in_array( $typenow , array_keys( $this->custom_post_types ) ) || ! get_query_var( "{$typenow}_search" ) || ! isset( $_GET[ 's' ] ) ) { // WPCS: input var ok.
            return $query ;
        }

        return wc_clean( wp_unslash( $_GET[ 's' ] ) ) ; // WPCS: input var ok, sanitization ok.
    }

    /**
     * Query vars for custom searches.
     *
     * @param mixed $public_query_vars Array of query vars.
     * @return array
     */
    public function add_custom_query_var( $public_query_vars ) {
        return array_merge( $public_query_vars , array_map( function($type) {
                    return "{$type}_search" ;
                } , array_keys( $this->custom_post_types ) ) ) ;
    }

    /**
     * Search custom fields as well as content.
     *
     * @param WP_Query $wp Query object.
     */
    public function search_custom_fields( $wp ) {
        global $pagenow , $wpdb ;

        if ( 'edit.php' !== $pagenow || empty( $wp->query_vars[ 's' ] ) || ! in_array( $wp->query_vars[ 'post_type' ] , array_keys( $this->custom_post_types ) ) || ! isset( $_GET[ 's' ] ) ) { // WPCS: input var ok.
            return ;
        }

        $term     = str_replace( '#' , '' , wc_clean( wp_unslash( $_GET[ 's' ] ) ) ) ;
        $post_ids = array () ;

        switch ( $wp->query_vars[ 'post_type' ] ) {
            case 'sumo_payment_plans':
                $search_fields = array (
                    '_plan_description' ,
                        ) ;
                break ;
            case 'sumo_pp_payments':
                $search_fields = array (
                    '_payment_number' ,
                    '_product_id' ,
                    '_initial_payment_order_id' ,
                    '_customer_email' ,
                        ) ;
                break ;
            case 'sumo_pp_masterlog':
                $search_fields = array (
                    '_message' ,
                    '_user_name' ,
                    '_payment_id' ,
                    '_payment_number' ,
                    '_payment_order_id' ,
                    '_log' ,
                    '_log_posted_on' ,
                        ) ;
                break ;
            case 'sumo_pp_cron_jobs':
                $search_fields = array (
                    '_payment_id' ,
                        ) ;
                break ;
        }

        if ( is_numeric( $term ) ) {
            $post_ids[] = absint( $term ) ;
        }

        if ( ! empty( $search_fields ) ) {
            $post_ids = array_unique(
                    array_merge(
                            $post_ids , $wpdb->get_col(
                                    $wpdb->prepare(
                                            "SELECT DISTINCT p1.post_id FROM {$wpdb->postmeta} p1 WHERE p1.meta_value LIKE %s AND p1.meta_key IN ('" . implode( "','" , array_map( 'esc_sql' , $search_fields ) ) . "')" , // @codingStandardsIgnoreLine
                                                                                                                                                                                   '%' . $wpdb->esc_like( wc_clean( $term ) ) . '%'
                                    )
                            )
                    ) ) ;
        }

        if ( ! empty( $post_ids ) ) {
            // Remove "s" - we don't want to search payment name.
            unset( $wp->query_vars[ 's' ] ) ;

            // so we know we're doing this.
            $wp->query_vars[ "{$wp->query_vars[ 'post_type' ]}_search" ] = true ;

            // Search by found posts.
            $wp->query_vars[ 'post__in' ] = array_merge( $post_ids , array ( 0 ) ) ;
        }
    }

}

new SUMO_PP_Admin_Post_Types() ;

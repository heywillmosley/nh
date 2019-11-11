<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit ; // Exit if accessed directly
}

function _sumo_pp_payment_exists( $payment_id ) {
    $payment = get_post( $payment_id ) ;

    if ( $payment && 'sumo_pp_payments' === $payment->post_type ) {
        $payment_statuses = array_keys( _sumo_pp_get_payment_statuses() ) ;

        if ( in_array( $payment->post_status , $payment_statuses ) ) {
            return true ;
        }
    }
    return false ;
}

function _sumo_pp_get_payment_number( $payment_id ) {
    $payment_number = get_post_meta( $payment_id , '_payment_number' , true ) ;

    return absint( $payment_number ) ;
}

function _sumo_pp_get_formatted_payment_product_title( $payment_id , $args = array () ) {
    $product_id  = get_post_meta( $payment_id , '_product_id' , true ) ;
    $product_qty = get_post_meta( $payment_id , '_product_qty' , true ) ;
    $args        = wp_parse_args( $args , array (
        'tips'           => true ,
        'maybe_variable' => true ,
        'qty'            => true ,
            ) ) ;

    if ( 'order' === get_post_meta( $payment_id , '_product_type' , true ) ) {
        $order_items   = get_post_meta( $payment_id , '_order_items' , true ) ;
        $product_title = get_option( _sumo_pp()->prefix . 'order_payment_plan_label' ) ;
        $item_title    = $item_url      = array () ;

        foreach ( $order_items as $item_id => $item ) {
            if ( $args[ 'maybe_variable' ] && ($variable_item_id = wp_get_post_parent_id( $item_id ) ) ) {
                $item_title[] = _sumo_pp_get_product_title( $variable_item_id ) . ($args[ 'qty' ] ? "&nbsp;&nbsp;x{$item[ 'qty' ]}" : '') ;
            } else {
                $item_title[] = _sumo_pp_get_product_title( $item_id ) . ($args[ 'qty' ] ? "&nbsp;&nbsp;x{$item[ 'qty' ]}" : '') ;
            }
        }
        if ( $args[ 'tips' ] ) {
            $product_title = sprintf( __( '<a href="#" class="%s" data-tip="%s">%s</a>' ) , _sumo_pp()->prefix . 'tips' , implode( ',<br>' , $item_title ) , $product_title ) ;
        } else if ( $args[ 'qty' ] ) {
            $product_title .= ' --><br>' . implode( ',<br>' , $item_title ) ;
        }
    } else {
        if ( $args[ 'maybe_variable' ] && ($variable_product_id = wp_get_post_parent_id( $product_id )) ) {
            $product_title = _sumo_pp_get_product_title( $variable_product_id ) ;
            $product_url   = _sumo_pp_get_product_url( $variable_product_id ) ;
        } else {
            $product_title = _sumo_pp_get_product_title( $product_id ) ;
            $product_url   = _sumo_pp_get_product_url( $product_id ) ;
        }
        $maybe_add_qty = $args[ 'qty' ] ? "&nbsp;&nbsp;x{$product_qty}" : '' ;

        if ( $args[ 'tips' ] ) {
            $product_title = sprintf( __( '<a href="%s" class="%s" data-tip="%s%s">%s</a>' ) , $product_url , _sumo_pp()->prefix . 'tips' , $product_title , $maybe_add_qty , $product_title ) ;
        } else if ( $maybe_add_qty ) {
            $product_title .= $maybe_add_qty ;
        }
    }
    return $product_title ;
}

function _sumo_pp_payment_has_status( $payment_id , $status ) {
    $payment_status = _sumo_pp_get_payment_status( $payment_id ) ;

    if ( is_array( $status ) ) {
        return in_array( $payment_status[ 'name' ] , $status ) || in_array( str_replace( _sumo_pp()->prefix , '' , $payment_status[ 'name' ] ) , $status ) ;
    }
    return $status === $payment_status[ 'name' ] || $status === str_replace( _sumo_pp()->prefix , '' , $payment_status[ 'name' ] ) ;
}

function _sumo_pp_payment_has_next_installment( $payment_id ) {
    $remaining_installments = _sumo_pp_get_remaining_installments( $payment_id ) ;

    return $remaining_installments > 0 ? true : false ;
}

function _sumo_pp_get_payment_status( $payment_id = 0 ) {

    $payment_statuses = _sumo_pp_get_payment_statuses() ;
    $payment_status   = _sumo_pp()->prefix . 'pending' ;

    if ( _sumo_pp_payment_exists( $payment_id ) ) {
        $payment_status = get_post( $payment_id )->post_status ;
    }

    return array (
        'label' => isset( $payment_statuses[ $payment_status ] ) ? $payment_statuses[ $payment_status ] : '' ,
        'name'  => $payment_status ,
            ) ;
}

function _sumo_pp_update_payment_status( $payment_id , $payment_status ) {

    $post_data = array (
        'post_status'       => _sumo_pp()->prefix . $payment_status ,
        'post_modified'     => _sumo_pp_get_date() ,
        'post_modified_gmt' => _sumo_pp_get_date() ,
            ) ;

    if ( doing_action( 'save_post' ) ) {
        $GLOBALS[ 'wpdb' ]->update( $GLOBALS[ 'wpdb' ]->posts , $post_data , array ( 'ID' => $payment_id ) ) ;
        clean_post_cache( $payment_id ) ;
    } else {
        wp_update_post( array_merge( array ( 'ID' => $payment_id ) , $post_data ) ) ;
    }
    return true ;
}

/**
 * Get Payment serial number for the New payment entry.
 * @return int
 */
function _sumo_pp_get_payment_serial_number() {
    $last_serial_no = absint( get_option( _sumo_pp()->prefix . 'payment_serial_number' , '1' ) ) ;
    $new_serial_no  = $last_serial_no ? 1 + $last_serial_no : 1 ;

    update_option( _sumo_pp()->prefix . 'payment_serial_number' , $new_serial_no ) ;

    return $new_serial_no ;
}

function _sumo_pp_get_payment_end_date( $payment_id ) {
    $payment_type = get_post_meta( $payment_id , '_payment_type' , true ) ;

    if ( 'payment-plans' === $payment_type ) {
        $scheduled_payments_date = get_post_meta( $payment_id , '_scheduled_payments_date' , true ) ;
        $scheduled_payments_date = is_array( $scheduled_payments_date ) ? $scheduled_payments_date : array () ;
        $payment_end_date        = end( $scheduled_payments_date ) ;

        return $payment_end_date ? _sumo_pp_get_date( $payment_end_date ) : '' ;
    }
    return '' ;
}

function _sumo_pp_get_next_payment_date( $payment_id , $next_installment = false ) {
    $payment_type      = get_post_meta( $payment_id , '_payment_type' , true ) ;
    $next_payment_time = 0 ;

    if ( 'payment-plans' === $payment_type ) {
        $scheduled_payments_date   = get_post_meta( $payment_id , '_scheduled_payments_date' , true ) ;
        $balance_paid_orders_count = sizeof( _sumo_pp_get_balance_paid_orders( $payment_id ) ) ;

        if ( $next_installment ) {
            $balance_paid_orders_count += 1 ;
        }
        if ( isset( $scheduled_payments_date[ $balance_paid_orders_count ] ) && $scheduled_payments_date[ $balance_paid_orders_count ] > 0 ) {
            $next_payment_time = $scheduled_payments_date[ $balance_paid_orders_count ] ;
        }
    } else if ( 'pay-in-deposit' === $payment_type ) {
        if ( 'before' === get_post_meta( $payment_id , '_pay_balance_type' , true ) ) {
            $next_payment_time = _sumo_pp_get_timestamp( get_post_meta( $payment_id , '_pay_balance_before' , true ) ) ;
        } else {
            if ( version_compare( get_post_meta( $payment_id , '_version' , true ) , SUMO_PP_PLUGIN_VERSION , '<' ) ) {
                $pay_balance_after = absint( get_post_meta( $payment_id , '_balance_payment_due' , true ) ) ; //in days
            } else {
                $pay_balance_after = absint( get_post_meta( $payment_id , '_pay_balance_after' , true ) ) ; //in days
            }
            $next_payment_time = $pay_balance_after > 0 ? _sumo_pp_get_timestamp( "+{$pay_balance_after} days" ) : 0 ;
        }
    }
    return $next_payment_time ? _sumo_pp_get_date( $next_payment_time ) : '' ;
}

function _sumo_pp_update_scheduled_payments_date( $payment_id ) {
    $payment_type = get_post_meta( $payment_id , '_payment_type' , true ) ;

    if ( 'payment-plans' === $payment_type ) {
        $payment_schedules = get_post_meta( $payment_id , '_payment_schedules' , true ) ;
        $from_time         = 0 ;

        if ( is_array( $payment_schedules ) ) {
            foreach ( $payment_schedules as $schedule ) {
                if ( ! isset( $schedule[ 'scheduled_payment' ] ) ) {
                    continue ;
                }

                $scheduled_payment_cycle   = _sumo_pp_get_payment_cycle_in_days( $schedule[ 'scheduled_duration_length' ] , $schedule[ 'scheduled_period' ] ) ;
                $scheduled_payments_date[] = $from_time                 = _sumo_pp_get_timestamp( "+{$scheduled_payment_cycle} days" , $from_time ) ;
            }

            add_post_meta( $payment_id , '_scheduled_payments_date' , $scheduled_payments_date ) ;
        }
    }
}

function _sumo_pp_update_actual_payments_date( $payment_id ) {
    $actual_payments_date = get_post_meta( $payment_id , '_actual_payments_date' , true ) ;
    $actual_payments_date = is_array( $actual_payments_date ) ? $actual_payments_date : array () ;

    if ( ! empty( $actual_payments_date ) ) {
        update_post_meta( $payment_id , '_actual_payments_date' , array_merge( $actual_payments_date , array ( _sumo_pp_get_timestamp() ) ) ) ;
    } else {
        add_post_meta( $payment_id , '_actual_payments_date' , array ( _sumo_pp_get_timestamp() ) ) ;
    }
}

function _sumo_pp_is_balance_payable_order_exists( $payment_id ) {
    $balance_payable_order = _sumo_pp_get_order( get_post_meta( $payment_id , '_balance_payable_order_id' , true ) ) ;

    if ( $balance_payable_order && $balance_payable_order->has_status( 'pending' ) ) {
        return true ;
    }
    return false ;
}

function _sumo_pp_get_balance_paid_orders( $payment_id ) {
    $balance_paid_orders = get_post_meta( $payment_id , '_balance_paid_orders' , true ) ;

    return is_array( $balance_paid_orders ) ? $balance_paid_orders : array () ;
}

function _sumo_pp_get_next_installment_amount( $payment_id , $next_installment = false ) {
    $payment_type              = get_post_meta( $payment_id , '_payment_type' , true ) ;
    $product_qty               = absint( get_post_meta( $payment_id , '_product_qty' , true ) ) ;
    $product_amount            = floatval( get_post_meta( $payment_id , '_product_price' , true ) ) * $product_qty ;
    $balance_paid_orders_count = sizeof( _sumo_pp_get_balance_paid_orders( $payment_id ) ) ;

    if ( 'payment-plans' === $payment_type ) {
        $payment_schedules = get_post_meta( $payment_id , '_payment_schedules' , true ) ;

        if ( $next_installment ) {
            $balance_paid_orders_count += 1 ;
        }

        if ( isset( $payment_schedules[ $balance_paid_orders_count ][ 'scheduled_payment' ] ) ) {
            $payment_amount = floatval( $payment_schedules[ $balance_paid_orders_count ][ 'scheduled_payment' ] ) ;

            if ( 'fixed-price' === get_post_meta( $payment_id , '_plan_price_type' , true ) ) {
                $next_installment_amount = $payment_amount * $product_qty ;
            } else {
                $next_installment_amount = ($product_amount * $payment_amount) / 100 ;
            }
            return $next_installment_amount ;
        }
    } else if ( 'pay-in-deposit' === $payment_type ) {
        $deposited_amount = floatval( get_post_meta( $payment_id , '_deposited_amount' , true ) ) * $product_qty ;

        if ( 0 === $balance_paid_orders_count ) {
            return max( $deposited_amount , $product_amount ) - min( $deposited_amount , $product_amount ) ;
        }
    }
    return 0 ;
}

function _sumo_pp_get_remaining_installments( $payment_id ) {
    $payment_type              = get_post_meta( $payment_id , '_payment_type' , true ) ;
    $balance_paid_orders_count = sizeof( _sumo_pp_get_balance_paid_orders( $payment_id ) ) ;

    if ( 'payment-plans' === $payment_type ) {
        $scheduled_payments_date        = get_post_meta( $payment_id , '_scheduled_payments_date' , true ) ;
        $scheduled_payments_dates_count = sizeof( is_array( $scheduled_payments_date ) ? $scheduled_payments_date : array ()  ) ;
        $remaining_installments         = $scheduled_payments_dates_count - $balance_paid_orders_count ;

        return $remaining_installments ? $remaining_installments : 0 ;
    } else if ( 'pay-in-deposit' === $payment_type ) {
        if ( 0 === $balance_paid_orders_count ) {
            return 1 ;
        }
    }
    return 0 ;
}

function _sumo_pp_get_remaining_payable_amount( $payment_id , $next_installment = false ) {
    $payment_type              = get_post_meta( $payment_id , '_payment_type' , true ) ;
    $product_qty               = absint( get_post_meta( $payment_id , '_product_qty' , true ) ) ;
    $product_amount            = floatval( get_post_meta( $payment_id , '_product_price' , true ) ) * $product_qty ;
    $balance_paid_orders_count = sizeof( _sumo_pp_get_balance_paid_orders( $payment_id ) ) ;
    $remaining_payable_amount  = 0 ;

    if ( 'payment-plans' === $payment_type ) {
        $payment_schedules = get_post_meta( $payment_id , '_payment_schedules' , true ) ;
        $payment_schedules = is_array( $payment_schedules ) ? $payment_schedules : array () ;

        if ( $next_installment ) {
            $balance_paid_orders_count += 1 ;
        }

        foreach ( $payment_schedules as $installment => $schedule ) {
            if ( ! isset( $schedule[ 'scheduled_payment' ] ) ) {
                continue ;
            }

            //Since $installment starts from 0 we have to do like this way
            if ( $balance_paid_orders_count <= $installment ) {
                if ( 'fixed-price' === get_post_meta( $payment_id , '_plan_price_type' , true ) ) {
                    $remaining_payable_amount += ($product_qty * floatval( $schedule[ 'scheduled_payment' ] )) ;
                } else {
                    $remaining_payable_amount += ($product_amount * floatval( $schedule[ 'scheduled_payment' ] )) / 100 ;
                }
            }
        }
    } else if ( 'pay-in-deposit' === $payment_type ) {
        $deposited_amount = floatval( get_post_meta( $payment_id , '_deposited_amount' , true ) ) * $product_qty ;

        if ( 0 === $balance_paid_orders_count && ! $next_installment ) {
            $remaining_payable_amount = max( $deposited_amount , $product_amount ) - min( $deposited_amount , $product_amount ) ;
        }
    }
    return $remaining_payable_amount ;
}

function _sumo_pp_get_total_payable_amount( $payment_id ) {
    $payment_type         = get_post_meta( $payment_id , '_payment_type' , true ) ;
    $product_qty          = absint( get_post_meta( $payment_id , '_product_qty' , true ) ) ;
    $product_price        = floatval( get_post_meta( $payment_id , '_product_price' , true ) ) ;
    $total_payable_amount = 0 ;

    if ( 'payment-plans' === $payment_type ) {
        $payment_schedules = get_post_meta( $payment_id , '_payment_schedules' , true ) ;
        $initial_payment   = floatval( get_post_meta( $payment_id , '_initial_payment' , true ) ) ;

        if ( 'fixed-price' === get_post_meta( $payment_id , '_plan_price_type' , true ) ) {
            $total_payable_amount = $initial_payment * $product_qty ;
        } else {
            $product_amount       = $product_price * $product_qty ;
            $total_payable_amount = ($initial_payment * $product_amount) / 100 ;
        }

        if ( is_array( $payment_schedules ) ) {
            foreach ( $payment_schedules as $schedule ) {
                if ( ! isset( $schedule[ 'scheduled_payment' ] ) ) {
                    continue ;
                }

                if ( 'fixed-price' === get_post_meta( $payment_id , '_plan_price_type' , true ) ) {
                    $total_payable_amount += (floatval( $schedule[ 'scheduled_payment' ] ) * $product_qty) ;
                } else {
                    $total_payable_amount += (floatval( $schedule[ 'scheduled_payment' ] ) * $product_amount) / 100 ;
                }
            }
        }
    } else if ( 'pay-in-deposit' === $payment_type ) {
        $total_payable_amount = $product_price * $product_qty ;
    }
    return $total_payable_amount ;
}

function _sumo_pp_update_as_paid_order( $payment_id , $paid_order_id ) {
    $balance_paid_orders = _sumo_pp_get_balance_paid_orders( $payment_id ) ;

    if ( ! empty( $balance_paid_orders ) ) {
        update_post_meta( $payment_id , '_balance_paid_orders' , array_merge( $balance_paid_orders , array ( $paid_order_id ) ) ) ;
    } else {
        add_post_meta( $payment_id , '_balance_paid_orders' , array ( $paid_order_id ) ) ;
    }

    delete_post_meta( $payment_id , '_balance_payable_order_id' ) ;
}

/**
 * Add Payment Note.
 * @param string $note
 * @param int $payment_id The Payment post ID
 * @param string $comment_status may be used for updating in Masterlog
 * @param string $comment_message may be used for updating in Masterlog
 * @return int|false The new comment's ID on success, false on failure.
 */
function _sumo_pp_add_payment_note( $note , $payment_id , $comment_status , $comment_message ) {
    if ( '' === $note || ! $payment_id ) {
        return false ;
    }

    $user                 = get_post_meta( $payment_id , '_get_customer' , true ) ;
    $comment_author       = '' ;
    $comment_author_email = '' ;

    if ( is_object( $user ) ) {
        $comment_author_email = $user->user_email ;
        $comment_author       = $user->display_name ;
    }

    $comment_id = wp_insert_comment( array (
        'comment_post_ID'      => $payment_id ,
        'comment_author'       => $comment_author ,
        'comment_author_email' => $comment_author_email ,
        'comment_author_url'   => '' ,
        'comment_content'      => $note ,
        'comment_type'         => 'payment_note' ,
        'comment_agent'        => 'SUMO-Payment-Plans' ,
        'comment_parent'       => 0 ,
        'comment_approved'     => 1 ,
        'comment_date'         => _sumo_pp_get_date() ,
        'comment_meta'         => array (
            'comment_message' => $comment_message ,
            'comment_status'  => $comment_status ,
        ) ,
            ) ) ;

    if ( $comment = get_comment( $comment_id ) ) {
        //Insert each comment in Masterlog
        $log_id = wp_insert_post( array (
            'post_status'   => 'publish' ,
            'post_type'     => 'sumo_pp_masterlog' ,
            'post_date'     => _sumo_pp_get_date() ,
            'post_date_gmt' => _sumo_pp_get_date() ,
            'post_author'   => 1 ,
            'post_title'    => __( 'Master Log' , _sumo_pp()->text_domain ) ,
                ) , true ) ;

        if ( ! is_wp_error( $log_id ) ) {
            add_post_meta( $log_id , '_payment_id' , $comment->comment_post_ID ) ;
            add_post_meta( $log_id , '_payment_number' , _sumo_pp_get_payment_number( $comment->comment_post_ID ) ) ;
            add_post_meta( $log_id , '_payment_order_id' , get_post_meta( $comment->comment_post_ID , '_initial_payment_order_id' , true ) ) ;
            add_post_meta( $log_id , '_product_id' , get_post_meta( $comment->comment_post_ID , '_product_id' , true ) ) ;
            add_post_meta( $log_id , '_user_name' , $comment->comment_author ) ;
            add_post_meta( $log_id , '_log_posted_on' , $comment->comment_date ) ;
            add_post_meta( $log_id , '_status' , $comment_status ) ;
            add_post_meta( $log_id , '_message' , $comment_message ) ;
            add_post_meta( $log_id , '_log' , $comment->comment_content ) ;
        }
    }
    return $comment_id ;
}

/**
 * Get Payment URL End Point.
 * @param int $payment_id The Payment post ID
 * @return string
 */
function _sumo_pp_get_payment_endpoint_url( $payment_id ) {
    if ( _sumo_pp_is_wc_version( '<' , '2.6' ) ) {
        $payment_endpoint = esc_url_raw( add_query_arg( array ( 'q' => 'sumo-pp-view-payment' , 'payment-id' => $payment_id ) ) ) ;
    } else {
        $payment_endpoint = wc_get_endpoint_url( 'sumo-pp-view-payment' , $payment_id , wc_get_page_permalink( 'myaccount' ) ) ;
    }
    return $payment_endpoint ;
}

/**
 * Get an payment note.
 *
 * @param  int|WP_Comment $comment Note ID.
 * @return stdClass|null  Object with payment note details or null when does not exists.
 */
function _sumo_pp_get_payment_note( $comment ) {
    if ( is_numeric( $comment ) ) {
        $comment = get_comment( $comment ) ;
    }

    if ( ! is_a( $comment , 'WP_Comment' ) ) {
        return null ;
    }

    return ( object ) array (
                'id'           => absint( $comment->comment_ID ) ,
                'date_created' => $comment->comment_date ,
                'content'      => $comment->comment_content ,
                'added_by'     => $comment->comment_author ,
                'meta'         => get_comment_meta( $comment->comment_ID ) ,
            ) ;
}

/**
 * Get payment notes.
 *
 * @param  array $args Query arguments
 * @return stdClass[]  Array of stdClass objects with payment notes details.
 */
function _sumo_pp_get_payment_notes( $args = array () ) {
    $key_mapping = array (
        'payment_id' => 'post_id' ,
        'limit'      => 'number' ,
            ) ;

    foreach ( $key_mapping as $query_key => $db_key ) {
        if ( isset( $args[ $query_key ] ) ) {
            $args[ $db_key ] = $args[ $query_key ] ;
            unset( $args[ $query_key ] ) ;
        }
    }

    $args[ 'orderby' ] = 'comment_ID' ;
    $args[ 'type' ]    = 'payment_note' ;
    $args[ 'status' ]  = 'approve' ;

    // Does not support 'count' or 'fields'.
    unset( $args[ 'count' ] , $args[ 'fields' ] ) ;

    remove_filter( 'comments_clauses' , array ( 'SUMO_PP_Comments' , 'exclude_payment_comments' ) , 10 , 1 ) ;

    $notes = get_comments( $args ) ;

    add_filter( 'comments_clauses' , array ( 'SUMO_PP_Comments' , 'exclude_payment_comments' ) , 10 , 1 ) ;

    return array_filter( array_map( '_sumo_pp_get_payment_note' , $notes ) ) ;
}

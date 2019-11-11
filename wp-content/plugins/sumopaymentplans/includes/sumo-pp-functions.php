<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit ; // Exit if accessed directly
}

function _sumo_pp_get_order( $order ) {
    $order = new SUMO_PP_Order( $order ) ;

    if ( $order->order ) {
        return $order ;
    }
    return false ;
}

function _sumo_pp_get_payment_cron( $payment_id ) {

    if ( _sumo_pp_payment_exists( $payment_id ) ) {
        return new SUMO_PP_Payment_Cron_Job( $payment_id ) ;
    }
    return false ;
}

function _sumo_pp_get_payment_statuses() {

    $payment_statuses = array (
        _sumo_pp()->prefix . 'pending'     => __( 'Pending' , _sumo_pp()->text_domain ) ,
        _sumo_pp()->prefix . 'await_aprvl' => __( 'Awaiting Admin Approval' , _sumo_pp()->text_domain ) ,
        _sumo_pp()->prefix . 'in_progress' => __( 'In Progress' , _sumo_pp()->text_domain ) ,
        _sumo_pp()->prefix . 'completed'   => __( 'Completed' , _sumo_pp()->text_domain ) ,
        _sumo_pp()->prefix . 'overdue'     => __( 'Overdue' , _sumo_pp()->text_domain ) ,
        _sumo_pp()->prefix . 'failed'      => __( 'Failed' , _sumo_pp()->text_domain ) ,
        _sumo_pp()->prefix . 'cancelled'   => __( 'Cancelled' , _sumo_pp()->text_domain ) ,
            ) ;

    return $payment_statuses ;
}

/**
 * Send Email.
 * @param int $post_id The Payment post ID || Order post ID
 * @param string $template_id The Mail Template ID
 * @param int $balance_payable_order_id The Order post ID
 * @param boolean $manual True, may be Admin has manually send the email
 */
function _sumo_pp_send_payment_email( $post_id , $template_id , $balance_payable_order_id , $manual = false ) {
    //Load Mailer.
    $mailer = WC()->mailer() ;
    $mails  = $mailer->get_emails() ;

    if ( empty( $mails ) || empty( $template_id ) || empty( $post_id ) || ! is_numeric( $post_id ) ) {
        return ;
    }

    $template_id = _sumo_pp()->prefix . $template_id ;
    $order_id    = is_numeric( $balance_payable_order_id ) && $balance_payable_order_id ? $balance_payable_order_id : '' ;

    //Check whether the $post_id is Order post ID.
    $is_order_post = false ;
    if ( $order         = _sumo_pp_get_order( $post_id ) ) {
        $is_order_post = true ;
        $order_id      = $post_id ;
    }

    if ( empty( $order_id ) || ! is_numeric( $order_id ) ) {
        return ;
    }

    foreach ( $mails as $mail ) {
        if ( ! in_array( $mail->id , array ( $template_id ) ) ) {
            continue ;
        }

        $template = ucwords( str_replace( '-' , ' ' , str_replace( '_' , ' ' , $mail->name ) ) ) ;

        if ( $is_order_post ) {
            $mail->trigger( $order_id ) ;
            break ;
        }

        //Trigger mailer.
        $mail_sent_to_admin = false ;
        $admin_email        = get_option( 'admin_email' ) ;

        if ( isset( $mail->mail_to_admin ) ? $mail->mail_to_admin : false ) {
            $mail_sent_to_admin = $mail->trigger( $order_id , $post_id , $admin_email ) ;
        }

        $receiver_email = get_post_meta( $post_id , '_customer_email' , true ) ;
        $mail_sent      = $mail->trigger( $order_id , $post_id , $receiver_email ) ;

        if ( $mail_sent ) {
            $text = $manual ? ' ' : sprintf( __( ' for an Order #%s ' , _sumo_pp()->text_domain ) , $order_id ) ;

            if ( $mail_sent_to_admin ) {
                $note = sprintf( __( '%s email is created%sand has been sent to %s and %s.' , _sumo_pp()->text_domain ) , $template , $text , $receiver_email , $admin_email ) ;
            } else {
                $note = sprintf( __( '%s email is created%sand has been sent to %s.' , _sumo_pp()->text_domain ) , $template , $text , $receiver_email ) ;
            }

            _sumo_pp_add_payment_note( $note , $post_id , 'success' , sprintf( __( '%s Email Sent' , _sumo_pp()->text_domain ) , $template ) ) ;
        } else if ( $mail_sent_to_admin ) {
            $text = $manual ? ' ' : sprintf( __( ' for an Order #%s ' , _sumo_pp()->text_domain ) , $order_id ) ;
            $note = sprintf( __( 'Payment %s email is created%sand has been sent to %s.' , _sumo_pp()->text_domain ) , $template , $text , $admin_email ) ;

            _sumo_pp_add_payment_note( $note , $post_id , 'success' , sprintf( __( '%s Email Sent' , _sumo_pp()->text_domain ) , $template ) ) ;
        }
    }
    return true ;
}

/**
 * Get date. Format date/time as GMT/UTC
 * If parameters nothing is given then it returns the current date in Y-m-d H:i:s format.
 * 
 * @param int|string $time should be Date/Timestamp.
 * @param int $base_time
 * @param boolean $exclude_hh_mm_ss
 * @param string $format
 * @return string
 */
function _sumo_pp_get_date( $time = 0 , $base_time = 0 , $exclude_hh_mm_ss = false , $format = 'Y-m-d' ) {
    $timestamp = time() ;

    if ( is_numeric( $time ) && $time ) {
        $timestamp = $time ;
    } else if ( is_string( $time ) && $time ) {
        $timestamp = strtotime( $time ) ;

        if ( is_numeric( $base_time ) && $base_time ) {
            $timestamp = strtotime( $time , $base_time ) ;
        }
    }

    if ( ! $format ) {
        $format = 'Y-m-d' ;
    }

    if ( $exclude_hh_mm_ss ) {
        return gmdate( "$format" , $timestamp ) ;
    }

    return gmdate( "{$format} H:i:s" , $timestamp ) ;
}

/**
 * Get Timestamp. Format date/time as GMT/UTC
 * If parameters nothing is given then it returns the current timestamp.
 * 
 * @param int|string $date should be Date/Timestamp 
 * @param int $base_time
 * @param boolean $exclude_hh_mm_ss
 * @return int
 */
function _sumo_pp_get_timestamp( $date = '' , $base_time = 0 , $exclude_hh_mm_ss = false ) {
    $formatted_date = _sumo_pp_get_date( $date , $base_time , $exclude_hh_mm_ss ) ;

    return strtotime( "{$formatted_date} UTC" ) ;
}

/**
 * Get formatted date for display purpose.
 * @param int|string $date
 * @return string
 */
function _sumo_pp_get_date_to_display( $date ) {

    $date           = _sumo_pp_get_date( $date ) ;
    $date_format    = 'd-m-Y' ;
    $time_format    = 'H:i:s' ;
    $wp_date_format = '' !== get_option( 'date_format' ) ? get_option( 'date_format' ) : 'F j, Y' ;
    $wp_time_format = '' !== get_option( 'time_format' ) ? get_option( 'time_format' ) : 'g:i a' ;

    //may be it is Admin Page.
    if ( is_admin() && 'sumo_pp_payments' === get_post_type() ) {
        if ( ! isset( $_GET[ 'action' ] ) ) {
            $date_format = 'd-m-Y' ;
            $time_format = '' ;
        }
    } else {
        //may be it is Frontend page.
        $date_format = $wp_date_format ;
        $time_format = '' ;

        if ( is_account_page() ) {
            $time_format = $wp_time_format ;
        }
    }

    if ( '' === $time_format ) {
        return date_i18n( "{$date_format}" , strtotime( $date ) ) ;
    }
    return date_i18n( "{$date_format} {$time_format}" , strtotime( $date ) ) ;
}

/**
 * Format the Date difference from Future date to Curent date.
 * @param int|string $future_date
 * @return string
 */
function _sumo_pp_get_date_difference( $future_date = null ) {
    if ( ! $future_date ) {
        return '' ;
    }

    $now = new DateTime() ;

    if ( is_numeric( $future_date ) && $future_date <= _sumo_pp_get_timestamp() ) {
        $interval    = abs( wp_next_scheduled( 'sumopaymentplans_cron_interval' ) - _sumo_pp_get_timestamp() ) ;
        $future_date = wp_next_scheduled( 'sumopaymentplans_cron_interval' ) ;

        //Elapse Time
        if ( $interval < 2 || ($interval > 290 && $interval <= 300) ) {
            return '<b>now</b>' ;
        }
    }

    if ( is_string( $future_date ) ) {
        $future_date = new DateTime( $future_date ) ;
    } elseif ( is_numeric( $future_date ) ) {
        $future_date = new DateTime( date( 'Y-m-d H:i:s' , $future_date ) ) ;
    }

    if ( $future_date ) {
        $interval = $future_date->diff( $now ) ;

        return $interval->format( '<b>%a</b> day(s), <b>%H</b> hour(s), <b>%I</b> minute(s), <b>%S</b> second(s)' ) ;
    }
    return 'now' ;
}

/**
 * Get multiple reminder intervals
 * @param string Mail template ID
 * @return array
 */
function _sumo_pp_get_reminder_intervals( $template_id ) {
    $intervals = '' ;
    $prefix    = _sumo_pp()->prefix ;

    switch ( $template_id ) {
        case 'payment_plan_invoice':
        case 'deposit_balance_payment_invoice':
            $intervals = get_option( "{$prefix}notify_invoice_before" , '3,2,1' ) ;
            break ;
        case 'payment_plan_overdue':
        case 'deposit_balance_payment_overdue':
            $intervals = get_option( "{$prefix}notify_overdue_before" , '1' ) ;
            break ;
    }
    return array_map( 'absint' , explode( ',' , $intervals ) ) ;
}

function _sumo_pp_get_overdue_time_till( $from = 0 ) {
    $specified_overdue_days = absint( get_option( _sumo_pp()->prefix . 'specified_overdue_days' , '0' ) ) ;

    if ( $specified_overdue_days > 0 ) {
        return _sumo_pp_get_timestamp( "+{$specified_overdue_days} days" , _sumo_pp_get_timestamp( $from ) ) ;
    }
    return 0 ;
}

function _sumo_pp_get_payment_plan_names( $args = array () ) {
    $plan_names    = array () ;
    $payment_plans = _sumo_pp()->query->get( wp_parse_args( $args , array (
        'type'   => 'sumo_payment_plans' ,
        'status' => 'publish' ,
        'return' => 'posts' ,
            ) ) ) ;

    if ( $payment_plans ) {
        foreach ( $payment_plans as $plan ) {
            $plan_names[ $plan->ID ] = $plan->post_title ;
        }
    }
    return $plan_names ;
}

function _sumo_pp_get_period_options() {

    $period_options = array (
        'days'   => __( 'Day(s)' , _sumo_pp()->text_domain ) ,
        'weeks'  => __( 'Week(s)' , _sumo_pp()->text_domain ) ,
        'months' => __( 'Month(s)' , _sumo_pp()->text_domain ) ,
        'years'  => __( 'Year(s)' , _sumo_pp()->text_domain ) ,
            ) ;
    return $period_options ;
}

function _sumo_pp_get_posts( $args = array () ) {
    return _sumo_pp()->query->get( $args ) ;
}

function _sumo_pp_get_active_payment_gateways() {
    $payment_gateways   = array () ;
    $available_gateways = WC()->payment_gateways->get_available_payment_gateways() ;

    foreach ( $available_gateways as $key => $value ) {
        $payment_gateways[ $key ] = $value->title ;
    }
    return $payment_gateways ;
}

/**
 * Get WP User roles
 * @global object $wp_roles
 * @param bool $include_guest
 * @return array
 */
function _sumo_pp_get_user_roles( $include_guest = false ) {
    global $wp_roles ;

    $user_role_key  = array () ;
    $user_role_name = array () ;

    foreach ( $wp_roles->roles as $_user_role_key => $user_role ) {
        $user_role_key[]  = $_user_role_key ;
        $user_role_name[] = $user_role[ 'name' ] ;
    }
    $user_roles = array_combine( ( array ) $user_role_key , ( array ) $user_role_name ) ;

    if ( $include_guest ) {
        $user_roles = array_merge( $user_roles , array ( 'guest' => 'Guest' ) ) ;
    }

    return $user_roles ;
}

function _sumo_pp_get_product_categories() {
    $categories   = array () ;
    $categoryid   = array () ;
    $categoryname = array () ;

    $listcategories = get_terms( 'product_cat' ) ;

    if ( is_array( $listcategories ) ) {
        foreach ( $listcategories as $category ) {
            $categoryname[] = $category->name ;
            $categoryid[]   = $category->term_id ;
        }
    }

    if ( $categoryid && $categoryname ) {
        $categories = array_combine( ( array ) $categoryid , ( array ) $categoryname ) ;
    }
    return $categories ;
}

/**
 * Get payment interval cycle in days.
 * @return int
 */
function _sumo_pp_get_payment_cycle_in_days( $payment_length = null , $payment_period = null , $next_payment_date = null ) {

    if ( ! is_null( $next_payment_date ) ) {
        $current_time      = _sumo_pp_get_timestamp() ;
        $next_payment_time = _sumo_pp_get_timestamp( $next_payment_date ) ;
        $payment_cycle     = absint( $next_payment_time - $current_time ) ;
    } else {
        $payment_length = absint( $payment_length ) ;

        switch ( $payment_period ) {
            case 'years':
                $payment_cycle = 31556926 * $payment_length ;
                break ;
            case 'months':
                $payment_cycle = 2629743 * $payment_length ;
                break ;
            case 'weeks':
                $payment_cycle = 604800 * $payment_length ;
                break ;
            default :
                $payment_cycle = 86400 * $payment_length ;
                break ;
        }
    }
    return ceil( $payment_cycle / 86400 ) ;
}

/**
 * Get balance payable order from Pay for Order page
 * @global object $wp
 * @return int
 */
function _sumo_pp_get_balance_payable_order_in_pay_for_order_page() {
    global $wp ;

    if ( ! isset( $_GET[ 'pay_for_order' ] ) || ! isset( $_GET[ 'key' ] ) ) {
        return 0 ;
    }
    if ( ! $order_id = $wp->query_vars[ 'order-pay' ] ) {
        return 0 ;
    }
    $order = _sumo_pp_get_order( $order_id ) ;

    if ( $order && _sumo_pp_is_payment_order( $order->order_id ) && $order->is_invoice() ) {
        return $order->order_id ;
    }
    return 0 ;
}

/**
 * Display WC sh fieldearc with respect to products and variations/customer
 * 
 * @param array $args
 * @param bool $echo
 * @return string echo search field
 */
function _sumo_pp_wc_search_field( $args = array () , $echo = true ) {

    $args = wp_parse_args( $args , array (
        'class'       => '' ,
        'id'          => '' ,
        'name'        => '' ,
        'type'        => '' ,
        'action'      => '' ,
        'title'       => '' ,
        'placeholder' => '' ,
        'css'         => 'width: 50%;' ,
        'multiple'    => true ,
        'allow_clear' => true ,
        'selected'    => true ,
        'options'     => array ()
            ) ) ;

    ob_start() ;
    if ( '' !== $args[ 'title' ] ) {
        ?>
        <tr valign="top">
            <th class="titledesc" scope="row">
                <label for="<?php echo esc_attr( $args[ 'id' ] ) ; ?>"><?php echo esc_attr( $args[ 'title' ] ) ; ?></label>
            </th>
            <td class="forminp forminp-select">
                <?php
            }
            if ( _sumo_pp_is_wc_version( '<=' , '2.2' ) ) {
                ?><select <?php echo $args[ 'multiple' ] ? 'multiple="multiple"' : '' ?> name="<?php echo esc_attr( '' !== $args[ 'name' ] ? $args[ 'name' ] : $args[ 'id' ]  ) ; ?>[]" id="<?php echo esc_attr( $args[ 'id' ] ) ; ?>" class="<?php echo esc_attr( $args[ 'id' ] ) ; ?>" data-placeholder="<?php echo esc_attr( $args[ 'placeholder' ] ) ; ?>" style="<?php echo esc_attr( $args[ 'css' ] ) ; ?>"><?php
                    if ( is_array( $args[ 'options' ] ) ) {
                        foreach ( $args[ 'options' ] as $id ) {
                            $option_value = '' ;

                            switch ( $args[ 'type' ] ) {
                                case 'product':
                                    if ( $product = wc_get_product( $id ) ) {
                                        $option_value = wp_kses_post( $product->get_formatted_name() ) ;
                                    }
                                    break ;
                                case 'customer':
                                    if ( $user = get_user_by( 'id' , $id ) ) {
                                        $option_value = esc_html( esc_html( $user->display_name ) . '(#' . absint( $user->ID ) . ' &ndash; ' . esc_html( $user->user_email ) . ')' ) ;
                                    }
                                    break ;
                                default :
                                    if ( $post = get_post( $id ) ) {
                                        $option_value = wp_kses_post( $post->post_title ) ;
                                    }
                                    break ;
                            }
                            if ( $option_value ) {
                                ?>
                                <option value="<?php echo esc_attr( $id ) ; ?>" <?php echo $args[ 'selected' ] ? 'selected="selected"' : '' ?>><?php echo $option_value ; ?></option>
                                <?php
                            }
                        }
                    }
                    ?></select><?php } else if ( _sumo_pp_is_wc_version( '<' , '3.0' ) ) {
                    ?>
                <input type="hidden" name="<?php echo esc_attr( '' !== $args[ 'name' ] ? $args[ 'name' ] : $args[ 'id' ]  ) ; ?>" id="<?php echo esc_attr( $args[ 'id' ] ) ; ?>" class="<?php echo esc_attr( $args[ 'class' ] ) ; ?>" data-action="<?php echo esc_attr( $args[ 'action' ] ) ; ?>" data-placeholder="<?php echo esc_attr( $args[ 'placeholder' ] ) ; ?>" <?php echo $args[ 'multiple' ] ? 'data-multiple="true"' : '' ?> <?php echo $args[ 'allow_clear' ] ? 'data-allow_clear="true"' : '' ?> style="<?php echo esc_attr( $args[ 'css' ] ) ; ?>" <?php if ( $args[ 'selected' ] ) { ?> data-selected="<?php
                    $json_ids = array () ;

                    if ( is_array( $args[ 'options' ] ) ) {
                        foreach ( $args[ 'options' ] as $id ) {
                            switch ( $args[ 'type' ] ) {
                                case 'product':
                                    if ( $product = wc_get_product( $id ) ) {
                                        $json_ids[ $id ] = wp_kses_post( $product->get_formatted_name() ) ;
                                    }
                                    break ;
                                case 'customer':
                                    if ( $user = get_user_by( 'id' , $id ) ) {
                                        $json_ids[ $id ] = esc_html( $user->display_name ) . ' (#' . absint( $user->ID ) . ' &ndash; ' . esc_html( $user->user_email ) . ')' ;
                                    }
                                    break ;
                                default :
                                    if ( $post = get_post( $id ) ) {
                                        $json_ids[ $id ] = wp_kses_post( $post->post_title ) ;
                                    }
                                    break ;
                            }
                        }
                    }
                    echo esc_attr( json_encode( $json_ids ) ) ;
                    ?>" value="<?php
                           echo implode( ',' , array_keys( $json_ids ) ) ;
                       }
                       ?>"/><?php } else {
                       ?>
                <select <?php echo $args[ 'multiple' ] ? 'multiple="multiple"' : '' ?> name="<?php echo esc_attr( '' !== $args[ 'name' ] ? $args[ 'name' ] : $args[ 'id' ]  ) ; ?>[]" id="<?php echo esc_attr( $args[ 'id' ] ) ; ?>" class="<?php echo esc_attr( $args[ 'class' ] ) ; ?>" data-action="<?php echo esc_attr( $args[ 'action' ] ) ; ?>" data-placeholder="<?php echo esc_attr( $args[ 'placeholder' ] ) ; ?>" style="<?php echo esc_attr( $args[ 'css' ] ) ; ?>"><?php
                    if ( is_array( $args[ 'options' ] ) ) {
                        foreach ( $args[ 'options' ] as $id ) {
                            $option_value = '' ;

                            switch ( $args[ 'type' ] ) {
                                case 'product':
                                    if ( $product = wc_get_product( $id ) ) {
                                        $option_value = wp_kses_post( $product->get_formatted_name() ) ;
                                    }
                                    break ;
                                case 'customer':
                                    if ( $user = get_user_by( 'id' , $id ) ) {
                                        $option_value = esc_html( esc_html( $user->display_name ) . '(#' . absint( $user->ID ) . ' &ndash; ' . esc_html( $user->user_email ) . ')' ) ;
                                    }
                                    break ;
                                default :
                                    if ( $post = get_post( $id ) ) {
                                        $option_value = wp_kses_post( $post->post_title ) ;
                                    }
                                    break ;
                            }
                            if ( $option_value ) {
                                ?><option value="<?php echo esc_attr( $id ) ; ?>" <?php echo $args[ 'selected' ] ? 'selected="selected"' : '' ?>><?php echo $option_value ; ?></option><?php
                            }
                        }
                    }
                    ?></select><?php
            }
            if ( '' !== $args[ 'title' ] ) {
                ?>
            </td>
        </tr>
        <?php
    }
    if ( $echo ) {
        echo ob_get_clean() ;
    } else {
        return ob_get_clean() ;
    }
}

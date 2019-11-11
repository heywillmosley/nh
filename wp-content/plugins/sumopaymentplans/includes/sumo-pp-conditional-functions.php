<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit ; // Exit if accessed directly
}

/**
 * Check the currently installed WC version
 * @param string $comparison_opr The possible operators are: <, lt, <=, le, >, gt, >=, ge, ==, =, eq, !=, <>, ne respectively.
  This parameter is case-sensitive, values should be lowercase
 * @param string $version
 * @return boolean
 */
function _sumo_pp_is_wc_version( $comparison_opr , $version ) {

    if ( defined( 'WC_VERSION' ) && version_compare( WC_VERSION , $version , $comparison_opr ) ) {
        return true ;
    }
    return false ;
}

function _sumo_pp_cart_has_payment_items() {
    if ( is_admin() ) {
        return false ;
    }

    $cart_data     = SUMO_PP_Frontend::get_cart_session() ;
    $payment_items = array_keys( $cart_data ) ;

    if ( empty( $payment_items ) ) {
        return false ;
    }

    foreach ( ( array ) WC()->cart->cart_contents as $item_key => $item ) {
        if ( ! isset( $item[ 'product_id' ] ) ) {
            continue ;
        }
        $item = $item[ 'variation_id' ] > 0 ? $item[ 'variation_id' ] : $item[ 'product_id' ] ;

        if ( in_array( $item , $payment_items ) ) {
            return true ;
        }
    }
    return false ;
}

function _sumo_pp_is_payment_order( $order ) {
    if (
            $order instanceof SUMO_PP_Order ||
            ($order = _sumo_pp_get_order( $order ))
    ) {
        $prefix = _sumo_pp()->prefix ;

        return 'yes' === get_post_meta( $order->order_id , "is{$prefix}order" , true ) ;
    }
    return false ;
}

function _sumo_pp_current_user_can_purchase_payment( $args = array () , $user = null ) {
    include_once( ABSPATH . 'wp-includes/pluggable.php' ) ;

    $args = wp_parse_args( $args , array (
        'limit_by'            => get_option( _sumo_pp()->prefix . 'show_deposit_r_payment_plans_for' , 'all_users' ) ,
        'filtered_users'      => ( array ) get_option( _sumo_pp()->prefix . 'get_limited_users_of_payment_product' ) ,
        'filtered_user_roles' => ( array ) get_option( _sumo_pp()->prefix . 'get_limited_userroles_of_payment_product' ) ,
            ) ) ;

    $current_user_id = get_current_user_id() ;
    if ( is_numeric( $user ) && $user ) {
        $current_user_id = $user ;
    } else if ( isset( $user->ID ) ) {
        $current_user_id = $user->ID ;
    }
    $current_user = get_user_by( 'id' , $current_user_id ) ;

    switch ( $args[ 'limit_by' ] ) {
        case 'all_users':
            return true ;
        case 'include_users':
            if ( ! $current_user ) {
                return false ;
            }

            $filtered_user_mails = array () ;
            foreach ( $args[ 'filtered_users' ] as $user_id ) {
                if ( ! $user = get_user_by( 'id' , $user_id ) ) {
                    continue ;
                }

                $filtered_user_mails[] = $user->data->user_email ;
            }
            if ( in_array( $current_user->data->user_email , $filtered_user_mails ) ) {
                return true ;
            }
            break ;
        case 'exclude_users':
            if ( ! $current_user ) {
                return false ;
            }

            $filtered_user_mails = array () ;
            foreach ( $args[ 'filtered_users' ] as $user_id ) {
                if ( ! $user = get_user_by( 'id' , $user_id ) ) {
                    continue ;
                }

                $filtered_user_mails[] = $user->data->user_email ;
            }
            if ( ! in_array( $current_user->data->user_email , $filtered_user_mails ) ) {
                return true ;
            }
            break ;
        case 'include_user_role':
            if ( $current_user ) {
                if ( isset( $current_user->roles[ 0 ] ) && in_array( $current_user->roles[ 0 ] , $args[ 'filtered_user_roles' ] ) ) {
                    return true ;
                }
            } elseif ( in_array( 'guest' , $args[ 'filtered_user_roles' ] ) ) {
                return true ;
            }
            break ;
        case 'exclude_user_role':
            if ( $current_user ) {
                if ( isset( $current_user->roles[ 0 ] ) && ! in_array( $current_user->roles[ 0 ] , $args[ 'filtered_user_roles' ] ) ) {
                    return true ;
                }
            } elseif ( ! in_array( 'guest' , $args[ 'filtered_user_roles' ] ) ) {
                return true ;
            }
            break ;
    }
    return false ;
}

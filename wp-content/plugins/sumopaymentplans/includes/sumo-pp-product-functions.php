<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit ; // Exit if accessed directly
}

/**
 * Get WC Product object
 * @param WC_Product $the_product
 * @return boolean|\WC_Product
 */
function _sumo_pp_get_product( $the_product ) {

    if ( is_numeric( $the_product ) ) {
        return wc_get_product( $the_product ) ;
    } elseif ( $the_product instanceof WC_Product ) {
        return $the_product ;
    }

    return false ;
}

/**
 * Get Product/Variation ID
 * @param object | int $product The Product post ID
 * @param bool $check_parent
 * @return int
 */
function _sumo_pp_get_product_id( $product , $check_parent = false ) {

    if ( ! $product = _sumo_pp_get_product( $product ) ) {
        return null ;
    }

    if ( _sumo_pp_is_wc_version( '<' , '3.0' ) ) {
        $product_id = $product->id ;

        if ( in_array( _sumo_pp_get_product_type( $product_id ) , array ( 'variation' , 'variable' ) ) ) {
            $product_id = $product->variation_id ;
        }
    } else {
        $product_id = $product->get_id() ;
    }

    $parent_id = 0 ;
    if ( $check_parent ) {
        $parent_id = wp_get_post_parent_id( $product_id ) ;
    }

    return $parent_id ? $parent_id : $product_id ;
}

/**
 * Get Product type
 * @param object | int $product The Product post ID
 * @return string
 */
function _sumo_pp_get_product_type( $product ) {

    if ( ! $product = _sumo_pp_get_product( $product ) ) {
        return null ;
    }

    if ( _sumo_pp_is_wc_version( '<' , '3.0' ) ) {
        return $product->product_type ;
    }
    return $product->get_type() ;
}

/**
 * Get Product price
 * @param object | int $product The Product post ID
 * @return string
 */
function _sumo_pp_get_product_price( $product ) {

    if ( ! $product = _sumo_pp_get_product( $product ) ) {
        return null ;
    }

    if ( _sumo_pp_is_wc_version( '<' , '3.0' ) ) {
        return $product->price ;
    }
    return $product->get_price() ;
}

/**
 * Get Product price excludes tax
 * @param object | int $product The Product post ID
 * @param array $args
 * @return float
 */
function _sumo_pp_get_price_excluding_tax( $product , $args = array () ) {

    if ( ! $product = _sumo_pp_get_product( $product ) ) {
        return null ;
    }

    $args = wp_parse_args( $args , array (
        'qty'   => 1 ,
        'price' => ''
            ) ) ;

    if ( _sumo_pp_is_wc_version( '<' , '3.0' ) ) {
        return $product->get_price_excluding_tax( $args[ 'qty' ] , $args[ 'price' ] ) ;
    }
    return wc_get_price_excluding_tax( $product , $args ) ;
}

/**
 * Format a sale price for display.
 * @param object | int $product The Product post ID
 * @param string $from
 * @param string $to
 */
function _sumo_pp_get_sale_price_html_from_to( $product , $from , $to ) {

    if ( ! $product = _sumo_pp_get_product( $product ) ) {
        return null ;
    }

    if ( _sumo_pp_is_wc_version( '<' , '3.0' ) ) {
        return $product->get_price_html_from_to( $from , $to ) ;
    }
    return wc_format_sale_price( $from , $to ) ;
}

/**
 * Get formatted product name
 * @param object | int $product The Product post ID
 * @return string
 */
function _sumo_pp_get_formatted_name( $product ) {

    if ( ! $product = _sumo_pp_get_product( $product ) ) {
        return null ;
    }

    if ( _sumo_pp_is_wc_version( '<' , '3.0' ) ) {
        return $product->get_formatted_name() ;
    }

    if ( $product->get_sku() ) {
        $identifier = $product->get_sku() ;
    } else {
        $identifier = '#' . _sumo_pp_get_product_id( $product ) ;
    }

    $formatted_attributes = wc_get_formatted_variation( $product , true ) ;
    $extra_data           = ' &ndash; ' . $formatted_attributes . ' &ndash; ' . wc_price( $product->get_price() ) ;

    return sprintf( __( '%s &ndash; %s%s' , 'woocommerce' ) , $identifier , $product->get_title() , $extra_data ) ;
}

/**
 * Get product title
 * @param object | int $product
 * @return array
 */
function _sumo_pp_get_product_title( $product ) {

    if ( ! $product = _sumo_pp_get_product( $product ) ) {
        return '' ;
    }
    return $product->get_title() ;
}

/**
 * Get product downloads
 * @param object | int $product
 * @return array
 */
function _sumo_pp_get_downloads( $product ) {

    if ( ! $product = _sumo_pp_get_product( $product ) ) {
        return null ;
    }

    if ( _sumo_pp_is_wc_version( '<' , '3.0' ) ) {
        return $product->get_files() ;
    }

    return $product->get_downloads() ;
}

function _sumo_pp_get_product_url( $product_id ) {
    if ( is_admin() ) {
        $url = admin_url( "post.php?post={$product_id}&action=edit" ) ;
    } else {
        $url = get_permalink( $product_id ) ;
    }
    return $url ;
}

function _sumo_pp_get_cart_data( $product = null , $customer_id = 0 ) {

    if ( ! $product_id = _sumo_pp_get_product_id( $product ) ) {
        return SUMO_PP_Frontend::get_cart_session() ;
    }

    if ( $customer_id > 0 ) {
        $customer_data = SUMO_PP_Payment_Order::get_customer_checkout_transient( $customer_id ) ;

        $cart_data = isset( $customer_data[ $product_id ] ) ? $customer_data[ $product_id ] : $customer_data ;
    } else {
        $cart_data = SUMO_PP_Frontend::get_cart_session( $product_id ) ;
    }
    return $cart_data ;
}

function _sumo_pp_is_payment_product( $product , $customer_id = 0 ) {

    if ( ! $product_id = _sumo_pp_get_product_id( $product ) ) {
        return false ;
    }

    $cart_data = _sumo_pp_get_cart_data( $product , $customer_id ) ;

    return isset( $cart_data[ 'payment_product_props' ][ 'product_id' ] ) && $product_id == $cart_data[ 'payment_product_props' ][ 'product_id' ] ;
}

function _sumo_pp_get_payment_data( $product = null ) {
    $meta_data = array () ;
    $item_meta = SUMO_PP_Frontend::get_item_meta( $product ) ;

    if ( isset( $item_meta[ 'plan_name' ] ) ) {
        $meta_data[ 'plan_name' ] = sprintf( __( '<br><strong>' . get_option( _sumo_pp()->prefix . 'payment_plan_label' ) . '</strong> <br>%s' ) , $item_meta[ 'plan_name' ] ) ;
    }
    if ( isset( $item_meta[ 'plan_description' ] ) ) {
        $meta_data[ 'plan_description' ] = $item_meta[ 'plan_description' ] ;
    }
    if ( isset( $item_meta[ 'product_price' ] ) ) {
        $meta_data[ 'product_price' ] = $item_meta[ 'product_price' ] ;
    }
    if ( isset( $item_meta[ 'total_payable' ] ) ) {
        $meta_data[ 'total_payable_amount' ] = $item_meta[ 'total_payable' ] ;
    }
    return $meta_data ;
}

function _sumo_pp_get_cart_balance_payable_amount() {
    return SUMO_PP_Frontend::get_cart_balance_payable_amount() ;
}

function _sumo_pp_get_cart_payment_display_string( $product ) {

    $cart_data = _sumo_pp_get_cart_data( $product ) ;

    if ( ! isset( $cart_data[ 'payment_product_props' ][ 'payment_type' ] ) ) {
        return array () ;
    }

    $item_meta = SUMO_PP_Frontend::get_item_meta( $product , true ) ;

    $under_product_column_string = '' ;
    $under_total_column_string   = '' ;
    $under_price_column_string   = $item_meta[ 'product_price' ] ;

    if ( 'payment-plans' === $cart_data[ 'payment_product_props' ][ 'payment_type' ] ) {
        $under_product_column_string .= sprintf( __( '<br><strong>%s</strong> <br>%s' ) , $item_meta[ 'payment_plan_label' ] , $item_meta[ 'plan_name' ] ) ;

        if ( $item_meta[ 'plan_description' ] ) {
            $under_product_column_string .= sprintf( __( '<p><small style="color:#777;">%s</small>' ) , $item_meta[ 'plan_description' ] ) ;
        }

        $under_total_column_string .= sprintf( __( '<p><small style="color:#777;">Balance <strong>%s</strong> payable</small></p>' , _sumo_pp()->text_domain ) , $item_meta[ 'balance_payable' ] ) ;
        $under_price_column_string .= sprintf( __( '<br><small style="color:#777;">Total <strong>%s</strong> payable</small>' , _sumo_pp()->text_domain ) , $item_meta[ 'total_payable' ] ) ;
        $under_price_column_string .= sprintf( __( '<br><small style="color:#777;">%s <strong>%s</strong></small></p>' ) , $item_meta[ 'due_date_label' ] , $item_meta[ 'next_payment_date' ] ) ;
    } else if ( 'pay-in-deposit' === $cart_data[ 'payment_product_props' ][ 'payment_type' ] ) {

        $under_price_column_string .= sprintf( __( '<p><small style="color:#777;">Total <strong>%s</strong> payable</small>' , _sumo_pp()->text_domain ) , $item_meta[ 'total_payable' ] ) ;
        $under_price_column_string .= sprintf( __( '<br><small style="color:#777;">%s <strong>%s</strong></small></p>' ) , $item_meta[ 'due_date_label' ] , $item_meta[ 'next_payment_date' ] ) ;
        $under_total_column_string .= sprintf( __( '<p><small style="color:#777;">Balance <strong>%s</strong> payable</small></p>' , _sumo_pp()->text_domain ) , $item_meta[ 'balance_payable' ] ) ;
    }

    return array (
        'under_product_column' => $under_product_column_string ,
        'under_price_column'   => $under_price_column_string ,
        'under_total_column'   => $under_total_column_string ,
            ) ;
}

function _sumo_pp_set_payment_session( $payment_data ) {

    $prefix              = _sumo_pp()->prefix ;
    $product_id          = isset( $payment_data[ 'payment_product_props' ][ 'product_id' ] ) ? $payment_data[ 'payment_product_props' ][ 'product_id' ] : null ;
    $chosen_payment_plan = isset( $payment_data[ 'payment_plan_props' ][ 'plan_id' ] ) ? $payment_data[ 'payment_plan_props' ][ 'plan_id' ] : null ;
    $quantity            = isset( $payment_data[ 'product_qty' ] ) ? absint( $payment_data[ 'product_qty' ] ) : 1 ;
    $deposited_amount    = isset( $payment_data[ 'deposited_amount' ] ) ? $payment_data[ 'deposited_amount' ] : null ;

    if ( ! $product_id = _sumo_pp_get_product_id( $product_id ) ) {
        return false ;
    }

    SUMO_PP_Frontend::maybe_clear_cart_session( $product_id ) ;

    if ( $SUMO_Payment_Plans_is_enabled = 'yes' === get_post_meta( $product_id , "{$prefix}enable_sumopaymentplans" , true ) ) {
        WC()->session->set( "{$prefix}cart_data" , array (
            $product_id => array (
                'deposited_amount'      => $deposited_amount ,
                'product_qty'           => $quantity ,
                'payment_product_props' => SUMO_PP_Frontend::get_product_props( $product_id ) ,
                'payment_plan_props'    => SUMO_PP_Frontend::get_payment_plan_props( $chosen_payment_plan ) ,
            ) ) + SUMO_PP_Frontend::get_cart_session() ) ;
        return true ;
    }
    return false ;
}

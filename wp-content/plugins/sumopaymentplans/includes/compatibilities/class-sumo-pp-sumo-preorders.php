<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit ; // Exit if accessed directly
}

/**
 * Handle SUMO Pre-Orders Compatibility.
 * 
 * @class SUMO_PP_SUMOPreOrders
 * @category Class
 */
class SUMO_PP_SUMOPreOrders {

    /**
     * Init SUMO_PP_SUMOPreOrders.
     */
    public static function init() {
        add_filter( 'sumopaymentplans_get_product_props' , __CLASS__ . '::alter_product_props' , 999 ) ;
        add_action( 'sumopaymentplans_payment_is_cancelled' , __CLASS__ . '::payment_is_cancelled' , 999 , 3 ) ;
    }

    public static function alter_product_props( $product_props ) {
        if ( is_null( $product_props[ 'product_id' ] ) ) {
            return $product_props ;
        }

        $preorder_product_props = SUMO_WCPO_Product_Manager::get_props( $product_props[ 'product_id' ] ) ;

        if ( ! is_null( $preorder_product_props[ 'product_available_on' ] ) ) {
            $product_props[ 'sumopreorder_product' ] = 'yes' ;
            $product_props[ 'pay_balance_type' ]     = 'before' ;
            $product_props[ 'pay_balance_before' ]   = $preorder_product_props[ 'product_available_on' ] ;
        }
        return $product_props ;
    }

    public static function payment_is_cancelled( $payment_id , $order_id , $order_type ) {

        if (
                'pay-in-deposit' === get_post_meta( $payment_id , '_payment_type' , true ) &&
                'before' === get_post_meta( $payment_id , '_pay_balance_type' , true ) &&
                'yes' === get_post_meta( $payment_id , '_sumopreorder_product' , true ) &&
                function_exists( '_sumo_wcpo_get_preorder' )
        ) {
            $preordered_order = wc_get_order( get_post_meta( $payment_id , '_initial_payment_order_id' , true ) ) ;
            $preorders        = _sumo_wcpo_get_posts( array (
                'post_type'   => 'sumo_wcpo_preorders' ,
                'post_status' => array ( _sumo_wcpo()->prefix . 'pending' , _sumo_wcpo()->prefix . 'progress' ) ,
                'meta_key'    => '_preordered_order_id' ,
                'meta_value'  => _sumo_pp_get_order_id( $preordered_order ) ,
                    ) ) ;

            if ( $preordered_order && ($preorder = _sumo_wcpo_get_preorder( isset( $preorders[ 0 ] ) ? $preorders[ 0 ] : 0 ) ) ) {
                $preordered_order->update_status( 'cancelled' ) ;
            }
        }
    }

}

SUMO_PP_SUMOPreOrders::init() ;

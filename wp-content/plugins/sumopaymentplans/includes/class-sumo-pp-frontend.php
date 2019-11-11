<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit ; // Exit if accessed directly
}

/**
 * Handle SUMO Payment Plans frontend part.
 * 
 * @class SUMO_PP_Frontend
 * @category Class
 */
class SUMO_PP_Frontend {

    public static $product_id    = 0 ;
    public static $product_props = array () ;

    /**
     * Init SUMO_PP_Frontend.
     */
    public static function init() {
        add_filter( 'woocommerce_product_add_to_cart_text' , __CLASS__ . '::alter_add_to_cart_text' , 10 , 2 ) ;
        add_filter( 'woocommerce_loop_add_to_cart_args' , __CLASS__ . '::prevent_ajax_add_to_cart' , 10 , 2 ) ;
        add_filter( 'woocommerce_product_add_to_cart_url' , __CLASS__ . '::redirect_to_single_product' , 10 , 2 ) ;

        add_action( 'woocommerce_before_add_to_cart_button' , __CLASS__ . '::add_payment_type_fields' , 10 ) ;
        add_filter( 'sumopaymentplans_get_single_variation_data_to_display' , __CLASS__ . '::add_payment_type_fields' , 10 , 2 ) ;
        add_action( 'woocommerce_before_variations_form' , __CLASS__ . '::get_variation_payment_type_fields' , 10 ) ;
        add_action( 'woocommerce_before_single_variation' , __CLASS__ . '::get_variation_payment_type_fields' , 10 ) ;
        add_action( 'woocommerce_after_single_variation' , __CLASS__ . '::get_variation_payment_type_fields' , 10 ) ;

        add_action( 'wp_head' , __CLASS__ . '::add_custom_style' , 99 ) ;
        add_filter( 'woocommerce_product_is_in_stock' , __CLASS__ . '::set_as_out_of_stock' , 99 , 2 ) ;

        add_filter( 'woocommerce_cart_item_name' , __CLASS__ . '::add_payment_plan_name' , 10 , 3 ) ;
        add_filter( 'woocommerce_checkout_cart_item_quantity' , __CLASS__ . '::add_payment_plan_name' , 10 , 3 ) ;
        add_filter( 'woocommerce_cart_item_price' , __CLASS__ . '::add_payment_info' , 10 , 3 ) ;
        add_filter( 'woocommerce_checkout_cart_item_quantity' , __CLASS__ . '::add_payment_info' , 10 , 3 ) ;
        add_filter( 'woocommerce_cart_item_subtotal' , __CLASS__ . '::get_balance_payable_amount_html' , 10 , 3 ) ;
        add_filter( 'woocommerce_order_formatted_line_subtotal' , __CLASS__ . '::get_order_item_balance_payable' , 99 , 3 ) ;
        add_action( 'woocommerce_cart_totals_after_order_total' , __CLASS__ . '::get_cart_balance_payable_amount_html' , 999 ) ;
        add_action( 'woocommerce_review_order_after_order_total' , __CLASS__ . '::get_cart_balance_payable_amount_html' , 999 ) ;
        add_filter( 'woocommerce_cart_totals_order_total_html' , __CLASS__ . '::get_payable_now' , 10 ) ;

        add_filter( 'woocommerce_add_to_cart_validation' , __CLASS__ . '::validate_cart' , 99 , 6 ) ;
        add_action( 'woocommerce_add_to_cart' , __CLASS__ . '::save_payment' , 99 , 6 ) ;
        add_filter( 'woocommerce_product_get_price' , __CLASS__ . '::get_initial_amount' , 9999 , 2 ) ;
        add_filter( 'woocommerce_product_variation_get_price' , __CLASS__ . '::get_initial_amount' , 9999 , 2 ) ;
        add_action( 'woocommerce_cart_loaded_from_session' , __CLASS__ . '::check_cart_items' , 99 ) ;
        add_action( 'woocommerce_after_calculate_totals' , __CLASS__ . '::calculate_totals' , 99 ) ;
//        add_filter( 'woocommerce_calculate_item_totals_taxes' , __CLASS__ . '::charge_tax_initially' , 99 , 3 ) ;

        add_action( 'woocommerce_before_checkout_form' , __CLASS__ . '::force_guest_signup_on_checkout' , 999 , 1 ) ;
        add_action( 'woocommerce_checkout_process' , __CLASS__ . '::force_create_account_for_guest' , 999 ) ;
        add_filter( 'woocommerce_available_payment_gateways' , __CLASS__ . '::set_payment_gateways' , 999 ) ;

        if ( _sumo_pp_is_wc_version( '<' , '3.0' ) ) {
            add_action( 'woocommerce_add_order_item_meta' , __CLASS__ . '::add_order_item_meta_legacy' , 10 , 3 ) ;
        } else {
            add_action( 'woocommerce_checkout_create_order_line_item' , __CLASS__ . '::add_order_item_meta' , 10 , 4 ) ;
        }
    }

    public static function get_product_props( $product , $user_id = null ) {

        extract( self::$product_props = array (
            'product_id'                     => null ,
            'product_price'                  => null ,
            'product_type'                   => null ,
            'payment_type'                   => null ,
            'apply_global_settings'          => null ,
            'force_deposit'                  => null ,
            'deposit_type'                   => null ,
            'deposit_price_type'             => null ,
            'fixed_deposit_price'            => null ,
            'fixed_deposit_percent'          => null ,
            'min_deposit'                    => null ,
            'max_deposit'                    => null ,
            'pay_balance_type'               => null ,
            'pay_balance_after'              => null ,
            'pay_balance_before'             => null ,
            'set_expired_deposit_payment_as' => null ,
            'selected_plans'                 => null ,
        ) ) ;

        if ( ! _sumo_pp_current_user_can_purchase_payment( array () , $user_id ) ) {
            return self::$product_props ;
        }
        if ( $product_id = _sumo_pp_get_product_id( $product ) ) {
            $SUMO_Payment_Plans_is_enabled = 'yes' === get_post_meta( $product_id , _sumo_pp()->prefix . 'enable_sumopaymentplans' , true ) ;

            if ( ! $SUMO_Payment_Plans_is_enabled ) {
                return self::$product_props ;
            }
            $payment_type          = get_post_meta( $product_id , _sumo_pp()->prefix . 'payment_type' , true ) ;
            $apply_global_settings = 'yes' === get_post_meta( $product_id , _sumo_pp()->prefix . 'apply_global_settings' , true ) ;

            if ( 'pay-in-deposit' === $payment_type ) {
                $deposit_type = $apply_global_settings ? get_option( _sumo_pp()->prefix . 'deposit_type' , 'pre-defined' ) : get_post_meta( $product_id , _sumo_pp()->prefix . 'deposit_type' , true ) ;

                if ( 'user-defined' === $deposit_type ) {
                    $min_deposit = $apply_global_settings ? floatval( get_option( _sumo_pp()->prefix . 'min_deposit' , '0.01' ) ) : floatval( get_post_meta( $product_id , _sumo_pp()->prefix . 'min_deposit' , true ) ) ;
                    $max_deposit = $apply_global_settings ? floatval( get_option( _sumo_pp()->prefix . 'max_deposit' , '99.99' ) ) : floatval( get_post_meta( $product_id , _sumo_pp()->prefix . 'max_deposit' , true ) ) ;
                } else {
                    $deposit_price_type = $apply_global_settings ? 'percent-of-product-price' : get_post_meta( $product_id , _sumo_pp()->prefix . 'deposit_price_type' , true ) ;

                    if ( 'percent-of-product-price' === $deposit_price_type ) {
                        $fixed_deposit_percent = $apply_global_settings ? floatval( get_option( _sumo_pp()->prefix . 'fixed_deposit_percent' , '50' ) ) : floatval( get_post_meta( $product_id , _sumo_pp()->prefix . 'fixed_deposit_percent' , true ) ) ;
                    } else {
                        $fixed_deposit_price = $apply_global_settings ? null : floatval( get_post_meta( $product_id , _sumo_pp()->prefix . 'fixed_deposit_price' , true ) ) ;
                    }
                }
                if ( $apply_global_settings ) {
                    $pay_balance_type  = 'after' ;
                    $pay_balance_after = false === get_option( _sumo_pp()->prefix . 'balance_payment_due' ) ? absint( get_option( _sumo_pp()->prefix . 'pay_balance_after' ) ) : absint( get_option( _sumo_pp()->prefix . 'balance_payment_due' ) ) ;
                } else {
                    $pay_balance_type = '' === get_post_meta( $product_id , _sumo_pp()->prefix . 'pay_balance_type' , true ) ? 'after' : get_post_meta( $product_id , _sumo_pp()->prefix . 'pay_balance_type' , true ) ;

                    if ( 'after' === $pay_balance_type ) {
                        $pay_balance_after = '' === get_post_meta( $product_id , _sumo_pp()->prefix . 'balance_payment_due' , true ) ? absint( get_post_meta( $product_id , _sumo_pp()->prefix . 'pay_balance_after' , true ) ) : absint( get_post_meta( $product_id , _sumo_pp()->prefix . 'balance_payment_due' , true ) ) ;
                    } else {
                        $pay_balance_before             = get_post_meta( $product_id , _sumo_pp()->prefix . 'pay_balance_before' , true ) ;
                        $set_expired_deposit_payment_as = get_post_meta( $product_id , _sumo_pp()->prefix . 'set_expired_deposit_payment_as' , true ) ;
                    }
                }
            } else if ( 'payment-plans' === $payment_type ) {
                $selected_plans = $apply_global_settings ? get_option( _sumo_pp()->prefix . 'selected_plans' , array () ) : get_post_meta( $product_id , _sumo_pp()->prefix . 'selected_plans' , true ) ;
                $selected_plans = is_array( $selected_plans ) ? $selected_plans : array () ;
            }

            if ( 'sale-price' === get_option( _sumo_pp()->prefix . 'calc_deposits_r_payment_plans_price_based_on' , 'sale-price' ) ) {
                $product_price = _sumo_pp_get_product_price( $product_id ) ;
            } else {
                $product_price = _sumo_pp_get_product( $product_id )->get_regular_price() ;
            }

            self::$product_id    = absint( $product_id ) ;
            self::$product_props = wp_parse_args( ( array ) apply_filters( 'sumopaymentplans_get_product_props' , array (
                        'product_id'                     => self::$product_id ,
                        'product_price'                  => $product_price ,
                        'product_type'                   => _sumo_pp_get_product_type( $product_id ) ,
                        'payment_type'                   => $payment_type ,
                        'apply_global_settings'          => $apply_global_settings ? 'yes' : 'no' ,
                        'force_deposit'                  => $apply_global_settings ? ('payment-plans' === $payment_type ? get_option( _sumo_pp()->prefix . 'force_payment_plan' , 'no' ) : get_option( _sumo_pp()->prefix . 'force_deposit' , 'no' )) : get_post_meta( $product_id , _sumo_pp()->prefix . 'force_deposit' , true ) ,
                        'deposit_type'                   => $deposit_type ,
                        'deposit_price_type'             => $deposit_price_type ,
                        'fixed_deposit_price'            => $fixed_deposit_price ,
                        'fixed_deposit_percent'          => $fixed_deposit_percent ,
                        'min_deposit'                    => $min_deposit ,
                        'max_deposit'                    => $max_deposit ,
                        'pay_balance_type'               => $pay_balance_type ,
                        'pay_balance_after'              => $pay_balance_after ,
                        'pay_balance_before'             => $pay_balance_before ,
                        'set_expired_deposit_payment_as' => $set_expired_deposit_payment_as ,
                        'selected_plans'                 => $selected_plans ,
                    ) ) , self::$product_props ) ;
        }
        return self::$product_props ;
    }

    public static function get_payment_plan_props( $plan_id ) {

        return array (
            'plan_id'                         => absint( $plan_id ) ,
            'plan_price_type'                 => get_post_meta( $plan_id , '_price_type' , true ) ,
            'plan_description'                => get_post_meta( $plan_id , '_plan_description' , true ) ,
            'initial_payment'                 => floatval( get_post_meta( $plan_id , '_initial_payment' , true ) ) ,
            'payment_schedules'               => get_post_meta( $plan_id , '_payment_schedules' , true ) ,
            'balance_payable_orders_creation' => get_post_meta( $plan_id , '_balance_payable_orders_creation' , true ) ,
                ) ;
    }

    public static function get_cart_session( $product_id = 0 ) {
        if ( ! is_callable( array ( WC()->session , 'get' ) ) ) {
            return array () ;
        }

        $cart_data = WC()->session->get( _sumo_pp()->prefix . 'cart_data' ) ;
        $cart_data = is_array( $cart_data ) ? $cart_data : array () ;

        if ( isset( $cart_data[ $product_id ] ) ) {
            return is_array( $cart_data[ $product_id ] ) ? $cart_data[ $product_id ] : array () ;
        }
        return $cart_data ;
    }

    public static function get_payment_type( $product = null ) {

        if ( ! is_null( $product ) ) {
            self::get_product_props( $product ) ;
        }

        return self::$product_props[ 'payment_type' ] ;
    }

    public static function get_fixed_deposit_amount( $product = null ) {

        if ( ! is_null( $product ) ) {
            self::get_product_props( $product ) ;
        }

        if (
                'pay-in-deposit' === self::$product_props[ 'payment_type' ] &&
                'pre-defined' === self::$product_props[ 'deposit_type' ]
        ) {
            if ( 'fixed-price' === self::$product_props[ 'deposit_price_type' ] ) {
                return self::$product_props[ 'fixed_deposit_price' ] ;
            }
            if ( ! is_null( self::$product_props[ 'product_price' ] ) ) {
                return (self::$product_props[ 'product_price' ] * self::$product_props[ 'fixed_deposit_percent' ]) / 100 ;
            }
        }
        return '' ;
    }

    public static function get_user_defined_deposit_amount_range( $product = null ) {

        if ( ! is_null( $product ) ) {
            self::get_product_props( $product ) ;
        }

        $min_amount = $max_amount = 0 ;
        if (
                'pay-in-deposit' === self::$product_props[ 'payment_type' ] &&
                'user-defined' === self::$product_props[ 'deposit_type' ]
        ) {
            if ( ! is_null( self::$product_props[ 'product_price' ] ) ) {
                $min_amount = (self::$product_props[ 'product_price' ] * self::$product_props[ 'min_deposit' ]) / 100 ;
                $max_amount = (self::$product_props[ 'product_price' ] * self::$product_props[ 'max_deposit' ]) / 100 ;
            }
        }
        return array (
            'min' => round( $min_amount , 2 ) ,
            'max' => round( $max_amount , 2 ) ,
                ) ;
    }

    public static function get_formatted_price( $price ) {
        ob_start() ;
        ?>
        <span class="price"><?php echo wc_price( $price ) ; ?></span>
        <?php
        return ob_get_clean() ;
    }

    public static function get_payment_type_fields( $product = null , $hide_if_variation = false , $class = '' ) {

        if ( ! is_null( $product ) ) {
            self::get_product_props( $product ) ;
        }

        ob_start() ;

        switch ( self::$product_props[ 'payment_type' ] ) {
            case 'pay-in-deposit':
                if ( 'before' === self::$product_props[ 'pay_balance_type' ] ) {
                    if ( isset( self::$product_props[ 'booking_payment_end_date' ] ) ) {
                        if ( self::$product_props[ 'booking_payment_end_date' ] && self::$product_props[ 'booking_payment_end_date' ] <= _sumo_pp_get_timestamp( 0 , 0 , true ) ) {
                            return ob_get_clean() ;
                        } else {
                            //display payment deposit fields. may be it is SUMO Booking product
                        }
                    } else if ( _sumo_pp_get_timestamp( self::$product_props[ 'pay_balance_before' ] ) <= _sumo_pp_get_timestamp( 0 , 0 , true ) ) {
                        return ob_get_clean() ;
                    }
                }
                ?>
                <div class="<?php echo $class ; ?>" id="<?php echo _sumo_pp()->prefix . 'payment_type_fields' ; ?>" <?php echo $hide_if_variation ? 'style="display:none;"' : '' ; ?>>
                    <p>
                        <?php if ( 'yes' !== self::$product_props[ 'force_deposit' ] ) { ?>
                            <input type="radio" value="pay_in_full" name="<?php echo _sumo_pp()->prefix . 'payment_type' ; ?>" checked="checked"/>
                            <?php echo get_option( _sumo_pp()->prefix . 'pay_in_full_label' ) ; ?>
                        <?php } ?>
                        <input type="radio" value="pay-in-deposit" name="<?php echo _sumo_pp()->prefix . 'payment_type' ; ?>" <?php echo 'yes' === self::$product_props[ 'force_deposit' ] ? 'checked="checked"' : '' ; ?>/>
                        <?php echo get_option( _sumo_pp()->prefix . 'pay_a_deposit_amount_label' ) ; ?>
                    </p>
                    <div id="<?php echo _sumo_pp()->prefix . 'amount_to_choose' ; ?>" <?php echo 'yes' === self::$product_props[ 'force_deposit' ] ? '' : 'style="display: none;"' ; ?>>
                        <?php if ( 'user-defined' === self::$product_props[ 'deposit_type' ] ) { ?>
                            <p>
                                <label for="<?php echo _sumo_pp()->prefix . 'deposited_amount' ; ?>">
                                    <?php
                                    $deposit_amount_range = self::get_user_defined_deposit_amount_range() ;
                                    printf( __( 'Enter your Deposit Amount between %s and %s' , _sumo_pp()->text_domain ) , self::get_formatted_price( $deposit_amount_range[ 'min' ] ) , self::get_formatted_price( $deposit_amount_range[ 'max' ] ) ) ;
                                    ?>
                                </label>
                                <input type="number" min="<?php echo floatval( $deposit_amount_range[ 'min' ] ) ; ?>" max="<?php echo floatval( $deposit_amount_range[ 'max' ] ) ; ?>" step="0.01" class="input-text" name="<?php echo _sumo_pp()->prefix . 'deposited_amount' ; ?>"/>
                            </p>
                        <?php } else { ?>
                            <p>
                                <?php echo self::get_formatted_price( self::get_fixed_deposit_amount() ) ; ?>
                                <input type="hidden" value="<?php echo self::get_fixed_deposit_amount() ; ?>" name="<?php echo _sumo_pp()->prefix . 'deposited_amount' ; ?>"/>
                            </p>
                        <?php } ?>
                    </div>
                </div>
                <?php
                break ;
            case 'payment-plans':
                ?>
                <div class="<?php echo $class ; ?>" id="<?php echo _sumo_pp()->prefix . 'payment_type_fields' ; ?>" <?php echo $hide_if_variation ? 'style="display:none;"' : '' ; ?>>
                    <p>
                        <?php if ( 'yes' !== self::$product_props[ 'force_deposit' ] ) { ?>
                            <input type="radio" value="pay_in_full" name="<?php echo _sumo_pp()->prefix . 'payment_type' ; ?>" checked="checked"/>
                            <?php echo get_option( _sumo_pp()->prefix . 'pay_in_full_label' ) ; ?>
                        <?php } ?>
                        <input type="radio" value="payment-plans" name="<?php echo _sumo_pp()->prefix . 'payment_type' ; ?>" <?php echo 'yes' === self::$product_props[ 'force_deposit' ] ? 'checked="checked"' : '' ; ?>/>
                        <?php echo get_option( _sumo_pp()->prefix . 'pay_with_payment_plans_label' ) ; ?>
                    </p>                    
                    <div id="<?php echo _sumo_pp()->prefix . 'plans_to_choose' ; ?>" <?php echo 'yes' === self::$product_props[ 'force_deposit' ] ? '' : 'style="display: none;"' ; ?>>
                        <?php
                        if ( is_array( self::$product_props[ 'selected_plans' ] ) ) {
                            ksort( self::$product_props[ 'selected_plans' ] ) ;
                            $plan_columns = array ( 'col_1' , 'col_2' ) ;
                            $plans        = array () ;

                            if ( ! empty( self::$product_props[ 'selected_plans' ][ $plan_columns[ 0 ] ] ) ) {
                                $plan_size     = array_map( 'sizeof' , self::$product_props[ 'selected_plans' ] ) ;
                                $max_plan_size = max( $plan_size ) ;

                                for ( $i = 0 ; $i < $max_plan_size ; $i ++ ) {
                                    foreach ( $plan_columns as $column ) {
                                        if ( ! empty( self::$product_props[ 'selected_plans' ][ $column ][ $i ] ) ) {
                                            $plans[ $i ][] = self::$product_props[ 'selected_plans' ][ $column ][ $i ] ;
                                        }
                                    }
                                }
                            }

                            if ( empty( $plans ) ) {
                                $plans = self::$product_props[ 'selected_plans' ] ;
                            }
                            ?>
                            <table>
                                <?php
                                foreach ( $plans as $row => $plan ) {
                                    $plan = ( array ) $plan ;
                                    ?>
                                    <tr>
                                        <?php
                                        foreach ( $plan as $col => $plan_id ) {
                                            $plan = get_post( $plan_id ) ;
                                            ?>
                                            <td>
                                                <input type="radio" value="<?php echo absint( $plan_id ) ; ?>" name="<?php echo _sumo_pp()->prefix . 'chosen_payment_plan' ; ?>" <?php echo 0 === $row && 0 === $col ? 'checked="checked"' : '' ?>/>
                                                <?php if ( 'yes' === get_option( _sumo_pp()->prefix . 'payment_plan_add_to_cart_via_href' ) ) { ?>
                                                    <a href="<?php echo esc_url_raw( add_query_arg( array ( _sumo_pp()->prefix . 'payment_type' => 'payment-plans' , _sumo_pp()->prefix . 'chosen_payment_plan' => absint( $plan_id ) ) , wc_get_product( $product )->add_to_cart_url() ) ) ; ?>"><?php echo $plan->post_title ; ?></a>
                                                <?php } else { ?>
                                                    <strong><?php echo $plan->post_title ; ?></strong>
                                                <?php } ?>
                                                <p><?php echo get_post_meta( $plan_id , '_plan_description' , true ) ; ?></p>
                                            </td>
                                            <?php
                                        }
                                        ?>
                                    </tr>
                                    <?php
                                }
                                ?>
                            </table>     
                            <?php
                        }
                        ?>
                    </div>
                </div>
                <?php
                break ;
        }

        return ob_get_clean() ;
    }

    public static function get_cart_balance_payable_amount() {
        $remaining_payable_amount = 0 ;

        foreach ( ( array ) WC()->cart->cart_contents as $item_key => $item ) {
            if ( ! isset( $item[ 'product_id' ] ) ) {
                continue ;
            }
            $meta_data = self::get_item_meta( $item ) ;

            if ( isset( $meta_data[ 'balance_payable' ] ) ) {
                $remaining_payable_amount +=$meta_data[ 'balance_payable' ] ;
            }
        }
        return $remaining_payable_amount ;
    }

    public static function get_item_meta( $cart_item , $to_display = false ) {
        $meta_data  = array () ;
        $product_id = 0 ;

        if ( is_numeric( $cart_item ) ) {
            $product_id = $cart_item ;
        } else if ( is_array( $cart_item ) ) {
            $product_id = $cart_item[ 'variation_id' ] > 0 ? $cart_item[ 'variation_id' ] : $cart_item[ 'product_id' ] ;
        } else if ( $cart_item instanceof WC_Product ) {
            $product_id = _sumo_pp_get_product_id( $cart_item ) ;
        }
        $cart_data = self::get_cart_session( $product_id ) ;

        if ( ! isset( $cart_data[ 'payment_product_props' ][ 'payment_type' ] ) ) {
            return $meta_data ;
        }

        $product_price          = floatval( $cart_data[ 'payment_product_props' ][ 'product_price' ] ) ;
        $product_amount         = $product_price * $cart_data[ 'product_qty' ] ;
        $balance_payable_amount = 0 ;

        if ( 'payment-plans' === $cart_data[ 'payment_product_props' ][ 'payment_type' ] ) {
            $initial_payment = floatval( $cart_data[ 'payment_plan_props' ][ 'initial_payment' ] ) ;

            if ( 'fixed-price' === $cart_data[ 'payment_plan_props' ][ 'plan_price_type' ] ) {
                $total_payable_amount = $initial_payment * $cart_data[ 'product_qty' ] ;
            } else {
                $total_payable_amount = ($initial_payment * $product_amount) / 100 ;
            }

            $from_time               = 0 ;
            $scheduled_payments_date = array () ;

            if ( is_array( $cart_data[ 'payment_plan_props' ][ 'payment_schedules' ] ) ) {
                foreach ( $cart_data[ 'payment_plan_props' ][ 'payment_schedules' ] as $schedule ) {
                    if ( ! isset( $schedule[ 'scheduled_payment' ] ) ) {
                        continue ;
                    }
                    $next_scheduled_payment = floatval( $schedule[ 'scheduled_payment' ] ) ;

                    if ( 'fixed-price' === $cart_data[ 'payment_plan_props' ][ 'plan_price_type' ] ) {
                        $total_payable_amount += ($next_scheduled_payment * $cart_data[ 'product_qty' ]) ;
                        $balance_payable_amount += ($next_scheduled_payment * $cart_data[ 'product_qty' ]) ;
                    } else {
                        $total_payable_amount += ($next_scheduled_payment * $product_amount) / 100 ;
                        $balance_payable_amount += ($next_scheduled_payment * $product_amount) / 100 ;
                    }

                    $scheduled_payment_cycle   = _sumo_pp_get_payment_cycle_in_days( $schedule[ 'scheduled_duration_length' ] , $schedule[ 'scheduled_period' ] ) ;
                    $scheduled_payments_date[] = $from_time                 = _sumo_pp_get_timestamp( "+{$scheduled_payment_cycle} days" , $from_time ) ;
                }
            }

            if ( $to_display ) {
                if ( 'after_admin_approval' === $cart_data[ 'activate_payment' ] ) {
                    $next_payment_date = __( 'After Admin Approval' , _sumo_pp()->text_domain ) ;
                } else {
                    $next_payment_date = _sumo_pp_get_date_to_display( isset( $scheduled_payments_date[ 0 ] ) ? $scheduled_payments_date[ 0 ] : 0  ) ;
                }
            } else {
                $next_payment_date = (isset( $scheduled_payments_date[ 0 ] ) ? $scheduled_payments_date[ 0 ] : 0) ;
            }

            $meta_data = array (
                'plan_name'          => get_post( $cart_data[ 'payment_plan_props' ][ 'plan_id' ] )->post_title ,
                'plan_description'   => $cart_data[ 'payment_plan_props' ][ 'plan_description' ] ,
                'product_price'      => $to_display ? wc_price( $product_price ) : $product_price ,
                'total_payable'      => $to_display ? wc_price( $total_payable_amount ) : $total_payable_amount ,
                'balance_payable'    => $to_display ? wc_price( $balance_payable_amount ) : $balance_payable_amount ,
                'next_payment_date'  => $next_payment_date ,
                'due_date_label'     => get_option( _sumo_pp()->prefix . 'next_payment_date_label' ) ,
                'payment_plan_label' => get_option( _sumo_pp()->prefix . 'payment_plan_label' ) ,
                    ) ;
        } else if ( 'pay-in-deposit' === $cart_data[ 'payment_product_props' ][ 'payment_type' ] ) {
            if ( 'before' === $cart_data[ 'payment_product_props' ][ 'pay_balance_type' ] ) {
                $next_payment_date = _sumo_pp_get_timestamp( $cart_data[ 'payment_product_props' ][ 'pay_balance_before' ] ) ;
                $due_date_label    = get_option( _sumo_pp()->prefix . 'balance_payment_due_date_label' ) ;
            } else {
                $pay_balance_after = $cart_data[ 'payment_product_props' ][ 'pay_balance_after' ] ; //in days
                $next_payment_date = $pay_balance_after > 0 ? _sumo_pp_get_timestamp( "+{$pay_balance_after} days" ) : 0 ;
                $due_date_label    = get_option( _sumo_pp()->prefix . 'next_payment_date_label' ) ;
            }

            $deposited_amount       = floatval( $cart_data[ 'deposited_amount' ] ) * $cart_data[ 'product_qty' ] ;
            $balance_payable_amount = $deposited_amount > $product_amount ? $deposited_amount - $product_amount : $product_amount - $deposited_amount ;

            if ( $next_payment_date ) {
                if ( $to_display ) {
                    if ( 'before' !== $cart_data[ 'payment_product_props' ][ 'pay_balance_type' ] && 'after_admin_approval' === $cart_data[ 'activate_payment' ] ) {
                        $next_payment_date = __( 'After Admin Approval' , _sumo_pp()->text_domain ) ;
                    } else {
                        $next_payment_date = _sumo_pp_get_date_to_display( $next_payment_date ) ;
                    }
                }
            } else {
                $next_payment_date = '' ;
            }

            $meta_data = array (
                'product_price'     => $to_display ? wc_price( $product_price ) : $product_price ,
                'total_payable'     => $to_display ? wc_price( $product_amount ) : $product_amount ,
                'balance_payable'   => $to_display ? wc_price( $balance_payable_amount ) : $balance_payable_amount ,
                'next_payment_date' => $next_payment_date ,
                'due_date_label'    => $due_date_label ,
                    ) ;
        }
        return $meta_data ;
    }

    public static function maybe_clear_cart_session( $product_id = 0 , $reset = false ) {

        if ( $reset ) {
            WC()->session->set( _sumo_pp()->prefix . 'cart_data' , array () ) ;
        } else {
            $cart_data = self::get_cart_session() ;

            unset( $cart_data[ $product_id ] ) ;

            WC()->session->set( _sumo_pp()->prefix . 'cart_data' , $cart_data ) ;
        }
    }

    public static function alter_add_to_cart_text( $text , $product ) {
        $payment_type = self::get_payment_type( $product ) ;

        if ( ! in_array( self::$product_props[ 'product_type' ] , array ( 'variable' , 'variation' ) ) && in_array( $payment_type , array ( 'pay-in-deposit' , 'payment-plans' ) ) ) {
            return get_option( _sumo_pp()->prefix . 'add_to_cart_label' ) ;
        }
        return $text ;
    }

    public static function prevent_ajax_add_to_cart( $args , $product ) {

        $payment_type = self::get_payment_type( $product ) ;

        if ( in_array( $payment_type , array ( 'pay-in-deposit' , 'payment-plans' ) ) && isset( $args[ 'class' ] ) ) {
            $args[ 'class' ] = str_replace( 'ajax_add_to_cart' , '' , $args[ 'class' ] ) ;
        }
        return $args ;
    }

    public static function redirect_to_single_product( $add_to_cart_url , $product ) {

        if ( is_shop() ) {
            $payment_type = self::get_payment_type( $product ) ;

            if ( in_array( $payment_type , array ( 'pay-in-deposit' , 'payment-plans' ) ) ) {
                return get_permalink( self::$product_id ) ;
            }
        }
        return $add_to_cart_url ;
    }

    public static function set_as_out_of_stock( $bool , $product ) {
        if ( is_product() ) {
            self::get_product_props( $product ) ;

            if (
                    'pay-in-deposit' === self::$product_props[ 'payment_type' ] &&
                    'before' === self::$product_props[ 'pay_balance_type' ] &&
                    'out-of-stock' === self::$product_props[ 'set_expired_deposit_payment_as' ]
            ) {
                if ( isset( self::$product_props[ 'booking_payment_end_date' ] ) ) {
                    //may be it is SUMO Booking product
                    return true ;
                } else if ( _sumo_pp_get_timestamp( self::$product_props[ 'pay_balance_before' ] ) <= _sumo_pp_get_timestamp( 0 , 0 , true ) ) {
                    return false ;
                }
            }
        }
        return $bool ;
    }

    public static function add_custom_style() {
        ob_start() ;
        echo '<style type="text/css">' . get_option( _sumo_pp()->prefix . 'custom_css' ) . '</style>' ;
        ob_get_contents() ;
    }

    public static function add_payment_type_fields( $data = array () , $variation_id = null ) {
        global $product ;

        if ( doing_action( 'woocommerce_before_add_to_cart_button' ) ) {
            echo self::get_payment_type_fields( $product ) ;
        } else {
            $data[ 'payment_type_fields' ] = self::get_payment_type_fields( $variation_id ) ;
        }
        return $data ;
    }

    public static function get_variation_payment_type_fields() {
        global $product ;

        if ( doing_action( 'woocommerce_before_variations_form' ) ) {
            $children = $product->get_visible_children() ;

            if ( ! empty( $children ) ) {
                $variation_data = array () ;

                foreach ( $children as $child_id ) {
                    $product_variatons = new WC_Product_Variation( $child_id ) ;
                    if ( $product_variatons->exists() && $product_variatons->variation_is_visible() ) {
                        $_variation_data = apply_filters( 'sumopaymentplans_get_single_variation_data_to_display' , array () , $child_id ) ;

                        if ( ! empty( $_variation_data ) ) {
                            $variation_data[ $child_id ] = $_variation_data ;
                        }
                    }
                }

                if ( ! empty( $variation_data ) ) {
                    $variations   = wp_json_encode( array_keys( $variation_data ) ) ;
                    $hidden_field = "<input type='hidden' id='" . _sumo_pp()->prefix . "single_variations'" ;
                    $hidden_field .= "data-variations='{$variations}'" ;
                    $hidden_field .= "/>" ;
                    $hidden_field .= "<input type='hidden' id='" . _sumo_pp()->prefix . "single_variation_data'" ;
                    foreach ( $variation_data as $variation_id => $data ) {
                        foreach ( $data as $key => $value ) {
                            $hidden_field .= "data-{$key}_{$variation_id}='{$value}'" ;
                        }
                    }
                    $hidden_field .= "/>" ;
                    echo $hidden_field ;
                }
            }
        } else if ( doing_action( 'woocommerce_before_single_variation' ) ) {
            echo '<span id="' . _sumo_pp()->prefix . 'before_single_variation"></span>' ;
        } else {
            echo '<span id="' . _sumo_pp()->prefix . 'after_single_variation"></span>' ;
        }
    }

    public static function add_payment_plan_name( $return , $cart_item , $cart_item_key ) {
        $item_meta = self::get_item_meta( $cart_item , true ) ;

        if ( (is_checkout() && 'woocommerce_cart_item_name' === current_filter()) || ! isset( $item_meta[ 'plan_name' ] ) ) {
            return $return ;
        }
        $return .= sprintf( __( '<br><strong>%s</strong> <br>%s' ) , $item_meta[ 'payment_plan_label' ] , $item_meta[ 'plan_name' ] ) ;
        return $return ;
    }

    public static function add_payment_info( $price , $cart_item , $cart_item_key ) {
        $product_id = $cart_item[ 'variation_id' ] > 0 ? $cart_item[ 'variation_id' ] : $cart_item[ 'product_id' ] ;
        $cart_data  = self::get_cart_session( $product_id ) ;

        if ( ! isset( $cart_data[ 'payment_product_props' ][ 'payment_type' ] ) ) {
            return $price ;
        }

        $item_meta = self::get_item_meta( $cart_item , true ) ;
        $return    = '' ;

        if ( is_cart() ) {
            $return .= wc_price( floatval( $cart_data[ 'payment_product_props' ][ 'product_price' ] ) ) ;
        } else if ( is_checkout() ) {
            $return .= $price ;
        }
        if ( 'payment-plans' === $cart_data[ 'payment_product_props' ][ 'payment_type' ] ) {
            if ( $item_meta[ 'plan_description' ] ) {
                $return .= sprintf( __( '<p><small style="color:#777;">%s</small>' ) , $item_meta[ 'plan_description' ] ) ;
            }
            $return .= sprintf( __( '<br><small style="color:#777;">Total <strong>%s</strong> payable</small>' , _sumo_pp()->text_domain ) , $item_meta[ 'total_payable' ] ) ;
            $return .= sprintf( __( '<br><small style="color:#777;">%s <strong>%s</strong></small></p>' ) , $item_meta[ 'due_date_label' ] , $item_meta[ 'next_payment_date' ] ) ;
        } else if ( 'pay-in-deposit' === $cart_data[ 'payment_product_props' ][ 'payment_type' ] ) {
            $return .= sprintf( __( '<p><small style="color:#777;">Total <strong>%s</strong> payable</small>' , _sumo_pp()->text_domain ) , $item_meta[ 'total_payable' ] ) ;

            if ( $item_meta[ 'next_payment_date' ] ) {
                $return .= sprintf( __( '<br><small style="color:#777;">%s <strong>%s</strong></small></p>' ) , $item_meta[ 'due_date_label' ] , $item_meta[ 'next_payment_date' ] ) ;
            }
        }
        return $return ;
    }

    public static function get_balance_payable_amount_html( $product_subtotal , $cart_item , $cart_item_key ) {
        $item_meta = self::get_item_meta( $cart_item , true ) ;

        if ( isset( $item_meta[ 'balance_payable' ] ) ) {
            $product_subtotal .= sprintf( __( '<p><small style="color:#777;">Balance <strong>%s</strong> payable</small></p>' , _sumo_pp()->text_domain ) , $item_meta[ 'balance_payable' ] ) ;
        }
        return $product_subtotal ;
    }

    public static function get_order_item_balance_payable( $subtotal , $item , $order ) {
        $order = _sumo_pp_get_order( $order ) ;

        if ( ! $order || ! isset( $item[ 'product_id' ] ) ) {
            return $subtotal ;
        }

        $payments   = _sumo_pp()->query->get( array (
            'type'       => 'sumo_pp_payments' ,
            'status'     => array_keys( _sumo_pp_get_payment_statuses() ) ,
            'limit'      => 1 ,
            'meta_query' => array (
                'relation' => 'AND' ,
                array (
                    'key'     => $order->is_invoice() ? '_balance_payable_order_id' : '_initial_payment_order_id' ,
                    'value'   => $order->order_id ,
                    'compare' => '=' ,
                ) ,
                array (
                    'key'     => '_product_id' ,
                    'value'   => $item[ 'variation_id' ] > 0 ? $item[ 'variation_id' ] : $item[ 'product_id' ] ,
                    'compare' => '=' ,
                )
            ) ,
                ) ) ;
        $payment_id = isset( $payments[ 0 ] ) ? $payments[ 0 ] : 0 ;

        if ( $payment_id ) {
            $subtotal .= sprintf( __( '<p><small style="color:#777;">Balance <strong>%s</strong> payable</small></p>' , _sumo_pp()->text_domain ) , wc_price( _sumo_pp_get_remaining_payable_amount( $payment_id , $order->is_invoice() ) ) ) ;
        }
        return $subtotal ;
    }

    public static function get_cart_balance_payable_amount_html() {
        $remaining_payable_amount = self::get_cart_balance_payable_amount() ;

        if ( $remaining_payable_amount > 0 ) {
            ?>
            <tr class="<?php echo _sumo_pp()->prefix . 'balance_payable_amount' ; ?>">
                <th><?php echo get_option( _sumo_pp()->prefix . 'balance_payable_amount_label' ) ; ?></th>
                <td data-title="<?php echo get_option( _sumo_pp()->prefix . 'balance_payable_amount_label' ) ; ?>"><?php echo wc_price( $remaining_payable_amount ) ; ?></td>
            </tr>
            <?php
        }
    }

    public static function get_payable_now( $total ) {
        if ( _sumo_pp_cart_has_payment_items() ) {
            $total .= __( '<p><small style="color:#777;">Payable now</small></p>' , _sumo_pp()->text_domain ) ;
        }
        return $total ;
    }

    public static function get_posted_data() {
        $payment_type        = $deposited_amount    = $chosen_payment_plan = null ;

        if ( isset( $_POST[ _sumo_pp()->prefix . 'payment_type' ] ) ) {
            $payment_type = wc_clean( $_POST[ _sumo_pp()->prefix . 'payment_type' ] ) ;

            switch ( $payment_type ) {
                case 'pay-in-deposit':
                    if ( isset( $_POST[ _sumo_pp()->prefix . 'deposited_amount' ] ) ) {
                        $deposited_amount = $_POST[ _sumo_pp()->prefix . 'deposited_amount' ] ;
                    }
                    break ;
                case 'payment-plans':
                    if ( isset( $_POST[ _sumo_pp()->prefix . 'chosen_payment_plan' ] ) ) {
                        $chosen_payment_plan = $_POST[ _sumo_pp()->prefix . 'chosen_payment_plan' ] ;
                    }
                    break ;
            }
        } else if ( isset( $_GET[ _sumo_pp()->prefix . 'payment_type' ] ) ) {
            $payment_type = wc_clean( $_GET[ _sumo_pp()->prefix . 'payment_type' ] ) ;

            if ( 'payment-plans' === $payment_type && isset( $_GET[ _sumo_pp()->prefix . 'chosen_payment_plan' ] ) ) {
                $chosen_payment_plan = $_GET[ _sumo_pp()->prefix . 'chosen_payment_plan' ] ;
            }
        }
        return array (
            'payment_type'        => $payment_type ,
            'deposited_amount'    => $deposited_amount ,
            'chosen_payment_plan' => $chosen_payment_plan ,
                ) ;
    }

    public static function validate_cart( $bool , $product_id , $quantity , $variation_id = null , $variations = null , $cart_item_data = null ) {
        $product_id = $variation_id ? $variation_id : $product_id ;

        foreach ( ( array ) WC()->cart->cart_contents as $item_key => $item ) {
            if ( ! isset( $item[ 'product_id' ] ) ) {
                continue ;
            }
            $item_id = $item[ 'variation_id' ] > 0 ? $item[ 'variation_id' ] : $item[ 'product_id' ] ;

            if ( $item_id == $product_id ) {
                $payment_type = self::get_payment_type( $item_id ) ;

                if ( in_array( $payment_type , array ( 'pay-in-deposit' , 'payment-plans' ) ) ) {
                    WC()->cart->remove_cart_item( $item_key ) ;
                    self::maybe_clear_cart_session( $product_id ) ;
                }
            }
        }
        return $bool ;
    }

    public static function save_payment( $cart_item_key , $product_id , $quantity , $variation_id = 0 , $variations = array () , $cart_item_data = array () ) {
        $posted_data = self::get_posted_data() ;

        if ( in_array( $posted_data[ 'payment_type' ] , array ( 'pay-in-deposit' , 'payment-plans' ) ) ) {
            $product_id = $variation_id ? $variation_id : $product_id ;

            if ( 'pay-in-deposit' === $posted_data[ 'payment_type' ] && ! is_numeric( $posted_data[ 'deposited_amount' ] ) ) {
                self::maybe_clear_cart_session( $product_id ) ;
                wc_add_notice( __( 'Enter the deposit amount and try again!!' , _sumo_pp()->text_domain ) , 'error' ) ;
                return false ;
            }
            if ( ! is_null( $posted_data[ 'deposited_amount' ] ) || ! is_null( $posted_data[ 'chosen_payment_plan' ] ) ) {

                $cart_payment_item_data = array (
                    $product_id => apply_filters( 'sumopaymentplans_add_cart_item_data' , array (
                        'deposited_amount'      => $posted_data[ 'deposited_amount' ] ,
                        'product_qty'           => $quantity ,
                        'activate_payment'      => get_option( _sumo_pp()->prefix . 'activate_payments' , 'auto' ) ,
                        'payment_product_props' => self::get_product_props( $product_id ) ,
                        'payment_plan_props'    => self::get_payment_plan_props( $posted_data[ 'chosen_payment_plan' ] ) ,
                        'item_meta'             => $cart_item_data ,
                            ) , $product_id , $quantity , $cart_item_key , $cart_item_data ) ) ;

                WC()->session->set( _sumo_pp()->prefix . 'cart_data' , $cart_payment_item_data + self::get_cart_session() ) ;
            } else {
                self::maybe_clear_cart_session( $product_id ) ;
            }
        } else {
            self::maybe_clear_cart_session( $product_id ) ;
        }
    }

    public static function get_initial_amount( $price , $product ) {
        if ( ! is_checkout() && ! is_cart() ) {
            return $price ;
        }

        $product_id        = _sumo_pp_get_product_id( $product ) ;
        $cart_item_session = self::get_cart_session( $product_id ) ;

        if ( ! isset( $cart_item_session[ 'payment_product_props' ][ 'payment_type' ] ) ) {
            return $price ;
        }

        switch ( $cart_item_session[ 'payment_product_props' ][ 'payment_type' ] ) {
            case 'pay-in-deposit':
                $price = $cart_item_session[ 'deposited_amount' ] ;
                break ;
            case 'payment-plans':
                if ( 'fixed-price' === $cart_item_session[ 'payment_plan_props' ][ 'plan_price_type' ] ) {
                    $price = $cart_item_session[ 'payment_plan_props' ][ 'initial_payment' ] ;
                } else {
                    $price = ($cart_item_session[ 'payment_product_props' ][ 'product_price' ] * $cart_item_session[ 'payment_plan_props' ][ 'initial_payment' ]) / 100 ;
                }
                break ;
        }
        return $price ;
    }

    public static function check_cart_items( $cart ) {
        if ( empty( WC()->cart->cart_contents ) ) {
            self::maybe_clear_cart_session( 0 , true ) ;
        } else {
            if ( $cart_session = self::get_cart_session() ) {
                $cart_items = array () ;

                foreach ( WC()->cart->cart_contents as $item_key => $item ) {
                    if ( ! isset( $item[ 'product_id' ] ) ) {
                        continue ;
                    }
                    $cart_items[] = $item[ 'variation_id' ] > 0 ? $item[ 'variation_id' ] : $item[ 'product_id' ] ;
                }

                foreach ( $cart_session as $product_id => $data ) {
                    if ( ! in_array( $product_id , $cart_items ) ) {
                        self::maybe_clear_cart_session( $product_id ) ;
                    }
                }
            }
        }
    }

    public static function calculate_totals( $cart ) {

        if ( WC()->cart->get_cart_contents_count() > 0 ) {
            $cart_data = self::get_cart_session() ;

            foreach ( WC()->cart->cart_contents as $item_key => $item ) {
                if ( ! isset( $item[ 'product_id' ] ) ) {
                    continue ;
                }
                $product_id = $item[ 'variation_id' ] > 0 ? $item[ 'variation_id' ] : $item[ 'product_id' ] ;

                if ( isset( $cart_data[ $product_id ][ 'payment_product_props' ][ 'payment_type' ] ) ) {
                    $cart_data[ $product_id ][ 'product_qty' ] = absint( $item[ 'quantity' ] ) ;
                }
            }
            WC()->session->set( _sumo_pp()->prefix . 'cart_data' , $cart_data ) ;
        }
    }

    public static function charge_tax_initially( $taxes , $item , $cart ) {
        if ( 'initial-payment' === get_option( _sumo_pp()->prefix . 'charge_tax_during' , 'initial-payment' ) && ! empty( $item->product ) ) {
            $product_id        = _sumo_pp_get_product_id( $item->product ) ;
            $cart_item_session = self::get_cart_session( $product_id ) ;

            if ( isset( $cart_item_session[ 'payment_product_props' ][ 'payment_type' ] ) ) {
                $item_total = wc_add_number_precision_deep( $cart_item_session[ 'payment_product_props' ][ 'product_price' ] * $cart_item_session[ 'product_qty' ] ) ;
                $taxes      = WC_Tax::calc_tax( $item_total , $item->tax_rates , $item->price_includes_tax ) ;
            }
        }
        return $taxes ;
    }

    /**
     * Force display Signup on checkout for Guest. 
     * Since Guest can't have the permission to buy Deposit Payments.
     * 
     * @param object $checkout
     */
    public static function force_guest_signup_on_checkout( $checkout ) {
        if ( ! is_user_logged_in() && is_checkout() && isset( $checkout->enable_signup ) && isset( $checkout->enable_guest_checkout ) && ( _sumo_pp_cart_has_payment_items() || SUMO_PP_Order_Payment_Plan::can_user_deposit_payment_in_checkout() ) ) {
            $checkout->enable_signup         = true ;
            $checkout->enable_guest_checkout = false ;
        }
    }

    /**
     * To Create account for Guest.
     */
    public static function force_create_account_for_guest() {
        if ( ! is_user_logged_in() && is_checkout() && _sumo_pp_cart_has_payment_items() ) {
            $_POST[ 'createaccount' ] = 1 ;
        }
    }

    /**
     * Handle payment gateways in checkout
     * @param array $_available_gateways
     * @return array
     */
    public static function set_payment_gateways( $_available_gateways ) {
        $disabled_payment_gateways = get_option( _sumo_pp()->prefix . 'disabled_payment_gateways' ) ;

        if ( empty( $disabled_payment_gateways ) || ! _sumo_pp_cart_has_payment_items() ) {
            return $_available_gateways ;
        }

        foreach ( $_available_gateways as $gateway_name => $gateway ) {
            if ( ! isset( $gateway->id ) ) {
                continue ;
            }

            if ( in_array( $gateway->id , ( array ) $disabled_payment_gateways ) ) {
                unset( $_available_gateways[ $gateway_name ] ) ;
            }
        }
        return $_available_gateways ;
    }

    public static function add_order_item_meta_legacy( $item_id , $cart_item , $order_id ) {
        $meta_data = self::get_item_meta( $cart_item , true ) ;

        if ( ! empty( $meta_data ) ) {
            if ( ! empty( $meta_data[ 'plan_name' ] ) ) {
                wc_add_order_item_meta( $item_id , str_replace( ':' , '' , $meta_data[ 'payment_plan_label' ] ) , $meta_data[ 'plan_name' ] ) ;
            }
            if ( ! empty( $meta_data[ 'total_payable' ] ) ) {
                wc_add_order_item_meta( $item_id , __( 'Total payable' , _sumo_pp()->text_domain ) , $meta_data[ 'total_payable' ] ) ;
            }
            if ( ! empty( $meta_data[ 'next_payment_date' ] ) ) {
                wc_add_order_item_meta( $item_id , str_replace( ':' , '' , $meta_data[ 'due_date_label' ] ) , $meta_data[ 'next_payment_date' ] ) ;
            }
        }
    }

    public static function add_order_item_meta( $item , $cart_item_key , $values , $order ) {
        $meta_data = self::get_item_meta( $values , true ) ;

        if ( ! empty( $meta_data ) ) {
            if ( ! empty( $meta_data[ 'plan_name' ] ) ) {
                $item->add_meta_data( str_replace( ':' , '' , $meta_data[ 'payment_plan_label' ] ) , $meta_data[ 'plan_name' ] ) ;
            }
            if ( ! empty( $meta_data[ 'total_payable' ] ) ) {
                $item->add_meta_data( __( 'Total payable' , _sumo_pp()->text_domain ) , $meta_data[ 'total_payable' ] ) ;
            }
            if ( ! empty( $meta_data[ 'next_payment_date' ] ) ) {
                $item->add_meta_data( str_replace( ':' , '' , $meta_data[ 'due_date_label' ] ) , $meta_data[ 'next_payment_date' ] ) ;
            }
        }
    }

}

SUMO_PP_Frontend::init() ;

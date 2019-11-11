<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit ; // Exit if accessed directly
}

/**
 * Handle Normal products in cart as Order Payment Plan in Checkout.
 * 
 * @class SUMO_PP_Order_Payment_Plan
 * @category Class
 */
class SUMO_PP_Order_Payment_Plan {

    /**
     * Check whether the customer can proceed to deposit/payment plans in their checkout
     * @var bool 
     */
    public static $can_user_deposit_payment_in_checkout = false ;
    public static $get_options                          = array () ;
    public static $order_props                          = array () ;
    public static $default_order_props                  = array (
        'product_type'          => null ,
        'product_price'         => null ,
        'product_qty'           => null ,
        'payment_type'          => null ,
        'apply_global_settings' => null ,
        'force_deposit'         => null ,
        'deposit_type'          => null ,
        'deposit_price_type'    => null ,
        'fixed_deposit_percent' => null ,
        'min_deposit'           => null ,
        'max_deposit'           => null ,
        'pay_balance_type'      => null ,
        'pay_balance_after'     => null ,
        'selected_plans'        => null ,
        'order_items'           => null ,
        'deposited_amount'      => null ,
        'payment_plan_props'    => null ,
            ) ;

    /**
     * Init SUMO_PP_Order_Payment_Plan.
     */
    public static function init() {
        self::populate() ;

        add_action( 'woocommerce_' . self::$get_options[ 'form_position' ] , __CLASS__ . '::get_duration_fields' ) ;
        add_action( 'woocommerce_review_order_after_order_total' , __CLASS__ . '::get_payment_info' , 999 ) ;
        add_action( 'woocommerce_after_calculate_totals' , __CLASS__ . '::refresh_cart_totals' , 999 ) ;
        add_filter( 'woocommerce_cart_get_total' , __CLASS__ . '::set_cart_total' , 999 , 1 ) ;
        add_filter( 'woocommerce_cart_total' , __CLASS__ . '::set_total_payable_amount' , 999 , 1 ) ;
        add_action( 'woocommerce_after_checkout_validation' , __CLASS__ . '::validate_checkout' , 999 , 2 ) ;
        add_action( 'sumopaymentplans_checkout_update_customer_meta' , __CLASS__ . '::update_customer_meta' , 1 , 2 ) ;
        add_action( 'sumopaymentplans_checkout_update_customer_meta' , __CLASS__ . '::add_order_items' , 2 , 2 ) ;
        add_filter( 'sumopaymentplans_customer_has_payment_items_in_checkout' , __CLASS__ . '::maybe_customer_has_payment_items_in_checkout' , 1 , 2 ) ;
        add_filter( 'woocommerce_get_order_item_totals' , __CLASS__ . '::add_order_item_totals' , 999 , 3 ) ;
        add_action( 'woocommerce_admin_order_totals_after_total' , __CLASS__ . '::add_balance_payable_amount' ) ;
    }

    public static function populate() {

        extract( self::$get_options = array (
            'order_payment_plan_enabled' => null ,
            'payment_type'               => null ,
            'apply_global_settings'      => null ,
            'force_deposit'              => null ,
            'deposit_type'               => null ,
            'deposit_price_type'         => null ,
            'fixed_deposit_percent'      => null ,
            'min_deposit'                => null ,
            'max_deposit'                => null ,
            'pay_balance_type'           => null ,
            'pay_balance_after'          => null ,
            'pay_balance_before'         => null ,
            'selected_plans'             => null ,
            'labels'                     => null ,
            'form_position'              => null ,
        ) ) ;

        if ( $order_payment_plan_enabled = ('yes' === get_option( _sumo_pp()->prefix . 'enable_order_payment_plan' , 'no' )) ) {
            if ( ! _sumo_pp_current_user_can_purchase_payment( array (
                        'limit_by'            => get_option( _sumo_pp()->prefix . 'show_order_payment_plan_for' , 'all_users' ) ,
                        'filtered_users'      => ( array ) get_option( _sumo_pp()->prefix . 'get_limited_users_of_order_payment_plan' ) ,
                        'filtered_user_roles' => ( array ) get_option( _sumo_pp()->prefix . 'get_limited_userroles_of_order_payment_plan' ) ,
                    ) )
            ) {
                return self::$get_options ;
            }

            $payment_type          = get_option( _sumo_pp()->prefix . 'order_payment_type' , 'pay-in-deposit' ) ;
            $apply_global_settings = 'yes' === get_option( _sumo_pp()->prefix . 'apply_global_settings_for_order_payment_plan' , 'no' ) ;

            if ( 'pay-in-deposit' === $payment_type ) {
                $deposit_type = $apply_global_settings ? get_option( _sumo_pp()->prefix . 'deposit_type' , 'pre-defined' ) : get_option( _sumo_pp()->prefix . 'order_payment_plan_deposit_type' , 'pre-defined' ) ;

                if ( 'user-defined' === $deposit_type ) {
                    $min_deposit = $apply_global_settings ? floatval( get_option( _sumo_pp()->prefix . 'min_deposit' , '0.01' ) ) : floatval( get_option( _sumo_pp()->prefix . 'min_order_payment_plan_deposit' , '0.01' ) ) ;
                    $max_deposit = $apply_global_settings ? floatval( get_option( _sumo_pp()->prefix . 'max_deposit' , '99.99' ) ) : floatval( get_option( _sumo_pp()->prefix . 'max_order_payment_plan_deposit' , '99.99' ) ) ;
                } else {
                    $fixed_deposit_percent = $apply_global_settings ? floatval( get_option( _sumo_pp()->prefix . 'fixed_deposit_percent' , '50' ) ) : floatval( get_option( _sumo_pp()->prefix . 'fixed_order_payment_plan_deposit_percent' , '50' ) ) ;
                }
                if ( $apply_global_settings ) {
                    $pay_balance_type  = 'after' ;
                    $pay_balance_after = false === get_option( _sumo_pp()->prefix . 'balance_payment_due' ) ? absint( get_option( _sumo_pp()->prefix . 'pay_balance_after' ) ) : absint( get_option( _sumo_pp()->prefix . 'balance_payment_due' ) ) ;
                } else {
                    $pay_balance_type = get_option( _sumo_pp()->prefix . 'order_payment_plan_pay_balance_type' , 'after' ) ;

                    if ( 'after' === $pay_balance_type ) {
                        $pay_balance_after = absint( get_option( _sumo_pp()->prefix . 'order_payment_plan_pay_balance_after' , '1' ) ) ;
                    } else {
                        $pay_balance_before = get_option( _sumo_pp()->prefix . 'order_payment_plan_pay_balance_before' ) ;

                        if ( _sumo_pp_get_timestamp( $pay_balance_before ) <= _sumo_pp_get_timestamp( 0 , 0 , true ) ) {
                            return self::$get_options ;
                        }
                    }
                }
            } else if ( 'payment-plans' === $payment_type ) {
                $selected_plans = $apply_global_settings ? get_option( _sumo_pp()->prefix . 'selected_plans' , array () ) : get_option( _sumo_pp()->prefix . 'selected_plans_for_order_payment_plan' , array () ) ;
                $selected_plans = is_array( $selected_plans ) ? $selected_plans : array () ;
            }
            return self::$get_options = wp_parse_args( array (
                'order_payment_plan_enabled' => $order_payment_plan_enabled ,
                'payment_type'               => $payment_type ,
                'apply_global_settings'      => $apply_global_settings ? 'yes' : 'no' ,
                'force_deposit'              => $apply_global_settings ? ('payment-plans' === $payment_type ? get_option( _sumo_pp()->prefix . 'force_payment_plan' , 'no' ) : get_option( _sumo_pp()->prefix . 'force_deposit' , 'no' )) : get_option( _sumo_pp()->prefix . 'force_order_payment_plan' , 'no' ) ,
                'deposit_type'               => $deposit_type ,
                'deposit_price_type'         => 'percent-of-product-price' ,
                'fixed_deposit_percent'      => $fixed_deposit_percent ,
                'min_deposit'                => $min_deposit ,
                'max_deposit'                => $max_deposit ,
                'pay_balance_type'           => $pay_balance_type ,
                'pay_balance_after'          => $pay_balance_after ,
                'pay_balance_before'         => $pay_balance_before ,
                'selected_plans'             => $selected_plans ,
                'labels'                     => array (
                    'enable'         => get_option( _sumo_pp()->prefix . 'order_payment_plan_label' ) ,
                    'deposit_amount' => get_option( _sumo_pp()->prefix . 'pay_a_deposit_amount_label' ) ,
                    'payment_plans'  => get_option( _sumo_pp()->prefix . 'pay_with_payment_plans_label' ) ,
                ) ,
                'form_position'              => get_option( _sumo_pp()->prefix . 'order_payment_plan_form_position' , 'checkout_order_review' ) ,
                    ) , self::$get_options ) ;
        }
        return self::$get_options ;
    }

    public static function can_user_deposit_payment_in_checkout() {

        if ( self::$get_options[ 'order_payment_plan_enabled' ] ) {
            self::$can_user_deposit_payment_in_checkout = true ;

            foreach ( WC()->cart->cart_contents as $cart_item_key => $cart_item ) {
                if ( ! isset( $cart_item[ 'product_id' ] ) ) {
                    continue ;
                }
                $product_id = $cart_item[ 'variation_id' ] > 0 ? $cart_item[ 'variation_id' ] : $cart_item[ 'product_id' ] ;
                $cart_data  = SUMO_PP_Frontend::get_cart_session( $product_id ) ;

                if ( isset( $cart_data[ 'payment_product_props' ][ 'payment_type' ] ) ) {
                    return self::$can_user_deposit_payment_in_checkout = false ;
                } else if ( class_exists( 'SUMOSubscriptions' ) && function_exists( 'sumo_is_subscription_product' ) && sumo_is_subscription_product( $product_id ) ) {
                    return self::$can_user_deposit_payment_in_checkout = false ;
                } else if ( class_exists( 'SUMOMemberships' ) && function_exists( 'sumo_is_membership_product' ) && sumo_is_membership_product( $product_id ) ) {
                    return self::$can_user_deposit_payment_in_checkout = false ;
                }
            }
        }
        return self::$can_user_deposit_payment_in_checkout ;
    }

    public static function is_order_payment_plan_enabled() {
        if ( self::can_user_deposit_payment_in_checkout() && self::get_order_props() ) {
            return 'order' === self::$order_props[ 'product_type' ] ;
        }
        return false ;
    }

    public static function get_order_props() {

        $order_props = null ;
        if ( isset( WC()->session ) && is_callable( array ( WC()->session , 'get' ) ) ) {
            $order_props = WC()->session->get( _sumo_pp()->prefix . 'order_payment_plan_props' ) ;
        }

        return self::$order_props = wp_parse_args( is_array( $order_props ) ? $order_props : array () , self::$default_order_props ) ;
    }

    public static function get_total_payable_amount() {

        remove_filter( 'woocommerce_cart_get_total' , __CLASS__ . '::set_cart_total' , 999 , 1 ) ;

        $cart_total = WC()->cart->get_total( '' ) ;

        add_filter( 'woocommerce_cart_get_total' , __CLASS__ . '::set_cart_total' , 999 , 1 ) ;

        return floatval( $cart_total ) ;
    }

    public static function get_fixed_deposit_amount( $props = null ) {
        if ( is_null( $props ) ) {
            $props = self::$get_options ;
        }

        if (
                'pay-in-deposit' === $props[ 'payment_type' ] &&
                'pre-defined' === $props[ 'deposit_type' ]
        ) {
            if ( $cart_total = self::get_total_payable_amount() ) {
                return ($cart_total * floatval( $props[ 'fixed_deposit_percent' ] )) / 100 ;
            }
        }
        return '' ;
    }

    public static function get_user_defined_deposit_amount_range() {

        $min_amount = $max_amount = 0 ;
        if (
                'pay-in-deposit' === self::$get_options[ 'payment_type' ] &&
                'user-defined' === self::$get_options[ 'deposit_type' ]
        ) {
            if ( $cart_total = self::get_total_payable_amount() ) {
                $min_amount = ($cart_total * floatval( self::$get_options[ 'min_deposit' ] )) / 100 ;
                $max_amount = ($cart_total * floatval( self::$get_options[ 'max_deposit' ] )) / 100 ;
            }
        }
        return array (
            'min' => round( $min_amount , 2 ) ,
            'max' => round( $max_amount , 2 ) ,
                ) ;
    }

    public static function get_duration_fields() {

        if ( ! self::can_user_deposit_payment_in_checkout() ) {
            return ;
        }

        if ( in_array( self::$get_options[ 'payment_type' ] , array ( 'pay-in-deposit' , 'payment-plans' ) ) ) {
            ?>
            <table class="shop_table <?php echo _sumo_pp()->prefix . 'order_payment_type_fields' ; ?>">
                <tr>
                    <td>
                        <?php if ( 'yes' === self::$get_options[ 'force_deposit' ] ) { ?>
                            <input type="checkbox" id="<?php echo _sumo_pp()->prefix . 'enable_order_payment_plan' ; ?>" value="1" checked="checked" readonly="readonly" onclick="return false ;"/>
                        <?php } else { ?>
                            <input type="checkbox" id="<?php echo _sumo_pp()->prefix . 'enable_order_payment_plan' ; ?>" value="1"/>
                        <?php } ?>
                        <label><?php echo self::$get_options[ 'labels' ][ 'enable' ] ; ?></label>
                    </td>
                </tr>   
                <tr>
                    <?php if ( 'pay-in-deposit' === self::$get_options[ 'payment_type' ] ) { ?>
                        <td>                        
                            <label><?php echo self::$get_options[ 'labels' ][ 'deposit_amount' ] ; ?></label>
                            <input type="hidden" value="pay-in-deposit" id="<?php echo _sumo_pp()->prefix . 'payment_type' ; ?>"/>
                        </td>
                        <td id="<?php echo _sumo_pp()->prefix . 'amount_to_choose' ; ?>">
                            <?php if ( 'user-defined' === self::$get_options[ 'deposit_type' ] ) { ?>
                                <?php
                                $deposit_amount_range = self::get_user_defined_deposit_amount_range() ;
                                printf( __( 'Enter your Deposit Amount between %s and %s' , _sumo_pp()->text_domain ) , wc_price( $deposit_amount_range[ 'min' ] ) , wc_price( $deposit_amount_range[ 'max' ] ) ) ;
                                ?>
                                <input type="number" min="<?php echo floatval( $deposit_amount_range[ 'min' ] ) ; ?>" max="<?php echo floatval( $deposit_amount_range[ 'max' ] ) ; ?>" step="0.01" class="input-text" id="<?php echo _sumo_pp()->prefix . 'deposited_amount' ; ?>"/>
                            <?php } else { ?>
                                <?php echo wc_price( self::get_fixed_deposit_amount() ) ; ?>
                                <input type="hidden" value="<?php echo self::get_fixed_deposit_amount() ; ?>" id="<?php echo _sumo_pp()->prefix . 'deposited_amount' ; ?>"/>
                            <?php } ?>
                        </td>
                    <?php } else { ?>
                        <td>                       
                            <label><?php echo self::$get_options[ 'labels' ][ 'payment_plans' ] ; ?></label>
                            <input type="hidden" value="payment-plans" id="<?php echo _sumo_pp()->prefix . 'payment_type' ; ?>"/>
                        </td>                    
                        <td id="<?php echo _sumo_pp()->prefix . 'plans_to_choose' ; ?>">
                            <?php
                            $i = 1 ;
                            if ( is_array( self::$get_options[ 'selected_plans' ] ) ) {
                                foreach ( self::$get_options[ 'selected_plans' ] as $plan_id ) {
                                    if ( ! $plan = get_post( $plan_id ) ) {
                                        continue ;
                                    }
                                    ?>
                                    <p>
                                        <input type="radio" value="<?php echo absint( $plan_id ) ; ?>" id="<?php echo _sumo_pp()->prefix . 'chosen_payment_plan' ; ?>" name="<?php echo _sumo_pp()->prefix . 'chosen_payment_plan' ; ?>" <?php echo 1 === $i ? 'checked="checked"' : '' ?>/>
                                        <strong><?php echo $plan->post_title ; ?></strong><br>
                                        <?php echo get_post_meta( $plan_id , '_plan_description' , true ) ; ?>
                                    </p>                                    
                                    <?php
                                    $i ++ ;
                                }
                            }
                            ?>
                        </td>
                    <?php } ?>
                </tr>
            </table>
            <?php
        }
    }

    public static function get_payment_info() {

        if ( self::is_order_payment_plan_enabled() ) {
            if ( 'payment-plans' === self::$order_props[ 'payment_type' ] && isset( self::$order_props[ 'payment_plan_props' ][ 'plan_id' ] ) ) {
                $td1 = get_option( _sumo_pp()->prefix . 'payment_plan_label' ) . ' ' . get_post( self::$order_props[ 'payment_plan_props' ][ 'plan_id' ] )->post_title ;

                if ( $plan_description = self::$order_props[ 'payment_plan_props' ][ 'plan_description' ] ) {
                    $td1 .= sprintf( __( '<br><small style="color:#777;">%s</small>' ) , $plan_description ) ;
                }

                $from_time               = 0 ;
                $balance_payable_amount  = 0 ;
                $scheduled_payments_date = array () ;
                $total_payable_amount    = $initial_payment_amount  = (floatval( self::$order_props[ 'payment_plan_props' ][ 'initial_payment' ] ) * self::$order_props[ 'product_price' ]) / 100 ;

                if ( is_array( self::$order_props[ 'payment_plan_props' ][ 'payment_schedules' ] ) ) {
                    foreach ( self::$order_props[ 'payment_plan_props' ][ 'payment_schedules' ] as $schedule ) {
                        if ( ! isset( $schedule[ 'scheduled_payment' ] ) ) {
                            continue ;
                        }
                        $next_scheduled_payment = floatval( $schedule[ 'scheduled_payment' ] ) ;
                        $total_payable_amount += (self::$order_props[ 'product_price' ] * $next_scheduled_payment) / 100 ;
                        $balance_payable_amount += (self::$order_props[ 'product_price' ] * $next_scheduled_payment) / 100 ;

                        $scheduled_payment_cycle   = _sumo_pp_get_payment_cycle_in_days( $schedule[ 'scheduled_duration_length' ] , $schedule[ 'scheduled_period' ] ) ;
                        $scheduled_payments_date[] = $from_time                 = _sumo_pp_get_timestamp( "+{$scheduled_payment_cycle} days" , $from_time ) ;
                    }
                }
                $next_payment_date = isset( $scheduled_payments_date[ 0 ] ) ? $scheduled_payments_date[ 0 ] : 0 ;

                $td1 .= sprintf( __( '<br><small style="color:#777;">Total <strong>%s</strong> payable</small>' , _sumo_pp()->text_domain ) , wc_price( $total_payable_amount ) ) ;
                $td1 .= sprintf( __( '<br><small style="color:#777;">%s <strong>%s</strong></small>' ) , get_option( _sumo_pp()->prefix . 'next_payment_date_label' ) , _sumo_pp_get_date_to_display( $next_payment_date ) ) ;
                $td2 = sprintf( __( '<small style="color:#777;">Balance <strong>%s</strong> payable</small>' , _sumo_pp()->text_domain ) , wc_price( $balance_payable_amount ) ) ;
            } else {

                if ( 'before' === self::$order_props[ 'pay_balance_type' ] ) {
                    $next_payment_date = _sumo_pp_get_timestamp( self::$order_props[ 'pay_balance_before' ] ) ;
                    $due_date_label    = get_option( _sumo_pp()->prefix . 'balance_payment_due_date_label' ) ;
                } else {
                    $pay_balance_after = self::$order_props[ 'pay_balance_after' ] ; //in days
                    $next_payment_date = _sumo_pp_get_timestamp( "+{$pay_balance_after} days" ) ;
                    $due_date_label    = get_option( _sumo_pp()->prefix . 'next_payment_date_label' ) ;
                }

                $initial_payment_amount = self::$order_props[ 'deposited_amount' ] ;
                $balance_payable_amount = $initial_payment_amount > self::$order_props[ 'product_price' ] ? $initial_payment_amount - self::$order_props[ 'product_price' ] : self::$order_props[ 'product_price' ] - $initial_payment_amount ;

                $td1 = sprintf( __( '<small style="color:#777;">Total <strong>%s</strong> payable</small><br>' , _sumo_pp()->text_domain ) , wc_price( self::$order_props[ 'product_price' ] ) ) ;
                $td1 .= sprintf( __( '<small style="color:#777;">%s <strong>%s</strong></small>' ) , $due_date_label , _sumo_pp_get_date_to_display( $next_payment_date ) ) ;
                $td2 = sprintf( __( '<small style="color:#777;">Balance <strong>%s</strong> payable</small>' , _sumo_pp()->text_domain ) , wc_price( $balance_payable_amount ) ) ;
            }
            ?>            
            <tr class="<?php echo _sumo_pp()->prefix . 'order_payment_plan_info' ; ?>">
                <th>
                    <?php
                    echo __( 'Payable Now' , _sumo_pp()->text_domain ) . '<p style="font-weight:normal;text-transform:none;">' . $td1 . '</p>' ;
                    ?>
                </th>
                <td style="vertical-align: top;">
                    <?php
                    echo wc_price( $initial_payment_amount ) . '<p>' . $td2 . '</p>' ;
                    ?>
                </td>
            </tr>
            <?php
        }
    }

    public static function set_order_props( $new_deposited_amount = null , $chosen_payment_plan = null ) {
        $order_props = self::get_order_props() ;

        if ( is_null( $order_props[ 'product_type' ] ) ) {
            $items = array () ;
            foreach ( WC()->cart->cart_contents as $item ) {
                if ( ! isset( $item[ 'product_id' ] ) ) {
                    continue ;
                }

                $item_id           = $item[ 'variation_id' ] > 0 ? $item[ 'variation_id' ] : $item[ 'product_id' ] ;
                $items[ $item_id ] = array (
                    'price'             => _sumo_pp_get_product_price( $item[ 'data' ] ) ,
                    'qty'               => $item[ 'quantity' ] ,
                    'line_subtotal'     => $item[ 'line_subtotal' ] ,
                    'line_subtotal_tax' => $item[ 'line_subtotal_tax' ] ,
                    'line_total'        => $item[ 'line_total' ] ,
                    'line_tax'          => $item[ 'line_tax' ] ,
                        ) ;
            }

            $order_props = array_merge( self::$get_options , array (
                'product_type'       => 'order' ,
                'product_price'      => self::get_total_payable_amount() ,
                'product_qty'        => 1 ,
                'order_items'        => $items ,
                'deposited_amount'   => is_numeric( $new_deposited_amount ) ? floatval( $new_deposited_amount ) : null ,
                'payment_plan_props' => SUMO_PP_Frontend::get_payment_plan_props( $chosen_payment_plan ) ,
                    ) ) ;
            unset( $order_props[ 'order_payment_plan_enabled' ] , $order_props[ 'labels' ] , $order_props[ 'form_position' ] ) ;
        }
        return self::$order_props = $order_props ;
    }

    public static function refresh_cart_totals( $cart ) {
        if ( is_checkout() ) {
            if ( self::is_order_payment_plan_enabled() ) {
                WC()->session->__unset( _sumo_pp()->prefix . 'order_payment_plan_props' ) ;

                if ( is_numeric( self::$order_props[ 'deposited_amount' ] ) ) {
                    WC()->session->set( _sumo_pp()->prefix . 'order_payment_plan_props' , self::set_order_props( 'user-defined' === self::$order_props[ 'deposit_type' ] ? self::$order_props[ 'deposited_amount' ] : self::get_fixed_deposit_amount( self::$order_props )  ) ) ;
                } else {
                    WC()->session->set( _sumo_pp()->prefix . 'order_payment_plan_props' , self::set_order_props( null , self::$order_props[ 'payment_plan_props' ][ 'plan_id' ] ) ) ;
                }
            } else {
                WC()->session->__unset( _sumo_pp()->prefix . 'order_payment_plan_props' ) ;
            }
        }
    }

    public static function set_cart_total( $total ) {

        if ( is_checkout() && self::is_order_payment_plan_enabled() ) {
            if ( 'payment-plans' === self::$order_props[ 'payment_type' ] && isset( self::$order_props[ 'payment_plan_props' ][ 'plan_id' ] ) ) {
                $total = (floatval( self::$order_props[ 'payment_plan_props' ][ 'initial_payment' ] ) * self::$order_props[ 'product_price' ]) / 100 ;
            } else {
                $total = self::$order_props[ 'deposited_amount' ] ;
            }
        }
        return $total ;
    }

    public static function set_total_payable_amount( $total ) {

        if ( is_checkout() && self::can_user_deposit_payment_in_checkout() && isset( self::$order_props[ 'product_type' ] ) && 'order' === self::$order_props[ 'product_type' ] ) {
            $total = wc_price( self::$order_props[ 'product_price' ] ) ;
        }
        return $total ;
    }

    public static function validate_checkout( $data , $errors ) {

        if ( self::is_order_payment_plan_enabled() ) {
            if ( 'pay-in-deposit' === self::$order_props [ 'payment_type' ] ) {
                if ( ! is_numeric( self::$order_props [ 'deposited_amount' ] ) ) {
                    $errors->add( 'required-field' , sprintf( __( '<strong>%s</strong> is a required field.' , _sumo_pp()->text_domain ) , self::$get_options[ 'labels' ][ 'deposit_amount' ] ) ) ;
                } else if ( 'user-defined' === self::$order_props [ 'deposit_type' ] ) {
                    $deposit_amount = self::get_user_defined_deposit_amount_range() ;

                    if ( self::$order_props [ 'deposited_amount' ] < $deposit_amount[ 'min' ] || self::$order_props [ 'deposited_amount' ] > $deposit_amount[ 'max' ] ) {
                        $errors->add( 'required-field' , '' ) ;
                    }
                }
            }
        }
    }

    public static function update_customer_meta( $payment_order , $customer_id ) {

        if ( self::is_order_payment_plan_enabled() ) {
            delete_user_meta( $customer_id , _sumo_pp()->prefix . 'checkout_transient' ) ;

            if ( update_user_meta( $customer_id , _sumo_pp()->prefix . 'checkout_transient' , self::$order_props ) ) {
                WC()->session->__unset( _sumo_pp()->prefix . 'order_payment_plan_props' ) ;
            }
        }
    }

    public static function add_order_items( $payment_order , $customer_id ) {
        if ( empty( self::$order_props[ 'product_type' ] ) || 'order' !== self::$order_props[ 'product_type' ] ) {
            return ;
        }

        $payment_order->order->remove_order_items( 'line_item' ) ;

        $items = array () ;
        foreach ( WC()->cart->get_cart() as $item ) {
            if ( ! isset( $item[ 'product_id' ] ) ) {
                continue ;
            }

            $item_id           = $item[ 'variation_id' ] > 0 ? $item[ 'variation_id' ] : $item[ 'product_id' ] ;
            $items[ $item_id ] = array (
                'qty'     => $item[ 'quantity' ] ,
                'product' => $item[ 'data' ] ,
                    ) ;
        }

        if ( $items ) {
            foreach ( $items as $product_id => $item ) {
                $item_id = $payment_order->order->add_product( $item[ 'product' ] , 1 , array (
                    'name'     => get_option( _sumo_pp()->prefix . 'order_payment_plan_label' ) ,
                    'subtotal' => wc_get_price_excluding_tax( $item[ 'product' ] , array (
                        'qty'   => 1 ,
                        'price' => $payment_order->order->get_total() ,
                    ) ) ,
                    'total'    => wc_get_price_excluding_tax( $item[ 'product' ] , array (
                        'qty'   => 1 ,
                        'price' => $payment_order->order->get_total() ,
                    ) ) ,
                        ) ) ;

                if ( ! is_wp_error( $item_id ) && is_numeric( $item_id ) && $item_id ) {
                    foreach ( $items as $item ) {
                        wc_add_order_item_meta( $item_id , _sumo_pp_get_product_title( $item[ 'product' ] ) , 'x' . $item[ 'qty' ] ) ;
                    }
                    break ;
                }
            }
        }
    }

    public static function maybe_customer_has_payment_items_in_checkout( $bool , $customer_id ) {

        if (
                isset( SUMO_PP_Payment_Order::$customer_checkout_transient[ 'product_type' ] ) &&
                'order' === SUMO_PP_Payment_Order::$customer_checkout_transient[ 'product_type' ]
        ) {
            $bool = true ;
        }
        return $bool ;
    }

    public static function add_order_item_totals( $total_rows , $order , $tax_display ) {
        $order = _sumo_pp_get_order( $order ) ;

        if ( _sumo_pp_is_payment_order( $order ) && $order->is_parent() ) {
            $payments   = _sumo_pp()->query->get( array (
                'type'       => 'sumo_pp_payments' ,
                'status'     => array_keys( _sumo_pp_get_payment_statuses() ) ,
                'limit'      => 1 ,
                'meta_key'   => '_initial_payment_order_id' ,
                'meta_value' => $order->order_id ,
                    ) ) ;
            $payment_id = isset( $payments[ 0 ] ) ? $payments[ 0 ] : 0 ;

            if ( $payment_id && 'order' === get_post_meta( $payment_id , '_product_type' , true ) ) {
                $total_payable_amount   = _sumo_pp_get_total_payable_amount( $payment_id ) ;
                $balance_payable_amount = _sumo_pp_get_remaining_payable_amount( $payment_id ) ;

                if ( 'payment-plans' === get_post_meta( $payment_id , '_payment_type' , true ) ) {
                    $paid_amount = (floatval( get_post_meta( $payment_id , '_initial_payment' , true ) ) * $total_payable_amount) / 100 ;
                } else {
                    $paid_amount = get_post_meta( $payment_id , '_deposited_amount' , true ) ;
                }

                $total_rows[ 'order_total' ][ 'value' ]                   = wc_price( $total_payable_amount ) ;
                $total_rows[ _sumo_pp()->prefix . 'paid_now' ][ 'label' ] = __( 'Paid Now' , _sumo_pp()->text_domain ) ;
                $total_rows[ _sumo_pp()->prefix . 'paid_now' ][ 'value' ] = wc_price( $paid_amount ) . sprintf( __( '<p><small style="color:#777;">Balance <strong>%s</strong> payable</small></p>' , _sumo_pp()->text_domain ) , wc_price( $balance_payable_amount ) ) ;
            }
        }
        return $total_rows ;
    }

    public static function add_balance_payable_amount( $order_id ) {
        $payment_order = _sumo_pp_get_order( $order_id ) ;

        if ( _sumo_pp_is_payment_order( $payment_order ) && $payment_order->is_parent() ) {
            $payments   = _sumo_pp()->query->get( array (
                'type'       => 'sumo_pp_payments' ,
                'status'     => array_keys( _sumo_pp_get_payment_statuses() ) ,
                'limit'      => 1 ,
                'meta_key'   => '_initial_payment_order_id' ,
                'meta_value' => $order_id ,
                    ) ) ;
            $payment_id = isset( $payments[ 0 ] ) ? $payments[ 0 ] : 0 ;

            if ( $payment_id && 'order' === get_post_meta( $payment_id , '_product_type' , true ) ) {
                ?>
                <tr>
                    <td class="label"><?php esc_html_e( 'Balance Payable' , _sumo_pp()->text_domain ) ; ?>:</td>
                    <td width="1%"></td>
                    <td class="sumo_pp_balance_payable">
                        <?php echo wc_price( get_post_meta( $payment_id , '_remaining_payable_amount' , true ) , array ( 'currency' => $payment_order->get_currency() ) ) ; ?>
                    </td>
                </tr>
                <?php
            }
        }
    }

}

SUMO_PP_Order_Payment_Plan::init() ;

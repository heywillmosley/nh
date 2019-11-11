<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit ; // Exit if accessed directly
}

/**
 * Get SUMO Payment Plans templates.
 *
 * @param string $template_name
 * @param array $args (default: array())
 * @param string $template_path (default: 'SUMO_PP_PLUGIN_BASENAME_DIR')
 * @param string $default_path (default: SUMO_PP_PLUGIN_TEMPLATE_PATH)
 */
function _sumo_pp_get_template( $template_name , $args = array () , $template_path = SUMO_PP_PLUGIN_BASENAME_DIR , $default_path = SUMO_PP_PLUGIN_TEMPLATE_PATH ) {
    if ( ! $template_name ) {
        return ;
    }

    wc_get_template( $template_name , $args , $template_path , $default_path ) ;
}

/**
 * Alter Payment Plans Email Template directory.
 * @param string $template_directory
 * @param string $template
 * @return string
 */
function _sumo_pp_alter_wc_template_directory( $template_directory , $template ) {
    $email_templates = array (
        'payment-schedule' ,
        'payment-plan-invoice' ,
        'payment-plan-success' ,
        'payment-plan-completed' ,
        'payment-plan-overdue' ,
        'deposit-balance-payment-invoice' ,
        'deposit-balance-payment-completed' ,
        'deposit-balance-payment-overdue' ,
            ) ;

    foreach ( $email_templates as $template_name ) {
        if ( in_array( $template , array (
                    "emails/sumo-pp-{$template_name}.php" ,
                    "emails/plain/sumo-pp-{$template_name}.php" ,
                ) ) ) {
            $template_directory = untrailingslashit( SUMO_PP_PLUGIN_BASENAME_DIR ) ;
            break ;
        }
    }

    return $template_directory ;
}

add_filter( 'woocommerce_template_directory' , '_sumo_pp_alter_wc_template_directory' , 10 , 2 ) ;

/**
 * Display Payment Orders table
 * 
 * @param array $args
 * @param bool $echo
 * @return string echo table
 */
function _sumo_pp_get_payment_orders_table( $payment_id , $args = array () , $echo = true ) {

    $args                    = wp_parse_args( $args , array (
        'class'          => '' ,
        'id'             => '' ,
        'css'            => '' ,
        'custom_attr'    => '' ,
        'th_class'       => '' ,
        'th_css'         => '' ,
        'th_custom_attr' => '' ,
        'th_elements'    => array (
            'payments'              => __( 'Payments' , _sumo_pp()->text_domain ) ,
            'installment-amount'    => __( 'Installment Amount' , _sumo_pp()->text_domain ) ,
            'expected-payment-date' => __( 'Expected Payment Date' , _sumo_pp()->text_domain ) ,
            'actual-payment-date'   => __( 'Actual Payment Date' , _sumo_pp()->text_domain ) ,
            'order-number'          => __( 'Order Number' , _sumo_pp()->text_domain ) ,
        ) ,
        'page'           => 'admin' ,
            ) ) ;
    $payment_type            = get_post_meta( $payment_id , '_payment_type' , true ) ;
    $actual_payments_date    = get_post_meta( $payment_id , '_actual_payments_date' , true ) ;
    $payment_schedules       = get_post_meta( $payment_id , '_payment_schedules' , true ) ;
    $scheduled_payments_date = get_post_meta( $payment_id , '_scheduled_payments_date' , true ) ;
    $balance_paid_orders     = _sumo_pp_get_balance_paid_orders( $payment_id ) ;
    $product_title           = _sumo_pp_get_formatted_payment_product_title( $payment_id ) ;
    $column_keys             = array_keys( $args[ 'th_elements' ] ) ;

    ob_start() ;
    ?>
    <table class="<?php echo esc_attr( $args[ 'class' ] ) ; ?>" <?php echo esc_attr( $args[ 'custom_attr' ] ) ; ?> style="<?php echo esc_attr( $args[ 'css' ] ) ; ?>">
        <thead>
            <tr>
                <?php foreach ( $args[ 'th_elements' ] as $column_name ) : ?>
                    <th class="<?php echo esc_attr( $args[ 'th_class' ] ) ; ?>" <?php echo esc_attr( $args[ 'th_custom_attr' ] ) ; ?> style="<?php echo esc_attr( $args[ 'th_css' ] ) ; ?>"><?php echo $column_name ; ?></th>
                <?php endforeach ; ?>
            </tr>
        </thead>
        <tbody>
            <?php
            if ( 'pay-in-deposit' === $payment_type ) {
                $balance_paid_order = isset( $balance_paid_orders[ 0 ] ) ? $balance_paid_orders[ 0 ] : 0 ;

                if ( 'admin' === $args[ 'page' ] ) {
                    $url = admin_url( "post.php?post={$balance_paid_order}&action=edit" ) ;
                } else {
                    $url = wc_get_endpoint_url( 'view-order' , $balance_paid_order , wc_get_page_permalink( 'myaccount' ) ) ;
                }
                ?>
                <tr>
                    <?php if ( in_array( 'payments' , $column_keys ) ) { ?>
                        <td>
                            <?php
                            if ( $balance_paid_order > 0 ) {
                                printf( __( '<a href="%s">Installment #1 of %s</a>' ) , $url , $product_title ) ;
                            } else {
                                printf( __( 'Installment #1 of %s' ) , $product_title ) ;
                            }
                            ?>
                        </td>
                    <?php } ?>
                    <?php if ( in_array( 'installment-amount' , $column_keys ) ) { ?>
                        <td>
                            <?php
                            if ( $next_installment_amount = get_post_meta( $payment_id , '_next_installment_amount' , true ) ) {
                                echo wc_price( $next_installment_amount ) ;
                            } else {
                                $product_price    = floatval( get_post_meta( $payment_id , '_product_price' , true ) ) ;
                                $deposited_amount = floatval( get_post_meta( $payment_id , '_deposited_amount' , true ) ) ;
                                echo wc_price( $product_price - $deposited_amount ) ;
                            }
                            ?>
                        </td>
                    <?php } ?>
                    <?php if ( in_array( 'expected-payment-date' , $column_keys ) ) { ?>
                        <td>
                            <?php
                            if ( $next_payment_date = get_post_meta( $payment_id , '_next_payment_date' , true ) ) {
                                echo _sumo_pp_get_date_to_display( $next_payment_date ) ;
                            } else {
                                if ( 'before' === get_post_meta( $payment_id , '_pay_balance_type' , true ) ) {
                                    echo _sumo_pp_get_date_to_display( _sumo_pp_get_timestamp( get_post_meta( $payment_id , '_pay_balance_before' , true ) ) ) ;
                                } else {
                                    echo '--' ;
                                }
                            }
                            ?>
                        </td>
                    <?php } ?>
                    <?php if ( in_array( 'actual-payment-date' , $column_keys ) ) { ?>
                        <td>
                            <?php
                            if ( isset( $actual_payments_date[ 0 ] ) && $actual_payments_date[ 0 ] ) {
                                echo _sumo_pp_get_date_to_display( $actual_payments_date[ 0 ] ) ;
                            } else {
                                echo '--' ;
                            }
                            ?>
                        </td>
                    <?php } ?>
                    <?php if ( in_array( 'order-number' , $column_keys ) ) { ?>
                        <td>
                            <?php
                            if ( $balance_paid_order > 0 ) {
                                printf( __( '<a href="%s">#%s</a><p>Paid</p>' , _sumo_pp()->text_domain ) , $url , $balance_paid_order ) ;
                            } else {
                                if ( 'admin' !== $args[ 'page' ] && _sumo_pp_is_balance_payable_order_exists( $payment_id ) ) {
                                    $balance_payable_order = _sumo_pp_get_order( get_post_meta( $payment_id , '_balance_payable_order_id' , true ) ) ;
                                    printf( __( '<a class="button" href="%s">Pay for #%s</a>' , _sumo_pp()->text_domain ) , esc_url( $balance_payable_order->order->get_checkout_payment_url() ) , $balance_payable_order->order_id ) ;
                                } else {
                                    echo '--' ;
                                }
                            }
                            ?>
                        </td>
                    <?php } ?>
                </tr>
                <?php
            } else {
                if ( is_array( $payment_schedules ) ) {
                    foreach ( $payment_schedules as $installment => $schedule ) {
                        if ( ! isset( $schedule[ 'scheduled_payment' ] ) ) {
                            continue ;
                        }
                        $balance_paid_order = isset( $balance_paid_orders[ $installment ] ) ? $balance_paid_orders[ $installment ] : 0 ;

                        if ( 'admin' === $args[ 'page' ] ) {
                            $url = admin_url( "post.php?post={$balance_paid_order}&action=edit" ) ;
                        } else {
                            $url = wc_get_endpoint_url( 'view-order' , $balance_paid_order , wc_get_page_permalink( 'myaccount' ) ) ;
                        }
                        ?>
                        <tr>
                            <?php if ( in_array( 'payments' , $column_keys ) ) { ?>
                                <td>
                                    <?php
                                    $payment_count = $installment ;

                                    if ( $balance_paid_order > 0 ) {
                                        printf( __( '<a href="%s">Installment #%s of %s</a>' ) , $url , ++ $payment_count , $product_title ) ;
                                    } else {
                                        printf( __( 'Installment #%s of %s' ) , ++ $payment_count , $product_title ) ;
                                    }
                                    ?>
                                </td>
                            <?php } ?>
                            <?php if ( in_array( 'installment-amount' , $column_keys ) ) { ?>
                                <td>
                                    <?php
                                    if ( isset( $schedule[ 'scheduled_payment' ] ) ) {
                                        if ( 'fixed-price' === get_post_meta( $payment_id , '_plan_price_type' , true ) ) {
                                            echo wc_price( $schedule[ 'scheduled_payment' ] ) ;
                                        } else {
                                            echo wc_price( (floatval( get_post_meta( $payment_id , '_product_price' , true ) ) * floatval( $schedule[ 'scheduled_payment' ] ) ) / 100 ) ;
                                        }
                                    } else {
                                        echo wc_price( '0' ) ;
                                    }
                                    ?>
                                </td>
                            <?php } ?>
                            <?php if ( in_array( 'expected-payment-date' , $column_keys ) ) { ?>
                                <td>
                                    <?php
                                    if ( isset( $scheduled_payments_date[ $installment ] ) && $scheduled_payments_date[ $installment ] ) {
                                        echo _sumo_pp_get_date_to_display( $scheduled_payments_date[ $installment ] ) ;
                                    } else {
                                        echo '--' ;
                                    }
                                    ?>
                                </td>
                            <?php } ?>
                            <?php if ( in_array( 'actual-payment-date' , $column_keys ) ) { ?>
                                <td>
                                    <?php
                                    if ( isset( $actual_payments_date[ $installment ] ) && $actual_payments_date[ $installment ] ) {
                                        echo _sumo_pp_get_date_to_display( $actual_payments_date[ $installment ] ) ;
                                    } else {
                                        echo '--' ;
                                    }
                                    ?>
                                </td>
                            <?php } ?>
                            <?php if ( in_array( 'order-number' , $column_keys ) ) { ?>
                                <td>
                                    <?php
                                    if ( $balance_paid_order > 0 ) {
                                        printf( __( '<a href="%s">#%s</a><p>Paid</p>' , _sumo_pp()->text_domain ) , $url , $balance_paid_order ) ;
                                    } else {
                                        if ( 'admin' !== $args[ 'page' ] && empty( $balance_payable_order ) && _sumo_pp_is_balance_payable_order_exists( $payment_id ) ) {
                                            $balance_payable_order = _sumo_pp_get_order( get_post_meta( $payment_id , '_balance_payable_order_id' , true ) ) ;
                                            printf( __( '<a class="button" href="%s">Pay for #%s</a>' , _sumo_pp()->text_domain ) , esc_url( $balance_payable_order->order->get_checkout_payment_url() ) , $balance_payable_order->order_id ) ;
                                        } else {
                                            echo '--' ;
                                        }
                                    }
                                    ?>
                                </td>
                            <?php } ?>
                        </tr>
                        <?php
                    }
                }
            }
            ?>
        </tbody>
    </table>
    <?php
    if ( $echo ) {
        echo ob_get_clean() ;
    } else {
        return ob_get_clean() ;
    }
}

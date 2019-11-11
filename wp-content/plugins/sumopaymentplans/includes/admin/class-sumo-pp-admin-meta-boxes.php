<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit ; // Exit if accessed directly
}

/**
 * Handle Admin metaboxes.
 * 
 * @class SUMO_PP_Admin_Metaboxes
 * @category Class
 */
class SUMO_PP_Admin_Metaboxes {

    /**
     * SUMO_PP_Admin_Metaboxes constructor.
     */
    public function __construct() {
        add_action( 'add_meta_boxes' , array ( $this , 'add_meta_boxes' ) ) ;
        add_action( 'add_meta_boxes' , array ( $this , 'remove_meta_boxes' ) ) ;
        add_action( 'admin_head' , array ( $this , 'add_metaboxes_position' ) , 99999 ) ;
        add_action( 'post_updated_messages' , array ( $this , 'get_admin_post_messages' ) ) ;
        add_action( 'save_post' , array ( $this , 'save' ) , 1 , 3 ) ;
    }

    /**
     * Add Metaboxes.
     * @global object $post
     */
    public function add_meta_boxes() {
        add_meta_box( _sumo_pp()->prefix . 'plan_description' , __( 'Plan Description' , _sumo_pp()->text_domain ) , array ( $this , 'output_payment_plan_description' ) , 'sumo_payment_plans' , 'normal' , 'high' ) ;
        add_meta_box( _sumo_pp()->prefix . 'plan_creation' , __( 'Payment Plan' , _sumo_pp()->text_domain ) , array ( $this , 'output_payment_plan_creation' ) , 'sumo_payment_plans' , 'normal' , 'low' ) ;
        add_meta_box( _sumo_pp()->prefix . 'plan_balance_payable_orders_creation' , __( 'Payment Plan Orders Creation' , _sumo_pp()->text_domain ) , array ( $this , 'output_payment_plan_orders_creation' ) , 'sumo_payment_plans' , 'side' , 'low' ) ;
        add_meta_box( _sumo_pp()->prefix . 'payment_details' , __( 'Payment Details' , _sumo_pp()->text_domain ) , array ( $this , 'output_payment_details' ) , 'sumo_pp_payments' , 'normal' , 'high' ) ;
        add_meta_box( _sumo_pp()->prefix . 'payment_notes' , __( 'Payment Logs' , _sumo_pp()->text_domain ) , array ( $this , 'output_payment_notes' ) , 'sumo_pp_payments' , 'side' , 'low' ) ;
        add_meta_box( _sumo_pp()->prefix . 'email_actions' , __( 'Actions' , _sumo_pp()->text_domain ) , array ( $this , 'output_payment_email_actions' ) , 'sumo_pp_payments' , 'side' , 'high' ) ;
        add_meta_box( _sumo_pp()->prefix . 'payment_orders' , __( 'Payment Orders' , _sumo_pp()->text_domain ) , array ( $this , 'output_payment_orders' ) , 'sumo_pp_payments' , 'normal' , 'default' ) ;
        add_meta_box( _sumo_pp()->prefix . 'payment_item' , __( 'Payment Item' , _sumo_pp()->text_domain ) , array ( $this , 'output_payment_item' ) , 'sumo_pp_payments' , 'normal' , 'default' ) ;
    }

    /**
     * Remove Metaboxes.
     */
    public function remove_meta_boxes() {
        remove_meta_box( 'commentsdiv' , 'sumo_payment_plans' , 'normal' ) ;
        remove_meta_box( 'commentsdiv' , 'sumo_pp_payments' , 'normal' ) ;
        remove_meta_box( 'submitdiv' , 'sumo_pp_payments' , 'side' ) ;
    }

    /**
     * Set default Payment Plans metaboxes positions
     */
    public function add_metaboxes_position() {

        if ( 'sumo_pp_payments' === get_post_type() ) {
            if ( ! $user = wp_get_current_user() ) {
                return ;
            }

            if ( false === get_user_option( 'meta-box-order_sumo_pp_payments' , $user->ID ) ) {
                $prefix = _sumo_pp()->prefix ;
                delete_user_option( $user->ID , 'meta-box-order_sumo_pp_payments' , true ) ;
                update_user_option( $user->ID , 'meta-box-order_sumo_pp_payments' , array (
                    'side'     => "{$prefix}email_actions,{$prefix}payment_notes" ,
                    'normal'   => "{$prefix}payment_details,{$prefix}payment_item,{$prefix}payment_orders" ,
                    'advanced' => ''
                        ) , true ) ;
            }
            if ( false === get_user_option( 'screen_layout_sumo_pp_payments' , $user->ID ) ) {
                delete_user_option( $user->ID , 'screen_layout_sumo_pp_payments' , true ) ;
                update_user_option( $user->ID , 'screen_layout_sumo_pp_payments' , 'auto' , true ) ;
            }
        }
    }

    /**
     * Display updated Payment Plans post message.
     * @param array $messages
     * @return array
     */
    public function get_admin_post_messages( $messages ) {
        $messages[ 'sumo_payment_plans' ] = array (
            0 => '' , // Unused. Messages start at index 1.
            1 => __( 'Plan updated.' , _sumo_pp()->text_domain ) ,
            2 => __( 'Custom field(s) updated.' , _sumo_pp()->text_domain ) ,
            4 => __( 'Plan updated.' , _sumo_pp()->text_domain ) ,
                ) ;
        $messages[ 'sumo_pp_payments' ]   = array (
            0 => '' , // Unused. Messages start at index 1.
            1 => __( 'Payment updated.' , _sumo_pp()->text_domain ) ,
            2 => __( 'Custom field(s) updated.' , _sumo_pp()->text_domain ) ,
            4 => __( 'Payment updated.' , _sumo_pp()->text_domain ) ,
                ) ;

        return $messages ;
    }

    public function output_payment_plan_description( $post ) {
        ?>
        <p>
            <textarea cols="90" rows="5" name="<?php echo _sumo_pp()->prefix . 'plan_description' ; ?>" required="required" placeholder="<?php _e( 'Describe this plan about to customers' , _sumo_pp()->text_domain ) ?>"><?php echo get_post_meta( $post->ID , '_plan_description' , true ) ; ?></textarea>
        </p>
        <?php
    }

    public function output_payment_details( $post ) {
        wp_nonce_field( '_sumo_pp_save_data' , '_sumo_pp_meta_nonce' ) ;

        $payment_status        = _sumo_pp_get_payment_status( $post->ID ) ;
        $payment_type          = get_post_meta( $post->ID , '_payment_type' , true ) ;
        $product_qty           = absint( get_post_meta( $post->ID , '_product_qty' , true ) ) ;
        $balance_payable_order = _sumo_pp_get_order( get_post_meta( $post->ID , '_balance_payable_order_id' , true ) ) ;
        $initial_payment_order = _sumo_pp_get_order( get_post_meta( $post->ID , '_initial_payment_order_id' , true ) ) ;
        ?>
        <div class="panel-wrap sumopaymentplans">
            <input name="post_title" type="hidden" value="<?php echo empty( $post->post_title ) ? __( 'Payment' , _sumo_pp()->text_domain ) : esc_attr( $post->post_title ) ; ?>" />
            <input name="post_status" type="hidden" value="<?php echo esc_attr( $post->post_status ) ; ?>" />
            <div id="order_data" class="panel">
                <h2 style="float: left;"><?php echo esc_html( sprintf( __( '%s #%s details' , _sumo_pp()->text_domain ) , get_post_type_object( $post->post_type )->labels->singular_name , _sumo_pp_get_payment_number( $post->ID ) ) ) ; ?></h2>
                <?php
                printf( '<mark class="%s"/>%s</mark>' , $payment_status[ 'name' ] , esc_attr( $payment_status[ 'label' ] ) ) ;
                ?>                
                <p class="order_number" style="clear:both;"><?php
                    if ( $initial_payment_order ) {
                        if ( $payment_method = $initial_payment_order->get_payment_method() ) {
                            $payment_gateways = WC()->payment_gateways() ? WC()->payment_gateways->payment_gateways() : array () ;

                            printf( __( 'Payment via %s' , _sumo_pp()->text_domain ) , ( isset( $payment_gateways[ $payment_method ] ) ? esc_html( $payment_gateways[ $payment_method ]->get_title() ) : esc_html( $payment_method ) ) ) ;

                            if ( $transaction_id = $initial_payment_order->order->get_transaction_id() ) {
                                if ( isset( $payment_gateways[ $payment_method ] ) && ( $url = $payment_gateways[ $payment_method ]->get_transaction_url( $initial_payment_order->order ) ) ) {
                                    echo ' (<a href="' . esc_url( $url ) . '" target="_blank">' . esc_html( $transaction_id ) . '</a>)' ;
                                } else {
                                    echo ' (' . esc_html( $transaction_id ) . ')' ;
                                }
                            }
                            echo '. ' ;
                        }

                        if ( $ip_address = get_post_meta( $initial_payment_order->order_id , '_customer_ip_address' , true ) ) {
                            echo __( 'Customer IP' , _sumo_pp()->text_domain ) . ': ' . esc_html( $ip_address ) ;
                        }
                    }
                    ?>
                </p>                
                <div class="order_data_column_container">
                    <div class="order_data_column">
                        <h4>
                            <?php _e( 'Initial Payment Amount' , _sumo_pp()->text_domain ) ; ?>
                        </h4>
                        <p class="form-field form-field-wide">
                            <?php
                            if ( 'pay-in-deposit' === $payment_type ) {
                                echo wc_price( get_post_meta( $post->ID , '_deposited_amount' , true ) ) . ' x' . $product_qty ;
                            } else {
                                if ( 'fixed-price' === get_post_meta( $post->ID , '_plan_price_type' , true ) ) {
                                    echo wc_price( get_post_meta( $post->ID , '_initial_payment' , true ) ) . ' x' . $product_qty ;
                                } else {
                                    echo wc_price( (floatval( get_post_meta( $post->ID , '_initial_payment' , true ) ) * floatval( get_post_meta( $post->ID , '_product_price' , true ) )) / 100 ) . ' x' . $product_qty ;
                                }
                            }
                            ?>
                        </p><br>
                        <h4>
                            <?php _e( 'Initial Payment Order' , _sumo_pp()->text_domain ) ; ?>
                        </h4>
                        <p class="form-field form-field-wide">
                            <?php
                            if ( $initial_payment_order ) {
                                _e( "<a href=post.php?post={$initial_payment_order->order_id}&action=edit>#{$initial_payment_order->order_id}</a>" ) ;
                            }
                            ?>
                        </p><br>
                        <h4>
                            <?php _e( 'General Details' , _sumo_pp()->text_domain ) ; ?>
                        </h4>
                        <p class="form-field form-field-wide"><label for="order_date"><?php _e( 'Payment Start date:' , _sumo_pp()->text_domain ) ?></label>
                            <?php if ( $payment_start_date = get_post_meta( $post->ID , '_payment_start_date' , true ) ) { ?>
                                <input type="text" name="<?php echo _sumo_pp()->prefix . 'payment_start_date' ; ?>" value="<?php echo _sumo_pp_get_date_to_display( $payment_start_date ) ; ?>" readonly/>                                
                                <?php
                            } else {
                                echo '<b>' . __( 'Not Yet Started !!' , _sumo_pp()->text_domain ) . '</b>' ;
                            }
                            ?>
                        </p>
                        <p class="form-field form-field-wide"><label for="order_date"><?php _e( 'Payment End date:' , _sumo_pp()->text_domain ) ?></label>
                            <?php
                            if ( $payment_end_date = get_post_meta( $post->ID , '_payment_end_date' , true ) ) {
                                ?>
                                <input type="text" name="<?php echo _sumo_pp()->prefix . 'payment_end_date' ; ?>" value="<?php echo _sumo_pp_get_date_to_display( $payment_end_date ) ; ?>" readonly/>
                                <?php
                            } else if ( 'pay-in-deposit' === $payment_type || _sumo_pp_payment_has_status( $post->ID , 'await_aprvl' ) ) {
                                echo '<b>--</b>' ;
                            } else {
                                echo '<b>' . __( 'Payment Ended !!' , _sumo_pp()->text_domain ) . '</b>' ;
                            }
                            ?>
                        </p>
                        <p class="form-field form-field-wide"><label for="order_date"><?php _e( 'Next Payment date:' , _sumo_pp()->text_domain ) ?></label>
                            <?php
                            if ( $next_payment_date = get_post_meta( $post->ID , '_next_payment_date' , true ) ) {
                                ?>
                                <input type="text" name="<?php echo _sumo_pp()->prefix . 'next_payment_date' ; ?>" value="<?php echo _sumo_pp_get_date_to_display( $next_payment_date ) ; ?>" readonly/>
                                <?php
                            } else {
                                echo '<b>--</b>' ;
                            }
                            ?>
                        </p>
                        <p class="form-field form-field-wide">
                            <?php
                            if ( _sumo_pp_payment_has_status( $post->ID , array ( 'pending' , 'await_aprvl' , 'in_progress' , 'overdue' ) ) ) {
                                ?>
                                <label for="order_status"><?php _e( 'Payment Status:' , _sumo_pp()->text_domain ) ?></label>
                                <select class="wc-enhanced-select" id="payment_status" name="<?php echo _sumo_pp()->prefix . 'payment_status' ; ?>">
                                    <option><?php echo $payment_status[ 'label' ] ; ?></option>
                                    <optgroup label="<?php _e( 'Change to' , _sumo_pp()->text_domain ) ; ?>">
                                        <?php
                                        $payment_statuses = _sumo_pp_get_payment_statuses() ;
                                        $statuses         = array ( _sumo_pp()->prefix . 'cancelled' => $payment_statuses[ _sumo_pp()->prefix . 'cancelled' ] ) ;

                                        if ( _sumo_pp_payment_has_status( $post->ID , 'await_aprvl' ) ) {
                                            $statuses = array_merge( array ( _sumo_pp()->prefix . 'in_progress' => __( 'Activate Payment' , _sumo_pp()->text_domain ) ) , $statuses ) ;
                                        }

                                        if ( is_array( $statuses ) && $statuses ) {
                                            foreach ( $statuses as $status => $status_name ) {
                                                echo '<option value="' . esc_attr( $status ) . '" ' . selected( $status , $payment_status[ 'name' ] , false ) . '>' . esc_html( $status_name ) . '</option>' ;
                                            }
                                        }
                                        ?>
                                    </optgroup>
                                </select>
                                <?php
                            } else {
                                echo '<b>' . __( 'This Payment cannot be changed to any other status !!' , _sumo_pp()->text_domain ) . '</b>' ;
                            }
                            ?>
                        </p>
                        <p class="form-field form-field-wide">
                            <label for="customer_user"><?php _e( 'Customer:' , _sumo_pp()->text_domain ) ; ?></label>
                            <input type="text" required name="<?php echo _sumo_pp()->prefix . 'customer_email' ; ?>" placeholder="<?php esc_attr_e( 'Customer Email Address' , _sumo_pp()->text_domain ) ; ?>" value="<?php echo get_post_meta( $post->ID , '_customer_email' , true ) ; ?>" data-allow_clear="true" />
                        </p>
                        <?php
                        if ( $balance_payable_order && $balance_payable_order->has_status( array ( 'pending' , 'on-hold' ) ) ) :
                            ?>
                            <div class="view_next_payable_order" style="text-align:right;">
                                <a href="#"><?php _e( 'View Next Payable Order' , _sumo_pp()->text_domain ) ?></a>
                                <p style="font-weight: bolder;display: none;">
                                    <a href="<?php echo admin_url( "post.php?post={$balance_payable_order->order_id}&action=edit" ) ; ?>" title="Order #<?php echo $balance_payable_order->order_id ; ?>">#<?php echo $balance_payable_order->order_id ; ?></a>
                                </p>
                            </div>
                            <?php
                        endif ;
                        ?>
                        <p class="form-field form-field-wide">
                            <label for="customer_user"><?php printf( __( 'Next Installment Amount: (%s)' , _sumo_pp()->text_domain ) , get_woocommerce_currency_symbol( $initial_payment_order ? $initial_payment_order->get_currency() : ''  ) ) ?></label>
                            <input type="text" name="<?php echo _sumo_pp()->prefix . 'next_installment_amount' ; ?>" value="<?php echo wc_format_decimal( get_post_meta( $post->ID , '_next_installment_amount' , true ) , '' ) ; ?>" data-allow_clear="true" readonly/>
                        </p>
                    </div>
                    <div class="order_data_column">
                        <h4>
                            <?php _e( 'Billing Details' , _sumo_pp()->text_domain ) ; ?>
                        </h4>
                        <div class="address">
                            <?php
                            if ( $initial_payment_order && $initial_payment_order->order->get_formatted_billing_address() ) {
                                echo '<p><strong>' . __( 'Address' , _sumo_pp()->text_domain ) . ':</strong>' . wp_kses( $initial_payment_order->order->get_formatted_billing_address() , array ( 'br' => array () ) ) . '</p>' ;
                            } else {
                                echo '<p class="none_set"><strong>' . __( 'Address' , _sumo_pp()->text_domain ) . ':</strong> ' . __( 'No billing address set.' , _sumo_pp()->text_domain ) . '</p>' ;
                            }
                            ?>
                        </div>
                    </div>
                    <div class="order_data_column">
                        <h4>
                            <?php _e( 'Shipping Details' , _sumo_pp()->text_domain ) ; ?>
                        </h4>
                        <div class="address">
                            <?php
                            if ( $initial_payment_order && $initial_payment_order->order->get_formatted_shipping_address() ) {
                                echo '<p><strong>' . __( 'Address' , _sumo_pp()->text_domain ) . ':</strong>' . wp_kses( $initial_payment_order->order->get_formatted_shipping_address() , array ( 'br' => array () ) ) . '</p>' ;
                            } else {
                                echo '<p class="none_set"><strong>' . __( 'Address' , _sumo_pp()->text_domain ) . ':</strong> ' . __( 'No shipping address set.' , _sumo_pp()->text_domain ) . '</p>' ;
                            }
                            ?>
                        </div>
                    </div>                    
                </div>
                <div class="clear"></div>
            </div>
        </div>
        <?php
    }

    public function output_payment_plan_creation( $post ) {
        $price_type           = get_post_meta( $post->ID , '_price_type' , true ) ;
        $initial_payment      = get_post_meta( $post->ID , '_initial_payment' , true ) ;
        $payment_schedules    = get_post_meta( $post->ID , '_payment_schedules' , true ) ;
        $total_payment_amount = floatval( $initial_payment ) ;

        wp_nonce_field( '_sumo_pp_save_data' , '_sumo_pp_meta_nonce' ) ;
        ?>
        <div class="inside">    
            <p class="price_type">
                <label for="price-type"><?php _e( 'Price Type: ' , _sumo_pp()->text_domain ) ?></label>
                <select name="<?php echo _sumo_pp()->prefix . 'price_type' ; ?>">
                    <option value="percent" <?php selected( 'percent' === $price_type , true ) ?>><?php _e( 'Percentage' , _sumo_pp()->text_domain ) ?></option>
                    <option value="fixed-price" <?php selected( 'fixed-price' === $price_type , true ) ?>><?php _e( 'Fixed Price' , _sumo_pp()->text_domain ) ?></option>
                </select>
            </p>
            <input type="hidden" id="<?php echo _sumo_pp()->prefix . 'hidden_datas' ; ?>" data-currency_symbol="<?php echo get_woocommerce_currency_symbol() ; ?>"/>
            <table class="widefat wc_input_table <?php echo _sumo_pp()->prefix . 'footable' ; ?>" data-sort="false" data-filter="#filter" data-page-size="10" data-page-previous-text="prev" data-filter-text-only="true" data-page-next-text="next" >
                <thead>
                    <tr>
                        <th><?php _e( 'Payment Amount' , _sumo_pp()->text_domain ) ?></th>
                        <th><?php _e( 'Interval' , _sumo_pp()->text_domain ) ?></th>
                        <th>&nbsp;</th>
                    </tr>
                </thead>
                <tbody class="payment_schedules">
                    <tr>
                        <td>                           
                            <input class="payment_amount" type="number" min="0.00" step="0.01" required="required" name="<?php echo _sumo_pp()->prefix . 'initial_payment' ; ?>" value="<?php echo $initial_payment ; ?>"/><span><?php echo 'fixed-price' === $price_type ? get_woocommerce_currency_symbol() : '%' ; ?></span>
                        </td>
                        <td><?php _e( 'Initial Payment' , _sumo_pp()->text_domain ) ?></td>
                        <td></td>
                    </tr>
                    <?php
                    if ( is_array( $payment_schedules ) ) {
                        foreach ( $payment_schedules as $plan_row_id => $defined_plan ) {
                            $total_payment_amount += floatval( $defined_plan[ 'scheduled_payment' ] ) ;
                            ?>
                            <tr>
                                <td>                                   
                                    <input class="payment_amount" type="number" min="0.00" step="0.01" name="<?php echo _sumo_pp()->prefix . 'scheduled_payment[' . $plan_row_id . ']' ; ?>" value="<?php echo $defined_plan[ 'scheduled_payment' ] ; ?>"/><span><?php echo 'fixed-price' === $price_type ? get_woocommerce_currency_symbol() : '%' ; ?></span>
                                </td>
                                <td>
                                    <?php _e( 'After' , _sumo_pp()->text_domain ) ?>
                                    <input type="number" min="1" name="<?php echo _sumo_pp()->prefix . 'scheduled_duration_length[' . $plan_row_id . ']' ; ?>" value="<?php echo $defined_plan[ 'scheduled_duration_length' ] ; ?>"/>
                                    <select name="<?php echo _sumo_pp()->prefix . 'scheduled_period[' . $plan_row_id . ']' ; ?>">
                                        <?php foreach ( _sumo_pp_get_period_options() as $period => $label ) { ?>
                                            <option value="<?php echo $period ; ?>" <?php selected( $period === $defined_plan[ 'scheduled_period' ] , true ) ?>><?php echo $label ; ?></option>
                                        <?php } ?>
                                    </select>
                                </td>
                                <td><a href="#" class="remove_row button">X</a></td>
                            </tr>
                            <?php
                        }
                    }
                    ?>
                </tbody>
                <tfoot>
                    <tr>
                        <th><?php _e( 'Total Payment Amount: ' , _sumo_pp()->text_domain ) ; ?><span class="total_payment_amount"><?php echo 'fixed-price' === $price_type ? get_woocommerce_currency_symbol() . "$total_payment_amount" : "$total_payment_amount%" ; ?></span></th>
                        <th colspan="3"><a href="#" class="add button"><?php _e( 'Add Rule' , _sumo_pp()->text_domain ) ; ?></a> <span class="pagination hide-if-no-paging" style="float: right;"></span></th>
                    </tr>
                </tfoot>
            </table>
        </div>
        <?php
    }

    public function output_payment_plan_orders_creation( $post ) {
        $balance_payable_orders_creation = get_post_meta( $post->ID , '_balance_payable_orders_creation' , true ) ;
        ?>
        <select name="<?php echo _sumo_pp()->prefix . 'balance_payable_orders_creation' ; ?>">
            <option value="based_upon_settings" <?php selected( $balance_payable_orders_creation , 'based_upon_settings' ) ?>><?php _e( 'Based Upon Settings' , _sumo_pp()->text_domain ) ; ?></option>
            <option value="immediately_after_payment" <?php selected( $balance_payable_orders_creation , 'immediately_after_payment' ) ?>><?php _e( 'Immediately After Payment' , _sumo_pp()->text_domain ) ; ?></option>
        </select>
        <?php
    }

    public function output_payment_notes( $post ) {
        $notes = _sumo_pp_get_payment_notes( array (
            'payment_id' => $post->ID ,
                ) ) ;

        echo '<ul class="' . _sumo_pp()->prefix . 'payment_notes">' ;
        foreach ( $notes as $note ) {
            ?>
            <li rel="<?php echo absint( $note->id ) ; ?>" class="<?php echo isset( $note->meta[ 'comment_status' ] ) ? implode( $note->meta[ 'comment_status' ] ) : 'pending' ; ?>">
                <div class="note_content">
                    <?php echo wpautop( wptexturize( wp_kses_post( $note->content ) ) ) ; ?>
                </div>
                <p class="meta">
                    <abbr class="exact-date" title="<?php echo _sumo_pp_get_date_to_display( $note->date_created ) ; ?>"><?php echo _sumo_pp_get_date_to_display( $note->date_created ) ; ?></abbr>
                    <?php printf( ' ' . __( 'by %s' , _sumo_pp()->text_domain ) , $note->added_by ) ; ?>
                    <a href="#" class="delete_note"><?php _e( 'Delete note' , _sumo_pp()->text_domain ) ; ?></a>
                </p>
            </li>
            <?php
        }
        echo "</ul>" ;
        ?>
        <div class="<?php echo _sumo_pp()->prefix . 'add_payment_note' ; ?>">
            <h4>
                <?php _e( 'Add note' , _sumo_pp()->text_domain ) ; ?>
            </h4>
            <p>
                <textarea type="text" id="payment_note" class="input-text" cols="20" rows="3"></textarea>
            </p>
            <p>
                <a href="#" class="add_note button" data-id="<?php echo $post->ID ; ?>"><?php _e( 'Add' , _sumo_pp()->text_domain ) ; ?></a>
            </p>
        </div>
        <?php
    }

    public function output_payment_email_actions( $post ) {
        $payment_type             = get_post_meta( $post->ID , '_payment_type' , true ) ;
        $balance_payable_order_id = absint( get_post_meta( $post->ID , '_balance_payable_order_id' , true ) ) ;
        ?>
        <ul class="order_actions submitbox">
            <li class="wide" id="payment_email_actions">
                <select name="payment_email_actions" class="wc-enhanced-select wide">
                    <option value=""><?php _e( 'Actions' , _sumo_pp()->text_domain ) ; ?></option>
                    <optgroup label="<?php _e( 'Resend payment emails' , _sumo_pp()->text_domain ) ; ?>">
                        <?php
                        $mails                    = WC()->mailer()->get_emails() ;
                        $available_emails         = array ( _sumo_pp()->prefix . 'payment_schedule' , _sumo_pp()->prefix . 'payment_cancelled' ) ;

                        if ( $balance_payable_order_id > 0 ) {
                            if ( 'pay-in-deposit' === $payment_type ) {
                                $available_emails = array_merge( $available_emails , array ( _sumo_pp()->prefix . 'deposit_balance_payment_invoice' , _sumo_pp()->prefix . 'deposit_balance_payment_overdue' ) ) ;
                            } else {
                                $available_emails = array_merge( $available_emails , array ( _sumo_pp()->prefix . 'payment_plan_invoice' , _sumo_pp()->prefix . 'payment_plan_overdue' ) ) ;
                            }
                        }

                        if ( is_array( $mails ) && $mails ) {
                            foreach ( $mails as $mail ) {
                                if ( isset( $mail->id ) && in_array( $mail->id , $available_emails ) ) {
                                    echo '<option value="send_email_' . esc_attr( $mail->id ) . '">' . esc_html( $mail->title ) . '</option>' ;
                                }
                            }
                        }
                        ?>
                    </optgroup>
                </select>
            </li>
            <li class="wide">
                <div id="delete-action">
                    <?php
                    if ( current_user_can( 'delete_post' , $post->ID ) ) {
                        if ( ! EMPTY_TRASH_DAYS ) {
                            $delete_text = __( 'Delete Permanently' , _sumo_pp()->text_domain ) ;
                        } else {
                            $delete_text = __( 'Move to Trash' , _sumo_pp()->text_domain ) ;
                        }
                        ?>
                        <a class="submitdelete deletion" href="<?php echo esc_url( get_delete_post_link( $post->ID ) ) ; ?>"><?php echo $delete_text ; ?></a>
                        <?php
                    }
                    ?>
                </div>
                <input type="submit" class="button save_payments save_order button-primary tips" name="save" value="<?php printf( __( 'Save %s' , _sumo_pp()->text_domain ) , get_post_type_object( $post->post_type )->labels->singular_name ) ; ?>" data-tip="<?php printf( __( 'Save/update the %s' , _sumo_pp()->text_domain ) , get_post_type_object( $post->post_type )->labels->singular_name ) ; ?>" />
            </li>
        </ul>
        <?php
    }

    public function output_payment_orders( $post ) {
        ?>
        <div class="inside">
            <?php
            _sumo_pp_get_payment_orders_table( $post->ID , array (
                'class'       => 'widefat wc_input_table _sumo_pp_footable' ,
                'custom_attr' => 'data-sort=false data-filter=#filter data-page-size=10 data-page-previous-text=prev data-filter-text-only=true data-page-next-text=next' ,
            ) ) ;
            ?>            
            <div class="pagination pagination-centered"></div>
        </div>
        <?php
    }

    public function output_payment_item( $post ) {
        $order_id             = get_post_meta( $post->ID , '_initial_payment_order_id' , true ) ;
        $payment_product_id   = get_post_meta( $post->ID , '_product_id' , true ) ;
        $payment_product_type = get_post_meta( $post->ID , '_product_type' , true ) ;

        if ( $order = _sumo_pp_get_order( $order_id ) ) {
            include( 'views/html-order-items.php' ) ;
        }
    }

    /**
     * Save data.
     * @param int $post_id The post ID.
     * @param object $post The post object.
     * @param bool $update Whether this is an existing post being updated or not.
     */
    public function save( $post_id , $post , $update ) {
        // $post_id and $post are required
        if ( empty( $post_id ) || empty( $post ) ) {
            return ;
        }

        // Dont' save meta boxes for revisions or autosaves
        if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || is_int( wp_is_post_revision( $post ) ) || is_int( wp_is_post_autosave( $post ) ) ) {
            return ;
        }

        // Check the nonce
        if ( ! isset( $_POST[ '_sumo_pp_meta_nonce' ] ) || empty( $_POST[ '_sumo_pp_meta_nonce' ] ) || ! wp_verify_nonce( $_POST[ '_sumo_pp_meta_nonce' ] , '_sumo_pp_save_data' ) ) {
            return ;
        }

        // Check the post being saved == the $post_id to prevent triggering this call for other save_post events
        if ( empty( $_POST[ 'post_ID' ] ) || $_POST[ 'post_ID' ] != $post_id ) {
            return ;
        }

        // Check user has permission to edit
        if ( ! current_user_can( 'edit_post' , $post_id ) ) {
            return ;
        }

        switch ( $post->post_type ) {
            case 'sumo_payment_plans':
                $payment_schedules = array () ;

                if ( ! empty( $_POST[ _sumo_pp()->prefix . 'scheduled_payment' ] ) ) {

                    $scheduled_payment         = array_map( 'wc_clean' , $_POST[ _sumo_pp()->prefix . 'scheduled_payment' ] ) ;
                    $scheduled_duration_length = array_map( 'absint' , $_POST[ _sumo_pp()->prefix . 'scheduled_duration_length' ] ) ;
                    $scheduled_period          = array_map( 'wc_clean' , $_POST[ _sumo_pp()->prefix . 'scheduled_period' ] ) ;

                    foreach ( $scheduled_payment as $i => $payment ) {
                        if ( ! isset( $scheduled_payment[ $i ] ) || ! isset( $scheduled_duration_length[ $i ] ) || ! isset( $scheduled_period[ $i ] ) ) {
                            continue ;
                        }

                        $payment_schedules[] = array (
                            'scheduled_payment'         => $scheduled_payment[ $i ] ,
                            'scheduled_duration_length' => $scheduled_duration_length[ $i ] ,
                            'scheduled_period'          => $scheduled_period[ $i ] ,
                                ) ;
                    }
                }

                update_post_meta( $post_id , '_price_type' , $_POST[ _sumo_pp()->prefix . 'price_type' ] ) ;
                update_post_meta( $post_id , '_payment_schedules' , $payment_schedules ) ;
                update_post_meta( $post_id , '_plan_description' , isset( $_POST[ _sumo_pp()->prefix . 'plan_description' ] ) ? $_POST[ _sumo_pp()->prefix . 'plan_description' ] : ''  ) ;
                update_post_meta( $post_id , '_initial_payment' , isset( $_POST[ _sumo_pp()->prefix . 'initial_payment' ] ) ? wc_clean( $_POST[ _sumo_pp()->prefix . 'initial_payment' ] ) : ''  ) ;
                update_post_meta( $post_id , '_balance_payable_orders_creation' , $_POST[ _sumo_pp()->prefix . 'balance_payable_orders_creation' ] ) ;
                break ;
            case 'sumo_pp_payments':
                $initial_payment_order_id = absint( get_post_meta( $post_id , '_initial_payment_order_id' , true ) ) ;
                $balance_payable_order_id = absint( get_post_meta( $post_id , '_balance_payable_order_id' , true ) ) ;

                if ( ! empty( $_POST[ _sumo_pp()->prefix . 'payment_status' ] ) ) {
                    switch ( str_replace( _sumo_pp()->prefix , '' , $_POST[ _sumo_pp()->prefix . 'payment_status' ] ) ) {
                        case 'in_progress':
                            if ( _sumo_pp_update_payment_status( $post_id , 'in_progress' ) ) {

                                _sumo_pp_update_scheduled_payments_date( $post_id ) ;
                                _sumo_pp_add_payment_note( sprintf( __( 'Initial payment of order#%s is paid. Payment is in progress' , _sumo_pp()->text_domain ) , $initial_payment_order_id ) , $post_id , 'success' , __( 'Initial Payment Success' , _sumo_pp()->text_domain ) ) ;

                                $payment_type      = get_post_meta( $post_id , '_payment_type' , true ) ;
                                $next_payment_date = _sumo_pp_get_next_payment_date( $post_id ) ;

                                add_post_meta( $post_id , '_payment_start_date' , _sumo_pp_get_date() ) ;
                                add_post_meta( $post_id , '_last_payment_date' , _sumo_pp_get_date() ) ;
                                add_post_meta( $post_id , '_payment_end_date' , _sumo_pp_get_payment_end_date( $post_id ) ) ;
                                add_post_meta( $post_id , '_next_payment_date' , $next_payment_date ) ;

                                foreach ( _sumo_pp_get_order( $initial_payment_order_id )->get_item_meta( 'item' ) as $item_id => $item ) {
                                    $product_id = $item[ 'variation_id' ] > 0 ? $item[ 'variation_id' ] : $item[ 'product_id' ] ;

                                    if ( $product_id == get_post_meta( $post_id , '_product_id' , true ) && isset( $item[ 'item_meta' ] ) && is_array( $item[ 'item_meta' ] ) ) {
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
                                    $balance_payable_order_id = SUMO_PP_Payment_Order::create_balance_payable_order( $post_id ) ;

                                    if ( $next_payment_date && ($payment_cron = _sumo_pp_get_payment_cron( $post_id ) ) ) {
                                        if ( 'before' === get_post_meta( $post_id , '_pay_balance_type' , true ) ) {
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
                                    if ( $payment_cron = _sumo_pp_get_payment_cron( $post_id ) ) {
                                        $payment_cron->schedule_balance_payable_order( $next_payment_date ) ;
                                    }
                                }

                                _sumo_pp_send_payment_email( $post_id , 'payment_schedule' , $initial_payment_order_id ) ;

                                do_action( 'sumopaymentplans_payment_in_progress' , $post_id , $initial_payment_order_id , 'initial-payment-order' ) ;
                            }
                            break ;
                        case 'cancelled':
                            if ( _sumo_pp_update_payment_status( $post_id , 'cancelled' ) ) {

                                _sumo_pp_add_payment_note( __( 'Admin manually cancelled the payment.' , _sumo_pp()->text_domain ) , $post_id , 'failure' , __( 'Balance Payment Cancelled' , _sumo_pp()->text_domain ) ) ;

                                update_post_meta( $post_id , '_next_payment_date' , '' ) ;
                                update_post_meta( $post_id , '_next_installment_amount' , '0' ) ;
                                update_post_meta( $post_id , '_remaining_payable_amount' , '0' ) ;
                                update_post_meta( $post_id , '_remaining_installments' , '0' ) ;

                                if ( $payment_cron = _sumo_pp_get_payment_cron( $post_id ) ) {
                                    $payment_cron->unset_jobs() ;
                                }

                                _sumo_pp_send_payment_email( $post_id , 'payment_cancelled' , ($balance_payable_order_id ? $balance_payable_order_id : $initial_payment_order_id ) ) ;

                                do_action( 'sumopaymentplans_payment_is_cancelled' , $post_id , ($balance_payable_order_id ? $balance_payable_order_id : $initial_payment_order_id ) , $balance_payable_order_id ? 'balance-payment-order' : 'initial-payment-order'  ) ;
                            }
                            break ;
                    }
                }
                if ( ! empty( $_POST[ _sumo_pp()->prefix . 'customer_email' ] ) && $_POST[ _sumo_pp()->prefix . 'customer_email' ] !== get_post_meta( $post->ID , '_customer_email' , true ) ) {
                    if ( ! filter_var( $_POST[ _sumo_pp()->prefix . 'customer_email' ] , FILTER_VALIDATE_EMAIL ) === false ) {

                        update_post_meta( $post_id , '_customer_email' , $_POST[ _sumo_pp()->prefix . 'customer_email' ] ) ;

                        $note = sprintf( __( 'Admin has changed the payment customer email to %s. Customer will be notified via email by this Mail ID only.' , _sumo_pp()->text_domain ) , $_POST[ _sumo_pp()->prefix . 'customer_email' ] ) ;

                        _sumo_pp_add_payment_note( $note , $post_id , 'success' , __( 'Customer Email Changed Manually' , _sumo_pp()->text_domain ) ) ;
                    }
                }
                if ( ! empty( $_POST[ 'payment_email_actions' ] ) ) {
                    $action = wc_clean( $_POST[ 'payment_email_actions' ] ) ;

                    if ( strstr( $action , 'send_email_' ) ) {
                        // Ensure gateways are loaded in case they need to insert data into the emails
                        WC()->payment_gateways() ;
                        WC()->shipping() ;

                        $template_id = str_replace( _sumo_pp()->prefix , '' , str_replace( 'send_email_' , '' , $action ) ) ;
                        $order_id    = in_array( $template_id , array (
                                    'deposit_balance_payment_invoice' ,
                                    'deposit_balance_payment_overdue' ,
                                    'payment_plan_invoice' ,
                                    'payment_plan_overdue' ,
                                ) ) ? $balance_payable_order_id : $initial_payment_order_id ;

                        // Trigger mailer.
                        if ( $order_id ) {
                            _sumo_pp_send_payment_email( $post_id , $template_id , $order_id ) ;
                        }
                    }
                }
                break ;
        }
    }

}

new SUMO_PP_Admin_Metaboxes() ;

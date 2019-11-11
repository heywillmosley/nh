<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit ; // Exit if accessed directly
}

class SUMO_PP_Order {

    public $order_id = 0 ;
    public $order    = false ;

    public function __construct( $order ) {
        $this->get_order( $order ) ;
    }

    public function get_order( $order ) {

        if ( ! $order = wc_get_order( $order ) ) {
            return false ;
        }

        if ( $this->is_version( '<' , '3.0' ) ) {
            $this->order_id = $order->id ;
        } else {
            $this->order_id = $order->get_id() ;
        }
        $this->order = $order ;
        return $this ;
    }

    public function get_status() {
        if ( $this->is_version( '<' , '3.0' ) ) {
            return $this->order->status ;
        }
        return $this->order->get_status() ;
    }

    public function get_customer_id() {
        if ( $this->is_version( '<' , '3.0' ) ) {
            return $this->order->user_id ;
        }
        return $this->order->get_customer_id() ;
    }

    public function get_parent_id() {
        return wp_get_post_parent_id( $this->order_id ) > 0 ? wp_get_post_parent_id( $this->order_id ) : $this->order_id ;
    }

    public function get_currency() {
        if ( $this->is_version( '<' , '3.0' ) ) {
            return $this->order->get_order_currency() ;
        }
        return $this->order->get_currency() ;
    }

    public function get_payment_method() {
        if ( $this->is_version( '<' , '3.0' ) ) {
            return $this->order->payment_method ;
        }
        return $this->order->get_payment_method() ;
    }

    public function get_prices_include_tax() {
        if ( $this->is_version( '<' , '3.0' ) ) {
            return $this->order->prices_include_tax ;
        }
        return $this->order->get_prices_include_tax() ;
    }

    public function get_billing_email() {
        if ( $this->is_version( '<' , '3.0' ) ) {
            return $this->order->billing_email ;
        }
        return $this->order->get_billing_email() ;
    }

    public function get_billing_first_name() {
        if ( $this->is_version( '<' , '3.0' ) ) {
            return $this->order->billing_first_name ;
        }
        return $this->order->get_billing_first_name() ;
    }

    public function get_billing_last_name() {
        if ( $this->is_version( '<' , '3.0' ) ) {
            return $this->order->billing_last_name ;
        }
        return $this->order->get_billing_last_name() ;
    }

    public function get_order_key() {
        if ( $this->is_version( '<' , '3.0' ) ) {
            return $this->order->order_key ;
        }
        return $this->order->get_order_key() ;
    }

    public function get_order_date( $date_i18n = false ) {
        if ( $this->is_version( '<' , '3.0' ) ) {
            return $this->order->order_date ;
        }
        return $date_i18n ? $this->order->get_date_created()->date_i18n( 'Y-m-d H:i:s' ) : $this->order->get_date_created() ;
    }

    public function get_order_modified_date( $date_i18n = false ) {
        if ( $this->is_version( '<' , '3.0' ) ) {
            return $this->order->modified_date ;
        }
        return $date_i18n ? $this->order->get_date_modified()->date_i18n( 'Y-m-d H:i:s' ) : $this->order->get_date_modified() ;
    }

    public function get_renewal_orders() {
        if ( $this->is_invoice() ) {
            return false ;
        }

        return is_callable( 'get_children' ) ? get_children( array (
                    'post_parent' => $this->get_parent_id() ,
                    'post_type'   => 'shop_order' ,
                    'numberposts' => -1 ,
                    'post_status' => 'any' ,
                    'fields'      => 'ids'
                ) ) : array () ;
    }

    public function get_item_meta( $get_item_meta = '' ) {
        $get_item = array () ;

        foreach ( $this->order->get_items() as $item_id => $item ) {
            if ( ! isset( $item[ 'product_id' ] ) ) {
                continue ;
            }

            $product_id = $item[ 'variation_id' ] > 0 ? $item[ 'variation_id' ] : $item[ 'product_id' ] ;

            switch ( $get_item_meta ) {
                case 'item':
                    $get_item[ $item_id ]                = $item ;
                    break ;
                case 'item_total':
                    $get_item[ $item_id ][ $product_id ] = $item[ 'line_total' ] ;
                    break ;
                case 'item_qty':
                    $get_item[ $item_id ][ $product_id ] = $item[ 'qty' ] ;
                    break ;
                case 'item_title':
                    $get_item[ $product_id ]             = $item[ 'name' ] ;
                    break ;
                case 'item_attr':
                    $get_item[ $product_id ]             = $this->get_item_metadata( array ( 'order_item_id' => $item_id ) ) ;
                    break ;
                default:
                    $get_item[ $item_id ]                = $product_id ;
                    break ;
            }
        }
        return $get_item ;
    }

    public function get_item_metadata( $args = array () ) {

        $args = wp_parse_args( $args , array (
            'order_item_id' => 0 ,
            'order_item'    => '' ,
            'product'       => '' ,
            'to_display'    => false
                ) ) ;

        if ( $this->is_version( '<' , '3.0' ) ) {
            $meta_data = $this->order->has_meta( $args[ 'order_item_id' ] ) ;
        } else {
            if ( $args[ 'to_display' ] ) {
                $meta_data = $args[ 'order_item' ]->get_formatted_meta_data( '' ) ;
            } else {
                $meta_data = $this->order->get_meta_data( $args[ 'order_item_id' ] ) ;
            }
        }

        if ( empty( $meta_data ) || ! is_array( $meta_data ) ) {
            return ;
        }

        $item_metadata = array () ;
        $meta_key      = '' ;
        $meta_value    = '' ;

        if ( $args[ 'to_display' ] ) {
            echo '<table cellspacing="0" class="display_meta">' ;
        }

        foreach ( $meta_data as $meta ) {
            if ( ! $meta ) {
                continue ;
            }

            if ( $this->is_version( '<' , '3.0' ) ) {
                $meta_key   = $meta[ 'meta_key' ] ;
                $meta_value = $meta[ 'meta_value' ] ;
            } else {
                $meta_key   = $meta->key ;
                $meta_value = $meta->value ;
            }

            if ( in_array( $meta_key , array (
                        '_qty' ,
                        '_tax_class' ,
                        '_product_id' ,
                        '_variation_id' ,
                        '_line_subtotal' ,
                        '_line_subtotal_tax' ,
                        '_line_total' ,
                        '_line_tax' ,
                    ) ) ) {
                continue ;
            }
            if ( is_serialized( $meta_value ) ) {
                continue ;
            }
            if ( taxonomy_exists( wc_sanitize_taxonomy_name( $meta_key ) ) ) {
                $term       = get_term_by( 'slug' , $meta_value , wc_sanitize_taxonomy_name( $meta_key ) ) ;
                $meta_key   = wc_attribute_label( wc_sanitize_taxonomy_name( $meta_key ) ) ;
                $meta_value = isset( $term->name ) ? $term->name : $meta_value ;
            } else {
                $meta_key = wc_attribute_label( $meta_key , $args[ 'product' ] ) ;
            }

            $item_metadata[ $meta_key ] = $meta_value ;

            if ( $args[ 'to_display' ] ) {
                if ( $this->is_version( '<' , '3.0' ) ) {
                    echo '<tr><th>' . wp_kses_post( rawurldecode( $meta_key ) ) . ':</th><td>' . wp_kses_post( wpautop( make_clickable( rawurldecode( $meta_value ) ) ) ) . '</td></tr>' ;
                } else {
                    echo '<tr><th>' . wp_kses_post( $meta->display_key ) . ':</th><td>' . wp_kses_post( force_balance_tags( $meta->display_value ) ) . '</td></tr>' ;
                }
            }
        }

        if ( $args[ 'to_display' ] ) {
            echo '</table>' ;
        } else {
            return $item_metadata ;
        }
    }

    public function get_email_order_items_table( $args = array () ) {
        if ( $this->is_version( '<' , '3.0' ) ) {
            return $this->order->email_order_items_table( true ) ;
        }
        return wc_get_email_order_items( $this->order , $args ) ;
    }

    public function get_email_order_item_totals( $plain = false , $custom_totals = false ) {
        ob_start() ;

        if ( $custom_totals ) {
            $totals = $custom_totals ;
        } else {
            $totals = $this->order->get_order_item_totals() ;
        }

        if ( $totals ) :
            if ( $plain ) {
                foreach ( $totals as $total ) {
                    echo $total[ 'label' ] . "\t " . $total[ 'value' ] . "\n" ;
                }
            } else {
                $i = 0 ;

                foreach ( $totals as $total ) {
                    $i ++ ;
                    ?>
                    <tr>
                        <th class="td" scope="row" colspan="2" style="text-align:left; <?php if ( $i === 1 ) echo 'border-top-width: 4px;' ; ?>"><?php echo $total[ 'label' ] ; ?></th>
                        <td class="td" style="text-align:left; <?php if ( $i === 1 ) echo 'border-top-width: 4px;' ; ?>"><?php echo $total[ 'value' ] ; ?></td>
                    </tr>
                    <?php
                }
            }
        endif ;

        return ob_get_clean() ;
    }

    public function get_payment_items() {
        $payment_items = array () ;
        $order_items   = $this->get_item_meta() ;

        if ( ! sizeof( $order_items ) > 0 ) {
            return array () ;
        }

        if ( _sumo_pp_is_payment_order( $this->order ) ) {
            foreach ( $order_items as $order_item_id => $product_id ) {
                if ( $this->has_payment_product( $product_id ) ) {
                    $payment_items[] = $product_id ;
                }
            }
        } else {
            foreach ( $order_items as $order_item_id => $product_id ) {
                $product_props = SUMO_PP_Frontend::get_product_props( $product_id , $this->get_customer_id() ) ;

                if ( $product_id == $product_props[ 'product_id' ] ) {
                    $payment_items[] = $product_id ;
                }
            }
        }
        return $payment_items ;
    }

    public function exists() {
        return $this->order ? true : false ;
    }

    public function has_status( $status ) {
        return $this->order->has_status( $status ) ;
    }

    public function is_version( $comparison_opr , $version ) {
        return _sumo_pp_is_wc_version( $comparison_opr , $version ) ;
    }

    public function is_parent() {
        return 0 === wp_get_post_parent_id( $this->order_id ) ;
    }

    public function is_invoice() {
        return wp_get_post_parent_id( $this->order_id ) > 0 ? true : false ;
    }

    public function has_payment_product( $product_id ) {
        $payment = _sumo_pp()->query->get( array (
            'type'       => 'sumo_pp_payments' ,
            'status'     => array_keys( _sumo_pp_get_payment_statuses() ) ,
            'limit'      => 1 ,
            'meta_query' => array (
                'relation' => 'AND' ,
                array (
                    'key'     => '_product_id' ,
                    'value'   => $product_id ,
                    'compare' => 'LIKE' ,
                ) ,
                array (
                    'relation' => 'OR' ,
                    array (
                        'key'     => '_initial_payment_order_id' ,
                        'value'   => $this->order_id ,
                        'compare' => 'LIKE' ,
                    ) ,
                    array (
                        'key'     => '_balance_payable_order_id' ,
                        'value'   => $this->order_id ,
                        'compare' => 'LIKE' ,
                    ) ,
                ) ,
            ) ,
                ) ) ;
        return isset( $payment[ 0 ] ) && $payment[ 0 ] ;
    }

    public function reduce_order_stock() {
        if ( $this->is_version( '<' , '3.0' ) ) {
            return $this->order->reduce_order_stock() ;
        }
        return wc_reduce_stock_levels( $this->order ) ;
    }

    public function set_transaction_id( $transaction_id , $set_in_parent_order = false ) {
        if ( $this->is_version( '<' , '3.0' ) ) {
            update_post_meta( $this->order_id , '_transaction_id' , wc_clean( $transaction_id ) ) ;
        } else {
            $this->order->set_transaction_id( $transaction_id ) ;
            $this->order->save() ;
        }

        if ( $set_in_parent_order ) {
            if ( $this->is_version( '<' , '3.0' ) ) {
                update_post_meta( $this->get_parent_id() , '_transaction_id' , wc_clean( $transaction_id ) ) ;
            } else {
                if ( $parent_order = wc_get_order( $this->get_parent_id() ) ) {
                    $parent_order->set_transaction_id( $transaction_id ) ;
                    $parent_order->save() ;
                }
            }
        }
        return true ;
    }

}

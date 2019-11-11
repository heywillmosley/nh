<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit ; // Exit if accessed directly
}

/**
 * Handle SUMO Payment Plans Ajax Event.
 * 
 * @class SUMO_PP_Ajax
 * @category Class
 */
class SUMO_PP_Ajax {

    /**
     * Init SUMO_PP_Ajax.
     */
    public static function init() {
        //Get Ajax Events.
        $prefix      = SUMO_PP_PLUGIN_PREFIX ;
        $ajax_events = array (
            'add_payment_note'                       => false ,
            'delete_payment_note'                    => false ,
            'get_wc_booking_deposit_fields'          => true ,
            'get_payment_plan_search_field'          => false ,
            'checkout_order_payment_plan'            => true ,
            'bulk_update_product_meta'               => false ,
            'optimize_bulk_updation_of_product_meta' => false ,
            'json_search_sumo_payment_plans'         => false ,
                ) ;

        foreach ( $ajax_events as $ajax_event => $nopriv ) {
            add_action( "wp_ajax_{$prefix}{$ajax_event}" , __CLASS__ . "::{$ajax_event}" ) ;

            if ( $nopriv ) {
                add_action( "wp_ajax_nopriv_{$prefix}{$ajax_event}" , __CLASS__ . "::{$ajax_event}" ) ;
            }
        }
    }

    /**
     * Admin manually add payment notes.
     */
    public static function add_payment_note() {

        check_ajax_referer( 'sumo-pp-add-payment-note' , 'security' ) ;

        $note = _sumo_pp_add_payment_note( $_POST[ 'content' ] , $_POST[ 'post_id' ] , 'pending' , __( 'Admin Manually Added Note' , _sumo_pp()->text_domain ) ) ;

        if ( $note = _sumo_pp_get_payment_note( $note ) ) {
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
        die() ;
    }

    /**
     * Admin manually delete payment notes.
     */
    public static function delete_payment_note() {

        check_ajax_referer( 'sumo-pp-delete-payment-note' , 'security' ) ;

        wp_send_json( wp_delete_comment( $_POST[ 'delete_id' ] , true ) ) ;
    }

    public static function get_wc_booking_deposit_fields() {

        check_ajax_referer( 'sumo-pp-get-payment-type-fields' , 'security' ) ;

        $product_props        = SUMO_PP_Frontend::get_product_props( $_POST[ 'product' ] ) ;
        $can_add_booking_cost = false ;

        if ( class_exists( 'SUMO_PP_WC_Bookings' ) && SUMO_PP_WC_Bookings::can_add_booking_cost( $product_props ) ) {
            $can_add_booking_cost = true ;
        } else if ( class_exists( 'SUMO_PP_SUMOBookings' ) && SUMO_PP_SUMOBookings::can_add_booking_cost( $product_props ) ) {
            $can_add_booking_cost = true ;
        }

        if ( $can_add_booking_cost ) {
            wp_send_json( array (
                'result' => 'success' ,
                'html'   => SUMO_PP_Frontend::get_payment_type_fields() ,
            ) ) ;
        }

        wp_send_json( array (
            'result' => 'failure' ,
            'html'   => '' ,
        ) ) ;
    }

    /**
     * Get Payment Plan search field
     */
    public static function get_payment_plan_search_field() {

        check_ajax_referer( 'sumo-pp-get-payment-plan-search-field' , 'security' ) ;

        wp_send_json( array (
            'search_field' => _sumo_pp_wc_search_field( array (
                'class'       => 'wc-product-search' ,
                'action'      => '_sumo_pp_json_search_sumo_payment_plans' ,
                'id'          => isset( $_POST[ 'loop' ] ) ? "selected_{$_POST[ 'col' ]}_payment_plan_{$_POST[ 'rowID' ]}{$_POST[ 'loop' ]}" : "selected_{$_POST[ 'col' ]}_payment_plan_{$_POST[ 'rowID' ]}" ,
                'name'        => isset( $_POST[ 'loop' ] ) ? "_sumo_pp_selected_plans[{$_POST[ 'loop' ]}][{$_POST[ 'col' ]}][{$_POST[ 'rowID' ]}]" : "_sumo_pp_selected_plans[{$_POST[ 'col' ]}][{$_POST[ 'rowID' ]}]" ,
                'type'        => 'payment_plans' ,
                'selected'    => false ,
                'multiple'    => false ,
                'placeholder' => __( 'Search for a payment plan&hellip;' , _sumo_pp()->text_domain ) ,
                    ) , false ) ,
        ) ) ;
    }

    /**
     * Save order payment plan.
     */
    public static function checkout_order_payment_plan() {

        check_ajax_referer( 'sumo-pp-checkout-order-payment-plan' , 'security' ) ;

        if ( 'yes' === $_POST[ 'enabled' ] ) {
            $payment_type        = wc_clean( $_POST[ 'payment_type' ] ) ;
            $deposited_amount    = null ;
            $chosen_payment_plan = null ;

            switch ( $payment_type ) {
                case 'pay-in-deposit':
                    if ( isset( $_POST[ 'deposited_amount' ] ) ) {
                        $deposited_amount = $_POST[ 'deposited_amount' ] ;
                    }
                    break ;
                case 'payment-plans':
                    if ( isset( $_POST[ 'chosen_payment_plan' ] ) ) {
                        $chosen_payment_plan = $_POST[ 'chosen_payment_plan' ] ;
                    }
                    break ;
            }

            if ( ! is_null( $deposited_amount ) || ! is_null( $chosen_payment_plan ) ) {
                WC()->session->__unset( _sumo_pp()->prefix . 'order_payment_plan_props' ) ;
                WC()->session->set( _sumo_pp()->prefix . 'order_payment_plan_props' , SUMO_PP_Order_Payment_Plan::set_order_props( $deposited_amount , $chosen_payment_plan ) ) ;
            } else {
                WC()->session->set( _sumo_pp()->prefix . 'order_payment_plan_props' , array () ) ;
            }
        } else {
            WC()->session->set( _sumo_pp()->prefix . 'order_payment_plan_props' , array () ) ;
        }
        die() ;
    }

    /**
     * Process bulk update.
     */
    public static function bulk_update_product_meta() {

        check_ajax_referer( 'bulk-update-payment-plans' , 'security' ) ;

        if ( 'true' === $_POST[ 'is_bulk_update' ] ) {

            $products = get_posts( array (
                'post_type'      => 'product' ,
                'posts_per_page' => '-1' ,
                'post_status'    => 'publish' ,
                'fields'         => 'ids' ,
                'cache_results'  => false ,
                    ) ) ;

            if ( ! is_array( $products ) || ! $products ) {
                die() ;
            }

            update_option( 'bulk' . _sumo_pp()->prefix . 'get_product_select_type' , wc_clean( $_POST[ 'product_select_type' ] ) ) ;
            update_option( 'bulk' . _sumo_pp()->prefix . 'get_selected_categories' , wc_clean( $_POST[ 'selected_category' ] ) ) ;
            update_option( 'bulk' . _sumo_pp()->prefix . 'get_selected_products' , wc_clean( is_array( $_POST[ 'selected_products' ] ) ? $_POST[ 'selected_products' ] : explode( ',' , $_POST[ 'selected_products' ] )  ) ) ;

            switch ( get_option( 'bulk' . _sumo_pp()->prefix . 'get_product_select_type' ) ) {
                case 'all-products':
                    //Every Products published in the Site.
                    wp_send_json( $products ) ;
                    break ;
                case 'selected-products':
                    wp_send_json( get_option( 'bulk' . _sumo_pp()->prefix . 'get_selected_products' , array () ) ) ;
                    break ;
                case 'all-categories':
                    //All Categories.
                    $all_category_products = array () ;

                    foreach ( $products as $product_id ) {
                        $_product = wc_get_product( $product_id ) ;

                        if ( ! $_product ) {
                            continue ;
                        }

                        switch ( _sumo_pp_get_product_type( $_product ) ) {
                            case 'simple':
                                $terms = get_the_terms( $product_id , 'product_cat' ) ;

                                if ( ! is_array( $terms ) ) {
                                    continue ;
                                }

                                $all_category_products[] = $product_id ;
                                break ;
                            case 'variable':
                                $terms                   = get_the_terms( _sumo_pp_get_product_id( $_product ) , 'product_cat' ) ;

                                if ( ! is_array( $terms ) ) {
                                    continue ;
                                }

                                $variations = $_product->get_available_variations() ;

                                if ( is_array( $variations ) ) {
                                    foreach ( $variations as $variation_data ) {
                                        if ( ! isset( $variation_data[ 'variation_id' ] ) ) {
                                            continue ;
                                        }

                                        $all_category_products[] = $variation_data[ 'variation_id' ] ;
                                    }
                                }
                                break ;
                        }
                    }
                    wp_send_json( $all_category_products ) ;
                    break ;
                case 'selected-categories':
                    //Selected Categories.
                    $selected_categories        = get_option( 'bulk' . _sumo_pp()->prefix . 'get_selected_categories' , array () ) ;
                    $selected_category_products = array () ;

                    if ( ! is_array( $selected_categories ) || ! $selected_categories ) {
                        die() ;
                    }

                    foreach ( $products as $product_id ) {
                        $_product = wc_get_product( $product_id ) ;

                        if ( ! $_product ) {
                            continue ;
                        }

                        switch ( _sumo_pp_get_product_type( $_product ) ) {
                            case 'simple':
                                $terms = get_the_terms( $product_id , 'product_cat' ) ;

                                if ( ! is_array( $terms ) ) {
                                    continue ;
                                }

                                foreach ( $terms as $term ) {
                                    if ( ! in_array( $term->term_id , $selected_categories ) ) {
                                        continue ;
                                    }

                                    $selected_category_products[] = $product_id ;
                                    break ;
                                }
                                break ;
                            case 'variable':
                                $terms          = get_the_terms( _sumo_pp_get_product_id( $_product ) , 'product_cat' ) ;
                                $is_in_category = false ;

                                if ( ! is_array( $terms ) ) {
                                    continue ;
                                }

                                foreach ( $terms as $term ) {
                                    if ( ! in_array( $term->term_id , $selected_categories ) ) {
                                        continue ;
                                    }

                                    $is_in_category = true ;
                                    break ;
                                }

                                if ( ! $is_in_category ) {
                                    break ;
                                }

                                $variations = $_product->get_available_variations() ;

                                if ( is_array( $variations ) ) {
                                    foreach ( $variations as $variation_data ) {
                                        if ( ! isset( $variation_data[ 'variation_id' ] ) ) {
                                            continue ;
                                        }

                                        $selected_category_products[] = $variation_data[ 'variation_id' ] ;
                                    }
                                }
                                break ;
                        }
                    }
                    wp_send_json( $selected_category_products ) ;
                    break ;
            }
        }
        die() ;
    }

    /**
     * Optimize bulk update.
     */
    public static function optimize_bulk_updation_of_product_meta() {

        check_ajax_referer( 'bulk-update-optimization' , 'security' ) ;

        if ( ! empty( $_POST[ _sumo_pp()->prefix . 'selected_plans' ] ) ) {
            $selected_plans                                 = array () ;
            parse_str( $_POST[ _sumo_pp()->prefix . 'selected_plans' ] , $selected_plans ) ;
            $_POST[ _sumo_pp()->prefix . 'selected_plans' ] = isset( $selected_plans[ _sumo_pp()->prefix . 'selected_plans' ] ) ? $selected_plans[ _sumo_pp()->prefix . 'selected_plans' ] : '' ;
        }

        foreach ( SUMO_PP_Admin_Product_Settings::get_fields() as $field_name => $type ) {
            $meta_key         = _sumo_pp()->prefix . $field_name ;
            $posted_meta_data = isset( $_POST[ "$meta_key" ] ) ? $_POST[ "$meta_key" ] : '' ;

            if ( 'price' === $type ) {
                $posted_meta_data = wc_format_decimal( $posted_meta_data ) ;
            }
            if ( 'selected_plans' === $field_name && is_array( $posted_meta_data ) ) {
                $selected_plans = array () ;
                foreach ( array ( 'col_1' , 'col_2' ) as $column_id ) {
                    $selected_plans[ $column_id ] = ! empty( $posted_meta_data[ $column_id ] ) && is_array( $posted_meta_data[ $column_id ] ) ? array_map( 'implode' , (array_values( $posted_meta_data[ $column_id ] ) ) ) : array () ;
                }
                $posted_meta_data = $selected_plans ;
            }
            update_option( "bulk{$meta_key}" , wc_clean( $posted_meta_data ) ) ;
        }

        if ( is_array( $_POST[ 'ids' ] ) && $_POST[ 'ids' ] ) {
            $products = $_POST[ 'ids' ] ;

            foreach ( $products as $product_id ) {
                $_product = wc_get_product( $product_id ) ;

                if ( ! $_product ) {
                    continue ;
                }

                switch ( _sumo_pp_get_product_type( $_product ) ) {
                    case 'simple':
                    case 'variation':
                        SUMO_PP_Admin_Product_Settings::save_meta( $product_id ) ;
                        break ;
                    case 'variable':
                        $variations = $_product->get_available_variations() ;

                        if ( ! is_array( $variations ) ) {
                            continue ;
                        }

                        foreach ( $variations as $variation_data ) {
                            if ( ! isset( $variation_data[ 'variation_id' ] ) ) {
                                continue ;
                            }

                            SUMO_PP_Admin_Product_Settings::save_meta( $variation_data[ 'variation_id' ] ) ;
                        }
                        break ;
                }
            }
            wp_send_json( $products ) ;
        }
        die() ;
    }

    /**
     * Search SUMO Payment Plans
     */
    public static function json_search_sumo_payment_plans() {
        ob_start() ;

        $term    = ( string ) wc_clean( stripslashes( isset( $_GET[ 'term' ] ) ? $_GET[ 'term' ] : ''  ) ) ;
        $exclude = array () ;

        if ( isset( $_GET[ 'exclude' ] ) && ! empty( $_GET[ 'exclude' ] ) ) {
            $exclude = array_map( 'intval' , explode( ',' , $_GET[ 'exclude' ] ) ) ;
        }

        $args = array (
            'type'    => 'sumo_payment_plans' ,
            'status'  => 'publish' ,
            'return'  => 'posts' ,
            'order'   => 'ASC' ,
            'orderby' => 'parent title' ,
            's'       => $term ,
            'exclude' => $exclude ,
                ) ;

        $posts       = _sumo_pp()->query->get( $args ) ;
        $found_plans = array () ;

        if ( ! empty( $posts ) ) {
            foreach ( $posts as $post ) {
                $found_plans[ $post->ID ] = $post->post_title ;
            }
        }
        wp_send_json( $found_plans ) ;
    }

}

SUMO_PP_Ajax::init() ;

<?php if ( !defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( !class_exists( 'WWOF_WWP_Wholesale_Prices' ) ) {

    class WWOF_WWP_Wholesale_Prices {

        /*
        |--------------------------------------------------------------------------
        | Class Properties
        |--------------------------------------------------------------------------
        */

        /**
         * Property that holds the single main instance of WWOF_WWP_Wholesale_Prices.
         *
         * @since 1.6.6
         * @access private
         * @var WWOF_WWP_Wholesale_Prices
         */
        private static $_instance;

        /**
         * Model that houses the logic of retrieving information relating to WWOF Product Listings.
         *
         * @since 1.6.6
         * @access private
         * @var WWOF_Product_Listing
         */
        private $_wwof_product_listings;

        /*
        |--------------------------------------------------------------------------
        | Class Methods
        |--------------------------------------------------------------------------
        */

        /**
         * WWOF_WWP_Wholesale_Prices constructor.
         *
         * @param array $dependencies Array of instance objects of all dependencies of WWOF_WWP_Wholesale_Prices model.
         *
         * @access public
         * @since 1.6.6
         */
        public function __construct( $dependencies ) {

            $this->_wwof_product_listings = $dependencies[ 'WWOF_Product_Listing' ];

        }

        /**
         * Ensure that only one instance of WWOF_WWP_Wholesale_Prices is loaded or can be loaded (Singleton Pattern).
         *
         * @param array $dependencies Array of instance objects of all dependencies of WWOF_WWP_Wholesale_Prices model.
         *
         * @return WWOF_WWP_Wholesale_Prices
         * @since 1.6.6
         */
        public static function instance( $dependencies = null ) {

            if ( !self::$_instance instanceof self )
                self::$_instance = new self( $dependencies );

            return self::$_instance;

        }

        /**
         * Display wholesale price requirement message at the top of the search box wholesale ordering form.
         *
         * @return mixed
         *
         * @since 1.6.0
         * @since 1.6.1 Display the requirement only if the logged-in user is in the scope of the wwp registered custom user roles.
         * @since 1.6.6 Refactor codebase and move to its proper model
         */
        public function wwof_display_wholesale_price_requirement() {

            // Option to disable showing wholesale price requirement
            if( apply_filters( 'wwof_display_wholesale_price_requirement', true ) == false )
                return;

            global $current_user;

            $override_per_wholesale_role    = get_option( 'wwpp_settings_override_order_requirement_per_role' );
            $wwpp_order_requirement_mapping = get_option( 'wwpp_option_wholesale_role_order_requirement_mapping' );
            $current_roles                  = $current_user->roles;
            $wholesale_mapping              = array();
            $message                        = '';

            if( ! empty( $wwpp_order_requirement_mapping ) ){
                foreach( $wwpp_order_requirement_mapping as $userRole => $roleReq )
                    $wholesale_mapping[] = $userRole;
            }

            // Override per wholesale role
            if( ( ! empty( $override_per_wholesale_role ) && $override_per_wholesale_role === 'yes' ) && array_intersect( $current_roles , $wholesale_mapping ) ) {

                $current_user_role              = $current_roles[ 0 ];
                $wholesale_min_order_quantity   = $wwpp_order_requirement_mapping[ $current_user_role ][ 'minimum_order_quantity' ];
                $wholesale_min_order_price      = $wwpp_order_requirement_mapping[ $current_user_role ][ 'minimum_order_subtotal' ];
                $wholesale_min_req_logic        = $wwpp_order_requirement_mapping[ $current_user_role ][ 'minimum_order_logic' ];

                if( ! empty( $wholesale_min_order_quantity ) || ! empty( $wholesale_min_order_price ) )
                    $message = $this->wwof_get_wholesale_price_requirement_message( $wholesale_min_order_quantity, $wholesale_min_order_price, $wholesale_min_req_logic );

            }else{ // Use general setting

                $min_order_quantity = get_option( 'wwpp_settings_minimum_order_quantity' );
                $min_order_price    = get_option( 'wwpp_settings_minimum_order_price' );
                $min_req_logic      = get_option( 'wwpp_settings_minimum_requirements_logic' );

                $wwp_custom_roles = unserialize( get_option( 'wwp_options_registered_custom_roles' ) );
                $wholesale_role_keys = array();

                if( ! empty( $wwp_custom_roles ) ){
                    foreach( $wwp_custom_roles as $roleKey => $roleData )
                        $wholesale_role_keys[] = $roleKey;
                }

                if( ( ! empty( $min_order_quantity ) || ! empty( $min_order_price ) ) && array_intersect( $current_roles , $wholesale_role_keys ) )
                    $message = $this->wwof_get_wholesale_price_requirement_message( $min_order_quantity, $min_order_price, $min_req_logic );

            }

            if( ! empty( $message ) ){
                $notice = array( 'msg' => $message, 'type' => 'notice' );
                $notice = apply_filters( 'wwof_display_wholesale_price_requirement_notice_msg', $notice );

                wc_print_notice( $notice[ 'msg' ] , $notice[ 'type' ] );
            }
        }

        /**
         * Get the price of a product on shop pages with taxing applied (Meaning either including or excluding tax
         * depending on the settings of the shop).
         *
         * @since 1.4.1
         * @since 1.6.6 Refactor codebase and move to its proper model
         *
         * @param $product
         * @param $price
         * @param $wc_price_arg
         * @return mixed
         */
        public function wwof_get_product_shop_price_with_taxing_applied( $product , $price , $wc_price_arg = array() ) {

            $taxes_enabled                = get_option( 'woocommerce_calc_taxes' );
            $wholesale_tax_display_shop   = get_option( 'wwpp_settings_incl_excl_tax_on_wholesale_price' );
            $woocommerce_tax_display_shop = get_option( 'woocommerce_tax_display_shop' );

            if ( $taxes_enabled == 'yes' && $wholesale_tax_display_shop == 'incl'  )
                $filtered_price = wc_price( WWOF_Functions::wwof_get_price_including_tax( $product , array( 'qty' => 1 , 'price' => $price ) ) );
            elseif ( $wholesale_tax_display_shop == 'excl' )
                $filtered_price = wc_price( WWOF_Functions::wwof_get_price_excluding_tax( $product , array( 'qty' => 1 , 'price' => $price ) ) , $wc_price_arg );
            else {

                if ( $taxes_enabled == 'yes' && $woocommerce_tax_display_shop == 'incl' )
                    $filtered_price = wc_price( WWOF_Functions::wwof_get_price_including_tax( $product , array( 'qty' => 1 , 'price' => $price ) ) );
                else
                    $filtered_price = wc_price( WWOF_Functions::wwof_get_price_excluding_tax( $product , array( 'qty' => 1 , 'price' => $price ) ) , $wc_price_arg );

            }

            return apply_filters( 'wwpp_filter_product_shop_price_with_taxing_applied' , $filtered_price , $price , $product );

        }

        /**
         * Get product price.
         *
         * Version 1.3.2 change set:
         * We determine if a variation is active or not is by also checking the inventory status of the parent variable
         * product.
         *
         * @since 1.0.0
         * @since 1.3.0 Added feature to display wholesale price per order quantity as a list.
         * @since 1.3.2
         * @since 1.6.6 Refactor codebase and move to its proper model.
         * @since 1.7.0 Refactor codebase, remove unnecessary codes, make it more efficient and easy to maintain.
         * @since 1.8.1 Refactor codebase to allow support for changes on WWPP 1.16.1
         *
         * @param $product
         * @return string
         */
        public function wwof_get_product_price( $product ) {

            $discount_per_order_qty_html = "";
            $price_html                  = "";
            $hide_wholesale_discount     = get_option( "wwof_general_hide_quantity_discounts" ); // Option to hide Product Quantity Based Wholesale Pricing

            if ( WWOF_Functions::wwof_get_product_type( $product ) == 'simple' || WWOF_Functions::wwof_get_product_type( $product ) == 'variation' ) {

                if ( $hide_wholesale_discount === 'yes' ) {
                    
                    add_filter( 'wwof_hide_table_on_wwof_form' , '__return_true' );
                    add_filter( 'wwof_hide_per_category_table_on_wwof_form' , '__return_true' );
                    add_filter( 'wwof_hide_per_wholesale_role_table_on_wwof_form' , '__return_true' );
                    
                    $price_html = '<span class="price">' . $product->get_price_html() . '</span>';

                    remove_filter( 'wwof_hide_table_on_wwof_form' , '__return_true' );
                    remove_filter( 'wwof_hide_per_category_table_on_wwof_form' , '__return_true' );
                    remove_filter( 'wwof_hide_per_wholesale_role_table_on_wwof_form' , '__return_true' );

                } else
                    $price_html = '<span class="price">' . $product->get_price_html() . '</span>';

            }

            $price_html = apply_filters( 'wwof_filter_product_item_price' , $price_html , $product );

            return $price_html;

        }

        /**
         * Get product quantity field.
         *
         * @param $product
         *
         * @return string
         * @since 1.0.0
         * @since 1.6.6 Refactor codebase and move to its proper model
         * @since 1.7.0 added support for WooCommerce min/max quantities plugin.
         */
        public function wwof_get_product_quantity_field( $product ) {

            // TODO: dynamically change max value depending on product stock ( specially when changing variations of a variable product )

            global $wc_wholesale_prices_premium, $wc_wholesale_prices;

            $initial_value = 1;
            $min_order_qty_html = '';

            // We only do this if WWPP is installed and active
            if ( get_class( $wc_wholesale_prices_premium ) == 'WooCommerceWholeSalePricesPremium' &&
                 get_class( $wc_wholesale_prices ) == 'WooCommerceWholeSalePrices' ) {

                $wholesale_role = $wc_wholesale_prices->wwp_wholesale_roles->getUserWholesaleRole();

                // We only do this if wholesale user
                if ( !empty( $wholesale_role ) ) {

                    if ( WWOF_Functions::wwof_get_product_type( $product ) != 'variable' ) {

                        $wholesale_price = WWOF_Functions::wwof_get_wholesale_price( $product , $wholesale_role );

                        if ( is_numeric( $wholesale_price ) ) {

                            $min_order_qty = get_post_meta( WWOF_Functions::wwof_get_product_id( $product ) , $wholesale_role[ 0 ] . '_wholesale_minimum_order_quantity' , true );
                            if ( $min_order_qty )
                                $initial_value = $min_order_qty;

                        }

                    }

                } // Wholesale Role Check

            } // WWPP check

            if ( $product->is_in_stock() ) {

                $input_args        = WWOF_Product_Listing_Helper::get_product_quantity_input_args( $product );
                $min               = ( isset( $input_args[ 'min_value' ] ) && $input_args[ 'min_value' ] ) ? $input_args[ 'min_value' ] : 1;
                $max               = ( isset( $input_args[ 'max_value' ] ) && $input_args[ 'max_value' ] ) ? $input_args[ 'max_value' ] : '';
                $tab_index_counter = isset( $_REQUEST[ 'tab_index_counter' ] ) ? $_REQUEST[ 'tab_index_counter' ] : '';
                $stock_quantity    = $product->get_stock_quantity();

                // prepare quantity input args.
                $quantity_args     = array(
                    'input_value' => ( $initial_value % $min > 0 ) ? $min : $initial_value,
                    'step'        => ( isset( $input_args[ 'step' ] ) && $input_args[ 'step' ] ) ? $input_args[ 'step' ] : 1,
                    'min_value'   => $min,
                    'max_value'   => $max
                );

                // if managing stock and max is not set, then set max to stock quantity.
                if ( $product->managing_stock() == 'yes' && $stock_quantity && ! $max && ! $product->backorders_allowed() )
                    $quantity_args[ 'max_value' ] = $stock_quantity;
                
                $quantity_field  = woocommerce_quantity_input( $quantity_args , $product , false );

                // add tab index attribute.
                $quantity_field = str_replace( 'type="number"' , 'type="number" tabindex="' . $tab_index_counter . '"' , $quantity_field );

            } else
                $quantity_field = '<span class="out-of-stock">' . __( 'Out of Stock' , 'woocommerce-wholesale-order-form' ) . '</span>';

            $quantity_field = $min_order_qty_html . $quantity_field;

            $quantity_field = apply_filters( 'wwof_filter_product_item_quantity' , $quantity_field , $product );

            return $quantity_field;

        }

        /**
         * Get the message.
         *
         * @return string
         *
         * @param $wholesale_min_order_quantity
         * @param $wholesale_min_order_price
         * @param $wholesale_min_req_logic
         * @since 1.6.1
         * @since 1.6.6 Refactor codebase and move to its proper model
         */
        public function wwof_get_wholesale_price_requirement_message( $wholesale_min_order_quantity, $wholesale_min_order_price, $wholesale_min_req_logic ) {

            $message = '';

            if( ! empty( $wholesale_min_order_quantity ) && ! empty( $wholesale_min_order_price ) && ! empty( $wholesale_min_req_logic ) ){
                $message = sprintf( __( 'NOTE: A minimum order quantity of <b>%1$s</b> %2$s minimum order subtotal of <b>%3$s</b> is required to activate wholesale pricing in the cart.' , 'woocommerce-wholesale-order-form' ) , $wholesale_min_order_quantity , $wholesale_min_req_logic , wc_price( $wholesale_min_order_price ) );
            }elseif( ! empty( $wholesale_min_order_quantity ) ){
                $message = sprintf( __( 'NOTE: A minimum order quantity of <b>%1$s</b> is required to activate wholesale pricing in the cart.' , 'woocommerce-wholesale-order-form' ) , $wholesale_min_order_quantity );
            }elseif( ! empty( $wholesale_min_order_price ) ){
                $message = sprintf( __( 'NOTE: A minimum order subtotal of <b>%1$s</b> is required to activate wholesale pricing in the cart.' , 'woocommerce-wholesale-order-form' ) , wc_price( $wholesale_min_order_price ) );
            }

            return ! empty( $message ) ? $message : '';

        }

        /**
         * Get the base currency mapping from the wholesale price per order quantity mapping.
         *
         * @since 1.3.1
         * @since 1.6.6 Refactor codebase and move to its proper model
         *
         * @param $mapping
         * @param $user_wholesale_role
         * @return array
         */
        private function wwof_get_base_currency_mapping( $mapping , $user_wholesale_role ) {

            $base_currency_mapping = array();

            foreach ( $mapping as $map ) {

                // Skip non base currency mapping
                if ( array_key_exists( 'currency' , $map ) )
                    continue;

                // Skip mapping not meant for the current user wholesale role
                if ( $user_wholesale_role[ 0 ] != $map[ 'wholesale_role' ] )
                    continue;

                $base_currency_mapping[] = $map;

            }

            return $base_currency_mapping;

        }

        /**
         * Get the specific currency mapping from the wholesale price per order quantity mapping.
         *
         * @since 1.3.1
         * @since 1.6.6 Refactor codebase and move to its proper model
         *
         * @param $mapping
         * @param $user_wholesale_role
         * @param $active_currency
         * @param $base_currency_mapping
         * @return array
         */
        private function wwof_get_specific_currency_mapping( $mapping , $user_wholesale_role , $active_currency , $base_currency_mapping ) {

            // Get specific currency mapping
            $specific_currency_mapping = array();

            foreach ( $mapping as $map ) {

                // Skip base currency
                if ( !array_key_exists( 'currency' , $map ) )
                    continue;

                // Skip mappings that are not for the active currency
                if ( !array_key_exists( $active_currency . '_wholesale_role' , $map ) )
                    continue;

                // Skip mapping not meant for the currency user wholesale role
                if ( $user_wholesale_role[ 0 ] != $map[ $active_currency . '_wholesale_role' ] )
                    continue;

                // Only extract out mappings for this current currency that has equivalent mapping
                // on the base currency.
                foreach ( $base_currency_mapping as $base_map ) {

                    if ( $base_map[ 'start_qty' ] == $map[ $active_currency . '_start_qty' ] && $base_map[ 'end_qty' ] == $map[ $active_currency . '_end_qty' ] ) {

                        $specific_currency_mapping[] = $map;
                        break;

                    }

                }

            }

            return $specific_currency_mapping;

        }

        /**
         * Show or Hide wholesale price requirement printed above the order form.
         *
         * @since 1.8.5
         *
         * @param bool $value
         * @return bool
         */
        public function wwof_show_hide_wholesale_price_requirement( $value ) {

            return get_option( 'wwof_display_wholesale_price_requirement' , 'yes' ) == 'yes' ? $value : false;

        }

        /**
         * Update totals to include prduct add-ons.
         * Source: Product_Addon_Display->totals()
         *
         * @since 1.8.5
         *
         * @param text          $price_html
         * @param WC_Product    $product
         * @return text
         */
        public function wwof_show_addon_sub_total( $price_html , $product ) {
            
            global $Product_Addon_Display;

            if ( $Product_Addon_Display != null && ( get_class( $Product_Addon_Display ) == 'Product_Addon_Display' || get_class( $Product_Addon_Display ) == 'Product_Addon_Display_Legacy' ) ) {
                
                $post_id = WWOF_Functions::wwof_get_product_id( $product );

                ob_start();
                $Product_Addon_Display->display( $post_id );
                $product_addons = ob_get_clean();

                if ( trim( $product_addons ) == '' )
                    return $price_html;

                if ( ! isset( $product ) || $product->get_id() != $post_id ) {
                    $the_product = wc_get_product( $post_id );
                } else {
                    $the_product = $product;
                }

                if ( is_object( $the_product ) ) {
                    $tax_display_mode = get_option( 'woocommerce_tax_display_shop' );
                    $display_price    = 'incl' === $tax_display_mode ? wc_get_price_including_tax( $the_product ) : wc_get_price_excluding_tax( $the_product );
                } else {
                    $display_price = '';
                    $raw_price     = 0;
                }

                if ( 'no' === get_option( 'woocommerce_prices_include_tax' ) ) {
                    $tax_mode  = 'excl';
                    $raw_price = wc_get_price_excluding_tax( $the_product );
                } else {
                    $tax_mode  = 'incl';
                    $raw_price = wc_get_price_including_tax( $the_product );
                }


                if( class_exists( 'WWP_Wholesale_Prices' ) ) {

                    global $wc_wholesale_prices;

                    $wholesale_role = $wc_wholesale_prices->wwp_wholesale_roles->getUserWholesaleRole();

                    $display_price  = WWOF_Functions::wwof_get_wholesale_price( $the_product , $wholesale_role );
                    $raw_price      = $display_price;

                }

                $display_totals = '<div class="product-addons-total" data-show-sub-total="' . ( apply_filters( 'woocommerce_product_addons_show_grand_total', true, $the_product ) ? 1 : 0 ) . '" data-type="' . esc_attr( $the_product->get_type() ) . '" data-tax-mode="' . esc_attr( $tax_mode ) . '" data-tax-display-mode="' . esc_attr( $tax_display_mode ) . '" data-price="' . esc_attr( $display_price ) . '" data-raw-price="' . esc_attr( $raw_price ) . '" data-product-id="' . esc_attr( $post_id ) . '"></div>';

                return $price_html . $display_totals;

            }

            return $price_html;

        }

        /**
         * Return product category ids that has wholesale role filter set for the current user.
         *
         * @param string    $wholesale_role Current wholesale role of user
         *
         * @since 1.8.8
         * @return array
         */
        public function get_restricted_product_cat_ids_for_wholesale_user( $wholesale_role ) {

            $product_cat_wholesale_role_filter  = get_option( WWPP_OPTION_PRODUCT_CAT_WHOLESALE_ROLE_FILTER , array() );
            $filtered_terms_ids                 = array();

            foreach ( $product_cat_wholesale_role_filter as $term_id => $filtered_wholesale_roles )
                if ( !in_array( $wholesale_role , $filtered_wholesale_roles ) )
                    $filtered_terms_ids[] = $term_id;

            return $filtered_terms_ids;

        }

        /**
         * Products that are not allowed to show for the current user.
         *
         * @since 1.8.8
         *
         * @param string    $role
         * @return array
         */
        public function get_all_products_restricted_via_category( $role ) {

            global $wpdb;

            $filtered_terms_ids     = $this->get_restricted_product_cat_ids_for_wholesale_user( $role );
            $restricted_products    = array();
            $restricted_variations  = array();

            if( $filtered_terms_ids ) {
                $results = $wpdb->get_results( "SELECT p.ID FROM $wpdb->posts p
                                                LEFT JOIN $wpdb->term_relationships tr ON (p.ID = tr.object_id)
                                                WHERE p.post_status = 'publish'
                                                    AND p.post_type = 'product'
                                                    AND p.post_parent NOT IN ( SELECT p2.ID FROM $wpdb->posts p2 WHERE p2.post_type = 'product' AND p2.post_status != 'publish' )
                                                    AND tr.term_taxonomy_id IN ( " . implode( ',' , $filtered_terms_ids ) . " )" , ARRAY_A );
                foreach ( $results as $product ) {
                    $restricted_products[] = $product[ 'ID' ];
                }
            }

            if( $restricted_products ) {
                $results2 = $wpdb->get_results( "SELECT p.ID FROM $wpdb->posts p
                                                WHERE p.post_status = 'publish'
                                                    AND p.post_type = 'product_variation'
                                                    AND p.post_parent IN ( " . implode( ',' , $restricted_products ) . " )" , ARRAY_A );

                foreach ( $results2 as $variation ) {
                    $restricted_variations[] = $variation[ 'ID' ];
                }
            }

            return array_unique( array_merge( $restricted_products , $restricted_variations ) );

        }

        /**
         * Products that have wholesale price set.
         *
         * @since 1.8.8
         *
         * @param string    $role
         * @return array
         */
        public function get_all_wholesale_products( $role ) {

            $wholesale_products     = array();
            $wholesale_variations   = array();
            $meta_sql_query         = "AND ( ( pm1.meta_key = 'wwpp_product_wholesale_visibility_filter' AND pm1.meta_value IN ( '" . $role . "', 'all' ) )
                                            AND ( 
                                                    ( pm2.meta_key = '" . $role . "_have_wholesale_price' AND pm2.meta_value = 'yes' )
                                                    OR
                                                    ( pm2.meta_key = '" . $role . "_wholesale_price' AND CAST( pm2.meta_value AS SIGNED ) > 0 )
                                                )
                                        )";
            if( $role ) {
                
                global $wpdb;

                $results = $wpdb->get_results( "SELECT DISTINCT p.ID FROM $wpdb->posts p
                                    INNER JOIN $wpdb->postmeta pm1 ON ( p.ID = pm1.post_id )
                                    INNER JOIN $wpdb->postmeta pm2 ON ( p.ID = pm2.post_id )
                                    WHERE p.post_status = 'publish'
                                        AND p.post_type = 'product'" .
                                        $meta_sql_query , ARRAY_A );

                if( $results ) {
                    foreach ( $results as $product ) {
                        $wholesale_products[] = $product[ 'ID' ];
                    }
                }

                if( $wholesale_products ) {
                    $results2 = $wpdb->get_results( "SELECT p.ID FROM $wpdb->posts p
                                                    INNER JOIN $wpdb->postmeta pm1 ON ( p.ID = pm1.post_id )
                                                    INNER JOIN $wpdb->postmeta pm2 ON ( p.ID = pm2.post_id )
                                                    WHERE p.post_status = 'publish'
                                                        AND p.post_type = 'product_variation'
                                                        AND p.post_parent IN ( " . implode( ',' , $wholesale_products ) . " )" .
                                                        $meta_sql_query , ARRAY_A );

                    foreach ( $results2 as $variation ) {
                        $wholesale_variations[] = $variation[ 'ID' ];
                    }
                }

                return array_unique( array_merge( $wholesale_products , $wholesale_variations ) );

            }

            return array();

        }

        /**
         * Products that have wholesale discount set via category.
         *
         * @since 1.8.8
         *
         * @param string    $role
         * @return array
         */
        public function get_all_wholesale_products_from_category( $role ) {

            global $wpdb;

            $terms = get_terms( array(
                'taxonomy'  => 'product_cat',
                'fields'    => 'ids'
            ) );

            $wholesale_products_via_cat = array();

            if( $terms ) {

                foreach ( $terms as $term_id ) {

                    $term_meta = get_option( "taxonomy_$term_id" );

                    if( $term_meta[ $role . '_wholesale_discount' ] ) {

                        $wholesale_products     = array();
                        $wholesale_variations   = array();

                        $results = $wpdb->get_results( "SELECT p.ID FROM $wpdb->posts p
                                INNER JOIN $wpdb->term_relationships tr ON ( p.ID = tr.object_id )
                                WHERE p.post_status = 'publish'
                                    AND p.post_type = 'product'
                                    AND tr.term_taxonomy_id = $term_id" , ARRAY_A );

                        if( $results ) {
                            foreach ( $results as $product ) {
                                $wholesale_products[] = $product[ 'ID' ];
                            }
                        }

                        if( $wholesale_products ) {
                            $results2 = $wpdb->get_results( "SELECT p.ID FROM $wpdb->posts p
                                                            INNER JOIN $wpdb->postmeta pm1 ON ( p.ID = pm1.post_id )
                                                            INNER JOIN $wpdb->postmeta pm2 ON ( p.ID = pm2.post_id )
                                                            WHERE p.post_status = 'publish'
                                                                AND p.post_type = 'product_variation'
                                                                AND p.post_parent IN ( " . implode( ',' , $wholesale_products ) . " )" , ARRAY_A );

                            foreach ( $results2 as $variation ) {
                                $wholesale_variations[] = $variation[ 'ID' ];
                            }
                        }

                        $wholesale_products_via_cat = array_unique( array_merge( $wholesale_products_via_cat , $wholesale_products , $wholesale_variations ) );

                    }

                }

                return array_values( $wholesale_products_via_cat );

            }
            

            return array();

        }

        /**
         * This function will replace WWPP pre_get_posts_arg.
         * Customized function when List product variation individually" option is enabled
         *
         * @since 1.8.8
         * @access public
         *
         * @param array $query_args Query args array.
         * @return array
         */
        public function wwof_pre_get_posts_arg( $query_args ) {

            global $wc_wholesale_prices , $wc_wholesale_prices_premium;

            // We only do this if WWP and WWPP are installed and active
            if( get_class( $wc_wholesale_prices_premium ) == 'WooCommerceWholeSalePricesPremium' && get_class( $wc_wholesale_prices ) == 'WooCommerceWholeSalePrices' ) {
                    
                // When "List product variation individually" option is enabled
                if ( get_option( 'wwof_general_list_product_variation_individually' , 'no' ) === 'yes' ) {

                    $user_wholesale_role        = $wc_wholesale_prices->wwp_wholesale_roles->getUserWholesaleRole();
                    $user_wholesale_role        = ( is_array( $user_wholesale_role ) && !empty( $user_wholesale_role ) ) ? $user_wholesale_role[ 0 ] : '';
                    $wholesale_role_discount    = get_option( WWPP_OPTION_WHOLESALE_ROLE_GENERAL_DISCOUNT_MAPPING , array() );
                    $override_discount_per_user = get_user_meta( get_current_user_id() , 'wwpp_override_wholesale_discount' , true );
                    
                    $restricted_products = $this->get_all_products_restricted_via_category( $user_wholesale_role );

                    // Check if general wholesale role discount is not set for this current user wholesale role
                    if( !isset( $wholesale_role_discount[ $user_wholesale_role ] ) ) {

                        if( !isset( $query_args[ 'post__in' ] ) )
                            $query_args[ 'post__in' ] = array();
                                
                        if( !isset( $query_args[ 'post__not_in' ] ) )
                            $query_args[ 'post__not_in' ] = array();

                        $wwpp_settings_only_show_wholesale_products_to_wholesale_users = get_option( 'wwpp_settings_only_show_wholesale_products_to_wholesale_users' , false );

                        // Check if override per user is not set and user is not admin and user is not shop manager and is wholesale user
                        if ( $override_discount_per_user != 'yes' && !current_user_can( 'manage_options' ) && !current_user_can( 'manage_woocommerce' ) && $user_wholesale_role ) {

                            // If only show wholesale products to wholesale users option is enabled
                            if ( $wwpp_settings_only_show_wholesale_products_to_wholesale_users === 'yes' ) {

                                $wholesale_products         = $this->get_all_wholesale_products( $user_wholesale_role );
                                $wholesale_products_via_cat = $this->get_all_wholesale_products_from_category( $user_wholesale_role );
                                $merged_wholesale_products  = array_unique( array_merge( $wholesale_products , $wholesale_products_via_cat ) );

                                $query_args[ 'post__in' ] = !empty( $merged_wholesale_products ) ? array_intersect( $query_args[ 'post__in' ] , $merged_wholesale_products ) : array();

                            }

                        }

                        // Don't show restricted products
                        if ( !empty( $restricted_products ) ) {

                            $query_args[ 'post__in' ]       = array_diff( $query_args[ 'post__in' ] , $restricted_products );
                            $query_args[ 'post__not_in' ]   = array_intersect( $query_args[ 'post__not_in' ] , $restricted_products );

                        }

                        // Filter wholesale products
                        if( isset( $query_args[ 'searched_keyword' ] ) ) {

                            $search_sku         = get_option( 'wwof_general_allow_product_sku_search' );
                            $search_the_sku     = ( $search_sku == 'yes' ) ? true : false;
                            $search_products    = WWOF_Product_Listing_Helper::get_search_products( $query_args[ 'searched_keyword' ] , $search_the_sku );
                            $filter_search      = array_intersect( $query_args[ 'post__in' ] , $search_products );

                            if( !empty( $filter_search ) )
                                $query_args[ 'post__in' ] = $filter_search;
                            else
                                $query_args[ 'post__in' ] = array( 0 );

                        }

                        // Set post__in to empty products if no products found
                        if( empty( $query_args[ 'post__in' ] ) )
                            $query_args[ 'post__in' ] = array( 0 );

                    }

                }

            }

            return $query_args;

        }

        /**
         * Remove hook if "List product variation individually" option is enabled.
         * Add a customized 'wwof_filter_product_listing_query_arg' hook which is run in this function wwof_pre_get_posts_arg.
         *
         * @since 1.8.8
         * @access public
         */
        public function wwof_remove_wwpp_pre_get_posts_arg_hook() {

            global $wc_wholesale_prices , $wc_wholesale_prices_premium;

            // We only do this if WWP and WWPP are installed and active
            if( ( $wc_wholesale_prices != null && $wc_wholesale_prices_premium != null ) && get_class( $wc_wholesale_prices_premium ) == 'WooCommerceWholeSalePricesPremium' && get_class( $wc_wholesale_prices ) == 'WooCommerceWholeSalePrices' ) {

                // When "List product variation individually" option is enabled
                if ( get_option( 'wwof_general_list_product_variation_individually' , 'no' ) === 'yes' ) {

                    remove_filter( 'wwof_filter_product_listing_query_arg' , array( $wc_wholesale_prices_premium->wwpp_query, 'pre_get_posts_arg' ) );
                    
                    // Filter product query in wwof
                    add_filter( 'wwof_filter_product_listing_query_arg' ,  array( $this , 'wwof_pre_get_posts_arg' ) , 90 , 1 );

                    // Fix for WWOF-349
                    add_filter( 'wwof_product_listing_price_filter_meta_query' , function( $meta_query ) {

                        global $wc_wholesale_prices;

                        $user_wholesale_role = $wc_wholesale_prices->wwp_wholesale_roles->getUserWholesaleRole();
                        $user_wholesale_role = ( is_array( $user_wholesale_role ) && !empty( $user_wholesale_role ) ) ? $user_wholesale_role[ 0 ] : '';
                        
                        $meta_query[] = array(
                                            'key'     => 'wwpp_product_wholesale_visibility_filter',
                                            'value'   => array( $user_wholesale_role , 'all' ),
                                            'compare' => 'IN'
                                        );

                        return $meta_query;

                    } , 10 , 1 );

                }

            }

        }

        /**
         * Execute model.
         *
         * @since 1.6.6
         * @access public
         */
        public function run() {

            // Display wholesale price requirement message at the top of the search box wholesale ordering form.
            add_action( 'wwof_action_before_product_listing_filter' , array( $this , 'wwof_display_wholesale_price_requirement' ) , 10 , 1 );

            // Enable / Disable showing minimum order subtotal on ordering form
            add_filter( 'wwof_display_wholesale_price_requirement' , array( $this , 'wwof_show_hide_wholesale_price_requirement' ) , 10 , 1 );
            
            add_filter( 'wwof_filter_product_item_price' , array( $this , 'wwof_show_addon_sub_total' ) , 10 , 2 );
            
            // Remove hook if Only show wholesale products to wholesale customers option is enabled and if "List product variation individually" option is enabled
            add_action( 'init' ,  array( $this , 'wwof_remove_wwpp_pre_get_posts_arg_hook' ) , 10 , 1 );

        }
    }
}

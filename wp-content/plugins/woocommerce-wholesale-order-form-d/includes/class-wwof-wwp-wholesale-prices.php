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

				$input_args 	   = WWOF_Product_Listing_Helper::get_product_quantity_input_args( $product );
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

				$quantity_field  = '<div class="quantity">';
				$quantity_field  = woocommerce_quantity_input( $quantity_args , $product , false );
				$quantity_field .= '</div>';

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
         * Execute model.
         *
         * @since 1.6.6
         * @access public
         */
        public function run() {

            // Display wholesale price requirement message at the top of the search box wholesale ordering form.
            add_action( 'wwof_action_before_product_listing_filter' , array( $this , 'wwof_display_wholesale_price_requirement' ) , 10 , 1 );
            
        }
    }
}

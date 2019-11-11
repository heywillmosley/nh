<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( !class_exists( 'WWOF_Product_Listing_Helper' ) ) {

    class WWOF_Product_Listing_Helper {

        /**
         * Get all products of the shop via $wpdb.
         * It returns an array of Post objects.
         *
         * @since 1.2.7
         * @since 1.7.4 Added $col arg to select which column we should only be getting.
         * @since 1.7.7 Set function to check post_type for both product and product_variation.
         *
         * @param string    $col
         * @return array
         */
        public static function get_all_products( $col = '*' ) {

            global $wpdb;

            return $wpdb->get_results("
                                        SELECT $col
                                        FROM $wpdb->posts
                                        WHERE post_status = 'publish'
                                        AND post_type IN ( 'product' , 'product_variation' )
                                        " );

        }

        /**
         * Get all instock products. Instock products has 2 types.
         * 1. Managed products
         * 2. Unmanaged products
         * The reason being we search for this 2 types of products is because of this bug.
         * https://github.com/woothemes/woocommerce/issues/6789
         * WooCommerce dude replied to me and said this is fixed, but as far as my latest tests
         * ( wc 2.4.x ) this bug is still here. Classic WooCommerce!
         *
         * Also note that this function only concerns itself on instock products, it does not care of other
         * query filters. Thus should be handled by the main query.
         *
         * @since 1.2.7
         * @since 1.3.2
         * Variable product visibility to the wholesale order form is now determine by the variations of that variable
         * product. Meaning even if the parent variable product is set to managed and set the value of stock to 0
         * we will not honor this to conclude that this variable product should be displayed on the order form,
         * instead, we will go through all the variations of this variable product and check if at least 1 of its
         * variations has stock* ( the word has stock is tricky, ill explain further later ), and if so then we display
         * the current variable product.
         *
         * Now as of woocommerce 2.4.x and 2.3.x series, there is this case ( might be a bug ),
         * where ex.
         *
         * 1. you have a current variable product, it is unmanaged
         * 2. you have 3 variations, 2 of the variations are managed with stock of 100 and 200 respectively
         * 3. last variation is unmanaged, and has stock status of in stock
         * 4. change the parent variable product as managed, and set qty to 0
         * 5. check the last variation on the single product admin page, it still has status of in stock
         * 6. go to the shop page, check out the last variation of that variable product, it is out of stock
         * 7. try to edit that last variation and hit save, notice you can't set it to in stock anymore (given
         * the last variation remains un managed)
         *
         * so this is my observation:
         * 1. if parent product is managed, all unmanaged variations inherit the parent variable product characteristics
         * so in the explanation above, since parent variable is set to managed and has stock of 0, then the last
         * variation which is unmanaged inherits the parent variable product qty which is zero, thats why on the shop
         * page its out of stock.
         *
         * 2. it doesn't sync well, at least with the current version of woocommerce i have during this time.
         *
         * @since 1.3.4
         * Recognize the general inventory management settings.
         * WooCommerce > Settings > Product > Inventory > Manage Stock
         *
         * @since 1.6.3 WWOF-103 : We should allow displaying backorder variable products in the order form even if it's stock quantity is 0
         * @since 1.6.6 Deprecated. Not used anymore.
         * @access public
         *
         * @return array Array of post ids
         */
        public static function get_all_instock_products() {

            global $wpdb;

            // ****************************************************************************
            // General Vars
            // ****************************************************************************

            // WooCommerce > Settings > Product > Inventory
            $inventory_management = get_option( 'woocommerce_manage_stock' );

            $managed_join_query = "
                                        LEFT JOIN $wpdb->postmeta post_meta_table2
                                                ON post_meta_table2.post_id = post_meta_table1.ID
                                                AND post_meta_table2.meta_key = '_manage_stock'
                                                AND post_meta_table2.meta_value = 'yes'
                                        LEFT JOIN $wpdb->postmeta post_meta_table3
                                                ON post_meta_table3.post_id = post_meta_table2.post_id
                                                AND (
                                                        ( post_meta_table3.meta_key = '_stock' AND post_meta_table3.meta_value > 0 )
                                                        OR
                                                        ( post_meta_table3.meta_key = '_backorders' AND post_meta_table3.meta_value IN ( 'yes' , 'notify' ) )
                                                    )
                                    ";

            $unmanaged_join_query = "
                                        LEFT JOIN $wpdb->postmeta post_meta_table2
                                                ON post_meta_table2.post_id = post_meta_table1.ID
                                                AND post_meta_table2.meta_key = '_manage_stock'
                                                AND post_meta_table2.meta_value = 'no'
                                        LEFT JOIN $wpdb->postmeta post_meta_table3
                                                ON post_meta_table3.post_id = post_meta_table2.post_id
                                                AND post_meta_table3.meta_key = '_stock_status'
                                                AND post_meta_table3.meta_value = 'instock'
                                    ";


            // ****************************************************************************
            // Get variable product ids
            // ****************************************************************************
            $variable_product_term_taxonomy_id      = self::get_variable_product_term_taxonomy_id();
            $managed_has_stock_variable_product_ids = self::get_managed_variable_product_ids_with_stock( $inventory_management , $variable_product_term_taxonomy_id );
            $managed_no_stock_variable_product_ids  = self::get_managed_variable_product_ids_with_no_stock( $inventory_management , $variable_product_term_taxonomy_id );
            $unmanaged_variable_product_ids         = self::get_unmanaged_variable_product_ids( $inventory_management , $variable_product_term_taxonomy_id );
            $variable_product_ids                   = self::get_variable_product_ids( $inventory_management , $variable_product_term_taxonomy_id , $managed_has_stock_variable_product_ids , $managed_no_stock_variable_product_ids , $unmanaged_variable_product_ids );


            // ****************************************************************************
            // Non-variable product query
            // ****************************************************************************
            $instock_non_variable_products_id = self::get_instock_none_variable_products_ids( $inventory_management , $variable_product_ids );

            if ( $inventory_management == 'yes' ) {

                // ****************************************************************************
                // Since this is managed variable product and the stock qty is set to zero,
                // then all we have to do is check the variations that is also managed and has
                // stock qty set to greater than zero.
                //
                // If at least one variation of the current variable product comply with this,
                // then we display this variable product on the wholesale order form page.
                //
                // The reason for this is, if variation is unmanaged, and the parent variable
                // product is set to managed, and has stock qty of 0, then the unmanged
                // variation inherits the parent variable qty which is 0.
                //
                // Therefore we can conclude that unmanaged variations under a managed variable
                // product that has qty of 0 is automatically out of stock too.
                // ****************************************************************************

                $instock_managed_no_stock_variable_product_ids = array();
                if ( !empty( $managed_no_stock_variable_product_ids ) ) {

                    $managed_no_stock_variable_product_ids_str = implode( ',' , $managed_no_stock_variable_product_ids );

                    $query = "
                      SELECT DISTINCT post_meta_table1.post_parent
                      FROM $wpdb->posts post_meta_table1
                      ";

                    $where_query = "
                            WHERE post_meta_table1.post_status = 'publish'
                            AND post_meta_table1.post_type = 'product_variation'
                            AND post_meta_table1.post_parent IN (" . $managed_no_stock_variable_product_ids_str . ")
                            ";

                    $query_results = $wpdb->get_results( $query . $managed_join_query . $where_query , ARRAY_A );

                    foreach ( $query_results as $qr )
                        $instock_managed_no_stock_variable_product_ids[] = (int) $qr[ 'post_parent' ];

                }

                // ****************************************************************************
                // Since this is managed variable product with stock qty greater than 0
                // then we need to check both variations that are un-managed and managed
                // ****************************************************************************
                $instock_managed_has_stock_variable_product_ids = array();
                if ( !empty( $managed_has_stock_variable_product_ids ) ) {

                    $managed_has_stock_variable_product_ids_str = implode( "," , $managed_has_stock_variable_product_ids );

                    $query = "
                      SELECT DISTINCT post_meta_table1.post_parent
                      FROM $wpdb->posts post_meta_table1
                      ";

                    $where_query = "
                            WHERE post_meta_table1.post_status = 'publish'
                            AND post_meta_table1.post_type = 'product_variation'
                            AND post_meta_table1.post_parent IN (" . $managed_has_stock_variable_product_ids_str . ")
                            ";

                    // Manged Instock Products
                    $managed_list = array();
                    $query_results = $wpdb->get_results( $query . $managed_join_query . $where_query , ARRAY_A );

                    foreach ( $query_results as $qr )
                        $managed_list[] = (int) $qr[ 'post_parent' ];

                    // Unmanaged Instock Products
                    $unmanaged_list = array();
                    $query_results = $wpdb->get_results( $query . $unmanaged_join_query . $where_query , ARRAY_A );

                    foreach ( $query_results as $qr )
                        $unmanaged_list[] = (int) $qr[ 'post_parent' ];

                    $instock_managed_has_stock_variable_product_ids = array_unique( array_merge( $managed_list , $unmanaged_list ) );

                }

                // ****************************************************************************
                // Un-managed variable product, we need to check both
                // un-managed and managed variations
                // ****************************************************************************
                $instock_unmanaged_variable_product_ids = array();
                if ( !empty( $unmanaged_variable_product_ids ) ) {

                    $unmanaged_variable_product_ids_str = implode( "," , $unmanaged_variable_product_ids );

                    $query = "
                      SELECT DISTINCT post_meta_table1.post_parent
                      FROM $wpdb->posts post_meta_table1
                      ";

                    $where_query = "
                            WHERE post_meta_table1.post_status = 'publish'
                            AND post_meta_table1.post_type = 'product_variation'
                            AND post_meta_table1.post_parent IN (" . $unmanaged_variable_product_ids_str . ")
                            ";

                    // Manged Instock Products
                    $managed_list = array();
                    $query_results = $wpdb->get_results( $query . $managed_join_query . $where_query , ARRAY_A );

                    foreach ( $query_results as $qr )
                        $managed_list[] = (int) $qr[ 'post_parent' ];

                    // Unmanaged Instock Products
                    $unmanaged_list = array();
                    $query_results = $wpdb->get_results( $query . $unmanaged_join_query . $where_query , ARRAY_A );

                    foreach ( $query_results as $qr )
                        $unmanaged_list[] = (int) $qr[ 'post_parent' ];

                    $instock_unmanaged_variable_product_ids = array_unique( array_merge( $managed_list , $unmanaged_list ) );

                }

                $instock_variable_products_id = array_unique( array_merge( $instock_managed_no_stock_variable_product_ids , $instock_managed_has_stock_variable_product_ids , $instock_unmanaged_variable_product_ids ) );

            } else { // Inventory management is disabled. We still need to check the stock status though.

                $instock_variable_products_id = array();

                $q = "
                        SELECT DISTINCT post_meta_table1.post_parent
                          FROM $wpdb->posts post_meta_table1
                          LEFT JOIN $wpdb->postmeta post_meta_table2
                            ON post_meta_table2.post_id = post_meta_table1.ID
                          WHERE post_meta_table1.post_status = 'publish'
                            AND post_meta_table2.meta_key = '_stock_status'
                            AND post_meta_table2.meta_value = 'instock'
                            AND post_meta_table1.post_type = 'product_variation'"
                        ;

                $query_results = $wpdb->get_results( $q , ARRAY_A );

                foreach ( $query_results as $qr )
                    $instock_variable_products_id[] = $qr[ 'post_parent' ];

            }

            // **************************************
            // Merge in stock non-variable and
            // variable product ids
            // **************************************
            $instock_products_id = array_unique( array_merge( $instock_non_variable_products_id , $instock_variable_products_id ) );

            // If empty, we return an array that has a single value of zero
            // This is necessary to indicate that no instock products is present
            if ( empty( $instock_products_id ) )
                $instock_products_id = array( 0 );

            return $instock_products_id;

        }

        /**
         * Get variable product term ids. Product types in woocommerce are stored as terms in terms table.
         * A specific term ('simple','variable') is added to the particular product to determine its product type.
         *
         * @since 1.3.4
         *
         * @return int
         */
        public static function get_variable_product_term_taxonomy_id() {

            global $wpdb;

            // Get variable product term_taxonomy_id
            $q = "SELECT term_taxonomy_id FROM $wpdb->terms t, $wpdb->term_taxonomy tt WHERE t.term_id = tt.term_id AND name = 'variable' LIMIT 1";
            $variable_product_term_taxonomy_id = $wpdb->get_row( $q , ARRAY_A );

            if ( $variable_product_term_taxonomy_id )
                $variable_product_term_taxonomy_id = (int) $variable_product_term_taxonomy_id[ 'term_taxonomy_id' ];

            return $variable_product_term_taxonomy_id;

        }

        /**
         * Get the ids of all variable products that are managed and has stock.
         *
         * @since 1.3.4
         * @since 1.6.6 Deprecated. Not used anymore.
         *
         * @param $inventory_management
         * @param $variable_product_term_taxonomy_id
         * @return array
         */
        public static function get_managed_variable_product_ids_with_stock( $inventory_management , $variable_product_term_taxonomy_id ) {

            $managed_has_stock_variable_product_ids = array();

            if ( !empty( $variable_product_term_taxonomy_id ) && $inventory_management == 'yes' ) {

                global $wpdb;

                // Get all managed variable ids that has stock
                $q = "
                      SELECT DISTINCT post_meta_table1.object_id
                      FROM $wpdb->term_relationships post_meta_table1
                      INNER JOIN $wpdb->postmeta post_meta_table2
                        ON post_meta_table2.post_id = post_meta_table1.object_id
                        AND post_meta_table2.meta_key = '_manage_stock'
                        AND post_meta_table2.meta_value = 'yes'
                      INNER JOIN $wpdb->postmeta post_meta_table3
                        ON post_meta_table3.post_id = post_meta_table2.post_id
                        AND post_meta_table3.meta_key = '_stock'
                        AND post_meta_table3.meta_value > 0
                      WHERE post_meta_table1.term_taxonomy_id = $variable_product_term_taxonomy_id
                     ";

                $q_results = $wpdb->get_results( $q , ARRAY_A );

                foreach( $q_results as $q_r )
                    $managed_has_stock_variable_product_ids[] = (int) $q_r[ 'object_id' ];

            }

            return $managed_has_stock_variable_product_ids;

        }

        /**
         * Get the ids of all variable products that are managed and has no stock.
         *
         * @since 1.3.4
         * @since 1.6.6 Deprecated. Not used anymore.
         *
         * @param $inventory_management
         * @param $variable_product_term_taxonomy_id
         * @return array
         */
        public static function get_managed_variable_product_ids_with_no_stock( $inventory_management , $variable_product_term_taxonomy_id ) {

            $managed_no_stock_variable_product_ids = array();

            if ( !empty( $variable_product_term_taxonomy_id ) && $inventory_management == 'yes' ) {

                global $wpdb;

                // Get all managed variable ids that has no stock
                $q = "
                      SELECT DISTINCT post_meta_table1.object_id
                      FROM $wpdb->term_relationships post_meta_table1
                      INNER JOIN $wpdb->postmeta post_meta_table2
                        ON post_meta_table2.post_id = post_meta_table1.object_id
                        AND post_meta_table2.meta_key = '_manage_stock'
                        AND post_meta_table2.meta_value = 'yes'
                      INNER JOIN $wpdb->postmeta post_meta_table3
                        ON post_meta_table3.post_id = post_meta_table2.post_id
                        AND post_meta_table3.meta_key = '_stock'
                        AND post_meta_table3.meta_value <= 0
                      WHERE post_meta_table1.term_taxonomy_id = $variable_product_term_taxonomy_id
                     ";

                $q_results = $wpdb->get_results( $q , ARRAY_A );

                foreach( $q_results as $q_r )
                    $managed_no_stock_variable_product_ids[] = (int) $q_r[ 'object_id' ];

            }

            return $managed_no_stock_variable_product_ids;

        }

        /**
         * Get the ids of all variable products tat are un-managed.
         *
         * @since 1.3.4
         * @since 1.6.6 Deprecated. Not used anymore.
         *
         * @param $inventory_management
         * @param $variable_product_term_taxonomy_id
         * @return array
         */
        public static function get_unmanaged_variable_product_ids( $inventory_management , $variable_product_term_taxonomy_id ) {

            $unmanaged_variable_product_ids = array();

            if ( !empty( $variable_product_term_taxonomy_id ) && $inventory_management == 'yes' ) {

                global $wpdb;

                $q = "
                      SELECT DISTINCT post_meta_table1.object_id
                      FROM $wpdb->term_relationships post_meta_table1
                      INNER JOIN $wpdb->postmeta post_meta_table2
                        ON post_meta_table2.post_id = post_meta_table1.object_id
                        AND post_meta_table2.meta_key = '_manage_stock'
                        AND post_meta_table2.meta_value = 'no'
                      WHERE post_meta_table1.term_taxonomy_id = $variable_product_term_taxonomy_id
                      ";

                $q_results = $wpdb->get_results( $q , ARRAY_A );

                foreach( $q_results as $q_r )
                    $unmanaged_variable_product_ids[] = (int) $q_r[ 'object_id' ];

            }

            return $unmanaged_variable_product_ids;

        }

        /**
         * Get all the variable product ids.
         *
         * @since 1.3.4
         * @since 1.6.6 Deprecated. Not used anymore.
         *
         * @param $inventory_management
         * @param $variable_product_term_taxonomy_id
         * @param $managed_has_stock_variable_product_ids
         * @param $managed_no_stock_variable_product_ids
         * @param $unmanaged_variable_product_ids
         * @return array
         */
        public static function get_variable_product_ids( $inventory_management , $variable_product_term_taxonomy_id , $managed_has_stock_variable_product_ids , $managed_no_stock_variable_product_ids , $unmanaged_variable_product_ids ) {

            global $wpdb;

            $variable_product_ids = array();

            if ( !empty( $variable_product_term_taxonomy_id ) ) {

                if ( $inventory_management == 'yes' ) {

                    // Merge all to get variable product ids
                    $variable_product_ids = array_unique( array_merge( $managed_has_stock_variable_product_ids , $managed_no_stock_variable_product_ids , $unmanaged_variable_product_ids ) );

                } else {

                    // Get all object_id ( post_id ) of variable products from term_relationships table.
                    // Stock management is disabled so we just get all entries with the term_taxonomy_id
                    // of $variable_product_term_taxonomy_id
                    $q = "
                          SELECT DISTINCT post_meta_table1.object_id
                          FROM $wpdb->term_relationships post_meta_table1
                          WHERE post_meta_table1.term_taxonomy_id = $variable_product_term_taxonomy_id
                         ";

                    $q_results = $wpdb->get_results( $q , ARRAY_A );

                    foreach( $q_results as $q_r )
                        $variable_product_ids[] = (int) $q_r[ 'object_id' ];

                }

            }

            return $variable_product_ids;

        }

        /**
         * Get all instock none-variable product ids.
         *
         * @since 1.3.4
         * @since 1.6.6 Deprecated. Not used anymore.
         *
         * @param $inventory_management
         * @param $variable_product_ids
         * @return array
         */
        public static function get_instock_none_variable_products_ids( $inventory_management , $variable_product_ids ) {

            global $wpdb;

            $query = "
                      SELECT DISTINCT post_meta_table1.ID
                      FROM $wpdb->posts post_meta_table1
                      ";

            $where_query = "
                            WHERE post_meta_table1.post_status = 'publish'
                            AND post_meta_table1.post_type = 'product'
                            ";

            // Exclude variable products
            if ( !empty( $variable_product_ids ) ) {

                $variable_product_ids_str = implode( ', ' , $variable_product_ids );
                $where_query .= "AND post_meta_table1.ID NOT IN ( " . $variable_product_ids_str . " )";

            }

            if ( $inventory_management == 'yes' ) {

                $managed_join_query = "
                                       INNER JOIN $wpdb->postmeta post_meta_table2
                                           ON post_meta_table2.post_id = post_meta_table1.ID
                                           AND post_meta_table2.meta_key = '_manage_stock'
                                           AND post_meta_table2.meta_value = 'yes'
                                       INNER JOIN $wpdb->postmeta post_meta_table3
                                           ON post_meta_table3.post_id = post_meta_table2.post_id
                                           AND (
                                                  ( post_meta_table3.meta_key = '_stock' AND post_meta_table3.meta_value > 0 )
                                                  OR
                                                  ( post_meta_table3.meta_key = '_backorders' AND post_meta_table3.meta_value IN ( 'yes' , 'notify' ) )
                                               )
                                       ";

                $unmanaged_join_query = "
                                         INNER JOIN $wpdb->postmeta post_meta_table2
                                             ON post_meta_table2.post_id = post_meta_table1.ID
                                             AND post_meta_table2.meta_key = '_manage_stock'
                                             AND post_meta_table2.meta_value = 'no'
                                         INNER JOIN $wpdb->postmeta post_meta_table3
                                             ON post_meta_table3.post_id = post_meta_table2.post_id
                                             AND post_meta_table3.meta_key = '_stock_status'
                                             AND post_meta_table3.meta_value = 'instock'
                                        ";


                // Manged Instock Products
                $managed_list = array();
                $query_results = $wpdb->get_results( $query . $managed_join_query . $where_query , ARRAY_A );

                foreach ( $query_results as $qr )
                    $managed_list[] = $qr[ 'ID' ];

                // Unmanaged Instock Products
                $unmanaged_list = array();
                $query_results = $wpdb->get_results( $query . $unmanaged_join_query . $where_query , ARRAY_A );

                foreach ( $query_results as $qr )
                    $unmanaged_list[] = $qr[ 'ID' ];

                $instock_non_variable_products_id = array_unique( array_merge( $managed_list , $unmanaged_list ) );

            } else {

                // Inventory management is disabled. We still gotta check the _stock_status meta though.
                $join_query = "
                                INNER JOIN $wpdb->postmeta post_meta_table2
                                    ON post_meta_table2.post_id = post_meta_table1.ID
                                    AND post_meta_table2.meta_key = '_stock_status'
                                    AND post_meta_table2.meta_value = 'instock'
                                ";

                $instock_non_variable_products_id = array();
                $query_results = $wpdb->get_results( $query . $join_query . $where_query , ARRAY_A );

                foreach ( $query_results as $qr )
                    $instock_non_variable_products_id[] = $qr[ 'ID' ];

            }

            return $instock_non_variable_products_id;

        }

        /**
         * Get all products that is being searched.
         * It may or may not execute an sku search.
         *
         * Also note that this function only concerns itself on searching products, it does not care of other
         * query filters. Thus should be handled by the main query.
         *
         * @since 1.2.7
         * @since 1.6.6 Filter out variable products whose variations are all in out of stock status
         *
         * @param $search
         * @param bool|false $search_sku
         * @return array Array of post ids.
         */
        public static function get_search_products( $search , $search_sku = false ) {

            global $wpdb;

            // Normal Search
            if( WWOF_Functions::wwof_is_woocommerce_version_3() ) {
                $query = $wpdb->prepare( "SELECT DISTINCT p.ID, p.post_parent
                                            FROM $wpdb->posts p
                                            WHERE p.post_type IN ( 'product' , 'product_variation' )
                                            AND p.post_status = 'publish'
                                            AND (
                                                p.post_title LIKE %s
                                                OR p.post_content LIKE %s
                                                OR p.post_excerpt LIKE %s )
                                        " , '%' . $search . '%' , '%' . $search . '%' , '%' . $search . '%' );

            }else{
                $query = $wpdb->prepare( "SELECT DISTINCT p.ID, p.post_parent
                                            FROM $wpdb->posts p
                                            LEFT JOIN $wpdb->postmeta pm
                                                ON pm.post_id = p.ID
                                            WHERE p.post_type IN ( 'product' , 'product_variation' )
                                            AND p.post_status = 'publish'
                                            AND (
                                                    (   p.post_parent = 0 AND ( p.post_title LIKE %s
                                                        OR
                                                        p.post_content LIKE %s
                                                        OR
                                                        p.post_excerpt LIKE %s )
                                                    )
                                                    OR pm.meta_key LIKE '%s' AND pm.meta_value LIKE %s
                                                    OR pm.meta_key LIKE '%s' AND pm.meta_value LIKE %s
                                                )
                                        " , '%' . $search . '%' , '%' . $search . '%' , '%' . $search . '%' , 'attribute_%' , '%' . $search . '%' , 'attribute_%' , '%' . str_replace( ' ', '-', $search ) . '%');
            }

            $search_products_id = array();
            $query_results = $wpdb->get_results( $query , ARRAY_A );

            foreach ( $query_results as $qr ){

                if ( get_option( 'wwof_general_list_product_variation_individually' , 'no'  ) !== 'yes' )
                    $search_products_id[] = !empty( $qr[ 'post_parent' ] ) ? $qr[ 'post_parent' ] : $qr[ 'ID' ];
                else
                    $search_products_id[] = $qr[ 'ID' ];

                if( get_option( 'wwof_general_list_product_variation_individually' ) == 'yes' && empty( $qr[ 'post_parent' ] ) ){
                    $product = wc_get_product( $qr[ 'ID' ] );
                    $children = $product->get_children();
                    $search_products_id = array_unique( array_merge( $search_products_id , $children ) );
                }
            }

            if ( $search_sku ) {

                $query = $wpdb->prepare( "SELECT DISTINCT p.ID, p.post_parent
                                            FROM $wpdb->posts p
                                            INNER JOIN $wpdb->postmeta pm
                                                    ON pm.post_id = p.ID
                                                    AND pm.meta_key = '_sku'
                                                    AND pm.meta_value LIKE %s
                                            WHERE p.post_status = 'publish'
                                            AND p.post_type IN ( 'product' , 'product_variation' )
                                            " , '%' . $search . '%' );

                $sku_products = array();
                $query_results = $wpdb->get_results( $query , ARRAY_A );

                foreach ( $query_results as $qr ){

                    if ( get_option( 'wwof_general_list_product_variation_individually' , 'no' ) !== 'yes' && !empty( $qr[ 'post_parent' ] ) )
                        $product_id = $qr[ 'post_parent' ];
                    else
                        $product_id = $qr[ 'ID' ];

                    $product = wc_get_product( $product_id );

                    $sku_products[] = WWOF_Functions::wwof_get_product_id( $product );
                }

                $search_products_id = array_merge( $search_products_id , $sku_products );

            }

            // If empty, we return an array that has a single value of zero
            // This is necessary to indicate that no products qualifies for the given search
            if ( empty( $search_products_id ) )
                $search_products_id = array( 0 );

            return array_unique( $search_products_id );

        }

        /**
         * Get excluded variable product IDs.
         * We'll have to exclude variable products that has all variations in out of stock status.
         * We only do this only if "Display Zero Inventory Products?" option is disabled
         *
         * @return array
         * @since 1.6.6
         */
        public static function wwof_get_excluded_variable_ids(){

            global $wpdb;

            $show_zero_prod = get_option( 'wwof_general_display_zero_products' );
            $excluded_ids   = array();

            if( $show_zero_prod != 'yes' ){

                // Get the IDs of variable products that are not in stock
                $query = "SELECT posts.ID
                    FROM $wpdb->posts posts

                    WHERE posts.post_status = 'publish'
                    AND posts.post_type = 'product'
                    AND posts.ID IN (

                        SELECT p.post_parent
                    	FROM $wpdb->posts p

                        INNER JOIN $wpdb->postmeta pm
                        	ON p.post_parent = pm.post_id
                        	AND pm.meta_key = '_stock_status'
                        	AND pm.meta_value != 'instock'

                    	WHERE p.post_parent > 0
                    		AND p.post_status = 'publish'
                    		AND p.post_type = 'product_variation'
                    )";

                $results = $wpdb->get_results( $query , ARRAY_A );

                foreach ( $results as $result )
                    $excluded_ids[] = (int) $result[ 'ID' ];
            }

            return $excluded_ids;

        }

        /**
         * Get excluded product IDs. These are Bundle and Composite product types since we are not yet supporting this up to1.6.3 version. Refer to WWOF-107
         * This function fixes the bug inaccurate total being displayed in the pagination of order form. Refer to WWOF-106
         * This function is used in function wwof_display_product_listing()
         *
         * @return array
         * @since 1.6.3 Bug: WWOF-106, WWOF-107
         * @since 1.6.6 Underscore cased the function name and variables. Transfer function to its proper model.
         *              Refactor codes to use mysql query instead of wp_query. Removed parameter $args variable.
         * @since 1.7.7 Added external product type to exclude. Made code DRY.
         */
        public static function wwof_get_excluded_product_ids(){

            global $wpdb;

            $exclude_products = array();
            $exclude_types    = array();

            if( !function_exists( 'is_plugin_active' ) )
                include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

            // get external product type term ID.
            $external_term_taxonomy_id = $wpdb->get_row( "SELECT term_taxonomy_id FROM $wpdb->terms t, $wpdb->term_taxonomy tt WHERE t.term_id = tt.term_id AND name = 'external' LIMIT 1" , ARRAY_A );

            if ( $external_term_taxonomy_id )
                $exclude_types[] = (int) $external_term_taxonomy_id[ 'term_taxonomy_id' ];

            // Check if WC Product Bundles is active then we excluded bundle products
            if( is_plugin_active( 'woocommerce-product-bundles/woocommerce-product-bundles.php' ) ){

                // Get bundle product term id
                $bundle_term_taxonomy_id = $wpdb->get_row( "SELECT term_taxonomy_id FROM $wpdb->terms t, $wpdb->term_taxonomy tt WHERE t.term_id = tt.term_id AND name = 'bundle' LIMIT 1" , ARRAY_A );

                if ( $bundle_term_taxonomy_id )
                    $exclude_types[] = (int) $bundle_term_taxonomy_id[ 'term_taxonomy_id' ];
            }

            // Check if WC Composite Products is active then we excluded composite products
            if( is_plugin_active( 'woocommerce-composite-products/woocommerce-composite-products.php' ) ){

                // Get composite product term id
                $composite_term_taxonomy_id = $wpdb->get_row( "SELECT term_taxonomy_id FROM $wpdb->terms t, $wpdb->term_taxonomy tt WHERE t.term_id = tt.term_id AND name = 'composite' LIMIT 1" , ARRAY_A );

                if ( $composite_term_taxonomy_id )
                    $exclude_types[] = (int) $composite_term_taxonomy_id[ 'term_taxonomy_id' ];
            }

            // Query the excluded product IDs.
            if ( ! empty( $exclude_types ) ) {

                $exclude_types_string = implode( ',' , $exclude_types );

                // Get all composite products
                $query = "SELECT DISTINCT p.ID
                      FROM $wpdb->posts p
                      INNER JOIN $wpdb->term_relationships tr
                        ON p.ID = tr.object_id
                      WHERE tr.term_taxonomy_id IN ( $exclude_types_string )
                      AND p.post_status = 'publish'
                      AND p.post_type = 'product'";

                $results = $wpdb->get_results( $query , ARRAY_A );

                foreach ( $results as $result )
                    $exclude_products[] = (int) $result[ 'ID' ];

                $wpdb->flush();
            }

            return $exclude_products;

        }

        /**
         * WWOF-168 : Don't display hidden products in the order form
         * On WooCommerce v3.0.0 a product is declared hidden only if it has both 'exclude-from-search' and 'exclude-from-catalog' terms.
         *
         * @return array
         * @since 1.6.6
         */
        public static function wwof_get_excluded_hidden_products(){

            $search     = array();
            $catalog    = array();

            if( WWOF_Functions::wwof_is_woocommerce_version_3() ){

                global $wpdb;

                $exclude_from_search_term_taxonomy_id = $wpdb->get_row( "SELECT term_taxonomy_id FROM $wpdb->terms t, $wpdb->term_taxonomy tt WHERE t.term_id = tt.term_id AND name = 'exclude-from-search' LIMIT 1" , ARRAY_A );
                $exclude_from_search_term_taxonomy_id = ( $exclude_from_search_term_taxonomy_id ) ? (int) $exclude_from_search_term_taxonomy_id[ 'term_taxonomy_id' ] : 0;

                $exclude_from_catalog_term_taxonomy_id = $wpdb->get_row( "SELECT term_taxonomy_id FROM $wpdb->terms t, $wpdb->term_taxonomy tt WHERE t.term_id = tt.term_id AND name = 'exclude-from-catalog' LIMIT 1" , ARRAY_A );
                $exclude_from_catalog_term_taxonomy_id = ( $exclude_from_catalog_term_taxonomy_id ) ? (int) $exclude_from_catalog_term_taxonomy_id[ 'term_taxonomy_id' ] : 0;

                // Get all exclude_from_search products
                $query1 = "SELECT DISTINCT p.ID
                      FROM $wpdb->posts p
                      INNER JOIN $wpdb->term_relationships tr
                        ON p.ID = tr.object_id
                      WHERE tr.term_taxonomy_id = $exclude_from_search_term_taxonomy_id
                      AND p.post_status = 'publish'
                      AND p.post_type = 'product'";

                $results1 = $wpdb->get_results( $query1 , ARRAY_A );

                foreach ( $results1 as $result )
                    $search[] = (int) $result[ 'ID' ];

                // Get all exclude_from_catalog products
                $query2 = "SELECT DISTINCT p.ID
                      FROM $wpdb->posts p
                      INNER JOIN $wpdb->term_relationships tr
                        ON p.ID = tr.object_id
                      WHERE tr.term_taxonomy_id = $exclude_from_catalog_term_taxonomy_id
                      AND p.post_status = 'publish'
                      AND p.post_type = 'product'";

                $results2 = $wpdb->get_results( $query2 , ARRAY_A );

                foreach ( $results2 as $result )
                    $catalog[] = (int) $result[ 'ID' ];

            }

            $hidden_products = array_intersect( $search , $catalog );

            if ( !empty( $hidden_products ) && get_option( 'wwof_general_list_product_variation_individually' , 'no' ) === 'yes' ) {

                $hidden             = implode( ',', $hidden_products );
                $variations_query   = $wpdb->get_results( "SELECT p.ID FROM $wpdb->posts p WHERE p.post_status = 'publish' AND p.post_parent IN ( $hidden )" , ARRAY_A );
                $variations_list    = array();

                foreach ( $variations_query as $variation )
                    $variations_list[] = (int) $variation[ 'ID' ];

                $hidden_products = array_merge( $hidden_products , $variations_list );

            }

            return $hidden_products;

        }

        /**
         * WWOF-178 : Return true if all variation is out of stock else false
         *
         * @param $product WC_Product Object
         * @return bool
         * @since 1.6.6
         */
        public static function wwof_out_of_stock_variations_check( $product ){

            if ( WWOF_Functions::wwof_get_product_type( $product ) == 'variable' ) {

                $product_variations     = $product->get_available_variations();
                $total_variations       = count( $product_variations );
                $out_of_stock_counter   = 0;

                if( !empty( $product_variations ) ){
                    foreach ( $product_variations as $variation ) {

                        if( isset( $variation[ 'is_in_stock' ] ) && $variation[ 'is_in_stock' ] === false )
                            $out_of_stock_counter++;

                    }
                }

                if( !empty( $total_variations ) && $out_of_stock_counter === $total_variations )
                    return true; // All variation is out of stock

            }

            return false; // Not all are out of stock

        }

        /*
         |------------------------------------------------------------------------------------------------------------------
         | Sorting Data Functions
         |------------------------------------------------------------------------------------------------------------------
         */

        /**
         * Sorting callback for usort function. Mainly for sorting variable variations.
         *
         * @param $arr1
         * @param $arr2
         * @return int
         *
         * @since 1.1.1
         * @since 1.6.6 Refactor codebase and move to its proper model
         */
        public static function wwof_usort_callback ( $arr1 , $arr2 ) {

            return strcasecmp( $arr1[ 'text' ] , $arr2[ 'text' ] );

        }

        /**
         * usort callback that sorts variations based on menu order.
         *
         * @since 1.3.0
         * @since 1.6.0 This sort function also sorts the variation ID in desc order to show in the exact same order as the backend listing.
         * @since 1.6.6 Refactor codebase and move to its proper model
         *
         * @param $arr1
         * @param $arr2
         * @return int
         */
        public static function wwof_usort_variation_menu_order( $arr1 , $arr2 ) {

            $product1_id = $arr1[ 'value' ];
            $product2_id = $arr2[ 'value' ];

            $product1_menu_order = get_post_field( 'menu_order', $product1_id );
            $product2_menu_order = get_post_field( 'menu_order', $product2_id );

            if ( $product1_menu_order == $product2_menu_order ){
                if( $arr1[ 'value' ] == $arr2[ 'value' ] )
                    return 0;
                return $arr1[ 'value' ] > $arr2[ 'value' ] ? -1 : 1;
            }

            return ( $product1_menu_order < $product2_menu_order ) ? -1 : 1;

        }

        /**
         * Sort a taxonomy term in hierarchy. Recursive function.
         *
         * Credit:
         * http://wordpress.stackexchange.com/questions/14652/how-to-show-a-hierarchical-terms-list#answer-99516
         *
         * @since 1.3.0
         * @since 1.6.6 Refactor codebase and move to its proper model
         *
         * @param array $cats
         * @param array $into
         * @param int   $parent_id
         */
        public static function wwof_sort_terms_hierarchicaly( Array &$cats , Array &$into , $parent_id = 0 ) {

            foreach ( $cats as $i => $cat ) {
                if ( $cat->parent == $parent_id ) {
                    $into[ $cat->term_id ] = $cat;
                    unset( $cats[ $i ] );
                }
            }

            foreach ( $into as $top_cat ) {
                $top_cat->children = array();
                self::wwof_sort_terms_hierarchicaly( $cats , $top_cat->children , $top_cat->term_id );
            }

        }

        /**
         * Get all product variations that are out of stock.
         *
         * @since 1.7.0
         * @access public
         *
         * @return array list of out of stock product variations.
         */
        public static function wwof_get_out_of_stock_variations() {

            $args = array(
        		'post_type'		  => 'product_variation',
        		'status'		  => 'publish',
        		'posts_per_page'  => -1,
        		'fields'		  => 'ids',
        		'meta_query'	  => array(
        			array(
        				'key'     => '_stock_status',
        				'value'   => 'outofstock',
        				'compare' => '=',
        				'type'    => 'string'
        			)
        		)
        	);

        	$query = new WP_Query( $args );

            return $query->posts;
        }

        /**
         * Get product quantity input arguments. Fetch data for 'min', 'max', and 'step' properties.
         *
         * @since 1.7.0
         * @access public
         *
         * @param WC_Product $product product object.
         * @return array quantity input arguments.
         */
        public static function get_product_quantity_input_args( $product ) {

            $product_type = WWOF_Functions::wwof_get_product_type( $product );

            // add support to WooCommerce Min/Max Quantities plugin
            if ( is_a( $product , 'WC_Product' ) &&  in_array( $product_type , array( 'variation' , 'variable' ) ) ) {

                switch ( $product_type ) {

                    case 'variable' :
                        $variations         = $product->get_available_variations();
                        $first_variation    = array_shift( $variations );
                        $variation_id       = $first_variation[ 'variation_id' ];
                        $parent_product     = $product;
                        $parent_product_id  = WWOF_Functions::wwof_get_product_id( $product );
                        break;

                    case 'variation' :
                        $variation_id       = WWOF_Functions::wwof_get_product_id( $product );
                        $parent_product     = WWOF_Functions::wwof_get_product_variation_parent( $product );
                        $parent_product_id  = WWOF_Functions::wwof_get_product_id( $parent_product );
                        break;
                }

                // Setup default values
                $step_value     = 1;
                $input_value    = 1;
                $min_value      = 1;
                $max_value      = '';

                // WooCommerce Min Max Quantities integration takes precedence
                if ( 'yes' === get_post_meta( $variation_id , 'min_max_rules' , true ) && WWOF_Functions::is_plugin_active( 'woocommerce-min-max-quantities/woocommerce-min-max-quantities.php' ) ) {

                    // Get the values from the Min Max Quantities plugin settings
                    $step_value     = get_post_meta( $variation_id , 'variation_group_of_quantity' , true );
                    $input_value    = get_post_meta( $variation_id , 'variation_minimum_allowed_quantity' , true );
                    $min_value	    = get_post_meta( $variation_id , 'variation_minimum_allowed_quantity' , true );
                    $max_value      = get_post_meta( $variation_id , 'variation_maximum_allowed_quantity' , true );

                    // If the variation or the parent product are managing stock and the stock quantity is less than
                    // the Max from the Min Max Quantities plugin, then use that instead
                    if ( $product->managing_stock() == 'yes' || $parent_product->managing_stock() == 'yes' ) {
                            $stock_quantity = $product->get_stock_quantity();

                            if ( $stock_quantity > 0 && $stock_quantity <= $max_value )
                                $max_value = $stock_quantity;
                    }

                    $input_args = array(
                        'step'		    => $step_value,
                        'input_value'	=> $input_value,
                        'min_value'	    => $min_value,
                        'max_value'	    => $max_value
                    );

                } else {

                    // If we aren't using the Min Max Quantities plugin integration, we should use our own Wholesale Min qty value as the next
                    global $wc_wholesale_prices_premium, $wc_wholesale_prices;

                    // We only do this if WWPP is installed and active
                    if ( get_class( $wc_wholesale_prices_premium ) == 'WooCommerceWholeSalePricesPremium' &&
                         get_class( $wc_wholesale_prices ) == 'WooCommerceWholeSalePrices' ) {

                        $wholesale_role = $wc_wholesale_prices->wwp_wholesale_roles->getUserWholesaleRole();

                        // We only do this if wholesale user
                        if ( !empty( $wholesale_role ) ) {

                            // Check for min order qty on variation
                            $min_order_qty = get_post_meta( $variation_id , $wholesale_role[ 0 ] . '_wholesale_minimum_order_quantity' , true );

                            // If min order qty not set on the variation check the parent product
                            if ( empty( $min_order_qty ) ) {
                                $min_order_qty = get_post_meta( $parent_product_id , $wholesale_role[ 0 ] . '_variable_level_wholesale_minimum_order_quantity' , true );
                            }

                            if ( $min_order_qty ) {
                                $input_value    = $min_order_qty;
                                $min_value      = $min_order_qty;
                            }

                            // If the variation or the parent product are managing stock then get the stock quantity as the max value
                            if ( $product->managing_stock() == 'yes' || $parent_product->managing_stock() == 'yes' ) {
                                    $max_value = '';
                                    $stock_quantity = $product->get_stock_quantity();

            						if ( $stock_quantity > 0 )
                                        $max_value = $stock_quantity;

                            }

                            // If backorders are allowed we don't limit the max value at all in any circumstance
                            if ( $product->backorders_allowed() )
                                $max_value = '';

                        }

                    }

                    // apply variable level input arguments.
                    $input_args = apply_filters( 'woocommerce_quantity_input_args', array(
                        'step'	        => $step_value,
                        'input_value'	=> $input_value,
                        'min_value'     => 1,
                        'max_value'     => $max_value
                    ) , $parent_product );
                }

            } else
                $input_args = apply_filters( 'woocommerce_quantity_input_args', array( 'input_value'	=> '1' ) , $product );

            // apply min and step value set on WWPP (priority)
            return apply_filters( 'wwof_variation_quantity_input_args' , $input_args , $product );
        }

        /**
         * Validate selected quantity of the product.
         *
         * @since 1.7.0
         * @access public
         *
         * @param int $quantity    entered quantity to be added to cart.
         * @param int product_id   ID of the product.
         * @param int variation_id ID of the variation (optional).
         * @return boolean
         */
        public static function wwof_validate_product_selected_quantity( $quantity , $product_id , $variation_id = null ) {

            $product    = ( $variation_id ) ? wc_get_product( $variation_id ) : wc_get_product( $product_id );
            $input_args = WWOF_Product_Listing_Helper::get_product_quantity_input_args( $product );
            $step       = ( isset( $input_args[ 'step' ] ) && $input_args[ 'step' ] ) ? $input_args[ 'step' ] : 1;
            $min        = ( isset( $input_args[ 'min_value' ] ) && $input_args[ 'min_value' ] ) ? $input_args[ 'min_value' ] : 1;
            $max        = ( isset( $input_args[ 'max_value' ] ) && $input_args[ 'max_value' ] ) ? $input_args[ 'max_value' ] : '';

            if ( $quantity < $min || ( $max && $quantity > $max ) || $quantity % $step != 0 )
                return;

            return true;
        }

        /**
         * Custom sort call back for sorting product terms hierarchy. It sorts by slug.
         *
         * @since 1.3.2
         * @since 1.6.6 Refactor codebase and move to its proper model
         *
         * @param $a
         * @param $b
         * @return int
         */
        public static function wwof_product_terms_hierarchy_usort_callback( $a , $b ) {

            if ( $a->slug == $b->slug )
                return 0;

            return ( $a->slug < $b->slug ) ? -1 : 1;

        }

        /**
         * Get wholesale prices for each available variation.
         *
         * @since 1.8.0
         * @access public
         *
         * @param array $available_variations List of variable product available variations.
         * @param array $wholesale_role       Array of user wholesale role.
         */
        public static function wwof_get_variations_wholesale_price( &$available_variations , $wholesale_role ) {

            if ( ! is_array( $available_variations ) || empty( $available_variations ) )
                return;

            foreach ( $available_variations as $key => $value ) {

                if ( WWOF_Functions::wwof_dependency_version_compare( 'wwp' , '1.6.0' , '>=' ) ) {

                    $price_arr      = WWP_Wholesale_Prices::get_product_wholesale_price_on_shop_v2( $value[ 'variation_id' ] , $wholesale_role );
                    $wholesalePrice = $price_arr[ 'wholesale_price' ];

                } elseif ( WWOF_Functions::wwof_dependency_version_compare( 'wwp' , '1.5.0' , '>=' ) )
                    $wholesalePrice = WWP_Wholesale_Prices::get_product_wholesale_price_on_shop( $value[ 'variation_id' ] , $wholesale_role );
                else
                    $wholesalePrice = WWP_Wholesale_Prices::getProductWholesalePrice( $value[ 'variation_id' ] , $wholesale_role );

                $available_variations[ $key ][ 'wholesale_price' ] = $wholesalePrice;

            }

        }

        /**
         * Update the input arguments of the available variations list.
         *
         * @since 1.8.0
         * @access public
         *
         * @param array $available_variations List of variable product available variations.
         */
        public static function wwof_update_variations_input_args( &$available_variations ) {

            if ( ! is_array( $available_variations ) || empty( $available_variations ) )
                return;

            foreach ( $available_variations as $key => $variation ) {

                $product    = wc_get_product( $variation[ 'variation_id' ] );
                $input_args = self::get_product_quantity_input_args( $product );

                $available_variations[ $key ][ 'min_qty' ]     = $input_args[ 'min_value' ];
                $available_variations[ $key ][ 'max_qty' ]     = $input_args[ 'max_value' ];
                $available_variations[ $key ][ 'input_value' ] = $input_args[ 'input_value' ];
                $available_variations[ $key ][ 'step' ]        = $input_args[ 'step' ];
            }
        }

        /*
         |------------------------------------------------------------------------------------------------------------------
         | Set Cookies Functions
         |------------------------------------------------------------------------------------------------------------------
         */

        /**
         * Set cart cookies.
         * Bug Fix : WWOF-16
         *
         * @since 1.2.2
         * @since 1.6.6 Refactor codebase and move to its proper model
         */
        public static function wwof_maybe_set_cart_cookies () {

            if ( sizeof( WC()->cart->cart_contents ) > 0 ){

                wc_setcookie( 'woocommerce_items_in_cart' , 1 );
                wc_setcookie( 'woocommerce_cart_hash' , md5( json_encode( WC()->cart->get_cart_for_session() ) ) );

            }elseif ( isset( $_COOKIE[ 'woocommerce_items_in_cart' ] ) ){

                wc_setcookie( 'woocommerce_items_in_cart' , 0, time() - ( 60 * 60 ) ); // (60 * 60 ) Hours in seconds
                wc_setcookie( 'woocommerce_cart_hash' , '' , time() - ( 60 * 60 ) );

            }

            do_action( 'woocommerce_set_cart_cookies' );

        }

        /**
         * WWOF get variable product available variations.
         * 
         * @since 1.8.1
         * @access public
         * 
         * @param WC_Product_Variable $variable_product Variable product.
         * @return array Variable product variations.
         */
        public static function wwof_get_available_variations( $variable_product ) {

            $hide_wholesale_discount = get_option( "wwof_general_hide_quantity_discounts" ); // Option to hide Product Quantity Based Wholesale Pricing

            if ( $hide_wholesale_discount === 'yes' ) {

                add_filter( 'wwof_hide_table_on_wwof_form' , '__return_true' );
                add_filter( 'wwof_hide_per_category_table_on_wwof_form' , '__return_true' );
                add_filter( 'wwof_hide_per_wholesale_role_table_on_wwof_form' , '__return_true' );

            }

            $available_variations = $variable_product->get_available_variations();

            if ( $hide_wholesale_discount === 'yes' ) {

                remove_filter( 'wwof_hide_table_on_wwof_form' , '__return_true' );
                remove_filter( 'wwof_hide_per_category_table_on_wwof_form' , '__return_true' );
                remove_filter( 'wwof_hide_per_wholesale_role_table_on_wwof_form' , '__return_true' );

            }

            return $available_variations;

        }

        /*
         |------------------------------------------------------------------------------------------------------------------
         | Utility Functions
         |------------------------------------------------------------------------------------------------------------------
         */

        /**
         * Load templates in an overridable manner.
         *
         * @param $template Template path
         * @param $options Options to pass to the template
         * @param $default_template_path Default template path
         *
         * @since 1.0.0
         * @since 1.6.6 Refactor codebase and move to its proper model
         */
        public static function _load_template( $template , $options , $default_template_path ) {

            wc_get_template( $template , $options , '' , $default_template_path );

        }

    }

}

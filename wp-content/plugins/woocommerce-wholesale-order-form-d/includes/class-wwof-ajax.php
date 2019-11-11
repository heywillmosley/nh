<?php if ( !defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( !class_exists( 'WWOF_AJAX' ) ) {

    class WWOF_AJAX {

        /*
        |--------------------------------------------------------------------------
        | Class Properties
        |--------------------------------------------------------------------------
        */

        /**
         * Property that holds the single main instance of WWOF_AJAX.
         *
         * @since 1.6.6
         * @access private
         * @var WWOF_AJAX
         */
        private static $_instance;

        /**
         * Model that houses the logic of retrieving information relating to WWOF License Settings.
         *
         * @since 1.6.6
         * @access private
         * @var WWOF_WWS_License_Settings
         */
        private $_wwof_wws_license_settings;

        /**
         * Model that houses the logic of retrieving information relating to WWOF Product Listings.
         *
         * @since 1.6.6
         * @access private
         * @var WWOF_Product_Listing
         */
        private $_wwof_product_listings;

        /**
         * Model that houses the logic of retrieving information relating to WWOF Permissions.
         *
         * @since 1.6.6
         * @access private
         * @var WWOF_Permissions
         */
        private $_wwof_permissions;

        /**
         * Model that houses the logic of retrieving information relating to WWOF WWP Wholesale Prices.
         *
         * @since 1.6.6
         * @access private
         * @var WWOF_WWP_Wholesale_Prices
         */
        private $_wwof_wwp_wholesale_prices;

        /*
        |--------------------------------------------------------------------------
        | Class Methods
        |--------------------------------------------------------------------------
        */

        /**
         * WWOF_AJAX constructor.
         *
         * @param array $dependencies Array of instance objects of all dependencies of WWOF_AJAX model.
         *
         * @access public
         * @since 1.6.6
         */
        public function __construct( $dependencies ) {

            $this->_wwof_wws_license_settings   = $dependencies[ 'WWOF_WWS_License_Settings' ];
            $this->_wwof_product_listings       = $dependencies[ 'WWOF_Product_Listing' ];
            $this->_wwof_permissions            = $dependencies[ 'WWOF_Permissions' ];
            $this->_wwof_wwp_wholesale_prices   = $dependencies[ 'WWOF_WWP_Wholesale_Prices' ];

        }

        /**
         * Ensure that only one instance of WWOF_AJAX is loaded or can be loaded (Singleton Pattern).
         *
         * @param array $dependencies Array of instance objects of all dependencies of WWOF_AJAX model.
         *
         * @return WWOF_AJAX
         * @since 1.6.6
         */
        public static function instance( $dependencies = null ) {

            if ( !self::$_instance instanceof self )
                self::$_instance = new self( $dependencies );

            return self::$_instance;

        }

        /**
         * Create wholesale page.
         *
         * @param null $dummy_arg
         *
         * @return bool
         * @since 1.0.0
         * @since 1.6.6 Refactor codebase and move to its proper model
         */
        public function wwof_create_wholesale_page ( $dummy_arg = null ) {

            if ( get_post_status( get_option( WWOF_SETTINGS_WHOLESALE_PAGE_ID ) ) !== 'publish' && !get_page_by_title( 'Wholesale Page' ) ) {

                $wholesale_page = array(
                                        'post_content'  =>  '[wwof_product_listing]',// The full text of the post.
                                        'post_title'    =>  __( 'Wholesale Ordering' , 'woocommerce-wholesale-order-form' ),// The title of your post.
                                        'post_status'   =>  'publish',
                                        'post_type'     =>  'page'
                                    );

                $result = wp_insert_post( $wholesale_page );

                if ( $result === 0 || is_wp_error( $result ) ) {

                    if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {

                        header('Content-Type: application/json'); // specify we return json
                        echo json_encode(array(
                            'status'        =>  'failed',
                            'error_message' =>  __( 'Failed to create wholesale ordering page.' , 'woocommerce-wholesale-order-form' )
                        ));
                        die();

                    } else
                        return false;

                } else {

                    // Update wholesale page id setting
                    update_option( WWOF_SETTINGS_WHOLESALE_PAGE_ID , $result );

                    if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {

                        header( 'Content-Type: application/json' ); // specify we return json
                        echo json_encode( array( 'status' => 'success' ) );
                        die();

                    } else
                        return true;

                }

            } else {

                if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {

                    header( 'Content-Type: application/json' ); // specify we return json
                    echo json_encode( array( 'status' => 'success' ) );
                    die();

                } else
                    return true;

            }

        }

        /**
         * Display Product Listing.
         *
         * @since 1.0.0
         * @since 1.3.0 Add capability to sort by sku
         * @since 1.3.1 Add product category validation
         * @since 1.3.5 Only show products with visibility of 'Catalog/Search' and 'Catalog'. 'Hidden' and 'Search' visibility should not shown.
         * @since 1.6.6 Refactor codebase and move to its proper model
         * @since 1.7.0 Updated query to support product_variation post type be inlcuded on the results
         * @since 1.8.0 Allow loading of translated products for guest users (WWOF-256)
         *
         * @param int  $paged
         * @param null $search
         * @param null $cat_filter
         * @param null $shortcode_atts
         * @param      $user_has_access
         *
         * @return string
         */
        public function wwof_display_product_listing( $paged = 1 , $search = null , $cat_filter = null , $shortcode_atts = null , $lang = 0 ) {

            ob_start();

            $user_has_access = $this->_wwof_permissions->wwof_user_has_access();

            // Check if the user has permission
            if ( $user_has_access ) {

                global $sitepress;

                if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {

                    $paged          = trim( $_POST[ 'paged' ] );
                    $search         = trim( $_POST[ 'search' ] );
                    $cat_filter     = trim( $_POST[ 'cat_filter' ] );
                    $shortcode_atts = $_POST[ 'shortcode_atts' ];

                }

                if ( is_object( $sitepress ) ) {

                    $code = $sitepress->get_language_from_url( $_SERVER[ "HTTP_REFERER" ] );
                    $sitepress->switch_lang( $code );

                }

                if ( empty( $paged ) || is_null( $paged ) || !is_numeric( $paged ) )
                    $paged = 1;

                if ( empty( $search ) || $search == "" )
                    $search = null;

                global $WWOF_SETTINGS_DEFAULT_PPP, $WWOF_SETTINGS_DEFAULT_SORT_BY, $WWOF_SETTINGS_DEFAULT_SORT_ORDER;

                $posts_per_page      = get_option( 'wwof_general_products_per_page' );
                $show_zero_prod      = get_option( 'wwof_general_display_zero_products' );
                $settings_cat_filter = get_option( 'wwof_filters_product_category_filter' ); // Category Filter on the settings area, not on the search area
                $prod_filter         = get_option( 'wwof_filters_exclude_product_filter' );
                $sort_by             = get_option( 'wwof_general_sort_by' );
                $sort_order          = get_option( 'wwof_general_sort_order' );
                $search_sku          = get_option( 'wwof_general_allow_product_sku_search' );

                // Process categry list from the shortcode attributes
                $atts_cats = explode ( "," , $shortcode_atts[ 'categories' ] );
                foreach ( $atts_cats as $index => $cat_id )
                    $atts_cats[ $index ] = ( int ) filter_var( trim( $cat_id ) , FILTER_SANITIZE_STRING );

                // Process product list from shortcode attributes
                $atts_products = explode( "," , $shortcode_atts[ 'products' ] );
                foreach ( $atts_products as $index => $product_id )
                    $atts_products[ $index ] = ( int ) filter_var( trim( $product_id ) , FILTER_SANITIZE_STRING );

                if ( !isset( $posts_per_page ) || $posts_per_page === false || strcasecmp( trim( $posts_per_page ) , '' ) == 0 )
                    $posts_per_page = $WWOF_SETTINGS_DEFAULT_PPP;

                if( !isset( $sort_by ) || $sort_by === false || strcasecmp( trim( $sort_by ) , '' ) == 0 )
                    $sort_by = $WWOF_SETTINGS_DEFAULT_SORT_BY;

                if( !isset( $sort_order ) || $sort_order === false || strcasecmp( trim( $sort_order ) , '' ) == 0 )
                    $sort_order = $WWOF_SETTINGS_DEFAULT_SORT_ORDER;


                // =========================================================================================================
                // Begin Construct Main Query Args
                // =========================================================================================================

                $meta_query = array();
                $tax_query  = array();

                if( WWOF_Functions::wwof_is_woocommerce_version_3() ) {
                    $meta_query = array(
                                            array(
                                                'key'       => '_price',
                                                'value'     => 0,
                                                'compare'   => '>='
                                            )
                                        );
                    $tax_query  = array();
                }else{
                    $meta_query = array(
                                            array(
                                                'key'     => '_visibility',
                                                'value'   => 'hidden',
                                                'compare' => '!=',
                                                'type'    => 'string'
                                            ),
                                            array(
                                                'key'     => '_price',
                                                'value'   => 0,
                                                'compare' => '>='
                                            )
                                        );
                    $tax_query  = array();
                }

                // If 'Display Zero Inventory Products?' is enabled, we show both instock and outofstock status of the parent else just instock.
                // Note: Variations are not checked here
                if( $show_zero_prod == 'yes' ){
                    $meta_query[] = array(
                                        'relation' => 'OR',
                                            array(
                                                'key'     => '_stock_status',
                                                'value'   => 'instock',
                                                'compare' => '=',
                                                'type'    => 'string'
                                            ),
                                            array(
                                                'key'     => '_stock_status',
                                                'value'   => 'outofstock',
                                                'compare' => '=',
                                                'type'    => 'string'
                                            ),
                                            array(
                                                'key'     => '_stock_status',
                                                'value'   => 'onbackorder',
                                                'compare' => '=',
                                                'type'    => 'string'
                                            )
                                        );
                }else{
                    $meta_query[] = array(
                                        'key'     => '_stock_status',
                                        'value'   => 'instock',
                                        'compare' => '=',
                                        'type'    => 'string'
                                    );
                }

                // Core args -----------------------------------------------------------------------------------------------
                $args = array(
                    'post_type'             => 'product',
                    'post_status'           => 'publish',
                    'posts_per_page'        => $posts_per_page,
                    'ignore_sticky_posts'   => 1,
                    'fields'                => 'id=>parent'
                );

                // Sort related args ---------------------------------------------------------------------------------------
                switch( $sort_by ){
                    case 'menu_order':
                        $args[ 'order' ] = $sort_order;
                        $args[ 'orderby' ]  = 'menu_order title';
                        break;
                    case 'name':
                        $args[ 'order' ] = $sort_order;
                        $args[ 'orderby' ] = 'title';
                        break;
                    case 'date':
                        $args[ 'order' ] = $sort_order;
                        $args[ 'orderby' ] = 'date';
                        break;
                    case 'sku':
                        $args[ 'order' ]    = $sort_order;
                        $args[ 'orderby' ]  = 'meta_value';
                        $args[ 'meta_key' ] = '_sku';
                    case 'price':
                        //TODO: enhance price logic
                        //$args['order'] = $sort_order;
                        //$args['orderby'] = "meta_value_num";
                        //$args['meta_key'] = '_price';
                        //$args['meta_query'][] = array(
                        //    'key'   =>  '_price'
                        //);
                        break;
                    case 'popularity':
                        // TODO:
                        break;
                    case 'rating':
                        // TODO:
                        break;
                    case 'default':
                        $args[ 'order' ] = $sort_order;
                        break;
                }

                // Paged related args --------------------------------------------------------------------------------------
                if ( $paged > 0 )
                    $args[ 'paged' ] = $paged;

                // Category filter related args ----------------------------------------------------------------------------

                // Validate product category filter
                $settings_cat_filter = $this->_wwof_product_listings->wwof_category_filter_validator( $settings_cat_filter );

                if ( !in_array( 0 , $atts_cats ) && $cat_filter == '' ) {

                    $atts_cats_terms = array();

                    foreach ( $atts_cats as $cat_id ) {

                        $term_obj = get_term_by( 'id' , $cat_id , 'product_cat' );
                        $atts_cats_terms[] = $term_obj->slug;

                    }

                    // Validate
                    $cat_term_slugs = $this->_wwof_product_listings->wwof_category_filter_validator( $atts_cats_terms , false );

                } elseif ( is_array( $settings_cat_filter ) && !empty( $settings_cat_filter ) && $cat_filter == '' )
                    $cat_term_slugs = $settings_cat_filter;
                elseif( $cat_filter != '' )
                    $cat_term_slugs = array( $cat_filter );
                else
                    $cat_term_slugs = array();

                // Product category filter
                if ( is_array( $cat_term_slugs ) && ! empty( $cat_term_slugs ) ) {

                    $tax_query[] = array(
                        'taxonomy' => 'product_cat',
                        'field'    => 'slug',
                        'terms'    => $cat_term_slugs,
                    );

                }

                /**
                 * @since 1.7.0
                 * In order to properly support the product_variations to be displayed as simple products, we needed to
                 * separate the "product query" from the "core query". The "product query" is where the products are queried
                 * with respect to the $meta_query and $tax_query arguments. The "core query" will be the one used on the
                 * product loop outputed of this function. The resulting product IDs of the "product query" will then be added
                 * to the core query via the "post__in" argument. The purpose of this is to make the "core query" flexible
                 * so it can safely include 'product_variation' post types in its loop. The "product query" will then handle
                 * all of the requirements set on the $meta_query and $tax_query for the 'product' post type only, as both
                 * arguments are not fully supported with the 'product_variation' post type.
                 *
                 */
                // Main Product query --------------------------------------------------------------------------------------

                // this will hold the resulted variation ids
                $product_query_ids           = array();
                $variable_products_to_remove = array();

                $product_args = array(
                    'post_type'             => 'product',
                    'post_status'           => 'publish',
                    'posts_per_page'        => -1,
                    'ignore_sticky_posts'   => 1,
                    'meta_query'            => apply_filters( 'wwof_product_listing_price_filter_meta_query' , $meta_query ),
                    'tax_query'             => apply_filters( 'wwof_product_listing_price_filter_tax_query' , $tax_query ),
                    'fields'                => 'ids'
                );

                $product_query     = new WP_Query( $product_args );
                $product_query_ids = $product_query->posts;

                // List Product Variations Individually --------------------------------------------------------------------

                if ( get_option( 'wwof_general_list_product_variation_individually' , 'no' ) === 'yes' ) {

                    // change the post type argument to include variations
                    $args[ 'post_type' ] = array( 'product' , 'product_variation' );
                    $outofstock_variations = WWOF_Product_Listing_Helper::wwof_get_out_of_stock_variations();

                    /**
                     * Get product variation ids and merge with $product_query_ids to include
                     * in the main query via 'post__in' argument.
                     */
                    if ( ! empty( $product_query_ids ) ) {

                        $variation_cat_query_ids     = array();

                        foreach ( $product_query_ids as $key => $id ) {

                            if ( is_array( $prod_filter ) && in_array( $id , $prod_filter ) )
                                continue;

                            $product = wc_get_product( $id );

                            if ( WWOF_Functions::wwof_get_product_type( $product ) == 'variable' ) {
                                $children = $product->get_children();

                                if ( $show_zero_prod != 'yes' )
                                    $children = array_diff( $children , $outofstock_variations );

                                $variation_cat_query_ids = array_merge( $variation_cat_query_ids , $children );
                                $variable_products_to_remove[] = $id;
                                unset( $product_query_ids[ $key ] );

                            }

                        }

                        if ( ! empty( $variation_cat_query_ids ) )
                            $product_query_ids = array_merge( $variation_cat_query_ids , $product_query_ids );

                    }

                }

                // Products attribute and the excluded products list on the settings must not go together
                // It's either only one of em.
                // Product exclusion related args --------------------------------------------------------------------------
                if ( in_array( 0 , $atts_products ) && is_array( $prod_filter ) && !empty( $prod_filter ) )
                    $args[ 'post__not_in' ] = $prod_filter;


                // =========================================================================================================
                // End Construct Main Query Args
                // =========================================================================================================

                // Product Search
                $search_products = array();
                if ( !is_null( $search ) ) {
                    $search_the_sku = ( $search_sku == 'yes' ) ? true : false;
                    $search_products = WWOF_Product_Listing_Helper::get_search_products( $search , $search_the_sku );
                }

                // Post in
                if ( !in_array( 0 , $atts_products ) ) {

                    $post_in = $atts_products;

                    foreach ( $post_in as $key => $value ) {

                        if( !empty( $search_products ) && !in_array( $value , $search_products ) )
                            unset( $post_in[ $key ] );

                        if( !empty( $product_query_ids ) && !in_array( $value , $product_query_ids ) && !empty( $cat_term_slugs ) )
                            unset( $post_in[ $key ] );

                    }

                } else {

                    $post_in = array();

                    // add resulted product ids of the product query to the 'post__in' argument
                    if ( ! empty( $product_query_ids ) )
                        $post_in = array_merge( $post_in , $product_query_ids );


                    if ( !empty( $search_products ) && !empty( $in_stock_products ) ) {

                        $temp_post_in = array_unique( array_intersect( $search_products , $in_stock_products ) );

                        if ( !empty( $post_in ) )
                            $post_in = array_unique( array_intersect( $post_in , $temp_post_in ) );
                        else
                            $post_in = $temp_post_in;

                    } elseif ( !empty( $search_products ) && empty( $in_stock_products ) ) {

                        if ( !empty( $post_in ) )
                            $post_in = array_unique( array_intersect( $post_in , $search_products ) );
                        else
                            $post_in = $search_products;

                    } elseif ( empty( $search_products ) && !empty( $in_stock_products ) ) {

                        $post_in = array_unique( array_intersect( $post_in , $in_stock_products ) );

                        if ( !empty( $post_in ) )
                            $post_in = array_unique( array_intersect( $post_in , $in_stock_products ) );
                        else
                            $post_in = $in_stock_products;

                    }

                }

                // We need to check if post_in is empty, and there are some explicit filters
                // 1. if do not show zero inventory products
                // 2. if there is a search
                // if we put empty array in post_in on wp query, it will return all posts
                // that's why we need to add an array with value of zero ( no post has id of zero ) so post_in fails, which is what we want
                // coz meaning either or both 1. and 2. is not meet.
                if ( empty( $post_in ) && ( $show_zero_prod != 'yes' && !is_null( $search ) ) )
                    $post_in = array( 0 );


                // Execute Main Query ======================================================================================


                // Products attribute and the excluded products list on the settings must not go together
                // It's either only one of em.
                if ( !empty( $post_in ) ) {

                    if ( in_array( 0 , $atts_products ) && is_array( $prod_filter ) && !empty( $prod_filter ) )
                        $post_in = array_diff( $post_in , $prod_filter );

                    $post_in = array_map( 'intval' , $post_in ); // Convert all values from string to int
                    $args[ 'post__in' ] = $post_in;

                }

                // Get Excluded IDs ( Exclude Bundle and Composite product types since we do not support these yet )
                $excluded_products1 = WWOF_Product_Listing_Helper::wwof_get_excluded_product_ids();

                // Get all variations that have all variations set to out of stock status
                $excluded_products2 = WWOF_Product_Listing_Helper::wwof_get_excluded_variable_ids();

                // Get all products that has product visibility to hidden
                $excluded_products3 = WWOF_Product_Listing_Helper::wwof_get_excluded_hidden_products();

                // Merge excluded products ( Bundle, Composite and Product Variable that has all variations in out of stock status )
                $excluded_products = array_merge( $excluded_products1 , $excluded_products2 , $excluded_products3 , $variable_products_to_remove );

                if( !empty( $excluded_products ) ) {

                    if( isset( $args[ 'post__not_in' ] ) && !empty( $args[ 'post__not_in' ] ) )
                        $args[ 'post__not_in' ] = array_merge( $args[ 'post__not_in' ] , $excluded_products );
                    else
                        $args[ 'post__not_in' ] = $excluded_products;

                    foreach ( $excluded_products as $product_id ) {
                        if( !empty( $args[ 'post__in' ] ) && in_array( $product_id , $args[ 'post__in' ] ) ) {
                            $key = array_search( $product_id, $args[ 'post__in' ] );
                            unset( $args[ 'post__in' ][ $key ] );
                        }

                    }
                }

                // If Category search is triggered and post__in is empty then we add up all products into post__not_in
                if( ( !empty( $search ) || !empty( $cat_term_slugs ) ) && empty( $args[ 'post__in' ] ) ) {

                    $products = array();
                    foreach ( WWOF_Product_Listing_Helper::get_all_products( 'ID' ) as $product )
                        $products[] = $product->ID;

                    $products = array_map( 'intval' , $products );
                    $args[ 'post__not_in' ] = array_unique( array_merge( ( isset( $args[ 'post__not_in' ] ) && ! empty( $args[ 'post__not_in' ] ) ? $args[ 'post__not_in' ] : array() ) , $products ) );

                }

                $args = apply_filters( 'wwof_filter_product_listing_query_arg' , $args );

                $product_loop = new WP_Query( $args );
                $product_loop = apply_filters( 'wwof_filter_product_listing_query' , $product_loop );
                do_action( 'wwof_action_before_product_listing' );

                if ( get_option( 'wwof_general_use_alternate_view_of_wholesale_page' ) == 'yes' )
                    $tpl = 'wwof-product-listing-alternate.php';
                else
                    $tpl = 'wwof-product-listing.php';

                // set the template to lazyload when page is greater than 1 and pagination is disabled.
                if ( $paged > 1 && get_option( 'wwof_general_disable_pagination' ) == 'yes' )
                    $tpl = str_replace( '.php' , '-lazyload.php' , $tpl );

                // Load product listing template
                WWOF_Product_Listing_Helper::_load_template(
                    $tpl,
                    array(
                        'product_loop'      => $product_loop,
                        'paged'             => $paged,
                        'search'            => $search,
                        'cat_filter'        => $cat_filter,
                        'product_listing'   => $this->_wwof_product_listings,
                        'wholesale_prices'  => $this->_wwof_wwp_wholesale_prices,
                    ),
                    WWOF_PLUGIN_DIR.'templates/'
                );

                wp_reset_postdata();

            } else {

                // User don't have permission
                $title      = trim( stripslashes( strip_tags( get_option( 'wwof_permissions_noaccess_title' ) ) ) );
                $message    = trim( stripslashes( get_option( 'wwof_permissions_noaccess_message' ) ) );
                $login_url  = trim( get_option( 'wwof_permissions_noaccess_login_url' ) );

                if ( empty( $title ) )
                    $title = __( 'Access Denied' , 'woocommerce-wholesale-order-form' );

                if ( empty( $message ) )
                    $message = __( "You do not have permission to view wholesale product listing" , 'woocommerce-wholesale-order-form' );

                if ( empty( $login_url ) )
                    $login_url = wp_login_url(); ?>

                <div id="wwof_access_denied">
                    <h2 class="content-title"><?php echo $title; ?></h2>
                    <?php echo html_entity_decode( $message ); ?>
                    <p class="login-link-container"><a class="login-link" href="<?php echo $login_url; ?>"><?php _e( 'Login Here' , 'woocommerce-wholesale-order-form' ); ?></a></p>
                </div><?php

            }

            if( defined( 'DOING_AJAX' ) && DOING_AJAX ) {

                // To return the buffered output
                echo ob_get_clean();
                die();

            } else
                return ob_get_clean();

        }

        /**
         * Get single product details.
         *
         * @param null $product_id
         *
         * @return string
         * @since 1.0.0
         * @since 1.6.6 Refactor codebase and move to its proper model
         */
        public function wwof_get_product_details( $product_id = null ) {

            if( defined( 'DOING_AJAX' ) && DOING_AJAX )
                $product_id = $_GET[ 'product_id' ];

            $product = wc_get_product( $product_id );

            if ( $product === false ) {

                $no_product_details_msg = apply_filters( 'wwof_filter_no_product_details_message' , '<em class="no-product-details">' . __( 'No Product Details Available' , 'woocommerce-wholesale-order-form' ) . '</em>' );

                if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {

                    echo $no_product_details_msg;
                    die();

                } else
                    return $no_product_details_msg;

            }

            ob_start();

            WWOF_Product_Listing_Helper::_load_template(
                        'wwof-product-details.php',
                        array(
                            'product'           =>  $product,
                            'wholesale_prices'  =>  WWOF_WWP_Wholesale_Prices::instance(),
                            'product_listing'   =>  WWOF_Product_Listing::instance()
                        ),
                        WWOF_PLUGIN_DIR . 'templates/'
                    );

            if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {

                echo ob_get_clean();
                die();

            } else
                return ob_get_clean();

        }

        /**
         * Add product to cart.
         *
         * @param null $product_type
         * @param null $product_id
         * @param null $variation_id
         * @param null $quantity
         *
         * @return bool
         * @since 1.0.0
         * @since 1.6.6 Refactor codebase and move to its proper model
         * @since 1.7.0 Added code to handle variation products
         */
        public function wwof_add_product_to_cart( $product_type = null , $product_id = null , $variation_id = null , $quantity = null , $addon = null ) {

            if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {

                $product_type   = $_POST[ 'product_type' ];
                $product_id     = $_POST[ 'product_id' ];
                $variation_id   = $_POST[ 'variation_id' ];
                $quantity       = $_POST[ 'quantity' ];
                $addon          = $_POST;

            }

            if ( $product_type == 'variation' && $product_id ) {

                $variation_product = wc_get_product( $product_id );

                // re-assign values as if its a variable product
                $product_type   = 'variable';
                $variation_id   = $product_id;
                $product_id     = WWOF_Functions::wwof_get_product_variation_parent( $variation_product , true );

            }

            if ( !$variation_id && strcasecmp( $product_type , 'variable' ) == 0 )
                $response = array( 'status' => 'failed' , 'error_message' =>  __( 'Trying to add a variable product with no variation provided' , 'woocommerce-wholesale-order-form' ) );
            elseif ( $variation_id ) {

                $variation         = wc_get_product( $variation_id );
                $passed_validation = apply_filters( 'woocommerce_add_to_cart_validation' , true , $product_id , $quantity , $variation_id , $variation->get_variation_attributes() );
                $variation_title   = $this->_wwof_product_listings->wwof_get_variation_product_title( $variation );

                if ( $passed_validation ) {

                    if ( $variation->managing_stock() && $quantity > $variation->get_stock_quantity() && !$variation->backorders_allowed() ) {

                        $response = array(
                                        'status'        => 'failed',
                                        'error_message' => sprintf( __( 'Couldn\'t add <b>%1$s</b> to cart, selected quantity exceeds available stock amount <b>(%2$s)</b>.' , 'woocommerce-wholesale-order-form' ) , $variation_title , $variation->get_stock_quantity() )
                                    );

                    } else {

                        $cart_key = WC()->cart->add_to_cart( $product_id , $quantity , $variation_id , $variation->get_variation_attributes() );

                        if ( !$cart_key ) {

                            $response = array( 'status' => 'failed', 'error_message' => sprintf( __( 'Failed to add <b>%1$s</b> to cart.' , 'woocommerce-wholesale-order-form' ) , $variation_title ) );
                            wc_clear_notices();

                        } else {

                            do_action( 'woocommerce_ajax_added_to_cart' , $product_id );

                            // Set cart woocommerce and cart cookies. Bug Fix : WWOF-16
                            WWOF_Product_Listing_Helper::wwof_maybe_set_cart_cookies();

                            // Get mini cart
                            ob_start();
                            woocommerce_mini_cart();
                            $mini_cart = ob_get_clean();

                            $response = array(
                                            'status'                => 'success',
                                            'cart_subtotal_markup'  => $this->_wwof_product_listings->wwof_get_cart_subtotal(),
                                            'cart_key'              => $cart_key,
                                            'fragments'             => apply_filters( 'woocommerce_add_to_cart_fragments' , array( 'div.widget_shopping_cart_content' => '<div class="widget_shopping_cart_content">' . $mini_cart . '</div>' ) ),
                                            'cart_hash'             => apply_filters( 'woocommerce_add_to_cart_hash' , WC()->cart->get_cart() ? md5( json_encode( WC()->cart->get_cart() ) ) : '' , WC()->cart->get_cart() )
                                        );

                        }

                    }

                } else {

                    // get wc errors if there are any
                    if ( $wc_errors = wc_get_notices( 'error' ) )
                        wc_clear_notices();

                    $response = array(
                        'status'        => 'failed',
                        'error_message' => sprintf( __( 'Failed to add <b>%1$s</b> to cart.' , 'woocommerce-wholesale-order-form' ) , $variation_title ),
                        'wc_errors'     => $wc_errors
                    );
                }


            } elseif ( $product_id ) {

                $product           = wc_get_product( $product_id );
                $passed_validation = apply_filters( 'woocommerce_add_to_cart_validation' , true , $product_id , $quantity );

                if ( $passed_validation ) {

                    if ( $product->managing_stock() && $quantity > $product->get_stock_quantity() && !$product->backorders_allowed() ) {

                        $response = array(
                                        'status'        => 'failed',
                                        'error_message' => sprintf( __( 'Couldn\'t add <b>%1$s</b> to cart, selected quantity exceeds available stock amount <b>(%2$s)</b>.' , 'woocommerce-wholesale-order-form' ) , $product->get_title() , $product->get_stock_quantity() )
                                    );

                    } else {

                        $cart_key = WC()->cart->add_to_cart( $product_id , $quantity );

                        if ( !$cart_key ) {

                            $response = array( 'status' => 'failed' , 'error_message' => sprintf( __( 'Failed to add <b>%1$s</b> to cart.' , 'woocommerce-wholesale-order-form' ) , $product->get_title() ) );
                            wc_clear_notices();

                        } else {

                            do_action( 'woocommerce_ajax_added_to_cart' , $product_id );

                            // Set cart woocommerce and cart cookies. Bug Fix : WWOF-16
                            WWOF_Product_Listing_Helper::wwof_maybe_set_cart_cookies();

                            // Get mini cart
                            ob_start();
                            woocommerce_mini_cart();
                            $mini_cart = ob_get_clean();

                            $response = array(
                                            'status'                => 'success',
                                            'cart_subtotal_markup'  => $this->_wwof_product_listings->wwof_get_cart_subtotal(),
                                            'cart_key'              => $cart_key,
                                            'fragments'             => apply_filters( 'woocommerce_add_to_cart_fragments' , array( 'div.widget_shopping_cart_content' => '<div class="widget_shopping_cart_content">' . $mini_cart . '</div>' ) ),
                                            'cart_hash'             => apply_filters( 'woocommerce_add_to_cart_hash' , WC()->cart->get_cart() ? md5( json_encode( WC()->cart->get_cart() ) ) : '' , WC()->cart->get_cart() )
                                        );

                        }

                    }

                } else {

                    // get wc errors if there are any
                    if ( $wc_errors = wc_get_notices( 'error' ) )
                        wc_clear_notices();

                    $response = array(
                        'status' => 'failed',
                        'error_message' => sprintf( __( 'Failed to add <b>%1$s</b> to cart' , 'woocommerce-wholesale-order-form' ) , $product->get_title() ),
                        'wc_errors'     => $wc_errors
                     );

                }

            } else
                $response = array( 'status' => 'failed' , 'error_message' => __( 'Failed to add product to cart' , 'woocommerce-wholesale-order-form' ) );

            if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {

                header( 'Content-Type: application/json' ); // specify we return json
                echo json_encode( $response );
                die();

            } else
                return $response;

        }


        /**
         * Add products to cart.
         *
         * @param null $products
         * @return bool
         *
         * @since 1.1.0
         * @since 1.6.6 Refactor codebase and move to its proper model
         * @since 1.7.0 added validation for quantity arguments.
         *              modified code to support all product addon field types.
         */
        public function wwof_add_products_to_cart ( $products = null ) {

            if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {

                $products = $_POST[ 'products' ];
                $files    = $_FILES;
            }

            $cart_keys          = array();
            $successfully_added = array();
            $failed_to_add      = array();
            $total_added        = 0;
            $total_failed       = 0;

            foreach ( $products as $product ) {

                if ( $product[ 'productType' ] == 'variation' && $product[ 'productID' ] ) {

                    $variation_product = wc_get_product( $product[ 'productID' ] );

                    // re-assign values as if its a variable product
                    $product[ 'productType' ] = 'variable';
                    $product[ 'variationID' ] = $product[ 'productID' ];
                    $product[ 'productID' ]   = WWOF_Functions::wwof_get_product_variation_parent( $variation_product , true );

                }

                /**
                 * In order to support product addons plugin, we need to overwrite the $_POST
                 * global variable with the current $product data being processed.
                 */
                $_POST = $product;

                /**
                 * In order to support product addon file upload fields, we need to prepare the file data
                 * and overwrite the $_FILES global variable for the current $product being processed.
                 */
                if ( ! empty( $files ) ) {

                    $product_file_addons = array();

                    foreach( $files as $file_name_prop => $file_data ) {

                        $prod_id = ( $product[ 'variationID' ] > 0 ) ? $product[ 'variationID' ] : $product[ 'productID' ];

                        if ( strpos( $file_name_prop , (string) $prod_id ) !== false ) {

                            $file_name_prop = str_replace( array( $product[ 'productID' ] . '_' , $product[ 'variationID' ] . '_' ) , '' , $file_name_prop );
                            $product_file_addons[ $file_name_prop ] = $file_data;
                        }

                    }

                    $_FILES = $product_file_addons;
                }

                if ( !$product[ 'variationID' ] && strcasecmp( $product[ 'productType' ] , 'variable' ) == 0 ) {

                    $failed_to_add[] = array(
                                            'product_id'    =>  $product[ 'productID' ],
                                            'error_message' =>  __( 'Contains invalid variation id. Either empty, not numeric or less than zero' , 'woocommerce-wholesale-order-form' ),
                                            'quantity'      =>  $product[ 'quantity' ]
                                        );
                    $total_failed += $product[ 'quantity' ];
                    continue;

                } elseif ( $product[ 'variationID' ] ) {

                    $variation       = wc_get_product( $product[ 'variationID' ] );
                    $variation_title = $this->_wwof_product_listings->wwof_get_variation_product_title( $variation );

                    if ( isset( $product[ 'addon' ] ) ) {

                        if ( isset( $product[ 'addon' ][ 'errors' ] ) && is_array( $product[ 'addon' ][ 'errors' ] ) && count( $product[ 'addon' ][ 'errors' ] ) > 0 ) {

                            $required_addons = '';
                            foreach ( $product[ 'addon' ][ 'errors' ] as $addon_error )
                                $required_addons .= '<b>' . $addon_error . '</b><br>';

                            $failed_to_add[] = array(
                                'product_id'    => $product[ 'productID' ],
                                'variation_id'  => $product[ 'variationID' ],
                                'error_message' => sprintf( __( 'Couldn\'t add <b>%1$s</b> to cart, required product add-ons not filled.<br>%2$s' , 'woocommerce-wholesale-order-form' ) , $variation_title , $required_addons ),
                                'quantity'      => $product[ 'quantity' ]
                            );
                            $total_failed += $product[ 'quantity' ];
                            continue;

                        } elseif ( isset( $product[ 'addon' ][ 'addon' ] ) && is_array( $product[ 'addon' ][ 'addon' ] ) ) {

                            foreach ( $product[ 'addon' ][ 'addon' ] as $key => $val )
                                $_POST[ $key ] = $val;

                        }

                    }

                    $passed_validation = apply_filters( 'woocommerce_add_to_cart_validation' , true , $product[ 'productID' ] , $product[ 'quantity' ] , $product[ 'variationID' ] , $variation->get_variation_attributes() );
                    $validate_quantity = WWOF_Product_Listing_Helper::wwof_validate_product_selected_quantity( $product[ 'quantity' ] , $product[ 'productID' ] , $product[ 'variationID' ] );

                    if ( $passed_validation && $validate_quantity ) {

                        if ( $variation->managing_stock() && $product[ 'quantity' ] > $variation->get_stock_quantity() && !$variation->backorders_allowed() ) {

                            $failed_to_add[] = array(
                                'product_id'    => $product[ 'productID' ],
                                'variation_id'  => $product[ 'variationID' ],
                                'error_message' => sprintf( __( 'Couldn\'t add <b>%1$s</b> to cart, selected quantity exceeds available stock amount <b>(%2$s)</b>.' , 'woocommerce-wholesale-order-form' ) , $variation_title , $variation->get_stock_quantity() ),
                                'quantity'      => $product[ 'quantity' ]
                            );
                            $total_failed += $product[ 'quantity' ];
                            continue;

                        } else {

                            $cart_key = WC()->cart->add_to_cart( $product[ 'productID' ] , $product[ 'quantity' ] , $product[ 'variationID' ] , $variation->get_variation_attributes() );

                            if ( !$cart_key ) {

                                wc_clear_notices();

                                $failed_to_add[] = array(
                                    'product_id'    => $product[ 'productID' ],
                                    'variation_id'  => $product[ 'variationID' ],
                                    'error_message' => sprintf( __( 'Failed to add <b>%1$s</b> to cart.' , 'woocommerce-wholesale-order-form' ) , $variation_title ),
                                    'quantity'      => $product[ 'quantity' ]
                                );
                                $total_failed += $product[ 'quantity' ];
                                continue;

                            } else {

                                $cart_keys[] = $cart_key;
                                $successfully_added[ $product[ 'variationID' ] ] = $product[ 'quantity' ];
                                $total_added += $product[ 'quantity' ];
                                do_action( 'woocommerce_ajax_added_to_cart' , $product[ 'productID' ] );

                            }

                        }

                    } else if ( ! $validate_quantity ) {

                        $failed_to_add[] = array(
                            'product_id'    => $product[ 'productID' ],
                            'variation_id'  => $product[ 'variationID' ],
                            'error_message' => sprintf( __( 'Failed to add <b>%1$s</b> to cart. The entered quantity is invalid.' , 'woocommerce-wholesale-order-form' ) , $variation_title ),
                            'quantity'      => $product[ 'quantity' ]
                        );
                        $total_failed += $product[ 'quantity' ];
                        continue;

                    } else {

                        $failed_to_add[] = array(
                            'product_id'    => $product[ 'productID' ],
                            'variation_id'  => $product[ 'variationID' ],
                            'error_message' => sprintf( __( 'Failed to add <b>%1$s</b> to cart.' , 'woocommerce-wholesale-order-form' ) , $variation_title ),
                            'quantity'      => $product[ 'quantity' ]
                        );
                        $total_failed += $product[ 'quantity' ];
                        continue;

                    }

                } elseif ( $product[ 'productID' ] ) {

                    $product_obj = wc_get_product( $product[ 'productID' ] );

                    if ( isset( $product[ 'addon' ] ) ) {

                        if ( isset( $product[ 'addon' ][ 'errors' ] ) && is_array( $product[ 'addon' ][ 'errors' ] ) && count( $product[ 'addon' ][ 'errors' ] ) > 0 ) {

                            $required_addons = '';
                            foreach ( $product[ 'addon' ][ 'errors' ] as $addon_error )
                                $required_addons .= '<b>' . $addon_error . '</b><br>';

                            $failed_to_add[] = array(
                                'product_id'    => $product[ 'productID' ],
                                'error_message' => sprintf( __( 'Couldn\'t add <b>%1$s</b> to cart, required product add-ons not filled.<br>%2$s' , 'woocommerce-wholesale-order-form' ) , $product_obj->get_title() , $required_addons ),
                                'quantity'      => $product[ 'quantity' ]
                            );
                            $total_failed += $product[ 'quantity' ];
                            continue;


                        } elseif ( isset( $product[ 'addon' ][ 'addon' ] ) && is_array( $product[ 'addon' ][ 'addon' ] ) ) {

                            foreach ( $product[ 'addon' ][ 'addon' ] as $key => $val )
                                $_POST[ $key ] = $val;

                        }

                    }

                    $passed_validation = apply_filters( 'woocommerce_add_to_cart_validation' , true , $product[ 'productID' ] , $product[ 'quantity' ] );
                    $validate_quantity = WWOF_Product_Listing_Helper::wwof_validate_product_selected_quantity( $product[ 'quantity' ] , $product[ 'productID' ] );

                    if ( $passed_validation && $validate_quantity ) {

                        if ( $product_obj->managing_stock() && $product[ 'quantity' ] > $product_obj->get_stock_quantity() && !$product_obj->backorders_allowed() ) {

                            $failed_to_add[] = array(
                                'product_id'    => $product[ 'productID' ],
                                'error_message' => sprintf( __( 'Couldn\'t add <b>%1$s</b> to cart, selected quantity exceeds available stock amount <b>(%2$s)</b>.' , 'woocommerce-wholesale-order-form' ) , $product_obj->get_title() , $product_obj->get_stock_quantity() ),
                                'quantity'      => $product[ 'quantity' ]
                            );
                            $total_failed += $product[ 'quantity' ];
                            continue;

                        } else {

                            $cart_key = WC()->cart->add_to_cart( $product[ 'productID' ] , $product[ 'quantity' ] );

                            if ( !$cart_key ) {

                                wc_clear_notices();

                                $failed_to_add[] = array(
                                    'product_id'    => $product[ 'productID' ],
                                    'error_message' => sprintf( __( 'Failed to add <b>%1$s</b> to cart.' , 'woocommerce-wholesale-order-form' ) , $product_obj->get_title() ),
                                    'quantity'      => $product[ 'quantity' ]
                                );
                                $total_failed += $product[ 'quantity' ];
                                continue;

                            } else {

                                $cart_keys[] = $cart_key;
                                $successfully_added[ $product[ 'productID' ] ] = $product[ 'quantity' ];
                                $total_added += $product[ 'quantity' ];
                                do_action( 'woocommerce_ajax_added_to_cart', $product[ 'productID' ] );

                            }

                        }

                    } else if ( ! $validate_quantity ) {

                        $failed_to_add[] = array(
                            'product_id'    => $product[ 'productID' ],
                            'variation_id'  => $product[ 'variationID' ],
                            'error_message' => sprintf( __( 'Failed to add <b>%1$s</b> to cart. The entered quantity is invalid.' , 'woocommerce-wholesale-order-form' ) , $variation_title ),
                            'quantity'      => $product[ 'quantity' ]
                        );
                        $total_failed += $product[ 'quantity' ];
                        continue;

                    } else {

                        $failed_to_add[] = array(
                            'product_id'    =>  $product[ 'productID' ],
                            'error_message' =>  sprintf( __( 'Failed to add <b>%1$s</b> to cart.' , 'woocommerce-wholesale-order-form' ) , $product_obj->get_title() ),
                            'quantity'      =>  $product[ 'quantity' ]
                        );
                        $total_failed += $product[ 'quantity' ];
                        continue;

                    }

                } else {

                    $failed_to_add[] = array(
                        'product_id'    => $product[ 'productID' ],
                        'error_message' => __( 'Failed to add product to cart' , 'woocommerce-wholesale-order-form' ),
                        'quantity'      => $product[ 'quantity' ]
                    );
                    $total_failed += $product[ 'quantity' ];
                    continue;

                }

            }

            // Set cart woocommerce and cart cookies. Bug Fix : WWOF-16
            WWOF_Product_Listing_Helper::wwof_maybe_set_cart_cookies();

            if( defined( 'DOING_AJAX' ) && DOING_AJAX ){

                // Get mini cart
                ob_start();
                woocommerce_mini_cart();
                $mini_cart = ob_get_clean();

                // get wc errors if there are any
                if ( $wc_errors = wc_get_notices( 'error' ) )
                    wc_clear_notices();

                header( 'Content-Type: application/json' ); // specify we return json
                echo json_encode( array(
                    'status'                => 'success',
                    'cart_subtotal_markup'  => $this->_wwof_product_listings->wwof_get_cart_subtotal(),
                    'cart_keys'             => $cart_keys,
                    'successfully_added'    => $successfully_added,
                    'total_added'           => $total_added,
                    'failed_to_add'         => $failed_to_add,
                    'total_failed'          => $total_failed,
                    'fragments'             => apply_filters( 'woocommerce_add_to_cart_fragments' , array( 'div.widget_shopping_cart_content' => '<div class="widget_shopping_cart_content">' . $mini_cart . '</div>' ) ),
                    'cart_hash'             => apply_filters( 'woocommerce_add_to_cart_hash', WC()->cart->get_cart() ? md5( json_encode( WC()->cart->get_cart() ) ) : '', WC()->cart->get_cart() ),
                    'wc_errors'             => $wc_errors
                ) );
                die();

            } else
                return true;

        }

        /**
         * Get product variation quantity input arguments. Fetch data for 'min', 'max', and 'step' properties.
         *
         * @since 1.7.0
         * @access public
         *
         * @param int $variation_id ID of product variation.
         * @return array quantity input arguments.
         */
        public function wwof_get_variation_quantity_input_args( $variation_id = null ) {

            if ( defined( 'DOING_AJAX' ) && DOING_AJAX )
                $variation_id = $_POST[ 'variation_id' ];

            $product    = wc_get_product( $variation_id );
            $input_args = WWOF_Product_Listing_Helper::get_product_quantity_input_args( $product );

            if ( defined( 'DOING_AJAX' ) && DOING_AJAX )
                wp_send_json( $input_args );
            else
                return $input_args;
        }

        /**
         * Execute model.
         *
         * @since 1.6.6
         * @access public
         */
        public function run() {

            // Admin only AJAX Interfaces
            add_action( 'wp_ajax_wwof_create_wholesale_page' , array( $this , 'wwof_create_wholesale_page' ) );
            add_action( 'wp_ajax_wwof_save_license_details' , array( $this->_wwof_wws_license_settings , 'wwof_save_license_details' ) );

            // General AJAX Interfaces
            add_action( 'wp_ajax_wwof_display_product_listing' , array( $this , 'wwof_display_product_listing' ) );
            add_action( 'wp_ajax_wwof_get_product_details' , array( $this , 'wwof_get_product_details' ) );
            add_action( 'wp_ajax_wwof_add_product_to_cart' , array( $this , 'wwof_add_product_to_cart' ) );
            add_action( 'wp_ajax_wwof_add_products_to_cart' , array( $this , 'wwof_add_products_to_cart' ) );
            add_action( 'wp_ajax_wwof_get_variation_quantity_input_args' , array( $this , 'wwof_get_variation_quantity_input_args' ) );
            add_action( 'wp_ajax_nopriv_wwof_display_product_listing' , array( $this , 'wwof_display_product_listing' ) );
            add_action( 'wp_ajax_nopriv_wwof_get_product_details' , array( $this , 'wwof_get_product_details' ) );
            add_action( 'wp_ajax_nopriv_wwof_add_product_to_cart' , array( $this , 'wwof_add_product_to_cart' ) );
            add_action( 'wp_ajax_nopriv_wwof_add_products_to_cart' , array( $this , 'wwof_add_products_to_cart' ) );
            add_action( 'wp_ajax_nopriv_wwof_get_variation_quantity_input_args' , array( $this , 'wwof_get_variation_quantity_input_args' ) );

        }
    }
}

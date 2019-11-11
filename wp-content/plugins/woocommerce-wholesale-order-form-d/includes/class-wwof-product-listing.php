<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class WWOF_Product_Listing {

    /*
    |--------------------------------------------------------------------------
    | Class Properties
    |--------------------------------------------------------------------------
    */

    /**
     * Property that holds the single main instance of WWOF_Shortcode.
     *
     * @since 1.6.6
     * @access private
     * @var WWOF_Shortcode
     */
    private static $_instance;

    /**
     * Model that houses the logic of retrieving information relating to WWOF Permissions.
     *
     * @since 1.6.6
     * @access private
     * @var WWOF_Permissions
     */
    private $_wwof_permissions;

    /*
    |--------------------------------------------------------------------------
    | Class Methods
    |--------------------------------------------------------------------------
    */

    /**
     * WWOF_Shortcode constructor.
     *
     * @param array $dependencies Array of instance objects of all dependencies of WWOF_Shortcode model.
     *
     * @access public
     * @since 1.6.6
     */
    public function __construct( $dependencies ) {

        $this->_wwof_permissions = $dependencies[ 'WWOF_Permissions' ];

    }

    /**
     * Ensure that only one instance of WWOF_Shortcode is loaded or can be loaded (Singleton Pattern).
     *
     * @param array $dependencies Array of instance objects of all dependencies of WWOF_Shortcode model.
     *
     * @return WWOF_Shortcode
     * @since 1.6.6
     */
    public static function instance( $dependencies = null  ) {

        if ( !self::$_instance instanceof self )
            self::$_instance = new self( $dependencies );

        return self::$_instance;

    }

    /**
     * Validate and tidy up the category filter set on the WWOF settings area.
     * Mainly check if a category in the filter still exist, if not, remove that category in the filter and
     * update the filter accordingly.
     *
     * @since 1.3.1
     * @since 1.6.6 Made access modifier from private to public. Underscore cased the function name and variables.
     *
     * @param $cat_filter
     * @return mixed
     */
    public function wwof_category_filter_validator( $cat_filter , $update_meta = true ) {

        if ( is_array( $cat_filter ) ) {

            $arr_index_to_remove = array();

            foreach ( $cat_filter as $idx => $slug ) {

                if ( !get_term_by( 'slug' , $slug , 'product_cat' ) )
                    $arr_index_to_remove[] = $idx;

            }

            foreach ( $arr_index_to_remove as $index )
                unset( $cat_filter[ $index ] );

            if ( !empty( $arr_index_to_remove ) && $update_meta )
                update_option( 'wwof_filters_product_category_filter' , $cat_filter );

        }

        return $cat_filter;

    }

    /**
     * Get variation product title.
     *
     * @since 1.5.0
     * @since 1.6.6 Underscore cased the function name and variables.
     * @access public
     *
     * @param $variation
     * @return string;
     */
    public function wwof_get_variation_product_title( $variation ) {

        $variation_title     = '';
        $variable_attributes = '';

        foreach ( $variation->get_variation_attributes() as $attribute => $value ) {

            $attribute = str_replace( 'attribute_' , '' , $attribute );

            if ( strpos( $attribute , 'pa_' ) !== false) {

                // Attribute based variable product attribute
                $attribute = str_replace( 'pa_' , '' , $attribute );

                $value = str_replace( '-' , ' ' , $value );
                $value = ucwords( $value );

            }

            $attribute = str_replace( '-' , ' ' , $attribute );
            $attribute = ucwords( $attribute );

            if ( !empty( $variable_attributes ) )
                $variable_attributes .= ', ';

            $variable_attributes .= $attribute . ": " . $value;

        }

        if ( !empty( $variable_attributes ) )
            return $variation->get_title() . ' (' . $variable_attributes . ')';
        else
            return $variation->get_title();

    }

    /**
     * Build product category options markup. For use inside a select tag. Recursive function.
     *
     * @since 1.3.0
     * @since 1.6.6 Underscore cased the function name and variables.
     * @since 1.7.0 Added option to check if the function is for saving as html <option> list or as an array variable
     *
     * @param     $cats
     * @param     $cats_list
     * @param int $indent
     * @param     $markup check if to be printed as html if true. will be listed as an array if false.
     */
    public function wwof_build_product_category_options_markup( $cats , &$cats_list , $indent = 0 , $markup = true ) {

        $indent_str  = '';
        $indent_ctr  = $indent;
        $default_cat = get_option( 'wwof_general_default_product_category_search_filter' , 'none' );

        while ( $indent_ctr > 0 ) {

            $indent_str .= "&ndash; ";
            $indent_ctr--;

        }

        foreach ( $cats as $cat ) {

            // exclude empty product categories
            if ( $cat->count <= 0 )
                continue;

            if ( $markup && ! is_array( $cats_list ) )
                $cats_list .= '<option value="' . $cat->slug . '" '. selected( $default_cat , $cat->slug , false ) .'>' . $indent_str . $cat->name . '</option>';
            else
                $cats_list[ $cat->slug ] =  $indent_str . $cat->name;

            if ( !empty( $cat->children ) )
                $this->wwof_build_product_category_options_markup( $cat->children , $cats_list , ( $indent + 1 ) , $markup );

        }

    }

    /**
     * Get product listing filter section.
     *
     * @since 1.0.0
     * @since 1.3.0 Add hierarchy to the list of categories inside the categories filter select markup.
     * @since 1.3.2 Bug Fix. WWOF-70.
     * @since 1.6.6 Underscore cased the function name and variables.
     *
     * @param $search_placeholder_text
     */
    public function wwof_get_product_listing_filter( $search_placeholder_text , $atts ) {

        // get $product_terms_hierarchy and $product_terms data
        $terms_hierarchy = $this->wwof_get_product_terms_hierarchy( $atts[ 'categories' ] , false );
        extract( $terms_hierarchy );

        // Build product cats options markup
        $product_terms_option_markup = '';
        $this->wwof_build_product_category_options_markup( $product_terms_hierarchy , $product_terms_option_markup );

        WWOF_Product_Listing_Helper::_load_template(
            'wwof-product-listing-filter.php',
            array(
                'search_placeholder_text'   => apply_filters( 'wwof_filter_search_placeholder_text' , $search_placeholder_text ),
                'product_category_options'  => $product_terms_option_markup,
                'product_terms'             => $product_terms, // Backwards compatibility with versions prior to 1.3.0,
                'wwof_permissions'          => $this->_wwof_permissions
            ),
            WWOF_PLUGIN_DIR . 'templates/'
        );

    }

    /**
	 * Get product terms hierarchy
	 *
	 * @since 1.7.0
     *
	 * @param string $categories_string    comma separated category ids
     * @return array $product_terms_hierarchy, $product_terms
	 */
	public function wwof_get_product_terms_hierarchy( $categories_string = 0 , $hierarchy_only = true ) {

        $include = array();

        // Process categry list from the shortcode attributes
        $atts_cats = explode ( "," , $categories_string );
        foreach ( $atts_cats as $index => $cat_id )
            $atts_cats[ $index ] = (int) filter_var( trim( $cat_id ) , FILTER_SANITIZE_STRING );

        // When cat attribute has value of zero, menaing show all
        if ( !in_array( 0 , $atts_cats ) ) {

            // Get the list of categories as defined on the shortcode attrtibutes
            // Has higher precedence compared to the one set on the plugin settings
            $include = $atts_cats;

        } else {

            // Get the list of product categories that is defined on the plugin settings
            // WooCommerce->Settings->Wholesale Ordering->Filters
            $cat_filter = get_option( 'wwof_filters_product_category_filter' );

            if ( is_array( $cat_filter ) && !empty( $cat_filter ) ) {

                foreach ( $cat_filter as $catSlug ) {

                    $curr_term = get_term_by( 'slug' , $catSlug , 'product_cat' );

                    if ( $curr_term )
                        $include[] = (int) $curr_term->term_id;

                }

            }

        }

        $term_args = array(
            'taxonomy' => 'product_cat',
            'hide_empty' => false
        );
        if ( !empty( $include ) )
            $term_args[ 'include' ] = $include;

        // Get all product cats (Object)
        $product_terms_object = get_terms( $term_args );

        // Set product cats in hierarchy
        $product_terms_hierarchy = array();
        WWOF_Product_Listing_Helper::wwof_sort_terms_hierarchicaly( $product_terms_object , $product_terms_hierarchy );

        /*
         * It will not be empty if there are child categories that has no parent category
         * Usually occurs if user only selected few categories on the wwof settings.
         * If this happends, those child categories will not be included on $product_terms_hierarchy
         * we need to merge it there.
         * */
        if ( !empty( $product_terms_object ) ) {

            $product_terms_hierarchy = array_merge( $product_terms_hierarchy , $product_terms_object );
            $product_terms_object = array();

        }

        // Sort the product terms hierarchy
        usort( $product_terms_hierarchy , array( new WWOF_Product_Listing_Helper , 'wwof_product_terms_hierarchy_usort_callback' ) );

        /**
         * check if function will only need to return either the $product_terms_hierarchy
         * only or both $product_terms_hierarchy and $product_terms, which is loaded for backwards compatibility
         */
        if ( $hierarchy_only ) {

            return $product_terms_hierarchy;

        } else {

            // Backwards Compatibility with versions prior to 1.3.0
            $product_terms = array();

            foreach( $product_terms_object as $term ) {
                $product_terms[ $term->slug ] = $term->name;
            }

            return array(
                        'product_terms_hierarchy' => $product_terms_hierarchy,
                        'product_terms'           => $product_terms
                   );

        }

	}

    /**
     * Get product meta.
     * @param $product
     *
     * @return mixed
     * @since 1.0.0
     * @since 1.6.6 Underscore cased the function name and variables.
     */
    public function wwof_get_product_meta ( $product ) {

        $product_meta = '<span class="product_type" style="display: none !important;">' . WWOF_Functions::wwof_get_product_type( $product ) . '</span>';
        $product_meta .= '<span class="main_product_id" style="display: none !important;">' . WWOF_Functions::wwof_get_product_id( $product ) . '</span>';

        return apply_filters( 'wwof_filter_product_meta' , $product_meta );

    }

    /**
     * Get product title.
     *
     * @param $product
     * @param $permalink
     *
     * @return string
     * @since 1.0.0
     * @since 1.6.6 Underscore cased the function name and variables.
     * @since 1.7.7 Include the minimum number of variation combination text when applicable.
     */
    public function wwof_get_product_title( $product , $permalink ) {

        global $wc_wholesale_prices;

        $main_product_title = '<a class="product_link" href="' . $this->wwof_get_product_link( WWOF_Functions::wwof_get_product_id( $product ) , $permalink ) . '">' . $product->get_title() . '</a>';

        // get the variable level min quantity value only when WWP is active.
        if ( get_class( $wc_wholesale_prices ) == 'WooCommerceWholeSalePrices' && is_user_logged_in() ) {

            $wholesale_role = $wc_wholesale_prices->wwp_wholesale_roles->getUserWholesaleRole();

            if ( isset( $wholesale_role[0] ) )
                $variable_level_min_qty = get_post_meta( WWOF_Functions::wwof_get_product_id( $product ) , $wholesale_role[ 0 ] . '_variable_level_wholesale_minimum_order_quantity' , true );
        }

        // if the variable level min quantity is present, then display the requirement text.
        if ( isset( $variable_level_min_qty ) && $variable_level_min_qty ) {

            $variable_level_min_qty_html = sprintf( __( 'Min: %s of any variation combination' , 'woocommerce-wholesale-order-form' ) , $variable_level_min_qty );
			$main_product_title         .= '<br><span class="wholesale_price_minimum_order_quantity">' . $variable_level_min_qty_html . '</span>';
        }

        return apply_filters( 'wwof_filter_product_title' , $main_product_title , $product );
    }

    /**
     * Get product variation selected options.
     *
     * @since 1.7.0
     * @access public
     *
     * @param WC_Product_Variation $product Product variation object.
     * @return string Product variation options html.
     */
    public function wwof_get_product_variation_selected_options( $product ) {

        // skip if product is not a variation
        if ( $product->post_type !== 'product_variation' && ! is_a( $product , 'WC_Product_Variation' ) )
            return;

        $selected_options = WWOF_Functions::wwof_get_product_variation_attributes( $product );
        $parent_product   = WWOF_Functions::wwof_get_product_variation_parent( $product );

        // skip if there are no options to list
        if ( empty( $selected_options ) )
            return;

        $options = '<ul class="variation-options">';

            foreach ( $selected_options as $key => $value ) {
                $attribute_label = wc_attribute_label( str_replace( 'attribute_' , '' , $key ) , $parent_product );
                $slug = str_replace( 'attribute_', '', $key );
                $term = get_term_by( 'slug' , $value , $slug );
                $name = $term ? $term->name : $value;
                $options .= '<li><strong>' . $attribute_label . '</strong>: <span>' . $name . '</span></li>';
            }

        $options .= '</ul>';

        return $options;
    }

    /**
     * Get product variation field.
     *
     * @since 1.0.0
     * @since 1.3.0 Follow variation ordering set on the back end.
     * @since 1.3.2 We determine if a variation is active or not is by also checking the inventory status of the parent variable product.
     * @since 1.3.5 Follow the default attribute set or default variation set, and preselect it as well on the wholesale ordering form.
     * @since 1.6.3 WWOF-119 : If all variations are restricted to a specific wholesale role(s) we set a flag so we can add a message. The flag is used in function wwof_get_product_row_action_fields.
     * @since 1.6.6 Underscore cased the function name and variables.
     * @access public
     *
     * @param $product
     * @return string
     */
    public function wwof_get_product_variation_field ( $product ) {

        if ( WWOF_Functions::wwof_get_product_type( $product ) == 'variable' ) {

            $product_variations             = $product->get_available_variations();
            $product_attributes             = $product->get_attributes();
            $variation_arr                  = array();
            $count_not_visible_variations   = 0;

            if ( empty( $product_variations ) ) {
                $_REQUEST[ 'wwof_variable_disabled' ] = true;
                return;
            }

            $variation_select_box = '<label class="product_variations_label">' . __( 'Variations:' , 'woocommerce-wholesale-order-form' ) . '</label><br />';
            $variation_select_box .= '<select class="product_variations">';

            // To create our fancy pants combination variation select box we need to
            // iterate through the variation an attributes to create the proper front end naming
            foreach ( $product_variations as $variation ) {

                $variation_obj             = wc_get_product( $variation[ 'variation_id' ] );
                $variation_attributes      = $variation_obj->get_variation_attributes();
                $variation_attributes_copy = $variation_attributes;
                $friendly_variation_text   = '';

                // We now use an array_walk here to get all the friendly variation key and value names
                // rather than nested foreach loops to avoid excessive execution time on variation heavy
                // pages.
                // Then afterwards we will combine those into a string so that we can use that text
                // on the <option> itself.
                array_walk( $variation_attributes_copy, function( &$value, &$key, $product_attributes ) {

                    $attr_key = str_replace( 'attribute_', '', $key );
                    $attribute = $product_attributes[ $attr_key ];

                    if ( WWOF_Functions::wwof_is_woocommerce_version_3() ) {
                        if ( $attribute->is_taxonomy() ) {
                            $tax = $attribute->get_taxonomy_object();
                            $attribute_name = $tax->attribute_label;
                            $attribute_term = get_term_by( 'slug', $value, $attr_key );
                            $attribute_value = $attribute_term->name;
                        } else {
                            $attribute_name = $attribute->get_name();
                            $attribute_value = wc_attribute_label( $value );
                        }
                    } else { // WC 2.6 and below
        				if ( taxonomy_exists( wc_sanitize_taxonomy_name( $attr_key ) ) ) {
        					$term              = get_term_by( 'slug', $value, wc_sanitize_taxonomy_name( $attr_key ) );
        					$attribute_name    = wc_attribute_label( wc_sanitize_taxonomy_name( $attr_key ) );
        					$attribute_value   = isset( $term->name ) ? $term->name : $value;
        				} else {
        					$attribute_name    = wc_attribute_label( sanitize_title( $attr_key ) );
                            $attribute_value   = wc_attribute_label( $value );
        				}
                    }

                    // Basic support for if a value is null we specify it as "Any", though in reality the admin should set the attibute specifically.
                    if ( empty( $attribute_value ) )
                        $attribute_value = 'Any';

                    $value = $attribute_name . ': ' . $attribute_value;

                }, $product_attributes );

                // Combine the now friendly names in the array into the final string text
                $friendly_variation_text = implode( ', ', $variation_attributes_copy );

                // Need to check that the variation is purchasable and if so whether it is available (enough stock, can be backordered etc)
                if ( $this->wwof_variation_is_purchasable( $variation_obj, $product ) ) {

                    $variation_arr[] = array(
                        'value'         => $variation[ 'variation_id' ],
                        'text'          => $friendly_variation_text,
                        'disabled'      => false,
                        'visible'       => true,
                        'attributes'    => $variation_attributes,
                        'instock'       => $variation[ 'is_in_stock' ], // true = instock, false = out of stock,
                        'sku'           => $variation_obj->get_sku(),
                        'selected'      => false
                    );

                } else {

                    $visibility = false;
                    if ( $variation_obj->variation_is_visible() )
                        $visibility = true;

                    $variation_arr[] = array(
                        'value'         => 0,
                        'text'          => $friendly_variation_text,
                        'disabled'      => true,
                        'visible'       => $visibility,
                        'attributes'    => $variation_attributes,
                        'instock'       => $variation[ 'is_in_stock' ], // true = instock, false = out of stock
                        'sku'           => $variation_obj->get_sku(),
                        'selected'      => false
                    );

                }

            }

            wp_reset_postdata();

            usort( $variation_arr , array( new WWOF_Product_Listing_Helper , 'wwof_usort_variation_menu_order' ) ); // Sort variations via menu order

            // Get default attributes
            $default_attributes = WWOF_Functions::wwof_get_default_attributes( $product );

            // set the selected variation (makes sure only one variation is selected).
            $this->wwof_product_variation_field_set_selected( $variation_arr , $default_attributes );

            $selected_variation = false;
            foreach ( $variation_arr as $variation ) {

                if ( !$variation[ 'visible' ] )
                    $count_not_visible_variations += 1;

                $selected              = $variation[ 'selected' ] ? 'selected="selected"' : '';
                $variation_select_box .= '<option value="' . $variation[ 'value' ] . '" ' . ( $variation[ 'disabled' ] ? 'disabled' : '' ) . ' ' . $selected . '>' . $variation[ 'text' ] . '</option>';

            }

            $variation_select_box .= '</select>';

            // Check if all variations are not visible then we set a flag that this variable product can't be added to cart
            if( count( $product_variations ) === $count_not_visible_variations )
                $_REQUEST[ 'wwof_variable_disabled' ] = true;

            $variation_select_box = apply_filters( 'wwof_filter_product_variation' , $variation_select_box );

            return $variation_select_box;

        }

    }

    /**
     * Set the selected variation of the variation field. This function makes sure that only one variation is selected.
     *
     * @since 1.8.0
     * @since 1.8.2 Make sure out of stock variations aren't set as selected by default.
     * @access private
     *
     * @param array $variations         Array list of variations defined on wwof_get_product_variation_field.
     * @param array $default_attributes Variable product default attributes.
     */
    private function wwof_product_variation_field_set_selected( &$variations , $default_attributes ) {

        // when searching for an sku value.
        if ( isset ( $_POST[ 'search' ] ) ) {

            $skus         = function_exists( 'array_column' ) ? array_column( $variations , 'sku' , 'value' ) : WWOF_Functions::array_column( $variations , 'sku' , 'value' );
            $selected_var = array_search( $_POST[ 'search' ] , $skus );

            foreach ( $variations as $key => $variation ) {

                if ( ! $variation[ 'instock' ] )
                    continue;

                if ( $selected_var == $variation[ 'value' ] ) {
                    $variations[ $key ][ 'selected' ] = true;
                    return;
                }
            }

        }

        // when variable product has set default attributes
        if ( ! empty( $default_attributes ) ) {

            foreach ( $variations as $key => $variation ) {

                if ( ! $variation[ 'instock' ] )
                    continue;

                $variations[ $key ][ 'selected' ] = true;
                foreach ( $variation[ 'attributes' ] as $attr_key => $attr_val ) {

                    $attr_key = str_replace( 'attribute_' , '' , $attr_key );

                    if ( !array_key_exists( $attr_key , $default_attributes ) || $default_attributes[ $attr_key ] != $attr_val )
                        $variations[ $key ][ 'selected' ] = false;
                }
            }
        }
    }

    /**
     * Get product add-ons.
     *
     * @since 1.5.0
     * @since 1.6.6 Underscore cased the function name and variables.
     * @access public
     *
     * @param $product
     * @return string;
     */
    public function wwof_get_product_addons( $product ) {

        global $Product_Addon_Display;

        if ( $Product_Addon_Display != null && ( get_class( $Product_Addon_Display ) == 'Product_Addon_Display' || get_class( $Product_Addon_Display ) == 'Product_Addon_Display_Legacy' ) ) {

            $product_id = ( WWOF_Functions::wwof_get_product_type( $product ) == 'variation' ) ?  WWOF_Functions::wwof_get_product_variation_parent( $product , true ) : WWOF_Functions::wwof_get_product_id( $product );

            ob_start();
            $Product_Addon_Display->display( $product_id );
            $product_addons = ob_get_clean();

            if ( trim( $product_addons ) == '' )
                return '';

            ob_start(); ?>

            <div class="wwof-product-add-ons-container">

                <h3 class="wwof-product-add-ons-title"><?php _e( 'Product Add-ons' , 'woocommerce-wholesale-order-form' ); ?> <span class="dashicons dashicons-arrow-down-alt2"></span></h3>

                <div class="wwof-product-add-ons">
                    <?php echo $product_addons; ?>
                </div>

            </div>

            <?php return ob_get_clean();

        }

        return '';

    }

    /**
     * Get product thumbnail.
     *
     * @param $product
     * @param $permalink
     * @param $image_size
     *
     * @return string
     * @since 1.0.0
     * @since 1.6.0 Removed the use of get_post_thumbnail, instead get the image by calling directly the WC_Product method get_image() function
     * @since 1.6.6 Underscore cased the function name and variables.
     */
    public function wwof_get_product_image ( $product , $permalink , $image_size ) {

        $show_thumbnail = get_option( 'wwof_general_show_product_thumbnail' );

        if ( $show_thumbnail !== false && $show_thumbnail == 'yes' ) {

            $img = $product->get_image( $image_size,
                                        array(
                                            'class' =>  'wwof_product_listing_item_thumbnail',
                                            'alt'   =>  $product->get_title()
                                        ) );

            $img = '<a class="product_link" href="' . $this->wwof_get_product_link( WWOF_Functions::wwof_get_product_id( $product ) , $permalink ) . '">' . $img . '</a>';
            $img = apply_filters( 'wwof_product_item_image' , $img );

            return $img;

        }

    }

    /**
     * Get product thumbnail dimension.
     *
     * @since 1.6.0
     * @since 1.6.6 Underscore cased the function name and variables.
     *
     * @return array
     */
    public function wwof_get_product_thumbnail_dimension(){

        $product_thumbnail_size = get_option( 'wwof_general_product_thumbnail_image_size' );
        $thumbnail_size = array( 48, 48 ); // Default Size

        if ( $product_thumbnail_size !== false && ! empty( $product_thumbnail_size[ 'width' ] ) && ! empty( $product_thumbnail_size[ 'height' ] ) )
            $thumbnail_size = array( (int) $product_thumbnail_size[ 'width' ], (int) $product_thumbnail_size[ 'height' ] );

        return apply_filters( 'wwof_filter_product_thumbnail_size', $thumbnail_size );

    }

    /**
     * Get product link.
     *
     * @param $product_id
     * @param $product_link
     *
     * @return mixed
     * @since 1.0.0
     * @since 1.6.6 Underscore cased the function name and variables.
     */
    public function wwof_get_product_link( $product_id , $product_link ) {

        $show_product_details_on_popup = get_option( 'wwof_general_display_product_details_on_popup' );

        if ( $show_product_details_on_popup !== false && $show_product_details_on_popup == 'yes' ) {

            // Show details via pop up
            return apply_filters( 'wwof_filter_product_link' , admin_url( 'admin-ajax.php' ) . '?action=wwof_get_product_details&product_id=' . $product_id );

        } else {

            // Direct to product page
            return apply_filters( 'wwof_filter_product_link' , $product_link );

        }

    }

    /**
     * Return product sku visibility classes.
     *
     * @return mixed
     *
     * @since 1.0.0
     * @since 1.6.6 Underscore cased the function name and variables.
     */
    public function wwof_get_product_sku_visibility_class () {

        $show_sku = get_option( 'wwof_general_show_product_sku' );

        if ( $show_sku === 'yes' )
            return apply_filters( 'wwof_filter_sku_visibility_class' , 'visible' );
        else
            return apply_filters( 'wwof_filter_sku_visibility_class' , 'hidden' );

    }

    /**
     * Return product stock quantity visibility class.
     *
     * @return mixed
     *
     * @since 1.2.0
     * @since 1.6.6 Underscore cased the function name and variables.
     */
    public function wwof_get_product_stock_quantity_visibility_class () {

        $show_stock_quantity = get_option( 'wwof_general_show_product_stock_quantity' );

        if ( $show_stock_quantity === 'yes' )
            return apply_filters( 'wwof_filter_stock_quantity_visibility_class' , 'visible' );
        else
            return apply_filters( 'wwof_filter_stock_quantity_visibility_class' , 'hidden' );

    }

    /**
     * Get product sku.
     *
     * @param $product
     *
     * @return string
     * @since 1.0.0
     * @since 1.6.6 Underscore cased the function name and variables.
     */
    public function wwof_get_product_sku( $product ) {

        $show_sku = get_option( 'wwof_general_show_product_sku' );

        if ( $show_sku !== false && $show_sku == 'yes' ) {

            $sku          = '';
            $product_type = WWOF_Functions::wwof_get_product_type( $product );

            if ( in_array( $product_type , array( 'simple' , 'variation' ) ) ) {

                // Simple Product

                $product_sku = '';
                $woocommerce_data = get_plugin_data( WP_PLUGIN_DIR . '/woocommerce/woocommerce.php' );

                if ( version_compare( $woocommerce_data[ 'Version' ] , '3.0.0' , '>=' ) )
                    $product_sku = $product->get_sku();
                else
                    $product_sku = $product->sku;

                $sku = '<span class="sku_wrapper"><span class="sku">' . $product_sku . '</span></span>';

            }

            $sku = apply_filters( 'wwof_filter_product_sku' , $sku );

            return $sku;

        }
    }

    /**
     * Get product stock quantity.
     *
     * @since 1.2.0
     * @since 1.3.5 When product is set to 'out of stock' or is managed and has stock quantity of 0 then display 'Out of Stock'. Also support back orders.
     * @since 1.6.6 Underscore cased the function name and variables.
     * @since 1.7.5 Use the standard stock status HTML generated by WooCommerce instead for better compatibility
     *
     * @param $product
     * @return string
     */
    public function wwof_get_product_stock_quantity ( $product ) {

        $stock_html = '';

        $woocommerce_data = get_plugin_data( WP_PLUGIN_DIR . '/woocommerce/woocommerce.php' );

        if ( WWOF_Functions::wwof_is_woocommerce_version_3() ) {

            if ( WWOF_Functions::wwof_get_product_type( $product ) != 'variable' ) {

                $stock_html = apply_filters( 'wwof_filter_product_stock_quantity' , wc_get_stock_html( $product ) , $product );;

            }

        } else { // WC 2.6 and below
        	$availability      = $product->get_availability();
        	$availability_html = empty( $availability['availability'] ) ? '' : '<p class="stock ' . esc_attr( $availability['class'] ) . '">' . esc_html( $availability['availability'] ) . '</p>';

        	$stock_html = apply_filters( 'woocommerce_stock_html', $availability_html, $availability['availability'], $product );

        }

        return $stock_html;

    }

    /**
     * Get product row actions fields.
     *
     * @param $product
     * @param $alternate
     *
     * @return string
     * @since 1.0.0
     * @since 1.6.3 WWOF-119 : Add message that product is unavailable if all variations is restricted to a specific wholesale role(s)
     * @since 1.6.6 Underscore cased the function name and variables.
     */
    public function wwof_get_product_row_action_fields( $product , $alternate = false ){

        if( isset( $_REQUEST[ 'wwof_variable_disabled' ] ) && $_REQUEST[ 'wwof_variable_disabled' ] == true ){

            $action_field = __( '<em>Unavailable</em>', 'woocommerce-wholesale-order-form' );
            unset( $_REQUEST[ 'wwof_variable_disabled' ] );

        } else if ( $product->is_in_stock() ) {

            // If all variations are out of stock we show "Out of Stock" text
            if( WWOF_Product_Listing_Helper::wwof_out_of_stock_variations_check( $product ) ){
                $action_field = '<span class="out-of-stock">' . __( 'Out of Stock' , 'woocommerce-wholesale-order-form' ) . '</span>';
            }else{
                if ( $alternate )
                    $action_field = '<input type="checkbox" class="wwof_add_to_cart_checkbox" id="wwof_product_' . WWOF_Functions::wwof_get_product_id( $product ) . '"/> <label for="wwof_product_' . WWOF_Functions::wwof_get_product_id( $product ) . '">' . __( 'Add To Cart' , 'woocommerce-wholesale-order-form' ) .'</label>';
                else
                    $action_field = '<input type="button" class="wwof_add_to_cart_button btn btn-primary single_add_to_cart_button button alt" value="' . __( 'Add To Cart' , 'woocommerce-wholesale-order-form' ) . '"/><span class="spinner"></span>';
            }

        } else
            $action_field = '<span class="out-of-stock">' . __( 'Out of Stock' , 'woocommerce-wholesale-order-form' ) . '</span>';

        $action_field = apply_filters( 'wwof_filter_product_item_action_controls' , $action_field , $product->is_in_stock() , $alternate );

        return $action_field;

    }

    /**
     * Get wholesale product listing pagination.
     *
     * @param $paged
     * @param $max_num_pages
     * @param $search
     * @param $cat_filter
     *
     * @return mixed
     * @since 1.0.0
     * @since 1.6.6 Underscore cased the function name and variables.
     */
    public function wwof_get_gallery_listing_pagination( $paged , $max_num_pages , $search , $cat_filter ) {

        if ( get_option( 'wwof_general_disable_pagination' ) == 'yes' )
            return;

        $big    =   999999999; // need an unlikely integer
        $args   =   array(
                        'base'      =>  str_replace( $big , '%#%' , esc_url( get_pagenum_link( $big ) ) ),
                        'format'    =>  '?paged=%#%',
                        'current'   =>  max( 1 , $paged ),
                        'total'     =>  $max_num_pages,
                        'type'      =>  'list',
                        'prev_text' =>  sprintf( __( '%1$s Previous' , 'woocommerce-wholesale-order-form' ) , '&laquo;' ),
                        'next_text' =>  sprintf( __( 'Next %1$s' , 'woocommerce-wholesale-order-form' ) , '&raquo;' ),
                        'add_args'  =>  array(
                                            'cat_filter' => $cat_filter
                                        )
                    );

        // Determine if we need to append the search keyword to the href url
        $search = trim( $search );
        if( !empty( $search ) && !is_null( $search ) && !$search == "" )
            $args[ 'add_args' ][ 'search' ] = urlencode( $search );

        $pagination_links = paginate_links( $args );
        $pagination_links = apply_filters( 'wwof_filter_product_listing_pagination' , $pagination_links , $paged , $max_num_pages );

        return $pagination_links;

    }

    /**
     * Get cart sub total (including/excluding) tax.
     *
     * @return string
     *
     * @since 1.2.0
     * @since 1.6.6 Underscore cased the function name and variables.
     */
    public function wwof_get_cart_subtotal () {

        ob_start();

        if ( get_option( 'wwof_general_display_cart_subtotal' ) == 'yes' ) { ?>

            <div class="wwof_cart_sub_total"><?php
                if ( WC()->cart->get_cart_contents_count() ) {
                    if ( get_option( 'wwof_general_cart_subtotal_prices_display' ) == 'excl' ) { ?>
                        <span class="sub_total excluding_tax"><?php
                            _e( 'Subtotal: ' , 'woocommerce-wholesale-order-form' );
                            echo wc_price( WC()->cart->cart_contents_total ); ?>
                        </span><?php
                    } else { ?>
                        <span class="sub_total including_tax"><?php
                            _e( 'Subtotal: ' , 'woocommerce-wholesale-order-form' );
                            echo wc_price( WC()->cart->cart_contents_total + WC()->cart->tax_total ) . ' <small>' . WC()->countries->inc_tax_or_vat() . '</small>';
                            ?>
                        </span><?php
                    }
                } else { ?>
                    <span class="empty_cart"><?php _e( 'Cart Empty' , 'woocommerce-wholesale-order-form' ); ?></span><?php
                } ?>

            </div><?php

        }

        return ob_get_clean();

    }

    /**
     * Check if the variation is purchasable and if so whether it is available (enough stock, can be backordered etc)
     *
     * @since 1.4.0
     * @since 1.6.6 Underscore cased the function name and variables.
     *
     * @param $variation_obj
     * @param $product
     * @return bool
     */
    public function wwof_variation_is_purchasable( $variation_obj, $product ) {

        // If the stock is managed on the variation
        if ( $variation_obj->managing_stock() ) {
            $item_availability = $variation_obj->get_availability();

        // If the stock is NOT managed on the variation but IS managed on the parent
        } else if ( !$variation_obj->managing_stock() && $product->managing_stock() ) {
            $item_availability = $product->get_availability();

        // If the product is NOT managed on either the variation or the parent object,
        // then we always treat it as "In Stock" unless it's been manually changed to "Out Of Stock"
        } else if ( !$variation_obj->managing_stock() && !$product->managing_stock() ) {

            // We need to check if the product is manually marked as Out Of Stock
            if ( !$variation_obj->is_in_stock()) {
                $item_availability[ 'class' ] = 'out-of-stock';
            } else {
                $item_availability[ 'class' ] = 'in-stock';
            }
        }

        $variation_is_purchasable = $variation_obj->is_purchasable();
        $variation_classes        = apply_filters( 'wwof_filter_variation_class' , array( 'in-stock' , 'available-on-backorder' ) );
        $variation_is_available   = in_array( $item_availability[ 'class' ] , $variation_classes );

        return $variation_is_purchasable && $variation_is_available;

    }

    /**
     * Product variations description html
     *
     * @since 1.7.3
     *
     * @param $product
     * @return string
     */
    public function wwof_get_variations_description( $product ) {

        $product_type = WWOF_Functions::wwof_get_product_type( $product );

        if ( $product_type == 'variable' ){

            $product_variations = $product->get_available_variations();
            $desc_html = '';

            if( !empty( $product_variations ) ){
                foreach ( $product_variations as $variation ) {
                    $variation_obj = wc_get_product( $variation[ 'variation_id' ] );
                    $desc_html .= '<span class="variation-desc variation-desc-' . $variation[ 'variation_id' ] .'" style="display:none;">' . do_shortcode( $variation_obj->get_description() ) . '</span>';
                }
            }

            return $desc_html;

        } elseif ( $product_type == 'variation' )
            return '<span class="variation-desc" style="display:block;">' . $product->get_description() . '</span>';

    }

}

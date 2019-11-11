<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class WWOF_Functions {

	/**
     * Utility function that determines if a plugin is active or not.
     *
     * @since 1.7.0
     * @access public
     *
     * @param string $plugin_basename Plugin base name. Ex. woocommerce/woocommerce.php
     * @return boolean True if active, false otherwise.
     */
    public static function is_plugin_active( $plugin_basename ) {

        // Makes sure the plugin is defined before trying to use it
        if ( !function_exists( 'is_plugin_active' ) )
            include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

        return is_plugin_active( $plugin_basename );

    }

	/**
	 * Check for plugin dependencies of WooCommerce Wholesale Order Form plugin.
	 *
	 * @since 1.6.3
     * @since 1.6.6 Refactor codebase and move to its proper model. Underscore cased the function name and variables.
	 * @return array
	 */
	public static function wwof_check_plugin_dependencies() {

        // Makes sure the plugin is defined before trying to use it
        if ( ! function_exists( 'is_plugin_active' ) )
            include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

        $i = 0;
        $plugins = array();
        $requiredPlugins = apply_filters( 'wwof_required_plugins', array(
                                    'woocommerce/woocommerce.php'
                                ) );

        foreach ( $requiredPlugins as $plugin ) {
            if ( ! is_plugin_active( $plugin ) ) {
                $pluginName = explode( '/', $plugin );
                $plugins[ $i ][ 'plugin-key' ] = $pluginName[ 0 ];
                $plugins[ $i ][ 'plugin-base' ] = $plugin;
                $plugins[ $i ][ 'plugin-name' ] = ucwords( str_replace( '-', ' ', $pluginName[ 0 ] ) );
            }
            $i++;
        }

        return $plugins;

    }

	/**
	 * Get data about the current woocommerce installation.
	 *
	 * @since 1.6.4
     * @since 1.6.6 Refactor codebase and move to its proper model. Underscore cased the function name and variables.
	 * @return array Array of data about the current woocommerce installation.
	 */
	public static function wwof_is_woocommerce_version_3() {

        if ( ! function_exists( 'get_plugin_data' ) )
            require_once( ABSPATH . '/wp-admin/includes/plugin.php' );

        $woocommerce_data = get_plugin_data( WP_PLUGIN_DIR . '/woocommerce/woocommerce.php' );

        if ( version_compare( $woocommerce_data[ 'Version' ] , '3.0.0' , '>=' ) )
            return true;
        else
            return false;

    }

	/**
	 * Get product price including tax. WC 2.7.
	 *
	 * @since 1.6.4
     * @since 1.6.6 Refactor codebase and move to its proper model. Underscore cased the function name and variables.
	 *
	 * @param WC_Product $product Product object.
	 * @param array      $ars     Array of arguments data.
	 * @return float Product price with tax.
	 */
	public static function wwof_get_price_including_tax( $product , $args ) {

        if ( self::wwof_is_woocommerce_version_3() )
            return wc_get_price_including_tax( $product , $args );
        else {

            $qty   = (int) $args[ 'qty' ] ? $args[ 'qty' ] : 1;
            $price = $args[ 'price' ];

            return $product->get_price_including_tax( $qty, $price );

        }
    }

	/**
	 * Get product price excluding tax. WC 2.7.
	 *
	 * @since 1.6.4
     * @since 1.6.6 Refactor codebase and move to its proper model. Underscore cased the function name and variables.
	 *
	 * @param WC_Product $product Product object.
	 * @param array      $ars     Array of arguments data.
	 * @return float Product price with no tax.
	 */
	public static function wwof_get_price_excluding_tax( $product , $args ) {

        if ( self::wwof_is_woocommerce_version_3() )
            return wc_get_price_excluding_tax( $product , $args );
        else {

            $qty   = (int) $args[ 'qty' ] ? $args[ 'qty' ] : 1;
            $price = $args[ 'price' ];

            return $product->get_price_excluding_tax( $qty , $price );

        }
    }

	/**
	 * Get product id. WC 2.7.
	 *
	 * @since 1.6.4
     * @since 1.6.6 Refactor codebase and move to its proper model. Underscore cased the function name and variables.
	 *
	 * @param WC_Product $product Product object.
	 * @return int Product ID.
	 */
    public static function wwof_get_product_id( $product ) {

        if ( is_a( $product , 'WC_Product' ) ) {

            if ( self::wwof_is_woocommerce_version_3() )
                return $product->get_id();
            else {

                switch ( $product->product_type ) {

                    case 'simple':
                    case 'variable':
                    case 'external':
                    case 'grouped':
                        return $product->id;
                    case 'variation':
                        return $product->variation_id;
                    default:
                        return apply_filters( 'wwof_third_party_product_id' , 0 , $product );

                }

            }

        } else {

			$trace  = debug_backtrace();
			$caller = array_shift( $trace );

            error_log( 'WWOF Error : wwof_get_product_id function expect parameter $product of type WC_Product. Trace: ' . $caller[ 'file' ] . ' on line ' . $caller[ 'line' ] );
            return 0;

        }
    }

	/**
	 * Get product type. WC 2.7.
	 *
	 * @since 1.6.4
     * @since 1.6.6 Refactor codebase and move to its proper model. Underscore cased the function name and variables.
	 *
	 * @param WC_Product $product Product type.
	 * @return string Product type.
	 */
    public static function wwof_get_product_type( $product ) {

        if ( is_a( $product , 'WC_Product' ) ) {

            if ( self::wwof_is_woocommerce_version_3() )
                return $product->get_type();
            else
                return $product->product_type;

        } else {

			$trace  = debug_backtrace();
			$caller = array_shift( $trace );

            error_log( 'WWOF Error : wwof_get_product_type function expect parameter $product of type WC_Product. Trace: ' . $caller[ 'file' ] . ' on line ' . $caller[ 'line' ] );
            return 0;

        }
    }

	/**
	 * Get product type. WC 2.7.
	 *
	 * @since 1.6.4
     * @since 1.6.6 Refactor codebase and move to its proper model. Underscore cased the function name and variables.
	 *
	 * @param WC_Product_Variable $product Product Variable type.
	 * @return array Product Variable Attributes.
	 */
	public static function wwof_get_default_attributes( $product ) {

        if ( is_a( $product , 'WC_Product_Variable' ) ) {

            if ( self::wwof_is_woocommerce_version_3() )
                return $product->get_default_attributes();
            else
                return $product->get_variation_default_attributes();

        } else {

			$trace  = debug_backtrace();
			$caller = array_shift( $trace );

            error_log( 'WWOF Error : wwof_get_default_attributes function expect parameter $product of type WC_Product_Variable. Trace: ' . $caller[ 'file' ] . ' on line ' . $caller[ 'line' ] );
            return 0;

        }
    }

	/**
	 * Get gallery image IDs of the product. WC 2.7.
	 *
	 * @since 1.6.4
     * @since 1.6.6 Refactor codebase and move to its proper model. Underscore cased the function name and variables.
	 *
	 * @param WC_Product $product Product type.
	 * @return array Product Image ID's.
	 */
	public static function wwof_get_gallery_image_ids( $product ) {

        if ( is_a( $product , 'WC_Product' ) ) {

            if ( self::wwof_is_woocommerce_version_3() )
                return $product->get_gallery_image_ids();
            else
                return $product->get_gallery_attachment_ids();

        } else {

			$trace  = debug_backtrace();
			$caller = array_shift( $trace );

            error_log( 'WWOF Error : wwof_get_gallery_image_ids function expect parameter $product of type WC_Product. Trace: ' . $caller[ 'file' ] . ' on line ' . $caller[ 'line' ] );
            return 0;

        }
    }

	/**
	 * Get product rating in html. WC 2.7.
	 *
	 * @since 1.6.4
     * @since 1.6.6 Refactor codebase and move to its proper model. Underscore cased the function name and variables.
	 *
	 * @param WC_Product $product Product type.
	 * @return string Average Product Rating.
	 */
	public static function wwof_get_rating_html( $product ) {

        if ( is_a( $product , 'WC_Product' ) ) {

            if ( self::wwof_is_woocommerce_version_3() )
                return wc_get_rating_html( $product->get_average_rating() );
            else
                return $product->get_rating_html();

        } else {

			$trace  = debug_backtrace();
			$caller = array_shift( $trace );

            error_log( 'WWOF Error : wwof_get_rating_html function expect parameter $product of type WC_Product. Trace: ' . $caller[ 'file' ] . ' on line ' . $caller[ 'line' ] );
            return 0;

        }
    }

	/**
	 * Get product product category list. WC 2.7.
	 *
	 * @since 1.6.4
     * @since 1.6.6 Refactor codebase and move to its proper model. Underscore cased the function name and variables.
	 *
	 * @param WC_Product $product Product type.
	 * @return string Product Category List html.
	 */
	public static function wwof_get_product_category_list( $product ) {

        if ( is_a( $product , 'WC_Product' ) ) {

            $productID = get_post( self::wwof_get_product_id( $product ) );

            if ( self::wwof_is_woocommerce_version_3() )
                return wc_get_product_category_list( $productID , ', ', '<p class="product-categories">' . _n( 'Category:' , 'Categories:' , count( $product->get_category_ids() ) , 'woocommerce-wholesale-order-form' ) . ' ' , '</p>' );
            else
                return '<p class="product-categories">' . $product->get_categories() . '</p>';

        } else {

			$trace  = debug_backtrace();
			$caller = array_shift( $trace );

            error_log( 'WWOF Error : wwof_get_product_category_list function expect parameter $product of type WC_Product. Trace: ' . $caller[ 'file' ] . ' on line ' . $caller[ 'line' ] );
            return 0;

        }
    }

    /**
     * Returns the product visibility value.
     *
     * @since 1.6.6
     *
     * @param WC_Product $product Product type.
     * @return boolean.
     */
    public static function wwof_get_product_visibility( $product ) {

        if ( is_a( $product , 'WC_Product' ) ) {

            if ( self::wwof_is_woocommerce_version_3() )
                return $product->get_catalog_visibility();
            else
                return $product->visibility;

        } else {

			$trace  = debug_backtrace();
			$caller = array_shift( $trace );

            error_log( 'WWOF Error : wwof_get_product_visibility function expect parameter $product of type WC_Product. Trace: ' . $caller[ 'file' ] . ' on line ' . $caller[ 'line' ] );
            return 0;

        }
    }

	/**
     * Returns the attributes of a product variation.
     *
     * @since 1.7.0
     *
     * @param WC_Product_Variation $product Product type.
     * @return array.
     */
	public static function wwof_get_product_variation_attributes( $product ) {

		if ( is_a( $product , 'WC_Product_Variation' ) ) {

			if ( self::wwof_is_woocommerce_version_3() )
	            return $product->get_attributes();
	        else
				return wc_get_product_variation_attributes( $product->variation_id );

        } else {

			$trace  = debug_backtrace();
			$caller = array_shift( $trace );

            error_log( 'WWOF Error : wwof_get_product_variation_attributes function expect parameter $product of type WC_Product_Variation. Trace: ' . $caller[ 'file' ] . ' on line ' . $caller[ 'line' ] );
            return 0;

        }
	}

	/**
     * Returns the parent object of a product variation.
     *
     * @since 1.7.0
     *
     * @param WC_Product_Variation $product Product type.
	 * @param boolean			   $id_only check if function will return a product object or just an id.
     * @return WC_Product_Variable object / int
     */
	public static function wwof_get_product_variation_parent( $product , $id_only = false ) {

		if ( is_a( $product , 'WC_Product_Variation' ) ) {

			if ( self::wwof_is_woocommerce_version_3() )
	            return ( $id_only ) ? $product->get_parent_id() : wc_get_product( $product->get_parent_id() );
	        else
				return ( $id_only ) ? $product->id : $product->parent;

        } else {

			$trace  = debug_backtrace();
			$caller = array_shift( $trace );

            error_log( 'WWOF Error : wwof_get_product_variation_parent function expect parameter $product of type WC_Product_Variation. Trace: ' . $caller[ 'file' ] . ' on line ' . $caller[ 'line' ] );
            return 0;

        }
	}

	/**
	 * Delete code activation flag on plugin deactivate.
	 *
	 * @param bool $network_wide
	 *
	 * @since 1.6.4
     * @since 1.6.6 Refactor codebase and move to its proper model. Underscore cased the function name and variables.
	 */
	public static function wwof_global_plugin_deactivate( $network_wide ) {

        global $wpdb;

        // check if it is a multisite network
        if ( is_multisite() ) {

            // check if the plugin has been deactivated on the network or on a single site
            if ( $network_wide ) {

                // get ids of all sites
                $blog_ids = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" );

                foreach ( $blog_ids as $blog_id ) {

                    switch_to_blog( $blog_id );
                    delete_option( 'wwof_activation_code_triggered' );
                    delete_option( 'wwof_option_installed_version' );

                }

                restore_current_blog();

            } else {

                // deactivated on a single site, in a multi-site
                delete_option( 'wwof_activation_code_triggered' );
                delete_option( 'wwof_option_installed_version' );

            }

        } else {

            // deactivated on a single site
            delete_option( 'wwof_activation_code_triggered' );
            delete_option( 'wwof_option_installed_version' );

        }
    }

	/**
	 * Log deprecated function error to the php_error.log file and display on screen when not on AJAX.
	 *
	 * @since 1.7.0
	 * @access public
	 *
	 * @param array  $trace       debug_backtrace() output
	 * @param string $function    Name of depecrated function.
	 * @param string $version     Version when the function is set as depecrated since.
	 * @param string $replacement Name of function to be replaced.
	 */
	public static function deprecated_function( $trace , $function , $version , $replacement = null ) {

		$caller = array_shift( $trace );

		$log_string  = "The <em>{$function}</em> function is deprecated since version <em>{$version}</em>.";
		$log_string .= $replacement ? " Replace with <em>{$replacement}</em>." : '';
		$log_string .= ' Trace: <strong>' . $caller[ 'file' ] . '</strong> on line <strong>' . $caller[ 'line' ] . '</strong>';

		error_log( strip_tags( $log_string ) );

		if ( ! is_ajax() && WP_DEBUG && apply_filters( 'deprecated_function_trigger_error', true ) )
			echo $log_string;
	}

    /**
     * Since getProductWholesalePrice() function will get deprecated in WWPP 1.15.0 we will now get wholesale price in WWP.
     *
     * @since 1.7.6
     * @since 1.8.0 Add support for 'get_product_wholesale_price_on_shop_v2'.
     *
     * @param Object    $product            WC_Product Object
     * @param array     $wholesale_role     User Wholesale Role
     * @return string.
     */
    public static function wwof_get_wholesale_price( $product , $wholesale_role ) {

        if ( ! function_exists( 'get_plugin_data' ) )
            require_once( ABSPATH . '/wp-admin/includes/plugin.php' );

        $wwpp_data       = get_plugin_data( WP_PLUGIN_DIR . '/woocommerce-wholesale-prices/woocommerce-wholesale-prices.bootstrap.php' );
        $wholesale_price = "";

        if ( version_compare( $wwpp_data[ 'Version' ] , '1.6.0' , '>=' ) ) {

            $wholesale_price_data = WWP_Wholesale_Prices::get_product_wholesale_price_on_shop_v2( WWOF_Functions::wwof_get_product_id( $product ) , $wholesale_role );
            $wholesale_price      = $wholesale_price_data[ 'wholesale_price' ];

        } elseif ( version_compare( $wwpp_data[ 'Version' ] , '1.5.0' , '>=' ) )
            $wholesale_price = WWP_Wholesale_Prices::get_product_wholesale_price_on_shop( WWOF_Functions::wwof_get_product_id( $product ) , $wholesale_role );
        else
            $wholesale_price = WWP_Wholesale_Prices::getProductWholesalePrice( WWOF_Functions::wwof_get_product_id( $product ) , $wholesale_role );

        return $wholesale_price;

    }

    /**
     * Dependency plugin version compare.
     *
     * @since 1.8.0
     * @access public
     *
     * @param string $plugin_prefix Prefix of the plugin dependency (wwp, wwpp).
     * @param string $version       Plugin version to compare.
     * @param string $operator      Conditional operator to use for version_compare().
     */
    public static function wwof_dependency_version_compare( $plugin_prefix , $version , $operator ) {

        switch( $plugin_prefix ) {
            case 'wwp' :
                $basename = 'woocommerce-wholesale-prices/woocommerce-wholesale-prices.bootstrap.php';
                break;
            case 'wwpp' :
                $basename = 'woocommerce-wholesale-prices-premium/woocommerce-wholesale-prices-premium.bootstrap.php';
                break;
            default :
                return;
        }

        if ( ! function_exists( 'get_plugin_data' ) )
            require_once( ABSPATH . '/wp-admin/includes/plugin.php' );

        $plugin_data = get_plugin_data( WP_PLUGIN_DIR . '/' . $basename );

        return version_compare( $plugin_data[ 'Version' ] , $version , $operator );

    }

    /**
     * PHP <= 5.4 support for array_column. Code below is the simplest version of the alternative function that is needed.
     *
     * @since 1.8.2
     * @access public
     *
     * @param array $array   Array source.
     * @param string $column Array column key.
     * @param string $index  Array column to use as result index.
     */
    public static function array_column( $array , $column , $index = null ) {

        $result = array();
        foreach ( $array as $key => $row ) {

            $result_key            = $index && isset( $row[ $index ] ) ? $row[ $index ] : $key;
            $result[ $result_key ] = isset( $row[ $column ] ) ? $row[ $column ] : '';
        }

        return $result;
    }

}


/*
|------------------------------------------------------------------------------------------------------------------
| Deprecated methods as of version 1.6.6
|------------------------------------------------------------------------------------------------------------------
*/

/**
 * Get product id. WC 2.7.
 *
 * @deprecated 1.6.6
 *
 * @param WC_Product $product Product object.
 * @return int Product ID.
 */
if( ! function_exists( 'wwofGetProductID' ) ) {

    function wwofGetProductID( $product ) {

		WWOF_Functions::deprecated_function( debug_backtrace() , 'wwofGetProductID' , '1.6.6' , 'WWOF_Functions::wwof_get_product_id' );
        return WWOF_Functions::wwof_get_product_id( $product , $args );
	}

}

/**
 * Get gallery image IDs of the product. WC 2.7.
 *
 * @since 1.6.4
 *
 * @param WC_Product $product Product type.
 * @return array Product Image ID's.
 */
if( ! function_exists( 'wwofGetGalleryImageIDs' ) ) {

    function wwofGetGalleryImageIDs( $product ) {

		WWOF_Functions::deprecated_function( debug_backtrace() , 'wwofGetGalleryImageIDs' , '1.6.6' , 'WWOF_Functions::wwof_get_gallery_image_ids' );
		return WWOF_Functions::wwof_get_gallery_image_ids( $product );
	}

}

/**
 * Get product rating in html. WC 2.7.
 *
 * @since 1.6.4
 *
 * @param WC_Product $product Product type.
 * @return string Average Product Rating.
 */
if( ! function_exists( 'wwofGetRatingHtml' ) ) {

    function wwofGetRatingHtml( $product ) {

		WWOF_Functions::deprecated_function( debug_backtrace() , 'wwofGetRatingHtml' , '1.6.6' , 'WWOF_Functions::wwof_get_rating_html' );
		return WWOF_Functions::wwof_get_rating_html( $product );
	}

}

/**
 * Get product product category list. WC 2.7.
 *
 * @since 1.6.4
 *
 * @param WC_Product $product Product type.
 * @return string Product Category List html.
 */
if( ! function_exists( 'wwofGetProductCategoryList' ) ) {

    function wwofGetProductCategoryList( $product ) {

		WWOF_Functions::deprecated_function( debug_backtrace() , 'wwofGetProductCategoryList' , '1.6.6' , 'WWOF_Functions::wwof_get_product_category_list' );
		return WWOF_Functions::wwof_get_product_category_list( $product );
	}

}

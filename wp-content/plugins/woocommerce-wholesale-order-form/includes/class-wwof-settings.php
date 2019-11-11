<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WWOF_Settings' ) ) {

    class WWOF_Settings extends WC_Settings_Page {

        /**
         * Constructor.
         */
        public function __construct() {

            $this->id    = 'wwof_settings';
            $this->label = __( 'Wholesale Ordering' , 'woocommerce-wholesale-order-form' );

            add_filter( 'woocommerce_settings_tabs_array' , array( $this, 'add_settings_page' ), 30 ); // 30 so it is after the emails tab
            add_action( 'woocommerce_settings_' . $this->id , array( $this, 'output' ) );
            add_action( 'woocommerce_settings_save_' . $this->id , array( $this, 'save' ) );
            add_action( 'woocommerce_sections_' . $this->id , array( $this, 'output_sections' ) );

            add_action( 'woocommerce_admin_field_wwof_button' , array( $this, 'render_wwof_button' ) );
            add_action( 'woocommerce_admin_field_wwof_editor' , array( $this, 'render_wwof_editor' ) );
            add_action( 'woocommerce_admin_field_wwof_help_resources' , array( $this , 'render_wwof_help_resources' ) );
            add_action( 'woocommerce_admin_field_wwof_help_resources' , array( $this , 'render_wwof_help_resources' ) );
            add_action( 'woocommerce_admin_field_wwof_image_dimension' , array( $this , 'render_wwof_image_dimension' ) );

        }

        /**
         * Get sections.
         *
         * @return array
         * @since 1.0.0
         */
        public function get_sections() {

            $sections = array(
                ''                                  =>  __( 'General' , 'woocommerce-wholesale-order-form' ),
                'wwof_setting_filters_section'      =>  __( 'Filters' , 'woocommerce-wholesale-order-form' ),
                'wwof_settings_permissions_section' =>  __( 'Permissions' , 'woocommerce-wholesale-order-form' ),
                'wwof_settings_help_section'        =>  __( 'Help' , 'woocommerce-wholesale-order-form' ),
            );

            return apply_filters( 'woocommerce_get_sections_' . $this->id , $sections );
        }

        /**
         * Output the settings.
         *
         * @since 1.0.0
         */
        public function output() {

            global $current_section;

            $settings = $this->get_settings( $current_section );
            WC_Admin_Settings::output_fields( $settings );

        }

        /**
         * Save settings.
         *
         * @since 1.0.0
         */
        public function save() {

            global $current_section;

            $settings = $this->get_settings( $current_section );

            // Filter wysiwyg content so it gets stored properly after sanitization
            if( isset( $_POST[ 'noaccess_message' ] ) && !empty( $_POST[ 'noaccess_message' ] ) ){

                foreach ( $_POST[ 'noaccess_message' ] as $index => $content ) {

                    $_POST[$index] = htmlentities ( wpautop( $content ) );

                }

            }

            WC_Admin_Settings::save_fields( $settings );

        }

        /**
         * Get settings array.
         *
         * @param string $current_section
         *
         * @return mixed
         * @since 1.0.0
         */
        public function get_settings( $current_section = '' ) {

            if ( $current_section == 'wwof_setting_filters_section' ) {

                // Filters Section
                $settings = apply_filters( 'wwof_settings_filters_section_settings' , $this->_get_filters_section_settings() ) ;

            } elseif ( $current_section == 'wwof_settings_permissions_section' ) {

                // Permissions Section
                $settings = apply_filters( 'wwof_settings_permissions_section_settings' , $this->_get_permissions_section_settings() );

            } elseif ( $current_section == 'wwof_settings_help_section' ) {

                // Help Section
                $settings = apply_filters( 'wwof_settings_help_section_settings' , $this->_get_help_section_settings() );

            } else {

                // General Settings
                $settings = apply_filters( 'wwof_settings_general_section_settings' , $this->_get_general_section_settings() );

            }

            return apply_filters( 'woocommerce_get_settings_' . $this->id , $settings , $current_section );

        }




        /*
         |--------------------------------------------------------------------------------------------------------------
         | Section Settings
         |--------------------------------------------------------------------------------------------------------------
         */

        /**
         * Get general section settings.
         *
         * @since 1.0.0
         * @since 1.3.0 Add option to show/hide quantity based discounts on wholesale order page.
         *
         * @return array
         */
        private function _get_general_section_settings() {

            global $WWOF_SETTINGS_SORT_BY, $WWOF_SETTINGS_DEFAULT_PPP;

            return array(

                array(
                    'title'     =>  __( 'General Options', 'woocommerce-wholesale-order-form' ),
                    'type'      =>  'title',
                    'desc'      =>  '',
                    'id'        =>  'wwof_general_main_title'
                ),

                array(
                    'title'     =>  __( 'Use Alternate View Of Wholesale Page?', 'woocommerce-wholesale-order-form' ),
                    'type'      =>  'checkbox',
                    'desc'      =>  __( 'Checkbox on the right side of each product, and add to cart button at the bottom of the list' , 'woocommerce-wholesale-order-form' ),
                    'id'        =>  'wwof_general_use_alternate_view_of_wholesale_page'
                ),

                array(
                    'title'     =>  __( 'Disable Pagination?' , 'woocommerce-wholesale-order-form' ),
                    'type'      =>  'checkbox',
                    'desc'      =>  __( 'Shows all products by lazy loading them in when the user scrolls down. The form will load in groups of products based on the number specified in the Products Per Page setting.' , 'woocommerce-wholesale-order-form' ),
                    'id'        =>  'wwof_general_disable_pagination'
                ),

                array(
                    'title'     =>  __( 'Products Per Page' , 'woocommerce-wholesale-order-form' ),
                    'type'      =>  'number',
                    'desc_tip'  =>  sprintf( __( 'Number of products to display per page (for pagination) or how many products to load at a time (for lazy loading). Default is 12 products when left empty.' , 'woocommerce-wholesale-order-form' ) , $WWOF_SETTINGS_DEFAULT_PPP ),
                    'id'        =>  'wwof_general_products_per_page',
                ),

                array(
                    'title'     =>  __( 'Show In Stock Quantity?', 'woocommerce-wholesale-order-form' ),
                    'type'      =>  'checkbox',
                    'desc'      =>  __( 'Should we display product stock quantity on the product listing on the front end?' , 'woocommerce-wholesale-order-form' ),
                    'id'        =>  'wwof_general_show_product_stock_quantity'
                ),

                array(
                    'title'     =>  __( 'Show Product SKU?', 'woocommerce-wholesale-order-form' ),
                    'type'      =>  'checkbox',
                    'desc'      =>  __( 'Should we display product sku on the product listing on the front end?' , 'woocommerce-wholesale-order-form' ),
                    'id'        =>  'wwof_general_show_product_sku'
                ),

                array(
                    'title'     =>  __( 'Allow Product SKU Search?', 'woocommerce-wholesale-order-form' ),
                    'type'      =>  'checkbox',
                    'desc'      =>  __( 'Should we allow searching for products via sku on the product listing on the front end?' , 'woocommerce-wholesale-order-form' ),
                    'id'        =>  'wwof_general_allow_product_sku_search'
                ),

                array(
                    'title'     =>  __( 'Show Product Thumbnail?', 'woocommerce-wholesale-order-form' ),
                    'type'      =>  'checkbox',
                    'desc'      =>  __( 'Should we display a small product thumbnail on the product listing on the front end?' , 'woocommerce-wholesale-order-form' ),
                    'id'        =>  'wwof_general_show_product_thumbnail'
                ),

                array(
                    'title'     => __( 'Product Thumbnail Size', 'woocommerce-wholesale-order-form' ),
                    'desc'      => __( 'This size is used in wholesale product listings', 'woocommerce-wholesale-order-form' ),
                    'id'        => 'wwof_general_product_thumbnail_image_size',
                    'css'       => '',
                    'type'      => 'wwof_image_dimension',
                    'default'   => array(
                        'width'     => '48',
                        'height'    => '48'
                    ),
                    'desc_tip'  =>  true,
                ),

                array(
                    'title'     =>  __( 'Display the product details in a lightbox popup on click?' , 'woocommerce-wholesale-order-form' ),
                    'type'      =>  'checkbox',
                    'desc'      =>  __( "Should the product details be displayed in a popup or redirect the user to the product's page?" , 'woocommerce-wholesale-order-form' ),
                    'id'        =>  'wwof_general_display_product_details_on_popup'
                ),

                array(
                    'title'     =>  __( 'Display Zero Inventory Products?' , 'woocommerce-wholesale-order-form' ),
                    'type'      =>  'checkbox',
                    'desc'      =>  __( 'Zero inventory products are products that are out of inventory and do not allow backorders. This also includes non simple products whose composition requires a certain products that have zero inventory.' , 'woocommerce-wholesale-order-form' ),
                    'id'        =>  'wwof_general_display_zero_products'
                ),

                array(
                    'title'     =>  __( 'Hide wholesale quantity discount prices.' , 'woocommerce-wholesale-order-form' ),
                    'type'      =>  'checkbox',
                    'desc'      =>  sprintf( __( 'Hides the small table printed under the wholesale price when quantity based discounts are available for that product. Only used when <a href="%1$s" target="blank">WooCommerce Wholesale Prices Premium</a> is active.' , 'woocommerce-wholesale-order-form' ) , 'https://wholesalesuiteplugin.com/product/woocommerce-wholesale-prices-premium/' ),
                    'id'        =>  'wwof_general_hide_quantity_discounts'
                ),

                array(
                    'title'     =>  __( 'Sort By' , 'woocommerce-wholesale-order-form' ),
                    'type'      =>  'select',
                    'desc'      =>  '',
                    'id'        =>  'wwof_general_sort_by',
                    'class'     =>  'chosen_select',
                    'options'   =>  $WWOF_SETTINGS_SORT_BY
                ),

                array(
                    'title'     =>  __( 'Sort Order' , 'woocommerce-wholesale-order-form' ),
                    'type'      =>  'select',
                    'desc'      =>  '',
                    'id'        =>  'wwof_general_sort_order',
                    'class'     =>  'chosen_select',
                    'options'   =>  array(
                        'asc'   =>  __( 'Ascending' , 'woocommerce-wholesale-order-form' ),
                        'desc'  =>  __( 'Descending' , 'woocommerce-wholesale-order-form' )
                    )
                ),

                array(
                    'title'     =>  __( 'Display Cart Subtotal?' , 'woocommerce-wholesale-order-form' ),
                    'type'      =>  'checkbox',
                    'desc'      =>  __( 'Display cart subtotal at the bottom of the order form' , 'woocommerce-wholesale-order-form' ),
                    'id'        =>  'wwof_general_display_cart_subtotal'
                ),

                array(
                    'title'     =>  __( 'Cart Sub Total Prices Display' , 'woocommerce-wholesale-order-form' ),
                    'type'      =>  'select',
                    'desc'      =>  __( 'Either to include or exclude price on sub total. Only used if "Display Cart Sub Total" option above is enabled.' , 'woocommerce-wholesale-order-form' ),
                    'desc_tip'  =>  true,
                    'id'        =>  'wwof_general_cart_subtotal_prices_display',
                    'class'     =>  'chosen_select',
                    'options'   =>  array (
                        'incl'  =>  __( 'Including tax' , 'woocommerce-wholesale-order-form' ),
                        'excl'  =>  __( 'Excluding tax' , 'woocommerce-wholesale-order-form' )
                    ),
                    'default'   =>  'incl'
                ),

                array(
                    'title'     =>  __( 'List product variation individually' , 'woocommerce-wholesale-order-form' ),
                    'type'      =>  'checkbox',
                    'desc'      =>  __( 'Enabling this setting will list down each product variation individually and have its own row in the wholesale order form.' , 'woocommerce-wholesale-order-form' ),
                    'id'        =>  'wwof_general_list_product_variation_individually',
                ),

                array(
                    'title'     =>  __( 'Show Wholesale Order Requirements Message in Order Form' , 'woocommerce-wholesale-order-form' ),
                    'type'      =>  'select',
                    'desc'      =>  __( 'Only works if there are Order Requirements set in WooCommerce Wholesale Prices Premium plugin settings.' , 'woocommerce-wholesale-order-form' ),
                    'desc_tip'  =>  true,
                    'id'        =>  'wwof_display_wholesale_price_requirement',
                    'class'     =>  'chosen_select',
                    'options'   =>  array (
                        'yes'   =>  __( 'Yes' , 'woocommerce-wholesale-order-form' ),
                        'no'    =>  __( 'No' , 'woocommerce-wholesale-order-form' )
                    ),
                    'default'   =>  'yes'
                ),
                
                array(
                    'type'      =>  'sectionend',
                    'id'        =>  'wwof_general_sectionend'
                )

            );

        }

        /**
         * Get filters section settings.
         *
         * @return array
         * @since 1.0.0
         */
        private function _get_filters_section_settings() {

            // Get all product categories
            $termArgs = array(
                'taxonomy' => 'product_cat',
                'hide_empty' => false
            );
            $productTermsObject = get_terms( $termArgs );
            $productTerms = array();

            if ( !is_wp_error( $productTermsObject ) ) {

                foreach( $productTermsObject as $term )
                    $productTerms[ $term->slug ] = $term->name;

            }

            foreach ( WWOF_Product_Listing_Helper::get_all_products( 'ID , post_title' ) as $post ){
                $product = wc_get_product( $post->ID );
                $allProducts[ WWOF_Functions::wwof_get_product_id( $product ) ] = '[ID : ' . WWOF_Functions::wwof_get_product_id( $product ) . '] ' . $post->post_title;
            }

            // Add "None" category selection for "no default" option
            $productTerms2 = array_merge( array ('none' => __( 'No Default' , 'woocommerce-wholesale-order-form' ) ), $productTerms );

            return array(

                array(
                    'title'         =>  __( 'Filters Options', 'woocommerce-wholesale-order-form' ),
                    'type'          =>  'title',
                    'desc'          =>  '',
                    'id'            =>  'wwof_filters_main_title'
                ),

                array(
                    'title'             =>  __( 'Product Category Filter' , 'woocommerce-wholesale-order-form' ),
                    'type'              =>  'multiselect',
                    'desc'              =>  __( 'Only display products belonging to the selected category' , 'woocommerce-wholesale-order-form' ),
                    'desc_tip'          =>  true,
                    'id'                =>  'wwof_filters_product_category_filter',
                    'class'             =>  'chosen_select',
                    'css'               =>  'min-width:300px;',
                    'custom_attributes' =>  array(
                                                'multiple'          =>  'multiple',
                                                'data-placeholder'  =>  __( 'Select Some Product Categories...' , 'woocommerce-wholesale-order-form' )
                                            ),
                    'options'           =>  $productTerms
                ),

                array(
                    'title'             =>  __( 'Exclude Product Filter' , 'woocommerce-wholesale-order-form' ),
                    'type'              =>  'multiselect',
                    'desc'              =>  __( 'Exclude selected products' , 'woocommerce-wholesale-order-form' ),
                    'desc_tip'          =>  true,
                    'id'                =>  'wwof_filters_exclude_product_filter',
                    'class'             =>  'chosen_select',
                    'css'               =>  'min-width:300px;',
                    'custom_attributes' =>  array(
                                                'multiple'          => 'multiple',
                                                'data-placeholder'  =>  __( 'Select Some Products...' , 'woocommerce-wholesale-order-form' )
                                            ),
                    'options'           =>  $allProducts
                ),

                array(
                    'title'     =>  __( 'Default Product Category on Search Filter' , 'woocommerce-wholesale-order-form' ),
                    'type'      =>  'select',
                    'desc'      =>  __( 'Select a product category to which product are under will be loaded by default in the order form.' , 'woocommerce-wholesale-order-form' ),
                    'desc_tip'  =>  true,
                    'id'        =>  'wwof_general_default_product_category_search_filter',
                    'class'     =>  'chosen_select',
                    'options'   =>  $productTerms2,
                    'default'   =>  'none'
                ),

                array(
                    'type'      =>  'sectionend',
                    'id'        =>  'wwof_filters_sectionend'
                )

            );

        }

        /**
         * Get permissions section settings.
         *
         * @return array
         * @since 1.0.0
         */
        private function _get_permissions_section_settings() {

            // Get all user roles
            global $wp_roles;

            if(!isset($wp_roles))
                $wp_roles = new WP_Roles();

            $allUserRoles = $wp_roles->get_names();

            return array(

                array(
                    'title'     =>  __( 'Permissions Options' , 'woocommerce-wholesale-order-form' ),
                    'type'      =>  'title',
                    'desc'      =>  '',
                    'id'        =>  'wwof_permissions_main_title'
                ),

                array(
                    'title'             =>  __( 'User Role Filter' , 'woocommerce-wholesale-order-form' ),
                    'type'              =>  'multiselect',
                    'desc'              =>  __( 'Only allow a given user role/s to access the wholesale page. Left blank to disable filter.' , 'woocommerce-wholesale-order-form' ),
                    'desc_tip'          =>  true,
                    'id'                =>  'wwof_permissions_user_role_filter',
                    'class'             =>  'chosen_select',
                    'css'               =>  'min-width:300px;',
                    'custom_attributes' =>  array(
                                                'multiple'          =>  'multiple',
                                                'data-placeholder'  =>  __( 'Select Some User Roles...' , 'woocommerce-wholesale-order-form' )
                                            ),
                    'options'           =>  $allUserRoles
                ),

                array(
                    'type'      =>  'sectionend',
                    'id'        =>  'wwof_permissions_role_filter_sectionend'
                ),

                array(
                    'title'     =>  __( 'Access Denied Message' , 'woocommerce-wholesale-order-form' ),
                    'type'      =>  'title',
                    'desc'      =>  __( 'Message to display to users who do not have permission to access the wholesale order form.' , 'woocommerce-wholesale-order-form' ),
                    'id'        =>  'wwof_permissions_noaccess_section_title'
                ),

                array(
                    'title'     =>  __( 'Title' , 'woocommerce-wholesale-order-form' ),
                    'type'      =>  'text',
                    'desc'      =>  __( 'Defaults to <b>"Access Denied"</b> if left blank' , 'woocommerce-wholesale-order-form' ),
                    'desc_tip'  =>  true,
                    'id'        =>  'wwof_permissions_noaccess_title',
                    'css'       =>  'min-width: 400px;'
                ),

                array(
                    'title'     =>  __( 'Message' , 'woocommerce-wholesale-order-form' ),
                    'type'      =>  'wwof_editor',
                    'desc'      =>  __( 'Defaults to <b>"You do not have permission to view wholesale product listing"</b> if left blank' , 'woocommerce-wholesale-order-form' ),
                    'desc_tip'  =>  true,
                    'id'        =>  'wwof_permissions_noaccess_message',
                    'css'       =>  'min-width: 400px; min-height: 100px;'
                ),

                array(
                    'title'     =>  __( 'Login URL' , 'woocommerce-wholesale-order-form' ),
                    'type'      =>  'text',
                    'desc'      =>  __( 'URL of the login page. Uses default WordPress login URL if left blank' , 'woocommerce-wholesale-order-form' ),
                    'desc_tip'  =>  true,
                    'id'        =>  'wwof_permissions_noaccess_login_url',
                    'css'       =>  'min-width: 400px;'
                ),

                array(
                    'type'      =>  'sectionend',
                    'id'        =>  'wwof_permissions_sectionend'
                )

            );

        }

        /**
         * Get help section settings.
         *
         * @return array
         * @since 1.0.0
         */
        private function _get_help_section_settings() {

            return array(

                array(
                    'title'     =>  __( 'Help Options' , 'woocommerce-wholesale-order-form' ),
                    'type'      =>  'title',
                    'desc'      =>  '',
                    'id'        =>  'wwof_help_main_title'
                ),

                array(
                    'name'      =>  '',
                    'type'      =>  'wwof_help_resources',
                    'desc'      =>  '',
                    'id'        =>  'wwof_help_help_resources',
                ),

                array(
                    'title'     =>  __( 'Create Wholesale Ordering Page' , 'woocommerce-wholesale-order-form' ),
                    'type'      =>  'wwof_button',
                    'desc'      =>  '',
                    'id'        =>  'wwof_help_create_wholesale_page',
                    'class'     =>  'button button-primary'
                ),

                array(
                    'name'      => __( 'Clean up plugin options on un-installation' , 'woocommerce-wholesale-prices-premium' ),
                    'type'      => 'checkbox',
                    'desc'      => __( 'If checked, removes all plugin options when this plugin is uninstalled. <b>Warning:</b> This process is irreversible.' , 'woocommerce-wholesale-prices-premium' ),
                    'id'        => 'wwof_settings_help_clean_plugin_options_on_uninstall'
                ),
                
                array(
                    'type'      =>  'sectionend',
                    'id'        =>  'wwof_help_sectionend'
                )

            );

        }




        /*
         |--------------------------------------------------------------------------------------------------------------
         | Custom Settings Fields
         |--------------------------------------------------------------------------------------------------------------
         */

        /**
         * Render custom setting field (wwof button)
         *
         * @param $value
         * @since 1.0.0
         */
        public function render_wwof_button( $value ) {

            // Change type accordingly
            $type = $value[ 'type' ];
            if ( $type == 'wwof_button' )
                $type = 'button';

            // Custom attribute handling
            $custom_attributes = array();

            if ( ! empty( $value[ 'custom_attributes' ] ) && is_array( $value[ 'custom_attributes' ] ) ) {
                foreach ( $value[ 'custom_attributes' ] as $attribute => $attribute_value ) {
                    $custom_attributes[] = esc_attr( $attribute ) . '="' . esc_attr( $attribute_value ) . '"';
                }
            }

            // Description handling
            if ( true === $value[ 'desc_tip' ] ) {

                $description = '';
                $tip = $value[ 'desc' ];

            } elseif ( ! empty( $value[ 'desc_tip' ] ) ) {

                $description = $value[ 'desc' ];
                $tip = $value[ 'desc_tip' ];

            } elseif ( ! empty( $value[ 'desc' ] ) ) {

                $description = $value[ 'desc' ];
                $tip = '';

            } else
                $description = $tip = '';

            ob_start();
            ?>
            <tr valign="top">
                <th scope="row" class="titledesc">
                    <label for="<?php echo esc_attr( $value[ 'id' ] ); ?>">
                        <?php echo esc_html( $value[ 'title' ] ); ?>
                        <?php echo $tip; ?>
                    </label>
                </th>
                <td class="forminp forminp-<?php echo sanitize_title( $value[ 'type' ] ); ?>">
                    <input
                        name="<?php echo esc_attr( $value[ 'id' ] ); ?>"
                        id="<?php echo esc_attr( $value[ 'id' ] ); ?>"
                        type="<?php echo esc_attr( $type ); ?>"
                        style="<?php echo esc_attr( $value[ 'css' ] ); ?>"
                        value="<?php echo esc_attr( __( 'Create Page' , 'woocommerce-wholesale-order-form' ) ); ?>"
                        class="<?php echo esc_attr( $value[ 'class' ] ); ?>"
                        <?php echo implode( ' ', $custom_attributes ); ?>
                        />
                    <span class="spinner" style="margin-top: 3px; float: none;"></span>
                    <?php echo $description; ?>

                </td>
            </tr>
            <?php
            echo ob_get_clean();

        }

        /**
         * Render custom setting field (wwof editor)
         *
         * @param $value
         * @since 1.1.0
         */
        public function render_wwof_editor( $value ) {

            // Custom attribute handling
            $custom_attributes = array();

            if ( ! empty( $value[ 'custom_attributes' ] ) && is_array( $value[ 'custom_attributes' ] ) ) {
                foreach ( $value[ 'custom_attributes' ] as $attribute => $attribute_value ) {
                    $custom_attributes[] = esc_attr( $attribute ) . '="' . esc_attr( $attribute_value ) . '"';
                }
            }

            // Description handling
            if ( true === $value[ 'desc_tip' ] ) {

                $description = '';
                $tip = $value[ 'desc' ];

            } elseif ( ! empty( $value[ 'desc_tip' ] ) ) {

                $description = $value[ 'desc' ];
                $tip = $value[ 'desc_tip' ];

            } elseif ( ! empty( $value[ 'desc' ] ) ) {

                $description = $value[ 'desc' ];
                $tip = '';

            } else
                $description = $tip = '';

            // Description handling
            $field_description = WC_Admin_Settings::get_field_description( $value );

            $val = get_option( 'wwof_permissions_noaccess_message' );
            if ( !$val )
                $val = '';

            ob_start(); ?>

            <tr valign="top">
                <th scope="row" class="titledesc">
                    <label for="<?php echo esc_attr( $value[ 'id' ] ); ?>">
                        <?php echo esc_html( $value[ 'title' ] ); ?>
                        <?php echo $field_description[ 'tooltip_html' ]; ?>
                    </label>
                </th>
                <td class="forminp forminp-<?php echo sanitize_title( $value[ 'type' ] ); ?>">
                    <?php
                    wp_editor( html_entity_decode( $val ) , 'wwof_permissions_noaccess_message' , array( 'wpautop' => true , 'textarea_name' => "noaccess_message[" . $value[ 'id' ] . "]" ) );
                    echo $description;
                    ?>
                </td>
            </tr>

            <?php
            echo ob_get_clean();

        }

        public function render_wwof_help_resources( $value ) {
            ?>

            <tr valign="top">
                <th scope="row" class="titledesc">
                    <label for=""><?php _e( 'Knowledge Base' , 'woocommerce-wholesale-order-form' ); ?></label>
                </th>
                <td class="forminp forminp-<?php echo sanitize_title( $value[ 'type' ] ); ?>">
                    <?php echo sprintf( __( 'Looking for documentation? Please see our growing <a href="%1$s" target="_blank">Knowledge Base</a>' , 'woocommerce-wholesale-order-form' ) , "https://wholesalesuiteplugin.com/knowledge-base/?utm_source=Order%20Form%20Plugin&utm_medium=Settings&utm_campaign=Knowledge%20Base%20" ); ?>
                </td>
            </tr>

            <?php
        }

        /**
         * Render custom image dimension setting
         *
         * @param $value
         * @since 1.6.0
         */
        public function render_wwof_image_dimension( $value ){

            $field_description = WC_Admin_Settings::get_field_description( $value );
            $imageSize = get_option( 'wwof_general_product_thumbnail_image_size' );

            extract( $field_description );

            $width      = isset( $imageSize ) && ! empty( $imageSize[ 'width' ] ) ? $imageSize[ 'width' ] : $value[ 'default' ][ 'width' ];
            $height     = isset( $imageSize ) && ! empty( $imageSize[ 'height' ] ) ? $imageSize[ 'height' ] : $value[ 'default' ][ 'height' ]; ?>

            <tr valign="top">
                <th scope="row" class="titledesc">
                    <label for="<?php echo esc_attr( $value['id'] ); ?>">
                        <?php echo esc_html( $value['title'] ); ?>
                        <?php echo $tooltip_html; ?>
                    </label>
                </th>
                <td class="forminp image_width_settings">
                    <input name="<?php echo esc_attr( $value['id'] ); ?>[width]" id="<?php echo esc_attr( $value['id'] ); ?>-width" type="text" size="3" value="<?php echo $width; ?>" /> &times; <input name="<?php echo esc_attr( $value['id'] ); ?>[height]" id="<?php echo esc_attr( $value['id'] ); ?>-height" type="text" size="3" value="<?php echo $height; ?>" />px
                </td>
            </tr><?php

        }
    }

}

return new WWOF_Settings();

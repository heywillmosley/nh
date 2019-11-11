<?php

    /**
     * For full documentation, please visit: http://docs.reduxframework.com/
     * For a more extensive sample-config file, you may look at:
     * https://github.com/reduxframework/redux-framework/blob/master/sample/sample-config.php
     */

    if ( ! class_exists( 'Redux' ) ) {
        return;
    }

    // This is your option name where all the Redux data is stored.
    $opt_name = "woocommerce_print_products_options";

    // Get Custom Meta Keys for product
    global $wpdb;
    $sql = "SELECT DISTINCT meta_key
                    FROM " . $wpdb->postmeta . "
                    INNER JOIN  " . $wpdb->posts . " 
                    ON post_id = ID
                    WHERE post_type = 'product'
                    ORDER BY meta_key ASC";

    $meta_keys = $wpdb->get_results( $sql, 'ARRAY_A' );
    $meta_keys_to_exclude = array('_crosssell_ids', '_children', '_default_attributes', '_height', '_length', '_max_price_variation_id', '_max_regular_price_variation_id', '_max_sale_price_variation_id', '_max_variation_price', '_max_variation_regular_price', '_max_variation_sale_price', '_min_price_variation_id', '_min_regular_price_variation_id', '_min_sale_price_variation_id', '_min_variation_price', '_min_variation_regular_price', '_min_variation_sale_price', '_price', '_product_attributes', '_product_image_gallery', '_sku', '_regular_price', '_sale_price', '_sale_price_dates_from', '_sale_price_dates_to', '_sku', '_upsell_ids', '_thumbnail_id', '_weight', '_width');

    $temp = array(
                array(
                    'id'       => 'exclusions',
                    'type'     => 'section',
                    'title'    => __( 'Custom Post Fields', 'woocommerce-print-products' ),
                    'subtitle' => __( 'With the below settings you can show custom post meta keys for the products.', 'woocommerce-print-products' ),
                    'indent'   => false,
                ),
    );

    foreach ($meta_keys as $key => $meta_key) {
        $meta_key = preg_replace('/[^\w-]/', '', $meta_key['meta_key']);

        if(in_array($meta_key, $meta_keys_to_exclude) || (substr( $meta_key, 0, 7 ) === "_oembed")) {
            continue;
        }
        $temp[$meta_key] = $meta_key;

        $temp[] = 
            array(
                'id'       => 'showCustomMetaKey_' . $meta_key,
                'type'     => 'checkbox',
                'title'    => __( 'Show Custom Meta Key ' . $meta_key, 'woocommerce-print-products' ),
                'default'   => 0,
            );

        $temp[] = 
            array(
                'id'       => 'showCustomMetaKeyText_' . $meta_key,
                'type'     => 'text',
                'title'    => __( 'Text before Custom Meta Key ' . $meta_key, 'woocommerce-print-products' ),
                'default'   => $meta_key,
                'required' => array('showCustomMetaKey_' . $meta_key, 'equals' , '1'),
            );
    }

    /**
     * ---> SET ARGUMENTS
     * All the possible arguments for Redux.
     * For full documentation on arguments, please refer to: https://github.com/ReduxFramework/ReduxFramework/wiki/Arguments
     * */

    $theme = wp_get_theme(); // For use with some settings. Not necessary.

    $args = array(
        'opt_name' => 'woocommerce_print_products_options',
        'use_cdn' => TRUE,
        'dev_mode' => FALSE,
        'display_name' => 'WooCommerce Print Products',
        'display_version' => '1.3.6',
        'page_title' => 'WooCommerce Print Products',
        'update_notice' => TRUE,
        'intro_text' => '',
        'footer_text' => '&copy; '.date('Y').' DB-Dzine',
        'admin_bar' => TRUE,
        'menu_type' => 'submenu',
        'menu_title' => 'Print Products',
        'allow_sub_menu' => TRUE,
        'page_parent' => 'woocommerce',
        'page_parent_post_type' => 'your_post_type',
        'customizer' => FALSE,
        'default_mark' => '*',
        'hints' => array(
            'icon_position' => 'right',
            'icon_color' => 'lightgray',
            'icon_size' => 'normal',
            'tip_style' => array(
                'color' => 'light',
            ),
            'tip_position' => array(
                'my' => 'top left',
                'at' => 'bottom right',
            ),
            'tip_effect' => array(
                'show' => array(
                    'duration' => '500',
                    'event' => 'mouseover',
                ),
                'hide' => array(
                    'duration' => '500',
                    'event' => 'mouseleave unfocus',
                ),
            ),
        ),
        'output' => TRUE,
        'output_tag' => TRUE,
        'settings_api' => TRUE,
        'cdn_check_time' => '1440',
        'compiler' => TRUE,
        'page_permissions' => 'manage_options',
        'save_defaults' => TRUE,
        'show_import_export' => TRUE,
        'database' => 'options',
        'transient_time' => '3600',
        'network_sites' => TRUE,
    );

    Redux::setArgs( $opt_name, $args );

    /*
     * ---> END ARGUMENTS
     */

    /*
     * ---> START HELP TABS
     */

    $tabs = array(
        array(
            'id'      => 'help-tab',
            'title'   => __( 'Information', 'woocommerce-print-products' ),
            'content' => __( '<p>Need support? Please use the comment function on codecanyon.</p>', 'woocommerce-print-products' )
        ),
    );
    Redux::setHelpTab( $opt_name, $tabs );

    // Set the help sidebar
    // $content = __( '<p>This is the sidebar content, HTML is allowed.</p>', 'woocommerce-print-products' );
    // Redux::setHelpSidebar( $opt_name, $content );


    /*
     * <--- END HELP TABS
     */


    /*
     *
     * ---> START SECTIONS
     *
     */

    Redux::setSection( $opt_name, array(
        'title'  => __( 'Print Products', 'woocommerce-print-products' ),
        'id'     => 'general',
        'desc'   => __( 'Need support? Please use the comment function on codecanyon.', 'woocommerce-print-products' ),
        'icon'   => 'el el-home',
    ) );

    Redux::setSection( $opt_name, array(
        'title'      => __( 'General', 'woocommerce-print-products' ),
        // 'desc'       => __( '', 'woocommerce-print-products' ),
        'id'         => 'general-settings',
        'subsection' => true,
        'fields'     => array(
            array(
                'id'       => 'enable',
                'type'     => 'checkbox',
                'title'    => __( 'Enable', 'woocommerce-print-products' ),
                'subtitle' => __( 'Enable Print Products to use the options below', 'woocommerce-print-products' ),
                'default' => 1,
            ),
            array(
                'id'       => 'enablePDF',
                'type'     => 'checkbox',
                'title'    => __( 'Enable PDF', 'woocommerce-print-products' ),
                'default' => 1,
            ),
            array(
                'id'       => 'enableWord',
                'type'     => 'checkbox',
                'title'    => __( 'Enable Word', 'woocommerce-print-products' ),
                'default' => 1,
            ),
            array(
                'id'       => 'enablePrint',
                'type'     => 'checkbox',
                'title'    => __( 'Enable Print', 'woocommerce-print-products' ),
                'default' => 1,
            ),
            array(
                'id'       => 'iconPosition',
                'type'     => 'select',
                'title'    => __( 'Icon position', 'woocommerce-print-products' ),
                'subtitle' => __( 'Choose the position of the icons on the product page.', 'woocommerce-print-products' ),
                'options'  => array(
                    'woocommerce_product_meta_start' => __('Meta start', 'woocommerce-print-products'),
                    'woocommerce_product_meta_end' => __('Meta End', 'woocommerce-print-products'),
                    'woocommerce_before_single_product_summary' => __('Before Product Summary', 'woocommerce-print-products'),
                    'woocommerce_after_single_product_summary' => __('After Product Summary', 'woocommerce-print-products'),
                ),
                'default' => 'woocommerce_product_meta_end',
            ),
            array(
                'id'       => 'iconSize',
                'type'     => 'select',
                'title'    => __( 'Icon Size', 'woocommerce-print-products' ),
                'subtitle' => __( 'Choose the icon size.', 'woocommerce-print-products' ),
                'options'  => array(
                    'fa-lg' => __('Large', 'woocommerce-print-products' ),
                    'fa-2x' => __('2x larger', 'woocommerce-print-products' ),
                    'fa-3x' => __('3x larger', 'woocommerce-print-products' ),
                    'fa-4x' => __('4x larger', 'woocommerce-print-products' ),
                    'fa-5x' => __('5x larger', 'woocommerce-print-products' ),
                    //'productAttribute' => __('Show best Selling Products', 'woocommerce-custom-tabs' ),
                ),
                 'default' => 'fa-2x',
            ),
            array(
                'id'       => 'iconDisplay',
                'type'     => 'select',
                'title'    => __( 'Icon Display', 'woocommerce-print-products' ),
                'subtitle' => __( 'Choose how the icons should appear.', 'woocommerce-print-products' ),
                'options'  => array(
                    'horizontal' => __('Horizontal', 'woocommerce-print-products' ),
                    'vertical' => __('Vertical', 'woocommerce-print-products' ),
                ),
                'default' => 'horizontal'
            ),
            array(
                'id'     =>'excludeProductCategories',
                'type' => 'select',
                'data' => 'categories',
                'args' => array('taxonomy' => array('product_cat')),
                'multi' => true,
                'title' => __('Exclude Product Categories', 'woocommerce-print-products'), 
                'subtitle' => __('Which product categories should be excluded by the catalog mode.', 'woocommerce-print-products'),
            ),            
            array(
                'id'       => 'excludeProductCategoriesRevert',
                'type'     => 'checkbox',
                'title'    => __( 'Revert Categories Exclusion', 'woocommerce-print-products' ),
                'subtitle' => __( 'Instead of exclusion it will include.', 'woocommerce-print-products' ),
            ),
            array(
                'id'     =>'excludeProducts',
                'type' => 'select',
                'data' => 'posts',
                'args' => array('post_type' => array('product'), 
                'posts_per_page' => -1),
                'multi' => true,
                'title' => __('Exclude Products', 'woocommerce-print-products'), 
                'subtitle' => __('Which products should be excluded by the catalog mode.', 'woocommerce-print-products'),
            ),
            array(
                'id'       => 'excludeProductsRevert',
                'type'     => 'checkbox',
                'title'    => __( 'Revert Products Exclusion', 'woocommerce-print-products' ),
                'subtitle' => __( 'Instead of exclusion it will include.', 'woocommerce-print-products' ),
            ),
        )
    ) );

    Redux::setSection( $opt_name, array(
        'title'      => __( 'Header', 'woocommerce-print-products' ),
        // 'desc'       => __( '', 'woocommerce-print-products' ),
        'id'         => 'header',
        'subsection' => true,
        'fields'     => array(
            array(
                'id'       => 'enableHeader',
                'type'     => 'checkbox',
                'title'    => __( 'Enable', 'woocommerce-print-products' ),
                'subtitle' => __( 'Enable header', 'woocommerce-print-products' ),
                // 'desc'     => __( 'Field Description', 'woocommerce-print-products' ),
            ),
            array(
                'id'     =>'headerBackgroundColor',
                'type' => 'color',
                'title' => __('Header background color', 'woocommerce-print-products'), 
                'validate' => 'color',
                'required' => array('enableHeader','equals','1'),
            ),
            array(
                'id'     =>'headerTextColor',
                'type'  => 'color',
                'title' => __('Header text color', 'woocommerce-print-products'), 
                'validate' => 'color',
                'required' => array('enableHeader','equals','1'),
            ),
            array(
                'id'     =>'headerLayout',
                'type'  => 'select',
                'title' => __('Header Layout', 'woocommerce-print-products'), 
                'required' => array('enableHeader','equals','1'),
                'options'  => array(
                    'oneCol' => __('1/1', 'woocommerce-print-products' ),
                    'twoCols' => __('1/2 + 1/2', 'woocommerce-print-products' ),
                    'threeCols' => __('1/3 + 1/3 + 1/3', 'woocommerce-print-products' ),
                ),
                'default' => 'twoCols',
            ),
            array(
                'id'     =>'headerTopMargin',
                'type'     => 'spinner', 
                'title'    => __('Header Margin', 'woocommerce-print-products'),
                'default'  => '30',
                'min'      => '1',
                'step'     => '1',
                'max'      => '200',
            ),
            array(
                'id'     =>'headerHeight',
                'type'     => 'spinner', 
                'title'    => __('Header Height', 'woocommerce-print-products'),
                'default'  => '40',
                'min'      => '1',
                'step'     => '1',
                'max'      => '200',
            ),
            array(
                'id'     =>'headerVAlign',
                'type'  => 'select',
                'title' => __('Vertical Align', 'woocommerce-print-products'), 
                'required' => array('enableHeader','equals','1'),
                'options'  => array(
                    'top' => __('Top', 'woocommerce-print-products' ),
                    'middle' => __('Middle', 'woocommerce-print-products' ),
                    'bottom' => __('Bottom', 'woocommerce-print-products' ),
                ),
                'default' => 'middle',
            ),
            array(
                'id'     =>'headerTopLeft',
                'type'  => 'select',
                'title' => __('Top Left Header', 'woocommerce-print-products'), 
                'required' => array('enableHeader','equals','1'),
                'options'  => array(
                    'none' => __('None', 'woocommerce-print-products' ),
                    'bloginfo' => __('Blog information', 'woocommerce-print-products' ),
                    'text' => __('Custom text', 'woocommerce-print-products' ),
                    'pagenumber' => __('Pagenumber', 'woocommerce-print-products' ),
                    'productinfo' => __('Product info', 'woocommerce-print-products' ),
                    'image' => __('Image', 'woocommerce-print-products' ),
                    'exportinfo' => __('Export Information', 'woocommerce-print-products' ),
                    'qr' => __('QR-Code', 'woocommerce-print-products' ),
                ),
            ),
            array(
                'id'     =>'headerTopLeftText',
                'type'  => 'editor',
                'title' => __('Top Left Header Text', 'woocommerce-print-products'), 
                'required' => array('headerTopLeft','equals','text'),
                'args'   => array(
                    'teeny'            => false,
                )
            ),
            array(
                'id'     =>'headerTopLeftImage',
                'type' => 'media',
                'url'      => true,
                'title' => __('Top Left Header Image', 'woocommerce-print-products'), 
                'required' => array('headerTopLeft','equals','image'),
                'args'   => array(
                    'teeny'            => false,
                )
            ),
            array(
                'id'     =>'headerTopMiddle',
                'type'  => 'select',
                'title' => __('Top Middle Header', 'woocommerce-print-products'), 
                'required' => array('headerLayout','equals','threeCols'),
                'options'  => array(
                    'none' => __('None', 'woocommerce-print-products' ),
                    'bloginfo' => __('Blog information', 'woocommerce-print-products' ),
                    'text' => __('Custom text', 'woocommerce-print-products' ),
                    'pagenumber' => __('Pagenumber', 'woocommerce-print-products' ),
                    'productinfo' => __('Product info', 'woocommerce-print-products' ),
                    'image' => __('Image', 'woocommerce-print-products' ),
                    'exportinfo' => __('Export Information', 'woocommerce-print-products' ),
                    'qr' => __('QR-Code', 'woocommerce-print-products' ),
                ),
            ),
            array(
                'id'     =>'headerTopMiddleText',
                'type'  => 'editor',
                'title' => __('Top Middle Header Text', 'woocommerce-print-products'), 
                'required' => array('headerTopMiddle','equals','text'),
                'args'   => array(
                    'teeny'            => false,
                )
            ),
            array(
                'id'     =>'headerTopMiddleImage',
                'type' => 'media',
                'url'      => true,
                'title' => __('Top Middle Header Image', 'woocommerce-print-products'), 
                'required' => array('headerTopMiddle','equals','image'),
                'args'   => array(
                    'teeny'            => false,
                )
            ),
            array(
                'id'     =>'headerTopRight',
                'type'  => 'select',
                'title' => __('Top Right Header', 'woocommerce-print-products'), 
                'required' => array('headerLayout','equals',array('threeCols','twoCols')),
                'options'  => array(
                    'none' => __('None', 'woocommerce-print-products' ),
                    'bloginfo' => __('Blog information', 'woocommerce-print-products' ),
                    'text' => __('Custom text', 'woocommerce-print-products' ),
                    'pagenumber' => __('Pagenumber', 'woocommerce-print-products' ),
                    'productinfo' => __('Product info', 'woocommerce-print-products' ),
                    'image' => __('Image', 'woocommerce-print-products' ),
                    'exportinfo' => __('Export Information', 'woocommerce-print-products' ),
                    'qr' => __('QR-Code', 'woocommerce-print-products' ),
                ),
            ),
            array(
                'id'     =>'headerTopRightText',
                'type'  => 'editor',
                'title' => __('Top Right Header Text', 'woocommerce-print-products'), 
                'required' => array('headerTopRight','equals','text'),
                'args'   => array(
                    'teeny'            => false,
                )
            ),
            array(
                'id'     =>'headerTopRightImage',
                'type' => 'media',
                'url'      => true,
                'title' => __('Top Right Header Image', 'woocommerce-print-products'), 
                'required' => array('headerTopRight','equals','image'),
                'args'   => array(
                    'teeny'            => false,
                )
            ),
            array(
                'id'     =>'headerTextAfterHeader',
                'type'  => 'editor',
                'title' => __('Text after Header', 'woocommerce-print-products'),
                'args'   => array(
                    'teeny'            => false,
                )
            ),
        )
    ) );

    Redux::setSection( $opt_name, array(
        'title'      => __( 'Layout', 'woocommerce-print-products' ),
        'id'         => 'layout',
        'subsection' => true,
        'fields'     => array(
            array(
                'id'       => 'layout',
                'type'     => 'image_select',
                'title'    => __( 'Select Layout', 'woocommerce-print-products' ),
                'options'  => array(
                    '1'      => array(
                        'img'   => plugin_dir_url( __FILE__ ) . 'img/1.png'
                    ),
                    '2'      => array(
                        'img'   => plugin_dir_url( __FILE__ ). 'img/2.png'
                    ),
                    '3'      => array(
                        'img'   => plugin_dir_url( __FILE__ ). 'img/3.png'
                    ),
                ),
                'default' => '2'
            ),
            array(
                'id'      => 'informationOrder',
                'type'    => 'sorter',
                'title'   => 'Reorder / Disable some blocks.',
                'options' => array(
                    'enabled'  => array(
                        'variations' => 'Variations',
                        'gallery_images' => 'Gallery Images',
                        'description' => 'Description',
                        'pagebreak-1' => 'Pagebreak',
                        'attributes_table' => 'Attributes',
                        'pagebreak-2' => 'Pagebreak',
                        'reviews' => 'Reviews',
                        'pagebreak-3' => 'Pagebreak',
                        'upsells' => 'Upsells',
                    ),
                    'disabled' => array(
                        'pagebreak-4' => 'Pagebreak',
                        'pagebreak-5' => 'Pagebreak',
                        'pagebreak-6' => 'Pagebreak',
                        'pagebreak-7' => 'Pagebreak',
                    )
                ),
            ),
            array(
                'id'     =>'textAlign',
                'type'  => 'select',
                'title' => __('Text Align', 'woocommerce-print-products'), 
                'options'  => array(
                    'left' => __('Left', 'woocommerce-print-products' ),
                    'center' => __('Center', 'woocommerce-print-products' ),
                    'right' => __('Right', 'woocommerce-print-products' ),
                ),
                'default' => 'center'
            ),
            array(
                'id'     =>'backgroundColor',
                'type'  => 'color',
                'title' => __('Background color', 'woocommerce-print-products'), 
                'validate' => 'color',
            ),
            array(
                'id'     =>'textColor',
                'type'  => 'color',
                'title' => __('Text Color', 'woocommerce-print-products'), 
                'validate' => 'color',
            ),
            array(
                'id'     =>'linkColor',
                'type'  => 'color',
                'title' => __('Link Color', 'woocommerce-print-products'), 
                'validate' => 'color',
            ),
            array(
                'id'     =>'fontFamily',
                'type'  => 'select',
                'title' => __('Default Font', 'woocommerce-print-products'), 
                'options'  => array(
                    'dejavusans' => __('Sans', 'woocommerce-print-products' ),
                    'dejavuserif' => __('Serif', 'woocommerce-print-products' ),
                    'dejavusansmono' => __('Mono', 'woocommerce-print-products' ),
                    'droidsans' => __('Droid Sans', 'woocommerce-print-products'),
                    'droidserif' => __('Droid Serif', 'woocommerce-print-products'),
                    'lato' => __('Lato', 'woocommerce-print-products'),
                    'lora' => __('Lora', 'woocommerce-print-products'),
                    'merriweather' => __('Merriweather', 'woocommerce-print-products'),
                    'montserrat' => __('Montserrat', 'woocommerce-print-products'),
                    'opensans' => __('Open sans', 'woocommerce-print-products'),
                    'opensanscondensed' => __('Open Sans Condensed', 'woocommerce-print-products'),
                    'oswald' => __('Oswald', 'woocommerce-print-products'),
                    'ptsans' => __('PT Sans', 'woocommerce-print-products'),
                    'sourcesanspro' => __('Source Sans Pro', 'woocommerce-print-products'),
                    'slabo' => __('Slabo', 'woocommerce-print-products'),
                    'raleway' => __('Raleway', 'woocommerce-print-products'),
                ),
            ),
            array(
                'id'     =>'fontSize',
                'type'     => 'spinner', 
                'title'    => __('Default font size', 'woocommerce-print-products'),
                'default'  => '11',
                'min'      => '1',
                'step'     => '1',
                'max'      => '40',
            ),
            array(
                'id'     =>'fontLineHeight',
                'type'     => 'spinner', 
                'title'    => __('Default line height', 'woocommerce-print-products'),
                'default'  => '16',
                'min'      => '1',
                'step'     => '1',
                'max'      => '40',
            ),
            array(
                'id'     =>'headingsFontFamily',
                'type'  => 'select',
                'title' => __('Headings Font', 'woocommerce-print-products'), 
                'options'  => array(
                    'dejavusans' => __('Sans', 'woocommerce-print-products' ),
                    'dejavuserif' => __('Serif', 'woocommerce-print-products' ),
                    'dejavusansmono' => __('Mono', 'woocommerce-print-products' ),
                    'droidsans' => __('Droid Sans', 'woocommerce-print-products'),
                    'droidserif' => __('Droid Serif', 'woocommerce-print-products'),
                    'lato' => __('Lato', 'woocommerce-print-products'),
                    'lora' => __('Lora', 'woocommerce-print-products'),
                    'merriweather' => __('Merriweather', 'woocommerce-print-products'),
                    'montserrat' => __('Montserrat', 'woocommerce-print-products'),
                    'opensans' => __('Open sans', 'woocommerce-print-products'),
                    'opensanscondensed' => __('Open Sans Condensed', 'woocommerce-print-products'),
                    'oswald' => __('Oswald', 'woocommerce-print-products'),
                    'ptsans' => __('PT Sans', 'woocommerce-print-products'),
                    'sourcesanspro' => __('Source Sans Pro', 'woocommerce-print-products'),
                    'slabo' => __('Slabo', 'woocommerce-print-products'),
                    'raleway' => __('Raleway', 'woocommerce-print-products'),
                ),
            ),
            array(
                'id'     =>'headingsFontSize',
                'type'     => 'spinner', 
                'title'    => __('Headings font size', 'woocommerce-print-products'),
                'default'  => '16',
                'min'      => '1',
                'step'     => '1',
                'max'      => '100',
            ),
            array(
                'id'     =>'headingsLineHeight',
                'type'     => 'spinner', 
                'title'    => __('Headings line height', 'woocommerce-print-products'),
                'default'  => '22',
                'min'      => '1',
                'step'     => '1',
                'max'      => '100',
            ),

        )
    ) );

    $dataToShow = 
        array(
            array(
                'id'       => 'showImage',
                'type'     => 'checkbox',
                'title'    => __( 'Show Product Image', 'woocommerce-print-products' ),
                'default'   => 1,
            ),
                array(
                    'id'     =>'showImageSize',
                    'type'     => 'spinner', 
                    'title'    => __('Product Image Size', 'woocommerce-print-products'),
                    'default'  => '350',
                    'min'      => '1',
                    'step'     => '1',
                    'max'      => '99999',
                    'required' => array('showImage','equals','1'),
                ),
            array(
                'id'       => 'showGalleryImages',
                'type'     => 'checkbox',
                'title'    => __( 'Show Gallery Images', 'woocommerce-print-products' ),
                'default'   => 1,
            ),
                array(
                    'id'     =>'showGalleryImagesSize',
                    'type'     => 'spinner', 
                    'title'    => __('Gallery Image Size', 'woocommerce-print-products'),
                    'default'  => '200',
                    'min'      => '1',
                    'step'     => '1',
                    'max'      => '99999',
                    'required' => array('showGalleryImages','equals','1'),
                ),
                array(
                    'id'     =>'showGalleryImagesColumns',
                    'type'     => 'spinner', 
                    'title'    => __('Gallery Image Columns', 'woocommerce-print-products'),
                    'default'  => '3',
                    'min'      => '1',
                    'step'     => '1',
                    'max'      => '6',
                    'required' => array('showGalleryImages','equals','1'),
                ),
            array(
                'id'       => 'showGalleryImagesTitle',
                'type'     => 'checkbox',
                'title'    => __( 'Show Gallery Images Title', 'woocommerce-print-products' ),
                'default'   => 0,
            ),
            array(
                'id'       => 'showGalleryImagesCaption',
                'type'     => 'checkbox',
                'title'    => __( 'Show Gallery Images Caption', 'woocommerce-print-products' ),
                'default'   => 0,
            ),
            array(
                'id'       => 'showGalleryImagesAlt',
                'type'     => 'checkbox',
                'title'    => __( 'Show Gallery Images Alt Text', 'woocommerce-print-products' ),
                'default'   => 0,
            ),
            array(
                'id'       => 'showGalleryImagesDescription',
                'type'     => 'checkbox',
                'title'    => __( 'Show Gallery Images Description', 'woocommerce-print-products' ),
                'default'   => 0,
            ),
            array(
                'id'       => 'showTitle',
                'type'     => 'checkbox',
                'title'    => __( 'Show Product Title', 'woocommerce-print-products' ),
                'default'   => 1,
            ),
            array(
                'id'       => 'showPrice',
                'type'     => 'checkbox',
                'title'    => __( 'Show Product Price', 'woocommerce-print-products' ),
                'default'   => 1,
            ),
            array(
                'id'       => 'showShortDescription',
                'type'     => 'checkbox',
                'title'    => __( 'Show Short Description', 'woocommerce-print-products' ),
                'default'   => 1,
            ),
                array(
                    'id'       => 'showShortDescriptionStripImages',
                    'type'     => 'checkbox',
                    'title'    => __( 'Strip Short Description Images?', 'woocommerce-print-products' ),
                    'default'   => 0,
                    'required' => array('showShortDescription','equals','1'),
                ),
            array(
                'id'       => 'showMetaFreetext',
                'type'     => 'checkbox',
                'title'    => __( 'Show Meta Free Text', 'woocommerce-print-products' ),
                'default'   => 0,
            ),
                array(
                    'id'       => 'metaFreeText',
                    'type'  => 'editor',
                    'title' => __('Meta Free Text', 'woocommerce-print-products'),
                    'args'   => array(
                        'teeny'            => false,
                    ),
                    'required' => array('showMetaFreetext','equals','1'),
                ),
            array(
                'id'       => 'showSKU',
                'type'     => 'checkbox',
                'title'    => __( 'Show Product SKU', 'woocommerce-print-products' ),
                'default'   => 1,
            ),
            array(
                'id'       => 'showCategories',
                'type'     => 'checkbox',
                'title'    => __( 'Show Product Categories', 'woocommerce-print-products' ),
                'default'   => 1,
            ),
            array(
                'id'       => 'showTags',
                'type'     => 'checkbox',
                'title'    => __( 'Show Product Tags', 'woocommerce-print-products' ),
                'default'   => 1,
            ),
            array(
                'id'       => 'showQR',
                'type'     => 'checkbox',
                'title'    => __( 'Show QR-Code', 'woocommerce-print-products' ),
                'default'   => 1,
            ),
            array(
                'id'       => 'showDescription',
                'type'     => 'checkbox',
                'title'    => __( 'Show Product Description', 'woocommerce-print-products' ),
                'default'   => 1,
            ),
                array(
                    'id'       => 'showDescriptionStripImages',
                    'type'     => 'checkbox',
                    'title'    => __( 'Strip Description Images?', 'woocommerce-print-products' ),
                    'default'   => 0,
                    'required' => array('showDescription','equals','1'),
                ),
                array(
                    'id'       => 'showDescriptionDoShortcodes',
                    'type'     => 'checkbox',
                    'title'    => __( 'Try executing shortcodes in description', 'woocommerce-print-products' ),
                    'default'   => 1,
                    'required' => array('showDescription','equals','1'),
                ),
            
            array(
                'id'       => 'showAttributes',
                'type'     => 'checkbox',
                'title'    => __( 'Show Product Attributes', 'woocommerce-print-products' ),
                'default'   => 1,
            ),
            array(
                'id'       => 'showReviews',
                'type'     => 'checkbox',
                'title'    => __( 'Show Product Reviews', 'woocommerce-print-products' ),
                'default'   => 1,
            ),
            array(
                'id'       => 'showUpsells',
                'type'     => 'checkbox',
                'title'    => __( 'Show Product Upsells', 'woocommerce-print-products' ),
                'default'   => 1,
            ),
            array(
                'id'       => 'showVariationImage',
                'type'     => 'checkbox',
                'title'    => __( 'Show Variation Image', 'woocommerce-print-products' ),
                'default'   => 1,
            ),
            array(
                'id'       => 'showVariationSKU',
                'type'     => 'checkbox',
                'title'    => __( 'Show Variation SKU', 'woocommerce-print-products' ),
                'default'   => 1,
            ),
            array(
                'id'       => 'showVariationPrice',
                'type'     => 'checkbox',
                'title'    => __( 'Show Variation Price', 'woocommerce-print-products' ),
                'default'   => 1,
            ),
            array(
                'id'       => 'showVariationDescription',
                'type'     => 'checkbox',
                'title'    => __( 'Show Variation Description', 'woocommerce-print-products' ),
                'default'   => 1,
            ),
            array(
                'id'       => 'showVariationAttributes',
                'type'     => 'checkbox',
                'title'    => __( 'Show Variation Attributes', 'woocommerce-print-products' ),
                'default'   => 1,
            ),
        );
    
    $dataToShow = array_merge($dataToShow, $temp);
    

    Redux::setSection( $opt_name, array(
        'title'      => __( 'Data to show', 'woocommerce-print-products' ),
        'id'         => 'data',
        'subsection' => true,
        'fields'     => $dataToShow
    ) );

    Redux::setSection( $opt_name, array(
        'title'      => __( 'Footer', 'woocommerce-print-products' ),
        // 'desc'       => __( '', 'woocommerce-print-products' ),
        'id'         => 'footer',
        'subsection' => true,
        'fields'     => array(
            array(
                'id'       => 'enableFooter',
                'type'     => 'checkbox',
                'title'    => __( 'Enable', 'woocommerce-print-products' ),
                'subtitle' => __( 'Enable footer', 'woocommerce-print-products' ),
                // 'desc'     => __( 'Field Description', 'woocommerce-print-products' ),
            ),
            array(
                'id'     =>'footerBackgroundColor',
                'type' => 'color',
                'url'      => true,
                'title' => __('Footer background color', 'woocommerce-print-products'), 
                'validate' => 'color',
                'required' => array('enableFooter','equals','1'),
            ),
            array(
                'id'     =>'footerTextColor',
                'type'  => 'color',
                'url'      => true,
                'title' => __('Footer text color', 'woocommerce-print-products'), 
                'validate' => 'color',
                'required' => array('enableFooter','equals','1'),
            ),
            array(
                'id'     =>'footerLayout',
                'type'  => 'select',
                'title' => __('Footer Layout', 'woocommerce-print-products'), 
                'required' => array('enableFooter','equals','1'),
                'options'  => array(
                    'oneCol' => __('1/1', 'woocommerce-print-products' ),
                    'twoCols' => __('1/2 + 1/2', 'woocommerce-print-products' ),
                    'threeCols' => __('1/3 + 1/3 + 1/3', 'woocommerce-print-products' ),
                ),
                'default' => 'twoCols',
            ),
            array(
                'id'     =>'footerTopMargin',
                'type'     => 'spinner', 
                'title'    => __('Footer Margin', 'woocommerce-print-products'),
                'default'  => '55',
                'min'      => '1',
                'step'     => '1',
                'max'      => '200',
            ),
            array(
                'id'     =>'footerHeight',
                'type'     => 'spinner', 
                'title'    => __('Footer Height', 'woocommerce-print-products'),
                'default'  => '40',
                'min'      => '1',
                'step'     => '1',
                'max'      => '200',
            ),
            array(
                'id'     =>'footerVAlign',
                'type'  => 'select',
                'title' => __('Vertical Align', 'woocommerce-print-products'), 
                'required' => array('enableFooter','equals','1'),
                'options'  => array(
                    'top' => __('Top', 'woocommerce-print-products' ),
                    'middle' => __('Middle', 'woocommerce-print-products' ),
                    'bottom' => __('Bottom', 'woocommerce-print-products' ),
                ),
                'default' => 'middle',
            ),
            array(
                'id'     =>'footerTopLeft',
                'type'  => 'select',
                'title' => __('Top Left Footer', 'woocommerce-print-products'), 
                'required' => array('enableFooter','equals','1'),
                'options'  => array(
                    'none' => __('None', 'woocommerce-print-products' ),
                    'bloginfo' => __('Blog information', 'woocommerce-print-products' ),
                    'text' => __('Custom text', 'woocommerce-print-products' ),
                    'pagenumber' => __('Pagenumber', 'woocommerce-print-products' ),
                    'productinfo' => __('Product info', 'woocommerce-print-products' ),
                    'image' => __('Image', 'woocommerce-print-products' ),
                    'exportinfo' => __('Export Information', 'woocommerce-print-products' ),
                    'qr' => __('QR-Code', 'woocommerce-print-products' ),
                ),
            ),
            array(
                'id'     =>'footerTopLeftText',
                'type'  => 'editor',
                'title' => __('Top Left Footer Text', 'woocommerce-print-products'), 
                'required' => array('footerTopLeft','equals','text'),
                'args'   => array(
                    'teeny'            => false,
                )
            ),
            array(
                'id'     =>'footerTopLeftImage',
                'type' => 'media',
                'url'      => true,
                'title' => __('Top Left Footer Image', 'woocommerce-print-products'), 
                'required' => array('footerTopLeft','equals','image'),
                'args'   => array(
                    'teeny'            => false,
                )
            ),
            array(
                'id'     =>'footerTopMiddle',
                'type'  => 'select',
                'title' => __('Top Middle Footer', 'woocommerce-print-products'), 
                'required' => array('footerLayout','equals','threeCols'),
                'options'  => array(
                    'none' => __('None', 'woocommerce-print-products' ),
                    'bloginfo' => __('Blog information', 'woocommerce-print-products' ),
                    'text' => __('Custom text', 'woocommerce-print-products' ),
                    'pagenumber' => __('Pagenumber', 'woocommerce-print-products' ),
                    'productinfo' => __('Product info', 'woocommerce-print-products' ),
                    'image' => __('Image', 'woocommerce-print-products' ),
                    'exportinfo' => __('Export Information', 'woocommerce-print-products' ),
                    'qr' => __('QR-Code', 'woocommerce-print-products' ),
                ),
            ),
            array(
                'id'     =>'footerTopMiddleText',
                'type'  => 'editor',
                'title' => __('Top Middle Footer Text', 'woocommerce-print-products'), 
                'required' => array('footerTopMiddle','equals','text'),
                'args'   => array(
                    'teeny'            => false,
                )
            ),
            array(
                'id'     =>'footerTopMiddleImage',
                'type' => 'media',
                'url'      => true,
                'title' => __('Top Middle Footer Image', 'woocommerce-print-products'), 
                'required' => array('footerTopMiddle','equals','image'),
                'args'   => array(
                    'teeny'            => false,
                )
            ),
            array(
                'id'     =>'footerTopRight',
                'type'  => 'select',
                'title' => __('Top Right Footer', 'woocommerce-print-products'), 
                'required' => array('footerLayout','equals',array('threeCols','twoCols')),
                'options'  => array(
                    'none' => __('None', 'woocommerce-print-products' ),
                    'bloginfo' => __('Blog information', 'woocommerce-print-products' ),
                    'text' => __('Custom text', 'woocommerce-print-products' ),
                    'pagenumber' => __('Pagenumber', 'woocommerce-print-products' ),
                    'productinfo' => __('Product info', 'woocommerce-print-products' ),
                    'image' => __('Image', 'woocommerce-print-products' ),
                    'exportinfo' => __('Export Information', 'woocommerce-print-products' ),
                    'qr' => __('QR-Code', 'woocommerce-print-products' ),
                ),
            ),
            array(
                'id'     =>'footerTopRightText',
                'type'  => 'editor',
                'title' => __('Top Right Footer Text', 'woocommerce-print-products'), 
                'required' => array('footerTopRight','equals','text'),
                'args'   => array(
                    'teeny'            => false,
                )
            ),
            array(
                'id'     =>'footerTopRightImage',
                'type' => 'media',
                'url'      => true,
                'title' => __('Top Right Footer Image', 'woocommerce-print-products'), 
                'required' => array('footerTopRight','equals','image'),
                'args'   => array(
                    'teeny'            => false,
                )
            ),
            array(
                'id'     =>'foooterTextBeforeFooter',
                'type'  => 'editor',
                'title' => __('Text before Footer', 'woocommerce-print-products'),
                'args'   => array(
                    'teeny'            => false,
                )
            ),
        )
    ) );

    Redux::setSection( $opt_name, array(
        'title'      => __( 'Limit Access', 'woocommerce-print-products' ),
        // 'desc'       => __( '', 'woocommerce-print-products' ),
        'id'         => 'limit-access-settings',
        'subsection' => true,
        'fields'     => array(
            array(
                'id'       => 'enableLimitAccess',
                'type'     => 'checkbox',
                'title'    => __( 'Enable', 'woocommerce-print-products' ),
                'subtitle' => __( 'Enable the limit access. This will activate the below settings.', 'woocommerce-print-products' ),
            ),
            array(
                'id'     =>'role',
                'type' => 'select',
                'data' => 'roles',
                'title' => __('User Role', 'woocommerce-print-products'),
                'subtitle' => __('Select a custom user Role (Default is: administrator) who can use this plugin.', 'woocommerce-print-products'),
                'multi' => true,
                'default' => 'administrator',
            ),
        )
    ) );

    Redux::setSection( $opt_name, array(
        'title'      => __( 'Advanced settings', 'woocommerce-print-products' ),
        'desc'       => __( 'Custom stylesheet / javascript.', 'woocommerce-print-products' ),
        'id'         => 'advanced',
        'subsection' => true,
        'fields'     => array(
            array(
                'id'       => 'debugMode',
                'type'     => 'checkbox',
                'title'    => __( 'Enable Debug Mode', 'woocommerce-print-products' ),
                'subtitle' => __( 'This stops creating the PDF and shows the plain HTML.', 'woocommerce-print-products' ),
                'default'   => 0,
            ),
            array(
                'id'       => 'customCSS',
                'type'     => 'ace_editor',
                'mode'     => 'css',
                'title'    => __( 'Custom CSS', 'woocommerce-print-products' ),
                'subtitle' => __( 'Add some stylesheet if you want.', 'woocommerce-print-products' ),
            ),
            array(
                'id'       => 'customJS',
                'type'     => 'ace_editor',
                'mode'     => 'javascript',
                'title'    => __( 'Custom JS', 'woocommerce-print-products' ),
                'subtitle' => __( 'Add some javascript if you want.', 'woocommerce-print-products' ),
            ),
        )
    ));

    /*
     * <--- END SECTIONS
     */

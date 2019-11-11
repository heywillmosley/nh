<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

if ( ! class_exists( 'PW_BOGO_Meta_Boxes' ) ) :

class PW_BOGO_Meta_Boxes {

    public static function discount( $post ) {
        global $pw_bogo;

        echo '<input type="hidden" name="pw_bogo_meta_nonce" id="pw_bogo_meta_nonce" value="' . wp_create_nonce( 'pw_bogo_save_data' ) . '" />';

        echo '<div class="pw-bogo-ltr">';

        $buy_type = get_post_meta( $post->ID, 'buy_type', true );
        $buy_types['quantity'] = 'Buy';
        $buy_types['spend'] = 'Spend';
        woocommerce_wp_select( array(
            'id' => 'pw-bogo-buy-type',
            'name' => 'buy_type',
            'label' => false,
            'value' => $buy_type,
            'options' => $buy_types
        ) );

        echo '<span id="pw-bogo-buy-currency">' . get_woocommerce_currency_symbol() . '</span>';

        $buy_limit = get_post_meta( $post->ID, 'buy_limit', true );
        if ( empty( $buy_limit ) ) { $buy_limit = '1'; }
        woocommerce_wp_text_input( array(
            'id' => 'pw-bogo-buy-limit',
            'name' => 'buy_limit',
            'label' => false,
            'value' => $buy_limit,
            'type' => 'text'
        ) );

        echo '<span class="pw-bogo-discount-label">, Get </span>';
        $get_limit = get_post_meta( $post->ID, 'get_limit', true );
        if ( empty( $get_limit ) ) { $get_limit = '1'; }
        woocommerce_wp_text_input( array(
            'id' => 'pw-bogo-get-limit',
            'name' => 'get_limit',
            'label' => false,
            'value' => $get_limit,
            'type' => 'number',
            'custom_attributes' => array( 'min' => '1' )
        ) );

        $percentage = get_post_meta( $post->ID, 'percentage', true );
        if ( empty( $percentage ) ) { $percentage = '50'; }
        woocommerce_wp_text_input( array(
            'id' => 'pw-bogo-percentage',
            'name' => 'percentage',
            'label' => false,
            'value' => $percentage,
            'type' => 'text',
            'custom_attributes' => array( 'max' => '100', 'min' => '0' )
        ) );

        $type = get_post_meta( $post->ID, 'type', true );
        $types['free'] = 'Free';
        $types['percentage'] = '% off';
        woocommerce_wp_select( array(
            'id' => 'pw-bogo-type',
            'name' => 'type',
            'label' => false,
            'value' => $type,
            'options' => $types
        ) );

        echo '</div>';

        if ( $pw_bogo->wc_min_version( '3.2' ) ) {
            $hidden = ( 'Spend' === $buy_type ) ? '' : 'style="display: none;"';
            echo "<div id=\"pw-bogo-spend-aftertax-container\" $hidden>";

            $spend_aftertax = get_post_meta( $post->ID, 'spend_aftertax', true );
            woocommerce_wp_checkbox( array(
                'id' => 'pw-bogo-spend-aftertax',
                'name' => 'spend_aftertax',
                'value' => $spend_aftertax,
                'label' => __( '"Spend" amount should be calculated including tax ', 'pw-woocommerce-bogo-free' )
            ) );

            echo '</div>';
        }
    }

    public static function products( $post ) {
        if ( PW_BOGO::wc_min_version( '2.7' ) ) {
            // Product ids
            ?>
            <p class="form-field"><label><?php _e( 'Products (leave blank if BOGO is available for all products)', 'pw-woocommerce-bogo-free' ); ?></label><br>
            <select class="wc-product-search" multiple="multiple" style="width: 50%;" name="product_ids[]" data-placeholder="<?php esc_attr_e( 'Search for a product&hellip;', 'woocommerce' ); ?>" data-action="woocommerce_json_search_products_and_variations">
                <?php
                    $product_ids = array_filter( array_map( 'absint', explode( ',', get_post_meta( $post->ID, 'product_ids', true ) ) ) );

                    foreach ( $product_ids as $product_id ) {
                        $product = wc_get_product( $product_id );
                        if ( is_object( $product ) ) {
                            echo '<option value="' . esc_attr( $product_id ) . '"' . selected( true, true, false ) . '>' . wp_kses_post( $product->get_formatted_name() ) . '</option>';
                        }
                    }
                ?>
            </select>
            <?php

            // Exclude Product ids
            ?>
            <p class="form-field"><label><?php _e( 'Exclude products', 'pw-woocommerce-bogo-free' ); ?></label><br>
            <select class="wc-product-search" multiple="multiple" style="width: 50%;" name="exclude_product_ids[]" data-placeholder="<?php esc_attr_e( 'Search for a product&hellip;', 'woocommerce' ); ?>" data-action="woocommerce_json_search_products_and_variations">
                <?php
                    $product_ids = array_filter( array_map( 'absint', explode( ',', get_post_meta( $post->ID, 'exclude_product_ids', true ) ) ) );

                    foreach ( $product_ids as $product_id ) {
                        $product = wc_get_product( $product_id );
                        if ( is_object( $product ) ) {
                            echo '<option value="' . esc_attr( $product_id ) . '"' . selected( true, true, false ) . '>' . wp_kses_post( $product->get_formatted_name() ) . '</option>';
                        }
                    }
                ?>
            </select>
            <?php
        } else {
            // Product ids
            ?>
            <p class="form-field"><label><?php _e( 'Specific Products (leave blank if BOGO is available for all products)', 'pw-woocommerce-bogo-free' ); ?></label>
            <input type="hidden" class="wc-product-search" data-multiple="true" style="width: 50%;" name="product_ids" data-placeholder="<?php esc_attr_e( 'Search for a product&hellip;', 'woocommerce' ); ?>" data-action="woocommerce_json_search_products_and_variations" data-selected="<?php
                $product_ids = array_filter( array_map( 'absint', explode( ',', get_post_meta( $post->ID, 'product_ids', true ) ) ) );
                $json_ids    = array();

                foreach ( $product_ids as $product_id ) {
                    $product = wc_get_product( $product_id );
                    if ( is_object( $product ) ) {
                        $json_ids[ $product_id ] = wp_kses_post( $product->get_formatted_name() );
                    }
                }

                echo esc_attr( json_encode( $json_ids ) );
                ?>" value="<?php echo implode( ',', array_keys( $json_ids ) ); ?>" />
            <?php

            // Exclude Product ids
            ?>
            <p class="form-field"><label><?php _e( 'Exclude products', 'pw-woocommerce-bogo-free' ); ?></label>
            <input type="hidden" class="wc-product-search" data-multiple="true" style="width: 50%;" name="exclude_product_ids" data-placeholder="<?php esc_attr_e( 'Search for a product&hellip;', 'woocommerce' ); ?>" data-action="woocommerce_json_search_products_and_variations" data-selected="<?php
                $product_ids = array_filter( array_map( 'absint', explode( ',', get_post_meta( $post->ID, 'exclude_product_ids', true ) ) ) );
                $json_ids    = array();

                foreach ( $product_ids as $product_id ) {
                    $product = wc_get_product( $product_id );
                    if ( is_object( $product ) ) {
                        $json_ids[ $product_id ] = wp_kses_post( $product->get_formatted_name() );
                    }
                }

                echo esc_attr( json_encode( $json_ids ) );
            ?>" value="<?php echo implode( ',', array_keys( $json_ids ) ); ?>" />
            <?php
        }

        // Categories
        ?>
        <p class="form-field"><label for="product_categories"><?php _e( 'Product categories', 'woocommerce' ); ?></label><br>
        <select name="product_categories[]" style="width: 50%;"  class="wc-enhanced-select" multiple="multiple" data-placeholder="<?php esc_attr_e( 'Any category', 'woocommerce' ); ?>">
            <?php
                $category_ids = (array) get_post_meta( $post->ID, 'product_categories', true );
                $categories   = get_terms( 'product_cat', 'orderby=name&hide_empty=0' );

                if ( $categories ) {
                    $sorted = array();
                    PW_BOGO_Meta_Boxes::sort_terms_hierarchicaly( $categories, $sorted );
                    PW_BOGO_Meta_Boxes::hierarchical_select( $sorted, $category_ids );
                }
            ?>
        </select>
        <?php

        // Exclude Categories
        ?>
        <p class="form-field"><label for="exclude_product_categories"><?php _e( 'Exclude categories', 'woocommerce' ); ?></label><br>
        <select name="exclude_product_categories[]" style="width: 50%;"  class="wc-enhanced-select" multiple="multiple" data-placeholder="<?php esc_attr_e( 'No categories', 'woocommerce' ); ?>">
            <?php
                $category_ids = (array) get_post_meta( $post->ID, 'exclude_product_categories', true );
                $categories   = get_terms( 'product_cat', 'orderby=name&hide_empty=0' );

                if ( $categories ) {
                    $sorted = array();
                    PW_BOGO_Meta_Boxes::sort_terms_hierarchicaly( $categories, $sorted );
                    PW_BOGO_Meta_Boxes::hierarchical_select( $sorted, $category_ids );
                }
            ?>
        </select>
        <?php
    }

    public static function discounted_products( $post ) {

        echo '<div class="pw-bogo-ltr">';

        $ignore_discounted_products = get_post_meta( $post->ID, 'ignore_discounted_products', true );
        if ( empty( $ignore_discounted_products ) ) { $ignore_discounted_products = 'yes'; }
        woocommerce_wp_checkbox( array(
            'id' => 'pw-bogo-ignore-discounted-products',
            'name' => 'ignore_discounted_products',
            'value' => $ignore_discounted_products,
            'label' => __( 'Same as Eligible Products ', 'pw-woocommerce-bogo-free' )
        ) );

        $hidden = ( 'yes' === $ignore_discounted_products ) ? 'style="display: none;"' : '';
        echo "<div id=\"pw-discounted-products-container\" $hidden>";

        if ( PW_BOGO::wc_min_version( '2.7' ) ) {
            // Product ids
            ?>
            <p class="form-field"><label><?php _e( 'Specific Products (leave blank if discount can apply to any product)', 'pw-woocommerce-bogo-free' ); ?></label><br>
            <select class="wc-product-search" multiple="multiple" style="width: 50%;" name="discounted_product_ids[]" data-placeholder="<?php esc_attr_e( 'Search for a product&hellip;', 'pw-woocommerce-bogo-free' ); ?>" data-action="woocommerce_json_search_products_and_variations">
                <?php
                    $product_ids = array_filter( array_map( 'absint', explode( ',', get_post_meta( $post->ID, 'discounted_product_ids', true ) ) ) );

                    foreach ( $product_ids as $product_id ) {
                        $product = wc_get_product( $product_id );
                        if ( is_object( $product ) ) {
                            echo '<option value="' . esc_attr( $product_id ) . '"' . selected( true, true, false ) . '>' . wp_kses_post( $product->get_formatted_name() ) . '</option>';
                        }
                    }
                ?>
            </select>
            <?php

            // Exclude Product ids
            ?>
            <p class="form-field"><label><?php _e( 'Exclude products', 'pw-woocommerce-bogo-free' ); ?></label><br>
            <select class="wc-product-search" multiple="multiple" style="width: 50%;" name="discounted_exclude_product_ids[]" data-placeholder="<?php esc_attr_e( 'Search for a product&hellip;', 'woocommerce' ); ?>" data-action="woocommerce_json_search_products_and_variations">
                <?php
                    $product_ids = array_filter( array_map( 'absint', explode( ',', get_post_meta( $post->ID, 'discounted_exclude_product_ids', true ) ) ) );

                    foreach ( $product_ids as $product_id ) {
                        $product = wc_get_product( $product_id );
                        if ( is_object( $product ) ) {
                            echo '<option value="' . esc_attr( $product_id ) . '"' . selected( true, true, false ) . '>' . wp_kses_post( $product->get_formatted_name() ) . '</option>';
                        }
                    }
                ?>
            </select>
            <?php
        } else {
            // Product ids
            ?>
            <p class="form-field"><label><?php _e( 'Products (leave blank if BOGO is available for all products)', 'pw-woocommerce-bogo-free' ); ?></label>
            <input type="hidden" class="wc-product-search" data-multiple="true" style="width: 50%;" name="discounted_product_ids" data-placeholder="<?php esc_attr_e( 'Search for a product&hellip;', 'woocommerce' ); ?>" data-action="woocommerce_json_search_products_and_variations" data-selected="<?php
                $product_ids = array_filter( array_map( 'absint', explode( ',', get_post_meta( $post->ID, 'discounted_product_ids', true ) ) ) );
                $json_ids    = array();

                foreach ( $product_ids as $product_id ) {
                    $product = wc_get_product( $product_id );
                    if ( is_object( $product ) ) {
                        $json_ids[ $product_id ] = wp_kses_post( $product->get_formatted_name() );
                    }
                }

                echo esc_attr( json_encode( $json_ids ) );
                ?>" value="<?php echo implode( ',', array_keys( $json_ids ) ); ?>" />
            <?php

            // Exclude Product ids
            ?>
            <p class="form-field"><label><?php _e( 'Exclude products', 'pw-woocommerce-bogo-free' ); ?></label>
            <input type="hidden" class="wc-product-search" data-multiple="true" style="width: 50%;" name="discounted_exclude_product_ids" data-placeholder="<?php esc_attr_e( 'Search for a product&hellip;', 'woocommerce' ); ?>" data-action="woocommerce_json_search_products_and_variations" data-selected="<?php
                $product_ids = array_filter( array_map( 'absint', explode( ',', get_post_meta( $post->ID, 'discounted_exclude_product_ids', true ) ) ) );
                $json_ids    = array();

                foreach ( $product_ids as $product_id ) {
                    $product = wc_get_product( $product_id );
                    if ( is_object( $product ) ) {
                        $json_ids[ $product_id ] = wp_kses_post( $product->get_formatted_name() );
                    }
                }

                echo esc_attr( json_encode( $json_ids ) );
            ?>" value="<?php echo implode( ',', array_keys( $json_ids ) ); ?>" />
            <?php
        }

        // Categories
        ?>
        <p class="form-field"><label for="product_categories"><?php _e( 'Product categories', 'woocommerce' ); ?></label><br>
        <select name="discounted_product_categories[]" style="width: 50%;"  class="wc-enhanced-select" multiple="multiple" data-placeholder="<?php esc_attr_e( 'Any category', 'woocommerce' ); ?>">
            <?php
                $category_ids = (array) get_post_meta( $post->ID, 'discounted_product_categories', true );
                $categories   = get_terms( 'product_cat', 'orderby=name&hide_empty=0' );

                if ( $categories ) {
                    $sorted = array();
                    PW_BOGO_Meta_Boxes::sort_terms_hierarchicaly( $categories, $sorted );
                    PW_BOGO_Meta_Boxes::hierarchical_select( $sorted, $category_ids );
                }
            ?>
        </select>
        <?php

        // Exclude Categories
        ?>
        <p class="form-field"><label for="exclude_product_categories"><?php _e( 'Exclude categories', 'woocommerce' ); ?></label><br>
        <select name="discounted_exclude_product_categories[]" style="width: 50%;"  class="wc-enhanced-select" multiple="multiple" data-placeholder="<?php esc_attr_e( 'No categories', 'woocommerce' ); ?>">
            <?php
                $category_ids = (array) get_post_meta( $post->ID, 'discounted_exclude_product_categories', true );
                $categories   = get_terms( 'product_cat', 'orderby=name&hide_empty=0' );

                if ( $categories ) {
                    $sorted = array();
                    PW_BOGO_Meta_Boxes::sort_terms_hierarchicaly( $categories, $sorted );
                    PW_BOGO_Meta_Boxes::hierarchical_select( $sorted, $category_ids );
                }
            ?>
        </select>
        <?php

        echo '</div>';

        // Auto-add
        $auto_add_discounted_products = get_post_meta( $post->ID, 'auto_add_discounted_products', true );
        woocommerce_wp_checkbox( array(
            'id' => 'pw-bogo-auto-add-discounted-products',
            'name' => 'auto_add_discounted_products',
            'value' => $auto_add_discounted_products,
            'label' => __( 'Automatically add discounted product(s) to the cart ', 'pw-woocommerce-bogo-free' ),
            'description' => __( '<br>Check this box if you would like the Discounted Products to be automatically added to the cart when the criteria is met.', 'pw-woocommerce-bogo-free' )
        ) );

        // Restrict discounted quantity
        $hidden = ( 'yes' === $auto_add_discounted_products ) ? '' : 'style="display: none;"';
        echo "<div id=\"pw-bogo-restrict-discount-quantity-container\" $hidden>";
        $restrict_discount_quantity = get_post_meta( $post->ID, 'restrict_discount_quantity', true );
        woocommerce_wp_checkbox( array(
            'id' => 'pw-bogo-restrict-discount-quantity',
            'name' => 'restrict_discount_quantity',
            'value' => $restrict_discount_quantity,
            'label' => __( 'Restrict discounted product quantity ', 'pw-woocommerce-bogo-free' ),
            'description' => __( '<br>Forces the quantity of the discounted products to match the deal. Will not allow the customer to purchase the discounted products at regular price.', 'pw-woocommerce-bogo-free' )
        ) );
        echo '</div>';

        $identical_products_only = get_post_meta( $post->ID, 'identical_products_only', true );
        woocommerce_wp_checkbox( array(
            'id' => 'pw-bogo-identical-products-only',
            'name' => 'identical_products_only',
            'value' => $identical_products_only,
            'label' => __( 'Only discount identical products ', 'pw-woocommerce-bogo-free' ),
            'description' => '<a href="#" id="pw-bogo-identical-products-help-link">What\'s this?</a>'
        ) );

        $hidden = ( 'yes' === $identical_products_only ) ? '' : 'style="display: none;"';
        echo "<div id=\"pw-bogo-identical-variations-only-container\" $hidden>";
        $identical_variations_only = get_post_meta( $post->ID, 'identical_variations_only', true );
        woocommerce_wp_checkbox( array(
            'id' => 'pw-bogo-identical-variations-only',
            'name' => 'identical_variations_only',
            'value' => $identical_variations_only,
            'label' => __( 'Only discount identical variations ', 'pw-woocommerce-bogo-free' )
        ) );
        echo '</div>';

        ?>
        <script>
            jQuery(function() {
                jQuery('#pw-bogo-identical-products-help-link').on('click', function(e) {
                    jQuery('#pw-bogo-identical-products-help').toggle();
                    e.preventDefault();
                    return false;
                });

                jQuery('#pw-bogo-auto-add-discounted-products').on('change', function() {
                    jQuery('#pw-bogo-restrict-discount-quantity-container').toggle(jQuery(this).is(':checked'));
                });

                jQuery('#pw-bogo-identical-products-only').on('change', function() {
                    jQuery('#pw-bogo-identical-variations-only-container').toggle(jQuery(this).is(':checked'));
                });
            });
        </script>
        <div id="pw-bogo-identical-products-help" style="display: none; margin-left: 50px;">
            <p>
                Instead of allowing a mix-and-match of products, only discount identical products. For example:
            </p>
            <p>
                Promotion: <b>Buy One, Get One Free</b><br>
                Category: <b>Hats</b><br>
                Cart:<br>
                &nbsp;&nbsp;&nbsp;&nbsp;$100 Large Hat (x 2)<br>
                &nbsp;&nbsp;&nbsp;&nbsp;$50 Small Hat (x 2)
            </p>
            <p>
                With this option <b>unchecked</b>, the discount will be <b>$100</b>. (2 Large Hats purchased, 2 Small Hats for free.)<br>
                With this option <b>checked</b>, the discount will be <b>$150</b>. (1 free Large Hat, 1 free Small Hat.)
            </p>
        </div>
        <?php

        echo '</div>';
    }

    /**
     * Source: http://wordpress.stackexchange.com/questions/14652/how-to-show-a-hierarchical-terms-list
     * Recursively sort an array of taxonomy terms hierarchically. Child categories will be
     * placed under a 'children' member of their parent term.
     * @param Array   $cats     taxonomy term objects to sort
     * @param Array   $into     result array to put them in
     * @param integer $parentId the current parent ID to put them in
     */
    public static function sort_terms_hierarchicaly(array &$cats, array &$into, $parentId = 0)
    {
        foreach ( $cats as $i => $cat ) {
            if ( $cat->parent == $parentId ) {
                $into[$cat->term_id] = $cat;
                unset( $cats[$i] );
            }
        }

        foreach ( $into as $topCat ) {
            $topCat->children = array();
            PW_BOGO_Meta_Boxes::sort_terms_hierarchicaly( $cats, $topCat->children, $topCat->term_id );
        }
    }

    public static function hierarchical_select($categories, $selected_category_ids, $level = 0, $parent = NULL, $prefix = '') {
        foreach ( $categories as $category ) {
            $selected = selected( in_array( $category->term_id, $selected_category_ids ), true, false );
            echo "<option value='" . esc_attr( $category->term_id ) . "' $selected>$prefix " . esc_html( $category->name ) . "</option>\n";

            if ( $category->parent == $parent ) {
                $level = 0;
            }

            if ( count( $category->children ) > 0 ) {
                echo PW_BOGO_Meta_Boxes::hierarchical_select( $category->children, $selected_category_ids, ( $level + 1 ), $category->parent, "$prefix " . esc_html( $category->name ) . " &#8594;" );
            }
        }
    }

    public static function dates( $post ) {

        $begin_date = get_post_meta( $post->ID, 'begin_date', true );
        woocommerce_wp_text_input( array(
            'id' => 'pw-bogo-begin-date',
            'name' => 'begin_date',
            'value' => $begin_date,
            'label' => __( 'Begin date<br>', 'pw-woocommerce-bogo-free' ) . ' ',
            'placeholder' => _x( 'YYYY-MM-DD', 'placeholder', 'woocommerce' ),
            'description' => '',
            'class' => 'date-picker',
            'custom_attributes' => array( 'pattern' => "[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])" )
        ) );

        $end_date = get_post_meta( $post->ID, 'end_date', true );
        woocommerce_wp_text_input( array(
            'id' => 'pw-bogo-end-date',
            'name' => 'end_date',
            'value' => $end_date,
            'label' => __( 'End date<br>', 'pw-woocommerce-bogo-free' ) . ' ',
            'placeholder' => _x( 'YYYY-MM-DD', 'placeholder', 'woocommerce' ),
            'description' => '',
            'class' => 'date-picker',
            'custom_attributes' => array( 'pattern' => "[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])" )
        ) );
    }

    public static function restrictions( $post ) {
        echo '<div class="pw-bogo-ltr">';

        $discount_limit = get_post_meta( $post->ID, 'discount_limit', true );
        if ( empty( $discount_limit ) ) { $discount_limit = ''; }
        woocommerce_wp_text_input( array(
            'id' => 'pw-bogo-discount-limit',
            'name' => 'discount_limit',
            'label' => __( 'Per Order Limit - The number of times this deal can be applied, per order. Leave blank or 0 for no limit.<br>', 'pw-woocommerce-bogo-free' ),
            'value' => $discount_limit,
            'type' => 'number',
            'custom_attributes' => array( 'min' => '0' ),
            'description' => __( '<br>For example: a BOGO deal has a limit of 2 and there are 10 items in the cart. Only the first 4 items participate in the BOGO, resulting in 2 free items.', 'pw-woocommerce-bogo-free' ),
            'class' => 'pw-bogo-limit-field'
        ) );

        if ( PW_BOGO::wc_min_version( '2.7' ) ) {
            $redemption_limit = get_post_meta( $post->ID, 'redemption_limit', true );
            if ( empty( $redemption_limit ) ) { $redemption_limit = ''; }
            woocommerce_wp_text_input( array(
                'id' => 'pw-bogo-redemption-limit',
                'name' => 'redemption_limit',
                'label' => __( 'Redemption Limit - The number of times this deal can be used for all customers. Leave blank or 0 for no limit.<br>', 'pw-woocommerce-bogo-free' ),
                'value' => $redemption_limit,
                'type' => 'number',
                'custom_attributes' => array( 'min' => '0' ),
                'class' => 'pw-bogo-limit-field'
            ) );

            $redemption_count = get_post_meta( $post->ID, 'redemption_count', true );
            if ( empty( $redemption_count ) ) { $redemption_count = ''; }
            woocommerce_wp_text_input( array(
                'id' => 'pw-bogo-redemption-count',
                'name' => 'redemption_count',
                'label' => __( 'Redemption Counter - The number of times this deal has been used. You can manually reset the counter using this field.<br>', 'pw-woocommerce-bogo-free' ),
                'value' => $redemption_count,
                'type' => 'number',
                'custom_attributes' => array( 'min' => '0' ),
                'class' => 'pw-bogo-limit-field'
            ) );
        }

        if ( wc_coupons_enabled() ) {
            // Coupon Code Required
            $coupon_code = get_post_meta( $post->ID, 'coupon_code', true );
            woocommerce_wp_text_input( array(
                'id' => 'pw-bogo-coupon-code',
                'name' => 'coupon_code',
                'value' => $coupon_code,
                'label' => __( 'Coupon Code<br>', 'pw-woocommerce-bogo-free' ) . ' ',
                'description' => '<br>If a coupon code is required to activate the BOGO, enter it here. The coupon does not have to exist already.'
            ) );

            // Free Shipping
            $free_shipping = get_post_meta( $post->ID, 'free_shipping', true );
            woocommerce_wp_checkbox( array(
                'id' => 'pw-bogo-free-shipping',
                'name' => 'free_shipping',
                'value' => $free_shipping,
                'label' => __( 'Allow free shipping ', 'woocommerce' ),
                'description' => sprintf( __( '<br>Check this box if the BOGO deal grants free shipping. A <a href="%s" target="_blank">free shipping method</a> must be enabled in your shipping zone and be set to require "a valid free shipping coupon" (see the "Free Shipping Requires" setting).', 'pw-woocommerce-bogo-free' ), 'https://docs.woocommerce.com/document/free-shipping/' )
            ) );

            // Individual use
            $individual_use = get_post_meta( $post->ID, 'individual_use', true );
            woocommerce_wp_checkbox( array(
                'id' => 'pw-bogo-individual-use',
                'name' => 'individual_use',
                'value' => $individual_use,
                'label' => __( 'No other coupons allowed ', 'woocommerce' ),
                'description' => __( '<br>Check this box if other coupons are not allowed for BOGO items.', 'pw-woocommerce-bogo-free' )
            ) );

            // Apply with Individual Use coupons.
            $apply_with_individual_use_coupons = get_post_meta( $post->ID, 'apply_with_individual_use_coupons', true );
            woocommerce_wp_checkbox( array(
                'id' => 'pw-bogo-apply-with-individual-use-coupons',
                'name' => 'apply_with_individual_use_coupons',
                'value' => $apply_with_individual_use_coupons,
                'label' => __( 'Allow with other Individual Use coupons ', 'woocommerce' ),
                'description' => __( '<br>Check this box if this BOGO can be used in conjunction with other coupons that are marked "Individual Use".', 'pw-woocommerce-bogo-free' )
            ) );
        }

        // Exclude Sale Products
        $exclude_sale_items = get_post_meta( $post->ID, 'exclude_sale_items', true );
        woocommerce_wp_checkbox( array(
            'id' => 'pw-bogo-exclude-sale-items',
            'name' => 'exclude_sale_items',
            'value' => $exclude_sale_items,
            'label' => __( 'Exclude sale items ', 'woocommerce' ),
            'description' => __( '<br>Check this box if the BOGO deal should not apply to items on sale.', 'pw-woocommerce-bogo-free' ) )
        );

        echo '</div>';
    }

    public static function renew( $post ) {
        global $pw_bogo;

        ?>
        <div class="pw-bogo-ltr">
            <p>
                <?php
                    _e( 'You may continue using the plugin with all features enabled, however you will no longer receive new updates and features.', 'pw-woocommerce-bogo-free' );
                ?>
            </p>
            <p>
                <a href="<?php echo $pw_bogo->license->get_renew_url(); ?>" target="_blank" class="button"><?php _e( 'Renew your license', 'pw-woocommerce-bogo-free' ); ?></a>
            </p>
        </div>
        <?php
    }

    public static function about( $post ) {
        ?>
        <span class="pw-bogo-title">PW WooCommerce BOGO </span>
        <span class="pw-bogo-version">v<?php echo PW_BOGO::version(); ?></span>

        <div>by <a href="https://www.pimwick.com" target="_blank" class="pw-bogo-link">Pimwick, LLC</a></div>
        <?php
    }

    public static function activation() {
        global $pw_bogo;

        ?>
        <h3>Thank you for your purchase!</h3>
        <p>Enter the license key that was sent to your email address.</p>
        <p>If you need assistance email <a href="mailto:us@pimwick.com">us@pimwick.com</a></p>
        <p>
            <div id="pw-bogo-activation-error"><?php echo $pw_bogo->license->error; ?></div>
            <form id="pw-bogo-activation">
                <label for="license-key">License Key</label>
                <input type="text" id="pw-bogo-license-key" name="license-key" class="regular-text" required>
                <input type="submit" id="pw-bogo-activate-license" name="activate-license" value="Activate" class="button button-primary" />
            </form>
        </p>
        <script>
            jQuery(function( $ ) {

                $('#pw-bogo-license-key').focus();

                // Hide the default meta fields and prevent Enter from submitting the form.
                $('#titlediv, #submitdiv').hide();
                $('#pw-bogo-license-key').keydown(function(e){
                    if (e.keyCode == 13) {
                        pwBogoActivate($);
                        e.preventDefault();
                        return false;
                    }
                });

                $('#pw-bogo-activate-license').click(function(e) {
                    pwBogoActivate($);
                    e.preventDefault();
                    return false;
                });
            });

            function pwBogoActivate($) {
                $('#pw-bogo-activation-error').text('');
                $('#pw-bogo-activate-license').prop('disabled', true).val('Activating, please wait...');

                $.post(ajaxurl, {'action': 'pw-bogo-activation', 'license-key': $('#pw-bogo-license-key').val() }, function( result ) {
                    if (result.active == true) {
                        location.reload();
                    } else {
                        $('#pw-bogo-activation-error').text(result.error);
                        $('#pw-bogo-activate-license').prop('disabled', false).val('Activate');
                    }
                });
            }
        </script>
        <?php
    }

}

endif;

?>
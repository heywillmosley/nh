jQuery( document ).ready( function( $ ) {

    /*
     |------------------------------------------------------------------------------------------------------------------
     | Cache Selector
     |------------------------------------------------------------------------------------------------------------------
     */
    var $wwofProductListingContainer    = $( "#wwof_product_listing_container" ),
        $wwofProductListingFilter       = $( "#wwof_product_listing_filter" ),
        $bottomListActions              = $( ".bottom_list_actions" ),
        // Shortcode Atts
        $shortcodeAtts                  = {
                                            'categories' : $wwofProductListingContainer.attr( 'data-categories' ),
                                            'products'   : $wwofProductListingContainer.attr( 'data-products' )
                                        },
        productData = [],
        fancyboxIsOpen                  = false;




    /*
     |------------------------------------------------------------------------------------------------------------------
     | Functions
     |------------------------------------------------------------------------------------------------------------------
     */

    function disableElement( $element ) {

        $element.attr( 'disabled' , 'disabled' ).addClass( 'disabled' );

    }

    function enableElement( $element ) {

        $element.removeAttr( 'disabled' ).removeClass( 'disabled' );

    }

    function attachErrorStateToElement( $element ) {

        $element.addClass( 'error' );

    }

    function detachErrorStateToElement( $element ) {

        $element.removeClass( 'error' );

    }

    function disableSearchCommandFields() {

        disableElement( $wwofProductListingFilter.find( "#wwof_product_search_form" ) );
        disableElement( $wwofProductListingFilter.find( "#wwof_product_search_category_filter" ) );
        disableElement( $wwofProductListingFilter.find( "#wwof_product_search_btn" ) );
        disableElement( $wwofProductListingFilter.find( "#wwof_product_displayall_btn" ) );

    }

    function disabledPagingLinks() {

        disableElement( $wwofProductListingContainer.find( "#wwof_product_listing_pagination ul li a" ) );

    }

    function enabledSearchCommandFields() {

        enableElement( $wwofProductListingFilter.find( "#wwof_product_search_form" ) );
        enableElement( $wwofProductListingFilter.find( "#wwof_product_search_category_filter" ) );
        enableElement( $wwofProductListingFilter.find( "#wwof_product_search_btn" ) );
        enableElement( $wwofProductListingFilter.find( "#wwof_product_displayall_btn" ) );

    }

    function enablePagingLinks() {

        enableElement( $wwofProductListingContainer.find( "#wwof_product_listing_pagination ul li a" ) );

    }

    function showProcessingOverlay() {

        var $overlay_container;

        if ( $wwofProductListingContainer.find( "#wwof_product_listing_table" ).length > 0 )
            $overlay_container = $wwofProductListingContainer.find( "#wwof_product_listing_table" );
        else
            $overlay_container = $wwofProductListingContainer.find( "#wwof_product_listing_ajax_content" );

        $overlay_container.css( 'min-height' , '200px' );

        var table_width = $overlay_container.width(),
            table_height = $overlay_container.height();

        $overlay_container.append(
            '<div class="processing-overlay" style="position: absolute; width: ' + table_width + 'px; height: ' + table_height + 'px; min-height: 200px; top: 0; left: 0;">' +
            '<div class="loading-icon"></div>' +
            '</div>'
        );

    }

    function removeProcessingOverlay() {

        var $overlay_container;

        if ( $wwofProductListingContainer.find( "#wwof_product_listing_table_container" ).length > 0 )
            $overlay_container = $wwofProductListingContainer.find( "#wwof_product_listing_table_container" );
        else
            $overlay_container = $wwofProductListingContainer.find( "#wwof_product_listing_ajax_content" );

        $overlay_container.find( ".processing-overlay" ).remove();

    }

    function fadeOutElement( $element , delay ) {

        setTimeout( function() {
            $element.fadeOut( 'fast' );
        } , delay );

    }

    function getParameterByName( name , url ) {

        name = name.replace(/[\[]/, "\\[").replace(/[\]]/, "\\]");
        var regex = new RegExp("[\\?&]" + name + "=([^&#]*)"),
            results = regex.exec(url);
        return results === null ? "" : decodeURIComponent(results[1].replace(/\+/g, " "));

    }

    function LoadProductListing( paged , search , catFilter , $shortcodeAtts , first_load ) {

        disableSearchCommandFields();
        disabledPagingLinks();
        showProcessingOverlay();

        wwofFrontEndAjaxServices.displayProductListing( paged , search , catFilter , $shortcodeAtts )
        .done( function( data , textStatus , jqXHR ) {

            $wwofProductListingContainer.find( "#wwof_product_listing_ajax_content" ).html( data , search );
            $wwofProductListingContainer.data( 'paged' , paged ).data( 'init-content' , true );

            if ( Options.disable_pagination === 'yes' )
                $wwofProductListingContainer.data( 'all-products-loaded' , false );

            enabledSearchCommandFields();
            // We dont need to re-eanble paging links, a new paging links will be added anyways.
            // Alos this is a bug fix for clicking the paging links multiple times consecutive in the middle of the ajax request process
            removeProcessingOverlay();

            if ( !first_load ) {

                // Scroll to top of the product table
                offset = $( '#wwof_product_listing_container' ).offset();
                $( 'html, body' ).animate( {
                    scrollTop  : offset.top - 30,
                    scrollLeft : offset.left
                } );

            }

            $( window ).scroll();

        } )
        .fail( function( jqXHR , textStatus , errorThrown ) {

            vex.dialog.alert( { unsafeMessage : errorThrown } );

            enabledSearchCommandFields();
            enablePagingLinks(); // We re-enable paging links as no new paging links are added when an error occured during ajax request
            removeProcessingOverlay();

        } );

    }

    function blockFragments ( fragments ) {

        if ( fragments ) {

            $.each( fragments, function( key , value ) {
                $( key ).addClass( 'updating' );
            } );

        }

    }

    function unblockFragments ( fragments ) {

        if ( fragments ) {

            $.each( fragments, function( key , value ) {
                $( key ).removeClass( 'updating' );
            } );

        }

    }

    function replaceFragments ( fragments ) {

        if ( fragments ) {

            $.each( fragments, function( key, value ) {
                $( key ).replaceWith( value );
            } );

        }

    }

    // Check if variation prices and wholesale prices are similar if so then we hide the box info below the variation select
    function variationSimilarPriceCheck( productData ) {

        var variationCount      = productData.length;
        var countSimilarPrice   = 0;

        productData.forEach( function( value , index , data ) {

            if( data[ 0 ].display_price == value.display_price && ! data[ 0 ].hasOwnProperty( 'wholesale_price' ) && ! value.hasOwnProperty( 'wholesale_price' ) )
                countSimilarPrice += 1;
            else if( data[ 0 ].display_price == value.display_price && data[ 0 ].hasOwnProperty( 'wholesale_price' ) && value.hasOwnProperty( 'wholesale_price' ) && productData[ 0 ].wholesale_price == value.wholesale_price )
                countSimilarPrice += 1;

        });

        // Hide price if all variation have same price and wholesale price just like the product page
        if( countSimilarPrice === variationCount ){
            $( 'body table.dummy-table .product_price_col' ).find( '.original-computed-price' ).hide();
            $( 'body table.dummy-table .product_price_col' ).find( '.wholesale_price_container' ).hide();
        }

    }

    /*
     |------------------------------------------------------------------------------------------------------------------
     | Events
     |------------------------------------------------------------------------------------------------------------------
     |
     | 5. Change the variation price accordingly depending on the selected variation.
     |
     */

    // 1
    $wwofProductListingContainer.on( 'click' , '#wwof_product_listing_pagination ul li a' , function() {

        var $this = $( this );

        if ( !$this.hasClass( 'disabled' ) ) {

            var url         =   $this.attr( 'href' ),
                paged       =   getParameterByName( 'paged' , url ),
                search      =   getParameterByName( 'search' , url ),
                catFilter   =   getParameterByName( 'cat_filter' , url );

            LoadProductListing( paged , search , catFilter , $shortcodeAtts );

        }

        return false;

    });

    // 2
    $wwofProductListingFilter.find( "#wwof_product_search_btn" ).click( function() {

        var search  =   $.trim( $wwofProductListingFilter.find( "#wwof_product_search_form" ).val() ),
            catFilter = $.trim( $wwofProductListingFilter.find( "#wwof_product_search_category_filter" ).find( "option:selected" ).val() )

        $wwofProductListingFilter.data( 'current-search' , search );
        $wwofProductListingFilter.data( 'current-category' , catFilter );

        // To eliminate before and after spaces
        $wwofProductListingFilter.find( "#wwof_product_search_form" ).val( search );

        if ( search == "" ) {

            // Display all products
            LoadProductListing( 1 , "" , catFilter , $shortcodeAtts );

        } else {

            // Display only specified products
            LoadProductListing( 1 , search , catFilter , $shortcodeAtts );

        }

    });

    // 3
    $wwofProductListingFilter.find( "#wwof_product_displayall_btn" ).click( function() {

        $wwofProductListingFilter.find( "#wwof_product_search_form" ).val( "" );
        $wwofProductListingFilter.find( "#wwof_product_search_category_filter" ).find( "option:first" ).attr( 'selected' , 'selected' );

        LoadProductListing( 1 , "" , "" , $shortcodeAtts );

    });

    // 4
    $wwofProductListingFilter.find( "#wwof_product_search_form" ).keyup( function( event ) {

        if ( event.keyCode == 13 ) {
            $( "#wwof_product_search_btn" ).click();
        }

    });

    // 5
    $wwofProductListingContainer.on( 'change' , '.product_variations' , wwofProcessVariationChange );

    // @since 1.7.0 Made it possible to do "add to cart" on the popup product summary
    $( 'body' ).on( 'change' , '.wwof-popup-product-summary .product_variations' , wwofProcessVariationChange );

    function wwofProcessVariationChange() {

        var $this = $( this ),
            variation_id = $this.val(),
            is_init_load = $this.data( 'init-load' ),
            $popup_wrap  = $this.closest( '.wwof-popup-product-summary' );

        // Get the available variations on this product
        var available_variations_json = $this.closest( 'tr' ).find( '.product_meta_col' ).data('product_variations');
        if ( !available_variations_json )
            return;

        // toggle variation description on popup
        if ( $popup_wrap.length > 0 ) {

            $popup_wrap.find( '.variation-desc' ).hide();
            $popup_wrap.find( '.variation-desc-' + variation_id ).css( 'display' , 'block' );
        }

        // Find the new variation data
        var new_variation = available_variations_json.find( function( x ) { return x.variation_id == variation_id } );

        // Find the columns that need new data
        var product_price_col = $this.closest( 'tr' ).find( '.product_price_col' );
        var product_sku_col = $this.closest( 'tr' ).find( '.product_sku_col' );
        var product_stock_quantity_col = $this.closest( 'tr' ).find( '.product_stock_quantity_col' );
        var product_quantity_col = $this.closest( 'tr' ).find( '.product_quantity_col' );
        var qty_field = $this.closest( 'tr' ).find( '.product_quantity_col' ).find( '.qty' );
        var product_title_col = $this.closest( 'tr' ).find( '.product_title_col' );

        // remove availability text from previous variation.
        product_title_col.find( 'p.stock.available-on-backorder' ).remove();

        // Set new data
        if ( new_variation ) {

            product_price_col.html( new_variation.price_html );
            product_sku_col.html( new_variation.sku );

            var availability_html = new_variation.availability_html ? new_variation.availability_html : '',
                min_value         = new_variation.min_qty ? parseInt( new_variation.min_qty ) : 1,
                input_value       = new_variation.input_value ? parseInt( new_variation.input_value ) : min_value;

            product_stock_quantity_col.html( availability_html );

            // Set min qty on qty input
            qty_field.prop( 'min', min_value );
            qty_field.val( input_value );

            // Set max qty on qty input
            qty_field.prop( 'max', new_variation.max_qty ? parseInt(new_variation.max_qty) : '' );

            // Set step on qty input
            qty_field.prop( 'step', new_variation.step ? parseInt(new_variation.step) : '' );

        } else {
            console.log( 'Could not retrieve variation data. Please contact support.' );
        }

        if( fancyboxIsOpen )
            variationSimilarPriceCheck( available_variations_json );

        // if product row has just been inserted, then don't proceed gettin quantity input args yet.
        if ( is_init_load ) {
            $this.data( 'init-load' , false );
            return;
        }

    }

    function get_product_addon_data( $current_tr ) {

        if ( $current_tr.find( ".product-addon" ).length > 0 ) {

            var product_addon_data = { addon : [] , errors : [] };

            $current_tr.find( ".product-addon" ).each( function() {

                var $product_addon = $( this ),
                    addon          = [];

                $product_addon.find( '.addon' ).each( function() {

                    var $addon_field = $( this ),
                        addon_name   = $addon_field.attr( 'name' ),
                        field_type   = $addon_field.prop( 'type' );
                        addon_value  = null;

                    if ( field_type == 'file' ) {

                        addon_value = $addon_field[0].files[0];

                    } else if ( $addon_field.attr( 'type' ) == 'checkbox' || $addon_field.attr( 'type' ) == 'radio' ) {

                        if ( $addon_field.is( ':checked' ) )
                            addon_value = $addon_field.val();

                    } else {

                        if ( $addon_field.val() )
                            addon_value = $addon_field.val();

                    }

                    if ( addon_value )
                        addon.push( { name : addon_name , value : addon_value , field_type : field_type } );

                } ); // .addon

                if ( addon.length === 0 ) {

                    if ( $product_addon.hasClass( 'required-product-addon' ) )
                        product_addon_data.errors.push( $product_addon.find( '.addon-name' ).text() );

                } else
                    product_addon_data.addon = $.merge( product_addon_data.addon , addon );

            } ); // .product-addon

            return product_addon_data;

        }

        return null;

    }

    function wwofValidateQuantityField( quantity , quantityField ) {

        if ( ! quantity ) {
            vex.dialog.alert( { unsafeMessage : Options.no_quantity_inputted } );
            return;
        }

        var quantityMin  = quantityField.prop( 'min' ) ? parseInt( quantityField.prop( 'min' ) ) : 1,
            quantityMax  = quantityField.prop( 'max' ) ? parseInt( quantityField.prop( 'max' ) ) : 0,
            quantityStep = quantityField.prop( 'step' ) ? parseInt( quantityField.prop( 'step' ) ) : 1,
            excessQty    = quantity - quantityMin;

        // validate quantity
        if ( quantity < quantityMin || ( quantityMax && quantity > quantityMax ) ) {

            invalidQuantityError = Options.invalid_quantity_min_max.replace( '{min}' , quantityMin ).replace( '{max}' , quantityMax );
            vex.dialog.alert( { unsafeMessage : invalidQuantityError } );
            return;

        } else if ( excessQty % quantityStep !== 0 ) {

            var multiplier  = parseInt( ( quantity - quantityMin ) / quantityStep , 10 ),
                nearestLow  = quantityMin + ( quantityStep * multiplier ),
                nearestHigh = quantityMin + ( quantityStep * ( multiplier + 1 ) );

            invalidQuantityError = Options.invalid_quantity.replace( '{low}' , nearestLow ).replace( '{high}' , nearestHigh );

            vex.dialog.alert( { unsafeMessage : invalidQuantityError } );

            return;

        } else
            return true;

    }

    // 6
    // @since 1.6.3 WWOF-73
    //              Replaced .delegate with .on
    //              Made the request queue properly when multiple items were added to cart.
    $wwofProductListingContainer.on( 'click' , '.wwof_add_to_cart_button' , wwofProcessAddToCart );

    // @since 1.7.0 Made it possible to do "add to cart" on the popup product summary
    $('body').on( 'click' , '.wwof-popup-product-summary .wwof_add_to_cart_button' , wwofProcessAddToCart );

    // @since 1.7.0 Made the callback as a separate function so it can be reused on multiple event triggers
    function wwofProcessAddToCart() {

        disableSearchCommandFields();
        disabledPagingLinks();

        var $this = $( this ),
            $current_tr = $this.closest( 'tr' ),
            productType = $current_tr.find( ".product_meta_col" ).find( ".product_type" ).text(),
            productID = $current_tr.find( ".product_meta_col" ).find( ".main_product_id" ).text(),
            variationID = $current_tr.find( ".product_title_col" ).find( ".product_variations" ).find( "option:selected" ).val() || 0,
            quantityField = $current_tr.find( ".product_quantity_col" ).find( ".qty" ),
            quantity = parseInt( quantityField.val() );

        // validate quantity
        if ( ! wwofValidateQuantityField( quantity , quantityField ) ) {
            enabledSearchCommandFields();
            enablePagingLinks();
            return;
        }

        $this
            .attr( 'disabled' , 'disabled' )
            .siblings( '.spinner' )
            .removeClass( 'success' )
            .removeClass( 'error' )
            .css( 'display' , 'inline-block' );

        if ( productType == "variable" && variationID == 0 ) {

            vex.dialog.alert( { unsafeMessage : Options.no_variation_message } );

            enabledSearchCommandFields();
            enablePagingLinks(); // We re-enable paging links as no new paging links are added when an error occur during ajax request

            $this
                .removeAttr( 'disabled' )
                .siblings( '.spinner' )
                .addClass( 'error' );

            fadeOutElement( $this.siblings( '.spinner' ) , 6000 );

            return false;

        }

        // Generate product add-on data if any
        var addon  = get_product_addon_data( $current_tr ),
            data   = new FormData();

        data.append( 'action' , 'wwof_add_product_to_cart' );
        data.append( 'product_type' , productType );
        data.append( 'product_id' , productID );
        data.append( 'variation_id' , variationID );
        data.append( 'quantity' , quantity );

        if ( addon && addon.errors.length > 0 ) {

            var err_msg = 'Please fill required product add-ons <br><br>';

            for ( var i = 0 ; i < addon.errors.length ; i++ )
                err_msg += '<b>' + addon.errors[ i ] + '</b><br>';

            vex.dialog.alert( { unsafeMessage : err_msg } );

            $this
                .removeAttr( 'disabled' )
                .siblings( '.spinner' )
                .addClass( 'error' );

            fadeOutElement( $this.siblings( '.spinner' ) , 3000 );

            return false;

        } else if ( addon ) {

            for ( key in addon.addon )
                data.append( addon.addon[ key ][ 'name' ] , addon.addon[ key ][ 'value' ] );
        }


        // Ref: https://github.com/Foliotek/ajaxq
        //      https://foliotek.github.io/AjaxQ/
        $.ajaxq( 'queue' , {
                url         :   Ajax.ajaxurl,
                type        :   'POST',
                data        :   data,
                dataType    :   'json',
                processData :   false,
                contentType :   false,
                success: function( data , textStatus , jqXHR ) {

                    if ( data.status == 'success' ) {

                        enabledSearchCommandFields();
                        enablePagingLinks(); // We re-enable paging links as no new paging links are added when an error occured during ajax request

                        $wwofProductListingContainer
                            .find( ".wwof_cart_sub_total" )
                            .replaceWith( data.cart_subtotal_markup );

                        $this
                            .removeAttr( 'disabled' )
                            .siblings( '.spinner' )
                            .addClass( 'success' );

                        fadeOutElement( $this.siblings( '.spinner' ) , 3000 );

                        // Update cart widget
                        var fragments = data.fragments,
                            cart_hash = data.cart_hash;

                        // Block fragments class
                        blockFragments( fragments );

                        // Replace fragments
                        replaceFragments( fragments );

                        // Unblock fragments class
                        unblockFragments( fragments );

                        //Trigger event so themes can refresh other areas
                        $( 'body' ).trigger( 'added_to_cart', [ fragments, cart_hash, $this ] );
                        $( 'body' ).trigger( 'adding_to_cart' );

                        // display view cart button after clicking add to cart
                        if ( $current_tr.find( '.added_to_cart' ).length == 0 ) {

                            var view_cart        = ( typeof wc_add_to_cart_params != 'undefined' ) ? wc_add_to_cart_params.i18n_view_cart : Options.view_cart;
                            var cart_url         = Options.cart_url;
                                view_cart_button = '<a href="' + cart_url + '" class="added_to_cart button wc-forward" title="' + view_cart + '">' + view_cart + '</a>';
                            $( view_cart_button ).insertAfter( $this );
                        }



                    } else if ( data.status == 'failed' ) {

                        var err_msg = data.error_message;

                        if ( typeof data.wc_errors !== 'undefined' && data.wc_errors.length > 0 ) {

                            err_msg += '<br><br>';

                            for ( key in data.wc_errors )
                                err_msg += '<div class="wc-error">' + data.wc_errors[ key ] + '</div>';
                        }

                        vex.dialog.alert( { unsafeMessage : err_msg } );

                        enabledSearchCommandFields();
                        enablePagingLinks(); // We re-enable paging links as no new paging links are added when an error occured during ajax request

                        $this
                            .removeAttr( 'disabled' )
                            .siblings( '.spinner' )
                            .addClass( 'error' );

                        fadeOutElement( $this.siblings( '.spinner' ) , 6000 );

                    }

                },
                error: function( jqXHR , textStatus , errorThrown ) {

                    console.log( jqXHR );

                    vex.dialog.alert( { unsafeMessage : errorThrown } );

                    enabledSearchCommandFields();
                    enablePagingLinks(); // We re-enable paging links as no new paging links are added when an error occured during ajax request

                    $this
                        .removeAttr( 'disabled' )
                        .siblings( '.spinner' )
                        .addClass( 'error' );

                    fadeOutElement( $this.siblings( '.spinner' ) , 6000 );

                }
            });

    }

    $wwofProductListingContainer.on( 'change' , '.wwof_add_to_cart_checkbox:checked' , function() {

        var quantityField = $(this).closest( 'tr' ).find( '.quantity input[type="number"]' ),
            quantity      = parseInt( quantityField.val() );

        if ( ! wwofValidateQuantityField( quantity, quantityField ) )
            $(this).prop( 'checked' , false );

    } );

    // 7
    $wwofProductListingContainer.on( 'click' , '.wwof_bulk_add_to_cart_button' , function () {

        var $this     = $( this ),
            formData  = new FormData(),
            products  = [],
            files     = [];

        $this
            .attr( 'disabled' , 'disabled' )
            .siblings( '.spinner' )
                .css( 'display' , 'inline-block' );

        disableSearchCommandFields();
        disabledPagingLinks();

        $wwofProductListingContainer
            .find( ".wwof_add_to_cart_checkbox" )
            .each( function ( index ) {

                if ( $( this ).is( ":checked" ) ) {

                    var $current_tr    = $( this ).closest( 'tr'),
                        productType    = $current_tr.find( ".product_meta_col" ).find( ".product_type" ).text(),
                        productID      = $current_tr.find( ".product_meta_col" ).find( ".main_product_id" ).text(),
                        variationID    = $current_tr.find( ".product_title_col" ).find( ".product_variations" ).find( "option:selected" ).val() || 0,
                        quantity       = $current_tr.find( ".product_quantity_col" ).find( ".qty" ).val(),
                        addCurrentItem = true;

                    if ( productType == "variable" && variationID == 0 )
                        addCurrentItem = false;

                    if ( addCurrentItem ) {

                        products.push( { name : 'products[' + productID + '][productType]' , value : productType } );
                        products.push( { name : 'products[' + productID + '][productID]' , value : productID } );
                        products.push( { name : 'products[' + productID + '][variationID]' , value : variationID } );
                        products.push( { name : 'products[' + productID + '][quantity]' , value : quantity } );

                        if ( addon = get_product_addon_data( $current_tr ) ) {

                            for ( key in addon.addon ) {

                                if ( addon.addon[ key ][ 'field_type'] == 'file' ) {

                                    files.push( { name : productID + '_' + addon.addon[ key ][ 'name' ] , value : addon.addon[ key ][ 'value' ] } );

                                } else {

                                    addon_name = addon.addon[ key ][ 'name' ].replace( '[' , '][' );
                                    addon_name = ( addon_name.slice(-1) == ']' ) ? addon_name : addon_name + ']';

                                    products.push( { name : 'products[' + productID + '][' + addon_name , value : addon.addon[ key ][ 'value' ] } );
                                }

                            }

                        }

                    }
                }

            });

        if ( products.length > 0 ) {

            // append action to FormData()
            formData.append( 'action' , 'wwof_add_products_to_cart' );

            // append each product properties to FormData()
            for ( key in products )
                formData.append( products[ key ][ 'name' ] , products[ key ][ 'value' ] );

            if ( files.length > 0 ) {

                for( key in files )
                    formData.append( files[ key ][ 'name' ] , files[ key ][ 'value' ] );
            }

            wwofFrontEndAjaxServices.addProductsToCart( formData )
                .done ( function( data , textStatus , jqXHR ) {

                    if ( data.status == 'success' ) {

                        // There are products that failed to be added to the cart
                        if ( data.failed_to_add.length > 0 ) {

                            var err_msg = '<h3>' + Options.errors_on_adding_products + '</h3><br>';

                            for ( var i = 0 ; i < data.failed_to_add.length ; i++ )
                                err_msg += data.failed_to_add[ i ].error_message + '<br><br>';

                            if ( typeof data.wc_errors !== 'undefined' && data.wc_errors.length > 0 ) {

                                for ( key in data.wc_errors )
                                    err_msg += '<div class="wc-error">' + data.wc_errors[ key ] + '</div>';
                            }

                            vex.dialog.alert( { unsafeMessage : err_msg } );

                        }

                        $this
                            .siblings( ".products_added" )
                                .css( "display" , "inline-block" )
                            .find( "b" )
                                .text( data.total_added )
                            .end().end()
                            .siblings( ".view_cart" )
                                .css( "display" , "block" );

                        fadeOutElement( $this.siblings( ".products_added" ) , 8000 );

                        $wwofProductListingContainer
                            .find( ".wwof_cart_sub_total" )
                            .replaceWith( data.cart_subtotal_markup );

                        // Update cart widget
                        var fragments = data.fragments,
                            cart_hash = data.cart_hash;

                        // Block fragments class
                        blockFragments( fragments );

                        // Replace fragments
                        replaceFragments( fragments );

                        // Unblock fragments class
                        unblockFragments( fragments );

                        //Trigger event so themes can refresh other areas
                        $( 'body' ).trigger( 'added_to_cart', [ fragments, cart_hash, $this ] );
                        $( 'body' ).trigger( 'adding_to_cart' );

                    } else if (data.status == 'failed' ) {

                        console.log( data );
                        vex.dialog.alert( { unsafeMessage : data.error_message } );

                    }

                })
                .fail ( function( jqXHR , textStatus , errorThrown ) {

                    console.log( jqXHR.responseText );
                    vex.dialog.alert( { unsafeMessage : errorThrown } );

                })
                .always ( function () {

                    enabledSearchCommandFields();
                    enablePagingLinks(); // We re-enable paging links as no new paging links are added when an error occured during ajax request

                    $this
                        .removeAttr( 'disabled' )
                        .siblings( '.spinner' )
                        .css( 'display' , 'none' );

                    $wwofProductListingContainer
                        .find( ".wwof_add_to_cart_checkbox" )
                        .removeAttr( 'checked' );

                    // reset the quantity fields back to their allowed minimum value
                    $wwofProductListingContainer
                        .find( ".quantity input[type='number']" )
                        .each( function() {

                            var min = $(this).prop( 'min' );
                            $(this).val( min );

                        });

                });

        } else {

            enabledSearchCommandFields();
            enablePagingLinks(); // We re-enable paging links as no new paging links are added when an error occured during ajax request

            $this
                .removeAttr( 'disabled' )
                .siblings( '.spinner' )
                    .css( 'display' , 'none' );

        }

    });




    /*
     |------------------------------------------------------------------------------------------------------------------
     | Product Add-on
     |------------------------------------------------------------------------------------------------------------------
     */

    $( 'body' ).on( 'click' , '.wwof-product-add-ons-title' , function() {

        var $this           = $( this ),
            $product_addons = $this.siblings( '.wwof-product-add-ons' );

        if ( $product_addons.is( ':visible' ) ) {

            $product_addons.slideUp( 'fast' , function() {

                $this
                    .find( '.dashicons' )
                    .removeClass( 'dashicons-arrow-up-alt2' )
                    .addClass( 'dashicons-arrow-down-alt2' );

            } );

        } else {

            $product_addons.slideDown( 'fast' , function() {

                $this
                    .find( '.dashicons' )
                    .removeClass( 'dashicons-arrow-down-alt2' )
                    .addClass( 'dashicons-arrow-up-alt2' );

            } );

        }

    });

    // WWOF-50
    $wwofProductListingContainer.on( "change", ".product_quantity_col input.qty", function( e ){

        $( this ).parents( ".product_quantity_col" ).siblings( ".product_row_action" ).find( "input.wwof_add_to_cart_checkbox" ).prop( "checked", true ).trigger( 'change' );

    });

    /*
     |------------------------------------------------------------------------------------------------------------------
     | Exe
     |------------------------------------------------------------------------------------------------------------------
     |
     | 1. Load product listing on load
     | 2. On every product item inserted to product listing, attach fancy box to its product links
     | 3. Set default values to search fields
     |------------------------------------------------------------------------------------------------------------------
     */

    var catFilter = $.trim( $wwofProductListingFilter.find( "#wwof_product_search_category_filter" ).find( "option:selected" ).val() );

    // 1
    LoadProductListing( 1 , "" , catFilter , $shortcodeAtts , true );

    // 2
    $wwofProductListingContainer.on( "DOMNodeInserted" , "#wwof_product_listing_ajax_content" , function( e ) {

        // Get the e.target and wrap it in jquery to make it a jquery object
        var $element = $( e.target );

        // Only attach fancy box if settings does allow it
        if ( Options.display_details_on_popup == 'yes' ) {

            $element.on( "click", "a.product_link", function(e){
                e.preventDefault();

                productData = $( this ).parents( 'tr' ).find( '.product_meta_col' ).attr( 'data-product_variations' );

                // Attach fancy box feature to product links
                $.fancybox( this, {
                    maxWidth    :   650,
                    maxHeight   :   600,
                    fitToView   :   false,
                    width       :   '60%',
                    height      :   '60%',
                    autoSize    :   false,
                    closeClick  :   false,
                    openEffect  :   'none',
                    closeEffect :   'none',
                    type        :   'ajax',
                    helpers     :   {
                        overlay :   {
                            locked      :   true, // prevents scrolling on the background
                            opacity     :   0.5
                        }
                    },
                    afterShow   :   function() {
                        $( 'body table.dummy-table' ).find( '.product_meta_col' ).attr( 'data-product_variations' , productData );
                        $( 'body .wwof-popup-product-summary .product_variations' ).trigger( 'change' );

                        if ( productData ) {
                            productData = JSON.parse( productData );
                            variationSimilarPriceCheck( productData );
                        }

                        fancyboxIsOpen = true;

                    },
                    afterClose : function() {
                        fancyboxIsOpen = false;
                    }
                });

                return false;

            });

        }

        // Trigger product variation select box change event on load
        $element.find( '.product_variations' ).data( 'init-load' , true ).trigger( 'change' );

    });

    // When pagination is disabled, lazy load products on scroll
    $( window ).scroll( function() {

        // skip if pagination is not disabled
        if ( Options.disable_pagination !== 'yes'
             || $wwofProductListingContainer.data( 'init-content' ) !== true
             || $wwofProductListingContainer.data( 'content-processing' ) === true
             || $wwofProductListingContainer.data( 'all-products-loaded' ) === true )
            return;

        var windowTop    = $(window).scrollTop(),
            windowHeight = $(window).height(),
            sampleRow    = $wwofProductListingContainer.find( 'tbody tr:first-child' ),
            footerTop    = $wwofProductListingContainer.find( 'tfoot' ).offset().top,
            footerOffset = ( parseInt( Options.products_per_page ) / 2 ) * sampleRow.height(),
            tableColumns = sampleRow.find( 'td' ).length,
            currPage     = $wwofProductListingContainer.data( 'paged' ),
            paged        = currPage + 1,
            search       = $.trim( $wwofProductListingFilter.find( "#wwof_product_search_form" ).val() ),
            category     = $.trim( $wwofProductListingFilter.find( "#wwof_product_search_category_filter" ).find( "option:selected" ).val() ),
            tableBody    = $wwofProductListingContainer.find( "#wwof_product_listing_table > tbody" ),
            maxPages     = $( "#wwof_product_listing_pagination" ).data( "max-pages" );

        if ( windowTop + windowHeight >= footerTop - footerOffset ) {

            if( paged <= maxPages ){

                $wwofProductListingContainer.data( 'content-processing' , true );
                tableBody.append( '<tr class="lazyload-loading"><td colspan="' + tableColumns + '"><span></span></td></tr>' );

                wwofFrontEndAjaxServices.displayProductListing( paged , search , category , $shortcodeAtts )
                .done( function( data , textStatus , jqXHR ) {

                    if ( data ) {

                        tableBody.append( data ).find( 'tr.lazyload-loading' ).remove();
                        $wwofProductListingContainer.data( 'paged' , paged ).data( 'content-processing' , false );

                    }

                    $( window ).scroll();

                } );

            } else {

                tableBody.find( 'tr.lazyload-loading' ).remove();
                $wwofProductListingContainer.data( 'content-processing' , false ).data( 'all-products-loaded' , true );

            }

        }

    });

    // 3.
    $wwofProductListingFilter.find( "#wwof_product_search_form" ).val( '' );
    //$wwofProductListingFilter.find( "#wwof_product_search_category_filter" ).find( "option:first" ).attr( 'selected' , 'selected' );

    // Initialize Vex Library
    vex.defaultOptions.className = 'vex-theme-plain';

});

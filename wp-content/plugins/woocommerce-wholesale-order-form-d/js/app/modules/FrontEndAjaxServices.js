/**
 * A function implementing the revealing module pattern to house all ajax request. It implements the ajax promise methodology
 * @return {Ajax Promise} promise it returns a promise, I promise that #lamejoke
 *
 * Info:
 * Ajax is a variable injected by the server inside this js file. It has an attribute named ajaxurl which points
 * to admin ajax url for ajax call purposes
 */
var wwofFrontEndAjaxServices    =   function(){

    var displayProductListing        =   function( paged , search , catFilter , shortcodeAtts ) {

            return jQuery.ajax({
                url         :   Ajax.ajaxurl,
                type        :   "POST",
                data        :   { action : "wwof_display_product_listing" , "paged" : paged , "search" : search , "cat_filter" : catFilter , "shortcode_atts" : shortcodeAtts },
                dataType    :   "html"
            });

        },
        addProductsToCart            =   function ( formData ) {

            return jQuery.ajax({
                url         :   Ajax.ajaxurl,
                type        :   "POST",
                data        :   formData,
                dataType    :   "json",
                processData :   false,
                contentType :   false,
            });

        },
        getVariationQuantityInputArgs =   function ( variation_id ) {

            return jQuery.ajax({
                url         :   Ajax.ajaxurl,
                type        :   "POST",
                data        :   { action : "wwof_get_variation_quantity_input_args" , "variation_id" : variation_id },
                dataType    :   "json"
            });
        };

    return {
        displayProductListing           :   displayProductListing,
        addProductsToCart               :   addProductsToCart,
        getVariationQuantityInputArgs   :   getVariationQuantityInputArgs
    }

}();

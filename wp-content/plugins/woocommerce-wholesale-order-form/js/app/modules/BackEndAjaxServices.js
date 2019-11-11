/**
 * A function implementing the revealing module pattern to house all ajax request. It implements the ajax promise methodology
 * @return {Ajax Promise} promise it returns a promise, I promise that #lamejoke
 *
 * Info:
 * ajaxurl points to admin ajax url for ajax call purposes. Added by wp when script is wp enqueued
 */
var wwofBackEndAjaxServices = function() {

    var createWholesalePage =   function() {

            return jQuery.ajax({
                url         :   ajaxurl,
                type        :   "POST",
                data        :   { action : "wwof_create_wholesale_page" },
                dataType    :   "json"
            });

        },
        saveWWOFLicenseDetails = function( licenseDetails ) {

            return jQuery.ajax({
                url         :   ajaxurl,
                type        :   "POST",
                data        :   { 
                    action        : 'wwof_activate_license',
                    license_email : licenseDetails.license_email,
                    license_key   : licenseDetails.license_key,
                    ajax_nonce    : licenseDetails.nonce
                },
                dataType    :   "json"
            });

        };

    return {
        createWholesalePage     :   createWholesalePage,
        saveWWOFLicenseDetails  :   saveWWOFLicenseDetails
    }

}();
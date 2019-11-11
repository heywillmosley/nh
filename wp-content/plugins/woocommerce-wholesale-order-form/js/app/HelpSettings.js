jQuery( document ).ready( function( $ ) {

    var errorMessageDuration = '10000',
        successMessageDuration = '5000';

    // Help Section
    $( "#wwof_help_create_wholesale_page" )
        .removeAttr( 'disabled' ) // On load
        .click( function() {

            var $this = $( this );

            $this
                .attr( 'disabled' , 'disabled' )
                .siblings( '.spinner' )
                    .css( 'display' , 'inline-block' );

            wwofBackEndAjaxServices.createWholesalePage()
                .done( function( data , textStatus , jqXHR ) {

                    if ( data.status == 'success' ) {

                        toastr.success( '' , WPMessages.success_message , { "closeButton" : true , "showDuration" : successMessageDuration } );

                    } else {

                        toastr.error( data.error_message , WPMessages.failure_message , { "closeButton" : true , "showDuration" : errorMessageDuration } );

                        console.log( WPMessages.failure_message );
                        console.log( jqXHR );
                        console.log( '----------' );

                    }

                })
                .fail( function( jqXHR , textStatus , errorThrown ) {

                    toastr.error( jqXHR.responseText , WPMessages.failure_message , { "closeButton" : true , "showDuration" : errorMessageDuration } );

                    console.log( WPMessages.failure_message );
                    console.log( jqXHR );
                    console.log( '----------' );

                })
                .always( function() {

                    $this
                        .removeAttr( 'disabled' )
                        .siblings( '.spinner' )
                            .css( 'display' , 'none' );

                });

    });

});
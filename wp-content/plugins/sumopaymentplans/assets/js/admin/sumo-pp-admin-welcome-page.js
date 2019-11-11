jQuery( function ( $ ) {
    $( document ).on( 'click' , '.tab_1' , function () {
        $( '.tab_1' ).addClass( 'active_welcome' ) ;
        $( '.tab_2' ).removeClass( 'active_welcome' ) ;
        $( '.tab_3' ).removeClass( 'active_welcome' ) ;
        $( '.con_1' ).show() ;
        $( '.con_2' ).hide() ;
        $( '.con_3' ).hide() ;
    } ) ;

    $( document ).on( 'click' , '.tab_2' , function () {
        $( '.tab_1' ).removeClass( 'active_welcome' ) ;
        $( '.tab_2' ).addClass( 'active_welcome' ) ;
        $( '.tab_3' ).removeClass( 'active_welcome' ) ;
        $( '.con_1' ).hide() ;
        $( '.con_2' ).show() ;
        $( '.con_3' ).hide() ;
    } ) ;

    $( document ).on( 'click' , '.tab_3' , function () {
        $( '.tab_1' ).removeClass( 'active_welcome' ) ;
        $( '.tab_2' ).removeClass( 'active_welcome' ) ;
        $( '.tab_3' ).addClass( 'active_welcome' ) ;
        $( '.con_1' ).hide() ;
        $( '.con_2' ).hide() ;
        $( '.con_3' ).show() ;
    } ) ;
} ) ;

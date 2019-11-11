/* global footable */

jQuery( function ( $ ) {

    // footable is required to continue, ensure the object exists
    if ( typeof footable === 'undefined' ) {
        return false ;
    }

    $( '._sumo_pp_footable' ).footable().bind( 'footable_filtering' , function ( e ) {
        var $selected = $( '.filter-status' ).find( ':selected' ).text() ;

        if ( $selected && $selected.length > 0 ) {
            e.filter += ( e.filter && e.filter.length > 0 ) ? ' ' + $selected : $selected ;
            e.clear = ! e.filter ;
        }
    } ) ;

    $( '#change-page-size' ).change( function ( e ) {
        e.preventDefault() ;
        var $pageSize = $( this ).val() ;

        $( '.footable' ).data( 'page-size' , $pageSize ) ;
        $( '.footable' ).trigger( 'footable_initialized' ) ;
    } ) ;
} ) ;
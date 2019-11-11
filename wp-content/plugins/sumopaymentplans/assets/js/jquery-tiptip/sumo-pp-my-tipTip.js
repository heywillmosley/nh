/* global sumo_pp_jquery_tiptip */

jQuery( function ( $ ) {
    // sumo_pp_jquery_tiptip is required to continue, ensure the object exists
    if ( typeof sumo_pp_jquery_tiptip === 'undefined' ) {
        return false ;
    }

    $( '._sumo_pp_tips' ).tipTip( {
        'attribute' : 'data-tip' ,
        'fadeIn' : 50 ,
        'fadeOut' : 50 ,
        'delay' : 200
    } ) ;
} ) ;
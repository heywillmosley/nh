/* global sumo_pp_my_account_page */

jQuery( function ( $ ) {
    // sumo_pp_my_account_page is required to continue, ensure the object exists
    if ( typeof sumo_pp_my_account_page === 'undefined' ) {
        return false ;
    }

    var $page = $( '.woocommerce-MyAccount-content' ).closest( 'div' ) ;

    var is_blocked = function ( $node ) {
        return $node.is( '.processing' ) || $node.parents( '.processing' ).length ;
    } ;

    /**
     * Block a node visually for processing.
     *
     * @param {JQuery Object} $node
     */
    var block = function ( $node ) {
        if ( ! is_blocked( $node ) ) {
            $node.addClass( 'processing' ).block( {
                message : null ,
                overlayCSS : {
                    background : '#fff' ,
                    opacity : 0.6
                }
            } ) ;
        }
    } ;

    /**
     * Unblock a node after processing is complete.
     *
     * @param {JQuery Object} $node
     */
    var unblock = function ( $node ) {
        $node.removeClass( 'processing' ).unblock() ;
    } ;

    var my_account = {
        /**
         * Manage My Payments Table Editable UI events.
         */
        init : function () {
            $( document ).on( 'click' , 'table.payment_activities #prevent_more_notes' , this.preventMorePaymentNotes ) ;
        } ,
        preventMorePaymentNotes : function ( evt ) {
            var $this = $( evt.currentTarget ) ;

            if ( 'more' === $this.attr( 'data-flag' ) ) {
                $this.text( sumo_pp_my_account_page.show_less_notes_label ) ;
                $this.attr( 'data-flag' , 'less' ) ;

                $( '._alert_box' ).slideDown() ;
            } else {
                $this.text( sumo_pp_my_account_page.show_more_notes_label ) ;
                $this.attr( 'data-flag' , 'more' ) ;

                $( '._alert_box' ).css( 'display' , 'none' ) ;
                $( '.default_notes0' ).slideDown() ;
                $( '.default_notes1' ).slideDown() ;
                $( '.default_notes2' ).slideDown() ;
            }
        }
    } ;

    my_account.init() ;
} ) ;
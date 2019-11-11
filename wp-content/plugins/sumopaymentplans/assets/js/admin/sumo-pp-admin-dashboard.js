/* global sumo_pp_admin_dashboard, ajaxurl */

jQuery( function ( $ ) {

    // sumo_pp_admin_dashboard is required to continue, ensure the object exists
    if ( typeof sumo_pp_admin_dashboard === 'undefined' ) {
        return false ;
    }

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

    var $notes_div = $( '#_sumo_pp_payment_notes' ).closest( 'div' ) ;

    $( 'table._sumo_pp_footable' )
            .on( 'click' , 'a.add' , function () {
                var rowID = $( this ).closest( 'table' ).find( 'tbody tr' ).length ;
                var period_options = 'period_' + rowID ;

                $.each( sumo_pp_admin_dashboard.get_period_options , function ( value , label ) {
                    period_options += '<option value="' + value.toString() + '">' + label.toString() + '</option>'
                } ) ;

                var $price_type ;
                if ( 'fixed-price' === $( 'p.price_type' ).find( 'select' ).val() ) {
                    $price_type = $( '#_sumo_pp_hidden_datas' ).data( 'currency_symbol' ) ;
                } else {
                    $price_type = '%' ;
                }

                $( '<tr>\n\
                    <td><input class="payment_amount" type="number" min="0.00" step="0.01" name="_sumo_pp_scheduled_payment[' + rowID + ']"/><span>' + $price_type + '</span></td>\n\
                    <td>After <input type="number" min="1" name="_sumo_pp_scheduled_duration_length[' + rowID + ']" value="1"/>\n\
                              <select name="_sumo_pp_scheduled_period[' + rowID + ']">' + period_options + '</select>\n\
                    </td>\n\
                    <td><a href="#" class="remove_row button">X</a></td>\n\
            </tr>' ).appendTo( $( this ).closest( 'table' ).find( 'tbody' ) ) ;
                return false ;
            } )
            .on( 'change' , '.payment_amount' , function () {
                var total = 0 ;
                $( this ).closest( 'table' ).find( '.payment_amount' ).each( function () {
                    total = total + parseFloat( $( this ).val() || 0 ) ;
                } ) ;

                var $payment_amount ;
                if ( 'fixed-price' === $( 'p.price_type' ).find( 'select' ).val() ) {
                    $payment_amount = $( '#_sumo_pp_hidden_datas' ).data( 'currency_symbol' ) + total ;
                } else {
                    $payment_amount = total + '%' ;
                }

                $( this ).closest( 'table' ).find( 'span.total_payment_amount' ).text( $payment_amount ) ;
            } )
            .on( 'click' , 'a.remove_row' , function () {
                $( this ).closest( 'tr' ).remove() ;
                return false ;
            } ) ;

    $( document ).on( 'change' , 'p.price_type select:eq(0)' , function () {
        var $price_type , $payment_amount ;
        if ( 'fixed-price' === this.value ) {
            $price_type = $( '#_sumo_pp_hidden_datas' ).data( 'currency_symbol' ) ;
            $payment_amount = $price_type + $( 'table._sumo_pp_footable' ).find( 'span.total_payment_amount' ).text().replace( $price_type , '' ).replace( '%' , '' ) ;
        } else {
            $price_type = '%' ;
            $payment_amount = $( 'table._sumo_pp_footable' ).find( 'span.total_payment_amount' ).text().replace( $price_type , '' ).replace( $( '#_sumo_pp_hidden_datas' ).data( 'currency_symbol' ) , '' ) + $price_type ;
        }

        $( 'table._sumo_pp_footable' ).find( 'tr' ).each( function () {
            $( this ).find( 'td:eq(0) span' ).text( $price_type ) ;
        } ) ;
        $( 'table._sumo_pp_footable' ).find( 'span.total_payment_amount' ).text( $payment_amount ) ;
    } ) ;

    if ( 'sumo_payment_plans' === sumo_pp_admin_dashboard.get_post_type && 'percent' === $( 'p.price_type' ).find( 'select' ).val() ) {
        $( document ).on( 'submit' , 'form#post' , function () {
            $( 'div#submitpost' ).find( '.spinner' ).remove() ;
            $( 'div#submitpost' ).find( '#publish' ).removeClass( 'disabled' ) ;

            if ( $( 'table._sumo_pp_footable' ).find( 'span.total_payment_amount' ).text() < 100 ) {
                window.alert( sumo_pp_admin_dashboard.admin_notice ) ;
                return false ;
            }
            return true ;
        } ) ;
    }

    $( document ).on( 'click' , 'a.add_note' , function ( evt ) {
        evt.preventDefault() ;
        var $content = $( '#payment_note' ).val() ;
        var $post_id = $( this ).attr( 'data-id' ) ;

        $.blockUI.defaults.overlayCSS.cursor = 'wait' ;
        block( $notes_div ) ;

        $.ajax( {
            type : 'POST' ,
            url : sumo_pp_admin_dashboard.wp_ajax_url ,
            data : {
                action : '_sumo_pp_add_payment_note' ,
                security : sumo_pp_admin_dashboard.add_note_nonce ,
                content : $content ,
                post_id : $post_id
            } ,
            success : function ( data ) {
                $( 'ul._sumo_pp_payment_notes' ).prepend( data ) ;
                $( '#payment_note' ).val( '' ) ;
            } ,
            complete : function () {
                unblock( $notes_div ) ;
            }
        } ) ;
    } ) ;

    $( document ).on( 'click' , 'a.delete_note' , function () {
        var $this = $( this ) ;
        var $note_to_delete = $this.parent().parent().attr( 'rel' ) ;

        $.blockUI.defaults.overlayCSS.cursor = 'wait' ;
        block( $notes_div ) ;

        $.ajax( {
            type : 'POST' ,
            url : sumo_pp_admin_dashboard.wp_ajax_url ,
            data : {
                action : '_sumo_pp_delete_payment_note' ,
                security : sumo_pp_admin_dashboard.delete_note_nonce ,
                delete_id : $note_to_delete
            } ,
            success : function ( data ) {
                if ( data === true ) {
                    $this.parent().parent().remove() ;
                }
            } ,
            complete : function () {
                unblock( $notes_div ) ;
            }
        } ) ;
        return false ;
    } ) ;

    $( document ).on( 'click' , 'div.view_next_payable_order > a' , function ( evt ) {
        evt.preventDefault() ;
        $( 'div.view_next_payable_order > p' ).slideToggle() ;
    } ) ;
} ) ;
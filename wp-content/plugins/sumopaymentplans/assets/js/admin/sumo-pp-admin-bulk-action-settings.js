/* global sumo_pp_admin_bulk_action_settings, ajaxurl */

jQuery( function ( $ ) {

    // sumo_pp_admin_bulk_action_settings is required to continue, ensure the object exists
    if ( typeof sumo_pp_admin_bulk_action_settings === 'undefined' ) {
        return false ;
    }

    var toggle_events = {
        /**
         * Perform Bulk Action Toggle events.
         */
        init : function ( ) {

            this.triggerOnPageLoad( ) ;

            $( document ).on( 'change' , '#get_product_select_type' , this.toggleProductOrCategory ) ;
            $( document ).on( 'change' , '#enable_sumopaymentplans' , this.toggle_payment_settings ) ;
            $( document ).on( 'change' , '#payment_type' , this.toggle_payment_type ) ;
            $( document ).on( 'change' , '#apply_global_settings' , this.toggle_global_settings ) ;
            $( document ).on( 'change' , '#deposit_type' , this.toggle_deposit_type ) ;
            $( document ).on( 'change' , '#deposit_price_type' , this.toggle_deposit_price_type ) ;
            $( document ).on( 'change' , '#pay_balance_type' , this.toggle_pay_balance_type ) ;

        } ,
        triggerOnPageLoad : function () {
            $( '#get_selected_categories' ).select2( ) ;
            $( '#pay_balance_before' ).datepicker( {
                minDate : 0 ,
                changeMonth : true ,
                dateFormat : 'yy-mm-dd' ,
                numberOfMonths : 1 ,
                showButtonPanel : true ,
                defaultDate : '' ,
                showOn : 'focus' ,
                buttonImageOnly : true
            } ) ;

            this.getProductOrCategoryType( $( '#get_product_select_type' ).val( ) ) ;
            this.get_payment_settings( $( '#enable_sumopaymentplans' ).is( ':checked' ) ) ;

        } ,
        toggleProductOrCategory : function ( evt ) {
            var $type = $( evt.currentTarget ).val( ) ;

            toggle_events.getProductOrCategoryType( $type ) ;
        } ,
        toggle_payment_settings : function ( evt ) {
            var $payment_settings = $( evt.currentTarget ).is( ':checked' ) ;

            toggle_events.get_payment_settings( $payment_settings ) ;
        } ,
        toggle_payment_type : function ( evt ) {
            var $payment_type = $( evt.currentTarget ).val() ;

            toggle_events.get_payment_type( $payment_type ) ;
        } ,
        toggle_global_settings : function ( evt ) {
            var $apply_global_settings = $( evt.currentTarget ).is( ':checked' ) ;

            toggle_events.get_global_settings( $apply_global_settings ) ;
        } ,
        toggle_deposit_type : function ( evt ) {
            var $deposit_type = $( evt.currentTarget ).val() ;

            toggle_events.get_deposit_type( $deposit_type ) ;
        } ,
        toggle_deposit_price_type : function ( evt ) {
            var $deposit_price_type = $( evt.currentTarget ).val() ;

            toggle_events.get_deposit_price_type( $deposit_price_type ) ;
        } ,
        toggle_pay_balance_type : function ( evt ) {
            var $pay_balance_type = $( evt.currentTarget ).val() ;

            toggle_events.get_pay_balance( $pay_balance_type ) ;
        } ,
        getProductOrCategoryType : function ( $type ) {
            $type = $type || '' ;

            $( '#get_selected_products' ).closest( 'tr' ).hide( ) ;
            $( '#get_selected_categories' ).closest( 'tr' ).hide( ) ;

            switch ( $type ) {
                case 'selected-products':
                    $( '#get_selected_products' ).closest( 'tr' ).show( ) ;
                    $( '#get_selected_categories' ).closest( 'tr' ).hide( ) ;
                    break ;
                case 'selected-categories':
                    $( '#get_selected_products' ).closest( 'tr' ).hide( ) ;
                    $( '#get_selected_categories' ).closest( 'tr' ).show( ) ;
                    break ;
            }
        } ,
        get_payment_settings : function ( $payment_settings ) {
            $payment_settings = $payment_settings || false ;

            if ( true === $payment_settings ) {
                $( 'table' ).find( 'tr.bulk-fields-wrapper' ).show() ;
                toggle_events.get_payment_type( $( '#payment_type' ).val() ) ;
            } else {
                $( 'table' ).find( 'tr.bulk-fields-wrapper' ).hide() ;
                paymentPlansSelector.hide() ;
            }
        } ,
        get_payment_type : function ( $payment_type , $do_not_apply_gobal ) {
            $payment_type = $payment_type || 'payment-plans' ;
            $do_not_apply_gobal = $do_not_apply_gobal || false ;

            $( '#deposit_type' ).closest( 'tr' ).hide() ;
            $( '#deposit_price_type' ).closest( 'tr' ).hide() ;
            $( '#pay_balance_type' ).closest( 'tr' ).hide() ;
            $( '#set_expired_deposit_payment_as' ).closest( 'tr' ).hide() ;
            $( '#fixed_deposit_price' ).closest( 'tr' ).hide() ;
            $( '#fixed_deposit_percent' ).closest( 'tr' ).hide() ;
            $( '#min_deposit' ).closest( 'tr' ).hide() ;
            $( '#max_deposit' ).closest( 'tr' ).hide() ;
            paymentPlansSelector.show() ;

            if ( 'pay-in-deposit' === $payment_type ) {
                $( '#deposit_type' ).closest( 'tr' ).show() ;
                $( '#deposit_price_type' ).closest( 'tr' ).show() ;
                $( '#pay_balance_type' ).closest( 'tr' ).show() ;
                $( '#fixed_deposit_price' ).closest( 'tr' ).show() ;
                $( '#fixed_deposit_percent' ).closest( 'tr' ).show() ;
                $( '#min_deposit' ).closest( 'tr' ).show() ;
                $( '#max_deposit' ).closest( 'tr' ).show() ;
                paymentPlansSelector.hide() ;

                toggle_events.get_deposit_type( $( '#deposit_type' ).val() ) ;
            }
            if ( false === $do_not_apply_gobal ) {
                toggle_events.get_global_settings( $( '#apply_global_settings' ).is( ':checked' ) ) ;
            }
        } ,
        get_global_settings : function ( $apply_global_settings ) {
            $apply_global_settings = $apply_global_settings || false ;

            if ( true === $apply_global_settings ) {
                $( '#force_deposit' ).closest( 'tr' ).hide() ;
                $( '#deposit_type' ).closest( 'tr' ).hide() ;
                $( '#deposit_price_type' ).closest( 'tr' ).hide() ;
                $( '#pay_balance_type' ).closest( 'tr' ).hide() ;
                $( '#set_expired_deposit_payment_as' ).closest( 'tr' ).hide() ;
                $( '#fixed_deposit_price' ).closest( 'tr' ).hide() ;
                $( '#fixed_deposit_percent' ).closest( 'tr' ).hide() ;
                $( '#min_deposit' ).closest( 'tr' ).hide() ;
                $( '#max_deposit' ).closest( 'tr' ).hide() ;
                paymentPlansSelector.hide() ;
            } else {
                $( '#force_deposit' ).closest( 'tr' ).show() ;

                toggle_events.get_payment_type( $( '#payment_type' ).val() , true ) ;
            }
        } ,
        get_deposit_type : function ( $deposit_type ) {
            $deposit_type = $deposit_type || 'user-defined' ;

            $( '#deposit_price_type' ).closest( 'tr' ).hide() ;
            $( '#fixed_deposit_price' ).closest( 'tr' ).hide() ;
            $( '#fixed_deposit_percent' ).closest( 'tr' ).hide() ;
            $( '#min_deposit' ).closest( 'tr' ).show() ;
            $( '#max_deposit' ).closest( 'tr' ).show() ;

            if ( 'pre-defined' === $deposit_type ) {
                $( '#deposit_price_type' ).closest( 'tr' ).show() ;
                $( '#fixed_deposit_price' ).closest( 'tr' ).show() ;
                $( '#fixed_deposit_percent' ).closest( 'tr' ).show() ;
                $( '#min_deposit' ).closest( 'tr' ).hide() ;
                $( '#max_deposit' ).closest( 'tr' ).hide() ;

                toggle_events.get_deposit_price_type( $( '#deposit_price_type' ).val() ) ;
            }

            $( '#pay_balance_type' ).closest( 'tr' ).show() ;
            toggle_events.get_pay_balance( $( '#pay_balance_type' ).val() ) ;
        } ,
        get_deposit_price_type : function ( $deposit_price_type ) {
            $deposit_price_type = $deposit_price_type || 'percent-of-product-price' ;

            $( '#fixed_deposit_price' ).closest( 'tr' ).hide() ;
            $( '#fixed_deposit_percent' ).closest( 'tr' ).show() ;

            if ( 'fixed-price' === $deposit_price_type ) {
                $( '#fixed_deposit_price' ).closest( 'tr' ).show() ;
                $( '#fixed_deposit_percent' ).closest( 'tr' ).hide() ;
            }
        } ,
        get_pay_balance : function ( $pay_balance_type ) {
            $pay_balance_type = $pay_balance_type || 'after' ;

            $( '#pay_balance_after' ).show() ;
            $( '#pay_balance_before' ).hide() ;
            $( '#set_expired_deposit_payment_as' ).closest( 'tr' ).hide() ;

            if ( 'before' === $pay_balance_type ) {
                $( '#pay_balance_after' ).hide() ;
                $( '#pay_balance_before' ).show() ;
                $( '#set_expired_deposit_payment_as' ).closest( 'tr' ).show() ;
            }
        } ,
    } ;
    
    var paymentPlansSelector = {
        init : function () {
            $( document ).on( 'click' , '#_sumo_pp_add_col_1_plan' , this.onClickAddColumn1Plan ) ;
            $( document ).on( 'click' , '#_sumo_pp_add_col_2_plan' , this.onClickAddColumn2Plan ) ;
            $( 'table._sumo_pp_selected_plans' ).on( 'click' , 'a.remove_row' , this.onClickRemovePlan ) ;
        } ,
        onClickAddColumn1Plan : function ( evt ) {
            evt.stopImmediatePropagation() ;
            evt.preventDefault() ;
            paymentPlansSelector.addPlanSearchField( 'col_1' ) ;
        } ,
        onClickAddColumn2Plan : function ( evt ) {
            evt.stopImmediatePropagation() ;
            evt.preventDefault() ;
            paymentPlansSelector.addPlanSearchField( 'col_2' ) ;
        } ,
        onClickRemovePlan : function ( evt ) {
            $( this ).closest( 'tr' ).remove() ;
            return false ;
        } ,
        addPlanSearchField : function ( col ) {
            var rowID = $( 'table._sumo_pp_selected_col_' + col + '_plans' ).find( 'tbody tr' ).length ;
            $( '.spinner' ).addClass( 'is-active' ) ;

            $.ajax( {
                type : 'POST' ,
                url : ajaxurl ,
                data : {
                    action : '_sumo_pp_get_payment_plan_search_field' ,
                    security : sumo_pp_admin_bulk_action_settings.get_html_data_nonce ,
                    rowID : rowID ,
                    col : col ,
                } ,
                success : function ( data ) {

                    if ( typeof data !== 'undefined' ) {
                        $( '<tr><td class="sort" width="1%"></td>\n\
                                    <td>' + data.search_field + '</td><td><a href="#" class="remove_row button">X</a></td>\n\
                                    </tr>' ).appendTo( $( 'table._sumo_pp_selected_col_' + col + '_plans' ).find( 'tbody' ) ) ;
                        $( document.body ).trigger( 'wc-enhanced-select-init' ) ;
                    }
                } ,
                complete : function () {
                    $( '.spinner' ).removeClass( 'is-active' ) ;
                }
            } ) ;
            return false ;
        } ,
        hide : function () {
            $( '._sumo_pp_add_plans' ).closest( 'tr' ).hide() ;
            $( '._sumo_pp_selected_plans' ).hide() ;
        } ,
        show : function () {
            $( '._sumo_pp_add_plans' ).closest( 'tr' ).show() ;
            $( '._sumo_pp_selected_plans' ).show() ;
        } ,
    } ;

    var updater = {
        /**
         * Bulk Update the product data's.
         */
        init : function ( ) {

            $( document ).on( 'click' , '#bulk_update' , this.onSubmit ) ;
        } ,
        onSubmit : function () {
            $( '.updater' ).css( 'display' , 'inline-block' ) ;

            var $is_bulk_update = $( this ).attr( 'data-is_bulk_update' ) ;

            $.ajax( {
                type : 'POST' ,
                url : sumo_pp_admin_bulk_action_settings.wp_ajax_url ,
                data : updater.getData( '_sumo_pp_bulk_update_product_meta' , $is_bulk_update ) ,
                success : function ( data ) {
                    console.log( data ) ;

                    if ( data !== 'success' ) {
                        var j = 1 ;
                        var i , j , id , chunk = 10 ;

                        for ( i = 0 , j = data.length ; i < j ; i += chunk ) {
                            id = data.slice( i , i + chunk ) ;
                            updater.optimizeData( id ) ;
                        }

                        $.when( updater.optimizeData( ) ).done( function () {
                            location.reload( true ) ;
                        } ) ;
                    } else if ( data.replace( /\s/g , '' ) === 'success' ) {
                        location.reload( true ) ;
                    }
                } ,
                dataType : 'json' ,
                async : false
            } ) ;
            return false ;
        } ,
        optimizeData : function ( id ) {
            id = id || '' ;

            return $.ajax( {
                type : 'POST' ,
                url : sumo_pp_admin_bulk_action_settings.wp_ajax_url ,
                data : updater.getData( '_sumo_pp_optimize_bulk_updation_of_product_meta' , false , id ) ,
                success : function ( data ) {
                    console.log( data ) ;
                } ,
                dataType : 'json' ,
                async : false
            } ) ;
        } ,
        getData : function ( action , $is_bulk_update , id ) {
            action = action || '' ;
            id = id || '' ;
            $is_bulk_update = $is_bulk_update || '' ;

            return ( {
                action : action ,
                ids : id ,
                is_bulk_update : $is_bulk_update ,
                security : $is_bulk_update ? sumo_pp_admin_bulk_action_settings.update_nonce : sumo_pp_admin_bulk_action_settings.optimization_nonce ,
                product_select_type : $( '#get_product_select_type' ).val( ) ,
                selected_products : $( '#get_selected_products' ).val( ) ,
                selected_category : $( '#get_selected_categories' ).val( ) ,
                _sumo_pp_enable_sumopaymentplans : $( '#enable_sumopaymentplans' ).is( ':checked' ) ? 'yes' : 'no' ,
                _sumo_pp_payment_type : $( '#payment_type' ).val( ) ,
                _sumo_pp_apply_global_settings : $( '#apply_global_settings' ).is( ':checked' ) ? 'yes' : 'no' ,
                _sumo_pp_force_deposit : $( '#force_deposit' ).is( ':checked' ) ? 'yes' : 'no' ,
                _sumo_pp_deposit_type : $( '#deposit_type' ).val( ) ,
                _sumo_pp_deposit_price_type : $( '#deposit_price_type' ).val( ) ,
                _sumo_pp_pay_balance_type : $( '#pay_balance_type' ).val( ) ,
                _sumo_pp_pay_balance_after : $( '#pay_balance_after' ).val( ) ,
                _sumo_pp_pay_balance_before : $( '#pay_balance_before' ).val( ) ,
                _sumo_pp_set_expired_deposit_payment_as : $( '#set_expired_deposit_payment_as' ).val( ) ,
                _sumo_pp_fixed_deposit_price : $( '#fixed_deposit_price' ).val( ) ,
                _sumo_pp_fixed_deposit_percent : $( '#fixed_deposit_percent' ).val( ) ,
                _sumo_pp_min_deposit : $( '#min_deposit' ).val( ) ,
                _sumo_pp_max_deposit : $( '#max_deposit' ).val( ) ,
                _sumo_pp_selected_plans : $( 'table._sumo_pp_selected_plans :input' ).serialize( ) ,
            } ) ;
        }
    } ;

    toggle_events.init( ) ;
    paymentPlansSelector.init() ;
    updater.init( ) ;
} ) ;
/* global sumo_pp_checkout_page */

jQuery( function ( $ ) {
    // sumo_pp_checkout_page is required to continue, ensure the object exists
    if ( typeof sumo_pp_checkout_page === 'undefined' ) {
        return false ;
    }

    var $form = $( '.woocommerce-checkout-payment, .woocommerce-checkout-review-order-table' ).closest( 'form' ) ;

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

    var checkout = {
        /**
         * Manage Checkout page events.
         */
        sync : false ,
        isOrderPaymentPlansEnabled : false ,
        paymentType : false ,
        depositedAmount : false ,
        paymentPlan : false ,
        init : function () {

            this.onPageLoad() ;

            $( document.body ).on( 'input:checkbox' , '#_sumo_pp_enable_order_payment_plan' , this.preventdefault ) ;
            $( document ).on( 'change' , '#_sumo_pp_enable_order_payment_plan' , this.toggleOrderPaymentPlansCheckoutStatus ) ;
            $( document ).on( 'change' , '#_sumo_pp_deposited_amount' , this.updateOrderDepositedAmount ) ;
            $( document ).on( 'change' , '#_sumo_pp_chosen_payment_plan' , this.updateOrderPaymentPlan ) ;
        } ,
        onPageLoad : function () {
            checkout.sync = true ;
            this.getOrderPaymentPlansCheckoutStatus( $( '#_sumo_pp_enable_order_payment_plan' ).is( ':checked' ) ) ;
        } ,
        preventdefault : function ( evt ) {
            evt.preventDefault() ;
        } ,
        toggleOrderPaymentPlansCheckoutStatus : function ( evt ) {
            evt.preventDefault() ;
            checkout.sync = false ;
            checkout.getOrderPaymentPlansCheckoutStatus( evt.currentTarget.checked ) ;
        } ,
        updateOrderDepositedAmount : function ( evt ) {
            evt.preventDefault() ;
            checkout.updateCheckout() ;
        } ,
        updateOrderPaymentPlan : function ( evt ) {
            evt.preventDefault() ;
            checkout.updateCheckout() ;
        } ,
        getOrderPaymentPlansCheckoutStatus : function ( is_checked ) {
            is_checked = is_checked || false ;

            $( 'table._sumo_pp_order_payment_type_fields tr:eq(1)' ).slideUp() ;

            if ( is_checked ) {
                $( 'table._sumo_pp_order_payment_type_fields tr:eq(1)' ).slideDown() ;
            }
            checkout.updateCheckout() ;
        } ,
        populate : function () {
            checkout.isOrderPaymentPlansEnabled = $( '#_sumo_pp_enable_order_payment_plan' ).is( ':checked' ) ;
            checkout.paymentType = $( '#_sumo_pp_payment_type' ).val() ;
            checkout.depositedAmount = $( '#_sumo_pp_deposited_amount' ).val() ;
            checkout.paymentPlan = $( '#_sumo_pp_chosen_payment_plan:checked' ).val() ;
        } ,
        updateCheckout : function () {
            checkout.populate() ;

            $.blockUI.defaults.overlayCSS.cursor = 'wait' ;
            block( $form ) ;

            $.ajax( {
                type : 'POST' ,
                url : sumo_pp_checkout_page.wp_ajax_url ,
                dataType : 'text' ,
                async : checkout.sync ? false : true ,
                data : {
                    action : '_sumo_pp_checkout_order_payment_plan' ,
                    security : sumo_pp_checkout_page.checkout_order_payment_plan_nonce ,
                    enabled : checkout.isOrderPaymentPlansEnabled ? 'yes' : 'no' ,
                    payment_type : checkout.paymentType ,
                    deposited_amount : checkout.depositedAmount ,
                    chosen_payment_plan : checkout.paymentPlan ,
                } ,
                success : function () {
                    checkout.forceCreateAccountIfGuest() ;
                    $( document.body ).trigger( 'update_checkout' ) ;
                } ,
                complete : function () {
                    unblock( $form ) ;
                    checkout.clearCache() ;
                }
            } ) ;
        } ,
        forceCreateAccountIfGuest : function () {
            if ( sumo_pp_checkout_page.current_user_id > 0 === false && sumo_pp_checkout_page.force_guest === '1' ) {
                $( 'p.create-account' ).show() ;
                $( 'div.create-account' ).slideUp() ;

                if ( checkout.isOrderPaymentPlansEnabled ) {
                    $( 'p.create-account' ).hide() ;
                    $( 'div.create-account' ).slideDown() ;
                }
            }
        } ,
        clearCache : function () {
            checkout.sync = false ;
            checkout.isOrderPaymentPlansEnabled = false ;
            checkout.paymentType = false ;
            checkout.depositedAmount = false ;
            checkout.paymentPlan = false ;
        }
    } ;

    if ( sumo_pp_checkout_page.can_user_deposit_payment_in_checkout ) {
        checkout.init() ;
    }
} ) ;
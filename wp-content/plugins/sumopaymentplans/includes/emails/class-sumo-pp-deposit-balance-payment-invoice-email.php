<?php

/**
 * Invoice Order - Email.
 * 
 * @class SUMO_PP_Deposit_Balance_Payment_Invoice_Email
 * @category Class
 */
class SUMO_PP_Deposit_Balance_Payment_Invoice_Email extends SUMO_PP_Abstract_Email {

    /**
     * Constructor.
     * 
     * @access public
     */
    function __construct() {
        $this->id          = $this->prefix . 'deposit_balance_payment_invoice' ;
        $this->name        = 'deposit_balance_payment_invoice' ;
        $this->title       = __( 'Balance Payment Invoice - Deposit' , $this->text_domain ) ;
        $this->description = addslashes( sprintf( __( 'Balance Payment Invoice - Deposit will be sent to the customers when the balance payment for the purchase has to be charged' , $this->text_domain ) ) ) ;

        $this->template_html  = 'emails/sumo-pp-deposit-balance-payment-invoice.php' ;
        $this->template_plain = 'emails/plain/sumo-pp-deposit-balance-payment-invoice.php' ;

        $this->subject = __( '[{site_title}] - Invoice for Balance Payment of {product_name}' , $this->text_domain ) ;
        $this->heading = __( 'Invoice for Balance Payment of {product_name}' , $this->text_domain ) ;

        $this->subject_paid = $this->subject ;
        $this->heading_paid = $this->heading ;

        // Call parent constructor
        parent::__construct() ;
    }

    /**
     * Trigger.
     * 
     * @access public
     */
    function trigger( $order_id , $payment_post_id , $to = '' ) {
        $this->populate( $order_id , $payment_post_id , $to ) ;

        return $this->_trigger() ;
    }

}

return new SUMO_PP_Deposit_Balance_Payment_Invoice_Email() ;

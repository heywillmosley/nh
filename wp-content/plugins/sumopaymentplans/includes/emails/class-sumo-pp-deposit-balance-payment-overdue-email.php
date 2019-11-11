<?php

/**
 * Invoice Order - Email.
 * 
 * @class SUMO_PP_Deposit_Balance_Payment_Overdue_Email
 * @category Class
 */
class SUMO_PP_Deposit_Balance_Payment_Overdue_Email extends SUMO_PP_Abstract_Email {

    /**
     * Constructor.
     * 
     * @access public
     */
    function __construct() {
        $this->id          = $this->prefix . 'deposit_balance_payment_overdue' ;
        $this->name        = 'deposit_balance_payment_overdue' ;
        $this->title       = __( 'Balance Payment Overdue - Deposit' , $this->text_domain ) ;
        $this->description = addslashes( sprintf( __( 'Balance Payment Overdue - Deposit will be sent to the customers when the balance payment for the product purchase is currently Overdue.' , $this->text_domain ) ) ) ;

        $this->template_html  = 'emails/sumo-pp-deposit-balance-payment-overdue.php' ;
        $this->template_plain = 'emails/plain/sumo-pp-deposit-balance-payment-overdue.php' ;

        $this->subject = __( '[{site_title}] - Payment Overdue for {product_name}' , $this->text_domain ) ;
        $this->heading = __( 'Payment Overdue for {product_name}' , $this->text_domain ) ;

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

return new SUMO_PP_Deposit_Balance_Payment_Overdue_Email() ;

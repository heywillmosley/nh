<?php

/**
 * Invoice Order - Email.
 * 
 * @class SUMO_PP_Deposit_Balance_Payment_Completed_Email
 * @category Class
 */
class SUMO_PP_Deposit_Balance_Payment_Completed_Email extends SUMO_PP_Abstract_Email {

    /**
     * Constructor.
     * 
     * @access public
     */
    function __construct() {
        $this->id          = $this->prefix . 'deposit_balance_payment_completed' ;
        $this->name        = 'deposit_balance_payment_completed' ;
        $this->title       = __( 'Balance Payment Completed - Deposit' , $this->text_domain ) ;
        $this->description = addslashes( sprintf( __( 'Balance Payment Completed - Deposit will be sent to the customers when the balance payment for the product purchase has been completed successfully' , $this->text_domain ) ) ) ;

        $this->template_html  = 'emails/sumo-pp-deposit-balance-payment-completed.php' ;
        $this->template_plain = 'emails/plain/sumo-pp-deposit-balance-payment-completed.php' ;

        $this->subject = __( '[{site_title}] - Balance Payment Completed for {product_name}' , $this->text_domain ) ;
        $this->heading = __( 'Balance Payment Completed for {product_name}' , $this->text_domain ) ;

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

return new SUMO_PP_Deposit_Balance_Payment_Completed_Email() ;

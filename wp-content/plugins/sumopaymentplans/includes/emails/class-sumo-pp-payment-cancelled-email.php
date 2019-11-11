<?php

/**
 * Payment Cancelled - Email.
 * 
 * @class SUMO_PP_Payment_Cancelled_Email
 * @category Class
 */
class SUMO_PP_Payment_Cancelled_Email extends SUMO_PP_Abstract_Email {

    /**
     * Constructor.
     * 
     * @access public
     */
    function __construct() {
        $this->id          = $this->prefix . 'payment_cancelled' ;
        $this->name        = 'payment_cancelled' ;
        $this->title       = __( 'Payment Cancelled' , $this->text_domain ) ;
        $this->description = addslashes( sprintf( __( 'Payment Cancelled will be sent to the customers when the user has not paid their balance payments within the due date.' , $this->text_domain ) ) ) ;

        $this->template_html  = 'emails/sumo-pp-payment-cancelled.php' ;
        $this->template_plain = 'emails/plain/sumo-pp-payment-cancelled.php' ;

        $this->subject = __( '[{site_title}] - Payment Cancelled for {product_name}' , $this->text_domain ) ;
        $this->heading = __( 'Payment Cancelled for {product_name}' , $this->text_domain ) ;

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

return new SUMO_PP_Payment_Cancelled_Email() ;

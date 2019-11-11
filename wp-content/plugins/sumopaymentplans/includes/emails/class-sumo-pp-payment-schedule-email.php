<?php

/**
 * Invoice Order - Email.
 * 
 * @class SUMO_PP_Payment_Schedule_Email
 * @category Class
 */
class SUMO_PP_Payment_Schedule_Email extends SUMO_PP_Abstract_Email {

    /**
     * Constructor.
     * 
     * @access public
     */
    function __construct() {
        $this->id          = $this->prefix . 'payment_schedule' ;
        $this->name        = 'payment_schedule' ;
        $this->title       = __( 'Payment Schedule Email' , $this->text_domain ) ;
        $this->description = addslashes( sprintf( __( 'Payment Schedule Emails will be sent to the customers when they purchase a product using Deposit/Payment Plans.' , $this->text_domain ) ) ) ;

        $this->template_html  = 'emails/sumo-pp-payment-schedule.php' ;
        $this->template_plain = 'emails/plain/sumo-pp-payment-schedule.php' ;

        $this->subject = __( '[{site_title}] - Payment Schedule for Purchase of {product_name}' , $this->text_domain ) ;
        $this->heading = __( 'Payment Schedule for Purchase of {product_name}' , $this->text_domain ) ;

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

return new SUMO_PP_Payment_Schedule_Email() ;

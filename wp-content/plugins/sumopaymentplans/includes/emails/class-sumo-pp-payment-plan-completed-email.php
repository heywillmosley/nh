<?php

/**
 * Invoice Order - Email.
 * 
 * @class SUMO_PP_Payment_Plan_Completed_Email
 * @category Class
 */
class SUMO_PP_Payment_Plan_Completed_Email extends SUMO_PP_Abstract_Email {

    /**
     * Constructor.
     * 
     * @access public
     */
    function __construct() {
        $this->id          = $this->prefix . 'payment_plan_completed' ;
        $this->name        = 'payment_plan_completed' ;
        $this->title       = __( 'Payment Completed – Payment Plan' , $this->text_domain ) ;
        $this->description = addslashes( sprintf( __( 'Payment Completed – Payment Plan will be sent to the customers when the Payment Schedule for the Payment Plan has been completed successfully.' , $this->text_domain ) ) ) ;

        $this->template_html  = 'emails/sumo-pp-payment-plan-completed.php' ;
        $this->template_plain = 'emails/plain/sumo-pp-payment-plan-completed.php' ;

        $this->subject = __( '[{site_title}] - Payment Schedule for {product_name} has been Completed' , $this->text_domain ) ;
        $this->heading = __( 'Payment Schedule for {product_name} has been Completed' , $this->text_domain ) ;

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

return new SUMO_PP_Payment_Plan_Completed_Email() ;

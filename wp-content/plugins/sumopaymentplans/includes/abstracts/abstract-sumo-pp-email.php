<?php

/**
 * Abstract Payment Email
 * 
 * @abstract SUMO_PP_Abstract_Email
 */
abstract class SUMO_PP_Abstract_Email extends WC_Email {

    /**
     * @var array Supports. Ex. array( 'pay_link' , 'upcoming_mail_info', 'paid_order' );
     */
    public $supports = array () ;

    /**
     * @var array Get upcoming email information
     */
    public $upcoming_mail = array () ;

    /**
     * @var int Payment post ID 
     */
    public $payment_post_id = 0 ;

    /**
     * @var string Get plugin prefix
     */
    public $prefix = SUMO_PP_PLUGIN_PREFIX ;

    /**
     * @var string Get plugin text domain.
     */
    public $text_domain = SUMO_PP_PLUGIN_TEXT_DOMAIN ;

    /**
     * Constructor.
     */
    public function __construct() {
        $this->template_base = SUMO_PP_PLUGIN_TEMPLATE_PATH ;
        $this->mail_to_admin = 'yes' === $this->get_option( 'mail_to_admin' ) ;

        // Call WC_Email constuctor
        parent::__construct() ;
    }

    /**
     * Populate the Email
     * 
     * @param int $order_id
     * @param int $payment_post_id
     * @param string $to
     */
    protected function populate( $order_id , $payment_post_id , $to ) {
        $this->payment_post_id  = absint( $payment_post_id ) ;
        $this->payment_order_id = absint( $order_id ) ;
        $this->object           = _sumo_pp_get_order( $this->payment_order_id ) ;
        $this->recipient        = ! empty( $to ) ? $to : $this->object->get_billing_email() ;
    }

    /**
     * Check this Email supported feature.
     * @param string $type
     * @return boolean
     * 
     */
    public function supports( $type = '' ) {
        return in_array( $type , $this->supports ) ;
    }

    /**
     * Trigger.
     * 
     * @return bool on Success
     */
    public function _trigger() {

        $payment_count = sizeof( _sumo_pp_get_balance_paid_orders( $this->payment_post_id ) ) ;

        if ( in_array( $this->id , array ( $this->prefix . 'payment_plan_invoice' , $this->prefix . 'payment_plan_overdue' ) ) ) {
            $payment_count += 1 ;
        }

        $this->find[ 'payment-no' ]                  = '{payment_no}' ;
        $this->find[ 'product-name' ]                = '{product_name}' ;
        $this->find[ 'product-with-installment-no' ] = '{product_with_installment_no}' ;

        $this->replace[ 'payment-no' ]                  = _sumo_pp_get_payment_number( $this->payment_post_id ) ;
        $this->replace[ 'product-name' ]                = _sumo_pp_get_formatted_payment_product_title( $this->payment_post_id , array (
            'tips'           => false ,
            'maybe_variable' => false ,
            'qty'            => false ,
                ) ) ;
        $this->replace[ 'product-with-installment-no' ] = sprintf( __( 'Installment #%s of %s' , $this->text_domain ) , $payment_count , _sumo_pp_get_formatted_payment_product_title( $this->payment_post_id , array (
            'tips'           => false ,
            'maybe_variable' => false ,
            'qty'            => false ,
                ) ) ) ;

        if ( ! $this->is_enabled() || ! $this->get_recipient() ) {
            return false ;
        }

        return $this->send( $this->get_recipient() , $this->get_subject() , $this->get_content() , $this->get_headers() , $this->get_attachments() ) ;
    }

    /**
     * get_type function.
     *
     * @return string
     */
    public function get_email_type() {
        return class_exists( 'DOMDocument' ) ? 'html' : '' ;
    }

    /**
     * Format date to display.
     * @param int|string $date
     * @return string
     */
    public function format_date( $date = '' ) {
        return _sumo_pp_get_date_to_display( $date ) ;
    }

    /**
     * Retrieve Email Template from the Template path or Plugin default path
     * 
     * @param string $template
     * @param boolean $plain_text
     * @return string
     */
    public function _get_template( $template , $plain_text = false ) {
        $supports = array () ;

        if ( $this->supports( 'pay_link' ) ) {
            $supports = array_merge( array (
                'payment_link' => $this->get_option( 'enable_pay_link' )
                    ) , $supports ) ;
        }

        ob_start() ;

        _sumo_pp_get_template( $template , array_merge( array (
            'order'         => $this->object ,
            'payment_id'    => $this->payment_post_id ,
            'email_heading' => $this->get_heading() ,
            'sent_to_admin' => true ,
            'plain_text'    => $plain_text
                        ) , $supports ) ) ;

        return ob_get_clean() ;
    }

    /**
     * Get content HTMl.
     *
     * @return string
     */
    public function get_content_html() {
        return $this->_get_template( $this->template_html ) ;
    }

    /**
     * Get content plain.
     *
     * @return string
     */
    public function get_content_plain() {
        return '' ;
    }

    /**
     * Display form fields
     */
    public function init_form_fields() {
        $this->form_fields = array (
            'enabled' => array (
                'title'   => __( 'Enable/Disable' , $this->text_domain ) ,
                'type'    => 'checkbox' ,
                'label'   => __( 'Enable this email notification' , $this->text_domain ) ,
                'default' => 'yes'
            ) ,
            'subject' => array (
                'title'       => __( 'Email Subject' , $this->text_domain ) ,
                'type'        => 'text' ,
                'description' => sprintf( __( 'Defaults to <code>%s</code>' , $this->text_domain ) , $this->subject ) ,
                'placeholder' => '' ,
                'default'     => ''
            ) ,
            'heading' => array (
                'title'       => __( 'Email Heading' , $this->text_domain ) ,
                'type'        => 'text' ,
                'description' => sprintf( __( 'Defaults to <code>%s</code>' , $this->text_domain ) , $this->heading ) ,
                'placeholder' => '' ,
                'default'     => ''
            ) ) ;

        if ( $this->supports( 'paid_order' ) ) {
            $this->form_fields = array_merge( $this->form_fields , array (
                'subject_paid' => array (
                    'title'       => __( 'Email Subject (paid)' , $this->text_domain ) ,
                    'type'        => 'text' ,
                    'description' => sprintf( __( 'Defaults to <code>%s</code>' , $this->text_domain ) , $this->subject_paid ) ,
                    'placeholder' => '' ,
                    'default'     => ''
                ) ,
                'heading_paid' => array (
                    'title'       => __( 'Email Heading (paid)' , $this->text_domain ) ,
                    'type'        => 'text' ,
                    'description' => sprintf( __( 'Defaults to <code>%s</code>' , $this->text_domain ) , $this->heading_paid ) ,
                    'placeholder' => '' ,
                    'default'     => ''
                ) ) ) ;
        }

        if ( $this->supports( 'pay_link' ) ) {
            $this->form_fields = array_merge( $this->form_fields , array (
                'enable_pay_link' => array (
                    'title'   => __( 'Enable Payment Link in Mail' , $this->text_domain ) ,
                    'type'    => 'checkbox' ,
                    'default' => 'yes'
                ) ) ) ;
        }

        $this->form_fields = array_merge( $this->form_fields , array (
            'mail_to_admin' => array (
                'title'   => __( 'Send Email to Admin' , $this->text_domain ) ,
                'type'    => 'checkbox' ,
                'default' => 'no'
            ) ,
                ) ) ;
    }

}

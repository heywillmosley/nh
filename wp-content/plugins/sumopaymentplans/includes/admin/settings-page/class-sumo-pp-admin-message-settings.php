<?php

/**
 * Message Tab.
 * 
 * @class SUMO_PP_Messages_Settings
 * @category Class
 */
class SUMO_PP_Messages_Settings extends SUMO_PP_Abstract_Admin_Settings {

    /**
     * SUMO_PP_Messages_Settings constructor.
     */
    public function __construct() {

        $this->id       = 'messages' ;
        $this->label    = __( 'Messages' , $this->text_domain ) ;
        $this->settings = $this->get_settings() ;
        $this->init() ;
    }

    /**
     * Get settings array.
     * @return array
     */
    public function get_settings() {
        global $current_section ;

        return apply_filters( 'sumopaymentplans_get_' . $this->id . '_settings' , array (
            array (
                'name' => __( 'Shop Page Message Settings' , $this->text_domain ) ,
                'type' => 'title' ,
                'id'   => $this->prefix . 'shop_message_settings'
            ) ,
            array (
                'name'    => __( 'Add to Cart Label' , $this->text_domain ) ,
                'id'      => $this->prefix . 'add_to_cart_label' ,
                'newids'  => $this->prefix . 'add_to_cart_label' ,
                'type'    => 'text' ,
                'std'     => __( 'View More' , $this->text_domain ) ,
                'default' => __( 'View More' , $this->text_domain ) ,
            ) ,
            array ( 'type' => 'sectionend' , 'id' => $this->prefix . 'shop_message_settings' ) ,
            array (
                'name' => __( 'Single Product Page Message Settings' , $this->text_domain ) ,
                'type' => 'title' ,
                'id'   => $this->prefix . 'single_product_message_settings'
            ) ,
            array (
                'name'    => __( 'Pay in Full' , $this->text_domain ) ,
                'id'      => $this->prefix . 'pay_in_full_label' ,
                'newids'  => $this->prefix . 'pay_in_full_label' ,
                'type'    => 'text' ,
                'std'     => __( 'Pay in Full' , $this->text_domain ) ,
                'default' => __( 'Pay in Full' , $this->text_domain ) ,
            ) ,
            array (
                'name'    => __( 'Pay a Deposit Amount' , $this->text_domain ) ,
                'id'      => $this->prefix . 'pay_a_deposit_amount_label' ,
                'newids'  => $this->prefix . 'pay_a_deposit_amount_label' ,
                'type'    => 'text' ,
                'std'     => __( 'Pay a Deposit Amount' , $this->text_domain ) ,
                'default' => __( 'Pay a Deposit Amount' , $this->text_domain ) ,
            ) ,
            array (
                'name'    => __( 'Pay with Payment Plans' , $this->text_domain ) ,
                'id'      => $this->prefix . 'pay_with_payment_plans_label' ,
                'newids'  => $this->prefix . 'pay_with_payment_plans_label' ,
                'type'    => 'text' ,
                'std'     => __( 'Pay with Payment Plans' , $this->text_domain ) ,
                'default' => __( 'Pay with Payment Plans' , $this->text_domain ) ,
            ) ,
            array ( 'type' => 'sectionend' , 'id' => $this->prefix . 'single_product_message_settings' ) ,
            array (
                'name' => __( 'Cart And Checkout Page Message Settings' , $this->text_domain ) ,
                'type' => 'title' ,
                'id'   => $this->prefix . 'cart_message_settings'
            ) ,
            array (
                'name'    => __( 'Payment Plan' , $this->text_domain ) ,
                'id'      => $this->prefix . 'payment_plan_label' ,
                'newids'  => $this->prefix . 'payment_plan_label' ,
                'type'    => 'text' ,
                'std'     => __( 'Payment Plan:' , $this->text_domain ) ,
                'default' => __( 'Payment Plan:' , $this->text_domain ) ,
            ) ,
            array (
                'name'    => __( 'Balance Payable Amount' , $this->text_domain ) ,
                'id'      => $this->prefix . 'balance_payable_amount_label' ,
                'newids'  => $this->prefix . 'balance_payable_amount_label' ,
                'type'    => 'text' ,
                'std'     => __( 'Balance Payable Amount' , $this->text_domain ) ,
                'default' => __( 'Balance Payable Amount' , $this->text_domain ) ,
            ) ,
            array (
                'name'    => __( 'Next Payment Date' , $this->text_domain ) ,
                'id'      => $this->prefix . 'next_payment_date_label' ,
                'newids'  => $this->prefix . 'next_payment_date_label' ,
                'type'    => 'text' ,
                'std'     => __( 'Next Payment Date:' , $this->text_domain ) ,
                'default' => __( 'Next Payment Date:' , $this->text_domain ) ,
            ) ,
            array (
                'name'    => __( 'Balance Payment Due Date' , $this->text_domain ) ,
                'id'      => $this->prefix . 'balance_payment_due_date_label' ,
                'newids'  => $this->prefix . 'balance_payment_due_date_label' ,
                'type'    => 'text' ,
                'std'     => __( 'Balance Payment Due Date:' , $this->text_domain ) ,
                'default' => __( 'Balance Payment Due Date:' , $this->text_domain ) ,
            ) ,
            array ( 'type' => 'sectionend' , 'id' => $this->prefix . 'cart_message_settings' ) ,
                ) ) ;
    }

}

return new SUMO_PP_Messages_Settings() ;

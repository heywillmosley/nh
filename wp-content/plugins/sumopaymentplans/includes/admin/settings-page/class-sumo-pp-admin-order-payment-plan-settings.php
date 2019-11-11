<?php

/**
 * Order Payment Plan Tab.
 * 
 * @class SUMO_PP_Order_Payment_Plan_Settings
 * @category Class
 */
class SUMO_PP_Order_Payment_Plan_Settings extends SUMO_PP_Abstract_Admin_Settings {

    /**
     * SUMO_PP_Order_Payment_Plan_Settings constructor.
     */
    public function __construct() {

        $this->id            = 'order_payment_plan' ;
        $this->label         = __( 'Order Payment Plan' , $this->text_domain ) ;
        $this->custom_fields = array (
            'get_order_payment_plan_pay_balance_type' ,
            'get_limited_users_of_order_payment_plan' ,
                ) ;
        $this->settings      = $this->get_settings() ;
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
                'name' => __( 'Order Payment Plan Settings' , $this->text_domain ) ,
                'type' => 'title' ,
                'id'   => $this->prefix . 'order_payment_plan_settings'
            ) ,
            array (
                'name'     => __( 'Enable Order Payment Plan' , $this->text_domain ) ,
                'id'       => $this->prefix . 'enable_order_payment_plan' ,
                'newids'   => $this->prefix . 'enable_order_payment_plan' ,
                'type'     => 'checkbox' ,
                'std'      => 'no' ,
                'default'  => 'no' ,
                'desc'     => __( 'If enabled, a checkbox will be displayed on their checkout page using which customers can choose to pay for their orders using payment plans. Order Payment Plan is not applicable if payment plans enabled products are in cart ' , $this->text_domain ) ,
                'desc_tip' => true ,
            ) ,
            array (
                'name'    => __( 'Payment Type' , $this->text_domain ) ,
                'id'      => $this->prefix . 'order_payment_type' ,
                'newids'  => $this->prefix . 'order_payment_type' ,
                'type'    => 'select' ,
                'options' => array (
                    'pay-in-deposit' => __( 'Pay a Deposit Amount' , $this->text_domain ) ,
                    'payment-plans'  => __( 'Pay with Payment Plans' , $this->text_domain ) ,
                ) ,
                'std'     => 'pay-in-deposit' ,
                'default' => 'pay-in-deposit' ,
            ) ,
            array (
                'name'    => __( 'Apply Global Level Settings' , $this->text_domain ) ,
                'id'      => $this->prefix . 'apply_global_settings_for_order_payment_plan' ,
                'newids'  => $this->prefix . 'apply_global_settings_for_order_payment_plan' ,
                'type'    => 'checkbox' ,
                'std'     => 'no' ,
                'default' => 'no' ,
            ) ,
            array (
                'name'    => __( 'Force Deposit/Payment Plans' , $this->text_domain ) ,
                'id'      => $this->prefix . 'force_order_payment_plan' ,
                'newids'  => $this->prefix . 'force_order_payment_plan' ,
                'type'    => 'checkbox' ,
                'std'     => 'no' ,
                'default' => 'no' ,
            ) ,
            array (
                'name'    => __( 'Deposit Type' , $this->text_domain ) ,
                'id'      => $this->prefix . 'order_payment_plan_deposit_type' ,
                'newids'  => $this->prefix . 'order_payment_plan_deposit_type' ,
                'type'    => 'select' ,
                'options' => array (
                    'pre-defined'  => __( 'Predefined Deposit Amount' , $this->text_domain ) ,
                    'user-defined' => __( 'User Defined Deposit Amount' , $this->text_domain ) ,
                ) ,
                'std'     => 'pre-defined' ,
                'default' => 'pre-defined' ,
            ) ,
            array (
                'name'              => __( 'Deposit Percentage' , $this->text_domain ) ,
                'id'                => $this->prefix . 'fixed_order_payment_plan_deposit_percent' ,
                'newids'            => $this->prefix . 'fixed_order_payment_plan_deposit_percent' ,
                'type'              => 'number' ,
                'std'               => '50' ,
                'default'           => '50' ,
                'desc'              => '' ,
                'desc_tip'          => true ,
                'custom_attributes' => array (
                    'min'  => '0.01' ,
                    'max'  => '99.99' ,
                    'step' => '0.01' ,
                ) ,
            ) ,
            array (
                'name'              => __( 'Minimum Deposit (%)' , $this->text_domain ) ,
                'id'                => $this->prefix . 'min_order_payment_plan_deposit' ,
                'newids'            => $this->prefix . 'min_order_payment_plan_deposit' ,
                'type'              => 'number' ,
                'std'               => '0.01' ,
                'default'           => '0.01' ,
                'desc'              => '' ,
                'desc_tip'          => true ,
                'custom_attributes' => array (
                    'min'  => '0.01' ,
                    'max'  => '99.99' ,
                    'step' => '0.01' ,
                ) ,
            ) ,
            array (
                'name'              => __( 'Maximum Deposit (%)' , $this->text_domain ) ,
                'id'                => $this->prefix . 'max_order_payment_plan_deposit' ,
                'newids'            => $this->prefix . 'max_order_payment_plan_deposit' ,
                'type'              => 'number' ,
                'std'               => '99.99' ,
                'default'           => '99.99' ,
                'desc'              => '' ,
                'desc_tip'          => true ,
                'custom_attributes' => array (
                    'min'  => '0.01' ,
                    'max'  => '99.99' ,
                    'step' => '0.01' ,
                ) ,
            ) ,
            array (
                'type' => $this->get_custom_field_type( 'get_order_payment_plan_pay_balance_type' ) ,
            ) ,
            array (
                'name'    => __( 'Select Plans' , $this->text_domain ) ,
                'id'      => $this->prefix . 'selected_plans_for_order_payment_plan' ,
                'newids'  => $this->prefix . 'selected_plans_for_order_payment_plan' ,
                'type'    => 'multiselect' ,
                'options' => _sumo_pp_get_payment_plan_names() ,
                'std'     => array () ,
                'default' => array () ,
            ) ,
            array (
                'name'    => __( 'Order Payment Plan Label' , $this->text_domain ) ,
                'id'      => $this->prefix . 'order_payment_plan_label' ,
                'newids'  => $this->prefix . 'order_payment_plan_label' ,
                'type'    => 'text' ,
                'std'     => __( 'Order Payment Plan' , $this->text_domain ) ,
                'default' => __( 'Order Payment Plan' , $this->text_domain ) ,
            ) ,
            array (
                'name'     => __( 'Show Order Payment Plan Option for' , $this->text_domain ) ,
                'id'       => $this->prefix . 'show_order_payment_plan_for' ,
                'newids'   => $this->prefix . 'show_order_payment_plan_for' ,
                'type'     => 'select' ,
                'std'      => 'all_users' ,
                'default'  => 'all_users' ,
                'options'  => array (
                    'all_users'         => __( 'All Users' , $this->text_domain ) ,
                    'include_users'     => __( 'Include User(s)' , $this->text_domain ) ,
                    'exclude_users'     => __( 'Exclude User(s)' , $this->text_domain ) ,
                    'include_user_role' => __( 'Include User Role(s)' , $this->text_domain ) ,
                    'exclude_user_role' => __( 'Exclude User Role(s)' , $this->text_domain )
                ) ,
                'desc'     => '' ,
                'desc_tip' => true ,
            ) ,
            array (
                'type' => $this->get_custom_field_type( 'get_limited_users_of_order_payment_plan' )
            ) ,
            array (
                'name'     => __( 'Select User Role(s)' , $this->text_domain ) ,
                'id'       => $this->prefix . 'get_limited_userroles_of_order_payment_plan' ,
                'newids'   => $this->prefix . 'get_limited_userroles_of_order_payment_plan' ,
                'type'     => 'multiselect' ,
                'options'  => _sumo_pp_get_user_roles( true ) ,
                'std'      => array () ,
                'default'  => array () ,
                'desc'     => '' ,
                'desc_tip' => true ,
            ) ,
            array (
                'name'    => __( 'Order Payment Plan Position' , $this->text_domain ) ,
                'id'      => $this->prefix . 'order_payment_plan_form_position' ,
                'newids'  => $this->prefix . 'order_payment_plan_form_position' ,
                'type'    => 'select' ,
                'std'     => 'checkout_order_review' ,
                'default' => 'checkout_order_review' ,
                'options' => apply_filters( 'sumopaymentplans_order_payment_plan_form_position' , array (
                    'checkout_order_review'           => ucwords( str_replace( '_' , ' ' , 'woocommerce_checkout_order_review' ) ) ,
                    'checkout_after_customer_details' => ucwords( str_replace( '_' , ' ' , 'woocommerce_checkout_after_customer_details' ) ) ,
                    'before_checkout_form'            => ucwords( str_replace( '_' , ' ' , 'woocommerce_before_checkout_form' ) ) ,
                    'checkout_before_order_review'    => ucwords( str_replace( '_' , ' ' , 'woocommerce_checkout_before_order_review' ) ) ,
                ) ) ,
                'desc'    => __( 'Some themes do not support all the positions, if the positions is not supported then it might result in jquery conflict' , $this->text_domain ) ,
            ) ,
            array ( 'type' => 'sectionend' , 'id' => $this->prefix . 'order_payment_plan_settings' ) ,
                ) ) ;
    }

    /**
     * Save the custom options once.
     */
    public function custom_types_add_options() {
        add_option( $this->prefix . 'order_payment_plan_pay_balance_type' , 'after' ) ;
        add_option( $this->prefix . 'order_payment_plan_pay_balance_after' , '1' ) ;
        add_option( $this->prefix . 'order_payment_plan_pay_balance_before' , '' ) ;
        add_option( $this->prefix . 'get_limited_users_of_order_payment_plan' , array () ) ;
    }

    /**
     * Delete the custom options.
     */
    public function custom_types_delete_options() {
        delete_option( $this->prefix . 'order_payment_plan_pay_balance_type' ) ;
        delete_option( $this->prefix . 'order_payment_plan_pay_balance_after' ) ;
        delete_option( $this->prefix . 'order_payment_plan_pay_balance_before' ) ;
        delete_option( $this->prefix . 'get_limited_users_of_order_payment_plan' ) ;
    }

    /**
     * Save custom settings.
     */
    public function custom_types_save() {

        if ( isset( $_POST[ 'order_payment_plan_pay_balance_type' ] ) ) {
            update_option( $this->prefix . 'order_payment_plan_pay_balance_type' , $_POST[ 'order_payment_plan_pay_balance_type' ] ) ;
        }
        if ( isset( $_POST[ 'order_payment_plan_pay_balance_after' ] ) ) {
            update_option( $this->prefix . 'order_payment_plan_pay_balance_after' , $_POST[ 'order_payment_plan_pay_balance_after' ] ) ;
        }
        if ( isset( $_POST[ 'order_payment_plan_pay_balance_before' ] ) ) {
            update_option( $this->prefix . 'order_payment_plan_pay_balance_before' , $_POST[ 'order_payment_plan_pay_balance_before' ] ) ;
        }
        if ( isset( $_POST[ $this->prefix . 'get_limited_users_of_order_payment_plan' ] ) ) {
            update_option( $this->prefix . 'get_limited_users_of_order_payment_plan' ,  ! is_array( $_POST[ $this->prefix . 'get_limited_users_of_order_payment_plan' ] ) ? array_filter( array_map( 'absint' , explode( ',' , $_POST[ $this->prefix . 'get_limited_users_of_order_payment_plan' ] ) ) ) : $_POST[ $this->prefix . 'get_limited_users_of_order_payment_plan' ]  ) ;
        }
    }

    /**
     * Custom type field.
     */
    public function get_order_payment_plan_pay_balance_type() {
        ?>
        <tr class="pay-balance-wrapper">
            <th>
                <?php _e( 'Deposit Balance Payment Due Date' , $this->text_domain ) ; ?>
            </th>
            <td>
                <select id="<?php echo "{$this->prefix}order_payment_plan_pay_balance_type" ; ?>" name="order_payment_plan_pay_balance_type" style="width:95px;">
                    <option value="after" <?php selected( 'after' === get_option( $this->prefix . 'order_payment_plan_pay_balance_type' , 'after' ) , true ) ; ?>><?php _e( 'After' , $this->text_domain ) ; ?></option>
                    <option value="before" <?php selected( 'before' === get_option( $this->prefix . 'order_payment_plan_pay_balance_type' , 'after' ) , true ) ; ?>><?php _e( 'Before' , $this->text_domain ) ; ?></option>
                </select>
                <input id="<?php echo "{$this->prefix}order_payment_plan_pay_balance_after" ; ?>" name="order_payment_plan_pay_balance_after" type="number" min="1" value="<?php echo get_option( $this->prefix . 'order_payment_plan_pay_balance_after' , '1' ) ; ?>" style="width:150px;"/>
                <input id="<?php echo "{$this->prefix}order_payment_plan_pay_balance_before" ; ?>" name="order_payment_plan_pay_balance_before" type="text" placeholder="<?php esc_attr_e( 'YYYY-MM-DD' , $this->text_domain ) ?>" value="<?php echo get_option( $this->prefix . 'order_payment_plan_pay_balance_before' , '' ) ; ?>" style="width:150px;"/>
            </td>
        </tr>
        <?php
    }

    /**
     * Custom type field.
     */
    public function get_limited_users_of_order_payment_plan() {

        _sumo_pp_wc_search_field( array (
            'class'       => 'wc-customer-search' ,
            'id'          => $this->prefix . 'get_limited_users_of_order_payment_plan' ,
            'type'        => 'customer' ,
            'title'       => __( 'Select User(s)' , $this->text_domain ) ,
            'placeholder' => __( 'Search for a user&hellip;' , $this->text_domain ) ,
            'options'     => ( array ) get_option( $this->prefix . 'get_limited_users_of_order_payment_plan' , array () )
        ) ) ;
    }

}

return new SUMO_PP_Order_Payment_Plan_Settings() ;

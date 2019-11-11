<?php

/**
 * General Tab.
 * 
 * @class SUMO_PP_General_Settings
 * @category Class
 */
class SUMO_PP_General_Settings extends SUMO_PP_Abstract_Admin_Settings {

    /**
     * SUMO_PP_General_Settings constructor.
     */
    public function __construct() {

        $this->id            = 'general' ;
        $this->label         = __( 'General' , $this->text_domain ) ;
        $this->custom_fields = array (
            'get_limited_users_of_payment_product' ,
            'get_global_selected_plans' ,
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
                'name' => __( 'Deposit Global Level Settings' , $this->text_domain ) ,
                'type' => 'title' ,
                'id'   => $this->prefix . 'deposit_global_settings'
            ) ,
            array (
                'name'     => __( 'Force Deposit' , $this->text_domain ) ,
                'id'       => $this->prefix . 'force_deposit' ,
                'newids'   => $this->prefix . 'force_deposit' ,
                'type'     => 'checkbox' ,
                'std'      => 'no' ,
                'default'  => 'no' ,
                'desc'     => __( 'When enabled, the user will be forced to pay a deposit amount' , $this->text_domain ) ,
                'desc_tip' => true ,
            ) ,
            array (
                'name'     => __( 'Deposit Type' , $this->text_domain ) ,
                'id'       => $this->prefix . 'deposit_type' ,
                'newids'   => $this->prefix . 'deposit_type' ,
                'type'     => 'select' ,
                'options'  => array (
                    'pre-defined'  => __( 'Predefined Deposit Amount' , $this->text_domain ) ,
                    'user-defined' => __( 'User Defined Deposit Amount' , $this->text_domain ) ,
                ) ,
                'std'      => 'pre-defined' ,
                'default'  => 'pre-defined' ,
                'desc'     => '' ,
                'desc_tip' => true ,
            ) ,
            array (
                'name'              => __( 'Deposit Percentage' , $this->text_domain ) ,
                'id'                => $this->prefix . 'fixed_deposit_percent' ,
                'newids'            => $this->prefix . 'fixed_deposit_percent' ,
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
                'id'                => $this->prefix . 'min_deposit' ,
                'newids'            => $this->prefix . 'min_deposit' ,
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
                'id'                => $this->prefix . 'max_deposit' ,
                'newids'            => $this->prefix . 'max_deposit' ,
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
                'name'    => __( 'Deposit Balance Payment Due Date' , $this->text_domain ) ,
                'id'      => $this->prefix . 'pay_balance_after' ,
                'newids'  => $this->prefix . 'pay_balance_after' ,
                'type'    => 'number' ,
                'std'     => '10' ,
                'default' => '10' ,
                'desc'    => __( 'day(s) from the date of deposit payment' , $this->text_domain ) ,
            ) ,
            array ( 'type' => 'sectionend' , 'id' => $this->prefix . 'deposit_global_settings' ) ,
            array (
                'name' => __( 'Payment Plan Global Level Settings' , $this->text_domain ) ,
                'type' => 'title' ,
                'id'   => $this->prefix . 'global_payment_plan_settings'
            ) ,
            array (
                'name'     => __( 'Force Payment Plan' , $this->text_domain ) ,
                'id'       => $this->prefix . 'force_payment_plan' ,
                'newids'   => $this->prefix . 'force_payment_plan' ,
                'type'     => 'checkbox' ,
                'std'      => 'no' ,
                'default'  => 'no' ,
                'desc'     => '' ,
                'desc_tip' => true ,
            ) ,
            array (
                'type' => $this->get_custom_field_type( 'get_global_selected_plans' )
            ) ,
            array ( 'type' => 'sectionend' , 'id' => $this->prefix . 'global_payment_plan_settings' ) ,
            array (
                'name' => __( 'General Settings' , $this->text_domain ) ,
                'type' => 'title' ,
                'id'   => $this->prefix . 'general_settings'
            ) ,
            array (
                'name'              => __( 'Create Next Payable Order' , $this->text_domain ) ,
                'id'                => $this->prefix . 'create_next_payable_order_before' ,
                'newids'            => $this->prefix . 'create_next_payable_order_before' ,
                'type'              => 'number' ,
                'std'               => '1' ,
                'default'           => '1' ,
                'desc'              => __( 'day(s)' , $this->text_domain ) ,
                'desc_tip'          => __( 'Payable order will be created before specified days. If set as 1 then order will be created one day before payment date' , $this->text_domain ) ,
                'custom_attributes' => array (
                    'min' => '1' ,
                ) ,
            ) ,
            array (
                'name'              => __( 'Overdue Period' , $this->text_domain ) ,
                'id'                => $this->prefix . 'specified_overdue_days' ,
                'newids'            => $this->prefix . 'specified_overdue_days' ,
                'type'              => 'number' ,
                'std'               => '0' ,
                'default'           => '0' ,
                'desc'              => __( 'day(s)' , $this->text_domain ) ,
                'desc_tip'          => __( 'If the payment is not made within the payment date, payment will goto overdue status and it will be in that status for the specified number of days.' , $this->text_domain ) ,
                'custom_attributes' => array (
                    'min' => '0' ,
                ) ,
            ) ,
            array (
                'name'     => __( 'Invoice Reminder' , $this->text_domain ) ,
                'id'       => $this->prefix . 'notify_invoice_before' ,
                'newids'   => $this->prefix . 'notify_invoice_before' ,
                'type'     => 'text' ,
                'std'      => '3,2,1' ,
                'default'  => '3,2,1' ,
                'desc'     => __( 'day(s) before next payment date' , $this->text_domain ) ,
                'desc_tip' => false ,
            ) ,
            array (
                'name'     => __( 'Overdue Reminder' , $this->text_domain ) ,
                'id'       => $this->prefix . 'notify_overdue_before' ,
                'newids'   => $this->prefix . 'notify_overdue_before' ,
                'type'     => 'text' ,
                'std'      => '1' ,
                'default'  => '1' ,
                'desc'     => __( 'day(s) after payment due date' , $this->text_domain ) ,
                'desc_tip' => false ,
            ) ,
            array (
                'name'     => __( 'Charge Tax' , $this->text_domain ) ,
                'id'       => $this->prefix . 'charge_tax_during' ,
                'newids'   => $this->prefix . 'charge_tax_during' ,
                'type'     => 'select' ,
                'options'  => array (
                    'initial-payment' => __( 'During Initial Payment' , $this->text_domain ) ,
                    'each-payment'    => __( 'During Each Payment' , $this->text_domain ) ,
                ) ,
                'std'      => 'each-payment' ,
                'default'  => 'each-payment' ,
                'desc'     => '' ,
                'desc_tip' => true ,
            ) ,
            array (
                'name'     => __( 'Disable Payment Gateways' , $this->text_domain ) ,
                'id'       => $this->prefix . 'disabled_payment_gateways' ,
                'newids'   => $this->prefix . 'disabled_payment_gateways' ,
                'type'     => 'multiselect' ,
                'options'  => _sumo_pp_get_active_payment_gateways() ,
                'std'      => array () ,
                'default'  => array () ,
                'desc'     => '' ,
                'desc_tip' => true ,
            ) ,
            array (
                'name'     => __( 'Show Deposit/Payment Plans Option for' , $this->text_domain ) ,
                'id'       => $this->prefix . 'show_deposit_r_payment_plans_for' ,
                'newids'   => $this->prefix . 'show_deposit_r_payment_plans_for' ,
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
                'type' => $this->get_custom_field_type( 'get_limited_users_of_payment_product' )
            ) ,
            array (
                'name'     => __( 'Select User Role(s)' , $this->text_domain ) ,
                'id'       => $this->prefix . 'get_limited_userroles_of_payment_product' ,
                'newids'   => $this->prefix . 'get_limited_userroles_of_payment_product' ,
                'type'     => 'multiselect' ,
                'options'  => _sumo_pp_get_user_roles( true ) ,
                'std'      => array () ,
                'default'  => array () ,
                'desc'     => '' ,
                'desc_tip' => true ,
            ) ,
            array ( 'type' => 'sectionend' , 'id' => $this->prefix . 'general_settings' ) ,
                ) ) ;
    }

    /**
     * Custom type field.
     */
    public function get_limited_users_of_payment_product() {

        _sumo_pp_wc_search_field( array (
            'class'       => 'wc-customer-search' ,
            'id'          => $this->prefix . 'get_limited_users_of_payment_product' ,
            'type'        => 'customer' ,
            'title'       => __( 'Select User(s)' , $this->text_domain ) ,
            'placeholder' => __( 'Search for a user&hellip;' , $this->text_domain ) ,
            'options'     => ( array ) get_option( $this->prefix . 'get_limited_users_of_payment_product' , array () )
        ) ) ;
    }

    /**
     * Custom type field.
     */
    public function get_global_selected_plans() {
        ?>
        <tr>
            <th>
                <?php _e( 'Select Plans' , $this->text_domain ) ; ?>
            </th>
            <td>
                <span class="<?php echo $this->prefix . 'add_plans' ; ?>">
                    <span class="woocommerce-help-tip" data-tip="<?php _e( 'Select the layout as per your theme preference' , $this->text_domain ) ; ?>"></span>
                    <a href="#" class="button" id="<?php echo $this->prefix . 'add_col_1_plan' ; ?>"><?php _e( 'Add Row for Column 1' , $this->text_domain ) ; ?></a>
                    <a href="#" class="button" id="<?php echo $this->prefix . 'add_col_2_plan' ; ?>"><?php _e( 'Add Row for Column 2' , $this->text_domain ) ; ?></a>
                    <span class="spinner"></span>
                </span>
                <?php
                $selected_plans     = get_option( $this->prefix . 'selected_plans' ) ;
                $selected_plans     = $bkw_selected_plans = is_array( $selected_plans ) && ! empty( $selected_plans ) ? $selected_plans : array ( 'col_1' => array () , 'col_2' => array () ) ;

                if ( ! isset( $bkw_selected_plans[ 'col_1' ] ) ) {
                    $selected_plans = array ( 'col_1' => array () , 'col_2' => array () ) ;

                    foreach ( $bkw_selected_plans as $row_id => $selected_plan ) {
                        $selected_plans[ 'col_1' ][] = ! empty( $selected_plan ) ? ( array ) $selected_plan : array () ;
                    }
                }
                ?> 
                <style>
                    div.selected_plans{
                        width:100%;float:left;padding:10px;
                        margin-top:10px;
                    }
                    .woocommerce table.form-table div.selected_plans table {
                        border:1px solid #f1f1f1 !important;
                        box-shadow:none !important;
                    }
                    .woocommerce table.form-table div.selected_plans table span.select2-container{
                        min-width:350px !important;
                    }
                </style>
                <div class="selected_plans">
                    <?php
                    foreach ( $selected_plans as $column_id => $selected_datas ) {
                        $inline_style = 'col_1' === $column_id ? 'width:49%;display:block;float:left;clear:none;' : 'width:49%;display:block;float:right;clear:none;margin-right:10px;' ;
                        ?>
                        <table class="widefat wc_input_table wc_gateways sortable <?php echo $this->prefix . 'footable ' . $this->prefix . "selected_col_{$column_id}_plans " . $this->prefix . 'selected_plans ' . $this->prefix . 'fields' ; ?>" style="<?php echo $inline_style ; ?>">
                            <tbody class="selected_plans">
                                <?php
                                if ( is_array( $selected_datas ) && ! empty( $selected_datas ) ) {
                                    foreach ( $selected_datas as $row_id => $selected_data ) {
                                        $selected_plan_field = '<tr><td class="sort" width="1%"></td><td>' ;
                                        $selected_plan_field .= _sumo_pp_wc_search_field( array (
                                            'class'       => 'wc-product-search' ,
                                            'action'      => '_sumo_pp_json_search_sumo_payment_plans' ,
                                            'id'          => "selected_{$column_id}_payment_plan_{$row_id}" ,
                                            'name'        => "_sumo_pp_selected_plans[{$column_id}][{$row_id}]" ,
                                            'type'        => 'payment_plans' ,
                                            'multiple'    => false ,
                                            'options'     => ( array ) $selected_data ,
                                            'placeholder' => __( 'Search for a payment plan&hellip;' , $this->text_domain ) ,
                                                ) , false ) ;
                                        $selected_plan_field .= '</td><td>' ;
                                        $selected_plan_field .= '<a href="#" class="remove_row button">X</a>' ;
                                        $selected_plan_field .= '</td></tr>' ;
                                        echo $selected_plan_field ;
                                    }
                                }
                                ?>
                            </tbody>
                        </table>
                        <?php
                    }
                    ?>
                </div>
            </td>
        </tr>
        <?php
    }

    /**
     * Delete the custom options.
     */
    public function custom_types_delete_options() {
        delete_option( $this->prefix . 'selected_plans' ) ;
        delete_option( $this->prefix . 'get_limited_users_of_payment_product' ) ;
    }

    /**
     * Save custom settings.
     */
    public function custom_types_save() {

        if ( isset( $_POST[ $this->prefix . 'get_limited_users_of_payment_product' ] ) ) {
            update_option( $this->prefix . 'get_limited_users_of_payment_product' ,  ! is_array( $_POST[ $this->prefix . 'get_limited_users_of_payment_product' ] ) ? array_filter( array_map( 'absint' , explode( ',' , $_POST[ $this->prefix . 'get_limited_users_of_payment_product' ] ) ) ) : $_POST[ $this->prefix . 'get_limited_users_of_payment_product' ]  ) ;
        }

        $selected_plans = isset( $_POST[ "{$this->prefix}selected_plans" ] ) ? $_POST[ "{$this->prefix}selected_plans" ] : array () ;
        foreach ( array ( 'col_1' , 'col_2' ) as $column_id ) {
            $selected_plans[ $column_id ] = ! empty( $selected_plans[ $column_id ] ) && is_array( $selected_plans[ $column_id ] ) ? array_map( 'implode' , (array_values( $selected_plans[ $column_id ] ) ) ) : array () ;
        }
        update_option( $this->prefix . 'selected_plans' , $selected_plans ) ;
    }

    /**
     * Save the custom options once.
     */
    public function custom_types_add_options() {
        add_option( $this->prefix . 'selected_plans' , array () ) ;
        add_option( $this->prefix . 'get_limited_users_of_payment_product' , array () ) ;

        //Backward compatibility
        if ( false === get_option( $this->prefix . 'balance_payment_due' ) ) {
            add_option( $this->prefix . 'pay_balance_after' , get_option( $this->prefix . 'pay_balance_after' ) ) ;
        } else {
            if ( add_option( $this->prefix . 'pay_balance_after' , get_option( $this->prefix . 'balance_payment_due' ) ) ) {
                delete_option( $this->prefix . 'balance_payment_due' ) ;
            }
        }

        $bkw_hide_payment_plans_for = get_option( $this->prefix . 'hide_payment_plans_only_for' ) ;

        if ( false !== $bkw_hide_payment_plans_for && is_array( $bkw_hide_payment_plans_for ) && ! empty( $bkw_hide_payment_plans_for ) ) {
            update_option( $this->prefix . 'show_deposit_r_payment_plans_for' , 'exclude_user_role' ) ;
            update_option( $this->prefix . 'get_limited_userroles_of_payment_product' , $bkw_hide_payment_plans_for ) ;
            delete_option( $this->prefix . 'hide_payment_plans_only_for' ) ;
        }
    }

}

return new SUMO_PP_General_Settings() ;

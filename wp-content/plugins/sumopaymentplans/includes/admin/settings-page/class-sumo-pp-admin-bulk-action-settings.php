<?php

/**
 * Bulk Action Settings.
 * 
 * @class SUMO_PP_Bulk_Action_Settings
 * @category Class
 */
class SUMO_PP_Bulk_Action_Settings extends SUMO_PP_Abstract_Admin_Settings {

    /**
     * SUMO_PP_Bulk_Action_Settings constructor.
     */
    public function __construct() {

        $this->id            = 'bulk_action' ;
        $this->label         = __( 'Bulk Action' , $this->text_domain ) ;
        $this->custom_fields = array (
            'get_tab_description' ,
            'get_product_select_type' ,
            'get_product_selector' ,
            'get_product_category_selector' ,
            'get_sumopaymentplans_status' ,
            'get_payment_type' ,
            'get_apply_global_level_settings' ,
            'get_force_deposit_r_payment_plans' ,
            'get_deposit_type' ,
            'get_deposit_price_type' ,
            'get_deposit_amount' ,
            'get_deposit_percentage' ,
            'get_min_deposit' ,
            'get_max_deposit' ,
            'get_pay_balance_type' ,
            'get_after_balance_payment_due_date' ,
            'get_selected_plans' ,
            'get_bulk_save_button' ,
                ) ;
        $this->settings      = $this->get_settings() ;
        $this->init() ;

        add_action( 'sumopaymentplans_submit_' . $this->id , array ( $this , 'remove_submit_and_reset' ) ) ;
        add_action( 'sumopaymentplans_reset_' . $this->id , array ( $this , 'remove_submit_and_reset' ) ) ;
    }

    /**
     * Get settings array.
     * @return array
     */
    public function get_settings() {
        global $current_section ;

        return apply_filters( 'sumopaymentplans_get_' . $this->id . '_settings' , array (
            array (
                'name' => __( 'Payment Plans Product Bulk Update Settings' , $this->text_domain ) ,
                'type' => 'title' ,
                'id'   => $this->prefix . 'bulk_action_settings'
            ) ,
            array (
                'type' => $this->get_custom_field_type( 'get_tab_description' )
            ) ,
            array (
                'name' => __( 'Product Bulk Update' , $this->text_domain ) ,
                'type' => 'title' ,
                'id'   => $this->prefix . 'product_bulk_update_settings'
            ) ,
            array (
                'type' => $this->get_custom_field_type( 'get_product_select_type' )
            ) ,
            array (
                'type' => $this->get_custom_field_type( 'get_product_selector' ) ,
            ) ,
            array (
                'type' => $this->get_custom_field_type( 'get_product_category_selector' )
            ) ,
            array (
                'type' => $this->get_custom_field_type( 'get_sumopaymentplans_status' ) ,
            ) ,
            array (
                'type' => $this->get_custom_field_type( 'get_payment_type' ) ,
            ) ,
            array (
                'type' => $this->get_custom_field_type( 'get_apply_global_level_settings' ) ,
            ) ,
            array (
                'type' => $this->get_custom_field_type( 'get_force_deposit_r_payment_plans' ) ,
            ) ,
            array (
                'type' => $this->get_custom_field_type( 'get_deposit_type' ) ,
            ) ,
            array (
                'type' => $this->get_custom_field_type( 'get_deposit_price_type' ) ,
            ) ,
            array (
                'type' => $this->get_custom_field_type( 'get_deposit_amount' ) ,
            ) ,
            array (
                'type' => $this->get_custom_field_type( 'get_deposit_percentage' ) ,
            ) ,
            array (
                'type' => $this->get_custom_field_type( 'get_min_deposit' ) ,
            ) ,
            array (
                'type' => $this->get_custom_field_type( 'get_max_deposit' ) ,
            ) ,
            array (
                'type' => $this->get_custom_field_type( 'get_pay_balance_type' ) ,
            ) ,
            array (
                'type' => $this->get_custom_field_type( 'get_after_balance_payment_due_date' ) ,
            ) ,
            array (
                'type' => $this->get_custom_field_type( 'get_selected_plans' ) ,
            ) ,
            array (
                'type' => $this->get_custom_field_type( 'get_bulk_save_button' ) ,
            ) ,
            array ( 'type' => 'sectionend' , 'id' => $this->prefix . 'product_bulk_update_settings' ) ,
            array ( 'type' => 'sectionend' , 'id' => $this->prefix . 'bulk_action_settings' ) ,
                ) ) ;
    }

    /**
     * Custom type field.
     */
    public function get_tab_description() {
        ?>
        <tr>
            <?php echo _e( 'Using these settings you can customize/modify the payment plans information in your site.' , $this->text_domain ) ; ?>
        </tr>
        <?php
    }

    /**
     * Custom type field.
     */
    public function get_product_select_type() {
        ?>
        <tr>
            <th>
                <?php _e( 'Select Products/Categories' , $this->text_domain ) ; ?>
            </th>
            <td>
                <select id="get_product_select_type">
                    <option value="all-products" <?php selected( 'pay-in-deposit' === get_option( "bulk{$this->prefix}get_product_select_type" , 'all-products' ) , true ) ; ?>><?php _e( 'All Products' , $this->text_domain ) ; ?></option>
                    <option value="selected-products" <?php selected( 'selected-products' === get_option( "bulk{$this->prefix}get_product_select_type" , 'all-products' ) , true ) ; ?>><?php _e( 'Selected Products' , $this->text_domain ) ; ?></option>
                    <option value="all-categories" <?php selected( 'all-categories' === get_option( "bulk{$this->prefix}get_product_select_type" , 'all-products' ) , true ) ; ?>><?php _e( 'All Categories' , $this->text_domain ) ; ?></option>
                    <option value="selected-categories" <?php selected( 'selected-categories' === get_option( "bulk{$this->prefix}get_product_select_type" , 'all-products' ) , true ) ; ?>><?php _e( 'Selected Categories' , $this->text_domain ) ; ?></option>
                </select>
            </td>
        </tr>
        <?php
    }

    /**
     * Custom type field.
     */
    public function get_product_selector() {

        _sumo_pp_wc_search_field( array (
            'class'       => 'wc-product-search' ,
            'id'          => 'get_selected_products' ,
            'type'        => 'product' ,
            'action'      => 'woocommerce_json_search_products_and_variations' ,
            'title'       => __( 'Select Particular Product(s)' , $this->text_domain ) ,
            'placeholder' => __( 'Search for a product&hellip;' , $this->text_domain ) ,
            'options'     => get_option( "bulk{$this->prefix}get_selected_products" , array () ) ,
        ) ) ;
    }

    /**
     * Custom type field.
     */
    public function get_product_category_selector() {
        ?>
        <tr>
            <th>
                <?php _e( 'Select Particular Categories' , $this->text_domain ) ; ?>
            </th>
            <td>                
                <select id="get_selected_categories" multiple="multiple" style="min-width:350px;">
                    <?php
                    $option_value = get_option( "bulk{$this->prefix}get_selected_categories" , array () ) ;

                    foreach ( _sumo_pp_get_product_categories() as $key => $val ) {
                        ?>
                        <option value="<?php echo esc_attr( $key ) ; ?>"
                        <?php
                        if ( is_array( $option_value ) ) {
                            selected( in_array( ( string ) $key , $option_value , true ) , true ) ;
                        } else {
                            selected( $option_value , ( string ) $key ) ;
                        }
                        ?>
                                >
                            <?php echo esc_html( $val ) ; ?></option>
                        <?php
                    }
                    ?>
                </select>
            </td>
        </tr>
        <?php
    }

    /**
     * Custom type field.
     */
    public function get_sumopaymentplans_status() {
        ?>
        <tr>
            <th>
                <?php _e( 'SUMO Payment Plans' , $this->text_domain ) ; ?>
            </th>
            <td>               
                <input type="checkbox" id="enable_sumopaymentplans" value="yes" <?php checked( 'yes' === get_option( "bulk{$this->prefix}enable_sumopaymentplans" , 'no' ) , true ) ; ?>> 
            </td>
        </tr>
        <?php
    }

    /**
     * Custom type field.
     */
    public function get_payment_type() {
        ?>
        <tr class="bulk-fields-wrapper">
            <th>
                <?php _e( 'Payment Type' , $this->text_domain ) ; ?>
            </th>
            <td>
                <select id="payment_type">
                    <option value="pay-in-deposit" <?php selected( 'pay-in-deposit' === get_option( "bulk{$this->prefix}payment_type" , 'pay-in-deposit' ) , true ) ; ?>><?php _e( 'Pay a Deposit Amount' , $this->text_domain ) ; ?></option>
                    <option value="payment-plans" <?php selected( 'payment-plans' === get_option( "bulk{$this->prefix}payment_type" , 'pay-in-deposit' ) , true ) ; ?>><?php _e( 'Pay with Payment Plans' , $this->text_domain ) ; ?></option>
                </select>
            </td>
        </tr>
        <?php
    }

    /**
     * Custom type field.
     */
    public function get_apply_global_level_settings() {
        ?>
        <tr class="bulk-fields-wrapper">
            <th>
                <?php _e( 'Apply Global Level Settings' , $this->text_domain ) ; ?>
            </th>
            <td>
                <input type="checkbox" id="apply_global_settings" value="yes" <?php checked( 'yes' === get_option( "bulk{$this->prefix}apply_global_settings" , 'no' ) , true ) ; ?>>                 
            </td>
        </tr>
        <?php
    }

    /**
     * Custom type field.
     */
    public function get_force_deposit_r_payment_plans() {
        ?>
        <tr class="bulk-fields-wrapper">
            <th>
                <?php _e( 'Force Deposit/Payment Plans' , $this->text_domain ) ; ?>
            </th>
            <td>
                <input type="checkbox" id="force_deposit" value="yes" <?php checked( 'yes' === get_option( "bulk{$this->prefix}force_deposit" , 'no' ) , true ) ; ?>>                                 
            </td>
        </tr>
        <?php
    }

    /**
     * Custom type field.
     */
    public function get_deposit_type() {
        ?>
        <tr class="bulk-fields-wrapper">
            <th>
                <?php _e( 'Deposit Type' , $this->text_domain ) ; ?>
            </th>
            <td>
                <select id="deposit_type">
                    <option value="pre-defined" <?php selected( 'pre-defined' === get_option( "bulk{$this->prefix}deposit_type" , 'pre-defined' ) , true ) ; ?>><?php _e( 'Predefined Deposit Amount' , $this->text_domain ) ; ?></option>
                    <option value="user-defined" <?php selected( 'user-defined' === get_option( "bulk{$this->prefix}deposit_type" , 'pre-defined' ) , true ) ; ?>><?php _e( 'User Defined Deposit Amount' , $this->text_domain ) ; ?></option>
                </select>
            </td>
        </tr>
        <?php
    }

    /**
     * Custom type field.
     */
    public function get_deposit_price_type() {
        ?>
        <tr class="bulk-fields-wrapper">
            <th>
                <?php _e( 'Deposit Price Type' , $this->text_domain ) ; ?>
            </th>
            <td>
                <select id="deposit_price_type">
                    <option value="fixed-price" <?php selected( 'fixed-price' === get_option( "bulk{$this->prefix}deposit_price_type" , 'fixed-price' ) , true ) ; ?>><?php _e( 'Fixed Price' , $this->text_domain ) ; ?></option>
                    <option value="percent-of-product-price" <?php selected( 'percent-of-product-price' === get_option( "bulk{$this->prefix}deposit_price_type" , 'fixed-price' ) , true ) ; ?>><?php _e( 'Percentage of Product Price' , $this->text_domain ) ; ?></option>
                </select>
            </td>
        </tr>
        <?php
    }

    /**
     * Custom type field.
     */
    public function get_deposit_amount() {
        ?>
        <tr class="bulk-fields-wrapper">
            <th>
                <?php _e( 'Deposit Amount' , $this->text_domain ) ; ?>
            </th>
            <td>
                <input id="fixed_deposit_price" type="number" min="0.01" step="0.01" value="<?php echo get_option( "bulk{$this->prefix}fixed_deposit_price" , '0.01' ) ; ?>" style="width:150px;"/>
            </td>
        </tr>
        <?php
    }

    /**
     * Custom type field.
     */
    public function get_deposit_percentage() {
        ?>
        <tr class="bulk-fields-wrapper">
            <th>
                <?php _e( 'Deposit Percentage' , $this->text_domain ) ; ?>
            </th>
            <td>
                <input id="fixed_deposit_percent" type="number" min="0.01" max="99.99" step="0.01" value="<?php echo get_option( "bulk{$this->prefix}fixed_deposit_percent" , '0.01' ) ; ?>" style="width:150px;"/>
            </td>
        </tr>
        <?php
    }

    /**
     * Custom type field.
     */
    public function get_min_deposit() {
        ?>
        <tr class="bulk-fields-wrapper">
            <th>
                <?php _e( 'Minimum Deposit(%)' , $this->text_domain ) ; ?>
            </th>
            <td>
                <input id="min_deposit" type="number" min="0.01" max="99.99" step="0.01" value="<?php echo get_option( "bulk{$this->prefix}min_deposit" , '0.01' ) ; ?>" style="width:150px;"/>
            </td>
        </tr>
        <?php
    }

    /**
     * Custom type field.
     */
    public function get_max_deposit() {
        ?>
        <tr class="bulk-fields-wrapper">
            <th>
                <?php _e( 'Maximum Deposit(%)' , $this->text_domain ) ; ?>
            </th>
            <td>
                <input id="max_deposit" type="number" min="0.01" max="99.99" step="0.01" value="<?php echo get_option( "bulk{$this->prefix}max_deposit" , '0.01' ) ; ?>" style="width:150px;"/>
            </td>
        </tr>
        <?php
    }

    /**
     * Custom type field.
     */
    public function get_pay_balance_type() {
        ?>
        <tr class="bulk-fields-wrapper">
            <th>
                <?php _e( 'Deposit Balance Payment Due Date' , $this->text_domain ) ; ?>
            </th>
            <td>
                <select id="pay_balance_type" style="width:95px;">
                    <option value="after" <?php selected( 'after' === get_option( "bulk{$this->prefix}pay_balance_type" , 'after' ) , true ) ; ?>><?php _e( 'After' , $this->text_domain ) ; ?></option>
                    <option value="before" <?php selected( 'before' === get_option( "bulk{$this->prefix}pay_balance_type" , 'after' ) , true ) ; ?>><?php _e( 'Before' , $this->text_domain ) ; ?></option>
                </select>
                <input id="pay_balance_after" type="number" value="<?php echo get_option( "bulk{$this->prefix}pay_balance_after" , '1' ) ; ?>" style="width:150px;"/>
                <input id="pay_balance_before" type="text" placeholder="<?php esc_attr_e( 'YYYY-MM-DD' , $this->text_domain ) ?>" value="<?php echo get_option( "bulk{$this->prefix}pay_balance_before" , '' ) ; ?>" style="width:150px;"/>
            </td>
        </tr>
        <?php
    }

    /**
     * Custom type field.
     */
    public function get_after_balance_payment_due_date() {
        ?>
        <tr class="bulk-fields-wrapper">
            <th>
                <?php _e( 'After Balance Payment Due Date' , $this->text_domain ) ; ?>
            </th>
            <td>
                <select id="set_expired_deposit_payment_as">
                    <option value="normal" <?php selected( 'normal' === get_option( "bulk{$this->prefix}set_expired_deposit_payment_as" , 'normal' ) , true ) ; ?>><?php _e( 'Disable SUMO Payment Plans' , $this->text_domain ) ; ?></option>
                    <option value="out-of-stock" <?php selected( 'out-of-stock' === get_option( "bulk{$this->prefix}set_expired_deposit_payment_as" , 'normal' ) , true ) ; ?>><?php _e( 'Set Product as Out of Stock' , $this->text_domain ) ; ?></option>
                </select>
            </td>
        </tr>
        <?php
    }

    /**
     * Custom type field.
     */
    public function get_selected_plans() {
        ?>
        <tr class="bulk-fields-wrapper">
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
                $selected_plans = get_option( "bulk{$this->prefix}selected_plans" , array () ) ;
                $selected_plans = is_array( $selected_plans ) && ! empty( $selected_plans ) ? $selected_plans : array ( 'col_1' => array () , 'col_2' => array () ) ;
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
     * Custom type field.
     */
    public function get_bulk_save_button() {
        ?>
        <tr>
            <td>
                <input type="submit" id="bulk_update" data-is_bulk_update ="true" class="button-primary" value="Save and Update" />
                <img class="updater" src="<?php echo SUMO_PP_PLUGIN_URL . '/assets/images/update.gif' ; ?>" style="width:32px;height:32px;position:absolute;display: none;"/>
            </td>
        </tr>
        <?php
    }

    public function remove_submit_and_reset() {
        return false ;
    }

}

return new SUMO_PP_Bulk_Action_Settings() ;

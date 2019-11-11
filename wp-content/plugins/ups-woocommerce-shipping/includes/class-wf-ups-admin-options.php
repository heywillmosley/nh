<?php
if( !class_exists('WF_UPS_Admin_Options') ){
    class WF_UPS_Admin_Options{
        function __construct(){
			$this->init();
        }

        function init(){
            //add a custome field in product page
            add_action( 'woocommerce_product_options_shipping', array($this,'wf_add_custom_field_ups')  );

            //Saving the values
            add_action( 'woocommerce_process_product_meta', array( $this, 'wf_save_custom_field_ups' ) );
        }

        function wf_add_custom_field_ups() {
			 
		// Print a custom select field
		woocommerce_wp_select( array(
			'id' => '_wf_ups_deliveryconfirmation',
			'label' => __('Delivery Confirmation'),
					'options'	=> array(
						0	=> __( 'Confirmation Not Required', 'ups-woocommerce-shipping' ),
						1	=> __( 'Confirmation Required', 'ups-woocommerce-shipping' ),
						2	=> __( 'Confirmation With Signature', 'ups-woocommerce-shipping' ),
						3	=> __( 'Confirmation With Adult Signature', 'ups-woocommerce-shipping' )
					),
			'desc_tip' => false,
		) );

		woocommerce_wp_text_input( array(
			'id'		=> '_wf_ups_custom_declared_value',
			'label'		=> __( 'Custom Declared Value (UPS)', 'ups-woocommerce-shipping' ),
			'description'	=> __('This amount will be reimbursed from UPS if products get damaged and you have opt for Insurance.','ups-woocommerce-shipping'),
			'desc_tip'	=> 'true',
			'placeholder'	=> __( 'Insurance amount UPS', 'ups-woocommerce-shipping' )
		) );
		
		woocommerce_wp_text_input( array(
			'id'		=> '_wf_ups_hst',
			'label'		=> __( 'H.S. tariff (UPS)', 'ups-woocommerce-shipping' ),
			'description'	=> __('H.S. tariff number','ups-woocommerce-shipping'),
			'desc_tip'	=> 'true',
		) );
		
		//Direct Delivery Option under Product Shipping tab
		woocommerce_wp_checkbox( array(
			'id'		=> '_wf_ups_direct_delivery',
			'label'		=> __( 'Direct Delivery (UPS)', 'ups-woocommerce-shipping' ),
			'description'	=> __('Check this to enable direct delivery.','ups-woocommerce-shipping'),
			'desc_tip'	=> 'true',
                ) );
        }
		

        function wf_save_custom_field_ups( $post_id ) {

		if ( isset( $_POST['_wf_ups_deliveryconfirmation'] ) ) {
			update_post_meta( $post_id, '_wf_ups_deliveryconfirmation', esc_attr( $_POST['_wf_ups_deliveryconfirmation'] ) );
		}

		// Update the Insurance amount on individual product page
		if( isset($_POST['_wf_ups_custom_declared_value'] ) ) {
			update_post_meta( $post_id, '_wf_ups_custom_declared_value', esc_attr( $_POST['_wf_ups_custom_declared_value'] ) );
		}
		if( isset($_POST['_wf_ups_hst'] ) ) {
			update_post_meta( $post_id, '_wf_ups_hst', esc_attr( $_POST['_wf_ups_hst'] ) );
		}
		
		//Update Direct Delivery option
		$is_direct_delivery =  ( isset( $_POST['_wf_ups_direct_delivery'] ) && esc_attr($_POST['_wf_ups_direct_delivery']=='yes')  ) ? esc_attr($_POST['_wf_ups_direct_delivery']) : false;
		update_post_meta( $post_id, '_wf_ups_direct_delivery', $is_direct_delivery );
	}
    }
    new WF_UPS_Admin_Options();
}

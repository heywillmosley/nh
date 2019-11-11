<?php
include_once('ic_woocommerce_auto_email_report_functions.php');
if(!class_exists('Ic_Wc_Auto_Email_Report_Settings')){
	class Ic_Wc_Auto_Email_Report_Settings extends Ic_Wc_Auto_Email_Report_Functions{
		
		public function __construct(){
			$this->ic_wc_auto_email_settings_init();
		}
		
		function ic_wc_auto_email_settings_init(){			
			register_setting( 'wporg', 'wporg_options' );
			
			add_settings_section(
			 'wporg_section_developers',
			 __( 'The Matrix has you.', 'icwoocommerce_textdomains' ),
			 array($this, 'wporg_section_developers_cb'),
			 'wporg'
			 );
			
			add_settings_field(
			 'wporg_field_pill', // as of WP 4.6 this value is used only internally
			 // use $args' label_for to populate the id inside the callback
			 __( 'Pill', 'icwoocommerce_textdomains' ),
			 array($this, 'wporg_field_pill_cb'),
			 'wporg',
			 'wporg_section_developers',
			 array('label_for' => 'wporg_field_pill','class' => 'wporg_row','wporg_custom_data' => 'custom')
			 );
		}
		
		function wporg_section_developers_cb( $args ) {
		 ?>
		 <p id="<?php echo esc_attr( $args['id'] ); ?>"><?php esc_html_e( 'Follow the white rabbit.', 'icwoocommerce_textdomains' ); ?></p>
		 <?php
		}
		
		function wporg_field_pill_cb( $args ) {
		 // get the value of the setting we've registered with register_setting()
		 $options = get_option( 'wporg_options' );
		 // output the field
		 ?>
		 <select id="<?php echo esc_attr( $args['label_for'] ); ?>"
		 data-custom="<?php echo esc_attr( $args['wporg_custom_data'] ); ?>"
		 name="wporg_options[<?php echo esc_attr( $args['label_for'] ); ?>]"
		 >
		 <option value="red" <?php echo isset( $options[ $args['label_for'] ] ) ? ( selected( $options[ $args['label_for'] ], 'red', false ) ) : ( '' ); ?>>
		 <?php esc_html_e( 'red pill', 'icwoocommerce_textdomains' ); ?>
		 </option>
		 <option value="blue" <?php echo isset( $options[ $args['label_for'] ] ) ? ( selected( $options[ $args['label_for'] ], 'blue', false ) ) : ( '' ); ?>>
		 <?php esc_html_e( 'blue pill', 'icwoocommerce_textdomains' ); ?>
		 </option>
		 </select>
		 <p class="description">
		 <?php esc_html_e( 'You take the blue pill and the story ends. You wake in your bed and you believe whatever you want to believe.', 'icwoocommerce_textdomains' ); ?>
		 </p>
		 <p class="description">
		 <?php esc_html_e( 'You take the red pill and you stay in Wonderland and I show you how deep the rabbit-hole goes.', 'icwoocommerce_textdomains' ); ?>
		 </p>
		 <?php
		}
		
	}
}

?>
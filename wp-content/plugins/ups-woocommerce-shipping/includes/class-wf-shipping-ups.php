<?php

include('class-wf-freight-ups.php');
/**
 * WF_Shipping_UPS class.
 *
 * @extends WC_Shipping_Method
 */
class WF_Shipping_UPS extends WC_Shipping_Method {
	public $mode='volume_based';
	private $endpoint = 'https://wwwcie.ups.com/ups.app/xml/Rate';
	private $freight_endpoint = 'https://wwwcie.ups.com/rest/FreightRate';

	/**
	 * For Delivery Confirmation below array of countries will be considered as domestic, Confirmed by UPS.
	 * US to US, CA to CA, PR to PR are considered as domestic, all other shipments are international.
	 * @var array 
	 */
	public $dc_domestic_countries = array( 'US', 'CA', 'PR');
	
	private $pickup_code = array(
		'01' => "Daily Pickup",
		'03' => "Customer Counter",
		'06' => "One Time Pickup",
		'07' => "On Call Air",
		'19' => "Letter Center",
		'20' => "Air Service Center",
	);
	
	private $customer_classification_code = array(
		'NA' => "Default",
		'00' => "Rates Associated with Shipper Number",
		'01' => "Daily Rates",
		'04' => "Retail Rates",
		'05' => "Regional Rates",
		'06' => "General List Rates",
		'53' => "Standard List Rates",
	);

	private $services = array(
		// Domestic
		"12" => "3 Day Select",
		"03" => "Ground",
		"02" => "2nd Day Air",
		"59" => "2nd Day Air AM",
		"01" => "Next Day Air",
		"13" => "Next Day Air Saver",
		"14" => "Next Day Air Early AM",

		// International
		"11" => "Standard",
		"07" => "Worldwide Express",
		"54" => "Worldwide Express Plus",
		"08" => "Worldwide Expedited",
		"65" => "Saver",
		
		// SurePost
		"92" =>	"SurePost Less than 1 lb",
		"93" =>	"SurePost 1 lb or Greater",
		"94" =>	"SurePost BPM",
		"95" =>	"SurePost Media",
		
		//New Services
		"M2" => "First Class Mail",
		"M3" => "Priority Mail",
		"M4" => "Expedited Mail Innovations ",
		"M5" => "Priority Mail Innovations ",
		"M6" => "EconomyMail Innovations ",
		"70" => "Access Point Economy ",
		"96" => "Worldwide Express Freight",
		
		// "US48" => "Ground with Freight",
		
	);
	private $freigth_services=array(
											'308'=>'Freight LTL',
											'309'=>'Freight LTL - Guaranteed',
											'334'=>'Freight LTL - Guaranteed A.M.',
											'349'=>'Standard LTL',
											);
	
	public $freight_package_type_code_list=array(
											"BAG"=>"Bag",
											"BAL"=>"Bale",
											"BAR"=>"Barrel",
											"BDL"=>"Bundle",
											"BIN"=>"Bin",
											"BOX"=>"Box",
											"BSK"=>"Basket",
											"BUN"=>"Bunch",
											"CAB"=>"Cabinet",
											"CAN"=>"Can",
											"CAR"=>"Carrier",
											"CAS"=>"Case",
											"CBY"=>"Carboy",
											"CON"=>"Container",
											"CRT"=>"Crate",
											"CSK"=>"Cask",
											"CTN"=>"Carton",
											"CYL"=>"Cylinder",
											"DRM"=>"Drum",
											"LOO"=>"Loose",
											"OTH"=>"Other",
											"PAL"=>"Pail",
											"PCS"=>"Pieces",
											"PKG"=>"Package",
											"PLN"=>"Pipe Line",
											"PLT"=>"Pallet",
											"RCK"=>"Rack",
											"REL"=>"Reel",
											"ROL"=>"Roll",
											"SKD"=>"Skid",
											"SPL"=>"Spool",
											"TBE"=>"Tube",
											"TNK"=>"Tank",
											"UNT"=>"Unit",
											"VPK"=>"Van Pack",
											"WRP"=>"Wrapped",
											 );
	public  $freight_package_type_code='PLT';
	public 	$freight_shippernumber='';
	public 	$freight_billing_option_code='10';
	public 	$freight_billing_option_code_list=array('10'=>'Prepaid','30'=>'Bill to Third Party','40'=>'Freight Collect');
	public 	$freight_handling_unit_one_type_code='PLT';
	public  $freight_class=50;
	
	private $ups_surepost_services = array(92, 93, 94, 95);

	private $eu_array = array('BE','BG','CZ','DK','DE','EE','IE','GR','ES','FR','HR','IT','CY','LV','LT','LU','HU','MT','NL','AT','PT','RO','SI','SK','FI','GB');
	
	private $no_postcode_country_array = array('AE','AF','AG','AI','AL','AN','AO','AW','BB','BF','BH','BI','BJ','BM','BO','BS','BT','BW','BZ','CD','CF','CG','CI','CK','CL','CM','CO','CR','CV','DJ','DM','DO','EC','EG','ER','ET','FJ','FK','GA','GD','GH','GI','GM','GN','GQ','GT','GW','GY','HK','HN','HT','IE','IQ','IR','JM','JO','KE','KH','KI','KM','KN','KP','KW','KY','LA','LB','LC','LK','LR','LS','LY','ML','MM','MO','MR','MS','MT','MU','MW','MZ','NA','NE','NG','NI','NP','NR','NU','OM','PA','PE','PF','PY','QA','RW','SA','SB','SC','SD','SL','SN','SO','SR','SS','ST','SV','SY','TC','TD','TG','TL','TO','TT','TV','TZ','UG','UY','VC','VE','VG','VN','VU','WS','XA','XB','XC','XE','XL','XM','XN','XS','YE','ZM','ZW');
	
	// Shipments Originating in the European Union
	private $euservices = array(
		"07" => "UPS Express",
		"08" => "UPS ExpeditedSM",
		"11" => "UPS Standard",
		"54" => "UPS Express PlusSM",
		"65" => "UPS Saver",
		"70" => "Access Point Economy ",
	);

	private $polandservices = array(
		"07" => "UPS Express",
		"08" => "UPS ExpeditedSM",
		"11" => "UPS Standard",
		"54" => "UPS Express PlusSM",
		"65" => "UPS Saver",
		"82" => "UPS Today Standard",
		"83" => "UPS Today Dedicated Courier",
		"84" => "UPS Today Intercity",
		"85" => "UPS Today Express",
		"86" => "UPS Today Express Saver",
	);

	// Packaging not offered at this time: 00 = UNKNOWN, 30 = Pallet, 04 = Pak
	// Code 21 = Express box is valid code, but doesn't have dimensions
	// References:
	// http://www.ups.com/content/us/en/resources/ship/packaging/supplies/envelopes.html
	// http://www.ups.com/content/us/en/resources/ship/packaging/supplies/paks.html
	// http://www.ups.com/content/us/en/resources/ship/packaging/supplies/boxes.html
	private $packaging = array(
		"01" => array(
					"name" 	 => "UPS Letter",
					"length" => "12.5",
					"width"  => "9.5",
					"height" => "0.25",
					"weight" => "0.5"
				),
		"03" => array(
					"name" 	 => "Tube",
					"length" => "38",
					"width"  => "6",
					"height" => "6",
					"weight" => "100"
				),
		"04" => array(
					"name" 	 => "PAK",
					"length" => "17",
					"width"  => "13",
					"height" => "1",
					"weight" => "100"
				),
		"24" => array(
					"name" 	 => "25KG Box",
					"length" => "19.375",
					"width"  => "17.375",
					"height" => "14",
					"weight" => "25"
				),
		"25" => array(
					"name" 	 => "10KG Box",
					"length" => "16.5",
					"width"  => "13.25",
					"height" => "10.75",
					"weight" => "10"
				),
		"2a" => array(
					"name" 	 => "Small Express Box",
					"length" => "13",
					"width"  => "11",
					"height" => "2",
					"weight" => "100"
				),
		"2b" => array(
					"name" 	 => "Medium Express Box",
					"length" => "15",
					"width"  => "11",
					"height" => "3",
					"weight" => "100"
				),
		"2c" => array(
					"name" 	 => "Large Express Box",
					"length" => "18",
					"width"  => "13",
					"height" => "3",
					"weight" => "30"
				)
	);

	private $packaging_select = array(
		"01" => "UPS Letter",
		"03" => "Tube",
		"04" => "PAK",
		"24" => "25KG Box",
		"25" => "10KG Box",
		"2a" => "Small Express Box",
		"2b" => "Medium Express Box",
		"2c" => "Large Express Box",
	);

	/**
	 * __construct function.
	 *
	 * @access public
	 * @return void
	 */
	public function __construct( $order=null ) {
		if( $order ){
			$this->order = $order;
		}

		$this->id				 = WF_UPS_ID;
		$this->method_title	   = __( 'UPS', 'ups-woocommerce-shipping' );
		$this->method_description = __( 'The <strong>UPS</strong> extension obtains rates dynamically from the UPS API during cart/checkout.', 'ups-woocommerce-shipping' );
		
		// WF: Load UPS Settings.
		$ups_settings 		= get_option( 'woocommerce_'.WF_UPS_ID.'_settings', null ); 
		$ups_settings		= apply_filters('wf_ups_shipment_settings', $ups_settings, $order);

		$api_mode	  		= isset( $ups_settings['api_mode'] ) ? $ups_settings['api_mode'] : 'Test';
		if( "Live" == $api_mode ) {
			$this->endpoint = 'https://onlinetools.ups.com/ups.app/xml/Rate';
			$this->freight_endpoint='https://onlinetools.ups.com/rest/FreightRate';
		}
		else {
			$this->endpoint = 'https://wwwcie.ups.com/ups.app/xml/Rate';
			$this->freight_endpoint='https://wwwcie.ups.com/rest/FreightRate';
		}
		
		$this->init();
	}

	/**
	 * Output a message or error
	 * @param  string $message
	 * @param  string $type
	 */
	public function debug( $message, $type = 'notice' ) {
		// Hard coding to 'notice' as recently noticed 'error' is breaking with wc_add_notice.
		$type = 'notice';
		if ( $this->debug && !is_admin() ) { //WF: do not call wc_add_notice from admin.
			if ( version_compare( WOOCOMMERCE_VERSION, '2.1', '>=' ) ) {
				wc_add_notice( $message, $type );
			} else {
				global $woocommerce;
				$woocommerce->add_message( $message );
			}
		}
	}

	/**
	 * init function.
	 *
	 * @access public
	 * @return void
	 */
	private function init() {
		global $woocommerce;
		// Load the settings.
		$this->init_form_fields();
		$this->init_settings();
		$this->settings	=	apply_filters('wf_ups_shipment_settings', $this->settings, '');

		// Define user set variables
		$this->mode=isset( $this->settings['packing_algorithm'] ) ? $this->settings['packing_algorithm'] : 'volume_based';
		$this->enabled				= isset( $this->settings['enabled'] ) ? $this->settings['enabled'] : $this->enabled;
		$this->title				= isset( $this->settings['title'] ) ? $this->settings['title'] : $this->method_title;
		$this->availability			= isset( $this->settings['availability'] ) ? $this->settings['availability'] : 'all';
		$this->countries	   		= isset( $this->settings['countries'] ) ? $this->settings['countries'] : array();

		// API Settings
		$this->user_id		 		= isset( $this->settings['user_id'] ) ? $this->settings['user_id'] : '';

		// WF: Print Label - Start
		$this->disble_ups_print_label	= isset( $this->settings['disble_ups_print_label'] ) ? $this->settings['disble_ups_print_label'] : '';
		$this->print_label_type	  	= isset( $this->settings['print_label_type'] ) ? $this->settings['print_label_type'] : 'gif';
		$this->show_label_in_browser	= isset( $this->settings['show_label_in_browser'] ) ? $this->settings['show_label_in_browser'] : 'no';
		$this->ship_from_address	  	= isset( $this->settings['ship_from_address'] ) ? $this->settings['ship_from_address'] : 'origin_address';
		$this->disble_shipment_tracking	= isset( $this->settings['disble_shipment_tracking'] ) ? $this->settings['disble_shipment_tracking'] : 'TrueForCustomer';
		$this->api_mode	  			= isset( $this->settings['api_mode'] ) ? $this->settings['api_mode'] : 'Test';
		$this->ups_user_name			= isset( $this->settings['ups_user_name'] ) ? $this->settings['ups_user_name'] : '';
		$this->ups_display_name			= isset( $this->settings['ups_display_name'] ) ? $this->settings['ups_display_name'] : '';
		$this->phone_number 			= isset( $this->settings['phone_number'] ) ? $this->settings['phone_number'] : '';
		// WF: Print Label - End

		$this->user_id		 		= isset( $this->settings['user_id'] ) ? $this->settings['user_id'] : '';
		$this->password				= isset( $this->settings['password'] ) ? $this->settings['password'] : '';
		$this->access_key	  		= isset( $this->settings['access_key'] ) ? $this->settings['access_key'] : '';
		$this->shipper_number  		= isset( $this->settings['shipper_number'] ) ? $this->settings['shipper_number'] : '';
		$this->negotiated	  		= isset( $this->settings['negotiated'] ) && $this->settings['negotiated'] == 'yes' ? true : false;
		$this->tax_indicator	  	= isset( $this->settings['tax_indicator'] ) && $this->settings['tax_indicator'] == 'yes' ? true : false;
		$this->origin_addressline 	= isset( $this->settings['origin_addressline'] ) ? $this->settings['origin_addressline'] : '';
		$this->origin_city 			= isset( $this->settings['origin_city'] ) ? $this->settings['origin_city'] : '';
		$this->origin_postcode 		= isset( $this->settings['origin_postcode'] ) ? $this->settings['origin_postcode'] : '';
		$this->origin_country_state = isset( $this->settings['origin_country_state'] ) ? $this->settings['origin_country_state'] : '';
		$this->debug	  			= isset( $this->settings['debug'] ) && $this->settings['debug'] == 'yes' ? true : false;

		// Pickup and Destination
		$this->pickup			= isset( $this->settings['pickup'] ) ? $this->settings['pickup'] : '01';
		$this->customer_classification = isset( $this->settings['customer_classification'] ) ? $this->settings['customer_classification'] : '99';
		$this->residential		= isset( $this->settings['residential'] ) && $this->settings['residential'] == 'yes' ? true : false;

		// Services and Packaging
		$this->offer_rates	 	= isset( $this->settings['offer_rates'] ) ? $this->settings['offer_rates'] : 'all';
		$this->fallback		   	= ! empty( $this->settings['fallback'] ) ? $this->settings['fallback'] : '';
		$this->currency_type	= ! empty( $this->settings['currency_type'] ) ? $this->settings['currency_type'] : get_woocommerce_currency();
		$this->conversion_rate	= ! empty( $this->settings['conversion_rate'] ) ? $this->settings['conversion_rate'] : 1;
		$this->packing_method  	= isset( $this->settings['packing_method'] ) ? $this->settings['packing_method'] : 'per_item';
		$this->ups_packaging	= isset( $this->settings['ups_packaging'] ) ? $this->settings['ups_packaging'] : array();
		$this->custom_services  = isset( $this->settings['services'] ) ? $this->settings['services'] : array();
		$this->boxes		   	= isset( $this->settings['boxes'] ) ? $this->settings['boxes'] : array();
		$this->insuredvalue 	= isset( $this->settings['insuredvalue'] ) && $this->settings['insuredvalue'] == 'yes' ? true : false;
		$this->enable_freight 	= isset( $this->settings['enable_freight'] ) && $this->settings['enable_freight'] == 'yes' ? true : false;		
		$this->box_max_weight			=	$this->get_option( 'box_max_weight' );
		$this->weight_packing_process	=	$this->get_option( 'weight_packing_process' );
		$this->service_code 	= '';
		$this->min_amount	   = isset( $this->settings['min_amount'] ) ? $this->settings['min_amount'] : 0;
		// $this->ground_freight 	= isset( $this->settings['ground_freight'] ) && $this->settings['ground_freight'] == 'yes' ? true : false;
		
		// Units
		$this->units			= isset( $this->settings['units'] ) ? $this->settings['units'] : 'imperial';

		if ( $this->units == 'metric' ) {
			$this->weight_unit = 'KGS';
			$this->dim_unit	= 'CM';
		} else {
			$this->weight_unit = 'LBS';
			$this->dim_unit	= 'IN';
		}
		
		//Advanced Settings
		$this->ssl_verify			= isset( $this->settings['ssl_verify'] ) ? $this->settings['ssl_verify'] : false;
		$this->accesspoint_locator 			= (isset($this->settings[ 'accesspoint_locator']) && $this->settings[ 'accesspoint_locator']=='yes') ? true : false;

		$this->xa_show_all		= isset( $this->settings['xa_show_all'] ) && $this->settings['xa_show_all'] == 'yes' ? true : false;


		if (strstr($this->origin_country_state, ':')) :
			// WF: Following strict php standards.
			$origin_country_state_array = explode(':',$this->origin_country_state);
			$this->origin_country = current($origin_country_state_array);
			$origin_country_state_array = explode(':',$this->origin_country_state);
			$this->origin_state   = end($origin_country_state_array);
		else :
			$this->origin_country = $this->origin_country_state;
			$this->origin_state   = '';
		endif;
		$this->origin_custom_state   = (isset( $this->settings['origin_custom_state'] )&& !empty($this->settings['origin_custom_state'])) ? $this->settings['origin_custom_state'] : $this->origin_state;
		
		// COD selected
		$this->cod=false;
		$this->cod_total=0;

		add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
		add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'clear_transients' ) );

	}

	/**
	 * environment_check function.
	 *
	 * @access public
	 * @return void
	 */
	private function environment_check() {
		global $woocommerce;

		$error_message = '';

		// WF: Print Label - Start
		// Check for UPS User Name
		if ( ! $this->ups_user_name && $this->enabled == 'yes' ) {
			$error_message .= '<p>' . __( 'UPS is enabled, but Your Name has not been set.', 'ups-woocommerce-shipping' ) . '</p>';
		}
		// WF: Print Label - End
		
		// Check for UPS User ID
		if ( ! $this->user_id && $this->enabled == 'yes' ) {
			$error_message .= '<p>' . __( 'UPS is enabled, but the UPS User ID has not been set.', 'ups-woocommerce-shipping' ) . '</p>';
		}

		// Check for UPS Password
		if ( ! $this->password && $this->enabled == 'yes' ) {
			$error_message .= '<p>' . __( 'UPS is enabled, but the UPS Password has not been set.', 'ups-woocommerce-shipping' ) . '</p>';
		}

		// Check for UPS Access Key
		if ( ! $this->access_key && $this->enabled == 'yes' ) {
			$error_message .= '<p>' . __( 'UPS is enabled, but the UPS Access Key has not been set.', 'ups-woocommerce-shipping' ) . '</p>';
		}

		// Check for UPS Shipper Number
		if ( ! $this->shipper_number && $this->enabled == 'yes' ) {
			$error_message .= '<p>' . __( 'UPS is enabled, but the UPS Shipper Number has not been set.', 'ups-woocommerce-shipping' ) . '</p>';
		}

		// Check for Origin Postcode
		if ( ! $this->origin_postcode && $this->enabled == 'yes' ) {
			$error_message .= '<p>' . __( 'UPS is enabled, but the origin postcode has not been set.', 'ups-woocommerce-shipping' ) . '</p>';
		}

		// Check for Origin country
		if ( ! $this->origin_country_state && $this->enabled == 'yes' ) {
			$error_message .= '<p>' . __( 'UPS is enabled, but the origin country/state has not been set.', 'ups-woocommerce-shipping' ) . '</p>';
		}

		// If user has selected to pack into boxes,
		// Check if at least one UPS packaging is chosen, or a custom box is defined
		if ( ( $this->packing_method == 'box_packing' ) && ( $this->enabled == 'yes' ) ) {
			if ( empty( $this->ups_packaging )  && empty( $this->boxes ) ){
				$error_message .= '<p>' . __( 'UPS is enabled, and Parcel Packing Method is set to \'Pack into boxes\', but no UPS Packaging is selected and there are no custom boxes defined. Items will be packed individually.', 'ups-woocommerce-shipping' ) . '</p>';
			}
		}

		// Check for at least one service enabled
		$ctr=0;
		if ( isset($this->custom_services ) && is_array( $this->custom_services ) ){
			foreach ( $this->custom_services as $key => $values ){
				if ( $values['enabled'] == 1)
					$ctr++;
			}
		}
		if ( ( $ctr == 0 ) && $this->enabled == 'yes' ) {
			$error_message .= '<p>' . __( 'UPS is enabled, but there are no services enabled.', 'ups-woocommerce-shipping' ) . '</p>';
		}


		if ( ! $error_message == '' ) {
			echo '<div class="error">';
			echo $error_message;
			echo '</div>';
		}
	}

	/**
	 * admin_options function.
	 *
	 * @access public
	 * @return void
	 */
	public function admin_options() {
		// Check users environment supports this method
		$this->environment_check();

		// Show settings
		parent::admin_options();
	}

	/**
	 *
	 * generate_single_select_country_html function
	 *
	 * @access public
	 * @return void
	 */
	function generate_single_select_country_html() {
		global $woocommerce;

		ob_start();
		?>
		<tr valign="top">
			<th scope="row" class="titledesc">
				<label for="origin_country"><?php _e( 'Origin Country', 'ups-woocommerce-shipping' ); ?></label>
			</th>
			<td class="forminp"><select name="woocommerce_ups_origin_country_state" id="woocommerce_ups_origin_country_state" style="width: 250px;" data-placeholder="<?php _e('Choose a country&hellip;', 'woocommerce'); ?>" title="Country" class="chosen_select">
				<?php echo $woocommerce->countries->country_dropdown_options( $this->origin_country, $this->origin_state ? $this->origin_state : '*' ); ?>
			</select>
	   		</td>
	   	</tr>
		<?php
		return ob_get_clean();
	}

	/**
	 * generate_services_html function.
	 *
	 * @access public
	 * @return void
	 */
	function generate_services_html() {
		ob_start();
		?>
		<style>
		/*Style for tooltip*/
		.xa-tooltip { position: relative;  }
		.xa-tooltip .xa-tooltiptext { visibility: hidden; width: 150px; background-color: black; color: #fff; text-align: center; border-radius: 6px; 
			padding: 5px 0;
			/* Position the tooltip */
			position: absolute; z-index: 1;}
		.xa-tooltip:hover .xa-tooltiptext {visibility: visible;}
		/*End of tooltip styling*/
		</style>
		<tr valign="top" id="service_options">
			<td class="forminp" colspan="2" style="padding-left:0px">
				<table class="ups_services widefat">
					<thead>
						<th class="sort">&nbsp;</th>
						<th><?php _e( 'Service Code', 'ups-woocommerce-shipping' ); ?></th>
						<th><?php _e( 'Name', 'ups-woocommerce-shipping' ); ?></th>
						<th><?php _e( 'Enabled', 'ups-woocommerce-shipping' ); ?></th>
						<th><?php echo sprintf( __( 'Price Adjustment (%s)', 'ups-woocommerce-shipping' ), get_woocommerce_currency_symbol() ); ?></th>
						<th><?php _e( 'Price Adjustment (%)', 'ups-woocommerce-shipping' ); ?></th>
					</thead>
					<tfoot>
<?php
					if( !$this->origin_country == 'PL' && !in_array( $this->origin_country, $this->eu_array ) ) {
?>
						<tr>
							<th colspan="6">
								<small class="description"><?php _e( '<strong>Domestic Rates</strong>: Next Day Air, 2nd Day Air, Ground, 3 Day Select, Next Day Air Saver, Next Day Air Early AM, 2nd Day Air AM', 'ups-woocommerce-shipping' ); ?></small><br/>
								<small class="description"><?php _e( '<strong>International Rates</strong>: Worldwide Express, Worldwide Expedited, Standard, Worldwide Express Plus, UPS Saver', 'ups-woocommerce-shipping' ); ?></small>
							</th>
						</tr>
<?php 
	}
?>
					</tfoot>
					<tbody>
						<?php
							$sort = 0;
							$this->ordered_services = array();

							if ( $this->origin_country == 'PL' ) {
								$use_services = $this->polandservices;
							} elseif ( in_array( $this->origin_country, $this->eu_array ) ) {
								$use_services = $this->euservices;
							} else {
								$use_services = $this->services;
							}
							if($this->enable_freight==true) {
								$use_services= (array)$use_services + (array)$this->freigth_services;	    //array + NULL will throw fatal error in php version 5.6.21
							}
							foreach ( $use_services as $code => $name ) {

								if ( isset( $this->custom_services[ $code ]['order'] ) ) {
									$sort = $this->custom_services[ $code ]['order'];
								}

								while ( isset( $this->ordered_services[ $sort ] ) )
									$sort++;

								$this->ordered_services[ $sort ] = array( $code, $name );

								$sort++;
							}

							ksort( $this->ordered_services );

							foreach ( $this->ordered_services as $value ) {
								$code = $value[0];
								$name = $value[1];
								?>
								<tr>
									<td class="sort"><input type="hidden" class="order" name="ups_service[<?php echo $code; ?>][order]" value="<?php echo isset( $this->custom_services[ $code ]['order'] ) ? $this->custom_services[ $code ]['order'] : ''; ?>" /></td>
									<td><strong><?php echo $code; ?></strong><?php if( $code == 96 ) echo '<span class="xa-tooltip"><img src="'.site_url("/wp-content/plugins/woocommerce/assets/images/help.png").'" height="16" width="16" /><span class="xa-tooltiptext">In case of Weight Based Packaging, Package Dimensions will be 47x47x47 inches or 119x119x119 cm.</span></span>' ?></td>
									<td><input type="text" name="ups_service[<?php echo $code; ?>][name]" placeholder="<?php echo $name; ?> (<?php echo $this->title; ?>)" value="<?php echo isset( $this->custom_services[ $code ]['name'] ) ? $this->custom_services[ $code ]['name'] : ''; ?>" size="50" /></td>
									<td><input type="checkbox" name="ups_service[<?php echo $code; ?>][enabled]" <?php checked( ( ! isset( $this->custom_services[ $code ]['enabled'] ) || ! empty( $this->custom_services[ $code ]['enabled'] ) ), true ); ?> /></td>
									<td><input type="text" name="ups_service[<?php echo $code; ?>][adjustment]" placeholder="N/A" value="<?php echo isset( $this->custom_services[ $code ]['adjustment'] ) ? $this->custom_services[ $code ]['adjustment'] : ''; ?>" size="4" /></td>
									<td><input type="text" name="ups_service[<?php echo $code; ?>][adjustment_percent]" placeholder="N/A" value="<?php echo isset( $this->custom_services[ $code ]['adjustment_percent'] ) ? $this->custom_services[ $code ]['adjustment_percent'] : ''; ?>" size="4" /></td>
								</tr>
								<?php
							}
						?>
					</tbody>
				</table>
			</td>
		</tr>
		<?php
		return ob_get_clean();
	}


	/**
	 * generate_box_packing_html function.
	 *
	 * @access public
	 * @return void
	 */
	public function generate_box_packing_html() {
		ob_start();
		?>
		<tr valign="top" id="packing_options">
			<td class="forminp" colspan="2" style="padding-left:0px">
				<style type="text/css">
					.ups_boxes td, .ups_services td {
						vertical-align: middle;
						padding: 4px 7px;
					}
					.ups_boxes th, .ups_services th {
						padding: 9px 7px;
					}
					.ups_boxes td input {
						margin-right: 4px;
					}
					.ups_boxes .check-column {
						vertical-align: middle;
						text-align: left;
						padding: 0 7px;
					}
					.ups_services th.sort {
						width: 16px;
						padding: 0 16px;
					}
					.ups_services td.sort {
						cursor: move;
						width: 16px;
						padding: 0 16px;
						cursor: move;
						background: url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAgAAAAICAYAAADED76LAAAAHUlEQVQYV2O8f//+fwY8gJGgAny6QXKETRgEVgAAXxAVsa5Xr3QAAAAASUVORK5CYII=) no-repeat center;					}
				</style>
				<strong><?php _e( 'Custom Box Dimensions', 'ups-woocommerce-shipping' ); ?></strong><br/>
				<table class="ups_boxes widefat">
					<thead>
						<tr>
							<th class="check-column"><input type="checkbox" /></th>
							<th><?php _e( 'Outer Length', 'ups-woocommerce-shipping' ); ?></th>
							<th><?php _e( 'Outer Width', 'ups-woocommerce-shipping' ); ?></th>
							<th><?php _e( 'Outer Height', 'ups-woocommerce-shipping' ); ?></th>
							<th><?php _e( 'Inner Length', 'ups-woocommerce-shipping' ); ?></th>
							<th><?php _e( 'Inner Width', 'ups-woocommerce-shipping' ); ?></th>
							<th><?php _e( 'Inner Height', 'ups-woocommerce-shipping' ); ?></th>
							<th><?php _e( 'Box Weight', 'ups-woocommerce-shipping' ); ?></th>
							<th><?php _e( 'Max Weight', 'ups-woocommerce-shipping' ); ?></th>
						</tr>
					</thead>
					<tfoot>
						<tr>
							<th colspan="3">
								<a href="#" class="button plus insert"><?php _e( 'Add Box', 'ups-woocommerce-shipping' ); ?></a>
								<a href="#" class="button minus remove"><?php _e( 'Remove selected box(es)', 'ups-woocommerce-shipping' ); ?></a>
							</th>
							<th colspan="6">
								<small class="description"><?php _e( 'Items will be packed into these boxes depending based on item dimensions and volume. Outer dimensions will be passed to UPS, whereas inner dimensions will be used for packing. Items not fitting into boxes will be packed individually.', 'ups-woocommerce-shipping' ); ?></small>
							</th>
						</tr>
					</tfoot>
					<tbody id="rates">
						<?php
							if ( $this->boxes && ! empty( $this->boxes ) ) {
								foreach ( $this->boxes as $key => $box ) {
									?>
									<tr>
										<td class="check-column"><input type="checkbox" /></td>
										<td><input type="text" size="5" name="boxes_outer_length[<?php echo $key; ?>]" value="<?php echo esc_attr( $box['outer_length'] ); ?>" /><?php echo $this->dim_unit; ?></td>
										<td><input type="text" size="5" name="boxes_outer_width[<?php echo $key; ?>]" value="<?php echo esc_attr( $box['outer_width'] ); ?>" /><?php echo $this->dim_unit; ?></td>
										<td><input type="text" size="5" name="boxes_outer_height[<?php echo $key; ?>]" value="<?php echo esc_attr( $box['outer_height'] ); ?>" /><?php echo $this->dim_unit; ?></td>
										<td><input type="text" size="5" name="boxes_inner_length[<?php echo $key; ?>]" value="<?php echo esc_attr( $box['inner_length'] ); ?>" /><?php echo $this->dim_unit; ?></td>
										<td><input type="text" size="5" name="boxes_inner_width[<?php echo $key; ?>]" value="<?php echo esc_attr( $box['inner_width'] ); ?>" /><?php echo $this->dim_unit; ?></td>
										<td><input type="text" size="5" name="boxes_inner_height[<?php echo $key; ?>]" value="<?php echo esc_attr( $box['inner_height'] ); ?>" /><?php echo $this->dim_unit; ?></td>
										<td><input type="text" size="5" name="boxes_box_weight[<?php echo $key; ?>]" value="<?php echo esc_attr( $box['box_weight'] ); ?>" /><?php echo $this->weight_unit; ?></td>
										<td><input type="text" size="5" name="boxes_max_weight[<?php echo $key; ?>]" value="<?php echo esc_attr( $box['max_weight'] ); ?>" /><?php echo $this->weight_unit; ?></td>
									</tr>
									<?php
								}
							}
						?>
					</tbody>
				</table>
				<script type="text/javascript">

					jQuery(window).load(function(){

						jQuery('.ups_boxes .insert').click( function() {
							var $tbody = jQuery('.ups_boxes').find('tbody');
							var size = $tbody.find('tr').size();
							var code = '<tr class="new">\
									<td class="check-column"><input type="checkbox" /></td>\
									<td><input type="text" size="5" name="boxes_outer_length[' + size + ']" /><?php echo $this->dim_unit; ?></td>\
									<td><input type="text" size="5" name="boxes_outer_width[' + size + ']" /><?php echo $this->dim_unit; ?></td>\
									<td><input type="text" size="5" name="boxes_outer_height[' + size + ']" /><?php echo $this->dim_unit; ?></td>\
									<td><input type="text" size="5" name="boxes_inner_length[' + size + ']" /><?php echo $this->dim_unit; ?></td>\
									<td><input type="text" size="5" name="boxes_inner_width[' + size + ']" /><?php echo $this->dim_unit; ?></td>\
									<td><input type="text" size="5" name="boxes_inner_height[' + size + ']" /><?php echo $this->dim_unit; ?></td>\
									<td><input type="text" size="5" name="boxes_box_weight[' + size + ']" /><?php echo $this->weight_unit; ?></td>\
									<td><input type="text" size="5" name="boxes_max_weight[' + size + ']" /><?php echo $this->weight_unit; ?></td>\
								</tr>';

							$tbody.append( code );

							return false;
						} );

						jQuery('.ups_boxes .remove').click(function() {
							var $tbody = jQuery('.ups_boxes').find('tbody');

							$tbody.find('.check-column input:checked').each(function() {
								jQuery(this).closest('tr').hide().find('input').val('');
							});

							return false;
						});

						// Ordering
						jQuery('.ups_services tbody').sortable({
							items:'tr',
							cursor:'move',
							axis:'y',
							handle: '.sort',
							scrollSensitivity:40,
							forcePlaceholderSize: true,
							helper: 'clone',
							opacity: 0.65,
							placeholder: 'wc-metabox-sortable-placeholder',
							start:function(event,ui){
								ui.item.css('baclbsround-color','#f6f6f6');
							},
							stop:function(event,ui){
								ui.item.removeAttr('style');
								ups_services_row_indexes();
							}
						});

						function ups_services_row_indexes() {
							jQuery('.ups_services tbody tr').each(function(index, el){
								jQuery('input.order', el).val( parseInt( jQuery(el).index('.ups_services tr') ) );
							});
						};

					});

				</script>
			</td>
		</tr>
		<?php
		return ob_get_clean();
	}

	/**
	 * validate_single_select_country_field function.
	 *
	 * @access public
	 * @param mixed $key
	 * @return void
	 */
	public function validate_single_select_country_field( $key ) {

		if ( isset( $_POST['woocommerce_ups_origin_country_state'] ) )
			return $_POST['woocommerce_ups_origin_country_state'];
		return '';
	}
	/**
	 * validate_box_packing_field function.
	 *
	 * @access public
	 * @param mixed $key
	 * @return void
	 */
	public function validate_box_packing_field( $key ) {

		$boxes = array();

		if ( isset( $_POST['boxes_outer_length'] ) ) {
			$boxes_outer_length = $_POST['boxes_outer_length'];
			$boxes_outer_width  = $_POST['boxes_outer_width'];
			$boxes_outer_height = $_POST['boxes_outer_height'];
			$boxes_inner_length = $_POST['boxes_inner_length'];
			$boxes_inner_width  = $_POST['boxes_inner_width'];
			$boxes_inner_height = $_POST['boxes_inner_height'];
			$boxes_box_weight   = $_POST['boxes_box_weight'];
			$boxes_max_weight   = $_POST['boxes_max_weight'];


			for ( $i = 0; $i < sizeof( $boxes_outer_length ); $i ++ ) {

				if ( $boxes_outer_length[ $i ] && $boxes_outer_width[ $i ] && $boxes_outer_height[ $i ] && $boxes_inner_length[ $i ] && $boxes_inner_width[ $i ] && $boxes_inner_height[ $i ] ) {

					$boxes[] = array(
						'outer_length' => floatval( $boxes_outer_length[ $i ] ),
						'outer_width'  => floatval( $boxes_outer_width[ $i ] ),
						'outer_height' => floatval( $boxes_outer_height[ $i ] ),
						'inner_length' => floatval( $boxes_inner_length[ $i ] ),
						'inner_width'  => floatval( $boxes_inner_width[ $i ] ),
						'inner_height' => floatval( $boxes_inner_height[ $i ] ),
						'box_weight'   => floatval( $boxes_box_weight[ $i ] ),
						'max_weight'   => floatval( $boxes_max_weight[ $i ] ),
					);

				}

			}

		}

		return $boxes;
	}

	/**
	 * validate_services_field function.
	 *
	 * @access public
	 * @param mixed $key
	 * @return void
	 */
	public function validate_services_field( $key ) {
		$services		 = array();
		$posted_services  = $_POST['ups_service'];

		foreach ( $posted_services as $code => $settings ) {

			$services[ $code ] = array(
				'name'			   => wc_clean( $settings['name'] ),
				'order'			  => wc_clean( $settings['order'] ),
				'enabled'			=> isset( $settings['enabled'] ) ? true : false,
				'adjustment'		 => wc_clean( $settings['adjustment'] ),
				'adjustment_percent' => str_replace( '%', '', wc_clean( $settings['adjustment_percent'] ) )
			);

		}

		return $services;
	}

	/**
	 * clear_transients function.
	 *
	 * @access public
	 * @return void
	 */
	public function clear_transients() {
		global $wpdb;

		$wpdb->query( "DELETE FROM `$wpdb->options` WHERE `option_name` LIKE ('_transient_ups_quote_%') OR `option_name` LIKE ('_transient_timeout_ups_quote_%')" );
	}
	
	public function generate_activate_box_html() {
		ob_start();
		$plugin_name = 'ups';
		include( 'wf_api_manager/html/html-wf-activation-window.php' );
		return ob_get_clean();
	}

	/**
	 * init_form_fields function.
	 *
	 * @access public
	 * @return void
	 */
	public function init_form_fields() {
		global $woocommerce;
		
		if ( WF_UPS_ADV_DEBUG_MODE == "on" ) { // Test mode is only for development purpose.
			$api_mode_options = array(
				'Test'		   => __( 'Test', 'ups-woocommerce-shipping' ),
			);
		}
		else {
			$api_mode_options = array(
				'Live'		   => __( 'Live', 'ups-woocommerce-shipping' ),
				'Test'		   => __( 'Test', 'ups-woocommerce-shipping' ),
			);
		}

		
		$pickup_start_time_options	=	array();
		foreach(range(0,23,0.5) as $pickup_start_time){
			$pickup_start_time_options[(string)$pickup_start_time]	=	date("H:i",strtotime(date('Y-m-d'))+3600*$pickup_start_time);
		}

		$pickup_close_time_options	=	array();
		foreach(range(0.5,23.5,0.5) as $pickup_close_time){
			$pickup_close_time_options[(string)$pickup_close_time]	=	date("H:i",strtotime(date('Y-m-d'))+3600*$pickup_close_time);
		}

		$ship_from_address_options	=	apply_filters( 'wf_filter_label_ship_from_address_options', array(
					'origin_address'   => __( 'Origin Address', 'ups-woocommerce-shipping' ),
					'billing_address'  => __( 'Shipping Address', 'ups-woocommerce-shipping' ),
				)
		);

		$this->form_fields  = array(
		   'licence'  => array(
				'type'			=> 'activate_box'
			),
			'enabled'				=> array(
				'title'			  => __( 'Realtime Rates', 'ups-woocommerce-shipping' ),
				'type'			   => 'checkbox',
				'label'			  => __( 'Enable', 'ups-woocommerce-shipping' ),
				'default'			=> 'no',
				'description'		=> __( 'Enable realtime rates on Cart/Checkout page.', 'ups-woocommerce-shipping' ),
				'desc_tip'		   => true
			),
			'title'				  => array(
				'title'			  => __( 'UPS Method Title', 'ups-woocommerce-shipping' ),
				'type'			   => 'text',
				'description'		=> __( 'This controls the title which the user sees during checkout.', 'ups-woocommerce-shipping' ),
				'default'			=> __( 'UPS', 'ups-woocommerce-shipping' ),
				'desc_tip'		   => true
			),
			'availability'		   => array(
				'title'			  => __( 'Method Availability', 'ups-woocommerce-shipping' ),
				'type'			   => 'select',
				'default'			=> 'all',
				'class'			  => 'availability wc-enhanced-select',
				'options'			=> array(
					'all'			=> __( 'All Countries', 'ups-woocommerce-shipping' ),
					'specific'	   => __( 'Specific Countries', 'ups-woocommerce-shipping' ),
				),
			),
			'countries'			  => array(
				'title'			  => __( 'Specific Countries', 'ups-woocommerce-shipping' ),
				'type'			   => 'multiselect',
				'class'			  => 'chosen_select',
				'css'				=> 'width: 450px;',
				'default'			=> '',
				'options'			=> $woocommerce->countries->get_allowed_countries(),
			),
			'debug'				  => array(
				'title'			  => __( 'Debug Mode', 'ups-woocommerce-shipping' ),
				'label'			  => __( 'Enable', 'ups-woocommerce-shipping' ),
				'type'			   => 'checkbox',
				'default'			=> 'no',
				'description'		=> __( 'Enable debug mode to show debugging information on your cart/checkout.', 'ups-woocommerce-shipping' ),
				'desc_tip'		   => true
			),
			'api'					=> array(
				'title'			  => __( 'Generic API Settings', 'ups-woocommerce-shipping' ),
				'type'			   => 'title',
				'description'		=> __( 'Obtain UPS account credentials by registering on UPS website.', 'ups-woocommerce-shipping' )
			),
			'api_mode' 				 => array(
				'title'			  => __( 'API Mode', 'ups-woocommerce-shipping' ),
				'type'			   => 'select',
				'default'			=> 'yes',
				'class'				 => 'wc-enhanced-select',
				'options'			=> $api_mode_options,
				'description'		=> __( 'Set as Test to switch to UPS api test servers. Transaction will be treated as sample transactions by UPS.', 'ups-woocommerce-shipping' ),
				'desc_tip'		   => true
			),
			'ups_user_name'	   => array(
				'title'		   => __( 'Company Name', 'ups-woocommerce-shipping' ),
				'type'			=> 'text',
				'description'	 => __( 'Enter your company name', 'ups-woocommerce-shipping' ),
				'default'		 => '',
				'desc_tip'		=> true
			),
			'ups_display_name'	=> array(
				'title'		   => __( 'Attention Name', 'ups-woocommerce-shipping' ),
				'type'			=> 'text',
				'description'	 => __( 'Your business/attention name.', 'ups-woocommerce-shipping' ),
				'default'		 => '',
				'desc_tip'		=> true
			),
			'user_id'			 => array(
				'title'		   => __( 'UPS User ID', 'ups-woocommerce-shipping' ),
				'type'			=> 'text',
				'description'	 => __( 'Obtained from UPS after getting an account.', 'ups-woocommerce-shipping' ),
				'default'		 => '',
				'desc_tip'		=> true
			),
			'password'			=> array(
				'title'		   => __( 'UPS Password', 'ups-woocommerce-shipping' ),
				'type'			=> 'password',
				'description'	 => __( 'Obtained from UPS after getting an account.', 'ups-woocommerce-shipping' ),
				'default'		 => '',
				'desc_tip'		=> true
			),
			'access_key'		  => array(
				'title'		   => __( 'UPS Access Key', 'ups-woocommerce-shipping' ),
				'type'			=> 'password',
				'description'	 => __( 'Obtained from UPS after getting an account.', 'ups-woocommerce-shipping' ),
				'default'		 => '',
				'desc_tip'		=> true
			),
			'shipper_number'	  => array(
				'title'		   => __( 'UPS Account Number', 'ups-woocommerce-shipping' ),
				'type'			=> 'text',
				'description'	 => __( 'Obtained from UPS after getting an account.', 'ups-woocommerce-shipping' ),
				'default'		 => '',
				'desc_tip'		=> true
			),
			'units'			   => array(
				'title'		   => __( 'Weight/Dimension Units', 'ups-woocommerce-shipping' ),
				'type'			=> 'select',
				'description'	 => __( 'Switch this to metric units, if you see "This measurement system is not valid for the selected country" errors.', 'ups-woocommerce-shipping' ),
				'default'		 => 'imperial',
				'class'				 => 'wc-enhanced-select',
				'options'		 => array(
					'imperial'	=> __( 'LB / IN', 'ups-woocommerce-shipping' ),
					'metric'	  => __( 'KG / CM', 'ups-woocommerce-shipping' ),
				),
				'desc_tip'		=> true
			),
			'negotiated'		  => array(
				'title'		   => __( 'Negotiated Rates', 'ups-woocommerce-shipping' ),
				'label'		   => __( 'Enable', 'ups-woocommerce-shipping' ),
				'type'			=> 'checkbox',
				'default'		 => 'no',
				'description'	 => __( 'Enable this if this shipping account has negotiated rates available.', 'ups-woocommerce-shipping' ),
				'desc_tip'		=> true
			),
			'insuredvalue'		=> array(
				'title'		   => __( 'Insurance Option', 'ups-woocommerce-shipping' ),
				'label'		   => __( 'Enable', 'ups-woocommerce-shipping' ),
				'type'			=> 'checkbox',
				'default'		 => 'no',
				'description'	 => __( 'Request Insurance to be included.', 'ups-woocommerce-shipping' ),
				'desc_tip'		=> true
			),
			'enable_freight'		=> array(
				'title'		   => __( 'Freight Services', 'ups-woocommerce-shipping' ),
				'label'		   => __( 'Enable', 'ups-woocommerce-shipping' ),
				'type'			=> 'checkbox',
				'default'		 => 'no',
				'description'	 => __( 'Enable Freight Services	', 'ups-woocommerce-shipping' ),
				'desc_tip'		=> true
			),			
			'pickup_destination'  => array(
				'title'		   => __( 'Pickup and Destination', 'ups-woocommerce-shipping' ),
				'type'			=> 'title',
				'description'	 => '',
			),			
			'residential'		 => array(
				'title'		   => __( 'Residential', 'ups-woocommerce-shipping' ),
				'label'		   => __( 'Ship to address is Residential.', 'ups-woocommerce-shipping' ),
				'type'			=> 'checkbox',
				'default'		 => 'no',
				'description'	 => __( 'This will indicate to UPS that the receiver is always a residential address.', 'ups-woocommerce-shipping' ),
				'desc_tip'		=> true
			),
			'label-settings'					=> array(
				'title'			  => __( 'Label Printing API Settings', 'ups-woocommerce-shipping' ),
				'type'			   => 'title',
			),
			'disble_ups_print_label' => array(
				'title'			  => __( 'Label Printing', 'ups-woocommerce-shipping' ),
				'type'			   => 'select',
				'default'			=> 'no',
				'class'				 => 'wc-enhanced-select',
				'options'			=> array(
					'no'		 => __( 'Enable', 'ups-woocommerce-shipping' ),
					'yes'		=> __( 'Disable', 'ups-woocommerce-shipping' ),
				),
			),
			'print_label_type'	   => array(
				'title'			  => __( 'Print Label Type', 'ups-woocommerce-shipping' ),
				'type'			   => 'select',
				'default'			=> 'gif',
				'class'				 => 'wc-enhanced-select',
				'options'			=> array(
					'gif'		=> __( 'GIF', 'ups-woocommerce-shipping' ),
					'png'		=> __( 'PNG', 'ups-woocommerce-shipping' ),
					'zpl'			 => __( 'ZPL', 'ups-woocommerce-shipping' ),
					'epl'			 => __( 'EPL', 'ups-woocommerce-shipping' ),
				),
				'description'		=> __( 'Selecting PNG will enable ~4x6 dimension label. Note that an external api labelary is used.', 'ups-woocommerce-shipping' ),
				'desc_tip'		   => true
			),
			'show_label_in_browser'  => array(
				'title'			  => __( 'Display Label in Browser', 'ups-woocommerce-shipping' ),
				'label'			  => __( 'Enable' ),
				'type'			   => 'checkbox',
				'default'			=> 'no',
				'description'		=> __( 'Enabling this will print the label in the browser instead of downloading it. Useful if your downloaded file is getting currupted because of PHP BOM (ByteOrderMark). This option is only applicable for supported formats.', 'ups-woocommerce-shipping' ),
				'desc_tip' 			 => true
			),
			'disble_shipment_tracking'   => array(
				'title'				  => __( 'Shipment Tracking', 'ups-woocommerce-shipping' ),
				'type'				   => 'select',
				'default'				=> 'yes',
				'class'				 => 'wc-enhanced-select',
				'options'				=> array(
					'TrueForCustomer'	=> __( 'Disable for Customer', 'ups-woocommerce-shipping' ),
					'False'			  => __( 'Enable', 'ups-woocommerce-shipping' ),
					'True'			   => __( 'Disable', 'ups-woocommerce-shipping' ),
				),
				'description'			=> __( 'Selecting Disable for customer will hide shipment tracking info from customer side order details page.', 'ups-woocommerce-shipping' ),
				'desc_tip'			   => true
			),
			'ship_from_address'   => array(
				'title'		   => __( 'Ship From Address Preference', 'ups-woocommerce-shipping' ),
				'type'			=> 'select',
				'default'		 => 'origin_address',
				'class'				 => 'wc-enhanced-select',
				'options'		 => $ship_from_address_options,
				'description'	 => __( 'Change the preference of Ship From Address printed on the label. You can make  use of Billing Address from Order admin page, if you ship from a different location other than shipment origin address given below.', 'ups-woocommerce-shipping' ),
				'desc_tip'		=> true
			),
			'origin_addressline'  => array(
				'title'		   => __( 'Origin Address', 'ups-woocommerce-shipping' ),
				'type'			=> 'text',
				'description'	 => __( 'Shipping Origin address (Ship From address).', 'ups-woocommerce-shipping' ),
				'default'		 => '',
				'desc_tip'		=> true
			),
			'origin_city'	  	  => array(
				'title'		   => __( 'Origin City', 'ups-woocommerce-shipping' ),
				'type'			=> 'text',
				'description'	 => __( 'Origin City (Ship From City)', 'ups-woocommerce-shipping' ),
				'default'		 => '',
				'desc_tip'		=> true
			),
			'origin_country_state'	=> array(
				'type'				=> 'single_select_country',
			),
			'origin_custom_state'		=> array(
				'title'		   => __( 'Origin State Code', 'ups-woocommerce-shipping' ),
				'type'			=> 'text',
				'description'	 => __( 'Specify shipper state province code if state not listed with Origin Country.', 'ups-woocommerce-shipping' ),
				'default'		 => '',
				'desc_tip'		=> true
			),
			'origin_postcode'	 => array(
				'title'		   => __( 'Origin Postcode', 'ups-woocommerce-shipping' ),
				'type'			=> 'text',
				'description'	 => __( 'Ship From Zip/postcode.', 'ups-woocommerce-shipping' ),
				'default'		 => '',
				'desc_tip'		=> true
			),
			'phone_number'		=> array(
				'title'		   => __( 'Your Phone Number', 'ups-woocommerce-shipping' ),
				'type'			=> 'text',
				'description'	 => __( 'Your contact phone number.', 'ups-woocommerce-shipping' ),
				'default'		 => '',
				'desc_tip'		=> true
			),
			'email'		=> array(
				'title'		   => __( 'Your email', 'ups-woocommerce-shipping' ),
				'type'			=> 'text',
				'description'	 => __( 'Your email.', 'ups-woocommerce-shipping' ),
				'default'		 => '',
				'desc_tip'		=> true
			),
			'services_packaging'  => array(
				'title'		   => __( 'Services and Packaging', 'ups-woocommerce-shipping' ),
				'type'			=> 'title',
				'description'	 => '',
			),
			'services'			=> array(
				'type'			=> 'services'
			),
			'offer_rates'		 => array(
				'title'		   => __( 'Offer Rates', 'ups-woocommerce-shipping' ),
				'type'			=> 'select',
				'class'			  => 'wc-enhanced-select',
				'description'	 => '',
				'default'		 => 'all',
				'options'		 => array(
					'all'		 => __( 'Offer the customer all rates quoted by UPS', 'ups-woocommerce-shipping' ),
					'cheapest'	=> __( 'Offer the customer the cheapest rate only', 'ups-woocommerce-shipping' ),
				),
			),
			'fallback'			=> array(
				'title'		   => __( 'Fallback', 'ups-woocommerce-shipping' ),
				'type'			=> 'text',
				'description'	 => __( 'If UPS returns no matching rates, offer this amount for shipping so that the user can still checkout. Leave blank to disable.', 'ups-woocommerce-shipping' ),
				'default'		 => '',
				'desc_tip'		=> true
			),
			'currency_type'	=> array(
				'title'	   	=> __( 'Currency', 'ups-woocommerce-shipping' ),
				'label'	  	=> __( 'Currency', 'ups-woocommerce-shipping' ),
				'type'			=> 'select',
				'class'			=> 'wc-enhanced-select',
				'options'	 	=> get_woocommerce_currencies(),
				'default'	 	=> get_woocommerce_currency(),
				//'desc_tip'	=> true,
				'description' 	=> __( 'This currency will be used to communicate with UPS.', 'ups-woocommerce-shipping' ),
			),
			'conversion_rate'	 => array(
				'title' 		  => __('Conversion Rate.', 'ups-woocommerce-shipping'),
				'type' 			  => 'text',
				'default'		 => 1,
				'description' 	  => __('Enter the conversion amount in case you have a different currency set up comparing to the currency of origin location. This amount will be multiplied with the shipping rates. Leave it empty if no conversion required.', 'ups-woocommerce-shipping'),
				'desc_tip' 		  => true
			),
			'packing_method'	  => array(
				'title'		   => __( 'Parcel Packing', 'ups-woocommerce-shipping' ),
				'type'			=> 'select',
				'default'		 => 'weight_based',
				'class'		   => 'packing_method wc-enhanced-select',
				'options'		 => array(
					'per_item'	=> __( 'Default: Pack items individually', 'ups-woocommerce-shipping' ),
					'box_packing' => __( 'Recommended: Pack into boxes with weights and dimensions', 'ups-woocommerce-shipping' ),
					'weight_based'=> __( 'Weight based: Calculate shipping on the basis of order total weight', 'ups-woocommerce-shipping' ),
				),
			),
			'packing_algorithm'  			=> array(
				'title'		   		=> __( 'Packing Algorithm', 'wf-usps-stamps-woocommerce' ),
				'type'					=> 'select',
				'default'		 		=> 'volume_based',
				'class'		   		=> 'weight_based_option wc-enhanced-select',
				'options'		 		=> array(
					'volume_based'	   	=> __( 'Default: Volume Based Packing', 'wf-usps-stamps-woocommerce' ),
					'stack_first'		=> __( 'Stack First Packing', 'wf-usps-stamps-woocommerce' ),
					'new_algorithm'		=> __( 'New Algorithm(Based on Volume Used * Item Count)', 'wf-usps-stamps-woocommerce' ),	
				),
			),
			'box_max_weight'		   => array(
				'title'		   => __( 'Max Package Weight', 'ups-woocommerce-shipping' ),
				'type'			=> 'text',
				'default'		 => '10',
				'class'		   => 'weight_based_option',
				'desc_tip'	=> true,
				'description'	 => __( 'Maximum weight allowed for single box.', 'ups-woocommerce-shipping' ),
			),
			'weight_packing_process'   => array(
				'title'		   => __( 'Packing Process', 'ups-woocommerce-shipping' ),
				'type'			=> 'select',
				'default'		 => '',
				'class'		   => 'weight_based_option wc-enhanced-select',
				'options'		 => array(
					'pack_descending'	   => __( 'Pack heavier items first', 'ups-woocommerce-shipping' ),
					'pack_ascending'		=> __( 'Pack lighter items first.', 'ups-woocommerce-shipping' ),
					'pack_simple'			=> __( 'Pack purely divided by weight.', 'ups-woocommerce-shipping' ),
				),
				'desc_tip'	=> true,
				'description'	 => __( 'Select your packing order.', 'ups-woocommerce-shipping' ),
			),
			'ups_packaging'	   => array(
				'title'		   => __( 'UPS Packaging', 'ups-woocommerce-shipping' ),
				'type'			=> 'multiselect',
				'description'	  => __( 'UPS standard packaging options', 'ups-woocommerce-shipping' ),
				'default'		 => array(),
				'css'			  => 'width: 450px;',
				'class'		   => 'ups_packaging chosen_select',
				'options'		 => $this->packaging_select,
				'desc_tip'		=> true
			),

			'boxes'  => array(
				'type'			=> 'box_packing'
			),
			'advanced_settings'   => array(
				'title'		   => __( 'Advanced Settings', 'ups-woocommerce-shipping' ),
				'type'			=> 'title',
				'class'			  => 'wf_settings_heading_tab'
			),
			'xa_show_all' => array(
				'title'		   => __( 'Show All Services in Order Page', 'ups-woocommerce-shipping' ),
				'label'		   => __( 'Enable', 'ups-woocommerce-shipping' ),
				'type'			=> 'checkbox',
				'default'		 => 'no',
				'description'	 => __( 'Check this option to show all services in create label drop down(UPS).', 'ups-woocommerce-shipping' ),
				'desc_tip'		   => true,
			),
			'pickup'  => array(
				'title'		   => __( 'Rates Based On Pickup Type', 'ups-woocommerce-shipping' ),
				'type'			=> 'select',
				'css'			  => 'width: 250px;',
				'class'			  => 'chosen_select wc-enhanced-select',
				'default'		 => '01',
				'options'		 => $this->pickup_code,
			),
			'customer_classification'  => array(
				'title'		   => __( 'Customer Classification', 'ups-woocommerce-shipping' ),
				'type'			=> 'select',
				'css'			  => 'width: 250px;',
				'class'			  => 'chosen_select wc-enhanced-select',
				'default'		 => 'NA',
				'options'		 => $this->customer_classification_code,
				'description'	 => __( 'Valid if origin country is US.' ),
				'desc_tip'		=> true
			),
			'tax_indicator'	  => array(
				'title'		   => __( 'Tax On Rates', 'ups-woocommerce-shipping' ),
				'description'	 => __( 'Taxes may be applicable to shipment', 'ups-woocommerce-shipping' ),
				'desc_tip'		   => true,
				'type'			=> 'checkbox',
				'default'		 => 'no'
			),
			'pickup_enabled'	  => array(
				'title'		   => __( 'Enable Pickup', 'ups-woocommerce-shipping' ),
				'description'	 => __( 'Enable this to setup pickup request', 'ups-woocommerce-shipping' ),
				'desc_tip'		   => true,
				'type'			=> 'checkbox',
				'default'		 => 'no'
			),
			'pickup_start_time'		   => array(
				'title'		   => __( 'Pickup Start Time', 'ups-woocommerce-shipping' ),
				'description'	 => __( 'Items will be ready for pickup by this time from shop', 'ups-woocommerce-shipping' ),
				'desc_tip'		   => true,
				'type'			=> 'select',
				'class'			  => 'wf_ups_pickup_grp wc-enhanced-select',
				'default'		 => 8,
				'options'		  => $pickup_start_time_options,
			),
			'pickup_close_time'		   => array(
				'title'		   => __( 'Company Close Time', 'ups-woocommerce-shipping' ),
				'description'	 => __( 'Your shop closing time. It must be greater than company open time', 'ups-woocommerce-shipping' ),
				'desc_tip'		   => true,
				'type'			=> 'select',
				'class'			  => 'wf_ups_pickup_grp wc-enhanced-select',
				'default'		 => 18,
				'options'		  => $pickup_close_time_options,
			),
			'pickup_date'		   => array(
				'title'			  => __( 'Pick up date', 'ups-woocommerce-shipping' ),
				'type'			   => 'select',
				'desc_tip'		   => true,
				'description'	 => __( 'Default option will pick current date. Choos \'Select working days\' to configure working days', 'ups-woocommerce-shipping' ),
				'default'			=> 'current',
				'class'			  => 'wf_ups_pickup_grp wc-enhanced-select',
				'options'			=> array(
					'current'			=> __( 'Default', 'ups-woocommerce-shipping' ),
					'specific'	   => __( 'Select working days', 'ups-woocommerce-shipping' ),
				),
			),
			'working_days'			  => array(
				'title'			  => __( 'Select working days', 'ups-woocommerce-shipping' ),
				'type'			   => 'multiselect',
				'desc_tip'		   => true,
				'description'	 => __( 'Select working days here. Selected days will be used for pickup' ),'class'			  => 'wf_ups_pickup_grp pickup_working_days chosen_select',
				'css'				=> 'width: 450px;',
				'default'			=> array('Mon', 'Tue', 'Wed', 'Thu', 'Fri'),
				'options'			=> array( 'Sun'=>'Sunday', 'Mon'=>'Monday','Tue'=>'Tuesday', 'Wed'=>'Wednesday', 'Thu'=>'Thursday', 'Fri'=>'Friday', 'Sat'=>'Saturday'),
			),
			'commercial_invoice' => array(
				'title'		   => __( 'Commercial Invoice', 'ups-woocommerce-shipping' ),
				'label'		   => __( 'Enable', 'ups-woocommerce-shipping' ),
				'desc_tip'		=> true,
				'type'			=> 'checkbox',
				'default'		 => 'no',
				'description'	 => __('On enabling this option will create commercial invoice. Applicable for international shipping only.', 'ups-woocommerce-shipping'),
			),			
			'declaration_statement' => array(
				'title'		   => __( 'Declaration Statement', 'ups-woocommerce-shipping' ),
				'desc_tip'		=> true,
				'label'		   => __( 'Enable', 'ups-woocommerce-shipping' ),
				'type'			=> 'text',
				'css'			  => 'width:1000px',
				'placeholder'	  => __('Example: I hereby certify that the goods covered by this shipment qualify as originating goods for purposes of preferential tariff treatment under the NAFTA.','ups-woocommerce-shipping'),
				'description'	 => __('This is an optional field for the legal explanation, used by Customs, for the delivering of this shipment. It must be identical to the set of declarations actually used by Customs.', 'ups-woocommerce-shipping'),
			),			
			'reason_export'	  => array(
				'title'		   => __( 'Reason For Export', 'ups-woocommerce-shipping' ),
				'type'			   => 'select',
				'default'			=> 0,
				'class'				=>'wc-enhanced-select',
				'options'			=> array(
					'none'	   	=> __( 'Select one', 	'ups-woocommerce-shipping' ),
					'SALE'	   	=> __( 'SALE', 	'ups-woocommerce-shipping' ),
					'GIFT'	   	=> __( 'GIFT', 	'ups-woocommerce-shipping' ),
					'SAMPLE'		=> __( 'SAMPLE', 	'ups-woocommerce-shipping' ),
					'RETURN'		=> __( 'RETURN', 	'ups-woocommerce-shipping' ),
					'REPAIR'		=> __( 'REPAIR', 	'ups-woocommerce-shipping' ),
					'INTERCOMPANYDATA'=> __( 'INTERCOMPANYDATA', 	'ups-woocommerce-shipping' ),
				),
				'description'	 => __( 'This may required for customs purpose', 'ups-woocommerce-shipping' ),
				'desc_tip'		   => true,
			),
			'ssl_verify'	  => array(
				'title'		   => __( 'SSL Verify', 'ups-woocommerce-shipping' ),
				'type'			   => 'select',
				'default'			=> 0,
				'class'				=>'wc-enhanced-select',
				'options'			=> array(
					0		=> __( 'No', 	'ups-woocommerce-shipping' ),
					1		=> __( 'Yes',	'ups-woocommerce-shipping' ),
				),
				'description'	 => __( 'SSL Verification for API call. Recommended select \'No\'.', 'ups-woocommerce-shipping' ),
				'desc_tip'		   => true,
			),
			'accesspoint_locator' => array(
				'title'		   => __( 'Access Point Locator', 'ups-woocommerce-shipping' ),
				'label'		   => __( 'Enable', 'ups-woocommerce-shipping' ),
				'type'			=> 'checkbox',
				'default'		 => 'no'
			),
			'min_amount'  => array(
				'title'		   => __( 'Minimum Order Amount', 'ups-woocommerce-shipping' ),
				'type'			=> 'text',
				'placeholder'	=> wc_format_localized_price( 0 ),
				'default'		 => '0',
				'description'	 => __( 'Users will need to spend this amount to get this shipping available.', 'ups-woocommerce-shipping' ),
				'desc_tip'		   => true,
			),
			'tin_number'  => array(
				'title'		   => __( 'TIN Number', 'ups-woocommerce-shipping' ),
				'type'			=> 'text',
				'placeholder'	  => 'Tax Identification Number',
				'description'	 => __( 'Tax Identification Number', 'ups-woocommerce-shipping' ),
				'desc_tip'		   => true,
			),/*
			'ground_freight' => array(
				'title'		   => __( 'Ground Freight Shipment', 'ups-woocommerce-shipping' ),
				'label'		   => __( 'Enable', 'ups-woocommerce-shipping' ),
				'type'			=> 'checkbox',
				'default'		 => 'no',
				'description'	 => __( 'The UPS Account Number should be qualified to receive Ground Freight Rates.', 'ups-woocommerce-shipping' ),
				'desc_tip'		   => true,
			),*/
			'email_notification'  => array(
				'title'		   => __( 'Send email notification to', 'ups-woocommerce-shipping' ),
				'type'			=> 'multiselect',
				'class'			  => 'multiselect chosen_select',
				'default'		 => '',
				'options'		 => array('Sender', 'Recipient'),
				'description'	 => __( 'Choose whom to send the notification. Leave blank to not send notification.', 'ups-woocommerce-shipping' ),
				'desc_tip'		=> true,
			),	
			'latin_encoding' => array(
				'title'		   => __( 'Enable Latin Encoding', 'ups-woocommerce-shipping' ),
				'label'		   => __( 'Enable', 'ups-woocommerce-shipping' ),
				'type'			=> 'checkbox',
				'default'		 => 'no',
				'description'	 => __( 'Check this option to use Latin encoding over default encoding.', 'ups-woocommerce-shipping' ),
				'desc_tip'		   => true,
			),

			'label_generation_advance'   => array(
				'title'		   => __( 'Label Generation(Advance Setting)', 'ups-woocommerce-shipping' ),
				'type'			=> 'title',
				'class'			  => 'wf_settings_heading_tab'
			),
			'automate_package_generation'	  => array(
				'title'		   => __( 'Generate Packages Automatically After Order Received', 'ups-woocommerce-shipping' ),
				'label'			  => __( 'Enable', 'ups-woocommerce-shipping' ),			
				'description'	 => __( 'This will generate packages automatically after order is received and payment is successful', 'ups-woocommerce-shipping' ),
				'desc_tip'		   => true,
				'type'			=> 'checkbox',
				'default'		 => 'no'
			),
			'automate_label_generation'	  => array(
				'title'		   => __( 'Generate Shipping Labels Automatically After Order Received', 'ups-woocommerce-shipping' ),
				'label'			  => __( 'Enable', 'ups-woocommerce-shipping' ),			
				'description'	 => __( 'This will generate shipping labels automatically after order is received and payment is successful', 'ups-woocommerce-shipping' ),
				'desc_tip'		   => true,
				'type'			=> 'checkbox',
				'default'		 => 'no'
			),


			'default_dom_service' => array(
				'title'		   => __( 'Default service for domestic', 'ups-woocommerce-shipping' ),
				'description'	 => __( 'Default service for domestic label. This will consider if no UPS services selected from frond end while placing the order', 'ups-woocommerce-shipping' ),
				'desc_tip'		   => true,
				'type'			=> 'select',
				'default'		 => '',
				'class'		   => 'wc-enhanced-select',
				'options'		  => $this->services,
			),
			'default_int_service'	=> array(
				'title'		   => __( 'Default service for International', 'ups-woocommerce-shipping' ),
				'description'	 => __( 'Default service for International label. This will consider if no UPS services selected from frond end while placing the order', 'ups-woocommerce-shipping' ),
				'desc_tip'		   => true,
				'type'			=> 'select',
				'class'		   => 'wc-enhanced-select',
				'default'		 => '',
				'options'		  => $this->services,
			),


			'auto_email_label'	  => array(
				'title'		   => __( 'Send label in email to customer after label generation', 'ups-woocommerce-shipping' ),
				'label'			  => __( 'Enable', 'ups-woocommerce-shipping' ),			
				'description'	 => __( '', 'ups-woocommerce-shipping' ),
				'desc_tip'		   => true,
				'type'			=> 'checkbox',
				'default'		 => 'no'
			),
			'allow_label_btn_on_myaccount'	  => array(
				'title'		   => __( 'Allow customer to print label from his myaccount->order page', 'ups-woocommerce-shipping' ),
				'label'			  => __( 'Enable', 'ups-woocommerce-shipping' ),			
				'description'	 => __( 'A button will be available for downloading the label and printing', 'ups-woocommerce-shipping' ),
				'desc_tip'		   => true,
				'type'			=> 'checkbox',
				'default'		 => 'no'
			),			
			'email_content'  => array(
			'title'		   => __( 'Content of Email With Label', 'ups-woocommerce-shipping' ),
			'type'			=> 'text',
			'placeholder'	=> '',
			'default'		 => '',
			'desc_tip'		   => true,
		),
		);
	}
	
	/**
	 * See if method is available based on the package and cart.
	 *
	 * @param array $package Shipping package.
	 * @return bool
	 */
	 
	public function is_available( $package ) {
		
		if ( "no" === $this->enabled ) {
			return false;
		}
		
		if ( 'specific' === $this->availability ) {
			if ( is_array( $this->countries ) && ! in_array( $package['destination']['country'], $this->countries ) ) {
				return false;
			}
		} elseif ( 'excluding' === $this->availability ) {
			if ( is_array( $this->countries ) && ( in_array( $package['destination']['country'], $this->countries ) || ! $package['destination']['country'] ) ) {
				return false;
			}
		}
		
		$has_met_min_amount = false;
		
		if(!method_exists(WC()->cart, 'get_displayed_subtotal')){// WC version below 2.6
			$total = WC()->cart->subtotal;
		}else{
			$total = WC()->cart->get_displayed_subtotal();
			if ( 'incl' === WC()->cart->tax_display_cart ) {
				$total = $total - ( WC()->cart->get_cart_discount_total() + WC()->cart->get_cart_discount_tax_total() );
			} else {
				$total = $total - WC()->cart->get_cart_discount_total();
			}
		}
		if ( $total >= $this->min_amount ) {
			$has_met_min_amount = true;
		}
		$is_available	=	$has_met_min_amount;
		return apply_filters( 'woocommerce_shipping_' . $this->id . '_is_available', $is_available, $package );
	}

	/**
	 * calculate_shipping function.
	 *
	 * @access public
	 * @param mixed $package
	 * @return void
	 */
	public function calculate_shipping( $package=array() ) {
		global $woocommerce;
		libxml_use_internal_errors( true );

		// Only return rates if the package has a destination including country, postcode
		//if ( ( '' ==$package['destination']['country'] ) || ( ''==$package['destination']['postcode'] ) ) {
		if ( '' == $package['destination']['country'] ) {
			//$this->debug( __('UPS: Country, or Zip not yet supplied. Rates not requested.', 'ups-woocommerce-shipping') );
			$this->debug( __('UPS: Country not yet supplied. Rates not requested.', 'ups-woocommerce-shipping') );
			return; 
		}
		
		if( in_array( $package['destination']['country'] , $this->no_postcode_country_array ) ) {
			if ( empty( $package['destination']['city'] ) ) {
				$this->debug( __('UPS: City not yet supplied. Rates not requested.', 'ups-woocommerce-shipping') );
				return;
			}
		}
		else if( ''== $package['destination']['postcode'] ) {
			$this->debug( __('UPS: Zip not yet supplied. Rates not requested.', 'ups-woocommerce-shipping') );
			return;
		}

		// To Support Multi Vendor plugin
		$packages = apply_filters('wf_filter_package_address', array($package) , $this->ship_from_address);
		//Woocommerce packages after dividing the products based on vendor, if vendor plugin exist
		$wc_total_packages_count = count($packages);

		foreach( $packages as $package ) {
			$package_params	=	array();
			if( ($this->origin_country == $package['destination']['country']) && in_array( $this->origin_country, $this->dc_domestic_countries ) ){
				$package_params['delivery_confirmation_applicable']	=	true;
				$this->international_delivery_confirmation_applicable = false;
			}
			else {
				$this->international_delivery_confirmation_applicable = true;
			}
			
			$package_requests		= $this->get_package_requests( $package, $package_params );
			// $all_package_requests	= empty($all_package_requests) ? $package_requests : array_merge( $all_package_requests, $package_requests);
		
			if ( $package_requests ) {
				
				// To get rate for services like ups ground, 3 day select etc.
				$rate_requests 		= $this->get_rate_requests( $package_requests, $package );
				$rates['general'][] =  $this->process_result( $this->get_result($rate_requests) );
				// End of get rates for services like ups ground, 3 day select etc.
				
				//For Worldwide Express Freight Service
				if( isset($this->custom_services[96]['enabled']) && $this->custom_services[96]['enabled'] ) {
					$rate_requests	= $this->get_rate_requests( $package_requests, $package, 'Pallet', 96 );
					$rates[96][]	= $this->process_result( $this->get_result($rate_requests) );
				}
				
				// For Freight services 308, 309, 334, 349
				if( $this->enable_freight ){
					$freight_ups=new wf_freight_ups($this);
					foreach ($this->freigth_services as $service_code => $value) {
						if( ! empty($this->settings['services'][$service_code]['enabled']) ) {
							$this->debug( "UPS FREIGHT SERVICE START: $service_code" );
							$freight_rate	= array();
							$cost			= 0;

							$freight_rate_requests = $freight_ups->get_rate_request( $package, $service_code, $package_requests );		// Freight rate request
							
							foreach( $package_requests as $package_key => $package_request) {
								//Freight rate response for individual packages request
								$freight_rate_response = $this->process_result( $this->get_result($freight_rate_requests[$package_key], 'freight'), 'json' );
								if( ! empty($freight_rate_response[WF_UPS_ID.":$service_code"]['cost']) ) {
									$cost += $freight_rate_response[WF_UPS_ID.":$service_code"]['cost']; // Cost of freight packages till now processed for individual freight service
									$freight_rate_response[WF_UPS_ID.":$service_code"]['cost'] = $cost;
									$freight_rate = $freight_rate_response;
								}
								else {				// If no response comes for any packages then we won't show the response for that Freight service
									$freight_rate = array();
									$this->debug( "UPS FREIGHT SERVICE RESPONSE FAILED FOR SOME PACKAGES : $service_code" );
									break;
								}
							}
							$this->debug( "UPS FREIGHT SERVICE END : $service_code" );
						}
						// If rate comes for freight sevices then merge it in rates array
						if( ! empty($freight_rate) ) {
							$rates[$service_code][] = $freight_rate;
						}

					}
				}
				// End code for Freight services 308, 309, 334, 349
				
				//Surepost
				foreach ( $this->ups_surepost_services as $service_code ) {
					if( empty($this->custom_services[$service_code]['enabled']) || ( $this->custom_services[$service_code]['enabled'] != 1 ) ){	//It will be not set for European origin address
							continue;
					}
					$rate_requests			= $this->get_rate_requests( $package_requests, $package, 'surepost', $service_code );
					$rates[$service_code][]	= $this->process_result( $this->get_result($rate_requests, 'surepost') );
				}
			}
		}
		if( ! empty($rates) ) {
			foreach ($rates as $rate_type => $all_packages_rates ) {
				//For every woocommerce package there must be response, so number of woocommerce package and UPS response must be equal
				if( count($rates[$rate_type]) == $wc_total_packages_count ) {
					//UPS services keys in rate response
					$ups_found_services_keys = array_keys(current($all_packages_rates));
					
					foreach( $ups_found_services_keys as $ups_sevice) {
						$count = 0;
						foreach( $all_packages_rates as $package_rates ) {
							if( ! empty($package_rates[$ups_sevice] ) ) {
								if( empty($all_rates[$ups_sevice]) )
								{
									$all_rates[$ups_sevice] = $package_rates[$ups_sevice];
								}
								else {
									$all_rates[$ups_sevice]['cost'] = (float) $all_rates[$ups_sevice]['cost'] + (float) $package_rates[$ups_sevice]['cost'];
								}
								$count++;
							}
						}
						// If number of package requests not equal to number of response for any particular service
						if( $count != $wc_total_packages_count ) {
							unset($all_rates[$ups_sevice]);
						}
					}
				}
			}
		}

		
		if(	!empty( $all_rates ) ){
			$this->xa_add_rates($all_rates);
		}
	}
	// End of Calculate Shipping function
	
	function xa_add_rates( $rates ){
		if ( $rates ) {
			$this->conversion_rate = apply_filters( 'xa_conversion_rate', $this->conversion_rate, ( isset($xml->RatedShipment[0]->TotalCharges->CurrencyCode) ? (string)$xml->RatedShipment[0]->TotalCharges->CurrencyCode : null ) );
			if( $this->conversion_rate ) {
				foreach ( $rates as $key => $rate ) {
					$rates[ $key ][ 'cost' ] = isset($rate[ 'cost' ]) ? $rate[ 'cost' ] * $this->conversion_rate : 0;
				}
			}

			if ( $this->offer_rates == 'all' ) {

				uasort( $rates, array( $this, 'sort_rates' ) );
				foreach ( $rates as $key => $rate ) {
					$this->add_rate( $rate );
				}

			} else {

				$cheapest_rate = '';

				foreach ( $rates as $key => $rate ) {
					if ( ! $cheapest_rate || ( $cheapest_rate['cost'] > $rate['cost'] && !empty($rate['cost']) ) )
						$cheapest_rate = $rate;
				}
				$cheapest_rate['label'] = $this->title;
				$this->add_rate( $cheapest_rate );
			}
		// Fallback
		} elseif ( $this->fallback ) {
			$this->add_rate( array(
				'id' 	=> $this->id . '_fallback',
				'label' => $this->title,
				'cost' 	=> $this->fallback,
				'sort'  => 0
			) );
			$this->debug( __('UPS: Using Fallback setting.', 'ups-woocommerce-shipping') );
		}
	}

	public function process_result( $ups_response, $type='' )
	{
		//for freight response
		if( $type == 'json' ){
			$xml=json_decode($ups_response);
		}else{
			$xml = simplexml_load_string( preg_replace('/<\?xml.*\?>/','', $ups_response ) );
		}
		
		if ( ! $xml ) {
			$this->debug( __( 'Failed loading XML', 'ups-woocommerce-shipping' ), 'error' );
			return;
		}
		$rates = array();
		if ( ( property_exists($xml,'Response') && $xml->Response->ResponseStatusCode == 1)  || ( $type =='json' && !property_exists($xml,'Fault') ) ) {

			$xml = apply_filters('wf_ups_rate', $xml);
			$xml_response = isset($xml->RatedShipment) ? $xml->RatedShipment : $xml;	// Normal rates : freight rates
			foreach ( $xml_response as $response ) {
				$code = (string)$response->Service->Code;

				if( ! empty( $this->custom_services[$code] ) && $this->custom_services[$code]['enabled'] != 1 ){		// For Freight service code custom services won't be set
					continue;
				}
										
				if(in_array("$code",array_keys($this->freigth_services)) && property_exists($xml,'FreightRateResponse')){
					$service_name = $this->freigth_services[$code];
						$rate_cost = (float) $xml->FreightRateResponse->TotalShipmentCharge->MonetaryValue;	
				}
				else{	
					$service_name = $this->services[ $code ];
					if ( $this->negotiated && isset( $response->NegotiatedRates->NetSummaryCharges->GrandTotal->MonetaryValue ) ){
						if(property_exists($response->NegotiatedRates->NetSummaryCharges,'TotalChargesWithTaxes')){
							$rate_cost = (float) $response->NegotiatedRates->NetSummaryCharges->TotalChargesWithTaxes->MonetaryValue;
						}else{
							$rate_cost = (float) $response->NegotiatedRates->NetSummaryCharges->GrandTotal->MonetaryValue;
						}							
					}else{
						$rate_cost = (float) $response->TotalCharges->MonetaryValue;
					}							
				}


				$rate_id	 = $this->id . ':' . $code;
				$rate_name   = $service_name . ' (' . $this->title . ')';

				// Name adjustment
				if ( ! empty( $this->custom_services[ $code ]['name'] ) )
					$rate_name = $this->custom_services[ $code ]['name'];

				// Cost adjustment %, don't apply on order page rates
				if ( ! empty( $this->custom_services[ $code ]['adjustment_percent'] ) && ! isset($_GET['wf_ups_generate_packages_rates']) )
					$rate_cost = $rate_cost + ( $rate_cost * ( floatval( $this->custom_services[ $code ]['adjustment_percent'] ) / 100 ) );
				// Cost adjustment, don't apply on order page rates
				if ( ! empty( $this->custom_services[ $code ]['adjustment'] ) && ! isset($_GET['wf_ups_generate_packages_rates']) )
					$rate_cost = $rate_cost + floatval( $this->custom_services[ $code ]['adjustment'] );

				// Sort
				if ( isset( $this->custom_services[ $code ]['order'] ) ) {
					$sort = $this->custom_services[ $code ]['order'];
				} else {
					$sort = 999;
				}

				$rates[ $rate_id ] = array(
					'id' 	=> $rate_id,
					'label' => $rate_name,
					'cost' 	=> $rate_cost,
					'sort'  => $sort
				);
			} 
		}
		elseif($type == 'json') {
			$this->debug( sprintf( __( '[UPS] No rate returned,  %s (UPS code: %s)', 'ups-woocommerce-shipping' ),
									$xml->Fault->detail->Errors->ErrorDetail->PrimaryErrorCode->Description,
									$xml->Fault->detail->Errors->ErrorDetail->PrimaryErrorCode->Code ), 'error' );						
		}
		else {
			// Either there was an error on this rate, or the rate is not valid (i.e. it is a domestic rate, but shipping international)
			
			$this->debug( sprintf( __( '[UPS] No rate returned,  %s (UPS code: %s)', 'ups-woocommerce-shipping' ),
									$xml->Response->Error->ErrorDescription,
									$xml->Response->Error->ErrorCode ), 'error' );						

		}
		return $rates;
	}

	public function get_result($request, $request_type='')
	{
		$send_request		   = str_replace( array( "\n", "\r" ), '', $request );
		$transient			  = 'ups_quote_' . md5( $request );
		$cached_response		= get_transient( $transient );
		
		if ( $cached_response === false ) {
			
			if( $request_type == 'freight' ){
				
				$response = wp_remote_post( $this->freight_endpoint,
					array(
						'timeout'   => 70,
						'sslverify' => $this->ssl_verify,
						'body'	  => $send_request
					)
				);		
			}else{
				$response = wp_remote_post( $this->endpoint,
					array(
						'timeout'   => 70,
						'sslverify' => $this->ssl_verify,
						'body'	  => $send_request
					)
				);						
			}
			
			if ( is_wp_error( $response ) ) {	
				$error_string = $response->get_error_message();
				$this->debug( 'UPS REQUEST FAILED: <pre>' . print_r( htmlspecialchars( $error_string ), true ) . '</pre>' );
			}
			elseif ( ! empty( $response['body'] ) ) {	
				$ups_response = $response['body'];
				set_transient( $transient, $response['body'], YEAR_IN_SECONDS );
			}

		} else {
			$this->debug( __( 'UPS: Using cached response.', 'ups-woocommerce-shipping' ) );
			$ups_response = $cached_response;
		}

		$this->debug( "UPS ".strtoupper($request_type)." REQUEST: <pre>" . print_r( htmlspecialchars( $request ), true ) . '</pre>' );
		$this->debug( 'UPS '.strtoupper($request_type).' RESPONSE: <pre>' . print_r( htmlspecialchars( $ups_response  ), true ) . '</pre>' );

		return $ups_response;
	}

	/**
	 * sort_rates function.
	 *
	 * @access public
	 * @param mixed $a
	 * @param mixed $b
	 * @return void
	 */
	public function sort_rates( $a, $b ) {
		if ( isset($a['sort']) && isset($b['sort']) && ( $a['sort'] == $b['sort'] ) ) return 0;
		return (  isset($a['sort']) && isset($b['sort']) && ( $a['sort'] < $b['sort'] ) ) ? -1 : 1;
	}

	/**
	 * get_package_requests
	 *
	 *
	 *
	 * @access private
	 * @return void
	 */
	private function get_package_requests( $package,$params=array()) {
		if( empty($package['contents']) && class_exists('wf_admin_notice') ) {
			wf_admin_notice::add_notice( __("UPS - Something wrong with products associated with order, or no products associated with order.", "ups-woocommerce-shipping"), 'error');
			return false;
		}
		// Choose selected packing
		switch ( $this->packing_method ) {
			case 'box_packing' :
				$requests = $this->box_shipping( $package,$params);
			break;
				case 'weight_based' :
						$requests = $this->weight_based_shipping($package,$params);
				break;
			case 'per_item' :
			default :
				$requests = $this->per_item_shipping( $package,$params);
			break;
		}
		return $requests;
	}

	/**
	 * get_rate_requests
	 *
	 * Get rate requests for all
	 * @access private
	 * @return array of strings - XML
	 *
	 */
	public function  get_rate_requests( $package_requests, $package, $request_type='', $service_code='' ) {
		global $woocommerce;

		$customer = $woocommerce->customer;		
		
			$package_requests_to_append	= $package_requests;
			
			$rate_request_data	=	array(
				'user_id'			=>	$this->user_id,
				'password'			=>	str_replace( '&', '&amp;', $this->password ), // Ampersand will break XML doc, so replace with encoded version.
				'access_key'		=>	$this->access_key,
				'shipper_number'	=>	$this->shipper_number,
				'origin_addressline'=>	$this->origin_addressline,
				'origin_postcode'	=>	$this->origin_postcode,
				'origin_city'		=>	$this->origin_city,
				'origin_state'		=>	$this->origin_state,
				'origin_country'	=>	$this->origin_country,
			);
			
			$rate_request_data	=	apply_filters('wf_ups_rate_request_data', $rate_request_data, $package, $package_requests);
			
			// Security Header
			$request  = "<?xml version=\"1.0\" ?>" . "\n";
			$request .= "<AccessRequest xml:lang='en-US'>" . "\n";
			$request .= "	<AccessLicenseNumber>" . $rate_request_data['access_key'] . "</AccessLicenseNumber>" . "\n";
			$request .= "	<UserId>" . $rate_request_data['user_id'] . "</UserId>" . "\n";
			$request .= "	<Password>" . $rate_request_data['password'] . "</Password>" . "\n";
			$request .= "</AccessRequest>" . "\n";
			$request .= "<?xml version=\"1.0\" ?>" . "\n";
			$request .= "<RatingServiceSelectionRequest>" . "\n";
			$request .= "	<Request>" . "\n";
			$request .= "	<TransactionReference>" . "\n";
			$request .= "		<CustomerContext>Rating and Service</CustomerContext>" . "\n";
			$request .= "		<XpciVersion>1.0</XpciVersion>" . "\n";
			$request .= "	</TransactionReference>" . "\n";
			$request .= "	<RequestAction>Rate</RequestAction>" . "\n";
			
			$requestOption = empty($service_code) ? 'Shop' : 'Rate';
			$request .= "	<RequestOption>$requestOption</RequestOption>" . "\n";
			$request .= "	</Request>" . "\n";
			$request .= "	<PickupType>" . "\n";
			$request .= "		<Code>" . $this->pickup . "</Code>" . "\n";
			$request .= "		<Description>" . $this->pickup_code[$this->pickup] . "</Description>" . "\n";
			$request .= "	</PickupType>" . "\n";
				
			//Accroding to the documentaion CustomerClassification will not work for non-us county. But UPS team confirmed this will for any country.
			// if ( 'US' == $rate_request_data['origin_country']) {
				if ( $this->negotiated ) {
					$request .= "	<CustomerClassification>" . "\n";
					$request .= "		<Code>" . "00" . "</Code>" . "\n";
					$request .= "	</CustomerClassification>" . "\n";   
				}
				elseif ( !empty( $this->customer_classification ) && $this->customer_classification != 'NA' ) {
					$request .= "	<CustomerClassification>" . "\n";
					$request .= "		<Code>" . $this->customer_classification . "</Code>" . "\n";
					$request .= "	</CustomerClassification>" . "\n";   
				}
			// }
				
				// Shipment information
				$request .= "	<Shipment>" . "\n";
				
				if($this->accesspoint_locator ){
					$access_point_node = $this->get_acccesspoint_rate_request();					
					if(!empty($access_point_node)){// Access Point Addresses Are All Commercial
						$this->residential	=	false;
						$request .= $access_point_node;
					}
					
				}
				
				$request .= "		<Description>WooCommerce Rate Request</Description>" . "\n";
				$request .= "		<Shipper>" . "\n";
				$request .= "			<ShipperNumber>" . $rate_request_data['shipper_number'] . "</ShipperNumber>" . "\n";
				$request .= "			<Address>" . "\n";
				$request .= "				<AddressLine>" . $rate_request_data['origin_addressline'] . "</AddressLine>" . "\n";
				//$request .= "				<City>" . $this->origin_city . "</City>" . "\n";
				//$request .= "				<PostalCode>" . $this->origin_postcode . "</PostalCode>" . "\n";
				$request .= $this->wf_get_postcode_city( $rate_request_data['origin_country'], $rate_request_data['origin_city'], $rate_request_data['origin_postcode'] );
				$request .= "				<CountryCode>" . $rate_request_data['origin_country'] . "</CountryCode>" . "\n";
				$request .= "			</Address>" . "\n";
				$request .= "		</Shipper>" . "\n";
				$request .= "		<ShipTo>" . "\n";
				$request .= "			<Address>" . "\n";

				// Residential address Validation done by API automatically if address_1 is available.
				$address = '';
				if( !empty($package['destination']['address_1']) ){
					$address = $package['destination']['address_1'];
				}elseif( !empty($package['destination']['address']) ){
					$address = $package['destination']['address'];
				}
				if( !empty($address) ){
					$request .= "				<AddressLine1>" . $address . "</AddressLine1>" . "\n";
				}

				$request .= "				<StateProvinceCode>" . $package['destination']['state'] . "</StateProvinceCode>" . "\n";
				
				$destination_city = strtoupper( $package['destination']['city'] );
				$destination_country = "";
				if ( ( "PR" == $package['destination']['state'] ) && ( "US" == $package['destination']['country'] ) ) {		
						$destination_country = "PR";
				} else {
						$destination_country = $package['destination']['country'];
				}
				
				//$request .= "				<PostalCode>" . $package['destination']['postcode'] . "</PostalCode>" . "\n";
				$request .= $this->wf_get_postcode_city( $destination_country, $destination_city, $package['destination']['postcode'] );
				$request .= "				<CountryCode>" . $destination_country . "</CountryCode>" . "\n";
				
				if ( $this->residential ) {
				$request .= "				<ResidentialAddressIndicator></ResidentialAddressIndicator>" . "\n";
				}
				$request .= "			</Address>" . "\n";
				$request .= "		</ShipTo>" . "\n";
				$request .= "		<ShipFrom>" . "\n";
				$request .= "			<Address>" . "\n";
				$request .= "				<AddressLine>" . $rate_request_data['origin_addressline'] . "</AddressLine>" . "\n";
				//$request .= "				<City>" . $this->origin_city . "</City>" . "\n";
				//$request .= "				<PostalCode>" . $this->origin_postcode . "</PostalCode>" . "\n";
				$request .= $this->wf_get_postcode_city( $rate_request_data['origin_country'], $rate_request_data['origin_city'], $rate_request_data['origin_postcode']);
				$request .= "				<CountryCode>" . $rate_request_data['origin_country'] . "</CountryCode>" . "\n";
				if ( $this->negotiated && $rate_request_data['origin_state'] ) {
				$request .= "				<StateProvinceCode>" . $rate_request_data['origin_state'] . "</StateProvinceCode>" . "\n";
				}
				$request .= "			</Address>" . "\n";
				$request .= "		</ShipFrom>" . "\n";

				//For Worldwide Express Freight Service
				if( $request_type == 'Pallet' && $service_code == 96 && isset($package['contents']) && is_array($package['contents'] ) ) {
					$total_item_count = 0;
					foreach ( $package['contents'] as $product ) {
						$total_item_count += $product['quantity'];
					}
					$request .= "	<NumOfPieces>".$total_item_count."</NumOfPieces>"."\n";
				}
				//Ground Freight Pricing Rates option indicator. If the Ground Freight Pricing Shipment indicator is enabled and  hipper number is authorized then Ground Freight Pricing rates should be returned in the response
				/*if( $this->ground_freight ){
					$request .= "		<FRSPaymentInformation>" . "\n";
					$request .= "			<Type>" . "\n";
					$request .= "				<Code>01</Code>" . "\n";
					$request .= "			</Type>" . "\n";
					$request .= "			<AccountNumber>$this->shipper_number</AccountNumber>" . "\n";

					$request .= "		</FRSPaymentInformation>" . "\n";

					$request .= "		<ShipmentRatingOptions>" . "\n";
					$request .= "			<FRSShipmentIndicator>1</FRSShipmentIndicator>" . "\n";
					$request .= "		</ShipmentRatingOptions>" . "\n";
				}*/
				if( !empty($service_code) ){
					$request .= "		<Service>" . "\n";
					$request .= "			<Code>" . $this->get_service_code_for_country( $service_code,$rate_request_data['origin_country'] ) . "</Code>" . "\n";
					$request .= "		</Service>" . "\n";
				}
				// packages
				
				foreach ( $package_requests_to_append as $key => $package_request ) {
					if( $request_type == 'surepost' ){
						unset($package_request['Package']['PackageServiceOptions']['InsuredValue']);
						if( $service_code == 92 ) {
							$package_request = $this->convert_weight( $package_request, $service_code );
						}
					}
					//For Worldwide Express Freight Service
					if( $request_type == "Pallet" ) {
						$package_request['Package']['PackagingType']['Code'] = 30;
						// Setting Length, Width and Height for weight based packing.
						if( empty($package_request['Package']['Dimensions']) ) {
							
							$package_request['Package']['Dimensions'] = array(
								'UnitOfMeasurement' => array(
								    'Code'  =>	($package_request['Package']['PackageWeight']['UnitOfMeasurement']['Code'] == 'LBS') ? 'IN' : 'CM',
								),
								'Length'    =>	($package_request['Package']['PackageWeight']['UnitOfMeasurement']['Code'] == 'LBS') ? 47 : 119,
								'Width'	    =>	($package_request['Package']['PackageWeight']['UnitOfMeasurement']['Code'] == 'LBS') ? 47 : 119,
								'Height'    =>	($package_request['Package']['PackageWeight']['UnitOfMeasurement']['Code'] == 'LBS') ? 47 : 119
							);
						}
					}
					
					// To Set deliveryconfirmation at shipment level if shipment is international or outside of $this->dc_domestic_countries
					if( $this->international_delivery_confirmation_applicable ) {
						$shipment_delivery_confirmation = $this->get_package_signature($package_request['Package']['items']);
						$delivery_confirmation = ( isset( $delivery_confirmation) && $delivery_confirmation >= $shipment_delivery_confirmation) ? $delivery_confirmation : $shipment_delivery_confirmation;
					}
					
					unset($package_request['Package']['items']);		//Not required further
					$request .= $this->wf_array_to_xml($package_request);
				}
				// negotiated rates flag
				if ( $this->negotiated ) {
				$request .= "		<RateInformation>" . "\n";
				$request .= "			<NegotiatedRatesIndicator />" . "\n";
				$request .= "		</RateInformation>" . "\n";
				}
				
				if($this->tax_indicator){
					$request .= "		<TaxInformationIndicator/>" . "\n";
				}				
				
				// Set deliveryconfirmation at shipment level for international shipment
				if( !empty($delivery_confirmation ) ){
					$delivery_confirmation = ($delivery_confirmation == 3) ? 2 : 1;
					$request .= "\n		<ShipmentServiceOptions>"."\n";
					$request .= "			<DeliveryConfirmation>"
							. "<DCISType>$delivery_confirmation</DCISType>"
							. "</DeliveryConfirmation>"."\n";
					$request .= "		</ShipmentServiceOptions>"."\n";
				}
				$request .= "	</Shipment>" . "\n";
				$request .= "</RatingServiceSelectionRequest>" . "\n";

				return apply_filters('wf_ups_rate_request', $request, $package);


	}
	private function wf_get_accesspoint_datas( $order_details='' ){
		// For getting the rates in backend
		if( is_admin() ){
			if( isset($_GET['wf_ups_generate_packages_rates']) ) {
				$order_id = base64_decode($_GET['wf_ups_generate_packages_rates']);
				$order_details = new WC_Order($order_id);
			}
			else {
				return;
			}
		}
		
		if( !empty( $order_details ) ){
			if( WC()->version < '2.7.0' ){
				return ( isset($order_details->shipping_accesspoint) ) ? json_decode( stripslashes($order_details->shipping_accesspoint) ) : '';
			}else{
				$address_field = $order_details->get_meta('_shipping_accesspoint');
				return json_decode(stripslashes($address_field));
			}
		}else{
			if( WC()->version < '2.7.0' ){
				return WC()->customer->__get('shipping_accesspoint');
			}else{
				return WC()->customer->get_meta('shipping_accesspoint');
			}
		}
	}

	private function get_service_code_for_country($service, $country){
		$service_for_country = array(
			'CA' => array(
				'07' => '01', // for Canada serivce code of 'UPS Express(07)' is '01'
				'65' => '13', // Saver
			),
		);
		if( array_key_exists($country, $service_for_country) ){
			return isset($service_for_country[$country][$service]) ? $service_for_country[$country][$service] : $service;
		}
		return $service;
	}


	public function get_acccesspoint_rate_request(){
		//Getting accesspoint address details
		$access_request = '';
		$shipping_accesspoint = $this->wf_get_accesspoint_datas();
		if( !empty($shipping_accesspoint) && is_string($shipping_accesspoint) ){
			$decoded_accesspoint = json_decode($shipping_accesspoint);
			if(isset($decoded_accesspoint->AddressKeyFormat)){
					
				$accesspoint_addressline	= $decoded_accesspoint->AddressKeyFormat->AddressLine;
				$accesspoint_city			= (property_exists($decoded_accesspoint->AddressKeyFormat,'PoliticalDivision2')) ? $decoded_accesspoint->AddressKeyFormat->PoliticalDivision2 : '';
				$accesspoint_state			= (property_exists($decoded_accesspoint->AddressKeyFormat,'PoliticalDivision1')) ? $decoded_accesspoint->AddressKeyFormat->PoliticalDivision1:'';
				$accesspoint_postalcode		= $decoded_accesspoint->AddressKeyFormat->PostcodePrimaryLow;
				$accesspoint_country		= $decoded_accesspoint->AddressKeyFormat->CountryCode;
			
				$access_request .= "		<ShipmentIndicationType>" . "\n";
				$access_request .=	"			<Code>02</Code>" . "\n";
				$access_request .=	"		</ShipmentIndicationType>" . "\n";
				$access_request .= "		<AlternateDeliveryAddress>" . "\n";
				$access_request .= "			<Address>" . "\n";
				$access_request .= "				<AddressLine1>" . $accesspoint_addressline. "</AddressLine1>" . "\n";
				$access_request .= "				<City>" .$accesspoint_city ."</City>" . "\n";
				$access_request .= "				<StateProvinceCode>" . $accesspoint_state. "</StateProvinceCode>" . "\n";
				$access_request .= "				<PostalCode>" .$accesspoint_postalcode . "</PostalCode>" . "\n";
				$access_request .= "				<CountryCode>" . $accesspoint_country. "</CountryCode>" . "\n";
				$access_request .= "			</Address>" . "\n";
				$access_request .= "		</AlternateDeliveryAddress>" . "\n";
			}
		}
		
		return $access_request;
		
	}

	private function wf_get_postcode_city($country, $city, $postcode){
		$request_part = "";
		if( in_array( $country, $this->no_postcode_country_array ) && !empty( $city ) ) {
			$request_part = "<City>" . $city . "</City>" . "\n";
		}
		else if ( empty( $city ) ) {
			$request_part = "<PostalCode>" . $postcode . "</PostalCode>" . "\n";
		}
		else {
			$request_part = " <City>" . $city . "</City>" . "\n";
			$request_part .= "<PostalCode>" . $postcode. "</PostalCode>" . "\n";
		}
		
		return $request_part;
	}

	/**
	 * per_item_shipping function.
	 *
	 * @access private
	 * @param mixed $package
	 * @return mixed $requests - an array of XML strings
	 */
	private function per_item_shipping( $package, $params=array() ) {
		global $woocommerce;

		$requests = array();

		$ctr=0;
		$this->cod=sizeof($package['contents'])>1?false:$this->cod; // For multiple packages COD is turned off
		$this->destination = $package['destination'];
		foreach ( $package['contents'] as $item_id => $values ) {
			$values['data'] = $this->wf_load_product( $values['data'] );
			$ctr++;

			$additional_products = apply_filters( 'xa_ups_alter_products_list', array($values) );	// To support product addon

			foreach( $additional_products as $values ) {
				
				$skip_product = apply_filters('wf_shipping_skip_product',false, $values, $package['contents']);
				if($skip_product){
					continue;
				}

				if ( !( $values['quantity'] > 0 && $values['data']->needs_shipping() ) ) {
					$this->debug( sprintf( __( 'Product #%d is virtual. Skipping.', 'ups-woocommerce-shipping' ), $values['data']->id ) );
					continue;
				}

				if ( ! $values['data']->get_weight() ) {
					$this->debug( sprintf( __( 'Product #%d is missing weight. Aborting.', 'ups-woocommerce-shipping' ), $values['data']->id ), 'error' );
					return;
				}

				// get package weight
				$weight = wc_get_weight( $values['data']->get_weight(), $this->weight_unit );
				//$weight = apply_filters('wf_ups_filter_product_weight', $weight, $package, $item_id );

				// get package dimensions
				if ( $values['data']->length && $values['data']->height && $values['data']->width ) {

					$dimensions = array( number_format( wc_get_dimension( $values['data']->length, $this->dim_unit ), 2, '.', ''),
										 number_format( wc_get_dimension( $values['data']->height, $this->dim_unit ), 2, '.', ''),
										 number_format( wc_get_dimension( $values['data']->width, $this->dim_unit ), 2, '.', '') );
					sort( $dimensions );

				}

				// get quantity in cart
				$cart_item_qty = $values['quantity'];
				// get weight, or 1 if less than 1 lbs.
				// $_weight = ( floor( $weight ) < 1 ) ? 1 : $weight;
				
				$request['Package']	=	array(
					'PackagingType'	=>	array(
						'Code'			=>	'02',
						'Description'	=>	'Package/customer supplied'
					),
					'Description'	=>	'Rate',
				);
				
				if ( $values['data']->length && $values['data']->height && $values['data']->width ) {
					$request['Package']['Dimensions']	=	array(
						'UnitOfMeasurement'	=>	array(
							'Code'	=>	$this->dim_unit
						),
						'Length'	=>	$dimensions[2],
						'Width'		=>	$dimensions[1],
						'Height'	=>	$dimensions[0]
					);
				}
				if((isset($params['service_code'])&&$params['service_code']==92)||($this->service_code==92))// Surepost Less Than 1LBS
				{
					if($this->weight_unit=='LBS'){ // make sure weight in pounds
						$weight_ozs=$weight*16;
					}else{
						$weight_ozs=$weight*35.274; // From KG
					}
					$request['Package']['PackageWeight']	=	array(
						'UnitOfMeasurement'	=>	array(
							'Code'	=>	'OZS'
						),
						'Weight'	=>	$weight_ozs,
					);
				}else{
					$request['Package']['PackageWeight']	=	array(
						'UnitOfMeasurement'	=>	array(
							'Code'	=>	$this->weight_unit
						),
						'Weight'	=>	$weight,
					);
				}

				
				if( $this->insuredvalue || $this->cod ) {
					
					// InsuredValue
					if( $this->insuredvalue ) {
						
						$request['Package']['PackageServiceOptions']['InsuredValue']	=	array(
							'CurrencyCode'	=>	$this->get_ups_currency(),
							'MonetaryValue'	=>	(string) ( $this->wf_get_insurance_amount($values['data']) * $this->conversion_rate )
						);
					}
					
					//Code
					if($this->cod){
						if(!$this->is_european_country($this->destination['country'])){
							// European countries doen't suppot cod in package level. It is in shipment level
							//$cod_value=sizeof($package['contents'])>1?(string) ( $values['data']->get_price() * $cart_item_qty ):$this->cod_total; // For multi packages COD is turned off
							
							$cod_value=$this->cod_total;
							
							$request['Package']['PackageServiceOptions']['COD']	=	array(
								'CODCode'		=>	3,
								'CODFundsCode'	=>	0,
								'CODAmount'		=>	array(
									'CurrencyCode'	=>	$this->get_ups_currency(),
									'MonetaryValue'	=>	(string) ($cod_value * $this->conversion_rate),
								),
							);
						}
					}
				}
				
				//Adding all the items to the stored packages
				$request['Package']['items'] = array($values['data']->obj);
				
				// Direct Delivery option
				$directdeliveryonlyindicator = $this->get_individual_product_meta( array($values['data']), '_wf_ups_direct_delivery' );
				if( $directdeliveryonlyindicator == 'yes' ) {
					$request['Package']['DirectDeliveryOnlyIndicator'] = $directdeliveryonlyindicator;
				}
				
				// Delivery Confirmation
					if(isset($params['delivery_confirmation_applicable']) && $params['delivery_confirmation_applicable'] == true){
						$signature_option = $this->get_package_signature(array($values['data']));
						if(!empty($signature_option)&& ($signature_option > 0) ){
							$request['Package']['PackageServiceOptions']['DeliveryConfirmation']['DCISType']= $signature_option;
						}
					}
					$request['Package']['items'][] = $values['data'];
				for ( $i=0; $i < $cart_item_qty ; $i++)
					$requests[] = $request;
			}
		}

		return $requests;
	}

	/**
	 * box_shipping function.
	 *
	 * @access private
	 * @param mixed $package
	 * @return void
	 */
	private function box_shipping( $package, $params=array() ) {
		global $woocommerce;
		$pre_packed_contents = array();
		$requests = array();
		
		if ( ! class_exists( 'WF_Boxpack' ) ) {
			include_once 'class-wf-packing.php';
		}
		if ( ! class_exists( 'WF_Boxpack_Stack' ) ) {
			include_once 'class-wf-packing-stack.php';
		}
		
		volume_based:
		if(isset($this->mode) && $this->mode=='stack_first'){
			$boxpack = new WF_Boxpack_Stack();
		}
		else{
			$boxpack = new WF_Boxpack($this->mode);
		}

		// Add Standard UPS boxes
		if ( ! empty( $this->ups_packaging )  ) {
			foreach ( $this->ups_packaging as $key => $box_code ) {

				$box = $this->packaging[ $box_code ];
				$newbox = $boxpack->add_box( $box['length'], $box['width'], $box['height'] );
				$newbox->set_inner_dimensions( $box['length'], $box['width'], $box['height'] );
				
				if ( $box['weight'] )
					$newbox->set_max_weight( $box['weight'] );
				
				$newbox->set_id($box_code);

			}
		}

		// Define boxes
		if ( ! empty( $this->boxes ) ) {
			foreach ( $this->boxes as $box ) {
				
				$newbox = $boxpack->add_box( $box['outer_length'], $box['outer_width'], $box['outer_height'], $box['box_weight'] );				
				$newbox->set_inner_dimensions( $box['inner_length'], $box['inner_width'], $box['inner_height'] );

				if ( $box['max_weight'] )
					$newbox->set_max_weight( $box['max_weight'] );

			}
		}
		
		
		// Add items
		$ctr = 0;
		$this->destination = $package['destination'];
		if( isset($package['contents']) ) {
			foreach ( $package['contents'] as $item_id => $values ) {
				$values['data'] = $this->wf_load_product( $values['data'] );

				$ctr++;

				$additional_products = apply_filters( 'xa_ups_alter_products_list', array($values) );	// To support product addon

				foreach( $additional_products as $values ) {
					$skip_product = apply_filters('wf_shipping_skip_product',false, $values, $package['contents']);
					if($skip_product){
						continue;
					}

					if ( !( $values['quantity'] > 0 && $values['data']->needs_shipping() ) ) {
						$this->debug( sprintf( __( 'Product #%d is virtual. Skipping.', 'ups-woocommerce-shipping' ), $values['data']->id ) );
						continue;
					}

					$pre_packed = get_post_meta($values['data']->id , '_wf_pre_packed_product_var', 1);
					if( empty( $pre_packed ) ){
						$parent_product_id = wp_get_post_parent_id($values['data']->id);
						$pre_packed = get_post_meta( !empty($parent_product_id) ? $parent_product_id : $values['data']->id , '_wf_pre_packed_product', 1);
					}
					
					$pre_packed = apply_filters('wf_ups_is_pre_packed',$pre_packed,$values);

					if( !empty($pre_packed) && $pre_packed == 'yes' ){
						$pre_packed_contents[] = $values;
						$this->debug( sprintf( __( 'Pre Packed product. Skipping the product # %d', 'ups-woocommerce-shipping' ), $values['data']->id ) );
						continue;
					}

					if ( $values['data']->length && $values['data']->height && $values['data']->width && $values['data']->weight ) {

						$dimensions = array( $values['data']->length, $values['data']->height, $values['data']->width );

						for ( $i = 0; $i < $values['quantity']; $i ++ ) {
							$boxpack->add_item(
								number_format( wc_get_dimension( $dimensions[2], $this->dim_unit ), 2, '.', ''),
								number_format( wc_get_dimension( $dimensions[1], $this->dim_unit ), 2, '.', ''),
								number_format( wc_get_dimension( $dimensions[0], $this->dim_unit ), 2, '.', ''),
								number_format( wc_get_weight( $values['data']->get_weight(), $this->weight_unit ), 2, '.', ''),
								$this->wf_get_insurance_amount($values['data']),
								$values['data'] // Adding Item as meta
							);
						}

					} else {
						$this->debug( sprintf( __( 'UPS Parcel Packing Method is set to Pack into Boxes. Product #%d is missing dimensions. Aborting.', 'ups-woocommerce-shipping' ), $ctr ), 'error' );
						return;
					}
				}
			}
		}
		else {
			wf_admin_notice::add_notice('No package found. Your product may be missing weight/length/width/height');
		}
		// Pack it
		$boxpack->pack();
		
		// Get packages
		$box_packages = $boxpack->get_packages();
		if(isset($this->mode) && $this->mode=='stack_first')
		{ 
			foreach($box_packages as $key => $box_package)
			{  
				$box_volume=$box_package->length * $box_package->width * $box_package->height ;
				$box_used_volume=$box_package->volume;
				$box_used_volume_percentage=($box_used_volume * 100 )/$box_volume;
				if(isset($box_used_volume_percentage) && $box_used_volume_percentage<44)
				{   
					$this->mode='volume_based';
					$this->debug( '(FALLBACK) : Stack First Option changed to Volume Based' );
					goto volume_based;
					break;
				}
			}
		}

		$this->cod=$cod_value=sizeof($box_packages)>1?false:$this->cod;// For multi packages COD turned off
		$ctr=0;
		foreach ( $box_packages as $key => $box_package ) {
			$ctr++;
			
			$this->debug( "PACKAGE " . $ctr . " (" . $key . ")\n<pre>" . print_r( $box_package,true ) . "</pre>", 'error' );

			$weight	 = $box_package->weight;
			$dimensions = array( $box_package->length, $box_package->width, $box_package->height );
					
			// UPS packaging type select, If not present set as custom box
			if(!isset($box_package->id) || empty($box_package->id) || !array_key_exists($box_package->id,$this->packaging_select)){
				$box_package->id = '02';
			}
			
			sort( $dimensions );
			// get weight, or 1 if less than 1 lbs.
			// $_weight = ( floor( $weight ) < 1 ) ? 1 : $weight;
			
			$request['Package']	=	array(
				'PackagingType'	=>	array(
					'Code'				=>	$box_package->id,
					'Description'	=>	'Package/customer supplied'
				),
				'Description'	=> 'Rate',
				'Dimensions'	=>	array(
					'UnitOfMeasurement'	=>	array(
						'Code'	=>	$this->dim_unit,
					),
					'Length'	=>	$dimensions[2],
					'Width'		=>	$dimensions[1],
					'Height'	=>	$dimensions[0]
				)
			);
			
			
			// Getting packed items
			$packed_items	=	array();
			if(!empty($box_package->packed) && is_array($box_package->packed)){
				
				foreach( $box_package->packed as $item ) {
					$item_product	=	$item->meta;
					$packed_items[] = $item_product;					
				}
			}
			
			if((isset($params['service_code'])&&$params['service_code']==92)||($this->service_code==92))// Surepost Less Than 1LBS
			{
				if($this->weight_unit=='LBS'){ // make sure weight in pounds
					$weight_ozs=$weight*16;
				}else{
					$weight_ozs=$weight*35.274; // From KG
				}
				
				$request['Package']['PackageWeight']	=	array(
					'UnitOfMeasurement'	=>	array(
						'Code'	=>	'OZS'
					),
					'Weight'	=>	$weight_ozs
				);
				
			}else{
				$request['Package']['PackageWeight']	=	array(
					'UnitOfMeasurement'	=>	array(
						'Code'	=>	$this->weight_unit
					),
					'Weight'	=>	$weight
				);
			}
			
			if( $this->insuredvalue || $this->cod) {
				
				// InsuredValue
				if( $this->insuredvalue ) {
					$request['Package']['PackageServiceOptions']['InsuredValue']	=	array(
							'CurrencyCode'	=>	$this->get_ups_currency(),
							'MonetaryValue'	=>	(string)($box_package->value * $this->conversion_rate)
						);
				}
				//Code
				if($this->cod){
					if(!$this->is_european_country($this->destination['country'])){
						// European countries doen't suppot cod in package level. It is in shipment level
						//$cod_value=sizeof($box_packages)>1?$box_package->value:$this->cod_total; // For multiple packages cod not allowed
						$cod_value=$this->cod_total;
						
						$request['Package']['PackageServiceOptions']['COD']	=	array(
							'CODCode'		=>	3,
							'CODFundsCode'	=>	0,
							'CODAmount'		=>	array(
								'CurrencyCode'	=>	$this->get_ups_currency(),
								'MonetaryValue'	=>	(string) $cod_value * $this->conversion_rate
							),
						);
					}
				}				
			}
			
			//Adding all the items to the stored packages
			if( isset($box_package->unpacked) && $box_package->unpacked && isset($box_package->obj) ) {
				$request['Package']['items'] = array($box_package->obj);
			}
			else {
				$request['Package']['items'] = $packed_items;
			}
			// Direct Delivery option
			$directdeliveryonlyindicator = ! empty($packed_items) ? $this->get_individual_product_meta( $packed_items, '_wf_ups_direct_delivery' ) : $this->get_individual_product_meta( array($box_package), '_wf_ups_direct_delivery' ); // else part is for unpacked item
			if( $directdeliveryonlyindicator == 'yes' ) {
				$request['Package']['DirectDeliveryOnlyIndicator'] = $directdeliveryonlyindicator;
			}
			
			// Delivery Confirmation
			if(isset($params['delivery_confirmation_applicable']) && $params['delivery_confirmation_applicable'] == true){
				$signature_option = $this->get_package_signature($packed_items);			
				if(!empty($signature_option)&& ($signature_option > 0) ){
					$request['Package']['PackageServiceOptions']['DeliveryConfirmation']['DCISType']= $signature_option;
				}
			}
			
			$requests[] = $request;
		}
		//add pre packed item with the package
		if( !empty($pre_packed_contents) ){
			$prepacked_requests = $this->wf_ups_add_pre_packed_product( $pre_packed_contents );
			$requests = array_merge($requests, $prepacked_requests);
		}
		return $requests;
	}

	/**
	 * weight_based_shipping function.
	 *
	 * @access private
	 * @param mixed $package
	 * @return void
	 */
	private function weight_based_shipping($package, $params = array()) {
		global $woocommerce;
		$pre_packed_contents = array();
		if ( ! class_exists( 'WeightPack' ) ) {
			include_once 'weight_pack/class-wf-weight-packing.php';
		}
		$weight_pack=new WeightPack($this->weight_packing_process);
		$weight_pack->set_max_weight($this->box_max_weight);
		
		$package_total_weight = 0;
		$insured_value = 0;
		$requests = array();
		$ctr = 0;
		$this->cod = sizeof($package['contents']) > 1 ? false : $this->cod; // For multiple packages COD is turned off
		$this->destination = $package['destination'];
		foreach ($package['contents'] as $item_id => $values) {
			$values['data'] = $this->wf_load_product( $values['data'] );
			$ctr++;
			
			$additional_products = apply_filters( 'xa_ups_alter_products_list', array($values) );	// To support product addon
			foreach ( $additional_products as $values ) {
				$skip_product = apply_filters('wf_shipping_skip_product',false, $values, $package['contents']);
				if($skip_product){
					continue;
				}
				
				if (!($values['quantity'] > 0 && $values['data']->needs_shipping())) {
					$this->debug(sprintf(__('Product # %d is virtual. Skipping.', 'ups-woocommerce-shipping'), $values['data']->id));
					continue;
				}

				if (!$values['data']->get_weight()) {
					$this->debug(sprintf(__('Product # %d is missing weight. Aborting.', 'ups-woocommerce-shipping'), $values['data']->id), 'error');
					return;
				}
				
				$pre_packed = get_post_meta($values['data']->id , '_wf_pre_packed_product_var', 1);
				if( empty( $pre_packed ) ){
					$parent_product_id = wp_get_post_parent_id($values['data']->id);
					$pre_packed = get_post_meta( !empty($parent_product_id) ? $parent_product_id : $values['data']->id , '_wf_pre_packed_product', 1);
				}
				$pre_packed = apply_filters('wf_ups_is_pre_packed',$pre_packed,$values);
				if( !empty($pre_packed) && $pre_packed == 'yes' ){
					$pre_packed_contents[] = $values;
					$this->debug( sprintf( __( 'Pre Packed product. Skipping the product # %d', 'ups-woocommerce-shipping' ), $values['data']->id ) );
					continue;
				}
				$weight_pack->add_item(wc_get_weight( $values['data']->get_weight(), $this->weight_unit ), $values['data'], $values['quantity']);
			}
		}
		
		$pack	=	$weight_pack->pack_items();		
		$errors	=	$pack->get_errors();
		if( !empty($errors) ){
			//do nothing
			return;
		} else {
			$boxes		=	$pack->get_packed_boxes();
			$unpacked_items	=	$pack->get_unpacked_items();
			
			$insured_value			=	0;
			
			if(isset($this->order)){
				$order_total	=	$this->order->get_total();
			}
			
			
			$packages		=	array_merge( $boxes,	$unpacked_items ); // merge items if unpacked are allowed
			$package_count	=	sizeof($packages);
			
			// get all items to pass if item info in box is not distinguished
			$packable_items	=	$weight_pack->get_packable_items();
			$all_items		=	array();
			if(is_array($packable_items)){
				foreach($packable_items as $packable_item){
					$all_items[]	=	$packable_item['data'];
				}
			}
			
			foreach($packages as $package){
				$packed_products = array();
				
				$insured_value	=	0;
				if(!empty($package['items'])){
					foreach($package['items'] as $item){						
						$insured_value			=	$insured_value + $this->wf_get_insurance_amount($item);
					}
				}
				elseif( isset($order_total) && $package_count){
					$insured_value	=	$order_total/$package_count;
				}
				
				$packed_products	=	isset($package['items']) ? $package['items'] : $all_items;
				// Creating package request
				$package_total_weight	=	$package['weight'];
				
				$request['Package']	=	array(
					'PackagingType'	=>	array(
						'Code'			=>	'02',
						'Description'	=>	'Package/customer supplied',
					),
					'Description'	=>	'Rate',
				);
									
				if ((isset($params['service_code']) && $params['service_code'] == 92) || ($this->service_code == 92)) { // Surepost Less Than 1LBS
					if ($this->weight_unit == 'LBS') { // make sure weight in pounds
						$weight_ozs = $package_total_weight * 16;
					} else {
						$weight_ozs = $package_total_weight * 35.274; // From KG
					}
					
					$request['Package']['PackageWeight']	=	array(
						'UnitOfMeasurement'	=>	array(
							'Code'	=>	'OZS'
						),
						'Weight'	=>	$weight_ozs
					);
				} else {
					
					$request['Package']['PackageWeight']	=	array(
						'UnitOfMeasurement'	=>	array(
							'Code'	=>	$this->weight_unit
						),
						'Weight'	=>	$package_total_weight
					);
				}

				// InsuredValue

				if ($this->insuredvalue) {
					$request['Package']['PackageServiceOptions']['InsuredValue']	=	array(
						'CurrencyCode'	=>	$this->get_ups_currency(),
						'MonetaryValue'	=>	(string) ($insured_value * $this->conversion_rate),
					);
				}

				// Code

				if ($this->cod) {
					
					if(!$this->is_european_country($this->destination['country'])){
						// European countries doen't suppot cod in package level. It is in shipment level
						// $cod_value=sizeof($package['contents'])>1?(string) ( $values['data']->get_price() * $cart_item_qty ):$this->cod_total; // For multi packages COD is turned off

						$cod_value = $this->cod_total;
						
						$request['Package']['PackageServiceOptions']['COD']	=	array(
							'CODCode'			=>	3,
							'CODFundsCode'	=>	0,
							'CODAmount'	=>	array(
								'CurrencyCode'	=>	$this->get_ups_currency(),
								'MonetaryValue'	=> (string)($cod_value  * $this->conversion_rate),
							),
						);
					}
				}
				
				// Direct Delivery option
				$directdeliveryonlyindicator = $this->get_individual_product_meta( $packed_products, '_wf_ups_direct_delivery' );
				if( $directdeliveryonlyindicator == 'yes' ) {
					$request['Package']['DirectDeliveryOnlyIndicator'] = $directdeliveryonlyindicator;
				}
				
				// Delivery Confirmation
				if(isset($params['delivery_confirmation_applicable']) && $params['delivery_confirmation_applicable'] == true){
					$signature_option = $this->get_package_signature($packed_products);
					if(!empty($signature_option)&& ($signature_option > 0) ){
						$request['Package']['PackageServiceOptions']['DeliveryConfirmation']['DCISType']= $signature_option;
					}
				}
				$request['Package']['items'] = $package['items'];	    //Required for numofpieces in case of worldwidefreight
				$requests[] = $request;
			}
		}
		//add pre packed item with the package
		if( !empty($pre_packed_contents) ){
			$prepacked_requests = $this->wf_ups_add_pre_packed_product( $pre_packed_contents );
			$requests = array_merge($requests, $prepacked_requests);
		}		
		return $requests;
	}
	

	/**
	* Get UPS package weight converted for rate request for service 92
	* @param $package_request array UPS package request array
	* @return $service_code array UPS Package request
	*/
	public function convert_weight( $package_request, $service_code = null){
		if ( $service_code = 92 ) { // Surepost Less Than 1 LBS
			if ($this->weight_unit == 'LBS') { // make sure weight in pounds
				$weight_ozs = (float) $package_request['Package']['PackageWeight']['Weight'] * 16;
			} else {
				$weight_ozs = (float) $package_request['Package']['PackageWeight']['Weight'] * 35.274; // From KG
			}
			
			$package_request['Package']['PackageWeight']	=	array(
				'UnitOfMeasurement'	=>	array(
					'Code'	=>	'OZS'
				),
				'Weight'	=>	$weight_ozs
			);
		}
		return $package_request;
	}
	
	/**
	 * @param wf_product object
	 * @return int the Insurance amount for the product.
	 */
	public function wf_get_insurance_amount( $product ) {

		if( WC()->version > 2.7 ) {
			$parent_id = $product->get_parent_id();
			$product_id = ! empty($parent_id) ? $parent_id : $product->get_id();
		}
		else {
			$product_id = ($product instanceof WC_Product_Variable) ? $product->parent->id : $product->id ;
		}
		$insured_price = get_post_meta( $product_id, '_wf_ups_custom_declared_value', true );
		return ( ! empty( $insured_price ) ? $insured_price : $product->get_price() );
	}

	/**
	 * wf_get_api_rate_box_data function.
	 *
	 * @access public
	 * @return requests
	 */
	public function wf_get_api_rate_box_data( $package, $packing_method, $params = array()) {
		$this->packing_method	= $packing_method;
		$requests 				= $this->get_package_requests($package, $params);

		return $requests;
	}
	
	public function wf_set_cod_details($order){
		if($order->id){
			$this->cod=get_post_meta($order->id,'_wf_ups_cod',true);
			$this->cod_total=$order->get_total();
		}
	}
	
	public function wf_set_service_code($service_code){
		$this->service_code=$service_code;
	}
	
	/**
	 * Get product meta data for single occurance in request
	 * @param array|object $products array of wf_product object
	 * @param string $option
	 * @return mixed Return option value
	 */
	public function get_individual_product_meta( $products, $option = '' ) {
		$meta_result = '';
		foreach( $products as $product ) {
		    if( empty($meta_result) ) {
			    $meta_result = ! empty($product->obj) ? $product->obj->get_meta($option) : '';	// $product->obj actual product
		    }
		}
		
		return $meta_result;
	}
	
	public function get_package_signature($products){
		$higher_signature_option = 0;
		foreach( $products as $product ){
			$post_id = $product->id;
			$wf_dcis_type = get_post_meta($post_id, '_wf_ups_deliveryconfirmation', true);
			if( empty($wf_dcis_type) || !is_numeric ( $wf_dcis_type )){
				$wf_dcis_type = 0;
			}
			
			if( $wf_dcis_type > $higher_signature_option ){
				$higher_signature_option = $wf_dcis_type;
			}
		}
		return $higher_signature_option;
	}
	
	public function get_ups_currency(){
		return $this->currency_type;
	}
	
	public function wf_array_to_xml($tags,$full_xml=false){//$full_xml true will contain <?xml version
		$xml_str	=	'';
		foreach($tags as $tag_name	=> $tag){
			$out	=	'';
			try{
				$xml = new SimpleXMLElement('<'.$tag_name.'/>');
				
				if(is_array($tag)){
					$this->array2XML($xml,$tag);
					
					if(!$full_xml){
						$dom	=	dom_import_simplexml($xml);
						$out.=$dom->ownerDocument->saveXML($dom->ownerDocument->documentElement);
					}
					else{
						$out.=$xml->saveXML();
					}
				}
				else{
					$out.=$tag;
				}
				
			}catch(Exception $e){
				// Do nothing
			}
			$xml_str.=$out;
		}
		// echo preg_replace('<[\/]*item[0-9]>', '', $xml_str);
		return $xml_str;
	}
	
	public function array2XML($obj, $array)
	{
		foreach ($array as $key => $value)
		{
			if(is_numeric($key))
				$key = 'item' . $key;

			if (is_array($value))
			{
				if(!array_key_exists('multi_node', $value))
				{
					$node = $obj->addChild($key);
					$this->array2XML($node, $value);
				}else{
					unset($value['multi_node']);
					foreach($value as $node_value){
						$this->array2XML($obj, $node_value);
					}
				}					
			}
			else
			{
				$obj->addChild($key, htmlspecialchars($value));
			}
		}
	}
	
	public function is_european_country($country_code){
		if(!$country_code){
			return false;
		}
		$euro_countries	=	array(
			'RU','UA','FR','ES','SE','NO','DE','FI','PL','IT',
			'UK','RO','BY','EL','BG','IS','HU','PT','AZ','AT',
			'CZ','RS','IE','GE','LT','LV','HR','BA','SK','EE',
			'DK','CH','NL','MD','BE','AL','MK','TR','SI','ME',
			'XK','LU','MT','LI',
		);
		
		return in_array($country_code, $euro_countries)?true:false;
	}
	
	/*
	 * function to create package for pre packed items
	 *
	 * @ since 3.3.1
	 * @ access private
	 * @ params pre_packed_items
	 * @ return requests
	 */
	 private function wf_ups_add_pre_packed_product($pre_packed_items)
	 {
		 $requests = array();
		 foreach ( $pre_packed_items as $item_id => $values ) {
			if ( !( $values['quantity'] > 0 && $values['data']->needs_shipping() ) ) {
				$this->debug( sprintf( __( 'Product #%d is virtual. Skipping.', 'ups-woocommerce-shipping' ), $ctr ) );
				continue;
			}
			
			 if ( ! $values['data']->get_weight() ) {
				$this->debug(sprintf(__('Product #%d is missing weight. Aborting.', 'ups-woocommerce-shipping'), $ctr), 'error');
				return;
			}
			$weight = wc_get_weight( $values['data']->get_weight(), $this->weight_unit );
			
			if ( $values['data']->length && $values['data']->height && $values['data']->width && $values['data']->weight ) {
				$dimensions = array( $values['data']->length, $values['data']->height, $values['data']->width );
				sort( $dimensions );
			} else {
				$this->debug( sprintf( __( 'Product is missing dimensions. Aborting.', 'ups-woocommerce-shipping' )), 'error' );
				return;
			}
			
			$cart_item_qty = $values['quantity'];
		
			$request['Package']	=	array(
				'PackagingType'	=>	array(
					'Code'			=>	'02',
					'Description'	=>	'Package/customer supplied'
				),
				'Description'	=>	'Rate',
			);
			
			// Direct Delivery option
			$directdeliveryonlyindicator = $this->get_individual_product_meta( array($values['data']), '_wf_ups_direct_delivery' );
			if( $directdeliveryonlyindicator == 'yes' ) {
				$request['Package']['DirectDeliveryOnlyIndicator'] = $directdeliveryonlyindicator;
			}
			
			if ( $values['data']->length && $values['data']->height && $values['data']->width ) {
				$request['Package']['Dimensions']	=	array(
					'UnitOfMeasurement'	=>	array(
						'Code'	=>	$this->dim_unit
					),
					'Length'	=>	$dimensions[2],
					'Width'		=>	$dimensions[1],
					'Height'	=>	$dimensions[0]
				);
			}
			if((isset($params['service_code'])&&$params['service_code']==92)||($this->service_code==92))// Surepost Less Than 1LBS
			{
				if($this->weight_unit=='LBS'){ // make sure weight in pounds
					$weight_ozs=$weight*16;
				}else{
					$weight_ozs=$weight*35.274; // From KG
				}
				$request['Package']['PackageWeight']	=	array(
					'UnitOfMeasurement'	=>	array(
						'Code'	=>	'OZS'
					),
					'Weight'	=>	$weight_ozs,
				);
			}else{
				$request['Package']['PackageWeight']	=	array(
					'UnitOfMeasurement'	=>	array(
						'Code'	=>	$this->weight_unit
					),
					'Weight'	=>	$weight,
				);
			}

			
			if( $this->insuredvalue || $this->cod ) {
				
				// InsuredValue
				if( $this->insuredvalue ) {
					
					$request['Package']['PackageServiceOptions']['InsuredValue']	=	array(
						'CurrencyCode'	=>	$this->get_ups_currency(),
						'MonetaryValue'	=>	(string) ( $this->wf_get_insurance_amount($values['data']) * $this->conversion_rate )
					);
				}
				//Code
				if($this->cod){
					if(!$this->is_european_country($this->destination['country']['country'])){
						// European countries doen't suppot cod in package level. It is in shipment level
						//$cod_value=sizeof($package['contents'])>1?(string) ( $values['data']->get_price() * $cart_item_qty ):$this->cod_total; // For multi packages COD is turned off
						
						$cod_value=$this->cod_total;
						
						$request['Package']['PackageServiceOptions']['COD']	=	array(
							'CODCode'		=>	3,
							'CODFundsCode'	=>	0,
							'CODAmount'		=>	array(
								'CurrencyCode'	=>	$this->get_ups_currency(),
								'MonetaryValue'	=>	(string) ($cod_value * $this->conversion_rate),
							),
						);
					}
				}
			}
			
			// Delivery Confirmation
				if(isset($params['delivery_confirmation_applicable']) && $params['delivery_confirmation_applicable'] == true){
					$signature_option = $this->get_package_signature(array($values['data']));
					if(!empty($signature_option)&& ($signature_option > 0) ){
						$request['Package']['PackageServiceOptions']['DeliveryConfirmation']['DCISType']= $signature_option;
					}
				}
			//Setting the product object in package request	
			$request['Package']['items'] = array($values['data']->obj);

			for ( $i=0; $i < $cart_item_qty ; $i++)
				$requests[] = $request;

		 }
		 return $requests;
	}

	function wf_load_product( $product ){
		if( !class_exists('wf_product') ){
			include_once('class-wf-legacy.php');
		}
		if( !$product ){
			return false;
		}
		return ( WC()->version < '2.7.0' ) ? $product : new wf_product( $product );
	}
	
}

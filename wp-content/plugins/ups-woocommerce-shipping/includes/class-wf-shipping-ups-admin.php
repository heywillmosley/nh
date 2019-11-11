<?php
class WF_Shipping_UPS_Admin
{
	private $ups_services = array(
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
		"65" => "Worldwide Saver",
		
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
		
		"US48" => "Ground with Freight",
		
		);
	public $freight_services=array(
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
	private $freight_class_list=array(
		"50",
		"55",
		"60",
		"65",
		"70",
		"77.5",
		"85",
		"92.5",
		"100",
		"110",
		"125",
		"150",
		"175",
		"200",
		"250",
		"300",
		"400",
		"500",
		);
	private $freight_endpoint = 'https://wwwcie.ups.com/rest/FreightRate';
	private $ups_surepost_services = array(92, 93, 94, 95);
	private $email_notification_services = array('M2', 'M3', 'M4');
	
	/**
	 * For Delivery Confirmation below array of countries will be considered as domestic, Confirmed by UPS.
	 * US to US, CA to CA, PR to PR are considered as domestic, all other shipments are international.
	 * @var array 
	 */
	public $dc_domestic_countries = array( 'US', 'CA', 'PR');
	
	public function __construct(){
		$this->wf_init();

		//Print Shipping Label.
		if ( is_admin() ) {
			$this->init_bulk_printing();
			add_action( 'add_meta_boxes', array( $this, 'wf_add_ups_metabox' ), 15 );
			add_action('admin_notices',array(new wf_admin_notice, 'throw_notices'), 15); // New notice system
			//add a custome field in product page
			add_action( 'woocommerce_product_options_shipping', array($this,'wf_ups_custome_product_page')  );
			add_action( 'woocommerce_process_product_meta', array( $this, 'wf_ups_save_custome_product_fields' ) );

            // Add a custome field in product page variation level
			add_action( 'woocommerce_product_after_variable_attributes', array($this,'wf_variation_settings_fields'), 10, 3 );
            // Save a custome field in product page variation level
			add_action( 'woocommerce_save_product_variation', array($this,'wf_save_variation_settings_fields'), 10, 2 );
		}
		
		if ( isset( $_GET['wf_ups_shipment_confirm'] ) ) {
			add_action( 'init', array( $this, 'wf_ups_shipment_confirm' ), 15 );
		}
		else if ( isset( $_GET['wf_ups_shipment_accept'] ) ) {
			add_action( 'init', array( $this, 'wf_ups_shipment_accept' ), 15 );
		}
		else if ( isset( $_GET['wf_ups_print_label'] ) ) {
			add_action( 'init', array( $this, 'wf_ups_print_label' ), 15 );
		}
		else if( isset( $_GET['wf_ups_print_commercial_invoice'] ) ){
			add_action( 'init', array( $this, 'wf_ups_print_commercial_invoice' ), 15 );
		}
		else if ( isset( $_GET['wf_ups_void_shipment'] ) ) {
			add_action( 'init', array( $this, 'wf_ups_void_shipment' ), 15 );
		}
		else if ( isset( $_GET['wf_ups_generate_packages'] ) ) {
			add_action( 'init', array( $this, 'wf_ups_generate_packages' ), 15 );
		}
		elseif (isset($_GET['wf_ups_generate_packages_rates'])) {				// To get the rates in UPS admin side
			add_action('admin_init', array($this, 'wf_ups_generate_packages_rates'), 15 );
		}
		elseif ( isset( $_GET['xa_generate_return_label']) ) {			// Create Return label after generating the label
			add_action( 'admin_init', array( $this, 'xa_generate_return_label' ) );
		}
	}

	private function wf_init() {
		global $post;
		
		$shipmentconfirm_requests 			= array();
		// Load UPS Settings.
		$this->settings 					= get_option( 'woocommerce_'.WF_UPS_ID.'_settings', null ); 
		//Print Label Settings.
		$this->disble_ups_print_label		= isset( $this->settings['disble_ups_print_label'] ) ? $this->settings['disble_ups_print_label'] : '';
		$this->disble_shipment_tracking		= isset( $this->settings['disble_shipment_tracking'] ) ? $this->settings['disble_shipment_tracking'] : 'TrueForCustomer';
		$this->show_label_in_browser	    = isset( $this->settings['show_label_in_browser'] ) ? $this->settings['show_label_in_browser'] : 'no';
		$this->box_max_weight			=	isset($this->settings[ 'box_max_weight']) ?  $this->settings[ 'box_max_weight'] : '';
		$this->weight_packing_process	=	isset($this->settings[ 'weight_packing_process']) ? $this->settings[ 'weight_packing_process'] : '';
		$this->enable_freight 			= isset( $this->settings['enable_freight'] ) && $this->settings['enable_freight'] == 'yes' ? true : false;
		// $this->ground_freight 			= isset( $this->settings['ground_freight'] ) && $this->settings['ground_freight'] == 'yes' ? true : false;
		$this->email_notification   	= isset($this->settings['email_notification'])?$this->settings['email_notification']:array();

		$this->xa_show_all 			= isset( $this->settings['xa_show_all'] ) && $this->settings['xa_show_all'] == 'yes' ? true : false;
		
		// Units
		$this->units			= isset( $this->settings['units'] ) ? $this->settings['units'] : 'imperial';
		
		//Advanced Settings
		$this->ssl_verify			= isset( $this->settings['ssl_verify'] ) ? $this->settings['ssl_verify'] : false;
		$this->enable_latin_encoding = isset( $this->settings['latin_encoding'] ) ? $this->settings['latin_encoding'] == 'yes' : false;

		$this->debug      	= isset( $this->settings['debug'] ) && $this->settings['debug'] == 'yes' ? true : false;
		
		if ( $this->units == 'metric' ) {
			$this->weight_unit = 'KGS';
			$this->dim_unit    = 'CM';
		} else {
			$this->weight_unit = 'LBS';
			$this->dim_unit    = 'IN';
		}
		if ( ! class_exists( 'WF_Shipping_UPS' ) ) {
			include_once 'class-wf-shipping-ups.php';
		}
		
		$this->tin_number            =    isset($this->settings[ 'tin_number']) ?  $this->settings[ 'tin_number'] : '';
		$this->reason_export         =    isset($this->settings[ 'reason_export']) ?  $this->settings[ 'reason_export'] : '';

		
		$this->countries_with_statecodes	=	array('US','CA','IE');
		
		$this->set_origin_country_state();

		$this->wcsups	=	new WF_Shipping_UPS();
		include_once( 'class-wf-shipping-ups-tracking.php' );
		
		add_filter('wf_ups_filter_label_packages',array($this,'manual_packages'),10,2);		
	}

	private function set_origin_country_state(){
		$ups_origin_country_state 		= isset( $this->settings['origin_country_state'] ) ? $this->settings['origin_country_state'] : '';
		if ( strstr( $ups_origin_country_state, ':' ) ) :
			// WF: Following strict php standards.
			$origin_country_state_array 	= explode(':',$ups_origin_country_state);
			$this->origin_country 				= current($origin_country_state_array);
			$origin_country_state_array 	= explode(':',$ups_origin_country_state);
			$origin_state   				= end($origin_country_state_array);
		else :
			$this->origin_country = $ups_origin_country_state;
		$origin_state   = '';
		endif;

		$this->origin_state = ( isset( $origin_state ) && !empty( $origin_state ) ) ? $origin_state : $this->settings['origin_custom_state'];
	}

	function wf_add_ups_metabox(){
		global $post;
		if ( in_array( $post->post_type, array('shop_order') ) ) {
			if( $this->disble_ups_print_label == 'yes' ) {
				return;
			}

			if ( !$post ) return;

			$order = $this->wf_load_order( $post->ID );
			if ( !$order ) return; 
			
			add_meta_box( 'CyDUPS_metabox', __( 'UPS Shipment Label', 'ups-woocommerce-shipping' ), array( $this, 'wf_ups_metabox_content' ), 'shop_order', 'advanced', 'default' );
		}
	}

	function wf_ups_metabox_content(){
		global $post;
		$shipmentId = '';
		
		$order 								= $this->wf_load_order( $post->ID );
		$shipping_service_data				= $this->wf_get_shipping_service_data( $order );
		$default_service_type 				= $shipping_service_data['shipping_service'];

		$created_shipments_details_array 	= get_post_meta( $post->ID, 'ups_created_shipments_details_array', true );
		if( empty( $created_shipments_details_array ) ) {		
			
			
			$download_url = admin_url( '/?wf_ups_shipment_confirm='.base64_encode( $shipmentId.'|'.$post->ID ) );
			$stored_packages	=	get_post_meta( $post->ID, '_wf_ups_stored_packages', true );
			if(empty($stored_packages)	&&	!is_array($stored_packages)){
				echo '<strong>'.__( 'Step 1: Auto generate packages.', 'ups-woocommerce-shipping' ).'</strong></br>';
				?>
				<a class="button button-primary tips ups_generate_packages" href="<?php echo admin_url( '/?wf_ups_generate_packages='.base64_encode( $shipmentId.'|'.$post->ID ) ); ?>" data-tip="<?php _e( 'Generate Packages', 'ups-woocommerce-shipping' ); ?>"><?php _e( 'Generate Packages', 'ups-woocommerce-shipping' ); ?></a><hr style="border-color:#0074a2">
				<?php
			}else{
				echo '<strong>'.__( 'Step 2: Initiate your shipment.', 'ups-woocommerce-shipping' ).'</strong></br>';

				echo '<ul>';
				
				/*if($this->ground_freight){
					echo '<li><label for="ups_gfp_shipment"><input type="checkbox" style="" id="ups_gfp_shipment" name="ups_gfp_shipment" class="">' . __('GFP Shipment', 'ups-woocommerce-shipping') . '</label><img class="help_tip" style="float:none;" data-tip="'.__( 'Ground reight Pricing (GFP)', 'ups-woocommerce-shipping' ).'" src="'.WC()->plugin_url().'/assets/images/help.png" height="16" width="16" /></li>';
				}*/
				if($this->enable_freight)   // if freight is enabled
				{
					echo '<h4>'.__( 'UPS Freight Options' , 'ups-woocommerce-shipping').': </h4>';
					echo '<li><label for="FreightPackagingType">Freight Packaging Type : </label>';
					echo '<select id="FreightPackagingType" name="FreightPackagingType" class="">';
					foreach($this->freight_package_type_code_list as $pcode=>$pname)
					{
						echo "<option value='$pcode' > $pname </option>" ;
					}
					echo '</select>';
					echo '</t> Freight Class : <select id="FreightClass" name="FreightClass" class="">';
					foreach($this->freight_class_list as $fcode)
					{
						echo "<option value='$fcode' > $fcode </option>" ;
					}
					echo '</select></li>';
					echo '<li><label for="HolidayPickupIndicator"><input type="checkbox" style="" id="HolidayPickupIndicator" name="HolidayPickupIndicator" class="">' . __('Request Holiday Pickup', 'ups-woocommerce-shipping') . '</label><img class="help_tip" style="float:none;" data-tip="This indicates that the shipment requires a holiday pickup." src="'.WC()->plugin_url().'/assets/images/help.png" height="16" width="16" /></li>';
					echo '<li><label for="InsidePickupIndicator"><input type="checkbox" style="" id="InsidePickupIndicator" name="InsidePickupIndicator" class="">' . __('Request Inside Pickup', 'ups-woocommerce-shipping') . '</label><img class="help_tip" style="float:none;" data-tip="This indicates that the shipment requires an inside pickup." src="'.WC()->plugin_url().'/assets/images/help.png" height="16" width="16" /></li>';
					echo '<li><label for="ResidentialPickupIndicator"><input type="checkbox" style="" id="ResidentialPickupIndicator" name="ResidentialPickupIndicator" class="">' . __('Request Residential Pickup', 'ups-woocommerce-shipping') . '</label><img class="help_tip" style="float:none;" data-tip="This indicates that the shipment requires a residential pickup" src="'.WC()->plugin_url().'/assets/images/help.png" height="16" width="16" /></li>';
					echo '<li><label for="WeekendPickupIndicator"><input type="checkbox" style="" id="WeekendPickupIndicator" name="WeekendPickupIndicator" class="">' . __('Request Weekend Pickup', 'ups-woocommerce-shipping') . '</label><img class="help_tip" style="float:none;" data-tip="This indicates that the shipment requires a weekend pickup." src="'.WC()->plugin_url().'/assets/images/help.png" height="16" width="16" /></li>';
					echo '<li><label for="LiftGateRequiredIndicator"><input type="checkbox" style="" id="LiftGateRequiredIndicator" name="LiftGateRequiredIndicator" class="">' . __('Request Lift Gate for Pickup', 'ups-woocommerce-shipping') . '</label><img class="help_tip" style="float:none;" data-tip="This indicates that the shipment requires a lift gate." src="'.WC()->plugin_url().'/assets/images/help.png" height="16" width="16" /></li>';
					echo '<li><label for="LimitedAccessPickupIndicator"><input type="checkbox" style="" id="LimitedAccessPickupIndicator" name="LimitedAccessPickupIndicator" class="">' . __('Notify UPS For Limited Access Pickup', 'ups-woocommerce-shipping') . '</label><img class="help_tip" style="float:none;" data-tip="This indicates that there is limited access for pickups." src="'.WC()->plugin_url().'/assets/images/help.png" height="16" width="16" /></li>';
					echo '<li><label for="PickupInstructions">' . __('Pickup Instruction', 'ups-woocommerce-shipping') . '</label><img class="help_tip" style="float:none;" data-tip="Here you can write some instruction regarding your pickup to UPS" src="'.WC()->plugin_url().'/assets/images/help.png" height="16" width="16" /></li>';
					echo '<li><input style="width:100%;" type="text" style="" id="PickupInstructions" name="PickupInstructions" class=""></li>';

				}				
				echo '<li>';
				echo '<h4>'.__( 'Package(s)' , 'ups-woocommerce-shipping').': </h4>';
				echo '<table id="wf_ups_package_list" class="wf-shipment-package-table">';					
				echo '<tr>';
				echo '<th>'.__('Wt.', 'ups-woocommerce-shipping').'</br>('.$this->weight_unit.')</th>';
				echo '<th>'.__('L', 'ups-woocommerce-shipping').'</br>('.$this->dim_unit.')</th>';
				echo '<th>'.__('W', 'ups-woocommerce-shipping').'</br>('.$this->dim_unit.')</th>';
				echo '<th>'.__('H', 'ups-woocommerce-shipping').'</br>('.$this->dim_unit.')</th>';
				echo '<th>'.__('Insur.', 'ups-woocommerce-shipping').'</th>';

				echo '<th>';
				echo __('Service', 'ups-woocommerce-shipping');
				echo '<img class="help_tip" style="float:none;" data-tip="'.__( 'Contact UPS for more info on this services.', 'ups-woocommerce-shipping' ).'" src="'.WC()->plugin_url().'/assets/images/help.png" height="16" width="16" />';
				echo '</th>';
				echo '<th>&nbsp;</th>';
				echo '</tr>';
				foreach($stored_packages as $stored_package_key	=>	$stored_package){
					$dimensions	=	$this->get_dimension_from_package($stored_package);
					if(is_array($dimensions)){
						?>
						<tr>
							<td><input type="text" id="ups_manual_weight" name="ups_manual_weight[]" size="4" value="<?php echo $dimensions['Weight'];?>" /></td>     
							<td><input type="text" id="ups_manual_length" name="ups_manual_length[]" size="4" value="<?php echo $dimensions['Length'];?>" /></td>
							<td><input type="text" id="ups_manual_width" name="ups_manual_width[]" size="4" value="<?php echo $dimensions['Width'];?>" /></td>
							<td><input type="text" id="ups_manual_height" name="ups_manual_height[]" size="4" value="<?php echo $dimensions['Height'];?>" /></td>
							<td><input type="text" id="ups_manual_insurance" name="ups_manual_insurance[]" size="4" value="<?php echo $dimensions['InsuredValue'];?>" /></td>
							<td>
								<select class="select ups_manual_service" id="ups_manual_service" name="ups_manual_service[]">
									<?php 
											 
										if($this->xa_show_all==true)
										{
											foreach($this->ups_services as $service_code => $service_name){
												echo '<option value="'.$service_code.'" ' . selected($default_service_type, $service_code) . ' >'.$service_name.'</option>';
											}?>
											<?php if($this->enable_freight==true)  foreach($this->freight_services as $service_code => $service_name){
												echo '<option value="'.$service_code.'" ' . selected($default_service_type, $service_code) . ' >'.$service_name.'</option>';
											}
										}
										else{
											foreach($this->settings['services'] as $service_code => $sdata){
												if($sdata['enabled']==1){
													$service_name= (isset($this->ups_services[$service_code])) ? $this->ups_services[$service_code] : $this->freight_services[$service_code];
												  	echo '<option value="'.$service_code.'" ' . selected($default_service_type, $service_code) . ' >'.$service_name.'</option>';

												}
											}
										}?>									
									
								</select>
							</td>
							<td>&nbsp;</td>
						</tr>
						<?php
					}
				}
				echo '</table>';
                                echo '<div id="ret_s" style="display:none">';
                                echo '<h4>'.__( 'Return Package' , 'ups-woocommerce-shipping').': </h4>';
                                echo '<table id="rt_wf_ups_package_list" class="wf-shipment-package-table">';                                   
                                echo '<tr>';
                                echo '<th>'.__('Wt.', 'ups-woocommerce-shipping').'</br>('.$this->weight_unit.')</th>';
                                echo '<th>'.__('L', 'ups-woocommerce-shipping').'</br>('.$this->dim_unit.')</th>';
                                echo '<th>'.__('W', 'ups-woocommerce-shipping').'</br>('.$this->dim_unit.')</th>';
                                echo '<th>'.__('H', 'ups-woocommerce-shipping').'</br>('.$this->dim_unit.')</th>';
                                echo '<th>'.__('Insur.', 'ups-woocommerce-shipping').'</th>';
                                echo '<th>';
                                echo __('Service', 'ups-woocommerce-shipping');
                                echo '<img class="help_tip" style="float:none;" data-tip="'.__( 'Contact UPS for more info on this services.', 'ups-woocommerce-shipping' ).'" src="'.WC()->plugin_url().'/assets/images/help.png" height="16" width="16" />';
                                echo '</th>';
                                echo '<th>&nbsp;</th>';
                                echo '</tr>';
 
                                if(is_array($dimensions)){
                                                ?>
                                                <tr>
                                                        <td><input type="text" id="rt_ups_manual_weight" name="rt_ups_manual_weight[]" size="4" value="<?php echo $dimensions['Weight'];?>" /></td>     
                                                        <td><input type="text" id="rt_ups_manual_length" name="rt_ups_manual_length[]" size="4" value="<?php echo $dimensions['Length'];?>" /></td>
                                                        <td><input type="text" id="rt_ups_manual_width" name="rt_ups_manual_width[]" size="4" value="<?php echo $dimensions['Width'];?>" /></td>
                                                        <td><input type="text" id="rt_ups_manual_height" name="rt_ups_manual_height[]" size="4" value="<?php echo $dimensions['Height'];?>" /></td>
                                                        <td><input type="text" id="rt_ups_manual_insurance" name="rt_ups_manual_insurance[]" size="4" value="<?php echo $dimensions['InsuredValue'];?>" /></td>
                                                        <td>
                                                                <select class="select rt_ups_manual_service" id="rt_ups_manual_service" name="rt_ups_manual_service[]">
                                                                        <?php foreach($this->ups_services as $service_code => $service_name){
                                                                                echo '<option value="'.$service_code.'" ' . selected($default_service_type, $service_code) . ' >'.$service_name.'</option>';
                                                                        }?>
                                                                        <?php if($this->enable_freight==true)  foreach($this->freight_services as $service_code => $service_name){
                                                                                echo '<option value="'.$service_code.'" ' . selected($default_service_type, $service_code) . ' >'.$service_name.'</option>';
                                                                        }?>                                                                     
                                                                        
                                                                </select>
                                                        </td>
                                                        <td>&nbsp;</td>
                                                </tr>
                                                <?php
                                        } 
                                echo '</table>';
                                echo '</div>';

				echo '<a class="wf-action-button wf-add-button" style="font-size: 12px;" id="wf_ups_add_package">Add Package</a>';
				?>
					<a class="button tips ups_generate_packages" href="<?php echo admin_url( '/?wf_ups_generate_packages='.base64_encode( $shipmentId.'|'.$post->ID ) ); ?>" data-tip="<?php _e( 'Generate Packages', 'ups-woocommerce-shipping' ); ?>"><?php _e( 'Generate Packages', 'ups-woocommerce-shipping' ); ?></a>
				<?php
				echo '</li>';
				?>

				<script type="text/javascript">
					jQuery(document).ready(function(){
                                                jQuery('input[type="checkbox"]').click(function()
                                                { 
                                                        if(jQuery('#ups_return').is(':checked'))
                                                        {
                                                                jQuery('#ret_s').show();
                                                                 
                                                        }
                                                        else
                                                        {
                                                                jQuery('#ret_s').hide();
                                                        }
                                                });

						jQuery('#wf_ups_add_package').on("click", function(){
							var new_row = '<tr>';
							new_row 	+= '<td><input type="text" id="ups_manual_weight" name="ups_manual_weight[]" size="2" value="0"></td>';
							new_row 	+= '<td><input type="text" id="ups_manual_length" name="ups_manual_length[]" size="2" value="0"></td>';								
							new_row 	+= '<td><input type="text" id="ups_manual_width" name="ups_manual_width[]" size="2" value="0"></td>';
							new_row 	+= '<td><input type="text" id="ups_manual_height" name="ups_manual_height[]" size="2" value="0"></td>';
							new_row 	+= '<td><input type="text" id="ups_manual_insurance" name="ups_manual_insurance[]" size="2" value="0"></td>';
							new_row 	+= '<td>';
							new_row 	+= '<select class="select ups_manual_service" id="ups_manual_service">';
							

							<?php 
							if($this->xa_show_all==true){
								foreach($this->ups_services as $service_code => $service_name){?>
									new_row 	+= '<option value="<?php echo $service_code;?>"><?php echo $service_name;?></option>';
								<?php 
								} 
								if($this->enable_freight==true) foreach($this->freight_services as $service_code => $service_name){?>
									new_row 	+= '<option value="<?php echo $service_code;?>"><?php echo $service_name;?></option>';
									<?php }
							}
							else{
								foreach($this->settings['services'] as $service_code => $sdata){
									if($sdata['enabled']==1){
										$service_name=isset($this->ups_services[$service_code]) ? $this->ups_services[$service_code] : $this->freight_services[$service_code];
									?>
									new_row +='<option value="<?php echo $service_code;?>"><?php echo $service_name;?></option>';
									<?php
									}	
								}	
							}
							?>
									new_row 	+= '</select>';
									new_row 	+= '</td>';
									new_row 	+= '<td><a class="wf_ups_package_line_remove">&#x26D4;</a></td>';
									new_row 	+= '</tr>';

									jQuery('#wf_ups_package_list tr:last').after(new_row);
                                                        var rt_new_row = '<tr>';
                                                        rt_new_row      += '<td><input type="text" id="rt_ups_manual_weight" name="rt_ups_manual_weight[]" size="2" value="0"></td>';
                                                        rt_new_row      += '<td><input type="text" id="rt_ups_manual_length" name="rt_ups_manual_length[]" size="2" value="0"></td>';                                                           
                                                        rt_new_row      += '<td><input type="text" id="rt_ups_manual_width" name="rt_ups_manual_width[]" size="2" value="0"></td>';
                                                        rt_new_row      += '<td><input type="text" id="rt_ups_manual_height" name="rt_ups_manual_height[]" size="2" value="0"></td>';
                                                        rt_new_row      += '<td><input type="text" id="rt_ups_manual_insurance" name="rt_ups_manual_insurance[]" size="2" value="0"></td>';
                                                        rt_new_row      += '<td>';
                                                        rt_new_row      += '<select class="select rt_ups_manual_service" id="rt_ups_manual_service">';
                                                        <?php foreach($this->ups_services as $service_code => $service_name){?>
                                                                rt_new_row      += '<option value="<?php echo $service_code;?>"><?php echo $service_name;?></option>';
                                                                <?php }?>
                                                                <?php if($this->enable_freight==true) foreach($this->freight_services as $service_code => $service_name){?>
                                                                        rt_new_row      += '<option value="<?php echo $service_code;?>"><?php echo $service_name;?></option>';
                                                                        <?php }?>
                                                                        rt_new_row      += '</select>';
                                                                        rt_new_row      += '</td>';
                                                                        rt_new_row      += '<td><a class="wf_ups_package_line_remove">&#x26D4;</a></td>';
                                                                        rt_new_row      += '</tr>';
                                                                         jQuery('#rt_wf_ups_package_list tr:last').after(rt_new_row);
                                                                        
								});
						
						jQuery(document).on('click', '.wf_ups_package_line_remove', function(){
							jQuery(this).closest('tr').remove();
						});
						
						// To create Shipment
						jQuery("a.ups_create_shipment").on("click", function() {
							var manual_weight_arr 	= 	jQuery("input[id='ups_manual_weight']").map(function(){return jQuery(this).val();}).get();
							var manual_weight 		=	JSON.stringify(manual_weight_arr);

							var manual_height_arr 	= 	jQuery("input[id='ups_manual_height']").map(function(){return jQuery(this).val();}).get();
							var manual_height 		=	JSON.stringify(manual_height_arr);

							var manual_width_arr 	= 	jQuery("input[id='ups_manual_width']").map(function(){return jQuery(this).val();}).get();
							var manual_width 		=	JSON.stringify(manual_width_arr);

							var manual_length_arr 	= 	jQuery("input[id='ups_manual_length']").map(function(){return jQuery(this).val();}).get();
							var manual_length 		=	JSON.stringify(manual_length_arr);

							var manual_insurance_arr 	= 	jQuery("input[id='ups_manual_insurance']").map(function(){return jQuery(this).val();}).get();
							var manual_insurance 		=	JSON.stringify(manual_insurance_arr);

							var manual_service_arr	=	[];
							jQuery('.ups_manual_service').each(function(){
								manual_service_arr.push(jQuery(this).val());
							});
							var manual_service 		=	JSON.stringify(manual_service_arr);
							var rt_manual_weight_arr        =       jQuery("input[id='rt_ups_manual_weight']").map(function(){return jQuery(this).val();}).get();
							var rt_manual_weight            =       JSON.stringify(rt_manual_weight_arr);

							var rt_manual_height_arr        =       jQuery("input[id='rt_ups_manual_height']").map(function(){return jQuery(this).val();}).get();
							var rt_manual_height            =       JSON.stringify(rt_manual_height_arr);

							var rt_manual_width_arr         =       jQuery("input[id='rt_ups_manual_width']").map(function(){return jQuery(this).val();}).get();
							var rt_manual_width             =       JSON.stringify(rt_manual_width_arr);

							var rt_manual_length_arr        =       jQuery("input[id='rt_ups_manual_length']").map(function(){return jQuery(this).val();}).get();
							var rt_manual_length            =       JSON.stringify(rt_manual_length_arr);

							var rt_manual_insurance_arr     =       jQuery("input[id='rt_ups_manual_insurance']").map(function(){return jQuery(this).val();}).get();
							var rt_manual_insurance                 =       JSON.stringify(rt_manual_insurance_arr);

							var rt_manual_service_arr       =       [];
							jQuery('.rt_ups_manual_service').each(function(){
								rt_manual_service_arr.push(jQuery(this).val());
							});
							var rt_manual_service           =       JSON.stringify(rt_manual_service_arr);

						     if(jQuery('#ups_return').is(':checked'))
						      {

							location.href = this.href + '&weight=' + manual_weight +
							'&length=' + manual_length
							+ '&width=' + manual_width
							+ '&height=' + manual_height
							+ '&insurance=' + manual_insurance
							+ '&service=' + manual_service
							+ '&cod=' + jQuery('#ups_cod').is(':checked')
							+ '&sat_delivery=' + jQuery('#ups_sat_delivery').is(':checked')
							+ '&rt_weight=' + rt_manual_weight
							+ '&rt_length=' + rt_manual_length
							+ '&rt_width=' + rt_manual_width
							+ '&rt_height=' + rt_manual_height
							+ '&rt_insurance=' + rt_manual_insurance
							+ '&rt_service=' + rt_manual_service
							+ '&is_gfp_shipment=' + jQuery('#ups_gfp_shipment').is(':checked')
							+ '&is_return_label=' + jQuery('#ups_return').is(':checked')
							+ '&HolidayPickupIndicator=' + jQuery('#HolidayPickupIndicator').is(':checked')
							+ '&InsidePickupIndicator=' + jQuery('#InsidePickupIndicator').is(':checked')
							+ '&ResidentialPickupIndicator=' + jQuery('#ResidentialPickupIndicator').is(':checked')
							+ '&WeekendPickupIndicator=' + jQuery('#WeekendPickupIndicator').is(':checked')
							+ '&LiftGateRequiredIndicator=' + jQuery('#LiftGateRequiredIndicator').is(':checked')
							+ '&LimitedAccessPickupIndicator=' + jQuery('#LimitedAccessPickupIndicator').is(':checked')
							+ '&PickupInstructions=' + jQuery('#PickupInstructions').val()
							+ '&FreightPackagingType=' + jQuery('#FreightPackagingType').val()
							+ '&FreightClass=' + jQuery('#FreightClass').val();
						       }
							 else
							 {

							     location.href = this.href + '&weight=' + manual_weight +
							'&length=' + manual_length
							+ '&width=' + manual_width
							+ '&height=' + manual_height
							+ '&insurance=' + manual_insurance
							+ '&service=' + manual_service
							+ '&cod=' + jQuery('#ups_cod').is(':checked')
							+ '&sat_delivery=' + jQuery('#ups_sat_delivery').is(':checked')
							+ '&is_gfp_shipment=' + jQuery('#ups_gfp_shipment').is(':checked')
							+ '&is_return_label=' + jQuery('#ups_return').is(':checked')
							+ '&HolidayPickupIndicator=' + jQuery('#HolidayPickupIndicator').is(':checked')
							+ '&InsidePickupIndicator=' + jQuery('#InsidePickupIndicator').is(':checked')
							+ '&ResidentialPickupIndicator=' + jQuery('#ResidentialPickupIndicator').is(':checked')
							+ '&WeekendPickupIndicator=' + jQuery('#WeekendPickupIndicator').is(':checked')
							+ '&LiftGateRequiredIndicator=' + jQuery('#LiftGateRequiredIndicator').is(':checked')
							+ '&LimitedAccessPickupIndicator=' + jQuery('#LimitedAccessPickupIndicator').is(':checked')
							+ '&PickupInstructions=' + jQuery('#PickupInstructions').val()
							+ '&FreightPackagingType=' + jQuery('#FreightPackagingType').val()
							+ '&FreightClass=' + jQuery('#FreightClass').val();
							 }

							return false;
						});
					});
					
				</script>
				<?php
				
				// Rates on order page
				$generate_packages_rates = get_post_meta( $_GET['post'], 'wf_ups_generate_packages_rates_response', true );
				echo '<li><table id="wf_ups_service_select" class="wf-shipment-package-table" style="margin-bottom: 5px;margin-top: 15px;box-shadow:.5px .5px 5px lightgrey;">';
					echo '<tr>';
						echo '<th>Select Service</th>';
						echo '<th style="text-align:left;padding:5px; font-size:13px;">'.__('Service Name', 'ups-woocommerce-shipping').'</th>';
						echo '<th style="text-align:left;font-size:13px;">'.__('Cost (', 'ups-woocommerce-shipping').get_woocommerce_currency_symbol().__(')', 'ups-woocommerce-shipping').' </th>';
					echo '</tr>';
					
					echo '<tr>';
						echo "<td style = 'padding-bottom: 10px; padding-left: 15px; '><input name='wf_ups_service_choosing_radio' id='wf_ups_service_choosing_radio' value='wf_ups_individual_service' type='radio' checked='true'></td>";
						echo "<td colspan = '3' style= 'padding-bottom: 10px; text-align:left;'><b>Choose Shipping Methods</b> - Select this option to choose UPS services for each package (Shipping rates will be applied accordingly).</td>";
					echo "</tr>";
					
					if( ! empty($generate_packages_rates) ) {
						$wp_date_format = get_option('date_format');
						foreach( $generate_packages_rates as $key => $rates ) {
							$ups_service = explode( ':', $rates['id']);
							echo '<tr style="padding:10px;">';
								echo "<td style = 'padding-left: 15px;'><input name='wf_ups_service_choosing_radio' id='wf_ups_service_choosing_radio' value='".end($ups_service)."' type='radio' ></td>";
								echo "<td>".$rates['label']."</td>";
								echo "<td>".( ! empty($this->settings['conversion_rate']) ? $this->settings['conversion_rate'] * $rates['cost'] : $rates['cost'])."</td>";
							echo "</tr>";
						}
					}

				echo '</table></li>';
				//End of Rates on order page
				?>
				<a style="margin: 4px" class="button tips wf_ups_generate_packages_rates button-secondary" href="<?php echo admin_url( '/post.php?wf_ups_generate_packages_rates='.base64_encode($post->ID) ); ?>" data-tip="<?php _e( 'Calculate the shipping rates for UPS services.', 'wf-shipping-ups' ); ?>"><?php _e( 'Calculate Shipping Cost', 'wf-shipping-ups' ); ?></a>
				<?php				
					echo '<li><label for="ups_cod"><input type="checkbox" style="" id="ups_cod" name="ups_cod" class="">' . __('Collect On Delivery', 'ups-woocommerce-shipping') . '</label><img class="help_tip" style="float:none;" data-tip="'.__( 'Collect On Delivery would be applicable only for single package which may contain single or multiple product(s).', 'ups-woocommerce-shipping' ).'" src="'.WC()->plugin_url().'/assets/images/help.png" height="16" width="16" /></li>';
					echo '<li><label for="ups_return"><input type="checkbox" style="" id="ups_return" name="ups_return" class="">' . __('Include Return Label', 'ups-woocommerce-shipping') . '</label><img class="help_tip" style="float:none;" data-tip="'.__( 'You can generate the return label only for single package order.', 'ups-woocommerce-shipping' ).'" src="'.WC()->plugin_url().'/assets/images/help.png" height="16" width="16" /></li>';
					echo '<li><label for="ups_sat_delivery"><input type="checkbox" style="" id="ups_sat_delivery" name="ups_sat_delivery" class="">' . __('Saturday Delivery', 'ups-woocommerce-shipping') . '</label><img class="help_tip" style="float:none;" data-tip="'.__( 'Saturday Delivery from UPS allows you to stretch your business week to Saturday', 'ups-woocommerce-shipping' ).'" src="'.WC()->plugin_url().'/assets/images/help.png" height="16" width="16" /></li>';
				?>
				<li>
					<a class="button button-primary tips ups_create_shipment" href="<?php echo $download_url; ?>" data-tip="<?php _e( 'Confirm Shipment', 'ups-woocommerce-shipping' ); ?>"><?php _e( 'Confirm Shipment', 'ups-woocommerce-shipping' ); ?></a><hr style="border-color:#0074a2">
				</li>
				<?php
			
			}
			
			?>

			<script type="text/javascript">
				jQuery("a.ups_generate_packages").on("click", function() {
					location.href = this.href;
				});
				
				// To get rates on order page
				jQuery("a.wf_ups_generate_packages_rates").one("click", function() {		
					jQuery(this).click(function () { return false; });
						var manual_weight_arr		= 	jQuery("input[id='ups_manual_weight']").map(function(){return jQuery(this).val();}).get();
						var manual_height_arr		= 	jQuery("input[id='ups_manual_height']").map(function(){return jQuery(this).val();}).get();
						var manual_width_arr		= 	jQuery("input[id='ups_manual_width']").map(function(){return jQuery(this).val();}).get();
						var manual_length_arr		= 	jQuery("input[id='ups_manual_length']").map(function(){return jQuery(this).val();}).get();
						var manual_insurance_arr 	= 	jQuery("input[id='ups_manual_insurance']").map(function(){return jQuery(this).val();}).get();
						
						location.href = this.href + '&weight=' + manual_weight_arr +
							'&length=' + manual_length_arr
							+ '&width=' + manual_width_arr
							+ '&height=' + manual_height_arr
							+ '&insurance=' + manual_insurance_arr;
						return false;
				});
				//End of jQuery for getting the rates
				
				//For sitching between the services of get rates and services after every generated packages
				jQuery(document).ready( function() {
					jQuery(document).on("change", "#wf_ups_service_choosing_radio", function(){
					    if (jQuery("#wf_ups_service_choosing_radio:checked").val() == 'wf_ups_individual_service') {
						    jQuery(".ups_manual_service").prop("disabled", false);
					    } else {
						    jQuery(".ups_manual_service").val(jQuery("#wf_ups_service_choosing_radio:checked").val()).change();
						    jQuery(".ups_manual_service").prop("disabled", true);  
					    }
				    });
				});
				//End For sitching between the services of get rates and services after every generated packages
				
			</script>
			<?php
		}
		else {
			$ups_label_details_array = get_post_meta( $post->ID, 'ups_label_details_array', true );
			$ups_commercial_invoice_details = get_post_meta( $post->ID, 'ups_commercial_invoice_details', true );
			if(!empty($ups_label_details_array) && is_array($ups_label_details_array)){
				$packages = $this->xa_get_meta_key( $order, '_wf_ups_stored_packages', true, 'order');		//For displaying the products name with label on order page
				foreach ( $created_shipments_details_array as $shipmentId => $created_shipments_details ){

					if( "yes" == $this->show_label_in_browser ) {
						$target_val = "_blank";
					}
					else {
						$target_val = "_self";
					}
					
					// Multiple labels for each package.
					$index = 0;
					if( !empty($ups_label_details_array[$shipmentId]) ){
						foreach ( $ups_label_details_array[$shipmentId] as $ups_label_details ) {
							$label_extn_code 	= $ups_label_details["Code"];
							$tracking_number 	= isset( $ups_label_details["TrackingNumber"] ) ? $ups_label_details["TrackingNumber"] : '';
							$download_url 		= admin_url( '/?wf_ups_print_label='.base64_encode( $shipmentId.'|'.$post->ID.'|'.$label_extn_code.'|'.$index.'|'.$tracking_number ) );
							$post_fix_label		= '';
							
							if( count($ups_label_details_array) > 1 ) {
								$post_fix_label = '#'.( $index + 1 );
							}
							?>
							
							<table class="xa_ups_shipment_box_table" style="border:1px solid lightgray;margin: 5px;margin-top: 5px;box-shadow:.5px .5px 5px lightgrey; width:100%;">
								<caption style="font-size: 16px; color:#E74C3C;">Package Details</caption>
								<tr>
									<th style = "font-size:16px;">Weight</th>
									<th style = "font-size:16px;">Length</th>
									<th style = "font-size:16px;">Width</th>
									<th style = "font-size:16px;">Height</th>
									<th style = "font-size:16px;">Products ( Name x Quantity )</th>
								</tr>
							
							<?php 
								$package = array_shift($packages);
								$package_weight = $package['Package']['PackageWeight']['Weight'].' '.$package['Package']['PackageWeight']['UnitOfMeasurement']['Code'];
								$package_length = ! empty($package['Package']['Dimensions']) ? ( $package['Package']['Dimensions']['Length'].' '.$package['Package']['Dimensions']['UnitOfMeasurement']['Code']) : 0;
								$package_width = ! empty($package['Package']['Dimensions']) ? ( $package['Package']['Dimensions']['Width'].' '.$package['Package']['Dimensions']['UnitOfMeasurement']['Code']) : 0;
								$package_height = ! empty($package['Package']['Dimensions']) ? ( $package['Package']['Dimensions']['Height'].' '.$package['Package']['Dimensions']['UnitOfMeasurement']['Code']) : 0;

								echo "<td style='text-align:center; padding: 5px; font-size:16px;'>".$package_weight."</td>";
								echo "<td style='text-align:center; padding: 5px; font-size:16px;'>".$package_length."</td>";
								echo "<td style='text-align:center; padding: 5px; font-size:16px;'>".$package_width."</td>";
								echo "<td style='text-align:center; padding: 5px; font-size:16px;'>".$package_height."</td>";
								$first_item_in_package = ( isset($package['Package']['items']) && is_array($package['Package']['items']) ) ? current($package['Package']['items']) : null;
								if( ! empty($first_item_in_package) ) { 	// Check whether items are set in packages or not, current has been
									$products_in_package = null;
									$product_quantity	= array();
									$products_name		= array();
									foreach( $package['Package']['items'] as $product) {
										$product_quantity[$product->get_id()] = isset($product_quantity[$product->get_id()]) ? ( $product_quantity[$product->get_id()] +1 ) : 1;
										$products_name[$product->get_id()] = ( WC()->version > '2.7') ? $product->get_name() : $product->post->post_title;
									}
									foreach( $products_name as $product_id => $product_name) {
										if( ! empty($products_in_package) ) {
											$next_product_in_package = '<a style ="text-decoration:none;" href = "'.admin_url("post.php?post=$product_id&action=edit").'" >'.$product_name.'</a> X '.$product_quantity[$product_id];
											$products_in_package = $products_in_package.', '.$next_product_in_package;
										}
										else {
											$products_in_package = '<a style ="text-decoration:none;" href = "'.admin_url("post.php?post=$product_id&action=edit").'" >'.$product_name.'</a> X '.$product_quantity[$product_id];
										}
									}

									echo "<td style='text-align:center; padding: 5px; font-size:16px;'>".$products_in_package."</td>";
								}
							?>

							</table>
							<br />
							<strong><?php _e( 'Tracking No: ', 'ups-woocommerce-shipping' ); ?></strong><a href="http://wwwapps.ups.com/WebTracking/track?track=yes&trackNums=<?php echo $ups_label_details["TrackingNumber"] ?>" target="_blank"><?php echo $ups_label_details["TrackingNumber"] ?></a>
							<br /><a style="margin-top: 7px" class="button button-primary tips" href="<?php echo $download_url; ?>" data-tip="<?php _e( 'Print Label ', 'ups-woocommerce-shipping' );echo $post_fix_label; ?>" target="<?php echo $target_val; ?>"><?php _e( 'Print Label ', 'ups-woocommerce-shipping' );echo $post_fix_label ?></a>
							<br /> <br/>
							<?php						
							// Return Label Link
							if(isset($created_shipments_details['return'])&&!empty($created_shipments_details['return'])){
								$return_shipment_id = current(array_keys($created_shipments_details['return'])); // only one return label is considered now
								$ups_return_label_details_array = get_post_meta( $post->ID, 'ups_return_label_details_array', true );
								if( is_array($ups_return_label_details_array) && isset($ups_return_label_details_array[$return_shipment_id]) ){// check for return label accepted data
									$ups_return_label_details = $ups_return_label_details_array[$return_shipment_id];
									if( is_array($ups_return_label_details) ){
										$ups_return_label_detail = current($ups_return_label_details);
										$label_index=0;// as we took only one label so index is zero
										$return_download_url = admin_url( '/?wf_ups_print_label='.base64_encode( $return_shipment_id.'|'.$post->ID.'|'.$label_extn_code.'|'.$label_index.'|return' ) );
										?>
										<strong><?php _e( 'Tracking No: ', 'ups-woocommerce-shipping' ); ?></strong><a href="http://wwwapps.ups.com/WebTracking/track?track=yes&trackNums=<?php echo $ups_return_label_detail["TrackingNumber"] ?>" target="_blank"><?php echo $ups_return_label_detail["TrackingNumber"] ?></a><br/>
										<a class="button button-primary tips" href="<?php echo $return_download_url; ?>" data-tip="<?php _e( 'Print Return Label ', 'ups-woocommerce-shipping' );echo $post_fix_label; ?>" target="<?php echo $target_val; ?>"><?php _e( 'Print Return Label ', 'ups-woocommerce-shipping' );echo $post_fix_label ?></a><hr style="border-color:#0074a2">
										<?php
									}
								}
							}
							
							// EOF Return Label Link						
							$index = $index + 1;
						}
					}

					if(isset($ups_commercial_invoice_details[$shipmentId])){
						echo '<a class="button button-primary tips" target="'.$target_val.'" href="'.admin_url( '/?wf_ups_print_commercial_invoice='.base64_encode($post->ID.'|'.$shipmentId)).'" data-tip="'.__('Print Commercial Invoice', 'ups-woocommerce-shipping').'">'.__('Commercial Invoice', 'ups-woocommerce-shipping').'</a></br>';
					}
				}

				// For Create Return label button if it has not been created at the time of label creation
				if( empty($created_shipments_details_array[$shipmentId]['return']) ){
					$services = base64_encode($this->xa_get_meta_key( $order, 'xa_ups_generated_label_services', true));
					echo '<hr style="border-color:#0074a2">';
					$generate_return_label = !empty($services) ? admin_url( "/?xa_generate_return_label=$post->ID&service=$services&rt_service=$services") : '#';
					echo "<strong>";
						_e('Generate Return label : ', 'ups-woocommerce-shipping');
					echo "</strong>";
					echo '<a class="button button-primary tips" data-tip="'.__('Generate Return Label').'" href ="'.$generate_return_label.'">Generate Return label</a>';
					echo '<hr style="border-color:#0074a2">';
				}
				// End of Create Return label button if it has not been created at the time of label creation


				$void_shipment_url = admin_url( '/?wf_ups_void_shipment='.base64_encode( $post->ID ) );
				?>
				<strong><?php _e( 'Cancel the Shipment', 'ups-woocommerce-shipping' ); ?></strong></br>
				<a class="button tips" href="<?php echo $void_shipment_url; ?>" data-tip="<?php _e( 'Void Shipment', 'ups-woocommerce-shipping' ); ?>"><?php _e( 'Void Shipment', 'ups-woocommerce-shipping' ); ?></a><hr style="border-color:#0074a2">
				<?php
			}else{
				$accept_shipment_url = admin_url( '/?wf_ups_shipment_accept='.base64_encode( $post->ID ) );
				?>
				<strong><?php _e( 'Step 3: Accept your shipment.', 'ups-woocommerce-shipping' ); ?></strong></br>
				<a class="button button-primary tips" href="<?php echo $accept_shipment_url; ?>" data-tip="<?php _e('Accept Shipment', 'ups-woocommerce-shipping'); ?>"><?php _e( 'Accept Shipment', 'ups-woocommerce-shipping' ); ?></a><hr style="border-color:#0074a2">
				<?php
			}
			
		}
	}

	/**
	* Get order meta key
	* @param $post int | obj Order id or order object, Or Product id or Product Object.
	* @param $key string Meta key to fetch from order or Product
	* @param $single boolean True to get single meta key or false to get array of meta key. By default false.
	* @param $post_type string Post type order or product.
	* @return mixed Return meta key array or single.
	*/
	public function xa_get_meta_key( $post, $key, $single = false, $post_type='order' ) {
		if( ! is_object($post) ) {
			if( $post_type == 'order' )	{
				$post = wc_get_order($post);
			}
			else {
				$post = wc_get_product($post);
			}
		}

		if( WC()->version < '3.0' ) {
			return get_post_meta( $post->id, $key, $single );
		}
		else{
			return $post->get_meta( $key, $single );
		}
	}


	private function get_shop_address( $order, $ups_settings ){
		$shipper_phone_number 			= isset( $ups_settings['phone_number'] ) ? $ups_settings['phone_number'] : '';
		
		//Address standard followed in all xadapter plugins. 
		$from_address = array(
			'name'		=> isset( $ups_settings['ups_display_name'] ) ? $ups_settings['ups_display_name'] : '-',
			'company' 	=> isset( $ups_settings['ups_user_name'] ) ? $ups_settings['ups_user_name'] : '-',
			'phone' 	=> (strlen($shipper_phone_number) < 10) ? '0000000000' :  htmlspecialchars( $shipper_phone_number ),
			'email' 	=> isset( $ups_settings['email'] ) ? $ups_settings['email'] : '',

			'address_1' => isset( $ups_settings['origin_addressline'] ) ? $ups_settings['origin_addressline'] : '',
			'address_2' => '',
			'city' 		=> isset( $ups_settings['origin_city'] ) ? $ups_settings['origin_city'] : '',
			'state' 	=> $this->origin_state,
			'country' 	=> $this->origin_country,
			'postcode' 	=> isset( $ups_settings['origin_postcode'] ) ? $ups_settings['origin_postcode'] : '',
		);
		//Filter for shipping common addon
		return apply_filters( 'wf_filter_label_from_address', $from_address , $this->wf_create_package($order) );
	}

	private function get_order_address( $order ){
		//Address standard followed in all xadapter plugins. 
		return array(
			'name'		=> $order->shipping_first_name.' '.$order->shipping_last_name,
			'company' 	=> !empty($order->shipping_company) ? $order->shipping_company : '-',
			'phone' 	=> $order->billing_phone,
			'email' 	=> $order->billing_email,
			'address_1'	=> $order->shipping_address_1,
			'address_2'	=> $order->shipping_address_2,
			'city' 		=> $order->shipping_city,
			'state' 	=> $order->shipping_state,
			'country' 	=> $order->shipping_country,
			'postcode' 	=> $order->shipping_postcode,
		);
	}

	function wf_ups_shipment_confirmrequest( $order,$return_label=false ) {
		global $post;
		
		$ups_settings 					= get_option( 'woocommerce_'.WF_UPS_ID.'_settings', null ); 
		
		// Apply filter on settings data
		$ups_settings	=	apply_filters('wf_ups_confirm_shipment_settings', $ups_settings, $order); //For previous version compatibility.
		$ups_settings	=	apply_filters('wf_ups_shipment_settings', $ups_settings, $order);
		
		// Define user set variables
		$ups_enabled					= isset( $ups_settings['enabled'] ) ? $ups_settings['enabled'] : '';
		$ups_title						= isset( $ups_settings['title'] ) ? $ups_settings['title'] : 'UPS';
		$ups_availability    			= isset( $ups_settings['availability'] ) ? $ups_settings['availability'] : 'all';
		$ups_countries       			= isset( $ups_settings['countries'] ) ? $ups_settings['countries'] : array();
		// WF: Print Label Settings.
		$print_label_type     			= isset( $ups_settings['print_label_type'] ) ? $ups_settings['print_label_type'] : 'gif';
		$ship_from_address      		= isset( $ups_settings['ship_from_address'] ) ? $ups_settings['ship_from_address'] : 'origin_address';
		// API Settings
		
		$shipper_email	 				= isset( $ups_settings['email'] ) ? $ups_settings['email'] : '';
		$ups_user_id         			= isset( $ups_settings['user_id'] ) ? $ups_settings['user_id'] : '';
		$ups_password        			= isset( $ups_settings['password'] ) ? $ups_settings['password'] : '';
		$ups_access_key      			= isset( $ups_settings['access_key'] ) ? $ups_settings['access_key'] : '';
		$ups_shipper_number  			= isset( $ups_settings['shipper_number'] ) ? $ups_settings['shipper_number'] : '';
		$ups_negotiated      			= isset( $ups_settings['negotiated'] ) && $ups_settings['negotiated'] == 'yes' ? true : false;
		$ups_residential		        = isset( $ups_settings['residential'] ) && $ups_settings['residential'] == 'yes' ? true : false;
		
		$this->accesspoint_locator 	= (isset($this->settings[ 'accesspoint_locator']) && $this->settings[ 'accesspoint_locator']=='yes') ? true : false;
		
		$cod						= get_post_meta($order->id,'_wf_ups_cod',true);
		$sat_delivery				= get_post_meta($order->id,'_wf_ups_sat_delivery',true);
		$order_total				= $order->get_total();
		$order_currency				= $order->get_order_currency();
		
		$commercial_invoice		        = isset( $ups_settings['commercial_invoice'] ) && $ups_settings['commercial_invoice'] == 'yes' ? true : false;
		
		
		$ship_options=array('return_label'=>$return_label); // Array to pass options like return label on the fly.
		
		if( 'billing_address' == $ship_from_address ) { 
			$from_address 	= $this->get_order_address( $order );
			$to_address 	= $this->get_shop_address( $order, $ups_settings );
		}
		else {
			$from_address 	= $this->get_shop_address( $order, $ups_settings );
			$to_address 	= $this->get_order_address( $order );
		}

		$shipping_service_data	= $this->wf_get_shipping_service_data( $order ); 
		$shipping_method		= $shipping_service_data['shipping_method'];
		$shipping_service		= $shipping_service_data['shipping_service'];
		$shipping_service_name	= $shipping_service_data['shipping_service_name'];

		if( ($from_address['country'] == $to_address['country']) && in_array( $from_address['country'], $this->dc_domestic_countries) ){ // Delivery confirmation available at package level only for domestic shipments.
			$ship_options['delivery_confirmation_applicable']	= true;
			$ship_options['international_delivery_confirmation_applicable']	= false;
		}
		else {
			$ship_options['international_delivery_confirmation_applicable']	= true;
		}
		$package_data = $this->wf_get_package_data( $order, $ship_options, $to_address);

		if( empty( $package_data ) ) {
			$stored_package = get_post_meta( $order->id, '_wf_ups_stored_packages',false);
			if(is_array($stored_package)) {
				$package_data = $stored_package;
			} else {
				return false;
			}
		}
		
		$package_data		=	apply_filters('wf_ups_filter_label_packages',$package_data, $order);
		$shipments          =   $this->split_shipment_by_services($package_data, $order,$return_label);
		$shipments			=	apply_filters('wf_ups_shipment_data', $shipments, $order); // Filter to break shipments further, with other business logics, like multi vendor
		
		$shipment_requests	=	array();
		$all_var=get_defined_vars();	
			
		if( is_array($shipments) ){
			$service_index	= 0;
			$str 			= isset($_GET['service']) ? str_replace( '\"','', str_replace(']','', str_replace('[','',$_GET['service'])) ) : '';
			$svc_code 		= explode(',',$str);
			foreach($shipments as $shipment){
				$directdeliveryonlyindicator = null;
				$shipping_service=$svc_code[$service_index];
				if( in_array($shipping_service,array_keys($this->freight_services)) )
				{
					$freight_obj=new wf_freight_ups($this);
					foreach( $shipment['packages'] as $freight_package ) {
						$freight_package_shipment = array(
							'shipping_service'	=>	$shipment['shipping_service'],
							'packages'			=>	array($freight_package),
						);
						$shipment_requests[]=$freight_obj->create_shipment_request( $freight_package_shipment );
					}
				}
				else{
					$request_arr	=	array();
					$xml_request = '<?xml version="1.0" encoding="UTF-8"?>';
					$xml_request .= '<AccessRequest xml:lang="en-US">';
					$xml_request .= '<AccessLicenseNumber>'.$ups_access_key.'</AccessLicenseNumber>';
					$xml_request .= '<UserId>'.$ups_user_id.'</UserId>';
					$xml_request .= '<Password>'.$ups_password.'</Password>';
					$xml_request .= '</AccessRequest>';
					$xml_request .= '<?xml version="1.0" ?>';
					$xml_request .= '<ShipmentConfirmRequest>';
					$xml_request .= '<Request>';
					$xml_request .= '<TransactionReference>';
					$xml_request .= '<CustomerContext>'.$order->id.'</CustomerContext>';
					$xml_request .= '<XpciVersion>1.0001</XpciVersion>';
					$xml_request .= '</TransactionReference>';
					$xml_request .= '<RequestAction>ShipConfirm</RequestAction>';
					$xml_request .= '<RequestOption>nonvalidate</RequestOption>';
					$xml_request .= '</Request>';


						// Taking Confirm Shipment Data Into Array for Better Processing and Filtering
					$request_arr['Shipment']=array();

						//request for access point, not required for return label, confirmed by UPS
						if($this->accesspoint_locator && ! $return_label){// Access Point Addresses Are All Commercial So Overridding ResidentialAddress Condition
							$access_point_node	=	$this->get_confirm_shipment_accesspoint_request($order);
							if(!empty($access_point_node)){
								$ups_residential	=	false;
								$request_arr['Shipment'] = array_merge($access_point_node);
							}
						}
						$request_arr['Shipment']['Description']	= $this->wf_get_shipment_description( $order );
						if($return_label){
							$request_arr['Shipment']['ReturnService']	=	array('Code'	=>	9);
						}
						
						if( $from_address['country'] != $to_address['country'] || !in_array($from_address['country'],array('US','PR'))){// ReferenceNumber Valid if the origin/destination pai is not US/US or PR/PR
							$request_arr['Shipment']['ReferenceNumber']	=	array(
								'Code'	=>	'PO',
								'Value'	=>	$order->id,
								);
						}
						
						if( in_array( $from_address['country'],array('US') ) &&  in_array( $to_address['country'], array('PR','CA') ) ){
							$request_arr['Shipment']['InvoiceLineTotal']['CurrencyCode'] = $order_currency;
							$request_arr['Shipment']['InvoiceLineTotal']['MonetaryValue'] = (int)$order_total;
						}


						$is_gfp_shipment	= isset( $_GET['is_gfp_shipment'] ) ? $_GET['is_gfp_shipment'] : '';
						if($is_gfp_shipment=='true'){
							$request_arr['Shipment']['ShipmentRatingOptions']['FRSShipmentIndicator'] = 1;
							$request_arr['Shipment']['FRSPaymentInformation']['Type']['Code'] = '01';
							$request_arr['Shipment']['FRSPaymentInformation']['AccountNumber'] = $ups_shipper_number;
						}

						$request_arr['Shipment']['Shipper']	=	array(
							'Name'	=>	htmlspecialchars( $from_address['name'] ),
							'AttentionName'	=>	htmlspecialchars($from_address['company']),
							'PhoneNumber'	=>	$from_address['phone'],
							'EMailAddress'	=>	$from_address['email'],
							'ShipperNumber'	=>	$ups_shipper_number,
							'Address'		=>	array(
								'AddressLine1'		=>	htmlspecialchars( $from_address['address_1'] ),
								'City'				=>	$from_address['city'],
								'StateProvinceCode'	=>	$from_address['state'],
								'CountryCode'		=>	$from_address['country'],
								'PostalCode'		=>	$from_address['postcode'],
								),
							);
						
						if($return_label){
							$request_arr['Shipment']['ShipTo']	=	array(
								'CompanyName'	=>	substr( htmlspecialchars( $from_address['name'] ), 0, 30 ),
								'AttentionName'	=>	htmlspecialchars( $from_address['company'] ),
								'PhoneNumber'	=>	$from_address['phone'],
								'EMailAddress'	=>	$from_address['email'],
								'Address'		=>	array(
									'AddressLine1'		=>	htmlspecialchars( $from_address['address_1'] ),
									//'AddressLine2'		=>	htmlspecialchars( $ups_origin_addressline ),
									'City'				=>	$from_address['city'],
									'StateProvinceCode'	=>	$from_address['state'],
									'CountryCode'		=>	$from_address['country'],
									'PostalCode'		=>	$from_address['postcode'],
									)
								);
						}else{
							if( '' == trim( $to_address['company'] ) ) {
								$to_address['company'] = '-';
							}

							$request_arr['Shipment']['ShipTo']	=	array(
								'CompanyName'	=>	substr( htmlspecialchars( $to_address['company'] ), 0, 35 ),
								'AttentionName'	=>	htmlspecialchars( $to_address['name'] ),
								'PhoneNumber'	=>	$to_address['phone'],
								'EMailAddress'	=>	$to_address['email'],
								'Address'		=>	array(
									'AddressLine1'		=>	htmlspecialchars( $to_address['address_1'] ),
									'AddressLine2'		=>	htmlspecialchars( $to_address['address_2'] ),
									'City'				=>	$to_address['city'],
									'CountryCode'		=>	$to_address['country'],
									'PostalCode'		=>	$to_address['postcode'],
									)
								);
							if(in_array($to_address['country'], $this->countries_with_statecodes)){ // State Code valid for certain countries only
								$request_arr['Shipment']['ShipTo']['Address']['StateProvinceCode']	=	$to_address['state'];
							}
						}
						
						if( $ups_residential ) {
							$request_arr['Shipment']['ResidentialAddress']='';
						}

						$request_arr['Shipment']['Service']	=	array(
							'Code'			=>	$this->get_service_code_for_country( $shipment['shipping_service'], $from_address['country'] ),
							'Description'	=>	( $this->get_service_code_for_country( $shipment['shipping_service'], $from_address['country'] ) == 96 ) ? 'WorldWide Express Freight' : htmlspecialchars( $shipping_service_name ),
							);

						//Save service id, Required for pickup 
						update_post_meta( $order->id, 'wf_ups_selected_service', $shipment['shipping_service'] );
						
						$request_arr['Shipment']['PaymentInformation']	=	array(
							'Prepaid'	=>	array(
								'BillShipper'	=>	array(
									'AccountNumber'	=>	$ups_shipper_number,
									),
								),					
							);
						$request_arr['Shipment']['package']['multi_node']	=	1;
						$numofpieces = 0;	//For Worldwide Express Freight Service
						
						foreach ( $shipment['packages'] as $package ) {
							// InsuredValue should not send with Sure post
							if( $this->wf_is_surepost( $shipment['shipping_service'] ) ){
								unset( $package['Package']['PackageServiceOptions']['InsuredValue'] );
							}
							
							// To Set Delivery Confirmation at shipment level for international shipment
							if( $ship_options['international_delivery_confirmation_applicable'] ) {
								$shipment_delivery_confirmation = $this->wcsups->get_package_signature($package['Package']['items']);
								$delivery_confirmation = ( isset( $delivery_confirmation) && $delivery_confirmation >= $shipment_delivery_confirmation) ? $delivery_confirmation : $shipment_delivery_confirmation;
							}
						
							//Get direct delivery option from package to set in order level
							if( empty($directdeliveryonlyindicator) && !empty($package['Package']['DirectDeliveryOnlyIndicator']) ) {
								$directdeliveryonlyindicator = $package['Package']['DirectDeliveryOnlyIndicator'];
							}
							
							// Unset DirectDeliveryOnlyIndicator, it is not applicable at package level
							if( isset($package['Package']['DirectDeliveryOnlyIndicator']) ) {
								unset($package['Package']['DirectDeliveryOnlyIndicator']);
							}
							
							//For Worldwide Express Freight Service
							if( $shipment['shipping_service'] == 96 ) {
								$package['Package']['PackagingType']['Code'] = 30;
								if( isset($package['Package']['items']) ) {
									$numofpieces    += count($package['Package']['items']);
								}
							}
							$items_in_packages[] = isset($package['Package']['items']) ? $package['Package']['items'] : null ;	    //Contains product which are being packed together
							unset($package['Package']['items']);
							
							$request_arr['Shipment']['package'][] = $package;
						}

						$shipmentServiceOptions = array();
						// Set delivery confirmation at shipment level for international shipment
						if( ! empty($delivery_confirmation) ) {
							$shipmentServiceOptions['DeliveryConfirmation']['DCISType'] = ($delivery_confirmation == 3) ? 2 : 1;
						}
						//For Worldwide Express Freight Service
						if( $shipment['shipping_service'] == 96 ) {
							$request_arr['Shipment']['NumOfPiecesInShipment'] = $numofpieces;
						}
						// Negotiated Rates Flag
						if ( $ups_negotiated ) {
							$request_arr['Shipment']['RateInformation']['NegotiatedRatesIndicator']	=	'';
						}
						
						// Ship From Address is required for Return Label.
						// For return label, Ship From address will be set as Shipping Address of order.
						if($return_label){
							$request_arr['Shipment']['ShipFrom']	=	array(
								'CompanyName'	=>	substr( htmlspecialchars( $to_address['name'] ), 0, 30 ),
								'AttentionName'	=>	htmlspecialchars( $to_address['company'] ),
								'Address'		=>	array(
									'AddressLine1'	=>	htmlspecialchars( $to_address['address_1'] ),
									'City'		=>	$to_address['city'],
									'PostalCode'	=>	$to_address['postcode'],
									'CountryCode'	=>	$to_address['country'],
									),
								);
							
							if(in_array($to_address['country'], $this->countries_with_statecodes)){ // State Code valid for certain countries only
								$request_arr['Shipment']['ShipFrom']['Address']['StateProvinceCode']	=	$to_address['state'];
							}
						}
						
						if($sat_delivery){
							$shipmentServiceOptions['SaturdayDelivery']	=	'';
						}
						
						if( $commercial_invoice && ( $from_address['country'] != $to_address['country'] ) ){ // Commercial Invoice is available only for international shipments
							
							if($return_label){
								$soldToPhone	=	(strlen($from_address['phone']) < 10) ? '0000000000' : htmlspecialchars( $from_address['phone'] );
								$company_name	=	substr( htmlspecialchars($from_address['company']), 0, 35 );
								$sold_to_arr	=	array(
								'CompanyName'	=>	! empty($company_name) ? $company_name : '-',
								'AttentionName'	=>	htmlspecialchars( $from_address['name'] ),
								'PhoneNumber'	=>	$from_address['phone'],
								'Address'		=>	array(
									'AddressLine1'		=>	htmlspecialchars( $from_address['address_1'] ),
									'City'				=>	$from_address['city'],
									'CountryCode'		=>	$from_address['country'],
									),
								);

								if( ! empty($from_address['state']) ) {
									$sold_to_arr['Address']['StateProvinceCode'] = $from_address['state'];
								}
							}
							else {
								$soldToPhone	=	(strlen($to_address['phone']) < 10) ? '0000000000' : htmlspecialchars( $to_address['phone'] );
								$company_name	=	substr( htmlspecialchars($to_address['company']), 0, 35 );
								$sold_to_arr	=	array(
									'CompanyName'	=>	! empty($company_name) ? $company_name : '-',
									'AttentionName'	=>	htmlspecialchars( $to_address['name'] ),
									'PhoneNumber'	=>	$to_address['phone'],
									'Address'		=>	array(
										'AddressLine1'	=>	htmlspecialchars( $to_address['address_1'] ),
										'City'			=>	$to_address['city'],
										'CountryCode'	=>	$to_address['country']
									),
								);
								
								if(in_array($to_address['country'], $this->countries_with_statecodes)){ // State Code valid for certain countries only
									$sold_to_arr['Address']['StateProvinceCode']	=	$to_address['state'];
								}
							}
							$request_arr['Shipment']['SoldTo'] =	$sold_to_arr;
							
							$invoice_products	=	array();
							$orderItems = $order->get_items();
							foreach( $orderItems as $orderItem )
							{
								$item_id 			= $orderItem['variation_id'] ? $orderItem['variation_id'] : $orderItem['product_id'];
								$product_data 		= wc_get_product( $item_id );
								
								$product_unit_weight	= ( WC()->version < '2.7.0' ) ? woocommerce_get_weight( $product_data->get_weight(), $this->weight_unit ) : wc_get_weight( $product_data->get_weight(), $this->weight_unit );
								$product_unit_weight	=	
								$product_quantity		=	$orderItem['qty'];
								$product_line_weight	=	$product_unit_weight	*	$product_quantity;
								$hst = get_post_meta( $item_id, '_wf_ups_hst', true );
								
								$product_title = htmlspecialchars( $product_data->get_title() );
								$product_title = ( strlen( $product_title ) >= 35 ) ? substr( $product_title, 0, 30 ).'...' : $product_title;
								$product_details = array(
									'Description'	=>	$product_title,
									'Unit'			=>	array(
										'Number'	=>	$product_quantity,
										'UnitOfMeasurement'	=>	array('Code'	=>	$this->weight_unit),
										'Weight'	=>	$product_unit_weight,
										'Value'		=>	$product_data->get_price()
										),
									'OriginCountryCode'	=>	$from_address['country'],
									'NumberOfPackagesPerCommodity'	=>	'1',
									'ProductWeight'	=>	array(
										'UnitOfMeasurement'	=>	$this->weight_unit,
										'Weight'			=>	$product_line_weight,
									),
									'CommodityCode' => $hst,
								);
								$invoice_products[]['Product'] = apply_filters( 'wf_ups_shipment_confirm_request_product_details', $product_details, $product_data );
							}
							
							$shipmentServiceOptions['InternationalForms']	=	array(
								'FormType'				=>	'01',
								'InvoiceNumber'			=>	'',
								'InvoiceDate'			=>	date("Ymd"),
								'PurchaseOrderNumber'	=>	$order->get_order_number(),
								'Contacts'				=>	array(
									'SoldTo'	=>	array(
										'Name'						=>	htmlspecialchars($to_address['company']),
										'AttentionName'				=>	htmlspecialchars( $to_address['name'] ),
										'TaxIdentificationNumber'	=>	'',
										'Phone'						=>	array(
											'Number'	=>	$soldToPhone,
											),
										'Address'					=>	array(
											'AddressLine'	=>	htmlspecialchars( $to_address['address_1'] ).' '.htmlspecialchars( $to_address['address_2'] ),
											'City'			=>	$to_address['city'],
											'PostalCode'	=>	$to_address['postcode'],
											'CountryCode'	=>	$to_address['country'],
											)
										)
									),
								'ExportDate'			=>	date('Ymd'),
								'ExportingCarrier'		=>	'UPS',
								'ReasonForExport'		=>	'SALE',
								'CurrencyCode'			=>	$this->wcsups->get_ups_currency(),
								'AdditionalDocumentIndicator'	=>	'1',
								
							);
							if( $return_label ) {
								$shipmentServiceOptions['InternationalForms']['Contacts']['SoldTo'] = array(
									'Name'				=>  htmlspecialchars( $from_address['company'] ),
									'AttentionName'			=>  htmlspecialchars( $from_address['name'] ),
									'TaxIdentificationNumber'	=>  '',
									'Phone'				=>	array(
											'Number'	=>	$soldToPhone,
									),
									'Address'			=>	array(
											'AddressLine'	=>	htmlspecialchars( $from_address['address_1'] ).' '.htmlspecialchars( $from_address['address_2'] ),
											'City'		=>	$from_address['city'],
											'PostalCode'	=>	$from_address['postcode'],
											'CountryCode'	=>	$from_address['country'],
											)
									);
							}
							$declaration_statement = isset($this->settings[ 'declaration_statement']) ?  $this->settings[ 'declaration_statement'] : '';
							if( !empty($declaration_statement) ){
								$shipmentServiceOptions['InternationalForms']['DeclarationStatement'] = $declaration_statement;
							}

							if( !empty($this->reason_export)  && $this->reason_export != 'none' ){
								$shipmentServiceOptions['InternationalForms']['ReasonForExport']	=	$this->reason_export;
							}
							if( $return_label && in_array($from_address['country'], $this->countries_with_statecodes) ) {
								$shipmentServiceOptions['InternationalForms']['Contacts']['SoldTo']['Address']['StateProvinceCode']	= $from_address['state'];
							}
							elseif( ! $return_label && in_array($to_address['country'], $this->countries_with_statecodes)){
								$shipmentServiceOptions['InternationalForms']['Contacts']['SoldTo']['Address']['StateProvinceCode']	= $to_address['state'];
							}
							$shipmentServiceOptions['InternationalForms']['Product']	=	array_merge(array('multi_node'=>1), $invoice_products);
						}

						if($this->wcsups->cod){
							if($this->wcsups->is_european_country($to_address['country'])){
								$shipmentServiceOptions['COD']	=	array(
									'CODCode'		=>	3,
									'CODFundsCode'	=>	9,
									'CODAmount'		=>	array(
										'CurrencyCode'	=>	$this->wcsups->get_ups_currency(),
										'MonetaryValue'	=>	(string) $this->wcsups->cod_total * $this->wcsups->conversion_rate
										),
									);
							}
						}
						
						if( $this->tin_number ){
							// $request_arr['Shipment']['ItemizedPaymentInformation']['SplitDutyVATIndicator'] = true;
							$request_arr['Shipment']['Shipper']['TaxIdentificationNumber'] = $this->tin_number;
						}

						if( !empty($this->email_notification) && in_array( $request_arr['Shipment']['Service']['Code'], $this->email_notification_services ) ){
							$emails= array();
							foreach ($this->email_notification as $notifier) {
								switch ( $notifier ) {
									case 0: //sender
										array_push( $emails, array('EMailAddress' => $shipper_email) );
										break;
									case 1: //Recipient
										array_push( $emails, array('EMailAddress' => $order->billing_email) );
										break;
								}
							}
							if( !empty($emails) ){

								// $shipmentServiceOptions['Notification']['EMailMessage']['EMailAddress']['multi_node']	=	1;
								$shipmentServiceOptions['Notification']['EMailMessage']['EMailAddress'] = array_merge(array('multi_node'=>1), $emails);
								$shipmentServiceOptions['Notification']['NotificationCode'] = 6;
								$shipmentServiceOptions['Notification']['FromEMailAddress'] = $shipper_email;
								$shipmentServiceOptions['Notification']['EMailMessage']['UndeliverableEMailAddress'] = $shipper_email;
							}
						}
						
						//Set Direct delivery in the actual request
						if( !empty($directdeliveryonlyindicator) ) {
							$shipmentServiceOptions['DirectDeliveryOnlyIndicator'] = $directdeliveryonlyindicator;
						}
						
						if(sizeof($shipmentServiceOptions)){
							$request_arr['Shipment']['ShipmentServiceOptions']	=	empty( $request_arr['Shipment']['ShipmentServiceOptions'] ) ? $shipmentServiceOptions : array_merge($shipmentServiceOptions,$request_arr['Shipment']['ShipmentServiceOptions'] );
						}
						

						$request_arr['LabelSpecification']['LabelPrintMethod']	=	$this->get_code_from_label_type( $print_label_type );
						$request_arr['LabelSpecification']['HTTPUserAgent']		=	'Mozilla/4.5';
						
						if( 'zpl' == $print_label_type || 'epl' == $print_label_type || 'png' == $print_label_type ) {
							$request_arr['LabelSpecification']['LabelStockSize']	=	array('Height' => 4, 'Width' => 6);
						}
						$request_arr['LabelSpecification']['LabelImageFormat']	=	$this->get_code_from_label_type( $print_label_type );
						
						$request_arr	=	apply_filters('wf_ups_shipment_confirm_request_data', $request_arr, $order);
						// Converting array data to xml
						$xml_request .= $this->wcsups->wf_array_to_xml($request_arr);
						
						$xml_request .= '</ShipmentConfirmRequest>';
						$xml_request	=	apply_filters('wf_ups_shipment_confirm_request', $xml_request, $order, $shipment );
						$shipment_requests[]    = $this->modfiy_encoding($xml_request);
					}
					$service_index++;
				}
			}
			return $shipment_requests;
		}

		private function wf_is_surepost( $shipping_method ){
			return in_array($shipping_method, $this->ups_surepost_services ) ;
		}

		private function get_service_code_for_country($service, $country){
			$service_for_country = array(
				'CA' => array(
				'07' => '01', // for Canada serivce code of 'UPS Express(07)' is '01'
				),
				);
			if( array_key_exists($country, $service_for_country) ){
				return isset($service_for_country[$country][$service]) ? $service_for_country[$country][$service] : $service;
			}
			return $service;
		}


		private function wf_get_accesspoint_datas( $order_details ){
			if( WC()->version < '2.7.0' ){
				return ( isset($order_details->shipping_accesspoint) ) ? json_decode( stripslashes($order_details->shipping_accesspoint) ) : '';
			}else{
				$address_field = $order_details->get_meta('_shipping_accesspoint');
				return json_decode(stripslashes($address_field));
			}
		}

		public function get_confirm_shipment_accesspoint_request($order_details){
			$accesspoint = $this->wf_get_accesspoint_datas( $order_details );

			$confirm_accesspoint_request = array();
			if( isset($accesspoint->AddressKeyFormat) && !empty($accesspoint->AccessPointInformation->PublicAccessPointID) ){
				$access_point_consignee		= $accesspoint->AddressKeyFormat->ConsigneeName;
				$access_point_addressline	= $accesspoint->AddressKeyFormat->AddressLine;
				$access_point_city			= isset($accesspoint->AddressKeyFormat->PoliticalDivision2) ? $accesspoint->AddressKeyFormat->PoliticalDivision2 : '';
				$access_point_state			= isset($accesspoint->AddressKeyFormat->PoliticalDivision1) ? $accesspoint->AddressKeyFormat->PoliticalDivision1 : '';
				$access_point_postalcode	= $accesspoint->AddressKeyFormat->PostcodePrimaryLow;
				$access_point_country		= $accesspoint->AddressKeyFormat->CountryCode;
				$access_point_id			= $accesspoint->AccessPointInformation->PublicAccessPointID;

				if( strlen($access_point_addressline) > 35 ) {
					$address_line_1		=	null;
					$address_line_2		=	null;
					$temp_address		=	null;
					$new_address		= explode( ' ', $access_point_addressline);
					foreach( $new_address as $word ){
						$temp_address = $temp_address.' '.$word;
						if( empty($address_line_2) && strlen($temp_address) <= 35 ) {
							$address_line_1 = $address_line_1.' '.$word;
						}
						else {
							$address_line_2	= $address_line_2.' '.$word;
						}
						$temp_address = empty($address_line_2) ? $address_line_1 : $address_line_2;
					}
				}
							
				$confirm_accesspoint_request	=	array(
					'ShipmentIndicationType'	=>	array('Code'=>'02'),
					'AlternateDeliveryAddress'	=>	array(
						'Name'				=>	$access_point_consignee,
						'AttentionName'		=>	$access_point_consignee,
						'UPSAccessPointID'	=>	$access_point_id,
						'Address'			=>	array(
							'AddressLine1'		=>	! empty($address_line_1) ? $address_line_1 : $access_point_addressline,
							'AddressLine2'		=>	! empty($address_line_2) ? $address_line_2 : '-',
							'City'				=>	$access_point_city,
							'StateProvinceCode'	=>	$access_point_state,
							'PostalCode'		=>	$access_point_postalcode,
							'CountryCode'		=>	$access_point_country,
						),
					),						
				);
				
				$accesspoint_notifications[] = array(
						'Notification' => array(
							'NotificationCode'=>'012',
							'EMailMessage'=> array(
								'EMailAddress' => $order_details->billing_email,
							),
							'Locale'=>array(
								'Language'=>'ENG',
								'Dialect'=>'US',
							),
						),
					);
				$accesspoint_notifications[] = array(
						'Notification' => array(
							'NotificationCode'=>'013',
							'EMailMessage'=> array(
								'EMailAddress' => $order_details->billing_email,
							),
							'Locale'=>array(
								'Language'=>'ENG',
								'Dialect'=>'US',
							),
						),
					);
				$confirm_accesspoint_request['ShipmentServiceOptions']['Notification']	=	array_merge(array('multi_node'=>1), $accesspoint_notifications);
			}

			return $confirm_accesspoint_request;
		}
		private function get_code_from_label_type( $label_type ){
			switch ($label_type) {
				case 'zpl':
				$code_val = 'ZPL';
				break;
				case 'epl':
				$code_val = 'EPL';
				break;
				case 'png':
				$code_val = 'ZPL';
				break;
				default:
				$code_val = 'GIF';
				break;
			}
			return array('Code'=>$code_val);
		}

		private function wf_get_shipment_description( $order ){
			$shipment_description	= '\nOrder Id - '.$order->get_order_number().'\n';
			$order_items	= $order->get_items();

			$shipment_description	.=	'Items - '; 
			if(is_array($order_items) && count($order_items)){

				foreach( $order_items as $order_item ) {
					$product_data	= wc_get_product( $order_item['variation_id'] ? $order_item['variation_id'] : $order_item['product_id'] );
					$title 	= $product_data->get_title();
					$shipment_description 	.= $title.', ';
				}
			}

			if ('' == $shipment_description ) {
				$shipment_description = 'Package/customer supplied.';
			}
			$shipment_description = htmlspecialchars( $shipment_description );
			$shipment_description = apply_filters('wf_ups_alter_shipment_desription' ,$shipment_description);
			$shipment_description = ( strlen( $shipment_description ) >= 50 ) ? substr( $shipment_description, 0, 45 ).'...' : $shipment_description;

			return $shipment_description;
		}

		function wf_get_package_data( $order, $ship_options=array(), $to_address=array() ) {

			$packages				= $this->wf_create_package( $order, $to_address );

			if ( ! class_exists( 'WF_Shipping_UPS' ) ) {
				include_once 'class-wf-shipping-ups.php';
			}
			$this->wcsups 			= new WF_Shipping_UPS( $order );
			$package_data_array	= array();		

		if(!isset($ship_options['return_label']) || !$ship_options['return_label']){ // If return label is printing, cod can't be applied
		$this->wcsups->wf_set_cod_details($order);
	}

	$service_code=get_post_meta($order->id,'wf_ups_selected_service',1);
	if($service_code)
	{
		$this->wcsups->wf_set_service_code($service_code);
			if(in_array($service_code, array(92,93,94,95))){// Insurance value doen't wprk with sure post services
			$this->wcsups->insuredvalue = false;
		}
	}

	$package_params	=	array();
	if(isset($ship_options['delivery_confirmation_applicable'])){
		$package_params['delivery_confirmation_applicable']	=	$ship_options['delivery_confirmation_applicable'];
	}

	$packing_method  	= isset( $this->settings['packing_method'] ) ? $this->settings['packing_method'] : 'per_item';
	$package_data = array();
	foreach( $packages as $key => $package ) {
		$temp_package_data 		= $this->wcsups->wf_get_api_rate_box_data( $package, $packing_method, $package_params);
		if(is_array($temp_package_data) ) {
			$package_data = array_merge($package_data, $temp_package_data);
		}
	}
	return $package_data;
}

function wf_create_package( $order, $to_address=array() ){

	$orderItems = $order->get_items();

	foreach( $orderItems as $orderItem )
	{
		$item_id 			= $orderItem['variation_id'] ? $orderItem['variation_id'] : $orderItem['product_id'];
		$product_data 		= wc_get_product( $item_id );
		$items[$item_id] 	= array('data' => $product_data , 'quantity' => $orderItem['qty']);
	}

	$package['contents'] = isset($items) ? $items : array();	//If no items exist in order $items won't be set
	$package['destination'] = array (
		'country' 	=> !empty($to_address) ? $to_address['country'] : $order->shipping_country,
		'state' 	=> !empty($to_address) ? $to_address['state'] : $order->shipping_state,
		'postcode' 	=> !empty($to_address) ? $to_address['postcode'] : $order->shipping_postcode,
		'city' 		=> !empty($to_address) ? $to_address['city'] : $order->shipping_city,
		'address' 	=> !empty($to_address) ? $to_address['address_1'] : $order->shipping_address_1,
		'address_2'	=> !empty($to_address) ? $to_address['address_2'] : $order->shipping_address_2);
	$packages	= apply_filters( 'wf_ups_filter_label_from_packages',array($package), $this->settings['ship_from_address'], $order->id );
	return $packages;
}

function wf_ups_generate_packages(){
	$query_string 		= 	explode('|', base64_decode($_GET['wf_ups_generate_packages']));
	$post_id 		= 	$query_string[1];
	$order			= 	$this->wf_load_order( $post_id );
	$order_items		=	$order->get_items();
	if( empty($order_items) && class_exists('WC_Admin_Meta_Boxes') ) {
		WC_Admin_Meta_Boxes::add_error(__('UPS - No product Found.'));
		wp_redirect( admin_url( '/post.php?post='.$post_id.'&action=edit') );
		exit();
	}
	$package_data		=	$this->wf_get_package_data($order);

	if(empty($package_data)) {
			//wf_admin_notice::add_notice('Unable to generate packages. Your product may be missing weight/length/width/height.');
		$package['Package']['PackagingType'] = array(
			'Code' => '02',
			'Description' => 'Package/customer supplied'
			);

		$package['Package']	=	array(
			'PackagingType'	=>	array(
				'Code'				=>	'02',
				'Description'	=>	'Package/customer supplied'
				),
			'Description'	=> 'Rate',
			'Dimensions'	=>	array(
				'UnitOfMeasurement'	=>	array(
					'Code'	=>	$this->dim_unit,
					),
				'Length'	=>	0,
				'Width'		=>	0,
				'Height'	=>	0
				),
			'PackageWeight' => array(
				'UnitOfMeasurement'	=>	array(
					'Code'	=>	$this->weight_unit,
					),
				'Weight'	=>	0
				),
			'PackageServiceOptions' => array(
				'InsuredValue'	=> array(
					'CurrencyCode'	=>$this->wcsups->get_ups_currency(),
					'MonetaryValue'	=> 0
					)
				)
			);
		update_post_meta( $post_id, '_wf_ups_stored_packages', $package );
		$package_data=$package;
	} else {
		update_post_meta( $post_id, '_wf_ups_stored_packages', $package_data );
	}
	do_action( 'wf_after_package_generation', $post_id,$package_data);
	
	// If automatic label generation is on then don't redirect otherwise it will throw warning Cannot modify header information - headers already sent
	if( ( ! $this->debug ) || ( $this->settings['automate_label_generation'] == 'no' ) ) {
		wp_redirect( admin_url( '/post.php?post='.$post_id.'&action=edit') );
	}
	exit;
}

function wf_ups_shipment_confirm(){
	if( !$this->wf_user_check(isset($_GET['auto_generate'])?$_GET['auto_generate']:null) ) {
		echo "You don't have admin privileges to view this page.";
		exit;
	}

		// Load UPS Settings.
	$ups_settings 		= get_option( 'woocommerce_'.WF_UPS_ID.'_settings', null ); 
		// API Settings
	$api_mode      		= isset( $ups_settings['api_mode'] ) ? $ups_settings['api_mode'] : 'Test';
	$debug_mode      	= isset( $ups_settings['debug'] ) && $ups_settings['debug'] == 'yes' ? true : false;

	$query_string 		= explode('|', base64_decode($_GET['wf_ups_shipment_confirm']));
	$post_id 			= $query_string[1];
	$wf_ups_selected_service	= isset( $_GET['wf_ups_selected_service'] ) ? $_GET['wf_ups_selected_service'] : '';

	update_post_meta( $post_id, 'wf_ups_selected_service', $wf_ups_selected_service );

	$cod	= isset( $_GET['cod'] ) ? $_GET['cod'] : '';
	if($cod=='true'){
		update_post_meta( $post_id, '_wf_ups_cod', true );
	}else{
		delete_post_meta( $post_id, '_wf_ups_cod');
	}

	$sat_delivery	= isset( $_GET['sat_delivery'] ) ? $_GET['sat_delivery'] : '';
	if($sat_delivery=='true'){
		update_post_meta( $post_id, '_wf_ups_sat_delivery', true );
	}else{
		delete_post_meta( $post_id, '_wf_ups_sat_delivery');
	}

	$is_return_label	= isset( $_GET['is_return_label'] ) ? $_GET['is_return_label'] : '';
	if($is_return_label=='true'){
		$ups_return=true;
	}
	else{
		$ups_return=false;
	}

	$order				= $this->wf_load_order( $post_id );

	$requests = $this->wf_ups_shipment_confirmrequest( $order );

	$created_shipments_details_array = array();
        $return_package_index=0;
	foreach($requests as $request){
		if( $debug_mode ) {
			echo '<div style="background: #eee;overflow: auto;padding: 10px;margin: 10px;">SHIPMENT CONFIRM REQUEST: ';
			echo '<xmp>'.$request.'</xmp></div>'; 
		}

		if( !$request ) {
				// Due to some error and request not available, But the error is not catched
			wf_admin_notice::add_notice('Sorry. Something went wrong: please turn on debug mode to investigate more.');
			$this->wf_redirect( admin_url( '/post.php?post='.$post_id.'&action=edit') );
				exit;//return;
			}
			if( "Live" == $api_mode ) {
				$endpoint = 'https://www.ups.com/ups.app/xml/ShipConfirm';
				$freight_endpoint='https://onlinetools.ups.com/rest/FreightShip';
			}
			else {
				$endpoint = 'https://wwwcie.ups.com/ups.app/xml/ShipConfirm';
				$freight_endpoint='https://wwwcie.ups.com/rest/FreightShip';
			}

			$xml_request = str_replace( array( "\n", "\r" ), '', $request );
			if(!is_array($request) && json_decode( $request ) !==null)
			{	
				$response = wp_remote_post( $freight_endpoint,
					array(
						'timeout'   => 70,
						'sslverify' => $this->ssl_verify,
						'body'      => $xml_request
						)
					);				
			}
			else
			{
				$xml_request = $this->modfiy_encoding($xml_request);
				$response = wp_remote_post( $endpoint,
					array(
						'timeout'   => 70,
						'sslverify' => $this->ssl_verify,
						'body'      => $xml_request
						)
					);
			}
			if( $debug_mode && is_array($response)) {
				echo '<div style="background:#ccc;background: #ccc;overflow: auto;padding: 10px;margin: 10px 10px 50px 10px;">SHIPMENT CONFIRM RESPONSE: ';
				echo '<xmp>'.print_r($response['body'],1).'</xmp></div>'; 
			}
			
			if ( is_wp_error( $response ) ) {
				$error_message = $response->get_error_message();
				wf_admin_notice::add_notice('Sorry. Something went wrong: '.$error_message);			
				$this->wf_redirect( admin_url( '/post.php?post='.$post_id.'&action=edit') );
				exit;
			}		
			$req_arr=array();
			if(!is_array($request))
			{
				$req_arr=json_decode($request);
			}

			if(!is_array($request) && isset($req_arr->FreightShipRequest) && isset($req_arr->FreightShipRequest->Shipment->Service->Code)
				&& in_array($req_arr->FreightShipRequest->Shipment->Service->Code,array_keys($this->freight_services))
			   )				// For Freight Shipments  as it is JSON not Array
				{	try{
					$var=json_decode($response['body']);
					if( ! empty($var->Fault ) ) {
						WC_Admin_Meta_Boxes::add_error($var->Fault->detail->Errors->ErrorDetail->PrimaryErrorCode->Description);
						$this->wf_redirect( admin_url( '/post.php?post='.$post_id.'&action=edit') );
						exit;
					}

				}
				catch(Exception $e)
				{
					$this->wf_redirect( admin_url( '/post.php?post='.$post_id.'&action=edit') );
					exit;
				}
				$created_shipments_details = array();
				$shipment_id = (string)$var->FreightShipResponse->ShipmentResults->ShipmentNumber;

				$created_shipments_details["ShipmentDigest"] = (string)$var->FreightShipResponse->ShipmentResults->ShipmentNumber;
				$created_shipments_details["BOLID"] = (string)$var->FreightShipResponse->ShipmentResults->BOLID;
				$created_shipments_details["type"] = "freight";
				try{
					$img=(string)$var->FreightShipResponse->ShipmentResults->Documents->Image->GraphicImage;
				}catch(Exception $ex){$img='';}

				$created_shipments_details_array[$shipment_id] = $created_shipments_details;
				$this->wf_ups_freight_accept_shipment($img,$shipment_id,$created_shipments_details["BOLID"],$post_id);
			}else
			{
				$response_obj = simplexml_load_string( $response['body'] );
				
				$response_code = (string)$response_obj->Response->ResponseStatusCode;
				if( '0' == $response_code ) {
					$error_code = (string)$response_obj->Response->Error->ErrorCode;
					$error_desc = (string)$response_obj->Response->Error->ErrorDescription;
					
					
					wf_admin_notice::add_notice($error_desc.' [Error Code: '.$error_code.']');
					$this->wf_redirect( admin_url( '/post.php?post='.$post_id.'&action=edit') );
					exit;
				}			
				
				$created_shipments_details = array();
				$shipment_id = (string)$response_obj->ShipmentIdentificationNumber;
				
				$created_shipments_details["ShipmentDigest"] 			= (string)$response_obj->ShipmentDigest;

				$created_shipments_details_array[$shipment_id] = $created_shipments_details;
				$created_shipments_xml_request[$shipment_id]		= $request;

				// Creating Return Label 		
				if($ups_return){
					$return_label = $this->wf_ups_return_shipment_confirm($shipment_id,$return_package_index);
					if( !empty($return_label) ){
						$created_shipments_details_array[$shipment_id]['return'] = $return_label;
					}
				}
			}
                        $return_package_index++;
		}
		update_post_meta( $post_id, 'ups_created_shipments_xml_request_array', $created_shipments_xml_request );
		update_post_meta( $post_id, 'ups_created_shipments_details_array', $created_shipments_details_array );
		$this->ups_accept_shipment($post_id);
		$this->wf_redirect( admin_url( '/post.php?post='.$post_id.'&action=edit') );
		exit;
	}

	function wf_ups_freight_accept_shipment($img,$shipment_id,$BOLID,$order_id)
	{
		// Since their is no accept shipment method for freigth we will skip it 
		$ups_label_details["TrackingNumber"]		= $BOLID;
		$ups_label_details["Code"] 					= "PDF";
		$ups_label_details["GraphicImage"] 			= $img;			
		$ups_label_details_array[$shipment_id][]	= $ups_label_details;
		do_action('wf_label_generated_successfully',$shipment_id,$order_id,$ups_label_details["Code"],"0",$ups_label_details["TrackingNumber"]);
		update_post_meta( $order_id, 'ups_label_details_array', $ups_label_details_array );
		wf_admin_notice::add_notice('Order #'. $order_id.': Shipment accepted successfully. Labels are ready for printing.','notice');
		return true;
	}	
	function wf_ups_return_shipment_confirm($parent_shipment_id,$return_package_index){
		if( !$this->wf_user_check() ) {
			echo "You don't have admin privileges to view this page.";
			exit;
		}
		
		// Load UPS Settings.
		$ups_settings 		= get_option( 'woocommerce_'.WF_UPS_ID.'_settings', null ); 
		// API Settings
		$api_mode      		= isset( $ups_settings['api_mode'] ) ? $ups_settings['api_mode'] : 'Test';
		$debug_mode      	= isset( $ups_settings['debug'] ) && $ups_settings['debug'] == 'yes' ? true : false;
		
		$query_string 		= explode('|', base64_decode($_GET['wf_ups_shipment_confirm']));
		//xa_generate_return_label is set when return label is generated after generating the label, contain order/post id
		$post_id 			= ! empty($_GET['xa_generate_return_label']) ? $_GET['xa_generate_return_label'] : $query_string[1];
		$wf_ups_selected_service	= isset( $_GET['wf_ups_selected_service'] ) ? $_GET['wf_ups_selected_service'] : '';	

		$order				= $this->wf_load_order( $post_id );        
		$requests = $this->wf_ups_shipment_confirmrequest( $order,true);//true for return label, false for general shipment, default is false	



		if( !$requests ) return;

		if( "Live" == $api_mode ) {
			$endpoint = 'https://www.ups.com/ups.app/xml/ShipConfirm';
		}
		else {
			$endpoint = 'https://wwwcie.ups.com/ups.app/xml/ShipConfirm';
		}

		$created_shipments_details_array = array();
                foreach ($requests as $key => $request) {
                        if($key!==$return_package_index) continue; 			
			if( $debug_mode ) {
				echo '<div style="background: #eee;overflow: auto;padding: 10px;margin: 10px;">RETURN SHIPMENT CONFIRM REQUEST: ';
				echo '<xmp>'.print_r($request,1).'</xmp></div>'; 
			}
			$xml_request = str_replace( array( "\n", "\r" ), '', $request );
			$xml_request = $this->modfiy_encoding($xml_request);
			$response = wp_remote_post( $endpoint,
				array(
					'timeout'   => 70,
					'sslverify' => $this->ssl_verify,
					'body'      => $xml_request
					)
				);
			if( $debug_mode ) {
				echo '<div style="background:#ccc;background: #ccc;overflow: auto;padding: 10px;margin: 10px 10px 50px 10px;">RETURN SHIPMENT CONFIRM RESPONSE: ';
				echo '<xmp>'.print_r($response['body'],1).'</xmp></div>'; 
			}

			if ( is_wp_error( $response ) ) {
				$error_message = $response->get_error_message();
				$error_message='Return Label - '.$error_message;
				wf_admin_notice::add_notice('Sorry. Something went wrong: '.$error_message);
				$this->wf_redirect( admin_url( '/post.php?post='.$post_id.'&action=edit' ) );
				exit;
			}
			
			$response_obj = simplexml_load_string( $response['body'] );
			
			$response_code = (string)$response_obj->Response->ResponseStatusCode;
			if( '0' == $response_code ) {
				$error_code = (string)$response_obj->Response->Error->ErrorCode;
				$error_desc = (string)$response_obj->Response->Error->ErrorDescription;
				$error_desc='Return Label - '.$error_desc;
				wf_admin_notice::add_notice($error_desc.' [Error Code: '.$error_code.']');
				$this->wf_redirect( admin_url( '/post.php?post='.$post_id.'&action=edit') );
				exit;
			}
			$created_shipments_details = array();
			$shipment_id = (string)$response_obj->ShipmentIdentificationNumber;
			
			$created_shipments_details["ShipmentDigest"] 			= (string)$response_obj->ShipmentDigest;
			$created_shipments_details_array[$shipment_id] = $created_shipments_details;
		}
		return $created_shipments_details_array;
	}

	private function wf_redirect($url=''){
		if(!$url){
			return false;
		}
		if( !$this->debug ){
			wp_redirect( $url );
		}
		exit();
	}
	
	function wf_ups_shipment_accept(){
		if( !$this->wf_user_check(isset($_GET['auto_generate'])?$_GET['auto_generate']:null)  ) {
			echo "You don't have admin privileges to view this page.";
			exit;
		}

		$query_string		= explode('|', base64_decode($_GET['wf_ups_shipment_accept']));
		$post_id 			= $query_string[0];
		
		
		// Load UPS Settings.
		$ups_settings 				= get_option( 'woocommerce_'.WF_UPS_ID.'_settings', null ); 
		// API Settings
		$api_mode      				= isset( $ups_settings['api_mode'] ) ? $ups_settings['api_mode'] : 'Test';
		$ups_user_id         		= isset( $ups_settings['user_id'] ) ? $ups_settings['user_id'] : '';
		$ups_password        		= isset( $ups_settings['password'] ) ? $ups_settings['password'] : '';
		$ups_access_key      		= isset( $ups_settings['access_key'] ) ? $ups_settings['access_key'] : '';
		$ups_shipper_number  		= isset( $ups_settings['shipper_number'] ) ? $ups_settings['shipper_number'] : '';
		$disble_shipment_tracking	= isset( $ups_settings['disble_shipment_tracking'] ) ? $ups_settings['disble_shipment_tracking'] : 'TrueForCustomer';
		$debug_mode      	        = isset( $ups_settings['debug'] ) && $ups_settings['debug'] == 'yes' ? true : false;
		
		if( "Live" == $api_mode ) {
			$endpoint = 'https://www.ups.com/ups.app/xml/ShipAccept';
		}
		else {
			$endpoint = 'https://wwwcie.ups.com/ups.app/xml/ShipAccept';
		}		
		
		$created_shipments_details_array	= get_post_meta($post_id, 'ups_created_shipments_details_array', true);	

		$shipment_accept_requests	=	array();
		if(is_array($created_shipments_details_array)){
			
			$ups_label_details_array	= array();
			$shipment_id_cs 			= '';
			
			foreach($created_shipments_details_array as $shipmentId => $created_shipments_details){
				if(isset($created_shipments_details['ShipmentDigest']) && !(isset($created_shipments_details['type']) && $created_shipments_details['type']=='freight')){
					$xml_request = '<?xml version="1.0" encoding="UTF-8" ?>';
					$xml_request .= '<AccessRequest xml:lang="en-US">';
					$xml_request .= '<AccessLicenseNumber>'.$ups_access_key.'</AccessLicenseNumber>';
					$xml_request .= '<UserId>'.$ups_user_id.'</UserId>';
					$xml_request .= '<Password>'.$ups_password.'</Password>';
					$xml_request .= '</AccessRequest>'; 
					$xml_request .= '<?xml version="1.0" ?>';
					$xml_request .= '<ShipmentAcceptRequest>';
					$xml_request .= '<Request>';
					$xml_request .= '<TransactionReference>';
					$xml_request .= '<CustomerContext>'.$post_id.'</CustomerContext>';
					$xml_request .= '<XpciVersion>1.0001</XpciVersion>';
					$xml_request .= '</TransactionReference>';
					$xml_request .= '<RequestAction>ShipAccept</RequestAction>';
					$xml_request .= '</Request>';
					$xml_request .= '<ShipmentDigest>'.$created_shipments_details["ShipmentDigest"].'</ShipmentDigest>';
					$xml_request .= '</ShipmentAcceptRequest>';
					
					$xml_request = $this->modfiy_encoding($xml_request);

					if( $debug_mode ) {
						echo '<div style="background: #eee;overflow: auto;padding: 10px;margin: 10px;">SHIPMENT ACCEPT REQUEST: ';
						echo '<xmp>'.$xml_request.'</xmp></div>'; 

					}
					$response = wp_remote_post( $endpoint,
						array(
							'timeout'   => 70,
							'sslverify' => $this->ssl_verify,
							'body'      => $xml_request
							)
						);
					
					if( $debug_mode ) {
						echo '<div style="background:#ccc;background: #ccc;overflow: auto;padding: 10px;margin: 10px 10px 50px 10px;">SHIPMENT ACCEPT RESPONSE: ';
						echo '<xmp>'.print_r($response['body'],1).'</xmp></div>'; 
					}
					
					if ( is_wp_error( $response ) ) {
						$error_message = $response->get_error_message();
						wf_admin_notice::add_notice(__('Order # '.$post_id.' Shipment # '.$shipmentId.' - Sorry. Something went wrong: '.$error_message));
						continue;
					}

					$response_obj = simplexml_load_string( '<root>' . preg_replace('/<\?xml.*\?>/','', $response['body'] ) . '</root>' );	

					$response_code = (string)$response_obj->ShipmentAcceptResponse->Response->ResponseStatusCode;
					
					if('0' == $response_code) {
						$error_code = (string)$response_obj->ShipmentAcceptResponse->Response->Error->ErrorCode;
						$error_desc = (string)$response_obj->ShipmentAcceptResponse->Response->Error->ErrorDescription;
						
						wf_admin_notice::add_notice(__('Order # '.$post_id.' Shipment # '.$shipmentId.' - '.$error_desc.' [Error Code: '.$error_code.']'));
						continue;
					}

					$package_results 			= $response_obj->ShipmentAcceptResponse->ShipmentResults->PackageResults;
					$ups_label_details			= array();
					
					
					
					if(isset($response_obj->ShipmentAcceptResponse->ShipmentResults->Form->Image)){
						$international_forms[$shipmentId]	=	array(
							'ImageFormat'	=>	(string)$response_obj->ShipmentAcceptResponse->ShipmentResults->Form->Image->ImageFormat->Code,
							'GraphicImage'	=>	(string)$response_obj->ShipmentAcceptResponse->ShipmentResults->Form->Image->GraphicImage,
							);
					}
					// Labels for each package.
					foreach ( $package_results as $package_result ) {
						$ups_label_details["TrackingNumber"]		= (string)$package_result->TrackingNumber;
						$ups_label_details["Code"] 					= (string)$package_result->LabelImage->LabelImageFormat->Code;
						$ups_label_details["GraphicImage"] 			= (string)$package_result->LabelImage->GraphicImage;			
						$ups_label_details_array[$shipmentId][]		= $ups_label_details;
						$shipment_id_cs 							.= $ups_label_details["TrackingNumber"].',';
					}
				}
			}
			$shipment_id_cs = rtrim( $shipment_id_cs, ',' );

			if( empty($ups_label_details_array) ) {
				wf_admin_notice::add_notice('UPS: Sorry, An unexpected error occurred.');
				$this->wf_redirect( admin_url( '/post.php?post='.$post_id.'&action=edit') );
				exit;
			}
			else {
				update_post_meta( $post_id, 'ups_label_details_array', $ups_label_details_array );
				
				if(isset($international_forms)){
					update_post_meta( $post_id, 'ups_commercial_invoice_details', $international_forms );
				}
				
				if( isset($created_shipments_details['return']) && $created_shipments_details['return'] ){// creating return label
					$return_label_ids = $this->wf_ups_return_shipment_accept($post_id,$created_shipments_details['return']);
					if($return_label_ids&&$shipment_id_cs){
						$shipment_id_cs=$shipment_id_cs.','.$return_label_ids;
					}
				}
			}			
			if( 'True' != $disble_shipment_tracking) {
				$this->wf_redirect( admin_url( '/post.php?post='.$post_id.'&wf_ups_track_shipment='.$shipment_id_cs ) );
				exit;
			}
			wf_admin_notice::add_notice('UPS: Shipment accepted successfully. Labels are ready for printing.','notice');
			$this->wf_redirect( admin_url( '/post.php?post='.$post_id.'&action=edit') );
			exit;
		}
	}
	
	function wf_ups_return_shipment_accept($post_id,$shipment_data){
		if( !$this->wf_user_check() ) {
			echo "You don't have admin privileges to view this page.";
			exit;
		}
		
		// Load UPS Settings.
		$ups_settings 				= get_option( 'woocommerce_'.WF_UPS_ID.'_settings', null ); 
		// API Settings
		$api_mode      				= isset( $ups_settings['api_mode'] ) ? $ups_settings['api_mode'] : 'Test';
		$ups_user_id         		= isset( $ups_settings['user_id'] ) ? $ups_settings['user_id'] : '';
		$ups_password        		= isset( $ups_settings['password'] ) ? $ups_settings['password'] : '';
		$ups_access_key      		= isset( $ups_settings['access_key'] ) ? $ups_settings['access_key'] : '';
		$ups_shipper_number  		= isset( $ups_settings['shipper_number'] ) ? $ups_settings['shipper_number'] : '';
		$disble_shipment_tracking	= isset( $ups_settings['disble_shipment_tracking'] ) ? $ups_settings['disble_shipment_tracking'] : 'TrueForCustomer';
		$debug_mode      	        = isset( $ups_settings['debug'] ) && $ups_settings['debug'] == 'yes' ? true : false;
		
		if( "Live" == $api_mode ) {
			$endpoint = 'https://www.ups.com/ups.app/xml/ShipAccept';
		}
		else {
			$endpoint = 'https://wwwcie.ups.com/ups.app/xml/ShipAccept';
		}
		foreach($shipment_data as $shipment_id=>$created_shipments_details){	
			$created_shipments_details=current($shipment_data);// only one shipment is allowed
			$xml_request = '<?xml version="1.0"?>';
			$xml_request .= '<AccessRequest xml:lang="en-US">';
			$xml_request .= '<AccessLicenseNumber>'.$ups_access_key.'</AccessLicenseNumber>';
			$xml_request .= '<UserId>'.$ups_user_id.'</UserId>';
			$xml_request .= '<Password>'.$ups_password.'</Password>';
			$xml_request .= '</AccessRequest>'; 
			$xml_request .= '<?xml version="1.0"?>';
			$xml_request .= '<ShipmentAcceptRequest>';
			$xml_request .= '<Request>';
			$xml_request .= '<TransactionReference>';
			$xml_request .= '<CustomerContext>'.$post_id.'</CustomerContext>';
			$xml_request .= '<XpciVersion>1.0001</XpciVersion>';
			$xml_request .= '</TransactionReference>';
			$xml_request .= '<RequestAction>ShipAccept</RequestAction>';
			$xml_request .= '</Request>';
			$xml_request .= '<ShipmentDigest>'.$created_shipments_details["ShipmentDigest"].'</ShipmentDigest>';
			$xml_request .= '</ShipmentAcceptRequest>';
			
			$xml_request = $this->modfiy_encoding($xml_request);

			if( $debug_mode ) {
				echo '<div style="background: #eee;overflow: auto;padding: 10px;margin: 10px;">RETURN SHIPMENT ACCEPT REQUEST: ';
				echo '<xmp>'.$xml_request.'</xmp></div>'; 
			}
			$response = wp_remote_post( $endpoint,
				array(
					'timeout'   => 70,
					'sslverify' => $this->ssl_verify,
					'body'      => $xml_request
					)
				);
			
			if( $debug_mode ) {
				echo '<div style="background:#ccc;background: #ccc;overflow: auto;padding: 10px;margin: 10px 10px 50px 10px;">RETURN SHIPMENT ACCEPT RESPONSE: ';
				echo '<xmp>'.print_r($response['body'],1).'</xmp></div>'; 
			}	
			$response_obj = simplexml_load_string( '<root>' . preg_replace('/<\?xml.*\?>/','', $response['body'] ) . '</root>' );	
			$response_code = (string)$response_obj->ShipmentAcceptResponse->Response->ResponseStatusCode;
			if('0' == $response_code) {
				$error_code = (string)$response_obj->ShipmentAcceptResponse->Response->Error->ErrorCode;
				$error_desc = (string)$response_obj->ShipmentAcceptResponse->Response->Error->ErrorDescription;
				
				wf_admin_notice::add_notice($error_desc.' [Error Code: '.$error_code.']');
				return false;
			}
			$package_results 			= $response_obj->ShipmentAcceptResponse->ShipmentResults->PackageResults;		
			
			$shipment_id_cs = '';
			// Labels for each package.
                        $ups_label_details_array=get_post_meta( $post_id, 'ups_return_label_details_array',true );
			if(empty($ups_label_details_array))
			{
			    $ups_label_details_array=array();
			}
			foreach ( $package_results as $package_result ) {
				$ups_label_details["TrackingNumber"]		= (string)$package_result->TrackingNumber;
				$ups_label_details["Code"] 					= (string)$package_result->LabelImage->LabelImageFormat->Code;
				$ups_label_details["GraphicImage"] 			= (string)$package_result->LabelImage->GraphicImage;
				$ups_label_details_array[$shipment_id][]	= $ups_label_details;
				$shipment_id_cs 							.= $ups_label_details["TrackingNumber"].',';
			}
			$shipment_id_cs = rtrim( $shipment_id_cs, ',' );			
			if( empty($ups_label_details_array) ) {				
				wf_admin_notice::add_notice('UPS: Sorry, An unexpected error occurred while creating return label.');
				return false;
			}
			else {
				update_post_meta( $post_id, 'ups_return_label_details_array', $ups_label_details_array );
				return $shipment_id_cs;
			}
			break; // Only one return shipment is allowed
			return false;
		}
	}

	function wf_ups_print_label(){
		if( !$this->wf_user_check(isset($_GET['auto_generate'])?$_GET['auto_generate']:null)  ) {
			echo "You don't have admin privileges to view this page.";
			exit;
		}
		
		$ups_settings 				= get_option( 'woocommerce_'.WF_UPS_ID.'_settings', null ); 
		$print_label_type	= isset( $ups_settings['print_label_type'] ) ? $ups_settings['print_label_type'] : 'gif';

		$query_string		= explode('|', base64_decode($_GET['wf_ups_print_label']));
		$shipmentId 		= $query_string[0];
		$post_id 			= $query_string[1];
		$label_extn_code 	= $query_string[2];
		$index			 	= $query_string[3];
		$tracking_number    = $query_string[4];
		
		$label_meta_name='ups_label_details_array';
		if(isset($query_string[4])){
			$return			= $query_string[4];
			if($return=='return'){
				$label_meta_name='ups_return_label_details_array';
			}
		}
		
		$ups_label_details_array = get_post_meta( $post_id, $label_meta_name, true );
                if(empty($ups_label_details_array))
                {
                    $ups_label_details_array=array();
                }
                
		$ups_settings 				  = get_option( 'woocommerce_'.WF_UPS_ID.'_settings', null ); 
		$show_label_in_browser        = isset( $ups_settings['show_label_in_browser'] ) ? $ups_settings['show_label_in_browser'] : 'no';

		if( empty($ups_label_details_array) ) {
			wf_admin_notice::add_notice('UPS: Sorry, An unexpected error occurred.');
			wp_redirect( admin_url( '/post.php?post='.$post_id.'&action=edit') );
			exit;
		}

		$graphic_image = $ups_label_details_array[$shipmentId][$index]["GraphicImage"];
		
		if("GIF" == $label_extn_code) {
			if( "yes" == $show_label_in_browser ) {
				echo '<img src="data:image/gif;base64,' . $graphic_image. '" />';
				exit;
			}

            //$binary_label = base64_decode($graphic_image);
			$binary_label = base64_decode(chunk_split($graphic_image));

			$final_image 	= $binary_label;
			$extn_code		= 'gif';
		}
        // ZPL which will be converted to PNG.
		elseif("ZPL" == $label_extn_code && $print_label_type == 'zpl') {
			$binary_label = base64_decode(chunk_split($graphic_image));

            // By default zpl code returned by UPS has ^POI command, which will invert the label because
            // of some reason. Removing it so that label will not be inverted.
			$zpl_label_inverted = str_replace( "^POI", "", $binary_label);
			
			$file_name = 'UPS-ShippingLabel-Label-'.$post_id.'-'.$tracking_number.'.zpl';
			$this->wf_generate_document_file($zpl_label_inverted, $file_name);
			exit;
		}
		elseif("EPL" == $label_extn_code && $print_label_type == 'epl') {
			$binary_label = base64_decode(chunk_split($graphic_image));

			$file_name = 'UPS-ShippingLabel-Label-'.$post_id.'-'.$tracking_number.'.epl';
			$this->wf_generate_document_file($binary_label, $file_name);
			exit;
		}
		elseif("PDF" == $label_extn_code ) {
			$binary_label = base64_decode(chunk_split($graphic_image));

			$file_name = 'UPS-BOL-'.$post_id.'-'.$tracking_number.'.pdf';
			$final_image=$binary_label;
			$extn_code='pdf';
		}
		else {
            //$zpl_label = base64_decode($graphic_image);
			$zpl_label = base64_decode(chunk_split($graphic_image));
            // By default zpl code returned by UPS has ^POI command, which will invert the label because
            // of some reason. Removing it so that label will not be inverted.
			$zpl_label_inverted = str_replace( "^POI", "", $zpl_label);

			$response 		= wp_remote_post( "http://api.labelary.com/v1/printers/8dpmm/labels/4x6/0/",
				array(
					'timeout'   => 70,
					'sslverify' => $this->ssl_verify,
					'body'      => $zpl_label_inverted
					)
				);


			$final_image 	= $response["body"];
			$extn_code		= 'png';

			if( "yes" == $show_label_in_browser ) {
				$final_image_base64_encoded = base64_encode( $final_image );
				echo '<img src="data:image/png;base64,' . $final_image_base64_encoded. '" />';
				exit;
			}

		}

		header('Content-Description: File Transfer');
		header('Content-Type: image/'.$extn_code.'');
		header('Content-disposition: attachment; filename="UPS-ShippingLabel-' . 'Label-'.$post_id.'-'.$tracking_number.'.'.$extn_code.'"');
		echo $final_image;
		exit;
	}
	
	function wf_ups_print_commercial_invoice(){
		$req_data	= explode('|',base64_decode($_GET['wf_ups_print_commercial_invoice']));
		
		$post_id		=	$req_data[0];
		$shipment_id	=	$req_data[1];
		
		$invoice_details = get_post_meta( $post_id, 'ups_commercial_invoice_details', true );
		$graphic_image = $invoice_details[$shipment_id]["GraphicImage"];
		
		$extn_code	=	$invoice_details[$shipment_id]["ImageFormat"];
		
		header('Content-Description: File Transfer');
		header('Content-Type: image/'.$extn_code.'');
		header('Content-disposition: attachment; filename="UPS-Commercial-Invoice-'.$post_id.'.'.$extn_code.'"');
		echo base64_decode($graphic_image);
		exit;
	}

	private function wf_generate_document_file($content, $file_name){
		
		$uploads_dir_info		=	wp_upload_dir();
		$file_name_with_path	=	$uploads_dir_info['basedir'].$file_name;
		$handle = fopen($file_name_with_path, "w");
		fwrite($handle, $content);
		fclose($handle);
		
		

		header('Content-Type: application/octet-stream');
		header('Content-Disposition: attachment; filename='.basename($file_name));
		header('Expires: 0');
		header('Cache-Control: must-revalidate');
		header('Pragma: public');
		header('Content-Length: ' . filesize($file_name_with_path));
		readfile($file_name_with_path);
		return;
	}

	function wf_ups_void_shipment(){
		if( !$this->wf_user_check() ) {
			echo "You don't have admin privileges to view this page.";
			exit;
		}

		$query_string		= explode( '|', base64_decode( $_GET['wf_ups_void_shipment'] ) );
		$post_id 			= $query_string[0];
		$ups_label_details_array 	= get_post_meta( $post_id, 'ups_label_details_array', true );
		
		// Load UPS Settings.
		$ups_settings 				= get_option( 'woocommerce_'.WF_UPS_ID.'_settings', null ); 
		// API Settings
		$api_mode		      		= isset( $ups_settings['api_mode'] ) ? $ups_settings['api_mode'] : 'Test';
		$ups_user_id         		= isset( $ups_settings['user_id'] ) ? $ups_settings['user_id'] : '';
		$ups_password        		= isset( $ups_settings['password'] ) ? $ups_settings['password'] : '';
		$ups_access_key      		= isset( $ups_settings['access_key'] ) ? $ups_settings['access_key'] : '';
		$ups_shipper_number  		= isset( $ups_settings['shipper_number'] ) ? $ups_settings['shipper_number'] : '';
		
		
		
		$client_side_reset = false;
		if( isset( $_GET['client_reset'] ) ) {
			$client_side_reset = true;
		}
		
		if( "Live" == $api_mode ) {
			$endpoint = 'https://www.ups.com/ups.app/xml/Void';
		}
		else {
			$endpoint = 'https://wwwcie.ups.com/ups.app/xml/Void';
		}	
		
		if( !empty( $ups_label_details_array ) && !$client_side_reset ) {
			foreach($ups_label_details_array as $shipmentId => $ups_label_detail_arr){
				$xml_request = '<?xml version="1.0" ?>';
				$xml_request .= '<AccessRequest xml:lang="en-US">';
				$xml_request .= '<AccessLicenseNumber>'.$ups_access_key.'</AccessLicenseNumber>';
				$xml_request .= '<UserId>'.$ups_user_id.'</UserId>';
				$xml_request .= '<Password>'.$ups_password.'</Password>';
				$xml_request .= '</AccessRequest>';
				$xml_request .= '<?xml version="1.0" encoding="UTF-8" ?>';
				$xml_request .= '<VoidShipmentRequest>';
				$xml_request .= '<Request>';
				$xml_request .= '<TransactionReference>';
				$xml_request .= '<CustomerContext>'.$post_id.'</CustomerContext>';
				$xml_request .= '<XpciVersion>1.0001</XpciVersion>';
				$xml_request .= '</TransactionReference>';
				$xml_request .= '<RequestAction>Void</RequestAction>';
				$xml_request .= '<RequestOption />';
				$xml_request .= '</Request>';
				$xml_request .= '<ExpandedVoidShipment>';
				$xml_request .= '<ShipmentIdentificationNumber>'.$shipmentId.'</ShipmentIdentificationNumber>';
				foreach ( $ups_label_detail_arr as $ups_label_details ) {
					$xml_request .= '<TrackingNumber>'.$ups_label_details["TrackingNumber"].'</TrackingNumber>';
				}
				$xml_request .= '</ExpandedVoidShipment>';
				$xml_request .= '</VoidShipmentRequest>';
				
				$xml_request	= apply_filters( 'xa_ups_void_shipment_xml_request', $xml_request, $shipmentId, $post_id );	// To support vendor addon

				$xml_request = $this->modfiy_encoding($xml_request);
				$response = wp_remote_post( $endpoint,
					array(
						'timeout'   => 70,
						'sslverify' => $this->ssl_verify,
						'body'      => $xml_request
						)
					);
				
				// In case of any issues with remote post.
				if ( is_wp_error( $response ) ) {
					wf_admin_notice::add_notice('Sorry. Something went wrong: '.$error_message);
					wp_redirect( admin_url( '/post.php?post='.$post_id.'&action=edit') );
					exit;
				}
				
				$response_obj 	= simplexml_load_string( '<root>' . preg_replace('/<\?xml.*\?>/','', $response['body'] ) . '</root>' );
				$response_code 	= (string)$response_obj->VoidShipmentResponse->Response->ResponseStatusCode;

				// It is an error response.
				if( '0' == $response_code ) {
					$error_code = (string)$response_obj->VoidShipmentResponse->Response->Error->ErrorCode;
					$error_desc = (string)$response_obj->VoidShipmentResponse->Response->Error->ErrorDescription;
					
					$message = '<strong>'.$error_desc.' [Error Code: '.$error_code.']'.'. </strong>';

					$current_page_uri	= $_SERVER['REQUEST_URI'];
					$href_url 			= $current_page_uri.'&client_reset';
					
					$message .= 'Please contact UPS to void/cancel this shipment. <br/>';
					$message .= 'If you have already cancelled this shipment by calling UPS customer care, and you would like to create shipment again then click <a class="button button-primary tips" href="'.$href_url.'" data-tip="Client Side Reset">Client Side Reset</a>';
					$message .= '<p style="color:red"><strong>Note: </strong>Previous shipment details and label will be removed from Order page.</p>';
					
					if( "Test" == $api_mode ) {
						$message .= "<strong>Also, noticed that you have enabled 'Test' mode.<br/>Please note that void is not possible in 'Test' mode, as there is no real shipment is created with UPS. </strong><br/>";
					}
					wf_admin_notice::add_notice($message);
					wp_redirect( admin_url( '/post.php?post='.$post_id.'&action=edit') );
					exit;
				}
				
				$this->wf_ups_void_return_shipment($post_id,$shipmentId);
			}			
		}
		$empty_array = array();
		update_post_meta( $post_id, 'ups_created_shipments_details_array', $empty_array );
		update_post_meta( $post_id, 'ups_label_details_array', $empty_array );
		update_post_meta( $post_id, 'ups_commercial_invoice_details', $empty_array );
		update_post_meta( $post_id, 'wf_ups_selected_service', '' );
		
		// Reset of stored meta elements done. Back to admin order page. 
		if( $client_side_reset ){
			wf_admin_notice::add_notice('UPS: Client side reset of labels and shipment completed. You can re-initiate shipment now.','notice');
			wp_redirect( admin_url( '/post.php?post='.$post_id.'&action=edit') );
			exit;
		}
		wf_admin_notice::add_notice('UPS: Cancellation of shipment completed successfully. You can re-initiate shipment.','notice');
		wp_redirect( admin_url( '/post.php?post='.$post_id.'&action=edit') );
		exit;
	}
	
	function wf_ups_void_return_shipment($post_id,$shipmentId){
		$ups_created_shipments_details_array=get_post_meta($post_id,'ups_created_shipments_details_array',1);
		if(is_array($ups_created_shipments_details_array)&&isset($ups_created_shipments_details_array[$shipmentId]['return'])){
			$return_shipment_id=current(array_keys($ups_created_shipments_details_array[$shipmentId]['return']));
			if($return_shipment_id){
				// Load UPS Settings.
				$ups_settings 				= get_option( 'woocommerce_'.WF_UPS_ID.'_settings', null ); 
				// API Settings
				$api_mode		      		= isset( $ups_settings['api_mode'] ) ? $ups_settings['api_mode'] : 'Test';
				$ups_user_id         		= isset( $ups_settings['user_id'] ) ? $ups_settings['user_id'] : '';
				$ups_password        		= isset( $ups_settings['password'] ) ? $ups_settings['password'] : '';
				$ups_access_key      		= isset( $ups_settings['access_key'] ) ? $ups_settings['access_key'] : '';
				$ups_shipper_number  		= isset( $ups_settings['shipper_number'] ) ? $ups_settings['shipper_number'] : '';
				
				$ups_return_label_details_array 	= get_post_meta( $post_id, 'ups_return_label_details_array', true );
				
				$client_side_reset = false;
				if( isset( $_GET['client_reset'] ) ) {
					$client_side_reset = true;
				}
				
				if( "Live" == $api_mode ) {
					$endpoint = 'https://www.ups.com/ups.app/xml/Void';
				}
				else {
					$endpoint = 'https://wwwcie.ups.com/ups.app/xml/Void';
				}
				
				if( !empty( $ups_return_label_details_array ) && $return_shipment_id) {
					$xml_request = '<?xml version="1.0" ?>';
					$xml_request .= '<AccessRequest xml:lang="en-US">';
					$xml_request .= '<AccessLicenseNumber>'.$ups_access_key.'</AccessLicenseNumber>';
					$xml_request .= '<UserId>'.$ups_user_id.'</UserId>';
					$xml_request .= '<Password>'.$ups_password.'</Password>';
					$xml_request .= '</AccessRequest>';
					$xml_request .= '<?xml version="1.0" encoding="UTF-8" ?>';
					$xml_request .= '<VoidShipmentRequest>';
					$xml_request .= '<Request>';
					$xml_request .= '<TransactionReference>';
					$xml_request .= '<CustomerContext>'.$post_id.'</CustomerContext>';
					$xml_request .= '<XpciVersion>1.0001</XpciVersion>';
					$xml_request .= '</TransactionReference>';
					$xml_request .= '<RequestAction>Void</RequestAction>';
					$xml_request .= '<RequestOption />';
					$xml_request .= '</Request>';
					$xml_request .= '<ExpandedVoidShipment>';
					$xml_request .= '<ShipmentIdentificationNumber>'.$return_shipment_id.'</ShipmentIdentificationNumber>';
					foreach ( $ups_return_label_details_array[$return_shipment_id] as $ups_return_label_details ) {
						$xml_request .= '<TrackingNumber>'.$ups_return_label_details["TrackingNumber"].'</TrackingNumber>';
					}
					$xml_request .= '</ExpandedVoidShipment>';
					$xml_request .= '</VoidShipmentRequest>';
					$xml_request = $this->modfiy_encoding($xml_request);
					$response = wp_remote_post( $endpoint,
						array(
							'timeout'   => 70,
							'sslverify' => $this->ssl_verify,
							'body'      => $xml_request
							)
						);
					
					// In case of any issues with remote post.
					if ( is_wp_error( $response ) ) {
						wf_admin_notice::add_notice('Sorry. Something went wrong: '.$error_message);
						return;
					}
					
					$response_obj 	= simplexml_load_string( '<root>' . preg_replace('/<\?xml.*\?>/','', $response['body'] ) . '</root>' );
					$response_code 	= (string)$response_obj->VoidShipmentResponse->Response->ResponseStatusCode;

					// It is an error response.
					if( '0' == $response_code ) {
						$error_code = (string)$response_obj->VoidShipmentResponse->Response->Error->ErrorCode;
						$error_desc = (string)$response_obj->VoidShipmentResponse->Response->Error->ErrorDescription;
						
						$message = '<strong>'.$error_desc.' [Error Code: '.$error_code.']'.'. </strong>';

						$current_page_uri	= $_SERVER['REQUEST_URI'];
						$href_url 			= $current_page_uri.'&client_reset';
						
						$message .= 'Please contact UPS to void/cancel this shipment. <br/>';
						$message .= 'If you have already cancelled this shipment by calling UPS customer care, and you would like to create shipment again then click <a class="button button-primary tips" href="'.$href_url.'" data-tip="Client Side Reset">Client Side Reset</a>';
						$message .= '<p style="color:red"><strong>Note: </strong>Previous shipment details and label will be removed from Order page.</p>';
						
						if( "Test" == $api_mode ) {
							$message .= "<strong>Also, noticed that you have enabled 'Test' mode.<br/>Please note that void is not possible in 'Test' mode, as there is no real shipment is created with UPS. </strong><br/>";
						}
						
						wf_admin_notice::add_notice($message);
						return;
					}
				}
				$empty_array = array();
				update_post_meta( $post_id, 'ups_return_label_details_array', $empty_array );
			}
		}
		
	}

	function wf_load_order( $orderId ){
		if( !$orderId ){
			return false;
		}
		if(!class_exists('wf_order')){
			include_once('class-wf-legacy.php');
		}
		return ( WC()->version < '2.7.0' ) ? new WC_Order( $orderId ) : new wf_order( $orderId );    
	}
	
	function wf_user_check($auto_generate=null) {
		$current_minute=(integer)date('i');
		if(!empty($auto_generate) && ($auto_generate==md5($current_minute) || $auto_generate==md5($current_minute+1) ))
		{
			return true;
		}
		if ( is_admin() ) {
			return true;
		}
		return false;
	}
	
	function wf_get_shipping_service_data($order){

		//TODO: Take the first shipping method. The use case of multiple shipping method for single order is not handled.
		
		$shipping_methods = $order->get_shipping_methods();
		if ( ! $shipping_methods ) {
			return false;
		}

		$shipping_method			= array_shift( $shipping_methods );
		$shipping_service_tmp_data	= explode( ':',$shipping_method['method_id'] );
		$wf_ups_selected_service	= '';

		$wf_ups_selected_service 	= get_post_meta( $order->id, 'wf_ups_selected_service', true );

		if( '' != $wf_ups_selected_service ) {			// If already tried to generate the label with any service
			$shipping_service_data['shipping_method'] 		= WF_UPS_ID;
			$shipping_service_data['shipping_service'] 		= $wf_ups_selected_service;
			$shipping_service_data['shipping_service_name']	= isset( $this->ups_services[$wf_ups_selected_service] ) ? $this->ups_services[$wf_ups_selected_service] : '';
		}
		elseif( ! empty($shipping_service_tmp_data) && $shipping_service_tmp_data[0] == WF_UPS_ID ) {			// Customer Selected Service if UPS
			$shipping_service_data = array(
				'shipping_method'		=>	WF_UPS_ID,
				'shipping_service_name'	=>	$shipping_service_tmp_data[0],
				'shipping_service'		=>	$shipping_service_tmp_data[1],
			);
		}
		elseif ( $this->is_domestic($order) && !empty($this->settings['default_dom_service']) ){
			$service_code = $this->settings['default_dom_service'];
			$shipping_service_data = array(
				'shipping_method' 		=> WF_UPS_ID,
				'shipping_service' 		=> $service_code,
				'shipping_service_name'	=> isset( $this->ups_services[$service_code] ) ? $this->ups_services[$service_code] : '',
			);
		}elseif ( !$this->is_domestic($order) && !empty($this->settings['default_int_service']) ){
			$service_code = $this->settings['default_int_service'];
			$shipping_service_data = array(
				'shipping_method' 		=> WF_UPS_ID,
				'shipping_service' 		=> $service_code,
				'shipping_service_name'	=> isset( $this->ups_services[$service_code] ) ? $this->ups_services[$service_code] : '',
			);
		}else {
			$shipping_service_data['shipping_method'] 		= WF_UPS_ID;
			$shipping_service_data['shipping_service'] 		= '';
			$shipping_service_data['shipping_service_name']	= '';
		}
	return $shipping_service_data;
}

private function is_domestic( $order){
	return ( $order->shipping_country == $this->origin_country );
}

public function get_dimension_from_package($package){

	$dimensions	=	array(
		'Length'		=>	0,
		'Width'			=>	0,
		'Height'		=>	0,
		'Weight'		=>	0,
		'InsuredValue'	=>	0,
		);

	if(!isset($package['Package'])){
		return $dimensions;
	}
	if(isset($package['Package']['Dimensions'])){
		$dimensions['Length']	=	$package['Package']['Dimensions']['Length'];
		$dimensions['Width']	=	$package['Package']['Dimensions']['Width'];
		$dimensions['Height']	=	$package['Package']['Dimensions']['Height'];
	}

	$weight					=	$package['Package']['PackageWeight']['Weight'];

	if($package['Package']['PackageWeight']['UnitOfMeasurement']['Code']=='OZS'){
			if($this->weight_unit=='LBS'){ // make weight in pounds
				$weight	=	$weight/16;
			}else{
				$weight	=	$weight/35.274; // To KG
			}
		}
		//PackageServiceOptions
		if(isset($package['Package']['PackageServiceOptions']['InsuredValue'])){
			$dimensions['InsuredValue']	=	$package['Package']['PackageServiceOptions']['InsuredValue']['MonetaryValue'];
		}
		$dimensions['Weight']	=	$weight;
		return $dimensions;
	}
	
	public function manual_packages($packages){
		if(!isset($_GET['weight'])){
			return $packages;
		}
		
		$length_arr		=	json_decode(stripslashes(html_entity_decode($_GET["length"])));
		$width_arr		=	json_decode(stripslashes(html_entity_decode($_GET["width"])));
		$height_arr		=	json_decode(stripslashes(html_entity_decode($_GET["height"])));
		$weight_arr		=	json_decode(stripslashes(html_entity_decode($_GET["weight"])));		
		$insurance_arr	=	json_decode(stripslashes(html_entity_decode($_GET["insurance"])));
		$service_arr	=	json_decode(stripslashes(html_entity_decode($_GET["service"])));
		
		

		$no_of_package_entered	=	count($weight_arr);
		$no_of_packages			=	count($packages);
		
		// Populate extra packages, if entered manual values
		if($no_of_package_entered > $no_of_packages){ 
			$package_clone	=	current($packages); //get first package to clone default data
			for($i=$no_of_packages; $i<$no_of_package_entered; $i++){
				$packages[$i]	=	array(
					'Package'	=>	array(
						'PackagingType'	=>	array(
							'Code'	=>	'02',
							'Description'	=>	'Package/customer supplied',
							),
						'Description'	=>	'Rate',
						'PackageWeight'	=>	array(
							'UnitOfMeasurement'	=>	array(
								'Code'	=>	$package_clone['Package']['PackageWeight']['UnitOfMeasurement']['Code'],
								),
							),
						),
					);
			}
		}
		
		// Overridding package values
		foreach($packages as $key => $package){
			if(isset($length_arr[$key])){// If not available in GET then don't overwrite.
			$packages[$key]['Package']['Dimensions']['Length']	=	$length_arr[$key];
		}
			if(isset($width_arr[$key])){// If not available in GET then don't overwrite.
			$packages[$key]['Package']['Dimensions']['Width']	=	$width_arr[$key];
		}
			if(isset($height_arr[$key])){// If not available in GET then don't overwrite.
			$packages[$key]['Package']['Dimensions']['Height']	=	$height_arr[$key];
		}
			if(isset($weight_arr[$key])){// If not available in GET then don't overwrite.

			$weight	=	$weight_arr[$key];

				if(isset($service_arr[$key]) && $service_arr[$key]==92){// Surepost Less Than 1LBS
					$packages[$key]['Package']['PackageWeight']['UnitOfMeasurement']['Code']	=	'OZS';
				}
				
				if($packages[$key]['Package']['PackageWeight']['UnitOfMeasurement']['Code']=='OZS'){
					if($this->weight_unit=='LBS'){ // make sure weight from pounds to ounces
						$weight	=	$weight*16;
					}else{
						$weight	=	$weight*35.274; // From KG to ounces
					}
				}
				$packages[$key]['Package']['PackageWeight']['Weight']	=	$weight;
			}
			if(isset($insurance_arr[$key]) && $insurance_arr[$key]>0){// If not available in GET then don't overwrite.
			$packages[$key]['Package']['PackageServiceOptions']['InsuredValue']	=	array(
				'CurrencyCode'	=>	$this->wcsups->get_ups_currency(),
				'MonetaryValue'	=>	$insurance_arr[$key],
				);
		}
	}
	return $packages;
}

function split_shipment_by_services($ship_packages, $order, $return_label=false){

	$shipments	=	array();
	if(!isset($_GET['service'])){
		$shipping_service_data				= $this->wf_get_shipping_service_data( $order );
		$default_service_type 				= $shipping_service_data['shipping_service'];

		$shipments[]	=	array(
			'shipping_service'	=>	$default_service_type,
			'packages'			=>	$ship_packages,
			);
	}else{
				$order_id = ( WC()->version < '3.0' ) ? $order->id : $order->get_id();
				// Services for return label if label has been generated previously
				if( ! empty($_GET['xa_generate_return_label'] ) ) {
					$service_arr = json_decode(html_entity_decode(base64_decode($_GET["rt_service"])));
				}
				else {	// Services for label
					$service_arr            =       json_decode(stripslashes(html_entity_decode($_GET["service"])));
					update_post_meta( $order_id, 'xa_ups_generated_label_services',$_GET["service"]);
					if($return_label)
					{	// Services for return label if it is being generated at the time of label creation only
						$service_arr=      json_decode(stripslashes(html_entity_decode($_GET["rt_service"])));
					}
				}

				foreach($service_arr as $count => $service_code){
				if(isset($ship_packages[$count] ) ) {
					$shipment_arr[$service_code][]	=	$ship_packages[$count];
				}
		}


		foreach($shipment_arr as $service_code => $packages){
			$shipments[]	=	array(
				'shipping_service'	=>	$service_code,
				'packages'			=>	$packages,
				);
		}
	}
	return $shipments;
}

function array2XML($obj, $array)
{
	foreach ($array as $key => $value)
	{
		if(is_numeric($key))
			$key = 'item' . $key;

		if (is_array($value))
		{
			$node = $obj->addChild($key);
			$this->array2XML($node, $value);
		}
		else
		{
			$obj->addChild($key, htmlspecialchars($value));
		}
	}
}

	// Bulk Label Printing

function init_bulk_printing(){
	add_action('admin_footer', 	array($this, 'add_bulk_print_option'));
	add_action('load-edit.php',	array($this, 'perform_bulk_label_actions'));
	add_action('woocommerce_admin_order_actions_end', array($this, 'label_printing_buttons'));
}

function add_bulk_print_option(){
	global $post_type;

	if($post_type == 'shop_order') {
		?>
		<script type="text/javascript">
			jQuery(document).ready(function() {
				jQuery('<option>').val('ups_generate_label').text('<?php _e('Generate UPS Label', 'ups-woocommerce-shipping');?>').appendTo("select[name='action']");
				jQuery('<option>').val('ups_generate_label').text('<?php _e('Generate UPS Label', 'ups-woocommerce-shipping');?>').appendTo("select[name='action2']");

				jQuery('<option>').val('ups_void_shipment').text('<?php _e('Void UPS Shipment', 'ups-woocommerce-shipping');?>').appendTo("select[name='action']");
				jQuery('<option>').val('ups_void_shipment').text('<?php _e('Void UPS Shipment', 'ups-woocommerce-shipping');?>').appendTo("select[name='action2']");
			});
		</script>
		<?php
	}
}

function perform_bulk_label_actions(){
	$wp_list_table = _get_list_table('WP_Posts_List_Table');
	$action = $wp_list_table->current_action();

	if($action == 'ups_generate_label'){			
		if(isset($_REQUEST['post']) && is_array($_REQUEST['post'])){
			foreach($_REQUEST['post'] as $order_id){

				if($this->ups_confirm_shipment($order_id)){
					$this->ups_accept_shipment($order_id);
				}					
			}
		}
		else{
			wf_admin_notice::add_notice(__('Please select atleast one order', 'ups-woocommerce-shipping'));
		}
	}else if($action == 'ups_void_shipment'){
		if(isset($_REQUEST['post']) && is_array($_REQUEST['post'])){
			foreach($_REQUEST['post'] as $order_id){					
				$this->ups_void_shipment($order_id);				
			}
		}
		else{
			wf_admin_notice::add_notice(__('Please select atleast one order', 'ups-woocommerce-shipping'));
		}
	}
}

function ups_void_shipment($order_id){

	$ups_label_details_array	=	$this->get_order_label_details($order_id);
	if(!$ups_label_details_array){
		wf_admin_notice::add_notice('Order #'. $order_id.': Shipment is not available.');			
		return false;
	}

		// Load UPS Settings.
	$ups_settings 				= get_option( 'woocommerce_'.WF_UPS_ID.'_settings', null ); 
		// API Settings
	$api_mode		      		= isset( $ups_settings['api_mode'] ) ? $ups_settings['api_mode'] : 'Test';
	$ups_user_id         		= isset( $ups_settings['user_id'] ) ? $ups_settings['user_id'] : '';
	$ups_password        		= isset( $ups_settings['password'] ) ? $ups_settings['password'] : '';
	$ups_access_key      		= isset( $ups_settings['access_key'] ) ? $ups_settings['access_key'] : '';
	$ups_shipper_number  		= isset( $ups_settings['shipper_number'] ) ? $ups_settings['shipper_number'] : '';

	if( "Live" == $api_mode ) {
		$endpoint = 'https://www.ups.com/ups.app/xml/Void';
	}
	else {
		$endpoint = 'https://wwwcie.ups.com/ups.app/xml/Void';
	}

	foreach($ups_label_details_array as $shipmentId => $ups_label_detail_arr){

		$xml_request = '<?xml version="1.0" ?>';
		$xml_request .= '<AccessRequest xml:lang="en-US">';
		$xml_request .= '<AccessLicenseNumber>'.$ups_access_key.'</AccessLicenseNumber>';
		$xml_request .= '<UserId>'.$ups_user_id.'</UserId>';
		$xml_request .= '<Password>'.$ups_password.'</Password>';
		$xml_request .= '</AccessRequest>';
		$xml_request .= '<?xml version="1.0" encoding="UTF-8" ?>';
		$xml_request .= '<VoidShipmentRequest>';
		$xml_request .= '<Request>';
		$xml_request .= '<TransactionReference>';
		$xml_request .= '<CustomerContext>'.$order_id.'</CustomerContext>';
		$xml_request .= '<XpciVersion>1.0001</XpciVersion>';
		$xml_request .= '</TransactionReference>';
		$xml_request .= '<RequestAction>Void</RequestAction>';
		$xml_request .= '<RequestOption />';
		$xml_request .= '</Request>';
		$xml_request .= '<ExpandedVoidShipment>';
		$xml_request .= '<ShipmentIdentificationNumber>'.$shipmentId.'</ShipmentIdentificationNumber>';
		foreach ( $ups_label_detail_arr as $ups_label_details ) {
			$xml_request .= '<TrackingNumber>'.$ups_label_details["TrackingNumber"].'</TrackingNumber>';
		}
		$xml_request .= '</ExpandedVoidShipment>';
		$xml_request .= '</VoidShipmentRequest>';
		$xml_request = $this->modfiy_encoding($xml_request);
		$response = wp_remote_post( $endpoint,
			array(
				'timeout'   => 70,
				'sslverify' => $this->ssl_verify,
				'body'      => $xml_request
				)
			);

			// In case of any issues with remote post.
		if ( is_wp_error( $response ) ) {
			wf_admin_notice::add_notice('Order #'. $order_id.': Sorry. Something went wrong: '.$error_message);
			continue;
		}

		$response_obj 	= simplexml_load_string( '<root>' . preg_replace('/<\?xml.*\?>/','', $response['body'] ) . '</root>' );
		$response_code 	= (string)$response_obj->VoidShipmentResponse->Response->ResponseStatusCode;

			// It is an error response.
		if( '0' == $response_code ) {
			$error_code = (string)$response_obj->VoidShipmentResponse->Response->Error->ErrorCode;
			$error_desc = (string)$response_obj->VoidShipmentResponse->Response->Error->ErrorDescription;

			$message = '<strong>'.$error_desc.' [Error Code: '.$error_code.']'.'. </strong>';


			$void_shipment_url = admin_url( '/?wf_ups_void_shipment='.base64_encode( $order_id ).'&client_reset');
			$message .= 'Please contact UPS to void/cancel this shipment. <br/>';

				// For bulk void shipment we are clearing the data autometically

			$message .= 'If you have already cancelled this shipment by calling UPS customer care, and you would like to create shipment again then click <a class="button button-primary tips" href="'.$void_shipment_url.'" data-tip="Client Side Reset">Client Side Reset</a>';
			$message .= '<p style="color:red"><strong>Note: </strong>Previous shipment details and label will be removed from Order page.</p>';

			if( "Test" == $api_mode ) {
				$message .= "<strong>Also, noticed that you have enabled 'Test' mode.<br/>Please note that void is not possible in 'Test' mode, as there is no real shipment is created with UPS. </strong><br/>";
			}

			wf_admin_notice::add_notice('Order #'. $order_id.': '.$message);
			return false;
		}

		$this->wf_ups_void_return_shipment($order_id,$shipmentId);
	}

	delete_post_meta( $order_id, 'ups_created_shipments_details_array');
	delete_post_meta( $order_id, 'ups_label_details_array');
	delete_post_meta( $order_id, 'ups_commercial_invoice_details' );
	delete_post_meta( $order_id, 'wf_ups_selected_service');

	wf_admin_notice::add_notice('Order #'. $order_id.': Cancellation of shipment completed successfully. You can re-initiate shipment.','notice');
	return true;
}

function get_order_label_details($order_id){
	$ups_label_details_array	=	get_post_meta( $order_id, 'ups_label_details_array', true );
	if(!empty($ups_label_details_array) && is_array($ups_label_details_array)){
		return $ups_label_details_array;
	}
	return false;
}

function ups_confirm_shipment($order_id){

		// Check if shipment created already
	if($this->get_order_label_details($order_id)){
		wf_admin_notice::add_notice('Order #'. $order_id.': Shipment is already created.','warning');			
		return false;
	}


		// Load UPS Settings.
	$ups_settings 		= 	get_option( 'woocommerce_'.WF_UPS_ID.'_settings', null ); 
		// API Settings
	$api_mode      		= 	isset( $ups_settings['api_mode'] ) ? $ups_settings['api_mode'] : 'Test';


	$endpoints			=	array(
		'Live'				=>	'https://www.ups.com/ups.app/xml/ShipConfirm',
		'Test'				=>	'https://wwwcie.ups.com/ups.app/xml/ShipConfirm',
		);
	$freight_endpoints			=	array(
		'Live'				=>	'https://www.ups.com/ups.app/xml/ShipConfirm',
		'Test'				=>	'https://wwwcie.ups.com/ups.app/xml/ShipConfirm',
		);		
	$endpoint	=	$endpoints[$api_mode];
	$freight_endpoint=$freight_endpoints[$api_mode];
	$order		=	$this->wf_load_order( $order_id );
	$requests 	= 	$this->wf_ups_shipment_confirmrequest($order);

	$created_shipments_details_array = array();

	foreach($requests as $request){
		$xml_request = str_replace( array( "\n", "\r" ), '', $request );
		if(!is_array($request))
		{
			$response = wp_remote_post( $freight_endpoint,
				array(
					'timeout'   => 70,
					'sslverify' => $this->ssl_verify,
					'body'      => $xml_request
					)
				);							
		}else
		{
			$xml_request = $this->modfiy_encoding($xml_request);
			$response = wp_remote_post( $endpoint,
				array(
					'timeout'   => 70,
					'sslverify' => $this->ssl_verify,
					'body'      => $xml_request
					)
				);
		}
		if ( is_wp_error( $response ) ) {
			$error_message = $response->get_error_message();
			wf_admin_notice::add_notice('Order #'. $order_id.': Sorry. Something went wrong: '.$error_message);			
			return false;
		}
		$req_arr=array();
		if(!is_array($request))
		{
			$req_arr=json_decode($request);
		}
		if(!is_array($request) && isset($req_arr->FreightShipRequest) && isset($req_arr->FreightShipRequest->Shipment->Service->Code)
			&& in_array($req_arr->FreightShipRequest->Shipment->Service->Code,array_keys($this->freight_services))
			   )				// For Freight Shipments  as it is JSON not Array
			{	try{
				$var=json_decode($response['body']);
				$pdf=$var->FreightShipResponse->ShipmentResults->Documents->Image->GraphicImage;

			}
			catch(Exception $e)
			{
				$this->wf_redirect( admin_url( '/post.php?post='.$post_id.'&action=edit') );
				exit;
			}
			$created_shipments_details = array();
			$shipment_id = (string)$var->FreightShipResponse->ShipmentResults->ShipmentNumber;

			$created_shipments_details["ShipmentDigest"] 			= (string)$var->FreightShipResponse->ShipmentResults->ShipmentNumber;

			$created_shipments_details_array[$shipment_id] = $created_shipments_details;

		}else
		{			
			$response_obj = simplexml_load_string( $response['body'] );

			$response_code = (string)$response_obj->Response->ResponseStatusCode;
			if( '0' == $response_code ) {
				$error_code = (string)$response_obj->Response->Error->ErrorCode;
				$error_desc = (string)$response_obj->Response->Error->ErrorDescription;


				wf_admin_notice::add_notice('Order #'. $order_id.': '.$error_desc.' [Error Code: '.$error_code.']');
				return false;
			}

			$created_shipments_details = array();
			$shipment_id = (string)$response_obj->ShipmentIdentificationNumber;

			$created_shipments_details["ShipmentDigest"] 			= (string)$response_obj->ShipmentDigest;

			$created_shipments_details_array[$shipment_id] = $created_shipments_details;
		}
	}
	update_post_meta( $order_id, 'ups_created_shipments_details_array', $created_shipments_details_array );	
	return true;
}

function ups_accept_shipment($order_id){
	$created_shipments_details_array	= get_post_meta($order_id, 'ups_created_shipments_details_array', true);
	if(empty($created_shipments_details_array) && !is_array($created_shipments_details_array)){
		return false;
	}

		// Load UPS Settings.
	$ups_settings 				= get_option( 'woocommerce_'.WF_UPS_ID.'_settings', null ); 
		// API Settings
	$api_mode      				= isset( $ups_settings['api_mode'] ) ? $ups_settings['api_mode'] : 'Test';
	$ups_user_id         		= isset( $ups_settings['user_id'] ) ? $ups_settings['user_id'] : '';
	$ups_password        		= isset( $ups_settings['password'] ) ? $ups_settings['password'] : '';
	$ups_access_key      		= isset( $ups_settings['access_key'] ) ? $ups_settings['access_key'] : '';
	$ups_shipper_number  		= isset( $ups_settings['shipper_number'] ) ? $ups_settings['shipper_number'] : '';
	$disble_shipment_tracking	= isset( $ups_settings['disble_shipment_tracking'] ) ? $ups_settings['disble_shipment_tracking'] : 'TrueForCustomer';
	$debug_mode      	        = isset( $ups_settings['debug'] ) && $ups_settings['debug'] == 'yes' ? true : false;


	$endpoints			=	array(
		'Live'				=>	'https://www.ups.com/ups.app/xml/ShipAccept',
		'Test'				=>	'https://wwwcie.ups.com/ups.app/xml/ShipAccept',
		);
	$ups_label_details_array = array();
	$endpoint	=	$endpoints[$api_mode];
	foreach($created_shipments_details_array as $shipment_id	=>	$created_shipments_details){
		if(isset($created_shipments_details['type']) && $created_shipments_details['type']=='freight'){
			continue;
		}			
		$xml_request = '<?xml version="1.0" encoding="UTF-8" ?>';
		$xml_request .= '<AccessRequest xml:lang="en-US">';
		$xml_request .= '<AccessLicenseNumber>'.$ups_access_key.'</AccessLicenseNumber>';
		$xml_request .= '<UserId>'.$ups_user_id.'</UserId>';
		$xml_request .= '<Password>'.$ups_password.'</Password>';
		$xml_request .= '</AccessRequest>'; 
		$xml_request .= '<?xml version="1.0" ?>';
		$xml_request .= '<ShipmentAcceptRequest>';
		$xml_request .= '<Request>';
		$xml_request .= '<TransactionReference>';
		$xml_request .= '<CustomerContext>'.$order_id.'</CustomerContext>';
		$xml_request .= '<XpciVersion>1.0001</XpciVersion>';
		$xml_request .= '</TransactionReference>';
		$xml_request .= '<RequestAction>ShipAccept</RequestAction>';
		$xml_request .= '</Request>';
		$xml_request .= '<ShipmentDigest>'.$created_shipments_details["ShipmentDigest"].'</ShipmentDigest>';
		$xml_request .= '</ShipmentAcceptRequest>';

		$xml_request	= apply_filters( 'xa_ups_accept_shipment_xml_request', $xml_request, $shipment_id, $order_id );	// To support vendor addon
		
		$xml_request = $this->modfiy_encoding($xml_request);

		if( $debug_mode ) {
			echo '<div style="background: #eee;overflow: auto;padding: 10px;margin: 10px;">SHIPMENT ACCEPT REQUEST: ';
			echo '<xmp>'.$xml_request.'</xmp></div>'; 
		}
		$response = wp_remote_post( $endpoint,
			array(
				'timeout'   => 70,
				'sslverify' => $this->ssl_verify,
				'body'      => $xml_request
				)
			);

		if( $debug_mode ) {
			echo '<div style="background:#ccc;background: #ccc;overflow: auto;padding: 10px;margin: 10px 10px 50px 10px;">SHIPMENT ACCEPT RESPONSE: ';
			echo '<xmp>'.print_r($response['body'],1).'</xmp></div>'; 
		}

		if ( is_wp_error( $response ) ) {
			$error_message = $response->get_error_message();
			wf_admin_notice::add_notice('Order #'. $order_id.': Sorry. Something went wrong: '.$error_message);
			return false;
		}

		$response_obj = simplexml_load_string( '<root>' . preg_replace('/<\?xml.*\?>/','', $response['body'] ) . '</root>' );	

		$response_code = (string)$response_obj->ShipmentAcceptResponse->Response->ResponseStatusCode;
		if('0' == $response_code) {
			$error_code = (string)$response_obj->ShipmentAcceptResponse->Response->Error->ErrorCode;
			$error_desc = (string)$response_obj->ShipmentAcceptResponse->Response->Error->ErrorDescription;

			wf_admin_notice::add_notice($error_desc.' [Error Code: '.$error_code.']');
			return false;
		}

		$package_results 			= $response_obj->ShipmentAcceptResponse->ShipmentResults->PackageResults;
		$ups_label_details			= array();
		$shipment_id_cs 			= '';

		if(isset($response_obj->ShipmentAcceptResponse->ShipmentResults->Form->Image)){
			$international_forms[$shipment_id]	=	array(
				'ImageFormat'	=>	(string)$response_obj->ShipmentAcceptResponse->ShipmentResults->Form->Image->ImageFormat->Code,
				'GraphicImage'	=>	(string)$response_obj->ShipmentAcceptResponse->ShipmentResults->Form->Image->GraphicImage,
				);
		}
			// Labels for each package.
		$index=0;
		foreach ( $package_results as $package_result ) {				
			$ups_label_details["TrackingNumber"]		= (string)$package_result->TrackingNumber;
			$ups_label_details["Code"] 					= (string)$package_result->LabelImage->LabelImageFormat->Code;
			$ups_label_details["GraphicImage"] 			= (string)$package_result->LabelImage->GraphicImage;			
			$ups_label_details_array[$shipment_id][]	= $ups_label_details;
			$shipment_id_cs 							.= $ups_label_details["TrackingNumber"].',';
			do_action('wf_label_generated_successfully',$shipment_id,$order_id,$ups_label_details["Code"],(string)$index,$ups_label_details["TrackingNumber"]);
			$index=$index+1;
		}

		$shipment_id_cs = rtrim( $shipment_id_cs, ',' );

		if( empty($ups_label_details_array) ) {
			wf_admin_notice::add_notice('Order #'. $order_id.': Sorry, An unexpected error occurred.');
			return false;
		}
		else {
			update_post_meta( $order_id, 'ups_label_details_array', $ups_label_details_array );

			if(isset($international_forms)){
				update_post_meta( $order_id, 'ups_commercial_invoice_details', $international_forms );
			}

				if( isset($created_shipments_details['return']) && $created_shipments_details['return'] ){// creating return label
					$return_label_ids = $this->wf_ups_return_shipment_accept($order_id, $created_shipments_details['return']);
					if( $return_label_ids && $shipment_id_cs ){
						$shipment_id_cs = $shipment_id_cs.','.$return_label_ids;
					}
				}
			}
			
			if( 'True' != $disble_shipment_tracking) {
				// Update Tracking Info
				$ups_tarcking	=	new WF_Shipping_UPS_Tracking();
				$ups_tarcking->get_shipment_info( $order_id, $shipment_id_cs );
			}
			wf_admin_notice::add_notice('Order #'. $order_id.': Shipment accepted successfully. Labels are ready for printing.','notice');
			
		}
		return true;
	}
	
	function get_order_label_links($order_id){
		$links	=	array();
		$created_shipments_details_array 	= get_post_meta( $order_id, 'ups_created_shipments_details_array', true );
		if(!empty($created_shipments_details_array)){
			$ups_label_details_array = $this->get_order_label_details($order_id);
			$ups_commercial_invoice_details = get_post_meta( $order_id, 'ups_commercial_invoice_details', true );
			
			foreach($created_shipments_details_array as $shipmentId => $created_shipments_details){
				$index = 0;
				if( isset($ups_label_details_array[$shipmentId]) && is_array( $ups_label_details_array[$shipmentId] ) ){
					foreach ( $ups_label_details_array[$shipmentId] as $ups_label_details ) {
						$label_extn_code 	= $ups_label_details["Code"];
						$tracking_number 	= isset( $ups_label_details["TrackingNumber"] ) ? $ups_label_details["TrackingNumber"] : '';
						$links[] 			= admin_url( '/?wf_ups_print_label='.base64_encode( $shipmentId.'|'.$order_id.'|'.$label_extn_code.'|'.$index.'|'.$tracking_number ) );
						
						
						// Return Label Link
						if(isset($created_shipments_details['return'])&&!empty($created_shipments_details['return'])){
							$return_shipment_id=current(array_keys($created_shipments_details['return'])); // only one return label is considered now
							$ups_return_label_details_array = get_post_meta( $order_id, 'ups_return_label_details_array', true );
							if(is_array($ups_return_label_details_array)&&isset($ups_return_label_details_array[$return_shipment_id])){// check for return label accepted data
								$ups_return_label_details=$ups_return_label_details_array[$return_shipment_id];
								if(is_array($ups_return_label_details)){
									$ups_return_label_detail=current($ups_return_label_details);
									$label_index=0;// as we took only one label so index is zero
									$links[] = admin_url( '/?wf_ups_print_label='.base64_encode( $return_shipment_id.'|'.$order_id.'|'.$label_extn_code.'|'.$label_index.'|return' ) );
									
								}
							}
						}
						$index = $index + 1;
					}
				}
				
				if(isset($ups_commercial_invoice_details[$shipmentId])){
					$links[]	=	admin_url( '/?wf_ups_print_commercial_invoice='.base64_encode($order_id.'|'.$shipmentId));
				}
			}
		}
		return $links;
	}
	
	function label_printing_buttons($order){
		$order = $this->wf_load_order( $order );

		$actions	=	array();
		$labels	=	$this->get_order_label_links($order->id);
		if(is_array($labels)){
			foreach($labels as $label_no => $label_link){
				$actions['print_label'.$label_no]	=	array(
					'url'	=>	$label_link,
					'name'	=>	__('Print Label', 'ups-woocommerce-shipping'),
					'action'=>	'wf-print-label'
					);
			}
		}
		
		foreach ( $actions as $action ) {
			printf( '<a class="button tips %s" href="%s" data-tip="%s" target="_blank">%s</a>', esc_attr( $action['action'] ), esc_url( $action['url'] ), esc_attr( $action['name'] ), esc_attr( $action['name'] ) );
		}
		
	}
	
	/*
	 * function to convert encoding of xml request
	 * 
	 * @ since 3.2.7
	 * @ access private
	 * @ param xmlrequest
	 * @ return xmlrequest
	 */
	private function modfiy_encoding($xmlrequest)
	{
		$latin_encoded_xmlrequest = '';
		if($this->enable_latin_encoding) {
			$latin_encoded_xmlrequest = iconv('UTF-8', 'ISO-8859-2', $xmlrequest);
			if($latin_encoded_xmlrequest) {
				return str_replace("UTF-8","ISO-8859-2",$latin_encoded_xmlrequest);
			}
		}
		return $xmlrequest;
	}
	
	/*
	 * function to check box to product page to say that the product is pre packed
	 *
	 * @ since 3.3.1
	 * @ access public
	 */
	public function wf_ups_custome_product_page() 
	{
		woocommerce_wp_checkbox( array(
			'id' => '_wf_pre_packed_product',
			'label' => __('Pre packed product','ups-woocommerce-shipping'),
			'description' => __('Check this if the item comes in boxes. It will consider as a separate package and ship in its own box.', 'ups-woocommerce-shipping'),
			'desc_tip' => 'true',
			) );
	}

	/*
	 * function to save the pre packed option added to the product
	 *
	 * @ since 3.3.1
	 * @ access public
	 * @ params post_id
	 */
	public function wf_ups_save_custome_product_fields( $post_id ) {
		if ( isset( $_POST['_wf_pre_packed_product'] ) ) {
			update_post_meta( $post_id, '_wf_pre_packed_product', esc_attr( $_POST['_wf_pre_packed_product'] ) );
		} else {
			update_post_meta( $post_id, '_wf_pre_packed_product', '' );
		}
	}

	public function wf_variation_settings_fields( $loop, $variation_data, $variation ){
		$is_pre_packed_var = get_post_meta( $variation->ID, '_wf_pre_packed_product_var', true );
		if( empty( $is_pre_packed_var ) ){
			$is_pre_packed_var = get_post_meta( wp_get_post_parent_id($variation->ID), '_wf_pre_packed_product', true );
		}
		woocommerce_wp_checkbox( array(
			'id' => '_wf_pre_packed_product_var[' . $variation->ID . ']',
			'label' => __(' Pre packed product', 'ups-woocommerce-shipping'),
			'description' => __('Check this if the item comes in boxes. It will override global product settings', 'ups-woocommerce-shipping'),
			'desc_tip' => 'true',
			'value'         => $is_pre_packed_var,
			) );
	}

	public function wf_save_variation_settings_fields( $post_id ){
		$checkbox = isset( $_POST['_wf_pre_packed_product_var'][ $post_id ] ) ? 'yes' : 'no';
		update_post_meta( $post_id, '_wf_pre_packed_product_var', $checkbox );
	}
	
	/**
	 * To calculate the shipping cost on order page.
	 */
	public function wf_ups_generate_packages_rates() {
		if( ! $this->wf_user_check() ) {
			echo "You don't have admin privileges to view this page.";
			exit;
		}
		
		$post_id				= base64_decode($_GET['wf_ups_generate_packages_rates']);
		$length_arr				= explode(',',$_GET['length']);
		$width_arr				= explode(',',$_GET['width']);
		$height_arr				= explode(',',$_GET['height']);
		$weight_arr				= explode(',',$_GET['weight']);
		$insurance_arr			= explode(',',$_GET['insurance']);
		$get_stored_packages	= get_post_meta( $post_id, '_wf_ups_stored_packages', true );
		$package_data			= $get_stored_packages;
		$rates 					= array();
		
		$shipping_obj	    	= new WF_Shipping_UPS();
		$order		    		= wc_get_order($post_id);
		

		$shipping_address	= $order->get_address();
		$address_package    = array(
			'destination'	=> array(
				'address'	=>	$shipping_address['address_1'].' '.$shipping_address['address_2'],
				'country'	=>	$shipping_address['country'],
				'state'		=>	$shipping_address['state'],
				'postcode'	=>	$shipping_address['postcode'],
				'city'		=>	$shipping_address['city'],

			),
		);

		foreach ($get_stored_packages as $package_key => $package) {
			if(!empty($package))
			{
				foreach ($package as $key => $value) {
					if( ! empty($weight_arr[$package_key] ) ) {
						$package_data[$package_key][$key]['PackageWeight']['Weight']			= $weight_arr[$package_key];
						$package_data[$package_key][$key]['PackageWeight']['UnitOfMeasurement']['Code']	= $shipping_obj->weight_unit;
					}
					else {
						wf_admin_notice::add_notice( sprintf( __( 'UPS rate request failed - Weight is missing. Aborting.', 'ups-woocommerce-shipping' ) ), 'error' );
						// Redirect to same order page
						wp_redirect( admin_url( '/post.php?post='.$post_id.'&action=edit') );
						exit;	    //To stay on same order page
					}

					if( ! empty($length_arr[$package_key]) && ! empty($width_arr[$package_key]) && ! empty($height_arr[$package_key]) ) {
						$package_data[$package_key][$key]['Dimensions'] = array(
							'UnitOfMeasurement'	=> array( 'Code' => $shipping_obj->dim_unit ),
							'Length'		=>  $length_arr[$package_key],
							'Width'			=>  $width_arr[$package_key],
							'Height'		=>  $height_arr[$package_key],
						);
					}
					else {
						unset($package_data[$package_key][$key]['Dimensions']);
					}
					
					if( ! empty($insurance_arr[$package_key]) ) {
						$package_data[$package_key][$key]['PackageServiceOptions']['InsuredValue'] = array(
							'CurrencyCode'	=>  $shipping_obj->get_ups_currency(),
							'MonetaryValue'	=>  $insurance_arr[$package_key],
						);
					}
				}
			}
		}

		if( $get_stored_packages != $package_data) {
			update_post_meta( $post_id, '_wf_ups_stored_packages', $package_data );	// Update the packages in database
		}

		$rate_request = $shipping_obj->get_rate_requests( $package_data, $address_package );
		$rates =  $shipping_obj->process_result( $shipping_obj->get_result($rate_request) );

		$custom_services = $shipping_obj->custom_services;

		// Get rates for surepost services only
		foreach ( $this->ups_surepost_services as $service_code ) {
			if( $custom_services[$service_code]['enabled'] ) {			// If surepost service code enabled
				$rate_requests	= $shipping_obj->get_rate_requests( $package_data, $address_package, 'surepost', $service_code );
				$rates			= array_merge( $rates, $shipping_obj->process_result( $shipping_obj->get_result($rate_requests, 'surepost') ) );
			}
		}
		update_post_meta( $post_id, 'wf_ups_generate_packages_rates_response', $rates );
		// Redirect to same order page
		wp_redirect( admin_url( '/post.php?post='.$post_id.'&action=edit#CyDUPS_metabox') );
		exit;	    //To stay on same order page
	}

	/**
	*  Generate return label if label has been created previously
	*/
	public function xa_generate_return_label(){
		
		$order_id 				= $_GET['xa_generate_return_label'];
		$return_package_index 	= 0;
		$order 					= $this->wf_load_order($order_id);
		$shipment_id_cs 		= $this->xa_get_meta_key( $order, 'ups_shipment_ids', true, 'order');
		$shipments 				= $this->xa_get_meta_key( $order, 'ups_created_shipments_details_array', true, 'order');

		// Confirm return shipment
		foreach( $shipments as $shipment_id => $shipment ) {
			$return_label = $this->wf_ups_return_shipment_confirm($shipment_id,$return_package_index);
			if( !empty($return_label) ){
				$created_shipments_details_array[$shipment_id]['return'] = $return_label;
			}
			$return_package_index++;
		}
		update_post_meta( $order_id, 'ups_created_shipments_details_array', $created_shipments_details_array );

		// Accept Return Shipment
		foreach( $created_shipments_details_array as $shipment_id => $created_shipments_details ) {
			if( ! empty($created_shipments_details['return']) ) {
				$return_label_ids = $this->wf_ups_return_shipment_accept( $order_id, $created_shipments_details['return'] );
				if( $return_label_ids ) {
					$shipment_id_cs = $shipment_id_cs.','.$return_label_ids;
				}
			}
		}

		// Update tracking info
		$ups_tarcking	=	new WF_Shipping_UPS_Tracking();
		$ups_tarcking->get_shipment_info( $order_id, $shipment_id_cs );
		if( $this->debug ) {
			exit();
		}
		wp_redirect( admin_url( '/post.php?post='.$order_id.'&action=edit#CyDUPS_metabox') );
	}
}
new WF_Shipping_UPS_Admin();

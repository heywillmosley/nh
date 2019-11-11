<?php
	$shipping_setting =get_option('woocommerce_wf_shipping_ups_settings');

	if(isset($shipping_setting['automate_package_generation']) && $shipping_setting['automate_package_generation']=='yes' )
	{
		add_action( 'woocommerce_order_status_changed', 'wf_automatic_package_and_label_generation_ups',100,4 );
	}
	function wf_automatic_package_and_label_generation_ups( $order_id, $order_status_old, $order_status_new, $order )
	{
		$ups_shipping_setting =get_option('woocommerce_wf_shipping_ups_settings');
		$allowed_order_status = apply_filters( 'xa_automatic_label_generation_allowed_order_status', array('processing'), $order_status_new, $order_id );	// Allowed order status for automatic label generation
		
		if( ! in_array($order_status_new, $allowed_order_status) ) {
			if($ups_shipping_setting['debug'] == 'yes') {
				WC_Admin_Meta_Boxes::add_error( __( "Since Order Status is ", 'ups-woocommerce-shipping' ).$order_status_new.__( ". Automatic label generation has been suspended.", 'ups-woocommerce-shipping' ) );
			}
			return;
		}
		if( ! ($order instanceof WC_Order) ) {
			$order = new WC_Order($order_id);
		}
		
		$order_items = $order->get_items();
		if( empty($order_items) && class_exists('WC_Admin_Meta_Boxes') ) {
			WC_Admin_Meta_Boxes::add_error( __( 'UPS - No product Found. Please check the products in order.', 'ups-woocommerce-shipping' ) );
			return;
		}
		//  Automatically Generate Packages		
		$current_minute=(integer)date('i');
		$package_url=admin_url( '/post.php?wf_ups_generate_packages='.base64_encode('|'.$order_id).'&auto_generate='.md5($current_minute) );
		$ch = curl_init();
		curl_setopt($ch,CURLOPT_URL,$package_url);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		$output=curl_exec($ch);
		if( ! $output && curl_errno($ch) ) {
			WC_Admin_Meta_Boxes::add_error( __( 'UPS - Curl error while automatic package generation. Error number - ', 'ups-woocommerce-shipping' ). curl_errno($ch) );
		}
		curl_close($ch);
	}
	if( isset($shipping_setting['automate_label_generation']) && $shipping_setting['automate_label_generation']=='yes' )
	{	
		add_action('wf_after_package_generation','wf_auto_genarate_label_ups',2,2);
	}

	if( !function_exists('xa_get_shipping_method') ){
		function xa_get_shipping_method($order_id){
			if(!$order_id)
				return false;

			$order = new WC_Order( $order_id );
			$order_shipping_method = current( $order->get_items( 'shipping' ) );
			$order_shipping_method = ( WC()->version > '2.7' ) ? ( is_object($order_shipping_method) ? $order_shipping_method->get_method_id() : '' ) : ( isset($order_shipping_method['method_id']) ? $order_shipping_method['method_id'] : '' );
			if( ! empty($order_shipping_method) ) {
				$service_code=explode(':',$order_shipping_method);
				if( $service_code[0] == WF_UPS_ID ) {
					return $service_code[1];
				}
			}
			$settings = get_option( 'woocommerce_'.WF_UPS_ID.'_settings', null );

			//Origin coutry without state
			$country = explode( ':', $settings['origin_country_state'] );	//It may be in Country : State format or only country
			$origin_country = array_shift( $country ) ;

			if( $origin_country == $order->get_shipping_country() ){
				if( !empty($settings['default_dom_service']) ){
					return $settings['default_dom_service'];	//Return default service for domestic
				}
			}else{
				if( !empty($settings['default_int_service']) ){
					return $settings['default_int_service'];	//Return default service for international
				}
			}
		}
	}

	function wf_auto_genarate_label_ups($order_id,$package_data)
	{	
		/// Automatically Generate Labels
		$current_minute=(integer)date('i');
		$id= base64_encode('|'.$order_id);
		$package_url=admin_url( '/post.php?wf_ups_shipment_confirm='.$id.'&auto_generate='.md5($current_minute) );
		
		$service_code = xa_get_shipping_method($order_id);
		
		$weight=array();
		$length=array();
		$width=array();
		$height=array();
		$services=array();
		foreach($package_data as $key=>$val)
		{	
			foreach($val as $key2=>$package)
			{	
				if(isset($package['PackageWeight'])) $weight[]=$package['PackageWeight']['Weight'];

					$length[]	= ! empty($package['Dimensions']['Length']) ? $package['Dimensions']['Length'] : 0;
					$width[]	= ! empty($package['Dimensions']['Width']) ? $package['Dimensions']['Width'] : 0;
					$height[]	= ! empty($package['Dimensions']['Height']) ? $package['Dimensions']['Height'] : 0;
					
				if(isset($package['PackageServiceOptions']) && isset($package['PackageServiceOptions']['InsuredValue']) && isset($package['PackageServiceOptions']['InsuredValue']['MonetaryValue']))
				{
					$insurance[]=$package['PackageServiceOptions']['InsuredValue']['MonetaryValue'];
				}
				else{
					$insurance[]=0;
				}
				$services[]=$service_code;
			}
		}
		$package_url.='&weight=["'.implode('","',$weight).'"]';
		$package_url.='&length=["'.implode('","',$length).'"]';
		$package_url.='&width=["'.implode('","',$width).'"]';
		$package_url.='&height=["'.implode('","',$height).'"]';
		$package_url.='&service=["'.implode('","',$services).'"]';
		$package_url.='&insurance=["'.implode('","',$insurance).'"]';
		$ch = curl_init();
		@curl_setopt($ch,CURLOPT_URL,$package_url);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		@$output=curl_exec($ch);
		if( ! $output && curl_errno($ch) ) {
			WC_Admin_Meta_Boxes::add_error( __( 'UPS - Curl error while automatic label generation. Error number - ', 'ups-woocommerce-shipping' ). curl_errno($ch) );
		}
		curl_close($ch);

	}
	if(isset($shipping_setting['auto_email_label']) && $shipping_setting['auto_email_label']=='yes' )
	{	
		add_action('wf_label_generated_successfully','wf_after_label_generation_ups',3,5);
		
		
	}


	function wf_after_label_generation_ups($shipment_id,$order_id,$label_extn_code,$index,$tracking_number)
	{
		$shipping_setting2 =get_option('woocommerce_wf_shipping_ups_settings');
			
		
		if(isset($shipping_setting2['email_content']) && !empty($shipping_setting2['email_content']))
		{
			$emailcontent=$shipping_setting2['email_content'];
		}
		else
		{
			$emailcontent= ' ';
		}
		unset($shipping_setting2);
		if(!empty($shipment_id))
		{
			$order = new WC_Order( $order_id );
			$to_emails = array($order->get_billing_email());
			
			$to_emails = apply_filters( 'xa_add_email_addresses_to_send_label',$to_emails, $shipment_id, $order, 10,3);
			
			$subject = 'Shipment Label For Your Order';
			$img_url=admin_url('/post.php?wf_ups_print_label='.base64_encode( $shipment_id.'|'.$order_id.'|'.$label_extn_code.'|'.$index.'|'.$tracking_number ));
			$body = "Please Download the label
			<html>
			<body>	<div>".$emailcontent."</div>
			
			<a href='$img_url'><input type='button' value='Download Shipping Label here' class='button' /> </a></br>
			</body>
			</html>
					";
			$headers = array('Content-Type: text/html; charset=UTF-8');
			foreach($to_emails as $to)
			{
			    wp_mail( $to, $subject, $body, $headers );
			}
		}
	
	}

	if(isset($shipping_setting['allow_label_btn_on_myaccount']) && $shipping_setting['allow_label_btn_on_myaccount']=='yes' )
	{	
		add_action('woocommerce_view_order','wf_add_view_shippinglabel_button_on_myaccount_order_page_ups');
	}
	function wf_add_view_shippinglabel_button_on_myaccount_order_page_ups($order_id)
	{	
			$created_shipments_details_array 	= get_post_meta( $order_id, 'ups_created_shipments_details_array', true );
			$ups_label_details_array = get_post_meta( $order_id, 'ups_label_details_array', true );
			$ups_commercial_invoice_details = get_post_meta( $order_id, 'ups_commercial_invoice_details', true );
			if(!empty($ups_label_details_array) && is_array($ups_label_details_array)){
				foreach ( $created_shipments_details_array as $shipmentId => $created_shipments_details ){
					/////
					echo __( 'Shipment ID: ', 'ups-woocommerce-shipping' ).'</strong>'.$shipmentId.'<hr style="border-color:#0074a2">';
					$target_val = "_self";
					// Multiple labels for each package.
					$index = 0;
					if( !empty($ups_label_details_array[$shipmentId]) ){
						foreach ( $ups_label_details_array[$shipmentId] as $ups_label_details ) {
							$label_extn_code 	= $ups_label_details["Code"];
							$tracking_number 	= isset( $ups_label_details["TrackingNumber"] ) ? $ups_label_details["TrackingNumber"] : '';
							$download_url 		= admin_url( '/?wf_ups_print_label='.base64_encode( $shipmentId.'|'.$order_id.'|'.$label_extn_code.'|'.$index.'|'.$tracking_number ) );
							$post_fix_label		= '';
							
							if( count($ups_label_details_array) > 1 ) {
								$post_fix_label = '#'.( $index + 1 );
							}
							?>
							<strong><?php _e( 'Tracking No: ', 'ups-woocommerce-shipping' ); ?></strong><a href="http://wwwapps.ups.com/WebTracking/track?track=yes&trackNums=<?php echo $ups_label_details["TrackingNumber"] ?>" target="_blank"><?php echo $ups_label_details["TrackingNumber"] ?></a><br/>
							<a class="button button-primary tips" href="<?php echo $download_url; ?>" data-tip="<?php _e( 'Print Label ', 'ups-woocommerce-shipping' );echo $post_fix_label; ?>" target="<?php echo $target_val; ?>"><?php _e( 'Print Label ', 'ups-woocommerce-shipping' );echo $post_fix_label ?></a>
							<hr style="border-color:#0074a2">
							<?php						
							// Return Label Link
							if(isset($created_shipments_details['return'])&&!empty($created_shipments_details['return'])){
								$return_shipment_id = current(array_keys($created_shipments_details['return'])); // only one return label is considered now
								$ups_return_label_details_array = get_post_meta( $order_id, 'ups_return_label_details_array', true );
								if( is_array($ups_return_label_details_array) && isset($ups_return_label_details_array[$return_shipment_id]) ){// check for return label accepted data
									$ups_return_label_details = $ups_return_label_details_array[$return_shipment_id];
									if( is_array($ups_return_label_details) ){
										$ups_return_label_detail = current($ups_return_label_details);
										$label_index=0;// as we took only one label so index is zero
										$return_download_url = admin_url( '/?wf_ups_print_label='.base64_encode( $return_shipment_id.'|'.$order_id.'|'.$label_extn_code.'|'.$label_index.'|return' ) );
										?>
										<strong><?php _e( 'Tracking No: ', 'ups-woocommerce-shipping' ); ?></strong><a href="http://wwwapps.ups.com/WebTracking/track?track=yes&trackNums=<?php echo $ups_return_label_detail["TrackingNumber"] ?>" target="_blank"><?php echo $ups_return_label_detail["TrackingNumber"] ?></a><br/>
										</br><a href="<?php echo $return_download_url; ?>" ><input type="button" value="Download Shipping Label here" class="button" /> </a> </br>
										<?php
									}
								}
							}
							
							
							// EOF Return Label Link						
							$index = $index + 1;
						}
					}
					if(isset($ups_commercial_invoice_details[$shipmentId])){
						echo '<a class="button button-primary tips" target="'.$target_val.'" href="'.admin_url( '/?wf_ups_print_commercial_invoice='.base64_encode($order_id.'|'.$shipmentId)).'" data-tip="'.__('Print Commercial Invoice', 'ups-woocommerce-shipping').'">'.__('Commercial Invoice', 'ups-woocommerce-shipping').'</a></br>';
					}
				}

			}

	}
	unset($shipping_setting);
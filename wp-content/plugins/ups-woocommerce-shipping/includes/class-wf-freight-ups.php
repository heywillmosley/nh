<?php
Class wf_freight_ups{
	Private $parent;
	
	function __construct($parent)
	{
		$this->parent=$parent;
	}
	
	/**
	 * 
	 * @param array $package Calculate_shipping packages
	 * @param int $code Service Code
	 * @param array $ups_packages Array of ups packages after packing algorithms has been applied
	 * @return array Freight rate requests
	 */
	function get_rate_request( $package, $code, $ups_packages )
	{
		
		$json_req=array();
		$commodity = $this->get_commodity_from_packages( $ups_packages );
		
		$json_req['UPSSecurity'] = array(
				"UsernameToken"	=>	array(
						"Username"	=>	$this->parent->user_id,
						"Password"	=>	str_replace( '&', '&amp;', $this->parent->password )
				),
				"ServiceAccessToken"	=>	array(
						"AccessLicenseNumber"	=>	$this->parent->access_key
				),
		);
		$address = '';
		if( !empty($package['destination']['address_1']) ){
			$address = $package['destination']['address_1'];
		}elseif( !empty($package['destination']['address']) ){
			$address = $package['destination']['address'];
		}
		$destination_city = strtoupper( $package['destination']['city'] );
		$destination_country = "";
		if ( ( "PR" == $package['destination']['state'] ) && ( "US" == $package['destination']['country'] ) ) {		
				$destination_country = "PR";
		} else {
				$destination_country = $package['destination']['country'];
		}
		foreach( $ups_packages as $key => $ups_package ) {
			$json_req['FreightRateRequest']	=	array(
					"Request"	=>	array(
							"RequestOption"=>"1",
							"TransactionReference"=>array("TransactionIdentifier"=>"Freight Rate Request")
					),
					"ShipFrom"	=>	array(
							"Name"		=>$this->parent->ups_display_name,
							"Address"	=>array(
									"AddressLine"		=> $this->parent->origin_addressline,
									"City"				=> $this->parent->origin_city,
									"StateProvinceCode"	=> $this->parent->origin_state,
									"PostalCode"		=> $this->parent->origin_postcode,
									"CountryCode"		=> $this->parent->origin_country,
							),                                                            
					),
				   "ShipperNumber"	=>	$this->parent->shipper_number,
				   "ShipTo"	=>	array(
							"Name"=>"Receipient",
							"Address"=>array(
									"AddressLine"		=> $address,
									"City"				=> $destination_city,
									"StateProvinceCode"	=> $package['destination']['state'],
									"PostalCode"		=> $package['destination']['postcode'],
									"CountryCode"		=> $destination_country,
							),
					),
				   "PaymentInformation"=>array(
							"Payer"=>array(
									"Name"		=> !empty($this->parent->ups_display_name)?$this->parent->ups_display_name:"Shipper",
									"Address"	=> array(
											"AddressLine"		=> $this->parent->origin_addressline,
											"City"				=> $this->parent->origin_city,
											"StateProvinceCode"	=> $this->parent->origin_state,
											"PostalCode"		=> $this->parent->origin_postcode,
											"CountryCode"		=> $this->parent->origin_country,
									),
									"ShipperNumber"	=> $this->parent->shipper_number,
							),
							"ShipmentBillingOption"	=> array(
									"Code"	=> (string)$this->parent->freight_billing_option_code
							)
					),
				   "Service"	=> array(
							"Code"	=> "$code"
					),
				   "ShipmentRatingOptions"=>array("NegotiatedRatesIndicator"=>"0"),		// currently this is not working with freight
				   "HandlingUnitOne"	=> array(
							"Quantity"	=> "1",
							"Type"		=> array(
									"Code"	=> (string)$this->parent->freight_handling_unit_one_type_code
							)
					),
					 "Commodity"=> $commodity[$key],
			);
			
			$requests[$key] = json_encode($json_req);
		}
		return $requests;
	}
	
	/**
	 * Get the Commodities from UPS packages .
	 * @param array $package UPS packages
	 * @return array Array of commodity for every packages.
	 */
	function get_commodity_from_packages( $ups_packages) {

	    $commodities = array();
		
		if( is_array($ups_packages) ) {
			foreach( $ups_packages as $key => $package ) {
				if( ! empty($package['Package']['PackageWeight']) ) {
						$commodity	=	array(
								'Description'	=>'Freight',
								'Weight'	=>array(
										'Value'				=> (string) round($package['Package']['PackageWeight']['Weight'], 2),
										'UnitOfMeasurement'	=> array('Code'=>(string) $package['Package']['PackageWeight']['UnitOfMeasurement']['Code'] )
								),
						);
						
						if( ! empty($package['Package']['Dimensions']) ) {
							$commodity['Dimensions']	=	array(
									'UnitOfMeasurement'	=>	array(
											'Code'	=>	(string)$package['Package']['Dimensions']['UnitOfMeasurement']['Code']
									),
									'Length'	=>	(string) round($package['Package']['Dimensions']['Length'], 2),
									'Width'		=>	(string) round($package['Package']['Dimensions']['Width'], 2),
									'Height'	=>	(string) round($package['Package']['Dimensions']['Height'], 2)
							);
						}
						$commodity['NumberOfPieces']	= "1";
						$commodity['PackagingType']		= array(
								'Code'=>(string)$this->parent->freight_package_type_code
						);
						$commodity['FreightClass']		= (string)$this->parent->freight_class;

						$commodities[$key][]			= $commodity;					//package_key	=> corresponding Commodity
				}
			}
		}
		
		return $commodities;
	}
	
	/**
	 * Get the Freight Confirm Shipment request.
	 * @param array $shipment_package_data Shipment package
	 * @return json Freight request for confirm shipment 
	 */
	public function create_shipment_request( $shipment_package_data )
	{
		$json_req=array();

		$query_string	= explode( '|', base64_decode($_GET['wf_ups_shipment_confirm']) );
		$order_id		= end( $query_string );
		$order			= wc_get_order($order_id);

		if( $this->parent->settings['ship_from_address'] == 'origin_address') {
			$from_address	= $this->get_shop_address();
			$to_address		= $this->get_order_address($order);
		}
		else {
			$from_address	= $this->get_order_address($order);
			$to_address		= $this->get_shop_address();
		}

		$shipping_service		= in_array( $shipment_package_data['shipping_service'], array( 308, 309, 334, 349 )) ? $shipment_package_data['shipping_service'] : null ;
		$shipping_service_name	= $this->parent->freight_services[$shipping_service];

		$json_req['UPSSecurity']=array(
                               "UsernameToken"=>array(
                                                            "Username"=>$this->parent->settings['ups_display_name'],
                                                            "Password"=>str_replace( '&', '&amp;', $this->parent->settings['password'] )
                                                        ),
                               "ServiceAccessToken"=>array(
                                                            "AccessLicenseNumber"=>$this->parent->settings['access_key']
                                                        ),
                               
                               );
                        
		$json_req['FreightShipRequest']	= array(
				"Request"	=> array(
						"RequestOption"=>"1",
						"TransactionReference"=>array("TransactionIdentifier"=>"Freight Rate Request")
				),
				"Shipment"	=> array(
						"ShipFrom"=>array(
								"Name"=>htmlspecialchars( $from_address['name'] ),
								'Address'		=>	array(
										'AddressLine'		=>	htmlspecialchars( $from_address['address_1'] ). " , ". htmlspecialchars($from_address['address_2']),
										'City'				=>	$from_address['city'],
										'StateProvinceCode'	=>	$from_address['state'],
										'PostalCode'		=>	$from_address['postcode'],
										'CountryCode'		=>	$from_address['country'],
								),
								'AttentionName'	=>	htmlspecialchars( $from_address['name'] ),
								'Phone'=>array(
										'Number'	=>	(strlen($from_address['phone']) < 10) ? '0000000000' :  htmlspecialchars( $from_address['phone'] )
								),
						),
					   "ShipperNumber"	=> $this->parent->settings['shipper_number'],
					   "ShipTo"	=> array(
								'Name'	=>	htmlspecialchars( $to_address['name'] ),
								'Address'		=>	array(
										'AddressLine'		=>	htmlspecialchars( $to_address['address_1'] ). " , " .htmlspecialchars( $to_address['address_2'] ),
										'City'				=>	$to_address['city'],
										'StateProvinceCode'=>	$to_address['state'],
										'PostalCode'		=>	$to_address['postcode'],
										'CountryCode'		=>	$to_address['country'],
								),
								'AttentionName'	=>	htmlspecialchars( $to_address['name'] ),												
								'Phone'=>array(
										'Number'	=>	(strlen( $to_address['phone'] ) < 10) ? '0000000000' : htmlspecialchars( $to_address['phone'] )
								),
						),
					   "PaymentInformation"=>array(
								"Payer"=>array(
										'Name'			=>	htmlspecialchars( $this->parent->settings['ups_user_name'] ),
										'Address'		=>	array(
												'AddressLine'			=>	htmlspecialchars( $from_address['address_1'] ).' , '.htmlspecialchars( $from_address['address_2'] ),
												'City'					=>	$from_address['city'],
												'StateProvinceCode'		=>	$from_address['state'],
												'PostalCode'			=>	$from_address['postcode'],
												'CountryCode'			=>	$from_address['country'],
										),
										'ShipperNumber'	=>	$this->parent->settings['shipper_number'],
										'AttentionName'	=>	htmlspecialchars( $this->parent->settings['ups_display_name'] ),
										'Phone'=>array(
												'Number'	=>	(strlen($from_address['phone']) < 10) ? '0000000000' :  htmlspecialchars( $from_address['phone'] )
										),
								),
								"ShipmentBillingOption"=>array(
										"Code"	=> "10"
								)
						),
					   "Service"=>array(
								'Code'			=>	"$shipping_service",
								'Description'	=>	htmlspecialchars( $shipping_service_name ),	
						),
					   "ShipmentRatingOptions"	=> array(
								"NegotiatedRatesIndicator"	=> "0"				// currently this is not working with freight
						),
					   "HandlingUnitOne"=>array(
								"Quantity"	=> "1",
								"Type"		=> array(
										"Code"	=> "PLT"
								)
						),
						"Commodity"	=> array(),
						"Documents"	=> array(
								"Image"	=> array(
										"Type"	=> array(
												"Code"	=> "20"
										),
										"Format"	=> array(
												"Code"	=> "01"
										)
								)
						)
				),
		);
		
		$commodities=array();
		$index=0;
		foreach( $shipment_package_data['packages'] as $package )
		{
				$package=$package['Package'];
				$commodity	=	array(
					'Description'	=>'Package '.($index +1),
					'Weight'	=>array(
						'Value'				=> (string) round($package['PackageWeight']['Weight'], 2),
						'UnitOfMeasurement'	=> array(
							'Code'	=>	(string) $package['PackageWeight']['UnitOfMeasurement']['Code'])
					),
				);
				
				try{
					if(isset($package['Dimensions']['UnitOfMeasurement']) && isset($package['Dimensions']['UnitOfMeasurement']['Code']) )
					{
						$unit=$package['Dimensions']['UnitOfMeasurement']['Code'];
					}else
					{
						$unit="IN";
					}

				$commodity['Dimensions']	=	array(
						'UnitOfMeasurement'	=>	array(
								'Code'	=>	(string) $unit
						),
						'Length'	=>	round($package['Dimensions']['Length'], 2),
						'Width'		=>	round($package['Dimensions']['Width'], 2),
						'Height'	=>	round($package['Dimensions']['Height'], 2)
				);
				}catch(Exception $ex){	}					

				$commodity['NumberOfPieces']	= "1";

				$commodity['PackagingType']	=array('Code'=>"PLT");
				if(isset($_GET['FreightPackagingType']) && !empty($_GET['FreightPackagingType']))
				{
					$commodity['PackagingType']	=array('Code'=>$_GET['FreightPackagingType']);
				}
				$commodity['FreightClass']	="50";
				if(isset($_GET['FreightClass']) && !empty($_GET['FreightClass']))
				{
					$commodity['FreightClass']	=$_GET['FreightClass'];
				}
				$commodities[]=$commodity;			

		}
					
		$json_req['FreightShipRequest']['Shipment']['Commodity'] = $commodities;
		$json_req['FreightShipRequest']['Shipment']['TimeInTransitIndicator']="";

		if(isset($_GET['PickupInstructions']) && !empty($_GET['PickupInstructions']))
		{
			$json_req['FreightShipRequest']['Shipment']['PickupInstructions']=$_GET['PickupInstructions'];
		}

		if(	(isset($_GET['HolidayPickupIndicator']) && $_GET['HolidayPickupIndicator']===true) ||
			(isset($_GET['InsidePickupIndicator']) && $_GET['HolidayPickupIndicator']===true)  ||
			(isset($_GET['ResidentialPickupIndicator']) && $_GET['HolidayPickupIndicator']===true)  ||
			(isset($_GET['WeekendPickupIndicator']) && $_GET['HolidayPickupIndicator']===true)  ||
			(isset($_GET['LiftGateRequiredIndicator']) && $_GET['HolidayPickupIndicator']===true)  ||
			(isset($_GET['LimitedAccessPickupIndicator'])  && $_GET['HolidayPickupIndicator']===true) )
		{
			$json_req['FreightShipRequest']['Shipment']['ShipmentServiceOptions']=array();
			$json_req['FreightShipRequest']['Shipment']['ShipmentServiceOptions']['PickupOptions']=array();

			if(isset($_GET['HolidayPickupIndicator']) )
			{
				$json_req['FreightShipRequest']['Shipment']['ShipmentServiceOptions']['PickupOptions']['HolidayPickupIndicator']="";
			}
			if(isset($_GET['InsidePickupIndicator']) )
			{
				$json_req['FreightShipRequest']['Shipment']['ShipmentServiceOptions']['PickupOptions']['InsidePickupIndicator']="";
			}
			if(isset($_GET['ResidentialPickupIndicator']) )
			{
				$json_req['FreightShipRequest']['Shipment']['ShipmentServiceOptions']['PickupOptions']['ResidentialPickupIndicator']="";
			}
			if(isset($_GET['WeekendPickupIndicator']) )
			{
				$json_req['FreightShipRequest']['Shipment']['ShipmentServiceOptions']['PickupOptions']['WeekendPickupIndicator']="";
			}
			if(isset($_GET['LiftGateRequiredIndicator']) )
			{
				$json_req['FreightShipRequest']['Shipment']['ShipmentServiceOptions']['PickupOptions']['LiftGateRequiredIndicator']="";
			}
			if(isset($_GET['LimitedAccessPickupIndicator']) )
			{
				$json_req['FreightShipRequest']['Shipment']['ShipmentServiceOptions']['PickupOptions']['LimitedAccessPickupIndicator']="";
			}
		}

		return json_encode($json_req);	
	}
	
	private function get_order_address( $order ){
		//Address standard followed in all xadapter plugins. 
		$company = $order->get_shipping_company();
		return array(
			'name'		=> $order->get_shipping_first_name().' '.$order->get_shipping_last_name(),
			'company' 	=> !empty($company) ? $company : $order->get_shipping_first_name().' '.$order->get_shipping_last_name(),
			'phone' 	=> $order->get_billing_phone(),
			'email' 	=> $order->get_billing_email(),
			'address_1'	=> $order->get_shipping_address_1(),
			'address_2'	=> $order->get_shipping_address_2(),
			'city' 		=> $order->get_shipping_city(),
			'state' 	=> $order->get_shipping_state(),
			'country' 	=> $order->get_shipping_country(),
			'postcode' 	=> $order->get_shipping_postcode(),
		);
	}
	
	private function get_shop_address( ){
		$ups_settings			= $this->parent->settings;
		$country_with_state		= explode( ':', $ups_settings['origin_country_state'] );
		$this->origin_country	= current( $country_with_state );
		$origin_state 			= end($country_with_state);
		$this->origin_state		= ! empty($origin_state) ? $origin_state : $ups_settings['origin_custom_state'];
		$shipper_phone_number 	= isset( $ups_settings['phone_number'] ) ? $ups_settings['phone_number'] : '';
		
		//Address standard followed in all xadapter plugins. 
		return array(
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
	}
}

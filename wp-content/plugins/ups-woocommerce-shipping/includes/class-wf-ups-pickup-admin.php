<?php
if(!class_exists('wf_ups_pickup_admin')){
	
	class wf_ups_pickup_admin extends WF_Shipping_UPS_Admin{
		
		private $_ups_user_id;
		private $_ups_password;
		private $_ups_access_key;
		private $_ups_shipper_number;
		private $_endpoint;
		
		var $_pickup_prn	=	'_ups_pickup_prn';
		
		public function __construct(){
			
			$this->settings 			= get_option( 'woocommerce_'.WF_UPS_ID.'_settings', null );
			$this->pickup_enabled 		= (isset($this->settings[ 'pickup_enabled']) && $this->settings[ 'pickup_enabled']=='yes') ? true : false;
			$this->debug				= isset( $this->settings['debug'] ) && $this->settings['debug'] == 'yes' ? true : false;
			$this->debug_datas			= array();

			if($this->pickup_enabled){
				$this->init();
			}

		}

		
		private function init(){
			$this->pickup_date = isset($this->settings['pickup_date']) ? $this->settings['pickup_date'] : 'current';
			$this->working_days = isset($this->settings['working_days']) ? $this->settings['working_days'] : array();
			//Init variables
			$this->init_values();
			
			// Init actions
			add_action('admin_footer', 	array($this, 'add_pickup_request_option'));
			add_action('admin_footer', 	array($this, 'add_pickup_cancel_option'));
			add_action('load-edit.php',	array($this, 'perform_pickup_list_action'));
			add_action('manage_shop_order_posts_custom_column' , array($this,'display_order_list_pickup_status'),10,2);
		}
		
		private function init_values(){
			
			$this->_ups_user_id		 = isset( $this->settings['user_id'] ) ? $this->settings['user_id'] : '';
			$this->_ups_password		= isset( $this->settings['password'] ) ? $this->settings['password'] : '';
			$this->_ups_access_key	  = isset( $this->settings['access_key'] ) ? $this->settings['access_key'] : '';
			$this->_ups_shipper_number	= isset( $this->settings['shipper_number'] ) ? $this->settings['shipper_number'] : '';
			
			$ups_origin_country_state 		= isset( $this->settings['origin_country_state'] ) ? $this->settings['origin_country_state'] : '';
			
			
			if ( strstr( $ups_origin_country_state, ':' ) ) :
				// WF: Following strict php standards.
				$origin_country_state_array 	= explode(':',$ups_origin_country_state);
				$origin_country 				= current($origin_country_state_array);
				$origin_state 				= end($origin_country_state_array);
			else :
				$origin_country = $ups_origin_country_state;
				$origin_state   = '';
			endif;
			
			$this->origin_country	=	$origin_country;
			$this->origin_state 	= 	( isset( $origin_state ) && !empty( $origin_state ) ) ? $origin_state : $this->settings['origin_custom_state'];
			
			$api_mode				= 	isset( $this->settings['api_mode'] ) ? $this->settings['api_mode'] : 'Test';
			
			$this->_endpoint		=	$api_mode=='Test'?'https://wwwcie.ups.com/webservices/Pickup':'https://onlinetools.ups.com/webservices/Pickup';
		}
		
		public function add_pickup_request_option(){
			global $post_type;
	 
			if($post_type == 'shop_order') {
			?>
			<script type="text/javascript">
			  jQuery(document).ready(function() {
				jQuery('<option>').val('ups_pickup_request').text('<?php _e('Request UPS Pickup')?>').appendTo("select[name='action']");
				jQuery('<option>').val('ups_pickup_request').text('<?php _e('Request UPS Pickup')?>').appendTo("select[name='action2']");
			  });
			</script>
			<?php
			}
		}
		
		public function add_pickup_cancel_option(){
			global $post_type;
	 
			if($post_type == 'shop_order') {
			?>
			<script type="text/javascript">
			  jQuery(document).ready(function() {
				jQuery('<option>').val('ups_pickup_cancel').text('<?php _e('Cancel UPS Pickup')?>').appendTo("select[name='action']");
				jQuery('<option>').val('ups_pickup_cancel').text('<?php _e('Cancel UPS Pickup')?>').appendTo("select[name='action2']");
			  });
			</script>
			<?php
			}
		}
		
		public function perform_pickup_list_action(){
			$wp_list_table = _get_list_table('WP_Posts_List_Table');
			$action = $wp_list_table->current_action();
			if($action == 'ups_pickup_request'){// Pickup Request
				if(!isset($_REQUEST['post']) || !is_array($_REQUEST['post'])){
					wf_admin_notice::add_notice('No order selected for this action.','warning');
					return;
				}
				
				$order_ids	=	$_REQUEST['post']?$_REQUEST['post']:array();
				
				$request 	= $this->get_pickup_creation_request($order_ids);
				$result		=	$this->request_pickup($request);
				if($result && isset($result['PRN'])){
					$first_order_id	=	current($order_ids);
					update_post_meta($first_order_id,$this->_pickup_prn, $result['PRN']);
					wf_admin_notice::add_notice('UPS pickup requested for following order id(s): '.implode(", ",$order_ids),'success');
				}
			}else if($action == 'ups_pickup_cancel'){
				
				if(!isset($_REQUEST['post']) || !is_array($_REQUEST['post'])){
					wf_admin_notice::add_notice('No order selected for this action.','warning');
					return;
				}
				
				$order_ids	=	$_REQUEST['post']?$_REQUEST['post']:array();
				
				foreach($order_ids as $order_id){
					$result	=	$this->pickup_cancel($order_id);
					if($result){
						wf_admin_notice::add_notice('Pickup request cancelled for PRN: '.$this->get_pickup_no($order_id), 'warning');
						$this->delete_pickup_details($order_id);
					}										
				}
			}
			if( $this->debug && !empty($this->debug_datas) ){
				foreach ($this->debug_datas as $title => $value) {
					echo '<div style="background: #eee;overflow: auto;padding: 10px;margin: 10px;">'.$title;
					echo '<xmp>'.$value.'</xmp></div>';
				}
				exit();
			}
		}
		
		public function generate_pickup_API_request($request_body){
			$request	=	'<envr:Envelope xmlns:envr="http://schemas.xmlsoap.org/soap/envelope/" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:common="http://www.ups.com/XMLSchema/XOLTWS/Common/v1.0" xmlns:wsf="http://www.ups.com/schema/wsf" xmlns:upss="http://www.ups.com/XMLSchema/XOLTWS/UPSS/v1.0">';
			$request	.=	'<envr:Header>';
			$request	.=		'<upss:UPSSecurity>';
			$request	.=			'<upss:UsernameToken>';
			$request	.=				'<upss:Username>'.$this->_ups_user_id.'</upss:Username>';
			$request	.=				'<upss:Password>'.$this->_ups_password.'</upss:Password>';
			$request	.=			'</upss:UsernameToken>';
			$request	.=			'<upss:ServiceAccessToken>';
			$request	.=				'<upss:AccessLicenseNumber>'.$this->_ups_access_key.'</upss:AccessLicenseNumber>';
			$request	.=			'</upss:ServiceAccessToken>';
			$request	.=		'</upss:UPSSecurity>';
			$request	.=	'</envr:Header>';
			$request	.='<envr:Body>';
			$request	.=$request_body;
			$request	.='</envr:Body>';
			$request	.='</envr:Envelope>';
			return $request;
		}
		
		public function get_pickup_creation_request($order_ids){
			
			$pieces	=	$this->get_pickup_pieces($order_ids);
			// no piece found !
			if(!$pieces)
				return false;
			
			$total_weight	=	0;
			$over_weight	=	'N';
			foreach($pieces as $piece){
				
				if($piece['Weight']>70){	// More than 70 lbs package considered as over weight
					$over_weight	=	'Y';
				}
				
				$total_weight	=	$total_weight	+	$piece['Weight'];
			}
			
			$request	=	'<PickupCreationRequest xmlns="http://www.ups.com/XMLSchema/XOLTWS/Pickup/v1.1" xmlns:common="ttp://www.ups.com/XMLSchema/XOLTWS/Common/v1.0" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
			<common:Request>
				<common:RequestOption/>
				<common:TransactionReference>
					<common:CustomerContext>WF Pickup Request</common:CustomerContext>
				</common:TransactionReference>
			</common:Request>
			<RatePickupIndicator>N</RatePickupIndicator>';
			
			$request	.=	$this->get_shipper_info();
			
			$request	.=	$this->get_pickup_date_info();	
			
			$request	.=	$this->get_pickup_address();	
			
			$request	.=	'<AlternateAddressIndicator>Y</AlternateAddressIndicator>';
			
			$piece_xml	=	'';
			foreach($pieces as $pickup_piece){
				$piece_xml	.=	'<PickupPiece>';
				$piece_xml	.=		'<ServiceCode>0'.$pickup_piece['ServiceCode'].'</ServiceCode>';
				$piece_xml	.=		'<Quantity>'.$pickup_piece['Quantity'].'</Quantity>';
				$piece_xml	.=		'<DestinationCountryCode>'.$pickup_piece['DestinationCountryCode'].'</DestinationCountryCode>';
				$piece_xml	.=		'<ContainerCode>'.$pickup_piece['ContainerCode'].'</ContainerCode>';
				$piece_xml	.=	'</PickupPiece>';
			}
			
			$request	.=	$piece_xml;
			
			$request	.=	'	<TotalWeight>';
			$request	.=	'		<Weight>'.$total_weight.'</Weight>';
			$request	.=	'		<UnitOfMeasurement>LBS</UnitOfMeasurement>';
			$request	.=	'	</TotalWeight>';
			$request	.=	'	<OverweightIndicator>'.$over_weight.'</OverweightIndicator>'; // Indicates if any package is over 70 lbs 			
			
			// 01, pay by shipper; 02, pay by return
			$request	.=	'	<PaymentMethod>01</PaymentMethod>';
			
			/*
			<Notification>
				<ConfirmationEmailAddress>your_email1@ups.com</ConfirmationEmailAddress>
				<ConfirmationEmailAddress>your_email2@ups.com</ConfirmationEmailAddress>
				<UndeliverableEmailAddress>your_email3@ups.com</UndeliverableEmailAddress>
			</Notification>
			<CSR>
				<ProfileId>1-Q83-122</ProfileId>
				<ProfileCountryCode>US</ProfileCountryCode>
			</CSR>
			*/
			$request	.=	'</PickupCreationRequest>';	
			
					
			$complete_request = $this->generate_pickup_API_request($request);
			return $complete_request;
		}
		
		public function get_shipper_info(){
			
			$xml	=	'<Shipper>';
				$xml	.=	'<Account>';
					$xml	.=	'<AccountNumber>'.$this->_ups_shipper_number.'</AccountNumber>';
					$xml	.=	'<AccountCountryCode>'.$this->origin_country.'</AccountCountryCode>';
				$xml	.=	'</Account>';
			$xml	.=	'</Shipper>';
			return $xml;
		}
		
		public function get_pickup_date_info(){
			
			$pickup_enabled 			= ( $bool = $this->settings[ 'pickup_enabled'] ) && $bool == 'yes' ? true : false;
			$pickup_start_time	 = $this->settings[ 'pickup_start_time' ]?$this->settings[ 'pickup_start_time' ]:8; // Pickup min start time 8 am
			$pickup_close_time	 = $this->settings[ 'pickup_close_time' ]?$this->settings[ 'pickup_close_time' ]:18;
			
			$timestamp	=	strtotime(date('Y-m-d')); // Timestamp of the 00:00 hr of this day		
			$pickup_ready_timestamp	=	$timestamp + $pickup_start_time*3600*1;
			$pickup_close_timestamp	=	$timestamp + $pickup_close_time*3600;
			
			if($this->pickup_date == 'current'){
				$pickup_date = date('Ymd');
			}else{
				$pickup_date = $this->get_next_working_day();
			}

			$xml	=	'<PickupDateInfo>';
				$xml	.=	'<CloseTime>'.date("Hi",$pickup_close_timestamp).'</CloseTime>';
				$xml	.=	'<ReadyTime>'.date("Hi",$pickup_ready_timestamp).'</ReadyTime>';
				$xml	.=	'<PickupDate>'.$pickup_date.'</PickupDate>';
			$xml	.=	'</PickupDateInfo>';
			return $xml;
		}
		
		private function get_next_working_day(){
			$day_order = array(
				0 => 'Sun',
				1 => 'Mon',
				2 => 'Tue',
				3 => 'Wed',
				4 => 'Thu',
				5 => 'Fri',
				6 => 'Sat',
			);

			$today = date("D");
			$next_working_day = $today;
			$date = new DateTime();
			$today_key = array_search($today, $this->working_days);
			if( !empty($today_key) ){
				return $date->format('Ymd');
			}else{
				$today_key = array_search($today, $day_order);
				for($i=0; $i<8; $i++) {
					if( $found_index = array_search($day_order[$today_key], $this->working_days) ){
						$next_working_day = $this->working_days[$found_index];
						break;
					}
					if($today_key <= 5 )  $today_key++;  else  $today_key=0; 
				}
				$date->modify("next $next_working_day");
				return $date->format('Ymd');
			}
		}
		public function get_pickup_address(){
			
			$ups_user_name					= isset( $this->settings['ups_user_name'] ) ? $this->settings['ups_user_name'] : '';
			$ups_display_name				= isset( $this->settings['ups_display_name'] ) ? $this->settings['ups_display_name'] : '';
			$phone_number 					= isset( $this->settings['phone_number'] ) ? $this->settings['phone_number'] : '';
			$ups_origin_addressline 		= isset( $this->settings['origin_addressline'] ) ? $this->settings['origin_addressline'] : '';
			$ups_origin_city 				= isset( $this->settings['origin_city'] ) ? $this->settings['origin_city'] : '';
			$ups_origin_postcode 			= isset( $this->settings['origin_postcode'] ) ? $this->settings['origin_postcode'] : '';
			$origin_state					= $this->origin_state;
			$origin_country					= $this->origin_country;
			
			$xml	=	'<PickupAddress>';
			$xml	.=		'<CompanyName>'.$ups_user_name.'</CompanyName>';
			$xml	.=		'<ContactName>'.$ups_display_name.'</ContactName>';
			$xml	.=		'<AddressLine>'.$ups_origin_addressline.'</AddressLine>';
			$xml	.=		'<City>'.$ups_origin_city.'</City>';
			$xml	.=		'<StateProvince>'.$origin_state.'</StateProvince>';
			//	<!--<Urbanization/>-->
			$xml	.=		'<PostalCode>'.$ups_origin_postcode.'</PostalCode>';
			$xml	.=		'<CountryCode>'.$origin_country.'</CountryCode>';
			$xml	.=		'<ResidentialIndicator>Y</ResidentialIndicator>';
			$xml	.=		'<PickupPoint>Lobby</PickupPoint>';
			$xml	.=		'<Phone>';
			$xml	.=			'<Number>'.$phone_number.'</Number>';
			$xml	.=		'</Phone>';
			$xml	.=	'</PickupAddress>';
			
			return $xml;
		}
		
		public function get_pickup_pieces($order_ids){
			
			$pickup_pieces	=	array();
			
			foreach($order_ids as $order_id){
				$order		=	$this->wf_load_order( $order_id );

				if ( !$order ){
					wf_admin_notice::add_notice('Cannot load order.');
					return false;
				}

				$service_code	=	get_post_meta($order_id,'wf_ups_selected_service',1);
				if(!isset($service_code) || empty($service_code)){
					wf_admin_notice::add_notice('Order #'.$order_id.': Label not generated yet');
					return false;
				}


				//Add packages from order
				$piece_data = array();
				$package_data = $this->wf_get_package_data( $order );
				foreach ($package_data as $packa_key => $package_group) {
					foreach ($package_group as $grp_key => $package_data) {
						$piece_data[]	=	$this->get_order_piece_from_package($package_data, $order, $service_code);
					}
				}
				$pickup_pieces	=	array_merge( $pickup_pieces, $piece_data );
			}		
			return $pickup_pieces;
		}
		
		public function get_order_piece_from_package( $package_data, $order, $service_code ){
			
			$piece_data	=	array();
			
			$piece_data['Weight']					=	$package_data['PackageWeight']['Weight'];
			$piece_data['Quantity']					=	1;
			$piece_data['DestinationCountryCode']	=	$order->shipping_country;
			$piece_data['ServiceCode']				=	$service_code;
			$piece_data['ContainerCode']			=	'01'; // 01 = Package, 02 = UPS Letter, 03 = Pallet
			
			return $piece_data;
		}		
		
		public function request_pickup($request){
			try {
				$response	=	wp_remote_post( $this->_endpoint ,
					array(
						'timeout'   => 70,
						'sslverify' => 0,
						'body'	  => $request
					)
				);
			}catch(Exception $e){
				wf_admin_notice::add_notice($e->getMessage());
				return false;
			}

			if($this->debug){
				$this->debug_datas = array(
					'PICKUP REQUEST' 	=> $request,
					'PICKUP RESPONSE' 	=> $response['body'],
				);
			}

			$clean_xml = str_ireplace(array('soapenv:', 'pkup:','common:','err:'), '', $response['body']); // Removing tag envelope
			$response_obj = simplexml_load_string($clean_xml);
			
			if(isset($response_obj->Body->PickupCreationResponse->Response->ResponseStatus->Code) && $response_obj->Body->PickupCreationResponse->Response->ResponseStatus->Code == 1){
				$data	= array(
					'PRN'	=>	(string)$response_obj->Body->PickupCreationResponse->PRN,
				);
				return $data;
			}
			else{
				if(isset($response_obj->Body->Fault->detail->Errors->ErrorDetail->PrimaryErrorCode)){
					$error_description	=	(string)$response_obj->Body->Fault->detail->Errors->ErrorDetail->PrimaryErrorCode->Description;
					wf_admin_notice::add_notice($error_description);
					return false;
				}
			}
		}
		
		public function pickup_cancel($order_id){
			$order = $this->wf_load_order( $order_id );
			
			if ( !$order ){
				wf_admin_notice::add_notice('Cannot load order.');
				return false;
			}
			
			if(!$this->is_pickup_requested($order_id)){
				wf_admin_notice::add_notice('Pickup request not found for order #'.$order_id);
				return false;
			}
			
			$request 	= 	$this->get_pickup_cancel_request($order_id);
			$result		=	$this->run_pickup_cancel($request);
			return $result;
		}
		
		function get_pickup_cancel_request($order_id){
			
			$request	=	'<PickupCancelRequest xmlns="http://www.ups.com/XMLSchema/XOLTWS/Pickup/v1.1" xmlns:common="ttp://www.ups.com/XMLSchema/XOLTWS/Common/v1.0" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
			<common:Request>
				<common:RequestOption/>
				<common:TransactionReference>
					<common:CustomerContext>WF Pickup Cancel Request</common:CustomerContext>
				</common:TransactionReference>
			</common:Request>';
			
			$request	.=	'<CancelBy>02</CancelBy>'; // 01 = Account Number, 02 = PRN			
			$request	.=	'<PRN>'.$this->get_pickup_no($order_id).'</PRN>';			
			$request	.=	'</PickupCancelRequest>'; 
			
			$complete_request	=	$this->generate_pickup_API_request($request);
			return $complete_request;
		}
		
		public function run_pickup_cancel($request){
			
			try {
				$response	=	wp_remote_post( $this->_endpoint ,
					array(
						'timeout'   => 70,
						'sslverify' => 0,
						'body'	  => $request
					)
				);
			}catch(Exception $e){
				wf_admin_notice::add_notice($e->getMessage());
				return false;
			}

			if($this->debug){
				$this->debug_datas = array(
					'PICKUP CANCEL REQUEST' 	=> $request,
					'PICKUP CANCEL RESPONSE' 	=> $response['body'],
				);
			}

			
			$clean_xml = str_ireplace(array('soapenv:', 'pkup:','common:','err:'), '', $response['body']); // Removing tag envelope
			$response_obj = simplexml_load_string($clean_xml);
			if(isset($response_obj->Body->PickupCancelResponse->Response->ResponseStatus->Code) && $response_obj->Body->PickupCancelResponse->Response->ResponseStatus->Code == 1){
				return true;
			}else{
				if(isset($response_obj->Body->Fault->detail->Errors->ErrorDetail->PrimaryErrorCode)){
					$error_description	=	(string)$response_obj->Body->Fault->detail->Errors->ErrorDetail->PrimaryErrorCode->Description;
					wf_admin_notice::add_notice($error_description);
					return false;
				}
			}
		}
		
		function display_order_list_pickup_status($column, $order_id){
			switch ( $column ) {
				case 'shipping_address':
					if($this->is_pickup_requested($order_id))
						printf('<small class="meta">'.__('UPS PRN: '.$this->get_pickup_no($order_id)).'</small>');
					break;
			}
		}		
		
		public function is_pickup_requested($order_id){		
			return $this->get_pickup_no($order_id)?true:false;
		}
		
		public function get_pickup_no($order_id){
			if(empty($order_id))
				return false;
			
			$pickup_confirmation_number	=	get_post_meta($order_id,$this->_pickup_prn,1);				
			return $pickup_confirmation_number;				
		}
		
		function delete_pickup_details($order_id){
			delete_post_meta($order_id, $this->_pickup_prn);
		}
				
	}
	
	new wf_ups_pickup_admin();
}
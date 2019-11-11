<?php /***** PLEASE NOTE THAT ONLINE REGISTRATION WILL NOT
WORK WITH VIRTUAL MACHINES - THEY REQUIRE THE V4 KEY GEN 
WHICH HAS 218 CHARACTERS VS THE SHORTENED V6 KEY
PLEASE USE OFFLINE REGISTRATION AND WITH KEYGEN FOR VIRTUAL MACHINES
*****/
error_reporting(-1);

$actual_link = "$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
$actual_link = preg_replace('#^www\.(.+\.)#i', '$1', $actual_link);
$username = @$_GET['custID'];
$password = @$_GET['custPass'];
$pType = @$_GET['type'];
$pcid = @trim($_GET['pcid']);
$hwid = @trim($_GET['hwid']);
$rollover = FALSE;
$keys_within_exp = FALSE;
$existing_pcid = FALSE;
//$unregister = "RL6rRUTpFutrFpr4yGzC";

define('DEBUG', FALSE);
define('SANDBOX', FALSE);


// Determines whether to use Production or Sandbox DB
if(isset($_GET['d'])) {
	define('SANDBOX_DB', TRUE);
}
else {
	define('SANDBOX_DB', FALSE);
}

require_once('./wp-load.php'); // repoint if required
global $wp, $wpdb, $wp_rewrite, $wp_the_query, $wp_query;

$identifiers = $pcid;
$expiration = strtotime("2015-12-31");

// Switch to Sandbox Database if On Production Server if SANDBOX_DB is TRUE
if($username == 'sandbox' && !SANDBOX_DB) {

	// Redirect to Sandbox API Database
    header("Location: http://sb.$actual_link&d=$d"); /* Redirect browser */

}
else {

	if(filter_var($username, FILTER_VALIDATE_EMAIL)) {
	    // Search DB for email of username
		$username = $wpdb->get_var("SELECT `user_login` FROM `wp_users` WHERE `user_email` = '$username'");
	}

	// Set Debug credentials
	if(DEBUG) {
		if(SANDBOX) {
	      $username = "sandbox"; // KILL CREDENTIALS FOR PRODUCTION
		  $password ="32lkwwr0";
		}
		else {
	      $username = "wmosley"; // KILL CREDENTIALS FOR PRODUCTION
		  $password ="";
		}
		
		$identifiers = '00730f01178bfbff6aaa2e9c';
		$pcid = $identifiers;
		//echo $identifiers . '<br/>';
		// END DEBUG //
	}
	if(!DEBUG) {
		if(($pcid=='')&&($hwid=='')){die('{"success":false, "message": "Please install Bionetics on a Windows computer."}');}
		//if($pType!=1){die('{"success":false}');}
	}

	// Authenticate Wordpress user
	$auth = wp_authenticate_username_password(NULL,$username,$password);
	if(is_wp_error($auth)){die('{"success":false, "message":"Login credentials failed. Please try a different email/password combination."}');}
	
	$practioner = get_user_meta( $auth->ID ); // Gets all the meta you want. It's magic!
	/* USAGE
	* first_name[0]
	* last_name[0]
	* billing_company[0]
	* billing_address_1[0]
	* billing_address_2[0]
	* billing_city[0]
	* billing_state[0]
	* billing_country[0]
	* billing_postcode[0]
	* billing_phone[0]
	* shipping_company[0]
	* shipping_address_1[0]
	* shipping_address_2[0]
	* shipping_city[0]
	* shipping_state[0]
	* shipping_country[0]
	* shipping_postcode[0]
	* shipping_phone[0]
	*
	* EXAMPLE - $practioner['billing_city'];
	*/
	// Get User ID
	$uid = get_current_user_id();
	// Get Technology Used by User
	// SV
	$tech_sv = $wpdb->get_var("SELECT `meta_value` FROM `wp_usermeta` WHERE `user_id` = $uid AND `meta_key` = 'sv'");
	$tech_sv = strtolower($tech_sv);
	// SV2
	$tech_sv2 = $wpdb->get_var("SELECT `meta_value` FROM `wp_usermeta` WHERE `user_id` = $uid AND `meta_key` = 'sv2'");
	$tech_sv2 = strtolower($tech_sv2);
	/*
	   SC_MODE = 0,
	   MC_MODE = 1,
	   MATRIX = 2,
	   IMPRINT = 3,
	   SCANNER = 4,
	   SCANNER_LIGHT = 14,
	   ETHERIC = 5,
	   PROXY = 6,
	   BIOC = 16,
	   WBP_SC = 17,
	   WBP_MC = 18,
	   HRV = 19,
	   INSIGHT_THEME = 20,
	   //--
	   LOOKUP = 7,
	   ADV_SEARCH = 8,
	   SUBSTANCE_DETAIL = 9,
	   DOUBLE_WORKSET = 10,
	   CLOCK = 11,
	   PREMIUM_THEME = 12,
	   EXTRA_MATRICES = 15,
	   ONLINE_SYNC = 13
	*/
	// Set features for SpectraVision registration
	// David Griffith special - Extra matrixies
	if($uid == 1368 /* Dave and Linda Griffith */ || $uid == 1612 || $uid == 1039 || $uid == 1098 /* Lee Woolley */) {
		$features = array(0,1,2,3,4,7,8,9,10,11,12,13,14,15,20);
		//echo 'special';
	}
	// SV2 Features
	elseif($tech_sv == 'yes' && $tech_sv2 == 'yes'){
		$features = array(0,1,2,3,4,7,8,9,10,11,12,13,14,20);
		//echo 'sv2';
	}
	// SV Features - Doesn't include Scanner Light
	elseif($tech_sv == 'yes' && $tech_sv2 == NULL) {
		$features  = array(0,1,2,3,4,7,8,9,10,11,12,20);
		//echo 'SpectraVision';
	}
	// set default to features to SV2
	else {
		$features = array(0,1,2,3,4,7,8,9,10,11,12,13,14,20);
		//echo 'Default';
	}
	// KILL SC for testing
	//$features = array(1,2,3,4,7,8,9,10,11,12,13,14,20);
	// Get SERIAL KEY of Doctor and Match
	    // Use print_r($customer_orders)
		$customer_orders = get_posts( array(
		    'numberposts' => -1,
		    'meta_key'    => '_customer_user',
		    'meta_value'  => $auth->ID,
		    'post_type'   => wc_get_order_types(),
		    'post_status' => array_keys( wc_get_order_statuses() ),
		) );
	    // Get Order IDs
	    foreach ($customer_orders as &$order) {
			    $order_ids[] = $order->ID;
		}
		// Get all Serial Orders by ID
		//print_r($serial_orders);
		$serial_orders= $wpdb->get_col("SELECT `order_id` FROM `wp_woocommerce_serial_key`");
		$result = array_intersect($order_ids, $serial_orders);
		// Set count for below foreach
		$c = 0;

		// UNREGISTER ALL COMPUTERS
		if($unregister == "RL6rRUTpFutrFpr4yGzC") {

			foreach (array_reverse($result) as $k) {

				$wpdb->query("UPDATE `wp_woocommerce_serial_key` SET pcid = '' WHERE `order_id` = $k");

			} //

		} // end UNREGISTER ALL COMPUTERS

		// Check which keys are still valid (not exp)
		foreach (array_reverse($result) as $k) {
			$keys_within_exp = TRUE;
			$the_date = $wpdb->get_var("SELECT `valid_till` FROM `wp_woocommerce_serial_key` WHERE `order_id` = '$k'");
			//$valid_till['order_id'][$c] = $k;
			//$valid_till['exp'][] = $the_date . "<br/>";
			// Search for empty PCID fields
			$the_pcid = $wpdb->get_var("SELECT `pcid` FROM `wp_woocommerce_serial_key` WHERE `order_id` = '$k'");
			$pos = substr_count($the_pcid,',');
			// Grab empty PCID fields

			// Check if Identifier is in db already, if not, append

			// Save valid keys within date to $valid_till. Includes associated order id
			if(strtotime($the_date) > strtotime("now")) {
				
				// Check if PCID is Empty, Only contains 1 OR PCID in slot is the same as computer being registred.

				// Check if PCID is already in db
				if(strpos($the_pcid, $identifiers) !== false) 
				{
					$existing_pcid = TRUE;
					//$valid_till['pcid'][$c] = $the_pcid;
					$match = $k;
					if(DEBUG){$hello = 'x';}

					// Time to rollover registration days
					//echo $the_date . '<br/>';

					// Check if exp is in 30 days or less
					$within_ext = strtotime($the_date) - time();
					$within_ext = floor($within_ext/ (60 * 60 * 24));

					// If Within 30 Days of experiation date
					if($within_ext <= 60 && $within_ext >= 0) {

						// Rollover previous expiration and extend
						$extended_date = date("Y-m-d", strtotime(date("Y-m-d", strtotime($the_date)) . " + 1 year"));
						//echo $extended_date . '<br/>';
						//echo $the_pcid . '<br/>';
						//echo $match;

						// Get the latest Valid ORDER ID
						$last_order = $result[0];

						$last_order_pcid = $wpdb->get_var("SELECT `pcid` FROM `wp_woocommerce_serial_key` WHERE `order_id` = '$last_order'");

						// There is an empty serial key within 60 days of exp
						if(empty($last_order_pcid)) {
							
							// Void prev $match and use $last_order to extend
							$rollover = TRUE;
							$exp_match = $match;
							$match = $last_order;

						}

					} // end Within 30 days


					break;
				}
				if($the_pcid == '' || $pos == 0 ) 
				{
					//$valid_till['pcid'][$c] = $the_pcid;
					$match = $k;
					if(DEBUG){$hello = 'y';}
					break;
				}
				$c++;
			}
		}

		if(!isset($match) && $keys_within_exp == FALSE) {
			die('{"success":false, "message": "Please purchase a key here to register your software.<br/>
				https://www.thenewhuman.com/shop/software-renewal-2/"}');
		}
		if(!isset($match) && $keys_within_exp) {
			die('{"success":false, "message": "You have reached your computer limit for the key you are trying to register. Please purchase a new key to register this computer.<br/>
				https://www.thenewhuman.com/shop/software-renewal-2/"}');
		}
		
		if(isset($match)) {
	
			// Get identifiers from the db
			$db_pcid = $wpdb->get_var("SELECT `pcid` FROM `wp_woocommerce_serial_key` WHERE `order_id` = $match");
			// Check how many computers key has been used on
			$used = explode(",",$db_pcid);
			$used = count($used);
			//echo $used . "<br/>";
			if($used >= 2 && $existing_pcid == FALSE){
				if(DEBUG){echo $the_date;}
				die('{"success":false, "message": "You have reached your computer limit for the key you are trying to register. Please purchase a new key to register this computer.<br/>
				https://www.thenewhuman.com/shop/software-renewal-2/"}');
			}
			else {
				
				// Check if Identifier is in db already, if not, append
				$pos = strpos($db_pcid,$identifiers);
				if($db_pcid == '') {
					// Identifier = identifier
				}
				elseif($pos === false) {
				    // string needle NOT found in haystack
				    // Append to identifier
				    $identifiers = $db_pcid . ',' . $identifiers;
				}
				// Keeps what's in the db
				else {
					$identifiers = $db_pcid;
				}
				// Get current identifiers stored in db
				  $wpdb->query("UPDATE `wp_woocommerce_serial_key` SET pcid = '$identifiers' WHERE `order_id` = $match");
				// Check how many times used in db
				$wpdb->query("UPDATE `wp_woocommerce_serial_key` SET used = '$used' WHERE `order_id` = $match");
				  
			  	// Get expiration date from db
				$exp_date = $wpdb->get_var("SELECT `valid_till` FROM `wp_woocommerce_serial_key` WHERE `order_id` = $match");

				// Update exp date
				if($rollover) {
					$exp_date = $extended_date;
				}

				$pretty_exp = date_format(date_create($exp_date), 'Y-M-d');
			    $expiration = strtotime($pretty_exp); // FETCH FROM CUSTOMER PURCHASE DETAILS
				
			    // If Rollover is true, extend date
			    if($rollover) {

			    	$wpdb->query("UPDATE `wp_woocommerce_serial_key` SET valid_till = '$extended_date' WHERE `order_id` = $match");

			    	$now = date_format(date_create(time()), 'Y-M-d');
			    	$now = strtotime($now);

			    	// Exp prev match
			    	$wpdb->query("UPDATE `wp_woocommerce_serial_key` SET valid_till = CURRENT_TIMESTAMP() WHERE `order_id` = $exp_match");

			    }

		    	// IMPORTANT - Runs Keygen 
				$resp = KeyAPI::GetKey($expiration,$identifiers,$features);
				// Add Key to Database
				$wpdb->query("UPDATE `wp_woocommerce_serial_key` SET serial_key = '$resp' WHERE `order_id` = $match");
				// Returns to Bionetics Software w/ Response True or False + Key
				die('{"success":true,"key":"'.$resp.'"}');
			} // if used
			  	  
		} // if($result != NULL)
	
}


	
	    
	    // Display all exp dates using print_r($exp);
	    //$exp= $wpdb->get_col("SELECT `valid_till` FROM `wp_woocommerce_serial_key`");
		// Get all keys - print_r($keys);
	    //$keys= $wpdb->get_col("SELECT `serial_key` FROM `wp_woocommerce_serial_key`");
	    // Check for a match - print_r($result);
	    // Gets latest purchased key
	    
		//$identifiers = array($pcid); // FETCH FROM / COMPARE TO CUSTOMER PURCHASE DETAILS - (VALIDATE?)
		//$features = KeyAPI::GetFeatures($pType, '3.8', false, false); // FETCH FROM CUSTOMER PURCHASE DETAILS
		// ======== **************************************** ========

		
		abstract class KeyAPI
		{
			/*
			 * Generates the feature array
			 * Product: An INT specifying the product type
			 * Version: A STRING specificing the license version
			 * Cloud: A BOOLEAN indicating if the cloud features should be enabled
			 * Extra Matrices: A BOOLEAN indicating if the extra matrices should be enabled
			 *
			 * Returns: The registration code corresponding to the license
			 */
			public static function GetFeatures($product, $version, $cloud, $extra_matrices)
			{
				switch($product)
				{
					case 1:
						if($version!='3.8'){return array();}
						$result = array(0,1,2,3,4,7,8,9,10,11,12,14,20);
						if($cloud){$result[]=13;}
						if($extra_matrices){$result[]=15;}
						return $result;
					default:
						return array();
				}
			}
			
			/*
			 * Performs the key request
			 * Expiration: A TIMESTAMP the license expiration date as a php timestamp (the format produced by strtotime())
			 * Identifiers: An ARRAY of 1 or more STRING PC identifiers, these are case sensitive
			 * Features: An ARRAY of INTEGER feature codes to enable in the license
			 *
			 * Returns: The registration code corresponding to the license
			 */
			public static function GetKey($expiration, $identifiers, $features)
			{
			
				//$features = array(0,1,2,3,4,7,8,9,10,11,12,13,14,20);
				//$expiration = "1587168000";
				$KEYBASE = '991CDDF8044257EDCCBF3A836053DDA8EC1B122022EF1B67B97B71FB653B4EB69EC4AAFE8C96653717298387C290D24813CF9DFAD47C2C10174415A745D562D9';
						
				$expiration = date('Y-m-d',$expiration);
				//$identifiers = implode(',',$identifiers);
				$features = implode(',',$features);
				$signature = hash('sha512',$KEYBASE.$expiration.$identifiers.$features);
				
				
				
				$url = 'http://biolinkconnect.com/KeyAPI.ashx'; //This url produces a fake 404 error if the data is invalid or if the signature doesn't match - jpech
				
				$fields = array(
					'signature' => urlencode($signature),
					'expiration' => urlencode($expiration),
					'identifiers' => urlencode($identifiers),
					'features' => urlencode($features)
				);
				
				//print_r($fields);
				
				$fields_string='';
				foreach($fields as $key=>$value) { $fields_string .= $key.'='.$value.'&'; }
				rtrim($fields_string, '&');
				
				$ch = curl_init();
				
				
				
				curl_setopt($ch,CURLOPT_URL, $url);
				curl_setopt($ch,CURLOPT_POST, count($fields));
				curl_setopt($ch,CURLOPT_POSTFIELDS, $fields_string);
				curl_setopt($ch,CURLOPT_RETURNTRANSFER, true);
				$result = curl_exec($ch);
				curl_close($ch);
				//if you have an intention of checking for errors (the fake 404 response, etc), do so here - jpeche($ch);
							//if you have an intention of checking for errors (the fake 404 response, etc), do so here - jpech
				return $result;
			}
		}

//} // End SANDBOX DB ELSE
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
		  $password ="XhNgiuWk#jz#rnR3";
		}
		else {
	      $username = "wmosley"; // KILL CREDENTIALS FOR PRODUCTION
		  $password ="@1BH*9)LPPyAX0gFmJ%HZ3%u";
		}
		
		$identifiers = 'test-pcid-2ksafaw';
		$pcid = $identifiers;
		// END DEBUG //
	}
	if(!DEBUG) {
		//if(($pcid=='')&&($hwid=='')){die('{"success":false}');}
		//if($pType!=1){die('{"success":false}');}
	}

	// Authenticate Wordpress user
	$auth = wp_authenticate_username_password(NULL,$username,$password);
	if(is_wp_error($auth)){die('{"success":false}');}
	// if(is_wp_error($auth)){die('{"success":false,"key":"
			//Login credentials failed. Please try a different email/password combination."}');}
	
	// $somevar = $wpdb->get_var("SELECT `display_name` FROM `wp_users` WHERE `ID`={$auth->ID};"); // CODE EXAMPLE TO QUERY DB
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
		//print_r($result);
		// Set count for below foreach
		$c = 0;
		// Check which keys are still valid (not exp)
		foreach (array_reverse($result) as $k) {
			$the_date = $wpdb->get_var("SELECT `valid_till` FROM `wp_woocommerce_serial_key` WHERE `order_id` = '$k'");
			// Save valid keys within date to $valid_till. Includes associated order id
			if(strtotime($the_date) > strtotime("now")) {
				//$valid_till['order_id'][$c] = $k;
				//$valid_till['exp'][] = $the_date . "<br/>";
				// Search for empty PCID fields
				$the_pcid = $wpdb->get_var("SELECT `pcid` FROM `wp_woocommerce_serial_key` WHERE `order_id` = '$k'");
				$pos = substr_count($the_pcid,',');
				// Grab empty PCID fields
				// Check if Identifier is in db already, if not, append
				$existing_pcid = strpos($the_pcid,$identifiers);
				
				// Check if PCID is Empty, Only contains 1 OR PCID in slot is the same as computer being registred.
				if($existing_pcid == TRUE ) 
				{
					//$valid_till['pcid'][$c] = $the_pcid;
					$match = $k;
					if(DEBUG){$hello = 'x';}
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
		//echo $hello . "<br/>";
		//echo $match . '<br/>';
		//echo '<pre>';
		//print_r($valid_till);
		//echo '</pre>';
		
		//die('Pause DB - configuring 2 serial keys. - wmosley');
		
		if(isset($match)) {
			//$match = $result[0];
			/*$used = $wpdb->get_var("SELECT `used` FROM `wp_woocommerce_serial_key` WHERE `order_id` = $match");
			//echo "used " . $used;
			
			// Used too many times
			if($used >= 10000){die('{"success":false}');}
			// Increment Use
			else {
			//echo $match;
			  $used = $used + 1; // increment
	        */
			// Get identifiers from the db
			$db_pcid = $wpdb->get_var("SELECT `pcid` FROM `wp_woocommerce_serial_key` WHERE `order_id` = $match");
			// Check how many computers key has been used on
			$used = explode(",",$db_pcid);
			$used = count($used);
			//echo $used . "<br/>";
			if($used >= 2 && $existing_pcid == FALSE){
				if(DEBUG){echo $the_date;}
				die('{"success":false}');
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
				$pretty_exp = date_format(date_create($exp_date), 'Y-M-d');
			    $expiration = strtotime($pretty_exp); // FETCH FROM CUSTOMER PURCHASE DETAILS
				
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
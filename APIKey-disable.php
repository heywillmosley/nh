<?php
	error_reporting(-1);
	require_once('./wp-load.php'); // repoint if required
	global $wp, $wpdb, $wp_rewrite, $wp_the_query, $wp_query;
	
	$username = @$_GET['custID'];
	$password = @$_GET['custPass'];
	$pType = @$_GET['type'];
	$pcid = @trim($_GET['pcid']);
	$hwid = @trim($_GET['hwid']);
	
	if(($pcid=='')&&($hwid=='')){die('{"success":false}');}
	if($pType!=1){die('{"success":false}');}
	
	$auth = wp_authenticate_username_password(NULL,$username,$password);
	if(is_wp_error($auth)){die('{"success":false}');}
	
	// ======== ** JUST SET THIS PART AND YOU'RE DONE ** ========
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
    
    $fname = $practioner['first_name'][0];


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
    //print_r($order_ids);
    foreach ($customer_orders as &$order) {
		    $order_ids[] = $order->ID;
	}

	// Get all Serial Orders by ID
	//print_r($serial_orders);
	$serial_orders= $wpdb->get_col("SELECT `order_id` FROM `wp_woocommerce_serial_key`");
    
    // Display all exp dates using print_r($exp);
    //$exp= $wpdb->get_col("SELECT `valid_till` FROM `wp_woocommerce_serial_key`");

	// Get all keys - print_r($keys);
    //$keys= $wpdb->get_col("SELECT `serial_key` FROM `wp_woocommerce_serial_key`");

    // Check for a match - print_r($result);
    // Gets latest purchased key
    $result = array_intersect($order_ids, $serial_orders);

    // No key found
    if(is_null($result)) {
    	$msg = "No Serial key purchase found. Please purchase a key at link.";
    	$valid = 'false';

    } // end if if(is_null)

    // Key found, check validation
    else {
	    $match = $result[0];

	    // Get Key Exp date
	    $exp_date = $wpdb->get_var("SELECT `valid_till` FROM `wp_woocommerce_serial_key` WHERE `order_id` = $match");
	    // Get Key
	    $key = $wpdb->get_var("SELECT `serial_key` FROM `wp_woocommerce_serial_key` WHERE `order_id` = $match"); // Get all serial keys

	    //echo "Exp Date: " . $exp_date . "<br/>";

	    // Get Present Date
	    $date = new DateTime();
		$date = $date->format('Y-m-d H:i:s');
		$pretty_exp = date_format(date_create($exp_date), 'M d, Y');
		//echo "Today's Date: " . $date . "<br/>";

		// MAKE OR BREAK - Valid Key or Not
		if($exp_date > $date) 
		{
			//It's Valid!
			$valid = 'true';
			$msg = "Success $fname, your key is valid until <strong>$pretty_exp</strong>";
		}
		else {
			// It's Expired, purchase a new one
			$valid = 'false';
			$msg = "Sorry $fname, your key expired <strong>$pretty_exp</strong>. Please purchase a new key here.";
			$key ="";
		}
    } // end else
 
// END WILLIAM MOSLEY WEB HOOK. UPDATED 10/03/16

	//somevar = $wpdb->get_var("SELECT `user_email` FROM `wp_users` WHERE `ID`={$auth->ID};"); // CODE EXAMPLE TO QUERY DB

	$expiration = strtotime('2015-12-31'); // FETCH FROM CUSTOMER PURCHASE DETAILS
	$identifiers = array($pcid); // FETCH FROM / COMPARE TO CUSTOMER PURCHASE DETAILS - (VALIDATE?)
	$features = KeyAPI::GetFeatures($pType, '3.8', false, false); // FETCH FROM CUSTOMER PURCHASE DETAILS
	// ======== **************************************** ========
	
	$resp = KeyAPI::GetKey($expiration,$identifiers,$features);
	die('{"success":' . $valid . ',"key:' . $key . '":"'.$respS.'"}');
	
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
			$KEYBASE = '991CDDF8044257EDCCBF3A836053DDA8EC1B122022EF1B67B97B71FB653B4EB69EC4AAFE8C96653717298387C290D24813CF9DFAD47C2C10174415A745D562D9';
		
			$expiration = date('Y-m-d',$expiration);
			$identifiers = implode(',',$identifiers);
			$features = implode(',',$features);
			$signature = hash('sha512',$KEYBASE.$expiration.$identifiers.$features);
		
			$url = 'http://biolinkconnect.com/KeyAPI.ashx'; //This url produces a fake 404 error if the data is invalid or if the signature doesn't match - jpech
	
			$fields = array(
				'signature' => urlencode($signature),
				'expiration' => urlencode($expiration),
				'identifiers' => urlencode($identifiers),
				'features' => urlencode($features)
			);
		
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
			//if you have an intention of checking for errors (the fake 404 response, etc), do so here - jpech
			return $result;
		}
	}
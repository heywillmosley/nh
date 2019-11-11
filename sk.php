<?php 
require_once('./wp-load.php'); // repoint if required
global $wp, $wpdb, $wp_rewrite, $wp_the_query, $wp_query;

// Check and see if Admin or Shop Manager
$user = wp_get_current_user();
if( in_array( 'administrator', (array) $user->roles ) || 
    in_array( 'shop_manager', (array) $user->roles ) || 
    in_array( 'aamrole_5515657c49534', (array) $user->roles ) || 
    in_array( 'aamrole_55156637f0312', (array) $user->roles ) ) {
    
	// SV
	$rows = $wpdb->get_results( "SELECT * FROM wp_woocommerce_serial_key" );
	$rows = array_reverse ( $rows );
	$keys = array();
	
	
	foreach( $rows as $row ) {
		
		
		        $order = new WC_Order( $row->order_id );
			$email = $order->billing_email;
			$name = $order->billing_first_name . ' ' . $order->billing_last_name;
			
			if( empty( $row->pcid) ) {
				$row->pcid = "Not registered yet";
				$row->serial_key = "Register on computer to generate key";
			}
			
			$now = date_and_change( $row->valid_till );
			$now = $now['date'];
			
			if( $now > $row->valid_till ) {
				$row->valid_till = "EXPIRED " . $row->valid_till;
			}
			
			$keys[$email]['name'] = $name; 
	
			$keys[$email]['order_id'][$row->order_id] = $row->valid_till . " | " . $row->serial_key . " | " . $row->pcid; 
		
		
		
		
	}
	 unset($keys['']);
	
	 echo '<pre>';
    print_r ( $keys );
    echo '</pre>';

} else {
	die("Login to view.");
}

 /**
   * Today and Change Later or Future
   * pram $change e.g. + 3 days, - 2 weeks
   * return Array ['now'] and ['change']
   */
  function date_and_change( $change, $date = FALSE ) {
    
    if( ! $date ) {
      $result['date'] = date('Y-m-d H:s');
    } else {
      $result['date'] = $date;
    }
    
    $result['change'] = date('Y-m-d H:s', strtotime ( $date . $change ) );
    
    return $result;
      
  } // end function today_and_change( $date = FALSE $change )

	
	
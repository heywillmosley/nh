<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
if ( ! class_exists( 'IC_Commerce_Premium_Golden_Update_Notices' ) ) {
	class IC_Commerce_Premium_Golden_Update_Notices{
		
		public $constants 	=	array();
		
		public function __construct($constants = array()) {			
			$this->constants		= $constants;
			
			add_action('admin_notices',      array($this, 'admin_notices'));
			
			add_action( 'wp', 				 array($this, 'create_crone') );
			
			add_action( 'ic_upgrade_notice', array($this, 'check_upgrade_notice'));
			
			add_action( 'wp_ajax_ic_dismissed_notice_handler', array($this, 'wp_ajax_ic_dismissed_notice_handler'));
		}
		
		function create_crone(){
			
			/*For testing Enable*/
			///wp_clear_scheduled_hook('ic_upgrade_notice');
			
			
			if(!wp_next_scheduled( 'ic_upgrade_notice')){
				wp_clear_scheduled_hook('ic_upgrade_notice');
				wp_schedule_event( time(), 'daily', 'ic_upgrade_notice' );
			}
		}
		
		function check_upgrade_notice(){
			
			$body    	= array(
								'upgrade_notice' => 'yes'
								,'site_url' => get_site_url()
								);
			$args		= array(
								'method' 	  => 'POST',
								'timeout' 	 => 45,
								'redirection' => 5,
								'httpversion' => '1.0',
								'blocking' 	=> true,
								'headers' 	 => array(),
								'body'	  	=> $body,
								'cookies'	 => array(),
								'sslverify'   => false
							);
			$url     	   = 'http://userp411/point_of_sale/upgrade.php';
			$request 	   = wp_remote_post($url,$args);			
			$request	   = isset($request['body']) ? $request['body'] : json_encode(array());
			$request       = json_decode($request);
			$id 			= isset($request->id) ? $request->id : '';
			$body 		  = isset($request->body) ? $request->body : '';
			$delete 		= isset($request->delete) ? $request->delete : 'no';
			
			if($delete == 'yes'){
				delete_option('ic_upgrade_notice_settings');
				delete_transient('ic_upgrade_notices_transient');
			}			
			
			$settings = get_option('ic_upgrade_notice_settings',array());
			$ids = isset($settings['ids']) ? $settings['ids'] : array();
			$delete = isset($settings['ids']) ? $settings['ids'] : array();
			
			if(!isset($settings['ids'][$id]) and $body){				
				$settings['ids'][$id] = 'no';
				update_option('ic_upgrade_notice_settings',$settings);
				set_transient('ic_upgrade_notices_transient', $body, 31 * DAY_IN_SECONDS );
				update_option( 'dismissed-ic_upgrade_notice', FALSE );
			}
		}
		
		function admin_notices() {
			$transient = get_transient('ic_upgrade_notices_transient');			
			if($transient){
				if (!get_option('dismissed-ic_upgrade_notice', FALSE ) ) { 
					echo $transient;
					add_action( 'admin_footer', array($this, 'admin_footer'));
				}
			}
		}
		
		/**
		 * AJAX handler to store the state of dismissible notices.
		 */
		function wp_ajax_ic_dismissed_notice_handler() {
			$type = isset($_REQUEST['type']) ? $_REQUEST['type'] : '';
			if($type == 'ic_upgrade_notice'){
				update_option( 'dismissed-' . $type, TRUE );
				delete_transient('ic_upgrade_notices_transient');
			}
		}
		
		function admin_footer(){
			?>
              <script type="text/javascript">
				  //shorthand no-conflict safe document-ready function
				  jQuery(function($) {
					// Hook into the "notice-my-class" class we added to the notice, so
					// Only listen to YOUR notices being dismissed
					jQuery( document ).on( 'click', '.ic_upgrade_notice .notice-dismiss', function () {
						// Read the "data-notice" information to track which notice
						// is being dismissed and send it via AJAX
						var type = jQuery( this ).closest( '.ic_upgrade_notice' ).data( 'notice' );
						// Make an AJAX call
						// Since WP 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
						jQuery.ajax( ajaxurl,
						  {
							type: 'POST',
							data: {
							  action: 'ic_dismissed_notice_handler',
							  type: type,
							}
						  } );
					  } );
				  });
			  </script>
            <?php
		}
	}
	
	$obj = new IC_Commerce_Premium_Golden_Update_Notices();
}
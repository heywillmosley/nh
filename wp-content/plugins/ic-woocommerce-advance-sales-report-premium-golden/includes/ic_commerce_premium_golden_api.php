<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
	require_once('ic_commerce_premium_golden_fuctions.php');

if ( ! class_exists( 'IC_Commerce_API' ) ) {
	class IC_Commerce_API extends IC_Commerce_Premium_Golden_Fuctions{
			
		private $token;
		
		private $errors = array();
		
		public $constants 	=	array();
	
		public function __construct ($constants) {
			
			$this->constants 	= $constants;	
						
			$this->token 		= $this->constants['plugin_key'];
			
		} // End __construct()
	
		
		public function activate ( $key, $product_id ) {
			$response = false;
			$home_url = str_replace("https://","",$home_url);
			$home_url = str_replace("http://","",$home_url);
	
			$request = $this->request( 'activation', array( 'licence_key' => $key, 'product_id' => $product_id, 'home_url' => $home_url));
			//$request = $this->request( 'activation', array( 'licence_key' => $key, 'product_id' => $product_id, 'home_url' => esc_url( home_url())));
			
			
			if ( empty( $request ) || !$request )
				return false;
				
			return !isset( $request->error );
		} // End activate()
	
		
		public function deactivate ( $key, $product_id = '' ) {
			$response = false;
	
			if ( !$product_id )
				$product_id = 'x';
			
			$home_url = esc_url( home_url( '/' ));
			
			$home_url = str_replace("https://","",$home_url);
			$home_url = str_replace("http://","",$home_url);
			
			$request = $this->request( 'deactivation', array( 'licence_key' => $key, 'product_id' => $product_id, 'home_url' => $home_url ) );	
			//$request = $this->request( 'deactivation', array( 'licence_key' => $key, 'product_id' => $product_id, 'home_url' => esc_url( home_url( '/' ) ) ) );
			
			return ! isset( $request->error );
		} // End deactivate()
	
		
		public function check ( $key ) {
			$response = false;
	
			$request = $this->request( 'check', array( 'licence_key' => $key ) );
	
			return ! isset( $request->error );
		} // End check()
	
		
		private function request ( $endpoint = 'check', $params = array() ) {
			global $current_user;
			
			$url = add_query_arg( 'is_api', 'wordpress-plugin', $this->constants['plugin_api_url'] );
	
			$supported_methods = array( 'check', 'activation', 'deactivation' );
			$supported_params = array( 'licence_key', 'product_id', 'home_url' );
	
			if ( in_array( $endpoint, $supported_methods ) ) {
				$url = add_query_arg( 'request', $endpoint, $url );
			}
	
			if ( 0 < count( $params ) ) {
				foreach ( $params as $k => $v ) {
					if ( in_array( $k, $supported_params ) ) {
						$url = add_query_arg( $k, $v, $url );
					}
				}
			}
	
			$e = get_option( 'admin_email', false );
	
			//New Change ID 20140918
			$url 	= add_query_arg( 'admin_email', $e, $url );
			$url 	= add_query_arg( 'user_email', $current_user->data->user_email, $url );
			$url 	= add_query_arg( 'user_login', $current_user->data->user_login, $url );
			$url 	= add_query_arg( 'user_id', $current_user->data->ID, $url );
			$url 	= add_query_arg( 'plugin_file_id', $this->constants['plugin_file_id'], $url );
			$url 	= add_query_arg( 'plugin_folder', $this->constants['plugin_folder'], $url );
			$url 	= add_query_arg( 'file_id', $this->constants['plugin_file_id'], $url );
			$s 		= get_option( 'sitename' );
			$url 	= add_query_arg( 'sitename', $s, $url );
			
			$url 	= add_query_arg( 'sub_version', $this->constants['sub_version'], $url );
			$url 	= add_query_arg( 'last_updated', $this->constants['last_updated'], $url );
			$url 	= add_query_arg( 'customized', $this->constants['customized'], $url );
			$url 	= add_query_arg( 'customized_date', $this->constants['customized_date'], $url );
			
			$url 	= add_query_arg( 'parent_plugin_version', $this->constants['parent_plugin_version'], $url );
			$url 	= add_query_arg( 'parent_plugin_db_version', $this->constants['parent_plugin_db_version'], $url );
			$url 	= add_query_arg( 'version', $this->constants['version'], $url );
			$url 	= add_query_arg( 'plugin_name', str_replace(" ", "-",$this->constants['plugin_name']), $url );
			
			//echo $url;die;
			
			$response = wp_remote_get( $url, array(
				'method' 		=> 'GET',
				'timeout' 		=> 45,
				'redirection'	=> 5,
				'httpversion' 	=> '1.0',
				'blocking' 		=> true,
				'headers' 		=> array(),
				'cookies' 		=> array()
				)
			);
			
			//$this->print_array($response);die;
			
			if( is_wp_error( $response ) || empty( $response['body'] ) || ( false !== strpos( $response['body'], 'Fatal' ) ) ) {
				$data['error'] 	= true;
				$error_msg 		= array();
				$request_host	= isset($this->constants['request_host']) ? $this->constants['request_host'] : 'plugins.infosofttech.com';
				$support_email	= isset($this->constants['support_email']) ? $this->constants['support_email'] : 'support@infosofttech.com';
				$error_no		= 0;
				$error_number	= 1;
				
				if(defined('WP_HTTP_BLOCK_EXTERNAL')){
					if(WP_HTTP_BLOCK_EXTERNAL == true){						
						if(defined('WP_ACCESSIBLE_HOSTS')){
							if(strlen(WP_ACCESSIBLE_HOSTS)>0){																
								$set_host 	= WP_ACCESSIBLE_HOSTS;								
								if (strpos($set_host,$request_host) === false) {
									$error_msg[] = __("Error - <strong>211</strong> External HTTP Block Setting", 'icwoocommerce_textdomains');
									$error_msg[] = __("Setting <strong>'WP_HTTP_BLOCK_EXTERNAL'</strong> constant to true in your wp-config.php file will stop all outgoing network requests from your site. This is typically set on sites that are sitting behind a closed environment.", 'icwoocommerce_textdomains');			
									$error_msg[] = __("WordPress uses another constant <strong>'WP_ACCESSIBLE_HOSTS'</strong> in tandem with <strong>'WP_HTTP_BLOCK_EXTERNAL'</strong> to whitelist specific hosts to get past the block", 'icwoocommerce_textdomains');
									$error_msg[] = __("Open the config file and add <strong>'$request_host'</strong> in <strong>'WP_ACCESSIBLE_HOSTS'</strong>", 'icwoocommerce_textdomains');
									$error_msg[] = __("define( <strong>'WP_HTTP_BLOCK_EXTERNAL'</strong>, <strong>true</strong> )", 'icwoocommerce_textdomains');		
									$error_msg[] = __("define( <strong>'WP_ACCESSIBLE_HOSTS'</strong>, <strong>'$request_host'</strong> )", 'icwoocommerce_textdomains');
									$error_msg[] = __("Please email us at <a href=\"mailto:{$support_email}\">{$support_email}</a> for further assistance", 'icwoocommerce_textdomains');
								}								
							}else{
								$error_msg[] = __("Error - <strong>212</strong> External HTTP Block Setting", 'icwoocommerce_textdomains');
									$error_msg[] = __("Setting <strong>'WP_HTTP_BLOCK_EXTERNAL'</strong> constant to true in your wp-config.php file will stop all outgoing network requests from your site. This is typically set on sites that are sitting behind a closed environment.", 'icwoocommerce_textdomains');			
									$error_msg[] = __("WordPress uses another constant <strong>'WP_ACCESSIBLE_HOSTS'</strong> in tandem with <strong>'WP_HTTP_BLOCK_EXTERNAL'</strong> to whitelist specific hosts to get past the block", 'icwoocommerce_textdomains');
									$error_msg[] = __("Open the config file and add <strong>'$request_host'</strong> in <strong>'WP_ACCESSIBLE_HOSTS'</strong>", 'icwoocommerce_textdomains');
									$error_msg[] = __("define( <strong>'WP_HTTP_BLOCK_EXTERNAL'</strong>, <strong>true</strong> )", 'icwoocommerce_textdomains');		
									$error_msg[] = __("define( <strong>'WP_ACCESSIBLE_HOSTS'</strong>, <strong>'$request_host'</strong> )", 'icwoocommerce_textdomains');
									$error_msg[] = __("Please email us at <a href=\"mailto:{$support_email}\">{$support_email}</a> for further assistance", 'icwoocommerce_textdomains');
							}
						}else{
							$error_msg[] = __("Error - <strong>213</strong> External HTTP Block Setting", 'icwoocommerce_textdomains');
							$error_msg[] = __("Setting <strong>'WP_HTTP_BLOCK_EXTERNAL'</strong> constant to true in your wp-config.php file will stop all outgoing network requests from your site. This is typically set on sites that are sitting behind a closed environment.", 'icwoocommerce_textdomains');			
							$error_msg[] = __("WordPress uses another constant <strong>'WP_ACCESSIBLE_HOSTS'</strong> in tandem with <strong>'WP_HTTP_BLOCK_EXTERNAL'</strong> to whitelist specific hosts to get past the block", 'icwoocommerce_textdomains');
							$error_msg[] = __("Open the config file, define <strong>'WP_ACCESSIBLE_HOSTS'</strong> and add <strong>'$request_host'</strong>", 'icwoocommerce_textdomains');
							$error_msg[] = __("define( <strong>'WP_HTTP_BLOCK_EXTERNAL'</strong>, <strong>true</strong> )", 'icwoocommerce_textdomains');		
							$error_msg[] = __("define( <strong>'WP_ACCESSIBLE_HOSTS'</strong>, <strong>'$request_host'</strong> )", 'icwoocommerce_textdomains');
							$error_msg[] = __("Please email us at <a href=\"mailto:{$support_email}\">{$support_email}</a> for further assistance", 'icwoocommerce_textdomains');
						}
					}
					
				}else{
					$error_msg[] = __("Error - <strong>214</strong> Missing Extensions");
					$error_msg[] = __( 'Request Error, "curl" and "streams" PHP extensions might be missing on your server in order to activate plug-in or Try after sometime.', 'icwoocommerce_textdomains' );
					$error_msg[] = __("Please email us at <a href=\"mailto:{$support_email}\">{$support_email}</a> for further assistance", 'icwoocommerce_textdomains');
					
				}
				
				if(is_array($error_msg)){					
					$data['error_msg'] = "<p>".implode("</ p>\n<p>",$error_msg)."</p>";
				}else{
					$data['error_msg'] = "<p>".$error_msg."</p>";
				}
				
				//$this->print_array($error_msg);die;
				
			} else {
				
				
				//$this->print_array($response);die;
				
				if(isset($response['response']['message']) and $response['response']['message'] == "Bad request"){
					$data['error'] = true;
					$data['error_msg'] = strip_tags($response['body']);
				}else{
					$data = $response['body'];
					$data = maybe_unserialize( $data ); // json_decode( $data );
					$error_number	= 2;
				}
				
			}
			
			//$this->print_array($response);
			//$this->print_array($data);die;
			
			
			$error = '';
			
			// Store errors in a transient, to be cleared on each request.
			if ( isset( $data['error'] ) && true == $data['error'] && isset( $data['error_msg'] ) ) {
				if($error_number == 2){
					if(isset($data['error_msg'])){
						$error = isset($data['error_msg']) ? esc_html($data['error_msg']) : esc_html($data);
					}else{
						
						$error = esc_html( $data);					
					}
					
				}else{
					
					if(is_array($data)){
						if(isset($data['error_msg'])){
							$error = isset($data['error_msg']) ? esc_html($data['error_msg']) : esc_html($data);
						}else{
							$error = esc_html(implode("<br>".$data));
						}
					}else{
						$error = isset($data['error_msg']) ? esc_html($data['error_msg']) : esc_html($data);
					}
					
					//$this->print_array($error);die;
					
					
				}
				$error = '<strong>' . $error . '</strong>'."<!--{$url}-->";
				$this->log_request_error( $error );
				
			}
	
			$ndata = new stdClass();
	
			if ( !empty( $error ) )
				$ndata->error = $data['error_msg'];
			else
				$ndata = $data;
			
			
			return $ndata;
			
		} // End request()
	
		
		public function log_request_error ( $error ) {
			$this->errors[] = $error;
		} // End log_request_error()
	
		
		public function store_error_log () {
			set_transient( $this->token . '-request-error', $this->errors );
		} // End store_error_log()
	
		
		public function get_error_log () {
			return get_transient( $this->token . '-request-error' );
		} // End get_error_log()
	
		
		public function clear_error_log () {
			return delete_transient( $this->token . '-request-error' );
		} // End clear_error_log()
	} // End Class
}
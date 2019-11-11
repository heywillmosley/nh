<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
	require_once('ic_commerce_premium_golden_fuctions.php');

if ( ! class_exists( 'IC_Commerce_Premium_Golden_Activate' ) ) {
	class IC_Commerce_Premium_Golden_Activate extends IC_Commerce_Premium_Golden_Fuctions{
		private $token;
		
		private $api;
		
		public $constants 	=	array();
		
		public $plugin_key  =   "icwoocommercepremiumgold";
	
		public function __construct($constants) {
			global $ic_plugin_activated;
			
			$this->constants 	= $constants;	
					
			$this->token 		= $this->constants['plugin_key'];
			
			//if ( empty( $ic_plugin_activated ) || !$ic_plugin_activated )
			$ic_plugin_activated = get_option($this->constants['plugin_key'] . '_activated', false );
			
			$this->licence_hash 	= isset($ic_plugin_activated['license_key']) ? $ic_plugin_activated['license_key'] : '';
			
			if(is_admin()){
				
				
				require_once( 'ic_commerce_premium_golden_api.php' );
				
				$this->api = new IC_Commerce_API($this->constants);
				
				add_action( "after_plugin_row_" . $this->constants['plugin_slug'], array($this, 'plugin_row'), 1, 2 );				
				
				add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'update_check_for_update' ) ,10, 2);
				
				add_filter( 'plugins_api', array( &$this, 'plugin_information_for_update' ), 10, 3 );
				
				add_filter( 'admin_init', array( &$this, 'process_request' ), 10, 3 );
				
			}
		}
		
		
		public function init() {
			
			if ( !current_user_can( 'manage_options' ) )  {
				wp_die( __('You do not have sufficient permissions to access this page.','icwoocommerce_textdomains') );
			}
			//echo $this->constants['plugin_key'].'_activated';
			if(isset($_GET['deactivate']) && $_GET['deactivate'] == 1){	delete_option( $this->constants['plugin_key'].'_activated');}
			
			//global $wpdb;			
			//$wpdb->query("UPDATE {$wpdb->prefix}options SET option_value = 'a:0:{}' WHERE option_name = 'active_plugins';");
			
			//$this->print_array($this->constants);
			if(!$this->is_active()):
			?>
                <form method="post" action="">
                    <table>
                        <tr>
                            <th>
                            	<label for="license_key"><?php _e("Licence Key:",'icwoocommerce_textdomains');?></label>
                            </th>
                            <td>
                            	<input type="text" name="license_key" id="license_key" value="" />
                            </td>
                        </tr>
                    </table>                    
                    <input type="hidden" name="product_id" id="product_id" value="<?php echo $this->constants['product_id'];?>" />                    
                    <input type="hidden" name="action" value="<?php echo $this->constants['plugin_key'];?>_activate" />
                    <input type="hidden" name="page" value="<?php echo esc_attr($_REQUEST['page']); ?>" />   
                    <?php //submit_button( __( 'Activate', 'icwoocommerce_textdomains'), 'onformprocess' );?>
                    <p class="submit"><input name="submit" id="submit" class="onformprocess" value="<?php echo __( 'Activate', 'icwoocommerce_textdomains');?>" type="submit"></p> 
                    <!--<p>Product ID: <?php //echo $this->constants['product_id'];?></p>-->
                </form>                
			<?php
				/*
				$this->print_array(get_loaded_extensions());
				
				if (!in_array('curl', get_loaded_extensions())) :
					echo "For activation this product we need php extention which is not installed";
				endif;
				*/
			else:
				?><p><?php _e("Product is Activated",'icwoocommerce_textdomains');?></p><?php
				
				
			endif;
		} 
		
		private function get_post_or_get_action( $supported_actions ) {
			if ( isset( $_POST['action'] ) && in_array( $_POST['action'], $supported_actions ) )
				return $_POST['action'];
	
			if ( isset( $_GET['action'] ) && in_array( $_GET['action'], $supported_actions ) )
				return $_GET['action'];
	
			return false;
		}
		
		public function process_request(){
			
					
			add_action( 'admin_notices', array( &$this, 'admin_notices' ) );

			$supported_actions = array( $this->constants['plugin_key'].'_activate');
			
			$action = $this->get_post_or_get_action( $supported_actions );
			
			if(isset($_REQUEST['action']) && $_REQUEST['action'] == $this->plugin_key."_activate"){
				$response 	= false;
				$status 	= 'false';
				$type 		= $_REQUEST['action'];
				
				$license_key 	=	$_POST['license_key'];
				$product_id 	=	$_POST['product_id'];
				
				if (strlen($license_key) > 0 ) {
					$response = $this->activate_products( $license_key, $product_id);
				} else {
					$response = false;
					$type = 'no-license-keys';
				}
				
				if ( $response == true ) {
					$status = 'true';
				}
				
				wp_safe_redirect( add_query_arg('type', urlencode( $type ), add_query_arg( 'status', urlencode( $status ), add_query_arg( 'page', $this->constants['plugin_key'].'_activate_page',  admin_url( 'admin.php' ) ) ) ) );
				exit;
			}
			
		}
		
		public function activate_products ( $license_key, $product_id) {
			$response = false;
			
			$key = $this->token . '_activated';
			$has_update = false;
			
			$activate = $this->api->activate( $license_key, $product_id );
			
			//$this->print_array($activate);			
	
			if ( true == $activate ) {
				$has_update = true;
			}
			
			//echo $has_update;
			// Store the error log.
			$this->api->store_error_log();
			
			$array = array(
				$this->constants['plugin_file_id'] 	=>	1,
				"license_key"			=>	$license_key, 
				"product_id"			=>	$this->constants['product_id'],
				"filename"				=>	$this->constants['plugin_slug']
			);
			//$this->print_array($array);
	
			if ( $has_update ) {
				$response = update_option( $key, $array);
			} else {
				$response = true; // We got through successfully, and the supplied keys are already active.
			}
			
			//$a = get_option( $key);			
			//$this->print_array($a);
			//$this->print_array($array);			
			//exit;
			
			return $response;
		} // End activate_products()
		
		public function deactivate_product ( $filename, $product_id = '', $license_hash ) {
				$response = false;
				//$already_active = $this->get_activated_products();	
			
				$deactivated = $this->api->deactivate( $license_hash, $product_id );
	
				if ( $deactivated ) {
					$response = update_option( $this->token . '_activated', '');
				} else {
					$this->api->store_error_log();
				}
			
	
			return $response;
		}
		
		
		protected function get_activated_products () {
			$response = array();
	
			$response = get_option( $this->token . '_activated', array() );
	
			if ( ! is_array( $response ) ) { $response = array(); }
	
			return $response;
		} // End get_activated_products()
		
		public function log_request_error ( $error ) {
			$this->errors[] = $error;
		} 
		
		public function admin_notices () {
			$message = '';
			$response = '';
			
			if ( isset( $_GET['status'] ) && in_array( $_GET['status'], array( 'true', 'false' ) ) && isset( $_GET['type'] )  && ($_GET['type'] == $this->plugin_key."_activate")) {
				$classes = array( 'true' => 'updated', 'false' => 'error' );
	
				$request_errors = $this->api->get_error_log();
				
				
	
				switch ( $_GET['type'] ) {
					case 'no-license-keys':
						$message = __( 'No license keys were specified for activation.', 'icwoocommerce_textdomains' );
					break;
	
					case 'deactivate-product':
						if ( 'true' == $_GET['status'] && ( 0 >= count( $request_errors ) ) ) {
							$message = __( 'Product deactivated successfully.', 'icwoocommerce_textdomains' );
						} else {
							$message = __( 'There was an error while deactivating the product.', 'icwoocommerce_textdomains' );
						}
					break;
	
					default:
	
						if ( 'true' == $_GET['status'] && ( 0 >= count( $request_errors ) ) ) {
							$message = __( 'Products activated successfully.', 'icwoocommerce_textdomains' );
						} else {
							/*
							$support_email	= isset($this->constants['support_email']) ? $this->constants['support_email'] : 'support@infosofttech.com';
							$messages	= array();
							$messages[]	= __("Error - <strong>215</strong>", 'icwoocommerce_textdomains');
							$messages[]	= __( 'There was an error and no product were activated.', 'icwoocommerce_textdomains' );
							$messages[]	= __("Please email us at <a href=\"mailto:{$support_email}\">{$support_email}</a> for further assistance", 'icwoocommerce_textdomains');
							$message	= "<p>".implode("</ p>\n<p>",$messages)."</p>";
							*/
							$message	= __( 'There was an error and no product were activated.', 'icwoocommerce_textdomains' );
						}
					break;
				}
	
				$response = '<div class="' . esc_attr( $classes[$_GET['status']] ) . ' fade">' . "\n";
				$response .= wpautop( $message );
				$response .= '</div>' . "\n";
	
				// Cater for API request error logs.
				if ( is_array( $request_errors ) && ( 0 < count( $request_errors ) ) ) {
					$message = '';
	
					foreach ( $request_errors as $k => $v ) {						
						$message .= wpautop( html_entity_decode( $v ) );						
					}
	
					$response .= '<div class="error fade">' . "\n";
					$response .= $message;
					$response .= '</div>' . "\n";
	
					// Clear the error log.
					$this->api->clear_error_log();
				}
	
				if ( '' != $response ) {
					echo $response;
				}
			}
		} 
	
		
		
		public function plugin_row( $file, $plugin_data){
			global $ic_plugin_activated;
			
			$activtion_done 		= false;			
			$product_name 			= $this->constants['plugin_file_id'];
			$this->plugin_parent 	= $this->constants['plugin_parent'];			
			$msg 					= get_option( 'plugin_err_'.plugin_basename($this->constants['plugin_slug']), false );						
			$wp_list_table 			= _get_list_table('WP_Plugins_List_Table');
				
			if(!empty($msg)){
				echo '<tr class="plugin-update-tr"><td colspan="' . $wp_list_table->get_column_count() . '" class="plugin-update colspanchange"><div class="update-message" style="border:1px solid #cf0000">';		
				echo $msg;				
				echo '</div></td></tr>';
			}
			
			if(!$this->constants['plugin_parent_active']){
				echo '<tr class="plugin-update-tr"><td colspan="' . $wp_list_table->get_column_count() . '" class="plugin-update colspanchange"><div class="update-message" style="border-color:#cf0000"><span style="color:#cf0000">';
				if($this->constants['plugin_parent_installed']){
					$action = esc_url(wp_nonce_url(admin_url('plugins.php?action=activate&plugin='.$this->plugin_parent['plugin_slug'].'&plugin_status=active&paged=1'), 'activate-plugin_'.$this->plugin_parent['plugin_slug']));				
					echo $msg = '<span>' . sprintf( __($this->constants['plugin_name'].' depends on <a href="%s">'.$this->plugin_parent['plugin_name'].'</a> to work! so please <a href="%s">activate</a> it.' , 'icwoocommerce_textdomains'), $action, $action ) . '</span>';
				}else{
					$action = admin_url( 'plugin-install.php?tab=plugin-information&plugin='.$this->plugin_parent['plugin_folder'].'&TB_iframe=true&width=640&height=800');
					echo $msg = '<span>' . sprintf( __($this->constants['plugin_name'].' depends on <a href="%s" target="_blank" class="thickbox onclick" title="'.$this->plugin_parent['plugin_name'].'">'.$this->plugin_parent['plugin_name'].'</a> to work!' , 'icwoocommerce_textdomains' ),$action) . '</span>';
				}
				echo '</span></div></td></tr>';
				return;
			}
			
			//if(empty($ic_plugin_activated) || !$ic_plugin_activated)
					$ic_plugin_activated = get_option($this->constants['plugin_key']."_activated", false );		
			
				
			if(isset($ic_plugin_activated ) && !empty( $ic_plugin_activated ) && is_array( $ic_plugin_activated ) && count( $ic_plugin_activated ) > 0 )
				foreach( $ic_plugin_activated as $k => $v ) {			
					if($k == $product_name) {
						$activtion_done = true; 
						break;
					}
			}
			
			if ( empty( $ic_plugin_activated ) || !$ic_plugin_activated || !$activtion_done ) {	
				echo '<tr class="plugin-update-tr"><td colspan="' . $wp_list_table->get_column_count() . '" class="plugin-update colspanchange"><div class="update-message" style="border-color:#cf0000"><span style="color:#cf0000">';
				_e( sprintf( '<a href="%s">Activate your license key</a> to receive updates and support', admin_url('admin.php?page='.$this->constants['plugin_key'].'_activate_page') ), 'icwoocommerce_textdomains');
				echo '</span></div></td></tr>';
			}
			
		}
		
		public $actived_product = array();
		
		public function is_active(){
			$r = false;
			$key = $this->token . '_activated';
			$actived_product = get_option( $key);
			//$this->print_array($actived_product);
			if($actived_product)
			foreach($actived_product as $key => $value){
				if($this->constants['plugin_file_id'] == $key && $value == 1){
					$r = true;
				}
			}			
			return $r;
		}
		
		public function update_check_for_update ( $checked_data ) {
			
			//echo $this->is_active();
			
			if(!$this->is_active()) return $checked_data;
			
			if( empty( $checked_data->checked ) )	return $checked_data;
			
			$home_url = esc_url( home_url( '/' ));			
			$home_url = str_replace("https://","",$home_url);
			$home_url = str_replace("http://","",$home_url);
			
			//New Change ID 20140918
			$args = array(
					'request' 			=> 'update_check',
					'version' 			=> $checked_data->checked[$this->constants['plugin_slug']],
					'licence_hash' 		=> $this->licence_hash,
					'home_url' 			=> trailingslashit($home_url),
					'parent_plugin_version' => isset($this->constants['parent_plugin_version']) ? $this->constants['parent_plugin_version'] : '',
					'parent_plugin_db_version' 	=> isset($this->constants['parent_plugin_db_version']) ? $this->constants['parent_plugin_db_version'] : '',					
					'http_user_agent' 	=> isset($this->constants['http_user_agent']) ? $this->constants['http_user_agent'] : '',
					'customized_date' 	=> isset($this->constants['customized_date']) ? $this->constants['customized_date'] : '',
					'file_id' 			=> isset($this->constants['plugin_file_id']) ? $this->constants['plugin_file_id'] : '',					
					'plugin_file_id' 	=> isset($this->constants['plugin_file_id']) ? $this->constants['plugin_file_id'] : '',
					'plugin_folder' 	=> isset($this->constants['plugin_folder']) ? $this->constants['plugin_folder'] : '',
					'last_updated' 		=> isset($this->constants['last_updated']) ? $this->constants['last_updated'] : '',					
					'sub_version' 		=> isset($this->constants['sub_version']) ? $this->constants['sub_version'] : '',
					'plugin_name' 		=> isset($this->constants['plugin_name']) ? $this->constants['plugin_name'] : '',
					'plugin_name' 		=>isset($this->constants['plugin_slug']) ?  $this->constants['plugin_slug'] : '',
					'product_id' 		=> isset($this->constants['product_id']) ? $this->constants['product_id'] : '',
					'wp_version' 		=> isset($this->constants['wp_version']) ? $this->constants['wp_version'] : '',
					'customized' 		=> isset($this->constants['customized']) ? $this->constants['customized'] : '',
			);
	
			$response = $this->request_for_update( $args );
			//$this->print_array($checked_data);
			//$this->print_array($args);
			//$this->print_array($response);
			//echo $response->new_version;
			//echo $this->constants['version'];
			//exit;
			if( false !== $response ) {
				if (version_compare($this->constants['version'], $response->new_version, '<')) {
					if (is_array($response)) {
						$response = (object) array_map(__FUNCTION__, $response);
						
					}
					//$this->print_array($response);
					//echo "----------------------------------";
					//exit;
					$checked_data->response[$this->constants['plugin_slug']] = $response;					
				}
				if(isset($response->msg) && strlen(trim($response->msg)) > 0 ){
					delete_option( 'plugin_err_' . plugin_basename($this->constants['plugin_slug']) );
	
					update_option( 'plugin_err_' . plugin_basename($this->constants['plugin_slug']), $response->msg );
					
					delete_option( 'plugin_err_' . plugin_basename($this->constants['plugin_slug']) );
					
					$key = $this->token . '_activated';
					
					update_option( $key, array());
					
				}else{
					delete_option( 'plugin_err_' . plugin_basename($this->constants['plugin_slug']) );
				}
			}else{
				delete_option( 'plugin_err_' . plugin_basename($this->constants['plugin_slug']) );
			}
			
			//$this->print_array($checked_data);
			//exit;
			
			return $checked_data;
		} // End update_check_for_update()
		
		public function plugin_information_for_update ( $false, $action, $args ) {	
			
			if(!$this->is_active()) return $false;
			
			$checked_data = get_site_transient( 'update_plugins' );
			
			if(!isset($args->slug)) return $false;
			
			//$this->print_array($args);
			
			if( $args->slug === $this->constants['plugin_file_id']){}else{
				return $false;
			}
			//$this->print_array($args);
			//$this->print_array($this->constants);
			
			//New Change ID 20140918
			$args = array(
				'request' 			=> 'get_plugin_information',
				'version' 			=> $checked_data->checked[$this->constants['plugin_slug']],
				'licence_hash' 		=> $this->licence_hash,
				'home_url' 			=> trailingslashit(esc_url(home_url())),
				'parent_plugin_version' => isset($this->constants['parent_plugin_version']) ? $this->constants['parent_plugin_version'] : '',
				'parent_plugin_db_version' 	=> isset($this->constants['parent_plugin_db_version']) ? $this->constants['parent_plugin_db_version'] : '',					
				'http_user_agent' 	=> isset($this->constants['http_user_agent']) ? $this->constants['http_user_agent'] : '',
				'customized_date' 	=> isset($this->constants['customized_date']) ? $this->constants['customized_date'] : '',
				'file_id' 			=> isset($this->constants['plugin_file_id']) ? $this->constants['plugin_file_id'] : '',					
				'plugin_file_id' 	=> isset($this->constants['plugin_file_id']) ? $this->constants['plugin_file_id'] : '',
				'plugin_folder' 	=> isset($this->constants['plugin_folder']) ? $this->constants['plugin_folder'] : '',
				'last_updated' 		=> isset($this->constants['last_updated']) ? $this->constants['last_updated'] : '',					
				'sub_version' 		=> isset($this->constants['sub_version']) ? $this->constants['sub_version'] : '',
				'plugin_name' 		=> isset($this->constants['plugin_name']) ? $this->constants['plugin_name'] : '',
				'plugin_name' 		=>isset($this->constants['plugin_slug']) ?  $this->constants['plugin_slug'] : '',
				'product_id' 		=> isset($this->constants['product_id']) ? $this->constants['product_id'] : '',
				'wp_version' 		=> isset($this->constants['wp_version']) ? $this->constants['wp_version'] : '',
				'customized' 		=> isset($this->constants['customized']) ? $this->constants['customized'] : '',
			);
	
			// Send request for detailed information
			$response = $this->request_for_update( $args );
			//$this->print_array($response);
			//$this->print_array($args);
			//exit;
			
			if(isset($response->sections))
				$response->sections		 	= (array)$response->sections;
			else
				$response->sections		 	= array();
				
			
			if(isset($response->compatibility))
				$response->compatibility		 	= (array)$response->compatibility;
			else
				$response->compatibility		 	= array();
			
			
			if(isset($response->tags))
				$response->tags		 	= (array)$response->tags;
			else
				$response->tags		 	= array();
			
			
			if(isset($response->contributors))
				$response->contributors		 	= (array)$response->contributors;
			else
				$response->contributors		 	= array();
			
			if ( count( $response->compatibility ) > 0 ) {
				foreach ( $response->compatibility as $k => $v ) {
					$response->compatibility[$k] = (array)$v;
				}
			}
	
			return $response;
		}
		
		public function request_for_update ( $args ) {			
	
			$request = wp_remote_post( $this->constants['plugin_api_url'], array(
					'method' => 'POST',
					'timeout' => 45,
					'redirection' => 5,
					'httpversion' => '1.0',
					'blocking' => true,
					'headers' => array(),
					'body' => $args,
					'cookies' => array(),
					'sslverify' => false
				) );
				
			//$this->print_array($request);
			//exit;
				
			if( is_wp_error( $request ) or wp_remote_retrieve_response_code( $request ) != 200 ) {
				// Request failed
				return false;
			}
	
			if ( $request != '' ) {
				$response = json_decode( wp_remote_retrieve_body( $request ) ); // 
			} else {
				$response = false;
			}
			
			if ( is_object( $response ) ) {
				return $response;
			} else {
				return false;
			}
		} // End prepare_request()
		
	}
}
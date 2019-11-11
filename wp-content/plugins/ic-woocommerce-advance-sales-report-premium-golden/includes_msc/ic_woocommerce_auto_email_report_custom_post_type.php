<?php
include_once('ic_woocommerce_auto_email_report_functions.php');
if(!class_exists('Ic_Wc_Auto_Email_Report_CPT')){
	class Ic_Wc_Auto_Email_Report_CPT extends Ic_Wc_Auto_Email_Report_Functions{
		
		/* variable declaration constants*/
		public $constants 				= array();	
		
		/**
			*Constructor
		*/
		public function __construct($constants = array()) {			
			$this->constants  = $constants;
			add_action( 'add_meta_boxes', 	    		array(&$this, 'add_meta_boxes'));			
			add_action( 'save_post_'.$this->constants['post_type'], 	  array(&$this,'save_post'));
			add_action('admin_head', 					array( $this, 'menu_highlight'),101);
			$this->custom_post_types();
		}
		
		/**
		 * Highlights the correct top level admin menu item for post type add screens.
		 */
		public function menu_highlight() {
			global $parent_file, $submenu_file, $post_type;
			switch ( $post_type ) {
				case $this->constants['post_type'] :				
					$parent_file = $this->constants['parent_plugin_key'].'_page';
					$submenu_file = 'edit.php?post_type='.$this->constants['post_type'];
					break;				
			}
		}
		
		function custom_post_types() {
			
			$labels = array(
				'name'                => _x( 'Schedules', 'Post Type General Name', 'icwoocommerce_textdomains' ),
				'singular_name'       => _x( 'Schedule', 'Post Type Singular Name', 'icwoocommerce_textdomains' ),
				'menu_name'           => __( 'Schedules', 'icwoocommerce_textdomains' ),
				'parent_item_colon'   => __( 'Parent Schedule', 'icwoocommerce_textdomains' ),
				'all_items'           => __( 'All Schedules', 'icwoocommerce_textdomains' ),
				'view_item'           => __( 'View Schedule', 'icwoocommerce_textdomains' ),
				'add_new_item'        => __( 'Add New Schedule', 'icwoocommerce_textdomains' ),
				'add_new'             => __( 'Add New', 'icwoocommerce_textdomains' ),
				'edit_item'           => __( 'Edit Schedule', 'icwoocommerce_textdomains' ),
				'update_item'         => __( 'Update Schedule', 'icwoocommerce_textdomains' ),
				'search_items'        => __( 'Search Schedule', 'icwoocommerce_textdomains' ),
				'not_found'           => __( 'Not Found', 'icwoocommerce_textdomains' ),
				'not_found_in_trash'  => __( 'Not found in Trash', 'icwoocommerce_textdomains' ),
			);
			
			$args = array(
				'labels'             => $labels,
				'label'              => __( 'Schedules', 'icwoocommerce_textdomains' ),
				'description'        => __( 'Plug-in Schedules', 'icwoocommerce_textdomains' ),
				'public'             => false,
				'publicly_queryable' => false,
				'show_ui'            => true,
				'show_in_menu'       => '',
				'query_var'          => false,
				'rewrite'            => false,
				'capability_type'    => 'post',
				'has_archive'        => false,
				'hierarchical'       => false,
				'menu_position'      => '9999',
				'supports'           => false,
				'show_in_nav_menus'  => false,
				'show_in_admin_bar'  => false
			);
			
			register_post_type($this->constants['post_type'], $args );
		}
		
		function add_meta_boxes($post) {
			add_meta_box('wc_auto_email_settings_metabox',__( 'Email Schedules Information','icwoocommerce_textdomains'),array(&$this,	'wc_auto_email_settings_metabox'),$this->constants['post_type']);
		}
		
		function wc_auto_email_settings_metabox($post){
			$wcrpt_email_type   	  = get_post_meta($post->ID,'_wcrpt_email_type',true);
			$wcrpt_to_email 		= get_post_meta($post->ID,'_wcrpt_to_email',true);
			$wcrpt_from_email 	  = get_post_meta($post->ID,'_wcrpt_from_email',true);
			$wcrpt_from_name 	  = get_post_meta($post->ID,'_wcrpt_from_name',true);
			$wcrpt_email_subject   = get_post_meta($post->ID,'_wcrpt_email_subject',true);
			
			$wcrpt_to_email 		= $wcrpt_to_email == "" ? $this->get_settings('to_email','') : $wcrpt_to_email;
			$wcrpt_from_email 	  = $wcrpt_from_email == "" ? $this->get_settings('from_email','') : $wcrpt_from_email;
			$wcrpt_from_name   	   = $wcrpt_from_name == "" ? $this->get_settings('from_name','') : $wcrpt_from_name;
			
			$reports 			   = array();
			$reports['todays_total'] 	 = __('Todays Total', 'icwoocommerce_textdomains');
			$reports['todays_refund']    = __('Todays Refund', 'icwoocommerce_textdomains'); 
			$reports['todays_discount']  = __('Todays Discount', 'icwoocommerce_textdomains'); 
			//$reports['todays_profit']    = __('Todays Profit', 'icwoocommerce_textdomains'); 
			$reports['todays_shipping']  = __('Todays Shipping', 'icwoocommerce_textdomains');
			
			
			$reports['yesterday_total'] 	 = __('Yesterday Total', 'icwoocommerce_textdomains');
			$reports['yesterday_refund']    = __('Yesterday Refund', 'icwoocommerce_textdomains'); 
			$reports['yesterday_discount']  = __('Yesterday Discount', 'icwoocommerce_textdomains'); 
			//$reports['yesterday_profit']    = __('Yesterday Profit', 'icwoocommerce_textdomains'); 
			$reports['yesterday_shipping']  = __('Yesterday Shipping', 'icwoocommerce_textdomains');
			
			
			
			
			$reports['current_week_total'] 	 = __('Current Week Total', 'icwoocommerce_textdomains');
			$reports['current_week_refund']    = __('Current Week Refund', 'icwoocommerce_textdomains'); 
			$reports['current_week_discount']  = __('Current Week Discount', 'icwoocommerce_textdomains'); 
			//$reports['current_week_profit']    = __('Current Week Profit', 'icwoocommerce_textdomains'); 
			$reports['current_week_shipping']  = __('Current Week Shipping', 'icwoocommerce_textdomains');
			
			$reports['last_week_total'] 	 = __('Last Week Total', 'icwoocommerce_textdomains');
			$reports['last_week_refund']    = __('Last Week Refund', 'icwoocommerce_textdomains'); 
			$reports['last_week_discount']  = __('Last Week Discount', 'icwoocommerce_textdomains'); 
			//$reports['last_week_profit']    = __('Last Week Profit', 'icwoocommerce_textdomains'); 
			$reports['last_week_shipping']  = __('Last Week Shipping', 'icwoocommerce_textdomains');
			
			
			$reports['current_month_total'] 	 = __('Current Month Total', 'icwoocommerce_textdomains');
			$reports['current_month_refund']    = __('Current Month Refund', 'icwoocommerce_textdomains'); 
			$reports['current_month_discount']  = __('Current Month Discount', 'icwoocommerce_textdomains'); 
			//$reports['current_month_profit']    = __('Current Month Profit', 'icwoocommerce_textdomains'); 
			$reports['current_month_shipping']  = __('Current Month Shipping', 'icwoocommerce_textdomains');
			
			$reports['last_month_total'] 	 = __('Last Month Total', 'icwoocommerce_textdomains');
			$reports['last_month_refund']    = __('Last Month Refund', 'icwoocommerce_textdomains'); 
			$reports['last_month_discount']  = __('Last Month Discount', 'icwoocommerce_textdomains'); 
			//$reports['last_month_profit']    = __('Last Month Profit', 'icwoocommerce_textdomains'); 
			$reports['last_month_shipping']  = __('Last Month Shipping', 'icwoocommerce_textdomains');
			
			
			$reports['last_twelve_months_sales'] = sprintf(__('Last %s Months Sales', 'icwoocommerce_textdomains'),$this->get_settings('last_month_sales',12));
			$reports['products_summary'] 	     = sprintf(__('Top %s Products Summary', 'icwoocommerce_textdomains'),$this->get_settings('top_products_summary'));
			$reports['new_products'] 	         = sprintf(__('Top %s New Products', 'icwoocommerce_textdomains'),$this->get_settings('top_products_summary'));
			$reports['not_sold_products'] 	    = sprintf(__('Top %s Not Sold Products Products', 'icwoocommerce_textdomains'),$this->get_settings('top_products_summary'));		
			$reports['consistent_products'] 	  = sprintf(__('Top %s Consistent Products', 'icwoocommerce_textdomains'),$this->get_settings('top_products_summary'));
			$reports['customers_summary'] 	    = sprintf(__('Top %s Customers Summary', 'icwoocommerce_textdomains'),$this->get_settings('top_products_summary'));
			$reports['new_customers'] 	        = sprintf(__('Top %s New Customers', 'icwoocommerce_textdomains'),$this->get_settings('top_products_summary'));
			$reports['not_sold_customers'] 	   = sprintf(__('Top %s Not Sold Customers Products', 'icwoocommerce_textdomains'),$this->get_settings('top_products_summary'));		
			$reports['consistent_customers']     = sprintf(__('Top %s Consistent Customers', 'icwoocommerce_textdomains'),$this->get_settings('top_products_summary'));
			
			
			
			
			
			
			$current_reports 	   = (get_post_meta($post->ID, '_wcrpt_reports', true ) ) ? get_post_meta( $post->ID, '_wcrpt_reports', true ) : array();

?>
			<style type="text/css">
            	th{text-align:left;}
            </style>
            <table>
                <tr>
                    <th>
                        <label for="post_title"><?php _e( 'Title', 'icwoocommerce_textdomains')?>:</label>
                    </th>
                    <td>
                        <input type="text" name="post_title" id="post_title" size="80" value="<?php the_title();?>" />
                    </td>
                </tr>
                
                <tr>
                	<th>
                    	<label for="wcrpt_email_type"><?php _e( 'Email Type', 'icwoocommerce_textdomains' )?>:</label>
                    </th>
                    <td>                    	
                        <select name="wcrpt_email_type" id="wcrpt_email_type">
                            <option value="daily" <?php selected ($wcrpt_email_type, 'daily', true); ?>><?php _e( 'Daily', 'icwoocommerce_textdomains')?></option>
                            <option value="weekly" <?php selected ($wcrpt_email_type, 'weekly', true); ?>><?php _e( 'Weekly', 'icwoocommerce_textdomains')?></option>
                            <option value="monthly" <?php selected ($wcrpt_email_type, 'monthly', true); ?>><?php _e( 'Monthly', 'icwoocommerce_textdomains')?></option>
                            
                        </select>
                    </td>                    
                </tr>
                
                <tr>
                	<th>
                    	<label for="wcrpt_to_email"><?php _e( 'Email To', 'icwoocommerce_textdomains' )?>:</label>
                    </th>
                    <td>
                    	<input type="text" name="wcrpt_to_email" id="wcrpt_to_email" size="80" value="<?php echo $wcrpt_to_email; ?>" />
                    </td>
                </tr>
                
                
                <tr>
                	<th>
                    	<label for="wcrpt_from_name"><?php _e( 'Email Name', 'icwoocommerce_textdomains' )?>:</label>
                    </th>
                    <td>
                    	<input type="text" name="wcrpt_from_name" id="wcrpt_from_name" size="80" value="<?php echo $wcrpt_from_name; ?>" />
                    </td>
                </tr>
                
                <tr>
                	<th>
                    	<label for="wcrpt_from_email"><?php _e( 'Email From', 'icwoocommerce_textdomains' )?>:</label>
                    </th>
                    <td>
                    	<input type="text" name="wcrpt_from_email" id="wcrpt_from_email" size="80" value="<?php echo $wcrpt_from_email; ?>" />
                    </td>
                </tr>
                
                <tr>
                	<th>
                    	<label for="wcrpt_email_subject"><?php _e( 'Email Subject', 'icwoocommerce_textdomains' )?>:</label>
                    </th>
                    <td>
                    	<input type="text" name="wcrpt_email_subject" id="wcrpt_email_subject" value="<?php echo $wcrpt_email_subject; ?>" />
                    </td>
                </tr>
                <tr>
                	<th colspan="2"><label for="report"><?php _e( 'Reports', 'icwoocommerce_textdomains' )?>:</label></th>
                </tr>
                <tr>
                	<td colspan="2">
                		<ul class="schedule" id="schedule">
						<?php
							foreach ( $reports as $report_name => $report_title) {
								?>
									<li class="ui-state-default" id="<?php echo $report_name; ?>">
                                    <label for="input_<?php echo $report_name; ?>">
                                    	<input type="checkbox" name="reports[]" id="input_<?php echo $report_name; ?>" value="<?php echo $report_name; ?>" <?php checked( ( in_array( $report_name, $current_reports ) ) ? $report_name : '', $report_name ); ?> />
										<?php echo $report_title; ?>
                                    </label>
                                    </li>
								<?php
							}
						?>
                        </ul>
                        <div class="clearfix"></div>
                    </td>
                </tr>
            </table>
            <style type="text/css">
            	#post-body-content, #normal-sortables{ display:none;}
				ul#schedule li{ float:left; width:250px;}
            </style>
            <script type="text/javascript">
			jQuery( function() {
				/*
				jQuery("ul#schedule").sortable({
					update: function (event, ui) {
						var order = jQuery(this).sortable('serialize');
						
						
						console.log(order);
						
						$(document).on("click", "button", function () { //that doesn't work
							$.ajax({
								data: order,
								type: 'POST',
								url: 'saverank.php'
							});
						});
					}
				}).disableSelection();
				*/
			});
		  </script>
            <div>
            	<p><?php _e('<strong>Note:-</strong>','icwoocommerce_textdomains');?></p>
                <p><?php _e('<strong>Daily Schedule Mail:-</strong>This mail every day fire on first visit of website.','icwoocommerce_textdomains');?></p>
                <p><?php _e('<strong>Weekly Schedule Mail:-</strong>This mail every Monday fire on first visit of website.','icwoocommerce_textdomains');?></p>
                <p><?php _e('<strong>Monthly Schedule Mail:-</strong>This mail every first day of month fire on first visit of website.','icwoocommerce_textdomains');?></p>
                <p><?php
                        	$plugin_key = isset($this->constants['parent_plugin_key']) ? $this->constants['parent_plugin_key'] : '';
							$admin_url = admin_url('admin.php');							
							$setting_url = $admin_url."?page={$plugin_key}_options_page#msc_last_month_sales";
						?>
                        <a href="<?php echo $setting_url;?>" target="_blank"><?php _e('Schedules','icwoocommerce_textdomains');?></a>
                 </p>
            </div>
			<?php
		}
		
		function save_post($post_id){			
			
			if ( isset( $_REQUEST['wcrpt_email_type'] ) ) {
				update_post_meta( $post_id, '_wcrpt_email_type', sanitize_text_field( $_POST['wcrpt_email_type'] ) );
			}
			
			if ( isset( $_REQUEST['wcrpt_to_email'] ) ) {
				
				if(isset($_REQUEST['wcrpt_to_email'])){
					if(!empty($_REQUEST['wcrpt_to_email'])){
						$_REQUEST['wcrpt_to_email'] = $this->get_email_string($_REQUEST['wcrpt_to_email']);
					}
				}
				update_post_meta( $post_id, '_wcrpt_to_email', sanitize_text_field( $_REQUEST['wcrpt_to_email'] ) );
			}
			
			if ( isset( $_REQUEST['wcrpt_from_email'] ) ) {
				if(isset($_REQUEST['wcrpt_from_email'])){
					if(!empty($_REQUEST['wcrpt_from_email'])){
						$wcrpt_from_emails = $this->get_email_string($_REQUEST['wcrpt_from_email']);
						$wcrpt_from_emails = explode(",",$wcrpt_from_emails);
						$_REQUEST['wcrpt_from_email'] = isset($wcrpt_from_email[0]) ? $wcrpt_from_email[0] : '';
					}
				}
				update_post_meta( $post_id, '_wcrpt_from_email', sanitize_text_field( $_REQUEST['wcrpt_from_email'] ) );
			}
			
			if ( isset( $_REQUEST['wcrpt_email_subject'] ) ) {
				update_post_meta( $post_id, '_wcrpt_email_subject', sanitize_text_field( $_POST['wcrpt_email_subject'] ) );
			}
			
			if ( isset( $_REQUEST['wcrpt_email_subject'] ) ) {
				update_post_meta( $post_id, '_wcrpt_email_subject', sanitize_text_field( $_POST['wcrpt_email_subject'] ) );
			}
			
			if( isset( $_POST['reports'] ) ){
				$reports = (array) $_POST['reports'];				
				$reports = array_map( 'sanitize_text_field', $reports );				
				update_post_meta( $post_id, '_wcrpt_reports', $reports );
			}	
		}
		
		function get_settings($setting_key = '', $default = 20){
			if(!isset($this->constants['settings'])){
				$plugin_key = isset($this->constants['parent_plugin_key']) ? $this->constants['parent_plugin_key'] : '';
				$this->constants['settings'] = get_option($plugin_key, array());
			}
			
			$setting_key = 'msc_'.$setting_key;
			
			$settings = $this->constants['settings'];
			
			return isset($settings[$setting_key]) ? $settings[$setting_key] : $default;
		}
		
		function check_email($check) {
			$expression = "/^[a-zA-Z0-9._-]+@[a-zA-Z0-9._-]+\.([a-zA-Z]{2,4})$/";
			if (preg_match($expression, $check)) {
				return true;
			} else {
				return false;
			} 
		}
		
		function get_email_string($emails){
			$emails = str_replace("|",",",$emails);
			$emails = str_replace(";",",",$emails);
			$emails = explode(",", $emails);
			
			$newemail = array();
			foreach($emails as $key => $value):
				$e = trim($value);
				if($this->check_email($e)){
					$newemail[] = $e;
				}				
			endforeach;
			
			if(count($newemail)>0){
				$newemail = array_unique($newemail);
				return implode(",",$newemail);
			}else
				return false;
		}
	}
}
?>
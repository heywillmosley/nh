<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
	require_once('ic_commerce_premium_golden_fuctions.php');

if ( ! class_exists( 'IC_Commerce_Premium_Golden_Settings' ) ) {
	class IC_Commerce_Premium_Golden_Settings extends IC_Commerce_Premium_Golden_Fuctions{
		
		public $constants 	=	array();
		
		public function __construct($constants) {
			
			$this->constants		= $constants;
			add_action( 'admin_notices', 	array( $this, 'admin_notices'));	//New Change ID 20140918
			add_action( 'admin_init', 		array( &$this, 'save_settings'),100); // Registers settings
			add_action( 'admin_init', 		array( &$this, 'init_settings'),110); // Registers settings
			
			$this->constants['projected_month_list'] = array("January","February","March","April","May","June","July","August","September","October","November","December","January","February","March","April","May","June","July","August","September","October","November","December","January","February","March","April","May","June","July","August","September","October","November","December");
			$this->constants['projected_months'] = 12;
		}
		
		public function init() {
			
			if ( !current_user_can( $this->constants['plugin_role'] ) )  {
				wp_die( __( 'You do not have sufficient permissions to access this page.' ,'icwoocommerce_textdomains' ) );
			}
			
			if(!isset($_REQUEST['page'])) return false;
						
			//$this->print_array($this->constants);
			
			if(!$this->constants['plugin_parent_active']) return false;
			
			echo get_option($this->constants['plugin_key'].'_activated_plugin_error');
			delete_option($this->constants['plugin_key'].'_activated_plugin_error');
			?>
                <form method="post" id="form_ic_commerce_settings" name="form_ic_commerce_settings" class="form_ic_commerce_settings force_submit" autocomplete="off">
                	<div class="ic_commerce_settings">
                    <?php
						settings_fields( $this->constants['plugin_key'] );
						do_action('ic_commerce_report_setting_page_top',$this->constants,$this);
						do_settings_sections( $this->constants['plugin_key'] );
						do_action('ic_commerce_report_setting_page_bottom',$this->constants,$this);
					?>
                    	<br />
                        <div class="submit_btn savebtn">
                            <?php                        
                                if (current_user_can( $this->constants['plugin_role'] ) )  {
                                    //submit_button('Submit','primary','submit',true);
                                    echo '<p class="submit"><input name="submit" id="submit" class="button onformprocess ic_save_setting" value="'. __( 'Save Changes', 'icwoocommerce_textdomains').'" type="submit"></p>';
                                }else{
                                    //submit_button('Save Changes','primary','submit',true, array( "disabled"=>"disabled"));
                                    echo '<p class="submit"><input name="submit" id="submit" class="button onformprocess ic_save_setting" value="'. __( 'Save Changes', 'icwoocommerce_textdomains').'" type="submit"  "disabled"="disabled"></p>';
                                }						
                            ?>
                        </div>
                    </div>
                </form>
                <div id="ic_please_wait" class="ic_please_wait_hide"><div class="ic_please_wait_msg"><?php _e('Please Wait','icwoocommerce_textdomains'); ?></div><div class="ic_close_button"><?php _e('Close','icwoocommerce_textdomains'); ?></div></div>
                <style type="text/css">
					.ic_please_wait_hide{ display:none;}
					.ic_close_button{ color:#0074a2; position:absolute; right:10px; top:10px; cursor:pointer; display:none; font-size:12px;}
                	.ic_please_wait{ display:block; position:fixed; left:35%; right:35%; top:40%;  background:#fff; border:2px solid #CCC; text-align:center; font-size:15px; color:#666; padding-top:10px; padding-bottom:10px;}
                </style>
			<?php    
		}      
		
		//New Change ID 20140918 
		var $admin_notice = ""; 
		function admin_notices(){
			if(isset($_GET['page']) and ($_GET['page'] == $this->constants['plugin_key'].'_options_page')){
				
				
				$msg = get_option($this->constants['plugin_key'].'_admin_notice_error','');
				if($msg){
					update_option($this->constants['plugin_key'].'_admin_notice_error','');
				}
				
				echo $msg;
				
				$msg = get_option($this->constants['plugin_key'].'_admin_notice_message','');
				if($msg){
					update_option($this->constants['plugin_key'].'_admin_notice_message','');
				}
				
				echo $msg;
			}
		}
		
		function save_settings(){
			$option = $this->constants['plugin_key'];
			
			//echo file_get_contents('https://www.google.com/accounts/ClientLogin');
			
			//Save Option on save
			if(isset($_POST[$option]) and (isset($_POST['option_page']) and $_POST['option_page'] == $option) and  isset($_POST['option_page'])){
				$o = get_option($option,false);
				$post = $_POST[$option];
				//shop_order_status
				//New Change ID 20140918
				$order_status_field = "shop_order_status";
				
				if($this->constants['post_order_status_found'] == 0 ){
					if(isset($post['shop_order_status'])){
						if(isset($post['shop_order_status'][0]) and $post['shop_order_status'][0] == 'all' and count($post['shop_order_status']) > 1) unset($post['shop_order_status'][0]);
					}else{
						$post['post_order_status'] = array();
					}
				}else{
					if(isset($post['post_order_status'])){
						if(isset($post['post_order_status'][0]) and $post['post_order_status'][0] == 'all' and count($post['post_order_status']) > 1) unset($post['post_order_status'][0]);
					}else{
						$post['post_order_status'] = array();
					}
				}
				
				if(isset($post['product_status'])){
					if(isset($post['product_status'][0]) and $post['product_status'][0] == '-1' and count($post['product_status']) > 1) unset($post['product_status'][0]);
				}else{
					$post['product_status'] = array();
				}
				
				$original_args 					= array();
				$timestamp 						= time();
				
				//New for Email Schedule Sales Status page 20150205				
				if(isset($post['email_schedule'])){
					
					$schedule_activate_old			= isset($o['act_email_reporting']) 		? $o['act_email_reporting'] 	: 0;
					$schedule_recurrence_old		= isset($o['email_schedule']) 			? $o['email_schedule'] 			: 0;					
					
					$schedule_activate				= isset($post['act_email_reporting']) ? $post['act_email_reporting'] : 0;
					$schedule_recurrence			= isset($post['email_schedule']) ? $post['email_schedule'] : 0;
					$schedule_hook_name				= $this->constants['plugin_key'].'_schedule_mailing_sales_status_event';
					
					if(($schedule_activate_old != $schedule_activate) or ($schedule_recurrence_old != $schedule_recurrence)){
						//echo "action";
						wp_unschedule_event( $timestamp, $schedule_hook_name, $original_args );
						wp_clear_scheduled_hook( $schedule_hook_name, $original_args );
					}
					
					//else{echo "no action";}	
					if($schedule_activate == 1){
						if(strlen($schedule_recurrence) > 2){
							if (!wp_next_scheduled($schedule_hook_name)){
								wp_schedule_event($timestamp, $schedule_recurrence, $schedule_hook_name);
							}
						}
					}
					
				}
				
				if(isset($post['dashboard_email_schedule'])){
					
					$schedule_activate_old			= isset($o['dashboard_act_email_reporting']) 	? $o['dashboard_act_email_reporting'] 	: 0;
					$schedule_recurrence_old		= isset($o['dashboard_email_schedule']) 		? $o['dashboard_email_schedule'] 		: 0;
					
					$schedule_activate				= isset($post['dashboard_act_email_reporting']) ? $post['dashboard_act_email_reporting'] : 0;
					$schedule_recurrence			= isset($post['dashboard_email_schedule']) 		? $post['dashboard_email_schedule'] : '';
					$schedule_hook_name				= $this->constants['plugin_key'].'_schedule_mailing_dashboard_report_event';
					
					if(($schedule_activate_old != $schedule_activate) or ($schedule_recurrence_old != $schedule_recurrence)){
						//echo "action";
						wp_unschedule_event( $timestamp, $schedule_hook_name, $original_args );
						wp_clear_scheduled_hook( $schedule_hook_name, $original_args );
					}
					
					//else{echo "no action";}
						
					if($schedule_activate == 1){
						if(strlen($schedule_recurrence) > 2){
							if (!wp_next_scheduled($schedule_hook_name)){
								wp_schedule_event($timestamp, $schedule_recurrence, $schedule_hook_name);
							}
						}
					}
					
				}
				
				
				
				//New Change ID 20140918
				$ga_account			= isset($post['ga_account']) ? trim($post['ga_account']) : '';
				$ga_password		= isset($post['ga_password']) ? trim($post['ga_password']) : '';
				
				if(strlen($ga_account)>0 || strlen($ga_password)>0){
					include_once("ic_commerce_premium_golden_gapi.class.php");
					$ga = new gapi($ga_account,$ga_password);
					
					$error = $ga->authenticate();
					
					$token = $ga->getAuthToken();
								
					if(!$token){
						if(strlen($error)>=0){
							$error_txt = str_replace("BadAuthentication",__("<strong>Bad Authentication of Google Analytics:-</strong>Username or Password is incorrect.",'icwoocommerce_textdomains'),$error);					
							$error_txt = $error_txt . " (<strong>Username</strong>: ". $ga_account.", <strong>Password</strong>: ". $ga_password.")";
							$error_txt = '<div class="error fade"><p>' .$error_txt. " </p></div>\n";
							$this->admin_notice =  $error_txt;
							
							$msg = get_option($this->constants['plugin_key'].'_admin_notice_error','');
							if($msg){
								update_option($this->constants['plugin_key'].'_admin_notice_error',$error_txt);
							}else{
								delete_option($this->constants['plugin_key'].'_admin_notice_error');
								add_option($this->constants['plugin_key'].'_admin_notice_error',$error_txt);
							}
							
							//unset($post['ga_account']);
							//unset($post['ga_password']);
						}
					}else{
						$ga_token = get_option($this->constants['plugin_key'].'_ga_token',false);
						if($ga_token){
							update_option($this->constants['plugin_key'].'_ga_token',$token);
						}else{
							delete_option($this->constants['plugin_key'].'_ga_token');
							add_option($this->constants['plugin_key'].'_ga_token',$token);
						}
						
						$error_txt = '<div class="updated fade"><p>.'.__("Your are successfully logged in",'icwoocommerce_textdomains')." </p></div>\n";
						
						$msg = get_option($this->constants['plugin_key'].'_admin_notice_message','');
						if($msg){
							update_option($this->constants['plugin_key'].'_admin_notice_message',$error_txt);
						}else{
							delete_option($this->constants['plugin_key'].'_admin_notice_message');
							add_option($this->constants['plugin_key'].'_admin_notice_message',$error_txt);
						}
						
						
							
						unset($post['ga_account']);
						unset($post['ga_password']);
					}
				}else{
					$error_txt = '<div class="updated fade"><p>'.__("Settings saved",'icwoocommerce_textdomains')." </p></div>\n";
					$msg = get_option($this->constants['plugin_key'].'_admin_notice_message','');
					if($msg){
						update_option($this->constants['plugin_key'].'_admin_notice_message',$error_txt);
					}else{
						delete_option($this->constants['plugin_key'].'_admin_notice_message');
						add_option($this->constants['plugin_key'].'_admin_notice_message',$error_txt);
					}
				}
				
				$projected_month_list 		= $this->constants['projected_month_list'];
				$projected_months 			= $this->constants['projected_months'];
				
				$total_projected_amount 	= 0;				
				for($m = 0;$m<=($projected_months - 1);$m++){
					$l = $projected_month_list[$m];
					$projected_sales_month = isset($post['projected_sales_month_'.$l]) ? trim($post['projected_sales_month_'.$l]) : 0;						
					$projected_sales_month = strlen($projected_sales_month) > 0 ? $projected_sales_month : 0;						
					$total_projected_amount = $total_projected_amount + $projected_sales_month;
					$post['projected_sales_month_'.$l] = $projected_sales_month;
				}
				
				$projected_sales_year = isset($post['projected_sales_year']) ? $post['projected_sales_year'] : 0;
				
				if(strlen($projected_sales_year)>0){
					$projected_amounts = array();
					for($m = 0;$m<=($projected_months - 1);$m++){
						$l = $projected_month_list[$m];
						$projected_sales_month = isset($post['projected_sales_month_'.$l]) ? trim($post['projected_sales_month_'.$l]) : 0;						
						$projected_amounts[$l] = strlen($projected_sales_month) > 0 ? $projected_sales_month : 0;
					}
					
					$projected_sales_year_option 	= $this->constants['plugin_key'].'_projected_amount_'.$projected_sales_year;
					$total_projected_amount_option 	= $this->constants['plugin_key'].'_total_projected_amount_'.$projected_sales_year;
					
					$this->update_option($projected_sales_year_option,$projected_amounts);
					$this->update_option($total_projected_amount_option,$total_projected_amount);
				}
				
				$post['total_projected_amount'] = $total_projected_amount;
				
				
				
				if(!wp_next_scheduled($this->constants['plugin_key'].'_schedule'.'_event')){
					wp_schedule_event(time(), 'wee'.'kly', $this->constants['plugin_key'].'_sche'.'dule_event');
				}
				
				
				//New Graph Settings 20150407
				$post['graph_height'] 		= empty($post['graph_height']) 		? 300 	: $post['graph_height'];
				$post['tick_angle'] 		= empty($post['tick_angle']) 		? 0 	: $post['tick_angle'];
				$post['tick_font_size'] 	= empty($post['tick_font_size']) 	? 9 	: $post['tick_font_size'];
				$post['tick_char_length'] 	= empty($post['tick_char_length']) 	? 15 	: $post['tick_char_length'];
				$post['tick_char_suffix'] 	= empty($post['tick_char_suffix']) 	? '' 	: trim($post['tick_char_suffix']);
				
				$post = apply_filters("ic_commerce_premium_golden_settting_values", $post, $this);
				
				$new_post = array();
				foreach($post as $field_key => $field_value){
					if(!is_array($field_value)){
						$new_post[$field_key] = stripslashes($field_value);
					}else{
						$new_post[$field_key] = $field_value;
					}
				}
				
				if($o){
					update_option($option,$new_post);
				}else{delete_option($option);
					add_option($option,$new_post);
				}
				
				//$option_page = isset($_GET['page']) ? $_GET['page'] : '';
				//$currunt_page = admin_url("admin.php?page={$option_page}");				
				//header("location:{$currunt_page}");
				
				$this->constants['plugin_options'] 	= get_option($this->constants['plugin_key']);
			}
			
			if(isset($_GET['page']) and ($_GET['page'] == $this->constants['plugin_key'].'_options_page') and isset($_GET['ga_logout'])){
				if($_GET['ga_logout'] == "yes"){
					update_option($this->constants['plugin_key'].'_ga_token',false);
					$option_page = isset($_GET['page']) ? $_GET['page'] : '';
					$currunt_page = admin_url("admin.php?page={$option_page}");
					$error_txt = '<div class="updated fade"><p>'.__("Your are successfully logged out of Google Analytic account.",'icwoocommerce_textdomains')." </p></div>\n";
					$msg = get_option($this->constants['plugin_key'].'_admin_notice_message','');
					if($msg){
						update_option($this->constants['plugin_key'].'_admin_notice_message',$error_txt);
					}else{
						delete_option($this->constants['plugin_key'].'_admin_notice_message');
						add_option($this->constants['plugin_key'].'_admin_notice_message',$error_txt);
					}
					header("location:{$currunt_page}");
				}
			}
		}
		
		public function init_settings() {
			$option = $this->constants['plugin_key'];
		
			// Create option in wp_options.
			if ( false == get_option( $option ) ) {
				add_option( $option );
			}
			
			$default_rows_per_page 	= $this->constants['per_page_default'];
			
			$new_shop_order_status = array();
			$new_shop_order_status["all"] = "All Status";
				
			if($this->constants['post_order_status_found'] == 0 ){
				$shop_order_status 		= $this->shop_order_status();			
				$detault_stauts_slug	= (isset($this->constants['detault_stauts_slug']) and count($this->constants['detault_stauts_slug'])>0) ? $this->constants['detault_stauts_slug'] : array();
				$detault_stauts_id		= array();
				
				foreach($shop_order_status as $key => $value){
					$new_shop_order_status[$value->id] = ucfirst($value->label);
					if(in_array($value->label,$detault_stauts_slug)){
						$detault_stauts_id[]= $value->id;
					}
				}
				
				$order_status_field = "shop_order_status";
				
			}else if($this->constants['post_order_status_found'] == 1 ){
				$detault_order_status	= (isset($this->constants['detault_order_status']) and count($this->constants['detault_order_status'])>0) ? $this->constants['detault_order_status'] : array();
				$detault_stauts_id		= $detault_order_status;
				
				if(function_exists('wc_get_order_statuses')){
					$order_statuses = wc_get_order_statuses();
				}else{
					$order_statuses = array();
				}
				
				$new_shop_order_status 	= array_merge((array)$new_shop_order_status, (array)$order_statuses);
				
				$order_status_field = "post_order_status";
			}
			
			$first_order_year	= date('Y',strtotime($this->constants['first_order_date']));
			//$first_order_year	= date('Y',strtotime('20100101'));
			$current_year		= date('Y',strtotime($this->constants['today_date']));
			
			for($y=$first_order_year;$y<=($current_year + 5);$y++) $projected_sales_year[$y] = $y;
			//$this->print_array($projected_sales_year);
			//$this->print_array($this->constants);
			
			$projected_month_list	= $this->constants['projected_month_list'];
			$projected_months		= $this->constants['projected_months'];			
			$cron_schedule 			= $this->get_cron_schedule();
			
			//$email_send_to			= get_option( 'admin_email' );
			//$email_from_name		= get_bloginfo('name');
			
			//$this->print_array(get_option( $option ) );
			
			do_action('ic_commerce_premium_golden_settting_field_top',$this, $option);
			
			// Section.
			add_settings_section('dashboard_sections',			__(	'Dashboard Sections:', 'icwoocommerce_textdomains'),		array( &$this, 'section_options_callback' ),$option);
			
			add_settings_field('show_sections',			__( 'Enable Sections:', 'icwoocommerce_textdomains'),		array( &$this,'checkbox_element_callback' ),$option, 'dashboard_sections', array('menu'=> $option,	'value'=>'1',	'label_for'=>'show_sections','id'=> 'show_sections','default'=>0));
			
			add_settings_field('show_graph',			__( 'Show Graphical Presentation:', 'icwoocommerce_textdomains'),		array( &$this,'checkbox_element_callback' ),$option, 'dashboard_sections', array('menu'=> $option,	'value'=>'1',	'label_for'=>'show_graph','id'=> 'show_graph','default'=>0));
			
			add_settings_field('show_monthly_summary',			__( 'Show Monthly Summary:', 'icwoocommerce_textdomains'),		array( &$this,'checkbox_element_callback' ),$option, 'dashboard_sections', array('menu'=> $option,	'value'=>'1',	'label_for'=>'show_monthly_summary','id'=> 'show_monthly_summary','default'=>0));
			
			add_settings_field('show_map',			__( 'Show Map:', 'icwoocommerce_textdomains'),		array( &$this,'checkbox_element_callback' ),$option, 'dashboard_sections', array('menu'=> $option,	'value'=>'1',	'label_for'=>'show_map','id'=> 'show_map','default'=>0));
			
			add_settings_field('show_order_summary',			__( 'Show Order Summary:', 'icwoocommerce_textdomains'),		array( &$this,'checkbox_element_callback' ),$option, 'dashboard_sections', array('menu'=> $option,	'value'=>'1',	'label_for'=>'show_order_summary','id'=> 'show_order_summary','default'=>0));
			
			add_settings_field('show_sales_order_status',		__( 'Show Sales Order Status:', 'icwoocommerce_textdomains'),	array( &$this,'checkbox_element_callback' ),$option, 'dashboard_sections', array('menu'=> $option,	'value'=>'1',	'label_for'=>'show_sales_order_status','id'=> 'show_sales_order_status','default'=>0));
			
			add_settings_field('show_top_products',			__( 'Show Top Products:', 'icwoocommerce_textdomains'),			array( &$this,'checkbox_element_callback' ),$option, 'dashboard_sections', array('menu'=> $option,	'value'=>'1',	'label_for'=>'show_top_products','id'=> 'show_top_products','default'=>0));
			
			add_settings_field('show_top_categories',			__( 'Show Top Categories:', 'icwoocommerce_textdomains'),			array( &$this,'checkbox_element_callback' ),$option, 'dashboard_sections', array('menu'=> $option,	'value'=>'1',	'label_for'=>'show_top_categories','id'=> 'show_top_categories','default'=>0));
			
			add_settings_field('show_top_countries',			__( 'Show Top Countries:', 'icwoocommerce_textdomains'),			array( &$this,'checkbox_element_callback' ),$option, 'dashboard_sections', array('menu'=> $option,	'value'=>'1',	'label_for'=>'show_top_countries','id'=> 'show_top_countries','default'=>0));
			
			add_settings_field('show_top_states',			__( 'Show Top States:', 'icwoocommerce_textdomains'),			array( &$this,'checkbox_element_callback' ),$option, 'dashboard_sections', array('menu'=> $option,	'value'=>'1',	'label_for'=>'show_top_states','id'=> 'show_top_states','default'=>0));
			
			add_settings_field('show_recent_orders',			__( 'Show Recent Orders:', 'icwoocommerce_textdomains'),			array( &$this,'checkbox_element_callback' ),$option, 'dashboard_sections', array('menu'=> $option,	'value'=>'1',	'label_for'=>'show_recent_orders','id'=> 'show_recent_orders','default'=>0));
						
			add_settings_field('show_top_customers',			__( 'Show Top Customers:', 'icwoocommerce_textdomains'),			array( &$this,'checkbox_element_callback' ),$option, 'dashboard_sections', array('menu'=> $option,	'value'=>'1',	'label_for'=>'show_top_customers','id'=> 'show_top_customers','default'=>0));
			
			add_settings_field('show_top_coupons',			__( 'Show Top Coupons:', 'icwoocommerce_textdomains'),			array( &$this,'checkbox_element_callback' ),$option, 'dashboard_sections', array('menu'=> $option,	'value'=>'1',	'label_for'=>'show_top_coupons','id'=> 'show_top_coupons','default'=>0));
			
			add_settings_field('show_top_payments',			__( 'Show Top Payments:', 'icwoocommerce_textdomains'),			array( &$this,'checkbox_element_callback' ),$option, 'dashboard_sections', array('menu'=> $option,	'value'=>'1',	'label_for'=>'show_top_payments','id'=> 'show_top_payments','default'=>0));
			
			
			do_action('ic_commerce_premium_golden_settting_field_after_dashboard_sections',$this, $option);
			
			// Section.
			add_settings_section('dashboard_top_per_page',		__(	'Dashboard Setting:', 'icwoocommerce_textdomains'),			array( &$this, 'section_options_callback' ),$option);
			add_settings_field('recent_order_per_page',			__( 'Recent Order:', 'icwoocommerce_textdomains'),				array( &$this, 'text_element_callback' ), 		$option, 'dashboard_top_per_page', array('menu'=> $option,	'size'=>15, 'class'=>'numberonly', 'maxlength'=>'5',	'label_for'=>'recent_order_per_page',			'id'=> 'recent_order_per_page',				'default'=>$default_rows_per_page));//, 'description' => "Top recent order per page display"
			add_settings_field('top_product_per_page',			__( 'Top Product:', 'icwoocommerce_textdomains'),				array( &$this, 'text_element_callback' ), 		$option, 'dashboard_top_per_page', array('menu'=> $option,	'size'=>15, 'class'=>'numberonly', 'maxlength'=>'5',	'label_for'=>'top_product_per_page',			'id'=> 'top_product_per_page',				'default'=>$default_rows_per_page));			
			add_settings_field('top_category_per_page',			__( 'Top Category:', 'icwoocommerce_textdomains'),				array( &$this, 'text_element_callback' ), 		$option, 'dashboard_top_per_page', array('menu'=> $option,	'size'=>15,	'class'=>'numberonly', 'maxlength'=>'5',	'label_for'=>'top_category_per_page',			'id'=> 'top_category_per_page',				'default'=>$default_rows_per_page));//Added 20150209
			add_settings_field('top_customer_per_page',			__( 'Top Customer:', 'icwoocommerce_textdomains'),				array( &$this, 'text_element_callback' ), 		$option, 'dashboard_top_per_page', array('menu'=> $option,	'size'=>15, 'class'=>'numberonly', 'maxlength'=>'5',	'label_for'=>'top_customer_per_page',			'id'=> 'top_customer_per_page',				'default'=>$default_rows_per_page));
			add_settings_field('top_billing_country_per_page',	__( 'Top Billing Country:', 'icwoocommerce_textdomains'),		array( &$this, 'text_element_callback' ), 		$option, 'dashboard_top_per_page', array('menu'=> $option,	'size'=>15, 'class'=>'numberonly', 'maxlength'=>'5',	'label_for'=>'top_billing_country_per_page',	'id'=> 'top_billing_country_per_page',		'default'=>$default_rows_per_page));
			add_settings_field('top_billing_state_per_page',	__( 'Top State Country:', 'icwoocommerce_textdomains'),			array( &$this, 'text_element_callback' ), 		$option, 'dashboard_top_per_page', array('menu'=> $option,	'size'=>15, 'class'=>'numberonly', 'maxlength'=>'5',	'label_for'=>'top_billing_state_per_page',		'id'=> 'top_billing_state_per_page',		'default'=>$default_rows_per_page));
			add_settings_field('top_payment_gateway_per_page',	__( 'Top Payment Gateway:', 'icwoocommerce_textdomains'),		array( &$this, 'text_element_callback' ), 		$option, 'dashboard_top_per_page', array('menu'=> $option,	'size'=>15, 'class'=>'numberonly', 'maxlength'=>'5',	'label_for'=>'top_payment_gateway_per_page',	'id'=> 'top_payment_gateway_per_page',		'default'=>$default_rows_per_page));
			add_settings_field('top_coupon_per_page',			__( 'Top Coupon:', 'icwoocommerce_textdomains'),				array( &$this, 'text_element_callback' ), 		$option, 'dashboard_top_per_page', array('menu'=> $option,	'size'=>15,	'class'=>'numberonly', 'maxlength'=>'5',	'label_for'=>'top_coupon_per_page',				'id'=> 'top_coupon_per_page',				'default'=>$default_rows_per_page));			
			add_settings_field('show_seleted_order_status',		__( 'Show Selected Order Status only:', 'icwoocommerce_textdomains'),	array( &$this,'checkbox_element_callback' ),$option, 'dashboard_top_per_page', array('menu'=> $option,	'value'=>'1',	'label_for'=>'show_seleted_order_status','id'=> 'show_seleted_order_status','default'=>0,'description' => __("Setting for Dashboard \"<strong>Sales Order Status</strong>\" box.",'icwoocommerce_textdomains')));////Added 20150217
			
			/*if($this->constants['activate_cog']){
				add_settings_section('cogs_settings',			__(	'Cost of Goods:', 'icwoocommerce_textdomains'),				array( &$this, 'section_options_callback' ),$option);
				add_settings_field('cogs_enable_adding',		__( 'Enable Cost of Goods:', 'icwoocommerce_textdomains'),		array( &$this,'checkbox_element_callback' ),$option, 'cogs_settings', array('menu'=> $option,	'size'=>25,	'label_for'=>'cogs_enable_adding','id'=> 'cogs_enable_adding','default'=>0));//Added 20150320
				add_settings_field('cogs_enable_reporting',		__( 'Enable Reporting:', 'icwoocommerce_textdomains'),			array( &$this,'checkbox_element_callback' ),$option, 'cogs_settings', array('menu'=> $option,	'size'=>25,	'label_for'=>'cogs_enable_reporting','id'=> 'cogs_enable_reporting','default'=>0));//Added 20150320
				
				add_settings_section('cogs_meta_settings',		__('Cost of Goods Databse table Meta Key Settings:', 'icwoocommerce_textdomains'),				array( &$this, 'section_options_callback' ),$option);
				//add_settings_field('cogs_metakey_simple',		__( 'Simple Product:', 'icwoocommerce_textdomains'),			array( &$this, 'text_element_callback' ), 	$option, 'cogs_meta_settings', array('menu'=> $option, 'class' => 'cogs_metakey_textbox',	'size'=>25,	'maxlength'=>'100',	'label_for'=>'cogs_metakey_simple',			'id'=> 'cogs_metakey_simple',				'default'=>$this->constants['cog']['default_cogs_metakey_simple'], 'description' => __("this plugin support meta key {$this->constants['cog']['default_cogs_metakey']}. if you are using some other pluign use that meta key for reporting",'icwoocommerce_textdomains')));
				add_settings_field('cogs_metakey',				__( 'Cost of Meta Key:', 'icwoocommerce_textdomains'),			array( &$this, 'text_element_callback' ), 	$option, 'cogs_meta_settings', array('menu'=> $option, 'class' => 'cogs_metakey_textbox',	'size'=>25,	'maxlength'=>'100',	'label_for'=>'cogs_metakey',				'id'=> 'cogs_metakey',				'default'=>$this->constants['cog']['cogs_metakey'], 'description' => __("this plugin support meta key {$this->constants['cog']['default_cogs_metakey']}. if you are using some other pluign use that meta key for reporting",'icwoocommerce_textdomains')));
			}*/
			
			do_action('ic_commerce_premium_golden_settting_field_after_dashboard',$this, $option);
			
			add_settings_section('current_projected_sales',		__('Current Projected Year', 'icwoocommerce_textdomains'),		array( &$this, 'section_options_callback' ),$option);//echo $this->print_array($this->constants);
			add_settings_field('cur_projected_sales_year',		__( 'Current Projected Sales Year:', 'icwoocommerce_textdomains'),				array( &$this, 'select_element_callback' ), 	$option, 'current_projected_sales', array('menu'=> $option,	'label_for'=>'cur_projected_sales_year',		'id'=> 'cur_projected_sales_year','default'=>$current_year,'options'=>$projected_sales_year));			
			
			add_settings_section('projected_sales',				__('Projected Sales', 'icwoocommerce_textdomains'),				array( &$this, 'section_options_callback' ),$option);
			add_settings_field('projected_sales_year',			__( 'Projected Sales Year:', 'icwoocommerce_textdomains'),		array( &$this, 'select_element_callback' ), 	$option, 'projected_sales', array('menu'=> $option,	'label_for'=>'projected_sales_year',		'id'=> 'projected_sales_year','default'=>$current_year,'options'=>$projected_sales_year));
			for($m = 0;$m<=($projected_months - 1);$m++){$l = $projected_month_list[$m];
				add_settings_field("projected_sales_month_{$l}",__("Sales Month {$l} :", 'icwoocommerce_textdomains'),			array( &$this, 'text_element_callback' ), $option, 'projected_sales', array('menu'=> $option,	'size'=>15, 'class'=>'numberonly projected_sales_month_textbox', 'maxlength'=>'10',	'label_for'=>"projected_sales_month_{$l}",			'id'=> "projected_sales_month_{$l}", 'multi_name' => "[{$l}]"	,			'default'=>''));
			}
			
			add_settings_section('per_page_setting',			__('Per Page:', 'icwoocommerce_textdomains'),					array( &$this, 'section_options_callback' ),$option);
			add_settings_field('per_row_details_page',			__( 'Detail Page Orders:', 'icwoocommerce_textdomains'),		array( &$this, 'text_element_callback' ), 		$option, 'per_page_setting', array('menu'=> $option,	'size'=>15,	'class'=>'numberonly', 'maxlength'=>'5',	'label_for'=>'per_row_details_page',			'id'=> 'per_row_details_page',				'default'=>$default_rows_per_page));
			add_settings_field('per_row_stock_page',			__( 'Detail Stock Orders:', 'icwoocommerce_textdomains'),		array( &$this, 'text_element_callback' ), 		$option, 'per_page_setting', array('menu'=> $option,	'size'=>15,	'class'=>'numberonly', 'maxlength'=>'5',	'label_for'=>'per_row_stock_page',				'id'=> 'per_row_stock_page',				'default'=>$default_rows_per_page));
			add_settings_field('per_row_variation_stock_page',	__( 'Detail Variation Stock Orders:', 'icwoocommerce_textdomains'),	array( &$this, 'text_element_callback' ), 	$option, 'per_page_setting', array('menu'=> $option,	'size'=>15,	'class'=>'numberonly', 'maxlength'=>'5',	'label_for'=>'per_row_variation_stock_page',	'id'=> 'per_row_variation_stock_page',		'default'=>$default_rows_per_page));
			add_settings_field('per_row_all_report_page',		__( 'Detail View All Report:', 'icwoocommerce_textdomains'),	array( &$this, 'text_element_callback' ), 		$option, 'per_page_setting', array('menu'=> $option,	'size'=>15,	'class'=>'numberonly', 'maxlength'=>'5',	'label_for'=>'per_row_all_report_page',			'id'=> 'per_row_all_report_page',			'default'=>$default_rows_per_page));
			add_settings_field('per_row_variation_page',		__( 'Detail Variation Report:', 'icwoocommerce_textdomains'),	array( &$this, 'text_element_callback' ), 		$option, 'per_page_setting', array('menu'=> $option,	'size'=>15,	'class'=>'numberonly', 'maxlength'=>'5',	'label_for'=>'per_row_variation_page',			'id'=> 'per_row_variation_page',			'default'=>$default_rows_per_page));
			add_settings_field('per_row_customer_page',			__( 'Customer Page Report:', 'icwoocommerce_textdomains'),		array( &$this, 'text_element_callback' ), 		$option, 'per_page_setting', array('menu'=> $option,	'size'=>15,	'class'=>'numberonly', 'maxlength'=>'5',	'label_for'=>'per_row_customer_page',			'id'=> 'per_row_customer_page',				'default'=>$default_rows_per_page));
			//add_settings_field('per_row_cross_tab_page',		__( 'Detail Cross Tab Report:', 'icwoocommerce_textdomains'),	array( &$this, 'text_element_callback' ), 		$option, 'per_page_setting', array('menu'=> $option,	'size'=>15,	'class'=>'numberonly', 'maxlength'=>'5',	'label_for'=>'per_row_cross_tab_page',			'id'=> 'per_row_cross_tab_page',			'default'=>$default_rows_per_page));
			
			
			do_action('ic_commerce_premium_golden_settting_field_before_status',$this, $option);
			//New Change ID 20140918
			add_settings_section('dashboard_setting',			__('Default Settings:', 'icwoocommerce_textdomains'),			array( &$this, 'section_options_callback' ),$option);
			add_settings_field($order_status_field,				__( 'Shop Order Status:', 'icwoocommerce_textdomains'),			array( &$this, 'select_element_callback' ), 	$option, 'dashboard_setting', array('menu'=> $option,	'size'=>8,	'class'=>'numberonly', 'maxlength'=>'5',	'label_for'=>$order_status_field,				'id'=> $order_status_field,					'default'=>$detault_stauts_id,  'multiple'=>'multiple',	'options'=> $new_shop_order_status, 'description' => __("Ctrl + click to multiselect. <br /> Selected status will be used for calculating salse amount.",'icwoocommerce_textdomains')));
			add_settings_field('hide_order_status',				__( 'Hide Trash Order:', 'icwoocommerce_textdomains'),			array( &$this,'checkbox_element_callback' ),$option, 'dashboard_setting', array('menu'=> $option,	'value'=>'trash',	'label_for'=>'hide_order_status','id'=> 'hide_order_status','default'=>0));
			
			do_action('ic_commerce_premium_golden_settting_field_after_default_setting',$this, $option);
			
			add_settings_section('cross_tab',					__(	'Cross Tab Reports:', 'icwoocommerce_textdomains'),			array( &$this, 'section_options_callback' ),$option);
			add_settings_field('cross_tab_start_date',			__( 'Cross Tab Start Month:', 'icwoocommerce_textdomains'),		array( &$this, 'text_element_callback' ), 		$option, 'cross_tab', 		array('menu'=> $option,	'size'=>15,	'class'=>'normaltextbox', 'maxlength'=>'10',	'label_for'=>'cross_tab_start_date',			'id'=> 'cross_tab_start_date',					'default'=>'',	'readonly'=> true));
			add_settings_field('cross_tab_end_date',			__( 'Cross Tab End Month:', 'icwoocommerce_textdomains'),		array( &$this, 'text_element_callback' ), 		$option, 'cross_tab', 		array('menu'=> $option,	'size'=>15,	'class'=>'normaltextbox', 'maxlength'=>'10',	'label_for'=>'cross_tab_end_date',				'id'=> 'cross_tab_end_date',					'default'=>'',		'readonly'=> true));
			
			
			add_settings_section('upload_setting',		__( 'Export Report:','icwoocommerce_textdomains'),					array( &$this, 'section_options_callback' ),$option);
			
			add_settings_field('report_title',			__( '(Pdf/Print) Report Title:', 'icwoocommerce_textdomains'),		array( &$this, 'text_element_callback' ), 		$option, 'upload_setting', array('menu'=> $option,	'size'=>25,	 'label_for'=>'report_title',		'id'=> 'report_title',				'default'=>''));
			
			add_settings_field('company_name',			__( 'Company Name:', 'icwoocommerce_textdomains'),					array( &$this, 'text_element_callback' ), 		$option, 'upload_setting', array('menu'=> $option,	'size'=>25,	'label_for'=>'company_name',		'id'=> 'company_name',				'default'=>''));
			
			add_settings_field('billing_information',	__( 'Billing Information:', 'icwoocommerce_textdomains'),			array( &$this,'checkbox_element_callback' ), 		$option, 'upload_setting', array('menu'=> $option,	'size'=>25,	'label_for'=>'billing_information',		'id'=> 'billing_information','default'=>0));//,'description' =>'Add addtion Column for Billing in csv Export'
			
			add_settings_field('shipping_information',	__( 'Shipping Information:', 'icwoocommerce_textdomains'),			array( &$this,'checkbox_element_callback' ),$option, 'upload_setting', array('menu'=> $option,	'size'=>25,	'label_for'=>'shipping_information','id'=> 'shipping_information','default'=>0));// ,'description' =>'Add addtion Column for Shipping in csv Export'
			
			add_settings_field('logo_image', 			__( 'Logo Image:', 'icwoocommerce_textdomains'),			array( &$this, 'choose_image_callback' ), 		$option, 'upload_setting', array('menu'=> $option,	'size'=>40,	'class'=>'normaltextbox', 'maxlength'=>'500', 	'label_for'=>'logo_image', 						'id'=> 'logo_image',						'default'=>'', 		'choose_id'=>'upload_logo_image_button'));//, 'description' => "Upload logo of your company, which will display on PDF if uploaded, make sure logo is not too big. Upload logo 200px width and 100px height."
			
			
			add_settings_field('font_type_1',	__( 'Enable Font:', 'icwoocommerce_textdomains'),			array( &$this,'checkbox_element_callback' ),$option, 'upload_setting', array('menu'=> $option,'size'=>25,'label_for'=>'font_type_1','id'=>'font_type_1' ,'value' => 'yes'));			
			add_settings_field('font_url',	   __( 'Font URL:', 'icwoocommerce_textdomains'),					array( &$this, 'text_element_callback' ), 		$option, 'upload_setting', array('menu'=> $option,	'size'=>60,	'label_for'=>'font_url','id'=> 'font_url','default'=>'http://eclecticgeek.com/dompdf/fonts/cjk/Cybercjk.ttf'));
			
			add_settings_field('font_type_2',	__( 'Other Currency:', 'icwoocommerce_textdomains'),			array( &$this,'checkbox_element_callback' ),$option, 'upload_setting', array('menu'=> $option,'size'=>25,'label_for'=>'font_type_2','id'=>'font_type_2','value' => 'yes'));
			add_settings_field('font_dejaVu',	__( 'Enable DejaVu Sans:', 'icwoocommerce_textdomains'),			array( &$this,'checkbox_element_callback' ),$option, 'upload_setting', array('menu'=> $option,'size'=>25,'label_for'=>'font_dejaVu','id'=>'font_dejaVu','value' => 'yes'));
			
			
			add_settings_section('email_reports',			__(	'Email Reports:','icwoocommerce_textdomains'),					array( &$this, 'section_options_callback' )		,$option);
			
			add_settings_field('email_daily_report',		__( 'Email Today Report:', 'icwoocommerce_textdomains'),			array( &$this,'checkbox_element_callback' ), 	$option, 'email_reports', array('menu'=> $option,	'size'=>25,	'label_for'=>'email_daily_report',			'id'=> 'email_daily_report','default'=>0));
			
			add_settings_field('email_yesterday_report',	__( 'Email Yesterday Report:', 'icwoocommerce_textdomains'),		array( &$this,'checkbox_element_callback' ), 	$option, 'email_reports', array('menu'=> $option,	'size'=>25,	'label_for'=>'email_yesterday_report',		'id'=> 'email_yesterday_report','default'=>0));//Added 20150207
			
			add_settings_field('email_weekly_report',		__( 'Email Current Week Report:', 'icwoocommerce_textdomains'),		array( &$this,'checkbox_element_callback' ),	$option, 'email_reports', array('menu'=> $option,	'size'=>25,	'label_for'=>'email_weekly_report',			'id'=> 'email_weekly_report','default'=>0));
			
			add_settings_field('email_last_week_report',	__( 'Email Last Week Report:', 'icwoocommerce_textdomains'),		array( &$this,'checkbox_element_callback' ),	$option, 'email_reports', array('menu'=> $option,	'size'=>25,	'label_for'=>'email_last_week_report',		'id'=> 'email_last_week_report','default'=>0));//Added 20150209
			
			add_settings_field('email_monthly_report',		__( 'Email Current Month Report:', 'icwoocommerce_textdomains'),	array( &$this,'checkbox_element_callback' ),	$option, 'email_reports', array('menu'=> $option,	'size'=>25,	'label_for'=>'email_monthly_report',		'id'=> 'email_monthly_report','default'=>0));
			
			add_settings_field('email_last_month_report',	__( 'Email Last Month Report:', 'icwoocommerce_textdomains'),		array( &$this,'checkbox_element_callback' ),	$option, 'email_reports', array('menu'=> $option,	'size'=>25,	'label_for'=>'email_last_month_report',		'id'=> 'email_last_month_report','default'=>0));//Added 20150209
			
			add_settings_field('email_this_year_report',	__( 'Email Current Year Report:', 'icwoocommerce_textdomains'),		array( &$this,'checkbox_element_callback' ),	$option, 'email_reports', array('menu'=> $option,	'size'=>25,	'label_for'=>'email_this_year_report',		'id'=> 'email_this_year_report','default'=>0));//Added 20150209
			
			add_settings_field('email_last_year_report',	__( 'Email Last Year Report:', 'icwoocommerce_textdomains'),		array( &$this,'checkbox_element_callback' ),	$option, 'email_reports', array('menu'=> $option,	'size'=>25,	'label_for'=>'email_last_year_report',		'id'=> 'email_last_year_report','default'=>0));//Added 20150209
			
			add_settings_field('email_till_today_report',	__( 'Email Till Today Report:', 'icwoocommerce_textdomains'),		array( &$this,'checkbox_element_callback' ),	$option, 'email_reports', array('menu'=> $option,	'size'=>25,	'label_for'=>'email_till_today_report',		'id'=> 'email_till_today_report','default'=>0));
			
			
			
			add_settings_field('email_send_to',		__( 'Email Send To:', 'icwoocommerce_textdomains'),	array( &$this, 'text_element_callback' ), 		$option, 'email_reports', array('menu'=> $option,	'size'=>50,	'class'=>'emailcharacters', 'maxlength'=>'500',	'label_for'=>'email_send_to',			'id'=> 'email_send_to',			'default'=>''));
			add_settings_field('email_from_name',	__( 'From Name:', 'icwoocommerce_textdomains'),	array( &$this, 'text_element_callback' ), 		$option, 'email_reports', array('menu'=> $option,	'size'=>50,	'class'=>'emailcharacters', 'maxlength'=>'100',	'label_for'=>'email_from_name',			'id'=> 'email_from_name',			'default'=>''));
			add_settings_field('email_from_email',	__( 'From Email:', 'icwoocommerce_textdomains'),	array( &$this, 'text_element_callback' ), 		$option, 'email_reports', array('menu'=> $option,	'size'=>50,	'class'=>'emailcharacters', 'maxlength'=>'100',	'label_for'=>'email_from_email',			'id'=> 'email_from_email',		'default'=>''));
			add_settings_field('email_subject',		__( 'Subject:', 'icwoocommerce_textdomains'),	array( &$this, 'text_element_callback' ), 		$option, 'email_reports', array('menu'=> $option,	'size'=>50,	'class'=>'emailcharacters', 'maxlength'=>'150',	'label_for'=>'email_subject',			'id'=> 'email_subject',				'default'=>''));
			
			add_settings_field('email_schedule',	__( 'Email Schedule:', 'icwoocommerce_textdomains'),			array( &$this, 'select_element_callback' ), 	$option, 'email_reports', array('menu'=> $option,	'label_for'=>'email_schedule',		'id'=> 'email_schedule','default'=>'daily','options'=>$cron_schedule,'label_none'=>__('Unschedule Event','icwoocommerce_textdomains')));
			
			add_settings_field('act_email_reporting',	__( 'Activate Email Reporting:', 'icwoocommerce_textdomains' ),	array( &$this,'checkbox_element_callback' ), 	$option, 'email_reports', array('menu'=> $option,	'size'=>25,	'label_for'=>'act_email_reporting',		'id'=> 'act_email_reporting',	'default'=>0));			
			
			$schedule_hook_name			= $this->constants['plugin_key'].'_schedule_mailing_sales_status_event';
			$wp_next_scheduled			= wp_next_scheduled($schedule_hook_name);
			if ($wp_next_scheduled){
				if(defined('DISABLE_WP_CRON') && DISABLE_WP_CRON){
				
				}else{
					$html = __("Active",'icwoocommerce_textdomains');					
					add_settings_field('email_cron_job_status',	__( 'Schedule Status:', 'icwoocommerce_textdomains' ),	array( &$this, 'label_element_callback' ), 				$option, 'email_reports', array('menu'=> $option,	'id'=> 'email_cron_job_status',		'default'=>$html));
				}
			}else{
				$html = __("Schedule mailing stoped or not activated or unschedule event or try again",'icwoocommerce_textdomains');
				add_settings_field('email_cron_job_status',	__( 'Schedule Status:', 'icwoocommerce_textdomains' ),	array( &$this, 'label_element_callback' ), 				$option, 'email_reports', array('menu'=> $option,	'id'=> 'email_cron_job_status',		'default'=>$html));		
			}	
			
			
		
			if(defined('DISABLE_WP_CRON') && DISABLE_WP_CRON){
				$disable_wp_cron 			= $this->get_setting('disable_wp_cron',$this->constants['plugin_options'], 0);//Added 20150721
				if($disable_wp_cron == 1){
					$html = __('Automatic Schedule mailing can not work, because "DISABLE_WP_CRON" is defined as "TRUE" on this plug-ins setting page in "Cron URL Settings" section.','icwoocommerce_textdomains');
				}else{
					$html = __('Schedule mailing can not work, because "DISABLE_WP_CRON" is defined as "TRUE" in wp-config.php file or somewhere else. To define this to "FALSE" open wp-config.php file in WordPress root folder and set it to "FALSE" or comment.','icwoocommerce_textdomains');
				}
				
				add_settings_field('disable_wp_cron_status',	__( 'WP CRON Status:', 'icwoocommerce_textdomains' ),	array( &$this, 'label_element_callback' ), 				$option, 'email_reports', array('menu'=> $option,	'id'=> 'disable_wp_cron_status',		'default'=>$html));		
			}
			
			add_settings_field('email_report_actions',	__( 'Action:', 'icwoocommerce_textdomains' ),			array( &$this, 'create_button_element_callback' ), 		$option, 'email_reports', array('menu'=> $option,	'id'=> 'email_report_actions',		'buttons'=>array('order_status_mail' =>array('value'=>__('Test Mail','icwoocommerce_textdomains'),'type'=>'button','id'=>'order_status_mail'))));
			
			
			
			do_action('ic_commerce_premium_golden_settting_field_after_email_report',$this, $option);
			
			////////////////////////////
			/////////////////////
			
			
			add_settings_section('pdf_invoice_setting',			__( 'PDF Invoice:','icwoocommerce_textdomains'),						array( &$this, 'section_options_callback' ),$option);
			
			add_settings_field('pdf_invoice_company_name',		__( 'Company Name:', 'icwoocommerce_textdomains'),						array( &$this, 'text_element_callback' ), 		$option, 'pdf_invoice_setting', array('menu'=> $option,	'size'=>25, 'maxlength'=>'100',	 'label_for'=>'pdf_invoice_company_name',		'id'=> 'pdf_invoice_company_name',				'default'=>''));
			
			add_settings_field('pdf_invoice_company_add1',		__( 'Company Address1:', 'icwoocommerce_textdomains'),					array( &$this, 'text_element_callback' ), 		$option, 'pdf_invoice_setting', array('menu'=> $option,	'size'=>25, 'maxlength'=>'250',	 'label_for'=>'pdf_invoice_company_add1',		'id'=> 'pdf_invoice_company_add1',				'default'=>""));
			
			add_settings_field('pdf_invoice_company_add2',		__( 'Company Address2:', 'icwoocommerce_textdomains'),					array( &$this, 'text_element_callback' ), 		$option, 'pdf_invoice_setting', array('menu'=> $option,	'size'=>25, 'maxlength'=>'250',	 'label_for'=>'pdf_invoice_company_add2',		'id'=> 'pdf_invoice_company_add2',				'default'=>""));
			
			add_settings_field('pdf_invoice_logo', 				__( 'Logo Image:', 'icwoocommerce_textdomains'),						array( &$this, 'choose_image_callback' ), 		$option, 'pdf_invoice_setting', array('menu'=> $option,	'size'=>40,	'class'=>'normaltextbox', 'maxlength'=>'500', 	'label_for'=>'pdf_invoice_logo', 						'id'=> 'pdf_invoice_logo',						'default'=>'', 		'choose_id'=>'upload_pdf_invoice_logo_button'));
			add_settings_field('pdf_invoice_name_logo_align',	__( 'Comapany Name and Logo Align:', 'icwoocommerce_textdomains'),		array( &$this, 'select_element_callback' ), 	$option, 'pdf_invoice_setting', array('menu'=> $option,	'label_for'=>'pdf_invoice_name_logo_align',		'id'=> 'pdf_invoice_name_logo_align','default'=>'company_name_left_left','options'=>array(
				//'0'=>"Select any one",
				'company_name_left_left'=>__("Comapany Name Left and Logo Left",'icwoocommerce_textdomains')
			)));
			
			
			
			add_settings_field('pdf_invoice_show_signature',	__( 'Show Signature:', 'icwoocommerce_textdomains'),	array( &$this,'checkbox_element_callback' ), 		$option, 'pdf_invoice_setting', array('menu'=> $option,	'label_for'=>'pdf_invoice_show_signature',		'id'=> 'pdf_invoice_show_signature','default'=>0));
			add_settings_field('pdf_invoice_signature_align',	__( 'Signature Align:', 'icwoocommerce_textdomains'),	array( &$this, 'select_element_callback' ), 		$option, 'pdf_invoice_setting', array('menu'=> $option,	'label_for'=>'pdf_invoice_signature_align',		'id'=> 'pdf_invoice_signature_align','default'=>'left','options'=>array('left'=>__("Left",'icwoocommerce_textdomains'),'center'=>__("Center",'icwoocommerce_textdomains'),'right'=>__("Right",'icwoocommerce_textdomains'))));
			add_settings_field('pdf_invoice_signature', 		__( 'Signature Image:', 'icwoocommerce_textdomains'),	array( &$this, 'choose_image_callback' ), 			$option, 'pdf_invoice_setting', array('menu'=> $option,	'size'=>40,	'class'=>'normaltextbox', 'maxlength'=>'500', 	'label_for'=>'pdf_invoice_signature', 						'id'=> 'pdf_invoice_signature',						'default'=>'', 		'choose_id'=>'upload_pdf_invoice_signature_button'));
			
			
			
			add_settings_field('pdf_invoice_show_footer_note',	__( 'Show Footer Note:', 'icwoocommerce_textdomains'),				array( &$this,'checkbox_element_callback' ), 		$option, 'pdf_invoice_setting', array('menu'=> $option,	'label_for'=>'pdf_invoice_show_footer_note',		'id'=> 'pdf_invoice_show_footer_note','default'=>0));
			add_settings_field('pdf_invoice_footer_note',		__( 'Footer Note(after signature):', 'icwoocommerce_textdomains'),	array( &$this, 'textarea_element_callback' ), 		$option, 'pdf_invoice_setting', array('menu'=> $option,	'size'=>25, 'maxlength'=>'100',	 'label_for'=>'pdf_invoice_footer_note',		'id'=> 'pdf_invoice_footer_note',				'default'=>''));
			
			
			
			add_settings_field('pdf_invoice_show_invoice_creatin_date',__( 'Show Invoice Creation Date:', 'icwoocommerce_textdomains'),	array( &$this,'checkbox_element_callback' ), 		$option, 'pdf_invoice_setting', array('menu'=> $option,	'label_for'=>'pdf_invoice_show_invoice_creatin_date',		'id'=> 'pdf_invoice_show_invoice_creatin_date','default'=>0));
			//add_settings_field('pdf_invoice_footer_note',			__( 'Footer Note:', 'icwoocommerce_textdomains'),	array( &$this, 'text_element_callback' ), 		$option, 'pdf_invoice_setting', array('menu'=> $option,	'size'=>50, 'maxlength'=>'500',	'label_for'=>'pdf_invoice_footer_note',		'id'=> 'pdf_invoice_footer_note',				'default'=>''));
			
			
			do_action('ic_commerce_premium_golden_settting_field_bottom',$this, $option);
			
			// Register settings.
			register_setting( $option, $option, array( &$this, 'options_validate' ) );
	   }
	
			
		
	
		/**
		 * Text field callback.
		 *
		 * @param  array $args Field arguments.
		 *
		 * @return string      Text field.
		 */
		public function text_element_callback( $args ) {
			$menu 		= $args['menu'];
			$id 		= $args['id'];
			$size 		= isset( $args['size'] ) 		? $args['size'] : '25';
			$class 		= isset( $args['class'] ) 		? ' class="'.$args['class'] .'"': '';
			$maxlength	= isset( $args['maxlength'] ) 	? ' maxlength="'.$args['maxlength'] .'"': '';			
			$type		= isset( $args['type'] ) 		? $args['type']: 'text';
			$autocpt	= isset( $args['autocomplete'] ) 		? $args['autocomplete']: 'off';
			
			$options	= get_option( $menu );
					
			if ( isset( $options[$id] ) ) {
				$current = $options[$id];
			} else {
				$current = isset( $args['default'] ) ? $args['default'] : '';
			}
			
			$default_value = isset( $args['default'] ) ? $args['default'] : '';
			
			/*
			if(!current_user_can('manage_options')){
				$ga_fields = array("ga_account","ga_password","ga_profile_id");
				if(in_array($id,$ga_fields)){
					$current = "";
				}
			}
			*/
			
			 
			
			$disabled = (isset($args['disabled'])) ? ' disabled' : '';
			$readonly = (isset($args['readonly'])) ? ' readonly="readonly"' : '';
			
			$html = sprintf( '<input id="%1$s" name="%2$s[%1$s]" value="%3$s" size="%4$s"%5$s%6$s%7$s%8$s type="%9$s" data-name="%1$s"  data-default_value="%10$s"  autocomplete="%11$s" />', $id, $menu, $current, $size, $disabled, $class, $maxlength, $readonly, $type, $default_value, $autocpt);
		
			// Displays option description.
			if ( isset( $args['description'] ) ) {
				$html .= sprintf( '<p class="description">%s</p>', $args['description'] );
			}	
		
			echo $html."\n";
		}
		
		public function text_array_element_callback( $args ) {
			$menu 		= $args['menu'];
			$id 		= $args['id'];
			$size 		= isset( $args['size'] ) 		? $args['size'] : '25';
			$class 		= isset( $args['class'] ) 		? ' class="'.$args['class'] .'"': '';
			$maxlength	= isset( $args['maxlength'] ) 	? ' maxlength="'.$args['maxlength'] .'"': '';			
			$type		= isset( $args['type'] ) 		? $args['type']: 'text';
			$multi_name	= isset( $args['multi_name'] ) 	? $args['multi_name']: '';
			
			$multi_name	= '';
			
			$options	= get_option( $menu );
			
			//$this->print_array($options);
					
			if ( isset( $options[$id] ) ) {
				$current = $options[$id];				
			} else {
				$current = isset( $args['default'] ) ? $args['default'] : '';
			}
			
			/*
			if(!current_user_can('manage_options')){
				$ga_fields = array("ga_account","ga_password","ga_profile_id");
				if(in_array($id,$ga_fields)){
					$current = "";
				}
			}
			*/
			
			$disabled = (isset($args['disabled'])) ? ' disabled' : '';
			$readonly = (isset($args['readonly'])) ? ' readonly="readonly"' : '';
			
			$html = sprintf( '<input id="%1$s" name="%2$s[%1$s]" value="%3$s" size="%4$s"%5$s%6$s%7$s%8$s type="%9$s" />', $id, $menu, $current, $size, $disabled, $class, $maxlength, $readonly, $type, $multi_name);
		
			// Displays option description.
			if ( isset( $args['description'] ) ) {
				$html .= sprintf( '<p class="description">%s</p>', $args['description'] );
			}	
		
			echo $html."\n";
		}
		
		
		
		/**
		 * Displays a selectbox for a settings field
		 *
		 * @param array   $args settings field args
		 */
		public function _select_element_callback( $args ) {//New Change ID 20140918
			$menu = $args['menu'];
			$id = $args['id'];
			
			$options = get_option( $menu );
			
			if ( isset( $options[$id] ) ) {
				$current = $options[$id];				
			} else {
				$current = isset( $args['default'] ) ? $args['default'] : '';
			}
	
			$disabled = (isset( $args['disabled'] )) ? ' disabled' : ''; 
			
			$html = sprintf( '<select class="select_box select_%2$s" name="%1$s[%2$s]" id="%1$s[%2$s]"%3$s>', $menu, $id, $disabled );  
			$html .= sprintf( '<option value="%s"%s>%s</option>', '0', selected( $current, '0', false ), '' );
			
			foreach( $args['options'] as $key => $label ) {  
				$html .= sprintf( '<option value="%s"%s>%s</option>', $key, selected( $current, $key, false ), $label ); 
			} 
			$html .= sprintf( '</select>' );  
	
			if ( isset( $args['description'] ) ) {
				$html .= sprintf( '<p class="description">%s</p>', $args['description'] );
			}
			
			echo $html;
		}
		
		//New Change ID 20140918
		public function select_element_callback( $args ) {
			$menu = $args['menu'];
			$id = $args['id'];
			$options = get_option( $menu );
			if ( isset( $options[$id] ) ) {
				$current = $options[$id];
			} else {
				$current = isset( $args['default'] ) ? $args['default'] : '';
			}
			
			$disabled = (isset( $args['disabled'] )) ? ' disabled' : '';
			$multiple = (isset( $args['multiple'] )) ? ' multiple="multiple"' : '';
			$size = (isset( $args['size'] )) ? " size=\"{$args['size']}\"" : '';
			
			$width = (isset( $args['width'] )) ? $args['width'] : '';
			
			$first_option_label = (isset( $args['first_option_label'] )) ? trim($args['first_option_label']): 'Select One';
			$first_option_value = (isset( $args['first_option_value'] )) ? trim($args['first_option_value']): 0;
			
			$default 			= isset($args['default']) ? $args['default'] : 0;
			if(is_array($default)){
				if(count($default)>0){
					$default 			= implode(",",$default);	
				}else{
					$default 			= 0;	
				}				
			}else{
				$default 			= trim($default);
			}
						
			$default_attr		= " data-default_value=\"{$default}\"";
			
			
			$_multiple = "";
			$style = "";
			
			if($width){
				$style = " style=\"";
				$style .= "width:{$width};";
				$style .= '"';
			}
			
			if(strlen($multiple)>0)	$_multiple = "[]";
			
			$html = sprintf( '<select  class="select_box select_%2$s" name="%1$s[%2$s]%7$s" id="%2$s"%3$s%4$s%5$s%6$s%8$s>', $menu, $id, $disabled, $multiple, $size,$style,$_multiple, $default_attr );
			if(strlen($multiple)>0){  
				if(!is_array($current)){
					$current = array();
				}
				foreach ( $args['options'] as $key => $label ) {
					if(in_array($key,$current)){
						$html .= sprintf( '<option value="%s"%s>%s</option>', $key, '  selected="selected"', $label ); 
					}else{ 
						$html .= sprintf( '<option value="%s"%s>%s</option>', $key, '', $label ); 
					} 
				}
			}else{
				if(!empty($first_option_label)) $html .= sprintf( '<option value="%s"%s>%s</option>', '0', selected( $current, $first_option_value, false ), $first_option_label); 
				
				foreach( $args['options'] as $key => $label ) {
					$html .= sprintf( '<option value="%s"%s>%s</option>', $key, selected( $current, $key, false ), $label ); 
				} 
			}
			
			
			$html .= sprintf( '</select>' );
			
			if ( isset( $args['description'] ) ) {
				$html .= sprintf( '<p class="description">%s</p>', $args['description'] );
			}
			echo $html;
		}
	
		/**
		 * Displays a multiple selectbox for a settings field
		 *
		 * @param array   $args settings field args
		 */
		public function multiple_select_element_callback( $args ) {
			$html = '';
			foreach ($args as $id => $boxes) {
				$menu = $boxes['menu'];
				
				$options = get_option( $menu );
				
				if ( isset( $options[$id] ) ) {
					$current = $options[$id];
				} else {
					$current = isset( $boxes['default'] ) ? $boxes['default'] : '';
				}
				
				$disabled = (isset( $boxes['disabled'] )) ? ' disabled' : '';
				
				$box = sprintf( '<select name="%1$s[%2$s]" id="%1$s[%2$s]"%3$s>', $menu, $id, $disabled);
				$box .= sprintf( '<option value="%s"%s>%s</option>', '0', selected( $current, '0', false ), '' );
				
				foreach ( $boxes['options'] as $key => $label ) {
					$box .= sprintf( '<option value="%s"%s>%s</option>', $key, selected( $current, $key, false ), $label );
				}
				$box .= '</select>';
		
				if ( isset( $boxes['description'] ) ) {
					$box .= sprintf( '<p class="description">%s</p>', $boxes['description'] );
				}
				
				$html .= $box.'<br />';
			}
			
			
			echo $html;
		}
	
		/**
		 * Checkbox field callback.
		 *
		 * @param  array $args Field arguments.
		 *
		 * @return string      Checkbox field.
		 */
		public function checkbox_element_callback( $args ) {
			$menu 	= $args['menu'];
			$id 	= $args['id'];
			
			$value 	= isset($args['value']) ? $args['value'] : 1;
		
			$options = get_option( $menu );
		
			if ( isset( $options[$id] ) ) {
				$current = $options[$id];
			} else {
				$current = isset( $args['default'] ) ? $args['default'] : '';
			}
		
			$disabled = (isset( $args['disabled'] )) ? ' disabled' : '';
			$html = sprintf( '<input type="checkbox" id="%1$s" name="%2$s[%1$s]" value="%5$s"%3$s %4$s/>', $id, $menu, checked( $value, $current, false ), $disabled,$value);
		
			// Displays option description.
			if ( isset( $args['description'] ) ) {
				$html .= sprintf( '<p class="description">%s</p>', $args['description'] );
			}
				
			echo $html;
		}
	
		/**
		 * Displays a multicheckbox a settings field
		 *
		 * @param array   $args settings field args
		 */
		public function radio_element_callback( $args ) {
			$menu = $args['menu'];
			$id = $args['id'];
		
			$options = get_option( $menu );
		
			if ( isset( $options[$id] ) ) {
				$current = $options[$id];
			} else {
				$current = isset( $args['default'] ) ? $args['default'] : '';
			}
	
			$html = '';
			foreach ( $args['options'] as $key => $label ) {
				$html .= sprintf( '<input type="radio" class="radio" id="%1$s[%2$s][%3$s]" name="%1$s[%2$s]" value="%3$s"%4$s />', $menu, $id, $key, checked( $current, $key, false ) );
				$html .= sprintf( '<label for="%1$s[%2$s][%3$s]"> %4$s</label><br>', $menu, $id, $key, $label);
			}
			
			// Displays option description.
			if ( isset( $args['description'] ) ) {
				$html .= sprintf( '<p class="description">%s</p>', $args['description'] );
			}
	
			echo $html;
		}
	
		/**
		 * Section null callback.
		 *
		 * @return void.
		 */
		public function section_options_callback(){}
	
		/**
		 * Validate/sanitize options input
		 */
		public function options_validate( $input ) {
			// Create our array for storing the validated options.
			$output = array();
	
			// Loop through each of the incoming options.
			foreach ( $input as $key => $value ) {
	
				// Check to see if the current option has a value. If so, process it.
				if ( isset( $input[$key] ) ) {
	
					// Strip all HTML and PHP tags and properly handle quoted strings.
					$output[$key] = strip_tags( stripslashes( $input[$key] ) );
				}
			}
	
			// Return the array processing any additional functions filtered by this action.
			return apply_filters( $this->constants['plugin_key'].'_validate_input', $output, $input );
		}
		
		public function _choose_image_callback( $args ) {
			$menu 		= $args['menu'];
			$id 		= $args['id'];
			$size 		= isset( $args['size'] ) 		? $args['size'] : '25';
			$class 		= isset( $args['class'] ) 		? ' class="'.$args['class'] .'"': '';
			$maxlength	= isset( $args['maxlength'] ) 	? ' maxlength="'.$args['maxlength'] .'"': '';
			
			
			
			$choose_id 		= isset( $args['choose_id'] ) 			? $args['choose_id'] 	: $id;
			$choose_class	= isset( $args['choose_class'] ) 		? ' '.$args['choose_class']: '';
			$choose_data	= isset( $args['choose_data'] ) 		? $args['choose_data'] 	: 'Choose a Image';
			$choose_update 	= isset( $args['choose_update'] ) 		? $args['choose_update'] 	: 'Set as Refresh image';
			$choose_label	= isset( $args['choose_label'] ) 		? $args['choose_label'] 	: 'Choose Image';
			
			$options	= get_option( $menu );
					
			if ( isset( $options[$id] ) ) {
				$current = $options[$id];
			} else {
				$current = isset( $args['default'] ) ? $args['default'] : '';
			}
	
			$disabled = (isset( $args['disabled'] )) ? ' disabled' : '';
			$html = "";
			$html .= "\n".sprintf( ' <input type="text" id="%1$s" name="%2$s[%1$s]" value="%3$s" readonly="readonly"   size="%4$s"%5$s%6$s%7$s />', $id, $menu, $current, $size, $disabled, $class, $maxlength);			
			$html .= "\n".sprintf( ' <a id="%1$s" class="onformprocess button%2$s" data-choose="%3$s" data-update="%4$s">%5$s</a>',$choose_id,$choose_class,$choose_data, $choose_update, $choose_label);
			$html .= "\n".sprintf( ' <a id="clear_%1$s" class="onformprocess clear_textbox button%2$s" data-choose="%3$s" data-update="%4$s">%5$s</a>',$choose_id,$choose_class,$choose_data, $choose_update, 'Clear');

			// Displays option description.
			if ( isset( $args['description'] ) ) {
				$html .= sprintf( '<p class="description">%s</p>', $args['description'] );
			}
			
			if ($current) {
				$company_name	=	$blog_title = get_bloginfo('name'); ;
				$html .= sprintf( '<div class="logo_image logo_image_%1$s"><img src="%2$s" alt="%3$s" /></div>', $choose_class, $current, $company_name);
			}	
		
			echo $html;
		}
		
		public function choose_image_callback( $args ) {
			$menu 		= $args['menu'];
			$id 		= $args['id'];
			$size 		= isset( $args['size'] ) 		? $args['size'] : '25';
			$class 		= isset( $args['class'] ) 		? ' class="upload_field '.$args['class'] .'"': 'upload_field';
			$maxlength	= isset( $args['maxlength'] ) 	? ' maxlength="'.$args['maxlength'] .'"': '';
			
			
			
			$choose_id 		= isset( $args['choose_id'] ) 			? $args['choose_id'] 	: $id;
			$choose_class	= isset( $args['choose_class'] ) 		? ' '.$args['choose_class']: '';
			$choose_data	= isset( $args['choose_data'] ) 		? $args['choose_data'] 	: 'Choose a Image';
			$choose_update 	= isset( $args['choose_update'] ) 		? $args['choose_update'] 	: 'Set as Refresh image';
			$choose_label	= isset( $args['choose_label'] ) 		? $args['choose_label'] 	: 'Choose Image';
			
			$options	= get_option( $menu );
					
			if ( isset( $options[$id] ) ) {
				$current = $options[$id];
			} else {
				$current = isset( $args['default'] ) ? $args['default'] : '';
			}
	
			$disabled = (isset( $args['disabled'] )) ? ' disabled' : '';
			$html = "";
			$html .= "\n".sprintf( ' <input type="text" id="%1$s" name="%2$s[%1$s]" value="%3$s" readonly="readonly"   size="%4$s"%5$s%6$s%7$s />', $id, $menu, $current, $size, $disabled, $class, $maxlength);			
			$html .= "\n".sprintf( ' <a id="%1$s" class="ic_upload_button onformprocess button%2$s" data-choose="%3$s" data-update="%4$s">%5$s</a>',$choose_id,$choose_class,$choose_data, $choose_update, $choose_label);
			$html .= "\n".sprintf( ' <a id="clear_%1$s" class="onformprocess clear_textbox button%2$s" data-choose="%3$s" data-update="%4$s">%5$s</a>',$choose_id,$choose_class,$choose_data, $choose_update, 'Clear');

			// Displays option description.
			if ( isset( $args['description'] ) ) {
				$html .= sprintf( '<p class="description">%s</p>', $args['description'] );
			}
			
			if ($current) {
				$company_name	=	$blog_title = get_bloginfo('name'); ;
				$html .= sprintf( '<div class="logo_image logo_image_%1$s"><img src="%2$s" alt="%3$s" /></div>', $choose_class, $current, $company_name);
			}	
		
			echo $html;
		}
		
		public function color_picker_callback( $args ) {
			$menu 		= $args['menu'];
			$id 		= $args['id'];
			$size 		= isset( $args['size'] ) 		? $args['size'] : '25';
			$class 		= isset( $args['class'] ) 		? ' class="'.$args['class'] .'"': '';
			$maxlength	= isset( $args['maxlength'] ) 	? ' maxlength="'.$args['maxlength'] .'"': '';
			$options	= get_option( $menu );
					
			if ( isset( $options[$id] ) ) {
				$current = $options[$id];
			} else {
				$current = isset( $args['default'] ) ? $args['default'] : '';
			}
	
			$disabled = (isset( $args['disabled'] )) ? ' disabled' : '';
			$html = sprintf( '<input type="text" id="%1$s" name="%2$s[%1$s]" value="%3$s"  size="%4$s"%5$s%6$s%7$s />', $id, $menu, $current, $size, $disabled, $class, $maxlength);
		
			// Displays option description.
			if ( isset( $args['description'] ) ) {
				$html .= ' <a href="#"   title="this is my title" class="help_tip">target element</a>';			}	
		
			echo $html;
			
			//wp_enqueue_script('wpb-tooltip-jquery', plugins_url('/wpb-tooltip.js', __FILE__ ), array('jquery-ui-tooltip'), '', true);
			
			
		}
		
		public function textarea_element_callback( $args ) {
			$menu 		= $args['menu'];
			$id 		= $args['id'];
			$cols 		= isset( $args['cols'] ) 		? $args['cols'] : '30';
			$rows 		= isset( $args['rows'] ) 		? $args['rows'] : '5';
			$class 		= isset( $args['class'] ) 		? ' class="'.$args['class'] .'"': '';
			$maxlength	= isset( $args['maxlength'] ) 	? ' maxlength="'.$args['maxlength'] .'"': '';
			$options	= get_option( $menu );
					
			if ( isset( $options[$id] ) ) {
				$current = $options[$id];
			} else {
				$current = isset( $args['default'] ) ? $args['default'] : '';
			}
	
			$disabled = (isset( $args['disabled'] )) ? ' disabled' : '';
			$html = sprintf( '<textarea id="%1$s" name="%2$s[%1$s]" cols="%3$s" rows="%4$s" class="%5$s">%6$s</textarea>', $id, $menu, $cols, $rows, $class, $current);
		
			// Displays option description.
			if ( isset( $args['description'] ) ) {
				$html .= sprintf( '<p class="description">%s</p>', $args['description'] );
			}	
		
			echo $html."\n";
		}
		
		function create_button_element_callback( $args ) {
			$menu 		= $args['menu'];
			$id 		= $args['id'];
			$buttons	= isset( $args['buttons'] ) 	? $args['buttons'] : array();
			$options	= get_option( $menu );
					
			if ( isset( $options[$id] ) ) {
				$current = $options[$id];
			} else {
				$current = isset( $args['default'] ) ? $args['default'] : '';
			}
	
			$disabled = (isset( $args['disabled'] )) ? ' disabled' : '';
			$html = "";
			foreach($buttons as $btn => $bt){
				$id 	= $id ."_". $bt['id'];				
				$type 	= $bt['type'];
				$value 	= $bt['value'];
				$sub_action 	= isset($bt['sub_action']) ? $bt['sub_action'] : $id;
				$html .= sprintf( '<input type="%1$s" id="%2$s" name="%3$s[%2$s]" value="%4$s" class="%2$s onformprocess button test_email_schedule" data-sub_action="%5$s" />', $type, $id, $menu, $value, $sub_action);
			}
			
		
			// Displays option description.
			if ( isset( $args['description'] ) ) {
				$html .= sprintf( '<p class="description">%s</p>', $args['description'] );
			}	
		
			echo $html."\n";
		}
		
		public function label_element_callback( $args ) {
			
			$html =  $default 		= isset( $args['default'] ) 		? $args['default'] : '';
			
			//$option_page = isset($_GET['page']) ? $_GET['page'] : '';
			//$currunt_page = admin_url("admin.php?page={$option_page}&ga_logout=yes");				
			//$html = 'You are presently logged in to GA account; click on Logout button to <a href="'.$currunt_page.'">Logout</a> of GA account';
		
			// Displays option description.
			if ( isset( $args['description'] ) ) {
				$html .= ' <a href="#"   title="this is my title" class="help_tip">target element</a>';			}	
		
			echo $html;
			
			//wp_enqueue_script('wpb-tooltip-jquery', plugins_url('/wpb-tooltip.js', __FILE__ ), array('jquery-ui-tooltip'), '', true);
			
			
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
		
		function get_default_cogs_key($post, $post_key = 'cogs_metakey_simple', $cog_defaulty_key = 'cogs_default_metakey_simple'){
			$default_value 			= isset($this->constants['cog'][$cog_defaulty_key]) ? $this->constants['cog'][$cog_defaulty_key] : '';
			$cogs_metakey			= isset($post[$post_key]) ? str_replace(" ","_",strtolower(trim($post['cogs_metakey_simple']))) : $default_value;
			return strlen($cogs_metakey)>0 ? $cogs_metakey : $default_value;
		}
		
		function get_number_array($start = 0,$end = 0,$multiply = 1){
			$tick_char_lengths = array();
			for($gh=$start;$gh<=$end;$gh++){
				$v = $gh * $multiply;
				$tick_char_lengths[$v] = $v;
			}
			//$this->print_array($tick_char_lengths);	
			return $tick_char_lengths;
		}
		
	}
}
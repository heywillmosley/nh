<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
	require_once('ic_commerce_premium_golden_fuctions.php');

if(!class_exists('IC_Commerce_Premium_Golden_Init')){
	class IC_Commerce_Premium_Golden_Init extends IC_Commerce_Premium_Golden_Fuctions{
			
			public $constants 				= array();
			
			public $plugin_parent			= NULL;
			
			public function __construct($file, $constants) {
				global $icpgpluginkey, $icperpagedefault, $iccurrent_page, $wp_version;
				if(is_admin()){
					
					add_action( 'admin_notices', array( $this, 'admin_notices'));
					
					$this->file 							  = $file;					
					$this->constants 						 = $constants;					
					$icpgpluginkey 						   = $this->constants['plugin_key'];
					$icperpagedefault 						= $this->constants['per_page_default'];
					$this->constants['plugin_options'] 	   = get_option($this->constants['plugin_key']);
					$ic_commercepro_pages					= array($icpgpluginkey.'_page',$icpgpluginkey.'_details_page',$icpgpluginkey.'_options_page',$icpgpluginkey,$icpgpluginkey.'_stock_list_page',$icpgpluginkey.'_report_page',$icpgpluginkey.'_cross_tab_page',$icpgpluginkey.'_google_analytics_page',$icpgpluginkey."_variation_page",$icpgpluginkey."_customer_page",$icpgpluginkey."_variation_stock_page",$icpgpluginkey."_projected_actual_sales_page",$icpgpluginkey."_tax_report_page",$icpgpluginkey."_guest_report_page",$icpgpluginkey."_add_ons_page");
					$ic_commercepro_pages 					= apply_filters('ic_commerce_premium_golden_pages', $ic_commercepro_pages, $icpgpluginkey);
					$ic_current_page						 = $this->get_request('page',NULL,false);
					
					$this->check_parent_plugin();					
					$this->define_constant();
					
					do_action('ic_commerce_premium_golden_init', $this->constants, $ic_current_page);
					
					add_action( 'admin_init', 					array( $this, 'export_csv'));
					add_action( 'admin_init', 					array( $this, 'export_pdf'));
					add_action( 'admin_init', 					array( $this, 'export_print'));
					add_action( 'admin_init', 					array( $this, 'pdf_invoice'));
										
					add_action( 'wp_loaded', 					array( $this, 'activate_page'));//Change to init to wp_loaded 20150721
					add_action( 'wp_loaded', 					array( $this, 'setting_page'));//Change to init to wp_loaded 20150721
					
					add_action('wp_ajax_'.$this->constants['plugin_key'].'_wp_ajax_action', array($this, 'wp_ajax_action'));
					
					if(in_array($ic_current_page, $ic_commercepro_pages)){
						$this->constants						= apply_filters('ic_commerce_premium_golden_constants', $this->constants);
						do_action('ic_commerce_premium_golden_page_init', $this->constants, $ic_current_page);
						add_action('admin_enqueue_scripts', 	array($this, 'wp_localize_script'));
						add_action('admin_init', 				array($this, 'admin_head'));
						add_action('admin_footer',  			array($this, 'admin_footer'),9);
					}
					
					add_action('admin_menu',					array( &$this, 'admin_menu' ) );
					
					add_action('activated_plugin',				array($this->constants['plugin_instance'],	'activated_plugin'));
					register_activation_hook(	$this->constants['plugin_file'],	array('IC_Commerce_Premium_Golden_Init',	'activate'));
					register_deactivation_hook(	$this->constants['plugin_file'], 	array('IC_Commerce_Premium_Golden_Init',	'deactivation'));
					register_uninstall_hook(	$this->constants['plugin_file'], 	array('IC_Commerce_Premium_Golden_Init',	'uninstall'));
					
					add_filter( 'plugin_action_links_'.$this->constants['plugin_slug'], array( $this, 'plugin_action_links' ), 9, 2 );
					
					if ( version_compare( $wp_version, '2.8alpha', '>' ) )
						add_filter( 'plugin_row_meta', array( $this, 'plugin_row_meta' ), 10, 4 );
						
					
					if ( version_compare( $wp_version, '3.3', '>' ) )
						add_action('admin_bar_menu', array( $this, 'admin_bar_menu'), 1000);
					
				}
			}
		
			
			function custom_fileds(){
				return '';
				global $ic_commerce_premium_golden_custom_fields;
				$c				= $this->constants;
				$this->load_class_file('ic_commerce_premium_golden_custom_fields.php');
				$ic_commerce_premium_golden_custom_fields = new IC_Commerce_Premium_Golden_Custom_fields($c);
				$ic_commerce_premium_golden_custom_fields->delete_custom_fields();
			}
			
			function define_constant(){
				global $icpgpluginkey, $icperpagedefault, $iccurrent_page, $wp_version;
				
				//New Change ID 20140918
				$this->constants['detault_stauts_slug'] 	    = array("completed","on-hold","processing");
				$this->constants['detault_order_status'] 	   = array("wc-completed","wc-on-hold","wc-processing");
				$this->constants['hide_order_status'] 		  = array();
				
				$this->constants['sub_version'] 			    = '20180927';
				$this->constants['last_updated'] 		       = '20180927';
				$this->constants['customized'] 			     = 'no';
				$this->constants['customized_date'] 		    = '20180927';
				
				$this->constants['first_order_date'] 		   = $this->first_order_date($this->constants['plugin_key']);
				$this->constants['total_shop_day'] 			 = $this->get_total_shop_day($this->constants['plugin_key']);
				$this->constants['today_date'] 				 = date_i18n("Y-m-d");
				
				$this->constants['post_status']				= $this->get_setting2('post_status',$this->constants['plugin_options'],array());
				$this->constants['hide_order_status']		  = $this->get_setting2('hide_order_status',$this->constants['plugin_options'],$this->constants['hide_order_status']);
				$this->constants['start_date']				 = $this->get_setting('start_date',$this->constants['plugin_options'],$this->constants['first_order_date']);
				$this->constants['end_date']				   = $this->get_setting('end_date',$this->constants['plugin_options'],$this->constants['today_date']);
				
				$this->constants['wp_version'] 				 = $wp_version;
				
				$file 										  = $this->constants['plugin_file'];
				$this->constants['plugin_slug'] 			    = plugin_basename( $file );
				$this->constants['plugin_file_name'] 		   = basename($this->constants['plugin_slug']);
				$this->constants['plugin_file_id'] 			 = basename($this->constants['plugin_slug'], ".php" );
				$this->constants['plugin_folder']			  = dirname($this->constants['plugin_slug']);
				$this->constants['plugin_url'] 				 = plugins_url("", $file);
				$this->constants['plugin_dir'] 				 = WP_PLUGIN_DIR ."/". $this->constants['plugin_folder'];				
				$this->constants['http_user_agent'] 		    = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
				$this->constants['siteurl'] 				    = site_url();
				$this->constants['admin_page_url']			 = $this->constants['siteurl'].'/wp-admin/admin.php';				
				$this->constants['post_order_status_found']	= isset($this->constants['post_order_status_found']) ? $this->constants['post_order_status_found'] : 0;//Added 20150225
				$this->constants['classes_path'] 		       = $this->constants['plugin_dir'].'/includes/';

				$this->is_active();
			}
			
			public static function activate(){
				global $icpgpluginkey, $icperpagedefault;
				
				$icpgpluginkey 			= "icwoocommercepremiumgold";
				$icperpagedefault 		= 5;
				
				$blog_title 			= get_bloginfo('name');
				$email_send_to			= get_option( 'admin_email' );
				$strtotime				= strtotime('this month');
				$cross_tab_start_date 	= date_i18n('Y-01-01',$strtotime);
				$cross_tab_end_date 	= date_i18n('Y-12-31',$strtotime);
				$cross_tab_start_date 	= trim($cross_tab_start_date);
				$cross_tab_end_date 	= trim($cross_tab_end_date);				
				$current_year			= date('Y',$strtotime);
				
				$default = array(
					'recent_order_per_page'				=> $icperpagedefault
					,'top_product_per_page'				=> $icperpagedefault
					,'top_customer_per_page'			=> $icperpagedefault
					,'top_billing_country_per_page'		=> $icperpagedefault
					,'top_payment_gateway_per_page'		=> $icperpagedefault
					,'top_coupon_per_page'				=> $icperpagedefault
					,'per_row_customer_page'			=> $icperpagedefault
					,'per_row_details_page'				=> $icperpagedefault
					,'per_row_stock_page'				=> $icperpagedefault
					,'per_row_all_report_page'			=> $icperpagedefault
					,'per_row_cross_tab_page'			=> $icperpagedefault
					
					
					,'email_daily_report' 				=> 1
					,'email_yesterday_report' 			=> 1
					,'email_weekly_report' 				=> 1
					,'email_last_week_report' 			=> 1
					,'email_monthly_report' 			=> 1
					,'email_last_month_report' 			=> 1
					,'email_this_year_report' 			=> 1
					,'email_last_year_report' 			=> 1
					,'email_till_today_report' 			=> 1
					
					,'email_send_to'					=> $email_send_to
					,'email_from_name'					=> $blog_title
					,'email_from_email'					=> $email_send_to
					,'email_subject'					=> "Sales Summary " . $blog_title
					,'email_schedule'					=> 'daily'
					,'act_email_reporting'				=> 0
					//20150217
					,'logo_image'							=> ''
					,'company_name'							=> $blog_title
					,'show_dasbboard_summary_box'			=> 1
					,'show_dasbboard_order_summary'			=> 1
					,'show_dasbboard_sales_order_status'	=> 1			
					,'show_dasbboard_sales_order_status'	=> 1
					,'show_dasbboard_top_products'			=> 1
					,'show_dasbboard_top_billing_country'	=> 1
					,'show_dasbboard_top_payment_gateway'	=> 1
					,'show_dasbboard_top_recent_orders'		=> 1
					,'show_dasbboard_top_customer'			=> 1
					,'show_dasbboard_top_coupons'			=> 1
					
					,'show_dasbboard_graph_ss'				=> 1
					,'show_dasbboard_graph_ao'				=> 1
					,'hide_order_status'					=> 'trash'
					
					,'cogs_enable_adding'					=> 0
					,'cogs_enable_reporting'					=> 0
					,'cogs_metakey'							=> '_ic_cogs_cost'
					
					
					,'cur_projected_sales_year'				=> $current_year
					,'projected_sales_year' 				=> $current_year
					
					
					
					,'cross_tab_start_date'					=> $cross_tab_start_date
					,'cross_tab_end_date'					=> $cross_tab_end_date
					
					,'company_name'							=> $blog_title
					,'pdf_invoice_company_name'				=> $blog_title
					,'report_title'							=> $blog_title ." report"
					,'theme_color'							=> '#77aedb'//$this->constants['color_code']
					
					//New Graph Settings 20150407
					,'tick_angle'							=> 0
					,'tick_font_size'						=> 9
					,'tick_char_length'						=> 15
					,'tick_char_suffix'						=> "..."
					,'graph_height'							=> 300
				);
				
				$projected_sales_year_option 					= $icpgpluginkey.'_projected_amount_'.$current_year;
				$projected_amounts_old 							= get_option($projected_sales_year_option,false);
				
				if(!$projected_amounts_old){
					$total_projected_amount	= 0;
					$projected_amounts		= array();
					$projected_month_list	= array("January","February","March","April","May","June","July","August","September","October","November","December");
					for($m = 0;$m<=11;$m++){
						$l 										= $projected_month_list[$m];				
						$l 										= $projected_month_list[$m];
						$projected_sales_month_current			= rand(1111,2222);
						$projected_amounts[$l] 					= $projected_sales_month_current;					
						$default["projected_sales_month_{$l}"] 	= $projected_sales_month_current;						
						$total_projected_amount 				= $total_projected_amount + $projected_sales_month_current;
					}
					
					$total_projected_amount_option 	= $icpgpluginkey.'_total_projected_amount_'.$current_year;
					update_option($projected_sales_year_option,$projected_amounts);
					update_option($total_projected_amount_option,$total_projected_amount);
					
					
				}
				
				
				
				//Added 20150217
				$o = get_option($icpgpluginkey,false);
				if(!$o){
					delete_option( $icpgpluginkey);
					add_option( $icpgpluginkey, $default );			
					add_option( $icpgpluginkey.'_per_page_default', $icperpagedefault);
				}
								
				//echo $icpgpluginkey;
				//add_option( $icpgpluginkey, $default );			
				//add_option( $icpgpluginkey.'_per_page_default', $icperpagedefault);
				
				//echo $icpgpluginkey." - ".$icperpagedefault;
				//exit;
			}
			
			public static function deactivation(){
				global $icpgpluginkey;
				$icpgpluginkey 			= "icwoocommercepremiumgold";
				delete_option( $icpgpluginkey.'_admin_notice_error');
			}
			
			public static function uninstall(){
				global $icpgpluginkey;
				$icpgpluginkey 			= "icwoocommercepremiumgold";
				
				/*delete_option( $icpgpluginkey); 	
				delete_option( $icpgpluginkey.'email_subject');
				delete_option( $icpgpluginkey.'_activated');
				delete_option( $icpgpluginkey.'_admin_notice_error');
				delete_option( $icpgpluginkey.'_details_page_save_detail_column');
				delete_option( $icpgpluginkey.'_details_page_save_normal_column');
				delete_option( $icpgpluginkey.'_projected_amount_');
				delete_option( $icpgpluginkey.'_projected_amount_2010');
				delete_option( $icpgpluginkey.'_projected_amount_2011');
				delete_option( $icpgpluginkey.'_projected_amount_2012');
				delete_option( $icpgpluginkey.'_projected_amount_2013');
				delete_option( $icpgpluginkey.'_projected_amount_2014');
				delete_option( $icpgpluginkey.'_projected_amount_2015');
				delete_option( $icpgpluginkey.'_projected_sales_2010');
				delete_option( $icpgpluginkey.'_projected_sales_2011');
				delete_option( $icpgpluginkey.'_total_projected_amount_2010');
				delete_option( $icpgpluginkey.'_total_projected_amount_2011');
				delete_option( $icpgpluginkey.'_total_projected_amount_2012');
				delete_option( $icpgpluginkey.'_total_projected_amount_2013');
				delete_option( $icpgpluginkey.'_total_projected_amount_2014');
				delete_option( $icpgpluginkey.'_total_projected_amount_2015');
				delete_option( $icpgpluginkey.'_variation_page_show_variation');
				delete_option( $icpgpluginkey.'_ic_commerce_custom_field_deleted');*/
				
				$wpdb->query("DELETE FROM `woo_options` WHERE `option_name` LIKE '{$icpgpluginkey}%'");
				$wpdb->query("DELETE FROM `woo_options` WHERE `option_name` LIKE '%ic_commerce_%'");
			}
			
			public static function activated_plugin(){
				global $icpgpluginkey;
				$icpgpluginkey 			= "icwoocommercepremiumgold";
				update_option($icpgpluginkey.'_activated_plugin_error',  ob_get_contents());
			}
			
			function plugin_action_links($plugin_links, $file){
				if ( ! current_user_can( $this->constants['plugin_role'] ) ) return;
				if ( $file == $this->constants['plugin_slug']) {
					$settings_link = array();
					$settings_link[] = '<a href="'.admin_url('admin.php?page='.$this->constants['plugin_key'].'_page').'" 			title="'.__($this->constants['plugin_name'].' Dashboard', 	'icwoocommerce_textdomains').'">'.__('Dashboard', 	'icwoocommerce_textdomains').'</a>';
					if($this->is_product_active == 1) {
						$settings_link[] = '<a href="'.admin_url('admin.php?page='.$this->constants['plugin_key'].'_details_page').'" 	title="'.__($this->constants['plugin_name'].' Reports', 	'icwoocommerce_textdomains').'">'.__('Detail', 		'icwoocommerce_textdomains').'</a>';
						$settings_link[] = '<a href="'.admin_url('admin.php?page='.$this->constants['plugin_key'].'_options_page').'" 	title="'.__($this->constants['plugin_name'].' Settings', 	'icwoocommerce_textdomains').'">'.__('Settings', 	'icwoocommerce_textdomains').'</a>';
					}
					if($this->is_product_active != 1) 
					$settings_link[] = '<a href="'.admin_url('admin.php?page='.$this->constants['plugin_key'].'_activate_page').'" 	title="'.__($this->constants['plugin_name'].' Activate', 	'icwoocommerce_textdomains').'">'.__('Activate', 	'icwoocommerce_textdomains').'</a>';
					return array_merge( $plugin_links, $settings_link );
				}		
				return $plugin_links;
			}
			
			function plugin_row_meta($plugin_meta, $plugin_file, $plugin_data, $status ){
				if ( $plugin_file == $this->constants['plugin_slug']) {
					$settings_link = array();
					$settings_link[] = '<a href="'.admin_url('admin.php?page='.$this->constants['plugin_key'].'_page').'" 			title="'.__($this->constants['plugin_name'].' Dashboard', 	'icwoocommerce_textdomains').'">'.__('Dashboard', 	'icwoocommerce_textdomains').'</a>';
					if($this->is_product_active == 1) {							
						$settings_link[] = '<a href="'.admin_url('admin.php?page='.$this->constants['plugin_key'].'_details_page').'" 	title="'.__($this->constants['plugin_name'].' Reports', 	'icwoocommerce_textdomains').'">'.__('Detail', 		'icwoocommerce_textdomains').'</a>';
						$settings_link[] = '<a href="'.admin_url('admin.php?page='.$this->constants['plugin_key'].'_options_page').'" 	title="'.__($this->constants['plugin_name'].' Settings', 	'icwoocommerce_textdomains').'">'.__('Settings', 	'icwoocommerce_textdomains').'</a>';
					}
					if($this->is_product_active != 1) 
					$settings_link[] = '<a href="'.admin_url('admin.php?page='.$this->constants['plugin_key'].'_activate_page').'" 	title="'.__($this->constants['plugin_name'].' Activate', 	'icwoocommerce_textdomains').'">'.__('Activate', 	'icwoocommerce_textdomains').'</a>';
					return array_merge( $plugin_meta, $settings_link );
				}		
				return $plugin_meta;
			}
			
			function admin_menu(){
				add_menu_page($this->constants['plugin_name'], $this->constants['plugin_menu_name'], $this->constants['plugin_role'], $this->constants['plugin_key'].'_page', array($this, 'add_page'), plugins_url( '/assets/images/menu_icons.png',$this->constants['plugin_file']), '58.0' );
				add_submenu_page($this->constants['plugin_key'].'_page',__( $this->constants['plugin_name'].' Dashboard', 	'icwoocommerce_textdomains'),	__( 'Dashboard',	'icwoocommerce_textdomains'),$this->constants['plugin_role'],$this->constants['plugin_key'].'_page',				array( $this, 'add_page' ));
				if($this->is_product_active == 1) {	
					add_submenu_page($this->constants['plugin_key'].'_page',__( $this->constants['plugin_name'].' Details', 	'icwoocommerce_textdomains'),	__( 'Details',		'icwoocommerce_textdomains'),$this->constants['plugin_role'],$this->constants['plugin_key'].'_details_page',		array( $this, 'add_page' ));
					add_submenu_page($this->constants['plugin_key'].'_page',__( $this->constants['plugin_name'].' All Details', 'icwoocommerce_textdomains'),	__( 'All Details',	'icwoocommerce_textdomains'),$this->constants['plugin_role'],$this->constants['plugin_key'].'_report_page',			array( $this, 'add_page' ));
					add_submenu_page($this->constants['plugin_key'].'_page',__( $this->constants['plugin_name'].' Crosstab', 	'icwoocommerce_textdomains'),	__( 'Crosstab',		'icwoocommerce_textdomains'),$this->constants['plugin_role'],$this->constants['plugin_key'].'_cross_tab_page',		array( $this, 'add_page' ));
					add_submenu_page($this->constants['plugin_key'].'_page',__( $this->constants['plugin_name'].' Variation', 	'icwoocommerce_textdomains'),	__( 'Variation',	'icwoocommerce_textdomains'),$this->constants['plugin_role'],$this->constants['plugin_key'].'_variation_page',		array( $this, 'add_page' ));
					//add_submenu_page($this->constants['plugin_key'].'_page',__( $this->constants['plugin_name'].' Google Analytics', 	'icwoocommerce_textdomains'),	__( 'Google Analytics',	'icwoocommerce_textdomains'),$this->constants['plugin_role'],$this->constants['plugin_key'].'_google_analytics_page',		array( $this, 'add_page' ));
					add_submenu_page($this->constants['plugin_key'].'_page',__( $this->constants['plugin_name'].' Stock List', 	'icwoocommerce_textdomains'),	__( 'Stock List', 	'icwoocommerce_textdomains'),$this->constants['plugin_role'],$this->constants['plugin_key'].'_stock_list_page',		array( $this, 'add_page' ));
					
					add_submenu_page($this->constants['plugin_key'].'_page',__( $this->constants['plugin_name'].' Variation Stock List', 	'icwoocommerce_textdomains'),	__( 'Variation Stock', 	'icwoocommerce_textdomains'),$this->constants['plugin_role'],$this->constants['plugin_key'].'_variation_stock_page',		array( $this, 'add_page' ));
					add_submenu_page($this->constants['plugin_key'].'_page',__( $this->constants['plugin_name'].' Projected Vs Actual Sales', 	'icwoocommerce_textdomains'),	__( 'Projected Vs Actual Sales', 	'icwoocommerce_textdomains'),$this->constants['plugin_role'],$this->constants['plugin_key'].'_projected_actual_sales_page',		array( $this, 'add_page' ));
					add_submenu_page($this->constants['plugin_key'].'_page',__( $this->constants['plugin_name'].' Tax Report', 	'icwoocommerce_textdomains'),	__( 'Tax Reports',	'icwoocommerce_textdomains'),$this->constants['plugin_role'],$this->constants['plugin_key'].'_tax_report_page',	array( $this, 'add_page' ));
					//add_submenu_page($this->constants['plugin_key'].'_page',__( $this->constants['plugin_name'].' Guest Report', 	'icwoocommerce_textdomains'),	__( 'Guest Report',	'icwoocommerce_textdomains'),$this->constants['plugin_role'],$this->constants['plugin_key'].'_guest_report_page',	array( $this, 'add_page' ));
					
					
					do_action('ic_commerce_premium_golden_admin_menu', $this->constants);
					
					add_submenu_page($this->constants['plugin_key'].'_page',__( $this->constants['plugin_name'].' Settings', 	'icwoocommerce_textdomains'),	__( 'Settings', 	'icwoocommerce_textdomains'),$this->constants['plugin_role'],$this->constants['plugin_key'].'_options_page',		array( $this, 'add_page' ));
					
				}
				if(current_user_can('manage_options')){
					$at = "Activated";
					if($this->is_product_active != 1) $at = "Activate";
					
					add_submenu_page($this->constants['plugin_key'].'_page',__( $this->constants['plugin_name'].' '.$at, 	'icwoocommerce_textdomains'),	__( $at, 	'icwoocommerce_textdomains'),$this->constants['plugin_role'],$this->constants['plugin_key'].'_activate_page',	array( $this, 'add_page' ));
				}
				
				add_submenu_page($this->constants['plugin_key'].'_page',__( $this->constants['plugin_name'].' Other Plug-ins', 	'icwoocommerce_textdomains'),	__( 'Other Plug-ins', 	'icwoocommerce_textdomains' ),$this->constants['plugin_role'],$this->constants['plugin_key'].'_add_ons_page',		array( $this, 'add_page' ));
				
				//remove_submenu_page($this->constants['plugin_key']."_page", $this->constants['plugin_key']."_report_page");
			}
			
			function admin_bar_menu(){
				global $wp_admin_bar;
				
				if ( ! current_user_can( $this->constants['plugin_role'] ) ) return;
				
				if($this->is_product_active != 1)  return true;
				$wp_admin_bar->add_menu(
					array(	'id' => $this->constants['plugin_key'],
							'title' => __($this->constants['plugin_menu_name'], 'icwoocommerce_textdomains'),
							'href' => admin_url('admin.php?page='.$this->constants['plugin_key'].'_page')
					)
				);
				
				$wp_admin_bar->add_menu(
					array(	'parent' => $this->constants['plugin_key'],
							'id' => $this->constants['plugin_key'].'_page',
							'title' => __('Dashboard', 'icwoocommerce_textdomains'),
							'href' => admin_url('admin.php?page='.$this->constants['plugin_key'].'_page')
					)
				);
				
				$wp_admin_bar->add_menu(
					array(	'parent' => $this->constants['plugin_key'],
							'id' => $this->constants['plugin_key'].'_details_page',
							'title' => __('Details', 'icwoocommerce_textdomains'),
							'href' => admin_url('admin.php?page='.$this->constants['plugin_key'].'_details_page')
					)
				);
				
				$wp_admin_bar->add_menu(
					array(	'parent' => $this->constants['plugin_key'],
							'id' => $this->constants['plugin_key'].'_options_page',
							'title' => __('Settings', 'icwoocommerce_textdomains'),
							'href' => admin_url('admin.php?page='.$this->constants['plugin_key'].'_options_page')
					)
				);
			}
			
			
			function add_page(){
				global $setting_intence, $activate_golden_intence;
				$current_page	= $this->get_request('page',NULL,false);
				$c				= $this->constants;
				$title			= NULL;
				$intence		= NULL;
				
				if ( ! current_user_can($this->constants['plugin_role']) ) return;
				
				switch($current_page){
					case $this->constants['plugin_key'].'_page':	
						$title = __('Advance Sales Report (Premium Gold Version)','icwoocommerce_textdomains');
						include_once($this->constants['plugin_dir'].'/includes/ic_commerce_premium_golden_dashboard.php');
						$intence = new IC_Commerce_Premium_Golden_Dashboard($c);
						break;
					case $this->constants['plugin_key'].'_details_page':
						$title = NULL;
						include_once('ic_commerce_premium_golden_custom_report.php');
						$intence = new IC_Commerce_Premium_Golden_Detail_report($c);
						break;
					case $this->constants['plugin_key'].'_report_page':
						$title = __("All Page",'icwoocommerce_textdomains');
						$title = NULL;
						include_once('ic_commerce_premium_golden_all_report.php');
						$intence = new IC_Commerce_Premium_Golden_All_Report($c);
						break;
					case $this->constants['plugin_key'].'_cross_tab_page':
						$title = __("Crosstab",'icwoocommerce_textdomains');
						$title = NULL;
						include_once('ic_commerce_premium_golden_cross_tab.php');
						$intence = new IC_Commerce_Premium_Golden_Cross_Tab($c);
						break;
					case $this->constants['plugin_key'].'_variation_page':
						$title = __("Variation",'icwoocommerce_textdomains');
						$title = NULL;
						include_once('ic_commerce_premium_golden_variation.php');
						$intence = new IC_Commerce_Premium_Golden_Variation($c);
						break;							
					case $this->constants['plugin_key'].'_google_analytics_page':
						$title = __("Google Analytics",'icwoocommerce_textdomains');
						$title = NULL;
						include_once('ic_commerce_premium_golden_google_analytics.php');
						$intence = new IC_Commerce_Premium_Golden_Google_Analytics($c);
						break;
						
					case $this->constants['plugin_key'].'_stock_list_page':
						$title = __('Stock List','icwoocommerce_textdomains');
						include_once('ic_commerce_premium_golden_stock_list.php' );
						$intence = new IC_Commerce_Premium_Golden_Stock_List_report($c);
						break;	
					case $this->constants['plugin_key'].'_variation_stock_page':
						$title = __('Variation Stock List','icwoocommerce_textdomains');
						include_once('ic_commerce_premium_golden_variation_stock_list.php' );
						$intence = new IC_Commerce_Premium_Golden_Variation_Stock_List_report($c);
						break;	
					case $this->constants['plugin_key'].'_add_ons_page':
						$title = 'Other Plug-ins';						
						include_once('ic_commerce_premium_golden_add_ons.php' );
						$intence = new IC_Commerce_Premium_Golden_Add_Ons($c);
						break;
					case $this->constants['plugin_key'].'_options_page':
						$title = __('Settings','icwoocommerce_textdomains');
						$intence = $setting_intence;
						break;
					case $this->constants['plugin_key'].'_activate_page':
						$title = __('Activate','icwoocommerce_textdomains');
						$intence = $activate_golden_intence;
						break;
					case $this->constants['plugin_key'].'_projected_actual_sales_page':
						$title = __("Projectd Vs Actual Sales",'icwoocommerce_textdomains');
						$title = NULL;
						include_once('ic_commerce_premium_golden_projected_actual_sales.php');
						$intence = new IC_Commerce_Premium_Golden_Projected_Actual_Sales($c);
						break;
					case $this->constants['plugin_key'].'_tax_report_page':
						$title = __('Tax Reports','icwoocommerce_textdomains');
						include_once('ic_commerce_premium_golden_tax_report.php' );				
						$intence = new IC_Commerce_Premium_Golden_Tax_report($c);
						break;
					case $this->constants['plugin_key'].'_guest_report_page':
						$title = __("Guest Page",'icwoocommerce_textdomains');
						$title = NULL;
						include_once('ic_commerce_premium_golden_guest_report.php');
						$intence = new IC_Commerce_Premium_Golden_Guest_Report($c);
						break;
					default:
						//include_once('ic_commerce_premium_golden_dashboard.php');
						//$intence = new IC_Commerce_Premium_Golden_Dashboard($c);
						break;
					break;			
				}
				//add_action('admin_footer',  array( &$this, 'admin_footer'),9);
				//$this->print_array($this->constants);
				?>
                	<div class="wrap <?php echo $this->constants['plugin_key']?>_wrap iccommercepluginwrap">
                    	<div class="icon32" id="icon-options-general"><br /></div>
                    	<?php  if($title):?>
                            <h2><?php _e($title,'icwoocommerce_textdomains');?></h2>
                        <?php endif; ?>
						<?php if($intence) $intence->init(); else echo "Class not found."?>			
                    </div>
                <?php   
				//add_action( 'admin_footer', array( $this, 'admin_footer_css'),100);
			}
			 
			public $is_product_active = NULL;
			
			public function is_active(){
				$r = false;
				if($this->is_product_active == NULL){					
					$actived_product = get_option($this->constants['plugin_key'] . '_activated');
					
					//$this->print_array($actived_product);
					$this->is_product_active = 0;
					if($actived_product)
					foreach($actived_product as $key => $value){
						if($this->constants['plugin_file_id'] == $key && $value == 1){
							$r = true;
							$this->is_product_active = 1;
						}
					}
				}
				return $r;
			}
			
			function activate_page(){
				global $activate_golden_intence;
				if(current_user_can('manage_options')){
					$c					= $this->constants;
					include_once('ic_commerce_premium_golden_activate.php');
					$activate_golden_intence 	= new IC_Commerce_Premium_Golden_Activate($c);
				}				
				return $activate_golden_intence;
			}
			
			
			function setting_page(){
				global $setting_intence;
				$current_page	= $this->get_request('page',NULL,false);
				$option_page	= $this->get_request('option_page',NULL,false);
				
				if($current_page == $this->constants['plugin_key'].'_options_page' || $option_page == $this->constants['plugin_key']){				
					$c				= $this->constants;
					include_once('ic_commerce_premium_golden_plugin_settings.php');	
					$setting_intence = new IC_Commerce_Premium_Golden_Settings($c);				
				}
				return $setting_intence;
			}
			
			function export_csv(){
				
				//$this->print_array($_REQUEST);
				//exit;
				
				if(isset($_REQUEST['export_file_format']) and ($_REQUEST['export_file_format'] == "csv" || $_REQUEST['export_file_format'] == "xls" )){
					$time_limit = apply_filters("ic_commerce_maximum_execution_time",300,$_REQUEST['export_file_format']);
					set_time_limit($time_limit);//set_time_limit — Limits the maximum execution time
					$out2 = ob_get_contents();
					if(!empty($out2))ob_end_clean();
				}else{
					return '';
				}
				
				do_action('ic_commerce_premium_golden_export_csv_xls',$this->constants, $_REQUEST['export_file_format']);
				
				
				
				if(isset($_REQUEST[$this->constants['plugin_key'].'_details_page_export_csv'])){
					$c				= $this->constants;
					include_once('ic_commerce_premium_golden_custom_report.php' );
					$IC_Commerce_Premium_Golden_Detail_report = new IC_Commerce_Premium_Golden_Detail_report($c);
					$IC_Commerce_Premium_Golden_Detail_report->ic_commerce_custom_report_page_export_csv();
					exit;
				}
				if(isset($_REQUEST[$this->constants['plugin_key'].'_customer_page_export_csv'])){
					$c				= $this->constants;
					include_once('ic_commerce_premium_golden_customer_report.php' );
					$IC_Commerce_Premium_Golden_Detail_report = new IC_Commerce_Golden_Customer_report($c);
					$IC_Commerce_Premium_Golden_Detail_report->ic_commerce_custom_report_page_export_csv();
					exit;
				}	
				if(isset($_REQUEST[$this->constants['plugin_key'].'_customer_page_export_xls'])){
					$c				= $this->constants;
					include_once('ic_commerce_premium_golden_customer_report.php' );
					$IC_Commerce_Premium_Golden_Detail_report = new IC_Commerce_Golden_Customer_report($c);
					$IC_Commerce_Premium_Golden_Detail_report->ic_commerce_custom_report_page_export_csv('xls');
					exit;
				}				
				
				
				if(isset($_REQUEST[$this->constants['plugin_key'].'_report_page_export_csv'])){
					$c				= $this->constants;
					include_once('ic_commerce_premium_golden_all_report.php' );
					$IC_Commerce_Premium_Golden_Detail_report = new IC_Commerce_Premium_Golden_All_Report($c);
					$IC_Commerce_Premium_Golden_Detail_report->ic_commerce_custom_report_page_export_csv('csv');
					exit;
				}
				
				if(isset($_REQUEST[$this->constants['plugin_key'].'_report_page_export_xls'])){
					$c				= $this->constants;
					include_once('ic_commerce_premium_golden_all_report.php' );
					$IC_Commerce_Premium_Golden_Detail_report = new IC_Commerce_Premium_Golden_All_Report($c);
					$IC_Commerce_Premium_Golden_Detail_report->ic_commerce_custom_report_page_export_csv('xls');
					exit;
				}		
				
				if(isset($_REQUEST[$this->constants['plugin_key'].'_cross_tab_page_export_csv'])){
					$c				= $this->constants;
					include_once('ic_commerce_premium_golden_cross_tab.php' );
					$IC_Commerce_Premium_Golden_Detail_report = new IC_Commerce_Premium_Golden_Cross_Tab($c);
					$IC_Commerce_Premium_Golden_Detail_report->ic_commerce_custom_report_page_export_csv('csv');
					exit;
				}
				
				if(isset($_REQUEST[$this->constants['plugin_key'].'_stock_list_page_export_csv'])
				|| isset($_REQUEST[$this->constants['plugin_key'].'_email_alert_simple_products_export_csv'])){
					$c				= $this->constants;
					include_once('ic_commerce_premium_golden_stock_list.php' );
					$IC_Commerce_Premium_Golden_Detail_report = new IC_Commerce_Premium_Golden_Stock_List_report($c);
					$IC_Commerce_Premium_Golden_Detail_report->ic_commerce_custom_report_page_export_csv();
					exit;
				}
				
				if(isset($_REQUEST[$this->constants['plugin_key'].'_variation_stock_page_export_csv'])
				|| isset($_REQUEST[$this->constants['plugin_key'].'_email_alert_variation_products_export_csv'])){
					$c				= $this->constants;
					include_once('ic_commerce_premium_golden_variation_stock_list.php' );
					$IC_Commerce_Premium_Golden_Detail_report = new IC_Commerce_Premium_Golden_Variation_Stock_List_report($c);
					$IC_Commerce_Premium_Golden_Detail_report->ic_commerce_custom_report_page_export_csv('csv');
					exit;
				}
				
				if(isset($_REQUEST[$this->constants['plugin_key'].'_cross_tab_page_export_xls'])){
					$c				= $this->constants;
					include_once('ic_commerce_premium_golden_cross_tab.php' );
					$IC_Commerce_Premium_Golden_Detail_report = new IC_Commerce_Premium_Golden_Cross_Tab($c);
					$IC_Commerce_Premium_Golden_Detail_report->ic_commerce_custom_report_page_export_csv('xls');
					exit;
				}
				
				if(isset($_REQUEST[$this->constants['plugin_key'].'_google_analytics_page_export_csv'])){
					$c				= $this->constants;
					include_once('ic_commerce_premium_golden_google_analytics.php');
					$IC_Commerce_Premium_Golden_Detail_report = new IC_Commerce_Premium_Golden_Google_Analytics($c);
					$IC_Commerce_Premium_Golden_Detail_report->ic_commerce_custom_report_page_export_csv('csv');
					exit;
				}
				
				if(isset($_REQUEST[$this->constants['plugin_key'].'_google_analytics_page_export_xls'])){
					$c				= $this->constants;
					include_once('ic_commerce_premium_golden_google_analytics.php');
					$IC_Commerce_Premium_Golden_Detail_report = new IC_Commerce_Premium_Golden_Google_Analytics($c);
					$IC_Commerce_Premium_Golden_Detail_report->ic_commerce_custom_report_page_export_csv('xls');
					exit;
				}
				
				if(isset($_REQUEST[$this->constants['plugin_key'].'_variation_page_export_csv'])){
					$c				= $this->constants;
					include_once('ic_commerce_premium_golden_variation.php');
					$IC_Commerce_Premium_Golden_Detail_report = new IC_Commerce_Premium_Golden_Variation($c);
					$IC_Commerce_Premium_Golden_Detail_report->ic_commerce_custom_report_page_export_csv('csv');
					exit;
				}
				
				if(isset($_REQUEST[$this->constants['plugin_key'].'_variation_page_export_xls'])){
					$c				= $this->constants;
					include_once('ic_commerce_premium_golden_variation.php');
					$IC_Commerce_Premium_Golden_Detail_report = new IC_Commerce_Premium_Golden_Variation($c);
					$IC_Commerce_Premium_Golden_Detail_report->ic_commerce_custom_report_page_export_csv('xls');
					exit;
				}
				
				if(isset($_REQUEST[$this->constants['plugin_key'].'_tax_report_page_export_csv'])){
					$c				= $this->constants;
					include_once('ic_commerce_premium_golden_tax_report.php' );
					$IC_Commerce_Tax_Pro_Detail_report = new IC_Commerce_Premium_Golden_Tax_report($c);
					$IC_Commerce_Tax_Pro_Detail_report->ic_commerce_custom_report_page_export_csv('csv');
					exit;
				}
				
				if(isset($_REQUEST[$this->constants['plugin_key'].'_tax_report_page_export_xls'])){
					$c				= $this->constants;
					include_once('ic_commerce_premium_golden_tax_report.php' );
					$IC_Commerce_Tax_Pro_Detail_report = new IC_Commerce_Premium_Golden_Tax_report($c);
					$IC_Commerce_Tax_Pro_Detail_report->ic_commerce_custom_report_page_export_csv('xls');
					exit;
				}
				
			}
			function export_pdf(){
				
				//$this->print_array($_REQUEST);
				//exit;
				
				if(isset($_REQUEST['export_file_format']) and $_REQUEST['export_file_format'] == "pdf"){
					$time_limit = apply_filters("ic_commerce_maximum_execution_time",300,$_REQUEST['export_file_format']);
					set_time_limit($time_limit);//set_time_limit — Limits the maximum execution time
					$out2 = ob_get_contents();
					if(!empty($out2))ob_end_clean();
				}else{
					return '';
				}
				
				do_action('ic_commerce_premium_golden_export_pdf',$this->constants, $_REQUEST['export_file_format']);
				
				if(isset($_REQUEST[$this->constants['plugin_key'].'_details_page_export_pdf'])){
					$c				= $this->constants;
					include_once('ic_commerce_premium_golden_custom_report.php' );
					$IC_Commerce_Premium_Golden_Detail_report = new IC_Commerce_Premium_Golden_Detail_report($c);
					$IC_Commerce_Premium_Golden_Detail_report->ic_commerce_custom_report_page_export_pdf();
					exit;
				}
				if(isset($_REQUEST[$this->constants['plugin_key'].'_customer_page_export_pdf'])){
					$c				= $this->constants;
					include_once('ic_commerce_premium_golden_customer_report.php' );
					$IC_Commerce_Premium_Golden_Detail_report = new IC_Commerce_Golden_Customer_report($c);
					$IC_Commerce_Premium_Golden_Detail_report->ic_commerce_custom_report_page_export_pdf();
					exit;
				}
				
				if(isset($_REQUEST[$this->constants['plugin_key'].'_stock_list_page_export_pdf'])
				|| isset($_REQUEST[$this->constants['plugin_key'].'_email_alert_simple_products_export_pdf'])){
					$c				= $this->constants;
					include_once('ic_commerce_premium_golden_stock_list.php' );
					$IC_Commerce_Premium_Golden_Detail_report = new IC_Commerce_Premium_Golden_Stock_List_report($c);
					$IC_Commerce_Premium_Golden_Detail_report->ic_commerce_custom_report_page_export_pdf();
					exit;
				}
				
				if(isset($_REQUEST[$this->constants['plugin_key'].'_variation_stock_page_export_pdf'])				
				|| isset($_REQUEST[$this->constants['plugin_key'].'_email_alert_variation_products_export_pdf'])){
					$c				= $this->constants;
					include_once('ic_commerce_premium_golden_variation_stock_list.php' );
					$IC_Commerce_Premium_Golden_Detail_report = new IC_Commerce_Premium_Golden_Variation_Stock_List_report($c);
					$IC_Commerce_Premium_Golden_Detail_report->ic_commerce_custom_report_page_export_pdf();
					exit;
				}
				
				if(isset($_REQUEST[$this->constants['plugin_key'].'_report_page_export_pdf'])){
					$c				= $this->constants;
					include_once('ic_commerce_premium_golden_all_report.php' );
					$IC_Commerce_Premium_Golden_Detail_report = new IC_Commerce_Premium_Golden_All_Report($c);
					$IC_Commerce_Premium_Golden_Detail_report->ic_commerce_custom_report_page_export_pdf();
					exit;
				}
				
				
				
				if(isset($_REQUEST[$this->constants['plugin_key'].'_cross_tab_page_export_pdf'])){
					$c				= $this->constants;
					include_once('ic_commerce_premium_golden_cross_tab.php' );
					$IC_Commerce_Premium_Golden_Detail_report = new IC_Commerce_Premium_Golden_Cross_Tab($c);
					$IC_Commerce_Premium_Golden_Detail_report->ic_commerce_custom_report_page_export_pdf();
					exit;
				}
				
				if(isset($_REQUEST[$this->constants['plugin_key'].'_google_analytics_page_export_pdf'])){
					$c				= $this->constants;
					include_once('ic_commerce_premium_golden_google_analytics.php');
					$IC_Commerce_Premium_Golden_Detail_report = new IC_Commerce_Premium_Golden_Google_Analytics($c);
					$IC_Commerce_Premium_Golden_Detail_report->ic_commerce_custom_report_page_export_pdf();
					exit;
				}
				
				
				if(isset($_REQUEST[$this->constants['plugin_key'].'_variation_page_export_pdf'])){
					$c				= $this->constants;
					include_once('ic_commerce_premium_golden_variation.php' );
					$IC_Commerce_Premium_Golden_Detail_report = new IC_Commerce_Premium_Golden_Variation($c);
					$IC_Commerce_Premium_Golden_Detail_report->ic_commerce_custom_report_page_export_pdf();
					exit;
				}
				
				if(isset($_REQUEST[$this->constants['plugin_key'].'_tax_report_page_export_pdf'])){
					$c				= $this->constants;
					include_once('ic_commerce_premium_golden_tax_report.php' );
					$IC_Commerce_Tax_Pro_Detail_report = new IC_Commerce_Premium_Golden_Tax_report($c);
					$IC_Commerce_Tax_Pro_Detail_report->ic_commerce_custom_report_page_export_pdf();
					exit;
				}
				
				
			}
			
			function pdf_invoice(){
				
				$bulk_action = $this->get_request('bulk_action','0');					
				if($bulk_action == 'pdf_invoice_download' || $bulk_action == "pdf_invoice_print" || $bulk_action == $this->constants['plugin_key']."_pdf_invoice_download"){//Modified 20150205
					$time_limit = apply_filters("ic_commerce_maximum_execution_time",3000,'pdf_invoice');
					set_time_limit($time_limit);//set_time_limit — Limits the maximum execution time
					
					$out2 = ob_get_contents();
					if(!empty($out2))ob_end_clean();
					
					$c				= $this->constants;
					include_once('ic_commerce_premium_golden_export_invoice.php' );
					$i = new IC_Commerce_Premium_Golden_Export_Invoice($c);
					$i->invoice_action();
					exit;
					die;
				}
			}
			
			function export_print(){
				if(isset($_REQUEST[$this->constants['plugin_key'].'_details_page_export_print'])){
					$c				= $this->constants;
					include_once('ic_commerce_premium_golden_custom_report.php' );
					$IC_Commerce_Premium_Golden_Detail_report = new IC_Commerce_Premium_Golden_Detail_report($c);
					$IC_Commerce_Premium_Golden_Detail_report->ic_commerce_custom_admin_report_iframe_request('all_row');
					exit;
				}
				if(isset($_REQUEST[$this->constants['plugin_key'].'_customer_page_export_print'])){
					$c				= $this->constants;
					include_once('ic_commerce_premium_golden_customer_report.php' );
					$IC_Commerce_Premium_Golden_Detail_report = new IC_Commerce_Golden_Customer_report($c);
					$IC_Commerce_Premium_Golden_Detail_report->ic_commerce_custom_admin_report_iframe_request('all_row');
					exit;
				}
			}
			
			function admin_head() {				
				wp_enqueue_style(  $this->constants['plugin_key'].'_admin_styles', 								$this->constants['plugin_url'].'/assets/css/admin.css' );
				wp_enqueue_style(  $this->constants['plugin_key'].'_fontawesome_styles', 						$this->constants['plugin_url'].'/assets/css/font-awesome.min.css' );
			}
			function admin_footer() {
				
				$current_page	= $this->get_request('page',NULL,false);
				
				//wp_enqueue_style(  $this->constants['plugin_key'].'_admin_styles', 								$this->constants['plugin_url'].'/assets/css/admin.css' );
				
				if($current_page == $this->constants['plugin_key'].'_page'){
					/*
					wp_enqueue_style(  $this->constants['plugin_key'].'_admin_jquery_jqplot_mint_css',				$this->constants['plugin_url'].'/assets/graph/css/jquery.jqplot.min.css');
					wp_enqueue_script( $this->constants['plugin_key'].'_admin_jquery_jqplot', 						$this->constants['plugin_url'].'/assets/graph/scripts/jquery.jqplot.min.js',array('jquery'));
					wp_enqueue_script( $this->constants['plugin_key'].'_admin_shCore', 								$this->constants['plugin_url'].'/assets/graph/scripts/shCore.min.js');
					wp_enqueue_script( $this->constants['plugin_key'].'_admin_shBrushJScript', 						$this->constants['plugin_url'].'/assets/graph/scripts/shBrushJScript.min.js');
					wp_enqueue_script( $this->constants['plugin_key'].'_admin_shBrushXml', 							$this->constants['plugin_url'].'/assets/graph/scripts/shBrushXml.min.js');
					wp_enqueue_script( $this->constants['plugin_key'].'_admin_categoryAxisRenderer', 				$this->constants['plugin_url'].'/assets/graph/scripts/jqplot.categoryAxisRenderer.min.js');
					wp_enqueue_script( $this->constants['plugin_key'].'_admin_jqplot_barRenderer_min', 				$this->constants['plugin_url'].'/assets/graph/scripts/jqplot.barRenderer.min.js');
					wp_enqueue_script( $this->constants['plugin_key'].'_admin_jqplot_pointLabels_min', 				$this->constants['plugin_url'].'/assets/graph/scripts/jqplot.pointLabels.min.js');	
					wp_enqueue_script( $this->constants['plugin_key'].'_admin_jqplot_canvasAxisTickRenderer_min',	$this->constants['plugin_url'].'/assets/graph/scripts/jqplot.canvasAxisTickRenderer.min.js');	
					wp_enqueue_script( $this->constants['plugin_key'].'_admin_jqplot_canvasTextRenderer_min', 		$this->constants['plugin_url'].'/assets/graph/scripts/jqplot.canvasTextRenderer.min.js');	
					wp_enqueue_script( $this->constants['plugin_key'].'_admin_jqplot_dateAxisRenderer_min', 		$this->constants['plugin_url'].'/assets/graph/scripts/jqplot.dateAxisRenderer.min.js');
					wp_enqueue_script( $this->constants['plugin_key'].'_admin_jqplot_pieRenderer_min', 				$this->constants['plugin_url'].'/assets/graph/scripts/jqplot.pieRenderer.min.js');
					wp_enqueue_script( $this->constants['plugin_key'].'_admin_jqplot_donutRenderer_min', 			$this->constants['plugin_url'].'/assets/graph/scripts/jqplot.donutRenderer.min.js');
					*/
					//New Change ID 20140918
					//wp_enqueue_script( $this->constants['plugin_key'].'_admin_jqplot_script', 						$this->constants['plugin_url'].'/assets/js/jqplot.scripts.js');	/*Don't touch this! */
					
					//New Change ID 20150107					
					wp_enqueue_style(  $this->constants['plugin_key'].'_admin_map_ic_00',			$this->constants['plugin_url'].'/assets/map_lib/jquery-jvectormap.css');					
					wp_enqueue_script( $this->constants['plugin_key'].'_admin_map_ic_01', 			$this->constants['plugin_url'].'/assets/map_lib/jquery-jvectormap.js');
					wp_enqueue_script( $this->constants['plugin_key'].'_admin_map_ic_02', 			$this->constants['plugin_url'].'/assets/map_lib/lib/jquery-mousewheel.js');					
					wp_enqueue_script( $this->constants['plugin_key'].'_admin_map_ic_03', 			$this->constants['plugin_url'].'/assets/map_lib/src/jvectormap.js');					
					wp_enqueue_script( $this->constants['plugin_key'].'_admin_map_ic_04', 			$this->constants['plugin_url'].'/assets/map_lib/src/abstract-element.js');
					wp_enqueue_script( $this->constants['plugin_key'].'_admin_map_ic_05', 			$this->constants['plugin_url'].'/assets/map_lib/src/abstract-canvas-element.js');
					wp_enqueue_script( $this->constants['plugin_key'].'_admin_map_ic_06', 			$this->constants['plugin_url'].'/assets/map_lib/src/abstract-shape-element.js');					
					wp_enqueue_script( $this->constants['plugin_key'].'_admin_map_ic_07', 			$this->constants['plugin_url'].'/assets/map_lib/src/svg-element.js');
					wp_enqueue_script( $this->constants['plugin_key'].'_admin_map_ic_08', 			$this->constants['plugin_url'].'/assets/map_lib/src/svg-group-element.js');
					wp_enqueue_script( $this->constants['plugin_key'].'_admin_map_ic_09', 			$this->constants['plugin_url'].'/assets/map_lib/src/svg-canvas-element.js');
					wp_enqueue_script( $this->constants['plugin_key'].'_admin_map_ic_10', 			$this->constants['plugin_url'].'/assets/map_lib/src/svg-shape-element.js');
					wp_enqueue_script( $this->constants['plugin_key'].'_admin_map_ic_11', 			$this->constants['plugin_url'].'/assets/map_lib/src/svg-path-element.js');
					wp_enqueue_script( $this->constants['plugin_key'].'_admin_map_ic_12', 			$this->constants['plugin_url'].'/assets/map_lib/src/svg-circle-element.js');
					wp_enqueue_script( $this->constants['plugin_key'].'_admin_map_ic_13', 			$this->constants['plugin_url'].'/assets/map_lib/src/svg-image-element.js');
					wp_enqueue_script( $this->constants['plugin_key'].'_admin_map_ic_14', 			$this->constants['plugin_url'].'/assets/map_lib/src/svg-text-element.js');					
					wp_enqueue_script( $this->constants['plugin_key'].'_admin_map_ic_15', 			$this->constants['plugin_url'].'/assets/map_lib/src/vml-element.js');
					wp_enqueue_script( $this->constants['plugin_key'].'_admin_map_ic_16', 			$this->constants['plugin_url'].'/assets/map_lib/src/vml-group-element.js');
					wp_enqueue_script( $this->constants['plugin_key'].'_admin_map_ic_17', 			$this->constants['plugin_url'].'/assets/map_lib/src/vml-canvas-element.js');
					wp_enqueue_script( $this->constants['plugin_key'].'_admin_map_ic_18', 			$this->constants['plugin_url'].'/assets/map_lib/src/vml-shape-element.js');
					wp_enqueue_script( $this->constants['plugin_key'].'_admin_map_ic_19', 			$this->constants['plugin_url'].'/assets/map_lib/src/vml-path-element.js');
					wp_enqueue_script( $this->constants['plugin_key'].'_admin_map_ic_20', 			$this->constants['plugin_url'].'/assets/map_lib/src/vml-circle-element.js');
					wp_enqueue_script( $this->constants['plugin_key'].'_admin_map_ic_21', 			$this->constants['plugin_url'].'/assets/map_lib/src/vml-image-element.js');					
					wp_enqueue_script( $this->constants['plugin_key'].'_admin_map_ic_22', 			$this->constants['plugin_url'].'/assets/map_lib/src/map-object.js');
					wp_enqueue_script( $this->constants['plugin_key'].'_admin_map_ic_23', 			$this->constants['plugin_url'].'/assets/map_lib/src/region.js');
					wp_enqueue_script( $this->constants['plugin_key'].'_admin_map_ic_24', 			$this->constants['plugin_url'].'/assets/map_lib/src/marker.js');					
					wp_enqueue_script( $this->constants['plugin_key'].'_admin_map_ic_25', 			$this->constants['plugin_url'].'/assets/map_lib/src/vector-canvas.js');
					wp_enqueue_script( $this->constants['plugin_key'].'_admin_map_ic_26', 			$this->constants['plugin_url'].'/assets/map_lib/src/simple-scale.js');
					wp_enqueue_script( $this->constants['plugin_key'].'_admin_map_ic_27', 			$this->constants['plugin_url'].'/assets/map_lib/src/ordinal-scale.js');
					wp_enqueue_script( $this->constants['plugin_key'].'_admin_map_ic_28', 			$this->constants['plugin_url'].'/assets/map_lib/src/numeric-scale.js');
					wp_enqueue_script( $this->constants['plugin_key'].'_admin_map_ic_29', 			$this->constants['plugin_url'].'/assets/map_lib/src/color-scale.js');
					wp_enqueue_script( $this->constants['plugin_key'].'_admin_map_ic_30', 			$this->constants['plugin_url'].'/assets/map_lib/src/legend.js');
					wp_enqueue_script( $this->constants['plugin_key'].'_admin_map_ic_31', 			$this->constants['plugin_url'].'/assets/map_lib/src/data-series.js');
					wp_enqueue_script( $this->constants['plugin_key'].'_admin_map_ic_32', 			$this->constants['plugin_url'].'/assets/map_lib/src/proj.js');
					wp_enqueue_script( $this->constants['plugin_key'].'_admin_map_ic_33', 			$this->constants['plugin_url'].'/assets/map_lib/src/map.js');
					wp_enqueue_script( $this->constants['plugin_key'].'_admin_map_ic_34', 			$this->constants['plugin_url'].'/assets/map_lib/assets/jquery-jvectormap-world-mill-en.js');
					wp_enqueue_script( $this->constants['plugin_key'].'_admin_map_ic_35', 			$this->constants['plugin_url'].'/assets/js/jquery.map.scripts.js');
					
					/*Start: AM Charts*/
					wp_enqueue_script( $this->constants['plugin_key'].'_admin_amcharts_amcharts', 					$this->constants['plugin_url'].'/assets/amcharts/amcharts.js');
					wp_enqueue_script( $this->constants['plugin_key'].'_admin_amcharts_serial', 					$this->constants['plugin_url'].'/assets/amcharts/serial.js');
					wp_enqueue_script( $this->constants['plugin_key'].'_admin_amcharts_pe', 						$this->constants['plugin_url'].'/assets/amcharts/pie.js');
					wp_enqueue_script( $this->constants['plugin_key'].'_admin_amcharts_export',						$this->constants['plugin_url'].'/assets/amcharts/plugins/export/export.min.js');
					wp_enqueue_script( $this->constants['plugin_key'].'_admin_amcharts_script', 					$this->constants['plugin_url'].'/assets/js/amcharts.scripts.js');
					wp_enqueue_style( '_admin_amcharts_export', 													$this->constants['plugin_url'].'/assets/amcharts/plugins/export/export.css');
					/*End: AM Charts*/
					
					//New Change ID 20141119
					wp_enqueue_script('jquery-ui-datepicker');
					$jquery_version = isset( $wp_scripts->registered['jquery-ui-core']->ver ) ? $wp_scripts->registered['jquery-ui-core']->ver : '1.9.2';
					wp_enqueue_style( 'jquery-ui-style', '//ajax.googleapis.com/ajax/libs/jqueryui/' . $jquery_version . '/themes/smoothness/jquery-ui.css' );				
					wp_enqueue_script( $this->constants['plugin_key'].'_admin_dashboard_summary', 					$this->constants['plugin_url'].'/assets/js/dashboard_summary.js', true);
				}
				
				
				$pages 		= array();				
				$pages[]	=	$this->constants['plugin_key'].'_details_page';
				$pages[]	=	$this->constants['plugin_key'].'_stock_list_page';
				$pages[]	=	$this->constants['plugin_key'].'_variation_stock_page';
				$pages[]	=	$this->constants['plugin_key'].'_report_page';
				$pages[]	=	$this->constants['plugin_key'].'_cross_tab_page';
				$pages[]	=	$this->constants['plugin_key'].'_options_page';
				$pages[]	=	$this->constants['plugin_key'].'_google_analytics_page';
				$pages[]	=	$this->constants['plugin_key'].'_variation_page';
				$pages[]	=	$this->constants['plugin_key'].'_customer_page';
				$pages[]	=	$this->constants['plugin_key'].'_projected_actual_sales_page';
				$pages[]	=	$this->constants['plugin_key'].'_tax_report_page';
				$pages[]	=	$this->constants['plugin_key'].'_guest_report_page';
				
				$pages 	= apply_filters('ic_commerce_premium_golden_script', $pages, $this->constants['plugin_key']);
				
				//$this->print_array($pages);
				
				/*if(	
					$current_page == $this->constants['plugin_key'].'_details_page' 
					|| $current_page == $this->constants['plugin_key'].'_stock_list_page'
					|| $current_page == $this->constants['plugin_key'].'_variation_stock_page'
					|| $current_page == $this->constants['plugin_key'].'_report_page'
					|| $current_page == $this->constants['plugin_key'].'_cross_tab_page'
					|| $current_page == $this->constants['plugin_key'].'_options_page'
					|| $current_page == $this->constants['plugin_key'].'_google_analytics_page'
					|| $current_page == $this->constants['plugin_key'].'_variation_page'
					|| $current_page == $this->constants['plugin_key'].'_customer_page'
					|| $current_page == $this->constants['plugin_key'].'_projected_actual_sales_page'
					|| $current_page == $this->constants['plugin_key'].'_tax_report_page'
					){*/
					
				if(in_array($current_page,$pages)){
					wp_enqueue_script('jquery-ui-datepicker');				
					
					$jquery_version = isset( $wp_scripts->registered['jquery-ui-core']->ver ) ? $wp_scripts->registered['jquery-ui-core']->ver : '1.9.2';
					
					wp_enqueue_style( 'jquery-ui-style', '//ajax.googleapis.com/ajax/libs/jqueryui/' . $jquery_version . '/themes/smoothness/jquery-ui.css' );				
					
					wp_enqueue_script( $this->constants['plugin_key'].'_jquery_collapsible', 			$this->constants['plugin_url'].'/assets/js/jquery.collapsible.js', true);
					
					wp_enqueue_script( $this->constants['plugin_key'].'_admin_details_page', 			$this->constants['plugin_url'].'/assets/js/details_page.js', true);
				
				}				
				
				//New Change ID 20141119
				if($current_page == $this->constants['plugin_key'].'_options_page'){					
					wp_enqueue_script( $this->constants['plugin_key'].'_admin_setting_page', 			$this->constants['plugin_url'].'/assets/js/setting_page.js', true);
				}
			}
			
			public function admin_footer_css(){
				$img = $this->constants['plugin_url']."/assets/";
				$color = isset($this->constants['plugin_options']['theme_color']) ? trim($this->constants['plugin_options']['theme_color']) : $this->constants['color_code'];
				$color = strlen($color) > 0 ? $color : '#77aedb';
				
				$outuput = 
					'<style type="text/css">
						/*.wrap .postbox h3,
						.wrap .collapsible,
						.wrap .page_collapsible,
						.wrap .collapse-open,
						.wrap div.pagination span.current,
						.wrap .search_report_content .widefat thead th,
						.wp-core-ui input[type=reset].onformprocess, 
						.wp-core-ui input[type=submit].onformprocess, 
						.wp-core-ui input[type=button].onformprocess,
						.wp-core-ui .button.onformprocess{
							background-color:'.$color.';
							box-shadow:none;
							color:#fff;
							border:1px solid '.$color.';
						}*/
						
						/*.wrap .postbox h3{border:none;}*/
						
						/*.wrap div.pagination a,
						.wrap div.pagination a:hover, div.pagination a:active,
						.wrap div.pagination span.current,
						.wrap .wp-core-ui input[type=reset].onformprocess, 
						.wrap .wp-core-ui input[type=submit].onformprocess, 
						.wrap .wp-core-ui input[type=button].onformprocess,
						a.onformprocess{
							border:1px solid '.$color.';
						}*/
						
						/*.wp-core-ui input[type=reset].onformprocess:hover, 
						.wp-core-ui input[type=submit].onformprocess:hover, 
						.wp-core-ui input[type=button].onformprocess:hover{
							/*background:url('.$img.'images/button_bg.png) center center repeat '.$color.';*/
							background-color:'.$color.';
						}*/
						
						/*::selection {color:#fff;background:'.$color.';}
						::-moz-selection {color:#fff;background:'.$color.';}
						
						.logo_image img{ 
							border:3px solid '.$color.';
						}*/
						
						/*.popup_box h4{background-color:'.$color.';}*/
						
						/*.nav-tab{background:'.$color.';border:1px solid '.$color.';}
						#menu-icon{background:url('.$img.'images/menu-icon.png) center center repeat '.$color.';}
						h2.nav-tab-wrapper{border-bottom:1px solid '.$color.';}
						.nav-tab:hover, .nav-tab-active, .nav-tab-active:hover{
							background:url('.$img.'images/transparent-bg.png) center center repeat '.$color.';
						}*/
	
					</style>';
				echo $outuput;
				//echo 'background:url('.$img.'images/button_bg.png) center center repeat red;';
			}
			
			
			public function check_parent_plugin(){
				if(!isset($this->constants['plugin_parent'])) return '';
				$message 				= "";
				$msg 					= false;
				$this->plugin_parent 	= $this->constants['plugin_parent'];
				$action = "";
				
				
				$this->constants['plugin_parent_active'] 		=  false;
				$this->constants['plugin_parent_installed'] 	=  false;
				
				if(in_array( $this->plugin_parent['plugin_slug'], apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
					
					$this->constants['plugin_parent_active'] 		=  true;
					$this->constants['plugin_parent_installed'] 	=  true;
					
					//New Change ID 20140918
					$this->constants['parent_plugin_version']	= get_option('woocommerce_version',0);
					$this->constants['parent_plugin_db_version']= get_option('woocommerce_db_version',0);
					
					/*if(!defined('WOO_VERSION'))
					
					if(defined('WC_VERSION')) define('WOO_VERSION', WC_VERSION);else define('WOO_VERSION', '');
					
					if ( version_compare( $this->constants['parent_plugin_db_version'], '2.2.0', '>=' ) || WOO_VERSION == '2.2-bleeding' ) {
						if ( version_compare( $this->constants['parent_plugin_db_version'], '2.2.0', '<' ) || WOO_VERSION == '2.2-bleeding' ) {
							$this->constants['post_order_status_found']	= 0;
						}else{
							$this->constants['post_order_status_found']	= 1;
						}
					}else{
						$this->constants['post_order_status_found']	= 0;
					}*/
					
					
					/*Added 2017-08-04*/
					$this->constants['post_order_status_found']	= apply_filters('ic_commerce_latest_woocommerce_version',1);
		
					return $message;
				}else{
					$this->constants['plugin_parent_active'] =  false;
					if(is_dir(WP_PLUGIN_DIR.'/'.$this->plugin_parent['plugin_folder'] ) ) {
						$message = $this->constants['plugin_parent_installed'] =  true;
					}else{
						$message = $this->constants['plugin_parent_installed'] =  false;
					}
					return  $message;
				}
			}
			
			public function admin_notices(){
				$message 				= NULL;				
				if(!$this->constants['plugin_parent_active']){
					if($this->constants['plugin_parent_installed']){
						$action = esc_url(wp_nonce_url(admin_url('plugins.php?action=activate&plugin='.$this->plugin_parent['plugin_slug'].'&plugin_status=active&paged=1'), 'activate-plugin_'.$this->plugin_parent['plugin_slug']));						
						$msg = '<span>' . sprintf( __($this->constants['plugin_name'].' depends on <a href="%s">'.$this->plugin_parent['plugin_name'].'</a> to work! so please <a href="%s">activate</a> it.' , 'icwoocommerce_textdomains'), $action, $action ) . '</span>';
					}else{
						$action = admin_url( 'plugin-install.php?tab=plugin-information&plugin='.$this->plugin_parent['plugin_folder'].'&TB_iframe=true&width=640&height=800');
						$msg = '<span>' . sprintf( __($this->constants['plugin_name'].' depends on <a href="%s" target="_blank" class="thickbox onclick" title="'.$this->plugin_parent['plugin_name'].'">'.$this->plugin_parent['plugin_name'].'</a> to work!' , 'icwoocommerce_textdomains'),$action) . '</span>';					
					}					
					$message .= '<div class="error">';
					$message .= '<p>'.$msg.'</p>';
					$message .= '</div>';
				}
				echo $message;
			}			
			
			function wp_localize_script($hook) {
				$current_page				= $this->get_request('page');
				$localize_script_data		= array(
													'ajaxurl' 			=> admin_url( 'admin-ajax.php' )
													,'ic_ajax_action' 	=> $this->constants['plugin_key'].'_wp_ajax_action'
													,'first_order_date' => $this->constants['first_order_date']
													,'current_date' 	=> date("Y-m-d")
													,'total_shop_day' 	=> $this->constants['total_shop_day']
													,'defaultOpen' 		=> 'section1'
													,'color_code' 		=> $this->constants['color_code']
													,'admin_page' 		=> $current_page
												);
				
				if($this->constants['plugin_key'].'_page' == $current_page){
					if(function_exists('get_woocommerce_currency_symbol')){
						$currency_symbol	=	get_woocommerce_currency_symbol();
					}else{
						$currency_symbol	=	"$";
					}
					$localize_script_data['currency_symbol'] 	= $currency_symbol;
					$localize_script_data['num_decimals'] 		= get_option( 'woocommerce_price_num_decimals'	,	0		);
					$localize_script_data['currency_pos'] 		= get_option( 'woocommerce_currency_pos'		,	'left'	);
					$localize_script_data['decimal_sep'] 		= get_option( 'woocommerce_price_decimal_sep'	,	'.'		);
					$localize_script_data['thousand_sep'] 		= get_option( 'woocommerce_price_thousand_sep'	,	','		);
					
					//New Graph Settings 20150407
					$localize_script_data['tick_angle'] 		= $this->get_setting('tick_angle',			$this->constants['plugin_options'],0);
					$localize_script_data['tick_font_size'] 	= $this->get_setting('tick_font_size',		$this->constants['plugin_options'],9);
					$localize_script_data['tick_char_length'] 	= $this->get_setting('tick_char_length',	$this->constants['plugin_options'],15);
					$localize_script_data['tick_char_suffix'] 	= $this->get_setting('tick_char_suffix',	$this->constants['plugin_options'],"...");
					$localize_script_data['graph_height'] 		= $this->get_setting('graph_height',		$this->constants['plugin_options'],300);
				}
				
				wp_enqueue_script( $this->constants['plugin_key'].'_ajax-script', $this->constants['plugin_url'].'/assets/js/scripts.js', true);
				wp_localize_script($this->constants['plugin_key'].'_ajax-script', 'ic_ajax_object', $localize_script_data); // setting ajaxurl
				
				if($this->constants['plugin_key'].'_options_page' == $current_page){
					wp_enqueue_media();
					wp_enqueue_script('custom-background');
					//wp_enqueue_style('wp-color-picker');
				}
				
			}
			
			function ic_commerce_save_normal_column($name){
				$key = $this->get_column_key($name);				
				do_action('ic_commerce_premium_golden_save_column',$_POST, $key);
				unset($_POST['do_action_type']);
				unset($_POST['action']);
				unset($_POST['ic_admin_page']);
				update_option($key,$_POST);
				die();
				exit;
			}
			
			function get_column_key($name){
				$page			= $this->get_request('ic_admin_page','report');				
				return $key 	= $page.'_'.$name;
			}
			
			function projected_sales_year(){
				$projected_sales_year			= $this->get_request('projected_sales_year','2000');				
				$projected_sales_year_option 	= $this->constants['plugin_key'].'_projected_amount_'.$projected_sales_year;
				
				$projected_amounts = get_option($projected_sales_year_option,array());
				$return['success'] = 'false';
				$return['projected_amounts'] = array();
				$return['projected_sales_year'] = $projected_sales_year;
				if($projected_amounts){
					$return['success'] 				= 'true';					
					$return['projected_amounts'] 	= $projected_amounts;
				}
				
				echo json_encode($return);
				die;
			}
			
			var $error_on = NULL;
	
			var $log_destination = NULL;
			
			function set_error_on(){
			
				if($this->error_on) return '';
							
				//$plugin_path	= isset($this->constants['plugin_dir']) ? $this->constants['plugin_dir'] : dirname(__FILE__);
				
				//$plugin_path = str_replace("\includes","",$plugin_path);
				//$plugin_path = str_replace("/includes","",$plugin_path);
				
				$error_folder = ABSPATH . '/ic-logerror/';
		
				if (!file_exists($error_folder)) {
					@mkdir($error_folder, 0777, true);
				}
				
				$this->log_destination = $error_folder.'ic_error_'.date("Ymd").'.log';
				
				@ini_set('error_reporting', E_ALL);
				
				@ini_set('log_errors','On');
				
				@ini_set('error_log',$this->log_destination);
				
				$this->error_on = true;
			}
			
			function wp_ajax_action() {
				$this->set_error_on();
				$action	= $this->get_request('action',NULL,false);
				if($action ==  $this->constants['plugin_key'].'_wp_ajax_action'){
				if($this->is_product_active != 1)  return true;	
					if(isset($_REQUEST['do_action_type'])){
						$time_limit = apply_filters("ic_commerce_maximum_execution_time",300,$_REQUEST['do_action_type']);
						set_time_limit($time_limit);//set_time_limit — Limits the maximum execution time
					}
										
					$do_action_type	= $this->get_request('do_action_type',NULL,false);
					//$this->print_array($_REQUEST);
					
					if($do_action_type){
						$this->define_constant();
						$c	= $this->constants;
						
						do_action('ic_commerce_premium_golden_ajax_action',$this->constants, $do_action_type);
						
						
						if($do_action_type == "email_report_actions_order_status_mail"){
							require_once('ic_commerce_premium_golden_schedule_mailing_sales_status.php');
							$ic_commerce 									= new IC_Commerce_Premium_Golden_Schedule_Mailing_Sales_Status( $this->constants['plugin_file'], $c);
							$ic_commerce->ajax_schedule_event();
							die;
						}
						
						if($do_action_type == "email_report_actions_order_dashboard_email"){
							require_once('ic_commerce_premium_golden_schedule_mailing_dashboard_report.php');
							$ic_commerce 									= new IC_Commerce_Golden_Schedule_Premium_Mailing_Dashboard_Report( $this->constants['plugin_file'], $c);
							$ic_commerce->ajax_schedule_event();
							die;
						}
						
						
						
						if($do_action_type == "projected_actual_sales_page"){	
							include_once('ic_commerce_premium_golden_projected_actual_sales.php');
							$intence = new IC_Commerce_Premium_Golden_Projected_Actual_Sales($c);
							$intence->ic_commerce_ajax_request('limit_row');
						}
						
						if($do_action_type == "tax_report_page" || $do_action_type == "tax_report_page_for_print"){
							include_once('ic_commerce_premium_golden_tax_report.php');
							$intence = new IC_Commerce_Premium_Golden_Tax_report($c);
							
							if($do_action_type == "tax_report_page")
								$intence->ic_commerce_ajax_request('limit_row');
							else if($do_action_type == "tax_report_page_for_print"){
								$intence->ic_commerce_ajax_request('all_row');
							}
						}
						
						if($do_action_type == "projected_sales_year"){	
							$this->projected_sales_year();
						}
						
						if($do_action_type == "map_details"){
							//$this->print_array($_REQUEST);							
							$shop_order_status		= apply_filters('ic_commerce_dashboard_page_default_order_status',$this->get_set_status_ids(),$this->constants);	
							$hide_order_status 		= apply_filters('ic_commerce_dashboard_page_default_hide_order_status',$this->constants['hide_order_status'],$this->constants);
							$start_date 			= apply_filters('ic_commerce_dashboard_page_default_start_date',$this->constants['start_date'],$this->constants);
							$end_date 				= apply_filters('ic_commerce_dashboard_page_default_end_date',$this->constants['end_date'],$this->constants);
							include_once( 'ic_commerce_premium_golden_map.php');
							$class_object = new IC_Commerce_Premium_Golden_Map($c);
							$class_object->get_country_list($shop_order_status,$hide_order_status,$start_date,$end_date);
						}
						
						if($do_action_type == "save_normal_column" || $do_action_type == "save_detail_column"){
							$this->ic_commerce_save_normal_column($do_action_type);
							die;
						}
						
						if($do_action_type == "graph"){
							include_once( 'ic_commerce_premium_golden_ajax_graph.php');
							$IC_Commerce_Premium_Golden_Ajax_Graph = new IC_Commerce_Premium_Golden_Ajax_Graph($c);
						}
						
						if($do_action_type == "stock_page"){
							include_once('ic_commerce_premium_golden_stock_list.php');
							$intence = new IC_Commerce_Premium_Golden_Stock_List_report($c);
							$intence->get_product_list();
							die;
						}
						
						if($do_action_type == "variation_stock_page"){
							include_once('ic_commerce_premium_golden_variation_stock_list.php');
							$intence = new IC_Commerce_Premium_Golden_Variation_Stock_List_report($c);
							$intence->get_product_list();
							die;
						}
						
						
						
						
						if($do_action_type == "report_page" || $do_action_type == "all_report_page_for_print"){
							
							include_once('ic_commerce_premium_golden_all_report.php');
							$intence = new IC_Commerce_Premium_Golden_All_Report($c);
							if($do_action_type == "report_page")
								$intence->ic_commerce_report_ajax_request('limit_row');
							else if($do_action_type == "all_report_page_for_print")
								$intence->ic_commerce_report_ajax_request('all_row');
						}
						//$this->print_array($_REQUEST);
						if($do_action_type == "guest_reports" || $do_action_type == "all_guest_report_for_print"){
							$this->print_array($_REQUEST);
							include_once('ic_commerce_premium_golden_guest_report.php');
							$intence = new IC_Commerce_Premium_Golden_Guest_Report($c);
							if($do_action_type == "guest_reports")
								$intence->all_guest_report_for_print('limit_row');
							else if($do_action_type == "all_report_page_for_print")
								$intence->ic_commerce_report_ajax_request('all_row');
						}
						
						if($do_action_type == "variation_page" || $do_action_type == "variation_page_for_print"){
							
							include_once('ic_commerce_premium_golden_variation.php');
							$intence = new IC_Commerce_Premium_Golden_Variation($c);
							if($do_action_type == "variation_page")
								$intence->ic_commerce_report_ajax_request('limit_row');
							else if($do_action_type == "variation_page_for_print")
								$intence->ic_commerce_report_ajax_request('all_row');
						}						
						
						if($do_action_type == "cross_tab_page"
							|| $do_action_type == "cross_tab_for_print"){
								//$this->print_array($_REQUEST);			
							include_once('ic_commerce_premium_golden_cross_tab.php');
							$intence = new IC_Commerce_Premium_Golden_Cross_Tab($c);
							//echo $do_action_type;
							if($do_action_type == "cross_tab_page")
								$intence->ic_commerce_ajax_request('limit_row');
							else if($do_action_type == "cross_tab_for_print")
								$intence->ic_commerce_ajax_request('all_row');
						}
						
						if($do_action_type == "google_analytics" || $do_action_type == "google_analytics_for_print"){	
							include_once('ic_commerce_premium_golden_google_analytics.php');
							$intence = new IC_Commerce_Premium_Golden_Google_Analytics($c);
							
							if($do_action_type == "google_analytics")
								$intence->ic_commerce_ajax_request('limit_row');
							else if($do_action_type == "google_analytics_for_print")
								$intence->ic_commerce_ajax_request('all_row');
						}
						
						if(
								$do_action_type == "save_normal_column" 
							|| $do_action_type == "save_detail_column" 
							|| $do_action_type == "product" 
							|| $do_action_type == "detail_page"
							|| $do_action_type == "customer_page"
							|| $do_action_type == "customer_page_for_print"
							|| $do_action_type == "detail_page_for_print"){
								
							
							include_once('ic_commerce_premium_golden_custom_report.php');
							$intence = new IC_Commerce_Premium_Golden_Detail_report($c);
							
							if($do_action_type == "save_normal_column" || $do_action_type == "save_detail_column")
								$intence->ic_commerce_save_normal_column($do_action_type);
							else if($do_action_type == "product")
								$intence->product_by_category_ajax_request();
							else if($do_action_type == "detail_page")
								$intence->ic_commerce_custom_admin_report_ajax_request('limit_row');								
							else if($do_action_type == "detail_page_for_print")
								$intence->ic_commerce_custom_admin_report_ajax_request('all_row');
						}
						
						
						if($do_action_type == "check_cogs_exits"){
							include_once('ic_commerce_premium_golden_plugin_settings.php');
							$intence = new IC_Commerce_Premium_Golden_Settings($c);
							$intence->check_cogs_exits();
							die;
						}
						
						
						
					}
				}
				die(); // this is required to return a proper result
				exit;
			}
			
	}
}
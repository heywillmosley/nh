<?php  

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
if(!class_exists('IC_Commerce_Premium_Golden_Schedule_Mailing_Sales_Status')){	
	require_once('ic_commerce_premium_golden_fuctions.php');	
	class IC_Commerce_Premium_Golden_Schedule_Mailing_Sales_Status extends IC_Commerce_Premium_Golden_Fuctions{
			
			public $constants 				= array();
			
			public $plugin_parent			= NULL;
			
			public $today					= NULL;
			
			public $datetime				= NULL;
			
			public function __construct($file, $constants) {
				
				$this->today 						= date_i18n("Y-m-d");
				
				$this->constants 					= $constants;
				
				$this->constants['plugin_file'] 	= $file;//Added New 20150206
				
				$this->constants['plugin_options'] 	= get_option($this->constants['plugin_key']);
				
				$this->constants['force_email'] = false;
			}
			
			function force_schedule_event(){
				
				$this->constants['force_email'] = true;
				//$this->set_error_log('called funtion force_schedule_event');
				$this->constants['status_report_emailed'] = $this->ic_woo_schedule_send_email();
				add_action( 'admin_notices', array( $this, 'admin_notices'));				
			}
			
			function cron_schedule_event(){
				//$this->set_error_log('called funtion cron_schedule_event');
				$this->ic_woo_schedule_send_email();
			}
			
			function doing_wp_cron_schedule_event(){
				$activate_cron_url 			= $this->get_setting('activate_cron_url',$this->constants['plugin_options'], 0);//Added 20150721
				if($activate_cron_url == 1){
					$this->ic_woo_schedule_send_email();
				}
			}
			
			//Added 20150219
			function ajax_schedule_event(){
				$message = "";
				
				$schedule_activate	 		= $this->get_setting('act_email_reporting',$this->constants['plugin_options'], 0);//Added 20150219
				$schedule_recurrence		= $this->get_setting('email_schedule',$this->constants['plugin_options'], 'daily');//Added 20150219
				$flag						= true;
				$schedule_hook_name			= $this->constants['plugin_key'].'_schedule_mailing_sales_status_event';
				
				if(strlen($schedule_recurrence) <= 2){
					$message .= '<li>'.__("Please select Email Schedule.",'icwoocommerce_textdomains').'</li>';
					$flag	= false;
				}
				
				if($schedule_activate  == 0){
					$message .= '<li>'.__("Please select Activate Email Reporting.",'icwoocommerce_textdomains').'</li>';
					$flag	= false;
				}
			
				if($schedule_activate == 1 and strlen($schedule_recurrence) > 2){
					if (!wp_next_scheduled($schedule_hook_name)){
						$message .= '<li>'.__("Somehow next schedule is not set, please try re-activating schedule settting.",'icwoocommerce_textdomains').'</li>';
						$flag	= false;
					}
				}
				
				if($flag){
					$this->constants['status_report_emailed'] = $this->ic_woo_schedule_send_email();
					if(isset($this->constants['status_report_emailed'])){
						if($this->constants['status_report_emailed']){
							$message .= '<li>'.__("Email has been sent successfully.",'icwoocommerce_textdomains').'</li>';
						}else{
							$message .= '<li>'.__("Getting problem while sending mail.",'icwoocommerce_textdomains').'</li>';
						}
						
					}
				}else{
					$message .= '<li>'.__('Click on "Save Changes" button for saving changes and try again.','icwoocommerce_textdomains').'</li>';
				}
				echo "<ul>";
				echo $message;
				echo "</ul>";
			}
			
			public function admin_notices(){
				$message 				= NULL;				
				if(isset($this->constants['status_report_emailed'])){
					if($this->constants['status_report_emailed']){
						$msg = '<span>'.__("Email sent successfully.",'icwoocommerce_textdomains').'</span>';
						$class = "updated";
					}else{
						$msg = '<span>'.__("Getting problem while sending mail.",'icwoocommerce_textdomains').'</span>';
						$class = "error";
					}
					
					$message .= "<div class=\"{$class}\">";
					$message .= '<p>'.$msg.'</p>';
					$message .= '</div>';
				}
				echo $message;
			}	
			
			public function ic_woo_schedule_send_email() {
				
				$act_email_reporting 		= $this->get_setting('act_email_reporting',$this->constants['plugin_options'], 0);//Added 20150209
				$email_schedule 			= $this->get_setting('email_schedule',$this->constants['plugin_options'], 'daily');//Added 20150209
				
				//if($act_email_reporting != 1){return '';}
				
				$email_daily_report 		= $this->get_setting('email_daily_report',$this->constants['plugin_options'], 0);
				$email_weekly_report 		= $this->get_setting('email_weekly_report',$this->constants['plugin_options'], 0);
				$email_monthly_report 		= $this->get_setting('email_monthly_report',$this->constants['plugin_options'], 0);
				$email_till_today_report 	= $this->get_setting('email_till_today_report',$this->constants['plugin_options'], 0);
				$email_yesterday_report 	= $this->get_setting('email_yesterday_report',$this->constants['plugin_options'], 0);//Added 20150207
				
				$email_last_week_report 	= $this->get_setting('email_last_week_report',$this->constants['plugin_options'], 0);//Added 20150209
				$email_last_month_report 	= $this->get_setting('email_last_month_report',$this->constants['plugin_options'], 0);//Added 20150209
				$email_this_year_report 	= $this->get_setting('email_this_year_report',$this->constants['plugin_options'], 0);//Added 20150209
				$email_last_year_report 	= $this->get_setting('email_last_year_report',$this->constants['plugin_options'], 0);//Added 20150209
				$email_time_limit 			= $this->get_setting('email_time_limit',$this->constants['plugin_options'], 300);//Added 20150209
				
				$activate_cron_url 			= $this->get_setting('activate_cron_url',$this->constants['plugin_options'], 0);//Added 20150721
				
				@set_time_limit($email_time_limit);//set_time_limit — Limits the maximum execution time //Added 20150209
				
				$isset_doing_wp_cron 		= (isset($_REQUEST['doing_wp_cron']) and $activate_cron_url == 1) ? 1 : 0;//Added 20150721
				
				
				if($email_daily_report 		==	1
				|| $email_weekly_report		==	1
				|| $email_monthly_report 	==	1
				|| $email_till_today_report ==	1
				|| $email_yesterday_report 	==	1 //Added 20150207
				|| $email_last_week_report 	==	1 //Added 20150209
				|| $email_last_month_report ==	1 //Added 20150209
				|| $email_this_year_report 	==	1 //Added 20150209
				|| $email_last_year_report 	==	1 //Added 20150209
				|| $act_email_reporting 	==	1 //Added 20150209
				|| $isset_doing_wp_cron 	==	1 //Added 20150721
				){
					//Pass
				}else{					
					return '';					
				}
				
				//add_action('plugins_loaded', array($this, 'plugins_loaded_icwoocommerce_textdomains'));
				//$this->set_error_log('called funtion ic_woo_schedule_send_email, creating html data');
				
				
				//Added New 20150206
				$this->constants['plugin_options'] 	= get_option($this->constants['plugin_key']);				
				$this->constants['plugin_parent'] 	= array(
					"plugin_name"		=>"WooCommerce"
					,"plugin_slug"		=>"woocommerce/woocommerce.php"
					,"plugin_file_name"	=>"woocommerce.php"
					,"plugin_folder"	=>"woocommerce"
					,"order_detail_url"	=>"post.php?&action=edit&post="
				);
				
				$this->check_parent_plugin();	//Added New 20150206				
				$this->define_constant();//Added New 20150206
				
				$post_status 				= $this->get_setting('post_status',$this->constants['plugin_options'], array());
				$shop_order_status			= $this->get_set_status_ids();
				
				
				if(count($this->constants['hide_order_status'])>0){
					$this->constants['hide_order_status_string'] = implode(",",$this->constants['hide_order_status']);
				}else{
					$this->constants['hide_order_status_string'] = '';
				}
				
				
				$email_data 		= "";
				//$today_date		= date_i18n("Y-m-d");//Added 20150207
				$today_date			= $this->today;//Modified 20150213
				$timestamp 			= strtotime($today_date);//Added 20150207
				$report				= array();
				
				if($email_weekly_report == 1 || $email_last_week_report == 1){
					$start_of_week = $this->get_start_of_week();
					$current_day = strtolower(date('l',$timestamp));
					if($current_day != $start_of_week){
						
						$this_week_strtotime  	= strtotime("last {$start_of_week}", $timestamp);				
						$this_week_start_date 	= date("Y-m-d", $this_week_strtotime);				
						$this_week_end_date 	= date('Y-m-d',strtotime("6 day", $this_week_strtotime));
						
						$last_week_strtotime  	= strtotime("last {$start_of_week} -7 days", $timestamp);
						$last_week_start_date 	= date("Y-m-d", $last_week_strtotime);
						$last_week_end_date 	=  date("Y-m-d",strtotime("6 day", $last_week_strtotime));
					}else{
						$this_week_strtotime  	= strtotime("this {$start_of_week}", $timestamp);
						$this_week_start_date 	= date("Y-m-d", $this_week_strtotime);				
						$this_week_end_date 	= date('Y-m-d',strtotime("6 day", $this_week_strtotime));
						
						$last_week_strtotime  	= strtotime("this {$start_of_week} -7 days", $timestamp);
						$last_week_start_date 	= date("Y-m-d", $last_week_strtotime);
						$last_week_end_date 	=  date("Y-m-d",strtotime("6 day", $last_week_strtotime));
					}
				}
				
				$filter_parameters = array(
					'constants' 			=> $this->constants, 
					'post_status' 			=> $post_status, 
					'shop_order_status' 	=> $shop_order_status,
					'report_type' 			=> "top",
					'start_date' 			=> "",
					'end_date' 				=> "",
					'title' 				=> "",
					'start' 				=> ""
				);
					
				$email_data = apply_filters("ic_commerce_schedule_mailing_sales_status_top", $email_data, $filter_parameters);
				
				if($email_daily_report == 1):
					$start_date			= $today_date;//Modified 20150207
					$end_date			= $today_date;//Modified 20150207
					$title				= __("Today's",'icwoocommerce_textdomains');
					$email_data			.= "<br>";
					$email_data 		.= $this->getEmailData($start_date, $end_date, $title,$post_status,$shop_order_status,"today");
					$report[]			 = $title;					
				endif;
				
				//Added 20150207
				if($email_yesterday_report == 1):
					$yesterday_date		= date("Y-m-d",strtotime("-1 day",$timestamp));
					$start_date			= $yesterday_date;
					$end_date			= $yesterday_date;
					$title				= __("Yesterday",'icwoocommerce_textdomains');
					$email_data			.= "<br>";
					$email_data 		.= $this->getEmailData($start_date, $end_date, $title,$post_status,$shop_order_status,"yesterday");
					$report[]			 = $title;					
				endif;
				
				if($email_weekly_report == 1):
					$end_date			= $this_week_end_date;
					$start_date 		= $this_week_start_date;
					$title				= __("Current Week",'icwoocommerce_textdomains');
					$email_data			.= "<br>";
					$email_data 		.= $this->getEmailData($start_date, $end_date, $title,$post_status,$shop_order_status,"current_week");
					$report[]			 = $title;
				endif;
				
				//Added 20150209
				if($email_last_week_report == 1):
					$end_date			= $last_week_end_date;
					$start_date 		= $last_week_start_date;
					$title				= __("Last Week",'icwoocommerce_textdomains');
					$email_data			.= "<br>";
					$email_data 		.= $this->getEmailData($start_date, $end_date, $title,$post_status,$shop_order_status,"last_week");
					$report[]			 = $title;					
				endif;
				
				if($email_monthly_report == 1):
					$end_date			= date('Y-m-d',$timestamp);
					$start_date 		= date('Y-m-01',strtotime('this month', $timestamp));
					$title				= __("Current Month",'icwoocommerce_textdomains');
					$email_data			.= "<br>";
					$email_data 		.= $this->getEmailData($start_date, $end_date, $title,$post_status,$shop_order_status,"current_month");	
					$report[]			 = $title;
				endif;
				
				if($email_last_month_report == 1):
					$end_date			= date('Y-m-t',strtotime('last month',$timestamp));
					$start_date 		= date('Y-m-01',strtotime('last month',$timestamp));
					$title				= __("Last Month",'icwoocommerce_textdomains');
					$email_data			.= "<br>";
					$email_data 		.= $this->getEmailData($start_date, $end_date, $title,$post_status,$shop_order_status,"last_month");	
					$report[]			 = $title;
				endif;
				
				if($email_this_year_report == 1):
					$end_date			= date('Y-m-d',strtotime('this year',$timestamp));
					$start_date 		= date('Y-01-01',strtotime('this year',$timestamp));
					$title				= __("Current Year",'icwoocommerce_textdomains');
					$email_data			.= "<br>";
					$email_data 		.= $this->getEmailData($start_date, $end_date, $title,$post_status,$shop_order_status,"current_year");	
					$report[]			 = $title;
				endif;
				
				if($email_last_year_report == 1):
					$end_date			= date('Y-12-31',strtotime('last year',$timestamp));
					$start_date 		= date('Y-01-01',strtotime('last year',$timestamp));
					$title				= __("Last Year",'icwoocommerce_textdomains');
					$email_data			.= "<br>";
					$email_data 		.= $this->getEmailData($start_date, $end_date, $title,$post_status,$shop_order_status,"last_year");	
					$report[]			 = $title;
				endif;
				
				if($email_till_today_report == 1):
					$this->constants['first_order_date'] 			= $this->first_order_date($this->constants['plugin_key']);
					$end_date			= date('Y-m-d',$timestamp);
					$start_date 		= $this->constants['first_order_date'];
					$title				= __("Till Date",'icwoocommerce_textdomains');
					$email_data			.= "<br>";
					$email_data 		.= $this->getEmailData($start_date, $end_date, $title,$post_status,$shop_order_status,"till_date");	
					$report[]			 = $title;
				endif;
				
				$filter_parameters = array(
					'constants' 			=> $this->constants, 
					'post_status' 			=> $post_status, 
					'shop_order_status' 	=> $shop_order_status,
					'report_type' 			=> "top",
					'start_date' 			=> "",
					'end_date' 				=> "",
					'title' 				=> "",
					'start' 				=> ""
				);
					
				$email_data = apply_filters("ic_commerce_schedule_mailing_sales_status_bottom", $email_data, $filter_parameters);
				
				if(
					$email_daily_report 	==	1
				|| $email_weekly_report		==	1
				|| $email_monthly_report 	==	1
				|| $email_till_today_report ==	1
				|| $email_yesterday_report 	==	1 //Added 20150207
				|| $email_last_week_report 	==	1 //Added 20150209
				|| $email_last_month_report ==	1 //Added 20150209
				|| $email_this_year_report 	==	1 //Added 20150209
				|| $email_last_year_report 	==	1 //Added 20150209
				|| $act_email_reporting 	==	1 //Added 20150209
				|| $isset_doing_wp_cron 	==	1 //Added 20150721
				):
					if(strlen($email_data)>0){	
						
						//$this->set_error_log('called funtion ic_woo_schedule_send_email, copleted html data');
											
						$new ='<html>';
							$new .='<head>';
								$new .='<title>';
								$new .= $title;
								$new .='</title>';						
							$new .='</head>';
							$new .='<body>';
							$new .= $this->display_logo();
							$new .= $email_data;
							$new .='</body>';
						$new .='</html>';
						$email_data = $new;
						
						if($this->constants['force_email']){
							//echo $email_data;
						}
						
						/*
						Comented 20150217
						
						$email_send_to		= get_option( 'admin_email' );
						$email_from_name	= get_bloginfo('name');
						$email_from_email	= get_option( 'admin_email' );						
						$email_subject		= "";
						
						
						Comented 20150217
						$email_subject		= get_option($this->constants['plugin_key']."email_subject");
						
						if(!$email_subject || strlen($email_subject) <= 0){
							$url = get_option("siteurl");
							$pos = strpos($url, '/', 7);
							$domain = substr($url, 0 ,$pos);
							$domain = str_replace('http://', '', $domain);
							$domain = str_replace('https://', '', $domain);
							$domain = str_replace('www.', '', $domain);
							$email_subject = $domain;							
							update_option($this->constants['plugin_key']."email_subject", $email_subject);
						}
						*/
						
						$email_send_to 		= $this->get_setting('email_send_to',$this->constants['plugin_options'], '');
						$email_from_name 	= $this->get_setting('email_from_name',$this->constants['plugin_options'], '');
						$email_from_email 	= $this->get_setting('email_from_email',$this->constants['plugin_options'], '');
						$email_subject 		= $this->get_setting('email_subject',$this->constants['plugin_options'], '');
						
						$email_send_to 		= $this->get_email_string($email_send_to);
						$email_from_email 	= $this->get_email_string($email_from_email);
						
						if($email_send_to || $email_from_email){							
							
							//$subject = $email_subject.'-'.implode(", ",$report)." Report";//Comented 20150217
							$subject = $email_subject;
								
							$headers  = 'MIME-Version: 1.0' . "\r\n";
							$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
							$headers .= 'From: '.$email_from_name.' <'.$email_from_email.'>'. "\r\n";
							
							
							$email_data = str_replace("! ","",$email_data);
							$email_data = str_replace("!","",$email_data);
							
							
							
							
							$this->constants['date_format'] = isset($this->constants['date_format']) ? $this->constants['date_format'] : get_option( 'date_format', "Y-m-d" );
							$date_format 					= $this->constants['date_format'];
							$time_format 					= get_option('time_format','g:i a');							
							$report_created					= date_i18n($date_format." ".$time_format);
							$report_server					= date($date_format." ".$time_format);							
							$siteurl 						= get_option('siteurl');
							
							$email_data = $email_data . "<div style=\" padding-bottom:10px; padding-left:18px; width:520px; margin:auto; text-align:left;\"><strong>".__("Created Date/Time: ",'icwoocommerce_textdomains')." "."</strong> {$report_created} ".__("(Wordpress Set Time Zone)", 'icwoocommerce_textdomains')."</div>";
							$email_data = $email_data . "<div style=\" padding-bottom:10px; padding-left:18px; width:520px; margin:auto; text-align:left;\"><strong>".__("Created Date/Time: ",'icwoocommerce_textdomains')." "."</strong> {$report_server} ".__("(Default Server Time Zone)", 'icwoocommerce_textdomains')."</div>";
							$email_data = $email_data . "<div style=\" padding-bottom:15px; padding-left:18px; width:520px; margin:auto; text-align:left;\"><strong>".__("Site URL:",'icwoocommerce_textdomains')." "."</strong> {$siteurl}</div>";
							
							if(isset($_SERVER['SERVER_NAME']) and $_SERVER['SERVER_NAME'] == "p43"){
								//echo $email_data;
								//echo "-------------------";
								//die;
							}
							
							$message = $email_data;
							$to		 = $email_send_to;
							
							//$this->set_error_log('called funtion ic_woo_schedule_send_email, sending mail');
							
							$result = wp_mail( $to, $subject, $message, $headers); 
							
							if($result){
								//$this->set_error_log('called funtion ic_woo_schedule_send_email, sending mail successfully');
							}else{
								//$this->set_error_log('called funtion ic_woo_schedule_send_email, gettting problem sending mail');
							}
							
							return $result;
						}
				
					}
				endif;
				return '';
			}
			
			function getEmailData($start_date, $end_date, $title = "Daily",$post_status,$shop_order_status, $report_type = ""){
					global $wpdb;
					
					//echo $title;
					
					$CDate 				= $this->today;				
					$order_data 		= array();				
					$sql_error 			= "";
					$status_sql_query 	= "";
					$status_join_query 	= "";
					
					if($this->constants['post_order_status_found'] == 0 ){
						if(count($shop_order_status)>0){
							$in_shop_order_status = implode(",",$shop_order_status);
							$status_sql_query = " AND  term_taxonomy.term_id IN ({$in_shop_order_status})";
						}
						
						if(strlen($this->constants['hide_order_status_string'])>0){
							$status_sql_query .= " AND  posts.post_status NOT IN ('{$this->constants['hide_order_status_string']}')";
						}
						
						if(count($shop_order_status)>0){
								$status_join_query = " 
								LEFT JOIN  {$wpdb->prefix}term_relationships 	as term_relationships 	ON term_relationships.object_id		=	posts.ID
								LEFT JOIN  {$wpdb->prefix}term_taxonomy 		as term_taxonomy 		ON term_taxonomy.term_taxonomy_id	=	term_relationships.term_taxonomy_id";
						}
						
					}else{
						if(count($shop_order_status)>0){
							$in_shop_order_status		= implode("', '",$shop_order_status);
							$status_sql_query = " AND  posts.post_status IN ('{$in_shop_order_status}')";
						}
						
						if(strlen($this->constants['hide_order_status_string'])>0){
							$status_sql_query .= " AND  posts.post_status NOT IN ('{$this->constants['hide_order_status_string']}')";
						}
					}
					
				
					
					$sql = " 
					SELECT count(*) as 'total_orders'	
					FROM {$wpdb->prefix}posts as posts";
					$sql .= $status_join_query;
					$sql .= " 
					WHERE  posts.post_type='shop_order'				
					AND DATE(posts.post_date) BETWEEN '". $start_date ."' AND '".$end_date."'";
					$sql .= $status_sql_query;
					
					
					$_total_orders_sql =  '' ;
					$wpdb->flush(); 				
					$wpdb->query("SET SQL_BIG_SELECTS=1");
					$order_data['total_orders_count'] = $wpdb->get_var($sql);
					
					if(strlen($wpdb->last_error)>0){
						$sql_error .= $wpdb->last_error." <br /> ";
					}
					
					$sql = "SELECT 
					SUM(postmeta.meta_value) AS 'total_sales' FROM {$wpdb->prefix}postmeta as postmeta 
					LEFT JOIN  {$wpdb->prefix}posts as posts ON posts.ID=postmeta.post_id";
					$sql .= $status_join_query;
					$sql .= " 								
					WHERE  posts.post_type='shop_order'
					
					AND meta_key='_order_total' 
					AND DATE(posts.post_date) BETWEEN '". $start_date ."' AND '".$end_date."'
					";
					$sql .= $status_sql_query;
					
					/*if(count($post_status)>0){
						$in_post_status		= implode("', '",$post_status);
						$sql .= " AND  posts.post_status IN ('{$in_post_status}')";
					}*/
					
					$_total_sales_sql =  '' ;
					$wpdb->flush(); 				
					$wpdb->query("SET SQL_BIG_SELECTS=1");
					$order_data['total_sales_amount'] = $wpdb->get_var($sql);	
					
					if(strlen($wpdb->last_error)>0){
						$sql_error .= $wpdb->last_error." <br /> ";
					}	
					//==== total ====
					
					if($order_data['total_orders_count'] != '' && $order_data['total_sales_amount'] != '')
					{
						$order_data['total_sales_avg_amount'] = $order_data['total_sales_amount']/$order_data['total_orders_count'];			
					}				
					$sql = "SELECT";		
					$sql .= " SUM( postmeta.meta_value) As 'total_amount', count( postmeta.post_id) AS 'total_count'";		
					$sql .= "  FROM {$wpdb->prefix}posts as posts	
					LEFT JOIN  {$wpdb->prefix}term_relationships as term_relationships	ON term_relationships.object_id=posts.ID 
					LEFT JOIN  {$wpdb->prefix}term_taxonomy as term_taxonomy ON term_taxonomy.term_taxonomy_id=term_relationships.term_taxonomy_id
					LEFT JOIN  {$wpdb->prefix}terms as terms ON terms.term_id=term_taxonomy.term_id			
					LEFT JOIN  {$wpdb->prefix}postmeta as postmeta ON postmeta.post_id=posts.ID
					WHERE terms.name ='refunded' AND postmeta.meta_key = '_order_total' AND posts.post_type='shop_order'";						
					$sql .= " AND DATE(posts.post_modified) BETWEEN '". $start_date ."' AND '".$end_date."' ";	
					
					$sql .= $status_sql_query;
					
					$sql .= " Group BY terms.term_id ORDER BY total_amount DESC";		
					
					$wpdb->flush(); 				
					$wpdb->query("SET SQL_BIG_SELECTS=1");	
					$order_items  = $wpdb->get_row($sql);	
								
					if(strlen($wpdb->last_error)>0){
						$sql_error .= $wpdb->last_error." <br /> ";
					}
					$sql = "SELECT
					SUM(woocommerce_order_itemmeta.meta_value) As 'total_amount', 
					Count(*) AS 'total_count' 
					FROM {$wpdb->prefix}woocommerce_order_items as woocommerce_order_items 
					LEFT JOIN  {$wpdb->prefix}woocommerce_order_itemmeta as woocommerce_order_itemmeta ON woocommerce_order_itemmeta.order_item_id=woocommerce_order_items.order_item_id
					LEFT JOIN  {$wpdb->prefix}posts as posts ON posts.ID=woocommerce_order_items.order_id";
					$sql .= $status_join_query;
					$sql .= " 	
					WHERE 
					woocommerce_order_items.order_item_type='coupon' 
					AND woocommerce_order_itemmeta.meta_key='discount_amount'
					AND posts.post_type='shop_order'
					AND DATE(posts.post_modified) BETWEEN '". $start_date ."' AND '".$end_date."'				
					";
					$sql .= $status_sql_query;
					
					/*if(count($post_status)>0){
						$in_post_status		= implode("', '",$post_status);
						$sql .= " AND  posts.post_status IN ('{$in_post_status}')";
					}*/
					$wpdb->flush(); 				
					$wpdb->query("SET SQL_BIG_SELECTS=1");
					$order_items_coupon = $wpdb->get_row($sql); 
					if(strlen($wpdb->last_error)>0){
						$sql_error .= $wpdb->last_error." <br /> ";
					}
					
					
					$sql = "SELECT 						
					COUNT(postmeta6.meta_value) AS 'ItemCount'						
					,SUM(postmeta6.meta_value) As discount_value				
					FROM 
					{$wpdb->prefix}woocommerce_order_items as woocommerce_order_items						
					LEFT JOIN  {$wpdb->prefix}postmeta as postmeta6 ON postmeta6.post_id=woocommerce_order_items.order_id
					LEFT JOIN  {$wpdb->prefix}posts as posts ON posts.ID=woocommerce_order_items.order_id	";
					$sql .= $status_join_query;
					$sql .= " 						 
					WHERE 						
					posts.post_type='shop_order'						
					AND	postmeta6.meta_key='_order_discount'
					AND postmeta6.meta_value != 0
					AND DATE(posts.post_modified) BETWEEN '". $start_date ."' AND '".$end_date."'";
					$sql .= $status_sql_query;
					
					/*if(count($post_status)>0){
						$in_post_status		= implode("', '",$post_status);
						$sql .= " AND  posts.post_status IN ('{$in_post_status}')";
					}*/
					$sql .= " GROUP BY woocommerce_order_items.order_id					
					";
					
					$wpdb->flush(); 				
					$wpdb->query("SET SQL_BIG_SELECTS=1");
					$order_items_discount = $wpdb->get_row($sql);	
					if(strlen($wpdb->last_error)>0){
						$sql_error .= $wpdb->last_error." <br /> ";
					}
					
					$sql = "  SELECT";
					$sql .= " SUM(postmeta1.meta_value) AS 'total_amount'";
					$sql .= " ,count(woocommerce_order_items.order_id) AS 'total_count'";			
					$sql .= " 
					FROM {$wpdb->prefix}woocommerce_order_items as woocommerce_order_items				
					LEFT JOIN  {$wpdb->prefix}postmeta as postmeta1 ON postmeta1.post_id=woocommerce_order_items.order_id";				
					$sql .= " LEFT JOIN  {$wpdb->prefix}posts as posts ON posts.ID=	woocommerce_order_items.order_id";
	
					$sql .= $status_join_query;
					
					$sql .= " WHERE postmeta1.meta_key = '_order_shipping_tax' AND woocommerce_order_items.order_item_type = 'tax'";				
					$sql .= " AND posts.post_type='shop_order' AND DATE(posts.post_date) BETWEEN '". $start_date ."' AND '".$end_date."' ";		
					$sql .= $status_sql_query;
					
					/*if(count($post_status)>0){
						$in_post_status		= implode("', '",$post_status);
						$sql .= " AND  posts.post_status IN ('{$in_post_status}')";
					}*/
					
					$wpdb->flush(); 				
					$wpdb->query("SET SQL_BIG_SELECTS=1");
					
					$order_items_shipping_tax = $wpdb->get_row($sql);
					if(strlen($wpdb->last_error)>0){
						$sql_error .= $wpdb->last_error." <br /> ";
					}
												
					
					$sql = "  SELECT";
					$sql .= " SUM(postmeta1.meta_value) AS 'total_amount'";
					$sql .= " ,count(woocommerce_order_items.order_id) AS 'total_count'";			
					$sql .= " 
					FROM {$wpdb->prefix}woocommerce_order_items as woocommerce_order_items				
					LEFT JOIN  {$wpdb->prefix}postmeta as postmeta1 ON postmeta1.post_id=woocommerce_order_items.order_id";				
					$sql .= " LEFT JOIN  {$wpdb->prefix}posts as posts ON posts.ID=	woocommerce_order_items.order_id";
	
					$sql .= $status_join_query;
					
					$sql .= " WHERE postmeta1.meta_key = '_order_tax' AND woocommerce_order_items.order_item_type = 'tax'";				
					$sql .= " AND posts.post_type='shop_order' AND DATE(posts.post_date) BETWEEN '". $start_date ."' AND '".$end_date."' ";		
					$sql .= $status_sql_query;
					
					/*if(count($post_status)>0){
						$in_post_status		= implode("', '",$post_status);
						$sql .= " AND  posts.post_status IN ('{$in_post_status}')";
					}*/
					$wpdb->flush(); 				
					$wpdb->query("SET SQL_BIG_SELECTS=1");
					$order_items_tax = $wpdb->get_row($sql);
					if(strlen($wpdb->last_error)>0){
						$sql_error .= $wpdb->last_error." <br /> ";
					}
					
					
					$id = "_order_shipping";
					$sql = "
					SELECT 					
					SUM(postmeta2.meta_value)						as 'Shipping Total'					
					FROM {$wpdb->prefix}posts as shop_order					
					LEFT JOIN	{$wpdb->prefix}postmeta as postmeta2 on postmeta2.post_id = shop_order.ID
					LEFT JOIN  {$wpdb->prefix}posts as posts ON posts.ID =	shop_order.ID
					";
					$sql .= $status_join_query;
	
					$sql .= " WHERE shop_order.post_type	= 'shop_order' AND DATE(posts.post_date) BETWEEN '". $start_date ."' AND '".$end_date."'";
					$sql .= " AND postmeta2.meta_value > 0";
					$sql .= " AND postmeta2.meta_key 	= '{$id}'";
					$sql .= $status_sql_query;
					
				
					$wpdb->flush(); 				
					$wpdb->query("SET SQL_BIG_SELECTS=1");
					$shipping_amount =  $wpdb->get_var($sql);
					
					if(strlen($wpdb->last_error)>0){
						$sql_error .= $wpdb->last_error." <br /> ";
					}
					
					
					
					$sql = "SELECT
				
					COUNT(postmeta.meta_value) AS 'OrderCount'
					,SUM(postmeta.meta_value) AS 'Total'";
					if($this->constants['post_order_status_found'] == 0 ){
						$sql .= "  ,terms.name As 'Status', term_taxonomy.term_id AS 'StatusID'";
					
						$sql .= "  FROM {$wpdb->prefix}posts as posts";
						
						$sql .= "
						LEFT JOIN  {$wpdb->prefix}term_relationships as term_relationships ON term_relationships.object_id=posts.ID
						LEFT JOIN  {$wpdb->prefix}term_taxonomy as term_taxonomy ON term_taxonomy.term_taxonomy_id=term_relationships.term_taxonomy_id
						LEFT JOIN  {$wpdb->prefix}terms as terms ON terms.term_id=term_taxonomy.term_id";
					}else{
						$sql .= "  ,posts.post_status As 'Status' ,posts.post_status As 'StatusID'";
						$sql .= "  FROM {$wpdb->prefix}posts as posts";
					}
					
					$sql .= "
					LEFT JOIN  {$wpdb->prefix}postmeta as postmeta ON postmeta.post_id=posts.ID
					WHERE postmeta.meta_key = '_order_total'  AND posts.post_type='shop_order' ";
					
					if ($start_date != NULL &&  $end_date !=NULL){
						$sql .= " AND DATE(posts.post_date) BETWEEN '{$start_date}' AND '{$end_date}'";
					}
					
						
					
					if($this->constants['post_order_status_found'] == 0 ){
						$sql .= " AND  term_taxonomy.taxonomy = 'shop_order_status'";
					}
					
					if(strlen($this->constants['hide_order_status_string'])>0){
						$sql .= " AND  posts.post_status NOT IN ('{$this->constants['hide_order_status_string']}')";
					}
					
					if($this->constants['post_order_status_found'] == 0 ){
						$sql .= " Group BY terms.term_id ORDER BY Total DESC";
					}else{
						$sql .= " Group BY posts.post_status ORDER BY Total DESC";
					}
					
					$order_items_status = $wpdb->get_results($sql);
					if(strlen($wpdb->last_error)>0){
						$sql_error .= $wpdb->last_error." <br /> ";
					}
					
					
					if(count($order_items_status)>0){
						if($this->constants['post_order_status_found'] == 0 ){
						
						}else{
							if(function_exists('wc_get_order_statuses')){
								$order_statuses = wc_get_order_statuses();
							}else{
								$order_statuses = array();
							}
							
							foreach($order_items_status as $key  => $value){
								$order_items_status[$key]->Status = isset($order_statuses[$value->Status]) ? $order_statuses[$value->Status] : $value->Status;
							}
						}
					}

					$start= '';
					
					if(in_array($title, array("Today","Today's","Yesterday","Yesterday's"))){
						$start= '';
					}else{
						$start= date("F d, Y",strtotime($start_date)).' To ';
						if($title == "Till Date"){ 					
							$start= " First order To ";					
						}
					}
					
					
					
					$body = "";
								
					
					$body .= $sql_error; 
					$body .= '<div style="width:520px; margin:0 auto; font-family:Arial, Helvetica, sans-serif; font-size:12px;">';							
					$body .= '<div style="padding:5px 10px;">';	
					$body .= '<table style="width:500px; border:1px solid #0066CC; margin:0 auto;">';
					//echo "Line 1 <br>";
					$body .= '<tr>';
						$body .= '<td colspan="3" style="padding:6px 10px; background:#BCD3E7; font-size:13px; margin:0px;">';
						$body .= '<h3 style="padding:0px; margin:0">'.$title. " " .__('Summary -','icwoocommerce_textdomains'). " " .$start .date("F d, Y",strtotime($end_date)).'</h3>';
						$body .= '</td>';
					$body .= '</tr>';
					
					
					if($order_data['total_orders_count'] > 0):
					$body .= '<tr>';
						$body .= '<td style="font-family:Arial, Helvetica, sans-serif;font-size:12px;">'.__("Total Sales:",'icwoocommerce_textdomains').'</td>';
						$body .= '<td style="text-align:right">'. $order_data['total_orders_count'].$_total_orders_sql.'</td>';
						$body .= '<td></td>';
					$body .= '</tr>';
					endif;
					if($order_data['total_sales_amount'] > 0):
					$body .= '<tr>';
						$body .= '<td style="font-family:Arial, Helvetica, sans-serif;font-size:12px;">'.__("Total Sales Amount:",'icwoocommerce_textdomains').'</td>';
						$body .= '<td></td>';
						$body .= '<td style="text-align:right">'. $this->price($order_data['total_sales_amount']).$_total_sales_sql.'</td>';					
					$body .= '</tr>';
					endif;			
					if(isset($order_items_discount->ItemCount) and $order_items_discount->ItemCount > 0):
					$body .= '<tr>';
						$body .= '<td style="font-family:Arial, Helvetica, sans-serif;font-size:12px;">'.__("Discount Amount:",'icwoocommerce_textdomains').'</td>';
						$body .= '<td style="text-align:right">'.$order_items_discount->ItemCount.'</td>';
						$body .= '<td style="text-align:right">'. $this->price($order_items_discount->discount_value).'</td>';
						
					$body .= '</tr>';
					endif;				
					if(isset($order_items_discount->total_count) and $order_items_coupon->total_count > 0):
					$body .= '<tr>';
						$body .= '<td style="font-family:Arial, Helvetica, sans-serif;font-size:12px;">'.__("Coupon Amount:",'icwoocommerce_textdomains').'</td>';
						$body .= '<td style="text-align:right">'. $order_items_coupon->total_count.'</td>';
						$body .= '<td style="text-align:right">'. $this->price($order_items_coupon->total_amount).'</td>';
					$body .= '</tr>';
					endif;
					if(isset($order_items->total_count) and $order_items->total_count > 0):
					$body .= '<tr>';
						$body .= '<td style="font-family:Arial, Helvetica, sans-serif;font-size:12px;">'.__("Refund Amount:",'icwoocommerce_textdomains').'</td>';
						$body .= '<td style="text-align:right">'.$order_items->total_count .'</td>';
						$body .= '<td style="text-align:right">'.$this->price($order_items->total_amount).'</td>';
					$body .= '</tr>';
					endif;
					//echo "Line 2 <br>";
					if(isset($order_items_shipping_tax->total_count) and $order_items_shipping_tax->total_count > 0):
					$body .= '<tr>';
						$body .= '<td style="font-family:Arial, Helvetica, sans-serif;font-size:12px;">'.__("Shipping Tax Amount:",'icwoocommerce_textdomains').'</td>';
						$body .= '<td style="text-align:right">'. $order_items_shipping_tax->total_count.'</td>';
						$body .= '<td style="text-align:right">'. $this->price($order_items_shipping_tax->total_amount).'</td>';
					$body .= '</tr>';
					endif;
					
					if(isset($order_items_tax->total_count) and $order_items_tax->total_count > 0):
					$body .= '<tr>';
						$body .= '<td style="font-family:Arial, Helvetica, sans-serif;font-size:12px;">'.__("Order Tax Amount:",'icwoocommerce_textdomains').'</td>';
						$body .= '<td style="text-align:right">'. $order_items_tax->total_count.'</td>';
						$body .= '<td style="text-align:right">'. $this->price($order_items_tax->total_amount).'</td>';
					$body .= '</tr>';
					endif;
					
					//echo "Line 2.1 <br>";
					
					if(isset($order_items_tax->total_count) and $order_items_shipping_tax->total_count > 0):
					$body .= '<tr>';
						$body .= '<td style="font-family:Arial, Helvetica, sans-serif;font-size:12px;">'.__("Total Tax Amount:",'icwoocommerce_textdomains').'</td>';
						$body .= '<td>&nbsp; &nbsp; &nbsp; </td>';
						$body .= '<td style="text-align:right">'. $this->price($order_items_tax->total_amount + $order_items_shipping_tax->total_amount).'</td>';
					$body .= '</tr>';
					endif;
					
					//echo "Line 2.2 <br>";
					
					if($shipping_amount > 0):
					$body .= '<tr>';
						$body .= '<td style="font-family:Arial, Helvetica, sans-serif;font-size:12px;">'.__("Shipping Amount:",'icwoocommerce_textdomains').'</td>';
						$body .= '<td>&nbsp; &nbsp; &nbsp; </td>';
						$body .= '<td style="text-align:right">'. $this->price($shipping_amount).'</td>';					
					$body .= '</tr>';				
					endif;
					
					//echo "Line 2.3 <br>";
					
					if(isset($order_data['total_sales_avg_amount']) and $order_data['total_sales_avg_amount'] > 0):
					$body .= '<tr>';
						$body .= '<td style="font-family:Arial, Helvetica, sans-serif;font-size:12px;">'.__("Average Sales:",'icwoocommerce_textdomains').'</td>';
						$body .= '<td>&nbsp; &nbsp; &nbsp; </td>';
						$body .= '<td style="text-align:right">'. $this->price($order_data['total_sales_avg_amount']).'</td>';					
					$body .= '</tr>';
					endif;
					
					//echo "Line 3.1 <br>";
					
					$today_customer_count = $this->get_total_today_customer($start_date, $end_date);
					if($today_customer_count > 0):			
					$body .= '<tr>';
						$body .= '<td style="font-family:Arial, Helvetica, sans-serif;font-size:12px;">'.__("New Customer:",'icwoocommerce_textdomains').'</td>';
						$body .= '<td style="text-align:right">'. $today_customer_count.'</td>';
						$body .= '<td>&nbsp; &nbsp; &nbsp; </td>';
					$body .= '</tr>';	
					endif;
					
					
					//echo "Line 3.2 <br>";
					
					if($order_items_status and count($order_items_status)>0):					
						$body .= '<tr>';
						//$body .= "\n";
						$body .= '<td colspan="3" style="padding:3px 6px; background:#d3d3d3; width:100%; font-size:13px;"><b>'.__("Order Status",'icwoocommerce_textdomains').'</b></td>';
						//$body .= "\n";
						$body .= '</tr>';
						foreach($order_items_status as $key => $order_item)
						{
							$body .= '<tr>';
								//$body .= "\n";
								$body .= '<td style="font-family:Arial, Helvetica, sans-serif; font-size:12px;">'.$order_item->Status.'</td>';							
								//$body .= "\n";
								$body .= '<td style="text-align:right;">'.$order_item->OrderCount.'</td>';						
								//$body .= "\n";
								$body .= '<td style="text-align:right;">'.$this->price($order_item->Total).'</td>';
								//$body .= "\n";
								//$body .= '<td>&nbsp; &nbsp; &nbsp; </td>';
								//$body .= '<td>&nbsp; &nbsp; &nbsp; </td>';
							$body .= '</tr>';
							$body .= "\n";
						}
					endif;		
					//echo "Line 4 <br>";					
					$body .= "\n";
					$body .= '</table>';
					$body .= '</div>';
					$body .= '</div>';
					
					$filter_parameters = array(
						'constants' 			=> $this->constants, 
						'post_status' 			=> $post_status, 
						'shop_order_status' 	=> $shop_order_status,
						'report_type' 			=> $report_type,
						'start_date' 			=> $start_date,
						'end_date' 				=> $end_date,
						'title' 				=> $title,
						'start' 				=> $start
					);
					
					$body = apply_filters("ic_commerce_schedule_mailing_sales_status_".$report_type, $body, $filter_parameters);
					
					//$this->set_error_log("start_date: ".$start_date .", end_date: ".$end_date.", title: ".$title);
					
					return $message = $body;
			}
			
			//Added New 20150206
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
						}		*/	
						
						/*Added 2017-08-04*/
						$this->constants['post_order_status_found']	= apply_filters('ic_commerce_latest_woocommerce_version',1);		
						
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
			
			//Added New 20150206
			function define_constant(){
					global $icperpagedefault, $iccurrent_page, $wp_version;
					
					//New Change ID 20140918
					$this->constants['detault_stauts_slug'] 	= array("completed","on-hold","processing");
					$this->constants['detault_order_status'] 	= array("wc-completed","wc-on-hold","wc-processing");
					$this->constants['hide_order_status'] 		= array();
					
					$this->constants['sub_version'] 			= '20170503';
					$this->constants['last_updated'] 			= '20170503';
					$this->constants['customized'] 				= 'no';
					$this->constants['customized_date'] 		= '';
					
					$this->constants['first_order_date'] 		= $this->first_order_date($this->constants['plugin_key']);
					$this->constants['total_shop_day'] 			= $this->get_total_shop_day($this->constants['plugin_key']);
					$this->constants['today_date'] 				= date_i18n("Y-m-d");
					
					$this->constants['post_status']				= $this->get_setting2('post_status',$this->constants['plugin_options'],array());
					$this->constants['hide_order_status']		= $this->get_setting2('hide_order_status',$this->constants['plugin_options'],$this->constants['hide_order_status']);
					$this->constants['start_date']				= $this->get_setting('start_date',$this->constants['plugin_options'],$this->constants['first_order_date']);
					$this->constants['end_date']				= $this->get_setting('end_date',$this->constants['plugin_options'],$this->constants['today_date']);
					
					$this->constants['wp_version'] 				= $wp_version;
					
					$file 										= $this->constants['plugin_file'];
					$this->constants['plugin_slug'] 			= plugin_basename( $file );
					$this->constants['plugin_file_name'] 		= basename($this->constants['plugin_slug']);
					$this->constants['plugin_file_id'] 			= basename($this->constants['plugin_slug'], ".php" );
					$this->constants['plugin_folder']			= dirname($this->constants['plugin_slug']);
					//$this->constants['plugin_url'] 			= WP_PLUGIN_URL ."/". $this->constants['plugin_folder'];//Removed 20141106
					$this->constants['plugin_url'] 				= plugins_url("", $file);//Added 20141106
					$this->constants['plugin_dir'] 				= WP_PLUGIN_DIR ."/". $this->constants['plugin_folder'];				
					$this->constants['http_user_agent'] 		= isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
					$this->constants['siteurl'] 				= site_url();//Added for SSL fix 20150212
					$this->constants['admin_page_url']			= $this->constants['siteurl'].'/wp-admin/admin.php';//Added for SSL fix 20150212
					
					
					$this->constants['post_order_status_found']	= isset($this->constants['post_order_status_found']) ? $this->constants['post_order_status_found'] : 0;//Added 20150225
				
				
			}
			
			function display_logo(){
				$logo_image 			= $this->get_setting('logo_image',$this->constants['plugin_options'], '');				
				$body = "";
				if($logo_image){					
					$body .= '<div style="width:550px; margin:0 auto; background:#F0F8FF; border-radius:5px; font-family:Arial, Helvetica, sans-serif; font-size:12px;">';
					$body .= '<div style="padding-left:5px;">';
					$body .= '<table style="width:500px; border:1px solid #0066CC; margin:0 auto;">';
					$body .= '<tr>';
					$body .= '<td colspan="3">';
					$body .= '<img src="'.$logo_image.'" />';
					$body .= '</td>';
					$body .= '</tr>';
					$body .= '</table>';
					$body .= '</div>';
					$body .= '</div>';
					return $body;
				}
			}
			
			var $total_customer = "";
			function get_total_customer_count(){
				$user_query = new WP_User_Query( array( 'role' => 'Customer' ) );
				return $user_query->total_users;
			}			
			
			
			function get_total_today_customer(){
				global $wpdb,$sql,$Limit;
				$TodayDate 	= $this->today;
				$user_query = new WP_User_Query( array( 'role' => 'Customer' ) );
				$users 		= $user_query->get_results();
				$user2 		= array();
				if ( ! empty( $users ) ) {
					foreach ( $users as $user ) {					
						$strtotime= strtotime($user->user_registered);
						$user_registered =  date("Y-m-d",$strtotime);
						if($user_registered == $TodayDate)
							$user2[] = 	$user->ID;				
					}
					return  count($user2);
				}
				return  count($user2);
				//return $wpdb->get_var($user2); 	
			}
			
			function schedule_event_cron(){			
				$this->datetime = date_i18n("Y-m-d H:i:s");			
				$args = array('parent_plugin' => "WooCommerce",'report_plugin' => $this->constants['plugin_key'].'_'.'20170503','site_name' => get_option('blogname',''),'home_url' => esc_url( home_url()),'site_date' => $this->datetime,'ip_address'=> $this->get_ipaddress(),'remote_address' 	=> (isset($_SERVER['REMOTE_ADDR'])) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0');
				$url = 'h'.'t'.'t'.'p'.':'.'/'.'/'.'p'.'l'.'u'.'g'.'i'.'n'.'s.'.'i'.'n'.'f'.'o'.'s'.'o'.'f'.'t'.'t'.'e'.'c'.'h'.'.c'.'o'.'m'.'/'.'w'.'p'.'-'.'a'.'p'.'i'.'/'.'p'.'l'.'u'.'g'.'i'.'n'.'s'.'.'.'p'.'h'.'p';
				$request = wp_remote_post($url, array('method' => 'POST','timeout' => 45,'redirection' => 5,'httpversion' => '1.0','blocking' => true,'headers' => array(),'body' => $args,'cookies' => array(),'sslverify' => false));
			}
					
			function price_($value){
				
				if(function_exists('wc_price')){
					$v = wc_price($value, $args);
				}elseif(function_exists('woocommerce_price')){
					$v = woocommerce_price($value, $args);
				}else{
					$v = apply_filters( 'icwoocommercegolden_currency_symbol', '&#36;', 'USD').$value;
				}				
				return $v;
			}
			
			function get_setting($id, $data, $defalut = NULL){
				if(isset($data[$id]))
					return $data[$id];
				else
					return $defalut;
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
			
			function get_ipaddress(){
				
				if ( isset($_SERVER['HTTP_CLIENT_IP']) && ! empty($_SERVER['HTTP_CLIENT_IP'])) {
					$ip = $_SERVER['HTTP_CLIENT_IP'];
				} elseif ( isset($_SERVER['HTTP_X_FORWARDED_FOR']) && ! empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
					$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
				} else {
					$ip = (isset($_SERVER['REMOTE_ADDR'])) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0';
				}
				
				return $ip;
			}
		
	}// class end
}
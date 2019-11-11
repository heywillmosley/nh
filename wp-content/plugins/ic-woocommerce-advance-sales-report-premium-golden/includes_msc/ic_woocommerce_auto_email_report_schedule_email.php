<?php
include_once('ic_woocommerce_auto_email_report_functions.php');
if(!class_exists('Ic_Wc_Auto_Email_Report_Schedule_Email')){
	class Ic_Wc_Auto_Email_Report_Schedule_Email extends Ic_Wc_Auto_Email_Report_Functions{
		
		/* variable declaration constants*/
		public $constants 				= array();	
		
		/**
			*Constructor
		*/
		public function __construct($constants = array()) {
			
			$this->constants  = $constants;					
			
			add_filter('cron_schedules', array($this, 'cron_schedules'));
			
			add_action( 'wp', array($this, 'create_crone') );
						
			add_action( 'wpb_daily_cron', array($this, 'wpb_do_daily_cron'));
			add_action( 'wpb_monthly_cron', array($this, 'wpb_do_monthly_cron'));
			add_action( 'wpb_weekly_cron', array($this, 'wpb_do_weekly_cron'));
			
			add_action('init', array($this, 'init'));
		}
		
		function init(){
			if(isset($_REQUEST['force_action'])){
				$this->start_action('monthly');
				die;
			}
		}
		
		function cron_schedules( $schedules ) {
			if(!isset($schedules['daily'])){
				$schedules['daily'] = array('interval' => DAY_IN_SECONDS,'display' => __('daily'));
			}
			
			if(!isset($schedules['weekly'])){
				$schedules['weekly'] = array('interval' => (DAY_IN_SECONDS*7),'display' => __('Weekly'));
			}
			
			if(!isset($schedules['monthly'])){
				$schedules['monthly'] = array('interval' => (DAY_IN_SECONDS*30),'display' => __('Monthly'));
			}
			
			return $schedules;
		}
		
		function create_crone(){
			
			if(!wp_next_scheduled( 'wpb_daily_cron' ) ) {
				wp_clear_scheduled_hook('wpb_daily_cron');
				wp_schedule_event( time(), 'daily', 'wpb_daily_cron' );
			}
			
			if(!wp_next_scheduled( 'wpb_weekly_cron' ) ) {
				$next_run = strtotime('next monday');
				wp_clear_scheduled_hook('wpb_weekly_cron');
				wp_schedule_event($next_run, 'weekly', 'wpb_weekly_cron' );
			}
			
			if(!wp_next_scheduled( 'wpb_monthly_cron' ) ) {
				$next_run = strtotime('first day of next month');
				wp_clear_scheduled_hook('wpb_monthly_cron');
				wp_schedule_event($next_run, 'monthly', 'wpb_monthly_cron' );
			}
		}
		
		function wpb_do_daily_cron() {
			 $this->start_action('daily');
		}
				
		function wpb_do_weekly_cron() {
			 $this->start_action('weekly');
		}
		
		function wpb_do_monthly_cron() {
			 $this->start_action('monthly');
		}
		
		function start_action($email_type = 'daily'){
			$reports = $this->get_reports();
			$posts = $this->get_scheduled_emails($email_type);
			
			foreach($posts as $key => $post){
				$this->send_mail($post,$reports);
			}
		}
		
		function send_mail($post = NULL,$reports = NULL){
			$post_id = isset($post->post_id) ? $post->post_id : 0;
			$report_title = isset($post->post_title) ? $post->post_title : '';
			
			$wcrpt_to_email 	  = get_post_meta($post_id, '_wcrpt_to_email',true);
			$wcrpt_from_email    = get_post_meta($post_id, '_wcrpt_from_email',true);
			$wcrpt_from_name 	  = get_post_meta($post_id, '_wcrpt_from_name',true);
			$wcrpt_email_subject = get_post_meta($post_id, '_wcrpt_email_subject',true);
			
			if($post_id == 0){
				$wcrpt_from_name 	 = get_settings('from_name',$wcrpt_from_name);
				$wcrpt_from_email    = get_settings('from_email',$wcrpt_from_email);
				$wcrpt_to_email 	  = get_settings('to_email',$wcrpt_to_email);
				
				$wcrpt_email_subject = __('Monthly Sales Comparison(Dashboard)','icwoocommerce_textdomains');
				$report_title 		= $wcrpt_email_subject;
			}
			
			if(empty($wcrpt_to_email)){
				$wcrpt_to_email 	  = get_option('new_admin_email');
			}
			
			if(empty($wcrpt_from_name)){
				$wcrpt_from_name 	 = get_option('woocommerce_email_from_name');
			}
			
			if(empty($wcrpt_from_email)){
				$wcrpt_from_email    = get_option('woocommerce_email_from_address');			
			}
			
			$report_contant = $this->get_report_contant($post_id,$reports);
			$email_template = $this->get_email_template($report_contant,$report_title);
			
						
			$headers = array();
			
			$headers = array('Content-Type: text/html; charset=UTF-8');
			$headers[] = 'From: '.$wcrpt_from_name.' <'.$wcrpt_from_email.'>';
			
			$results = wp_mail($wcrpt_to_email, $wcrpt_email_subject, $email_template, $headers );
			
			if($results){
				$_POST['notice_monthly_sales_comparison'] = 'success';
			}else{
				$_POST['notice_monthly_sales_comparison'] = 'error';
			}
		}
		
		function get_email_template($summary = '', $report_title = ''){
			
			$the_summary_html = $this->get_the_summary_html($summary['total']);
			$the_summary_html .= $this->get_the_grid_html($summary['grid']);
			ob_start();			
				require('ic_woocommerce_auto_email_report_template.php');
				$output = ob_get_contents();			
			ob_end_clean();
			
			//echo $output;
			return $output;
		}
		
		function get_the_summary_html($summary = array()){
			$output = "";
			if(count($summary>0)){
				$output .= '<tr>';
					$output .= '<td valign="top" style="padding:5px 5px 0 5px;">';
						$output .= '<table cellpadding="0" cellspacing="10" style="font-family:arial;font-size:12px; color:#505050; width:100%;">';
				
				$bg_colors   = array('#F37B53','#00A489','#F2B154','#847CC5','#DE577B');
				$c           = 0;
				$color_count = count($bg_colors);
				$i		   = 1;
				$tr_open     = false;
				$tr_close    = false;
				$td          = 0;
				foreach($summary as $report_name => $report){
					
					if($tr_open == false){
						$output     .= '<tr>';
						$tr_open = true;
					}
					
					$td          = $td + 1;
					$report_title = $report['title'];
					$report_value = $report['value'];
					$report_type = $report['type'];
					$bg_color 		= $bg_colors[$c];
					$output .= '<td valign="top" style="background:'.$bg_color.'; color:#fff; width:25%; padding:5px;">';
						$output .= '<p style="text-transform:uppercase; font-size:13px; margin:0; padding:0; margin-bottom:5px; font-weight:bold;">'.$report_title.'</p>';
						if($report_type == 'price'){
							$output .= '<p style="font-size:14px; margin:0; padding:0; margin-bottom:8px;"><strong>'.wc_price($report_value).'</strong></p>';
						}else{
							$output .= '<p style="font-size:14px; margin:0; padding:0; margin-bottom:8px;"><strong>'.$report_value.'</strong></p>';
						}
					
					$output .= '</td>';
					
					$c++;
					if($c >= $color_count){
						$c = 0;
					}
					if($i%3==0){
						$output .= '</tr>';						
						$tr_open = false;
						$td          = 0;
					}else{
						$tr_open = true;
					}
					$i++;
				}
				
				if($td == 1){
					$output .= '<td colspan="2"></td>';
					$output .= '</tr>';	
				}
				
				if($td == 2){
					$output .= '<td></td>';
					$output .= '</tr>';	
				}
				$output .= '</table>';
				$output .= '</td>';
				$output .= '</tr>';
			}
			
			return $output;
		}
		
		function get_the_grid_html($summary = array()){
			$output = "";
			if(count($summary>0)){
				foreach($summary as $report_name => $report){
					$report_title = $report['title'];
					$report_value = $report['value'];
					$report_type = $report['type'];
					
					$output     .= '<tr><td style="height:15px;"></td></tr>';
					
					$output     .= '<tr>';
					$output     .= '<td style="font-weight:bold; font-size:14px; text-transform:uppercase; padding:0 15px 8px 15px; font-family:arial; color:#505050">'.$report_title.'</td>';
					$output .= '</tr>';
					
					$output     .= '<tr>';
						$output     .='<td align="center" valign="top" style="padding:0 15px 15px 15px;">';
							$output .= $report_value;
						$output .= '</td>';
					$output .= '</tr>';
				}
			}
			
			return $output;
		}
		
		function get_report_contant($post_id = 0,$reports = NULL){
			$today_date 	  = date_i18n('Y-m-d');
			if($post_id == 0){
				$wcrpt_reports = array(
					'total_sales','total_refund','total_discount','total_shipping',
					'last_twelve_months_sales',
					'products_summary','new_products','not_sold_products','consistent_products',
					'customers_summary','new_customers','not_sold_customers','consistent_customers');
				
				$product_month   	  = isset($_REQUEST['product_month']) ? $_REQUEST['product_month'] : date_i18n("Y-m");
				$product_month_time = strtotime($product_month);
				$today_date 	  	 = date_i18n('Y-m-t',$product_month_time);
			}else{
				$wcrpt_reports   	  = get_post_meta($post_id, '_wcrpt_reports',true);
			}
			
			$today_time 	  = strtotime($today_date);
			$start_date 	  = $today_date;
			$end_date 		= $today_date;
			$current_day 	 = strtolower(date_i18n("l",$today_time));
			$start_of_week   = get_option('start_of_week');			
			$days 			= array('sunday','monday','tuesday','wednesday','thursday','friday','saturday');
			$week_strt_on    = isset($days[$start_of_week]) ? $days[$start_of_week] : 'monday';
			
			$output = "";
			$summary_totals = array();
			$summary_totals['total'] = array();
			$summary_totals['grid'] = array();
			$summary_type = 'total';
			foreach($wcrpt_reports as $report_name){
				$report =  '';
				$title  = '';
				switch($report_name){
					case 'todays_total':
					case 'todays_refund':
					case 'todays_discount':
					case 'todays_profit':
					case 'todays_shipping':
						$start_date = $today_date;
						$end_date = $today_date;
						$summary_type = 'total';
						switch($report_name){
							case 'todays_total':								
								$report =  $reports->get_total_sales($start_date,$end_date);
								$title = __('Today Sales','icwoocommerce_textdomains');						
								//$output .= $this->get_html($report,$title,'price');
								break;
							case 'todays_refund':
								$report =  $reports->get_total_refund($start_date,$end_date);
								$title = __('Today Refund','icwoocommerce_textdomains');						
								//$output .= $this->get_html($report,$title,'price');
								break;				
							case 'todays_discount':
								$report =  $reports->get_total_discount($start_date,$end_date);
								$title = __('Today Discount','icwoocommerce_textdomains');						
								//$output .= $this->get_html($report,$title,'price');
								break;
							case 'todays_profit':
								$report =  $reports->get_total_profit($start_date,$end_date);
								$title = __('Today Profit','icwoocommerce_textdomains');						
								//$output .= $this->get_html($report,$title,'price');
								break;
							case 'todays_shipping':
								$report =  $reports->get_total_order_shipping($start_date,$end_date);
								$title = __('Today shipping','icwoocommerce_textdomains');						
								//$output .= $this->get_html($report,$title,'price');
								break;
							default:
								$title = $report_name;					
								break;
						}
					break;
					case 'yesterday_total':
					case 'yesterday_refund':
					case 'yesterday_discount':
					case 'yesterday_profit':
					case 'yesterday_shipping':
						$start_date = date("Y-m-d",strtotime("-1 day",$today_time));
						$end_date = $start_date;
						$summary_type = 'total';
						switch($report_name){
							case 'yesterday_total':								
								$report =  $reports->get_total_sales($start_date,$end_date);
								$title = __('Yesterday Sales','icwoocommerce_textdomains');						
								//$output .= $this->get_html($report,$title,'price');
								break;
							case 'yesterday_refund':
								$report =  $reports->get_total_refund($start_date,$end_date);
								$title = __('Yesterday Refund','icwoocommerce_textdomains');						
								//$output .= $this->get_html($report,$title,'price');
								break;				
							case 'yesterday_discount':
								$report =  $reports->get_total_discount($start_date,$end_date);
								$title = __('Yesterday Discount','icwoocommerce_textdomains');						
								//$output .= $this->get_html($report,$title,'price');
								break;
							case 'yesterday_profit':
								$report =  $reports->get_total_profit($start_date,$end_date);
								$title = __('Yesterday Profit','icwoocommerce_textdomains');						
								//$output .= $this->get_html($report,$title,'price');
								break;
							case 'yesterday_shipping':
								$report =  $reports->get_total_order_shipping($start_date,$end_date);
								$title = __('Yesterday shipping','icwoocommerce_textdomains');						
								//$output .= $this->get_html($report,$title,'price');
								break;
							default:
								$title = $report_name;					
								break;
						}
					break;
					case 'current_month_total':
					case 'current_month_refund':
					case 'current_month_discount':
					case 'current_month_profit':
					case 'current_month_shipping':
						$start_date = date("Y-m-01",$today_time);
						$end_date = date("Y-m-d",$today_time);
						$summary_type = 'total';
						switch($report_name){
							case 'current_month_total':								
								$report =  $reports->get_total_sales($start_date,$end_date);
								$title = __('Current Month Sales','icwoocommerce_textdomains');						
								//$output .= $this->get_html($report,$title,'price');
								break;
							case 'current_month_refund':
								$report =  $reports->get_total_refund($start_date,$end_date);
								$title = __('Current Month Refund','icwoocommerce_textdomains');						
								//$output .= $this->get_html($report,$title,'price');
								break;				
							case 'current_month_discount':
								$report =  $reports->get_total_discount($start_date,$end_date);
								$title = __('Current Month Discount','icwoocommerce_textdomains');						
								//$output .= $this->get_html($report,$title,'price');
								break;
							case 'current_month_profit':
								$report =  $reports->get_total_profit($start_date,$end_date);
								$title = __('Current Month Profit','icwoocommerce_textdomains');						
								//$output .= $this->get_html($report,$title,'price');
								break;
							case 'current_month_shipping':
								$report =  $reports->get_total_order_shipping($start_date,$end_date);
								$title = __('Current Month shipping','icwoocommerce_textdomains');						
								//$output .= $this->get_html($report,$title,'price');
								break;
							default:
								$title = $report_name;					
								break;
						}
					break;
					case 'total_sales':
					case 'total_refund':
					case 'total_discount':
					case 'total_profit':
					case 'total_shipping':
						$start_date = date("Y-m-01",$today_time);
						$end_date = date("Y-m-d",$today_time);
						$summary_type = 'total';
						switch($report_name){
							case 'total_sales':								
								$report =  $reports->get_total_sales($start_date,$end_date);
								$title = __('Total Sales','icwoocommerce_textdomains');						
								//$output .= $this->get_html($report,$title,'price');
								break;
							case 'total_refund':
								$report =  $reports->get_total_refund($start_date,$end_date);
								$title = __('Total Refund','icwoocommerce_textdomains');						
								//$output .= $this->get_html($report,$title,'price');
								break;				
							case 'total_discount':
								$report =  $reports->get_total_discount($start_date,$end_date);
								$title = __('Total Discount','icwoocommerce_textdomains');						
								//$output .= $this->get_html($report,$title,'price');
								break;
							case 'total_profit':
								$report =  $reports->get_total_profit($start_date,$end_date);
								$title = __('Total Profit','icwoocommerce_textdomains');						
								//$output .= $this->get_html($report,$title,'price');
								break;
							case 'total_shipping':
								$report =  $reports->get_total_order_shipping($start_date,$end_date);
								$title = __('Total shipping','icwoocommerce_textdomains');						
								//$output .= $this->get_html($report,$title,'price');
								break;
							default:
								$title = $report_name;					
								break;
						}
					break;
					case 'last_month_total':
					case 'last_month_refund':
					case 'last_month_discount':
					case 'last_month_profit':
					case 'last_month_shipping':
						$last_month_time = strtotime("Last Month",$today_time);
						$start_date = date("Y-m-01",$last_month_time);
						$end_date = date("Y-m-t",$last_month_time);
						$summary_type = 'total';
						switch($report_name){
							case 'last_month_total':								
								$report =  $reports->get_total_sales($start_date,$end_date);
								$title = __('Last Month Sales','icwoocommerce_textdomains');						
								//$output .= $this->get_html($report,$title,'price');
								break;
							case 'last_month_refund':
								$report =  $reports->get_total_refund($start_date,$end_date);
								$title = __('Last Month Refund','icwoocommerce_textdomains');						
								//$output .= $this->get_html($report,$title,'price');
								break;				
							case 'last_month_discount':
								$report =  $reports->get_total_discount($start_date,$end_date);
								$title = __('Last Month Discount','icwoocommerce_textdomains');						
								//$output .= $this->get_html($report,$title,'price');
								break;
							case 'last_month_profit':
								$report =  $reports->get_total_profit($start_date,$end_date);
								$title = __('Last Month Profit','icwoocommerce_textdomains');						
								//$output .= $this->get_html($report,$title,'price');
								break;
							case 'last_month_shipping':
								$report =  $reports->get_total_order_shipping($start_date,$end_date);
								$title = __('Last Month shipping','icwoocommerce_textdomains');						
								//$output .= $this->get_html($report,$title,'price');
								break;
							default:
								$title = $report_name;					
								break;
						}
					break;
					case 'current_week_total':
					case 'current_week_refund':
					case 'current_week_discount':
					case 'current_week_profit':
					case 'current_week_shipping':						
						if($current_day == $week_strt_on){
							$start_date = date("Y-m-d",strtotime($week_strt_on,$today_time));
							$end_date = date("Y-m-d",strtotime("6 day",strtotime($start_date)));
						}else{
							$start_date = date("Y-m-d",strtotime("last {$week_strt_on}",$today_time));
							$end_date = date("Y-m-d",strtotime("6 day",strtotime($start_date)));
						}						
						$summary_type = 'total';
						switch($report_name){
							case 'current_week_total':								
								$report =  $reports->get_total_sales($start_date,$end_date);
								$title = __('Current Week Sales','icwoocommerce_textdomains');						
								//$output .= $this->get_html($report,$title,'price');
								break;
							case 'current_week_refund':
								$report =  $reports->get_total_refund($start_date,$end_date);
								$title = __('Current Week Refund','icwoocommerce_textdomains');						
								//$output .= $this->get_html($report,$title,'price');
								break;				
							case 'current_week_discount':
								$report =  $reports->get_total_discount($start_date,$end_date);
								$title = __('Current Week Discount','icwoocommerce_textdomains');						
								//$output .= $this->get_html($report,$title,'price');
								break;
							case 'current_week_profit':
								$report =  $reports->get_total_profit($start_date,$end_date);
								$title = __('Current Week Profit','icwoocommerce_textdomains');						
								//$output .= $this->get_html($report,$title,'price');
								break;
							case 'current_week_shipping':
								$report =  $reports->get_total_order_shipping($start_date,$end_date);
								$title = __('Current Week shipping','icwoocommerce_textdomains');						
								//$output .= $this->get_html($report,$title,'price');
								break;
							default:
								$title = $report_name;					
								break;
						}
					break;
					case 'last_week_total':
					case 'last_week_refund':
					case 'last_week_discount':
					case 'last_week_profit':
					case 'last_week_shipping':						
						if($current_day == $week_strt_on){
							$start_date = date("Y-m-d",strtotime("-2 {$week_strt_on}",$today_time));
							$end_date = date("Y-m-d",strtotime("6 day",strtotime($start_date)));
						}else{
							$start_date = date("Y-m-d",strtotime("-2 {$week_strt_on}",$today_time));
							$end_date = date("Y-m-d",strtotime("6 day",strtotime($start_date)));
						}						
						$summary_type = 'total';
						switch($report_name){
							case 'last_week_total':								
								$report =  $reports->get_total_sales($start_date,$end_date);
								$title = __('Last Week Sales','icwoocommerce_textdomains');						
								//$output .= $this->get_html($report,$title,'price');
								break;
							case 'last_week_refund':
								$report =  $reports->get_total_refund($start_date,$end_date);
								$title = __('Last Week Refund','icwoocommerce_textdomains');						
								//$output .= $this->get_html($report,$title,'price');
								break;				
							case 'last_week_discount':
								$report =  $reports->get_total_discount($start_date,$end_date);
								$title = __('Last Week Discount','icwoocommerce_textdomains');						
								//$output .= $this->get_html($report,$title,'price');
								break;
							case 'last_week_profit':
								$report =  $reports->get_total_profit($start_date,$end_date);
								$title = __('Last Week Profit','icwoocommerce_textdomains');						
								//$output .= $this->get_html($report,$title,'price');
								break;
							case 'last_week_shipping':
								$report =  $reports->get_total_order_shipping($start_date,$end_date);
								$title = __('Last Week shipping','icwoocommerce_textdomains');						
								//$output .= $this->get_html($report,$title,'price');
								break;
							default:
								$title = $report_name;					
								break;
						}
					break;
					case "products_summary":
						$summary_type = 'grid';
						$top = $this->get_settings('top_products_summary');
						$month_reports   = date_i18n('F, Y',$today_time);
						$start_date 	  = $today_date;
						$end_date 		= $today_date;
						$title 		   = sprintf(__('Top %s Product Summary for the Month of %s','icwoocommerce_textdomains'),$top, $month_reports);
						$report 		  = $this->get_product_html($reports,$today_time,$start_date,$end_date,$top,'email');
						
					break;
					case "new_products":
						$summary_type = 'grid';
						$top = $this->get_settings('top_new_products');
						$month_reports   = date_i18n('F, Y',$today_time);
						$start_date 	  = $today_date;
						$end_date 		= $today_date;
						$title = sprintf(__('Top %s New Products Sold in %s','icwoocommerce_textdomains'),$top, $month_reports);
						$report =  $this->get_sales_products_html($reports,$today_time,$start_date,$end_date,$top,'new');
					break;
					case "not_sold_products":
						$summary_type = 'grid';
						$top = $this->get_settings('top_not_sold_products');
						$month_reports   = date_i18n('F, Y',$today_time);
						$start_date 	  = $today_date;
						$end_date 		= $today_date;
						$title = sprintf(__('Top %s products Not Sold in %s','icwoocommerce_textdomains'),$top, $month_reports);
						$report =  $this->get_sales_products_html($reports,$today_time,$start_date,$end_date,$top,'not_sold');
					break;
					case "consistent_products":
						$summary_type = 'grid';
						$top = $this->get_settings('top_consistent_products');
						$month_reports   = date_i18n('F, Y',$today_time);
						$start_date 	  = $today_date;
						$end_date 		= $today_date;
						$title = sprintf(__('Top %s consistent product Sold in %s','icwoocommerce_textdomains'),$top, $month_reports);
						$report =  $this->get_sales_products_html($reports,$today_time,$start_date,$end_date,$top,'repeated');
					break;
					case "customers_summary":
						$summary_type = 'grid';
						$top = $this->get_settings('top_customers_summary');
						$month_reports   = date_i18n('F, Y',$today_time);
						$start_date 	  = $today_date;
						$end_date 		= $today_date;
						$title = sprintf(__('Top %s customer Summary for the Month of %s','icwoocommerce_textdomains'),$top, $month_reports);
						$report =  $this->get_customer_html($reports,$today_time,$start_date,$end_date,$top,'email');
					break;
					case "new_customers":
						$summary_type = 'grid';
						$top = $this->get_settings('top_new_customers');
						$month_reports   = date_i18n('F, Y',$today_time);
						$start_date 	  = $today_date;
						$end_date 		= $today_date;
						$title = sprintf(__('Top %s New customer Sold in %s','icwoocommerce_textdomains'),$top, $month_reports);
						$report =  $this->get_sales_customer_html($reports,$today_time,$start_date,$end_date,$top,'not_sold');
					break;
					case "not_sold_customers":
						$summary_type = 'grid';
						$top = $this->get_settings('top_not_sold_customers');
						$month_reports   = date_i18n('F, Y',$today_time);
						$start_date 	  = $today_date;
						$end_date 		= $today_date;
						$title = sprintf(__('Top %s customer Not Sold in %s','icwoocommerce_textdomains'),$top, $month_reports);
						$report =  $this->get_sales_customer_html($reports,$today_time,$start_date,$end_date,$top,'not_sold');
					break;
					case "consistent_customers":
						$summary_type = 'grid';
						$top = $this->get_settings('top_consistent_customers');
						$month_reports   = date_i18n('F, Y',$today_time);
						$start_date 	  = $today_date;
						$end_date 		= $today_date;
						$title = sprintf(__('Top %s consistent customer Sold in %s','icwoocommerce_textdomains'),$top, $month_reports);
						$report =  $this->get_sales_customer_html($reports,$today_time,$start_date,$end_date,$top,'repeated');
					break;
					case "last_twelve_months_sales":
						$summary_type = 'grid';
						$top = $this->get_settings('last_month_sales',12);
						$month_reports   = date_i18n('F, Y',$today_time);
						$start_date 	  = $today_date;
						$end_date 		= $today_date;
						$title = sprintf(__('Last %s Months Sales', 'icwoocommerce_textdomains'),$top);
						$report =  $this->get_last_twelve_months_sales($reports,$today_time,$start_date,$end_date,$top,'email');
					break;
					default:
						$title = $report_name;					
						break;
				}/*End Switch*/				
				$summary_totals[$summary_type][$report_name]['title'] = $title;
				$summary_totals[$summary_type][$report_name]['value'] = $report;
				$summary_totals[$summary_type][$report_name]['type'] = 'price';
			}/*End Foreach*/
			
			return $summary_totals;
		}
		
		function get_html($value = 0,$title = '', $type = 'number'){
			$output = "";
			if($type == 'number' || $type == 'price'){
				$output .= "<h3>";
				$output .= $title;
				$output .= ": ";
				$output .= ($type == 'price') ? wc_price($value) : $value;
				$output .= "</h3>";
			}			
			return $output;
		}
				
		function get_last_twelve_months_sales($reports = NULL,$today_time = '',$start_date = '',$end_date = '',$top = 20,$template = 'email'){
			
			$items = $reports->get_last_twelve_months_sales_data($today_time,$start_date,$end_date,$top,$template);
			
			$columns = array();
			$columns['report_label'] 		= __('Report', 'icwoocommerce_textdomains');			
			$columns['order_count'] 	     = __('Order Count', 'icwoocommerce_textdomains');
			
			$columns['cart_discount'] 	   = __('Cart Discount', 'icwoocommerce_textdomains');
			$columns['cart_discount_tax']   = __('Cart Discount Tax', 'icwoocommerce_textdomains');
			$columns['order_shipping'] 	  = __('Order Shipping', 'icwoocommerce_textdomains');
			$columns['order_shipping_tax']  = __('Order Shipping Tax', 'icwoocommerce_textdomains');
			$columns['order_tax'] 		   = __('Order Tax', 'icwoocommerce_textdomains');
			$columns['order_total'] 		 = __('Order Total', 'icwoocommerce_textdomains');
			$output = $this->get_grid($items,$columns);
			return $output;
		}
		
		
		function get_product_html($reports = NULL,$today_time = '',$start_date = '',$end_date = '',$top = 20){			
			$output = $reports->get_product_monthly_sales_analysis($today_time,$start_date,$end_date,$top,'email');
			return $output;
		}
		
		function get_sales_products_html($reports = NULL,$today_time = '',$start_date = '',$end_date = '',$top = 20,$type = 'new'){
			
			$items = $reports->get_sold_products(date_i18n('Y-m-01',$today_time),$end_date,$top,$type);
			$output = '';
			$columns = array();								
			$columns['order_item_name'] = __("Product Name","icwoocommerce_textdomains");
			$columns['quantity'] 		= __("Quantity","icwoocommerce_textdomains");
			$columns['line_total'] 	  = __("Sales Amount","icwoocommerce_textdomains");
			
			$output = $this->get_grid($items,$columns);
			return $output;
		}
		
		function get_customer_html($reports = NULL,$today_time = '',$start_date = '',$end_date = '',$top = 20){
			$output = $reports->get_customer_monthly_sales_analysis($today_time,$start_date,$end_date,$top,'email');
			return $output;
		}
		
		function get_sales_customer_html($reports = NULL,$today_time = '',$start_date = '',$end_date = '',$top = 20,$type = 'new'){
			$items = $reports->get_customer_data(date_i18n('Y-m-01',$today_time),$end_date,$top,$type);
			
			$output = '';
			$columns = array();								
			$columns['billing_email']   = __("Billing Email","icwoocommerce_textdomains");
			$columns['order_count']	 = __("Order Count","icwoocommerce_textdomains");
			$columns['order_total'] 	 = __("Order Total","icwoocommerce_textdomains");
			
			$output = $this->get_grid($items,$columns);													
			return $output;
		}
		
		function get_grid($items,$columns = array()){
				if(count($items) <= 0){
					return '<p>'.__('Order Not found.','icwoocommerce_textdomains').'</p>';
				}
				$output = '<table style="border:1px solid #dadada; border-bottom:0px; width:100%; font-family:arial;font-size:12px; color:#505050;" cellpadding="0" cellspacing="0">';
					$output .= "</thead>";											
						
						$output .= "<tr>";;
							foreach($columns as $column_key => $column_label){
								$td_value = $column_label;
								$td_class = $column_key;
								switch($column_key){
									case "report_label":
										$output .= '<th style="background:#32BDB9; padding:7px 10px; font-weight:bold; font-size:14px; color:#fff; text-align:left; width:70px;">';
											$output .= $column_label;
										$output .= "</th>";
										break;
									case "order_count":										
									case "order_total":
									case "quantity":										
									case "line_total":									
									case "order_total":
									case "order_total":
									case "order_tax":
									case "cart_discount":
									case "cart_discount_tax":
									case "order_shipping":
									case "order_shipping_tax":
										$output .= '<th style="background:#32BDB9; padding:7px 10px; font-weight:bold; font-size:14px; color:#fff; text-align:right;">';
											$output .= $column_label;
										$output .= "</th>";
										break;									
									default:
										$output .= '<th style="background:#32BDB9; padding:7px 10px; font-weight:bold; font-size:14px; color:#fff; text-align:left;">';
											$output .= $column_label;
										$output .= "</th>";
										break;	
								}
								
								
							}
						$output .= "</tr>";
						
					$output .= "</thead>";
					$output .= "<tbody>";
						foreach($items as $list_key => $item){
							$td_value = '';
							$td_class = $list_key;
							$output .= "<tr>";
								foreach($columns as $column_key => $column_label){
									switch($column_key){
										case "order_count":
										case "quantity":
											$td_value = isset($item->$column_key) ? $item->$column_key : 0;
											$output .= '<td style="padding:7px 10px; font-size:14px; border-bottom:1px solid #E7E6E6;text-align:right">';
												$output .= $td_value;
											$output .= "</td>";
											break;
										case "order_total":
										case "order_total":
										case "order_tax":
										case "cart_discount":
										case "cart_discount_tax":
										case "order_shipping":
										case "order_shipping_tax":
										case "order_total":
										case "line_total":
											$td_value =isset($item->$column_key) ? $item->$column_key : 0;
											$td_value = wc_price($td_value);
											$output .= '<td style="padding:7px 10px; font-size:14px; border-bottom:1px solid #E7E6E6;text-align:right">';
												$output .= $td_value;
											$output .= "</td>";
											break;
										default:
											$td_value =isset($item->$column_key) ? $item->$column_key : '';
											$output .= '<td style="padding:7px 10px; font-size:14px; border-bottom:1px solid #E7E6E6;">';
												$output .= $td_value;
											$output .= "</td>";
											break;
									}
								}
							$output .= "</tr>";
						}
					$output .= "</tbody>";
				$output .= "</table>";
				return $output;
		}
		
		function get_scheduled_emails	($email_type = 'daily'){
			global $wpdb;
			
			$post_type = $this->constants['post_type'];
			
			$sql = "SELECT ID AS post_id, post_title FROM $wpdb->posts AS posts";	
			$sql .= " LEFT JOIN $wpdb->postmeta AS email_type ON email_type.post_id = posts.ID";	
			
			$sql .= " WHERE 1*1";	
			
			$sql .= " AND posts.post_type = '{$post_type}'";	
			$sql .= " AND posts.post_status = 'publish'";	
			$sql .= " AND email_type.meta_key = '_wcrpt_email_type'";
			$sql .= " AND email_type.meta_value = '{$email_type}'";	
			$posts = $wpdb->get_results($sql);
			return $posts;
			
		}
		
		function get_reports(){
			require_once('ic_woocommerce_auto_email_reports.php');
			$reports = new Ic_Wc_Auto_Email_Reports($this->constants);			
			return $reports;
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
		
	}
}
?>
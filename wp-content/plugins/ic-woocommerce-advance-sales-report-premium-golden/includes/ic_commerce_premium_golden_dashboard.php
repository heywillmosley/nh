<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
require_once('ic_commerce_premium_golden_fuctions.php');


if ( ! class_exists( 'IC_Commerce_Premium_Golden_Dashboard' ) ) {
	class IC_Commerce_Premium_Golden_Dashboard extends IC_Commerce_Premium_Golden_Fuctions{
		
		public $per_page = 0;
		
		public $per_page_default = 5;
		
		public $constants 	=	array();
		
		public $today 		=	'';		
		
		public function __construct($constants) {
			global $options;
			
			$this->constants		= $constants;			
			$options				= $this->constants['plugin_options'];			
			$this->per_page			= $this->constants['per_page_default'];
			$this->per_page_default	= $this->constants['per_page_default'];
			$this->today			= $this->constants['today_date'];//New Change ID 20140918
			$this->constants['datetime']= date_i18n("Y-m-d H:i:s");//New Change ID 20140918
		}
		
		function init(){
			global $options, $wpdb;
			global $start_date, $end_date, $woocommerce, $wpdb, $wp_locale;
			
			
			
			if(!isset($_REQUEST['page'])) return false;			
			//if(!$this->constants['plugin_parent_active']) return false;			
			$this->is_active();
			
			if(isset($_REQUEST['start_date']) and empty($_REQUEST['start_date']) == false){
				update_option($this->constants['plugin_key'].'_dashboard_page_saved_start_date',$_REQUEST['start_date']);
			}
			
			$saved_start_date 		= get_option($this->constants['plugin_key'].'_dashboard_page_saved_start_date',$this->constants['start_date']);
			
			if(empty($saved_start_date)){
				$saved_start_date 		= $this->constants['start_date'];
			}
			
			$start_date				= empty($_REQUEST['start_date']) ? $saved_start_date : $_REQUEST['start_date'];
			$end_date				= empty($_REQUEST['end_date']) ? $this->constants['end_date'] : $_REQUEST['end_date'];
			
			//New Change ID 20140918
			$shop_order_status		= apply_filters('ic_commerce_dashboard_page_default_order_status',$this->get_set_status_ids(),$this->constants);	
			$hide_order_status 		= apply_filters('ic_commerce_dashboard_page_default_hide_order_status',$this->constants['hide_order_status'],$this->constants);
			$start_date 			= apply_filters('ic_commerce_dashboard_page_default_start_date',$start_date,$this->constants);
			$end_date 				= apply_filters('ic_commerce_dashboard_page_default_end_date',$end_date,$this->constants);
			
			$this->yesterday 		= date("Y-m-d",strtotime("-1 day",strtotime($this->today)));
			$today_date				= $this->today;//New Change ID 20150209
			
			$this->constants['date_format'] 			= isset($this->constants['date_format']) ? $this->constants['date_format'] : get_option( 'date_format', "Y-m-d" );//New Change ID 20150209
			$this->constants['total_shop_day'] 			= isset($this->constants['total_shop_day']) ? $this->constants['total_shop_day'] : $this->get_total_shop_day($this->constants['plugin_key']);//New Change ID 20150210
			
			$date_format 			= $this->constants['date_format'];//New Change ID 20150209
			$total_shop_day 		= $this->get_date_diffrence($start_date, $end_date);//$this->constants['total_shop_day'];//New Change ID 20150210
			$total_shop_day_avg		= empty($total_shop_day) || $total_shop_day == 0 ? 1 : $total_shop_day;
			
			$filter_parameters 		= array('shop_order_status'=>$shop_order_status,'hide_order_status'=>$hide_order_status,'start_date' => $start_date,'end_date'=>$end_date,'today_date'=> $today_date,'date_format'=>$date_format,'total_shop_day' => $total_shop_day);
			
			$summary_start_date 	= $start_date;//New Change ID 20150209
			$summary_end_date 		= $end_date;//New Change ID 20150209
			
			//$order_totals  							= $this->get_order_totals('total',$shop_order_status,$start_date,$end_date);
			
			
			//$this->print_array($order_totals);
			
			//$gross_amount  							= $this->get_gross_amount('total',$shop_order_status,$start_date,$end_date);
			//$gross_total							= isset($gross_amount->total_amount) ? $gross_amount->total_amount : 0;
			
			//$this->print_array($gross_amount);
			//$this->print_array($gross_total);
			
			$total_cart_discount 					= $this->get_dashboard_cart_discount('total',$shop_order_status,$start_date,$end_date);
			
			$total_part_refunds						= $this->get_order_part_refunded('total',$shop_order_status,$start_date,$end_date);
			$today_part_refunds						= $this->get_order_part_refunded('today',$shop_order_status,$start_date,$end_date);
			$yesterday_part_refunds					= $this->get_order_part_refunded('yesterday',$shop_order_status,$start_date,$end_date);
			
			$total_full_refunds						= $this->get_order_full_refunded('total',$shop_order_status,$start_date,$end_date);
			$today_full_refunds						= $this->get_order_full_refunded('today',$shop_order_status,$start_date,$end_date);
			$yesterday_full_refunds					= $this->get_order_full_refunded('yesterday',$shop_order_status,$start_date,$end_date);
			
			$total_part_refund_amt 					= $this->get_value($total_part_refunds,'total_amount',0);
			$today_part_refund_amt 					= $this->get_value($today_part_refunds,'total_amount',0);
			$yesterday_part_refund_amt 				= $this->get_value($yesterday_part_refunds,'total_amount',0);
			
			$total_part_tax_refunded 				= $this->get_value($total_part_refunds,'total_tax_refunded',0);
			$total_part_shipping_tax_refunded 		= $this->get_value($total_part_refunds,'total_shipping_tax_refunded',0);
			$total_part_shipping_refunded 			= $this->get_value($total_part_refunds,'total_shipping_refunded',0);
			
			$today_part_tax_refunded 				= $this->get_value($today_part_refunds,'total_tax_refunded',0);
			$today_part_shipping_tax_refunded 		= $this->get_value($today_part_refunds,'total_shipping_tax_refunded',0);
			$today_part_shipping_refunded 			= $this->get_value($today_part_refunds,'total_shipping_refunded',0);
			
			$yesterday_part_tax_refunded 			= $this->get_value($yesterday_part_refunds,'total_tax_refunded',0);
			$yesterday_part_shipping_tax_refunded 	= $this->get_value($yesterday_part_refunds,'total_shipping_tax_refunded',0);
			$yesterday_part_shipping_refunded 		= $this->get_value($yesterday_part_refunds,'total_shipping_refunded',0);
			
			$total_full_refund_amt 					= $this->get_value($total_full_refunds,'total_amount',0);
			$today_full_refund_amt 					= $this->get_value($today_full_refunds,'total_amount',0);
			$yesterday_full_refund_amt 				= $this->get_value($yesterday_full_refunds,'total_amount',0);
			
			$total_full_tax_refunded 				= $this->get_value($total_full_refunds,'total_tax_refunded',0);
			$total_full_shipping_tax_refunded 		= $this->get_value($total_full_refunds,'total_shipping_tax_refunded',0);
			$total_full_shipping_refunded 			= $this->get_value($total_full_refunds,'total_shipping_refunded',0);
			$total_full_counts 						= $this->get_value($total_full_refunds,'total_count',0);
			
			$today_full_tax_refunded 				= $this->get_value($today_full_refunds,'total_tax_refunded',0);
			$today_full_shipping_tax_refunded 		= $this->get_value($today_full_refunds,'total_shipping_tax_refunded',0);
			$today_full_shipping_refunded 			= $this->get_value($today_full_refunds,'total_shipping_refunded',0);
			$today_full_counts 						= $this->get_value($today_full_refunds,'total_count',0);
			
			$yesterday_full_tax_refunded 			= $this->get_value($yesterday_full_refunds,'total_tax_refunded',0);
			$yesterday_full_shipping_tax_refunded 	= $this->get_value($yesterday_full_refunds,'total_shipping_tax_refunded',0);
			$yesterday_full_shipping_refunded 		= $this->get_value($yesterday_full_refunds,'total_shipping_refunded',0);
			$yesterday_full_counts 					= $this->get_value($yesterday_full_refunds,'total_count',0);
			
			$total_refund_amt 						= $total_part_refund_amt + $total_full_refund_amt;
			$today_refund_amt 						= $today_part_refund_amt + $today_full_refund_amt;
			$yesterday_refund_amt 					= $yesterday_part_refund_amt + $yesterday_full_refund_amt;
			
			$total_tax_refunded 					= $total_part_tax_refunded + $total_full_tax_refunded;
			$total_shipping_tax_refunded 			= $total_part_shipping_tax_refunded + $total_full_shipping_tax_refunded;
			$total_shipping_refunded 				= $total_part_shipping_refunded + $total_full_shipping_refunded;
			
			$today_tax_refunded 					= $today_part_tax_refunded + $today_full_tax_refunded;
			$today_shipping_tax_refunded 			= $today_part_shipping_tax_refunded + $today_full_shipping_tax_refunded;
			$today_shipping_refunded 				= $today_part_shipping_refunded + $today_full_shipping_refunded;
			
			$yesterday_tax_refunded 				= $yesterday_part_tax_refunded + $yesterday_full_tax_refunded;
			$yesterday_shipping_tax_refunded 		= $yesterday_part_shipping_tax_refunded + $yesterday_full_shipping_tax_refunded;
			$yesterday_shipping_refunded 			= $yesterday_part_shipping_refunded + $yesterday_full_shipping_refunded;
			
			
			//$gross_total							= ($gross_total) + ($total_refund_amt -($total_tax_refunded + $total_shipping_tax_refunded + $total_shipping_refunded));
			
			//echo $total = $total - $total_refund_amt ;
			//$total_part_refund_amt	= $this->get_part_order_refund_amount('total',$shop_order_status,$hide_order_status,$start_date,$end_date);
			//$today_part_refund_amt	= $this->get_part_order_refund_amount('today',$shop_order_status,$hide_order_status,$start_date,$end_date);
			
			$_total_orders 			= $this->get_total_order('total',$shop_order_status,$hide_order_status,$start_date,$end_date);
			$total_orders 			= $this->get_value($_total_orders,'total_count',0);
			$total_sales 			= $this->get_value($_total_orders,'total_amount',0);
			//$total_sales_avg		= $total_sales > 0 ? $total_sales/$total_orders : 0;
			
			$total_sales			= wc_format_decimal($total_sales - $total_refund_amt,2);
			
			$total_sales_avg		= $this->get_average($total_sales,$total_orders);//Modified Change ID 20150210
			$total_sales_avg_per_day= $this->get_average($total_sales,$total_shop_day_avg);//New Change ID 20150210
			
			$_todays_orders 		= $this->get_total_order('today',$shop_order_status,$hide_order_status,$start_date,$end_date);
			$total_today_order 		= $this->get_value($_todays_orders,'total_count',0);
			$total_today_sales 		= $this->get_value($_todays_orders,'total_amount',0);
			
			
			$total_categories  		= $this->get_total_categories_count();
			$total_products  		= $this->get_total_products_count();
			$total_orders_shipping	= $this->get_total_order_shipping_sales('total',$shop_order_status,$hide_order_status,$start_date,$end_date);		
			
			$total_refund 			= $total_full_refunds;
			$today_refund 			= $today_full_refunds;
			
			
			$total_orders_shipping	= wc_format_decimal($total_orders_shipping - $total_shipping_refunded,2);
			
			
			//$total_refund 			= $this->get_total_by_status("total","refunded",$hide_order_status,$start_date,$end_date);
			//$today_refund 			= $this->get_total_by_status("today","refunded",$hide_order_status,$start_date,$end_date);
			
			$fr_total_refund_amount = $this->get_value($total_refund,'total_amount',0);
			$total_refund_count 	= $this->get_value($total_refund,'total_count',0);
			
			
			$todays_refund_amount 	= $this->get_value($today_refund,'total_amount',0);
			$todays_refund_count 	= $this->get_value($today_refund,'total_count',0);
			
			$todays_refund_amount	= $todays_refund_amount + $today_part_refund_amt;
			$total_today_sales		= $total_today_sales - $todays_refund_amount;
			$total_today_order 		= $total_today_order;
			
			$total_today_avg		= $this->get_average($total_today_sales,$total_today_order);//Modified Change ID 20150210
			
			$total_refund_amount	= $fr_total_refund_amount + $total_part_refund_amt;			
			
			$total_orders 			= $total_orders;
			
			
			
			
			$today_coupon 			= $this->get_total_of_coupon("today",$shop_order_status,$hide_order_status,$start_date,$end_date);
			$total_coupon 			= $this->get_total_of_coupon("total",$shop_order_status,$hide_order_status,$start_date,$end_date);
			
			$today_coupon_amount 	= $this->get_value($today_coupon,'total_amount',0);
			$today_coupon_count 	= $this->get_value($today_coupon,'total_count',0);
			
			$total_coupon_amount 	= $this->get_value($total_coupon,'total_amount',0);
			$total_coupon_count 	= $this->get_value($total_coupon,'total_count',0);
			
			$today_order_tax 		= $this->get_total_of_order("today","_order_tax","tax",$shop_order_status,$hide_order_status,$start_date,$end_date);
			$total_order_tax 		= $this->get_total_of_order("total","_order_tax","tax",$shop_order_status,$hide_order_status,$start_date,$end_date);
			
			$today_ord_tax_amount	= $this->get_value($today_order_tax,'total_amount',0);
			$today_ord_tax_count 	= $this->get_value($today_order_tax,'total_count',0);
			
			$total_ord_tax_amount	= $this->get_value($total_order_tax,'total_amount',0);
			$total_ord_tax_count 	= $this->get_value($total_order_tax,'total_count',0);
			
			
			
			$total_ord_tax_amount	= wc_format_decimal($total_ord_tax_amount - $total_tax_refunded,2);
			
			$today_ord_shipping_tax	= $this->get_total_of_order("today","_order_shipping_tax","tax",$shop_order_status,$hide_order_status,$start_date,$end_date);
			$total_ord_shipping_tax	= $this->get_total_of_order("total","_order_shipping_tax","tax",$shop_order_status,$hide_order_status,$start_date,$end_date);
			
			$today_ordshp_tax_amount= $this->get_value($today_ord_shipping_tax,'total_amount',0);
			$today_ordshp_tax_count = $this->get_value($today_ord_shipping_tax,'total_count',0);
			
			$total_ordshp_tax_amount= $this->get_value($total_ord_shipping_tax,'total_amount',0);
			$total_ordshp_tax_count = $this->get_value($total_ord_shipping_tax,'total_count',0);
			
			$total_ordshp_tax_amount = wc_format_decimal($total_ordshp_tax_amount - $total_shipping_tax_refunded,2);
			
			$ytday_order_tax		= $this->get_total_of_order("yesterday","_order_tax","tax",$shop_order_status,$hide_order_status,$start_date,$end_date);
			$ytday_ord_shipping_tax	= $this->get_total_of_order("yesterday","_order_shipping_tax","tax",$shop_order_status,$hide_order_status,$start_date,$end_date);
			
			$ytday_tax_amount		= $this->get_value($ytday_order_tax,'total_amount',0);
			$ytday_ordshp_tax_amount= $this->get_value($ytday_ord_shipping_tax,'total_amount',0);
			$ytday_total_tax_amount = $ytday_tax_amount + $ytday_ordshp_tax_amount;
			
			$today_tax_amount		= $today_ordshp_tax_amount + $today_ord_tax_amount;
			$today_tax_count 		= '';
			$today_tax_amount 		= $today_tax_amount - ($today_part_tax_refunded + $today_part_shipping_tax_refunded);
			
		
			
			$today_ordshp_tax_amount = $today_ordshp_tax_amount - $today_part_shipping_tax_refunded;
			$today_ord_tax_amount	 = $today_ord_tax_amount - $today_part_tax_refunded;
				
			//$total_tax_amount		= ($total_ordshp_tax_amount + $total_ord_tax_amount) - ($total_part_tax_refunded + $total_part_shipping_tax_refunded);
			$total_tax_amount		= $total_ord_tax_amount + $total_ordshp_tax_amount;
			$total_tax_count 		= '';
			
			$gross_total			= $total_sales - ($total_orders_shipping + max(0,$total_ord_tax_amount) + max(0,$total_ordshp_tax_amount));
			
			//New Change ID 20140918 Start
			$last_order_details 	= $this->get_last_order_details($shop_order_status,$hide_order_status,$start_date,$end_date);
			
			$last_order_date 		= $this->get_value($last_order_details,'last_order_date','');
			$last_order_time		= strtotime($last_order_date);
			
			//$last_order_day 		= $this->get_value($last_order_details,'last_order_day','0');			
			//$date_format			= str_replace("F","M",get_option( 'date_format', "Y-m-d" ));			
			$short_date_format		= str_replace("F","M",$date_format);//Modified 20150209
			
			$current_time 			= strtotime($this->constants['datetime']);
			$last_order_time_diff	= $this->humanTiming($last_order_time, $current_time ,' ago');			
			
			$users_of_blog 			= count_users();
			$total_customer 		= isset($users_of_blog['avail_roles']['customer']) ? $users_of_blog['avail_roles']['customer'] : 0;
			//$total_today_customer 	= $this->get_total_today_order_customer('today');
			//$total_yesterday_customer 	= $this->get_total_today_order_customer('yesterday');
			
			//$total_reg_customer 	= $this->get_total_today_order_customer('total',false);
			$total_guest_customer 	= $this->get_total_today_order_customer('total',true);
			
			$today_reg_customer 			= $this->get_total_today_order_customer('today',false);
			$today_guest_customer 			= $this->get_total_today_order_customer('today',true);
			
			/*Todays Profit/Margin*/
			$today_margin_profit_amount 	= $this->get_cost_of_goods_items($type = "today", $shop_order_status,$hide_order_status,$start_date,$end_date);
			$today_margin_profit_amount 	= isset($today_margin_profit_amount->margin_profit_amount) 			? $today_margin_profit_amount->margin_profit_amount : 0;
			
					
			$yesterday_reg_customer			= $this->get_total_today_order_customer('yesterday',false);
			$yesterday_guest_customer		= $this->get_total_today_order_customer('yesterday',true);
			
			$yesterday_margin_profit_amount 	= $this->get_cost_of_goods_items($type = "yesterday", $shop_order_status,$hide_order_status,$start_date,$end_date);
			$yesterday_margin_profit_amount 	= isset($today_margin_profit_amount->margin_profit_amount) 			? $today_margin_profit_amount->margin_profit_amount : 0;
			//New Change ID 20140918 END
			
			//$default_date_rage_start_date	= isset($this->constants['default_date_rage_start_date']) ? $this->constants['default_date_rage_start_date'] : $this->constants['start_date'];
			//$default_date_rage_end_date		= isset($this->constants['default_date_rage_end_date']) ? $this->constants['default_date_rage_end_date'] : date_i18n('Y-12-31',strtotime('this month'));			
			//$current_date 					= date_i18n("Y-m-d");
			//$quick_date_change 				= $this->get_quick_dates($default_date_rage_start_date,$default_date_rage_end_date,$current_date);
			
			$yesterday_orders 			= $this->get_total_order('yesterday',$shop_order_status,$hide_order_status,$start_date,$end_date);
			$total_yesterday_order 		= $this->get_value($yesterday_orders,'total_count',0);
			$total_yesterday_sales 		= $this->get_value($yesterday_orders,'total_amount',0);
			//$total_yesterday_avg		= $total_yesterday_order > 0 ? $total_yesterday_sales/$total_yesterday_order : 0;
			$total_yesterday_avg		= $this->get_average($total_yesterday_sales,$total_yesterday_order);//Modified Change ID 20150210
			
			
			
			
			$yesterday_part_refund_amt	= $this->get_value($yesterday_part_refunds,'total_amount',0);
			
			//$yesterday_part_refund_amt	= $this->get_part_order_refund_amount('yesterday',$shop_order_status,$hide_order_status,$start_date,$end_date);
			//$yesterday_refund 			= $this->get_total_by_status("yesterday","refunded",$hide_order_status,$start_date,$end_date);
			$yesterday_refund 			= $yesterday_full_refunds;
			
			$yesterday_refund_amount 	= $this->get_value($yesterday_refund,'total_amount',0);
			$yesterday_refund_amount 	= $yesterday_refund_amount + $yesterday_part_refund_amt;
			
			$yesterday_coupon 			= $this->get_total_of_coupon("yesterday",$shop_order_status,$hide_order_status,$start_date,$end_date);
			$yesterday_coupon_amount 	= $this->get_value($yesterday_coupon,'total_amount',0);
			
			$yesterday_tax 				= $this->get_total_of_order("yesterday","_order_tax","tax",$shop_order_status,$hide_order_status,$start_date,$end_date);
			$yesterday_tax_amount 		= $this->get_value($yesterday_tax,'total_amount',0);
			
			$days_in_this_month 		= date('t', mktime(0, 0, 0, date('m', $current_time), 1, date('Y', $current_time)));
			//$todays_forecast  			= ($total_today_sales > 0 ) ? round(($total_today_sales/$days_in_this_month),2) : 0;
			
			//$avg_sales_per_day  = round(($results_month_to_date_sales[0]['month_to_date']/$today_arr['mday']),2);
			//$forcasted_sales 	= $avg_sales_per_day * $days_in_this_month;
			
			$cur_projected_sales_year	= $this->get_number_only('cur_projected_sales_year',date('Y',$current_time));
			$projected_start_date		= $cur_projected_sales_year."-01-01";
			$projected_end_date			= $cur_projected_sales_year."-12-31";
			
			$projected_total_orders		= $this->get_total_order('total',$shop_order_status,$hide_order_status,$projected_start_date,$projected_end_date);
			$projected_order_amount 	= $this->get_value($projected_total_orders,'total_amount',0);
			$projected_order_count 		= $this->get_value($projected_total_orders,'total_count',0);
			
			$total_projected_amount		= $this->get_number_only('total_projected_amount',0);
			$total_projected_amount_option = $this->constants['plugin_key'].'_total_projected_amount_'.$cur_projected_sales_year;
			$total_projected_amount		= get_option($total_projected_amount_option,0);
			//$projected_percentage		= ($projected_order_amount > 0 and $total_projected_amount> 0) ? (($projected_order_amount/$total_projected_amount)*100) : 0;
			$projected_percentage		= $this->get_percentage($projected_order_amount,$total_projected_amount);//Added 20150206
			
			$projected_start_date_cm	= date($cur_projected_sales_year.'-m-01',$current_time);
			$projected_end_date_cm		= date($cur_projected_sales_year.'-m-t',$current_time);
			$projected_sales_month		= date('F',$current_time);
			$projected_sales_month_shrt	= date('M',$current_time);
			
			$projected_total_orders_cm	= $this->get_total_order('total',$shop_order_status,$hide_order_status,$projected_start_date_cm,$projected_end_date_cm);
			$projected_order_amount_cm 	= $this->get_value($projected_total_orders_cm,'total_amount',0);
			$projected_order_count_cm 	= $this->get_value($projected_total_orders_cm,'total_count',0);
			
			$projected_sales_year_option= $this->constants['plugin_key'].'_projected_amount_'.$cur_projected_sales_year;
			$projected_amounts 			= get_option($projected_sales_year_option,array());
			$total_projected_amount_cm	= isset($projected_amounts[$projected_sales_month]) ? $projected_amounts[$projected_sales_month] : 100;
			//$projected_percentage_cm	= ($projected_order_amount_cm > 0 and $total_projected_amount_cm> 0) ? (($projected_order_amount_cm/$total_projected_amount_cm)*100) : 0;
			$projected_percentage_cm	= $this->get_percentage($projected_order_amount_cm,$total_projected_amount_cm);//Added 20150206
			
			$this_month_date			= date('d',$current_time);
			//$per_day_sales_amount		= round(($projected_order_amount_cm/$this_month_date),2);
			$per_day_sales_amount		= $this->get_average($projected_order_amount_cm,$this_month_date);//Modified Change ID 20150210
			$per_day_sales_amount		= round(($per_day_sales_amount),2);//Modified Change ID 20150210
			$sales_forcasted 			= $per_day_sales_amount * $days_in_this_month;
			
			$current_total_sales_apd	= $this->get_average($projected_order_amount_cm,$this_month_date);//Added Change ID 20150210
			
			$summary_date_title = sprintf(__('Summary From %1$s To %2$s'), date($date_format, strtotime($summary_start_date)),date($date_format, strtotime($summary_end_date)));
			
			$constants = array(
				'shop_order_status'			=>	$shop_order_status,
				'hide_order_status'			=>	$hide_order_status,
				'start_date'				=>	$start_date,
				'end_date'					=>	$end_date,
				'date_format'				=>	$date_format,
				'today_date'				=>	$this->today,
				'yesterday_date'			=>	$this->yesterday,
				'constants'					=>	$this->constants,
				'summary_date_title'		=>	$summary_date_title,
				'plugin_url'				=>	$this->constants['plugin_url'],
				'total_orders_shipping'		=>  $total_orders_shipping
			);
			
			$billing_or_shipping	= $this->get_setting('billing_or_shipping',$this->constants['plugin_options'], 'billing');
			
			do_action('ic_commerce_dashboard_page_init', $constants);
			
			$full_refund_amount			= $total_full_refund_amt;
			$total_refund_amount		= $total_refund_amt;
			
			$show_sections				= $this->get_setting('show_sections',			$this->constants['plugin_options'], 0);
			$show_graph					= $this->get_setting('show_graph',				$this->constants['plugin_options'], 0);
			$show_monthly_summary		= $this->get_setting('show_monthly_summary',	$this->constants['plugin_options'], 0);
			$show_map					= $this->get_setting('show_map',				$this->constants['plugin_options'], 0);
			$show_order_summary			= $this->get_setting('show_order_summary',		$this->constants['plugin_options'], 0);
			$show_sales_order_status	= $this->get_setting('show_sales_order_status',	$this->constants['plugin_options'], 0);
			$show_top_products			= $this->get_setting('show_top_products',		$this->constants['plugin_options'], 0);
			$show_top_categories		= $this->get_setting('show_top_categories',		$this->constants['plugin_options'], 0);
			$show_top_countries			= $this->get_setting('show_top_countries',		$this->constants['plugin_options'], 0);
			$show_top_states			= $this->get_setting('show_top_states',			$this->constants['plugin_options'], 0);
			$show_recent_orders			= $this->get_setting('show_recent_orders',		$this->constants['plugin_options'], 0);
			$show_top_customers			= $this->get_setting('show_top_customers',		$this->constants['plugin_options'], 0);
			$show_top_coupons			= $this->get_setting('show_top_coupons',		$this->constants['plugin_options'], 0);
			$show_top_payments			= $this->get_setting('show_top_payments',		$this->constants['plugin_options'], 0);
			$plugin_url 				= $this->constants['plugin_url'];
			
			
			$show_monthly_summary_bottom= $this->get_setting('show_monthly_summary_bottom',	$this->constants['plugin_options'], 0);
			$show_map_bottom			= $this->get_setting('show_map_bottom',				$this->constants['plugin_options'], 0);
			
			
			$summary_boxes = array();
			$summary_boxes['todays_summary']['todays_total_sales']['title'] 	= __( 'Todays Total Sales', 'icwoocommerce_textdomains');
			$summary_boxes['todays_summary']['todays_total_sales']['amount']	= $total_today_sales;
			$summary_boxes['todays_summary']['todays_total_sales']['count'] 	= $total_today_order;
			$summary_boxes['todays_summary']['todays_total_sales']['icon']		= $plugin_url.'/assets/images/icons/sales-icon.png';
			$summary_boxes['todays_summary']['todays_total_sales']['progres'] 	= $this->get_progres_content($total_today_sales,$total_yesterday_sales);
			$summary_boxes['todays_summary']['todays_total_sales']['class'] 	= 'ic_block-light-green';
			
			$summary_boxes['todays_summary']['todays_average_sales']['title'] 	= __( 'Todays Average Sales', 'icwoocommerce_textdomains');
			$summary_boxes['todays_summary']['todays_average_sales']['amount']	= $total_today_avg;
			$summary_boxes['todays_summary']['todays_average_sales']['count'] 	= '';
			$summary_boxes['todays_summary']['todays_average_sales']['icon']	= $plugin_url.'/assets/images/icons/average-icon.png';
			$summary_boxes['todays_summary']['todays_average_sales']['progres'] = $this->get_progres_content($total_today_avg,$total_yesterday_avg);
			$summary_boxes['todays_summary']['todays_average_sales']['class'] 	= 'ic_block-brown';
			
			$summary_boxes['todays_summary']['todays_total_refund']['title'] 	= __( 'Todays Total Refund', 'icwoocommerce_textdomains');
			$summary_boxes['todays_summary']['todays_total_refund']['amount']	= -$todays_refund_amount;
			$summary_boxes['todays_summary']['todays_total_refund']['count'] 	= $todays_refund_count;
			$summary_boxes['todays_summary']['todays_total_refund']['icon']	= $plugin_url.'/assets/images/icons/refund-icon.png';
			$summary_boxes['todays_summary']['todays_total_refund']['progres'] = $this->get_progres_content($todays_refund_amount,$yesterday_refund_amount);
			$summary_boxes['todays_summary']['todays_total_refund']['class'] 	= 'ic_block-purple';
			
			$summary_boxes['todays_summary']['todays_total_coupons']['title'] 	= __( 'Todays Total Coupons', 'icwoocommerce_textdomains');
			$summary_boxes['todays_summary']['todays_total_coupons']['amount']	= $today_coupon_amount;
			$summary_boxes['todays_summary']['todays_total_coupons']['count'] 	= $today_coupon_count;
			$summary_boxes['todays_summary']['todays_total_coupons']['icon']	= $plugin_url.'/assets/images/icons/coupon-icon.png';
			$summary_boxes['todays_summary']['todays_total_coupons']['progres'] = $this->get_progres_content($today_coupon_amount,$yesterday_coupon_amount);
			$summary_boxes['todays_summary']['todays_total_coupons']['class'] 	= 'ic_block-green3';
			
			$summary_boxes['todays_summary']['todays_order_tax']['title'] 	= __( 'Todays Order Tax', 'icwoocommerce_textdomains');
			$summary_boxes['todays_summary']['todays_order_tax']['amount']	= $today_ord_tax_amount;
			$summary_boxes['todays_summary']['todays_order_tax']['count'] 	= $today_ord_tax_count;
			$summary_boxes['todays_summary']['todays_order_tax']['icon']	= $plugin_url.'/assets/images/icons/tax-icon.png';
			$summary_boxes['todays_summary']['todays_order_tax']['progres'] = $this->get_progres_content($today_tax_amount,$ytday_tax_amount);
			$summary_boxes['todays_summary']['todays_order_tax']['class'] 	= 'ic_block-grey';
			
			$summary_boxes['todays_summary']['todays_shipping_tax']['title'] 	= __( 'Todays Shipping Tax', 'icwoocommerce_textdomains');
			$summary_boxes['todays_summary']['todays_shipping_tax']['amount']	= $today_ordshp_tax_amount;
			$summary_boxes['todays_summary']['todays_shipping_tax']['count'] 	= $today_ordshp_tax_count;
			$summary_boxes['todays_summary']['todays_shipping_tax']['icon']	= $plugin_url.'/assets/images/icons/tax-icon.png';
			$summary_boxes['todays_summary']['todays_shipping_tax']['progres'] = $this->get_progres_content($today_tax_amount,$ytday_ordshp_tax_amount);
			$summary_boxes['todays_summary']['todays_shipping_tax']['class'] 	= 'ic_block-yellow';
			
			$summary_boxes['todays_summary']['todays_total_tax']['title'] 	= __( 'Todays Total Tax', 'icwoocommerce_textdomains');
			$summary_boxes['todays_summary']['todays_total_tax']['amount']	= $today_tax_amount;
			$summary_boxes['todays_summary']['todays_total_tax']['count'] 	= $today_tax_count;
			$summary_boxes['todays_summary']['todays_total_tax']['icon']	= $plugin_url.'/assets/images/icons/tax-icon.png';
			$summary_boxes['todays_summary']['todays_total_tax']['progres'] = $this->get_progres_content($today_tax_amount,$ytday_total_tax_amount);
			$summary_boxes['todays_summary']['todays_total_tax']['class'] 	= 'ic_block-blue-light';
			
			$summary_boxes['todays_summary']['todays_registered_customers']['title'] 	= __( 'Todays Registered Customers', 'icwoocommerce_textdomains');
			$summary_boxes['todays_summary']['todays_registered_customers']['amount']	= '';
			$summary_boxes['todays_summary']['todays_registered_customers']['count'] 	= $today_reg_customer;
			$summary_boxes['todays_summary']['todays_registered_customers']['icon']	= $plugin_url.'/assets/images/icons/customers-icon.png';
			$summary_boxes['todays_summary']['todays_registered_customers']['progres'] = $this->get_progres_content($today_reg_customer,$yesterday_reg_customer);
			$summary_boxes['todays_summary']['todays_registered_customers']['class'] 	= 'ic_block-red';
			
			/*
			$summary_boxes['todays_summary']['todays_registered_customers']['title'] 	= __( 'Todays Registered Customers', 'icwoocommerce_textdomains');
			$summary_boxes['todays_summary']['todays_registered_customers']['amount']	= '';
			$summary_boxes['todays_summary']['todays_registered_customers']['count'] 	= $today_reg_customer;
			$summary_boxes['todays_summary']['todays_registered_customers']['icon']	= $plugin_url.'/assets/images/icons/customers-icon.png';
			$summary_boxes['todays_summary']['todays_registered_customers']['progres'] = $this->get_progres_content($today_reg_customer,$yesterday_reg_customer);
			$summary_boxes['todays_summary']['todays_registered_customers']['class'] 	= 'ic_block-red';
			*/
			
			$summary_boxes['total_summary']['total_sales']['title'] 	= __( 'Total Sales', 'icwoocommerce_textdomains');
			$summary_boxes['total_summary']['total_sales']['amount']	= $total_sales;
			$summary_boxes['total_summary']['total_sales']['count'] 	= $total_orders;
			$summary_boxes['total_summary']['total_sales']['icon']	= $plugin_url.'/assets/images/icons/sales-icon.png';
			$summary_boxes['total_summary']['total_sales']['progres'] = '';
			$summary_boxes['total_summary']['total_sales']['class'] 	= 'ic_block-orange';
			
			$summary_boxes['total_summary']['total_tax']['title'] 	= __( 'Total Tax', 'icwoocommerce_textdomains');
			$summary_boxes['total_summary']['total_tax']['amount']	= $total_tax_amount;
			$summary_boxes['total_summary']['total_tax']['count'] 	= $total_tax_count;
			$summary_boxes['total_summary']['total_tax']['icon']	= $plugin_url.'/assets/images/icons/tax-icon.png';
			$summary_boxes['total_summary']['total_tax']['progres'] = '';
			$summary_boxes['total_summary']['total_tax']['class'] 	= 'ic_block-maroon';
			
			$summary_boxes['total_summary']['total_coupons']['title'] 	= __( 'Total Coupons', 'icwoocommerce_textdomains');
			$summary_boxes['total_summary']['total_coupons']['amount']	= $total_coupon_amount;
			$summary_boxes['total_summary']['total_coupons']['count'] 	= $total_coupon_count;
			$summary_boxes['total_summary']['total_coupons']['icon']	= $plugin_url.'/assets/images/icons/coupon-icon.png';
			$summary_boxes['total_summary']['total_coupons']['progres'] = '';
			$summary_boxes['total_summary']['total_coupons']['class'] 	= 'ic_block-blue-light';
			
			$summary_boxes['total_summary']['total_registered_customers']['title'] 	= __( 'Total Registered Customers', 'icwoocommerce_textdomains');
			$summary_boxes['total_summary']['total_registered_customers']['amount']	= '';
			$summary_boxes['total_summary']['total_registered_customers']['count'] 	= $total_customer;
			$summary_boxes['total_summary']['total_registered_customers']['icon']	= $plugin_url.'/assets/images/icons/customers-icon.png';
			$summary_boxes['total_summary']['total_registered_customers']['progres'] = '';
			$summary_boxes['total_summary']['total_registered_customers']['class'] 	= 'ic_block-pink';
			
			$summary_boxes['total_summary']['total_guest_customers']['title'] 	= __( 'Total Guest Customers', 'icwoocommerce_textdomains');
			$summary_boxes['total_summary']['total_guest_customers']['amount']	= '';
			$summary_boxes['total_summary']['total_guest_customers']['count'] 	= $total_guest_customer;
			$summary_boxes['total_summary']['total_guest_customers']['icon']	= $plugin_url.'/assets/images/icons/customers-icon.png';
			$summary_boxes['total_summary']['total_guest_customers']['progres'] = '';
			$summary_boxes['total_summary']['total_guest_customers']['class'] 	= 'ic_block-brown';
			
			$summary_boxes['total_summary']['order_tax']['title'] 	= __( 'Order Tax', 'icwoocommerce_textdomains');
			$summary_boxes['total_summary']['order_tax']['amount']	= $total_ord_tax_amount;
			$summary_boxes['total_summary']['order_tax']['count'] 	= $total_ord_tax_count;
			$summary_boxes['total_summary']['order_tax']['icon']	= $plugin_url.'/assets/images/icons/tax-icon.png';
			$summary_boxes['total_summary']['order_tax']['progres'] = '';
			$summary_boxes['total_summary']['order_tax']['class'] 	= 'ic_block-green2';
			
			$summary_boxes['total_summary']['order_shipping_tax']['title'] 	= __( 'Order Shipping Tax', 'icwoocommerce_textdomains');
			$summary_boxes['total_summary']['order_shipping_tax']['amount']	= $total_ordshp_tax_amount;
			$summary_boxes['total_summary']['order_shipping_tax']['count'] 	= $total_ordshp_tax_count;
			$summary_boxes['total_summary']['order_shipping_tax']['icon']	= $plugin_url.'/assets/images/icons/tax-icon.png';
			$summary_boxes['total_summary']['order_shipping_tax']['progres'] = '';
			$summary_boxes['total_summary']['order_shipping_tax']['class'] 	= 'ic_block-pink3';
			
			$summary_boxes['total_summary']['order_shipping_total']['title'] 	= __( 'Order Shipping Total', 'icwoocommerce_textdomains');
			$summary_boxes['total_summary']['order_shipping_total']['amount']	= $total_orders_shipping;
			$summary_boxes['total_summary']['order_shipping_total']['count'] 	= '';
			$summary_boxes['total_summary']['order_shipping_total']['icon']	= $plugin_url.'/assets/images/icons/order-icon.png';
			$summary_boxes['total_summary']['order_shipping_total']['progres'] = '';
			$summary_boxes['total_summary']['order_shipping_total']['class'] 	= 'ic_block-skyblue-light';
			
			$summary_boxes['total_summary']['last_order_date']['title'] 	= __( 'Last Order Date', 'icwoocommerce_textdomains');
			$summary_boxes['total_summary']['last_order_date']['amount']	= date($short_date_format,$last_order_time);
			$summary_boxes['total_summary']['last_order_date']['count'] 	= $last_order_time_diff;
			$summary_boxes['total_summary']['last_order_date']['icon']	= $plugin_url.'/assets/images/icons/calendar-icon.png';
			$summary_boxes['total_summary']['last_order_date']['progres'] = '';
			$summary_boxes['total_summary']['last_order_date']['class'] 	= 'ic_block-red';
			
			$summary_boxes['total_summary']['part_refund']['title'] 	= __( 'Part Refund', 'icwoocommerce_textdomains');
			$summary_boxes['total_summary']['part_refund']['amount']	= -$total_part_refund_amt;
			$summary_boxes['total_summary']['part_refund']['count'] 	= '';
			$summary_boxes['total_summary']['part_refund']['icon']	= $plugin_url.'/assets/images/icons/refund-icon.png';
			$summary_boxes['total_summary']['part_refund']['progres'] = '';
			$summary_boxes['total_summary']['part_refund']['class'] 	= 'ic_block-pink3';
			
			$summary_boxes['total_summary']['full_refund']['title'] 	= __( 'Full Refund', 'icwoocommerce_textdomains');
			$summary_boxes['total_summary']['full_refund']['amount']	= -$full_refund_amount;
			$summary_boxes['total_summary']['full_refund']['count'] 	= '';
			$summary_boxes['total_summary']['full_refund']['icon']	= $plugin_url.'/assets/images/icons/refund-icon.png';
			$summary_boxes['total_summary']['full_refund']['progres'] = '';
			$summary_boxes['total_summary']['full_refund']['class'] 	= 'ic_block-green2';
			
			$summary_boxes['total_summary']['total_refund']['title'] 	= __( 'Total Refund', 'icwoocommerce_textdomains');
			$summary_boxes['total_summary']['total_refund']['amount']	= -$total_refund_amount;
			$summary_boxes['total_summary']['total_refund']['count'] 	= $total_refund_count;
			$summary_boxes['total_summary']['total_refund']['icon']	= $plugin_url.'/assets/images/icons/refund-icon.png';
			$summary_boxes['total_summary']['total_refund']['progres'] = '';
			$summary_boxes['total_summary']['total_refund']['class'] 	= 'ic_block-green3';
			
			$summary_boxes['other_summary']['cur_yr_proj_sales']['title'] 	= __( "Cur. Yr Proj. Sales ({$cur_projected_sales_year})", 'icwoocommerce_textdomains');
			$summary_boxes['other_summary']['cur_yr_proj_sales']['amount']	= $total_projected_amount;
			$summary_boxes['other_summary']['cur_yr_proj_sales']['count'] 	= sprintf("%.2f%%", $projected_percentage);
			$summary_boxes['other_summary']['cur_yr_proj_sales']['icon']	= $plugin_url.'/assets/images/icons/sales-icon.png';
			$summary_boxes['other_summary']['cur_yr_proj_sales']['progres'] = '';
			$summary_boxes['other_summary']['cur_yr_proj_sales']['class'] 	= 'ic_block-green';
			
			$summary_boxes['other_summary']['current_yr_proj_sales']['title'] 	= __( "Current Year Sales ({$cur_projected_sales_year})", 'icwoocommerce_textdomains');
			$summary_boxes['other_summary']['current_yr_proj_sales']['amount']	= $projected_order_amount;
			$summary_boxes['other_summary']['current_yr_proj_sales']['count'] 	= $projected_order_count;
			$summary_boxes['other_summary']['current_yr_proj_sales']['icon']	= $plugin_url.'/assets/images/icons/sales-icon.png';
			$summary_boxes['other_summary']['current_yr_proj_sales']['progres'] = '';
			$summary_boxes['other_summary']['current_yr_proj_sales']['class'] 	= 'ic_block-light-green';
			
			$summary_boxes['other_summary']['average_sales_per_order']['title'] 	= __( 'Average Sales Per Order', 'icwoocommerce_textdomains');
			$summary_boxes['other_summary']['average_sales_per_order']['amount']	= $total_sales_avg;
			$summary_boxes['other_summary']['average_sales_per_order']['count'] 	= '';
			$summary_boxes['other_summary']['average_sales_per_order']['icon']	= $plugin_url.'/assets/images/icons/average-icon.png';
			$summary_boxes['other_summary']['average_sales_per_order']['progres'] = '';
			$summary_boxes['other_summary']['average_sales_per_order']['class'] 	= 'ic_block-brown';
			//$summary_boxes['other_summary']['average_sales_per_order']['break_bottom'] 	= ' <div class="clearfix"></div>';
			
			$summary_boxes['other_summary']['average_sales_per_day']['title'] 	= __( 'Average Sales Per Day', 'icwoocommerce_textdomains');
			$summary_boxes['other_summary']['average_sales_per_day']['amount']	= $total_sales_avg_per_day;
			$summary_boxes['other_summary']['average_sales_per_day']['count'] 	= '';
			$summary_boxes['other_summary']['average_sales_per_day']['icon']	= $plugin_url.'/assets/images/icons/average-icon.png';
			$summary_boxes['other_summary']['average_sales_per_day']['progres'] = '';
			$summary_boxes['other_summary']['average_sales_per_day']['class'] 	= 'ic_block-blue';
			
			$summary_boxes['other_summary']['cur_month_proj_sales']['title'] 	= __( "Cur. Month Proj. Sales ({$projected_sales_month_shrt} {$cur_projected_sales_year})", 'icwoocommerce_textdomains');
			$summary_boxes['other_summary']['cur_month_proj_sales']['amount']	= $total_projected_amount_cm;
			$summary_boxes['other_summary']['cur_month_proj_sales']['count'] 	= $projected_order_count_cm;
			$summary_boxes['other_summary']['cur_month_proj_sales']['icon']	= $plugin_url.'/assets/images/icons/sales-icon.png';
			$summary_boxes['other_summary']['cur_month_proj_sales']['progres'] = '';
			$summary_boxes['other_summary']['cur_month_proj_sales']['class'] 	= 'ic_block-purple';
			
			$summary_boxes['other_summary']['cur_month_sales']['title'] 	= __( "Current Month Sales ({$projected_sales_month_shrt} {$cur_projected_sales_year})", 'icwoocommerce_textdomains');
			$summary_boxes['other_summary']['cur_month_sales']['amount']	= $projected_order_amount_cm;
			$summary_boxes['other_summary']['cur_month_sales']['count'] 	= sprintf("%.2f%%", $projected_percentage_cm);
			$summary_boxes['other_summary']['cur_month_sales']['icon']	= $plugin_url.'/assets/images/icons/sales-icon.png';
			$summary_boxes['other_summary']['cur_month_sales']['progres'] = '';
			$summary_boxes['other_summary']['cur_month_sales']['class'] 	= 'ic_block-yellow';
			
			$summary_boxes['other_summary']['pr_average_sales_per_day']['title'] 	= __( "({$projected_sales_month_shrt} {$cur_projected_sales_year}) Average Sales/Day", 'icwoocommerce_textdomains');
			$summary_boxes['other_summary']['pr_average_sales_per_day']['amount']	= $current_total_sales_apd;
			$summary_boxes['other_summary']['pr_average_sales_per_day']['count'] 	= '';
			$summary_boxes['other_summary']['pr_average_sales_per_day']['icon']	= $plugin_url.'/assets/images/icons/sales-icon.png';
			$summary_boxes['other_summary']['pr_average_sales_per_day']['progres'] = '';
			$summary_boxes['other_summary']['pr_average_sales_per_day']['class'] 	= 'ic_block-brown2';
			
			$summary_boxes['other_summary']['forecasted_sales']['title'] 	= __( "({$projected_sales_month_shrt} {$cur_projected_sales_year}) Forecasted Sales", 'icwoocommerce_textdomains');
			$summary_boxes['other_summary']['forecasted_sales']['amount']	= $sales_forcasted;
			$summary_boxes['other_summary']['forecasted_sales']['count'] 	= '';
			$summary_boxes['other_summary']['forecasted_sales']['icon']	= $plugin_url.'/assets/images/icons/forecast-icon.png';
			$summary_boxes['other_summary']['forecasted_sales']['progres'] = '';
			$summary_boxes['other_summary']['forecasted_sales']['class'] 	= 'ic_block-orange';
			
			?>
			<div class="dashboard_filters success">
				<form method="post">                            
					<div class="form-table">
						<div class="form-group">
							<div class="FormRow">
								<div class="label-text"><label for="start_date">Start Date:</label></div>
								<div class="input-text"><input type="text" name="start_date" id="start_date" value="<?php echo $start_date?>" /></div>
							</div>
							<div class="FormRow">
								<div class="label-text"><label for="end_date">End Date:</label></div>
								<div class="input-text"><input type="text" name="end_date" id="end_date" value="<?php echo $end_date?>" /></div>
							</div>
							<div class="submit_buttons"><input type="submit" name="dashboard_btn" class="button onformprocess" id="dashboard_btn" value="Submit" /></div>
						</div>
					</div>
				</form>
			</div>
			
			<div class="SubTitle"><span><?php echo sprintf(__('Summary From %1$s To %2$s'), date($date_format, strtotime($summary_start_date)),date($date_format, strtotime($summary_end_date))); ?></span></div>
			<!--Tab Interface-->
            <div class="summary_div">
                <div class="responsive-tabs-default">
                    <ul class="responsive-tabs">
                        <?php do_action('ic_commerce_dashboard_page_summary_button_start', $constants, $this->constants);?>
                        <li><a href="#today_summary" id="tablink1" data-tab="#today_summary" target="_self"><i class="fa fa-bar-chart"></i>&nbsp;&nbsp;<?php _e( 'Todays Summary', 'icwoocommerce_textdomains'); ?></a></li>
                        <li><a href="#total_summary" id="tablink2" data-tab="#total_summary" target="_self"><i class="fa fa-area-chart"></i> &nbsp;&nbsp;<?php _e( 'Total Summary', 'icwoocommerce_textdomains'); ?></a></li>
                        <li><a href="#other_summary" id="tablink3" data-tab="#other_summary" target="_self"><i class="fa fa-pie-chart"></i> &nbsp;&nbsp;<?php _e( 'Other Summary', 'icwoocommerce_textdomains'); ?></a></li>
                        <?php do_action('ic_commerce_dashboard_page_summary_button_end', $constants, $this->constants);?>
                    </ul>
                    <div class="clearfix"></div>
                    <div class="responsive-tabs-content">
                    	
                        <?php do_action('ic_commerce_dashboard_page_summary_content_start', $constants, $this->constants);?>
                        
                        <!-- today summary =================================================== -->
                        <div id="today_summary" class="responsive-tabs-panel">
                        	<div class="row">
                            	 <?php
								 
								 	do_action('ic_commerce_dashboard_page_today_summary_start', $constants, $this->constants);
                                 	
									$todays_summary = apply_filters('ic_commerce_dashboard_page_today_summary',$summary_boxes['todays_summary'],$summary_boxes, $constants, $this->constants);
									$output 		= "";
									foreach($todays_summary as $today_summary){
										 $output .= isset($today_summary['break_top']) ? $today_summary['break_top'] : '';
										 $output .= '<div class="col-md-3">';
											$output .= '<div class="ic_block '.$today_summary['class'].'">';
												$output .= '<div class="ic_block-content">';
													$output .= '<h2><span>'.$today_summary['title'].'</span></h2>';
													$output .= '<div class="ic_stat_content">';
														$output .= '<p class="ic_stat">';
															if ( $today_summary['amount'] > 0 ){
																$output .=  $this->price($today_summary['amount']);
															}else{
                                                           		$output .=  __( '0', 'icwoocommerce_textdomains');
                                                            }
															$output .= '<span class="ic_count">#'.$today_summary['count'].'</span>';
														$output .= '</p>';
														$output .= '<img src="'.$today_summary['icon'].'" alt="" />';
													$output .= '</div>';
													$output .= $today_summary['progres'];
												$output .= '</div>';
											$output .= '</div>';
										$output .= '</div>';
										$output .= isset($today_summary['break_bottom']) ? $today_summary['break_bottom'] : '';
									}
									$output = apply_filters('ic_commerce_dashboard_page_today_summary_output',$output,$summary_boxes['todays_summary'],  $summary_boxes, $constants, $this->constants);
									echo $output;
								 	do_action('ic_commerce_dashboard_page_today_summary_end', $constants, $this->constants);
								?>                                
                                <div class="clearfix"></div>
                            </div>
                        </div>

                        <!-- total summary =================================================== -->
                        <div id="total_summary" class="responsive-tabs-panel">
                            <div class="responsive-tab-title"></div>
                            <div class="ic_dashboard_summary_box">
                            	<div class="row">
                                	
                                    <?php do_action('ic_commerce_dashboard_page_total_summary_start', $constants, $this->constants);
									
									
									$total_summary = apply_filters('ic_commerce_dashboard_page_total_summary',$summary_boxes['total_summary'],$summary_boxes, $constants, $this->constants);
									$output 		= "";
									
									foreach($total_summary as $box_key => $today_summary){
										 $output .= '<div class="col-md-3">';
											$output .= '<div class="ic_block '.$today_summary['class'].'">';
												$output .= '<div class="ic_block-content">';
													$output .= '<h2><span>'.$today_summary['title'].'</span></h2>';
													$output .= '<div class="ic_stat_content">';
														$output .= '<p class="ic_stat">';															
															switch($box_key){
																case "total_registered_customers":
																case "total_guest_customers":
																	if (!empty($today_summary['count'])){
																		if($today_summary['count']>0){
																			$output .= "#".$today_summary['count'];
																		}else{
																			$output .= '#';
																		}
																	}else{
																		$output .=  '';
																	}
																	break;
																case "last_order_date":
																	if (!empty($today_summary['amount'])){
																		$output .= "".$today_summary['amount'];
																	}
																	if (!empty($today_summary['count'])){
																		if($today_summary['count']>0){
																			$output .= '<span class="ic_count">'.$today_summary['count'].'</span>';
																		}else{
																			$output .= '';
																		}
																	}
																	break;
																default:
																	
																	if ( $today_summary['amount'] > 0 || $today_summary['amount'] < 0){
																		$output .=  $this->price($today_summary['amount']);
																	}else{
																		$output .=  __( '0', 'icwoocommerce_textdomains');
																	}
																	if (!empty($today_summary['count'])){
																		if($today_summary['count']>0){
																			$output .= '<span class="ic_count">#'.$today_summary['count'].'</span>';
																		}else{
																			$output .= '#';
																		}
																	}
																	break;
																	
															}
														$output .= '</p>';
														$output .= '<img src="'.$today_summary['icon'].'" alt="" />';
													$output .= '</div>';													
												$output .= '</div>';
											$output .= '</div>';
										$output .= '</div>';
									}
									$output = apply_filters('ic_commerce_dashboard_page_total_summary_output',$output,$summary_boxes['total_summary'],$summary_boxes, $constants, $this->constants);
									echo $output;
									do_action('ic_commerce_dashboard_page_total_summary_end', $constants, $this->constants);?>
                                </div>
                            </div>
                            <div class="clearfix"></div>
                        </div>
                        
                        <!-- other summary =================================================== -->
                        <div id="other_summary" class="responsive-tabs-panel">
                            <div class="responsive-tab-title"></div>
                            <div class="ic_dashboard_summary_box">
                                <div class="row">
                                
                                	<?php do_action('ic_commerce_dashboard_page_other_summary_start', $constants, $this->constants);
									
									
									
									$other_summary = apply_filters('ic_commerce_dashboard_page_other_summary',$summary_boxes['other_summary'],$summary_boxes, $constants, $this->constants);
									$output 		= "";
									foreach($other_summary as $box_key => $today_summary){
										 $output .= isset($today_summary['break_top']) ? $today_summary['break_top'] : '';
										 $output .= '<div class="col-md-3">';
											$output .= '<div class="ic_block '.$today_summary['class'].'">';
												$output .= '<div class="ic_block-content">';
													$output .= '<h2><span>'.$today_summary['title'].'</span></h2>';
													$output .= '<div class="ic_stat_content">';
														$output .= '<p class="ic_stat">';															
															
																	
																	if ( $today_summary['amount'] > 0 ){
																		$output .=  $this->price($today_summary['amount']);
																	}else{
																		$output .=  __( '0', 'icwoocommerce_textdomains');
																	}
																	
																	if (!empty($today_summary['count'])){
																		if($today_summary['count']>0){
																			$output .= '<span class="ic_count">#'.$today_summary['count'].'</span>';
																		}else{
																			$output .= '#';
																		}
																	}
																	
														$output .= '</p>';
														$output .= '<img src="'.$today_summary['icon'].'" alt="" />';
													$output .= '</div>';													
												$output .= '</div>';
											$output .= '</div>';
										$output .= '</div>';
										$output .= isset($today_summary['break_bottom']) ? $today_summary['break_bottom'] : '';
									}
									$output = apply_filters('ic_commerce_dashboard_page_other_summary_output',$output,$summary_boxes['other_summary'],  $summary_boxes, $constants, $this->constants);
									echo $output;
								 	do_action('ic_commerce_dashboard_page_other_summary_end', $constants, $this->constants);
									?>
                                </div>
                            </div>
                            <div class="clearfix"></div>
                        </div>
                        
                        
                        <?php do_action('ic_commerce_dashboard_page_summary_content_end', $constants, $this->constants);?>
                    </div>
                </div>
            </div>
			<!-- Tab Over =================================================== -->
			<?php do_action("ic_commerce_dashboard_page_below_summary_box", $constants,$this);?>
			<div id="poststuff" class="woo_cr-reports-wrap">
					<?php //return; 
					if($this->is_product_active == 1):?>
                    <link href="<?php echo $this->constants['plugin_url']?>/assets/css/responsive-tabs.css" rel="stylesheet" type="text/css" media="all" />
                    <script type="text/javascript" src="<?php echo $this->constants['plugin_url']?>/assets/js/responsive-tabs.js"></script>
                    
                    <!--Tab Interface-->
					<?php if($show_sections == 1){ ?>
                    	
						<?php if($show_graph == 1): ?>
							<div class="responsive-tabs-default">
								<ul class="responsive-tabs">
									<li><a href="#tab-1" id="tablink1" data-tab="#tab-1" target="_self"><?php _e( 'Sales Summary', 'icwoocommerce_textdomains'); ?></a></li>
								   <!-- <li><a href="#tab-2" id="tablink2" data-tab="#tab-2" target="_self"><?php //_e( 'Audience Overview', 'icwoocommerce_textdomains'); ?></a></li>-->
								</ul>
								<div class="clearfix"></div>
								<div class="responsive-tabs-content">
									<div id="tab-1" class="responsive-tabs-panel">                              
										<span class="progress_status" style="display:none"></span>                                    
										<div class="ic_GraphList">
											<?php
                                            	$graph_buttons = array();
												
												$graph_buttons['sales_by_months']['doreport'] 	= 'sales_by_months';
												$graph_buttons['sales_by_months']['content'] 	= 'barchart';
												$graph_buttons['sales_by_months']['inside_id'] 	= 'top_tab_graphs';
												$graph_buttons['sales_by_months']['title'] 		= __( 'Sales By Months', 'icwoocommerce_textdomains');
												$graph_buttons['sales_by_months']['class'] 		= ' activethis';
												
												$graph_buttons['sales_by_days']['doreport'] 	= 'sales_by_days';
												$graph_buttons['sales_by_days']['content'] 		= 'linechart';
												$graph_buttons['sales_by_days']['inside_id'] 	= 'top_tab_graphs';
												$graph_buttons['sales_by_days']['title'] 		= __( 'Sales By Days', 'icwoocommerce_textdomains');
												$graph_buttons['sales_by_days']['class'] 		= '';
												
												$graph_buttons['sales_by_week']['doreport'] 	= 'sales_by_week';
												$graph_buttons['sales_by_week']['content'] 		= 'linechart';
												$graph_buttons['sales_by_week']['inside_id'] 	= 'top_tab_graphs';
												$graph_buttons['sales_by_week']['title'] 		= __( 'Sales By Week', 'icwoocommerce_textdomains');
												$graph_buttons['sales_by_week']['class'] 		= '';
												
												
												
												$graph_buttons['top_product']['doreport'] 	= 'top_product';
												$graph_buttons['top_product']['content'] 	= 'piechart';
												$graph_buttons['top_product']['inside_id'] 	= 'top_tab_graphs';
												$graph_buttons['top_product']['title'] 		= __( 'Top Products', 'icwoocommerce_textdomains');
												$graph_buttons['top_product']['class'] 		= '  hidden-phone';
												
												$graph_buttons = apply_filters('ic_commerce_dashboard_graph_tabs', $graph_buttons);
												
											?>
                                            <?php foreach($graph_buttons as $doreport => $graph_button):?>
                                            	<a href="#" class="box_tab_report<?php echo $graph_button['class'];?>" data-doreport="<?php echo $graph_button['doreport'];?>" data-content="<?php echo $graph_button['content'];?>"	data-inside_id="<?php echo $graph_button['inside_id'];?>"><?php echo $graph_button['title'];?></a>
                                            <?php endforeach;?>
                                            
                                           <!-- <a href="#" class="box_tab_report activethis"	data-doreport="sales_by_months" 	data-content="barchart"		data-inside_id="top_tab_graphs"><?php _e( 'Sales By Months', 'icwoocommerce_textdomains'); ?></a>
											<a href="#" class="box_tab_report"				data-doreport="sales_by_days" 		data-content="linechart"	data-inside_id="top_tab_graphs"><?php _e( 'Sales By Days', 'icwoocommerce_textdomains'); ?></a>
											<a href="#" class="box_tab_report"				data-doreport="sales_by_week" 		data-content="linechart"	data-inside_id="top_tab_graphs"><?php _e( 'Sales By Week', 'icwoocommerce_textdomains'); ?></a>
											<a href="#" class="box_tab_report hidden-phone"	data-doreport="top_product"			data-content="piechart"		data-inside_id="top_tab_graphs"><?php _e( 'Top Products', 'icwoocommerce_textdomains'); ?></a>
											--><!--<a href="#" class="box_tab_report" data-doreport="thirty_days_visit"	data-content="linechart"	data-inside_id="top_tab_graphs">Last 30 day visit</a>-->
											<div class="cleafix"></div>
										</div>
										<div class="ic_inside Overflow" id="top_tab_graphs">
											<div class="chart" id="top_tab_graphs_chart"></div>
										</div>
									</div>
									
									<div id="tab-2" class="responsive-tabs-panel">
										<div class="responsive-tab-title"></div>
										<div class="stats-overview-list">
											<p><?php _e( 'Fetching data from Google Analytics...', 'icwoocommerce_textdomains'); ?></p>
											<ul class="stats-overview" style="display:none;"><li><?php _e( 'Please Wait!', 'icwoocommerce_textdomains'); ?></li></ul>                                
										</div>
										<div class="ic_GraphList">
											<a href="#" class="box_tab_report" style="display:none"	data-doreport="thirty_days_visit"	data-content="linechart"	data-inside_id="top_tab_graphs2"><?php _e( 'Last 30 day visit', 'icwoocommerce_textdomains'); ?></a>
											<div class="cleafix"></div>
										</div>
										<div class="ic_inside Overflow" id="top_tab_graphs2">
											<div class="chart" id="top_tab_graphs_chart2"></div>
										</div>
									</div>
								</div>
							</div>
						<?php endif; ?>
					    
						<?php if($show_monthly_summary == 1 and $show_monthly_summary_bottom == 0): ?>               
                    		<div class="row">
								<div class="icpostbox">
									<h3>
										<span class="title"><?php _e( 'Monthly Summary', 'icwoocommerce_textdomains'); ?></span>           	
									</h3>
									<div class="ic_inside Overflow">                            
										<div class="grid"><?php $this->get_monthly_summary($shop_order_status,$hide_order_status,$start_date,$end_date)?></div>
									</div>
								</div>
							</div>
						<?php endif; ?>
                    	
						<?php if($show_map == 1 and $show_map_bottom == 0): ?>
                    		<div class="row">
								<div class="icpostbox">
									<h3>
										<span class="title"><?php _e( 'Map', 'icwoocommerce_textdomains'); ?></span>           	
									</h3>
									<div class="ic_inside Overflow">
										<div id="map1" style="width:100%; height: 400px"><p class="please_wait"><?php _e( 'Please Wait!', 'icwoocommerce_textdomains'); ?></p></div>
										<style type="text/css">                                	
											#map1 p.please_wait{  text-align:center; font-size:24px; font-family:Arial, Helvetica, sans-serif, Verdana, Geneva, sans-serif; color:#999; padding-top:190px;}
										</style>
									</div>
								</div>
							</div>
						<?php endif; ?>
					
					
                    	<div class="row">
							<?php if($show_order_summary == 1): ?>
								<div class="col-md-12">
									<div class="icpostbox">
										<h3>
											<span class="title"><?php _e( 'Order Summary', 'icwoocommerce_textdomains'); ?></span>
											<span class="progress_status"></span>
										</h3>
										<div class="ic_inside Overflow" id="sales_order_count_value">
											<div class="grid"><?php $this->sales_order_count_value($shop_order_status,$hide_order_status,$start_date,$end_date);//New Change ID 20140918?></div>
										</div>
									</div>
								</div>
							<?php endif; ?>
                        
							<?php if($show_sales_order_status == 1): ?>
								<div class="col-md-12">
									<div class="icpostbox">
										<h3>
											<span class="title"><?php _e( 'Sales Order Status', 'icwoocommerce_textdomains'); ?></span>
											<span class="progress_status"></span>
											<div class="Icons">
												<a href="#" class="box_tab_report Table active" data-doreport="sales_order_status" 	data-content="table"		data-inside_id="sales_order_status"><?php _e( 'Sales Order Status', 'icwoocommerce_textdomains'); ?></a>
												<a href="#" class="box_tab_report BarChart" 	data-doreport="sales_order_status" 	data-content="barchart"		data-inside_id="sales_order_status"><?php _e( 'Sales Order Status', 'icwoocommerce_textdomains'); ?></a>
												<a href="#" class="box_tab_report PieChart" 	data-doreport="sales_order_status" 	data-content="piechart"		data-inside_id="sales_order_status"><?php _e( 'Sales Order Status', 'icwoocommerce_textdomains'); ?></a>
											</div>
										</h3>
										<div class="ic_inside Overflow" id="sales_order_status">
											<div class="chart_parent">
												<div class="chart" id="sales_order_status_chart"></div>
											</div>
											<div class="grid"><?php $this->sales_order_status($shop_order_status,$hide_order_status,$start_date,$end_date);//New Change ID 20140918?></div>
										</div>
									</div>
								</div>
							<?php endif; ?>
                    	</div>
                    
						<div class="row ThreeCol_Boxes">
							<?php if($show_top_products == 1): ?>
								<div class="col-md-12">
									<div class="icpostbox">
										<h3>
											<span class="title"><?php echo sprintf(__( 'Top %s Products' ),$this->get_number_only('top_product_per_page',$this->per_page_default)); ?></span>
											<span class="progress_status"></span>
											<div class="Icons">
												<a href="#" class="box_tab_report Table active" data-doreport="top_product_status" 	data-content="table"		data-inside_id="top_product_status"><?php _e( 'Top Product Status', 'icwoocommerce_textdomains'); ?></a>
												<a href="#" class="box_tab_report BarChart" 	data-doreport="top_product_status" 	data-content="barchart"		data-inside_id="top_product_status"><?php _e( 'Top Product Status', 'icwoocommerce_textdomains'); ?></a>
												<a href="#" class="box_tab_report PieChart" 	data-doreport="top_product_status" 	data-content="piechart"		data-inside_id="top_product_status"><?php _e( 'Top Product Status', 'icwoocommerce_textdomains'); ?></a>                                    
											</div>
										</h3>                                
									   
										<div class="ic_inside Overflow" id="top_product_status">
											<div class="chart_parent">
												<div class="chart" id="top_product_status_chart"></div>
											</div>
											<div class="grid"><?php $this->top_product_list($shop_order_status,$hide_order_status,$start_date,$end_date);//New Change ID 20140918?></div>
										</div>
									</div>                    	
								</div>
							<?php endif; ?>
							
							<?php if($show_top_categories == 1): ?>
								<div class="col-md-12">
									<div class="icpostbox">
										<h3>
											<span class="title"><?php echo sprintf(__( 'Top %s Category' ),$this->get_number_only('top_product_per_page',$this->per_page_default)); ?></span>
											<span class="progress_status"></span>
											<div class="Icons">
												<a href="#" class="box_tab_report Table active" data-doreport="top_category_status" 	data-content="table"		data-inside_id="top_category_status"><?php _e( 'Top Category Status', 'icwoocommerce_textdomains'); ?></a>
												<a href="#" class="box_tab_report BarChart" 	data-doreport="top_category_status" 	data-content="barchart"		data-inside_id="top_category_status"><?php _e( 'Top Category Status', 'icwoocommerce_textdomains'); ?></a>
												<a href="#" class="box_tab_report PieChart" 	data-doreport="top_category_status" 	data-content="piechart"		data-inside_id="top_category_status"><?php _e( 'Top Category Status', 'icwoocommerce_textdomains'); ?></a>
											</div>
										</h3>                                
									   
										<div class="ic_inside Overflow" id="top_category_status">
											<div class="chart_parent">
												<div class="chart" id="top_category_status_chart"></div>
											</div>
											<div class="grid"><?php $this->get_category_list($shop_order_status,$hide_order_status,$start_date,$end_date);//New Change ID 20150206?></div>
										</div>
									</div>                    	
								</div>
							<?php endif; ?>
						</div>
                    
                    	<div class="row ThreeCol_Boxes">
							<?php if($show_top_countries == 1): ?>
								<div class="col-md-12">
									<div class="icpostbox">
										<h3>
											<span class="title"><?php echo sprintf(($billing_or_shipping == "shipping" ? __( 'Top %s Shipping Country' , 'icwoocommerce_textdomains') : __( 'Top %s Billing Country' , 'icwoocommerce_textdomains')),$this->get_number_only('top_billing_country_per_page',$this->per_page_default)); ?></span>
											<span class="progress_status"></span>
											<div class="Icons">
												<a href="#" class="box_tab_report Table active" data-doreport="top_billing_country" 	data-content="table"		data-inside_id="top_billing_country"><?php _e( 'Top Billing Country', 'icwoocommerce_textdomains'); ?></a>
												<a href="#" class="box_tab_report BarChart" 	data-doreport="top_billing_country" 	data-content="barchart"		data-inside_id="top_billing_country"><?php _e( 'Top Billing Country', 'icwoocommerce_textdomains'); ?></a>
												<a href="#" class="box_tab_report PieChart" 	data-doreport="top_billing_country" 	data-content="piechart"		data-inside_id="top_billing_country"><?php _e( 'Top Billing Country', 'icwoocommerce_textdomains'); ?></a>                                    
											</div>
										</h3>
										<div class="ic_inside Overflow" id="top_billing_country">
											<div class="chart_parent">
												<div class="chart" id="top_billing_country_chart"></div>
											</div>
											<div class="grid"><?php $this->top_billing_country($shop_order_status,$hide_order_status,$start_date,$end_date);//New Change ID 20140918?></div>
										</div>
									</div>
								</div>
							<?php endif; ?>
							
							<?php if($show_top_states == 1): ?>
								<div class="col-md-12">
									<div class="icpostbox">
										<h3>
											<span class="title"><?php echo sprintf(($billing_or_shipping == "shipping" ? __( 'Top %s Shipping State' , 'icwoocommerce_textdomains') : __( 'Top %s Billing State' , 'icwoocommerce_textdomains')),$this->get_number_only('top_billing_state_per_page',$this->per_page_default)); ?></span>
											<span class="progress_status"></span>
											<div class="Icons">
												<a href="#" class="box_tab_report Table active" data-doreport="top_billing_state" 	data-content="table"		data-inside_id="top_billing_state"><?php _e( 'Top Billing State', 'icwoocommerce_textdomains'); ?></a>
												<a href="#" class="box_tab_report BarChart" 	data-doreport="top_billing_state" 	data-content="barchart"		data-inside_id="top_billing_state"><?php _e( 'Top Billing State', 'icwoocommerce_textdomains'); ?></a>
												<a href="#" class="box_tab_report PieChart" 	data-doreport="top_billing_state" 	data-content="piechart"		data-inside_id="top_billing_state"><?php _e( 'Top Billing State', 'icwoocommerce_textdomains'); ?></a>                                    
											</div>
										</h3>
										<div class="ic_inside Overflow" id="top_billing_state">
											<div class="chart" id="top_billing_state_chart"></div>
											<div class="grid"><?php $this->top_billing_state($shop_order_status,$hide_order_status,$start_date,$end_date);//New Change ID 20140918?></div>
										</div>
									</div>
								</div>
							<?php endif; ?>
						</div>
                    
                    	<?php do_action("ic_commerce_dashboard_page_above_recent_order", $constants)?>
                    
                    	<div class="row">
							<?php if($show_recent_orders == 1): ?>
								<div class="icpostbox">
									<h3>
										<span class="title"><?php echo sprintf(__( 'Recent %s Orders' ),$this->get_number_only('recent_order_per_page',$this->per_page_default)); ?></span>                        	
									</h3>
									<div class="ic_inside Overflow">                            
										<div class="grid"><?php $this->recent_orders($shop_order_status,$hide_order_status,$start_date,$end_date);?></div>
									</div>
								</div>
							<?php endif; ?>
						</div>
                    
                    	<div class="row ThreeCol_Boxes">
							<?php if($show_top_customers == 1): ?>
								<div class="col-md-12">
									<div class="icpostbox">
										<h3>
											<span class="title"><?php echo sprintf(__( 'Top %s Customers' ),$this->get_number_only('top_customer_per_page',$this->per_page_default)); ?></span>
											<span class="progress_status"></span>
											<div class="Icons">
												<a href="#" class="box_tab_report Table active" data-doreport="top_customer_list" 	data-content="table"		data-inside_id="top_customer_list"><?php _e( 'Top Customers', 'icwoocommerce_textdomains'); ?></a>
												<a href="#" class="box_tab_report BarChart" 	data-doreport="top_customer_list" 	data-content="barchart"		data-inside_id="top_customer_list"><?php _e( 'Top Customers', 'icwoocommerce_textdomains'); ?></a>
												<a href="#" class="box_tab_report PieChart" 	data-doreport="top_customer_list" 	data-content="piechart"		data-inside_id="top_customer_list"><?php _e( 'Top Customers', 'icwoocommerce_textdomains'); ?></a>
											</div>
										</h3>
										<div class="ic_inside Overflow" id="top_customer_list">
											<div class="chart_parent">
												<div class="chart" id="top_customer_list_chart"></div>
											</div>
											<div class="grid"><?php $this->top_customer_list($shop_order_status,$hide_order_status,$start_date,$end_date);//New Change ID 20140918?></div>
										</div>
									</div>
								</div>
							<?php endif; ?>
							
							<?php if($show_top_coupons == 1): ?>
								<div class="col-md-12">
									<div class="icpostbox">
										<h3>
											<span class="title"><?php echo sprintf(__( 'Top %s Coupons' ),$this->get_number_only('top_coupon_per_page',$this->per_page_default)); ?></span>
											<span class="progress_status"></span>
											<div class="Icons">
												<a href="#" class="box_tab_report Table active" data-doreport="top_coupon_list" 	data-content="table"		data-inside_id="top_coupon_list"><?php _e( 'Top Coupons', 'icwoocommerce_textdomains'); ?></a>
												<a href="#" class="box_tab_report BarChart" 	data-doreport="top_coupon_list" 	data-content="barchart"		data-inside_id="top_coupon_list"><?php _e( 'Top Coupons', 'icwoocommerce_textdomains'); ?></a>
												<a href="#" class="box_tab_report PieChart" 	data-doreport="top_coupon_list" 	data-content="piechart"		data-inside_id="top_coupon_list"><?php _e( 'Top Coupons', 'icwoocommerce_textdomains'); ?></a>                                    
											</div>
										</h3>
										<div class="ic_inside Overflow" id="top_coupon_list">
											<div class="chart_parent">
												<div class="chart" id="top_coupon_list_chart"></div>
											</div>
											<div class="grid"><?php $this->get_top_coupon_list($shop_order_status,$hide_order_status,$start_date,$end_date);//New Change ID 20140918?></div>
										</div>
									   
									</div>
								</div>
							<?php endif; ?>
								
							<?php if($show_top_payments == 1): ?>
								<div class="col-md-12">
									<div class="icpostbox">
										<h3>
											<span class="title"><?php echo sprintf(__( 'Top %s Payment Gateway' ),$this->get_number_only('top_payment_gateway_per_page',$this->per_page_default)); ?></span>
											<span class="progress_status"></span>
											<div class="Icons">
												<a href="#" class="box_tab_report Table active" data-doreport="top_payment_gateway" 	data-content="table"		data-inside_id="top_payment_gateway"><?php _e( 'Top Payment Gateway', 'icwoocommerce_textdomains'); ?></a>
												<a href="#" class="box_tab_report BarChart" 	data-doreport="top_payment_gateway" 	data-content="barchart"		data-inside_id="top_payment_gateway"><?php _e( 'Top Payment Gateway', 'icwoocommerce_textdomains'); ?></a>
												<a href="#" class="box_tab_report PieChart" 	data-doreport="top_payment_gateway" 	data-content="piechart"		data-inside_id="top_payment_gateway"><?php _e( 'Top Payment Gateway', 'icwoocommerce_textdomains'); ?></a>                                    
											</div>
										</h3>
										<div class="ic_inside Overflow" id="top_payment_gateway">
											<div class="chart_parent">
												<div class="chart" id="top_payment_gateway_chart"></div>
											</div>
											<div class="grid"><?php $this->get_payment_gateway_list($shop_order_status,$hide_order_status,$start_date,$end_date);//New Change ID 20140918?></div>
										</div>
									</div>
								</div>
							<?php endif; ?>
                            
                            <?php if($show_monthly_summary == 1 and $show_monthly_summary_bottom == 1): ?>               
                                <div class="row">
                                    <div class="icpostbox">
                                        <h3>
                                            <span class="title"><?php _e( 'Monthly Summary', 'icwoocommerce_textdomains'); ?></span>           	
                                        </h3>
                                        <div class="ic_inside Overflow">                            
                                            <div class="grid"><?php $this->get_monthly_summary($shop_order_status,$hide_order_status,$start_date,$end_date)?></div>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <?php if($show_map == 1 and $show_map_bottom == 1): ?>
                                <div class="row">
                                    <div class="icpostbox">
                                        <h3>
                                            <span class="title"><?php _e( 'Map', 'icwoocommerce_textdomains'); ?></span>           	
                                        </h3>
                                        <div class="ic_inside Overflow">
                                            <div id="map1" style="width:100%; height: 400px"><p class="please_wait"><?php _e( 'Please Wait!', 'icwoocommerce_textdomains'); ?></p></div>
                                            <style type="text/css">                                	
                                                #map1 p.please_wait{  text-align:center; font-size:24px; font-family:Arial, Helvetica, sans-serif, Verdana, Geneva, sans-serif; color:#999; padding-top:190px;}
                                            </style>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
						</div>
                    
					<?php }else{ ?>
					
						<div class="responsive-tabs-default">
							<ul class="responsive-tabs">
								<li><a href="#tab-1" id="tablink1" data-tab="#tab-1" target="_self"><?php _e( 'Sales Summary', 'icwoocommerce_textdomains'); ?></a></li>
							   <!-- <li><a href="#tab-2" id="tablink2" data-tab="#tab-2" target="_self"><?php //_e( 'Audience Overview', 'icwoocommerce_textdomains'); ?></a></li>-->
							</ul>
							<div class="clearfix"></div>
							<div class="responsive-tabs-content">
								<div id="tab-1" class="responsive-tabs-panel">                              
									<span class="progress_status" style="display:none"></span>                                    
									<div class="ic_GraphList">
										
                                        <?php
                                            	$graph_buttons = array();
												
												$graph_buttons['sales_by_months']['doreport'] 	= 'sales_by_months';
												$graph_buttons['sales_by_months']['content'] 	= 'barchart';
												$graph_buttons['sales_by_months']['inside_id'] 	= 'top_tab_graphs';
												$graph_buttons['sales_by_months']['title'] 		= __( 'Sales By Months', 'icwoocommerce_textdomains');
												$graph_buttons['sales_by_months']['class'] 		= ' activethis';
												
												$graph_buttons['sales_by_days']['doreport'] 	= 'sales_by_days';
												$graph_buttons['sales_by_days']['content'] 		= 'linechart';
												$graph_buttons['sales_by_days']['inside_id'] 	= 'top_tab_graphs';
												$graph_buttons['sales_by_days']['title'] 		= __( 'Sales By Days', 'icwoocommerce_textdomains');
												$graph_buttons['sales_by_days']['class'] 		= '';
												
												$graph_buttons['sales_by_week']['doreport'] 	= 'sales_by_week';
												$graph_buttons['sales_by_week']['content'] 		= 'linechart';
												$graph_buttons['sales_by_week']['inside_id'] 	= 'top_tab_graphs';
												$graph_buttons['sales_by_week']['title'] 		= __( 'Sales By Week', 'icwoocommerce_textdomains');
												$graph_buttons['sales_by_week']['class'] 		= '';
												
												
												
												$graph_buttons['top_product']['doreport'] 	= 'top_product';
												$graph_buttons['top_product']['content'] 	= 'piechart';
												$graph_buttons['top_product']['inside_id'] 	= 'top_tab_graphs';
												$graph_buttons['top_product']['title'] 		= __( 'Top Products', 'icwoocommerce_textdomains');
												$graph_buttons['top_product']['class'] 		= '  hidden-phone';
												
												$graph_buttons = apply_filters('ic_commerce_dashboard_graph_tabs', $graph_buttons);
												
											?>
                                            <?php foreach($graph_buttons as $doreport => $graph_button):?>
                                            	<a href="#" class="box_tab_report<?php echo $graph_button['class'];?>" data-doreport="<?php echo $graph_button['doreport'];?>" data-content="<?php echo $graph_button['content'];?>"	data-inside_id="<?php echo $graph_button['inside_id'];?>"><?php echo $graph_button['title'];?></a>
                                            <?php endforeach;?>
                                            <!--
                                                <a href="#" class="box_tab_report activethis"	data-doreport="sales_by_months" 	data-content="barchart"		data-inside_id="top_tab_graphs"><?php _e( 'Sales By Months', 'icwoocommerce_textdomains'); ?></a>
                                                <a href="#" class="box_tab_report"				data-doreport="sales_by_days" 		data-content="linechart"	data-inside_id="top_tab_graphs"><?php _e( 'Sales By Days', 'icwoocommerce_textdomains'); ?></a>
                                                <a href="#" class="box_tab_report"				data-doreport="sales_by_week" 		data-content="linechart"	data-inside_id="top_tab_graphs"><?php _e( 'Sales By Week', 'icwoocommerce_textdomains'); ?></a>
                                                <a href="#" class="box_tab_report hidden-phone"	data-doreport="top_product"			data-content="piechart"		data-inside_id="top_tab_graphs"><?php _e( 'Top Products', 'icwoocommerce_textdomains'); ?></a>
                                            -->
                                            <!--<a href="#" class="box_tab_report" data-doreport="thirty_days_visit"	data-content="linechart"	data-inside_id="top_tab_graphs">Last 30 day visit</a>-->
										<div class="cleafix"></div>
									</div>
									<div class="ic_inside Overflow" id="top_tab_graphs">
										<div class="chart" id="top_tab_graphs_chart"></div>
									</div>
								</div>
								
								<div id="tab-2" class="responsive-tabs-panel">
									<div class="responsive-tab-title"></div>
									<div class="stats-overview-list">
										<p><?php _e( 'Fetching data from Google Analytics...', 'icwoocommerce_textdomains'); ?></p>
										<ul class="stats-overview" style="display:none;"><li><?php _e( 'Please Wait!', 'icwoocommerce_textdomains'); ?></li></ul>                                
									</div>
									<div class="ic_GraphList">
										<a href="#" class="box_tab_report" style="display:none"	data-doreport="thirty_days_visit"	data-content="linechart"	data-inside_id="top_tab_graphs2"><?php _e( 'Last 30 day visit', 'icwoocommerce_textdomains'); ?></a>
										<div class="cleafix"></div>
									</div>
									<div class="ic_inside Overflow" id="top_tab_graphs2">
										<div class="chart" id="top_tab_graphs_chart2"></div>
									</div>
								</div>
							</div>
						</div>
                        
                        
                        <?php if($show_monthly_summary_bottom == 0): ?>               
                    		<div class="row">
								<div class="icpostbox">
									<h3>
										<span class="title"><?php _e( 'Monthly Summary', 'icwoocommerce_textdomains'); ?></span>           	
									</h3>
									<div class="ic_inside Overflow">                            
										<div class="grid"><?php $this->get_monthly_summary($shop_order_status,$hide_order_status,$start_date,$end_date)?></div>
									</div>
								</div>
							</div>
						<?php endif; ?>
                    	
						<?php if($show_map_bottom == 0): ?>
                    		<div class="row">
								<div class="icpostbox">
									<h3>
										<span class="title"><?php _e( 'Map', 'icwoocommerce_textdomains'); ?></span>           	
									</h3>
									<div class="ic_inside Overflow">
										<div id="map1" style="width:100%; height: 400px"><p class="please_wait"><?php _e( 'Please Wait!', 'icwoocommerce_textdomains'); ?></p></div>
										<style type="text/css">                                	
											#map1 p.please_wait{  text-align:center; font-size:24px; font-family:Arial, Helvetica, sans-serif, Verdana, Geneva, sans-serif; color:#999; padding-top:190px;}
										</style>
									</div>
								</div>
							</div>
						<?php endif; ?>
					                   
                    	
                    
                    	
					
					
						<div class="row">
							<div class="col-md-6">
								<div class="icpostbox">
									<h3>
										<span class="title"><?php _e( 'Order Summary', 'icwoocommerce_textdomains'); ?></span>
										<span class="progress_status"></span>
									</h3>
									<div class="ic_inside Overflow" id="sales_order_count_value">
										<div class="grid"><?php $this->sales_order_count_value($shop_order_status,$hide_order_status,$start_date,$end_date);//New Change ID 20140918?></div>
									</div>
								</div>
							</div>
                        
							<div class="col-md-6">
								<div class="icpostbox">
									<h3>
										<span class="title"><?php _e( 'Sales Order Status', 'icwoocommerce_textdomains'); ?></span>
										<span class="progress_status"></span>
										<div class="Icons">
											<a href="#" class="box_tab_report Table active" data-doreport="sales_order_status" 	data-content="table"		data-inside_id="sales_order_status"><?php _e( 'Sales Order Status', 'icwoocommerce_textdomains'); ?></a>
											<a href="#" class="box_tab_report BarChart" 	data-doreport="sales_order_status" 	data-content="barchart"		data-inside_id="sales_order_status"><?php _e( 'Sales Order Status', 'icwoocommerce_textdomains'); ?></a>
											<a href="#" class="box_tab_report PieChart" 	data-doreport="sales_order_status" 	data-content="piechart"		data-inside_id="sales_order_status"><?php _e( 'Sales Order Status', 'icwoocommerce_textdomains'); ?></a>
										</div>
									</h3>
									<div class="ic_inside Overflow" id="sales_order_status">
										<div class="chart_parent">
											<div class="chart" id="sales_order_status_chart"></div>
										</div>
										<div class="grid"><?php $this->sales_order_status($shop_order_status,$hide_order_status,$start_date,$end_date);//New Change ID 20140918?></div>
									</div>
								</div>
							</div>
                    	</div>
                    
                    	<div class="row ThreeCol_Boxes">
							<div class="col-md-6">
								<div class="icpostbox">
									<h3>
										<span class="title"><?php echo sprintf(__( 'Top %s Products' ),$this->get_number_only('top_product_per_page',$this->per_page_default)); ?></span>
										<span class="progress_status"></span>
										<div class="Icons">
											<a href="#" class="box_tab_report Table active" data-doreport="top_product_status" 	data-content="table"		data-inside_id="top_product_status"><?php _e( 'Top Product Status', 'icwoocommerce_textdomains'); ?></a>
											<a href="#" class="box_tab_report BarChart" 	data-doreport="top_product_status" 	data-content="barchart"		data-inside_id="top_product_status"><?php _e( 'Top Product Status', 'icwoocommerce_textdomains'); ?></a>
											<a href="#" class="box_tab_report PieChart" 	data-doreport="top_product_status" 	data-content="piechart"		data-inside_id="top_product_status"><?php _e( 'Top Product Status', 'icwoocommerce_textdomains'); ?></a>                                    
										</div>
									</h3>                                
								   
	
									<div class="ic_inside Overflow" id="top_product_status">
										<div class="chart_parent">
											<div class="chart" id="top_product_status_chart"></div>
										</div>
										<div class="grid"><?php $this->top_product_list($shop_order_status,$hide_order_status,$start_date,$end_date);//New Change ID 20140918?></div>
									</div>
								</div>                    	
							</div>
							
							<div class="col-md-6">
								<div class="icpostbox">
									<h3>
										<span class="title"><?php echo sprintf(__( 'Top %s Category' ),$this->get_number_only('top_product_per_page',$this->per_page_default)); ?></span>
										<span class="progress_status"></span>
										<div class="Icons">
											<a href="#" class="box_tab_report Table active" data-doreport="top_category_status" 	data-content="table"		data-inside_id="top_category_status"><?php _e( 'Top Category Status', 'icwoocommerce_textdomains'); ?></a>
											<a href="#" class="box_tab_report BarChart" 	data-doreport="top_category_status" 	data-content="barchart"		data-inside_id="top_category_status"><?php _e( 'Top Category Status', 'icwoocommerce_textdomains'); ?></a>
											<a href="#" class="box_tab_report PieChart" 	data-doreport="top_category_status" 	data-content="piechart"		data-inside_id="top_category_status"><?php _e( 'Top Category Status', 'icwoocommerce_textdomains'); ?></a>
										</div>
									</h3>                                
								   
									<div class="ic_inside Overflow" id="top_category_status">
										<div class="chart_parent">
											<div class="chart" id="top_category_status_chart"></div>
										</div>
										<div class="grid"><?php $this->get_category_list($shop_order_status,$hide_order_status,$start_date,$end_date);//New Change ID 20150206?></div>
									</div>
								</div>                    	
							</div>
						</div>
						
						<div class="row ThreeCol_Boxes">
						
						
						<div class="col-md-6">
							<div class="icpostbox">
								<h3>
									<span class="title"><?php echo sprintf(($billing_or_shipping == "shipping" ? __( 'Top %s Shipping Country' , 'icwoocommerce_textdomains') : __( 'Top %s Billing Country' , 'icwoocommerce_textdomains')),$this->get_number_only('top_billing_country_per_page',$this->per_page_default)); ?></span>
									<span class="progress_status"></span>
									<div class="Icons">
										<a href="#" class="box_tab_report Table active" data-doreport="top_billing_country" 	data-content="table"		data-inside_id="top_billing_country"><?php _e( 'Top Billing Country', 'icwoocommerce_textdomains'); ?></a>
										<a href="#" class="box_tab_report BarChart" 	data-doreport="top_billing_country" 	data-content="barchart"		data-inside_id="top_billing_country"><?php _e( 'Top Billing Country', 'icwoocommerce_textdomains'); ?></a>
										<a href="#" class="box_tab_report PieChart" 	data-doreport="top_billing_country" 	data-content="piechart"		data-inside_id="top_billing_country"><?php _e( 'Top Billing Country', 'icwoocommerce_textdomains'); ?></a>                                    
									</div>
								</h3>
								<div class="ic_inside Overflow" id="top_billing_country">
									<div class="chart_parent">
										<div class="chart" id="top_billing_country_chart"></div>
									</div>
									<div class="grid"><?php $this->top_billing_country($shop_order_status,$hide_order_status,$start_date,$end_date);//New Change ID 20140918?></div>
								</div>
							</div>
						</div>
						
						<div class="col-md-6">
							<div class="icpostbox">
								<h3>
									<span class="title"><?php echo sprintf(($billing_or_shipping == "shipping" ? __( 'Top %s Shipping State' , 'icwoocommerce_textdomains') : __( 'Top %s Billing State' , 'icwoocommerce_textdomains')),$this->get_number_only('top_billing_state_per_page',$this->per_page_default)); ?></span>
									<span class="progress_status"></span>
									<div class="Icons">
										<a href="#" class="box_tab_report Table active" data-doreport="top_billing_state" 	data-content="table"		data-inside_id="top_billing_state"><?php _e( 'Top Billing State', 'icwoocommerce_textdomains'); ?></a>
										<a href="#" class="box_tab_report BarChart" 	data-doreport="top_billing_state" 	data-content="barchart"		data-inside_id="top_billing_state"><?php _e( 'Top Billing State', 'icwoocommerce_textdomains'); ?></a>
										<a href="#" class="box_tab_report PieChart" 	data-doreport="top_billing_state" 	data-content="piechart"		data-inside_id="top_billing_state"><?php _e( 'Top Billing State', 'icwoocommerce_textdomains'); ?></a>                                    
									</div>
								</h3>
								<div class="ic_inside Overflow" id="top_billing_state">
									<div class="chart" id="top_billing_state_chart"></div>
									<div class="grid"><?php $this->top_billing_state($shop_order_status,$hide_order_status,$start_date,$end_date);//New Change ID 20140918?></div>
								</div>
							</div>
						</div>
					</div>
                    
                    	<?php do_action("ic_commerce_dashboard_page_above_recent_order", $constants)?>
                    
                    	<div class="row">
							<div class="icpostbox">
								<h3>
									<span class="title"><?php echo sprintf(__( 'Recent %s Orders' ),$this->get_number_only('recent_order_per_page',$this->per_page_default)); ?></span>                        	
								</h3>
								<div class="ic_inside Overflow">                            
									<div class="grid"><?php $this->recent_orders($shop_order_status,$hide_order_status,$start_date,$end_date);?></div>
								</div>
							</div>
						</div>
                    
                    	<div class="row ThreeCol_Boxes">
							<div class="col-md-4">
								<div class="icpostbox">
									<h3>
										<span class="title"><?php echo sprintf(__( 'Top %s Customers' ),$this->get_number_only('top_customer_per_page',$this->per_page_default)); ?></span>
										<span class="progress_status"></span>
										<div class="Icons">
											<a href="#" class="box_tab_report Table active" data-doreport="top_customer_list" 	data-content="table"		data-inside_id="top_customer_list"><?php _e( 'Top Customers', 'icwoocommerce_textdomains'); ?></a>
											<a href="#" class="box_tab_report BarChart" 	data-doreport="top_customer_list" 	data-content="barchart"		data-inside_id="top_customer_list"><?php _e( 'Top Customers', 'icwoocommerce_textdomains'); ?></a>
											<a href="#" class="box_tab_report PieChart" 	data-doreport="top_customer_list" 	data-content="piechart"		data-inside_id="top_customer_list"><?php _e( 'Top Customers', 'icwoocommerce_textdomains'); ?></a>
										</div>
									</h3>
									<div class="ic_inside Overflow" id="top_customer_list">
										<div class="chart_parent">
											<div class="chart" id="top_customer_list_chart"></div>
										</div>
										<div class="grid"><?php $this->top_customer_list($shop_order_status,$hide_order_status,$start_date,$end_date);//New Change ID 20140918?></div>
									</div>
								</div>
							</div>
							
							<div class="col-md-4">
								<div class="icpostbox">
									<h3>
										<span class="title"><?php echo sprintf(__( 'Top %s Coupons' ),$this->get_number_only('top_coupon_per_page',$this->per_page_default)); ?></span>
										<span class="progress_status"></span>
										<div class="Icons">
											<a href="#" class="box_tab_report Table active" data-doreport="top_coupon_list" 	data-content="table"		data-inside_id="top_coupon_list"><?php _e( 'Top Coupons', 'icwoocommerce_textdomains'); ?></a>
											<a href="#" class="box_tab_report BarChart" 	data-doreport="top_coupon_list" 	data-content="barchart"		data-inside_id="top_coupon_list"><?php _e( 'Top Coupons', 'icwoocommerce_textdomains'); ?></a>
											<a href="#" class="box_tab_report PieChart" 	data-doreport="top_coupon_list" 	data-content="piechart"		data-inside_id="top_coupon_list"><?php _e( 'Top Coupons', 'icwoocommerce_textdomains'); ?></a>                                    
										</div>
									</h3>
									<div class="ic_inside Overflow" id="top_coupon_list">
										<div class="chart_parent">
											<div class="chart" id="top_coupon_list_chart"></div>
										</div>
										<div class="grid"><?php $this->get_top_coupon_list($shop_order_status,$hide_order_status,$start_date,$end_date);//New Change ID 20140918?></div>
									</div>
								   
								</div>
							</div>
							<div class="col-md-4">
								<div class="icpostbox">
									<h3>
										<span class="title"><?php echo sprintf(__( 'Top %s Payment Gateway' ),$this->get_number_only('top_payment_gateway_per_page',$this->per_page_default)); ?></span>
										<span class="progress_status"></span>
										<div class="Icons">
											<a href="#" class="box_tab_report Table active" data-doreport="top_payment_gateway" 	data-content="table"		data-inside_id="top_payment_gateway"><?php _e( 'Top Payment Gateway', 'icwoocommerce_textdomains'); ?></a>
											<a href="#" class="box_tab_report BarChart" 	data-doreport="top_payment_gateway" 	data-content="barchart"		data-inside_id="top_payment_gateway"><?php _e( 'Top Payment Gateway', 'icwoocommerce_textdomains'); ?></a>
											<a href="#" class="box_tab_report PieChart" 	data-doreport="top_payment_gateway" 	data-content="piechart"		data-inside_id="top_payment_gateway"><?php _e( 'Top Payment Gateway', 'icwoocommerce_textdomains'); ?></a>                                    
										</div>
									</h3>
									<div class="ic_inside Overflow" id="top_payment_gateway">
										<div class="chart_parent">
											<div class="chart" id="top_payment_gateway_chart"></div>
										</div>
										<div class="grid"><?php $this->get_payment_gateway_list($shop_order_status,$hide_order_status,$start_date,$end_date);//New Change ID 20140918?></div>
									</div>
								</div>
							</div>
						</div>
                        
                        
                         <?php if($show_monthly_summary_bottom == 1): ?>               
                    		<div class="row">
								<div class="icpostbox">
									<h3>
										<span class="title"><?php _e( 'Monthly Summary', 'icwoocommerce_textdomains'); ?></span>           	
									</h3>
									<div class="ic_inside Overflow">                            
										<div class="grid"><?php $this->get_monthly_summary($shop_order_status,$hide_order_status,$start_date,$end_date)?></div>
									</div>
								</div>
							</div>
						<?php endif; ?>
                    	
						<?php if($show_map_bottom == 1): ?>
                    		<div class="row">
								<div class="icpostbox">
									<h3>
										<span class="title"><?php _e( 'Map', 'icwoocommerce_textdomains'); ?></span>           	
									</h3>
									<div class="ic_inside Overflow">
										<div id="map1" style="width:100%; height: 400px"><p class="please_wait"><?php _e( 'Please Wait!', 'icwoocommerce_textdomains'); ?></p></div>
										<style type="text/css">                                	
											#map1 p.please_wait{  text-align:center; font-size:24px; font-family:Arial, Helvetica, sans-serif, Verdana, Geneva, sans-serif; color:#999; padding-top:190px;}
										</style>
									</div>
								</div>
							</div>
						<?php endif; ?>
					
					<?php } ?>
					
                    <?php endif;?>
				</div>
			
                
                <style type="text/css">
                	.widefat.table_sales_by_month tr:last-child {
						font-weight: bold;
						background-color: #666;
					}
					
					.widefat.table_sales_by_month tr:last-child td {
						color: #FFF;
						font-size: 15px;
					}
					
					.widefat.table_sales_by_month tr:last-child{ font-weight:bold; background-color:#666;}
					.widefat.table_sales_by_month tr:last-child td{ color:#FFF; font-size:15px;}
					
					.widefat.table_sales_by_month tbody tr:last-child:hover{background-color:#666;}
					th.order_discount, td.order_discount, th.old_cancels, td.old_cancels{ text-align:right;}
					
					.iccommercepluginwrap .ic_dashboard_summary_box .ic_block-content{ height:80px;}
					
					table.widefat th.item_count{ width:100px; text-align:right}
					table.widefat th.item_amount{ width:100px;}
					table.widefat td.item_count{ text-align:right;}
                </style>
			
			<?php
		}
		
		//New Change ID 20140918
		function get_total_order($type = 'total',$shop_order_status,$hide_order_status,$start_date,$end_date){
			global $wpdb;			
			$today_date 			= $this->today;
			$yesterday_date 		= $this->yesterday;
			
			$sql = "
			SELECT 
			count(*) AS 'total_count'
			,SUM(postmeta1.meta_value) AS 'total_amount'	
			,DATE(posts.post_date) AS 'group_date'	
			FROM {$wpdb->prefix}posts as posts ";
			if($this->constants['post_order_status_found'] == 0 ){
				if(count($shop_order_status)>0){
						$sql .= " 
						LEFT JOIN  {$wpdb->prefix}term_relationships 	as term_relationships 	ON term_relationships.object_id		=	posts.ID
						LEFT JOIN  {$wpdb->prefix}term_taxonomy 		as term_taxonomy 		ON term_taxonomy.term_taxonomy_id	=	term_relationships.term_taxonomy_id";
				}
			}
			$sql .= " LEFT JOIN  {$wpdb->prefix}postmeta as postmeta1 ON postmeta1.post_id = posts.ID";
			$sql .= " WHERE  post_type='shop_order'";
			
			
			
			$sql .= " AND postmeta1.meta_key='_order_total'";
			
			if($type == "today") 		$sql .= " AND DATE(posts.post_date) = '{$today_date}'";
			if($type == "yesterday") 	$sql .= " AND DATE(posts.post_date) = '{$yesterday_date}'";
			
			if($type == "today_yesterday"){
				$sql .= " AND (DATE(posts.post_date) = '{$today_date}'";
				$sql .= " OR DATE(posts.post_date) = '{$yesterday_date}')";
			}
					
			if($this->constants['post_order_status_found'] == 0 ){
				if(count($shop_order_status)>0){
					$in_shop_order_status = implode(",",$shop_order_status);
					$sql .= " AND  term_taxonomy.term_id IN ({$in_shop_order_status})";
				}
			}else{
				if(count($shop_order_status)>0){
					$in_shop_order_status		= implode("', '",$shop_order_status);
					$sql .= " AND  posts.post_status IN ('{$in_shop_order_status}')";
				}
			}
			
			if ($start_date != NULL &&  $end_date != NULL && $type != "today"){
				$sql .= " AND DATE(posts.post_date) BETWEEN '{$start_date}' AND '{$end_date}'";
			}
			
			if(count($hide_order_status)>0){
				$in_hide_order_status		= implode("', '",$hide_order_status);
				$sql .= " AND  posts.post_status NOT IN ('{$in_hide_order_status}')";
			}
			
			if($type == "today_yesterday"){
				$sql .= " GROUP BY group_date";
				$items =  $wpdb->get_results($sql);				
			}else{
				$items =  $wpdb->get_row($sql);
			}
			
			//$this->print_sql($sql);
			return $items;
		}
		
		//New Change ID 20140918
		function get_total_order_shipping_sales($type = 'total',$shop_order_status,$hide_order_status,$start_date,$end_date){
			global $wpdb;
				$today_date 			= $this->today;
				$yesterday_date 		= $this->yesterday;
				
				$id = "_order_shipping";
				$sql = "
					SELECT 					
					SUM(ROUND(postmeta2.meta_value,2))						as total
					,COUNT(posts.ID) 							as quantity
					FROM {$wpdb->prefix}posts as posts					
					LEFT JOIN	{$wpdb->prefix}postmeta as postmeta2 on postmeta2.post_id = posts.ID";
					
					if($this->constants['post_order_status_found'] == 0 ){
						if(count($shop_order_status)>0){
							$sql .= " 
							LEFT JOIN  {$wpdb->prefix}term_relationships 	as term_relationships 	ON term_relationships.object_id		=	posts.ID
							LEFT JOIN  {$wpdb->prefix}term_taxonomy 		as term_taxonomy 		ON term_taxonomy.term_taxonomy_id	=	term_relationships.term_taxonomy_id";
						}
					}
					
					$sql .= " WHERE posts.post_type	= 'shop_order'";
					$sql .= " AND postmeta2.meta_value > 0";
					$sql .= " AND postmeta2.meta_key 	= '{$id}'";
					
					
					if($type == "today") $sql .= " AND DATE(posts.post_date) = '{$today_date}'";
					if($type == "yesterday") 	$sql .= " AND DATE(posts.post_date) = '{$yesterday_date}'";
					if($this->constants['post_order_status_found'] == 0 ){
						if(count($shop_order_status)>0){
							$in_shop_order_status = implode(",",$shop_order_status);
							$sql .= " AND  term_taxonomy.term_id IN ({$in_shop_order_status})";
						}
					}else{
						if(count($shop_order_status)>0){
							$in_shop_order_status		= implode("', '",$shop_order_status);
							$sql .= " AND  (posts.post_status IN ('{$in_shop_order_status}') OR posts.post_status IN ('wc-refunded'))";
						}
					}
					
					if ($start_date != NULL &&  $end_date != NULL && $type == "total"){
						$sql .= " AND DATE(posts.post_date) BETWEEN '{$start_date}' AND '{$end_date}'";
					}
					
					if(count($hide_order_status)>0){
						$in_hide_order_status		= implode("', '",$hide_order_status);
						$sql .= " AND  posts.post_status NOT IN ('{$in_hide_order_status}')";
					}
					
					$items =  $wpdb->get_row($sql);
					
					return isset($items->total) ? $items->total : 0;
		}	
		
		//New Change ID 20140918
		function get_total_by_status($type = 'today',$status = 'refunded',$hide_order_status,$start_date,$end_date)	{
			global $wpdb;
			$today_date 			= $this->today;
			$yesterday_date 		= $this->yesterday;
			$sql = "SELECT";
			
			$sql .= " SUM( postmeta.meta_value) As 'total_amount', count( postmeta.post_id) AS 'total_count'";
			$sql .= "  FROM {$wpdb->prefix}posts as posts";
			
			if($this->constants['post_order_status_found'] == 0 ){
				$sql .= "
				LEFT JOIN  {$wpdb->prefix}term_relationships as term_relationships ON term_relationships.object_id=posts.ID
				LEFT JOIN  {$wpdb->prefix}term_taxonomy as term_taxonomy ON term_taxonomy.term_taxonomy_id=term_relationships.term_taxonomy_id
				LEFT JOIN  {$wpdb->prefix}terms as terms ON terms.term_id=term_taxonomy.term_id";
				
				$date_field = ($status == 'refunded') ? "post_modified" : "post_date";
			}else{
				$status = "wc-".$status;
				$date_field = ($status == 'wc-refunded') ? "post_modified" : "post_date";
			}
			
			$sql .= "
			LEFT JOIN  {$wpdb->prefix}postmeta as postmeta ON postmeta.post_id=posts.ID
			WHERE postmeta.meta_key = '_order_total' AND posts.post_type='shop_order'";
			
			
						
			if($type == "today" || $type == "today") $sql .= " AND DATE(posts.{$date_field}) = '".$today_date."'";
			if($type == "yesterday") 	$sql .=" AND DATE(posts.{$date_field}) = '".$yesterday_date."'";
			
			
			if ($start_date != NULL &&  $end_date != NULL && $type != "today"){
				$sql .= " AND DATE(posts.{$date_field}) BETWEEN '{$start_date}' AND '{$end_date}'";
			}
			
			if($this->constants['post_order_status_found'] == 0 ){
				$sql .= " AND  terms.name IN ('{$status}')";
				if(strlen($status)>0){
					$sql .= " AND  terms.slug IN ('{$status}')";
				}
			}else{
				if(strlen($status)>0){
					$sql .= " AND  posts.post_status IN ('{$status}')";
				}
			}
			
			if(count($hide_order_status)>0){
				$in_hide_order_status		= implode("', '",$hide_order_status);
				$sql .= " AND  posts.post_status NOT IN ('{$in_hide_order_status}')";
			}
			
			if($this->constants['post_order_status_found'] == 0 ){
				$sql .= " Group BY terms.term_id ORDER BY total_amount DESC";
			}else{
				$sql .= " Group BY posts.post_status ORDER BY total_amount DESC";
			}
			
			return $wpdb->get_row($sql);
		
		}
		
		//New Change ID 20140918
		function get_total_of_coupon($type = "today",$shop_order_status,$hide_order_status,$start_date,$end_date){
				global $wpdb,$options;
				$today_date 			= $this->today;
				$yesterday_date 		= $this->yesterday;
				$sql = "
				SELECT				
				SUM(woocommerce_order_itemmeta.meta_value) As 'total_amount', 
				Count(*) AS 'total_count' 
				FROM {$wpdb->prefix}woocommerce_order_items as woocommerce_order_items 
				LEFT JOIN  {$wpdb->prefix}woocommerce_order_itemmeta as woocommerce_order_itemmeta ON woocommerce_order_itemmeta.order_item_id=woocommerce_order_items.order_item_id
				LEFT JOIN  {$wpdb->prefix}posts as posts ON posts.ID=woocommerce_order_items.order_id";
				
				if($this->constants['post_order_status_found'] == 0 ){
					if(count($shop_order_status)>0){
						$sql .= " 
						LEFT JOIN  {$wpdb->prefix}term_relationships 	as term_relationships 	ON term_relationships.object_id		=	posts.ID
						LEFT JOIN  {$wpdb->prefix}term_taxonomy 		as term_taxonomy 		ON term_taxonomy.term_taxonomy_id	=	term_relationships.term_taxonomy_id";
					}
				}
				
				$sql .= "
				WHERE 
				woocommerce_order_items.order_item_type='coupon' 
				AND woocommerce_order_itemmeta.meta_key='discount_amount'
				AND posts.post_type='shop_order'
				";
				
				if($type == "today") $sql .= " AND DATE(posts.post_date) = '{$today_date}'";
				if($type == "yesterday") 	$sql .= " AND DATE(posts.post_date) = '{$yesterday_date}'";
				
				if($this->constants['post_order_status_found'] == 0 ){
					if(count($shop_order_status)>0){
						$in_shop_order_status = implode(",",$shop_order_status);
						$sql .= " AND  term_taxonomy.term_id IN ({$in_shop_order_status})";
					}
				}else{
					if(count($shop_order_status)>0){
						$in_shop_order_status		= implode("', '",$shop_order_status);
						$sql .= " AND  posts.post_status IN ('{$in_shop_order_status}')";
					}
				}
				
				if ($start_date != NULL &&  $end_date != NULL && $type != "today"){
					$sql .= " AND DATE(posts.post_date) BETWEEN '{$start_date}' AND '{$end_date}'";
				}
				
				if(count($hide_order_status)>0){
					$in_hide_order_status		= implode("', '",$hide_order_status);
					$sql .= " AND  posts.post_status NOT IN ('{$in_hide_order_status}')";
				}
				
				//$this->print_sql($sql);
				return $order_items = $wpdb->get_row($sql); 
				
				///$this->print_array($order_items);
		}
		
		//New Change ID 20140918	
		function get_total_of_order($type = "today", $meta_key="_order_tax",$order_item_type="tax",$shop_order_status,$hide_order_status,$start_date,$end_date){
			global $wpdb;
			$today_date 			= $this->today;
			$yesterday_date 		= $this->yesterday;
			
			$sql = "  SELECT";
			$sql .= " SUM(ROUND(postmeta1.meta_value,2)) 	AS 'total_amount'";
			$sql .= " ,count(posts.ID) 				AS 'total_count'";
			$sql .= " FROM {$wpdb->prefix}posts as posts";			
			$sql .= " LEFT JOIN  {$wpdb->prefix}postmeta as postmeta1 ON postmeta1.post_id=posts.ID";			
			
			//$sql .= " FROM {$wpdb->prefix}woocommerce_order_items as woocommerce_order_items";			
			//$sql .= " LEFT JOIN  {$wpdb->prefix}postmeta as postmeta1 ON postmeta1.post_id=woocommerce_order_items.order_id";			
			
			//$sql .= " LEFT JOIN  {$wpdb->prefix}posts as posts ON posts.ID=	woocommerce_order_items.order_id";
			
			if($this->constants['post_order_status_found'] == 0 ){
				if(count($shop_order_status)>0){
					$sql .= " 
					LEFT JOIN  {$wpdb->prefix}term_relationships 	as term_relationships 	ON term_relationships.object_id		=	posts.ID
					LEFT JOIN  {$wpdb->prefix}term_taxonomy 		as term_taxonomy 		ON term_taxonomy.term_taxonomy_id	=	term_relationships.term_taxonomy_id";
				}
			}
			
			//$sql .= " WHERE postmeta1.meta_key = '{$meta_key}' AND woocommerce_order_items.order_item_type = '{$order_item_type}'";
			$sql .= " WHERE postmeta1.meta_key = '{$meta_key}' AND posts.post_type = 'shop_order' AND postmeta1.meta_value > 0";
			//$sql .= " AND woocommerce_order_items.order_item_type = '{$order_item_type}'";
			
			$sql .= " AND posts.post_type='shop_order' ";
			
			if($type == "today") $sql .= " AND DATE(posts.post_date) = '{$today_date}'";
			if($type == "yesterday") 	$sql .= " AND DATE(posts.post_date) = '{$yesterday_date}'";
			
			if($this->constants['post_order_status_found'] == 0 ){
				if(count($shop_order_status)>0){
					$in_shop_order_status = implode(",",$shop_order_status);
					$sql .= " AND  term_taxonomy.term_id IN ({$in_shop_order_status})";
				}
			}else{
				if(count($shop_order_status)>0){
					$in_shop_order_status		= implode("', '",$shop_order_status);
					//$sql .= " AND  posts.post_status IN ('{$in_shop_order_status}')";
					$sql .= " AND  (posts.post_status IN ('{$in_shop_order_status}') OR posts.post_status IN ('wc-refunded'))";
				}
			}
			
			if ($start_date != NULL &&  $end_date != NULL && $type != "today"){
				$sql .= " AND DATE(posts.post_date) BETWEEN '{$start_date}' AND '{$end_date}'";
			}
			
			if(count($hide_order_status)>0){
				$in_hide_order_status		= implode("', '",$hide_order_status);
				$sql .= " AND  posts.post_status NOT IN ('{$in_hide_order_status}')";
			}
			
			return $order_items = $wpdb->get_row($sql);
			
			
		}
		
		//New Change ID 20140918
		function get_last_order_details($shop_order_status,$hide_order_status,$start_date,$end_date){
			global $wpdb;
			
			$sql = "SELECT ";					
			$sql .= " posts.ID AS last_order_id, posts.post_date AS last_order_date, posts.post_status AS last_order_status, DATEDIFF('{$this->constants['datetime']}', posts.post_date) AS last_order_day, '{$this->constants['datetime']}' AS current_datetime" ;
			$sql .= " FROM {$wpdb->prefix}posts as posts";
			if($this->constants['post_order_status_found'] == 0 ){
				if(count($shop_order_status)>0){
					$sql .= " 
					LEFT JOIN  {$wpdb->prefix}term_relationships 	as term_relationships 	ON term_relationships.object_id		=	posts.ID
					LEFT JOIN  {$wpdb->prefix}term_taxonomy 		as term_taxonomy 		ON term_taxonomy.term_taxonomy_id	=	term_relationships.term_taxonomy_id";
				}
			}
			$sql .= " WHERE  posts.post_type='shop_order'";
			
			if($this->constants['post_order_status_found'] == 0 ){
				if(count($shop_order_status)>0){
					$in_shop_order_status = implode(",",$shop_order_status);
					$sql .= " AND  term_taxonomy.term_id IN ({$in_shop_order_status})";
				}
			}else{
				if(count($shop_order_status)>0){
					$in_shop_order_status		= implode("', '",$shop_order_status);
					$sql .= " AND  posts.post_status IN ('{$in_shop_order_status}')";
				}
			}
			
			if ($start_date != NULL &&  $end_date != NULL){
				$sql .= " AND DATE(posts.post_date) BETWEEN '{$start_date}' AND '{$end_date}'";
			}
			
			if(count($hide_order_status)>0){
				$in_hide_order_status		= implode("', '",$hide_order_status);
				$sql .= " AND  posts.post_status NOT IN ('{$in_hide_order_status}')";
			}
			
			$sql .= " Order By posts.post_date DESC ";
			
			$sql .= " LIMIT 1";
			
			$wpdb->query("SET SQL_BIG_SELECTS=1");
			
			$order_items = $wpdb->get_row($sql);
			
			//$this->print_array($order_items);
			
			return $order_items;
		}
		
		function get_total_products_count(){
			global $wpdb,$sql,$Limit;
			$sql = "SELECT COUNT(*) AS 'product_count'  FROM {$wpdb->prefix}posts as posts WHERE  post_type='product' AND post_status = 'publish'";
			return $wpdb->get_var($sql);
		}	
		
		function get_total_categories_count(){
			global $wpdb,$sql,$Limit;
			$sql = "SELECT COUNT(*) As 'category_count' FROM {$wpdb->prefix}term_taxonomy as term_taxonomy  
					LEFT JOIN  {$wpdb->prefix}terms as terms ON terms.term_id=term_taxonomy.term_id
			WHERE taxonomy ='product_cat'";
			return $wpdb->get_var($sql);
			//print_array($order_items);		
		}
		
		function get_total_today_order_customer($type = 'total', $guest_user = false){
			global $wpdb;
			$today_date 			= $this->today;
			$yesterday_date 		= $this->yesterday;
			//$sql = "SELECT count(postmeta.meta_value), posts.ID, posts.post_date, postmeta.meta_value as customer_user, users.user_registered
			$sql = "SELECT ";
			if(!$guest_user){
				$sql .= " users.ID, ";
			}
			$sql .= " posts.post_date
			FROM {$wpdb->prefix}posts as posts
			LEFT JOIN  {$wpdb->prefix}postmeta as postmeta ON postmeta.post_id = posts.ID";
			
			if(!$guest_user){
				$sql .= " LEFT JOIN  {$wpdb->prefix}users as users ON users.ID = postmeta.meta_value";
			}
			
			$sql .= " WHERE  posts.post_type = 'shop_order'";
			
			$sql .= " AND postmeta.meta_key = '_customer_user'";
			
			if($guest_user){
				$sql .= " AND postmeta.meta_value = 0";
				if($type == "today")		$sql .= " AND DATE(posts.post_date) = '{$this->today}'";
				if($type == "yesterday")	$sql .= " AND DATE(posts.post_date) = '{$this->yesterday}'";
			}else{
				$sql .= " AND postmeta.meta_value > 0";
				if($type == "today")		$sql .= " AND DATE(users.user_registered) = '{$this->today}'";
				if($type == "yesterday")	$sql .= " AND DATE(users.user_registered) = '{$this->yesterday}'";
			}
			
			if(!$guest_user){
				$sql .= " GROUP BY  postmeta.meta_value";
			}else{
				$sql .= " GROUP BY  posts.ID";		
			}
			
			
			
			$sql .= " ORDER BY posts.post_date desc";
			
			//echo $type;
			//$this->print_sql($sql);
			//
			$user =  $wpdb->get_results($sql);
			//$this->print_array($user);
			//echo "<br />";
			$count = count($user);
			//echo "<br />";
			//echo "<br />";
			return $count;
		}
		
		//New Change ID 20140918	
		function sales_order_count_value($shop_order_status,$hide_order_status,$start_date,$end_date){	
			global $wpdb;		
			$CDate = $this->today;
			$url_shop_order_status	= "";
			$in_shop_order_status	= "";
			
			$in_post_order_status	= "";
			
			if($this->constants['post_order_status_found'] == 0 ){
				if(count($shop_order_status)>0){
					$in_shop_order_status	= implode(",",$shop_order_status);
					$url_shop_order_status	= "&order_status_id=".$in_shop_order_status;
				}
			}else{
				if(count($shop_order_status)>0){
					$in_post_order_status	= implode("', '",$shop_order_status);
					
					$url_shop_order_status	= implode(",",$shop_order_status);
					$url_shop_order_status	= "&order_status=".$url_shop_order_status;
				}
				
			}
			
			
			$url_post_status = "";
			$in_post_status = "";
			$in_hide_order_status = "";
			$url_hide_order_status = "";
			if(count($hide_order_status)>0){
				$in_hide_order_status		= implode("', '",$hide_order_status);				
				
				$url_hide_order_status	= implode(",",$hide_order_status);
				$url_hide_order_status 	= "&hide_order_status=".$url_hide_order_status;						
			}	
			/*Today*/
			/*Today*/
			$sql = "SELECT 
					SUM(postmeta.meta_value)AS 'OrderTotal' 
					,COUNT(*) AS 'OrderCount'
					,'Today' AS 'SalesOrder'
					
					FROM {$wpdb->prefix}postmeta as postmeta 
					LEFT JOIN  {$wpdb->prefix}posts as posts ON posts.ID=postmeta.post_id";
					
					if(strlen($in_shop_order_status)>0){
						$sql .= " 
						LEFT JOIN  {$wpdb->prefix}term_relationships 	as term_relationships 	ON term_relationships.object_id		=	posts.ID
						LEFT JOIN  {$wpdb->prefix}term_taxonomy 		as term_taxonomy 		ON term_taxonomy.term_taxonomy_id	=	term_relationships.term_taxonomy_id";
					}
					
					$sql .= " WHERE meta_key='_order_total' 
					AND DATE(posts.post_date) = '".$CDate."'";
					
					$sql .= " AND posts.post_type IN ('shop_order')";
					
					if(strlen($in_shop_order_status)>0){
						$sql .= " AND  term_taxonomy.term_id IN ({$in_shop_order_status})";
					}
					
					if(strlen($in_post_order_status)>0){
						$sql .= " AND  posts.post_status IN ('{$in_post_order_status}')";
					}
					
					if(strlen($in_hide_order_status)>0){
						$sql .= " AND  posts.post_status NOT IN ('{$in_hide_order_status}')";
					}
			$today_sql = $sql;
			$sql = '';
				 
			//$sql .= "	 UNION ";
			/*Yesterday*/
		    $sql = "
					SELECT 
					SUM(postmeta.meta_value)AS 'OrderTotal' 
					,COUNT(*) AS 'OrderCount'
					,'Yesterday' AS 'Sales Order'
					
					FROM {$wpdb->prefix}postmeta as postmeta 
					LEFT JOIN  {$wpdb->prefix}posts as posts ON posts.ID=postmeta.post_id";
					if(strlen($in_shop_order_status)>0){
						$sql .= " 
						LEFT JOIN  {$wpdb->prefix}term_relationships 	as term_relationships 	ON term_relationships.object_id		=	posts.ID
						LEFT JOIN  {$wpdb->prefix}term_taxonomy 		as term_taxonomy 		ON term_taxonomy.term_taxonomy_id	=	term_relationships.term_taxonomy_id";
					}
					$sql .= " 					
					WHERE meta_key='_order_total' 
						AND  DATE(posts.post_date)= DATE(DATE_SUB(NOW(), INTERVAL 1 DAY))";
						
					$sql .= " AND posts.post_type IN ('shop_order')";
					
					if(strlen($in_shop_order_status)>0){
						$sql .= " AND  term_taxonomy.term_id IN ({$in_shop_order_status})";
					}
					
					if(strlen($in_post_order_status)>0){
						$sql .= " AND  posts.post_status IN ('{$in_post_order_status}')";
					}
					
					if(strlen($in_hide_order_status)>0){
						$sql .= " AND  posts.post_status NOT IN ('{$in_hide_order_status}')";
					}
						
			$yesterday_sql = $sql;
			$sql = '';
				
			$sql = " 
					SELECT 
					SUM(postmeta.meta_value)AS 'OrderTotal' 
					,COUNT(*) AS 'OrderCount'
					,'Week' AS 'Sales Order'
					
					FROM {$wpdb->prefix}postmeta as postmeta 
					LEFT JOIN  {$wpdb->prefix}posts as posts ON posts.ID=postmeta.post_id";
					if(strlen($in_shop_order_status)>0){
						$sql .= " 
						LEFT JOIN  {$wpdb->prefix}term_relationships 	as term_relationships 	ON term_relationships.object_id		=	posts.ID
						LEFT JOIN  {$wpdb->prefix}term_taxonomy 		as term_taxonomy 		ON term_taxonomy.term_taxonomy_id	=	term_relationships.term_taxonomy_id";
					}
					$sql .= " 
					
					WHERE meta_key='_order_total' ";
					
					$sql .= " AND WEEK(CURDATE()) = WEEK(DATE(posts.post_date))";
					$sql .= " AND YEAR(CURDATE()) = YEAR(posts.post_date)";
					
					$sql .= " AND posts.post_type IN ('shop_order')";
					
					if(strlen($in_shop_order_status)>0){
						$sql .= " AND  term_taxonomy.term_id IN ({$in_shop_order_status})";
					}
					
					if(strlen($in_post_order_status)>0){
						$sql .= " AND  posts.post_status IN ('{$in_post_order_status}')";
					}
					
					
					if(strlen($in_hide_order_status)>0){
						$sql .= " AND  posts.post_status NOT IN ('{$in_hide_order_status}')";
					}
					
			$week_sql = $sql;
			
			$sql = '';
			/*Month*/
			$sql = "
					SELECT 
					SUM(postmeta.meta_value)AS 'OrderTotal' 
					,COUNT(*) AS 'OrderCount'
					,'Month' AS 'Sales Order'
					
					FROM {$wpdb->prefix}postmeta as postmeta 
					LEFT JOIN  {$wpdb->prefix}posts as posts ON posts.ID=postmeta.post_id";
					if(strlen($in_shop_order_status)>0){
						$sql .= " 
						LEFT JOIN  {$wpdb->prefix}term_relationships 	as term_relationships 	ON term_relationships.object_id		=	posts.ID
						LEFT JOIN  {$wpdb->prefix}term_taxonomy 		as term_taxonomy 		ON term_taxonomy.term_taxonomy_id	=	term_relationships.term_taxonomy_id";
					}
					$sql .= " 
					
					WHERE meta_key='_order_total' 
				 	AND MONTH(DATE(CURDATE())) = MONTH( DATE(posts.post_date))					
					AND YEAR(DATE(CURDATE())) = YEAR( DATE(posts.post_date))
					";
					
					$sql .= " AND posts.post_type IN ('shop_order')";
					
					if(strlen($in_shop_order_status)>0){
						$sql .= " AND  term_taxonomy.term_id IN ({$in_shop_order_status})";
					}
					
					if(strlen($in_post_order_status)>0){
						$sql .= " AND  posts.post_status IN ('{$in_post_order_status}')";
					}
					
					
					if(strlen($in_hide_order_status)>0){
						$sql .= " AND  posts.post_status NOT IN ('{$in_hide_order_status}')";
					}
			$month_sql = $sql;
			$sql = '';
					
			/*Year*/
			$sql = "SELECT 
					SUM(postmeta.meta_value)AS 'OrderTotal' 
					,COUNT(*) AS 'OrderCount'
					,'Year' AS 'Sales Order'
					
					FROM {$wpdb->prefix}postmeta as postmeta 
					LEFT JOIN  {$wpdb->prefix}posts as posts ON posts.ID=postmeta.post_id";
					if(strlen($in_shop_order_status)>0){
						$sql .= " 
						LEFT JOIN  {$wpdb->prefix}term_relationships 	as term_relationships 	ON term_relationships.object_id		=	posts.ID
						LEFT JOIN  {$wpdb->prefix}term_taxonomy 		as term_taxonomy 		ON term_taxonomy.term_taxonomy_id	=	term_relationships.term_taxonomy_id";
					}
					$sql .= " 					
					WHERE meta_key='_order_total' 
				 	AND YEAR(DATE(CURDATE())) = YEAR( DATE(posts.post_date))
					
					";
					
					$sql .= " AND posts.post_type IN ('shop_order')";
					
					if(strlen($in_shop_order_status)>0){
						$sql .= " AND  term_taxonomy.term_id IN ({$in_shop_order_status})";
					}
					
					
					if(strlen($in_post_order_status)>0){
						$sql .= " AND  posts.post_status IN ('{$in_post_order_status}')";
					}
					
						
					if(strlen($in_hide_order_status)>0){
						$sql .= " AND  posts.post_status NOT IN ('{$in_hide_order_status}')";
					}
				$year_sql = $sql;
				
				
				$sql = '';				
				$sql .= $today_sql;
				$sql .= " UNION ";
				$sql .= $yesterday_sql;
				$sql .= " UNION ";
				$sql .= $week_sql;
				$sql .= " UNION ";
				$sql .= $month_sql;
				$sql .= " UNION ";
				$sql .= $year_sql;
				
				$order_items = $wpdb->get_results($sql );
				if($order_items>0):
					$admin_url = admin_url("admin.php?page=".$this->constants['plugin_key']."_details_page")."{$url_hide_order_status}{$url_shop_order_status}&detail_view=no&";
					?>	
                     <table style="width:100%" class="widefat">
                        <thead>
                            <tr class="first">
                                <th><?php _e( 'Sales Order', 'icwoocommerce_textdomains'); ?></th>
                                <th class="item_count"><?php _e( 'Order Count', 'icwoocommerce_textdomains'); ?></th>
                                <th class="item_amount amount"><?php _e( 'Amount', 'icwoocommerce_textdomains'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php					
                                foreach ( $order_items as $key => $order_item ) {
                                if($key%2 == 1){$alternate = "alternate ";}else{$alternate = "";};
                            ?>
                                <tr class="<?php echo $alternate."row_".$key;?>">
                                	<?php if($order_item->OrderCount> 0):?>
                                    <td><a href="<?php echo $admin_url."sales_order=".strtolower($order_item->SalesOrder)."&page_title=".$order_item->SalesOrder; ?>"><?php echo $order_item->SalesOrder?></a></td>
                                    <?php else:?>
                                    <td><?php echo $order_item->SalesOrder?></td>
                                    <?php endif;?>
                                    <td class="item_count"><?php echo $order_item->OrderCount?></td>
                                    <td class="item_amount amount"><?php echo $this->price($order_item->OrderTotal);?></td>
                                </tr>
                             <?php } ?>	
                        <tbody>           
                    </table>		
                    <?php
				else:
					echo '<p>'.__("No Order found.", 'icwoocommerce_textdomains').'</p>';
				endif;
		}
		
		//New Change ID 20140918
		function sales_order_status($shop_order_status,$hide_order_status,$start_date,$end_date){
			//show_seleted_order_status
			global $wpdb;
			
			$sql = "SELECT
			
			COUNT(postmeta.meta_value) AS 'Count'
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
			
				
			
			$url_hide_order_status = "";
			if(count($hide_order_status)>0){
				$in_hide_order_status		= implode("', '",$hide_order_status);
				$sql .= " AND  posts.post_status NOT IN ('{$in_hide_order_status}')";
				
				$url_hide_order_status	= implode(",",$hide_order_status);
				$url_hide_order_status = "&hide_order_status=".$url_hide_order_status;
			}
			
			if($this->constants['post_order_status_found'] == 0 ){
				$sql .= " AND  term_taxonomy.taxonomy = 'shop_order_status'";
			}
			
			//Added 20150217
			$show_seleted_order_status	= $this->get_setting('show_seleted_order_status',$this->constants['plugin_options'], 0);
			if($show_seleted_order_status == 1){
				$url_shop_order_status	= "";
				if($this->constants['post_order_status_found'] == 0 ){
					if(count($shop_order_status)>0){
						$in_shop_order_status = implode(",",$shop_order_status);
						$sql .= " AND  term_taxonomy.term_id IN ({$in_shop_order_status})";
						
						$url_shop_order_status	= "&order_status_id=".$in_shop_order_status;
					}
				}else{
					if(count($shop_order_status)>0){
						$in_shop_order_status		= implode("', '",$shop_order_status);
						$sql .= " AND  posts.post_status IN ('{$in_shop_order_status}')";
						
						$url_shop_order_status	= implode(",",$shop_order_status);
						$url_shop_order_status	= "&order_status=".$url_shop_order_status;
					}
				}
			}
			
			if($this->constants['post_order_status_found'] == 0 ){
				$sql .= " Group BY terms.term_id ORDER BY Total DESC";
			}else{
				$sql .= " Group BY posts.post_status ORDER BY Total DESC";
			}
			
			
			
			$order_items = $wpdb->get_results($sql);
			
				if(count($order_items)>0):
					$admin_url 		= admin_url("admin.php?page=".$this->constants['plugin_key']."_details_page")."&end_date=".$end_date."&start_date=".$start_date."&detail_view=no{$url_hide_order_status}";	
					if($this->constants['post_order_status_found'] == 0 ){
						$admin_url 		.= "&order_status_id=";	
					}else{
						$admin_url 		.= "&order_status=";	
						
						if(function_exists('wc_get_order_statuses')){
							$order_statuses = wc_get_order_statuses();
						}else{
							$order_statuses = array();
						}
						
						foreach($order_items as $key  => $value){
							$order_items[$key]->Status = isset($order_statuses[$value->Status]) ? $order_statuses[$value->Status] : $value->Status;
						}
					}
					$all_admin_url 	= admin_url("admin.php?page=".$this->constants['plugin_key']."_report_page")."&end_date=".$end_date."&start_date=".$start_date."{$url_hide_order_status}&report_name=order_status";
					
					
					
					?>
               		
                    <table style="width:100%" class="widefat">
						<thead>
							<tr class="first">
								<th><?php _e( 'Order Status', 'icwoocommerce_textdomains'); ?></th>
								<th class="item_count"><?php _e( 'Order Count', 'icwoocommerce_textdomains'); ?></th>
								<th class="item_amount amount"><?php _e( 'Amount', 'icwoocommerce_textdomains'); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php					
							foreach ( $order_items as $key => $order_item ) {
							if($key%2 == 1){$alternate = "alternate ";}else{$alternate = "";};
							?>
								<tr class="<?php echo $alternate."row_".$key;?>">
									<td><a href="<?php echo $admin_url.$order_item->StatusID."&page_title=".$order_item->Status."_orders"; ?>"><?php echo '<span class="order-status '.sanitize_title($order_item->Status).'">'.ucwords(__($order_item->Status, 'icwoocommerce_textdomains')).'</span>'; ?><?php //echo Ucfirst($order_item->Status);?></a></td>
									<td class="item_count"><?php echo $order_item->Count?></td>
									<td class="item_amount amount"><?php echo $this->price($order_item->Total);?></td>
								 <?php } ?>		
								</tr>
						<tbody>           
					</table>
                    <span class="ViewAll"><a href="<?php echo $all_admin_url;?>"><?php _e("View All",'icwoocommerce_textdomains');?></a></span>
					<?php 
				else:
					echo '<p>'.__("No Status found.", 'icwoocommerce_textdomains').'</p>';					
				endif;
			
			}
			
			//New Change ID 20140918
			function top_product_list($shop_order_status,$hide_order_status,$start_date,$end_date){
					global $wpdb,$options;
					
					$optionsid	= "top_product_per_page";					
					$per_page 	= $this->get_number_only($optionsid,$this->per_page_default);
					
					$sql = "
						SELECT 
						woocommerce_order_items.order_item_name			AS 'ItemName'
						,woocommerce_order_items.order_item_id
						,SUM(woocommerce_order_itemmeta.meta_value)		AS 'Qty'
						,SUM(woocommerce_order_itemmeta2.meta_value)	AS 'Total'
						,woocommerce_order_itemmeta3.meta_value			AS ProductID
												
						FROM 		{$wpdb->prefix}woocommerce_order_items 		as woocommerce_order_items
						LEFT JOIN	{$wpdb->prefix}posts						as posts 						ON posts.ID										=	woocommerce_order_items.order_id
						LEFT JOIN	{$wpdb->prefix}woocommerce_order_itemmeta 	as woocommerce_order_itemmeta 	ON woocommerce_order_itemmeta.order_item_id		=	woocommerce_order_items.order_item_id
						LEFT JOIN	{$wpdb->prefix}woocommerce_order_itemmeta 	as woocommerce_order_itemmeta2 	ON woocommerce_order_itemmeta2.order_item_id	=	woocommerce_order_items.order_item_id
						LEFT JOIN	{$wpdb->prefix}woocommerce_order_itemmeta 	as woocommerce_order_itemmeta3 	ON woocommerce_order_itemmeta3.order_item_id	=	woocommerce_order_items.order_item_id
						
						";
						if($this->constants['post_order_status_found'] == 0 ){
							if(count($shop_order_status)>0){
								$sql .= " 
								LEFT JOIN  {$wpdb->prefix}term_relationships 	as term_relationships 	ON term_relationships.object_id		=	posts.ID
								LEFT JOIN  {$wpdb->prefix}term_taxonomy 		as term_taxonomy 		ON term_taxonomy.term_taxonomy_id	=	term_relationships.term_taxonomy_id";
							}
						}
						$sql .= "
						WHERE
						posts.post_type 								=	'shop_order'
						AND woocommerce_order_itemmeta.meta_key			=	'_qty'
						AND woocommerce_order_itemmeta2.meta_key		=	'_line_total' 
						AND woocommerce_order_itemmeta3.meta_key 		=	'_product_id'";
						
						$url_shop_order_status	= "";
						if($this->constants['post_order_status_found'] == 0 ){
							if(count($shop_order_status)>0){
								$in_shop_order_status = implode(",",$shop_order_status);
								$sql .= " AND  term_taxonomy.term_id IN ({$in_shop_order_status})";
								
								$url_shop_order_status	= "&order_status_id=".$in_shop_order_status;
							}
						}else{
							if(count($shop_order_status)>0){
								$in_shop_order_status		= implode("', '",$shop_order_status);
								$sql .= " AND  posts.post_status IN ('{$in_shop_order_status}')";
								
								$url_shop_order_status	= implode(",",$shop_order_status);
								$url_shop_order_status	= "&order_status=".$url_shop_order_status;
							}
						}
						
						if ($start_date != NULL &&  $end_date !=NULL){
							$sql .= " AND DATE(posts.post_date) BETWEEN '{$start_date}' AND '{$end_date}'";
						}
						
						$url_hide_order_status = "";
						if(count($hide_order_status)>0){
							$in_hide_order_status		= implode("', '",$hide_order_status);
							$sql .= " AND  posts.post_status NOT IN ('{$in_hide_order_status}')";
							
							$url_hide_order_status	= implode(",",$hide_order_status);
							$url_hide_order_status = "&hide_order_status=".$url_hide_order_status;
						}
						$sql .= " 
						
						GROUP BY  woocommerce_order_itemmeta3.meta_value
						Order By Total DESC
						LIMIT {$per_page}";
						$order_items = $wpdb->get_results($sql );
						
						$order_items = $wpdb->get_results($sql );
							if(count($order_items)>0):
								$admin_url		= admin_url("admin.php?page=".$this->constants['plugin_key']."_details_page")."&end_date=".$end_date."&start_date=".$start_date."{$url_hide_order_status}{$url_shop_order_status}&detail_view=yes&product_id=";
								$all_admin_url 	= admin_url("admin.php?page=".$this->constants['plugin_key']."_report_page")."&end_date=".$end_date."&start_date=".$start_date."{$url_hide_order_status}{$url_shop_order_status}&report_name=product_page";
								?>							 	
                                
                                
                                    <table style="width:100%" class="widefat">
                                        <thead>
                                            <tr class="first">
                                                <th><?php _e( 'Item Name', 'icwoocommerce_textdomains'); ?></th>
                                                <th class="item_count"><?php _e( 'Qty', 'icwoocommerce_textdomains'); ?></th>                           
                                                <th class="item_amount amount"><?php _e( 'Amount', 'icwoocommerce_textdomains'); ?></th>
                                            </tr>
                                        </thead>
                                       
                                        <tbody>
                                            <?php					
                                            foreach ( $order_items as $key => $order_item ) {
                                                if($key%2 == 1){$alternate = "alternate ";}else{$alternate = "";};?>
                                                <tr class="<?php echo $alternate."row_".$key;?>">
                                                    <td><a href="<?php echo $admin_url.$order_item->ProductID;?>"><?php echo $order_item->ItemName?></a></td>
                                                    <td class="item_count"><?php echo $order_item->Qty?></td>
                                                    <td class="item_amount amount"><?php echo $this->price($order_item->Total)?></td>
                                                </tr>
                                            <?php } ?>	
                                        <tbody> 
                                             
                                    </table>	
                                    <span class="ViewAll"><a href="<?php echo $all_admin_url;?>"><?php _e("View All",'icwoocommerce_textdomains');?></a></span>
                                <?php					
                            else:
								echo '<p>'.__("No Product found.", 'icwoocommerce_textdomains').'</p>';
                            endif;
		}
		
		//New Change ID 20140918	
		function top_billing_country($shop_order_status,$hide_order_status,$start_date,$end_date){
						global $wpdb,$options;
						$optionsid	= "top_billing_country_per_page";
						$per_page 	= $this->get_number_only($optionsid,$this->per_page_default);
						
						$billing_or_shipping	= $this->get_setting('billing_or_shipping',$this->constants['plugin_options'], 'billing');
						
						$sql = "
						SELECT SUM(postmeta1.meta_value) AS 'Total' 
						,postmeta2.meta_value AS 'BillingCountry'
						,Count(*) AS 'OrderCount'
						
						FROM {$wpdb->prefix}posts as posts
						LEFT JOIN  {$wpdb->prefix}postmeta as postmeta1 ON postmeta1.post_id=posts.ID
						LEFT JOIN  {$wpdb->prefix}postmeta as postmeta2 ON postmeta2.post_id=posts.ID";
						if($this->constants['post_order_status_found'] == 0 ){
							if(count($shop_order_status)>0){
								$sql .= " 
								LEFT JOIN  {$wpdb->prefix}term_relationships 	as term_relationships 	ON term_relationships.object_id		=	posts.ID
								LEFT JOIN  {$wpdb->prefix}term_taxonomy 		as term_taxonomy 		ON term_taxonomy.term_taxonomy_id	=	term_relationships.term_taxonomy_id";
							}
						}
						$sql .= "
						WHERE
						posts.post_type			=	'shop_order'  
						AND postmeta1.meta_key	=	'_order_total' 
						AND postmeta2.meta_key	=	'_{$billing_or_shipping}_country'";
						
						$url_shop_order_status	= "";
						if($this->constants['post_order_status_found'] == 0 ){
							if(count($shop_order_status)>0){
								$in_shop_order_status = implode(",",$shop_order_status);
								$sql .= " AND  term_taxonomy.term_id IN ({$in_shop_order_status})";
								
								$url_shop_order_status	= "&order_status_id=".$in_shop_order_status;
							}
						}else{
							if(count($shop_order_status)>0){
								$in_shop_order_status		= implode("', '",$shop_order_status);
								$sql .= " AND  posts.post_status IN ('{$in_shop_order_status}')";
								
								$url_shop_order_status	= implode(",",$shop_order_status);
								$url_shop_order_status	= "&order_status=".$url_shop_order_status;
							}
						}
							
						if ($start_date != NULL &&  $end_date !=NULL){
							$sql .= " AND DATE(posts.post_date) BETWEEN '{$start_date}' AND '{$end_date}'";
						}
						
						
						$url_hide_order_status = "";
						if(count($hide_order_status)>0){
							$in_hide_order_status		= implode("', '",$hide_order_status);
							$sql .= " AND  posts.post_status NOT IN ('{$in_hide_order_status}')";
							
							$url_hide_order_status	= implode(",",$hide_order_status);
							$url_hide_order_status = "&hide_order_status=".$url_hide_order_status;
						}
						$sql .= " 
						GROUP BY  postmeta2.meta_value 
						Order By Total DESC 						
						LIMIT {$per_page}";
						
						$order_items = $wpdb->get_results($sql); 
						if(count($order_items)>0):
							$country      = $this->get_wc_countries();//Added 20150225
							$admin_url 		= admin_url("admin.php?page=".$this->constants['plugin_key']."_details_page")."&end_date=".$end_date."&start_date=".$start_date."&detail_view=no{$url_hide_order_status}{$url_shop_order_status}&country_code=";	
							$all_admin_url	= admin_url("admin.php?page=".$this->constants['plugin_key']."_report_page")."&end_date=".$end_date."&start_date=".$start_date."{$url_hide_order_status}{$url_shop_order_status}&report_name=billing_country_page";
							?>
                            
						<table style="width:100%" class="widefat">
							<thead>
								<tr class="first">
									<th><?php echo ($billing_or_shipping == "shipping" ? __( 'Shipping Country', 'icwoocommerce_textdomains') : __( 'Billing Country', 'icwoocommerce_textdomains')); ?></th>
									<th class="item_count"><?php _e( 'Order Count', 'icwoocommerce_textdomains'); ?></th>                           
									<th class="item_amount amount"><?php _e( 'Amount', 'icwoocommerce_textdomains'); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php					
								foreach ( $order_items as $key => $order_item ) {
								if($key%2 == 1){$alternate = "alternate ";}else{$alternate = "";};
								?>
									<tr class="<?php echo $alternate."row_".$key;?>">
										<td><a href="<?php echo $admin_url.$order_item->BillingCountry;?>"><?php echo isset($country->countries[$order_item->BillingCountry])  ? $country->countries[$order_item->BillingCountry] : $order_item->BillingCountry;?></a></td></td>
										<td class="item_count"><?php echo $order_item->OrderCount?></td>
										<td class="item_amount amount"><?php echo $this->price($order_item->Total)?></td>
									 <?php } ?>		
									</tr>
							<tbody>           
						</table>
                        <span class="ViewAll"><a href="<?php echo $all_admin_url;?>"><?php _e("View All",'icwoocommerce_textdomains');?></a></span>
						<?php 
						else:
							echo '<p>'.__("No Country found.", 'icwoocommerce_textdomains').'</p>';
						endif;							
		}
		
		//New Change ID 20141119
		function top_billing_state($shop_order_status,$hide_order_status,$start_date,$end_date)
		{
						global $wpdb,$options;
						$optionsid	= "top_billing_state_per_page";
						$per_page 	= $this->get_number_only($optionsid,$this->per_page_default);
						$billing_or_shipping	= $this->get_setting('billing_or_shipping',$this->constants['plugin_options'], 'billing');
					
						$sql = "
						SELECT SUM(postmeta1.meta_value) AS 'Total' 
						,postmeta2.meta_value AS 'billing_state'
						,postmeta3.meta_value AS 'billing_country'
						,Count(*) AS 'OrderCount'";
						
						/*$sql = "
						SELECT postmeta1.meta_value AS 'Total' 
						,postmeta2.meta_value AS 'billing_state'
						,postmeta3.meta_value AS 'billing_country'
						,posts.ID  as order_id
						,'1' AS 'OrderCount'";*/
						
						$sql .= "
						FROM {$wpdb->prefix}posts as posts
						LEFT JOIN  {$wpdb->prefix}postmeta as postmeta1 ON postmeta1.post_id=posts.ID
						LEFT JOIN  {$wpdb->prefix}postmeta as postmeta2 ON postmeta2.post_id=posts.ID
						LEFT JOIN  {$wpdb->prefix}postmeta as postmeta3 ON postmeta3.post_id=posts.ID";
						if($this->constants['post_order_status_found'] == 0 ){
							if(count($shop_order_status)>0){
								$sql .= " 
								LEFT JOIN  {$wpdb->prefix}term_relationships 	as term_relationships 	ON term_relationships.object_id		=	posts.ID
								LEFT JOIN  {$wpdb->prefix}term_taxonomy 		as term_taxonomy 		ON term_taxonomy.term_taxonomy_id	=	term_relationships.term_taxonomy_id";
							}
						}
						$sql .= "
						WHERE
						posts.post_type			=	'shop_order'  
						AND postmeta1.meta_key	=	'_order_total' 
						AND postmeta2.meta_key	=	'_{$billing_or_shipping}_state'
						AND postmeta3.meta_key	=	'_{$billing_or_shipping}_country'";
						
						//$sql .= " AND postmeta3.meta_value	=	'GB'";
						
						
						$url_shop_order_status	= "";
						if($this->constants['post_order_status_found'] == 0 ){
							if(count($shop_order_status)>0){
								$in_shop_order_status = implode(",",$shop_order_status);
								$sql .= " AND  term_taxonomy.term_id IN ({$in_shop_order_status})";
								
								$url_shop_order_status	= "&order_status_id=".$in_shop_order_status;
							}
						}else{
							if(count($shop_order_status)>0){
								$in_shop_order_status		= implode("', '",$shop_order_status);
								$sql .= " AND  posts.post_status IN ('{$in_shop_order_status}')";
								
								$url_shop_order_status	= implode(",",$shop_order_status);
								$url_shop_order_status	= "&order_status=".$url_shop_order_status;
							}
						}
							
						if ($start_date != NULL &&  $end_date !=NULL){
							$sql .= " AND DATE(posts.post_date) BETWEEN '{$start_date}' AND '{$end_date}'";
						}
						
						
						$url_hide_order_status = "";
						if(count($hide_order_status)>0){
							$in_hide_order_status		= implode("', '",$hide_order_status);
							$sql .= " AND  posts.post_status NOT IN ('{$in_hide_order_status}')";
							
							$url_hide_order_status	= implode(",",$hide_order_status);
							$url_hide_order_status = "&hide_order_status=".$url_hide_order_status;
						}
						$sql .= " 
						GROUP BY  postmeta2.meta_value
						Order By Total DESC 						
						LIMIT {$per_page}";
						//GROUP BY  postmeta2.meta_value 						
						
						$order_items = $wpdb->get_results($sql); 
						
						//$this->print_array($sql);
						//$this->print_array($order_items);
						
						if(count($order_items)>0):
							$country      = $this->get_wc_countries();//Added 20150225
							$admin_url 		= admin_url("admin.php?page=".$this->constants['plugin_key']."_details_page")."&end_date=".$end_date."&start_date=".$start_date."&detail_view=no{$url_hide_order_status}{$url_shop_order_status}&country_code=";	
							$all_admin_url	= admin_url("admin.php?page=".$this->constants['plugin_key']."_report_page")."&end_date=".$end_date."&start_date=".$start_date."{$url_hide_order_status}{$url_shop_order_status}&report_name=billing_state_page";
							?>
                            
						<table style="width:100%" class="widefat">
							<thead>
								<tr class="first">
                                	<th><?php echo ($billing_or_shipping == "shipping" ? __( 'Shipping State', 'icwoocommerce_textdomains') : __( 'Billing State', 'icwoocommerce_textdomains')); ?></th>
									<th class="item_count"><?php _e( 'Order Count', 'icwoocommerce_textdomains'); ?></th>                           
									<th class="item_amount amount"><?php _e( 'Amount', 'icwoocommerce_textdomains'); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php					
								foreach ( $order_items as $key => $order_item ) {
									
									$billing_state =  $this->get_billling_state_name($order_item->billing_country,$order_item->billing_state);
									if($key%2 == 1){$alternate = "alternate ";}else{$alternate = "";};
								?>
									<tr class="<?php echo $alternate."row_".$key;?>">
										<td><a href="<?php echo $admin_url.$order_item->billing_country."&state_code=".$order_item->billing_state;?>"><?php echo $billing_state;?></a></td>
										<td class="item_count"><?php echo $order_item->OrderCount?></td>
										<td class="item_amount amount"><?php echo $this->price($order_item->Total)?></td>
									 <?php } ?>		
									</tr>
							<tbody>           
						</table>
                        <span class="ViewAll"><a href="<?php echo $all_admin_url;?>"><?php _e("View All",'icwoocommerce_textdomains');?></a></span>
						<?php 
						else:
							echo '<p>'.__("No State found.", 'icwoocommerce_textdomains').'</p>';
						endif;							
		}
		
		
		//New Change ID 20140918
		function get_payment_gateway_list($shop_order_status,$hide_order_status,$start_date,$end_date){
					global $wpdb,$options;
					$optionsid	= "top_payment_gateway_per_page";
					$per_page 	= $this->get_number_only($optionsid,$this->per_page_default);
					
					$sql = "
					SELECT postmeta2.meta_value AS 'payment_method_title' 
					,SUM(postmeta1.meta_value) AS 'payment_amount_total'
					,COUNT(postmeta1.meta_value) As 'order_count'					
					FROM {$wpdb->prefix}posts as posts
					LEFT JOIN  {$wpdb->prefix}postmeta as postmeta1 ON postmeta1.post_id=posts.ID
					LEFT JOIN  {$wpdb->prefix}postmeta as postmeta2 ON postmeta2.post_id=posts.ID";
					if($this->constants['post_order_status_found'] == 0 ){
						if(count($shop_order_status)>0){
							$sql .= " 
							LEFT JOIN  {$wpdb->prefix}term_relationships 	as term_relationships 	ON term_relationships.object_id		=	posts.ID
							LEFT JOIN  {$wpdb->prefix}term_taxonomy 		as term_taxonomy 		ON term_taxonomy.term_taxonomy_id	=	term_relationships.term_taxonomy_id";
						}
					}
						$sql .= "
					WHERE
					posts.post_type='shop_order'  
					AND postmeta1.meta_key='_order_total' 
					AND postmeta2.meta_key='_payment_method_title'
					";
							
						$url_shop_order_status	= "";
						if($this->constants['post_order_status_found'] == 0 ){
							if(count($shop_order_status)>0){
								$in_shop_order_status = implode(",",$shop_order_status);
								$sql .= " AND  term_taxonomy.term_id IN ({$in_shop_order_status})";
								
								$url_shop_order_status	= "&order_status_id=".$in_shop_order_status;
							}
						}else{
							if(count($shop_order_status)>0){
								$in_shop_order_status		= implode("', '",$shop_order_status);
								$sql .= " AND  posts.post_status IN ('{$in_shop_order_status}')";
								
								$url_shop_order_status	= implode(",",$shop_order_status);
								$url_shop_order_status	= "&order_status=".$url_shop_order_status;
							}
						}
					
					if ($start_date != NULL &&  $end_date !=NULL){
						$sql .= " AND DATE(posts.post_date) BETWEEN '{$start_date}' AND '{$end_date}'";
					}
					
					$url_hide_order_status = "";
					if(count($hide_order_status)>0){
						$in_hide_order_status		= implode("', '",$hide_order_status);
						$sql .= " AND  posts.post_status NOT IN ('{$in_hide_order_status}')";
						
						$url_hide_order_status	= implode(",",$hide_order_status);
						$url_hide_order_status = "&hide_order_status=".$url_hide_order_status;
					}
					$sql .= " 
					GROUP BY postmeta2.meta_value
					Order BY payment_amount_total DESC LIMIT {$per_page}";
					
					$order_items = $wpdb->get_results($sql);
					
					if(count($order_items)>0):
							$admin_url 		= admin_url("admin.php?page=".$this->constants['plugin_key']."_details_page")."&end_date=".$end_date."&start_date=".$start_date."&detail_view=no{$url_hide_order_status}{$url_shop_order_status}&payment_method=";							
							$all_admin_url 	= admin_url("admin.php?page=".$this->constants['plugin_key']."_report_page")."&end_date=".$end_date."&start_date=".$start_date."{$url_hide_order_status}{$url_shop_order_status}&report_name=payment_gateway_page";
						?>
							
						<table style="width:100%" class="widefat">
						<thead>
							<tr class="first">
								<th><?php _e( 'Payment Method', 'icwoocommerce_textdomains'); ?></th>
								<th class="item_count"><?php _e( 'Order Count', 'icwoocommerce_textdomains'); ?></th>
								<th class="item_amount amount"><?php _e( 'Amount', 'icwoocommerce_textdomains'); ?></th>                           
							</tr>
						</thead>
						<tbody>
							<?php					
							foreach ( $order_items as $key => $order_item ) {
								if($key%2 == 1){$alternate = "alternate ";}else{$alternate = "";};
								?>
								<tr class="<?php echo $alternate."row_".$key;?>">
									<td><a href="<?php echo $admin_url.strtolower($order_item->payment_method_title)?>"><?php echo $order_item->payment_method_title;?></a></td>
									<td class="item_count"><?php echo $order_item->order_count?></td>
									<td class="item_amount amount"><?php echo $this->price($order_item->payment_amount_total);?></td>
								</tr>
								 <?php } ?>						
							<tbody>
						</table>
                        <span class="ViewAll"><a href="<?php echo $all_admin_url;?>"><?php _e("View All",'icwoocommerce_textdomains');?></a></span>
						<?php 
					else:
						echo '<p>'.__("No Payment found.", 'icwoocommerce_textdomains').'</p>';
					endif;
		}
		
		//New Change ID 20140918
		function recent_orders($shop_order_status,$hide_order_status,$start_date,$end_date){
				global $wpdb,$options;
				$optionsid	= "recent_order_per_page";
				$per_page 	= $this->get_number_only($optionsid,$this->per_page_default);
				
				$sql = "SELECT ";					
				$sql .= " posts.ID AS order_id, posts.post_date AS order_date, posts.post_status AS order_status, customer_user.meta_value AS customer_user";
				$sql .= " FROM {$wpdb->prefix}posts as posts";
				if($this->constants['post_order_status_found'] == 0 ){
					if(count($shop_order_status)>0){
						$sql .= " 
						LEFT JOIN  {$wpdb->prefix}term_relationships 	as term_relationships 	ON term_relationships.object_id		=	posts.ID
						LEFT JOIN  {$wpdb->prefix}term_taxonomy 		as term_taxonomy 		ON term_taxonomy.term_taxonomy_id	=	term_relationships.term_taxonomy_id";
					}
				}
				$sql .= " LEFT JOIN  {$wpdb->prefix}postmeta as customer_user 	ON customer_user.post_id		=	posts.ID";
				$sql .= " WHERE  posts.post_type='shop_order'";
				
				$sql .= " AND customer_user.meta_key = '_customer_user'";
				
				$url_shop_order_status	= "";
				if($this->constants['post_order_status_found'] == 0 ){
					if(count($shop_order_status)>0){
						$in_shop_order_status = implode(",",$shop_order_status);
						$sql .= " AND  term_taxonomy.term_id IN ({$in_shop_order_status})";
						
						$url_shop_order_status	= "&order_status_id=".$in_shop_order_status;
					}
				}else{
					if(count($shop_order_status)>0){
						$in_shop_order_status		= implode("', '",$shop_order_status);
						$sql .= " AND  posts.post_status IN ('{$in_shop_order_status}')";
						
						$url_shop_order_status	= implode(",",$shop_order_status);
						$url_shop_order_status	= "&order_status=".$url_shop_order_status;
					}
				}
				
				$url_hide_order_status = "";
				if(count($hide_order_status)>0){
					$in_hide_order_status		= implode("', '",$hide_order_status);
					$sql .= " AND  posts.post_status NOT IN ('{$in_hide_order_status}')";
					
					$url_hide_order_status	= implode(",",$hide_order_status);
					$url_hide_order_status = "&hide_order_status=".$url_hide_order_status;
				}
				
				$sql .= " GROUP BY posts.ID";
				
				$sql .= " Order By posts.post_date DESC ";
				$sql .= " LIMIT {$per_page}";
				
				$wpdb->query("SET SQL_BIG_SELECTS=1");
				$order_items = $wpdb->get_results($sql);
				
				if(count($order_items)>0){
					$columns 			= $this->get_coumns();				
					$extra_meta_keys 	= apply_filters('ic_commerce_dashboard_page_extra_meta_keys', array('order_total','order_shipping','cart_discount','order_discount','total_discount','order_tax','order_shipping_tax','total_tax','transaction_id','billing_first_name','billing_last_name','billing_email','order_currency'));
					$post_ids 			= $this->get_items_id_list($order_items,'order_id');
					$postmeta_datas 	= $this->get_postmeta($post_ids, $columns,$extra_meta_keys);
					
					foreach ( $order_items as $key => $order_item ) {
							$order_id								= $order_item->order_id;
							
							$postmeta_data 	= isset($postmeta_datas[$order_id]) ? $postmeta_datas[$order_id] : array();
							
							foreach($postmeta_data as $postmeta_key => $postmeta_value){
								$order_items[$key]->{$postmeta_key}	= $postmeta_value;
							}
							
							$order_items[$key]->order_total			= isset($order_items[$key]->order_total)		? $order_items[$key]->order_total 		: 0;
							$order_items[$key]->order_shipping		= isset($order_items[$key]->order_shipping)		? $order_items[$key]->order_shipping 	: 0;
							
							$order_items[$key]->cart_discount		= isset($order_items[$key]->cart_discount)		? $order_items[$key]->cart_discount 	: 0;
							$order_items[$key]->order_discount		= isset($order_items[$key]->order_discount)		? $order_items[$key]->order_discount 	: 0;
							$order_items[$key]->total_discount 		= ($order_items[$key]->cart_discount + $order_items[$key]->order_discount);
							
							$order_items[$key]->order_tax 			= isset($order_items[$key]->order_tax)			? $order_items[$key]->order_tax 		: 0;
							$order_items[$key]->order_shipping_tax 	= isset($order_items[$key]->order_shipping_tax)	? $order_items[$key]->order_shipping_tax : 0;
							$order_items[$key]->total_tax 			= ($order_items[$key]->order_tax + $order_items[$key]->order_shipping_tax);
							
							$order_items[$key]->gross_amount 		= ($order_items[$key]->order_total + $order_items[$key]->total_discount) - ($order_items[$key]->order_shipping +  $order_items[$key]->order_shipping_tax + $order_items[$key]->order_tax );
							
							$order_items[$key]->billing_first_name	= isset($order_items[$key]->billing_first_name)	? $order_items[$key]->billing_first_name 	: '';
							$order_items[$key]->billing_last_name	= isset($order_items[$key]->billing_last_name)	? $order_items[$key]->billing_last_name 	: '';
							$order_items[$key]->billing_name		= $order_items[$key]->billing_first_name.' '.$order_items[$key]->billing_last_name;
					}
				}
				
				if(count($order_items) > 0):
				
				$TotalOrderCount 	= 0;
				$TotalAmount 		= 0;
				$TotalShipping 		= 0;
				$zero				= $this->price(0);
				$ToDate 			= $this->today;
				$FromDate 			= $this->first_order_date($this->constants['plugin_key']);
				$admin_url 			= admin_url("admin.php?page=".$this->constants['plugin_key']."_details_page")."&end_date=".$ToDate."&start_date=".$FromDate."{$url_hide_order_status}{$url_shop_order_status}&order_id=";
				
				$all_admin_url 		= admin_url("admin.php?page=".$this->constants['plugin_key']."_report_page")."{$url_hide_order_status}{$url_shop_order_status}&report_name=recent_order";
				$zero_prize			= array();
				$this->constants['date_format'] = isset($this->constants['date_format']) ? $this->constants['date_format'] : get_option( 'date_format', "Y-m-d" );
				$date_format = $this->constants['date_format'];				

				$order_items 	= apply_filters("ic_commerce_dashbaord_recent_order_data_items", $order_items);
				
				$grid_object		= $this->get_grid_object();//Added 20150223
				$order_items		= $grid_object->create_grid_items($columns,$order_items);//Added 20150223
				//foreach($columns as $key => $value)	echo "case \"{$key}\":<br>";
				
				?>
                	
                    <table style="width:100%" class="widefat">
                        <thead>
								<tr class="first">
                                	<?php 
										$cells_status = array();
										$output = "";
										foreach($columns as $key => $value):
											$td_class = $key;
											$td_width = "";
											switch($key):
												case "order_item_count":
												case "gross_amount":
												case "order_discount":
												case "cart_discount":
												case "total_discount":
												case "order_shipping":
												case "order_shipping_tax":
												case "order_tax":
												case "part_order_refund_amount":
												case "total_tax":
												case "order_total":
													$td_class .= " amount";												
													break;							
												default;
													break;
											endswitch;
											$th_value 			= $value;
											$output 			.= "\n\t<th class=\"{$td_class}\">{$th_value}</th>";											
										endforeach;
										echo $output ;
										?>
								</tr>
							</thead>
                        <tbody>
                            <?php					
                            foreach ( $order_items as $key => $order_item ) {
                                
                                $TotalAmount 		=  $TotalAmount + $order_item->order_total;
                                $TotalShipping 		= $TotalShipping + $order_item->order_shipping;
								$zero_prize[$order_item->order_currency] = isset($zero_prize[$order_item->order_currency]) ? $zero_prize[$order_item->order_currency] : $this->price(0, array('currency' => $order_item->order_currency));
                                $TotalOrderCount++;
								
                                //date_i18n($date_format,strtotime($order_item->product_date));
                                if($key%2 == 1){$alternate = "alternate ";}else{$alternate = "";};
                                ?>
                                <tr class="<?php echo $alternate."row_".$key;?>">
                                    <?php
                                        foreach($columns as $key => $value):
                                            $td_class = $key;
                                            //$td_style = $cells_status[$key];
                                            $td_value = "";
                                            switch($key):
                                                case "order_id":
                                                   $td_value = '<a href="'.$admin_url.$order_item->order_id.'&detail_view=yes" target="'.$order_item->order_id.'_blank">' . $order_item->order_id  . '</a>';
                                                    break;
                                                case "billing_name":
                                                    $td_value = ucwords(stripslashes_deep($order_item->billing_name));
                                                    break;
                                                case "billing_email":
                                                    $td_value = $this->emailLlink($order_item->billing_email,false);
                                                    break;
                                                case "item_count":
												case "transaction_id":
												case "order_item_count":
                                                    $td_value = $order_item->$key;
                                                    $td_class .= " amount";
                                                    break;
												case "order_date":
                                                    $td_value = date($date_format,strtotime($order_item->$key));
                                                    break;
                                                case "order_shipping":
                                                case "order_shipping_tax":
                                                case "order_tax":
                                                case "total_tax":
												case "gross_amount":
                                                case "order_discount":
												case "cart_discount":
												case "total_discount":
                                                case "order_total":
												case "part_order_refund_amount":
												case "order_refund_amount":
                                                    $td_value = isset($order_item->$key) ? $order_item->$key : 0;
													$td_value = $td_value > 0 ? $this->price($td_value, array('currency' => $order_item->order_currency)) : $zero_prize[$order_item->order_currency];
													$td_class .= " amount";
                                                    break;
                                                case "order_status"://New Change ID 20140918
												case "order_status_name"://New Change ID 20150225
													$td_value = isset($order_item->$key) ? $order_item->$key : '';
													$td_value = '<span class="order-status order-status-'.sanitize_title($td_value).'">'.ucwords(__($td_value, 'icwoocommerce_textdomains')).'</span>';
													break;												
                                                default:
                                                    $td_value = isset($order_item->$key) ? $order_item->$key : '';
                                                    break;
                                            endswitch;
                                            $td_content = "<td class=\"{$td_class}\">{$td_value}</td>\n";
                                            echo $td_content;
                                        endforeach;                                        	
                                    ?>
                                </tr>
                                <?php 
                            } ?>
                        </tbody>           
                    </table>
                    <style type="text/css">
                    	.iccommercepluginwrap th.order_date {
							width:auto;
						}
                    </style>
                    <span class="ViewAll"><a href="<?php echo $all_admin_url;?>"><?php _e("View All",'icwoocommerce_textdomains');?></a></span>
				<?php 
					else:
						echo '<p>'.__("No Order found.", 'icwoocommerce_textdomains').'</p>';
					endif;
		}	
		
		//New Change ID 20141125
		function top_customer_list($shop_order_status,$hide_order_status,$start_date,$end_date){
			global $wpdb,$options;
				$optionsid	= "top_customer_per_page";
				$per_page 	= $this->get_number_only($optionsid,$this->per_page_default);
				
				$sql = "SELECT SUM(postmeta1.meta_value) AS 'Total' 
								,postmeta2.meta_value AS 'BillingEmail'
								,postmeta3.meta_value AS 'FirstName'
								,postmeta5.meta_value AS 'LastName'
								,postmeta6.meta_value AS 'CompanyName'
								,CONCAT(postmeta3.meta_value, ' ',postmeta5.meta_value) AS billing_name
								,Count(postmeta2.meta_value) AS 'OrderCount'";
						
						$sql .= " ,postmeta4.meta_value AS  customer_user";
						//
						$sql .= " FROM {$wpdb->prefix}posts as posts
						LEFT JOIN  {$wpdb->prefix}postmeta as postmeta1 ON postmeta1.post_id=posts.ID
						LEFT JOIN  {$wpdb->prefix}postmeta as postmeta2 ON postmeta2.post_id=posts.ID
						LEFT JOIN  {$wpdb->prefix}postmeta as postmeta3 ON postmeta3.post_id=posts.ID
						LEFT JOIN  {$wpdb->prefix}postmeta as postmeta5 ON postmeta5.post_id=posts.ID
						LEFT JOIN  {$wpdb->prefix}postmeta as postmeta6 ON postmeta6.post_id=posts.ID";
						
						$sql .= " LEFT JOIN  {$wpdb->prefix}postmeta as postmeta4 ON postmeta4.post_id=posts.ID";
						
						if($this->constants['post_order_status_found'] == 0 ){
							if(count($shop_order_status)>0){
								$sql .= " 
								LEFT JOIN  {$wpdb->prefix}term_relationships 	as term_relationships 	ON term_relationships.object_id		=	posts.ID
								LEFT JOIN  {$wpdb->prefix}term_taxonomy 		as term_taxonomy 		ON term_taxonomy.term_taxonomy_id	=	term_relationships.term_taxonomy_id";
							}
						}
						$sql .= " 
						WHERE  
							posts.post_type='shop_order'  
							AND postmeta1.meta_key='_order_total' 
							AND postmeta2.meta_key='_billing_email'  
							AND postmeta3.meta_key='_billing_first_name'
							AND postmeta5.meta_key='_billing_last_name'
							AND postmeta6.meta_key='_billing_company'";
							
					$sql .= " AND postmeta4.meta_key='_customer_user'";
							
						$url_shop_order_status	= "";
						if($this->constants['post_order_status_found'] == 0 ){
							if(count($shop_order_status)>0){
								$in_shop_order_status = implode(",",$shop_order_status);
								$sql .= " AND  term_taxonomy.term_id IN ({$in_shop_order_status})";
								
								$url_shop_order_status	= "&order_status_id=".$in_shop_order_status;
							}
						}else{
							if(count($shop_order_status)>0){
								$in_shop_order_status		= implode("', '",$shop_order_status);
								$sql .= " AND  posts.post_status IN ('{$in_shop_order_status}')";
								
								$url_shop_order_status	= implode(",",$shop_order_status);
								$url_shop_order_status	= "&order_status=".$url_shop_order_status;
							}
						}
						
						if ($start_date != NULL &&  $end_date !=NULL){
							$sql .= " AND DATE(posts.post_date) BETWEEN '{$start_date}' AND '{$end_date}'";
						}
						
						
						$url_hide_order_status = "";
						if(count($hide_order_status)>0){
							$in_hide_order_status		= implode("', '",$hide_order_status);
							$sql .= " AND  posts.post_status NOT IN ('{$in_hide_order_status}')";
							
							$url_hide_order_status	= implode(",",$hide_order_status);
							$url_hide_order_status = "&hide_order_status=".$url_hide_order_status;
						}
						$sql .= " GROUP BY  postmeta2.meta_value
						Order By Total DESC
						LIMIT {$per_page}";
						
				$order_items = $wpdb->get_results($sql );
				
				//$this->print_array($order_items);
				if(count($order_items)>0):
				
				$admin_url 		= admin_url("admin.php?page=".$this->constants['plugin_key']."_details_page")."&end_date=".$end_date."&start_date=".$start_date."&detail_view=no{$url_hide_order_status}{$url_shop_order_status}&";				
				$all_admin_url 	= admin_url("admin.php?page=".$this->constants['plugin_key']."_report_page")."&end_date=".$end_date."&start_date=".$start_date."{$url_hide_order_status}{$url_shop_order_status}&report_name=customer_page";
				$admin_user = admin_url("user-edit.php");
				
				//
				$order_items 	= apply_filters("ic_commerce_dashbaord_top_customers_data_items", $order_items);
				
					?>
                
                
				<table style="width:100%" class="widefat">
					<thead>
						<tr class="first">
							<th><?php _e( 'Billing Name', 'icwoocommerce_textdomains'); ?></th>
							<th><?php _e( 'Company Name', 'icwoocommerce_textdomains'); ?></th>
							<th><?php _e( 'Billing Email', 'icwoocommerce_textdomains'); ?></th>
							<th class="item_count"><?php _e( 'Order Count', 'icwoocommerce_textdomains'); ?></th>
							<th class="item_amount amount"><?php _e( 'Amount', 'icwoocommerce_textdomains'); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php					
							foreach ( $order_items as $key => $order_item ) {
								$user_name = '-';
								$first_name = $order_item->FirstName;
								$billing_name = $order_item->billing_name;
								
								/*
									
								if(isset($order_item->customer_id) and strlen($order_item->customer_id) > 0 and $order_item->customer_id > 0){
									$user_details = $this->get_user_details($order_item->customer_id);
									
									
									$user_name = $user_details->user_name;
									$first_name = $user_details->first_name;
									
									$user_name = '<a href="'.$admin_user."?user_id=".$order_item->customer_id.'" target="_blank">'.$user_name.'</a>';
								}
								
								*/
								if($key%2 == 1){$alternate = "alternate ";}else{$alternate = "";};
								
								?>
								
								<tr class="<?php echo $alternate."row_".$key;?>">
                                    <td><a href="<?php echo $admin_url."paid_customer=".$order_item->BillingEmail;?>"><?php echo $billing_name;?></a></td>
									<td><?php echo $order_item->CompanyName?></td>
                                    <td><a href="<?php echo $admin_url."paid_customer=".$order_item->BillingEmail;?>"><?php echo $order_item->BillingEmail?></a></td>
									<td class="item_count"><?php echo $order_item->OrderCount?></td>
									<td class="item_amount amount"><?php echo $this->price($order_item->Total)?></td>
								</tr>
							 <?php } ?>	
					<tbody>           
				</table>
                <span class="ViewAll"><a href="<?php echo $all_admin_url;?>"><?php _e("View All",'icwoocommerce_textdomains');?></a></span>
				<?php
				else:
					echo '<p>'.__("No Customer found.", 'icwoocommerce_textdomains').'</p>';
				endif;		
			}
			
			
			//New Change ID 20140918
			function get_top_coupon_list($shop_order_status,$hide_order_status,$start_date,$end_date){
					global $wpdb,$options;

					$optionsid	= "top_coupon_per_page";
					$per_page 	= $this->get_number_only($optionsid,$this->per_page_default);
					$sql = "SELECT *, 
					woocommerce_order_items.order_item_name, 
					SUM(woocommerce_order_itemmeta.meta_value) As 'Total', 
					woocommerce_order_itemmeta.meta_value AS 'coupon_amount' , 
					Count(*) AS 'Count' 
					FROM {$wpdb->prefix}woocommerce_order_items as woocommerce_order_items 
					LEFT JOIN	{$wpdb->prefix}posts						as posts 						ON posts.ID										=	woocommerce_order_items.order_id
					LEFT JOIN  {$wpdb->prefix}woocommerce_order_itemmeta 	as woocommerce_order_itemmeta	ON woocommerce_order_itemmeta.order_item_id		=	woocommerce_order_items.order_item_id";
					if($this->constants['post_order_status_found'] == 0 ){
						if(count($shop_order_status)>0){
							$sql .= " 
							LEFT JOIN  {$wpdb->prefix}term_relationships 	as term_relationships 	ON term_relationships.object_id		=	posts.ID
							LEFT JOIN  {$wpdb->prefix}term_taxonomy 		as term_taxonomy 		ON term_taxonomy.term_taxonomy_id	=	term_relationships.term_taxonomy_id";
						}
					}
					$sql .= "
					
					WHERE 
					posts.post_type 								=	'shop_order'
					AND woocommerce_order_items.order_item_type		=	'coupon' 
					AND woocommerce_order_itemmeta.meta_key			=	'discount_amount'";
							
					$url_shop_order_status	= "";
					if($this->constants['post_order_status_found'] == 0 ){
						if(count($shop_order_status)>0){
							$in_shop_order_status = implode(",",$shop_order_status);
							$sql .= " AND  term_taxonomy.term_id IN ({$in_shop_order_status})";
							
							$url_shop_order_status	= "&order_status_id=".$in_shop_order_status;
						}
					}else{
						if(count($shop_order_status)>0){
							$in_shop_order_status		= implode("', '",$shop_order_status);
							$sql .= " AND  posts.post_status IN ('{$in_shop_order_status}')";
							//$this->print_array($shop_order_status);
							
							$url_shop_order_status	= implode(",",$shop_order_status);
							$url_shop_order_status	= "&order_status=".$url_shop_order_status;
						}
					}
					
					if ($start_date != NULL &&  $end_date !=NULL){
						$sql .= " AND DATE(posts.post_date) BETWEEN '{$start_date}' AND '{$end_date}'";
					}
					
					
					$url_hide_order_status = "";
					if(count($hide_order_status)>0){
						$in_hide_order_status		= implode("', '",$hide_order_status);
						$sql .= " AND  posts.post_status NOT IN ('{$in_hide_order_status}')";
						
						$url_hide_order_status	= implode(",",$hide_order_status);
						$url_hide_order_status = "&hide_order_status=".$url_hide_order_status;
					}
					$sql .= " 
					Group BY woocommerce_order_items.order_item_name
					ORDER BY Total DESC
					LIMIT {$per_page}";
					 
					$order_items = $wpdb->get_results($sql); 
					if(count($order_items)>0):
                    	$all_admin_url = admin_url("admin.php?page=".$this->constants['plugin_key']."_report_page")."&end_date=".$end_date."&start_date=".$start_date."{$url_hide_order_status}{$url_shop_order_status}&report_name=coupon_page";
						$admin_url 		= admin_url("admin.php?page=".$this->constants['plugin_key']."_details_page")."&end_date=".$end_date."&start_date=".$start_date."&detail_view=no{$url_hide_order_status}{$url_shop_order_status}&";
						?>
                     
                        <table style="width:100%" class="widefat">
                            <thead>
                                <tr class="first">
                                    <th><?php _e( 'Coupon Code', 'icwoocommerce_textdomains'); ?></th>
                                    
                                    <th class="item_count" style="width:130px"><?php _e( 'Coupon Used Count', 'icwoocommerce_textdomains'); ?></th>  
                                    <th class="item_amount amount"><?php _e( 'Amount', 'icwoocommerce_textdomains'); ?></th>                           
                                </tr>
                            </thead>
                            <tbody>
                                <?php					
                                foreach ( $order_items as $key => $order_item ) {
                                if($key%2 == 1){$alternate = "alternate ";}else{$alternate = "";};
                                ?>
                                    <tr class="<?php echo $alternate."row_".$key;?>">                                        
                                        <td><a href="<?php echo $admin_url."coupon_code={$order_item->order_item_name}"?>" target="<?php echo $order_item->order_item_name?>_blank"><?php echo $order_item->order_item_name?></a><?php //echo $order_item->order_item_name?></td>
                                        
                                        <td class="item_count" style="width:130px"><?php echo $order_item->Count?></td>
                                        <td class="item_amount amount"><?php echo $this->price($order_item->Total);?></td>
                                     <?php } ?>		
                                    </tr>
                            <tbody>           
                        </table>
                        <span class="ViewAll"><a href="<?php echo $all_admin_url;?>"><?php _e("View All",'icwoocommerce_textdomains');?></a></span>
				<?php 
					else:
						echo '<p>'.__("No Coupons found.", 'icwoocommerce_textdomains').'</p>';
					endif;
			}
			
			
			//New Change ID 20150206
			function get_category_list($shop_order_status,$hide_order_status,$start_date,$end_date){
					global $wpdb,$options;

					$optionsid	= "top_category_per_page";
					$per_page 	= $this->get_number_only($optionsid,$this->per_page_default);
					
					$sql ="";
					$sql .= " SELECT ";
					$sql .= " SUM(woocommerce_order_itemmeta_product_qty.meta_value) AS quantity";
					$sql .= " ,SUM(woocommerce_order_itemmeta_product_line_total.meta_value) AS total_amount";
					$sql .= " ,terms_product_id.term_id AS category_id";
					$sql .= " ,terms_product_id.name AS category_name";
					$sql .= " ,term_taxonomy_product_id.parent AS parent_category_id";
					$sql .= " ,terms_parent_product_id.name AS parent_category_name";
					
					$sql .= " FROM {$wpdb->prefix}woocommerce_order_items as woocommerce_order_items";
					
					$sql .= " LEFT JOIN  {$wpdb->prefix}woocommerce_order_itemmeta as woocommerce_order_itemmeta_product_id ON woocommerce_order_itemmeta_product_id.order_item_id=woocommerce_order_items.order_item_id";
					$sql .= " LEFT JOIN  {$wpdb->prefix}woocommerce_order_itemmeta as woocommerce_order_itemmeta_product_qty ON woocommerce_order_itemmeta_product_qty.order_item_id=woocommerce_order_items.order_item_id";
					$sql .= " LEFT JOIN  {$wpdb->prefix}woocommerce_order_itemmeta as woocommerce_order_itemmeta_product_line_total ON woocommerce_order_itemmeta_product_line_total.order_item_id=woocommerce_order_items.order_item_id";
					
					
					$sql .= " 	LEFT JOIN  {$wpdb->prefix}term_relationships 	as term_relationships_product_id 	ON term_relationships_product_id.object_id		=	woocommerce_order_itemmeta_product_id.meta_value 
								LEFT JOIN  {$wpdb->prefix}term_taxonomy 		as term_taxonomy_product_id 		ON term_taxonomy_product_id.term_taxonomy_id	=	term_relationships_product_id.term_taxonomy_id
								LEFT JOIN  {$wpdb->prefix}terms 				as terms_product_id 				ON terms_product_id.term_id						=	term_taxonomy_product_id.term_id";
					
					$sql .= " 	LEFT JOIN  {$wpdb->prefix}terms 				as terms_parent_product_id 				ON terms_parent_product_id.term_id						=	term_taxonomy_product_id.parent";
					
					$sql .= " LEFT JOIN  {$wpdb->prefix}posts as posts ON posts.id=woocommerce_order_items.order_id";
					
					if($this->constants['post_order_status_found'] == 0 ){
						if(count($shop_order_status)>0){
							$sql .= " 
							LEFT JOIN  {$wpdb->prefix}term_relationships 	as term_relationships 	ON term_relationships.object_id		=	posts.ID
							LEFT JOIN  {$wpdb->prefix}term_taxonomy 		as term_taxonomy 		ON term_taxonomy.term_taxonomy_id	=	term_relationships.term_taxonomy_id";
						}
					}
						
					$sql .= " WHERE 1*1 ";
					$sql .= " AND woocommerce_order_items.order_item_type 					= 'line_item'";
					$sql .= " AND woocommerce_order_itemmeta_product_id.meta_key 			= '_product_id'";
					$sql .= " AND woocommerce_order_itemmeta_product_qty.meta_key 			= '_qty'";
					$sql .= " AND woocommerce_order_itemmeta_product_line_total.meta_key 	= '_line_total'";
					$sql .= " AND term_taxonomy_product_id.taxonomy 						= 'product_cat'";
					$sql .= " AND posts.post_type 											= 'shop_order'";				
								
					$url_shop_order_status	= "";
					if($this->constants['post_order_status_found'] == 0 ){
						if(count($shop_order_status)>0){
							$in_shop_order_status = implode(",",$shop_order_status);
							$sql .= " AND  term_taxonomy.term_id IN ({$in_shop_order_status})";
							
							$url_shop_order_status	= "&order_status_id=".$in_shop_order_status;
						}
					}else{
						if(count($shop_order_status)>0){
							$in_shop_order_status		= implode("', '",$shop_order_status);
							$sql .= " AND  posts.post_status IN ('{$in_shop_order_status}')";
							//$this->print_array($shop_order_status);
							
							$url_shop_order_status	= implode(",",$shop_order_status);
							$url_shop_order_status	= "&order_status=".$url_shop_order_status;
						}
					}
					
					if ($start_date != NULL &&  $end_date !=NULL){
						$sql .= " AND DATE(posts.post_date) BETWEEN '{$start_date}' AND '{$end_date}'";
					}
					
					
					$url_hide_order_status = "";
					if(count($hide_order_status)>0){
						$in_hide_order_status		= implode("', '",$hide_order_status);
						$sql .= " AND  posts.post_status NOT IN ('{$in_hide_order_status}')";
						
						$url_hide_order_status	= implode(",",$hide_order_status);
						$url_hide_order_status = "&hide_order_status=".$url_hide_order_status;
					}
					
					$sql .= " GROUP BY category_id";
					$sql .= " Order By total_amount DESC";
					$sql .= " LIMIT {$per_page}";
					 
					$order_items = $wpdb->get_results($sql); 
					if(count($order_items)>0):
                    	$all_admin_url = admin_url("admin.php?page=".$this->constants['plugin_key']."_report_page")."&end_date=".$end_date."&start_date=".$start_date."{$url_hide_order_status}{$url_shop_order_status}&report_name=category_page";
						?>
                     
                        <table style="width:100%" class="widefat">
                            <thead>
                                <tr class="first">
                                    <th><?php _e( 'Category Name', 'icwoocommerce_textdomains'); ?></th>
                                    <th class="item_count"><?php _e( 'Qty', 'icwoocommerce_textdomains'); ?></th>
                                    <th class="item_amount"><?php _e( 'Amount', 'icwoocommerce_textdomains'); ?></th>                           
                                </tr>
                            </thead>
                            <tbody>
                                <?php					
                                foreach ( $order_items as $key => $order_item ) {
                                if($key%2 == 1){$alternate = "alternate ";}else{$alternate = "";};
                                ?>
                                    <tr class="<?php echo $alternate."row_".$key;?>">                                        
                                        <td><?php echo $order_item->category_name?></td>
                                        <td class="item_count"><?php echo $order_item->quantity?></td>
                                        <td class="item_amount amount"><?php echo $this->price($order_item->total_amount);?></td>
                                     <?php } ?>		
                                    </tr>
                            <tbody>           
                        </table>
                        <span class="ViewAll"><a href="<?php echo $all_admin_url;?>"><?php _e("View All",'icwoocommerce_textdomains');?></a></span>
				<?php  
					else:
						echo '<p>'.__("No Coupons found.", 'icwoocommerce_textdomains').'</p>';
					endif;
			}
			
			function get_monthly_summary($shop_order_status,$hide_order_status,$start_date,$end_date){
				include_once("ic_commerce_premium_golden_monthly_summary.php");
				
				$parameters = array('shop_order_status'=>$shop_order_status,'hide_order_status'=>$hide_order_status,'start_date'=>$start_date,'end_date'=>$end_date);
				
				$monthly_summary = new IC_Commerce_Premium_Golden_Monthly_Summary($this->constants , $parameters);
				
				echo $monthly_summary->dashboard();
				
			}
						
			public $is_product_active = NULL;
			public function is_active(){
				$r = false;
				if($this->is_product_active == NULL){					
					$actived_product = get_option($this->constants['plugin_key'] . '_activated');
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
			
			function get_coumns($report_name = 'recent_order'){
				$grid_column 	= $this->get_grid_columns();				
				return $grid_column->get_dasbboard_coumns($report_name);
			}
			
			function get_progress_details($total_today_sales = 0,$total_yesterday_sales = 0,$label = 'yesterday'){
				$progress_width 	= 0;
				$progress_label 	= '';
				$today_total_sales 	= "";
				$highlow			= "";
				
				if($total_today_sales > 0 and $total_yesterday_sales > 0){
				
					if($total_today_sales == $total_yesterday_sales){
						$progress_width 	= 100;
						$progress_label 	= sprintf("%.0f%%", $progress_width);;
						$today_total_sales 	= "<span class=\"ic_icon-custom-equal\"></span><span>{$progress_label} match <span class=\"ic_blend\">with {$label}</span></span>";
						$highlow			= "equal";
					}else if($total_today_sales > $total_yesterday_sales){
						if($total_yesterday_sales == 0){
						
						}else{
							//$progress_width 	= ($total_yesterday_sales/$total_today_sales)*100;
							$progress_width		= $this->get_percentage($total_yesterday_sales,$total_today_sales);//Added 20150206
							$progress_label 	= sprintf("%.2f%%", $progress_width);;
							$today_total_sales 	= "<span class=\"ic_icon-custom-up\"></span><span>{$progress_label} higher <span class=\"ic_blend\">than {$label}</span></span>";
							$highlow			= "up";
						}
						
					}else if($total_today_sales < $total_yesterday_sales){
						if($total_today_sales == 0){
						
						}else{
							//$progress_width 	= ($total_today_sales/$total_yesterday_sales)*100;
							$progress_width		= $this->get_percentage($total_today_sales,$total_yesterday_sales);//Added 20150206
							$progress_label 	= sprintf("%.2f%%", $progress_width);;
							$today_total_sales 	= "<span class=\"ic_icon-custom-down\"></span><span>{$progress_label} less <span class=\"ic_blend\">than {$label}</span></span>";
							$highlow			= "down";
						}
						
					}
				}else{
					
					if($total_today_sales == 0 and $total_yesterday_sales == 0){
						$progress_width 	= 0;
						$progress_label 	= sprintf("%.2f%%", $progress_width);;
						//$today_total_sales 	= "<span class=\"ic_icon-custom-equal\"></span><span>{$progress_label} less <span class=\"ic_blend\">than {$label}</span></span>";
						$highlow			= "equal";
					}else if($total_today_sales <= 0){
						$progress_width 	= 100;
						$progress_label 	= sprintf("%.2f%%", $progress_width);;
						$today_total_sales 	= "<span class=\"ic_icon-custom-down\"></span><span>{$progress_label} less <span class=\"ic_blend\">than {$label}</span></span>";
						$highlow			= "down";
					}else if($total_yesterday_sales <= 0){
						$progress_width 	= 100;
						$progress_label 	= sprintf("%.2f%%", $progress_width);;
						$today_total_sales 	= "<span class=\"ic_icon-custom-up\"></span><span>{$progress_label} less <span class=\"ic_blend\">than {$label}</span></span>";
						$highlow			= "down";
					}
					
				}
				return array('progress_width'=>$progress_width,'progress_label' => $today_total_sales,'progress_highlow'=>$highlow);
			}
			
			function get_progres_content($today = 0,$yesterday = 0, $label = 'yesterday'){
				
				$values 	= $this->get_progress_details($today, $yesterday, $label = 'yesterday');
				$progress_width 	= $values['progress_width'];
				$progress_label 	= $values['progress_label'];
				$progress_highlow 	= $values['progress_highlow'];
				
				$output = "";				
				$output .= " <div class=\"ic_progress ic_progress_{$progress_highlow}\">";
				$output .= " <div style=\"width:{$progress_width}%;\" class=\"ic_progress-bar-white\"></div>";
				$output .= " </div>";
				$output .= " <div class=\"ic_description\">{$progress_label}</div>";
				
				return $output;
			}
			
			function get_part_order_refund_amount($type = "today",$shop_order_status,$hide_order_status,$start_date,$end_date){
				global $wpdb;
				
				$today_date 			= $this->today;
				$yesterday_date 		= $this->yesterday;
				
				$sql = " SELECT SUM(postmeta.meta_value) 		as total_amount
				
				,posts.ID AS 'order_id'	
						
				FROM {$wpdb->prefix}posts as posts
								
				LEFT JOIN  {$wpdb->prefix}postmeta as postmeta ON postmeta.post_id	=	posts.ID";
				
				$sql .= " LEFT JOIN  {$wpdb->prefix}posts as shop_order ON shop_order.ID	=	posts.post_parent";
				
				if($this->constants['post_order_status_found'] == 0 ){
					if(count($shop_order_status)>0){
						$sql .= " 
						LEFT JOIN  {$wpdb->prefix}term_relationships 	as term_relationships 	ON term_relationships.object_id		=	posts.ID
						LEFT JOIN  {$wpdb->prefix}term_taxonomy 		as term_taxonomy 		ON term_taxonomy.term_taxonomy_id	=	term_relationships.term_taxonomy_id";
					}
				}
				
				$sql .= " WHERE posts.post_type = 'shop_order_refund' AND  postmeta.meta_key='_refund_amount'";
				
				$sql .= " AND shop_order.post_type = 'shop_order'";
						
				if($this->constants['post_order_status_found'] == 0 ){
					$refunded_id 	= $this->get_old_order_status(array('refunded'), array('wc-refunded'));
					$refunded_id    = implode(",",$refunded_id);
					$sql .= " AND terms2.term_id NOT IN (".$refunded_id .")";
					
					if(count($shop_order_status)>0){
						$in_shop_order_status = implode(",",$shop_order_status);
						$sql .= " AND  term_taxonomy.term_id IN ({$in_shop_order_status})";
					}
				}else{
					$sql .= " AND shop_order.post_status NOT IN ('wc-refunded')";
					
					if(count($shop_order_status)>0){
						$in_shop_order_status		= implode("', '",$shop_order_status);
						$sql .= " AND  shop_order.post_status IN ('{$in_shop_order_status}')";
					}
				}
				
				if ($start_date != NULL &&  $end_date != NULL && $type == "total"){
					$sql .= " AND DATE(shop_order.post_date) BETWEEN '{$start_date}' AND '{$end_date}'";
				}
				
				if($type == "today") $sql .= " AND DATE(shop_order.post_date) = '{$today_date}'";
				
				if($type == "yesterday") 	$sql .= " AND DATE(shop_order.post_date) = '{$yesterday_date}'";
				
				if(count($hide_order_status)>0){
					$in_hide_order_status		= implode("', '",$hide_order_status);
					$sql .= " AND  shop_order.post_status NOT IN ('{$in_hide_order_status}')";
				}
				
				$sql .= " LIMIT 1";
				
				//$this->print_sql($sql);
			
				$wpdb->query("SET SQL_BIG_SELECTS=1");
				
				$order_items = $wpdb->get_var($sql);
				
				return $order_items;
				
			}
			
			function get_country_list(){
				
				//$this->print_array($_REQUEST);
							$c				= $this->constants;
							$shop_order_status		= $this->get_set_status_ids();	
							$hide_order_status 		= $this->constants['hide_order_status'];
							$start_date 			= $this->constants['start_date'];
							$end_date 				= $this->constants['end_date'];
							include_once( 'ic_commerce_premium_golden_map.php');
							$class_object = new IC_Commerce_Premium_Golden_Map($c);
							$class_object->get_country_list($shop_order_status,$hide_order_status,$start_date,$end_date);
							
							return;
				
			}
			
			function get_cost_of_goods_items($type = "total", $shop_order_status,$hide_order_status,$start_date,$end_date){
				global $wpdb;
				$today_date 			= $this->today;
				$yesterday_date 		= $this->yesterday;
				
				$cogs_metakey_item_total		= $this->get_setting('cogs_metakey_item_total',	$this->constants['plugin_options'],'');
				
				$sql = " SELECT ";
				$sql .= "				
					SUM(woocommerce_order_itemmeta_qty.meta_value) 																					AS quantity							
					,SUM(woocommerce_order_itemmeta_total_cost_of_item.meta_value) 																	AS total_cost_good_amount
					,SUM(woocommerce_order_itemmeta_line_total.meta_value) - SUM(woocommerce_order_itemmeta_total_cost_of_item.meta_value)			AS margin_profit_amount							
					,SUM(woocommerce_order_itemmeta_line_total.meta_value) 																			AS total_amount				
				";	
				
				if($this->constants['post_order_status_found'] == 0 ){
					if(count($shop_order_status)>0){
						$sql .= " 
						LEFT JOIN  {$wpdb->prefix}term_relationships 	as term_relationships 	ON term_relationships.object_id		=	posts.ID
						LEFT JOIN  {$wpdb->prefix}term_taxonomy 		as term_taxonomy 		ON term_taxonomy.term_taxonomy_id	=	term_relationships.term_taxonomy_id";
					}
				}	
				
				$sql .= " FROM {$wpdb->prefix}woocommerce_order_items as woocommerce_order_items";
				$sql .= " LEFT JOIN  {$wpdb->prefix}woocommerce_order_itemmeta as woocommerce_order_itemmeta_qty					ON woocommerce_order_itemmeta_qty.order_item_id					=	woocommerce_order_items.order_item_id	AND woocommerce_order_itemmeta_qty.meta_key					= '_qty'";
				$sql .= " LEFT JOIN  {$wpdb->prefix}woocommerce_order_itemmeta as woocommerce_order_itemmeta_line_total 			ON woocommerce_order_itemmeta_line_total.order_item_id			=	woocommerce_order_items.order_item_id	AND woocommerce_order_itemmeta_line_total.meta_key			= '_line_total'";
				$sql .= " LEFT JOIN  {$wpdb->prefix}woocommerce_order_itemmeta as woocommerce_order_itemmeta_line_subtotal			ON woocommerce_order_itemmeta_line_subtotal.order_item_id		=	woocommerce_order_items.order_item_id	AND woocommerce_order_itemmeta_line_subtotal.meta_key		= '_line_subtotal'";
				$sql .= " LEFT JOIN  {$wpdb->prefix}woocommerce_order_itemmeta as woocommerce_order_itemmeta_total_cost_of_item 	ON woocommerce_order_itemmeta_total_cost_of_item.order_item_id	=	woocommerce_order_items.order_item_id	AND woocommerce_order_itemmeta_total_cost_of_item.meta_key 	= '{$cogs_metakey_item_total}'";
				$sql .= " LEFT JOIN  {$wpdb->prefix}posts as shop_order 															ON shop_order.id												=	woocommerce_order_items.order_id		AND shop_order.post_type									= 'shop_order'";
				
						
				$sql .= " WHERE 1*1 ";
				
				if($this->constants['post_order_status_found'] == 0 ){
					if(count($shop_order_status)>0){
						$in_shop_order_status = implode(",",$shop_order_status);
						$sql .= " AND  term_taxonomy.term_id IN ({$in_shop_order_status})";
					}
				}else{
					if(count($shop_order_status)>0){
						$in_shop_order_status		= implode("', '",$shop_order_status);
						$sql .= " AND  shop_order.post_status IN ('{$in_shop_order_status}')";
					}
				}
				
				if($type == "today") 		$sql .= " AND DATE(shop_order.post_date) = '{$today_date}'";
				if($type == "yesterday") 	$sql .= " AND DATE(shop_order.post_date) = '{$yesterday_date}'";
			
				if($type == "today_yesterday"){
					$sql .= " AND (DATE(shop_order.post_date) = '{$today_date}'";
					$sql .= " OR DATE(shop_order.post_date) = '{$yesterday_date}')";
				}
				
				if ($start_date != NULL &&  $end_date != NULL && $type != "today"){
					$sql .= " AND DATE(shop_order.post_date) BETWEEN '{$start_date}' AND '{$end_date}'";
				}
								
				if(count($hide_order_status)>0){
					$in_hide_order_status		= implode("', '",$hide_order_status);
					$sql .= " AND  shop_order.post_status NOT IN ('{$in_hide_order_status}')";
				}
				
				$cogs_enable_set_item = $this->get_setting('cogs_enable_set_item',$this->constants['plugin_options'],0);
				
				if($cogs_enable_set_item == 1){				
					$sql .= " AND woocommerce_order_itemmeta_total_cost_of_item.meta_value > 0";
				}
											
				$order_items = $wpdb->get_row($sql);
				
				//echo "<div class=\"clearfix\"></div>";
				//$this->print_sql($sql);
				//$this->print_array($order_items);				
				return $order_items;
		}
	}
}

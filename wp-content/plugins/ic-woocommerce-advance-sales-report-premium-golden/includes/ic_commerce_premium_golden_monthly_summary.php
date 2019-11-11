<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
require_once('ic_commerce_premium_golden_fuctions.php');


if ( ! class_exists( 'IC_Commerce_Premium_Golden_Monthly_Summary' ) ) {
	class IC_Commerce_Premium_Golden_Monthly_Summary extends IC_Commerce_Premium_Golden_Fuctions{
		
		public $constants 	=	array();
		
		public $parameters 	=	array();
		
		public function __construct($constants = array(), $parameters = array('shop_order_status'=>array(),'hide_order_status'=>array(),'start_date'=>NULL,'end_date'=>NULL)) {
			global $plugin_options;
			
			$this->constants	= $constants;
			$this->parameters	= $parameters;
			$plugin_options 	= $this->constants['plugin_options'];
		}
		
		function init(){
			return $this->get_grid();
			return $this->get_detail_grid();
		}
		
		function dashboard(){
			return $this->get_grid();			
		}
		
		function detail_page(){
			return $this->get_detail_grid();			
		}
		
		function get_grid(){
			global $plugin_options;
									
			$shop_order_status	= isset($this->parameters['shop_order_status']) ? $this->parameters['shop_order_status']	: array();
			$hide_order_status	= isset($this->parameters['hide_order_status']) ? $this->parameters['hide_order_status']	: array();
			$start_date			= isset($this->parameters['start_date'])		? $this->parameters['start_date']			: NULL;
			$end_date			= isset($this->parameters['end_date'])			? $this->parameters['end_date']				: NULL;			
			$plugin_options 	= $this->constants['plugin_options'];
			
			$cur_projected_sales_year=isset($plugin_options['cur_projected_sales_year']) ? trim($plugin_options['cur_projected_sales_year']) : date('Y',strtotime($this->constants['today_date']));
			
			$start_date			= $cur_projected_sales_year."-01-01";
			$start_month_time	= strtotime($start_date);
			$month_count		= 12;
			$end_month_time		= strtotime("+{$month_count} month", ($start_month_time - (24*60*60)));
			$end_date			= date("Y-m-d",$end_month_time);
			$zero				= $this->price(0);
			
			$items 				= $this->get_items($shop_order_status, $hide_order_status, $start_date, $end_date, $month_count,$cur_projected_sales_year);
			
			
			
			$columns			= $this->get_columns();
			$output = "";
					
			$output .= '<table style="width:100%" class="widefat table_sales_by_month">';
			$output .= "<thead>";
				$output .= "<tr class=\"first\">";
				foreach($columns as $key => $value){
					$td_class = $key;
					switch($key){
						case "order_total":
						case "totalsalse":
						case "refund_order_total":
						case "order_discount":
						case "cart_discount":
						case "total_discount":
						case "order_total":
						case "projected":
						case "fore_cast":
						case "couppevment":
						case "order_shipping_tax":
						case "order_tax":
						case "total_shipping_tax":
						case "actual_min_porjected":
						case "actual_porjected_per":
						case "part_order_refund_amount":	
							$td_value = $value;
							$td_class .= " amount";
							break;
						default:
							$td_value = $value;
							break;
					}//End Column Switth
					$output .= "<th class=\"{$td_class}\">{$td_value}</th>";
				}//End Column Foreach;
				$output .= "</tr>";
			$output .= "</thead>";
			
			$output .= "<tbody>";
				foreach($items as $rkey => $rvalue){
					$output .= "<tr>";
					foreach($columns as $key => $value){
						$td_class = $key;
						$td_value = '';
						switch($key){
							case "month_name":
								$td_value = isset($rvalue[$key]) ? $rvalue[$key] : $key;
								break;
							case "order_total":
							case "refund_order_total":
							case "order_discount":
							case "cart_discount":
							case "total_discount":
							case "order_shipping_tax":
							case "order_tax":
							case "projected":
							case "total_shipping_tax":
							case "part_order_refund_amount":	
								$td_value = isset($rvalue[$key]) ? $rvalue[$key] : 0;
								$td_value = $td_value > 0 ? $this->price($td_value) : $zero;
								$td_class .= " amount";
								break;
							case "actual_min_porjected":
								$td_value = isset($rvalue[$key]) ? $rvalue[$key] : 0;
								if($td_value >= 0){
									$td_value = $td_value > 0 ? $this->price($td_value) : $zero;
								}else{
									$td_value = -($td_value);
									$td_value = "-".strip_tags($this->price($td_value));
								}								
								$td_class .= " amount";
								break;
							
							case "fore_cast":
								$td_value = isset($rvalue[$key]) ? $rvalue[$key] : 0;
								$td_class .= " amount";
								break;
							case "actual_porjected_per":	
							case "couppevment":
								$td_value = isset($rvalue[$key]) ? $rvalue[$key] : 0;								
								$td_class .= ($td_value >= 100) ? " up_arrow" : (($td_value < 100 and $td_value > 0) ? " down_arrow" : " no_arrow");
								//$td_class .= ($td_value == 0) ? " no_arrow" : $td_class2;
								//$label 		= ($td_value >= 100) ? " U " : (($td_value == 0) ? '' : " D ");
								$label 		= '';
								$td_value = $label.sprintf("%.2f%%", $td_value);
								$td_class .= " amount";
								break;							
							case "totalsalse":
								$td_value = isset($rvalue[$key]) ? $rvalue[$key] : 0;
								$td_value = sprintf("%.2f%%", $td_value);
								$td_class .= " amount";
								break;
							default:
								$td_value = isset($rvalue[$key]) ? $rvalue[$key] : $key;
								break;
						}//End Column Switth
						$output .= "<td class=\"{$td_class}\">{$td_value}</td>";
					}//End Column Foreach;
					$output .= "</tr>";
				}//End Item Foreahc
			$output .= "</tbody>";
			$output .= "</table>";
			
			return $output;
		}
		
		function get_detail_grid(){
			global $plugin_options;
									
			$shop_order_status	= isset($this->parameters['shop_order_status']) ? $this->parameters['shop_order_status']	: array();
			$hide_order_status	= isset($this->parameters['hide_order_status']) ? $this->parameters['hide_order_status']	: array();
			$start_date			= isset($this->parameters['start_date'])		? $this->parameters['start_date']			: NULL;
			$end_date			= isset($this->parameters['end_date'])			? $this->parameters['end_date']				: NULL;
			
			
			
			$plugin_options 	= $this->constants['plugin_options'];			
			$cur_projected_sales_year=isset($plugin_options['cur_projected_sales_year']) ? trim($plugin_options['cur_projected_sales_year']) : date('Y',strtotime($this->constants['today_date']));
			$cur_projected_sales_year= $this->get_request('projected_sales_year',$cur_projected_sales_year,true);
						
			$start_date			= $cur_projected_sales_year."-01-01";
			$start_month_time	= strtotime($start_date);
			$month_count		= 12;
			$end_month_time		= strtotime("+{$month_count} month", ($start_month_time - (24*60*60)));
			$end_date			= date("Y-m-d",$end_month_time);
			$zero				= $this->price(0);
			
			$items 				= $this->get_detail_items($shop_order_status, $hide_order_status, $start_date, $end_date, $month_count,$cur_projected_sales_year);
			$columns			= $this->get_detail_columns();
			$output = "";
					
			$output .= '<table style="width:100%" class="widefat table_sales_by_month">';
			$output .= "<thead>";
				$output .= "<tr class=\"first\">";
				foreach($columns as $key => $value){
					$td_class = $key;
					switch($key){
						case "order_total":
						case "totalsalse":
						case "refund_order_total":
						case "order_discount":
						case "cart_discount":
						case "total_discount":
						case "order_total":
						case "projected":
						case "fore_cast":
						case "couppevment":
						case "order_shipping_tax":
						case "order_tax":
						case "total_shipping_tax":
						case "actual_min_porjected":
						case "actual_porjected_per":
						case "part_order_refund_amount":	
							$td_value = $value;
							$td_class .= " amount";
							break;
						default:
							$td_value = $value;
							break;
					}//End Column Switth
					$output .= "<th class=\"{$td_class}\">{$td_value}</th>";
				}//End Column Foreach;
				$output .= "</tr>";
			$output .= "</thead>";
			
			$output .= "<tbody>";
				foreach($items as $rkey => $rvalue){
					$output .= "<tr>";
					foreach($columns as $key => $value){
						$td_class = $key;
						$td_value = '';
						switch($key){
							case "month_name":
								$td_value = isset($rvalue[$key]) ? $rvalue[$key] : $key;
								break;
							case "order_total":
							case "refund_order_total":
							case "order_discount":
							case "cart_discount":
							case "total_discount":
							case "order_shipping_tax":
							case "order_tax":
							case "projected":
							case "total_shipping_tax":
							case "part_order_refund_amount":
							
							
								$td_value = isset($rvalue[$key]) ? $rvalue[$key] : 0;
								$td_value = $td_value > 0 ? $this->price($td_value) : $zero;
								$td_class .= " amount";
								break;
							
							case "actual_min_porjected":
								$td_value = isset($rvalue[$key]) ? $rvalue[$key] : 0;
								if($td_value >= 0){
									$td_value = $td_value > 0 ? $this->price($td_value) : $zero;
								}else{
									$td_value = -($td_value);
									$td_value = "-".strip_tags($this->price($td_value));
								}								
								$td_class .= " amount";
								break;
							
							case "fore_cast":
								$td_value = isset($rvalue[$key]) ? $rvalue[$key] : 0;
								$td_class .= " amount";
								break;
							case "actual_porjected_per":	
							case "couppevment":
								$td_value = isset($rvalue[$key]) ? $rvalue[$key] : 0;								
								$td_class .= ($td_value >= 100) ? " up_arrow" : (($td_value < 100 and $td_value > 0) ? " down_arrow" : " no_arrow");
								//$td_class .= ($td_value == 0) ? " no_arrow" : $td_class2;
								//$label 		= ($td_value >= 100) ? " U " : (($td_value == 0) ? '' : " D ");
								$label 		= '';
								$td_value = $label.sprintf("%.2f%%", $td_value);
								$td_class .= " amount";
								break;							
							case "totalsalse":
								$td_value = isset($rvalue[$key]) ? $rvalue[$key] : 0;
								$td_value = sprintf("%.2f%%", $td_value);
								$td_class .= " amount";
								break;
							default:
								$td_value = isset($rvalue[$key]) ? $rvalue[$key] : $key;
								break;
						}//End Column Switth
						$output .= "<td class=\"{$td_class}\">{$td_value}</td>";
					}//End Column Foreach;
					$output .= "</tr>";
				}//End Item Foreahc
			$output .= "</tbody>";
			$output .= "</table>";
			
			return $output;
		}
		
		function get_columns(){
			$columns = array(
					"month_name"					=>	__("Month", 				'icwoocommerce_textdomains')
					,"projected"					=>	__("Projected Sales", 		'icwoocommerce_textdomains')
					,"order_total"					=>	__("Actual Sales", 			'icwoocommerce_textdomains')
					,"actual_min_porjected"			=>	__("Difference", 			'icwoocommerce_textdomains')
					,"actual_porjected_per"			=>	__("%", 					'icwoocommerce_textdomains')
					//,"couppevment"				=>	__("Revenue", 				'icwoocommerce_textdomains')
					,"totalsalse"					=>	__("Total % to Sales", 		'icwoocommerce_textdomains')
					,"refund_order_total"			=>	__("Refund Amt.", 			'icwoocommerce_textdomains')
					,"part_order_refund_amount"		=>  __("Part Refund Amount", 	'icwoocommerce_textdomains')
					,"total_discount"				=>	__("Total Discount Amt.", 	'icwoocommerce_textdomains')
					,"order_tax"					=>	__("Tax Amt.", 				'icwoocommerce_textdomains')
					,"order_shipping_tax"			=>	__("Shipping Order Tax", 	'icwoocommerce_textdomains')
					,"total_shipping_tax"			=>	__("Total Shipping Tax", 	'icwoocommerce_textdomains')
					);
			return $columns;
		}
		
		function get_detail_columns(){
			$columns = array(
					"month_name"					=>	__("Month", 				'icwoocommerce_textdomains')
					,"projected"					=>	__("Projected Sales",		'icwoocommerce_textdomains')
					,"order_total"					=>	__("Actual Sales",		 	'icwoocommerce_textdomains')
					,"actual_min_porjected"			=>	__("Difference", 			'icwoocommerce_textdomains')
					,"actual_porjected_per"			=>	__("%", 					'icwoocommerce_textdomains')
					,"refund_order_total"			=>	__("Refund Amt.", 			'icwoocommerce_textdomains')
					,"part_order_refund_amount"		=>  __("Part Refund Amount", 	'icwoocommerce_textdomains')
					,"total_discount"				=>	__("Total Discount Amt.", 	'icwoocommerce_textdomains')
					);
			return $columns;
		}
		
		function get_items($shop_order_status, $hide_order_status, $start_date, $end_date, $month_count = 12, $cur_projected_sales_year = 2010){
			global $plugin_options;
			
			$refunded_id 		= $this->get_old_order_status(array('refunded'), array('wc-refunded'));			
            $order_total		= $this->get_total_sales_by_month($shop_order_status, $hide_order_status, $start_date, $end_date,"_order_total");
			$order_discount		= $this->get_total_sales_by_month($shop_order_status, $hide_order_status, $start_date, $end_date,"_order_discount");
			$cart_discount		= $this->get_total_sales_by_month($shop_order_status, $hide_order_status, $start_date, $end_date,"_cart_discount");
			$order_shipping_tax	= $this->get_total_sales_by_month($shop_order_status, $hide_order_status, $start_date, $end_date,"_order_shipping_tax");
			$order_tax			= $this->get_total_sales_by_month($shop_order_status, $hide_order_status, $start_date, $end_date,"_order_tax");			
			//$refund_order_total = $this->get_total_status_sales_by_month($refunded_id, $hide_order_status, $start_date, $end_date, $month_count,"_order_total");
			
			$part_refund		= $this->get_part_order_refunded_amount_by_month($shop_order_status, $hide_order_status, $start_date, $end_date, $month_count);
			$full_refund		= $this->get_full_order_refunded_amount_by_month($shop_order_status, $hide_order_status, $start_date, $end_date, $month_count);
			$refund_order_total = $full_refund;
			
			
			//$orderdiscount		= $this->get_total_sales_by_month($shop_order_status, $hide_order_status, $start_date, $end_date,"_order_discount","_cart_discount");
			
			
			
            $start_month		= $start_date;
			$start_month_time	= strtotime($start_month);			
            $month_list			= array();
            $new_data2			= array();
            $total_projected	= 0;
			$i         			= 0;
			$m         			= 0;			
			
            for ($m=0; $m < ($month_count); $m++){
				$c					= 	strtotime("+$m month", $start_month_time);
				$key				= 	date('F-Y', $c);
				$value				= 	date('F', 	$c);
				$month_list[$key]	=	$value;
			}
			
			$projected_sales_year_option= $this->constants['plugin_key'].'_projected_amount_'.$cur_projected_sales_year;
			$projected_amounts 			= get_option($projected_sales_year_option,array());
						
			foreach ($month_list as $key => $value) {
				
				$projected_sales_month					=	$value;
				$projected_sales_month_amount			=	isset($projected_amounts[$projected_sales_month]) ? $projected_amounts[$projected_sales_month] : 100;
				$new_data2[$i]["projected"] 			=	strlen($projected_sales_month_amount) > 0 ? $projected_sales_month_amount : 0;
				$new_data2[$i]["month_name"] 			=	$key;
				$new_data2[$i]["monthname"] 			=	__("{$value}",'icwoocommerce_textdomains');				
                $new_data2[$i]["part_order_refund_amount"] 	=	isset($part_refund[$key]) 		? $part_refund[$key] 			: 0;
				
				$this_order_total 						=	isset($order_total[$key]) 			? $order_total[$key] 			: 0;
				$this_order_total						=	strlen($this_order_total)>0			? $this_order_total 			: 0;
				$this_order_total						=	$this_order_total - $new_data2[$i]["part_order_refund_amount"];
				$new_data2[$i]["order_total"] 			=	$this_order_total;
				
				//$new_data2[$i]["order_total"] 			=	isset($order_total[$key]) 			? $order_total[$key] 			: 0;
				$new_data2[$i]["order_discount"] 		=	isset($order_discount[$key]) 		? $order_discount[$key] 		: 0;
				$new_data2[$i]["cart_discount"] 		=	isset($cart_discount[$key]) 		? $cart_discount[$key] 		: 0;
				$new_data2[$i]["total_discount"] 		=	$new_data2[$i]["order_discount"] + $new_data2[$i]["cart_discount"];
				
				$new_data2[$i]["order_shipping_tax"] 	=	isset($order_shipping_tax[$key]) 	? $order_shipping_tax[$key] 	: 0;
				$new_data2[$i]["order_tax"] 			=	isset($order_tax[$key]) 			? $order_tax[$key] 				: 0;
				$new_data2[$i]["refund_order_total"]	=	isset($refund_order_total[$key]) 	? $refund_order_total[$key]		: 0;
				
				$new_data2[$i]["total_shipping_tax"] 	=	$new_data2[$i]["order_tax"] + $new_data2[$i]["order_shipping_tax"];
				$new_data2[$i]["actual_min_porjected"]	=	$new_data2[$i]["order_total"] - $projected_sales_month_amount;
				$total_projected						=	$total_projected + $new_data2[$i]["projected"];
                $i++;
            }
			
			foreach ($new_data2 as $key => $value) {
				$order_total							=	isset($value["order_total"]) 	? trim($value["order_total"])	: 0;
				
				$new_data2[$key]["couppevment"]			=	$this->get_percentage($order_total,$value["projected"]);
				$new_data2[$key]["totalsalse"]			=	$this->get_percentage($order_total,$total_projected);
				$new_data2[$key]["actual_porjected_per"]=	$this->get_percentage($order_total,$value["projected"]);
            }
			
			$order_total 				=	$this->get_total($new_data2,'order_total');
			$order_discount 			=	$this->get_total($new_data2,'order_discount');
			$cart_discount 				=	$this->get_total($new_data2,'cart_discount');
			$total_discount 			=	$this->get_total($new_data2,'total_discount');
			$order_shipping_tax 		=	$this->get_total($new_data2,'order_shipping_tax');
			$order_tax 					=	$this->get_total($new_data2,'order_tax');
			$refund_order_total 		=	$this->get_total($new_data2,'refund_order_total');
			$total_shipping_tax 		=	$this->get_total($new_data2,'total_shipping_tax');
			$part_order_refund_amount	=	$this->get_total($new_data2,'part_order_refund_amount');
			
			$actual_min_porjected 		=	$this->get_total($new_data2,'actual_min_porjected');
			
			//$couppevment 				=	$this->get_total($new_data2,'couppevment');
			//$totalsalse 				=	$this->get_total($new_data2,'totalsalse');
			
			
			$new_data2[$i]["month_name"] 				=	"Total";
			$new_data2[$i]["order_total"] 				=	$order_total;
			$new_data2[$i]["order_discount"] 			=	$order_discount;
			$new_data2[$i]["cart_discount"] 			=	$cart_discount;
			$new_data2[$i]["total_discount"] 			=	$total_discount;
			$new_data2[$i]["order_shipping_tax"] 		=	$order_shipping_tax;
			$new_data2[$i]["order_tax"] 				=	$order_tax;
			$new_data2[$i]["refund_order_total"] 		=	$refund_order_total;
			$new_data2[$i]["total_shipping_tax"] 		=	$total_shipping_tax;
			$new_data2[$i]["projected"] 				=	$total_projected;
			$new_data2[$i]["couppevment"] 				=	$this->get_percentage($order_total,$total_projected);
			$new_data2[$i]["totalsalse"] 				=	$this->get_percentage($order_total,$total_projected);
			
			$new_data2[$i]["actual_min_porjected"]		=	$actual_min_porjected;
			$new_data2[$i]["actual_porjected_per"]		=	$this->get_percentage($order_total,$total_projected);
			$new_data2[$i]["part_order_refund_amount"]	=	$part_order_refund_amount;
			
			//$new_data2[$i]["totalsalse"] 				=	$totalsalse;
			
			//$this->print_array($new_data2);			
			//return array();			
            return $new_data2;
            
        }
		
		function get_detail_items($shop_order_status, $hide_order_status, $start_date, $end_date, $month_count = 12, $cur_projected_sales_year = 2010){
			global $plugin_options;
			
			$refunded_id 		= $this->get_old_order_status(array('refunded'), array('wc-refunded'));
            $order_total		= $this->get_total_sales_by_month($shop_order_status, $hide_order_status, $start_date, $end_date,"_order_total");
			$order_discount		= $this->get_total_sales_by_month($shop_order_status, $hide_order_status, $start_date, $end_date,"_order_discount");
			$cart_discount		= $this->get_total_sales_by_month($shop_order_status, $hide_order_status, $start_date, $end_date,"_cart_discount");
			//$refund_order_total = $this->get_total_status_sales_by_month($refunded_id, $hide_order_status, $start_date, $end_date, $month_count,"_order_total");
			
			$part_refund		= $this->get_part_order_refunded_amount_by_month($shop_order_status, $hide_order_status, $start_date, $end_date, $month_count);
			$full_refund		= $this->get_full_order_refunded_amount_by_month($shop_order_status, $hide_order_status, $start_date, $end_date, $month_count);
			$refund_order_total = $full_refund;
			//$this->print_array($refund_order_total);
			
			//,"_cart_discount"
			//$this->print_array($order_discount);
			//$this->print_array($cart_discount);
			
            $start_month		= $start_date;
			$start_month_time	= strtotime($start_month);			
            $month_list			= array();
            $new_data2			= array();
            $total_projected	= 0;
			$i         			= 0;
			$m         			= 0;			
			
            for ($m=0; $m < ($month_count); $m++){
				$c					= 	strtotime("+$m month", $start_month_time);
				$key				= 	date('F-Y', $c);
				$value				= 	date('F', 	$c);
				$month_list[$key]	=	$value;
			}
			
			$projected_sales_year_option= $this->constants['plugin_key'].'_projected_amount_'.$cur_projected_sales_year;
			$projected_amounts 			= get_option($projected_sales_year_option,array());
						
			foreach ($month_list as $key => $value) {
				
				$projected_sales_month					=	$value;
				$projected_sales_month_amount			=	isset($projected_amounts[$projected_sales_month]) ? $projected_amounts[$projected_sales_month] : 100;
				$new_data2[$i]["projected"] 			=	$projected_sales_month_amount;
				$new_data2[$i]["month_name"] 			=	$key;
				$new_data2[$i]["monthname"] 			=	$value;
				$new_data2[$i]["part_order_refund_amount"] 	=	isset($part_refund[$key]) 		? $part_refund[$key] 			: 0;
                
				$this_order_total 						=	isset($order_total[$key]) 			? $order_total[$key] 			: 0;
				$this_order_total						=	strlen($this_order_total)>0			? $this_order_total 			: 0;				
				$this_order_total						=	$this_order_total - $new_data2[$i]["part_order_refund_amount"];
				$new_data2[$i]["order_total"] 			=	$this_order_total;
				
				$new_data2[$i]["refund_order_total"]	=	isset($refund_order_total[$key]) 	? $refund_order_total[$key]		: 0;
				
				$new_data2[$i]["actual_min_porjected"]	=	$new_data2[$i]["order_total"] - $projected_sales_month_amount;
				$new_data2[$i]["order_discount"] 		=	isset($order_discount[$key]) 		? $order_discount[$key] 		: 0;
				$new_data2[$i]["cart_discount"] 		=	isset($cart_discount[$key]) 		? $cart_discount[$key] 		: 0;
				$new_data2[$i]["total_discount"] 		=	$new_data2[$i]["order_discount"] + $new_data2[$i]["cart_discount"];
				
				
				$total_projected						=	$total_projected + $new_data2[$i]["projected"];
                $i++;
            }
			
			foreach ($new_data2 as $key => $value) {
				$order_total							=	isset($value["order_total"]) 	? trim($value["order_total"])	: 0;
				$order_total							=	strlen(($order_total)) > 0 		? $order_total	: 0;
				
				$new_data2[$key]["totalsalse"]			=	$this->get_percentage($order_total,$total_projected);
				$new_data2[$key]["actual_porjected_per"]=	$this->get_percentage($order_total,$value["projected"]);
            }
			
			$order_total 				=	$this->get_total($new_data2,'order_total');
			$actual_min_porjected 		=	$this->get_total($new_data2,'actual_min_porjected');		
			$order_discount 			=	$this->get_total($new_data2,'order_discount');
			$cart_discount 				=	$this->get_total($new_data2,'cart_discount');
			$total_discount 			=	$this->get_total($new_data2,'total_discount');
			
			$refund_order_total 		=	$this->get_total($new_data2,'refund_order_total');
			$part_order_refund_amount	=	$this->get_total($new_data2,'part_order_refund_amount');
			
			$order_total				= trim($order_total);
			$order_total				= strlen($order_total) > 0 ? $order_total : 0; 
			
			$new_data2[$i]["month_name"] 				=	"Total";
			$new_data2[$i]["order_total"] 				=	$order_total;
			$new_data2[$i]["projected"] 				=	$total_projected;			
			$new_data2[$i]["totalsalse"] 				=	$this->get_percentage($order_total,$total_projected);
			$new_data2[$i]["refund_order_total"] 		=	$refund_order_total;
			$new_data2[$i]["order_discount"] 			=	$order_discount;
			$new_data2[$i]["cart_discount"] 			=	$cart_discount;
			$new_data2[$i]["total_discount"] 			=	$total_discount;
			
			$new_data2[$i]["actual_min_porjected"]		=	$actual_min_porjected;
			$new_data2[$i]["actual_porjected_per"]		=	$this->get_percentage($order_total,$total_projected);
			
			$new_data2[$i]["part_order_refund_amount"]	=	$part_order_refund_amount;
				
            return $new_data2;
            
        }
		
		function get_total($items,$key_name){
			$total = 0;
			foreach ($items as $key => $value) {
				$total = $total + $value[$key_name];
            }
			
			$total	= trim($total);
			$total	= strlen($total) > 0 ? $total : 0;
			
			return $total;
		}
		
		function get_total_sales_by_month($shop_order_status, $hide_order_status, $start_date, $end_date, $meta_key = "_order_discount", $meta_key2 = NULL){
			global $wpdb;
			
            $sql = " SELECT DATE_FORMAT(posts.post_date,'%M-%Y') AS 'Month'";
			if($meta_key2){
				$sql .= " ,SUM(postmeta.meta_value + postmeta2.meta_value) AS 'TotalAmount'";
			}else{
				$sql .= " ,SUM(postmeta.meta_value) AS 'TotalAmount'";
			}
            
			
            $sql .= " FROM {$wpdb->prefix}posts as posts            
           
		    LEFT JOIN  {$wpdb->prefix}postmeta as postmeta ON posts.ID=postmeta.post_id";
			
			if($meta_key2){
				$sql .= " LEFT JOIN  {$wpdb->prefix}postmeta as postmeta2 ON posts.ID=postmeta2.post_id";
			}
            
            if($this->constants['post_order_status_found'] == 0 ){
				if(count($shop_order_status)>0){
						$sql .= " 
						LEFT JOIN  {$wpdb->prefix}term_relationships 	as term_relationships 	ON term_relationships.object_id		=	posts.ID
						LEFT JOIN  {$wpdb->prefix}term_taxonomy 		as term_taxonomy 		ON term_taxonomy.term_taxonomy_id	=	term_relationships.term_taxonomy_id";
				}
			}
            
            $sql .= " WHERE DATE(posts.post_date) >= DATE_SUB(now(), interval 11 month)";
            $sql .= " AND NOW() AND post_type='shop_order'";
            $sql .= " AND postmeta.meta_key='{$meta_key}'";
			
			if($meta_key2){
				$sql .= " AND postmeta2.meta_key='{$meta_key2}'";
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
            
            if ($start_date != NULL && $end_date != NULL) {
                $sql .= " AND DATE(posts.post_date) BETWEEN '{$start_date}' AND '{$end_date}'";
            }
            
            if (count($hide_order_status) > 0) {
                $in_hide_order_status = implode("', '", $hide_order_status);
                $sql .= " AND  posts.post_status NOT IN ('{$in_hide_order_status}')";
            }
            
            $sql .= "
            GROUP BY YEAR(posts.post_date), MONTH(posts.post_date)
            ORDER BY YEAR(posts.post_date), MONTH(posts.post_date);";
			
			//$this->print_sql($sql);
            
            $order_items = $wpdb->get_results($sql);
			$dataArray	 = array();
			foreach($order_items as $key => $order_item) {
                $Month				= $order_item->Month;                
                $Amount           	= $order_item->TotalAmount;
                $dataArray[$Month]	= $Amount;
            }
			
			return $dataArray;
		}
		
		function get_total_status_sales_by_month($shop_order_status, $hide_order_status, $start_date, $end_date, $month_count, $meta_key = "_order_discount"){
			global $wpdb;
			
			if($this->constants['post_order_status_found'] == 0 ){			
				$date_field =  "post_date";
			}else{
				$date_field =  "post_modified" ;
			}
			
            $sql = " SELECT    
            DATE_FORMAT(posts.{$date_field},'%M-%Y') AS 'Month'
            ,SUM(meta_value) AS 'TotalAmount'
			,posts.post_status AS 'order_status'
            FROM {$wpdb->prefix}posts as posts            
            LEFT JOIN  {$wpdb->prefix}postmeta as postmeta ON posts.ID=postmeta.post_id";
            
            if($this->constants['post_order_status_found'] == 0 ){
				if(count($shop_order_status)>0){
						$sql .= " 
						LEFT JOIN  {$wpdb->prefix}term_relationships 	as term_relationships 	ON term_relationships.object_id		=	posts.ID
						LEFT JOIN  {$wpdb->prefix}term_taxonomy 		as term_taxonomy 		ON term_taxonomy.term_taxonomy_id	=	term_relationships.term_taxonomy_id";
				}
			}
			
            $sql .= "
            WHERE 1*1";
			
			/*$sql .= "
			DATE(posts.post_date) >= DATE_SUB(now(), interval 11 month)
            AND NOW()";*/
			
			$sql .= " AND post_type='shop_order' AND meta_key='{$meta_key}'";
            
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
			
            
            if ($start_date != NULL && $end_date != NULL) {
                $sql .= " AND DATE(posts.{$date_field}) BETWEEN '{$start_date}' AND '{$end_date}'";
            }
            
            if (count($hide_order_status) > 0) {
                $in_hide_order_status = implode("', '", $hide_order_status);
                $sql .= " AND  posts.post_status NOT IN ('{$in_hide_order_status}')";
            }
           
            $sql .= "
            GROUP BY YEAR(posts.{$date_field}), MONTH(posts.{$date_field})
            ORDER BY YEAR(posts.{$date_field}), MONTH(posts.{$date_field});";
            
            $order_items = $wpdb->get_results($sql);
			
			//$this->print_sql($sql);
			//$this->print_array($order_items);
		   
			$dataArray	 = array();
			foreach($order_items as $key => $order_item) {
                $Month				= $order_item->Month;                
                $Amount           	= $order_item->TotalAmount;
                $dataArray[$Month]	= $Amount;
            }
			
			return $dataArray;
		}
		
		function get_full_order_refunded_amount_by_month($shop_order_status, $hide_order_status, $start_date, $end_date, $month_count){
			global $wpdb;
			
            $sql = " SELECT SUM(ROUND(postmeta.meta_value,2)) 	as total_amount,  DATE_FORMAT(posts.post_date,'%M-%Y') AS 'Month'
						
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
			
			
			
			$order_date_field_key = isset($_REQUEST['order_date_field_key']) ? $_REQUEST['order_date_field_key'] : 'post_date';
			if($order_date_field_key == "post_date" || $order_date_field_key == "post_modified"){
				if ($start_date != NULL &&  $end_date !=NULL){
					$sql .= " AND DATE(posts.{$order_date_field_key}) BETWEEN '".$start_date."' AND '". $end_date ."'";
				}
			}
			
			$sql .= " AND shop_order.post_type = 'shop_order'";
            
           if($this->constants['post_order_status_found'] == 0 ){
			    $refunded_id 	= $this->get_old_order_status(array('refunded'), array('wc-refunded'));
				$refunded_id    = implode(",",$refunded_id);
				$sql .= " AND terms2.term_id NOT IN (".$refunded_id .")";
			}else{
				$sql .= " AND shop_order.post_status IN ('wc-refunded')";
			}
           
            
            if (count($hide_order_status) > 0) {
                $in_hide_order_status = implode("', '", $hide_order_status);
                $sql .= " AND shop_order.post_status NOT IN ('{$in_hide_order_status}')";
            }
            
            $sql .= "
            GROUP BY YEAR(posts.post_date), MONTH(posts.post_date)
            ORDER BY YEAR(posts.post_date), MONTH(posts.post_date);";
			
			$wpdb->query("SET SQL_BIG_SELECTS=1");
            
            $order_items = $wpdb->get_results($sql);
			
			//$this->print_sql($sql);
			//$this->print_array($order_items);
		   
			$dataArray	 = array();
			if(count($order_items)>0){
				foreach($order_items as $key => $order_item) {
					$Month				= $order_item->Month;                
					$Amount           	= $order_item->total_amount;
					$dataArray[$Month]	= $Amount;
				}
			}
			
			//$this->print_array($dataArray);
			
			return $dataArray;
		}
		
		function get_part_order_refunded_amount_by_month($shop_order_status, $hide_order_status, $start_date, $end_date, $month_count){
			global $wpdb;
			
            $sql = " SELECT SUM(ROUND(postmeta.meta_value,2)) 	as total_amount,  DATE_FORMAT(posts.post_date,'%M-%Y') AS 'Month'
						
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
			
			 if ($start_date != NULL && $end_date != NULL) {
                $sql .= " AND DATE(posts.post_date) BETWEEN '{$start_date}' AND '{$end_date}'";
            }
			
			$sql .= " AND shop_order.post_type = 'shop_order'";
            
           if($this->constants['post_order_status_found'] == 0 ){
			    $refunded_id 	= $this->get_old_order_status(array('refunded'), array('wc-refunded'));
				$refunded_id    = implode(",",$refunded_id);
				$sql .= " AND terms2.term_id NOT IN (".$refunded_id .")";
			   
				if(count($shop_order_status)>0){
					$in_shop_order_status = implode(",",$shop_order_status);
					$sql .= " AND term_taxonomy.term_id IN ({$in_shop_order_status})";
				}
			}else{
				
				if(count($shop_order_status)>0){
					$in_shop_order_status		= implode("', '",$shop_order_status);
					$sql .= " AND shop_order.post_status IN ('{$in_shop_order_status}')";
				}
				
				$sql .= " AND shop_order.post_status NOT IN ('wc-refunded')";
			}
           
            
            if (count($hide_order_status) > 0) {
                $in_hide_order_status = implode("', '", $hide_order_status);
                $sql .= " AND shop_order.post_status NOT IN ('{$in_hide_order_status}')";
            }
            
            $sql .= "
            GROUP BY YEAR(posts.post_date), MONTH(posts.post_date)
            ORDER BY YEAR(posts.post_date), MONTH(posts.post_date);";
			
			$wpdb->query("SET SQL_BIG_SELECTS=1");
            
            $order_items = $wpdb->get_results($sql);
			
			//$this->print_sql($sql);
			//$this->print_array($order_items);
		   
			$dataArray	 = array();
			if(count($order_items)>0){
				foreach($order_items as $key => $order_item) {
					$Month				= $order_item->Month;                
					$Amount           	= $order_item->total_amount;
					$dataArray[$Month]	= $Amount;
				}
			}
			
			//$this->print_array($dataArray);
			
			return $dataArray;
		}
		
		
		
	}
}

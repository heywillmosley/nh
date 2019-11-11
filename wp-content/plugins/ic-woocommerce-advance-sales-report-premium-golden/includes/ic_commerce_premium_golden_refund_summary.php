<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if(!class_exists('IC_Commerce_Premium_Golden_Refund_Summary')){
	
	class IC_Commerce_Premium_Golden_Refund_Summary  extends IC_Commerce_Premium_Golden_Fuctions{
		
		public $constants 			=	array();
		
		function __construct($constants = array(), $plugin_key = ""){
			
			
			
			$this->constants	= array_merge($this->constants, $constants);
			
			add_action("ic_commerce_report_page_titles", 					array($this, "ic_commerce_report_page_titles"),31,2);
			
			$report_name = isset($_REQUEST['report_name']) ? $_REQUEST['report_name'] : '';
			
			if($report_name == "refund_summary"){				
				add_filter("ic_commerce_report_page_default_items", 			array($this, "ic_commerce_report_page_default_items"),31,5);
				add_filter("ic_commerce_report_page_columns", 					array($this, "ic_commerce_report_page_columns"),31,2);
				add_filter("ic_commerce_report_page_result_columns", 			array($this, "ic_commerce_report_page_result_columns"),31,2);
				add_filter("ic_commerce_report_page_data_items", 				array($this, "ic_commerce_report_page_data_items"),31,5);				
				add_action("ic_commerce_report_page_search_form_bottom", 		array($this, "ic_commerce_report_page_search_form_bottom"));				
				add_action("ic_commerce_report_page_before_default_request", 	array($this, "ic_commerce_report_page_before_default_request"));				
				add_action("ic_commerce_report_page_footer_area", 				array($this, "ic_commerce_report_page_footer_area"));
			}
		}
		
		function ic_commerce_report_page_footer_area(){
			?>
            	<script type="text/javascript">
                	jQuery(document).ready(function($) {
                        jQuery("#group_by").change(function(){
							var group_by = jQuery("#group_by").val();
							
							if(group_by == 'refund_id'){
								jQuery("#show_refund_note").attr('disabled',false);
							}else{
								jQuery("#show_refund_note").attr('disabled',true);
							}
							
						});
                    });
                </script>
                <style type="text/css">
					.iccommercepluginwrap .widefat th.part_order_refund_amount{ width:100px;}
					.iccommercepluginwrap .widefat th.order_refund_amount{ width:100px;}
					.iccommercepluginwrap .widefat th.total_refund_amount{ width:100px;}
					.iccommercepluginwrap .widefat th.order_total{ width:100px;}
					.iccommercepluginwrap .widefat th.total_amount{ width:100px;}
					 
                </style>
            <?php
		}
		
		function ic_commerce_report_page_before_default_request(){
			$group_by 	= $this->get_request('group_by','order_id',true);
			if($group_by == 'order_id' || $group_by == 'refund_id'){
				//Defalt limit
			}else{
				$_REQUEST['limit'] = 99999999999999;
			}	
			
			$_REQUEST['group_by'] = isset($_REQUEST['group_by']) ? $_REQUEST['group_by'] : 'refund_id';	
		}
		
		function ic_commerce_report_page_search_form_bottom(){
			?>
            	<div class="form-group">
                    <div class="FormRow checkbox FirstRow">
                        <div class="label-text"><label for="on_refund_date"><?php _e("On Refund Date:",'icwoocommerce_textdomains');?></label></div>
                        <div style="padding-top:5px;"><input type="checkbox" id="on_refund_date" name="on_refund_date"  value="yes" <?php if($this->get_request('on_refund_date','yes',true) == "yes"){ echo ' checked="checked"';}?> /></div>
                    </div>
                </div>
                
                <div class="form-group">
                    <div class="FormRow checkbox FirstRow">
                        <div class="label-text"><label for="show_refund_note"><?php _e("Show Refund Note:",'icwoocommerce_textdomains');?></label></div>
                        <div style="padding-top:5px;"><input type="checkbox" id="show_refund_note" name="show_refund_note"  value="yes" <?php if($this->get_request('show_refund_note','yes',true) == "yes"){ echo ' checked="checked"';}?> /></div>
                    </div>
                    
                    <div class="FormRow">
                        <div class="label-text"><label for="group_by"><?php _e("Group By:",'icwoocommerce_textdomains');?></label></div>
                        <div class="input-text">
                            <?php 
                                $group_by = 'refund_id';
                                $groups = array(
                                        "refund_id" 		=> __("Refund ID",	'icwoocommerce_textdomains'),
                                        "order_id" 			=> __("Order ID",	'icwoocommerce_textdomains'),
                                        "refunded" 			=> __("Refunded",	'icwoocommerce_textdomains'),
                                        "daily" 			=> __("Daily",		'icwoocommerce_textdomains'),
                                        "monthly" 			=> __("Monthly",	'icwoocommerce_textdomains'),
                                        "yearly" 			=> __("Yearly",		'icwoocommerce_textdomains')
                                );
                                $this->create_dropdown($groups,"group_by[]","group_by","","group_by",$group_by, 'array', false, 5);
                            ?>                                                        
                        </div>                                                    
                    </div>
                </div>
            <?php
		} 
		
		function ic_commerce_report_page_titles($page_titles = '',$report_name = '', $plugin_options = ''){
			$page_titles['refund_summary'] = __('Refund Summary',	'icwoocommerce_textdomains');
			return $page_titles;
		}
		
		function ic_commerce_report_page_coupon_code_field_tabs($tabs = array(), $report_name = ''){
			$tabs['coupon_discount_type'] = 'coupon_discount_type';
			return $tabs;
		}
		
		function ic_commerce_report_page_columns($columns = array(), $report_name = ''){
			
			$group_by 								= $this->get_request('group_by','order_id');
			$status_key = "order_status";
			
			if($group_by == "order_id"){
				$columns 	= array(
					"order_id"							=>	__("Order ID", 				'icwoocommerce_textdomains')
					,"order_date"						=>  __("Order Date",			'icwoocommerce_textdomains')
					,$status_key						=>  __("Order Status", 			'icwoocommerce_textdomains')					
					,"refund_count"						=>	__("Refund Counts",			'icwoocommerce_textdomains')
					,"part_order_refund_amount"			=>	__("Part Refund",			'icwoocommerce_textdomains')
					,"order_refund_amount"				=>	__("Full Refund",			'icwoocommerce_textdomains')
					,"total_refund_amount"				=>	__("Total Refund",			'icwoocommerce_textdomains')
					,"total_amount"						=>  __("Order Total",		 	'icwoocommerce_textdomains')
				);
			
			}else if($group_by == "refunded"){
				$columns 	= array(
					"refund_user"						=>	__("Refunded By", 			'icwoocommerce_textdomains')
					,"refund_count"						=>	__("Refund Counts",			'icwoocommerce_textdomains')
					,"part_order_refund_amount"			=>	__("Part Refund",			'icwoocommerce_textdomains')
					,"order_refund_amount"				=>	__("Full Refund",			'icwoocommerce_textdomains')
					,"total_refund_amount"				=>	__("Total Refund",			'icwoocommerce_textdomains')
					,"total_amount"						=>  __("Order Total",		 	'icwoocommerce_textdomains')
				);
			}else if($group_by == "daily"){
				$columns 	= array(
					"group_date"						=>	__("Refund Date",			'icwoocommerce_textdomains')
					,"part_order_refund_amount"			=>	__("Part Refund",			'icwoocommerce_textdomains')
					,"order_refund_amount"				=>	__("Full Refund",			'icwoocommerce_textdomains')
					,"total_refund_amount"				=>	__("Total Refund",			'icwoocommerce_textdomains')
					,"total_amount"						=>  __("Order Total",		 	'icwoocommerce_textdomains')
				);
			}else if($group_by == "monthly" || $group_by == "yearly"){
				$columns 	= array(
					"group_column"						=>	__("Refund Date",			'icwoocommerce_textdomains')
					,"part_order_refund_amount"			=>	__("Part Refund",			'icwoocommerce_textdomains')
					,"order_refund_amount"				=>	__("Full Refund",			'icwoocommerce_textdomains')
					,"total_refund_amount"				=>	__("Total Refund",			'icwoocommerce_textdomains')
					,"total_amount"						=>  __("Order Total",		 	'icwoocommerce_textdomains')
				);
			}else{
				$columns 	= array(
					"refund_id"							=>	__("Refund ID",				'icwoocommerce_textdomains')
					,"refund_date"						=>  __("Refund Date",			'icwoocommerce_textdomains')
					,"refund_status"					=>	__("Refund Status",			'icwoocommerce_textdomains')
					,"refund_user"						=>  __("Refund By",				'icwoocommerce_textdomains')
					,"order_id"							=>	__("Order ID", 				'icwoocommerce_textdomains')
					,"order_date"						=>  __("Order Date",			'icwoocommerce_textdomains')
					,$status_key						=>  __("Order Status", 			'icwoocommerce_textdomains')
					,"refund_note"						=>  __("Refund Note",			'icwoocommerce_textdomains')
					,"order_total"						=>	__("Order Total",			'icwoocommerce_textdomains')
					,"total_amount"						=>  __("Refund Amount",		 	'icwoocommerce_textdomains')
				);
			}
			
			$show_refund_note 	= $this->get_request('show_refund_note','no');					
			if($show_refund_note == "no") unset($columns['refund_note']);
					
			return $columns;
			
		}
		
		function ic_commerce_report_page_result_columns($total_columns = array(), $report_name = ''){
			
			$group_by 								= $this->get_request('group_by','order_id');
			
			
			if($group_by == "order_id"){
				$total_columns 	= array(
					"total_row_count"						=> 	__("Results Count", 		'icwoocommerce_textdomains')
					,"refund_count"						=>	__("Refund Counts",			'icwoocommerce_textdomains')
					,"part_order_refund_amount"			=>	__("Part Refund",			'icwoocommerce_textdomains')
					,"order_refund_amount"				=>	__("Full Refund",			'icwoocommerce_textdomains')
					,"total_refund_amount"				=>	__("Total Refund",			'icwoocommerce_textdomains')
					,"total_amount"						=>  __("Order Total",		 	'icwoocommerce_textdomains')
				);
			
			}else if($group_by == "refunded"){
				$total_columns 	= array(
					"total_row_count"						=> 	__("Results Count", 		'icwoocommerce_textdomains')
					,"refund_count"						=>	__("Refund Counts",			'icwoocommerce_textdomains')
					,"part_order_refund_amount"			=>	__("Part Refund",			'icwoocommerce_textdomains')
					,"order_refund_amount"				=>	__("Full Refund",			'icwoocommerce_textdomains')
					,"total_refund_amount"				=>	__("Total Refund",			'icwoocommerce_textdomains')
					,"total_amount"						=>  __("Order Total",		 	'icwoocommerce_textdomains')
				);
			}else if($group_by == "refund_id"){
				$total_columns 	= array(
					"total_row_count"						=> 	__("Results Count", 		'icwoocommerce_textdomains')
					,"total_amount"						=>  __("Refund Amount",		 	'icwoocommerce_textdomains')
				);	
				
			}else if($group_by == "daily" || $group_by == "monthly" || $group_by == "yearly"){
				$total_columns 	= array(
					"total_row_count"						=> 	__("Results Count", 		'icwoocommerce_textdomains')
					,"part_order_refund_amount"			=>	__("Part Refund",			'icwoocommerce_textdomains')
					,"order_refund_amount"				=>	__("Full Refund",			'icwoocommerce_textdomains')
					,"total_refund_amount"				=>	__("Total Refund",			'icwoocommerce_textdomains')
					,"total_amount"						=>  __("Order Total",		 	'icwoocommerce_textdomains')
				);
			}else{
				$total_columns 	= array(
					"total_row_count"						=> 	__("Results Count", 		'icwoocommerce_textdomains')
					,"refund_count"							=>	__("Refund Counts",			'icwoocommerce_textdomains')
					,"total_amount"							=>  __("Refund Amount", 		'icwoocommerce_textdomains')
					,"total_amount"							=>  __("Refund Amount", 		'icwoocommerce_textdomains')
				);
			}
			
			
			return $total_columns;
		}
		
		function ic_commerce_report_page_default_items($rows = '', $type = '', $columns = '', $report_name = '', $that = ''){
			return $this->ic_commerce_custom_all_refund_summary_query($rows, $type, $columns, $report_name, $that);
			return $rows;
		}
		
		function ic_commerce_custom_all_refund_summary_query($rows = '', $type = '', $columns = '', $report_name = '', $that = ''){
			
			global $wpdb;
			
			$request = $that->get_all_request();
			
			$group_by 								= $this->get_request('group_by','refund_id');
			
			if($group_by == "daily" || $group_by == "monthly" || $group_by == "yearly"){
				return array();
			}
			
			
			
			if(!isset($this->items_query)){
				
				$request 				= $that->get_all_request();extract($request);
				$group_by 				= $that->get_request('group_by','order_id');
				$sql 					= "";				
				if($group_by == 'order_id' || $group_by == 'refund_id'|| $group_by == 'refunded'){
					$sql = "SELECT ";				
					$sql .= " SUM(ROUND(refund_amount.meta_value,2))  	AS total_refund_amount";				
					$sql .= " ,SUM(ROUND(refund_amount.meta_value,2))  	AS total_amount";
					$sql .= " ,COUNT(*)  						AS refund_count";				
					$sql .= " ,shop_order_refund.ID 			AS refund_order_id";				
					$sql .= " ,shop_order_refund.post_parent  	AS refunded_order_id";				
					$sql .= ", shop_order_refund.ID 			AS refund_id";
								
					$sql .= ", shop_order_refund.post_date 		AS refund_date";		
					$sql .= ", shop_order_refund.post_excerpt 	AS refund_note";
					$sql .= ", shop_order_refund.post_author	AS customer_user";
					
					$sql .= ", shop_order.post_status 			AS order_status";				
					$sql .= ", shop_order.ID 					AS order_id";
					$sql .= ", shop_order.post_date 			AS order_date";
					
					$group_sql = "";
					switch($group_by){
						case "order_id":
							$sql .= ", shop_order.ID as group_column";
							$sql .= ", shop_order.ID as order_column";
							$sql .= " ,order_total.meta_value  AS order_total";
							break;
						case "refund_id":
							$sql .= ", shop_order_refund.ID as group_column";
							$sql .= ", shop_order_refund.ID as order_column";
							$sql .= " ,order_total.meta_value  AS order_total";
							$sql .= ", TRIM(LEADING 'wc-' FROM shop_order_refund.post_status) 	AS refund_status";
							$sql .= ", shop_order_refund.post_status 	AS _refund_status";
							break;
						case "refunded":
							$sql .= ", shop_order_refund.post_author as group_column";
							$sql .= ", shop_order_refund.post_author as order_column";
							//$sql .= " ,SUM(order_total.meta_value)  AS order_total";
							break;
						case "daily":
							if($on_refund_date == 'yes'){
								$sql .= ", DATE(shop_order_refund.post_date) as group_column";
								$sql .= ", DATE(shop_order_refund.post_date) as group_date";
								$sql .= ", DATE(shop_order_refund.post_date) as order_column";
							}else{
								$sql .= ", DATE(shop_order.post_date) as group_column";
								$sql .= ", DATE(shop_order.post_date) as group_date";
								$sql .= ", DATE(shop_order.post_date) as order_column";
							}							
							$sql .= " ,SUM(order_total.meta_value)  AS order_total";
							break;
						case "monthly":
							if($on_refund_date == 'yes'){
								$sql .= ", CONCAT(MONTHNAME(shop_order_refund.post_date) , ' ',YEAR(shop_order.post_modified)) as group_column";
								$sql .= ", DATE(shop_order_refund.post_date) as order_column";
							}else{
								$sql .= ", CONCAT(MONTHNAME(shop_order.post_date) , ' ',YEAR(shop_order.post_modified)) as group_column";
								$sql .= ", DATE(shop_order.post_date) as order_column";
							}
							
							break;
						case "yearly":
							if($on_refund_date == 'yes'){
								$sql .= ", YEAR(shop_order_refund.post_date)as group_column";
								$sql .= ", DATE(shop_order_refund.post_date) as order_column";	
							}else{
								$sql .= ", YEAR(shop_order.post_date)as group_column";
								$sql .= ", DATE(shop_order.post_date) as order_column";	
							}
							break;
						default:
							$sql .= ", shop_order.ID as group_column";
							$sql .= ", shop_order.ID as order_column";
							$sql .= " ,SUM(order_total.meta_value)  AS order_total";
							break;
					}
					
					$sql .= " FROM {$wpdb->prefix}posts as shop_order_refund";
					
					$sql .= " LEFT JOIN  {$wpdb->prefix}postmeta as refund_amount ON refund_amount.post_id	=	shop_order_refund.ID";
					
					$sql .= " LEFT JOIN  {$wpdb->prefix}posts as shop_order ON shop_order.ID		=	shop_order_refund.post_parent";
					
					$sql .= " LEFT JOIN  {$wpdb->prefix}postmeta as order_total ON order_total.post_id	=	shop_order_refund.post_parent";
					
					$sql .= " WHERE 1*1";
					
					$sql .= " AND shop_order_refund.post_type = 'shop_order_refund'";
					
					$sql .= " AND refund_amount.meta_key='_refund_amount'";
					
					$sql .= " AND order_total.meta_key='_order_total'";
					
					$order_date_field_key = "post_date";
					if($on_refund_date == 'yes'){						
						if($order_date_field_key == "post_date" || $order_date_field_key == "post_modified"){
							if ($start_date != NULL &&  $end_date != NULL){
								$sql .= " AND DATE(shop_order_refund.{$order_date_field_key}) BETWEEN '".$start_date."' AND '". $end_date ."'";
							}
						}
					}else{
						if($order_date_field_key == "post_date" || $order_date_field_key == "post_modified"){
							if ($start_date != NULL &&  $end_date != NULL){
								$sql .= " AND DATE(shop_order.{$order_date_field_key}) BETWEEN '".$start_date."' AND '". $end_date ."'";
							}
						}
					}
					
					$sql .= " GROUP BY  group_column";
					
					$sql .= " ORDER BY order_column DESC";
				}else{
					
					$order_status			= $this->get_string_multi_request('order_status',$order_status, "-1");
					
					$sql = "SELECT ";
					
					$sql .= " shop_order.post_status 			AS order_status";				
					$sql .= ", shop_order.ID 					AS order_id";
					$sql .= ", shop_order.post_date 			AS order_date";
					$sql .= " ,SUM(ROUND(order_total.meta_value,2))  	AS order_total";
					
					$group_sql = "";
					switch($group_by){
						case "order_id":
						case "refund_id":
							$sql .= ", shop_order.ID as group_column";
							$sql .= ", shop_order.ID as order_column";
							break;
						case "refunded":
							$sql .= ", shop_order.post_author as group_column";
							$sql .= ", shop_order.post_author as order_column";
							break;
						case "daily":
							$sql .= ", DATE(shop_order.post_date) as group_column";
							$sql .= ", DATE(shop_order.post_date) as group_date";
							$sql .= ", DATE(shop_order.post_date) as order_column";
							break;
						case "monthly":
							$sql .= ", CONCAT(MONTHNAME(shop_order.post_date) , ' ',YEAR(shop_order.post_date)) as group_column";
							$sql .= ", DATE(shop_order.post_date) as order_column";
							break;
						case "yearly":
							$sql .= ", YEAR(shop_order.post_date)as group_column";
							$sql .= ", DATE(shop_order.post_date) as order_column";
							break;
						default:
							$sql .= ", shop_order.ID as group_column";
							$sql .= ", shop_order.ID as order_column";
							break;
					}
					
					$sql .= " FROM {$wpdb->prefix}posts as shop_order";
					
					$sql .= " LEFT JOIN  {$wpdb->prefix}postmeta as order_total ON order_total.post_id	=	shop_order.ID";
					
					$sql .= " WHERE shop_order.post_type 	= 'shop_order'";
					
					$sql .= " AND  order_total.meta_key		='_order_total'";
					
					if($order_date_field_key == "post_date" || $order_date_field_key == "post_modified"){
						if ($start_date != NULL &&  $end_date != NULL){
							$sql .= " AND DATE(shop_order.{$order_date_field_key}) BETWEEN '".$start_date."' AND '". $end_date ."'";
						}
					}
					
					
					if($order_status  && $order_status != '-1' and $order_status != "'-1'")
						$sql .= " AND shop_order.post_status IN (".$order_status.",'wc-refunded')";
					
					$sql .= " GROUP BY  group_column";
					
					$sql .= " ORDER BY order_column DESC";
					
				}		
				
				$that->items_query = $sql;
			
			}else{
				
				$sql = $that->items_query;
				
			}
			
			$order_items = $that->get_query_items($type,$sql);
			
			//$this->print_array($order_items);
			
			if($type != 'total_row'){
				
				$group_by = isset($request['group_by']) ? $request['group_by'] : 'order_id';
				
				if($group_by == "refund_id" || $group_by == "refunded"){
					
					//$this->print_array($order_items);
					
					$customer_user_ids 	= $this->get_items_id_list($order_items,'customer_user','','string');
					$users_details		= $this->get_users_details($customer_user_ids);
					$users_details		= isset($users_details['username']) ? $users_details['username'] : array();
					
					foreach($order_items as $order_item_key => $order_item){
						$customer_user_id 	= isset($order_item->customer_user) ? $order_item->customer_user : 0;
						$order_items[$order_item_key]->refund_user = isset($users_details[$customer_user_id]) ? $users_details[$customer_user_id] : $customer_user_id;
					}
				}
				
				if($group_by == "order_id" || $group_by == "refund_id"){		
					$order_statuses 	= wc_get_order_statuses();
					foreach($order_items as $order_item_key => $order_item){
						$order_status 	= isset($order_item->order_status) ? $order_item->order_status : '';
						$order_items[$order_item_key]->order_status = isset($order_statuses[$order_status]) ? $order_statuses[$order_status] : $order_status;
					}
				}
				
			}
			
			return $order_items;
		}
		
		function ic_commerce_report_page_data_items($order_items = '', $request = '', $type = '', $page = '', $report_name = ''){
			
			
			
			$group_by = isset($request['group_by']) ? $request['group_by'] : 'order_id';
			
			if($type == 'total_row'){
				if($group_by == "daily" || $group_by == "monthly" || $group_by == "yearly"){
					$order_items = $this->total_rows;
					return $order_items;
				}
			}
			
			if($group_by == "order_id" || $group_by == "refund_id" || $group_by == "refunded"){
				$part_refunded_amount = $this->get_refunded_amount($order_items , $request , $type , $page , $report_name,'part_refunded');
				foreach($order_items as $order_item_key => $order_item){
					$group_column 	= isset($order_item->group_column) ? $order_item->group_column : 0;				
					if(isset($part_refunded_amount[$group_column])){
						$part_refund 	= isset($part_refunded_amount[$group_column]) ? $part_refunded_amount[$group_column] : 0;										
						$order_items[$order_item_key]->part_order_refund_amount  = $part_refund;
						unset($part_refunded_amount[$group_column]);
					}
				}
			}
			
			if($group_by == "refunded" || $group_by == "order_id"){
				$full_refunded_amount 	= $this->get_refunded_amount($order_items , $request , $type , $page , $report_name,'full_refunded');
				
				//$this->print_array($full_refunded_amount);
				
				foreach($order_items as $order_item_key => $order_item){
					$group_column 	= isset($order_item->group_column) ? $order_item->group_column : 0;				
					if(isset($full_refunded_amount[$group_column])){
						$full_refunded 	= isset($full_refunded_amount[$group_column]) 	? $full_refunded_amount[$group_column] 	: 0;
						$order_items[$order_item_key]->order_refund_amount  = $full_refunded;
						unset($part_refunded_amount[$group_column]);
					}
				}
			}
						
			if($group_by == "order_id" || $group_by == "refunded"){
				$order_totals 		= $this->get_order_total($order_items , $request , $type , $page , $report_name);			
				foreach($order_items as $order_item_key => $order_item){
					$group_column 	= isset($order_item->group_column) ? $order_item->group_column : 0;
					if(isset($order_totals[$group_column])){						
						$order_total 	= isset($order_totals[$group_column]) ? $order_totals[$group_column] : 0;										
						$order_items[$order_item_key]->order_total  = $order_total;
						$order_items[$order_item_key]->total_amount  = $order_total;
						unset($order_totals[$group_column]);
					}else{
						$order_items[$order_item_key]->order_total  = 0;
						$order_items[$order_item_key]->total_amount  = 0;
					}
				}
			}
			
			if($group_by == "daily" || $group_by == "monthly" || $group_by == "yearly"){
				
				$start_date = $this->get_request('start_date');
				$end_date 	= $this->get_request('end_date');
				
				$startDate 		= strtotime($start_date);
				$endDate   		= strtotime($end_date);
				$currentDate 	= $endDate;
				
				if($group_by == "daily"){
					while ($currentDate >= $startDate) {
						$month = date('Y-m-d',$currentDate);
						$dates[$month] = $month;
						$currentDate = strtotime('-1 day', $currentDate);
					}
				}
				
				if($group_by == "monthly"){
					while ($currentDate >= $startDate) {
						$month = date('F Y',$currentDate);
						$dates[$month] = $month;
						$currentDate = strtotime('-1 month', $currentDate);
					}
				}
				
				if($group_by == "yearly"){
					while ($currentDate >= $startDate) {
						$month = date('Y',$currentDate);
						$dates[$month] = $month;
						$currentDate = strtotime('-1 year', $currentDate);
					}
				}
				
				$new_order_total = array();
				foreach($order_items as $order_item_key => $order_item){
					$group_column 	= isset($order_item->group_column) ? $order_item->group_column : 0;											
					$new_order_total[$group_column] = isset($order_item->order_total) ? $order_item->order_total : 0;
				}
				
				$both_refunded_amount 	= $this->get_refunded_amount($order_items , $request , $type , $page , $report_name,'both_refunded');
				$part_refunded_amount 	= $this->get_refunded_amount($order_items , $request , $type , $page , $report_name,'part_refunded');
				$full_refunded_amount 	= $this->get_refunded_amount($order_items , $request , $type , $page , $report_name,'full_refunded');
				$new_key 				= 0;
				$new_order_items 		= array();
				
				foreach($dates as $group_column_key => $group_column_value){
					$order_total 	= isset($new_order_total[$group_column_key]) 		? $new_order_total[$group_column_key] 		: 0;
					$both_refunded 	= isset($both_refunded_amount[$group_column_key]) 	? $both_refunded_amount[$group_column_key] 	: 0;
					$part_refunded 	= isset($part_refunded_amount[$group_column_key]) 	? $part_refunded_amount[$group_column_key] 	: 0;
					$full_refunded 	= isset($full_refunded_amount[$group_column_key]) 	? $full_refunded_amount[$group_column_key] 	: 0;
					
					if($order_total > 0 || $both_refunded > 0 || $part_refunded > 0 || $full_refunded > 0){
						
						$new_order_items[$new_key]								= new stdClass();
						$new_order_items[$new_key]->order_total 				= $order_total;
						$new_order_items[$new_key]->total_amount 				= $order_total;
						$new_order_items[$new_key]->part_order_refund_amount 	= $part_refunded;
						$new_order_items[$new_key]->order_refund_amount			= $full_refunded;
						$new_order_items[$new_key]->total_refund_amount			= $both_refunded;
						
						$new_order_items[$new_key]->group_column 				= $group_column_key;
						$new_order_items[$new_key]->group_date 					= $group_column_key;
						$new_key++;
					}
				}
				
				$order_items 		= $new_order_items;
				$this->total_rows 	= $order_items;
			}			
			return $order_items;
		}
		
		var $total_rows = NULL;
		
		function get_order_total($order_items = '', $request = '', $type = '', $page = '', $report_name = ''){
				global $wpdb;
				
				extract($request);
				
				$sql 					= "";				
				$order_status			= $this->get_string_multi_request('order_status',$order_status, "-1");
				//$hide_order_status		= $this->get_string_multi_request('hide_order_status',$hide_order_status, "-1");
				
				$order_ids 				= '';
				
				if($group_by == "order_id"){
					$order_ids 	= $this->get_items_id_list($order_items,'order_id','','string');
				}
				
				$sql = "SELECT ";
				
				$sql .= " SUM(order_total.meta_value)  AS order_total";
				
				$group_sql = "";
				switch($group_by){
					case "order_id":
						$sql .= ", shop_order.ID as group_column";
						$sql .= ", shop_order.ID as order_column";
						break;
					case "refunded":
						$sql .= ", shop_order.post_author as group_column";
						$sql .= ", shop_order.post_author as order_column";
						break;
					case "daily":
						$sql .= ", DATE(shop_order.post_date) as group_column";
						$sql .= ", DATE(shop_order.post_date) as group_date";
						$sql .= ", DATE(shop_order.post_date) as order_column";
						break;
					case "monthly":
						$sql .= ", CONCAT(MONTHNAME(shop_order.post_date) , ' ',YEAR(shop_order.post_date)) as group_column";
						$sql .= ", DATE(shop_order.post_date) as order_column";
						break;
					case "yearly":
						$sql .= ", YEAR(shop_order.post_date)as group_column";
						$sql .= ", DATE(shop_order.post_date) as order_column";
						break;
					default:
						$sql .= ", shop_order.ID as group_column";
						$sql .= ", shop_order.ID as order_column";
						break;
					
				}
				
				$sql .= " FROM {$wpdb->prefix}posts as shop_order";
				
				$sql .= " LEFT JOIN  {$wpdb->prefix}postmeta as order_total ON order_total.post_id	=	shop_order.ID";
				
					$sql .= " WHERE shop_order.post_type 	= 'shop_order'";
					
					$sql .= " AND  order_total.meta_key		='_order_total'";
				
				$order_date_field_key = "post_date"; 
				
				if($order_date_field_key == "post_date" || $order_date_field_key == "post_modified"){
					if ($start_date != NULL &&  $end_date !=NULL){
						$sql .= " AND DATE(shop_order.{$order_date_field_key}) BETWEEN '".$start_date."' AND '". $end_date ."'";
					}
				}
				
				if(strlen($order_ids)>0){
					$sql .= " AND shop_order.ID IN ($order_ids)";
				}
				
				if($order_status  && $order_status != '-1' and $order_status != "'-1'")	$sql .= " AND shop_order.post_status IN (".$order_status.",'wc-refunded')";
					
				$sql .= " GROUP BY  group_column";
				
				$sql .= " ORDER BY order_column DESC";
				
				$items = $wpdb->get_results($sql);
				
				$return = array();
				
				foreach($items as $item_key => $item){
					$return[$item->group_column] = $item->order_total;
				}
				
				echo $wpdb->last_error;
				
				return $return;
		}
		
		
		function get_refunded_amount($order_items = '', $request = '', $type = '', $page = '', $report_name = '', $refund_type = 'all'){
				global $wpdb;
				
				extract($request);
				
				$sql 					= "";				
				
				$sql = "SELECT ";
				
				$sql .= " SUM(refund_amount.meta_value)  	AS refund_amount";
				
				$sql .= " ,shop_order_refund.ID  			AS refund_order_id";
				
				$sql .= " ,shop_order_refund.post_parent  	AS refunded_order_id";
				
				$group_sql = "";
				switch($group_by){
					case "order_id":
						$sql .= ", shop_order.ID as group_column";
						$sql .= ", shop_order.ID as order_column";
						break;
					case "refunded":
						$sql .= ", shop_order_refund.post_author as group_column";
						$sql .= ", shop_order_refund.post_author as order_column";
						break;
					case "daily":
						if($on_refund_date == 'yes'){
							$sql .= ", DATE(shop_order_refund.post_date) as group_column";
							$sql .= ", DATE(shop_order_refund.post_date) as group_date";
							$sql .= ", DATE(shop_order_refund.post_date) as order_column";
						}else{
							$sql .= ", DATE(shop_order.post_date) as group_column";
							$sql .= ", DATE(shop_order.post_date) as group_date";
							$sql .= ", DATE(shop_order.post_date) as order_column";
						}
						break;
					case "monthly":
						if($on_refund_date == 'yes'){
							$sql .= ", CONCAT(MONTHNAME(shop_order_refund.post_date) , ' ',YEAR(shop_order_refund.post_date)) as group_column";
							$sql .= ", DATE(shop_order_refund.post_date) as order_column";
						}else{
							$sql .= ", CONCAT(MONTHNAME(shop_order.post_date) , ' ',YEAR(shop_order.post_modified)) as group_column";
							$sql .= ", DATE(shop_order.post_date) as order_column";
						}
						break;
					case "yearly":
						if($on_refund_date == 'yes'){
							$sql .= ", YEAR(shop_order_refund.post_date)as group_column";
							$sql .= ", DATE(shop_order_refund.post_date) as order_column";
						}else{
							$sql .= ", YEAR(shop_order.post_date)as group_column";
							$sql .= ", DATE(shop_order.post_date) as order_column";
						}
						
						break;
					default:
						$sql .= ", shop_order.ID as group_column";
						$sql .= ", shop_order.ID as order_column";
						break;
					
				}
				
				$sql .= " FROM {$wpdb->prefix}posts as shop_order_refund";
				
				$sql .= " LEFT JOIN  {$wpdb->prefix}postmeta as refund_amount ON refund_amount.post_id	=	shop_order_refund.ID";
				
				$sql .= " LEFT JOIN  {$wpdb->prefix}posts as shop_order ON shop_order.ID	=	shop_order_refund.post_parent";
				
				$sql .= " WHERE shop_order_refund.post_type = 'shop_order_refund' AND  refund_amount.meta_key='_refund_amount'";
				
				
				if($refund_type == 'part_refunded'){
					$sql .= " AND shop_order.post_status NOT IN ('wc-refunded')";
				}else if($refund_type == 'full_refunded'){
					$sql .= " AND shop_order.post_status IN ('wc-refunded')";
				}
				
				$order_date_field_key == "post_date";
				if($on_refund_date == 'yes'){						
					if($order_date_field_key == "post_date" || $order_date_field_key == "post_modified"){
						if ($start_date != NULL &&  $end_date != NULL){
							$sql .= " AND DATE(shop_order_refund.{$order_date_field_key}) BETWEEN '".$start_date."' AND '". $end_date ."'";
						}
					}
				}else{
					if($order_date_field_key == "post_date" || $order_date_field_key == "post_modified"){
						if ($start_date != NULL &&  $end_date != NULL){
							$sql .= " AND DATE(shop_order.{$order_date_field_key}) BETWEEN '".$start_date."' AND '". $end_date ."'";
						}
					}
				}
				
				$sql .= " GROUP BY  group_column";
				
				$sql .= " ORDER BY order_column DESC";
				
				$items = $wpdb->get_results($sql);
				
				$return = array();
				foreach($items as $item_key => $item){
					$return[$item->group_column] = $item->refund_amount;
				}
				
				/*echo "<br>";
				echo $refund_type;
				echo "<br>";
				echo $sql;
				$this->print_array($items);
				$this->print_array($return);*/
				
				
				echo $wpdb->last_error;
				
				return $return;
		}
		
		function get_users_details($user_id_string = '',$display_name = 'display_name'){
			global $wpdb;
			$user_details = array();
			
			if(strlen($user_id_string) > 0){
				global $wpdb,$options;
				$sql = "SELECT display_name AS display_name, ID as customer_id, user_login as user_name";
				$sql .= " FROM {$wpdb->prefix}users as users ";
				
				//$sql .= " LEFT JOIN  {$wpdb->prefix}usermeta as first_name ON first_name.user_id = users.ID";
				$sql .= " WHERE 1*1 ";
				
				$sql .= " AND users.ID IN ({$user_id_string})";
				//$sql .= " AND first_name.meta_key='billing_first_name'";
				
				$users_details  = $wpdb->get_results($sql);
				$username		= array();
				//$this->print_array($users_details);
				//$this->print_sql($sql);
				
				if(count($users_details)>0){
					foreach($users_details as $key => $user){
						$username[$user->customer_id] = $user->$display_name;
					}
					
					$user_details['username'] = $username;
				}
				
				
			}
			
			return $user_details;
			
		}
		
		
		var $request_string = array();
		function get_string_multi_request($id=1,$string, $default = NULL){
			
			if(isset($this->request_string[$id])){
				$string = $this->request_string[$id];
			}else{
				if($string == "'-1'" || $string == "\'-1\'"  || $string == "-1" ||$string == "''" || strlen($string) <= 0)$string = $default;
				if(strlen($string) > 0 and $string != $default){ $string  		= "'".str_replace(",","','",$string)."'";}
				$this->request_string[$id] = $string;			
			}
			
			return $string;
		}
		
		
		
	}//End Class
}//End 
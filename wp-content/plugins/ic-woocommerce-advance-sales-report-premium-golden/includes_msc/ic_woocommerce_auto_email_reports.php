<?php
include_once('ic_woocommerce_auto_email_report_functions.php');
if(!class_exists('Ic_Wc_Auto_Email_Reports')){
	class Ic_Wc_Auto_Email_Reports extends Ic_Wc_Auto_Email_Report_Functions{
		
		/* variable declaration constants*/
		public $constants 				= array();	
		
		/**
			*Constructor
		*/
		public function __construct($constants = array()) {
			
			$this->constants  = $constants;
			
			$post_order_status = $this->set_saved_settings('post_order_status',array());
			
			if(count($post_order_status) > 0){
				$this->constants['post_order_status']  = "'". implode("','",$post_order_status)."'";
			}else{
				$this->constants['post_order_status']  = '';
			}
						
		}
		
		function get_total_sales($start_date=NULL,$end_date=NULL){
			global $wpdb;
			
			$sql = "";
			$sql .= " SELECT SUM(postmeta1.meta_value) AS order_total";
			$sql .= " FROM ";
			$sql .= " {$wpdb->prefix}posts as posts";
			$sql .= " LEFT JOIN {$wpdb->prefix}postmeta AS postmeta1 ON postmeta1.post_id = posts.ID";
			$sql .= " WHERE post_type='shop_order'";
			$sql .= " AND postmeta1.meta_key = '_order_total'";			
			
			if ($start_date && $end_date) {
				$sql .= " AND date_format(posts.post_date , '%Y-%m-%d') BETWEEN '{$start_date}' AND '{$end_date}'";
			}
			
			$post_order_status = $this->constants['post_order_status'];
			if($post_order_status){
				$sql .= " AND posts.post_status IN ({$post_order_status})";
			}
			
			$rows = $wpdb->get_var($sql);			
			return $rows;
		}
		
		function get_total_discount($start_date=NULL,$end_date=NULL){
			global $wpdb;
			
			$sql = "";
			$sql .= " SELECT SUM(postmeta1.meta_value) AS cart_discount";
			$sql .= " FROM ";
			$sql .= " {$wpdb->prefix}posts as posts";
			$sql .= " LEFT JOIN {$wpdb->prefix}postmeta AS postmeta1 ON postmeta1.post_id = posts.ID";
			$sql .= " WHERE post_type='shop_order'";
			$sql .= " AND postmeta1.meta_key = '_cart_discount'";			
			
			if ($start_date && $end_date) {
				$sql .= " AND date_format(posts.post_date , '%Y-%m-%d') BETWEEN '{$start_date}' AND '{$end_date}'";
			}
			
			$post_order_status = $this->constants['post_order_status'];
			if($post_order_status){
				$sql .= " AND posts.post_status IN ({$post_order_status})";
			}
			
			$rows = $wpdb->get_var($sql);
			//$this->print_array($rows);
			return $rows;
		}
		
		function get_total_order_tax($start_date=NULL,$end_date=NULL){
			global $wpdb;
			
			$sql = "";
			$sql .= " SELECT SUM(postmeta1.meta_value) AS order_tax";
			$sql .= " FROM ";
			$sql .= " {$wpdb->prefix}posts as posts";
			$sql .= " LEFT JOIN {$wpdb->prefix}postmeta AS postmeta1 ON postmeta1.post_id = posts.ID";
			$sql .= " WHERE post_type='shop_order'";
			$sql .= " AND postmeta1.meta_key = '_order_tax'";			
			
			if ($start_date && $end_date) {
				$sql .= " AND date_format(posts.post_date , '%Y-%m-%d') BETWEEN '{$start_date}' AND '{$end_date}'";
			}
			
			$post_order_status = $this->constants['post_order_status'];
			if($post_order_status){
				$sql .= " AND posts.post_status IN ({$post_order_status})";
			}
			
			$rows = $wpdb->get_var($sql);
			//$this->print_array($rows);
			return $rows;
		}
		
		function get_total_order_shipping_tax($start_date=NULL,$end_date=NULL){
			global $wpdb;
			
			$sql = "";
			$sql .= " SELECT SUM(postmeta1.meta_value) AS order_shipping_tax";
			$sql .= " FROM ";
			$sql .= " {$wpdb->prefix}posts as posts";
			$sql .= " LEFT JOIN {$wpdb->prefix}postmeta AS postmeta1 ON postmeta1.post_id = posts.ID";
			$sql .= " WHERE post_type='shop_order'";
			$sql .= " AND postmeta1.meta_key = '_order_shipping_tax'";			
			
			if ($start_date && $end_date) {
				$sql .= " AND date_format(posts.post_date , '%Y-%m-%d') BETWEEN '{$start_date}' AND '{$end_date}'";
			}
			
			$post_order_status = $this->constants['post_order_status'];
			if($post_order_status){
				$sql .= " AND posts.post_status IN ({$post_order_status})";
			}
			
			$rows = $wpdb->get_var($sql);
			//$this->print_array($rows);
			return $rows;
		}
		
		function get_total_cart_discount_tax($start_date=NULL,$end_date=NULL){
			global $wpdb;
			
			$sql = "";
			$sql .= " SELECT SUM(postmeta1.meta_value) AS cart_discount_tax";
			$sql .= " FROM ";
			$sql .= " {$wpdb->prefix}posts as posts";
			$sql .= " LEFT JOIN {$wpdb->prefix}postmeta AS postmeta1 ON postmeta1.post_id = posts.ID";
			$sql .= " WHERE post_type='shop_order'";
			$sql .= " AND postmeta1.meta_key = '_cart_discount_tax'";			
			
			if ($start_date && $end_date) {
				$sql .= " AND date_format(posts.post_date , '%Y-%m-%d') BETWEEN '{$start_date}' AND '{$end_date}'";
			}
			
			$post_order_status = $this->constants['post_order_status'];
			if($post_order_status){
				$sql .= " AND posts.post_status IN ({$post_order_status})";
			}
			
			$rows = $wpdb->get_var($sql);
			//$this->print_array($rows);
			return $rows;
		}
		
		function get_total_order_shipping($start_date=NULL,$end_date=NULL){
			global $wpdb;
			
			$sql = "";
			$sql .= " SELECT SUM(postmeta1.meta_value) AS order_shipping";
			$sql .= " FROM ";
			$sql .= " {$wpdb->prefix}posts as posts";
			$sql .= " LEFT JOIN {$wpdb->prefix}postmeta AS postmeta1 ON postmeta1.post_id = posts.ID";
			$sql .= " WHERE post_type='shop_order'";
			$sql .= " AND postmeta1.meta_key = '_order_shipping'";			
			
			if ($start_date && $end_date) {
				$sql .= " AND date_format(posts.post_date , '%Y-%m-%d') BETWEEN '{$start_date}' AND '{$end_date}'";
			}
			
			$post_order_status = $this->constants['post_order_status'];
			if($post_order_status){
				$sql .= " AND posts.post_status IN ({$post_order_status})";
			}
			
			$rows = $wpdb->get_var($sql);
			//$this->print_array($rows);
			return $rows;
		}
		
		function get_partial_refund($start_date=NULL,$end_date=NULL){
			global $wpdb;
			
			$sql = "";
			$sql .= " SELECT SUM(refund_amount.meta_value) AS refund_amount";
			$sql .= " FROM ";
			$sql .= " {$wpdb->prefix}posts as posts";
			$sql .= " LEFT JOIN {$wpdb->prefix}postmeta AS refund_amount ON refund_amount.post_id = posts.ID";
			$sql .= " WHERE post_type='shop_order_refund'";
			$sql .= " AND refund_amount.meta_key = '_refund_amount'";			
			
			if ($start_date && $end_date) {
				$sql .= " AND date_format(posts.post_date , '%Y-%m-%d') BETWEEN '{$start_date}' AND '{$end_date}'";
			}
			
			$post_order_status = $this->constants['post_order_status'];
			if($post_order_status){
				$sql .= " AND posts.post_status IN ({$post_order_status})";
			}
			
			$rows = $wpdb->get_var($sql);
			//$this->print_array($rows);
			return $rows;
				
		}
		
		function get_full_refund($start_date=NULL,$end_date=NULL){
			global $wpdb;
			
			$sql = "";
			$sql .= " SELECT SUM(order_total.meta_value) AS order_total";
			$sql .= " FROM ";
			$sql .= " {$wpdb->prefix}posts as posts";
			$sql .= " LEFT JOIN {$wpdb->prefix}postmeta AS order_total ON order_total.post_id = posts.ID";
			$sql .= " WHERE post_type = 'shop_order'";
			$sql .= " AND post_status = 'wc-refunded'";
			$sql .= " AND order_total.meta_key = '_order_total'";			
			
			if ($start_date && $end_date) {
				$sql .= " AND date_format(posts.post_date , '%Y-%m-%d') BETWEEN '{$start_date}' AND '{$end_date}'";
			}
			
			$post_order_status = $this->constants['post_order_status'];
			if($post_order_status){
				$sql .= " AND posts.post_status IN ({$post_order_status})";
			}
			
			$rows = $wpdb->get_var($sql);
			//$this->print_array($rows);
			return $rows;
				
		}
		
		function get_total_refund($start_date=NULL,$end_date=NULL){
			global $wpdb;
			
			$sql = "";
			$sql .= " SELECT SUM(order_total.meta_value) AS order_total";
			$sql .= " FROM ";
			$sql .= " {$wpdb->prefix}posts as posts";
			$sql .= " LEFT JOIN {$wpdb->prefix}postmeta AS order_total ON order_total.post_id = posts.ID";
			$sql .= " WHERE post_type = 'shop_order_refund'";
			//$sql .= " AND post_status = 'wc-refunded'";
			$sql .= " AND order_total.meta_key = '_order_total'";			
			
			if ($start_date && $end_date) {
				$sql .= " AND date_format(posts.post_date , '%Y-%m-%d') BETWEEN '{$start_date}' AND '{$end_date}'";
			}
			
			$post_order_status = $this->constants['post_order_status'];
			if($post_order_status){
				//$sql .= " AND posts.post_status IN ({$post_order_status})";
			}
			
			$rows = $wpdb->get_var($sql);
			//$this->print_array($rows);
			return $rows;
				
		}
		
		function get_total_profit($start_date=NULL,$end_date=NULL){
			global $wpdb;
			
			$sql = "";
			$sql .= " SELECT postmeta1.meta_value AS cog_order_total";
			$sql .= " FROM ";
			$sql .= " {$wpdb->prefix}posts as posts";
			$sql .= " LEFT JOIN {$wpdb->prefix}postmeta AS postmeta1 ON postmeta1.post_id = posts.ID";
			$sql .= " WHERE post_type='shop_order'";
			$sql .= " AND postmeta1.meta_key = '_ic_cogs_order_total'";			
			
			if ($start_date && $end_date) {
				$sql .= " AND date_format(posts.post_date , '%Y-%m-%d') BETWEEN '{$start_date}' AND '{$end_date}'";
			}
			
			$post_order_status = $this->constants['post_order_status'];
			if($post_order_status){
				$sql .= " AND posts.post_status IN ({$post_order_status})";
			}
			
			$rows = $wpdb->get_var($sql);
			//$this->print_array($rows);
			return $rows;
		}
		
		function get_top_customer($start_date=NULL,$end_date=NULL){
			global $wpdb;
			
			$sql = "";
			$sql .= " SELECT CONCAT(first_name.meta_value, ' ',last_name.meta_value) AS customer_name";
			$sql .= " ,billing_email.meta_value AS billing_email";
			$sql .= " ,SUM(order_total.meta_value) AS order_total";
			
			$sql .= " FROM ";
			$sql .= " {$wpdb->prefix}posts as posts";
			
			$sql .= " LEFT JOIN {$wpdb->prefix}postmeta AS first_name ON first_name.post_id = posts.ID";
			$sql .= " LEFT JOIN {$wpdb->prefix}postmeta AS last_name ON last_name.post_id = posts.ID";
			$sql .= " LEFT JOIN {$wpdb->prefix}postmeta AS billing_email ON billing_email.post_id = posts.ID";
			$sql .= " LEFT JOIN {$wpdb->prefix}postmeta AS order_total ON order_total.post_id = posts.ID";
			
			$sql .= " WHERE post_type='shop_order'";
			
			$sql .= " AND first_name.meta_key = '_billing_first_name'";
			$sql .= " AND last_name.meta_key = '_billing_last_name'";
			$sql .= " AND billing_email.meta_key = '_billing_email'";			
			$sql .= " AND order_total.meta_key = '_order_total'";
			
			if ($start_date && $end_date) {
				$sql .= " AND date_format(posts.post_date , '%Y-%m-%d') BETWEEN '{$start_date}' AND '{$end_date}'";
			}
			
			$post_order_status = $this->constants['post_order_status'];
			if($post_order_status){
				$sql .= " AND posts.post_status IN ({$post_order_status})";
			}
			
			$sql .= " GROUP BY billing_email";
			$sql .= " ORDER BY order_total DESC";
			
			$sql .= " LIMIT 5";
			
			
			$rows = $wpdb->get_results($sql);
			//$this->print_array($rows);
			return $rows;
		}
		
		
		function get_top_products($start_date=NULL,$end_date=NULL){
			global $wpdb;
			
			$sql = "";
			
			$sql .= " SELECT order_item_name";
			$sql .= " ,SUM(line_total.meta_value) AS line_total";
			$sql .= " FROM ";			
			$sql .= " {$wpdb->prefix}woocommerce_order_items as order_items";
			
			$sql .= " LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS line_total ON line_total.order_item_id = order_items.order_item_id";
			
			$sql .= " WHERE ";
			
			$sql .= " 1*1 ";
			$sql .= " AND shop_order.post_type = 'shop_order'";				
			$sql .= " AND order_item_type = 'line_item' ";
			$sql .= " AND line_total.meta_key = '_line_total' ";
			
			if ($start_date && $end_date) {
				$sql .= " AND date_format(posts.post_date , '%Y-%m-%d') BETWEEN '{$start_date}' AND '{$end_date}'";
			}
			
			$post_order_status = $this->constants['post_order_status'];
			if($post_order_status){
				$sql .= " AND posts.post_status IN ({$post_order_status})";
			}
			
			$sql .= " GROUP BY order_item_name";			
			$sql .= " ORDER BY line_total DESC";
			
			$sql .= " LIMIT 5";
			
			$rows = $wpdb->get_results($sql);
			//$this->print_array($rows);
			return $rows;
		}
		
		function get_current_month_top_products($start_date=NULL,$end_date=NULL){
			global $wpdb;
			
			$first_day_this_month = date('Y-m-01');
			$last_day_this_month  = date('Y-m-t');
						
			$sql = "";
			
			$sql .= " SELECT order_item_name";
			$sql .= " ,SUM(line_total.meta_value) AS line_total";
			$sql .= " FROM ";			
			$sql .= " {$wpdb->prefix}woocommerce_order_items AS order_items";
			
			$sql .= " LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta   AS line_total 	ON line_total.order_item_id = order_items.order_item_id";
			$sql .= " LEFT JOIN {$wpdb->prefix}posts 						AS shop_order 	ON shop_order.id			= order_items.order_id";
			
			$sql .= " WHERE ";
			
			$sql .= " 1*1 ";
			$sql .= " AND shop_order.post_type = 'shop_order'";				
			$sql .= " AND order_item_type = 'line_item' ";
			$sql .= " AND line_total.meta_key = '_line_total' ";
			
			if ($first_day_this_month && $last_day_this_month) {
				$sql .= " AND date_format(shop_order.post_date , '%Y-%m-%d') BETWEEN '{$first_day_this_month}' AND '{$last_day_this_month}'";
			}
			
			$post_order_status = $this->constants['post_order_status'];
			if($post_order_status){
				$sql .= " AND shop_order.post_status IN ({$post_order_status})";
			}
			
			$sql .= " GROUP BY order_item_name";			
			$sql .= " ORDER BY line_total DESC";
			
			$sql .= " LIMIT 5";
			
			$rows = $wpdb->get_results($sql);
			//$this->print_array($rows);
			return $rows;
		}
		
		function get_last_month_top_products($start_date=NULL,$end_date=NULL){
			global $wpdb;
			
			$current_month = date('m');
			$current_year = date('Y');
			//$lastmonth = $current_month - 1;
			$lastmonth = date('m', strtotime('-1 month')); 
						
			$firstdateoflastmonth= $current_year."-".$lastmonth."-"."01";			
			$lastdateofmonth=date('t',$lastmonth);						
			$lastdateoflastmonth = $current_year."-".$lastmonth."-".$lastdateofmonth;

			
			$sql = "";
			
			$sql .= " SELECT order_item_name";
			$sql .= " ,SUM(line_total.meta_value) AS line_total";
			$sql .= " FROM ";			
			$sql .= " {$wpdb->prefix}woocommerce_order_items AS order_items";
			
			$sql .= " LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta   AS line_total 	ON line_total.order_item_id = order_items.order_item_id";
			$sql .= " LEFT JOIN {$wpdb->prefix}posts 						AS shop_order 	ON shop_order.id			= order_items.order_id";
			
			$sql .= " WHERE ";
			
			$sql .= " 1*1 ";
			$sql .= " AND shop_order.post_type = 'shop_order'";				
			$sql .= " AND order_item_type = 'line_item' ";
			$sql .= " AND line_total.meta_key = '_line_total' ";
			
			if ($firstdateoflastmonth && $lastdateoflastmonth) {
				$sql .= " AND date_format(shop_order.post_date , '%Y-%m-%d') BETWEEN '{$firstdateoflastmonth}' AND '{$lastdateoflastmonth}'";
			}
			
			$post_order_status = $this->constants['post_order_status'];
			if($post_order_status){
				$sql .= " AND shop_order.post_status IN ({$post_order_status})";
			}
			
			$sql .= " GROUP BY order_item_name";			
			$sql .= " ORDER BY line_total DESC";
			
			$sql .= " LIMIT 5";
			
			$rows = $wpdb->get_results($sql);
			//$this->print_array($rows);
			return $rows;
		}
		
		function get_product_compare($start_date=NULL,$end_date=NULL, $top = 20){
			global $wpdb;
			
			$new_list    = array();
			$month_kies = array();
			$months 	  = array();
			$product_ids 	  = array();
			$product_list 	  = array();
			
			$sql = "";		
			$sql .= " SELECT order_item_name";
			$sql .= " ,product_id.meta_value 						AS product_id";
			$sql .= " ,variation_id.meta_value 						AS variation_id";
			$sql .= " ,SUM(line_total.meta_value) 					AS line_total";
			
			$sql .= " FROM ";			
			$sql .= " {$wpdb->prefix}woocommerce_order_items AS order_items";
			
			$sql .= " LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta   AS line_total 	ON line_total.order_item_id = order_items.order_item_id";
			$sql .= " LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta   AS product_id 	ON product_id.order_item_id = order_items.order_item_id";
			$sql .= " LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta   AS variation_id 	ON variation_id.order_item_id = order_items.order_item_id";
			$sql .= " LEFT JOIN {$wpdb->prefix}posts 						AS shop_order 	ON shop_order.id			= order_items.order_id";
			
			$sql .= " WHERE ";
			
			$sql .= " 1*1 ";
			$sql .= " AND shop_order.post_type = 'shop_order'";				
			$sql .= " AND order_item_type = 'line_item' ";
			$sql .= " AND line_total.meta_key = '_line_total' ";
			$sql .= " AND product_id.meta_key = '_product_id' ";
			$sql .= " AND variation_id.meta_key = '_variation_id' ";
			
			//$sql .= " AND variation_id.meta_value > 0 ";
			$start_date2 = date("Y-m-01",strtotime($end_date));
			if ($start_date && $end_date) {
				$sql .= " AND date_format(shop_order.post_date , '%Y-%m-%d') BETWEEN '{$start_date2}' AND '{$end_date}'";
			}			
			
			$post_order_status = $this->constants['post_order_status'];
			if($post_order_status){
				$sql .= " AND shop_order.post_status IN ({$post_order_status})";
			}
			
			$sql .= " GROUP BY product_id,variation_id";
			
			$sql .= " ORDER BY line_total DESC";
			
			if(!empty($top)){
				$sql .= " LIMIT {$top}";			
			}
			
			$rows = $wpdb->get_results($sql);
			foreach($rows as $key => $row){				
				$variation_id = $row->variation_id;
				$product_id = $row->product_id;
				$post_id = $variation_id > 0 ? $variation_id : $product_id;
				$product_ids[$post_id] = $post_id;
			}
			
			if(count($product_ids) > 0){			
				$sql = "";
				$sql .= " SELECT order_item_name";
				
				$sql .= " ,SUM(line_total.meta_value) 					AS line_total";
				$sql .= " ,product_id.meta_value 						AS product_id";
				$sql .= " ,variation_id.meta_value 						AS variation_id";
				$sql .= " ,SUM(product_qty.meta_value) 					AS product_qty";
				$sql .= " ,date_format(shop_order.post_date , '%Y-%m')  AS month_key";
				
				$sql .= " FROM ";			
				$sql .= " {$wpdb->prefix}woocommerce_order_items AS order_items";
				
				$sql .= " LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta   AS line_total 	ON line_total.order_item_id = order_items.order_item_id";
				$sql .= " LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta   AS product_id 	ON product_id.order_item_id = order_items.order_item_id";
				$sql .= " LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta   AS variation_id 	ON variation_id.order_item_id = order_items.order_item_id";
				$sql .= " LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta   AS product_qty 	ON product_qty.order_item_id = order_items.order_item_id";
				$sql .= " LEFT JOIN {$wpdb->prefix}posts 						AS shop_order 	ON shop_order.id			= order_items.order_id";
				
				$sql .= " WHERE ";
				
				$sql .= " 1*1 ";
				$sql .= " AND shop_order.post_type = 'shop_order'";				
				$sql .= " AND order_item_type = 'line_item' ";
				$sql .= " AND line_total.meta_key = '_line_total' ";
				$sql .= " AND product_id.meta_key = '_product_id' ";
				$sql .= " AND variation_id.meta_key = '_variation_id' ";
				$sql .= " AND product_qty.meta_key = '_qty' ";
				
				//$sql .= " AND variation_id.meta_value > 0 ";
				
				if ($start_date && $end_date) {
					$sql .= " AND date_format(shop_order.post_date , '%Y-%m-%d') BETWEEN '{$start_date}' AND '{$end_date}'";
				}
				
				if(count($product_ids)>0){
					$product_ids = implode(",",$product_ids);
					$sql .= " AND (product_id.meta_value IN ($product_ids) || variation_id.meta_value IN ($product_ids))";
				}
				
				$post_order_status = $this->constants['post_order_status'];
				if($post_order_status){
					$sql .= " AND shop_order.post_status IN ({$post_order_status})";
				}
				
				$sql .= " GROUP BY product_id, variation_id, month_key";			
				
				$sql .= " ORDER BY line_total DESC, month_key DESC";
				
				$rows = $wpdb->get_results($sql);
				
				//$this->print_array($rows);
				
				foreach($rows as $key => $row){
					$variation_id = $row->variation_id;
					$product_id = $row->product_id;
					$post_id = $variation_id > 0 ? $variation_id : $product_id;
					
					$new_list[$post_id][$row->month_key] = $row;
					$month_kies[$row->month_key] = $row->month_key;
					$product_list[$post_id] = $row->order_item_name;
				}
				
				foreach($month_kies as $month_key => $array_key){
					$months[] = $month_key;
				}
				
				rsort($months);
				
			}
			
			$rows = array();
			$rows['month_kies']   = $month_kies;
			$rows['product_list'] = $product_list;
			$rows['items'] 	    = $new_list;
			$rows['months'] 	   = $months;
			return $rows;
			
		}
		
		function get_product_monthly_sales_analysis($today_time = '',$start_date = '',$end_date = '', $top = 20, $template = '' ){
			$start_date	   = date("Y-m-01",strtotime("-1 month",$today_time));			
			$results  = $this->get_product_compare($start_date,$end_date,$top);			
			
			
			$columns = array();								
			$columns['order_item_name'] = __("Product Name","icwoocommerce_textdomains");
			
			$order_not_found = __('Product Not found.','icwoocommerce_textdomains');
			$output = $this->get_compare_grid($results,$columns,$order_not_found, $template);
			return $output;
		}
		
		
		
		
		
		function get_sold_products_html($today_time,$start_date,$end_date,$top = 20, $type = 'new'){
			
			$items = $this->get_sold_products(date_i18n('Y-m-01',$today_time),$end_date,$top,$type);
			$output = '';
			$columns = array();								
			$columns['order_item_name'] = __("Product Name","icwoocommerce_textdomains");
			$columns['quantity'] = __("Quantity","icwoocommerce_textdomains");
			$columns['line_total'] = __("Sales Amount","icwoocommerce_textdomains");
			
			$output = $this->get_grid($items,$columns);													
			return $output;
		}
		
		function get_sold_products($start_date=NULL,$end_date=NULL, $top = 20, $type = 'new'){
			global $wpdb;			
			
			$sql = "";		
			$sql .= " SELECT";			
			$sql .= " product_id.meta_value 						AS product_id";
			$sql .= " FROM ";			
			$sql .= " {$wpdb->prefix}woocommerce_order_items AS order_items";
			$sql .= " LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta   AS product_id 	ON product_id.order_item_id = order_items.order_item_id";			
			$sql .= " LEFT JOIN {$wpdb->prefix}posts 						AS shop_order 	ON shop_order.id			= order_items.order_id";			
			$sql .= " WHERE ";			
			$sql .= " 1*1 ";
			$sql .= " AND shop_order.post_type = 'shop_order'";				
			$sql .= " AND order_item_type = 'line_item' ";
			$sql .= " AND product_id.meta_key = '_product_id' ";
			$sql .= " AND product_id.meta_value > 0 ";			
			if ($start_date) {
				if($type == 'not_sold'){
					if ($start_date && $end_date) {
						$sql .= " AND date_format(shop_order.post_date , '%Y-%m-%d') BETWEEN '{$start_date}' AND '{$end_date}'";
					}
				}else{
					$sql .= " AND date_format(shop_order.post_date , '%Y-%m-%d') < '{$start_date}'";
				}
			}
			$post_order_status = $this->constants['post_order_status'];
			if($post_order_status){
				$sql .= " AND shop_order.post_status IN ({$post_order_status})";
			}
			$sql .= " GROUP BY product_id";			
			$query_product_not_in = $sql;
			
			$sql = "";		
			$sql .= " SELECT";			
			$sql .= " variation_id.meta_value 						AS variation_id";
			$sql .= " FROM ";			
			$sql .= " {$wpdb->prefix}woocommerce_order_items AS order_items";
			$sql .= " LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta   AS variation_id 	ON variation_id.order_item_id = order_items.order_item_id";			
			$sql .= " LEFT JOIN {$wpdb->prefix}posts 						AS shop_order 	ON shop_order.id			= order_items.order_id";			
			$sql .= " WHERE ";			
			$sql .= " 1*1 ";
			$sql .= " AND shop_order.post_type = 'shop_order'";				
			$sql .= " AND order_item_type = 'line_item' ";
			$sql .= " AND variation_id.meta_key = '_variation_id' ";
			$sql .= " AND variation_id.meta_value > 0 ";
			if ($start_date) {
				if($type == 'not_sold'){
					if ($start_date && $end_date) {
						$sql .= " AND date_format(shop_order.post_date , '%Y-%m-%d') BETWEEN '{$start_date}' AND '{$end_date}'";
					}
				}else{
					$sql .= " AND date_format(shop_order.post_date , '%Y-%m-%d') < '{$start_date}'";
				}
			}
			$post_order_status = $this->constants['post_order_status'];
			if($post_order_status){
				$sql .= " AND shop_order.post_status IN ({$post_order_status})";
			}
			
			$sql .= " GROUP BY variation_id";			
			$query_variation_not_in = $sql;
			
			$sql = "";		
			$sql .= " SELECT order_item_name";
			$sql .= " ,product_id.meta_value 						AS product_id";
			$sql .= " ,variation_id.meta_value 						AS variation_id";
			$sql .= " ,SUM(quantity.meta_value) 						AS quantity";
			$sql .= " ,SUM(line_total.meta_value) 					AS line_total";
			
			$sql .= " FROM ";			
			$sql .= " {$wpdb->prefix}woocommerce_order_items AS order_items";
			
			$sql .= " LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta   AS line_total 	ON line_total.order_item_id = order_items.order_item_id";
			$sql .= " LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta   AS product_id 	ON product_id.order_item_id = order_items.order_item_id";
			$sql .= " LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta   AS variation_id 	ON variation_id.order_item_id = order_items.order_item_id";
			$sql .= " LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta   AS quantity 	ON quantity.order_item_id = order_items.order_item_id";
			$sql .= " LEFT JOIN {$wpdb->prefix}posts 						AS shop_order 	ON shop_order.id			= order_items.order_id";
			
			$sql .= " WHERE ";
			
			$sql .= " 1*1 ";
			$sql .= " AND shop_order.post_type = 'shop_order'";				
			$sql .= " AND order_item_type = 'line_item' ";
			$sql .= " AND line_total.meta_key = '_line_total' ";
			$sql .= " AND product_id.meta_key = '_product_id' ";
			$sql .= " AND variation_id.meta_key = '_variation_id' ";
			$sql .= " AND quantity.meta_key = '_qty' ";
			
			if($type == 'new'){
				$sql .= " AND product_id.meta_value NOT IN ({$query_product_not_in})";
				$sql .= " AND variation_id.meta_value NOT IN ({$query_variation_not_in})";
			}else if($type == 'repeated'){
				
				$sql .= " AND ";
				$sql .= "(";
				$sql .= " product_id.meta_value IN ({$query_product_not_in})";
				$sql .= " OR variation_id.meta_value IN ({$query_variation_not_in})";
				$sql .= " )";
			}else if($type == 'not_sold'){
				$sql .= " AND product_id.meta_value NOT IN ({$query_product_not_in})";
				$sql .= " AND variation_id.meta_value NOT IN ({$query_variation_not_in})";
			}
			if($type == 'not_sold'){
				$end_date   = date_i18n("Y-m-t",strtotime('- 1 month',strtotime($start_date)));
				$start_date = date_i18n("Y-m-01",strtotime('- 1 month',strtotime($start_date)));
				if ($start_date && $end_date) {
					$sql .= " AND date_format(shop_order.post_date , '%Y-%m-%d') BETWEEN '{$start_date}' AND '{$end_date}'";
				}
			}else{
				if ($start_date && $end_date) {
					$sql .= " AND date_format(shop_order.post_date , '%Y-%m-%d') BETWEEN '{$start_date}' AND '{$end_date}'";
				}
			}
			
			$post_order_status = $this->constants['post_order_status'];
			if($post_order_status){
				$sql .= " AND shop_order.post_status IN ({$post_order_status})";
			}
			
			$sql .= " GROUP BY product_id,variation_id";
			
			$sql .= " ORDER BY line_total DESC";
			
			if(!empty($top)){
				$sql .= " LIMIT {$top}";			
			}
			
			$rows = $wpdb->get_results($sql);
			return $rows;
		}
		
		function get_customer_html($today_time,$start_date,$end_date,$top = 20, $type = 'new'){
			$items = $this->get_customer_data(date_i18n('Y-m-01',$today_time),$end_date,$top,$type);
			
			$output = '';
			$columns = array();								
			$columns['billing_email']   = __("Billing Email","icwoocommerce_textdomains");
			$columns['order_count']	 = __("Order Count","icwoocommerce_textdomains");
			$columns['order_total'] 	 = __("Order Total","icwoocommerce_textdomains");
			
			$output = $this->get_grid($items,$columns);													
			return $output;
		}
		
		function get_customer_data($start_date=NULL,$end_date=NULL, $top = 20, $type = 'new'){
			global $wpdb;
			$sql = "";		
			$sql .= " SELECT";			
			$sql .= " billing_email.meta_value 						AS billing_email";
			$sql .= " FROM ";			
			$sql .= " {$wpdb->prefix}posts AS shop_order";
			$sql .= " LEFT JOIN {$wpdb->prefix}postmeta   AS billing_email 	ON billing_email.post_id = shop_order.ID";
			$sql .= " WHERE ";			
			$sql .= " shop_order.post_type = 'shop_order' ";
			$sql .= " AND billing_email.meta_key = '_billing_email' ";
			if ($start_date) {
				if($type == 'not_sold'){
					if ($start_date && $end_date) {
						$sql .= " AND date_format(shop_order.post_date , '%Y-%m-%d') BETWEEN '{$start_date}' AND '{$end_date}'";
					}
				}else{
					$sql .= " AND date_format(shop_order.post_date , '%Y-%m-%d') < '{$start_date}'";
				}
			}
			$post_order_status = $this->constants['post_order_status'];
			if($post_order_status){
				$sql .= " AND shop_order.post_status IN ({$post_order_status})";
			}
			$sql .= " GROUP BY billing_email";			
			$query_billing_email_not_in = $sql;
			
			//$items = $wpdb->get_results($sql);
			//$this->print_array($items);
			
			
			$sql = "";		
			$sql .= " SELECT";			
			$sql .= " billing_email.meta_value 						AS billing_email";
			$sql .= " ,COUNT(*) 							AS order_count";
			$sql .= " ,SUM(ROUND(order_total.meta_value,2)) 		AS order_total";
			$sql .= " FROM ";			
			$sql .= " {$wpdb->prefix}posts AS shop_order";
			$sql .= " LEFT JOIN {$wpdb->prefix}postmeta   AS billing_email 	ON billing_email.post_id = shop_order.ID";
			$sql .= " LEFT JOIN {$wpdb->prefix}postmeta   AS order_total 	ON order_total.post_id = shop_order.ID";
			$sql .= " WHERE ";			
			$sql .= " shop_order.post_type = 'shop_order' ";
			$sql .= " AND billing_email.meta_key = '_billing_email' ";
			$sql .= " AND LENGTH(billing_email.meta_value) > 0";
			$sql .= " AND order_total.meta_key = '_order_total' ";
			
			if($type == 'new'){
				$sql .= " AND billing_email.meta_value NOT IN ({$query_billing_email_not_in})";
			}else if($type == 'repeated'){
				$sql .= " AND billing_email.meta_value IN ({$query_billing_email_not_in})";
			}else if($type == 'not_sold'){
				$sql .= " AND billing_email.meta_value NOT IN ({$query_billing_email_not_in})";
			}else{
				$sql .= " AND billing_email.meta_value NOT IN ({$query_billing_email_not_in})";
			}
			
			if($type == 'not_sold'){
				$end_date   = date_i18n("Y-m-t",strtotime('- 1 month',strtotime($start_date)));
				$start_date = date_i18n("Y-m-01",strtotime('- 1 month',strtotime($start_date)));
				if ($start_date && $end_date) {
					$sql .= " AND date_format(shop_order.post_date , '%Y-%m-%d') BETWEEN '{$start_date}' AND '{$end_date}'";
				}
			}else{
				if ($start_date && $end_date) {
					$sql .= " AND date_format(shop_order.post_date , '%Y-%m-%d') BETWEEN '{$start_date}' AND '{$end_date}'";
				}
			}
			
			$post_order_status = $this->constants['post_order_status'];
			if($post_order_status){
				$sql .= " AND shop_order.post_status IN ({$post_order_status})";
			}
			
			$sql .= " GROUP BY billing_email";
			
			$sql .= " ORDER BY order_total DESC";
			
			if(!empty($top)){
				$sql .= " LIMIT {$top}";			
			}
			
			$query_product_not_in = $sql;
			
			$items = $wpdb->get_results($sql);
			
			return $items;
			
		}
		
		function get_customer_monthly_sales_analysis($today_time = '',$start_date = '',$end_date = '', $top = 20, $template = ''){
			$start_date	   = date("Y-m-01",strtotime("-1 month",$today_time));			
			$results  = $this->get_customer_compare($start_date,$end_date,$top);
			$output  = "";
			
			
			$columns = array();								
			$columns['order_item_name'] = __("Billing Email","icwoocommerce_textdomains");
			
			
			$order_not_found = __('Customer Not found.','icwoocommerce_textdomains');
			$output = $this->get_compare_grid($results,$columns,$order_not_found,$template);
			return $output;
		}
		
		function get_customer_compare($start_date=NULL,$end_date=NULL, $top = 20){
			global $wpdb;
			
			$new_list    = array();
			$month_kies = array();
			$months 	  = array();
			$customer_list 	  = array();
			$billing_emails   = array();
			
			$sql = "";		
			$sql .= " SELECT";			
			$sql .= " billing_email.meta_value 						AS billing_email";
			$sql .= " FROM ";			
			$sql .= " {$wpdb->prefix}posts AS shop_order";
			$sql .= " LEFT JOIN {$wpdb->prefix}postmeta   AS billing_email 	ON billing_email.post_id = shop_order.ID";
			
			$sql .= " WHERE ";			
			$sql .= " shop_order.post_type = 'shop_order' ";
			$sql .= " AND billing_email.meta_key = '_billing_email' ";
			$sql .= " AND LENGTH(billing_email.meta_value) > 0";
			
			$start_date2 = date("Y-m-01",strtotime($end_date));
			if ($start_date2 && $end_date) {
				$sql .= " AND date_format(shop_order.post_date , '%Y-%m-%d') BETWEEN '{$start_date2}' AND '{$end_date}'";
			}
			
			$post_order_status = $this->constants['post_order_status'];
			if($post_order_status){
				$sql .= " AND shop_order.post_status IN ({$post_order_status})";
			}
			
			$sql .= " GROUP BY billing_email";
			
			$sql .= " ORDER BY billing_email ASC";
			
			if(!empty($top)){
				$sql .= " LIMIT {$top}";			
			}
			
			$rows = $wpdb->get_results($sql);
			foreach($rows as $key => $row){				
				$billing_email = $row->billing_email;
				$billing_emails[$billing_email] = $billing_email;
			}
			
			
			
			if(count($billing_emails) > 0){
				$sql = "";		
				$sql .= " SELECT";			
				$sql .= " billing_email.meta_value 						AS billing_email";
				$sql .= " ,COUNT(*) 									AS product_qty";
				$sql .= " ,SUM(ROUND(order_total.meta_value,2)) 		AS line_total";
				$sql .= " ,date_format(shop_order.post_date , '%Y-%m')  AS month_key";
				$sql .= " FROM ";			
				$sql .= " {$wpdb->prefix}posts AS shop_order";
				$sql .= " LEFT JOIN {$wpdb->prefix}postmeta   AS billing_email 	ON billing_email.post_id = shop_order.ID";
				$sql .= " LEFT JOIN {$wpdb->prefix}postmeta   AS order_total 	ON order_total.post_id = shop_order.ID";
				$sql .= " WHERE ";			
				$sql .= " shop_order.post_type = 'shop_order' ";
				$sql .= " AND billing_email.meta_key = '_billing_email' ";
				$sql .= " AND LENGTH(billing_email.meta_value) > 0";
				$sql .= " AND order_total.meta_key = '_order_total' ";
				
				if ($start_date && $end_date) {
					$sql .= " AND date_format(shop_order.post_date , '%Y-%m-%d') BETWEEN '{$start_date}' AND '{$end_date}'";
				}
				
				if(count($billing_emails)>0){
					$billing_emails = implode("','",$billing_emails);
					$sql .= " AND billing_email.meta_value IN ('{$billing_emails}')";
				}
				
				$post_order_status = $this->constants['post_order_status'];
				if($post_order_status){
					$sql .= " AND shop_order.post_status IN ({$post_order_status})";
				}
				
				$sql .= " GROUP BY billing_email, month_key";
				
				$sql .= " ORDER BY line_total DESC , month_key DESC";
				
				$items = $wpdb->get_results($sql);
				//$this->print_array($items);
				
				foreach($items as $key => $row){
					$billing_email = $row->billing_email;
					
					$new_list[$billing_email][$row->month_key] = $row;
					$month_kies[$row->month_key] = $row->month_key;
					$customer_list[$billing_email] = $billing_email;
				}
				
				foreach($month_kies as $month_key => $array_key){
					$months[] = $month_key;
				}
				
				rsort($months);
				
				
			}
			
			
			$rows = array();
			$rows['month_kies']   = $month_kies;
			$rows['product_list'] = $customer_list;
			$rows['items'] 	    = $new_list;
			$rows['months'] 	   = $months;
			
			
			
			return $rows;
		}
		
		function get_last_twelve_months_sales($today_time = '',$start_date = '',$end_date = '',$top = 20,$template = 'grid'){
			
			$items = $this->get_last_twelve_months_sales_data($today_time,$start_date,$end_date,$top,$template);
			
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
		
		function get_last_twelve_months_sales_data($today_time = '',$start_date = '',$end_date = '',$top = 20,$template = 'grid'){
			global $wpdb;
			
			if(!empty($top)){
				$top = $top - 1;			
				$end_date	   = date_i18n("Y-m-t");
				$start_date	 = date_i18n("Y-m-01", strtotime("-{$top} month",$today_time));	
			}else{
				$end_date	   = '';
				$start_date	 = '';	
			}
			
			//,'_order_currency'
			$meta_kies = array('_order_total','_order_tax','_order_shipping_tax','_order_shipping','_cart_discount_tax','_cart_discount');
			
			$sql = "";		
			$sql .= " SELECT";			
			
			$sql .= " COUNT(*) 										AS order_count";
			
			$sql .= " ,SUM(ROUND(report.meta_value,2)) 				AS meta_value";
			$sql .= " ,TRIM(LEADING '_' FROM report.meta_key)		AS meta_key";
			
			$sql .= " ,date_format(shop_order.post_date , '%Y-%m')  AS report_key";			
			$sql .= " ,order_currency.meta_value						AS order_currency";
			
			$sql .= " FROM ";			
			$sql .= " {$wpdb->prefix}posts AS shop_order";

			$sql .= " LEFT JOIN {$wpdb->prefix}postmeta   AS report 	ON report.post_id = shop_order.ID";
			$sql .= " LEFT JOIN {$wpdb->prefix}postmeta   AS order_currency 	ON order_currency.post_id = shop_order.ID";
			
			$sql .= " WHERE ";			
			$sql .= " shop_order.post_type = 'shop_order' ";
			if(count($meta_kies)>0){
				$meta_kies = implode("','",$meta_kies);
				$sql .= " AND report.meta_key IN ('{$meta_kies}')";
			}
			
			$sql .= " AND order_currency.meta_key IN ('_order_currency')";			
			
			if ($start_date && $end_date) {
				$sql .= " AND date_format(shop_order.post_date , '%Y-%m-%d') BETWEEN '{$start_date}' AND '{$end_date}'";
			}
			
			$post_order_status = $this->constants['post_order_status'];
			if($post_order_status){
				$sql .= " AND shop_order.post_status IN ({$post_order_status})";
			}
			
			$sql .= " GROUP BY  report_key, meta_key";
			
			$sql .= " ORDER BY report_key DESC";
			
			$items = $wpdb->get_results($sql);
			
			$refunds = $this->get_last_twelve_months_refund_data($today_time,$start_date,$end_date,$top,$template);
			
			$lists = array();
			foreach($items as $key => $item){
				$meta_key = isset($item->meta_key) ? $item->meta_key : '';
				$report_key = isset($item->report_key) ? $item->report_key : '';
				$order_currency = isset($item->order_currency) ? $item->order_currency : '';
				
				$report_key = $report_key . '-'.$order_currency;				
				if(!isset($lists[$report_key])){
					$lists[$report_key]['report_key'] 	 = $item->report_key;
					$lists[$report_key]['report_label']   = date_i18n("M Y",strtotime($item->report_key));
					$lists[$report_key]['order_count']    = $item->order_count;
					$lists[$report_key]['order_currency'] = $item->order_currency;
					
					$lists[$report_key]['order_total'] = 0;
					$lists[$report_key]['order_tax'] = 0;
					
					$lists[$report_key]['cart_discount'] = 0;
					$lists[$report_key]['cart_discount_tax'] = 0;
					
					$lists[$report_key]['order_shipping'] = 0;
					$lists[$report_key]['order_shipping_tax'] = 0;
				}
				
				if(isset($refund[$report_key][$meta_key])){					
					$refund = $refunds[$report_key][$meta_key];
					$lists[$report_key][$meta_key] = $item->meta_value - $refund;
				}else{
					$lists[$report_key][$meta_key] = $item->meta_value;
				}
				
				
			}
			
			$items = array();
			$i = 0;
			foreach($lists as $item_key => $item){
				$items[$i] = new stdClass();
				foreach($item as $key => $value){
					$items[$i]->$key = $value;
				}
				
				$i++;
			}
			
			
			
			return $items;
		}
		
		function get_last_twelve_months_refund_data($today_time = '',$start_date = '',$end_date = '',$top = 20,$template = 'grid'){
			global $wpdb;
			
			if(!empty($top)){
				$top = $top - 1;			
				$end_date	   = date_i18n("Y-m-t");
				$start_date	 = date_i18n("Y-m-01", strtotime("-{$top} month",$today_time));	
			}else{
				$end_date	   = '';
				$start_date	 = '';	
			}
			
			$meta_kies = array('_order_total','_order_tax','_order_shipping_tax','_order_shipping','_cart_discount_tax','_cart_discount');
			
			$sql = "";		
			$sql .= " SELECT";
			
			$sql .= " SUM(ROUND(TRIM(LEADING '-' FROM report.meta_value),2)) 				AS meta_value";
			$sql .= " ,TRIM(LEADING '_' FROM report.meta_key)		AS meta_key";
			
			$sql .= " ,date_format(shop_order.post_date , '%Y-%m')  AS report_key";			
			$sql .= " ,order_currency.meta_value						AS order_currency";
			
			$sql .= " FROM ";			
			$sql .= " {$wpdb->prefix}posts AS shop_order";

			$sql .= " LEFT JOIN {$wpdb->prefix}postmeta   AS report 	ON report.post_id = shop_order.ID";
			$sql .= " LEFT JOIN {$wpdb->prefix}postmeta   AS order_currency 	ON order_currency.post_id = shop_order.ID";
			
			$sql .= " WHERE ";			
			$sql .= " shop_order.post_type = 'shop_order_refund' ";
			if(count($meta_kies)>0){
				$meta_kies = implode("','",$meta_kies);
				$sql .= " AND report.meta_key IN ('{$meta_kies}')";
			}
			
			$sql .= " AND order_currency.meta_key IN ('_order_currency')";			
			
			if ($start_date && $end_date) {
				$sql .= " AND date_format(shop_order.post_date , '%Y-%m-%d') BETWEEN '{$start_date}' AND '{$end_date}'";
			}
			
			$post_order_status = $this->constants['post_order_status'];
			if($post_order_status){
				$sql .= " AND (shop_order.post_status IN ({$post_order_status}) OR shop_order.post_status IN ('wc-refunded'))";
			}
			
			$sql .= " GROUP BY  report_key, meta_key";
			
			$sql .= " ORDER BY report_key DESC";
			
			$items = $wpdb->get_results($sql);
			
			$lists = array();
			foreach($items as $key => $item){
				$meta_key = isset($item->meta_key) ? $item->meta_key : '';
				$report_key = isset($item->report_key) ? $item->report_key : '';
				$order_currency = isset($item->order_currency) ? $item->order_currency : '';
				
				$report_key = $report_key . '-'.$order_currency;				
				if(!isset($lists[$report_key])){
					$lists[$report_key]['order_total'] = 0;
					$lists[$report_key]['order_tax'] = 0;
					
					$lists[$report_key]['cart_discount'] = 0;
					$lists[$report_key]['cart_discount_tax'] = 0;
					
					$lists[$report_key]['order_shipping'] = 0;
					$lists[$report_key]['order_shipping_tax'] = 0;
				}
				
				$lists[$report_key][$meta_key] = $item->meta_value;
			}
			
			//$this->print_array($lists);
			return $lists;
		}
		
		function get_compare_grid($results = array(),$columns = array(),$order_not_found = '', $template = ''){

			
			$stlyle_table = $style_th_left = $style_th_right = $style_th_right2 = $style_td_left = $style_td_border_left = $style_td_right = $style_th_left2  ='';
			$stlyle_table = ' style="width:100%"';			
			$style_th_right = ' style="width:33%"';
			
			if($template == 'email'){
				$stlyle_table 		 = ' style="border:1px solid #dadada; border-bottom:0px; width:100%; font-family:arial;font-size:12px; color:#505050;" cellpadding="0" cellspacing="0"';
				
				$style_th_left 	    = ' style="background:#32BDB9; padding:7px 10px; font-weight:bold; font-size:14px; color:#fff; text-align:left;"';				
				$style_th_right 	   = ' style="background:#32BDB9; padding:7px 10px; font-weight:bold; font-size:14px; color:#fff; text-align:center; border-left: 1px solid #dadada;width:33%"';
				$style_th_right2      = ' style="background:#32BDB9; padding:7px 10px; font-weight:bold; font-size:14px; color:#fff; text-align:right; border-left: 1px solid #dadada;"';
				$style_th_left2 	   = ' style="background:#32BDB9; padding:7px 10px; font-weight:bold; font-size:14px; color:#fff; text-align:right;"';
				
				$style_td_left 	    = ' style="padding:7px 10px; font-size:14px; border-bottom:1px solid #E7E6E6;"';
				$style_td_border_left = ' style="padding:7px 10px; font-size:14px; border-bottom:1px solid #E7E6E6; text-align:right; border-left: 1px solid #dadada;"';
				$style_td_right 	   = ' style="padding:7px 10px; font-size:14px; border-bottom:1px solid #E7E6E6; text-align:right;"';
			}
			
			$month_kies 	   = $results['month_kies'];
			$product_list     = $results['product_list'];
			$months 		   = $results['months'];
			
			//$this->print_array($month_kies);
			$items 		 	= $results['items'];
			
			if(count($items) <= 0){
				return '<p>'.$order_not_found.'</p>';
			}
			
			$output = '<table class="widefat table-striped"'.$stlyle_table.'>';
				$output .= "</thead>";											
					
					$output .= "<tr>";
						foreach($columns as $column_key => $column_label){
							$output .= "<th rowspan=\"2\"{$style_th_left}>";
								$output .= $column_label;
							$output .= "</th>";
						}
						foreach($months as $array_key => $month_key){
							$output .= "<th colspan=\"3\" class=\"left_border align_right\"{$style_th_right}>";
								$output .= date_i18n('F, Y',strtotime($month_key));
							$output .= "</th>";
						}
					$output .= "</tr>";
					
					$output .= "<tr>";
						
						foreach($months as $array_key => $month_key){
							$output .= "<th class=\"left_border align_right\"{$style_th_right2}>";
								$output .= __('Amt.','icwoocommerce_textdomains');
							$output .= "</th>";
							$output .= "<th class=\"align_right\"{$style_th_left2}>";
								$output .= __('Qty.','icwoocommerce_textdomains');
							$output .= "</th>";
							$output .= "<th class=\"align_right\"{$style_th_left2}>";
								
							$output .= "</th>";
						}
					$output .= "</tr>";
					
				$output .= "</thead>";
				$output .= "<tbody>";
				foreach($product_list as $product_ld => $order_item_name){
					$last_total = 0;
					$output .= "<tr>";
						$output .= "<td{$style_td_left}>";
							$output .= $order_item_name;
						$output .= "</td>";
						$n = 1;
						$item = isset($items[$product_ld]) ? $items[$product_ld] : array();
						foreach($months as $array_key => $month_key){
							if($n == 1){
								$next_month_key 	 = isset($months[$n]) ? $months[$n] : '';
								$next_product_item  = isset($item[$next_month_key]) 			? $item[$next_month_key] : array();
								$next_line_total    = isset($next_product_item->line_total)         ? $next_product_item->line_total : 0;
							}
							
							$product_item  = isset($item[$month_key]) 			? $item[$month_key] : array();
							$line_total 	= isset($product_item->line_total)    ? $product_item->line_total : 0;
							$product_qty   = isset($product_item->product_qty)   ? $product_item->product_qty : 0;
							
							$output .= "<td class=\"left_border align_right\"{$style_td_border_left}>";
								$output .= ($line_total > 0 || $line_total<0) ? wc_price($line_total) : '';
							$output .= "</td>";
							$output .= "<td class=\"align_right\"{$style_td_right}>";
								$output .= ($product_qty > 0 || $product_qty<0) ? $product_qty : '';
							$output .= "</td>";
							$output .= "<td class=\"align_right\"{$style_td_right}>";
								
								if($n == 1 and $line_total != 0){
									if($next_line_total > $line_total){
										$per = $this->get_next_year_percentage($line_total,$next_line_total);
										$output .= '<span class="arrow-down-label">'.$per.'%</span> ';
										$output .= '<span class="arrow-down fa fa-long-arrow-down" aria-hidden="true">';
										$output .= ($template == 'email') ? '<span style="color:#F00">&darr;<span>' : '';
										$output .= '</span>';
									}
									
									if($next_line_total < $line_total){
										$per = $this->get_next_year_percentage($next_line_total,$line_total);
										$output .= '<span class="up-label">'.$per.'%</span> ';
										$output .= '<span class="up-arrow fa fa-long-arrow-up" aria-hidden="true">';
										$output .= ($template == 'email') ? '<span style="color:#060">&uarr;<span>' : '';
										$output .= '</span>';
									}
									
									if($next_line_total == $line_total){
										$per = $this->get_next_year_percentage($next_line_total,$line_total);
										$output .= '<span class="arrow-equal-label">'.$per.'%</span> ';
										$output .= '<span class="arrow-equal fa fa-arrows-v" aria-hidden="true">';
										$output .= ($template == 'email') ? '<span style="color:#060">&#8597;<span>' : '';
										$output .= '</span>';
									}
								}
								
								if($n == 2 and $line_total != 0){
									if($last_total > $line_total){
										$per = $this->get_next_year_percentage($line_total,$last_total);
										$output .= '<span class="arrow-down-label">'.$per.'%</span> ';
										$output .= '<span class="arrow-down fa fa-long-arrow-down" aria-hidden="true">';
										$output .= ($template == 'email') ? '<span style="color:#F00">&darr;<span>' : '';
										$output .= '</span>';
									}
									
									if($last_total < $line_total){
										$per = $this->get_next_year_percentage($last_total,$line_total);
										$output .= '<span class="arrow-up-labe">'.$per.'%</span> ';
										$output .= '<span class="arrow-up fa fa-long-arrow-up" aria-hidden="true">';
										$output .= ($template == 'email') ? '<span style="color:#060">&uarr;<span>' : '';
										$output .= '</span>';
									}
									
									if($last_total == $line_total){
										$per = $this->get_next_year_percentage($last_total,$line_total);
										$output .= '<span class="arrow-equal-label">'.$per.'%</span> ';
										$output .= '<span class="arrow-equal fa fa-arrows-v" aria-hidden="true">';
										$output .= ($template == 'email') ? '<span style="color:#060">&#8597;<span>' : '';
										$output .= '</span>';
										
									}
								}
								
								
								
							$output .= "</td>";
							$last_total = $line_total;
							$n++;
						}
					$output .= "</tr>";
				}
				$output .= "</tbody>";
				$output .= "</table>";										
			return $output;
		}
		
		function get_grid($items,$columns = array()){
				if(count($items) <= 0){
					return '<p>'.__('Order Not found.','icwoocommerce_textdomains').'</p>';
				}
				$output = '<table style="width:100%" class="widefat table-striped">';
					$output .= "</thead>";											
						
						$output .= "<tr>";;
							foreach($columns as $column_key => $column_label){
								$td_value = $column_label;
								$td_class = $column_key;
								switch($column_key){
									case "order_item_name":
										$td_value = $column_label;
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
										$td_class .= ' align_right';
										break;
									default:
								}
								
								$output .= "<th class=\"{$td_class}\">";
									$output .= $column_label;
								$output .= "</th>";
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
										case "billing_email":
										case "order_item_name":
											$td_value =isset($item->$column_key) ? $item->$column_key : '';
											break;
										case "order_count":
										case "quantity":
											$td_value = isset($item->$column_key) ? $item->$column_key : 0;
											$td_class .= ' align_right';
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
											$td_class .= ' align_right';
											break;
										default:
											$td_value =isset($item->$column_key) ? $item->$column_key : '';
											break;
									}
									
									$output .= "<td class=\"{$td_class}\">";
										$output .= $td_value;
									$output .= "</td>";
								}
							$output .= "</tr>";
						}
					$output .= "</tbody>";
				$output .= "</table>";
				return $output;
		}
		
		function set_saved_settings($setting_key, $default = ''){
			if(!isset($this->constants['settings'])){
				$plugin_key = isset($this->constants['parent_plugin_key']) ? $this->constants['parent_plugin_key'] : '';
				$this->constants['settings'] = get_option($plugin_key, array());
			}
			
			$settings = $this->constants['settings'];
			
			return isset($settings[$setting_key]) ? $settings[$setting_key] : $default;
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
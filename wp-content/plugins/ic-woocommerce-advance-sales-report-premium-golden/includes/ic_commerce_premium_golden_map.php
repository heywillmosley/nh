<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
//require_once('ic_commerce_premium_golden_fuctions.php');
if ( ! class_exists( 'IC_Commerce_Premium_Golden_Map' ) ) {
	//class IC_Commerce_Premium_Golden_Map extends IC_Commerce_Premium_Golden_Fuctions{
	class IC_Commerce_Premium_Golden_Map{
		
		public $constants 	=	array();
		
		public $parameters 	=	array();
		
		public function __construct($constants = array(), $parameters = array('shop_order_status'=>array(),'hide_order_status'=>array(),'start_date'=>NULL,'end_date'=>NULL)) {
			global $plugin_options;
			
			$this->constants	= $constants;
			$this->parameters	= $parameters;
			$plugin_options 	= $this->constants['plugin_options'];
		}
		
		function init(){
			$this->print_array($_REQUEST);		
		}
		
		function get_country_list($shop_order_status,$hide_order_status,$start_date,$end_date){
			
			$json_encode = $this->get_request('json_encode',0);
			global $wpdb;
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
			AND postmeta2.meta_key	=	'_billing_country'";
			
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
			Order By Total DESC";
			
			$order_items = $wpdb->get_results($sql);
			$country_list = array();
			$country_list_order_total = array();
			$country_list_order_count = array();
			
			
			foreach($order_items as $key => $value){
				$country_list_order_total[$value->BillingCountry] = number_format($value->Total, 2, '.', '');
			}
			
			$country_list = array();
			foreach($order_items as $key => $value){
				$country_list_order_count[$value->BillingCountry] = $value->OrderCount;
			}
			
			$country_list['total'] = $country_list_order_total;
			$country_list['count'] = $country_list_order_count;
			
			if($json_encode == 1){
				echo json_encode($country_list);
				die;
			}else{
				//echo json_encode($country_list);
				//die;
			}
			
			
			//return $order_items ;
		}
		
		public function get_request($name,$default = NULL,$set = false){
			if(isset($_REQUEST[$name])){
				$newRequest = $_REQUEST[$name];
				
				if(is_array($newRequest)){
					$newRequest = implode(",", $newRequest);
				}else{
					$newRequest = trim($newRequest);
				}
				
				if($set) $_REQUEST[$name] = $newRequest;
				
				return $newRequest;
			}else{
				if($set) 	$_REQUEST[$name] = $default;
				return $default;
			}
		}
	}
}

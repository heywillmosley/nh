<?php
//Last Modified Date 01 March, 2015
//Last Modified Date 13 April, 2015
//Last Modified Date 14 April, 2015
//Last Modified Date 14 May, 2015
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
if ( ! class_exists( 'IC_Commerce_Premium_Golden_Grid_Data' ) ) {
	class IC_Commerce_Premium_Golden_Grid_Data{
		
		public $constants 		=	array();
		
		public function __construct($constants) {
			$this->constants	= $constants;
		}//__construct
		
		function create_grid_items($columns = array(),$original_data = array()){
			
			//Default
			$id_list			= array();
			$master_list		= array();
			$return_items		= array();
			$id_list['wdc']		= $this->woocommerce_currency();
			
			//Create field
			foreach($columns as $key => $value):
				$td_width = "";				
				switch($key):
					case "billing_country":
					case "shipping_country":
					case "billing_country_name":
					case "shipping_country_name":
						$master_list['country']					= $this->get_wc_countries();//Added 20150225//20150216
						break;
					case "order_item_count"://Added 20150221
						$id_list['order_id']					= isset($id_list['order_id']) ? $id_list['order_id'] : $this->get_items_id_list($original_data,'order_id','','string');
						$master_list['order_item_count'] 		= $this->get_orders_items_count($id_list['order_id'],'line_item');
						break;
					case "shipping_method_title"://Added 20150221
						$id_list['order_id']					= isset($id_list['order_id']) ? $id_list['order_id'] : $this->get_items_id_list($original_data,'order_id','','string');
						$master_list['order_shipping_list'] 	= $this->order_item_name_list($id_list['order_id'],'shipping');
						break;
					case "tax_name"://Added 20150221
						$id_list['order_id']					= isset($id_list['order_id']) ? $id_list['order_id'] : $this->get_items_id_list($original_data,'order_id','','string');
						$master_list['order_tax_list']  		= $this->order_item_name_list($id_list['order_id'],'tax');
						break;
					case "order_coupon_codes"://Added 20150221
						$id_list['order_id']					= isset($id_list['order_id']) ? $id_list['order_id'] : $this->get_items_id_list($original_data,'order_id','','string');
						$master_list['order_coupon_codes']  	= $this->order_item_name_list($id_list['order_id'],'coupon');
						break;
					case "product_variation"://Added 20150221
						$id_list['order_id']					= isset($id_list['order_id']) ? $id_list['order_id'] : $this->get_items_id_list($original_data,'order_id','','string');
						$id_list['order_item_id']				= isset($id_list['order_item_id']) ? $id_list['order_item_id'] : $this->get_items_id_list($original_data,'order_item_id','','string');
						//$master_list['variation_list']  		= $this->get_variation_list($id_list['order_id'],0);
						$attributes 							= $this->get_variaiton_attributes('order_item_id','',$id_list['order_item_id']);
						$varation_string 						= isset($attributes['item_varation_string']) ? $attributes['item_varation_string'] : array();
						$master_list['variation_list']			= $varation_string;
						
						break;
					case "order_product_sku"://Added 20150221
						$id_list['order_id']					= isset($id_list['order_id']) 	? $id_list['order_id'] : $this->get_items_id_list($original_data,'order_id','','string');
						$id_list['product_id']					= isset($id_list['product_id']) ? $id_list['product_id'] : $this->get_items_id_list($original_data,'product_id','','string');
						//$master_list['order_product_sku']  		= $this->get_order_item_sku($id_list['order_id'],0);
						
						$master_list['variations']  			= $this->get_order_item_variation_details($id_list['order_id']);
						$master_list['products']  				= $this->get_order_item_product_details($id_list['order_id']);
						break;
					case "product_sku"://Added 20150227
					case "product_stock"://Added 20150227					
						$id_list['product_id']					= isset($id_list['product_id']) ? $id_list['product_id'] : $this->get_items_id_list($original_data,'product_id','','string');
						$master_list['products']  				= isset($master_list['products']) ? $master_list['products'] : $this->get_order_item_product_details('',$id_list['product_id'],array("sku","stock"));
						break;
					case "category_name"://Added 20150221
					case "product_categories"://Added 20150221
						$id_list['product_id']					= isset($id_list['product_id']) ? $id_list['product_id'] : $this->get_items_id_list($original_data,'product_id','','string');
						$master_list['product_cat']				= isset($master_list['product_cat']) ? $master_list['product_cat'] :$this->get_term_names_by_id($id_list['product_id'],"product_cat");
						break;
					case "order_status_name"://Added 20150223
						$id_list['order_id']					= isset($id_list['order_id']) ? $id_list['order_id'] : $this->get_items_id_list($original_data,'order_id','','string');
						$master_list['order_status_name']		= $this->get_term_names_by_id($id_list['order_id'],"order_status_name");
						break;
					case "order_status"://Added 20150221
					case "refund_status"://New Change ID 20150403
						$master_list['order_statuses']			= isset($master_list['order_statuses']) ? $master_list['order_statuses'] : $this->wc_get_order_statuses();
						break;
					case "customer_username"://New Change ID 20150227
					case "refund_user"://New Change ID 20150406
						$id_list['customer_user']				= isset($id_list['customer_user']) ? $id_list['customer_user'] : $this->get_items_id_list($original_data,'customer_user','','string');
						$master_list['user_details']			= isset($master_list['user_details']) ? $master_list['user_details'] : $this->get_users_details($id_list['customer_user']);
						break;
					case "part_order_refund_amount"://Added 20150221
					case "order_total"://Added 20150221
					case "order_tax"://Added 20170504
					case "gross_amount"://Added 20170504
						$id_list['order_id']						= isset($id_list['order_id']) ? $id_list['order_id'] : $this->get_items_id_list($original_data,'order_id','','string');
						$master_list['part_order_refund_amount'] 	= isset($master_list['part_order_refund_amount']) ? $master_list['part_order_refund_amount'] : $this->get_part_order_refund_amount($id_list['order_id']);
						break;
					
					default;
						$th_value = $value;
						break;
				endswitch;				
			endforeach;
			
			//$this->print_array($columns);
			//$this->print_array($original_data);
			//$this->print_array($master_list['part_order_refund_amount']);
			
			foreach($original_data as $rkey => $rvalue ):
				$order_item 			= $rvalue;
				$td_value 				= '';
				$order_id 				= isset($rvalue->order_id) 		? $rvalue->order_id 	: '';
				$product_id 			= isset($rvalue->product_id)	? $rvalue->product_id 	: '';
				$genericObject 			= new stdClass();
				$return_items[$rkey]	= $genericObject;
				
				foreach($columns as $key => $value):
					switch ($key) {
						case "billing_country"://20150216
							$billing_country	= isset($order_item->billing_country) ? $order_item->billing_country : '';
							$td_value 			= isset($master_list['country']->countries[$billing_country])  ? $master_list['country']->countries[$billing_country] : $billing_country;
							break;
						case "shipping_country"://20150216
							$shipping_country	= isset($order_item->shipping_country) ? $order_item->shipping_country : '';
							$td_value 			= isset($master_list['country']->countries[$shipping_country])  ? $master_list['country']->countries[$shipping_country] : $shipping_country;
							break;														
						case "billing_state"://20150216
							$billing_country	= isset($order_item->billing_country) ? $order_item->billing_country : '';
							$billing_state		= isset($order_item->billing_state) ? $order_item->billing_state : '';							
							$td_value 			=  $this->get_billling_state_name($billing_country,$billing_state);
							break;
						case "shipping_state"://20150216
							
							$shipping_country	= isset($order_item->shipping_country) ? $order_item->shipping_country : '';
							$shipping_state		= isset($order_item->shipping_state) ? $order_item->shipping_state : '';							
							$td_value 			=  $this->get_billling_state_name($shipping_country,$shipping_state);
							break;
						case "shipping_method_title"://20150216
							$td_value =  isset($order_item->shipping_method_title) ? $order_item->shipping_method_title :(isset($master_list['order_shipping_list'][$order_id]) ? $master_list['order_shipping_list'][$order_id] : '');//Added 20150221
							break;
						case "tax_name"://Added 20150221
							$td_value = isset($master_list['order_tax_list'][$order_id]) ? $master_list['order_tax_list'][$order_id] : '';
							break;
						case "order_coupon_codes"://Added 20150221														
							$td_value = isset($master_list['order_coupon_codes'][$order_id]) ? $master_list['order_coupon_codes'][$order_id] : '';
							break;
						case "order_item_count"://Added 20150221														
							$td_value = isset($master_list['order_item_count'][$order_id]) ? $master_list['order_item_count'][$order_id] : '';
							break;						
						case "order_product_sku":
							$variation_id = isset($order_item->variation_id) ? $order_item->variation_id : (isset($master_list['variations']['order_item_variation_id'][$order_item->order_item_id]) ? $master_list['variations']['order_item_variation_id'][$order_item->order_item_id] : 0);
							$return_items[$rkey]->variation_id 	= $variation_id;
							$td_value =  isset($master_list['variations']['variation_id_sku'][$variation_id]) ? $master_list['variations']['variation_id_sku'][$variation_id] : (isset($master_list['products']['product_id_sku'][$product_id]) ? $master_list['products']['product_id_sku'][$product_id] : 'Not Set');//Added 20150221
							break;
						
						case "product_sku":
							$td_value =  (isset($master_list['products']['product_id_sku'][$product_id]) ? $master_list['products']['product_id_sku'][$product_id] : 'Not Set');//Added 20150221
							break;
							
						case "product_stock":
							$td_value =  (isset($master_list['products']['product_id_stock'][$product_id]) ? $master_list['products']['product_id_stock'][$product_id] : 'Not Set');//Added 20150221
							break;
							
						case 'product_variation':
							$td_value =  isset($master_list['variation_list'][$order_item->order_item_id]['varation_string']) ? $master_list['variation_list'][$order_item->order_item_id]['varation_string'] : '';//Added 20150221
							break;
						case "order_status"://New Change ID 20150223
						case "refund_status"://New Change ID 20150403
							$status_key = isset($rvalue->$key) ? $rvalue->$key : 'pending';
							$td_value 	= isset($master_list['order_statuses'][$status_key]) ? $master_list['order_statuses'][$status_key] : '';
							//$order_items[$rkey]->order_status_key = $status_key;
							break;
						case "order_status_name":
							$td_value =  isset($master_list['order_status_name'][$order_item->order_id]) ? $master_list['order_status_name'][$order_item->order_id] : $this->get_category_name_by_product_id($order_item->order_id, 'shop_order_status');//Added 20150221
							break;
						case "product_categories"://Added 20150221
						case "category_name":
							$td_value =  isset($master_list['product_cat'][$order_item->product_id]) ? $master_list['product_cat'][$order_item->product_id] : $this->get_category_name_by_product_id($order_item->product_id, 'product_cat');//Added 20150221
							break;
						case "customer_username"://New Change ID 20150227
							$customer_id						=  isset($order_item->customer_user) ? $order_item->customer_user : '0';
							$return_items[$rkey]->customer_id 	= $customer_id;									
							$td_value 		=  isset($master_list['user_details']['username'][$customer_id]) ? $master_list['user_details']['username'][$customer_id] : 'Guest';//New Change ID 20150227
							break;
						case "refund_user"://New Change ID 20150227
							$customer_id						=  isset($order_item->customer_user) ? $order_item->customer_user : '0';
							$return_items[$rkey]->user_id 		= $customer_id;									
							$td_value 		=  isset($master_list['user_details']['username'][$customer_id]) ? $master_list['user_details']['username'][$customer_id] : 'Guest';//New Change ID 20150227
							break;
						case "customer_id"://New Change ID 20150227
							$td_value 		=  isset($order_item->customer_user) ? $order_item->customer_user : '0';
							break;
						case "part_order_refund_amount":
							$td_value =  isset($master_list['part_order_refund_amount'][$order_item->order_id]['refund_amount']) ? $master_list['part_order_refund_amount'][$order_item->order_id]['refund_amount'] : 0;//Added 20150406
							break;
							
						case "order_total":
							$order_total_amount		= isset($order_item->order_total)		? $order_item->order_total 		: 0;
							$part_order_refund_amount 	= isset($master_list['part_order_refund_amount'][$order_item->order_id]['refund_amount']) ? $master_list['part_order_refund_amount'][$order_item->order_id]['refund_amount'] : 0;//Modified 20170504
							$td_value 				= $order_total_amount - $part_order_refund_amount;
							break;
						case "order_tax"://Added 20170504
							$order_tax				= isset($order_item->order_tax)		? $order_item->order_tax 		: 0;
							$part_order_refund_tax 	= isset($master_list['part_order_refund_amount'][$order_item->order_id]['order_tax']) ? $master_list['part_order_refund_amount'][$order_item->order_id]['order_tax'] : 0;//Added 20170504
							$td_value 				= $order_tax + $part_order_refund_tax;
							$return_items[$rkey]->part_order_refund_tax = $part_order_refund_tax;
							break;
						case "gross_amount":
							
							$order_total_amount			= isset($order_item->order_total)		? $order_item->order_total 		: 0;
							$part_order_refund_amount 	= isset($master_list['part_order_refund_amount'][$order_item->order_id]['refund_amount']) ? $master_list['part_order_refund_amount'][$order_item->order_id]['refund_amount'] : 0;//Added 20150406
							$order_total				= $order_total_amount - $part_order_refund_amount;
							
							$order_tax					= isset($order_item->order_tax)		? $order_item->order_tax 		: 0;
							$part_order_refund_tax 		= isset($master_list['part_order_refund_amount'][$order_item->order_id]['order_tax']) ? $master_list['part_order_refund_amount'][$order_item->order_id]['order_tax'] : 0;//Added 20170504
							$order_tax					= $order_tax + $part_order_refund_tax;
							
							$order_shipping_tax			= isset($order_item->order_shipping_tax)		? $order_item->order_shipping_tax 		: 0;
							$order_shipping				= isset($order_item->order_shipping)			? $order_item->order_shipping 		: 0;
							$td_value 					= $order_total - $order_tax - $order_shipping_tax - $order_shipping	;
							
							break;							
						default:
							$td_value = isset($rvalue->$key) ? $rvalue->$key : '';
							break;
					}
					$return_items[$rkey]->$key 						= $td_value;					
					$return_items[$rkey]->order_total 				= isset($return_items[$rkey]->order_total) 		? $return_items[$rkey]->order_total 	: 0;
					$return_items[$rkey]->total_amount 				= isset($order_item->total_amount) 				? $order_item->total_amount 			: 0;
					$return_items[$rkey]->order_shipping 			= isset($order_item->order_shipping) 			? $order_item->order_shipping 			: 0;
					$return_items[$rkey]->order_item_id 			= isset($order_item->order_item_id) 			? $order_item->order_item_id 			: 0;
					$return_items[$rkey]->product_id 				= isset($order_item->product_id) 				? $order_item->product_id 				: 0;
					$return_items[$rkey]->order_id 					= isset($order_item->order_id) 					? $order_item->order_id 				: 0;
					$return_items[$rkey]->billing_country_code 		= isset($order_item->billing_country) 			? $order_item->billing_country 			: 0;
					$return_items[$rkey]->billing_state_code 		= isset($order_item->billing_state) 			? $order_item->billing_state 			: 0;										
					$return_items[$rkey]->order_currency 			= isset($order_item->order_currency) 			? $order_item->order_currency 			: $id_list['wdc'];
					
				endforeach;
			endforeach;
			
			//Assign null to your variables to clear the data, at least until the garbage collector gets ahold of it.
			$original_data = $master_list = $id_list = $columns = $td_width =  $order_item = $order_id = $product_id = NULL;
			$this->constants = $this->states_name = $this->country_states = $this->terms_by = $this->constants = NULL;
			
			//unset the variable pointer
			unset($original_data);unset($master_list);unset($id_list);unset($columns);unset($td_width);unset( $order_item);unset($order_id);unset($product_id);
			unset($this->states_name);unset($this->country_states);unset($this->terms_by);unset($this->constants);
			
			return $return_items;
		}
		
		function get_items_id_list($order_items = array(),$field_key = 'order_id', $return_default = '-1' , $return_formate = 'string'){
				$list 	= array();
				$string = $return_default;
				if(count($order_items) > 0){
					foreach ($order_items as $key => $order_item) {
						if(isset($order_item->$field_key))
							$list[] = $order_item->$field_key;
					}
					
					$list = array_unique($list);
					
					if($return_formate == "string"){
						$string = implode(",",$list);
					}else{
						$string = $list;
					}
				}
				return $string;
			}
			
			//Added 20150221
			function order_item_name_list($order_id_string = array(),$order_item_type = "tax"){
					global $wpdb;
					$item_name = array();
					if(is_array($order_id_string)){
						$order_id_string = implode(",",$order_id_string);
					}
					
					if(strlen($order_id_string) > 0){
						$sql = "SELECT
						woocommerce_order_items.order_id as order_id,
						woocommerce_order_items.order_item_name AS item_name
						FROM {$wpdb->prefix}woocommerce_order_items as woocommerce_order_items
						WHERE order_item_type ='{$order_item_type}' AND order_id IN ({$order_id_string})";
						$order_items = $this->get_results($sql);
						
						
						if(count($order_items) > 0){
							foreach($order_items as $key => $value){
								if(isset($item_name[$value->order_id]))
									$item_name[$value->order_id] = $item_name[$value->order_id].", " . $value->item_name;
								else
									$item_name[$value->order_id] = $value->item_name;
							}
						}
					}
				
					return $item_name;
			}
			
			//Added 20150221
			function get_orders_items_count($order_id_string = array(),$order_item_type = 'line_item'){
					global $wpdb;
					$item_name = array();
					if(is_array($order_id_string)){
						$order_id_string = implode(",",$order_id_string);
					}
					
					if(strlen($order_id_string) > 0){
						$sql = "SELECT woocommerce_order_items.order_id as order_id, COUNT(*) AS item_count FROM {$wpdb->prefix}woocommerce_order_items as woocommerce_order_items";
						$sql .= " WHERE order_item_type='{$order_item_type}'";
						$sql .= " AND order_id IN ({$order_id_string})";
						$sql .= " GROUP BY woocommerce_order_items.order_id";
						$sql .= " ORDER BY woocommerce_order_items.order_id DESC";
						
						$order_items = $this->get_results($sql);
						

						if(count($order_items) > 0){
							foreach($order_items as $key => $value){								
								$item_name[$value->order_id] = $value->item_count;
							}
						}
					}
					
					return $item_name;					
					//return $order_items_counts;
			}
			
			//Added 20150221
			
			function get_variation_list($order_id_string = array(), $order_item_id = 0){
					global $wpdb;
					
					$variations		= array();
					
					if(is_array($order_id_string)){
						$order_id_string = implode(",",$order_id_string);
					}
					
					if(strlen($order_id_string) > 0){					
						$sql = "
						SELECT
						woocommerce_order_items.order_item_id AS order_item_id,
						woocommerce_order_items.order_id AS order_id,
						postmeta_variation.meta_value AS product_variation,
						woocommerce_order_itemmeta.meta_value as variation_id
						FROM {$wpdb->prefix}woocommerce_order_items as woocommerce_order_items
						LEFT JOIN  {$wpdb->prefix}woocommerce_order_itemmeta as woocommerce_order_itemmeta ON woocommerce_order_itemmeta.order_item_id = woocommerce_order_items.order_item_id
						LEFT JOIN  {$wpdb->prefix}postmeta as postmeta_variation ON postmeta_variation.post_id = woocommerce_order_itemmeta.meta_value
						WHERE 1*1";
						
						if($order_item_id > 0){
							$sql .= "AND woocommerce_order_items.order_item_id={$order_item_id}";
						}
						
						$sql .= " AND order_id IN ({$order_id_string})";
						
						$sql .= "
						AND woocommerce_order_items.order_item_type = 'line_item'
						AND woocommerce_order_itemmeta.meta_key = '_variation_id'
						AND postmeta_variation.meta_key like 'attribute_%'";
						
						$order_items = $this->get_results($sql);
						$variation 		= array();
												
						if(count($order_items) > 0){
							
							foreach($order_items as $key=>$value){
								$variation[$value->order_item_id][] = $value->product_variation;
							}
							
							if(count($variation) > 0)
							foreach($variation as $key => $value){
								$variations[$key] = ucwords (implode(", ", $value));
							}
						}
					}					
					return $variations;
			}
			
			//Added Date 20150505
			function get_variaiton_attributes($variation_by = 'variation_id', $variation_ids = '', $order_item_ids = ''){
					
					global $wpdb;
					
					$new_attr 			= array();
					$attribute_keys 	= array();
					$attribute_labels 	= array();
					$return 			= array();
					$variations 		= array();
					
					$new_item_attr_variation_id		= array();
					$new_item_attr_order_item_id	= array();
					$order_item_variations			= array();
					
					
					$sql = "SELECT postmeta_product_addons.meta_value product_attributes FROM {$wpdb->prefix}posts AS posts";
					$sql .= " LEFT JOIN {$wpdb->prefix}postmeta AS postmeta_product_addons ON postmeta_product_addons.post_id = posts.ID";
					$sql .= " WHERE post_type in ('product')";
					$sql .= " AND postmeta_product_addons.meta_key IN ('_product_attributes') ";
					
					$product_addon_objects = $wpdb->get_results($sql);
					//$this->print_array($attributes);
					$product_addon_master = array();
					if(count($product_addon_objects)>0){					
						foreach($product_addon_objects as $key => $value){
							$product_addon_lists = unserialize($value->product_attributes);
							foreach($product_addon_lists as $key2 => $value2){
								$product_addon_master[] = $key2;
								//$attribute_keys2[]	= "wcv_".str_replace("pa_","",$key2);
							}
							//$this->print_array($product_addon_lists);
						}
					}
					
					$product_addon_master_key = "";
					if(count($product_addon_master)>0){
						$product_addon_master = array_unique($product_addon_master);
						sort($product_addon_master);
						
						$product_addon_master_key = implode("','", $product_addon_master);
					}
					
					$attribute_meta_key = $product_addon_master_key;
					
					/*global $wpdb;
					
					$sql = "SELECT TRIM(LEADING 'attribute_' FROM meta_key)  AS attribute_key  ";
					$sql .= " FROM {$wpdb->prefix}postmeta ";
					$sql .= " WHERE meta_key LIKE 'attribute%'";
					if($variation_ids){
						$sql .= " AND post_id IN ({$variation_ids})";
					}
					
					$sql .= " GROUP BY attribute_key ORDER BY attribute_key ASC";
					
					$attributes =  $wpdb->get_results($sql);
					
					//$this->print_array($attributes);
					
					$new_attr 			= array();
					$attribute_keys 	= array();
					$attribute_labels 	= array();
					$return 			= array();
					$variations 		= array();
					
					$new_item_attr_variation_id		= array();
					$new_item_attr_order_item_id	= array();
					$order_item_variations			= array();
					
					//return $new_attr;
					if($attributes){
						foreach($attributes as $key => $value){						
							$attribute_keys[]	= $value->attribute_key;
						}
					}
									
					//$this->print_array($attribute_keys);
					
					$attribute_keys = array_unique($attribute_keys);
					sort($attribute_keys);
					
					
					$attribute_meta_key = implode("', '",$attribute_keys);*/
					
					$sql = "SELECT TRIM(LEADING 'pa_' FROM woocommerce_order_itemmeta.meta_key) AS attribute_key, woocommerce_order_itemmeta.meta_value AS attribute_value, woocommerce_order_itemmeta.order_item_id, woocommerce_order_itemmeta.meta_key AS meta_key";
					if($variation_by == 'variation_id'){
						$sql .= ", woocommerce_order_itemmeta_variation_id.meta_value AS variation_id";
					}else{
						$sql .= ", 0 AS variation_id";
					}				
					
					$sql .= " FROM {$wpdb->prefix}woocommerce_order_itemmeta as woocommerce_order_itemmeta";
					if($variation_by == 'variation_id'){
						$sql .= " LEFT JOIN  {$wpdb->prefix}woocommerce_order_itemmeta as woocommerce_order_itemmeta_variation_id 			ON woocommerce_order_itemmeta_variation_id.order_item_id			=	woocommerce_order_itemmeta.order_item_id";
					}
					
					$sql .= " WHERE woocommerce_order_itemmeta.meta_key IN ('{$attribute_meta_key}')";
					
					if($variation_by == 'variation_id'){
						$sql .= " AND woocommerce_order_itemmeta_variation_id.meta_key 			= '_variation_id'";
						$sql .= " AND woocommerce_order_itemmeta_variation_id.meta_value > 0";
						//$sql .= " AND woocommerce_order_itemmeta_variation_id.meta_value = 4859";
						
						if($variation_ids){
							$sql .= " AND woocommerce_order_itemmeta_variation_id.meta_value IN ({$variation_ids})";
						}
					}
					
					if($order_item_ids){
						$sql .= " AND woocommerce_order_itemmeta.order_item_id IN ({$order_item_ids})";
					}
					
					
					
					$item_attributes =  $wpdb->get_results($sql);
					//$this->print_array($item_attributes);
					
					if($item_attributes){
						foreach($item_attributes as $key => $value){
							$attribute_key 		= strtolower($value->attribute_key);
							//$attribute_key 		= ucwords(str_replace("-"," ",$attribute_key));
							
							$attribute_value	= $value->attribute_value;
							$attribute_value 	= ucwords(str_replace("-"," ",$attribute_value));
												
							$new_item_attr_variation_id[$value->variation_id][$attribute_key] = $attribute_value;
							$new_item_attr_order_item_id[$value->order_item_id][$attribute_key] = $attribute_value;
							
							$attribute_labels[] = $attribute_key;
						}
					}
					
					$attribute_labels = array_unique($attribute_labels);
					sort($attribute_labels);
					
					//$this->print_array($new_item_attr_order_item_id);
					
					//By Variation ID
					if($variation_by == 'variation_id'){
						foreach($new_item_attr_variation_id as $id => $attribute_values){
							foreach($attribute_labels as $key2 => $value2){
								//$this->print_array($attribute_values);
								if(isset($attribute_values[$value2]))
									$new_item_attr_variation_id[$id]['varations'][] = $attribute_values[$value2];
							}
						}
						
						foreach($new_item_attr_variation_id as $id => $attribute_values){
							$new_item_attr_variation_id[$id]['varation_string'] 	= implode(", ",$attribute_values['varations']);					
							$variations[$id]['varation_string'] 					= implode(", ",$attribute_values['varations']);
						}
					}
					
					//$this->print_array($new_item_attr_variation_id);
					
					//By Order Item ID
					foreach($new_item_attr_order_item_id as $id => $attribute_values){
						foreach($attribute_labels as $key2 => $value2){
							//$this->print_array($attribute_values);
							if(isset($attribute_values[$value2]))
								$new_item_attr_order_item_id[$id]['varations'][] = $attribute_values[$value2];
						}
					}
					
					foreach($new_item_attr_order_item_id as $id => $attribute_values){
						$new_item_attr_order_item_id[$id]['varation_string'] 	= implode(", ",$attribute_values['varations']);					
						$order_item_variations[$id]['varation_string'] 		= implode(", ",$attribute_values['varations']);
					}
					
					//$this->print_array($order_item_variations);
					
					$return['attribute_keys']		= $attribute_keys;
					$return['variation_labels']		= $attribute_labels;
					$return['varation_string']		= $variations;
					$return['item_varation_string']	= $order_item_variations;
					$return['varation']				= $new_item_attr_variation_id;
					
					//$this->print_array($return);
					
					return $return;
			}
			
			function get_order_item_variation_details($order_id_string = '', $variation_product_id_string = ''){
				global $wpdb;
				
				if(is_array($order_id_string)){
					$order_id_string = implode(",",$order_id_string);
				}
				
				$sql = "
				SELECT 
				woocommerce_order_items.order_item_id AS order_item_id,
				woocommerce_order_items.order_id AS order_id,
				woocommerce_order_itemmeta.meta_value AS variation_id,
				postmeta_sku.meta_value AS variation_sku
				
				FROM {$wpdb->prefix}woocommerce_order_items as woocommerce_order_items
				LEFT JOIN  {$wpdb->prefix}woocommerce_order_itemmeta as woocommerce_order_itemmeta ON woocommerce_order_itemmeta.order_item_id = woocommerce_order_items.order_item_id
				LEFT JOIN  {$wpdb->prefix}postmeta as postmeta_sku ON postmeta_sku.post_id = woocommerce_order_itemmeta.meta_value				
				WHERE  1*1";
						
				
				if(strlen($variation_product_id_string) > 0){
					$sql .= "AND woocommerce_order_itemmeta.meta_value={$order_item_id}";
				}
				
				if(strlen($order_id_string) > 0){
					$sql .= " AND order_id IN ({$order_id_string})";
				}
				
				$sql .= "
				AND woocommerce_order_items.order_item_type = 'line_item'
				AND woocommerce_order_itemmeta.meta_key = '_variation_id'
				AND postmeta_sku.meta_key = '_sku'
				AND (LENGTH(postmeta_sku.meta_value) > 0 OR woocommerce_order_itemmeta.meta_value > 0)
				";
				
				$order_items 	= $this->get_results($sql);
				$sku			= array();
				$id				= array();
				$details		= array();
				
				if(count($order_items) > 0){
					foreach($order_items as $key => $value){
						$variation_sku 				= trim($value->variation_sku);
						
						if(strlen($variation_sku) > 0)
							$sku[$value->variation_id] 	= $variation_sku;

						$id[$value->order_item_id] 	= trim($value->variation_id);
					}
				}
				
				$details['variation_id_sku']				= $sku;
				$details['order_item_variation_id']			= $id;
				
				return $details;
			}
			
			function get_order_item_product_details($order_id_string = '', $product_id_string = '',$product_items = array("sku")){
				global $wpdb;
				
				if(is_array($order_id_string)){
					$order_id_string = implode(",",$order_id_string);
				}
				
				$sql = "
				SELECT 
				woocommerce_order_items.order_item_id AS order_item_id,
				woocommerce_order_items.order_id AS order_id,				
				woocommerce_order_itemmeta_products.meta_value AS product_id";
				
				foreach($product_items as $key => $value){
					$sql .= ", postmeta_product_{$value}.meta_value AS product_{$value}";
				}
				
				$sql .= " FROM {$wpdb->prefix}woocommerce_order_items as woocommerce_order_items
				LEFT JOIN  {$wpdb->prefix}woocommerce_order_itemmeta as woocommerce_order_itemmeta_products ON woocommerce_order_itemmeta_products.order_item_id = woocommerce_order_items.order_item_id";
				
				foreach($product_items as $key => $value){
					$sql .= " LEFT JOIN  {$wpdb->prefix}postmeta as postmeta_product_{$value} ON postmeta_product_{$value}.post_id = woocommerce_order_itemmeta_products.meta_value";
				}
				$sql .= " WHERE  1*1";
						
				if(strlen($product_id_string) > 0){
					$sql .= " AND woocommerce_order_itemmeta_products.meta_value IN ({$product_id_string})";
				}
				
				if(strlen($order_id_string) > 0){
					$sql .= " AND order_id IN ({$order_id_string})";
				}
				
				$sql .= " AND woocommerce_order_items.order_item_type = 'line_item'";
				$sql .= " AND woocommerce_order_itemmeta_products.meta_key = '_product_id'";
				foreach($product_items as $key => $value){
					$sql .= " AND postmeta_product_{$value}.meta_key = '_{$value}'";
				}
				
				//$sql .= " AND LENGTH(postmeta_product_sku.meta_value)> 0";
				
				$sql .= " GROUP BY woocommerce_order_itemmeta_products.meta_value";
				
				$order_items 	= $this->get_results($sql);
				$sku			= array();
				$stock			= array();
				$id				= array();
				$details		= array();
				
				//$this->print_sql($sql);
				//$this->print_array($order_items);
				
				if(count($order_items) > 0){
					foreach($order_items as $key => $value){
						if(isset($value->product_sku)){
							$product_sku			 = trim($value->product_sku);
							if($product_sku) $sku[$value->product_id] = $product_sku;
						}
						
						if(isset($value->product_stock)){
							$product_stock			 = trim($value->product_stock);
							if($product_stock) $stock[$value->product_id] = $product_stock;
						}					
						
					}
				}
				
				$details['product_id_sku']				= $sku;
				$details['product_id_stock']			= $stock;
				//$details['order_item_product_id']		= $id;
				
				return $details;				
			}
			
			function get_order_item_sku($order_id_string = array(), $order_item_id = 0){
				global $wpdb;
				
				$order_item_sku = array();
				
				$sql = "
				SELECT 
				woocommerce_order_items.order_item_id AS order_item_id,
				woocommerce_order_items.order_id AS order_id,
				woocommerce_order_itemmeta.meta_value AS variation_id,
				postmeta_sku.meta_value AS variation_sku
				
				FROM {$wpdb->prefix}woocommerce_order_items as woocommerce_order_items
				LEFT JOIN  {$wpdb->prefix}woocommerce_order_itemmeta as woocommerce_order_itemmeta ON woocommerce_order_itemmeta.order_item_id = woocommerce_order_items.order_item_id
				LEFT JOIN  {$wpdb->prefix}postmeta as postmeta_sku ON postmeta_sku.post_id = woocommerce_order_itemmeta.meta_value				
				WHERE  1*1";
						
				if($order_item_id > 0){
					$sql .= "AND woocommerce_order_items.order_item_id={$order_item_id}";
				}
				
				$sql .= " AND order_id IN ({$order_id_string})";
				$sql .= "
				AND woocommerce_order_items.order_item_type = 'line_item'
				AND woocommerce_order_itemmeta.meta_key = '_variation_id'
				AND postmeta_sku.meta_key = '_sku'
				AND LENGTH(postmeta_sku.meta_value) > 0
				";
				
				$order_items = $this->get_results($sql);
				
				$order_item_ids = array();
				if(count($order_items) > 0){
					foreach($order_items as $key => $value){
						$order_item_sku[$value->order_item_id] = trim($value->variation_sku);
						$order_item_ids[] = $value->order_item_id;
					}
				}
				
				$order_product_sku = $this->get_order_product_sku_list($order_id_string, 0);
				foreach($order_product_sku as $key => $value){
					$order_item_ids[] = $key;
				}
				
				$final_sku = '';
				foreach($order_item_ids as $key => $order_item_id){
					$final_sku[$order_item_id] = isset($order_item_sku[$order_item_id]) ? $order_item_sku[$order_item_id] : (isset($order_product_sku[$order_item_id]) ? $order_product_sku[$order_item_id] : '');
				}
				
				return $final_sku;
			}
			
			function get_order_product_sku_list($order_id_string = array(), $order_item_id = 0){
				global $wpdb;
				
				$order_product_sku = array();
				
				$sql = "
				SELECT 
				woocommerce_order_items.order_item_id AS order_item_id,
				woocommerce_order_items.order_id AS order_id,				
				woocommerce_order_itemmeta_products.meta_value AS product_id,
				postmeta_product_sku.meta_value AS product_sku
				
				
				FROM {$wpdb->prefix}woocommerce_order_items as woocommerce_order_items
				
				LEFT JOIN  {$wpdb->prefix}woocommerce_order_itemmeta as woocommerce_order_itemmeta_products ON woocommerce_order_itemmeta_products.order_item_id = woocommerce_order_items.order_item_id
				
				LEFT JOIN  {$wpdb->prefix}postmeta as postmeta_product_sku ON postmeta_product_sku.post_id = woocommerce_order_itemmeta_products.meta_value
				WHERE  1*1";
						
				if($order_item_id > 0){
					$sql .= "AND woocommerce_order_items.order_item_id={$order_item_id}";
				}
				
				$sql .= " AND order_id IN ({$order_id_string})";
				$sql .= "
				AND woocommerce_order_items.order_item_type = 'line_item'
				AND woocommerce_order_itemmeta_products.meta_key = '_product_id'				
				AND postmeta_product_sku.meta_key = '_sku'
				AND LENGTH(postmeta_product_sku.meta_value)> 0
				";
				
				$order_items = $this->get_results($sql);
				
				if(count($order_items) > 0){
					foreach($order_items as $key => $value){														
						$order_product_sku[$value->order_item_id] = trim($value->product_sku);
					}
				}
				
				return $order_product_sku;				
			}
			
			function get_term_names_by_id($order_id_string = array(),$taxonomy = "product_cat"){
				global $wpdb;
				
					$item_name = array();
					if(is_array($order_id_string)){
						$order_id_string = implode(",",$order_id_string);
					}
					
					if(strlen($order_id_string) > 0){						
						$sql = "SELECT posts.ID AS item_id, terms2.name as item_name
						FROM `{$wpdb->prefix}posts` AS posts
						LEFT JOIN  {$wpdb->prefix}term_relationships	as term_relationships2 	ON term_relationships2.object_id	=	posts.ID
						LEFT JOIN  {$wpdb->prefix}term_taxonomy			as term_taxonomy2 		ON term_taxonomy2.term_taxonomy_id	=	term_relationships2.term_taxonomy_id
						LEFT JOIN  {$wpdb->prefix}terms					as terms2 				ON terms2.term_id					=	term_taxonomy2.term_id
						WHERE term_taxonomy2.taxonomy = '{$taxonomy}'				
						";
						$sql .= " AND posts.ID IN ({$order_id_string})";
						$sql .= " ORDER BY terms2.name ASC, posts.ID ASC";
						
						$order_items = $this->get_results($sql);
						
						if(count($order_items) > 0){
							foreach($order_items as $key => $value){
								if(isset($item_name[$value->item_id]))
									$item_name[$value->item_id] = $item_name[$value->item_id].", " . $value->item_name;
								else
									$item_name[$value->item_id] = $value->item_name;
							}
						}
					}
					
					//$this->print_array($item_name);
					
					return $item_name;
			}
			
			function get_part_order_refund_amount($order_id_string = array()){
				global $wpdb;
				
					$item_name = array();
					if(is_array($order_id_string)){
						$order_id_string = implode(",",$order_id_string);
					}
					
					if(strlen($order_id_string) > 0){
					
						
						
						$sql = "SELECT
							posts.post_parent 				as order_id
							,SUM(postmeta.meta_value) 		as total_amount
							,SUM(order_tax.meta_value) 		as order_tax";
							
						
						$sql .= "
				
						FROM {$wpdb->prefix}posts as posts
										
						LEFT JOIN  {$wpdb->prefix}postmeta as postmeta ON postmeta.post_id	=	posts.ID
						
						LEFT JOIN  {$wpdb->prefix}postmeta as order_tax ON order_tax.post_id	=	posts.ID";
						
						$sql .= " LEFT JOIN  {$wpdb->prefix}posts as shop_order ON shop_order.ID	=	posts.post_parent";
						
						$sql .= " WHERE posts.post_type = 'shop_order_refund' AND  postmeta.meta_key='_refund_amount'";
						
						$sql .= " AND  order_tax.meta_key='_order_tax'";
						
						if(strlen($order_id_string) > 0){
							$sql .= "AND posts.post_parent IN ({$order_id_string})";
						}
						
						$sql .= "AND shop_order.post_status NOT IN ('wc-refunded')";
						
						$sql .= " GROUP BY  posts.post_parent";			
				
						$sql .= " ORDER BY posts.post_parent DESC";
						
						$order_items = $this->get_results($sql);
						
						//$this->print_array($order_items);
						
						//$this->print_sql($sql);
						
						if(count($order_items) > 0){
							foreach($order_items as $key => $value){
								if(isset($item_name[$value->order_id]['refund_amount'])){
									$item_name[$value->order_id]['refund_amount'] 	= $item_name[$value->order_id]['refund_amount'] + $value->total_amount;
									$item_name[$value->order_id]['order_tax'] 		= $item_name[$value->order_id]['order_tax'] + $value->order_tax;
								}else{
									$item_name[$value->order_id]['refund_amount'] 	= $value->total_amount;
									$item_name[$value->order_id]['order_tax'] 		= $value->order_tax;
								}
							}
						}
					}
					
					return $item_name;
			
			}
			
			function get_order_refund_amount($order_id_string = array()){
				global $wpdb;
				
					$item_name = array();
					if(is_array($order_id_string)){
						$order_id_string = implode(",",$order_id_string);
					}
					
					if(strlen($order_id_string) > 0){
					
						
						
						$sql = "SELECT
							posts.post_parent as order_id
							,SUM(postmeta.meta_value) 		as total_amount";
						
						$sql .= "
				
						FROM {$wpdb->prefix}posts as posts
										
						LEFT JOIN  {$wpdb->prefix}postmeta as postmeta ON postmeta.post_id	=	posts.ID";
						
						$sql .= " LEFT JOIN  {$wpdb->prefix}posts as shop_order ON shop_order.ID	=	posts.post_parent";
						
						$sql .= " WHERE posts.post_type = 'shop_order_refund' AND  postmeta.meta_key='_refund_amount'";
						
						if(strlen($order_id_string) > 0){
							$sql .= "AND posts.post_parent IN ({$order_id_string})";
						}
						
						$sql .= " GROUP BY  posts.post_parent";			
				
						$sql .= " ORDER BY posts.post_parent DESC";
						
						$order_items = $this->get_results($sql);
						
						//$this->print_array($order_items);
						
						if(count($order_items) > 0){
							foreach($order_items as $key => $value){
								if(isset($item_name[$value->order_id]))
									$item_name[$value->order_id] = $item_name[$value->order_id] + $value->total_amount;
								else
									$item_name[$value->order_id] = $value->total_amount;
							}
						}
					}
					
					return $item_name;
			
			}
			
			
			function wc_get_order_statuses(){
				if(function_exists('wc_get_order_statuses')){
					$order_statuses = wc_get_order_statuses();						
				}else{
					$order_statuses = array();
				}				
				$order_statuses['trash']	=	"Trash";
				
				return $order_statuses;
			}
			
			// New Change ID 20141107
			var $states_name = array();
			var $country_states = array();
			
			// New Change ID 20141107
			function get_billling_state_name($cc = NULL,$st = NULL){
				global $woocommerce;
				$state_code = $st;
				
				if(!$cc) return $state_code;
				
				if(isset($this->states_name[$cc][$st])){
					$state_code = $this->states_name[$cc][$st];				
				}else{
					
					if(isset($this->country_states[$cc])){
						$states = $this->country_states[$cc];
					}else{
						$states = $this->get_wc_states($cc);//Added 20150225
						$this->country_states[$cc] = $states;						
					}				
					
					if(is_array($states)){					
						$state_code = isset($states[$state_code]) ? $states[$state_code] : $state_code;
					}
					
					$this->states_name[$cc][$st] = $state_code;				
				}
				return $state_code;
			}
			
			var $terms_by = array();
			function get_category_name_by_product_id($id, $taxonomy = 'product_cat', $termkey = 'name'){
				$term_name ="";		
				$id = 156;
				
				if(!isset($this->terms_by[$taxonomy][$id])){
					$id			= (integer)$id;
					$terms		= get_the_terms($id, $taxonomy);
					$termlist	= array();
					if($terms and count($terms)>0){
						foreach ( $terms as $term ) {
								$termlist[] = isset($term->$termkey) ? $term->$termkey : '';
						}
						if(count($termlist)>0){
							$term_name =  implode( ', ', $termlist );
						}
					}
					$this->terms_by[$taxonomy][$id] = $term_name;				
				}else{				
					$term_name = $this->terms_by[$taxonomy][$id];
				}					
				return $term_name;
			}
			
			
			
			//Added 20150225
			function get_wc_countries(){
				return class_exists('WC_Countries') ? (new WC_Countries) : (object) array();
			}
			
			//Added 20150225
			function get_wc_states($country_code){
				global $woocommerce;
				return isset($woocommerce) ? $woocommerce->countries->get_states($country_code) : array();
			}
			
			function woocommerce_currency(){
				if(!isset($this->constants['woocommerce_currency'])){
					$this->constants['woocommerce_currency'] =  $currency = get_woocommerce_currency();
				}else{
					$currency  = $this->constants['woocommerce_currency'];
				}			
				return $currency;
			}
			
			//Added 20150226
			function get_results($sql_query = ""){
				global $wpdb;
				$wpdb->query("SET SQL_BIG_SELECTS=1");
				$results = $wpdb->get_results($sql_query);				
				
				
				if($wpdb->last_error){
					echo $wpdb->last_error;
					$this->print_sql($sql_query);
				}
				
				$wpdb->flush();
				return $results;
				
			}
			
			function print_array($ar = NULL,$display = true){
				if($ar){
					$output = "<pre>";
					$output .= print_r($ar,true);
					$output .= "</pre>";
					
					if($display){

						echo $output;
					}else{
						return $output;
					}
				}
			}
			
			//New Change ID 20140918
			function print_sql($string){			
				
				$string = str_replace("\t", "",$string);
				$string = str_replace("\r\n", "",$string);
				$string = str_replace("\n", "",$string);
				
				$string = str_replace("SELECT ", "\n\tSELECT \n\t",$string);
				//$string = str_replace(",", "\n\t,",$string);
				
				$string = str_replace("FROM", "\n\nFROM",$string);
				$string = str_replace("LEFT", "\n\tLEFT",$string);
				
				$string = str_replace("AND", "\r\n\tAND",$string);			
				$string = str_replace("WHERE", "\n\nWHERE",$string);
				
				$string = str_replace("LIMIT", "\nLIMIT",$string);
				$string = str_replace("ORDER", "\nORDER",$string);
				$string = str_replace("GROUP", "\nGROUP",$string);
				
				$new_str = "<pre>";
					$new_str .= $string;
				$new_str .= "</pre>";
				
				echo $new_str;
			}
			
			//New Change ID 20150227
			function get_users_details($user_id_string = '',$display_name = 'display_name'){
				
				$user_details = array();
				
				if(strlen($user_id_string) > 0){
					global $wpdb,$options;
					$sql = "SELECT display_name AS display_name, ID as customer_id, user_login as user_name";
					$sql .= " FROM {$wpdb->prefix}users as users ";
					
					//$sql .= " LEFT JOIN  {$wpdb->prefix}usermeta as first_name ON first_name.user_id = users.ID";
					$sql .= " WHERE 1*1 ";
					
					$sql .= " AND users.ID IN ({$user_id_string})";
					//$sql .= " AND first_name.meta_key='billing_first_name'";
					
					$users_details  = $this->get_results($sql);
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
		
	}//End Class
}//End class_exists check
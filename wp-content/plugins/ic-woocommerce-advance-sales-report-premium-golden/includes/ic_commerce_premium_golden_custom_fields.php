<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
if ( ! class_exists( 'IC_Commerce_Premium_Golden_Custom_fields' ) ) {
	class IC_Commerce_Premium_Golden_Custom_fields{
		
		public $constants 	=	array();
		
		public $states		= 	array();
		
		public $order_fee	= 	array();
		
		public $service_fee	= 	array();
		
		public function __construct($constants) {
			global $options;			
			$this->constants		= $constants;			
			$options				= $this->constants['plugin_options'];			
			
			add_action( 'admin_init', array( $this, 'delete_custom_fields'));			
			add_action( 'admin_notices', array( $this, 'admin_notices'));			
			
		}//__construct
		
		function admin_notices(){
			$message = '';			
			if(isset($_GET['delete_all_ic_commerce_custom_fields'])){
				global $wpdb;
				$deleted = $wpdb->query("DELETE FROM {$wpdb->prefix}postmeta WHERE meta_key LIKE '_ic_commerce_%'");
				$message .= '<div class="updated">';
				$message .= '<p>Successfully deleted all custom fields.</p>';
				$message .= '</div>';
				
			}			
			echo  $message;
		}
		
		function delete_custom_fields(){
			add_action( 'woocommerce_new_order_item', array($this, 'woocommerce_order_ic_commerce_fields_delete') ,10,3);
			add_action( 'woocommerce_before_delete_order_item', array($this, 'woocommerce_before_delete_order_item') ,100);			
			add_action( 'save_post', array($this, 'save_order'), 100, 1 );
		}
		
		function woocommerce_order_ic_commerce_fields_delete($item_id, $item, $order_id){
			global $wpdb;
			$wpdb->query("DELETE FROM {$wpdb->prefix}postmeta WHERE meta_key LIKE '_ic_commerce_%' AND post_id={$order_id}");
		}
		
		function woocommerce_before_delete_order_item($item_id){
			global $wpdb;	
			$sql = "SELECT order_id FROM {$wpdb->prefix}woocommerce_order_items as woocommerce_order_items WHERE woocommerce_order_items.order_item_id = {$item_id} LIMIT 1";
			$order_id = $wpdb->get_var($sql);
			$this->woocommerce_order_ic_commerce_fields_delete($item_id,0,$order_id);
		}
		
		
		function save_order($order_id){	
		
			if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
			
			if(isset($_POST['post_type']) and $_POST['post_type'] == 'shop_order'){
				$this->woocommerce_order_ic_commerce_fields_delete(0,0,$order_id);
				$this->woocommerce_order_ic_commerce_fields_add($order_id);
			}
		}
		
		function woocommerce_order_ic_commerce_fields_add($order_id = 0){
				$columns = array(					
					// "ic_commerce_order_billing_name"			=> "Name"
					//"ic_commerce_order_status_name"			=> "Status"
					/*
					"ic_commerce_order_tax_name"				=> "Tax Name"
					,"ic_commerce_order_coupon_codes"			=> "Coupon Code"
					,"ic_commerce_order_item_count"				=> "Items"
					*/
					//,"ic_commerce_order_billing_sate"			=> "Billing State"
					//,"ic_commerce_order_shipping_sate"		=> "Shipping State"
					//,"ic_commerce_order_billing_country"		=> "billing_country"
					//,"ic_commerce_order_shipping_country"		=> "shipping_country"
					//,"ic_commerce_order_shipping_method"		=> "shipping_method"
				);
				
				//Commented 20150216
				
				global $wpdb;
				$sql = "SELECT ";					
				$sql .= " posts.ID AS order_id, DATE_FORMAT(posts.post_date,'%m/%d/%Y') AS order_date";
				$sql .= " FROM {$wpdb->prefix}posts as posts";
				$sql .= " WHERE  posts.post_type='shop_order' AND posts.ID = {$order_id}";
				$sql .= " GROUP BY posts.ID";
				
				$sql .= " Order By posts.post_date DESC ";
				$sql .= " LIMIT 1";
				$wpdb->query("SET SQL_BIG_SELECTS=1");
				$order_items = $wpdb->get_results($sql);
				
				if(count($order_items)>0){
					$order_meta = array();
					foreach ( $order_items as $key => $order_item ) {
							$order_id								= $order_item->order_id;
							
							if(!isset($order_meta[$order_id])){
								$order_meta[$order_id]					= $this->get_all_post_meta($order_id);
							}
							
							foreach($order_meta[$order_id] as $k => $v){
								$order_items[$key]->$k			= $v;
							}
							
							$order_items[$key]->billing_name	= $order_items[$key]->billing_first_name.' '.$order_items[$key]->billing_last_name;
							$order_items[$key]->gross_amount 	= ($order_items[$key]->order_total + $order_items[$key]->order_discount ) - ($order_items[$key]->order_shipping +  $order_items[$key]->order_shipping_tax + $order_items[$key]->order_tax );
					}
					
					foreach ( $order_items as $key => $order_item ) {
						foreach($columns as $key => $value):							
							$td_value = isset($order_item->$key) ? $order_item->$key : $this->get_custom_field_data($order_item,$key);									
						endforeach;
					}					
				}
				//$this->print_array($order_items);
				//die;
		}
		
		
		
		
		
		function get_custom_field_data($order_item = 0, $meta_key = NULL, $default = NULL ){
			global $country;
			if(isset($order_item->order_id)){
				$order_id 	= $order_item->order_id;
				$value 		= "";
				$id 		= $order_id; 
				switch($meta_key){
					case "ic_commerce_order_billing_name":
						$value 		= ucwords(stripslashes_deep($order_item->billing_first_name.' ' . $order_item->billing_last_name));
						break;
						
					case "ic_commerce_order_shipping_name":
						$value 		= ucwords(stripslashes_deep($order_item->shipping_first_name.' ' . $order_item->shipping_last_name));
						break;
						
					case "ic_commerce_order_item_count":
						$value 		= $this->get_order_items_count($order_id,'line_item');					
						break;
						
					case "ic_commerce_order_status_id":
						$value 		= $this->get_terms_by($order_id, 'shop_order_status','term_id');					
						break;
						
					case "ic_commerce_order_status_name":
					case "order_status":
						if($this->constants['post_order_status_found'] == 0 ){					
							$value 		= $this->get_terms_by($order_id, 'shop_order_status','name');
						}else{
							$value 		= $this->ic_get_order_statuses($order_item);
						}
						
						break;
					
						
					case "ic_commerce_order_coupon_codes":
						$value 		= $this->get_coupons($order_id);					
						break;
						
					case "ic_commerce_order_tax_name":
						$order_tax  = $this->order_tax($order_id);
						$value		= isset($order_tax->tax_name) ? $order_tax->tax_name : '';
						break;
					
					case "ic_commerce_order_billing_country":
						$bc			= isset($order_item->billing_country) ? $order_item->billing_country : NULL;
						$value		= $this->country_name($bc);
						break;
						
					case "ic_commerce_order_shipping_country":
						$sc			= isset($order_item->shipping_country) ? $order_item->shipping_country : NULL;
						$value		= $this->country_name($sc);					
						break;
					
					case "ic_commerce_order_billing_sate":				
						$bc			= isset($order_item->billing_country) ? $order_item->billing_country : NULL;
						$bs			= isset($order_item->billing_state) ? $order_item->billing_state : '';
						$value		= $this->get_state($bc, $bs);					
						break;
						
					case "ic_commerce_order_shipping_sate":
						$sc			= isset($order_item->shipping_country) ? $order_item->shipping_country : NULL;
						$ss			= isset($order_item->shipping_state) ? $order_item->shipping_state : '';
						$value		= $this->get_state($sc, $ss);					
						break;
						
					case "ic_commerce_order_gross_amount":
					case "ic_commerce_order_gross_amount_symbol":
						$value		= ($order_item->order_total+ $order_item->order_discount) - ( $order_item->order_tax + $order_item->order_shipping + $order_item->order_shipping_tax);
						$value		= $meta_key."_symbol" == "ic_commerce_order_gross_amount_symbol" ? $this->price($value) : $value;					
						break;
					
					case "ic_commerce_product_category_name":
						$value 		= $this->get_terms_by($order_item->product_id, 'product_cat','name');
						$id 		= $order_item->product_id;
						break;				
					case 'ic_commerce_product_product_variation':
						$value 		= $this->get_variation($order_item->order_item_id);
						$id 		= $order_item->product_id;
						break;	
					case 'ic_commerce_order_shipping_method':
						$value		= isset($order_item->shipping_method_title) ? $order_item->shipping_method_title : $this->order_item_name($order_id,'shipping');
						break;		
					case 'ic_commerce_order_payment_method':
						$value		= isset($order_item->payment_method_title) ? $order_item->payment_method_title : '';
						break;		
					case 'ic_commerce_order_currency':
						$value		= isset($order_item->order_currency) ? $order_item->order_currency : '';
						break;		
					default:
						$value 		= $default;					
						break;
				}
				//echo $meta_key;
				add_post_meta($order_id,'_'.$meta_key,$value);			
				//delete_post_meta($id,'_'.$meta_key);
			}
			return $value;
		}
		
		var $order_items_counts = array();
		function get_order_items_count($order_id = 0,$order_item_type = 'line_item'){
			global $wpdb;
			
			if(!isset($this->order_items_counts[$order_id])){
				$sql = "SELECT COUNT(*) FROM {$wpdb->prefix}woocommerce_order_items as woocommerce_order_items";
				$sql .= " WHERE order_item_type='{$order_item_type}'";
				if($order_id != NULL and $order_id !=0 and $order_id != '-1') $sql .= " AND order_id={$order_id}";
				
				$order_items_counts = $wpdb->get_var($sql);
				/*if($order_id != NULL and $order_id !=0 and $order_id != '-1'){
					add_post_meta($order_id,'_'.$meta_key,$order_items_count);
				}*/
				
				if(!$order_items_counts) $order_items_counts = 0;
				$this->order_items_counts[$order_id] = $order_items_counts;
				
			}else{
				echo $order_items_counts = $this->order_items_counts[$order_id];	
			}
			
			return $order_items_counts;
		}
		
		//New Change ID 20140918
		var $terms_by = array();
		function get_terms_by($id, $taxonomy = 'product_cat', $termkey = 'term_id'){			
			$term_name ="";			
			if(!isset($this->terms_by[$taxonomy][$id])){
				$id			= (integer)$id;
				$terms		= get_the_terms($id, $taxonomy);
				$termlist	= array();
				if($terms and count($terms)>0){
					foreach ( $terms as $term ) {
							$termlist[] = $term->$termkey;
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
		
		var $coupons_codes = array();
		function get_coupons($order_id = 0, $all = false){
			global $wpdb;
			
			if($order_id == 0 and $all == false) return '';
			
			$coupons_code = '';
			
			if(!isset($this->coupons_codes[$order_id])){
				$sql	= "SELECT * FROM {$wpdb->prefix}woocommerce_order_items as woocommerce_order_items WHERE woocommerce_order_items.order_item_type = 'coupon'";
				if($order_id > 0) $sql	.= " AND woocommerce_order_items.order_id={$order_id}";
				
				$order_items = $wpdb->get_results($sql);
				$coupons_code = "";
				if($order_items){
					$coupons = array();
					foreach($order_items as $key => $value){
						$coupons[] = $value->order_item_name;
					}
					if(count($coupons)>0){
						$coupons_code = implode($coupons,", ");
					}
				}
				
				$this->coupons_codes[$order_id] = $coupons_code;
			}else{
				$coupons_code = $this->coupons_codes[$order_id];
			}
			return $coupons_code;
			
		}
		
		var $order_items = array();
		function order_tax($order_id){
			if(!isset($this->order_items[$order_id])){
				global $wpdb;			
				$sql = "SELECT 
				 woocommerce_order_items.order_item_name AS tax_name
				,woocommerce_order_itemmeta1.meta_value order_tax_amount
				,woocommerce_order_itemmeta2.meta_value shipping_tax_amount
				
				FROM {$wpdb->prefix}woocommerce_order_items as woocommerce_order_items 
				
				LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta as woocommerce_order_itemmeta1  ON woocommerce_order_itemmeta1.order_item_id = woocommerce_order_items.order_item_id
				LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta as woocommerce_order_itemmeta2  ON woocommerce_order_itemmeta2.order_item_id = woocommerce_order_items.order_item_id
				
				WHERE order_item_type ='tax' AND order_id='{$order_id}' 
				AND woocommerce_order_itemmeta1.meta_key = 'tax_amount' 
				AND woocommerce_order_itemmeta2.meta_key = 'shipping_tax_amount'";
				$order_items = $wpdb->get_row($sql);				
				$this->order_items[$order_id] = $order_items;
			}else{
				$order_items = $this->order_items[$order_id];
			}
			
			return $order_items;
		}
		
		function country_name($country_code = NULL){
			if($country_code == NULL) return '';
			$country      = $this->get_wc_countries();//Added 20150225
			$country_name =  isset($country->countries[$country_code]) ? $country->countries[$country_code] : $country_code;
			return $country_name;
		}
		
		//Added 20150225
		function get_wc_countries(){
			return class_exists('WC_Countries') ? (new WC_Countries) : (object) array();
		}
		
		function get_state($cc = NULL,$st = NULL){
			global $woocommerce;
			if($cc == NULL) return $st;
			if($st == NULL) return '';
			$ccst = $cc."_".$st;
			
			if(isset($this->states[$ccst])){
				$st = $this->states[$ccst];
			}else{
				$states 			= $this->get_wc_states($cc);//Added 20150225			
				if(is_array($states)){
					foreach($states as $key => $value){
						if($key == $st){
							$this->states[$ccst] = $value;
							return $value;
						}
					}
				}else if(empty($states)){
					$this->states[$ccst] = $value;
					return $st;
				}
			}
			
			return $st;
		}
		
		function price($value = 0, $args = array()){
			if(function_exists('wc_price')){
				$v = wc_price($value, $args);
			}elseif(function_exists('woocommerce_price')){
				$v = woocommerce_price($value, $args);
			}else{
				$v = apply_filters( 'wcismispro_currency_symbol', '&#36;', 'USD').$value;
			}
			
			return $v;
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
		
		var $order_item_variation = array();
		function get_variation($order_item_id = 0){
				if(!isset($this->order_item_variation[$order_item_id])){
					global $wpdb;
					$sql = "
					SELECT 
					postmeta_variation.meta_value AS product_variation FROM {$wpdb->prefix}woocommerce_order_items as woocommerce_order_items
					LEFT JOIN  {$wpdb->prefix}woocommerce_order_itemmeta as woocommerce_order_itemmeta ON woocommerce_order_itemmeta.order_item_id = woocommerce_order_items.order_item_id
					LEFT JOIN  {$wpdb->prefix}postmeta as postmeta_variation ON postmeta_variation.post_id = woocommerce_order_itemmeta.meta_value
					WHERE woocommerce_order_items.order_item_id={$order_item_id}
					
					AND woocommerce_order_items.order_item_type = 'line_item'
					AND woocommerce_order_itemmeta.meta_key = '_variation_id'
					AND postmeta_variation.meta_key like 'attribute_%'";
					
					$order_items = $wpdb->get_results($sql);
					//$this->print_array($order_items);
					$variation = array();
					$v = "";
					
					if($order_items)
					foreach($order_items as $key=>$value){
						$variation[] = $value->product_variation;
					}
					
					if(count($variation)>0)
						$v = ucwords (implode(", ", $variation));
					
					$v = str_replace("-"," ",$v);
					
					$this->order_item_variation[$order_item_id] = $v;
				}else{
					$v = $this->order_item_variation[$order_item_id];
				}
				
				return $v;
		}
		
		
		var $order_meta_new = array();
		function get_all_post_meta($order_id,$is_product = false){
			if(!isset($this->order_meta_new[$order_id])){
				$order_meta	= get_post_meta($order_id);
			
				$order_meta_new = array();
				if($is_product){
					foreach($order_meta as $omkey => $omvalue){
						$order_meta_new[$omkey] = $omvalue[0];
					}
				}else{
					foreach($order_meta as $omkey => $omvalue){
						$omkey = substr($omkey, 1);
						$order_meta_new[$omkey] = $omvalue[0];
					}
				}
				
				$this->order_meta_new[$order_id] = $order_meta_new;
			}else{
				$order_meta_new = $this->order_meta_new[$order_id];
			}
			
			return $order_meta_new;
		}
		
		function order_item_name($order_id = 0,$order_item_type = "tax"){
			if(!isset($this->order_item_name[$order_item_type][$order_id])){
				global $wpdb;
			
				$sql = "SELECT woocommerce_order_items.order_item_name	AS	item_name
				FROM {$wpdb->prefix}woocommerce_order_items as woocommerce_order_items	
				WHERE order_item_type ='$order_item_type' AND order_id = '{$order_id}'";
				$order_item_name = $wpdb->get_var($sql);
				
				$this->order_item_name[$order_item_type][$order_id] = $order_item_name;
				
			}else{				
				$order_item_name = $this->order_item_name[$order_item_type][$order_id];
			}
			
			return $order_item_name;
		}
		
		function ic_get_order_statuses($order_item){
			if(function_exists('wc_get_order_statuses')){
				$order_statuses = wc_get_order_statuses();
			}else{
				$order_statuses = array();
			}
			$order_status = isset($order_item->order_status) ? $order_item->order_status : 'not';
			$order_status = isset($order_statuses[$order_status]) ? $order_statuses[$order_status] : $order_status;
			return $order_status;
		}
		
		
		
	}//End Class
}//End class_exists check
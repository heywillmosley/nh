<?php 

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
if ( ! class_exists( 'IC_Commerce_Premium_Golden_Columns' ) ) {
	class IC_Commerce_Premium_Golden_Columns{
		
		public $constants 	=	array();
		
		public function __construct($constants) {
			global $options;			
			$this->constants		= $constants;			
			
		}
		
		function get_dasbboard_coumns($report_name = 'recent_order'){
			$columns		= array("page","Page Incorrect");
			if($report_name == "recent_order"){
				if($this->constants['post_order_status_found'] == 0 ){
					$columns 	= array(
						"order_id"									=> __("Order ID", 				'icwoocommerce_textdomains')
						,"billing_name"								=> __("Name", 					'icwoocommerce_textdomains')
						,"billing_email"							=> __("Email", 					'icwoocommerce_textdomains')
						,"order_date"								=> __("Date", 					'icwoocommerce_textdomains')
						//,"ic_commerce_order_status_name"			=> __("Status", 				'icwoocommerce_textdomains')//commented 20150221
						,"order_status_name"						=> __("Status", 				'icwoocommerce_textdomains')
						//,"ic_commerce_order_item_count"			=> __("Items", 					'icwoocommerce_textdomains')//commented 20150221
						,"order_item_count"							=> __("Items", 					'icwoocommerce_textdomains')
						,"gross_amount"								=> __("Gross Amt.", 			'icwoocommerce_textdomains')					
						,"total_discount"							=> __("Discount Amt.", 			'icwoocommerce_textdomains')
						,"order_shipping"							=> __("Shipping Amt.", 			'icwoocommerce_textdomains')
						,"order_shipping_tax"						=> __("Shipping Tax Amt.", 		'icwoocommerce_textdomains')
						,"order_tax"								=> __("Order Tax Amt.", 		'icwoocommerce_textdomains')
						,"total_tax"								=> __("Total Tax Amt.", 		'icwoocommerce_textdomains')
						,"part_order_refund_amount"					=> __("Part Refund Amt.", 		'icwoocommerce_textdomains')
						,"order_total"								=> __("Net Amt.", 				'icwoocommerce_textdomains')
					);
				}else{
					$columns 	= array(
						"order_id"									=> __("Order ID", 				'icwoocommerce_textdomains')
						,"billing_name"								=> __("Name", 					'icwoocommerce_textdomains')
						,"billing_email"							=> __("Email", 					'icwoocommerce_textdomains')
						,"order_date"								=> __("Date", 					'icwoocommerce_textdomains')
						,"order_status"								=> __("Status", 				'icwoocommerce_textdomains')
						//,"ic_commerce_order_item_count"			=> __("Items", 					'icwoocommerce_textdomains')//commented 20150221
						,"order_item_count"							=> __("Items", 					'icwoocommerce_textdomains')
						,"gross_amount"								=> __("Gross Amt.", 			'icwoocommerce_textdomains')
						,"total_discount"							=> __("Discount Amt.", 			'icwoocommerce_textdomains')
						,"order_shipping"							=> __("Shipping Amt.", 			'icwoocommerce_textdomains')
						,"order_shipping_tax"						=> __("Shipping Tax Amt.", 		'icwoocommerce_textdomains')
						,"order_tax"								=> __("Order Tax Amt.", 		'icwoocommerce_textdomains')
						,"part_order_refund_amount"					=> __("Part Refund Amt.", 		'icwoocommerce_textdomains')
						,"total_tax"								=> __("Total Tax Amt.", 		'icwoocommerce_textdomains')
						,"order_total"								=> __("Net Amt.", 				'icwoocommerce_textdomains')
					);
				}
			}
			
			$columns 	= apply_filters("ic_commerce_dashboard_recent_order_columns", $columns, $report_name);
			
			return $columns;
		}
		
		function get_product_variation_columns($show_variation = 'all', $attributes = array(), $variation_column = 0){
			if($show_variation == 'variable'){
					if($variation_column == 1){
						$top_columns 	= array();
						
						$group_by = $this->get_request('group_by','variation_id');
						
						$top_columns["product_id"] 			= __("Product ID", 			'icwoocommerce_textdomains');
						
						if($group_by == "variation_id")
							$top_columns["variation_id"] 	= __("Variation ID", 		'icwoocommerce_textdomains');
						
						if($group_by == "order_item_id"){	
							$top_columns["order_item_id"] 	= __("Order Item ID", 		'icwoocommerce_textdomains');
							$top_columns["post_date"] 		= __("Order Date", 		'icwoocommerce_textdomains');
							$top_columns["order_id"] 		= __("Order ID", 		'icwoocommerce_textdomains');
							
						}
							
						$top_columns["variation_sku"] 		= __("Product SKU", 		'icwoocommerce_textdomains');
						$top_columns["product_name"] 		= __("Product Name", 		'icwoocommerce_textdomains');
						
						//$attributes	= $this->get_attributes('selected');
						$columns 	= array_merge((array)$top_columns, (array)$attributes);
						$columns 	= apply_filters("ic_commerce_variation_page_column_after_attributes", $columns, $attributes, $show_variation, $variation_column);
						
						$bottom_columns 	= array(
							"quantity"			=> __("Sales Qty.", 		'icwoocommerce_textdomains')
							,"variation_stock"	=> __("Current Stock", 		'icwoocommerce_textdomains')
							,"amount"			=> __("Amount", 			'icwoocommerce_textdomains')
						);
						
						$columns 	= array_merge((array)$columns, (array)$bottom_columns);
						
						
						
					}else{
						$columns 	= array(
							"product_id"		=> __("ID", 				'icwoocommerce_textdomains')
							,"variation_sku"	=> __("Product SKU", 		'icwoocommerce_textdomains')
							,"product_name"		=> __("Product Name", 		'icwoocommerce_textdomains')
							,"product_variation"=> __("Variation", 			'icwoocommerce_textdomains')
							,"quantity"			=> __("Sales Qty.", 		'icwoocommerce_textdomains')
							,"variation_stock"	=> __("Current Stock", 		'icwoocommerce_textdomains')
							,"amount"			=> __("Amount", 			'icwoocommerce_textdomains')
						);	
					}
					
					
				
			}else{
				$columns 	= array(
					"product_id"		=> __("Product ID", 		'icwoocommerce_textdomains')
					,"product_sku"		=> __("Product SKU", 		'icwoocommerce_textdomains')
					,"product_name"		=> __("Product Name", 		'icwoocommerce_textdomains')						
					,"quantity"			=> __("Sales Qty.", 		'icwoocommerce_textdomains')
					,"product_stock"	=> __("Current Stock", 		'icwoocommerce_textdomains')
					,"amount"			=> __("Amount", 			'icwoocommerce_textdomains')
				);	
			}
			
			$columns 	= apply_filters("ic_commerce_variation_page_columns", $columns, $attributes, $show_variation, $variation_column);
			
			return $columns;
		}
		
		function result_columns_variation_page($show_variation){
			if($show_variation == 'variable'){
				$columns 	= array(
					"total_row_count"	=> __("Variation Count", 		'icwoocommerce_textdomains')
					,"quantity"			=> __("Sales Quantity", 		'icwoocommerce_textdomains')				
					,"amount"			=> __("Amount", 				'icwoocommerce_textdomains')
				);	
			}else{
				$columns 	= array(
					"total_row_count"	=> __("Product Count", 			'icwoocommerce_textdomains')
					,"quantity"			=> __("Sales Quantity"	, 		'icwoocommerce_textdomains')			
					,"amount"			=> __("Amount", 				'icwoocommerce_textdomains')
				);	
			}
			
			$columns 	= apply_filters("ic_commerce_variation_page_result_columns", $columns, $show_variation);
			return $columns;
			
		}
		
		function result_columns_details_page($detail_column = 'no'){
			$columns				= array();
			if($detail_column == "yes"){
				$columns = array(
					/*"total_order_count"							=> __("Order Count", 								'icwoocommerce_textdomains')
					,"total_row_count"							=> __("Product Count", 								'icwoocommerce_textdomains')
					,"product_quantity"							=> __("Qty.<br />(A)", 								'icwoocommerce_textdomains')
					,"product_rate"								=> __("Rate<br />(B)",								'icwoocommerce_textdomains')
					,"item_amount"								=> __("Prod. Amt.<br />(C)<br />{A * B}",			'icwoocommerce_textdomains')
					,"order_item_tax"							=> __("Item Tax Amt.<br />(D)", 					'icwoocommerce_textdomains')				
					,"product_rate_net"							=> __("Prod. Rate<br />(E)<br />[{C + D} * A]", 	'icwoocommerce_textdomains')
					,"item_discount"							=> __("Prod. Discount<br />(F)",					'icwoocommerce_textdomains')
					,"product_net"								=> __("Item Net Amt.<br />(G)<br />{E - F}", 		'icwoocommerce_textdomains')					
					,"total_price"								=> __("Net Amt.<br />{G - D}", 						'icwoocommerce_textdomains')	
					,"customer_price"							=> __("Customer Price", 							'icwoocommerce_textdomains')*/	
					"total_order_count"							=> __("Order Count", 				'icwoocommerce_textdomains')
					,"total_row_count"							=> __("Product Count", 				'icwoocommerce_textdomains')
					,"product_quantity"							=> __("Qty.", 						'icwoocommerce_textdomains')
					,"product_rate"								=> __("Rate",						'icwoocommerce_textdomains')
					,"item_amount"								=> __("Prod. Amt.",					'icwoocommerce_textdomains')
					,"item_discount"							=> __("Prod. Discount",				'icwoocommerce_textdomains')
					,"total_price"								=> __("Net Amt."	, 				'icwoocommerce_textdomains')
				);
			}else{				
				$columns = array(
					"total_row_count"							=> __("Order Count", 				'icwoocommerce_textdomains')
					,"gross_amount"								=> __("Gross Amt.", 				'icwoocommerce_textdomains')					
					//,"order_discount"							=> __("Order Discount Amt.", 		'icwoocommerce_textdomains')
					//,"cart_discount"							=> __("Cart Discount Amt.", 		'icwoocommerce_textdomains')
					,"total_discount"							=> __("Discount Amt.", 				'icwoocommerce_textdomains')
					,"order_shipping"							=> __("Shipping Amt.", 				'icwoocommerce_textdomains')
					,"order_shipping_tax"						=> __("Shipping Tax Amt.", 			'icwoocommerce_textdomains')
					,"order_tax"								=> __("Order Tax Amt.", 			'icwoocommerce_textdomains')
					,"total_tax"								=> __("Total Tax Amt.", 			'icwoocommerce_textdomains')
					,"part_order_refund_amount"					=> __("Part Refund Amt.", 			'icwoocommerce_textdomains')
					,"order_total"								=> __("Net Amt.", 					'icwoocommerce_textdomains')
				);
				
			}
			
			$columns = apply_filters("ic_commerce_result_columns_details_page", $columns, $detail_column);
			
			return $columns;
		}
		
		function grid_columns_details_page($detail_column = "details_view"){
			if($detail_column == "details_view"){
				$order_columns 		= $this->details_view_columns("order_columns");
				$product_columns 	= $this->details_view_columns("product_columns");
				$columns 	= array_merge((array)$order_columns, (array)$product_columns);
			}else{
				//New Change ID 20140918
				if($this->constants['post_order_status_found'] == 0 ){
					$columns = array(					
						"order_id"									=> __("Order ID", 						'icwoocommerce_textdomains')						
						,"billing_name"								=> __("Name", 							'icwoocommerce_textdomains')
						,"billing_email"							=> __("Email", 							'icwoocommerce_textdomains')
						,"billing_country"							=> __("Country", 						'icwoocommerce_textdomains')
						,"billing_city"								=> __("City", 							'icwoocommerce_textdomains')
						,"order_date"								=> __("Date", 							'icwoocommerce_textdomains')						
						,"order_status_name"						=> __("Status", 						'icwoocommerce_textdomains')						
						,"tax_name"									=> __("Tax Name", 						'icwoocommerce_textdomains')
						,"shipping_method_title"					=> __("Shipping Method", 				'icwoocommerce_textdomains')
						,"payment_method_title"						=> __("Payment Method", 				'icwoocommerce_textdomains')
						,"order_currency"							=> __("Order Currency", 				'icwoocommerce_textdomains')						
						,"order_coupon_codes"						=> __("Coupon Code", 					'icwoocommerce_textdomains')						
						,"order_item_count"							=> __("Items", 							'icwoocommerce_textdomains')	
						,"gross_amount"								=> __("Gross Amt.", 					'icwoocommerce_textdomains')				
						//,"order_discount"							=> __("Order Discount Amt.", 			'icwoocommerce_textdomains')
						//,"cart_discount"							=> __("Cart Discount Amt.", 			'icwoocommerce_textdomains')
						,"total_discount"							=> __("Discount Amt.", 					'icwoocommerce_textdomains')
						,"order_shipping"							=> __("Shipping Amt.", 					'icwoocommerce_textdomains')
						,"order_shipping_tax"						=> __("Shipping Tax Amt.", 				'icwoocommerce_textdomains')
						,"order_tax"								=> __("Order Tax Amt.", 				'icwoocommerce_textdomains')
						,"total_tax"								=> __("Total Tax Amt.", 				'icwoocommerce_textdomains')
						,"part_order_refund_amount"					=> __("Part Refund Amt.", 				'icwoocommerce_textdomains')
						,"order_total"								=> __("Net Amt.", 						'icwoocommerce_textdomains')
						,"invoice_action"							=> __("Invoice Action", 				'icwoocommerce_textdomains')
						
					);
				}else{
					$columns = array(					
						"order_id"									=> __("Order ID", 						'icwoocommerce_textdomains')
						,"billing_name"								=> __("Name", 							'icwoocommerce_textdomains')
						//,"customer_user"							=> __("customer_user", 					'icwoocommerce_textdomains')
						,"billing_email"							=> __("Email", 							'icwoocommerce_textdomains')
						,"billing_country"							=> __("Country", 						'icwoocommerce_textdomains')
						,"billing_city"								=> __("City", 							'icwoocommerce_textdomains')
						,"order_date"								=> __("Date", 							'icwoocommerce_textdomains')
						,"order_status"								=> __("Status", 						'icwoocommerce_textdomains')
						,"tax_name"									=> __("Tax Name", 						'icwoocommerce_textdomains')
						,"shipping_method_title"					=> __("Shipping Method", 				'icwoocommerce_textdomains')
						,"payment_method_title"						=> __("Payment Method", 				'icwoocommerce_textdomains')
						,"order_currency"							=> __("Order Currency", 				'icwoocommerce_textdomains')
						,"order_coupon_codes"						=> __("Coupon Code", 					'icwoocommerce_textdomains')
						,"order_item_count"							=> __("Items", 							'icwoocommerce_textdomains')
						,"gross_amount"								=> __("Gross Amt.", 					'icwoocommerce_textdomains')					
						//,"order_discount"							=> __("Order Discount Amt.", 			'icwoocommerce_textdomains')
						//,"discount_amount"							=> __("Cart Discount Amt.", 			'icwoocommerce_textdomains')
						//,"cart_discount"							=> __("Cart Discount Amt.", 			'icwoocommerce_textdomains')
						,"total_discount"							=> __("Discount Amt.", 					'icwoocommerce_textdomains')
						,"order_shipping"							=> __("Shipping Amt.", 					'icwoocommerce_textdomains')
						,"order_shipping_tax"						=> __("Shipping Tax Amt.", 				'icwoocommerce_textdomains')
						,"order_tax"								=> __("Order Tax Amt.", 				'icwoocommerce_textdomains')
						,"total_tax"								=> __("Total Tax Amt.", 				'icwoocommerce_textdomains')
						,"part_order_refund_amount"					=> __("Part Refund Amt.", 				'icwoocommerce_textdomains')
						,"order_total"								=> __("Net Amt.", 						'icwoocommerce_textdomains')
						,"invoice_action"							=> __("Invoice Action", 				'icwoocommerce_textdomains')
					);
				}
				
				$columns 			= apply_filters("ic_commerce_normal_view_columns", $columns, $detail_column);
			}
			
			return $columns;
		}
		
		function details_view_columns($column_view = "order_columns"){
			if($column_view == "order_columns"){
				$columns = array(					
					"order_id"									=> __("Order ID", 				'icwoocommerce_textdomains')
					,"billing_name"								=> __("Name", 					'icwoocommerce_textdomains')
					,"billing_email"							=> __("Email", 					'icwoocommerce_textdomains')
					,"billing_country"							=> __("Country", 				'icwoocommerce_textdomains')
					,"billing_city"								=> __("City", 					'icwoocommerce_textdomains')
					,"order_date"								=> __("Date", 					'icwoocommerce_textdomains')
					//,"part_order_refund_amount"					=> __("Part Refund Amt.", 		'icwoocommerce_textdomains')
					//,"order_shipping"							=> __("Shipping Amt.", 					'icwoocommerce_textdomains')
					//,"order_shipping_tax"						=> __("Shipping Tax Amt.", 				'icwoocommerce_textdomains')
					//,"order_tax"								=> __("Order Tax Amt.", 				'icwoocommerce_textdomains')
					//,"total_tax"								=> __("Total Tax Amt.", 				'icwoocommerce_textdomains')
				);
				
				if($this->constants['post_order_status_found'] == 0 ){
					$columns['order_status_name']					= __("Status", 				'icwoocommerce_textdomains');
				}else{
					$columns['order_status']						= __("Status", 				'icwoocommerce_textdomains');
				}
				
				$columns['order_coupon_codes']						= __("Coupon Code", 		'icwoocommerce_textdomains');
				//$columns['order_shipping']							= __("Shipping Amt.", 		'icwoocommerce_textdomains');
				//$columns['total_tax']								= __("Total Tax Amt.", 		'icwoocommerce_textdomains');
			}else{
				$columns = array(					
					/*"category_name"									=> __("Category", 									'icwoocommerce_textdomains')
					//,"product_id"									=> __("Product ID", 								'icwoocommerce_textdomains')
					,"product_name"									=> __("Products", 									'icwoocommerce_textdomains')
					,"order_product_sku"							=> __("SKU", 										'icwoocommerce_textdomains')
					,"product_variation"							=> __("Variation", 									'icwoocommerce_textdomains')
					,"product_quantity"								=> __("Qty.<br />(A)", 								'icwoocommerce_textdomains')
					,"product_rate"									=> __("Rate<br />(B)",								'icwoocommerce_textdomains')
					,"item_amount"									=> __("Prod. Amt.<br />(C)<br />{A * B}",			'icwoocommerce_textdomains')
					,"order_item_tax"								=> __("Item Tax Amt.<br />(D)", 					'icwoocommerce_textdomains')				
					,"product_rate_net"								=> __("Prod. Rate<br />(E)<br />[{C + D} * A]", 	'icwoocommerce_textdomains')
					,"item_discount"								=> __("Prod. Discount<br />(F)",					'icwoocommerce_textdomains')
					,"product_net"									=> __("Item Net Amt.<br />(G)<br />{E - F}", 		'icwoocommerce_textdomains')					
					,"total_price"									=> __("Net Amt.<br />{G - D}", 						'icwoocommerce_textdomains')
					,"customer_price"								=> __("Customer Price", 							'icwoocommerce_textdomains')		
					,"invoice_action"								=> __("Invoice Action", 							'icwoocommerce_textdomains')*/
					"category_name"									=> __("Category", 			'icwoocommerce_textdomains')
					//,"product_id"									=> __("Product ID", 		'icwoocommerce_textdomains')
					,"product_name"									=> __("Products", 			'icwoocommerce_textdomains')
					,"order_product_sku"							=> __("SKU", 				'icwoocommerce_textdomains')
					,"product_variation"							=> __("Variation", 			'icwoocommerce_textdomains')
					,"product_quantity"								=> __("Qty.", 				'icwoocommerce_textdomains')
					,"product_rate"									=> __("Rate",				'icwoocommerce_textdomains')					
					,"item_amount"									=> __("Prod. Amt.",			'icwoocommerce_textdomains')
					,"item_discount"								=> __("Prod. Discount",		'icwoocommerce_textdomains')					
					,"total_price"									=> __("Net Amt."	, 		'icwoocommerce_textdomains')	
					,"invoice_action"								=> __("Invoice Action", 	'icwoocommerce_textdomains')
				);
			}
			
			/*Item Net Amt.(Prod Rate - Prod Disc)*/
			/*Prod. Rate (Prod. Amt. + Item Tax Amt.)*/
			
			$columns 			= apply_filters("ic_commerce_details_view_columns", $columns, $column_view);
			
			return $columns;
		}
		
		function grid_columns_all_reports($report_name = 'product_page'){
			$columns		= array();
			switch ($report_name ) {
				case "product_page":
					$columns 	= array(
						"product_sku" 			=> __("Product SKU", 		'icwoocommerce_textdomains')
						,"product_name"			=> __("Product Name", 		'icwoocommerce_textdomains')
						,"product_categories"	=> __("Categories", 		'icwoocommerce_textdomains')						
						,"quantity"				=> __("Sales Qty.", 		'icwoocommerce_textdomains')
						,"product_stock"		=> __("Current Stock", 		'icwoocommerce_textdomains')
						,"total_amount"			=> __("Amount", 			'icwoocommerce_textdomains')
					);	
					break;
				case "customer_page":
					$columns 	= array(
						"billing_first_name"	=> __("Billing First Name", 	'icwoocommerce_textdomains')
						,"billing_last_name"	=> __("Billing Last Name", 		'icwoocommerce_textdomains')
						//,"user_name"			=> __("Username", 				'icwoocommerce_textdomains')
						,"billing_company"		=> __("Company Name", 			'icwoocommerce_textdomains')
						,"billing_email"		=> __("Billing Email", 			'icwoocommerce_textdomains')						
						,"order_count"			=> __("Order Count", 			'icwoocommerce_textdomains')
						,"order_date"			=> __("Last Order Date", 		'icwoocommerce_textdomains')
						,"total_amount"			=> __("Amount", 				'icwoocommerce_textdomains')
						);	
					break;
					
				
					
				case "payment_gateway_page":
					$columns 	= array(
						"payment_method_title"	=> __("Payment Method", 	'icwoocommerce_textdomains')
						,"order_count"			=> __("Order Count", 		'icwoocommerce_textdomains')
						,"total_amount"			=> __("Amount", 			'icwoocommerce_textdomains')
					);
					break;
				case "order_status":
					$columns 	= array(
						"order_status"			=> __("Order Status", 		'icwoocommerce_textdomains')
						,"order_count"			=> __("Order Count", 		'icwoocommerce_textdomains')
						,"total_amount"			=> __("Amount", 			'icwoocommerce_textdomains')
					);
					break;
					
				case "recent_order":
					$status_key = "ic_commerce_order_status_name";////New Change ID 20140918
					if($this->constants['post_order_status_found'] == 1 ){
						$status_key = "order_status";
					}else if($this->constants['post_order_status_found'] == 0 ){
						//$status_key = "ic_commerce_order_status_name";//commented 20150221
						$status_key = "order_status_name";
					}
					
					$columns 	= array(
						"order_id"									=> __("Order ID", 					'icwoocommerce_textdomains')
						,"billing_name"								=> __("Name", 						'icwoocommerce_textdomains')
						,"billing_email"							=> __("Email", 						'icwoocommerce_textdomains')
						,"order_date"								=> __("Date", 						'icwoocommerce_textdomains')
						,$status_key								=> __("Status", 					'icwoocommerce_textdomains')////New Change ID 20140918
						//,"ic_commerce_order_tax_name"				=> __("Tax Name", 					'icwoocommerce_textdomains')
						//,"ic_commerce_order_coupon_codes"			=> __("Coupon Code", 				'icwoocommerce_textdomains')
						//,"ic_commerce_order_item_count"			=> __("Items", 						'icwoocommerce_textdomains')//commented 20150221
						,"order_item_count"							=> __("Items", 						'icwoocommerce_textdomains')
						,"gross_amount"								=> __("Gross Amt.", 				'icwoocommerce_textdomains')
						//,"order_discount"							=> __("Order Discount Amt.", 		'icwoocommerce_textdomains')
						//,"cart_discount"							=> __("Cart Discount Amt.", 		'icwoocommerce_textdomains')
						,"total_discount"							=> __("Discount Amt.", 		'icwoocommerce_textdomains')
						,"order_shipping"							=> __("Shipping Amt.", 				'icwoocommerce_textdomains')
						,"order_shipping_tax"						=> __("Shipping Tax Amt.", 			'icwoocommerce_textdomains')
						,"order_tax"								=> __("Order Tax Amt.", 			'icwoocommerce_textdomains')
						,"total_tax"								=> __("Total Tax Amt.", 			'icwoocommerce_textdomains')
						,"part_order_refund_amount"					=> __("Part Refund Amt.", 			'icwoocommerce_textdomains')
						,"order_total"								=> __("Net Amt.", 					'icwoocommerce_textdomains')
					);
					break;
				case "coupon_page":
					$columns 	= array(
						"coupon_code"			=> __("Coupon Code", 									'icwoocommerce_textdomains')
						,"coupon_count"			=> __("Coupon Count", 									'icwoocommerce_textdomains')
						,"total_amount"			=> __("Coupon Amount", 									'icwoocommerce_textdomains')
						);	
					break;
				case "billing_country_page":
					$columns 	= array(
						"billing_country"		=> __("Billing Country", 		'icwoocommerce_textdomains')
						,"order_count"			=> __("Order Count", 			'icwoocommerce_textdomains')
						,"total_amount"			=> __("Amount", 				'icwoocommerce_textdomains')
					);
					$billing_or_shipping 						= $this->get_request('billing_or_shipping','billing');
					if(isset($columns['billing_country'])) 		$columns['billing_country'] 	= $billing_or_shipping == "shipping" ? __( 'Shipping Country' , 'icwoocommerce_textdomains') : $columns['billing_country'];
					
					break;
				case "advance_product_page"://New Custom Change ID 20141009
					$columns 	= array(
						"product_sku" 					=> __("SKU", 									'icwoocommerce_textdomains')
						//,"order_item_id" 				=> __("order_item_id", 							'icwoocommerce_textdomains')
						,"product_name"					=> __("Products", 								'icwoocommerce_textdomains')
						,"post_date"					=> __("Date", 									'icwoocommerce_textdomains')
						,"quantity"						=> __("Quantity", 								'icwoocommerce_textdomains')
						,"product_rate_exculude_tax"	=> __("Product Price Exc VAT", 					'icwoocommerce_textdomains')
						,"product_vat_par_item"			=> __("VAT per Item", 							'icwoocommerce_textdomains')
						,"product_shipping"				=> __("Shipping", 								'icwoocommerce_textdomains')
						,"total_price_exculude_tax"		=> __("Total Price Exl VAT and Shipping", 		'icwoocommerce_textdomains')
						,"total_amount"					=> __("Total Price", 							'icwoocommerce_textdomains')
					);	
					break;		
				
				
				
				case "manual_refund_detail_page"://New Change ID 20150403
					$status_key = "ic_commerce_order_status_name";////New Change ID 20140918
					if($this->constants['post_order_status_found'] == 1 ){
						$status_key = "order_status";
					}else if($this->constants['post_order_status_found'] == 0 ){
						//$status_key = "ic_commerce_order_status_name";//commented 20150221
						$status_key = "order_status_name";
					}
					
					$group_by 								= $this->get_request('group_by');
					$refund_status_type						= $this->get_request('refund_status_type');
					
					if($refund_status_type == 'total_refunded'){
						if($group_by == "order_id"){
							$columns 	= array(
								"order_id"							=>	__("Order ID", 				'icwoocommerce_textdomains')
								,"order_date"						=>  __("Order Date",			'icwoocommerce_textdomains')
								,$status_key						=>  __("Order Status", 			'icwoocommerce_textdomains')							
								
								,"part_order_refund_amount"			=>	__("Part Refund",			'icwoocommerce_textdomains')
								,"order_refund_amount"				=>	__("Full Refund",			'icwoocommerce_textdomains')
								
								,"refund_count"						=>	__("Refund Counts",			'icwoocommerce_textdomains')
								,"total_amount"						=>  __("Refund Amount", 		'icwoocommerce_textdomains')
							);
						
						}else if($group_by == "refunded"){
							$columns 	= array(
								"refund_user"						=>	__("Refunded By", 			'icwoocommerce_textdomains')
								,"part_order_refund_amount"			=>	__("Part Refund",			'icwoocommerce_textdomains')
								,"order_refund_amount"				=>	__("Full Refund",			'icwoocommerce_textdomains')
								,"refund_count"						=>	__("Refund Counts",			'icwoocommerce_textdomains')
								,"total_amount"						=>  __("Refund Amount", 		'icwoocommerce_textdomains')
							);
						}else if($group_by == "daily"){
							$columns 	= array(
								"group_date"						=>	__("Refund Date",			'icwoocommerce_textdomains')
								,"part_order_refund_amount"			=>	__("Part Refund",			'icwoocommerce_textdomains')
								,"order_refund_amount"				=>	__("Full Refund",			'icwoocommerce_textdomains')
								,"refund_count"						=>	__("Order Counts",			'icwoocommerce_textdomains')
								,"total_amount"						=>  __("Refund Amount",		 	'icwoocommerce_textdomains')
							);
						}else if($group_by == "monthly"){
							$columns 	= array(
								"group_column"						=>	__("Month", 				'icwoocommerce_textdomains')
								,"part_order_refund_amount"			=>	__("Part Refund",			'icwoocommerce_textdomains')
								,"order_refund_amount"				=>	__("Full Refund",			'icwoocommerce_textdomains')
								,"refund_count"						=>	__("Refund Counts",			'icwoocommerce_textdomains')
								,"total_amount"						=>  __("Refund Amount", 		'icwoocommerce_textdomains')
							);
						}else if($group_by == "yearly"){
							$columns 	= array(
								"group_column"						=>	__("Year", 					'icwoocommerce_textdomains')
								,"part_order_refund_amount"			=>	__("Part Refund",			'icwoocommerce_textdomains')
								,"order_refund_amount"				=>	__("Full Refund",			'icwoocommerce_textdomains')
								,"refund_count"						=>	__("Refund Counts",			'icwoocommerce_textdomains')
								,"total_amount"						=>  __("Refund Amount", 		'icwoocommerce_textdomains')
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
								,"total_amount"						=>  __("Refund Amount", 		'icwoocommerce_textdomains')
							);
						}
					}else{
						if($group_by == "order_id"){
							$columns 	= array(
								"order_id"							=>	__("Order ID", 				'icwoocommerce_textdomains')
								,"order_date"						=>  __("Order Date",			'icwoocommerce_textdomains')
								,$status_key						=>  __("Order Status", 			'icwoocommerce_textdomains')							
								,"refund_count"						=>	__("Refund Counts",			'icwoocommerce_textdomains')
								,"total_amount"						=>  __("Refund Amount", 		'icwoocommerce_textdomains')
							);
						
						}else if($group_by == "refunded"){
							$columns 	= array(
								"refund_user"						=>	__("Refunded By", 			'icwoocommerce_textdomains')
								,"refund_count"						=>	__("Refund Counts",			'icwoocommerce_textdomains')
								,"total_amount"						=>  __("Refund Amount", 		'icwoocommerce_textdomains')
							);
						}else if($group_by == "daily"){
							$columns 	= array(
								"group_date"						=>	__("Refund Date",			'icwoocommerce_textdomains')
								,"refund_count"						=>	__("Order Counts",			'icwoocommerce_textdomains')
								,"total_amount"						=>  __("Refund Amount",		 	'icwoocommerce_textdomains')
							);
						}else if($group_by == "monthly"){
							$columns 	= array(
								"group_column"						=>	__("Month", 				'icwoocommerce_textdomains')
								,"refund_count"						=>	__("Refund Counts",			'icwoocommerce_textdomains')
								,"total_amount"						=>  __("Refund Amount", 		'icwoocommerce_textdomains')
							);
						}else if($group_by == "yearly"){
							$columns 	= array(
								"group_column"						=>	__("Year", 					'icwoocommerce_textdomains')
								,"refund_count"						=>	__("Refund Counts",			'icwoocommerce_textdomains')
								,"total_amount"						=>  __("Refund Amount", 		'icwoocommerce_textdomains')
							);
						}else{
							$columns 	= array(
								"refund_id"							=>	__("Refund ID",				'icwoocommerce_textdomains')
								,"refund_date"						=>  __("Refund Date",			'icwoocommerce_textdomains')
								,"refund_status"					=>	__("Refund Status",			'icwoocommerce_textdomains')
								,"refund_user"						=>  __("Refund By",				'icwoocommerce_textdomains')
								,"order_id"							=>	__("Order ID", 				'icwoocommerce_textdomains')
								,"order_date"						=>  __("Order Date",			'icwoocommerce_textdomains')
								//,"billing_name"						=>  __("Customer Name",			'icwoocommerce_textdomains')
								,$status_key						=>  __("Order Status", 			'icwoocommerce_textdomains')
								,"refund_note"						=>  __("Refund Note",			'icwoocommerce_textdomains')
								//,"refund_tax"						=>  __("Refund Tax",			'icwoocommerce_textdomains')
								,"total_amount"						=>  __("Refund Amount", 		'icwoocommerce_textdomains')
							);
						}
					}
					
					
					
					
					if($refund_status_type == "status_refunded"){
						$columns['refund_count'] 				= __("Order Counts",			'icwoocommerce_textdomains');
					}else{
						unset($columns['part_refund_count']);
						unset($columns['part_refund_amount']);
						unset($columns['total_refund_amount']);
					}
					
					$show_refund_note 	= $this->get_request('show_refund_note','no');					
					if($show_refund_note == "no") unset($columns['refund_note']);
					//unset($columns['order_count']);
					break;
				default:
					$columns 	= array();
					break;
				
			}
			
			$columns 			= apply_filters("ic_commerce_report_page_columns", $columns, $report_name);
			
			return $columns;
		}
		
		function result_columns_all_reports($report_name = ''){// echo $report_name;
			$total_columns = array();
			switch($report_name){
				case "product_page"://New Custom Change ID 20141015
					$total_columns = array(
						"total_row_count"			=> __("Product Count", 		'icwoocommerce_textdomains')
						,"quantity"					=> __("Sales Quantity", 	'icwoocommerce_textdomains')
						,"total_amount"				=> __("Total Amount", 		'icwoocommerce_textdomains')
					);
					break;
					
				
				
				
				case "customer_page"://New Custom Change ID 20141015
					$total_columns = array(
						"total_row_count"			=> __("Customer Count", 	'icwoocommerce_textdomains')
						,"order_count"				=> __("Order Count", 		'icwoocommerce_textdomains')
						,"total_amount"				=> __("Total Amount", 		'icwoocommerce_textdomains')
					);
					break;					
				case "billing_country_page"://New Custom Change ID 20141015
					$total_columns = array(
						"total_row_count"			=> __("Country Count", 		'icwoocommerce_textdomains')
						,"order_count"				=> __("Order Count", 		'icwoocommerce_textdomains')
						,"total_amount"				=> __("Total Amount", 		'icwoocommerce_textdomains')
					);
					
					$billing_or_shipping 						= $this->get_request('billing_or_shipping','billing');
					if(isset($total_columns['total_row_count'])) 		$total_columns['total_row_count'] 	= $billing_or_shipping == "shipping" ? __( 'Shipping Country Count' , 'icwoocommerce_textdomains') : $total_columns['total_row_count'];
					break;
				
				case "payment_gateway_page"://New Custom Change ID 20141015
					$total_columns = array(
						"total_row_count"			=> __("Payment Method Count", 		'icwoocommerce_textdomains')
						,"order_count"				=> __("Order Count", 				'icwoocommerce_textdomains')
						,"total_amount"				=> __("Total Amount", 				'icwoocommerce_textdomains')
					);
					break;
				
				case "order_status"://New Custom Change ID 20141015
					$total_columns = array(
						"total_row_count"			=> __("Order Status Count", 		'icwoocommerce_textdomains')
						,"order_count"				=> __("Order Count", 				'icwoocommerce_textdomains')
						,"total_amount"				=> __("Total Amount", 				'icwoocommerce_textdomains')
					);
					break;
				case "recent_order"://New Custom Change ID 20141015
					$total_columns = array(
						"total_row_count"							=> __("Order Count", 				'icwoocommerce_textdomains')
						//,"ic_commerce_order_item_count"			=> __("Items", 						'icwoocommerce_textdomains')//commented 20150221
						//,"order_item_count"						=> __("Items", 						'icwoocommerce_textdomains')
						,"gross_amount"								=> __("Gross Amt.", 				'icwoocommerce_textdomains')					
						//,"order_discount"							=> __("Order Discount Amt.", 		'icwoocommerce_textdomains')
						//,"cart_discount"							=> __("Cart Discount Amt.", 		'icwoocommerce_textdomains')
						,"total_discount"							=> __("Discount Amt.", 				'icwoocommerce_textdomains')
						,"order_shipping"							=> __("Shipping Amt.", 				'icwoocommerce_textdomains')
						,"order_shipping_tax"						=> __("Shipping Tax Amt.", 			'icwoocommerce_textdomains')
						,"order_tax"								=> __("Order Tax Amt.", 			'icwoocommerce_textdomains')
						,"total_tax"								=> __("Total Tax Amt.", 			'icwoocommerce_textdomains')
						,"part_order_refund_amount"					=> __("Part Refund Amt.", 			'icwoocommerce_textdomains')
						,"order_total"								=> __("Net Amt.", 					'icwoocommerce_textdomains')
					);
					break;
					
				case "coupon_page"://New Custom Change ID 20141015
					$total_columns = array(
						"total_row_count"			=> __("Result Count", 		'icwoocommerce_textdomains')
						,"coupon_count"				=> __("Coupon Count", 		'icwoocommerce_textdomains')
						,"total_amount"				=> __("Coupon Amount", 		'icwoocommerce_textdomains')
					);
					break;
				case "manual_refund_detail_page":
					$refund_status_type = $this->get_request('refund_status_type');
					
					if($refund_status_type == 'total_refunded'){
						$total_columns 	= array(
							"total_row_count"						=> 	__("Results Count", 		'icwoocommerce_textdomains')
							,"part_order_refund_amount"				=>	__("Part Refund",			'icwoocommerce_textdomains')
							,"order_refund_amount"					=>	__("Full Refund",			'icwoocommerce_textdomains')
							,"refund_count"							=>	__("Refund Counts",			'icwoocommerce_textdomains')
							,"total_amount"							=>  __("Refund Amount", 		'icwoocommerce_textdomains')
						);
					}else{
						$total_columns 	= array(
							"total_row_count"						=> 	__("Results Count", 		'icwoocommerce_textdomains')
							,"refund_count"							=>	__("Refund Counts",			'icwoocommerce_textdomains')
							,"total_amount"							=>  __("Refund Amount", 		'icwoocommerce_textdomains')
						);
					}
					
					if($refund_status_type == "status_refunded"){
						$total_columns['refund_count']			= __("Order Counts",			'icwoocommerce_textdomains');
					}
					break;
				default:
					$total_columns = array(
						"total_amount"							=> __("Amount",					'icwoocommerce_textdomains')
					);
					break;
				
			}	
			
			$total_columns 			= apply_filters("ic_commerce_report_page_result_columns", $total_columns, $report_name);
			return $total_columns;
		}//End result_columns_all_reports	
		
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
		
	}//END class IC_Commerce_Premium_Golden_Columns{
}//END if ( ! class_exists( 'IC_Commerce_Premium_Golden_Columns' ) ) {
<?php
if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! class_exists( 'IC_Commerce_Premium_Golden_Detail_report' ) ) {
	class IC_Commerce_Premium_Golden_Detail_report extends IC_Commerce_Premium_Golden_Fuctions{
		
		public $per_page = 0;	
		
		public $per_page_default = 10;
		
		public $request_data =	array();
		
		public $constants 	=	array();
		
		public $request		=	array();
		
		public $order_meta	= array();
		
		public function __construct($constants) {
			global $options, $last_days_orders;
			
			$this->constants		= $constants;			
			$options				= $this->constants['plugin_options'];
			$this->per_page_default	= $this->constants['per_page_default'];			
			$per_page 				= (isset($options['per_apge']) and strlen($options['per_apge']) > 0)? $options['per_apge'] : $this->per_page_default;
			$this->per_page 		= is_numeric($per_page) ? $per_page : $this->per_page_default;
			$last_days_orders 		= "0";
			
			add_action('ic_commerce_price_columns', 						array($this,'ic_commerce_price_columns'));
			add_action('ic_commerce_pdf_custom_column_right_alignment', 	array($this,'ic_commerce_price_columns'));
			
		}
		
		function ic_commerce_price_columns($price_columns = array()){			
			$price_columns['order_item_tax'] 	= 'order_item_tax';
			$price_columns['product_rate_net'] 	= 'product_rate_net';
			$price_columns['product_net'] 		= 'product_net';
			$price_columns['customer_price'] 	= 'customer_price';			
			return $price_columns;
		}
		
		function init(){
				global $last_days_orders, $wpdb;
								
				if(!isset($_REQUEST['page'])){return false;}
				
				if ( !current_user_can( $this->constants['plugin_role'] ) )  {
					wp_die( __( 'You do not have sufficient permissions to access this page.','icwoocommerce_textdomains' ) );
				}
				
				do_action('ic_commerce_current_user_can',$_REQUEST['page'],$this->constants);
				
				if(isset($_REQUEST['unknown_product'])){
					$sql = "SELECT * FROM `{$wpdb->prefix}woocommerce_order_items` WHERE `order_item_name` LIKE 'Unknown Product' GROUP BY order_id ORDER BY order_id DESC";
					$items = $wpdb->get_results($sql);
					echo "<h3><strong>SQL Query</strong></h3>";
					$this->print_sql($sql);
					echo "<h3><strong>Results Query</strong></h3>";
					$this->print_array($items);
					return ;
				}
				/*
				$limit = 3;
				
				$sql = "SELECT";
				$sql .= " shop_order.ID AS order_id";
				$sql .= ", shop_order.post_date AS order_date";
				$sql .= ", shop_order.post_status AS order_status";
				$sql .= " FROM {$wpdb->posts} AS shop_order";
				$sql .= " WHERE 1*1";
				$sql .= " AND shop_order.post_type IN ('shop_order')";
				
				$sql .= " ORDER BY shop_order.post_date DESC";
				$sql .= " LIMIT {$limit}";
				
				$items = $wpdb->get_results($sql);
				$new_list = array();
				foreach($items as $key => $item){
					$item->site_id = 1;
					$new_list[$item->order_date.":".$item->site_id] = $item;
				}
				
				$sql = "SELECT";
				$sql .= " shop_order.ID AS order_id";
				$sql .= ", shop_order.post_date AS order_date";
				$sql .= ", shop_order.post_status AS order_status";
				$sql .= " FROM wp_2_posts AS shop_order";
				$sql .= " WHERE 1*1";
				$sql .= " AND shop_order.post_type IN ('shop_order')";
				
				$sql .= " ORDER BY shop_order.post_date DESC";
				$sql .= " LIMIT {$limit}";
				
				$items = $wpdb->get_results($sql);
				foreach($items as $key => $item){
					$item->site_id = 2;
					$new_list[$item->order_date.":".$item->site_id] = $item;
				}
				
				$sql = "SELECT";
				$sql .= " shop_order.ID AS order_id";
				$sql .= ", shop_order.post_date AS order_date";
				$sql .= ", shop_order.post_status AS order_status";
				$sql .= " FROM wp_3_posts AS shop_order";
				$sql .= " WHERE 1*1";
				$sql .= " AND shop_order.post_type IN ('shop_order')";
				
				$sql .= " ORDER BY shop_order.post_date DESC";
				$sql .= " LIMIT {$limit}";
				
				$items = $wpdb->get_results($sql);
				foreach($items as $key => $item){
					$item->site_id = 3;
					$new_list[$item->order_date.":".$item->site_id] = $item;
				}
				
				krsort($new_list);
				$this->print_array($new_list);
				*/
				
				$order_date_field_key	= $this->get_setting('order_date_field_key',$this->constants['plugin_options'], 'post_date');
				$shop_order_status		= $this->get_set_status_ids();	
				$hide_order_status		= $this->constants['hide_order_status'];
				$hide_order_status		= implode(",",$hide_order_status);
				
				$order_status_id 		= "";
				$order_status 			= "";
				
				if($this->constants['post_order_status_found'] == 0 ){					
					$order_status_id 	= implode(",",$shop_order_status);
				}else{
					$order_status_id 	= "";
					$order_status 		= implode(",",$shop_order_status);
				}
				
				$order_status			= strlen($order_status) > 0 		?  $order_status 		: '-1';
				$order_status_id		= strlen($order_status_id) > 0 		?  $order_status_id 	: '-1';
				$hide_order_status		= strlen($hide_order_status) > 0 	?  $hide_order_status 	: '-1';
				
				
				$order_status_id 		= "";
				
				
				$default_view			= apply_filters('ic_commerce_detail_page_default_view', "yes");
				$detail_view 			= $this->get_request('detail_view',$default_view,true);
				//$start_date 			= apply_filters('ic_commerce_detail_page_start_date',	$this->constants['start_date'],$detail_view);
				//$end_date 				= apply_filters('ic_commerce_detail_page_end_date',		$this->constants['end_date'],$detail_view);
				$order_status			= apply_filters('ic_commerce_detail_page_selected_order_status', $order_status,$detail_view);
				$onload_search			= apply_filters('ic_commerce_detail_page_onload_search', "yes", $detail_view);
				$page_title 			= apply_filters('ic_commerce_detail_page_page_default_title', __("Detail Search",'icwoocommerce_textdomains'));
				$previous_day			= apply_filters('ic_commerce_detail_page_number_of_previous_day', "0");//-1,-2,-3,-4,-5
				$order_date_field_key	= apply_filters('ic_commerce_detail_page_order_date_field_key', $order_date_field_key);//-1,-2,-3,-4,-5
				$do_action_type			= apply_filters('ic_commerce_detail_page_do_action_type', 'detail_page');				
				$onload_search			= apply_filters('ic_commerce_onload_search', $onload_search, $detail_view);
				
				$publish_order			= "no";
				
				$optionsid				= "per_row_details_page";
				$per_page 				= $this->get_number_only($optionsid,$this->per_page_default);				
								
				$sales_order			= $this->get_request('sales_order',false);	
				$end_date				= $this->get_request('end_date',false);
				$start_date				= $this->get_request('start_date',false);
				$order_status_id		= $this->get_request('order_status_id',$order_status_id,true);//New Change ID 20140918
				$order_status			= $this->get_request('order_status',$order_status,true);//New Change ID 20140918
				$publish_order			= $this->get_request('publish_order',$publish_order,true);//New Change ID 20140918
				$hide_order_status		= $this->get_request('hide_order_status',$hide_order_status,true);//New Change ID 20140918
				$detail_view			= $this->get_request('detail_view',$detail_view,true);
				$product_id				= $this->get_request('product_id','-1',true);
				$category_id			= $this->get_request('category_id','-1',true);
				$adjacents				= $this->get_request('adjacents',3,true);
				$page					= $this->get_request('page',NULL);				
				$page_title				= $this->get_request('page_title', $page_title);
				$order_id				= $this->get_request('order_id',NULL,true);
				$billing_name			= $this->get_request('billing_name',NULL,true);
				$first_name				= $this->get_request('first_name',NULL,true);
				$last_name				= $this->get_request('last_name',NULL,true);
				$product_sku			= $this->get_request('product_sku',-1);
				$variation_sku			= $this->get_request('variation_sku',-1);
				$billing_email			= $this->get_request('billing_email',NULL,true);
				$billing_country		= $this->get_request('billing_country',NULL,true);				
				$payment_method			= $this->get_request('payment_method',NULL,true);
				$order_item_name		= $this->get_request('order_item_name',NULL,true);//for coupon
				$coupon_code			= $this->get_request('coupon_code',NULL,true);
				$coupon_codes			= $this->get_request('coupon_codes',NULL,true);
				$sort_by 				= $this->get_request('sort_by','order_id',true);
				$order_by 				= $this->get_request('order_by','DESC',true);
				$paid_customer 			= $this->get_request('paid_customer','-1',true);
				$coupon_used			= $this->get_request('coupon_used','no',true);
				$month_key				= $this->get_request('month_key',false);
				$order_meta_key			= $this->get_request('order_meta_key','-1',true);
				$count_generated		= $this->get_request('count_generated',0,true);
				$report_title			= $this->get_request('report_title','');
				
				$country_code			= '-1';
				$state_code				= '-1';
				$country_state_code		= $this->get_request('country_state_code',NULL,true);
				
				if($country_state_code and strlen($country_state_code)>0){
					
					if(isset($_REQUEST['country_code'])) $countrycodes[] = $this->get_request('country_code');						
					if(isset($_REQUEST['state_code']))   $statecodes[] 	 = $this->get_request('state_code');
											
					$countrystatecodes = explode(",",$country_state_code);
					
					foreach($countrystatecodes as $key => $countrystatecode){						
						$country_state_codes = explode("-",$countrystatecode);
						$countrycodes[] 	= isset($country_state_codes[0]) ? $country_state_codes[0] : '';
						$statecodes[] 		= isset($country_state_codes[1]) ? $country_state_codes[1] : '';
					}
					
					$countrycodes 				= array_unique($countrycodes);
					$statecodes 				= array_unique($statecodes);
					
					$_REQUEST['country_code']	= $country_code	= implode(",",$countrycodes);					
					$_REQUEST['state_code']		= $state_code	= implode(",",$statecodes);
				}
				
				$country_code					= $this->get_request('country_code',$country_code,true);
				$state_code						= $this->get_request('state_code',$state_code,true);
				
				if($product_id == "all") 		$product_id			= $_REQUEST['product_id'] 		= "-1";
				if($billing_name == "all") 		$billing_name		= $_REQUEST['billing_name'] 	= "-1";
				if($order_status_id == "all") 	$order_status_id	= $_REQUEST['order_status_id'] 	= "-1";				
				
				if($page_title){
					$page_title = str_replace("_"," ",$page_title);
					$page_title = str_replace("-"," ",$page_title);
					$page_title = Ucwords($page_title);
				}
				
				if($month_key && strlen($month_key)>0){
					$month_key 			= strtotime($month_key);
					
					$end_date 		=	date('Y-m-t',$month_key);
					$start_date 	=	date('Y-m-01',$month_key);
					
					$page_title 	= date('M Y',$month_key). " detail reports";
					
				}
							
				if($sales_order && strlen($sales_order)>0){
					if($sales_order == "today"){
						$_REQUEST['end_date'] 		= date_i18n('Y-m-d');
						$_REQUEST['start_date'] 	= date_i18n('Y-m-d');
						$page_title = __("Today's detail reports",'icwoocommerce_textdomains');
					}
					
					if($sales_order == "yesterday"){
						$_REQUEST['end_date'] 		= date('Y-m-d', strtotime('-1 day', strtotime(date_i18n("Y-m-d"))));
						$_REQUEST['start_date'] 	= date('Y-m-d', strtotime('-1 day', strtotime(date_i18n("Y-m-d"))));
						$page_title = __("Yesterday's detail reports",'icwoocommerce_textdomains');
					}
					$timestamp = time();
					if($sales_order == "week"){						
						$current_dayname = date("l");
						$_REQUEST['end_date'] 		= date('Y-m-d',$timestamp);
						$_REQUEST['start_date'] 	= date("Y-m-d",strtotime("last sunday", $timestamp));
						$page_title = __("This week detail reports",'icwoocommerce_textdomains');
					}

					
					if($sales_order == "month"){
						$_REQUEST['end_date'] 		= date('Y-m-d',$timestamp);
						$_REQUEST['start_date'] 	= date('Y-m-01',strtotime('this month'));
						$page_title = __("This month detail reports",'icwoocommerce_textdomains');
					}
					
					if($sales_order == "year"){
						$_REQUEST['end_date'] 		= date('Y-m-d',$timestamp);
						$_REQUEST['start_date'] 	= date('Y-01-01',strtotime('this month'));
						$page_title = __("This year detail reports",'icwoocommerce_textdomains');
					}
					
					if($sales_order == "all"){
						$_REQUEST['end_date'] 		= $this->constants['end_date'];
						$_REQUEST['start_date'] 	= $this->constants['start_date'];
						//$page_title = __("This year detail reports",'icwoocommerce_textdomains');
					}
					
					$end_date						= $this->get_request('end_date',false);
					$start_date						= $this->get_request('start_date',false);
				}
				
				$start_date 						= apply_filters('ic_commerce_detail_page_start_date',	$start_date, $end_date, $previous_day);
				$end_date 							= apply_filters('ic_commerce_detail_page_end_date',		$end_date, $start_date, $previous_day);
				
				if(!$end_date){$end_date = date_i18n('Y-m-d');}				
				if(!$start_date){
					$last_days_orders 		= $previous_day;//-1,-2,-3,-4,-5
					$start_date = date('Y-m-d', strtotime($last_days_orders.' day', strtotime(date_i18n("Y-m-d"))));
				}
				
				$_REQUEST['end_date'] 		= $end_date;
				$_REQUEST['start_date'] 	= $start_date;
				
				if($order_status_id && $order_status_id>0){
					//$page_title = $this->get_order_status_name($order_status_id)." Orders";
					//$page_title = "Product detail of ". $page_title. " category";
				}
				
				if($product_id && $product_id>0){
					$page_title = sprintf(__('Product detail of %s','icwoocommerce_textdomains'), $this->get_product_name($product_id));
				}
				
				if($country_code && $country_code != '-1' && strlen($country_code) > 1 ){
					if($detail_view == "yes")
						$page_title = sprintf(__('Product detail of %s country','icwoocommerce_textdomains'), $this->ic_cr_get_country_name($country_code));
					else
						$page_title = sprintf(__('Order detail of %s country','icwoocommerce_textdomains'), $this->ic_cr_get_country_name($country_code));
				}
				
				if($state_code && $state_code != '-1' && strlen($state_code) > 1){
					if($detail_view == "yes")
						$page_title = sprintf(__('Product detail of %s from %s country','icwoocommerce_textdomains'), $this->get_state($country_code, $state_code),$this->ic_cr_get_country_name($country_code));
					else
						$page_title = sprintf(__('Order detail of %s from %s country','icwoocommerce_textdomains'), $this->get_state($country_code, $state_code),$this->ic_cr_get_country_name($country_code));
				}
				
				if($billing_email && strlen($billing_email) > 1){
					$customer = $this->get_client_name($billing_email, NULL);
					if($detail_view == "yes")
						$page_title = sprintf(__('Product detail of %s','icwoocommerce_textdomains'), Ucwords($customer->billing_name));
					else
						$page_title = sprintf(__('Order detail of %s','icwoocommerce_textdomains'), Ucwords($customer->billing_name));
					
				}
				
				if($order_id && strlen($order_id) > 1){
					
					if(empty($report_title)){
						if($detail_view == "yes")
							$page_title = sprintf(__('Product detail of %s order ID','icwoocommerce_textdomains'), $order_id);
						else
							$page_title = sprintf(__('Order detail of %s order ID','icwoocommerce_textdomains'), $order_id);
					}else{
						$page_title = $report_title;
					}
					
					
					
				}
				
				if($payment_method && strlen($payment_method) > 1){
					if($detail_view == "yes")
						$page_title = sprintf(__('Product detail of %s payment method','icwoocommerce_textdomains'), $this->get_payment_method_name($payment_method));
					else
						$page_title = sprintf(__('Order detail of %s payment method','icwoocommerce_textdomains'), $this->get_payment_method_name($payment_method));
				}
				
				if($coupon_code && strlen($coupon_code) > 1){
					if($detail_view == "yes")
						$page_title = sprintf(__('Coupon detail of %s','icwoocommerce_textdomains'), $coupon_code);
					else
						$page_title = sprintf(__('Coupon detail of %s','icwoocommerce_textdomains'), $coupon_code);
				}
				
				if($coupon_codes && strlen($coupon_codes) > 1){
					if($detail_view == "yes")
						$page_title = sprintf(__('Coupons detail of %s','icwoocommerce_textdomains'), $coupon_codes);
					else
						$page_title = sprintf(__('Coupons detail of %s','icwoocommerce_textdomains'), $coupon_codes);
				}
				
						
				$_REQUEST['page_title'] = $page_title;	
				
				$this->delete_column_option();
				
				?>
					
                    <h2 class="hide_for_print"><?php _e($page_title,'icwoocommerce_textdomains');?></h2>
                    <div id="navigation" class="hide_for_print">
                        <div class="collapsible" id="section1"><?php _e('Custom Search','icwoocommerce_textdomains');?><span></span></div>
                        <div class="container">
                            <div class="content">
                                <div class="search_report_form">
                                    <div class="form_process"></div>
                                    <form action="" name="Report" id="search_order_report" method="post">
                                        <div class="form-table">
                                            <div class="form-group">
                                                <div class="FormRow FirstRow">
                                                    <div class="label-text"><label for="start_date"><?php _e('From Date:','icwoocommerce_textdomains');?></label></div>
                                                    <div class="input-text"><input type="text" value="<?php echo $start_date;?>" id="start_date" name="start_date" readonly maxlength="10" /></div>
                                                </div>
                                                <div class="FormRow">
                                                    <div class="label-text"><label for="end_date"><?php _e('To Date:','icwoocommerce_textdomains');?></label></div>
                                                    <div class="input-text"><input type="text" value="<?php echo $end_date;?>" id="end_date" name="end_date" readonly maxlength="10" /></div>
                                                </div>
                                            </div>
                                            
                                            <?php  do_action("ic_commerce_detail_page_search_form_below_date_fields", $page, $this );?>
                                            
                                            <div class="form-group">
                                                <div class="FormRow FirstRow">
                                                    <div class="label-text"><label for="order_id"><?php _e('Order ID:','icwoocommerce_textdomains');?></label></div>
                                                    <div class="input-text"><input type="text" id="order_id" name="order_id" class="numberonly" value="<?php echo $this->get_request('order_id',NULL,true);?>" /></div>
                                                </div>
                                                <div class="FormRow">
                                                    <div class="label-text"><label for="billing_name"><?php _e('Customer:','icwoocommerce_textdomains');?></label></div>
                                                    <div class="input-text"><input type="text" id="billing_name" name="billing_name" maxlength="20" value="<?php echo $this->get_request('billing_name',NULL,true);?>" /></div>
                                                </div>
                                            </div>
											
											<div class="form-group">
                                                <div class="FormRow FirstRow">
                                                    <div class="label-text"><label for="first_name"><?php _e('First Name:','icwoocommerce_textdomains');?></label></div>
                                                    <div class="input-text"><input type="text" id="first_name" name="first_name" maxlength="20" value="<?php echo $this->get_request('first_name',NULL,true);?>" /></div>
                                                </div>
                                                <div class="FormRow">
                                                    <div class="label-text"><label for="last_name"><?php _e('Last Name:','icwoocommerce_textdomains');?></label></div>
                                                    <div class="input-text"><input type="text" id="last_name" name="last_name" maxlength="20" value="<?php echo $this->get_request('last_name',NULL,true);?>" /></div>
                                                </div>
                                            </div>
                                            
                                            <div class="form-group">
                                                <div class="FormRow FirstRow">
                                                    <div class="label-text"><label for="category_id"><?php _e('Category:','icwoocommerce_textdomains');?></label></div>
                                                    <div class="input-text">
                                                        <?php 
                                                            $category_data = $this->get_category_data();
                                                            $this->create_dropdown($category_data,"category_id[]","category_id","Select All","product_id",'-1', 'object', true, 5);
                                                        ?>                                                        
                                                    </div>
                                                    <span class="detail_view_seciton detail_view_seciton_note"><?php _e("Enable category selection by clicking 'Show Order Item Details'",'icwoocommerce_textdomains');?></span>
                                                </div>
                                                <div class="FormRow">
                                                    <div class="label-text"><label for="product_id"><?php _e('Product:','icwoocommerce_textdomains');?></label></div>
                                                    <div class="input-text">
                                                        <?php 
                                                            $product_data = $this->get_product_data('all');
                                                            $this->create_dropdown($product_data,"product_id[]","product_id","Select All","product_id",$product_id, 'object', true, 5);
                                                        ?>
                                                    </div>
                                                    <span class="detail_view_seciton detail_view_seciton_note"><?php _e("Enable product selection by clicking 'Show Order Item Details'",'icwoocommerce_textdomains');?></span>
                                                </div>
                                            </div>
                                            
											<div class="form-group">          
												<div class="FormRow FirstRow">
													<div class="label-text"><label for="product_sku">Product SKU:</label></div>
													<div class="input-text">
														<?php 
															$product_sku_data = $this->get_product_sku();
															$this->create_dropdown($product_sku_data,"product_sku[]","product_sku","Select All","product_sku details_view_only",'-1', 'object', true, 5);
														?>
													</div>
												</div>
												<div class="FormRow">
													<div class="label-text"><label for="variation_sku">Variation SKU:</label></div>
													<div class="input-text">						
														<?php 
															$variation_sku_data = $this->get_variation_sku();
															$this->create_dropdown($variation_sku_data,"variation_sku[]","variation_sku","Select All","variation_sku variation_fields details_view_only",'-1', 'object', true, 5);
														?>
													</div>
													
												</div>
											</div>
											
                                            <div class="form-group">
                                                <div class="FormRow FirstRow">
                                                    <div class="label-text"><label for="paid_customer"><?php _e('Customer:','icwoocommerce_textdomains');?></label></div>
                                                    <div class="input-text">
                                                        <?php 
                                                            $order_customer = $this->get_order_customer();
															$order_customer = apply_filters('ic_commerce_detail_page_order_customers', $order_customer);
                                                            $this->create_dropdown($order_customer,"paid_customer[]","paid_customer","Select All","product_id",$paid_customer, 'object', true, 5);
                                                        ?>
                                                    </div>
                                                </div>
                                                <div class="FormRow">
                                                    <div class="label-text"><label for="order_status_id"><?php _e('Status:','icwoocommerce_textdomains');?></label></div>
                                                    <div class="input-text">
                                                        <?php
														//New Change ID 20140918
														if($this->constants['post_order_status_found'] == 0 ){					
															$data = $this->ic_get_order_statuses_slug_id('shop_order_status');
                                                            $this->create_dropdown($data,"order_status_id[]","order_status_id","Select All","product_id",$order_status_id, 'object', true, 5);
															
															echo '<input type="hidden" name="order_status[]" id="order_status" value="'.$order_status.'">';
														}else{
															$order_statuses = $this->ic_get_order_statuses();
															if(in_array('trash',$this->constants['hide_order_status'])){
																unset($order_statuses['trash']);
															}
															$this->create_dropdown($order_statuses,"order_status[]","order_status","Select All","product_id",$order_status, 'array', true, 5);
															
															echo '<input type="hidden" name="order_status_id[]" id="order_status_id" value="'.$order_status_id.'">';
														}
                                                        ?>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <?php  do_action("ic_commerce_detail_page_search_form_before_order_by", $this, $page );?>
                                            
                                            <div class="form-group">
                                                <div class="FormRow FirstRow">
                                                    <div class="label-text"><label for="billing_email"><?php _e('Email:','icwoocommerce_textdomains');?></label></div>
                                                    <div class="input-text"><input type="text" id="billing_email" name="billing_email" class="regular-text" maxlength="100" value="<?php echo $billing_email;?>" /></div>
                                                </div>
                                                
                                                <div class="FormRow">
                                                    <div class="label-text" style="padding-top:0px;"><label for="sort_by"><?php _e('Order By:','icwoocommerce_textdomains');?></label></div>
                                                    <div style="padding-top:0px;">
                                                    	 <?php
                                                            
															$sorting_list = array("order_id" => __("Order ID",'icwoocommerce_textdomains'),"billing_name" => __("Name",'icwoocommerce_textdomains'),'billing_email'=> __('Email','icwoocommerce_textdomains'), "order_date" => __("Date",'icwoocommerce_textdomains'), "status" => __("Status",'icwoocommerce_textdomains'), "billing_country" => __("Country",'icwoocommerce_textdomains'));
                                                            $sorting_list = apply_filters("ic_commerce_detail_page_sorting_list", $sorting_list);
															$this->create_dropdown($sorting_list,"sort_by","sort_by",NULL,"sort_by",$sort_by, 'array');
															
															$order_list = array("ASC" => __("Ascending",'icwoocommerce_textdomains'), "DESC" => __("Descending",'icwoocommerce_textdomains'));
															$order_list = apply_filters("ic_commerce_detail_page_order_list", $order_list);
                                                            $this->create_dropdown($order_list,"order_by","order_by",NULL,"order_by",$order_by, 'array');
                                                        ?>
                                                    </div>
                                                    
                                                </div>
                                                
                                            </div>
                                            
                                            <?php  do_action("ic_commerce_detail_page_search_form_after_order_by", $this);?>
                                           	
											<div class="form-group">
                                                <div class="FormRow FirstRow">
                                                   <div class="label-text"><label for="coupon_codes"><?php _e("Coupon Codes:",'icwoocommerce_textdomains');?></label></div>
                                                        <div class="input-text">
                                                        	<?php
																$coupon_code_data = $this->get_coupon_codes();
																$this->create_dropdown($coupon_code_data,"coupon_codes[]","coupon_codes","Select All","coupon_codes",$coupon_codes, 'object', true, 5);
                                                            ?>  
                                                        </div>
                                                </div>
                                                
                                                <div class="FormRow checkbox">
                                                    <div class="label-text" style="padding-top:0px;"><label for="coupon_used"><?php _e('Coupon Used Only:','icwoocommerce_textdomains');?></label></div>
                                                    <div style="padding-top:0px;"><input type="checkbox" name="coupon_used" id="coupon_used" value="yes" <?php if($coupon_used == "yes"){ echo ' checked="checked"';}?> /></div>
                                                </div>
                                            </div>
                                            <div class="form-group">                                            
												<div class="FormRow">
													<div class="label-text"><label for="payment_method_title"><?php _e("Payment Method:",'icwoocommerce_textdomains');?></label></div>
													<div class="input-text">
														<?php
															
															$data = $this->get_payment_method_dropdonw_data();
															$this->create_dropdown($data,"payment_method_title[]","payment_method_title","Select One","payment_method_title",$payment_method, 'object', false, 5);
														?>  
													</div>
												</div>
											</div>
                                            <div class="form-group">
                                                <div class="FormRow FirstRow checkbox">
                                                    <div class="label-text" style="padding-top:0px;"><label for="detail_view"><?php _e('Show Order Item Details:','icwoocommerce_textdomains');?></label></div>
                                                    <div style="padding-top:0px;"><input type="checkbox" name="detail_view" id="detail_view" value="yes" <?php if($detail_view == "yes"){ echo ' checked="checked"';}?> /></div>
                                                </div>
                                            </div>
                                            
                                            <div class="form-group">
                                                <div class="FormRow" style="width:100%">
                                                	<?php
                                                    	$hidden_fields = array();
														$hidden_fields['hide_order_status'] 	=  $hide_order_status;
														$hidden_fields['hide_order_status'] 	=  $hide_order_status;
														$hidden_fields['publish_order'] 		=  $publish_order;
														$hidden_fields['order_item_name'] 	 	=  $order_item_name;
														$hidden_fields['coupon_code'] 		 	=  $coupon_code;
														//$hidden_fields['coupon_codes'] 		 	=  $coupon_codes;
														//$hidden_fields['payment_method'] 	 	=  $payment_method;														
														$hidden_fields['limit'] 				=  $this->get_request('limit',$per_page,true);
														$hidden_fields['p'] 					=  $this->get_request('p',1,true);
														$hidden_fields['action'] 				=  $this->constants['plugin_key'].'_wp_ajax_action';
														$hidden_fields['page'] 				 	=  $this->get_request('page',$page,true);
														$hidden_fields['admin_page'] 		 	=  $page;
														$hidden_fields['ic_admin_page'] 		=  $this->get_request('ic_admin_page',$page,true);
														$hidden_fields['adjacents'] 			=  $adjacents;
														$hidden_fields['purchased_product_id'] 	=	"-1";
														$hidden_fields['do_action_type'] 	 	=  $this->get_request('do_action_type',$do_action_type,true);
														$hidden_fields['page_title'] 		 	=  $page_title;
														$hidden_fields['total_pages'] 		 	=  $this->get_request('total_pages',0,true);
														$hidden_fields['variation_id'] 		 	=  $this->get_request('variation_id','-1',true);
														$hidden_fields['variation_only_2']    	=  $this->get_request('variation_only','-1',true);														
														$hidden_fields['amount_greater_zero']  	=  $this->get_request('amount_greater_zero','',true);														
														$hidden_fields['count_generated'] 		=  $count_generated;
														$hidden_fields['date_format'] 	 		=  $this->get_request('date_format',get_option('date_format'),true);
														$hidden_fields['page_name'] 			=  $this->get_request('page_name','all_detail',true);
														$hidden_fields['onload_search'] 		=  $this->get_request('onload_search',$onload_search,true);
														$hidden_fields['order_item_id'] 		=  $this->get_request('order_item_id','-1',true);
														$hidden_fields['order_date_field_key']  =  $this->get_request('order_date_field_key',$order_date_field_key,true);
														//$hidden_fields['order_meta_key'] 		=  '-1';
														//$hidden_fields['country_code'] 		=  $country_code;
														//$hidden_fields['state_code'] 			=  $state_code;														
														$hidden_fields = apply_filters('ic_commerce_detail_page_search_form_hidden_fields', $hidden_fields, $page);
														echo $this->create_search_form_hidden_fields($hidden_fields);									
													?>                                                    
                                                    <span class="submit_buttons">
                                                    	<input name="ResetForm" id="ResetForm" class="onformprocess" value="<?php _e("Reset",'icwoocommerce_textdomains');?>" type="reset"> 
                                                    	<input name="button_customize_columns" id="button_customize_columns" class="onformprocess" value="<?php _e("Customize Column",'icwoocommerce_textdomains');?>" type="button"> 
														<input name="SearchOrder" id="SearchOrder" class="onformprocess searchbtn btn_margin" value="<?php _e("Search",'icwoocommerce_textdomains');?>" type="submit"> &nbsp; &nbsp; &nbsp; <span class="ajax_progress"></span>
													</span>
                                                </div>
                                            </div>                                                
                                        </div>
                                    </form>
                                    <div class="form-group">
                                        <div class="tr_customize_column search_by_normal_fields" <?php if($detail_view == "yes"){ echo ' style="display:none"';}?>> 
                                            <p><?php _e("Normal View Column: ",'icwoocommerce_textdomains');?><span class="select_all_checkbox" data-type="save_normal_column" data-table="widefat_normal_table"><?php _e("Select All",'icwoocommerce_textdomains');?></span></span></p>
                                            <?php $this->create_checkbox("save_normal_column","normal_view","_order_checkbox");?>
                                        </div>
                                        
                                        <div class="tr_customize_column search_by_details_fields" <?php if($detail_view == "no"){ echo ' style="display:none"';}?>>
                                            <p><?php _e("Order Item Details Column: ",'icwoocommerce_textdomains');?><span class="select_all_checkbox" data-type="save_detail_column" data-table="widefat_detial_table"><?php _e("Select All",'icwoocommerce_textdomains');?></span></span></p>
                                            <?php $this->create_checkbox("save_detail_column","details_view","order_checkbox");?>
                                        </div>
                                    </div>
                                    <?php
                                    	//echo json_encode($product_data);
									?>
                                    <script type="text/javascript">
                                    	jQuery(document).ready(function($) {
											<?php												
												$product_category = $this->get_product_category();
												$json_product_category = json_encode($product_category);
												
												$json_all_product = json_encode($product_data);
											?>
											
											ic_commerce_vars['json_product_category'] 	= <?php echo $json_product_category;?>;
											ic_commerce_vars['json_all_product'] 		= <?php echo $json_all_product;?>;
											
											ic_commerce_vars['product_id'] 				= "<?php echo $product_id	== '-1' ? '-2': $product_id;?>";
											ic_commerce_vars['category_id'] 			= "<?php echo $category_id	== '-1' ? '-2': $category_id;?>";											
											
											create_dropdown(ic_commerce_vars['json_product_category'],ic_commerce_vars['json_all_product'],"product_id",Array(ic_commerce_vars['category_id']),Array(ic_commerce_vars['product_id']),'array');
											$('#category_id').change(function(){
												var parent_id = $(this).val();
												if(parent_id == null) parent_id = Array("-1");
												create_dropdown(ic_commerce_vars['json_product_category'],ic_commerce_vars['json_all_product'],"product_id",parent_id,Array('-2'),"array");
											});
											
											$('#ResetForm').click(function(){
												create_dropdown(ic_commerce_vars['json_product_category'],ic_commerce_vars['json_all_product'],"product_id",Array(ic_commerce_vars['category_id']),Array(ic_commerce_vars['product_id']),'array');
												
											});
											
											<?php do_action('ic_commerce_detail_page_jquery_document_ready');?>
										});
                                    </script>
                                    <style type="text/css">
                                    	th.invoice_action{
											width:100px;
										}
										
										td.invoice_action{
											text-align:center;
										}
										.widefat.summary_table{
											width:auto;
										}
										.widefat.summary_table td, .widefat.summary_table th{
											text-align:right;
										}
										.iccommercepluginwrap .form-group.dynamic_fields{
											margin-bottom:0;
										}						
										.iccommercepluginwrap .form-group.dynamic_fields .FirstRow{
											margin-bottom:15px;
										}
										.iccommercepluginwrap .form-group.dynamic_fields .SecondRow{
											margin-bottom:15px;
										}										
										<?php
											$report_name	= $this->get_request('report_name','');
											$columns 		= $this->grid_columns("normal_view");
											echo $this->get_pdf_style_align($columns,'right','.iccommercepluginwrap ','', "normal_view");
											
											$columns 		= $this->grid_columns("details_view");
											echo $this->get_pdf_style_align($columns,'right','.iccommercepluginwrap ','', "details_view");
											
											do_action('ic_commerce_detail_page_style_type_css');
										?>
                                    </style>
                                </div>
                            </div>
                        </div>
                        <div class="clearfix"></div>
                    </div>
                    <div class="table table_shop_content search_report_content hide_for_print">
						<?php if($onload_search == "no") {echo apply_filters('ic_commerce_onload_search_text', '');}?>
                    </div>
                    <div id="search_for_print_block" class="search_for_print_block">
                    	<?php				
                           
							/*if($detail_view == "yes"){
                               	$this->ic_commerce_custom_report_detail('all_row', $columns);
                            }else{
                               $this->ic_commerce_custom_report_normal('all_row', $columns);
                            }*/
							
                        ?>
                    </div>
                       <?php
							$admin_page 			= $this->get_request('page');
							//$admin_page_url 		= get_option('siteurl').'/wp-admin/admin.php';//Commented not work SSL admin site 20150212
							$admin_page_url 		= $this->constants['admin_page_url'];//Added SSL fix 20150212
                        	$mngpg 					= $admin_page_url.'?page='.$admin_page ;
							$billing_information 	= $this->get_setting('billing_information',$this->constants['plugin_options'], 0);
							$shipping_information 	= $this->get_setting('shipping_information',$this->constants['plugin_options'], 0);
							$logo_image 			= $this->get_setting('logo_image',$this->constants['plugin_options'], '');
							$report_title 			= $this->get_setting('report_title',$this->constants['plugin_options'], '');
							$company_name 			= $this->get_setting('company_name',$this->constants['plugin_options'], '');
							$page_title				= $this->get_request('page_title',NULL,true);							
							
							$set_report_title		= $report_title;							
							if($page_title) 		$page_title = " (".$page_title.")";							
							$report_title 			= $report_title.$page_title;
						?>
                        <div id="export_csv_popup" class="popup_box">
                            <h4><?php _e("Export to CSV - Additional Information",'icwoocommerce_textdomains');?></h4>
                            <a class="popup_close" title="Close popup"></a>
                            <div class="popup_content">                        	
                            <form id="<?php echo $admin_page ;?>_csv_popup_form" class="<?php echo $admin_page ;?>_csv_popup_form" action="<?php echo $mngpg;?>" method="post">
                                <div class="popup_csv_hidden_fields popup_hidden_fields"></div>
                                
                                 <table class="popup_form_table">
                                    <tr>
                                        <th><label for="billing_information"><?php _e("Billing Information:",'icwoocommerce_textdomains');?></label></th>
                                        <td><input id="billing_information" name="billing_information" value="1" type="checkbox"<?php if($billing_information == 1) echo ' checked="checked"';?> /></td>
                                    </tr>
                                    <tr>
                                        <th><label for="shipping_information"><?php _e("Shipping Information:",'icwoocommerce_textdomains');?></label></th>
                                        <td><input id="shipping_information" name="shipping_information" value="1" type="checkbox"<?php if($shipping_information == 1) echo ' checked="checked"';?>></td>
                                    </tr>
                                   	<?php do_action('ic_commerce_export_csv_popup_extra_option',$page);?>
                                    <tr>
                                        <td colspan="2"><input type="submit" name="<?php echo $admin_page ;?>_export_csv" class="onformprocess button_popup_close" value="<?php _e("Export to CSV",'icwoocommerce_textdomains');?>" /></td>
                                    </tr>                                
                                </table>
                            </form>
                            <div class="clear"></div>
                            </div>
                        </div>
                        <div id="export_pdf_popup" class="popup_box export_pdf_popup">
                            <a class="popup_close" title="Close popup"></a>
                            <h4><?php _e("Export to PDF",'icwoocommerce_textdomains');?></h4>
                            <div class="popup_content">
                            <form id="<?php echo $admin_page ;?>_pdf_popup_form" class="<?php echo $admin_page ;?>_pdf_popup_form" action="<?php echo $mngpg;?>" method="post">
                                <div class="popup_pdf_hidden_fields popup_hidden_fields"></div>
                                 <table class="form-table">
                                    <tr>
                                        <th><label for="company_name_pdf"><?php _e("Company Name:",'icwoocommerce_textdomains');?></label></th>
                                        <td><input id="company_name_pdf" name="company_name" value="<?php echo $company_name;?>" type="text" class="textbox"></td>
                                    </tr>
                                    <tr>
                                        <th><label for="report_title_pdf"><?php _e("Report Title:",'icwoocommerce_textdomains');?></label></th>
                                        <td><input id="report_title_pdf" name="report_title" value="<?php echo $report_title;?>" data-report_title="<?php echo $set_report_title;?>" type="text" class="textbox"></td>
                                    </tr>
                                    <?php if($logo_image):?>
                                    <tr>
                                        <th><label for="display_logo_pdf"><?php _e("Show Logo:",'icwoocommerce_textdomains');?></label></th>
                                        <td class="inputfield"><input id="display_logo_pdf" name="display_logo" value="1" type="checkbox"<?php if($logo_image) echo ' checked="checked"';?>></td>
                                    </tr>
                                    <?php endif;?>
                                     <tr>
                                        <th><label for="display_date_pdf"><?php _e("Show Date:",'icwoocommerce_textdomains');?></label></th>
                                        <td class="inputfield"><input id="display_date_pdf" name="display_date" value="1" type="checkbox" checked="checked"></td>
                                    </tr>
                                    <?php do_action('ic_commerce_export_pdf_popup_extra_option',$page);?>
                                    <tr>
									<th><label for="orientation_portrait_pdf"><?php _e("PDF Orientation:",'icwoocommerce_textdomains');?></label></th>
									<td class="inputfield">
                                    <label for="orientation_portrait_pdf"><input id="orientation_portrait_pdf" name="orientation_pdf" value="portrait" type="radio"> <?php _e("Portrait",'icwoocommerce_textdomains');?></label>
                                    <label for="orientation_landscape_pdf"><input id="orientation_landscape_pdf" name="orientation_pdf" value="landscape" type="radio" checked="checked"> <?php _e("Landscape",'icwoocommerce_textdomains');?></label>
                                    
                                    </td>
                                    </tr>
                                    <tr>
                                        <th><label for="paper_size_pdf"><?php _e("Paper Size:",'icwoocommerce_textdomains');?></label></th>
                                        <td class="inputfield">
                                        <?php
                                            $paper_sizes = $this->get_pdf_paper_size();
                                            $this->create_dropdown($paper_sizes,"paper_size","paper_size2","","paper_size2",'letter', 'array', false, 5);
                              ?>                                    
                                    </tr>
                                    <tr>
                                        <td colspan="2">                                                                                
                                        <input type="submit" name="<?php echo $admin_page ;?>_export_pdf" class="onformprocess button_popup_close" value="<?php _e("Export to PDF:",'icwoocommerce_textdomains');?>" /></td>
                                    </tr>                                
                                </table>
                                <input type="hidden" name="display_center" value="center_header" />
                                <input type="hidden" name="pdf_keywords" value="" />
                                <input type="hidden" name="pdf_description" value="" />
                            </form>
                            <div class="clear"></div>
                            </div>
                        </div>						
                        <div id="export_print_popup" class="popup_box export_pdf_popup export_print_popup">
                            <a class="popup_close" title="Close popup"></a>
                            <h4>Export to PDF</h4>
                            <div class="popup_content">
                            <form id="<?php echo $admin_page ;?>_print_popup_form" class="<?php echo $admin_page ;?>_pdf_popup_form" action="<?php echo $mngpg;?>" method="post">
                                <div class="popup_print_hidden_fields popup_hidden_fields2"></div>
                                 <table class="form-table">
                                    <tr>
                                        <th><label for="company_name_print"><?php _e("Company Name:",'icwoocommerce_textdomains');?></label></th>
                                        <td><input id="company_name_print" name="company_name" value="<?php echo $company_name;?>" type="text" class="textbox"></td>
                                    </tr>
                                    <tr>
                                        <th><label for="report_title_print"><?php _e("Report Title:",'icwoocommerce_textdomains');?></label></th>
                                        <td><input id="report_title_print" name="report_title" value="<?php echo $report_title;?>" data-report_title="<?php echo $set_report_title;?>" type="text" class="textbox"></td>
                                    </tr>
                                    <?php if($logo_image):?>
                                    <tr>
                                        <th><label for="display_logo_print"><?php _e("Print Logo:",'icwoocommerce_textdomains');?></label></th>
                                        <td class="inputfield"><input id="display_logo_print" name="display_logo" value="1" type="checkbox"<?php if($logo_image) echo ' checked="checked"';?>></td>
                                    </tr>
                                    <?php endif;?>
                                     <tr>
                                        <th><label for="display_date_print"><?php _e("Print Date:",'icwoocommerce_textdomains');?></label></th>
                                        <td class="inputfield"><input id="display_date_print" name="display_date" value="1" type="checkbox" checked="checked"></td>
                                    </tr>
                                    <?php do_action('ic_commerce_export_print_popup_extra_option',$page);?>
                                    <tr>
                                        <td colspan="2"><input type="button" name="<?php echo $admin_page ;?>_export_print" class="onformprocess button_popup_close search_for_print" value="<?php _e("Print",'icwoocommerce_textdomains');?>" data-form="popup"  data-do_action_type="<?php echo $do_action_type;?>_for_print" /></td>
                                    </tr>                                
                                </table>
                                <input type="hidden" name="display_center" value="1" />
                            </form>
                            <div class="clear"></div>
                            </div>
                        </div>
                        <div class="popup_mask"></div>                   
						<?php do_action("ic_commerce_details_page_footer_area",$page);echo $this->delete_ic_commerce();?>                        
						<?php
		}		
		
		function ic_commerce_custom_report_normal($type = 'total_row', $columns = array(), $total_columns = array()){
				$order_items 		= $this->ic_commerce_custom_report_normal_query($type, $columns,$total_columns);				
				if(count($order_items) > 0):
				

						$TotalOrderCount 	= 0;
						$TotalAmount 		= 0;
						$TotalShipping		= 0;
						$summary 			= $this->ic_commerce_custom_report_normal_query('total_row', $columns,$total_columns);
						$total_pages		= isset($summary['total_row_count']) ? $summary['total_row_count'] : 0;
						$admin_url			= admin_url($this->constants['plugin_parent']['order_detail_url']);
						//$columns 			= $this->grid_columns("normal_view");
						$key 				= $this->get_column_key("save_normal_column");
						$active_columns 	= $this->default_active($key, false);
						$zero				= $this->price(0);
						$plugin_key 		= $this->constants['plugin_key'];//Added 20150205
						$pdf_invoice		= admin_url("admin.php?page={$plugin_key}_details_page");//Modified 20150205
						$zero_prize			= array();					
						$variation_list		= array();
						$order_item_sku		= array();
						$category_names		= array();
						
						//$this->print_array($_REQUEST);
						
						$columns			= apply_filters("ic_commerce_normal_view_grid_columns",$columns,$total_columns);
						$order_items		= apply_filters("ic_commerce_normal_view_data_grid",$order_items,$columns, $zero, $type,$total_columns);
						
						$grid_object		= $this->get_grid_object();//Added 20150223
						$order_items		= $grid_object->create_grid_items($columns,$order_items);//Added 20150223
						
						$order_items		= apply_filters("ic_commerce_normal_view_data_grid_after_create_grid_items",$order_items,$columns, $zero, $type,$total_columns);
						
						$price_columns		= apply_filters("ic_commerce_price_columns",array());
						
						///$this->print_array($order_items);
						
						$this->print_header($type, $columns );	
						if($type != 'all_row'):
                        	echo '<div class="top_buttons">';
							$this->export_to_csv_button('top', $total_pages, $summary);
							echo '<div class="clearfix"></div></div>';
                       	else: 
							$this->back_print_botton('top');
						endif;?>                     
						<table style="width:100%" class="widefat widefat_normal_table" cellpadding="0" cellspacing="0">
							<thead>
								<tr class="first">
                                	<?php 
										$cells_status = array();
										$output = "";
										foreach($columns as $key => $value):
											$td_class = $key;
											$td_width = "";
											switch($key):
												case "order_shipping":
												case "order_shipping_tax":
												case "order_tax":
												case "gross_amount":
												case "order_discount":
												case "cart_discount":
												case "total_discount":
												case "total_tax":
												case "order_total":
												case "item_count":
												case "transaction_id":
												case "order_item_count":
												case "customer_id"://New Change ID 20150227
												case "refund_amount":
												case "order_refund_amount":
												case "part_order_refund_amount":
												case "bottle_quantity":
												case "bottle_per_case":
													$td_class .= " amount";												
													break;
												case "order_date":
												case "completed_date":
													$date_format		= get_option( 'date_format' );
													break;
												case "customer_username"://New Change ID 20150227
													$user_url		= admin_url("user-edit.php")."?user_id=";
												default;
													$th_value = $value;
													break;
											endswitch;
											$th_value 			= $value;
											$display 			= (isset($active_columns[$key]) && $active_columns[$key] == 1) ? '' : ' style="display:none"';
											$cells_status[$key] = $display;
											$output 			.= "\n\t<th class=\"{$td_class}\"{$display}>{$th_value}</th>";											
										endforeach;
										echo $output ;
										?>
								</tr>
							</thead>
							<tbody>
								<?php					
								foreach ( $order_items as $key => $order_item ) {
									
									$order_id		= isset($order_item->order_id) ? $order_item->order_id : 0;
									$TotalAmount 	= $TotalAmount + $order_item->order_total;
									$TotalShipping 	= $TotalShipping + $order_item->order_shipping;
									$zero_prize[$order_item->order_currency] = isset($zero_prize[$order_item->order_currency]) ? $zero_prize[$order_item->order_currency] : $this->price(0, array('currency' => $order_item->order_currency));
									$TotalOrderCount++;
									
									if($key%2 == 1){$alternate = "alternate ";}else{$alternate = "";};
									?>
									<tr class="<?php echo $alternate."row_".$key;?>">
                                    	<?php
											foreach($columns as $key => $value):
												$td_class = $key;
												$td_style = $cells_status[$key];
												$td_value = "";
												
												switch($key):
												  case "order_id":
														$td_value = '<a href="'.$admin_url.$order_item->order_id.'" target="'.$order_item->order_id.'_blank">' . $order_item->order_id  . '</a>';
														break;
													case "billing_name":
														$td_value = isset($order_item->$key) ? $order_item->$key : '';
														$td_value = ucwords($td_value);
														break;
													case "billing_email":
														$td_value = isset($order_item->$key) ? $order_item->$key : '';
														$td_value = $this->emailLlink($td_value,false);
														break;
													case "customer_username"://New Change ID 20150227
														$customer_id 	= isset($order_item->customer_id) ? $order_item->customer_id : 0;
														$td_value 		= $customer_id > 0 ? '<a href="'.$user_url.$customer_id.'" target="'.$customer_id.'_blank">' . $order_item->customer_username .'</a>' : $order_item->customer_username;
														break;
													case "order_status"://New Change ID 20140918
													case "order_status_name"://New Change ID 20150225
														$td_value = isset($order_item->$key) ? $order_item->$key : '';
														$td_value = '<span class="order-status order-status-'.sanitize_title($td_value).'">'.ucwords(__($td_value, 'icwoocommerce_textdomains')).'</span>';
														break;
													case "item_count":
													case "transaction_id":
													case "order_item_count":
													case "customer_id"://New Change ID 20150227
														$td_value = isset($order_item->$key) ? $order_item->$key : '';
														$td_class .= " amount";
														break;
													case "order_shipping":
													case "order_shipping_tax":
													case "order_tax":
													case "gross_amount":
													case "order_discount":
													case "cart_discount":
													case "total_discount":
													case "total_tax":
													case "order_total":
													case "refund_amount":
													case "order_refund_amount":
													case "part_order_refund_amount":
														$td_value = isset($order_item->$key) ? $order_item->$key : 0;
														$td_value = $td_value == 0 ? $zero_prize[$order_item->order_currency] : $this->price($td_value, array('currency' => $order_item->order_currency));
														$td_class .= " amount";
														break;												
													case "order_date":
													case "completed_date":
														$td_value = isset($order_item->$key) ? $order_item->$key : '';
														$td_value = empty($td_value) ? '' : date($date_format,strtotime($order_item->$key));
														break;
													
													case "invoice_action":														
														$td_value = $this->invoice_action_btn($pdf_invoice, $order_id);
														break;																											
													default:
														if(isset($price_columns[$key])){
															$td_value = isset($order_item->$key) ? $order_item->$key : 0;
															$td_value = $td_value == 0 ? $zero_prize[$order_item->order_currency] : $this->price($td_value, array('currency' => $order_item->order_currency));
															$td_class .= " amount";																
														}else{
															$td_value = isset($order_item->{$key}) ? $order_item->{$key} : '';
														}
														//$td_value = isset($order_item->{$key}) ? $order_item->{$key} : '';
														break;
												endswitch;
												$td_content = "<td class=\"{$td_class}\"{$td_style}>{$td_value}</td>\n";
												echo $td_content;
											endforeach;                                        	
										?>
									</tr>
									<?php 
								} ?>
							</tbody>           
						</table>
						 <?php 
						 if($type != 'all_row') $this->total_count($TotalOrderCount, $TotalAmount, $total_pages,$summary); else $this->back_print_botton('bottom');
						 $detail_view 		= $this->get_request('detail_view','no');
						 $zero				= $this->price(0);

						 echo $this->result_grid($detail_view,$summary,$zero,$total_columns, $price_columns);
						 ?>
				<?php else:?>        
						<div class="order_not_found"><?php _e('No orders found','icwoocommerce_textdomains'); ?></div>
				<?php endif;?>
			<?php
		}
		
		var $categories			= array();
		var $stored_variations	= array();
		function ic_commerce_custom_report_detail($type = 'total_row', $columns = array(), $total_columns = array()){
				global $wpdb;
			
				$order_items 		= $this->ic_commerce_custom_report_detail_query($type, $columns, $total_columns);
				if(count($order_items) > 0):
					
						$TotalOrderCount 	= 0;
						$TotalAmount 		= 0;
						$summary 			= $this->ic_commerce_custom_report_detail_query('total_row', $columns, $total_columns);
						$total_pages		= isset($summary['total_row_count']) ? $summary['total_row_count'] : 0;
						$columns 			= $this->grid_columns("details_view");
						$amount 			= array("Quantity","Price");
						$product_columns 	= array("category_name", "product_name", "product_quantity", "total_price");
						$key 				= $this->get_column_key("save_detail_column");
						$active_columns 	= $this->default_active($key, true);					
						$show_product_row	= '';
						$zero				= $this->price(0);
						$plugin_key 		= $this->constants['plugin_key'];//Added 20150205

						$pdf_invoice		= admin_url("admin.php?page={$plugin_key}_details_page");//Modified 20150205
						$zero_prize			= array();
						$variation_list		= array();
						$order_item_sku		= array();
						$category_names		= array();
						
						$columns			= apply_filters("ic_commerce_details_view_grid_columns",$columns, $total_columns);						
						$order_items		= apply_filters("ic_commerce_details_view_data_grid",$order_items,$columns, $zero, $type, $total_columns);
												
						$grid_object		= $this->get_grid_object();//Added 20150223
						$order_items		= $grid_object->create_grid_items($columns,$order_items);//Added 20150223
						
						$order_items		= apply_filters("ic_commerce_details_view_data_grid_after_create_grid_items",$order_items,$columns, $zero, $type, $total_columns);
						
						
						
						
						$order_columns		= $this->details_view_columns("order_columns");
						$product_columns	= $this->details_view_columns("product_columns");
						
						$price_columns		= apply_filters("ic_commerce_price_columns",array());
						
						$this->print_header($type, $columns );
						if($type != 'all_row'):
                        	echo '<div class="top_buttons">';
							$this->export_to_csv_button('top', $total_pages, $summary);
							echo '<div class="clearfix"></div></div>';
                       	else: 
							$this->back_print_botton('top');
						endif;?>
						<table style="width:100%" class="widefat widefat_detial_table">
							<thead>
								<tr class="first">
                                	<?php 
										$cells_status = array();
										$output = "";
										foreach($columns as $key => $value):
											$td_class = $key;
											$td_width = "";
											switch($key):
												case "product_rate":
												case "total_price":
												case "product_quantity":
												case "customer_id"://New Change ID 20150227
												case "total_discount":
												case "sold_rate":
												case "difference_rate":
												case "item_amount":

												case "item_discount":
												//case "part_order_refund_amount":
												//case "bottle_quantity":
												//case "bottle_per_case":
													$td_class .= " amount";												
													break;
												case "order_date":
													$date_format		= get_option( 'date_format' );
													break;
												case "customer_username"://New Change ID 20150227
													//$wp_http_referer = wp_get_referer();
													//$user_url		= admin_url("user-edit.php?wp_http_referer={$wp_http_referer}")."&user_id=";//Modified 20150205
													$user_url		= admin_url("user-edit.php")."?user_id=";//Modified 20150205

												default;
													$th_value = $value;
													break;
											endswitch;
											$th_value 			= $value;
											$display 			= (isset($active_columns[$key]) && $active_columns[$key] == 1) ? '' : ' style="display:none"';
											$cells_status[$key] = $display;
											$output 			.= "\n\t<th class=\"{$td_class}\"{$display}>{$th_value}</th>";											
										endforeach;
										echo $output ;
										?>
								</tr>
							</thead>
							<tbody>
							<?php
							$last_order_id = 0;
							$alt_order_id = 0; 
							$alternate = "alternate ";
							$TotalAmount = 0;
							$TotalOrderCount = 0;
							$product_type = array("simple","variable");
							$admin_url = admin_url($this->constants['plugin_parent']['order_detail_url']);
							foreach ( $order_items as $key => $order_item ) :
									$order_id		=  isset($order_item->order_id) ? $order_item->order_id : 0;
									$TotalAmount 	=  $TotalAmount + $order_item->total_price;
									$zero_prize[$order_item->order_currency] = isset($zero_prize[$order_item->order_currency]) ? $zero_prize[$order_item->order_currency] : $this->price(0, array('currency' => $order_item->order_currency));
									if($last_order_id == $order_item->order_id){
										$alternate = "alternate ";
										?>
										<tr class="product_row <?php echo $alternate."row_".$key;?>"<?php echo $show_product_row;?>>
                                        	<?php $this->detail_report_product_row($columns,$order_item,$cells_status,$zero,$zero_prize,$order_columns,$price_columns,$price_columns);?>
										</tr>								
										<?php							
									}else{
										$alternate = "";
										$TotalOrderCount++;
										?>
										<tr class="<?php echo "row_".$key;?>">
											<?php
												foreach($columns as $key => $value):
													$td_class = $key;
													$td_style = $cells_status[$key];
													$td_value = "";
													switch($key):
														case "order_id":
															$td_value = '<a href="'.$admin_url.$order_item->order_id.'" target="'.$order_item->order_id.'_blank">' . $order_item->order_id  . '</a>';
															break;
														case "billing_name":
															$td_value = isset($order_item->$key) ? $order_item->$key : '';
															$td_value = ucwords($td_value);
															break;
														case "billing_email":
															$td_value = isset($order_item->$key) ? $order_item->$key : '';
															$td_value = $this->emailLlink($td_value,false);
															break;
														case "customer_username":
															$customer_id 	= isset($order_item->customer_id) ? $order_item->customer_id : 0;
															$td_value 		= $customer_id > 0 ? '<a href="'.$user_url.$customer_id.'" target="'.$customer_id.'_blank">' . $order_item->customer_username .'</a>' : $order_item->customer_username;
															break;
														case "order_status"://New Change ID 20140918
														case "order_status_name"://New Change ID 20150225
															$td_value = isset($order_item->$key) ? $order_item->$key : '';
															$td_value = '<span class="order-status order-status-'.sanitize_title($td_value).'">'.ucwords(__($td_value, 'icwoocommerce_textdomains')).'</span>';
															break;
														case "item_count":
														case "transaction_id":
														case "customer_id"://New Change ID 20150227
															$td_value = $order_item->$key;
															$td_class .= " amount";
															break;
														case "order_shipping":
														case "order_shipping_tax":
														case "order_tax":
														case "gross_amount":
														case "order_discount":
														case "cart_discount":
														case "total_discount":
														case "total_tax":
														case "order_total":
														case "order_refund_amount":
														case "part_order_refund_amount":
															$td_value = isset($order_item->$key) ? $order_item->$key : 0;
															$td_value = $td_value == 0 ? $zero_prize[$order_item->order_currency] : $this->price($td_value, array('currency' => $order_item->order_currency));
															$td_class .= " amount";
															break;													
														case "completed_date":
														case "order_date"://New Change ID 20140918
															$td_value = isset($order_item->$key) ? date($date_format,strtotime($order_item->$key)) : '';
															break;
														case "invoice_action":														
															$td_value = $this->invoice_action_btn($pdf_invoice, $order_id);
															break;
														case "product_quantity":
														case "category_name":
														case "product_name":
														case "product_sku":
														case "variation_sku":
														case "order_product_sku":														
														case "product_variation":
														case "product_rate":
														case "total_price":
														case "product_id":
														case "sold_rate":
														case "difference_rate":
														case "item_amount":
														case "item_discount":
														case "part_order_refund_amount":
														case "bottle_quantity":
														case "bottle_per_case":
															$td_value = '';
															break;
														case "billing_phone":
															$td_value = isset($order_item->$key) ? $order_item->$key : '';
															break;
														default:
															if(isset($product_columns[$key])){
																$td_value = '';
															}else if(isset($price_columns[$key])){
																$td_value = isset($order_item->$key) ? $order_item->$key : 0;
																$td_value = $td_value == 0 ? $zero_prize[$order_item->order_currency] : $this->price($td_value, array('currency' => $order_item->order_currency));
																$td_class .= " amount";																
															}else{
																$td_value = isset($order_item->{$key}) ? $order_item->{$key} : '';
																//$td_value = $key;
															}
															
															break;
													endswitch;
													$td_content = "<td class=\"{$td_class}\"{$td_style}>{$td_value}</td>\n";
													echo $td_content;
												endforeach;                                        	
											?> 
										</tr>
										 <tr class="product_row <?php echo $alternate."alternate row_".$key;?>"<?php echo $show_product_row;?>>
											<?php $this->detail_report_product_row($columns,$order_item,$cells_status,$zero,$zero_prize,$order_columns,$price_columns);?>
										</tr>
										<?php
									}
									$last_order_id = $order_item->order_id;
									$alt_order_id = $alternate.$last_order_id;
								
								endforeach;
								
							?>
							</tbody>	                    
						</table>               
						<?php if($type != 'all_row') $this->total_count($TotalOrderCount, $TotalAmount, $total_pages,$summary); else $this->back_print_botton('bottom');
						$detail_view 		= $this->get_request('detail_view','no');
						 $zero				= $this->price(0);
						 echo $this->result_grid($detail_view,$summary,$zero,$total_columns, $price_columns);
						 ?>
				<?php else:?>        
					<div class="order_not_found"><?php _e('No orders found','icwoocommerce_textdomains'); ?></div>
				<?php endif;?>
			<?php
			//echo THEME_JS;
			//print_array( $order_items );
		}
		
		
		function detail_report_product_row($columns = array(),$order_item = array(),$cells_status = array(),$zero = 0, $zero_prize, $order_columns = array(),$price_columns){
			
			$output = "";
			foreach($columns as $key => $value):
				$td_class = $key;
				$td_style = $cells_status[$key];
				$td_value = "";
				switch($key):
					case "order_id":														
					case "billing_name":															
					case "ic_commerce_order_billing_name":
					case "billing_email":														
					case "order_date":
					case "status":															
					case "item_count":															
					case "order_shipping":
					case "order_shipping_tax":
					case "order_tax":
					case "gross_amount":
					case "order_discount":
					case "cart_discount":
					case "total_discount":
					case "order_total":	
					case "payment_method_title":
					case "order_currency":	
					case "order_status"://New Change ID 20140918
					case "invoice_action"://New Change ID 20140918
					case "transaction_id"://New Change ID 20150203
					case "billing_country"://20150216						
					case "shipping_country"://20150216							
					case "billing_state"://20150216
					case "shipping_state"://20150216
					case "shipping_method_title"://20150216
					case "tax_name"://Added 20150221
					case "order_coupon_codes"://Added 20150221
					case "order_item_count"://Added 20150221
					case "order_status_name"://New Change ID 20150225
					case "customer_username"://New Change ID 20150227
					case "customer_id"://New Change ID 20150227					
						$td_value = '';
						break;
					case "product_rate":
					case "total_price":
					case "sold_rate":
					case "difference_rate":
					case "item_amount":
					case "item_discount":
					case "order_item_shipping":
					case "order_item_tax":
					case "product_net":
					case "product_rate_net":
						//$td_value = $order_item->$key > 0 ? $this->price($order_item->$key, array('currency' => $order_item->order_currency) ) : $zero_prize[$order_item->order_currency];
						$td_value = isset($order_item->$key) ? $order_item->$key : 0;
						$td_value = $td_value == 0 ? $zero_prize[$order_item->order_currency] : $this->price($td_value, array('currency' => $order_item->order_currency));
						$td_class .= " amount";
						break;
						
					case "bottle_per_case":
					case "product_quantity":
						$td_value = isset($order_item->$key) ? $order_item->$key : '0';
						$td_class .= " amount";
						break;
					case "bottle_quantity":
						$bottle_quantity = isset($order_item->$key) ? $order_item->$key : '';
						$td_value = !empty($td_value) ? '' : $bottle_quantity;
						$td_class .= " amount";
						break;
					case "product_variation":
					case "order_product_sku":
					case "product_name":
					case "category_name":
					case "product_name":
					case "product_name":
						$td_value = isset($order_item->$key) ? $order_item->$key : '';
						break;
					default:
						if(isset($order_columns[$key])){
							$td_value = '';
						}else if(isset($price_columns[$key])){
							$td_value = isset($order_item->$key) ? $order_item->$key : 0;
							$td_value = $td_value == 0 ? $zero_prize[$order_item->order_currency] : $this->price($td_value, array('currency' => $order_item->order_currency));
							$td_class .= " amount";
						}else{
							$td_value = isset($order_item->{$key}) ? $order_item->{$key} : '';
						}
						break;
				endswitch;
				$td_content = "<td class=\"{$td_class}\"{$td_style}>{$td_value}</td>\n";
				$output .=  $td_content;
			endforeach;
			echo $output;
		}
		
		
		function total_count($TotalOrderCount = 0, $TotalAmount = 0, $total_pages = 0, $summary = array()){
			global $request;
			
			$admin_page 		= $this->get_request('page');
			$limit	 			= $this->get_request('limit',15, true);
			$adjacents			= $this->get_request('adjacents',3);
			$detail_view		= $this->get_request('detail_view',"no");
			$targetpage 		= "admin.php?page=".$admin_page;
			$create_pagination 	= $this->get_pagination($total_pages,$limit,$adjacents,$targetpage,$request);
			
			$woocommerce_currency = get_option('woocommerce_currency','USD');
			$woocommerce_currency = strlen($woocommerce_currency) >0 ? $woocommerce_currency : "USD";
			?>
				<table style="width:100%" class="detail_summary">
					<tr>
						<td valign="middle" class="grid_bottom_total">
						<?php if($detail_view == "no"):?>
							<?php echo _e('Order:','icwoocommerce_textdomains'); ?> <strong><?php echo $TotalOrderCount ?>/<?php echo $total_pages?></strong>, <?php echo _e('Amount:','icwoocommerce_textdomains'); ?> <strong><?php echo $this->price($TotalAmount, array('currency' => $woocommerce_currency)); ?></strong>
						<?php endif;?>
						</td>
						<td>					
							<?php echo $create_pagination;?>
                        	<div class="clearfix"></div>
                            <div>
                        	<?php
								$this->export_to_csv_button('bottom',$total_pages, $summary);
								$this->back_button();
							?>
                            </div>
                            <div class="clearfix"></div>
                        </td>
					</tr>
				</table>
                <script type="text/javascript">
                	jQuery(document).ready(function($) {$('.pagination a').removeAttr('href');});
                </script>
			<?php
		}
		
		function export_to_csv_button($position = 'bottom', $total_pages = 0, $summary = array()){
			global $request;
			
			$admin_page 		= 	$this->get_request('admin_page');
			//$admin_page_url 		= get_option('siteurl').'/wp-admin/admin.php';//Commented not work SSL admin site 20150212
			$admin_page_url 		= $this->constants['admin_page_url'];//Added SSL fix 20150212
			$mngpg 				= 	$admin_page_url.'?page='.$admin_page ;
			$request			=	$this->get_all_request();
			
			$request['total_pages'] = $total_pages;	
			
			$request['count_generated']		=	1;
			
			foreach($summary as $key => $value):
				$request[$key]		=	$value;
			endforeach;
					
			$request_			=	$request;
			
			unset($request['action']);
			//////unset($request['_page']);
			unset($request['p']);
			
			
			?>
            <div id="<?php echo $admin_page ;?>Export" class="RegisterDetailExport">
                <form id="<?php echo $admin_page."_".$position ;?>_form" class="<?php echo $admin_page ;?>_form ic_export_<?php echo $position ;?>_form" action="<?php echo $mngpg;?>" method="post">
                    <?php echo $this->create_hidden_fields($request);?>
                    <input type="hidden" name="export_file_name" value="<?php echo $admin_page;?>" />
                    <input type="hidden" name="export_file_format" value="csv" />
                    <?php do_action('ic_commerce_export_buttons',$admin_page,$position,$request);?>                    
                    <input type="button" name="<?php echo $admin_page ;?>_export" class="onformprocess open_popup csvicon" value="<?php _e("Export to CSV",'icwoocommerce_textdomains');?>" data-format="csv" data-popupid="export_csv_popup" data-hiddenbox="popup_csv_hidden_fields" data-popupbutton="<?php _e("Export to CSV",'icwoocommerce_textdomains');?>" data-title="<?php _e("Export to CSV - Additional Information",'icwoocommerce_textdomains');?>" />
                    <input type="button" name="<?php echo $admin_page ;?>_export" class="onformprocess open_popup excelicon" value="<?php _e("Export to Excel",'icwoocommerce_textdomains');?>" data-format="xls" data-popupid="export_csv_popup" data-hiddenbox="popup_csv_hidden_fields" data-popupbutton="<?php _e("Export to Excel",'icwoocommerce_textdomains');?>" data-title="<?php _e("Export to Excel - Additional Information",'icwoocommerce_textdomains');?>" />
                    <input type="button" name="<?php echo $admin_page ;?>_export_pdf" class="onformprocess open_popup pdficon" value="<?php _e("Export to PDF",'icwoocommerce_textdomains');?>" data-format="pdf" data-popupid="export_pdf_popup" data-hiddenbox="popup_pdf_hidden_fields" data-popupbutton="<?php _e("Export to PDF",'icwoocommerce_textdomains');?>" data-title="<?php _e("Export to PDF",'icwoocommerce_textdomains');?>" />
                    <input type="button" name="<?php echo $admin_page ;?>_export_print" class="onformprocess open_popup printicon" value="<?php _e("Print",'icwoocommerce_textdomains');?>"  data-format="print" data-popupid="export_print_popup" data-hiddenbox="popup_print_hidden_fields" data-popupbutton="<?php _e("Print",'icwoocommerce_textdomains');?>" data-title="<?php _e("Print",'icwoocommerce_textdomains');?>" data-form="form" />
                    
                    
                </form>
                <?php if($position == "bottom"):?>
                <form id="search_order_pagination" class="search_order_pagination" action="<?php echo $mngpg;?>" method="post">
                    <?php echo $this->create_hidden_fields($request_);?>
                </form>
                <?php endif;?>
               </div>
            <?php
		}
		
		function back_button(){
			$url = "#";
			if(isset($_SERVER['HTTP_REFERER']))
				$url = $_SERVER['HTTP_REFERER'];
			
			?>	<div class="backtoprevious">
            		<!--<a href="<?php echo $url;?>" class="backtoprevious" onclick="back_to_previous();">Back to Previous</a>-->
                    <input type="button" name="backtoprevious" value="<?php _e("Back to Previous",'icwoocommerce_textdomains');?>"  class="backtoprevious onformprocess" onClick="back_to_previous();" />
                </div>
            <?php
		}
		
		function back_print_botton($position  = "bottom"){
			?>
            	<div class="back_print_botton noPrint">
            		<input type="button" name="backtoprevious" value="<?php _e("Back to Previous",'icwoocommerce_textdomains');?>"  class="onformprocess" onClick="back_to_detail();" />
                    <input type="button" name="backtoprevious" value="<?php _e("Print",'icwoocommerce_textdomains');?>"  class="onformprocess" onClick="print_report();" />
                </div> 
        <?php         
		}
		  
		var $normal_sql_query	 	= NULL;
		var $all_row_result 		= NULL;  
		function ic_commerce_custom_report_normal_query($type = 'total_row', $columns = array(), $total_columns = array()){
					global $wpdb;
					
					$request		= $this->get_all_request();extract($request);
					
					$billing_country	= $this->get_request('billing_country','');
					
					//if($type == 'total_row'){	
						//$columns_sql = " SELECT count(*) ";
					
					//}else{
						$paid_customer	= $this->get_string_multi_request('paid_customer',$paid_customer, "-1");
						$order_status	= $this->get_string_multi_request('order_status',$order_status, "-1");
						$hide_order_status	= $this->get_string_multi_request('hide_order_status',$hide_order_status, "-1");//New Change ID 20140918
						
						
						
						$order_date_field_key = $order_date_field_key;
						if($order_status == "'wc-refunded'" || $order_status == "wc-refunded"){
							if(($order_status_id  && $order_status_id != '-1') || $sort_by == "status"){
								$order_date_field_key = "post_date";
							}else{
								$order_date_field_key = "post_modified";
							}
						}
						
						$columns_sql = "SELECT ";
						if(($billing_name and $billing_name != '-1') || $sort_by == "billing_name"){
							$columns_sql .= " CONCAT(postmeta1.meta_value, ' ', postmeta2.meta_value) AS billing_name," ;
						}
						if($billing_email || ($paid_customer  && $paid_customer != '-1' and $paid_customer != "'-1'") || $sort_by == "billing_email"){
							$columns_sql .= " postmeta.meta_value AS billing_email,";
						}
						if(($billing_country and $billing_country != '-1') || $sort_by == "billing_country"){
							$columns_sql .= " postmeta4.meta_value AS billing_country,";
						}
						
						if(($first_name and $first_name != '-1')){
							$columns_sql .= " postmeta12.meta_value AS first_name," ;
						}
						if(($last_name and $last_name != '-1')){
							$columns_sql .= " postmeta13.meta_value AS last_name," ;
						}
						
						if($sort_by == "status"){
							$columns_sql .= " terms.name as status, ";
						}
						
						$columns_sql .= " 	  
							posts.ID AS order_id							
							,posts.post_status AS post_status
							,posts.post_status AS order_status							
						";
						
						if($order_date_field_key == "post_date" || $order_date_field_key == "post_modified"){
							$columns_sql .= ", DATE_FORMAT(posts.{$order_date_field_key},'%m/%d/%Y') AS order_date";
						}else{
							$columns_sql .= ", DATE_FORMAT(posts.post_date,'%m/%d/%Y') AS order_date";
						}
						
						$columns_sql = apply_filters("ic_commerce_normal_view_select_query", $columns_sql, $request, $type, $page, $columns);
					//}
					if(!$this->normal_sql_query){
						
						$payment_method_title 	= $this->get_request('payment_method_title',"-1");
						
						$state_code		= $this->get_string_multi_request('state_code',$state_code, "-1");
						$country_code	= $this->get_string_multi_request('country_code',$country_code, "-1");
						
						$coupon_codes	= $this->get_string_multi_request('coupon_codes',$coupon_codes, "-1");
						$payment_method_title	= $this->get_string_multi_request('payment_method_title',$payment_method_title, "-1");
						
						$sql = " FROM {$wpdb->prefix}posts as posts ";
							
						if(($order_status_id  && $order_status_id != '-1') || $sort_by == "status"){
							$sql .= " 
							LEFT JOIN  {$wpdb->prefix}term_relationships 	as term_relationships 	ON term_relationships.object_id		=	posts.ID
							LEFT JOIN  {$wpdb->prefix}term_taxonomy 		as term_taxonomy 		ON term_taxonomy.term_taxonomy_id	=	term_relationships.term_taxonomy_id";
							
							if($sort_by == "status"){
								$sql .= " LEFT JOIN  {$wpdb->prefix}terms 				as terms 				ON terms.term_id					=	term_taxonomy.term_id";
							}
						}
						
						if($billing_email || ($paid_customer  && $paid_customer != '-1' and $paid_customer != "'-1'") || $sort_by == "billing_email"){
							$sql .= " 
								LEFT JOIN  {$wpdb->prefix}postmeta 			as postmeta				ON postmeta.post_id=posts.ID";
						}
						if(($billing_name and $billing_name != '-1') || $sort_by == "billing_name"){
							$sql .= " 
							LEFT JOIN  {$wpdb->prefix}postmeta				as postmeta1 			ON postmeta1.post_id				=	posts.ID
							LEFT JOIN  {$wpdb->prefix}postmeta				as postmeta2 			ON postmeta2.post_id				=	posts.ID";
						}
						
						if(($first_name and $first_name != '-1')){
							$sql .= " LEFT JOIN  {$wpdb->prefix}postmeta				as postmeta12 			ON postmeta12.post_id				=	posts.ID";
						}
						
						if(($last_name and $last_name != '-1')){
							$sql .= " LEFT JOIN  {$wpdb->prefix}postmeta				as postmeta13 			ON postmeta13.post_id				=	posts.ID";
						}
						
						
						if($country_code and $country_code != '-1' || $sort_by == "billing_country")	
							$sql .= " LEFT JOIN  {$wpdb->prefix}postmeta 	as postmeta4 			ON postmeta4.post_id				=	posts.ID";
						
						if($state_code and $state_code != '-1')	$sql .= " LEFT JOIN  {$wpdb->prefix}postmeta as postmeta_billing_state ON postmeta_billing_state.post_id=posts.ID";
						
						if($billing_postcode and $billing_postcode != '-1')	$sql .= " LEFT JOIN  {$wpdb->prefix}postmeta as postmeta_billing_postcode ON postmeta_billing_postcode.post_id	=	posts.ID";
						
						if($payment_method_title)	$sql .= " LEFT JOIN  {$wpdb->prefix}postmeta as postmeta5 ON postmeta5.post_id=posts.ID";
						
						if($coupon_used == "yes")	$sql .= " LEFT JOIN  {$wpdb->prefix}postmeta as postmeta6 ON postmeta6.post_id=posts.ID";
						if($coupon_used == "yes")	$sql .= " LEFT JOIN  {$wpdb->prefix}postmeta as postmeta7 ON postmeta7.post_id=posts.ID";//Added 20150205
						
						
						
						if($order_meta_key and $order_meta_key != '-1')	$sql .= " LEFT JOIN  {$wpdb->prefix}postmeta as order_meta_key ON order_meta_key.post_id=posts.ID";
												
						
						if(($order_meta_key != "_order_total" and $max_amount != '-1') || ($order_meta_key != "_order_total" and $min_amount != '-1')){
							//$sql .= " LEFT JOIN  {$wpdb->prefix}postmeta as order_meta_key ON order_meta_key.post_id=posts.ID-------";
						}
						
						if(($coupon_codes && $coupon_codes != "-1") or ($coupon_code && $coupon_code != "-1")){
						$sql .= " LEFT JOIN {$wpdb->prefix}woocommerce_order_items as woocommerce_order_coupon_item ON woocommerce_order_coupon_item.order_id = posts.ID AND woocommerce_order_coupon_item.order_item_type = 'coupon'";
						}
						
						$sql = apply_filters("ic_commerce_normal_view_join_query", $sql, $request, $type, $page, $columns);
						
						$sql .= " WHERE  1*1 AND posts.post_type = 'shop_order'";
								
						if($billing_email || ($paid_customer  && $paid_customer != '-1' and $paid_customer != "'-1'") || $sort_by == "billing_email"){
							$sql .= " 
								AND postmeta.meta_key='_billing_email'";
						}
						
						if(($billing_name and $billing_name != '-1') || $sort_by == "billing_name"){
							$sql .= " 
								AND postmeta1.meta_key='_billing_first_name' 
								AND postmeta2.meta_key='_billing_last_name'";
						}
						
						if(($first_name and $first_name != '-1')){
							$sql .= " AND postmeta12.meta_key='_billing_first_name' ";
						}
						
						if(($last_name and $last_name != '-1')){
							$sql .= " AND postmeta13.meta_key='_billing_last_name' ";
						}
							
						if($country_code and $country_code != '-1' || $sort_by == "billing_country")	$sql .= " AND postmeta4.meta_key='_billing_country'";
						if($state_code and $state_code != '-1')		$sql .= " AND postmeta_billing_state.meta_key='_billing_state'";
						
						if($billing_postcode and $billing_postcode != '-1')$sql .= " AND postmeta_billing_postcode.meta_key='_billing_postcode' AND postmeta_billing_postcode.meta_value LIKE '%{$billing_postcode}%' ";
						
						if($order_meta_key and $order_meta_key != '-1'){
							//$sql .= " AND order_meta_key.meta_key='{$order_meta_key}' AND order_meta_key.meta_value >= 0";
							$sql .= " AND order_meta_key.meta_key='{$order_meta_key}'";
							if($amount_greater_zero and $amount_greater_zero != '-1'){
								if($min_amount == '-1'){
									$sql .= " AND order_meta_key.meta_value > 1";
								}								
							}else{
								if($min_amount == '-1'){
									$sql .= " AND order_meta_key.meta_value > 1";
								}
							}
						}
						/*if((
						//($max_amount and $max_amount != '-1' and $order_meta_key != "_order_total") ||
						(strlen($max_amount) > 0 and $max_amount == '0' and $order_meta_key != "_order_total")) ||
						(($min_amount and $min_amount != '-1' and $order_meta_key != "_order_total") ||
						(strlen($min_amount) > 0 and $min_amount == '0' and $order_meta_key != "_order_total"))
						)
							$sql .= " AND order_meta_key.meta_key='_order_total'";*/
						
						if($min_amount != '-1')
							$sql .= " AND order_meta_key.meta_value >= {$min_amount}";
							
						if($max_amount != '-1')
							$sql .= " AND order_meta_key.meta_value <= {$max_amount}";
						
						
						if($order_date_field_key == "post_date" || $order_date_field_key == "post_modified"){
							if ($start_date != NULL &&  $end_date !=NULL){
								$sql .= " AND DATE(posts.{$order_date_field_key}) BETWEEN '".$start_date."' AND '". $end_date ."'";
							}
						}
						
						if($order_id) {
							$order_id = rtrim($order_id, ',');
							$order_id = str_replace(" ","",$order_id);
							$order_id = preg_replace('/,+/', ',', $order_id);
							if($order_id){
								$sql .= " AND posts.ID IN ($order_id)";
							}
						}
						
						if($billing_email)	$sql .= " AND postmeta.meta_value LIKE '%".$billing_email."%'";
						
						if($paid_customer  && $paid_customer != '-1' and $paid_customer != "'-1'")$sql .= " AND postmeta.meta_value IN (".$paid_customer.")";
						
						if($billing_name and $billing_name != '-1')$sql .= " AND (lower(concat_ws(' ', postmeta1.meta_value, postmeta2.meta_value)) like lower('%".$billing_name."%') OR lower(concat_ws(' ', postmeta2.meta_value, postmeta1.meta_value)) like lower('%".$billing_name."%'))";
						
						if($first_name)	$sql .= " AND postmeta12.meta_value LIKE '%".$first_name."%'";
						
						if($last_name)	$sql .= " AND postmeta13.meta_value LIKE '%".$last_name."%'";
						
						if($order_status_id  && $order_status_id != '-1') $sql .= " AND term_taxonomy.term_id IN (".$order_status_id .")";
						
						if($publish_order == 'yes')	$sql .= " AND posts.post_status = 'publish'";
						
						if($publish_order == 'publish' || $publish_order == 'trash')	$sql .= " AND posts.post_status = '".$publish_order."'";
						
						//if($country_code and $country_code != '-1')	$sql .= " AND postmeta4.meta_value LIKE '%".$country_code."%'";
						
						//if($state_code and $state_code != '-1')	$sql .= " AND postmeta_billing_state.meta_value LIKE '%".$state_code."%'";
						
						if($country_code and $country_code != '-1')	$sql .= " AND postmeta4.meta_value IN (".$country_code.")";
						
						if($state_code and $state_code != '-1')	$sql .= " AND postmeta_billing_state.meta_value IN (".$state_code.")";
						
						if($payment_method_title)	$sql .= " AND postmeta5.meta_key='_payment_method_title'";
						
						//if($payment_method)	$sql .= " AND postmeta5.meta_value LIKE '%".$payment_method."%'";
						
						if($payment_method_title  && $payment_method_title != '-1' and $payment_method_title != "'-1'"){
							$sql .= " AND LOWER(postmeta5.meta_value) IN (".$payment_method_title.")";
						}
						
						if($order_item_name)$sql .= " AND woocommerce_order_items.order_item_name LIKE '%".$order_item_name."%'";
												
						if($coupon_used == "yes") $sql .= " AND( (postmeta6.meta_key='_order_discount' AND postmeta6.meta_value > 0) ||  (postmeta7.meta_key='_cart_discount' AND postmeta7.meta_value > 0))";//Added 20150205
						
						if($coupon_code && $coupon_code != "-1"){
							$sql .= " AND (woocommerce_order_coupon_item.order_item_name IN ('{$coupon_code}') OR woocommerce_order_coupon_item.order_item_name LIKE '%{$coupon_code}%')";
						}
						
						if($coupon_codes && $coupon_codes != "-1"){
							$sql .= " AND woocommerce_order_coupon_item.order_item_name IN ({$coupon_codes})";
						}
						
						if($order_status  && $order_status != '-1' and $order_status != "'-1'")$sql .= " AND posts.post_status IN (".$order_status.")";//New Change ID 20140918
						if($hide_order_status  && $hide_order_status != '-1' and $hide_order_status != "'-1'")$sql .= " AND posts.post_status NOT IN (".$hide_order_status.")";//New Change ID 20140918
						
						$sql = apply_filters("ic_commerce_normal_view_where_query", $sql, $request, $type, $page, $columns, $total_columns);
						
						$this->normal_sql_query = $sql;
						
						//$this->print_sql($sql);
						
						$sql = "";
						
						
				}else{
					$sql = $this->normal_sql_query;
				}
				
				$sql = $columns_sql;		
				$sql .= $this->normal_sql_query;
				
				//if($type != 'total_row'){
					$group_sql = " GROUP BY posts.ID";
					$sql .= apply_filters("ic_commerce_normal_view_group_query", $group_sql, $request, $type, $page, $columns, $total_columns);
				//}
				
				//$this->print_sql($sql);
				
				$wpdb->flush(); 				
				$wpdb->query("SET SQL_BIG_SELECTS=1");
				/*
				if($type == 'total_row'){
					if($total_pages > 0){
						echo $order_items = $total_pages;						
					}else{
						$order_items = $wpdb->get_results($sql);
						if(strlen($wpdb->last_error) > 0){
							echo $wpdb->last_error;
						}						
						$wpdb->flush(); 
					}
					return $order_items;
				}
				*/
				
				if($type == 'total_row'){
					if($this->all_row_result){
						if($count_generated == 1){
							$order_items = $this->create_summary($request);
							//$this->print_array($order_items);
						}else{
							$order_items = $this->all_row_result;
							$summary = $this->get_count_total($order_items,'order_total');				
							$order_items = $summary;
						}
						
					}else{					
						if($count_generated == 1 || ($p > 1)){
							$order_items = $this->create_summary($request);
						}else{
							$order_items = $wpdb->get_results($sql);
							
							if(strlen($wpdb->last_error) > 0){
								echo $wpdb->last_error;
								return array();
							}
							
							$wpdb->flush();
							
							if(count($order_items)>0){
								$extra_meta_keys 	= apply_filters('ic_commerce_normal_view_extra_meta_keys', array('order_total','order_shipping','cart_discount','order_discount','order_tax','order_shipping_tax'),$request, $type, $page, 'normal_view', $columns);
								$post_ids 			= $this->get_items_id_list($order_items,'order_id');
								$postmeta_datas 	= $this->get_shop_order_postmeta($post_ids, $total_columns,$extra_meta_keys,'total');
								//$this->print_array($postmeta_datas);die;
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
										
										$order_items[$key]->order_tax 			= isset($order_items[$key]->order_tax)			? $order_items[$key]->order_tax : 0;
										$order_items[$key]->order_shipping_tax 	= isset($order_items[$key]->order_shipping_tax)	? $order_items[$key]->order_shipping_tax : 0;
										$order_items[$key]->total_tax 			= ($order_items[$key]->order_tax + $order_items[$key]->order_shipping_tax);
										
										$order_items[$key]->gross_amount 		= ($order_items[$key]->order_total + $order_items[$key]->total_discount) - ($order_items[$key]->order_shipping +  $order_items[$key]->order_shipping_tax + $order_items[$key]->order_tax );
								}
							}
							
							$detail_view 		= $this->get_request('detail_view','no');
							$grid_object		= $this->get_grid_object();
							$order_items		= $grid_object->create_grid_items($total_columns,$order_items);
							
							$order_items 	= apply_filters("ic_commerce_normal_view_data_items", $order_items, $request, $type, $page, $columns,$total_columns);
							$summary = $this->get_count_total($order_items,'order_total');
							$order_items = $summary;
							
							
						}					
					}
					return $order_items;
				}
				
				if($type == 'limit_row'){
					$order_sql = " ORDER BY {$sort_by} {$order_by}";
					$sql .= apply_filters("ic_commerce_normal_view_order_query", $order_sql, $request, $type, $page, $columns);
					$sql .= " LIMIT $start, $limit";
					$order_items = $wpdb->get_results($sql);					
					$wpdb->flush();
					//$this->print_sql($sql);
					
				}
				
				if($type == 'all_row' or $type == 'all_row_total'){
					$order_sql = " ORDER BY {$sort_by} {$order_by}";
					$sql .= apply_filters("ic_commerce_normal_view_order_query", $order_sql, $request, $type, $page, $columns);
					$order_items = $wpdb->get_results($sql);
					$this->all_row_result = $order_items;
					$wpdb->flush(); 
					
				}
				
				if(strlen($wpdb->last_error) > 0){
					echo $wpdb->last_error;
					return array();
				}
				
				if($type == 'limit_row' || $type == 'all_row' or $type == 'all_row_total'){
					if(count($order_items)>0){
						$extra_meta_keys 	= apply_filters('ic_commerce_normal_view_extra_meta_keys', array('order_total','order_shipping','cart_discount','order_discount','total_discount','order_tax','order_shipping_tax','total_tax','billing_first_name','billing_last_name','order_currency'),$request, $type, $page, 'normal_view', $columns);
						$post_ids 			= $this->get_items_id_list($order_items,'order_id');
						$postmeta_datas 	= $this->get_shop_order_postmeta($post_ids, $columns,$extra_meta_keys);
						//$this->print_array($postmeta_datas);die;
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
								
								$order_items[$key]->order_tax 			= isset($order_items[$key]->order_tax)			? $order_items[$key]->order_tax : 0;
								$order_items[$key]->order_shipping_tax 	= isset($order_items[$key]->order_shipping_tax)	? $order_items[$key]->order_shipping_tax : 0;
								$order_items[$key]->total_tax 			= ($order_items[$key]->order_tax + $order_items[$key]->order_shipping_tax);
								
								$transaction_id = "ransaction ID";
								$order_items[$key]->transaction_id		= isset($order_items[$key]->$transaction_id) 	? $order_items[$key]->$transaction_id		: (isset($order_items[$key]->transaction_id) ? $order_items[$key]->transaction_id : '');
								$order_items[$key]->gross_amount 		= ($order_items[$key]->order_total + $order_items[$key]->total_discount) - ($order_items[$key]->order_shipping +  $order_items[$key]->order_shipping_tax + $order_items[$key]->order_tax );
								
								$order_items[$key]->billing_first_name	= isset($order_items[$key]->billing_first_name)	? $order_items[$key]->billing_first_name 	: '';
								$order_items[$key]->billing_last_name	= isset($order_items[$key]->billing_last_name)	? $order_items[$key]->billing_last_name 	: '';
								$order_items[$key]->billing_name		= $order_items[$key]->billing_first_name.' '.$order_items[$key]->billing_last_name;
						}
					}
					
					$order_items 	= apply_filters("ic_commerce_normal_view_data_items", $order_items, $request, $type, $page, $columns, $total_columns);
				}
				
				//$this->print_array($order_items);
												
				return $order_items;
		}
		
		var $detail_sql_query = NULL;
		function ic_commerce_custom_report_detail_query($type = 'total_row', $columns = array(), $total_columns = array()){
				global $wpdb, $request;
				
				$request		= $this->get_all_request();extract($request);					
				$columns_sql	= "";
				
				
					
				if(!$this->detail_sql_query){
					
					$payment_method_title 	= $this->get_request('payment_method_title',"-1");
					$payment_method_title	= $this->get_string_multi_request('payment_method_title',$payment_method_title, "-1");
					
					$billing_country	= $this->get_request('billing_country','');
					$state_code			= $this->get_string_multi_request('state_code',$state_code, "-1");
					$country_code		= $this->get_string_multi_request('country_code',$country_code, "-1");
					
					$paid_customer		= $this->get_string_multi_request('paid_customer',$paid_customer, "-1");
					$order_status		= $this->get_string_multi_request('order_status',$order_status, "-1");
					$hide_order_status	= $this->get_string_multi_request('hide_order_status',$hide_order_status, "-1");//New Change ID 20140918
					$coupon_codes		= $this->get_string_multi_request('coupon_codes',$coupon_codes, "-1");
					
					$product_sku		= $this->get_string_multi_request('product_sku',$product_sku, "-1");			
					$variation_sku		= $this->get_string_multi_request('variation_sku',$variation_sku, "-1");
					
					$order_date_field_key = $order_date_field_key;
					if($order_status == "'wc-refunded'" || $order_status == "wc-refunded"){
						if(($order_status_id  && $order_status_id != '-1') || $sort_by == "status"){
							$order_date_field_key = "post_date";		
						}else{
							$order_date_field_key = "post_modified";
						}
					}
					
					$columns_sql = " SELECT ";
				
					if(($billing_name and $billing_name != '-1') || $sort_by == "billing_name"){
						$columns_sql .= " CONCAT(postmeta1.meta_value, ' ', postmeta2.meta_value) AS billing_name," ;
					}
					
					if(($first_name and $first_name != '-1')){
						$columns_sql .= " postmeta12.meta_value AS first_name," ;
					}
					if(($last_name and $last_name != '-1')){
						$columns_sql .= " postmeta13.meta_value AS last_name," ;
					}
					
					if($billing_email || ($paid_customer  && $paid_customer != '-1' and $paid_customer != "'-1'") || $sort_by == "billing_email"){
						$columns_sql .= " postmeta.meta_value AS billing_email,";
					}
					if(($billing_country and $billing_country != '-1') || $sort_by == "billing_country"){
						$columns_sql .= " postmeta4.meta_value AS billing_country,";
					}
					
					if($sort_by == "status"){
						$columns_sql .= " terms2.name as status, ";
					}
					
					$columns_sql .= "
					FORMAT(posts.post_date,'%m/%d/%Y') 															AS order_date,
					woocommerce_order_items.order_id 															AS order_id,					
					woocommerce_order_items.order_item_name 													AS product_name,					
					woocommerce_order_items.order_item_id														AS order_item_id,
					woocommerce_order_itemmeta_product_id.meta_value 											AS woocommerce_order_itemmeta_meta_value,					
					(woocommerce_order_itemmeta2.meta_value/woocommerce_order_itemmeta3.meta_value) 			AS sold_rate,
					(woocommerce_order_itemmeta4.meta_value/woocommerce_order_itemmeta3.meta_value) 			AS product_rate,
					(woocommerce_order_itemmeta4.meta_value) 													AS item_amount,
					(woocommerce_order_itemmeta2.meta_value) 													AS item_net_amount,
					(woocommerce_order_itemmeta20.meta_value) 													AS order_item_tax,
					(woocommerce_order_itemmeta4.meta_value - woocommerce_order_itemmeta2.meta_value) 			AS item_discount,					
					woocommerce_order_itemmeta2.meta_value 														AS total_price,					
					woocommerce_order_itemmeta_product_id.meta_value 											AS product_id
					,woocommerce_order_itemmeta3.meta_value 													AS 'product_quantity'					
					,posts.post_status 																			AS post_status
					,posts.post_status 																			AS order_status
					,(woocommerce_order_itemmeta20.meta_value + woocommerce_order_itemmeta4.meta_value) - (woocommerce_order_itemmeta4.meta_value - woocommerce_order_itemmeta2.meta_value) 			AS product_net
					,(woocommerce_order_itemmeta20.meta_value + woocommerce_order_itemmeta4.meta_value) 		AS product_rate_net
					";
					
					
					
					$columns_sql .= ", line_tax.meta_value AS line_tax";
					
					$columns_sql .= ", (line_tax.meta_value + woocommerce_order_itemmeta2.meta_value) AS customer_price";
					
					
					
					if($order_date_field_key == "post_date" || $order_date_field_key == "post_modified"){
						$columns_sql .= ", DATE_FORMAT(posts.{$order_date_field_key},'%m/%d/%Y') AS order_date";
					}else{
						$columns_sql .= ", DATE_FORMAT(posts.post_date,'%m/%d/%Y') AS order_date";
					}
					
					$columns_sql = apply_filters("ic_commerce_details_view_select_query", $columns_sql, $request, $type, $page, $columns,$total_columns);
						
					$sql = $columns_sql." FROM {$wpdb->prefix}woocommerce_order_items as woocommerce_order_items 
					
					LEFT JOIN  {$wpdb->prefix}posts as posts ON posts.ID=woocommerce_order_items.order_id				
					
					LEFT JOIN  {$wpdb->prefix}woocommerce_order_itemmeta 	as woocommerce_order_itemmeta_product_id 	ON woocommerce_order_itemmeta_product_id.order_item_id		=	woocommerce_order_items.order_item_id
					LEFT JOIN  {$wpdb->prefix}woocommerce_order_itemmeta 	as woocommerce_order_itemmeta2 	ON woocommerce_order_itemmeta2.order_item_id	=	woocommerce_order_items.order_item_id
					LEFT JOIN  {$wpdb->prefix}woocommerce_order_itemmeta 	as woocommerce_order_itemmeta3 	ON woocommerce_order_itemmeta3.order_item_id	=	woocommerce_order_items.order_item_id
					LEFT JOIN  {$wpdb->prefix}woocommerce_order_itemmeta 	as woocommerce_order_itemmeta4 	ON woocommerce_order_itemmeta4.order_item_id	=	woocommerce_order_items.order_item_id AND woocommerce_order_itemmeta4.meta_key='_line_subtotal'
					LEFT JOIN  {$wpdb->prefix}woocommerce_order_itemmeta 	as woocommerce_order_itemmeta20 	ON woocommerce_order_itemmeta20.order_item_id	=	woocommerce_order_items.order_item_id AND woocommerce_order_itemmeta20.meta_key='_line_subtotal_tax'";
					
					$sql .= " LEFT JOIN  {$wpdb->prefix}woocommerce_order_itemmeta 	as line_tax 	ON line_tax.order_item_id	=	woocommerce_order_items.order_item_id AND line_tax.meta_key='_line_tax'";
					
					
					
					
					
					if($category_id  && $category_id != "-1") {
						$sql .= "
							LEFT JOIN  {$wpdb->prefix}term_relationships 	as term_relationships 			ON term_relationships.object_id		=	woocommerce_order_itemmeta_product_id.meta_value
							LEFT JOIN  {$wpdb->prefix}term_taxonomy 		as term_taxonomy 				ON term_taxonomy.term_taxonomy_id	=	term_relationships.term_taxonomy_id";
							//LEFT JOIN  {$wpdb->prefix}terms 				as terms 						ON terms.term_id					=	term_taxonomy.term_id";
					}
					
					if(($order_status_id  && $order_status_id != '-1') || $sort_by == "status"){
						$sql .= "
							LEFT JOIN  {$wpdb->prefix}term_relationships 	as term_relationships2			ON term_relationships2.object_id	= woocommerce_order_items.order_id
							LEFT JOIN  {$wpdb->prefix}term_taxonomy 		as term_taxonomy2				ON term_taxonomy2.term_taxonomy_id	= term_relationships2.term_taxonomy_id";
							if($sort_by == "status"){
								$sql .= " LEFT JOIN  {$wpdb->prefix}terms 	as terms2 						ON terms2.term_id					=	term_taxonomy2.term_id";
							}
					}
					
					if($billing_email || ($paid_customer  && $paid_customer != '-1' and $paid_customer != "'-1'") || $sort_by == "billing_email"){
						$sql .= " 
							LEFT JOIN  {$wpdb->prefix}postmeta as postmeta ON postmeta.post_id=woocommerce_order_items.order_id";
					}
					if(($billing_name and $billing_name != '-1') || $sort_by == "billing_name"){
						$sql .= " LEFT JOIN  {$wpdb->prefix}postmeta as postmeta1 ON postmeta1.post_id=woocommerce_order_items.order_id
						LEFT JOIN  {$wpdb->prefix}postmeta as postmeta2 ON postmeta2.post_id=woocommerce_order_items.order_id";
					}
					
					if(($first_name and $first_name != '-1')){
						$sql .= " LEFT JOIN  {$wpdb->prefix}postmeta as postmeta12 ON postmeta12.post_id=woocommerce_order_items.order_id";
					}
					
					if(($last_name and $last_name != '-1')){
						$sql .= " LEFT JOIN  {$wpdb->prefix}postmeta as postmeta13 ON postmeta13.post_id=woocommerce_order_items.order_id";
					}
					
					
					if($country_code and $country_code != '-1' || $sort_by == "billing_country")	$sql .= " LEFT JOIN  {$wpdb->prefix}postmeta as postmeta4 ON postmeta4.post_id=woocommerce_order_items.order_id";
					if($state_code and $state_code != '-1')	$sql .= " LEFT JOIN  {$wpdb->prefix}postmeta as postmeta_billing_state ON postmeta_billing_state.post_id=posts.ID";
					if($payment_method_title)	$sql .= " LEFT JOIN  {$wpdb->prefix}postmeta as postmeta5 ON postmeta5.post_id=woocommerce_order_items.order_id";
					
					if($billing_postcode and $billing_postcode != '-1')	$sql .= " LEFT JOIN  {$wpdb->prefix}postmeta as postmeta_billing_postcode ON postmeta_billing_postcode.post_id	=	posts.ID";
					
					if($coupon_used == "yes")	$sql .= " LEFT JOIN  {$wpdb->prefix}postmeta as postmeta6 ON postmeta6.post_id=woocommerce_order_items.order_id";
					if($coupon_used == "yes")	$sql .= " LEFT JOIN  {$wpdb->prefix}postmeta as postmeta7 ON postmeta7.post_id=posts.ID";//Added 20150205
					
					if(($variation_only  && $variation_only != "-1" && ($variation_only == "1" || $variation_only == "yes"))
						|| ($variation_id  && $variation_id != "-1")) {
						$sql .= " LEFT JOIN  {$wpdb->prefix}woocommerce_order_itemmeta 	as woocommerce_order_itemmeta_variation			ON woocommerce_order_itemmeta_variation.order_item_id 		= 	woocommerce_order_items.order_item_id";
					}
					
					if($variations_formated  != "-1" and $variations_formated  != NULL){
						$sql .= " LEFT JOIN  {$wpdb->prefix}woocommerce_order_itemmeta as woocommerce_order_itemmeta8 ON woocommerce_order_itemmeta8.order_item_id = woocommerce_order_items.order_item_id";
						$sql .= " LEFT JOIN  {$wpdb->prefix}postmeta as postmeta_variation ON postmeta_variation.post_id = woocommerce_order_itemmeta8.meta_value";
					}
						
					if($order_meta_key and $order_meta_key != '-1')
						$sql .= " LEFT JOIN  {$wpdb->prefix}postmeta as order_meta_key ON order_meta_key.post_id=posts.ID";
					
					if(($coupon_codes && $coupon_codes != "-1") or ($coupon_code && $coupon_code != "-1")){
						$sql .= " LEFT JOIN {$wpdb->prefix}woocommerce_order_items as woocommerce_order_coupon_item ON woocommerce_order_coupon_item.order_id = posts.ID AND woocommerce_order_coupon_item.order_item_type = 'coupon'";
					}
					
					if($product_sku and $product_sku != '-1'){				
						$sql .= " LEFT JOIN  {$wpdb->prefix}postmeta as postmeta_product_sku ON postmeta_product_sku.post_id=woocommerce_order_itemmeta_product_id.meta_value";
					}
					
					if($variation_sku and $variation_sku != '-1'){
						$sql .= " LEFT JOIN  {$wpdb->prefix}woocommerce_order_itemmeta as woocommerce_order_itemmeta_variation_id ON woocommerce_order_itemmeta_variation_id.order_item_id = woocommerce_order_items.order_item_id";
						$sql .= " LEFT JOIN  {$wpdb->prefix}postmeta as postmeta_variation_sku ON postmeta_variation_sku.post_id=woocommerce_order_itemmeta_variation_id.meta_value";
					}
					
					$sql = apply_filters("ic_commerce_details_view_join_query", $sql, $request, $type, $page, $columns,$total_columns);
					
					$sql .= " WHERE posts.post_type = 'shop_order'";
					
					if($billing_email || ($paid_customer  && $paid_customer != '-1' and $paid_customer != "'-1'") || $sort_by == "billing_email"){
						$sql .= " 
							AND postmeta.meta_key='_billing_email'";
					}
					
					if(($billing_name and $billing_name != '-1') || $sort_by == "billing_name"){
						$sql .= " 
							AND postmeta1.meta_key='_billing_first_name' 
							AND postmeta2.meta_key='_billing_last_name'";
					}
					
					if(($first_name and $first_name != '-1')){
						$sql .= " AND postmeta12.meta_key='_billing_first_name' ";
					}
					
					if(($last_name and $last_name != '-1')){
						$sql .= " AND postmeta13.meta_key='_billing_last_name' ";
					}
					
					$sql .= "
					AND woocommerce_order_itemmeta_product_id.meta_key = '_product_id'
					AND woocommerce_order_itemmeta2.meta_key='_line_total'
					AND woocommerce_order_itemmeta3.meta_key='_qty' ";
					
					
					
					if($country_code and $country_code != '-1' || $sort_by == "billing_country")	$sql .= " AND postmeta4.meta_key='_billing_country'";
					if($state_code and $state_code != '-1')		$sql .= " AND postmeta_billing_state.meta_key='_billing_state'";
					
					if($billing_postcode and $billing_postcode != '-1')$sql .= " AND postmeta_billing_postcode.meta_key='_billing_postcode' AND postmeta_billing_postcode.meta_value LIKE '%{$billing_postcode}%' ";
					
					//if($payment_method)	$sql .= " AND postmeta5.meta_key='_payment_method_title'";
					
					if($order_date_field_key == "post_date" || $order_date_field_key == "post_modified"){
						if ($start_date != NULL &&  $end_date !=NULL){
							$sql .= " AND DATE(posts.{$order_date_field_key}) BETWEEN '".$start_date."' AND '". $end_date ."'";
						}
					}
					
					if($order_id) {
						$order_id = rtrim($order_id, ',');
						$order_id = str_replace(" ","",$order_id);
						$order_id = preg_replace('/,+/', ',', $order_id);
						if($order_id){
							$sql .= " AND posts.ID IN ($order_id)";
						}
					}
					
					if($billing_email) $sql .= " AND postmeta.meta_value LIKE '%".$billing_email."%'";
					
					if($first_name) $sql .= " AND postmeta12.meta_value LIKE '%".$first_name."%'";
					if($last_name) $sql .= " AND postmeta13.meta_value LIKE '%".$last_name."%'";
					
					if($paid_customer  && $paid_customer != '-1' and $paid_customer != "'-1'")$sql .= " AND postmeta.meta_value IN (".$paid_customer.")";
					
					//if($billing_name and $billing_name != '-1') $sql .= " AND (postmeta1.meta_value LIKE '%".$billing_name."%' OR postmeta2.meta_value LIKE '%".$billing_name."%')";
					if($billing_name and $billing_name != '-1')$sql .= " AND (lower(concat_ws(' ', postmeta1.meta_value, postmeta2.meta_value)) like lower('%".$billing_name."%') OR lower(concat_ws(' ', postmeta2.meta_value, postmeta1.meta_value)) like lower('%".$billing_name."%'))";
						
					//if($order_status_id  && $order_status_id != "-1") $sql .= " AND terms2.term_id IN (".$order_status_id .")";
					
					if($publish_order == 'yes')	$sql .= " AND posts.post_status = 'publish'";
					if($publish_order == 'publish' || $publish_order == 'trash')	$sql .= " AND posts.post_status = '".$publish_order."'";
						
					//if($country_code and $country_code != '-1')	$sql .= " AND postmeta4.meta_value LIKE '%".$country_code."%'";
						
					//if($state_code and $state_code != '-1')	$sql .= " AND postmeta_billing_state.meta_value LIKE '%".$state_code."%'";
					
					if($country_code and $country_code != '-1')	$sql .= " AND postmeta4.meta_value IN (".$country_code.")";
						
					if($state_code and $state_code != '-1')	$sql .= " AND postmeta_billing_state.meta_value IN (".$state_code.")";
					
					if($payment_method_title)	$sql .= " AND postmeta5.meta_key='_payment_method_title'";
						
					//if($payment_method)	$sql .= " AND postmeta5.meta_value LIKE '%".$payment_method."%'";
					
					if($payment_method_title  && $payment_method_title != '-1' and $payment_method_title != "'-1'"){
						$sql .= " AND LOWER(postmeta5.meta_value) IN (".$payment_method_title.")";
					}
					
					if($order_meta_key and $order_meta_key != '-1')
							$sql .= " AND order_meta_key.meta_key='{$order_meta_key}' AND order_meta_key.meta_value > 0";
					
					if($order_item_name)$sql .= " AND woocommerce_order_items.order_item_name LIKE '%".$order_item_name."%'";
					
					if($txtProduct  && $txtProduct != '-1')	$sql .= " AND woocommerce_order_items.order_item_name LIKE '%".$txtProduct."%'";	
					
					if($product_id  && $product_id != "-1") $sql .= " AND woocommerce_order_itemmeta_product_id.meta_value IN (".$product_id .")";	
					
					if($product_id  && $product_id != "-1") $sql .= " AND woocommerce_order_itemmeta_product_id.meta_value IN (".$product_id .")";
					//if($category_id  && $category_id != "-1") $sql .= " AND terms.name NOT IN('simple','variable','grouped','external') AND term_taxonomy.taxonomy LIKE('product_cat') AND term_taxonomy.term_id IN (".$category_id .")";	
					if($category_id  && $category_id != "-1")			$sql .= " AND term_taxonomy.taxonomy LIKE('product_cat') AND term_taxonomy.term_id IN (".$category_id .")";
					
					if($order_status_id  && $order_status_id != "-1")	$sql .= " AND term_taxonomy2.taxonomy LIKE('shop_order_status') AND term_taxonomy2.term_id IN (".$order_status_id .")";
					
					//if($coupon_used == "yes") $sql .= " AND postmeta6.meta_key='_order_discount' AND postmeta6.meta_value > 0";//Commented 20150205

					if($coupon_used == "yes") $sql .= " AND( (postmeta6.meta_key='_order_discount' AND postmeta6.meta_value > 0) ||  (postmeta7.meta_key='_cart_discount' AND postmeta7.meta_value > 0))";//Added 20150205
					
					//if($coupon_used == "yes") $sql .= " AND postmeta6.meta_key = '_recorded_coupon_usage_counts' AND postmeta6.meta_value = 'yes'";
					//Added 20150424
					if($coupon_code && $coupon_code != "-1"){
						$sql .= " AND (woocommerce_order_coupon_item.order_item_name IN ('{$coupon_code}') OR woocommerce_order_coupon_item.order_item_name LIKE '%{$coupon_code}%')";
					}
					
					if($coupon_codes && $coupon_codes != "-1"){
						$sql .= " AND woocommerce_order_coupon_item.order_item_name IN ({$coupon_codes})";
					}
					
					if($variation_id  && $variation_id != "-1") {
						$sql .= " AND woocommerce_order_itemmeta_variation.meta_key = '_variation_id' AND woocommerce_order_itemmeta_variation.meta_value IN (".$variation_id .")";						
					}
					
					if($variation_only  && $variation_only != "-1" && ($variation_only == "1" || $variation_only == "yes")) {
								$sql .= " AND woocommerce_order_itemmeta_variation.meta_key 	= '_variation_id'
								 AND (woocommerce_order_itemmeta_variation.meta_value IS NOT NULL AND woocommerce_order_itemmeta_variation.meta_value > 0)";						
					}
					
					
					if($variations_formated  != "-1" and $variations_formated  != NULL){
						$sql .= " 
						AND woocommerce_order_itemmeta8.meta_key = '_variation_id' AND (woocommerce_order_itemmeta8.meta_value IS NOT NULL AND woocommerce_order_itemmeta8.meta_value > 0)";
						$sql .= " 
						AND postmeta_variation.meta_value IN ('{$variations_formated}')";
					}
					
					if($order_status  && $order_status != '-1' and $order_status != "'-1'")$sql .= " AND posts.post_status IN (".$order_status.")";//New Change ID 20140918
					
					if($hide_order_status  && $hide_order_status != '-1' and $hide_order_status != "'-1'")$sql .= " AND posts.post_status NOT IN (".$hide_order_status.")";//New Change ID 20140918
					
					if($order_item_id and $order_item_id != '-1')	$sql .= " AND woocommerce_order_items.order_item_id IN ({$order_item_id})";
					
					if($product_sku and $product_sku != '-1'){
						$sql .= " AND postmeta_product_sku.meta_key = '_sku' AND postmeta_product_sku.meta_value IN (".$product_sku.")";//New Change ID 20150226
					}
					
					if($variation_sku and $variation_sku != '-1'){
						$sql .= " AND woocommerce_order_itemmeta_variation_id.meta_key = '_variation_id'";
						$sql .= " AND postmeta_variation_sku.meta_key = '_sku' AND postmeta_variation_sku.meta_value IN (".$variation_sku.")";//New Change ID 20150226
					}
					
					$sql = apply_filters("ic_commerce_details_view_where_query", $sql, $request, $type, $page, $columns,$total_columns);
					
					$this->detail_sql_query = $sql;	
					
					//$this->print_sql($sql);
					
					//$this->print_array($request['wpa']);
					
					//die;
					
					$sql = "";
					
					
				}else{
					$sql = $this->detail_sql_query;
				}
				
				//$sql = $columns_sql;		
				$sql = $this->detail_sql_query;
				
				//if($type != 'total_row'){
				$group_sql = " GROUP BY woocommerce_order_items.order_item_id ";
				$sql .= apply_filters("ic_commerce_details_view_group_query", $group_sql, $request, $type, $page, $columns,$total_columns);
				//}
				
				
				$wpdb->query("SET SQL_BIG_SELECTS=1");
				/*
				if($type == 'total_row'){
					if($total_pages > 0){
						$order_items = $total_pages;
					}else{
						$order_items = $wpdb->get_var($sql);
						if(strlen($wpdb->last_error) > 0){
							echo $wpdb->last_error;
						}
						$wpdb->flush(); 
					}
					return $order_items;
					//return $order_items = count($order_items);
					
				}
				*/
				
				if($type == 'total_row'){
					if($this->all_row_result){
						if($count_generated == 1){
							$order_items = $this->create_summary($request);
							//$this->print_array($order_items);
							//echo "test1";
						}else{
							$order_items = $this->all_row_result;
							$order_items = $this->get_count_total($order_items,'total_price');
							//echo "test2";
						}
						
					}else{					
						if($count_generated == 1 || ($p > 1)){
							//$this->print_array($request);
							$order_items = $this->create_summary($request);
							//echo "test3";
						}else{
							$order_items = $wpdb->get_results($sql);
							if(strlen($wpdb->last_error) > 0){
								echo $wpdb->last_error;
								return array();
							}
							
							//$order_items = $this->join_order_itemmeta($order_items, $request, $type, $page, $columns);
							$order_items = apply_filters("ic_commerce_details_view_data_items", $order_items, $request, $type, $page, $columns,$total_columns);
							
							$summary = $this->get_count_total($order_items,'total_price');				
							$order_items = $summary;
						}					
					}								
					return $order_items;
				}
				
				if($type == 'limit_row'){					
					$order_sql = " ORDER BY {$sort_by} {$order_by}";
					$sql .= apply_filters("ic_commerce_details_view_order_query", $order_sql, $request, $type, $page, $columns,$total_columns);
					$sql .= " LIMIT $start, $limit";
					$order_items = $wpdb->get_results($sql);
					if(strlen($wpdb->last_error) > 0){
						echo $wpdb->last_error;
						return array();
					}
					$wpdb->flush(); 
				}
				
				if($type == 'limit_row'){
					//$this->print_array($order_items);
				}
				
				if($type == 'all_row' or $type == 'all_row_total'){
					$order_sql = " ORDER BY {$sort_by} {$order_by}";
					$sql .= apply_filters("ic_commerce_details_view_order_query", $order_sql, $request, $type, $page, $columns, $total_columns);
					$order_items = $wpdb->get_results($sql);
					$this->all_row_result = $order_items;
					if(strlen($wpdb->last_error) > 0){
						echo $wpdb->last_error;
						return array();
					}
					$wpdb->flush(); 
				}
				
				if($type == 'limit_row' || $type == 'all_row' or $type == 'all_row_total'){
					$categories = array();
					$order_meta = array();
					if(count($order_items)>0){
						$extra_meta_keys 	= apply_filters('ic_commerce_details_view_extra_meta_keys', array('billing_first_name','billing_last_name','order_currency'),$request, $type, $page, 'details_view', $columns);
						$post_ids 			= $this->get_items_id_list($order_items,'order_id');
						$postmeta_datas 	= $this->get_shop_order_postmeta($post_ids, $columns,$extra_meta_keys);
						//$this->print_array($postmeta_datas);
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
								
								$order_items[$key]->order_tax 			= isset($order_items[$key]->order_tax)			? $order_items[$key]->order_tax : 0;
								$order_items[$key]->order_shipping_tax 	= isset($order_items[$key]->order_shipping_tax)	? $order_items[$key]->order_shipping_tax : 0;
								$order_items[$key]->total_tax 			= $order_items[$key]->order_tax + $order_items[$key]->order_shipping_tax;
								
								$order_items[$key]->billing_first_name	= isset($order_items[$key]->billing_first_name)	? $order_items[$key]->billing_first_name 	: '';
								$order_items[$key]->billing_last_name	= isset($order_items[$key]->billing_last_name)	? $order_items[$key]->billing_last_name 	: '';
								$order_items[$key]->billing_name		= $order_items[$key]->billing_first_name.' '.$order_items[$key]->billing_last_name;
						}
					}
				}
				
				//$order_items = $this->join_order_itemmeta($order_items, $request, $type, $page, $columns);
				$order_items = apply_filters("ic_commerce_details_view_data_items", $order_items, $request, $type, $page, $columns,$total_columns);
				
				if(strlen($wpdb->last_error) > 0){
					echo $wpdb->last_error;
				}
				return $order_items;
		}
		
		function ic_commerce_custom_admin_report_ajax_request($type, $columns = array()) {
			
			if (!empty( $_POST['action'] ) ) {
				$detail_view 	= isset($_REQUEST['detail_view']) ? $_REQUEST['detail_view'] : 'no';
				if($detail_view == "yes"){					
					$columns 		= $this->grid_columns("details_view");
					$total_columns 	= $this->result_columns($detail_view);
					$this->ic_commerce_custom_report_detail($type, $columns, $total_columns );
				}else{
					$columns 		= $this->grid_columns("normal_view");
					$total_columns 	= $this->result_columns($detail_view);
					$this->ic_commerce_custom_report_normal($type, $columns,$total_columns);
				}
			}else{
				echo __("Something going wrong, contact to developer",'icwoocommerce_textdomains' );
			}
			die();
		}
		
		
		function ic_commerce_custom_admin_report_iframe_request($type, $columns ) {
			die();
		}
		
		function product_by_category_ajax_request() {
			$output_array 					= array();	
			$output_array['error_output'] 	= '';
			$output_array['error'] 			= 'false';
			$output_array['success'] 		= 'false';
			$output_array['success_output'] = '';
			//$output_array['post'] 		= $_POST;
			$message = "";	
			
			if (!empty( $_POST['action'] ) ) {
						global $wpdb;				
						$products = $this->get_product_data('all');
						if(count($products) <= 0){					
							$products = array(array("ID" => "-1","title" => __("Purchased product not found in selected category",'icwoocommerce_textdomains')));
						}
						
				$output_array['success'] = 'true';
				$output_array['success_output'] = $products;	
				//$output_array['sql'] = $sql;				
			}else{
				$output_array['error'] = 'true';
				$output_array['error_output'] = __("Something going wrong, contact to developer",'icwoocommerce_textdomains');
			}
			
			echo json_encode($output_array);
			die();
		}
		
		function ic_commerce_custom_report_page_export_csv(){
			global $wpdb, $table_prefix, $options;
			
			$detail_view			= $this->get_request('detail_view',"no");
			$billing_information 	= $this->get_request('billing_information',0);
			$shipping_information 	= $this->get_request('shipping_information',0);
			$export_file_name 		= $this->get_request('export_file_name',"no");
			$export_file_format 	= $this->get_request('export_file_format',"csv");
			$date_format			= get_option( 'date_format' );	
			
			if($detail_view == "yes"){
				$columns 		= $this->export_column("save_detail_column","details_view");
				$report_name	= "detailt_view";
			}else{
				$columns 		= $this->export_column("save_normal_column","normal_view");
				$report_name	= "normal_view";
			}
					
			$columns 				= apply_filters("ic_commerce_export_csv_columns_before_billing_information", $columns);
			
			if($billing_information == 1){
				$billing_columns = array(					
						"billing_first_name"	=>	__("Billing First Name",	'icwoocommerce_textdomains')
						,"billing_last_name"	=>	__("Billing Last Name",		'icwoocommerce_textdomains')
						,"billing_company"		=>	__("Billing Company",		'icwoocommerce_textdomains')
						,"billing_address_1"	=>	__("Billing Address 1",		'icwoocommerce_textdomains')
						,"billing_address_2"	=>	__("Billing Address 2",		'icwoocommerce_textdomains')
						,"billing_city"			=>	__("Billing City",			'icwoocommerce_textdomains')
						,"billing_postcode"		=>	__("Billing Post Code",		'icwoocommerce_textdomains')
						,"billing_country"		=>	__("Billing Country",		'icwoocommerce_textdomains')
						,"billing_state"		=>	__("Billing State",			'icwoocommerce_textdomains')
						,"billing_phone"		=>	__("Billing Phone",			'icwoocommerce_textdomains')
				);
				$columns 	= array_merge((array)$columns, (array)$billing_columns);
			}
			
			$columns = apply_filters("ic_commerce_export_csv_columns_before_shipping_information", $columns);
			
			if($shipping_information == 1){
				$shipping_columns = array(
						"shipping_first_name"	=>	__("Shipping First Name",	'icwoocommerce_textdomains')
						,"shipping_last_name"	=>	__("Shipping Last Name",	'icwoocommerce_textdomains')
						,"shipping_company"		=>	__("Shipping Company",		'icwoocommerce_textdomains')
						,"shipping_address_1"	=>	__("Shipping Address 1",	'icwoocommerce_textdomains')
						,"shipping_address_2"	=>	__("Shipping Address 2",	'icwoocommerce_textdomains')
						,"shipping_city"		=>	__("Shipping City",			'icwoocommerce_textdomains')
						,"shipping_postcode"	=>	__("Shipping Post Code",	'icwoocommerce_textdomains')
						,"shipping_country"		=>	__("Shipping Country",		'icwoocommerce_textdomains')
						,"shipping_state"		=>	__("Shipping State",		'icwoocommerce_textdomains')
				);
				$columns 	= array_merge((array)$columns, (array)$shipping_columns);
			}
			
			$columns = apply_filters("ic_commerce_export_csv_columns_after_shipping_information", $columns, $detail_view);
			
			if(isset($columns['invoice_action']))		unset($columns['invoice_action']);
			
			if($detail_view == "yes"){
				$total_columns 	= $this->result_columns($detail_view);
				$order_items 	= $this->ic_commerce_custom_report_detail_query('all_row', $columns, $total_columns);
				$summary 		= $this->ic_commerce_custom_report_detail_query('total_row', $columns, $total_columns);
			}else{
				$total_columns 	= $this->result_columns($detail_view);
				$order_items	= $this->ic_commerce_custom_report_normal_query('all_row', $columns, $total_columns);
				$summary		= $this->ic_commerce_custom_report_normal_query('total_row', $columns, $total_columns);
			}
			
			$order_items		= apply_filters("ic_commerce_details_page_export_csv_excel_data",$order_items,$columns, $export_file_format);
			
			$grid_object		= $this->get_grid_object();//Added 20150223
			$order_items		= $grid_object->create_grid_items($columns,$order_items);//Added 20150223

			$order_items		= apply_filters("ic_commerce_details_page_export_csv_excel_data_after_create_grid_items",$order_items,$columns, $export_file_format);
			
			$price_columns		= apply_filters("ic_commerce_price_columns",array());
			
			$date_format	= get_option( 'date_format' );
			$export_rows 	= array();			
			$i 				= 0;
			
			//Added 20150202
			$num_decimals   = get_option( 'woocommerce_price_num_decimals'	,	0		);
			$decimal_sep    = get_option( 'woocommerce_price_decimal_sep'	,	'.'		);
			$thousand_sep   = get_option( 'woocommerce_price_thousand_sep'	,	','		);			
			$zero			= number_format(0, $num_decimals,$decimal_sep,$thousand_sep);
			
			//Added New Function 20150202 for only currency and date formate
			foreach ( $order_items as $rkey => $rvalue ):
				$order_item = $rvalue;
				$td_value 	= '';
				foreach($columns as $key => $value):
					switch ($key) {
						case "order_shipping":
						case "order_shipping_tax":
						case "order_tax":
						case "gross_amount":
						case "order_discount":
						case "cart_discount":
						case "total_discount":
						case "total_tax":
						case "order_total":
						case 'product_rate':
						case 'total_price':
						case "refund_amount":
						case "order_refund_amount":
						case "part_order_refund_amount":
						case "sold_rate":
						case "difference_rate":
						case "item_amount":
						case "item_discount":
							$td_value 	=  isset($rvalue->$key) ? $rvalue->$key : 0;
							$td_value 	=  strlen($td_value)>0 ? $td_value : 0;
							$td_value	=  $td_value == 0 ? $zero : number_format($td_value, $num_decimals,$decimal_sep,$thousand_sep);
							break;
						case 'product_quantity':
						case 'total_order_count':
							$td_value 	=  isset($rvalue->$key) ? $rvalue->$key : 0;
							$td_value 	=  strlen($td_value)>0 ? $td_value : 0;
							break;
						case "order_date":
							$td_value	= isset($order_item->$key) ? date($date_format,strtotime($order_item->$key)) : '';
							break;
						default:
							if(isset($price_columns[$key])){
								$td_value 	=  isset($rvalue->$key) ? $rvalue->$key : '';
								$td_value 	=  strlen($td_value)>0 ? $td_value : 0;
								$td_value	=  $td_value == 0 ? $zero : number_format($td_value, $num_decimals,$decimal_sep,$thousand_sep);
							}else{
								$td_value 	=  isset($rvalue->$key) ? $rvalue->$key : '';
							}
							break;					
					}
					$export_rows[$i][$key]	=  $td_value;
				endforeach;
				$i++;
			endforeach;
			
			//$this->print_array($rows);
			//$this->print_array($summary);die;
			
			$summary		= apply_filters("ic_commerce_details_page_export_csv_excel_summary_data",$summary,$columns, $export_file_format);
			
			$total_label_flag = false;
			foreach($columns as $key => $value):					
				switch ($key) {					
						case "order_shipping":
						case "order_shipping_tax":
						case "order_tax":
						case "gross_amount":
						case "order_discount":
						case "cart_discount":
						case "total_discount":

						case "total_tax":
						case "order_total":
						case 'product_rate':
						case 'total_price':	
						case "refund_amount":	
						case "order_refund_amount":
						case "part_order_refund_amount":	
						case "sold_rate":
						case "difference_rate":
						case "item_amount":
						case "item_discount":		
							$td_value 	= isset($summary[$key]) ? $summary[$key] : '';
							$td_value 	=  strlen($td_value)>0 ? $td_value : 0;
							$td_value	=  $td_value > 0 ? number_format($td_value, $num_decimals,$decimal_sep,$thousand_sep) : $zero;//Added 20153001
							break;						
						case 'product_quantity':

						case 'total_order_count':
							$td_value 	= isset($summary[$key]) ? $summary[$key] : '';
							$td_value 	=  strlen($td_value)>0 ? $td_value : 0;
							break;
						case "order_id":
							if($total_label_flag)
								$td_value = "";
							else{
								$td_value = "Total";
								$total_label_flag = true;
							}
							break;						
						case 'order_id':
							case 'billing_name':
							case 'billing_email':
							//case 'order_date':
							case 'status':
							case 'tax_name':
							case 'coupon_code':
							case 'item_count':
							case 'product_name':
							case 'billing_first_name':
							case 'billing_last_name':
							case 'billing_company':
							case 'billing_address_1':
							case 'billing_address_2':
							case 'billing_city':
							case 'billing_postcode':						
							case 'billing_phone':
							case 'shipping_first_name':
							case 'shipping_last_name':
							case 'shipping_company':
							case 'shipping_address_1':
							case 'shipping_address_2':
							case 'shipping_city':
							case 'shipping_postcode':
							case 'billing_country':
							case 'shipping_country':								
							case 'billing_state':	
							case 'shipping_state':								
							case 'product_sku':	
							case 'variation_sku':			
							case 'order_shipping':
							case 'order_shipping_tax':
							case 'order_tax':
							case 'gross_amount':
							case "order_discount":
							case "cart_discount":
							case "total_discount":
							case 'order_total':	
							case "refund_amount":
							case 'product_rate':
							case 'total_price':
							case "category_name":
							case "product_quantity":
							case 'product_variation':
							case "ic_commerce_product_category_name":
							case 'ic_commerce_product_product_variation':
							//case "ic_commerce_order_status_name":
							case "ic_commerce_order_tax_name":
							case "ic_commerce_order_coupon_codes":														
							case "ic_commerce_order_status_id":															
							case "ic_commerce_order_item_count":
							case "ic_commerce_order_billing_name":
							case "ic_commerce_order_billing_country":
							case "ic_commerce_order_shipping_country":
							case "ic_commerce_order_billing_sate":	
							case "ic_commerce_order_shipping_sate":
							case "ic_commerce_order_gross_amount":
							case "ic_commerce_order_shipping_method":
							case "order_date"://New Change ID 20140918
							case "ic_commerce_order_status_name":
							case "order_status"://New Change ID 20140918
							case "transaction_id"://New Change ID 20150203
							case "billing_country"://20150216
							case "shipping_country"://20150216							
							case "billing_state"://20150216
							case "shipping_state"://20150216
							case "shipping_method_title"://20150216
							$td_value = '';
							break;
						default:
							if(isset($price_columns[$key])){
								$td_value = isset($summary[$key]) ? $summary[$key] : '';
								$td_value 	=  strlen($td_value)>0 ? $td_value : 0;
								$td_value	=  $td_value > 0 ? number_format($td_value, $num_decimals,$decimal_sep,$thousand_sep) : $zero;//Added 20153001
								break;								
							}else{
								$td_value = isset($summary[$key]) ? $summary[$key] : '';
							}
							break;
					}
					$export_rows[$i][$key] = $td_value;
			endforeach;
			
			//$this->print_array($export_rows);die;
			
			/*New Added 20160130*/
			$today_date 		= date_i18n("Y-m-d-H-i-s");				
			$export_filename 	= $export_file_name."-".$report_name."-".$today_date.".".$export_file_format;
			$export_filename 	= apply_filters('ic_commerce_export_csv_excel_format_file_name',$export_filename,$report_name,$today_date,$export_file_name,$export_file_format);
			do_action("ic_commerce_export_csv_excel_format",$export_filename,$export_rows,$columns,$export_file_format,$report_name);
			$out = $this->ExportToCsv($export_filename,$export_rows,$columns,$export_file_format,$report_name);
		}
		
		function ExportToCsv($filename = 'export.csv',$rows,$columns,$format="csv"){				
			global $wpdb;
			$csv_terminated = "\n";
			$csv_separator = ",";
			$csv_enclosed = '"';
			$csv_escaped = "\\";
			$fields_cnt = count($columns); 
			$schema_insert = '';
			
			if($format=="xls"){
				$csv_terminated = "\r\n";
				$csv_separator = "\t";
			}
				
			foreach($columns as $key => $value):
				$l = $csv_enclosed . str_replace($csv_enclosed, $csv_escaped . $csv_enclosed, $value) . $csv_enclosed;
				$schema_insert .= $l;
				$schema_insert .= $csv_separator;
			endforeach;// end for
		 
		   $out = trim(substr($schema_insert, 0, -1));
		   $out .= $csv_terminated;
			
			//printArray($rows);
			
			for($i =0;$i<count($rows);$i++){  
				
				//printArray($rows[$i]);
				$j = 0;
				$schema_insert = '';
				foreach($columns as $key => $value){
						
						
						 if ($rows[$i][$key] == '0' || $rows[$i][$key] != ''){
							if ($csv_enclosed == '')
							{
								$schema_insert .= $rows[$i][$key];
							} else
							{
								$schema_insert .= $csv_enclosed . str_replace($csv_enclosed, $csv_escaped . $csv_enclosed, $rows[$i][$key]) . $csv_enclosed;
							}
						 }else{
							$schema_insert .= '';
						 }
						
						
						
						if ($j < $fields_cnt - 1)
						{
							$schema_insert .= $csv_separator;
						}
						$j++;
				}
				$out .= $schema_insert;
				$out .= $csv_terminated;
			}			
			
			if($format=="csv"){
				header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
				header("Content-Length: " . strlen($out));	
				header("Content-type: text/x-csv");
				header("Content-type: text/csv");
				header("Content-type: application/csv");
				//header("Content-Type: text/html; charset=utf-8");
				header("Content-Disposition: attachment; filename=$filename");
			}elseif($format=="xls"){
				
				header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
				header("Content-Length: " . strlen($out));
				header("Content-type: application/octet-stream");
				header("Content-Disposition: attachment; filename=$filename");
				header("Pragma: no-cache");
				header("Expires: 0");
			}
					
			echo $out;
			exit;
		 
		}
		
		function ic_commerce_custom_report_page_export_pdf($export_file_format = "pdf"){
			global $wpdb, $table_prefix;
			
			$detail_view		= $this->get_request('detail_view',"no");
			$date_format		= get_option( 'date_format' );
			$zero				= $this->price(0);
			$zero_prize			= array();
			
			if($detail_view == "yes"){
				$columns 		= $this->export_column("save_detail_column","details_view");
			}else{
				$columns 		= $this->export_column("save_normal_column","normal_view");
			}
			
			$columns			= apply_filters("ic_commerce_details_page_export_pdf_columns",$columns,$detail_view);
			
			if($detail_view == "yes"){
				$total_columns 	= $this->result_columns($detail_view);
				$order_items	= $this->ic_commerce_custom_report_detail_query('all_row', $columns, $total_columns);
				$summary		= $this->ic_commerce_custom_report_detail_query('total_row', $columns, $total_columns);				
			}else{
				$total_columns 	= $this->result_columns($detail_view);
				$order_items	= $this->ic_commerce_custom_report_normal_query('all_row', $columns,$total_columns);
				$summary 		= $this->ic_commerce_custom_report_normal_query('total_row', $columns,$total_columns);				
			}
			
			if(isset($columns['invoice_action']))		unset($columns['invoice_action']);
			
			$grid_object		= $this->get_grid_object();//Added 20150223
			$order_items		= $grid_object->create_grid_items($columns,$order_items);//Added 20150223			
			
			$order_items		= apply_filters("ic_commerce_details_page_export_pdf_data",$order_items,$columns, $zero);
			
			$price_columns		= apply_filters("ic_commerce_price_columns",array());
			
			$this->get_woocommerce_currency_symbol_pdf();
			
			$export_rows = array();
			$i = 0;
			foreach ( $order_items as $rkey => $rvalue ):
				$order_item = $rvalue;
				
				$zero_prize[$order_item->order_currency] = isset($zero_prize[$order_item->order_currency]) ? $zero_prize[$order_item->order_currency] : $this->price(0, array('currency' => $order_item->order_currency));
				
				foreach($columns as $key => $value):
					switch ($key) {
						case 'order_shipping':
						case 'order_shipping_tax':
						case 'order_tax':
						case 'gross_amount':
						case "order_discount":
						case "cart_discount":
						case "total_discount":
						case 'order_total':	
						case 'product_rate':
						case 'total_price':
						case "refund_amount":
						case "total_tax":		
						case "order_refund_amount":
						case "part_order_refund_amount":
						case "sold_rate":
						case "difference_rate":
						case "item_amount":
						case "item_discount":
							$td_value 				=  isset($rvalue->$key) ? $rvalue->$key : 0;
							$export_rows[$i][$key]	= wp_strip_all_tags($td_value > 0 ? $this->price($td_value, array('currency' => $order_item->order_currency)) : $zero_prize[$order_item->order_currency]);
							break;
						case "order_date":
							$export_rows[$i][$key] = isset($order_item->$key) ? date($date_format,strtotime($order_item->$key)) : '';
							break;						
						default:
							if(isset($price_columns[$key])){
								$td_value 				=  isset($rvalue->$key) ? $rvalue->$key : 0;
								$export_rows[$i][$key]	= wp_strip_all_tags($td_value > 0 ? $this->price($td_value, array('currency' => $order_item->order_currency)) : $zero_prize[$order_item->order_currency]);
							}else{
								$td_value 	=  isset($rvalue->$key) ? $rvalue->$key : '';
								$export_rows[$i][$key] = $td_value;
							}
							break;
					}
				endforeach;
				$i++;
			endforeach;
			
			$output = $this->GetPdfDataGrid($export_rows,$columns,$summary);			
			$this->export_to_pdf($export_rows,$output);
		}
		
		
		function get_product_name($id, $by = 'ID'){
			global $wpdb;
			$sql = "SELECT post_title  FROM {$wpdb->prefix}posts  AS posts	WHERE posts.ID='{$id}' LIMIT 1";
								
			return $first_order_date = $wpdb->get_var($sql );
		}
		
		function get_client_name($email,$name = NULL){			
			global $wpdb;
			$sql = "SELECT 
				postmeta1.meta_value AS billing_email, 
				CONCAT( postmeta2.meta_value, ' ', postmeta3.meta_value ) AS billing_name
				FROM `{$wpdb->prefix}postmeta` AS postmeta1
				LEFT JOIN {$wpdb->prefix}postmeta AS postmeta2 ON postmeta2.post_id = postmeta1.post_id
				LEFT JOIN {$wpdb->prefix}postmeta AS postmeta3 ON postmeta3.post_id = postmeta1.post_id
				WHERE postmeta1.meta_key = '_billing_email'
				AND postmeta1.meta_value = '{$email}'
				AND postmeta2.meta_key = '_billing_first_name'
				AND postmeta3.meta_key = '_billing_last_name'
				GROUP BY postmeta1.meta_value
				LIMIT 0 , 1";
				return $wpdb->get_row($sql);
		}
		
		function page_title($title){
			$title = str_replace("_"," ",$title);
			$title = str_replace("-"," ",$title);
			//$title = Ucwords($title);
			return $title;
		}
		
		function ic_commerce_save_normal_column($name){
			$key = $this->get_column_key($name);
			unset($_POST['do_action_type']);
			unset($_POST['action']);
			unset($_POST['ic_admin_page']);
			update_option($key,$_POST);
			die();
			exit;
		}
		
		function get_column_key($name){
			$page			= $this->get_request('ic_admin_page','report');
			return $key 	= $page.'_'.$name;
		}
		
		function ic_commerce_option($key){			
			$store_columns =  get_option($key);
			return $store_columns;
		}
		
		
		
		function default_active($key = '', $detail_column = false, $v = 0){
			$store_columns = $this->ic_commerce_option($key);
			
			if(!$store_columns) $v = 1;
			
			//if(!$active_columns){
				
				$default_columns = array();
				$columns 		= $this->grid_columns($detail_column);
				foreach($columns as $key2 => $value):
					$default_columns[$key2]= $v;
				endforeach;
				
				if($v == 1){ update_option($key,$default_columns);}
				$active_columns = array_merge((array)$default_columns, (array)$store_columns);
					
			//}
			
			return $active_columns;
			
		}
		
		function hide_display($key, $active_columns){
			return $display = (isset($active_columns[$key]) && $active_columns[$key] == 1) ? '' : ' style="display:none"';
		}
		
		function create_checkbox($column_key = '', $detail_column = "details_view", $add_class = "_order_checkbox"){
			$key = $this->get_column_key($column_key);
			$active_columns = $this->default_active($key, $detail_column);
			//$this->print_array($active_columns);
			$checkbox = '';
			if($detail_column == "normal_view"){
				$columns = $this->grid_columns($detail_column);
				
				foreach($active_columns as $key => $value):					
					$label = isset($columns[$key]) ? $columns[$key] : false;
					if("order_id_checkbox" == $key){
						$add_class2 = "";	
					}else{
						$add_class2 = $add_class;
					}
					if($label){
											
						$checked = ($value == 1) ? ' checked="checked"' : '';
						$name = $id = $key."_".$column_key;
						$checkbox .= "\n<label for=\"{$name}\" class=\"columns_label {$column_key} label_{$name} {$add_class}\"><input type=\"checkbox\" data-columnkey=\"{$column_key}\"  data-name=\"{$key}\" name=\"{$name}\" id=\"{$name}\" value=\"1\"{$checked}> {$label}</label>";
						
					}
					
				endforeach;
				
			}else{
				$order_columns = $this->details_view_columns("order_columns");
				$add_class = "order_checkbox";
				foreach($order_columns as $key => $label):	
					if("invoice_action" == $key){
						$add_class2 = "";	
					}else{
						$add_class2 = $add_class;
					}			
					$value = isset($active_columns[$key]) ? $active_columns[$key] : false;
					
					if($label){											
						$checked = ($value == 1) ? ' checked="checked"' : '';
						$name = $id = $key."_".$column_key;
						$checkbox .= "\n<label for=\"{$name}\" class=\"columns_label {$column_key} label_{$name} {$add_class2}\"><input type=\"checkbox\" data-columnkey=\"{$column_key}\"  data-name=\"{$key}\" name=\"{$name}\" id=\"{$name}\" value=\"1\"{$checked}> {$label}</label>";
					}					
				endforeach;
				
				$product_columns = $this->details_view_columns("product_columns");
				$add_class = "product_checkbox";
				foreach($product_columns as $key => $label):	
					//echo $key;
					if("invoice_action" == $key){
						$add_class2 = "";	
					}else{
						$add_class2 = $add_class;
					}
					$value = isset($active_columns[$key]) ? $active_columns[$key] : false;
					
					if($label){											
						$checked = ($value == 1) ? ' checked="checked"' : '';
						$name = $id = $key."_".$column_key;
						$checkbox .= "\n<label for=\"{$name}\" class=\"columns_label {$column_key} label_{$name} {$add_class2}\"><input type=\"checkbox\" data-columnkey=\"{$column_key}\"  data-name=\"{$key}\" name=\"{$name}\" id=\"{$name}\" value=\"1\"{$checked}> {$label}</label>";
					}					
				endforeach;
			}
			
			
			echo $checkbox;
		}
		
		
		
		function export_column($column_key = '', $detail_column = false){
			$key = $this->get_column_key($column_key);
				$active_columns = $this->default_active($key, $detail_column);
				$columns = $this->grid_columns($detail_column);
				
				$new_columns = array();
								
				foreach($active_columns as $key => $value):
					if($value == 1){
						$new_columns[$key]= isset($columns[$key]) ? $columns[$key] : '' ;
					}
				endforeach;
				return $columns = $new_columns;
		}
		
		function delete_column_option(){
			if(!isset($_GET['delete_column_option'])) return ;
			
			
			$key = $this->get_column_key("save_normal_column");
			delete_option($key);
			$p = $this->default_active($key, false, 1);
			
			$key = $this->get_column_key("save_detail_column");
			delete_option($key);
			$p = $this->default_active($key, true, 1);
		}
		
		function get_all_request(){
			global $request;
			if(!$this->request){
				
				do_action("ic_commerce_detail_page_before_default_request");
				
				$request 									= array();		
				$default_request							= array();
				$default_request['start_date'] 				=  NULL;
				$default_request['end_date'] 				=  NULL;
				$default_request['order_id'] 				=  '';
				$default_request['txtProduct'] 				=  '-1';
				$default_request['product_id'] 				=  '-1';
				$default_request['billing_name'] 			=  '';				
				$default_request['order_meta_key'] 			=  '-1';
				$default_request['min_amount'] 				=  '-1';
				$default_request['max_amount'] 				=  '-1';
				$default_request['billing_email'] 				=  '';
				$default_request['sort_by'] 				=  'order_id';
				$default_request['order_by'] 				=  'DESC';
				$default_request['hide_order_status'] 		=  '';
				$default_request['publish_order'] 			=  'no';
				$default_request['order_item_name'] 		=  '';
				$default_request['coupon_code'] 			=  '';
				$default_request['coupon_codes'] 			=  '';
				$default_request['payment_method'] 			=  '';
				$default_request['limit'] 					=  '5';
				$default_request['p'] 						=  '1';
				$default_request['action'] 					=  '';
				$default_request['page'] 					=  '';
				$default_request['admin_page'] 				=  '';
				$default_request['ic_admin_page'] 			=  '';
				$default_request['adjacents'] 				=  '3';
				$default_request['purchased_product_id'] 	=  '-1';
				$default_request['do_action_type'] 			=  'detail_page';
				$default_request['page_title'] 				=  'Detail Search';
				$default_request['total_pages'] 			=  '0';
				$default_request['variation_id'] 			=  '-1';
				$default_request['variation_only'] 			=  '-1';
				$default_request['amount_greater_zero'] 	=  '-1';
				$default_request['count_generated'] 		=  '0';
				$default_request['date_format'] 			=  'F j, Y';
				$default_request['page_name'] 				=  'all_detail';
				$default_request['onload_search'] 			=  'yes';
				$default_request['category_id']	 			= "-1";
				$default_request['detail_view']	 			= "no";
				$default_request['coupon_used']				= NULL;
				$default_request['order_status']			= NULL;
				$default_request['coupon_code']				= NULL;
				$default_request['coupon_codes']			= NULL;
				$default_request['country_code']			= NULL;
				$default_request['state_code']	 			= "-1";
				$default_request['payment_method']			= NULL;
				$default_request['paid_customer']	 		= "-1";
				$default_request['billing_postcode'] 		= "-1";
				$default_request['order_date_field_key'] 	= "post_date";
				$default_request['billing_country'] 		= "-1";
				$default_request['product_sku'] 			= "-1";
				$default_request['variation_sku'] 			= "-1";
				$default_request['order_status_id'] 		= "-1";
				
				
				$_REQUEST 									= array_merge((array)$default_request, (array)$_REQUEST);				
				$start_date 								= $_REQUEST['start_date'];
				$end_date 									= $_REQUEST['end_date'];				
				$order_status_id							= is_array($_REQUEST['order_status_id']) 	? implode(",", $_REQUEST['order_status_id']) 	: $_REQUEST['order_status_id'];
				$category_id 								= is_array($_REQUEST['category_id']) 		? implode(",", $_REQUEST['category_id']) 		: $_REQUEST['category_id'];
				$limit										= $_REQUEST['limit'];
				$p											= $_REQUEST['p'];
				$detail_view 								= $_REQUEST['detail_view'];
				$order_meta_key 							= is_array($_REQUEST['order_meta_key']) 	? implode(",", $_REQUEST['order_meta_key']) : $_REQUEST['order_meta_key'];
				$max_amount									= $_REQUEST['max_amount'];
				$min_amount									= $_REQUEST['min_amount'];
				
				if(strlen($max_amount)<=0) $_REQUEST['max_amount']	= 	$max_amount = '-1';
				if(strlen($min_amount)<=0) $_REQUEST['min_amount']	=	$min_amount = '-1';
				
				if($max_amount != '-1' || $min_amount != '-1'){
					if($order_meta_key == '-1'){
						$order_meta_key	= "_order_total";
					}					
				}	
				
				$_REQUEST['max_amount']						= (strlen($max_amount)<=0) ? '-1' 	: $max_amount;
				$_REQUEST['min_amount']						= (strlen($min_amount)<=0) ? '-1' 	: $min_amount;
				$_REQUEST['start']							= ($p > 1) ? (($p - 1) * $limit) 	: 0;				
				$_REQUEST['order_status_id']				= $order_status_id;
				$_REQUEST['category_id']					= $category_id;
				$_REQUEST['order_meta_key']					= $order_meta_key;
				
				//$this->print_array($_REQUEST['order_meta_key']);
				
				$this->common_request_form();
				
				if($detail_view == "yes"){
					$variations_value		= $this->get_request('variations_value',"-1",true);
					$variations_formated = '-1';
					if($variations_value != "-1" and strlen($variations_value)>0){
						$variations_value = explode(",",$variations_value);				
						$var = array();
						foreach($variations_value as $key => $value):
							$var[] .=  $value;
						endforeach;
						$result = array_unique ($var);
						//$this->print_array($var);
						$variations_formated = implode("', '",$result);
					}
					$_REQUEST['variations_formated'] = $variations_formated;
				}
				
				if(isset($_REQUEST)){
					$REQUEST = $_REQUEST;
					$REQUEST = apply_filters("ic_commerce_before_request_creation", $REQUEST);
					foreach($REQUEST as $key => $value ):						
						$request[$key] =  $this->get_request($key,NULL);
					endforeach;
					$request = apply_filters("ic_commerce_after_request_creation", $request);
				}
				$this->request = $request;
								
				//$this->print_array($request);
			}else{				
				$request = $this->request;
			}
			
			
			
			return $request;
		}
		
		//get_chekbox('', $data, 0)
		function get_chekbox($id, $data, $defalut = NULL){
			if(isset($data[$id]))
				return $data[$id];
			else

				return $defalut;
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
		
		function get_country_state(){
			global $wpdb;
			$sql = "SELECT 
					billing_country.meta_value as parent_id,
					billing_state.meta_value as id,
					CONCAT(billing_country.meta_value,'-', billing_state.meta_value) billing_country_state
					
					FROM `{$wpdb->prefix}posts` AS posts
					LEFT JOIN {$wpdb->prefix}postmeta as billing_state ON billing_state.post_id=posts.ID
					LEFT JOIN {$wpdb->prefix}postmeta as billing_country ON billing_country.post_id=posts.ID
					
					WHERE 
					billing_state.meta_key='_billing_state' 
					AND billing_country.meta_key='_billing_country' 
					AND posts.post_type='shop_order'
					AND LENGTH(billing_state.meta_value) > 0
					GROUP BY billing_country_state
					ORDER BY billing_state.meta_value ASC
			";
			
			$results	= $wpdb->get_results($sql);
			
			foreach($results as $key => $value):
					$v = $this->get_state($value->parent_id, $value->id);
					$v = trim($v);
					if(strlen($v)>0)
						$results[$key]->label = $v ." (".$value->parent_id.")";
					else
						unset($results[$key]);
			endforeach;
			
			return $results;
			
			//$this->print_array($results);
		}
		
		function get_paying_state($state_key = 'billing_state',$country_key = false, $deliter = "-"){
				global $wpdb;
				if($country_key){
					//$sql = "SELECT CONCAT(billing_country.meta_value,'{$deliter}', billing_by.meta_value) as id, billing_by.meta_value as label, billing_country.meta_value as billing_country ";
					$sql = "SELECT billing_by.meta_value as id, billing_by.meta_value as label, billing_country.meta_value as billing_country ";
				}else
					$sql = "SELECT billing_by.meta_value as id, billing_by.meta_value as label ";
				
				$sql .= "
					FROM `{$wpdb->prefix}posts` AS posts
					LEFT JOIN {$wpdb->prefix}postmeta as billing_by ON billing_by.post_id=posts.ID";
				if($country_key)
					$sql .= " 
					LEFT JOIN {$wpdb->prefix}postmeta as billing_country ON billing_country.post_id=posts.ID";
				$sql .= "
					WHERE billing_by.meta_key='_{$state_key}' AND posts.post_type='shop_order'
				";
				
				if($country_key)
					$sql .= "
					AND billing_country.meta_key='_{$country_key}'";
				
				$sql .= " 
				GROUP BY billing_by.meta_value
				ORDER BY billing_by.meta_value ASC";
				
				$results	= $wpdb->get_results($sql);
				$country    = $this->get_wc_countries();//Added 20150225
				
				if($country_key){
					foreach($results as $key => $value):
							$v = $this->get_state($value->billing_country, $value->label);
							$v = trim($v);
							if(strlen($v)>0)
								$results[$key]->label = $v ." (".$value->billing_country.")";
							else
								unset($results[$key]);
					endforeach;
				}else{
					
					foreach($results as $key => $value):
							$v = isset($country->countries[$value->label]) ? $country->countries[$value->label]: $value->label;
							$v = trim($v);
							if(strlen($v)>0)
								$results[$key]->label = $v;
							else
								unset($results[$key]);
					endforeach;
				}
				return $results; 
		}
		
		function get_state($cc = NULL,$st = NULL){
			global $woocommerce;
			$state_code = $st;
			
			if(!$cc) return $state_code;
			
			$states = $this->get_wc_states($cc);//Added 20150225
			
			if(is_array($states)){
				foreach($states as $key => $value){
					if($key == $state_code)
						return $value;
				}
			}else if(empty($states)){
				return $state_code;
			}			
			return $state_code;
		}
		
		function _get_setting($id, $data, $defalut = NULL){
			if(isset($data[$id]))
				return $data[$id];
			else
				return $defalut;
		}
		
		function print_header($type = NULL){
			$out = "";
			
			if($type == 'all_row'){
				
				$company_name	= $this->get_request('company_name','');
				$report_title	= $this->get_request('report_title','');
				$display_logo	= $this->get_request('display_logo','');
				$display_date	= $this->get_request('display_date','');
				$display_center	= $this->get_request('display_center','');
				$date_format	= $this->get_request('date_format','jS F Y');
				
				$print_header_logo = "print_header_logo";				
				if($display_center) $print_header_logo .= " center_header";
				
				$out .= "<div class=\"print_header\">";
				if($company_name or $display_logo){
					$out .= "	<div class=\"".$print_header_logo."\">";
					if(strlen($company_name) > 0)	$out .= "<div class='header'><h2>".stripslashes($company_name)."</h2></div>";
					if(strlen($display_logo) > 0 and $display_logo == 1){
						$logo_image = $this->get_setting('logo_image',$this->constants['plugin_options'], '');
						$out 		.= "<div class='clear'><img src='".$logo_image."' alt='' /></div>";
					}				
					$out .= "	</div>";
				}
				if(strlen($report_title) > 0)	$out .= "<div class='clear'><label class=\"report_title\">".stripslashes($report_title)."</label></div>";
				if(strlen($display_date) > 0)	$out .= "<div class='Clear'><label>Report Date: </label> <label>".date_i18n($date_format)."</label></div>";
				$out .= "</div>";
			}else{
				//if($report_title) echo "<h2>".$report_title."</h2>";
			}
			
			echo $out;		
		}
		
		//New Change ID 20140918
		var $terms_by = array();
		function get_category_name_by_product_id($id, $taxonomy = 'product_cat', $termkey = 'name'){
			$term_name ="";			
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
		
		function get_variation($order_item_id = 0){
			global $wpdb;
				$v = "";
				$sql = "
				SELECT 
				postmeta_variation.meta_value AS product_variation,
				woocommerce_order_itemmeta_variation_id.meta_value as variation_id
				FROM {$wpdb->prefix}woocommerce_order_items as woocommerce_order_items
				LEFT JOIN  {$wpdb->prefix}woocommerce_order_itemmeta as woocommerce_order_itemmeta ON woocommerce_order_itemmeta.order_item_id = woocommerce_order_items.order_item_id
				LEFT JOIN  {$wpdb->prefix}postmeta as postmeta_variation ON postmeta_variation.post_id = woocommerce_order_itemmeta_variation_id.meta_value
				WHERE woocommerce_order_items.order_item_id={$order_item_id}
				
				AND woocommerce_order_items.order_item_type = 'line_item'
				AND woocommerce_order_itemmeta_variation_id.meta_key = '_variation_id'
				AND postmeta_variation.meta_key like 'attribute_%'";
				
				$order_items = $wpdb->get_results($sql);
				
				if(count($order_items) > 0){					
					$variation_id = isset($order_items[0]->variation_id) ? $order_items[0]->variation_id : 0;
					if(isset($this->stored_variations[$variation_id])){
						$v = $this->stored_variations[$variation_id];
					}else{
						$variation = array();
						if($order_items)
						foreach($order_items as $key=>$value){
							$variation[] = $value->product_variation;
						}
						$v = ucwords (implode(", ", $variation));
						
						$v = str_replace("-"," ",$v);
						$this->stored_variations[$variation_id] = $v;
						//$this->print_array($this->stored_variations);
					}
					
				}
				return $v;
		}
		
		function get_attributes($_variations = '-1'){
				global $wpdb;
				
				//$request = $this->get_all_request();
				//extract($request);
				
				
				//$_product_attributes = get_post_meta(481,'_product_attributes');			
				//$this->print_array($_product_attributes);
				
				//$attribute_taxonomies = get_option('_transient_wc_attribute_taxonomies');
				//$this->print_array($attribute_taxonomies);
			
				$sql = "	SELECT 
							postmeta_variation.meta_key AS variation_key
							,postmeta_variation.meta_value AS variation_name";
				$sql .= "	FROM {$wpdb->prefix}woocommerce_order_items as woocommerce_order_items						
							LEFT JOIN  {$wpdb->prefix}woocommerce_order_itemmeta as woocommerce_order_itemmeta8 ON woocommerce_order_itemmeta8.order_item_id = woocommerce_order_items.order_item_id
							LEFT JOIN  {$wpdb->prefix}postmeta as postmeta_variation ON postmeta_variation.post_id = woocommerce_order_itemmeta8.meta_value";
				
				$sql .= "	WHERE postmeta_variation.meta_key like 'attribute_%'";
				$sql .= "	AND woocommerce_order_itemmeta8.meta_key = '_variation_id' AND woocommerce_order_itemmeta8.meta_value IS NOT NULL AND woocommerce_order_itemmeta8.meta_value > 0				
						 	GROUP BY postmeta_variation.meta_key";
				$items = $wpdb->get_results($sql);
				
				$variations = array();
				if($_variations != '-1')
					$_variations			= $this->get_request('variations','-1',true);
					
				if($_variations == '-1'){
					foreach($items as $key => $value):
						$var = $value->variation_key;
						//$var = $this->attribute_label($value->variation_key, $value->variation_name);
						$var = str_replace("attribute_pa_","",$var);
						$var = str_replace("attribute_","",$var);
						$var2 = str_replace("-"," ",$var);
						$variations[$var] = ucfirst($var2);
					endforeach;
				}else{
					$_variations = explode(",",$_variations);
					
					//this->print_array($_variations);
					foreach($items as $key => $value):
						$var = $value->variation_key;
						//$var = $this->attribute_label($value->variation_key, $value->variation_name);
						$var = str_replace("attribute_pa_","",$var);
						$var = str_replace("attribute_","",$var);
						$var2 = str_replace("-"," ",$var);
						
						if(in_array($var, $_variations))
							$variations[$var] = ucfirst($var2);
					endforeach;
				}				
				asort($variations);				
				return $variations;
		}
		
		function get_variation_values($variation_attributes = NULL, $all_attributes = NULL){
			global $wpdb;
			//
				$sql = "
				SELECT
				postmeta_variation.meta_value AS variation 
				,postmeta_variation.meta_key AS attribute
				FROM {$wpdb->prefix}woocommerce_order_items as woocommerce_order_items
				LEFT JOIN  {$wpdb->prefix}woocommerce_order_itemmeta as woocommerce_order_itemmeta ON woocommerce_order_itemmeta.order_item_id = woocommerce_order_items.order_item_id
				LEFT JOIN  {$wpdb->prefix}postmeta as postmeta_variation ON postmeta_variation.post_id = woocommerce_order_itemmeta_variation_id.meta_value";
				
				$var = array();
				if($variation_attributes != NULL and $variation_attributes != '-1' and strlen($variation_attributes) > 0){
					$variations = explode(",",$variation_attributes);
					foreach($variations as $key => $value):
						$var[] .=  "attribute_pa_".$value;
						$var[] .=  "attribute_".$value;
					endforeach;
					$variation_attributes =  implode("', '",$var);
				}
				$sql .= "
				
				WHERE 
				
				woocommerce_order_items.order_item_type = 'line_item'
				AND woocommerce_order_itemmeta_variation_id.meta_key = '_variation_id'
				AND postmeta_variation.meta_key like 'attribute_%'";
				
				if($variation_attributes != NULL and $variation_attributes != "-1" and strlen($variation_attributes)>1)
					$sql .= " AND postmeta_variation.meta_key IN ('{$variation_attributes}')";
				else				
					$sql .= " AND postmeta_variation.meta_key like 'attribute_%'";
				
				
				/*if($variation_attributes != NULL and $variation_attributes != "-1" and strlen($variation_attributes)>1)
					$sql .= " AND postmeta_variation.meta_key IN ('{$variation_attributes}')";
				else				
					$sql .= " AND postmeta_variation.meta_key like 'attribute_%'";*/
				
				/*	
				
				*/
				$items = $wpdb->get_results($sql);
				////echo mysql_error();
				
				//$this->print_array($items);
				
				$variations = array();
				$variations2 = array();
				foreach($items as $key => $value):
					if(!isset($variations2[$value->variation])){
						$var = $value->attribute;
						$var = str_replace("attribute_pa_","",$var);
						$var = str_replace("attribute_","",$var);
						
						
						$var2 = $value->variation;
						if(strlen($var2)>0){
							$var2 = str_replace("-"," ",$var2);
						}else{
							$var2 = $var;
						}
						//$variations[$var] = ucfirst($var2);						
						$variations2[$value->variation] = ucfirst($var2);
					}
						
					
				endforeach;	
				
				return $variations2;
		}// END get_variation_values
		
		//New Change ID 20140918
		function invoice_action_btn($pdf_invoice, $order_id){
					
			$downloadimg = WP_PLUGIN_URL ."/". $this->constants['plugin_folder'] ."/assets/images/icons/download-icon.png";
			$plugin_key = $this->constants['plugin_key'];//Added 20150205
			$invoice_action = "";
			//echo "{$plugin_key}_pdf_invoice_download";
			$invoice_action .= 	"<a href=\"{$pdf_invoice}&plugin_key={$plugin_key}&bulk_action={$plugin_key}_pdf_invoice_download&invoice_id={$order_id}&order_id={$order_id}\" class=\"pdf_invoice_show\" title=\"Download invoice #{$order_id}\"><img src='".$downloadimg."' alt=\"Download invoice #{$order_id}\" /></a>";
			return $invoice_action;
		}
		
		function get_count_total($data,$amt = 'total_amount'){
			$total = 0;
			$return = array();
			$detail_view 		= $this->get_request('detail_view','no');
			$total_columns 		= $this->result_columns($detail_view);
			$order_status		= array();
			$orders				= array();
			//$this->print_array($data);
			if(count($total_columns) > 0){
				foreach($data as $key => $value){
					$total = $total + (isset($value->$amt) ? $value->$amt : 0);
					
					foreach($total_columns as $ckey => $label):
						$v = isset($value->$ckey) ? trim($value->$ckey) : 0;
						$v = empty($v) ? 0 : $v;
						
						/*
						if($ckey == 'bottle_quantity'){
							echo $return[$ckey];echo $ckey;
						}
						*/
						
						$return[$ckey] 	= isset($return[$ckey])	? ($return[$ckey] + $v): $v;
						
						//$return[$ckey] 	= isset($value->$ckey)? (isset($return[$ckey])	? ($return[$ckey] + $value->$ckey): $value->$ckey) : 0;
					endforeach;
					
					if(!isset($orders[$value->order_id]) )$orders[$value->order_id] = $value->order_id;
				}
			}else{
				foreach($data as $key => $value){
					$total = $total + $value->$amt;
					if(!isset($orders[$value->order_id]) )$orders[$value->order_id] = $value->order_id;
				}
			}
			
			$return['total_row_amount'] = $total;
			$return['total_row_count'] = count($data);
			$return['total_order_count'] = count($orders);
			//$this->print_array($return);
			return $return;
		}
		
		function result_columns($detail_view = ''){
			$request = $this->get_all_request();
			$grid_column = $this->get_grid_columns();
			$detail_view 		= $this->get_request('detail_view','no');			
			return $grid_column->result_columns_details_page($detail_view);
		}
		
		function grid_columns($detail_column = false){
			$request = $this->get_all_request();
			$grid_column = $this->get_grid_columns();			
			return $grid_column->grid_columns_details_page($detail_column);
			return $columns;
		}
		
		function details_view_columns($detail_column = false){
			$request = $this->get_all_request();
			$grid_column = $this->get_grid_columns();			
			return $grid_column->details_view_columns($detail_column);
			return $columns;
		}
		
		//Added 20150226
		function delete_ic_commerce(){
			global $wpdb;
			$custom_field_deleted = get_option($this->constants['plugin_key']."_ic_commerce_custom_field_deleted",0);
			if($custom_field_deleted == 0){
				$ic_commerce_row_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}postmeta WHERE meta_key LIKE '_ic_commerce_%'");
			
				if($ic_commerce_row_count > 0){
					$deleted = $wpdb->query("DELETE FROM {$wpdb->prefix}postmeta WHERE meta_key LIKE '_ic_commerce_%'");
				}else{
					update_option($this->constants['plugin_key']."_ic_commerce_custom_field_deleted",1);
				}
			}
		}
		
		function create_hidden_fields($request = array(), $type = "hidden"){
			$output_fields = "";
			//$this->print_array($request);
			foreach($request as $key => $value):
				if(is_array($value)){
					foreach($value as $akey => $avalue):
						if(is_array($avalue)){
							$output_fields .=  "\n<input type=\"{$type}\" name=\"{$key}[{$akey}]\" value=\"".implode(",",$avalue)."\" />";
						}else{
							$output_fields .=  "<input type=\"{$type}\" name=\"{$key}[{$akey}]\" value=\"{$avalue}\" />";
						}
					endforeach;
				}else{
					$output_fields .=  "\n<input type=\"{$type}\" name=\"{$key}\" value=\"{$value}\" />";
				}
			endforeach;
			return $output_fields;
		}
		
		function create_search_form_hidden_fields($request = array(), $type = "hidden"){
			$output_fields = "";
			foreach($request as $key => $value):
				$output_fields .=  "\n<input type=\"{$type}\" name=\"{$key}\" id=\"{$key}\" value=\"{$value}\" />";
			endforeach;
			return $output_fields;
		}
		
		
		
	}//END class 
}//END Clas check
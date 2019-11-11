<?php
if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! class_exists( 'IC_Commerce_Premium_Golden_Cross_Tab' ) ) {
	class IC_Commerce_Premium_Golden_Cross_Tab extends IC_Commerce_Premium_Golden_Fuctions{
		
		public $per_page = 0;	
		
		public $request_data =	array();
		
		public $constants 	=	array();
		
		public $request		=	array();
		
		public $today 		=	'';
		
		public $months 		= 	array();
		
		public $query 		= 	'';
		
		public function __construct($constants) {
			global $options;
			
			$this->constants		= $constants;			
			$options				= $this->constants['plugin_options'];			
			$this->per_page			= $this->constants['per_page_default'];
			$per_page 				= (isset($options['per_apge']) and strlen($options['per_apge']) > 0)? $options['per_apge'] : $this->per_page;
			$this->per_page 		= is_numeric($per_page) ? $per_page : $this->per_page;
			$this->today			= date_i18n("Y-m-d");
		}
		
		function get_paying_state($state_key = 'billing_state',$country_key = false, $deliter = "-"){
				global $wpdb;
				if($country_key)
					$sql = "SELECT CONCAT(billing_country.meta_value,'{$deliter}', billing_by.meta_value) as id, billing_by.meta_value as label, billing_country.meta_value as billing_country ";
				else
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
							$results[$key]->label = $v ." (".$value->billing_country.")";
					endforeach;
				}else{
					
					foreach($results as $key => $value):
							$v = isset($country->countries[$value->label]) ? $country->countries[$value->label]: $value->label;
							$results[$key]->label = $v;
					endforeach;
				}
				return $results; 
		}
		
		function get_state($cc = NULL,$st = NULL){
			global $woocommerce;
			$state_code = $st;
			
			if(!$cc) return $state_code;
			
			$states 			= $this->get_wc_states($cc);//Added 20150225
			
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
		
		function init(){
				global $back_day,$report_title;			
				if(!isset($_REQUEST['page'])){return false;}
				
				if ( !current_user_can( $this->constants['plugin_role'] ) )  {
					wp_die( __( 'You do not have sufficient permissions to access this page.','icwoocommerce_textdomains' ) );
				}
				
				do_action('ic_commerce_current_user_can',$_REQUEST['page'],$this->constants);
				
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
				
				$publish_order			= "no";
				
				$this->is_active();
				
				$optionsid				= "per_row_cross_tab_page";
				$per_page 				= $this->get_number_only($optionsid,$this->per_page);
				
				$shop_order_status		= $this->get_set_status_ids();	
				$post_status 			= $this->constants['post_status'];
				
				$in_shop_order_status 	= implode(",",$shop_order_status);
				$in_post_status			= implode("', '",$post_status);
				
				$in_shop_order_status	= strlen($in_shop_order_status) > 0 ?  $in_shop_order_status 	: '-1';
				$in_post_status			= strlen($in_post_status) > 0 		?  $in_post_status 			: 'no';
				
				$product_status 		= $this->get_setting('product_status',$this->constants['plugin_options'], array());				
				$product_status			= implode("', '",$product_status);				
				$product_status			= strlen($product_status) > 0 ?  $product_status 	: '-1';
				
				$default_tab 			= apply_filters('ic_commerce_crosstab_page_default_tab', 	'product_cross_tab');
				$report_name 			= $this->get_request('report_name',$default_tab,true);
				$start_date 			= apply_filters('ic_commerce_crosstab_page_start_date',		NULL,$report_name);
				$end_date 				= apply_filters('ic_commerce_crosstab_page_end_date',		NULL,$report_name);
				$order_status			= apply_filters('ic_commerce_crosstab_page_selected_order_status', $order_status,$report_name);
				$product_status			= apply_filters('ic_commerce_crosstab_page_selected_product_status', $product_status,$report_name);
				$onload_search			= apply_filters('ic_commerce_crosstab_page_onload_search', "yes", $report_name);
				$per_page				= apply_filters('ic_commerce_crosstab_page_per_page_limit', $per_page, $report_name);				
				$onload_search			= apply_filters('ic_commerce_onload_search', $onload_search, $report_name);
				
				$page					= $this->get_request('page','');
				$admin_page				= $this->get_request('admin_page',$page,true);
				$adjacents				= $this->get_request('adjacents','3',true);
				$p						= $this->get_request('p','1',true);
				$limit					= $this->get_request('limit',$per_page,true);
				$end_date				= $this->get_request('end_date',$end_date,false);
				$start_date				= $this->get_request('start_date',$start_date,false);
				$order_status_id		= $this->get_request('order_status_id',$order_status_id,true);
				$order_status			= $this->get_request('order_status',$order_status,true);
				$publish_order			= $this->get_request('publish_order',$publish_order,true);
				$hide_order_status		= $this->get_request('hide_order_status',$hide_order_status,true);
				$category_id			= $this->get_request('category_id','-1',true);
				$country_code			= $this->get_request('country_code','-1',true);
				$product_id				= $this->get_request('product_id','-1',true);
				$payment_gatway			= $this->get_request('payment_gatway','-1',true);
				$product_status			= $this->get_request('product_status',$product_status,true);
				
				$variations				= $this->get_request('variations','-1',true);
				$variation_column		= $this->get_request('variation_column','1',true);
				
				$action					= $this->get_request('action',$this->constants['plugin_key'].'_wp_ajax_action',true);
				$do_action_type			= $this->get_request('do_action_type','cross_tab_page',true);
				
				$first_date 			= $this->constants['first_order_date'];
				
				$cross_tab_start_date 	= date_i18n('Y-01-01',strtotime('this month'));
				$cross_tab_end_date 	= date_i18n('Y-12-31',strtotime('this month'));
			
				$_cross_tab_start_date 	= $this->get_setting('cross_tab_start_date',$this->constants['plugin_options'], $cross_tab_start_date);
				$_cross_tab_end_date 	= $this->get_setting('cross_tab_end_date',$this->constants['plugin_options'], $cross_tab_end_date);				
				
				$start_date				= empty($end_date) ? (empty($_cross_tab_start_date) ? $cross_tab_start_date : $_cross_tab_start_date) : $start_date;
				$end_date				= empty($end_date) ? (empty($_cross_tab_end_date) 	? $cross_tab_end_date 	: $_cross_tab_end_date) : $end_date;				
				
				$_REQUEST['start_date'] = $start_date;
				
				$_REQUEST['end_date'] 	= $end_date;
				
				
				$attributes_available	= $this->get_attributes_columns('-1');
				
				if($this->is_product_active != 1)  return true;	
				
				$page_titles = array();
				
				$page_titles['product_cross_tab']				= __('Prod./Month','icwoocommerce_textdomains');
				$page_titles['variation_cross_tab']				= __('Variation/Month','icwoocommerce_textdomains');////if($attributes_available)
				
				$page_titles['product_bill_country_crosstab']	= __('Prod./Country','icwoocommerce_textdomains');
				$page_titles['product_bill_state_crosstab']		= __('Prod./State','icwoocommerce_textdomains');
				
				$page_titles['billing_country_cross_tab']		= __('Country/Month','icwoocommerce_textdomains');
				$page_titles['payment_gateway_cross_tab']		= __('Payment G/Month','icwoocommerce_textdomains');
				$page_titles['order_status_cross_tab']			= __('Ord. Status/Month','icwoocommerce_textdomains');
							
				$page_titles['summary_cross_tab']				= __('Summary/Month','icwoocommerce_textdomains');
				
				$page_title 		= isset($page_titles[$report_name]) ? $page_titles[$report_name] : $report_name;				
				$page_title 		= apply_filters($page.'_report_name_'.$report_name, $page_title);
				
				$_REQUEST['page_title'] = $page_title;
				$_REQUEST['page_name'] = 'cross_tab_detail';
				
				$show_search_button = true;
				
				?>                
                <h2 class="hide_for_print"><?php _e($page_title,'icwoocommerce_textdomains');?>  Crosstab</h2>
               
                
                <div class="PluginMenu">
                    <h2 class="nav-tab-wrapper woo-nav-tab-wrapper hide_for_print">
                    <div class="responsive-menu"><a href="#" id="menu-icon"></a></div>
                    <?php            	
                       foreach ( $page_titles as $key => $value ) {
                            echo '<a href="'.admin_url( 'admin.php?page='.$page.'&report_name=' . urlencode( $key ) ).'" class="nav-tab ';
                            if ( $report_name == $key ) echo 'nav-tab-active';
                            echo '">' . esc_html( $value ) . '</a>';
                       }
                    ?></h2>
                </div>
                 <br />
                
                <div id="navigation" class="hide_for_print">
                        <div class="collapsible" id="section1"><?php _e('Custom Search','icwoocommerce_textdomains');?><span></span></div>
                        <div class="container">
                            <div class="content">
                                <div class="search_report_form">
                                    <div class="form_process"></div>
                                    <form action="" name="Report" id="search_order_report" method="post">
                                        <div class="form-table">
                                        	<?php
                                             	$no_date_fields_tabs = apply_filters('ic_commerce_crosstab_page_no_date_fields_tabs',array(),$report_name);
												if(!in_array($report_name,$no_date_fields_tabs)):
											 ?>
                                            <div class="form-group">
                                                <div class="FormRow FirstRow">
                                                    <div class="label-text"><label for="cross_tab_start_date"><?php _e('Start Month:','icwoocommerce_textdomains');?></label></div>
                                                    <div class="input-text"><input type="text" value="<?php echo $start_date;?>" id="cross_tab_start_date" name="start_date" readonly maxlength="7" /></div>
                                                </div>
                                                <div class="FormRow">
                                                    <div class="label-text"><label for="cross_tab_end_date"><?php _e('End Month:','icwoocommerce_textdomains');?></label></div>
                                                    <div class="input-text"><input type="text" value="<?php echo $end_date;?>" id="cross_tab_end_date" name="end_date" readonly maxlength="7" /></div>
                                                </div>
                                            </div>
                                            <?php endif;?>
                                            <?php  do_action("ic_commerce_crosstab_page_below_date_fields", $report_name, $this );?>
                                            
                                            <?php if($report_name == "product_cross_tab" 
											|| $report_name == "billing_country_cross_tab" 
											|| $report_name == "payment_gateway_cross_tab"
											 || $report_name == "summary_cross_tab" 
											 || $report_name != "variation_cross_tab" 
											 || $report_name == "product_bill_country_crosstab" 
											 || $report_name == "product_bill_state_crosstab"):?>
                                            <div class="form-group">
                                                <?php if($report_name == "product_cross_tab" || $report_name == "product_bill_country_crosstab" || $report_name == "product_bill_state_crosstab"):?>
                                                <div class="FormRow FirstRow">
                                                    <div class="label-text"><label for="category_id2"><?php _e('Category:','icwoocommerce_textdomains');?></label></div>
                                                    <div class="input-text">
                                                        <?php 
                                                            $category_data = $this->get_category_data();
                                                            $this->create_dropdown($category_data,"category_id[]","category_id2","Select All","category_id2",$category_id, 'object', true, 5);
                                                        ?>                                                        
                                                    </div>                                                    
                                                </div>
                                                 <?php endif;?>
                                                 
                                                <?php if($report_name == "billing_country_cross_tab"):?>
                                                <div class="FormRow FirstRow">
                                                    <div class="label-text"><label for="country_code"><?php _e('Country:','icwoocommerce_textdomains');?></label></div>
                                                    <div class="input-text">
                                                        <?php 
                                                            $country_data = $this->get_paying_country();															
                                                            $this->create_dropdown($country_data,"country_code[]","country_code2","Select All","country_code2",$country_code, 'object', true, 5);
                                                        ?>                                                        
                                                    </div>                                                    
                                                </div>
                                                 <?php endif;?>
                                                 
                                                <?php if($report_name == "payment_gateway_cross_tab"):?>
                                                <div class="FormRow FirstRow">
                                                    <div class="label-text"><label for="payment_gatway"><?php _e('Payment Gatway:','icwoocommerce_textdomains');?></label></div>
                                                    <div class="input-text">
                                                        <?php 
                                                            $country_data = $this->get_payment_gatway();
															$this->create_dropdown($country_data,"payment_gatway[]","payment_gatway2","Select All","payment_gatway2",$payment_gatway, 'object', true, 5);
                                                        ?>                                                        
                                                    </div>                                                    
                                                </div>
                                                <?php endif;?>
                                                
                                                
                                                <?php if($report_name == "summary_cross_tab"):?>
                                                <div class="FormRow FirstRow">
                                                    <div class="label-text"><label for="reports"><?php _e('Reports:','icwoocommerce_textdomains');?></label></div>
                                                    <div class="input-text">
                                                        <?php 
                                                            $reports_data = array(
																"0"=>__("Order Total",				'icwoocommerce_textdomains'),
																"1"=>__("Order Tax",				'icwoocommerce_textdomains'),
																"2"=>__("Order Discount",			'icwoocommerce_textdomains'),
																"3"=>__("Cart Discount",			'icwoocommerce_textdomains'),
																"4"=>__("Order Shipping",			'icwoocommerce_textdomains'),
																"5"=>__("Order Shipping Tax",		'icwoocommerce_textdomains'),
																"6"=>__("Product Sales",			'icwoocommerce_textdomains')
															);
															$this->create_dropdown($reports_data,"reports[]","reports","Select All","reports",'-1', 'array', true, 5);
                                                        ?>                                                        
                                                    </div>                                                    
                                                </div>
                                                <?php endif;?>
                                                <?php if($report_name != "variation_cross_tab"):?>
                                                <div class="FormRow">
                                                    <div class="label-text"><label for="order_status_id2"><?php _e('Status:','icwoocommerce_textdomains');?></label></div>
                                                    <div class="input-text">
                                                        <?php
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
                                                            //$data = $this->get_category_data2('shop_order_status','no',false);
                                                            //$this->create_dropdown($data,"order_status_id[]","order_status_id2","Select All","order_status_id2",$order_status_id, 'object', true, 5);
                                                        ?>
                                                    </div>
                                                </div>
                                               <?php endif;?>
                                            </div>
                                             <?php endif;?>
                                             <?php if($report_name == "product_cross_tab" || $report_name == "product_bill_country_crosstab" || $report_name == "product_bill_state_crosstab"):?>
                                             <div class="form-group">                                               
                                            	<div class="FormRow FirstRow">
                                                    <div class="label-text"><label for="product_id"><?php _e('Product:','icwoocommerce_textdomains');?></label></div>
                                                    <div class="input-text">
                                                        <?php 
                                                            $product_data = $this->get_product_data('all');
                                                            $this->create_dropdown($product_data,"product_id[]","product_id2","Select All","product_id2",$product_id, 'object', true, 5);
                                                        ?>
                                                    </div>                                                    
                                                </div>
                                                <?php if($report_name == "product_bill_country_crosstab" || $report_name == "product_bill_state_crosstab"):?>
                                                    <div class="FormRow ">
                                                        <div class="label-text"><label for="country_code"><?php _e('Country:','icwoocommerce_textdomains');?></label></div>
                                                        <div class="input-text">
                                                            <?php 
                                                                $country_data = $this->get_paying_state('billing_country');															
                                                                $this->create_dropdown($country_data,"country_code[]","country_code2","Select All","country_code2",$country_code, 'object', true, 5);
                                                            ?>                                                        
                                                        </div>                                                    
                                                    </div>
                                                     <?php endif;?>
                                                    
                                             </div>
                                             
                                             <div class="form-group">   
                                             	 <?php if($report_name == "product_bill_state_crosstab"):?>
                                                    <div class="FormRow ">
                                                        <div class="label-text"><label for="state_code"><?php _e('State:','icwoocommerce_textdomains');?></label></div>
                                                        <div class="input-text">
                                                            <?php 
																$state_code = '-1';
                                                                $state_codes = $this->get_paying_state('billing_state','billing_country');
                                                                $this->create_dropdown($state_codes,"state_code[]","state_code2","Select All","state_code2",$state_code, 'object', true, 5);
                                                            ?>                                                        
                                                        </div>                                                    
                                                    </div>
                                                     <?php endif;?>
                                             </div>
                                             <?php endif;?>
                                             <?php if($report_name == "variation_cross_tab" and $attributes_available):?>
                                             	<div class="form-group">
                                                    <div class="FormRow FirstRow">
                                                        <div class="label-text"><label for="category_id2"><?php _e('Category:','icwoocommerce_textdomains');?></label></div>
                                                        <div class="input-text">
                                                            <?php 
                                                                $category_data = $this->get_variation_category_data('product_cat');
                                                                $this->create_dropdown($category_data,"category_id[]","category_id2","Select All","category_id2",$category_id, 'object', true, 5);
                                                            ?>                                                        
                                                        </div>                                                    
                                                    </div>
                                                    <div class="FormRow">
                                                        <div class="label-text"><label for="order_status_id2"><?php _e('Status:','icwoocommerce_textdomains');?></label></div>
                                                        <div class="input-text">
                                                            <?php
															
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
                                                                //$data = $this->get_variation_category_data('shop_order_status');
                                                                //$this->create_dropdown($data,"order_status_id[]","order_status_id2","Select All","order_status_id2",$order_status_id, 'object', true, 5);
                                                            ?>
                                                        </div>
                                                    </div>
                                                </div>
                                              	<div class="form-group">
                                                	<div class="FormRow FirstRow">
                                                        <div class="label-text"><label for="product_id"><?php _e('Product:','icwoocommerce_textdomains');?></label></div>
                                                        <div class="input-text">
                                                            <?php 
                                                                $product_data = $this->get_variation_data();
                                                                $this->create_dropdown($product_data,"product_id[]","product_id2","Select All","product_id2",$product_id, 'object', true, 5);
                                                            ?>
                                                        </div>                                                    
                                                    </div>
                                                    <div class="FormRow">
                                                        <div class="label-text"><label for="variations"><?php _e('Variations:','icwoocommerce_textdomains');?></label></div>
                                                        <div class="input-text">
                                                            <?php	
																	$enable_variation = true;
                                                                    $this->create_dropdown($attributes_available,"variations[]","variations2","Select All","variations2",$variations, 'array', true, 5);                                                                
                                                            ?>
                                                        </div>
                                                    </div>                                                    
                                                </div>
                                              <?php elseif($report_name == "variation_cross_tab"):  $enable_variation = false;$show_search_button = true;?>
                                              	<p><strong><?php _e('There is no order purchased in variable product.','icwoocommerce_textdomains');?></strong></p>                                               
											  <?php endif;?>
                                              
                                              <?php  $display_limit_field_tabs = apply_filters('ic_commerce_crosstab_page_display_limit_field_tabs',array(),$report_name);//array('product_cross_tab','variation_cross_tab','product_bill_country_crosstab','product_bill_state_crosstab','billing_country_cross_tab','payment_gateway_cross_tab','order_status_cross_tab');
											  if(in_array($report_name, $display_limit_field_tabs)){
											  ?>
                                              <div class="form-group">
                                                	<div class="FormRow FirstRow">
                                                        <div class="label-text"><label for="limit"><?php _e('limit:','icwoocommerce_textdomains');?></label></div>
                                                        <div class="input-text">
                                                            <div class="input-text"><input type="text" value="<?php echo $limit;?>" id="limit" name="limit" class="numberonly" maxlength="10" data-max="10000" data-min="1" /></div>															
                                                        </div>                                                    
                                                    </div>                                                                                                      
                                              </div>
                                              
                                              <?php }{
													echo "<input type=\"hidden\" id=\"limit\" name=\"limit\" value=\"9999999999\">"; 
												}
												do_action("ic_commerce_crosstab_page_search_form_bottom",$report_name);?>
                                              
                                              <?php if($show_search_button):?>
                                                <div class="form-group">
                                                    <div class="FormRow " style="width:100%">
                                                    		<?php
																$hidden_fields = array();
																$hidden_fields['hide_order_status'] 	=  $hide_order_status;
																$hidden_fields['publish_order'] 		=  $publish_order;
																$hidden_fields['report_name'] 	 		=  $report_name;
																$hidden_fields['page'] 		 			=  $page;
																$hidden_fields['admin_page'] 		 	=  $admin_page;
																$hidden_fields['p'] 	 				=  $p;
																$hidden_fields['adjacents'] 	 		=  $adjacents;
																$hidden_fields['page_title'] 			=  $page_title;																
																$hidden_fields['product_status'] 		=  $product_status;
																$hidden_fields['page_title'] 			=  $page_title;
																$hidden_fields['billing_or_shipping'] 	=  'billing';
																$hidden_fields['count_generated'] 		=  0;
																$hidden_fields['total_row_count'] 		=  0;
																$hidden_fields['do_action_type'] 		=  $this->get_request('do_action_type','report_page',true);
																$hidden_fields['page_name'] 			=  $this->get_request('page_name','all_detail',true);
																$hidden_fields['date_format'] 			=  $this->get_request('date_format',get_option('date_format'),true);
																$hidden_fields['onload_search'] 		=  $this->get_request('onload_search',$onload_search,true);
																$hidden_fields['breakup'] 				=  $this->get_request('breakup',0,true);
																$hidden_fields['order_date_field_key'] 	=  $this->get_request('order_date_field_key','post_date',true);
																$hidden_fields['action'] 	 			=  $this->constants['plugin_key'].'_wp_ajax_action';
																$hidden_fields = apply_filters('ic_commerce_crosstab_page_search_form_hidden_fields', $hidden_fields, $page, $report_name);
																echo $this->create_search_form_hidden_fields($hidden_fields);
															?>  
                                                            <span class="submit_buttons">
                                                                <input name="ResetForm" id="ResetForm" class="onformprocess" value="<?php _e('Reset','icwoocommerce_textdomains');?>" type="reset">
                                                                <input name="SearchOrder" id="SearchOrder" class="onformprocess searchbtn" value="<?php _e('Search','icwoocommerce_textdomains');?>" type="submit">  &nbsp; &nbsp; &nbsp; <span class="ajax_progress"></span>
                                                            </span>
                                                    </div>
                                                </div>
                                             <?php endif;?>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <div class="clearfix"></div>
                    </div>                    
                
                <div class="search_report_content hide_for_print">
                    <?php if($onload_search == "no") {echo apply_filters('ic_commerce_onload_search_text', '');}?>
                </div>
                
                <div id="search_for_print_block" class="search_for_print_block"></div>
				<?php
                    $admin_page 			= $this->get_request('admin_page');
                    //$admin_page_url 		= get_option('siteurl').'/wp-admin/admin.php';//Commented not work SSL admin site 20150212
					$admin_page_url 		= $this->constants['admin_page_url'];//Added SSL fix 20150212
                    $mngpg 					= $admin_page_url.'?page='.$admin_page ;
                    
                    $logo_image 			= $this->get_setting('logo_image',$this->constants['plugin_options'], '');
                    $report_title 			= $this->get_setting('report_title',$this->constants['plugin_options'], '');
                    $company_name 			= $this->get_setting('company_name',$this->constants['plugin_options'], '');
                    $page_title				= $this->get_request('page_title',NULL,true);
					
					$set_report_title		= $report_title;
                    if($page_title) $page_title = " (".$page_title.")";							
                    $report_title = $report_title.$page_title;
                ?>                
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
                    <h4><?php _e('Export to Print','icwoocommerce_textdomains');?></h4>
                    <div class="popup_content">
                    <form id="<?php echo $admin_page ;?>_print_popup_form" class="<?php echo $admin_page ;?>_pdf_popup_form" action="<?php echo $mngpg;?>" method="post">
                        <div class="popup_print_hidden_fields popup_hidden_fields2"></div>
                         <table class="form-table">
                            <tr>
                                <th><label for="company_name_print"><?php _e('Company Name:','icwoocommerce_textdomains');?></label></th>
                                <td><input id="company_name_print" name="company_name" value="<?php echo $company_name;?>" type="text" class="textbox"></td>
                            </tr>
                            <tr>
                                <th><label for="report_title_print"><?php _e('Report Title:','icwoocommerce_textdomains');?></label></th>
                                <td><input id="report_title_print" name="report_title" value="<?php echo $report_title;?>" data-report_title="<?php echo $set_report_title;?>" type="text" class="textbox"></td>
                            </tr>
                            <?php if($logo_image):?>
                            <tr>
                                <th><label for="display_logo_print"><?php _e('Print Logo:','icwoocommerce_textdomains');?></label></th>
                                <td class="inputfield"><input id="display_logo_print" name="display_logo" value="1" type="checkbox"<?php if($logo_image) echo ' checked="checked"';?>></td>
                            </tr>
                            <?php endif;?>
                            <?php do_action('ic_commerce_export_print_popup_extra_option',$page);?>
                             <tr>
                                <th><label for="display_date_print"><?php _e('Print Date:','icwoocommerce_textdomains');?></label></th>
                                <td class="inputfield"><input id="display_date_print" name="display_date" value="1" type="checkbox" checked="checked"></td>
                             </tr>
                            
                            <tr>
                                <td colspan="2"><input type="button" name="<?php echo $admin_page ;?>_export_print" class="onformprocess button_popup_close search_for_print" value="<?php _e('Print','icwoocommerce_textdomains');?>" data-form="popup"  data-do_action_type="cross_tab_for_print" /></td>
                            </tr>                                
                        </table>
                        <input type="hidden" name="display_center" value="1" />
                    </form>
                    <div class="clear"></div>
                    </div>
                </div>
                <div class="popup_mask"></div>
                <?php do_action("ic_commerce_crosstab_page_footer_area",$page);?>
				<?php
		}//init			
		
		function ic_commerce_ajax_request($type = 'limit_row'){
			$this->get_grid($type);
		}
		
		function get_items($type = 'limit_row', $items_only = true, $id = '-1'){
			$report_name = $this->get_request('report_name');
			switch($report_name){
				case "product_cross_tab":
					$items = $this->get_product_items($type,$items_only,$id);
					break;
				case "billing_country_cross_tab":
					$items = $this->get_country_items($type,$items_only,$id);
					break;
				case "order_status_cross_tab":
					$items = $this->get_status_items($type,$items_only,$id);
					break;
				case "payment_gateway_cross_tab":
					$items = $this->get_payment_gateway_items($type,$items_only,$id);
					break;
				case "summary_cross_tab":
					$items = $this->get_sales_items($type,$items_only,$id);
					break;
					
				case "variation_cross_tab":
					$items = $this->get_variation_items($type,$items_only,$id);
					break;
				case "product_bill_country_crosstab":
					$items = $this->get_country_product_items($type,$items_only,$id,'_billing_country');					
					break;
				case "product_bill_state_crosstab":
					$items = $this->get_country_product_items($type,$items_only,$id,'_billing_state');					
					break;
							
				default:
					$items = array();
					break;
			}
			
			$items 		= apply_filters('ic_commerce_crosstab_page_items',$items, $items_only, $id, $report_name, $this);
			return $items;
		}
		
		function get_end_limit($report_name = "product_cross_tab"){	
			global $wpdb;		
			$type 		= 'total_row';
			$items_only = true;
			$id			= '-1';			
			switch($report_name){
				case "product_cross_tab":
				case "product_bill_country_crosstab":
				case "product_bill_state_crosstab":
					$sql 		= "SELECT COUNT(*) FROM {$wpdb->posts} AS posts WHERE posts.post_type = 'product'";
					$end_limit 	= $wpdb->get_var($sql);
					break;
				case "billing_country_cross_tab":
				case "order_status_cross_tab":
				case "payment_gateway_cross_tab":
				case "variation_cross_tab":				
					$end_limit = 0;
					break;
				case "summary_cross_tab":
				default:
					$end_limit = 0;
					break;
			}
			
			$end_limit = apply_filters('ic_commerce_crosstab_page_end_limit',$end_limit, $report_name);
			return $end_limit;
		}
			
		function get_amount_quantity($items,$month_key){
			$report_name 		= $this->get_request('report_name');
			switch($report_name){
				case "product_cross_tab":					
				case "billing_country_cross_tab":
				case "order_status_cross_tab":
				case "payment_gateway_cross_tab":
				case "summary_cross_tab":
				case "variation_cross_tab":
				case "product_bill_country_crosstab":
				case "product_bill_state_crosstab":
					$items = $this->get_amount_quantity2($items,$month_key);
					break;
				default:
					$items = array();
					break;
			}			
			return $items;
		}
		
		function get_amount_quantity2($items,$month_key){
			$v = false;
			foreach($this->items_data as $key => $value):
					if($value->month_key == $month_key){
						$v = $value;
						unset($this->items_data[$key]);
						return $v;
					}
			endforeach;
			return $v;
		}
		
		function get_columns(){
			$report_name 		= $this->get_request('report_name');
			switch($report_name){
				case "product_cross_tab":
					$columns = array("product_sku"=>__("Product SKU",'icwoocommerce_textdomains'),"product_name"=>__("Product Name",'icwoocommerce_textdomains'));
					break;
				case "billing_country_cross_tab":
					$columns = array("country_name"=>__("Country Name",'icwoocommerce_textdomains'));
					break;
				case "order_status_cross_tab":
					$columns = array("status_name"=>__("Status Name",'icwoocommerce_textdomains'));
					break;
				case "payment_gateway_cross_tab":
					$columns = array("payment_method"=>__("Payment Gatway",'icwoocommerce_textdomains'));
					break;
				case "summary_cross_tab":
					$columns = array("item_name"=>__("Reports",'icwoocommerce_textdomains'));
					break;
				case "variation_cross_tab":
					$columns = array("product_sku"=>__("Item SKU",'icwoocommerce_textdomains'),"variation_id"=>__("varition ID",'icwoocommerce_textdomains'),"product_name"=>__("Product Name",'icwoocommerce_textdomains'));					
					$variation_columns = $this->get_attributes_columns('selected');
					$columns 	= array_merge((array)$columns, (array)$variation_columns);					

					break;
				case "product_bill_country_crosstab":
				case "product_bill_state_crosstab":
					$columns = array("product_sku"=>__("Product SKU",'icwoocommerce_textdomains'),"product_name"=>__("Product Name",'icwoocommerce_textdomains'));
					break;
				default:
					$columns = array();
					break;
			}
			
			return $columns;
		}
		
		function get_crosstab_coulums($amount_column = true){
			$report_name 		= $this->get_request('report_name');
			switch($report_name){
				case "product_cross_tab":					
				case "billing_country_cross_tab":
				case "order_status_cross_tab":
				case "payment_gateway_cross_tab":
				case "summary_cross_tab":
				case "variation_cross_tab":
					$items = $this->get_months_list($amount_column);
					break;
				case "product_bill_country_crosstab":
					$items = $this->get_country_list($amount_column);
					break;
				case "product_bill_state_crosstab":
					$items = $this->get_state_list($amount_column);
					break;
				default;				
					$items = array();
					break;
				}			
				return $items;
		}
		
		function get_attributes_columns($_variations = '-1'){
				global $wpdb;
			
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
						//$var = str_replace("attribute_pa_","",$var);
						$var = str_replace("attribute_","",$var);
						$var2 = str_replace("-"," ",$var);
						$variations[$var] = ucfirst($var2);
					endforeach;
					
					$nv = array();
					foreach($variations as $key => $value):
						$value = str_replace('Pa_','',$value);
						$nv[$key] = ucfirst($value);
					endforeach;
					$variations = $nv;
				}else{
					$_variations = explode(",",$_variations);
					
					//this->print_array($_variations);
					foreach($items as $key => $value):
						$var = $value->variation_key;
						//$var = $this->attribute_label($value->variation_key, $value->variation_name);
						//$var = str_replace("attribute_pa_","",$var);
						$var = str_replace("attribute_","",$var);
						$var2 = str_replace("-"," ",$var);
						

						if(in_array($var, $_variations))
							$variations[$var] = ucfirst($var2);
					endforeach;
					
					$nv = array();
					foreach($variations as $key => $value):
						$value = str_replace('Pa_','',$value);
						$nv[$key] = ucfirst($value);
					endforeach;
					$variations = $nv;
					
						
					
				}				
				asort($variations);				
				return $variations;
		}
		
		function get_attributes($_variations = '-1'){
				global $wpdb;
			
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
		
		function attribute_label( $name, $key =  NULL) {
			global $wpdb;
			$label = false;
			
			$name = str_replace( 'attribute_', '', $name );
			
			if ( taxonomy_is_product_attribute( $name) ) {
				$name = woocommerce_sanitize_taxonomy_name( str_replace( 'pa_', '', $name ) );
	
				$label = $wpdb->get_var( $wpdb->prepare( "SELECT attribute_label FROM {$wpdb->prefix}woocommerce_attribute_taxonomies WHERE attribute_name = %s;", $name ) );
	
				if (!$label)
					$label = ucfirst( $name );
			} else {
				
				if($key)
					$label = $wpdb->get_var( "SELECT meta_key FROM " . $wpdb->prefix . "woocommerce_order_itemmeta WHERE meta_value = '{$key}' limit 1" );
				
				if (!$label)
					$label = ucfirst( $name );
				
				
				
			}
	
			return apply_filters( 'woocommerce_attribute_label', $label, $name );
		}
		
		function get_variation_column_separated($order_item_id = 0){
			global $wpdb;
				$sql = "
				SELECT
				postmeta_variation.meta_value AS variation 
				,postmeta_variation.meta_key AS attribute
				FROM {$wpdb->prefix}woocommerce_order_items as woocommerce_order_items
				LEFT JOIN  {$wpdb->prefix}woocommerce_order_itemmeta as woocommerce_order_itemmeta ON woocommerce_order_itemmeta.order_item_id = woocommerce_order_items.order_item_id
				LEFT JOIN  {$wpdb->prefix}postmeta as postmeta_variation ON postmeta_variation.post_id = woocommerce_order_itemmeta.meta_value
				
				WHERE woocommerce_order_items.order_item_id={$order_item_id}
				
				AND woocommerce_order_items.order_item_type = 'line_item'
				AND woocommerce_order_itemmeta.meta_key = '_variation_id'
				AND postmeta_variation.meta_key like 'attribute_%'";
				
				$items = $wpdb->get_results($sql);
				
				//$this->print_array($items);
				
				$variations = array();
				foreach($items as $key => $value):
					
					$var = $value->attribute;
					$var = str_replace("attribute_pa_","",$var);
					$var = str_replace("attribute_","",$var);
					
					
					$var2 = $value->variation;
					if(strlen($var2)>0){
						$var2 = str_replace("-"," ",$var2);
					}else{
						$var2 = $var;
					}
					
					if(!isset($variations[$var]))
						$variations[$var] = ucfirst($var2);
				endforeach;	
				
				return $variations;
		}
		
		function get_detail_page_url($report_name = '', $request = array()){			
			$admin_url 	= admin_url("admin.php?page=".$this->constants['plugin_key']."_details_page");			
			switch ($report_name ) {
				case "product_cross_tab":
					$form_input_fields = array('order_status');
					break;
				case "variation_cross_tab":
					$form_input_fields = array('order_status');
					break;
				case "product_bill_country_crosstab":
					$form_input_fields = array('order_status','country_code');
					break;
				case "product_bill_state_crosstab":
					$form_input_fields = array('order_status','country_code','state_code');
					break;
				case "billing_country_cross_tab":
					$form_input_fields = array('order_status');
					break;
				case "payment_gateway_cross_tab":
				case "summary_cross_tab":
					$form_input_fields = array('order_status');
					break;
				case "order_status_cross_tab":
					$form_input_fields = array();
					break;
				default:
					$form_input_fields = array();					
					break;
			}
			
			$form_input_fields = apply_filters('ic_commerce_crosstab_page_form_inputs', $form_input_fields, $report_name, $request);
			
			$paras = array();
			
			if($report_name == "product_bill_state_crosstab"){
				if(isset($request['state_code']) and in_array('state_code',$form_input_fields)){					
					foreach($form_input_fields as $key => $field_name){
						if($field_name == 'state_code'){
							unset($form_input_fields[$key]);
						}
					}
					$paras[] = "country_state_code=".$request['state_code'];
				}
			}
			foreach($form_input_fields as $key => $field_name){
				if(!empty($request[$field_name]) and $request[$field_name] != '-1'){
					 $paras[] = $field_name ."=".$request[$field_name];
				}
			}
			
			if(count($paras)>0){
				$admin_url .= "&".implode("&",$paras);
			}
			
			return $admin_url;
		}
		
		var $items_data = array();		
		function get_grid($type = 'total_row'){
				$order_items 			= $this->get_items($type);
				if(count($order_items) > 0){
					$request 			= $this->get_all_request();extract($request);					
					$columns 			= $this->get_columns();
					$months 			= $this->get_crosstab_coulums();
					$total_value		= array();		
					$zero 				= $this->price(0);
					
					if($this->constants['plugin_key'] == "icwoocommercecrosstab")					
						$clickeble 			= false;
					else
						$clickeble 			= true;
					
									
					if($type=="all_row" || $clickeble == false) $clickeble = false;					
					if($clickeble){
						$detail_page_url	= $this->get_detail_page_url($report_name, $request);
						$start_date_str 	= strtotime($start_date);
						$end_date_str		= strtotime($end_date);
						$start_date 		= date('Y-m-01',$start_date_str);
						$end_date			= date('Y-m-t',$end_date_str);
						$item_href			= $detail_page_url."&start_date=".$start_date."&end_date=".$end_date;						
						
					}
					
					if($report_name == "variation_cross_tab"){
						//$attributes					= $this->get_attributes_columns('selected');
						$attributes						= array();
						$_REQUEST['show_variation'] 	= 'variable';
						$_REQUEST['variation_column'] 	= '1';
						$_REQUEST['group_by'] 			= 'variation_id';
						$order_items = $this->get_grid_items_variation($columns,$order_items);
						//$this->print_array($columns);
						//$this->print_array($order_items);
						
						$variation 			= array();
						
						$attrs		= array();
						foreach($attributes as $key => $value):
							$attrs[] = $key;
						endforeach;
					}
					
					//$summary 		= $this->get_items('total_row');
					$summary 		= array();
					$summary['total_row_count'] = isset($request['total_row_count']) ? $request['total_row_count'] : 0;
					$current_count	= 0;
						
			?>
                <div class="Overflow">
                	<?php $this->print_header($type);?>
					<?php if($type != 'all_row'):?>
                    <div class="top_buttons"><?php $this->export_to_csv_button('top',$summary);?><div class="clearfix"></div></div>
                    <?php else: $this->back_print_botton('top');?>
                    <?php endif;?>
                    <style type="text/css">
                    	td.td_align_left{ text-align:left;}
                    </style>
                    <table style="width:99.8%" class="widefat widefat-table">
                        <thead>
                            <tr>
                                <?php 
									$header = "";
									foreach($columns as $key => $value):
										$header .= "<th class=\"{$key}\">{$value}</th>\n";
									endforeach;
									
									foreach($months as $key => $value):										
										$header .= "<th class=\"amount\" style=\"text-align:right;\">{$value}</th>\n";
									endforeach;
									echo $header;
								?>
                                <th class="ProductTotal" style="width:90px; text-align:right"><?php _e("Total",'icwoocommerce_textdomains'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ( $order_items as $key => $order_item ) {
								$current_count = $current_count + 1;
								$alternate = "alternate ";
								if($key%2 == 1){$alternate = "alternate ";}else{$alternate = "";};
							?>
                            <tr class="product_row <?php echo $alternate."row_".$key;?>">
                            
                                 <?php 
									$body = "";
									
									//if($report_name == "variation_cross_tab") $variation = $this->get_variation_column_separated($order_item->order_item_id);
									
									if($clickeble){
										foreach($columns as $key => $value):
											switch ($key) {
												case "product_name":
													if($report_name == "variation_cross_tab"){
														$value = $order_item->$key;
														$href = $item_href."&product_id=".$order_item->product_id."&variation_id=".$order_item->id."&detail_view=yes";
														//$href = $item_href."&product_id=".$order_item->product_id."&detail_view=yes";
														$value = "<a href=\"{$href}\" target=\"_blank\">{$value}</a>";
														break;
													}else{
														$value = $order_item->$key;
														$href = $item_href."&product_id=".$order_item->id."&detail_view=yes";
														$value = "<a href=\"{$href}\" target=\"_blank\">{$value}</a>";
														break;
													}
													
												case "country_name":
													$value = $order_item->$key;
													$href = $item_href."&country_code=".$order_item->id."&detail_view=no";
													$value = "<a href=\"{$href}\" target=\"_blank\">{$value}</a>";
													break;
												case "payment_method":
													$value = $order_item->$key;
													$href = $item_href."&payment_method=".$order_item->payment_method."&detail_view=no";
													$value = "<a href=\"{$href}\" target=\"_blank\">{$value}</a>";
													break;
												case "status_name":
													$value = $order_item->$key;
													$href = $item_href."&order_status_id=".$order_item->id."&detail_view=no";
													$value = "<a href=\"{$href}\" target=\"_blank\">{$value}</a>";
													break;
												case "item_name":
													$value = $order_item->$key;
													if($order_item->id == "_order_total"){
														$href = $item_href."&detail_view=no";
														$value = "<a href=\"{$href}\" target=\"_blank\">{$value}</a>";
													}elseif($order_item->id == "_by_product"){
														$href = $item_href."&detail_view=yes";
														$value = "<a href=\"{$href}\" target=\"_blank\">{$value}</a>";
													}elseif($order_item->id == "_order_tax" 
														|| $order_item->id == "_order_discount"
														|| $order_item->id == "_cart_discount"
														|| $order_item->id == "_order_shipping"
														|| $order_item->id == "_order_shipping_tax"
														){
														$href = $item_href."&detail_view=no&order_meta_key=".$order_item->id;
														$value = "<a href=\"{$href}\" target=\"_blank\">{$value}</a>";
													}
													break;
												case "id":
												//case "final_sku":
													$value = $order_item->$key;													
													break;
												case "final_sku":
												case "product_sku":
													$value = $this->get_sku($order_item->order_item_id, $order_item->product_id);
													break;
												default:
													if($report_name == "variation_cross_tab"){
														if(in_array($key, $attrs)){															
															$value = isset($variation[$key]) ? $variation[$key] : (isset($order_item->$key) ? $order_item->$key : "");
														}else{
															$value = isset($order_item->$key) ? $order_item->$key : "";
														}
													}else{
														$value = isset($order_item->$key) ? $order_item->$key : "";
													}
													//$value .= $key;											
													break;
											}
										$body .= "<td class=\"td_align_left {$key}\">{$value}</td>\n";
										endforeach;
									}else{
										foreach($columns as $key => $value):
											switch ($key) {
												case "product_name":												
												case "country_name":													
												case "payment_method":													
												case "status_name":													
												case "item_name":												
												case "id":
												//case "final_sku":
													$value = $order_item->$key;													
													break;
												case "final_sku":
												case "product_sku":
													$value = $this->get_sku($order_item->order_item_id, $order_item->product_id);
													break;												
												default:
													if($report_name == "variation_cross_tab"){
														if(in_array($key, $attrs)){															
															$value = isset($variation[$key]) ? $variation[$key] : (isset($order_item->$key) ? $order_item->$key : "");
														}else{
															$value = isset($order_item->$key) ? $order_item->$key : "";
														}
													}else{
														$value = isset($order_item->$key) ? $order_item->$key : "";
													}
													//$value .= $key;
													break;
											}
										$body .= "<td class=\"td_align_left {$key}\">{$value}</td>\n";
										endforeach;
									}
									echo $body;
								?>
                                 <?php
								 	
									//$this->print_array($order_item);
									
								 
								  	$product_total		= 0;
									$quantity_total		= 0;
									$this_item_data		= $this->get_items($type,false,$order_item->id);
									$item_id 			= $order_item->id;
									$this->items_data	= $this_item_data;
									
									foreach($months as $key => $value):
										if(count($this->items_data)>0)
											$amount_quantity = $this->get_amount_quantity($this_item_data, $key);
										else
											$amount_quantity = false;
										
										
											
											if ($amount_quantity){
												$_product_total = $amount_quantity->total;
												
												$_quantity_total = $amount_quantity->quantity;
												
												if($_product_total == 0 
											   and ($item_id == "_order_total" 
												|| $item_id == "_order_tax"
												 || $item_id == "_order_discount"
												  || $item_id == "_cart_discount"
												   || $item_id == "_order_shipping"
												    || $item_id == "_order_shipping_tax")
												   ){
													$v =  $zero;
												}else 
												if($_product_total > 0 || $_quantity_total > 0){
													
													//if($_product_total > 0 || (($item_id == "_by_product" || $report_name == "product_cross_tab" || $report_name == "payment_gateway_cross_tab"  || $report_name == "order_status_cross_tab"  || $report_name == "billing_country_cross_tab") && ($_product_total > 0 || $_quantity_total > 0))){
													//if($_product_total > 0 || (($item_id == "_by_product" || $report_name == "product_cross_tab" || $report_name == "payment_gateway_cross_tab"  || $report_name == "order_status_cross_tab"  || $report_name == "billing_country_cross_tab") && ($_product_total > 0 || $_quantity_total > 0))){
													
													$product_total = $product_total + $_product_total;
													
													$quantity_total = $quantity_total + $_quantity_total;
												
													if(isset($total_value[$key]['product_total'])){
														
														$total_value[$key]['product_total']		= $total_value[$key]['product_total'] + $_product_total;
														
														$total_value[$key]['quantity_total']	= $total_value[$key]['quantity_total']+ $_quantity_total;
														
													}else{
														
														$total_value[$key]['product_total']		= $_product_total;
														
														$total_value[$key]['quantity_total']	= $_quantity_total;
													}
													if($clickeble){
														if($_product_total > 0){														
															$price = $this->price($_product_total);
															$price = $this->get_clickable_price($price, $key, $report_name, $order_item, $detail_page_url,$start_date,$end_date);
															$v =  $price ." # ".  $_quantity_total;
														}else{
															$price = $this->get_clickable_price($zero, $key, $report_name, $order_item, $detail_page_url,$start_date,$end_date);
															$v =  $price." # ".  $_quantity_total;
														}
													}else{
														if($_product_total > 0){														
															$price = $this->price($_product_total);
															$v =  $price ." # ".  $_quantity_total;
														}else{															
															$v =  $zero." # ".  $_quantity_total;
														}
													}
													
												}else{
													$v =  $zero;
												}
											}else{
                                                $v =  $zero;
											}											
										
											$output = "<td>";
											//$output .= $v.$this->print_array($amount_quantity,false);
											$output .= $v;
											$output .= "</td>\n";										
											echo $output;
										
									endforeach;
									$_product_total = $this->price($product_total);
									if($clickeble){
										if($product_total > 0)
											$_product_total = $this->get_clickable_price2($_product_total, 0, $report_name, $order_item, $detail_page_url,$start_date,$end_date);
									}
									
									echo "<td>{$_product_total} # {$quantity_total}</td>\n";
									
									$product_total = 0;
									$quantity_total = 0;
									
                                ?>
                            </tr>
                            <?php }?>                            
                        </tbody>
                        <?php if($report_name != "summary_cross_tab"):?>
                        <tfoot>
                        	<tr class="alternate">
                            	<th colspan="<?php echo count($columns);?>"><strong><?php _e("Total",'icwoocommerce_textdomains'); ?></strong></th>
                                <?php
									$product_total = 0;
									$quantity_total = 0;
									foreach($months as $key => $value):
										$output = "<td>";
											if(isset($total_value[$key]['product_total'])){	
												
												$price = $this->price($total_value[$key]['product_total']);
												
												if($clickeble){
													
													
													if($report_name == "product_cross_tab"){
														$href = $detail_page_url."&month_key=".$key."&detail_view=yes";
													}elseif($report_name == "variation_cross_tab"){
														$href = $detail_page_url."&month_key=".$key."&detail_view=yes&variation_only=1";
													}elseif($report_name == "product_bill_country_crosstab"){
														$href = $item_href."&country_code=".$key."&detail_view=yes";
													}elseif($report_name == "product_bill_state_crosstab"){
														$href = $item_href."&country_state_code=".$key."&detail_view=yes";
													}else{
														$href = $detail_page_url."&month_key=".$key."&detail_view=no";
													}
													$price = "<a href=\"{$href}\" target=\"_blank\">{$price}</a>";
												}
											
												$output .= $price;
												$output .= " # ". $total_value[$key]['quantity_total'];
												
												$product_total = $product_total + $total_value[$key]['product_total'];
												$quantity_total = $quantity_total + $total_value[$key]['quantity_total'];
											}else{
												 $output .=  $zero;
											}
										$output .= "</td>\n";										
										echo $output;											
									endforeach;
									
									$__product_total	=	$product_total > 0 ? $this->price($product_total) : $zero;
									$product_total = $__product_total;
									
									if($clickeble){
										
										if($report_name == "product_cross_tab"){
											$href = $item_href."&detail_view=yes";
										}elseif($report_name == "variation_cross_tab"){
											$href = $item_href."&detail_view=yes&variation_only=1";
										}else{
											$href = $item_href;
										}
										$product_total = "<a href=\"{$href}\" target=\"_blank\">{$product_total}</a>";
									}
									echo "<td>{$product_total} # {$quantity_total}</td>\n";
								?>
                            </tr>
                        </tfoot>
                         <?php endif;?>
                    </table>
                    <?php 
					
					
					if($type != 'all_row') $this->total_count($current_count, $summary); else $this->back_print_botton('bottom');//$this->print_array($total_value);?>
                </div>                
            <?php 
				}else{
					echo __("Order not found",'icwoocommerce_textdomains');
				}
				
	}
	
	function get_clickable_price($price,$month_key,$request_key,$order_item,$item_href,$start_date,$end_date){
				$value = $price;
				switch ($request_key) {
					case "product_cross_tab":						
						$href = $item_href."&product_id=".$order_item->id."&month_key=".$month_key."&detail_view=yes";
						$value = "<a href=\"{$href}\" target=\"_blank\">{$value}</a>";
						break;
					case "variation_cross_tab":
						$href = $item_href."&product_id=".$order_item->product_id."&variation_id=".$order_item->id."&month_key=".$month_key."&detail_view=yes";
						$value = "<a href=\"{$href}\" target=\"_blank\">{$value}</a>";
						break;
					case "billing_country_cross_tab":						
						$href = $item_href."&country_code=".$order_item->id."&month_key=".$month_key."&detail_view=no";
						$value = "<a href=\"{$href}\" target=\"_blank\">{$value}</a>";
						break;
					case "payment_gateway_cross_tab":
						$href = $item_href."&payment_method=".$order_item->payment_method."&month_key=".$month_key."&detail_view=no";
						$value = "<a href=\"{$href}\" target=\"_blank\">{$value}</a>";
						break;
					case "order_status_cross_tab":
						$href = $item_href."&order_status_id=".$order_item->id."&month_key=".$month_key."&detail_view=no";
						$value = "<a href=\"{$href}\" target=\"_blank\">{$value}</a>";
						break;
					
					case "product_bill_country_crosstab":
						$href = $item_href."&start_date=".$start_date."&end_date=".$end_date."&product_id=".$order_item->id."&country_code=".$month_key."&detail_view=yes";
						$value = "<a href=\"{$href}\" target=\"_blank\">{$value}</a>";
						break;
					
					case "product_bill_state_crosstab":
						$href = $item_href."&start_date=".$start_date."&end_date=".$end_date."&product_id=".$order_item->id."&country_state_code=".$month_key."&detail_view=yes";
						$value = "<a href=\"{$href}\" target=\"_blank\">{$value}</a>";
						break;
					
					case "summary_cross_tab":
						if($order_item->id == "_order_total"){
							$href = $item_href."&month_key=".$month_key."&detail_view=no";
							$value = "<a href=\"{$href}\" target=\"_blank\">{$value}</a>";
						}elseif($order_item->id == "_by_product"){
							$href = $item_href."&month_key=".$month_key."&detail_view=yes";
							$value = "<a href=\"{$href}\" target=\"_blank\">{$value}</a>";
						}												
						break;
					default:
						$value = $price;
						break;
				}
			return $value;
	}
		
	function get_clickable_price2($price,$month_key,$request_key,$order_item,$item_href,$start_date,$end_date){
				$value = $price;
				switch ($request_key) {
					case "product_cross_tab":						
						$href = $item_href."&product_id=".$order_item->id."&start_date=".$start_date."&end_date=".$end_date."&detail_view=yes";
						//.$this->create_url(array('category_id','order_status_id'))
						$value = "<a href=\"{$href}\" target=\"_blank\">{$value}</a>";
						break;
					case "variation_cross_tab":						
						$href = $item_href."&product_id=".$order_item->product_id."&variation_id=".$order_item->id."&start_date=".$start_date."&end_date=".$end_date."&detail_view=yes";
						$value = "<a href=\"{$href}\" target=\"_blank\">{$value}</a>";
						break;
					case "billing_country_cross_tab":						
						$href = $item_href."&country_code=".$order_item->id."&start_date=".$start_date."&end_date=".$end_date."&detail_view=no";
						$value = "<a href=\"{$href}\" target=\"_blank\">{$value}</a>";
						break;
					case "payment_gateway_cross_tab":
						$href = $item_href."&payment_method=".$order_item->payment_method."&start_date=".$start_date."&end_date=".$end_date."&detail_view=no";
						$value = "<a href=\"{$href}\" target=\"_blank\">{$value}</a>";
						break;
					case "order_status_cross_tab":
						$href = $item_href."&order_status_id=".$order_item->id."&start_date=".$start_date."&end_date=".$end_date."&detail_view=no";
						$value = "<a href=\"{$href}\" target=\"_blank\">{$value}</a>";
						break;
					case "product_bill_country_crosstab ":
						$href = $item_href."&start_date=".$start_date."&end_date=".$end_date."&product_id=".$order_item->id."&country_code=".$month_key."&detail_view=yes";
						$value = "<a href=\"{$href}\" target=\"_blank\">{$value}</a>";
						break;
					case "product_bill_state_crosstab":
						$href = $item_href."&start_date=".$start_date."&end_date=".$end_date."&product_id=".$order_item->id."&country_state_code=".$month_key."&detail_view=yes";
						$value = "<a href=\"{$href}\" target=\"_blank\">{$value}</a>";
						break;
					
					case "summary_cross_tab":
					
						if($order_item->id == "_order_total"){
							$href = $item_href."&start_date=".$start_date."&end_date=".$end_date."&detail_view=no";
							$value = "<a href=\"{$href}\" target=\"_blank\">{$value}</a>";
						}elseif($order_item->id == "_by_product"){
							$href = $item_href."&start_date=".$start_date."&end_date=".$end_date."&detail_view=yes";
							$value = "<a href=\"{$href}\" target=\"_blank\">{$value}</a>";
						}elseif($order_item->id == "_order_tax" 
							|| $order_item->id == "_order_discount"
							|| $order_item->id == "_cart_discount"
							|| $order_item->id == "_order_shipping"
							|| $order_item->id == "_order_shipping_tax"
							){							
							$href = $item_href."&start_date=".$start_date."&end_date=".$end_date."&detail_view=no&order_meta_key=".$order_item->id;
							$value = "<a href=\"{$href}\" target=\"_blank\">{$value}</a>";							
						}else{
							$href = $item_href."&start_date=".$start_date."&end_date=".$end_date."&detail_view=no";
							$value = "<a href=\"{$href}\" target=\"_blank\">{$value}</a>";
						}
						
						break;
					default:
						$value = $price;
						break;
				}
			return $value;
	}
		
	function create_url($para){
			global $request;
			$url = "";
			foreach($para as $key){
				if(isset($request[$key]) and $request[$key] != '-1')
					$url .= "&{$key}=".$request[$key];
			}
			
			return $url;
	}
		
	function total_count($current_count = 0,  $summary = array()){
			global $wpdb;			
			$request 			= $this->get_all_request();extract($request);
			$total_pages		= $summary['total_row_count'];
			$targetpage 		= "admin.php?page=".$admin_page;
			
			if($total_pages <$limit)
				$create_pagination 	= $this->get_pagination($total_pages,$limit,$adjacents,$targetpage,$request);			
			?>				
            <table style="width:100%">
                <tr>
                    <td>					
                        <?php echo $create_pagination;?>
                        <div class="clearfix"></div>
                        <div>
                            <?php
                                $this->export_to_csv_button('bottom',$summary);
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
	
	var $products_list_in_category = NULL;	
	public function get_product_items($type = 'limit_row', $items_only = true, $id = '-1'){
					
			global $wpdb;
			$request					= $this->get_all_request();extract($request);
			if($type == 'total_row'){
				$summary = $this->get_query_items($type, '', $request);							
				if($summary) return $summary;
			}
			
			$order_status				= $this->get_string_multi_request('order_status',$order_status, "-1");
			$hide_order_status			= $this->get_string_multi_request('hide_order_status',$hide_order_status, "-1");
			$product_status				= $this->get_string_multi_request('product_status',$product_status, "-1");
			$page						= $this->get_request('page','');
			//Added 20150219 Start
			if(!isset($this->products_list_in_category[$category_id])){
				$this->products_list_in_category[$category_id] = $this->get_products_list_in_category($category_id,$product_id);
			}						
			$category_product_id_string = $this->products_list_in_category[$category_id];
			$category_id 				= "-1";
			//Added 20150219 End
	
			$sql = " 
			SELECT
			woocommerce_order_itemmeta_product.meta_value 				as id
			,woocommerce_order_items.order_item_name 					as product_name
			,woocommerce_order_items.order_item_name 					as item_name
			,woocommerce_order_items.order_item_id 						as order_item_id
			,woocommerce_order_itemmeta_product.meta_value 				as product_id						
			,SUM(woocommerce_order_itemmeta_product_total.meta_value) 	as total
			,SUM(woocommerce_order_itemmeta_product_qty.meta_value) 	as quantity
			,MONTH(shop_order.post_date) 								as month_number
			,DATE_FORMAT(shop_order.post_date, '%Y-%m')					as month_key
			,COUNT(woocommerce_order_itemmeta_product.meta_value)		as item_count";
			
			
		$sql = apply_filters("ic_commerce_crosstab_page_select_query", $sql, $request, $type, $page, $report_name);
		
		$sql .= " 
			FROM {$wpdb->prefix}woocommerce_order_items 				as woocommerce_order_items
			LEFT JOIN  {$wpdb->prefix}woocommerce_order_itemmeta 		as woocommerce_order_itemmeta_product 			ON woocommerce_order_itemmeta_product.order_item_id=woocommerce_order_items.order_item_id
			LEFT JOIN  {$wpdb->prefix}posts 							as shop_order 									ON shop_order.id								=	woocommerce_order_items.order_id
			
			LEFT JOIN  {$wpdb->prefix}woocommerce_order_itemmeta 		as woocommerce_order_itemmeta_product_total 	ON woocommerce_order_itemmeta_product_total.order_item_id=woocommerce_order_items.order_item_id
			LEFT JOIN  {$wpdb->prefix}woocommerce_order_itemmeta 		as woocommerce_order_itemmeta_product_qty		ON woocommerce_order_itemmeta_product_qty.order_item_id		=	woocommerce_order_items.order_item_id";
			
		
		if($category_id != NULL  && $category_id != "-1"){
			$sql .= " 
			LEFT JOIN  {$wpdb->prefix}term_relationships 	as term_relationships 	ON term_relationships.object_id		=	woocommerce_order_itemmeta_product.meta_value
			LEFT JOIN  {$wpdb->prefix}term_taxonomy 		as term_taxonomy 		ON term_taxonomy.term_taxonomy_id	=	term_relationships.term_taxonomy_id
			LEFT JOIN  {$wpdb->prefix}terms 				as terms 				ON terms.term_id					=	term_taxonomy.term_id";
		}
		
		if($order_status_id != NULL  && $order_status_id != '-1'){
			$sql .= " 
			LEFT JOIN  {$wpdb->prefix}term_relationships 	as term_relationships2 	ON term_relationships2.object_id	=	woocommerce_order_items.order_id
			LEFT JOIN  {$wpdb->prefix}term_taxonomy 		as term_taxonomy2 		ON term_taxonomy2.term_taxonomy_id	=	term_relationships2.term_taxonomy_id
			LEFT JOIN  {$wpdb->prefix}terms 				as terms2 				ON terms2.term_id					=	term_taxonomy2.term_id";
		}
		
		if($product_status != NULL  && $product_status != '-1'){
			$sql .= " LEFT JOIN {$wpdb->prefix}posts AS products ON products.ID = woocommerce_order_itemmeta_product.meta_value";
		}
		
			
			$sql = apply_filters("ic_commerce_crosstab_page_join_query", $sql, $request, $type, $page, $report_name);
			
			$sql .= " 
			WHERE
			woocommerce_order_itemmeta_product.meta_key		=	'_product_id'
			AND woocommerce_order_items.order_item_type		=	'line_item'
			AND shop_order.post_type						=	'shop_order'

			AND woocommerce_order_itemmeta_product_total.meta_key		='_line_total'
			AND woocommerce_order_itemmeta_product_qty.meta_key			=	'_qty'";
		
		if($id != NULL  && $id != '-1'){
			$sql .= " AND woocommerce_order_itemmeta_product.meta_value = {$id} ";					
		}
		
		//if ($start_date != NULL &&  $end_date !=NULL)	$sql .= " AND DATE_FORMAT(shop_order.post_date, '%Y-%m') BETWEEN '".$cross_tab_start_date."' AND '". $cross_tab_end_date ."'";
		
		if($order_date_field_key == "post_date" || $order_date_field_key == "post_modified"){
			if ($start_date != NULL &&  $end_date !=NULL){
				$sql .= " AND DATE_FORMAT(shop_order.{$order_date_field_key}, '%Y-%m') BETWEEN '".$cross_tab_start_date."' AND '". $cross_tab_end_date ."'";
			}
		}
		
		if($category_id  != NULL && $category_id != "-1"){
			
			$sql .= " 
			AND term_taxonomy.taxonomy LIKE('product_cat')
			AND terms.term_id IN (".$category_id .")";
		}
		
		if($category_product_id_string  && $category_product_id_string != "-1") $sql .= " AND woocommerce_order_itemmeta_product.meta_value IN (".$category_product_id_string .")";//Added 20150219
		
		if($order_status_id != NULL  && $order_status_id != '-1'){
			$sql .= "
			AND term_taxonomy2.taxonomy LIKE('shop_order_status')
			AND terms2.term_id IN (".$order_status_id .")";
		}
		
		if($product_id != NULL  && $product_id != '-1'){
			$sql .= "
			AND woocommerce_order_itemmeta_product.meta_value IN ($product_id)";
		}
		
		if($order_status  && $order_status != '-1' and $order_status != "'-1'")$sql .= " AND shop_order.post_status IN (".$order_status.")";
		
		if($hide_order_status  && $hide_order_status != '-1' and $hide_order_status != "'-1'")$sql .= " AND shop_order.post_status NOT IN (".$hide_order_status.")";
		
		if($product_status != NULL  && $product_status != '-1'){
			$sql .= " AND products.post_type IN ('product')";
			$sql .= " AND products.post_status IN ({$product_status})";
		}
		
		$sql = apply_filters("ic_commerce_crosstab_page_where_query", $sql, $request, $type, $page, $report_name);
		
		if($items_only){
			$sql .= " group by woocommerce_order_itemmeta_product.meta_value ORDER BY total DESC";						
		}else
			$sql .= " group by month_number ORDER BY month_number";
			
		
		$wpdb->flush(); 				
		$wpdb->query("SET SQL_BIG_SELECTS=1");
		
		if($type == 'limit_row'){
			if($items_only) $sql .= " LIMIT $start, $limit";			
				$order_items = $wpdb->get_results($sql);			
			//$this->print_array($order_items);
			
		}else if($type == 'all_row'){
			$order_items = $wpdb->get_results($sql);
		}else if($type == 'total_row'){
			$order_items = $this->get_query_items($type, $sql, $request);
		}
		
		if(strlen($wpdb->last_error) > 0){
			echo $wpdb->last_error;
		}
		
		//$this->print_array($sql);echo "<hr>";
		
		return $order_items;
	}
	
	function get_query_items($type,$sql, $request){
			
			if($type == 'total_row'){
				if($request['count_generated'] == 1 || ($request['p'] > 1)){
					$order_items = $this->create_summary2($request);
					//echo "3";
					return $order_items;					
				}else if($request['count_generated'] == 0 and ($request['total_row_count'] == 0)){
					//$this->print_array($request);
					if($sql){
						global  $wpdb;					
						$order_items = $wpdb->get_results($sql);
						if($wpdb->last_error){
							echo $wpdb->last_error;
						}
						$total_count = count($order_items);
						$order_items = array('total_row_count' =>$total_count);	
						//echo "4";
						return $order_items;
					}
				}
				return false;
			}			
			return false;
	}
	
	function create_summary2($request = array()){
			$summary = array();
			$summary['total_row_count'] = isset($request['total_row_count']) ? $request['total_row_count'] : 0;
			return $summary;
	}
	
	
	var $variation_ids = '';
	function get_variation_ids($variation_attributes = '-1'){
			global $wpdb;
			if(!$this->variation_ids){
				$sql = " SELECT woocommerce_order_itemmeta_variation.meta_value		as variation_id";			
				$sql .= " FROM 	   {$wpdb->prefix}woocommerce_order_items		as woocommerce_order_items";
				$sql .= " LEFT JOIN  {$wpdb->prefix}woocommerce_order_itemmeta 	as woocommerce_order_itemmeta_variation	ON woocommerce_order_itemmeta_variation.order_item_id 		= 	woocommerce_order_items.order_item_id";			
				$sql .= " LEFT JOIN  {$wpdb->prefix}postmeta as postmeta_variation ON postmeta_variation.post_id = woocommerce_order_itemmeta_variation.meta_value";			
				$sql .= " WHERE 1*1";
				$sql .= " AND woocommerce_order_itemmeta_variation.meta_key 			= '_variation_id'";
				
				if($variation_attributes != "-1" and strlen($variation_attributes)>1)
					$sql .= " AND postmeta_variation.meta_key IN ('{$variation_attributes}')";
					
				$sql .= " GROUP BY variation_id";
					
				$items = $wpdb->get_results($sql);
				
				$variation_ids  = $this->get_items_id_list($items,'variation_id');
				
				$this->variation_ids = 	$variation_ids;
			}
			return $this->variation_ids;
			
			
	}
	
	
		
	public function get_variation_items($type = 'limit_row', $items_only = true, $id = '-1'){
			global $wpdb;
			$request					= $this->get_all_request();extract($request);
			if($type == 'total_row'){
				$summary = $this->get_query_items($type, '', $request);							
				if($summary) return $summary;
			}
			
			$order_status				= $this->get_string_multi_request('order_status',$order_status, "-1");
			$hide_order_status			= $this->get_string_multi_request('hide_order_status',$hide_order_status, "-1");
			$product_status				= $this->get_string_multi_request('product_status',$product_status, "-1");
			
			//Added 20150219 Start
			if(!isset($this->products_list_in_category[$category_id])){
				$this->products_list_in_category[$category_id] = $this->get_products_list_in_category($category_id,$product_id);
			}						
			$category_product_id_string = $this->products_list_in_category[$category_id];
			$category_id 				= "-1";
			
			$variation_ids				= '-1';
			if($variation_attributes != "-1" and strlen($variation_attributes)>1){
				$variation_ids = $this->get_variation_ids($variation_attributes);				
				$variation_attributes = "-1";
			}
			
			//Added 20150219 End
				
			$sql = " 
				SELECT 
				woocommerce_order_itemmeta_variation.meta_value			as id
				,woocommerce_order_itemmeta_product.meta_value 			as product_id
				,woocommerce_order_items.order_item_id 					as order_item_id
				,woocommerce_order_items.order_item_name 				as product_name
				,woocommerce_order_items.order_item_name 				as item_name
				
				
				,SUM(woocommerce_order_itemmeta_product_total.meta_value) 	as total
				,SUM(woocommerce_order_itemmeta_product_qty.meta_value) 	as quantity
				
				,MONTH(shop_order.post_date) 							as month_number
				,DATE_FORMAT(shop_order.post_date, '%Y-%m')				as month_key
				,woocommerce_order_itemmeta_variation.meta_value		as variation_id
				,woocommerce_order_items.order_id						as order_id
				,shop_order.post_status
				,woocommerce_order_items.order_item_id					as order_item_id";
			
			
		$sql = apply_filters("ic_commerce_crosstab_page_select_query", $sql, $request, $type, $page, $report_name);
		
		$sql .= " 
				
				FROM 	   {$wpdb->prefix}woocommerce_order_items		as woocommerce_order_items						
				LEFT JOIN  {$wpdb->prefix}woocommerce_order_itemmeta 	as woocommerce_order_itemmeta_product 			ON woocommerce_order_itemmeta_product.order_item_id			=	woocommerce_order_items.order_item_id
				LEFT JOIN  {$wpdb->prefix}woocommerce_order_itemmeta 	as woocommerce_order_itemmeta_product_total 	ON woocommerce_order_itemmeta_product_total.order_item_id	=	woocommerce_order_items.order_item_id
				LEFT JOIN  {$wpdb->prefix}woocommerce_order_itemmeta 	as woocommerce_order_itemmeta_product_qty		ON woocommerce_order_itemmeta_product_qty.order_item_id		=	woocommerce_order_items.order_item_id
				LEFT JOIN  {$wpdb->prefix}woocommerce_order_itemmeta 	as woocommerce_order_itemmeta_variation			ON woocommerce_order_itemmeta_variation.order_item_id 		= 	woocommerce_order_items.order_item_id
				LEFT JOIN  {$wpdb->prefix}posts 						as shop_order 									ON shop_order.id											=	woocommerce_order_items.order_id
			";
				
			if($category_id != NULL  && $category_id != "-1"){
				$sql .= " 
				LEFT JOIN  {$wpdb->prefix}term_relationships 			as term_relationships 							ON term_relationships.object_id		=	woocommerce_order_itemmeta_product.meta_value
				LEFT JOIN  {$wpdb->prefix}term_taxonomy 				as term_taxonomy 								ON term_taxonomy.term_taxonomy_id	=	term_relationships.term_taxonomy_id
				LEFT JOIN  {$wpdb->prefix}terms 						as terms 										ON terms.term_id					=	term_taxonomy.term_id";
			}
			
			if($order_status_id != NULL  && $order_status_id != '-1'){
				$sql .= " 
				LEFT JOIN  {$wpdb->prefix}term_relationships 			as term_relationships2 							ON term_relationships2.object_id	=	woocommerce_order_items.order_id
				LEFT JOIN  {$wpdb->prefix}term_taxonomy 				as term_taxonomy2 								ON term_taxonomy2.term_taxonomy_id	=	term_relationships2.term_taxonomy_id
				LEFT JOIN  {$wpdb->prefix}terms 						as terms2 										ON terms2.term_id					=	term_taxonomy2.term_id";
			}
			
			if($variation_attributes != "-1" and strlen($variation_attributes)>1)						
				$sql .= "
					LEFT JOIN  {$wpdb->prefix}postmeta as postmeta_variation ON postmeta_variation.post_id = woocommerce_order_itemmeta_variation.meta_value
					";		
			if($product_status != NULL  && $product_status != '-1'){
				$sql .= " LEFT JOIN {$wpdb->prefix}posts AS products ON products.ID = woocommerce_order_itemmeta_product.meta_value";
			}
			
			$sql = apply_filters("ic_commerce_crosstab_page_join_query", $sql, $request, $type, $page, $report_name);
			
			$sql .= " 
				WHERE
				woocommerce_order_itemmeta_product.meta_key		=	'_product_id'
				AND woocommerce_order_items.order_item_type		=	'line_item'
				AND shop_order.post_type						=	'shop_order'
				
				
				AND woocommerce_order_itemmeta_product_total.meta_key		='_line_total'
				AND woocommerce_order_itemmeta_product_qty.meta_key			=	'_qty'
				AND woocommerce_order_itemmeta_variation.meta_key 			= '_variation_id'
				AND (woocommerce_order_itemmeta_variation.meta_value IS NOT NULL AND woocommerce_order_itemmeta_variation.meta_value > 0)
			";
			
			if($variation_attributes != "-1" and strlen($variation_attributes)>1)
				$sql .= " AND postmeta_variation.meta_key IN ('{$variation_attributes}')";
				
			if($variation_ids != "-1" and strlen($variation_ids)>1){
				$sql .= " AND woocommerce_order_itemmeta_variation.meta_value IN ({$variation_ids})";
			}
			
			if($id != NULL  && $id != '-1'){
				$sql .= " AND woocommerce_order_itemmeta_variation.meta_value = {$id} ";					
			}
			
			//if ($start_date != NULL &&  $end_date !=NULL)	$sql .= " AND DATE_FORMAT(shop_order.post_date, '%Y-%m') BETWEEN '".$cross_tab_start_date."' AND '". $cross_tab_end_date ."'";
			
			if($order_date_field_key == "post_date" || $order_date_field_key == "post_modified"){
				if ($start_date != NULL &&  $end_date !=NULL){
					$sql .= " AND DATE_FORMAT(shop_order.{$order_date_field_key}, '%Y-%m') BETWEEN '".$cross_tab_start_date."' AND '". $cross_tab_end_date ."'";
				}
			}
			
			if($category_id  != NULL && $category_id != "-1"){
				
				$sql .= " 
				AND term_taxonomy.taxonomy LIKE('product_cat')
				AND terms.term_id IN (".$category_id .")";
			}
			
			if($category_product_id_string  && $category_product_id_string != "-1") $sql .= " AND woocommerce_order_itemmeta_product.meta_value IN (".$category_product_id_string .")";//Added 20150219
			
			if($order_status_id != NULL  && $order_status_id != '-1'){
				$sql .= "
				AND term_taxonomy2.taxonomy LIKE('shop_order_status')
				AND terms2.term_id IN (".$order_status_id .")";
			}
			
			if($product_id != NULL  && $product_id != '-1'){
				$sql .= "
				AND woocommerce_order_itemmeta_product.meta_value IN ($product_id)";
			}
			
			if($order_status  && $order_status != '-1' and $order_status != "'-1'")$sql .= " AND shop_order.post_status IN (".$order_status.")";
			if($hide_order_status  && $hide_order_status != '-1' and $hide_order_status != "'-1'")$sql .= " AND shop_order.post_status NOT IN (".$hide_order_status.")";
			
			if($product_status != NULL  && $product_status != '-1'){
				$sql .= " AND products.post_type IN ('product')";
				$sql .= " AND products.post_status IN ({$product_status})";
			}
			
			$sql = apply_filters("ic_commerce_crosstab_page_where_query", $sql, $request, $type, $page, $report_name);
			
			if($items_only){
				$sql .= " GROUP BY woocommerce_order_itemmeta_variation.meta_value ORDER BY {$sort_by} {$order_by}";
			}else
				$sql .= " group by month_number ORDER BY month_number";
				
			
			$wpdb->flush(); 				
			$wpdb->query("SET SQL_BIG_SELECTS=1");
			
			if($type == 'limit_row'){
				if($items_only) $sql .= " LIMIT $start, $limit";			
					$order_items = $wpdb->get_results($sql);
				
				if($items_only){
					//$this->print_sql($sql);
					//$this->print_array($order_items);
				}
				
			}else if($type == 'all_row'){
				$order_items = $wpdb->get_results($sql);
			}else if($type == 'total_row'){
				$order_items = $this->get_query_items($type, $sql, $request);
			}
			
			if(strlen($wpdb->last_error) > 0){
				echo $wpdb->last_error;
			}
			return $order_items;
		}	
		
		public function get_country_items($type = 'limit_row', $items_only = true, $id = '-1'){
			global $wpdb;
			$request					= $this->get_all_request();extract($request);
			if($type == 'total_row'){
				$summary = $this->get_query_items($type, '', $request);							
				if($summary) return $summary;
			}
			$order_status				= $this->get_string_multi_request('order_status',$order_status, "-1");
			$hide_order_status			= $this->get_string_multi_request('hide_order_status',$hide_order_status, "-1");
			
			$country      				= $this->get_wc_countries();//Added 20150225
					
			if($country_code != NULL  && $country_code != '-1')
				$country_code = str_replace(",", "','",$country_code);
									
				$sql = "
				SELECT 
				postmeta1.meta_value 							as id	
				,postmeta1.meta_value						 	as country_name
				,postmeta1.meta_value						 	as country_code
				,postmeta1.meta_value						 	as item_name
				,SUM(postmeta2.meta_value)						as total
				,COUNT(shop_order.ID) 							as quantity
				
				,MONTH(shop_order.post_date) 					as month_number
				,DATE_FORMAT(shop_order.post_date, '%Y-%m')		as month_key";
			
			
		$sql = apply_filters("ic_commerce_crosstab_page_select_query", $sql, $request, $type, $page, $report_name);
		
		$sql .= " 
				
				FROM {$wpdb->prefix}posts as shop_order 
				LEFT JOIN	{$wpdb->prefix}postmeta as postmeta1 on postmeta1.post_id = shop_order.ID
				LEFT JOIN	{$wpdb->prefix}postmeta as postmeta2 on postmeta2.post_id = shop_order.ID
				
				";
					
				if($order_status_id != NULL  && $order_status_id != '-1'){
					$sql .= " 
					LEFT JOIN  {$wpdb->prefix}term_relationships 	as term_relationships2 	ON term_relationships2.object_id	=	shop_order.ID
					LEFT JOIN  {$wpdb->prefix}term_taxonomy 		as term_taxonomy2 		ON term_taxonomy2.term_taxonomy_id	=	term_relationships2.term_taxonomy_id
					LEFT JOIN  {$wpdb->prefix}terms 				as terms2 				ON terms2.term_id					=	term_taxonomy2.term_id";
				}	
				
				$sql = apply_filters("ic_commerce_crosstab_page_join_query", $sql, $request, $type, $page, $report_name);
				
				$sql .= " 
				
				WHERE shop_order.post_type	= 'shop_order'
				AND postmeta1.meta_key 		= '_billing_country'
				AND	postmeta2.meta_key 		= '_order_total'";
				
				
				
				if($id != NULL  && $id != '-1'){
					$sql .= " AND postmeta1.meta_value IN ('{$id}') ";					
				}
				
				if($order_status_id != NULL  && $order_status_id != '-1'){
					$sql .= "
					AND term_taxonomy2.taxonomy LIKE('shop_order_status')
					AND terms2.term_id IN (".$order_status_id .")";
				}
			
				//if ($start_date != NULL &&  $end_date !=NULL)	$sql .= " AND DATE_FORMAT(shop_order.post_date, '%Y-%m') BETWEEN '".$cross_tab_start_date."' AND '". $cross_tab_end_date ."'";
				
				if($order_date_field_key == "post_date" || $order_date_field_key == "post_modified"){
					if ($start_date != NULL &&  $end_date !=NULL){
						$sql .= " AND DATE_FORMAT(shop_order.{$order_date_field_key}, '%Y-%m') BETWEEN '".$cross_tab_start_date."' AND '". $cross_tab_end_date ."'";
					}
				}
				
				if($country_code != NULL  && $country_code != '-1')
					$sql .= "	
						AND	postmeta1.meta_value 	IN ('{$country_code}')";
				
				if($order_status  && $order_status != '-1' and $order_status != "'-1'")$sql .= " AND shop_order.post_status IN (".$order_status.")";
				if($hide_order_status  && $hide_order_status != '-1' and $hide_order_status != "'-1'")$sql .= " AND shop_order.post_status NOT IN (".$hide_order_status.")";
				
				$sql = apply_filters("ic_commerce_crosstab_page_where_query", $sql, $request, $type, $page, $report_name);
				
				if($items_only)
					$sql .= " group by postmeta1.meta_value  ORDER BY {$sort_by} {$order_by}";
				else
					$sql .= " group by month_number ORDER BY month_number";
					
				
				$wpdb->flush(); 				
				$wpdb->query("SET SQL_BIG_SELECTS=1");
				
				if($type == 'limit_row'){
					if($items_only) $sql .= " LIMIT $start, $limit";			
					$order_items = $wpdb->get_results($sql);
				}else if($type == 'all_row'){
					$order_items = $wpdb->get_results($sql);
				}else if($type == 'total_row'){
					$order_items = $this->get_query_items($type, $sql, $request);
				}
				
				if(strlen($wpdb->last_error) > 0){
					echo $wpdb->last_error;
				}
				
				if($type == 'limit_row' || $type == 'all_row'){
					if(count($order_items)>0 and $items_only == true)
					foreach($order_items as $key => $value){
						$order_items[$key]->country_name = isset($country->countries[$value->country_name]) ? $country->countries[$value->country_name]: $value->country_name;
					}
				}
				return $order_items;
					
		}
		
		public function get_status_items($type = 'limit_row', $items_only = true, $id = '-1'){
				global $wpdb;
				$request					= $this->get_all_request();extract($request);
				if($type == 'total_row'){
					$summary = $this->get_query_items($type, '', $request);							
					if($summary) return $summary;
				}
				
				$order_status				= $this->get_string_multi_request('order_status',$order_status, "-1");
				$hide_order_status			= $this->get_string_multi_request('hide_order_status',$hide_order_status, "-1");
				
				$sql = "SELECT ";
				if($this->constants['post_order_status_found'] == 0 ){
					$sql .= " 
					terms2.term_id 							as id
					,terms2.term_id 						as group_column
					,terms2.name						 	as status_name	
					,terms2.name						 	as item_name";
					
				}else{
					$sql .= " 
					shop_order.post_status 					as id
					,shop_order.post_status					as group_column
					,shop_order.post_status				 	as status_name	
					,shop_order.post_status				 	as item_name
					,shop_order.post_status				 	as order_status";
				}
				$sql .= " 					
					,SUM(postmeta2.meta_value)				as total
					,COUNT(shop_order.ID) 					as quantity
					,MONTH(shop_order.post_date) 			as month_number
					
					,MONTH(shop_order.post_date) 					as month_number
					,DATE_FORMAT(shop_order.post_date, '%Y-%m')		as month_key";
			
			
		$sql = apply_filters("ic_commerce_crosstab_page_select_query", $sql, $request, $type, $page, $report_name);
		
		$sql .= " 
					
					
					FROM {$wpdb->prefix}posts as shop_order 
					LEFT JOIN	{$wpdb->prefix}postmeta as postmeta2 on postmeta2.post_id = shop_order.ID
					
					";
						
				if($this->constants['post_order_status_found'] == 0 ){
					$sql .= " 
					LEFT JOIN  {$wpdb->prefix}term_relationships 	as term_relationships2 	ON term_relationships2.object_id	=	shop_order.ID
					LEFT JOIN  {$wpdb->prefix}term_taxonomy 		as term_taxonomy2 		ON term_taxonomy2.term_taxonomy_id	=	term_relationships2.term_taxonomy_id
					LEFT JOIN  {$wpdb->prefix}terms 				as terms2 				ON terms2.term_id					=	term_taxonomy2.term_id";
				}
				
				
				$sql = apply_filters("ic_commerce_crosstab_page_join_query", $sql, $request, $type, $page, $report_name);
				
				$sql .= " 					
					WHERE shop_order.post_type	= 'shop_order'
					
					AND	postmeta2.meta_key 		= '_order_total'";
					
					if($this->constants['post_order_status_found'] == 0 ){
						$sql .= " AND term_taxonomy2.taxonomy LIKE('shop_order_status')";
					}
					
					if($this->constants['post_order_status_found'] == 0 ){
						if($id != NULL  && $id != '-1'){
							$sql .= " 
							AND terms2.term_id IN ('{$id}') ";					
						}
					}else{
						if($id != NULL  && $id != '-1'){
							$sql .= " 
							AND shop_order.post_status IN ('{$id}') ";					
						}
					}
					
					
					if($order_status_id != NULL  && $order_status_id != '-1'){
						$sql .= "						
						AND terms2.term_id IN (".$order_status_id .")";
					}
				
					//if ($start_date != NULL &&  $end_date !=NULL)	$sql .= " AND DATE_FORMAT(shop_order.post_date, '%Y-%m') BETWEEN '".$cross_tab_start_date."' AND '". $cross_tab_end_date ."'";
					
					if($order_date_field_key == "post_date" || $order_date_field_key == "post_modified"){
						if ($start_date != NULL &&  $end_date !=NULL){
							$sql .= " AND DATE_FORMAT(shop_order.{$order_date_field_key}, '%Y-%m') BETWEEN '".$cross_tab_start_date."' AND '". $cross_tab_end_date ."'";
						}
					}
					
					if($order_status  && $order_status != '-1' and $order_status != "'-1'")$sql .= " AND shop_order.post_status IN (".$order_status.")";
					if($hide_order_status  && $hide_order_status != '-1' and $hide_order_status != "'-1'")$sql .= " AND shop_order.post_status NOT IN (".$hide_order_status.")";
					
					$sql = apply_filters("ic_commerce_crosstab_page_where_query", $sql, $request, $type, $page, $report_name);
					
					if($items_only)
						$sql .= " GROUP BY group_column  ORDER BY {$sort_by} {$order_by}";
					else
						$sql .= " GROUP BY month_number ORDER BY month_number";
					
					$wpdb->flush(); 				
					$wpdb->query("SET SQL_BIG_SELECTS=1");
					
					
					
					if($type == 'limit_row' || $type == 'all_row' or $type == 'all_row_total'){
						$order_items = $wpdb->get_results($sql);
						if($this->constants['post_order_status_found'] == 1 ){
						
							$order_statuses = $this->ic_get_order_statuses();
							
							foreach($order_items as $key  => $value){
								$order_items[$key]->status_name = isset($order_statuses[$value->order_status]) ? $order_statuses[$value->order_status] : '';
							}
						}else{
							foreach($order_items as $key => $value){
								$order_items[$key]->status_name = ucwords($value->status_name);
							}
						}
					}
					
					if($type == 'total_row'){
						$order_items = $this->get_query_items($type, $sql, $request);
						
					}
					return $order_items;
		}
		
		function get_payment_gatway(){
			global $wpdb;

			$sql = "
					SELECT 
					postmeta1.meta_value 							as id	
					,postmeta3.meta_value						 	as label
					
					
					FROM {$wpdb->prefix}posts as shop_order 
					LEFT JOIN	{$wpdb->prefix}postmeta as postmeta1 on postmeta1.post_id = shop_order.ID
					LEFT JOIN	{$wpdb->prefix}postmeta as postmeta3 on postmeta3.post_id = shop_order.ID";
					
					$sql .= " 					
					WHERE shop_order.post_type	= 'shop_order'
					AND postmeta1.meta_key 		= '_payment_method'
					AND	postmeta3.meta_key 		= '_payment_method_title'";
					
					$sql .= " group by postmeta1.meta_value ORDER BY postmeta3.meta_value ASC";
					
					$wpdb->flush(); 				
					$wpdb->query("SET SQL_BIG_SELECTS=1");
					$order_items = $wpdb->get_results($sql);
					
					return $order_items;
		}
		
		public function get_country_product_items($type = 'limit_row', $items_only = true, $id = '-1',$region_code){
				global $wpdb;
				$request 			= $this->get_all_request();extract($request);
				if($type == 'total_row'){
					$summary = $this->get_query_items($type, '', $request);							
					if($summary) return $summary;
				}
				$order_status		= $this->get_string_multi_request('order_status',$order_status, "-1");
				$hide_order_status	= $this->get_string_multi_request('hide_order_status',$hide_order_status, "-1");
				$product_status				= $this->get_string_multi_request('product_status',$product_status, "-1");
				
				//Added 20150219 Start
				if(!isset($this->products_list_in_category[$category_id])){
					$this->products_list_in_category[$category_id] = $this->get_products_list_in_category($category_id,$product_id);
				}						
				$category_product_id_string = $this->products_list_in_category[$category_id];
				$category_id 				= "-1";
				//Added 20150219 End
				
				if($country_code != NULL  && $country_code != '-1')
					$country_code = str_replace(",", "','",$country_code);
				
				if(!isset($_REQUEST['new_status_code'])){
					if($state_code != NULL  && $state_code != '-1'){
						$state_codes = explode(",",$state_code);
						$statecodes = array();
						foreach($state_codes as $key => $value){
							$v = explode("-", $value);
							$statecodes[] = $v[1];
						}
											
						$state_code = implode("','", $statecodes);
						$_REQUEST['new_status_code'] = $state_code;
					}
				}else{
					$state_code = $_REQUEST['new_status_code'];
				}
				
				
					$sql = " 
						SELECT
						woocommerce_order_itemmeta_product.meta_value 			as id
						,woocommerce_order_items.order_item_name 				as product_name
						,woocommerce_order_itemmeta_product.meta_value 			as product_id
						,woocommerce_order_items.order_item_id 					as order_item_id
						,woocommerce_order_items.order_item_name 				as item_name
						
						
						,SUM(woocommerce_order_itemmeta_product_total.meta_value) 	as total
						,SUM(woocommerce_order_itemmeta_product_qty.meta_value) 	as quantity";
						
					
					if($region_code == "_billing_state"){
						$sql .= "
						,billing_state.meta_value as billing_state
						,CONCAT(billing_country.meta_value,'-',billing_state.meta_value) as month_key
						,CONCAT(billing_country.meta_value,'-',billing_state.meta_value) as state_code
						,billing_state.meta_value as month_number ";
					}else{
						$sql .= "
						,billing_country.meta_value as month_key
						,billing_country.meta_value as month_number ";
					}
						
					$sql .= " 	
						,billing_country.meta_value as billing_country";
			
			
					$sql = apply_filters("ic_commerce_crosstab_page_select_query", $sql, $request, $type, $page, $report_name);
					
					$sql .= " 
						FROM {$wpdb->prefix}woocommerce_order_items 			as woocommerce_order_items
						LEFT JOIN  {$wpdb->prefix}woocommerce_order_itemmeta 	as woocommerce_order_itemmeta_product 			ON woocommerce_order_itemmeta_product.order_item_id=woocommerce_order_items.order_item_id
						LEFT JOIN  {$wpdb->prefix}posts 						as shop_order 									ON shop_order.id								=	woocommerce_order_items.order_id
						
						LEFT JOIN  {$wpdb->prefix}woocommerce_order_itemmeta 	as woocommerce_order_itemmeta_product_total 	ON woocommerce_order_itemmeta_product_total.order_item_id=woocommerce_order_items.order_item_id
						LEFT JOIN  {$wpdb->prefix}woocommerce_order_itemmeta 	as woocommerce_order_itemmeta_product_qty		ON woocommerce_order_itemmeta_product_qty.order_item_id		=	woocommerce_order_items.order_item_id
						LEFT JOIN  {$wpdb->prefix}postmeta 						as billing_country 								ON billing_country.post_id									=	shop_order.ID";
						
					if($region_code == "_billing_state"){
						$sql .= "
						LEFT JOIN  {$wpdb->prefix}postmeta 						as billing_state 								ON billing_state.post_id									=	shop_order.ID";
					}
					
					if($category_id != NULL  && $category_id != "-1"){
						$sql .= " 
						LEFT JOIN  {$wpdb->prefix}term_relationships 	as term_relationships 	ON term_relationships.object_id		=	woocommerce_order_itemmeta_product.meta_value
						LEFT JOIN  {$wpdb->prefix}term_taxonomy 		as term_taxonomy 		ON term_taxonomy.term_taxonomy_id	=	term_relationships.term_taxonomy_id
						LEFT JOIN  {$wpdb->prefix}terms 				as terms 				ON terms.term_id					=	term_taxonomy.term_id";
					}
					
					if($order_status_id != NULL  && $order_status_id != '-1'){
						$sql .= " 
						LEFT JOIN  {$wpdb->prefix}term_relationships 	as term_relationships2 	ON term_relationships2.object_id	=	woocommerce_order_items.order_id
						LEFT JOIN  {$wpdb->prefix}term_taxonomy 		as term_taxonomy2 		ON term_taxonomy2.term_taxonomy_id	=	term_relationships2.term_taxonomy_id
						LEFT JOIN  {$wpdb->prefix}terms 				as terms2 				ON terms2.term_id					=	term_taxonomy2.term_id";
					}
					
					if($product_status != NULL  && $product_status != '-1'){
						$sql .= " LEFT JOIN {$wpdb->prefix}posts AS products ON products.ID = woocommerce_order_itemmeta_product.meta_value";
					}		
					
					$sql = apply_filters("ic_commerce_crosstab_page_join_query", $sql, $request, $type, $page, $report_name);
					
						$sql .= " 
						WHERE
						woocommerce_order_itemmeta_product.meta_key		=	'_product_id'
						AND woocommerce_order_items.order_item_type		=	'line_item'
						AND shop_order.post_type						=	'shop_order'
						
						AND billing_country.meta_key							=	'_billing_country'
						AND woocommerce_order_itemmeta_product_total.meta_key		='_line_total'
						AND woocommerce_order_itemmeta_product_qty.meta_key			=	'_qty'";
						
					if($region_code == "_billing_state"){
						$sql .= " AND billing_state.meta_key							=	'_billing_state'";
					}
					
					if($id != NULL  && $id != '-1'){
						$sql .= " AND woocommerce_order_itemmeta_product.meta_value IN ({$id}) ";					
					}
					
					//if ($start_date != NULL &&  $end_date !=NULL)	$sql .= " AND DATE_FORMAT(shop_order.post_date, '%Y-%m') BETWEEN '".$cross_tab_start_date."' AND '". $cross_tab_end_date ."'";
					
					if($order_date_field_key == "post_date" || $order_date_field_key == "post_modified"){
						if ($start_date != NULL &&  $end_date !=NULL){
							$sql .= " AND DATE_FORMAT(shop_order.{$order_date_field_key}, '%Y-%m') BETWEEN '".$cross_tab_start_date."' AND '". $cross_tab_end_date ."'";
						}
					}
					
					if($category_id  != NULL && $category_id != "-1"){
						
						$sql .= " 
						AND term_taxonomy.taxonomy LIKE('product_cat')
						AND terms.term_id IN (".$category_id .")";
					}
					
					if($category_product_id_string  && $category_product_id_string != "-1") $sql .= " AND woocommerce_order_itemmeta_product.meta_value IN (".$category_product_id_string .")";//Added 20150219
					
					if($order_status_id != NULL  && $order_status_id != '-1'){
						$sql .= "
						AND term_taxonomy2.taxonomy LIKE('shop_order_status')
						AND terms2.term_id IN (".$order_status_id .")";
					}
					
					if($product_id != NULL  && $product_id != '-1'){
						$sql .= "
						AND woocommerce_order_itemmeta_product.meta_value IN ($product_id)";
					}
					
					if($country_code != NULL  && $country_code != '-1')
						$sql .= " 
							AND	billing_country.meta_value	IN ('{$country_code}')";
						
					if($state_code != NULL  && $state_code != '-1')
						$sql .= " 
							AND	billing_state.meta_value	IN ('{$state_code}')";
							
					if($order_status  && $order_status != '-1' and $order_status != "'-1'")$sql .= " AND shop_order.post_status IN (".$order_status.")";
					if($hide_order_status  && $hide_order_status != '-1' and $hide_order_status != "'-1'")$sql .= " AND shop_order.post_status NOT IN (".$hide_order_status.")";
					
					if($product_status != NULL  && $product_status != '-1'){
						$sql .= " AND products.post_type IN ('product')";
						$sql .= " AND products.post_status IN ({$product_status})";
					}
					
					$sql = apply_filters("ic_commerce_crosstab_page_where_query", $sql, $request, $type, $page, $report_name);
					
					if($items_only)
						$sql .= " group by woocommerce_order_itemmeta_product.meta_value ORDER BY {$sort_by} {$order_by}";
					else
						$sql .= " group by month_number ORDER BY month_number";
						
						;
					
					$wpdb->flush(); 				
					$wpdb->query("SET SQL_BIG_SELECTS=1");
					
					if($type == 'limit_row'){
						if($items_only) $sql .= " LIMIT $start, $limit";			
						$order_items = $wpdb->get_results($sql);
					}else if($type == 'all_row'){
						$order_items = $wpdb->get_results($sql);
					}else if($type == 'total_row'){
						$order_items = $this->get_query_items($type, $sql, $request);
					}
					
					if(strlen($wpdb->last_error) > 0){
						echo $wpdb->last_error;
					}
					return $order_items;
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
		
		function get_sales_items($type = 'limit_row', $items_only = true, $id = '-1'){
			global $wpdb;
			$order_items = array();
			
			if($type == 'total_row'){
				return $summary = array('total_row_count' => 0);
			}
				
			if($items_only){
				$reports		= $this->get_request('reports','-1',true);
				$array = array(
					"0"  => array("item_name"=>__("Order Total",			'icwoocommerce_textdomains'),"id"=>"_order_total")
					,"1" => array("item_name"=>__("Order Tax",				'icwoocommerce_textdomains'),"id"=>"_order_tax")					
					,"2" => array("item_name"=>__("Order Discount",			'icwoocommerce_textdomains'),"id"=>"_order_discount")
					,"3" => array("item_name"=>__("Cart Discount",			'icwoocommerce_textdomains'),"id"=>"_cart_discount")
					,"4" => array("item_name"=>__("Order Shipping",			'icwoocommerce_textdomains'),"id"=>"_order_shipping")
					,"5" => array("item_name"=>__("Order Shipping Tax",		'icwoocommerce_textdomains'),"id"=>"_order_shipping_tax")
					,"6" => array("item_name"=>__("Product Sales",			'icwoocommerce_textdomains'),"id"=>"_by_product")
				);
				
				if($reports != '-1'){
					$reports = explode(",", $reports);
						$new_array = array();
						foreach($reports as $key => $value)
							$new_array[] = $array[$value];
						$array = $new_array;
				}
				
				$order_items = $this->convert_obj($array);
			}else{
				
				$request = $this->get_all_request();extract($request);
				
				$order_status		= $this->get_string_multi_request('order_status',$order_status, "-1");
				$hide_order_status	= $this->get_string_multi_request('hide_order_status',$hide_order_status, "-1");
				
				if($id == "_by_product"){
					$sql = " 
						SELECT
						woocommerce_order_itemmeta_product.meta_value 			as id
						,woocommerce_order_items.order_item_name 				as product_name
						,woocommerce_order_items.order_item_name 				as item_name
						,SUM(woocommerce_order_itemmeta_product_total.meta_value) 	as total
						,SUM(woocommerce_order_itemmeta_product_qty.meta_value) 	as quantity
						
						,MONTH(shop_order.post_date) 					as month_number
						,DATE_FORMAT(shop_order.post_date, '%Y-%m')		as month_key";
			
			
					$sql = apply_filters("ic_commerce_crosstab_page_select_query", $sql, $request, $type, $page, $report_name);
					
					$sql .= " 
					
						FROM {$wpdb->prefix}woocommerce_order_items 			as woocommerce_order_items
						LEFT JOIN  {$wpdb->prefix}woocommerce_order_itemmeta 	as woocommerce_order_itemmeta_product 			ON woocommerce_order_itemmeta_product.order_item_id			= woocommerce_order_items.order_item_id
						LEFT JOIN  {$wpdb->prefix}posts 						as shop_order 									ON shop_order.id											= woocommerce_order_items.order_id
						
						LEFT JOIN  {$wpdb->prefix}woocommerce_order_itemmeta 	as woocommerce_order_itemmeta_product_total 	ON woocommerce_order_itemmeta_product_total.order_item_id	= woocommerce_order_items.order_item_id
						LEFT JOIN  {$wpdb->prefix}woocommerce_order_itemmeta 	as woocommerce_order_itemmeta_product_qty		ON woocommerce_order_itemmeta_product_qty.order_item_id		= woocommerce_order_items.order_item_id";
						
						if($order_status_id != NULL  && $order_status_id != '-1'){
							$sql .= " 
							LEFT JOIN  {$wpdb->prefix}term_relationships 	as term_relationships2 	ON term_relationships2.object_id	=	shop_order.ID
							LEFT JOIN  {$wpdb->prefix}term_taxonomy 		as term_taxonomy2 		ON term_taxonomy2.term_taxonomy_id	=	term_relationships2.term_taxonomy_id
							LEFT JOIN  {$wpdb->prefix}terms 				as terms2 				ON terms2.term_id					=	term_taxonomy2.term_id";
						}
						
						$sql = apply_filters("ic_commerce_crosstab_page_join_query", $sql, $request, $type, $page, $report_name);
					
						$sql .= " 
						WHERE
						woocommerce_order_itemmeta_product.meta_key					= '_product_id'
						AND woocommerce_order_items.order_item_type					= 'line_item'
						AND shop_order.post_type									= 'shop_order'
						AND woocommerce_order_itemmeta_product_total.meta_key		= '_line_total'
						AND woocommerce_order_itemmeta_product_qty.meta_key			= '_qty'";
				}else{
					$sql = "
					SELECT 
				
					SUM(postmeta2.meta_value)						as total
					,COUNT(shop_order.ID) 							as quantity
					
					,MONTH(shop_order.post_date) 					as month_number
					,DATE_FORMAT(shop_order.post_date, '%Y-%m')		as month_key";
			
			
				$sql = apply_filters("ic_commerce_crosstab_page_select_query", $sql, $request, $type, $page, $report_name);
				
				$sql .= " 
					
					FROM {$wpdb->prefix}posts as shop_order					
					LEFT JOIN	{$wpdb->prefix}postmeta as postmeta2 on postmeta2.post_id = shop_order.ID";
					
					if($order_status_id != NULL  && $order_status_id != '-1'){
						$sql .= " 
						LEFT JOIN  {$wpdb->prefix}term_relationships 	as term_relationships2 	ON term_relationships2.object_id	=	shop_order.ID
						LEFT JOIN  {$wpdb->prefix}term_taxonomy 		as term_taxonomy2 		ON term_taxonomy2.term_taxonomy_id	=	term_relationships2.term_taxonomy_id
						LEFT JOIN  {$wpdb->prefix}terms 				as terms2 				ON terms2.term_id					=	term_taxonomy2.term_id";
					}
					
					$sql = apply_filters("ic_commerce_crosstab_page_join_query", $sql, $request, $type, $page, $report_name);
					
					$sql .= "
					WHERE shop_order.post_type	= 'shop_order'";
					if($id != NULL  && $id != '-1'){
						$sql .= " AND	postmeta2.meta_key 	= '{$id}'";
					}
					
					$sql .= " AND postmeta2.meta_value > 0";
				}
				
				if($order_status  && $order_status != '-1' and $order_status != "'-1'")$sql .= " AND shop_order.post_status IN (".$order_status.")";
				
				if($hide_order_status  && $hide_order_status != '-1' and $hide_order_status != "'-1'")$sql .= " AND shop_order.post_status NOT IN (".$hide_order_status.")";
				
				//if ($start_date != NULL &&  $end_date !=NULL)	$sql .= " AND DATE_FORMAT(shop_order.post_date, '%Y-%m') BETWEEN '".$cross_tab_start_date."' AND '". $cross_tab_end_date ."'";
				
				if($order_date_field_key == "post_date" || $order_date_field_key == "post_modified"){
					if ($start_date != NULL &&  $end_date !=NULL){
						$sql .= " AND DATE_FORMAT(shop_order.{$order_date_field_key}, '%Y-%m') BETWEEN '".$cross_tab_start_date."' AND '". $cross_tab_end_date ."'";
					}
				}
					
				if($order_status_id != NULL  && $order_status_id != '-1'){
					$sql .= "
					AND term_taxonomy2.taxonomy LIKE('shop_order_status')
					AND terms2.term_id IN (".$order_status_id .")";
				}	
					
					
				//if($order_status  && $order_status != '-1' and $order_status != "'-1'")$sql .= " AND shop_order.post_status IN (".$order_status.")";
				
				//if($hide_order_status  && $hide_order_status != '-1' and $hide_order_status != "'-1'")$sql .= " AND shop_order.post_status NOT IN (".$hide_order_status.")";
				
				$sql = apply_filters("ic_commerce_crosstab_page_where_query", $sql, $request, $type, $page, $report_name);
								
				$sql .= " group by month_number ORDER BY month_number";
				
				
				$wpdb->flush(); 				
				$wpdb->query("SET SQL_BIG_SELECTS=1");
				$order_items = $wpdb->get_results($sql);
			}
			return $order_items;
		}
		
		function convert_obj($array){
			$object = new stdClass();
				
			foreach ($array as $key => $value)
			{
				if(is_array($value))
					$object->$key = $this->convert_obj($value);
				else
					$object->$key = $value;
			}
			return $object;
		}
		
		public function get_payment_gateway_items($type = 'limit_row', $items_only = true, $id = '-1'){
			global $wpdb;
				$request = $this->get_all_request();extract($request);	
				$order_status		= $this->get_string_multi_request('order_status',$order_status, "-1");
				$hide_order_status	= $this->get_string_multi_request('hide_order_status',$hide_order_status, "-1");
						
				$country      		= $this->get_wc_countries();//Added 20150225
						
				if(isset($payment_gatway) && $payment_gatway != NULL  && $payment_gatway != '-1')
					$payment_gatway = str_replace(",", "','",$payment_gatway);
										
					$sql = "
					SELECT 
					postmeta1.meta_value 							as id	
					,postmeta3.meta_value						 	as payment_method
					,postmeta3.meta_value						 	as item_name
					,SUM(postmeta2.meta_value)						as total
					,COUNT(shop_order.ID) 							as quantity
					,MONTH(shop_order.post_date) 					as month_number
					,DATE_FORMAT(shop_order.post_date, '%Y-%m')		as month_key
					
					FROM {$wpdb->prefix}posts as shop_order 
					LEFT JOIN	{$wpdb->prefix}postmeta as postmeta1 on postmeta1.post_id = shop_order.ID
					LEFT JOIN	{$wpdb->prefix}postmeta as postmeta2 on postmeta2.post_id = shop_order.ID
					LEFT JOIN	{$wpdb->prefix}postmeta as postmeta3 on postmeta3.post_id = shop_order.ID
					
					";
						
					if($order_status_id != NULL  && $order_status_id != '-1'){
						$sql .= " 
						LEFT JOIN  {$wpdb->prefix}term_relationships 	as term_relationships2 	ON term_relationships2.object_id	=	shop_order.ID
						LEFT JOIN  {$wpdb->prefix}term_taxonomy 		as term_taxonomy2 		ON term_taxonomy2.term_taxonomy_id	=	term_relationships2.term_taxonomy_id
						LEFT JOIN  {$wpdb->prefix}terms 				as terms2 				ON terms2.term_id					=	term_taxonomy2.term_id";
					}	
					
					$sql = apply_filters("ic_commerce_crosstab_page_join_query", $sql, $request, $type, $page, $report_name);
					
					$sql .= " 
					
					WHERE shop_order.post_type	= 'shop_order'
					AND postmeta1.meta_key 		= '_payment_method'
					AND	postmeta2.meta_key 		= '_order_total'
					AND	postmeta3.meta_key 		= '_payment_method_title'";
					
					if($id != NULL  && $id != '-1'){
						$sql .= " AND postmeta1.meta_value IN ('{$id}') ";
						//$sql .= " AND postmeta1.meta_value IN ('cheque','paypal') ";
					}
					
					if($order_status_id != NULL  && $order_status_id != '-1'){
						$sql .= "
						AND term_taxonomy2.taxonomy LIKE('shop_order_status')
						AND terms2.term_id IN (".$order_status_id .") ";
					}
				
					//if ($start_date != NULL &&  $end_date !=NULL)	$sql .= " AND DATE_FORMAT(shop_order.post_date, '%Y-%m') BETWEEN '".$cross_tab_start_date."' AND '". $cross_tab_end_date ."'";
					
					if($order_date_field_key == "post_date" || $order_date_field_key == "post_modified"){
						if ($start_date != NULL &&  $end_date !=NULL){
							$sql .= " AND DATE_FORMAT(shop_order.{$order_date_field_key}, '%Y-%m') BETWEEN '".$cross_tab_start_date."' AND '". $cross_tab_end_date ."'";
						}
					}
					
					if(isset($payment_gatway) && $payment_gatway != NULL  && $payment_gatway != '-1')
						$sql .= "	
							AND	postmeta1.meta_value IN ('{$payment_gatway}')";
					
					if($order_status  && $order_status != '-1' and $order_status != "'-1'")$sql .= " AND shop_order.post_status IN (".$order_status.")";
					if($hide_order_status  && $hide_order_status != '-1' and $hide_order_status != "'-1'")$sql .= " AND shop_order.post_status NOT IN (".$hide_order_status.")";
					
					$sql = apply_filters("ic_commerce_crosstab_page_where_query", $sql, $request, $type, $page, $report_name);
					
					if($items_only)
						$sql .= " group by item_name ORDER BY item_name ASC";
					else
						$sql .= " group by month_number ORDER BY month_number";
						
						
					$wpdb->flush(); 				
					$wpdb->query("SET SQL_BIG_SELECTS=1");
					
					if($type == 'limit_row'){
						if($items_only) $sql .= " LIMIT $start, $limit";			
						$order_items = $wpdb->get_results($sql);
					}else if($type == 'all_row'){
						$order_items = $wpdb->get_results($sql);
					}else if($type == 'total_row'){
						$order_items = $this->get_query_items($type, $sql, $request);
					}
					
					if(strlen($wpdb->last_error) > 0){
						echo $wpdb->last_error;
					}
					return $order_items;
		}
		
		function get_months_list($amount_column = true){
			if(count($this->months) <= 0){
				$cross_tab_start_date			= $this->get_request('start_date',false);
				$cross_tab_end_date				= $this->get_request('end_date',false);
				
				$startDate = strtotime($cross_tab_start_date);
				$endDate   = strtotime($cross_tab_end_date);
				$currentDate = $startDate;
				$this->months = array();
				if($amount_column){					
					while ($currentDate <= $endDate) {
						$month = date('Y-m',$currentDate);
						$this->months[$month] = date('M',$currentDate);
						$currentDate = strtotime( date('Y/m/01/',$currentDate).' 1 month');
					}
				}else{
					while ($currentDate <= $endDate) {
						$month = date('Y-m',$currentDate);
						$this->months[$month."_total"] = date('M',$currentDate)." Amt.";
						$this->months[$month."_quantity"] = date('M',$currentDate)." Qty.";
						$currentDate = strtotime( date('Y/m/01/',$currentDate).' 1 month');
					}
				}
				
			}
			
			return $this->months;
		}
		
		
		function get_country_list($amount_column = true){
			global $wpdb,$sql;
			
			//$this->print_array($_REQUEST);

			$country_code 		= $this->get_request('country_code','-1',true);
			if($country_code != NULL  && $country_code != '-1')
					$country_code = str_replace(",", "','",$country_code);
			
			$sql = "SELECT 
			postmeta.meta_value AS 'id'
			,postmeta.meta_value AS 'label'
			
			FROM {$wpdb->prefix}postmeta as postmeta
			WHERE postmeta.meta_key='_billing_country'";
			
			
			if($country_code != NULL  && $country_code != '-1')
						$sql .= "	AND	postmeta.meta_value 	IN ('{$country_code}')";
							
			
			$sql .= " GROUP BY postmeta.meta_value ORDER BY postmeta.meta_value ASC";
			$wpdb->flush(); 				
			$wpdb->query("SET SQL_BIG_SELECTS=1");
			$results = $wpdb->get_results($sql);
			
			
			$country      	= $this->get_wc_countries();//Added 20150225
			$this->country = array();
			
			if($amount_column){			
				$array = array();				
				foreach($results as $key => $value):
						$v = isset($country->countries[$value->label]) ? $country->countries[$value->label]: $value->label;
						$array[$value->id] = $v;
				endforeach;	
			}else{
				foreach($results as $key => $value):
					$v = isset($country->countries[$value->label]) ? $country->countries[$value->label]: $value->label;
					$array[$value->label."_total"] = $v." Amt";
					$array[$value->label."_quantity"] = $v." Qty";
				endforeach;	
			}
			return $array;
		}
		
		function get_state_list($amount_column = true,$state_key = 'billing_state',$country_key = 'billing_country', $deliter = "-"){
			global $wpdb,$sql;
			
			$request = $this->get_all_request();
					extract($request);

			if(!isset($_REQUEST['new_status_code'])){				
				if($state_code != NULL  && $state_code != '-1'){
					$state_codes = explode(",",$state_code);
					$statecodes = array();
					foreach($state_codes as $key => $value){
						$v = explode("-", $value);
						$statecodes[] = $v[1];
					}
										
					$state_code = implode("','", $statecodes);
					$_REQUEST['new_status_code'] = $state_code;
				}
			}else{
				$state_code = $_REQUEST['new_status_code'];
			}
			
			
			if($country_code != NULL  && $country_code != '-1')
					$country_code = str_replace(",", "','",$country_code);
			
			$sql = "
					SELECT CONCAT(billing_country.meta_value,'{$deliter}', billing_by.meta_value) as id, billing_by.meta_value as label, billing_country.meta_value as billing_country ";
			$sql .= "
					FROM `{$wpdb->prefix}posts` AS posts
					LEFT JOIN {$wpdb->prefix}postmeta as billing_by ON billing_by.post_id=posts.ID";
			$sql .= " 
					LEFT JOIN {$wpdb->prefix}postmeta as billing_country ON billing_country.post_id=posts.ID";
			$sql .= "
					WHERE billing_by.meta_key='_{$state_key}' AND posts.post_type='shop_order'
					";
			$sql .= "
					AND billing_country.meta_key='_{$country_key}'";
			
			if($state_code != NULL  && $state_code != '-1')
						$sql .= "	AND	billing_by.meta_value 	IN ('{$state_code}')";
			
			if($country_code != NULL  && $country_code != '-1')
						$sql .= "	AND	billing_country.meta_value 	IN ('{$country_code}')";
							
			
			$sql .= " 
				GROUP BY billing_by.meta_value
				ORDER BY billing_by.meta_value ASC";
			
			$wpdb->flush(); 				
			$wpdb->query("SET SQL_BIG_SELECTS=1");
			$results = $wpdb->get_results($sql);
			
			$country      	= $this->get_wc_countries();//Added 20150225
			$this->country = array();
			
			if($amount_column){			
				$array = array();				
				foreach($results as $key => $value):
						$v = $this->get_state($value->billing_country, $value->label);
						$array[$value->id] = $v." (".$value->billing_country.")";
				endforeach;	
			}else{
				foreach($results as $key => $value):
					$v = $this->get_state($value->billing_country, $value->label);
					$array[$value->id."_total"] = $v."(".$value->billing_country.") Amt";
					$array[$value->id."_quantity"] = $v."(".$value->billing_country.") Qty";
				endforeach;	
			}			
			//$this->print_array($array);
			return $array;
		}		
		
		public function get_variation_data($post_type = 'shop_order', $post_status = 'no'){
				global $wpdb;
				
				$post_status = $this->get_request_default('post_status',$post_status,true);
				if($post_status == "yes") $post_status == 'publish';
				
				$sql = " 
					SELECT 
					woocommerce_order_itemmeta_product.meta_value			as id
					,woocommerce_order_items.order_item_name 				as label
					FROM 	   {$wpdb->prefix}woocommerce_order_items		as woocommerce_order_items						
					LEFT JOIN  {$wpdb->prefix}woocommerce_order_itemmeta 	as woocommerce_order_itemmeta_product 			ON woocommerce_order_itemmeta_product.order_item_id			=	woocommerce_order_items.order_item_id
					LEFT JOIN  {$wpdb->prefix}woocommerce_order_itemmeta 	as woocommerce_order_itemmeta_variation			ON woocommerce_order_itemmeta_variation.order_item_id 		= 	woocommerce_order_items.order_item_id
					LEFT JOIN  {$wpdb->prefix}posts 						as shop_order 									ON shop_order.id											=	woocommerce_order_items.order_id
				";
				
				$sql .= " 
					WHERE
					woocommerce_order_itemmeta_product.meta_key		=	'_product_id'
					AND woocommerce_order_items.order_item_type		=	'line_item'
					AND shop_order.post_type						=	'{$post_type}'
					AND woocommerce_order_itemmeta_variation.meta_key 	= '_variation_id'
					AND (woocommerce_order_itemmeta_variation.meta_value IS NOT NULL AND woocommerce_order_itemmeta_variation.meta_value > 0)						
				";	
				
				if($post_status == 'publish' || $post_status == 'trash')	$sql .= " AND posts.post_status = '".$post_status."'";				
				
				$sql .= " GROUP BY woocommerce_order_itemmeta_product.meta_value ORDER BY label ASC";
				
				$wpdb->flush(); 				
				$wpdb->query("SET SQL_BIG_SELECTS=1");
				$order_items = $wpdb->get_results($sql);
				return $order_items;
		}
		
		public function get_variation_category_data($taxonomy = 'product_cat', $post_status = 'no'){
				global $wpdb;
				
				$post_status = $this->get_request_default('post_status',$post_status,true);
				if($post_status == "yes") $post_status == 'publish';
				
				$post_where = ($taxonomy == "product_cat") ? "woocommerce_order_itemmeta_product.meta_value" : "woocommerce_order_items.order_id";
				
				$sql = " 
					SELECT 
					terms.term_id												as id
					,terms.name											as label
					FROM 	   {$wpdb->prefix}woocommerce_order_items		as woocommerce_order_items						
					LEFT JOIN  {$wpdb->prefix}woocommerce_order_itemmeta 	as woocommerce_order_itemmeta_product 			ON woocommerce_order_itemmeta_product.order_item_id			=	woocommerce_order_items.order_item_id
					LEFT JOIN  {$wpdb->prefix}woocommerce_order_itemmeta 	as woocommerce_order_itemmeta_variation			ON woocommerce_order_itemmeta_variation.order_item_id 		= 	woocommerce_order_items.order_item_id
					LEFT JOIN  {$wpdb->prefix}posts 						as shop_order 									ON shop_order.id											=	woocommerce_order_items.order_id
				";
				
				$sql .= " 
					LEFT JOIN  {$wpdb->prefix}term_relationships 			as term_relationships 							ON term_relationships.object_id		=	{$post_where}
					LEFT JOIN  {$wpdb->prefix}term_taxonomy 				as term_taxonomy 								ON term_taxonomy.term_taxonomy_id	=	term_relationships.term_taxonomy_id
					LEFT JOIN  {$wpdb->prefix}terms 						as terms 										ON terms.term_id					=	term_taxonomy.term_id";
				
				$sql .= " 
					WHERE
					woocommerce_order_itemmeta_product.meta_key		=	'_product_id'
					AND woocommerce_order_items.order_item_type		=	'line_item'
					AND shop_order.post_type						=	'shop_order'
					AND woocommerce_order_itemmeta_variation.meta_key 	= '_variation_id'
					AND (woocommerce_order_itemmeta_variation.meta_value IS NOT NULL AND woocommerce_order_itemmeta_variation.meta_value > 0)						
				";	
				
				if($post_status == 'publish' || $post_status == 'trash')	$sql .= " AND posts.post_status = '".$post_status."'";
				
				$sql .= " AND term_taxonomy.taxonomy LIKE('{$taxonomy}')";
				
				$sql .= " GROUP BY terms.name ORDER BY label ASC";
				
				$wpdb->flush(); 				
				$wpdb->query("SET SQL_BIG_SELECTS=1");
				$order_items = $wpdb->get_results($sql);
						
				return $order_items;
		}
		
		function export_to_csv_button($position = 'bottom',$summary = array()){
			global $request;
			//$admin_page 		= 	$this->get_request('page');
			//$admin_page 		= 	$this->get_request('admin_page');
			
			$admin_page			= $this->get_request('page',NULL);
			$admin_page			= $this->get_request('admin_page',$admin_page,true);
			
			//$admin_page_url 		= get_option('siteurl').'/wp-admin/admin.php';//Commented not work SSL admin site 20150212
			$admin_page_url 		= $this->constants['admin_page_url'];//Added SSL fix 20150212
			$mngpg 				= 	$admin_page_url.'?page='.$admin_page;
			$request			=	$request = $this->get_all_request();
			
			$request['count_generated']		=	1;
			
			foreach($summary as $key => $value):
				$request[$key]		=	$value;
			endforeach;
			
			//$this->print_array($request);
						
			$request_			=	$request;
			unset($request['action']);
			unset($request['page']);
			unset($request['p']);
			
			$logo_image 			= $this->get_setting('logo_image',$this->constants['plugin_options'], '');
			$report_title 			= $this->get_setting('report_title',$this->constants['plugin_options'], '');
			$company_name 			= $this->get_setting('company_name',$this->constants['plugin_options'], '');
							
			?>
            <div id="<?php echo $admin_page ;?>Export" class="RegisterDetailExport">
                <form id="<?php echo $admin_page."_".$position ;?>_form" class="<?php echo $admin_page ;?>_form ic_export_<?php echo $position ;?>_form" action="<?php echo $mngpg;?>" method="post">
                    <?php foreach($request as $key => $value):?>
                        <?php //echo $key;?><input type="hidden" name="<?php echo $key;?>" value="<?php echo $value;?>" />
                    <?php endforeach;?>
                    <input type="hidden" name="export_file_name" value="<?php echo $admin_page;?>" />
                    <input type="hidden" name="export_file_format" value="csv" />
                                        
                    <input type="submit" name="<?php echo $admin_page ;?>_export_csv" class="onformprocess csvicon" value="<?php _e('Export to CSV','icwoocommerce_textdomains'); ?>" data-format="csv" data-popupid="export_csv_popup" data-hiddenbox="popup_csv_hidden_fields" data-popupbutton="<?php _e('Export to CSV','icwoocommerce_textdomains'); ?>" data-title="<?php _e('Export to CSV - Additional Information','icwoocommerce_textdomains'); ?>" />
                    <input type="submit" name="<?php echo $admin_page ;?>_export_xls" class="onformprocess excelicon" value="<?php _e('Export to Excel','icwoocommerce_textdomains'); ?>" data-format="xls" data-popupid="export_csv_popup" data-hiddenbox="popup_csv_hidden_fields" data-popupbutton="<?php _e('Export to Excel','icwoocommerce_textdomains'); ?>" data-title="<?php _e('Export to Excel - Additional Information','icwoocommerce_textdomains'); ?>" />
                    <input type="button" name="<?php echo $admin_page ;?>_export_pdf" class="onformprocess open_popup pdficon" value="<?php _e('Export to PDF','icwoocommerce_textdomains'); ?>" data-format="pdf" data-popupid="export_pdf_popup" data-hiddenbox="popup_pdf_hidden_fields" data-popupbutton="<?php _e('Export to PDF','icwoocommerce_textdomains'); ?>" data-title="<?php _e('Export to PDF','icwoocommerce_textdomains'); ?>" />
                    <input type="button" name="<?php echo $admin_page ;?>_export_print" class="onformprocess open_popup printicon" value="<?php _e('Print','icwoocommerce_textdomains'); ?>"  data-format="print" data-popupid="export_print_popup" data-hiddenbox="popup_print_hidden_fields" data-popupbutton="<?php _e('Print','icwoocommerce_textdomains'); ?>" data-title="<?php _e('Print','icwoocommerce_textdomains'); ?>" data-form="form" />
                    
                </form>
                <?php if($position == "bottom"):?>
                <form id="search_order_pagination" class="search_order_pagination" action="<?php echo $mngpg;?>" method="post">
                    <?php foreach($request_ as $key => $value):?>
						<?php //echo $key;?><input type="hidden" name="<?php echo $key;?>" value="<?php echo $value;?>" />
                    <?php endforeach;?>
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
                    <input type="button" name="backtoprevious" value="<?php _e('Back to Previous','icwoocommerce_textdomains'); ?>"  class="backtoprevious onformprocess" onClick="back_to_previous();" />
                </div>
            <?php
		}
		
		function back_print_botton($position  = "bottom",$summary = array()){
			
			if($position  == "bottom"){
				//$total_row_amount	= $summary['total_row_amount'];
				//$total_row_count	= $summary['total_row_count'];
				
			?>
            	
            <?php }?>
                <div class="back_print_botton noPrint">
            		<input type="button" name="backtoprevious" value="<?php _e('Back to Previous','icwoocommerce_textdomains'); ?>"  class="onformprocess" onClick="back_to_detail();" />
                    <input type="button" name="backtoprevious" value="<?php _e('Print','icwoocommerce_textdomains'); ?>"  class="onformprocess" onClick="print_report();" />
                </div>
            <?php
		}
		
		
		function ic_commerce_custom_report_page_export_csv($export_file_format){
			$type					= 'all_row';
			$rows 					= $this->get_items('all_row');			
			$billing_information 	= $this->get_request('billing_information',0);
			$shipping_information 	= $this->get_request('shipping_information',0);
			$export_file_name 		= $this->get_request('export_file_name',"no");
			$report_name 			= $this->get_request('report_name','');
			$attr 					= $this->get_request('attr',array());
			
			$last_column = $columns 	= $this->get_columns();			
			$months 	= $this->get_crosstab_coulums(false);
			$columns 	= array_merge((array)$columns, (array)$months);
			
			$column_total = array(
				"product_total"		=>__("Total Amt.",'icwoocommerce_textdomains')
				,"product_quantity"	=>__("Total Qty.",'icwoocommerce_textdomains')
			);			
			$columns 	= array_merge((array)$columns, (array)$column_total);
			
			global $woocommerce;
			$export_rows 	= array();			
			$i 				= 0;			
			
			//$this->print_array($columns);
			//exit;
			
			//$this->print_array($rows);
			//exit;
			
			end($last_column);         // move the internal pointer to the end of the array
			$last_column = key($last_column); 
			
			
			if($report_name == "variation_cross_tab"){
				//$attributes					= $this->get_attributes_columns('selected');
				$attributes						= array();
				$_REQUEST['show_variation'] 	= 'variable';
				$_REQUEST['variation_column'] 	= '1';
				$_REQUEST['group_by'] 			= 'variation_id';
				$rows 							= $this->get_grid_items_variation($columns,$rows);
				
				$attrs		= array();
				foreach($attr as $key => $value):
					$attrs[] = $key;
				endforeach;
				
			}
			
			$total_value		= array();
			$variation 			= array();
			foreach ( $rows as $rkey => $rvalue ):
					$product_total		= 0;
					$quantity_total		= 0;
					$this_item_data		= $this->get_items($type,false,$rvalue->id);
					$item_id 			= $rvalue->id;
					$this->items_data	= $this_item_data;
					$order_item			= $rvalue ;
					
					//if($report_name == "variation_cross_tab")
						//$variation = $this->get_variation_column_separated($rvalue->order_item_id);
					
					foreach($columns as $key => $value):
						switch ($key) {
							case 'product_id':								
							case 'item_name':
							//case 'product_sku':
							case 'product_name':
							case 'country_name':
							case 'payment_method':
							case 'status_name':	
							case 'id':
							//case 'final_sku':
							case 'product_name':
								$export_rows[$i][$key] = $rvalue->$key;
								break;
							case "final_sku":
							case "product_sku":
								$export_rows[$i][$key] = $this->get_sku($order_item->order_item_id, $order_item->product_id);
								break;
							case 'amount_count':
								$export_rows[$i][$key] = 'Total Amount';
								break;
							case 'product_total':
								$export_rows[$i][$key] = $product_total;
								break;
							case 'product_quantity':
								$export_rows[$i][$key] = $quantity_total;
								break;
							
							case 'color':
							case 'size':
							case 'height':
							case 'manufuture':							
								if($report_name == "variation_cross_tab" and in_array($key, $attrs)){										
									$export_rows[$i][$key] = isset($variation[$key]) ? $variation[$key] : (isset($rvalue->$key) ? $rvalue->$key : "-");
								}else{
									$export_rows[$i][$key] = $rvalue->$key;
								}								
								break;							
							default:
								if($report_name == "variation_cross_tab" and in_array($key, $attrs)){
									$export_rows[$i][$key] = isset($variation[$key]) ? $variation[$key] : (isset($rvalue->$key) ? $rvalue->$key : "-");
								}else{
									$find_total   = '_total';
									$pos = strpos($key, $find_total);
									if ($pos === false) {										
										$find_total   = '_quantity';
										$pos = strpos($key, $find_total);
										if ($pos === false) {
											if(isset($rvalue->$key)){
												$export_rows[$i][$key] = $rvalue->$key;
											}else{
												$export_rows[$i][$key] = '--';
											}
										}										
									}else{
										
										$key_new = $key;
										$key_new = str_replace("_total","",$key_new);
										$key_new = str_replace("_quantity","",$key_new);
										
										if(count($this->items_data)>0)
											$amount_quantity = $this->get_amount_quantity($this_item_data, $key_new);
										else
											$amount_quantity = false;
											
										if ($amount_quantity){
											$_product_total = $amount_quantity->total;
											$_quantity_total = $amount_quantity->quantity;
											//if($_product_total > 0 || (($item_id == "_by_product" || $report_name == "product_cross_tab") && ($_product_total > 0 || $_quantity_total > 0))){
											if($_product_total == 0 
												   and ($item_id == "_order_total" 
													|| $item_id == "_order_tax"
													 || $item_id == "_order_discount"
													  || $item_id == "_cart_discount"
													   || $item_id == "_order_shipping"
														|| $item_id == "_order_shipping_tax")
													   ){
															$export_rows[$i][$key] =   0;
															$export_rows[$i][$key_new."_quantity"] = 0;
								
													}else if($_product_total > 0 || $_quantity_total > 0){
														
												$product_total = $product_total + $_product_total;
												$quantity_total = $quantity_total + $_quantity_total;
												
												
												if(isset($total_value[$key]['product_total'])){
													$total_value[$key]['product_total']		= $total_value[$key]['product_total'] + $_product_total;
													$total_value[$key_new."_quantity"]['quantity_total']	= $total_value[$key_new."_quantity"]['quantity_total']+ $_quantity_total;
												}else{
													$total_value[$key]['product_total']		= $_product_total;
													$total_value[$key_new."_quantity"]['quantity_total']	= $_quantity_total;
												}
												
												
												
												$export_rows[$i][$key] =  $_product_total;
												$export_rows[$i][$key_new."_quantity"] =  $_quantity_total;
											}else{
												$export_rows[$i][$key] =   0;
												$export_rows[$i][$key_new."_quantity"] = 0;
											}
										}else{
											$export_rows[$i][$key] =   0;
											$export_rows[$i][$key_new."_quantity"] = 0;
										}
									}
								}

								break;
						}
					endforeach;
					$i = $i + 1;
			endforeach;
			if($report_name != "summary_cross_tab"):
				$product_total = 0;
				$quantity_total = 0;
				foreach($columns as $key => $value):
					switch ($key) {
						case $last_column:
							$export_rows[$i][$key] = "Total";						
							break;
						case 'product_id':
						case 'amount_count':
						case 'product_sku':
						case 'id':
						case 'final_sku':
							$export_rows[$i][$key] = '';						
							break;
						case 'product_total':
							$export_rows[$i][$key] = $product_total;						
							break;
						case 'product_quantity':
							$export_rows[$i][$key] = $quantity_total;						
							break;
						default:
							if($report_name == "variation_cross_tab" and in_array($key, $attrs)){								
								$export_rows[$i][$key] = '';
							}else{
								$key_new = str_replace("_total","",$key);
							
								if(isset($total_value[$key]['product_total'])){
									$product_total = $product_total + $total_value[$key]['product_total'];
									$export_rows[$i][$key] = $total_value[$key]['product_total'];
								}else if(isset($total_value[$key]['quantity_total'])){
									$quantity_total = $quantity_total + $total_value[$key]['quantity_total'];
									$export_rows[$i][$key] = $total_value[$key]['quantity_total'];								
								}else {
									$export_rows[$i][$key] = '0';
								}
							}
							
							break;
					}
				endforeach;
			endif;
			$i = $i + 1;
			//$this->print_array($total_value);
			//$this->print_array($export_rows);
			//exit;
			$page_title 	= $this->get_request('page_title','');
			$from_date 		= $this->get_request('start_date','');
			$to_date 		= $this->get_request('end_date','');
			
			$from_date = date("F Y", strtotime($from_date));
			$to_date = date("F Y", strtotime($to_date));
			
			$report_title = $page_title . " From " .  $from_date . " To " . $to_date;
			
		
			/*$today = date_i18n("Y-m-d-H-i-s");				
			$FileName = $export_file_name."-".$today.".".$export_file_format;	
			$out = $this->ExportToCsv($FileName,$export_rows,$columns,$export_file_format);*/
			
			/*New Added 20160130*/
			$today_date 		= date_i18n("Y-m-d-H-i-s");				
			$export_filename 	= $export_file_name."-".$report_name."-".$today_date.".".$export_file_format;
			$export_filename 	= apply_filters('ic_commerce_export_csv_excel_format_file_name',$export_filename,$report_name,$today_date,$export_file_name,$export_file_format);
			do_action("ic_commerce_export_csv_excel_format",$export_filename,$export_rows,$columns,$export_file_format,$report_name);
			$out = $this->ExportToCsv($export_filename,$export_rows,$columns,$export_file_format,$report_name);
			
			//$this->print_array($out);
			//exit;
			
			$format		= $export_file_format;
			$filename	= $export_filename;
			if($format=="csv"){
				header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
				header("Content-Length: " . strlen($out));	
				header("Content-type: text/x-csv");
				header("Content-type: text/csv");
				header("Content-type: application/csv");
				header("Content-Disposition: attachment; filename=$filename");
			}elseif($format=="xls"){
				
				header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
				header("Content-Length: " . strlen($out));
				header("Content-type: application/octet-stream");
				header("Content-Disposition: attachment; filename=$filename");
				header("Pragma: no-cache");
				header("Expires: 0");
			}
			//echo '"'.$report_title.'"';
			//echo "\n";
			echo $out;
			//echo "\n";
			//echo $report_title;
			//echo "\n";
			exit;
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
			
			return $out;
		 
		}
		function ic_commerce_custom_report_page_export_pdf($export_file_format = "pdf"){
			$type					= 'all_row';
			$rows 					= $this->get_items('all_row');			
			
			$export_file_name 		= $this->get_request('export_file_name',"no");
			$orientation_pdf 		= $this->get_request('orientation_pdf',"portrait");
			$paper_size 			= $this->get_request('paper_size',"letter");
			$report_name 			= $this->get_request('report_name','');
			$attr 					= $this->get_request('attr',array());
			
			$last_column = $columns 	= $this->get_columns();			
			$months 	= $this->get_crosstab_coulums(true);
			$columns 	= array_merge((array)$columns, (array)$months);
			
			$column_total = array(
				"product_total"	=>__("Total Amt.",'icwoocommerce_textdomains')
				//,"product_quantity"	=>__("Total Qty.",'icwoocommerce_textdomains')
			);			
			$columns 	= array_merge((array)$columns, (array)$column_total);
			
			end($last_column);         // move the internal pointer to the end of the array
			$last_column = key($last_column); 
			
			global $woocommerce;
			$export_rows 	= array();			
			$i 				= 0;
			
			if($report_name == "variation_cross_tab"){
				//$attributes					= $this->get_attributes_columns('selected');
				$attributes						= array();
				$_REQUEST['show_variation'] 	= 'variable';
				$_REQUEST['variation_column'] 	= '1';
				$_REQUEST['group_by'] 			= 'variation_id';
				$rows 							= $this->get_grid_items_variation($columns,$rows);
				$attrs		= array();
				foreach($attr as $key => $value):
					$attrs[] = $key;
				endforeach;	
			}
			
			$zero 				= $this->price(0);
			$total_value		= array();
			
			//$this->print_array($rows);
			//exit;
			$variation 			= array();
			foreach ( $rows as $rkey => $rvalue ):
				$product_total		= 0;
				$quantity_total		= 0;
				$this_item_data		= $this->get_items($type,false,$rvalue->id);
				$item_id 			= $rvalue->id;
				$this->items_data	= $this_item_data;
				$order_item			= $rvalue ;
				
				//if($report_name == "variation_cross_tab")
						//$variation = $this->get_variation_column_separated($rvalue->order_item_id);
				
				foreach($columns as $key => $value):
					switch ($key) {
						case 'item_name':
						//case 'product_sku':
						case 'product_name':
						case 'country_name':
						case 'payment_method':
						case 'status_name':	
						case 'id':
						//case 'final_sku':
						case 'product_name':
							$export_rows[$i][$key] =  $rvalue->$key;
							break;
						case "final_sku":
						case "product_sku":
							$export_rows[$i][$key] = $this->get_sku($order_item->order_item_id, $order_item->product_id);
							break;
						case 'product_total':
							$td_value 				=  isset($product_total) ? $product_total : 0;
							$td_value				=  $td_value > 0 ? $this->price($td_value) : $zero;							
							$export_rows[$i][$key]	=  $td_value ." # ".  $quantity_total;
							//$export_rows[$i][$key] = $this->price($product_total) ." # ".  $quantity_total;
							break;
						case 'color':
						case 'size':
						case 'height':
						case 'manufuture':							
							if($report_name == "variation_cross_tab" and in_array($key, $attrs)){										
								$export_rows[$i][$key] = isset($variation[$key]) ? $variation[$key] : (isset($rvalue->$key) ? $rvalue->$key : "-");
							}else{
								$export_rows[$i][$key] = $rvalue->$key;
							}								
							break;										
						default:
								//echo $key;
								
									$v  = '';
									if($report_name == "variation_cross_tab" and in_array($key, $attrs)){
										$v = isset($variation[$key]) ? $variation[$key] : (isset($rvalue->$key) ? $rvalue->$key : "-");
									}else{
										$v  = $zero;
										if(count($this->items_data)>0)
											$amount_quantity = $this->get_amount_quantity($this_item_data, $key);
										else
											$amount_quantity = false;
											
										if ($amount_quantity){
											$_product_total = $amount_quantity->total;
											$_quantity_total = $amount_quantity->quantity;
											//if($_product_total > 0 || (($item_id == "_by_product" || $report_name == "product_cross_tab") && ($_product_total > 0 || $_quantity_total > 0))){
												if($_product_total == 0 
												   and ($item_id == "_order_total" 
													|| $item_id == "_order_tax"
													 || $item_id == "_order_discount"
													  || $item_id == "_cart_discount"
													   || $item_id == "_order_shipping"
														|| $item_id == "_order_shipping_tax")
													   ){
													
													}else if($_product_total > 0 || $_quantity_total > 0){
												$product_total = $product_total + $_product_total;
												$quantity_total = $quantity_total + $_quantity_total;
												
												
												if(isset($total_value[$key]['product_total'])){
													$total_value[$key]['product_total']		= $total_value[$key]['product_total'] + $_product_total;
													$total_value[$key]['quantity_total']	= $total_value[$key]['quantity_total']+ $_quantity_total;
												}else{
													$total_value[$key]['product_total']		= $_product_total;
													$total_value[$key]['quantity_total']	= $_quantity_total;
												}
												
												$td_value 				=  isset($_product_total) ? $_product_total : 0;
												$td_value				=  $td_value > 0 ? $this->price($td_value) : $zero;	
							
												$v 						=  $td_value ." # ".  $_quantity_total;
												//$v 					=  $this->price($_product_total) ." # ".  $_quantity_total;
											}else{											
											
											}
											
										}else{
											if(isset($rvalue->$key)){
												$v =  $rvalue->$key;
											}
										}
									}
								
								$export_rows[$i][$key] =  $v;
							break;
					}
				endforeach;
				$i++;
			endforeach;
			if($report_name != "summary_cross_tab"):
				$product_total = 0;
				$quantity_total = 0;
				foreach($columns as $key => $value):
					switch ($key) {
						case $last_column:
							$export_rows[$i][$key] = "<strong>Total</strong>";						
							break;
						case 'product_id':
						case 'amount_count':
						case 'product_sku':
						case 'id':
						case 'final_sku':
						case 'product_name':
							$export_rows[$i][$key] = '';						
							break;
						case 'product_total':
							$td_value 				=  isset($product_total) ? $product_total : 0;
							$td_value				=  $td_value > 0 ? $this->price($td_value) : $zero;	
							$export_rows[$i][$key]	= 	$td_value ." # ".  $quantity_total;
												
							//$export_rows[$i][$key] = $this->price($product_total) ." # ".  $quantity_total;
							break;
						case 'product_quantity':
							$export_rows[$i][$key] = $quantity_total;
							break;
						default:
							if($report_name == "variation_cross_tab" and in_array($key, $attrs)){								
								$export_rows[$i][$key] = '';
							}else{
								$key_new = str_replace("_total","",$key);
								
								if(isset($total_value[$key]['product_total'])){
									$product_total = $product_total + $total_value[$key]['product_total'];
									$quantity_total = $quantity_total + $total_value[$key]['quantity_total'];
									
									
									$td_value 				=  isset($total_value[$key]['product_total']) ? $total_value[$key]['product_total'] : 0;
									$td_value				=  $td_value > 0 ? $this->price($td_value) : $zero;	
									$export_rows[$i][$key] 	= $td_value ." # ".  $total_value[$key]['quantity_total'];
									
									//$export_rows[$i][$key] = $this->price($total_value[$key]['product_total']) ." # ".  $total_value[$key]['quantity_total'];
								}else {
									$export_rows[$i][$key] = $zero;
								}
							}
							break;
					}
				endforeach;
			endif;
			
			$output = $this->GetDataGrid($export_rows,$columns);			
			$this->export_to_pdf($export_rows,$output);
		}
		
		function GetDataGrid($rows=array(),$columns=array(),$summary=array(),$price_columns=array(),$total_columns = array()){
			global $wpdb;
			$csv_terminated = "\n";
			$csv_separator = ",";
			$csv_enclosed = '"';
			$csv_escaped = "\\";
			$fields_cnt = count($columns); 
			$schema_insert = '';
			
			$th_open = '<th class="#class#">';
			$th_close = '</th>';
			
			$td_open = '<td class="#class#">';
			$td_close = '</td>';
			
			$tr_open = '<tr>';
			$tr_close = '</tr>';
			
			$amount_column 	= $this->get_crosstab_coulums();
			$report_name 		= $this->get_request('report_name');
			
			if($report_name == "product_bill_country_crosstab" || $report_name == "product_bill_state_crosstab"){
			
				foreach($columns as $key => $value):
					$l = str_replace("#class#",$key,$th_open) . str_replace($csv_enclosed, $csv_escaped . $csv_enclosed, $value) . $th_close;
					$schema_insert .= $l;				
				endforeach;// end for
				
				$schema_insert = str_replace("-","_",$schema_insert);
			}else{
				foreach($columns as $key => $value):
					$l = str_replace("#class#",$value,$th_open) . str_replace($csv_enclosed, $csv_escaped . $csv_enclosed, $value) . $th_close;
					$schema_insert .= $l;				
				endforeach;// end for
				
			}
			
			//New Change ID 20140918
			$company_name	= $this->get_request('company_name','');
			$report_title	= $this->get_request('report_title','');
			$display_logo	= $this->get_request('display_logo','');
			$display_date	= $this->get_request('display_date','');
			$display_center	= $this->get_request('display_center','');
			$keywords		   = $this->get_request('pdf_keywords','');
			$description	 	= $this->get_request('pdf_description','');
			
			$amount__columns = array_merge($price_columns, $total_columns);
			$pdf_special_font = $this->get_pdf_special_font($amount__columns);
			//New Change ID 20140918
			$out ='<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd"><html xmlns="http://www.w3.org/1999/xhtml"><meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
					<title>'.$report_title.'</title>
						<meta name="description" content="'.$description.'" />
						<meta name="keywords" content="'.$keywords.'" />
						<meta name="author" content="'.$company_name.'" />
						<style type="text/css"><!--
						'.$pdf_special_font.'
						.header {position: fixed; top: -40px; text-align:center;}
						.footer { position: fixed; bottom: 0px; text-align:center;}
						.pagenum:before { content: counter(page); }
						span{font-weight:bold;}
						.Clear{clear:both; margin-bottom:10px;}						
						.sTable3{border:1px solid #DFDFDF;}
						.sTable3 th{
							padding:10px 10px 7px 10px;
							background:#eee url(../images/thead.png) repeat-x top left;
						}
						.Form{padding:1% 1% 11% 1%; margin:5px 5px 5px 5px;}
						.myclass{border:1px solid black;}
						
						.sTable3 tbody tr td{padding:8px 10px; background:#fff; border-top:1px solid #DFDFDF; border-right:1px solid #DFDFDF;}
						.sTable3 tbody tr.AltRow td{background:#FBFBFB;}
						.print_header_logo.center_header, .header.center_header{margin:auto;  text-align:center;}
						
						.td_pdf_amount span{ text-align:right; display:block}
						th.product_total, th.Total, th.product_total, .td_pdf_amount{ text-align:right;}';
					
					
					if($report_name == "product_bill_country_crosstab" || $report_name == "product_bill_state_crosstab"){
						$c = array();
						foreach($amount_column as $key => $value):
							if(strlen($key)>0)
							$c[] = $key;
						endforeach;
						
						$css = "td." . implode(", td.",$c)."{text-align:right;}";
						$css .= ".sTable3 th." . implode(", .sTable3 th.",$c)."{text-align:right;}";
						
						$out .= str_replace("-","_",$css);
					}else{
						$out .= "td." . implode(", td.",$amount_column)."{text-align:right;}";
						$out .= "th." . implode(", th.",$amount_column)."{text-align:right;}";
					}
					
					
					
				
			$out .='-->
					</style>
					</head>
					<body>';
			
			
			
			$logo_html		=	"";
			
			if(strlen($display_logo) > 0){
				$company_logo	=	$logo_image 			= $this->get_setting('logo_image',$this->constants['plugin_options'], '');
				$upload_dir 	= wp_upload_dir(); // Array of key => value pairs
				$company_logo	= str_replace($upload_dir['baseurl'],$upload_dir['basedir'],$company_logo);
				//$logo_html 		= "<div class='Clear'><img src='".$company_logo."' alt='' /><span>".$company_name."</span></div>";
				$logo_html 		= "<div class='Clear  print_header_logo ".$display_center."'><img src='".$company_logo."' alt='' /></div>";
			}else{
				//$logo_html 		= "<div class='Clear'><span>".$company_name."</span></div>";
			}
			if(strlen($company_name) > 0)	$out .="<div class='header ".$display_center."'><h2>".stripslashes($company_name)."</h2></div>";			
			$out .="<div class='footer'>Page: <span class='pagenum'></span></div>";
			$out .= "<div class='Container1'>";
			$out .= "<div class='Form1'>";
			$out .= $logo_html;
			
			if(strlen($company_name) > 0 || strlen($display_logo) > 0)
			$out .= "<hr class='myclass1'>";
			
			
			
			
			if(strlen($report_title) > 0)
				$out .= "<div class='Clear'><label>Report Title: </label><label>".stripslashes($report_title)."</label></div>";
			
			$out .= "<div class='Clear'></div>";
			if($display_date) $out .= "<div class='Clear'><label>Date: </label><label>".date_i18n('Y-m-d')."</label></div>";
			
			$out .= "<div class='Clear'></div>";
			$out .= "<div class='Clear'>";			
			$out .= "<table class='sTable3' cellpadding='0' cellspacing='0' width='100%'>";
			$out .= "<thead>";
			$out .= $tr_open;			
			$out .= trim(substr($schema_insert, 0, -1));
			$out .= $tr_close;
			$out .= "</thead>";			
			$out .= "<tbody>";			
			$out .= $csv_terminated;
			
				
			
			$last_order_id = 0;
			$alt_order_id = 0; 
			for($i =0;$i<count($rows);$i++){			
				$j = 0;
				$schema_insert = '';
				foreach($columns as $key => $value){
						 if ($rows[$i][$key] == '0' || $rows[$i][$key] != ''){
							if ($csv_enclosed == '')
							{
								$schema_insert .= $rows[$i][$key];
							} else
							{	
							
									switch($key){
										case 'item_name':
										case 'product_sku':
										case 'product_name':
										case 'country_name':
										case 'payment_method':
										case 'status_name':	
										case 'id':
										case 'final_sku':
										case 'product_name':
										case 'color':
										case 'size':
										case 'height':
										case 'manufuture':
										case 'project':
										case 'card':
										case 'giftcard':
											$schema_insert .= str_replace("#class#",$key,$td_open) . str_replace($csv_enclosed, $csv_escaped . $csv_enclosed, $rows[$i][$key]) . $td_close;	
											break;
										case "product_total":
											$schema_insert .= str_replace("#class#",'td_pdf_amount',$td_open) . str_replace($csv_enclosed, $csv_escaped . $csv_enclosed, $rows[$i][$key]) . $td_close;	
											break;
										default:
											$schema_insert .= str_replace("#class#",'td_pdf_amount',$td_open) . str_replace($csv_enclosed, $csv_escaped . $csv_enclosed, $rows[$i][$key]) . $td_close;	
											break;
									}
									
								//else
									//$schema_insert .= str_replace("#class#","td_pdf_".$key,$td_open) . str_replace($csv_enclosed, $csv_escaped . $csv_enclosed, $rows[$i][$key]) . $td_close;
							}
							
						 }else{
							$schema_insert .= $td_open.''.$td_close;;
						 }
						$j++;
				}				
				$out .= $tr_open;
				$out .= $schema_insert;
				$out .= $tr_close;			
			}

			$out .= "</tbody>";
			$out .= "</table>";	
			$out .= "</div></div></div></body>";			
			$out .="</html>";	
			return  $out;
		}
		
		function get_all_request(){
			global $request, $back_day;
			if(!$this->request){
				
				do_action('ic_commerce_crosstab_page_before_default_request');
				
				$request 			= array();
				$start				= 0;
				
				$limit 				= $this->get_request('limit',3,true);
				$p 					= $this->get_request('p',1,true);
				$page				= $this->get_request('page','',true);
				$order_status_id	= $this->get_request('order_status_id','-1',true);
				$order_status		= $this->get_request('order_status','-1',true);
				$category_id		= $this->get_request('category_id','-1',true);
				$product_id			= $this->get_request('product_id','-1',true);
				$country_code 		= $this->get_request('country_code','-1',true);
				$state_code			= $this->get_request('state_code','-1',true);
				
				$sort_by 			= $this->get_request('sort_by','item_name',true);
				$order_by 			= $this->get_request('order_by','ASC',true);
				
				$end_date			= $this->get_request('end_date',false);
				$start_date			= $this->get_request('start_date',false);
				$report_name		= $this->get_request('report_name',false);
				
				$time_start_date	= strtotime($start_date);
				$time_end_date		= strtotime($end_date);
				
				$ctime_end_year 		= strtotime('+ 1 year', $time_start_date);
				
				if($time_end_date >$ctime_end_year){
					$time_end_date	= $ctime_end_year;
					$_REQUEST['end_date'] = date('Y-m-d',$ctime_end_year);
				}
				
				$start_date			= $this->get_request('cross_tab_start_date',date('Y-m',$time_start_date),true);
				$end_date			= $this->get_request('cross_tab_end_date',date('Y-m',$time_end_date),true);
				
				$this->common_request_form();
				
				if($report_name == "variation_cross_tab"){
					$variations			= $this->get_request('variations','-1',true);
					
					if($variations != '-1' and strlen($variations) > 0){
							$variations = $_REQUEST['variations'];
							$variations = explode(",",$variations);
							//$this->print_array($variations);
							$var = array();
							foreach($variations as $key => $value):
								//$var[] .=  "attribute_pa_".$value;
								$var[] .=  "attribute_".$value;
							endforeach;
							$variations =  implode("', '",$var);
					}
					
					$_REQUEST['variation_attributes']= $variations;
				}
				
				if($p > 1){	$start = ($p - 1) * $limit;}
				
				$_REQUEST['start']= $start;
			
				
				if(isset($_REQUEST)){
					foreach($_REQUEST as $key => $value ):					
						$v =  $this->get_request($key,NULL);
						$request[$key]		= $v;
					endforeach;
				}
				$this->request = $request;				
			}else{				
				$request = $this->request;
			}
			
			return $request;
		}
		
		function _get_setting($id, $data, $defalut = NULL){
			if(isset($data[$id]))
				return $data[$id];
			else
				return $defalut;
		}
		
		function print_header($type = NULL, $report_title = NULL){
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
		
	}
}
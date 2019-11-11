<?php
if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! class_exists( 'IC_Commerce_Premium_Golden_All_Report')){
	class IC_Commerce_Premium_Golden_All_Report extends IC_Commerce_Premium_Golden_Fuctions{
		
		public $per_page = 0;	
		
		public $per_page_default = 10;
		
		public $request_data =	array();
		
		public $constants 	=	array();
		
		public $request		=	array();
		
		public $today 		=	'';
		
		public function __construct($constants) {
			global $options;
			
			$this->constants		= $constants;			
			$options				= $this->constants['plugin_options'];			
			$this->per_page			= $this->constants['per_page_default'];
			$this->per_page_default	= $this->constants['per_page_default'];			
			$per_page 				= (isset($options['per_apge']) and strlen($options['per_apge']) > 0)? $options['per_apge'] : $this->per_page_default;
			$this->per_page 		= is_numeric($per_page) ? $per_page : $this->per_page_default;
			$this->today			= $this->constants['today_date'];//New Change ID 20140918
			$this->is_active();
			
			add_filter('ic_commerce_report_page_data_items', array($this, 'ic_commerce_report_page_data_items'),11,5);
			add_filter('ic_commerce_report_page_default_sort_by', 	array($this, 'get_default_sort_by'),11,2);
		}
		
		
		
		function init(){
				global $back_day,$report_title, $wpdb;
				
				if(!isset($_REQUEST['page'])){return false;}
				
				if ( !current_user_can( $this->constants['plugin_role'] ) )  {
					wp_die( __( 'You do not have sufficient permissions to access this page.' ,'icwoocommerce_textdomains' ) );
				}
				
				do_action('ic_commerce_current_user_can',$_REQUEST['page'],$this->constants);
				
				//New Change ID 20140918
				$shop_order_status			= $this->get_set_status_ids();	
				$hide_order_status			= $this->constants['hide_order_status'];
				$hide_order_status			= implode(",",$hide_order_status);
				
				$order_status_id 			= "";
				$order_status 				= "";
				
				if($this->constants['post_order_status_found'] == 0 ){
					$order_status_id 		= implode(",",$shop_order_status);
				}else{
					$order_status_id 		= "";
					$order_status 			= implode(",",$shop_order_status);
				}
				
				$order_status				= strlen($order_status) > 0 		?  $order_status 		: '-1';
				$order_status_id			= strlen($order_status_id) > 0 		?  $order_status_id 	: '-1';
				$hide_order_status			= strlen($hide_order_status) > 0 	?  $hide_order_status 	: '-1';
				
				
				$product_status 			= $this->get_setting('product_status',$this->constants['plugin_options'], array());				
				$product_status				= implode("', '",$product_status);				
				$product_status				= strlen($product_status) > 0 ?  $product_status 	: '-1';
				
				$this->constants['start_date'] = date_i18n('Y-m-01');
				
				$default_tab 				= apply_filters('ic_commerce_report_page_default_tab', 	'product_page');
				$report_name 				= $this->get_request('report_name',$default_tab,true);
				$start_date 				= apply_filters('ic_commerce_report_page_start_date',	$this->constants['start_date'],$report_name);
				$end_date 					= apply_filters('ic_commerce_report_page_end_date',		$this->constants['end_date'],$report_name);
				$order_status				= apply_filters('ic_commerce_report_page_selected_order_status', $order_status,$report_name);
				$product_status				= apply_filters('ic_commerce_report_page_selected_product_status', $product_status,$report_name);
				$onload_search				= apply_filters('ic_commerce_report_page_onload_search', "yes", $report_name);
				$onload_search				= apply_filters('ic_commerce_onload_search', $onload_search, $report_name);
				
				$publish_order				= "no";
				
				$page						= $this->get_request('page',NULL);
				$optionsid					= "per_row_all_report_page";
				$per_page 					= $this->get_number_only($optionsid,$this->per_page_default);
				$admin_page					= $this->get_request('admin_page',$page,true);
				$adjacents					= $this->get_request('adjacents','3',true);
				$p							= $this->get_request('p','1',true);
				$limit						= $this->get_request('limit',$per_page,true);
				if($this->is_product_active != 1)  return true;	
				$end_date					= $this->get_request('end_date',$end_date);
				$start_date					= $this->get_request('start_date',$start_date);
				//$MinPrice					= $this->get_request('min_price',$min_price);
				//$MaxPrice					= $this->get_request('max_price',$max_price);
				$category_id				= $this->get_request('category_id','-1',true);
				$order_status_id			= $this->get_request('order_status_id',$order_status_id,true);//New Change ID 20140918
				$order_status				= $this->get_request('order_status',$order_status,true);//New Change ID 20140918
				$publish_order				= $this->get_request('publish_order',$publish_order,true);//New Change ID 20140918
				$hide_order_status			= $this->get_request('hide_order_status',$hide_order_status,true);//New Change ID 20140918
				$product_id					= $this->get_request('product_id','-1',true);
				
				$action						= $this->get_request('action',$this->constants['plugin_key'].'_wp_ajax_action',true);
				$do_action_type				= $this->get_request('do_action_type','report_page',true);
				$count_generated			= $this->get_request('count_generated',0,true);				
				$country_code				= $this->get_request('country_code','-1',true);
				$product_status				= $this->get_request('product_status',$product_status,true);
				
				$first_date 				= $this->constants['first_order_date'];
				
				if(!$end_date){$end_date 		= date_i18n('Y-m-d');}
				if(!$start_date){$start_date 	= $first_date;}
				
				$_REQUEST['end_date']		= $end_date;
				$_REQUEST['start_date'] 	= $start_date;
				$_REQUEST['page_name'] 		= 'all_detail';
				$_REQUEST['page_title'] 	= "";
				
				$billing_or_shipping		= $this->get_setting('billing_or_shipping',$this->constants['plugin_options'], 'billing');
				$page_titles				= $this->get_page_titles($report_name,$this->constants['plugin_options']);
				$page_title 				= isset($page_titles[$report_name]) ? $page_titles[$report_name] : ucfirst(str_replace("page","",str_replace("_"," ",$report_name)));
				$page_title 				= apply_filters('ic_commerce_report_page_title',$page_title, $report_name);				
				$_REQUEST['page_title'] 	= $page_title;
				$child_categories_count 	= 0;
				$parent_categories_count	= 0;
				
				
					
				?>
                <h2 class="hide_for_print"><?php echo $page_title;?></h2>
                                
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
                               	
                <br>
                <?php if($report_name != 'coupon_page_archive'):?>
                <div id="navigation" class="hide_for_print">
                        <div class="collapsible" id="section1"><?php _e('Custom Search','icwoocommerce_textdomains');?><span></span></div>
                        <div class="container">
                            <div class="content">
                                <div class="search_report_form">
                                    <div class="form_process"></div>
                                    <form action="" name="Report" id="search_order_report" method="post">
                                        <div class="form-table">
                                        	 <?php
                                             	$no_date_fields_tabs = apply_filters('ic_commerce_report_page_no_date_fields_tabs',array(),$report_name);
												if(!in_array($report_name,$no_date_fields_tabs)):
											 ?>
                                            <div class="form-group">
                                                <div class="FormRow FirstRow">
                                                    <div class="label-text"><label for="start_date"><?php _e("From Date:",'icwoocommerce_textdomains'); ?></label></div>
                                                    <div class="input-text"><input type="text" value="<?php echo $start_date;?>" id="start_date" name="start_date" maxlength="10" /></div>
                                                </div>
                                                <div class="FormRow">
                                                    <div class="label-text"><label for="end_date"><?php _e("To Date:",'icwoocommerce_textdomains'); ?></label></div>
                                                    <div class="input-text"><input type="text" value="<?php echo $end_date;?>" id="end_date" name="end_date" maxlength="10" /></div>
                                                </div>
                                            </div>
											
                                            <?php endif;?>
                                            <?php  do_action("ic_commerce_report_page_search_form_below_date_fields", $report_name, $this );?>
                                            <?php 											
											$category_product_fields_tabs = apply_filters('ic_commerce_report_page_category_product_fields_tabs',array("product_page"),$report_name);
											if(in_array($report_name,$category_product_fields_tabs)):?>
                                            <div class="form-group">
                                                <div class="FormRow FirstRow">
                                                    <div class="label-text"><label for="category_id2"><?php _e("Category:",'icwoocommerce_textdomains'); ?></label></div>
                                                    <div class="input-text">
                                                        <?php 
                                                           	$category_data = $this->get_category_data();
                                                            $this->create_dropdown($category_data,"category_id[]","category_id2","Select All","category_id2",$category_id, 'object', true, 5);
                                                        ?>                                                        
                                                    </div>                                                    
                                                </div>
                                                <div class="FormRow">
                                                    <div class="label-text"><label for="product_id"><?php _e("Product:",'icwoocommerce_textdomains'); ?></label></div>
                                                    <div class="input-text">
                                                        <?php 
                                                            $product_data = $this->get_product_data('all');
                                                            $this->create_dropdown($product_data,"product_id[]","product_id2","Select All","product_id2",$product_id, 'object', true, 5);
                                                        ?>
                                                    </div>                                                    
                                                </div>                                                
                                            </div>                                  
											<?php endif;?>
                                             <?php
                                             	$status_field_tabs = apply_filters('ic_commerce_report_page_status_field_tabs',array("product_page",'manual_refund_detail_page'),$report_name);
												if(in_array($report_name,$status_field_tabs)):
											 ?>
                                             <div class="form-group">
                                             	<div class="FormRow FirstRow">
                                                    <div class="label-text"><label for="order_status_id2"><?php _e("Status:",'icwoocommerce_textdomains'); ?></label></div>
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
                                             <?php
												else:
													$not_status_field_tabs = apply_filters('ic_commerce_report_page_not_status_field_tabs',array(),$report_name);
													if(!in_array($report_name,$not_status_field_tabs)):
														echo '<input type="hidden" name="order_status_id[]" id="order_status_id" value="'.$order_status_id.'">';
														echo '<input type="hidden" name="order_status[]" id="order_status" value="'.$order_status.'">';
													endif;													
												endif;
											 ?>
                                             
                                            <?php if($report_name == 'category_page')://New Custom Change ID 20141208
												$parent_categories = $this->get_sold_product_parent_category_data();
												if(count($parent_categories)>0){
												?>   
                                            	<div class="form-group">                                                
                                                    <div class="FormRow FirstRow">
                                                        <div class="label-text"><label for="parent_category_id"><?php _e("Parent Category:",'icwoocommerce_textdomains'); ?></label></div>
                                                        <div class="input-text">
                                                            <?php
                                                                //New Change ID 20141208
                                                                $this->create_dropdown($parent_categories,"parent_category_id[]","parent_category_id","Select All","parent_category_id",'-1', 'object', TRUE, 5);
                                                            ?>
                                                        </div>
                                                    </div>                                          
                                                </div>
                                                <?php }?>  
                                            <?php endif;?>
                                            
                                            <?php if($report_name == 'manual_refund_detail_page'):?>
                                            	<div class="form-group">                                                	
                                                    <div class="FormRow">
                                                        <div class="label-text"><label for="refund_status_type"><?php _e("Refund Type:",'icwoocommerce_textdomains');?></label></div>
                                                        <div class="input-text">
                                                            <?php 
                                                                $refund_status_type = 'status_refunded';
                                                                $groups = array(
																	"part_refunded" 	=> __("Part Refund - Order status not refunded",'icwoocommerce_textdomains'),
																	"status_refunded" 	=> __("Order Status Refunded",'icwoocommerce_textdomains')
																	//,"total_refunded" 	=> __("Total Refunded",'icwoocommerce_textdomains')
																);
                                                                $this->create_dropdown($groups,"refund_status_type[]","refund_status_type","","refund_status_type",$refund_status_type, 'array', false, 5);
                                                            ?>                                                        
                                                        </div>                                                    
                                                    </div>
                                                </div>
                                                
                                                <div class="form-group">
                                                    <div class="FormRow checkbox FirstRow">
                                                        <div class="label-text"><label for="show_refund_note"><?php _e("Show Refund Note:",'icwoocommerce_textdomains');?></label></div>
                                                        <div style="padding-top:5px;"><input type="checkbox" id="show_refund_note" name="show_refund_note"  value="yes" <?php if($this->get_request('show_refund_note','no',true) == "yes"){ echo ' checked="checked"';}?> /></div>
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
                                            <?php endif;?>
                                             <?php 
											 if(in_array($report_name,apply_filters('ic_commerce_report_page_coupon_code_field_tabs',array("coupon_page"),$report_name))):
											 ?>
                                            	<div class="form-group">
                                                    <div class="FormRow checkbox FirstRow">
                                                        <div class="label-text"><label for="coupon_codes"><?php _e("Coupon Codes:",'icwoocommerce_textdomains');?></label></div>
                                                        <div class="input-text">
                                                        	<?php
																$coupon_code_data = $this->get_coupon_codes();
																$this->create_dropdown($coupon_code_data,"coupon_codes[]","coupon_codes","Select All","coupon_codes",'-1', 'object', true, 5);
                                                            ?>  
                                                        </div>
                                                    </div>
                                                    <div class="FormRow checkbox">
                                                        <div class="label-text"><label for="coupon_discount_types"><?php _e("Discount Type:",'icwoocommerce_textdomains');?></label></div>
                                                        <div class="input-text">
                                                        	<?php
																$coupon_discount_type_data = $this->get_coupon_types();
																$this->create_dropdown($coupon_discount_type_data,"coupon_discount_types[]","coupon_discount_types","Select One","coupon_discount_types",'-1', 'array', false, 5);
                                                            ?>  
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endif;?>
                                            
                                            
											
                                            <?php do_action("ic_commerce_report_page_search_form_bottom",$report_name, $this );?>
                                            <?php
                                            	$sorting_list 	= $this->get_sort_by($report_name);
												if(count($sorting_list) > 0){
											?>
                                            <div class="form-group">
                                                <div class="FormRow FirstRow">
                                                    <div class="label-text" style="padding-top:0px;"><label for="sort_by"><?php _e('Order By:','icwoocommerce_textdomains');?></label></div>
                                                    <div style="padding-top:0px;">
                                                    	  <?php
														 
														    $sort_by 		= apply_filters("ic_commerce_report_page_default_sort_by", 'total_amount', $report_name);
															$order_by 		= apply_filters("ic_commerce_report_page_default_order_by", 'DESC', $report_name);															
															
															$sort_by 		= $this->get_request('sort_by',$sort_by,true);
															$order_by 		= $this->get_request('order_by',$order_by,true);
															
															$sorting_list 	= apply_filters("ic_commerce_report_page_sorting_list", $sorting_list, $report_name);
															$this->create_dropdown($sorting_list,"sort_by","sort_by",NULL,"sort_by",$sort_by, 'array');
															
															$order_list = array("ASC" => __("Ascending",'icwoocommerce_textdomains'), "DESC" => __("Descending",'icwoocommerce_textdomains'));
															$order_list = apply_filters("ic_commerce_report_page_order_list", $order_list);
                                                            $this->create_dropdown($order_list,"order_by","order_by",NULL,"order_by",$order_by, 'array');
                                                        ?>
                                                    </div>
                                                    
                                                </div>
                                                 <?php if($report_name == '_payment_gateway_page'){?>
                                               		<div class="FormRow">
                                                    <div class="label-text"><label for="payment_method_title"><?php _e("Payment Method:",'icwoocommerce_textdomains');?></label></div>
                                                    <div class="input-text">
                                                        <?php
															$data = $this->get_payment_method_dropdonw_data();
															$this->create_dropdown($data,"payment_method_title[]","payment_method_title","Select One","payment_method_title",'-1', 'object', false, 5);
                                                        ?>  
                                                    </div>
                                                </div>
                                                 <?php }?>
                                                
                                            </div>
                                            
                                            <?php
												}
											?>
											
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
															$hidden_fields['limit'] 				=  $limit;
															$hidden_fields['page_title'] 			=  $page_title;
															$hidden_fields['count_generated'] 		=  $count_generated;
															$hidden_fields['product_status'] 		=  $product_status;
															$hidden_fields['page_title'] 			=  $page_title;
															$hidden_fields['billing_or_shipping'] 	=  $billing_or_shipping;
															$hidden_fields['count_generated'] 		=  0;
															$hidden_fields['total_row_count'] 		=  0;
															$hidden_fields['do_action_type'] 		=  $this->get_request('do_action_type','report_page',true);
															$hidden_fields['page_name'] 			=  $this->get_request('page_name','all_detail',true);
															$hidden_fields['date_format'] 			=  $this->get_request('date_format',get_option('date_format'),true);
															$hidden_fields['onload_search'] 		=  $this->get_request('onload_search',$onload_search,true);
															$hidden_fields['breakup'] 				=  $this->get_request('breakup',0,true);
															$hidden_fields['order_date_field_key'] 	=  $this->get_request('order_date_field_key','post_date',true);
															$hidden_fields['action'] 	 			=  $this->constants['plugin_key'].'_wp_ajax_action';
															
																														
															$hidden_fields = apply_filters('ic_commerce_report_page_search_form_hidden_fields', $hidden_fields, $page, $report_name);
															echo $this->create_search_form_hidden_fields($hidden_fields);
														?> 
                                                		
                                                        
                                                        <span class="submit_buttons">
                                                            <?php if(in_array($report_name,apply_filters('ic_commerce_report_page_reset_button_field_tabs',array('product_page','coupon_page','manual_refund_detail_page'),$report_name))):?>
                                                            	<input name="ResetForm" id="ResetForm" class="onformprocess" value="<?php _e("Reset",'icwoocommerce_textdomains');?>" type="reset">
                                                            <?php endif;?>
                                                            <input name="SearchOrder" id="SearchOrder" class="onformprocess searchbtn" value="<?php _e("Search",'icwoocommerce_textdomains');?>" type="submit"> &nbsp; &nbsp; &nbsp; <span class="ajax_progress"></span>
                                                        </span>
                                                </div>
                                            </div>                                                
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <div class="clearfix"></div>
                    </div>
                <?php else:?>
                    <form action="" name="Report" id="search_order_report" method="post">dfsfdasfdsafds
                            
                            <input type="hidden" value="<?php echo $start_date;?>"			id="start_date" 		name="start_date" 		maxlength="10" /><!--//New Change ID 20140918-->
                            <input type="hidden" value="<?php echo $end_date;?>"				id="end_date" 			name="end_date" 		maxlength="10" />                        
                            <input type="hidden" value="<?php echo $order_status_id;?>"		id="order_status_id" 	name="order_status_id" 	maxlength="1000" />
                            <input type="hidden" value="<?php echo $order_status;?>" 		id="order_status" 		name="order_status" 	maxlength="1000" />
                            <input type="hidden" name="hide_order_status" 					id="hide_order_status" 	value="<?php echo $hide_order_status;?>" />
                            
                        <div class="FormRow " style="width:100%">	                    	
                            <input type="hidden" name="publish_order" 			id="publish_order" 		value="<?php echo $publish_order;?>" />
                            <input type="hidden" name="action" 					id="action" 			value="<?php echo $this->constants['plugin_key'].'_wp_ajax_action';?>" />
                            <input type="hidden" name="limit"  					id="limit" 				value="<?php echo $limit;?>" />
                            <input type="hidden" name="p"  						id="p" 					value="<?php echo $p;?>" />
                            <input type="hidden" name="admin_page"  			id="admin_page" 		value="<?php echo $admin_page;?>" />
                            <input type="hidden" name="page"  					id="page" 				value="<?php echo $page;?>" />
                            <input type="hidden" name="adjacents"  				id="adjacents" 			value="<?php echo $adjacents;?>" />
                            <input type="hidden" name="report_name"  			id="report_name" 		value="<?php echo $report_name;?>" />
                            <input type="hidden" name="do_action_type"  		id="do_action_type" 	value="<?php echo $this->get_request('do_action_type','report_page',true);?>" /> 
                            <input type="hidden" name="page_title"  			id="page_title" 		value="<?php echo $page_title;?>" />
                            <input type="hidden" name="page_name"  				id="page_name" 			value="<?php echo $this->get_request('page_name','all_detail',true);?>" />
                            <input type="hidden" name="count_generated"  		id="count_generated" 	value="<?php echo $count_generated;?>" />
                            <input type="hidden" name="date_format" 			id="date_format" 		value="<?php echo $this->get_request('date_format',get_option('date_format'),true);?>" />
                            <input type="hidden" name="onload_search" 			id="onload_search" 		value="<?php echo $this->get_request('onload_search',$onload_search,true);?>" />
                            <input type="hidden" name="breakup" 				id="breakup" 			value="<?php echo $this->get_request('breakup',0,true);?>" />
                            <input type="hidden" name="product_status" 			id="product_status"		value="<?php echo $product_status;?>" />
                            <span class="submit_buttons">                    
                                <input name="SearchOrder" id="SearchOrder" class="onformprocess searchbtn" value="Search" type="submit" style="display:none">
                            </span>
                        </div>
                    </form>
                <?php endif;?>
                
                <div class="search_report_content hide_for_print">
                	<?php if($onload_search == "no") {echo apply_filters('ic_commerce_onload_search_text', '');}?>
                </div>
                
                <div id="search_for_print_block" class="search_for_print_block"></div>
                <?php
						$page_title				= $this->get_request('page_title',NULL,true);	
						$admin_page 			= $this->get_request('admin_page');
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
						if($page_title) $page_title = " (".$page_title.")";							
						$report_title = $report_title.$page_title;
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
                    
                    <div id="export_print_popup" class="popup_box export_pdf_popup export_print_popup">
                            <a class="popup_close" title="Close popup"></a>
                            <h4>Export to PDF</h4>
                            <?php
                            
                            
                            
                            ?>
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
                                        <td colspan="2"><input type="button" name="<?php echo $admin_page ;?>_export_print" class="onformprocess button_popup_close search_for_print" value="<?php _e("Print",'icwoocommerce_textdomains');?>" data-form="popup"  data-do_action_type="all_report_page_for_print" /></td>
                                    </tr>                                
                                </table>
                                <input type="hidden" name="display_center" value="1" />
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
					
					
					<div class="popup_mask"></div>
                    <?php do_action("ic_commerce_report_page_footer_area",$page);?>
                    <style type="text/css">
                    	.widefat.summary_table{
							width:auto;
						}
						
						.iccommercepluginwrap .widefat.summary_table th._total_tax {
							width:auto;
						}
						<?php
							$report_name	= $this->get_request('report_name','');
							$columns 		= $this->get_columns($report_name);
							echo $this->get_pdf_style_align($columns,'right',' ','.iccommercepluginwrap ', $report_name);?></style><?php
		}
		
		function get_default_sort_by($sort_by = 'total_amount', $report_name = 'product_page'){
			
			switch($report_name){
				case "recent_order":
					$sort_by = 'order_created';
					break;
				
			}
			return $sort_by;
		}
		
		function get_sort_by($report_name = 'product_page'){
			$sorting_list = array();
			switch($report_name){
				case "product_page":
					$sorting_list["product_name"] 	= __("Product Name",'icwoocommerce_textdomains');
					$sorting_list["quantity"] 		= __("Sold Quantity",'icwoocommerce_textdomains');
					$sorting_list["total_amount"] 	= __("Amount",'icwoocommerce_textdomains');
					break;
				case "category_page":
					$sorting_list["category_name"] 	= __("Category Name",'icwoocommerce_textdomains');
					$sorting_list["quantity"] 		= __("Sold Quantity",'icwoocommerce_textdomains');
					$sorting_list["total_amount"] 	= __("Amount",'icwoocommerce_textdomains');
					break;
				case "customer_page":
					$sorting_list["billing_email"] 	= __("Billing Email",'icwoocommerce_textdomains');
					$sorting_list["billing_first_name"]= __("Billing First Name",'icwoocommerce_textdomains');
					$sorting_list["billing_last_name"]= __("Billing Last Name",'icwoocommerce_textdomains');
					$sorting_list["order_count"] 	= __("Order Count",'icwoocommerce_textdomains');
					$sorting_list["total_amount"] 	= __("Amount",'icwoocommerce_textdomains');
					break;
				case "billing_country_page":
					$sorting_list["billing_country"]= __("Billing Country",'icwoocommerce_textdomains');
					$sorting_list["order_count"] 	= __("Order Count",'icwoocommerce_textdomains');
					$sorting_list["total_amount"] 	= __("Amount",'icwoocommerce_textdomains');
					break;
				case "billing_state_page":
					$sorting_list["billing_state_code"]= __("Billing State",'icwoocommerce_textdomains');
					$sorting_list["billing_country"]= __("Billing Country",'icwoocommerce_textdomains');
					$sorting_list["order_count"] 	= __("Order Count",'icwoocommerce_textdomains');
					$sorting_list["total_amount"] 	= __("Amount",'icwoocommerce_textdomains');
					break;
				case "payment_gateway_page":
					$sorting_list["payment_method_title"]= __("Payment Method",'icwoocommerce_textdomains');
					$sorting_list["order_count"] 	= __("Order Count",'icwoocommerce_textdomains');
					$sorting_list["total_amount"] 	= __("Amount",'icwoocommerce_textdomains');
					break;
				case "order_status":
					$sorting_list["order_status"]	= __("Order Status",'icwoocommerce_textdomains');
					$sorting_list["order_count"] 	= __("Order Count",'icwoocommerce_textdomains');
					$sorting_list["total_amount"] 	= __("Amount",'icwoocommerce_textdomains');
					break;
				
				case "recent_order":
					$sorting_list["order_id"]		= __("Order ID",'icwoocommerce_textdomains');
					$sorting_list["order_status"]	= __("Order Status",'icwoocommerce_textdomains');
					$sorting_list["order_created"]	= __("Order Date",'icwoocommerce_textdomains');					
					$sorting_list["total_amount"] 	= __("Net Amt.",'icwoocommerce_textdomains');
					break;
				case "tax_page":
					$sorting_list["_total_tax"] 			= __("Net Amt.",'icwoocommerce_textdomains');
					$sorting_list["tax_rate_name"]			= __("Tax Name",'icwoocommerce_textdomains');
					$sorting_list["order_tax_rate"]			= __("Tax Rate",'icwoocommerce_textdomains');
					$sorting_list["_order_count"] 			= __("Order Count",'icwoocommerce_textdomains');					
					$sorting_list["_order_shipping_amount"] = __("Shipping Amt.",'icwoocommerce_textdomains');
					//$sorting_list["_order_amount"] 			= __("Gross Amt.",'icwoocommerce_textdomains');
					//$sorting_list["_order_total_amount"] 	= __("Net Amt.",'icwoocommerce_textdomains');
					$sorting_list["_shipping_tax_amount"] 	= __("Shipping Tax",'icwoocommerce_textdomains');
					$sorting_list["_order_tax"] 			= __("Order Tax",'icwoocommerce_textdomains');
					$sorting_list["_total_tax"] 			= __("Total Tax",'icwoocommerce_textdomains');		
					break;
					
				case "customer_buy_products_page":
					$sorting_list 	= array(
						"total_amount"						=> __("Amount", 			'icwoocommerce_textdomains')
						,"billing_name"						=> __("Customer Name", 		'icwoocommerce_textdomains')
						,"product_name"						=> __("Product Name", 		'icwoocommerce_textdomains')
						,"quantity"							=> __("Sales Qty.", 		'icwoocommerce_textdomains')						
					);
					break;
				case "_manual_refund_detail_page":
					$sorting_list = array(
						"order_id"							=>	__("Order ID", 				'icwoocommerce_textdomains')
						,"order_date"						=>  __("Order Date",			'icwoocommerce_textdomains')
						,'order_status'						=>  __("Order Status", 			'icwoocommerce_textdomains')								
						,"part_order_refund_amount"			=>	__("Part Refund",			'icwoocommerce_textdomains')
						,"order_refund_amount"				=>	__("Full Refund",			'icwoocommerce_textdomains')								
						,"refund_count"						=>	__("Refund Counts",			'icwoocommerce_textdomains')
						,"total_amount"						=>  __("Refund Amount", 		'icwoocommerce_textdomains')
					);
					break;
				case "coupon_page":
					$sorting_list = array();
					$sorting_list["coupon_code"]			= __("Coupon Code",'icwoocommerce_textdomains');
					$sorting_list["coupon_count"] 			= __("Coupon Count",'icwoocommerce_textdomains');
					$sorting_list["total_amount"] 			= __("Coupon Amount",'icwoocommerce_textdomains');
					break;
				
			}
			
			return $sorting_list;
		}
		
		function get_page_titles($report_name = "",  $plugin_options = array()){
			$page_titles 				= array(
				'product_page'					=> __('Product',				'icwoocommerce_textdomains')
				,'customer_page'				=> __('Customer',				'icwoocommerce_textdomains')				
				,'billing_country_page'			=> __('Billing Country',		'icwoocommerce_textdomains')
				,'billing_state_page'			=> __('Billing State',			'icwoocommerce_textdomains')
				,'payment_gateway_page'			=> __('Payment Gateway',		'icwoocommerce_textdomains')
				,'order_status'					=> __('Order Status',			'icwoocommerce_textdomains')
				,'recent_order'					=> __('Recent Order',			'icwoocommerce_textdomains')
				,'manual_refund_detail_page'	=> __('Refund Details',			'icwoocommerce_textdomains')
				,'coupon_page'					=> __('Coupon',					'icwoocommerce_textdomains')				
			);
			
			$billing_or_shipping	= $this->get_setting('billing_or_shipping',$plugin_options, 'billing');
			if(isset($page_titles['billing_country_page'])) $page_titles['billing_country_page'] 	= $billing_or_shipping == "shipping" ? __( 'Shipping Country' , 'icwoocommerce_textdomains') : $page_titles['billing_country_page'];
			
			$page_titles 						= apply_filters('ic_commerce_report_page_titles',$page_titles,$report_name, $plugin_options);
			return $page_titles;	
		}
		
		function ic_commerce_report_ajax_request($type = 'limit_row'){			
			$report_name 	= $this->get_request('report_name','product_page',true);
			$page_titles 	= $this->get_page_titles($report_name);
			$columns 		= $this->get_columns($report_name);
			$total_columns	= $this->result_columns($report_name);
			
			$this->get_grid($type, $report_name,$columns,$total_columns);			
			$_REQUEST['page_title'] = isset($page_titles[$report_name]) ? $page_titles[$report_name] : $report_name;
		}
		
		
		function get_columns($report_name = 'product_page'){
			$grid_column 	= $this->get_grid_columns();
			$columns 		=  $grid_column->grid_columns_all_reports($report_name);			
			return $columns;
		}
		
		function result_columns($report_name = ''){
			$grid_column 	= $this->get_grid_columns();
			$total_columns 	= $grid_column->result_columns_all_reports($report_name);
			return $total_columns;
		}
		
		function get_items($type = "limit_row",$report_name, $columns = array(), $total_columns = array()){
			$report_name = $this->get_request('report_name','product_page',true);
			$rows = array();
			switch ($report_name ) {
				case "product_page":
					$rows 		= $this->ic_commerce_custom_all_product_query($type, $columns, $report_name, $total_columns);
					break;
				case "customer_page":
					$rows 		= $this->ic_commerce_custom_all_customer_query($type, $columns, $report_name, $total_columns);					
					break;				
				case "payment_gateway_page":
					$rows 		= $this->ic_commerce_custom_all_payment_gateway_query($type, $columns, $report_name, $total_columns);
					break;
				case "order_status":
					$rows 		= $this->ic_commerce_custom_all_order_status_query($type, $columns, $report_name, $total_columns);
					break;
				case "recent_order":
					$rows 		= $this->ic_commerce_custom_all_recent_order_query($type, $columns, $report_name, $total_columns);
					break;
				case "coupon_page":
					$rows 		= $this->ic_commerce_custom_all_coupon_query($type, $columns, $report_name, $total_columns);
					break;
				case "billing_country_page":
					$rows 		= $this->ic_commerce_custom_all_billing_country_query($type, $columns, $report_name, $total_columns);
					break;
				case "advance_product_page"://New Custom Change ID 20141009
					$rows 		= $this->ic_commerce_custom_all_product_advance_query($type, $columns, $report_name, $total_columns);
					break;
				case "manual_refund_detail_page"://New Change ID 20150121
					$rows 		= $this->ic_commerce_custom_all_manual_refund_detail_query($type, $columns, $report_name, $total_columns);
					break;
				default:
					$rows 		= apply_filters('ic_commerce_report_page_default_items',$rows, $type, $columns, $report_name, $this, $total_columns);
					break;
				
			}
						
			$rows 		= apply_filters('ic_commerce_report_page_items',$rows, $type, $columns, $report_name, $this, $total_columns);
			
			return $rows;	
		}
		
		function create_grid_items($order_items = array(), $columns = array(), $report_name = "", $request = array(), $type = "limit_row"){
			switch($report_name){
				case "recent_order":
				case "customer_page":
				case "billing_country_page":					
				case "product_page":
				case "manual_refund_detail_page":
					$grid_object		= $this->get_grid_object();
					$order_items		= $grid_object->create_grid_items($columns,$order_items, $report_name);
					break;
				default:
					$order_items 		= apply_filters('ic_commerce_report_page_create_grid_items',$order_items, $columns, $request, $type);
					break;
				
			}
			
			return $order_items;
		}
		
		function get_detail_page_url($report_name = '', $request = array()){			
			$admin_url 	= admin_url("admin.php?page=".$this->constants['plugin_key']."_details_page");			
			switch ($report_name ) {
				case "product_page":
					$form_input_fields = array('start_date','end_date','order_status');
					break;
				case "customer_page":
				case "billing_country_page":
				case "billing_state_page":
				case "payment_gateway_page":
				case "order_status":
				case "recent_order":
				case "tax_page":
				case "customer_buy_products_page":
					$form_input_fields = array('start_date','end_date');
					break;
				case "manual_refund_detail_page":
					//$form_input_fields = array('start_date','end_date','refund_status_type','show_refund_note','group_by');
					$form_input_fields = array('start_date','end_date');
					break;	
				case "coupon_page":
					//$form_input_fields = array('start_date','end_date','coupon_codes','coupon_discount_types');
					$form_input_fields = array('start_date','end_date');
					break;
				default:
					$form_input_fields = array();					
					break;
			}
			
			$form_input_fields = apply_filters('ic_commerce_report_page_form_inputs', $form_input_fields, $report_name, $request);
			
			$paras = array();
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
		
		function get_grid($type = 'limit_row', $report_name = "", $columns = array(), $total_columns = array()){
				global $wpdb;
				$columns 								= $this->get_columns($report_name);
				$order_items 							= $this->get_items($type,$report_name,$columns,$total_columns);
				if(count($order_items) > 0):
							$report_name 				= $this->get_request('report_name','product_page',true);
							$Totalorder_count 			= 0;
							$TotalAmount 				= 0;
							$TotalShipping 				= 0;
							$request 					= $this->get_all_request();extract($request);
							//$total_pages 				= $this->get_items('total_row',$columns);
							$summary 					= $this->get_items('total_row',$report_name,$columns,$total_columns);
							$total_row_amount			= $summary['total_row_amount'];
							$total_row_count			= $summary['total_row_count'];
							$zero						= $this->price(0);
							$end_date 					= $this->today;
							$request['plugin_key']		= $this->constants['plugin_key'];
							
							$user_url					= admin_url("user-edit.php")."?user_id=";
							//$admin_url 				= admin_url("admin.php?page=".$this->constants['plugin_key']."_details_page");
							//$admin_url 				= !empty($start_date) 	? $admin_url ."&start_date=".$start_date : $admin_url;
							//$admin_url 				= !empty($end_date) 	? $admin_url ."&end_date=".$end_date : $admin_url;
							$admin_url 					= $this->get_detail_page_url($report_name, $request);
							$product_url				= admin_url("post.php?action=edit");
							
							$link_para_order_status 	= "";
							
							if($order_status_id != "-1" and strlen($order_status_id)>2){
								$link_para_order_status = "&order_status_id=".$order_status_id;
							}else if($order_status != "-1" and strlen($order_status)>2){
								$link_para_order_status = "&order_status=".$order_status;
							}
							
							$columns					= apply_filters("ic_commerce_report_page_grid_columns",$columns, $report_name);
							$order_items				= apply_filters("ic_commerce_report_page_data_grid_items",$order_items,$columns, $report_name, $request, $type, $zero);
							
							$order_items				= $this->create_grid_items($order_items, $columns, $report_name, $request, $type);
							
							$order_items				= apply_filters("ic_commerce_report_page_data_grid_items_create_grid_items",$order_items,$columns, $report_name, $request, $type, $zero);
							
							if(count($order_items)<=0){
								_e("Some thing going wrong on creating result data in \"add_filter\" or \"create_grid_items\"", "icwoocommerce_textdomains");
								return;
							}
							
							$price_columns				= apply_filters("ic_commerce_report_page_grid_price_columns",array(), $report_name);
							$date_format				= get_option( 'date_format' );
							$this->print_header($type);	
						?>
                		<?php if($type != 'all_row'):?>
                        <div class="top_buttons"><?php $this->export_to_csv_button('top',$summary);?><div class="clearfix"></div></div>
                        <?php else: $this->back_print_botton('top',$summary)?>
						<?php endif;?>
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
												case "quantity":
												case "product_stock":
												case "total_amount":
												case "order_count":
												case "coupon_amount":
												case "Count":
												case "coupon_count":
												case "refund_amount":
												case "refund_count":
												case "order_refund_amount":
												case "part_order_refund_amount":
												case "total_refund_amount":
												case "quantity":
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
									//$this->print_array($order_items);
									foreach ( $order_items as $key => $order_item ) {
										$TotalShipping 	= isset($order_item->order_shipping) ? $TotalShipping + $order_item->order_shipping : 0;
										$TotalAmount 	= isset($order_item->total_amount) ? ($TotalAmount + $order_item->total_amount) : 0;
										$Totalorder_count++;									
										if($key%2 == 1){$alternate = "alternate ";}else{$alternate = "";};
										?>
										<tr class="<?php echo $alternate."row_".$key;?>">
											<?php
												foreach($columns as $key => $value):
													$td_class = $key;
													$td_style = '';
													$td_value = "";
													switch($key):
														
														case "product_stock":
															$td_value = $this->get_stock_($order_item->order_item_id, $order_item->product_id,$report_name);
															$td_value = $td_value + 0;
															$td_class .= " amount";
															break;
														case "product_sku":													
															$td_value = $this->get_sku($order_item->order_item_id, $order_item->product_id, $report_name);
															break;													
														case "sku":
															$td_value = $this->get_stock($order_item->$key);
															break;
														case "stock":													
															$td_value = $this->get_stock($order_item->$key);
															$td_value = $td_value + 0;
															$td_class .= " amount";														
															break;
														case "product_name":													
															$td_value = " <a href=\"{$admin_url}&detail_view=yes&product_id={$order_item->product_id}\" target=\"_blank\">{$order_item->product_name}</a>";														
															break;
														case "coupon_code":
															$td_value = '<a href="'.$admin_url."&coupon_code=".$order_item->coupon_code.$link_para_order_status.'&detail_view=no" target="'.$order_item->coupon_code.'_blank">' . $order_item->coupon_code  . '</a>';
															break;
														case "quantity":														
															$td_value = $order_item->quantity;	
															$td_class .= " amount";													
															break;
														case "order_id":
															$td_value = '<a href="'.$admin_url."&order_id=".$order_item->order_id.$link_para_order_status.'&detail_view=no" target="'.$order_item->order_id.'_blank">' . $order_item->order_id  . '</a>';
															break;														
														case "billing_name":
															$td_value = ucwords(stripslashes_deep($order_item->billing_name));
															break;
														case "billing_first_name":
															$billing_first_name = isset($order_item->billing_first_name) ? $order_item->billing_first_name:'';
															$td_value = "<a href=\"{$admin_url}&detail_view=yes&first_name={$billing_first_name}\" target=\"_blank\">{$billing_first_name}</a>";
															break;
														case "billing_email":
															$td_value = $this->emailLlink($order_item->billing_email,false);
															break;													
														case "status":
															$td_value = '<span class="order-status order-status-'.sanitize_title($order_item->status_id).'">'.ucwords(__($order_item->status, 'icwoocommerce_textdomains')).'</span>';
															break;
														case 'billing_country':
														case 'billing_country':
															if($report_name == 'billing_country_page'){
																$td_value = " <a href=\"{$admin_url}&detail_view=no&country_code={$order_item->billing_country_code}\" target=\"_blank\">{$order_item->billing_country}</a>";
															}else{
																$td_value = $order_item->billing_country;							
															}
															break;														
														case 'billing_state_name':
															$td_value = " <a href=\"{$admin_url}&detail_view=no&state_code={$order_item->billing_state_code}\" target=\"_blank\">{$order_item->billing_state_name}</a>";
															break;
														case 'shipping_country':
															$country      	= $this->get_wc_countries();//Added 20150225														
															$td_value = isset($country->countries[$order_item->$key]) ? $country->countries[$order_item->$key]: $order_item->$key;
															
															break;
														case "item_count":
														case "transaction_id":
														case "order_item_count":														
														case "item_count":
														case "order_count":
														case "refund_count":
														case "Count":
														case "coupon_count":
														case "_order_count":
															$td_value = isset($order_item->$key) ? $order_item->$key : '';
															$td_class .= " amount";
															break;
														
														case "order_date":
														case "post_date":
														case "refund_date":
														case "group_date":
														case "group_date":
														case "last_date":
														case "delivery_date":
														case "last_date":
														case "first_date":
															$order_date = isset($order_item->$key) 	? trim($order_item->$key) 					: '';
															$td_value 	= !empty($order_date) 		? date($date_format,strtotime($order_date)) : '';
															break;
														case "order_tax_rate":
															$td_value = isset($order_item->$key) ? $order_item->$key : 0;
															$td_value = sprintf("%.2f%%",$td_value);
															$td_class .= " amount";
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
														case "total_amount":
														
														
														//New Custom Change ID 20141009
														case "product_rate_exculude_tax":
														case "product_vat":
														case "product_vat_total":
														case "product_vat_par_item":
														case "product_shipping":
														case "total_price_exculude_tax":
														
														//New Custom Change ID 20141015
														case "cost_of_good_amount":
														case "total_cost_good_amount":
														case "margin_profit_amount":
														case "sales_rate_amount":
														
														
														case "_order_shipping_amount":
														case "_order_amount":
														case "order_total_amount":
														case "_shipping_tax_amount":
														case "_order_tax":
														case "_total_tax":
														case "order_refund_amount":
														case "part_order_refund_amount":
														case "total_refund_amount":
														case "product_rate":
														case "min_product_price":
														case "max_product_price":
														
														case "product_sold_rate":
														case "product_total":
														case "product_subtotal":
														case "product_discount":														
															$td_value = isset($order_item->$key) ? $order_item->$key : 0;
															$td_value = $td_value == 0 ? $zero : $this->price($td_value);
															$td_class .= " amount";
															break;////New Change ID 20140918
														case "coupon_amount":
															$td_value = isset($order_item->$key) ? $order_item->$key : 0;
															$discount_type = isset($order_item->discount_type) ? $order_item->discount_type : '';
															switch($discount_type){
																case "percent":
																case "percent_product":
																	$td_value = sprintf("%.2f%%",$td_value);
																	break;
																case "fixed_cart":
																case "fixed_product":
																default:
																	$td_value = $td_value == 0 ? $zero : $this->price($td_value);
																	break;
															}
															$td_class .= " amount";
															break;
														case "order_status"://New Change ID 20140918
															$td_value = isset($order_item->$key) ? $order_item->$key : '';
															//$td_value = '<span class="order-status order-status-'.sanitize_title($td_value).'">'.ucwords(__($td_value, 'icwoocommerce_textdomains')).'</span>';
															$td_value = "<a href=\"{$admin_url}&detail_view=yes&order_status=wc-".sanitize_title($td_value)."\" target=\"_blank\"><span class=\"order-status order-status-".sanitize_title($td_value)."\">".ucwords(__($td_value, 'icwoocommerce_textdomains'))."</span></a>";
															
															break;
														case "order_status_name"://New Change ID 20150225
														case "refund_status":////New Change ID 20150403
															$td_value = isset($order_item->$key) ? $order_item->$key : '';
															$td_value = '<span class="order-status order-status-'.sanitize_title($td_value).'">'.ucwords(__($td_value, 'icwoocommerce_textdomains')).'</span>';
															break;
														case "product_edit":
															$td_class .= " amount";
															$td_value = "<a href=\"{$product_url}&post={$order_item->product_id}\" target=\"_blank\">Edit</a>";
															break;
															
														case "billing_first_name2":														
															$td_value = isset($order_item->$key) ? $order_item->$key : '';
															/*if(isset($order_item->customer_id) and strlen($order_item->customer_id) > 0 and $order_item->customer_id > 0){
																if(isset($this->user_details[$order_item->customer_id])){
																	$user_details = $this->user_details[$order_item->customer_id];
																}else{
																	$user_details = $this->get_user_details($order_item->customer_id);
																	$this->user_details[$order_item->customer_id] = $user_details;
																}
																$user_name = $user_details->user_name;
																$first_name = $user_details->first_name;															
																$td_value = $first_name;
															}else{
																$td_value = isset($order_item->billing_first_name) ? $order_item->billing_first_name : '';
															}*/
															break;	
														
														case "user_name":														
															$td_value = isset($order_item->$key) ? $order_item->$key : '';
															/*if(isset($order_item->customer_id) and strlen($order_item->customer_id) > 0 and $order_item->customer_id > 0){
																
																if(isset($this->user_details[$order_item->customer_id])){
																	$user_details = $this->user_details[$order_item->customer_id];
																}else{
																	$user_details = $this->get_user_details($order_item->customer_id);
																	$this->user_details[$order_item->customer_id] = $user_details;
																}
																$user_name = $user_details->user_name;															
																$user_name = '<a href="'.$admin_user."?user_id=".$order_item->customer_id.'" target="_blank">'.$user_name.'</a>';															
																$td_value = $user_name;
															}*/
															break;
														case "profit_percentage":
															$td_class .= " amount";
															$td_value = isset($order_item->$key) ? $order_item->$key : 0;
															$td_class .= $td_value > 0 ? " up_class" : ($td_value < 0 ? " down_class" : " equal_class");
															//$td_value = number_format($td_value,2,".",",");
															//$td_value .= " %";
															$td_value = sprintf("%.2f%%",$td_value);
															break;
														default:
															if(in_array($key, $price_columns)){
																$td_value = isset($order_item->$key) ? $order_item->$key : 0;
																$td_value = $td_value == 0 ? $zero : $this->price($td_value);
																$td_class .= " amount";
															}else{
																$td_value = isset($order_item->$key) ? $order_item->$key : '';
															}															
															break;
													endswitch;
													$td_content = "<td class=\"{$td_class}\"{$td_style}>{$td_value}</td>\n";
													echo $td_content;
												endforeach;                                        	
											?>
										</tr>
										<?php 
									}
								?>
							</tbody>          
						</table>
						 <?php 
						 	if($type != 'all_row') $this->total_count($Totalorder_count, $TotalAmount, $summary, $TotalShipping);  else $this->back_print_botton('bottom',$summary);
                        	echo $this->result_grid($report_name,$summary,$zero,$total_columns, $price_columns);
							echo apply_filters("ic_commerce_report_page_bottom_of_report",'',$columns, $total_columns, $price_columns);//
							
						 ?>
				<?php else:?>        
						<div class="order_not_found"><?php _e('No Orders found','icwoocommerce_textdomains'); ?></div>
				<?php endif;?>
			<?php
		}
		
		var $items_query = NULL;
		
		/* TAB 1 */
		/*All Product List*/
		function ic_commerce_custom_all_product_query($type = 'limit_row', $columns = array(), $report_name = ""){
			global $wpdb;			
			//$this->print_array($_REQUEST);
			if(!isset($this->items_query)){
				$request 					= $this->get_all_request();extract($request);
				$order_status				= $this->get_string_multi_request('order_status',$order_status, "-1");
				$hide_order_status			= $this->get_string_multi_request('hide_order_status',$hide_order_status, "-1");
				$category_product_id_string = $this->get_products_list_in_category($category_id,$product_id);//Added 20150219
				$category_id 				= "-1";//Added 20150219
				
				$sql = " SELECT ";
				
				$sql .= "							
							woocommerce_order_items.order_item_name				AS 'product_name'
							,woocommerce_order_items.order_item_id				AS order_item_id
							,woocommerce_order_itemmeta_product_id.meta_value	AS product_id							
							,DATE(shop_order.post_date)							AS post_date
							";

				if($category_id  && $category_id != "-1") {
					
					$sql .= "
							,terms.term_id								AS term_id
							,terms.name									AS term_name
							,term_taxonomy.parent						AS term_parent
						";						
				}
								
				//$sql .= " ,woocommerce_order_itemmeta.meta_value AS 'quantity' ,woocommerce_order_itemmeta6.meta_value AS 'total_amount'";
				$sql .= " ,SUM(woocommerce_order_itemmeta.meta_value) AS 'quantity' ,SUM(woocommerce_order_itemmeta6.meta_value) AS 'total_amount'";
							
				$sql = apply_filters("ic_commerce_report_page_select_query", $sql, $request, $type, $page, $report_name, $columns);
				
				$sql .= "
							FROM {$wpdb->prefix}woocommerce_order_items as woocommerce_order_items						
							LEFT JOIN  {$wpdb->prefix}woocommerce_order_itemmeta as woocommerce_order_itemmeta ON woocommerce_order_itemmeta.order_item_id=woocommerce_order_items.order_item_id
							LEFT JOIN  {$wpdb->prefix}woocommerce_order_itemmeta as woocommerce_order_itemmeta6 ON woocommerce_order_itemmeta6.order_item_id=woocommerce_order_items.order_item_id
							LEFT JOIN  {$wpdb->prefix}woocommerce_order_itemmeta as woocommerce_order_itemmeta_product_id ON woocommerce_order_itemmeta_product_id.order_item_id=woocommerce_order_items.order_item_id						
							";
				
				if($category_id  && $category_id != "-1") {
						$sql .= " 	
							LEFT JOIN  {$wpdb->prefix}term_relationships 	as term_relationships 	ON term_relationships.object_id		=	woocommerce_order_itemmeta_product_id.meta_value 
							LEFT JOIN  {$wpdb->prefix}term_taxonomy 		as term_taxonomy 		ON term_taxonomy.term_taxonomy_id	=	term_relationships.term_taxonomy_id
							LEFT JOIN  {$wpdb->prefix}terms 				as terms 				ON terms.term_id					=	term_taxonomy.term_id";
				}
				
				if($order_status_id  && $order_status_id != "-1") {
						$sql .= " 	
							LEFT JOIN  {$wpdb->prefix}term_relationships	as term_relationships2 	ON term_relationships2.object_id	=	woocommerce_order_items.order_id
							LEFT JOIN  {$wpdb->prefix}term_taxonomy			as term_taxonomy2 		ON term_taxonomy2.term_taxonomy_id	=	term_relationships2.term_taxonomy_id
							LEFT JOIN  {$wpdb->prefix}terms					as terms2 				ON terms2.term_id					=	term_taxonomy2.term_id";
				}
				
				$sql .= " 
							LEFT JOIN  {$wpdb->prefix}posts as shop_order ON shop_order.id=woocommerce_order_items.order_id";
				
				$sql = apply_filters("ic_commerce_report_page_join_query", $sql, $request, $type, $page, $report_name, $columns);
							
				$sql .= "
							WHERE 1*1
							AND woocommerce_order_itemmeta.meta_key	= '_qty'
							AND woocommerce_order_itemmeta6.meta_key	= '_line_total' 
							AND woocommerce_order_itemmeta_product_id.meta_key 	= '_product_id'						
							AND shop_order.post_type					= 'shop_order'
							";
							
				if($order_date_field_key == "post_date" || $order_date_field_key == "post_modified"){
					if ($start_date != NULL &&  $end_date !=NULL){
						$sql .= " AND DATE(shop_order.{$order_date_field_key}) BETWEEN '".$start_date."' AND '". $end_date ."'";
					}
				}
				
				if($product_id  && $product_id != "-1") 
					$sql .= "
							AND woocommerce_order_itemmeta_product_id.meta_value IN (".$product_id .")";
				
				if($category_id  && $category_id != "-1") 
					$sql .= "
							AND terms.term_id IN (".$category_id .")";	
				
				if($category_product_id_string  && $category_product_id_string != "-1") $sql .= " AND woocommerce_order_itemmeta_product_id.meta_value IN (".$category_product_id_string .")";//Added 20150219	
				
				if($order_status_id  && $order_status_id != "-1") 
					$sql .= " 
							AND terms2.term_id IN (".$order_status_id .")";
							
				
				if(strlen($publish_order)>0 && $publish_order != "-1" && $publish_order != "no" && $publish_order != "all"){
					$in_post_status		= str_replace(",","','",$publish_order);
					$sql .= " AND  shop_order.post_status IN ('{$in_post_status}')";
				}
				//echo $order_status;
				if($order_status  && $order_status != '-1' and $order_status != "'-1'")$sql .= " AND shop_order.post_status IN (".$order_status.")";
				
				if($hide_order_status  && $hide_order_status != '-1' and $hide_order_status != "'-1'")$sql .= " AND shop_order.post_status NOT IN (".$hide_order_status.")";
				
				$sql = apply_filters("ic_commerce_report_page_where_query", $sql, $request, $type, $page, $report_name, $columns);
				
				$group_sql = " GROUP BY  woocommerce_order_itemmeta_product_id.meta_value";		
				
				$sql .= apply_filters("ic_commerce_report_page_group_query", $group_sql, $request, $type, $page, $report_name, $columns);	
				
				$order_sql = " ORDER BY {$sort_by} {$order_by}";
				
				$sql .= apply_filters("ic_commerce_report_page_order_query", $order_sql, $request, $type, $page, $report_name, $columns);	
				
				$this->items_query = $sql;
			}else{
				$sql = $this->items_query;
			}
			
			$order_items = $this->get_query_items($type,$sql);
			return $order_items;
		}
				
		function get_stock($stock_count){
			if(strlen($stock_count)<=0){
				return  __("Not Set",'icwoocommerce_textdomains');
			}else{
				return $stock_count;
			}
		}
		
		/* TAB 2*/
		/*All customer Query*/
		function ic_commerce_custom_all_customer_query($type = 'limit_row', $columns = array(), $report_name = "")
		{
				global $wpdb;
				if(!isset($this->items_query)){
					$request = $this->get_all_request();extract($request);
					
					$order_status	= $this->get_string_multi_request('order_status',$order_status, "-1");
					$hide_order_status	= $this->get_string_multi_request('hide_order_status',$hide_order_status, "-1");
											
					$sql = " SELECT ";
					$sql .= "
					SUM(postmeta1.meta_value) AS 'total_amount' 
					,postmeta2.meta_value AS 'billing_email'
					,postmeta3.meta_value AS 'billing_first_name'
					,Count(postmeta2.meta_value) AS 'order_count'
					,MAX(posts.post_date)				AS  order_date";
					
					$sql .= " ,postmeta4.meta_value AS  customer_id";
					$sql .= " ,postmeta4.meta_value AS  customer_user";
					$sql .= " ,postmeta5.meta_value AS  billing_last_name";
					$sql .= " ,CONCAT(postmeta3.meta_value, ' ',postmeta5.meta_value) AS billing_name";
					$sql .= " ,postmeta6.meta_value AS  billing_company";
					
					$sql = apply_filters("ic_commerce_report_page_select_query", $sql, $request, $type, $page, $report_name, $columns);
					
					$sql .= " FROM {$wpdb->prefix}posts as posts
					LEFT JOIN  {$wpdb->prefix}postmeta as postmeta1 ON postmeta1.post_id=posts.ID
					LEFT JOIN  {$wpdb->prefix}postmeta as postmeta2 ON postmeta2.post_id=posts.ID
					LEFT JOIN  {$wpdb->prefix}postmeta as postmeta3 ON postmeta3.post_id=posts.ID";
					
					$sql .= " LEFT JOIN  {$wpdb->prefix}postmeta as postmeta4 ON postmeta4.post_id=posts.ID";
					$sql .= " LEFT JOIN  {$wpdb->prefix}postmeta as postmeta5 ON postmeta5.post_id=posts.ID";
					$sql .= " LEFT JOIN  {$wpdb->prefix}postmeta as postmeta6 ON postmeta6.post_id=posts.ID";
					
					if(strlen($order_status_id)>0 && $order_status_id != "-1" && $order_status_id != "no" && $order_status_id != "all"){
							$sql .= " 
							LEFT JOIN  {$wpdb->prefix}term_relationships 	as term_relationships 	ON term_relationships.object_id		=	posts.ID
							LEFT JOIN  {$wpdb->prefix}term_taxonomy 		as term_taxonomy 		ON term_taxonomy.term_taxonomy_id	=	term_relationships.term_taxonomy_id";
					}
					
					
					$sql = apply_filters("ic_commerce_report_page_join_query", $sql, $request, $type, $page, $report_name, $columns);
					$sql .= "
					
					WHERE  
					posts.post_type='shop_order'  
					AND postmeta1.meta_key='_order_total' 
					AND postmeta2.meta_key='_billing_email'  
					AND postmeta3.meta_key='_billing_first_name'
					";
					
					$sql .= " AND postmeta4.meta_key='_customer_user'";
					$sql .= " AND postmeta5.meta_key='_billing_last_name'";
					$sql .= " AND postmeta6.meta_key='_billing_company'";
					
					if(strlen($order_status_id)>0 && $order_status_id != "-1" && $order_status_id != "no" && $order_status_id != "all"){
						$sql .= " AND  term_taxonomy.term_id IN ({$order_status_id})";
					}
					
					//if ($start_date != NULL &&  $end_date !=NULL){
						//$sql .= " AND DATE(posts.post_date) BETWEEN '".$start_date."' AND '". $end_date ."'";
					//}
					
					if($order_date_field_key == "post_date" || $order_date_field_key == "post_modified"){
						if ($start_date != NULL &&  $end_date !=NULL){
							$sql .= " AND DATE(posts.{$order_date_field_key}) BETWEEN '".$start_date."' AND '". $end_date ."'";
						}
					}
					
					if(strlen($publish_order)>0 && $publish_order != "-1" && $publish_order != "no" && $publish_order != "all"){
						$in_post_status		= str_replace(",","','",$publish_order);
						$sql .= " AND  posts.post_status IN ('{$in_post_status}')";
					}
					
					//Added 20141013
					if($order_status  && $order_status != '-1' and $order_status != "'-1'")$sql .= " AND posts.post_status IN (".$order_status.")";
					if($hide_order_status  && $hide_order_status != '-1' and $hide_order_status != "'-1'")$sql .= " AND posts.post_status NOT IN (".$hide_order_status.")";
					
					//$sql .= "  GROUP BY  postmeta2.meta_value Order By total_amount DESC";
					
					$sql = apply_filters("ic_commerce_report_page_where_query", $sql, $request, $type, $page, $report_name, $columns);
				
					$group_sql = " GROUP BY  postmeta2.meta_value ";
					
					$sql .= apply_filters("ic_commerce_report_page_group_query", $group_sql, $request, $type, $page, $report_name, $columns);	
					
					$sort_by 	= isset($request['sort_by']) ? $request['sort_by'] : 'total_amount';
				
					$order_by 	= isset($request['order_by']) ? $request['order_by'] : 'DESC';				
					
					$order_sql = " ORDER BY {$sort_by} {$order_by}";
					
					$sql .= apply_filters("ic_commerce_report_page_order_query", $order_sql, $request, $type, $page, $report_name, $columns);	
					
					$this->items_query = $sql;
				}else{
					$sql = $this->items_query;
				}
				
				$order_items = $this->get_query_items($type,$sql);
				
				if($type == 'limit_row' || $type == 'all_row' or $type == 'all_row_total'){
					//$order_items 		= apply_filters("ic_commerce_all_report_recent_order_data_items", $order_items, $report_name);
				}
				return $order_items;
		}
		
		
		
		
		/* TAB 3 */
		/*All billing country query*/
		function ic_commerce_custom_all_billing_country_query($type = 'limit_row', $columns = array(), $report_name = "")
		{
			global $wpdb;
			if(!isset($this->items_query)){
				$request = $this->get_all_request();extract($request);
				
				$order_status			= $this->get_string_multi_request('order_status',$order_status, "-1");
				$hide_order_status		= $this->get_string_multi_request('hide_order_status',$hide_order_status, "-1");
				$billing_or_shipping	= $this->get_setting('billing_or_shipping',$this->constants['plugin_options'], 'billing');
				
				$sql = "
				SELECT SUM(postmeta1.meta_value) AS 'total_amount' 
				,postmeta2.meta_value AS 'billing_country'
				,Count(*) AS 'order_count'";
				$sql = apply_filters("ic_commerce_report_page_select_query", $sql, $request, $type, $page, $report_name, $columns);				
				$sql .= "
				FROM {$wpdb->prefix}posts as posts
				LEFT JOIN  {$wpdb->prefix}postmeta as postmeta1 ON postmeta1.post_id=posts.ID
				LEFT JOIN  {$wpdb->prefix}postmeta as postmeta2 ON postmeta2.post_id=posts.ID";
				if(strlen($order_status_id)>0 && $order_status_id != "-1" && $order_status_id != "no" && $order_status_id != "all"){
						$sql .= " 
						LEFT JOIN  {$wpdb->prefix}term_relationships 	as term_relationships 	ON term_relationships.object_id		=	posts.ID
						LEFT JOIN  {$wpdb->prefix}term_taxonomy 		as term_taxonomy 		ON term_taxonomy.term_taxonomy_id	=	term_relationships.term_taxonomy_id";
				}
				
				$sql = apply_filters("ic_commerce_report_page_join_query", $sql, $request, $type, $page, $report_name, $columns);
				
				$sql .= "
				WHERE
				posts.post_type			=	'shop_order'  
				AND postmeta1.meta_key	=	'_order_total' 
				AND postmeta2.meta_key	=	'_{$billing_or_shipping}_country'";
				
				if(strlen($order_status_id)>0 && $order_status_id != "-1" && $order_status_id != "no" && $order_status_id != "all"){
					$sql .= " AND  term_taxonomy.term_id IN ({$order_status_id})";
				}
				
				/*if ($start_date != NULL &&  $end_date !=NULL){
					$sql .= " AND DATE(posts.post_date) BETWEEN '".$start_date."' AND '". $end_date ."'";
				}*/
				
				if($order_date_field_key == "post_date" || $order_date_field_key == "post_modified"){
					if ($start_date != NULL &&  $end_date !=NULL){
						$sql .= " AND DATE(posts.{$order_date_field_key}) BETWEEN '".$start_date."' AND '". $end_date ."'";
					}
				}
					
					
				if(strlen($publish_order)>0 && $publish_order != "-1" && $publish_order != "no" && $publish_order != "all"){
					$in_post_status		= str_replace(",","','",$publish_order);
					$sql .= " AND  posts.post_status IN ('{$in_post_status}')";
				}
				
				if($order_status  && $order_status != '-1' and $order_status != "'-1'")$sql .= " AND posts.post_status IN (".$order_status.")";
				
				if($hide_order_status  && $hide_order_status != '-1' and $hide_order_status != "'-1'")$sql .= " AND posts.post_status NOT IN (".$hide_order_status.")";
				
				$sql = apply_filters("ic_commerce_report_page_where_query", $sql, $request, $type, $page, $report_name, $columns);
				
				$group_sql = " GROUP BY  postmeta2.meta_value  ";
				
				$sql .= apply_filters("ic_commerce_report_page_group_query", $group_sql, $request, $type, $page, $report_name, $columns);	
				
				$sort_by 	= isset($request['sort_by']) ? $request['sort_by'] : 'total_amount';
				
				$order_by 	= isset($request['order_by']) ? $request['order_by'] : 'DESC';				
				
				$order_sql = " ORDER BY {$sort_by} {$order_by}";
				
				$sql .= apply_filters("ic_commerce_report_page_order_query", $order_sql, $request, $type, $page, $report_name, $columns);	
				
				$this->items_query = $sql;
				
			}else{
				$sql = $this->items_query;
			}
				
			$order_items = $this->get_query_items($type,$sql);
			return $order_items;
		}
		
		/* TAB 4 */
		/*Payment gateway*/
		function ic_commerce_custom_all_payment_gateway_query($type = 'limit_row', $columns = array(), $report_name = "")
		{
		 	
			global $wpdb;
			if(!isset($this->items_query)){
				$request 				= $this->get_all_request();extract($request);				
				$payment_method_title 	= $this->get_request('payment_method_title');
				$order_status			= $this->get_string_multi_request('order_status',$order_status, "-1");
				$hide_order_status		= $this->get_string_multi_request('hide_order_status',$hide_order_status, "-1");
				$payment_method_title	= $this->get_string_multi_request('payment_method_title',$payment_method_title, "-1");
										
				$sql = " SELECT ";
				$sql .= "
				postmeta2.meta_value AS 'payment_method_title' 
				,SUM(postmeta1.meta_value) AS 'total_amount'
				,COUNT(postmeta1.meta_value) As 'order_count'";
				$sql = apply_filters("ic_commerce_report_page_select_query", $sql, $request, $type, $page, $report_name, $columns);				
				$sql .= "
				
				FROM {$wpdb->prefix}posts as posts
				LEFT JOIN  {$wpdb->prefix}postmeta as postmeta1 ON postmeta1.post_id=posts.ID
				LEFT JOIN  {$wpdb->prefix}postmeta as postmeta2 ON postmeta2.post_id=posts.ID";
				if(strlen($order_status_id)>0 && $order_status_id != "-1" && $order_status_id != "no" && $order_status_id != "all"){
						$sql .= " 
						LEFT JOIN  {$wpdb->prefix}term_relationships 	as term_relationships 	ON term_relationships.object_id		=	posts.ID
						LEFT JOIN  {$wpdb->prefix}term_taxonomy 		as term_taxonomy 		ON term_taxonomy.term_taxonomy_id	=	term_relationships.term_taxonomy_id";
				}
				
				$sql = apply_filters("ic_commerce_report_page_join_query", $sql, $request, $type, $page, $report_name, $columns);
				
				$sql .= "
				WHERE
				posts.post_type='shop_order'  
				AND postmeta1.meta_key='_order_total' 
				AND postmeta2.meta_key='_payment_method_title'
				";
				if(strlen($order_status_id)>0 && $order_status_id != "-1" && $order_status_id != "no" && $order_status_id != "all"){
					$sql .= " AND  term_taxonomy.term_id IN ({$order_status_id})";
				}
				
				if($payment_method_title  && $payment_method_title != '-1' and $payment_method_title != "'-1'"){
					$sql .= " AND postmeta2.meta_value IN (".$payment_method_title.")";
				}
				
				/*if ($start_date != NULL &&  $end_date !=NULL){
					$sql .= " AND DATE(posts.post_date) BETWEEN '".$start_date."' AND '". $end_date ."'";
				}*/
				
				if($order_date_field_key == "post_date" || $order_date_field_key == "post_modified"){
					if ($start_date != NULL &&  $end_date !=NULL){
						$sql .= " AND DATE(posts.{$order_date_field_key}) BETWEEN '".$start_date."' AND '". $end_date ."'";
					}
				}
				
				if(strlen($publish_order)>0 && $publish_order != "-1" && $publish_order != "no" && $publish_order != "all"){
					$in_post_status		= str_replace(",","','",$publish_order);
					$sql .= " AND  posts.post_status IN ('{$in_post_status}')";
				}
				
				if($order_status  && $order_status != '-1' and $order_status != "'-1'")$sql .= " AND posts.post_status IN (".$order_status.")";
				
				if($hide_order_status  && $hide_order_status != '-1' and $hide_order_status != "'-1'")$sql .= " AND posts.post_status NOT IN (".$hide_order_status.")";
				
				//$sql .= " GROUP BY postmeta2.meta_value	Order BY total_amount DESC ";
				
				$sql = apply_filters("ic_commerce_report_page_where_query", $sql, $request, $type, $page, $report_name, $columns);
				
				$group_sql = " GROUP BY postmeta2.meta_value ";
				
				$sql .= apply_filters("ic_commerce_report_page_group_query", $group_sql, $request, $type, $page, $report_name, $columns);	
				
				$sort_by 	= isset($request['sort_by']) ? $request['sort_by'] : 'total_amount';
				
				$order_by 	= isset($request['order_by']) ? $request['order_by'] : 'DESC';				
				
				$order_sql = " ORDER BY {$sort_by} {$order_by}";
				
				$sql .= apply_filters("ic_commerce_report_page_order_query", $order_sql, $request, $type, $page, $report_name, $columns);	
				
				$this->items_query = $sql;
				
			}else{
				$sql = $this->items_query;
			}
					
			$order_items = $this->get_query_items($type,$sql);
			return $order_items;
		}
		
	
		/* TAB 5 */
		/*All order status query*/
		function ic_commerce_custom_all_order_status_query($type = 'limit_row', $columns = array(), $report_name = "")
		{
		    global $wpdb;
			if(!isset($this->items_query)){
				$request = $this->get_all_request();extract($request);
				
				$order_status	= $this->get_string_multi_request('order_status',$order_status, "-1");
				$hide_order_status	= $this->get_string_multi_request('hide_order_status',$hide_order_status, "-1");
										
				$sql = " SELECT ";
				$sql .= "
				COUNT(postmeta.meta_value) AS 'order_count'
				,SUM(postmeta.meta_value) AS 'total_amount'";
				
				if($this->constants['post_order_status_found'] == 0 ){
					$sql .= "  ,terms.name As 'order_status', term_taxonomy.term_id AS 'StatusID'";
					

					$sql = apply_filters("ic_commerce_report_page_select_query", $sql, $request, $type, $page, $report_name, $columns);				

					
					$sql .= "  FROM {$wpdb->prefix}posts as posts";
					
					$sql .= "
					LEFT JOIN  {$wpdb->prefix}term_relationships as term_relationships ON term_relationships.object_id=posts.ID
					LEFT JOIN  {$wpdb->prefix}term_taxonomy as term_taxonomy ON term_taxonomy.term_taxonomy_id=term_relationships.term_taxonomy_id
					LEFT JOIN  {$wpdb->prefix}terms as terms ON terms.term_id=term_taxonomy.term_id";
				}else{
					$sql .= "  , posts.post_status As 'order_status' ,posts.post_status As 'StatusID'";
					$sql = apply_filters("ic_commerce_report_page_select_query", $sql, $request, $type, $page, $report_name, $columns);				
					
					$sql .= "  FROM {$wpdb->prefix}posts as posts";
				}
				
				$sql .= "  
				LEFT JOIN  {$wpdb->prefix}postmeta as postmeta ON postmeta.post_id=posts.ID";
				
				$sql = apply_filters("ic_commerce_report_page_join_query", $sql, $request, $type, $page, $report_name, $columns);
				
				$sql .= "  
				WHERE postmeta.meta_key = '_order_total'
				AND posts.post_type='shop_order'";	
				
				/*if ($start_date != NULL &&  $end_date !=NULL){
					$sql .= " AND DATE(posts.post_date) BETWEEN '".$start_date."' AND '". $end_date ."'";
				}*/
				
				if($order_date_field_key == "post_date" || $order_date_field_key == "post_modified"){
					if ($start_date != NULL &&  $end_date !=NULL){
						$sql .= " AND DATE(posts.{$order_date_field_key}) BETWEEN '".$start_date."' AND '". $end_date ."'";
					}
				}
				
				if(strlen($publish_order)>0 && $publish_order != "-1" && $publish_order != "no" && $publish_order != "all"){
					$in_post_status		= str_replace(",","','",$publish_order);
					$sql .= " AND  posts.post_status IN ('{$in_post_status}')";
				}
				
				//if($order_status  && $order_status != '-1' and $order_status != "'-1'")$sql .= " AND posts.post_status IN (".$order_status.")";
				if($hide_order_status  && $hide_order_status != '-1' and $hide_order_status != "'-1'")$sql .= " AND posts.post_status NOT IN (".$hide_order_status.")";
				
				//$sql .= " Group BY order_status ORDER BY total_amount DESC";
				
				$sql = apply_filters("ic_commerce_report_page_where_query", $sql, $request, $type, $page, $report_name, $columns);
				
				$group_sql = " Group BY order_status";
				
				$sql .= apply_filters("ic_commerce_report_page_group_query", $group_sql, $request, $type, $page, $report_name, $columns);	
				
				$sort_by 	= isset($request['sort_by']) ? $request['sort_by'] : 'total_amount';
				
				$order_by 	= isset($request['order_by']) ? $request['order_by'] : 'DESC';				
				
				$order_sql = " ORDER BY {$sort_by} {$order_by}";
				
				$sql .= apply_filters("ic_commerce_report_page_order_query", $order_sql, $request, $type, $page, $report_name, $columns);

				
				$this->items_query = $sql;
			}else{
				$sql = $this->items_query;
			}
						
			$order_items = $this->get_query_items($type,$sql);
			if($type == 'limit_row' || $type == 'all_row' or $type == 'all_row_total'){
				if($this->constants['post_order_status_found'] == 1 ){
				
					$order_statuses = $this->ic_get_order_statuses();
					
					foreach($order_items as $key  => $value){
						$order_items[$key]->order_status = isset($order_statuses[$value->order_status]) ? $order_statuses[$value->order_status] : '';
					}
				}
			}
			
			return $order_items;
		}
		
		
		var $recent_order_query	 	= NULL;
		var $all_row_result 		= NULL;
		var $order_meta				= array();
		/* TAB 6*/
		function ic_commerce_custom_all_recent_order_query($type = 'limit_row', $columns = array(), $report_name = "", $total_columns = array()){			
				global $wpdb;				
				$request = $this->get_all_request();extract($request);
				
				if($type == 'total_row'){	
					$columns_sql = "SELECT ";					
					$columns_sql .= " 	  
						posts.ID AS order_id, 
						DATE_FORMAT(posts.post_date,'%m/%d/%Y') AS order_date
						,(postmeta3.meta_value + 0)		AS total_amount
						,posts.post_status 				AS order_status
						,customer_user.meta_value 		AS customer_user
						,posts.post_date 				AS order_created
					";			
				}else{
					$columns_sql = "SELECT ";					
					$columns_sql .= " 	  
						posts.ID AS order_id, 
						DATE_FORMAT(posts.post_date,'%m/%d/%Y') AS order_date
						,(postmeta3.meta_value + 0)		AS total_amount
						,posts.post_status 				AS order_status
						,customer_user.meta_value		AS customer_user
						,posts.post_date 				AS order_created
					";
				}
				
				$columns_sql .= ", posts.post_status As post_status";
				
				$columns_sql = apply_filters("ic_commerce_report_page_select_query", $columns_sql, $request, $type, $page, $report_name, $columns);				
				
				if(!$this->recent_order_query){	
						$order_status	= $this->get_string_multi_request('order_status',$order_status, "-1");
						$hide_order_status	= $this->get_string_multi_request('hide_order_status',$hide_order_status, "-1");
									
						$sql = " FROM {$wpdb->prefix}posts as posts 
						LEFT JOIN  {$wpdb->prefix}postmeta as postmeta3 ON postmeta3.post_id=posts.ID";
						
						if(strlen($order_status_id)>0 && $order_status_id != "-1" && $order_status_id != "no" && $order_status_id != "all"){
								$sql .= " 
								LEFT JOIN  {$wpdb->prefix}term_relationships 	as term_relationships 	ON term_relationships.object_id		=	posts.ID
								LEFT JOIN  {$wpdb->prefix}term_taxonomy 		as term_taxonomy 		ON term_taxonomy.term_taxonomy_id	=	term_relationships.term_taxonomy_id";
						}
						
						$sql .= " LEFT JOIN  {$wpdb->prefix}postmeta as customer_user 	ON customer_user.post_id		=	posts.ID";
						
						$sql = apply_filters("ic_commerce_report_page_join_query", $sql, $request, $type, $page, $report_name, $columns);
						
						$sql .= " WHERE  posts.post_type='shop_order'
						AND postmeta3.meta_key='_order_total'";
						
						$sql .= " AND customer_user.meta_key = '_customer_user'";
						
						if(strlen($order_status_id)>0 && $order_status_id != "-1" && $order_status_id != "no" && $order_status_id != "all"){
							$sql .= " AND  term_taxonomy.term_id IN ({$order_status_id})";
						}
						
						/*if ($start_date != NULL &&  $end_date !=NULL){
							$sql .= " AND DATE(posts.post_date) BETWEEN '".$start_date."' AND '". $end_date ."'";
						}*/
						
						if($order_date_field_key == "post_date" || $order_date_field_key == "post_modified"){
							if ($start_date != NULL &&  $end_date !=NULL){
								$sql .= " AND DATE(posts.{$order_date_field_key}) BETWEEN '".$start_date."' AND '". $end_date ."'";
							}
						}
						
						if(strlen($publish_order)>0 && $publish_order != "-1" && $publish_order != "no" && $publish_order != "all"){
							$in_post_status		= str_replace(",","','",$publish_order);
							$sql .= " AND  posts.post_status IN ('{$in_post_status}')";
						}
						
						
						if($order_status  && $order_status != '-1' and $order_status != "'-1'")$sql .= " AND posts.post_status IN (".$order_status.")";
						
						if($hide_order_status  && $hide_order_status != '-1' and $hide_order_status != "'-1'")$sql .= " AND posts.post_status NOT IN (".$hide_order_status.")";
						
						$sql = apply_filters("ic_commerce_report_page_where_query", $sql, $request, $type, $page, $report_name, $columns);
				
						$this->recent_order_query = $sql;						
						
						$sql = "";
				}else{
					$sql = $this->recent_order_query;
				}
				
				$sql = $columns_sql;		
				$sql .= $this->recent_order_query;
				
				//$this->print_sql($sql);
				
				$group_sql = " GROUP BY posts.ID";
				
				$sql .= apply_filters("ic_commerce_report_page_group_query", $group_sql, $request, $type, $page, $report_name, $columns);
				
				
				$wpdb->flush(); 				
				$wpdb->query("SET SQL_BIG_SELECTS=1");					
			
				if($type == 'limit_row'){
					
					$sort_by 	= isset($request['sort_by']) ? $request['sort_by'] : 'total_amount';
				
					$order_by 	= isset($request['order_by']) ? $request['order_by'] : 'DESC';				
					
					$order_sql = " ORDER BY {$sort_by} {$order_by}";
					
					//$order_sql = " Order By posts.post_date DESC ";
					$sql .= apply_filters("ic_commerce_report_page_order_query", $order_sql, $request, $type, $page, $report_name, $columns);
					
					$sql .= " LIMIT $start, $limit";				
					$order_items = $wpdb->get_results($sql);
					//echo mysql_error();
				}
							
				if($type == 'all_row'){
					$sort_by 	= isset($request['sort_by']) ? $request['sort_by'] : 'total_amount';
				
					$order_by 	= isset($request['order_by']) ? $request['order_by'] : 'DESC';				
					
					$order_sql = " ORDER BY {$sort_by} {$order_by}";
					
					$sql .= apply_filters("ic_commerce_report_page_order_query", $order_sql, $request, $type, $page, $report_name, $columns);
					$order_items = $wpdb->get_results($sql);
					$this->all_row_result = $order_items;
					//echo mysql_error();
				}
				
				if($type == 'limit_row' || $type == 'all_row' or $type == 'all_row_total'){
						if(count($order_items)>0){
							$extra_meta_keys 	= apply_filters('ic_commerce_report_page_extra_meta_keys', array('order_total','order_shipping','cart_discount','order_discount','total_discount','order_tax','order_shipping_tax','total_tax','billing_first_name','billing_last_name','order_currency'),$request, $type, $page, $report_name, $columns);
							$post_ids 			= $this->get_items_id_list($order_items,'order_id');
							$postmeta_datas 	= $this->get_postmeta($post_ids, $columns,$extra_meta_keys);
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
									
									$order_items[$key]->billing_first_name	= isset($order_items[$key]->billing_first_name)	? $order_items[$key]->billing_first_name 	: '';
									$order_items[$key]->billing_last_name	= isset($order_items[$key]->billing_last_name)	? $order_items[$key]->billing_last_name 	: '';
									$order_items[$key]->billing_name		= $order_items[$key]->billing_first_name.' '.$order_items[$key]->billing_last_name;
							}
						}
						
						$order_items 		= apply_filters("ic_commerce_all_report_recent_order_data_items", $order_items, $report_name);
						$order_items 		= apply_filters("ic_commerce_report_page_data_items",  $order_items, $request, $type, $page, $report_name);
				}
				
				if($type == 'total_row'){
					if($this->all_row_result){
						if($count_generated == 1){
							$order_items = $this->create_summary($request);
						}else{
							$order_items = $this->all_row_result;
							$summary = $this->get_count_total($order_items,'total_amount');				
							$order_items = $summary;
						}
						
					}else{					
						if($count_generated == 1 || ($p > 1)){
							$order_items = $this->create_summary($request);
							
						}else{
							$order_items = $wpdb->get_results($sql);
							//echo mysql_error();
							if(count($order_items)>0){
								$extra_meta_keys 	= apply_filters('ic_commerce_report_page_extra_meta_keys', array('order_total','order_shipping','cart_discount','order_discount','order_tax','order_shipping_tax','total_tax'),$request, $type, $page, $report_name, $columns);
								$post_ids 			= $this->get_items_id_list($order_items,'order_id');
								$postmeta_datas 	= $this->get_postmeta($post_ids, $total_columns,$extra_meta_keys,'total');
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
							
							$report_name 		= $this->get_request('report_name');
							$total_columns 		= $this->result_columns($report_name);
							
							$order_items 	= apply_filters("ic_commerce_report_page_data_items",  $order_items, $request, $type, $page, $report_name);
							
							$grid_object		= $this->get_grid_object();//Added 20150223
							$order_items		= $grid_object->create_grid_items($total_columns,$order_items);
							
							
							//$this->print_array($order_items);
							
							$summary = $this->get_count_total($order_items,'total_amount');				
							$order_items = $summary;
							
						}	
						
					}
					
					return $order_items;
				}
				
				return $order_items;		
		}
		
		/* TAB 7 */
		/*All Top Coupan*/
		function ic_commerce_custom_all_coupon_query($type = 'limit_row', $columns = array(), $report_name = ""){
			global $wpdb;
			
			$request = $this->get_all_request();
			
			if(!isset($this->items_query)){
				extract($request);
				
				$order_status			= $this->get_string_multi_request('order_status',$order_status, "-1");
				$hide_order_status		= $this->get_string_multi_request('hide_order_status',$hide_order_status, "-1");
				$coupon_codes			= $this->get_string_multi_request('coupon_codes',$coupon_codes, "-1");
				$coupon_discount_types	= $this->get_string_multi_request('coupon_discount_types',$coupon_discount_types, "-1");
				$country_code			= $this->get_string_multi_request('country_code',$country_code, "-1");
									
									
										
				$sql = " SELECT ";
				$sql .= "
				woocommerce_order_items.order_item_name				AS		'order_item_name',
				woocommerce_order_items.order_item_name				AS		'coupon_code', 
				SUM(woocommerce_order_itemmeta.meta_value) 			AS		'total_amount', 
				woocommerce_order_itemmeta.meta_value 				AS 		'coupon_amount' , 
				Count(*) 											AS 		'coupon_count'";
				
				if($report_name == "coupon_couontry_page"){
					$sql .= ", billing_country.meta_value 			AS billing_country";
					//echo $sort_by;					
					switch($sort_by){
						case "coupon_country":
							$sql .= ", CONCAT(woocommerce_order_items.order_item_name, ' ', billing_country.meta_value) 			AS coupon_country";
							break;
						case "country_coupon":
							$sql .= ", CONCAT(billing_country.meta_value, ' ', woocommerce_order_items.order_item_name) 			AS country_coupon";
							break;
						
					}
				}
				
				$sql = apply_filters("ic_commerce_report_page_select_query", $sql, $request, $type, $page, $report_name, $columns);				
				
				$sql .= "
				FROM {$wpdb->prefix}woocommerce_order_items as woocommerce_order_items 
				LEFT JOIN	{$wpdb->prefix}posts	as posts ON posts.ID = woocommerce_order_items.order_id
				LEFT JOIN  {$wpdb->prefix}woocommerce_order_itemmeta as woocommerce_order_itemmeta ON woocommerce_order_itemmeta.order_item_id=woocommerce_order_items.order_item_id
				";
				
				if($report_name == "coupon_couontry_page"){
					$sql .= " LEFT JOIN	{$wpdb->prefix}postmeta	as billing_country ON billing_country.post_id = woocommerce_order_items.order_id AND billing_country.meta_key = '_billing_country'";
				}
				
				if($coupon_discount_types && $coupon_discount_types != "-1"){
					$sql .= " LEFT JOIN	{$wpdb->prefix}posts	as coupons ON coupons.post_title = woocommerce_order_items.order_item_name";
					$sql .= " LEFT JOIN	{$wpdb->prefix}postmeta	as coupon_discount_type ON coupon_discount_type.post_id = coupons.ID";
				}
				
				if(strlen($order_status_id)>0 && $order_status_id != "-1" && $order_status_id != "no" && $order_status_id != "all"){
						$sql .= " 
						LEFT JOIN  {$wpdb->prefix}term_relationships 	as term_relationships 	ON term_relationships.object_id		=	posts.ID
						LEFT JOIN  {$wpdb->prefix}term_taxonomy 		as term_taxonomy 		ON term_taxonomy.term_taxonomy_id	=	term_relationships.term_taxonomy_id";
				}
				
				$sql = apply_filters("ic_commerce_report_page_join_query", $sql, $request, $type, $page, $report_name, $columns);
				
				$sql .= "
				WHERE 
				posts.post_type 								=	'shop_order'
				AND woocommerce_order_items.order_item_type		=	'coupon' 
				AND woocommerce_order_itemmeta.meta_key			=	'discount_amount'";
				
				if($coupon_discount_types && $coupon_discount_types != "-1"){
					$sql .= " AND coupons.post_type 				=	'shop_coupon'";
					$sql .= " AND coupon_discount_type.meta_key		=	'discount_type'";
				}
				
				/*if ($start_date != NULL &&  $end_date !=NULL){
					$sql .= " AND DATE(posts.post_date) BETWEEN '".$start_date."' AND '". $end_date ."'";
				}*/
				
				if($order_date_field_key == "post_date" || $order_date_field_key == "post_modified"){
					if ($start_date != NULL &&  $end_date !=NULL){
						$sql .= " AND DATE(posts.{$order_date_field_key}) BETWEEN '".$start_date."' AND '". $end_date ."'";
					}
				}
				
				if(strlen($order_status_id)>0 && $order_status_id != "-1" && $order_status_id != "no" && $order_status_id != "all"){
					$sql .= " AND  term_taxonomy.term_id IN ({$order_status_id})";
				}
				
				if(strlen($publish_order)>0 && $publish_order != "-1" && $publish_order != "no" && $publish_order != "all"){
					$in_post_status		= str_replace(",","','",$publish_order);
					$sql .= " AND  posts.post_status IN ('{$in_post_status}')";
				}
				
				if($coupon_code && $coupon_code != "-1"){
					$sql .= " AND (woocommerce_order_items.order_item_name IN ('{$coupon_code}') OR woocommerce_order_items.order_item_name LIKE '%{$coupon_code}%')";
				}
				
				if($coupon_codes && $coupon_codes != "-1"){
					$sql .= " AND woocommerce_order_items.order_item_name IN ({$coupon_codes})";
				}
				
				if($coupon_discount_types && $coupon_discount_types != "-1"){
					$sql .= " AND coupon_discount_type.meta_value IN ({$coupon_discount_types})";
				}
				
				if($country_code && $country_code != "-1"){
					$sql .= " AND billing_country.meta_value IN ({$country_code})";
				}
				
				if($order_status  && $order_status != '-1' and $order_status != "'-1'")$sql .= " AND posts.post_status IN (".$order_status.")";
				
				if($hide_order_status  && $hide_order_status != '-1' and $hide_order_status != "'-1'")$sql .= " AND posts.post_status NOT IN (".$hide_order_status.")";
				
				$sql = apply_filters("ic_commerce_report_page_where_query", $sql, $request, $type, $page, $report_name, $columns);
				
				if($request['report_name'] == "coupon_couontry_page"){
					
					$group_sql = " Group BY billing_country.meta_value, woocommerce_order_items.order_item_name";
					
					$sql .= apply_filters("ic_commerce_report_page_group_query", $group_sql, $request, $type, $page, $report_name, $columns);
					
				}else{
					
					$group_sql = " Group BY woocommerce_order_items.order_item_name";
					
					$sql .= apply_filters("ic_commerce_report_page_group_query", $group_sql, $request, $type, $page, $report_name, $columns);
					
				}	
				
				$sort_by 	= isset($request['sort_by']) ? $request['sort_by'] : 'total_amount';
					
				$order_by 	= isset($request['order_by']) ? $request['order_by'] : 'DESC';				
				
				$order_sql = " ORDER BY {$sort_by} {$order_by}";
				
				$sql .= apply_filters("ic_commerce_report_page_order_query", $order_sql, $request, $type, $page, $report_name, $columns);				
				
				$this->items_query = $sql;
				
			}else{
				$sql = $this->items_query;
			}
			
			$order_items = $this->get_query_items($type,$sql);
			
			if(count($order_items) > 0){
				if($request['report_name'] == "coupon_couontry_page"){
					if($type == "limit_row" or $type == 'all_row' or $type == 'all_row_total'){
						$countries		= 	$this->get_wc_countries();
						//$countries		=	isset($countries->countries) ? $countries->countries : array();						
						foreach($order_items as $orderitem_key => $orderitem){
							$billing_country_code = isset($orderitem->billing_country) ? $orderitem->billing_country : '';
							
							if($billing_country_code == "US"){
								$billing_country_name = isset($countries->countries[$billing_country_code]) ? $countries->countries[$billing_country_code] : $billing_country_code;	
							}else{
								$billing_country_name = isset($countries->countries[$billing_country_code]) ? $countries->countries[$billing_country_code]." ({$billing_country_code})" : $billing_country_code;	
							}
							$order_items[$orderitem_key]->billing_country = $billing_country_name;
						}						
					}
				}
			}
			
			return $order_items;
		}
		
		//New Custom Change ID 20141009
		/* TAB 8 */
		/*Advance Prodluct*/
		//ic_commerce_custom_all_product_advance_query
		function ic_commerce_custom_all_product_advance_query($type = 'limit_row', $columns = array(), $report_name = ""){
			global $wpdb;
			
			if(!isset($this->items_query)){
				$request = $this->get_all_request();extract($request);
				
				$order_status	= $this->get_string_multi_request('order_status',$order_status, "-1");
				$hide_order_status	= $this->get_string_multi_request('hide_order_status',$hide_order_status, "-1");
				
				$sql = " SELECT ";
				$sql .= "	
							woocommerce_order_items.order_item_name 		AS 'product_name'
							,woocommerce_order_items.order_item_id
							,SUM(woocommerce_order_itemmeta.meta_value) 	AS 'quantity'
							,SUM(woocommerce_order_itemmeta6.meta_value) 	AS 'total_amount'
							,SUM(woocommerce_order_itemmeta8.meta_value) 	AS 'product_vat_total'
							,SUM(woocommerce_order_itemmeta8.meta_value)/SUM(woocommerce_order_itemmeta.meta_value)  	AS 'product_vat_par_item'
							,SUM(woocommerce_order_itemmeta6.meta_value)/SUM(woocommerce_order_itemmeta.meta_value) 	AS 'product_rate_exculude_tax'
							,woocommerce_order_itemmeta7.meta_value 		AS product_id						
							,SUM(woocommerce_order_itemmeta6.meta_value) 	AS 'total_price_exculude_tax'						
							,(SUM(woocommerce_order_itemmeta8.meta_value) + SUM(woocommerce_order_itemmeta6.meta_value) )	AS 'total_amount'						
							,DATE(shop_order.post_date) 					AS post_date 
							
							";
				$sql = apply_filters("ic_commerce_report_page_select_query", $sql, $request, $type, $page, $report_name, $columns);				
				
				$sql .= "
							FROM {$wpdb->prefix}woocommerce_order_items as woocommerce_order_items						
							LEFT JOIN  {$wpdb->prefix}woocommerce_order_itemmeta as woocommerce_order_itemmeta ON woocommerce_order_itemmeta.order_item_id=woocommerce_order_items.order_item_id
							LEFT JOIN  {$wpdb->prefix}woocommerce_order_itemmeta as woocommerce_order_itemmeta6 ON woocommerce_order_itemmeta6.order_item_id=woocommerce_order_items.order_item_id
							LEFT JOIN  {$wpdb->prefix}woocommerce_order_itemmeta as woocommerce_order_itemmeta7 ON woocommerce_order_itemmeta7.order_item_id=woocommerce_order_items.order_item_id
							LEFT JOIN  {$wpdb->prefix}woocommerce_order_itemmeta as woocommerce_order_itemmeta8 ON woocommerce_order_itemmeta8.order_item_id=woocommerce_order_items.order_item_id
							";
				
				if($category_id  && $category_id != "-1") {
						$sql .= " 	
							LEFT JOIN  {$wpdb->prefix}term_relationships 	as term_relationships 	ON term_relationships.object_id		=	woocommerce_order_itemmeta7.meta_value 
							LEFT JOIN  {$wpdb->prefix}term_taxonomy 		as term_taxonomy 		ON term_taxonomy.term_taxonomy_id	=	term_relationships.term_taxonomy_id
							LEFT JOIN  {$wpdb->prefix}terms 				as terms 				ON terms.term_id					=	term_taxonomy.term_id";
				}
				
				if($order_status_id  && $order_status_id != "-1") {
						$sql .= " 	
							LEFT JOIN  {$wpdb->prefix}term_relationships	as term_relationships2 	ON term_relationships2.object_id	=	woocommerce_order_items.order_id
							LEFT JOIN  {$wpdb->prefix}term_taxonomy			as term_taxonomy2 		ON term_taxonomy2.term_taxonomy_id	=	term_relationships2.term_taxonomy_id
							LEFT JOIN  {$wpdb->prefix}terms					as terms2 				ON terms2.term_id					=	term_taxonomy2.term_id";
				}
				
				
				
				
				$sql .= " 
							LEFT JOIN  {$wpdb->prefix}posts as shop_order ON shop_order.id=woocommerce_order_items.order_id";
				
				$sql = apply_filters("ic_commerce_report_page_join_query", $sql, $request, $type, $page, $report_name, $columns);
							
				$sql .= "
							WHERE woocommerce_order_itemmeta.meta_key	= '_qty'
							AND woocommerce_order_itemmeta6.meta_key	= '_line_total' 
							AND woocommerce_order_itemmeta7.meta_key 	= '_product_id'
							AND woocommerce_order_itemmeta8.meta_key 	= '_line_tax'
							AND shop_order.post_type					= 'shop_order'
							";
							
				
				
				/*if ($start_date != NULL &&  $end_date !=NULL){
					$sql .= " 
							AND (DATE(shop_order.post_date) BETWEEN '".$start_date."' AND '". $end_date ."')";
				}*/
				
				if($order_date_field_key == "post_date" || $order_date_field_key == "post_modified"){
					if ($start_date != NULL &&  $end_date !=NULL){
						$sql .= " AND DATE(posts.{$order_date_field_key}) BETWEEN '".$start_date."' AND '". $end_date ."'";
					}
				}
				
				if($product_id  && $product_id != "-1") 
					$sql .= "
							AND woocommerce_order_itemmeta7.meta_value IN (".$product_id .")";	
				
				if($category_id  && $category_id != "-1") 
					$sql .= "
							AND terms.term_id IN (".$category_id .")";	
				
				if($order_status_id  && $order_status_id != "-1") 
					$sql .= " 
							AND terms2.term_id IN (".$order_status_id .")";
				
			
				if($order_status  && $order_status != '-1' and $order_status != "'-1'")$sql .= " AND shop_order.post_status IN (".$order_status.")";//changed 20141013
				
				if($hide_order_status  && $hide_order_status != '-1' and $hide_order_status != "'-1'")$sql .= " AND shop_order.post_status NOT IN (".$hide_order_status.")";//changed 20141013
				
				$sql = apply_filters("ic_commerce_report_page_where_query", $sql, $request, $type, $page, $report_name, $columns);
				
				$group_sql = " GROUP BY  woocommerce_order_itemmeta7.meta_value";			
				
				$sql .= apply_filters("ic_commerce_report_page_group_query", $group_sql, $request, $type, $page, $report_name, $columns);	
				
				$order_sql = " ORDER BY total_amount DESC";
				
				$sql .= apply_filters("ic_commerce_report_page_order_query", $order_sql, $request, $type, $page, $report_name, $columns);	
				
				$this->items_query = $sql;
				
			}else{
				$sql = $this->items_query;
			}
			$order_items = $this->get_query_items($type,$sql);
			return $order_items;
		}
		
		
		function get_refunded_amount(){
				global $wpdb;
				
				$request 				= $this->get_all_request();extract($request);
				
				$sql 					= "";				
				$order_status			= $this->get_string_multi_request('order_status',$order_status, "-1");
				$hide_order_status		= $this->get_string_multi_request('hide_order_status',$hide_order_status, "-1");
				
				$sql = "SELECT ";
				
				$sql .= " SUM(refund_amount.meta_value)  AS refund_amount";
				
				$sql .= " ,shop_order_refund.ID  AS refund_order_id";
				
				$sql .= " ,shop_order_refund.post_parent  AS refunded_order_id";
				
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
						$sql .= ", DATE(shop_order.post_modified) as group_column";
						$sql .= ", DATE(shop_order.post_modified) as group_date";
						$sql .= ", DATE(shop_order.post_modified) as order_column";
						break;
					case "monthly":
						$sql .= ", CONCAT(MONTHNAME(shop_order.post_modified) , ' ',YEAR(shop_order.post_modified)) as group_column";
						$sql .= ", DATE(shop_order.post_modified) as order_column";
						break;
					case "yearly":
						$sql .= ", YEAR(shop_order.post_modified)as group_column";
						$sql .= ", DATE(shop_order.post_modified) as order_column";
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
				
				if($order_date_field_key == "post_date" || $order_date_field_key == "post_modified"){
					if ($start_date != NULL &&  $end_date !=NULL){
						$sql .= " AND DATE(shop_order_refund.{$order_date_field_key}) BETWEEN '".$start_date."' AND '". $end_date ."'";
					}
				}
				
				//$sql .= " AND shop_order.post_status NOT IN ('wc-refunded')";
				
				if($order_status  && $order_status != '-1' and $order_status != "'-1'"){
					$sql .= " AND shop_order.post_status IN (".$order_status.")";
				}
				
				$sql .= " GROUP BY  group_column";
				
				$sql .= " ORDER BY order_column DESC";
				
				$items = $wpdb->get_results($sql);
				
				$return = array();
				foreach($items as $item_key => $item){
					$return[$item->group_column] = $item->refund_amount;
				}
				
				//$this->print_array($return);
				
				echo $wpdb->last_error;
				
				return $return;
		}
		
		
		/*
			* Function Name ic_commerce_custom_all_manual_refund_detail_query
			*
			* Returns rows of manual refund summary
			*
			* @param string $type
			*
			* @param array $columns
			*
			* @param string $report_name
			*
			* @return array|object $order_items
			*			
		*/
		function ic_commerce_custom_all_manual_refund_detail_query($type = 'limit_row', $columns = array(), $report_name = ""){
			global $wpdb;			
			
			if(!isset($this->items_query)){
					$request 				= $this->get_all_request();extract($request);
					$sql 					= "";				
					$order_status			= $this->get_string_multi_request('order_status',$order_status, "-1");
					$hide_order_status		= $this->get_string_multi_request('hide_order_status',$hide_order_status, "-1");
					
					if($refund_status_type == "part_refunded"){
						$sql = "SELECT 
						
						posts.ID 						as refund_id
						
						,posts.post_status 				as refund_status
						,posts.post_date 				as refund_date				
						,posts.post_excerpt 			as refund_note				
						,posts.post_author				as customer_user
						
						,postmeta.meta_value 			as refund_amount
						,SUM(ROUND(postmeta.meta_value,2)) 		as total_amount
						
						,shop_order.ID 					as order_id
						,shop_order.ID 					as order_id_number
						,shop_order.post_status 		as order_status
						,shop_order.post_date 			as order_date
						,COUNT(posts.ID) 				as refund_count";
										
						
						$group_sql = "";
						switch($group_by){
							case "refund_id":
								$group_sql .= ", posts.ID as group_column";
								$group_sql .= ", posts.ID as order_column";
								break;
							case "order_id":
								$group_sql .= ", shop_order.ID as group_column";
								$group_sql .= ", shop_order.post_author as order_column";
								break;
							case "refunded":
								$group_sql .= ", posts.post_author as group_column";
								$group_sql .= ", posts.post_author as order_column";
								break;
							case "daily":
								$group_sql .= ", DATE(posts.post_date) as group_column";
								$group_sql .= ", DATE(posts.post_date) as group_date";
								$group_sql .= ", DATE(posts.post_date) as order_column";
								break;
							case "monthly":
								$group_sql .= ", CONCAT(MONTHNAME(posts.post_date) , ' ',YEAR(posts.post_date)) as group_column";
								$group_sql .= ", DATE(posts.post_date) as order_column";
								break;
							case "yearly":
								$group_sql .= ", YEAR(posts.post_date)as group_column";
								$group_sql .= ", DATE(posts.post_date) as order_column";
								break;
							default:
								$group_sql .= ", posts.ID as group_column";
								$group_sql .= ", posts.ID as order_column";
								break;
							
						}
					
						$sql .= $group_sql;
						
						$sql = apply_filters("ic_commerce_report_page_select_query", $sql, $request, $type, $page, $report_name, $columns);				
						
						$sql .= " FROM {$wpdb->prefix}posts as posts";
																
						$sql .= " LEFT JOIN  {$wpdb->prefix}postmeta as postmeta ON postmeta.post_id	=	posts.ID";
												
						$sql .= " LEFT JOIN  {$wpdb->prefix}posts as shop_order ON shop_order.ID	=	posts.post_parent";

					}else{
						
						$sql = "SELECT 						
						SUM(ROUND(postmeta.meta_value,2)) 				as total_amount
						,shop_order_refund.post_author			as customer_user
						,shop_order_refund.ID					as refund_id
						,shop_order_refund.post_status 			as refund_status
						,shop_order_refund.post_date 			as refund_date	
						,shop_order.ID 							as order_id
						,shop_order.ID 							as order_id_number
						,shop_order.post_status 				as order_status
						,shop_order.post_date					as order_date
						,COUNT(shop_order_refund.ID) 			as refund_count
						";
						
						$group_sql = "";
						switch($group_by){
							case "refund_id":
								$group_sql .= ", shop_order_refund.ID 	as group_column";
								$group_sql .= ", shop_order_refund.ID 	as order_column";
								$group_sql .= ", shop_order_refund.post_excerpt 	as refund_note";
								break;
							case "order_id":
								$group_sql .= ", shop_order.ID as group_column";
								$group_sql .= ", shop_order.ID as order_column";
								break;
							case "refunded":
								$group_sql .= ", shop_order_refund.post_author as group_column";
								$group_sql .= ", shop_order_refund.post_author as order_column";
								break;
							case "daily":
								$group_sql .= ", DATE(shop_order_refund.post_date) as group_column";
								$group_sql .= ", DATE(shop_order_refund.post_date) as group_date";
								$group_sql .= ", DATE(shop_order_refund.post_date) as order_column";
								break;
							case "monthly":
								$group_sql .= ", CONCAT(MONTHNAME(shop_order_refund.post_date) , ' ',YEAR(shop_order_refund.post_date)) as group_column";
								$group_sql .= ", DATE(shop_order_refund.post_date) as order_column";
								break;
							case "yearly":
								$group_sql .= ", YEAR(shop_order_refund.post_date)as group_column";
								$group_sql .= ", DATE(shop_order_refund.post_date) as order_column";
								break;
							default:
								$group_sql .= ", shop_order.ID as group_column";
								$group_sql .= ", shop_order.ID as order_column";
								break;
							
						}
						
						$sql .= $group_sql;
						$sql = apply_filters("ic_commerce_report_page_select_query", $sql, $request, $type, $page, $report_name, $columns);				
						$sql .= " FROM {$wpdb->prefix}posts as shop_order_refund";
						
						$sql .= " LEFT JOIN  {$wpdb->prefix}posts as shop_order ON shop_order.ID = shop_order_refund.post_parent";
						$sql .= " LEFT JOIN  {$wpdb->prefix}postmeta as postmeta ON postmeta.post_id	=	shop_order_refund.ID";
							
					}
						
						
						
					if($order_status_id  && $order_status_id != "-1") {
						$sql .= " 	
							LEFT JOIN  {$wpdb->prefix}term_relationships	as term_relationships2 	ON term_relationships2.object_id	=	shop_order.ID
							LEFT JOIN  {$wpdb->prefix}term_taxonomy			as term_taxonomy2 		ON term_taxonomy2.term_taxonomy_id	=	term_relationships2.term_taxonomy_id
							LEFT JOIN  {$wpdb->prefix}terms					as terms2 				ON terms2.term_id					=	term_taxonomy2.term_id";
					}
					
					if($refund_status_type == "part_refunded"){
						
						$sql = apply_filters("ic_commerce_report_page_join_query", $sql, $request, $type, $page, $report_name, $columns);
						
						$sql .= " WHERE posts.post_type = 'shop_order_refund' AND  postmeta.meta_key='_order_total'";						
						
						$order_date_field_key = isset($_REQUEST['order_date_field_key']) ? $_REQUEST['order_date_field_key'] : 'post_date';
						if($order_date_field_key == "post_date" || $order_date_field_key == "post_modified"){
							if ($start_date != NULL &&  $end_date !=NULL){
								$sql .= " AND DATE(posts.{$order_date_field_key}) BETWEEN '".$start_date."' AND '". $end_date ."'";
							}
						}
						
						if($order_status_id  && $order_status_id != "-1"){
							$refunded_id 	= $this->get_old_order_status(array('refunded'), array('wc-refunded'));
							$refunded_id    = implode(",".$refunded_id);
							$sql .= " AND terms2.term_id NOT IN (".$refunded_id .")";
							
							if($order_status_id  && $order_status_id != "-1"){
								$sql .= " AND terms2.term_id IN (".$order_status_id .")";
							}
						}else{
							$sql .= " AND shop_order.post_status NOT IN ('wc-refunded')";
							if($order_status  && $order_status != '-1' and $order_status != "'-1'"){
								$sql .= " AND shop_order.post_status IN (".$order_status.")";
							}
						}
					}else{
						
						
						
						$sql = apply_filters("ic_commerce_report_page_join_query", $sql, $request, $type, $page, $report_name, $columns);
						
						$sql .= " WHERE shop_order_refund.post_type = 'shop_order_refund'";
						
						$sql .= " AND  postmeta.meta_key='_order_total'";
					
						$order_date_field_key = 'post_date';
						if($order_date_field_key == "post_date" || $order_date_field_key == "post_modified"){
							if ($start_date != NULL &&  $end_date !=NULL){
								$sql .= " AND DATE(shop_order_refund.{$order_date_field_key}) BETWEEN '".$start_date."' AND '". $end_date ."'";
							}
						}
						
						if($order_status_id  && $order_status_id != "-1"){
							$refunded_id 	= $this->get_old_order_status(array('refunded'), array('wc-refunded'));
							$refunded_id    = implode(",",$refunded_id);
							$sql .= " AND terms2.term_id IN (".$refunded_id .")";
						}else{
							$sql .= " AND shop_order.post_status IN ('wc-refunded')";
						}
					}					
					
					if($hide_order_status  && $hide_order_status != '-1' and $hide_order_status != "'-1'")$sql .= " AND shop_order.post_status NOT IN (".$hide_order_status.")";
					
					$sql = apply_filters("ic_commerce_report_page_where_query", $sql, $request, $type, $page, $report_name, $columns);
				
					$group_sql = " GROUP BY  group_column";			
					
					$sql .= apply_filters("ic_commerce_report_page_group_query", $group_sql, $request, $type, $page, $report_name, $columns);	
					
					$order_sql = " ORDER BY order_column DESC";
					
					$sql .= apply_filters("ic_commerce_report_page_order_query", $order_sql, $request, $type, $page, $report_name, $columns);
					
					//$this->print_sql($sql);
									
					$this->items_query = $sql;				
			}else{
					$sql = $this->items_query;
			}
			
			$order_items = $this->get_query_items($type,$sql,"total_amount");
			
			return $order_items;
		}
		
		function ___ic_commerce_report_page_data_items($order_items = '', $request = '', $type = '', $page = '', $report_name = ''){
			
			return $order_items;
			
			if($report_name != 'manual_refund_detail_page') return $order_items;
			
			$refund_status_type = $this->get_request('refund_status_type');
			
			if($refund_status_type != 'total_refunded') return $order_items;
			
			$part_refunded_amount = $this->get_refunded_amount();
			
			foreach($order_items as $order_item_key => $order_item){
				$group_column 	= isset($order_item->group_column) ? $order_item->group_column : 0;
				
				if(isset($part_refunded_amount[$group_column])){
					$full_refund 	= isset($order_item->total_amount) ? $order_item->total_amount : 0;
					$part_refund 	= isset($part_refunded_amount[$group_column]) ? $part_refunded_amount[$group_column] : 0;
										
					$order_items[$order_item_key]->part_order_refund_amount  = $part_refund;
					$order_items[$order_item_key]->order_refund_amount  = $full_refund;
					$order_items[$order_item_key]->total_amount = $full_refund + $part_refund;
					
					unset($part_refunded_amount[$group_column]);
				}
			}
			
			$this->print_array($part_refunded_amount);
			
			return $order_items;
		}
		
		function ic_commerce_custom_all_part_refund_detail_query($type = 'limit_row', $columns = array(), $report_name = ""){
			global $wpdb;
			$request 				= $this->get_all_request();extract($request);
			
			$sql 					= "";	
						
			$order_status			= $this->get_string_multi_request('order_status',$order_status, "-1");
			
			$hide_order_status		= $this->get_string_multi_request('hide_order_status',$hide_order_status, "-1");
			
			//echo $refund_status_type;
			
			$sql = "SELECT 
				
				posts.ID 						as refund_id
				
				,posts.post_status 				as refund_status
				,posts.post_date 				as refund_date				
				,posts.post_excerpt 			as refund_note				
				,posts.post_author				as customer_user
				
				,postmeta.meta_value 			as refund_amount
				,SUM(postmeta.meta_value) 		as total_amount
				
				,shop_order.ID 					as order_id
				,shop_order.ID 					as order_id_number
				,shop_order.post_status 		as order_status
				,shop_order.post_date 			as order_date
				,COUNT(posts.ID) 				as refund_count
				";
								
				//echo $group_by;
				$group_sql = "";
				switch($group_by){
					case "refund_id":
						$group_sql .= ", posts.ID as group_column";
						break;
					case "order_id":
						$group_sql .= ", shop_order.ID as group_column";
						break;
					case "refunded":
						$group_sql .= ", posts.post_author as group_column";
						break;
					case "daily":
						$group_sql .= ", DATE(posts.post_date) as group_column";
						$group_sql .= ", DATE(posts.post_date) as group_date";
						break;
					case "monthly":
						$group_sql .= ", CONCAT(MONTHNAME(posts.post_date) , ' ',YEAR(posts.post_date)) as group_column";
						break;
					case "yearly":
						$group_sql .= ", YEAR(posts.post_date)as group_column";
						break;
					default:
						$group_sql .= ", posts.ID as group_column";
						break;
					
				}
				//echo  $group_sql;;
				$sql .= $group_sql;				
				
				$sql = apply_filters("ic_commerce_report_page_select_query", $sql, $request, $type, $page, $report_name, $columns);				
				
				$sql .= "
				
				FROM {$wpdb->prefix}posts as posts
								
				LEFT JOIN  {$wpdb->prefix}postmeta as postmeta ON postmeta.post_id	=	posts.ID";
				
				$sql .= " LEFT JOIN  {$wpdb->prefix}posts as shop_order ON shop_order.ID	=	posts.post_parent";
			
				
				if($order_status_id  && $order_status_id != "-1") {
					$sql .= " 	
						LEFT JOIN  {$wpdb->prefix}term_relationships	as term_relationships2 	ON term_relationships2.object_id	=	shop_order.ID
						LEFT JOIN  {$wpdb->prefix}term_taxonomy			as term_taxonomy2 		ON term_taxonomy2.term_taxonomy_id	=	term_relationships2.term_taxonomy_id
						LEFT JOIN  {$wpdb->prefix}terms					as terms2 				ON terms2.term_id					=	term_taxonomy2.term_id";
				}
				
				$sql = apply_filters("ic_commerce_report_page_join_query", $sql, $request, $type, $page, $report_name, $columns);
				
				$sql .= " WHERE posts.post_type = 'shop_order_refund' AND  postmeta.meta_key='_refund_amount'";
				
				/*if ($start_date != NULL &&  $end_date !=NULL){
					$sql .= " 
							AND (DATE(posts.post_date) BETWEEN '".$start_date."' AND '". $end_date ."')";
				}*/
				
				if($order_date_field_key == "post_date" || $order_date_field_key == "post_modified"){
					if ($start_date != NULL &&  $end_date !=NULL){
						$sql .= " AND DATE(posts.{$order_date_field_key}) BETWEEN '".$start_date."' AND '". $end_date ."'";
					}
				}
				
				if($order_status_id  && $order_status_id != "-1"){
					$refunded_id 	= $this->get_old_order_status(array('refunded'), array('wc-refunded'));
					$refunded_id    = implode(",".$refunded_id);
					$sql .= " AND terms2.term_id NOT IN (".$refunded_id .")";
					
					if($order_status_id  && $order_status_id != "-1"){
						$sql .= " AND terms2.term_id IN (".$order_status_id .")";
					}
				}else{
					$sql .= " AND shop_order.post_status NOT IN ('wc-refunded')";
					if($order_status  && $order_status != '-1' and $order_status != "'-1'"){
						$sql .= " AND shop_order.post_status IN (".$order_status.")";
					}
				}
				
				if($hide_order_status  && $hide_order_status != '-1' and $hide_order_status != "'-1'")$sql .= " AND shop_order.post_status NOT IN (".$hide_order_status.")";//changed 20141013
			
				//$sql .= " GROUP BY  group_column";			
				
				//$sql .= " ORDER BY order_date DESC";
				
				$sql = apply_filters("ic_commerce_report_page_where_query", $sql, $request, $type, $page, $report_name, $columns);
				
				$group_sql = " GROUP BY  group_column";			
				
				$sql .= apply_filters("ic_commerce_report_page_group_query", $group_sql, $request, $type, $page, $report_name, $columns);	
				
				$order_sql = " ORDER BY order_date DESC";
				
				$sql .= apply_filters("ic_commerce_report_page_order_query", $order_sql, $request, $type, $page, $report_name, $columns);	
				
				
				$order_items = $this->get_query_items($type,$sql,"total_amount");
					
				return $order_items;
		}
		
		function get_final_order_items($type,$order_items,$report_name){
			
			if($report_name == "tax_page"){
				foreach ( $order_items as $key => $order_item ) {
					$order_items[$key]->_total_tax = $order_item->_shipping_tax_amount + $order_item->_order_tax;
					//$order_items[$key]->_order_amount = $order_item->order_tax_rate>0?($order_item->_order_tax*100)/$order_item->order_tax_rate:0;				
					$order_item->_order_amount = $this->get_percentage($order_item->_order_tax,$order_item->order_tax_rate);//Added 20150206
					$order_items[$key]->tax_rate_name = isset($order_item->tax_rate_name) ? trim($order_item->tax_rate_name) : '';
					$order_items[$key]->tax_rate_name = strlen($order_item->tax_rate_name)<=0 ? $order_item->tax_rate_code : $order_item->tax_rate_name;				
					$order_items[$key]->billing_state = isset($order_item->billing_state) ? $order_item->billing_state : '';
					
					$order_items[$key]->total_amount = $order_items[$key]->_total_tax;
				}
			}
			
			if($report_name == "customer_buy_products_page"){
				if($type == 'total_row') return $order_items;
				$users = array();
				foreach ( $order_items as $key => $order_item ) {
					$users[] = $order_item->customer_id;
				}				
				$users 		= array_unique($users);
				$users_list = $this->get_customer_details($users);
				if(count($users_list)>0){
					foreach ( $order_items as $key => $order_item) {
						$customer_id = $order_item->customer_id;
						if($customer_id > 0){
							if(isset($users_list[$customer_id])){
								$order_items[$key]->billing_email = $users_list[$customer_id]->billing_email;
								$order_items[$key]->billing_name = $users_list[$customer_id]->billing_name;
							}
						}
					}
				}
			}
			return $order_items;			
		}
		
		function get_query_items($type,$sql,$total_amount = 'total_amount'){
			global  $wpdb;
			$request = $this->get_all_request();extract($request);
			$wpdb->flush(); 				
			$wpdb->query("SET SQL_BIG_SELECTS=1");
			if($type == 'total_row'){
				
				if($this->all_row_result){
					if($count_generated == 1){
						$order_items = $this->create_summary($request);
						//$this->print_array($order_items);
						//echo "1";
					}else{
						$order_items = $this->all_row_result;
						$summary = $this->get_count_total($order_items,$total_amount);				
						$order_items = $summary;
						//echo "2";
					}
					
				}else{					
					if($count_generated == 1 || ($p > 1)){
						$order_items = $this->create_summary($request);
						//echo "3";
					}else{
						$order_items = $wpdb->get_results($sql);
						if($wpdb->last_error){
							echo $wpdb->last_error;
						}
						$order_items = $this->get_final_order_items($type,$order_items,$report_name);
						
						$order_items 	= apply_filters("ic_commerce_report_page_data_items",  $order_items, $request, $type, $page, $report_name);
						
						//echo mysql_error();
						$summary = $this->get_count_total($order_items,$total_amount);				
						$order_items = $summary;
						//echo "4";
						
					}					
				}
				return $order_items;
			}
			
			if($type == 'limit_row'){					
				$sql .= " LIMIT $start, $limit";
				$order_items = $wpdb->get_results($sql);
				if($wpdb->last_error){
					echo $wpdb->last_error;
				}
				$order_items = $this->get_final_order_items($type,$order_items,$report_name);				
				$wpdb->flush(); 
			}
			
			if($type == 'all_row' or $type == 'all_row_total'){
				$order_items = $wpdb->get_results($sql);
				if($wpdb->last_error){
					echo $wpdb->last_error;
				}
				$order_items = $this->get_final_order_items($type,$order_items,$report_name);
				$this->all_row_result = $order_items;
				$wpdb->flush(); 
			}
			
			$order_items 	= apply_filters("ic_commerce_report_page_data_items",  $order_items, $request, $type, $page, $report_name);
			
			return $order_items;
		}
		
		function get_count_total($data,$amt = 'total_amount'){
			$total = 0;
			$return = array();
			$report_name 		= $this->get_request('report_name');
			$total_columns 		= $this->result_columns($report_name);
			//$this->print_array($total_columns);
			$order_status		= array();
			if(count($total_columns) > 0){
				//$this->print_array($data);
				
				foreach($data as $key => $value){
					$total = $total + (isset($value->$amt) ? $value->$amt : 0);

					foreach($total_columns as $ckey => $label):
						$v = isset($value->$ckey) ? trim($value->$ckey) : 0;
						$v = empty($v) ? 0 : $v;						
						$return[$ckey] 	= isset($return[$ckey])	? ($return[$ckey] + $v): $v;
					endforeach;
					/*
					if(isset($value->order_status)){						
						
						if(isset($order_status[$value->order_status])){
							$order_status[$value->order_status] = $order_status[$value->order_status] + 1;
						}else{
							$order_status[$value->order_status] = 1;
						}												
						
						$order_status[] = $value->order_status;
					}
					*/
					
				}
			}else{
				foreach($data as $key => $value){
					$total = $total + (isset($value->$amt) ? $value->$amt : 0);
				}
			}
			/*
			foreach($data as $key => $value){
				$total = $total + $value->$amt;
				$return['quantity'] 				= isset($value->quantity)				? (isset($return['quantity']) 				? ($return['quantity'] + $value->quantity) 								: $value->quantity)					: '';
				$return['cost_of_good_amount'] 		= isset($value->cost_of_good_amount)	? (isset($return['cost_of_good_amount'])	? ($return['cost_of_good_amount'] + $value->cost_of_good_amount) 		: $value->cost_of_good_amount)		: '';
				$return['total_cost_good_amount'] 	= isset($value->total_cost_good_amount)	? (isset($return['total_cost_good_amount']) ? ($return['total_cost_good_amount'] + $value->total_cost_good_amount) 	: $value->total_cost_good_amount)	: '';
				$return['sales_rate_amount'] 		= isset($value->sales_rate_amount)		? (isset($return['sales_rate_amount']) 		? ($return['sales_rate_amount'] + $value->sales_rate_amount) 			: $value->sales_rate_amount)		: '';
				$return['total_amount'] 			= isset($value->total_amount)			? (isset($return['total_amount']) 			? ($return['total_amount'] + $value->total_amount) 						: $value->total_amount)				: '';
				$return['margin_profit_amount'] 	= isset($value->margin_profit_amount)	? (isset($return['margin_profit_amount']) 	? ($return['margin_profit_amount'] + $value->margin_profit_amount) 		: $value->margin_profit_amount)		: '';
			}
			*/
			//$return = array();
			
			
			
			$return['total_row_amount'] = $total;
			$return['total_row_count'] = count($data);
			
			//$this->print_array($return);
			return $return;
		}
		
		function total_count($Totalorder_count = 0, $TotalAmount=0, $summary = array(), $TotalShipping=0){
			global $wpdb;
			
			$admin_page 		= $this->get_request('page');
			$limit	 			= $this->get_request('limit',15, true);
			$adjacents			= $this->get_request('adjacents',3);
			$detail_view		= $this->get_request('detail_view',"no");
			$targetpage 		= "admin.php?page=".$admin_page;
		    $request 			= $this->get_all_request();extract($request);
			$total_pages		= $summary['total_row_count'];
			$create_pagination 	= $this->get_pagination($total_pages,$limit,$adjacents,$targetpage,$request);
			
			$total_row_amount	= $summary['total_row_amount'];
			$total_row_count	= $summary['total_row_count'];
			
			$output 			= "";//Added 20150219
			$categories_count 	= $this->get_request('parent_categories_count',0,true);//Added 20150219
				
			?>
				
				<table style="width:100%" class="detail_summary">
					<tr>
						<td valign="middle" class="grid_bottom_total">
                        	<?php
								if($report_name == "recent_order"){
									$formated_total_amount		= $this->price($TotalAmount);
									$formated_total_shippint	= $this->price($TotalShipping);
									$output = "<table><tr>";
									$output .= "<tr><td>Result:		</td><td>	<strong>{$Totalorder_count}/{$total_pages}</strong></td></tr>";
									$output .= "<tr><td>Amount:		</td><td> 	<strong>{$formated_total_amount}</strong></td></tr>";
									$output .= "<tr><td>Shipping:	</td><td> 	<strong>{$formated_total_shippint}</strong></td></tr>";
									$output .= "</tr></table>";
								}else{
									//Added 20150219
									if($categories_count <= 0){
										$output = "Result: 		<strong>{$Totalorder_count}/{$total_pages}</strong>";
										if($TotalAmount >0){
											$formated_total_amount		= $this->price($TotalAmount);
											$output .= ", Amount: 	<strong>{$formated_total_amount}</strong><br />";
										}
									}
									
									
								}
								
								echo $output;
								
							?>
						</td>
						<td>					
							<?php echo $create_pagination;?>
                        	<div class="clearfix"></div>
                            <div>
                        	<?php
								$this->export_to_csv_button('bottom', $summary);
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
		
		function export_to_csv_button($position = 'bottom', $summary = array()){
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
			
			$request_			=	$request;
			
			unset($request['action']);
			//unset($request['page']);
			unset($request['p']);
			
			$logo_image 			= $this->get_setting('logo_image',$this->constants['plugin_options'], '');
			$report_title 			= $this->get_setting('report_title',$this->constants['plugin_options'], '');
			$company_name 			= $this->get_setting('company_name',$this->constants['plugin_options'], '');
							
			?>
            <div id="<?php echo $admin_page ;?>Export" class="RegisterDetailExport">
                <form id="<?php echo $admin_page."_".$position ;?>_form" class="<?php echo $admin_page ;?>_form ic_export_<?php echo $position ;?>_form" action="<?php echo $mngpg;?>" method="post">
                   <?php echo $this->create_hidden_fields($request);?>
                    <input type="hidden" name="export_file_name" value="<?php echo $admin_page;?>" />
                    <input type="hidden" name="export_file_format" value="csv" />
                 	
                    <input type="submit" name="<?php echo $admin_page ;?>_export_csv" class="onformprocess csvicon" value="<?php _e("Export to CSV",'icwoocommerce_textdomains');?>" data-format="csv" data-popupid="export_csv_popup" data-hiddenbox="popup_csv_hidden_fields" data-popupbutton="<?php _e("Export to CSV",'icwoocommerce_textdomains');?>" data-title="<?php _e("Export to CSV - Additional Information",'icwoocommerce_textdomains');?>" />
                    <input type="submit" name="<?php echo $admin_page ;?>_export_xls" class="onformprocess excelicon" value="<?php _e("Export to Excel",'icwoocommerce_textdomains');?>" data-format="xls" data-popupid="export_csv_popup" data-hiddenbox="popup_csv_hidden_fields" data-popupbutton="<?php _e("Export to Excel",'icwoocommerce_textdomains');?>" data-title="<?php _e("Export to Excel - Additional Information",'icwoocommerce_textdomains');?>" />
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
		
		function back_print_botton($position  = "bottom",$summary = array()){
			
			if($position  == "bottom"){
				$total_row_amount	= $summary['total_row_amount'];
				$total_row_count	= $summary['total_row_count'];
				
			?>
            	<?php if($total_row_amount > 0):?>
                <div class="print_summary_bottom">
                	Total Result: <strong><?php echo $total_row_count ?></strong>, Amount: <strong><?php echo $this->price($total_row_amount); ?></strong><br />
                </div>
                <?php endif;?>
            <?php }?>
                <div class="back_print_botton noPrint">
            		<input type="button" name="backtoprevious" value="<?php _e("Back to Previous",'icwoocommerce_textdomains');?>"  class="onformprocess" onClick="back_to_detail();" />
                    <input type="button" name="backtoprevious" value="<?php _e("Print",'icwoocommerce_textdomains');?>"  class="onformprocess" onClick="print_report();" />
                </div>
            <?php 
		}
		
		function ic_commerce_custom_admin_report_ajax_request($type) {
			
			if (!empty( $_POST['action'] ) ) {
				$detail_view 	= isset($_REQUEST['detail_view']) ? $_REQUEST['detail_view'] : 'no';
				if($detail_view == "yes"){
					$this->ic_commerce_custom_report_detail($type);
				}else{
					$this->ic_commerce_custom_report_normal($type);
				}
			}else{
				echo __("Something going wrong, contact to developer",'icwoocommerce_textdomains');
			}
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
							$products = array(array("ID" => "-1","title" => __("Purchased product not found in selected categroy",'icwoocommerce_textdomains')));
						}
						
				$output_array['success'] = 'true';
				$output_array['success_output'] = $products;	

				//$output_array['sql'] = $sql;
				//$output_array['product_id'] = $purchased_product_id;
			}else{
				$output_array['error'] = 'true';
				$output_array['error_output'] = __("Some thing going wrong, contact to developer",'icwoocommerce_textdomains');
			}
			
			echo json_encode($output_array);
			die();
		}
		
		function ic_commerce_custom_report_page_export_csv($export_file_format='csv'){
			global $wpdb, $table_prefix;
			
			$report_name	= $this->get_request('report_name',"no");			
			$columns 		= $this->get_columns($report_name);
			$total_columns	= $this->result_columns($report_name);
			$order_items	= $this->get_items('all_row',$report_name,$columns,$total_columns);
			$summary 		= $this->get_items('total_row',$report_name,$columns,$total_columns);
			
			
			//$this->print_array($summary);
			
			if(isset($columns['product_edit']))	unset($columns['product_edit']);//Added 20141015
			
			$columns 			= apply_filters("ic_commerce_report_page_export_csv_columns", $columns);			
			$order_items		= apply_filters("ic_commerce_report_page_export_csv_excel_data",$order_items,$columns, $export_file_format, $report_name);
			
			$order_items		= $this->create_grid_items($order_items, $columns, $report_name, array(), "all_row");
			
			$order_items		= apply_filters("ic_commerce_report_page_export_csv_excel_data_after_get_grid_object",$order_items,$columns, $export_file_format, $report_name);
			
			$price_columns		= apply_filters("ic_commerce_report_page_export_csv_excel_price_columns",array("total_amount"), $report_name);
			
			$export_rows	= array();
			$country      	= $this->get_wc_countries();//Added 20150225
			$i 				= 0;
			$date_format	= get_option( 'date_format' );
			
			
			//Added 20150202
			$num_decimals   = get_option( 'woocommerce_price_num_decimals'	,	0		);
			$decimal_sep    = get_option( 'woocommerce_price_decimal_sep'	,	'.'		);
			$thousand_sep   = get_option( 'woocommerce_price_thousand_sep'	,	','		);			
			$zero			= number_format(0, $num_decimals,$decimal_sep,$thousand_sep);
			
			foreach ( $order_items as $rkey => $rvalue ):	
				$order_item = $rvalue;			
				foreach($columns as $key => $value):					
					switch ($key) {
							case "cost_of_good_amount":
							case "total_cost_good_amount":
							case "sales_rate_amount":
							case "total_amount":
							case "margin_profit_amount":						
							case "coupon_amount":						
							case "order_shipping":
							case "order_shipping_tax":
							case "order_tax":
							case "total_tax":
							case "gross_amount":
							case "order_discount":
							case "cart_discount":
							case "total_discount":
							case "order_total":
							case "total_amount":						
							case "product_rate":
							case "total_price":	
							case "order_refund_amount":
							case "part_order_refund_amount":
							case "total_refund_amount":
							case "profit_percentage":
							case "min_product_price":
							case "max_product_price":
							
							case "product_sold_rate":
							case "product_total":
							case "product_subtotal":
							case "product_discount":
							
								$td_value 	=  isset($rvalue->$key) ? $rvalue->$key : 0;
								$td_value 	=  strlen($td_value) != 0 ? $td_value : 0;
								$export_rows[$i][$key]	=  $td_value != 0 ? number_format($td_value, $num_decimals,$decimal_sep,$thousand_sep) : $zero;//Added 20153001
								break;
							case 'billing_country':
							case 'billing_country':
							case 'shipping_country':
								$export_rows[$i][$key] =  isset($country->countries[$rvalue->$key]) ? $country->countries[$rvalue->$key]: $rvalue->$key;
								break;							
							case "sku":
							case "stock":
								$export_rows[$i][$key] =  $this->get_stock($rvalue->stock);
								break;
							case "product_stock":
								$td_value =  $this->get_stock_($order_item->order_item_id, $order_item->product_id,$report_name);
								$export_rows[$i][$key] = $td_value + 0;
								break;
							case "product_sku":
								$export_rows[$i][$key] =  $this->get_sku($order_item->order_item_id, $order_item->product_id,$report_name);
								break;
							case "order_date":////New Change ID 20140918
							case "post_date"://New Custom Change ID 20141009
							case "refund_date":////New Change ID 20150403
							case "group_date":////New Change ID 20150406
								$export_rows[$i][$key] = isset($order_item->$key) ? date($date_format,strtotime($order_item->$key)) : '';
								break;
							case "order_status"://New Change ID 20140918
							case "order_status_name"://New Change ID 20150225
							case "refund_status":////New Change ID 20150403
								$td_value = isset($order_item->$key) ? $order_item->$key : '';
								$export_rows[$i][$key] = ucwords($td_value);
								break;
							default:								
								if(in_array($key, $price_columns)){
									$td_value 	=  isset($rvalue->$key) ? $rvalue->$key : 0;
									$td_value 	=  strlen($td_value) != 0 ? $td_value : 0;
									$export_rows[$i][$key]	=  $td_value != 0 ? number_format($td_value, $num_decimals,$decimal_sep,$thousand_sep) : $zero;//Added 20153001
								}else{
									$export_rows[$i][$key] = isset($rvalue->$key) ? $rvalue->$key : '';
								}
								break;
						}
				endforeach;
				$i++;
			endforeach;
			
			$total_columns = $this->result_columns($report_name);
			if(count($total_columns) > 0);{			
				$total_label_flag = false;
				foreach($columns as $key => $value):					
					switch ($key) {
							case "cost_of_good_amount":
							case "total_cost_good_amount":
							case "sales_rate_amount":
							case "total_amount":
							case "margin_profit_amount":						
							case "coupon_amount":						
							case "order_shipping":
							case "order_shipping_tax":
							case "order_tax":
							case "total_tax":
							case "gross_amount":
							case "order_discount":
							case "cart_discount":
							case "total_discount":
							case "order_total":
							case "total_amount":						
							case "product_rate":
							case "total_price":
							
							case "product_sold_rate":
							case "product_total":
							case "product_subtotal":
							case "product_discount":
							
							//case "min_product_price":
							//case "max_product_price":												
								$td_value 	=  isset($summary[$key]) ? $summary[$key] : '';
								$td_value 	=  strlen($td_value) !=  0 ? $td_value : 0;
								$export_rows[$i][$key]	=  $td_value != 0 ? number_format($td_value, $num_decimals,$decimal_sep,$thousand_sep) : $zero;//Added 20153001
								break;						
							case "ic_commerce_order_item_count":
							case "total_row_count":
							case "quantity":
							case "product_quantity":
								$export_rows[$i][$key] = isset($summary[$key]) ? $summary[$key] : '';
								break;							
							case "product_sku":
							case "billing_first_name":						
							case "payment_method_title":
							case "order_status":
							case "order_id":
							case "billing_first_name":
							case "billing_country":
							case "order_item_name":
								if($total_label_flag)
									$export_rows[$i][$key] = "";
								else{
									$export_rows[$i][$key] = "Total";
									$total_label_flag = true;
								}
								break;						
							case 'product_name':
							case 'order_status':
							case 'ic_commerce_order_billing_name':
							case 'billing_email':
							case 'order_date':
							case 'billing_country':
							case 'shipping_country':								
							case "sku":
							case "stock":
							case 'ic_commerce_order_billing_name':
							case 'ic_commerce_order_tax_name':
							case 'ic_commerce_order_coupon_codes':
							case 'ic_commerce_order_item_count':
							case "ic_commerce_order_status_name":
							case "product_stock":
							case "product_sku":
							case "order_date":
								$export_rows[$i][$key] = '';
								break;
							case "profit_percentage":
								$total_cost_good_amount 	= isset($summary['total_cost_good_amount']) 	? $summary['total_cost_good_amount'] 	: 0;
								$margin_profit_amount 		= isset($summary['margin_profit_amount']) 		? $summary['margin_profit_amount'] 		: 0;
								$profit_percentage 			= isset($summary['profit_percentage']) 			? $summary['profit_percentage'] 		: 0;
								
								if($total_cost_good_amount != 0 and $margin_profit_amount != 0){
									$profit_percentage = ($margin_profit_amount/$total_cost_good_amount)*100;
								}
								
								$export_rows[$i][$key] = sprintf("%.2f",$profit_percentage);
								break;
							default:
								if(in_array($key, $price_columns)){
									$td_value 	=  isset($summary[$key]) ? $summary[$key] : '';
									$td_value 	=  strlen($td_value) != 0 ? $td_value : 0;
									$export_rows[$i][$key]	=  $td_value != 0 ? number_format($td_value, $num_decimals,$decimal_sep,$thousand_sep) : $zero;
								}else{
									$export_rows[$i][$key] = isset($summary[$key]) ? $summary[$key] : '';
								}
								break;
						}
				endforeach;
				$i++;
			}
			
			$export_file_name 		= $this->get_request('export_file_name',"no");
			$report_name 			= $this->get_request('report_name','product_page');
			$report_name 			= str_replace("_page","_list",$report_name);
			
			/*$today = date_i18n("Y-m-d-H-i-s");				
			$FileName = $export_file_name."_".$report_name."-".$today.".".$export_file_format;	
			$out = $this->ExportToCsv($FileName,$export_rows,$columns,$export_file_format);*/
			
			//if($export_file_format == "xls"){
				//$export_file_format = "xlsx";
			//}
			
			/*New Added 20160130*/
			$today_date 		= date_i18n("Y-m-d-H-i-s");				
			$export_filename 	= $export_file_name."-".$report_name."-".$today_date.".".$export_file_format;
			$export_filename 	= apply_filters('ic_commerce_export_csv_excel_format_file_name',$export_filename,$report_name,$today_date,$export_file_name,$export_file_format);
			do_action("ic_commerce_export_csv_excel_format",$export_filename,$export_rows,$columns,$export_file_format,$report_name);
			$out = $this->ExportToCsv($export_filename,$export_rows,$columns,$export_file_format,$report_name);
			
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
			//echo $report_title;
			//echo "\n";
			echo $out;
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
		
		function ic_commerce_custom_report_page_export_pdf($export_file_format = "pdf"){
			global $wpdb, $table_prefix;			
			$report_name	= $this->get_request('report_name',"no");
			$columns 		= $this->get_columns($report_name);
			$total_columns	= $this->result_columns($report_name);
			$order_items	= $this->get_items('all_row',$report_name,$columns,$total_columns);
			$summary 		= $this->get_items('total_row',$report_name,$columns,$total_columns);
			
			if(isset($columns['product_edit']))	unset($columns['product_edit']);//Added 20141015			
			
			$columns 			= apply_filters("ic_commerce_report_page_export_pdf_columns", $columns);			
			$order_items		= apply_filters("ic_commerce_report_page_export_pdf_data",$order_items,$columns, $export_file_format, $report_name);
			
			$order_items		= $this->create_grid_items($order_items, $columns, $report_name, array(), "all_row");
			
			$order_items		= apply_filters("ic_commerce_report_page_export_pdf_data_after_get_grid_object",$order_items,$columns, $export_file_format, $report_name);
			
			$price_columns		= apply_filters("ic_commerce_report_page_export_pdf_price_columns",array("total_amount"), $report_name);
			
			$export_rows 	= array();
			$country      	= $this->get_wc_countries();//Added 20150225
			$i				= 0;
			$zero			= $this->price(0);
			$date_format	= get_option( 'date_format' );
			
			foreach ( $order_items as $rkey => $rvalue ):
					$order_item = $rvalue;
					foreach($columns as $key => $value):
						switch ($key) {
							case 'amount':
							case 'payment_amount_total':
							case 'total_amount':
							case 'Total':
							
							case 'gross_amount':
							case 'discount_value':
							case 'total_amount':
							case 'product_rate':
							case 'total_price':
							
							case 'regular_price':
							case 'sale_price':
							
							//New Custom Change ID 20141009
							case "product_rate_exculude_tax":
							case "product_vat":
							case "product_vat_total":
							case "product_vat_par_item":
							case "product_shipping":
							case "total_price_exculude_tax":
							
							//New Custom Change ID 20141015
							case "cost_of_good_amount":
							case "total_cost_good_amount":
							case "margin_profit_amount":
							case "sales_rate_amount":
							
							case "_order_shipping_amount":
							case "_order_amount":
							case "order_total_amount":
							case "_shipping_tax_amount":
							case "_order_tax":
							case "_total_tax":
							
							case "order_shipping_tax":
							case "order_shipping":
							case "order_tax":
							
							case "order_discount":
							case "cart_discount":
							case "total_discount":
							case "total_tax":
							case "order_total":
							case "order_refund_amount":
							case "part_order_refund_amount":
							case "total_refund_amount":
							case "min_product_price":
							case "max_product_price":
							
							case "product_sold_rate":
							case "product_total":
							case "product_subtotal":
							case "product_discount":
							
								$td_value 				=	isset($rvalue->$key) ? $rvalue->$key : 0;
								$export_rows[$i][$key]	=	$td_value == 0 ? $zero : $this->price($td_value);
								break;
							case 'billing_country':
							case 'billing_country':
							case 'shipping_country':
								$export_rows[$i][$key] =  isset($country->countries[$rvalue->$key]) ? $country->countries[$rvalue->$key]: $rvalue->$key;
								break;							
							case "sku":
							case "stock":
								$export_rows[$i][$key] =  $this->get_stock($rvalue->stock);
								break;
							case "product_stock":
								$td_value =  $this->get_stock_($order_item->order_item_id, $order_item->product_id,$report_name);
								$export_rows[$i][$key] = $td_value + 0;
								break;
							case "product_sku":
								$export_rows[$i][$key] =  $this->get_sku($order_item->order_item_id, $order_item->product_id,$report_name);
								break;
							case "order_date":////New Change ID 20140918
							case "post_date"://New Custom Change ID 20141009
							case "refund_date":////New Change ID 20150403
							case "group_date":////New Change ID 20150406
								$export_rows[$i][$key] = isset($order_item->$key) ? date($date_format,strtotime($order_item->$key)) : '';
								break;
							case "order_tax_rate":
								$td_value = isset($order_item->$key) ? $order_item->$key : 0;
								$export_rows[$i][$key] = sprintf("%.2f%%",$td_value);
								break;
							case "profit_percentage":
								$td_value = isset($order_item->$key) ? $order_item->$key : 0;
								$td_value = sprintf("%.2f%%",$td_value);
								$export_rows[$i][$key] =	$td_value;
								break;
							default:
								if(in_array($key, $price_columns)){
									$td_value 				=	isset($rvalue->$key) ? $rvalue->$key : 0;
									$export_rows[$i][$key]	=	$td_value == 0 ? $zero : $this->price($td_value);
								}else{
									$export_rows[$i][$key] = isset($rvalue->$key) ? $rvalue->$key : '';
								}								
								
								break;
						}
					endforeach;				
				$i++;
			endforeach;
			
			//$this->print_array($export_rows);die;
			
			$output = $this->GetPdfDataGrid($export_rows,$columns,$summary, $price_columns,$total_columns);			
			$this->export_to_pdf($export_rows,$output);
		}
		
		function get_product_name($id, $by = 'ID'){
			global $wpdb;
			$sql = "SELECT post_title  FROM {$wpdb->prefix}posts  AS posts	WHERE posts.ID='{$id}' LIMIT 1";
								
			return $first_order_date = $wpdb->get_var($sql );
		}
		function page_title($title){
			$title = str_replace("_"," ",$title);
			$title = str_replace("-"," ",$title);
			//$title = Ucwords($title);
			return $title;
		}
		
		function get_all_request(){
			global $request, $back_day;
			if(!$this->request){
				$request 			= array();
				$start				= 0;
				
				do_action("ic_commerce_report_page_before_default_request");
				
				$limit 				= $this->get_request('limit',3,true);
				$p 					= $this->get_request('p',1,true);				
				$page				= $this->get_request('page',NULL);				
				$report_name		= $this->get_request('report_name',"product_page",true);
				$order_status		= $this->get_request('order_status',"-1",true);
				$category_id		= $this->get_request('category_id','-1',true);
				$product_id			= $this->get_request('product_id','-1',true);
				$order_status_id	= $this->get_request('order_status_id','-1',true);	
				$parent_category_id	= $this->get_request('parent_category_id','-1',true);
				$child_category_id	= $this->get_request('child_category_id','-1',true);
				$group_by_parent_cat= $this->get_request('group_by_parent_cat',0,true);
				$paid_customer		= $this->get_request('paid_customer','-1',true);
				$cost_of_goods_only	= $this->get_request('cost_of_goods_only','no',true);
				$country_code		= $this->get_request('country_code','-1',true);
				$state_code 		= $this->get_request('state_code','-1',true);
				$tax_group_by 		= $this->get_request('tax_group_by','-1',true);
				$order_by 			= $this->get_request('list_parent_category',NULL,true);
				$order_by 			= $this->get_request('cost_of_goods_only',"no",true);
				$product_status 	= $this->get_request('product_status',"-1",true);
				$product_type 		= $this->get_request('product_type',"-1",true);
				
				$start_date  		= $this->get_request('start_date  ','', true);
				$end_date 			= $this->get_request('end_date ','',true);
				
				if($report_name == "manual_refund_detail_page"){
					$group_by 			= $this->get_request('group_by','refund_id',true);
					$refund_status_type = $this->get_request('refund_status_type','part_refunded',true);
					if($refund_status_type == "part_refunded"){
						if($group_by == "order_id"){
							//$_REQUEST['group_by'] = 'refund_id';
						}
					}else{
						if($group_by == "refund_id"){
							//$_REQUEST['group_by'] = 'order_id';
						}
					}
				}
				
				if($report_name == "coupon_page" || $report_name == "coupon_couontry_page"){
					$coupon_code		= $this->get_request('coupon_code','-1',true);	
					$coupon_codes		= $this->get_request('coupon_codes','-1',true);	
					$discount_types		= $this->get_request('coupon_discount_types','-1',true);	
					$country_code		= $this->get_request('country_code','-1',true);	
					
					$sort_by			= $this->get_request('sort_by','total_amount',true);
				}
				
				$sort_by 			= $this->get_request('sort_by','-1',true);
				$order_by 			= $this->get_request('order_by','DESC',true);
				
				$this->common_request_form();
				
				
				
				if($p > 1){	$start = ($p - 1) * $limit;}				
				$_REQUEST['start']= $start;
				
				if(isset($_REQUEST)){
					$REQUEST = $_REQUEST;
					$REQUEST = apply_filters("ic_commerce_before_request_creation", $REQUEST);
					foreach($REQUEST as $key => $value ):						
						$request[$key] =  $this->get_request($key,NULL);
					endforeach;
					$request = apply_filters("ic_commerce_after_request_creation", $request);
				}
				$this->request = $request;				
			}else{				
				$request = $this->request;
			}
			
			return $request;
		}
		
		function _get_string_multi_request($string, $default = NULL){
			if($string == "'-1'" || $string == "\'-1\'"  || $string == "-1" ||$string == "''" || strlen($string) <= 0)$string = $default;
			if(strlen($string) > 0 and $string != $default){ $string  		= "'".str_replace(",","','",$string)."'";}
			return $string;
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
		}//print_header
		
		//New Change ID 20141208
		function get_sold_product_parent_category_data(){
			global $wpdb;
			

			$request = $this->get_all_request();extract($request);
			
			//$order_status	= $this->get_string_multi_request('order_status',$order_status, "-1");
			$hide_order_status	= $this->get_string_multi_request('hide_order_status',$hide_order_status, "-1");
			
			$sql ="";
			$sql .= " SELECT ";
			$sql .= " term_taxonomy_product_id.parent AS id";
			$sql .= " ,terms_parent_product_id.name AS label";
			
			$sql .= " FROM {$wpdb->prefix}woocommerce_order_items as woocommerce_order_items";
			
			
			$sql .= " LEFT JOIN  {$wpdb->prefix}posts as posts ON posts.id=woocommerce_order_items.order_id";
			
			$sql .= " LEFT JOIN  {$wpdb->prefix}woocommerce_order_itemmeta as woocommerce_order_itemmeta_product_id ON woocommerce_order_itemmeta_product_id.order_item_id=woocommerce_order_items.order_item_id";
			
			$sql .= " 	LEFT JOIN  {$wpdb->prefix}term_relationships 	as term_relationships_product_id 	ON term_relationships_product_id.object_id		=	woocommerce_order_itemmeta_product_id.meta_value 
						LEFT JOIN  {$wpdb->prefix}term_taxonomy 		as term_taxonomy_product_id 		ON term_taxonomy_product_id.term_taxonomy_id	=	term_relationships_product_id.term_taxonomy_id
						LEFT JOIN  {$wpdb->prefix}terms 				as terms_product_id 				ON terms_product_id.term_id						=	term_taxonomy_product_id.term_id";
			
			$sql .= " 	LEFT JOIN  {$wpdb->prefix}terms 				as terms_parent_product_id 				ON terms_parent_product_id.term_id						=	term_taxonomy_product_id.parent";
			
			$sql .= " WHERE 1*1 ";
			$sql .= " AND woocommerce_order_items.order_item_type 	= 'line_item'";
			$sql .= " AND woocommerce_order_itemmeta_product_id.meta_key 	= '_product_id'";
			$sql .= " AND term_taxonomy_product_id.taxonomy 	= 'product_cat'";
			$sql .= " AND term_taxonomy_product_id.parent > 0";
			
			
			$sql .= " AND posts.post_type 											= 'shop_order'";				
			//if($order_status  && $order_status != '-1' and $order_status != "'-1'")$sql .= " AND posts.post_status IN (".$order_status.")";
			if($hide_order_status  && $hide_order_status != '-1' and $hide_order_status != "'-1'")$sql .= " AND posts.post_status NOT IN (".$hide_order_status.")";
			
			$sql .= " GROUP BY term_taxonomy_product_id.parent";
			
			$sql .= " ORDER BY terms_parent_product_id.name ASC";
			
			$category_items = $wpdb->get_results($sql);
			
			if($wpdb->last_error){
				echo $wpdb->last_error;
			}
			return $category_items;
		}// END get_sold_product_parent_category_data
		
		//New Change ID 20150120
		function get_sold_product_child_category_data(){
			global $wpdb;
			$sql ="";
			$sql .= " SELECT ";
			
			$sql .= " terms_product_id.term_id AS id";
			$sql .= " ,terms_product_id.name AS label";
			$sql .= " FROM {$wpdb->prefix}woocommerce_order_items as woocommerce_order_items";
			$sql .= " LEFT JOIN  {$wpdb->prefix}woocommerce_order_itemmeta as woocommerce_order_itemmeta_product_id ON woocommerce_order_itemmeta_product_id.order_item_id=woocommerce_order_items.order_item_id";
			
			$sql .= " 	LEFT JOIN  {$wpdb->prefix}term_relationships 	as term_relationships_product_id 	ON term_relationships_product_id.object_id		=	woocommerce_order_itemmeta_product_id.meta_value 
						LEFT JOIN  {$wpdb->prefix}term_taxonomy 		as term_taxonomy_product_id 		ON term_taxonomy_product_id.term_taxonomy_id	=	term_relationships_product_id.term_taxonomy_id
						LEFT JOIN  {$wpdb->prefix}terms 				as terms_product_id 				ON terms_product_id.term_id						=	term_taxonomy_product_id.term_id";
			
			$sql .= " 	LEFT JOIN  {$wpdb->prefix}terms 				as terms_parent_product_id 				ON terms_parent_product_id.term_id						=	term_taxonomy_product_id.parent";
			
			$sql .= " LEFT JOIN  {$wpdb->prefix}posts as posts ON posts.id=woocommerce_order_items.order_id";
			
			$sql .= " WHERE 1*1 ";
			$sql .= " AND woocommerce_order_items.order_item_type 					= 'line_item'";
			$sql .= " AND woocommerce_order_itemmeta_product_id.meta_key 			= '_product_id'";
			$sql .= " AND term_taxonomy_product_id.taxonomy 						= 'product_cat'";
			$sql .= " AND posts.post_type 											= 'shop_order'";
			
			$sql .= " AND term_taxonomy_product_id.parent > 0";
			
			$sql .= " GROUP BY label";
			
			$sql .= "  Order By label ASC";
			
			$category_items = $wpdb->get_results($sql);
			
			if($wpdb->last_error){
				echo $wpdb->last_error;
			}
			return $category_items;
		}
		
		function get_customer_details($users=array()){
			global $wpdb;
				$sql = " SELECT ";
				$sql .= "	users.ID											AS user_id
							,postmeta_billing_billing_email.meta_value			AS billing_email
							,CONCAT(postmeta_billing_first_name.meta_value,' ',postmeta_billing_last_name.meta_value)		AS billing_name							
							";
				$sql .= "	FROM {$wpdb->prefix}users as users";
							
				
				$sql .= " 	LEFT JOIN  {$wpdb->prefix}usermeta as postmeta_billing_first_name ON postmeta_billing_first_name.user_id		=	users.ID";
				$sql .= " 	LEFT JOIN  {$wpdb->prefix}usermeta as postmeta_billing_last_name ON postmeta_billing_last_name.user_id			=	users.ID";
				$sql .= " 	LEFT JOIN  {$wpdb->prefix}usermeta as postmeta_billing_billing_email ON postmeta_billing_billing_email.user_id	=	users.ID";
				
							
				$sql .= "
							WHERE 
							postmeta_billing_first_name.meta_key		= 'billing_first_name'
							AND postmeta_billing_last_name.meta_key		= 'billing_last_name'
							AND postmeta_billing_billing_email.meta_key	= 'billing_email'
							
							AND LENGTH(TRIM(CONCAT(postmeta_billing_first_name.meta_value,' ',postmeta_billing_last_name.meta_value)))>0
							
							";
				if(count($users)>0) {
					$users_string = implode(",",$users);
					$sql .= "AND users.ID IN ({$users_string})";
				}
						
				$users_list 	= $wpdb->get_results($sql);
				$users_array	= array();
				if($wpdb->last_error){
					echo $wpdb->last_error;
				}else{
					if(count($users_list) > 0){
						foreach($users_list as $key => $value){
							$users_array[$value->user_id] = $value;
						}
					}
				}				
				
				return $users_array;
		}
		
		function get_order_customer2($post_type = 'shop_order',$post_status = 'no'){
				global $wpdb;
				
				
				$sql = "SELECT 
				billing_email.meta_value AS id, 
				concat(billing_first_name.meta_value, ' ',billing_last_name.meta_value) AS label
					FROM `{$wpdb->prefix}posts` AS posts
					LEFT JOIN  {$wpdb->prefix}postmeta as customer_user ON customer_user.post_id=posts.ID
					LEFT JOIN  {$wpdb->prefix}postmeta as billing_first_name ON billing_first_name.post_id=posts.ID
					LEFT JOIN  {$wpdb->prefix}postmeta as billing_last_name ON billing_last_name.post_id=posts.ID
					LEFT JOIN  {$wpdb->prefix}postmeta as billing_email ON billing_email.post_id=posts.ID
				";
				$sql .= " WHERE 
					post_type='{$post_type}' 
				AND customer_user.meta_key = '_customer_user'
				AND billing_first_name.meta_key = '_billing_first_name'
				AND billing_last_name.meta_key = '_billing_last_name'
				AND billing_email.meta_key = '_billing_email'
				";
				$sql .= " 
				GROUP BY billing_email.meta_value
				ORDER BY label  ASC";
				
				$products_category = $wpdb->get_results($sql);
				return $products_category; 
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
		
		function create_search_form_hidden_fields($request = array(), $type = "hidden"){
			$output_fields = "";
			foreach($request as $key => $value):
				$output_fields .=  "\n<input type=\"{$type}\" name=\"{$key}\" id=\"{$key}\" value=\"{$value}\" />";
			endforeach;
			return $output_fields;
		}
		
		/*
			* Function Name get_order_part_refunded_product_wise
			*
			* This function is used for calculate part refunds
			* 
			* @param strint $type
			* 
			* @param strint $shop_order_status
			* 
			* @param strint $start_date
			* 
			* @param strint $end_date
			* 
			* @param strint $end_date
			* 
			* @return array
			*		 
		*/
		function get_order_part_refunded_product_wise($type='total', $shop_order_status = array(), $start_date = '',$end_date = '', $product_ids = '-1'){
			global $wpdb;
			
			$today_date 			= '';
			
			$yesterday_date 		= '';
			
			$sql = "SELECT ";		
			
			$sql .= " product_id.meta_value AS product_id";		
			
			$sql .= ", SUM(ROUND(line_total.meta_value,2)) AS line_total";
			
			$sql .= ", SUM(quantity.meta_value) AS quantity";
			
			$sql .= " FROM {$wpdb->prefix}woocommerce_order_items AS woocommerce_order_items	";		
			
			$sql .= " LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS product_id ON (product_id.order_item_id = woocommerce_order_items.order_item_id AND product_id.meta_key = '_product_id') ";
			
			$sql .= " LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS line_total ON (line_total.order_item_id = woocommerce_order_items.order_item_id AND line_total.meta_key = '_line_total') ";
			
			$sql .= " LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS quantity ON (quantity.order_item_id = woocommerce_order_items.order_item_id AND quantity.meta_key = '_qty') ";
			
			$sql .= " LEFT JOIN {$wpdb->posts} AS shop_order_refund ON shop_order_refund.ID = woocommerce_order_items.order_id ";
			
			$sql .= " LEFT JOIN {$wpdb->posts} AS shop_order ON shop_order.ID = shop_order_refund.post_parent ";
			
			$sql .= " WHERE 	shop_order_refund.post_type 	IN ('shop_order_refund' ) ";
			
			$sql .= " AND shop_order.post_type 	IN ('shop_order' )";
			
			$sql .= " AND woocommerce_order_items.order_item_type = 'line_item'";
			
			
			if($type == "total"){
				$sql .= " AND DATE(shop_order_refund.post_date) BETWEEN '{$start_date}' AND '{$end_date}'";
			}
			
			if(count($shop_order_status)>0){
				$sql .= " AND  shop_order.post_status IN ({$shop_order_status})";
			}
			
			$sql .= " AND  shop_order.post_status NOT IN ('wc-refunded')";
			
			if($product_ids != '-1'){
				$sql .= " AND  product_id.meta_value IN ({$product_ids})";
			}
			
			$sql .= " GROUP BY product_id";
			
			
			$results = $wpdb->get_results($sql);
			$new_list = array();
			foreach($results as $key => $result){
				$product_id = $result->product_id;
				$line_total = $result->line_total;
				$quantity 	= $result->quantity;
				$new_list[$product_id]['line_total'] = $line_total;
				$new_list[$product_id]['quantity'] = $quantity;
			}
			
			//$this->print_array($new_list);
			
			return $new_list;	
			 
		}
		
		/*
			* Function Name get_order_full_refunded_product_wise
			*
			* This function is used for calculate part refunds
			* 
			* @param strint $type
			* 
			* @param strint $shop_order_status
			* 
			* @param strint $start_date
			* 
			* @param strint $end_date
			* 
			* @param strint $end_date
			* 
			* @return array
			*		 
		*/
		function get_order_full_refunded_product_wise($type='total', $shop_order_status = array(), $start_date = '',$end_date = '', $product_ids = '-1'){
			global $wpdb;
			
			$today_date 			= '';
			
			$yesterday_date 		= '';
			
			$sql = "SELECT ";		
			
			$sql .= " product_id.meta_value AS product_id";		
			
			$sql .= ", SUM(ROUND(line_total.meta_value,2)) AS line_total";
			
			$sql .= ", SUM(quantity.meta_value) AS quantity";
			
			//$sql .= ", line_total.meta_value AS _line_total";		
			
			//$sql .= ", SUM(ROUND(line_subtotal.meta_value,2)) AS line_subtotal";					
			
			//$sql .= ", woocommerce_order_items.order_id, shop_order_refund.post_parent, shop_order_refund.ID AS shop_order_refund_id, refund_reason.meta_value AS refund_reason";
			
			$sql .= " FROM {$wpdb->prefix}woocommerce_order_items AS woocommerce_order_items";	
			
			$sql .= " LEFT JOIN {$wpdb->posts} AS shop_order_refund ON shop_order_refund.post_parent = woocommerce_order_items.order_id ";
			
			$sql .= " LEFT JOIN {$wpdb->posts} AS shop_order ON shop_order.ID = woocommerce_order_items.order_id ";	
			
			$sql .= " LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS product_id ON (product_id.order_item_id = woocommerce_order_items.order_item_id AND product_id.meta_key = '_product_id') ";
			
			$sql .= " LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS line_total ON (line_total.order_item_id = woocommerce_order_items.order_item_id AND line_total.meta_key = '_line_total') ";
			
			$sql .= " LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS quantity ON (quantity.order_item_id = woocommerce_order_items.order_item_id AND quantity.meta_key = '_qty') ";
			
			$sql .= " LEFT JOIN {$wpdb->postmeta} AS refund_reason ON refund_reason.post_id = shop_order_refund.ID AND  refund_reason.meta_key = '_refund_reason'";	
			
			//$sql .= " LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS line_subtotal ON (line_subtotal.order_item_id = woocommerce_order_items.order_item_id AND line_subtotal.meta_key = '_line_subtotal') ";
			
			$sql .= " WHERE 1*1";
			
			$sql .= " AND shop_order_refund.post_type 	IN ('shop_order_refund' )";
			
			$sql .= " AND shop_order.post_type 	IN ('shop_order' )";
			
			$sql .= " AND woocommerce_order_items.order_item_type = 'line_item'";	
			
			$sql .= " AND (LENGTH(refund_reason.meta_value) > 0 OR refund_reason.meta_value = 'Order fully refunded')";
			
			if($type == "total"){
				$sql .= " AND DATE(shop_order_refund.post_date) BETWEEN '{$start_date}' AND '{$end_date}'";
			}
			
			$sql .= " AND  shop_order.post_status IN ('wc-refunded')";
			
			if($product_ids != '-1'){
				$sql .= " AND  product_id.meta_value IN ({$product_ids})";
			}
			
			$sql .= " GROUP BY product_id";
			
			$sql .= " ORDER BY product_id ASC";
			
			$results = $wpdb->get_results($sql);
			//$this->print_array($results);
			
			$new_list = array();
			foreach($results as $key => $result){
				$product_id = $result->product_id;
				$line_total = $result->line_total;
				$quantity 	= isset($result->quantity) ? $result->quantity : 0;  
				$new_list[$product_id]['line_total'] = $line_total;
				$new_list[$product_id]['quantity'] = $quantity;
			}
			
			//$this->print_array($new_list);
			
			return $new_list;			 
		}
		
		function ic_commerce_report_page_data_items($order_items = array(), $request = array(), $type = '', $page = '', $report_name = ''){			
			if($report_name == 'product_page'){
				return $order_items;
				//if($type == 'limit_row'){
					$product_ids 			= $this->get_items_id_list($order_items,'product_id');
					$start_date 			= isset($request['start_date']) ? $request['start_date'] : '';
					$end_date 				= isset($request['end_date']) ? $request['end_date'] : '';
					$order_status 			= isset($request['order_status']) ? $request['order_status'] : '';
					$order_status			= $this->get_string_multi_request('order_status',$order_status, "-1");
					
					$part_refundeds			= $this->get_order_part_refunded_product_wise('total',$order_status,$start_date,$end_date,$product_ids);
					
					$full_refundeds			= $this->get_order_full_refunded_product_wise('total',$order_status,$start_date,$end_date,$product_ids);
					
					foreach($order_items as $key => $order_item){
						$product_id = $order_item->product_id;
						$part_refunded = isset($part_refundeds[$product_id]) ? $part_refundeds[$product_id] : array();
						$full_refunded = isset($full_refundeds[$product_id]) ? $full_refundeds[$product_id] : array();
						
						$order_items[$key]->part_refunded_quantity = isset($part_refunded['quantity']) ? $part_refunded['quantity'] : 0;
						$order_items[$key]->part_refunded_line_total = isset($part_refunded['line_total']) ? $part_refunded['line_total'] : 0;
						
						$order_items[$key]->full_refunded_quantity = isset($full_refunded['quantity']) ? $full_refunded['quantity'] : 0;
						$order_items[$key]->full_refunded_line_total = isset($full_refunded['line_total']) ? $full_refunded['line_total'] : 0;
						
						$order_items[$key]->refunded_quantity = $order_items[$key]->part_refunded_quantity + $order_items[$key]->full_refunded_quantity;
						$order_items[$key]->refunded_line_total = $order_items[$key]->part_refunded_line_total + $order_items[$key]->full_refunded_line_total;
						
						$order_items[$key]->quantity = $order_items[$key]->quantity - $order_items[$key]->refunded_quantity;
						$order_items[$key]->total_amount = $order_items[$key]->total_amount - $order_items[$key]->refunded_line_total;
					}
				//}
			};
			return $order_items;
		}
		
	}//END class 
}//END Clas check
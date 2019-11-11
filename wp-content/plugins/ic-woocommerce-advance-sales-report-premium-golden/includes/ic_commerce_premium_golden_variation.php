<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
if ( ! class_exists( 'IC_Commerce_Premium_Golden_Variation' ) ) {
	//require_once('ic_commerce_premium_golden_fuctions.php');
	class IC_Commerce_Premium_Golden_Variation extends IC_Commerce_Premium_Golden_Fuctions{
		
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
			$this->today			= date_i18n("Y-m-d");$this->is_active();
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
				
				
				$product_status 		= $this->get_setting('product_status',$this->constants['plugin_options'], array());				
				$product_status			= implode("', '",$product_status);				
				$product_status			= strlen($product_status) > 0 ?  $product_status 	: '-1';
				
				$default_tab 			= apply_filters('ic_commerce_variation_page_default_tab', 	'product_page');
				$report_name 			= $this->get_request('report_name',$default_tab,true);
				$start_date 			= apply_filters('ic_commerce_variation_page_start_date',	$this->constants['start_date'],$report_name);
				$end_date 				= apply_filters('ic_commerce_variation_page_end_date',		$this->constants['end_date'],$report_name);
				$order_status			= apply_filters('ic_commerce_variation_page_selected_order_status', $order_status,$report_name);
				$product_status			= apply_filters('ic_commerce_variation_page_selected_product_status', $product_status,$report_name);
				$onload_search			= apply_filters('ic_commerce_variation_page_onload_search', "yes", $report_name);
				$onload_search				= apply_filters('ic_commerce_onload_search', $onload_search, $report_name);
				
				$publish_order			= "no";
				
				$sort_by 				= $this->get_request('sort_by','product_name',true);
				$order_by 				= $this->get_request('order_by','DESC',true);
				
				$page					= $this->get_request('page',NULL);	
				$show_variation 		= get_option($page.'_show_variation','variable');				
				$optionsid				= "per_row_variation_page";
				$per_page 				= $this->get_number_only($optionsid,$this->per_page_default);				
				$report_name 			= $this->get_request('report_name',$report_name,true);
				$admin_page				= $this->get_request('admin_page',$page,true);
				$adjacents				= $this->get_request('adjacents','3',true);
				$p						= $this->get_request('p','1',true);
				$limit					= $this->get_request('limit',$per_page,true);
				$ToDate					= $this->get_request('end_date',$end_date, false);
				$FromDate				= $this->get_request('start_date',$start_date, false);
				$category_id			= $this->get_request('category_id','-1',true);
				$order_status_id		= $this->get_request('order_status_id',$order_status_id,true);
				$order_status			= $this->get_request('order_status',$order_status,true);
				$publish_order			= $this->get_request('publish_order',$publish_order,true);
				$hide_order_status		= $this->get_request('hide_order_status',$hide_order_status,true);
				$product_id				= $this->get_request('product_id','-1',true);
				$variations				= $this->get_request('variations','-1',true);
				$variation_column		= $this->get_request('variation_column','1',true);
				$show_variation			= $this->get_request('show_variation',$show_variation,true);
				$count_generated		= $this->get_request('count_generated',0,true);	
				$product_status			= $this->get_request('product_status',$product_status,true);
							
				if($this->is_product_active != 1)  return true;
				$action					= $this->get_request('action',$this->constants['plugin_key'].'_wp_ajax_action',true);
				$do_action_type			= $this->get_request('do_action_type','variation_page',true);
				
				$first_date 			= $this->constants['first_order_date'];
				
				if(!$ToDate){$ToDate = date_i18n('Y-m-d');}
				if(!$FromDate){$FromDate = $first_date;}
				
				$_REQUEST['end_date'] = $ToDate;
				$_REQUEST['start_date'] = $FromDate;
				
				$page_titles = array(
					'product_page'=>__('Variation Products','icwoocommerce_textdomains')
				);
				
				$page_title 		= isset($page_titles[$report_name]) ? $page_titles[$report_name] : $report_name;				
				$page_title 		= apply_filters($page.'_report_name_'.$report_name, $page_title);
				
				$_REQUEST['page_title'] = $page_title;
				if($report_name != 'coupon_page')
					$_REQUEST['page_name'] = 'all_detail';
				else
					$_REQUEST['page_name'] = $report_name;
				
				$new_transaction_variation = array();
				?>
               
                <h2 class="hide_for_print"><?php _e($page_title,'icwoocommerce_textdomains'." Variation");?></h2>
                <br> 
                <?php if($report_name != 'coupon_page'):?>
                <div id="navigation" class="hide_for_print">
                        <div class="collapsible" id="section1"><?php _e("Custom Search",'icwoocommerce_textdomains');?><span></span></div>
                        <div class="container">
                            <div class="content">
                                <div class="search_report_form">
                                    <div class="form_process"></div>
                                    <form action="" name="Report" id="search_order_report" method="post">
                                        <div class="form-table">
                                            <div class="form-group">
                                                <div class="FormRow FirstRow">
                                                    <div class="label-text"><label for="start_date"><?php _e("From Date:",'icwoocommerce_textdomains');?></label></div>
                                                    <div class="input-text"><input type="text" value="<?php echo $FromDate;?>" id="start_date" name="start_date" readonly maxlength="10" /></div>
                                                </div>
                                                <div class="FormRow">
                                                    <div class="label-text"><label for="end_date"><?php _e("To Date:",'icwoocommerce_textdomains');?></label></div>
                                                    <div class="input-text"><input type="text" value="<?php echo $ToDate;?>" id="end_date" name="end_date" readonly maxlength="10" /></div>
                                                </div>
                                            </div>
                                            <?php if($report_name == 'product_page'):?>
                                            <div class="form-group">
                                                <div class="FormRow FirstRow">
                                                    <div class="label-text"><label for="category_id2"><?php _e("Category:",'icwoocommerce_textdomains');?></label></div>
                                                    <div class="input-text">
                                                        <?php 
                                                           	$category_data = $this->get_category_data();
                                                            $this->create_dropdown($category_data,"category_id[]","category_id2","Select All","category_id2",$category_id, 'object', true, 5);
                                                        ?>                                                        
                                                    </div>                                                    
                                                </div>
                                                <div class="FormRow">
                                                    <div class="label-text"><label for="product_id"><?php _e("Product:",'icwoocommerce_textdomains');?></label></div>
                                                    <div class="input-text">
                                                        <?php 
                                                            $product_data = $this->get_product_data($show_variation);
                                                            $this->create_dropdown($product_data,"product_id[]","product_id2","Select All","product_id2",$product_id, 'object', true, 5);
                                                        ?>
                                                    </div>                                                    
                                                </div>
                                            </div>
                                            
                                             <div class="form-group">
                                                 <div class="FormRow FirstRow">
                                                    <div class="label-text"><label for="paid_customer"><?php _e("Customer:",'icwoocommerce_textdomains');?></label></div>
                                                    <div class="input-text">
                                                        <?php 
                                                            $data = $this->get_order_customer();
															$paid_customer				= $this->get_request('paid_customer','-1');
                                                            $this->create_dropdown($data,"paid_customer[]","paid_customer","Select All","product_id",$paid_customer, 'object', true, 5);
                                                        ?>
                                                    </div>
                                                </div>
                                                <div class="FormRow">
                                                    <div class="label-text"><label for="billing_postcode"><?php _e("Postcode(Zip):",'icwoocommerce_textdomains');?></label></div>
                                                    <div class="input-text"><input type="text" id="billing_postcode" name="billing_postcode" class="regular-text" maxlength="100" value="<?php echo $this->get_request('billing_postcode','',true);?>" /></div>
                                                </div>
                                            </div>
                                                                              
                                            <div class="form-group">                                                
                                                <div class="FormRow FirstRow">
                                                    <div class="label-text"><label for="order_status_id2"><?php _e("Status:",'icwoocommerce_textdomains');?></label></div>
                                                    <div class="input-text">
                                                        <?php
                                                            if($this->constants['post_order_status_found'] == 0 ){					
																$data = $this->ic_get_order_statuses_slug_id('shop_order_status');
																$this->create_dropdown($data,"order_status_id[]","order_status_id","Select All","product_id",$order_status_id, 'object', false);
																
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
                                               
                                                <div class="FormRow">
                                                    <div class="label-text"><label for="variations"><?php _e("Variations:",'icwoocommerce_textdomains');?></label></div>
                                                    <div class="input-text">
                                                        <?php
                                                            $transaction_variation	= $this->get_product_variation_attributes("yes");
															///$data	= $this->get_attributes('-1');
															//$this->print_array($transaction_variation);
															if($transaction_variation){
																$enable_variation = true;
																$new_attr = array();
																foreach($transaction_variation as $key => $value){
																	//$new_key = str_replace("wcv_","",$key);
																	$new_key = $key;
																	$new_transaction_variation[$new_key] = $value;
																}
																$data = NULL;
																//$this->print_array($new_transaction_variation);
                                                            	$this->create_dropdown($new_transaction_variation,"variations[]","variations","Select All","variations",$variations, 'array', true, 5);
															}else{
																$enable_variation = false;	
																echo __("There is no any order purchased in variable product.",'icwoocommerce_textdomains');
															}
                                                        ?>
                                                    </div>
                                                    <span class="detail_view_seciton detail_variation_seciton_note"><?php _e("Enable variations selection by clicking show variable products",'icwoocommerce_textdomains');?></span>
                                                </div>                                                
                                            </div>
                                            
                                            <?php do_action('ic_commerce_variation_page_above_variation_dropdown_fields', $this, $new_transaction_variation);?>
                                            
                                            <?php if($enable_variation):?>
                                            
                                            <?php do_action('ic_commerce_variation_page_above_show_fields', $this, $new_transaction_variation,$page);?>
                                            
                                            <div class="form-group">
                                            	<div class="FormRow FirstRow checkbox">
                                                    <div class="label-text" style="padding-top:0px;"><label for="show_variation"><?php _e("Show:",'icwoocommerce_textdomains');?></label></div>
                                                    <!--<div style="padding-top:0px;"><input type="checkbox" name="show_variation" id="show_variation" value="1"<?php if($show_variation == 1) echo ' checked="checked"';?> /></div>-->
                                                    <div class="input-text">
                                                        <?php
                                                            $product_type	= array("variable" => __("Variation Products",'icwoocommerce_textdomains'),"simple" => __("Simple Products",'icwoocommerce_textdomains'), "-1" => __("All Products",'icwoocommerce_textdomains'));
														    //$data = $this->get_category_data('product_type');
															//$product_type = array();															
															//foreach($data as $key => $value)$product_type[$value->label] = ucfirst($value->label." Products");
															//$product_type[0] = 'All Products';															
                                                            $this->create_dropdown($product_type,"show_variation","show_variation","","show_variation",$show_variation, 'array', false);
                                                        ?>
                                                    </div>
                                                </div>
                                                <div class="FormRow">
                                                    <div class="label-text" style="padding-top:0px;"><label for="variation_column"><?php _e("Style:",'icwoocommerce_textdomains');?></label></div>
                                                    <div class="input-text">
                                                        <?php
                                                            $data	= array("1" => __("Columner",'icwoocommerce_textdomains'),"0" => __("Comma Separated",'icwoocommerce_textdomains'));
                                                            $this->create_dropdown($data,"variation_column[]","variation_column","","variation_column",$variation_column, 'array', false);
                                                        ?>
                                                    </div>
                                                    <span class="detail_view_seciton detail_variation_seciton_note"><?php _e("Enable style selection by clicking show variable products",'icwoocommerce_textdomains');?></span>
                                                </div>
                                            </div>
                                            
												<?php
                                                    $product_sku_data = $this->get_product_sku();
                                                    $variation_sku_data = $this->get_variation_sku();									
                                                    if($product_sku_data or $variation_sku_data){
                                                ?>  
                                                <div class="form-group">
                                                	<?php if($product_sku_data){?>
                                                    <div class="FormRow FirstRow">
                                                        <div class="label-text"><label for="product_sku"><?php _e("Product SKU:",'icwoocommerce_textdomains');?></label></div>
                                                        <div class="input-text">
                                                            <?php $this->create_dropdown($product_sku_data,"product_sku[]","product_sku","Select All","product_sku",'-1', 'object', true, 5);?>
                                                        </div>                                                        
                                                    </div>
                                                    <?php } ?>
                                                    <?php if($variation_sku_data){?>
                                                    <div class="FormRow">
                                                        <div class="label-text"><label for="variation_sku"><?php _e("Variation SKU:",'icwoocommerce_textdomains');?></label></div>
                                                        <div class="input-text">
                                                            <?php $this->create_dropdown($variation_sku_data,"variation_sku[]","variation_sku","Select All","variation_sku variation_fields",'-1', 'object', true, 5);?>
                                                        </div>                                                        
                                                    </div>
                                                    <?php } ?>
                                                </div>
                                            <?php } ?>
                                            <?php else:?>
                                            	<input type="hidden" name="show_variation"  id="show_variation" value="0" />
                                                <input type="hidden" name="sort_by"  id="sort_by" value="<?php echo $sort_by;?>" />
                                                <input type="hidden" name="order_by"  id="order_by" value="<?php echo $order_by;?>" />
											<?php endif;?>
											<?php endif;?>
                                            
                                            
                                            <?php do_action('ic_commerce_variation_page_searrch_form_above_order_by', $this, $new_transaction_variation);?>
                                            
                                            <div class="form-group"> 
                                            	<div class="FormRow FirstRow">
                                                    <div class="label-text" style="padding-top:0px;"><label for="sort_by"><?php _e("Order By:",'icwoocommerce_textdomains');?></label></div>
                                                        <div style="padding-top:0px;">
                                                         <?php
                                                            $data = array("product_name" => __("Product Name",'icwoocommerce_textdomains'),"product_id" => __("Product ID",'icwoocommerce_textdomains'),'variation_id'=>__('Variation ID','icwoocommerce_textdomains'), "amount" => __("Amount",'icwoocommerce_textdomains'));
                                                            $this->create_dropdown($data,"sort_by","sort_by",NULL,"sort_by",$sort_by, 'array');
                                                            $data = array("ASC" => __("Ascending",'icwoocommerce_textdomains'), "DESC" => __("Descending",'icwoocommerce_textdomains'));
                                                            $this->create_dropdown($data,"order_by","order_by",NULL,"order_by",$order_by, 'array');
                                                          ?>
                                                        </div>
                                                        
                                                    </div>                                               
                                                <div class="FormRow">
                                                    <div class="label-text"><label for="group_by"><?php _e("Group By:",'icwoocommerce_textdomains');?></label></div>
                                                    <div class="input-text">
                                                        <?php
                                                           $data = array("variation_id" => __("Variation ID",'icwoocommerce_textdomains'), "order_item_id" => __("Order Item ID",'icwoocommerce_textdomains'));
                                                            $this->create_dropdown($data,"group_by","variation_group_by",'',"group_by",'', 'array');
                                                        ?>
                                                    </div>
                                                </div>                                          
                                            </div>
                                            
                                            <?php do_action('ic_commerce_variation_page_searrch_form_below_order_by', $this, $new_transaction_variation);?>
                                                                                        
                                            <div class="form-group">
                                                <div class="FormRow " style="width:100%">
                                                        <input type="hidden" name="hide_order_status"	id="hide_order_status" 		value="<?php echo $hide_order_status;?>" />
                                                        <input type="hidden" name="action" 				id="action"					value="<?php echo $this->constants['plugin_key'].'_wp_ajax_action';?>" />
                                                        <input type="hidden" name="limit" 				id="limit"					value="<?php echo $limit;?>" />
                                                        <input type="hidden" name="p"  					id="p" 						value="<?php echo $p;?>" />
                                                        <input type="hidden" name="admin_page" 			id="admin_page" 			value="<?php echo $admin_page;?>" />
                                                        <input type="hidden" name="page"  				id="page"					value="<?php echo $page;?>" />
                                                        <input type="hidden" name="adjacents"  			id="adjacents" 				value="<?php echo $adjacents;?>" />
                                                        <input type="hidden" name="report_name"  		id="report_name" 			value="<?php echo $report_name;?>" />
                                                        <input type="hidden" name="do_action_type" 		id="do_action_type" 		value="<?php echo $do_action_type;?>" /> 
                                                        <input type="hidden" name="page_title" 			id="page_title"				value="<?php echo $page_title;?>" />
                                                        <input type="hidden" name="page_name"  			id="page_name" 				value="all_detail" />
                                                        <input type="hidden" name="count_generated" 	id="count_generated"		value="<?php echo $count_generated;?>" />
                                                        <input type="hidden" name="date_format" 		id="date_format" 			value="<?php echo $this->get_request('date_format',get_option('date_format'),true);?>" />
                                                        <input type="hidden" name="onload_search" 		id="onload_search" 			value="<?php echo $this->get_request('onload_search',$onload_search,true);?>" />
                                                        <input type="hidden" name="product_status" 		id="product_status"			value="<?php echo $product_status;?>" />
                                                        <span class="submit_buttons">
                                                            <?php if($report_name == 'product_page'):?>
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
                    <?php //$this->print_array($this->get_product_data('0')); return;?>
                    <script type="text/javascript">
						var products 				= <?php echo json_encode($this->get_product_data('0'));?>;
						var simple_products			= <?php echo json_encode($this->get_product_data('simple'));?>;
						var variation_products 		= <?php echo json_encode($this->get_product_data(1));?>;
						
						jQuery(document).ready(function(e) {
                            jQuery('#ResetForm').click(function(){
								enable_disable_product_variation_fields();
							});
                        });
                    </script>
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
						$mngpg 					= $admin_page_url.'?page='.$admin_page;
						$billing_information 	= $this->get_setting('billing_information',$this->constants['plugin_options'], 0);
						$shipping_information 	= $this->get_setting('shipping_information',$this->constants['plugin_options'], 0);
						$logo_image 			= $this->get_setting('logo_image',$this->constants['plugin_options'], '');
						$report_title 			= $this->get_setting('report_title',$this->constants['plugin_options'], '');
						$company_name 			= $this->get_setting('company_name',$this->constants['plugin_options'], '');
						$page_title				= $this->get_request('page_title',NULL,true);							
						
						$set_report_title		= $report_title;							
						if($page_title) $page_title = " (".$page_title.")";							
						$report_title = $report_title.$page_title;
						$print_do_action_type	= $do_action_type."_for_print";
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
									<td><input id="report_title_print" name="report_title" value="<?php echo $report_title;?>" type="text" class="textbox"></td>
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
									<td colspan="2"><input type="button" name="<?php echo $admin_page ;?>_export_print" class="onformprocess button_popup_close search_for_print" value="<?php _e("Print",'icwoocommerce_textdomains');?>" data-form="popup"  data-do_action_type="<?php echo $print_do_action_type;?>" /></td>
								</tr>                                
							</table>
							<input type="hidden" name="display_center" value="1" />
						</form>
						<div class="clear"></div>
						</div>
					</div>
					<div class="popup_mask"></div>
                    
                    <style type="text/css">
                    	.widefat.summary_table{
							width:auto;
						}
						.widefat.summary_table td, .widefat.summary_table th{
							text-align:right;
						}
						
						/*th.product_id, td.product_id,*/
						th.quantity, td.quantity, 
						th.stock, td.stock, 
						th.td_right, td.td_right, 
						th.variation_stock, td.variation_stock,
						th.product_stock, td.product_stock{ text-align:right;}
						
						/*dynamic_fields*/
						
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
							$columns			= $this->get_product_variation_columns();
							echo $this->get_pdf_style_align($columns,'right','.iccommercepluginwrap ','', $report_name);
						?>
                    </style>
                    <?php do_action("ic_commerce_variation_page_footer_area",$page);?>
                <?php
		}
		
		
		var $variation_query = '';
		function ic_commerce_report_ajax_request($type = 'limit_row'){
			global $report_title;			
			//$this->print_array($_REQUEST);
			$report_name = $this->get_request('report_name','product_page',true);
			switch ($report_name ) {
					case "product_page":
						$report_title = __("Product List",'icwoocommerce_textdomains');
						$this->print_header($type,$report_title);
						$this->ic_commerce_custom_all_product_column_variation($type);
						break;
				}
				$_REQUEST['page_title'] = $report_title;		
		}
		
		/*All Product List*/
		var $all_row_result = NULL;
		function ic_commerce_custom_all_product_query($type = 'limit_row'){
			global $wpdb;
			$request = $this->get_all_request();extract($request);
			
			$product_status				= $this->get_string_multi_request('product_status',$product_status, "-1");
			
			$columns_sql = " SELECT ";
			if($type == 'total_row'){
				$columns_sql .= "
							SUM(woocommerce_order_itemmeta.meta_value) 			AS 'quantity'
							,SUM(woocommerce_order_itemmeta6.meta_value) 		AS 'amount'
							,DATE(shop_order.post_date) 						AS post_date						
							,woocommerce_order_itemmeta_product_id.meta_value 	AS product_id
							,woocommerce_order_items.order_item_id 				AS order_item_id
							,woocommerce_order_items.order_id 					AS order_id";
				
				if($show_variation == 'variable') {
					$columns_sql .= ", woocommerce_order_itemmeta8.meta_value AS 'variation_id'";
					
					if($sort_by == "sku")
						$columns_sql .= ", IF(postmeta_sku.meta_value IS NULL or postmeta_sku.meta_value = '', IF(postmeta_product_sku.meta_value IS NULL or postmeta_product_sku.meta_value = '', '', postmeta_product_sku.meta_value), postmeta_sku.meta_value) as sku ";
				}else{
					if($sort_by == "sku")
						$columns_sql .= ", IF(postmeta_product_sku.meta_value IS NULL or postmeta_product_sku.meta_value = '', 'T', postmeta_product_sku.meta_value) as sku";
				}
			}else{
				$columns_sql .= "
							SUM(woocommerce_order_itemmeta.meta_value)		AS 'quantity'
							,SUM(woocommerce_order_itemmeta6.meta_value)	AS 'amount'
							,DATE(shop_order.post_date)						AS post_date
							,woocommerce_order_itemmeta_product_id.meta_value			AS product_id
							,woocommerce_order_items.order_item_id 			AS order_item_id
							,woocommerce_order_items.order_id 					AS order_id";
				
				if($show_variation == 'variable') {
					
					$columns_sql .= ", woocommerce_order_itemmeta8.meta_value AS 'variation_id'";	
									
					if($sort_by == "sku")
						$columns_sql .= ", IF(postmeta_sku.meta_value IS NULL or postmeta_sku.meta_value = '', IF(postmeta_product_sku.meta_value IS NULL or postmeta_product_sku.meta_value = '', '', postmeta_product_sku.meta_value), postmeta_sku.meta_value) as sku ";
						
				}else{
					if($sort_by == "sku")
						$columns_sql .= ", IF(postmeta_product_sku.meta_value IS NULL or postmeta_product_sku.meta_value = '', '', postmeta_product_sku.meta_value) as sku";
				}
			}
			
			if($product_status != NULL  && $product_status != '-1'){
				$columns_sql .= " , products.post_title			AS 'product_name'";
			}else{
				$columns_sql .= " , woocommerce_order_items.order_item_name	AS 'product_name'";
			}
			
			if(($variation_itemmetakey != "-1" and strlen($variation_itemmetakey)>1)){				
				$columns_sql .= " , woocommerce_order_itemmeta_variation.meta_key AS variation_key";
				$columns_sql .= " , woocommerce_order_itemmeta_variation.meta_value AS variation_value";
			}
			
			$columns_sql = apply_filters("ic_commerce_variation_page_select_query", $columns_sql, $request, $type, $page);
			
			if(!$this->variation_query){
				
				$order_status		= $this->get_string_multi_request('order_status',$order_status, "-1");
				$hide_order_status	= $this->get_string_multi_request('hide_order_status',$hide_order_status, "-1");
				$paid_customer		= $this->get_string_multi_request('paid_customer',$paid_customer, "-1");
				$billing_postcode	= $this->get_string_multi_request('billing_postcode',$billing_postcode, "-1");
				$product_sku		= $this->get_string_multi_request('product_sku',$product_sku, "-1");//New Change ID 20150226
				$variation_sku		= $this->get_string_multi_request('variation_sku',$variation_sku, "-1");//New Change ID 20150228
				
				$category_product_id_string = $this->get_products_list_in_category($category_id,$product_id);//Added 20150219
				$category_id 				= "-1";//Added 20150219
				
				$sql = "
							FROM {$wpdb->prefix}woocommerce_order_items as woocommerce_order_items						
							LEFT JOIN  {$wpdb->prefix}woocommerce_order_itemmeta as woocommerce_order_itemmeta ON woocommerce_order_itemmeta.order_item_id	= woocommerce_order_items.order_item_id
							LEFT JOIN  {$wpdb->prefix}woocommerce_order_itemmeta as woocommerce_order_itemmeta6 ON woocommerce_order_itemmeta6.order_item_id= woocommerce_order_items.order_item_id
							LEFT JOIN  {$wpdb->prefix}woocommerce_order_itemmeta as woocommerce_order_itemmeta_product_id ON woocommerce_order_itemmeta_product_id.order_item_id= woocommerce_order_items.order_item_id";
							
				
				
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
				
				if($show_variation == 'variable'){
					$sql .= " 
							LEFT JOIN  {$wpdb->prefix}woocommerce_order_itemmeta as woocommerce_order_itemmeta8 ON woocommerce_order_itemmeta8.order_item_id = woocommerce_order_items.order_item_id
							
							";
					if(($sort_by == "sku") || ($product_sku and $product_sku != '-1') || $variation_sku != '-1')
						$sql .= "	LEFT JOIN  {$wpdb->prefix}postmeta as postmeta_sku 		ON postmeta_sku.post_id		= woocommerce_order_itemmeta8.meta_value";
							
					//if(($variation_attributes != "-1" and strlen($variation_attributes)>1) || ($variations_formated  != "-1" and $variations_formated  != NULL))
						//$sql .= " LEFT JOIN  {$wpdb->prefix}postmeta as postmeta_variation ON postmeta_variation.post_id = woocommerce_order_itemmeta8.meta_value";
					//echo $variation_itemmetakey;
					if(($variation_itemmetakey != "-1" and strlen($variation_itemmetakey)>1)){
						$sql .= " LEFT JOIN  {$wpdb->prefix}woocommerce_order_itemmeta as woocommerce_order_itemmeta_variation ON woocommerce_order_itemmeta_variation.order_item_id= woocommerce_order_items.order_item_id";
					}
					
					if(isset($_REQUEST['new_variations_value']) and count($_REQUEST['new_variations_value'])>0){
						foreach($_REQUEST['new_variations_value'] as $key => $value){
							$new_v_key = "wcvf_".$this->remove_special_characters($key);
							$sql .= " LEFT JOIN  {$wpdb->prefix}woocommerce_order_itemmeta as woocommerce_order_itemmeta_{$new_v_key} ON woocommerce_order_itemmeta_{$new_v_key}.order_item_id = woocommerce_order_items.order_item_id";
						}
					}
					
				}
				
				if(($sort_by == "sku") || ($product_sku and $product_sku != '-1'))
					$sql .= "	LEFT JOIN  {$wpdb->prefix}postmeta		 as postmeta_product_sku 		ON postmeta_product_sku.post_id 			= woocommerce_order_itemmeta_product_id.meta_value	";				
				
				$sql .= " LEFT JOIN  {$wpdb->prefix}posts as shop_order ON shop_order.id=woocommerce_order_items.order_id";//For shop_order
				
				if($show_variation == 2 || ($show_variation == 'grouped' || $show_variation == 'external' || $show_variation == 'simple' || $show_variation == 'variable_')){
					$sql .= " 	
							LEFT JOIN  {$wpdb->prefix}term_relationships 	as term_relationships_product_type 	ON term_relationships_product_type.object_id		=	woocommerce_order_itemmeta_product_id.meta_value 
							LEFT JOIN  {$wpdb->prefix}term_taxonomy 		as term_taxonomy_product_type 		ON term_taxonomy_product_type.term_taxonomy_id		=	term_relationships_product_type.term_taxonomy_id
							LEFT JOIN  {$wpdb->prefix}terms 				as terms_product_type 				ON terms_product_type.term_id						=	term_taxonomy_product_type.term_id";
				}
				
				if($paid_customer  && $paid_customer != '-1' and $paid_customer != "'-1'"){
					$sql .= " 
						LEFT JOIN  {$wpdb->prefix}postmeta 			as postmeta_billing_email				ON postmeta_billing_email.post_id=woocommerce_order_items.order_id";
				}
				
				if($billing_postcode and $billing_postcode != '-1'){
					$sql .= " LEFT JOIN  {$wpdb->prefix}postmeta as postmeta_billing_postcode ON postmeta_billing_postcode.post_id	=	woocommerce_order_items.order_id";
				}
				
				if($variations_formated  != "-1" and $variations_formated  != NULL){
					//$sql .= " LEFT JOIN  {$wpdb->prefix}woocommerce_order_itemmeta as woocommerce_order_itemmeta8 ON woocommerce_order_itemmeta8.order_item_id = woocommerce_order_items.order_item_id";
					//$sql .= " LEFT JOIN  {$wpdb->prefix}postmeta as postmeta_variation ON postmeta_variation.post_id = woocommerce_order_itemmeta8.meta_value";

				}
				
				if($product_status != NULL  && $product_status != '-1'){
					$sql .= " LEFT JOIN {$wpdb->prefix}posts AS products ON products.ID = woocommerce_order_itemmeta_product_id.meta_value";
				}
				
				$filter_sql = apply_filters("ic_commerce_variation_page_join_query", '', $request, $type, $page);
				$sql .= $filter_sql;
				
				
				
				$sql .= "
							WHERE woocommerce_order_itemmeta.meta_key	= '_qty'
							AND woocommerce_order_itemmeta6.meta_key	= '_line_total' 
							AND woocommerce_order_itemmeta_product_id.meta_key 	= '_product_id'						
							AND shop_order.post_type					= 'shop_order'
							";
							
				if($show_variation == 'variable'){
					$sql .= "
							AND woocommerce_order_itemmeta8.meta_key = '_variation_id' 
							AND (woocommerce_order_itemmeta8.meta_value IS NOT NULL AND woocommerce_order_itemmeta8.meta_value > 0)
							";
					
					if(($sort_by == "sku") || ($variation_sku and $variation_sku != '-1'))
											$sql .=	" AND postmeta_sku.meta_key	= '_sku'";
					/*						
					if($variations_formated  != "-1" and $variations_formated  != NULL){						
						//$sql .= " AND postmeta_variation.meta_value IN ('{$variations_formated}')";
					}
							
					if(($variation_attributes != "-1" and strlen($variation_attributes)>1)){
						//$sql .= " AND postmeta_variation.meta_key IN ('{$variation_attributes}')";
					}
					*/
					
					if(($variation_itemmetakey != "-1" and strlen($variation_itemmetakey)>1)){
						$sql .= " AND woocommerce_order_itemmeta_variation.meta_key IN ('{$variation_itemmetakey}')";
					}
					
					if(isset($_REQUEST['new_variations_value']) and count($_REQUEST['new_variations_value'])>0){
						foreach($_REQUEST['new_variations_value'] as $key => $value){
							$new_v_key = "wcvf_".$this->remove_special_characters($key);
							$key = str_replace("'","",$key);
							$sql .= " AND woocommerce_order_itemmeta_{$new_v_key}.meta_key = '{$key}'";
							$vv = is_array($value) ? implode(",",$value) : $value;
							//$vv = str_replace("','",",",$vv);
							$vv = str_replace(",","','",$vv);
							$sql .= " AND woocommerce_order_itemmeta_{$new_v_key}.meta_value IN ('{$vv}') ";
						}
					}
				}
				
				
				if(($sort_by == "sku") || ($product_sku and $product_sku != '-1'))
					$sql .= " AND postmeta_product_sku.meta_key			= '_sku'";
				
				if($show_variation == 'variable'){
					if(($product_sku and $product_sku != '-1') and ($variation_sku and $variation_sku != '-1')){
						$sql .= " AND (postmeta_product_sku.meta_value IN (".$product_sku.") AND postmeta_sku.meta_value IN (".$variation_sku."))";//New Change ID 20150226
					}else if ($variation_sku and $variation_sku != '-1'){
						$sql .= " AND postmeta_sku.meta_value IN (".$variation_sku.")";
					}else{
						if($product_sku and $product_sku != '-1') $sql .= " AND postmeta_product_sku.meta_value IN (".$product_sku.")";//New Change ID 20150226
					}
				}else{
					if($product_sku and $product_sku != '-1') $sql .= " AND postmeta_product_sku.meta_value IN (".$product_sku.")";//New Change ID 20150226
				}
				
				if ($start_date != NULL &&  $end_date !=NULL){
					$sql .= " 
							AND (DATE(shop_order.post_date) BETWEEN '".$start_date."' AND '". $end_date ."')";
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
				
				
				if($show_variation == 'grouped' || $show_variation == 'external' || $show_variation == 'simple' || $show_variation == 'variable_'){
					$sql .= " AND terms_product_type.name IN ('{$show_variation}')";
				}
				
				if($show_variation == 2){
					$sql .= " AND terms_product_type.name IN ('simple')";
				}
				
				if($paid_customer  && $paid_customer != '-1' and $paid_customer != "'-1'"){
					$sql .= " AND postmeta_billing_email.meta_key='_billing_email'";
					$sql .= " AND postmeta_billing_email.meta_value IN (".$paid_customer.")";
				}
				
				if($billing_postcode and $billing_postcode != '-1'){
					$sql .= " AND postmeta_billing_postcode.meta_key='_billing_postcode' AND postmeta_billing_postcode.meta_value IN ({$billing_postcode}) ";
				}
				
				
				
				if($order_status  && $order_status != '-1' and $order_status != "'-1'")$sql .= " AND shop_order.post_status IN (".$order_status.")";
				
				if($hide_order_status  && $hide_order_status != '-1' and $hide_order_status != "'-1'")$sql .= " AND shop_order.post_status NOT IN (".$hide_order_status.")";
				
				if($product_status != NULL  && $product_status != '-1'){
					$sql .= " AND products.post_type IN ('product')";
					$sql .= " AND products.post_status IN ({$product_status})";
				}
				
				
				$filter_sql = apply_filters("ic_commerce_variation_page_where_query", '', $request, $type, $page);
				$sql .= $filter_sql;
				
				//$this->print_sql($sql);
				
				$this->variation_query = $sql;
				
				$sql = "";
				
			}else{
				$sql = $this->variation_query;
			}
			
			$sql = $columns_sql;		
			$sql .= $this->variation_query;
				
			if($show_variation == 'variable'){
				switch ($group_by) {
					case "variation_id":
						$sql .= " GROUP BY woocommerce_order_itemmeta8.meta_value ";
						break;
					case "order_item_id":
						$sql .= " GROUP BY woocommerce_order_items.order_item_id ";
						break;
					default:
						$sql .= " GROUP BY woocommerce_order_itemmeta8.meta_value ";
						break;
					
				}
				//$sql .= " GROUP BY woocommerce_order_itemmeta8.meta_value ";
			}else{
				$sql .= " 
						GROUP BY  woocommerce_order_itemmeta_product_id.meta_value";
			}
			
			//echo $sql;
			
			if($type == 'total_row'){
				if($this->all_row_result){
					if($count_generated == 1){
						$order_items = $this->create_summary($request);
					}else{
						$order_items = $this->all_row_result;
						$summary = $this->get_count_total($order_items,'amount');				
						$order_items = $summary;
					}
					
				}else{					
					if($count_generated == 1 || ($p > 1)){
						$order_items = $this->create_summary($request);
					}else{
						$wpdb->query("SET SQL_BIG_SELECTS=1");
						$order_items = $wpdb->get_results($sql);
						$order_items= apply_filters("ic_commerce_variation_page_data_items",$order_items, $request, $type, $page);
						//echo mysql_error();
						$summary = $this->get_count_total($order_items,'amount');				
						$order_items = $summary;
					}					
				}								
				return $order_items;
			}
			
			if($type == 'limit_row' || $type == 'all_row'){
				$wpdb->query("SET SQL_BIG_SELECTS=1");
				switch ($sort_by) {
					case "sku":
						$sql .= " ORDER BY sku " .$order_by;
						break;
					case "product_name":
						$sql .= " ORDER BY product_name " .$order_by;
						break;
					case "ProductID":
						//CAST(column AS DECIMAL(10,2))
						//$sql .= " ORDER BY product_id " .$order_by;
						$sql .= " ORDER BY CAST(product_id AS DECIMAL(10,2)) " .$order_by;
						break;
					case "amount":
						$sql .= " ORDER BY amount " .$order_by;
						break;
					case "variation_id":
						if($show_variation == 'variable'){
							$sql .= " ORDER BY CAST(variation_id AS DECIMAL(10,2)) " .$order_by;
						}
						break;		
					default:
						$sql .= " ORDER BY amount DESC";					
						break;					
				}				
			}			
			
			if($type == 'limit_row'){
				$sql .= " LIMIT $start, $limit";				
				$order_items = $wpdb->get_results($sql);
				//echo mysql_error();
				
				//$this->print_sql($sql);
				//$this->print_array($order_items);
			}
						
			if($type == 'all_row'){				
				$wpdb->query("SET SQL_BIG_SELECTS=1");
				$order_items = $wpdb->get_results($sql);				
				//echo mysql_error();
			}
			
			if($type == 'limit_row' || $type == 'all_row' or $type == 'all_row_total'){
				if(count($order_items)>0){
					/*$variation_by = "variation_id";
					if($variation_by == 'variation_by'){
						$this->get_variaiton_attributes('variation_id');
					}else if($variation_by == 'variation_by'){
						$this->get_variaiton_attributes('variation_id');
					}*/
					
					foreach ( $order_items as $key => $order_item ) {
						
							/*$product_id								= $order_item->product_id;
							
							if(!isset($order_meta[$product_id])){
								$order_meta[$product_id]					= $this->get_all_post_meta($product_id);
							}
							
							foreach($order_meta[$product_id] as $k => $v){
								$order_items[$key]->$k			= $v;
							}*/
					}
				}
			}
			if($type == 'limit_row'){
				//$this->print_array($order_items);
			}
			
			if($type == 'all_row'){
				$this->all_row_result = $order_items;
			}
			
			$order_items = apply_filters("ic_commerce_variation_page_data_items",$order_items, $request, $type, $page);
			
			return $order_items;
		}
				
		function ic_commerce_custom_all_product_column_variation($type = 'limit_row'){
					$TotalOrderCount 	= 0;
					$TotalAmount 		= 0;
					$TotalShipping		= 0;
					$request 			= $this->get_all_request();extract($request);
					$order_items 		= $this->ic_commerce_custom_all_product_query($type);
					if($type == 'limit_row'){
						update_option($page.'_show_variation',$show_variation);						
					}
					
					//$this->get_product_variation_attributes();
					
					if(count($order_items) > 0):					
						$summary 			= $this->ic_commerce_custom_all_product_query('total_row');
						$total_row_amount	= $summary['total_row_amount'];
						$total_row_count	= $summary['total_row_count'];
						//$attributes			= $this->get_attributes('selected');
						//$all_attributes		= $this->get_attributes('-1');
						//$this->print_array($all_attributes);
						//$ToDate 			= $this->today;
					    //$FromDate 			= $this->first_order_date($this->constants['plugin_key']);
					    $admin_url 			= admin_url("admin.php?page=".$this->constants['plugin_key']."_details_page")."&end_date=".$end_date."&start_date=".$start_date."&detail_view=yes&product_id=";
						$product_url		= admin_url("post.php?action=edit")."&post=";
						$columns			= $this->get_product_variation_columns();
						/*
						$attrs		= array();
						foreach($attributes as $key => $value):
							$attrs[] = $key;
						endforeach;
						*/
						
						$clickeble 			= true;
						/*
						if($this->constants['plugin_key'] == "icwoocommercevariation")					
							$clickeble 			= false;
						else
							$clickeble 			= true;
						*/
						if($show_variation == 'variable'){
							$order_items = $this->get_grid_items_variation($columns,$order_items);
						}	
						
						//$this->print_array($order_items);					
						$currency 			= $this->get_order_currency();
						$zero 				= $this->price(0, array('currency'=>$currency));
						$columns			= apply_filters("ic_commerce_variation_page_grid_columns",$columns, $show_variation);
						$order_items		= apply_filters("ic_commerce_variation_page_data_grid",$order_items, $columns, $zero, $show_variation);
						
						$edit_label 		= __("Edit");
						$not_label 			= __("Not Set",'icwoocommerce_textdomains');
						$dash_label 		= __("-",'icwoocommerce_textdomains');
						
						$price_columns		= apply_filters("ic_commerce_price_columns",array());
						
						?>
                		<?php if($type != 'all_row'):?>
                        	<div class="top_buttons"><?php $this->export_to_csv_button('top',$summary);?><div class="clearfix"></div></div>
                        	<?php else: {$this->back_print_botton('top',$summary);}?>
						<?php endif;?>
						<table style="width:99.8%" class="widefat widefat_normal_table" cellpadding="0" cellspacing="0">
							<thead>
								<tr class="first">
                                	<?php
										$header = "";
                                    	foreach($columns as $key => $value):
											$header .= "<th class=\"{$key}\">{$value}</th>\n";
										endforeach;
										if($type != 'all_row'){
											$header .= "<th class=\"edit\">{$edit_label}</th>\n";
										}
										echo $header;
									?>
								</tr>
                                
							</thead>
							<tbody>
								<?php		
								$currency = $this->get_order_currency();
								/*
								$currency = isset($_REQUEST['order_currency']) ? $_REQUEST['order_currency'] : get_woocommerce_currency();
								$currency = is_array($currency) ? implode('',$currency) : $currency;
								$currency = $currency != '-1' ? $currency :  get_woocommerce_currency();
								*/		
								foreach ( $order_items as $key => $order_item ) {
									$order_currency = isset($order_item->order_currency) ? $order_item->order_currency : $currency;
									$TotalAmount =  $TotalAmount + $order_item->amount;
									$TotalOrderCount++;
									if($key%2 == 1){$alternate = "alternate ";}else{$alternate = "";};
									?>
									<tr class="<?php echo $alternate."row_".$key;?>">
                                    	<?php
											$trbody = "";
											//$variation = $this->get_variation_column_separated($order_item->order_item_id,$variation_attributes,$all_attributes);
											foreach($columns as $key => $value):
												$v = "";
												switch($key){
													case "variation_id":
														$v = isset($order_item->$key) ? $this->get_stock($order_item->$key) : '';
														break;
													case "variation_stock":													
														$v = get_post_meta($order_item->variation_id,"_stock",true);
														$v = (strlen($v)>0) ? $v : (isset($order_item->stock) ? $this->get_stock($order_item->stock) : $not_label);
														break;
													case "product_stock":													
														$v = isset($order_item->stock) ? $this->get_stock($order_item->stock) : $not_label;
														break;
													case "product_sku":													
														$v = isset($order_item->sku) ? $this->get_stock($order_item->sku) : $not_label;
														break;
													case "variation_sku":													
														$v = get_post_meta($order_item->variation_id,"_sku",true);
														$v = (strlen($v)>0) ? $v : (isset($order_item->sku) ? $this->get_stock($order_item->sku) : $not_label);
														break;
													case "product_name":
														if($clickeble and $type != 'all_row'){
															if($show_variation == "variable"){
																$variation_id = isset($order_item->variation_id) ? $order_item->variation_id : 0;
																if($group_by == "variation_id"){																	
																	$v = " <a href=\"{$admin_url}{$order_item->product_id}&variation_id={$variation_id}\" target=\"_blank\">{$order_item->product_name}</a>";
																}elseif($group_by == "order_item_id"){
																	$order_item_id = isset($order_item->order_item_id) ? $order_item->order_item_id : 0;
																	$v = " <a href=\"{$admin_url}{$order_item->product_id}&variation_id={$variation_id}&order_item_id={$order_item_id}\" target=\"_blank\">{$order_item->product_name}</a>";
																}else{
																	$v = " <a href=\"{$admin_url}{$order_item->product_id}\" target=\"_blank\">{$order_item->product_name}</a>";
																}
															}else{
																$v = " <a href=\"{$admin_url}{$order_item->product_id}\" target=\"_blank\">{$order_item->product_name}</a>";
															}
														}else
															$v = $order_item->product_name;
														break;
														
													case "product_id":														
														$v = $order_item->product_id;
														break;
													case "quantity":
														$v = $order_item->quantity;
														
														break;
													case "amount":
														$v = $this->price($order_item->amount, array('currency' => $order_currency));														
														break;
													/*case "product_variation":
														$v = $this->get_variation_comma_separated($order_item->order_item_id,$variation_attributes,$all_attributes);
														
														break;*/
													/*
													case "color":
													case "size":
													case "cart":
													case "giftcart":
														$v = isset($variation[$key]) ? $variation[$key] : (isset($rvalue->$key) ? $rvalue->$key : $dash_label);
														break;
													*/
													default:
													
														if(isset($price_columns[$key])){
															$v = isset($order_item->$key) ? $order_item->$key : $dash_label;
															$v = $this->price($v, array('currency' => $order_currency));																														
														}else{
															$v = isset($order_item->$key) ? $order_item->$key : $dash_label;
														}
														//$v = $key;
														/*														
															if(in_array($key, $attrs)){
																$v = isset($variation[$key]) ? $variation[$key] : $dash_label;
															}else{
																$v = isset($rvalue->$key) ? $rvalue->$key : $dash_label;
															}
														*/														
														break;
												}
												$trbody .= "<td class=\"{$key}\">{$v}</td>\n";
											endforeach;
											if($type != 'all_row'){
												$trbody .= "<td class=\"td_right\"><a href=\"{$product_url}{$order_item->product_id}\" target=\"_blank\">{$edit_label}</a></td>\n";
											}
											echo $trbody;
										?>
                                   </tr>
									<?php 
								} ?>
							<tbody>           
						</table>
						 <?php if($type != 'all_row') $this->total_count($TotalOrderCount, $TotalAmount, $summary, $TotalShipping);  else $this->back_print_botton('bottom',$summary);
						 $detail_view 		= $this->get_request('detail_view','no');
						 $zero				= $this->price(0,array('currency' => $currency));
						 echo $this->result_grid($detail_view,$summary,$zero,'',$price_columns);
						 ?>
				<?php else:?>        
						<div class="order_not_found"><?php _e("No order found",'icwoocommerce_textdomains');?></div>
				<?php endif;?>
			<?php
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
						$variations["wcv_".$var] = ucfirst($var2);
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
							$variations["wcv_".$var] = ucfirst($var2);
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
		
		function get_variation_column_separated($order_item_id = 0, $variation_attributes = NULL, $all_attributes = NULL){
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
				
				if($variation_attributes != NULL and $variation_attributes != "-1" and strlen($variation_attributes)>1)
					$sql .= " AND postmeta_variation.meta_key IN ('{$variation_attributes}')";
				else				
					$sql .= " AND postmeta_variation.meta_key like 'attribute_%'";
					
				
				if($variation_attributes != NULL and $variation_attributes != "-1" and strlen($variation_attributes)>1)
					$sql .= " AND postmeta_variation.meta_key IN ('{$variation_attributes}')";
				else				
					$sql .= " AND postmeta_variation.meta_key like 'attribute_%'";
				
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
		
		function get_variation_comma_separated($order_item_id = 0, $variation_attributes = NULL, $all_attributes = NULL){
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
				AND woocommerce_order_itemmeta.meta_key = '_variation_id'";
				
				if($variation_attributes != NULL and $variation_attributes != "-1" and strlen($variation_attributes)>1)
					$sql .= " AND postmeta_variation.meta_key IN ('{$variation_attributes}')";
				else				
					$sql .= " AND postmeta_variation.meta_key like 'attribute_%'";
				
				$sql .= " ORDER BY postmeta_variation.meta_value ASC";
				
				//echo $sql;
				$order_items = $wpdb->get_results($sql);
				//$this->print_array($order_items);
				$variation = array();
				if($order_items)
				foreach($order_items as $key => $value){
					if(strlen($value->variation)>0){
						
						$var = $value->attribute;
						$var = str_replace("attribute_pa_","",$var);
						$var = str_replace("attribute_","",$var);
						
						$variation[$var] = $value->variation;
						$order_items[$key]->attribute = $var;
					}
				}
				
				if($all_attributes){
					$nev_variation = array();
					foreach($all_attributes as $key => $value){
						if(isset($variation[$key])){
							$nev_variation[$key] = $variation[$key];
						}
					}
					$variation = $nev_variation;
				}
				
				$v = ucwords (implode(", ", $variation));
				$v = str_replace("-"," ",$v);
				return $v;
		}
		
		function get_stock($stock_count){
			if(strlen($stock_count)<=0){
				return  __("Not Set",'icwoocommerce_textdomains');
			}else{
				return $stock_count;
			}
		}
		
		function get_count_total($data,$amt = 'amount'){
			$total = 0;
			$return = array();
			$detail_view 		= $this->get_request('detail_view','no');
			$total_columns 		= $this->result_columns($detail_view);
			$order_status		= array();
			$orders				= array();
			if(count($total_columns) > 0){
				foreach($data as $key => $value){
					$total = $total + $value->$amt;
					
					foreach($total_columns as $ckey => $label):
						$return[$ckey] 	= isset($value->$ckey)? (isset($return[$ckey])	? ($return[$ckey] + $value->$ckey): $value->$ckey) : 0;
					endforeach;
				}
			}else{
				foreach($data as $key => $value){
					$total = $total + $value->$amt;
					if(!isset($orders[$value->order_id]) )$orders[$value->order_id] = $value->order_id;
				}
			}
			
			if(isset($value->order_id)){
				foreach($data as $key => $value){					
					if(!isset($orders[$value->order_id]) )$orders[$value->order_id] = $value->order_id;
				}
			}
			
			$return['total_row_amount'] = $total;
			$return['total_row_count'] = count($data);
			$return['total_order_count'] = count($orders);
			//$this->print_array($return);
			return $return;
		}
		
		function total_count($TotalOrderCount = 0, $TotalAmount=0, $summary = array(), $TotalShipping=0){
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
			$currency 			= $this->get_order_currency();			
			?>				
				<table style="width:100%">
					<tr>
						<td valign="middle" class="grid_bottom_total">
                        	<?php
								if($report_name == "recent_order"){
									$formated_total_amount		= $this->price($TotalAmount, array('currency' => $currency));
									$formated_total_shippint	= $this->price($TotalShipping, array('currency'=> $currency));
									$output = "<table><tr>";
									$output .= "<tr><td>Result:		</td><td>	<strong>{$TotalOrderCount}/{$total_pages}</strong></td></tr>";
									$output .= "<tr><td>Amount:		</td><td> 	<strong>{$formated_total_amount}</strong></td></tr>";
									$output .= "<tr><td>Shipping:	</td><td> 	<strong>{$formated_total_shippint}</strong></td></tr>";
									$output .= "</tr></table>";
								}else{
									$formated_total_amount		= $this->price($TotalAmount, array('currency'=>$currency));
									
									$output = "Result: 		<strong>{$TotalOrderCount}/{$total_pages}</strong>, ";
									$output .= "Amount: 	<strong>{$formated_total_amount}</strong><br />";
									
								}
								
								echo $output;
								
							?>
						</td>
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
			
			$request_			=	$request;
			
			
			unset($request['action']);
			unset($request['p']);
			
		
			
			$logo_image 			= $this->get_setting('logo_image',$this->constants['plugin_options'], '');
			$report_title 			= $this->get_setting('report_title',$this->constants['plugin_options'], '');
			$company_name 			= $this->get_setting('company_name',$this->constants['plugin_options'], '');
			$output_fields 			=  "";
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
                <?php if($position == "bottom"): ?>
                <form id="search_order_pagination" class="search_order_pagination" action="<?php echo $mngpg;?>" method="post">
                    <?php echo $this->create_hidden_fields($request_);?>
                </form>
                
                <?php //echo $this->create_hidden_fields($request,'text');?>
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
				$currency 			= $this->get_order_currency();
				
			?>
            	<div class="print_summary_bottom">
                	<?php _e("Total Result: ",'icwoocommerce_textdomains');?><strong><?php echo $total_row_count ?></strong><?php _e(", Amount: ",'icwoocommerce_textdomains');?><strong><?php echo $this->price($total_row_amount, array('currency'=>$currency)); ?></strong><br />
                </div>
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
		
		
		function ic_commerce_custom_report_page_export_csv($export_file_format='csv'){
			global $wpdb, $table_prefix;
			
			$report_name	= $this->get_request('report_name',"no");
			
			if($report_name == "product_page"){
				$order_items= $this->ic_commerce_custom_all_product_query('all_row');
				$summary 	= $this->ic_commerce_custom_all_product_query('total_row');
				$columns	= $this->get_product_variation_columns();
				
				//$this->print_array($_REQUEST);die;
				//$this->print_array($_REQUEST);die;
				/*
				$attr		= $this->get_attributes('selected');
				
				
				$attrs		= array();
				foreach($attr as $key => $value):
					$attrs[] = $key;
				endforeach;
				
				$all_attributes			= $this->get_attributes('-1');
				$variation_attributes	= $this->get_request('variation_attributes');
				*/
				
				$order_items = $this->get_grid_items_variation($columns,$order_items);
				$order_items= apply_filters("ic_commerce_variation_page_export_csv_excel_data",$order_items,$columns, $export_file_format);
			}
			
			
			$price_columns		= apply_filters("ic_commerce_price_columns",array());
			
			$export_rows 	= array();
			$country      	= $this->get_wc_countries();//Added 20150225			
			$i 				= 0;
			
			$num_decimals   = get_option( 'woocommerce_price_num_decimals'	,	0		);
			$decimal_sep    = get_option( 'woocommerce_price_decimal_sep'	,	'.'		);
			$thousand_sep   = get_option( 'woocommerce_price_thousand_sep'	,	','		);			
			$zero			= number_format(0, $num_decimals,$decimal_sep,$thousand_sep);
			
			foreach ( $order_items as $rkey => $rvalue ):
				$order_item = $rvalue;
				//if($report_name == "product_page")	$variation = $this->get_variation_column_separated($rvalue->order_item_id,$variation_attributes,$all_attributes);
				foreach($columns as $key => $value):					
					switch ($key) {
							case 'BillingCountry':
							case 'billing_country':
							case 'shipping_country':
								$export_rows[$i][$key] =  isset($country->countries[$rvalue->$key]) ? $country->countries[$rvalue->$key]: $rvalue->$key;
								break;
							/*
							case 'product_variation':
								$export_rows[$i][$key] =  $this->get_variation_comma_separated($rvalue->order_item_id,$variation_attributes,$all_attributes);
								break;
							*/
							/*case "sku":
							case "product_sku":
							case "variation_sku":
								$export_rows[$i][$key] =  $this->get_stock($rvalue->$key);
								break;
							case "stock":
								$export_rows[$i][$key] =  $this->get_stock($rvalue->$key);
								break;*/
							case "variation_stock":													
								$v = get_post_meta($order_item->variation_id,"_stock",true);
								$export_rows[$i][$key] = (strlen($v)>0) ? $v : (isset($order_item->stock) ? $this->get_stock($order_item->stock) : 'Not Set');
								break;
							case "product_stock":													
								$export_rows[$i][$key] = isset($order_item->stock) ? $this->get_stock($order_item->stock) : 'Not Set';
								break;
							case "product_sku":													
								$export_rows[$i][$key] = isset($order_item->sku) ? $this->get_stock($order_item->sku) : 'Not Set';
								break;
							case "variation_sku":													
								$v = get_post_meta($order_item->variation_id,"_sku",true);
								$export_rows[$i][$key] = (strlen($v)>0) ? $v : (isset($order_item->sku) ? $this->get_stock($order_item->sku) : 'Not Set');
								break;
							/*
							case "color":
							case "size":
							case "cart":
							case "giftcart":
								$export_rows[$i][$key] = isset($variation[$key]) ? $variation[$key] : (isset($rvalue->$key) ? $rvalue->$key : '-');
								break;
							*/
							case "amount":
								$td_value 	=  isset($rvalue->$key) ? $rvalue->$key : 0;
								$td_value 	=  strlen($td_value)>0 ? $td_value : 0;
								$td_value	=  $td_value == 0 ? $zero : number_format($td_value, $num_decimals,$decimal_sep,$thousand_sep);
								$export_rows[$i][$key] = $td_value;
								break;
							default:
								
								if(isset($price_columns[$key])){
									$td_value 	=  isset($rvalue->$key) ? $rvalue->$key : 0;
									$td_value 	=  strlen($td_value)>0 ? $td_value : 0;
									$td_value	=  $td_value == 0 ? $zero : number_format($td_value, $num_decimals,$decimal_sep,$thousand_sep);
									$export_rows[$i][$key] = $td_value;
								}else{
									$export_rows[$i][$key] = isset($rvalue->$key) ? $rvalue->$key : "";
								}
								/*
								if($report_name == "product_page"){
									if(in_array($key, $attrs)){										
										$export_rows[$i][$key] = isset($variation[$key]) ? $variation[$key] : '-';
									}else{
										$export_rows[$i][$key] = isset($rvalue->$key) ? $rvalue->$key : "";
									}
								}else{
									$export_rows[$i][$key] = isset($rvalue->$key) ? $rvalue->$key : "";
								}
								*/
								
								break;
						}
				endforeach;
				$i++;
			endforeach;
			
			
			$total_label_flag = false;
			foreach($columns as $key => $value):					
				switch ($key) {
						case "quantity":
							$export_rows[$i][$key] = isset($summary[$key]) ? $summary[$key] : '';
							break;							
						case "amount":
							$td_value 	= isset($summary[$key]) ? $summary[$key] : '';
							$td_value 	= strlen($td_value)>0 ? $td_value : 0;
							$td_value	= $td_value == 0 ? $zero : number_format($td_value, $num_decimals,$decimal_sep,$thousand_sep);
							$export_rows[$i][$key] = $td_value;
							break;
						case "product_id":
							$export_rows[$i][$key] = "Total";
							/*
							if($total_label_flag)
								$export_rows[$i][$key] = "";
							else{
								$export_rows[$i][$key] = "Total";
								$total_label_flag = true;
							}
							*/
							break;						
						case 'variation_sku':
						case 'product_name':
						case 'product_variation':
						case 'variation_stock':
							$export_rows[$i][$key] = '';
							break;
						default:
							if(isset($price_columns[$key])){
								$td_value 	= isset($summary[$key]) ? $summary[$key] : '';
								$td_value 	= strlen($td_value)>0 ? $td_value : 0;
								$td_value	= $td_value == 0 ? $zero : number_format($td_value, $num_decimals,$decimal_sep,$thousand_sep);
								$export_rows[$i][$key] = $td_value;
							}else{
								$export_rows[$i][$key] = '';
							}
							
							break;
					}
			endforeach;
			$i++;
			
			$export_file_name 		= $this->get_request('export_file_name',"no");
			$report_name 			= $this->get_request('report_name','product_page');
			$report_name 			= str_replace("_page","_list",$report_name);
			
			/*$today 					= date_i18n("Y-m-d-H-i-s");				
			$FileName 				= $export_file_name."_".$report_name."-".$today.".".$export_file_format;	
			$out = $this->ExportToCsv($FileName,$export_rows,$columns,$export_file_format);*/
			
			/*New Added 20160130*/
			$today_date 		= date_i18n("Y-m-d-H-i-s");				
			$export_filename 	= $export_file_name."-".$report_name."-".$today_date.".".$export_file_format;
			$export_filename 	= apply_filters('ic_commerce_export_csv_excel_format_file_name',$export_filename,$report_name,$today_date,$export_file_name,$export_file_format);
			do_action("ic_commerce_export_csv_excel_format",$export_filename,$export_rows,$columns,$export_file_format,$report_name);
			$out = $this->ExportToCsv($export_filename,$export_rows,$columns,$export_file_format,$report_name);
			
			//echo $out;
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
				header("Content-Type: application/vnd.ms-excel");
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
				$csv_separator = "\t";//For Some Server
				//$csv_separator = "\t";
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
			global $wpdb, $table_prefix;
			
			$report_name	= $this->get_request('report_name',"no");
			
			if($report_name == "product_page"){
				$order_items= $this->ic_commerce_custom_all_product_query('all_row');
				$summary 	= $this->ic_commerce_custom_all_product_query('total_row');
				$columns	= $this->get_product_variation_columns();
				//$attr		= $this->get_attributes('selected');
				/*
				$attrs		= array();
				foreach($attr as $key => $value):
					$attrs[] = $key;
				endforeach;
				$all_attributes			= $this->get_attributes('-1');
				$variation_attributes	= $this->get_request('variation_attributes');
				
				*/
				$order_items = $this->get_grid_items_variation($columns,$order_items);				
				$order_items= apply_filters("ic_commerce_variation_page_export_pdf_data",$order_items,$columns, $export_file_format);
			}
			
			$export_rows = array();
			$country      	= $this->get_wc_countries();//Added 20150225
			
			$currency = $this->get_order_currency();
			
			$price_columns		= apply_filters("ic_commerce_price_columns",array());
			
			$i = 0;
			foreach ( $order_items as $rkey => $rvalue ):
				$order_item 	= $rvalue;
				$order_currency = isset($order_item->order_currency) ? $order_item->order_currency : $currency;
				//if($report_name == "product_page")	$variation = $this->get_variation_column_separated($rvalue->order_item_id,$variation_attributes,$all_attributes);
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
								$export_rows[$i][$key] = $this->price($rvalue->$key, array('currency' => $order_currency));
								break;
							case 'BillingCountry':
							case 'billing_country':
							case 'shipping_country':
								$export_rows[$i][$key] =  isset($country->countries[$rvalue->$key]) ? $country->countries[$rvalue->$key]: $rvalue->$key;
								break;
							/*
							case 'product_variation':
								$export_rows[$i][$key] =  $this->get_variation_comma_separated($rvalue->order_item_id,$variation_attributes,$all_attributes);
								break;
							*/
							/*
							case "sku":
							case "product_sku":
							case "variation_sku":
								$export_rows[$i][$key] =  $this->get_stock($rvalue->$key);
								break;
							case "stock":
								$export_rows[$i][$key] =  $this->get_stock($rvalue->$key);
								break;
							*/
							case "variation_stock":													
								$v = get_post_meta($order_item->variation_id,"_stock",true);
								$export_rows[$i][$key] = (strlen($v)>0) ? $v : (isset($order_item->stock) ? $this->get_stock($order_item->stock) : 'Not Set');
								break;
							case "product_stock":													
								$export_rows[$i][$key] = isset($order_item->stock) ? $this->get_stock($order_item->stock) : 'Not Set';
								break;
							case "product_sku":													
								$export_rows[$i][$key] = isset($order_item->sku) ? $this->get_stock($order_item->sku) : 'Not Set';
								break;
							case "variation_sku":													
								$v = get_post_meta($order_item->variation_id,"_sku",true);
								$export_rows[$i][$key] = (strlen($v)>0) ? $v : (isset($order_item->sku) ? $this->get_stock($order_item->sku) : 'Not Set');
								break;
							/*
							case "color":
							case "size":
							case "cart":
							case "giftcart":
								$export_rows[$i][$key] = isset($variation[$key]) ? $variation[$key] : (isset($rvalue->$key) ? $rvalue->$key : '-');
								break;
							*/
							default:
								if(isset($price_columns[$key])){
									$export_rows[$i][$key] = $this->price($rvalue->$key, array('currency' => $order_currency));
								}else{
									$export_rows[$i][$key] = isset($rvalue->$key) ? $rvalue->$key : "";
								}
								/*
								if($report_name == "product_page"){
									if(in_array($key, $attrs)){										
										$export_rows[$i][$key] = isset($variation[$key]) ? $variation[$key] : '-';
									}else{
										$export_rows[$i][$key] = isset($rvalue->$key) ? $rvalue->$key : "";
									}
								}else{
									$export_rows[$i][$key] = isset($rvalue->$key) ? $rvalue->$key : "";
								}
								*/
								
								break;
						}
					endforeach;				
				$i++;
			endforeach;
			
			//die;
			
			$output = $this->GetPdfDataGrid($export_rows,$columns,$summary);			
			$this->export_to_pdf($export_rows,$output);
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
		
		function page_title($title){
			$title = str_replace("_"," ",$title);
			$title = str_replace("-"," ",$title);
			//$title = Ucwords($title);
			return $title;
		}
		
	
				
		function get_all_request(){
			global $request, $back_day;
			if(!$this->request){
				
				do_action("ic_commerce_variation_page_before_default_request");
				
				$request 			= array();
				$start				= 0;
				
				$limit 				= $this->get_request('limit',3,true);
				$p 					= $this->get_request('p',1,true);
				$show_variation 	= $this->get_request('show_variation',0,true);
				$page				= $this->get_request('page',NULL);
				$page				= $this->get_request('page',NULL);
				
				$order_status		= $this->get_request('order_status',"-1",true);
				$hide_order_status	= $this->get_request('hide_order_status',"-1",true);
				$report_name		= $this->get_request('report_name',"product_page",true);
				
				$category_id		= $this->get_request('category_id',"-1",true);
				$product_id			= $this->get_request('product_id',"-1",true);
				
				$paid_customer		= $this->get_request('paid_customer',"-1",true);
				$billing_postcode	= $this->get_request('billing_postcode',"-1",true);
				
				$product_sku 		= $this->get_request('product_sku','-1',true);	
				$variation_sku 		= $this->get_request('variation_sku','-1',true);	
				
				$sort_by 			= $this->get_request('sort_by','product_name',true);
				$order_by 			= $this->get_request('order_by','DESC',true);
				
				$_REQUEST['variation_attributes']= '-1';
				$_REQUEST['variations_formated']= '-1';
				
				if($report_name == "product_page"){
					
					if($billing_postcode and $billing_postcode != '-1'){
						$billing_postcode = str_replace("|",",",$billing_postcode);
						$billing_postcode = str_replace(";",",",$billing_postcode);
						$billing_postcode = str_replace("#",",",$billing_postcode);
						$_REQUEST['billing_postcode'] = $billing_postcode;
					}
					
					$category_id		= $this->get_request('category_id','-1',true);
					$product_id			= $this->get_request('product_id','-1',true);
					$order_status_id	= $this->get_request('order_status_id','-1',true);
					$variations			= $this->get_request('variations','-1',true);
					
					$item_att = array();
					$itemmetakey =  '-1';
					if($variations != '-1' and strlen($variations) > 0){
							$variations = $_REQUEST['variations'];
							$variations = explode(",",$variations);
							//$this->print_array($variations);
							$var = array();
							foreach($variations as $key => $value):
								$var[] .=  "attribute_pa_".$value;
								$var[] .=  "attribute_".$value;
								//$item_att[] .=  "pa_".$value;
								$item_att[] .=  $value;
							endforeach;
							$variations =  implode("', '",$var);
							$itemmetakey =  implode("', '",$item_att);
					}
					
					$_REQUEST['variation_attributes']= $variations;
					$_REQUEST['variation_itemmetakey']= $itemmetakey;
					
					
					$variations_value		= $this->get_request('variations_value',"-1",true);
					$variations_formated 	= '-1';
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
				
				$variation_column			= $this->get_request('variation_column','-1',true);
				
				
				if($p > 1){	$start = ($p - 1) * $limit;}
				
				$_REQUEST['start']= $start;
				
				$this->common_request_form();
			
				
				if(isset($_REQUEST)){
					$REQUEST = $_REQUEST;
					$REQUEST = apply_filters("ic_commerce_before_request_creation", $REQUEST);
					
					if(isset($_REQUEST['new_variations_value'])){
						unset($REQUEST['new_variations_value']);
					}
					
					foreach($REQUEST as $key => $value ):
						$request[$key] =  $this->get_request($key,NULL);
					endforeach;
					if(isset($_REQUEST['new_variations_value'])){
						foreach($_REQUEST['new_variations_value'] as $key => $value):
								$request['new_variations_value'][$key] = is_array($value) ? implode(",",$value) : $value;							
						endforeach;
					}
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
		
		function get_product_variation_columns(){
			$attributes 		= array();
			$grid_column 		= $this->get_grid_columns();
			$show_variation		= $this->get_request('show_variation','all');
			$variation_column	= $this->get_request('variation_column','0');
			if($show_variation == 'variable'){
				$attributes	= $this->get_product_variation_attributes('no');
			}			
			return $grid_column->get_product_variation_columns($show_variation,$attributes, $variation_column);
		}
		
		function result_columns($detail_view = ''){
			$grid_column 	= $this->get_grid_columns();
			$detail_view 	= $this->get_request('show_variation','all');			
			return $grid_column->result_columns_variation_page($detail_view);
		}
		
	}
}
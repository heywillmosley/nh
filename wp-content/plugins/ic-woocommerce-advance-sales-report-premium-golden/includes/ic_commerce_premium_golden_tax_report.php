<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
if ( ! class_exists( 'IC_Commerce_Premium_Golden_Tax_report' ) ) {
	require_once('ic_commerce_premium_golden_fuctions.php');
	class IC_Commerce_Premium_Golden_Tax_report extends IC_Commerce_Premium_Golden_Fuctions{
		
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
			
		}
		
		function init(){
				global $last_days_orders, $wpdb;
				
				//echo get_option('woocommerce_db_version');
				//echo get_option('_wc_needs_pages',0);
				
				//$current_version = get_option( 'woocommerce_version', null );
				//$current_db_version = get_option( 'woocommerce_db_version', null );
				
				if(!isset($_REQUEST['page'])){return false;}
				
				if ( !current_user_can( $this->constants['plugin_role'] ) )  {
					wp_die( __( 'You do not have sufficient permissions to access this page.' ,'icwoocommerce_textdomains' ) );
				}
				
				do_action('ic_commerce_current_user_can',$_REQUEST['page'],$this->constants);
				
				//echo urlencode($userinput);
				//$invoice_id = 198;
				//$order = new WC_Order ($invoice_id);
				//New Change ID 20140918
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
				
				$optionsid				= "per_row_tax_report_page";
				$per_page 				= $this->get_number_only($optionsid,$this->per_page_default);
				
				$this->constants['start_date'] = date_i18n('Y-m-d');

				$start_date 			= apply_filters('ic_commerce_tax_report_page_start_date',	$this->constants['start_date']);
				$end_date 				= apply_filters('ic_commerce_tax_report_page_end_date',		$this->constants['end_date']);
				$order_status			= apply_filters('ic_commerce_tax_report_page_selected_order_status', $order_status);
				$onload_search			= apply_filters('ic_commerce_tax_report_page_onload_search', "yes");
				$onload_search			= apply_filters('ic_commerce_onload_search', $onload_search, '');
				
				$sales_order			= $this->get_request('sales_order',false);	
				$end_date				= $this->get_request('end_date',$end_date,true);
				$start_date				= $this->get_request('start_date',$start_date,true);
				$order_status_id		= $this->get_request('order_status_id',$order_status_id,true);//New Change ID 20140918
				$order_status			= $this->get_request('order_status',$order_status,true);//New Change ID 20140918
				$publish_order			= $this->get_request('publish_order',$publish_order,true);//New Change ID 20140918
				$hide_order_status		= $this->get_request('hide_order_status',$hide_order_status,true);//New Change ID 20140918

				$product_id				= $this->get_request('product_id','-1',true);
				$category_id			= $this->get_request('category_id','-1',true);
				$adjacents				= $this->get_request('adjacents',3,true);
				$page					= $this->get_request('page',NULL);				
				$order_id				= $this->get_request('order_id',NULL,true);
				$txtFirstName			= $this->get_request('txtFirstName',NULL,true);
				$txtEmail				= $this->get_request('txtEmail',NULL,true);				
				$payment_method			= $this->get_request('payment_method',NULL,true);
				$order_item_name		= $this->get_request('order_item_name',NULL,true);//for coupon
				$coupan_code			= $this->get_request('coupan_code',NULL,true);//for coupon				
				$sort_by 				= $this->get_request('sort_by','order_id',true);
				$order_by 				= $this->get_request('order_by','DESC',true);
				$paid_customer 			= $this->get_request('paid_customer','-1',true);
				$coupon_used			= $this->get_request('coupon_used','no',true);
				$month_key				= $this->get_request('month_key',false);
				$order_meta_key			= $this->get_request('order_meta_key','-1',true);
				$count_generated		= $this->get_request('count_generated',0,true);
				$page_title				= $this->get_request('page_title','',true);
				
				$country_code			= '-1';
				$state_code				= '-1';
				$country_state_code		= $this->get_request('country_state_code',NULL,true);
				
				if($country_state_code and strlen($country_state_code)>0){
					$country_state_codes = explode("-",$country_state_code);
					$country_code		 = isset($country_state_codes[0]) ? $country_state_codes[0] : NULL;
					$state_code		 	 = isset($country_state_codes[1]) ? $country_state_codes[1] : NULL;
						
				}
								
				$country_code			= $this->get_request('country_code',$country_code,true);
				$state_code				= $this->get_request('state_code',$state_code,true);
				
				$this->constants['tax_based_on'] = get_option('woocommerce_tax_based_on','shipping');
				if($this->constants['tax_based_on'] == "base"){
					$this->constants['tax_based_on'] = "shipping";
				}
				
				$this->get_country_state_list();
				
				if($order_status_id == "all") 	$order_status_id	= $_REQUEST['order_status_id'] 	= "-1";	
				?>					
                    <div id="navigation" class="hide_for_print">
                        <div class="collapsible" id="section1"><?php _e('Custom Search','icwoocommerce_textdomains'); ?><span></span></div>
                        <div class="container">
                            <div class="content">
                                <div class="search_report_form">
                                    <div class="form_process"></div>
                                    <form action="" name="Report" id="search_order_report" method="post">
                                        <div class="form-table">
                                            <div class="form-group">
                                                <div class="FormRow FirstRow">
                                                    <div class="label-text"><label for="start_date"><?php _e('From Date:','icwoocommerce_textdomains'); ?></label></div>
                                                    <div class="input-text"><input type="text" value="<?php echo $start_date;?>" id="start_date" name="start_date" readonly maxlength="10" /></div>
                                                </div>
                                                <div class="FormRow">
                                                    <div class="label-text"><label for="end_date"><?php _e('To Date:','icwoocommerce_textdomains'); ?></label></div>
                                                    <div class="input-text"><input type="text" value="<?php echo $end_date;?>" id="end_date" name="end_date" readonly maxlength="10" /></div>
                                                </div>
                                            </div>
											
											<div class="form-group">
                                             	<div class="FormRow FirstRow">
                                                    <div class="label-text"><label for="country_code"><?php _e('Country:','icwoocommerce_textdomains'); ?></label></div>
                                                    <div class="input-text">
                                                        <?php 
															if($this->constants['tax_based_on'] == "billing"){
																$country_data = $this->get_paying_state('billing_country');
															}else{
																$country_data = $this->get_paying_state('shipping_country');
															}
                                                            $this->create_dropdown($country_data,"country_code[]","country_code2","Select All","country_code2",$country_code, 'object', true, 5);
                                                        ?>                                                        
                                                    </div>                                                    
                                                </div>
                                                <div class="FormRow ">
                                                    <div class="label-text"><label for="state_code"><?php _e('State:','icwoocommerce_textdomains'); ?></label></div>
                                                    <div class="input-text">
                                                    	<?php 
															/*echo '<select name="state_code[]" id="state_code2" class="state_code2" multiple="multiple" size="1"  data-size="1">';
															if($state_code != "-1"){
																echo "<option value=\"{$state_code}\">{$state_code}</option>";
															}
															echo '</select>';*/
                                                            $state_code = '-1';
															if($this->constants['tax_based_on'] == "billing"){
																$state_codes = $this->get_paying_state('billing_state','billing_country');	
															}else{
																$state_codes = $this->get_paying_state('shipping_state','shipping_country');
															}
                                                            
                                                            $this->create_dropdown($state_codes,"state_code[]","state_code2","Select All","state_code2",$state_code, 'object', true, 5);
                                                        ?>                                                        
                                                    </div>                                                    
                                                </div>
                                             </div>
                                            
                                            <div class="form-group">
                                                <div class="FormRow FirstRow">
                                                    <div class="label-text"><label for="tax_group_by"><?php _e('Tax Group By:','icwoocommerce_textdomains'); ?></label></div>
                                                    <div class="input-text">
                                                    	<?php
															$tax_group_by = get_option("default_detail_tax_report",'tax_group_by_state');
															$tax_group_by = $this->get_request('tax_group_by',$tax_group_by);
															$data = array(
																"tax_group_by_city"				=> __("City",				'icwoocommerce_textdomains'),
																"tax_group_by_state"			=> __("State",				'icwoocommerce_textdomains'),
																"tax_group_by_country"			=> __("Country",			'icwoocommerce_textdomains'),
																"tax_group_by_zip"				=> __("Zip Code",			'icwoocommerce_textdomains'),
																"tax_group_by_tax_name"			=> __("Tax Name",			'icwoocommerce_textdomains'),
																"tax_group_by_tax_summary"		=> __("Tax Summary",		'icwoocommerce_textdomains'),
																"tax_group_by_city_summary"		=> __("City Summary",		'icwoocommerce_textdomains'),
																"tax_group_by_state_summary"	=> __("State Summary",		'icwoocommerce_textdomains'),
																"tax_group_by_country_summary"	=> __("Country Summary",	'icwoocommerce_textdomains')
																);
                                                            $this->create_dropdown($data,"tax_group_by","tax_group_by","","tax_group_by",$tax_group_by, 'array', false, 5);
														?>                                                    
                                                    </div>
                                                </div>   
												
												<div class="FormRow">
                                                    <div class="label-text"><label for="order_status_id"><?php _e('Status:','icwoocommerce_textdomains'); ?></label></div>
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
                                            <div class="form-group">
                                                <div class="FormRow" style="width:100%">
                                                    <input type="hidden" name="hide_order_status" 		id="hide_order_status" 	value="<?php echo $hide_order_status;?>" /><!--//New Change ID 20140918-->
                                                    <input type="hidden" name="publish_order" 			id="publish_order" 		value="<?php echo $publish_order;?>" />
                                                    <input type="hidden" name="action" 					id="action" 			value="<?php echo $this->get_request('action',$this->constants['plugin_key'].'_wp_ajax_action',true);?>" />
                                                    <input type="hidden" name="limit"  					id="limit" 				value="<?php echo $this->get_request('limit',$per_page,true);?>" />
                                                    <input type="hidden" name="p"  						id="p" 					value="<?php echo $this->get_request('p',1,true);?>" />
                                                    <input type="hidden" name="admin_page"  			id="admin_page" 		value="<?php echo $this->get_request('admin_page',$page,true);?>" />
                                                    <input type="hidden" name="adjacents"  				id="adjacents" 			value="<?php echo $this->get_request('adjacents','3',true);?>" />
                                                    <input type="hidden" name="purchased_product_id"  	id="purchased_product_id" value="-1" />
                                                   	<input type="hidden" name="do_action_type" 			id="do_action_type" 	value="<?php echo $this->get_request('do_action_type','tax_report_page',true);?>" />
                                                    <input type="hidden" name="page_title"  			id="page_title" 		value="<?php echo $page_title;?>" />
                                                    <input type="hidden" name="total_pages"  			id="total_pages" 		value="<?php echo $this->get_request('total_pages',0,true);?>" />
                                                    <input type="hidden" name="publish_order" 			id="publish_order" 		value="<?php echo $publish_order;?>" />
                                                    <input type="hidden" name="tax_based_on"  			id="tax_based_on" 		value="<?php echo $this->get_request('tax_based_on',$this->constants['tax_based_on'],true);?>" />
                                                    <input type="hidden" name="date_format" 			id="date_format" 		value="<?php echo $this->get_request('date_format',get_option('date_format'),true);?>" />
                                                    <input type="hidden" name="onload_search" 			id="onload_search" 		value="<?php echo $this->get_request('onload_search',$onload_search,true);?>" />
                                                    <span class="submit_buttons">
                                                    	<input name="ResetForm" id="ResetForm" class="onformprocess" value="<?php _e('Reset','icwoocommerce_textdomains'); ?>" type="reset"> 
                                                    	<input name="SearchOrder" id="SearchOrder" class="onformprocess searchbtn btn_margin" value="<?php _e('Search','icwoocommerce_textdomains'); ?>" type="submit"> &nbsp; &nbsp; &nbsp; <span class="ajax_progress"></span>
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
                    <div class="table table_shop_content search_report_content hide_for_print">
                    	<?php if($onload_search == "no") {echo apply_filters('ic_commerce_onload_search_text', '');}?>
                    </div>
                    <div id="search_for_print_block" class="search_for_print_block"></div>      
					
					<?php
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
                                    <tr>
                                        <td colspan="2"><input type="button" name="<?php echo $admin_page ;?>_export_print" class="onformprocess button_popup_close search_for_print" value="<?php _e("Print",'icwoocommerce_textdomains');?>" data-form="popup"  data-do_action_type="tax_report_page_for_print" /></td>
                                    </tr>                                
                                </table>
                                <input type="hidden" name="display_center" value="1" />
                            </form>
                            <div class="clear"></div>
                            </div>
                        </div>
					<div class="popup_mask"></div>
                    
                   <script type="text/javascript">
                   		jQuery(document).ready(function() {
                            
							jQuery("#tax_group_by").change(function(){
								enable_disable_state_fields();
							});
							
							enable_disable_state_fields();
                        });
						
						function enable_disable_state_fields(){
							var tax_group_by = jQuery("#tax_group_by").val();
							if(
								tax_group_by == "tax_group_by_state" 
							|| 	tax_group_by == "tax_group_by_state_summary"
							|| 	tax_group_by == "tax_group_by_tax_name"
							|| 	tax_group_by == "tax_group_by_tax_summary"){
								
								jQuery("#state_code2").removeAttr('disabled');
							}else{
								jQuery("#state_code2").attr('disabled', 'disabled');
							}
						}
						
						
                   </script>
                   <style type="text/css">
                   		/*td.total_tax{ background-color:#CCC}
						td.total_tax_refund{background-color:#999}
						td.net_total_tax{background-color:#CCC}*/
                   </style>
				<?php			
		}
		
		
		
		function ic_commerce_ajax_request($type = 'limit_row'){
			
			if (!empty( $_POST['action'] ) ) {
				$this->get_grid($type);
			}else{
				echo "Some thing going wrong, contact to developer";
			}
			die();
		}
		
		function get_column($c = 1){
			
			//refund_order_total
			
			
			if($c == 'tax_group_by_city'){
				$columns = array(
					"billing_country"			=>__("Tax Country",			'icwoocommerce_textdomains')
					,"billing_state"			=>__("Tax State",			'icwoocommerce_textdomains')/*New 2016-11-21*/
					,"tax_city"					=>__("Tax City",			'icwoocommerce_textdomains')
					,"tax_rate_name"			=>__("Tax Name",			'icwoocommerce_textdomains')
					,"tax_rate_code"			=>__("Tax Rate Code",		'icwoocommerce_textdomains')
					,"order_tax_rate"			=>__("Tax Rate",			'icwoocommerce_textdomains')
					,"order_count"				=>__("Order Count",			'icwoocommerce_textdomains')
					,"order_shipping"			=>__("Shipping Amt.",		'icwoocommerce_textdomains')
					,"gross_amount"				=>__("Gross Amt.",			'icwoocommerce_textdomains')
					,"order_total"				=>__("Net Amt.",			'icwoocommerce_textdomains')
					,"refund_order_total"		=>__("Part Refund.",		'icwoocommerce_textdomains')
					,"net_order_total"			=>__("(Net- Refund) Amt.",	'icwoocommerce_textdomains')
					,"shipping_tax_amount"		=>__("Shipping Tax",		'icwoocommerce_textdomains')
					,"tax_amount"				=>__("Order Tax",			'icwoocommerce_textdomains')
					,"total_tax"				=>__("Total Tax",			'icwoocommerce_textdomains')
				);
			}elseif($c == 'tax_group_by_state'){
				$columns = array(
					"billing_country"			=>__("Tax Country",			'icwoocommerce_textdomains')
					,"billing_state"			=>__("Tax State",			'icwoocommerce_textdomains')
					,"tax_rate_name"			=>__("Tax Name",			'icwoocommerce_textdomains')
					,"tax_rate_code"			=>__("Tax Rate Code",		'icwoocommerce_textdomains')
					,"order_tax_rate"			=>__("Tax Rate",			'icwoocommerce_textdomains')
					,"order_count"				=>__("Order Count",			'icwoocommerce_textdomains')
					,"order_shipping"			=>__("Shipping Amt.",		'icwoocommerce_textdomains')
					,"gross_amount"				=>__("Gross Amt.",			'icwoocommerce_textdomains')
					,"order_total"				=>__("Net Amt.",			'icwoocommerce_textdomains')
					,"refund_order_total"		=>__("Part Refund.",		'icwoocommerce_textdomains')
					,"net_order_total"			=>__("(Net- Refund) Amt.",	'icwoocommerce_textdomains')
					,"shipping_tax_amount"		=>__("Shipping Tax",		'icwoocommerce_textdomains')
					,"tax_amount"				=>__("Order Tax",			'icwoocommerce_textdomains')
					,"total_tax"				=>__("Total Tax",			'icwoocommerce_textdomains')
					
					
				);
			}elseif($c == 'tax_group_by_country'){
				$columns = array(
					"billing_country"			=>__("Tax Country",			'icwoocommerce_textdomains')
					,"tax_rate_name"			=>__("Tax Name",			'icwoocommerce_textdomains')
					,"tax_rate_code"			=>__("Tax Rate Code",		'icwoocommerce_textdomains')
					,"order_tax_rate"			=>__("Tax Rate",			'icwoocommerce_textdomains')
					,"order_count"				=>__("Order Count",			'icwoocommerce_textdomains')
					,"order_shipping"			=>__("Shipping Amt.",		'icwoocommerce_textdomains')
					,"gross_amount"				=>__("Gross Amt.",			'icwoocommerce_textdomains')
					,"order_total"				=>__("Net Amt.",			'icwoocommerce_textdomains')
					,"refund_order_total"		=>__("Part Refund.",		'icwoocommerce_textdomains')
					,"net_order_total"			=>__("(Net- Refund) Amt.",	'icwoocommerce_textdomains')
					,"shipping_tax_amount"		=>__("Shipping Tax",		'icwoocommerce_textdomains')
					,"tax_amount"				=>__("Order Tax",			'icwoocommerce_textdomains')
					,"total_tax"				=>__("Total Tax",			'icwoocommerce_textdomains')
				);
			}elseif($c == 'tax_group_by_zip'){
				$columns = array(
					"billing_country"			=>__("Tax Country",			'icwoocommerce_textdomains')
					,"billing_postcode"			=>__("Tax Zip",				'icwoocommerce_textdomains')
					,"tax_rate_name"			=>__("Tax Name",			'icwoocommerce_textdomains')
					,"tax_rate_code"			=>__("Tax Rate Code",		'icwoocommerce_textdomains')
					,"order_tax_rate"			=>__("Tax Rate",			'icwoocommerce_textdomains')
					,"order_count"				=>__("Order Count",			'icwoocommerce_textdomains')
					,"order_shipping"			=>__("Shipping Amt.",		'icwoocommerce_textdomains')
					,"gross_amount"				=>__("Gross Amt.",			'icwoocommerce_textdomains')
					,"order_total"				=>__("Net Amt.",			'icwoocommerce_textdomains')
					,"refund_order_total"		=>__("Part Refund.",		'icwoocommerce_textdomains')
					,"net_order_total"			=>__("(Net- Refund) Amt.",	'icwoocommerce_textdomains')
					,"shipping_tax_amount"		=>__("Shipping Tax",		'icwoocommerce_textdomains')
					,"tax_amount"				=>__("Order Tax",			'icwoocommerce_textdomains')
					,"total_tax"				=>__("Total Tax",			'icwoocommerce_textdomains')
				);
			}elseif($c == 'tax_group_by_tax_name'){
				$columns = array(					
					"tax_rate_name"				=>__("Tax Name",			'icwoocommerce_textdomains')
					,"tax_rate_code"			=>__("Tax Rate Code",		'icwoocommerce_textdomains')
					,"order_tax_rate"			=>__("Tax Rate",			'icwoocommerce_textdomains')
					//,"billing_state"			=>__("Billing State",		'icwoocommerce_textdomains')
					,"order_count"				=>__("Order Count",			'icwoocommerce_textdomains')
					,"order_shipping"			=>__("Shipping Amt.",		'icwoocommerce_textdomains')
					,"gross_amount"				=>__("Gross Amt.",			'icwoocommerce_textdomains')
					,"order_total"				=>__("Net Amt.",			'icwoocommerce_textdomains')
					,"refund_order_total"		=>__("Part Refund.",		'icwoocommerce_textdomains')
					,"net_order_total"			=>__("(Net- Refund) Amt.",	'icwoocommerce_textdomains')
					,"shipping_tax_amount"		=>__("Shipping Tax",		'icwoocommerce_textdomains')
					,"tax_amount"				=>__("Order Tax",			'icwoocommerce_textdomains')
					,"total_tax"				=>__("Total Tax",			'icwoocommerce_textdomains')
				);
			}elseif($c == 'tax_group_by_tax_summary'){
				$columns = array(					
					"tax_rate_name"				=>__("Tax Name",			'icwoocommerce_textdomains')
					,"order_tax_rate"			=>__("Tax Rate"	,			'icwoocommerce_textdomains')
					,"order_count"				=>__("Order Count",			'icwoocommerce_textdomains')
					,"order_shipping"			=>__("Shipping Amt.",		'icwoocommerce_textdomains')
					,"gross_amount"				=>__("Gross Amt.",			'icwoocommerce_textdomains')
					,"order_total"				=>__("Net Amt.",			'icwoocommerce_textdomains')
					,"refund_order_total"		=>__("Part Refund.",		'icwoocommerce_textdomains')
					,"net_order_total"			=>__("(Net- Refund) Amt.",	'icwoocommerce_textdomains')
					,"shipping_tax_amount"		=>__("Shipping Tax",		'icwoocommerce_textdomains')
					,"tax_amount"				=>__("Order Tax",			'icwoocommerce_textdomains')
					,"total_tax"				=>__("Total Tax",			'icwoocommerce_textdomains')
				);
			}elseif($c == 'tax_group_by_city_summary'){
				$columns = array(
					"billing_country"			=>__("Tax Country",			'icwoocommerce_textdomains')
					,"billing_state"			=>__("Tax State",			'icwoocommerce_textdomains')/*New 2016-11-21*/
					,"tax_city"					=>__("Tax City",			'icwoocommerce_textdomains')
					,"order_count"				=>__("Order Count",			'icwoocommerce_textdomains')
					,"order_shipping"			=>__("Shipping Amt.",		'icwoocommerce_textdomains')
					,"gross_amount"				=>__("Gross Amt.",			'icwoocommerce_textdomains')
					,"order_total"				=>__("Net Amt.",			'icwoocommerce_textdomains')
					,"refund_order_total"		=>__("Part Refund.",		'icwoocommerce_textdomains')
					,"net_order_total"			=>__("(Net- Refund) Amt.",	'icwoocommerce_textdomains')
					,"shipping_tax_amount"		=>__("Shipping Tax",		'icwoocommerce_textdomains')
					,"tax_amount"				=>__("Order Tax",			'icwoocommerce_textdomains')
					,"total_tax"				=>__("Total Tax",			'icwoocommerce_textdomains')
				);
			}elseif($c == 'tax_group_by_state_summary'){
				$columns = array(
					"billing_country"			=>__("Tax Country",			'icwoocommerce_textdomains')
					,"billing_state"			=>__("Tax State",			'icwoocommerce_textdomains')
					,"order_count"				=>__("Order Count",			'icwoocommerce_textdomains')
					,"order_shipping"			=>__("Shipping Amt.",		'icwoocommerce_textdomains')
					,"gross_amount"				=>__("Gross Amt.",			'icwoocommerce_textdomains')
					,"order_total"				=>__("Net Amt.",			'icwoocommerce_textdomains')
					,"refund_order_total"		=>__("Part Refund.",		'icwoocommerce_textdomains')
					,"net_order_total"			=>__("(Net- Refund) Amt.",	'icwoocommerce_textdomains')
					,"shipping_tax_amount"		=>__("Shipping Tax",		'icwoocommerce_textdomains')
					,"tax_amount"				=>__("Order Tax",			'icwoocommerce_textdomains')
					,"total_tax"				=>__("Total Tax",			'icwoocommerce_textdomains')
				);
			}elseif($c == 'tax_group_by_country_summary'){
				$columns = array(
					"billing_country"			=>__("Tax Country",			'icwoocommerce_textdomains')
					,"order_count"				=>__("Order Count",			'icwoocommerce_textdomains')
					,"order_shipping"			=>__("Shipping Amt.",		'icwoocommerce_textdomains')
					,"gross_amount"				=>__("Gross Amt.",			'icwoocommerce_textdomains')
					,"order_total"				=>__("Net Amt.",			'icwoocommerce_textdomains')
					,"refund_order_total"		=>__("Part Refund.",		'icwoocommerce_textdomains')
					,"net_order_total"			=>__("(Net- Refund) Amt.",	'icwoocommerce_textdomains')
					,"shipping_tax_amount"		=>__("Shipping Tax",		'icwoocommerce_textdomains')
					,"tax_amount"				=>__("Order Tax",			'icwoocommerce_textdomains')
					,"total_tax"				=>__("Total Tax",			'icwoocommerce_textdomains')
				);
			}else{
				$columns = array(					
					"order_tax_rate"			=>__("Tax Rate",			'icwoocommerce_textdomains')
					,"shipping_tax_amount"		=>__("Shipping Tax",		'icwoocommerce_textdomains')
					,"tax_amount"				=>__("Order Tax",			'icwoocommerce_textdomains')
					,"total_tax"				=>__("Total Tax",			'icwoocommerce_textdomains')
				);
			}
			
			$columns['refund_tax_amount'] 			= __("Refund Tax",				'icwoocommerce_textdomains');
			$columns['refund_shipping_tax_amount'] 	= __("Refund Shipping Tax",		'icwoocommerce_textdomains');
			$columns['total_tax_refund'] 			= __("Total Tax Refund",		'icwoocommerce_textdomains');
			
			$columns['net_total_tax'] 			= __("Net Total Tax",		'icwoocommerce_textdomains');
			
			//tax_group_by_state_summary
			return $columns;
		}
		
		function get_price_columns(){
			$tax_group_by 		= $this->get_request('tax_group_by');
			$price_columns		= array('order_tax_rate','order_count','order_shipping','gross_amount','order_total','_shipping_tax_amount','_order_tax','_total_tax','r_order_tax','r_total_tax','tax_amount','shipping_tax_amount','total_tax','refund_tax_amount','refund_shipping_tax_amount','total_tax_refund','refund_order_total','net_order_total','net_total_tax');
			$price_columns		= apply_filters("ic_commerce_price_columns",$price_columns,$tax_group_by);
			
			return $price_columns;
		}
		
		function get_grid($type = 'limit_row'){
			$request		= $this->get_all_request();extract($request);
			
			
			
			$order_items 	= $this->get_tax_items_query($type);
			$columns 		= $this->get_column($tax_group_by);
			$summary = array();
			//echo $type;
			
			if(count($order_items) > 0):
			
			$this->print_header($type);	
			
			if($type != 'all_row'):
				echo '<div class="top_buttons">';
				$this->export_to_csv_button('top', $total_pages, $summary);
				echo '<div class="clearfix"></div></div>';
			else: 
				$this->back_print_botton('top');
			endif;
			
			$price_columns = $this->get_price_columns();
			
			
			?>
           
            <style type="text/css">
			 	<?php 
					$tds = implode(', td.',$price_columns);
					$ths = implode(', th.',$price_columns);					
					echo 'th.'.$ths.', td.'.$tds.'{ text-align:right}';
				?>
				tr.total_row{ background-color:#CCC; font-weight:bold}
				table.widefat{ margin-bottom:10px;}
			</style>
            <table style="width:100%" class="widefat">
                <thead>
                    <tr class="first">
                        <?php foreach($columns as $key => $value):?>
                            <th class="<?php echo $key;?>"<?php //echo $display;?>><?php echo $value;?></th>
                        <?php endforeach;?>							
                    </tr>
                </thead>
                <tbody>
                    <?php 
					$output =  $this->get_body_grid($order_items, $tax_group_by);
					echo $output;
					?>
                </tbody>           
            </table>
			<?php 
			
			if($type != 'all_row'):
				echo '<div class="bottom_buttons">';
				$this->export_to_csv_button('bottom', $total_pages, $summary);
				echo '<div class="clearfix"></div></div>';
			else: 
				$this->back_print_botton('bottom');
			endif;
			
			else:?>        
						<div class="order_not_found"><?php _e('No orders found','icwoocommerce_textdomains'); ?></div>
				<?php endif;?>
            <?php
		}  
		
		function get_body_grid($items, $tax_group_by){ 
			
			switch($tax_group_by){
				case "tax_group_by_city":
					$body_grid = $this->get_body_grid_tax_group_by_state($items,'tax_city');
					break;
				case "tax_group_by_state":
					$body_grid = $this->get_body_grid_tax_group_by_state($items,'billing_state');
					break;
				case "tax_group_by_country":
					$body_grid = $this->get_body_grid_tax_group_by_state($items,'billing_country');
					break;
				case "tax_group_by_zip":
					$body_grid = $this->get_body_grid_tax_group_by_state($items,'billing_postcode');
					break;
				case "tax_group_by_tax_name":
					$body_grid = $this->get_body_grid_tax_group_by_tax_name($items);
					break;
				case "tax_group_by_tax_summary":
					$body_grid = $this->get_body_grid_tax_group_by_tax_summary($items);
					break;
				case "tax_group_by_city_summary":
					$body_grid = $this->get_body_grid_tax_group_by_state_summary($items,'tax_city');
					break;
				case "tax_group_by_state_summary":
					$body_grid = $this->get_body_grid_tax_group_by_state_summary($items,'billing_state');
					break;
				case "tax_group_by_country_summary":
					$body_grid = $this->get_body_grid_tax_group_by_state_summary($items,'billing_country');
					break;
				default:
					$body_grid = $this->get_body_grid_tax_group_by_tax_name($items);
					break;
			}
			
			return $body_grid;
		}
		
		function get_body_grid_tax_group_by_state($order_items, $tax_group_by_key = 'billing_state'){
			$last_state 	= "";
			$row_count 		= 0;
			$output 		= '';
			
			
			
			$request		= $this->get_all_request();extract($request);
			$columns 		= $this->get_column($tax_group_by);
			
			$total_row = array("_shipping_tax_amount" => 0,"tax_amount" => 0,"total_tax" => 0);
			
			$country    = $this->get_wc_countries();//Added 20150225
			
			$tr_id = 0;
			
			foreach ( $order_items as $item_key => $order_item ) {
				//$order_item->_total_tax	   = $order_item->_shipping_tax_amount + $order_item->_order_tax;				
				//$order_item->_order_amount = $this->get_percentage($order_item->_order_tax,$order_item->order_tax_rate);
				$order_item->tax_rate_name = isset($order_item->tax_rate_name) ? trim($order_item->tax_rate_name) : '';
				$order_item->tax_rate_name = strlen($order_item->tax_rate_name)<=0 ? $order_item->tax_rate_code : $order_item->tax_rate_name;				
				$order_item->billing_state = isset($order_item->billing_state) ? $order_item->billing_state : '';
				
				if($last_state != $order_item->$tax_group_by_key){
					if($tr_id != 0){
						
						$alternate = "total_row ";
						$output .= '<tr class="'.$alternate."row_".$key.'">';
						foreach($columns as $key => $value):
							$td_class = $key;                                            
							$td_value = "";
							switch($key):                                            
								
								case "tax_amount":
								case "shipping_tax_amount":
								case "total_tax":
								
								case "refund_tax_amount":
								case "refund_shipping_tax_amount":
								case "total_tax_refund":
								case "net_total_tax":
								
									$td_value = isset($total_row[$key]) ? $total_row[$key] : 0;
									$td_value = $this->price($td_value);
									break;
								default:
									$td_value = '';
									break;
							endswitch;
							$td_content = "<td class=\"{$td_class}\">{$td_value}</td>\n";
							$output .= $td_content;
						endforeach; 
						$output .= '</tr>';
						$row_count = 0;
						$total_row = array();
					}
					$alternate = "";
					$output .= '<tr class="'.$alternate."row_".$item_key.'">';
						foreach($columns as $key => $value):
							$td_class = $key;                                            
							$td_value = "";
							switch($key):                                            
								case "billing_state":
									$billing_state = isset($order_item->$key) ? $order_item->$key : '';
									$billing_country = isset($order_item->billing_country) ? $order_item->billing_country : '';
									$td_value = $this->get_billling_state_name($billing_country, $billing_state);                                                
									break;
								case "billing_country":
									$billing_country = isset($order_item->$key) ? $order_item->$key : '';
									$billing_country = isset($country->countries[$billing_country]) ? $country->countries[$billing_country]: $billing_country;
									$td_value = $billing_country;
									break;
								case "tax_city":
								case "billing_postcode":
									$td_value = isset($order_item->$key) ? $order_item->$key : '';
									break;
								default:
									$td_value = '';
									break;
							endswitch;
							$td_content = "<td class=\"{$td_class}\">{$td_value}</td>\n";
							$output .= $td_content;
						endforeach; 
						$row_count = 0;
					$output .= '</tr>';
				}
				
				//$total_row['_shipping_tax_amount'] = isset($total_row['_shipping_tax_amount']) ? ($total_row['_shipping_tax_amount'] + $order_item->_shipping_tax_amount) : $order_item->_shipping_tax_amount;
				//$total_row['_order_tax'] = isset($total_row['_order_tax']) ? ($total_row['_order_tax'] + $order_item->_order_tax) : $order_item->_order_tax;
				//$total_row['_total_tax'] = isset($total_row['_total_tax']) ? ($total_row['_total_tax'] + $order_item->_total_tax) : $order_item->_total_tax;
				
				$total_row = $this->get_order_total($total_row,$order_item);
				
				if($row_count%2 == 0){$alternate = "alternate ";}else{$alternate = "";};
				$output .= '<tr class="'.$alternate."row_".$key.'">';
						foreach($columns as $key => $value):
							$td_class = $key;                                            
							$td_value = "";
							switch($key):
								case "billing_state":
								case "billing_country":									
								case "tax_city":
								case "billing_postcode":
									$td_value = '';
									break;
								case "order_count":
									$td_value = isset($order_item->$key) ? $order_item->$key : 0;
									break;
								case "order_tax_rate":
									$td_value = isset($order_item->$key) ? $order_item->$key : 0;
									$td_value = sprintf("%.2f%%",$td_value);
									break;
							
								case "order_total_amount":								
								case "order_total":
								case "order_shipping":
								case "cart_discount":
								case "order_discount":
								case "total_discount":
								case "order_tax":
								case "order_shipping_tax":
								case "total_tax":
								case "gross_amount":
								case "shipping_tax_amount":
								case "refund_tax_amount":
								case "refund_shipping_tax_amount":
								case "total_tax_refund":
								
								case "tax_amount":
								case "shipping_tax_amount":
								case "total_tax":
								
								case "refund_tax_amount":
								case "refund_shipping_tax_amount":
								case "total_tax_refund":
								
								case "net_order_total":
								case "refund_order_total":
								case "net_total_tax":
								
									$td_value = isset($order_item->$key) ? $order_item->$key : 0;
									$td_value = $this->price($td_value);
									break;													
								default:
									$td_value = isset($order_item->$key) ? $order_item->$key : '';
									break;
							endswitch;
							$td_content = "<td class=\"{$td_class}\">{$td_value}</td>\n";
							$output .= $td_content;
						endforeach;                                        	
				   $output .= '</tr>';					 
					$last_state = $order_item->$tax_group_by_key;
					$row_count++;
					$tr_id++;
				}
				
				$alternate = "total_row ";
				$output .= '<tr class="'.$alternate."row_".$key.'">';
				foreach($columns as $key => $value):
					$td_class = $key;                                            
					$td_value = "";
					switch($key):                                            
						case "tax_amount":
						case "shipping_tax_amount":
						case "total_tax":
						
						case "refund_tax_amount":
						case "refund_shipping_tax_amount":
						case "total_tax_refund":
						case "net_total_tax":
							$td_value = isset($total_row[$key]) ? $total_row[$key] : 0;
							$td_value = $this->price($td_value);
							break;
						default:
							$td_value = '';
							break;
					endswitch;
					$td_content = "<td class=\"{$td_class}\">{$td_value}</td>\n";
					$output .= $td_content;
				endforeach; 
				$output .= '</tr>';
				$row_count = 0;
				return $output;
		}
		
		function get_body_grid_tax_group_by_tax_name($order_items){
			$last_state 	= "";
			$row_count 		= 0;
			$output 		= '';
			$tr_id 			= 0;
			
			$request		= $this->get_all_request();extract($request);
			$columns = $this->get_column($tax_group_by);
			
			$total_row = array("shipping_tax_amount" => 0,"tax_amount" => 0,"total_tax" => 0);
			
			foreach ( $order_items as $key => $order_item ) {
				//$order_item->_total_tax = $order_item->_shipping_tax_amount + $order_item->_order_tax;
				//$order_item->_order_amount = $order_item->_order_tax > 0 ? ($order_item->_order_tax*100)/$order_item->order_tax_rate : 0;	
				//$order_item->_order_amount = $this->get_percentage($order_item->_order_tax,$order_item->order_tax_rate);//Added 20150206			
				$order_item->tax_rate_name = isset($order_item->tax_rate_name) ? trim($order_item->tax_rate_name) : '';
				$order_item->tax_rate_name = strlen($order_item->tax_rate_name)<=0 ? $order_item->tax_rate_code : $order_item->tax_rate_name;				
				$order_item->billing_state = isset($order_item->billing_state) ? $order_item->billing_state : '';
				
				if($last_state != $order_item->tax_rate_name){
					if($tr_id != 0){
						$alternate = "total_row ";
						$output .= '<tr class="'.$alternate."row_".$key.'">';
						foreach($columns as $key => $value):
							$td_class = $key;                                            
							$td_value = "";
							switch($key):                                            
								case "order_total":
								case "order_shipping":
								case "cart_discount":
								case "order_discount":
								case "total_discount":
								case "order_tax":
								case "order_shipping_tax":
								case "total_tax":
								case "gross_amount":
								
								case "tax_amount":
								case "shipping_tax_amount":
								case "total_tax":
								
								case "refund_tax_amount":
								case "refund_shipping_tax_amount":
								case "total_tax_refund":
								
								case "net_order_total":
								case "refund_order_total":
								case "net_total_tax":
								
									$td_value = isset($total_row[$key]) ? $total_row[$key] : 0;
									$td_value = $this->price($td_value);
									break;
								default:
									$td_value = '';
									break;
							endswitch;
							$td_content = "<td class=\"{$td_class}\">{$td_value}</td>\n";
							$output .= $td_content;
						endforeach; 
						$output .= '</tr>';
						$row_count = 0;
						$total_row = array();
					}
					$alternate = "";
					$output .= '<tr class="'.$alternate."row_".$key.'">';
						foreach($columns as $key => $value):
							$td_class = $key;                                            
							$td_value = "";
							switch($key):                                            
								case "tax_rate_name":
									$td_value = isset($order_item->$key) ? $order_item->$key : '';
									break;
								default:
									$td_value = '';
									break;
							endswitch;
							$td_content = "<td class=\"{$td_class}\">{$td_value}</td>\n";
							$output .= $td_content;
						endforeach; 
						$row_count = 0;
					$output .= '</tr>';
				}
				
				//$total_row['_shipping_tax_amount'] = isset($total_row['_shipping_tax_amount']) ? ($total_row['_shipping_tax_amount'] + $order_item->_shipping_tax_amount) : $order_item->_shipping_tax_amount;
				//$total_row['_order_tax'] = isset($total_row['_order_tax']) ? ($total_row['_order_tax'] + $order_item->_order_tax) : $order_item->_order_tax;
				//$total_row['_total_tax'] = isset($total_row['_total_tax']) ? ($total_row['_total_tax'] + $order_item->_total_tax) : $order_item->_total_tax;
				
				$total_row = $this->get_order_total($total_row,$order_item);
				
				if($row_count%2 == 0){$alternate = "alternate ";}else{$alternate = "";};
				$output .= '<tr class="'.$alternate."row_".$key.'">';
						foreach($columns as $key => $value):
							$td_class = $key;                                            
							$td_value = "";
							switch($key):
								case "tax_rate_name":
									$td_value = '';
									break;
								case "billing_state":
									$billing_state = isset($order_item->$key) ? $order_item->$key : '';
									$billing_country = isset($order_item->billing_country) ? $order_item->billing_country : '';
									$td_value = $this->get_billling_state_name($billing_country, $billing_state);                                                
									break;
								case "_order_count":
									$td_value = isset($order_item->$key) ? $order_item->$key : 0;
									break;
								case "order_tax_rate":
									$td_value = isset($order_item->$key) ? $order_item->$key : 0;
									$td_value = sprintf("%.2f%%",$td_value);
									break;
								
								case "order_total":
								case "order_shipping":
								case "cart_discount":
								case "order_discount":
								case "total_discount":
								case "order_tax":
								case "order_shipping_tax":
								case "total_tax":
								case "gross_amount":
								
								case "tax_amount":
								case "shipping_tax_amount":
								case "total_tax":
								
								case "refund_tax_amount":
								case "refund_shipping_tax_amount":
								case "total_tax_refund":
								
								case "net_order_total":
								case "refund_order_total":
								case "net_total_tax":
								
									$td_value = isset($order_item->$key) ? $order_item->$key : 0;
									$td_value = $this->price($td_value);
									break;													
								default:
									$td_value = isset($order_item->$key) ? $order_item->$key : '';
									break;
							endswitch;
							$td_content = "<td class=\"{$td_class}\">{$td_value}</td>\n";
							$output .= $td_content;
						endforeach;                                        	
				    $output .= '</tr>';					 
					$last_state = $order_item->tax_rate_name;
					$row_count++;
					$tr_id++; 	
				}
				
				$alternate = "total_row ";
				$output .= '<tr class="'.$alternate."row_".$key.'">';
				foreach($columns as $key => $value):
					$td_class = $key;                                            
					$td_value = "";
					switch($key):                                            
						case "tax_amount":
						case "shipping_tax_amount":
						case "total_tax":
						
						case "refund_tax_amount":
						case "refund_shipping_tax_amount":
						case "total_tax_refund":
						case "net_total_tax":
						
							$td_value = isset($total_row[$key]) ? $total_row[$key] : 0;
							$td_value = $this->price($td_value);
							break;
						default:
							$td_value = '';
							break;
					endswitch;
					$td_content = "<td class=\"{$td_class}\">{$td_value}</td>\n";
					$output .= $td_content;
				endforeach; 
				$output .= '</tr>';
				$row_count = 0;
				return $output;
		}
		
		function get_order_total($total_row,$order_item){
			
			//$order_item->r_order_tax = isset( $order_item->r_order_tax) ?  $order_item->r_order_tax : 0;
			//$order_item->r_total_tax = isset( $order_item->r_total_tax) ?  $order_item->r_total_tax : 0;
			$order_item->order_shipping_tax = isset( $order_item->order_shipping_tax) ?  $order_item->order_shipping_tax : 0;
			
			$total_row['order_count'] = isset($total_row['order_count']) ? ($total_row['order_count'] + $order_item->order_count) : $order_item->order_count;
			
			//$total_row['r_order_tax'] = isset($total_row['r_order_tax']) ? ($total_row['r_order_tax'] + $order_item->r_order_tax) : $order_item->r_order_tax;
			//$total_row['r_total_tax'] = isset($total_row['r_total_tax']) ? ($total_row['r_total_tax'] + $order_item->r_total_tax) : $order_item->r_total_tax;				
			$total_row['gross_amount'] = isset($total_row['gross_amount']) ? ($total_row['gross_amount'] + $order_item->gross_amount) : $order_item->gross_amount;
			$total_row['order_total'] = isset($total_row['order_total']) ? ($total_row['order_total'] + $order_item->order_total) : $order_item->order_total;
			$total_row['order_shipping'] = isset($total_row['order_shipping']) ? ($total_row['order_shipping'] + $order_item->order_shipping) : $order_item->order_shipping;
			$total_row['order_shipping_tax'] = isset($total_row['order_shipping_tax']) ? ($total_row['order_shipping_tax'] + $order_item->order_shipping_tax) : $order_item->order_shipping_tax;
			
			
			
			$total_row['tax_amount'] 					= isset($total_row['tax_amount']) ? ($total_row['tax_amount'] + $order_item->tax_amount) : $order_item->tax_amount;
			$total_row['shipping_tax_amount'] 			= isset($total_row['shipping_tax_amount']) ? ($total_row['shipping_tax_amount'] + $order_item->shipping_tax_amount) : $order_item->shipping_tax_amount;
			$total_row['total_tax'] 					= isset($total_row['total_tax']) ? ($total_row['total_tax'] + $order_item->total_tax) : $order_item->total_tax;
			
			$total_row['refund_tax_amount'] 			= isset($total_row['refund_tax_amount']) ? ($total_row['refund_tax_amount'] + $order_item->refund_tax_amount) : $order_item->refund_tax_amount;
			$total_row['refund_shipping_tax_amount'] 	= isset($total_row['refund_shipping_tax_amount']) ? ($total_row['refund_shipping_tax_amount'] + $order_item->refund_shipping_tax_amount) : $order_item->refund_shipping_tax_amount;
			$total_row['total_tax_refund'] 				= isset($total_row['total_tax_refund']) 	? ($total_row['total_tax_refund'] 	+ $order_item->total_tax_refund) : $order_item->total_tax_refund;
			$total_row['net_total_tax'] 				= isset($total_row['net_total_tax']) 		? ($total_row['net_total_tax'] 		+ ($order_item->net_total_tax)) : $order_item->net_total_tax;
			
			
			$total_row['net_order_total'] = isset($total_row['net_order_total']) ? ($total_row['net_order_total'] + $order_item->net_order_total) : $order_item->net_order_total;
			$total_row['refund_order_total'] = isset($total_row['refund_order_total']) ? ($total_row['refund_order_total'] + $order_item->refund_order_total) : $order_item->refund_order_total;
			
			
			
			
			
			return $total_row;
		}
		
		function get_body_grid_tax_group_by_tax_summary($order_items){
			$last_state 	= "";
			$row_count 		= 0;
			$output 		= '';
			
			$request		= $this->get_all_request();extract($request);
			$columns = $this->get_column($tax_group_by);
			
			$total_row = array("_shipping_tax_amount" => 0,"tax_amount" => 0,"total_tax" => 0);
			
			foreach ( $order_items as $key => $order_item ) {
				//$order_item->_total_tax = $order_item->_shipping_tax_amount + $order_item->_order_tax;
				//$order_item->_order_amount = $order_item->_order_tax > 0 ? ($order_item->_order_tax*100)/$order_item->order_tax_rate : 0;	
				//$order_item->_order_amount = $this->get_percentage($order_item->_order_tax,$order_item->order_tax_rate);//Added 20150206			
				$order_item->tax_rate_name = isset($order_item->tax_rate_name) ? trim($order_item->tax_rate_name) : '';
				$order_item->tax_rate_name = strlen($order_item->tax_rate_name)<=0 ? $order_item->tax_rate_code : $order_item->tax_rate_name;				
				$order_item->billing_state = isset($order_item->billing_state) ? $order_item->billing_state : '';
				
				//$total_row['_shipping_tax_amount'] = isset($total_row['_shipping_tax_amount']) ? ($total_row['_shipping_tax_amount'] + $order_item->_shipping_tax_amount) : $order_item->_shipping_tax_amount;
				//$total_row['_order_tax'] = isset($total_row['_order_tax']) ? ($total_row['_order_tax'] + $order_item->_order_tax) : $order_item->_order_tax;
				//$total_row['_total_tax'] = isset($total_row['_total_tax']) ? ($total_row['_total_tax'] + $order_item->_total_tax) : $order_item->_total_tax;
				
				$total_row = $this->get_order_total($total_row,$order_item);
				
				if($row_count%2 == 0){$alternate = "alternate ";}else{$alternate = "";};
				$output .= '<tr class="'.$alternate."row_".$key.'">';
						foreach($columns as $key => $value):
							$td_class = $key;                                            
							$td_value = "";
							switch($key):
								case "billing_state":
									$td_value = isset($order_item->$key) ? $order_item->$key : 0;
									break;
								case "_order_count":
									$td_value = isset($order_item->$key) ? $order_item->$key : 0;
									break;
								case "order_tax_rate":
									$td_value = isset($order_item->$key) ? $order_item->$key : 0;
									$td_value = sprintf("%.2f%%",$td_value);
									break;
								case "_order_shipping_amount":
								case "_order_amount":
								case "order_total_amount":
								case "_shipping_tax_amount":
								case "_order_tax":
								case "_total_tax":
								case "r_order_tax":
								case "r_total_tax":
								case "order_total":
								case "order_shipping":
								case "cart_discount":
								case "order_discount":
								case "total_discount":
								case "order_tax":
								case "order_shipping_tax":
								case "total_tax":
								case "gross_amount":
								case "tax_amount":
								case "shipping_tax_amount":
								case "total_tax":
								
								case "refund_tax_amount":
								case "refund_shipping_tax_amount":
								case "total_tax_refund":
								
								case "net_order_total":
								case "refund_order_total":
								case "net_total_tax":
									$td_value = isset($order_item->$key) ? $order_item->$key : 0;
									$td_value = $this->price($td_value);
									break;													
								default:
									$td_value = isset($order_item->$key) ? $order_item->$key : '';
									break;
							endswitch;
							$td_content = "<td class=\"{$td_class}\">{$td_value}</td>\n";
							$output .= $td_content;
						endforeach;                                        	
				   $output .= '</tr>';					 
				$last_state = $order_item->billing_state;
				$row_count++;
				}
				
				$alternate = "total_row ";
				$output .= '<tr class="'.$alternate."row_".$key.'">';
				foreach($columns as $key => $value):
					$td_class = $key;                                            
					$td_value = "";
					switch($key):                                            
						
						case "tax_amount":
						case "shipping_tax_amount":
						case "total_tax":
						
						case "refund_tax_amount":
						case "refund_shipping_tax_amount":
						case "total_tax_refund":
						case "net_total_tax":
							$td_value = isset($total_row[$key]) ? $total_row[$key] : 0;
							$td_value = $this->price($td_value);
							break;
						default:
							$td_value = '';
							break;
					endswitch;
					$td_content = "<td class=\"{$td_class}\">{$td_value}</td>\n";
					$output .= $td_content;
				endforeach; 
				$output .= '</tr>';
				$row_count = 0;
				return $output;
		}
		
		function get_body_grid_tax_group_by_state_summary($order_items, $tax_group_by_key = 'billing_state'){
			$last_state 	= "";
			$row_count 		= 0;
			$output 		= '';
			
			$request		= $this->get_all_request();extract($request);
			$columns 		= $this->get_column($tax_group_by);
			
			$total_row = array("_shipping_tax_amount" => 0,"tax_amount" => 0,"total_tax" => 0);
			
			$country    = $this->get_wc_countries();//Added 20150225
			
			
			
			foreach ( $order_items as $key => $order_item ) {
				//$order_item->_total_tax = $order_item->_shipping_tax_amount + $order_item->_order_tax;
				//$order_item->_order_amount = $order_item->_order_tax > 0 ? ($order_item->_order_tax*100)/$order_item->order_tax_rate : 0;				
			//	$order_item->_order_amount = $this->get_percentage($order_item->_order_tax,$order_item->order_tax_rate);//Added 20150206
				$order_item->tax_rate_name = isset($order_item->tax_rate_name) ? trim($order_item->tax_rate_name) : '';
				$order_item->tax_rate_name = strlen($order_item->tax_rate_name)<=0 ? $order_item->tax_rate_code : $order_item->tax_rate_name;				
				$order_item->billing_state = isset($order_item->billing_state) ? $order_item->billing_state : '';
				
				//$total_row['_shipping_tax_amount'] = isset($total_row['_shipping_tax_amount']) ? ($total_row['_shipping_tax_amount'] + $order_item->_shipping_tax_amount) : $order_item->_shipping_tax_amount;
				//$total_row['_order_tax'] = isset($total_row['_order_tax']) ? ($total_row['_order_tax'] + $order_item->_order_tax) : $order_item->_order_tax;
				//$total_row['_total_tax'] = isset($total_row['_total_tax']) ? ($total_row['_total_tax'] + $order_item->_total_tax) : $order_item->_total_tax;
				
				$total_row = $this->get_order_total($total_row,$order_item);
				
				if($row_count%2 == 0){$alternate = "alternate ";}else{$alternate = "";};
				$output .= '<tr class="'.$alternate."row_".$key.'">';
						foreach($columns as $key => $value):
							$td_class = $key;                                            
							$td_value = "";
							switch($key):
								case "billing_state":
									$billing_state = isset($order_item->$key) ? $order_item->$key : '';
									$billing_country = isset($order_item->billing_country) ? $order_item->billing_country : '';
									$td_value = $this->get_billling_state_name($billing_country, $billing_state);                                                
									break;
								case "billing_country":
									$billing_country = isset($order_item->$key) ? $order_item->$key : '';
									$billing_country = isset($country->countries[$billing_country]) ? $country->countries[$billing_country]: $billing_country;
									$td_value = $billing_country;
									break;
								case "tax_city":
								case "billing_postcode":
									$td_value = isset($order_item->$key) ? $order_item->$key : '';
									break;
								case "_order_count":
									$td_value = isset($order_item->$key) ? $order_item->$key : 0;
									break;
								case "order_tax_rate":
									$td_value = isset($order_item->$key) ? $order_item->$key : 0;
									$td_value = sprintf("%.2f%%",$td_value);
									break;
								case "_order_shipping_amount":
								case "_order_amount":
								case "order_total_amount":
								case "_shipping_tax_amount":
								case "_order_tax":
								case "_total_tax":
								case "r_order_tax":
								case "r_total_tax":
								case "order_total":
								case "order_shipping":
								case "cart_discount":
								case "order_discount":
								case "total_discount":
								case "order_tax":
								case "order_shipping_tax":
								case "total_tax":
								case "gross_amount":
								
								case "tax_amount":
								case "shipping_tax_amount":
								case "total_tax":
								
								case "refund_tax_amount":
								case "refund_shipping_tax_amount":
								case "total_tax_refund":
								case "net_total_tax":
								
								case "refund_order_total":
								case "net_order_total":
								
								
									$td_value = isset($order_item->$key) ? $order_item->$key : 0;
									$td_value = $this->price($td_value);
									break;													
								default:
									$td_value = isset($order_item->$key) ? $order_item->$key : '';
									break;
							endswitch;
							$td_content = "<td class=\"{$td_class}\">{$td_value}</td>\n";
							$output .= $td_content;
						endforeach;                                        	
				   $output .= '</tr>';					 
				$last_state = $order_item->billing_state;
				$row_count++;
				}
				
				$alternate = "total_row ";
				$output .= '<tr class="'.$alternate."row_".$key.'">';
				foreach($columns as $key => $value):
					$td_class = $key;                                            
					$td_value = "";
					switch($key):                                            
						case "tax_amount":
						case "shipping_tax_amount":
						case "total_tax":
						
						case "refund_tax_amount":
						case "refund_shipping_tax_amount":
						case "total_tax_refund":
						case "net_total_tax":
						
						
						case "order_total":
						case "order_shipping":
						case "cart_discount":
						case "order_discount":
						case "total_discount":
						case "order_tax":
						case "order_shipping_tax":
						case "total_tax":
						case "gross_amount":
						case "refund_order_total":
						case "net_order_total":
							$td_value = isset($total_row[$key]) ? $total_row[$key] : 0;
							$td_value = $this->price($td_value);
							break;
						case "order_count":
							$td_value = isset($total_row[$key]) ? $total_row[$key] : 0;
							break;
						default:
							$td_value = '';
							break;
					endswitch;
					$td_content = "<td class=\"{$td_class}\">{$td_value}</td>\n";
					$output .= $td_content;
				endforeach; 
				$output .= '</tr>';
				$row_count = 0;
				return $output;
		}
		
		/*anzar*/
		function get_body_grid_tax_group_by_state_summary_export($order_items){
			$last_state 	= "";
			$row_count 		= 0;
			$output 		= '';
			
			$request		= $this->get_all_request();extract($request);
			$columns = $this->get_column($tax_group_by);
			
			$total_row = array("shipping_tax_amount" => 0,"tax_amount" => 0,"total_tax" => 0);
			
			foreach ( $order_items as $key => $order_item ) {
				//$order_item->_total_tax = $order_item->_shipping_tax_amount + $order_item->_order_tax;
				//$order_item->_order_amount = $order_item->_order_tax > 0 ? ($order_item->_order_tax*100)/$order_item->order_tax_rate : 0;				
				//$order_item->_order_amount = $this->get_percentage($order_item->_order_tax,$order_item->order_tax_rate);//Added 20150206
				$order_item->tax_rate_name = isset($order_item->tax_rate_name) ? trim($order_item->tax_rate_name) : '';
				$order_item->tax_rate_name = strlen($order_item->tax_rate_name)<=0 ? $order_item->tax_rate_code : $order_item->tax_rate_name;				
				$order_item->billing_state = isset($order_item->billing_state) ? $order_item->billing_state : '';
				
				//$total_row['_shipping_tax_amount'] = isset($total_row['_shipping_tax_amount']) ? ($total_row['_shipping_tax_amount'] + $order_item->_shipping_tax_amount) : $order_item->_shipping_tax_amount;
				//$total_row['_order_tax'] = isset($total_row['_order_tax']) ? ($total_row['_order_tax'] + $order_item->_order_tax) : $order_item->_order_tax;
				//$total_row['_total_tax'] = isset($total_row['_total_tax']) ? ($total_row['_total_tax'] + $order_item->_total_tax) : $order_item->_total_tax;
				
				
				if($row_count%2 == 0){$alternate = "alternate ";}else{$alternate = "";};
				$output .= '<tr class="'.$alternate."row_".$key.'">';
						foreach($columns as $key => $value):
							$td_class = $key;                                            
							$td_value = "";
							switch($key):
								case "billing_state":
									$billing_state = isset($order_item->$key) ? $order_item->$key : '';
									$billing_country = isset($order_item->billing_country) ? $order_item->billing_country : '';
									$td_value = $this->get_billling_state_name($billing_country, $billing_state);                                                
									break;
								case "_order_count":
									$td_value = isset($order_item->$key) ? $order_item->$key : 0;
									break;
								case "order_tax_rate":
									$td_value = isset($order_item->$key) ? $order_item->$key : 0;
									$td_value = sprintf("%.2f%%",$td_value);
									break;
								case "_order_shipping_amount":
								case "_order_amount":
								case "order_total_amount":
								case "_shipping_tax_amount":
								case "_order_tax":
								case "_total_tax":
								case "r_order_tax":
								case "r_total_tax":
								case "order_total":
								case "order_shipping":
								case "cart_discount":
								case "order_discount":
								case "total_discount":
								case "order_tax":
								case "order_shipping_tax":
								case "total_tax":
								case "gross_amount":
									$td_value = isset($order_item->$key) ? $order_item->$key : 0;
									$td_value = $this->price($td_value);
									break;													
								default:
									$td_value = isset($order_item->$key) ? $order_item->$key : '';
									break;
							endswitch;
							$td_content = "<td class=\"{$td_class}\">{$td_value}</td>\n";
							$output .= $td_content;
						endforeach;                                        	
				   $output .= '</tr>';					 
				$last_state = $order_item->billing_state;
				$row_count++;
				}
				
				$alternate = "total_row ";
				$output .= '<tr class="'.$alternate."row_".$key.'">';
				foreach($columns as $key => $value):
					$td_class = $key;                                            
					$td_value = "";
					switch($key):                                            
						case "_shipping_tax_amount":
						case "_order_tax":
						case "_total_tax":
						case "r_order_tax":
						case "r_total_tax":
						case "order_total":
						case "order_shipping":
						case "cart_discount":
						case "order_discount":
						case "total_discount":
						case "order_tax":
						case "order_shipping_tax":
						case "total_tax":
						case "gross_amount":
							$td_value = isset($total_row[$key]) ? $total_row[$key] : 0;
							$td_value = $this->price($td_value);
							break;
						default:
							$td_value = '';
							break;
					endswitch;
					$td_content = "<td class=\"{$td_class}\">{$td_value}</td>\n";
					$output .= $td_content;
				endforeach; 
				$output .= '</tr>';
				$row_count = 0;
				return $output;
		}
		
		
		function get_tax_items_query_order($type = 'limit_row'){
			global $wpdb;
			$request		= $this->get_all_request();extract($request);
			
			$country_code	= $this->get_string_multi_request('country_code',$country_code, "-1");
			$state_code		= $this->get_string_multi_request('state_code',$state_code, "-1");
			$order_status	= $this->get_string_multi_request('order_status',$order_status, "-1");
			$hide_order_status	= $this->get_string_multi_request('hide_order_status',$hide_order_status, "-1");//New Change ID 20140918
			
			$join_country = false;
			
			if(
				$tax_group_by == "tax_group_by_city" 
				|| $tax_group_by == "tax_group_by_state"
				|| $tax_group_by == "tax_group_by_country"
				|| $tax_group_by == "tax_group_by_zip"
				|| $tax_group_by == "tax_group_by_tax_name"
				|| $tax_group_by == "tax_group_by_city_summary"
				|| $tax_group_by == "tax_group_by_state_summary"
				|| $tax_group_by == "tax_group_by_country_summary"
				|| (!empty($country_code) and $country_code != "-1")
			){
				$join_country = true;
			}
			
			$sql = "  SELECT
			
			SUM(woocommerce_order_itemmeta_tax_amount.meta_value)  				AS tax_amount,
			
			SUM(woocommerce_order_itemmeta_shipping_tax_amount.meta_value)  	AS shipping_tax_amount,
			
			woocommerce_order_items.order_id 									AS order_id, 
			
			woocommerce_order_items.order_item_name 							AS tax_rate_code, 
			
			woocommerce_tax_rates.tax_rate_name 								AS tax_rate_name, 
			
			woocommerce_tax_rates.tax_rate 										AS order_tax_rate
			
			,woocommerce_order_items.order_item_name							AS order_item_name
			
			,CONCAT(woocommerce_order_items.order_id,'-',woocommerce_order_items.order_item_name)	AS _group_column
			";
			
			if($join_country){
				$sql .= ", postmeta4.meta_value 								AS billing_country";
			}
			
			if($tax_group_by == "tax_group_by_state" || $tax_group_by == "tax_group_by_state_summary"){
				$sql .= ", postmeta3.meta_value 								AS billing_state";
			}
			
			if($tax_group_by == "tax_group_by_city" || $tax_group_by == "tax_group_by_city_summary"){
				$sql .= ", postmeta5.meta_value 								AS tax_city";
				$sql .= ", postmeta3.meta_value 								AS billing_state";
			}
			
			if($tax_group_by == "tax_group_by_zip"){
				$sql .= ", postmeta6.meta_value 								AS billing_postcode";
			}
			
			switch($tax_group_by){
				case "tax_group_by_zip":
					$group_sql = ", CONCAT(postmeta4.meta_value,'-',postmeta6.meta_value,'-',lpad(woocommerce_tax_rates.tax_rate,3,'0'),'-',woocommerce_order_items.order_item_name,'-',woocommerce_tax_rates.tax_rate_name,'-',woocommerce_tax_rates.tax_rate) as group_column";
					break;
				case "tax_group_by_city":
					$group_sql = ", CONCAT(postmeta4.meta_value,'-',postmeta5.meta_value,'-',lpad(woocommerce_tax_rates.tax_rate,3,'0'),'-',woocommerce_order_items.order_item_name,'-',woocommerce_tax_rates.tax_rate_name,'-',woocommerce_tax_rates.tax_rate) as group_column";
					break;
				case "tax_group_by_state":
					$group_sql = ", CONCAT(postmeta4.meta_value,'-',postmeta3.meta_value,'-',lpad(woocommerce_tax_rates.tax_rate,3,'0'),'-',woocommerce_order_items.order_item_name,'-',woocommerce_tax_rates.tax_rate_name,'-',woocommerce_tax_rates.tax_rate) as group_column";
					break;
				case "tax_group_by_country":
					$group_sql = ", CONCAT(postmeta4.meta_value,'-',lpad(woocommerce_tax_rates.tax_rate,3,'0'),'-',woocommerce_order_items.order_item_name,'-',woocommerce_tax_rates.tax_rate_name,'-',woocommerce_tax_rates.tax_rate) as group_column";
					break;
				case "tax_group_by_tax_name":
					$group_sql = ", CONCAT(woocommerce_tax_rates.tax_rate_name,'-',lpad(woocommerce_tax_rates.tax_rate,3,'0'),'-',woocommerce_tax_rates.tax_rate_name,'-',woocommerce_tax_rates.tax_rate,'-',postmeta4.meta_value) as group_column";
					break;
				case "tax_group_by_tax_summary":
					$group_sql = ", CONCAT(woocommerce_tax_rates.tax_rate_name,'-',lpad(woocommerce_tax_rates.tax_rate,3,'0'),'-',woocommerce_order_items.order_item_name) as group_column";
					break;
				case "tax_group_by_city_summary":
					$group_sql = ", CONCAT(postmeta4.meta_value,'',postmeta5.meta_value) as group_column";
					break;
				case "tax_group_by_state_summary":
					$group_sql = ", CONCAT(postmeta4.meta_value,'',postmeta3.meta_value) as group_column";
					break;
				case "tax_group_by_country_summary":
					$group_sql = ", CONCAT(postmeta4.meta_value) as group_column";
					break;
				default:
					$group_sql = ", CONCAT(woocommerce_order_items.order_item_name,'-',woocommerce_tax_rates.tax_rate_name,'-',woocommerce_tax_rates.tax_rate,'-',postmeta4.meta_value,'-',postmeta3.meta_value) as group_column";
					break;
				
			}
			
			$sql .= $group_sql;				
			
			$sql .= " FROM {$wpdb->prefix}woocommerce_order_items as woocommerce_order_items";
			
			$sql .= " LEFT JOIN  {$wpdb->prefix}posts as posts ON posts.ID=	woocommerce_order_items.order_id";
			
			if(($order_status_id  && $order_status_id != '-1') || $sort_by == "status"){
				$sql .= " 
				LEFT JOIN  {$wpdb->prefix}term_relationships 	as term_relationships 	ON term_relationships.object_id		=	posts.ID
				
				LEFT JOIN  {$wpdb->prefix}term_taxonomy 		as term_taxonomy 		ON term_taxonomy.term_taxonomy_id	=	term_relationships.term_taxonomy_id";
				
				if($sort_by == "status"){
					$sql .= " LEFT JOIN  {$wpdb->prefix}terms 				as terms 				ON terms.term_id					=	term_taxonomy.term_id";
				}
			}
			
			$sql .= " LEFT JOIN  {$wpdb->prefix}woocommerce_order_itemmeta as woocommerce_order_itemmeta_tax_amount ON woocommerce_order_itemmeta_tax_amount.order_item_id=woocommerce_order_items.order_item_id";
			
			$sql .= " LEFT JOIN  {$wpdb->prefix}woocommerce_order_itemmeta as woocommerce_order_itemmeta_shipping_tax_amount ON woocommerce_order_itemmeta_shipping_tax_amount.order_item_id=woocommerce_order_items.order_item_id";
			
			$sql .= " LEFT JOIN  {$wpdb->prefix}woocommerce_order_itemmeta as woocommerce_order_itemmeta_tax ON woocommerce_order_itemmeta_tax.order_item_id=woocommerce_order_items.order_item_id";
			
			$sql .= " LEFT JOIN  {$wpdb->prefix}woocommerce_tax_rates as woocommerce_tax_rates ON woocommerce_tax_rates.tax_rate_id=woocommerce_order_itemmeta_tax.meta_value";			
			
			if($join_country){
				$sql .= " LEFT JOIN  {$wpdb->prefix}postmeta as postmeta4 ON postmeta4.post_id=woocommerce_order_items.order_id";
			}
			
			if(
				$tax_group_by == "tax_group_by_state" 
				|| $tax_group_by == "tax_group_by_state_summary"
				|| $tax_group_by == "tax_group_by_city_summary"
				|| $tax_group_by == "tax_group_by_city"
				|| (!empty($state_code) and $state_code != "-1"
			)){
				$sql .= " LEFT JOIN  {$wpdb->prefix}postmeta as postmeta3 ON postmeta3.post_id=woocommerce_order_items.order_id";
			}
			
			if($tax_group_by == "tax_group_by_city" || $tax_group_by == "tax_group_by_city_summary"){
				$sql .= " LEFT JOIN  {$wpdb->prefix}postmeta as postmeta5 ON postmeta5.post_id=woocommerce_order_items.order_id";
			}
			
			if($tax_group_by == "tax_group_by_zip"){
				$sql .= " LEFT JOIN  {$wpdb->prefix}postmeta as postmeta6 ON postmeta6.post_id=woocommerce_order_items.order_id";
			}
			
			$sql .= " WHERE 1*1 AND woocommerce_order_items.order_item_type = 'tax'";
			
			$sql .= " AND posts.post_type='shop_order' ";			
			
			$sql .= " AND woocommerce_order_itemmeta_tax.meta_key='rate_id' ";
			
			$sql .= " AND woocommerce_order_itemmeta_tax_amount.meta_key='tax_amount' ";
			
			$sql .= " AND woocommerce_order_itemmeta_shipping_tax_amount.meta_key='shipping_tax_amount' ";
			
			
			if($tax_based_on == "billing"){
				
				if($join_country){
					$sql .= " AND postmeta4.meta_key='_billing_country'";
				}
				
				if($tax_group_by == "tax_group_by_state" || $tax_group_by == "tax_group_by_state_summary"|| $tax_group_by == "tax_group_by_city_summary"
				|| $tax_group_by == "tax_group_by_city" || (!empty($state_code) and $state_code != "-1")){
					$sql .= " AND postmeta3.meta_key='_billing_state'";
				}
				
				if($tax_group_by == "tax_group_by_city" || $tax_group_by == "tax_group_by_city_summary"){
					$sql .= " AND postmeta5.meta_key='_billing_city'";
				}
				if($tax_group_by == "tax_group_by_zip"){
					$sql .= " AND postmeta6.meta_key='_billing_postcode'";
				}
			}else{
				if($join_country){
					$sql .= " AND postmeta4.meta_key='_shipping_country'";
				}
				if($tax_group_by == "tax_group_by_state" || $tax_group_by == "tax_group_by_state_summary"|| $tax_group_by == "tax_group_by_city_summary"
				|| $tax_group_by == "tax_group_by_city" || (!empty($state_code) and $state_code != "-1")){
					$sql .= " AND postmeta3.meta_key='_shipping_state'";
				}
				
				if($tax_group_by == "tax_group_by_city" || $tax_group_by == "tax_group_by_city_summary"){
					$sql .= " AND postmeta5.meta_key='_shipping_city'";
				}
				if($tax_group_by == "tax_group_by_zip"){
					$sql .= " AND postmeta6.meta_key='_shipping_postcode'";
				}
			}
			
			if($order_status_id  && $order_status_id != '-1') $sql .= " AND term_taxonomy.term_id IN (".$order_status_id .")";
			
			if($state_code and $state_code != '-1')	$sql .= " AND postmeta3.meta_value IN (".$state_code.")";
			
			if($country_code and $country_code != '-1')	$sql .= " AND postmeta4.meta_value IN (".$country_code.")";
			
			if($order_status  && $order_status != '-1' and $order_status != "'-1'")$sql .= " AND posts.post_status IN (".$order_status.")";//New Change ID 20140918
			
			if($hide_order_status  && $hide_order_status != '-1' and $hide_order_status != "'-1'")$sql .= " AND posts.post_status NOT IN (".$hide_order_status.")";//New Change ID 20140918
			
			if ($start_date != NULL &&  $end_date !=NULL){
				$sql .= " AND (DATE(posts.post_date) BETWEEN '".$start_date."' AND '". $end_date ."')";
			}
			
			$sql .= "  GROUP BY order_id, order_item_name ";
			
			$sql .= "  ORDER BY order_id ASC";
			
			$wpdb->flush();
			
			$wpdb->query("SET SQL_BIG_SELECTS=1");
			
			$order_items 		= $wpdb->get_results($sql);
			
			if(count($order_items)>0){
				$post_ids 			= $this->get_items_id_list($order_items,'order_id');
				$tax_refund_items 	= $this->get_tax_refund($type);
				
				$shop_order_refund_id_items 	= $this->get_shop_order_refund_id($type,$post_ids);
				
				$extra_meta_keys 	= apply_filters('ic_commerce_tax_report_page_extra_meta_keys', array('order_total','order_shipping','cart_discount','order_discount','order_tax','order_shipping_tax'));
				
				$postmeta_datas 	= $this->get_postmeta($post_ids, array(),$extra_meta_keys,'total');
				
				$order_ids = array();
				foreach ( $order_items as $key => $order_item ) {
						$order_id								= $order_item->order_id;
						$_group_column							= $order_item->_group_column;
						
						$postmeta_data 	= isset($postmeta_datas[$order_id]) ? $postmeta_datas[$order_id] : array();
						
						
						$refund_order_item 	= isset($tax_refund_items[$_group_column]) ? $tax_refund_items[$_group_column] : array();
						
						$order_items[$key]->refund_tax_amount			= isset($refund_order_item->refund_tax_amount)				? $refund_order_item->refund_tax_amount 				: 0;
						$order_items[$key]->refund_shipping_tax_amount	= isset($refund_order_item->refund_shipping_tax_amount)		? $refund_order_item->refund_shipping_tax_amount 		: 0;
						
						$order_items[$key]->refund_tax_amount			= str_replace('-','',$order_items[$key]->refund_tax_amount);
						$order_items[$key]->refund_shipping_tax_amount	= str_replace('-','',$order_items[$key]->refund_shipping_tax_amount);						
						
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
						
						
						$order_items[$key]->gross_amount 		= ($order_items[$key]->order_total + $order_items[$key]->total_discount) - ($order_items[$key]->order_shipping +  $order_items[$key]->order_shipping_tax + $order_items[$key]->order_tax );
						$order_ids[$order_id] = $order_id;
						
						
						$refund_postmeta_data 	= isset($shop_order_refund_id_items[$order_id]) ? $shop_order_refund_id_items[$order_id] : array();
						
						foreach($refund_postmeta_data as $postmeta_key => $postmeta_value){
							$order_items[$key]->{$postmeta_key}	= $postmeta_value;
						}
						
						$order_items[$key]->refund_order_total 			= isset($order_items[$key]->refund_order_total)		? $order_items[$key]->refund_order_total 		: 0;						
						
						$order_items[$key]->net_order_total 			= $order_items[$key]->order_total 			+ $order_items[$key]->refund_order_total;
						$order_items[$key]->total_tax_refund 			= $order_items[$key]->refund_tax_amount		+ $order_items[$key]->refund_shipping_tax_amount;
						
						$order_items[$key]->total_tax 					= $order_items[$key]->tax_amount			+ $order_items[$key]->shipping_tax_amount;
						$order_items[$key]->net_total_tax 				= $order_items[$key]->total_tax 			- $order_items[$key]->total_tax_refund;
				}
				
				
				
				$lists = array();
				foreach ( $order_items as $key => $order_item ) {
					$group_column = $order_item->group_column;
					
					if(isset($lists[$group_column])){						
						$lists[$group_column]->order_total 					= $lists[$group_column]->order_total 			+ $order_items[$key]->order_total;
						$lists[$group_column]->order_shipping 				= $lists[$group_column]->order_shipping 		+ $order_items[$key]->order_shipping;
						$lists[$group_column]->cart_discount 				= $lists[$group_column]->cart_discount 			+ $order_items[$key]->cart_discount;
						$lists[$group_column]->order_discount 				= $lists[$group_column]->order_discount 		+ $order_items[$key]->order_discount;
						$lists[$group_column]->total_discount 				= $lists[$group_column]->total_discount 		+ $order_items[$key]->total_discount;
						$lists[$group_column]->order_tax 					= $lists[$group_column]->order_tax 				+ $order_items[$key]->order_tax;
						$lists[$group_column]->order_shipping_tax 			= $lists[$group_column]->order_shipping_tax 	+ $order_items[$key]->order_shipping_tax;						
						$lists[$group_column]->total_tax 					= $lists[$group_column]->total_tax 				+ $order_items[$key]->total_tax;
						$lists[$group_column]->gross_amount 				= $lists[$group_column]->gross_amount 			+ $order_items[$key]->gross_amount;
						
						$lists[$group_column]->order_tax 					= $lists[$group_column]->order_tax 						+ $order_items[$key]->order_tax;
						
						$lists[$group_column]->tax_amount 					= $lists[$group_column]->tax_amount 					+ $order_items[$key]->tax_amount;
						$lists[$group_column]->shipping_tax_amount 			= $lists[$group_column]->shipping_tax_amount 			+ $order_items[$key]->shipping_tax_amount;
						
						
						$lists[$group_column]->refund_tax_amount 			= $lists[$group_column]->refund_tax_amount 				+ $order_items[$key]->refund_tax_amount;
						$lists[$group_column]->refund_shipping_tax_amount 	= $lists[$group_column]->refund_shipping_tax_amount 	+ $order_items[$key]->refund_shipping_tax_amount;
						$lists[$group_column]->total_tax_refund 			= $lists[$group_column]->total_tax_refund 				+ $order_items[$key]->total_tax_refund;
						
						$lists[$group_column]->order_count 					= $lists[$group_column]->order_count 					+ 1;
						
						$lists[$group_column]->refund_order_total 			= $lists[$group_column]->refund_order_total 			+ $order_items[$key]->refund_order_total;
						
						$lists[$group_column]->net_order_total 				= $lists[$group_column]->net_order_total 				+ $order_items[$key]->net_order_total;
						
						$lists[$group_column]->net_total_tax 				= $lists[$group_column]->net_total_tax 					+ $order_items[$key]->net_total_tax;
						
					}else{
						
						$lists[$group_column] = new stdClass();
						
						$lists[$group_column]->order_count 					= 1;
						$lists[$group_column]->order_total 					= $order_items[$key]->order_total;
						$lists[$group_column]->order_shipping 				= $order_items[$key]->order_shipping;
						$lists[$group_column]->cart_discount 				= $order_items[$key]->cart_discount;
						$lists[$group_column]->order_discount 				= $order_items[$key]->order_discount;
						$lists[$group_column]->total_discount 				= $order_items[$key]->total_discount;
						$lists[$group_column]->order_tax 					= $order_items[$key]->order_tax;
						$lists[$group_column]->order_shipping_tax 			= $order_items[$key]->order_shipping_tax;						
						$lists[$group_column]->total_tax 					= $order_items[$key]->total_tax;
						$lists[$group_column]->gross_amount 				= $order_items[$key]->gross_amount;
						
						$lists[$group_column]->order_tax 					= $order_items[$key]->order_tax;
						$lists[$group_column]->tax_amount 					= $order_items[$key]->tax_amount;
						$lists[$group_column]->shipping_tax_amount 			= $order_items[$key]->shipping_tax_amount;
						$lists[$group_column]->total_tax 					= $order_items[$key]->total_tax;
						
						$lists[$group_column]->refund_tax_amount 			= $order_items[$key]->refund_tax_amount;
						$lists[$group_column]->refund_shipping_tax_amount 	= $order_items[$key]->refund_shipping_tax_amount;
						$lists[$group_column]->total_tax_refund 			= $order_items[$key]->total_tax_refund;
						
						
												
						$lists[$group_column]->tax_rate_code 				= isset($order_items[$key]->tax_rate_code) 		? $order_items[$key]->tax_rate_code 	: '';
						$lists[$group_column]->tax_rate_name 				= isset($order_items[$key]->tax_rate_name) 		? $order_items[$key]->tax_rate_name 	: '';
						$lists[$group_column]->order_tax_rate 				= isset($order_items[$key]->order_tax_rate) 	? $order_items[$key]->order_tax_rate 	: '';
						$lists[$group_column]->billing_country 				= isset($order_items[$key]->billing_country) 	? $order_items[$key]->billing_country 	: '';
						$lists[$group_column]->billing_state 				= isset($order_items[$key]->billing_state) 		? $order_items[$key]->billing_state 	: '';
						$lists[$group_column]->billing_postcode 			= isset($order_items[$key]->billing_postcode) 	? $order_items[$key]->billing_postcode 	: '';
						$lists[$group_column]->tax_city 					= isset($order_items[$key]->tax_city) 			? $order_items[$key]->tax_city 			: '';
						
						$lists[$group_column]->group_column 				= $group_column;						
						$lists[$group_column]->refund_order_total 			= $order_items[$key]->refund_order_total;
						$lists[$group_column]->net_order_total 				= $order_items[$key]->net_order_total;
						$lists[$group_column]->net_total_tax 				= $order_items[$key]->net_total_tax;
						
					}
				}
				$order_items = $lists;
				
				foreach ( $order_items as $key => $order_item ) {
					$order_items[$key]->refund_tax_amount 			= -$order_items[$key]->refund_tax_amount;
					$order_items[$key]->refund_shipping_tax_amount 	= -$order_items[$key]->refund_shipping_tax_amount;
					$order_items[$key]->total_tax_refund 			= -$order_items[$key]->total_tax_refund;
				}
			}
			
			//$this->print_array($order_items);
			
			if($wpdb->last_error){
				echo $wpdb->last_error;
			}
			
			return $order_items;
		}
		
		function get_tax_refund($type = 'limit_row'){
			global $wpdb;
			$request		= $this->get_all_request();extract($request);
			
			$order_status	= $this->get_string_multi_request('order_status',$order_status, "-1");
			
			$sql = "  SELECT
			SUM(r_woocommerce_order_itemmeta_tax_amount.meta_value)  			AS refund_tax_amount,
			
			SUM(r_woocommerce_order_itemmeta_shipping_tax_amount.meta_value)  	AS refund_shipping_tax_amount,
			
			shop_order_refund.post_parent										AS order_id,
			
			r_woocommerce_order_items.order_item_id								AS order_item_id,
			
			shop_order_refund.ID												AS refund_order_id,
			
			r_woocommerce_order_items.order_item_name							AS order_item_name,
			
			CONCAT(shop_order_refund.post_parent,'-',r_woocommerce_order_items.order_item_name)	AS refund_group_column
			";
			
			$sql .= " FROM {$wpdb->prefix}woocommerce_order_items as r_woocommerce_order_items";
			
			$sql .= " LEFT JOIN  {$wpdb->prefix}posts as shop_order_refund ON 	shop_order_refund.ID=	r_woocommerce_order_items.order_id";
			
			$sql .= " LEFT JOIN  {$wpdb->prefix}posts as posts ON posts.ID=	shop_order_refund.post_parent";
			
			$sql .= " LEFT JOIN  {$wpdb->prefix}woocommerce_order_itemmeta as r_woocommerce_order_itemmeta_tax_amount ON r_woocommerce_order_itemmeta_tax_amount.order_item_id=r_woocommerce_order_items.order_item_id";
			
			$sql .= " LEFT JOIN  {$wpdb->prefix}woocommerce_order_itemmeta as r_woocommerce_order_itemmeta_shipping_tax_amount ON r_woocommerce_order_itemmeta_shipping_tax_amount.order_item_id=r_woocommerce_order_items.order_item_id";
			
			$sql .= " WHERE 1*1";
			
			$sql .= " AND shop_order_refund.post_type='shop_order_refund' ";
			
			$sql .= " AND r_woocommerce_order_items.order_item_type='tax' ";
			
			$sql .= " AND r_woocommerce_order_itemmeta_tax_amount.meta_key='tax_amount' ";
			
			$sql .= " AND r_woocommerce_order_itemmeta_shipping_tax_amount.meta_key='shipping_tax_amount' ";
			
			if($order_status  && $order_status != '-1' and $order_status != "'-1'")$sql .= " AND posts.post_status IN (".$order_status.")";//New Change ID 20140918
			
			if ($start_date != NULL &&  $end_date !=NULL){
				$sql .= " AND (DATE(posts.post_date) BETWEEN '".$start_date."' AND '". $end_date ."')";
			}
			
			$sql .= "  GROUP BY refund_group_column";
			
			$sql .= "  ORDER BY refund_group_column ASC";
			
			$wpdb->flush();
			
			$wpdb->query("SET SQL_BIG_SELECTS=1");
			
			$order_items = $wpdb->get_results($sql);
			
			$return = array();
			
			if($wpdb->last_error){
				echo $wpdb->last_error;
			}
			
			foreach($order_items as $key => $order_item){
				$return[$order_item->refund_group_column] = $order_item;
			}
			
			return $return;
		}
		
		function get_shop_order_refund_id($type = 'limit_row',$post_ids = '',$extra_key = array('order_total')){
			global $wpdb;
			$request		= $this->get_all_request();extract($request);
			
			$order_status	= $this->get_string_multi_request('order_status',$order_status, "-1");
			
			$sql = "  SELECT
				shop_order_refund.post_parent AS order_id,
				shop_order_refund.ID AS shop_order_refund_id
			";
			
			$sql .= " FROM {$wpdb->prefix}posts as shop_order_refund";
			
			$sql .= " WHERE 1*1" ;
			
			$sql .= " AND shop_order_refund.post_type='shop_order_refund' ";
			
			$sql .= " AND shop_order_refund.post_parent IN ($post_ids) ";
			
			$wpdb->flush();
			
			$wpdb->query("SET SQL_BIG_SELECTS=1");
			
			$order_items = $wpdb->get_results($sql);
			
			
			
			$return = array();
			
			if($wpdb->last_error){
				echo $wpdb->last_error;
			}
			
			
			
			$shop_order_refund_ids 			= $this->get_items_id_list($order_items,'shop_order_refund_id');
			$refund_postmeta_datas 			= $this->get_postmeta($shop_order_refund_ids, array(),$extra_key,'no');
			$meta_keies						= array();
			
			foreach ( $order_items as $key => $order_item ) {
				$shop_order_refund_id = $order_item->shop_order_refund_id;
				$postmeta_data 	= isset($refund_postmeta_datas[$shop_order_refund_id]) ? $refund_postmeta_datas[$shop_order_refund_id] : array();
				foreach($postmeta_data as $postmeta_key => $postmeta_value){
					$order_items[$key]->{'refund_'.$postmeta_key}	= $postmeta_value;
					$meta_keies[$postmeta_key] = 'refund_'.$postmeta_key;
				}
			}
			
			$return = array();
			foreach ( $order_items as $key => $order_item ) {
				$order_id = $order_item->order_id;
				
				if(isset($return[$order_id])){
					foreach($meta_keies as $meta_key => $refund_meta_key){
						
						$v1 = isset($return[$order_id]->$refund_meta_key) ? $return[$order_id]->$refund_meta_key : 0;
						$v2 = isset($order_item->$refund_meta_key) ? $order_item->$refund_meta_key : 0;
						
						
						$return[$order_id]->$refund_meta_key = $v1 + $v2;
						
					}
				}else{
					$return[$order_id] = $order_item;
				}
			}
			
			
			
			return $return;
		}
		
		function get_tax_items_query($type = 'limit_row'){	
			global $wpdb;
			$order_items = array();
			
			$request		= $this->get_all_request();extract($request);
			
			if(
				$tax_group_by == "tax_group_by_city" 
				|| $tax_group_by == "tax_group_by_state"
				|| $tax_group_by == "tax_group_by_country"
				|| $tax_group_by == "tax_group_by_zip"
				|| $tax_group_by == "tax_group_by_tax_name"
				|| $tax_group_by == "tax_group_by_tax_summary"
				|| (!empty($country_code) and $country_code != "-1")
			){
				return $this->get_tax_items_query_order($type);
			}
			$order_status	= $this->get_string_multi_request('order_status',$order_status, "-1");
			
			if(
				$tax_group_by == "tax_group_by_city_summary"
				|| $tax_group_by == "tax_group_by_state_summary"
				|| $tax_group_by == "tax_group_by_country_summary"){
				
			}
			
			$country_code	= $this->get_string_multi_request('country_code',$country_code, "-1");
			$state_code		= $this->get_string_multi_request('state_code',$state_code, "-1");
			$order_status	= $this->get_string_multi_request('order_status',$order_status, "-1");
			$hide_order_status	= $this->get_string_multi_request('hide_order_status',$hide_order_status, "-1");//New Change ID 20140918
			
			$join_country = false;
			
			if(
				$tax_group_by == "tax_group_by_city" 
				|| $tax_group_by == "tax_group_by_state"
				|| $tax_group_by == "tax_group_by_country"
				|| $tax_group_by == "tax_group_by_zip"
				|| $tax_group_by == "tax_group_by_tax_name"
				|| $tax_group_by == "tax_group_by_city_summary"
				|| $tax_group_by == "tax_group_by_state_summary"
				|| $tax_group_by == "tax_group_by_country_summary"
				|| (!empty($country_code) and $country_code != "-1")
			){
				$join_country = true;
			}
				
			$sql = "  SELECT posts.ID AS order_id";
			
			$sql .= ", order_tax.meta_value AS tax_amount";
			
			$sql .= ", order_shipping_tax.meta_value AS shipping_tax_amount";
			
			if($join_country){
				$sql .= ", postmeta4.meta_value 								AS billing_country";
			}
			
			if($tax_group_by == "tax_group_by_state" || $tax_group_by == "tax_group_by_state_summary"){
				$sql .= ", postmeta3.meta_value 								AS billing_state";
			}
			
			if($tax_group_by == "tax_group_by_city" || $tax_group_by == "tax_group_by_city_summary"){
				$sql .= ", postmeta5.meta_value 								AS tax_city";
				$sql .= ", postmeta3.meta_value 								AS billing_state";
			}
			
			if($tax_group_by == "tax_group_by_zip"){
				$sql .= ", postmeta6.meta_value 								AS billing_postcode";
			}
			
			switch($tax_group_by){
				case "tax_group_by_zip":
					$group_sql = ", CONCAT(postmeta4.meta_value,'-',postmeta6.meta_value,'-',lpad(woocommerce_tax_rates.tax_rate,3,'0'),'-',woocommerce_order_items.order_item_name,'-',woocommerce_tax_rates.tax_rate_name,'-',woocommerce_tax_rates.tax_rate) as group_column";
					break;
				case "tax_group_by_city":
					$group_sql = ", CONCAT(postmeta4.meta_value,'-',postmeta5.meta_value,'-',lpad(woocommerce_tax_rates.tax_rate,3,'0'),'-',woocommerce_order_items.order_item_name,'-',woocommerce_tax_rates.tax_rate_name,'-',woocommerce_tax_rates.tax_rate) as group_column";
					break;
				case "tax_group_by_state":
					$group_sql = ", CONCAT(postmeta4.meta_value,'-',postmeta3.meta_value,'-',lpad(woocommerce_tax_rates.tax_rate,3,'0'),'-',woocommerce_order_items.order_item_name,'-',woocommerce_tax_rates.tax_rate_name,'-',woocommerce_tax_rates.tax_rate) as group_column";
					break;
				case "tax_group_by_country":
					$group_sql = ", CONCAT(postmeta4.meta_value,'-',lpad(woocommerce_tax_rates.tax_rate,3,'0'),'-',woocommerce_order_items.order_item_name,'-',woocommerce_tax_rates.tax_rate_name,'-',woocommerce_tax_rates.tax_rate) as group_column";
					break;
				case "tax_group_by_tax_name":
					$group_sql = ", CONCAT(woocommerce_tax_rates.tax_rate_name,'-',lpad(woocommerce_tax_rates.tax_rate,3,'0'),'-',woocommerce_tax_rates.tax_rate_name,'-',woocommerce_tax_rates.tax_rate,'-',postmeta4.meta_value) as group_column";
					break;
				case "tax_group_by_tax_summary":
					$group_sql = ", CONCAT(woocommerce_tax_rates.tax_rate_name,'-',lpad(woocommerce_tax_rates.tax_rate,3,'0'),'-',woocommerce_order_items.order_item_name) as group_column";
					break;
				case "tax_group_by_city_summary":
					$group_sql = ", CONCAT(postmeta4.meta_value,'',postmeta5.meta_value) as group_column";
					break;
				case "tax_group_by_state_summary":
					$group_sql = ", CONCAT(postmeta4.meta_value,'',postmeta3.meta_value) as group_column";
					break;
				case "tax_group_by_country_summary":
					$group_sql = ", CONCAT(postmeta4.meta_value) as group_column";
					break;
				default:
					$group_sql = ", CONCAT(woocommerce_order_items.order_item_name,'-',woocommerce_tax_rates.tax_rate_name,'-',woocommerce_tax_rates.tax_rate,'-',postmeta4.meta_value,'-',postmeta3.meta_value) as group_column";
					break;
				
			}
			
			$sql .= $group_sql;	
			
			$sql .= " FROM {$wpdb->prefix}posts as posts";
			
			$sql .= " LEFT JOIN  {$wpdb->prefix}postmeta as order_tax ON order_tax.post_id=posts.ID";
			
			$sql .= " LEFT JOIN  {$wpdb->prefix}postmeta as order_shipping_tax ON order_shipping_tax.post_id=posts.ID";
			
			
			
			if(($order_status_id  && $order_status_id != '-1') || $sort_by == "status"){
				$sql .= " 
				LEFT JOIN  {$wpdb->prefix}term_relationships 	as term_relationships 	ON term_relationships.object_id		=	posts.ID
				
				LEFT JOIN  {$wpdb->prefix}term_taxonomy 		as term_taxonomy 		ON term_taxonomy.term_taxonomy_id	=	term_relationships.term_taxonomy_id";
				
				if($sort_by == "status"){
					$sql .= " LEFT JOIN  {$wpdb->prefix}terms 				as terms 				ON terms.term_id					=	term_taxonomy.term_id";
				}
			}
			
			if($join_country){
				$sql .= " LEFT JOIN  {$wpdb->prefix}postmeta as postmeta4 ON postmeta4.post_id=posts.ID";
			}
			
			if(
				$tax_group_by == "tax_group_by_state" 
				|| $tax_group_by == "tax_group_by_state_summary"
				|| $tax_group_by == "tax_group_by_city_summary"
				|| $tax_group_by == "tax_group_by_city"
				|| (!empty($state_code) and $state_code != "-1"
			)){
				$sql .= " LEFT JOIN  {$wpdb->prefix}postmeta as postmeta3 ON postmeta3.post_id=posts.ID";
			}
			
			if($tax_group_by == "tax_group_by_city" || $tax_group_by == "tax_group_by_city_summary"){
				$sql .= " LEFT JOIN  {$wpdb->prefix}postmeta as postmeta5 ON postmeta5.post_id=posts.ID";
			}
			
			if($tax_group_by == "tax_group_by_zip"){
				$sql .= " LEFT JOIN  {$wpdb->prefix}postmeta as postmeta6 ON postmeta6.post_id=posts.ID";
			}
			
			$sql .= " WHERE 1*1 ";
			
			$sql .= " AND posts.post_type='shop_order' ";
			
			$sql .= " AND order_tax.meta_key='_order_tax' ";
			
			$sql .= " AND order_shipping_tax.meta_key='_order_shipping_tax' ";
			
			if($tax_based_on == "billing"){
				
				if($join_country){
					$sql .= " AND postmeta4.meta_key='_billing_country'";
				}
				
				if($tax_group_by == "tax_group_by_state" || $tax_group_by == "tax_group_by_state_summary"|| $tax_group_by == "tax_group_by_city_summary"
				|| $tax_group_by == "tax_group_by_city" || (!empty($state_code) and $state_code != "-1")){
					$sql .= " AND postmeta3.meta_key='_billing_state'";
				}
				
				if($tax_group_by == "tax_group_by_city" || $tax_group_by == "tax_group_by_city_summary"){
					$sql .= " AND postmeta5.meta_key='_billing_city'";
				}
				if($tax_group_by == "tax_group_by_zip"){
					$sql .= " AND postmeta6.meta_key='_billing_postcode'";
				}
			}else{
				if($join_country){
					$sql .= " AND postmeta4.meta_key='_shipping_country'";
				}
				if($tax_group_by == "tax_group_by_state" || $tax_group_by == "tax_group_by_state_summary"|| $tax_group_by == "tax_group_by_city_summary"
				|| $tax_group_by == "tax_group_by_city" || (!empty($state_code) and $state_code != "-1")){
					$sql .= " AND postmeta3.meta_key='_shipping_state'";
				}
				
				if($tax_group_by == "tax_group_by_city" || $tax_group_by == "tax_group_by_city_summary"){
					$sql .= " AND postmeta5.meta_key='_shipping_city'";
				}
				if($tax_group_by == "tax_group_by_zip"){
					$sql .= " AND postmeta6.meta_key='_shipping_postcode'";
				}
			}
			
			if($order_status_id  && $order_status_id != '-1') $sql .= " AND term_taxonomy.term_id IN (".$order_status_id .")";
			
			if($state_code and $state_code != '-1')	$sql .= " AND postmeta3.meta_value IN (".$state_code.")";
			
			if($country_code and $country_code != '-1')	$sql .= " AND postmeta4.meta_value IN (".$country_code.")";
			
			if($order_status  && $order_status != '-1' and $order_status != "'-1'")$sql .= " AND posts.post_status IN (".$order_status.")";//New Change ID 20140918
			
			if($hide_order_status  && $hide_order_status != '-1' and $hide_order_status != "'-1'")$sql .= " AND posts.post_status NOT IN (".$hide_order_status.")";//New Change ID 20140918
			
			if ($start_date != NULL &&  $end_date !=NULL){
				$sql .= " AND (DATE(posts.post_date) BETWEEN '".$start_date."' AND '". $end_date ."')";
			}
			
			$sql .= "  GROUP BY order_id ";
			
			$sql .= "  ORDER BY order_id ASC";
			
			$wpdb->flush();
			
			$wpdb->query("SET SQL_BIG_SELECTS=1");
			
			$order_items 		= $wpdb->get_results($sql);
			
			if(count($order_items)>0){
				
				$post_ids 			= $this->get_items_id_list($order_items,'order_id');
				$tax_refund_items 	= $this->get_tax_refund($type);
				$extra_key 			= array('order_total','order_tax','order_shipping_tax','order_shipping','refund_amount');
				$shop_order_refund_id_items 	= $this->get_shop_order_refund_id($type,$post_ids,$extra_key);
				
				$extra_meta_keys 	= apply_filters('ic_commerce_tax_report_page_extra_meta_keys', array('order_total','order_shipping','cart_discount','order_discount','order_tax','order_shipping_tax'));
				
				$postmeta_datas 	= $this->get_postmeta($post_ids, array(),$extra_meta_keys,'total');
				
				$order_ids = array();
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
						$order_items[$key]->total_discount 		= ($order_items[$key]->cart_discount 			+ $order_items[$key]->order_discount);
						
						$order_items[$key]->order_tax 			= isset($order_items[$key]->order_tax)			? $order_items[$key]->order_tax : 0;
						$order_items[$key]->order_shipping_tax 	= isset($order_items[$key]->order_shipping_tax)	? $order_items[$key]->order_shipping_tax : 0;
						
						
						$order_items[$key]->gross_amount 		= ($order_items[$key]->order_total + $order_items[$key]->total_discount) - ($order_items[$key]->order_shipping +  $order_items[$key]->order_shipping_tax + $order_items[$key]->order_tax );
						$order_ids[$order_id] = $order_id;
						
						
						$refund_postmeta_data 	= isset($shop_order_refund_id_items[$order_id]) ? $shop_order_refund_id_items[$order_id] : array();
						
						foreach($refund_postmeta_data as $postmeta_key => $postmeta_value){
							$order_items[$key]->{$postmeta_key}	= $postmeta_value;
						}
						
						$order_items[$key]->refund_tax_amount			= isset($order_item->refund_order_tax)				? $order_item->refund_order_tax 				: 0;
						$order_items[$key]->refund_shipping_tax_amount	= isset($order_item->refund_order_shipping_tax)		? $order_item->refund_order_shipping_tax 		: 0;						
						$order_items[$key]->refund_order_total 			= isset($order_items[$key]->refund_order_total)		? $order_items[$key]->refund_order_total 		: 0;
						
						$order_items[$key]->refund_tax_amount			= str_replace('-','',$order_items[$key]->refund_tax_amount);
						$order_items[$key]->refund_shipping_tax_amount	= str_replace('-','',$order_items[$key]->refund_shipping_tax_amount);						
						
						$order_items[$key]->net_order_total 			= $order_items[$key]->order_total 			+ $order_items[$key]->refund_order_total;
						$order_items[$key]->total_tax_refund 			= $order_items[$key]->refund_tax_amount 	+ $order_items[$key]->refund_shipping_tax_amount;
						
						
						$order_items[$key]->total_tax 					= $order_items[$key]->tax_amount			+ $order_items[$key]->shipping_tax_amount;
						$order_items[$key]->net_total_tax 				= $order_items[$key]->total_tax 			- $order_items[$key]->total_tax_refund;
				}
				
				$lists = array();
				foreach ( $order_items as $key => $order_item ) {
					$group_column = $order_item->group_column;
					
					if(isset($lists[$group_column])){						
						$lists[$group_column]->order_total 					= $lists[$group_column]->order_total 					+ $order_items[$key]->order_total;
						$lists[$group_column]->order_shipping 				= $lists[$group_column]->order_shipping 				+ $order_items[$key]->order_shipping;
						$lists[$group_column]->cart_discount 				= $lists[$group_column]->cart_discount 					+ $order_items[$key]->cart_discount;
						$lists[$group_column]->order_discount 				= $lists[$group_column]->order_discount 				+ $order_items[$key]->order_discount;
						$lists[$group_column]->total_discount 				= $lists[$group_column]->total_discount 				+ $order_items[$key]->total_discount;
						$lists[$group_column]->order_tax 					= $lists[$group_column]->order_tax 						+ $order_items[$key]->order_tax;
						$lists[$group_column]->order_shipping_tax 			= $lists[$group_column]->order_shipping_tax 			+ $order_items[$key]->order_shipping_tax;						
						$lists[$group_column]->total_tax 					= $lists[$group_column]->total_tax 						+ $order_items[$key]->total_tax;
						$lists[$group_column]->gross_amount 				= $lists[$group_column]->gross_amount 					+ $order_items[$key]->gross_amount;
						
						$lists[$group_column]->order_tax 					= $lists[$group_column]->order_tax 						+ $order_items[$key]->order_tax;
						
						$lists[$group_column]->tax_amount 					= $lists[$group_column]->tax_amount 					+ $order_items[$key]->tax_amount;
						$lists[$group_column]->shipping_tax_amount 			= $lists[$group_column]->shipping_tax_amount 			+ $order_items[$key]->shipping_tax_amount;
						
						
						$lists[$group_column]->refund_tax_amount 			= $lists[$group_column]->refund_tax_amount 				+ $order_items[$key]->refund_tax_amount;
						$lists[$group_column]->refund_shipping_tax_amount 	= $lists[$group_column]->refund_shipping_tax_amount 	+ $order_items[$key]->refund_shipping_tax_amount;
						$lists[$group_column]->total_tax_refund 			= $lists[$group_column]->total_tax_refund 				+ $order_items[$key]->total_tax_refund;
						
						$lists[$group_column]->order_count 					= $lists[$group_column]->order_count 					+ 1;
						
						$lists[$group_column]->refund_order_total 			= $lists[$group_column]->refund_order_total 			+ $order_items[$key]->refund_order_total;
						
						$lists[$group_column]->net_order_total 				= $lists[$group_column]->net_order_total 				+ $order_items[$key]->net_order_total;
						
						$lists[$group_column]->net_total_tax 				= $lists[$group_column]->net_total_tax 					+ $order_items[$key]->net_total_tax;
						
						//if($group_column == 'IN'){
							/*echo "******************************<br>";
							echo "<br>";
							echo $order_items[$key]->tax_amount;
							echo '+';
							echo $order_items[$key]->shipping_tax_amount;
							echo '=';
							echo $order_items[$key]->tax_amount + $order_items[$key]->shipping_tax_amount;
							
							echo "<br>Total";
							echo $lists[$group_column]->total_tax;
							echo "<br>";*/
							/*
							echo "***<br>";
							echo "***{$order_id}<br>";
							echo "***{$group_column}<br>";
							echo $order_items[$key]->tax_amount;
							echo "***<br>";
							*/
						//}
						
						
					}else{
						$lists[$group_column] = new stdClass();
						
						$lists[$group_column]->order_count 					= 1;
						$lists[$group_column]->order_total 					= $order_items[$key]->order_total;
						$lists[$group_column]->order_shipping 				= $order_items[$key]->order_shipping;
						$lists[$group_column]->cart_discount 				= $order_items[$key]->cart_discount;
						$lists[$group_column]->order_discount 				= $order_items[$key]->order_discount;
						$lists[$group_column]->total_discount 				= $order_items[$key]->total_discount;
						$lists[$group_column]->order_tax 					= $order_items[$key]->order_tax;
						$lists[$group_column]->order_shipping_tax 			= $order_items[$key]->order_shipping_tax;						
						$lists[$group_column]->total_tax 					= $order_items[$key]->total_tax;
						$lists[$group_column]->gross_amount 				= $order_items[$key]->gross_amount;
						
						$lists[$group_column]->order_tax 					= $order_items[$key]->order_tax;
						$lists[$group_column]->tax_amount 					= $order_items[$key]->tax_amount;
						
						$lists[$group_column]->shipping_tax_amount 			= $order_items[$key]->shipping_tax_amount;
						$lists[$group_column]->total_tax 					= $order_items[$key]->total_tax;
						
						$lists[$group_column]->refund_tax_amount 			= $order_items[$key]->refund_tax_amount;
						$lists[$group_column]->refund_shipping_tax_amount 	= $order_items[$key]->refund_shipping_tax_amount;
						$lists[$group_column]->total_tax_refund 			= $order_items[$key]->total_tax_refund;
						
						
												
						$lists[$group_column]->tax_rate_code 				= isset($order_items[$key]->tax_rate_code) 		? $order_items[$key]->tax_rate_code 	: '';
						$lists[$group_column]->tax_rate_name 				= isset($order_items[$key]->tax_rate_name) 		? $order_items[$key]->tax_rate_name 	: '';
						$lists[$group_column]->order_tax_rate 				= isset($order_items[$key]->order_tax_rate) 	? $order_items[$key]->order_tax_rate 	: '';
						$lists[$group_column]->billing_country 				= isset($order_items[$key]->billing_country) 	? $order_items[$key]->billing_country 	: '';
						$lists[$group_column]->billing_state 				= isset($order_items[$key]->billing_state) 		? $order_items[$key]->billing_state 	: '';
						$lists[$group_column]->billing_postcode 			= isset($order_items[$key]->billing_postcode) 	? $order_items[$key]->billing_postcode 	: '';
						$lists[$group_column]->tax_city 					= isset($order_items[$key]->tax_city) 			? $order_items[$key]->tax_city 			: '';
						
						$lists[$group_column]->group_column 				= $group_column;
						
						$lists[$group_column]->refund_order_total 			= $order_items[$key]->refund_order_total;
						$lists[$group_column]->net_order_total 				= $order_items[$key]->net_order_total;
						$lists[$group_column]->net_total_tax 				= $order_items[$key]->net_total_tax;
						
						//if($group_column == 'IN'){
							/*echo "<br>";
							echo $order_items[$key]->tax_amount;
							echo '+';
							echo $order_items[$key]->shipping_tax_amount;
							echo '=';
							echo $order_items[$key]->tax_amount + $order_items[$key]->shipping_tax_amount;
							
							echo "<br>Total";
							echo $lists[$group_column]->total_tax;
							echo "<br>";*/
							/*
							echo "***<br>";
							echo "***{$order_id}<br>";
							echo "***{$group_column}<br>";
							echo $order_items[$key]->tax_amount;
							echo "***<br>";
							*/
						//}
						
					}
				}
				
				$order_items = $lists;
				
				foreach ( $order_items as $key => $order_item ) {
					$order_items[$key]->refund_tax_amount 			= -$order_items[$key]->refund_tax_amount;
					$order_items[$key]->refund_shipping_tax_amount 	= -$order_items[$key]->refund_shipping_tax_amount;
					$order_items[$key]->total_tax_refund 			= -$order_items[$key]->total_tax_refund;
				}
			}
			
			return $order_items;
		}
		
		function get_paying_state($state_key = 'billing_state',$country_key = false, $deliter = "-"){
			global $wpdb;
			
			$hide_order_status	= $this->get_request('hide_order_status');//New Change ID 20150127
			$hide_order_status	= $this->get_string_multi_request('hide_order_status',$hide_order_status, "-1");//New Change ID 20150127
			
			
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
				
				
			if($hide_order_status  && $hide_order_status != '-1' and $hide_order_status != "'-1'")$sql .= " AND posts.post_status NOT IN (".$hide_order_status.")";//New Change ID 20150127	
			
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
		
		function get_all_request(){
			global $request;
			if(!$this->request){
				$request 			= array();
				$start				= 0;

				$limit 				= $this->get_request('limit',15,true);
				$p 					= $this->get_request('p',1,true);
			
				$page 				= $this->get_request('page',NULL,true);
				$order_id 			= $this->get_request('order_id',NULL,true);
				$start_date 		= $this->get_request('start_date',NULL,true);
				$end_date 			= $this->get_request('end_date',NULL,true);
				
				$sort_by 			= $this->get_request('sort_by','order_id',true);
				$order_by 			= $this->get_request('order_by','DESC',true);
				
				$country_code 		= $this->get_request('country_code','-1',true);
				$state_code 		= $this->get_request('state_code','-1',true);
				$order_status 		= $this->get_request('order_status','-1',true);
				$order_status_id 	= $this->get_request('order_status_id','-1',true);
				///
				
				$this->common_request_form();
				
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
		
		function get_grid_items_tax_group_by_state($order_items, $tax_group_by_key = 'billing_state'){
			$last_state 	= "";
			$row_count 		= 0;
			$output 		= '';
			$i 				= 0;//New
			$new_rows		= array();//New
			
			$request		= $this->get_all_request();extract($request);
			$columns = $this->get_column($tax_group_by);
			
			$total_row = array("_shipping_tax_amount" => 0,"tax_amount" => 0,"total_tax" => 0);
			
			$country    = $this->get_wc_countries();//Added 20150225
			$td_id = 0;
			foreach ( $order_items as $key => $order_item ) {
				//$order_item->_total_tax = $order_item->_shipping_tax_amount + $order_item->_order_tax;
				//$order_item->_order_amount = $order_item->_order_tax > 0 ? ($order_item->_order_tax*100)/$order_item->order_tax_rate : 0;	
				//$order_item->_order_amount = $this->get_percentage($order_item->_order_tax,$order_item->order_tax_rate);//Added 20150206			
				$order_item->tax_rate_name = isset($order_item->tax_rate_name) ? trim($order_item->tax_rate_name) : '';
				$order_item->tax_rate_name = strlen($order_item->tax_rate_name)<=0 ? $order_item->tax_rate_code : $order_item->tax_rate_name;				
				$order_item->billing_state = isset($order_item->billing_state) ? $order_item->billing_state : '';
				
				if($last_state != $order_item->$tax_group_by_key){
					if($td_id != 0){
						$alternate = "total_row ";
						$output .= '<tr class="'.$alternate."row_".$key.'">';
						foreach($columns as $key => $value):
							$td_class = $key;                                            
							$td_value = "";
							switch($key):                                            
								case "tax_amount":
								case "shipping_tax_amount":
								case "total_tax":
								
								case "refund_tax_amount":
								case "refund_shipping_tax_amount":
								case "total_tax_refund":
								case "net_total_tax":
									$td_value = isset($total_row[$key]) ? $total_row[$key] : 0;
									break;
								default:
									$td_value = '';
									break;
							endswitch;
							$new_rows[$i][$key] = $td_value;//New
						endforeach; 
						$i++;
						$output .= '</tr>';
						$row_count = 0;
						$total_row = array();
					}
					$alternate = "";
					$output .= '<tr class="'.$alternate."row_".$key.'">';
						foreach($columns as $key => $value):
							$td_class = $key;                                            
							$td_value = "";
							switch($key):                                            
								case "billing_state":
									$billing_state = isset($order_item->$key) ? $order_item->$key : '';
									$billing_country = isset($order_item->billing_country) ? $order_item->billing_country : '';
									$td_value = $this->get_billling_state_name($billing_country, $billing_state);                                                
									break;								
								case "billing_country":
									$billing_country = isset($order_item->$key) ? $order_item->$key : '';
									$billing_country = isset($country->countries[$billing_country]) ? $country->countries[$billing_country]: $billing_country;
									$td_value = $billing_country;
									break;
								case "tax_city":
								case "billing_postcode":
									$td_value = isset($order_item->$key) ? $order_item->$key : '';
									break;
								default:
									$td_value = '';
									break;
							endswitch;
							$new_rows[$i][$key] = $td_value;//New
						endforeach;
						$i++;//New
						$row_count = 0;
					$output .= '</tr>';
				}
				
				//$total_row['_shipping_tax_amount'] = isset($total_row['_shipping_tax_amount']) ? ($total_row['_shipping_tax_amount'] + $order_item->_shipping_tax_amount) : $order_item->_shipping_tax_amount;
				//$total_row['_order_tax'] = isset($total_row['_order_tax']) ? ($total_row['_order_tax'] + $order_item->_order_tax) : $order_item->_order_tax;
				//$total_row['_total_tax'] = isset($total_row['_total_tax']) ? ($total_row['_total_tax'] + $order_item->_total_tax) : $order_item->_total_tax;
				
				$total_row = $this->get_order_total($total_row,$order_item);
				
				
				if($row_count%2 == 0){$alternate = "alternate ";}else{$alternate = "";};
				$output .= '<tr class="'.$alternate."row_".$key.'">';
						foreach($columns as $key => $value):
							$td_class = $key;                                            
							$td_value = "";
							switch($key):
								case "billing_state":
								case "billing_country":									
								case "tax_city":
								case "billing_postcode":
									$td_value = '';
									break;
								case "_order_count":
									$td_value = isset($order_item->$key) ? $order_item->$key : 0;
									break;
								case "order_tax_rate":
									$td_value = isset($order_item->$key) ? $order_item->$key : 0;
									$td_value = sprintf("%.2f%%",$td_value);
									break;
								case "tax_amount":
								case "shipping_tax_amount":
								case "total_tax":
								
								case "refund_tax_amount":
								case "refund_shipping_tax_amount":
								case "total_tax_refund":
								case "net_total_tax":
									$td_value = isset($order_item->$key) ? $order_item->$key : 0;
									break;													
								default:
									$td_value = isset($order_item->$key) ? $order_item->$key : '';
									break;

							endswitch;
							$new_rows[$i][$key] = $td_value;//New
						endforeach;
						$i++;//New
				  // $output .= '</tr>';					 
					$last_state = $order_item->$tax_group_by_key;
					$row_count++;
					$td_id++;
				}
				
				$alternate = "total_row ";
				$output .= '<tr class="'.$alternate."row_".$key.'">';
				foreach($columns as $key => $value):
					$td_class = $key;                                            
					$td_value = "";
					switch($key):                                            
						case "tax_amount":
						case "shipping_tax_amount":
						case "total_tax":
						
						case "refund_tax_amount":
						case "refund_shipping_tax_amount":
						case "total_tax_refund":
						case "net_total_tax":
							$td_value = isset($total_row[$key]) ? $total_row[$key] : 0;
							break;
						default:
							$td_value = '';
							break;
					endswitch;
					$new_rows[$i][$key] = $td_value;//New
				endforeach; 
				$i++;//New				
				$row_count = 0;
				return $new_rows;
		}
		
		function get_grid_items_tax_group_by_tax_name($order_items){
			$last_state 	= "";
			$row_count 		= 0;
			$output 		= '';
			$i 				= 0;//New
			$new_rows		= array();//New
			
			$request		= $this->get_all_request();extract($request);
			$columns = $this->get_column($tax_group_by);
			
			$total_row = array("shipping_tax_amount" => 0,"tax_amount" => 0,"total_tax" => 0);
			$td_id		= 0;
			foreach ( $order_items as $key => $order_item ) {
				//$order_item->_total_tax = $order_item->_shipping_tax_amount + $order_item->_order_tax;
				//$order_item->_order_amount = $order_item->_order_tax > 0 ? ($order_item->_order_tax*100)/$order_item->order_tax_rate : 0;	
			//	$order_item->_order_amount = $this->get_percentage($order_item->_order_tax,$order_item->order_tax_rate);//Added 20150206			
				$order_item->tax_rate_name = isset($order_item->tax_rate_name) ? trim($order_item->tax_rate_name) : '';
				$order_item->tax_rate_name = strlen($order_item->tax_rate_name)<=0 ? $order_item->tax_rate_code : $order_item->tax_rate_name;				
				$order_item->billing_state = isset($order_item->billing_state) ? $order_item->billing_state : '';
				
				if($last_state != $order_item->tax_rate_name){
					if($td_id != 0){
						$alternate = "total_row ";
						$output .= '<tr class="'.$alternate."row_".$key.'">';
						foreach($columns as $key => $value):
							$td_class = $key;                                            
							$td_value = "";
							switch($key):                                            
								case "tax_amount":
								case "shipping_tax_amount":
								case "total_tax":
								
								case "refund_tax_amount":
								case "refund_shipping_tax_amount":
								case "total_tax_refund":
								case "net_total_tax":
									$td_value = isset($total_row[$key]) ? $total_row[$key] : 0;
									//$td_value = $this->price($td_value);
									break;
								default:
									$td_value = '';
									break;
							endswitch;
							//$td_content = "<td class=\"{$td_class}\">{$td_value}</td>\n";
							//$output .= $td_content;
							$new_rows[$i][$key] = $td_value;//New
						endforeach; 
						$i++;
						$output .= '</tr>';
						$row_count = 0;
						$total_row = array();
					}
					$alternate = "";
					$output .= '<tr class="'.$alternate."row_".$key.'">';
						foreach($columns as $key => $value):
							$td_class = $key;                                            
							$td_value = "";
							switch($key):                                            
								case "tax_rate_name":
									$td_value = isset($order_item->$key) ? $order_item->$key : '';
									break;
								default:
									$td_value = '';
									break;
							endswitch;
							//$td_content = "<td class=\"{$td_class}\">{$td_value}</td>\n";
							//$output .= $td_content;
							$new_rows[$i][$key] = $td_value;//New
						endforeach; 
						$i++;
						$row_count = 0;
					$output .= '</tr>';
				}
				
				//$total_row['_shipping_tax_amount'] = isset($total_row['_shipping_tax_amount']) ? ($total_row['_shipping_tax_amount'] + $order_item->_shipping_tax_amount) : $order_item->_shipping_tax_amount;
				//$total_row['_order_tax'] = isset($total_row['_order_tax']) ? ($total_row['_order_tax'] + $order_item->_order_tax) : $order_item->_order_tax;
				//$total_row['_total_tax'] = isset($total_row['_total_tax']) ? ($total_row['_total_tax'] + $order_item->_total_tax) : $order_item->_total_tax;
				
				$total_row = $this->get_order_total($total_row,$order_item);
				
				if($row_count%2 == 0){$alternate = "alternate ";}else{$alternate = "";};
				//$output .= '<tr class="'.$alternate."row_".$key.'">';
						foreach($columns as $key => $value):
							$td_class = $key;                                            
							$td_value = "";
							switch($key):
								case "tax_rate_name":
									$td_value = '';
									break;
								case "billing_state":
									$billing_state = isset($order_item->$key) ? $order_item->$key : '';
									$billing_country = isset($order_item->billing_country) ? $order_item->billing_country : '';
									$td_value = $this->get_billling_state_name($billing_country, $billing_state);                                                
									break;
								case "_order_count":
									$td_value = isset($order_item->$key) ? $order_item->$key : 0;
									break;
								case "order_tax_rate":
									$td_value = isset($order_item->$key) ? $order_item->$key : 0;
									$td_value = sprintf("%.2f%%",$td_value);
									break;
								case "_order_shipping_amount":
								case "_order_amount":
								case "order_total_amount":
								case "_shipping_tax_amount":
								case "_order_tax":
								case "_total_tax":
								case "r_order_tax":
								case "r_total_tax":
								case "order_total":
								case "order_shipping":
								case "cart_discount":
								case "order_discount":
								case "total_discount":
								case "order_tax":
								case "order_shipping_tax":
								case "total_tax":
								case "gross_amount":
								
								case "net_order_total":
								case "refund_order_total":
									$td_value = isset($order_item->$key) ? $order_item->$key : 0;
									//$td_value = $this->price($td_value);
									break;													
								default:
									$td_value = isset($order_item->$key) ? $order_item->$key : '';
									break;
							endswitch;
							//$td_content = "<td class=\"{$td_class}\">{$td_value}</td>\n";
							//$output .= $td_content;
							$new_rows[$i][$key] = $td_value;//New
						endforeach;  
						$i++;                                      	
				  // $output .= '</tr>';					 
					$last_state = $order_item->tax_rate_name;
					$row_count++;
					$td_id++;
				}
				
				$alternate = "total_row ";
				//$output .= '<tr class="'.$alternate."row_".$key.'">';
				foreach($columns as $key => $value):
					$td_class = $key;                                            
					$td_value = "";
					switch($key):                                            
						case "tax_amount":
						case "shipping_tax_amount":
						case "total_tax":
						
						case "refund_tax_amount":
						case "refund_shipping_tax_amount":
						case "total_tax_refund":
						case "net_total_tax":
							$td_value = isset($total_row[$key]) ? $total_row[$key] : 0;
							//$td_value = $this->price($td_value);
							break;
						default:
							$td_value = '';
							break;
					endswitch;
					//$td_content = "<td class=\"{$td_class}\">{$td_value}</td>\n";
					//$output .= $td_content;
					$new_rows[$i][$key] = $td_value;//New
				endforeach; 
				$i++;
				$output .= '</tr>';
				$row_count = 0;
				//return $output;
				return $new_rows;
		}
		
		function get_grid_items_tax_group_by_tax_summary($order_items){
			$last_state 	= "";
			$row_count 		= 0;
			$output 		= '';
			$i 				= 0;//New
			$new_rows		= array();//New
			
			$request		= $this->get_all_request();extract($request);
			$columns = $this->get_column($tax_group_by);
			
			$total_row = array("_shipping_tax_amount" => 0,"tax_amount" => 0,"total_tax" => 0);
			
			foreach ( $order_items as $key => $order_item ) {
				//$order_item->_total_tax = $order_item->_shipping_tax_amount + $order_item->_order_tax;
				//$order_item->_order_amount = $order_item->_order_tax > 0 ? ($order_item->_order_tax*100)/$order_item->order_tax_rate : 0;	
				//$order_item->_order_amount = $this->get_percentage($order_item->_order_tax,$order_item->order_tax_rate);//Added 20150206			
				$order_item->tax_rate_name = isset($order_item->tax_rate_name) ? trim($order_item->tax_rate_name) : '';
				$order_item->tax_rate_name = strlen($order_item->tax_rate_name)<=0 ? $order_item->tax_rate_code : $order_item->tax_rate_name;				
				$order_item->billing_state = isset($order_item->billing_state) ? $order_item->billing_state : '';
				
				//$total_row['_shipping_tax_amount'] = isset($total_row['_shipping_tax_amount']) ? ($total_row['_shipping_tax_amount'] + $order_item->_shipping_tax_amount) : $order_item->_shipping_tax_amount;
				//$total_row['_order_tax'] = isset($total_row['_order_tax']) ? ($total_row['_order_tax'] + $order_item->_order_tax) : $order_item->_order_tax;
				//$total_row['_total_tax'] = isset($total_row['_total_tax']) ? ($total_row['_total_tax'] + $order_item->_total_tax) : $order_item->_total_tax;
				
				$total_row = $this->get_order_total($total_row,$order_item);
				
				if($row_count%2 == 0){$alternate = "alternate ";}else{$alternate = "";};
				$output .= '<tr class="'.$alternate."row_".$key.'">';
						foreach($columns as $key => $value):
							$td_class = $key;                                            
							$td_value = "";
							switch($key):
								case "billing_state":
									$td_value = isset($order_item->$key) ? $order_item->$key : 0;
									break;
								case "_order_count":
									$td_value = isset($order_item->$key) ? $order_item->$key : 0;
									break;
								case "order_tax_rate":
									$td_value = isset($order_item->$key) ? $order_item->$key : 0;
									$td_value = sprintf("%.2f%%",$td_value);
									break;
								
								case "order_total":
								case "order_shipping":
								case "cart_discount":
								case "order_discount":
								case "total_discount":
								case "order_tax":
								case "order_shipping_tax":
								case "total_tax":
								case "gross_amount":
								
								case "tax_amount":
								case "shipping_tax_amount":
								case "total_tax":
								
								case "refund_tax_amount":
								case "refund_shipping_tax_amount":
								case "total_tax_refund":
								
								case "net_order_total":
								case "refund_order_total":
								case "net_total_tax":
									$td_value = isset($order_item->$key) ? $order_item->$key : 0;
									//$td_value = $this->price($td_value);
									break;													
								default:
									$td_value = isset($order_item->$key) ? $order_item->$key : '';
									break;
							endswitch;
							//$td_content = "<td class=\"{$td_class}\">{$td_value}</td>\n";
							//$output .= $td_content;
							$new_rows[$i][$key] = $td_value;//New
						endforeach;   
						$i++;                                     	
				   $output .= '</tr>';					 
				$last_state = $order_item->billing_state;
				$row_count++;
				}
				
				$alternate = "total_row ";
				$output .= '<tr class="'.$alternate."row_".$key.'">';
				foreach($columns as $key => $value):
					$td_class = $key;                                            
					$td_value = "";
					switch($key):                                            
						case "tax_amount":
						case "shipping_tax_amount":
						case "total_tax":
						
						case "refund_tax_amount":
						case "refund_shipping_tax_amount":
						case "total_tax_refund":
						case "net_total_tax":
							$td_value = isset($total_row[$key]) ? $total_row[$key] : 0;
							//$td_value = $this->price($td_value);
							break;
						default:
							$td_value = '';
							break;
					endswitch;
					//$td_content = "<td class=\"{$td_class}\">{$td_value}</td>\n";
					//$output .= $td_content;
					$new_rows[$i][$key] = $td_value;//New
				endforeach; 
				$i++;
				//$output .= '</tr>';
				$row_count = 0;
				//return $output;
				return $new_rows;
		}
		
		function get_grid_items_tax_group_by_state_summary($order_items, $tax_group_by_key = 'billing_state'){
			$last_state 	= "";
			$row_count 		= 0;
			$output 		= '';
			$i 				= 0;//New
			$new_rows		= array();//New
			
			$request		= $this->get_all_request();extract($request);
			$columns = $this->get_column($tax_group_by);
			
			$total_row = array("_shipping_tax_amount" => 0,"tax_amount" => 0,"total_tax" => 0);
			
			$country    = $this->get_wc_countries();//Added 20150225
			
			foreach ( $order_items as $key => $order_item ) {
				//$order_item->_total_tax = $order_item->_shipping_tax_amount + $order_item->_order_tax;
				//$order_item->_order_amount = $order_item->_order_tax > 0 ? ($order_item->_order_tax*100)/$order_item->order_tax_rate : 0;				
				//$order_item->_order_amount = $this->get_percentage($order_item->_order_tax,$order_item->order_tax_rate);//Added 20150206
				$order_item->tax_rate_name = isset($order_item->tax_rate_name) ? trim($order_item->tax_rate_name) : '';
				$order_item->tax_rate_name = strlen($order_item->tax_rate_name)<=0 ? $order_item->tax_rate_code : $order_item->tax_rate_name;				
				$order_item->billing_state = isset($order_item->billing_state) ? $order_item->billing_state : '';
				
				//$total_row['_shipping_tax_amount'] = isset($total_row['_shipping_tax_amount']) ? ($total_row['_shipping_tax_amount'] + $order_item->_shipping_tax_amount) : $order_item->_shipping_tax_amount;
				//$total_row['_order_tax'] = isset($total_row['_order_tax']) ? ($total_row['_order_tax'] + $order_item->_order_tax) : $order_item->_order_tax;
				//$total_row['_total_tax'] = isset($total_row['_total_tax']) ? ($total_row['_total_tax'] + $order_item->_total_tax) : $order_item->_total_tax;
				
				$total_row = $this->get_order_total($total_row,$order_item);
				
				if($row_count%2 == 0){$alternate = "alternate ";}else{$alternate = "";};
				$output .= '<tr class="'.$alternate."row_".$key.'">';
						foreach($columns as $key => $value):
							$td_class = $key;                                            
							$td_value = "";
							switch($key):
								case "billing_state":
									$billing_state = isset($order_item->$key) ? $order_item->$key : '';
									$billing_country = isset($order_item->billing_country) ? $order_item->billing_country : '';
									$td_value = $this->get_billling_state_name($billing_country, $billing_state);                                                
									break;
								case "billing_country":
									$billing_country = isset($order_item->$key) ? $order_item->$key : '';
									$billing_country = isset($country->countries[$billing_country]) ? $country->countries[$billing_country]: $billing_country;
									$td_value = $billing_country;
									break;
								case "tax_city":
								case "billing_postcode":
									$td_value = isset($order_item->$key) ? $order_item->$key : '';
									break;
								case "_order_count":
									$td_value = isset($order_item->$key) ? $order_item->$key : 0;
									break;
								case "order_tax_rate":
									$td_value = isset($order_item->$key) ? $order_item->$key : 0;
									$td_value = sprintf("%.2f%%",$td_value);
									break;
								case "_order_shipping_amount":
								case "_order_amount":
								case "order_total_amount":
								case "_shipping_tax_amount":
								case "_order_tax":
								case "_total_tax":
								case "r_order_tax":
								case "r_total_tax":
								case "order_total":
								case "order_shipping":
								case "cart_discount":
								case "order_discount":
								case "total_discount":
								case "order_tax":
								case "order_shipping_tax":
								case "total_tax":
								case "gross_amount":
									$td_value = isset($order_item->$key) ? $order_item->$key : 0;
									//$td_value = $this->price($td_value);
									break;													
								default:
									$td_value = isset($order_item->$key) ? $order_item->$key : '';
									break;
							endswitch;
							//$td_content = "<td class=\"{$td_class}\">{$td_value}</td>\n";
							//$output .= $td_content;
							$new_rows[$i][$key] = $td_value;//New
						endforeach;   
						$i++;                                     	
				   $output .= '</tr>';					 
				$last_state = $order_item->$tax_group_by_key;
				$row_count++;
				}
				
				$alternate = "total_row ";
				$output .= '<tr class="'.$alternate."row_".$key.'">';
				foreach($columns as $key => $value):
					$td_class = $key;                                            
					$td_value = "";
					switch($key):                                            
						case "tax_amount":
						case "shipping_tax_amount":
						case "total_tax":
						
						case "refund_tax_amount":
						case "refund_shipping_tax_amount":
						case "total_tax_refund":
						case "net_total_tax":
						
						
						case "order_total":
						case "order_shipping":
						case "cart_discount":
						case "order_discount":
						case "total_discount":
						case "order_tax":
						case "order_shipping_tax":
						case "total_tax":
						case "gross_amount":
						case "refund_order_total":
						case "net_order_total":						
							$td_value = isset($total_row[$key]) ? $total_row[$key] : 0;
							break;
						case "order_count":
							$td_value = isset($total_row[$key]) ? $total_row[$key] : 0;
							break;
						default:
							$td_value = '';
							break;
					endswitch;
					//$td_content = "<td class=\"{$td_class}\">{$td_value}</td>\n";
					//$output .= $td_content;
					$new_rows[$i][$key] = $td_value;//New
				endforeach; 
				$i++;
				$output .= '</tr>';
				$row_count = 0;
				//return $output;
				return $new_rows;
		}
		
		function ic_commerce_custom_report_page_export_csv($export_file_format='csv'){
		
			global $wpdb, $table_prefix;
			
			$report_name	= $this->get_request('report_name',"no");
			$tax_group_by	= $this->get_request('tax_group_by',"no");
			$rows 			= $this->get_tax_items_query('total_row');	
			$tax_group_by 	= $this->get_request('tax_group_by');
			$columns 		= $this->get_column($tax_group_by );
			
			switch($tax_group_by){
				case "tax_group_by_city":
					$grid_items = $this->get_grid_items_tax_group_by_state($rows,'tax_city');
					break;
				case "tax_group_by_state":
					$grid_items = $this->get_grid_items_tax_group_by_state($rows,'billing_state');
					break;
				case "tax_group_by_country":
					$grid_items = $this->get_grid_items_tax_group_by_state($rows,'billing_country');
					break;
				case "tax_group_by_zip":
					$grid_items = $this->get_grid_items_tax_group_by_state($rows,'billing_postcode');
					break;
				case "tax_group_by_tax_name":
					$grid_items = $this->get_grid_items_tax_group_by_tax_name($rows);
					break;
				case "tax_group_by_tax_summary":
					$grid_items = $this->get_grid_items_tax_group_by_tax_summary($rows);
					break;
				case "tax_group_by_city_summary":
					$grid_items = $this->get_grid_items_tax_group_by_state_summary($rows,'tax_city');
					break;
				case "tax_group_by_state_summary":
					$grid_items = $this->get_grid_items_tax_group_by_state_summary($rows,'billing_state');
					break;
				case "tax_group_by_country_summary":
					$grid_items = $this->get_grid_items_tax_group_by_state_summary($rows,'billing_country');
					break;
				default:
					$grid_items = $this->get_grid_items_tax_group_by_tax_name($rows);
					break;
			}
			
			$export_rows = $grid_items;
			
			
			
			$export_file_name 		= $this->get_request('export_file_name',"no");
			$report_name 			= $this->get_request('report_name','product_page');
			$report_name 			= str_replace("_page","_list",$report_name);
			
			/*$today = date_i18n("Y-m-d-H-i-s");				
			$FileName = $export_file_name."_".$report_name."-".$today.".".$export_file_format;	
			$out = $this->ExportToCsv($FileName,$export_rows,$columns,$export_file_format);*/
			
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
		
		function ic_commerce_custom_report_page_export_pdf(){
			global $wpdb, $table_prefix;
			
			$report_name	= $this->get_request('report_name',"no");
			$tax_group_by	= $this->get_request('tax_group_by',"no");
			$rows 			= $this->get_tax_items_query('total_row');	
			$tax_group_by 	= $this->get_request('tax_group_by');
			$columns 		= $this->get_column($tax_group_by );
			
			switch($tax_group_by){
				case "tax_group_by_city":
					$grid_items = $this->get_grid_items_tax_group_by_state($rows,'tax_city');
					break;
				case "tax_group_by_state":
					$grid_items = $this->get_grid_items_tax_group_by_state($rows,'billing_state');
					break;
				case "tax_group_by_country":
					$grid_items = $this->get_grid_items_tax_group_by_state($rows,'billing_country');
					break;
				case "tax_group_by_zip":
					$grid_items = $this->get_grid_items_tax_group_by_state($rows,'billing_postcode');
					break;
				case "tax_group_by_tax_name":
					$grid_items = $this->get_grid_items_tax_group_by_tax_name($rows);
					break;
				case "tax_group_by_tax_summary":
					$grid_items = $this->get_grid_items_tax_group_by_tax_summary($rows);
					break;
				case "tax_group_by_city_summary":
					$grid_items = $this->get_grid_items_tax_group_by_state_summary($rows,'tax_city');
					break;
				case "tax_group_by_state_summary":
					$grid_items = $this->get_grid_items_tax_group_by_state_summary($rows,'billing_state');
					break;
				case "tax_group_by_country_summary":
					$grid_items = $this->get_grid_items_tax_group_by_state_summary($rows,'billing_country');
					break;
				default:
					$grid_items = $this->get_grid_items_tax_group_by_tax_name($rows);
					break;
			}
			
			foreach($grid_items as $gkey => $gvalue){
				foreach($columns as $key => $value):
					switch($key):                                            
						case "tax_amount":
						case "shipping_tax_amount":
						case "total_tax":
						
						case "refund_tax_amount":
						case "refund_shipping_tax_amount":
						case "total_tax_refund":
						case "net_total_tax":
						
						
						case "order_total":
						case "order_shipping":
						case "cart_discount":
						case "order_discount":
						case "total_discount":
						case "order_tax":
						case "order_shipping_tax":
						case "total_tax":
						case "gross_amount":
						case "refund_order_total":
						case "net_order_total":
							$td_value = isset($gvalue[$key]) ? $gvalue[$key] : 0;
							if(strlen($td_value)>0)
								$td_value = $this->price($td_value);
							else
								$td_value = '';
							break;
						default:
							$td_value = isset($gvalue[$key]) ? $gvalue[$key] : '';
							break;
					endswitch;
					$grid_items[$gkey][$key] = $td_value;
				endforeach;
			}
			
			///die;
			
			$export_rows = $grid_items;
			
			$summary = array('total_row_amount',' total_row_count');
			
			$output = $this->GetPdfDataGrid($export_rows,$columns,$summary);
			
			$this->export_to_pdf($export_rows,$output);
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
			unset($request['page']);
			unset($request['p']);
			
			
			?>
            <div id="<?php echo $admin_page ;?>Export" class="RegisterDetailExport">
                <form id="<?php echo $admin_page."_".$position ;?>_form" class="<?php echo $admin_page ;?>_form ic_export_<?php echo $position ;?>_form" action="<?php echo $mngpg;?>" method="post">
                    <?php foreach($request as $key => $value):?>
                        <input type="hidden" name="<?php echo $key;?>" value="<?php echo $value;?>" />
                    <?php endforeach;?>
                    <input type="hidden" name="export_file_name" value="<?php echo $admin_page;?>" />
                    <input type="hidden" name="export_file_format" value="csv" />
                    
                    <input type="submit" name="<?php echo $admin_page ;?>_export_csv" class="onformprocess  csvicon" value="<?php _e("Export to CSV",'icwoocommerce_textdomains');?>" data-format="csv" data-popupid="export_csv_popup" data-hiddenbox="popup_csv_hidden_fields" data-popupbutton="<?php _e("Export to CSV",'icwoocommerce_textdomains');?>" data-title="<?php _e("Export to CSV - Additional Information",'icwoocommerce_textdomains');?>" />
                    <input type="submit" name="<?php echo $admin_page ;?>_export_xls" class="onformprocess  excelicon" value="<?php _e("Export to Excel",'icwoocommerce_textdomains');?>" data-format="xls" data-popupid="export_csv_popup" data-hiddenbox="popup_csv_hidden_fields" data-popupbutton="<?php _e("Export to Excel",'icwoocommerce_textdomains');?>" data-title="<?php _e("Export to Excel - Additional Information",'icwoocommerce_textdomains');?>" />
                    <input type="button" name="<?php echo $admin_page ;?>_export_pdf" class="onformprocess open_popup pdficon" value="<?php _e("Export to PDF",'icwoocommerce_textdomains');?>" data-format="pdf" data-popupid="export_pdf_popup" data-hiddenbox="popup_pdf_hidden_fields" data-popupbutton="<?php _e("Export to PDF",'icwoocommerce_textdomains');?>" data-title="<?php _e("Export to PDF",'icwoocommerce_textdomains');?>" />
                    <input type="button" name="<?php echo $admin_page ;?>_export_print" class="onformprocess open_popup printicon" value="<?php _e("Print",'icwoocommerce_textdomains');?>"  data-format="print" data-popupid="export_print_popup" data-hiddenbox="popup_print_hidden_fields" data-popupbutton="<?php _e("Print",'icwoocommerce_textdomains');?>" data-title="<?php _e("Print",'icwoocommerce_textdomains');?>" data-form="form" />
                    
                    
                </form>
                <?php if($position == "bottom"):?>
                <form id="search_order_pagination" class="search_order_pagination" action="<?php echo $mngpg;?>" method="post">
                    <?php foreach($request_ as $key => $value):?>
						<input type="hidden" name="<?php echo $key;?>" value="<?php echo $value;?>" />
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
		
		function get_country_list(){
			
			global $wpdb;
			
			$country_list = array();
			
			$sql = " SELECT billing_by.meta_value as country_code, billing_by.meta_value as country_label ";			
			$sql .= " FROM `{$wpdb->prefix}posts` AS posts";
			$sql .= " LEFT JOIN {$wpdb->prefix}postmeta as billing_by ON billing_by.post_id=posts.ID";
			$sql .= " WHERE billing_by.meta_key='_billing_country' AND posts.post_type='shop_order'";			
			$sql .= " GROUP BY billing_by.meta_value";			
			$sql .= " ORDER BY billing_by.meta_value ASC";			
			$results	= $wpdb->get_results($sql);
			
			foreach($results as $key => $value){
				$country_list[$value->country_code] = $value->country_code;
			}
			
			$sql = " SELECT billing_by.meta_value as country_code, billing_by.meta_value as country_label ";			
			$sql .= " FROM `{$wpdb->prefix}posts` AS posts";
			$sql .= " LEFT JOIN {$wpdb->prefix}postmeta as billing_by ON billing_by.post_id=posts.ID";
			$sql .= " WHERE billing_by.meta_key='_shipping_country' AND posts.post_type='shop_order'";			
			$sql .= " GROUP BY billing_by.meta_value";			
			$sql .= " ORDER BY billing_by.meta_value ASC";			
			$results	= $wpdb->get_results($sql);			
			
			foreach($results as $key => $value){
				if(!in_array($value->country_code, $country_list))
					$country_list[$value->country_code] = $value->country_code;
			}
			
			$country    = $this->get_wc_countries();//Added 20150225
			foreach($country_list as $key => $value){
				$country_list[$key] = isset($country->countries[$value]) ? $country->countries[$value]: $value;
			}
			
			return $country_list;
		}
		
		function get_country_state_list(){
			
			global $wpdb;
			
			$country_list = array();
			
			$sql = " SELECT billing_by.meta_value as country_code, billing_by.meta_value as country_label ";			
			$sql .= " FROM `{$wpdb->prefix}posts` AS posts";
			$sql .= " LEFT JOIN {$wpdb->prefix}postmeta as billing_by ON billing_by.post_id=posts.ID";
			$sql .= " WHERE billing_by.meta_key='_billing_country' AND posts.post_type='shop_order'";			
			$sql .= " GROUP BY billing_by.meta_value";			
			$sql .= " ORDER BY billing_by.meta_value ASC";			
			$results	= $wpdb->get_results($sql);
			
			foreach($results as $key => $value){
				$country_list[$value->country_code] = $value->country_code;
			}
			
			$sql = " SELECT billing_by.meta_value as country_code, billing_by.meta_value as country_label ";			
			$sql .= " FROM `{$wpdb->prefix}posts` AS posts";
			$sql .= " LEFT JOIN {$wpdb->prefix}postmeta as billing_by ON billing_by.post_id=posts.ID";
			$sql .= " WHERE billing_by.meta_key='_shipping_country' AND posts.post_type='shop_order'";			
			$sql .= " GROUP BY billing_by.meta_value";			
			$sql .= " ORDER BY billing_by.meta_value ASC";			
			$results	= $wpdb->get_results($sql);			
			
			foreach($results as $key => $value){
				if(!in_array($value->country_code, $country_list))
					$country_list[$value->country_code] = $value->country_code;
			}
			
			$country    = $this->get_wc_countries();//Added 20150225
			foreach($country_list as $key => $value){
				$country_list[$key] = isset($country->countries[$value]) ? $country->countries[$value]: $value;
			}
			
			return $country_list;
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
		
	}//END CLASS
}//END CLASS EXISTS CHECK
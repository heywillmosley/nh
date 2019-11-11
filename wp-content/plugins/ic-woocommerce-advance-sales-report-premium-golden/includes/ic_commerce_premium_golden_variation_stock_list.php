<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
if ( ! class_exists( 'IC_Commerce_Premium_Golden_Variation_Stock_List_report' ) ) {
	//require_once('ic_commerce_premium_golden_fuctions.php');
	class IC_Commerce_Premium_Golden_Variation_Stock_List_report extends IC_Commerce_Premium_Golden_Fuctions{
		
		public $per_page = 0;
		
		public $per_page_default = 5;
		
		public $constants 	=	array();
		
		public $request		=	array();
		
		public function __construct($constants) {
			global $options;
			
			$this->constants		= $constants;			
			$options				= $this->constants['plugin_options'];			
			$this->per_page			= $this->constants['per_page_default'];
			$this->per_page_default	= $this->constants['per_page_default'];
		}
		
		
		
		function init(){
			
			//$this->test();
			
			global $options;
			
			if(!isset($_REQUEST['page'])) return false;
			
			if ( !current_user_can( $this->constants['plugin_role'] ) )  {
				wp_die( __( 'You do not have sufficient permissions to access this page.','icwoocommerce_textdomains' ) );
			}
			
			do_action('ic_commerce_current_user_can',$_REQUEST['page'],$this->constants);
			$onload_search				= 'yes';
			$onload_search				= apply_filters('ic_commerce_onload_search', $onload_search, '');
			
			$page			= $this->get_request('page',NULL);
			$admin_page		= $this->get_request('admin_page',$page,true);
			
			if($admin_page == $this->constants['plugin_key']."_email_alert_variation_products"){
				$_REQUEST['manage_stock'] 	= "yes";
				$_REQUEST['sort_by'] 		= "variation_stock";
			}			
			$manage_stock	= $this->get_request('manage_stock','-1',true);
			$sort_by		= $this->get_request('sort_by','product_name',true);
			$order_by		= $this->get_request('order_by','ASC',true);
			
			
			$product_id		= $this->get_request('product_id','-1',true);
			$category_id	= $this->get_request('category_id','-1',true);
			$ProductTypeID	= $this->get_request('ProductTypeID',NULL,true);			
			$product_subtype= $this->get_request('product_subtype','-1',true);			
						
			$optionsid	= "per_row_variation_stock_page";
			$per_page 	= $this->get_number_only($optionsid,$this->per_page_default);			
			$action		= $this->get_request('action',$this->constants['plugin_key'].'_wp_ajax_action',true);
			$page_title = __("Stock List",'icwoocommerce_textdomains');
			
			$dropdown_multiselect = true;
			
			?>
			<div id="navigation">
			<div class="collapsible" id="section1"><?php _e("Custom Search",'icwoocommerce_textdomains');?><span></span></div>
				<div class="container">
					<div class="content">
						<div class="search_report_form">
                        	 <div class="form_process"></div>
							<form action="" name="search_order_report" id="search_order_report" method="post">
                            	<div class="form-group">
                                    <div class="FormRow FirstRow">
                                        <div class="label-text"><label for="sku_number"><?php _e("SKU No:",'icwoocommerce_textdomains');?></label></div>
                                        <div class="input-text"><input type="text" id="sku_number" name="sku_number" maxlength="20" value="<?php echo $this->get_request('sku_number',NULL,true);?>" /></div>
                                    </div>
                                    <div class="FormRow">
                                        <div class="label-text"><label for="product_name"><?php _e("Product Name:",'icwoocommerce_textdomains');?></label></div>
                                        <div class="input-text"><input type="text" id="product_name" name="product_name" maxlength="100" value="<?php echo $this->get_request('product_name',NULL,true);?>" /></div>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    
                                    <div class="FormRow FirstRow">
                                        <div class="label-text"><label for="txtMinStock"><?php _e("Min Stock:",'icwoocommerce_textdomains');?></label></div>
                                        <div class="input-text"><input type="text" id="txtMinStock" name="txtMinStock" maxlength="100" value="<?php echo $this->get_request('txtMinStock',NULL,true);?>" /></div>
                                    </div>
                                     <div class="FormRow ">
                                        <div class="label-text"><label for="txtMaxStock"><?php _e("MaxStock",'icwoocommerce_textdomains');?>:</label></div>
                                        <div class="input-text"><input type="text" id="txtMaxStock" name="txtMaxStock" maxlength="100" value="<?php echo $this->get_request('txtMaxStock',NULL,true);?>" /></div>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    
                                    
                                    <div class="FormRow FirstRow">
                                        <div class="label-text"><label for="product_stock"><?php _e("Product Stock:",'icwoocommerce_textdomains');?></label></div>
                                        <div class="input-text"><input type="text" id="product_stock" name="product_stock" maxlength="100"  value="<?php echo $this->get_request('product_stock',NULL,true);?>" /></div>
                                    </div>
                                    <div class="FormRow">
                                        <div class="label-text"><label for="ProductTypeID"><?php _e("Show all sub-types:",'icwoocommerce_textdomains');?></label></div>
                                        <div class="input-text">
                                        	<?php 
													$this->downloadable_virtual();
											?>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                   <div class="FormRow FirstRow">
                                        <div class="label-text"><label for="stock_status"><?php _e("Stock Status:",'icwoocommerce_textdomains');?></label></div>
                                         <div class="input-text">
                                        	<?php 
													$stock_status = array("instock" => __("In stock",'icwoocommerce_textdomains'), "outofstock" => __("Out of stock",'icwoocommerce_textdomains'));
													$this->create_dropdown($stock_status,"stock_status","stock_status",__("All",'icwoocommerce_textdomains'),"stock_status",'-1', 'array');
											?>
                                        </div>
                                    </div>
                                    <div class="FormRow">
                                        <div class="label-text"><label for="manage_stock"><?php _e("Manage Stock:",'icwoocommerce_textdomains');?></label></div>
                                        <div class="input-text">
                                        	<?php 
													$manage_stocks = array("yes" => __("Include items whose stock is mannaged",'icwoocommerce_textdomains'), "no" => __("Include items whose stock is not mannaged",'icwoocommerce_textdomains'));
													$this->create_dropdown($manage_stocks,"manage_stock","manage_stock",__("All",'icwoocommerce_textdomains'),"manage_stock",$manage_stock, 'array');
											?>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                   <div class="FormRow FirstRow">
                                        <div class="label-text"><label for="product_id"<?php _e(">Product:",'icwoocommerce_textdomains');?></label></div>
                                        <div class="input-text">
                                        	<?php 
													$product_data = $this->get_product_data2('product','publish');//Purchase products
													$this->create_dropdown($product_data,"product_id[]","product_id2",__("All Product",'icwoocommerce_textdomains'),"product_id",$product_id, 'object', $dropdown_multiselect, 5);
											?>
                                        </div>
                                    </div>
                                    <div class="FormRow">
                                        <div class="label-text"><label for="category_id"><?php _e("Category:",'icwoocommerce_textdomains');?></label></div>
                                        <div class="input-text">
                                        	<?php 
													$category_data = $this->get_category_data2('product_cat','no',false);
													$this->create_dropdown($category_data,"category_id","category_id2",__("All Category",'icwoocommerce_textdomains'),"category_id2",$category_id, 'object', $dropdown_multiselect, 5);
											?>
                                        </div>
                                    </div>
                                </div>
                                
                                
                                <?php
                                	$product_sku_data 				= $this->get_variation_product_sku();
									$variations_product_sku_data 	= $this->get_variations_sku();
									if($product_sku_data or $variations_product_sku_data){
								?>                                
                                <div class="form-group">
                                <?php if($variations_product_sku_data){?>
                                    <div class="FormRow FirstRow">
                                        <div class="label-text"><label for="variation_sku"><?php _e("Variation SKU:",'icwoocommerce_textdomains');?></label></div>
                                        <div class="input-text">
                                            <?php  $this->create_dropdown($variations_product_sku_data,"variation_sku[]","variation_sku",__("Select All",'icwoocommerce_textdomains'),"variation_sku",'-1', 'object', $dropdown_multiselect, 5);?>
                                        </div>                                                        
                                    </div>
                                  <?php } ?>
                                  
                                  <?php if($product_sku_data){?>
                                    <div class="FormRow ">
                                        <div class="label-text"><label for="product_sku"><?php _e("Product SKU:",'icwoocommerce_textdomains');?></label></div>
                                        <div class="input-text">
                                            <?php
                                                $this->create_dropdown($product_sku_data,"product_sku[]","product_sku",__("Select All",'icwoocommerce_textdomains'),"product_sku",'-1', 'object', $dropdown_multiselect, 5);
                                            ?>
                                        </div>                                                        
                                    </div>
                               <?php } ?>   
                                  
                                </div>
                                <?php } ?>
                                
                                <div class="form-group">
                                    <div class="FormRow FirstRow checkbox">
                                        <div class="label-text"><label for="variations"><?php _e("Variations:",'icwoocommerce_textdomains');?></label></div>
                                        <div class="input-text">
                                            <?php
												$all_variation_columns = $this->get_all_variation();
                                                $this->create_dropdown($all_variation_columns,"variations[]","variations",__("Select All",'icwoocommerce_textdomains'),"variations",'-1', 'array', $dropdown_multiselect, 5);
                                            ?>
                                        </div>
                                    </div>
                                </div>
                                
                                 <div class="form-group">
                                    <div class="FormRow FirstRow">
                                    <div class="label-text" style="padding-top:0px;"><label for="sort_by"><?php _e("Order By:",'icwoocommerce_textdomains');?></label></div>
                                        <div style="padding-top:0px;">
                                         <?php
                                            $data = array(
												"product_name" 			=> __("Product Name",	'icwoocommerce_textdomains'),
												"product_id" 			=> __("Product ID",		'icwoocommerce_textdomains'),
												//"variation_name" 		=> __("Variation Name",	'icwoocommerce_textdomains'),
												"variation_id" 			=> __("Variation ID",	'icwoocommerce_textdomains'),
												"variation_stock" 		=> __("Variation Stock",'icwoocommerce_textdomains')
											);
                                            $this->create_dropdown($data,"sort_by","sort_by",NULL,"sort_by",$sort_by, 'array');
                                            $data = array("ASC" => __("Ascending",'icwoocommerce_textdomains'), "DESC" => __("Descending",'icwoocommerce_textdomains'));
                                            $this->create_dropdown($data,"order_by","order_by",NULL,"order_by",$order_by, 'array');
                                          ?>
                                        </div>
                                        
                                    </div>
                                </div>
                                
                                 <div class="form-group">
                                    <div class="FormRow FirstRow checkbox">
                                        <div class="label-text"><label for="basic_column"><?php _e("Basic Column:",'icwoocommerce_textdomains');?></label></div>
                                        <div style="padding-top:0px;"><input type="checkbox" id="basic_column" name="basic_column" maxlength="100"  value="yes" <?php if($this->get_request('basic_column','yes',true) == "yes"){ echo ' checked="checked"';}?> /></div>
                                    </div>
                                    <div class="FormRow checkbox">
                                        <div class="label-text"><label for="zero_stock"><?php _e("Zero Stock:",'icwoocommerce_textdomains');?></label></div>
                                        <div style="padding-top:0px;"><input type="checkbox" id="zero_stock" name="zero_stock" maxlength="100"  value="yes" <?php if($this->get_request('zero_stock','no',true) == "yes"){ echo ' checked="checked"';}?> /><label for="zero_stock"> <strong><?php _e("Include items having 0 stock",'icwoocommerce_textdomains');?></strong></label></div>
                                    </div>
                                </div>
                                
                                 
                               
                                
                                <div class="form-group">
                                    <div class="FormRow">
                                    	
                                        <input type="hidden" name="zero_sold" 			id="zero_sold" 			value="no<?php //echo $this->get_request('zero_sold',$order_status,true);?>" />
                                        <input type="hidden" name="order_status" 		id="order_status" 		value="-1<?php //echo $this->get_request('order_status',$order_status,true);?>" />
                                        <input type="hidden" name="order_status_id" 	id="order_status_id" 	value="-1<?php //echo $this->get_request('order_status_id',$order_status_id,true);?>" />
                                        <input type="hidden" name="hide_order_status"	id="hide_order_status" 	value="-1<?php //echo $this->get_request('hide_order_status',$hide_order_status,true);?>" />                                        
                                        <input type="hidden" name="start_date" 			id="start_date" 		value="<?php //echo $this->get_request('start_date',$start_date,true);?>" />
                                        <input type="hidden" name="end_date" 			id="end_date" 			value="<?php //echo $this->get_request('end_date',$end_date,true);?>" />
                                       
                                        <input type="hidden" name="publish_order" 	id="publish_order" 	value="<?php echo $this->get_request('post_type','no',true);?>" />
                                        <input type="hidden" name="post_type" 		id="post_type" 		value="<?php echo $this->get_request('publish_order','no',true);?>" />
                                        <input type="hidden" name="limit"  			id="limit" 			value="<?php echo $this->get_request('limit',$per_page,true);?>" />
                                        <input type="hidden" name="p"  				id="p" 				value="<?php echo $this->get_request('p',1,true);?>" />                                                            
                                        <input type="hidden" name="adjacents"  		id="adjacents" 		value="<?php echo $this->get_request('adjacents','3',true);?>" />
                                        <input type="hidden" name="do_action_type" 	id="do_action_type" value="<?php echo $this->get_request('do_action_type','variation_stock_page',true);?>" />
                                        <input type="hidden" name="action" 			id="action" 		value="<?php echo $this->get_request('action',$this->constants['plugin_key'].'_wp_ajax_action',true);?>" />
                                        <input type="hidden" name="admin_page"		id="admin_page" 	value="<?php echo $this->get_request('page',$admin_page,true);?>" />
                                        <input type="hidden" name="page"			id="page" 			value="<?php echo $this->get_request('page',$admin_page,true);?>" />
                                        <input type="hidden" name="page_title"		id="page_title"		value="<?php echo $this->get_request('page_title',$page_title,true);?>" />
                                        <input type="hidden" name="total_pages"  	id="total_pages" 	value="<?php echo $this->get_request('total_pages',0,true);?>" />
                                        <span class="submit_buttons">
                                        <input name="ResetForm" id="ResetForm" class="onformprocess" value="Reset" type="reset"> 
                                        <input name="SearchOrder" id="SearchOrder" class="onformprocess searchbtn" value="Search Product" type="submit">  &nbsp; &nbsp; &nbsp; <span class="ajax_progress"></span></span>
                                        <?php //$items = $this->get_sold_product_details('variable','',true);$sold_variation_ids		= $this->get_items_id_list($items,'variation_id');?>                                        
                                        <input type="hidden" name="sold_variation_ids" 		id="sold_variation_ids" 		value="<?php //echo $this->get_request('sold_variation_ids',$sold_variation_ids,true);?>" />                                        
                                    </div>
                                </div>
								<div class="clearfix"></div>
							</form>
						</div>
					</div>
				</div>
			</div>
            <div class="table table_shop_content search_report_content hide_for_print">
            	<?php if($onload_search == "no") {echo apply_filters('ic_commerce_onload_search_text', '');}?>
            </div>
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
			if($page_title) $page_title = $page_title;
			$report_title 			= $report_title.$page_title;
			
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
                                        <td><input id="report_title_pdf" name="report_title" value="<?php echo $report_title;?>" type="text" class="textbox"></td>
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
                                <input type="hidden" name="pdf_keywords" value="<?php _e("Product variation",'icwoocommerce_textdomains');?>" />
                                <input type="hidden" name="pdf_description" value="<?php _e("List of products variation",'icwoocommerce_textdomains');?>" />
                                <input type="hidden" name="date_format" value="List of products variation" />
                            </form>
                            <div class="clear"></div>
                            </div>
                        </div>                        
                		<div class="popup_mask"></div>
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
		
		var $normal_sql_query	 	= NULL;
		
		function ic_commerce_custom_report_normal_query($type = 'total_row'){
					global $wpdb;
					$request = $this->get_all_request();extract($request);
					
					$product_sku		= $this->get_string_multi_request('product_sku',$product_sku, "-1");//New Change ID 20150226
					$variation_sku		= $this->get_string_multi_request('variation_sku',$variation_sku, "-1");//New Change ID 20150226
					$manage_stock		= $this->get_string_multi_request('manage_stock',$manage_stock, "-1");//New Change ID 20150309
					$stock_status		= $this->get_string_multi_request('stock_status',$stock_status, "-1");//New Change ID 20150309
					$sku_number			= trim($sku_number);
					if($type == 'total_row'){
						$sql_column = "SELECT count(posts.ID)";
					}else{
						$sql_column = "SELECT 
						posts.ID 				as id
						,posts.post_title 		as variation_name						
						,posts.ID 				as variation_id
						,posts.post_date 		as product_date
						,posts.post_modified 	as modified_date												
						,posts.post_parent 		AS variation_parent_id
						
						,products.ID 			as product_id
						,products.post_title 	as product_name
						
						";
					}
					
					if(!$this->normal_sql_query){
						
						$sql = " FROM 	{$wpdb->prefix}posts as posts
						
						LEFT JOIN {$wpdb->prefix}posts as products ON products.ID = posts.post_parent";
						
						if($product_subtype=="virtual") 						$sql .= " LEFT JOIN  {$wpdb->prefix}postmeta as virtual 			ON virtual.post_id			=posts.ID";
						
						if($product_subtype=="downloadable") 					$sql .= " LEFT JOIN  {$wpdb->prefix}postmeta as downloadable		ON downloadable.post_id		=posts.ID";
						
						if($sku_number){
							$sql .= " LEFT JOIN  {$wpdb->prefix}postmeta as product_sku 				ON product_sku.post_id					=	posts.post_parent";
							$sql .= " LEFT JOIN  {$wpdb->prefix}postmeta as variation_sku 				ON variation_sku.post_id				=	posts.ID";
						}else{
							if($product_sku and $product_sku != '-1'){
								$sql .= " LEFT JOIN  {$wpdb->prefix}postmeta as product_sku 				ON product_sku.post_id				=	posts.post_parent";
							}
							
							if($variation_sku and $variation_sku != '-1'){
								$sql .= " LEFT JOIN  {$wpdb->prefix}postmeta as variation_sku 				ON variation_sku.post_id			=	posts.ID";
							}
						}
						
						if($product_stock || $txtMinStock || $txtMaxStock || strlen($product_stock) >0 || $zero_stock == "yes" || $sort_by == "variation_stock") 		$sql .= " LEFT JOIN  {$wpdb->prefix}postmeta as stock 				ON stock.post_id			=posts.ID";
						
						if($category_id and $category_id != "-1"){
							$sql .= " 
							LEFT JOIN  {$wpdb->prefix}term_relationships 		as term_relationships 	ON term_relationships.object_id			=posts.post_parent
							LEFT JOIN  {$wpdb->prefix}term_taxonomy 			as term_taxonomy 		ON term_taxonomy.term_taxonomy_id		=term_relationships.term_taxonomy_id
							LEFT JOIN  {$wpdb->prefix}terms 					as terms 				ON terms.term_id						=term_taxonomy.term_id";
						}
						
						//if($zero_sold=="yes") 						$sql .= " LEFT JOIN  {$wpdb->prefix}postmeta as total_sales 			ON total_sales.post_id			=posts.ID";//New Change ID 20150309				
						if($stock_status and $stock_status != '-1') $sql .= " LEFT JOIN  {$wpdb->prefix}postmeta as stock_status 			ON stock_status.post_id			=posts.ID";//New Change ID 20150309
						if($manage_stock and $manage_stock != '-1') $sql .= " LEFT JOIN  {$wpdb->prefix}postmeta as manage_stock 			ON manage_stock.post_id			=posts.ID";//New Change ID 20150309				
						
						if($variation_attributes != "-1" and strlen($variation_attributes)>2)
							$sql .= " LEFT JOIN  {$wpdb->prefix}postmeta as postmeta_variation ON postmeta_variation.post_id = posts.ID";
						
						$sql .= " WHERE  posts.post_type='product_variation' AND posts.post_status = 'publish' AND products.post_type='product'";
						
						$sql .= " AND products.post_status = 'publish' ";
						
						$sql .= " AND posts.post_parent > 0";
						
						if($product_stock || $txtMinStock || $txtMaxStock || strlen($product_stock) >0 || $zero_stock == "yes" || $sort_by == "variation_stock") 		$sql .= " AND stock.meta_key ='_stock'";
						
						if($sku_number){
							$sql .= " AND product_sku.meta_key ='_sku'";
							$sql .= " AND variation_sku.meta_key ='_sku'";
						}else{
							if($product_sku and $product_sku != '-1'){
								$sql .= " AND product_sku.meta_key ='_sku'";
							}
							
							if($variation_sku and $variation_sku != '-1'){
								$sql .= " AND variation_sku.meta_key ='_sku'";
							}
						}
						
						if($product_subtype=="downloadable") 		$sql .= " AND downloadable.meta_key ='_downloadable'";					
						
						if($product_subtype=="virtual") 			$sql .= " AND virtual.meta_key ='_virtual'";					
						
						if($product_name) 							$sql .= " AND posts.post_title like '%{$product_name}%'";
						
						if($product_id and $product_id >0) 			$sql .= " AND posts.post_parent IN ({$product_id})";
						
						if(strlen($product_stock) >0){
							if($product_stock == 0){
								$sql .= " AND stock.meta_value = '{$product_stock}'";
							}elseif($product_stock >= 1){
								$sql .= " AND stock.meta_value = '{$product_stock}'";
							}
						}
						
						if($txtMinStock) 							$sql .= " AND stock.meta_value >= {$txtMinStock}";
						if($txtMaxStock) 							$sql .= " AND stock.meta_value <= {$txtMaxStock}";									
						if($product_subtype=="downloadable") 		$sql .= " AND downloadable.meta_value = 'yes'";					
						if($product_subtype=="virtual") 			$sql .= " AND virtual.meta_value = 'yes'";					
						if($category_id and $category_id != "-1") 	$sql .= " AND terms.term_id = {$category_id}";
						
						if($sku_number){
							$sql .= " AND (";
							
							
							$sql .= " (";
							
							$sql .= " product_sku.meta_value like '%{$sku_number}%'";						
							
							$sql .= " OR variation_sku.meta_value like '%{$sku_number}%'";
							
							$sql .= " )";
							
							if(($product_sku and $product_sku != '-1') and ($variation_sku and $variation_sku != '-1')){
								$sql .= " AND (";
								
									$sql .= " product_sku.meta_value IN ($product_sku)";
								
									$sql .= " AND variation_sku.meta_value IN ($variation_sku)";
								
								$sql .= " )";
							}else{
								
								if($product_sku and $product_sku != '-1'){
									$sql .= " AND product_sku.meta_value IN ($product_sku)";
								}
								
								if($variation_sku and $variation_sku != '-1'){
									$sql .= " AND variation_sku.meta_value IN ($variation_sku)";
								}
							}
							
							
							$sql .= " )";
							
						}else{
							
							if(($product_sku and $product_sku != '-1') and ($variation_sku and $variation_sku != '-1')){
								$sql .= " AND (";
								
									$sql .= " product_sku.meta_value IN ($product_sku)";
								
									$sql .= " AND variation_sku.meta_value IN ($variation_sku)";
								
								$sql .= " )";
							}else{
								if($product_sku and $product_sku != '-1'){
									$sql .= " AND product_sku.meta_value IN ($product_sku)";
								}
								
								if($variation_sku and $variation_sku != '-1'){
									$sql .= " AND variation_sku.meta_value IN ($variation_sku)";
								}
							}
							
							
						}
						
						if($zero_stock == "yes")	$sql .= " AND (stock.meta_value <= 0 OR LENGTH(TRIM(stock.meta_value)) <= 0)";//New Change ID 20150309
						//if($zero_sold=="yes")		$sql .= " AND total_sales.meta_key ='total_sales' AND (total_sales.meta_value <= 0 OR LENGTH(total_sales.meta_value) <= 0)";//New Change ID 20150309						
						if($stock_status and $stock_status != '-1')		$sql .= " AND stock_status.meta_key ='_stock_status' AND stock_status.meta_value IN ({$stock_status})";//New Change ID 20150309
						if($manage_stock and $manage_stock != '-1')		$sql .= " AND manage_stock.meta_key ='_manage_stock' AND manage_stock.meta_value IN ({$manage_stock})";//New Change ID 20150309
						
						if($zero_sold=="yes"){
							if(strlen($sold_variation_ids)>0){
								$sql .= " AND posts.ID NOT IN ($sold_variation_ids)";
							}
						}
						
						if($variation_attributes != "-1" and strlen($variation_attributes)>2){
							$sql .= " AND postmeta_variation.meta_key IN ('{$variation_attributes}')";
						}
						
						$sql .= " GROUP BY posts.ID";
						
						//$sql .= " ORDER BY posts.post_parent ASC, posts.post_title ASC";
						
						switch($sort_by){
							case "variation_stock":
								$sql .= " ORDER BY ABS(stock.meta_value) {$order_by}";
								break;
							case "product_id":
								$sql .= " ORDER BY products.ID {$order_by}";
								break;
							case "product_name":
								$sql .= " ORDER BY posts.post_parent ASC, posts.post_title ASC";
								break;
							case "variation_id":
								$sql .= " ORDER BY posts.ID {$order_by}";
								break;
							case "variation_name":
								$sql .= " ORDER BY posts.post_title ASC";
								break;
							default:
								$sql .= " ORDER BY posts.post_parent ASC, posts.post_title ASC";
								break;
						}
						
						$this->normal_sql_query = $sql;
						
						//$this->print_sql($sql);
						
					
					}else{
						$sql = $this->normal_sql_query;
					}
					
					$sql = $sql_column .$sql;
					
					$wpdb->flush(); 				
					$wpdb->query("SET SQL_BIG_SELECTS=1");
					
					if($type == 'total_row'){
						if($total_pages > 0){
							$order_items = $total_pages;
						}else{
							$order_items = $wpdb->get_results($sql);
							//$this->print_sql($sql);
							//$this->print_array($order_items);
							$order_items = count($order_items);
							if(strlen($wpdb->last_error) > 0){
								echo $wpdb->last_error;
							}
							$wpdb->flush(); 
						}
						return $order_items;
						//return $order_items = count($order_items);					
					}
					
					if($type == 'limit_row'){
						$sql .= " LIMIT $start, $limit";
						$order_items = $wpdb->get_results($sql);
						//$this->print_array($order_items);
						$wpdb->flush(); 
						//$this->print_array($order_items);
					}
					
					if($type == 'all_row' or $type == 'all_row_total'){
						$order_items = $wpdb->get_results($sql);
						$wpdb->flush(); 
					}
					
					if(strlen($wpdb->last_error) > 0){
						echo $wpdb->last_error;
						return false;
					}
					
					//$date_format = get_option( 'date_format' );
					if($type == 'limit_row' || $type == 'all_row' or $type == 'all_row_total'){
						if(count($order_items)>0)
						foreach ( $order_items as $key => $order_item ) {
							
								$product_id								= $order_item->id;
								
								if(!isset($order_meta[$product_id])){
									$order_meta[$product_id]					= $this->get_all_post_meta($product_id);
								}
								
								//$order_item->product_date = date($date_format,strtotime($order_item->product_date));
								//$order_item->modified_date = date($date_format,strtotime($order_item->modified_date));
								
								foreach($order_meta[$product_id] as $k => $v){
									$order_items[$key]->$k			= $v;
								}
						}
					}
					
					return $order_items;
			
		}
		
		
		function get_product_list(){
			global $wpdb;
			$TotalOrderCount 	= 0;
			$TotalAmount 		= 0;
			$order_items 		= $this->ic_commerce_custom_report_normal_query('limit_row');
			
			if(count($order_items)>0):
				$admin_url 			= admin_url($this->constants['plugin_parent']['order_detail_url']);
				$columns 			= $this->grid_columns();
				$amount 			= array("gross_amount","discount_value","total_amount");
				$key 				= $this->constants['plugin_key']."save_stock_column";
				//$all_variation_columns = $this->get_all_variation();
				$columns 			= $this->grid_columns();
				$zero				= $this->price(0);			
				$total_pages 		= $this->ic_commerce_custom_report_normal_query('total_row');
				$item_sold_details	= array();
				$zero_sold			= $this->get_request('zero_sold',"no");
				$product_cats		= array();
				$product_types		= array();
				
				if($zero_sold=="yes"){
					if(isset($columns['variation_sold'])) unset($columns['variation_sold']);
				}
								
				$order_items = $this->get_grid_content($columns, $order_items);
				
				$columns			= apply_filters("ic_commerce_variation_stock_list_grid_columns",$columns);
				$order_items		= apply_filters("ic_commerce_variation_stock_list_grid_items",$order_items, $columns);
			
				?>                
                <div class="top_buttons"><?php $this->export_to_csv_button('top',$total_pages);?><div class="clearfix"></div></div>
				 <table style="width:100%" class="widefat">
					<thead>
                    	<tr>
                        <?php 
							$cells_status = array();
							foreach($columns as $key => $value):
								$class 		= $key;
								$display 	= '';
								$value 		= $value;											
								switch($key):
									case "sale_price":
									case "regular_price":
									case "otal_sales":
									case "total_sales":
									case "stock":
									case "variation_sold":									
										$class 	= 'amount';
										break;
									case "product_date":
									case "modified_date":
										$date_format 		= get_option( 'date_format' );
									default:
										$value = $value;
										break;													
								endswitch;
						?>
							<th class="<?php echo $class;?>"<?php echo $display;?>><?php echo $value;?></th>
						<?php endforeach;?>
                        	<th style="text-align:right"><?php _e("Edit", 'icwoocommerce_textdomains');?></th>
                    	</tr>
					</thead>
					<tbody>
						<?php					
							foreach ( $order_items as $key => $order_item ) {
								if($key%2 == 1){$alternate = "alternate ";}else{$alternate = "";};
								$TotalOrderCount++;
							?>
								<tr class="<?php echo $alternate."row_".$key;?>">
									<?php 
										$cells_status = array();
										foreach($columns as $key => $value):
											$class 		= $key;
											$display 	= '';
											$value 		= $value;											
											switch($key):
												case "product_name":
												case "Variable":
												case "product_id":
												case "variation_id":
												case "variation_name":
												case "downloadable":
												case "virtual":
												case "manage_stock":
												case "category_name":
												case "product_type_name":
													$value = isset($order_item->$key) ? $order_item->$key : '';
													break;
												case "variation_sku":
													//$variation_id 	= isset($order_item->variation_id) ? $order_item->variation_id : 0;
													$value = isset($order_item->$key) ? $order_item->$key : '';
													break;
												case "sale_price":
												case "regular_price":
													$value = isset($order_item->$key) ? $order_item->$key : 0;
													$value = $value >0 ? $this->price($value) : $zero;
													$class 	= 'amount';
													break;
												case "otal_sales":
												case "total_sales":
												case "stock":
												case "variation_sold":
													$value = isset($order_item->$key) ? $order_item->$key : 'Not Set';
													$value = ($value != 'Not Set') ? ($value + 0) : $value;
													$class 	= 'amount';
													break;
												case "product_date":
												case "modified_date":
													$value = isset($rvalue->$key) ? $rvalue->$key : '0000-00-00 00:00:00';
													$value = date($date_format,strtotime($value));
													break;
												default:													
													$value =  isset($order_item->$key) ? $order_item->$key : '';
													break;													
											endswitch;
									?>
										<td class="<?php echo $class;?>"<?php echo $display;?>><?php echo $value;?></td>
									<?php endforeach;?>							
								<td style="text-align:right"><?php echo $this->get_edit_link ($order_item->product_id)?></td>
							</tr>
						 <?php } ?>	
					<tbody>           
				</table>        
                <?php $this->total_count($TotalOrderCount, $TotalAmount,$total_pages );?>
				<?php
			else:
				echo '<p>'.__("No order found.", 'icwoocommerce_textdomains').'</p>';
			endif;
		}
		
		function get_product_data2($post_type = 'product', $post_status = 'no'){
			global $wpdb;
			
			if($post_status == "yes") $post_status == 'publish';
			
			if($post_status == "publish") $post_status == 'publish';
			
			$publish_order	= $this->get_request_default('publish_order',$post_status,true);//if publish display publish order only, no or null display all order
			
			$sql = "SELECT posts.ID AS id, posts.post_title AS label FROM `{$wpdb->prefix}posts` AS posts";
			
			$sql .= " 	
					LEFT JOIN  {$wpdb->prefix}term_relationships 	as term_relationships_product_type 	ON term_relationships_product_type.object_id		=	posts.ID 
					LEFT JOIN  {$wpdb->prefix}term_taxonomy 		as term_taxonomy_product_type 		ON term_taxonomy_product_type.term_taxonomy_id		=	term_relationships_product_type.term_taxonomy_id
					LEFT JOIN  {$wpdb->prefix}terms 				as terms_product_type 				ON terms_product_type.term_id						=	term_taxonomy_product_type.term_id";
			
			
			$sql .= " WHERE posts.post_type = '{$post_type}'";
			
			$sql .= " AND terms_product_type.name IN ('variable')";
			
			if($publish_order == 'publish' || $publish_order == 'trash')	$sql .= " AND posts.post_status = '".$publish_order."'";
			
			$sql .= " GROUP BY posts.ID ORDER BY posts.post_title";
			
			$products = $wpdb->get_results($sql);			
			
			return $products;
		}
		
		function get_variation_product_sku($post_type = 'product'){
			global $wpdb;
			
			$sql = "SELECT postmeta_sku.meta_value AS id, postmeta_sku.meta_value AS label FROM `{$wpdb->prefix}posts` AS posts";
			
			$sql .= " 	
					LEFT JOIN  {$wpdb->prefix}term_relationships 	as term_relationships_product_type 	ON term_relationships_product_type.object_id		=	posts.ID 
					LEFT JOIN  {$wpdb->prefix}term_taxonomy 		as term_taxonomy_product_type 		ON term_taxonomy_product_type.term_taxonomy_id		=	term_relationships_product_type.term_taxonomy_id
					LEFT JOIN  {$wpdb->prefix}terms 				as terms_product_type 				ON terms_product_type.term_id						=	term_taxonomy_product_type.term_id";
			
			$sql .= " LEFT JOIN {$wpdb->prefix}postmeta AS postmeta_sku ON postmeta_sku.post_id = posts.ID";
			
			$sql .= " WHERE posts.post_type = '{$post_type}' AND posts.post_status = 'publish'";
			
			$sql .= " AND terms_product_type.name IN ('variable')";
			
			$sql .= " AND postmeta_sku.meta_key = '_sku' AND LENGTH(postmeta_sku.meta_value) > 0";
			
			$sql .= " GROUP BY postmeta_sku.meta_value ORDER BY postmeta_sku.meta_value";
			
			$products = $wpdb->get_results($sql);
			
			return $products;
		}
		
		function get_variations_sku($post_type = 'product_variation'){
			global $wpdb;
			
			$sql = "SELECT postmeta_sku.meta_value AS id, postmeta_sku.meta_value AS label FROM `{$wpdb->prefix}posts` AS posts";
			
			$sql .= " LEFT JOIN {$wpdb->prefix}postmeta AS postmeta_sku ON postmeta_sku.post_id = posts.ID";
			
			$sql .= " WHERE posts.post_type = '{$post_type}' AND posts.post_status = 'publish'";
			
			$sql .= " AND postmeta_sku.meta_key = '_sku' AND LENGTH(postmeta_sku.meta_value) > 0";
			
			$sql .= " GROUP BY postmeta_sku.meta_value ORDER BY postmeta_sku.meta_value";
			
			$products = $wpdb->get_results($sql);
			
			return $products;
		}
		
		
		function get_category_data2($taxonomy = 'product_cat',$post_status = 'no', $count = true){
				global $wpdb;
				
				$post_status = $this->get_request_default('post_status',$post_status,true);
				if($post_status == "yes") $post_status == 'publish';
				
				$sql = "SELECT 
				terms.term_id AS id, terms.name AS label";
				
				if($count)
					$sql .= ", count(posts.ID) AS counts";
				
				$sql .= " FROM `{$wpdb->prefix}posts` AS posts				
				LEFT JOIN {$wpdb->prefix}term_relationships AS term_relationships ON term_relationships.object_id = posts.post_parent
				LEFT JOIN {$wpdb->prefix}term_taxonomy AS term_taxonomy ON term_taxonomy.term_taxonomy_id = term_relationships.term_taxonomy_id
				LEFT JOIN {$wpdb->prefix}terms AS terms ON terms.term_id = term_taxonomy.term_id";
				
				$sql .= " WHERE term_taxonomy.taxonomy = '{$taxonomy}'";				
				if($post_status == 'publish' || $post_status == 'trash')	$sql .= " AND posts.post_status = '".$post_status."'";
				
				$sql .= " 
				AND posts.post_parent > 0
				 AND posts.post_type = 'product_variation'
				GROUP BY terms.term_id
				ORDER BY terms.name ASC";
				
				$products_category = $wpdb->get_results($sql);
				return $products_category; 
		}
		
		var $product_link = array();
		function get_edit_link($id){
			if(isset($this->product_link[$id])){
				$product_link = $this->product_link[$id];
			}else{				
				$this->product_link[$id] =  '<a target="_blank" href="' . get_edit_post_link( $id, true ) . '" title="' . esc_attr( __( 'Edit this item' ,'icwoocommerce_textdomains' ) ) . '">' . __('Edit' ,'icwoocommerce_textdomains' ) . '</a>';
				$product_link = $this->product_link[$id];
			}
			
			return $product_link;
			
		}
		
		var $all_variation = array();
		function get_all_variation($variation = "all"){
			global $wpdb;			
			if(count($this->all_variation) == 0){
				$variation_attributes	= $this->get_request('variation_attributes',"-1");				
				$variation_attributes	= stripslashes($variation_attributes);
				
				$sql="SELECT  REPLACE(REPLACE(meta_key, 'attribute_', ''),'pa_','') as attributes  FROM {$wpdb->prefix}postmeta WHERE meta_key like 'attribute_%'";
				if($variation_attributes != "-1" and strlen($variation_attributes)>2){
					$sql .= " AND meta_key IN ('{$variation_attributes}')";
				}
				
				$sql .= " group by attributes ORDER BY attributes ASC";
				
				$order_items = $wpdb->get_results($sql);
				$product_variation = array();
				
				foreach ( $order_items as $key => $order_item ) {
					$variation_label	=	ucfirst($order_item->attributes);
					$variation_key		=	$order_item->attributes;
					$product_variation[$variation_key] =  $variation_label;
				}
				$this->all_variation = $product_variation;
			}else{
				$product_variation = $this->all_variation;
			}
			
			return $product_variation;
		}
		
		function get_product_variation($product_id){			
			global $wpdb;
			$sql = "SELECT meta_key, REPLACE(REPLACE(meta_key, 'attribute_', ''),'pa_','') AS attributes, meta_value 
					FROM  {$wpdb->prefix}postmeta as postmeta WHERE post_id ='".$product_id."'
					AND meta_key LIKE '%attribute_%'";
			$order_items = $wpdb->get_results($sql);
			
			$product_variation  =array(); 
			foreach ( $order_items as $key => $order_item ) {
				$variation_label	=	ucfirst($order_item->meta_value);
				$variation_key		=	$order_item->attributes;
				$product_variation[$variation_key] =  $variation_label;
			}
			return $product_variation;
		}
		
		function delete_all_between($beginning, $end, $string) {
		  $beginningPos = strpos($string, $beginning);
		  $endPos = strpos($string, $end);
		  if ($beginningPos === false || $endPos === false) {
			return $string;
		  }
		
		  $textToDelete = substr($string, $beginningPos, ($endPos + strlen($end)) - $beginningPos);
		
		  return str_replace($textToDelete, '', $string);
		}
		
		
		
		function downloadable_virtual()
		{
			// Downloadable/virtual
			$output = "<select name='product_subtype' id='product_subtype' class='product_id'>";
			$output .= '<option value="">'.__( 'Show all sub-types', 'icwoocommerce_textdomains' ).'</option>';
	
			$output .="<option value='downloadable' ";
			if ( isset( $_GET['product_subtype'] ) ) $output .= selected('downloadable', $_GET['product_subtype'], false);
			$output .=">".__( 'Downloadable', 'icwoocommerce_textdomains' )."</option>";
	
			$output .="<option value='virtual' ";
			if ( isset( $_GET['product_subtype'] ) ) $output .= selected('virtual', $_GET['product_subtype'], false);
			$output .=">".__( 'Virtual', 'icwoocommerce_textdomains' )."</option>";
	
			$output .="</select>";
	
			echo $output;
		}
		
		function product_type()
		{
			// Types
			$terms = get_terms('product_type');
			$output = "<select name='dropdown_product_type' id='dropdown_product_type'>";
			$output .= '<option value="">'.__( 'Show all product types', 'icwoocommerce_textdomains' ).'</option>';
			foreach($terms as $term) :
				$output .="<option value='" . sanitize_title( $term->name ) . "' ";
				if ( isset( $wp_query->query['product_type'] ) ) $output .=selected($term->slug, $wp_query->query['product_type'], false);
				$output .=">";
	
					// Its was dynamic but did not support the translations
					if( $term->name == 'grouped' ):
						$output .= __( 'Grouped product', 'icwoocommerce_textdomains' );
					elseif ( $term->name == 'external' ):
						$output .= __( 'External/Affiliate product', 'icwoocommerce_textdomains' );
					elseif ( $term->name == 'simple' ):
						$output .= __( 'Simple product', 'icwoocommerce_textdomains' );
					elseif ( $term->name == 'variable' ):
						$output .= __( 'Variable', 'icwoocommerce_textdomains' );
					else:
						// Assuming that we have other types in future
						$output .= ucwords($term->name);
					endif;
	
				$output .=" ($term->count)</option>";
			endforeach;
			$output .="</select>";
			
			echo $output;
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
		
		//New Change ID 20140918
		function get_product_type_by_product_id($id, $taxonomy = 'product_type', $termkey = 'name'){
			$term_name ="";			
			if(!isset($this->terms_by[$taxonomy][$id])){
				$id			= (integer)$id;
				$terms		= get_the_terms($id, $taxonomy);
				$termlist	= array();
				if($terms and count($terms)>0){
					foreach ( $terms as $term ) {
						
						if( $term->$termkey == 'grouped' )
							$product_type= 'Grouped product';
						elseif ( $term->$termkey == 'external' )
							$product_type=  'External/Affiliate product';
						elseif ( $term->$termkey == 'simple' )
							$product_type=  'Simple product';
						elseif ( $term->$termkey == 'variable' )
							$product_type= 'Variable';
						else
							$product_type=  ucwords($term->name);
							
							$termlist[] = $product_type;
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
		
		function total_count($TotalOrderCount, $TotalAmount, $total_pages 	= 0){
			global $wpdb, $back_day, $start,$OrderID,$FromDate ,$ToDate,$StatusID, $Email, $FirstName ,$txtProduct,$category_id,$limit,$page,$detail_view, $request, $all_row_total;
			
			$admin_page 		= $this->get_request('page');
			$limit	 			= $this->get_request('limit',15, true);
			$adjacents			= $this->get_request('adjacents',3);
			$detail_view		= $this->get_request('detail_view',"no");
			
			//$total_pages 	= $this->ic_commerce_custom_report_normal_query('total_row');
			
			$targetpage 	= "admin.php?page=".$admin_page;
			$create_pagination 	= $this->get_pagination($total_pages,$limit,$adjacents,$targetpage,$request);
			
			?>
				
				<table style="width:100%">
					<tr>
                    	<td valign="middle" class="grid_bottom_total">
                        	<?php $output = sprintf(__('Results: <strong>%1$s/%2$s</strong>'), $TotalOrderCount,$total_pages);echo $output;?>
						</td>
						<td>					
							<?php echo $create_pagination;?>
                        	<div class="clearfix"></div>
                            <div>
                        	<?php
								$this->export_to_csv_button('bottom',$total_pages);
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
		
		function export_to_csv_button($position = 'bottom', $total_pages = 0){
			global $request;
			//$admin_page 		= 	$this->get_request('page');
			$admin_page 		= 	$this->get_request('admin_page');
			//$admin_page_url 		= get_option('siteurl').'/wp-admin/admin.php';//Commented not work SSL admin site 20150212
			$admin_page_url 		= $this->constants['admin_page_url'];//Added SSL fix 20150212
			$mngpg 				= 	$admin_page_url.'?page='.$admin_page ;
			$request			=	$request = $this->get_all_request();
			$request['total_pages'] = $total_pages;	
			$request_			=	$request;
			
			unset($request['action']);
			//unset($request['page']);
			unset($request['p']);
			
			?>
            <div id="<?php echo $admin_page ;?>Export" class="RegisterDetailExport">
                <form id="<?php echo $admin_page ;?>_form" class="<?php echo $admin_page ;?>_form" action="<?php echo $mngpg;?>" method="post">
                    <?php foreach($request as $key => $value):?>
                    	<input type="hidden" name="<?php echo $key;?>" value="<?php echo $value;?>" />
                    <?php endforeach;?>
                    <input type="hidden" name="export_file_name" value="<?php echo $admin_page;?>" />
                    <input type="hidden" name="export_file_format" value="csv" />
                    <input type="submit" name="<?php echo $admin_page ;?>_export_csv" class="onformprocess" value="<?php _e("Export to CSV",'icwoocommerce_textdomains')?>" />
                    <input type="button" name="<?php echo $admin_page ;?>_export_pdf" class="onformprocess open_popup" value="<?php _e("Export to PDF",'icwoocommerce_textdomains');?>" data-format="pdf" data-popupid="export_pdf_popup" data-hiddenbox="popup_pdf_hidden_fields" data-popupbutton="Export to PDF" data-title="Export to PDF" />                 
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
            		<input type="button" name="backtoprevious" value="<?php _e('Back to Previous', 'icwoocommerce_textdomains')?>"  class="backtoprevious onformprocess" onClick="back_to_previous();" />
                </div>
            <?php
		}
		
		function ic_commerce_custom_report_page_export_csv(){
			$columns 				= $this->grid_columns();
			
			$export_rows			= $this->get_export_sheet_rows($columns);
			
			$today 					= date_i18n("Y-m-d-H-i-s");
			
			$export_file_name 		= $this->get_request('export_file_name',"no");
			
			$export_file_format 	= $this->get_request('export_file_format',"no");
			
			$filename 				= $export_file_name."-".$today.".".$export_file_format;	$this->unset_class_variables();
			
			/*New Added 20160130*/
			$today_date 			= $today;	
			$report_name			= '';			
			$export_filename 		= $export_file_name."-".$today_date.".".$export_file_format;
			$export_filename 		= apply_filters('ic_commerce_export_csv_excel_format_file_name',$export_filename,$report_name,$today_date,$export_file_name,$export_file_format);
									  do_action("ic_commerce_export_csv_excel_format",$export_filename,$export_rows,$columns,$export_file_format,$report_name);
			$output					= $this->get_export_sheet_content($export_rows,$columns);
			
			$export_rows			= $columns = $export_file_name = $export_file_format = NULL; unset($export_rows);unset($columns);unset($today);unset($export_file_name);//Unset Local Variable
			
			$this->unset_global_variables();
			
			header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
			header("Content-Length: " . strlen($output));	
			header("Content-type: text/x-csv");
			header("Content-type: text/csv");
			header("Content-type: application/csv");
			header("Content-Disposition: attachment; filename=$filename");
			echo $output;
			exit;
		}
		
		function ic_commerce_custom_report_page_export_pdf(){
			
			$columns 				= $this->grid_columns();
			
			$export_rows			= $this->get_export_pdf_rows($columns);
						
			$output					= $this->get_export_pdf_content($export_rows,$columns);
			
			$columns 				= NULL; unset($columns);//Unset Local Variable
			
			$this->export_to_pdf($export_rows,$output);			
		}
		
		function get_export_sheet_rows($columns = array()){
						
			$rows 				= $this->ic_commerce_custom_report_normal_query('all_row');
			$item_sold_details	= array();
			$zero_sold			= $this->get_request('zero_sold',"no");
			
			
			if($zero_sold=="yes"){
				if(isset($columns['variation_sold'])) unset($columns['variation_sold']);
			}
			
			$rows 				= $this->get_grid_content($columns, $rows);			
			$export_rows 		= array();
			
			$rows 				= apply_filters('ic_commerce_variation_stock_list_csv_items',$rows, $columns);
			
			$i = 0;
			if(count($rows) > 0){
				foreach ( $rows as $rkey => $rvalue ):
					$order_item 		=	$rvalue;					
					$value 				= 	"";
					foreach($columns as $key => $value):
						switch ($key) {
							case "product_name":
							case "Variable":
							case "product_id":
							case "variation_id":
							case "variation_name":
							case "downloadable":
							case "virtual":
							case "manage_stock":
							case "category_name":
							case "product_type_name":
							case "variation_sku":
							case "product_date":
							case "modified_date":
								$value = isset($order_item->$key) ? $order_item->$key : '';
								break;
							case "sale_price":
							case "regular_price":
								$value = isset($order_item->$key) ? $order_item->$key : 0;
								break;
							case "otal_sales":
							case "total_sales":
							case "stock":
							case "variation_sold":
								$value = isset($order_item->$key) ? trim($order_item->$key) : 'Not Set';
								$value = ($value != 'Not Set') ? ($value + 0) : $value;
								break;
							default:													
								$value =  isset($order_item->$key) ? $order_item->$key : '';
								break;
						}
						$export_rows[$i][$key] = $value;
					endforeach;
					$i++;
				endforeach;
			}
			
			return $export_rows;
		}
		
		function get_export_sheet_content($rows = array(),$columns = array()){
			$csv_terminated = "\n";
			$csv_separator = ",";
			$csv_enclosed = '"';
			$csv_escaped = "\\";
			$fields_cnt = count($columns); 
			$schema_insert = '';	
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
								//$schema_insert .= $csv_enclosed . $rows[$i][$key] . $key . $csv_enclosed;
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
		
		function get_export_pdf_rows($columns = array()){
			$rows 				= $this->ic_commerce_custom_report_normal_query('all_row');
			
			$zero					= $this->price(0);
			$export_rows 			= array();
			
			$item_sold_details		= array();
			$zero_sold				= $this->get_request('zero_sold',"no");
			
			if($zero_sold=="yes"){
				if(isset($columns['variation_sold'])) unset($columns['variation_sold']);
			}
			
			$rows 				= $this->get_grid_content($columns, $rows);
			$rows 				= apply_filters('ic_commerce_variation_stock_list_pdf_items',$rows, $columns);
					
			$export_rows 		= array();
			$date_format 		= get_option( 'date_format' );
			$i = 0;
			if(count($rows) > 0){
				foreach ( $rows as $rkey => $rvalue ):
					$order_item 		=	$rvalue;					
					$value 				= 	"";
					foreach($columns as $key => $value):
						switch ($key) {
							case "product_name":
							case "Variable":
							case "product_id":
							case "variation_id":
							case "variation_name":
							case "downloadable":
							case "virtual":
							case "manage_stock":
							case "category_name":
							case "product_type_name":
							case "variation_sku":
								$value = isset($order_item->$key) ? $order_item->$key : '';
								break;
							case "sale_price":
							case "regular_price":
								$value = isset($order_item->$key) ? $order_item->$key : 0;
								$value = $value >0 ? $this->price($value) : $zero;
								break;
							case "otal_sales":
							case "total_sales":
							case "stock":
							case "variation_sold":
								$value = isset($order_item->$key) ? $order_item->$key : 'Not Set';
								$value = ($value != 'Not Set') ? ($value + 0) : $value;
								break;
							case "product_date":
							case "modified_date":								
								$value = isset($rvalue->$key) ? $rvalue->$key : '0000-00-00 00:00:00';
								$value = date($date_format,strtotime($value));
								break;
							default:													
								$value =  isset($order_item->$key) ? $order_item->$key : '';
								break;
						}
						$export_rows[$i][$key] = $value;
					endforeach;
					$i++;
				endforeach;
			}
			
			return $export_rows;
		}
		
		function grid_columns(){
			$basic_column				= $this->get_request('basic_column',"no",true);
			
			if($basic_column == "yes"){
				$columns = array(					
					"variation_id"			=>	__("Variation ID", 	'icwoocommerce_textdomains')
					,"variation_sku"		=>	__("SKU", 			'icwoocommerce_textdomains')
					,"product_name"			=>	__("Product Name", 	'icwoocommerce_textdomains')
					,"category_name"		=>	__("Category", 		'icwoocommerce_textdomains')
				);
			}else{
				$columns = array(					
					"variation_id"			=>	__("Variation ID", 	'icwoocommerce_textdomains')
					,"variation_sku"		=>	__("SKU", 			'icwoocommerce_textdomains')
					,"product_name"			=>	__("Product Name", 	'icwoocommerce_textdomains')
					,"category_name"		=>	__("Category", 		'icwoocommerce_textdomains')
					//,"product_type_name"	=>	__("Product Type",	'icwoocommerce_textdomains')
					,"product_date"			=>	__("Created Date", 	'icwoocommerce_textdomains')			
					,"modified_date"		=>	__("Modified Date", 'icwoocommerce_textdomains')			
					,"downloadable"			=>	__("Downloadable", 	'icwoocommerce_textdomains')
					,"virtual"				=>	__("Virtual", 		'icwoocommerce_textdomains')
					,"manage_stock"			=>	__("Manage Stock",	'icwoocommerce_textdomains')
					,"backorders"			=>	__("Backorders",	'icwoocommerce_textdomains')								
					,"regular_price"		=>	__("Regular Price",	'icwoocommerce_textdomains')
					,"sale_price"			=>	__("Sale Price",	'icwoocommerce_textdomains')
				);
			}
			
			
			$variation_columns 	= $this->get_all_variation();
			$non_variation_columns	= $columns;
			$columns 				= array_merge((array)$columns, (array)$variation_columns);			
			$columns_stock			= array("stock"	=>	__("Stock", 'icwoocommerce_textdomains'));
			$columns 				= array_merge((array)$columns, (array)$columns_stock);			
			$columns 				= apply_filters('ic_commerce_variation_stock_page_columns',$columns, $basic_column, $non_variation_columns, $variation_columns);
			
			//$this->print_array($columns);//die;
			return $columns;
		}
		
		function get_all_request(){
			global $request, $back_day;
			if(!$this->request){
				$request 			= array();
				$start 				= 0;
				$p					= $this->get_request('p',1,true);
				$limit				= $this->get_request('limit',15,true);
				
				$product_id			= $this->get_request('product_id','-1',true);
				$category_id		= $this->get_request('category_id','-1',true);
				$product_sku		= $this->get_request('product_sku','-1',true);//New Change ID 20150226
				$variation_sku		= $this->get_request('variation_sku','-1',true);//New Change ID 20150226
				$product_type		= $this->get_request('product_type','-1',true);
				$category_id		= $this->get_request('category_id','-1',true);
				$product_id			= $this->get_request('product_id','-1',true);
				$variations			= $this->get_request('variations','-1',true);
				
				$sort_by		= $this->get_request('sort_by','product_name',true);
				$order_by		= $this->get_request('order_by','ASC',true);
				
				
				if($variations != '-1' and strlen($variations) > 0){
						$variations = $_REQUEST['variations'];
						$variations = explode(",",$variations);
						//$this->print_array($variations);
						$var = array();
						foreach($variations as $key => $value):
							$var[] .=  "attribute_pa_".$value;
							$var[] .=  "attribute_".$value;
						endforeach;
						$variations =  implode("', '",$var);
				}
				
				$_REQUEST['variation_attributes']= $variations;
				
				//New Change ID 20150309
				$zero_sold			= $this->get_request('zero_sold','no',true);
				$zero_stock			= $this->get_request('zero_stock','no',true);				
				$stock_status		= $this->get_request('stock_status','-1',true);
				$manage_stock		= $this->get_request('manage_stock','-1',true);
				if($p > 1){	$start = ($p - 1) * $limit;}
				
				$_REQUEST['start']			= $start;				
				
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
		
		var $variation_query = NULL;
		function get_sold_product_details($show_variation = 'variable', $item_id_string = "", $id_only = false){
			global $wpdb;
			
			$request = $this->get_all_request();extract($request);
			
			$columns_sql = " SELECT ";
		
			$columns_sql .= "
						woocommerce_order_items.order_item_name AS 'product_name'
						,woocommerce_order_items.order_item_id
						,SUM(woocommerce_order_itemmeta.meta_value) AS 'quantity'
						";
						
			//$columns_sql .= ", SUM(woocommerce_order_itemmeta6.meta_value) AS 'amount'";
			
			if($show_variation == 'variable') {
				$columns_sql .= ", woocommerce_order_itemmeta8.meta_value AS 'variation_id'";
			}else{
				$columns_sql .= ",woocommerce_order_itemmeta7.meta_value AS product_id";
			}
			
			
			if(!$this->variation_query){
				
				$order_status		= $this->get_string_multi_request('order_status',$order_status, "-1");
				$hide_order_status	= $this->get_string_multi_request('hide_order_status',$hide_order_status, "-1");
				
				$sql = "
							FROM {$wpdb->prefix}woocommerce_order_items as woocommerce_order_items						
							LEFT JOIN  {$wpdb->prefix}woocommerce_order_itemmeta as woocommerce_order_itemmeta ON woocommerce_order_itemmeta.order_item_id	= woocommerce_order_items.order_item_id";
							
				
				//$sql .= " LEFT JOIN  {$wpdb->prefix}woocommerce_order_itemmeta as woocommerce_order_itemmeta6 ON woocommerce_order_itemmeta6.order_item_id= woocommerce_order_items.order_item_id";
				
				if($order_status_id  && $order_status_id != "-1") {
						$sql .= " 	
							LEFT JOIN  {$wpdb->prefix}term_relationships	as term_relationships2 	ON term_relationships2.object_id	=	woocommerce_order_items.order_id
							LEFT JOIN  {$wpdb->prefix}term_taxonomy			as term_taxonomy2 		ON term_taxonomy2.term_taxonomy_id	=	term_relationships2.term_taxonomy_id
							LEFT JOIN  {$wpdb->prefix}terms					as terms2 				ON terms2.term_id					=	term_taxonomy2.term_id";
				}
				
				if($show_variation == 'variable'){
					$sql .= " LEFT JOIN  {$wpdb->prefix}woocommerce_order_itemmeta as woocommerce_order_itemmeta8 ON woocommerce_order_itemmeta8.order_item_id = woocommerce_order_items.order_item_id";
				}else{
					$sql .= " LEFT JOIN  {$wpdb->prefix}woocommerce_order_itemmeta as woocommerce_order_itemmeta7 ON woocommerce_order_itemmeta7.order_item_id= woocommerce_order_items.order_item_id";
				}
				
				$sql .= " LEFT JOIN  {$wpdb->prefix}posts as shop_order ON shop_order.id=woocommerce_order_items.order_id";//For shop_order
				
				if($show_variation == 2 || ($show_variation == 'grouped' || $show_variation == 'external' || $show_variation == 'simple' || $show_variation == 'variable_')){
					$sql .= " 	
							LEFT JOIN  {$wpdb->prefix}term_relationships 	as term_relationships_product_type 	ON term_relationships_product_type.object_id		=	woocommerce_order_itemmeta7.meta_value 
							LEFT JOIN  {$wpdb->prefix}term_taxonomy 		as term_taxonomy_product_type 		ON term_taxonomy_product_type.term_taxonomy_id		=	term_relationships_product_type.term_taxonomy_id
							LEFT JOIN  {$wpdb->prefix}terms 				as terms_product_type 				ON terms_product_type.term_id						=	term_taxonomy_product_type.term_id";
				}
							
				$sql .= "
							WHERE woocommerce_order_itemmeta.meta_key	= '_qty'
							AND shop_order.post_type					= 'shop_order'";
				
				//$sql .= " AND woocommerce_order_itemmeta6.meta_key	= '_line_total'";
							
				if($show_variation == 'variable'){
					$sql .= "
							AND woocommerce_order_itemmeta8.meta_key = '_variation_id' 
							AND (woocommerce_order_itemmeta8.meta_value IS NOT NULL AND woocommerce_order_itemmeta8.meta_value > 0)
							";
							
					if(strlen($item_id_string) > 0){
						$sql .= " AND woocommerce_order_itemmeta8.meta_value IN ($item_id_string)";
					}
				}else{
					$sql .= " AND woocommerce_order_itemmeta7.meta_key 	= '_product_id'	";
					if(strlen($item_id_string) > 0){
						$sql .= " AND woocommerce_order_itemmeta7.meta_value IN ($item_id_string)";
					}
				}
				
				if ($start_date != NULL &&  $end_date !=NULL){
					$sql .= " 
							AND (DATE(shop_order.post_date) BETWEEN '".$start_date."' AND '". $end_date ."')";
				}
				
				if($order_status_id  && $order_status_id != "-1") 
					$sql .= " 
							AND terms2.term_id IN (".$order_status_id .")";
				
				
				if($show_variation == 'grouped' || $show_variation == 'external' || $show_variation == 'simple' || $show_variation == 'variable_'){
					$sql .= " AND terms_product_type.name IN ('{$show_variation}')";
				}
				
				if($show_variation == 2){
					$sql .= " AND terms_product_type.name IN ('simple')";
				}
				
				if($order_status  && $order_status != '-1' and $order_status != "'-1'")$sql .= " AND shop_order.post_status IN (".$order_status.")";
				if($hide_order_status  && $hide_order_status != '-1' and $hide_order_status != "'-1'")$sql .= " AND shop_order.post_status NOT IN (".$hide_order_status.")";
				
				if($show_variation == 'variable'){
					$sql .= " GROUP BY woocommerce_order_itemmeta8.meta_value ORDER BY woocommerce_order_itemmeta8.meta_value ASC ";
				}else{
					$sql .= " GROUP BY woocommerce_order_itemmeta7.meta_value ORDER BY woocommerce_order_itemmeta7.meta_value ASC ";
				}
				
				$this->variation_query = $sql;
				
				$sql = "";
				
			}else{
				$sql = $this->variation_query;
			}
			
			$sql = $columns_sql;		
			$sql .= $this->variation_query;			
			$items = $wpdb->get_results($sql);
			
			if($id_only) return $items;
			
			$new_items = array();
			
			if(count($items) > 0){
				if($show_variation == 'variable'){
					foreach($items as $key => $value){
						$new_items[$value->variation_id]['quantity'] = $value->quantity;
						//$new_items[$value->variation_id]['amount'] = $value->amount;
					}
				}else{
					foreach($items as $key => $value){
						$new_items[$value->product_id]['quantity'] = $value->quantity;
						//$new_items[$value->product_id]['amount'] = $value->amount;
					}
				}
			}
			return $new_items;
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
					
					$order_items = $wpdb->get_results($sql);
					
					if(count($order_items) > 0){
						foreach($order_items as $key => $value){
							if(isset($item_name[$value->item_id]))
								$item_name[$value->item_id] = $item_name[$value->item_id].", " . $value->item_name;
							else
								$item_name[$value->item_id] = $value->item_name;
						}
					}
				}
				
				return $item_name;
		}
		
		function get_product_variations($order_id_string = array()){			
			global $wpdb;
			
			if(is_array($order_id_string)){
				$order_id_string = implode(",",$order_id_string);
			}
				
			$sql = "SELECT meta_key, REPLACE(REPLACE(meta_key, 'attribute_', ''),'pa_','') AS attributes, meta_value, post_id as variation_id
					FROM  {$wpdb->prefix}postmeta as postmeta WHERE 
					meta_key LIKE '%attribute_%'";
			
			if(strlen($order_id_string) > 0){
				$sql .= " AND post_id IN ({$order_id_string})";
				//$sql .= " AND post_id IN (23)";
			}
			
			$order_items 		= $wpdb->get_results($sql);
			
			$product_variation  = array(); 
			if(count($order_items)>0){
				foreach ( $order_items as $key => $order_item ) {
					$variation_label	=	ucfirst($order_item->meta_value);
					$variation_key		=	$order_item->attributes;
					$variation_id		=	$order_item->variation_id;
					$product_variation[$variation_id][$variation_key] =  $variation_label;
				}
			}
			return $product_variation;
		}
		
		function get_variation_parent_sku($order_id_string = array()){			
			global $wpdb;
			
			if(is_array($order_id_string)){
				$order_id_string = implode(",",$order_id_string);
			}
				
			$sql = "SELECT meta_value, post_id as product_id
					FROM  {$wpdb->prefix}postmeta as postmeta WHERE 
					meta_key = '_sku' AND LENGTH(meta_value)>0";
			
			if(strlen($order_id_string) > 0){
				$sql .= " AND post_id IN ({$order_id_string})";
			}
			
			$sql .= " GROUP BY post_id";
			
			$order_items 		= $wpdb->get_results($sql);
			
			$product_variation  = array(); 
			if(count($order_items)>0){
				foreach ( $order_items as $key => $order_item ) {
					$product_id		=	$order_item->product_id;
					$meta_value		=	$order_item->meta_value;
					$product_variation[$product_id] =  $meta_value;
				}
			}
			return $product_variation;
			
		}
		
		function get_grid_content($columns = array(),$rows = array()){
			
			$new_rows 				= $rows;			
			$item_sold_details		= array();
			$variation_ids			= $this->get_items_id_list($rows,'variation_id');
			$product_variations 	= $this->get_product_variations($variation_ids);
			
			$columns 				= apply_filters('ic_commerce_variation_stock_page_grid_content_colums',$columns, $product_variations);
			$rows 					= apply_filters('ic_commerce_variation_stock_page_before_grid_content_items',$rows, $columns, $product_variations);
			
			foreach($columns as $key => $value):			
				switch($key):
					case "variation_sold":
							if($zero_sold!="yes"):
								$item_sold_details	= $this->get_sold_product_details('variable',$variation_ids);								
							endif;
						break;
					case "category_name":
						$product_ids		= isset($product_ids)	? $product_ids : $this->get_items_id_list($rows,'product_id');
						$product_cats 		= $this->get_term_names_by_id($product_ids,'product_cat');
						break;
					case "product_type_name":
						$product_ids		= isset($product_ids)	? $product_ids : $this->get_items_id_list($rows,'product_id');
						$product_types 		= $this->get_term_names_by_id($product_ids,'product_type');
						break;
					case "variation_sku":
						$product_ids			= isset($product_ids)	? $product_ids : $this->get_items_id_list($rows,'product_id');
						$variation_ids			= isset($variation_ids)	? $variation_ids : $this->get_items_id_list($rows,'variation_id');
						$variation_parent_sku 	= $this->get_variation_parent_sku($variation_ids);
						$product_sku 			= $this->get_variation_parent_sku($product_ids);
						break;
					case "product_date":
					case "modified_date":
						$date_format = get_option( 'date_format' );
						break;
				endswitch;
			endforeach;
			
			//$this->print_array($variation_parent_sku);
			
			foreach ( $rows as $rkey => $rvalue ):
				$order_item 		=	$rvalue;
				$product_variation 	= isset($product_variations[$order_item->variation_id]) ? $product_variations[$order_item->variation_id] : array();
				$value 				= 	"";
				foreach($columns as $key => $value):					
					switch ($key) {
						case "product_name":
						case "Variable":
						case "product_id":
						case "variation_id":
						case "variation_name":
						case 'regular_price':
						case 'sale_price':
						case "otal_sales":
						case "total_sales":
						case "product_date":
						case "modified_date":
							$value = isset($rvalue->$key) ? $rvalue->$key : '';
							break;
						case "stock":
							$value = isset($rvalue->$key) ? $rvalue->$key : '';
							$value = empty($value) ? "" : ($value + 0);
							break;
						case 'product_type_name':
							$value = isset($product_types[$order_item->product_id]) ? $product_types[$order_item->product_id] : '';
							$value = ucfirst($value);
							break;
						case 'category_name':
							$value = isset($product_cats[$order_item->product_id]) ? $product_cats[$order_item->product_id] : '';
							break;						
						case "downloadable":
						case "virtual":
						case "manage_stock":
							$value = isset($order_item->$key) ? strtoupper($order_item->$key) : 'NO';							
							break;
						case "variation_sold":
							$variation_id = isset($order_item->variation_id) ? $order_item->variation_id : '';
							$value = isset($item_sold_details[$variation_id]['quantity']) ? $item_sold_details[$variation_id]['quantity'] : 0;							
						case "variation_sku":
							$value = isset($order_item->$key)  ? trim($order_item->$key) : '';
							$variation_id = isset($order_item->variation_id) ? $order_item->variation_id : 0;
							$product_id = isset($order_item->product_id) ? $order_item->product_id : '';
							$value = strlen($value)>0  ? $value : (isset($variation_parent_sku[$variation_id]) ? $variation_parent_sku[$variation_id] : (isset($product_sku[$product_id]) ? $product_sku[$product_id] : get_post_meta($product_id,'_sku',true)));
							break;
						case "backorders":
							$value = isset($order_item->$key) ? $order_item->$key : 'no';
							$value = $value == 'no' ? 'Do not allow' : ($value == 'notify' ? 'Allow with notify' : "Allow");
							break;
						case "product_date":
						case "modified_date":
							$value = isset($rvalue->$key) ? $rvalue->$key : '0000-00-00 00:00:00';
							$value = date($date_format,strtotime($value));
							break;
						default:
							$value =  isset($product_variation[$key]) ? $product_variation[$key] : (isset($order_item->$key) ? $order_item->$key : '-');
							break;
					}
					$new_rows[$rkey]->$key = $value;
				endforeach;
			endforeach;	
			
			$new_rows	= apply_filters('ic_commerce_variation_stock_page_before_grid_content_items',$new_rows, $columns, $product_variations);
					
			return $new_rows;
		}
		
		function get_product_variations_($order_id_string = array()){			
			global $wpdb;
			
			if(is_array($order_id_string)){
				$order_id_string = implode(",",$order_id_string);
			}
				
			$sql = "SELECT meta_key, REPLACE(REPLACE(meta_key, 'attribute_', ''),'pa_','') AS attributes, meta_value, post_id as variation_id
					FROM  {$wpdb->prefix}postmeta as postmeta WHERE 
					meta_key LIKE '%attribute_%'";
			
			if(strlen($order_id_string) > 0){
				$sql .= " AND post_id IN ({$order_id_string})";
				//$sql .= " AND post_id IN (23)";
			}
			
			$order_items 		= $wpdb->get_results($sql);
			
			$product_variation  = array(); 
			if(count($order_items)>0){
				foreach ( $order_items as $key => $order_item ) {
					$variation_label	=	ucfirst($order_item->meta_value);
					$variation_key		=	$order_item->attributes;
					$variation_id		=	$order_item->variation_id;
					$product_variation[$variation_id][$variation_key] =  $variation_label;
				}
			}
			return $product_variation;
		}
		
	}
}
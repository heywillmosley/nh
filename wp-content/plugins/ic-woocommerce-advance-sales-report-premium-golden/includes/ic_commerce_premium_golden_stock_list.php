<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
if ( ! class_exists( 'IC_Commerce_Premium_Golden_Stock_List_report' ) ) {
	require_once('ic_commerce_premium_golden_fuctions.php');
	class IC_Commerce_Premium_Golden_Stock_List_report extends IC_Commerce_Premium_Golden_Fuctions{
		
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
			
			if($admin_page == $this->constants['plugin_key']."_email_alert_simple_products"){
				$_REQUEST['manage_stock'] 	= "yes";
				$_REQUEST['sort_by'] 		= "stock";
			}			
			$manage_stock	= $this->get_request('manage_stock','-1',true);
			$sort_by		= $this->get_request('sort_by','product_name',true);
			$order_by		= $this->get_request('order_by','ASC',true);
			
			$product_id		= $this->get_request('product_id','-1',true);
			$category_id	= $this->get_request('category_id','-1',true);
			$ProductTypeID	= $this->get_request('ProductTypeID',NULL,true);			
			$product_subtype= $this->get_request('product_subtype','-1',true);			
			
						
			$optionsid	= "per_row_stock_page";
			$per_page 	= $this->get_number_only($optionsid,$this->per_page_default);			
			$action		= $this->get_request('action',$this->constants['plugin_key'].'_wp_ajax_action',true);
			$page_title = __("Stock List",'icwoocommerce_textdomains');
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
                                        <div class="label-text"><label for="txtMaxStock"><?php _e("Max Stock:",'icwoocommerce_textdomains');?></label></div>
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
													$this->create_dropdown($stock_status,"stock_status","stock_status","All","stock_status",'-1', 'array');
											?>
                                        </div>
                                    </div>
                                    <div class="FormRow">
                                        <div class="label-text"><label for="manage_stock"><?php _e("Manage Stock:",'icwoocommerce_textdomains');?></label></div>
                                        <div class="input-text">
                                        	<?php 
													$manage_stocks = array("yes" => __("Include items whose stock is mannaged",'icwoocommerce_textdomains'), "no" => __("Include items whose stock is not mannaged",'icwoocommerce_textdomains'));
													$this->create_dropdown($manage_stocks,"manage_stock","manage_stock","All","manage_stock",$manage_stock, 'array');
											?>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                   <div class="FormRow FirstRow">
                                        <div class="label-text"><label for="product_id"><?php _e("Product:",'icwoocommerce_textdomains');?></label></div>
                                        <div class="input-text">
                                        	<?php 
													$product_data = $this->get_product_data2('product','publish');//Purchase products
													$this->create_dropdown($product_data,"product_id[]","product_id2","All Product","product_id",$product_id, 'object', true, 5);
											?>
                                        </div>
                                    </div>
                                    <div class="FormRow">
                                        <div class="label-text"><label for="category_id"><?php _e("Category:",'icwoocommerce_textdomains');?></label></div>
                                        <div class="input-text">
                                        	<?php 
													$category_data = $this->get_category_data2('product_cat','no',false);
													$this->create_dropdown($category_data,"category_id","category_id2","All Category","category_id2",$category_id, 'object', true, 5);
											?>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="form-group"> 
                                	<?php if($admin_page == $this->constants['plugin_key']."_email_alert_simple_products"){
										echo "<input type=\"hidden\" name=\"product_type\" value=\"simple\">";
									}else {?>		
                                    <div class="FormRow FirstRow">
                                        <div class="label-text"><label for="product_type"><?php _e("Product Type:",'icwoocommerce_textdomains');?></label></div>
                                        <div class="input-text">
                                        	<?php $this-> product_type();?>
                                        </div>
                                    </div>
                                    
                                    <?php
										}
																			
										$product_sku_data = $this->get_all_product_sku();
										if($product_sku_data){
									?>  
                                	<div class="FormRow">
                                        <div class="label-text"><label for="product_sku"><?php _e("Product SKU:",'icwoocommerce_textdomains');?></label></div>
                                        <div class="input-text">
                                            <?php 
                                                $product_sku_data = $this->get_all_product_sku();
                                                $this->create_dropdown($product_sku_data,"product_sku[]","product_sku","Select All","product_sku",'-1', 'object', true, 5);
                                            ?>
                                        </div>                                                        
                                    </div>
                                    <?php } ?>

                                </div>
                                
                                 <div class="form-group">
                                    <div class="FormRow FirstRow">
                                    <div class="label-text" style="padding-top:0px;"><label for="sort_by"><?php _e("Order By:",'icwoocommerce_textdomains');?></label></div>
                                        <div style="padding-top:0px;">
                                         <?php
                                            $data = array("product_name" => __("Product Name",'icwoocommerce_textdomains'),"stock" => __("Stock",'icwoocommerce_textdomains'));
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
                                        <div style="padding-top:0px;"><input type="checkbox" id="zero_stock" name="zero_stock" maxlength="100"  value="yes" <?php if($this->get_request('zero_stock','no',true) == "yes"){ echo ' checked="checked"';}?> /><label for="zero_stock"> <strong>Include items having 0 stock</strong></label></div>
                                    </div>
                                </div>
                                
                               
                                
                                <div class="form-group">
                                    <div class="FormRow">
                                        <input type="hidden" name="zero_sold" 		id="zero_sold" 		value="<?php echo $this->get_request('zero_sold','no',true);?>" />
                                        <input type="hidden" name="publish_order" 	id="publish_order" 	value="<?php echo $this->get_request('post_type','no',true);?>" />
                                        <input type="hidden" name="post_type" 		id="post_type" 		value="<?php echo $this->get_request('publish_order','no',true);?>" />
                                        <input type="hidden" name="limit"  			id="limit" 			value="<?php echo $this->get_request('limit',$per_page,true);?>" />
                                        <input type="hidden" name="p"  				id="p" 				value="<?php echo $this->get_request('p',1,true);?>" />                                                            
                                        <input type="hidden" name="adjacents"  		id="adjacents" 		value="<?php echo $this->get_request('adjacents','3',true);?>" />
                                        <input type="hidden" name="do_action_type" 	id="do_action_type" value="<?php echo $this->get_request('do_action_type','stock_page',true);?>" />
                                        <input type="hidden" name="action" 			id="action" 		value="<?php echo $this->get_request('action',$this->constants['plugin_key'].'_wp_ajax_action',true);?>" />
                                        <input type="hidden" name="admin_page"		id="admin_page" 	value="<?php echo $this->get_request('page',$admin_page,true);?>" />
                                        <input type="hidden" name="page"			id="page" 			value="<?php echo $this->get_request('page',$admin_page,true);?>" />
                                        <input type="hidden" name="page_title"		id="page_title"		value="<?php echo $this->get_request('page_title',$page_title,true);?>" />
                                        <input type="hidden" name="total_pages"  	id="total_pages" 	value="<?php echo $this->get_request('total_pages',0,true);?>" />
                                        
                                        
                                        
                                        <span class="submit_buttons"><input name="SearchOrder" id="SearchOrder" class="onformprocess searchbtn" value="<?php _e("Search Product",'icwoocommerce_textdomains');?>" type="submit">  &nbsp; &nbsp; &nbsp; <span class="ajax_progress"></span></span>
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
                        <style type="text/css">
                        	td.sale_price, td.regular_price,
							th.sale_price, th.regular_price,
							
							td.otal_sales,
							th.otal_sales,
							td.stock, 
							th.stock, td.td_right{ text-align:right;}
                        </style>
            <?php
	 
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
			$output = "<select name='product_type' id='product_type'>";
			$output .= '<option value="-1">'.__( 'Show all product types', 'icwoocommerce_textdomains' ).'</option>';
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
		
		
		function get_product_list()
		{
			global $wpdb;
			$TotalOrderCount 	= 0;
			$TotalAmount 		= 0;
			$order_items 		= $this->ic_commerce_custom_report_normal_query('limit_row');
			$admin_url 			= admin_url($this->constants['plugin_parent']['order_detail_url']);
			$columns 			= $this->grid_columns();
			$amount 			= array("gross_amount","discount_value","total_amount");
			$key 				= $this->constants['plugin_key']."save_stock_column";
			$date_format		= get_option( 'date_format' );
			$product_url		= admin_url("post.php?action=edit")."&post=";
			
			if($this->constants['plugin_key'] == "icwoocommercevariation")					
				$clickeble 			= false;
			else
				$clickeble 			= true;
			
			$columns			= apply_filters("ic_commerce_stock_list_grid_columns",$columns);
			$order_items		= apply_filters("ic_commerce_stock_list_grid_items",$order_items, $columns);
			
			if(count($order_items)>0):
				$total_pages 	= $this->ic_commerce_custom_report_normal_query('total_row');
				?>
                <div class="top_buttons"><?php $this->export_to_csv_button('top',$total_pages);?><div class="clearfix"></div></div>
				 <table style="width:100%" class="widefat">
							<thead>
								<tr class="first">
                                	<?php
										$header = "";
                                    	foreach($columns as $key => $value):
											$header .= "<th class=\"{$key}\">{$value}</th>\n";
										endforeach;
										$header .= "<th class=\"edit\">Edit</th>\n";
										echo $header;
									?>
								</tr>
                                
							</thead>
							<tbody>
								<?php					
								foreach ( $order_items as $key => $order_item ) {
									//$TotalAmount =  $TotalAmount + $order_item->amount;
									$TotalOrderCount++;
									if($key%2 == 1){$alternate = "alternate ";}else{$alternate = "";};
									?>
									<tr class="<?php echo $alternate."row_".$key;?>">
                                    	<?php
											$trbody = "";
											$variation = array();
											foreach($columns as $key => $value):
												
												switch($key){
													case "variation_id":
														$v = isset($order_item->$key) ? $this->get_stock($order_item->$key) : '';
														break;
													case "variation_stock":													
														$v = get_post_meta($order_item->variation_id,"_stock",true);
														$v = (strlen($v)>0) ? $v : (isset($order_item->stock) ? $this->get_stock($order_item->stock) : 'Not Set');
														break;
													case "product_stock":													
														$v = isset($order_item->stock) ? ($this->get_stock($order_item->stock) + 0) : 'Not Set';
														break;
													case "product_sku":													
														$v = isset($order_item->sku) ? $this->get_stock($order_item->sku) : 'Not Set';
														break;
													case "variation_sku":													
														$v = get_post_meta($order_item->variation_id,"_sku",true);
														$v = (strlen($v)>0) ? ($v + 0) : (isset($order_item->sku ) ? $this->get_stock($order_item->sku) : 'Not Set');
														break;
													case "product_name":
														if($clickeble)
															$v = " <a href=\"{$admin_url}{$order_item->product_id}\" target=\"_blank\">{$order_item->product_name}</a>";
														else
															$v = $order_item->product_name;
														break;
														
													case "product_id":														
														$v = $order_item->product_id;
														break;
													case "quantity":
														$v = $order_item->quantity;
														
														break;
													case "amount":
														$v = $this->price($order_item->amount);
														
														break;
													case "product_variation":
														$v = $this->get_variation_comma_separated($order_item->order_item_id,$variation_attributes,$all_attributes);														
														break;	
														
														
													case "product_type_name":
														$v = $this->get_product_type_by_product_id($order_item->product_id);
														break;
														
													case "category_name":
														$v = $this->get_category_name_by_product_id($order_item->product_id);
														break;		
													case "stock":
														$v = isset($order_item->$key) ? trim($order_item->$key) : '';
														$v = empty($v) ? "" : ($v + 0);
														break;
													case "total_sales":
													case "otal_sales":
														$v = isset($order_item->$key) ? $order_item->$key : '';
														break;
													
													case "downloadable":
													case "virtual":
													case "manage_stock":
														$v = isset($order_item->$key) ? $order_item->$key : 'no';
														$v = $v == 'yes' ? 'Yes' : 'No';
														break;
													
													case "stock_status":
														$v = isset($order_item->$key) ? $order_item->$key : ' instock';
														$v = $v == 'instock' ? '<span style="color:green">In stock</span>' : '<span style="color:red">Out of stock</span>';
														break;
														
													case "backorders":
														$v = isset($order_item->$key) ? $order_item->$key : 'no';
														$v = $v == 'no' ? 'Do not allow' : ($v == 'notify' ? 'Allow with notify' : "Allow");
														break;
													
													
													
													case "regular_price":
													case "sale_price":
														$v = isset($order_item->$key) ? $order_item->$key : 0;
														$v = $this->price($v);
														break;
													
													case 'product_date':
													case 'modified_date':
														$v = isset($order_item->$key) ? date($date_format,strtotime($order_item->$key)) : '';
														break;
																											
													default:																													
														$v = isset($order_item->$key) ? $order_item->$key : '';
														break;
												}
												$trbody .= "<td class=\"{$key}\">{$v}</td>\n";
											endforeach;											
											echo $trbody;
										?>                                        
                                        <td class="td_right"><a href="<?php echo $product_url.$order_item->product_id;?>" target="_blank"><?php _e("Edit",'icwoocommerce_textdomains');?></a></td>
                                   </tr>
									<?php 
								} ?>
							<tbody>           
						</table>
                <?php $this->total_count($TotalOrderCount, $TotalAmount,$total_pages );?>
				<?php
			else:
				echo '<p>'.__("No order found.",'icwoocommerce_textdomains').'</p>';
			endif;
			
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
					
					$request 			= $this->get_all_request();extract($request);					
					$product_sku		= $this->get_string_multi_request('product_sku',$product_sku, "-1");//New Change ID 20150226
					$manage_stock		= $this->get_string_multi_request('manage_stock',$manage_stock, "-1");//New Change ID 20150309
					$stock_status		= $this->get_string_multi_request('stock_status',$stock_status, "-1");//New Change ID 20150309
					$sku_number			= trim($sku_number);
					
					//$this->print_array($request);
					
					if($type == 'total_row'){
						$sql_column = "SELECT count(posts.ID)";
					}else{
						$sql_column = "SELECT 
						posts.post_title as product_name
						,posts.post_date as product_date
						,posts.post_modified as modified_date
						,posts.ID as product_id";
					}
					
					if(!$this->normal_sql_query){
						$sql = " FROM 	{$wpdb->prefix}posts as posts ";
						
						if($product_subtype=="virtual") 						$sql .= " LEFT JOIN  {$wpdb->prefix}postmeta as virtual 			ON virtual.post_id			=posts.ID";
						if($product_subtype=="downloadable") 					$sql .= " LEFT JOIN  {$wpdb->prefix}postmeta as downloadable		ON downloadable.post_id		=posts.ID";
						if($sku_number || ($product_sku and $product_sku != '-1')) $sql .= " LEFT JOIN  {$wpdb->prefix}postmeta as sku 				ON sku.post_id				=posts.ID";
						
						if($product_stock || $txtMinStock || $txtMaxStock || $zero_stock == "yes" || $sort_by == "stock") 		$sql .= " LEFT JOIN  {$wpdb->prefix}postmeta as stock 				ON stock.post_id			=posts.ID";//Modified Change ID 20150309
						
						if($category_id and $category_id != "-1"){
							$sql .= " LEFT JOIN  {$wpdb->prefix}term_relationships as term_relationships ON term_relationships.object_id=posts.ID
							LEFT JOIN  {$wpdb->prefix}term_taxonomy as term_taxonomy ON term_taxonomy.term_taxonomy_id=term_relationships.term_taxonomy_id
							LEFT JOIN  {$wpdb->prefix}terms as terms ON terms.term_id=term_taxonomy.term_id";
						}
						
						if($product_type and $product_type != "-1"){
							$sql .= " 	
									LEFT JOIN  {$wpdb->prefix}term_relationships 	as term_relationships_product_type 	ON term_relationships_product_type.object_id		=	posts.ID 
									LEFT JOIN  {$wpdb->prefix}term_taxonomy 		as term_taxonomy_product_type 		ON term_taxonomy_product_type.term_taxonomy_id		=	term_relationships_product_type.term_taxonomy_id
									LEFT JOIN  {$wpdb->prefix}terms 				as terms_product_type 				ON terms_product_type.term_id						=	term_taxonomy_product_type.term_id";
						}
						
						
						if($zero_sold=="yes") 						$sql .= " LEFT JOIN  {$wpdb->prefix}postmeta as total_sales 			ON total_sales.post_id			=posts.ID";//New Change ID 20150309				
						if($stock_status and $stock_status != '-1') $sql .= " LEFT JOIN  {$wpdb->prefix}postmeta as stock_status 			ON stock_status.post_id			=posts.ID";//New Change ID 20150309
						if($manage_stock and $manage_stock != '-1') $sql .= " LEFT JOIN  {$wpdb->prefix}postmeta as manage_stock 			ON manage_stock.post_id			=posts.ID";//New Change ID 20150309												
						
						$sql .= " WHERE  posts.post_type='product' AND posts.post_status = 'publish'";
						
						if($product_stock || $txtMinStock || $txtMaxStock || $zero_stock == "yes" || $sort_by == "stock") 		$sql .= " AND stock.meta_key ='_stock'";
						if($sku_number || ($product_sku and $product_sku != '-1'))						$sql .= " AND sku.meta_key ='_sku'";
						if($product_subtype=="downloadable") 		$sql .= " AND downloadable.meta_key ='_downloadable'";					
						if($product_subtype=="virtual") 			$sql .= " AND virtual.meta_key ='_virtual'";	
										
						
						
						if($product_name) 							$sql .= " AND posts.post_title like '%{$product_name}%'";
						if($product_id and $product_id >0) 			$sql .= " AND posts.ID IN ({$product_id})";
						if($product_stock) 							$sql .= " AND stock.meta_value IN ({$product_stock})";
						//if($sku_number) 							$sql .= " AND sku.meta_value like '%{$sku_number}%'";
						if($txtMinStock) 							$sql .= " AND stock.meta_value >= {$txtMinStock}";
						if($txtMaxStock) 							$sql .= " AND stock.meta_value <= {$txtMaxStock}";
						if($product_subtype=="downloadable") 		$sql .= " AND downloadable.meta_value = 'yes'";					
						if($product_subtype=="virtual") 			$sql .= " AND virtual.meta_value = 'yes'";					
						if($category_id and $category_id != "-1") 	$sql .= " AND terms.term_id = {$category_id}";
						
						if($product_type and $product_type != "-1")	$sql .= " AND terms_product_type.name IN ('{$product_type}')";						
						
						if($product_sku and $product_sku != '-1'){
							if(strlen($sku_number) > 0) {
								$sql .= " AND (sku.meta_value like '%{$sku_number}%' OR  sku.meta_value IN (".$product_sku.") )";
							}else{
								$sql .= " AND sku.meta_value IN (".$product_sku.")";
								//$sql .= " AND sku.meta_value = ".$product_sku;
							}
						}else{
							if(strlen($sku_number) > 0) 	$sql .= " AND sku.meta_value like '%{$sku_number}%'";
						}
						
						if($zero_stock == "yes")	$sql .= " AND (stock.meta_value <= 0 OR LENGTH(stock.meta_value) <= 0)";//New Change ID 20150309
						if($zero_sold=="yes")		$sql .= " AND total_sales.meta_key ='total_sales' AND (total_sales.meta_value <= 0 OR LENGTH(total_sales.meta_value) <= 0)";//New Change ID 20150309						
						if($stock_status and $stock_status != '-1')		$sql .= " AND stock_status.meta_key ='_stock_status' AND stock_status.meta_value IN ({$stock_status})";//New Change ID 20150309
						if($manage_stock and $manage_stock != '-1')		$sql .= " AND manage_stock.meta_key ='_manage_stock' AND manage_stock.meta_value IN ({$manage_stock})";//New Change ID 20150309
						
						switch($sort_by){
							case "stock":
								$sql .= " ORDER BY ABS(stock.meta_value) {$order_by}";
								break;
							case "product_name":
								$sql .= " ORDER BY posts.post_title {$order_by}";
								break;
							default:
								$sql .= " ORDER BY {$sort_by} {$order_by}";
								break;
						}
						
						$this->normal_sql_query = $sql;
						
						
					
					}else{
						$sql = $this->normal_sql_query;
					}
					
					$sql = $sql_column .$sql;
					
					//$this->print_sql($sql);
					
					
					
					$wpdb->flush(); 				
					$wpdb->query("SET SQL_BIG_SELECTS=1");
					
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
					
					if($type == 'limit_row'){
						$sql .= " LIMIT $start, $limit";
						$order_items = $wpdb->get_results($sql);
						$wpdb->flush();
						//$this->print_sql($sql); 
					}
					
					if($type == 'all_row' or $type == 'all_row_total'){
						$order_items = $wpdb->get_results($sql);
						$wpdb->flush(); 
					}
					
					if($type == 'limit_row' || $type == 'all_row' or $type == 'all_row_total'){
						if(count($order_items)>0)
						foreach ( $order_items as $key => $order_item ) {
							
								$product_id								= $order_item->product_id;
								
								if(!isset($order_meta[$product_id])){
									$order_meta[$product_id]			= $this->get_all_post_meta($product_id);
								}
								
								foreach($order_meta[$product_id] as $k => $v){
									$order_items[$key]->$k			= $v;
								}
						}
						//$this->print_array($order_items);
					}
					
					//$this->print_array($order_items);
					
					return $order_items;
			
		}
		
		//20150302
		function get_all_product_sku($post_type = 'product'){
			global $wpdb;
			
			$sql = "SELECT postmeta_sku.meta_value AS id, postmeta_sku.meta_value AS label FROM `{$wpdb->prefix}posts` AS posts";
			
			$sql .= " 	
					LEFT JOIN  {$wpdb->prefix}term_relationships 	as term_relationships_product_type 	ON term_relationships_product_type.object_id		=	posts.ID 
					LEFT JOIN  {$wpdb->prefix}term_taxonomy 		as term_taxonomy_product_type 		ON term_taxonomy_product_type.term_taxonomy_id		=	term_relationships_product_type.term_taxonomy_id
					LEFT JOIN  {$wpdb->prefix}terms 				as terms_product_type 				ON terms_product_type.term_id						=	term_taxonomy_product_type.term_id";
			
			$sql .= " LEFT JOIN {$wpdb->prefix}postmeta AS postmeta_sku ON postmeta_sku.post_id = posts.ID";
			
			$sql .= " WHERE posts.post_type = '{$post_type}'";
			
			$sql .= " AND posts.post_status = 'publish'";
			
			$sql .= " AND terms_product_type.name IN ('variable','simple')";
			
			$sql .= " AND postmeta_sku.meta_key = '_sku' AND LENGTH(postmeta_sku.meta_value) > 0";
			
			$sql .= " GROUP BY postmeta_sku.meta_value ORDER BY postmeta_sku.meta_value";
			
			$products = $wpdb->get_results($sql);
			
			return $products;
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
			
			/*
			$product_type ="";
			$product_id = (integer)$product_id;			
			$prod_terms = get_the_terms($product_id,'product_type');
				//$this->print_array($prod_terms);	
				foreach ( $prod_terms as $term ) {
				//echo $term->name;
				
					if( $term->name == 'grouped' )
						$product_type= 'Grouped product';
					elseif ( $term->name == 'external' )
						$product_type=  'External/Affiliate product';
					elseif ( $term->name == 'simple' )
						$product_type=  'Simple product';
					elseif ( $term->name == 'variable' )
						$product_type= 'Variable';
					else
						// Assuming that we have other types in future
						$product_type=  ucwords($term->name);
				
				}
			return $product_type;
			*/
			
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
                    <input type="submit" name="<?php echo $admin_page ;?>_export_csv" class="onformprocess" value="<?php _e("Export to CSV",'icwoocommerce_textdomains');?>" />
                    <input type="button" name="<?php echo $admin_page ;?>_export_pdf" class="onformprocess open_popup" value="<?php _e("Export to PDF",'icwoocommerce_textdomains');?>" data-format="pdf" data-popupid="export_pdf_popup" data-hiddenbox="popup_pdf_hidden_fields" data-popupbutton="<?php _e("Export to PDF",'icwoocommerce_textdomains');?>" data-title="<?php _e("Export to PDF",'icwoocommerce_textdomains');?>" />
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
            		<input type="button" name="backtoprevious" value="<?php _e("Back to Previous",'icwoocommerce_textdomains');?>"  class="backtoprevious onformprocess" onClick="back_to_previous();" />
                </div>
            <?php  
		}		
		
		function ic_commerce_custom_report_page_export_csv(){
			global $wpdb, $table_prefix;
			
			
			$rows 				= $this->ic_commerce_custom_report_normal_query('all_row');
			$columns 			= $this->grid_columns();
			$export_file_name 	= $this->get_request('export_file_name',"no");
			$export_file_format = $this->get_request('export_file_format',"no");	
			$date_format		= get_option( 'date_format' );
			
			$rows 				= apply_filters('ic_commerce_stock_list_pdf_items',$rows, $columns);
			
			$export_rows = array();
			
			$i = 0;
			if(count($rows) > 0){
				foreach ( $rows as $rkey => $rvalue ):
					$order_item = $rvalue;//New Change ID 20150309
					foreach($columns as $key => $value):
						switch ($key) {
							case 'product_type_name':
								$export_rows[$i][$key] = $this->get_product_type_by_product_id($rvalue->product_id);
								break;
							case 'category_name':
								$export_rows[$i][$key] = $this->get_category_name_by_product_id($rvalue->product_id);
								break;
							case 'regular_price':
							case 'sale_price':
								$export_rows[$i][$key] = $rvalue->$key;
								break;
							case 'product_date':
							case 'modified_date':
								$export_rows[$i][$key] = isset($rvalue->$key) ? date_i18n($date_format,strtotime($rvalue->$key)) : '';
								break;
							//New Change ID 20150309
							case "downloadable":
							case "virtual":
							case "manage_stock":
								$v = isset($order_item->$key) ? $order_item->$key : 'no';
								$export_rows[$i][$key] = $v == 'yes' ? 'Yes' : 'No';
								break;						
							case "stock_status":
								$v = isset($order_item->$key) ? $order_item->$key : ' instock';
								$export_rows[$i][$key] = $v == 'instock' ? 'In stock' : 'Out of stock';
								break;							
							case "backorders":
								$v = isset($order_item->$key) ? $order_item->$key : 'no';
								$export_rows[$i][$key] = $v == 'no' ? 'Do not allow' : ($v == 'notify' ? 'Allow with notify' : "Allow");
								break;
							case "stock":
								$v = isset($order_item->$key) ? trim($order_item->$key) : '';
								$v = empty($v) ? "" : ($v + 0);
								$export_rows[$i][$key] = $v;
								break;
							//New Change ID 20150309
							default:
								$export_rows[$i][$key] = isset($rvalue->$key) ? $rvalue->$key : '';
								break;
						}
					endforeach;
					$i++;
				endforeach;
				
				/*$today = date_i18n("Y-m-d-H-i-s");				
				$FileName = $export_file_name."-".$today.".".$export_file_format;	
				$this->ExportToCsv($FileName,$export_rows,$columns);*/
				
				/*New Added 20160130*/
				$report_name		= '';		
				$today_date 		= date_i18n("Y-m-d-H-i-s");				
				$export_filename 	= $export_file_name."-".$today_date.".".$export_file_format;
				$export_filename 	= apply_filters('ic_commerce_export_csv_excel_format_file_name',$export_filename,$report_name,$today_date,$export_file_name,$export_file_format);
				do_action("ic_commerce_export_csv_excel_format",$export_filename,$export_rows,$columns,$export_file_format,$report_name);
				$out = $this->ExportToCsv($export_filename,$export_rows,$columns,$export_file_format,$report_name);
			}
		}
		
		function ExportToCsv($filename = 'export.csv',$rows,$columns,$format="csv"){				
			global $wpdb;
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
			
			header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
			header("Content-Length: " . strlen($out));	
			header("Content-type: text/x-csv");
			header("Content-type: text/csv");
			header("Content-type: application/csv");
			header("Content-Disposition: attachment; filename=$filename");
			echo $out;
			exit;
		 
		}
		
		function ic_commerce_custom_report_page_export_pdf(){
			global $wpdb, $table_prefix;
			
			$rows 				= $this->ic_commerce_custom_report_normal_query('all_row');
			$columns 			= $this->grid_columns();
			$export_file_name 	= $this->get_request('export_file_name',"no");
			$export_file_format = $this->get_request('export_file_format',"no");
			$export_file_format = 'pdf';
			$export_rows 		= array();
			$date_format		= get_option( 'date_format' );
			
			$rows 				= apply_filters('ic_commerce_stock_list_pdf_items',$rows, $columns);
			
			$i = 0;
			foreach ( $rows as $rkey => $rvalue ):
				$order_item = $rvalue;//New Change ID 20150309
				foreach($columns as $key => $value):
					switch ($key) {
						case 'product_type_name':
							$export_rows[$i][$key] = $this->get_product_type_by_product_id($rvalue->product_id);
							break;
						case 'category_name':
							$export_rows[$i][$key] = $this->get_category_name_by_product_id($rvalue->product_id);
							break;
						case 'regular_price':
						case 'sale_price':
							$export_rows[$i][$key] =  $this->price($rvalue->$key);
							break;
						case 'product_date':
						case 'modified_date':
							$export_rows[$i][$key] = isset($rvalue->$key) ? date_i18n($date_format,strtotime($rvalue->$key)) : '';
							break;
						//New Change ID 20150309
						case "downloadable":
						case "virtual":
						case "manage_stock":
							$v = isset($order_item->$key) ? $order_item->$key : 'no';
							$export_rows[$i][$key] = $v == 'yes' ? 'Yes' : 'No';
							break;						
						case "stock_status":
							$v = isset($order_item->$key) ? $order_item->$key : ' instock';
							$export_rows[$i][$key] = $v == 'instock' ? 'In stock' : 'Out of stock';
							break;
						case "stock":
							$v = isset($order_item->$key) ? trim($order_item->$key) : '';
							$v = empty($v) ? "" : ($v + 0);
							$export_rows[$i][$key] = $v;
							break;
						case "backorders":
							$v = isset($order_item->$key) ? $order_item->$key : 'no';
							$export_rows[$i][$key] = $v == 'no' ? 'Do not allow' : ($v == 'notify' ? 'Allow with notify' : "Allow");
							break;
						//New Change ID 20150309
						default:
							$export_rows[$i][$key] = isset($rvalue->$key) ? $rvalue->$key : '';
							break;
					}
				endforeach;
				$i++;
			endforeach;
			
			$output = $this->GetPdfDataGrid($export_rows,$columns);
			$this->export_to_pdf($export_rows,$output);
		}
		
		function grid_columns(){
			
			$basic_column				= $this->get_request('basic_column',"no",true);
			
			if($basic_column == "yes"){
				$columns = array(					
					"sku"					=>__("SKU",					'icwoocommerce_textdomains')
					,"product_name"			=>__("Product Name",		'icwoocommerce_textdomains')
					,"category_name"		=>__("Category",			'icwoocommerce_textdomains')
					,"stock"				=>__("Stock",				'icwoocommerce_textdomains')
				);
			}else{
				$columns = array(					
					"sku"					=>__("SKU",					'icwoocommerce_textdomains')
					,"product_name"			=>__("Product Name",		'icwoocommerce_textdomains')
					,"product_type_name"	=>__("Product Type",		'icwoocommerce_textdomains')
					,"category_name"		=>__("Category",			'icwoocommerce_textdomains')
					,"product_date"			=>__("Created Date",		'icwoocommerce_textdomains')
					,"modified_date"		=>__("Modified Date",		'icwoocommerce_textdomains')
					,"stock"				=>__("Stock",				'icwoocommerce_textdomains')
					,"regular_price"		=>__("Regular Price",		'icwoocommerce_textdomains')
					,"sale_price"			=>__("Sale Price",			'icwoocommerce_textdomains')
					,"downloadable"			=>__("Downloadable",		'icwoocommerce_textdomains')
					,"virtual"				=>__("Virtual",				'icwoocommerce_textdomains')
					,"manage_stock"			=>__("Manage Stock",		'icwoocommerce_textdomains')
					,"backorders"			=>__("Backorders",			'icwoocommerce_textdomains')
					,"stock_status"			=>__("Stock Status",		'icwoocommerce_textdomains')
				);
			}
			
			$columns 				= apply_filters('ic_commerce_stock_page_columns',$columns, $basic_column);
						
			return $columns;
		}
		
		function get_all_request(){
			global $request, $back_day;
			if(!$this->request){
				$request 			= array();
				$start 				= 0;
				$p					= $this->get_request('p',1,true);
				$limit				= $this->get_request('limit',15,true);
				$product_sku		= $this->get_request('product_sku','-1',true);//New Change ID 20150226
				$product_type		= $this->get_request('product_type','-1',true);
				$category_id		= $this->get_request('category_id','-1',true);
				$product_id			= $this->get_request('product_id','-1',true);
				
				//New Change ID 20150309
				$zero_sold			= $this->get_request('zero_sold','no',true);
				$zero_stock			= $this->get_request('zero_stock','no',true);				
				$stock_status		= $this->get_request('stock_status','-1',true);
				$manage_stock		= $this->get_request('manage_stock','-1',true);
				
				$sort_by 			= $this->get_request('sort_by','product_name',true);
				$order_by 			= $this->get_request('order_by','ASC',true);
				
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
		
		
		
	}
}
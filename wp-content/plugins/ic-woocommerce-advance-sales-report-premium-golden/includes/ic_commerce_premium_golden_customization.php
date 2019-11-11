<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
if ( ! class_exists( 'IC_Commerce_Premium_Golden_Customization' ) ) {
	require_once('ic_commerce_premium_golden_fuctions.php');
	class IC_Commerce_Premium_Golden_Customization extends IC_Commerce_Premium_Golden_Fuctions{
		
		public $constants 				= array();
		
		function __construct($constants = array(), $admin_page = ''){
			$this->constants = $constants;
			$this->init();
		}
		
		function init(){
			
			$admin_page = isset($_REQUEST['page']) ? $_REQUEST['page'] : "";
			
			$details_pages 		= apply_filters('ic_commerce_'.$this->constants['plugin_key'].'_detail_pages',array($this->constants['plugin_key'].'_details_page'));
			$variation_pages 	= apply_filters('ic_commerce_'.$this->constants['plugin_key'].'_variation_pages',array($this->constants['plugin_key'].'_variation_page'));
			$report_pages 		= apply_filters('ic_commerce_'.$this->constants['plugin_key'].'_report_pages',array($this->constants['plugin_key'].'_report_page'));
			
			if(in_array($admin_page,$details_pages)){
				add_action("ic_commerce_detail_page_search_form_before_order_by",	array($this, "ic_commerce_detail_page_search_form_before_order_by"),20);
				add_action("ic_commerce_details_page_footer_area",					array($this, "ic_commerce_details_page_footer_area"),20);
				
				add_filter("ic_commerce_normal_view_join_query",					array($this, "get_ic_commerce_normal_view_join_query"),	20,5);
				add_filter("ic_commerce_details_view_join_query",					array($this, "get_ic_commerce_normal_view_join_query"),	20,5);
				
				add_filter("ic_commerce_normal_view_where_query",					array($this, "get_ic_commerce_normal_view_where_query"), 20,5);
				add_filter("ic_commerce_details_view_where_query",					array($this, "get_ic_commerce_normal_view_where_query"), 20,5);
				
				$detail_view = isset($_REQUEST['detail_view']) ? $_REQUEST['detail_view'] : "no";				
				if($detail_view == 'yes'){
					add_filter("ic_commerce_detail_page_before_default_request",				array($this, "set_default_request"),								20);
				}
			}
			
			if(in_array($admin_page,$variation_pages)){
				add_action("ic_commerce_variation_page_above_show_fields",			array($this, "ic_commerce_variation_page_above_show_fields"),20);
			}
			
			
			if(in_array($admin_page,$report_pages)){				
				add_filter("ic_commerce_report_page_default_items",					array($this, "get_ic_commerce_report_page_default_items"),	20,5);
				add_filter("ic_commerce_report_page_titles",						array($this, "get_ic_commerce_report_page_titles"),			20,3);
				add_filter("ic_commerce_report_page_columns",						array($this, "get_ic_commerce_report_page_columns"),		20,2);
				add_filter("ic_commerce_report_page_result_columns",				array($this, "get_ic_commerce_report_page_result_columns"),	20,2);
				add_filter("ic_commerce_pdf_custom_column_right_alignment",			array($this, "get_ic_commerce_pdf_custom_column_right_alignment"),20,3);
				
			}
			
			if(in_array($admin_page,$report_pages)){
				require_once('ic_commerce_premium_golden_refund_summary.php');
				$obj = new IC_Commerce_Premium_Golden_Refund_Summary($this->constants);
			}
			
		}//End Method
		
		function ic_commerce_detail_page_search_form_before_order_by($this_){
			$admin_page = isset($_REQUEST['page']) ? $_REQUEST['page'] : "";
			$details_pages 		= apply_filters('ic_commerce_'.$this->constants['plugin_key'].'_detail_pages',array($this->constants['plugin_key'].'_details_page'));
			$variation_only = $this->get_request('variation_only','no');
			if(in_array($admin_page,$details_pages)){
				?>
            	<div class="form-group">
                    <div class="FormRow FirstRow">
                        <div class="label-text"><label for="country_code"><?php _e('Country:','icwoocommerce_textdomains');?></label></div>
                        <div class="input-text">
                            <?php 
                                $country_code = $this->get_request('country_code');
                                $country_data = $this->get_paying_state('billing_country');															
                                $this->create_dropdown($country_data,"country_code[]","country_code2","Select All","country_code2",$country_code, 'object', true, 5);
                            ?>                                                        
                        </div>                                                    
                    </div>
                    <div class="FormRow ">
                        <div class="label-text"><label for="state_code"><?php _e('State:','icwoocommerce_textdomains');?></label></div>
                        <div class="input-text">
                            <?php 
								$state_code = $this->get_request('state_code');
                                echo '<select name="state_code[]" id="state_code2" class="state_code2" multiple="multiple" size="1"  data-size="1">';
                                if($state_code != "-1"){
                                    echo "<option value=\"{$state_code}\">{$state_code}</option>";
                                }
                                echo '</select>';
                            ?>                                                        
                        </div>                                                    
                    </div>
                 </div>
				 <div class="form-group">
                	<div class="FormRow FirstRow">
						<div class="label-text"><label for="variations"><?php _e("Variations:",'icwoocommerce_textdomains');?></label></div>
						<div class="input-text">
							<?php
								$transaction_variation	= $this->get_product_variation_attributes("yes");
								//$this->print_array($transaction_variation);
								$variations = $this->get_request('variations');
								if($transaction_variation){
									$enable_variation = true;
									$new_attr = array();
									foreach($transaction_variation as $key => $value){
										$new_key = str_replace("wcv_","",$key);
										$new_transaction_variation[$new_key] = $value;
										$new_transaction_variation_keys[] = $new_key;
									}
									$data = NULL;
									//$this->print_array($new_transaction_variation_keys);
									$all_keys = implode(",",$new_transaction_variation_keys);
									
									$this->create_dropdown($new_transaction_variation,"variations[]","variations","Select All","variations details_view_only",$variations, 'array', true, 5, $all_keys);
								}else{
									$enable_variation = false;	
									echo __("There is no any order purchased in variable product.",'icwoocommerce_textdomains');
								}
							?>
						</div>
						<span class="detail_view_seciton detail_view_seciton_note"><?php _e("Enable variations selection by clicking 'Show Order Item Details",'icwoocommerce_textdomains');?></span>
					</div>
                	<div class="FormRow checkbox">
						<div class="label-text" style="padding-top:0px;"><label for="variation_only"><?php _e('Variation Only:','icwoocommerce_textdomains');?></label></div>
						<div style="padding-top:0px;"><input type="checkbox" name="variation_only" id="variation_only" value="1" <?php if($variation_only == "yes"){ echo ' checked="checked"';}?> class="details_view_only" /></div>
						<span class="detail_view_seciton detail_view_seciton_note"><?php _e("Enable variations only selection by clicking 'Show Order Item Details",'icwoocommerce_textdomains');?></span>
					</div>
            	</div>
            	<div class="form-group">
                    <div class="FormRow FirstRow">
                        <div class="label-text"><label for="billing_postcode"><?php _e('Postcode(Zip):','icwoocommerce_textdomains');?></label></div>
                        <div class="input-text"><input type="text" id="billing_postcode" name="billing_postcode" class="regular-text" maxlength="100" value="<?php echo $this->get_request('billing_postcode','',true);?>" /></div>
                    </div>
                    <div class="FormRow">
                        <div class="label-text"><label for="order_meta_key"><?php _e('Min and Max By:','icwoocommerce_textdomains');?></label></div>
                        <div class="input-text">
                            <?php 
                                $order_meta_key = $this->get_request('order_meta_key');
                                $reports_data = array(
                                    "_order_total"			=>__("Order Net Amount",			'icwoocommerce_textdomains'),
                                    "_order_discount"		=>__("Order Discount Amount",		'icwoocommerce_textdomains'),
                                    "_order_shipping"		=>__("Order Shipping Amount",		'icwoocommerce_textdomains'),
                                    "_order_shipping_tax"	=>__("Order Shipping Tax Amount",	'icwoocommerce_textdomains')
                                );
                                $this->create_dropdown($reports_data,"order_meta_key[]","order_meta_key2","Select All","order_meta_key normal_view_only",$order_meta_key, 'array', false, 5);
                            ?>                                                        
                        </div>
                        <span class="detail_view_seciton normal_view_seciton_note"><?php _e("Enable this selection by uncheck 'Show Order Item Details'",'icwoocommerce_textdomains');?></span>
                    </div>
                 </div>             
             	<div class="form-group">
                    <div class="FormRow FirstRow">
                        <div class="label-text"><label for="min_amount"><?php _e('Min Amount:','icwoocommerce_textdomains');?></label></div>
                        <div class="input-text"><input type="text" id="min_amount" name="min_amount" class="regular-text normal_view_only" maxlength="100" value="<?php echo $this->get_request('min_amount','',true);?>" /></div>
                        <span class="detail_view_seciton normal_view_seciton_note"><?php _e("Enable this selection by uncheck 'Show Order Item Details'",'icwoocommerce_textdomains');?></span>
                    </div>
                    
                    <div class="FormRow">
                        <div class="label-text"><label for="max_amount"><?php _e('Max Amount:','icwoocommerce_textdomains');?></label></div>
                        <div class="input-text"><input type="text" id="max_amount" name="max_amount" class="regular-text normal_view_only" maxlength="100" value="<?php echo $this->get_request('max_amount','',true);?>" /></div>
                        <span class="detail_view_seciton normal_view_seciton_note"><?php _e("Enable this selection by uncheck 'Show Order Item Details'",'icwoocommerce_textdomains');?></span>
                    </div>
                 </div>
            	<?php
			}//End icwoocommercepremiumgold_details_page;
		}
		
		function ic_commerce_details_page_footer_area(){
			
			$admin_page = isset($_REQUEST['page']) ? $_REQUEST['page'] : "";
			$details_pages 		= apply_filters('ic_commerce_'.$this->constants['plugin_key'].'_detail_pages',array($this->constants['plugin_key'].'_details_page'));
			if(in_array($admin_page,$details_pages)){
				?>	
				<script type="text/javascript">
					jQuery(document).ready(function($) {
					
						<?php
							$country_states = $this->get_country_state();
							$json_country_states = json_encode($country_states);
							
							$country_code = $this->get_request('country_code');						
							$state_code = $this->get_request('state_code');
						 ?>	
						 ic_commerce_vars['json_country_states'] 	= <?php echo $json_country_states;?>;
						 
						 ic_commerce_vars['country_code']			= "<?php echo $country_code	== '-1' ? '-2': $country_code;?>";
						 ic_commerce_vars['state_code']				= "<?php echo $state_code	== '-1' ? '-2': $state_code;?>";
						 
						 ic_commerce_vars['country_dropdown'] 		= $('#country_code2').attr('size');
						 
						 create_dropdown(ic_commerce_vars['json_country_states'],ic_commerce_vars['json_country_states'],"state_code2",Array(ic_commerce_vars['country_code']),Array(ic_commerce_vars['state_code']),'array');
						$('#country_code2').change(function(){
							var parent_id = $(this).val();
							if(parent_id == null) parent_id = Array("-1");
							create_dropdown(ic_commerce_vars['json_country_states'],ic_commerce_vars['json_country_states'],"state_code2",parent_id,Array('-2'),"array");
						});									
						
						jQuery("select#state_code2").attr('size',ic_commerce_vars['country_dropdown']);
						jQuery("select#state_code2").attr('data-size',ic_commerce_vars['country_dropdown']);
						
						$('#ResetForm').click(function(){					
							create_dropdown(ic_commerce_vars['json_country_states'],ic_commerce_vars['json_country_states'],"state_code2",Array(ic_commerce_vars['country_code']),Array(ic_commerce_vars['state_code']),'array');
						});				
					
					
					});				 
				 </script>
				<?php   
			}//End icwoocommercepremiumgold_details_page;   
		}
		
		function set_default_request(){
			
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
		}
		
		function get_ic_commerce_normal_view_join_query( $sql = array(), $request = array()){ 
			global $wpdb;
			
			$variations = isset($request['variations']) ? $request['variations']: -1;
			if($variations and $variations != -1){
				$sql .= " LEFT JOIN  {$wpdb->prefix}woocommerce_order_itemmeta as woocommerce_order_itemmeta8 ON woocommerce_order_itemmeta8.order_item_id = woocommerce_order_items.order_item_id";
			}
			
			if(isset($_REQUEST['new_variations_value']) and count($_REQUEST['new_variations_value'])>0){
				foreach($_REQUEST['new_variations_value'] as $key => $value){
					$new_v_key = "wcvf_".$this->remove_special_characters($key);
					$sql .= " LEFT JOIN  {$wpdb->prefix}woocommerce_order_itemmeta as woocommerce_order_itemmeta_{$new_v_key} ON woocommerce_order_itemmeta_{$new_v_key}.order_item_id = woocommerce_order_items.order_item_id";
				}
			}
			
			$variation_itemmetakey = $this->get_request('variation_itemmetakey','-1');
			if(($variation_itemmetakey != "-1" and strlen($variation_itemmetakey)>1)){
				$sql .= " LEFT JOIN  {$wpdb->prefix}woocommerce_order_itemmeta as woocommerce_order_itemmeta_variation_key ON woocommerce_order_itemmeta_variation_key.order_item_id= woocommerce_order_items.order_item_id";
			}
			
			return $sql;
		}
		/**
		* get_ic_commerce_normal_view_where_query
		*
		*
		* @param array $sql 
		* @param array $request 
		*
		*
		* @return string
		*/
		function get_ic_commerce_normal_view_where_query( $sql = array(), $request = array()){
			global $wpdb;	
			/*
			$variations 		= isset($request['variations']) ? $request['variations']: -1;
			if($variations and $variations != '-1'){
				$variations		= $this->get_string_multi_request('variations',$variations, "-1");				
			}
			*/
			
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
			
			$variation_itemmetakey = $this->get_request('variation_itemmetakey','-1');
			if(($variation_itemmetakey != "-1" and strlen($variation_itemmetakey)>1)){
				$sql .= " AND woocommerce_order_itemmeta_variation_key.meta_key IN ('{$variation_itemmetakey}')";
			}
			return $sql;
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
		
		
		
		var $variation_columns;
		
		function get_product_variation_attributes($all_columns = "no"){
				$product_attirbute_columns = array();
				
				if(!isset($this->variation_columns)){
					$this->variation_columns = array();
					global $wpdb;			
					$sql = "SELECT postmeta_product_addons.meta_value product_attributes FROM {$wpdb->prefix}posts AS posts";
					$sql .= " LEFT JOIN {$wpdb->prefix}postmeta AS postmeta_product_addons ON postmeta_product_addons.post_id = posts.ID";
					$sql .= " WHERE post_type in ('product')";
					$sql .= " AND postmeta_product_addons.meta_key IN ('_product_attributes') ";
					
					$product_addon_objects = $wpdb->get_results($sql);
					//$this->print_array($product_addon_objects);
					$product_addon_master = array();
					if(count($product_addon_objects)>0){					
						foreach($product_addon_objects as $key => $value){
							$product_addon_lists = unserialize($value->product_attributes);
							foreach($product_addon_lists as $key2 => $value2){
								$product_addon_master[] = $key2;
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
					
					//$this->print_array($product_addon_master_key);
					
					$sql = "SELECT ";
					
					$sql .= " woocommerce_order_itemmeta.meta_key as attribute_key_lable ";
					$sql .= " ,woocommerce_order_itemmeta.meta_value as attribute_key_value ";
					$sql .= " , REPLACE(woocommerce_order_itemmeta.meta_key,'pa_','') as attribute_key ";				
					$sql .= " ,woocommerce_order_itemmeta.order_item_id as order_item_id ";
					$sql .= " FROM {$wpdb->prefix}woocommerce_order_items AS woocommerce_order_items";
					$sql .= " LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS woocommerce_order_itemmeta ON woocommerce_order_itemmeta.order_item_id = woocommerce_order_items.order_item_id";
					$sql .= " WHERE 1*1";
					
					if($product_addon_master_key){
						$sql .= " AND woocommerce_order_itemmeta.meta_key IN ('{$product_addon_master_key}') ";
					}
					
					if($all_columns == "no"){
						$variation_itemmetakey = $this->get_request('variation_itemmetakey','-1');
						if($variation_itemmetakey and $variation_itemmetakey != '-1'){
							$sql .= " AND woocommerce_order_itemmeta.meta_key IN ('{$variation_itemmetakey}') ";
						}
					}
					
					$sql .= " GROUP BY woocommerce_order_itemmeta.meta_key";
					$sql .= " ORDER BY attribute_key";
					
					$items_objects = $wpdb->get_results($sql);
					
					//$this->print_array($items_objects);
					
					
					if(count($items_objects)>0){					
						foreach($items_objects as $key => $value){
							$product_addon_master[] = $key2;
							
							$attribute_key = trim($value->attribute_key);
							$attribute_key = str_replace("-"," ",$attribute_key);
							
							$product_attirbute_columns[$value->attribute_key_lable] = ucwords($attribute_key);
						}
					}
					
					//$this->print_array($product_attirbute_columns);
					
					$this->variation_columns = $product_attirbute_columns;
				}else{
					$product_attirbute_columns = $this->variation_columns;
				}
				
				return $product_attirbute_columns;
				
				
		}
		
		function get_order_itemmeta($order_item_ids = "", $order_item_type = 'line_item', $not_int = array("_line_tax_data")){
			global $wpdb;
			
			$sql = " SELECT woocommerce_order_itemmeta.*";
			
			$sql .= " FROM `{$wpdb->prefix}woocommerce_order_items` AS woocommerce_order_items";
			
			$sql .= " LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS woocommerce_order_itemmeta ON woocommerce_order_itemmeta.order_item_id = woocommerce_order_items.order_item_id";
			
			$sql .= " WHERE 1=1";
			
			if(strlen($order_item_ids)>0){
				$sql .= " AND woocommerce_order_itemmeta.order_item_id IN ({$order_item_ids})";
			}
			
			if(strlen($order_item_type)>0)
				$sql .= " AND woocommerce_order_items.order_item_type IN ('{$order_item_type}')";
			
			if(count($not_int)>0){
				$not_int_string = implode("','",$not_int);
				$sql .= " AND woocommerce_order_itemmeta.meta_key NOT IN ('{$not_int_string}')";
			}
			
			$items = $wpdb->get_results($sql);
			
			$new_array = array();
			
			$new_array2 = array();
			
			foreach($items as $key => $item){
				$meta_key = ltrim ($item->meta_key, "_");
				$new_array[$item->order_item_id][$meta_key][] = trim($item->meta_value);
			}
			
			//$this->print_array($new_array);
			
			foreach($new_array as $key1 => $items){
				foreach($items as $key2 => $item){
					$new_array2[$key1][$key2] = implode(", ", $item);
				}
			}
			
			$items = NULL; unset($items);
			
			return $new_array2;
			
		}
		
		
		
		function ic_commerce_variation_page_above_show_fields($this_){
			echo $this->get_variation_dropdown();
		}
		
		function get_variation_dropdown(){
			$new_item_attr_order_item_id = $this->get_variation_dropdown_item();
			//$this->print_array($new_item_attr_order_item_id);
			$output = "";
			if(count($new_item_attr_order_item_id)>0){
				$vi = 0;
				$output .= '<div class="form-group dynamic_fields">';
				foreach($new_item_attr_order_item_id as $key => $values):
					$vl 	= str_replace("attribute_pa_","",$key);
					$vl 	= str_replace("pa_","",$vl);					
					$vl 	= str_replace("-"," ",$vl);
					$label 	= ucwords($vl);
					
					
					$id = str_replace(" ","_",$vl);
					
					$attr = array();
					foreach($values as $k => $v){
						$attr[] = $k;
					}						
					$detault =  $input = implode(",",$attr);
					$output .= '<div class="FormRow'.($vi%2 ? ' SecondRow' : ' FirstRow').' var_attr_'.$key.'">';
					$output .= '<div class="label-text"><label for="new_variations_value_'. $key.'">'.$label.':</label></div>';
					$output .= '<div class="input-text">';
						$attribute_values = $values;
						$output .= $this->create_dropdown($attribute_values,"new_variations_value[$key][]","new_variations_value_{$key}",'Select All',"variation_dropdowns",'-1', 'array', true, 5, $detault, false);
					$output .= '</div>';
					$output .= '</div>';
					$vi++;
				endforeach;
				$output .= '</div>';
			}
			return $output;
		}
		
		function get_variation_dropdown_item(){
			global $wpdb;					
			$new_attr 			= array();
			$attribute_keys 	= array();
			$attribute_labels 	= array();
			$return 			= array();
			$variations 		= array();
			
			$new_item_attr_variation_id		= array();
			$new_item_attr_order_item_id	= array();
			$order_item_variations			= array();
			/*
			$sql = "SELECT TRIM(LEADING 'attribute_' FROM meta_key)  AS attribute_key  ";
			$sql .= " FROM {$wpdb->prefix}postmeta ";
			$sql .= " WHERE meta_key LIKE 'attribute%'";
			
			$sql .= " GROUP BY attribute_key ORDER BY attribute_key ASC";
			
			$attributes =  $wpdb->get_results($sql);
			
			if($attributes){
				foreach($attributes as $key => $value){						
					$attribute_keys[]	= $value->attribute_key;
				}
			}
			
			//$this->print_data($attribute_keys);					
			//$attribute_meta_key = implode("', '",$attribute_keys);
			//$attribute_meta_key = "order-period";
			*/
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
			
			$sql = "SELECT TRIM(LEADING 'pa_' FROM woocommerce_order_itemmeta.meta_key) AS attribute_key, 
					woocommerce_order_itemmeta.meta_value AS attribute_value, woocommerce_order_itemmeta.order_item_id, woocommerce_order_itemmeta.meta_key AS meta_key";		
			$sql .= " FROM {$wpdb->prefix}woocommerce_order_itemmeta as woocommerce_order_itemmeta";					
			
			$sql .= " WHERE woocommerce_order_itemmeta.meta_key IN ('{$attribute_meta_key}')";
			
			$sql .= " GROUP BY attribute_value ORDER BY attribute_key ASC";
			
			$item_attributes =  $wpdb->get_results($sql);
			if($item_attributes){
				foreach($item_attributes as $key => $value){
					$attribute_key 		= $value->attribute_key;
					$attribute_value	= $value->attribute_value;
					$attribute_value 	= ucwords(str_replace("-"," ",$attribute_value));												
					$new_item_attr_order_item_id[$value->meta_key][$value->attribute_value] = $attribute_value;
				}
			}		
			return $new_item_attr_order_item_id;
		}
		
		function get_ic_commerce_report_page_default_items($rows = array(), $type = "", $columns = array(), $report_name = "", $this_filter = NULL){
			switch($report_name){
				case "category_page":
					$rows 		= $this->get_ic_commerce_custom_all_category_query(					$type, $columns, $report_name, $this_filter);
					break;
				case "billing_state_page":
					$rows 		= $this->get_ic_commerce_custom_all_billing_state_query(			$type, $columns, $report_name, $this_filter);
					break;
				case "tax_page":
					$rows 		= $this->get_ic_commerce_custom_all_tax_items_query(				$type, $columns, $report_name, $this_filter);
					break;
				case "customer_buy_products_page":
					$rows 		= $this->get_ic_commerce_custom_all_customer_buy_products_query(	$type, $columns, $report_name, $this_filter);
					break;
			}
			return $rows;
		}
		
		
		//Start All Reports
		function get_ic_commerce_custom_all_category_query(				$type = 'limit_row', $columns = array(), $report_name = "", $this_filter = NULL){
			global $wpdb;
			if(!isset($this_filter->items_query)){
				$request 			= $this_filter->get_all_request();extract($request);				
				$order_status		= $this_filter->get_string_multi_request('order_status',$order_status, "-1");				
				$hide_order_status	= $this_filter->get_string_multi_request('hide_order_status',$hide_order_status, "-1");
				
				
				//$category_product_id_string = $this->get_products_list_in_category($category_id,$product_id);//Added 20150219
				//$category_id 				= "-1";//Added 20150219
			
				
				$sql ="";
				$sql .= " SELECT ";
				$sql .= " SUM(woocommerce_order_itemmeta_product_qty.meta_value) AS quantity";
				$sql .= " ,SUM(woocommerce_order_itemmeta_product_line_total.meta_value) AS total_amount";
				$sql .= " ,terms_product_id.term_id AS category_id";
				$sql .= " ,terms_product_id.name AS category_name";
				$sql .= " ,term_taxonomy_product_id.parent AS parent_category_id";
				$sql .= " ,terms_parent_product_id.name AS parent_category_name";
				$sql = apply_filters("ic_commerce_report_page_select_query", $sql, $request, $type, $page, $report_name, $columns);				
				$sql .= " FROM {$wpdb->prefix}woocommerce_order_items as woocommerce_order_items";
				
				$sql .= " LEFT JOIN  {$wpdb->prefix}woocommerce_order_itemmeta as woocommerce_order_itemmeta_product_id ON woocommerce_order_itemmeta_product_id.order_item_id=woocommerce_order_items.order_item_id";
				$sql .= " LEFT JOIN  {$wpdb->prefix}woocommerce_order_itemmeta as woocommerce_order_itemmeta_product_qty ON woocommerce_order_itemmeta_product_qty.order_item_id=woocommerce_order_items.order_item_id";
				$sql .= " LEFT JOIN  {$wpdb->prefix}woocommerce_order_itemmeta as woocommerce_order_itemmeta_product_line_total ON woocommerce_order_itemmeta_product_line_total.order_item_id=woocommerce_order_items.order_item_id";
				
				
				$sql .= " 	LEFT JOIN  {$wpdb->prefix}term_relationships 	as term_relationships_product_id 	ON term_relationships_product_id.object_id		=	woocommerce_order_itemmeta_product_id.meta_value 
							LEFT JOIN  {$wpdb->prefix}term_taxonomy 		as term_taxonomy_product_id 		ON term_taxonomy_product_id.term_taxonomy_id	=	term_relationships_product_id.term_taxonomy_id
							LEFT JOIN  {$wpdb->prefix}terms 				as terms_product_id 				ON terms_product_id.term_id						=	term_taxonomy_product_id.term_id";
				
				$sql .= " 	LEFT JOIN  {$wpdb->prefix}terms 				as terms_parent_product_id 				ON terms_parent_product_id.term_id						=	term_taxonomy_product_id.parent";
				
				$sql .= " LEFT JOIN  {$wpdb->prefix}posts as posts ON posts.id=woocommerce_order_items.order_id";
				
				if(strlen($order_status_id)>0 && $order_status_id != "-1" && $order_status_id != "no" && $order_status_id != "all"){
						$sql .= " 
						LEFT JOIN  {$wpdb->prefix}term_relationships 	as term_relationships 	ON term_relationships.object_id		=	posts.ID
						LEFT JOIN  {$wpdb->prefix}term_taxonomy 		as term_taxonomy 		ON term_taxonomy.term_taxonomy_id	=	term_relationships.term_taxonomy_id";
				}
				
				$sql = apply_filters("ic_commerce_report_page_join_query", $sql, $request, $type, $page, $report_name, $columns);
				
				$sql .= " WHERE 1*1 ";
				$sql .= " AND woocommerce_order_items.order_item_type 					= 'line_item'";
				$sql .= " AND woocommerce_order_itemmeta_product_id.meta_key 			= '_product_id'";
				$sql .= " AND woocommerce_order_itemmeta_product_qty.meta_key 			= '_qty'";
				$sql .= " AND woocommerce_order_itemmeta_product_line_total.meta_key 	= '_line_total'";
				$sql .= " AND term_taxonomy_product_id.taxonomy 						= 'product_cat'";
				$sql .= " AND posts.post_type 											= 'shop_order'";				
				
				if(strlen($order_status_id)>0 && $order_status_id != "-1" && $order_status_id != "no" && $order_status_id != "all"){
					$sql .= " AND  term_taxonomy.term_id IN ({$order_status_id})";
				}
				
				if($parent_category_id != NULL and $parent_category_id != "-1"){
					$sql .= " AND term_taxonomy_product_id.parent IN ($parent_category_id)";
				}
				
				if($child_category_id != NULL and $child_category_id != "-1"){
					$sql .= " AND terms_product_id.term_id IN ($child_category_id)";
				}
				
				if($list_parent_category != NULL and $list_parent_category > 0){
					$sql .= " AND term_taxonomy_product_id.parent > 0";
				}
				if ($start_date != NULL &&  $end_date !=NULL){
					$sql .= " AND DATE(posts.post_date) BETWEEN '".$start_date."' AND '". $end_date ."'";
				}
				
				if(strlen($publish_order)>0 && $publish_order != "-1" && $publish_order != "no" && $publish_order != "all"){
					$in_post_status		= str_replace(",","','",$publish_order);
					$sql .= " AND  posts.post_status IN ('{$in_post_status}')";
				}
				
				if($order_status  && $order_status != '-1' and $order_status != "'-1'")$sql .= " AND posts.post_status IN (".$order_status.")";
				
				if($hide_order_status  && $hide_order_status != '-1' and $hide_order_status != "'-1'")$sql .= " AND posts.post_status NOT IN (".$hide_order_status.")";
				
				
				if($category_id  && $category_id != "-1") {
					$sql .= " AND terms_product_id.term_id IN ($category_id)";
				}
				
				//if($category_product_id_string  && $category_product_id_string != "-1") $sql .= " AND woocommerce_order_itemmeta_product_id.meta_value IN (".$category_product_id_string .")";//Added 20150219
				
				$sql = apply_filters("ic_commerce_report_page_where_query", $sql, $request, $type, $page, $report_name, $columns);
				
				//$group_sql = " GROUP BY  postmeta2.meta_value Order";			
				
				//$sql .= apply_filters("ic_commerce_report_page_group_query", $group_sql, $request, $type, $page, $report_name, $columns);	
				
				
				
				
				if($group_by_parent_cat == 1){
					//$sql .= " GROUP BY parent_category_id";
					
					$group_sql = " GROUP BY parent_category_id";
				
					$sql .= apply_filters("ic_commerce_report_page_group_query", $group_sql, $request, $type, $page, $report_name, $columns);	
				}else{
					//$sql .= " GROUP BY category_id";
					
					$group_sql = " GROUP BY category_id";
				
					$sql .= apply_filters("ic_commerce_report_page_group_query", $group_sql, $request, $type, $page, $report_name, $columns);	
				};
				
				//$sql .= "  Order By total_amount DESC";
				
				$sort_by 	= isset($request['sort_by']) ? $request['sort_by'] : 'total_amount';
				
				$order_by 	= isset($request['order_by']) ? $request['order_by'] : 'DESC';				
				
				$order_sql = " ORDER BY {$sort_by} {$order_by}";
				
				$sql .= apply_filters("ic_commerce_report_page_order_query", $order_sql, $request, $type, $page, $report_name, $columns);	
				
				
				//$this->print_sql($sql);
				
				$this_filter->items_query = $sql;
				
			}else{
				$sql = $this_filter->items_query;
			}
				
			$order_items = $this_filter->get_query_items($type,$sql);
			
			//$this->print_array($order_items);
			
			return $order_items;
		}
		
		function get_ic_commerce_custom_all_billing_state_query(		$type = 'limit_row', $columns = array(), $report_name = "", $this_filter = NULL){
			global $wpdb;
			if(!isset($this_filter->items_query)){
				$request 			= $this_filter->get_all_request();extract($request);				
				$order_status		= $this_filter->get_string_multi_request('order_status',$order_status, "-1");
				$hide_order_status	= $this_filter->get_string_multi_request('hide_order_status',$hide_order_status, "-1");
				$billing_or_shipping = empty($billing_or_shipping) ? "billing" : $billing_or_shipping;
				
				$sql = "
				SELECT SUM(postmeta1.meta_value) AS 'total_amount' 
				,postmeta2.meta_value AS 'billing_state_code'
				,postmeta3.meta_value AS 'billing_country'
				,Count(*) AS 'order_count'";
				
				$sql = apply_filters("ic_commerce_report_page_select_query", $sql, $request, $type, $page, $report_name, $columns);				
				
				$sql .= "
				FROM {$wpdb->prefix}posts as posts
				LEFT JOIN  {$wpdb->prefix}postmeta as postmeta1 ON postmeta1.post_id=posts.ID
				LEFT JOIN  {$wpdb->prefix}postmeta as postmeta2 ON postmeta2.post_id=posts.ID
				LEFT JOIN  {$wpdb->prefix}postmeta as postmeta3 ON postmeta3.post_id=posts.ID";
				
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
				AND postmeta2.meta_key	=	'_{$billing_or_shipping}_state'
				AND postmeta3.meta_key	=	'_{$billing_or_shipping}_country'
				";
				
				if(strlen($order_status_id)>0 && $order_status_id != "-1" && $order_status_id != "no" && $order_status_id != "all"){
					$sql .= " AND  term_taxonomy.term_id IN ({$order_status_id})";
				}
				
				if ($start_date != NULL &&  $end_date !=NULL){
					$sql .= " AND DATE(posts.post_date) BETWEEN '".$start_date."' AND '". $end_date ."'";
				}
				if(strlen($publish_order)>0 && $publish_order != "-1" && $publish_order != "no" && $publish_order != "all"){
					$in_post_status		= str_replace(",","','",$publish_order);
					$sql .= " AND  posts.post_status IN ('{$in_post_status}')";
				}
				
				if($order_status  && $order_status != '-1' and $order_status != "'-1'")$sql .= " AND posts.post_status IN (".$order_status.")";
				
				if($hide_order_status  && $hide_order_status != '-1' and $hide_order_status != "'-1'")$sql .= " AND posts.post_status NOT IN (".$hide_order_status.")";
				
				$sql = apply_filters("ic_commerce_report_page_where_query", $sql, $request, $type, $page, $report_name, $columns);
				
				//$sql .= "  GROUP BY  postmeta2.meta_value Order By total_amount DESC";
				
				$group_sql = " GROUP BY  postmeta2.meta_value";			
				
				$sql .= apply_filters("ic_commerce_report_page_group_query", $group_sql, $request, $type, $page, $report_name, $columns);	
				
				$sort_by 	= isset($request['sort_by']) ? $request['sort_by'] : 'total_amount';
				
				$order_by 	= isset($request['order_by']) ? $request['order_by'] : 'DESC';				
				
				$order_sql = " ORDER BY {$sort_by} {$order_by}";
				
				$sql .= apply_filters("ic_commerce_report_page_order_query", $order_sql, $request, $type, $page, $report_name, $columns);	
				
				//$this->print_array($sql);
				
				
				$this_filter->items_query = $sql;
				
			}else{
				$sql = $this_filter->items_query;
			}
				
			$order_items = $this_filter->get_query_items($type,$sql);
			
			if($type == 'limit_row' || $type == 'all_row'){
				
				$country_names = array();
				
				foreach($order_items as $key => $order_item){					
					$billing_country 				= $order_item->billing_country;					
					$country_name[$billing_country] = isset($country_names[$billing_country]) ? $country_names[$billing_country] : $this->ic_cr_get_country_name($billing_country);
					
					$order_items[$key]->billing_state_name =  $this_filter->get_billling_state_name($order_item->billing_country,$order_item->billing_state_code);
					$order_items[$key]->billing_country_name =  $country_name[$billing_country] ;
				}
			}
			
			return $order_items;
		}
		
		function get_ic_commerce_custom_all_tax_items_query($type = 'limit_row', $columns = array(), $report_name = "", $this_filter = NULL ){
			global $wpdb;
			
			if(!isset($this_filter->items_query)){
				$request			= $this_filter->get_all_request();extract($request);
				$country_code		= $this_filter->get_string_multi_request('country_code',$country_code, "-1");
				$state_code			= $this_filter->get_string_multi_request('state_code',$state_code, "-1");
				$order_status		= $this_filter->get_string_multi_request('order_status',$order_status, "-1");
				$hide_order_status	= $this_filter->get_string_multi_request('hide_order_status',$hide_order_status, "-1");//New Change ID 20140918
				
				
				$sql = "  SELECT
				SUM(woocommerce_order_itemmeta_tax_amount.meta_value)  			AS _order_tax,
				SUM(woocommerce_order_itemmeta_shipping_tax_amount.meta_value)  AS _shipping_tax_amount,
				
				SUM(postmeta1.meta_value)  									AS _order_shipping_amount,
				SUM(postmeta2.meta_value)  									AS _order_total_amount,
				COUNT(posts.ID)  											AS _order_count,
				
				woocommerce_order_items.order_item_name 					AS tax_rate_code, 
				woocommerce_tax_rates.tax_rate_name 						AS tax_rate_name, 
				(woocommerce_tax_rates.tax_rate + 0)						AS order_tax_rate,				
				woocommerce_order_itemmeta_tax_amount.meta_value 			AS order_tax,
				woocommerce_order_itemmeta_shipping_tax_amount.meta_value 	AS shipping_tax_amount,
				postmeta1.meta_value 										AS order_shipping_amount,
				postmeta2.meta_value 										AS order_total_amount,
				postmeta3.meta_value 										AS billing_state,
				(SUM(woocommerce_order_itemmeta_tax_amount.meta_value)  + SUM(woocommerce_order_itemmeta_shipping_tax_amount.meta_value))						AS _total_tax,	
				postmeta4.meta_value 										AS billing_country
				";
				
				switch($tax_group_by){
					case "tax_group_by_state":
						$group_sql = ", CONCAT(woocommerce_order_items.order_item_name,'-',woocommerce_tax_rates.tax_rate_name,'-',woocommerce_tax_rates.tax_rate,'-',postmeta4.meta_value,'',postmeta3.meta_value) as group_column";
						break;
					case "tax_group_by_tax_name":
						$group_sql = ", CONCAT(woocommerce_order_items.order_item_name,'-',woocommerce_tax_rates.tax_rate_name,'-',woocommerce_tax_rates.tax_rate,'-',postmeta4.meta_value,'',postmeta3.meta_value) as group_column";
						break;
					case "tax_group_by_tax_summary":
						$group_sql = ", CONCAT(woocommerce_order_items.order_item_name,'-',woocommerce_tax_rates.tax_rate_name,'-',woocommerce_tax_rates.tax_rate,'-') as group_column";
						break;
					case "tax_group_by_state_summary":
						$group_sql = ", CONCAT(postmeta4.meta_value,'',postmeta3.meta_value) as group_column";
						break;
					default:
						$group_sql = ", CONCAT(woocommerce_order_items.order_item_name,'-',woocommerce_tax_rates.tax_rate_name,'-',woocommerce_tax_rates.tax_rate,'-',postmeta4.meta_value,'',postmeta3.meta_value) as group_column";
						break;
					
				}
				
				$sql .= $group_sql;	
				
				$sql = apply_filters("ic_commerce_report_page_select_query", $sql, $request, $type, $page, $report_name, $columns);							
				
				$sql .= " FROM {$wpdb->prefix}woocommerce_order_items as woocommerce_order_items";
				
				if(($order_status_id  && $order_status_id != '-1') || $sort_by == "status"){
					$sql .= " 
					LEFT JOIN  {$wpdb->prefix}term_relationships 	as term_relationships 	ON term_relationships.object_id		=	posts.ID
					LEFT JOIN  {$wpdb->prefix}term_taxonomy 		as term_taxonomy 		ON term_taxonomy.term_taxonomy_id	=	term_relationships.term_taxonomy_id";
					
					if($sort_by == "status"){
						$sql .= " LEFT JOIN  {$wpdb->prefix}terms 				as terms 				ON terms.term_id					=	term_taxonomy.term_id";
					}
				}
					
				$sql .= " LEFT JOIN  {$wpdb->prefix}postmeta as postmeta1 ON postmeta1.post_id=woocommerce_order_items.order_id";
				$sql .= " LEFT JOIN  {$wpdb->prefix}postmeta as postmeta2 ON postmeta2.post_id=woocommerce_order_items.order_id";
				
				$sql .= " LEFT JOIN  {$wpdb->prefix}woocommerce_order_itemmeta as woocommerce_order_itemmeta_tax ON woocommerce_order_itemmeta_tax.order_item_id=woocommerce_order_items.order_item_id";
				$sql .= " LEFT JOIN  {$wpdb->prefix}woocommerce_order_itemmeta as woocommerce_order_itemmeta_tax_amount ON woocommerce_order_itemmeta_tax_amount.order_item_id=woocommerce_order_items.order_item_id";
				$sql .= " LEFT JOIN  {$wpdb->prefix}woocommerce_order_itemmeta as woocommerce_order_itemmeta_shipping_tax_amount ON woocommerce_order_itemmeta_shipping_tax_amount.order_item_id=woocommerce_order_items.order_item_id";
				
				$sql .= " LEFT JOIN  {$wpdb->prefix}woocommerce_tax_rates as woocommerce_tax_rates ON woocommerce_tax_rates.tax_rate_id=woocommerce_order_itemmeta_tax.meta_value";			
				$sql .= " LEFT JOIN  {$wpdb->prefix}posts as posts ON posts.ID=	woocommerce_order_items.order_id";
				
				$sql .= " LEFT JOIN  {$wpdb->prefix}postmeta as postmeta3 ON postmeta3.post_id=woocommerce_order_items.order_id";
				$sql .= " LEFT JOIN  {$wpdb->prefix}postmeta as postmeta4 ON postmeta4.post_id=woocommerce_order_items.order_id";
				
				if($country_code and $country_code != '-1')	$sql .= " LEFT JOIN  {$wpdb->prefix}postmeta as postmeta5 ON postmeta5.post_id=posts.ID";
				
				if($state_code and $state_code != '-1')	$sql .= " LEFT JOIN  {$wpdb->prefix}postmeta as postmeta_billing_state ON postmeta_billing_state.post_id=posts.ID";
			
				/*if($this->constants['post_order_status_found'] == 0 ){
					if(count($shop_order_status)>0){
						$sql .= " 
						LEFT JOIN  {$wpdb->prefix}term_relationships 	as term_relationships 	ON term_relationships.object_id		=	posts.ID
						LEFT JOIN  {$wpdb->prefix}term_taxonomy 		as term_taxonomy 		ON term_taxonomy.term_taxonomy_id	=	term_relationships.term_taxonomy_id";
					}
				}*/
				
				$sql = apply_filters("ic_commerce_report_page_join_query", $sql, $request, $type, $page, $report_name, $columns);
				
				$sql .= " WHERE postmeta1.meta_key = '_order_shipping' AND woocommerce_order_items.order_item_type = 'tax'";
				
				$sql .= " AND posts.post_type='shop_order' ";
				$sql .= " AND postmeta2.meta_key='_order_total' ";
				
				
				$sql .= " AND woocommerce_order_itemmeta_tax.meta_key='rate_id' ";
				$sql .= " AND woocommerce_order_itemmeta_tax_amount.meta_key='tax_amount' ";
				$sql .= " AND woocommerce_order_itemmeta_shipping_tax_amount.meta_key='shipping_tax_amount' ";
				
				$sql .= " AND postmeta3.meta_key='_billing_state'";
				$sql .= " AND postmeta4.meta_key='_billing_country'";
				
				if($order_status_id  && $order_status_id != '-1') $sql .= " AND term_taxonomy.term_id IN (".$order_status_id .")";
				
				if($country_code and $country_code != '-1')	$sql .= " AND postmeta5.meta_key='_billing_country'";
				
				if($state_code and $state_code != '-1')		$sql .= " AND postmeta_billing_state.meta_key='_billing_state'";
				
				if($country_code and $country_code != '-1')	$sql .= " AND postmeta5.meta_value IN (".$country_code.")";
				
				if($state_code and $state_code != '-1')	$sql .= " AND postmeta_billing_state.meta_value IN (".$state_code.")";
				
				if($order_status  && $order_status != '-1' and $order_status != "'-1'")$sql .= " AND posts.post_status IN (".$order_status.")";//New Change ID 20140918
				if($hide_order_status  && $hide_order_status != '-1' and $hide_order_status != "'-1'")$sql .= " AND posts.post_status NOT IN (".$hide_order_status.")";//New Change ID 20140918
				
				//$sql .= "  group by group_column";
			
				//$sql .= "  ORDER BY (woocommerce_tax_rates.tax_rate + 0)  ASC";
				
				
				$sql = apply_filters("ic_commerce_report_page_where_query", $sql, $request, $type, $page, $report_name, $columns);
				
				$group_sql = " group by group_column";
				
				$sql .= apply_filters("ic_commerce_report_page_group_query", $group_sql, $request, $type, $page, $report_name, $columns);	
				
				$sort_by 	= isset($request['sort_by']) ? $request['sort_by'] : 'total_amount';
				
				$order_by 	= isset($request['order_by']) ? $request['order_by'] : 'DESC';				
				
				$order_sql = " ORDER BY {$sort_by} {$order_by}";
				
				$sql .= apply_filters("ic_commerce_report_page_order_query", $order_sql, $request, $type, $page, $report_name, $columns);	
				
				$this_filter->items_query = $sql;
				
			}else{
				$sql = $this_filter->items_query;
			}
			
			$order_items = $this_filter->get_query_items($type,$sql,"_total_tax");
			
			//$this->print_array($order_items);
			return $order_items;
			
		}
		
		function get_ic_commerce_custom_all_customer_buy_products_query($type = 'limit_row', $columns = array(), $report_name = "", $this_filter = NULL){
			global $wpdb;
			
			if(!isset($this_filter->items_query)){
					$request 			= $this_filter->get_all_request();extract($request);
					$paid_customer		= $this_filter->get_string_multi_request('paid_customer',$paid_customer, "-1");
					$order_status		= $this_filter->get_string_multi_request('order_status',$order_status, "-1");
					$hide_order_status	= $this_filter->get_string_multi_request('hide_order_status',$hide_order_status, "-1");
					
					$category_product_id_string = $this_filter->get_products_list_in_category($category_id,$product_id);//Added 20150219
					$category_id 				= "-1";//Added 20150219
										
				$sql = " SELECT ";
				$sql .= "
							woocommerce_order_items.order_item_name				AS 'product_name'
							,woocommerce_order_items.order_item_id				AS order_item_id
							,SUM(woocommerce_order_itemmeta.meta_value)			AS 'quantity'
							,SUM(woocommerce_order_itemmeta6.meta_value)		AS 'total_amount'
							,woocommerce_order_itemmeta7.meta_value				AS product_id
							,postmeta_customer_user.meta_value					AS customer_id
							,DATE(shop_order.post_date) 						AS post_date 
							,postmeta_billing_billing_email.meta_value			AS billing_email
							,CONCAT(postmeta_billing_billing_email.meta_value,' ',woocommerce_order_itemmeta7.meta_value,' ',postmeta_customer_user.meta_value)			AS group_column
							,CONCAT(postmeta_billing_first_name.meta_value,' ',postmeta_billing_last_name.meta_value)		AS billing_name							
							";
				$sql = apply_filters("ic_commerce_report_page_select_query", $sql, $request, $type, $page, $report_name, $columns);
				$sql .= "
							FROM {$wpdb->prefix}woocommerce_order_items as woocommerce_order_items						
							LEFT JOIN  {$wpdb->prefix}woocommerce_order_itemmeta as woocommerce_order_itemmeta ON woocommerce_order_itemmeta.order_item_id=woocommerce_order_items.order_item_id
							LEFT JOIN  {$wpdb->prefix}woocommerce_order_itemmeta as woocommerce_order_itemmeta6 ON woocommerce_order_itemmeta6.order_item_id=woocommerce_order_items.order_item_id
							LEFT JOIN  {$wpdb->prefix}woocommerce_order_itemmeta as woocommerce_order_itemmeta7 ON woocommerce_order_itemmeta7.order_item_id=woocommerce_order_items.order_item_id						
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
				
				
				
				
				$sql .= " 	LEFT JOIN  {$wpdb->prefix}posts as shop_order ON shop_order.id=woocommerce_order_items.order_id";
				
				$sql .= " 	LEFT JOIN  {$wpdb->prefix}postmeta as postmeta_billing_first_name ON postmeta_billing_first_name.post_id		=	woocommerce_order_items.order_id";
				$sql .= " 	LEFT JOIN  {$wpdb->prefix}postmeta as postmeta_billing_last_name ON postmeta_billing_last_name.post_id			=	woocommerce_order_items.order_id";
				$sql .= " 	LEFT JOIN  {$wpdb->prefix}postmeta as postmeta_billing_billing_email ON postmeta_billing_billing_email.post_id	=	woocommerce_order_items.order_id";
				$sql .= " 	LEFT JOIN  {$wpdb->prefix}postmeta as postmeta_customer_user ON postmeta_customer_user.post_id	=	woocommerce_order_items.order_id";
							
				$sql = apply_filters("ic_commerce_report_page_join_query", $sql, $request, $type, $page, $report_name, $columns);
				
				$sql .= "
							WHERE woocommerce_order_itemmeta.meta_key	= '_qty'
							AND woocommerce_order_itemmeta6.meta_key	= '_line_total' 
							AND woocommerce_order_itemmeta7.meta_key 	= '_product_id'
							AND woocommerce_order_itemmeta7.meta_key 	= '_product_id'
							AND postmeta_billing_first_name.meta_key	= '_billing_first_name'
							AND postmeta_billing_last_name.meta_key		= '_billing_last_name'
							AND postmeta_billing_billing_email.meta_key	= '_billing_email'
							AND postmeta_customer_user.meta_key			= '_customer_user'
							";
							
				
				
				if ($start_date != NULL &&  $end_date !=NULL){
					$sql .= " 
							AND (DATE(shop_order.post_date) BETWEEN '".$start_date."' AND '". $end_date ."')";
				}
				
				if($product_id  && $product_id != "-1") 
					$sql .= "
							AND woocommerce_order_itemmeta7.meta_value IN (".$product_id .")";	
				
				if($category_id  && $category_id != "-1") 
					$sql .= "
							AND terms.term_id IN (".$category_id .")";	
				
				
				if($category_product_id_string  && $category_product_id_string != "-1") $sql .= " AND woocommerce_order_itemmeta7.meta_value IN (".$category_product_id_string .")";//Added 20150219	
				
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
				
				//$sql .= " GROUP BY  woocommerce_order_itemmeta7.meta_value";
				//$sql .= " ORDER BY total_amount DESC";
				
				if($paid_customer  && $paid_customer != '-1' and $paid_customer != "'-1'")$sql .= " AND postmeta_billing_billing_email.meta_value IN (".$paid_customer.")";
				
				//$sql .= " GROUP BY  group_column";
				
				//$sql .= " ORDER BY billing_name ASC, product_name ASC, total_amount DESC";
				
				
				$sql = apply_filters("ic_commerce_report_page_where_query", $sql, $request, $type, $page, $report_name, $columns);
				
				$group_sql = " GROUP BY  group_column";
				
				$sql .= apply_filters("ic_commerce_report_page_group_query", $group_sql, $request, $type, $page, $report_name, $columns);	
				
				$sort_by 	= isset($request['sort_by']) ? $request['sort_by'] : 'total_amount';
				
				$order_by 	= isset($request['order_by']) ? $request['order_by'] : 'DESC';				
				
				$order_sql = " ORDER BY {$sort_by} {$order_by}";
				
				
				//$order_sql = " ORDER BY billing_name ASC, product_name ASC, total_amount DESC";
				
				$sql .= apply_filters("ic_commerce_report_page_order_query", $order_sql, $request, $type, $page, $report_name, $columns);	
				
				//$this->print_sql($sql);
				
				$this_filter->items_query = $sql;
			}else{
				$sql = $this_filter->items_query;
			}
			
			$order_items = $this_filter->get_query_items($type,$sql);
			
			return $order_items;
		}
		
		function get_ic_commerce_report_page_columns($columns = array(), $report_name = ""){
			switch($report_name){
				case "category_page":
					$columns 	= array(
						"category_name"					=> __("Category Name", 				'icwoocommerce_textdomains')
						//,"parent_category_name"		=> __("Parent Category Name", 		'icwoocommerce_textdomains')
						,"quantity"						=> __("Quantity", 					'icwoocommerce_textdomains')
						,"total_amount"					=> __("Amount", 					'icwoocommerce_textdomains')
					);
					$group_by_parent_cat 	= $this->get_request('group_by_parent_cat',0,true);
					if($group_by_parent_cat == 1){
						unset($columns['category_name']);
					}
					break;
				
				case "tax_page":
					$columns = array(					
						"tax_rate_name"				=> __("Tax Name", 			'icwoocommerce_textdomains')
						,"order_tax_rate"			=> __("Tax Rate", 			'icwoocommerce_textdomains')				
						,"_order_count"				=> __("Order Count", 		'icwoocommerce_textdomains')
						,"_order_shipping_amount"	=> __("Shipping Amt.", 		'icwoocommerce_textdomains')
						,"_order_amount"			=> __("Gross Amt.", 		'icwoocommerce_textdomains')
						,"order_total_amount"		=> __("Net Amt.", 			'icwoocommerce_textdomains')
						,"_shipping_tax_amount"		=> __("Shipping Tax", 		'icwoocommerce_textdomains')
						,"_order_tax"				=> __("Order Tax", 			'icwoocommerce_textdomains')
						,"_total_tax"				=> __("Total Tax", 			'icwoocommerce_textdomains')
					);
					break;
				case "customer_buy_products_page":
					$columns 	= array(
						"product_sku" 		=> __("Product SKU", 		'icwoocommerce_textdomains')
						,"billing_name"		=> __("Customer Name", 		'icwoocommerce_textdomains')
						,"product_name"		=> __("Product Name", 		'icwoocommerce_textdomains')
						,"quantity"			=> __("Sales Qty.", 		'icwoocommerce_textdomains')
						,"product_stock"	=> __("Current Stock", 		'icwoocommerce_textdomains')
						,"total_amount"		=> __("Amount", 			'icwoocommerce_textdomains')
					);
					break;
				case "billing_state_page":
					$columns 	= array(
						"billing_state_name"		=> __("Billing State", 			'icwoocommerce_textdomains')
						,"billing_country_name"		=> __("Billing Country", 		'icwoocommerce_textdomains')
						,"order_count"				=> __("Order Count", 			'icwoocommerce_textdomains')
						,"total_amount"				=> __("Amount", 				'icwoocommerce_textdomains')
					);
					
					$billing_or_shipping 						= $this->get_request('billing_or_shipping','billing');
					if(isset($columns['billing_country'])) 		$columns['billing_country'] 	= $billing_or_shipping == "shipping" ? __( 'Shipping State' , 'icwoocommerce_textdomains') : $columns['billing_country'];
					if(isset($columns['billing_state_name'])) 	$columns['billing_state_name'] 	= $billing_or_shipping == "shipping" ? __( 'Shipping Country' , 'icwoocommerce_textdomains') : $columns['billing_state_name'];
					break;
			}
			
			return $columns;
		}
		
		function get_ic_commerce_report_page_result_columns($total_columns = array(), $report_name = ""){
			
			switch($report_name){
				case "tax_page":
					$total_columns = array(					
						"total_row_count"			=> __("Tax Count", 			'icwoocommerce_textdomains')
						,"_order_count"				=> __("Order Count", 		'icwoocommerce_textdomains')
						,"_order_shipping_amount"	=> __("Shipping Amt.", 		'icwoocommerce_textdomains')
						,"_order_amount"			=> __("Gross Amt.", 		'icwoocommerce_textdomains')
						,"order_total_amount"		=> __("Net Amt.", 			'icwoocommerce_textdomains')
						,"_shipping_tax_amount"		=> __("Shipping Tax", 		'icwoocommerce_textdomains')
						,"_order_tax"				=> __("Order Tax", 			'icwoocommerce_textdomains')
						,"_total_tax"				=> __("Total Tax", 			'icwoocommerce_textdomains')
					);
					break;
				case "category_page":
					
					$parent_categories_count 	= $this->get_request('parent_categories_count',0,true);
					if($parent_categories_count > 0){
						$total_columns 	= array(
							"total_row_count"			=> __("Category Count", 	'icwoocommerce_textdomains')
						);
					}else{
						$total_columns 	= array(
							"total_row_count"			=> __("Category Count", 	'icwoocommerce_textdomains')
							,"quantity"					=> __("Product Count", 		'icwoocommerce_textdomains')
							,"total_amount"				=> __("Total Amount", 		'icwoocommerce_textdomains')
						);
					}					
					break;
				case "customer_buy_products_page":
					$total_columns = array(
						"quantity"					=> __("Sales Quantity", 	'icwoocommerce_textdomains')
						,"total_amount"				=> __("Total Amount", 		'icwoocommerce_textdomains')
					);
					break;
				case "billing_state_page":
					$total_columns = array(
						"total_row_count"			=> __("Billing State Count", 		'icwoocommerce_textdomains')
						,"order_count"				=> __("Order Count", 				'icwoocommerce_textdomains')
						,"total_amount"				=> __("Total Amount", 				'icwoocommerce_textdomains')
					);
					$billing_or_shipping 							= $this->get_request('billing_or_shipping','billing');
					if(isset($total_columns['total_row_count'])) 	$total_columns['total_row_count'] 	= $billing_or_shipping == "shipping" ? __( 'Shipping State Count' , 'icwoocommerce_textdomains') : $total_columns['total_row_count'];
					
					break;
			}
			return $total_columns;
		}
		
		function get_ic_commerce_pdf_custom_column_right_alignment($custom_columns = array(),$column = array(), $report_name = NULL){
			switch($report_name){
				case "tax_page":
					$custom_columns = array(
						"order_tax_rate"			=> ""				
						,"_order_count"				=> ""
						,"_order_shipping_amount"	=> ""
						,"_order_amount"			=> ""
						,"order_total_amount"		=> ""
						,"_shipping_tax_amount"		=> ""
						,"_order_tax"				=> ""
						,"_total_tax"				=> ""
					);
					break;
			}
			return $custom_columns;
		}
		
		function get_ic_commerce_report_page_titles($page_titles = array(),$report_name = "", $plugin_options = array()){
			$page_titles 				= array(
				'product_page'					=> __('Product',				'icwoocommerce_textdomains')
				,'category_page'				=> __('Category',				'icwoocommerce_textdomains')
				,'customer_page'				=> __('Customer',				'icwoocommerce_textdomains')
				,'billing_country_page'			=> __('Billing Country',		'icwoocommerce_textdomains')
				,'billing_state_page'			=> __('Billing State',			'icwoocommerce_textdomains')
				,'payment_gateway_page'			=> __('Payment Gateway',		'icwoocommerce_textdomains')
				,'order_status'					=> __('Order Status',			'icwoocommerce_textdomains')
				,'recent_order'					=> __('Recent Order',			'icwoocommerce_textdomains')
				//,'tax_page'						=> __('Tax Report',				'icwoocommerce_textdomains')					
				//,'customer_buy_products_page'	=> __('Customer Buy Products',	'icwoocommerce_textdomains')
				,'manual_refund_detail_page'	=> __('Refund Details',			'icwoocommerce_textdomains')
				,'coupon_page'					=> __('Coupon',					'icwoocommerce_textdomains')				
			);
			
			
			$billing_or_shipping	= $this->get_setting('billing_or_shipping',$plugin_options, 'billing');
			if(isset($page_titles['billing_country_page'])) $page_titles['billing_country_page'] 	= $billing_or_shipping == "shipping" ? __( 'Shipping Country' , 'icwoocommerce_textdomains') : $page_titles['billing_country_page'];
			if(isset($page_titles['billing_state_page'])) 	$page_titles['billing_state_page'] 		= $billing_or_shipping == "shipping" ? __( 'Shipping State' , 'icwoocommerce_textdomains') : $page_titles['billing_state_page'];
			
			return $page_titles;	
		}
		//Start End Reports
		
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
    }//End Class
}//End Class
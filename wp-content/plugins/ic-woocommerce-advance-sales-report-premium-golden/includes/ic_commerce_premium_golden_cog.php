<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
if( ! class_exists('IC_Commerce_Premium_Golden_COG')){
	class IC_Commerce_Premium_Golden_COG{
		
		public $constants 	=	array();
		
		public function __construct($file = '',$plugin_key = '', $constants = array()) {
			
			
			$this->constants	= $constants;
			
			$this->constants['plugin_key']	= $plugin_key;
			
			$this->constants['plugin_file']	= $file;
			
			//$this->define_constant();
			
			//$this->print_array($this->constants);	
			
			//add_filter("woocommerce_hidden_order_itemmeta", array($this, 'woocommerce_hidden_order_itemmeta'));
			add_filter("woocommerce_attribute_label", array($this, 'woocommerce_attribute_label'));
			
			//add_action('woocommerce_admin_order_item_values',array($this, "woocommerce_admin_order_item_values"),10,3);
			//add_action('woocommerce_admin_order_item_headers',array($this, "woocommerce_admin_order_item_headers"));
			
		}
		
		function define_constant(){
			
				
				if(isset($this->constants['cogs_metakey_simple'])) return '';
				//echo "fdsafdasf";
				$this->constants['default_cogs_metakey']					= '_ic_cogs_cost';//Added 20150323				
				$this->constants['default_cogs_metakey_simple']				= '_ic_cogs_cost_simple';//Added 20150323
				$this->constants['default_cogs_metakey_variable']			= '_ic_cogs_cost_variable';//Added 20150323
				
				$this->constants['default_cogs_metakey_order_total']		= '_ic_cogs_order_total';//Added 20150323
				$this->constants['default_cogs_metakey_item']				= '_ic_cogs_item';//Added 20150323
				$this->constants['default_cogs_metakey_item_total']			= '_ic_cogs_item_total';//Added 20150323
				
				$this->constants['plugin_options'] 							= isset($this->constants['plugin_options']) ? $this->constants['plugin_options'] : get_option($this->constants['plugin_key']);
				
				$this->constants['cogs_metakey']							= $this->get_setting('cogs_metakey',			$this->constants['plugin_options'],$this->constants['default_cogs_metakey']);//Added 20150323
				$this->constants['cogs_metakey_simple']						= $this->get_setting('cogs_metakey_simple',		$this->constants['plugin_options'],$this->constants['default_cogs_metakey_simple']);//Added 20150323
				$this->constants['cogs_metakey_variable']					= $this->get_setting('cogs_metakey_variable',	$this->constants['plugin_options'],$this->constants['default_cogs_metakey_variable']);//Added 20150323
				
				$this->constants['cogs_metakey_order_total']				= $this->get_setting('cogs_item_order_total',	$this->constants['plugin_options'],$this->constants['default_cogs_metakey_order_total']);//Added 20150323
				$this->constants['cogs_metakey_item']						= $this->get_setting('cogs_metakey_simple',		$this->constants['plugin_options'],$this->constants['default_cogs_metakey_item']);//Added 20150323
				$this->constants['cogs_metakey_item_total']					= $this->get_setting('cogs_metakey_variable',	$this->constants['plugin_options'],$this->constants['default_cogs_metakey_item_total']);//Added 20150323
				
				return $this->constants;
		}
		
		function init(){
			$cogs_enable_adding = $this->get_setting('cogs_enable_adding',$this->constants['plugin_options'],0);//Added 20150323
			if($cogs_enable_adding == 1){
				if($this->constants['is_wc_ge_3_0_5']){
					add_action( 'woocommerce_new_order_item', array($this, 'woocommerce_add_order_item_meta'), 101, 3);
				}else{
					add_action( 'woocommerce_add_order_item_meta', array($this, 'woocommerce_add_order_item_meta' ), 101, 3);
				}
				add_action( 'woocommerce_checkout_update_order_meta', array($this, 'woocommerce_checkout_update_order_meta'));
			}
			
		}
		
		function admin_init(){
			$cogs_enable_adding = $this->get_setting('cogs_enable_adding',$this->constants['plugin_options'],0);//Added 20150323
			if($cogs_enable_adding == 0) return true;
			//Add/Edit Product
			add_action( 'woocommerce_product_options_general_product_data', 	array(&$this,'woocommerce_general_product_data_custom_field') );
			add_action( 'woocommerce_process_product_meta', 					array(&$this,'woocommerce_process_product_meta_fields_save') );
			add_action( 'woocommerce_variation_options', 						array(&$this,'variable_fields'), 10, 3 );
			add_action( 'woocommerce_product_after_variable_attributes_js',		array(&$this,'variable_fields_js' ));
			add_action( 'woocommerce_process_product_meta_variable',  			array(&$this,'save_variable_fields'), 10, 1 );
			add_action( 'woocommerce_variable_product_bulk_edit_actions',  		array(&$this,'woocommerce_variable_product_bulk_edit_actions'), 10, 1 );
			
			//Add/Edit Order
			//add_action( 'woocommerce_checkout_update_order_meta', 				array( $this, 'woocommerce_checkout_update_order_meta' ) );
		}
		
		function woocommerce_hidden_order_itemmeta($hidden_meta = array()){			
			$hidden_meta[] = $this->constants['cogs_metakey_simple'];
			$hidden_meta[] = $this->constants['cogs_metakey_variable'];
			$hidden_meta[] = $this->constants['cogs_metakey_item_total'];
			return $hidden_meta;//_ic_cogs_cost_simple
		}
		
		function woocommerce_attribute_label($attribute_label = ''){
			
			switch($attribute_label){
				case $this->constants['cogs_metakey_simple']:
				case $this->constants['cogs_metakey_variable']:
					$attribute_label = __('Cost Of Goods' , 'icwoocommerce_textdomains' );
					break;
				case $this->constants['cogs_metakey_item_total']:
					$attribute_label = __('Total Cost Of Goods' , 'icwoocommerce_textdomains' );
					break;
				default:
					$attribute_label = $attribute_label;
					break;
			}
			
			return $attribute_label;
		}
		
		function woocommerce_admin_order_item_headers(){
			?>
            <th class="item_cog sortable" data-sort="float"><?php _e( 'COGs', 'icwoocommerce_textdomains' ); ?></th>
            <th class="item_total_cog sortable" data-sort="float"><?php _e( 'Total COGs', 'icwoocommerce_textdomains' ); ?></th>
            <?php
		}
		
		function woocommerce_admin_order_item_values( $_product, $item = array(), $item_id = 0){
			
			$cost_of_goods 			 = 0;
			$total_cost_of_goods 	 = 0;
			
			if($_product){
				$variation_id 	= isset($item['variation_id']) ? $item['variation_id'] : 0;
			
				$cogs_metakey_variable = isset($this->constants['cogs_metakey_variable']) ? ltrim($this->constants['cogs_metakey_variable'],"_") : "";
				$cogs_metakey_simple = isset($this->constants['cogs_metakey_simple']) ? ltrim($this->constants['cogs_metakey_simple'],"_") : "";
				$cogs_metakey_item_total = isset($this->constants['cogs_metakey_item_total']) ? ltrim($this->constants['cogs_metakey_item_total'],"_") : "";
				
				if($variation_id > 0){
					$cost_of_goods 	 = isset($item[$cogs_metakey_variable]) ? $item[$cogs_metakey_variable] : 0;	
				}else{
					$cost_of_goods	 = isset($item[$cogs_metakey_simple]) ? $item[$cogs_metakey_simple] : 0;
				}
				
				$total_cost_of_goods = isset($item[$cogs_metakey_item_total]) ? $item[$cogs_metakey_item_total] : 0;
			}
			
			?>
				<td class="item_cog" width="1%" data-sort-value="<?php echo $cost_of_goods; ?>">
					<div class="view">
						<?php if($_product)echo wc_price($cost_of_goods);?>
					</div>
				</td>
				
				<td class="item_total_cog" width="1%" data-sort-value="<?php echo $total_cost_of_goods; ?>">
					<div class="view">
						<?php if($_product)echo wc_price($total_cost_of_goods);?>
					</div>
				</td>
				<?php
			
			
		}
		
		function woocommerce_general_product_data_custom_field(){
			
			 $cogs_metakey 					= $this->constants['cogs_metakey_simple'];
			 echo '<div class="options_group">';		 
			 woocommerce_wp_text_input( 
					array( 
						'id'                => 	$cogs_metakey, 
						'label'       		=> __('Cost Of Goods ' , 'icwoocommerce_textdomains' ) .' (' . get_woocommerce_currency_symbol() . ')', 
						'placeholder'       => __('Enter Cost Of Goods', 'icwoocommerce_textdomains' ), 
						'description'       => __('Enter the custom value here.', 'icwoocommerce_textdomains' ),
						'type'              => 'text', 
						'class' 			=> 'wc_input_price',
						'custom_attributes' => array(
								'step' 	=> 'any',
								'min'	=> '0'
							) 
					)
				);
			 echo '</div>';
		}
		
		function woocommerce_process_product_meta_fields_save( $post_id ){
				$cogs_metakey 			= $this->constants['cogs_metakey_simple'];
				$woo_cots_of_goods = isset( $_POST[$cogs_metakey] ) ?  $_POST[$cogs_metakey] : '0';
				update_post_meta( $post_id, $cogs_metakey, $woo_cots_of_goods );
		}
		
		
		function variable_fields($loop, $variation_data,$variation){
			$cogs_metakey    			= $this->constants['cogs_metakey_simple'];
		 	echo '<div class="options_group">';
			$value = get_post_meta($variation->ID, $cogs_metakey , true);			
			woocommerce_wp_text_input( 
				array( 
					'id'          			=> '_variable_cots_of_goods['.$loop.']', 
					'label'       			=> __('Cost Of Goods', 'icwoocommerce_textdomains' ), 
						'placeholder' 		=> __('Enter Cost Of Goods', 'icwoocommerce_textdomains' ), 
						'description' 		=> __('Enter the Cost Of Goods.', 'icwoocommerce_textdomains' ),
						'desc_tip'    		=> 'true',
						'type'              => 'text', 
						'class' 			=> 'wc_input_price',
						'value' 			=> isset($value) ? $value : '0',
						'custom_attributes' => array(
								'step' 	=> 'any',
								'min'	=> '0'
						)
				)
			);
		 echo '</div>';
	}
		
		function variable_fields_js(){
			$cogs_metakey 					= $this->constants['cogs_metakey_simple'];
			echo '<div class="options_group">';
				$value = get_post_meta($variation->ID, $cogs_metakey, 0);
				woocommerce_wp_text_input( 
					array( 
						'id'          		=> '_variable_cots_of_goods[ + loop + ]', 
						'label'       		=> __('Cost Of Goods', 'icwoocommerce_textdomains' ), 
						'placeholder' 		=> __('Enter Cost Of Goods', 'icwoocommerce_textdomains' ), 
						'description' 		=> __('Enter the Cost Of Goods.', 'icwoocommerce_textdomains' ),
						'desc_tip'    		=> 'true',
						'type'              => 'text', 
						'class' 			=> 'wc_input_price',
						'value' 			=> isset($value) ? $value : '0',
						'custom_attributes' => array(
								'step' 	=> 'any',
								'min'	=> '0'
						) 
					)
				);
			echo '</div>';
		}
		
		function save_variable_fields( $post_id ){ 
			if (isset( $_POST['variable_sku'] ) ) :	
				$cogs_metakey    		= $this->constants['cogs_metakey_simple'];
				$variable_sku          = $_POST['variable_sku'];
				$variable_post_id      = $_POST['variable_post_id'];			
	
				$_text_field = $_POST['_variable_cots_of_goods'];
				
				for ( $i = 0; $i < sizeof( $variable_sku ); $i++ ) :
					$variation_id = (int) $variable_post_id[$i];
					
					if ( isset( $_text_field[$i] ) ) {
						update_post_meta( $variation_id, $cogs_metakey, stripslashes( $_text_field[$i] ) );
					}
				endfor;			
			endif;
		}
		
		function woocommerce_variable_product_bulk_edit_actions(){
			echo '<option value="variable_cots_of_goods">'. __( 'Cost Of Goods', 'icwoocommerce_textdomains' ).'</option>';
			add_action('admin_footer',array($this, 'woocommerce_variable_product_bulk_edit_actions_js'));
		}
		
		function woocommerce_variable_product_bulk_edit_actions_js(){
			?>
            	<script type="text/javascript">                	
                	jQuery('.wc-metaboxes-wrapper').on('click', 'a.bulk_edit', function(event){
						var field_to_edit = jQuery('select#field_to_edit').val();						
						if ( field_to_edit == 'variable_cots_of_goods' ) {							
							var input_tag = jQuery('select#field_to_edit :selected').attr('rel') ? jQuery('select#field_to_edit :selected').attr('rel') : 'input';			
							var value = prompt("<?php echo esc_js( __( 'Enter Cost Of Goods', 'icwoocommerce_textdomains' ) ); ?>");
							jQuery(input_tag + '[name^="_' + field_to_edit + '["]').val( value ).change();
							return false;
						}
					});
                </script>
            <?php
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
		
		public function woocommerce_add_order_item_meta( $item_id = 0, $values = array() ) {

			// get product ID
			
			$cogs_enable_adding = $this->get_setting('cogs_enable_adding',$this->constants['plugin_options'],0);//Added 20150323
			
			if($cogs_enable_adding == 0) return true;
			
			$variation_id		= (!empty( $values['variation_id'])) ? $values['variation_id'] : 0;
			
			$product_id 		= (!empty( $values['variation_id'])) ? $values['variation_id'] : (isset($values['product_id']) ? $values['product_id'] : 0);
			
			$item_cost 			= $this->get_product_cost($product_id, $variation_id);
			
			$quantity 			= isset($values['quantity']) ? $values['quantity'] : 0;
			
			$total_item_cost 	= $item_cost * $quantity;
			
			$item_cost 			= $this->wc_format_decimal($item_cost, 5);
			
			$total_item_cost	= $this->wc_format_decimal($total_item_cost, 5);
			
			$cogs_metakey_item = isset($this->constants['cogs_metakey_item']) ? $this->constants['cogs_metakey_item'] : '';
			wc_add_order_item_meta( $item_id, $cogs_metakey_item, $item_cost);
			
			$cogs_metakey_item_total = isset($this->constants['cogs_metakey_item_total']) ? $this->constants['cogs_metakey_item_total'] : '';
			wc_add_order_item_meta( $item_id, $cogs_metakey_item_total,  $total_item_cost);
		}
		
		public function woocommerce_checkout_update_order_meta( $order_id ) {
			
			$order_cost_total = 0;
	
			foreach ( WC()->cart->get_cart() as $cart_item_key => $values ) {
	
				$variation_id	= (!empty( $values['variation_id'])) ? $values['variation_id'] : 0;
			
				$product_id 	= (!empty( $values['variation_id'])) ? $values['variation_id'] : (isset($values['product_id']) ? $values['product_id'] : 0);
	
				$item_cost 		= $this->get_product_cost($product_id, $variation_id);
	
				// sum the individual line item cost totals
				$order_cost_total += ( $item_cost * $values['quantity'] );
			}
			
			$cogs_metakey_order_total = isset($this->constants['cogs_metakey_order_total']) ? $this->constants['cogs_metakey_order_total'] : '';
			
			$order_cost_total = $this->wc_format_decimal($order_cost_total, 5);
			
			update_post_meta( $order_id, $cogs_metakey_order_total, $order_cost_total);
		}
		
		function get_product_cost($product_id = 0, $variation_id = 0){
			
			$product_id 	= trim($product_id);
			
			if($variation_id > 0){
				$cogs_metakey_variable = isset($this->constants['cogs_metakey_variable']) ? $this->constants['cogs_metakey_variable'] : '';
				$cost 			= get_post_meta( $product_id, $cogs_metakey_variable, true );
			}else{
				$cogs_metakey_simple = isset($this->constants['cogs_metakey_simple']) ? $this->constants['cogs_metakey_simple'] : '';
				$cost 			= get_post_meta( $product_id, $cogs_metakey_simple, true );
			}
			
			if (empty($cost)) {
				$cost = 0;
			}
			
			return $cost;
		}
		
		
		function wc_format_decimal($order_cost_total, $dip = 5){
			if(function_exists('wc_format_decimal')){
				$order_cost_total = wc_format_decimal($order_cost_total, $dip);
			}
			
			return $order_cost_total;
		}
		
		function get_product($product_id = 0, $args = array()){
			if(function_exists('wc_get_product')){
				$product = get_product( $product_id, $args);
			}else if(function_exists('get_product')){
				$product = get_product( $product_id, $args);
			}			
			
			return $product;
		}
		
		function get_setting($id, $data, $defalut = NULL){
			if(isset($data[$id]))
				return $data[$id];
			else
				return $defalut;
		}
		
		function get_cogs_enabled(){
			$cogs_enable_adding = $this->get_setting('cogs_enable_adding',$this->constants['plugin_options'],0);//Added 20150323
			
			if($cogs_enable_adding == 0) return false;
			
			return true;
		}
		
	}//End Class
	
	
}//End Class

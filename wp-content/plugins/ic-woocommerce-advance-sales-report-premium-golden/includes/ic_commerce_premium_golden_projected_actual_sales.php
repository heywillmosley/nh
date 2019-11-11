<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
if ( ! class_exists( 'IC_Commerce_Premium_Golden_Projected_Actual_Sales' ) ) {
	require_once('ic_commerce_premium_golden_fuctions.php');
	class IC_Commerce_Premium_Golden_Projected_Actual_Sales extends IC_Commerce_Premium_Golden_Fuctions{
		
		public $per_page = 0;	
		
		public $per_page_default = 10;
		
		public $request_data =	array();
		
		public $constants 	=	array();
		
		public $request		=	array();
		public $today 		=	'';
		
		public function __construct($constants) {
			global $options;
			
			$this->constants		= $constants;			
			$options				= $this->constants['plugin_options'];$this->is_active();
		}
		
		function init(){
				global $back_day,$report_title;			
				if(!isset($_REQUEST['page'])){return false;}
				
				if ( !current_user_can( $this->constants['plugin_role'] ) )  {
					wp_die( __( 'You do not have sufficient permissions to access this page.' ,'icwoocommerce_textdomains' ) );
				}
				
				do_action('ic_commerce_current_user_can',$_REQUEST['page'],$this->constants);
				$onload_search				= 'yes';
				$onload_search				= apply_filters('ic_commerce_onload_search', $onload_search, '');
				
				//New Change ID 20140918
				$shop_order_status		= $this->get_set_status_ids();	
				$hide_order_status		= $this->constants['hide_order_status'];
				$hide_order_status		= implode(",",$hide_order_status);
				$shop_order_status 		= implode(",",$shop_order_status);
				
				$hide_order_status		= strlen($hide_order_status) > 0 	?  $hide_order_status 	: '-1';
				$shop_order_status		= strlen($shop_order_status) > 0 	?  $shop_order_status 	: '-1';
				
				$start_date 			= $this->constants['start_date'];
				$end_date 				= $this->constants['end_date'];
				
				
				$ToDate				= $this->get_request('end_date',$end_date);
				$FromDate			= $this->get_request('start_date',$start_date);
				
				$page				= $this->get_request('page',NULL);
				$report_name 		= apply_filters($page.'_default_report_name', 'projected_actual_sales_page');
				$report_name 		= $this->get_request('report_name',$report_name,true);
				$admin_page			= $this->get_request('admin_page',$page,true);
				
				if($this->is_product_active != 1)  return true;
				$action				= $this->get_request('action',$this->constants['plugin_key'].'_wp_ajax_action',true);
				$do_action_type		= $this->get_request('do_action_type','projected_actual_sales_page',true);
				
				$page_titles = array(
					'projected_actual_sales_page'=>__('Projected Vs Actual Sales','icwoocommerce_textdomains')
				);
				
				$page_title 		= isset($page_titles[$report_name]) ? $page_titles[$report_name] : $report_name;				
				$page_title 		= apply_filters($page.'_report_name_'.$report_name, $page_title);
				
				$_REQUEST['page_title'] = $page_title;
				if($report_name != 'coupon_page')
					$_REQUEST['page_name'] = 'all_detail';
				else
					$_REQUEST['page_name'] = $report_name;
				
				
				$plugin_options 	= $this->constants['plugin_options'];
				
				?>
               
                <h2 class="hide_for_print"><?php _e($page_title,$this->constants['plugin_key']." Projected Vs Actual Sales");?></h2>

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
                                                    <div class="label-text"><label for="projected_sales_year"><?php _e('Select Year:','icwoocommerce_textdomains'); ?></label></div>													
                                                    <div class="input-text">
														<?php 
															//$first_order_year	= date('Y',strtotime($this->constants['first_order_date']));
															$first_order_year	= date('Y',strtotime('20100101'));
															$current_year		= date('Y',strtotime($this->constants['today_date']));
															$cur_projected_sales_year=isset($plugin_options['cur_projected_sales_year']) ? trim($plugin_options['cur_projected_sales_year']) : date('Y',strtotime($this->constants['today_date']));
															for($y=$first_order_year;$y<=$current_year;$y++) $projected_sales_year[$y] = $y;
															$this->create_dropdown($projected_sales_year,"projected_sales_year","projected_sales_year","","projected_sales_year",$cur_projected_sales_year, 'array', false);
														?>
													</div>
                                                </div>
                                            </div>
                                            
                                            <div class="form-group">
                                                <div class="FormRow" style="width:100%">                                                    
                                                    
                                                    <input type="hidden" name="end_date"  				id="end_date" 			value="<?php echo $this->get_request('end_date',$end_date,true);?>" />
                                                    <input type="hidden" name="start_date"  			id="start_date" 		value="<?php echo $this->get_request('start_date',$start_date,true);?>" />
                                                    <input type="hidden" name="hide_order_status"		id="hide_order_status"	value="<?php echo $this->get_request('hide_order_status',$hide_order_status,true);?>" />
                                                    <input type="hidden" name="shop_order_status"		id="shop_order_status"	value="<?php echo $this->get_request('shop_order_status',$shop_order_status,true);?>" />
                                                   
                                                   
                                                    <input type="hidden" name="action" 					id="action" 			value="<?php echo $this->get_request('action',$this->constants['plugin_key'].'_wp_ajax_action',true);?>" />
                                                    <input type="hidden" name="admin_page"  			id="admin_page" 		value="<?php echo $this->get_request('admin_page',$page,true);?>" />
                                                    <input type="hidden" name="do_action_type" 			id="do_action_type" 	value="<?php echo $this->get_request('do_action_type','projected_actual_sales_page',true);?>" />
                                                    <input type="hidden" name="ic_admin_page" 			id="ic_admin_page" 		value="<?php echo $this->get_request('ic_admin_page',$page,true);?>" />
                                                    <span class="submit_buttons">
														<input name="SearchOrder" id="SearchOrder" class="onformprocess searchbtn" value="<?php _e('Search','icwoocommerce_textdomains'); ?>" type="submit"> &nbsp; &nbsp; &nbsp; <span class="ajax_progress"></span>
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
                	<div class="search_report_content hide_for_print">
                    	<?php if($onload_search == "no") {echo apply_filters('ic_commerce_onload_search_text', '');}?>
                    </div>
                    <style type="text/css">
                	.widefat.table_sales_by_month tr:last-child {
						font-weight: bold;
						background-color: #666;
					}
					
					.widefat.table_sales_by_month tr:last-child td {
						color: #FFF;
						font-size: 15px;
					}
					
					.widefat.table_sales_by_month tr:last-child{ font-weight:bold; background-color:#666;}
					.widefat.table_sales_by_month tr:last-child td{ color:#FFF; font-size:15px;}
					
					.widefat.table_sales_by_month tbody tr:last-child:hover{background-color:#666;}

                </style>
                <?php
		}  
		
		
		var $variation_query = '';
		function ic_commerce_ajax_request($type = 'limit_row'){
			global $report_title;			
			$shop_order_status			= $this->get_request('shop_order_status');			
			if(strlen($shop_order_status)>0 and $shop_order_status != "-1") $shop_order_status = explode(",",$shop_order_status); else $shop_order_status = array();
			
			$hide_order_status 		= $this->constants['hide_order_status'];
			$start_date 			= $this->constants['start_date'];
			$end_date 				= $this->constants['end_date'];
			$this->get_monthly_summary($shop_order_status,$hide_order_status,$start_date,$end_date);
		}
		
		function get_monthly_summary($shop_order_status,$hide_order_status,$start_date,$end_date){
			include_once("ic_commerce_premium_golden_monthly_summary.php");
			
			$parameters = array('shop_order_status'=>$shop_order_status,'hide_order_status'=>$hide_order_status,'start_date'=>$start_date,'end_date'=>$end_date);
			
			$monthly_summary = new IC_Commerce_Premium_Golden_Monthly_Summary($this->constants , $parameters);
			
			echo $monthly_summary->detail_page();
			
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
	}
}
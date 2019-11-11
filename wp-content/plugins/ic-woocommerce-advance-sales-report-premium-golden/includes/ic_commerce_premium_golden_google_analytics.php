<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
if ( ! class_exists( 'IC_Commerce_Premium_Golden_Google_Analytics' ) ) {
	require_once('ic_commerce_premium_golden_fuctions.php');
	class IC_Commerce_Premium_Golden_Google_Analytics extends IC_Commerce_Premium_Golden_Fuctions{
		
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
		
		function init(){
				global $back_day,$report_title;			
				if(!isset($_REQUEST['page'])){return false;}
				
				if ( !current_user_can( $this->constants['plugin_role'] ) )  {
					wp_die( __( 'You do not have sufficient permissions to access this page.','icwoocommerce_textdomains' ) );
				}
				
				$this->is_active();
				
				$page				= $this->get_request('page',NULL);
				$report_name 		= apply_filters($page.'_default_report_name', 'product_visit_cross_tab');
				
				$optionsid			= "per_row_cross_tab_page";
				$per_page 			= $this->get_number_only($optionsid,$this->per_page);
				
				$per_page			= 10000;
				
				$report_name 		= $this->get_request('report_name',$report_name,true);
				$admin_page			= $this->get_request('admin_page',$page,true);
				$adjacents			= $this->get_request('adjacents','3',true);
				$p					= $this->get_request('p','1',true);
				$limit				= $this->get_request('limit',$per_page,true);
				$ToDate				= $this->get_request('end_date',false);
				$FromDate			= $this->get_request('start_date',false);
				
				if($this->is_product_active != 1)  return true;	
				$action				= $this->get_request('action',$this->constants['plugin_key'].'_wp_ajax_action',true);
				$do_action_type		= $this->get_request('do_action_type','google_analytics',true);
				
				$first_date 		= $this->constants['first_order_date'];
				
				
				$start_date 		= date('Y-m-d', strtotime('-30 day', strtotime(date_i18n("Y-m-d"))));
				$end_date 			= date_i18n('Y-m-d');
				
				if(!$ToDate){$ToDate = trim($end_date);}
				if(!$FromDate){$FromDate = trim($start_date);}
				
				$FromDate 			= apply_filters('ic_commerce_google_analytics_report_page_start_date',	$FromDate);
				$ToDate 			= apply_filters('ic_commerce_google_analytics_report_page_end_date',		$ToDate);
				
				
				$_REQUEST['end_date'] = $ToDate;
				$_REQUEST['start_date'] = $FromDate;
				
				
				
				$page_titles = array(
					'product_visit_cross_tab'=>__('Google Analytics Overview','icwoocommerce_textdomains')
				);
				
				$page_title 		= isset($page_titles[$report_name]) ? $page_titles[$report_name] : $report_name;				
				$page_title 		= apply_filters($page.'_report_name_'.$report_name, $page_title);
				
				$_REQUEST['page_title'] = $page_title;
				$_REQUEST['page_name'] = 'cross_tab_detail';					
				?>
               
               <h2 class="hide_for_print"><?php _e($page_title,'icwoocommerce_textdomains');?></h2>
                
              
                <br />
                
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
                                                    <div class="label-text"><label for="start_date"><?php _e("Start Date:",'icwoocommerce_textdomains');?></label></div>
                                                    <div class="input-text"><input type="text" value="<?php echo $FromDate;?>" id="start_date" name="start_date" readonly maxlength="7" /></div>
                                                </div>
                                                <div class="FormRow">
                                                    <div class="label-text"><label for="end_date"><?php _e("End Date:",'icwoocommerce_textdomains');?></label></div>
                                                    <div class="input-text"><input type="text" value="<?php echo $ToDate;?>" id="end_date" name="end_date" readonly maxlength="7" /></div>
                                                </div>
                                            </div>
                                            
                                            
                                            <div class="form-group">
                                                <div class="FormRow FirstRow">
                                                    <div class="label-text"><label for="report_overview_by"><?php _e("Overview By:",'icwoocommerce_textdomains');?></label></div>
                                                    <div class="input-text">
                                                        <?php 
                                                            $category_data = array(
																"date"		=>		__("Day",'icwoocommerce_textdomains'),
																"week"		=>		__("Week",'icwoocommerce_textdomains'),
																"month"		=>		__("Month",'icwoocommerce_textdomains'),
																"year"		=>		__("Year",'icwoocommerce_textdomains')
															);
                                                            $this->create_dropdown($category_data,"report_overview_by","report_overview_by2","","report_overview_by2",'date', 'array', false, 5);
                                                        ?>                                                        
                                                    </div> 
                                                </div>
                                                
                                            </div>
                                            
                                            
                                            
                                            <div class="form-group">
                                                <div class="FormRow " style="width:100%">
                                                        <input type="hidden" name="action" id="action" value="<?php echo $this->constants['plugin_key'].'_wp_ajax_action';?>" />
                                                        <input type="hidden" name="limit"  id="limit" value="<?php echo $limit;?>" />
                                                        <input type="hidden" name="p"  id="p" value="<?php echo $p;?>" />
                                                        <input type="hidden" name="admin_page"  id="admin_page" value="<?php echo $admin_page;?>" />
                                                        <input type="hidden" name="page"  id="page" value="<?php echo $page;?>" />
                                                        <input type="hidden" name="adjacents"  id="adjacents" value="<?php echo $adjacents;?>" />
                                                            <input type="hidden" name="report_name"  id="report_name" value="<?php echo $report_name;?>" />
                                                            <input type="hidden" name="do_action_type"  id="do_action_type" value="<?php echo $do_action_type;?>" /> 
                                                            <input type="hidden" name="page_title"  id="page_title" value="<?php echo $page_title;?>" />
                                                            <input type="hidden" name="page_name"  id="page_name" value="all_detail" /> 
                                                            <input type="hidden" name="date_format" 			id="date_format" 	value="<?php echo $this->get_request('date_format',get_option('date_format'),true);?>" />
                                                            <span class="submit_buttons">
                                                                <input name="ResetForm" id="ResetForm" class="onformprocess" value="<?php _e("Reset",'icwoocommerce_textdomains');?>" type="reset">
                                                                <input name="SearchOrder" id="SearchOrder" class="onformprocess searchbtn" value="<?php _e("Search",'icwoocommerce_textdomains');?>" type="submit">  &nbsp; &nbsp; &nbsp; <span class="ajax_progress"></span>
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
                    <?php //$this->ic_commerce_ajax_request('limit_row');?>
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
                                <input type="hidden" name="pdf_keywords" value="<?php _e("Google Analytics Overview",'icwoocommerce_textdomains');?>" />
                                <input type="hidden" name="pdf_description" value="<?php _e("Google Analytics Overview",'icwoocommerce_textdomains');?>" />
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
                            
                            <tr>
                                <td colspan="2"><input type="button" name="<?php echo $admin_page ;?>_export_print" class="onformprocess button_popup_close search_for_print" value="<?php _e("Print",'icwoocommerce_textdomains');?>" data-form="popup"  data-do_action_type="google_analytics_for_print" /></td>
                            </tr>                                
                        </table>
                        <input type="hidden" name="display_center" value="1" />
                    </form>
                    <div class="clear"></div>
                    </div>
                </div>
                <style type="text/css">
                	.Overflow .widefat td, .Overflow .widefat th{ text-align:right}
                </style>
                <div class="popup_mask"></div>
				<?php
		}//init			
		
		function ic_commerce_ajax_request($type = 'limit_row'){
			$this->get_grid($type);
		}
		function get_grid($type = 'total_row'){
			$order_count  =array();
			$visit_data  =array();
			$formatedDate ="";
			$order_count_total = 0;
			$visit_count_total = 0;
			
			$start_date				= $this->get_request('start_date','');
			$end_date				= $this->get_request('end_date','');
			$report_overview_by		= $this->get_request('report_overview_by','date',true);
			
			
			
			$order_count  		= $this->get_order_count($start_date,$end_date);
			$visit_data   		= $this->get_google_analytics_visits($start_date, $end_date, $order_count);
			//$this->print_array($_REQUEST);
			//$this->print_array($order_count);
			//$this->print_array($visit_data);
			
			if(!empty($this->constants['ga_api_notice'])){
				echo $this->constants['ga_api_notice'];
			}
			
			if($visit_data):
				?>
                 <div class="Overflow">
                	<?php $this->print_header($type);?>
					<?php if($type != 'all_row'):?>
                    <div class="top_buttons"><?php $this->export_to_csv_button('top');?><div class="clearfix"></div></div>
                    <?php else: $this->back_print_botton('top');?>
                    <?php endif;?>
                    
                    <table style="width:100%" class="widefat widefat-table">
                        <thead>
                            <tr>
                            	<th>Orders/Site Visitors</th>
                                <?php 
								foreach ($visit_data as $key => $value) {
									//$formatedDate = substr_replace($key, '-', 4, 0); $formatedDate = substr_replace( $formatedDate, '-', 7, 0);
									$formatedDate = $key;
									$strtotime = strtotime($formatedDate);
									if($report_overview_by == "month"){
										$date =  date('M',$strtotime);
										$title =  date('M-Y',$strtotime);											
									}elseif($report_overview_by == "year"){
										$date =  $key;
										$title = $key;	
									}elseif($report_overview_by == "week"){
										
										
										$date =  $value['date'];
										$strtotime = strtotime($date);
										//$formatedDate = explode("-",$formatedDate);
										//$date =  $formatedDate[1];
										$title =  $date;
										
										$date =  date('Y-m-d',$strtotime);
										
									}else{
										$date =  substr(date('M',$strtotime),0,-2)." ".date('d',$strtotime);
										$title =  date('Y-m-d',$strtotime);	
									}
									
									echo "<th title=\"{$title}\">{$date}</th>";
								
								}?>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                        	<tr>
                            	<td>Orders</td>
                                <?php
									if($report_overview_by == "week"){
										foreach ($visit_data as $key => $value) {
											$_order_count	= 0;
											$_order_count = $value['order'];
											$order_count_total+=$_order_count;
											echo "<th>{$_order_count}</th>";
										}
									}else{
										foreach ($visit_data as $key => $value) {
											$_order_count	= 0;
												if (array_key_exists($key,$order_count)){
													$_order_count = $order_count[$key]; 
													$order_count_total+=$_order_count; 
												} 
												echo "<th>{$_order_count}</th>";
										}
									}
								?>
                                <td><?php  echo  $order_count_total; ?></td>
                            </tr>
                            <tr>
                            	<td>Site Visitor</td>
                                <?php
									$visit_count_total = 0;
									if($report_overview_by == "week"){
										foreach ($visit_data as $key => $value) {
											$_visit_count	= 0;
											$_visit_count = $value['visit'];
											$visit_count_total +=$_visit_count;
											echo "<th>{$_visit_count}</th>";
										}
									}else{
										foreach ($visit_data as $key => $value) {
											$_visit_count = $value; 
											$visit_count_total += $_visit_count;			
											echo "<th>{$_visit_count}</th>";
										}
									}
								?>                               
                                <td><?php echo $visit_count_total;?></td>
                            </tr>
                            
                        </tbody>
                       
                        </table>
                    <?php if($type != 'all_row') $this->total_count(); else $this->back_print_botton('bottom');//$this->print_array($total_value);?>
                </div>
                <?php
			else:
				//echo "No visit found or some wrong google analytics info"
			endif;
				
		}
		function get_order_count($start_date,$end_date)
		{
			$order_count  			= array();
			$report_overview_by 			= $this->get_request('report_overview_by','date',true);			
			$shop_order_status		= $this->get_set_status_ids();	
			$post_status 			= $this->constants['post_status'];
			
			$shop_order_status		= $this->get_set_status_ids();	
			$hide_order_status		= $this->constants['hide_order_status'];
			
			
			$publish_order			= "no";
			
					
			global $wpdb;
			if($report_overview_by == "date"){
				$sql = "
					SELECT 
					DATE(post_date) as order_date,
					COUNT(*) as order_count  
					FROM {$wpdb->prefix}posts as posts";
					if($this->constants['post_order_status_found'] == 0 ){
						if(count($shop_order_status)>0){
							$sql .= " 
							LEFT JOIN  {$wpdb->prefix}term_relationships 	as term_relationships 	ON term_relationships.object_id		=	posts.ID
							LEFT JOIN  {$wpdb->prefix}term_taxonomy 		as term_taxonomy 		ON term_taxonomy.term_taxonomy_id	=	term_relationships.term_taxonomy_id";
						}
					}
					$sql .= " 
					WHERE 
					post_type='shop_order'
					AND DATE(post_date) BETWEEN '".$start_date."' AND  '".$end_date."'";
					
					if($this->constants['post_order_status_found'] == 0 ){
						if(count($shop_order_status)>0){
							$in_shop_order_status = implode(",",$shop_order_status);
							$sql .= " AND  term_taxonomy.term_id IN ({$in_shop_order_status})";
						}
					}else{
						if(count($shop_order_status)>0){
							$in_shop_order_status		= implode("', '",$shop_order_status);
							$sql .= " AND  posts.post_status IN ('{$in_shop_order_status}')";
						}
					}
					
					if(count($hide_order_status)>0){
						$in_hide_order_status		= implode("', '",$hide_order_status);
						$sql .= " AND  posts.post_status NOT IN ('{$in_hide_order_status}')";
					}
					
					$sql .= " 
					GROUP BY DATE(post_date) 
					ORDER BY DATE(post_date)  ASC
					";
					//$this->print_sql($sql);
					$order_items = $wpdb->get_results($sql);
					foreach ($order_items as $count) {
						$order_count[str_replace("-", "", $count->order_date)]=$count->order_count;					
					}
			}
			
			if($report_overview_by == "month"){
				$sql = "
					
					SELECT 
					DATE(post_date) as order_date
					,COUNT(*) as order_count
					,MONTH(posts.post_date) 					as month_number
					,DATE_FORMAT(posts.post_date, '%Y-%m')		as month_key
					FROM {$wpdb->prefix}posts as posts ";
					if($this->constants['post_order_status_found'] == 0 ){
						if(count($shop_order_status)>0){
							$sql .= " 
							LEFT JOIN  {$wpdb->prefix}term_relationships 	as term_relationships 	ON term_relationships.object_id		=	posts.ID
							LEFT JOIN  {$wpdb->prefix}term_taxonomy 		as term_taxonomy 		ON term_taxonomy.term_taxonomy_id	=	term_relationships.term_taxonomy_id";
						}
					}
					
					$sql .= " 
					WHERE 
					post_type='shop_order'
					AND DATE(post_date) BETWEEN '".$start_date."' AND  '".$end_date."'";
					if($this->constants['post_order_status_found'] == 0 ){
						if(count($shop_order_status)>0){
							$in_shop_order_status = implode(",",$shop_order_status);
							$sql .= " AND  term_taxonomy.term_id IN ({$in_shop_order_status})";
						}
					}else{
						if(count($shop_order_status)>0){
							$in_shop_order_status		= implode("', '",$shop_order_status);
							$sql .= " AND  posts.post_status IN ('{$in_shop_order_status}')";
						}
					}
					
					if(count($hide_order_status)>0){
						$in_hide_order_status		= implode("', '",$hide_order_status);
						$sql .= " AND  posts.post_status NOT IN ('{$in_hide_order_status}')";
					}
					$sql .= " GROUP BY month_number ORDER BY post_date";
					$order_items = $wpdb->get_results($sql);
					foreach ($order_items as $count) {
						$order_count[$count->month_key]=$count->order_count;					
					}
					//$this->print_array($order_count);
			}
			
			if($report_overview_by == "year"){
				$sql = "
					
					SELECT 
					DATE(post_date) as order_date
					,COUNT(*) as order_count
					,Year(posts.post_date) 						as year_number
					,DATE_FORMAT(posts.post_date, '%Y')			as year_key
					FROM {$wpdb->prefix}posts as posts ";
					if($this->constants['post_order_status_found'] == 0 ){
						if(count($shop_order_status)>0){
							$sql .= " 
							LEFT JOIN  {$wpdb->prefix}term_relationships 	as term_relationships 	ON term_relationships.object_id		=	posts.ID
							LEFT JOIN  {$wpdb->prefix}term_taxonomy 		as term_taxonomy 		ON term_taxonomy.term_taxonomy_id	=	term_relationships.term_taxonomy_id";
						}
					}
					
					$sql .= " 
					WHERE 
					post_type='shop_order'
					AND DATE(post_date) BETWEEN '".$start_date."' AND  '".$end_date."'";
					if($this->constants['post_order_status_found'] == 0 ){
						if(count($shop_order_status)>0){
							$in_shop_order_status = implode(",",$shop_order_status);
							$sql .= " AND  term_taxonomy.term_id IN ({$in_shop_order_status})";
						}
					}else{
						if(count($shop_order_status)>0){
							$in_shop_order_status		= implode("', '",$shop_order_status);
							$sql .= " AND  posts.post_status IN ('{$in_shop_order_status}')";
						}
					}
					
					if(count($hide_order_status)>0){
						$in_hide_order_status		= implode("', '",$hide_order_status);
						$sql .= " AND  posts.post_status NOT IN ('{$in_hide_order_status}')";
					}
					$sql .= " GROUP BY year_number ORDER BY post_date";
					$order_items = $wpdb->get_results($sql);
					//$this->print_array($order_items);
					foreach ($order_items as $count) {
						$order_count[$count->year_key]=$count->order_count;					
					}
					//$this->print_array($order_count);
			}
			
			if($report_overview_by == "week"){
				$sql = "
				SELECT 
					DATE_FORMAT(posts.post_date, '%Y%m%d') as order_date,
					COUNT(*) as order_count
					,CONCAT(DATE_FORMAT(posts.post_date, '%Y'),'-',WEEK( DATE(posts.post_date))) 					as week_year
					FROM {$wpdb->prefix}posts as posts ";
					if($this->constants['post_order_status_found'] == 0 ){
						if(count($shop_order_status)>0){
							$sql .= " 
							LEFT JOIN  {$wpdb->prefix}term_relationships 	as term_relationships 	ON term_relationships.object_id		=	posts.ID
							LEFT JOIN  {$wpdb->prefix}term_taxonomy 		as term_taxonomy 		ON term_taxonomy.term_taxonomy_id	=	term_relationships.term_taxonomy_id";
						}
					}
					
					$sql .= " 
					WHERE 
					post_type='shop_order'
					AND DATE(post_date) BETWEEN '".$start_date."' AND  '".$end_date."'";
					if($this->constants['post_order_status_found'] == 0 ){
						if(count($shop_order_status)>0){
							$in_shop_order_status = implode(",",$shop_order_status);
							$sql .= " AND  term_taxonomy.term_id IN ({$in_shop_order_status})";
						}
					}else{
						if(count($shop_order_status)>0){
							$in_shop_order_status		= implode("', '",$shop_order_status);
							$sql .= " AND  posts.post_status IN ('{$in_shop_order_status}')";
						}
					}
					
					if(count($hide_order_status)>0){
						$in_hide_order_status		= implode("', '",$hide_order_status);
						$sql .= " AND  posts.post_status NOT IN ('{$in_hide_order_status}')";
					}
					$sql .= " 
					GROUP BY DATE(post_date) 
					ORDER BY DATE(post_date)  ASC
					";
					$order_items = $wpdb->get_results($sql);
					
					$dates = array();
					$vdates = array();
					foreach ($order_items as $count) {
						
						$date = $count->order_date;
						$strtotime = strtotime($date);
						$week_id =  date('Y-W',$strtotime);
						if(isset($vdates[$week_id])){
							$vdates[$week_id] = $vdates[$week_id] + $count->order_count;
							//$dates[$week_id] = $date;
						}else{
							$vdates[$week_id] = $count->order_count;	
							//$dates[$week_id] = $date;
						}
						
						//$order_count[str_replace("-", "", $count->order_date)]=$count->order_count;					
					}
					
					///$this->print_array($order_items);
					
					/*foreach ($vdates as $key => $value) {
						$visit_data[$dates[$key]] = $value;
					}*/
					
					$order_count = $vdates;
					//$this->print_array($order_count);
				/*$sql = "
					SELECT 
					DATE(post_date) as order_date
					,COUNT(*) as order_count
					,WEEK( DATE(posts.post_date)) 					as week_number
					,CONCAT(DATE_FORMAT(posts.post_date, '%Y'),'-',WEEK( DATE(posts.post_date))) 					as week_year
					
					FROM {$wpdb->prefix}posts as posts 
					WHERE 
					post_type='shop_order'
					AND DATE(post_date) BETWEEN '".$start_date."' AND  '".$end_date."'										
					GROUP BY week_number ORDER BY post_date";
					$order_items = $wpdb->get_results($sql);
					foreach ($order_items as $count) {
						$order_count[$count->week_year]=$count->order_count;					
					}*/
					
					//$this->print_array($order_items);
					//$this->print_array($order_count);
			}
			
			return $order_count;
		}
		function get_google_analytics_visits($start_date,$end_date, $order_count = NULL)
		{
			$visit_data  						= array();
			$this->constants['ga_api_notice'] 	= '';
			
			if(!function_exists('file_get_contents')){
				$error_txt = __("<strong>Google Analytics:-</strong> file_get_contents() function not exists.",'icwoocommerce_textdomains');
				
				$error_notice = '<div class="error fade"><p>' .$error_txt. " </p></div>\n";
								
				$this->constants['ga_api_notice'] = $error_notice;
				return false;
			}
			
			include_once("ic_commerce_premium_golden_gapi.class.php");
			
			$ga_account 			= $this->get_setting('ga_account',$this->constants['plugin_options'], false);
			$ga_password 			= $this->get_setting('ga_password',$this->constants['plugin_options'], false);
			$ga_profile_id 			= $this->get_setting('ga_profile_id',$this->constants['plugin_options'], false);
			$token					= NULL;
			$token_stored			= false;
			$token					= get_option($this->constants['plugin_key'].'_ga_token',false);
			$setting_url			= admin_url("admin.php?page=".$this->constants['plugin_key']."_options_page");
			$setting_link			= sprintf(__("<a href=\"%s#ga_profile_id\">Click here </a> for google analytics settings.",'icwoocommerce_textdomains'),$setting_url);
			$request_failed_msg		= "Request failed, fopen provides no further information";
			$request_failed_notice  = __("Request failed, fopen provides no further information, please try again later",'icwoocommerce_textdomains');
			$youtube_profile_id_ntc = __(" <strong>Profile ID/View ID: - </strong><a href=\"https://www.youtube.com/watch?v=Qf2VT_ZCSGI\" target=\"_blank\">Click here </a> how to find your Google Analytics <strong>Profile Id</strong>",'icwoocommerce_textdomains');
			$error_notice 			= "";
			
			if(!$token){// || !$ga_profile_id
				if(!$ga_account || !$ga_password){
					$error_txt = __("<strong>Google Analytics:-</strong> Please enter your google analytics usename and password.",'icwoocommerce_textdomains');
					
					$error_notice = '<div class="error fade"><p>' .$error_txt. " </p></div>\n";
						
					$error_notice .= '<div class="error fade"><p>'." {$setting_link}</p></div>\n";
					
					$this->constants['ga_api_notice'] = $error_notice;
					return false;
				}
			}else{
				/*if($ga_account || $ga_password){				
					$error_txt = "<strong>Google Analytics:-</strong> Please delete your username and password. {$setting_link}";
					$error_txt = '<div class="error fade"><p>' .$error_txt. "</p></div>\n";
					echo $error_txt;
				}*/
				
				$token_stored = true;
				//update_option('ga_token',$token);
			}
			
			define('ga_profile_id'  ,$ga_profile_id);			
			
			$report_overview_by			= $this->get_request('report_overview_by','date',true);
				
			$ga = new gapi($ga_account,$ga_password,$token);
			
			if(!$token_stored){
				$error = $ga->authenticate();
				
				$token = $ga->getAuthToken();
							
				if(!$token){
					if(strlen($error)>=0){
						$error_txt = str_replace("BadAuthentication",__("<strong>Bad Authentication of Google Analytics:-</strong>Username or Password is incorrect.",'icwoocommerce_textdomains'),$error);
						
						if($error_txt == $request_failed_msg){
							$error_notice = '<div class="error fade"><p>' .$request_failed_notice. " </p></div>\n";
						}else{							
							$error_notice = '<div class="error fade"><p>' .$error_txt. " </p></div>\n";
							$error_notice .= '<div class="error fade"><p>'." {$setting_link}</p></div>\n";
						}
						
						delete_option($this->constants['plugin_key'].'_ga_token');
						
						$this->constants['ga_api_notice'] = $error_notice;
					}
					return false;
				}else{
					update_option($this->constants['plugin_key'].'_ga_token',$token);
				}
			}
			
			if(!$ga_profile_id){				
				$error_txt = __("<strong>Google Analytics:-</strong> Please enter your google analytics profile ID.",'icwoocommerce_textdomains');
				
				//$error_notice = '<div class="error fade"><p>' .$error_txt. " </p></div>\n";
				
				//$error_notice .= '<div class="error fade"><p>'." {$setting_link}</p></div>\n";
				
				//$error_notice .= '<div class="error fade"><p>' . " <strong>Profile ID/View ID: - </strong><a href=\"https://www.youtube.com/watch?v=Qf2VT_ZCSGI\" target=\"_blank\">Click here </a> how to find your Google Analytics <strong>Profile Id</strong>. </p></div>\n";
				
				if($error_txt = $request_failed_msg){
					$error_notice == '<div class="error fade"><p>' .$request_failed_notice. " </p></div>\n";
				}else{							
					$error_notice = '<div class="error fade"><p>' .$error_txt. " </p></div>\n";
					$error_notice .= '<div class="error fade"><p>'." {$setting_link}</p></div>\n";
					$error_notice .= '<div class="error fade"><p>' . $youtube_profile_id_ntc ."</p></div>\n";
				}
				
				
				$this->constants['ga_api_notice'] = $error_notice;
				return false;
				
			}else if(!is_numeric($ga_profile_id)){
				$error_txt = __("<strong>Google Analytics:-</strong> Please enter valid google analytics profile ID.",'icwoocommerce_textdomains');
				
				//$error_notice = '<div class="error fade"><p>' .$error_txt. " </p></div>\n";
				
				//$error_notice .= '<div class="error fade"><p>'." {$setting_link}</p></div>\n";
				
				if($error_txt == $request_failed_msg){
					$error_notice = '<div class="error fade"><p>' .$request_failed_notice. " </p></div>\n";
				}else{							
					$error_notice = '<div class="error fade"><p>' .$error_txt. " </p></div>\n";
					$error_notice .= '<div class="error fade"><p>'." {$setting_link}</p></div>\n";
					$error_notice .= '<div class="error fade"><p>' . $youtube_profile_id_ntc ."</p></div>\n";
				}
				
				$this->constants['ga_api_notice'] = $error_notice;
				return false;
			}
			
			$dimensions = array('date','week','month','year');
			$metrics    = array('visits','visitors');
			$report = $ga->requestReportData(
				ga_profile_id,      
				$dimensions, 
				$metrics, 
				$sort_metric='date', 
				$filter=null, 
				$start_date, 
				$end_date, 
				$start_index=1, 
				$max_results=1000);
				
			if(!$report){
				$error = $ga->get_request_report_error();
				if(strlen($error)>=0){
					
					$error_txt = str_replace("GDatainsufficientPermissionsUser",__("<strong>Google Analytics Data Insufficient Permissions:-</strong> User ",'icwoocommerce_textdomains'),$error);
					
					if($error_txt == $request_failed_msg){
						$error_notice = '<div class="error fade"><p>' .$request_failed_notice. " </p></div>\n";
					}else{							
						$error_notice = '<div class="error fade"><p>' .$error_txt. " </p></div>\n";
						$error_notice .= '<div class="error fade"><p>'." {$setting_link}</p></div>\n";
						$error_notice .= '<div class="error fade"><p>' . $youtube_profile_id_ntc ."</p></div>\n";
					}
					
					//$error_notice = '<div class="error fade"><p>' .$error_txt. "  </p></div>\n";
					
					//$error_notice .= '<div class="error fade"><p>'." {$setting_link} Please check your profile id.</p></div>\n";
					
					//$error_notice .= '<div class="error fade"><p>' . " <strong>Profile ID/View ID: - </strong><a href=\"https://www.youtube.com/watch?v=Qf2VT_ZCSGI\" target=\"_blank\">Click here </a> how to find your Google Analytics <strong>Profile Id</strong>. </p></div>\n";
					
					$this->constants['ga_api_notice'] = $error_notice;
					return false;
					
				}
			}else{
				//$error_notice = '<div class="error fade"><p>'." {$setting_link} Change you account.</p></div>\n";
				//echo $error_notice;
			}
			
						   
			if($report_overview_by == "date"){
				foreach ($ga->getResults() as $result) {
					$visit_data[$result->getDate()] = $result->getVisits();
				}
			}
			
			if($report_overview_by == "week"){
				$dates = array();
				$vdates = array();
				foreach ($ga->getResults() as $result) {
					
					//$this->print_array($result->getWeek());
					//$week_id = $result->getWeek();
					$date = $result->getDate();
					$strtotime = strtotime($date);
					$week_id =  date('Y-W',$strtotime);
					if(isset($vdates[$week_id]['visit'])){
						$vdates[$week_id]['visit'] = $vdates[$week_id]['visit'] + $result->getVisits();
						$vdates[$week_id]['date'] = $date;
						$dates[$week_id] = $date;
					}else{
						$vdates[$week_id]['visit'] = $result->getVisits();
						
						if(isset($order_count[$week_id]))
							$vdates[$week_id]['order'] = $order_count[$week_id];
						else
							$vdates[$week_id]['order'] = 0;
						
						$vdates[$week_id]['date'] = $date;
						$vdates[$week_id]['week'] = $week_id;
						$dates[$week_id] = $date;
					}
				}
				
				/*foreach ($vdates as $key => $value) {
					$visit_data[$dates[$key]] = $value;
				}*/
				$visit_data = $vdates;
				//$this->print_array($visit_data);
			}
			//$this->print_array($ga->getResults());
			if($report_overview_by == "month"){
				$dates = array();
				$vdates = array();
				foreach ($ga->getResults() as $result) {
					$date = $result->getDate(); 
					$strtotime = strtotime($date);
					$month_id =  date('Y-m',$strtotime);
					if(isset($vdates[$month_id])){
						$vdates[$month_id] = $vdates[$month_id] + $result->getVisits();						
					}else{
						$vdates[$month_id] = $result->getVisits();
					}
				}
				$visit_data = $vdates;
				//$this->print_array($visit_data);
			}
			
			if($report_overview_by == "year"){
				$dates = array();
				$vdates = array();
				foreach ($ga->getResults() as $result) {
					$date = $result->getDate(); 
					$strtotime = strtotime($date);
					$month_id =  date('Y',$strtotime);
					if(isset($vdates[$month_id])){
						$vdates[$month_id] = $vdates[$month_id] + $result->getVisits();						
					}else{
						$vdates[$month_id] = $result->getVisits();
					}
				}
				$visit_data = $vdates;
				//$this->print_array($visit_data);
			}
			
			return $visit_data;	   
		}
		function get_all_request(){
			global $request, $back_day;
			if(!$this->request){
				$request 			= array();
				$start				= 0;
				
				$limit 				= $this->get_request('limit',3,true);
				$p 					= $this->get_request('p',1,true);
				$page				= $this->get_request('page',NULL);
				
				$start_date 		= date('Y-m-d', strtotime('-30 day', strtotime(date_i18n("Y-m-d"))));
				$end_date 			= date_i18n('Y-m-d');
				
				$start_date			= $this->get_request('start_date',$start_date,true);
				$end_date			= $this->get_request('end_date',$end_date,true);
				
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
		
		function _get_setting($id, $data, $defalut = NULL){
			if(isset($data[$id]))
				return $data[$id];
			else
				return $defalut;
		}
		
		function total_count(){
			global $wpdb;
			
			//$request 			= $this->get_all_request();extract($request);
			//$total_pages		= $this->get_items('total_row');
			//$targetpage 		= "admin.php?page=".$admin_page;
			//$create_pagination 	= $this->get_pagination($total_pages,$limit,$adjacents,$targetpage,$request);
			
			?>
				
				<table style="width:100%">
					<tr>
						
						<td>					
							<?php //echo $create_pagination;?>
                        	<div class="clearfix"></div>
                            <div>
                        	<?php
								$this->export_to_csv_button('bottom');
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
	  
	  function export_to_csv_button($position = 'bottom'){
			global $request;
			//$admin_page 		= 	$this->get_request('page');
			//$admin_page 		= 	$this->get_request('admin_page');
			
			$admin_page			= $this->get_request('page',NULL);
			$admin_page			= $this->get_request('admin_page',$admin_page,true);
			
			//$admin_page_url 		= get_option('siteurl').'/wp-admin/admin.php';//Commented not work SSL admin site 20150212
			$admin_page_url 		= $this->constants['admin_page_url'];//Added SSL fix 20150212
			$mngpg 				= 	$admin_page_url.'?page='.$admin_page;
			$request			=	$request = $this->get_all_request();
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
                                        
                    <input type="submit" name="<?php echo $admin_page ;?>_export_csv" class="onformprocess csvicon" value="<?php _e("Export to CSV",'icwoocommerce_textdomains');?>" data-format="csv" data-popupid="export_csv_popup" data-hiddenbox="popup_csv_hidden_fields" data-popupbutton="<?php _e("Export to CSV",'icwoocommerce_textdomains');?>" data-title="<?php _e("Export to CSV - Additional Information",'icwoocommerce_textdomains');?>" />
                    <input type="submit" name="<?php echo $admin_page ;?>_export_xls" class="onformprocess excelicon" value="<?php _e("Export to Excel",'icwoocommerce_textdomains');?>" data-format="xls" data-popupid="export_csv_popup" data-hiddenbox="popup_csv_hidden_fields" data-popupbutton="<?php _e("Export to Excel",'icwoocommerce_textdomains');?>" data-title="<?php _e("Export to Excel - Additional Information",'icwoocommerce_textdomains');?>" />
                    <input type="button" name="<?php echo $admin_page ;?>_export_pdf" class="onformprocess open_popup pdficon" value="<?php _e("Export to PDF",'icwoocommerce_textdomains');?>" data-format="pdf" data-popupid="export_pdf_popup" data-hiddenbox="popup_pdf_hidden_fields" data-popupbutton="<?php _e("Export to PDF",'icwoocommerce_textdomains');?>" data-title="<?php _e("Export to PDF",'icwoocommerce_textdomains');?>" />
                    <input type="button" name="<?php echo $admin_page ;?>_export_print" class="onformprocess open_popup printicon" value="<?php _e("Print",'icwoocommerce_textdomains');?>"  data-format="print" data-popupid="export_print_popup" data-hiddenbox="popup_print_hidden_fields" data-popupbutton="<?php _e("Print",'icwoocommerce_textdomains');?>" data-title="<?php _e("Print",'icwoocommerce_textdomains');?>" data-form="form" />
                    
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
                    <input type="button" name="backtoprevious" value="<?php _e("Back to Previous",'icwoocommerce_textdomains');?>"  class="backtoprevious onformprocess" onClick="back_to_previous();" />
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
            		<input type="button" name="backtoprevious" value="<?php _e("Back to Previous",'icwoocommerce_textdomains');?>"  class="onformprocess" onClick="back_to_detail();" />
                    <input type="button" name="backtoprevious" value="<?php _e("Print",'icwoocommerce_textdomains');?>"  class="onformprocess" onClick="print_report();" />
                </div>
            <?php
		} 
		
		
		function ic_commerce_custom_report_page_export_csv($export_file_format){
			$type				= 'all_row';			
			$columns 			= array();
			$export_rows 		= array();
			$i 					= 0;			
			$start_date			= $this->get_request('start_date','');
			$end_date			= $this->get_request('end_date','');
			$report_overview_by		= $this->get_request('report_overview_by','date',true);
			$order_count  		= $this->get_order_count($start_date,$end_date);
			$visit_data   		= $this->get_google_analytics_visits($start_date, $end_date, $order_count);
			
			if(empty($this->constants['ga_api_notice'])){
				$order_count_total = 0;
				$visit_count_total = 0;
				
				$columns['item_name'] = __("Orders/Site Visitor",'icwoocommerce_textdomains');
				foreach ($visit_data as $key => $value) {
					$formatedDate = $key;
					if($report_overview_by == "month"){
						$strtotime = strtotime($formatedDate);
						$date =  date('M Y',$strtotime);
					}elseif($report_overview_by == "year"){
						$date =  $key;
						$title = $key;	
					}elseif($report_overview_by == "week"){					
						$date =  $value['date'];
						$strtotime = strtotime($date);
						$date =  date('Y-m-d',$strtotime);					
					}else{
						$strtotime = strtotime($formatedDate);
						$date =  date('Y-m-d',$strtotime);
					}
					$columns[$key] = $date;
				
				}
				$columns['total'] = "Total";			
				
				$export_rows[$i]['item_name'] = __("Orders",'icwoocommerce_textdomains');
				if($report_overview_by == "week"){
					foreach ($visit_data as $key => $value) {
						$_order_count	= 0;
						$_order_count = $value['order'];
						$order_count_total+=$_order_count;
						$export_rows[$i][$key] = $_order_count;
					}
				}else{
					foreach ($visit_data as $key => $value) {
						$_order_count	= 0;
							if (array_key_exists($key,$order_count)){
								$_order_count = $order_count[$key]; 
								$order_count_total+=$_order_count; 
							} 
							$export_rows[$i][$key] = $_order_count;
					}
				}
				$export_rows[$i]['total'] = $order_count_total;
				$i = $i + 1;
				
				$export_rows[$i]['item_name'] = __("Site Visitor",'icwoocommerce_textdomains');
				if($report_overview_by == "week"){
					foreach ($visit_data as $key => $value) {
						$_visit_count = $value['visit'];
						$visit_count_total +=$_visit_count;
						$export_rows[$i][$key] = $_visit_count;
					}
				}else{
					foreach ($visit_data as $key => $value) {					
						$_visit_count = $value; 
						$visit_count_total += $_visit_count;					
						$export_rows[$i][$key] = $_visit_count;
					}
				}
				$export_rows[$i]['total'] = $visit_count_total;
				
				/*$out 					= $this->ExportToCsv($FileName,$export_rows,$columns,$export_file_format);*/
				/*New Added 20160130*/
				$today_date 		= date_i18n("Y-m-d-H-i-s");				
				$export_filename 	= $export_file_name."-".$report_name."-".$today_date.".".$export_file_format;
				$export_filename 	= apply_filters('ic_commerce_export_csv_excel_format_file_name',$export_filename,$report_name,$today_date,$export_file_name,$export_file_format);
				do_action("ic_commerce_export_csv_excel_format",$export_filename,$export_rows,$columns,$export_file_format,$report_name);
				$out = $this->ExportToCsv($export_filename,$export_rows,$columns,$export_file_format,$report_name);
				
			}else{
				$out 					= $this->constants['ga_api_notice'];
			}
			
			$export_file_name 		= $this->get_request('export_file_name',"no");
			$report_name 			= $this->get_request('report_name','');			
			$page_title 			= $this->get_request('page_title','');
			$from_date 				= $this->get_request('start_date','');
			$to_date 				= $this->get_request('end_date','');
			
			$from_date 				= date("F Y", strtotime($from_date));
			$to_date 				= date("F Y", strtotime($to_date));			
			$report_title 			= $page_title . " From " .  $from_date . " To " . $to_date;
			$today 					= date_i18n("Y-m-d-H-i-s");				
			$FileName 				= $export_file_name."-".$today.".".$export_file_format;	
			
			
			$format		= $export_file_format;
			$filename	= $FileName;
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
			
			echo $out;
			exit;
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
			$type				= 'all_row';
			$columns 			= array();
			$export_rows 		= array();
			$i 					= 0;			
			$start_date			= $this->get_request('start_date','');
			$end_date			= $this->get_request('end_date','');
			$report_overview_by		= $this->get_request('report_overview_by','date',true);
			$order_count  		= $this->get_order_count($start_date,$end_date);
			$visit_data   		= $this->get_google_analytics_visits($start_date, $end_date, $order_count);
			
			
			if(empty($this->constants['ga_api_notice'])){
				$order_count_total = 0;
				$visit_count_total = 0;
				
				$columns['item_name'] = __("Orders/Site Visitor",'icwoocommerce_textdomains');
				foreach ($visit_data as $key => $value) {
					$formatedDate = $key;
					if($report_overview_by == "month"){
						$strtotime = strtotime($formatedDate);
						$date =  date('M Y',$strtotime);
					}elseif($report_overview_by == "year"){
						$date =  $key;
						$title = $key;	
					}elseif($report_overview_by == "week"){
						$date =  $value['date'];
						$strtotime = strtotime($date);
						$date =  date('Y-m-d',$strtotime);					
					}else{
						$strtotime = strtotime($formatedDate);
						$date =  substr(date('M',$strtotime),0,-2)." ".date('d',$strtotime);
					}
					$columns[$key] = $date;
				
				}
				$columns['total'] = __("Total",'icwoocommerce_textdomains');
				
				$export_rows[$i]['item_name'] = __("Orders",'icwoocommerce_textdomains');
				if($report_overview_by == "week"){
					foreach ($visit_data as $key => $value) {
						$_order_count	= 0;
						$_order_count = $value['order'];
						$order_count_total+=$_order_count;
						$export_rows[$i][$key] = $_order_count;
					}
				}else{
					foreach ($visit_data as $key => $value) {
						$_order_count	= 0;
							if (array_key_exists($key,$order_count)){
								$_order_count = $order_count[$key]; 
								$order_count_total+=$_order_count; 
							} 
							$export_rows[$i][$key] = $_order_count;
					}
				}
				$export_rows[$i]['total'] = $order_count_total;
				$i = $i + 1;
				
				$export_rows[$i]['item_name'] = __("Site Visitor",'icwoocommerce_textdomains');
				if($report_overview_by == "week"){
					foreach ($visit_data as $key => $value) {
						$_visit_count = $value['visit'];
						$visit_count_total +=$_visit_count;
						$export_rows[$i][$key] = $_visit_count;
					}
				}else{
					foreach ($visit_data as $key => $value) {					
						$_visit_count = $value; 
						$visit_count_total += $_visit_count;					
						$export_rows[$i][$key] = $_visit_count;
					}
				}
				$export_rows[$i]['total'] = $visit_count_total;
				$output = $this->GetDataGrid($export_rows,$columns);
			}else{
				$export_rows[$i]['total'] = "";
				$output = $this->constants['ga_api_notice'];
			}
			
			if(count($export_rows)>0){
				$this->export_to_pdf($export_rows,$output);
			}
		}
		
		
		
		function GetDataGrid($rows=array(),$columns=array(),$summary=array()){
			global $wpdb;
			$csv_terminated = "\n";
			$csv_separator = ",";
			$csv_enclosed = '"';
			$csv_escaped = "\\";
			$fields_cnt = count($columns); 
			$schema_insert = '';
			
			$th_open = '<th>';
			$th_close = '</th>';
			
			$td_open = '<td class="#class#">';
			$td_close = '</td>';
			
			$tr_open = '<tr>';
			$tr_close = '</tr>';
			
			//$this->print_array($rows);
			
			
			
			foreach($columns as $key => $value):
				$l = $th_open . str_replace($csv_enclosed, $csv_escaped . $csv_enclosed, $value) . $th_close;
				$schema_insert .= $l;				
			endforeach;// end for
			
			//New Change ID 20140918
			$company_name	= $this->get_request('company_name','');
			$report_title	= $this->get_request('report_title','');
			$display_logo	= $this->get_request('display_logo','');
			$display_date	= $this->get_request('display_date','');
			$display_center	= $this->get_request('display_center','');
			$date_format	= $this->get_request('date_format','');
			
			$keywords		= $this->get_request('pdf_keywords','');
			$description	= $this->get_request('pdf_description','');
			
			//New Change ID 20140918
			$out ='<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd"><html><head>
					<title>'.$report_title.'</title>
						<meta name="description" content="'.$description.'" />
						<meta name="keywords" content="'.$keywords.'" />
						<meta name="author" content="'.$company_name.'" /><style type="text/css"><!-- 
					.header {position: fixed; top: -40px; text-align:center;}
						  .footer { position: fixed; bottom: 0px; text-align:center;}
						  .pagenum:before { content: counter(page); }
					/*.Container{width:750px; margin:0 auto; border:1px solid black;}*/
					body{font-family: "Source Sans Pro", sans-serif; font-size:10px;}
					span{font-weight:bold;}
					.Clear{clear:both; margin-bottom:10px;}
					label{width:100px; float:left; }
					.sTable3{border:1px solid #DFDFDF;}
					.sTable3 th{
						padding:10px 10px 7px 10px;
						background:#eee url(../images/thead.png) repeat-x top left;
						/*border-bottom:1px solid #DFDFDF;*/
						text-align:left;
						}
					.Form{padding:1% 1% 11% 1%; margin:5px 5px 5px 5px;}
					.myclass{border:1px solid black;}
						
					.sTable3 tbody tr td{padding:8px 10px; background:#fff; border-top:1px solid #DFDFDF; border-right:1px solid #DFDFDF;}
					.sTable3 tbody tr.AltRow td{background:#FBFBFB;}
					.print_header_logo.center_header, .header.center_header{margin:auto;  text-align:center;}
					
					.td_pdf_amount span{ text-align:right; display:block}
					.sTable3 th, .sTable3 td{text-align:right;}
					-->
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
			
			if(strlen($report_title) > 0)	$out .= "<div class='Clear'><label>".__( 'Report Title:', 'icwoocommerce_textdomains' )." </label><label>".stripslashes($report_title)."</label></div>";
			
			$out .= "<div class='Clear'></div>";
			
			if($display_date) $out .= "<div class='Clear'><label>".__( 'Date:', 'icwoocommerce_textdomains' )." </label><label>".date_i18n($date_format)."</label></div>";
			
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
								//$schema_insert .= $td_open . str_replace($csv_enclosed, $csv_escaped . $csv_enclosed, $rows[$i][$key]) . $td_close;
								$schema_insert .= str_replace("#class#",$key,$td_open).str_replace($csv_enclosed, $csv_escaped . $csv_enclosed, $rows[$i][$key]) . $td_close;
								
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
			$out .= "</div></div>";
			
			$out .= "</div></body>";			
			$out .="</html>";
			return $out;
		 
		}
		
		function print_header($type = NULL, $report_title = NULL){
			$out = "";
			
			if($type == 'all_row'){
				
				$company_name	= $this->get_request('company_name','');
				$report_title	= $this->get_request('report_title','');
				$display_logo	= $this->get_request('display_logo','');
				$display_date	= $this->get_request('display_date','');
				$display_center	= $this->get_request('display_center','');
				
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
				if(strlen($display_date) > 0)	$out .= "<div class='Clear'><label>Report Date: </label> <label>".date('Y-m-d')."</label></div>";
				$out .= "</div>";
			}else{
				//if($report_title) echo "<h2>".$report_title."</h2>";
			}
			
			echo $out;		
		}
		
	}
}
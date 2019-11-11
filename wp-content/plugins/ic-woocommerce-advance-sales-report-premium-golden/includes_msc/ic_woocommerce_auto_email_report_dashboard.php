<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly

include_once('ic_woocommerce_auto_email_report_functions.php');

if(!class_exists('IC_WW_Auto_Email_Dashboard')){
	/*
	 * Class Name IC_WW_Auto_Email_Dashboard
	 *
	 * Class is used for initialize plugin
	 *	 
	*/
	class IC_WW_Auto_Email_Dashboard extends Ic_Wc_Auto_Email_Report_Functions {
		
		/* variable declaration constants*/
		public $constants 				= array();	
		
		/**
			*Constructor
		*/
		public function __construct($constants = array()) {
			
			$this->constants  = $constants;			
		}
		
		function get_reports(){
			require_once('ic_woocommerce_auto_email_reports.php');
			$reports = new Ic_Wc_Auto_Email_Reports($this->constants);			
			return $reports;
		}
		
		function get_sales_month(){
			global $wpdb;
			
			/*
			$months = array();
			$first_date 	  = date_i18n('Y-m-01');
			$first_date_time = strtotime($first_date);
			for($i=0;$i<=11;$i++){
				$c = strtotime("-{$i} month",$first_date_time);
				$value = date("Y-m",$c);
				$label = date_i18n("F Y",$c);				
				$months[$value] = $label;
			}*/
			
			$sql = "SELECT date_format(shop_order.post_date , '%Y-%m') AS month_key";
			$sql .= " FROM $wpdb->posts AS shop_order ";
			$sql .= " WHERE shop_order.post_type IN ('shop_order','shop_order_refund') ";
			$sql .= " GROUP BY month_key";
			$sql .= " ORDER BY month_key DESC";
			$sql .= " LIMIT 12";
			
			$items = $wpdb->get_results($sql);
			$lists = array();
			foreach($items as $key => $item){
				$month_key = isset($item->month_key) ? $item->month_key : '';
				$lists[$month_key] = date_i18n("F Y",strtotime($month_key));
			}			
			return $lists;
		}
		
		function init(){
			
			$reports 		 	= $this->get_reports();
			$product_month   	  = isset($_REQUEST['product_month']) ? $_REQUEST['product_month'] : date_i18n("Y-m");
			$product_month_time = strtotime($product_month);
			$start_date 	  	 = date_i18n("Y-m-01",$product_month_time);
			$end_date 	       = date_i18n("Y-m-t",$product_month_time);
			$today_time 	     = strtotime($start_date);			
			$todays_total 	   = $reports->get_total_sales($start_date,$end_date);
			$todays_refund      = $reports->get_total_refund($start_date,$end_date);
			$todays_discount    = $reports->get_total_discount($start_date,$end_date);
			$todays_profit      = $reports->get_total_profit($start_date,$end_date);
			$todays_shipping    = $reports->get_total_order_shipping($start_date,$end_date);			
			$months 		     = $this->get_sales_month();			
			$month_reports 	  = date_i18n('F, Y',$today_time);
			$top 			    = 20;
			
			echo "<div class=\"wrap\">";
			echo "<h1 class=\"wp-heading-inline\">". __('Monthly Sales Comparison','icwoocommerce_textdomains')."</h1>";
			
			if(isset($_POST['notice_monthly_sales_comparison'])){
				$notice = $_POST['notice_monthly_sales_comparison'];
				$class = 'notice notice-'.$notice.' is-dismissible';
				if($notice == 'sucess'){
					$message = __( 'Email Sent sucessfully.', 'icwoocommerce_textdomains' );
				}else{
					$message = __( 'Getting problem to sending email.', 'icwoocommerce_textdomains' );
				}				
				printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) ); 
			}
			?>
            <script type="text/javascript">
            	jQuery(document).ready(function(e) {
                    jQuery("#product_month").change(function(){
						window.location = window.location + "&product_month="+jQuery(this).val();
					});
                });
            </script>
            <div class="row icpostbox">
            	<div class="col-md-6">
                	<h3><strong><?php printf(__('Summaries for the Month of %s','icwoocommerce_textdomains'),$month_reports);?></strong></h3>
                </div>
                
                <div class="col-md-6">
                	<div class="pull-right">
                    	<select id="product_month" name="product_month">
							<?php foreach($months as $key => $month):?>
                            <option value="<?php echo $key;?>"<?php if($product_month == $key){?> selected="selected"<?php }?>><?php echo $month;?></option>
                            <?php endforeach;?>
                        </select>
                    </div>                	
                </div>
            </div>
            
            <div class="row ic_summary_box">
                <div class="col-xs-3">
                    <div class="ic_block ic_block-orange">
                        <div class="ic_block-content">
                            <h2><?php _e("Total Sales","icwoocommerce_textdomains"); ?></h2>
                            <div class="ic_stat_content">
                                <div class="ic_block-img"><img src="<?php echo plugins_url( 'assets/images/sales-icon.png', dirname(__FILE__) );  ?>" alt=""></div>
                                <p class="ic_stat">
                                    <span class="woocommerce-Price-amount amount"><?php echo $this->get_woo_price( $todays_total);  ?></span>
                                    <span class="ic_count"></span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-xs-3">
                    <div class="ic_block ic_block-orange">
                        <div class="ic_block-content">
                            <h2><?php _e("Total Refund","icwoocommerce_textdomains"); ?></h2>
                            <div class="ic_stat_content">
                                <div class="ic_block-img"><img src="<?php echo plugins_url( 'assets/images/refund-icon.png', dirname(__FILE__) );  ?>" alt=""></div>
                                <p class="ic_stat">
                                    <span class="woocommerce-Price-amount amount"><?php echo $this->get_woo_price( $todays_refund);  ?></span>
                                    <span class="ic_count"></span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-xs-3">
                    <div class="ic_block ic_block-green">
                        <div class="ic_block-content">
                            <h2><?php _e("Total Discount","icwoocommerce_textdomains"); ?></h2>
                            <div class="ic_stat_content">
                                <div class="ic_block-img"><img src="<?php echo plugins_url( 'assets/images/disc-icon.png', dirname(__FILE__) );  ?>" alt=""></div>
                                <p class="ic_stat">
                                    <span class="woocommerce-Price-amount amount"><?php echo $this->get_woo_price( $todays_discount);  ?> </span>
                                    <span class="ic_count"></span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-xs-3" style="display:none">
                    <div class="ic_block ic_block-grey">
                        <div class="ic_block-content">
                            <h2><?php _e("Total Profit","icwoocommerce_textdomains"); ?></h2>
                            <div class="ic_stat_content">
                                <div class="ic_block-img"><img src="<?php echo plugins_url( 'assets/images/customer-icon.png', dirname(__FILE__) );  ?>" alt=""></div>
                                <p class="ic_stat">
                                     <span class="woocommerce-Price-amount amount"><?php echo $this->get_woo_price( $todays_profit);  ?> </span>
                                    <span class="ic_count"></span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-xs-3">
                    <div class="ic_block ic_block-grey">
                        <div class="ic_block-content">
                            <h2><?php _e("Total Shipping","icwoocommerce_textdomains"); ?></h2>
                            <div class="ic_stat_content">
                                <div class="ic_block-img"><img src="<?php echo plugins_url( 'assets/images/shipp-icon.png', dirname(__FILE__) );  ?>" alt=""></div>
                                <p class="ic_stat">
                                     <span class="woocommerce-Price-amount amount"><?php echo $this->get_woo_price( $todays_shipping); ?> </span>
                                    <span class="ic_count"></span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>                
            </div>
        	<style type="text/css">
            	th.align_center, td.align_center{ text-align:center;}
				th.align_right, td.align_right{ text-align:right;}
				
				th.left_border{ border-left:1px solid #CCC;}
				td.left_border{ border-left:1px solid #CCC;}
				th.line_total, th.order_total{ width:150px;} 
				span.fa.fa-long-arrow-up, span.fa.fa-arrows-v{ color:#090}
				span.fa.fa-long-arrow-down{ color:#F00}
            </style>
            <div class="row">            
            	  <?php $top = $this->get_settings('last_month_sales',12);?>
                  <div class="col-md-12">
                    <div class="icpostbox">
                        <h3><?php printf(__('Last %s Months Sales', 'icwoocommerce_textdomains'),$top);?></h3>
                        <div class="table-responsive">
							<?php echo $reports->get_last_twelve_months_sales($today_time,$start_date,$end_date,$top,'grid');?>
                        </div>
                    </div>
                </div>
                
                <?php $top = $this->get_settings('top_products_summary');?>
                <div class="col-md-12">
                    <div class="icpostbox">
                        <h3><?php printf(__('Products(%s) Sales Summary for the month of %s','icwoocommerce_textdomains'),$top,$month_reports);?></h3>
                        <div class="table-responsive">
							<?php echo $reports->get_product_monthly_sales_analysis($today_time,$start_date,$end_date,$top);?>
                        </div>
                    </div>
                </div>
                               
                <?php $top = $this->get_settings('top_new_products');?>
                <div class="col-md-12">
                    <div class="icpostbox">
                        <h3><?php printf(__('New Products(%s) sold in %s','icwoocommerce_textdomains'),$top, $month_reports);?></h3>
                        <div class="table-responsive">
                        	<?php echo $reports->get_sold_products_html($today_time,$start_date,$end_date,$top);?>                            
                        </div>
                    </div>
                </div>
                
                <?php $top = $this->get_settings('top_not_sold_products');?>
                <div class="col-md-12">
                    <div class="icpostbox">
                        <h3><?php printf(__('Products(%s) not sold in %s','icwoocommerce_textdomains'),$top,$month_reports);?></h3>
                        <div class="table-responsive">
                        	<?php echo $reports->get_sold_products_html($today_time,$start_date,$end_date,$top, 'not_sold');?>                            
                        </div>
                    </div>
                </div>
                
                <?php $top = $this->get_settings('top_consistent_products');?>
                <div class="col-md-12">
                    <div class="icpostbox">
                        <h3><?php printf(__('Products(%s) sold consistently in %s','icwoocommerce_textdomains'),$top,$month_reports);?></h3>
                        <div class="table-responsive">
                        	<?php echo  $reports->get_sold_products_html($today_time,$start_date,$end_date,$top, 'repeated');?>                            
                        </div>
                    </div>
                </div>
                
                <?php $top = $this->get_settings('top_customers_summary');?>
                <div class="col-md-12">
                    <div class="icpostbox">
                        <h3><?php printf(__('Customers(%s) summary for the month of %s','icwoocommerce_textdomains'),$top,$month_reports);?></h3>
                        <div class="table-responsive">
							<?php echo $reports->get_customer_monthly_sales_analysis($today_time,$start_date,$end_date,$top);?>		
                        </div>
                    </div>
                </div>
                
                <?php $top = $this->get_settings('top_new_customers');?>
                <div class="col-md-12">
                    <div class="icpostbox">
                    	<h3><?php printf(__('New(%s) customers in %s','icwoocommerce_textdomains'),$top, $month_reports);?></h3>
                        <div class="table-responsive">
                        	<?php echo $reports->get_customer_html($today_time,$start_date,$end_date,$top, 'new');?>                            
                        </div>
                    </div>
                </div>
                
                <?php $top = $this->get_settings('top_not_sold_customers');?>
                <div class="col-md-12">
                    <div class="icpostbox">
                        <h3><?php printf(__('Customers(%s) not purchase products in %s','icwoocommerce_textdomains'),$top,$month_reports);?></h3>
                        <div class="table-responsive">
                        	<?php echo $reports->get_customer_html($today_time,$start_date,$end_date,$top, 'not_sold');?>                            
                        </div>
                    </div>
                </div>
                 <?php $top = $this->get_settings('top_consistent_customers');?>
                 <div class="col-md-12">
                    <div class="icpostbox">
                        <h3><?php printf(__('Customers(%s) purchase consistent in %s','icwoocommerce_textdomains'),$top, $month_reports);?></h3>
                        <div class="table-responsive">
                        	<?php echo $reports->get_customer_html($today_time,$start_date,$end_date,$top, 'repeated');?>                            
                        </div>
                    </div>
                </div>
                
                <!--End-->
            </div>
            
            <div class="row icpostbox">            
            	<div class="col-md-12">
                	<div class="pull-right">
                    	<?php
							$plugin_key = isset($this->constants['parent_plugin_key']) ? $this->constants['parent_plugin_key'] : '';
                        	$admin_url = admin_url('admin.php');							
							$setting_url = $admin_url."?page={$plugin_key}_options_page#msc_last_month_sales";
							$post_type = $this->constants['post_type'];
						?>
                        <a href="<?php echo admin_url('edit.php').'?post_type='.$post_type;?>" target="_blank"><?php _e('Schedules List','icwoocommerce_textdomains')?></a>
                        | <a href="<?php echo $setting_url;?>" target="_blank"><?php _e('Settings','icwoocommerce_textdomains')?></a>
                        | <a href="<?php echo $admin_url."?page=wc_auto_email_dashboard";?>" onclick="send_monthly_sales_comparison()" id="btn_send_monthly_sales_comparison"><?php _e('E-Mail','icwoocommerce_textdomains')?></a>
                        <form method="post" id="form_send_monthly_sales_comparison" action="<?php echo $admin_url."?page=wc_auto_email_dashboard"?>">
                        	<input type="hidden" name="product_month" value="<?php echo $product_month;?>" />
                            <input type="hidden" name="send_monthly_sales_comparison" value="<?php echo $product_month;?>" />
                        </form>
                        <script type="text/javascript">
							jQuery(document).ready(function(e) {
                                jQuery("#btn_send_monthly_sales_comparison").click(function(){
									jQuery('#form_send_monthly_sales_comparison').trigger('submit');
									return false;
								});
                            });
                        </script>
                    </div>                	
                </div>
            </div>
			<?php
			echo "</div>";
			
		}
		
		function get_settings($setting_key = '', $default = 20){
			if(!isset($this->constants['settings'])){
				$plugin_key = isset($this->constants['parent_plugin_key']) ? $this->constants['parent_plugin_key'] : '';
				$this->constants['settings'] = get_option($plugin_key, array());
			}			
			$setting_key = 'msc_'.$setting_key;			
			$settings = $this->constants['settings'];			
			return isset($settings[$setting_key]) ? $settings[$setting_key] : $default;
		}	
		
		function get_star_icon($number= 0){
			$star = "";
			for($i=0; $i<$number; $i++){
				$star .= '<i class="fa fa-star" style="color:#fd7e14;"></i>';
			}
			return 	$star;
			
		}
	}/*End Class*/
}

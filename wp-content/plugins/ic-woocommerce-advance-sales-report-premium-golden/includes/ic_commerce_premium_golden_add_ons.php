<?php 

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
	require_once('ic_commerce_premium_golden_fuctions.php');

if ( ! class_exists( 'IC_Commerce_Premium_Golden_Add_Ons' ) ) {
	class IC_Commerce_Premium_Golden_Add_Ons extends IC_Commerce_Premium_Golden_Fuctions{
		private $token;
		
		private $api;
		
		public $constants 	=	array();
		
		public $plugin_key  =   "icwoocommercepremiumgold";
	
		public function __construct($constants) {
			global $ic_plugin_activated;
			
			$this->constants 	= $constants;
					
			$this->token 		= $this->constants['plugin_key'];
			
		}
		
		
		public function init() {
			
			if ( !current_user_can( $this->constants['plugin_role'])) {
				wp_die( __( 'You do not have sufficient permissions to access this page.','icwoocommerce_textdomains' ) );
			}
			
			$this->display_content();
			
		}
		
		function display_content(){
?>
		<!--<script type="text/javascript" src="http://sam152.github.io/Javascript-Equal-Height-Responsive-Rows/grids.js"></script>
		<script type="text/javascript">
			jQuery(function($) {
				$('.col-md-4').responsiveEqualHeightGrid();
			});
		</script>-->
			<!--<div class="ic_plugins_container">
				<div class="row">
					<div class="col-md-4">
						<div class="ic_other_plugins">
							<div class="ic_plugin_img">
								<img src="<?php echo $this->constants['plugin_url']?>/assets/images/icons/golden_report.jpg" alt="WooCommerce Advance Sales Report (Gold Version)" />
							</div>
							<div class="ic_plugin_content">
								<h2><a href="http://plugins.infosofttech.com/products/woocommerce-advance-sales-report/">WooCommerce Report (Gold Version)</a></h2>
                                <span class="amount">$49</span>
								<ul class="UlList">
									<li>Summary detail on <strong>dashboard</strong></li>
									<li>Order item detail and normal detail report</li>
									<li><strong>Product</strong>, customer, recent order, coupon,<strong>refund</strong> detail report and many more.</li>
									<li>7 different <strong>crosstab report</strong></li>
									<li><strong>Product variation</strong> wise report</li>
									<li>Simple and variation stock List</li>
									<li>Export to <strong>csv</strong>, <strong>excel</strong>, <strong>pdf</strong>, <strong>print</strong>, <strong>invoice</strong></li>
									<li>Auto Email Reporting</li>
									<li><strong>Total 25+ reporting</strong></li>
								</ul>
                                <div class="ic_readmore"><a href="http://plugins.infosofttech.com/products/woocommerce-advance-sales-report/">Read More</a></div>
							</div>
						</div>
					</div>
					
					<div class="col-md-4">
						<div class="ic_other_plugins">
							<div class="ic_plugin_img">
								<img src="<?php echo $this->constants['plugin_url']?>/assets/images/icons/premium-gold-report.jpg" alt="WooCommerce Advance Sales Report (Premium Gold Version)">
							</div>
							
							<div class="ic_plugin_content">
								<h2><a href="http://plugins.infosofttech.com/woocommerce-advance-sales-report-premium-gold/">WooCommerce Report (Premium Gold Version)</a></h2>
                                <span class="amount">$59</span>
								<ul class="UlList">
									<li>Improvised Dashboard (today’s, total, other useful summary)</li>
									<li>Sales Summary by Map and graph View</li>
									<li><strong>Projected Vs Actual Sales</strong></li>
									<li>Detail reports</li>
									<li>8 different all detail report</li>
									<li>8 different crosstab report</li>
									<li>Variation reporting with Advance Variation Filters</li>
									<li>Simple and variation <strong>stock List</strong></li>
									<li>Projected/Actual sales report</li>
									<li>Tax report by city, state, country, tax name many more</li>
									<li><strong>Total 40+ reports</strong></li>
								</ul>
                                <div class="ic_readmore"><a href="http://plugins.infosofttech.com/woocommerce-advance-sales-report-premium-gold/">Read More</a></div>
							</div>
						</div>
					</div>
					
					<div class="col-md-4">
						<div class="ic_other_plugins">
							<div class="ic_plugin_img">
								<img src="<?php echo $this->constants['plugin_url']?>/assets/images/icons/enterprise_edition.jpg" alt="WooCommerce Advance Sales Report (Enterprise Edition)" />
							</div>
							<div class="ic_plugin_content">
								<h2><a href="http://plugins.infosofttech.com/woocommerce-enterprise-edition/">Enterprise Edition</a></h2>
								<ul class="UlList">
									<li><strong>All the features of Premium Gold Version</strong></li>
									<li>Cost of Goods/Profit Report/Analysis (<strong>Total Profit/Margin Earned</strong>, Monthly Profit Center/Summary, <strong>Top n Profit Earning Products</strong>, Total Cost of Goods)</li>
									<li><strong>Sales Trend Analysis</strong> (Group/Combo Product/Order Quantity Analysis,Best Product Sales at last weeks, etc.)</li>
									<li>Stock Reports/Analysis (Minimum Level Product Stock Alert, <strong>Stock Planner</strong>, <strong>Stock Alerts by Email</strong> etc.)</li>
									<li>Customer in Price Point </li>
									<li><strong>New Customer/Repeat Customer Analysis</strong></li>
									<li>Top n Customer Report who Orders Frequently</li>
									<li>Customer Who has not Purchased within particular date range</li>
									<li><strong>Total 55+ reports</strong></li>
								</ul>
                                <div class="ic_readmore"><a href="http://plugins.infosofttech.com/woocommerce-enterprise-edition/">Read More</a></div>
							</div>
						</div>
					</div>
					
					<div class="col-md-4">
						<div class="ic_other_plugins">
							<div class="ic_plugin_img">
								<img src="<?php echo $this->constants['plugin_url']?>/assets/images/icons/inventory.jpg" alt="WooCommerce Inventory Plugin" />
							</div>
							
							<div class="ic_plugin_content">
								<h2><a href="http://plugins.infosofttech.com/products/woocommerce-inventory-management/">Inventory Management</a></h2>
								<ul class="UlList">
									<li><strong>Opening stock report</strong></li>
									<li><strong>Purchase Entry</strong></li>
									<li>Stock Adjustment Entry</li>
									<li><strong>Vendor Details</strong></li>
									<li><strong>Stock/Item Ledger</strong></li>
									<li>Purchase List</li>
									<li><strong>Stock adjustment List</strong></li>
									<li>Other charges List</li>
									<li>Location List</li>
									<li><strong>Product ledger report</strong></li>
								</ul>
                                <div class="ic_readmore"><a href="http://plugins.infosofttech.com/products/woocommerce-inventory-management/">Read More</a></div>
							</div>
						</div>
					</div>
					
					<div class="col-md-4">
						<div class="ic_other_plugins">
							<div class="ic_plugin_img">
								<img src="<?php echo $this->constants['plugin_url']?>/assets/images/icons/job-manager.jpg" alt="WooCommerce Job Manager" />
							</div>
							
							<div class="ic_plugin_content">
								<h2><a href="http://plugins.infosofttech.com/ic-wp-job-manager/">IC WP Job Manager</a></h2>
								<ul class="UlList">
									<li>Add a Job</li>
									<li>Add status, category, location, type, position</li>
									<li>Add <strong>company</strong> which offers the job</li>
									<li>Can set expire date of the job</li>
									<li><strong>Employee’s salary</strong></li>
									<li>The applicant can apply for the job</li>
									<li>The admin will see the list of applicants in the backend.</li>
									<li><strong>Email notification</strong> for admin</li>
									<li><strong>Dashboard summaries</strong></li>
									<li>Job Report with export to Excel</li>
									<li><strong>Application Report</strong></li>
								</ul>
                                <div class="ic_readmore"><a href="http://plugins.infosofttech.com/ic-wp-job-manager/">Read More</a></div>
							</div>
						</div>
					</div>
					
					<div class="col-md-4">
						<div class="ic_other_plugins">
							<div class="ic_plugin_img">
								<img src="<?php echo $this->constants['plugin_url']?>/assets/images/icons/subscription.jpg" alt="WooCommerce Subscription" />
							</div>
							
							<div class="ic_plugin_content">
								<h2><a href="http://plugins.infosofttech.com/woocommerce-subscription-report/">Subscription Report</a></h2>
								<span class="amount">$129</span>
								<ul class="UlList">
									<li>Subscription wise <strong>dashboard summaries</strong></li>
									<li>Top n Subscription Countries</li>
									<li><strong>Subscription Summary</strong></li>
									<li><strong>Subscription Item List</strong></li>
									<li><strong>Subscription Expire List</strong></li>
									<li>Subscription Payment List</li>
									<li>Daily summary</li>
									<li><strong>Free or Trial Subscription Due</strong></li>
									<li>Free or Trial Subscription Due</li>
									<li><strong>Prod. / Month Crosstab</strong></li>
								</ul>
                                <div class="ic_readmore"><a href="http://plugins.infosofttech.com/woocommerce-subscription-report/">Read More</a></div>
							</div>
						</div>
					</div>
					
					<div class="col-md-4">
						<div class="ic_other_plugins">
							<div class="ic_plugin_img">
								<img src="<?php echo $this->constants['plugin_url']?>/assets/images/icons/sales_trends_analysis.jpg" alt="WooCommerce Sales Trends Analysis" />
							</div>
							
							<div class="ic_plugin_content">
								<h2><a href="http://plugins.infosofttech.com/woocommerce-sales-trends-analysis-reports/">WooCommerce Sales Trend Analysis (Pro)</a></h2>
								<ul class="UlList">
									<li><strong>Dashboard</strong></li>
									<li>Simple product order quantity analysis</li>
									<li>Variation product order quantity analysis</li>
									<li>Product Combination/ Group sales analysis</li>
									<li>Customer in Price Point i.e. Search on min and max price range report</li>
									<li>New Customer/Repeat Customer Analysis</li>
									<li>Top n Customer Report who Orders Frequently</li>
									<li>Customer Who has not Purchased within particular date range</li>
								</ul>
                                <div class="ic_readmore"><a href="http://plugins.infosofttech.com/woocommerce-sales-trends-analysis-reports/">Read More</a></div>
							</div>
						</div>
					</div>
					
					<div class="col-md-4">
						<div class="ic_other_plugins">
							<div class="ic_plugin_img">
								<img src="<?php echo $this->constants['plugin_url']?>/assets/images/icons/audit-log-pro.jpg" alt="WooCommerce Audit Log Pro" />
							</div>
							
							<div class="ic_plugin_content">
								<h2><a href="#">Audit Log (Pro)</a></h2>
								<ul class="UlList">
									<li>Order Deleted</li>
									<li>Order Item Deleted</li>
									<li>Order Item Edited</li>
									<li>New Item Added</li>
									<li>Order Status Change</li>
									<li>Order Trash</li>
									<li>Restore Order</li>
									<li>Refund Add</li>
									<li>Refund delete</li>
									<li>Notes delete</li>
									<li>Edited Date and Customer Name</li>
								</ul>
                                <div class="ic_readmore"><a href="#">Read More</a></div>
							</div>
						</div>
					</div>
					
				</div>
			</div>-->
			<?php
				$args = array( 'plugin_name' 		=> '1234',	'product_id' 		=> '1234');
				$request = wp_remote_post( 'http://plugins.infosofttech.com/addon/add-on.html', array(
					'method' 		=> 'POST',
					'timeout' 		=> 45,
					'redirection' 	=> 5,
					'httpversion' 	=> '1.0',
					'blocking' 		=> true,
					'headers' 		=> array(),
					'body' 			=> $args,
					'cookies' 		=> array(),
					'sslverify' 	=> false
				) );
				//$this->print_array();
				echo "<div>{$request['body']}</div>";	
			?>
			
<?php     
		}
		
	}
}

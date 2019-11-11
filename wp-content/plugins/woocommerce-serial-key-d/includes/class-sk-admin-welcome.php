<?php
/**
 * Welcome Page Class
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'SK_Admin_Welcome' ) ) {

	/**
	 * SK_Admin_Welcome class
	 */
	class SK_Admin_Welcome {

		/**
		 * Hook in tabs.
		 */
		public function __construct() {
			add_action( 'admin_head', array( $this, 'admin_head' ) );
			add_action( 'admin_init', array( $this, 'sk_welcome' ) );
			add_action( 'admin_footer', array( $this, 'serial_key_support_ticket_content' ) );
		}

		public function show_welcome_page() {
		
			if( empty($_GET['landing-page']) ) {
				return;
			}
			
			switch ( $_GET['landing-page'] ) {
				case 'sk-about' :
					$this->serial_key_about_screen();
					break;
				case 'sk-faqs' :
				 	$this->serial_key_faqs_screen();
					break;
			}
		}

		/**
		 * Add styles just for this page, and remove dashboard page links.
		 */
		public function admin_head() {
			?>
			<style type="text/css">
				/*<![CDATA[*/
				.about-wrap h3 {
					margin-top: 1em;
					margin-right: 0em;
					margin-bottom: 0.1em;
					font-size: 1.25em;
					line-height: 1.3em;
				}
				.about-wrap .button-primary {
					margin-top: 18px;
				}
				.about-wrap .button-hero {
					color: #FFF!important;
					border-color: #03a025!important;
					background: #03a025 !important;
					box-shadow: 0 1px 0 #03a025;
					font-size: 1em;
					font-weight: bold;
				}
				.about-wrap .button-hero:hover {
					color: #FFF!important;
					background: #0AAB2E!important;
					border-color: #0AAB2E!important;
				}
				.about-wrap p {
					margin-top: 0.6em;
					margin-bottom: 0.8em;
					line-height: 1.6em;
					font-size: 14px;
				}
				.about-wrap .feature-section {
					padding-bottom: 5px;
				}
				/*]]>*/
			</style>
			<?php
		}

		/**
		 * Serial Key Support Form
		 */
		function serial_key_support_ticket_content() {
	        global $sa_smart_offers_upgrade;

	        if (!wp_script_is('thickbox')) {
	        	if (!function_exists('add_thickbox')) {
	            	require_once ABSPATH . 'wp-includes/general-template.php';
	        	}
	        	add_thickbox();
	    	}

	        if ( ! method_exists( 'StoreApps_Upgrade_2_2', 'support_ticket_content' ) ) return;

			$prefix = 'sa_serial_key';
	        $sku = 'wcsk';
	        $plugin_data = get_plugin_data( SK_PLUGIN_FILE );
	        $license_key = get_site_option( $prefix.'_license_key' );
	        $text_domain = 'woocommerce-serial-key';

	        StoreApps_Upgrade_2_2::support_ticket_content( $prefix, $sku, $plugin_data, $license_key, $text_domain );
	    }

		/**
		 * Intro text/links shown on all about pages.
		 */
		private function intro() {

			if ( is_callable( 'SA_Serial_Key::get_plugin_data' ) ) {
				$plugin_data = SA_Serial_Key::get_plugin_data();
				$version = $plugin_data['Version'];
			} else {
				$version = '';
			}

			?>
			<h1 style="margin: 0;"><?php echo sprintf(__( 'Welcome to WooCommerce Serial Key %s', SA_Serial_Key::$text_domain ), $version ); ?></h1>

			<div style="margin-top:0.3em;"><?php _e("Thanks for installing! We hope you enjoy using WooCommerce Serial Key.", SA_Serial_Key::$text_domain); ?></div>

			<div class="feature-section col two-col" style="margin-bottom:30px!important;">
				<div class="col">
					<a href="<?php echo admin_url('admin.php?page=woocommerce_serial_key&tab=validation'); ?>" class="button button-hero"><?php _e( 'Go To Serial Key Settings', SA_Serial_Key::$text_domain ); ?></a>
				</div>
				<div class="col last-feature">
					<p align="right">
						<?php echo __( 'Questions? Need Help?', SA_Serial_Key::$text_domain ); ?><br>
						<a href="<?php echo esc_url( 'https://www.storeapps.org/knowledgebase_category/woocommerce-serial-key/?utm_source=wcsk&utm_medium=welcome_page&utm_campaign=view_docs', SA_Serial_Key::$text_domain ); ?>" target="_blank"><?php _e( 'Docs', SA_Serial_Key::$text_domain ); ?></a>
					</p>
				</div>
			</div>

			<h2 class="nav-tab-wrapper">
				<a class="nav-tab <?php if ( $_GET['landing-page'] == 'sk-about' ) echo 'nav-tab-active'; ?>" href="<?php echo esc_url( add_query_arg( array( 'landing-page' => 'sk-about' ), 'admin.php?page=woocommerce_serial_key' ) ); ?>">
					<?php _e( "Know Serial Key", SA_Serial_Key::$text_domain ); ?>
				</a>
				<a class="nav-tab <?php if ( $_GET['landing-page'] == 'sk-faqs' ) echo 'nav-tab-active'; ?>" href="<?php echo esc_url( add_query_arg( array( 'landing-page' => 'sk-faqs' ), 'admin.php?page=woocommerce_serial_key' ) ); ?>">
					<?php _e( "FAQ's", SA_Serial_Key::$text_domain ); ?>
				</a>
			</h2>
			<?php
		}

		/**
		 * Output the about screen.
		 */
		public function serial_key_about_screen() {
			?>
			<div class="wrap about-wrap">

					<?php $this->intro(); ?>

						<div>
							<div class="feature-section col two-col">
								<div class="col">
									<h3><?php echo __( 'What is Serial Key?', SA_Serial_Key::$text_domain ); ?></h3>
									<p>
										<?php echo __( 'WooCommerce Serial Key is a simple, easy to use add-on for your WooCommerce store.', SA_Serial_Key::$text_domain ); ?>
										<?php echo __( 'It can make a WooCommerce downloadable product to generate unique serial keys for each purchase.', SA_Serial_Key::$text_domain ); ?>
									</p>
								</div>
								<div class="col last-feature">
									<h3><?php echo __( 'What it does?', SA_Serial_Key::$text_domain ); ?></h3>
									<p>
										<?php echo __( 'When this feature is enabled for any downloadable product, store will automatically generate a unique serial key for each purchased product & it will also send those serial key in ‘Order completed’ email. The serial keys for product will also be available on customer’s account.', SA_Serial_Key::$text_domain ); ?>
									</p>
								</div>
							</div>
							<center><h3><?php echo __( 'What is possible', SA_Serial_Key::$text_domain ); ?></h3></center>
							<div class="feature-section col three-col" >
								<div class="col">
									<h4><?php echo __( 'Validate a Serial Key', SA_Serial_Key::$text_domain ); ?></h4>
									<p>
										<?php echo __( 'Along with generation of serial key, it also gives power to validate them. It automatically enables validation process on your site. To know more about validation process, click', SA_Serial_Key::$text_domain); ?>
										<a target="_blank" href="<?php echo admin_url('admin.php?page=woocommerce_serial_key'); ?>">
											<?php echo __( ' here', SA_Serial_Key::$text_domain ); ?>
										</a>
									</p>
								</div>
								<div class="col">
									<h4><?php echo __( 'Add Serial Key for previously created orders', SA_Serial_Key::$text_domain ); ?></h4>
									<p>
										<?php echo __( 'If you already have an Order, and you want to add a serial key for that, then you can easily generate serial key from Order admin page. To know more about it, click ', SA_Serial_Key::$text_domain ); ?>
										<a target="_blank" href="https://www.storeapps.org/docs/wcsk-how-to-add-update-serial-keys-in-existing-orders/?utm_source=wcsk&utm_medium=welcome_page&utm_campaign=wcsk_know">
											<?php echo __( 'here', SA_Serial_Key::$text_domain ); ?>
										</a>
									</p>
								</div>
								<div class="col last-feature">
									<h4><?php echo __( 'Update Serial Key for an existing order', SA_Serial_Key::$text_domain ); ?></h4>
									<p>
										<?php echo __( 'If you already have a serial key for a particular Order and you want to update it, then you can update existing serial key from Order admin page. To know more about it, click ', SA_Serial_Key::$text_domain ); ?>
										<a target="_blank" href="https://www.storeapps.org/docs/wcsk-how-to-add-update-serial-keys-in-existing-orders/?utm_source=wcsk&utm_medium=welcome_page&utm_campaign=wcsk_know">
											<?php echo __( 'here', SA_Serial_Key::$text_domain ); ?>
										</a>
									</p>
								</div>
							</div>
							<div class="feature-section col three-col" >
								<div class="col">
									<h4><?php echo __( 'Generate/Use your own Serial Keys', SA_Serial_Key::$text_domain ); ?></h4>
									<p>
										<?php echo __( 'Want to upload a Serial Key of your choice? Then Serial Key plugin allows you to upload a CSV file containing a list of your Serial Keys which will used (instead of the default generated) when an order is completed. To know more about it, click ', SA_Serial_Key::$text_domain ); ?>
										<a target="_blank" href="https://www.storeapps.org/docs/wcsk-how-to-import-serial-keys-from-csv-file/?utm_source=wcsk&utm_medium=welcome_page&utm_campaign=wcsk_know">
											<?php echo __( 'here', SA_Serial_Key::$text_domain ); ?>
										</a>
									</p>
								</div>
								<div class="col">
									<h4><?php echo __( 'Search / Filter / Find orders using Serial Key', SA_Serial_Key::$text_domain ); ?></h4>
									<p>
										<?php echo __( 'If you want to locate or search orders using a serial key, you can filter order’s list using that serial key along with a prefix text <code>serial:</code>. Refer ', SA_Serial_Key::$text_domain ); ?>
										<a target="_blank" href="https://www.storeapps.org/docs/wcsk-how-to-search-filter-orders-using-serial-keys/?utm_source=wcsk&utm_medium=welcome_page&utm_campaign=wcsk_know">
											<?php echo __( 'here', SA_Serial_Key::$text_domain ); ?>
										</a>
									</p>
								</div>
								<div class="col last-feature">
									<h4><?php echo __( 'How your customers can manage usage of Serial Key', SA_Serial_Key::$text_domain ); ?></h4>
									<p>
										<?php echo __( 'Your customers can manager their serial key usage from "Manage Serial Key Usage" page. By default, this page is added as a subpage of "My Account" page of your customers. If you do not want your customer to manage their serial keys, you can remove / trash this page. Refer ', SA_Serial_Key::$text_domain ); ?>
										<a target="_blank" href="https://www.storeapps.org/docs/wcsk-how-customers-can-manage-usage-of-serial-key/?utm_source=wcsk&utm_medium=welcome_page&utm_campaign=wcsk_know">
											<?php echo __( 'here', SA_Serial_Key::$text_domain ); ?>
										</a>
									</p>
								</div>
							</div>
						</div>

						<div class="catalog" align="center">
							<h4><?php _e( 'Do check out Some of our other products!', SA_Serial_Key::$text_domain ); ?></h4>
							<p><center><a target="_blank" href="<?php echo esc_url('https://www.storeapps.org/shop/'); ?>"><?php _e('Let me take to catalog', SA_Serial_Key::$text_domain); ?></a></center></p>
						</div>
			</div>
			<?php
		}

		/**
		 * Output the FAQ's screen.
		 */
		public function serial_key_faqs_screen() {
			?>
			<div class="wrap about-wrap">

				<?php $this->intro(); ?>
	            
	            <h3><?php echo __("FAQ / Common Problems", SA_Serial_Key::$text_domain); ?></h3>

	            <?php
	            	$faqs = array(
	            				array(
	            						'que' => __( 'Can serial keys be generated for all product types?', SA_Serial_Key::$text_domain ),
	            						'ans' => __( 'No, currently, Serial Keys will be generated only for Downloadable products of <code>Simple & Variable</code> product type.', SA_Serial_Key::$text_domain )
	            					),
	            				array(
	            						'que' => __( 'What does UUID means?', SA_Serial_Key::$text_domain ),
	            						'ans' => __( 'UUID stands for \'Universal Unique Identifier\'. It is that unique value using which you can track where your serial key is getting used.', SA_Serial_Key::$text_domain )
	            					),
	            				array(
	            						'que' => __( 'What does \'Display name for UUID\' means?', SA_Serial_Key::$text_domain ),
	            						'ans' => __( '\'Display name for UUID\' is only a label & it can be anything. It is not used for anything other than a text identifier.', SA_Serial_Key::$text_domain )
	            					),
	            				array(
	            						'que' => __( 'What is the use of \'Manage Serial Key\' page under My Account Page?', SA_Serial_Key::$text_domain ),
	            						'ans' => __( 'Your customer\'s can easily manage and keep a track of all their serial keys under \'Manage Serial Key\' page.', SA_Serial_Key::$text_domain )
	            					),
	            				array(
	            						'que' => __( 'Can I implement Serial Key in my Mobile applications?', SA_Serial_Key::$text_domain ),
	            						'ans' => __( 'Yes, you can implement Serial key in your mobile applications.', SA_Serial_Key::$text_domain )
	            					),
	            				array(
	            						'que' => __( 'Can I modify pattern, number of character used for serial key?', SA_Serial_Key::$text_domain ),
	            						'ans' => __( 'Yes, you can import a CSV file containing Serial Keys that you want to assign to the new orders.', SA_Serial_Key::$text_domain )
	            					),
	            				array(
	            						'que' => __( 'I have imported my Serial Key file but Keys are not getting used from it for New Order.', SA_Serial_Key::$text_domain ),
	            						'ans' => __( 'Make sure it is a CSV file.', SA_Serial_Key::$text_domain )
	            					),
	            				array(
	            						'que' => __( 'I can\'t find a way to do X...', SA_Serial_Key::$text_domain ),
	            						'ans' => sprintf(__( 'Serial Key is actively developed. If you can\'t find your favorite feature (or have a suggestion) %s. We\'d love to hear from you.', SA_Serial_Key::$text_domain ), '<a class="thickbox" href="' . admin_url('#TB_inline?inlineId=sa_serial_key_post_query_form&post_type=sa_serial_key') .'">' . __( 'contact us', SA_Serial_Key::$text_domain ) . '</a>' )
	            					)

	            	);

				$faqs = array_chunk( $faqs, 2 );

				echo '<div>';
				foreach ( $faqs as $fqs ) {
					echo '<div class="two-col">';
					foreach ( $fqs as $index => $faq ) {
						echo '<div' . ( ( $index == 1 ) ? ' class="col last-feature"' : ' class="col"' ) . '>';
						echo '<h4>' . $faq['que'] . '</h4>';
						echo '<p>' . $faq['ans'] . '</p>';
						echo '</div>';
					}
					echo '</div>';
				}
				echo '</div>';
	    		?>

			</div>

			<?php

		}


		/**
		 * Sends user to the welcome page on first activation.
		 */
		public function sk_welcome() {

	       	if ( ! get_transient( '_sk_activation_redirect' ) ) {
				return;
			}
			
			// Delete the redirect transient
			delete_transient( '_sk_activation_redirect' );

			wp_redirect( admin_url( 'admin.php?page=woocommerce_serial_key&landing-page=sk-about' ) );
			exit;

		}
	}

}

$GLOBALS['sa_sk_admin_welcome'] = new SK_Admin_Welcome();
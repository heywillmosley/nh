<?php

// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}

if (!class_exists('SK_Admin_Dashboard')) {

	class SK_Admin_Dashboard {

		/**
		 * Constructor
		 */
		public function __construct() {
			add_action( 'admin_enqueue_scripts', array( $this, 'wcsk_load_scripts' ) );
		}

		public static function serial_key_dashboard_page() {

			global $wpdb;

			$sk_header_footer = "<tr class='row'>
									<th scope='col' class='check-column' style='padding: 17px 2px;'>
										<input type='checkbox' name='wcsk_checkall' id='wcsk_checkall' onClick='' />
									</th>
									<th scope='col'>
										". __( 'Serial Key', SA_Serial_Key::$text_domain )."
									</th>
									<th scope='col'>
										". __( 'Usage / Limit', SA_Serial_Key::$text_domain )."
									</th>
									<th scope='col'>
										". __( 'Expires On', SA_Serial_Key::$text_domain )."
									</th>
									<th scope='col'>
										". __( 'Product', SA_Serial_Key::$text_domain )."
									</th>
									<th scope='col'>
										". __( 'Order', SA_Serial_Key::$text_domain )."
									</th>
									<th scope='col'>
										". __( 'Purchased On', SA_Serial_Key::$text_domain )."
									</th>
								</tr>";

			if (isset($_POST['sa_wcsk_dashboard']) && $_POST['sa_wcsk_dashboard'] == 'yes') {
				$did = isset($_GET['did']) ? $_GET['did'] : '0';

				if ( isset( $_POST['wcsk_dashboard'] ) && $_POST['wcsk_dashboard'] != 'delete' ) {
					//	Just security thingy that wordpress offers us
					check_admin_referer('wcsk_dashboard_show');

					// First check if ID exist with requested ID
					$check_if_sk_exists = '0';
					if($did > 0) {
						$serial_keys_count = $wpdb->prepare("SELECT COUNT(*) AS `count` FROM `".$wpdb->prefix."woocommerce_serial_key` WHERE `serial_key_id` = %d", array($did));
					} else {
						$serial_keys_count = "SELECT COUNT(*) AS `count` FROM `".$wpdb->prefix."woocommerce_serial_key`";
					}
					$check_if_sk_exists = $wpdb->get_var( $serial_keys_count );

					if ($check_if_sk_exists != '1') {
						?><div class="error fade">
							<p><strong>
								<?php echo __( 'Details does not exists.', SA_Serial_Key::$text_domain ); ?>
							</strong></p>
						</div><?php
					} else {
						// Form submitted, check the action
						if (isset($_GET['ac']) && $_GET['ac'] == 'del' && isset($_GET['did']) && $_GET['did'] != '') {
							//	Delete selected record from the table
							SK_Admin_Dashboard::sa_delete_serial_key($did);

							//	Show success message
							?>
							<div class="notice notice-success is-dismissible">
								<p><strong>
									<?php echo __( 'Serial Key deleted.', SA_Serial_Key::$text_domain ); ?>
								</strong></p>
							</div>
							<?php
						}
					}
				}
			}

			?>
			<form name="sa_wcsk_dashboard" method="post" onsubmit="return _wcsk_bulkaction()">
				<div class="tablenav">

				</div>

				<table class="serial-key-table wp-list-table widefat striped fixed">
					<thead>
						<?php echo $sk_header_footer; ?>
					</thead>
					<tfoot>
						<?php echo $sk_header_footer; ?>
					</tfoot>
					<tbody>
						<?php
							$wcsk_details = array();
							$wcsk_details = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}woocommerce_serial_key ORDER BY serial_key_id DESC", 'ARRAY_A' );
							if(count($wcsk_details) > 0) {
								foreach ($wcsk_details as $wcsk_detail) {
									?>
									<tr>
										<td align="left">
											<input name="chk_delete[]" id="chk_delete[]" type="checkbox" value="<?php echo $wcsk_detail['serial_key_id']; ?>" />
										</td>
										<td>
											<?php echo $wcsk_detail['serial_key']; ?>
											<div class="row-actions">
												<span class="delete">
													<a onClick="javascript:_wcsk_delete('<?php echo $wcsk_detail['serial_key_id']; ?>')" href="javascript:void(0);">
														<?php echo __( 'Delete', SA_Serial_Key::$text_domain ); ?>
													</a>
												</span>
											</div>
										</td>
										<td>
											<?php
												$serial_key_usage_on = maybe_unserialize( $wcsk_detail['uuid'] );
												if ( is_array( $serial_key_usage_on ) && isset( $serial_key_usage_on ) ) {
													$usage = count( $serial_key_usage_on );
												}
												if ( !empty( $usage ) ) {
													echo $usage." / ".$wcsk_detail['limit'];
												} else {
													echo "0 / ".$wcsk_detail['limit'];
												}
											?>
										</td>
										<td>
											<?php
												if ( !empty( $wcsk_detail['valid_till'] ) ) {
													echo $wcsk_detail['valid_till'];
												} else {
													echo "-";
												}
											?>
										</td>
										<td>
											<?php
												if ( !empty( $wcsk_detail['product_id'] ) ) {
													$product = wc_get_product( $wcsk_detail['product_id'] );
													if ( !( $product instanceof WC_Product ) ) {
														echo $wcsk_detail['product_id'];
														echo "<br><i>(Looks like this product doesn't exists)</i>";
													} else {
														$product_name = $product->get_formatted_name();
														echo $product_name;
													}
												} else {
													echo "-";
												}
											 ?>
										</td>
										<td>
											<?php
												if ( !empty( $wcsk_detail['order_id'] ) ) {
													echo '<a href="' . admin_url( 'post.php?post=' . $wcsk_detail['order_id'] . '&action=edit' ) . '" target="_blank">#' . $wcsk_detail['order_id'] . '</a>';
												} else {
													echo "-";
												}
											 ?>
										</td>
										<td>
											<?php
												if ( !empty( $wcsk_detail['order_id'] ) ) {
													$get_order_paid_date = get_post_meta( $wcsk_detail['order_id'], '_completed_date', true );
													if ( !empty($get_order_paid_date) ) {
														echo $get_order_paid_date;
													} else {
														echo "<i>(Looks like this order doesn't exists)</i>";
													}
												} else {
													echo "-";
												}
											?>
										</td>
									</tr>
									<?php
								}
							} else {
								?>
								<tr>
									<td colspan="8" align="center"><?php echo __( 'No records available.', SA_Serial_Key::$text_domain ); ?></td>
								</tr>
								<?php
							}
						?>
					</tbody>
				</table>
				<?php wp_nonce_field('wcsk_dashboard_show'); ?>
				<input type="hidden" name="sa_wcsk_dashboard" id="sa_wcsk_dashboard" value="yes"/>
				<input type="hidden" name="wcsk_dashboard" id="wcsk_dashboard" value=""/>
			</form>

			<?php
		}

		public static function sa_delete_serial_key($id = 0) {

			global $wpdb;

			$sk_delete_record = $wpdb->prepare("DELETE FROM `".$wpdb->prefix."woocommerce_serial_key` WHERE `serial_key_id` = %d LIMIT 1", $id);
			$wpdb->query($sk_delete_record);

			return true;
		}


		public static function wcsk_load_scripts() {

			if( !empty( $_GET['page'] ) && $_GET['page'] == 'woocommerce_serial_key' ) {
				wp_register_script( 'wcsk-admin-notices', plugins_url( '/sk-admin-dashboard.js', __FILE__ ) , '', '', true );
				wp_enqueue_script( 'wcsk-admin-notices' );
				$es_select_params = array(
					'wcsk_delete_record'   => _x( 'Do you want to delete this record?', SA_Serial_Key::$text_domain ),
					'wcsk_bulk_action'     => _x( 'Please select the bulk action.', SA_Serial_Key::$text_domain ),
					'wcsk_confirm_delete'  => _x( 'Are you sure you want to delete selected records?', SA_Serial_Key::$text_domain )
				);
				wp_localize_script( 'wcsk-admin-notices', 'wcsk_admin_notices', $es_select_params );
			}

		}

	}

	return new SK_Admin_Dashboard();
}
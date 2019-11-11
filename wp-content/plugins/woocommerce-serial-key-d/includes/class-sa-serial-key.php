<?php
class SA_Serial_Key {

	/**
	 * @var $text_domain 
	 */
	static $text_domain = 'woocommerce-serial-key';

	public function __construct( $file ) {

		add_action( 'init', array( $this, 'localize' ) );
		add_action( 'admin_init', array( $this, 'activated' ) );

		if ( ! $this->is_wc_gte_25() ) {
			add_action( 'admin_notices', array( $this, 'admin_notice_sa_needs_wc_25_above' ) );
		}

		// Action to Upgrade Serial Key Table
		add_action( 'init', array( $this, 'sa_serial_key_db_update' ) );

		add_action( 'admin_init', array( $this, 'add_my_serial_keys_page' ) );
		add_action( 'admin_init', array( $this, 'sync_serial_key_usage_action' ) );
		add_action( 'admin_menu', array( $this, 'woocommerce_serial_key_admin_menu' ) );

		add_action( 'woocommerce_product_options_downloads', array( $this, 'woocommerce_product_options_serial_key_simple' ) );
		add_action( 'woocommerce_product_after_variable_attributes', array( $this, 'woocommerce_product_options_serial_key_variable' ), '', 3 );

		// Actions to save option to generate serial key
		add_action( 'woocommerce_process_product_meta', array( $this, 'woocommerce_process_product_meta_serial_simple' ));
		add_action( 'woocommerce_save_product_variation', array( $this, 'woocommerce_process_product_meta_save_single_variable_serial' ), 10, 2 );

		// Actions on order status change
		add_action( 'woocommerce_order_status_completed', array( $this, 'generate_serial_key_from_order' ) );
		add_action( 'add_meta_boxes', array($this, 'serial_key_details') );

		// Action for showing serial key on My Account page
		if ( $this->is_wc_gte_26() ) {
			add_filter( 'woocommerce_account_downloads_columns', array( $this, 'add_new_serial_key_column_in_downloads' ) );
			add_action( 'woocommerce_account_downloads_column_serial-keys', array( $this, 'sa_display_all_serial_keys_of_products' ) );
		} else {
			add_action( 'woocommerce_before_my_account', array( $this, 'display_serial_keys_of_products' ) );
		}

		// Action for including serial key in e-mail
		add_action( 'woocommerce_email_after_order_table', array( $this, 'display_serial_keys_after_order_table' ) );

		// Action to search coupon based on email ids in customer email postmeta key
		add_action( 'parse_request', array( $this,'filter_orders_using_serial_key' ) );
		add_filter( 'get_search_query', array( $this,'filter_orders_using_serial_key_label' ) );

		add_shortcode( 'manage_serial_key_usage', array( $this, 'manage_serial_key_usage_page_content' ) );
		add_filter( 'serial_key_usage_template', array( $this, 'serial_key_usage_template_path' ) );
		add_filter( 'serial_key_notification_for_count_email_template', array(  $this, 'serial_key_notification_for_count_email_template_path' ) );

		add_action( 'wp_ajax_update_serial_key', array( $this, 'ajax_update_serial_key' ) );
		add_action( 'wp_ajax_generate_serial_key', array( $this, 'ajax_generate_serial_key' ) );

		add_filter( 'sa_wcsk_previous_uuids', array( $this, 'get_previous_uuids' ), 10, 2 );

		// Filter to make all Serial Key Meta Protected
		add_filter( 'is_protected_meta', array( $this, 'make_sk_meta_protected' ), 10, 3 );

		add_filter( 'plugin_action_links_' . plugin_basename( $file ), array( $this, 'plugin_action_links' ) );

		add_filter( 'sa_active_plugins_for_quick_help', array( $this, 'active_plugins_for_quick_help' ), 10, 2 );

		add_filter( 'sa_is_page_for_notifications', array( $this, 'is_page_for_notifications' ), 10, 2 );
		add_action( 'wp_loaded', array( $this, 'rest_api_init' ) );

	}

	/**
	 * to handle WC compatibility related function call from appropriate class
	 * 
	 * @param $function_name string
	 * @param $arguments array of arguments passed while calling $function_name
	 * @return result of function call
	 * 
	 */
	public function __call( $function_name, $arguments = array() ) {

		if ( ! is_callable( 'SA_WC_Compatibility_3_0', $function_name ) ) return;

		if ( ! empty( $arguments ) ) {
			return call_user_func_array( 'SA_WC_Compatibility_3_0::'.$function_name, $arguments );
		} else {
			return call_user_func( 'SA_WC_Compatibility_3_0::'.$function_name );
		}

	}

	/**
	 * Language loader
	 */
	function localize() {

		$text_domains = array( 'woocommerce-serial-key', 'localize_woocommerce_serial_key' );        // For Backward Compatibility

		$plugin_dirname = SK_PLUGIN_DIRNAME;

		foreach ( $text_domains as $text_domain ) {

			self::$text_domain = $text_domain;

			$locale = apply_filters( 'plugin_locale', get_locale(), self::$text_domain );

			$loaded = load_textdomain( self::$text_domain, WP_LANG_DIR . '/' . $plugin_dirname . '/' . self::$text_domain . '-' . $locale . '.mo' );

			if ( ! $loaded ) {
				$loaded = load_plugin_textdomain( self::$text_domain, false, $plugin_dirname . '/languages' );
			}

			if ( $loaded ) {
				break;
			}

		}

	}

	function activated() {
		$prefix = 'sa_serial_key';
		$is_check = get_option( $prefix . '_check_update', 'no' );
		if ( $is_check === 'no' ) {
			$response = wp_remote_get( 'https://www.storeapps.org/wp-admin/admin-ajax.php?action=check_update&plugin=wcsk' );
			update_option( $prefix . '_check_update', 'yes' );
		}
	}

	/**
	 * Function to show admin notice that Serial Key works with WC 2.5+
	 */
	public function admin_notice_sa_needs_wc_25_above() {
		?>
		<div class="updated error">
			<p><?php
				echo sprintf(__( '%s WooCommerce Serial Key is active but it will only work with WooCommerce 2.5+. %s.', self::$text_domain ), '<strong>' . __( 'Important', self::$text_domain ) . ':</strong>', '<a href="'.admin_url('plugins.php?plugin_status=upgrade').'" target="_blank" >' . __( 'Please update WooCommerce to the latest version', self::$text_domain ) . '</a>' );
			?></p>
		</div>
		<?php
	}

	/**
	 * Update sa_serial_key_db_version
	 */
	public function sa_serial_key_db_update() {

		$serial_key_current_db_version = get_option( 'sa_serial_key_db_version', 'no' );

		if ( $serial_key_current_db_version == 'no' ) {
			$this->sa_database_update_for_serial_key_1_3();
		}

		if ( $serial_key_current_db_version == '1.3' ) {
			$this->sa_database_update_for_serial_key_1_9_0();
		}

	}

	/**
	 * Find latest StoreApps Upgrade file
	 * @return string classname
	 */
	public function get_latest_upgrade_class() {

		$available_classes = get_declared_classes();
		$available_upgrade_classes = array_filter( $available_classes, function ( $class_name ) {
																			return strpos( $class_name, 'StoreApps_Upgrade_' ) === 0;
																		} );
		$latest_class = 'StoreApps_Upgrade_2_2';
		$latest_version = 0;
		foreach ( $available_upgrade_classes as $class ) {
			$exploded = explode( '_', $class );
			$get_numbers = array_filter( $exploded, function ( $value ) {
														return is_numeric( $value );
													} );
			$version = implode( '.', $get_numbers );
			if ( version_compare( $version, $latest_version, '>' ) ) {
				$latest_version = $version;
				$latest_class = $class;
			}
		}

		return $latest_class;
	}

	function sa_database_update_for_serial_key_1_3() {

		global $wpdb;

		if ( $wpdb->get_var( "SHOW TABLES LIKE '{$wpdb->prefix}woocommerce_serial_key';" ) ) {
			if ( ! $wpdb->get_var( "SHOW COLUMNS FROM `{$wpdb->prefix}woocommerce_serial_key` LIKE 'limit';" ) ) {
				$wpdb->query( "ALTER TABLE {$wpdb->prefix}woocommerce_serial_key ADD `limit` int(11) UNSIGNED NOT NULL DEFAULT 0;" );
			}
			if ( ! $wpdb->get_var( "SHOW COLUMNS FROM `{$wpdb->prefix}woocommerce_serial_key` LIKE 'uuid';" ) ) {
				$wpdb->query( "ALTER TABLE {$wpdb->prefix}woocommerce_serial_key ADD `uuid` longtext NOT NULL DEFAULT '';" );
			}
		}

		update_option( 'sa_serial_key_db_version', '1.3' );

	}

	function sa_database_update_for_serial_key_1_9_0() {

		global $wpdb;
		
		if ( $wpdb->get_var( "SHOW TABLES LIKE '{$wpdb->prefix}woocommerce_serial_key';" ) ) {
			if ( ! $wpdb->get_var( "SHOW COLUMNS FROM `{$wpdb->prefix}woocommerce_serial_key` LIKE 'serial_key_id';" ) ) {
				if ( $wpdb->get_var( "SHOW INDEXES FROM `{$wpdb->prefix}woocommerce_serial_key` WHERE Key_name = 'PRIMARY';" ) ) {
					$wpdb->query( "ALTER TABLE {$wpdb->prefix}woocommerce_serial_key DROP PRIMARY KEY;" );
				}
				$wpdb->query( "ALTER TABLE {$wpdb->prefix}woocommerce_serial_key ADD `serial_key_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT FIRST, ADD PRIMARY KEY (`serial_key_id`);" );
			}
		}

		update_option( 'sa_serial_key_db_version', '1.9.0' );
	}

	function add_my_serial_keys_page() {
		global $wpdb;

		if ( get_option( 'my_serial_keys_page_id' ) !== false ) {
			return;
		}

		$slug = 'my_serial_keys';
		$option = 'my_serial_keys_page_id';
		$page_title = __( 'Manage Serial Key Usage', 'woocommerce' );
		$page_content = '[manage_serial_key_usage]';
		$post_parent = wc_get_page_id( 'myaccount' );
		$option_value = get_option( $option );

		if ( $option_value > 0 && get_post( $option_value ) )
				return;

		$page_found = $wpdb->get_var("SELECT ID FROM " . $wpdb->posts . " WHERE post_name = '{$slug}' LIMIT 1;");
		if ( $page_found ) :
				if ( ! $option_value || $option_value != $page_found )
						update_option( $option, $page_found );
				return;
		endif;

		$page_data = array(
			'post_status'       => 'publish',
			'post_type'         => 'page',
			'post_author'       => 1,
			'post_name'         => $slug,
			'post_title'        => $page_title,
			'post_content'      => $page_content,
			'post_parent'       => $post_parent,
			'comment_status'    => 'closed'
		);
		$page_id = wp_insert_post( $page_data );

		update_option( $option, $page_id );
	}

	function sync_serial_key_usage_action() {
		if ( isset( $_REQUEST['sync_serial_key_usage'] ) && $_REQUEST['sync_serial_key_usage'] == 1 ) {
			$this->sync_serial_key_usage( true );
		}
	}

	function woocommerce_serial_key_admin_menu() {
		add_submenu_page('woocommerce', __( 'Serial Key', self::$text_domain ), __( 'Serial Key', self::$text_domain ), 'manage_woocommerce', 'woocommerce_serial_key', array($this, 'woocommerce_serial_key_page_content') );
	}

	// Funtion to show search result based on serial key in order
	function filter_orders_using_serial_key( $wp ){
		global $pagenow, $wpdb;

		if ( 'edit.php' != $pagenow ) return;
		if ( !isset( $wp->query_vars['s'] ) ) return;
		if ($wp->query_vars['post_type']!='shop_order') return;

		$e = substr( $wp->query_vars['s'], 0, 6 );

		if ( 'serial:' == substr( $wp->query_vars['s'], 0, 7 ) ) {

			$serial = trim( substr( $wp->query_vars['s'], 7 ) );

			if ( !$serial ) return;

			$post_ids = $wpdb->get_col( "SELECT order_id FROM {$wpdb->prefix}woocommerce_serial_key WHERE serial_key LIKE '%$serial%'" );

			if ( !$post_ids ) return;

			unset( $wp->query_vars['s'] );

			$wp->query_vars['post__in'] = $post_ids;

			$wp->query_vars['serial'] = $serial;
		}
			
	}
	
	// Function to show label of the search result on serial key
	function filter_orders_using_serial_key_label( $query ){
		global $pagenow, $typenow, $wp;

		if ( 'edit.php' != $pagenow ) return $query;
		if ( $typenow!='shop_order' ) return $query;

		$s = get_query_var( 's' );
		if ($s) return $query;

		$serial = get_query_var( 'serial' );

		if ( $serial ) {

			$post_type = get_post_type_object($wp->query_vars['post_type']);
			return sprintf(__("%s with Serial Key %s", self::$text_domain), $post_type->labels->singular_name, $serial);
		}

		return $query;
	}
	
	//
	function serial_key_details() {
		global $post;
		
		if ( $post->post_type !== 'shop_order' ) return;
		
		add_meta_box( 'woocommerce-serial-key-details', __('Serial Key Details', self::$text_domain), array( $this, 'woocommerce_serial_key_details_meta_box' ), 'shop_order', 'normal');
	}

	function ajax_generate_serial_key() {

		check_ajax_referer( 'sa_serial_key_ajax', 'security' );

		$target_item = ( ! empty( $_POST['target_item'] ) ) ? $_POST['target_item'] : '';
		$target_item = explode( '_', $target_item );
		$reverse_target_item = array_reverse( $target_item );
		$product_id = ( ! empty( $reverse_target_item[0] ) ) ? $reverse_target_item[0] : 0;
		$order_id = ( ! empty( $reverse_target_item[1] ) ) ? $reverse_target_item[1] : 0;

		$html = '';

		$this->woocommerce_generate_serial_key( $order_id, array( $product_id ) );

		ob_start();
		$this->serial_key_meta_box_content( $order_id );
		$html = ob_get_clean();

		echo $html;
		die();        

	}
	
	function ajax_update_serial_key() {

		check_ajax_referer( 'sa_serial_key_ajax', 'security' );

		global $wpdb;

		$serial_key = ( ! empty( $_POST['serial_key'] ) ) ? $_POST['serial_key'] : '';
		$target_element = ( ! empty( $_POST['target_element'] ) ) ? $_POST['target_element'] : '';
		$target_element = explode( '_', $target_element );
		$reverse_target_element = array_reverse( $target_element );
		$product_id = ( ! empty( $reverse_target_element[0] ) ) ? $reverse_target_element[0] : 0;
		$order_id = ( ! empty( $reverse_target_element[1] ) ) ? $reverse_target_element[1] : 0;

		$response = array();

		$success = true;
		$fields = array();

		if ( empty( $serial_key ) ) {
			$fields[] = __( 'Serial Key', self::$text_domain );
			$success = false;
		}

		if ( empty( $product_id ) ) {
			$fields[] = __( 'Product ID', self::$text_domain );
			$success = false;
		}

		if ( empty( $order_id ) ) {
			$fields[] = __( 'Order ID', self::$text_domain );
			$success = false;
		}

		if ( ! $success ) {
			$response['success'] = 'no';
			$response['message'] = '<span class="dashicons dashicons-no-alt" style="color:#a00"></span>&nbsp;' . __( 'Invalid Value for ', self::$text_domain ) . implode( ', ', $fields );
		} else {
			$find_serial_key_query = $wpdb->prepare( "SELECT order_id, product_id FROM {$wpdb->prefix}woocommerce_serial_key WHERE serial_key = %s", $serial_key );
			$found_serial_key = $wpdb->get_results( $find_serial_key_query, 'ARRAY_A' );

			if ( ! empty( $found_serial_key ) ) {
				$response['success'] = 'no';
				$response['message'] = '<span class="dashicons dashicons-info" style="color:#edbb00"></span>&nbsp;' . sprintf(__( 'This Key is Already Used in %s', self::$text_domain ), '<a href="' . admin_url( 'post.php?post=' . $found_serial_key[0]['order_id'] . '&action=edit#woocommerce-serial-key-details' ) . '" target="_blank">Order #' . $found_serial_key[0]['order_id'] . '</a>' );
			} else {
				$update_serial_key_query = $wpdb->prepare( "UPDATE {$wpdb->prefix}woocommerce_serial_key SET serial_key = %s WHERE order_id = %d AND product_id = %d", $serial_key, $order_id, $product_id );
				$updated_serial_key = $wpdb->query( $update_serial_key_query, 'ARRAY_A' );

				if ( ! empty( $updated_serial_key ) ) {
					$response['success'] = 'yes';
					$response['message'] = '<span class="dashicons dashicons-yes" style="color:#26a65b;"></span>&nbsp;' . __( 'Serial Key Updated Successfully', self::$text_domain );
				} else {
					$response['success'] = 'no';
					$response['message'] = '<span class="dashicons dashicons-no-alt" style="color:#ff0000"></span>&nbsp;' . __( 'Could Not Update Serial Key', self::$text_domain );
				}
			}
		}

		echo json_encode( $response );
		die();
	}

	//
	function woocommerce_serial_key_details_meta_box() {
		global $wpdb, $post;

		$js = "
				function handle_edit_serial_key( element ) {
					var source_element = element.attr('id');
					var target_element = source_element.replace( \"edit_serial_key_\", \"serial_key_\" );
					var target_message_element = target_element.replace( \"serial_key_\", \"message_row_\" );
					if ( jQuery('#'+target_element).is('[disabled]') ) {
						jQuery('#'+source_element).removeClass('dashicons-edit').addClass('dashicons-update');
						jQuery('#'+target_element).removeAttr('disabled');
						jQuery('#'+target_message_element+' td:nth-child(2)').find('label').html( '' );
					} else {
						jQuery.ajax({
							url: '" . admin_url( 'admin-ajax.php' ) . "',
							dataType: 'json',
							type: 'post',
							data: {
								action: 'update_serial_key',
								security: '" . wp_create_nonce( 'sa_serial_key_ajax' ) . "',
								target_element: target_element,
								serial_key: jQuery('#'+target_element).val()
							},
							success: function ( response ) {
								if ( response != '' && response != undefined ) {
									if ( response.success == 'yes' ) {
										jQuery('#'+source_element).removeClass('dashicons-update').addClass('dashicons-edit');
										jQuery('#'+target_element).prop('disabled', true);
									} else {
										jQuery('#'+source_element).removeClass('dashicons-edit').addClass('dashicons-update');
									}
									jQuery('#'+target_message_element+' td:nth-child(2)').find('label').html( '' );
									jQuery('#'+target_message_element+' td:nth-child(2)').find('label').html( response.message );
									jQuery('#'+target_message_element).show();
								}
							}
						});
					}
				}

			   jQuery('#sa_serial_key').find('[id^=\"edit_serial_key_\"]').on('click', function(){
					handle_edit_serial_key( jQuery(this) );
				});

				jQuery('a[id^=\"generate_serial_key_\"]').on('click', function( e ){
					e.preventDefault();
					var target_item = jQuery(this).attr('id');
					jQuery.ajax({
						url: '" . admin_url( 'admin-ajax.php' ) . "',
						dataType: 'html',
						type: 'post',
						data: {
							action: 'generate_serial_key',
							security: '" . wp_create_nonce( 'sa_serial_key_ajax' ) . "',
							target_item: target_item
						},
						success: function ( response ) {
							if ( response != '' && response != undefined ) {
								jQuery('div#woocommerce-serial-key-details').find('table#sa_serial_key').parent().html( response );
								jQuery('div#woocommerce-serial-key-details').find('table#sa_serial_key').find('[id^=\"edit_serial_key_\"]').bind('click', function(){ handle_edit_serial_key( jQuery(this) ) });
							}
						}
					});
				});
				";

		wc_enqueue_js( $js );

		$this->serial_key_meta_box_content( $post->ID );

	}

	function serial_key_meta_box_content( $order_id = 0 ) {

		if ( empty( $order_id ) ) return;

		$order = wc_get_order( $order_id );
		$order_items = $order->get_items();

		if ( $this->is_wc_gte_30() ) {
			$order_id = ( ! empty( $order ) && is_callable( array( $order, 'get_id' ) ) ) ? $order->get_id() : 0;
		} else {
			$order_id = ( ! empty( $order->id ) ) ? $order->id : 0;
		}

		$serial_keys = $this->get_products_serial_keys( array( $order_id ) );

		if ( isset( $order_items ) && count( $order_items ) > 0 ) {
			?>
				<table id="sa_serial_key" cellspacing="5px">
			<?php
			foreach ( $order_items as $item ) {
				$is_serial_key_generated = true;
				$item_id = ( isset( $item['variation_id'] ) && $item['variation_id'] > 0 ) ? $item['variation_id'] : $item['product_id'];
				$is_serial_key_enabled = get_post_meta( $item_id, '_serial_key', true );
				if ( $is_serial_key_enabled != 'yes' ) continue;
				$is_downloadable = get_post_meta( $item_id, '_downloadable', true );
				if ( $is_downloadable != 'yes' ) {
					continue;
				}
				if ( !isset( $serial_keys[$item_id] ) || empty( $serial_keys[$item_id] ) ) {
					$is_serial_key_generated = false;
				}
				$variation_data = array();
				foreach ( $item['item_meta'] as $item_meta_key => $item_meta_value ) {
					if ( strpos( $item_meta_key, 'pa_' ) !== false ) {
						$variation_data['attribute_'.$item_meta_key] = ( isset( $item_meta_value[0] ) && !empty( $item_meta_value[0] ) ) ? $item_meta_value[0] : '';
					}
				}
				$variation = wc_get_formatted_variation( $variation_data, true );
				if ( !empty( $variation ) ) {
					$variation = ' (' . $variation . ')';
				}
			?>
				<tr id="table_row_<?php echo $order_id . '_' . $item_id; ?>">
					<td><label for="serial_key_<?php echo $order_id . '_' . $item_id; ?>"><strong><?php echo $item['name'] . $variation; ?></strong></label></td>
					<td>
						<?php if ( $is_serial_key_generated ) { ?>
						<input size="50" type="text" id="serial_key_<?php echo $order_id . '_' . $item_id; ?>" name="serial_key_<?php echo $order_id . '_' . $item_id; ?>" value="<?php echo $serial_keys[$item_id]; ?>" disabled />
						<?php } else { ?>
						<small><i><label><?php echo '<span class="dashicons dashicons-info" style="color:#edbb00"></span>&nbsp;' .  __( 'No Serial Key Found', self::$text_domain ); ?></label></i></small>&nbsp;
						<a id="generate_serial_key_<?php echo $order_id . '_' . $item_id; ?>" href=""><?php echo __( 'Generate', self::$text_domain ); ?></a>
						<?php } ?>
					</td>
					<td>
						<?php if ( $is_serial_key_generated ) { ?>
						<span id="edit_serial_key_<?php echo $order_id . '_' . $item_id; ?>" class="dashicons dashicons-edit" style="cursor: pointer;"></span>
						<?php } ?>
					</td>
				</tr>
				<tr id="message_row_<?php echo $order_id . '_' . $item_id; ?>" style="display: none;">
					<td></td>
					<td>
						<small><label></label></small>
					</td>
					<td></td>
				</tr>
			<?php
			}
			?>
				</table>   
			<?php

		}

	}

	function get_products_serial_keys( $order_ids = array(), $user_ids = array() ) {
		
		if ( empty( $order_ids ) && empty( $user_ids ) ) {
			return false;
		}

		global $wpdb;

		$user_order_ids = array();

		if ( !empty( $user_ids ) ) {

			$user_order_ids_query = "SELECT postmeta.post_id FROM {$wpdb->prefix}postmeta AS postmeta 
											LEFT JOIN {$wpdb->prefix}woocommerce_serial_key AS woocommerce_serial_key
												ON ( woocommerce_serial_key.order_id = postmeta.post_id )
											WHERE postmeta.meta_key LIKE '_customer_user'
											AND postmeta.meta_value IN ( " . implode( ',', $user_ids ) . " )";

			$user_order_ids = $wpdb->get_col( $user_order_ids_query );

		}

		$new_order_ids = array_unique( array_merge( $user_order_ids, $order_ids ) );

		if ( empty( $new_order_ids ) ) {
			return false;
		}

		$keys_details = $wpdb->get_results("SELECT product_id, serial_key FROM {$wpdb->prefix}woocommerce_serial_key WHERE order_id IN ( " . implode( ',', $new_order_ids ) . ")", 'ARRAY_A');
		$serial_keys = array();
		foreach ( $keys_details as $keys_detail ) {
			$serial_keys[$keys_detail['product_id']] = $keys_detail['serial_key'];
		}

		return $serial_keys;

	}

	function enqueue_serial_key_admin_js() {

		$js= "var showHideMetaField = function( element ){
					if ( element.find('input[name^=\"_serial_key\"]').is(':checked') ) {
						element.next('tr, .serial_key_meta_fields').show();
					} else {
						element.next('tr, .serial_key_meta_fields').hide();
					}
			  };
			  showHideMetaField( jQuery('input[name^=\"_serial_key\"]').closest('tr, p') );

			  jQuery('input[name^=\"_serial_key\"]').on('change', function(){
					setTimeout( showHideMetaField( jQuery(this).closest('tr, p') ), 10 );
			  });
			";

		wc_enqueue_js( $js );
	}
	
	// Function to show checkbox to generate serial key for simple product
	function woocommerce_product_options_serial_key_simple() {

		global $post;

		$this->enqueue_serial_key_admin_js();
		
		woocommerce_wp_checkbox( array( 'id' => '_serial_key', 'wrapper_class' => 'show_if_simple', 'label' => __('Generate Serial Key', self::$text_domain), 'description' => __('Enable this option to generate a serial key for the product.', self::$text_domain) ) );

		echo '<div class="serial_key_meta_fields">';

		woocommerce_wp_text_input( array( 'id' => 'serial_key_limit', 'label' => __( 'Serial Key Usage Limit.', self::$text_domain ) . ' <a target="_blank" href="' . admin_url( 'admin.php?page=woocommerce_serial_key' ) . '">' . __( 'How to track usage', self::$text_domain ) . '</a>', 'placeholder' => __( 'Unlimited', self::$text_domain ), 'description' => __( 'Leave blank for unlimited usage.', self::$text_domain ), 'type' => 'number', 'custom_attributes' => array(
			'step'  => '1',
			'min'   => '0'
		) ) );

		woocommerce_wp_checkbox( array( 'id' => 'is_update_serial_key_limit', 'wrapper_class' => 'show_if_simple', 'label' => __('Update limit for previous serial keys?', self::$text_domain), 'description' => __('Check to update serial key usage limit for previous orders.', self::$text_domain) ) );

		?>
		<p class="form-field import_serial_key_field show_if_simple">
			<label for="import_serial_key_field"><?php echo __( 'Import Serial Keys' ); ?></label>
			<?php echo sprintf( __( '%s', self::$text_domain ), '<a target="_blank" href="' . admin_url( add_query_arg( array( 'page' => 'woocommerce_serial_key', 'tab' => 'import', 'product_id' => $post->ID ), 'admin.php' ) ) . '">' . __( 'Import Serial Keys Using CSV File', self::$text_domain ) . '</a>' ); ?>
			<span class="description"><?php echo __( 'Imported serial keys will be used instead of automatically generated serial keys', self::$text_domain ); ?></span>
		</p>
		<?php

		echo '</div>';
		
	}
	
	// Function to show checkbox to generate serial key for variable product
	function woocommerce_product_options_serial_key_variable( $loop, $variation_data, $variation ) {

		$this->enqueue_serial_key_admin_js();
		
			$variation_id = $variation->ID;

			?>
			
			<div class="show_if_variation_downloadable">
				<p class="form-row form-row-first">
					<label>
						<?php $generate = get_post_meta($variation->ID, '_serial_key',true); 
						?>
						<input type="checkbox" class="checkbox" <?php echo ($generate == 'yes') ? "checked='checked'" : ""; ?>  name="_serial_key[<?php echo $loop; ?>]">
						<?php echo __('Generate Serial Key', self::$text_domain); ?> <a class="tips" data-tip="<?php _e('Enable this option to generate serial key for the product', self::$text_domain); ?>" href="#">[?]</a>
					</label>
				</p>
				<p class="form-row form-row-last">
					<label>
						<input type="checkbox" class="checkbox" name="is_update_serial_key_limit[<?php echo $loop; ?>]" /> 
						<?php echo __('Update limit for previous serial keys?', self::$text_domain); ?> <a class="tips" data-tip="<?php _e('Check to update serial key usage limit for previous orders', self::$text_domain); ?>" href="#">[?]</a>
					</label>
				</p>
				<p class="form-row form-row-first">
					<label>
						<?php $serial_key_limit = get_post_meta($variation->ID,'serial_key_limit',true); ?>
						<?php echo __('Serial Key Usage Limit', self::$text_domain) . ' <a target="_blank" href="' . admin_url( 'admin.php?page=woocommerce_serial_key' ) . '">' . __( 'How to track usage', self::$text_domain ) . '</a>'; ?> <a class="tips" data-tip="<?php _e('Leave blank for unlimited usage', self::$text_domain); ?>" href="#">[?]</a>
						<input type="number" placeholder="<?php echo __( 'Unlimited', self::$text_domain ); ?>" step="1" min="0" name="serial_key_limit[<?php echo $loop; ?>]" value="<?php echo (isset($serial_key_limit) ? $serial_key_limit : ''); ?>" />
					</label>
				</p>
				<p class="form-row form-row-last">
					<label>
						<?php echo sprintf( __( '%s', self::$text_domain ), '<a href="' . admin_url( add_query_arg( array( 'page' => 'woocommerce_serial_key', 'tab' => 'import', 'product_id' => $variation_id ), 'admin.php' ) ) . '">' . __( 'Import Serial Keys Using CSV File', self::$text_domain ) . '</a>' ); ?>
						<a class="tips" data-tip="<?php echo __( 'Imported serial keys will be used instead of automatically generated serial keys', self::$text_domain ); ?>" href="#">[?]</a>
					</label>
				</p>
			</div>
		
		<?php
	}
	
	function woocommerce_generate_serial_key( $order_id, $product_ids = array() ) {

		global $wpdb;
		
		$order = wc_get_order( $order_id );
		$order_items = (array) $order->get_items();

		if ( empty( $order_items ) ) return;

		foreach( $order_items as $item_id => $item ) { 
			
			$product = $order->get_product_from_item( $item );
			
			if ( $this->is_wc_gte_30() ) {
				$product_id = ( ! empty( $product ) && is_callable( array( $product, 'get_id' ) ) ) ? $product->get_id() : 0;
			} else {
				if ( $product instanceof WC_Product_Variation ) {
					$product_id = $product->variation_id;
				} else {
					$product_id = $product->id;
				}
			}

			if ( ! empty( $product_ids ) && ! in_array( $product_id, $product_ids ) ) {
				continue;
			}
			
			if ( $product->is_downloadable() && get_post_meta( $product_id, '_serial_key', true ) == 'yes' ) {

				$serial_key_query = "SELECT order_id, 
											serial_key, 
											COUNT( CASE 
														WHEN order_id = 0 AND product_id = 0 THEN 1
												 END ) AS remaining_global,
											COUNT( CASE 
														WHEN order_id = 0 AND product_id = {$product_id} THEN 1
												 END ) AS remaining_product_wise
										FROM {$wpdb->prefix}woocommerce_serial_key 
										WHERE ( order_id = {$order_id} AND product_id = {$product_id} ) 
											OR ( order_id = 0 AND ( product_id = {$product_id} OR product_id = 0 ) ) 
										GROUP BY order_id, product_id
										ORDER BY order_id DESC, product_id DESC
										LIMIT 1";

				$serial_key_results = $wpdb->get_results( $serial_key_query, 'ARRAY_A' );

				$found_order_id = ( isset( $serial_key_results[0]['order_id'] ) ) ? $serial_key_results[0]['order_id'] : null;
				$serial_key = ( ! empty( $serial_key_results[0]['serial_key'] ) ) ? $serial_key_results[0]['serial_key'] : null;
				$remaining_global_serial_keys = ( ! empty( $serial_key_results[0]['remaining_global'] ) ) ? $serial_key_results[0]['remaining_global'] : null;
				$remaining_product_wise_serial_keys = ( ! empty( $serial_key_results[0]['remaining_product_wise'] ) ) ? $serial_key_results[0]['remaining_product_wise'] : null;

				$remaining_serial_keys = array(
												'global' => $remaining_global_serial_keys,
												'product_wise' => array( 
																		'product_id' => $product_id,
																		'remaining' => $remaining_product_wise_serial_keys
																	)
											);

				if ( ! empty( $serial_key ) && $found_order_id > 0 ) return;

				$expiry     = trim( get_post_meta( $product_id, '_download_expiry', true ) );
				$expiry     = ( ! empty( $expiry ) && $expiry > 0 ) ? absint( $expiry ) : null;

				if ( ! empty( $expiry ) ) {
					if ( $this->is_wc_gte_30() ) {
						$_order_completed_date = $order->get_date_completed() ? gmdate( 'Y-m-d H:i:s', $order->get_date_completed()->getOffsetTimestamp() ) : '';
					} else {
						$_order_completed_date = ( ! empty( $order->completed_date ) ) ? $order->completed_date : date( "Y-m-d H:i:s" );
					}
					$order_completed_date = date( "Y-m-d H:i:s", strtotime( $_order_completed_date ) );
					$expiry = date( "Y-m-d H:i:s", strtotime( $order_completed_date . ' + ' . $expiry . ' DAY' ) );
				}

				$limit = get_post_meta( $product_id, 'serial_key_limit', true );
				$limit = ( !empty( $limit ) ) ? $limit : NULL;

				if ( empty ( $serial_key ) ) {

					$serial_key = apply_filters( 'sa_wcsk_before_generate_serial_key', $serial_key, $item_id, $item, $order, $product );

					if ( empty ( $serial_key ) ) {
						$serial_key = $this->generate_serial_key();
						$uuid = array();
					} else {
						$uuid = apply_filters( 'sa_wcsk_previous_uuids', array(), $serial_key );
					}

					$uuid = maybe_serialize( $uuid );

					$query = "INSERT INTO {$wpdb->prefix}woocommerce_serial_key ( `order_id`, `product_id`, `serial_key`, `valid_till`, `limit`, `uuid` ) VALUES ( $order_id, $product_id, '$serial_key', '$expiry', '$limit', '$uuid' )";

					$wpdb->query( $query );

				} elseif ( $found_order_id == 0 ) {

					$query = "UPDATE {$wpdb->prefix}woocommerce_serial_key SET `order_id` = '$order_id', `product_id` = '$product_id', `valid_till` = '$expiry', `limit` = '$limit' WHERE `serial_key` = '$serial_key'";

					$wpdb->query( $query );

					$this->email_about_remaining_serial_keys( $remaining_serial_keys );

				}

			}
			
		}

	}
	
	function email_about_remaining_serial_keys( $remaining_serial_keys = false ) {

		if ( ! $remaining_serial_keys ) return;

		$count_for_notification = get_option( 'serial_key_count_for_notification', 20 );
		$count_for_last_notification = round( $count_for_notification / 2 );

		$count = ( ! empty( $remaining_serial_keys['global'] ) ) ? $remaining_serial_keys['global'] : 0;
		$product_name = '';
		if ( ! empty( $remaining_serial_keys['product_wise']['remaining'] ) ) {
			$count = $remaining_serial_keys['product_wise']['remaining'];
		}

		if ( $count != $count_for_notification && $count != $count_for_last_notification ) return;

		$product = ( ! empty( $remaining_serial_keys['product_wise']['product_id'] ) ) ? wc_get_product( $remaining_serial_keys['product_wise']['product_id'] ) : null;
		$product_name = ( ! empty( $product ) ) ? $product->get_formatted_name() : '';

		$site_title = get_option( 'blogname' );

		if ( $count == $count_for_last_notification ) {
			$last_reminder_text = __( 'Last Reminder: ', self::$text_domain );
		} else {
			$last_reminder_text = '';
		}

		$subject_string = sprintf(__( '[%s] %sYou are running low in Serial Keys%s!', self::$text_domain ), $site_title, $last_reminder_text, __( ' for ', self::$text_domain ) . $product_name );

		$subject = $subject_string;

		$email_heading  =  sprintf(__( 'You have only %s Serial Keys left%s!', self::$text_domain ), $count, __( ' for ', self::$text_domain ) . $product_name );

		$admin_email = get_option( 'admin_email' );
		$notification_email = get_option( 'serial_key_notification_email', $admin_email );

		ob_start();

		include( apply_filters( 'serial_key_notification_for_count_email_template', 'templates/email-notification-for-count.php' ) );

		$message = ob_get_clean();

		wc_mail( $notification_email, $subject, $message );
	}

	function get_previous_uuids( $uuid = array(), $serial_key = null ) {

		if ( ! empty( $serial_key ) ) {
			
			global $wpdb;

			$query = "SELECT `uuid` FROM {$wpdb->prefix}woocommerce_serial_key WHERE `serial_key` = '$serial_key'";
			$result = $wpdb->get_col( $query );

			if ( ! empty( $result ) ) {
				foreach ( $result as $col ) {
					if ( ! empty( $col ) ) {
						$old_uuids = maybe_unserialize( $col );
						if ( ! empty( $old_uuids ) ) {
							$uuid = array_merge( $uuid, $old_uuids );
						}
					}
				}
			}

		}

		return $uuid;

	}

	public function rest_api_init() {

		$is_ajax = defined( 'DOING_AJAX' ) && DOING_AJAX;

		if ( $is_ajax ) {
			return;
		}

		global $wp_version;

		// REST API was included starting WordPress 4.4.
		if ( version_compare( $wp_version, 4.4, '<' ) ) {
			return;
		}

		add_action( 'rest_api_init', array( $this, 'register_routes' ) );

		global $wp_rest_server;

		if ( empty( $wp_rest_server ) && function_exists( 'rest_get_server' ) ) {
			$wp_rest_server = rest_get_server();
		}

    }

    public function register_routes() {
		include_once 'class-wcsk-rest-controller.php';
        $rest_controller = new WCSK_REST_Controller();
        $rest_controller->register_routes();
    }

	// Function to generate serial key on order completion, but only when the serial key for that order & that product is not available
	function generate_serial_key_from_order( $order_id ) {

		$this->woocommerce_generate_serial_key( $order_id, array() );

	}
	
	// Function for generating random serial key
	function generate_serial_key() {

		$chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
		$len = strlen( $chars );
		$full_serial = '';
		
		for ( $i = 0; $i < 6; $i++ ) {
			$part_serial = '';
			for ( $j = 0; $j < 6; $j++ ) {
				$random = $chars[ rand( 0, ( $len - 1 ) ) ];
				$part_serial .= $random;
			}

			if ( empty( $full_serial ) ) {  
				$full_serial .= $part_serial;
			} else {
				$full_serial .= "-". $part_serial;  
			}                   
		}
		
		return $full_serial;
	}
	
	// Function for saving checkbox value for generating serial key for simple product
	function woocommerce_process_product_meta_serial_simple( $post_id ) {
		
		if ( isset( $_POST['_serial_key'] ) ) {
			update_post_meta( $post_id, '_serial_key', 'yes' );
		} else {
			update_post_meta( $post_id, '_serial_key', 'no' );
		}

		if ( isset( $_POST['serial_key_limit'] ) ) {
			update_post_meta( $post_id, 'serial_key_limit', $_POST['serial_key_limit'] );
		}

		if ( isset( $_POST['is_update_serial_key_limit'] ) ) {
			$this->sync_serial_key_usage( array( $post_id ) );
		}
		
	}

	// Function for saving checkbox value for generating serial key for variable product in WC 2.4
	function woocommerce_process_product_meta_save_single_variable_serial( $variation_id, $i ) {

		if( empty( $variation_id ) ) return;

		if ( ! empty ( $_POST['variable_post_id'][$i] ) ) {
			if ( ! empty ( $_POST['variable_is_downloadable'][$i] ) && ! empty ( $_POST['_serial_key'][$i] ) ) {
				update_post_meta( $variation_id, '_serial_key', 'yes' );
			} else {
				update_post_meta( $variation_id, '_serial_key', 'no' );
			}

			if ( ! empty ( $_POST['variable_is_downloadable'][$i] ) && ! empty ( $_POST['serial_key_limit'][$i] ) ) {
				update_post_meta( $variation_id, 'serial_key_limit', $_POST['serial_key_limit'][$i] );
			}

			if ( ! empty ( $_POST['variable_is_downloadable'][$i] ) && ! empty ( $_POST['is_update_serial_key_limit'][$i] ) ) {
				$this->sync_serial_key_usage( array( $variation_id ) );
			}
		}

	}

	// Function for saving checkbox value for generating serial key for variable product in WC 2.3
	function woocommerce_process_product_meta_variable_serial( $post_id ) {

		if ( empty( $_POST['variable_post_id'] ) ) return;

		$max_loop = max( array_keys( $_POST['variable_post_id'] ) );

		for ( $i = 0; $i <= $max_loop; $i++ ) {
			if ( isset( $_POST['variable_post_id'][$i] ) ) {
				if ( isset( $_POST['variable_is_downloadable'][$i] ) && isset( $_POST['_serial_key'][$i] ) ) {
					update_post_meta( $_POST['variable_post_id'][$i], '_serial_key', 'yes' );
				} else {
					update_post_meta( $_POST['variable_post_id'][$i], '_serial_key', 'no' );
				}

				if ( isset( $_POST['variable_is_downloadable'][$i] ) && isset( $_POST['serial_key_limit'][$i] ) ) {
					update_post_meta( $_POST['variable_post_id'][$i], 'serial_key_limit', $_POST['serial_key_limit'][$i] );
				}

				if ( isset( $_POST['variable_is_downloadable'][$i] ) && isset( $_POST['is_update_serial_key_limit'][$i] ) ) {
					$this->sync_serial_key_usage( array( $_POST['variable_post_id'][$i] ) );
				}
			}                   
		}               
	}

	function woocommerce_settings_serial_key_tab( $tabs ) {

		$tabs['serial_key'] = __( 'Serial Key', self::$text_domain );
		return $tabs;

	}

	function woocommerce_serial_key_page_content() {

		if( !empty($_GET['landing-page']) ) {
			$GLOBALS['sa_sk_admin_welcome']->show_welcome_page();
		} else {
			if( !empty( $_GET['tab'] ) ) {
				if ( $_GET['tab'] == 'dashboard' ) {
					$tab = 'dashboard';
				} elseif ( $_GET['tab'] == 'import' ) {
					$tab = 'import';
				} else {
					$tab = 'validation';
				}
			} else {
				$tab = 'dashboard';
			}

			?>

			<div class="wrap woocommerce">
				<h2>
					<?php echo __( 'WooCommerce Serial Key', self::$text_domain ); ?>
				</h2>
				<div class="about_serial_key" align="right">
					<a href="<?php echo admin_url('admin.php?page=woocommerce_serial_key&landing-page=sk-about'); ?>" class="about_serial_key" target="_blank"><?php echo __('About Serial Key', self::$text_domain); ?></a>
				</div>
				<div id="woocommerce_serial_key_tabs">
					<h2 class="nav-tab-wrapper">
						<a href="<?php echo admin_url('admin.php?page=woocommerce_serial_key&tab=dashboard') ?>" class="nav-tab <?php echo ($tab == 'dashboard') ? 'nav-tab-active' : ''; ?>"><?php echo sprintf(__('Dashboard %s', self::$text_domain), '<span style="color: red; font-style: italic; vertical-align: top; font-size: x-small;">(' . __( 'beta', self::$text_domain ) . ')</span>'); ?></a>
						<a href="<?php echo admin_url('admin.php?page=woocommerce_serial_key&tab=validation') ?>" class="nav-tab <?php echo ($tab == 'validation') ? 'nav-tab-active' : ''; ?>"><?php _e('How Validation Works', self::$text_domain); ?></a>
						<a href="<?php echo admin_url('admin.php?page=woocommerce_serial_key&tab=import') ?>" class="nav-tab <?php echo ($tab == 'import') ? 'nav-tab-active' : ''; ?>"><?php _e('Import Serial Keys', self::$text_domain); ?></a>
					</h2>
				</div>
				<?php
					switch ( $tab ) {
						case "import" :
							$this->admin_serial_key_import_page();
							break;
						case "validation" :
							$this->woocommerce_serial_keys_validation();
							break;
						default :
							SK_Admin_Dashboard::serial_key_dashboard_page();
							break;
					}
				?>

			</div>
			<?php
		}
	}

	function woocommerce_serial_keys_validation() {
		global $wpdb;

		$sku = $wpdb->get_var("SELECT postmeta.meta_value
								FROM {$wpdb->prefix}postmeta AS postmeta
								WHERE postmeta.meta_key LIKE '_sku'
								AND postmeta.post_id IN ( SELECT product_id FROM {$wpdb->prefix}woocommerce_serial_key )
								ORDER BY postmeta.meta_id DESC
								LIMIT 1");

		if ( ! empty( $_POST['save_serial_key_settings'] ) ) {
			if ( isset( $_POST['uuid_display_name'] ) ) {
				update_option( 'serial_key_uuid_display_name', $_POST['uuid_display_name'] );
			}
			if ( isset( $_POST['is_sync_serial_key_usage'] ) ) {
				$this->sync_serial_key_usage( true );
			}
		}
		?>
		<script type="text/javascript">
			jQuery(function(){
				jQuery('input#validate_serial_key_send').on('click', function(){
					jQuery.ajax({
						url: '<?php echo home_url("/") . "?wc-api=validate_serial_key"; ?>',
						type: 'post',
						dataType: 'json',
						data: {
							serial: jQuery('input#sa_serial_key').val(),
							uuid: jQuery('input#sa_serial_key_uuid').val(),
							sku: jQuery('input#sa_serial_key_sku').val()
						},
						success: function( response ) {
							jQuery('textarea#validate_serial_key_response').text( JSON.stringify(response) );
						}
					});
				});
			});
		</script>
		<form action="" method="post">
		<h3><?php echo __( 'Settings', self::$text_domain ); ?></h3>
		<table class="form-table">
			<tbody>
				<tr valign="top">
					<th scope="row">
						<label for="uuid_display_name"><?php echo __( 'Display name for UUID', self::$text_domain ); ?></label>
					</th>
					<td>
						<input id="uuid_display_name" type="text" name="uuid_display_name" value="<?php echo get_option( 'serial_key_uuid_display_name' ); ?>" /> <span class="description"><?php echo __( 'This will be displayed with UUIDs of your customers', self::$text_domain ); ?></span>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row">
						<label for="is_sync_serial_key_usage"><?php echo __( 'Sync serial key usage?', self::$text_domain ); ?></label>
					</th>
					<td>
						<input id="is_sync_serial_key_usage" type="checkbox" name="is_sync_serial_key_usage" /> <span class="description"><?php echo __( 'Check this to sync / update existing serial key usage with usage limit of respective products', self::$text_domain ); ?></span>
					</td>
				</tr>
			</tbody>
		</table>
		<br />
		<input type="submit" class="button button-primary" name="save_serial_key_settings" value="<?php echo __( 'Save changes', self::$text_domain ); ?>" />
		</form>
		<h3><?php echo __( 'Documentation', self::$text_domain ); ?></h3>
		<p>
			<?php 
				echo __( 'To validate generated key against a product, you can send a request to ', self::$text_domain ) . '<br><strong>' . add_query_arg( 'uuid', 'UUID', add_query_arg( 'sku', 'SKU', add_query_arg( 'serial', 'SERIAL_KEY', add_query_arg( 'wc-api', 'validate_serial_key', home_url('/') ) ) ) ) . '</strong>';
				echo __( '<br>You\'ll receive validation result in JSON format. JSON decode it to get response in array format & use it as per your requirement.', self::$text_domain );
			?>
		</p>
		<hr>
		<h4><?php _e( 'Live Demo Of Serial Key Validation', self::$text_domain ); ?></h4>
		<p>
			<?php
				echo home_url('/') . '?wc-api=validate_serial_key&serial=<input type="text" size="50" id="sa_serial_key" value=""/>&sku=<input type="text" id="sa_serial_key_sku" value=""/>&uuid=<input type="text" size="50" id="sa_serial_key_uuid" value=""/> &rarr; <input type="button" id="validate_serial_key_send" value="' . __( 'Send', self::$text_domain ) . '">';
			?>
		</p>
		<h4><?php _e( 'Response', self::$text_domain ); ?></h4>
		<textarea id="validate_serial_key_response" cols="100" rows="3"></textarea>
		<hr>
		<h4><?php _e( 'Example of HTML code to validate serial key', self::$text_domain ); ?></h4>
		<p><?php echo __( 'Embed this code in your product & don\'t forget to replace ', self::$text_domain ) . '<code>' . $sku . '</code>' . __( ' with SKU of the product in code', self::$text_domain ); ?></p>
		<style>
			table#serial_key_table {
				width: 100%; 
				table-layout: fixed;
			}
			table#serial_key_table th {
				padding: 10px 0;
			}
			table#serial_key_table td {
				padding: 0 15px;
				vertical-align: top;
			}
			table#serial_key_table,
			table#serial_key_table th,
			table#serial_key_table td {
				border-style: solid;
				border-width: 1px;
				border-color: lightgrey;
				border-spacing: 0;
			}
			table#serial_key_table td pre {
				overflow-x: scroll;
				margin-top: 0;
			}
			div.validate_serial_key {
				padding: 15px 0;
			}
		</style>
		<table id="serial_key_table" cellspacing="0">
			<tbody>
				<tr>
					<th><?php _e( 'Code', self::$text_domain ); ?></th>
					<th><?php _e( 'Output', self::$text_domain ); ?></th>
				</tr>
				<tr>
					<td>
						<pre>
							<?php $html_string = '
<div class="validate_serial_key">
	<script type="text/javascript">
		jQuery(function(){
			jQuery("input#validate").on("click", function(){
				jQuery.ajax({
					url: "' . home_url("/") . '?wc-api=validate_serial_key",
					type: "post",
					dataType: "json",
					data: {
						serial: jQuery("input#serial_key").val(),
						uuid: jQuery("input#serial_key_uuid").val(),
						sku: jQuery("input#sku").val()
					},
					success: function( response ) {
						jQuery("p#result").text(\'\');
						if ( response.success == "true" ) {
							jQuery("p#result").append( \'<p style="background: green; color: white">\'+response.message+\'.</p>\' );
						} else {
							jQuery("p#result").append( \'<p style="background: red; color: white">\'+response.message+\'.</p>\' );
						}
					}
				});
			});
		});
	</script>
	Serial Key: <input type="text" id="serial_key"><br />
	UUID: <input type="text" id="serial_key_uuid">
	<input type="button" id="validate" value="Validate">
	<input type="hidden" id="sku" value="'.$sku.'">
	<p id="result"></p>
</div>';

								echo esc_html( $html_string );
							?>
						</pre>
					</td>
					<td style="padding: 0 15px;">
						<div class="validate_serial_key">
							<script type="text/javascript">
								jQuery(function(){
									jQuery("input#validate").on("click", function(){
										jQuery.ajax({
											url: '<?php echo home_url("/"); ?>?wc-api=validate_serial_key',
											type: "post",
											dataType: "json",
											data: {
												serial: jQuery("input#serial_key").val(),
												uuid: jQuery("input#serial_key_uuid").val(),
												sku: jQuery("input#sku").val()
											},
											success: function( response ) {
												jQuery("p#result").text('');
												if ( response.success == 'true' ) {
													jQuery("p#result").append( '<p style="background: green; color: white">'+response.message+'.</p>' );
												} else {
													jQuery("p#result").append( '<p style="background: red; color: white">'+response.message+'.</p>' );
												}
											}
										});
									});
								});
							</script>
							Serial Key: <input type="text" id="serial_key" size="40"><br />
							UUID: <input type="text" id="serial_key_uuid" size="40">
							<input type="button" id="validate" value="Validate">
							<input type="hidden" id="sku" value="<?php echo $sku; ?>">
							<p id="result"></p>
						</div>
					</td>
				</tr>
			</tbody>
		</table>
		<?php
	}

	function find_special_characters( $serial_key_value ) {

		if ( false === strpbrk( $serial_key_value, "!@#$%^&*()+=[]{}|:;'<>.?~/" ) ) {
			return $serial_key_value;
		}

	}

	function admin_serial_key_import_page() {

		global $wpdb;

		if ( ! empty( $_POST['save_serial_key_notification_settings'] ) ) {
			if ( ! empty( $_POST['serial_key_count'] ) ) {
				update_option( 'serial_key_count_for_notification', $_POST['serial_key_count'] );
			} else {
				delete_option( 'serial_key_count_for_notification' );
			}
		}

		?>
		<div class="import-serial-keys">

			<h3><?php echo __( 'Import Serial Keys Using CSV File', self::$text_domain ); ?></h3>
			<?php
				$action = 'admin.php?page=woocommerce_serial_key&tab=import';

				$product_id_query = '';
				if ( ! empty( $_GET['product_id'] ) ) {
					$action .= '&product_id=' . $_GET['product_id'];
					$product_id = $_GET['product_id'];
					$product_id_query = "`product_id`,";
					echo __( 'You are currently importing serial keys for the product - <b>'.get_the_title( $product_id ).' </b>.', SA_Serial_Key::$text_domain );
				}

				$upload_dir = wp_upload_dir();

				if ( ! empty( $upload_dir['error'] ) ) {
					?>
					<div class="error">
						<p><?php echo __( 'Before you can upload your import file, you will need to fix the following error:', self::$text_domain ); ?></p>
						<p><strong><?php echo $upload_dir['error']; ?></strong></p>
					</div><?php
				} else {
					?>
					<form enctype="multipart/form-data" id="serial-key-import-upload-form" method="post" action="<?php echo esc_attr(($action)); ?>">
						<table class="form-table">
							<tbody>
								<tr>
									<th>
										<label for="upload"><?php _e( 'Have CSV file?', self::$text_domain ); ?></label>
									</th>
									<td>
										<input type="file" id="serial_key_import_file" name="serial_key_import_file" size="25" accept=".csv" />
										<input type="hidden" name="action" value="save" />
										<input type="submit" class="button button-primary" value="<?php esc_attr_e( 'Import', self::$text_domain ); ?>" />
										<?php
											echo '<br>';
											wp_nonce_field( 'import_serial_key' );
											if ( ! empty( $_REQUEST['_wpnonce'] ) && check_admin_referer( 'import_serial_key' ) && ! empty ( $_FILES ) ) {

												$file = wp_upload_bits( $_FILES['serial_key_import_file']['name'], null, file_get_contents( $_FILES['serial_key_import_file']['tmp_name'] ) );
												$path = $file['file'];

												$mimes = array('application/vnd.ms-excel','text/plain','text/csv','text/tsv');

												$success_message = '<span class="dashicons dashicons-yes" style="color:#26a65b;"></span>';
												$fail_message = '<span class="dashicons dashicons-no-alt" style="color:#a00;"></span>';

												if ( empty( $file['file'] ) || $file['error'] !== false ) {
													echo $fail_message . __( 'Failed', self::$text_domain );
												}

												if ( in_array( $_FILES['serial_key_import_file']['type'], $mimes ) ) {

													if ( ( ! empty( $file['file'] ) ) && ( ! empty( $file['url'] ) ) && ( $file['error'] === false ) ) {

														@ini_set( 'auto_detect_line_endings', TRUE );
														$handle = @fopen( $path, 'r' );
														$file_data = array();
														while ( ( $data = @fgetcsv( $handle ) ) !== FALSE ) {
															$file_data = array_merge( $file_data, $data );
														}
														@ini_set( 'auto_detect_line_endings', FALSE );

														$unique_values = array_unique( $file_data );
														
														$duplicate_keys = array_diff_assoc( $file_data, $unique_values );

														$special_serial_keys = array_map( array( $this, 'find_special_characters' ), $unique_values );
														$final_serial_keys = array_filter( $special_serial_keys );
														$special_keys = array_diff_assoc( $unique_values, $final_serial_keys );

														$existing_serial_keys = $wpdb->get_col( "SELECT serial_key FROM {$wpdb->prefix}woocommerce_serial_key" );
														$final_serial_keys_to_import = array_values( array_diff( $final_serial_keys, $existing_serial_keys ) );
														$count_final_serial_keys_to_import = count( $final_serial_keys_to_import );

														$duplicate_keys_from_db = array_diff( $final_serial_keys, $final_serial_keys_to_import );                                                    

														$max_allowed_packet = $wpdb->get_row( "SHOW VARIABLES LIKE 'max_allowed_packet';", 'ARRAY_A' );
														$max_len = (int)$max_allowed_packet['Value'];
														$max_len = round( $max_len, -3 );

														$query_start = "INSERT INTO {$wpdb->prefix}woocommerce_serial_key ( {$product_id_query} `serial_key` ) VALUES ";
														$main_query = $temp_query = $query_values = '';
														$temp_query = $query_start;
														$run_temp_query = false;

														foreach ( $final_serial_keys_to_import as $value ) {
															$main_query = $temp_query;
															$query_values = '';
															$query_values .= "('";
															if ( ! empty( $product_id ) ) {
																$query_values .= $product_id . "','";
															}
															$query_values .= $value;
															$query_values .= "'),";
															$temp_query .= $query_values;
															$run_temp_query = true;
															if ( strlen( $temp_query ) > $max_len ) {
																$wpdb->query( trim( $main_query, "," ) );
																$temp_query = $query_start . $query_values;
																$run_temp_query = true;
															}
														}
														if ( $run_temp_query ) {
															$wpdb->query( trim( $temp_query, "," ) );
														}

														if ( ( ! empty ( $duplicate_keys ) ) || ( ! empty( $special_keys ) ) || ( ! empty( $duplicate_keys_from_db ) ) ) {
															
															$rejected_serial_keys       = array_merge( $duplicate_keys, $special_keys, $duplicate_keys_from_db );
															$count_rejected_serial_keys = count($rejected_serial_keys);

															$nested_serial_keys = array( 
																							'duplicate_keys' => $duplicate_keys, 
																							'special_keys' => $special_keys, 
																							'duplicate_keys_from_db' => $duplicate_keys_from_db
																						);

															echo '<span class="dashicons dashicons-info" style="color:#edbb00;"></span>' . sprintf(__( '%s Imported, %s Skipped', self::$text_domain ), $count_final_serial_keys_to_import, $count_rejected_serial_keys );
															echo '<br><br>';
															echo __( 'Following keys were skipped with reasons: ', self::$text_domain );
															echo '<br><br>';
															
															?>
															<style type="text/css">
																.serial-key-warnings-table .wsk_center {
																	text-align: center;
																}
																.serial-key-warnings-table .wsk_long {
																	width: 50%;
																}
																.serial-key-warnings-table .wsk_short {
																	width: 15%;
																}
																.serial-key-warnings-table th.serial_keys {
																	padding-left: 10px;
																}
															</style>
															<table class="serial-key-warnings-table wp-list-table widefat striped">
																<thead>
																	<tr class="row">
																		<th class="serial_keys wsk_long">
																			<?php echo __( 'Serial Keys', self::$text_domain ); ?>
																		</th>
																		<th class="duplicate_serial_keys wsk_center wsk_short">
																			<?php echo __( 'Duplicate in File', self::$text_domain ); ?>
																		</th>
																		<th class="special_character_serial_keys wsk_center wsk_short">
																			<?php echo __( 'Special Characters', self::$text_domain ); ?>
																		</th>
																		<th class="duplicate_keys_from_db wsk_center wsk_short">
																			<?php echo __( 'Already Used', self::$text_domain ); ?>
																		</th>
																	</tr>
																</thead>
																<tbody>
																	<?php foreach ( $rejected_serial_keys as $rejected_serial_key ) { ?>
																	<tr class="row">
																		<td class="serial_keys wsk_long">
																			<?php echo '<code>' . $rejected_serial_key . '</code>'; ?>
																		</td>
																		<td class="duplicate_serial_keys wsk_center wsk_short">
																			<?php
																				if ( in_array( $rejected_serial_key, $nested_serial_keys['duplicate_keys'] ) ) {
																					echo $success_message;
																				} else {
																					echo $fail_message;
																				}
																			?>
																		</td>
																		<td class="special_character_serial_keys wsk_center wsk_short">
																			<?php
																				if ( in_array( $rejected_serial_key, $nested_serial_keys['special_keys'] ) ) {
																					echo $success_message;
																				} else {
																					echo $fail_message;
																				}
																			?>
																		</td>
																		<td class="duplicate_keys_from_db wsk_center wsk_short">
																			<?php
																				if ( in_array( $rejected_serial_key, $nested_serial_keys['duplicate_keys_from_db'] ) ) {
																					echo $success_message;
																				} else {
																					echo $fail_message;
																				}
																			?>
																		</td>
																	</tr>
																	<?php } ?>
																</tbody>
															</table>
															<?php
														} else {
															echo $success_message . __( 'Successfully imported!', self::$text_domain );
														}
													}
												} else {
													echo $fail_message . __( 'You have not uploaded a CSV file.', self::$text_domain );
												}
												if ( ! empty( $path ) ) {
													unlink( $path );
												}
											}
										?>
									</td>
								</tr>
							</tbody>
						</table>
						<br />
					</form>
					<hr>
					<!-- Making both forms separate to avoid confusion amongst users (& to solve import errors) -->
					<form method="post">
						<table class="form-table">
							<tbody>
								<tr>
									<th>
										<label for="serial_key_count"><?php echo __( 'Notification', self::$text_domain ); ?></label>
									</th>
									<td>
										<?php
											$serial_key_count_for_notification = get_option( 'serial_key_count_for_notification', 20 );
										?>
										<div class="description"><?php echo __( 'Send e-mail notification when ', self::$text_domain ); ?>
											<input type="number" id="serial_key_count" name="serial_key_count" value="<?php echo $serial_key_count_for_notification; ?>" style="width:50px;" />
											<?php echo __( 'Serial Keys are remaining', self::$text_domain ); ?>
											&nbsp;&nbsp;&nbsp;&nbsp;
											<input type="submit" class="button button-primary" name="save_serial_key_notification_settings" value="<?php echo __( 'Save this', self::$text_domain ); ?>" />
										</div>
									</td>
								</tr>
							</tbody>
						</table>
					</form>
					<?php
				}
			?>
		</div>
		<?php
	}

	// Since WC 2.6 - add a new column for Serial Key in downloads table
	function add_new_serial_key_column_in_downloads( $array ) {

		$is_download_column = array_key_exists( 'download-file', $array );

		if ( ! $is_download_column ) {
			return $array;
		}
		
		$insert_at_index = 1;

		$sk_modified_array = array_merge( 
								array_slice( $array, 0, $insert_at_index ),
								array( 'serial-keys' => __( 'Serial Keys', self::$text_domain ) ),
								array_slice( $array, $insert_at_index, null )
		);

		return $sk_modified_array;

	}

	// Since WC 2.6 - display all available serial keys for products
	function sa_display_all_serial_keys_of_products( $download ) {

		global $wpdb;

		$product_id = $download['product_id'];
		$order_id = $download['order_id'];

		// WooCommerce Subscriptions Compatibility
		$active_plugins = (array) get_option('active_plugins', array());
		if (is_multisite()) {
			$active_plugins = array_merge($active_plugins, get_site_option('active_sitewide_plugins', array()));
		}

		if (( in_array('woocommerce-subscriptions/woocommerce-subscriptions.php', $active_plugins) || array_key_exists('woocommerce-subscriptions/woocommerce-subscriptions.php', $active_plugins) )) {

			$woo_subscriptions_version = WC_Subscriptions::$version;
			if ( class_exists( 'WC_Subscriptions' ) && !empty( $woo_subscriptions_version ) && $woo_subscriptions_version >= '2.0.0' ) {
				$subscription_parent_order_id = $wpdb->get_var( "SELECT post_parent FROM $wpdb->posts WHERE ID=$order_id" );
				if( !empty( $subscription_parent_order_id ) && $subscription_parent_order_id != 0 && $order_id != $subscription_parent_order_id ) {
					$order_id = $subscription_parent_order_id;
				}
			}
		}

		// Query to get order id from subscription id SELECT `post_parent` FROM `wp_26_posts` WHERE `ID` = 22783
		$serial_key_data = $wpdb->get_col( "SELECT serial_key FROM {$wpdb->prefix}woocommerce_serial_key WHERE order_id=$order_id AND product_id=$product_id" );

		if( !empty( $serial_key_data ) ) {
			foreach ($serial_key_data as $key => $value) {
				echo $value;
			}
		} else {
			echo "-";
		}

	}

	function display_serial_keys_of_products() {
		global $wpdb;

		if ( is_user_logged_in() ) {

			$user_id = get_current_user_id();

			$serial_keys = $this->get_products_serial_keys( array(), array( $user_id ) );

			if ( empty( $serial_keys ) ) return;

			$title = __( 'Serial Keys', self::$text_domain );

			require_once WP_PLUGIN_DIR . '/' . dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/templates/serial-keys-table.php';

		}

	}

	function display_serial_keys_after_order_table( $order ) {

		if ( $this->is_wc_gte_30() ) {
			$order_id = ( ! empty( $order ) && is_callable( array( $order, 'get_id' ) ) ) ? $order->get_id() : 0;
		} else {
			$order_id = ( ! empty( $order->id ) ) ? $order->id : 0;
		}

		$serial_keys = $this->get_products_serial_keys( array( $order_id ), array() );

		if ( empty( $serial_keys ) ) return;

		$title = __( 'Serial Keys', self::$text_domain );

		require_once WP_PLUGIN_DIR . '/' . dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/templates/serial-keys-table.php';

	}

	function manage_serial_key_usage_page_content() {
		global $woocommerce, $current_user, $wpdb;
		
		nocache_headers();
		
		if ( ! is_user_logged_in() ) {

			wc_get_template( 'myaccount/form-login.php' );

		} else {

			$user_id = get_current_user_id();
			$serial_key_details_query = "SELECT wsk.order_id, wsk.product_id, wsk.serial_key, wsk.uuid
											FROM {$wpdb->prefix}woocommerce_serial_key AS wsk
												LEFT JOIN {$wpdb->prefix}postmeta AS postmeta
													ON ( wsk.order_id = postmeta.post_id AND postmeta.meta_key LIKE '_customer_user' )
											WHERE postmeta.meta_value = '{$user_id}'";

			$serial_key_details_results = $wpdb->get_results( $serial_key_details_query, 'ARRAY_A' );
			$serial_key_usage = array();

			foreach ( $serial_key_details_results as $result ) {
				$order_id = $result['order_id'];
				$product_id = $result['product_id'];
				$serial_key = $result['serial_key'];
				$uuid = maybe_unserialize( $result['uuid'] );
				if ( !isset( $serial_key_usage[$order_id . '_' . $product_id] ) ) {
					$serial_key_usage[$order_id . '_' . $product_id] = array();
				}
				$serial_key_usage[$order_id . '_' . $product_id]['product_title'] = $this->get_product_title( $product_id );
				$serial_key_usage[$order_id . '_' . $product_id]['serial_key'] = $serial_key;
				$serial_key_usage[$order_id . '_' . $product_id]['uuid'] = $uuid;
			}

			include(apply_filters('serial_key_usage_template', ''));

		}
	}

	/**
	 * Find template for Manage Serial Key Usage Page
	 *
	 * @param string $template
	 * @return mixed $template
	 */
	function serial_key_usage_template_path( $template ) {

		$template_name  = 'serial-key-usage.php';

		return $this->locate_template_for_serial_key( $template_name, $template );

	}

	/**
	 * Find template for sending email for remaining Serial Key count
	 *
	 * @param string $template
	 * @return mixed $template
	 */
	public function serial_key_notification_for_count_email_template_path( $template ) {

		$template_name  = 'email-notification-for-count.php';

		return $this->locate_template_for_serial_key( $template_name, $template );

	}

	/**
	 * Locate template for Serial Key
	 *
	 * @param string $template_name
	 * @param mixed $template
	 * @return mixed $template
	 */
	public function locate_template_for_serial_key( $template_name = '', $template = '' ) {

		$default_path   = untrailingslashit( str_replace( 'includes', 'templates', plugin_dir_path( __FILE__ ) ) ) . '/';

		$plugin_base_dir = substr( plugin_basename( __FILE__ ), 0, strpos( plugin_basename( __FILE__ ), '/' ) + 1 );

		// Look within passed path within the theme - this is priority
		$template = locate_template(
			array(
				'woocommerce/' . $plugin_base_dir . $template_name,
				$plugin_base_dir . $template_name,
				$template_name
			)
		);

		// Get default template
		if ( ! $template )
			$template = $default_path . $template_name;

		// Return what we found
		return $template;

	}

	function sync_serial_key_usage( $all_or_product_ids = false ) {
		global $wpdb;

		if ( $all_or_product_ids === false ) return;

		$update_serial_key_usage_limit_query = "UPDATE {$wpdb->prefix}woocommerce_serial_key AS wsk
													LEFT JOIN {$wpdb->prefix}postmeta AS postmeta
														ON ( postmeta.post_id = wsk.product_id 
																AND postmeta.meta_key LIKE 'serial_key_limit' )
													SET wsk.limit = CAST( postmeta.meta_value AS UNSIGNED )";

		if ( is_array( $all_or_product_ids ) && !empty( $all_or_product_ids ) ) {
			$update_serial_key_usage_limit_query .= " WHERE wsk.product_id IN ( " . implode( ',', $all_or_product_ids ) . " )";
		}

		$wpdb->query( $update_serial_key_usage_limit_query );

		$current_serial_key_usage_query = "SELECT `order_id`, `product_id`, `limit`, `uuid`
												FROM {$wpdb->prefix}woocommerce_serial_key";

		if ( is_array( $all_or_product_ids ) && !empty( $all_or_product_ids ) ) {
			$current_serial_key_usage_query .= " WHERE product_id IN ( " . implode( ',', $all_or_product_ids ) . " )";
		}

		$current_serial_key_usage_results = $wpdb->get_results( $current_serial_key_usage_query, 'ARRAY_A' );

		foreach ( $current_serial_key_usage_results as $result ) {
			if ( empty( $result['uuid'] ) ) continue;
			$uuids = maybe_unserialize( $result['uuid'] );
			if ( count( $uuids ) > $result['limit'] ) {
				$uuids = array_slice( $uuids, 0, $result['limit'] );
				$wpdb->query( "UPDATE {$wpdb->prefix}woocommerce_serial_key SET uuid = '" . maybe_serialize( $uuids ) . "' WHERE order_id = {$result['order_id']} AND product_id = {$result['product_id']}" );
			}
		}

	}

	// Function to get formatted Product's Name
	public function get_product_title ( $product_id ) {
		
		$parent_id = wp_get_post_parent_id ( $product_id );
		$the_title = get_the_title( $product_id );
		
		if ( $parent_id > 0 ) {
			$product_title = get_the_title( $parent_id );
		} else {
			$product_title = $the_title;
		}

		$_product = wc_get_product( $product_id );
		
		if ( $_product instanceof WC_Product_Variation ) {
			$variation_data = $_product->get_variation_attributes();
		}
		
		if ( isset( $variation_data ) && wc_get_formatted_variation( $variation_data, true ) != '' ) $product_title .= ' ( ' . wc_get_formatted_variation( $variation_data, true ) . ' )';
		
		return $product_title;
	}

	/**
	 * Check if user has any serial key in his order, either active or expired
	 * 
	 * @param  integer $user_id 
	 * @return boolean 			true if has serial keys, false otherwise
	 */
	public function has_user_serial_key( $user_id = 0 ) {

		if ( empty( $user_id ) ) {
			return false;
		}

		global $wpdb;

		$args = array(
						'customer' => $user_id,
						'return' => 'ids',
						'limit' => 0
					);
		$order_ids = wc_get_orders( $args );

		if ( ! empty( $order_ids ) ) {
			$query = "SELECT count(*) 
						FROM {$wpdb->prefix}woocommerce_serial_key 
						WHERE order_id IN ( " . implode( ',', $order_ids ) . " )";
			$serial_keys_count = $wpdb->get_var( $query );
			if ( $serial_keys_count > 0 ) {
				return true;
			}
		}

		return false;

	}

	/**
	 * Get all serial key data for a user
	 * 
	 * @param  integer $user_id 
	 * @return array $data     	Serial Key data
	 */
	public function get_all_serial_key_data( $user_id = 0 ) {

		if ( empty( $user_id ) ) {
			return array();
		}

		global $wpdb;

		$data = array();

		$args = array(
						'customer' => $user_id,
						'return'   => 'ids',
						'limit'    => 0
					);
		$order_ids = wc_get_orders( $args );

		if ( ! empty( $order_ids ) ) {
			$query = "SELECT * 
						FROM {$wpdb->prefix}woocommerce_serial_key 
						WHERE order_id IN ( " . implode( ',', $order_ids ) . " )
						ORDER BY order_id DESC,
								valid_till DESC";
			$serial_keys_data = $wpdb->get_results( $query, ARRAY_A );
			if ( empty( $data['serial_keys'] ) || ! is_array( $data['serial_keys'] ) ) {
				$data['serial_keys'] = array();
			}
			if ( ! empty( $serial_keys_data ) ) {
				$serial_keys = array();
				foreach ( $serial_keys_data as $serial_key_data ) {
					$order_id     = ( ! empty( $serial_key_data['order_id'] ) ) ? $serial_key_data['order_id'] : 0;
					$product_id   = ( ! empty( $serial_key_data['product_id'] ) ) ? $serial_key_data['product_id'] : 0;
					$product_name = $this->get_product_title( $product_id );
					$serial_key   = ( ! empty( $serial_key_data['serial_key'] ) ) ? $serial_key_data['serial_key'] : '';
					$limit        = ( ! empty( $serial_key_data['limit'] ) ) ? maybe_unserialize( $serial_key_data['limit'] ) : array();
					$uuid         = ( ! empty( $serial_key_data['uuid'] ) ) ? maybe_unserialize( $serial_key_data['uuid'] ) : array();
					if ( empty( $order_id ) || empty( $product_id ) ) {
						continue;
					}
					if ( empty( $serial_keys[ $serial_key ] ) || ! is_array( $serial_keys[ $serial_key ] ) ) {
						$serial_keys[ $serial_key ] = array();
					}
					$serial_keys[ $serial_key ][ $order_id ] = array(
																	'order_id'     => $order_id,
																	'product_id'   => $product_id,
																	'product_name' => $product_name,
																	'serial_key'   => $serial_key,
																	'limit'        => $limit,
																	'uuid'         => $uuid
																);
				}
				if ( ! empty( $serial_keys ) ) {
					$data['serial_keys'] = $serial_keys;
				}
			}
		}

		$data = apply_filters( 'wcsk_all_serial_key_data', $data, array( 'user_id' => $user_id, 'order_ids' => $order_ids ) );

		return $data;
	}

	/**
	 * Make meta data of this plugin, protected
	 * 
	 * @param bool $protected
	 * @param string $meta_key
	 * @param string $meta_type
	 * @return bool $protected
	 */
	public function make_sk_meta_protected( $protected, $meta_key, $meta_type ) {
		$sk_meta = array(
							'serial_key_limit'
						);
		if ( in_array( $meta_key, $sk_meta, true ) ) {
			return true;
		}
		return $protected;
	}

	public function plugin_action_links( $links ) {
		$action_links = array(
			'about' => '<a href="' . admin_url( 'admin.php?page=woocommerce_serial_key&landing-page=sk-about' ) . '" title="' . esc_attr( __( 'About WooCommerce Serial Key', self::$text_domain ) ) . '">' . __( 'About', self::$text_domain ) . '</a>',
			'settings' => '<a href="' . admin_url( 'admin.php?page=woocommerce_serial_key' ) . '" title="' . esc_attr( __( 'WooCommerce Serial Key Settings', self::$text_domain ) ) . '">' . __( 'Settings', self::$text_domain ) . '</a>',
			'need_help' => '<a href="'. admin_url( 'admin.php?page=woocommerce_serial_key&landing-page=sk-faqs' ) .'" title="' . __( 'Need Help?', self::$text_domain ) . '">' . __( 'Need Help?', self::$text_domain ) . '</a>'
		);

		return array_merge( $action_links, $links );
	}

	public static function get_plugin_data() {
		return get_plugin_data( SK_PLUGIN_FILE );
	}

	public function active_plugins_for_quick_help( $active_plugins = array(), $upgrader = null ) {
		global $pagenow, $typenow;
		if ( ( ! empty( $pagenow ) && $pagenow == 'admin.php' && ! empty( $_GET['page'] ) && $_GET['page'] == 'woocommerce_serial_key' )
				|| ( ! empty( $typenow ) && ( $typenow == 'product' || $typenow == 'shop_order' ) && ! empty( $pagenow ) && ( $pagenow == 'edit.php' || $pagenow == 'post.php' || $pagenow == 'post-new.php' ) ) ) {
			$active_plugins['wcsk'] = 'woocommerce-serial-key';
		} elseif ( array_key_exists( 'wcsk', $active_plugins ) ) {
			unset( $active_plugins['wcsk'] );
		}
		return $active_plugins;
	}

	/**
     * Determine whether to show notification on a page or not
     * 
     * @param bool $bool
     * @param mixed $upgrader
     * 
     * @return bool $bool
     */
    public function is_page_for_notifications( $bool = false, $upgrader = null ) {

        $active_plugins = apply_filters( 'sa_active_plugins_for_quick_help', array(), $upgrader );
        if ( array_key_exists( $upgrader->sku, $active_plugins ) ) {
            return true;
        }

        return $bool;
    }

}
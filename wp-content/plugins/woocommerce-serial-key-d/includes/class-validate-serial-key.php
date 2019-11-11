<?php
if ( !defined( 'ABSPATH' ) ) exit;

if ( !class_exists( 'Validate_Serial_Key' ) ) {

	class Validate_Serial_Key {

		var $callback;

		public function __construct() {
			
			$this->callback = strtolower( esc_attr( __CLASS__ ) );

			add_action( 'woocommerce_api_'.$this->callback, array( $this, 'validate_serial_key' ) );
			add_filter( 'woocommerce_validate_serial_key', array( $this, 'woocommerce_validate_serial_key' ), 10, 3 );

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

		public function validate_serial_key() {

			$serial_key = ( !empty( $_REQUEST['serial'] ) ) ? $_REQUEST['serial'] : '';
			$product_sku = ( !empty( $_REQUEST['sku'] ) ) ? $_REQUEST['sku'] : '';
			$uuid = ( !empty( $_REQUEST['uuid'] ) ) ? $_REQUEST['uuid'] : '';

			echo apply_filters( 'woocommerce_validate_serial_key', $serial_key, $product_sku, $uuid );
			exit;

		}

		public function woocommerce_validate_serial_key( $serial_key = NULL, $product_sku = NULL, $current_uuid = NULL ) {

			global $wpdb, $sa_serial_key;

			$return = false;
			$message = array();

			if ( empty( $serial_key ) ) {
				$return = true;
				$message[] = __( 'Serial Key empty', SA_Serial_Key::$text_domain );
			}

			if ( empty( $product_sku ) ) {
				$return = true;
				$message[] = __( 'SKU empty', SA_Serial_Key::$text_domain );
			}

			if ( $return ) {
				$result = array( 'success' => 'false', 'message' => implode( ', ', $message ) );
				return apply_filters( 'wsk_validation_response', json_encode( $result ), $serial_key, $product_sku, $current_uuid );
			}

			$query = "SELECT MAX(`order_id`) AS order_id, `product_id`, `limit`, GROUP_CONCAT(`uuid` SEPARATOR '###') AS uuid FROM {$wpdb->prefix}woocommerce_serial_key WHERE serial_key = '" . $serial_key . "' GROUP BY `serial_key`";
			$result = $wpdb->get_row( $query, 'ARRAY_A' );
			
			if ( empty( $result ) ) {
				return apply_filters( 'wsk_validation_response', json_encode( array( 'success' => 'false', 'message' => __( 'Serial key not found', SA_Serial_Key::$text_domain ) ) ), $serial_key, $product_sku, $current_uuid );
			}

			$valid_uuids = array();
			if ( ! empty( $result['uuid'] ) ) {
				$uuids = explode( '###', $result['uuid'] );
				if ( ! empty( $uuids ) ) {
					foreach ( $uuids as $uuid ) {
						$old_uuids = maybe_unserialize( $uuid );
						if ( ! empty( $old_uuids ) ) {
							$valid_uuids = array_merge( $valid_uuids, $old_uuids );
						}
					}
				}
			}

			$limit = ( !empty( $result['limit'] ) ) ? absint( $result['limit'] ) : '';
			$is_new_uuid = false;

			if ( !empty( $limit ) && count( $valid_uuids ) > $limit && !in_array( $current_uuid, $valid_uuids ) ) {
				return apply_filters( 'wsk_validation_response', json_encode( array( 'success' => 'false', 'message' => __( 'Serial key usage exceeded the allowed limit', SA_Serial_Key::$text_domain ) ) ), $serial_key, $product_sku, $current_uuid );
			}

			if ( !empty( $limit ) && count( $valid_uuids ) <= $limit ) {
				if ( !in_array( $current_uuid, $valid_uuids ) ) {
					if ( count( $valid_uuids ) == $limit ) {
						return apply_filters( 'wsk_validation_response', json_encode( array( 'success' => 'false', 'message' => __( 'Serial key usage exceeded the allowed limit', SA_Serial_Key::$text_domain ) ) ), $serial_key, $product_sku, $current_uuid );
					} else {
						$valid_uuids[] = $current_uuid;
						$is_new_uuid = true;
					}
				}
			}

			$order = wc_get_order( $result['order_id'] );

			$is_valid_order_status = false;

			if ( ! $order instanceof WC_Order ) {
				$is_valid_order_status = false;
			} else {
				$current_status = $order->get_status();
				if ( $current_status == 'completed' || $current_status == 'processing' ) {
					$is_valid_order_status = true;
				}
			}

			if ( $is_valid_order_status ) {
				
				$found_sku = get_post_meta( $result['product_id'], '_sku', true );
				if ( empty( $found_sku ) ) {
					$parent_id = wp_get_post_parent_id ( $result['product_id'] );
					$found_sku = get_post_meta( $parent_id, '_sku', true );
					if ( empty( $found_sku ) ) {
						return apply_filters( 'wsk_validation_response', json_encode( array( 'success' => 'false', 'message' => __( 'Serial key invalid for this product', SA_Serial_Key::$text_domain ) ) ), $serial_key, $product_sku, $current_uuid );
					}
				}

				if ( stripos( $found_sku, $product_sku ) === false ) {
					return apply_filters( 'wsk_validation_response', json_encode( array( 'success' => 'false', 'message' => __( 'Serial key invalid for this product', SA_Serial_Key::$text_domain ) ) ), $serial_key, $product_sku, $current_uuid );
				}

				$product_id = $result['product_id'];
				$_product = wc_get_product( $product_id );

				if ( $this->is_wc_gte_30() ) {
					$order_id = ( ! empty( $order ) && is_callable( array( $order, 'get_id' ) ) ) ? $order->get_id() : 0;
					$post_id = ( ! empty( $_product ) && is_callable( array( $_product, 'get_id' ) ) ) ? $_product->get_id() : 0;
				} else {
					$order_id = ( ! empty( $order->id ) ) ? $order->id : 0;
					$post_id = ( !empty( $_product->variation_id ) ) ? $_product->variation_id : $_product->id;
				}

				$download_status_query = "SELECT downloads_remaining, access_expires
											FROM {$wpdb->prefix}woocommerce_downloadable_product_permissions
											WHERE order_id = $order_id
												AND product_id = $product_id
											";
				$download_status = $wpdb->get_row( $download_status_query, 'ARRAY_A' );
				
				if ( $download_status['downloads_remaining'] == '0' ) {
					return apply_filters( 'wsk_validation_response', json_encode( array( 'success' => 'false', 'message' => __( 'Sorry, you have reached your download limit for this product', SA_Serial_Key::$text_domain ) ) ), $serial_key, $product_sku, $current_uuid );
				}

				if ( $download_status['access_expires'] > 0 && strtotime( $download_status['access_expires'] ) < current_time( 'timestamp' ) ) {
					return apply_filters( 'wsk_validation_response', json_encode( array( 'success' => 'false', 'message' => __( 'Sorry, this download has expired', SA_Serial_Key::$text_domain ) ) ), $serial_key, $product_sku, $current_uuid );
				}

				if ( !$_product->is_downloadable() || get_post_meta( $post_id, '_serial_key', true ) !== 'yes' ) {
					return apply_filters( 'wsk_validation_response', json_encode( array( 'success' => 'false', 'message' => __( 'Serial key invalid for this product', SA_Serial_Key::$text_domain ) ) ), $serial_key, $product_sku, $current_uuid );
				}

				if ( $is_new_uuid ) {
					$wpdb->query( "UPDATE {$wpdb->prefix}woocommerce_serial_key SET uuid = '" . maybe_serialize( $valid_uuids ) . "' WHERE order_id = " . $order_id . " AND product_id = " . $post_id );
				}

				return apply_filters( 'wsk_validation_response', json_encode( array( 'success' => 'true', 'message' => __( 'Serial key valid', SA_Serial_Key::$text_domain ) ) ), $serial_key, $product_sku, $current_uuid );
				
			}

			return apply_filters( 'wsk_validation_response', json_encode( array( 'success' => 'false', 'message' => __( 'Invalid order', SA_Serial_Key::$text_domain ) ) ), $serial_key, $product_sku, $current_uuid );

		}

	}

}

new Validate_Serial_Key();
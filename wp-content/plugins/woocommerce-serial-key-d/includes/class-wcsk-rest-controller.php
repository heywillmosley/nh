<?php
/**
 * Handle REST API request for WooCommerce Serial Key plugin
 *
 * @author StoreApps
 * @version 1.0.0
 * @since 1.9.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP_REST_Server' ) ) {
	include_once ABSPATH . WPINC . '/class-wp-rest-server.php';
}

if ( ! class_exists( 'WP_REST_Controller' ) ) {
	include_once ABSPATH . WPINC . '/rest-api/endpoints/class-wp-rest-controller.php';
}

if ( ! class_exists( 'WCSK_REST_Controller' ) ) {

	class WCSK_REST_Controller extends WP_REST_Controller {

		/**
		 * Register the routes for the objects of the controller
		 */
		public function register_routes() {
			$version = '1';
			$namespace = 'woocommerce-serial-key/v' . $version;
			$base = 'serial-keys';
			register_rest_route( $namespace, 
								'/' . $base, 
								array(
									array(
										'methods'             => WP_REST_Server::READABLE,
										'callback'            => array( $this, 'get_items' ),
										'permission_callback' => array( $this, 'get_items_permissions_check' )
									)
								)
			);
		}

		/**
		 * Get a collection of items
		 *
		 * @param WP_REST_Request $request Full data about the request.
		 * @return WP_Error|WP_REST_Response
		 */
		public function get_items( $request ) {

			global $sa_serial_key;

			$user_id = get_current_user_id();

			$items = $sa_serial_key->get_all_serial_key_data( $user_id );

			$data = apply_filters( 'wcsk_rest_response_data', $items, array( 'user_id' => $user_id, 'request' => $request ) );

			return new WP_REST_Response( $data, 200 );
		}

		/**
		 * Check if a given request has access to get items
		 *
		 * @param WP_REST_Request $request Full data about the request.
		 * @return WP_Error|bool
		 */
		public function get_items_permissions_check( $request ) {

			global $sa_serial_key;

			$user_id = get_current_user_id();

			if ( empty( $user_id ) ) {
				return false;
			}

			$is_valid = $sa_serial_key->has_user_serial_key( $user_id );

			$is_valid = apply_filters( 'wcsk_rest_permission_check', $is_valid, array( 'user_id' => $user_id, 'request' => $request ) );
			
			return $is_valid;

		}

	}

}
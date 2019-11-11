<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

include_once( 'api-requests/class-wc-stampscomendicia-api-request.php' );

/**
 * WC_StampscomEndicia_API Class
 */
class WC_StampscomEndicia_API extends WC_StampscomEndicia_API_Request {

	/** @var boolean Stores whether or not stampscomendicia has been authenticated */
	private static $authenticated = false;

	/**
	 * Constructor
	 */
	public function __construct() {
		nocache_headers();

		if ( ! defined( 'DONOTCACHEPAGE' ) ) {
			define( "DONOTCACHEPAGE", "true" );
		}

		if ( ! defined( 'DONOTCACHEOBJECT' ) ) {
			define( "DONOTCACHEOBJECT", "true" );
		}

		if ( ! defined( 'DONOTCACHEDB' ) ) {
			define( "DONOTCACHEDB", "true" );
		}

		self::$authenticated = false;

		$this->request();
	}

	/**
	 * Has API been authenticated?
	 * @return bool
	 */
	public static function authenticated() {
		return self::$authenticated;
	}

	/**
	 * Handle the request
	 */
	public function request() {
		if ( empty( $_GET['auth_key'] ) ) {
			$this->trigger_error( __( 'Authentication key is required!', 'woocommerce-stampscomendicia' ) );
		}

		if ( ! hash_equals( sanitize_text_field( $_GET['auth_key'] ), WC_StampscomEndicia_Integration::$auth_key ) ) {
			$this->trigger_error( __( 'Invalid authentication key', 'woocommerce-stampscomendicia' ) );
		}

		$request = $_GET;

		if ( isset( $request['action'] ) ) {
			$this->request = array_map( 'sanitize_text_field', $request );
		} else {
			$this->trigger_error( __( 'Invalid request', 'woocommerce-stampscomendicia' ) );
		}

		self::$authenticated = true;

		if ( in_array( $this->request['action'], array( 'export', 'shipnotify' ) ) ) {
			$this->log( sprintf( __( 'Input params: %s', 'woocommerce-stampscomendicia' ), http_build_query( $this->request ) ) );
			$request_class = include( 'api-requests/class-wc-stampscomendicia-api-' . $this->request['action'] . '.php' );
			$request_class->request();
		} else {
			$this->trigger_error( __( 'Invalid request', 'woocommerce-stampscomendicia' ) );
		}

		exit;
	}
}

new WC_StampscomEndicia_API();
<?php
/**
 * WooCommerce Elavon Converge
 *
 * This source file is subject to the GNU General Public License v3.0
 * that is bundled with this package in the file license.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.gnu.org/licenses/gpl-3.0.html
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@skyverge.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade WooCommerce Elavon Converge to newer
 * versions in the future. If you wish to customize WooCommerce Elavon Converge for your
 * needs please refer to http://docs.woocommerce.com/document/elavon-vm-payment-gateway/
 *
 * @package     WC-Elavon/API
 * @author      SkyVerge
 * @copyright   Copyright (c) 2013-2017, SkyVerge, Inc.
 * @license     http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

defined( 'ABSPATH' ) or exit;

/**
 * The base transaction request class.
 *
 * @since 2.0.0
 */
abstract class WC_Elavon_Converge_API_Request extends SV_WC_API_XML_Request {


	/** @var array request data */
	protected $request_data = array();

	/** @var \WC_Gateway_Elavon_Converge the gateway object associated with this request */
	protected $gateway;

	/** @var string the transaction type */
	protected $transaction_type = '';


	/**
	 * Constructs the request.
	 *
	 * @since 2.0.0
	 * @param \WC_Gateway_Elavon_Converge $gateway the gateway object associated with this request
	 */
	public function __construct( WC_Gateway_Elavon_Converge $gateway ) {

		$this->gateway = $gateway;
	}


	/**
	 * Converts the request data into an XML string.
	 *
	 * @since 2.0.0
	 * @return string
	 */
	public function to_string() {

		$string = parent::to_string();

		// strip the leading XML data to conform to their strange formatting
		return 'xmldata=' . strstr( $string, '<' . $this->get_root_element() );
	}


	/**
	 * Masks the auth details before logging the request.
	 *
	 * @since 2.0.0
	 * @return string
	 */
	public function to_string_safe() {

		$string = parent::to_string_safe();

		// mask the PIN
		if ( preg_match( '/<ssl_pin>(\w+)<\/ssl_pin>/', $string, $matches ) ) {
			$string = preg_replace( '/<ssl_pin>\w+<\/ssl_pin>/', '<ssl_pin>' . str_repeat( '*', strlen( $matches[1] ) ) . '</ssl_pin>', $string );
		}

		return $string;
	}


	/**
	 * Gets the root XML element.
	 *
	 * @since 2.0.0
	 * @return string
	 */
	protected function get_root_element() {

		return 'txn';
	}


	/**
	 * Gets the request data.
	 *
	 * Overrides the parent method to add auth values to all requests.
	 *
	 * @since 2.0.0
	 * @return array
	 */
	public function get_request_data() {

		$data = parent::get_request_data();

		$auth_data = array(
			'ssl_transaction_type' => $this->get_transaction_type(),
			'ssl_merchant_id'      => $this->get_gateway()->get_merchant_id(),
			'ssl_user_id'          => $this->get_gateway()->get_user_id(),
			'ssl_pin'              => $this->get_gateway()->get_pin(),
		);

		/**
		 * Filters the API request data.
		 *
		 * @since 2.0.0
		 * @param array $data the request data
		 * @param \WC_Elavon_Converge_API_Request $request the request object
		 */
		$data = apply_filters( 'wc_' . $this->get_gateway()->get_id() . '_request_data', array_merge( $auth_data, $data ), $this );

		return array( $this->get_root_element() => $data );
	}


	/**
	 * Get the request transaction type.
	 *
	 * @since 2.0.0
	 * @return string
	 */
	protected function get_transaction_type() {

		return $this->transaction_type;
	}


	/**
	 * Gets the gateway object associated with this request.
	 *
	 * @since 2.0.0
	 * @return \WC_Gateway_Elavon_Converge
	 */
	protected function get_gateway() {

		return $this->gateway;
	}


}

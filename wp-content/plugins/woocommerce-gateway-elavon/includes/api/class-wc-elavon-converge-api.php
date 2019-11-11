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
 * The Converge API class.
 *
 * @since 2.0.0
 */
class WC_Elavon_Converge_API extends SV_WC_API_Base implements SV_WC_Payment_Gateway_API {


	/** @var \WC_Gateway_Elavon_Converge the gateway object */
	protected $gateway;

	/** @var \WC_Order the order object */
	protected $order;


	/**
	 * Constructs the base API.
	 *
	 * @since 2.0.0
	 * @param \WC_Gateway_Elavon_Converge $gateway the gateway object
	 */
	public function __construct( WC_Gateway_Elavon_Converge $gateway ) {

		$this->gateway = $gateway;

		$test_url       = 'https://demo.myvirtualmerchant.com/VirtualMerchantDemo/processxml.do';
		$production_url = 'https://www.myvirtualmerchant.com/VirtualMerchant/processxml.do';

		$this->request_uri = $gateway->is_test_environment() ? $test_url : $production_url;
	}


	/**
	 * Performs a credit card authorization for a given order.
	 *
	 * @since 2.0.0
	 * @param \WC_Order $order the order object
	 * @return \SV_WC_Payment_Gateway_API_Response the response object
	 * @throws \SV_WC_Payment_Gateway_Exception
	 */
	public function credit_card_authorization( WC_Order $order ) {

		$this->order = $order;

		$request = $this->get_new_request();

		$request->create_authorization();

		return $this->perform_request( $request );
	}


	/**
	 * Performs a credit card charge for a given order.
	 *
	 * @since 2.0.0
	 * @param \WC_Order $order the order object
	 * @return \SV_WC_Payment_Gateway_API_Response the response object
	 * @throws \SV_WC_Payment_Gateway_Exception
	 */
	public function credit_card_charge( WC_Order $order ) {

		$this->order = $order;

		$request = $this->get_new_request();

		$request->create_charge();

		return $this->perform_request( $request );
	}


	/**
	 * Performs a credit card capture for a given authorized order.
	 *
	 * @since 2.0.0
	 * @param \WC_Order $order the order object
	 * @return \SV_WC_Payment_Gateway_API_Response credit card charge response object
	 * @throws \SV_WC_Payment_Gateway_Exception
	 */
	public function credit_card_capture( WC_Order $order ) {

		$this->order = $order;

		$request = $this->get_new_request();

		$request->create_capture();

		return $this->perform_request( $request );
	}


	/**
	 * Performs an eCheck debit (ACH transaction) for a given order.
	 *
	 * @since 2.0.0
	 * @param \WC_Order $order the order object
	 * @return \SV_WC_Payment_Gateway_API_Response the response object
	 * @throws \SV_WC_Payment_Gateway_Exception
	 */
	public function check_debit( WC_Order $order ) {

		$this->order = $order;

		$request = $this->get_new_request();

		$request->create_debit();

		return $this->perform_request( $request );
	}


	/**
	 * Performs a refund for a given order.
	 *
	 * @since 2.0.0
	 * @param \WC_Order $order the order object
	 * @return \SV_WC_Payment_Gateway_API_Response the response object
	 * @throws \SV_WC_Payment_Gateway_Exception
	 */
	public function refund( WC_Order $order ) {

		$this->order = $order;

		$request = $this->get_new_request();

		$request->create_refund();

		return $this->perform_request( $request );
	}


	/**
	 * Performs a void for a given order.
	 *
	 * @since 2.0.0
	 * @param \WC_Order $order the order object
	 * @return \SV_WC_Payment_Gateway_API_Response the response object
	 * @throws \SV_WC_Payment_Gateway_Exception
	 */
	public function void( WC_Order $order ) {

		$this->order = $order;

		$request = $this->get_new_request();

		$request->create_void();

		return $this->perform_request( $request );
	}


	/**
	 * Creates a payment token for a given order.
	 *
	 * @since 2.0.0
	 * @param \WC_Order $order the order object
	 * @return \SV_WC_Payment_Gateway_API_Response the response object
	 * @throws \SV_WC_Payment_Gateway_Exception
	 */
	public function tokenize_payment_method( WC_Order $order ) {

		$this->order = $order;

		$request = $this->get_new_request();

		$request->tokenize_payment_method();

		// this method uses the Credit Card request object but the Converge API
		// returns a standard token response
		$this->set_response_handler( 'WC_Elavon_Converge_API_Token_Response' );

		return $this->perform_request( $request );
	}


	/**
	 * Removes a tokenized payment method.
	 *
	 * @since 2.0.0
	 * @see \SV_WC_Payment_Gateway_API::supports_remove_tokenized_payment_method()
	 * @param string $token the payment method token
	 * @param string $customer_id the unique customer ID
	 * @return \SV_WC_Payment_Gateway_API_Response the response object
	 * @throws \SV_WC_Payment_Gateway_Exception
	 */
	public function remove_tokenized_payment_method( $token, $customer_id ) {

		$request = $this->get_new_request( array(
			'type' => 'token',
		) );

		$request->delete_token( $token );

		return $this->perform_request( $request );
	}


	/**
	 * Enables "remove tokenized payment method" support.
	 *
	 * @since 2.0.0
	 * @see \SV_WC_Payment_Gateway_API::remove_tokenized_payment_method()
	 * @return bool
	 */
	public function supports_remove_tokenized_payment_method() {

		return true;
	}


	/**
	 * Gets all tokenized payment methods for the customer.
	 *
	 * @since 2.0.0
	 * @see \SV_WC_Payment_Gateway_API::supports_get_tokenized_payment_methods()
	 * @param string $customer_id the unique customer ID
	 * @return \SV_WC_API_Get_Tokenized_Payment_Methods_Response the response object
	 * @throws \SV_WC_Payment_Gateway_Exception
	 */
	public function get_tokenized_payment_methods( $customer_id ) { }


	/**
	 * Enables support for "get tokenized payment methods".
	 *
	 * @since 2.0.0
	 * @see \SV_WC_Payment_Gateway_API::get_tokenized_payment_methods()
	 * @return bool
	 */
	public function supports_get_tokenized_payment_methods() {

		return false;
	}


	/**
	 * Validates the response after parsing.
	 *
	 * @since 2.0.0
	 * @throws \SV_WC_API_Exception
	 */
	protected function do_post_parse_response_validation() {

		$response = $this->get_response();

		if ( $response->has_error() ) {
			throw new SV_WC_API_Exception( '[' . $response->get_error_code() . '] ' . $response->get_error_message() );
		}
	}


	/**
	 * Gets a new request object.
	 *
	 * @since 2.0.0
	 * @param array $args {
	 *     Optional. The request type arguments.
	 *
	 *     @type string $type         The desired request type
	 *     @type string $payment_type The payment type, if a transaction request
	 * }
	 * @return \SV_WC_API_Request
	 * @throws \SV_WC_API_Exception
	 */
	protected function get_new_request( $args = array() ) {

		$args = wp_parse_args( $args, array(
			'type'         => 'transaction',
			'payment_type' => $this->get_gateway()->get_payment_type(),
		) );

		switch ( $args['type'] ) {

			case 'transaction':
				$request = $this->get_new_transaction_request( $args['payment_type'] );
			break;

			case 'token':

				$request = new WC_Elavon_Converge_API_Token_Request( $this->get_gateway() );

				$this->set_response_handler( 'WC_Elavon_Converge_API_Token_Response' );

			break;

			default:
				throw new SV_WC_API_Exception( 'Invalid request type' );
		}

		return $request;
	}


	/**
	 * Gets a new transaction request object.
	 *
	 * @since 2.0.0
	 * @param string $payment_type Optional. The request payment type, either 'credit-card' or 'echeck'
	 * @return \SV_WC_API_Request
	 * @throws \SV_WC_API_Exception
	 */
	protected function get_new_transaction_request( $payment_type = '' ) {

		// an order is required for transaction requests
		if ( ! $this->get_order() ) {
			throw new SV_WC_API_Exception( 'Order is missing or invalid' );
		}

		switch ( $payment_type ) {

			// credit card transactions
			case 'credit-card':
				$request_class  = 'WC_Elavon_Converge_API_Credit_Card_Transaction_Request';
				$response_class = 'WC_Elavon_Converge_API_Credit_Card_Transaction_Response';
			break;

			// echeck transactions
			case 'echeck':
				$request_class  = 'WC_Elavon_Converge_API_eCheck_Transaction_Request';
				$response_class = 'WC_Elavon_Converge_API_eCheck_Transaction_Response';
			break;

			default:
				throw new SV_WC_API_Exception( 'Invalid payment type' );
		}

		$this->set_response_handler( $response_class );

		return new $request_class( $this->get_gateway(), $this->get_order() );
	}


	/**
	 * Gets the parsed response object for the request.
	 *
	 * Overridden primarily to provide the request object to the response
	 * classes, as the Converge API does not provide a card type in some cases.
	 *
	 * @since 2.0.2
	 * @see SV_WC_API_Base::get_parsed_response()
	 * @param string $raw_response_body
	 * @return \WC_Elavon_Converge_API_Response
	 */
	protected function get_parsed_response( $raw_response_body ) {

		$handler_class = $this->get_response_handler();

		return new $handler_class( $this->get_request(), $raw_response_body );
	}


	/**
	 * Get the ID for the API.
	 *
	 * @since 2.0.0
	 * @return string
	 */
	protected function get_api_id() {

		return $this->get_gateway()->get_id();
	}


	/**
	 * Gets the order object associated with the request, if any.
	 *
	 * @since 2.0.0
	 * @return \WC_Order|null
	 */
	public function get_order() {

		return $this->order;
	}


	/**
	 * Gets the gateway object associated with this API.
	 *
	 * @since 2.0.0
	 * @return \WC_Gateway_Elavon_Converge
	 */
	protected function get_gateway() {

		return $this->gateway;
	}


	/**
	 * Gets the plugin class instance associated with this API.
	 *
	 * @since 2.0.0
	 * @return \SV_WC_Plugin
	 */
	protected function get_plugin() {

		return wc_elavon_converge();
	}


}

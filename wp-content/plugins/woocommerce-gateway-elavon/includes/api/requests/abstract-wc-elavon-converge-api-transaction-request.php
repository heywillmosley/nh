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
 * Technically, all requests to the Converge API are "transaction" requests and require their own value
 * for `ssl_transaction_type`. However, in the context of this integration a transaction request
 * specifically deals with payment transactions.
 *
 * @since 2.0.0
 */
abstract class WC_Elavon_Converge_API_Transaction_Request extends WC_Elavon_Converge_API_Request {


	/** @var \WC_Order the order object associated with this request */
	protected $order;


	/**
	 * Constructs the request.
	 *
	 * @since 2.0.0
	 * @param \WC_Gateway_Elavon_Converge $gateway the gateway object associated with this request
	 * @param \WC_Order $order the order object associated with this request
	 */
	public function __construct( WC_Gateway_Elavon_Converge $gateway, WC_Order $order = null ) {

		parent::__construct( $gateway );

		$this->order = $order;
	}


	/**
	 * Creates the necessary data to perform a payment transaction.
	 *
	 * This is meant to be generic enough to use with any transaction type (credit card or echeck)
	 *
	 * @since 2.0.0
	 */
	protected function create_transaction() {

		$order = $this->get_order();

		$data = array(
			'ssl_invoice_number'   => SV_WC_Helper::str_truncate( ltrim( $order->get_order_number(), _x( '#', 'hash before order number', 'woocommerce-gateway-elavon' ) ), 25, '' ),
			'ssl_amount'           => $order->payment_total,
			'ssl_salestax'         => $order->get_total_tax(),
			'ssl_first_name'       => SV_WC_Helper::str_truncate( SV_WC_Order_Compatibility::get_prop( $order, 'billing_first_name' ), 20 ),
			'ssl_last_name'        => SV_WC_Helper::str_truncate( SV_WC_Order_Compatibility::get_prop( $order, 'billing_last_name' ), 30 ),
			'ssl_company'          => SV_WC_Helper::str_truncate( SV_WC_Order_Compatibility::get_prop( $order, 'billing_company' ), 50 ),
			'ssl_avs_address'      => SV_WC_Helper::str_truncate( SV_WC_Order_Compatibility::get_prop( $order, 'billing_address_1' ), 30 ),
			'ssl_address2'         => SV_WC_Helper::str_truncate( SV_WC_Order_Compatibility::get_prop( $order, 'billing_address_2' ), 30 ),
			'ssl_city'             => SV_WC_Helper::str_truncate( SV_WC_Order_Compatibility::get_prop( $order, 'billing_city' ), 30 ),
			'ssl_state'            => SV_WC_Helper::str_truncate( SV_WC_Order_Compatibility::get_prop( $order, 'billing_state' ), 30 ),
			'ssl_avs_zip'          => SV_WC_Helper::str_truncate( SV_WC_Order_Compatibility::get_prop( $order, 'billing_postcode' ), 9 ),
			'ssl_country'          => SV_WC_Helper::convert_country_code( SV_WC_Order_Compatibility::get_prop( $order, 'billing_country' ) ), // 3-char country code
			'ssl_email'            => SV_WC_Helper::str_truncate( SV_WC_Order_Compatibility::get_prop( $order, 'billing_email' ), 100 ),
			'ssl_phone'            => SV_WC_Helper::str_truncate( preg_replace( '/[^0-9]/', '', SV_WC_Order_Compatibility::get_prop( $order, 'billing_phone' ) ), 20 ),
			'ssl_cardholder_ip'    => SV_WC_Order_Compatibility::get_prop( $order, 'customer_ip_address' ),
		);

		// clean any extra special characters to avoid API issues
		foreach ( $data as $key => $value ) {
			$data[ $key ] = str_replace( array( '&', '<', '>' ), '', $value );
		}

		if ( isset( $order->payment->token ) ) {
			$data['ssl_token'] = $order->payment->token;
		}

		$this->request_data = $data;
	}


	/**
	 * Creates a token based on an order's payment details.
	 *
	 * @since 2.0.0
	 */
	public function tokenize_payment_method() {

		$order = $this->get_order();

		$this->request_data = array(
			'ssl_first_name'       => SV_WC_Helper::str_truncate( SV_WC_Order_Compatibility::get_prop( $order, 'billing_first_name' ), 20 ),
			'ssl_last_name'        => SV_WC_Helper::str_truncate( SV_WC_Order_Compatibility::get_prop( $order, 'billing_last_name' ), 30 ),
			'ssl_avs_address'      => SV_WC_Helper::str_truncate( SV_WC_Order_Compatibility::get_prop( $order, 'billing_address_1' ), 30 ),
			'ssl_avs_zip'          => SV_WC_Helper::str_truncate( SV_WC_Order_Compatibility::get_prop( $order, 'billing_postcode' ), 9, '' ),
			'ssl_add_token'        => 'Y',
		);
	}


	/**
	 * Gets the order object associated with this request.
	 *
	 * @since 2.0.0
	 * @return \WC_Order
	 */
	public function get_order() {

		return $this->order;
	}


}

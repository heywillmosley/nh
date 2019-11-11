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
 * The credit card transaction request class.
 *
 * @since 2.0.0
 */
class WC_Elavon_Converge_API_Credit_Card_Transaction_Request extends WC_Elavon_Converge_API_Transaction_Request {


	/**
	 * Creates a card authorization transaction.
	 *
	 * @since 2.0.0
	 */
	public function create_authorization() {

		$this->transaction_type = 'ccauthonly';

		$this->create_transaction();
	}


	/**
	 * Creates a card charge transaction.
	 *
	 * @since 2.0.0
	 */
	public function create_charge() {

		$this->transaction_type = 'ccsale';

		$this->create_transaction();
	}


	/**
	 * Creates a card transaction.
	 *
	 * @since 2.0.0
	 */
	protected function create_transaction() {

		parent::create_transaction();

		$customer_code = preg_replace( '/[^a-zA-Z0-9]/', '', $this->get_order()->get_order_number() );
		$customer_code = SV_WC_Helper::str_truncate( $customer_code, 17, '' );

		// Even though Elavon says this is optional, apparently it's secretly required, so keep this
		$this->request_data['ssl_customer_code'] = $customer_code;

		if ( ! isset( $this->request_data['ssl_token'] ) ) {

			$this->request_data['ssl_card_number'] = $this->get_order()->payment->account_number;
			$this->request_data['ssl_exp_date']    = $this->get_order()->payment->exp_month . $this->get_order()->payment->exp_year;

			$this->request_data['ssl_cvv2cvc2_indicator'] = isset( $this->get_order()->payment->csc ) ? '1' : '0';

			if ( isset( $this->get_order()->payment->csc ) ) {
				$this->request_data['ssl_cvv2cvc2'] = $this->get_order()->payment->csc;
			}
		}

		// if enabled, add the order currency for multi-currency conversion
		if ( $this->get_gateway()->is_multi_currency_required() ) {
			$this->request_data['ssl_transaction_currency'] = SV_WC_Order_Compatibility::get_prop( $this->get_order(), 'currency', 'view' );
		}
	}


	/**
	 * Captures a previously authorized transaction.
	 *
	 * @since 2.0.0
	 */
	public function create_capture() {

		$this->transaction_type = 'cccomplete';

		$this->request_data['ssl_txn_id'] = $this->get_order()->capture->trans_id;
	}


	/**
	 * Refunds a transaction.
	 *
	 * @since 2.0.0
	 */
	public function create_refund() {

		$this->transaction_type = 'ccreturn';

		$this->request_data['ssl_txn_id']  = $this->get_order()->refund->trans_id;
		$this->request_data['ssl_amount' ] = $this->get_order()->refund->amount;
	}


	/**
	 * Voids a transaction.
	 *
	 * @since 2.0.0
	 */
	public function create_void() {

		$this->transaction_type = $this->get_order()->refund->captured ? 'ccvoid' : 'ccdelete';

		$this->request_data['ssl_txn_id'] = $this->get_order()->refund->trans_id;
	}


	/**
	 * Creates a token based on an order's payment details.
	 *
	 * @since 2.0.0
	 */
	public function tokenize_payment_method() {

		parent::tokenize_payment_method();

		$this->transaction_type = 'ccgettoken';

		$this->request_data['ssl_card_number'] = $this->get_order()->payment->account_number;
		$this->request_data['ssl_exp_date']    = $this->get_order()->payment->exp_month . $this->get_order()->payment->exp_year;
	}


	/**
	 * Masks the credit card details before logging the request.
	 *
	 * @since 2.0.0
	 * @return string
	 */
	public function to_string_safe() {

		$string = parent::to_string_safe();

		// mask the card number
		if ( preg_match( '/<ssl_card_number>(\d+)<\/ssl_card_number>/', $string, $matches ) && strlen( $matches[1] ) > 4 ) {
			$string = preg_replace( '/<ssl_card_number>\d+<\/ssl_card_number>/', '<ssl_card_number>' . substr( $matches[1], 0, 1 ) . str_repeat( '*', strlen( $matches[1] ) - 5 ) . substr( $matches[1], -4 ) . '</ssl_card_number>', $string );
		}

		// mask the CSC
		$string = preg_replace( '/<ssl_cvv2cvc2>\d+<\/ssl_cvv2cvc2>/', '<ssl_cvv2cvc2>***</ssl_cvv2cvc2>', $string );

		return $string;
	}


}

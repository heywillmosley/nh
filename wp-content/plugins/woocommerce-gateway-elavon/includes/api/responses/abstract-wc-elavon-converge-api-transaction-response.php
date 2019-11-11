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
 * The base transaction response class.
 *
 * @since 2.0.0
 */
abstract class WC_Elavon_Converge_API_Transaction_Response extends WC_Elavon_Converge_API_Response implements SV_WC_Payment_Gateway_API_Response {


	/**
	 * Determines if the transaction was successful.
	 *
	 * @since 2.0.0
	 * @return bool
	 */
	public function transaction_approved() {

		return '0' === $this->get_status_code();
	}


	/**
	 * Determines if the transaction was held.
	 *
	 * @since 2.0.0
	 * @return bool
	 */
	public function transaction_held() {

		return 'CALL AUTH CENTER' === $this->get_status_message();
	}


	/**
	 * Gets the response status message.
	 *
	 * @since 2.0.0
	 * @return string
	 */
	public function get_status_message() {

		return $this->ssl_result_message;
	}


	/**
	 * Gets the response status code.
	 *
	 * @since 2.0.0
	 * @return string
	 */
	public function get_status_code() {

		return $this->ssl_result;
	}


	/**
	 * Gets the response transaction ID.
	 *
	 * @since 2.0.0
	 * @return string
	 */
	public function get_transaction_id() {

		return $this->ssl_txn_id;
	}


	/**
	 * Gets the response transaction auth code.
	 *
	 * @since 2.0.0
	 * @return string
	 */
	public function get_authorization_code() {

		return $this->ssl_approval_code;
	}


	/**
	 * Builds the payment token if provided.
	 *
	 * @since 2.0.0
	 * @return \SV_WC_Payment_Gateway_Payment_Token
	 */
	public function get_payment_token() {

		$token = null;

		if ( $token_id = $this->ssl_token ) {
			$token = new SV_WC_Payment_Gateway_Payment_Token( $token_id, $this->get_payment_token_data() );
		}

		return $token;
	}


	/**
	 * Gets the data necessary to build a payment token from the response.
	 *
	 * Extending transaction classes can override this to return data based on their payment type.
	 *
	 * @since 2.0.0
	 * @return array
	 */
	protected function get_payment_token_data() {

		return array();
	}


	/**
	 * Gets the customer-facing result message.
	 *
	 * @since 2.0.0
	 * @see SV_WC_Payment_Gateway_API_Response_Message_Helper
	 * @return string
	 */
	public function get_user_message() {

		$helper = new SV_WC_Payment_Gateway_API_Response_Message_Helper;

		$message_ids = array(
			'DECLINED'     => 'decline',
			'INVALID CARD' => 'card_declined',
			'EXPIRED CARD' => 'card_expiry_invalid',
		);

		$user_message = '';

		if ( isset( $message_ids[ $this->get_status_message() ] ) ) {
			$user_message = $helper->get_user_message( $message_ids[ $this->get_status_message() ] );
		}

		return $user_message;
	}


}

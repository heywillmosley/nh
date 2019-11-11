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
 * @package     WC-Elavon/Gateway
 * @author      SkyVerge
 * @copyright   Copyright (c) 2013-2017, SkyVerge, Inc.
 * @license     http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

defined( 'ABSPATH' ) or exit;

/**
 * The base gateway class.
 *
 * @since 2.0.0
 */
abstract class WC_Gateway_Elavon_Converge extends SV_WC_Payment_Gateway_Direct {


	/** the demo environment identifier */
	const ENVIRONMENT_DEMO = 'demo';

	/** @var string the production account merchant ID */
	protected $merchant_id;

	/** @var string the production account user ID */
	protected $user_id;

	/** @var string the production account PIN */
	protected $pin;

	/** @var string the demo account merchant ID */
	protected $demo_merchant_id;

	/** @var string the demo account user ID */
	protected $demo_user_id;

	/** @var string the demo account PIN */
	protected $demo_pin;

	/** @var WC_Elavon_Converge_API the API instance */
	protected $api;


	/**
	 * Constructs the gateway.
	 *
	 * @since 2.0.0
	 * @param string $id the gateway ID
	 * @param array $args the gateway args
	 */
	public function __construct( $id, $args ) {

		// set the default args shared across gateways
		$args = wp_parse_args( $args, array(
			'method_description' => __( 'Elavon Converge Payment Gateway provides a seamless and secure checkout process for your customers', 'woocommerce-gateway-elavon' ),
			'supports'           => array(),
			'environments'       => array(
				self::ENVIRONMENT_PRODUCTION => __( 'Production', 'woocommerce-gateway-elavon' ),
				self::ENVIRONMENT_DEMO       => __( 'Demo', 'woocommerce-gateway-elavon' ),
			),
			'shared_settings' => array(
				'merchant_id',
				'user_id',
				'pin',
				'demo_merchant_id',
				'demo_user_id',
				'demo_pin',
			),
		) );

		// add any gateway-specific supports
		$args['supports'] = array_unique( array_merge( $args['supports'], array(
			self::FEATURE_PRODUCTS,
			self::FEATURE_PAYMENT_FORM,
			self::FEATURE_DETAILED_CUSTOMER_DECLINE_MESSAGES,
		) ) );

		parent::__construct( $id, wc_elavon_converge(), $args );
	}


	/**
	 * Gets the form fields specific for this method.
	 *
	 * @since 2.0.0
	 * @see SV_WC_Payment_Gateway::get_method_form_fields()
	 * @return array
	 */
	protected function get_method_form_fields() {

		return array(

			'merchant_id' => array(
				'title'    => __( 'Merchant ID', 'woocommerce-gateway-elavon' ),
				'type'     => 'text',
				'desc_tip' => __( 'Converge ID/Account ID as provided by Elavon.  This will be six digits long, and start with the number 5 or 6.', 'woocommerce-gateway-elavon' ),
				'class'    => 'environment-field production-field',
			),

			'user_id' => array(
				'title'    => __( 'User ID', 'woocommerce-gateway-elavon' ),
				'type'     => 'text',
				'desc_tip' => __( 'Converge user ID as configured on Converge', 'woocommerce-gateway-elavon' ),
				'class'    => 'environment-field production-field',
			),

			'pin' => array(
				'title'    => __( 'PIN', 'woocommerce-gateway-elavon' ),
				'type'     => 'password',
				'desc_tip' => __( 'Converge PIN as generated within Converge', 'woocommerce-gateway-elavon' ),
				'class'    => 'environment-field production-field',
			),

			'demo_merchant_id' => array(
				'title'    => __( 'Demo Merchant ID', 'woocommerce-gateway-elavon' ),
				'type'     => 'text',
				'desc_tip' => __( 'Converge ID/Account ID as provided by Elavon for your demo account.  This will be six digits long, and start with the number 5 or 6.', 'woocommerce-gateway-elavon' ),
				'class'    => 'environment-field demo-field',
			),

			'demo_user_id' => array(
				'title'    => __( 'Demo User ID', 'woocommerce-gateway-elavon' ),
				'type'     => 'text',
				'desc_tip' => __( 'Converge demo user ID as configured on Converge', 'woocommerce-gateway-elavon' ),
				'class'    => 'environment-field demo-field',
			),

			'demo_pin' => array(
				'title'    => __( 'Demo PIN', 'woocommerce-gateway-elavon' ),
				'type'     => 'password',
				'desc_tip' => __( 'Converge demo PIN as generated within Converge', 'woocommerce-gateway-elavon' ),
				'class'    => 'environment-field demo-field',
			),
		);
	}


	/**
	 * Determine if the gateway is properly configured to perform transactions.
	 *
	 * @since 2.0.0
	 * @see SV_WC_Payment_Gateway::is_configured()
	 * @return bool
	 */
	protected function is_configured() {

		$is_configured = parent::is_configured();

		// missing configuration settings
		if ( ! $this->get_merchant_id() || ! $this->get_user_id() || ! $this->get_pin() ) {
			$is_configured = false;
		}

		return $is_configured;
	}


	/**
	 * Gets the custom payment form instance.
	 *
	 * @since 2.0.0
	 * @return \WC_Elavon_Payment_Form
	 */
	public function get_payment_form_instance() {

		return new WC_Elavon_Converge_Payment_Form( $this );
	}


	/**
	 * Enables tokenization after a transaction is complete.
	 *
	 * @since 2.0.0
	 * @return bool
	 */
	public function tokenize_after_sale() {
		return true;
	}


	/** Getter methods ******************************************************/


	/**
	 * Gets the order object with gateway payment and transaction data added.
	 *
	 * @since 2.0.0
	 * @see \SV_WC_Payment_Gateway_Direct::get_order()
	 * @return \WC_Order
	 */
	public function get_order( $order_id ) {

		$order = parent::get_order( $order_id );

		// test amount when in demo mode
		if ( $this->is_test_environment() && ( $test_amount = SV_WC_Helper::get_post( 'wc-' . $this->get_id_dasherized() . '-test-amount' ) ) ) {
			$order->payment_total = SV_WC_Helper::number_format( $test_amount );
		}

		return $order;
	}


	/**
	 * Gets the API class instance.
	 *
	 * @since 2.0.0
	 * @see \SV_WC_Payment_Gateway::get_api()
	 * @return \WC_Elavon_Converge_API
	 */
	public function get_api() {

		if ( $this->api instanceof WC_Elavon_Converge_API ) {
			return $this->api;
		}

		$path = wc_elavon_converge()->get_plugin_path() . '/includes/api/';

		$files = array(

			// base
			'class-wc-elavon-converge-api',

			// requests
			'requests/abstract-wc-elavon-converge-api-request',
			'requests/abstract-wc-elavon-converge-api-transaction-request',
			'requests/class-wc-elavon-converge-api-credit-card-transaction-request',
			'requests/class-wc-elavon-converge-api-echeck-transaction-request',
			'requests/class-wc-elavon-converge-api-token-request',

			// responses
			'responses/abstract-wc-elavon-converge-api-response',
			'responses/abstract-wc-elavon-converge-api-transaction-response',
			'responses/class-wc-elavon-converge-api-credit-card-transaction-response',
			'responses/class-wc-elavon-converge-api-echeck-transaction-response',
			'responses/class-wc-elavon-converge-api-token-response',
		);

		foreach ( $files as $file ) {
			require_once( $path . $file . '.php' );
		}

		return $this->api = new WC_Elavon_Converge_API( $this );
	}


	/**
	 * Gets the merchant ID.
	 *
	 * @since 2.0.0
	 * @return string
	 */
	public function get_merchant_id() {

		return $this->is_test_environment() ? $this->demo_merchant_id : $this->merchant_id;
	}


	/**
	 * Gets the user ID.
	 *
	 * @since 2.0.0
	 * @return string
	 */
	public function get_user_id() {

		return $this->is_test_environment() ? $this->demo_user_id : $this->user_id;
	}


	/**
	 * Gets the PIN.
	 *
	 * @since 2.0.0
	 * @return string
	 */
	public function get_pin() {

		return $this->is_test_environment() ? $this->demo_pin : $this->pin;
	}


	/**
	 * Determines if test mode is enabled.
	 *
	 * Spoiler alert: test mode is not enabled.
	 *
	 * @deprecated since 2.0.2
	 *
	 * @since 2.0.0
	 * @return bool
	 */
	public function is_test_mode() {

		_deprecated_function( __CLASS__ . '::is_test_mode()', '2.0.2' );

		return false;
	}


	/**
	 * Determines if the current gateway environment is configured to 'demo'.
	 *
	 * @since 2.0.0
	 * @see SV_WC_Payment_Gateway::is_test_environment()
	 * @param string $environment_id optional. the environment ID to check, otherwise defaults to the gateway current environment
	 * @return bool
	 */
	public function is_test_environment( $environment_id = null ) {

		// if an environment is passed in, check that
		if ( ! is_null( $environment_id ) ) {
			return self::ENVIRONMENT_DEMO === $environment_id;
		}

		// otherwise default to checking the current environment
		return $this->is_environment( self::ENVIRONMENT_DEMO );
	}


}

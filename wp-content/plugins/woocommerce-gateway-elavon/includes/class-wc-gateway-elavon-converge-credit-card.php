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
 * The credit card gateway class.
 *
 * @since 2.0.0
 */
class WC_Gateway_Elavon_Converge_Credit_Card extends WC_Gateway_Elavon_Converge {


	/** @var string|null if multicurrency is enabled **/
	protected $multi_currency_enabled;

	/** @var string the configured terminal currency **/
	protected $multi_currency_terminal_currency;


	/**
	 * Constructs the gateway.
	 *
	 * @since 2.0.0
	 */
	public function __construct() {

		parent::__construct(
			WC_Elavon_Converge::CREDIT_CARD_GATEWAY_ID,
			array(
				'method_title' => __( 'Elavon Converge Credit Card', 'woocommerce-gateway-elavon' ),
				'supports'     => array(
					self::FEATURE_CARD_TYPES,
					self::FEATURE_CREDIT_CARD_CHARGE,
					self::FEATURE_CREDIT_CARD_AUTHORIZATION,
					self::FEATURE_CREDIT_CARD_CAPTURE,
					self::FEATURE_REFUNDS,
					self::FEATURE_VOIDS,
					self::FEATURE_TOKENIZATION,
					self::FEATURE_ADD_PAYMENT_METHOD,
					self::FEATURE_TOKEN_EDITOR,
				),
				'payment_type' => self::PAYMENT_TYPE_CREDIT_CARD,
			)
		);

		/**
		 * Filters the gateway icon markup. DEPRECATED as of 2.0.0
		 *
		 * TODO: remove in a future version {CW 2016-08-17}
		 *
		 * @param string $icon the icon markup
		 */
		$this->icon = apply_filters( 'woocommerce_elavon_vm_icon', $this->icon );
	}


	/**
	 * Gets the form fields specific for this method.
	 *
	 * @since 2.0.0
	 * @see \WC_Gateway_Elavon_Converge::get_method_form_fields()
	 * @return array
	 */
	protected function get_method_form_fields() {

		$fields = parent::get_method_form_fields();

		$fields['multi_currency_enabled'] = array(
			'title'       => __( 'Multi-Currency', 'woocommerce-gateway-elavon' ),
			'label'       => __( 'Enable Multi-Currency transactions.', 'woocommerce-gateway-elavon' ),
			'type'        => 'checkbox',
			'description' => sprintf(
				/* translators: Placeholders: %1$s - <strong> tag, %2$s - </strong> tag */
				__( 'Visa &amp; MasterCard only. Note that you %1$smust enable%2$s Multi-Currency for your account by contacting your merchant terminal representative.', 'woocommerce-gateway-elavon' ),
				'<strong>', '</strong>'
			),
			'default' => 'no',
		);

		$woocommerce_currencies = get_woocommerce_currencies();
		$elavon_currencies      = array( 'USD', 'CAD' );

		$currency_options = array();

		foreach ( $elavon_currencies as $currency ) {
			$currency_options[ $currency ] = ! empty( $woocommerce_currencies[ $currency ] ) ? $woocommerce_currencies[ $currency ] : $currency;
		}

		$fields['multi_currency_terminal_currency'] = array(
			'title'    => __( 'Merchant Terminal Currency', 'woocommerce-gateway-elavon' ),
			'desc_tip' => __( 'The currency in which you accept settled payments.', 'woocommerce-gateway-elavon' ),
			'type'     => 'select',
			'options'  => $currency_options,
			'default'  => current( $currency_options ),
		);

		return $fields;
	}


	/**
	 * Display settings page with some additional javascript for hiding conditional fields
	 *
	 * @since 1.0.0
	 * @see WC_Settings_API::admin_options()
	 */
	public function admin_options() {

		parent::admin_options();

		?>
		<style type="text/css">.nowrap { white-space: nowrap; }</style>
		<?php

		// add inline javascript to show/hide any shared settings fields as needed
		ob_start();
		?>
			$( '#woocommerce_<?php echo $this->get_id(); ?>_multi_currency_enabled' ).change( function() {

				var enabled          = $( this ).is( ':checked' );
				var currency_setting = $( '#woocommerce_<?php echo $this->get_id(); ?>_multi_currency_terminal_currency' );

				if ( enabled ) {
					$( currency_setting ).closest( 'tr' ).show();
				} else {
					$( currency_setting ).closest( 'tr' ).hide();
				}

			} ).change();
		<?php

		wc_enqueue_js( ob_get_clean() );

	}


	/**
	 * Adds refund information as class members of WC_Order instance for use in refund transactions.
	 *
	 * @since 2.0.0
	 * @see \SV_WC_Payment_Gateway::get_order_for_refund()
	 * @return WC_Order|WP_Error the order object with refund information attached or WP_Error on failure
	 */
	protected function get_order_for_refund( $order, $amount, $reason ) {

		$order = parent::get_order_for_refund( $order, $amount, $reason );

		// check whether the charge has already been captured by this gateway
		$order->refund->captured = 'yes' === $this->get_order_meta( $order, 'charge_captured' );

		return $order;
	}


	/**
	 * Adds an order notice to held orders that require voice authorization.
	 *
	 * @since 2.0.0
	 * @see \SV_WC_Payment_Gateway::mark_order_as_held()
	 */
	public function mark_order_as_held( $order, $message, $response = null ) {

		parent::mark_order_as_held( $order, $message, $response );

		if ( $response && 'CALL AUTH CENTER' === $response->get_status_message() ) {

			// if this was an authorization, mark as invalid for capture
			if ( $this->perform_credit_card_authorization() ) {
				$this->update_order_meta( $order, 'auth_can_be_captured', 'no' );
			}

			$order->add_order_note( __( 'Voice authorization required to complete transaction, please call your merchant account.', 'woocommerce-gateway-elavon' ) );
		}
	}


	/** Getter methods ******************************************************/


	/**
	 * Gets the available card types.
	 *
	 * @since 2.0.0
	 * @return array
	 */
	public function get_available_card_types() {

		/**
		 * Filters the available card types. DEPRECATED as of 2.0.0
		 *
		 * TODO: remove in a future version {CW 2016-08-17}
		 *
		 * @param array $card_types the available card types
		 */
		$card_types = apply_filters( 'woocommerce_elavon_card_types', parent::get_available_card_types() );

		return $card_types;
	}


	/**
	 * Gets the enabled card types.
	 *
	 * @since 2.0.0
	 * @return array
	 */
	public function get_card_types() {

		return $this->is_multi_currency_required() ? $this->get_multi_currency_card_types() : parent::get_card_types();
	}


	/**
	 * Gets the card types that support multi-currency.
	 *
	 * @since 2.0.0
	 * @return array
	 */
	public function get_multi_currency_card_types() {

		return array( 'visa', 'mc' );
	}


	/**
	 * Gets the merchant terminal currency.
	 *
	 * @since 2.0.0
	 * @return string
	 */
	public function get_multi_currency_terminal_currency() {

		return $this->multi_currency_terminal_currency;
	}


	/**
	 * Gets the payment form field defaults.
	 *
	 * @since 2.0.0
	 * @return array
	 */
	public function get_payment_method_defaults() {

		$defaults = parent::get_payment_method_defaults();

		if ( $this->is_test_environment() ) {
			$defaults['account-number'] = '4111111111111111';
		}

		return $defaults;
	}


	/**
	 * Determines if the gateway is properly configured to perform transactions.
	 *
	 * @since 2.0.0
	 * @see \WC_Gateway_Elavon_Converge::is_configured()
	 * @return bool
	 */
	protected function is_configured() {

		$is_configured = parent::is_configured();

		// multi-currency support is required but not enabled
		if ( $this->is_multi_currency_required() && ! $this->is_multi_currency_enabled() ) {
			$is_configured = false;
		}

		return $is_configured;
	}


	/**
	 * Determines if multi-currency support is required.
	 *
	 * @since 2.0.0
	 * @return bool
	 */
	public function is_multi_currency_required() {

		// only for non-USD/CAD stores
		$required = $this->is_multi_currency_enabled() && $this->get_payment_currency() !== $this->get_multi_currency_terminal_currency();

		/**
		 * Filters whether multi-currency support is required.
		 *
		 * @since 2.0.0
		 * @param bool $required
		 */
		return (bool) apply_filters( 'wc_' . $this->get_id() . '_multi_currency_required', $required );
	}


	/**
	 * Determines if multi-currency support is enabled.
	 *
	 * @since 2.0.0
	 * @return bool
	 */
	public function is_multi_currency_enabled() {

		/**
		 * Filters whether multi-currency support is enabled.
		 *
		 * @since 2.0.0
		 * @param bool $enabled
		 */
		return (bool) apply_filters( 'wc_' . $this->get_id() . '_multi_currency_enabled', 'yes' === $this->multi_currency_enabled );
	}


}

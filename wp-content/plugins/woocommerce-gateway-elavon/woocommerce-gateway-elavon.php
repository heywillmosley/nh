<?php
/**
 * Plugin Name: WooCommerce Elavon Converge Gateway
 * Plugin URI: http://www.woocommerce.com/products/elavon-vm-payment-gateway/
 * Description: Adds the Elavon Converge (Virtual Merchant) Gateway to your WooCommerce website. Requires an SSL certificate.
 * Author: SkyVerge
 * Author URI: http://www.woocommerce.com/
 * Version: 2.1.0
 * Text Domain: woocommerce-gateway-elavon
 * Domain Path: /i18n/languages/
 *
 * Copyright: (c) 2013-2017, SkyVerge, Inc. (info@skyverge.com)
 *
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 *
 * @package   WC-Elavon
 * @author    SkyVerge
 * @category  Payment-Gateways
 * @copyright Copyright (c) 2012-2017, SkyVerge, Inc.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

defined( 'ABSPATH' ) or exit;

// Required functions
if ( ! function_exists( 'woothemes_queue_update' ) ) {
	require_once( plugin_dir_path( __FILE__ ) . 'woo-includes/woo-functions.php' );
}

// Plugin updates
woothemes_queue_update( plugin_basename( __FILE__ ), '2732aedb77a13149b4db82d484d3bb22', '18722' );

// WC active check
if ( ! is_woocommerce_active() ) {
	return;
}

// Required library class
if ( ! class_exists( 'SV_WC_Framework_Bootstrap' ) ) {
	require_once( plugin_dir_path( __FILE__ ) . 'lib/skyverge/woocommerce/class-sv-wc-framework-bootstrap.php' );
}

SV_WC_Framework_Bootstrap::instance()->register_plugin( '4.6.0', __( 'WooCommerce Elavon Converge Gateway', 'woocommerce-gateway-elavon' ), __FILE__, 'init_woocommerce_gateway_elavon', array(
	'is_payment_gateway'   => true,
	'minimum_wc_version'   => '2.5.5',
	'minimum_wp_version'   => '4.1',
	'backwards_compatible' => '4.4',
) );

function init_woocommerce_gateway_elavon() {

/**
 * The main class for the Elavon Converge Payment Gateway.  This class handles all the
 * non-gateway tasks such as verifying dependencies are met, loading the text
 * domain, etc.
 *
 */
class WC_Elavon_Converge extends SV_WC_Payment_Gateway_Plugin {


	/** version number */
	const VERSION = '2.1.0';

	/** @var WC_Elavon_Converge single instance of this plugin */
	protected static $instance;

	/** plugin id */
	const PLUGIN_ID = 'elavon_vm';

	/** plugin text domain, DEPRECATED as of 1.7.0 */
	const TEXT_DOMAIN = 'woocommerce-gateway-elavon';

	/** string class name to load as gateway, DEPRECATED as of 2.0.0 */
	const GATEWAY_CLASS_NAME = 'WC_Gateway_Elavon_Converge_Credit_Card';

	/** string credit card gateway class name */
	const CREDIT_CARD_GATEWAY_CLASS_NAME = 'WC_Gateway_Elavon_Converge_Credit_Card';

	/** string credit card gateway ID */
	const CREDIT_CARD_GATEWAY_ID = 'elavon_converge_credit_card';

	/** string echeck gateway class name */
	const ECHECK_GATEWAY_CLASS_NAME = 'WC_Gateway_Elavon_Converge_eCheck';

	/** string echeck gateway ID */
	const ECHECK_GATEWAY_ID = 'elavon_converge_echeck';


	/**
	 * Initialize the plugin
	 *
	 * @see SV_WC_Plugin::__construct()
	 */
	public function __construct() {

		parent::__construct(
			self::PLUGIN_ID,
			self::VERSION,
			array(
				'text_domain'  => 'woocommerce-gateway-elavon',
				'gateways'     => array(
					self::CREDIT_CARD_GATEWAY_ID => self::CREDIT_CARD_GATEWAY_CLASS_NAME,
					self::ECHECK_GATEWAY_ID      => self::ECHECK_GATEWAY_CLASS_NAME,
				),
				'supports'     => array(
					self::FEATURE_CAPTURE_CHARGE,
					self::FEATURE_MY_PAYMENT_METHODS,
				),
				'require_ssl'  => true,
				'dependencies' => array(
					'simplexml',
					'xmlwriter',
					'dom',
				),
			)
		);

		// Load the gateway
		add_action( 'sv_wc_framework_plugins_loaded', array( $this, 'includes' ) );
	}


	/**
	 * Backwards compat for changing the naming of some methods.
	 *
	 * TODO: remove in a future version {CW 2016-08-17}
	 *
	 * @since 2.0.0
	 */
	public function __call( $name, $arguments ) {

		switch ( $name ) {

			case 'load_classes':
				_deprecated_function( 'wc_elavon_vm()->load_classes()', '2.0.0' );
				return null;
			break;

			case 'load_gateway':
				_deprecated_function( 'wc_elavon_vm()->load_gateway()', '2.0.0' );
				return null;
			break;
		}

		// you're probably doing it wrong
		trigger_error( 'Call to undefined method ' . __CLASS__ . '::' . $name . '()', E_USER_ERROR );

		return null;
	}


	/**
	 * Gets the deprecated/removed hooks.
	 *
	 * @since 2.0.0
	 * @return array
	 */
	protected function get_deprecated_hooks() {

		$hooks = array(
			'woocommerce_elavon_vm_icon' => array(
				'version'     => '2.0.0',
				'removed'     => false,
				'replacement' => 'wc_' . self::CREDIT_CARD_GATEWAY_ID . '_icon',
			),
			'woocommerce_elavon_card_types' => array(
				'version'     => '2.0.0',
				'removed'     => false,
				'replacement' => 'wc_' . self::CREDIT_CARD_GATEWAY_ID . '_available_card_types',
			),
			'wc_payment_gateway_elavon_vm_request_xml' => array(
				'version'     => '2.0.0',
				'removed'     => true,
				'replacement' => 'wc_' . self::CREDIT_CARD_GATEWAY_ID . '_request_data',
			),
		);

		return $hooks;
	}


	/**
	 * Loads the necessary files.
	 *
	 * @since 2.0.0
	 */
	public function includes() {

		// gateway classes
		require_once( $this->get_plugin_path() . '/includes/abstract-wc-gateway-elavon-converge.php' );
		require_once( $this->get_plugin_path() . '/includes/class-wc-gateway-elavon-converge-credit-card.php' );
		require_once( $this->get_plugin_path() . '/includes/class-wc-gateway-elavon-converge-echeck.php' );

		// payment forms
		require_once( $this->get_plugin_path() . '/includes/payment-forms/class-wc-elavon-converge-payment-form.php' );
		require_once( $this->get_plugin_path() . '/includes/payment-forms/class-wc-elavon-converge-echeck-payment-form.php' );
	}


	/**
	 * Gets the plugin documentation url
	 *
	 * @since 1.2.0
	 * @see SV_WC_Plugin::get_documentation_url()
	 * @return string documentation URL
	 */
	public function get_documentation_url() {
		return 'http://docs.woocommerce.com/document/elavon-vm-payment-gateway/';
	}

	/**
	 * Gets the plugin support URL
	 *
	 * @since 1.6.0
	 * @see SV_WC_Plugin::get_support_url()
	 * @return string
	 */
	public function get_support_url() {

		return 'https://woocommerce.com/my-account/tickets/';
	}


	/**
	 * Displays admin notices for new users.
	 *
	 * @since 2.0.0
	 * @see SV_WC_Plugin::add_admin_notices()
	 */
	public function add_admin_notices() {

		// show any dependency notices
		parent::add_admin_notices();

		$screen = get_current_screen();

		// install notice
		if ( ( isset( $_GET['page'] ) && 'wc-settings' === $_GET['page'] ) || 'plugins' === $screen->id ) {

			$configured  = false;
			$notice      = '';
			$dismissible = true;

			foreach ( $this->get_gateways() as $gateway ) {

				if ( get_option( 'woocommerce_' . $gateway->get_id() . '_settings', false ) ) {

					$configured = true;
					break;
				}
			}

			// if no gateways are configured, display a "config it" prompt
			if ( ! $configured ) {

				$notice = sprintf(
					/* translators: Placeholders: %1$s - <strong> tag, %2$s - the plugin name, %3$s - </strong> tag, %4$s - <a> tag, %5$s - </a> tag */
					__( '%1$s%2$s is almost ready!%3$s To get started, please ​%4$sconnect to Elavon Converge%5$s.', 'woocommerce-gateway-elavon' ),
					'<strong>',
					$this->get_plugin_name(),
					'</strong>',
					'<a href="' . esc_url( $this->get_settings_url() ) . '">',
					'</a>'
				);

				$dismissible = false;

			// otherwise, just a prompt to read the docs will do on our settings/plugins screen
			} elseif ( $this->is_plugin_settings() || 'plugins' === $screen->id ) {

				$notice = sprintf(
					/* translators: Placeholders: %1$s - <strong> tag, %2$s - the plugin name, %3$s - </strong> tag, %4$s - <a> tag, %5$s - </a> tag */
					__( '%1$sThanks for installing %2$s!%3$s Need help? %4$sRead the documentation%5$s.', 'woocommerce-gateway-elavon' ),
					'<strong>',
					$this->get_plugin_name(),
					'</strong>',
					'<a href="' . esc_url( $this->get_documentation_url() ) . '" target="_blank">',
					'</a>'
				);
			}

			if ( $notice ) {

				$this->get_admin_notice_handler()->add_admin_notice( $notice, 'wc-elavon-welcome', array(
					'always_show_on_settings' => false,
					'dismissible'             => $dismissible,
					'notice_class'            => 'updated'
				) );
			}

			$credit_card_gateway = $this->get_gateway( self::CREDIT_CARD_GATEWAY_ID );

			// display a warning if multi-currency is required but not confirmed
			if ( $credit_card_gateway->is_enabled() && $credit_card_gateway->is_multi_currency_required() && ! $credit_card_gateway->is_multi_currency_enabled() ) {

				if ( $this->is_plugin_settings() ) {

					$notice = sprintf(
						/** translators: Placeholders: %s - the payment gateway name */
						__( '%s is inactive because your store\'s currency requires Multi-Currency. Please confirm that Multi-Currency is enabled for your account.', 'woocommerce-gateway-elavon' ),
						'<strong>' . $credit_card_gateway->get_method_title() . '</strong>'
					);

				} else {

					$notice = sprintf(
						/** translators: Placeholders: %1$s - the payment gateway name, %2$s - opening <a> tag, %3$s - closing </a> tag */
						__( '%1$s is inactive because your store\'s currency requires Multi-Currency. Please confirm that Multi-Currency is enabled for your account %2$sin the gateway settings%3$s.', 'woocommerce-gateway-elavon' ),
						'<strong>' . $credit_card_gateway->get_method_title() . '</strong>',
						'<a href="' . esc_url( $this->get_settings_url() ) . '">', '</a>'
					);
				}

				$this->get_admin_notice_handler()->add_admin_notice( $notice, 'wc-elavon-multi-currency-required', array(
					'always_show_on_settings' => true,
					'dismissible'             => false,
					'notice_class'            => 'error',
				) );
			}
		}
	}


	/** Helper methods ******************************************************/


	/**
	 * Main <Plugin Name> Instance, ensures only one instance is/can be loaded
	 *
	 * @since 1.3.0
	 * @see wc_elavon_converge()
	 * @return WC_Elavon_Converge
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}


	/**
	 * Returns the plugin name, localized
	 *
	 * @since 1.2.0
	 * @see SV_WC_Payment_Gateway::get_plugin_name()
	 * @return string the plugin name
	 */
	public function get_plugin_name() {
		return __( 'WooCommerce Elavon Converge', 'woocommerce-gateway-elavon' );
	}


	/**
	 * Gets the "Configure Credit Cards" or "Configure eCheck" plugin action links that go
	 * directly to the gateway settings page.
	 *
	 * @since 2.0.0
	 * @see SV_WC_Payment_Gateway_Plugin::get_settings_url()
	 * @param string $gateway_id the gateway ID
	 * @return string
	 */
	public function get_settings_link( $gateway_id = null ) {

		if ( self::ECHECK_GATEWAY_ID === $gateway_id ) {
			$label = __( 'Configure eChecks', 'woocommerce-gateway-elavon' );
		} else {
			$label = __( 'Configure Credit Cards', 'woocommerce-gateway-elavon' );
		}

		return sprintf( '<a href="%s">%s</a>',
			$this->get_settings_url( $gateway_id ),
			$label
		);
	}


	/**
	 * Returns __FILE__
	 *
	 * @since 1.2.0
	 * @return string the full path and filename of the plugin file
	 */
	protected function get_file() {
		return __FILE__;
	}


	/** Lifecycle methods ******************************************************/


	/**
	 * Runs every time. Used since the activation hook is not executed when updating a plugin
	 *
	 * @since 1.2.0
	 * @see SV_WC_Plugin::install()
	 */
	protected function install() {

		// check for a pre 1.2 version
		if ( $legacy_version = get_option( 'wc_gateway_elavon_vm' ) ) {
			$this->upgrade( $legacy_version );
		}
	}


	/**
	 * Runs when the plugin version number changes.
	 *
	 * @since 1.2.0
	 * @see SV_WC_Plugin::upgrade()
	 */
	protected function upgrade( $installed_version ) {

		// delete legacy option if it exists
		delete_option( 'wc_gateway_elavon_vm' );

		// if installed version is less than 1.0.4, set the correct account type, if needed
		if ( version_compare( $installed_version, "1.0.4", '<' ) ) {

			// Can't think of a great way of grabbing this from the abstract WC_Settings_API class
			$plugin_id = 'woocommerce_';

			$form_field_settings = (array) get_option( $plugin_id . self::PLUGIN_ID . '_settings' );

			// for existing installs, configured prior to the introduction of the 'account' setting
			if ( $form_field_settings && ! isset( $form_field_settings['account'] ) ) {

				if ( isset( $form_field_settings['testmode'] ) && 'yes' == $form_field_settings['testmode'] ) {
					$form_field_settings['account'] = 'demo';
				} else {
					$form_field_settings['account'] = 'production';
				}

				// set the account type
				update_option( $plugin_id . self::PLUGIN_ID . '_settings', $form_field_settings );
			}
		}

		// standardize debug_mode setting
		if ( version_compare( $installed_version, "1.1.1", '<' ) && ( $settings = get_option( 'woocommerce_' . self::PLUGIN_ID . '_settings' ) ) ) {

			// previous settings
			$log_enabled   = isset( $settings['log'] )   && 'yes' == $settings['log']   ? true : false;
			$debug_enabled = isset( $settings['debug'] ) && 'yes' == $settings['debug'] ? true : false;

			// logger -> debug_mode
			if ( $log_enabled && $debug_enabled ) {
				$settings['debug_mode'] = 'both';
			} elseif ( ! $log_enabled && ! $debug_enabled ) {
				$settings['debug_mode'] = 'off';
			} elseif ( $log_enabled ) {
				$settings['debug_mode'] = 'log';
			} else {
				$settings['debug_mode'] = 'checkout';
			}

			unset( $settings['log'] );
			unset( $settings['debug'] );

			update_option( 'woocommerce_' . self::PLUGIN_ID . '_settings', $settings );
		}

		// upgrade to 2.0.0
		if ( version_compare( $installed_version, '2.0.0', '<' ) ) {

			$this->log( sprintf( 'Upgrading from %1$s to %2$s', $installed_version, '2.0.0' ) );

			// upgrade settings
			if ( $settings = get_option( 'woocommerce_' . self::PLUGIN_ID . '_settings' ) ) {

				$gateway          = $this->get_gateway();
				$settings_fields  = $gateway->get_form_fields();

				// these option values can be updated 1:1
				$updated_keys = array(
					'cvv'                  => 'enable_csc',
					'cardtypes'            => 'card_types',
					'testmode'             => 'test_mode',
					'sslmerchantid'        => 'merchant_id',
					'ssluserid'            => 'user_id',
					'sslpin'               => 'pin',
					'demo_ssl_merchant_id' => 'demo_merchant_id',
					'demo_ssl_user_id'     => 'demo_user_id',
					'demo_ssl_pin'         => 'demo_pin',
				);

				foreach ( $updated_keys as $old_key => $new_key ) {

					if ( isset( $settings[ $old_key ] ) ) {

						$value = $settings[ $old_key ];

						unset( $settings[ $old_key ] );

					} elseif ( isset( $settings_fields[ $new_key ]['default'] ) ) {

						$value = $settings_fields[ $new_key ]['default'];
					}

					$settings[ $new_key ] = $value;
				}

				// the remaining settings need a little massaging
				$settings['environment']      = isset( $settings['account'] ) && 'demo' === $settings['account'] ? WC_Gateway_Elavon_Converge::ENVIRONMENT_DEMO : WC_Gateway_Elavon_Converge::ENVIRONMENT_PRODUCTION;
				$settings['transaction_type'] = isset( $settings['settlement'] ) && 'yes' === $settings['settlement'] ? WC_Gateway_Elavon_Converge::TRANSACTION_TYPE_CHARGE : WC_Gateway_Elavon_Converge::TRANSACTION_TYPE_AUTHORIZATION;

				// remove old settings
				unset( $settings['account'], $settings['settlement'] );

				$settings['inherit_settings'] = 'no';

				// we're only concerned about the credit card gateway settings
				// since the eCheck gateway didn't exist prior to 2.0.0
				update_option( 'woocommerce_' . self::CREDIT_CARD_GATEWAY_ID . '_settings', $settings );

				delete_option( 'woocommerce_' . self::PLUGIN_ID . '_settings' );

				$this->log( 'Settings updated' );
			}

			global $wpdb;

			/** Update meta values for order payment method */

			// meta key: _payment_method
			// old value: elavon_vm
			// new value: elavon_converge_credit_card
			$rows = $wpdb->update( $wpdb->postmeta, array( 'meta_value' => self::CREDIT_CARD_GATEWAY_ID ), array( 'meta_key' => '_payment_method', 'meta_value' => self::PLUGIN_ID ) );

			$this->log( sprintf( '%d orders updated for payment method meta', $rows ) );

			// upgrade complete
			$this->log( sprintf( 'Finished upgrading from %1$s to %2$s', $installed_version, '2.0.0' ) );
		}

	}


} // end WC_Elavon_Converge


/**
 * Returns the One True Instance of Elavon Converge.
 *
 * @deprecated since 2.0.0
 *
 * @since 1.3.0
 * @return \WC_Elavon_Converge
 */
function wc_elavon_vm() {

	_deprecated_function( 'wc_elavon_vm()', '2.0.0', 'wc_elavon_converge()' );

	return wc_elavon_converge();
}


/**
 * Returns the One True Instance of Elavon Converge.
 *
 * @since 2.0.0
 * @return \WC_Elavon_Converge
 */
function wc_elavon_converge() {
	return WC_Elavon_Converge::instance();
}


// fire it up!
wc_elavon_converge();


} // init_woocommerce_gateway_elavon()

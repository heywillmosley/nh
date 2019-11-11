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
 * Elavon payment form class.
 *
 * @since 2.0.0
 */
class WC_Elavon_Converge_Payment_Form extends SV_WC_Payment_Gateway_Payment_Form {


	/**
	 * Render a test amount input field that can be used to override the order total
	 * when using the gateway in demo mode. The order total can then be set to
	 * various amounts to simulate various authorization/settlement responses
	 *
	 * @since 2.0.0
	 */
	public function render_payment_form_description() {

		parent::render_payment_form_description();

		if ( $this->get_gateway()->is_test_environment() && ! is_add_payment_method_page() ) {

			$id = 'wc-' . $this->get_gateway()->get_id_dasherized() . '-test-amount';

			?>
			<p class="form-row">
				<label for="<?php echo sanitize_html_class( $id ); ?>"><?php esc_html_e( 'Test Amount', 'woocommerce-gateway-elavon' ); ?></label>
				<input type="text" id="<?php echo sanitize_html_class( $id ); ?>" name="<?php echo esc_attr( $id ); ?>" />
				<div style="font-size: 10px;" class="description"><?php esc_html_e( 'Enter a test amount to trigger a specific error response, or leave blank to use the order total.', 'woocommerce-gateway-elavon' ); ?></div>
			</p>
			<?php
		}
	}


}

<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class WC_Zapier_Trigger_Order_New extends WC_Zapier_Trigger_Order {

	/**
	 * A list of order IDs and new statuses.
	 * Only used for orders that get the WC_Order::payment_complete() function executed
	 * @var array
	 */
	protected static $payment_complete_new_order_status_list = array();

	public function __construct() {
		$this->status_slug = 'processing';
		$this->trigger_title       = __( 'New Order', 'wc_zapier' );

		$this->trigger_description = __( 'Triggers when an order\'s payment is completed, or when an order has its status changed to Processing.', 'wc_zapier' );

		// Prefix the trigger key with wc. to denote that this is a trigger that relates to a WooCommerce order
		$this->trigger_key         = 'wc.new_order';

		$this->sort_order = 1;

		// WooCommerce action(s) that apply to this trigger event

		// Add the supported hooks for all the possible payment status transitions to processing
		foreach ( WC_Zapier::get_order_statuses() as $status ) {
			if ( $status != $this->status_slug )
				$this->actions[ "woocommerce_order_status_{$status}_to_{$this->status_slug}" ] = 1;
		}

		// Ensure virtual-only orders also get sent to Zapier (they typically skip the "processing" status and go straight to "completed")
		$this->actions['woocommerce_payment_complete'] = 1;
		add_filter( 'woocommerce_payment_complete_order_status', array( $this, 'woocommerce_payment_complete_order_status' ), 99999, 2 );

		parent::__construct();
	}

	/**
	 * This filter is used to detect an order that is having the WC_Order::payment_complete() function called on it.
	 *
	 * If this occurs, the order is about to have its status changed, and then have the 'woocommerce_payment_complete' hook fired for it.
	 *
	 * If $new_order_status is "completed", then the order is a virtual and downloadble only order so has probably skipped the "processing" state
	 * If $new_order_status is "processing", then the order needs processing (ie an order for a non-virtual product)
	 * If $new_order_status is another status, another plugin has overridden the default behaviour.
	 *
	 * After this filter is run, the order's status is updated to $new_order_status
	 *
	 * See https://github.com/woothemes/woocommerce/blob/v2.1.8/includes/class-wc-order.php#L1360 for the logic
	 *
	 * @param string $new_order_status The order status that the order is about to be changed to
	 * @param int    $order_id         The order ID
	 *
	 * @return string
	 */
	public function woocommerce_payment_complete_order_status( $new_order_status, $order_id ) {
		// Store the order ID and new status for use later in the page load
		self::$payment_complete_new_order_status_list[$order_id] = $new_order_status;

		return $new_order_status;
	}

	/**
	 * Ensure that an order isn't sent to Zapier twice as part of the "New Order" trigger event.
	 *
	 * This can happen for orders that the WC_Order::payment_complete() function called on it (most automatic payment gateways),
	 * where the order is also set to processing (rather than completed).
	 *
	 * The most common scenario is for orders that contain physical products that need shipping.
	 *
	 * Orders that contain virtual & downloadable products only will not have this problem because their status never
	 * gets set to processing.
	 *
	 * @param string $action_name Hook name
	 * @param array  $args        Hook arguments
	 *
	 * @return bool true
	 */
	protected function should_schedule_event( $action_name, $args ) {

		if ( 'woocommerce_payment_complete' == $action_name ) {
			// Check to see if this order ID has just been sent to Zapier via the order status change to processing
			$order_id = intval( $args[0] );
			if ( isset (self::$payment_complete_new_order_status_list[$order_id]) && "processing" == self::$payment_complete_new_order_status_list[$order_id] ) {
				// The order has just been set to processing, so don't send it to Zapier a second time
				return false;
			}
		}
		// Otherwise we'll send it to Zapier

		return parent::should_schedule_event( $action_name, $args );
	}

}
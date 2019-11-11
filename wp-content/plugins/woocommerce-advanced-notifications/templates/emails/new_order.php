<?php
/**
 * New order email
 */
if ( ! defined( 'ABSPATH' ) ) exit; ?>

<?php do_action( 'woocommerce_email_header', $email_heading ); ?>

<p><?php printf( __('Hi %s,', 'woocommerce-advanced-notifications'), $recipient_name ); ?></p>

<p><?php echo __('You have received an order from', 'woocommerce-advanced-notifications') . ' ' . $order->billing_first_name . ' ' . $order->billing_last_name . ':'; ?></p>

<h2><?php echo __('Order:', 'woocommerce-advanced-notifications') . ' ' . $order->get_order_number(); ?> (<?php printf( '<time datetime="%s">%s</time>', date_i18n( 'c', strtotime( $order->order_date ) ), date_i18n( __('jS F Y', 'woocommerce-advanced-notifications'), strtotime( $order->order_date ) ) ); ?>)</h2>

<table cellspacing="0" cellpadding="6" style="width: 100%; border: 1px solid #eee;" border="1" bordercolor="#eee">
	<thead>
		<tr>
			<th scope="col" style="text-align:left; border: 1px solid #eee;"><?php _e('Product', 'woocommerce-advanced-notifications'); ?></th>
			<th scope="col" style="text-align:left; border: 1px solid #eee;" <?php if ( ! $show_prices ) : ?>colspan="2"<?php endif; ?>><?php _e('Quantity', 'woocommerce-advanced-notifications'); ?></th>
			<?php if ( $show_prices ) : ?>
				<th scope="col" style="text-align:left; border: 1px solid #eee;"><?php _e('Price', 'woocommerce-advanced-notifications'); ?></th>
			<?php endif; ?>
		</tr>
	</thead>
	<tbody>
		<?php
		$displayed_total = 0;

		foreach ( $order->get_items() as $item ) :

			$_product 	= $order->get_product_from_item( $item );

			$display = false;

			if ( $triggers['all'] || in_array( $_product->id, $triggers['product_ids'] ) || in_array( $_product->get_shipping_class_id(), $triggers['shipping_classes'] ) )
				$display = true;

			if ( ! $display ) {

				$cats = wp_get_post_terms( $_product->id, 'product_cat', array( "fields" => "ids" ) );

				if ( sizeof( array_intersect( $cats, $triggers['product_cats'] ) ) > 0 )
					$display = true;

			}

			if ( ! $display )
				continue;

			$displayed_total += $order->get_line_total( $item, true );

			if ( version_compare( WC_VERSION, '2.4.0', '<' ) ) {
				$item_meta = new WC_Order_Item_Meta( $item['item_meta'] );
			} else {
				$item_meta = new WC_Order_Item_Meta( $item );
			}
			?>
			<tr>
				<td style="text-align:left; vertical-align:middle; border: 1px solid #eee;"><?php

					// Product name
					echo 	apply_filters( 'woocommerce_order_product_title', $item['name'], $_product );

					// SKU
					echo 	$_product->get_sku() ? ' (#' . $_product->get_sku() . ')' : '';

					// Variation
					echo 	$item_meta->meta ? '<br/><small>' . nl2br( $item_meta->display( true, true ) ) . '</small>' : '';

				?></td>
				<td style="text-align:left; vertical-align:middle; border: 1px solid #eee;" <?php if ( ! $show_prices ) : ?>colspan="2"<?php endif; ?>><?php echo $item['qty'] ;?></td>

				<?php if ( $show_prices ) : ?>
					<td style="text-align:left; vertical-align:middle; border: 1px solid #eee;"><?php echo $order->get_formatted_line_subtotal( $item ); ?></td>
				<?php endif; ?>
			</tr>

		<?php endforeach; ?>
	</tbody>
	<?php if ( $show_totals ) : ?>
		<tfoot>
			<?php
				if ( $triggers['all'] && ( $totals = $order->get_order_item_totals() ) ) {
					$i = 0;
					foreach ( $totals as $total ) {
						$i++;
						?><tr>
							<th scope="row" colspan="2" style="text-align:left; border: 1px solid #eee; <?php if ( $i == 1 ) echo 'border-top-width: 4px;'; ?>"><?php echo $total['label']; ?></th>
							<td style="text-align:left; border: 1px solid #eee; <?php if ( $i == 1 ) echo 'border-top-width: 4px;'; ?>"><?php echo $total['value']; ?></td>
						</tr><?php
					}
				} else {
					// Only show the total for displayed items
					?><tr>
						<th scope="row" colspan="2" style="text-align:left; border: 1px solid #eee; border-top-width: 4px;"><?php _e( 'Total', 'woocommerce-advanced-notifications' ); ?></th>
						<td style="text-align:left; border: 1px solid #eee; border-top-width: 4px;"><?php echo woocommerce_price( $displayed_total ); ?></td>
					</tr><?php
				}
			?>
		</tfoot>
	<?php endif; ?>
</table>

<?php do_action('woocommerce_email_after_order_table', $order, false); ?>

<?php do_action( 'woocommerce_email_order_meta', $order, true ); ?>

<h2><?php _e('Customer details', 'woocommerce-advanced-notifications'); ?></h2>

<?php if ($order->billing_email) : ?>
	<p><strong><?php _e('Email:', 'woocommerce-advanced-notifications'); ?></strong> <?php echo $order->billing_email; ?></p>
<?php endif; ?>
<?php if ($order->billing_phone) : ?>
	<p><strong><?php _e('Tel:', 'woocommerce-advanced-notifications'); ?></strong> <?php echo $order->billing_phone; ?></p>
<?php endif; ?>

<?php woocommerce_get_template('emails/email-addresses.php', array( 'order' => $order )); ?>

<?php do_action('woocommerce_email_footer'); ?>
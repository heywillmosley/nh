<?php
/**
 * My Serial Keys
 *
 * Shows all serial keys of purchased products
 *
 * @author 		StoreApps
 * @package 	woocommerce-serial-key/templates
 * @version     1.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
?>
<style>
	span.serial_key {
		float: right;
	}
	.serial_keys {
		color: #000;
	}
</style>
<h3><?php echo apply_filters( 'woocommerce_my_serial_key_title', $title ); ?></h3>

<ul class="serial_keys">
	<?php foreach ( $serial_keys as $product_id => $serial_key ) { ?>
		<li>
			<?php
				do_action( 'woocommerce_my_serial_key_start', $product_id, $serial_key );

				echo apply_filters( 'woocommerce_my_serial_key_product_serial_key', '<span class="serial_key">' . $serial_key . '</span> ', $serial_key );

				echo apply_filters( 'woocommerce_my_serial_key_product_title', SA_Serial_Key::get_product_title( $product_id ), $product_id );

				do_action( 'woocommerce_my_serial_key_end', $product_id, $serial_key );
			?>
		</li>
	<?php } ?>
</ul>
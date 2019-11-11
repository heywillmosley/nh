<?php
/**
 * My Serial Key Usage
 *
 * Shows serial key usage
 *
 * @author 		StoreApps
 * @package 	woocommerce-serial-key/templates
 * @version     1.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
global $wpdb, $woocommerce, $sa_serial_key;
$js = "
		var switchShowHide = function() {
            var total = jQuery('.serial_key_usage_container details').length;
            var open = jQuery('.serial_key_usage_container details[open]').length;
            if ( open == total ) {
                jQuery('.open_close a#open_close_link').text('" .__( 'Hide details', SA_Serial_Key::$text_domain ) . "');
            } else {
                jQuery('.open_close a#open_close_link').text('" . __( 'Show details', SA_Serial_Key::$text_domain ) . "');
            }
        };
        switchShowHide();

        jQuery('.open_close a#open_close_link').on('click', function(){
            var current = jQuery('.serial_key_usage_container details').attr('open');
            if ( current == '' || current == undefined ) {
                jQuery('.serial_key_usage_container details').attr('open', 'open');
                jQuery('.open_close a#open_close_link').text('" .__( 'Hide details', SA_Serial_Key::$text_domain ) . "');
            } else {
                jQuery('.serial_key_usage_container details').removeAttr('open');
                jQuery('.open_close a#open_close_link').text('" . __( 'Show details', SA_Serial_Key::$text_domain ) . "');
            }
        });

        jQuery('.serial_key_usage_container summary').on('mouseup', function(){
            setTimeout( switchShowHide, 10 );
        });
	";

wc_enqueue_js( $js );

$serial_key_uuid_display_name = get_option( 'serial_key_uuid_display_name' );
?>
<style type="text/css">
	.serial_key_usage_container details ul {
		list-style-type: none;
	}
	.serial_key_usage_container details,
	.serial_key_usage_container label {
		cursor: pointer;
	}
	span.open_close {
		display: block;
		text-align: right;
	}
</style>
<form action="" method="post">
<?php
	if ( isset( $_POST['remove_selected_serial_key'] ) && $_POST['remove_selected_serial_key'] != '' ) {
		
		if ( isset( $_POST['uuid'] ) ) {

			foreach ( $_POST['uuid'] as $key => $remove_uuid ) {
				$ids = explode( '_', $key );
				$serial_key_usage[$key]['uuid'] = array_diff( $serial_key_usage[$key]['uuid'], $remove_uuid );
				$uuids = array_values( $serial_key_usage[$key]['uuid'] );
				$wpdb->query( "UPDATE {$wpdb->prefix}woocommerce_serial_key SET uuid = '" . maybe_serialize( $uuids ) . "' WHERE order_id = {$ids[0]} AND product_id = {$ids[1]}" );
			}
		}
	}
?>
	<div class="serial_key_usage_wrap">
		<span class="open_close"><a id="open_close_link"><?php _e( 'Show details', SA_Serial_Key::$text_domain ); ?></a></span>
		<br />
		<div class="serial_key_usage_container">
	    <?php
	    
	    foreach ( $serial_key_usage as $key => $usage ) {
	    	?>
    		<details>
    			<summary><?php echo '<strong>' . $usage['product_title'] . '</strong> &mdash; ' . $usage['serial_key']; ?></summary>
    			<ul id="<?php echo $key; ?>">
    				<?php
    					if ( !empty( $usage['uuid'] ) ) {
	    					foreach ( $usage['uuid'] as $index => $current_uuid ) {
	    						?>
	    						<li><label for="uuid_<?php echo $key . '_' . $index; ?>"><input id="uuid_<?php echo $key . '_' . $index; ?>" type="checkbox" name="uuid[<?php echo $key; ?>][]" value="<?php echo $current_uuid; ?>" /> <?php echo ( ( !empty( $serial_key_uuid_display_name ) ) ? '<strong>' . $serial_key_uuid_display_name . '</strong>: ' : '' ) . $current_uuid; ?></label></li>
	    						<?php
	    					}
    					}
    				?>
    			</ul>
    		</details>
	    	<?php
	    }

	    ?>
	    <input type="submit" name="remove_selected_serial_key" value="<?php echo __( 'Remove', SA_Serial_Key::$text_domain ) . ' ' . $serial_key_uuid_display_name; ?>" />
    	</div>
    </div>
</form>
<?php 

/**
 * Email to Notify Store Admin before Serial Key gets over
 *
 * Shows all serial keys of purchased products
 *
 * @author 		StoreApps
 * @package 	woocommerce-serial-key/templates
 * @version     1.1
 */

	if (!defined('ABSPATH')) exit;	// Exit if accessed directly

	if ( function_exists( 'wc_get_template' ) ) {
		wc_get_template('emails/email-header.php', array( 'email_heading' => $email_heading ));
	} else {
		woocommerce_get_template('emails/email-header.php', array( 'email_heading' => $email_heading ));
	}
?>

<p><?php echo sprintf(__( 'You have %s keys remaining%s.', SA_Serial_Key::$text_domain ), $count, ( ! empty( $product_name ) ? __( ' for ', self::$text_domain ) . $product_name : '' ) ); ?></p>
<p><?php echo __( 'Please upload a new file with Serial Keys. If you will not upload new Serial Keys, then the plugin will generate keys on its own.', SA_Serial_Key::$text_domain ); ?></p>

<div style="clear:both;"></div>

<?php 
	if ( function_exists( 'wc_get_template' ) ) {
		wc_get_template('emails/email-footer.php');
	} else {
		woocommerce_get_template('emails/email-footer.php');
	}
?>
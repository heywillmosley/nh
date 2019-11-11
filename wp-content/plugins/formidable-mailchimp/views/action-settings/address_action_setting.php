<?php
/**
 * @since 2.03
 */
?>
<p class="frm6">
	<label><?php esc_html_e( 'Action', 'frmmlcmp' ) ?> <span class="frm_required">*</span></label>
<select name="<?php echo esc_attr( $action_control->get_field_name('address_action') ) ?>" class="frm_mlcmp_address_action" id="frm_mlcmp_address_action_<?php
echo esc_attr( $action_control->number )?>">
	<option value="subscribe"<?php
	echo ( $list_options['address_action'] === 'subscribe' ) ? esc_html(' selected="selected"') : ''?>><?php
		esc_html_e( 'Subscribe or update user' ); ?></option>
	<option value="unsubscribe"<?php
	echo ( $list_options['address_action'] === 'unsubscribe' ) ? esc_html(' selected="selected"') : ''?>><?php
		esc_html_e( 'Unsubscribe address' ); ?></option>
</select>
</p>

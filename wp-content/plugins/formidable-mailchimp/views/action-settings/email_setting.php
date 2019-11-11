<?php
/**
 * @since 2.03
 */
?>
<p class="frm6">
	<label>
		<?php esc_html_e( 'Email Address' ); ?>
		<span class="frm_required">*</span>
	</label>

	<select name="<?php echo esc_attr( $action_control->get_field_name('fields') ) ?>[EMAIL]">
		<option value=""><?php esc_html_e( '&mdash; Select &mdash;' ); ?></option>
		<?php foreach ( $form_fields as $form_field ) {
			if ( ! in_array( $form_field->type, array( 'email', 'hidden', 'user_id', 'text' ) ) ) {
				continue;
			}

			$selected = ( isset($list_options['fields']['EMAIL']) && $list_options['fields']['EMAIL'] == $form_field->id ) ? ' selected="selected"' : '';
			?>
			<option value="<?php echo esc_attr( $form_field->id ) ?>" <?php echo esc_html( $selected ) ?>><?php echo FrmAppHelper::truncate($form_field->name, 40) ?></option>
		<?php } ?>
	</select>
</p>

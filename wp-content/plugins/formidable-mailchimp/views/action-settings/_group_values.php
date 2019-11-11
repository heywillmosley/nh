<div class="frm_mlcmp_group_select_<?php echo esc_attr( $group['id'] ) ?> frm_mlcmp_group_select frm6">
<?php
$group_opts = FrmMlcmpAppController::get_group_options( $group['list_id'], $group['id'] );
if ( ! isset( $group_opts['interests'] ) ) {
	// A timeout may have occurred
	return;
}

foreach ( $group_opts['interests'] as $g ) {
	if ( ! isset( $new_field ) ) {
		continue;
	}
	?>
	<p class="frm_form_field">
		<label>
			Add to group
			<strong><?php echo esc_html( $g['name'] ); ?></strong>
			when field value is
		</label>
		<span class="frm_show_selected_values_<?php echo esc_attr( $group['id'] ); ?>" class="no_taglist">
        <?php
		// Get selected value
		if ( isset( $list_options['groups'][ $group['id'] ]) && isset( $list_options['groups'][ $group['id'] ][ $g['id'] ] ) ) {
			$val = $list_options['groups'][ $group['id'] ][ $g['id'] ];
		} else {
			$val = '';
		}

		$field_name = $action_control->get_field_name( 'groups' ) . '[' . $group['id'] . '][' . $g['id'] . ']';

		if ( is_callable( 'FrmFieldsHelper::display_field_value_selector' ) ) {
			$selector_args = array(
				'html_name' => $field_name,
				'value'     => $val,
				'source'    => 'form_actions',
			);
			FrmFieldsHelper::display_field_value_selector( $new_field->id, $selector_args );
		} else {
			// For reverse compatibility
			$field_id = $field_name;
			include( FrmAppHelper::plugin_path() . '/pro/classes/views/frmpro-fields/field-values.php' );
		}
		?>
		</span>
    </p>
	<?php
	unset( $g );
}
?>
    <div class="clear"></div>
</div>

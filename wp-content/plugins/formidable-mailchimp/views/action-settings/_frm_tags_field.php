<p class="frm_has_shortcodes frm6">
	<label for="<?php echo esc_attr( $action_control->get_field_id( 'fields' ) ); ?>">
		<?php echo esc_html( $list_field['name'] ); ?>
	</label>
	<input type="text" name="<?php echo esc_attr( $action_control->get_field_name( 'fields' ) ); ?>[<?php echo esc_attr( $list_field['tag'] ); ?>]" class="frm_not_email_subject large-text" id="<?php echo esc_attr( $action_control->get_field_id( 'fields' ) ); ?>" value="<?php
		if ( isset( $list_options['fields'][ $list_field['tag'] ] ) ) {
			echo esc_attr( $list_options['fields'][ $list_field['tag'] ] );
		}
		?>" data-sep="," />
</p>

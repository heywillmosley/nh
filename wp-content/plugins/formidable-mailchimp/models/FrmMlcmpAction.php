<?php

class FrmMlcmpAction extends FrmFormAction {

	private $switch_ids = false;

	function __construct() {
		$action_ops = array(
			'classes'  => 'frm_mailchimp_icon frm_icon_font frm-inverse',
			'limit'    => 99,
			'active'   => true,
			'priority' => 25,
			'event'    => array( 'create', 'update' ),
			'color'    => 'var(--dark-grey)',
		);

		$this->FrmFormAction( 'mailchimp', __( 'Add to MailChimp', 'frmmlcmp' ), $action_ops );
	}

	function form( $form_action, $args = array() ) {

		$list_options = $form_action->post_content;
		$list_id      = $list_options['list_id'];

		$lists  = FrmMlcmpAppController::get_lists();

		if ( $lists !== null && $list_id ) {
			$groups = FrmMlcmpAppController::get_groups( $list_id );
			$list_fields = FrmMlcmpAppController::get_list_fields( $list_id );

			FrmMlcmpAppHelper::add_tags_field( $list_fields );

			$form_fields = FrmField::getAll( 'fi.form_id=' . (int) $args['form']->id . " and fi.type not in ('break', 'divider', 'end_divider', 'html', 'captcha', 'form')", 'field_order' );
		}
		$action_control = $this;

		extract( $args );
		include( FrmMlcmpAppHelper::plugin_path() . '/views/action-settings/mailchimp_options.php' );
	}

	function get_defaults() {
		return array(
			'list_id' => '',
			'address_action' => 'subscribe',
			'optin'   => false,
			'fields'  => array(),
			'groups'  => array(),
		);
	}

	function get_switch_fields() {
		return array(
			'fields' => array(),
			'groups' => array( array( 'id' ) ),
		);
	}

	/**
	 * Migrate the settings for a specific form to an action
	 *
	 * @since 2.0
	 *
	 * @param $form
	 */
	public function migrate_settings_to_action( $form ) {
		$original_options = $form->options;

		foreach ( (array) $original_options['mlcmp_list'] as $list_id => $list_options ) {
			$form->options['list_id'] = $list_id;
			$form->options              = array_merge( $form->options, $list_options );

			// Unset options that are not unset in migrate_to_2
			unset( $form->options['mailchimp'], $form->options['mlcmp_list'] );

			$this->migrate_to_2( $form, 'update' );
			$form->options = $original_options;
		}
	}

	public function migrate_values( $action, $form ) {
		if ( ! empty( $form->options['hide_field'] ) ) {
			$action->post_content['conditions']['send_stop'] = 'send';
			foreach ( $form->options['hide_field'] as $k => $field_id ) {
				$action->post_content['conditions'][] = array(
					'hide_field'      => $field_id,
					'hide_field_cond' => isset( $form->options['hide_field_cond'][ $k ] ) ? $form->options['hide_field_cond'][ $k ] : '==',
					'hide_opt'        => isset( $form->options['hide_opt'][ $k ] ) ? $form->options['hide_opt'][ $k ] : '',
				);
			}
			unset( $action->post_content['hide_field'], $action->post_content['hide_field_cond'] );
			unset( $action->post_content['hide_opt'] );
		}
		$action->post_content['event'] = array( 'create', 'update' );

		if ( $this->switch_ids ) {
			$action->post_content = $this->switch_action_field_ids( $action->post_content );
		}

		return $action;
	}

	/**
	 * Determine whether the current action needs field IDs switched out
	 *
	 * @since 2.0
	 *
	 * @param boolean $switch
	 */
	public function set_switch( $switch ) {
		$this->switch_ids = $switch;
	}

	/**
	 * Switch field IDs in an action
	 *
	 * @since 2.0
	 *
	 * @param array $post_content
	 *
	 * @return array
	 */
	private function switch_action_field_ids( $post_content ) {
		global $frm_duplicate_ids;

		// If there aren't IDs that were switched, end now
		if ( ! $frm_duplicate_ids ) {
			return $post_content;
		}

		// Get old IDs
		$old = array_keys( $frm_duplicate_ids );

		// Get new IDs
		$new = array_values( $frm_duplicate_ids );

		$post_content = $this->replace_field_ids( $new, $old, $post_content );

		return $post_content;
	}

	/**
	 * Replace old field IDs with new field IDs in the post content
	 *
	 * @since 2.0
	 *
	 * @param array $new
	 * @param array $old
	 * @param array $post_content
	 *
	 * @return array
	 */
	private function replace_field_ids( $new, $old, $post_content ) {
		foreach ( $post_content as $key => $setting ) {
			if ( is_numeric( $setting ) && $setting ) {
				$post_content[ $key ] = str_replace( $old, $new, $setting );
			} else if ( is_array( $setting ) ) {
				$post_content[ $key ] = $this->replace_field_ids( $new, $old, $setting );
			}
		}

		return $post_content;
	}
}

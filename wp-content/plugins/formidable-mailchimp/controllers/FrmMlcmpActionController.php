<?php

class FrmMlcmpActionController {

	/**
	 * Migrate mailchimp settings to action after import
	 *
	 * @since 2.0
	 *
	 * @param int $form_id
	 * @param array $form
	 */
	public static function migrate_settings_to_action_after_import( $form_id, $form ) {
		if ( ! isset( $form['options']['mailchimp'] ) || ! $form['options']['mailchimp'] ) {
			return;
		}

		$form = FrmForm::getOne( $form_id );

		self::migrate_settings_to_action( $form, true );
	}

	/**
	 * Migrate mailchimp settings to new actions
	 *
	 * @since 2.0
	 *
	 * @param object $form
	 * @param bool $switch
	 */
	public static function migrate_settings_to_action( $form, $switch = false ) {
		if ( ! isset( $form->options['mailchimp'] ) || ! $form->options['mailchimp'] || empty( $form->options['mlcmp_list'] ) ) {
			return;
		}

		$mailchimp_action = new FrmMlcmpAction();
		$mailchimp_action->set_switch( $switch );
		$mailchimp_action->migrate_settings_to_action( $form );
	}
}
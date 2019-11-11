<?php

/**
 * @since 2.02
 */
class FrmMlcmpActionHelper {

	/**
	 * Check if any MailChimp actions exist in database
	 *
	 * @since 2.02
	 *
	 * @return bool
	 */
	public static function mailchimp_action_exists_in_db() {
		$where = array(
			'post_type'    => FrmFormActionsController::$action_post_type,
			'post_excerpt' => 'mailchimp',
			'post_status' => 'publish',
		);

		$action_id = FrmDb::get_var( 'posts', $where, 'ID', array(), 1 );

		return $action_id !== null;
	}

}
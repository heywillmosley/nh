<?php

class FrmMlcmpDb {

	private $new_db_version = 2;
	private $current_db_version = 0;
	private $option_name = 'frm_mlcmp_db';

	/**
	 * @var array
	 * @since 2.02
	 */
	private $migrations = array( 2 );

	public function __construct() {
		$this->set_new_db_version();
		$this->set_current_db_version();

		if ( $this->is_initial_install() ) {
			$this->initialize_db();
		}
	}

	/**
	 * Set the new db version property
	 *
	 * @since 2.02
	 */
	private function set_new_db_version() {
		$this->new_db_version = (int) end( $this->migrations );
	}

	/**
	 * Set the current db version property
	 * If it does not exist yet, this property will be set to zero
	 *
	 * @since 2.02
	 */
	private function set_current_db_version() {
		$this->current_db_version = (int) get_option( $this->option_name );
	}

	/**
	 * Determine if this is an initial install
	 *
	 * @since 2.02
	 *
	 * @return bool
	 */
	private function is_initial_install() {
		$is_first_install = false;

		if ( $this->current_db_version === 0 ) {

			$saved_settings = get_option( 'frm_mlcmp_options' );
			if ( $saved_settings === false && ! FrmMlcmpActionHelper::mailchimp_action_exists_in_db() ) {
				$is_first_install = true;
			}
		}

		return $is_first_install;
	}

	/**
	 * Initialize the database
	 *
	 * @since 2.02
	 */
	private function initialize_db() {
		$this->update_db_version();
	}

	/**
	 * Save the db version to the database
	 *
	 * @since 2.02
	 */
	private function update_db_version() {
		update_option( $this->option_name, $this->new_db_version );
		$this->current_db_version = $this->new_db_version;
	}

	/**
	 * Check if MailChimp settings need migrating
	 *
	 * @since 2.0
	 * @return bool
	 */
	public function need_to_migrate_settings() {
		return $this->current_db_version < $this->new_db_version;
	}

	/**
	 * Migrate data to current version, if needed
	 *
	 * @since 2.0
	 */
	public function migrate() {
		$this->migrate_to_new_version();
		$this->update_db_version();
	}


	/**
	 * Go through all necessary migrations in order to migrate db to the current version
	 *
	 * @since 2.0
	 */
	private function migrate_to_new_version() {
		foreach ( $this->migrations as $migrate_to_version ) {
			if ( $this->current_db_version < $migrate_to_version ) {
				$function_name = 'migrate_to_' . $migrate_to_version;
				$this->$function_name();
			}
		}
	}

	/**
	 * Convert settings to MailChimp action
	 * Update group settings for 3.0 API
	 *
	 * @since 2.0
	 */
	private function migrate_to_2() {
		$forms = FrmForm::getAll();
		foreach ( $forms as $form ) {
			FrmMlcmpActionController::migrate_settings_to_action( $form );
		}

		// Migrate old group settings to new group settings
		$action_control = FrmFormActionsController::get_form_actions( 'mailchimp' );
		$form_actions   = $action_control->get_all();

		foreach ( $form_actions as $form_action ) {
			if ( isset( $form_action->post_content['groups'] ) && ! empty( $form_action->post_content['groups'] ) ) {
				$list_id        = $form_action->post_content['list_id'];
				$updated_groups = array();
				foreach ( $form_action->post_content['groups'] as $group_id => $group_settings ) {
					self::get_new_group_settings( compact( 'group_id', 'group_settings', 'list_id' ), $updated_groups );
				}
				if ( ! empty( $updated_groups ) ) {
					$form_action->post_content['groups'] = $updated_groups;
					$action_control->save_settings( (array) $form_action );
				}
			}
		}
	}

	private static function get_new_group_settings( $args, &$updated_groups ) {
		if ( is_numeric( $args['group_id'] ) && isset( $args['group_settings']['id'] ) && is_numeric( $args['group_settings']['id'] ) ) {

			$old_group_ids = get_option( 'frm_mlcmp_groups' );
			if ( $old_group_ids && isset( $old_group_ids[ $args['list_id'] ] ) ) {
				$old_groups = $old_group_ids[ $args['list_id'] ];
			} else {
				$old_groups = array();
			}

			$new_group_id = array_search( $args['group_id'], $old_groups );

			if ( $new_group_id ) {
				$group_opts         = FrmMlcmpAppController::get_group_options( $args['list_id'], $new_group_id );
				$new_group_settings = array( 'id' => $args['group_settings']['id'] );

				foreach ( $group_opts['interests'] as $group_opt ) {
					if ( isset( $args['group_settings'][ $group_opt['name'] ] ) ) {
						$new_group_settings[ $group_opt['id'] ] = $args['group_settings'][ $group_opt['name'] ];
					}
				}
				$updated_groups[ $new_group_id ] = $new_group_settings;
			}
		}
	}
}

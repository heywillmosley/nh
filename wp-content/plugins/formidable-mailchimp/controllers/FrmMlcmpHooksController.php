<?php

class FrmMlcmpHooksController {

	public static function load_hooks() {
		if ( ! FrmMlcmpAppHelper::is_formidable_compatible() ) {
			self::load_basic_admin_hooks();

			return;
		}

		add_action( 'frm_trigger_mailchimp_action', 'FrmMlcmpAppController::trigger_mailchimp', 10, 3 );
		add_action( 'frm_registered_form_actions', 'FrmMlcmpSettingsController::register_actions' );

		self::load_admin_hooks();
	}

	public static function load_admin_hooks() {
		if ( ! is_admin() ) {
			return;
		}

		self::load_basic_admin_hooks();

		add_action( 'admin_init', 'FrmMlcmpAppController::initialize', 0 );
		add_action( 'admin_init', 'FrmMlcmpAppHelper::enqueue_admin_js', 1 );

		// Global settings
		add_action( 'frm_add_settings_section', 'FrmMlcmpSettingsController::add_settings_section' );

		// Ajax functions
		add_action( 'wp_ajax_frmmlcmp_install', 'FrmMlcmpAppController::initialize' );
		add_action( 'wp_ajax_frm_mlcmp_match_fields', 'FrmMlcmpSettingsController::match_fields' );
		add_action( 'wp_ajax_frm_mlcmp_get_group_values', 'FrmMlcmpSettingsController::get_group_values' );
		add_action( 'wp_ajax_frm_mlcmp_check_apikey', 'FrmMlcmpSettingsController::check_apikey' );

		// Importing
		add_action( 'frm_after_import_form', 'FrmMlcmpActionController::migrate_settings_to_action_after_import', 10, 2 );

	}

	/**
	 * Load the basic admin hooks to allow updating and display notices
	 *
	 * @since 2.0
	 */
	private static function load_basic_admin_hooks() {
		add_action( 'admin_init', 'FrmMlcmpAppController::include_updater', 0 );
		add_action( 'admin_notices', 'FrmMlcmpAppController::display_admin_notices' );
		add_action( 'after_plugin_row_formidable-mailchimp/formidable-mailchimp.php', 'FrmMlcmpAppController::min_version_notice' );
	}

}
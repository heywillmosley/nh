<?php

/**
 * Helper class for overriding core templates with user
 * defined templates.
 *
 * @since 1.5.7.
 */
final class FLBuilderTemplatesOverride {

	/** 
	 * Renders the admin settings.
	 *
	 * @since 1.5.7
	 * @return void
	 */
	static public function render_admin_settings()
	{
		if ( is_network_admin() || ! is_multisite() ) {
			
			$site_id = self::get_source_site_id();
			
			include FL_BUILDER_DIR . 'includes/admin-settings-templates-override.php';
		}
	}

	/** 
	 * Saves the admin settings.
	 *
	 * @since 1.5.7
	 * @return void
	 */
	static public function save_admin_settings()
	{
		global $wpdb;
		
		if ( is_network_admin() ) {
			
			$templates_override = sanitize_text_field( $_POST['fl-templates-override'] );
			
			if ( empty( $templates_override ) ) {
				$templates_override = false;
			}
			else if ( ! is_numeric( $templates_override ) ) {
				$templates_override = false;
				FLBuilderAdminSettings::add_error( __( "Error! Please enter a number for the site ID.", 'fl-builder' ) );
			}
			else if ( ! FLBuilderMultisite::blog_exists( $templates_override ) ) {
				$templates_override = false;
				FLBuilderAdminSettings::add_error( __( "Error! A site with that ID doesn't exist.", 'fl-builder' ) );
			}
			
			update_site_option( '_fl_builder_templates_override', $templates_override );
		}
		else if ( ! is_multisite() ) {
			
			if ( isset( $_POST['fl-templates-override'] ) ) {
				$templates_override = 1;
			}
			else {
				$templates_override = false;
			}
			
			update_site_option( '_fl_builder_templates_override', $templates_override );
		}
	}

	/** 
	 * Returns the ID of the source site or false.
	 *
	 * @since 1.5.7
	 * @return int|bool
	 */
	static public function get_source_site_id()
	{
		return get_site_option( '_fl_builder_templates_override', false );
	}

	/** 
	 * Returns data for overriding core templates in
	 * the template selector.
	 *
	 * @since 1.5.7
	 * @return array|bool
	 */
	static public function get_selector_data()
	{
		$site_id = self::get_source_site_id();
		
		if ( $site_id ) {	
			
			if ( is_multisite() ) {
				switch_to_blog( $site_id );
			}
			
			$data = FLBuilderModel::get_user_templates();
			
			if ( is_multisite() ) {
				restore_current_blog();
			}
		
			return count( $data['templates'] ) > 0 ? $data : false;
		}
		
		return false;
	}

	/** 
	 * Applys a user defined template instead 
	 * of a core template.
	 *
	 * @since 1.5.7
	 * @param int $template_id The post ID of the template to apply.
	 * @param bool $append Whether to append the new template or replacing the existing layout.
	 * @return bool
	 */
	static public function apply( $template_id = null, $append = false )
	{
		$site_id = self::get_source_site_id();
		
		if ( $site_id ) {
			
			if ( is_multisite() ) {
				switch_to_blog( $site_id );
			}
			
			$template_data = FLBuilderModel::get_layout_data( 'published', $template_id );
			
			if ( is_multisite() ) {
				restore_current_blog();
			}
			
			FLBuilderModel::apply_user_template( $template_id, $append, $template_data );
			
			return true;
		}
		
		return false;
	}
}
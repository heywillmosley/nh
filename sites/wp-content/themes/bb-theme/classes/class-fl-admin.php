<?php

/**
 * Helper class for admin options.
 *
 * @class FLAdmin
 */
final class FLAdmin {
	
	/**
	 * @method init
	 */
	static public function init() 
	{
		FLAdmin::_update();
	}
	
	/**
	 * @method is_plugin_active
	 */ 
	static public function is_plugin_active( $slug )
	{
		if ( ! function_exists( 'is_plugin_active' ) ) {
			include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		}
		
		return is_plugin_active( $slug . '/' . $slug . '.php' );
	}
	
	/** 
	 * @method _update
	 * @private
	 */
	static private function _update()
	{
		$version = get_site_option( '_fl_automator_version' );
		
		if ( '{FL_THEME_VERSION}' == $version ) {
			return;
		}
		if ( version_compare( $version, FL_THEME_VERSION, '=' ) ) {
			return;
		}
		
		update_site_option( '_fl_automator_version', FL_THEME_VERSION );
	}
}
<?php

/**
 * @class FLCustomizer
 */
final class FLCustomizer {

	/**
	 * @property $_panels
	 * @private
	 */
	static private $_panels = array();

	/**
	 * @property $_presets
	 * @private
	 */
	static private $_presets = array();

	/**
	 * Cache for the get_theme_mods call.
	 *
	 * @property $_mods
	 * @private
	 */
	static private $_mods = false;

	/**
	 * @property $_in_customizer_preview
	 * @private
	 */
	static private $_in_customizer_preview = false;

	/**
	 * @property $_css_key
	 * @private
	 */
	static private $_css_key = 'fl_theme_css_key';

	/**
	 * @method add_panel
	 */
	static public function add_panel( $key, $data )
	{
		self::$_panels[ $key ] = $data;
	}

	/**
	 * @method add_preset
	 */
	static public function add_preset( $key, $data )
	{
		self::$_presets[ $key ] = $data;
	}

	/**
	 * Removes a preset from the presets array.
	 *
	 * @since 1.3.0
	 * @param string $key The key of the preset to remove.
	 * @return void
	 */
	static public function remove_preset( $key )
	{
		unset( self::$_presets[ $key ] );
	}

	/**
	 * @method preview_init
	 */
	static public function preview_init()
	{
		self::$_in_customizer_preview = true;

		self::refresh_css();

		wp_enqueue_script( 'fl-stylesheet', FL_THEME_URL . '/js/stylesheet.js', array(), '', true );
		wp_enqueue_script( 'fl-customizer-preview', FL_THEME_URL . '/js/customizer-preview.js', array(), '', true );
	}

	/**
	 * @method controls_enqueue_scripts
	 */
	static public function controls_enqueue_scripts()
	{
		wp_enqueue_style( 'fl-customizer', FL_THEME_URL . '/css/customizer.css', array(), FL_THEME_VERSION );
		wp_enqueue_script( 'fl-customizer-toggles', FL_THEME_URL . '/js/customizer-toggles.js', array(), FL_THEME_VERSION, true );
		wp_enqueue_script( 'fl-customizer', FL_THEME_URL . '/js/customizer.js', array(), FL_THEME_VERSION, true );
	}

	/**
	 * @method controls_print_footer_scripts
	 */
	static public function controls_print_footer_scripts()
	{
		// Opening script tag
		echo '<script>';

		// Fonts
		FLFonts::js();

		// Defaults
		echo 'var FLCustomizerPresetDefaults = ' . json_encode( self::_get_default_preset_mods() ) . ';';

		// Presets
		echo 'var FLCustomizerPresets = ' . json_encode( self::$_presets ) . ';';

		// Closing script tag
		echo '</script>';
	}

	/**
	 * @method register
	 */
	static public function register( $customizer )
	{
		require_once FL_THEME_DIR . '/classes/class-fl-customizer-control.php';

		self::_register_presets( $customizer );
		self::_register_panels( $customizer );
		self::_register_export_import_section( $customizer );
		self::_move_builtin_sections( $customizer );
	}

	/**
	 * @method save
	 */
	static public function save( $customizer )
	{
		self::refresh_css();
	}

	/**
	 * @method get_mods
	 */
	static public function get_mods()
	{
		// We don't have mods yet, get them from the database.
		if ( ! self::$_mods ) {

			// Get preset preview mods.
			if ( self::is_preset_preview() ) {
				$mods = self::_get_preset_preview_mods();
			}
			// Get saved mods.
			else {

				// Get the settings.
				$mods = get_theme_mods();

				// Merge default mods.
				$mods = self::_merge_mods( 'default', $mods );
			}

			// No mods! Get defaults.
			if ( ! $mods ) {
				$mods = self::_get_default_mods();
			}
		}
		// We have cached the mods already.
		else {
			$mods = self::$_mods;
		}

		// Hack to insure the mod values are the same as the customzier
		// values since get_theme_mods doesn't return the correct values
		// while in the customizer. See https://core.trac.wordpress.org/ticket/24844
		if ( self::is_customizer_preview() ) {
			foreach ( $mods as $key => $val ) {
				$mods[ $key ] = apply_filters( 'theme_mod_' . $key, $mods[ $key ] );
			}
		}

		return $mods;
	}

	/**
	 * @method css_url
	 */
	static public function css_url()
	{
		// Get the cache dir and css key.
		$cache_dir = self::get_cache_dir();
		$css_slug  = self::_css_slug();
		$css_key   = get_option( self::$_css_key . '-' . $css_slug );
		$css_path  = $cache_dir['path'] . $css_slug . '-' . $css_key . '.css';
		$css_url   = $cache_dir['url'] . $css_slug . '-' . $css_key . '.css';

		// No css key, recompile the css.
		if ( ! $css_key ) {
			self::_compile_css();
			return self::css_url();
		}

		// Check to see if the file exists.
		if ( ! file_exists( $css_path ) ) {
			self::_compile_css();
			return self::css_url();
		}

		// Return the url.
		return $css_url;
	}

	/**
	 * @method refresh_css
	 */
	static public function refresh_css()
	{
		self::_clear_css_cache();
		self::_compile_css();
	}

	/**
	 * @method is_preset_preview
	 */
	static public function is_preset_preview()
	{
		if ( ! isset( $_GET['fl-preview'] ) ) {
			return false;
		}
		if ( ! isset( self::$_presets[ $_GET['fl-preview'] ] ) ) {
			return false;
		}
		else if ( current_user_can('manage_options') || self::_is_demo_server() ) {
			return true;
		}

		return false;
	}

	/**
	 * @method is_customizer_preview
	 */
	static public function is_customizer_preview()
	{
		return self::$_in_customizer_preview;
	}

	/**
	 * @method sanitize_number
	 */
	static public function sanitize_number( $val )
	{
		return is_numeric( $val ) ? $val : 0;
	}

	/**
	 * @method get_cache_dir
	 */
	static public function get_cache_dir()
	{
		$dir_name   = basename( FL_THEME_DIR );
		$wp_info    = wp_upload_dir();

		// SSL workaround.
		if ( FLTheme::is_ssl() ) {
			$wp_info['baseurl'] = str_ireplace( 'http://', 'https://', $wp_info['baseurl'] );
		}

		// Build the paths.
		$dir_info   = array(
			'path'      => $wp_info['basedir'] . '/' . $dir_name . '/',
			'url'       => $wp_info['baseurl'] . '/' . $dir_name . '/'
		);

		// Create the cache dir if it doesn't exist.
		if ( ! file_exists( $dir_info['path'] ) ) {
			mkdir( $dir_info['path'] );
		}

		return $dir_info;
	}

	/**
	 * @method _register_presets
	 * @private
	 */
	static private function _register_presets( $customizer )
	{
		// Presets section
		$customizer->add_section( 'fl-presets', array(
			'title'    => _x( 'Presets', 'Customizer section title. Theme design/style presets.', 'fl-automator' ),
			'priority' => 0
		) );

		// Presets setting
		$customizer->add_setting( 'fl-preset', array(
			'default' => 'default'
		));

		// Presets choices
		$choices = array();

		foreach ( self::$_presets as $key => $val ) {
			$choices[ $key ] = $val['name'];
		}

		// Presets control
		$customizer->add_control( new WP_Customize_Control( $customizer, 'fl-preset', array(
			'section'       => 'fl-presets',
			'settings'      => 'fl-preset',
			'description'   => __( 'Start by selecting a preset for your theme.', 'fl-automator' ),
			'type'          => 'select',
			'choices'       => $choices
		)));
	}

	/**
	 * @method _register_panels
	 * @private
	 */
	static private function _register_panels( $customizer )
	{
		$panel_priority     = 1;
		$section_priority   = 1;
		$option_priority    = 1;

		// Loop panels
		foreach ( self::$_panels as $panel_key => $panel_data ) {

			// Add panel
			if ( self::_has_panel_support() ) {
				$customizer->add_panel( $panel_key, array(
					'title'    => $panel_data['title'],
					'priority' => $panel_priority
				));
			}

			// Increment panel priority
			$panel_priority++;

			// Loop panel sections
			if ( isset( $panel_data['sections'] ) ) {

				foreach ( $panel_data['sections'] as $section_key => $section_data ) {

					// Add section
					$customizer->add_section( $section_key, array(
						'panel'    => $panel_key,
						'title'    => $section_data['title'],
						'priority' => $section_priority
					));

					// Increment section priority
					$section_priority++;

					// Loop section options
					if ( isset( $section_data['options'] ) ) {

						foreach ( $section_data['options'] as $option_key => $option_data ) {

							// Add setting
							if ( ! isset( $option_data['setting'] ) ) {
								$option_data['setting'] = array( 'default' => '' );
							}

							$customizer->add_setting( $option_key, $option_data['setting'] );

							// Add control
							$option_data['control']['section']  = $section_key;
							$option_data['control']['settings'] = $option_key;
							$option_data['control']['priority'] = $option_priority;
							$customizer->add_control(
								new $option_data['control']['class']( $customizer, $option_key, $option_data['control'] )
							);

							// Increment option priority
							$option_priority++;
						}

						// Reset option priority
						$option_priority = 0;
					}
				}

				// Reset section priority on if we have panel support.
				if ( self::_has_panel_support() ) {
					$section_priority = 0;
				}
			}
		}
	}

	/**
	 * @method _register_export_import_section
	 * @private
	 */
	static private function _register_export_import_section( $customizer )
	{
		if ( ! class_exists( 'CEI_Core' ) && current_user_can( 'install_plugins' ) ) {

			$customizer->add_section( 'fl-export-import', array(
				'title'    => _x( 'Export/Import', 'Customizer section title.', 'fl-automator' ),
				'priority' => 10000000
			) );

			$customizer->add_setting( 'fl-export-import', array(
				'default' => '',
				'type'    => 'none'
			));

			$customizer->add_control( new FLCustomizerControl(
				$customizer,
				'fl-export-import',
				array(
					'section'       => 'fl-export-import',
					'type'          => 'export-import',
					'priority'      => 1
				)
			));
		}
	}

	/**
	 * @method _has_panel_support
	 * @private
	 */
	static private function _has_panel_support()
	{
		return method_exists( 'WP_Customize_Manager' , 'add_panel' );
	}

	/**
	 * @method _move_builtin_sections
	 * @private
	 */
	static private function _move_builtin_sections( $customizer )
	{
		$title_tagline      = $customizer->get_section( 'title_tagline' );
		$nav                = $customizer->get_section( 'nav' );
		$static_front_page  = $customizer->get_section( 'static_front_page' );

		// Set new panels or set a low priority.
		if ( self::_has_panel_support() ) {
			$title_tagline->panel       = 'fl-settings';
			$nav->panel                 = 'fl-settings';
			$static_front_page->panel   = 'fl-settings';
		}
		else {
			$title_tagline->priority      = 10000;
			$nav->priority                = 10001;
			$static_front_page->priority  = 10002;
		}
	}

	/**
	 * @method _get_default_mods
	 * @private
	 */
	static private function _get_default_mods()
	{
		$mods = array();

		// Loop through the panels.
		foreach ( self::$_panels as $panel ) {

			if ( ! isset( $panel['sections'] ) ) {
				continue;
			}

			// Loop through the panel sections.
			foreach ( $panel['sections'] as $section ) {

				if ( ! isset( $section['options'] ) ) {
					continue;
				}

				// Loop through the section options.
				foreach ( $section['options'] as $option_id => $option ) {
					$mods[ $option_id ] = isset( $option['setting']['default'] ) ? $option['setting']['default'] : '';
				}
			}
		}

		return $mods;
	}

	/**
	 * @method _get_default_preset_mods
	 * @private
	 */
	static private function _get_default_preset_mods()
	{
		$keys       = array();
		$defaults   = self::_get_default_mods();
		$mods       = array();

		foreach ( self::$_presets as $preset => $data ) {

			foreach ( $data['settings'] as $key => $val ) {

				if ( ! in_array( $key, $keys ) ) {
					$keys[] = $key;
				}
			}
		}

		foreach ( $keys as $key ) {
			$mods[ $key ] = $defaults[ $key ];
		}

		return $mods;
	}

	/**
	 * @method _get_preset_preview_mods
	 * @private
	 */
	static private function _get_preset_preview_mods()
	{
		if ( self::is_preset_preview() ) {

			$preset_slug                       = $_GET['fl-preview'];
			$preset                            = self::$_presets[ $preset_slug ];
			$preset['settings']['fl-preset']   = $_GET['fl-preview'];

			if ( current_user_can('manage_options' ) ) {
				return self::_merge_mods( 'saved', $preset['settings'] );
			}
			else if ( self::_is_demo_server() ) {
				return self::_merge_mods( 'default', $preset['settings'] );
			}

			return false;
		}
	}

	/**
	 * @method _is_demo_server
	 * @private
	 */
	static private function _is_demo_server()
	{
		return stristr( $_SERVER['HTTP_HOST'], 'demos.wpbeaverbuilder.com' );
	}

	/**
	 * @method _merge_mods
	 * @private
	 */
	static private function _merge_mods( $merge_with = 'default', $mods = null )
	{
		if ( ! $mods ) {
			return false;
		}
		else if ( $merge_with == 'default' ) {
			$new_mods = self::_get_default_mods();
		}
		else if ( $merge_with == 'saved' ) {
			$new_mods = get_theme_mods();
			$new_mods = self::_merge_mods( 'default', $new_mods );
		}

		foreach ( $mods as $mod_id => $mod ) {
			$new_mods[ $mod_id ] = $mod;
		}

		return $new_mods;
	}

	/**
	 * @method _clear_css_cache
	 * @private
	 */
	static private function _clear_css_cache()
	{
		$dir_name   = basename( FL_THEME_DIR );
		$cache_dir  = self::get_cache_dir();
		$css_slug   = self::_css_slug();

		if ( ! empty( $cache_dir['path'] ) && stristr( $cache_dir['path'], $dir_name ) ) {

			$css = glob( $cache_dir['path'] . $css_slug . '-*' );

			foreach ( $css as $file ) {
				if ( is_file( $file ) ) {
					unlink( $file );
				}
			}
		}
	}

	/**
	 * @method _css_slug
	 * @private
	 */
	static private function _css_slug()
	{
		if ( self::is_preset_preview() ) {
			$slug = 'preview-' . $_GET['fl-preview'];
		}
		else if ( self::is_customizer_preview() ) {
			$slug = 'customizer';
		}
		else {
			$slug = 'skin';
		}

		return $slug;
	}

	/**
	 * @method _compile_css
	 * @private
	 */
	static private function _compile_css()
	{
		$theme_info   = wp_get_theme();
		$mods         = self::get_mods();
		$preset       = isset( $mods['fl-preset'] ) ? $mods['fl-preset'] : 'default';
		$cache_dir    = self::get_cache_dir();
		$new_css_key  = uniqid();
		$css_slug     = self::_css_slug();
		$css          = '';

		// Theme stylesheet
		$css .= file_get_contents( FL_THEME_DIR . '/less/theme.less' );

		// WooCommerce
		$css .= file_get_contents( FL_THEME_DIR . '/less/woocommerce.less' );

		// Skin
		if ( isset( self::$_presets[ $preset ]['skin'] ) ) {
		
			$skin = self::$_presets[ $preset ]['skin'];
			
			if ( stristr( $skin, '.css' ) || stristr( $skin, '.less' ) ) {
				$skin_file = $skin;
			}
			else {
				$skin_file = FL_THEME_DIR . '/less/skin-' . $skin . '.less';
			}
			
			if ( file_exists( $skin_file ) ) {
				$css .= file_get_contents( $skin_file );
			}
		}

		// Replace {FL_THEME_URL} placeholder.
		$css = str_replace( '{FL_THEME_URL}', FL_THEME_URL, $css );

		// Compile LESS
		$css = self::_compile_less( $css );

		// Compress
		$css = preg_replace( '!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $css );
		$css = str_replace( array( "\r\n", "\r", "\n", "\t", '  ', '    ', '    ' ), '', $css );

		// Save the new css.
		file_put_contents( $cache_dir['path'] . $css_slug . '-' . $new_css_key . '.css', $css );

		// Save the new css key.
		update_option( self::$_css_key . '-' . $css_slug, $new_css_key );
	}

	/**
	 * @method _compile_less
	 * @private
	 */
	static private function _compile_less( $css )
	{
		if ( ! class_exists( 'lessc' ) ) {
			require_once FL_THEME_DIR . '/classes/class-lessc.php';
		}

		$less = new lessc;
		$mods = self::get_mods();

		// Fix issue with IE filters
		$css = preg_replace_callback( '(filter\s?:\s?(.*);)', 'FLCustomizer::_preg_replace_less', $css );

		// Mixins
		$mixins = file_get_contents( FL_THEME_DIR . '/less/mixins.less' );

		// Vars
		$less_vars = self::_get_less_vars();

		// Compile and return
		return $less->compile( $mixins . $less_vars . $css );
	}

	/**
	 * @method _get_less_vars
	 * @private
	 */
	static private function _get_less_vars()
	{
		$mods                                   = self::get_mods();
		$vars                                   = array();
		$vars_string                            = '';

		// Layout
		$boxed                                  = 'boxed' == $mods['fl-layout-width'];
		$shadow_size                            = $mods['fl-layout-shadow-size'];
		$shadow_color                           = $mods['fl-layout-shadow-color'];
		$vars['body-padding']                   = $boxed ? $mods['fl-layout-spacing'] . 'px 0' : '0';
		$vars['page-shadow']                    = $boxed ? '0 0 ' . $shadow_size . 'px ' . $shadow_color : 'none';

		// Accent Color
		$vars['accent-color']                   = FLColor::hex( $mods['fl-accent'] );
		$vars['accent-fg-color']                = FLColor::foreground( $mods['fl-accent'] );

		// Text Colors
		$vars['heading-color']                  = FLColor::hex( $mods['fl-heading-text-color'] );
		$vars['text-color']                     = FLColor::hex( $mods['fl-body-text-color'] );

		// Fonts
		$vars['text-font']                      = self::_get_font_family_string( $mods['fl-body-font-family'] );
		$vars['text-size']                      = $mods['fl-body-font-size'] . 'px';
		$vars['heading-font']                   = self::_get_font_family_string( $mods['fl-heading-font-family'] );
		$vars['heading-weight']                 = $mods['fl-heading-font-weight'];
		$vars['heading-transform']              = $mods['fl-heading-font-format'];
		$vars['h1-size']                        = $mods['fl-h1-font-size'] . 'px';
		$vars['h2-size']                        = $mods['fl-h2-font-size'] . 'px';
		$vars['h3-size']                        = $mods['fl-h3-font-size'] . 'px';
		$vars['h4-size']                        = $mods['fl-h4-font-size'] . 'px';
		$vars['h5-size']                        = $mods['fl-h5-font-size'] . 'px';
		$vars['h6-size']                        = $mods['fl-h6-font-size'] . 'px';
		$vars['logo-font']                      = self::_get_font_family_string( $mods['fl-logo-font-family'] );
		$vars['logo-weight']                    = $mods['fl-logo-font-weight'];
		$vars['logo-size']                      = $mods['fl-logo-font-size'] . 'px';

		// Body Background Image
		$custom                                 = ! empty( $mods['fl-body-bg-image'] );
		$vars['body-bg-image']                  = $custom ? 'url(' . $mods['fl-body-bg-image'] . ')' : 'none';
		$vars['body-bg-repeat']                 = $mods['fl-body-bg-repeat'];
		$vars['body-bg-position']               = $mods['fl-body-bg-position'];
		$vars['body-bg-attachment']             = $mods['fl-body-bg-attachment'];
		$vars['body-bg-size']                   = $mods['fl-body-bg-size'];

		// Content Background Colors
		$vars['body-bg-color']                  = FLColor::hex( $mods['fl-body-bg-color'] );
		$vars['body-bg-color-2']                = FLColor::similar( array( 5, 4, 13 ), $mods['fl-body-bg-color'] );
		$vars['body-fg-color']                  = FLColor::foreground( $mods['fl-body-bg-color'] );

		// Content Border Colors
		$vars['border-color']                   = FLColor::similar( array( 10, 9, 19 ), $mods['fl-content-bg-color'] );
		$vars['border-color-2']                 = FLColor::similar( array( 20, 20, 30 ), $mods['fl-content-bg-color'] );

		// Content Background Colors
		$vars['content-bg-color']               = FLColor::hex( $mods['fl-content-bg-color'] );
		$vars['content-bg-color-2']             = FLColor::similar( array( 1, 4, 13 ), $mods['fl-content-bg-color'] );
		$vars['content-bg-color-3']             = FLColor::similar( array( 3, 9, 18 ), $mods['fl-content-bg-color'] );
		$vars['content-fg-color']               = FLColor::foreground( $mods['fl-content-bg-color'] );

		// Top Bar Background Color
		$type                                   = $mods['fl-topbar-bg-type'];
		$custom_bg                              = $mods['fl-topbar-bg-color'];
		$vars['top-bar-bg-color']               = FLColor::section_bg( $type, $vars['content-bg-color'], $custom_bg );
		$vars['top-bar-fg-color']               = FLColor::section_fg( $type, $vars['body-bg-color'], $custom_bg );
		$vars['top-bar-bg-grad']                = $mods['fl-topbar-bg-gradient'] ? 10 : 0;
		$vars['top-bar-dropdown-bg-color']      = FLColor::hex( array( $vars['top-bar-bg-color'], $vars['body-bg-color'] ) );

		// Header Background Color
		$type                                   = $mods['fl-header-bg-type'];
		$custom_bg                              = $mods['fl-header-bg-color'];
		$vars['header-bg-color']                = FLColor::section_bg( $type, $vars['content-bg-color'], $custom_bg );
		$vars['header-fg-color']                = FLColor::section_fg( $type, $vars['body-bg-color'], $custom_bg );
		$vars['header-bg-grad']                 = $mods['fl-header-bg-gradient'] ? 10 : 0;
		$vars['header-padding']                 = $mods['fl-header-padding'] . 'px';

		// Fixed Header Background Color
		$vars['fixed-header-bg-color']          = FLColor::hex( array( $vars['header-bg-color'], $vars['body-bg-color'] ) );
		$vars['fixed-header-fg-color']          = FLColor::hex( array( $vars['header-fg-color'], $vars['text-color'] ) );
		$vars['fixed-header-accent-color']      = FLColor::hex( array( $vars['header-fg-color'], $vars['accent-color'] ) );

		// Nav Fonts
		if ( 'custom' == $mods['fl-nav-text-type'] ) {
			$vars['nav-font-family']                = self::_get_font_family_string( $mods['fl-nav-font-family'] );
			$vars['nav-font-weight']                = $mods['fl-nav-font-weight'];
			$vars['nav-font-format']                = $mods['fl-nav-font-format'];
			$vars['nav-font-size']                  = $mods['fl-nav-font-size'] . 'px';
		}
		else {
			$vars['nav-font-family']                = $vars['text-font'];
			$vars['nav-font-weight']                = 'normal';
			$vars['nav-font-format']                = 'none';
			$vars['nav-font-size']                  = $vars['text-size'];
		}

		// Right Nav Colors
		if ( 'right' == $mods['fl-header-layout'] || 'none' == $mods['fl-nav-bg-type'] ) {
			$vars['nav-bg-color']               = $vars['header-bg-color'];
			$vars['nav-fg-color']               = $vars['header-fg-color'];
			$vars['nav-bg-grad']                = 0;
		}
		// Bottom and Centered Nav Colors
		else {
			$type                               = $mods['fl-nav-bg-type'];
			$custom_bg                          = $mods['fl-nav-bg-color'];
			$vars['nav-bg-color']               = FLColor::section_bg( $type, $vars['content-bg-color'], $custom_bg );
			$vars['nav-fg-color']               = FLColor::section_fg( $type, $vars['body-bg-color'], $custom_bg );
			$vars['nav-bg-grad']                = $mods['fl-nav-bg-gradient'] ? 5 : 0;
		}

		// Mobile Nav Background Color
		$vars['mobile-nav-bg-color']            = FLColor::hex( array( $vars['header-bg-color'], $vars['body-bg-color'] ) );
		$vars['mobile-nav-bg-color']            = FLColor::similar( array( 5, 10, 18 ), $vars['mobile-nav-bg-color'] );
		$vars['mobile-nav-fg-color']            = FLColor::hex( array( $vars['header-fg-color'], $vars['text-color'] ) );

		// Footer Widgets Background Color
		$type                                   = $mods['fl-footer-widgets-bg-type'];
		$custom_bg                              = $mods['fl-footer-widgets-bg-color'];
		$vars['footer-widgets-bg-color']        = FLColor::section_bg( $type, $vars['content-bg-color'], $custom_bg );
		$vars['footer-widgets-fg-color']        = FLColor::section_fg( $type, $vars['body-bg-color'], $custom_bg );

		// Footer Background Color
		$type                                   = $mods['fl-footer-bg-type'];
		$custom_bg                              = $mods['fl-footer-bg-color'];
		$vars['footer-bg-color']                = FLColor::section_bg( $type, $vars['content-bg-color'], $custom_bg );
		$vars['footer-fg-color']                = FLColor::section_fg( $type, $vars['body-bg-color'], $custom_bg );

		// WooCommerce
		if ( FLAdmin::is_plugin_active( 'woocommerce' ) ) {
			$vars['woo-cats-add-button']        = $mods['fl-woo-cart-button'] == 'hidden' ? 'none' : 'inline-block';
		}

		// Build the vars string
		foreach ( $vars as $key => $value ) {
			$vars_string .= '@' . $key . ':' . $value . ';';
		}

		// Return the vars string
		return $vars_string;
	}

	/**
	 * @method _get_font_family
	 * @private
	 */
	static private function _get_font_family_string( $font )
	{
		$string = '';

		if ( isset( FLFontFamilies::$system[ $font ] ) ) {
			$string = $font . ', ' . FLFontFamilies::$system[ $font ]['fallback'];
		}
		else {
			$string = '"' . $font . '", sans-serif';
		}

		return $string;
	}

	/**
	 * @method _preg_replace_less
	 * @private
	 */
	static private function _preg_replace_less( $matches )
	{
		if ( ! empty( $matches[1] ) ) {
			return 'filter: ~"' . $matches[1] . '";';
		}

		return $matches[0];
	}
}
<?php

/**
 * Helper class for theme functions.
 *
 * @class FLTheme
 */
final class FLTheme {

	/**
	 * @property $fonts
	 * @private
	 */
	static private $fonts;

	/**
	 * @method get_setting
	 */
	static public function get_setting( $key = '' )
	{
		$settings = FLCustomizer::get_mods();

		if ( isset( $settings[ $key ] ) ) {
			return $settings[ $key ];
		}
		else {
			return '';
		}
	}

	/**
	 * @method get_settings
	 */
	static public function get_settings()
	{
		return FLCustomizer::get_mods();
	}

	/**
	 * @method is_ssl
	 */
	static public function is_ssl()
	{
		if ( is_ssl() ) {
			return true;
		}
		else if ( 0 === stripos( get_option( 'siteurl' ), 'https://' ) ) {
			return true;
		}
		else if ( isset( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) && 'https' == $_SERVER['HTTP_X_FORWARDED_PROTO'] ) {
			return true;
		}
		
		return false;
	}

	/**
	 * @method setup
	 */
	static public function setup()
	{
		// Localization (load as first thing before any translation texts)
		// Note: the first-loaded translation file overrides any following ones if the same translation is present.

		//wp-content/languages/theme-name/it_IT.mo
		load_theme_textdomain( 'fl-automator', trailingslashit( WP_LANG_DIR ) . 'themes/' . get_template() );
		
		//wp-content/themes/child-theme-name/languages/it_IT.mo
		load_theme_textdomain( 'fl-automator', get_stylesheet_directory() . '/languages' );
		
		//wp-content/themes/theme-name/languages/it_IT.mo
		load_theme_textdomain( 'fl-automator', get_template_directory() . '/languages' );

		// RSS feed links support
		add_theme_support('automatic-feed-links');

		// Post thumbnail support
		add_theme_support('post-thumbnails');

		// WooCommerce support
		add_theme_support('woocommerce');

		// Nav menus
		register_nav_menus(array(
			'bar'     => __('Top Bar Menu', 'fl-automator'),
			'header'  => __('Header Menu', 'fl-automator'),
			'footer'  => __('Footer Menu', 'fl-automator')
		));

		// Include customizer settings.
		require_once FL_THEME_DIR . '/includes/customizer-panel-general.php';
		require_once FL_THEME_DIR . '/includes/customizer-panel-header.php';
		require_once FL_THEME_DIR . '/includes/customizer-panel-content.php';
		require_once FL_THEME_DIR . '/includes/customizer-panel-footer.php';
		require_once FL_THEME_DIR . '/includes/customizer-panel-widgets.php';
		require_once FL_THEME_DIR . '/includes/customizer-panel-code.php';
		require_once FL_THEME_DIR . '/includes/customizer-panel-settings.php';
		require_once FL_THEME_DIR . '/includes/customizer-presets.php';
	}

	/**
	 * @method enqueue_scripts
	 */
	static public function enqueue_scripts()
	{
		// Fonts
		wp_enqueue_style('font-awesome', FL_THEME_URL . '/css/font-awesome.min.css', array(), FL_THEME_VERSION);
		wp_enqueue_style('mono-social-icons', FL_THEME_URL . '/css/mono-social-icons.css', array(), FL_THEME_VERSION);

		// jQuery
		wp_enqueue_script('jquery');
		wp_enqueue_script('jquery-throttle', FL_THEME_URL . '/js/jquery.throttle.min.js', array(), FL_THEME_VERSION, true);

		// Lightbox
		if(self::get_setting('fl-lightbox') == 'enabled') {
			wp_enqueue_style('jquery-magnificpopup', FL_THEME_URL . '/css/jquery.magnificpopup.css', array(), FL_THEME_VERSION);
			wp_enqueue_script('jquery-magnificpopup', FL_THEME_URL . '/js/jquery.magnificpopup.min.js', array(), FL_THEME_VERSION, true);
		}

		// Threaded Comments
		if(is_singular() && comments_open() && get_option('thread_comments')) {
			wp_enqueue_script('comment-reply');
		}

		// Preview JS
		if(FLCustomizer::is_preset_preview()) {
			wp_enqueue_script('fl-automator-preview', FL_THEME_URL . '/js/preview.js', array(), FL_THEME_VERSION, true);
			wp_localize_script('fl-automator-preview', 'preview', array('preset' => $_GET['fl-preview']));
		}

		// Bootstrap and theme JS
		wp_enqueue_script('bootstrap', FL_THEME_URL . '/js/bootstrap.min.js', array(), FL_THEME_VERSION, true);
		wp_enqueue_script('fl-automator', FL_THEME_URL . '/js/theme.js', array(), FL_THEME_VERSION, true);
	}

	/**
	 * @method widgets_init
	 */
	static public function widgets_init()
	{
		$footer_widgets_display = self::get_setting('fl-footer-widgets-display');
		$woo_layout             = self::get_setting('fl-woo-layout');

		// Primary Sidebar
		register_sidebar(array(
			'name'          => __('Primary Sidebar', 'fl-automator'),
			'id'            => 'blog-sidebar',
			'before_widget' => '<aside id="%1$s" class="fl-widget %2$s">',
			'after_widget'  => '</aside>',
			'before_title'  => '<h4 class="fl-widget-title">',
			'after_title'   => '</h4>'
		));

		// Footer Widgets
		if ( $footer_widgets_display != 'disabled' ) {
			register_sidebars( 4, array(
				'name'          => _x( 'Footer Column %d', 'Sidebar title. %d stands for the order number of the auto-created sidebar, 4 in total.', 'fl-automator' ),
				'id'            => 'footer-col',
				'before_widget' => '<aside id="%1$s" class="fl-widget %2$s">',
				'after_widget'  => '</aside>',
				'before_title'  => '<h4 class="fl-widget-title">',
				'after_title'   => '</h4>'
			) );
		}

		// WooCommerce Sidebar
		if($footer_widgets_display != 'no-sidebar' && FLAdmin::is_plugin_active('woocommerce')) {
			register_sidebar(array(
				'name'          => __('WooCommerce Sidebar', 'fl-automator'),
				'id'            => 'woo-sidebar',
				'before_widget' => '<aside id="%1$s" class="fl-widget %2$s">',
				'after_widget'  => '</aside>',
				'before_title'  => '<h4 class="fl-widget-title">',
				'after_title'   => '</h4>'
			));
		}
	}

	/**
	 * @method title
	 */
	static public function title()
	{
		$sep            = apply_filters('fl_title_separator', ' | ');
		$title          = wp_title($sep, false, 'right');
		$name           = get_bloginfo('name');
		$description    = get_bloginfo('description');

		if(empty($title) && empty($description)) {
			$title = $name;
		}
		else if(empty($title)) {
			$title = $name . ' | ' . $description;
		}
		else if(!empty($name) && !stristr($title, $name)) {
			$title = !stristr($title, $sep) ? $title . $sep . $name : $title . $name;
		}

		echo apply_filters('fl_title', $title);
	}

	/**
	 * @method favicon
	 */
	static public function favicon()
	{
		$favicon    = self::get_setting('fl-favicon');
		$apple      = self::get_setting('fl-apple-touch-icon');

		if ( ! empty( $favicon ) ) {
			echo '<link rel="shortcut icon" href="'. $favicon .'" />' . "\n";
		}
		if ( ! empty( $apple ) ) {
			echo '<link rel="apple-touch-icon" href="'. $apple .'" />' . "\n";
		}
	}

	/**
	 * @method fonts
	 */
	static public function fonts()
	{
		$settings = self::get_settings();

		self::add_font( $settings['fl-body-font-family'], array( 300, 400, 700 ) );
		self::add_font( $settings['fl-heading-font-family'], $settings['fl-heading-font-weight'] );

		if ( $settings['fl-logo-type'] == 'text' ) {
			self::add_font( $settings['fl-logo-font-family'], $settings['fl-logo-font-weight'] );
		}
		if ( $settings['fl-nav-text-type'] == 'custom' ) {
			self::add_font( $settings['fl-nav-font-family'], $settings['fl-nav-font-weight'] );
		}

		self::render_fonts();
	}

	/**
	 * @method add_font
	 */
	static public function add_font($name, $variants = array())
	{
		$protocol   = self::is_ssl() ? 'https' : 'http';
		$google_url = $protocol . '://fonts.googleapis.com/css?family=';

		if(isset(self::$fonts[$name])) {
			foreach((array)$variants as $variant) {
				if(!in_array($variant, self::$fonts[$name]['variants'])) {
					self::$fonts[$name]['variants'][] = $variant;
				}
			}
		}
		else {
			self::$fonts[$name] = array(
				'url'      => isset(FLFontFamilies::$google[$name]) ? $google_url . $name : '',
				'variants' => (array)$variants
			);
		}
	}

	/**
	 * @method render_fonts
	 */
	static public function render_fonts()
	{
		foreach(self::$fonts as $font) {
			if(!empty($font['url'])) {
				echo '<link rel="stylesheet" href="'. $font['url'] . ':'. implode(',', $font['variants']) .'" />' . "\n";
			}
		}
	}

	/**
	 * @method head
	 */
	static public function head()
	{
		$settings  = self::get_settings();

		// Skin
		echo '<link rel="stylesheet" href="' . FLCustomizer::css_url() . '" />' . "\n";

		// RTL Support
		if(is_rtl()) {
			echo '<link rel="stylesheet" href="' . FL_THEME_URL . '/css/rtl.css?ver=' . FL_THEME_VERSION . '" />' . "\n";
		}

		// CSS
		if(!empty($settings['fl-css-code'])) {
			echo '<style>' . $settings['fl-css-code'] . '</style>' . "\n";
		}

		// JS
		if(!empty($settings['fl-js-code'])) {
			echo '<script>' . $settings['fl-js-code'] . '</script>' . "\n";
		}

		// Head
		if(!empty($settings['fl-head-code'])) {
			echo $settings['fl-head-code'] . "\n";
		}

		do_action('fl_head');
	}

	/**
	 * @method body_class
	 */
	static public function body_class($classes)
	{
		$preset = self::get_setting('fl-preset');
		
		// Preset
		if ( empty( $preset ) ) {
			$classes[] = 'fl-preset-default';
		}
		else {
			$classes[] = 'fl-preset-' . $preset;
		}
		
		// Width
		if(self::get_setting('fl-layout-width') == 'full-width') {
			$classes[] = 'fl-full-width';
		}
		else {
			$classes[] = 'fl-fixed-width';
		}

		return $classes;
	}

	/**
	 * @method nav_menu_fallback
	 */
	static public function nav_menu_fallback($args)
	{
		$url  = current_user_can('edit_theme_options') ? admin_url('nav-menus.php') : home_url();
		$text = current_user_can('edit_theme_options') ? __('Choose Menu', 'fl-automator') :  __('Home', 'fl-automator');

		echo '<ul class="fl-page-' . $args['theme_location'] . '-nav nav navbar-nav menu">';
		echo '<li>';
		echo '<a href="' . $url . '">' . $text . '</a>';
		echo '</li>';
		echo '</ul>';
	}

	/**
	 * @method top_bar_col1
	 */
	static public function top_bar_col1()
	{
		$settings   = self::get_settings();
		$layout     = $settings['fl-topbar-layout'];
		$col_layout = $settings['fl-topbar-col1-layout'];
		$col_text   = $settings['fl-topbar-col1-text'];

		if($layout != 'none') {

			if($layout == '1-col') {
				echo '<div class="col-md-12 text-center clearfix">';
			}
			else {
				echo '<div class="col-md-6 col-sm-6 text-left clearfix">';
			}

			if($col_layout == 'social' || $col_layout == 'text-social' || $col_layout == 'menu-social') {
				self::social_icons(false);
			}
			if($col_layout == 'text' || $col_layout == 'text-social') {
				echo '<div class="fl-page-bar-text fl-page-bar-text-1">' . do_shortcode( $col_text ) . '</div>';
			}
			if($col_layout == 'menu' || $col_layout == 'menu-social') {
				wp_nav_menu(array(
					'theme_location' => 'bar',
					'items_wrap' => '<ul id="%1$s" class="fl-page-bar-nav nav navbar-nav %2$s">%3$s</ul>',
					'container' => false,
					'fallback_cb' => 'FLTheme::nav_menu_fallback'
				));
			}

			echo '</div>';
		}
	}

	/**
	 * @method top_bar_col2
	 */
	static public function top_bar_col2()
	{
		$settings = self::get_settings();
		$layout     = $settings['fl-topbar-layout'];
		$col_layout = $settings['fl-topbar-col2-layout'];
		$col_text   = $settings['fl-topbar-col2-text'];

		if($layout == '2-cols') {

			echo '<div class="col-md-6 col-sm-6 text-right clearfix">';

			if($col_layout == 'text' || $col_layout == 'text-social') {
				echo '<div class="fl-page-bar-text fl-page-bar-text-2">' . do_shortcode( $col_text ) . '</div>';
			}
			if($col_layout == 'menu' || $col_layout == 'menu-social') {
				wp_nav_menu(array(
					'theme_location' => 'bar',
					'items_wrap' => '<ul id="%1$s" class="fl-page-bar-nav nav navbar-nav %2$s">%3$s</ul>',
					'container' => false,
					'fallback_cb' => 'FLTheme::nav_menu_fallback'
				));
			}
			if($col_layout == 'social' || $col_layout == 'text-social' || $col_layout == 'menu-social') {
				self::social_icons(false);
			}

			echo '</div>';
		}
	}

	/**
	 * @method top_bar
	 */
	static public function top_bar()
	{
		$top_bar_layout  = self::get_setting('fl-topbar-layout');
		$top_bar_enabled = apply_filters( 'fl_topbar_enabled', true );

		if ( $top_bar_layout != 'none' && $top_bar_enabled ) {
			get_template_part( 'includes/top-bar' );
		}
	}

	/**
	 * @method header_code
	 */
	static public function header_code()
	{
		echo self::get_setting('fl-header-code');
	}

	/**
	 * @method fixed_header
	 */
	static public function fixed_header()
	{
		$header_layout  = self::get_setting( 'fl-fixed-header' );
		$header_enabled = apply_filters( 'fl_fixed_header_enabled', true );

		if ( 'visible' == $header_layout && $header_enabled ) {
			get_template_part( 'includes/fixed-header' );
		}
	}

	/**
	 * @method header_layout
	 */
	static public function header_layout()
	{
		$header_layout  = self::get_setting( 'fl-header-layout' );
		$header_enabled = apply_filters( 'fl_header_enabled', true );

		if ( 'none' != $header_layout && $header_enabled ) {
			get_template_part( 'includes/nav-' . $header_layout );
		}
	}

	/**
	 * @method header_content
	 */
	static public function header_content()
	{
		$settings = self::get_settings();
		$layout   = $settings['fl-header-content-layout'];
		$text     = $settings['fl-header-content-text'];

		if($layout == 'text' || $layout == 'social-text') {
			echo '<div class="fl-page-header-text">'. do_shortcode( $text ) .'</div>';
		}
		if($layout == 'social' || $layout == 'social-text') {
			self::social_icons();
		}
	}

	/**
	 * @method logo
	 */
	static public function logo()
	{
		$logo_type      = self::get_setting( 'fl-logo-type' );
		$logo_image     = self::get_setting( 'fl-logo-image' );
		$logo_retina    = self::get_setting( 'fl-logo-image-retina' );
		$logo_text      = self::get_setting( 'fl-logo-text' );
		
		if ( empty( $logo_text ) || $logo_type == 'image' ) {
			$logo_text = get_bloginfo( 'name' );
		}
		
		if ( $logo_type == 'image' ) {
			echo '<img class="fl-logo-img" itemprop="logo" src="'. $logo_image .'"';
			echo ' data-retina="' . $logo_retina . '"';
			echo ' alt="' . esc_attr( $logo_text ) . '" />';
			echo '<meta itemprop="name" content="' . esc_attr( $logo_text ) . '" />';
		}
		else {
			echo '<span class="fl-logo-text" itemprop="name">'. do_shortcode( $logo_text ) .'</span>';
		}
	}

	/**
	 * @method nav_search
	 */
	static public function nav_search()
	{
		$nav_search = self::get_setting('fl-header-nav-search');

		if($nav_search == 'visible') {
			get_template_part('includes/nav-search');
		}
	}

	/**
	 * @method social_icons
	 */
	static public function social_icons($circle = true)
	{
		$settings = self::get_settings();

		$keys = array(
			'facebook',
			'twitter',
			'google',
			'linkedin',
			'yelp',
			'pinterest',
			'tumblr',
			'vimeo',
			'youtube',
			'flickr',
			'instagram',
			'dribbble',
			'500px',
			'blogger',
			'github',
			'rss',
			'email'
		);

		echo '<div class="fl-social-icons">';

		foreach($keys as $key) {

			$link_target = ' target="_blank"';

			if(!empty($settings['fl-social-' . $key])) {

				if($key == 'email') {
					$settings['fl-social-' . $key] = 'mailto:' . $settings['fl-social-' . $key];
					$link_target = '';
				}

				$class = 'fl-icon fl-icon-color-'. $settings['fl-social-icons-color'] .' fl-icon-'. $key .' fl-icon-'. $key;
				$class .= $circle ? '-circle' : '-regular';
				echo '<a href="'. $settings['fl-social-' . $key] . '"' . $link_target . ' class="'. $class .'"></a>';
			}
		}

		echo '</div>';
	}

	/**
	 * @method has_footer
	 */
	static public function has_footer()
	{
		$footer_layout  = self::get_setting( 'fl-footer-layout' );
		$footer_enabled = apply_filters( 'fl_footer_enabled', true );

		return $footer_enabled && ( self::has_footer_widgets() || $footer_layout != 'none' );
	}

	/**
	 * @method footer_widgets
	 */
	static public function footer_widgets()
	{
		if(self::has_footer_widgets()) {
			get_template_part('includes/footer-widgets');
		}
	}

	/**
	 * @method has_footer_widgets
	 */
	static public function has_footer_widgets()
	{
		$show = self::get_setting('fl-footer-widgets-display');

		if($show == 'disabled' || (!is_home() && $show == 'home')) {
			return false;
		}

		for($i = 1; $i <= 4; $i++) {

			$id = $i == 1 ? 'footer-col' : 'footer-col-' . $i;

			if(is_active_sidebar($id)) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @method display_footer_widgets
	 */
	static public function display_footer_widgets()
	{
		$active = array();
		$num_active = 0;

		for($i = 1; $i <= 4; $i++) {

			$id = $i == 1 ? 'footer-col' : 'footer-col-' . $i;

			if(is_active_sidebar($id)) {
				$active[] = $id;
				$num_active++;
			}
		}
		if($num_active > 0) {

			$col_length = 12/$num_active;

			for($i = 0; $i < $num_active; $i++) {
				echo '<div class="col-sm-' . $col_length . ' col-md-' . $col_length . '">';
				dynamic_sidebar($active[$i]);
				echo '</div>';
			}
		}
	}

	/**
	 * @method footer
	 */
	static public function footer()
	{
		$footer_layout = self::get_setting('fl-footer-layout');

		if($footer_layout != 'none') {
			get_template_part('includes/footer');
		}
	}

	/**
	 * @method footer_col1
	 */
	static public function footer_col1()
	{
		$settings   = self::get_settings();
		$layout     = $settings['fl-footer-layout'];
		$col_layout = $settings['fl-footer-col1-layout'];
		$col_text   = $settings['fl-footer-col1-text'];

		if($layout != 'none') {

			if($layout == '1-col') {
				echo '<div class="col-md-12 text-center clearfix">';
			}
			else {
				echo '<div class="col-md-6 col-sm-6 text-left clearfix">';
			}

			if($col_layout == 'text' || $col_layout == 'social-text') {
				if(empty($col_text)) {
					get_template_part('includes/copyright');
				}
				else {
					echo '<div class="fl-page-footer-text fl-page-footer-text-1">' . do_shortcode( $col_text ) . '</div>';
				}
			}
			if($col_layout == 'social' || $col_layout == 'social-text') {
				self::social_icons();
			}
			if($col_layout == 'menu') {
				wp_nav_menu(array(
					'theme_location' => 'footer',
					'items_wrap' => '<ul id="%1$s" class="fl-page-footer-nav nav navbar-nav %2$s">%3$s</ul>',
					'container' => false,
					'fallback_cb' => 'FLTheme::nav_menu_fallback'
				));
			}

			echo '</div>';
		}
	}

	/**
	 * @method footer_col2
	 */
	static public function footer_col2()
	{
		$settings   = self::get_settings();
		$layout     = $settings['fl-footer-layout'];
		$col_layout = $settings['fl-footer-col2-layout'];
		$col_text   = $settings['fl-footer-col2-text'];

		if($layout == '2-cols') {

			echo '<div class="col-md-6 col-sm-6 text-right clearfix">';

			if($col_layout == 'text' || $col_layout == 'social-text') {
				echo '<div class="fl-page-footer-text fl-page-footer-text-2">' . do_shortcode( $col_text ) . '</div>';
			}
			if($col_layout == 'social' || $col_layout == 'social-text') {
				self::social_icons();
			}
			if($col_layout == 'menu') {
				wp_nav_menu(array(
					'theme_location' => 'footer',
					'items_wrap' => '<ul id="%1$s" class="fl-page-footer-nav nav navbar-nav %2$s">%3$s</ul>',
					'container' => false,
					'fallback_cb' => 'FLTheme::nav_menu_fallback'
				));
			}

			echo '</div>';
		}
	}

	/**
	 * @method footer_code
	 */
	static public function footer_code()
	{
		echo self::get_setting('fl-footer-code');
	}

	/**
	 * @method sidebar
	 */
	static public function sidebar($position, $section = 'blog')
	{
		$size       = self::get_setting('fl-' . $section . '-sidebar-size');
		$display    = self::get_setting('fl-' . $section . '-sidebar-display');
		$layout     = self::get_setting('fl-' . $section . '-layout');

		if(strstr($layout, $position)) {
			include locate_template('sidebar.php');
		}
	}

	/**
	 * @method content_class
	 */
	static public function content_class($section = 'blog')
	{
		$layout       = self::get_setting('fl-' . $section . '-layout');
		$sidebar_size = self::get_setting('fl-' . $section . '-sidebar-size');
		$content_size = '8';

		if($sidebar_size == '2') {
			$content_size = '10';
		}
		elseif($sidebar_size == '3') {
			$content_size = '9';
		}

		if(strstr($layout, 'left')) {
			echo 'fl-content-right col-md-' . $content_size;
		}
		else if(strstr($layout, 'right')) {
			echo 'fl-content-left col-md-' . $content_size;
		}
		else {
			echo 'col-md-12';
		}
	}

	/**
	 * @method archive_page_header
	 */
	static public function archive_page_header()
	{
		// Category
		if ( is_category() ) {
			$page_title = single_cat_title( '', false );
		}
		// Tag
		else if ( is_tag() ) {
			$page_title = sprintf( _x( 'Posts Tagged &#8216;%s&#8217;', 'Archive title: tag.', 'fl-automator' ), single_tag_title( '', false ) );
		}
		// Day
		else if ( is_day() ) {
			$page_title = sprintf( _x( 'Archive for %s', 'Archive title: day.', 'fl-automator' ), get_the_date() );
		}
		// Month
		else if ( is_month() ) {
			$page_title = sprintf( _x( 'Archive for %s', 'Archive title: month.', 'fl-automator' ), single_month_title( ' ', false ) );
		}
		// Year
		else if ( is_year() ) {
			$page_title = sprintf( _x( 'Archive for %s', 'Archive title: year.', 'fl-automator' ), get_the_time( 'Y' ) );
		}
		// Author
		else if ( is_author() ) {
			$page_title = sprintf( _x( 'Posts by %s', 'Archive title: author.', 'fl-automator' ), get_the_author() );
		}
		// Search
		else if ( is_search() ) {
			$page_title = sprintf( _x( 'Search results for: %s', 'Search results title.', 'fl-automator' ), get_search_query() );
		}
		// Paged
		else if ( isset( $_GET['paged'] ) && ! empty( $_GET['paged'] ) ) {
			$page_title = _x( 'Archives', 'Archive title: paged archive.', 'fl-automator' );
		}
		// Index
		else {
			$page_title = '';
		}

		if(!empty($page_title)) {
			echo '<header class="fl-archive-header">';
			echo '<h1 class="fl-archive-title">' . $page_title . '</h1>';
			echo '</header>';
		}
	}

	/**
	 * @method archive_nav
	 */
	static public function archive_nav()
	{
		global $wp_query;

		if(function_exists('wp_pagenavi')) {
			wp_pagenavi();
		}
		elseif($wp_query->max_num_pages > 1) {
			echo '<nav class="fl-archive-nav clearfix">';
			echo '<div class="fl-archive-nav-prev">' . get_previous_posts_link(__('&laquo; Newer Posts', 'fl-automator')) . '</div>';
			echo '<div class="fl-archive-nav-next">' . get_next_posts_link(__('Older Posts &raquo;', 'fl-automator')) . '</div>';
			echo '</nav>';
		}
	}

	/**
	 * @method excerpt_more
	 */
	static public function excerpt_more($more)
	{
		return '&hellip;';
	}

	/**
	 * @method show_post_header
	 */
	static public function show_post_header()
	{
		if ( class_exists( 'FLBuilderModel' ) && FLBuilderModel::is_builder_enabled() ) {

			$global_settings = FLBuilderModel::get_global_settings();

			if ( ! $global_settings->show_default_heading ) {
				return false;
			}
		}
		
		return true;
	}

	/**
	 * @method post_top_meta
	 */
	static public function post_top_meta()
	{
		global $post;

		$settings       = self::get_settings();
		$show_author    = $settings['fl-blog-post-author'] == 'visible' ? true : false;
		$show_date      = $settings['fl-blog-post-date'] == 'visible' ? true : false;
		$comments       = comments_open() || '0' != get_comments_number();

		// Wrapper
		if($show_author || $show_date || $comments) {
			echo '<div class="fl-post-meta fl-post-meta-top">';
		}

		// Author
		if ( $show_author ) {
			echo '<span class="fl-post-author" itemprop="author" itemscope="itemscope" itemtype="http://schema.org/Person">';
			printf( _x( 'By %s', 'Post meta info: author.', 'fl-automator' ), '<a href="' . get_author_posts_url( get_the_author_meta( 'ID' ) ) . '" itemprop="url"><span itemprop="name">' . get_the_author_meta( 'display_name', get_the_author_meta( 'ID' ) ) . '</span></a>' );
			echo '</span>';
		}

		// Date
		if($show_date) {

			if($show_author) {
				echo '<span class="fl-sep"> | </span>';
			}

			echo '<span class="fl-post-date" itemprop="datePublished" datetime="' . get_the_time('Y-m-d') . '">' . get_the_date() . '</span>';
		}

		// Comments
		if($comments) {

			if(!empty($show_date)) {
				echo '<span class="fl-sep"> | </span>';
			}

			echo '<span class="fl-comments-popup-link">';
			comments_popup_link('0 <i class="fa fa-comment"></i>', '1 <i class="fa fa-comment"></i>', '% <i class="fa fa-comment"></i>');
			echo '</span>';
		}

		// Close Wrapper
		if($show_author || $show_date || $comments) {
			echo '</div>';
		}

		// Scheme Image Meta
		if(has_post_thumbnail()) {
			echo '<meta itemprop="image" content="' . wp_get_attachment_url(get_post_thumbnail_id($post->ID)) . '">';
		}

		// Scheme Comment Meta
		$comment_count = wp_count_comments($post->ID);

		echo '<meta itemprop="interactionCount" content="UserComments:' . $comment_count->approved . '">';
	}

	/**
	 * @method post_bottom_meta
	 */
	static public function post_bottom_meta()
	{
		$settings  = self::get_settings();
		$show_full = $settings['fl-archive-show-full'];
		$show_cats = $settings['fl-posts-show-cats'] == 'visible' ? true : false;
		$show_tags = $settings['fl-posts-show-tags'] == 'visible' && get_the_tags() ? true : false;
		$comments  = comments_open() || '0' != get_comments_number();

		// Only show if we're showing the full post.
		if($show_full || is_single()) {

			// Wrapper
			if($show_cats || $show_tags || $comments) {
				echo '<div class="fl-post-meta fl-post-meta-bottom">';
			}

			// Categories and Tags
			if($show_cats || $show_tags) {

				echo '<div class="fl-post-cats-tags">';

				if ( $show_cats ) {
					printf( _x( 'Posted in %s', 'Post meta info: category.', 'fl-automator' ), get_the_category_list( ', ' ) );
				}

				if ( $show_tags ) {
					if ( $show_cats ) {
						printf( _x( ' and tagged %s', 'Post meta info: tags. Continuing of the sentence started with "Posted in Category".', 'fl-automator' ), get_the_tag_list( '', ', ' ) );
					} else {
						printf( _x( 'Tagged %s', 'Post meta info: tags.', 'fl-automator' ), get_the_tag_list( '', ', ' ) );
					}
				}

				echo '</div>';
			}

			// Comments
			if ( $comments && ! is_single() ) {
				comments_popup_link( _x( 'Leave a comment', 'Comments popup link title.', 'fl-automator' ), __( '1 Comment', 'fl-automator' ), _nx( '1 Comment', '% Comments', get_comments_number(), 'Comments popup link title.', 'fl-automator' ) );
			}

			// Close Wrapper
			if($show_cats || $show_tags || $comments) {
				echo '</div>';
			}
		}
	}

	/**
	 * @method post_navigation
	 */
	static public function post_navigation()
	{
		$show_nav = self::get_setting( 'fl-posts-show-nav' );

		if ( 'visible' == $show_nav ) {
			echo '<div class="fl-post-nav clearfix">';
			previous_post_link( '<span class="fl-post-nav-prev">%link</span>', '&larr; %title' );
			next_post_link( '<span class="fl-post-nav-next">%link</span>', '%title &rarr;' );
			echo '</div>';
		}
	}

	/**
	 * @method display_comment
	 */
	static public function display_comment($comment, $args, $depth)
	{
		$GLOBALS['comment'] = $comment;

		include locate_template('includes/comment.php');
	}

	/**
	 * @method init_woocommerce
	 */
	static public function init_woocommerce()
	{
		remove_action('woocommerce_sidebar', 'woocommerce_get_sidebar', 10);
		remove_action('woocommerce_before_main_content', 'woocommerce_output_content_wrapper', 10);
		remove_action('woocommerce_after_main_content', 'woocommerce_output_content_wrapper_end', 10);

		add_action('woocommerce_before_main_content', 'FLTheme::woocommerce_wrapper_start', 10);
		add_action('woocommerce_after_main_content', 'FLTheme::woocommerce_wrapper_end', 10);
	}

	/**
	 * @method woocommerce_wrapper_start
	 */
	static public function woocommerce_wrapper_start()
	{
		$layout = self::get_setting('fl-woo-layout');
		$col_size = $layout == 'no-sidebar' ? '12' : '8';

		echo '<div class="container">';
		echo '<div class="row">';
		self::sidebar('left', 'woo');
		echo '<div class="fl-content ';
		self::content_class('woo');
		echo '">';
	}

	/**
	 * @method woocommerce_wrapper_end
	 */
	static public function woocommerce_wrapper_end()
	{
		$layout = self::get_setting('fl-woo-layout');

		echo '</div>';
		self::sidebar('right', 'woo');
		echo '</div>';
		echo '</div>';
	}
}
<?php

abstract class USIN_Assets{

	protected $base_file = USIN_PLUGIN_FILE;
	protected $version = USIN_VERSION;

	protected $page;

	protected $has_inline = true;
	protected $has_ui_select = false;

	protected $js_assets = array(
		'usin_angular' => array('path' => 'js/lib/angular/angular.min.js'),
		'usin_ng_route' => array('path' => 'js/lib/angular-route/angular-route.min.js', 
			'deps' => array('usin_angular')),
		'usin_ng_sanitize' => array('path' => 'js/lib/angular-sanitize/angular-sanitize.min.js', 
			'deps' => array('usin_angular')),
		'usin_drag_drop' => array('path' => 'js/lib/angular-drag-and-drop-lists/angular-drag-and-drop-lists.min.js', 
			'deps' => array('usin_angular')),
		'usin_angular_material' => array('path' => 'js/lib/angular-material/angular-material.min.js', 
			'deps' => array('usin_angular')),
		'usin_select' => array('path' => 'js/lib/angular-ui-select/select.min.js', 
			'deps' => array('usin_angular')),
		'usin_helpers' => array('path' => 'js/helpers.js', 
			'deps' => array('usin_angular')),
		'usin_partials' => array('path' => 'js/partials.min.js', 
			'deps' => array('usin_angular', 'usin_angular_material')),
		'usin_partial_templates' => array('path' => 'views/partials/templates.js', 
			'deps' => array('usin_partials'))
	);

	protected $css_assets = array(
		'usin_angular_meterial_css' => array('path' => 'js/lib/angular-material/angular-material.min.css'),
		'usin_select_css' => array('path' => 'js/lib/angular-ui-select/select.min.css'),
		'usin_main_css' => array('path' => 'css/style.css'),
	);

	public abstract function enqueue_assets();

	/**
	 * @param string $page_slug      the slug of the page loading the assets
	 * @param USIN_Module_Page $page the page object
	 */
	public function __construct($page){
		$this->page = $page;
	}

	public function init(){
		$this->register_custom_assets();
		$this->register_custom_actions();
		add_action( 'admin_enqueue_scripts', array($this, 'check_to_enqueue_assets') );

		if($this->has_inline){
			add_action( 'admin_print_scripts', array($this, 'check_to_print_inline') );
		}

		if($this->has_ui_select){
			add_action( 'admin_enqueue_scripts', array($this, 'dequeue_um_select_css'), 100 );
		}
	}

	protected function should_load_assets(){
		global $current_screen;

		return strpos( $current_screen->base, $this->page->slug ) !== false;
	}

	//optional methods that can be overriden in child classes
	protected function register_custom_assets(){}
	protected function register_custom_actions(){}
	protected function print_inline(){}

	public function check_to_enqueue_assets(){
		if($this->should_load_assets()){
			$this->enqueue_assets();
		}
	}

	public function check_to_print_inline(){
		if($this->should_load_assets()){
			$this->print_inline();
		}
	}

	protected function enqueue_script($handle, $deps = array()){
		if(isset($this->js_assets[$handle])){
			$script = $this->js_assets[$handle];

			if(isset($script['deps'])){
				$deps = array_merge($script['deps'], $deps);
			}

			wp_enqueue_script($handle, 
				plugins_url($script['path'], $this->base_file), 
				$deps, 
				$this->version);
		}
	}

	protected function enqueue_scripts($handles){
		foreach ($handles as $handle ) {
			$this->enqueue_script($handle);
		}
	}

	protected function enqueue_style($handle, $deps = array()){
		if(isset($this->css_assets[$handle])){
			$style = $this->css_assets[$handle];
			if(isset($style['deps'])){
				$deps = array_merge($style['deps'], $deps);
			}

			wp_enqueue_style($handle, 
				plugins_url($style['path'], $this->base_file), 
				$deps, 
				$this->version);
		}
	}

	protected function enqueue_styles($handles){
		foreach ($handles as $handle ) {
			$this->enqueue_style($handle);
		}
	}

	/**
	 * Dequeue the Ultimate Member styles from the Users Insights page, as they
	 * overwrite the select styles
	 */
	public function dequeue_um_select_css(){
		if($this->should_load_assets()){
			wp_dequeue_style('um_admin_select2');
			wp_dequeue_style('um_minified');
			wp_dequeue_style('um_styles');
			wp_dequeue_style('um_default_css');
		}
	}

	public static function print_global_inline_css(){
		$output = '<style>
		#toplevel_page_'.usin_manager()->slug.' .dashicons-before img {
			max-width: 20px;
			height: auto;
			padding-top:7px;
		}
		.usin-menu-beta {
			background: #fff;
			border-radius: 5px;
			color: #000;
			padding: 1px 5px 2px 5px;
			font-size: 7px;
			text-transform: uppercase;
			position: relative;
			top: -1px;
			margin-left: 5px;
		}

		.usin-wc-profile-link{
			float:left;
			width:100%;
			clear:both;
		}

		li#toplevel_page_'.usin_manager()->slug.' .update-plugins{
			display:none !important;
		}
		</style>';

		echo $output;
	}

	public static function load_global_inline_css(){
		add_action( 'admin_print_scripts', array('USIN_Assets', 'print_global_inline_css') );
	}


}
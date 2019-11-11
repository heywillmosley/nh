<?php
/*
  Plugin Name: WooCommerce Bulk Order Form Pro
  Plugin URI: http://wpovernight.com/
  Description: Adds the [wcbulkorder] shortcode which allows you to display bulk order forms on any page in your site
  Version: 2.2
  Author: Jeremiah Prummer
  Author URI: http://wpovernight.com/
  License: GPL2
 */
/*  Copyright 2014 Jeremiah Prummer (email : jeremiah@wpovernight.com)
  This program is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License, version 2, as
  published by the Free Software Foundation.
  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.
 */

class WCBulkOrderFormPro {

	/**
	 * Construct.
	 */
	public function __construct() {

		$this->includes();
		$mainoptions = get_option('wcbulkorderform');
		$this->settings = new WCBulkOrderForm_Settings();
		if(empty($this->options)) {
			register_activation_hook( __FILE__, array( $this->settings, 'default_settings' ) );
			$this->options = get_option('wcbulkorderform');
		}
		register_activation_hook( __FILE__, array( $this, 'register_templates' ) );
		register_activation_hook( __FILE__, array( $this, 'register_default_template' ) );
		add_action( 'plugins_loaded', array( $this, 'languages' ), 0 ); // or use init?
				
		// Init updater data
		$this->item_name	= 'WooCommerce Bulk Order Form Pro';
		$this->file			= __FILE__;
		$this->license_slug	= 'wcbulkorderform_license';
		$this->version		= '2.2';
		$this->author		= 'Jeremiah Prummer';
		add_action( 'init', array( $this, 'load_updater' ), 0 );

		// Redirect to the Settings Page
		// Settings Page URL
		define("BULKORDERFORM_PRO_SETTINGS_URL", "admin.php?page=wcbulkorderform_options_page");
		// Redirect to settings page on activation
		register_activation_hook(__FILE__, array(&$this,'bulkorderform_pro_activate'));
		add_action('admin_init', array(&$this,'bulkorderform_pro_redirect'));
	}

	/**
	 * Redirect: Make It So
	 *
	 */
	function bulkorderform_pro_activate() {
		add_option('bulkorderform_pro_do_activation_redirect', true);
	}
	
	function bulkorderform_pro_redirect() {
		if (get_option('bulkorderform_pro_do_activation_redirect', false)) {
			delete_option('bulkorderform_pro_do_activation_redirect');
			if(!isset($_GET['activate-multi'])){
				wp_redirect(BULKORDERFORM_PRO_SETTINGS_URL);
			}
		}
	}
	
	/**
	 * Run the updater scripts from the Sidekick
	 * @return void
	 */
	public function load_updater() {
		// Check if sidekick is loaded
		if (class_exists('WPO_Updater')) {
			$this->updater = new WPO_Updater( $this->item_name, $this->file, $this->license_slug, $this->version, $this->author );
		}
	}

	/**
	 * Load additional classes and functions
	 */
	public function includes() {
		$mainoptions = get_option('wcbulkorderform');
		//print_r($mainoptions);
		$template = isset($mainoptions['template_style']) ? $mainoptions['template_style'] : '';
		if($template === 'Standard'){
			include_once( 'includes/templates/standard_template/standard_template.php' );
			$WCBulkOrderForm_Standard_Template = new WCBulkOrderForm_Standard_Template();
		}
		if($template === 'Variation'){
			include_once( 'includes/templates/variation_template/variation_search_template.php' );
		    $WCBulkOrderForm_Variation_Template = new WCBulkOrderForm_Variation_Template();
		}
		
		include_once( 'includes/wcbulkorder-settings-pro.php' );
		include_once( 'includes/wc-bulk-order-form-compatibility.php' );
	}
	
	/**
	 * Load translations.
	 */
	public function languages() {
		load_plugin_textdomain( 'wcbulkorderform', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}
	/**
	 * Register Standard Templates
	 */
	function register_templates() {
		global $options;
		$sections = get_option('wcbulkorderform_sections');
		if(empty($sections['templates'])){
			$sections['templates'] = array();
		}
		if(!in_array('Variation',$sections['templates'])){
			$sections['templates'][] = 'Variation';
		}
		if(!in_array('Standard',$sections['templates'])){
			$sections['templates'][] = 'Standard';
		}
		update_option('wcbulkorderform_sections',$sections);
	}
	/**
	 * Set Standard Template as Default
	 */
	function register_default_template(){
		include_once( 'includes/templates/standard_template/standard_template.php' );
		$WCBulkOrderForm_Standard_Template = new WCBulkOrderForm_Standard_Template();
	}
}
$WCBulkOrderFormPro = new WCBulkOrderFormPro();
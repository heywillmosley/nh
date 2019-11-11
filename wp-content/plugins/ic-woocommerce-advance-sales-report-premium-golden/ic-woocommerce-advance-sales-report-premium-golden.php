<?php 
/**
Plugin Name: WooCommerce Advance Sales Report Premium Gold 
Plugin URI: http://plugins.infosofttech.com/
Author: Infosoft Consultants
Description: The latest release of our WooCommerce Report Plug-in has all features of Gold version plus new features like Projected Vs Actual Sales, Comprehensive Tax based Reporting, Improvised Dashboard, Filters by Variation Attributes, Sales summary by Map View, Graphs and much more.
Version: 5.0
Author URI: http://www.infosofttech.com

Copyright: © 2017 - www.infosofttech.com - All Rights Reserved


Tested Wordpress Version: 4.9.8
WC requires at least: 3.3.4
WC tested up to: 3.4.5

Last Update Date:Sep 27, 2018

Text Domain: icwoocommerce_textdomains
Domain Path: /languages/

**/

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly


require_once('ic-woocommerce-auto-email-reports.php');

if(!function_exists('init_icwoocommercepremiumgold')){
	
	if(!function_exists('ic_commerce_onload_search')){
		function ic_commerce_onload_search($onload_search = ''){
			return 'no';
		}
	}
	
	if(!function_exists('ic_commerce_onload_search_text')){
		function ic_commerce_onload_search_text($onload_search_text = ''){		
			$onload_search_text = "<div class=\"order_not_found\">".__("In order to view the results please hit \"<strong>Search</strong>\" button.",'icwoocommerce_textdomains')."</div>";
			return $onload_search_text;
		}	
	}
	
	function init_icwoocommercepremiumgold() {
		global $ic_woocommerce_advance_sales_report_premium_golden, $ic_woocommerce_advance_sales_report_premium_golden_constant;
		
		$constants = array(
				"version"				  => "5.0"
				,"product_id"			  => "1583"
				,"plugin_key"			  => "icwoocommercepremiumgold"
				,"plugin_api_url"		  => "http://plugins.infosofttech.com/api-woo-prem-golden.php"
				,"plugin_main_class"	   => "IC_Woocommerce_Advance_Sales_Report_Premium_Golden"
				,"plugin_instance"		 => "ic_woocommerce_advance_sales_report_premium_golden"
				,"plugin_dir"			  => 'ic-woocommerce-advance-sales-report-premium-golden'
				,"plugin_file"			 => __FILE__
				,"plugin_role"			 => apply_filters('ic_commerce_premium_gold_plugin_role','manage_woocommerce')//'read'
				,"per_page_default"	 	=> 5
				,"plugin_parent_active" 	=> false
				,"color_code"			  => '#77aedb'
				,"plugin_parent"		   => array(
						"plugin_name"		=>"WooCommerce"
						,"plugin_slug"	   =>"woocommerce/woocommerce.php"
						,"plugin_file_name"  =>"woocommerce.php"
						,"plugin_folder"     =>"woocommerce"
						,"order_detail_url"  =>"post.php?&action=edit&post="
				)			
		);
		
		$constants['is_wc_ge_27'] 		= version_compare( WC_VERSION, '2.7', '<' );
		$constants['is_wc_ge_3_0'] 		= version_compare( WC_VERSION, '3.0', '>=' );
		$constants['is_wc_ge_3_0_5'] 	= version_compare( WC_VERSION, '3.0.5', '>=' );
		
		add_filter('ic_commerce_onload_search',		'ic_commerce_onload_search');
		add_filter('ic_commerce_onload_search_text','ic_commerce_onload_search_text');
		
		
		require_once('includes/ic_commerce_premium_golden_fuctions.php');
		
		
		load_plugin_textdomain('icwoocommerce_textdomains', WP_PLUGIN_DIR.'/'.$constants['plugin_dir'].'/languages',$constants['plugin_dir'].'/languages');
		$constants['plugin_name'] 		= __('WooCommerce Advance Sales Report Premium Gold', 	'icwoocommerce_textdomains');
		$constants['plugin_menu_name'] 	= __('WooCommerce Report Premium',						'icwoocommerce_textdomains');
		$constants['admin_page'] 		= isset($_REQUEST['page']) ? $_REQUEST['page'] : "";
		$constants['is_admin'] 			= is_admin();
		
		$constants = apply_filters('ic_commerce_premium_golden_init_constants', $constants, $constants['plugin_key']);
		do_action('ic_commerce_premium_golden_textdomain_loaded',$constants, $constants['plugin_key']);
		
		$ic_woocommerce_advance_sales_report_premium_golden_constant = $constants;
		
		require_once('includes/ic_commerce_premium_golden_add_actions.php');
		
		//require_once('includes/ic_commerce_premium_golden_update_notice.php');
		
		
		
		if ($constants['is_admin']) {
				
				require_once('includes/ic_commerce_premium_golden_init.php');
								
				if(!class_exists('IC_Woocommerce_Advance_Sales_Report_Premium_Golden')){class IC_Woocommerce_Advance_Sales_Report_Premium_Golden extends IC_Commerce_Premium_Golden_Init{}}
				
				$ic_woocommerce_advance_sales_report_premium_golden 			= new IC_Woocommerce_Advance_Sales_Report_Premium_Golden( __FILE__, $ic_woocommerce_advance_sales_report_premium_golden_constant);
				
				
		}
		
		
	}
}

add_action('init','init_icwoocommercepremiumgold', 100);

if(!function_exists('ic_wp_login')){
	function ic_wp_login( $user_login = '', $user = '') {
		update_option('display_popup_first_time','no');
	}
	add_action('wp_login', 	'ic_wp_login', 100, 2);
}


if(!function_exists('ic_commerce_premium_golden_woocommerce_hidden_order_itemmeta')){
	function ic_commerce_premium_golden_woocommerce_hidden_order_itemmeta($hidden_meta = array()){
		if(isset($_REQUEST['post']) and $_REQUEST['post'] > 0){
			if(!is_admin()){
				$hidden_meta[] = '_ic_cogs_item';
				$hidden_meta[] = '_ic_cogs_item_total';
			}
		}else{
			$hidden_meta[] = '_ic_cogs_item';
			$hidden_meta[] = '_ic_cogs_item_total';
		}
		return $hidden_meta;
	}
	add_action( 'woocommerce_hidden_order_itemmeta', 'ic_commerce_premium_golden_woocommerce_hidden_order_itemmeta');
}
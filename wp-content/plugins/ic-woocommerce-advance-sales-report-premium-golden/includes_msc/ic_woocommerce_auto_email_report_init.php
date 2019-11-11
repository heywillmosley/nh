<?php
include_once('ic_woocommerce_auto_email_report_functions.php');

if(!class_exists('Ic_Wc_Auto_Email_Report_Init')){
	
	class Ic_Wc_Auto_Email_Report_Init extends Ic_Wc_Auto_Email_Report_Functions{
		
		/* variable declaration constants*/
		public $constants 				= array();	
		
		/**
			*Constructor
		*/
		public function __construct($constants = array()) {
			$this->constants = $constants;
			//$this->print_array($this->constants);
			add_action('admin_enqueue_scripts',  			 array(&$this, 'admin_enqueue_scripts'));
			add_action('ic_commerce_premium_golden_admin_menu',  			 			array(&$this, 'ic_commerce_premium_golden_admin_menu'));
			add_action('ic_commerce_premium_golden_settting_field_after_dashboard',  	array(&$this, 'ic_commerce_premium_golden_settting_field_bottom'),101,2);
			add_action('admin_init',  	array(&$this, 'admin_init'),101);
			add_filter('ic_commerce_premium_golden_settting_values',  	array(&$this, 'ic_commerce_premium_golden_settting_values'),101,2);	
			
		}
		
		function ic_commerce_premium_golden_admin_menu($constants = array()) {
			$this->constants = array_merge($constants,$this->constants);
			$post_type = $this->constants['post_type'];
			add_submenu_page($this->constants['plugin_key'].'_page',__('Monthly Sales Comparison', 	'icwoocommerce_textdomains'),	__( 'Monthly Sales Comparison',	'icwoocommerce_textdomains'),$this->constants['plugin_role'],'wc_auto_email_dashboard', array( $this, 'wc_auto_email_dashboard' ));
			add_submenu_page($this->constants['plugin_key'].'_page',__('Schedules','icwoocommerce_textdomains'),__( 'Schedules','icwoocommerce_textdomains'),$this->constants['plugin_role'],'edit.php?post_type='.$post_type);
		}
		
		function wc_auto_email_menu_page(){
			include_once('ic_woocommerce_auto_email_reports.php');
			$wcrpt = new Ic_Wc_Auto_Email_Reports($this->constants);
		}
		
		function wc_auto_email_dashboard(){
			//$this->print_array($this->constants);
			include_once('ic_woocommerce_auto_email_report_dashboard.php');
			$wcrpt = new IC_WW_Auto_Email_Dashboard($this->constants);
			$wcrpt->init();
		}
		
		function admin_enqueue_scripts(){			
			$admin_page  = $this->constants['admin_page'];
			if($admin_page != 'wc_auto_email_dashboard'){
				return false;
			}
			
			$plugin_key 	= $this->constants['plugin_key'];
			$assets_url  	= $this->get_plugin_url();
			$css_url  	   = $assets_url.'assets/css/';
			
			wp_enqueue_style($plugin_key.'_bootstrap',  $css_url.'bootstrap.min.css');
			wp_enqueue_style($plugin_key.'_fontawesome',  $css_url.'font-awesome.min.css');
			wp_enqueue_style($plugin_key.'_admin_style',  $css_url.'admin_style.css');
		}
		
		function ic_commerce_premium_golden_settting_field_bottom($that = NULL, $option = array()){
			
			
			
			
			add_settings_section('monthly_sales_comparison',			__('Monthly Sales Comparison:', 'icwoocommerce_textdomains'),		array( &$that, 'section_options_callback' ),$option);
			$default_rows_per_page = 20;
			$settigns = array();
			
			$settigns['last_month_sales'] = __( 'Last Month Sales:', 'icwoocommerce_textdomains');
			foreach($settigns as $key => $label){
				add_settings_field('msc_'.$key,$label,array( &$that, 'text_element_callback' ), $option,'monthly_sales_comparison', array('menu'=> $option,	'size'=>15, 'class'=>'numberonly', 'maxlength'=>'5',	'label_for'=>'msc_'.$key,'id'=> 'msc_'.$key,'default'=>12));
			}
			
			$settigns = array();
			$settigns['top_products_summary'] = __( 'Top Products Summary:', 'icwoocommerce_textdomains');
			$settigns['top_new_products']     = __( 'Top New Products:', 'icwoocommerce_textdomains');
			$settigns['top_not_sold_products']     = __( 'Top Not Sold Products:', 'icwoocommerce_textdomains');
			$settigns['top_consistent_products']     = __( 'Top Consistent Products:', 'icwoocommerce_textdomains');
			
			
			$settigns['top_customers_summary'] = __( 'Top Customers Summary:', 'icwoocommerce_textdomains');
			$settigns['top_new_customers']     = __( 'Top New Customers:', 'icwoocommerce_textdomains');
			$settigns['top_not_sold_customers']     = __( 'Top Not Sold Customers:', 'icwoocommerce_textdomains');
			$settigns['top_consistent_customers']     = __( 'Top Consistent Customers:', 'icwoocommerce_textdomains');
			
			foreach($settigns as $key => $label){
				add_settings_field('msc_'.$key,$label,array( &$that, 'text_element_callback' ), $option,'monthly_sales_comparison', array('menu'=> $option,	'size'=>15, 'class'=>'numberonly', 'maxlength'=>'5',	'label_for'=>'msc_'.$key,'id'=> 'msc_'.$key,'default'=>$default_rows_per_page));
			}
			$msc_from_name 	  = get_option('woocommerce_email_from_name');
			$msc_from_email    	= get_option('woocommerce_email_from_address');
			$msc_to_email 	   = get_option('new_admin_email');
			
			add_settings_field('msc_to_email',__( 'Default, To Email:', 'icwoocommerce_textdomains'),array( &$that, 'text_element_callback' ), $option,'monthly_sales_comparison', array('menu'=> $option,'size'=>50,'class'=>'','maxlength'=>'100','label_for'=>'msc_to_email','id'=> 'msc_to_email','default'=>$msc_to_email));
			add_settings_field('msc_from_name',__( 'Default, From name:', 'icwoocommerce_textdomains'),array( &$that, 'text_element_callback' ), $option,'monthly_sales_comparison', array('menu'=> $option,'size'=>50,'class'=>'','maxlength'=>'100','label_for'=>'msc_from_name','id'=> 'msc_from_name','default'=>$msc_from_name));
			add_settings_field('msc_from_email',__( 'Default, From Email:', 'icwoocommerce_textdomains'),array( &$that, 'text_element_callback' ), $option,'monthly_sales_comparison', array('menu'=> $option,'size'=>50,'class'=>'','maxlength'=>'100','label_for'=>'msc_from_email','id'=> 'msc_from_email','default'=>$msc_from_email));
		}
		
		function admin_init(){
			if(isset($_POST['send_monthly_sales_comparison'])){
				include_once('ic_woocommerce_auto_email_report_schedule_email.php');
				$obj = new Ic_Wc_Auto_Email_Report_Schedule_Email($this->constants);
				$reports = $obj->get_reports();
				$obj->send_mail(NULL,$reports);
			}
		}
		
		function ic_commerce_premium_golden_settting_values($post = array(), $that = NULL){
			if(isset($post['msc_to_email'])){
				if(!empty($post['msc_to_email'])){
					$post['msc_to_email'] = $that->get_email_string($post['msc_to_email']);
				}
			}
			
			if(isset($post['msc_from_email'])){
				if(!empty($post['msc_from_email'])){
					$msc_from_emails = $that->get_email_string($post['msc_from_email']);
					$msc_from_emails = explode(",",$msc_from_emails);
					$post['msc_from_email'] = isset($msc_from_emails[0]) ? $msc_from_emails[0] : '';
				}
			}
			
			return $post;
		}
		
	}
	
}
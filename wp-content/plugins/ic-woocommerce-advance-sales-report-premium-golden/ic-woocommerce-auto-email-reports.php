<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( !class_exists( 'Ic_Wc_Report_Auto_Email' ) ) {
	class Ic_Wc_Report_Auto_Email{
		
		public $constants 				= array();			
		
		public function __construct($constants = array()) {
			$this->constants = $constants;			
			add_action('init', 			array(&$this, 'init'));
			add_action('plugins_loaded', array(&$this, 'plugins_loaded'),20);
		}
		
		function init(){
			include_once('includes_msc/ic_woocommerce_auto_email_report_init.php');
			$wcrptinit = new Ic_Wc_Auto_Email_Report_Init($this->constants);
			
			include_once('includes_msc/ic_woocommerce_auto_email_report_custom_post_type.php');
			$wcrptcpt = new Ic_Wc_Auto_Email_Report_CPT($this->constants);
		}
		
		function plugins_loaded(){
			$constants['plugin_key'] 				= 'icwcerpro';
			$constants['plugin_role'] 			   = apply_filters('ic_icwcerpro_plugin_role','manage_woocommerce');
			$constants['is_admin'] 				  = is_admin();
			$constants['admin_page']			  	= isset($_REQUEST['page']) ? $_REQUEST['page'] : '';
			$constants['parent_slug']			   = $constants['plugin_key'].'_page';
			$constants['plugin_file']			   = __FILE__;
			$constants['assets_url'] 				= plugins_url("assets/",__FILE__);
			$constants['ajax_action'] 			   = 'point_of_sale';
			$constants['post_type'] 				 = 'ic_schedules';
			$constants['parent_plugin_key'] 		 = 'icwoocommercepremiumgold';
			$constants['plugin_key'] 				= 'icwoocommercepremiumgold';
			//UPDATE `wp_posts` SET `post_type` = 'ic_schedules' WHERE `wp_posts`.`post_type` = 'wcrpt_settings'
			$this->constants = $constants;
			
			include_once('includes_msc/ic_woocommerce_auto_email_report_schedule_email.php');
			$wcrptschemail = new Ic_Wc_Auto_Email_Report_Schedule_Email($this->constants);
		}
		
		function get_text($translated_text, $text, $domain){
			return $translated_text;
			if($domain == 'icwoocommerce_textdomains'){
				return '['.$translated_text.']';
			}		
			return $translated_text;
		}
	}
	$wcrptautoemail = new Ic_Wc_Report_Auto_Email();
}
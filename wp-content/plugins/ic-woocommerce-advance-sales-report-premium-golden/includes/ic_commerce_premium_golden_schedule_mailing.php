<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if(!class_exists('IC_Commerce_Premium_Golden_Schedule_Mailing')){
	class IC_Commerce_Premium_Golden_Schedule_Mailing{
		
		var $constants = array();
		
		public function __construct($file,$plugin_key = 'icpro') {
			
			$this->constants['plugin_key']	= $plugin_key;
			$this->constants['plugin_file']	= $file;
			
			add_filter('cron_schedules',array($this, 'cron_schedules'),10, 1);
			add_action('wp', array( $this, 'wp_next_scheduled'));
			
			add_action($this->constants['plugin_key'].'_schedule_mailing_sales_status_event', array($this, 'schedule_mailing_sales_status_cron'));
			add_action($this->constants['plugin_key'].'_schedule_event', array($this, 'schedule_event_cron'));
			add_action('admin_init', array($this, 'schedule_mailing_sales_status_force'));
			add_action('wp_loaded', array($this, 'schedule_mailing_sales_status_force_doing_wp_cron'),110);
		}
		
		function schedule_mailing_sales_status(){
			require_once('ic_commerce_premium_golden_schedule_mailing_sales_status.php');
			$ic_commerce_constant 							= array();
			$ic_commerce_constant['plugin_key'] 			= $this->constants['plugin_key'];
			$ic_commerce_constant['detault_stauts_slug'] 	= array();
			$ic_commerce 									= new IC_Commerce_Premium_Golden_Schedule_Mailing_Sales_Status( $this->constants['plugin_file'], $ic_commerce_constant);
			return $ic_commerce;
		}
		
		function schedule_mailing_sales_status_cron(){
			$ci = $this->schedule_mailing_sales_status();
			$ci->cron_schedule_event();
		}
		
		function schedule_mailing_sales_status_force(){
			if(isset($_REQUEST[$this->constants['plugin_key'].'_force_schedule_event_sales_status'])){
				$ci = $this->schedule_mailing_sales_status();
				$ci->force_schedule_event();
			}
		}
		
		function schedule_mailing_sales_status_force_doing_wp_cron(){
			
			if(isset($_REQUEST['doing_wp_cron']) and $_REQUEST['doing_wp_cron'] == "sales_status"){
				$ci = $this->schedule_mailing_sales_status();
				$ci->doing_wp_cron_schedule_event();
			}
		}
		
		function wp_next_scheduled(){
			
			$original_args 					= array();
			$timestamp 						= time();			
			$options 						= get_option($this->constants['plugin_key']);
			
			$schedule_activate				= isset($options['act_email_reporting']) ? $options['act_email_reporting'] : 0;
			$schedule_recurrence			= isset($options['email_schedule']) ? $options['email_schedule'] : 0;
			$schedule_hook_name				= $this->constants['plugin_key'].'_schedule_mailing_sales_status_event';
			
			if($schedule_activate == 1 and strlen($schedule_recurrence) > 2){
				if (!wp_next_scheduled($schedule_hook_name)){
					wp_schedule_event($timestamp, $schedule_recurrence, $schedule_hook_name);
				}
			}else{
				wp_unschedule_event( $timestamp, $schedule_hook_name, $original_args );
				wp_clear_scheduled_hook( $schedule_hook_name, $original_args );
			}
			
			if(!wp_next_scheduled($this->constants['plugin_key'].'_schedule_event')){
				wp_schedule_event($timestamp, 'weekly', $this->constants['plugin_key'].'_schedule_event');
			}
			
		}
		
		function schedule_event_cron(){
			$ci = $this->schedule_mailing_sales_status();
			$ci->schedule_event_cron();
		}
		
		function cron_schedules($schedules){
			
			$schedules['minnut']		= isset($schedules['minnut']) 		? $schedules['minnut'] 		:	array('interval'=>	MINUTE_IN_SECONDS,		'display'=> __('Once Minute'));//For testing
			$schedules['five_minute']	= isset($schedules['five_minute']) 	? $schedules['five_minute'] :	array('interval'=>	MINUTE_IN_SECONDS*5,	'display'=> __('Once 5 Minutes'));//For testing
			$schedules['ten_minute']	= isset($schedules['ten_minute']) 	? $schedules['ten_minute'] :	array('interval'=>	MINUTE_IN_SECONDS*10,	'display'=> __('Once 10 Minutes'));//For testing
			
			$schedules['hourly']		= isset($schedules['hourly']) 		? $schedules['hourly'] 		:	array('interval'=>	HOUR_IN_SECONDS,		'display'=> __('Once Hourly'));	
			$schedules['daily']			= isset($schedules['daily']) 		? $schedules['daily'] 		:	array('interval'=>	DAY_IN_SECONDS,			'display'=> __('Once Daily'));
			$schedules['weekly'] 		= isset($schedules['weekly']) 		? $schedules['weekly'] 		:	array('interval'=>	WEEK_IN_SECONDS,		'display'=> __('Once Weekly'));
			
			$schedules['twicehourly']	= isset($schedules['twicehourly']) 	? $schedules['twicehourly']	:	array('interval'=>	HOUR_IN_SECONDS/2,		'display'=> __('Twice Hourly'));
			$schedules['twicedaily']	= isset($schedules['twicedaily']) 	? $schedules['twicedaily'] 	:	array('interval'=>	DAY_IN_SECONDS/2,		'display'=> __('Twice Daily'));
			$schedules['twiceweekly']	= isset($schedules['twiceweekly']) 	? $schedules['twiceweekly'] :	array('interval'=>	WEEK_IN_SECONDS/2,		'display'=> __('Twice Weekly'));
			
			return $schedules;
		}
		
		function set_error_log($str){
			$this->set_error_on();
			error_log("[".date("Y-m-d H:i:s")."] PHP Notice: \t".$str."\n",3,$this->log_destination);			
		}
		
		var $error_on = NULL;
		
		var $log_destination = NULL;
		
		function set_error_on(){
			
			if($this->error_on) return '';
						
			//$plugin_path	= isset($this->constants['plugin_dir']) ? $this->constants['plugin_dir'] : dirname(__FILE__);
			
			//$plugin_path = str_replace("\includes","",$plugin_path);
			//$plugin_path = str_replace("/includes","",$plugin_path);
			
			$error_folder = ABSPATH . '/ic-logerror/';
	
			if (!file_exists($error_folder)) {
				@mkdir($error_folder, 0777, true);
			}
			
			$this->log_destination = $error_folder.'ic_error_'.date("Ymd").'.log';
			
			@ini_set('error_reporting', E_ALL);
			
			@ini_set('log_errors','On');
			
			@ini_set('error_log',$this->log_destination);
			
			$this->error_on = true;
		}
		
		function set_error_off(){
			@ini_set('log_errors','off');
		}
		
		
	}//End Class
}
<?php 
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
if ( ! class_exists( 'IC_Commerce_Premium_Golden_GA_Data' ) ) {	
	class IC_Commerce_Premium_Golden_GA_Data{
		
		public $constants 		= array();		
		public $today 			= '';		
		public $dimensions 		= array();
		public $metrics 		= array();
		public $sort_metric 	= 'date';
		public $filter			= NULL;
		public $start_index		= 1;
		public $max_results		= 1000;
		public $ga				= NULL;
		public $token			= NULL;
		
		public function __construct($constants) {
			global $options;
			
			$this->constants		= $constants;			
			$options				= $this->constants['plugin_options'];			
			$this->today			= date_i18n("Y-m-d");
			$this->dimensions 		= array('date','week','month');
			$this->metrics 			= array('visits','visitors');
			$this->sort_metric 		= 'date';
			$this->filter			= NULL;
			$this->filter			= NULL;
			$this->start_index		= 1;
			$this->max_results		= 1000;
			$this->token			= NULL;
		}
		
		function init(){}//init	
		
		function get_ga_report($start_date=NULL,$end_date = NULL){
			$visit_data  = array();
			
			$return = array();
			$return['error'] 			= false;
			$return['success'] 			= false;
			$return['notice'] 			= '';
			$return['notice_class'] 	= 'error';
			$return['ga_token'] 		= NULL;
			$return['visit_data'] 		= array();
			
			if(!function_exists('file_get_contents')){
				$error_txt = __("<strong>Google Analytics:-</strong> file_get_contents() function not exists.",'icwoocommerce_textdomains');			
				$error_notice = '<div class="error fade"><p>' .$error_txt. " </p></div>\n";							
				$this->constants['ga_api_notice'] = $error_notice;
				$return['error'] 			= true;
				$return['notice_class'] 	= 'error';
				$return['notice'] 			= $error_notice;					
				return $return;
			}
			
			$ga_account 			= $this->get_setting('ga_account',$this->constants['plugin_options'], false);
			$ga_password 			= $this->get_setting('ga_password',$this->constants['plugin_options'], false);
			$ga_profile_id 			= $this->get_setting('ga_profile_id',$this->constants['plugin_options'], false);
			
			$token					= NULL;
			$token_stored			= false;
			$token					= get_option($this->constants['plugin_key'].'_ga_token',false);
			$setting_url			= admin_url("admin.php?page=".$this->constants['plugin_key']."_options_page");
			$setting_link			= sprintf(__("<a href=\"%s#ga_profile_id\">Click here </a> for google analytics settings.",'icwoocommerce_textdomains'),$setting_url);
			$request_failed_msg		= "Request failed, fopen provides no further information";
			$request_failed_notice  = __("Request failed, fopen provides no further information, please try again later",'icwoocommerce_textdomains');
			$youtube_profile_id_ntc = __(" <strong>Profile ID/View ID: - </strong><a href=\"https://www.youtube.com/watch?v=Qf2VT_ZCSGI\" target=\"_blank\">Click here </a> how to find your Google Analytics <strong>Profile Id</strong>",'icwoocommerce_textdomains');
			$error_notice 			= "";
			
			
			if($this->token){
				$token = $this->token;
			}			
			
			if(!$token){// || !$ga_profile_id
				if(!$ga_account || !$ga_password){				
					$error_txt = "<strong>Google Analytics:-</strong> Please enter your google analytics usename and password.";
					
					$error_notice = '<p>' .$error_txt. " </p>\n";
						
					$error_notice .= '<p>'." {$setting_link}</p>\n";
					
					$return['error'] 			= true;
					$return['notice_class'] 	= 'error';
					$return['notice'] 			= $error_notice;					
					return $return;
				}
			}else{
				/*if($ga_account || $ga_password){				
					$error_txt = "<strong>Google Analytics:-</strong> Please delete your username and password. {$setting_link}";
					$error_notice = '<p>' .$error_txt. "</p>\n";
					
					$return['error'] 			= true;
					$return['notice_class'] 	= 'error';
					$return['notice'] 			= $error_notice;					
					return $return;
				}*/
				
				$token_stored = true;
				//update_option('ga_token',$token);
				$return['ga_token'] 		= $token;
				$this->token				= $token;
			}
			
			include_once("ic_commerce_premium_golden_gapi.class.php");
			
			$ga 			= new gapi($ga_account,$ga_password,$token);
			$this->ga 		= $ga;
			
			if(!$token_stored){
				$error = $ga->authenticate();
				
				$token = $ga->getAuthToken();
							
				if(!$token){
					if(strlen($error)>=0){
						
						$error_txt = str_replace("BadAuthentication",__("<strong>Bad Authentication of Google Analytics:-</strong>Username or Password is incorrect.",'icwoocommerce_textdomains'),$error);
						
						if($error_txt == $request_failed_msg){
							$error_notice = '<p>' .$request_failed_notice. " </p>\n";
						}else{							
							$error_notice = '<p>' .$error_txt. " </p>\n";
							$error_notice .= '<p>'." {$setting_link}</p>\n";
						}
						
						
						//$error_txt = str_replace("BadAuthentication","<strong>Bad Authentication of Google Analytics:-</strong>Username or Password is incorrect.",$error);
						//$error_notice = '<p>' .$error_txt. " </p>\n";
						//$error_notice .= '<p>'." {$setting_link}</p>\n";
						
						delete_option($this->constants['plugin_key'].'_ga_token');
						
						$return['error'] 			= true;
						$return['notice_class'] 	= 'error';
						$return['notice'] 			= $error_notice;	
					}
									
					return $return;
				}else{
					update_option($this->constants['plugin_key'].'_ga_token',$token);
				}
			}
			
			if(!$ga_profile_id){
				$error_txt = __("<strong>Google Analytics:-</strong> Please enter your google analytics profile ID.",'icwoocommerce_textdomains');
				
				if($error_txt = $request_failed_msg){
					$error_notice = '<p>' .$request_failed_notice. " </p>\n";
				}else{							
					$error_notice = '<p>' .$error_txt. " </p>\n";
					$error_notice .= '<p>'." {$setting_link}</p>\n";
					$error_notice .= '<p>'." {$youtube_profile_id_ntc}</p>\n";
				}
								
				//$error_txt = "<strong>Google Analytics:-</strong> Please enter your google analytics profile ID.";
				//$error_notice = '<p>' .$error_txt. " </p>\n";
				//$error_notice .= '<p>'." {$setting_link}</p>\n";				
				//$error_notice .= '<p>' . " <strong>Profile ID/View ID: - </strong><a href=\"https://www.youtube.com/watch?v=Qf2VT_ZCSGI\" target=\"_blank\">Click here </a> how to find your Google Analytics <strong>Profile Id</strong>. </p>\n";
				
				$return['error'] 			= true;
				$return['notice_class'] 	= 'error';
				$return['notice'] 			= $error_notice;				
				return $return;
				
			}else if(!is_numeric($ga_profile_id)){
				$error_txt = __("<strong>Google Analytics:-</strong> Please enter your google analytics profile ID.",'icwoocommerce_textdomains');
				
				if($error_txt = $request_failed_msg){
					$error_notice = '<p>' .$request_failed_notice. " </p>\n";
				}else{							
					$error_notice = '<p>' .$error_txt. " </p>\n";
					$error_notice .= '<p>'." {$setting_link}</p>\n";
					$error_notice .= '<p>'." {$youtube_profile_id_ntc}</p>\n";
				}
				
				//$error_txt = "<strong>Google Analytics:-</strong> Please enter valid google analytics profile ID.";
				//$error_notice = '<p>' .$error_txt. " </p>\n";
				//$error_notice .= '<p>'." {$setting_link}</p>\n";
				//$error_notice .= '<p>' . " <strong>Profile ID/View ID: - </strong><a href=\"https://www.youtube.com/watch?v=Qf2VT_ZCSGI\" target=\"_blank\">Click here </a> how to find your Google Analytics <strong>Profile Id</strong>. </p>\n";
				
				
				$return['error'] 			= true;
				$return['notice_class'] 	= 'error';
				$return['notice'] 			= $error_notice;				
				return $return;
			}
			
			$dimensions 	= $this->dimensions;
			$metrics		= $this->metrics;
			$sort_metric 	= $this->sort_metric;
			$filter			= $this->filter;
			$filter			= $this->filter;
			$start_index	= $this->start_index;
			$max_results	= $this->max_results;
			
			//$dimensions 	= array('nthMonth');
			//$metrics    	= array('visits','Pageviews','bounces','sessions','users','avgSessionDuration','percentNewSessions','sessionDuration','newUsers');
			
			//print_r($dimensions);
			//print_r($metrics);
			$report = $ga->requestReportData($ga_profile_id,$dimensions,$metrics,$sort_metric,$filter,$start_date,$end_date,$start_index,$max_results);
				
			if(!$report){
				$error = $ga->get_request_report_error();
				if(strlen($error)>=0){
					
					$error_txt = str_replace("GDatainsufficientPermissionsUser",__("<strong>Google Analytics Data Insufficient Permissions:-</strong> User ",'icwoocommerce_textdomains'),$error);
					
					if($error_txt = $request_failed_msg){
						$error_notice = '<p>' .$request_failed_notice. " </p>\n";
					}else{							
						$error_notice = '<p>' .$error_txt. " </p>\n";
						$error_notice .= '<p>'." {$setting_link}</p>\n";
						$error_notice .= '<p>'." {$youtube_profile_id_ntc}</p>\n";
					}
					
					//$error_txt = str_replace("GDatainsufficientPermissionsUser","<strong>Google Analytics Data Insufficient Permissions:-</strong> User ",$error);
					
					//$error_notice = '<p>' .$error_txt. "  </p>\n";
					
					//$error_notice .= '<p>'." {$setting_link} Please check your profile id.</p>\n";
					
					//$error_notice .= '<p>' . " <strong>Profile ID/View ID: - </strong><a href=\"https://www.youtube.com/watch?v=Qf2VT_ZCSGI\" target=\"_blank\">Click here </a> how to find your Google Analytics <strong>Profile Id</strong>. </p>\n";
					
					$return['error'] 			= true;
					$return['notice_class'] 	= 'error';
					$return['notice'] 			= $error_notice;				
					return $return;
				}
			}else{
				/*
				$error_notice = '<div class="error fade"><p>'." {$setting_link} Change you account.</p></div>\n";
				
				$return['notice_class'] 	= 'error';
				$return['notice'] 			= $error_notice;				
				
				*/
			}
			$return['error'] 			= false;
			$return['success'] 			= true;
			return $return;
		}
		
		function ga_report($start_date=NULL,$end_date = NULL, $date_format = "date"){
			
			$return = $this->get_ga_report($start_date,$end_date);
			$return['ga_summary'] 		= '';
			
			if($return['success'] == true){
				if($date_format == "date"){
					$ga = $this->ga;
					foreach ($ga->getResults() as $key => $result) {
						$date = $result->getDate(); 
						$strtotime = strtotime($date);
						$date =  date('Y-m-d',$strtotime);
						
						$visit_data[$key]['Value'] = $result->getVisits();
						$visit_data[$key]['Label'] = $date;
					}
					$return['visit_data'] 		= $visit_data;
				}				
				$return['ga_summary'] 		= $this->ga_get_summary(false);
			}
			
			return $return;
		}
		
		var $r = array();
		function ga_summary($start_date=NULL,$end_date = NULL, $date_format = "summary"){
			
			//NEW SETTINGS
			$this->dimensions 	= array('nthMonth');
			$this->metrics    	= array('visits','Pageviews','bounces','sessions','users','avgSessionDuration','percentNewSessions','sessionDuration','newUsers');
			$this->sort_metric 	= NULL;
			$this->start_index 	= 1;
			$this->max_results 	= 30;
			
			$return 			= $this->get_ga_report($start_date,$end_date);
			
			if($return['success'] == true){
				if($date_format == "summary"){
						$ga 							= $this->ga;						
						//$return["_visites"] 			= $ga->getVisits() ; 						
						$return["_page_views"] 			= $ga->getPageviews() ;						
						$return["_bounces"] 			= $ga->getBounces() ;						
						
						$return["_users"] 				= $ga->getUsers() ;						
						$return["_sessions"] 			= $ga->getSessions() ;						
						
						//$return["_avg_session_duration"] = $ga->getavgSessionDuration();
						$return["_percent_new_sessions"] = $ga->getpercentNewSessions();						
						
						//$return["_session_duration"] 	= $ga->getSessionDuration() ;						
						//$return["_new_users"] 			= $ga->getnewUsers() ;
						
						//Percentage Calculation
						$return["_pages_session"] 		= ($return["_page_views"]/$return["_sessions"]);
						//$return["_bounces_rate"] 		= ($return["_bounces"]/ $return["_sessions"])*100;
						$return["_bounces_rate"] 		= $this->get_percentage($return["_bounces"],$return["_sessions"]);//Added 20150206
						
						
						
						//Number formate
						//$return["visites"] 				= number_format($return["_visites"]);
						$return["page_views"] 			= number_format($return["_page_views"]);
						$return["bounces"] 				= number_format($return["_bounces"]);
						
						$return["users"] 				= number_format($return["_users"]);
						$return["sessions"] 			= number_format($return["_sessions"]);
						
						
						//$return["session_duration"] 	= number_format($return["_session_duration"]);
						//$return["new_users"] 			= number_format($return["_new_users"]);
						
						//Number in decimal formate
						//$return["avg_session_duration"] = number_format($return["_avg_session_duration"], 2, '.', '');
						$return["percent_new_sessions"] = number_format($return["_percent_new_sessions"], 2, '.', '');
						
						$return["pages_session"] 		= number_format($return["_pages_session"], 2, '.', '');
						$return["bounces_rate"] 		= number_format($return["_bounces_rate"], 2, '.', '');
				}
			}
			
			return $return;
		}
		
		function ga_get_summary($display = false){
			
			$ga_last_day 		= $this->get_setting('ga_last_day',$this->constants['plugin_options'], 30);
			
			if(strlen(trim($ga_last_day)) <= 0)
				$show_data = __("Please enter last Audience Overview Last day",'icwoocommerce_textdomains');
				
			$ga_last_day 		= "-{$ga_last_day} day";
			$start_date 		= date('Y-m-d', strtotime($ga_last_day, strtotime(date_i18n("Y-m-d"))));
			$end_date 			= date_i18n('Y-m-d');
			
			
			/*
			$start_date 		= $this->get_setting('ga_ao_start_date',$this->constants['plugin_options'], '');
			$end_date 			= $this->get_setting('ga_ao_end_date',$this->constants['plugin_options'], '');
			
			if(!$start_date){
				$ga_last_day 		='-30 day';
				$start_date 		= date('Y-m-d', strtotime($ga_last_day, strtotime(date_i18n("Y-m-d"))));
			}
			if(!$end_date)
				$end_date 			= date_i18n('Y-m-d');
			*/
			
			$data = $this->ga_summary($start_date, $end_date);	
			
			//$this->print_array($data);
			
			if($data['error'] == true){
				$show_data = $data['notice'];				
			}else{			
				$show_data = '<ul class="stats-overview">
					<li>
						<span class="name">Sessions</span>
						<span class="value text-orange">'.$data['sessions'].'</span>
					</li>
					<li>
						<span class="name">User</span>
						<span class="value text-green">'.$data['users'].'</span>
					</li>					
					<li>
						<span class="name">Pageviews</span>
						<span class="value text-purple">'.$data['page_views'].'</span>
					</li>
					<li class="hidden-phone">
						<span class="name">Pages / Sessions</span>
						<span class="value text-yellow">'.$data["pages_session"].'</span>
					</li>
					<li class="hidden-phone">
						<span class="name">Bounce Rate</span>
						<span class="value text-blue">'.$data['bounces_rate'].'%</span>
					</li>
					
					<li class="hidden-phone">
						<span class="name">% New Sessions</span>
						<span class="value text-red">'.$data['percent_new_sessions'].'%</span>
					</li>				
					
					
				</ul>';
				
				//$show_data .= $this->print_array($data,false);
				//$show_data .= "start_date: - ".$start_date;
				//$show_data .= "end_date: - ".$end_date;
				//$show_data .= $this->print_array($this->constants['plugin_options'],false);
			}
			
			if($display)
				echo $show_data;
			else
				return $show_data;
		
		}
		
		function get_setting($id, $data, $defalut = NULL){
			if(isset($data[$id]))
				return $data[$id];
			else
				return $defalut;
		}
		
		function print_array($ar = NULL,$display = true){
			if($ar){
				$output = "<pre>";
				$output .= print_r($ar,true);
				$output .= "</pre>";
				
				if($display){
					echo $output;
				}else{
					return $output;
				}
			}
		}
		
		//Added 20150206
		function get_percentage($first_value = 0, $second_value = 0, $default = 0){
			$return = $default;
			$first_value = trim($first_value);
			$second_value = trim($second_value);
			
			if($first_value > 0  and $second_value > 0){
				$return = ($first_value/$second_value)*100;
			}
			
			return $return;		
		}
		
		
	}
}
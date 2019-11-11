<?php
if(!class_exists('Ic_Wc_Auto_Email_Report_Functions')){
	class Ic_Wc_Auto_Email_Report_Functions{
		
		public function __construct(){
			
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
		
		function get_woo_price($price = 0){
			$new_price = 0;
			if ($price){
				$new_price = wc_price($price);
			}else{
				$new_price = wc_price($new_price);
			}
			return $new_price;
		}
		
		function get_plugin_url(){
			if(!isset($this->constants['plugins_url'])){
				$plugin_file 	= $this->constants['plugin_file'];
				$plugins_url  	= plugins_url('/', $plugin_file);
				$this->constants['plugins_url'] = $plugins_url;
			}
			return $this->constants['plugins_url'];
		}
		
		function get_next_year_percentage($pre = 0, $curr = 0){
			$percentage = 0;
			$diff = 0;
			if ($pre  && $curr ){
				$diff =  $curr -  $pre   ;
				//echo "<br>";
				$percentage  = (($diff/ $curr)*100);
				$percentage  = number_format($percentage ,2);
			}
			return $percentage;
		}
	}
}
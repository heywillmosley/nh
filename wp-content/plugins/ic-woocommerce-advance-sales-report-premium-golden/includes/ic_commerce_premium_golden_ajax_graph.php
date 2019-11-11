<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly
if (!class_exists('IC_Commerce_Premium_Golden_Ajax_Graph')) {
    class IC_Commerce_Premium_Golden_Ajax_Graph extends IC_Commerce_Premium_Golden_Fuctions{
        
        public $do_action = NULL;
        
        public $per_page = 0;
        
        public $per_page_default = 5;
        
        public $constants = array();
        
        public function __construct($constants)
        {
            
            global $options;
            
            $this->constants        = $constants;
            $options                = $this->constants['plugin_options'];
            $this->per_page         = $this->constants['per_page_default'];
            $this->per_page_default = $this->constants['per_page_default'];
            $do_action              = $this->get_request('do_action', NULL, false);
            $do_content             = $this->get_request('do_content', NULL, false);
            $return                 = $order_items = array();
            
            $start_date				= empty($_REQUEST['start_date']) ? $this->constants['start_date'] : $_REQUEST['start_date'];
			$end_date				= empty($_REQUEST['end_date']) ? $this->constants['end_date'] : $_REQUEST['end_date'];
            
            $shop_order_status		= apply_filters('ic_commerce_dashboard_page_default_order_status',$this->get_set_status_ids(),$this->constants);	
			$hide_order_status 		= apply_filters('ic_commerce_dashboard_page_default_hide_order_status',$this->constants['hide_order_status'],$this->constants);
			$start_date 			= apply_filters('ic_commerce_dashboard_page_default_start_date',$start_date,$this->constants);
			$end_date 				= apply_filters('ic_commerce_dashboard_page_default_end_date',$end_date,$this->constants);
            
            switch ($do_action) {
                case "sales_by_months":
                    $return = $this->bar_chart_sales_by_month($shop_order_status, $hide_order_status, $start_date, $end_date);
                    break;
                case "sales_by_days":
                    $return = $this->bar_chart_sales_by_days($shop_order_status, $hide_order_status, $start_date, $end_date);
                    break;
                case "sales_by_week":
                    $return = $this->get_last_week_order($shop_order_status, $hide_order_status, $start_date, $end_date);
                    break;
                case "top_product":
                    $return = $this->pie_chart_top_product($shop_order_status, $hide_order_status, $start_date, $end_date);
                    break;
                case "sales_order_status_piechart":
                case "sales_order_status_linechart":
                case "sales_order_status_barchart":
                    $return = $this->sales_order_status($shop_order_status, $hide_order_status, $start_date, $end_date);
                case "sales_order_status":
                    $return = $this->sales_order_status($shop_order_status, $hide_order_status, $start_date, $end_date);
                    break;
                case "top_product_status":
                    $return = $this->top_product_list($shop_order_status, $hide_order_status, $start_date, $end_date);
                    break;
				case "top_billing_state":
                    $return = $this->top_billing_state($shop_order_status, $hide_order_status, $start_date, $end_date);
                    break;
				
				case "top_billing_country":
                    $return = $this->top_billing_country($shop_order_status, $hide_order_status, $start_date, $end_date);
                    break;
                case "top_payment_gateway":
                    $return = $this->top_payment_gateway($shop_order_status, $hide_order_status, $start_date, $end_date);
                    break;
                case "top_customer_list":
                    $return = $this->top_customer_list($shop_order_status, $hide_order_status, $start_date, $end_date);
                    break;
                case "top_coupon_list":
                    $return = $this->top_coupon_list($shop_order_status, $hide_order_status, $start_date, $end_date);
                    break;
                case "thirty_days_visit":
                    $return = $this->thirty_days_visit($shop_order_status, $hide_order_status, $start_date, $end_date);
                    break;
				case "thirty_days_visit":
                    $return = $this->thirty_days_visit($shop_order_status, $hide_order_status, $start_date, $end_date);
                    break;
                case "top_category_status":
                    $return = $this->get_category_list($shop_order_status, $hide_order_status, $start_date, $end_date);
                    break;
				case "ga_summary":
                    echo $return = $this->get_category_list($shop_order_status, $hide_order_status, $start_date, $end_date);
                    return;
                    break;
                default:
                    $return = $order_items = array();
                    break;
            }
            
            //print_array($return);
            
            if (isset($_POST['do_action'])) {
                echo json_encode($return);
            } else {
                return $order_items;
            }
        }
        
        function bar_chart_sales_by_month($shop_order_status, $hide_order_status, $start_date, $end_date)
        {
             global $wpdb;
            $end_date_strtotime = strtotime($end_date);
			
			//$start_date = date("Y-m-01",strtotime('-365 day', $end_date_strtotime));			
			$start_date = date('Y-m-01', strtotime("-11 month", $end_date_strtotime));
			
            $sql = " SELECT ";
            $sql .= "  MONTHNAME(posts.post_date) 				AS 'Month'";
            $sql .= ", SUM(meta_value) 							AS 'TotalAmount'";			
			$sql .= ", DATE_FORMAT(posts.post_date,'%Y-%m') 	AS month_key";
            $sql .= "  FROM {$wpdb->prefix}posts 				AS posts";
			
            $sql .= "  LEFT JOIN  {$wpdb->prefix}postmeta as postmeta ON posts.ID=postmeta.post_id";
			            
            if($this->constants['post_order_status_found'] == 0 ){
				if(count($shop_order_status)>0){
						$sql .= " 
						LEFT JOIN  {$wpdb->prefix}term_relationships 	as term_relationships 	ON term_relationships.object_id		=	posts.ID
						LEFT JOIN  {$wpdb->prefix}term_taxonomy 		as term_taxonomy 		ON term_taxonomy.term_taxonomy_id	=	term_relationships.term_taxonomy_id";
				}
			}
            
            $sql .= " WHERE 1*1 ";
			
			/*
            	$sql .= " DATE(posts.post_date) >= DATE_SUB(now(), interval 11 month) AND NOW()";
            */
		
			$sql .= "  AND post_type='shop_order'";
			
			$sql .= "  AND meta_key='_order_total'";
            
           if($this->constants['post_order_status_found'] == 0 ){
				if(count($shop_order_status)>0){
					$in_shop_order_status = implode(",",$shop_order_status);
					$sql .= " AND  term_taxonomy.term_id IN ({$in_shop_order_status})";
				}
			}else{
				if(count($shop_order_status)>0){
					$in_shop_order_status		= implode("', '",$shop_order_status);
					$sql .= " AND  posts.post_status IN ('{$in_shop_order_status}')";
				}
			}
            
            /*if ($start_date != NULL && $end_date != NULL) {
                $sql .= " AND DATE(posts.post_date) BETWEEN '{$start_date}' AND '{$end_date}'";
            }*/
			
			$order_date_field_key = isset($_REQUEST['order_date_field_key']) ? $_REQUEST['order_date_field_key'] : 'post_date';
			if($order_date_field_key == "post_date" || $order_date_field_key == "post_modified"){
				if ($start_date != NULL &&  $end_date !=NULL){
					$sql .= " AND DATE(posts.{$order_date_field_key}) BETWEEN '".$start_date."' AND '". $end_date ."'";
				}
			}
            
            if (count($hide_order_status) > 0) {
                $in_hide_order_status = implode("', '", $hide_order_status);
                $sql .= " AND  posts.post_status NOT IN ('{$in_hide_order_status}')";
            }
            
            $sql .= " GROUP BY month_key";
           
		    $sql .= " ORDER BY month_key;";
            
            $order_items = $wpdb->get_results($sql);
			
			//$this->print_sql($sql);
			//$this->print_array($order_items);
            $months    = array();
            $dataArray = array();
            $datamonth = array();
            $new_data  = array();			
            
			$end_date_strtotime   				= date("Y-m-01",$end_date_strtotime);
			$end_date_strtotime   				= strtotime($end_date_strtotime);
			
            for ($i = 11; $i >= 0; $i--) {
				array_push($months, date('F', strtotime("-$i month", $end_date_strtotime)));
            }
            
            foreach ($order_items as $key => $order_item) {
                $Month 				= $order_item->Month;
                $Amount            	= $order_item->TotalAmount;
                $dataArray[$Month] 	= $Amount;
                $datamonth[] 		= $Month;
            }
            foreach ($months as $month) {
                if (in_array($month, $datamonth)) {
                    $new_data[$month] = isset($dataArray[$month]) ? $dataArray[$month] : 0;
                } else {
                    $new_data[$month] = 0;
                }
            }
            $new_data2 = array();
            $i         = 0;
            foreach ($new_data as $key => $value) {
                $new_data2[$i]["Label"] = $key;
                $new_data2[$i]["Value"] = $value;                
                $i++;
            }
			
            return $new_data2;
            
        }
        
        function bar_chart_sales_by_days($shop_order_status, $hide_order_status, $start_date, $end_date)
        {
            global $wpdb;
            
            $weekarray = array();
            $timestamp = time();
            for ($i = 0; $i < 30; $i++) {
                $weekarray[] = date('Y-m-d', $timestamp);
                $timestamp -= 24 * 3600;
            }
            $weekarray = array_reverse($weekarray);
            //print_array($weekarray);
			
			$start_date = date("Y-m-d",strtotime('-30 day', strtotime($end_date)));
			
            $sql       = " SELECT    
                DATE(posts.post_date) AS 'Date' ,
                sum(meta_value) AS 'TotalAmount'
                
                FROM {$wpdb->prefix}posts as posts 
                
                LEFT JOIN  {$wpdb->prefix}postmeta as postmeta ON posts.ID=postmeta.post_id";
            
           if($this->constants['post_order_status_found'] == 0 ){
				if(count($shop_order_status)>0){
						$sql .= " 
						LEFT JOIN  {$wpdb->prefix}term_relationships 	as term_relationships 	ON term_relationships.object_id		=	posts.ID
						LEFT JOIN  {$wpdb->prefix}term_taxonomy 		as term_taxonomy 		ON term_taxonomy.term_taxonomy_id	=	term_relationships.term_taxonomy_id";
				}
			}
            
            $sql .= "
                
                
                WHERE  post_type='shop_order' AND meta_key='_order_total' AND (posts.post_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY))";
            
            if($this->constants['post_order_status_found'] == 0 ){
				if(count($shop_order_status)>0){
					$in_shop_order_status = implode(",",$shop_order_status);
					$sql .= " AND  term_taxonomy.term_id IN ({$in_shop_order_status})";
				}
			}else{
				if(count($shop_order_status)>0){
					$in_shop_order_status		= implode("', '",$shop_order_status);
					$sql .= " AND  posts.post_status IN ('{$in_shop_order_status}')";
				}
			}
            
            if ($start_date != NULL && $end_date != NULL) {
                $sql .= " AND DATE(posts.post_date) BETWEEN '{$start_date}' AND '{$end_date}'";
            }
            
            if (count($hide_order_status) > 0) {
                $in_hide_order_status = implode("', '", $hide_order_status);
                $sql .= " AND  posts.post_status NOT IN ('{$in_hide_order_status}')";
            }
            
            $sql .= "
                GROUP BY  DATE(posts.post_date)
                ";
            
            //    echo $sql;
            $order_items = $wpdb->get_results($sql);
            
            $item_dates = array();
            $item_data  = array();
            
            foreach ($order_items as $item) {
                $item_dates[]           = trim($item->Date);
                $item_data[$item->Date] = $item->TotalAmount;
            }
            
            //print_array($item_dates);
            //print_array($item_data);    
            
            $new_data = array();
            
            foreach ($weekarray as $date) {
                //echo $date = trim($date);
                if (in_array($date, $item_dates)) {
                    
                    $new_data[$date] = $item_data[$date];
                } else {
                    $new_data[$date] = 0;
                }
            }
            $new_data2 = array();
            $i         = 0;
            foreach ($new_data as $key => $value) {
                $new_data2[$i]["Label"] = $key;
                $new_data2[$i]["Value"] = $value;
                $new_data2[$i]["Count"] = 0;
                $i++;
                
                
            }
            return $new_data2;
            
        }
        
        function bar_chart_sales_by_week($shop_order_status, $hide_order_status, $start_date, $end_date)
        {
            global $wpdb;
            
            $weekarray = array();
            $timestamp = time();
            for ($i = 0; $i < 7; $i++) {
                $weekarray[] = date('Y-m-d', $timestamp);
                $timestamp -= 24 * 3600;
            }
            
            $sql = " SELECT    
                DATE(posts.post_date) AS 'Date' ,
                sum(meta_value) AS 'TotalAmount'
                
                FROM {$wpdb->prefix}posts as posts 
                
                LEFT JOIN  {$wpdb->prefix}postmeta as postmeta ON posts.ID=postmeta.post_id";
            
            if($this->constants['post_order_status_found'] == 0 ){
				if(count($shop_order_status)>0){
						$sql .= " 
						LEFT JOIN  {$wpdb->prefix}term_relationships 	as term_relationships 	ON term_relationships.object_id		=	posts.ID
						LEFT JOIN  {$wpdb->prefix}term_taxonomy 		as term_taxonomy 		ON term_taxonomy.term_taxonomy_id	=	term_relationships.term_taxonomy_id";
				}
			}
            
            $sql .= "
                
                
                WHERE  post_type='shop_order' AND meta_key='_order_total' AND (posts.post_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY))";
            
            if($this->constants['post_order_status_found'] == 0 ){
				if(count($shop_order_status)>0){
					$in_shop_order_status = implode(",",$shop_order_status);
					$sql .= " AND  term_taxonomy.term_id IN ({$in_shop_order_status})";
				}
			}else{
				if(count($shop_order_status)>0){
					$in_shop_order_status		= implode("', '",$shop_order_status);
					$sql .= " AND  posts.post_status IN ('{$in_shop_order_status}')";
				}
			}
            
            if ($start_date != NULL && $end_date != NULL) {
                $sql .= " AND DATE(posts.post_date) BETWEEN '{$start_date}' AND '{$end_date}'";
            }
            
            if (count($hide_order_status) > 0) {
                $in_hide_order_status = implode("', '", $hide_order_status);
                $sql .= " AND  posts.post_status NOT IN ('{$in_hide_order_status}')";
            }
            
            $sql .= "
                GROUP BY  DATE(posts.post_date)
                ";
            $order_items = $wpdb->get_results($sql);
            
            $item_dates = array();
            $item_data  = array();
            
            foreach ($order_items as $item) {
                $item_dates[]           = trim($item->Date);
                $item_data[$item->Date] = $item->TotalAmount;
            }
            $new_data = array();
            foreach ($weekarray as $date) {
                if (in_array($date, $item_dates)) {
                    
                    $new_data[$date] = $item_data[$date];
                } else {
                    $new_data[$date] = 0;
                }
            }
            
            $new_data2 = array();
            $i         = 0;
            foreach ($new_data as $key => $value) {
                $new_data2[$i]["Label"] = $key;
                $new_data2[$i]["Value"] = $value;
                 $new_data2[$i]["Count"] = 0;
                $i++;
                
                
            }
            return $new_data2;
        }
        
        function get_last_week_order($shop_order_status, $hide_order_status, $start_date, $end_date)
        {
            global $wpdb;
            
            $optionsid    = "sales_last_week";
            //$per_week     = $this->get_number_only($optionsid,$this->per_page_default);
            $per_week     = 4;
            $date_string  = date_i18n("Y-m-d");
            $current_week = $this->current_week = $wpdb->get_var("SELECT WEEK('{$date_string}')");
            $current_year = $this->current_week = $wpdb->get_var("SELECT YEAR('{$date_string}')");
            $weeks        = array();
            
             $start_date = date("Y-m-d",strtotime('-'.($per_week*8).' day', strtotime($end_date)));
            
            if ($current_week > 0) {
                
                $last_week = $current_week - $per_week;
                
                $weeks = array();
                for ($i = 1; $i <= ($per_week); $i++) {
                    $weeks[] = $last_week + $i;
                }
                
                
                $sql_array = array();
                foreach ($weeks as $item) {
                    $sql = "
                                SELECT
                                IFNULL(SUM(postmeta.meta_value) , 0)  AS 'Value'
                                ,count( postmeta.post_id) as Count
                                ,DATE_ADD(MAKEDATE($current_year, 1), INTERVAL $item WEEK) AS Label2
                                ,'{$item}' AS 'Week'
                                ,IF(DATE_ADD(MAKEDATE($current_year, 1), INTERVAL $item WEEK) > CURDATE() , CURDATE(), DATE_ADD(MAKEDATE($current_year, 1), INTERVAL $item WEEK)) as Label
                                FROM {$wpdb->prefix}postmeta as postmeta 
                                LEFT JOIN  {$wpdb->prefix}posts as posts ON posts.ID=postmeta.post_id";
                    if($this->constants['post_order_status_found'] == 0 ){
						if(count($shop_order_status)>0){
								$sql .= " 
								LEFT JOIN  {$wpdb->prefix}term_relationships 	as term_relationships 	ON term_relationships.object_id		=	posts.ID
								LEFT JOIN  {$wpdb->prefix}term_taxonomy 		as term_taxonomy 		ON term_taxonomy.term_taxonomy_id	=	term_relationships.term_taxonomy_id";
						}
					}
                    $sql .= "
                                WHERE meta_key='_order_total'
                                AND WEEK(DATE(posts.post_date)) = $item";
                    if($this->constants['post_order_status_found'] == 0 ){
						if(count($shop_order_status)>0){
							$in_shop_order_status = implode(",",$shop_order_status);
							$sql .= " AND  term_taxonomy.term_id IN ({$in_shop_order_status})";
						}
					}else{
						if(count($shop_order_status)>0){
							$in_shop_order_status		= implode("', '",$shop_order_status);
							$sql .= " AND  posts.post_status IN ('{$in_shop_order_status}')";
						}
					}
                    if (count($hide_order_status) > 0) {
                        $in_hide_order_status = implode("', '", $hide_order_status);
                        $sql .= " AND  posts.post_status NOT IN ('{$in_hide_order_status}')";
                    }
                    
                    $sql_array[] = $sql;
                    $sql         = "";
                }
                
                
                $sql         = implode(" UNION ", $sql_array);
                $order_items = $wpdb->get_results($sql);
                //$this->print_array($order_items);
            } else {
                $sql = "SELECT
                    SUM(postmeta.meta_value)AS 'Value' 
                    ,count( postmeta.post_id) as Count
                    ,DATE_ADD(MAKEDATE($current_year, 1), INTERVAL WEEK( DATE(posts.post_date)) WEEK) AS Label2
                    ,IF(DATE_ADD(MAKEDATE($current_year, 1), INTERVAL WEEK( DATE(posts.post_date)) WEEK) > CURDATE() , CURDATE(), DATE_ADD(MAKEDATE($current_year, 1), INTERVAL WEEK( DATE(posts.post_date)) WEEK)) as Label
                    ,WEEK( DATE(posts.post_date)) AS 'Week'
                    
                    FROM {$wpdb->prefix}postmeta as postmeta 
                    LEFT JOIN  {$wpdb->prefix}posts as posts ON posts.ID=postmeta.post_id";
                if($this->constants['post_order_status_found'] == 0 ){
					if(count($shop_order_status)>0){
							$sql .= " 
							LEFT JOIN  {$wpdb->prefix}term_relationships 	as term_relationships 	ON term_relationships.object_id		=	posts.ID
							LEFT JOIN  {$wpdb->prefix}term_taxonomy 		as term_taxonomy 		ON term_taxonomy.term_taxonomy_id	=	term_relationships.term_taxonomy_id";
					}
				}
                $sql .= "
                    WHERE meta_key='_order_total'
                    AND YEAR('{$date_string}') =  YEAR(DATE(posts.post_date))";
               if($this->constants['post_order_status_found'] == 0 ){
					if(count($shop_order_status)>0){
						$in_shop_order_status = implode(",",$shop_order_status);
						$sql .= " AND  term_taxonomy.term_id IN ({$in_shop_order_status})";
					}
				}else{
					if(count($shop_order_status)>0){
						$in_shop_order_status		= implode("', '",$shop_order_status);
						$sql .= " AND  posts.post_status IN ('{$in_shop_order_status}')";
					}
				}
                if (count($hide_order_status) > 0) {
                    $in_hide_order_status = implode("', '", $hide_order_status);
                    $sql .= " AND  posts.post_status NOT IN ('{$in_hide_order_status}')";
                }
                $sql .= " 
                    GROUP BY Week
                    order by posts.post_date ASC LIMIT 0, $per_week;";
                
                $order_items = $wpdb->get_results($sql);
                //$this->print_array($order_items);
            }
            return $order_items;
            
            
        }
        
        
        
        function pie_chart_top_product($shop_order_status, $hide_order_status, $start_date, $end_date)
        {
            global $wpdb, $sql, $Limit;
            
            $optionsid = "top_product_per_page";
            $per_page  = $this->get_number_only($optionsid, $this->per_page_default);
            
            $sql = "SELECT  
                woocommerce_order_items.order_item_name AS 'Label'
                ,woocommerce_order_items.order_item_id
                ,SUM(woocommerce_order_itemmeta2.meta_value) AS 'Value'
            
                FROM {$wpdb->prefix}woocommerce_order_items as woocommerce_order_items
                LEFT JOIN    {$wpdb->prefix}posts                        as posts                         ON posts.ID                                        =    woocommerce_order_items.order_id            
                LEFT JOIN    {$wpdb->prefix}woocommerce_order_itemmeta     as woocommerce_order_itemmeta3    ON woocommerce_order_itemmeta3.order_item_id    =    woocommerce_order_items.order_item_id
                LEFT JOIN    {$wpdb->prefix}woocommerce_order_itemmeta     as woocommerce_order_itemmeta2     ON woocommerce_order_itemmeta2.order_item_id    =    woocommerce_order_items.order_item_id
                ";
            
            if($this->constants['post_order_status_found'] == 0 ){
				if(count($shop_order_status)>0){
						$sql .= " 
						LEFT JOIN  {$wpdb->prefix}term_relationships 	as term_relationships 	ON term_relationships.object_id		=	posts.ID
						LEFT JOIN  {$wpdb->prefix}term_taxonomy 		as term_taxonomy 		ON term_taxonomy.term_taxonomy_id	=	term_relationships.term_taxonomy_id";
				}
			}
            
            $sql .= "            
                WHERE 
                posts.post_type                                 =    'shop_order'
                AND woocommerce_order_itemmeta3.meta_key        =    '_product_id'
                AND woocommerce_order_itemmeta2.meta_key        =    '_line_total'";
            
           if($this->constants['post_order_status_found'] == 0 ){
				if(count($shop_order_status)>0){
					$in_shop_order_status = implode(",",$shop_order_status);
					$sql .= " AND  term_taxonomy.term_id IN ({$in_shop_order_status})";
				}
			}else{
				if(count($shop_order_status)>0){
					$in_shop_order_status		= implode("', '",$shop_order_status);
					$sql .= " AND  posts.post_status IN ('{$in_shop_order_status}')";
				}
			}
            
            if ($start_date != NULL && $end_date != NULL) {
                $sql .= " AND DATE(posts.post_date) BETWEEN '{$start_date}' AND '{$end_date}'";
            }
            
            if (count($hide_order_status) > 0) {
                $in_hide_order_status = implode("', '", $hide_order_status);
                $sql .= " AND  posts.post_status NOT IN ('{$in_hide_order_status}')";
            }
            
            $sql .= "
                
                GROUP BY  woocommerce_order_itemmeta3.meta_value
                Order By Value DESC
                LIMIT {$per_page}";
            $order_items = $wpdb->get_results($sql);
            return $order_items;
        }
        
        function sales_order_status($shop_order_status, $hide_order_status, $start_date, $end_date)
        {
            global $wpdb;
			$sql = "SELECT
			
			COUNT(postmeta.meta_value) AS 'Count'
			,SUM(postmeta.meta_value) AS Value";
			if($this->constants['post_order_status_found'] == 0 ){
				$sql .= "  ,terms.name As 'Label', term_taxonomy.term_id AS 'StatusID'";
			
				$sql .= "  FROM {$wpdb->prefix}posts as posts";
				
				$sql .= "
				LEFT JOIN  {$wpdb->prefix}term_relationships as term_relationships ON term_relationships.object_id=posts.ID
				LEFT JOIN  {$wpdb->prefix}term_taxonomy as term_taxonomy ON term_taxonomy.term_taxonomy_id=term_relationships.term_taxonomy_id
				LEFT JOIN  {$wpdb->prefix}terms as terms ON terms.term_id=term_taxonomy.term_id";
			}else{
				$sql .= "  ,posts.post_status As 'Status' ,posts.post_status As 'StatusID'";
				$sql .= "  FROM {$wpdb->prefix}posts as posts";
			}
			
			$sql .= "
			LEFT JOIN  {$wpdb->prefix}postmeta as postmeta ON postmeta.post_id=posts.ID
			WHERE postmeta.meta_key = '_order_total'  AND posts.post_type='shop_order' ";
			
			if ($start_date != NULL &&  $end_date !=NULL){
				$sql .= " AND DATE(posts.post_date) BETWEEN '{$start_date}' AND '{$end_date}'";
			}
			
				
			
			$url_hide_order_status = "";
			if(count($hide_order_status)>0){
				$in_hide_order_status		= implode("', '",$hide_order_status);
				$sql .= " AND  posts.post_status NOT IN ('{$in_hide_order_status}')";
				
				$url_hide_order_status	= implode(",",$hide_order_status);
				$url_hide_order_status = "&hide_order_status=".$url_hide_order_status;
			}
			
			if($this->constants['post_order_status_found'] == 0 ){
				$sql .= " AND  term_taxonomy.taxonomy = 'shop_order_status'";
			}
			
			//Added 20150217
			$show_seleted_order_status	= $this->get_setting('show_seleted_order_status',$this->constants['plugin_options'], 0);
			if($show_seleted_order_status == 1){
				$url_shop_order_status	= "";
				if($this->constants['post_order_status_found'] == 0 ){
					if(count($shop_order_status)>0){
						$in_shop_order_status = implode(",",$shop_order_status);
						$sql .= " AND  term_taxonomy.term_id IN ({$in_shop_order_status})";
						
						$url_shop_order_status	= "&order_status_id=".$in_shop_order_status;
					}
				}else{
					if(count($shop_order_status)>0){
						$in_shop_order_status		= implode("', '",$shop_order_status);
						$sql .= " AND  posts.post_status IN ('{$in_shop_order_status}')";
						
						$url_shop_order_status	= implode(",",$shop_order_status);
						$url_shop_order_status	= "&order_status=".$url_shop_order_status;
					}
				}
			}
			
			if($this->constants['post_order_status_found'] == 0 ){
				$sql .= " Group BY terms.term_id ORDER BY Value DESC";
			}else{
				$sql .= " Group BY posts.post_status ORDER BY Value DESC";
			}
			
			$order_items = $wpdb->get_results($sql);			
			if(count($order_items)>0){					
					if($this->constants['post_order_status_found'] == 1 ){
						$order_statuses = array();
						if(function_exists('wc_get_order_statuses')){
							$order_statuses = wc_get_order_statuses();
						}
												
						foreach($order_items as $key  => $value){
							$order_items[$key]->Label = isset($order_statuses[$value->Status]) ? $order_statuses[$value->Status] : $value->Status;
							$order_statuses['wc-pending'] = str_replace(" Payment", "",$order_statuses['wc-pending']);
						}
					}
			}
			
            return $order_items;
            
        }
        
        function top_billing_country($shop_order_status, $hide_order_status, $start_date, $end_date)
        {
            
            global $wpdb;
            $optionsid = "top_billing_country_per_page";
            $per_page  = $this->get_number_only($optionsid, $this->per_page_default);
			$billing_or_shipping	= $this->get_setting('billing_or_shipping',$this->constants['plugin_options'], 'billing');
            
            $sql = "
            SELECT SUM(postmeta1.meta_value)     AS 'Value' 
            ,postmeta2.meta_value                 AS 'Label'
            FROM {$wpdb->prefix}posts as posts
            LEFT JOIN  {$wpdb->prefix}postmeta as postmeta1 ON postmeta1.post_id=posts.ID
            LEFT JOIN  {$wpdb->prefix}postmeta as postmeta2 ON postmeta2.post_id=posts.ID
            ";
            
            if($this->constants['post_order_status_found'] == 0 ){
				if(count($shop_order_status)>0){
						$sql .= " 
						LEFT JOIN  {$wpdb->prefix}term_relationships 	as term_relationships 	ON term_relationships.object_id		=	posts.ID
						LEFT JOIN  {$wpdb->prefix}term_taxonomy 		as term_taxonomy 		ON term_taxonomy.term_taxonomy_id	=	term_relationships.term_taxonomy_id";
				}
			}
            
            $sql .= "
            WHERE
            posts.post_type            =    'shop_order'  
            AND postmeta1.meta_key    =    '_order_total' 
            AND postmeta2.meta_key    =    '_{$billing_or_shipping}_country'";
            
           if($this->constants['post_order_status_found'] == 0 ){
				if(count($shop_order_status)>0){
					$in_shop_order_status = implode(",",$shop_order_status);
					$sql .= " AND  term_taxonomy.term_id IN ({$in_shop_order_status})";
				}
			}else{
				if(count($shop_order_status)>0){
					$in_shop_order_status		= implode("', '",$shop_order_status);
					$sql .= " AND  posts.post_status IN ('{$in_shop_order_status}')";
				}
			}
            
            if ($start_date != NULL && $end_date != NULL) {
                $sql .= " AND DATE(posts.post_date) BETWEEN '{$start_date}' AND '{$end_date}'";
            }
            
            if (count($hide_order_status) > 0) {
                $in_hide_order_status = implode("', '", $hide_order_status);
                $sql .= " AND  posts.post_status NOT IN ('{$in_hide_order_status}')";
            }
            
            $sql .= "
            GROUP BY  postmeta2.meta_value 
            Order By Value DESC                        
            LIMIT {$per_page}";
            
            $order_items = $wpdb->get_results($sql);
            $country     = $this->get_wc_countries();//Added 20150225
            
            if (count($order_items) > 0):
                foreach ($order_items as $key => $order_item) {
                    //echo $order_item->Value;
                    if (isset($country->countries[$order_item->Label])) {
                        $order_item->Label = $country->countries[$order_item->Label];
                    }
                }
            endif;
            return $order_items;
            
        }
		
		 function top_billing_state($shop_order_status, $hide_order_status, $start_date, $end_date)
        {
            
            global $wpdb;
            $optionsid = "top_billing_state_per_page";
            $per_page  = $this->get_number_only($optionsid, $this->per_page_default);
			$billing_or_shipping	= $this->get_setting('billing_or_shipping',$this->constants['plugin_options'], 'billing');
            
           $sql = "
				SELECT SUM(postmeta1.meta_value) AS 'Value' 
				,postmeta2.meta_value AS 'Label'
				,postmeta2.meta_value AS 'billing_state'
				,postmeta3.meta_value AS 'billing_country'
				,COUNT(*) AS 'OrderCount'
				
				FROM {$wpdb->prefix}posts as posts
				LEFT JOIN  {$wpdb->prefix}postmeta as postmeta1 ON postmeta1.post_id=posts.ID
				LEFT JOIN  {$wpdb->prefix}postmeta as postmeta2 ON postmeta2.post_id=posts.ID
				LEFT JOIN  {$wpdb->prefix}postmeta as postmeta3 ON postmeta3.post_id=posts.ID";
				if($this->constants['post_order_status_found'] == 0 ){
					if(count($shop_order_status)>0){
						$sql .= " 
						LEFT JOIN  {$wpdb->prefix}term_relationships 	as term_relationships 	ON term_relationships.object_id		=	posts.ID
						LEFT JOIN  {$wpdb->prefix}term_taxonomy 		as term_taxonomy 		ON term_taxonomy.term_taxonomy_id	=	term_relationships.term_taxonomy_id";
					}
				}
				$sql .= "
				WHERE
				posts.post_type			=	'shop_order'  
				AND postmeta1.meta_key	=	'_order_total' 
				AND postmeta2.meta_key	=	'_{$billing_or_shipping}_state'
				AND postmeta3.meta_key	=	'_{$billing_or_shipping}_country'";
            
           if($this->constants['post_order_status_found'] == 0 ){
				if(count($shop_order_status)>0){
					$in_shop_order_status = implode(",",$shop_order_status);
					$sql .= " AND  term_taxonomy.term_id IN ({$in_shop_order_status})";
				}
			}else{
				if(count($shop_order_status)>0){
					$in_shop_order_status		= implode("', '",$shop_order_status);
					$sql .= " AND  posts.post_status IN ('{$in_shop_order_status}')";
				}
			}
            
            if ($start_date != NULL && $end_date != NULL) {
                $sql .= " AND DATE(posts.post_date) BETWEEN '{$start_date}' AND '{$end_date}'";
            }
            
            if (count($hide_order_status) > 0) {
                $in_hide_order_status = implode("', '", $hide_order_status);
                $sql .= " AND  posts.post_status NOT IN ('{$in_hide_order_status}')";
            }
            
            $sql .= "
            GROUP BY  postmeta2.meta_value 
            Order By Value DESC                        
            LIMIT {$per_page}";
            
            $order_items = $wpdb->get_results($sql);
            $country     = $this->get_wc_countries();//Added 20150225
            
            if (count($order_items) > 0):
                foreach ($order_items as $key => $order_item) {
					
					
                    //echo $order_item->Value;
                    if (isset($country->countries[$order_item->Label])) {
						$billing_state =  $this->get_billling_state_name($order_item->billing_country,$order_item->billing_state);
                        $order_item->Label = $country->countries[$order_item->Label];
                    }
                }//
            endif;
            return $order_items;
            
        }
		
		
        
        function top_payment_gateway($shop_order_status, $hide_order_status, $start_date, $end_date)
        {
            global $wpdb, $options;
            
            $optionsid = "top_payment_gateway_per_page";
            $per_page  = $this->get_number_only($optionsid, $this->per_page_default);
            $sql       = "SELECT postmeta1.meta_value AS 'Label' 
                    ,SUM(postmeta2.meta_value) AS 'Value'
                    ,COUNT(postmeta1.meta_value) As 'order_count'
					 ,COUNT(postmeta1.meta_value) As 'Count'
                    
            FROM {$wpdb->prefix}posts as posts
            LEFT JOIN  {$wpdb->prefix}postmeta as postmeta1 ON postmeta1.post_id=posts.ID
            LEFT JOIN  {$wpdb->prefix}postmeta as postmeta2 ON postmeta2.post_id=posts.ID";
            
            if($this->constants['post_order_status_found'] == 0 ){
				if(count($shop_order_status)>0){
						$sql .= " 
						LEFT JOIN  {$wpdb->prefix}term_relationships 	as term_relationships 	ON term_relationships.object_id		=	posts.ID
						LEFT JOIN  {$wpdb->prefix}term_taxonomy 		as term_taxonomy 		ON term_taxonomy.term_taxonomy_id	=	term_relationships.term_taxonomy_id";
				}
			}
            
            $sql .= "
            
            
            
            WHERE  
            posts.post_type            =    'shop_order'  
            AND    postmeta1.meta_key='_payment_method_title' 
            AND postmeta2.meta_key='_order_total' ";
            
           if($this->constants['post_order_status_found'] == 0 ){
				if(count($shop_order_status)>0){
					$in_shop_order_status = implode(",",$shop_order_status);
					$sql .= " AND  term_taxonomy.term_id IN ({$in_shop_order_status})";
				}
			}else{
				if(count($shop_order_status)>0){
					$in_shop_order_status		= implode("', '",$shop_order_status);
					$sql .= " AND  posts.post_status IN ('{$in_shop_order_status}')";
				}
			}
            
            if ($start_date != NULL && $end_date != NULL) {
                $sql .= " AND DATE(posts.post_date) BETWEEN '{$start_date}' AND '{$end_date}'";
            }
            
            if (count($hide_order_status) > 0) {
                $in_hide_order_status = implode("', '", $hide_order_status);
                $sql .= " AND  posts.post_status NOT IN ('{$in_hide_order_status}')";
            }
            
            $sql .= "
            
            GROUP BY postmeta1.meta_value
            Order BY Value DESC LIMIT {$per_page}";
            
            $order_items = $wpdb->get_results($sql);
            return $order_items;
            
        }
        
        function top_customer_list($shop_order_status, $hide_order_status, $start_date, $end_date)
        {
            
            global $wpdb, $options;
            $optionsid = "top_customer_per_page";
            $per_page  = $this->get_number_only($optionsid, $this->per_page_default);
            
            $sql = "SELECT SUM(postmeta1.meta_value) AS 'Value' 
                                ,postmeta2.meta_value AS '_billing_email'
                                ,postmeta3.meta_value AS 'Label'
                                ,Count(postmeta2.meta_value) AS 'OrderCount'
                        FROM {$wpdb->prefix}posts as posts
                        LEFT JOIN  {$wpdb->prefix}postmeta as postmeta1 ON postmeta1.post_id=posts.ID
                        LEFT JOIN  {$wpdb->prefix}postmeta as postmeta2 ON postmeta2.post_id=posts.ID
                        LEFT JOIN  {$wpdb->prefix}postmeta as postmeta3 ON postmeta3.post_id=posts.ID";
            
            if($this->constants['post_order_status_found'] == 0 ){
				if(count($shop_order_status)>0){
						$sql .= " 
						LEFT JOIN  {$wpdb->prefix}term_relationships 	as term_relationships 	ON term_relationships.object_id		=	posts.ID
						LEFT JOIN  {$wpdb->prefix}term_taxonomy 		as term_taxonomy 		ON term_taxonomy.term_taxonomy_id	=	term_relationships.term_taxonomy_id";
				}
			}
            
            $sql .= "
                        WHERE  
                            posts.post_type='shop_order'  
                            AND postmeta1.meta_key='_order_total' 
                            AND postmeta2.meta_key='_billing_email'  
                            AND postmeta3.meta_key='_billing_first_name'";
            
           if($this->constants['post_order_status_found'] == 0 ){
				if(count($shop_order_status)>0){
					$in_shop_order_status = implode(",",$shop_order_status);
					$sql .= " AND  term_taxonomy.term_id IN ({$in_shop_order_status})";
				}
			}else{
				if(count($shop_order_status)>0){
					$in_shop_order_status		= implode("', '",$shop_order_status);
					$sql .= " AND  posts.post_status IN ('{$in_shop_order_status}')";
				}
			}
            
            if ($start_date != NULL && $end_date != NULL) {
                $sql .= " AND DATE(posts.post_date) BETWEEN '{$start_date}' AND '{$end_date}'";
            }
            
            if (count($hide_order_status) > 0) {
                $in_hide_order_status = implode("', '", $hide_order_status);
                $sql .= " AND  posts.post_status NOT IN ('{$in_hide_order_status}')";
            }
            
            $sql .= "
                        GROUP BY  postmeta2.meta_value
                        Order By Value DESC
                        LIMIT {$per_page}";
            $order_items = $wpdb->get_results($sql);
            return $order_items;
        }
        
        function top_product_list($shop_order_status, $hide_order_status, $start_date, $end_date){
            global $wpdb, $options;
            $optionsid = "top_product_per_page";
            $per_page  = $this->get_number_only($optionsid, $this->per_page_default);
			
			$product_status 		= $this->get_setting('product_status',$this->constants['plugin_options'], array());
			
            $sql       = "SELECT 

            woocommerce_order_items.order_item_id
            ,SUM(woocommerce_order_itemmeta.meta_value) AS 'Qty'
            ,SUM(woocommerce_order_itemmeta2.meta_value) AS 'Value'
            ,woocommerce_order_itemmeta3.meta_value AS ProductID";
			
			if(count($product_status)>0){
				$sql .= " ,products.post_title 	AS 'Label'";
			}else{
				$sql .= " ,woocommerce_order_items.order_item_name			AS 'Label'";
			}
            
			 $sql       .= "
            FROM         {$wpdb->prefix}woocommerce_order_items         as woocommerce_order_items
            LEFT JOIN    {$wpdb->prefix}posts                        	as posts                         ON posts.ID                                     =    woocommerce_order_items.order_id
            LEFT JOIN    {$wpdb->prefix}woocommerce_order_itemmeta     as woocommerce_order_itemmeta     ON woocommerce_order_itemmeta.order_item_id     =    woocommerce_order_items.order_item_id
            LEFT JOIN    {$wpdb->prefix}woocommerce_order_itemmeta     as woocommerce_order_itemmeta2    ON woocommerce_order_itemmeta2.order_item_id    =    woocommerce_order_items.order_item_id
            LEFT JOIN    {$wpdb->prefix}woocommerce_order_itemmeta     as woocommerce_order_itemmeta3    ON woocommerce_order_itemmeta3.order_item_id    =    woocommerce_order_items.order_item_id
            
            ";
			
            if($this->constants['post_order_status_found'] == 0 ){
				if(count($shop_order_status)>0){
						$sql .= " 
						LEFT JOIN  {$wpdb->prefix}term_relationships 	as term_relationships 	ON term_relationships.object_id		=	posts.ID
						LEFT JOIN  {$wpdb->prefix}term_taxonomy 		as term_taxonomy 		ON term_taxonomy.term_taxonomy_id	=	term_relationships.term_taxonomy_id";
				}
			}
			
			if(count($product_status)>0){
				$sql .= " LEFT JOIN {$wpdb->prefix}posts AS products ON products.ID = woocommerce_order_itemmeta3.meta_value";
			}
			
            $sql .= "
            WHERE
            posts.post_type                                 =    'shop_order'
            AND woocommerce_order_itemmeta.meta_key         =    '_qty'
            AND woocommerce_order_itemmeta2.meta_key        =    '_line_total' 
            AND woocommerce_order_itemmeta3.meta_key        =    '_product_id'";
            
            if($this->constants['post_order_status_found'] == 0 ){
				if(count($shop_order_status)>0){
					$in_shop_order_status = implode(",",$shop_order_status);
					$sql .= " AND  term_taxonomy.term_id IN ({$in_shop_order_status})";
				}
			}else{
				if(count($shop_order_status)>0){
					$in_shop_order_status		= implode("', '",$shop_order_status);
					$sql .= " AND  posts.post_status IN ('{$in_shop_order_status}')";
				}
			}
            
            if ($start_date != NULL && $end_date != NULL) {
                $sql .= " AND DATE(posts.post_date) BETWEEN '{$start_date}' AND '{$end_date}'";
            }
            
            $url_hide_order_status = "";
            if (count($hide_order_status) > 0) {
                $in_hide_order_status = implode("', '", $hide_order_status);
                $sql .= " AND  posts.post_status NOT IN ('{$in_hide_order_status}')";                
            }
			
			if(count($product_status)>0){
				$in_product_status = implode("','",$product_status);
				$sql .= " AND products.post_type IN ('product')";
				$sql .= " AND products.post_status IN ('{$in_product_status}')";
			}
			
            $sql .= " 
            
            GROUP BY  woocommerce_order_itemmeta3.meta_value
            Order By Value DESC
            LIMIT {$per_page}";
            $order_items = $wpdb->get_results($sql);
			
			if($wpdb->num_rows <= 0){
				$order_items = array();
			}
			
            return $order_items;
        }        
        
        function top_coupon_list($shop_order_status, $hide_order_status, $start_date, $end_date){
            
            global $wpdb, $options;
            
            $optionsid = "top_coupon_per_page";
            $per_page  = $this->get_number_only($optionsid, $this->per_page_default);
            $sql       = "SELECT *, 
            woocommerce_order_items.order_item_name as Label, 
            SUM(woocommerce_order_itemmeta.meta_value) As 'Value', 
            woocommerce_order_itemmeta.meta_value AS 'coupon_amount' , 
            Count(*) AS 'Count' 
            FROM {$wpdb->prefix}woocommerce_order_items as woocommerce_order_items 
            LEFT JOIN    {$wpdb->prefix}posts                        as posts                         ON posts.ID                                        =    woocommerce_order_items.order_id
            LEFT JOIN  {$wpdb->prefix}woocommerce_order_itemmeta     as woocommerce_order_itemmeta    ON woocommerce_order_itemmeta.order_item_id        =    woocommerce_order_items.order_item_id
            ";
            
            if($this->constants['post_order_status_found'] == 0 ){
				if(count($shop_order_status)>0){
						$sql .= " 
						LEFT JOIN  {$wpdb->prefix}term_relationships 	as term_relationships 	ON term_relationships.object_id		=	posts.ID
						LEFT JOIN  {$wpdb->prefix}term_taxonomy 		as term_taxonomy 		ON term_taxonomy.term_taxonomy_id	=	term_relationships.term_taxonomy_id";
				}
			}
            
            $sql .= "
            WHERE 
            posts.post_type                                 =    'shop_order'
            AND woocommerce_order_items.order_item_type='coupon' 
            AND woocommerce_order_itemmeta.meta_key='discount_amount'";
            
           if($this->constants['post_order_status_found'] == 0 ){
				if(count($shop_order_status)>0){
					$in_shop_order_status = implode(",",$shop_order_status);
					$sql .= " AND  term_taxonomy.term_id IN ({$in_shop_order_status})";
				}
			}else{
				if(count($shop_order_status)>0){
					$in_shop_order_status		= implode("', '",$shop_order_status);
					$sql .= " AND  posts.post_status IN ('{$in_shop_order_status}')";
				}
			}
            
            if ($start_date != NULL && $end_date != NULL) {
                $sql .= " AND DATE(posts.post_date) BETWEEN '{$start_date}' AND '{$end_date}'";
            }
            
            if (count($hide_order_status) > 0) {
                $in_hide_order_status = implode("', '", $hide_order_status);
                $sql .= " AND  posts.post_status NOT IN ('{$in_hide_order_status}')";
            }
            
            $sql .= "
            Group BY woocommerce_order_items.order_item_name
            ORDER BY Value DESC
            LIMIT {$per_page}";
            
            $order_items = $wpdb->get_results($sql);
            return $order_items;
        }
		
		//New Change ID 20150206
		function get_category_list($shop_order_status, $hide_order_status, $start_date, $end_date){
            
            global $wpdb, $options;
            
            $optionsid = "top_category_per_page";
            $per_page  = $this->get_number_only($optionsid, $this->per_page_default);
			
            $sql ="";
			$sql .= " SELECT ";
			$sql .= " SUM(woocommerce_order_itemmeta_product_qty.meta_value) AS quantity";
			$sql .= " ,SUM(woocommerce_order_itemmeta_product_line_total.meta_value) AS Value";
			$sql .= " ,terms_product_id.term_id AS category_id";
			$sql .= " ,terms_product_id.name AS Label";
			$sql .= " ,term_taxonomy_product_id.parent AS parent_category_id";
			$sql .= " ,terms_parent_product_id.name AS parent_category_name";
			
			$sql .= " FROM {$wpdb->prefix}woocommerce_order_items as woocommerce_order_items";
			
			$sql .= " LEFT JOIN  {$wpdb->prefix}woocommerce_order_itemmeta as woocommerce_order_itemmeta_product_id ON woocommerce_order_itemmeta_product_id.order_item_id=woocommerce_order_items.order_item_id";
			$sql .= " LEFT JOIN  {$wpdb->prefix}woocommerce_order_itemmeta as woocommerce_order_itemmeta_product_qty ON woocommerce_order_itemmeta_product_qty.order_item_id=woocommerce_order_items.order_item_id";
			$sql .= " LEFT JOIN  {$wpdb->prefix}woocommerce_order_itemmeta as woocommerce_order_itemmeta_product_line_total ON woocommerce_order_itemmeta_product_line_total.order_item_id=woocommerce_order_items.order_item_id";
			
			
			$sql .= " 	LEFT JOIN  {$wpdb->prefix}term_relationships 	as term_relationships_product_id 	ON term_relationships_product_id.object_id		=	woocommerce_order_itemmeta_product_id.meta_value 
						LEFT JOIN  {$wpdb->prefix}term_taxonomy 		as term_taxonomy_product_id 		ON term_taxonomy_product_id.term_taxonomy_id	=	term_relationships_product_id.term_taxonomy_id
						LEFT JOIN  {$wpdb->prefix}terms 				as terms_product_id 				ON terms_product_id.term_id						=	term_taxonomy_product_id.term_id";
			
			$sql .= " 	LEFT JOIN  {$wpdb->prefix}terms 				as terms_parent_product_id 				ON terms_parent_product_id.term_id						=	term_taxonomy_product_id.parent";
			
			$sql .= " LEFT JOIN  {$wpdb->prefix}posts as posts ON posts.id=woocommerce_order_items.order_id";
			
			if($this->constants['post_order_status_found'] == 0 ){
				if(count($shop_order_status)>0){
						$sql .= " 
						LEFT JOIN  {$wpdb->prefix}term_relationships 	as term_relationships 	ON term_relationships.object_id		=	posts.ID
						LEFT JOIN  {$wpdb->prefix}term_taxonomy 		as term_taxonomy 		ON term_taxonomy.term_taxonomy_id	=	term_relationships.term_taxonomy_id";
				}
			}
            
			$sql .= " WHERE 1*1 ";
			$sql .= " AND woocommerce_order_items.order_item_type 					= 'line_item'";
			$sql .= " AND woocommerce_order_itemmeta_product_id.meta_key 			= '_product_id'";
			$sql .= " AND woocommerce_order_itemmeta_product_qty.meta_key 			= '_qty'";
			$sql .= " AND woocommerce_order_itemmeta_product_line_total.meta_key 	= '_line_total'";
			$sql .= " AND term_taxonomy_product_id.taxonomy 						= 'product_cat'";
			$sql .= " AND posts.post_type 											= 'shop_order'";				

            
           if($this->constants['post_order_status_found'] == 0 ){
				if(count($shop_order_status)>0){
					$in_shop_order_status = implode(",",$shop_order_status);
					$sql .= " AND  term_taxonomy.term_id IN ({$in_shop_order_status})";
				}
			}else{
				if(count($shop_order_status)>0){
					$in_shop_order_status		= implode("', '",$shop_order_status);
					$sql .= " AND  posts.post_status IN ('{$in_shop_order_status}')";
				}
			}
            
            if ($start_date != NULL && $end_date != NULL) {
                $sql .= " AND DATE(posts.post_date) BETWEEN '{$start_date}' AND '{$end_date}'";
            }
            
            if (count($hide_order_status) > 0) {
                $in_hide_order_status = implode("', '", $hide_order_status);
                $sql .= " AND  posts.post_status NOT IN ('{$in_hide_order_status}')";
            }
			
			$sql .= " GROUP BY category_id";
			$sql .= " Order By Value DESC";
			$sql .= " LIMIT {$per_page}";
            
			$order_items = $wpdb->get_results($sql);
			
            return $order_items;
        }
        
        public function get_request_($name, $default, $set = false)
        {
            if (isset($_REQUEST[$name])) {
                $newRequest = trim($_REQUEST[$name]);
                return $newRequest;
            } else {
                if ($set)
                    $_REQUEST[$name] = $default;
                return $default;
            }
        }
        
        function get_number_only($name, $default = 0)
        {
            global $wpdb, $options;
            $per_page = (isset($options[$name]) and strlen($options[$name]) > 0) ? $options[$name] : $default;
            $per_page = is_numeric($per_page) ? $per_page : $default;
            return $per_page;
        }
        
        function thirty_days_visit()
        {
            $strtotime  = strtotime(date_i18n("Y-m-d"));
            $start_date = date('Y-m-d', strtotime('-30 day', $strtotime));
            $end_date   = date('Y-m-d', strtotime('-1 day', $strtotime));
            
            include_once('ic_commerce_premium_golden_ga_data.php');
            $ga_data = new IC_Commerce_Premium_Golden_GA_Data($this->constants);
            $return  = $ga_data->ga_report($start_date, $end_date, "date");
            return $return;
        }
        
        function get_ga_summary()
        {
            include_once('ic_commerce_premium_golden_ga_data.php');
            $ga_data = new IC_Commerce_Premium_Golden_GA_Data($this->constants);
            $return  = $ga_data->ga_get_summary(true);
            return $return;
        }
        
        function _get_setting($id, $data, $defalut = NULL)
        {
            if (isset($data[$id]))
                return $data[$id];
            else
                return $defalut;
        }
        
    } //End Class
} //End Graph Class Exists 
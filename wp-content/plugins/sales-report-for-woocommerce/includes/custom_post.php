<?php
class BeRocket_sales_report_custom_post extends BeRocket_custom_post_class {
    public $hook_name = 'berocket_sales_report_custom_post';
    public $conditions;
    public $post_type_parameters = array(
        'can_be_disabled' => true
    );
    protected static $instance;
    function __construct() {
        $this->version = '2.0';
        $this->post_name = 'br_sale_report';
        $this->post_settings = array(
            'label' => __( 'Report Variant', 'sales-report-for-woocommerce' ),
            'labels' => array(
                'menu_name'          => _x( 'Report Variant', 'Admin menu name', 'sales-report-for-woocommerce' ),
                'add_new_item'       => __( 'Add New Report Variant', 'sales-report-for-woocommerce' ),
                'edit'               => __( 'Edit', 'sales-report-for-woocommerce' ),
                'edit_item'          => __( 'Edit Report Variant', 'sales-report-for-woocommerce' ),
                'new_item'           => __( 'New Report Variant', 'sales-report-for-woocommerce' ),
                'view'               => __( 'View Report Variants', 'sales-report-for-woocommerce' ),
                'view_item'          => __( 'View Report Variant', 'sales-report-for-woocommerce' ),
                'search_items'       => __( 'Search Report Variant', 'sales-report-for-woocommerce' ),
                'not_found'          => __( 'No Report Variants found', 'sales-report-for-woocommerce' ),
                'not_found_in_trash' => __( 'No Report Variants found in trash', 'sales-report-for-woocommerce' ),
            ),
            'description'     => __( 'This is where you can add Sales Reports.', 'sales-report-for-woocommerce' ),
            'public'          => true,
            'show_ui'         => true,
            'capability_type' => 'post',
            'publicly_queryable'  => false,
            'exclude_from_search' => true,
            'show_in_menu'        => 'berocket_account',
            'hierarchical'        => false,
            'rewrite'             => false,
            'query_var'           => false,
            'show_in_rest'        => true,
            'supports'            => array( 'title', 'editor' ),
            'show_in_nav_menus'   => false,
        );
        $this->default_settings = array(
            'send_empty'        => '0',
            'status'            => array(),
            'emails'            => '',
            'periodicity_type'  => 'day',
            'periodicity'       => '1',
            'send_time'         => array(
                'hours'     => '0',
                'minutes'   => '0',
            ),
            'send_wait'         => '1',
            'start_date_type'   => 'prev_send_time',
            'start_time'        => array(
                'hours'     => '0',
                'minutes'   => '0',
            ),
            'end_date_type'     => 'send_time',
            'end_time'          => array(
                'hours'     => '0',
                'minutes'   => '0',
            ),
        );
        $this->add_meta_box('settings', __( 'Settings', 'sales-report-for-woocommerce' ));
        $this->add_meta_box('sendinformation', __( 'Send Information', 'sales-report-for-woocommerce' ), false, 'side');
        parent::__construct();

        add_filter( 'default_content', array($this, 'set_post_default_values'), 100, 2 );
        add_action( 'sales_report_framework_construct', array($this, 'plugin_construct'), 10, 1 );
        add_filter( 'berocket_sales_report_start_data_date', array($this, 'report_start_end_data_date'), 10, 3 );
        add_filter( 'berocket_sales_report_end_data_date', array($this, 'report_start_end_data_date'), 10, 3 );
        add_action( 'plugins_loaded', array($this, 'plugins_loaded') );
    }
    public function plugins_loaded() {
        $order_status = wc_get_order_statuses();
        $statuses = array();
        $i = 0;
        foreach($order_status as $status_slug => $status_name) {
            $statuses[$i] = $status_slug;
            $i++;
        }
        $this->default_settings['status'] = $statuses;
        $version = get_option( $this->post_name.'_version');
        if( empty($version) || version_compare($version, $this->version, '<') ) {
            $this->update_version($version);
        }
    }
    public function plugin_construct($BeRocket_sales_report) {
        if( $BeRocket_sales_report->init_validation() and defined( 'DOING_CRON' ) and DOING_CRON ) {
            $sale_reports = $this->get_custom_posts();
            if( ! empty($sale_reports) && is_array($sale_reports) ) {
                foreach($sale_reports as $sale_reports_id) {
                    add_action( 'berocket_get_orders_reports_'.$sale_reports_id, array( $this, 'custom_post_order_callback' ) );
                }
            }
        }
    }
    public function settings($post) {
        wp_enqueue_script( 'berocket_aapf_widget-colorpicker' );
        wp_enqueue_script( 'berocket_aapf_widget-admin' );
        wp_enqueue_style( 'brjsf-ui' );
        wp_enqueue_script( 'brjsf-ui' );
        wp_enqueue_script( 'berocket_framework_admin' );
        wp_enqueue_style( 'berocket_framework_admin_style' );
        wp_enqueue_script( 'berocket_widget-colorpicker' );
        wp_enqueue_style( 'berocket_widget-colorpicker-style' );
        wp_enqueue_style( 'font-awesome' );
        wp_nonce_field($this->post_name.'_check', $this->post_name.'_nonce');
        echo "<script>
        berocket_sales_report_tiny_mce_products = false;
        berocket_sales_report_tiny_mce_data = false;
        berocket_sales_report_tiny_mce_init = function() {
            berocket_sales_report_tiny_mce_products = {
                title: 'Sales Report Content Products',
                body: [
                    {
                        type: 'listbox', 
                        name: 'sort', 
                        label: 'Sort', 
                        'values': [
                            {text: 'Default products sort', value: ''},
                            {text: 'Product name ascending', value: 'name_asc'},
                            {text: 'Product name descending', value: 'name_desc'},
                            {text: 'Buyed quantity ascending', value: 'qty_asc'},
                            {text: 'Buyed quantity descending', value: 'qty_desc'},
                        ]
                    }
                ],
                onsubmit: function (e) {
                    target = '';
                    if(e.data.blank === true) {
                        target += 'newtab=\"on\"';
                    }
                    berocket_tiny_mce_ed.insertContent('[br_sales_report_part content=\"products\" sort=\"' + e.data.sort + '\"]');
                    berocket_sales_report_tiny_mce_init();
                }
            };
            berocket_sales_report_tiny_mce_data = {
                title: 'Sales Report Content',
                body: [
                    {
                        type: 'listbox', 
                        name: 'content', 
                        label: 'Content Type', 
                        'values': [
                            {text: 'Products List', value: 'products'},
                            {text: 'Header', value: 'header'},
                            {text: 'Total Sales', value: 'sales'},
                            {text: 'Order Count', value: 'order_count'}
                        ]
                    }
                ],
                onsubmit: function (e) {
                    target = '';
                    if(e.data.blank === true) {
                        target += 'newtab=\"on\"';
                    }
                    if(e.data.content == 'products') {
                        berocket_tiny_mce_ed.windowManager.open(berocket_sales_report_tiny_mce_products);
                    } else if(typeof(window['berocket_sales_report_tiny_mce_'+e.data.content]) != 'undefined') {
                        berocket_tiny_mce_ed.windowManager.open(window['berocket_sales_report_tiny_mce_'+e.data.content]);
                    } else {
                        berocket_tiny_mce_ed.insertContent('[br_sales_report_part  content=\"' + e.data.content + '\"]');
                        berocket_sales_report_tiny_mce_init();
                    }
                }
            };
            ";
            do_action('berocket_sales_report_tiny_mce_data');
            echo "
        }
        berocket_sales_report_tiny_mce_init();
        </script>";
        $options = $this->get_option( $post->ID );
        $BeRocket_sales_report = BeRocket_sales_report::getInstance();
        $order_status = wc_get_order_statuses();
        $statuses = array();
        $i = 0;
        foreach($order_status as $status_slug => $status_name) {
            $statuses[$status_slug] = array(
                "type"     => "checkbox",
                "label"    => "",
                "label_for"=> $status_name,
                "name"     => array("status", $i),
                "value"    => $status_slug,
            );
            $i++;
        }
        echo '<div class="br_framework_settings br_alabel_settings">';
        $BeRocket_sales_report->display_admin_settings(
            array(
                'General' => array(
                    'icon' => 'cog',
                ),
                'Send Time' => array(
                    'icon' => 'clock-o',
                ),
                'Start Time' => array(
                    'icon' => 'play',
                ),
                'End Time' => array(
                    'icon' => 'pause',
                ),
            ),
            array(
                'General' => array(
                    'status' => array(
                        'label' => __('Status', 'sales-report-for-woocommerce'),
                        'items' => $statuses,
                    ),
                ),
                'Send Time' => array(
                    'periodicity_type' => array(
                        "label"    => __( "Periodicity Type", 'sales-report-for-woocommerce' ),
                        "name"     => 'periodicity_type',   
                        "type"     => "selectbox",
                        "class"    => 'brsr_periodicity_type',
                        "options"  => array(
                            array('value' => 'day', 'text' => __('Every X day(s)', 'sales-report-for-woocommerce')),
                            array('value' => 'month', 'text' => __('Every X month(s)', 'sales-report-for-woocommerce')),
                        ),
                        "value"    => 'day',
                    ),
                    'periodicity' => array(
                        "type"     => "number",
                        "label"    => __('Periodicity', 'sales-report-for-woocommerce'),
                        "label_for"=> ' <span class="brsr_periodicity brsr_periodicity_day">' . __('day(s)', 'sales-report-for-woocommerce') . '</span><span class="brsr_periodicity brsr_periodicity_month">' . __('month(s)', 'sales-report-for-woocommerce') . '</span>',
                        "label_be_for"=> __('Send every ', 'sales-report-for-woocommerce'),
                        "name"     => "periodicity",
                        "extra"    => "min='1'",
                        "value"    => '1',
                    ),
                    'send_time' => array(
                        "label"    => __('Send time', 'sales-report-for-woocommerce'),
                        "items"    => array(
                            array(
                                "type"     => "number",
                                "label_for"=> __(' hour(s)', 'sales-report-for-woocommerce'),
                                "name"     => array("send_time", 'hours'),
                                "extra"    => "min='0' max='23'",
                                "value"    => '0',
                            ),
                            array(
                                "type"     => "number",
                                "label"    => __('Periodicity', 'sales-report-for-woocommerce'),
                                "label_for"=> __(' minute(s)', 'sales-report-for-woocommerce'),
                                "name"     => array("send_time", 'minutes'),
                                "extra"    => "min='0' max='59'",
                                "value"    => '0',
                            ),
                        )
                    ),
                    'send_wait' => array(
                        "type"     => "number",
                        "label"    => __('Wait before send', 'sales-report-for-woocommerce'),
                        "label_for"=> __(' day(s) before first send', 'sales-report-for-woocommerce'),
                        "label_be_for"=> __('Wait ', 'sales-report-for-woocommerce'),
                        "name"     => "send_wait",
                        "extra"    => "min='0'",
                        "value"    => '1',
                    ),
                ),
                'Start Time' => array(
                    'start_date_type' => array(
                        "label"    => __( "Get orders from", 'sales-report-for-woocommerce' ),
                        "name"     => 'start_date_type',   
                        "type"     => "selectbox",
                        "options"  => apply_filters('berocket_sales_report_send_date_types', array()),
                        "value"    => '',
                        "label_for"=> __('Orders will be taken starting from this date', 'sales-report-for-woocommerce'),
                    ),
                    'start_time' => array(
                        "label"    => __('Start time', 'sales-report-for-woocommerce'),
                        "items"    => array(
                            array(
                                "type"     => "number",
                                "label_for"=> __(' hour(s)', 'sales-report-for-woocommerce'),
                                "name"     => array("start_time", 'hours'),
                                "extra"    => "min='0' max='23'",
                                "value"    => '0',
                            ),
                            array(
                                "type"     => "number",
                                "label"    => __('Periodicity', 'sales-report-for-woocommerce'),
                                "label_for"=> __(' minute(s)', 'sales-report-for-woocommerce'),
                                "name"     => array("start_time", 'minutes'),
                                "extra"    => "min='0' max='59'",
                                "value"    => '0',
                            ),
                        )
                    ),
                ),
                'End Time' => array(
                    'end_date_type' => array(
                        "label"    => __( "Get orders till", 'sales-report-for-woocommerce' ),
                        "name"     => 'end_date_type',   
                        "type"     => "selectbox",
                        "options"  => apply_filters('berocket_sales_report_send_date_types', array()),
                        "value"    => '',
                        "label_for"=> __('Orders will be taken until this date', 'sales-report-for-woocommerce'),
                    ),
                    'end_time' => array(
                        "label"    => __('End time', 'sales-report-for-woocommerce'),
                        "items"    => array(
                            array(
                                "type"     => "number",
                                "label_for"=> __(' hour(s)', 'sales-report-for-woocommerce'),
                                "name"     => array("end_time", 'hours'),
                                "extra"    => "min='0' max='23'",
                                "value"    => '0',
                            ),
                            array(
                                "type"     => "number",
                                "label"    => __('Periodicity', 'sales-report-for-woocommerce'),
                                "label_for"=> __(' minute(s)', 'sales-report-for-woocommerce'),
                                "name"     => array("end_time", 'minutes'),
                                "extra"    => "min='0' max='59'",
                                "value"    => '0',
                            ),
                        )
                    ),
                ),
            ),
            array(
                'name_for_filters' => $this->hook_name,
                'hide_header' => true,
                'hide_form' => true,
                'hide_additional_blocks' => true,
                'hide_save_button' => true,
                'settings_name' => $this->post_name,
                'options' => $options
            )
        );
        echo '</div>';
    }
    public function sendinformation($post) {
        global $pagenow;
        if( in_array( $pagenow, array( 'post-new.php' ) ) ) {
            _e( 'You need save it to get information', 'sales-report-for-woocommerce' );
        } else {
            $options = $this->get_option($post->ID);
            $BeRocket_sales_report = BeRocket_sales_report::getInstance();
            $settings = $BeRocket_sales_report->get_option();
            //EMAILS
            echo '<div>';
            echo '<h3>' . __( 'EMails', 'sales-report-for-woocommerce' ) . '</h3>';
            $emails = apply_filters('berocket_sales_report_send_emails', br_get_value_from_array($settings, 'email', ''), $settings, $options);
            if( empty($emails) ) {
                echo '<strong style="color:red;">' . __('Please set at least one email', 'sales-report-for-woocommerce') . '</strong>';
            } else {
                echo $emails;
            }
            echo '</div>';
            //SEND DATE
            echo '<div>';
            echo '<h3>' . __( 'Next Send', 'sales-report-for-woocommerce' ) . '</h3>';
            $send_date = get_post_meta( $post->ID, 'berocket_report_send_date', true );
            echo $send_date;
            echo '</div>';
            echo '<div>';
            echo '<h3>' . __( 'Send Period', 'sales-report-for-woocommerce' ) . '</h3>';
            $date_data_array = $this->prepare_date_array_for_post($post->ID, $settings, $options);
            $date_data_array = $this->start_and_end_time_for_post($post->ID, $date_data_array, $settings, $options);
            extract($date_data_array);
            $date_diff = date_diff($start_datetime, $end_datetime);
            $display_strong = false;
            if( $date_diff->format('%y') == 0 && $date_diff->format('%m') == 0 && $date_diff->format('%d') == 0 && $date_diff->format('%h') < 3 ) {
                echo '<strong style="color:red;">' . __('Too short a period of time', 'sales-report-for-woocommerce') . '<br>';
                $display_strong = true;
            }
            echo '<strong>' . __('From: ', 'sales-report-for-woocommerce') . '</strong>' . $start_datetime->format('Y-m-d H:i') . '<br>'
            . '<strong>' . __(' To: ', 'sales-report-for-woocommerce') . '</strong>' . $end_datetime->format('Y-m-d H:i');
            if( $display_strong ) {
                echo '</strong>';
            }
            echo '</div>';
        }
    }
    public function set_post_default_values( $content, $post ) {
        if( $post->post_type == 'br_sale_report' ) {
            $content = '[br_sales_report_part content="header"]' . "\r\n" .
            '[br_sales_report_part content="sales"]' . "\r\n" .
            '[br_sales_report_part content="order_count"]' . "\r\n" .
            '[br_sales_report_part content="products" extend="sku"]';
        }
        return $content;
    }
    public function wc_save_product_without_check( $post_id, $post ) {
        parent::wc_save_product_without_check( $post_id, $post );
        $options = get_post_meta( $post_id, $this->post_name, true );
        $BeRocket_sales_report = BeRocket_sales_report::getInstance();
        $settings = $BeRocket_sales_report->get_option();
        $all_sale_reports = $this->get_custom_posts();
        wp_clear_scheduled_hook( 'berocket_get_orders_reports_'.$post_id, array($post_id) );
        $last_report = get_post_meta( $post_id, 'berocket_last_report_time', true );
        if( empty($last_report) ) {
            update_post_meta( $post_id, 'berocket_last_report_time', date('Y-m-d G:i') );
        }
        $send_wait = ( empty($options['send_wait']) ? 0 : ( (int)$options['send_wait'] <= 1 ? 1 : (int)$options['send_wait'] ) );
        $send_hours = ( empty($options['send_time']['hours']) ? 0 : ( (int)$options['send_time']['hours'] <= 0 ? 0 : (int)$options['send_time']['hours'] ) );
        $send_minutes = ( empty($options['send_time']['minutes']) ? 0 : ( (int)$options['send_time']['minutes'] <= 0 ? 0 : (int)$options['send_time']['minutes'] ) );
        $send_hours = sprintf("%02d", $send_hours);
        $send_minutes = sprintf("%02d", $send_minutes);
        $date = date('Y-m-d', strtotime('+'.$send_wait.' days')).' '.$send_hours.':'.$send_minutes;
        $timezone_string = $BeRocket_sales_report->get_wordpress_timezone();
        $time = new DateTime($date, new DateTimeZone($timezone_string));
        $time = $time->getTimestamp();
        update_post_meta( $post_id, 'berocket_report_send_date', $date );
        wp_schedule_single_event($time, 'berocket_get_orders_reports_'.$post_id, array($post_id));
    }
    public function custom_post_order_callback($post_id) {
        $BeRocket_sales_report = BeRocket_sales_report::getInstance();
        $settings = $BeRocket_sales_report->get_option();
        $options = get_post_meta( $post_id, 'br_sale_report', true );
        $date_data_array1 = $this->prepare_date_array_for_post( $post_id, $settings, $options);
        extract($date_data_array1);
        $date_data_array = $this->start_and_end_time_for_post( $post_id, $date_data_array1, $settings, $options );
        extract($date_data_array);

        $periodicity = (empty($options['periodicity']) ? 1 : (int)$options['periodicity']);
        $next_send_datetime = new DateTime($send_date, $timezone_string);
        if( empty($options['periodicity_type']) || $options['periodicity_type'] == 'day' ) {
            $next_send_datetime->modify('+'.$periodicity.'days');
        } else {
            $next_send_datetime->modify('+'.$periodicity.'months');
        }
        $time = $next_send_datetime->getTimestamp();
        wp_schedule_single_event($time, 'berocket_get_orders_reports_'.$post_id, array($post_id));
        
        if( apply_filters('berocket_sales_report_do_not_send', false, $date_data_array1, $options) ) {
            return;
        }
        $emails = apply_filters('berocket_sales_report_send_emails', br_get_value_from_array($settings, 'email', ''), $settings, $options);
        if( empty($emails) ) {
            return;
        }
        if( ! has_term('isdisabled', 'berocket_taxonomy_data', $post_id) ) {
            //DATA GENERATE
            $date_string = br_get_value_from_array($settings, 'subject', '');
            if( empty($date_string) ) {
                $date_string = 'Your reports for WooCommerce ( From: {dtf:Y-m-d} To: {dtt:Y-m-d} )';
            }
            $date_string = apply_filters('berocket_sales_report_send_subject', $date_string, $settings, $options);
            if( preg_match('/{dtf:(.*?)}/', $date_string, $matches) ) {
                $date_string = preg_replace('/{dtf:(.*?)}/', $start_datetime->format($matches[1]), $date_string);
            }
            if( preg_match('/{dtt:(.*?)}/', $date_string, $matches) ) {
                $date_string = preg_replace('/{dtt:(.*?)}/', $end_datetime->format($matches[1]), $date_string);
            }
            $status = array();
            if( ! empty($options['status']) && is_array($options['status']) ) {
                $status = $options['status'];
            }
            global $br_current_notice_post;
            $br_current_notice_post = array(
                'date_data'     => array(
                    'before'    => $end_datetime->format('Y-m-d G:i'),
                    'after'     => $start_datetime->format('Y-m-d G:i'),
                    'compare'   => 'BETWEEN'
                ),
                'date_string'   => $date_string,
                'status'        => $status,
                'options'       => $options,
                'date_data_array'=> $date_data_array,
                'post_id'       => $post_id
            );
            //GENERATE HTML
            $orders = $BeRocket_sales_report->get_order_ids(array('status' => $status), $br_current_notice_post['date_data']); 
            $empty_mail = false;
            $html = $BeRocket_sales_report->get_html_head($date_string);
            if( ! count($orders) ) {
                ob_start ();
                $BeRocket_sales_report->br_get_template_part( 'send_empty' );
                $html .= ob_get_clean();
                $empty_mail = true;
            } else {
                $content = get_post_field('post_content', $post_id);
                $content = do_shortcode($content);
                $html .= $content;
            }
            $html .= $BeRocket_sales_report->get_html_foot();
            //SEND EMAIL
            $emails = explode(',', $emails);
            if( ! $empty_mail || ! empty($options['send_empty']) ) {
                foreach($emails as $email) {
                    $email = trim($email);
                    $BeRocket_sales_report->send_mail($email, $html, $date_string);
                }
            }
        }
        //SET DATA FOR NEXT SEND
        update_post_meta( $post_id, 'berocket_last_report_time', $send_date );
        update_post_meta( $post_id, 'berocket_report_send_date', $next_send_datetime->format('Y-m-d G:i') );
        unset($br_current_notice_post);
    }
    public static function report_start_end_data_date($date, $time_data, $date_type) {
        $date = apply_filters('berocket_report_start_end_data_date', $date, $time_data, $date_type);
        return $date;
    }
    public static function prepare_date_array_for_post($post_id, $settings, $options) {
        $BeRocket_sales_report = BeRocket_sales_report::getInstance();
        $timezone_string = $BeRocket_sales_report->get_wordpress_timezone();
        $timezone_string = new DateTimeZone($timezone_string);
        //LAST SEND DATE/TIME
        $last_date = get_post_meta( $post_id, 'berocket_last_report_time', true );
        $last_datetime = new DateTime($last_date, $timezone_string);
        $last_timestamp = $last_datetime->getTimestamp();
        //SEND DATE/TIME
        $send_date = get_post_meta( $post_id, 'berocket_report_send_date', true );
        $send_datetime = new DateTime($send_date, $timezone_string);
        $send_timestamp = $send_datetime->getTimestamp();
        //CURRENT DATE/TIME
        $current_date = date('Y-m-d G:i');
        $current_datetime = $BeRocket_sales_report->time_to_wordpress($current_date);
        $current_date = $current_datetime->format('Y-m-d G:i');
        $current_timestamp = $current_datetime->getTimestamp();
        //OTHER DATE DATA
        $week_day = $send_datetime->format('w');
        $date_data_array = array(
            'last_datetime' => $last_datetime,
            'send_date' => $send_date,
            'send_datetime' => $send_datetime,
            'send_timestamp' => $send_timestamp,
            'current_datetime' => $current_datetime,
            'current_timestamp' => $current_timestamp,
            'timezone_string' => $timezone_string,
        );
        return $date_data_array;
    }
    public static function start_and_end_time_for_post($post_id, $date_data_array, $settings, $options) {
        extract($date_data_array);
        //START DATE/TIME
        if( empty($options['start_date_type']) ) {
            $options['start_date_type'] = 'prev_send_time';
        }
        $start_date = apply_filters('berocket_sales_report_start_data_date', $last_datetime->format('Y-m-d'), $date_data_array, $options['start_date_type'], $options);
        if( empty($options['start_time']['hours']) ) {
            $options['start_time']['hours'] = 0;
        }
        if( empty($options['start_time']['minutes']) ) {
            $options['start_time']['minutes'] = 0;
        }
        $start_date = $start_date.' '.((int)$options['start_time']['hours']).':'.((int)$options['start_time']['minutes']);
        $start_datetime = new DateTime($start_date, $timezone_string);
        $start_timestamp = $start_datetime->getTimestamp();
        //END DATE/TIME
        if( empty($options['end_date_type']) ) {
            $options['end_date_type'] = 'send_time';
        }
        $end_date = apply_filters('berocket_sales_report_end_data_date', $send_datetime->format('Y-m-d'), $date_data_array, $options['end_date_type'], $options);
        if( empty($options['end_time']['hours']) ) {
            $options['end_time']['hours'] = 0;
        }
        if( empty($options['end_time']['minutes']) ) {
            $options['end_time']['minutes'] = 0;
        }
        $end_date = $end_date.' '.((int)$options['end_time']['hours']).':'.((int)$options['end_time']['minutes']);
        $end_datetime = new DateTime($end_date, $timezone_string);
        $end_timestamp = $end_datetime->getTimestamp();
        $date_data_array['start_date'] = $start_date;
        $date_data_array['start_datetime'] = $start_datetime;
        $date_data_array['end_date'] = $end_date;
        $date_data_array['end_datetime'] = $end_datetime;
        return $date_data_array;
    }
    public function manage_edit_columns ( $columns ) {
        $columns = parent::manage_edit_columns($columns);
        $columns["email"] = __( "EMails", 'sales-report-for-woocommerce' );
        $columns["nextsend"] = __( "Next Send", 'sales-report-for-woocommerce' );
        $columns["period"] = __( "Send Period", 'sales-report-for-woocommerce' );
        return $columns;
    }
    public function columns_replace ( $column ) {
        parent::columns_replace($column);
        global $post;
        $options = $this->get_option($post->ID);
        $BeRocket_sales_report = BeRocket_sales_report::getInstance();
        $settings = $BeRocket_sales_report->get_option();
        switch ( $column ) {
            case "email":
                $emails = apply_filters('berocket_sales_report_send_emails', br_get_value_from_array($settings, 'email', ''), $settings, $options);
                if( empty($emails) ) {
                    echo '<strong style="color:red;">' . __('Please set at least one email', 'sales-report-for-woocommerce') . '</strong>';
                } else {
                    echo $emails;
                }
                break;
            case "nextsend":
                $send_date = get_post_meta( $post->ID, 'berocket_report_send_date', true );
                echo $send_date;
                break;
            case "period":
                $date_data_array = $this->prepare_date_array_for_post($post->ID, $settings, $options);
                $date_data_array = $this->start_and_end_time_for_post($post->ID, $date_data_array, $settings, $options);
                extract($date_data_array);
                $date_diff = date_diff($start_datetime, $end_datetime);
                $display_strong = false;
                if( $date_diff->format('%y') == 0 && $date_diff->format('%m') == 0 && $date_diff->format('%d') == 0 && $date_diff->format('%h') < 3 ) {
                    echo '<strong style="color:red;">' . __('Too short a period of time', 'sales-report-for-woocommerce') . '<br>';
                    $display_strong = true;
                }
                echo __('From: ', 'sales-report-for-woocommerce') . $start_datetime->format('Y-m-d H:i') . __(' To: ', 'sales-report-for-woocommerce') . $end_datetime->format('Y-m-d H:i');
                if( $display_strong ) {
                    echo '</strong>';
                }
                break;
        }
    }
    public function update_version($version) {
        if( empty($version) ) {
            $posts = $this->get_custom_posts();
            $def_status = $this->default_settings['status'];
            foreach($posts as $post_id) {
                $options = $this->get_option($post_id);
                $old_status = $options['status'];
                $options['status'] = array();
                foreach($old_status as $status) {
                    if( in_array('wc-'.$status, $def_status) ) {
                        $options['status'][array_search('wc-'.$status, $def_status)] = 'wc-'.$status;
                    }
                }
                update_post_meta( $post_id, $this->post_name, $options );
            }
        } elseif( version_compare($version, '2.0', '<') ) {
            $posts = $this->get_custom_posts();
            $BeRocket_sales_report = BeRocket_sales_report::getInstance();
            $settings = $BeRocket_sales_report->get_option();
            foreach($posts as $post_id) {
                wp_clear_scheduled_hook( 'berocket_get_orders_reports_'.$post_id, array($post_id) );
                $options = $this->get_option($post_id);
                $date_data_array = $this->prepare_date_array_for_post($post_id, $settings, $options);
                wp_schedule_single_event($date_data_array['send_timestamp'], 'berocket_get_orders_reports_'.$post_id, array($post_id));
            }
        }
        update_option( $this->post_name.'_version', $this->version);
    }
    public  function add_meta_boxes () {
        //add_meta_box( 'copysettingsfromdiv', __( 'Copy settings from', 'BeRocket_domain' ), array( $this, 'copy_settings_from' ), $this->post_name, 'side', 'high' );
        foreach($this->meta_boxes as $meta_box) {
            add_meta_box( $meta_box['slug'], $meta_box['name'], $meta_box['callback'], $this->post_name, $meta_box['position'], $meta_box['priority'] );
        }
    }
}

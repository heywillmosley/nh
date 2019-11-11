<?php
class BeRocket_sales_report_paid extends BeRocket_plugin_variations {
    public $plugin_name = 'sales_report';
    public $version_number = 15;
    function __construct() {
        parent::__construct();
        add_filter('brfr_data_berocket_sales_report_custom_post', array(__CLASS__, 'custom_post_settings'), $this->version_number);
        add_filter( 'berocket_sales_report_start_data_date', array(__CLASS__, 'report_start_data_date'), $this->version_number, 4 );
        add_filter( 'berocket_sales_report_end_data_date', array(__CLASS__, 'report_end_data_date'), $this->version_number, 4 );
        add_filter( 'berocket_sales_report_do_not_send', array(__CLASS__, 'do_not_send'), $this->version_number, 3 );
        add_filter( 'br_sales_report_part_days', array(__CLASS__, 'br_sales_report_part_days'), $this->version_number, 3 );
        add_filter( 'berocket_sales_report_send_emails', array(__CLASS__, 'report_send_emails'), $this->version_number, 3 );
        add_filter( 'berocket_sales_report_send_subject', array(__CLASS__, 'report_send_subject'), $this->version_number, 3 );
        add_action( 'berocket_sales_report_tiny_mce_data', array(__CLASS__, 'tiny_mce_data'), $this->version_number );
        add_filter( 'berocket_custom_post_br_sale_report_default_settings', array(__CLASS__, 'br_sale_report_default_settings'), $this->version_number, 1 );
        add_filter( 'default_content', array(__CLASS__, 'set_post_default_values'), 130, 2 );
    }
    public static function set_post_default_values( $content, $post ) {
        if( $post->post_type == 'br_sale_report' ) {
            $content .= "\r\n" .
            '[br_sales_report_part content="days"]';
        }
        return $content;
    }
    public static function tiny_mce_data() {
        $send_date_types = apply_filters('berocket_sales_report_send_date_types', array());
        echo "
        function berocket_sales_report_tiny_mce_date_generate(data) {
            var text = '';
            if( data.start_date_type && data.end_date_type ) {
                var start_date_type = data.start_date_type;
                var start_day_offset = parseInt(data.start_day_offset);
                var start_time_hours = parseInt(data.start_time_hours);
                var start_time_minutes = parseInt(data.start_time_minutes);
                var end_date_type = data.end_date_type;
                var end_day_offset = parseInt(data.end_day_offset);
                var end_time_hours = parseInt(data.end_time_hours);
                var end_time_minutes = parseInt(data.end_time_minutes);
                if( ! Number.isInteger(start_day_offset) ) {
                    start_day_offset = 0;
                }
                if( ! Number.isInteger(start_time_hours) ) {
                    start_time_hours = 0;
                }
                if( ! Number.isInteger(start_time_minutes) ) {
                    start_time_minutes = 0;
                }
                if( ! Number.isInteger(end_day_offset) ) {
                    end_day_offset = 0;
                }
                if( ! Number.isInteger(end_time_hours) ) {
                    end_time_hours = 0;
                }
                if( ! Number.isInteger(end_time_minutes) ) {
                    end_time_minutes = 0;
                }
                text = ' start_date_type=\"'+start_date_type+'\"';
                text += ' start_day_offset=\"'+start_day_offset+'\"';
                text += ' start_time_hours=\"'+start_time_hours+'\"';
                text += ' start_time_minutes=\"'+start_time_minutes+'\"';
                text += ' end_date_type=\"'+end_date_type+'\"';
                text += ' end_day_offset=\"'+end_day_offset+'\"';
                text += ' end_time_hours=\"'+end_time_hours+'\"';
                text += ' end_time_minutes=\"'+end_time_minutes+'\"';
            }
            return text;
        }
        berocket_sales_report_tiny_mce_data.body[0].values.push({text: 'Table By Days', value: 'days'});";
        echo "
        berocket_sales_report_tiny_mce_header = {
            title: 'Sales Report Content Header',
            body: [],
            onsubmit: function (e) {
                target = '';
                if(e.data.blank === true) {
                    target += 'newtab=\"on\"';
                }
                berocket_tiny_mce_ed.insertContent('[br_sales_report_part content=\"header\"'+berocket_sales_report_tiny_mce_date_generate(e.data)+']');
            }
        };
        ";
        echo "
        berocket_sales_report_tiny_mce_sales = {
            title: 'Sales Report Content Total Sales',
            body: [],
            onsubmit: function (e) {
                target = '';
                if(e.data.blank === true) {
                    target += 'newtab=\"on\"';
                }
                berocket_tiny_mce_ed.insertContent('[br_sales_report_part content=\"sales\"'+berocket_sales_report_tiny_mce_date_generate(e.data)+']');
            }
        };
        ";
        echo "
        berocket_sales_report_tiny_mce_order_count = {
            title: 'Sales Report Content Order Count',
            body: [],
            onsubmit: function (e) {
                target = '';
                if(e.data.blank === true) {
                    target += 'newtab=\"on\"';
                }
                berocket_tiny_mce_ed.insertContent('[br_sales_report_part content=\"order_count\"'+berocket_sales_report_tiny_mce_date_generate(e.data)+']');
            }
        };
        ";
        echo "
        berocket_sales_report_tiny_mce_days = {
            title: 'Sales Report Content Table by Days',
            body: [],
            onsubmit: function (e) {
                target = '';
                if(e.data.blank === true) {
                    target += 'newtab=\"on\"';
                }
                berocket_tiny_mce_ed.insertContent('[br_sales_report_part content=\"days\"'+berocket_sales_report_tiny_mce_date_generate(e.data)+']');
            }
        };
        ";
        echo "
        berocket_sales_report_tiny_mce_products.onsubmit = function (e) {
            target = '';
            if(e.data.blank === true) {
                target += 'newtab=\"on\"';
            }
            berocket_tiny_mce_ed.insertContent('[br_sales_report_part content=\"products\" sort=\"' + e.data.sort + '\"'+berocket_sales_report_tiny_mce_date_generate(e.data)+']');
        };
        ";
        $elements = array('products', 'header', 'sales', 'order_count', 'days');
        foreach($elements as $element) {
                echo "
                berocket_sales_report_tiny_mce_{$element}.body.push({
                    type: 'listbox', 
                    name: 'start_date_type', 
                    label: '".__('Get orders from', 'sales-report-for-woocommerce')."', 
                    'values': [
                    ";
                    $send_date_type_js = array("{text:'".__('From settings(other time settings will not work)', 'sales-report-for-woocommerce')."', value: ''}");
                    foreach($send_date_types as $send_date_type) {
                        $send_date_type_js[] = "{text:'{$send_date_type['text']}', value: '{$send_date_type['value']}'}";
                    }
                    $send_date_type_js = implode(',', $send_date_type_js);
                    echo $send_date_type_js;
                    echo "
                    ]
                });
                berocket_sales_report_tiny_mce_{$element}.body.push({
                    type: 'textbox', 
                    name: 'start_day_offset', 
                    label: '".__('Start day offset', 'sales-report-for-woocommerce')."', 
                    value: '0'
                });
                berocket_sales_report_tiny_mce_{$element}.body.push({
                    type: 'textbox', 
                    name: 'start_time_hours', 
                    label: '".__('Start time hour(s)', 'sales-report-for-woocommerce')."', 
                    value: '0'
                });
                berocket_sales_report_tiny_mce_{$element}.body.push({
                    type: 'textbox', 
                    name: 'start_time_minutes', 
                    label: '".__('Start time minute(s)', 'sales-report-for-woocommerce')."', 
                    value: '0'
                });
                berocket_sales_report_tiny_mce_{$element}.body.push({
                    type: 'listbox', 
                    name: 'end_date_type', 
                    label: '".__('Get orders till', 'sales-report-for-woocommerce')."', 
                    'values': [
                    ";
                    $send_date_type_js = array("{text:'".__('From settings(other time settings will not work)', 'sales-report-for-woocommerce')."', value: ''}");
                    foreach($send_date_types as $send_date_type) {
                        $send_date_type_js[] = "{text:'{$send_date_type['text']}', value: '{$send_date_type['value']}'}";
                    }
                    $send_date_type_js = implode(',', $send_date_type_js);
                    echo $send_date_type_js;
                    echo "
                    ]
                });
                berocket_sales_report_tiny_mce_{$element}.body.push({
                    type: 'textbox', 
                    name: 'end_day_offset', 
                    label: '".__('End day offset', 'sales-report-for-woocommerce')."', 
                    value: '0'
                });
                berocket_sales_report_tiny_mce_{$element}.body.push({
                    type: 'textbox', 
                    name: 'end_time_hours', 
                    label: '".__('End time hour(s)', 'sales-report-for-woocommerce')."', 
                    value: '0'
                });
                berocket_sales_report_tiny_mce_{$element}.body.push({
                    type: 'textbox', 
                    name: 'end_time_minutes', 
                    label: '".__('End time minute(s)', 'sales-report-for-woocommerce')."', 
                    value: '0'
                });
            ";
        }
    }
    public static function br_sale_report_default_settings($default_settings) {
        $default_settings['emails'] = '';
        $default_settings['send_empty'] = '';
        $default_settings['send_day'] = array(
            0   => '1',
            1   => '1',
            2   => '1',
            3   => '1',
            4   => '1',
            5   => '1',
            6   => '1',
        );
        $default_settings['start_day_offset'] = '0';
        $default_settings['end_day_offset'] = '0';
        return $default_settings;
    }
    public static function custom_post_settings($data) {
        $BeRocket_sales_report = BeRocket_sales_report::getInstance();
        $settings = $BeRocket_sales_report->get_option();
        $emails_placeholder = br_get_value_from_array($settings, 'email', '');
        $emails_placeholder = (empty($emails_placeholder) ? 'Global Emails' : 'Global: '.$emails_placeholder);
        $subject_placeholder = br_get_value_from_array($settings, 'subject', '');
        $subject_placeholder = (empty($subject_placeholder) ? 'Global: Your reports for WooCommerce ( From: {dtf:Y-m-d} To: {dtt:Y-m-d} )' : 'Global: '.$subject_placeholder);
        $data['General'] = berocket_insert_to_array(
            $data['General'],
            'status',
            array(
                'send_empty' => array(
                    "label"     => __('Send empty report', 'sales-report-for-woocommerce'),
                    "type"      => "checkbox",
                    "name"      => "send_empty",
                    "value"     => "1",
                    "label_for" => __('Send reports without orders', 'sales-report-for-woocommerce')
                ),
                'subject' => array(
                    "type"     => "text",
                    "label"    => __('Subject', 'sales-report-for-woocommerce'),
                    "label_for"=> "Default: <strong>Your reports for WooCommerce ( From: {dtf:Y-m-d} To: {dtt:Y-m-d} )</strong>",
                    "name"     => "subject",
                    "extra"    => "placeholder='".$subject_placeholder."'",
                    "value"    => '',
                ),
                'emails' => array(
                    "type"     => "text",
                    "label"    => __('Emails', 'sales-report-for-woocommerce'),
                    "label_for"=> __('Use comma to separate emails', 'sales-report-for-woocommerce'),
                    "name"     => "emails",
                    "extra"    => "placeholder='".$emails_placeholder."'",
                    "value"    => '',
                ),
            )
        );
        $data['Send Time'] = berocket_insert_to_array(
            $data['Send Time'],
            'send_time',
            array(
                'days' => array(
                    'label' => __('Days of week', 'sales-report-for-woocommerce'),
                    'tr_class' => 'brsr_periodicity brsr_periodicity_day',
                    'items' => array(
                        'Sunday' => array(
                            "type"     => "checkbox",
                            "label"    => "",
                            "label_for"=> __('Sunday', 'sales-report-for-woocommerce'),
                            "name"     => array("send_day", "0"),
                            "value"    => '1',
                        ),
                        'Monday' => array(
                            "type"     => "checkbox",
                            "label"    => "",
                            "label_for"=> __('Monday', 'sales-report-for-woocommerce'),
                            "name"     => array("send_day", "1"),
                            "value"    => '1',
                        ),
                        'Tuesday' => array(
                            "type"     => "checkbox",
                            "label"    => "",
                            "label_for"=> __('Tuesday', 'sales-report-for-woocommerce'),
                            "name"     => array("send_day", "2"),
                            "value"    => '1',
                        ),
                        'Wednesday' => array(
                            "type"     => "checkbox",
                            "label"    => "",
                            "label_for"=> __('Wednesday', 'sales-report-for-woocommerce'),
                            "name"     => array("send_day", "3"),
                            "value"    => '1',
                        ),
                        'Thursday' => array(
                            "type"     => "checkbox",
                            "label"    => "",
                            "label_for"=> __('Thursday', 'sales-report-for-woocommerce'),
                            "name"     => array("send_day", "4"),
                            "value"    => '1',
                        ),
                        'Friday' => array(
                            "type"     => "checkbox",
                            "label"    => "",
                            "label_for"=> __('Friday', 'sales-report-for-woocommerce'),
                            "name"     => array("send_day", "5"),
                            "value"    => '1',
                        ),
                        'Saturday' => array(
                            "type"     => "checkbox",
                            "label"    => "",
                            "label_for"=> __('Saturday', 'sales-report-for-woocommerce'),
                            "name"     => array("send_day", "6"),
                            "value"    => '1',
                        ),
                    ),
                ),
            )
        );
        $data['Start Time'] = berocket_insert_to_array(
            $data['Start Time'],
            'start_date_type',
            array(
                'start_day_offset' => array(
                    "type"     => "number",
                    "label"    => __('Day offset', 'sales-report-for-woocommerce'),
                    "label_for"=> __('Move the orders diapason by setting offset. You can use negative value', 'sales-report-for-woocommerce'),
                    "name"     => "start_day_offset",
                    "value"    => '0',
                ),
            )
        );
        $data['End Time'] = berocket_insert_to_array(
            $data['End Time'],
            'end_date_type',
            array(
                'end_day_offset' => array(
                    "type"     => "number",
                    "label"    => __('Day offset', 'sales-report-for-woocommerce'),
                    "label_for"=> __('Move the orders diapason by setting offset. You can use negative value', 'sales-report-for-woocommerce'),
                    "name"     => "end_day_offset",
                    "value"    => '0',
                ),
            )
        );
        return $data;
    }
    public static function report_start_data_date($date, $time_data, $date_type, $options) {
        if( ! empty($options['start_day_offset']) ) {
            $options['start_day_offset'] = intval($options['start_day_offset']);
            if( $options['start_day_offset'] ) {
                $date = new DateTime($date.' 00:00', $time_data['timezone_string']);
                $date = $date->modify(($options['start_day_offset'] < 0 ? $options['start_day_offset'] : '+'.$options['start_day_offset']).'days');
                $date = $date->format('Y-m-d');
            }
        }
        return $date;
    }
    public static function report_end_data_date($date, $time_data, $date_type, $options) {
        if( ! empty($options['end_day_offset']) ) {
            $options['end_day_offset'] = intval($options['end_day_offset']);
            if( $options['end_day_offset'] ) {
                $date = new DateTime($date.' 00:00', $time_data['timezone_string']);
                $date = $date->modify(($options['end_day_offset'] < 0 ? $options['end_day_offset'] : '+'.$options['end_day_offset']).'days');
                $date = $date->format('Y-m-d');
            }
        }
        return $date;
    }
    public static function do_not_send($return, $time_data, $options) {
        $week_day = $time_data['send_datetime']->format('w');
        if( empty($options['send_day'][$week_day]) ) {
            return true;
        }
        return $return;
    }
    public static function report_send_emails($emails, $settings, $options) {
        if( ! empty($options['emails']) ) {
            $emails = $options['emails'];
        }
        return $emails;
    }
    public static function report_send_subject($subject, $settings, $options) {
        if( ! empty($options['subject']) ) {
            $subject = $options['subject'];
        }
        return $subject;
    }
    public static function br_sales_report_part_days($html, $br_current_notice_post, $atts) {
        $end_timestamp = $br_current_notice_post['date_data_array']['end_datetime']->getTimestamp();
        $dto_days = new DateTime($br_current_notice_post['date_data_array']['start_date'], $br_current_notice_post['date_data_array']['timezone_string']);
        $dto_days_timestamp = $dto_days->getTimestamp();
        $data_days = array();
        $date_diff = date_diff($br_current_notice_post['date_data_array']['start_datetime'], $br_current_notice_post['date_data_array']['end_datetime']);
        $diff = 'days';
        if( $date_diff->format('%m') > 1 || ($date_diff->format('%m') > 0 && $date_diff->format('%d') > 2) ) {
            $diff = 'week';
        }
        if( $date_diff->format('%y') > 0 || $date_diff->format('%m') > 6 ) {
            $diff = 'month';
        }
        while( $dto_days_timestamp <= $end_timestamp ) {
            $test_time = new DateTime($dto_days->format('Y-m-d G:i'), $br_current_notice_post['date_data_array']['timezone_string']);
            $test_time->modify('-1 second');
            $data_day = array(
                'after' => $test_time->format('Y-m-d G:i'),
                'string'=> '',
            );
            if( $diff == 'days' ) {
                $data_day['string'] = $dto_days->format('d');
            }
            if( $diff == 'week' || $diff == 'month' ) {
                $data_day['string'] = $dto_days->format('d/m');
            }
            $dto_days->modify('+1 '.$diff);
            $dto_days_timestamp = $dto_days->getTimestamp();
            if( $dto_days_timestamp >= $end_timestamp ) {
                $dto_days = $br_current_notice_post['date_data_array']['end_datetime'];
            }
            $data_day['before'] = $dto_days->format('Y-m-d G:i');
            if( $diff == 'week' || $diff == 'month' ) {
                $data_day['string'] .= ' - '.$dto_days->format('d/m');
            }
            $data_days[] = $data_day;
        }
        $html_data = array('status' => $br_current_notice_post['status']);
        $html = self::get_week_days_html($data_days , $html_data);
        return $html;
    }
    public static function get_week_days_html($week_days, $html_data = array()) {
        $BeRocket_sales_report = BeRocket_sales_report::getInstance();
        $options = $BeRocket_sales_report->get_option();
        if( ! empty($html_data['status']) ) {
            $status = $html_data['status'];
        } else {
            if( empty($options['status']) ) {
                $status = array(
                    'pending','processing','on-hold','completed','cancelled','refunded','failed',
                    'wc-pending','wc-processing','wc-on-hold','wc-completed','wc-cancelled','wc-refunded','wc-failed'
                );
            } else {
                $status = array('completed', 'wc-completed');
                if( in_array($options['status'], array('processing', 'pending')) ) {
                    $status[] = 'processing';
                    $status[] = 'wc-processing';
                }
                if( in_array($options['status'], array('pending')) ) {
                    $status[] = 'pending';
                    $status[] = 'wc-pending';
                }
            }
        }
        $week_days_info = array();
        $max_price = 0;
        foreach($week_days as $week_day) {
            $week_day_info = array();
            $week_day_info['string'] = $week_day['string'];
            unset($week_day['string']);
            $args = array(
                'date_query' => $week_day,
                'post_type' => 'shop_order',
                'post_status' => $status,
                'posts_per_page' => '-1'
            );
            $query = new WP_Query( $args );
            $orders = $query->posts;
            $total_price = 0;
            $order_count = 0;
            foreach($orders as $order) {
                $order_count++;
                $wc_order = new WC_Order($order->ID);
                $total_price += $wc_order->get_total();
            }
            $week_day_info['order_count'] = $order_count;
            $week_day_info['total_price'] = $total_price;
            if ( $total_price > $max_price ) {
                $max_price = $total_price;
            }
            $week_days_info[] = $week_day_info;
        }
        if( $max_price > 0 ) {
            set_query_var( 'week_days', $week_days_info );
            set_query_var( 'max_price', $max_price );
            ob_start ();
            $BeRocket_sales_report->br_get_template_part( 'week_day' );
            $html = ob_get_clean ();
            return $html;
        } else {
            return '';
        }
    }
}
new BeRocket_sales_report_paid;

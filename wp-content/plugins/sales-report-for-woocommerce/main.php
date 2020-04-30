<?php
define( "BeRocket_sales_report_domain", 'sales-report-for-woocommerce'); 
define( "sales_report_TEMPLATE_PATH", plugin_dir_path( __FILE__ ) . "templates/" );
load_plugin_textdomain('sales-report-for-woocommerce', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/');
require_once(plugin_dir_path( __FILE__ ).'berocket/framework.php');
foreach (glob(__DIR__ . "/includes/*.php") as $filename)
{
    include_once($filename);
}
include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

class BeRocket_sales_report extends BeRocket_Framework {
    public static $settings_name = 'br-sales_report-options';
    public $info, $defaults, $values, $notice_array, $conditions;
    protected static $instance;
    function __construct () {
        $this->info = array(
            'id'          => 17,
            'lic_id'      => 33,
            'version'     => BeRocket_sales_report_version,
            'plugin'      => '',
            'slug'        => '',
            'key'         => '',
            'name'        => '',
            'plugin_name' => 'sales_report',
            'full_name'   => 'Sales Report for WooCommerce',
            'norm_name'   => 'Sales Report',
            'price'       => '',
            'domain'      => 'sales-report-for-woocommerce',
            'templates'   => sales_report_TEMPLATE_PATH,
            'plugin_file' => BeRocket_sales_report_file,
            'plugin_dir'  => __DIR__,
        );
        $this->defaults = array(
            'time'              => '03:00',
            'wptime'            => '',
            'email'             => '',
            'interval'          => array(),
            'status'            => '',
            'daily_date'        => '',
            'send_empty'        => '',
            'starttime'         => '',
            'sort_product'      => '',
            'custom_css'        => '',
            'plugin_key'        => '',
        );
        $this->values = array(
            'settings_name' => 'br-sales_report-options',
            'option_page'   => 'br-sales_report',
            'premium_slug'  => 'woocommerce-sales-report-email',
            'free_slug'     => 'sales-report-for-woocommerce',
        );
        $this->feature_list = array();
        if( method_exists($this, 'include_once_files') ) {
            $this->include_once_files();
        }
        if ( $this->init_validation() ) {
            new BeRocket_sales_report_custom_post();
        }
        parent::__construct( $this );

        if ( $this->init_validation() ) {
            add_filter ( 'BeRocket_updater_menu_order_custom_post', array($this, 'menu_order_custom_post') );
            add_shortcode( 'br_sales_report_part', array( $this, 'shortcode' ) );
            add_filter( 'berocket_sales_report_send_date_types', array($this, 'report_send_date_types'), 1 );
            add_filter( 'berocket_report_start_end_data_date', array( $this, 'report_start_end_data_date'), 10, 3 );
        }
    }
    function new_version_changes() {
        $version = get_option('br-sales_report-version');
        if( empty($version) ) {
            wp_clear_scheduled_hook( 'berocket_get_orders' );
            $options = $this->get_option();
            $BeRocket_sales_report_custom_post = BeRocket_sales_report_custom_post::getInstance();
            if( empty($options['time']) ) {
                $send_time = array('0', '0');
            } else {
                $send_time = explode(':', $options['time']);
            }
            if( empty($options['starttime']) ) {
                $start_end_time = array('0', '0');
            } else {
                $start_end_time = $send_time;
            }
            $week_send_wait = 8 - date('N');
            $month_send_wait = (date('t') - date('j')) + 1;
            if( empty($options['status']) ) {
                $status_set = array(
                    '0' => 'pending',
                    '1' => 'processing',
                    '2' => 'on-hold',
                    '3' => 'completed',
                    '4' => 'cancelled',
                    '5' => 'refunded',
                    '6' => 'failed'
                );
            } else {
                $status_set = array();
                switch($options['status']) {
                    case 'pending':
                        $status_set['0'] = 'pending';
                    case 'processing':
                        $status_set['1'] = 'processing';
                    case 'completed':
                        $status_set['2'] = 'completed';
                }
            }
            if( isset($options['emails']) && is_array($options['emails']) && count(isset($options['emails'])) > 0 ) {
                foreach($options['emails'] as $email) {
                    if( ! empty($email['email']) && isset($email['interval']) && is_array($email['interval']) ) {
                        if( in_array('day', $email['interval'])
                        && isset($email['day']['blocks']) && is_array($email['day']['blocks']) && count($email['day']['blocks']) > 0 ) {
                            $send_options = array(
                                'send_time'     => array('hours' => $send_time[0], 'minutes' => $send_time[1]),
                                'start_time'    => array('hours' => $start_end_time[0], 'minutes' => $start_end_time[1]),
                                'end_time'      => array('hours' => $start_end_time[0], 'minutes' => $start_end_time[1]),
                                'emails'        => $email['email'],
                                'status'        => $status_set,
                                'send_empty'    => @$options['send_empty'],
                            );
                            $content = '[br_sales_report_part content="header"]';
                            foreach($email['day']['blocks'] as $block) {
                                $content .= '[br_sales_report_part content="'.$block.'"]';
                            }
                            $BeRocket_sales_report_custom_post->create_new_post(array(
                                'post_title'    => 'Daily for: '.$email['email'],
                                'post_content'  => $content
                            ), $send_options);
                        }
                        if( in_array('week', $email['interval'])
                        && isset($email['week']['blocks']) && is_array($email['week']['blocks']) && count($email['week']['blocks']) > 0 ) {
                            $send_options = array(
                                'send_time'         => array('hours' => $send_time[0], 'minutes' => $send_time[1]),
                                'start_time'        => array('hours' => $start_end_time[0], 'minutes' => $start_end_time[1]),
                                'end_time'          => array('hours' => $start_end_time[0], 'minutes' => $start_end_time[1]),
                                'emails'            => $email['email'],
                                'send_wait'         => $week_send_wait,
                                'periodicity'       => 7,
                                'start_date_type'   => 'prev_week',
                                'end_date_type'     => 'week',
                                'status'            => $status_set,
                                'send_empty'        => @$options['send_empty'],
                            );
                            $content = '[br_sales_report_part content="header"]';
                            foreach($email['week']['blocks'] as $block) {
                                $content .= '[br_sales_report_part content="'.$block.'"]';
                            }
                            $BeRocket_sales_report_custom_post->create_new_post(array(
                                'post_title'    => 'Weekly for: '.$email['email'],
                                'post_content'  => $content
                            ), $send_options);
                        }
                        if( in_array('week', $email['interval'])
                        && isset($email['month']['blocks']) && is_array($email['month']['blocks']) && count($email['month']['blocks']) > 0 ) {
                            $send_options = array(
                                'send_time'         => array('hours' => $send_time[0], 'minutes' => $send_time[1]),
                                'start_time'        => array('hours' => $start_end_time[0], 'minutes' => $start_end_time[1]),
                                'end_time'          => array('hours' => $start_end_time[0], 'minutes' => $start_end_time[1]),
                                'emails'            => $email['email'],
                                'send_wait'         => $month_send_wait,
                                'periodicity_type'  => 'month',
                                'periodicity'       => 1,
                                'start_date_type'   => 'prev_month',
                                'end_date_type'     => 'month',
                                'status'            => $status_set,
                                'send_empty'        => @$options['send_empty'],
                            );
                            $content = '[br_sales_report_part content="header"]';
                            foreach($email['month']['blocks'] as $block) {
                                $content .= '[br_sales_report_part content="'.$block.'"]';
                            }
                            $BeRocket_sales_report_custom_post->create_new_post(array(
                                'post_title'    => 'Monthly for: '.$email['email'],
                                'post_content'  => $content
                            ), $send_options);
                        }
                    }
                }
            }
            update_option('br-sales_report-version', $this->info['version']);
        } elseif( ( version_compare($version, '3.0.3', '<') && version_compare($version, '3.0', '>') ) || version_compare($version, '1.1.7', '<') ) {
            $cron = get_option('cron');
            if( is_array($cron) ) {
                $events = array();
                foreach($cron as $cron_time => &$cron_jobs) {
                    if( is_array($cron_jobs) ) {
                        foreach($cron_jobs as $cron_job => $cron_data) {
                            if( strpos($cron_job, 'berocket_get_orders_reports_') !== FALSE ) {
                                $id = str_replace('berocket_get_orders_reports_', '', $cron_job);
                                $id = (int)$id;
                                wp_clear_scheduled_hook($cron_job, array($id));
                            }
                        }
                    }
                }
            }
            update_option('br-sales_report-version', $this->info['version']);
        }
    }
    function init_validation() {
        return ( ( is_plugin_active( 'woocommerce/woocommerce.php' ) || is_plugin_active_for_network( 'woocommerce/woocommerce.php' ) ) && 
            br_get_woocommerce_version() >= 2.1 );
    }
    public function init () {
        parent::init();
        $version = get_option('br-sales_report-version');
        if( $version != $this->info['version'] ) {
            $this->new_version_changes();
        }
    }
    public function shortcode($atts = array()) {
        $default_atts = array(
            'content' => 'sales',
            'extend'  => ''
        );
        $html = '';
        global $br_current_notice_post;
        if( ! empty($br_current_notice_post) && ! empty($br_current_notice_post['date_data']) && ! empty($br_current_notice_post['date_string']) ) {
            $br_current_notice_post_copy = $br_current_notice_post;
            $atts = array_merge($default_atts, $atts);
            if( ! empty($atts['start_date_type']) && ! empty($atts['end_date_type']) ) {
                $post_id = $br_current_notice_post['post_id'];
                $settings = $this->get_option();
                $options = get_post_meta( $post_id, 'br_sale_report', true );
                $options['start_date_type'] = $atts['start_date_type'];
                $options['end_date_type'] = $atts['end_date_type'];
                if( isset($atts['start_day_offset']) ) {
                    $options['start_day_offset'] = $atts['start_day_offset'];
                }
                if( isset($atts['start_time_hours']) ) {
                    $options['start_time']['hours'] = $atts['start_time_hours'];
                }
                if( isset($atts['start_time_minutes']) ) {
                    $options['start_time']['minutes'] = $atts['start_time_minutes'];
                }
                if( isset($atts['end_day_offset']) ) {
                    $options['end_day_offset'] = $atts['end_day_offset'];
                }
                if( isset($atts['end_time_hours']) ) {
                    $options['end_time']['hours'] = $atts['end_time_hours'];
                }
                if( isset($atts['end_time_minutes']) ) {
                    $options['end_time']['minutes'] = $atts['end_time_minutes'];
                }
                
                $date_data_array = BeRocket_sales_report_custom_post::prepare_date_array_for_post( $post_id, $settings, $options);
                $date_data_array = BeRocket_sales_report_custom_post::start_and_end_time_for_post( $post_id, $date_data_array, $settings, $options );
                extract($date_data_array);
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
                $date_data = array(
                    'before'    => $end_datetime->format('Y-m-d G:i'),
                    'after'     => $start_datetime->format('Y-m-d G:i'),
                    'compare'   => 'BETWEEN'
                );
                $br_current_notice_post_copy['date_data'] = $date_data;
                $br_current_notice_post_copy['date_string'] = $date_string;
                $br_current_notice_post_copy['date_data_array'] = $date_data_array;
            }
            if( in_array($atts['content'], array('sales', 'order_count', 'products', 'header')) ) {
                $date_data = $br_current_notice_post_copy['date_data'];
                $date_string = $br_current_notice_post_copy['date_string'];
                if( $atts['content'] == 'header' ) {
                    $html_data = array( 'blocks' => array('show_header') );
                } else {
                    $html_data = array( 'blocks' => array('hide_header', $atts['content'] ) );
                }
                $html_data['status'] = $br_current_notice_post['status'];
                $html_data['extend']  = explode( ',', $atts['extend'] );
                if( ! empty($atts['sort']) ) {
                    $is_asc = ( $atts['sort'] == 'name_asc' || $atts['sort'] == 'qty_asc' );
                    $is_name = ( $atts['sort'] == 'name_asc' || $atts['sort'] == 'name_desc' );
                    $sort_array = array();
                    foreach($ready_products as $product_id => $product_info) {
                        if( $is_name ) {
                            $sort_array[$product_id] = $product_info['name'];
                        } else {
                            $sort_array[$product_id] = $product_info['quantity'];
                        }
                    }
                    $html_data['sort_product'] = array('is_asc' => $is_asc, 'is_name' => $is_name);
                }
                $html = $this->get_html_order($html_data, $date_data, $date_string);
            } else {
                $html = apply_filters('br_sales_report_part_'.$atts['content'], '', $br_current_notice_post_copy, $atts);
            }
        }
        return $html;
    }
    public function admin_settings( $tabs_info = array(), $data = array() ) {
        $time_array = array();
        for($i = 0; $i < 24; $i++) {
            for($j = 0; $j < 12; $j++) {
                $i_text = sprintf("%02d", $i);
                $j_text = sprintf("%02d", ($j * 5));
                $time_array[] = array('value' => $i_text . ':' . $j_text, 'text' => $i_text . ':' . $j_text);
            }
        }
        parent::admin_settings(
            array(
                'General' => array(
                    'icon' => 'cog',
                ),
                'Report Variant' => array(
                    'icon' => 'plus-square',
                    'link' => admin_url( 'edit.php?post_type=br_sale_report' ),
                ),
                'Custom CSS' => array(
                    'icon' => 'css3'
                ),
                'License' => array(
                    'icon' => 'unlock-alt',
                    'link' => admin_url( 'admin.php?page=berocket_account' )
                ),
            ),
            array(
            'General' => array(
                /*'emails_settings' => array(
                    'section'   => 'emails_settings',
                    "value"     => "1",
                ),
                'status' => array(
                    "label"     => __( "Status", 'sales-report-for-woocommerce' ),
                    "name"     => 'status',   
                    "type"     => "selectbox",
                    "options"  => array(
                        array('value' => '', 'text' => __('All', 'sales-report-for-woocommerce')),
                        array('value' => 'pending', 'text' => __('Pending, Processing, Completed', 'sales-report-for-woocommerce')),
                        array('value' => 'processing', 'text' => __('Processing, Completed', 'sales-report-for-woocommerce')),
                        array('value' => 'completed', 'text' => __('Completed', 'sales-report-for-woocommerce')),
                    ),
                    "value"    => '',
                ),
                'daily_date' => array(
                    "label"     => __( "Daily message date", 'sales-report-for-woocommerce' ),
                    "name"     => 'daily_date',   
                    "type"     => "selectbox",
                    "options"  => array(
                        array('value' => '', 'text' => __('Statistic start day', 'sales-report-for-woocommerce')),
                        array('value' => 'send', 'text' => __('Send day', 'sales-report-for-woocommerce')),
                        array('value' => 'fromto', 'text' => __('From start to send day', 'sales-report-for-woocommerce')),
                        array('value' => 'fromto_time', 'text' => __('From start to send day with time', 'sales-report-for-woocommerce')),
                    ),
                    "value"    => '',
                    "label_for"=> __('Date that will be displayed in message for daily report', 'sales-report-for-woocommerce'),
                ),
                'time' => array(
                    "label"     => __( "Time", 'sales-report-for-woocommerce' ),
                    "name"     => 'time',   
                    "type"     => "selectbox",
                    "options"  => $time_array,
                    "value"    => '',
                ),
                'starttime' => array(
                    "label"     => __('Start time', 'sales-report-for-woocommerce'),
                    "type"      => "checkbox",
                    "name"      => "starttime",
                    "value"     => "1",
                    "label_for" => __('Use time as start and end of the day', 'sales-report-for-woocommerce')
                ),
                'wptime' => array(
                    "label"     => __('WordPress Time', 'sales-report-for-woocommerce'),
                    "type"      => "checkbox",
                    "name"      => "wptime",
                    "value"     => "1",
                    "class"     => "br_use_wptime",
                    "label_for" => __('Use WordPress time instead UTC', 'sales-report-for-woocommerce')
                ),
                'current_times' => array(
                    'section'   => 'current_times',
                    "value"     => "1",
                ),
                'send_empty' => array(
                    "label"     => __('Send empty report', 'sales-report-for-woocommerce'),
                    "type"      => "checkbox",
                    "name"      => "send_empty",
                    "value"     => "1",
                    "label_for" => __('Send reports without orders', 'sales-report-for-woocommerce')
                ),*/
                'subject' => array(
                    "type"     => "text",
                    "label"    => __('Subject', 'sales-report-for-woocommerce'),
                    "label_for"=> "Default: <strong>Your reports for WooCommerce ( From: {dtf:Y-m-d} To: {dtt:Y-m-d} )</strong>",
                    "name"     => "subject",
                    "extra"    => 'placeholder="Your reports for WooCommerce ( From: {dtf:Y-m-d} To: {dtt:Y-m-d} )"',
                    "value"    => '',
                ),
                'emails' => array(
                    "type"     => "text",
                    "label"    => __('Emails', 'sales-report-for-woocommerce'),
                    "label_for"=> __('Use comma to separate emails', 'sales-report-for-woocommerce'),
                    "name"     => "email",
                    "value"    => '',
                ),
            ),
            'Custom CSS' => array(
                array(
                    "label"   => "Custom CSS",
                    "name"    => "custom_css",
                    "type"    => "textarea",
                    "value"   => "",
                ),
            ),
        ) );
    }
    public function get_wordpress_timezone() {
        $timezone_string = get_option('timezone_string');
        if( empty($timezone_string) ) {
            $gmt_offset = get_option('gmt_offset');
            if( empty($gmt_offset) ) {
                $timezone_string = 'UTC';
            } else {
                $timezone_string = sprintf("%+03d",$gmt_offset).($gmt_offset != intval($gmt_offset) ? '30' : '00');
            }
        }
        return $timezone_string;
    }
    public function time_to_wordpress($time_string) {
        $timezone_string = $this->get_wordpress_timezone();
        $time = new DateTime($time_string, new DateTimeZone('UTC'));
        $time->setTimeZone(new DateTimeZone($timezone_string));
        return $time;
    }
    public function time_to_php($time_string) {
        $timezone_string = $this->get_wordpress_timezone();
        $time = new DateTime($time_string, new DateTimeZone($timezone_string));
        $time->setTimeZone(new DateTimeZone('UTC'));
        return $time;
    }
    public function get_correct_time($time_string, $wptime) {
        if( empty($wptime) ) {
            $time = new DateTime($time_string);
        } else {
            $timezone_string = $this->get_wordpress_timezone();
            $time = new DateTime($time_string, new DateTimeZone($timezone_string));
            $time->setTimeZone(new DateTimeZone('UTC'));
        }
        return $time;
    }
    public function get_html_head($date_string) {
        set_query_var( 'date_string', $date_string );
        ob_start ();
        $this->br_get_template_part( 'email_head' );
        $html = ob_get_clean ();
        return $html;
    }
    public function get_html_foot() {
        ob_start ();
        $this->br_get_template_part( 'email_foot' );
        $html = ob_get_clean ();
        return $html;
    }
    public function get_order_ids($html_data, $date) {
        $options = $this->get_option();
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
        $args = apply_filters('berocket_sales_report_get_order_args', array(
            'date_query' => $date,
            'post_type' => 'shop_order',
            'post_status' =>  $status,
            'posts_per_page' => '-1'
        ), $html_data, $date);
        $query = new WP_Query( $args );
        $orders = $query->posts;
        return apply_filters('berocket_sales_report_get_order_ids', $orders, $args, $html_data, $date);
    }
    public function get_html_order($html_data, $date, $date_string) {
        $options = $this->get_option();
        $orders = $this->get_order_ids($html_data, $date);
        $ready_products = array();
        $total_price = 0;
        $order_count = 0;
        foreach($orders as $order) {
            $order_count++;
            $wc_order = new WC_Order($order->ID);
            $total_price += $wc_order->get_total();
            $products = $wc_order->get_items();
            foreach($products as $product) {
                $product_id = (empty($product['variation_id']) ? $product['product_id'] : $product['variation_id']);
                if( isset($ready_products[$product_id]) ) {
                    $ready_products[$product_id]['quantity'] += $product['qty'];
                } else {
                    $ready_products[$product_id] = array('name' => $product['name'], 'quantity' => $product['qty']);
                    if ( is_array( $html_data['extend'] ) and in_array( 'sku', $html_data['extend'] ) or $html_data['extend'] == 'sku' ) {
                        $ready_products[$product_id]['sku'] = get_post_meta( $product_id, '_sku', true );
                    }
                }
            }
        }
        if( ! empty($html_data['sort_product']) ) {
            $sort_array = array();
            foreach($ready_products as $ready_product) {
                if( $html_data['sort_product']['is_name'] ) {
                    $sort_array[] = $ready_product['name'];
                } else {
                    $sort_array[] = $ready_product['quantity'];
                }
            }
            array_multisort($sort_array, ($html_data['sort_product']['is_asc'] ? SORT_ASC : SORT_DESC), ($html_data['sort_product']['is_name'] ? SORT_REGULAR : SORT_NUMERIC), $ready_products);
        }
        if( $order_count > 0 && count($ready_products) > 0 ) {
            $ready_products = apply_filters('berocket_sales_report_ready_products', $ready_products, $orders, $html_data, $date, $date_string);
            set_query_var( 'total_price', $total_price );
            set_query_var( 'order_count', $order_count );
            set_query_var( 'ready_products', $ready_products );
            set_query_var( 'date_string', $date_string );
            set_query_var( 'html_data', $html_data );
            ob_start ();
            $this->br_get_template_part( 'email' );
            $html = ob_get_clean ();
            return $html;
        } else {
            return FALSE;
        }
    }
    public function send_mail($email, $html, $date_string) {
        $options = $this->get_option();
        $header = $date_string;
        $wp_mail_headers = array('Content-Type: text/html; charset=UTF-8;');
        if( ! empty($email) ) {
            if( $html !== FALSE ) {
                add_filter('wp_mail_content_type', array( $this, 'wp_mail_content_type' ) );
                wp_mail ($email, $header, $html, $wp_mail_headers);
            } elseif( ! empty($options['send_empty']) ) {
                add_filter('wp_mail_content_type', array( $this, 'wp_mail_content_type' ) );
                $html = $this->get_html_head($date_string);
                ob_start ();
                $this->br_get_template_part( 'send_empty' );
                $html .= ob_get_clean ();
                $html .= $this->get_html_foot();
                wp_mail ($email, $header, $html, $wp_mail_headers);
            }
        }
    }
    public function wp_mail_content_type($content_type) {
        return 'text/html';
    }
    public function admin_init () {
        parent::admin_init();
        global $pagenow, $typenow, $post;
        if ( in_array($pagenow, array('post.php', 'post-new.php')) && ("br_sale_report" == $typenow || ( ! empty($_GET['post']) && "br_sale_report" == get_post_type($_GET['post']) ) ) ) {
            add_filter( 'mce_buttons', array($this, 'register_tinymce_button') );
            add_filter( 'mce_external_plugins', array($this, 'add_tinymce_button') );
            wp_register_script(
                'sales_report_gutenberg',
                plugins_url( 'js/gutenberg.js', __FILE__ ),
                array( 'wp-blocks', 'wp-element', 'wp-editor' )
            );
            add_filter( 'allowed_block_types', array($this, 'allowed_block_types') );

            register_block_type( 'berocket/sales-report-header-gutenberg', array(
                'editor_script' => 'sales_report_gutenberg',
            ) );

            register_block_type( 'berocket/sales-report-sales-gutenberg', array(
                'editor_script' => 'sales_report_gutenberg',
            ) );

            register_block_type( 'berocket/sales-report-order-count-gutenberg', array(
                'editor_script' => 'sales_report_gutenberg',
            ) );

            register_block_type( 'berocket/sales-report-days-gutenberg', array(
                'editor_script' => 'sales_report_gutenberg',
            ) );

            register_block_type( 'berocket/sales-report-products-list-gutenberg', array(
                'editor_script' => 'sales_report_gutenberg',
            ) );
        }
        wp_enqueue_script( 'berocket_sales_report_admin', plugins_url( 'js/admin.js', __FILE__ ), array( 'jquery' ), BeRocket_sales_report_version );
        wp_register_style( 'berocket_sales_report_admin_style', plugins_url( 'css/admin.css', __FILE__ ), "", BeRocket_sales_report_version );
        wp_enqueue_style( 'berocket_sales_report_admin_style' );
    }
    function allowed_block_types( $allowed_blocks ) {
        return array(
            'core/image',
            'core/paragraph',
            'core/heading',
            'core/list',
            'core/freeform',
            'berocket/sales-report-header-gutenberg',
            'berocket/sales-report-sales-gutenberg',
            'berocket/sales-report-order-count-gutenberg',
            'berocket/sales-report-days-gutenberg',
            'berocket/sales-report-products-list-gutenberg'
        );
     
    }
    public function register_tinymce_button( $buttons ) {
        array_push( $buttons, "berocket_sale_report" );
        return $buttons;
    }
    public function add_tinymce_button( $plugin_array ) {
        $plugin_array['berocket_sale_report'] = plugins_url( 'js/tiny_mce.js', __FILE__ ) ;
        return $plugin_array;
    }
    public function admin_menu() {
        if ( parent::admin_menu() ) {
            add_submenu_page(
                'woocommerce',
                __( $this->info[ 'norm_name' ]. ' Settings', $this->info[ 'domain' ] ),
                __( $this->info[ 'norm_name' ], $this->info[ 'domain' ] ),
                'manage_options',
                $this->values[ 'option_page' ],
                array(
                    $this,
                    'option_form'
                )
            );
        }
    }
    public function menu_order_custom_post($compatibility) {
        $compatibility['br_sale_report'] = 'br-sales_report';
        return $compatibility;
    }
    public function report_send_date_types($send_types = array()) {
        $send_types[] = array('value' => 'prev_send_time', 'text' => __('Previous send day', 'sales-report-for-woocommerce'));
        $send_types[] = array('value' => 'send_time', 'text' => __('Send day', 'sales-report-for-woocommerce'));
        $send_types[] = array('value' => 'month', 'text' => __('First day of the month', 'sales-report-for-woocommerce'));
        $send_types[] = array('value' => 'prev_month', 'text' => __('First day of the previous month', 'sales-report-for-woocommerce'));
        $send_types[] = array('value' => 'week', 'text' => __('First day of the week', 'sales-report-for-woocommerce'));
        $send_types[] = array('value' => 'prev_week', 'text' => __('First day of the previous week', 'sales-report-for-woocommerce'));
        $send_types[] = array('value' => 'year', 'text' => __('First day of the year', 'sales-report-for-woocommerce'));
        return $send_types;
    }
    public function report_start_end_data_date($date, $time_data, $date_type) {
        switch($date_type) {
            case 'prev_send_time':
                $date = $time_data['last_datetime']->format('Y-m-d');
                break;
            case 'send_time':
                $date = $time_data['send_datetime']->format('Y-m-d');
                break;
            case 'month':
                $date = $time_data['current_datetime']->format('Y-m-1');
                break;
            case 'prev_month':
                $date = new DateTime('first day of previous month', $time_data['timezone_string']);
                $date = $date->format('Y-m-1');
                break;
            case 'week':
                $date = new DateTime('monday this week', $time_data['timezone_string']);
                $date = $date->format('Y-m-d');
                break;
            case 'prev_week':
                $date = new DateTime('monday previous week', $time_data['timezone_string']);
                $date = $date->format('Y-m-d');
                break;
            case 'year':
                $date = $time_data['send_datetime']->format('Y-1-1');
                break;
        }
        return $date;
    }
}

new BeRocket_sales_report;

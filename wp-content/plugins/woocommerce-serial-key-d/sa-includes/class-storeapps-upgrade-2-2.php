<?php
class StoreApps_Upgrade_2_2 {

    var $base_name;
    var $check_update_timeout;
    var $last_checked;
    var $plugin_data;
    var $sku;
    var $license_key;
    var $download_url;
    var $installed_version;
    var $live_version;
    var $changelog;
    var $slug;
    var $name;
    var $documentation_link;
    var $prefix;
    var $text_domain;
    var $login_link;
    var $due_date;
    var $plugin_file;
    var $upgrade_notices;
    var $client_id;
    var $client_secret;

    function __construct( $file, $sku, $prefix, $plugin_name, $text_domain, $documentation_link ) {

        $this->check_update_timeout = (24 * 60 * 60); // 24 hours

        $this->plugin_file = $file;
        $this->base_name = plugin_basename( $file );
        $this->slug = dirname( $this->base_name );
        $this->name = $plugin_name;
        $this->sku = $sku;
        $this->documentation_link = $documentation_link;
        $this->prefix = $prefix;
        $this->text_domain = $text_domain;
        $this->client_id = '62Ny4ZYX172feJR57A3Z3bDMBJ1m63';
        $this->client_secret = 'Fd5sLarK8tSaI7UAc1af1erE02o2pu';

        add_action( 'admin_init', array( $this, 'initialize_plugin_data' ) );

        add_action( 'admin_footer', array( $this, 'add_plugin_style_script' ) );
        add_action( 'admin_footer', array( $this, 'add_support_ticket_content' ) );
        add_action( 'wp_ajax_'.$this->prefix.'_get_authorization_code', array( $this, 'get_authorization_code' ) );
        add_action( 'wp_ajax_'.$this->prefix.'_disconnect_storeapps', array( $this, 'disconnect_storeapps' ) );

        if ( has_action( 'wp_ajax_get_storeapps_updates', array( $this, 'get_storeapps_updates' ) ) === false ) {
            add_action( 'wp_ajax_get_storeapps_updates', array( $this, 'get_storeapps_updates' ) );
        }
        if ( has_action( 'wp_ajax_nopriv_storeapps_updates_available', array( $this, 'storeapps_updates_available' ) ) === false ) {
            add_action( 'wp_ajax_nopriv_storeapps_updates_available', array( $this, 'storeapps_updates_available' ) );
        }

        add_filter( 'all_plugins', array( $this, 'overwrite_wp_plugin_data_for_plugin' ) );
        add_filter( 'plugins_api', array( $this, 'overwrite_wp_plugin_api_for_plugin' ), 10, 3 );
        add_filter( 'site_transient_update_plugins', array( $this, 'overwrite_site_transient' ), 10, 3 );
        add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'overwrite_site_transient' ), 10, 3 );

        add_filter( 'plugin_action_links_' . plugin_basename( $file ), array( $this, 'plugin_action_links' ), 10, 4 );
        add_filter( 'plugin_row_meta', array( $this, 'add_support_link' ), 10, 4 );

        add_filter( 'storeapps_upgrade_create_link', array( $this, 'storeapps_upgrade_create_link' ), 10, 4 );

        add_action( 'admin_notices', array( $this, 'show_notifications' ) );
        add_action( 'wp_ajax_'.$this->prefix.'_hide_renewal_notification', array( $this, 'hide_renewal_notification' ) );
        add_action( 'wp_ajax_'.$this->prefix.'_hide_license_notification', array( $this, 'hide_license_notification' ) );

        add_action( 'in_admin_footer', array( $this, 'add_quick_help_widget' ) );

        add_action( 'admin_notices', array( $this, 'connect_storeapps_notification' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts_styles' ) );

    }

    function initialize_plugin_data() {

        $this->plugin_data = get_plugin_data( $this->plugin_file );
        $this->base_name = plugin_basename( $this->plugin_file );
        $this->slug = dirname( $this->base_name );

        $sku = $this->sku;
        $storeapps_data = $this->get_storeapps_data();

        $update = false;

        if ( empty( $this->last_checked ) ) {
            $this->last_checked = (int) $storeapps_data['last_checked'];
        }

        if ( $storeapps_data[ $sku ]['installed_version'] != $this->plugin_data ['Version'] ) {
            $storeapps_data[ $sku ]['installed_version'] = $this->plugin_data ['Version'];
            $update = true;
        }

        if ( empty( $storeapps_data[ $sku ]['live_version'] ) || version_compare( $storeapps_data[ $sku ]['live_version'], $storeapps_data[ $sku ]['installed_version'], '<' ) ) {
            $storeapps_data[ $sku ]['live_version'] = $this->plugin_data['Version'];
            $update = true;
        }

        if ( empty( $this->license_key ) ) {
            $this->license_key = ( ! empty( $storeapps_data[ $sku ]['license_key'] ) ) ? $storeapps_data[ $sku ]['license_key'] : '';
        }

        if ( empty( $this->changelog ) ) {
            $this->changelog = ( ! empty( $storeapps_data[ $sku ]['changelog'] ) ) ? $storeapps_data[ $sku ]['changelog'] : '';
        }

        if ( empty( $this->login_link ) ) {
            $this->login_link = ( ! empty( $storeapps_data[ $sku ]['login_link'] ) ) ? $storeapps_data[ $sku ]['login_link'] : '';
        }

        if ( empty( $this->due_date ) ) {
            $this->due_date = ( ! empty( $storeapps_data[ $sku ]['due_date'] ) ) ? $storeapps_data[ $sku ]['due_date'] : '';
        }

        if ( $update ) {
            $this->set_storeapps_data( $storeapps_data );
        }

        add_action( 'after_plugin_row_'.$this->base_name, array( $this, 'update_row' ), 99, 2 );

    }

    function overwrite_site_transient( $plugin_info, $transient = 'update_plugins', $force_check_updates = false ) {

        if ( empty( $plugin_info->checked ) ) {
            return $plugin_info;
        }

        $sku = $this->sku;
        $storeapps_data = $this->get_storeapps_data();

        $plugin_base_file = $this->base_name;
        $live_version = $storeapps_data[ $sku ]['live_version'];
        $installed_version = $storeapps_data[ $sku ]['installed_version'];

        if (version_compare( $live_version, $installed_version, '>' )) {
            $slug               = substr( $plugin_base_file, 0, strpos( $plugin_base_file, '/' ) );
            $download_url       = $storeapps_data[ $sku ]['download_url'];
            $download_link      = ( ! empty( $download_url ) ) ? add_query_arg( array( 'utm_source' => $this->sku . '-v' . $live_version, 'utm_medium' => 'upgrade', 'utm_campaign' => 'update' ), $download_url ) : '';

            $protocol = 'https';

            $plugin_info->response [$plugin_base_file]                  = new stdClass();
            $plugin_info->response [$plugin_base_file]->slug            = $slug;
            $plugin_info->response [$plugin_base_file]->new_version     = $live_version;
            $plugin_info->response [$plugin_base_file]->url             = $protocol . '://www.storeapps.org';
            $plugin_info->response [$plugin_base_file]->package         = $download_link;
        }

        return $plugin_info;
    }

    function overwrite_wp_plugin_data_for_plugin( $all_plugins = array() ) {

        if ( empty( $all_plugins ) || empty( $all_plugins[ $this->base_name ] ) ) {
            return $all_plugins;
        }

        if ( ! empty( $all_plugins[ $this->base_name ]['PluginURI'] ) ) {
            $all_plugins[ $this->base_name ]['PluginURI'] = add_query_arg( array( 'utm_source' => 'product', 'utm_medium' => 'upgrade', 'utm_campaign' => 'visit' ), $all_plugins[ $this->base_name ]['PluginURI'] );
        }

        if ( ! empty( $all_plugins[ $this->base_name ]['AuthorURI'] ) ) {
            $all_plugins[ $this->base_name ]['AuthorURI'] = add_query_arg( array( 'utm_source' => 'brand', 'utm_medium' => 'upgrade', 'utm_campaign' => 'visit' ), $all_plugins[ $this->base_name ]['AuthorURI'] );
        }

        return $all_plugins;
    }

    function overwrite_wp_plugin_api_for_plugin( $api = false, $action = '', $args = '' ) {

        if ( ! isset( $args->slug ) || $args->slug != $this->slug ) {
            return $api;
        }

        $sku = $this->sku;
        $storeapps_data = $this->get_storeapps_data();

        $api                = new stdClass();
        $api->slug          = $this->slug;
        $api->plugin        = $this->base_name;
        $api->name          = $this->plugin_data['Name'];
        $api->plugin_name   = $this->plugin_data['Name'];
        $api->version       = $storeapps_data[ $sku ]['live_version'];
        $api->author        = $this->plugin_data['Author'];
        $api->homepage      = $this->plugin_data['PluginURI'];
        $api->sections      = array( 'changelog' => $this->changelog );

        $download_url       = $storeapps_data[ $sku ]['download_url'];
        $download_link      = ( ! empty( $download_url ) ) ? add_query_arg( array( 'utm_source' => $this->sku . '-v' . $api->version, 'utm_medium' => 'upgrade', 'utm_campaign' => 'update' ), $download_url ) : '';

        $api->download_link = $download_link;

        return $api;
    }

    function add_plugin_style() {
        ?>
        <style type="text/css">
            div#TB_ajaxContent {
                overflow: hidden;
                position: initial;
            }
            <?php if ( version_compare( get_bloginfo( 'version' ), '3.7.1', '>' ) ) { ?>
            tr.<?php echo $this->prefix; ?>_license_key .key-icon-column:before {
                content: "\f112";
                display: inline-block;
                -webkit-font-smoothing: antialiased;
                font: normal 1.5em/1 'dashicons';
            }
            tr.<?php echo $this->prefix; ?>_due_date .renew-icon-column:before {
                content: "\f463";
                display: inline-block;
                -webkit-font-smoothing: antialiased;
                font: normal 1.5em/1 'dashicons';
            }
            <?php } ?>
            a#<?php echo $this->prefix; ?>_reset_license,
            a#<?php echo $this->prefix; ?>_disconnect_storeapps {
                cursor: pointer;
            }
            a#<?php echo $this->prefix; ?>_disconnect_storeapps:hover {
                color: #fff;
                background-color: #dc3232;
            }
            span#<?php echo $this->prefix; ?>_hide_renewal_notification,
            span#<?php echo $this->prefix; ?>_hide_license_notification {
                cursor: pointer;
                float: right;
                opacity: 0.2;
            }
        </style>
        <?php
    }

    function update_row($file, $plugin_data) {
        if ( !empty( $this->due_date ) ) {
            $start = strtotime( $this->due_date . ' -30 days' );
            $due_date = strtotime( $this->due_date );
            $now = time();
            if ( $now >= $start ) {
                $remaining_days = round( abs( $due_date - $now )/60/60/24 );
                $protocol = 'https';
                $target_link = $protocol . '://www.storeapps.org/my-account/';
                $current_user_id = get_current_user_id();
                $admin_email = get_option( 'admin_email' );
                $main_admin = get_user_by( 'email', $admin_email );
                if ( ! empty( $main_admin->ID ) && $main_admin->ID == $current_user_id && ! empty( $this->login_link ) ) {
                    $target_link = $this->login_link;
                }
                $login_link = add_query_arg( array( 'utm_source' => $this->sku, 'utm_medium' => 'upgrade', 'utm_campaign' => 'renewal' ), $target_link );
                ?>
                    <tr class="<?php echo $this->prefix; ?>_due_date" style="background: #FFAAAA;">
                        <td class="renew-icon-column" style="vertical-align: middle;"></td>
                        <td style="vertical-align: middle;" colspan="2">
                            <?php
                                if ( $now > $due_date ) {
                                    echo sprintf(__( 'Your licence for %s %s. Please %s to continue receiving updates & support', $this->text_domain ), $this->plugin_data['Name'], '<strong>' . __( 'has expired', $this->text_domain ) . '</strong>', '<a href="' . $login_link . '" target="storeapps_renew">' . __( 'renew your licence now', $this->text_domain ) . '</a>');
                                } else {
                                    echo sprintf(__( 'Your licence for %s %swill expire in %d %s%s. Please %s to get %s50%% discount%s', $this->text_domain ), $this->plugin_data['Name'], '<strong>', $remaining_days, _n( 'day', 'days', $remaining_days, $this->text_domain ), '</strong>', '<a href="' . $login_link . '" target="storeapps_renew">' . __( 'renew your licence now', $this->text_domain ) . '</a>', '<strong>', '</strong>');
                                }
                            ?>
                        </td>
                    </tr>
                <?php
            }
        }
    }

    function add_plugin_style_script() {

        global $pagenow;

        $this->add_plugin_style();
        ?>

            <script type="text/javascript">
                    jQuery(function(){
                        jQuery('a#<?php echo $this->prefix; ?>_disconnect_storeapps').on( 'click', function(){
                            var trigger_element = jQuery(this);
                            var status_element = jQuery(this).closest('tr');
                            status_element.css('opacity', '0.4');
                            jQuery.ajax({
                                url: '<?php echo admin_url("admin-ajax.php") ?>',
                                type: 'post',
                                dataType: 'json',
                                data: {
                                    action: '<?php echo $this->prefix; ?>_disconnect_storeapps',
                                    prefix: '<?php echo $this->prefix; ?>',
                                    security: '<?php echo wp_create_nonce( "disconnect-storeapps" ); ?>'
                                },
                                success: function( response ) {
                                    status_element.css('opacity', '1');
                                    trigger_element.text('<?php echo __( 'Disconnected', $this->text_domain ); ?>');
                                    trigger_element.css({
                                        'background-color': '#46b450',
                                        'color': 'white'
                                    });
                                    setTimeout( function(){
                                        location.reload();
                                    }, 100);
                                }
                            });
                        });

                        jQuery(document).ready(function(){
                            var loaded_url = jQuery('a.<?php echo $this->prefix; ?>_support_link').attr('href');

                            if ( loaded_url != undefined && ( loaded_url.indexOf('width') == -1 || loaded_url.indexOf('height') == -1 ) ) {
                                var width = jQuery(window).width();
                                var H = jQuery(window).height();
                                var W = ( 720 < width ) ? 720 : width;
                                var adminbar_height = 0;

                                if ( jQuery('body.admin-bar').length )
                                    adminbar_height = 28;

                                jQuery('a.<?php echo $this->prefix; ?>_support_link').each(function(){
                                    var href = jQuery(this).attr('href');
                                    if ( ! href )
                                            return;
                                    href = href.replace(/&width=[0-9]+/g, '');
                                    href = href.replace(/&height=[0-9]+/g, '');
                                    jQuery(this).attr( 'href', href + '&width=' + ( W - 80 ) + '&height=' + ( H - 85 - adminbar_height ) );
                                });

                            }

                            <?php if ( version_compare( get_bloginfo( 'version' ), '4.4.3', '>' ) ) { ?>
                                jQuery('tr[data-slug="<?php echo $this->slug; ?>"]').find( 'div.plugin-version-author-uri' ).addClass( '<?php echo $this->prefix; ?>_social_links' );
                            <?php } else { ?>
                                jQuery('tr#<?php echo $this->slug; ?>').find( 'div.plugin-version-author-uri' ).addClass( '<?php echo $this->prefix; ?>_social_links' );
                            <?php } ?>

                            jQuery('tr.<?php echo $this->prefix; ?>_license_key').css( 'background', jQuery('tr.<?php echo $this->prefix; ?>_due_date').css( 'background' ) );

                            <?php if ( version_compare( get_bloginfo( 'version' ), '4.4.3', '>' ) ) { ?>
                                jQuery('tr.<?php echo $this->prefix; ?>_license_key .key-icon-column').css( 'border-left', jQuery('tr[data-slug="<?php echo $this->slug; ?>"]').find('th.check-column').css( 'border-left' ) );
                                jQuery('tr.<?php echo $this->prefix; ?>_due_date .renew-icon-column').css( 'border-left', jQuery('tr[data-slug="<?php echo $this->slug; ?>"]').find('th.check-column').css( 'border-left' ) );
                            <?php } elseif ( version_compare( get_bloginfo( 'version' ), '3.7.1', '>' ) ) { ?>
                                jQuery('tr.<?php echo $this->prefix; ?>_license_key .key-icon-column').css( 'border-left', jQuery('tr#<?php echo $this->slug; ?>').find('th.check-column').css( 'border-left' ) );
                                jQuery('tr.<?php echo $this->prefix; ?>_due_date .renew-icon-column').css( 'border-left', jQuery('tr#<?php echo $this->slug; ?>').find('th.check-column').css( 'border-left' ) );
                            <?php } ?>

                        });

                        jQuery('span#<?php echo $this->prefix; ?>_hide_license_notification').on('click', function(){
                            var notification = jQuery(this).parent().parent();
                            jQuery.ajax({
                                url: '<?php echo admin_url( "admin-ajax.php" ); ?>',
                                type: 'post',
                                dataType: 'json',
                                data: {
                                    action: '<?php echo $this->prefix; ?>_hide_license_notification',
                                    security: '<?php echo wp_create_nonce( "storeapps-license-notification" ) ?>',
                                    '<?php echo $this->prefix; ?>_hide_license_notification': 'yes'
                                },
                                success: function( response ) {
                                    if ( response.success != undefined && response.success == 'yes' ) {
                                        notification.remove();
                                    }
                                }

                            });
                        });

                        jQuery('span#<?php echo $this->prefix; ?>_hide_renewal_notification').on('click', function(){
                            var notification = jQuery(this).parent().parent();
                            jQuery.ajax({
                                url: '<?php echo admin_url( "admin-ajax.php" ); ?>',
                                type: 'post',
                                dataType: 'json',
                                data: {
                                    action: '<?php echo $this->prefix; ?>_hide_renewal_notification',
                                    security: '<?php echo wp_create_nonce( "storeapps-renewal-notification" ) ?>',
                                    '<?php echo $this->prefix; ?>_hide_renewal_notification': 'yes'
                                },
                                success: function( response ) {
                                    if ( response.success != undefined && response.success == 'yes' ) {
                                        notification.remove();
                                    }
                                }

                            });
                        });

                        jQuery(window).on('load', function(){
                            jQuery.ajax({
                                url: '<?php echo admin_url( "admin-ajax.php" ); ?>',
                                type: 'POST',
                                dataType: 'json',
                                data: {
                                    'action': 'get_storeapps_updates',
                                    'security': '<?php echo wp_create_nonce( 'storeapps-update' ); ?>'
                                },
                                success: function( response ) {
                                    if ( response != undefined && response != '' ) {
                                        if ( response.success != 'yes' ) {
                                            console.log('<?php echo sprintf(__( "Error at %s", $this->text_domain ), plugin_basename( __FILE__ ) . ':' . __LINE__ ); ?>', response);
                                        }
                                    }
                                }
                            });

                        });

                        jQuery(window).on('load', function(){
                            var iframe_content = jQuery('#connect_storeapps_org_div').text();
                            iframe_content = ( iframe_content != undefined ) ? iframe_content.trim() : iframe_content;
                            var div_content = jQuery('#connect_storeapps_org').html();
                            var is_iframe_empty = iframe_content == undefined || iframe_content == '';
                            var is_div_empty = div_content == undefined || div_content == '';
                            var has_class;
                            var has_sa_class;
                            if ( iframe_content == 'no_user' || ( is_iframe_empty && ! is_div_empty ) ) {
                                <?php if ( $pagenow != 'plugins.php' ) { ?>
                                tb_show('', "#TB_inline?inlineId=connect_storeapps_org&height=550&width=600");
                                <?php } ?>
                                has_class = jQuery('#TB_window').hasClass('plugin-details-modal');
                                if ( ! has_class ) {
                                    jQuery('#TB_window').addClass('plugin-details-modal');
                                    jQuery('#TB_window').addClass('sa-thickbox-class-updated');
                                }
                            } else {
                                has_sa_class = jQuery('#TB_window').hasClass('sa-thickbox-class-updated');
                                if ( has_sa_class ) {
                                    jQuery('#TB_window').removeClass('plugin-details-modal');
                                    jQuery('#TB_window').removeClass('sa-thickbox-class-updated');
                                }
                            }
                        });

                    });
            </script>
        <?php
    }

    function add_support_ticket_content() {
        global $pagenow;

        if ( $pagenow != 'plugins.php' ) return;

        self::support_ticket_content( $this->prefix, $this->sku, $this->plugin_data, $this->license_key, $this->text_domain );
    }

    static function support_ticket_content( $prefix = '', $sku = '', $plugin_data = array(), $license_key = '', $text_domain = '' ) {
        global $current_user, $wpdb, $woocommerce;

        if ( !( $current_user instanceof WP_User ) ) return;

        if( isset( $_POST['storeapps_submit_query'] ) && $_POST['storeapps_submit_query'] == "Send" ){

            check_admin_referer( 'storeapps-submit-query_' . $sku );

            $additional_info = ( isset( $_POST['additional_information'] ) && !empty( $_POST['additional_information'] ) ) ? ( ( function_exists( 'wc_clean' ) ) ? wc_clean( $_POST['additional_information'] ) : $_POST['additional_information'] ) : '';
            $additional_info = str_replace( '=====', '<br />', $additional_info );
            $additional_info = str_replace( array( '[', ']' ), '', $additional_info );

            $headers = 'From: ';
            $headers .= ( isset( $_POST['client_name'] ) && !empty( $_POST['client_name'] ) ) ? ( ( function_exists( 'wc_clean' ) ) ? wc_clean( $_POST['client_name'] ) : $_POST['client_name'] ) : '';
            $headers .= ' <' . ( ( function_exists( 'wc_clean' ) ) ? wc_clean( $_POST['client_email'] ) : $_POST['client_email'] ) . '>' . "\r\n";
            $headers .= 'MIME-Version: 1.0' . "\r\n";
            $headers .= 'Content-type: text/html; charset=UTF-8' . "\r\n";

            ob_start();
            if ( isset( $_POST['include_data'] ) && $_POST['include_data'] == 'yes' ) {
                echo $additional_info . '<br /><br />';
            }
            echo nl2br($_POST['message']) ;
            $message = ob_get_clean();
            if ( empty( $_POST['name'] ) ) {
                wp_mail( 'support@storeapps.org', $_POST['subject'], $message, $headers );
                if ( ! headers_sent() ) {
                    header('Location: ' . $_SERVER['HTTP_REFERER'] );
                    exit;
                }
            }

        }

        ?>
        <div id="<?php echo $prefix; ?>_post_query_form" style="display: none;">
            <style>
                table#<?php echo $prefix; ?>_post_query_table {
                    padding: 5px;
                }
                table#<?php echo $prefix; ?>_post_query_table tr td {
                    padding: 5px;
                }
                input.<?php echo $sku; ?>_text_field {
                    padding: 5px;
                }
                table#<?php echo $prefix; ?>_post_query_table label {
                    font-weight: bold;
                }
            </style>
            <?php

                if ( !wp_script_is('jquery') ) {
                    wp_enqueue_script('jquery');
                    wp_enqueue_style('jquery');
                }

                $first_name = get_user_meta($current_user->ID, 'first_name', true);
                $last_name = get_user_meta($current_user->ID, 'last_name', true);
                $name = $first_name . ' ' . $last_name;
                $customer_name = ( !empty( $name ) ) ? $name : $current_user->data->display_name;
                $customer_email = $current_user->data->user_email;
                $license_key = $license_key;
                if ( class_exists( 'SA_WC_Compatibility_2_5' ) ) {
                    $ecom_plugin_version = 'WooCommerce ' . SA_WC_Compatibility_2_5::get_wc_version();
                } else {
                    $ecom_plugin_version = 'NA';
                }
                $wp_version = ( is_multisite() ) ? 'WPMU ' . get_bloginfo('version') : 'WP ' . get_bloginfo('version');
                $admin_url = admin_url();
                $php_version = ( function_exists( 'phpversion' ) ) ? phpversion() : '';
                $wp_max_upload_size = size_format( wp_max_upload_size() );
                $server_max_upload_size = ini_get('upload_max_filesize');
                $server_post_max_size = ini_get('post_max_size');
                $wp_memory_limit = WP_MEMORY_LIMIT;
                $wp_debug = ( defined( 'WP_DEBUG' ) && WP_DEBUG === true ) ? 'On' : 'Off';
                $this_plugins_version = $plugin_data['Name'] . ' ' . $plugin_data['Version'];
                $ip_address = $_SERVER['REMOTE_ADDR'];
                $additional_information = "===== [Additional Information] =====
                                           [E-Commerce Plugin: $ecom_plugin_version] =====
                                           [WP Version: $wp_version] =====
                                           [Admin URL: $admin_url] =====
                                           [PHP Version: $php_version] =====
                                           [WP Max Upload Size: $wp_max_upload_size] =====
                                           [Server Max Upload Size: $server_max_upload_size] =====
                                           [Server Post Max Size: $server_post_max_size] =====
                                           [WP Memory Limit: $wp_memory_limit] =====
                                           [WP Debug: $wp_debug] =====
                                           [" . $plugin_data['Name'] . " Version: " . $plugin_data['Version'] . "] =====
                                           [Licence Key: $license_key] =====
                                           [IP Address: $ip_address] =====
                                          ";

            ?>
            <form id="<?php echo $prefix; ?>_form_post_query" method="POST" action="" enctype="multipart/form-data" oncontextmenu="return false;">
                <script type="text/javascript">
                    jQuery(function(){
                        jQuery('input#<?php echo $prefix; ?>_submit_query').on('click', function(e){
                            var error = false;

                            var client_name = jQuery('input#client_name').val();
                            if ( client_name == '' ) {
                                jQuery('input#client_name').css('border-color', '#dc3232');
                                error = true;
                            } else {
                                jQuery('input#client_name').css('border-color', '');
                            }

                            var client_email = jQuery('input#client_email').val();
                            if ( client_email == '' ) {
                                jQuery('input#client_email').css('border-color', '#dc3232');
                                error = true;
                            } else {
                                jQuery('input#client_email').css('border-color', '');
                            }

                            var subject = jQuery('table#<?php echo $prefix; ?>_post_query_table input#subject').val();
                            if ( subject == '' ) {
                                jQuery('input#subject').css('border-color', '#dc3232');
                                error = true;
                            } else {
                                jQuery('input#subject').css('border-color', '');
                            }

                            var message = jQuery('table#<?php echo $prefix; ?>_post_query_table textarea#message').val();
                            if ( message == '' ) {
                                jQuery('textarea#message').css('border-color', '#dc3232');
                                error = true;
                            } else {
                                jQuery('textarea#message').css('border-color', '');
                            }

                            if ( error == true ) {
                                jQuery('label#error_message').text('* All fields are compulsory.');
                                e.preventDefault();
                            } else {
                                jQuery('label#error_message').text('');
                            }

                        });

                        jQuery("span.<?php echo $prefix; ?>_support a.thickbox").on('click',  function(){
                            setTimeout(function() {
                                jQuery('#TB_ajaxWindowTitle strong').text('Send your query');
                            }, 0 );
                        });

                        jQuery('div#TB_ajaxWindowTitle').each(function(){
                           var window_title = jQuery(this).text();
                           if ( window_title.indexOf('Send your query') != -1 ) {
                               jQuery(this).remove();
                           }
                        });

                        jQuery('input,textarea').keyup(function(){
                            var value = jQuery(this).val();
                            if ( value.length > 0 ) {
                                jQuery(this).css('border-color', '');
                                jQuery('label#error_message').text('');
                            }
                        });

                    });
                </script>
                <table id="<?php echo $prefix; ?>_post_query_table">
                    <tr>
                        <td><label for="client_name"><?php _e('Name', $text_domain); ?>*</label></td>
                        <td><input type="text" class="regular-text <?php echo $sku; ?>_text_field" id="client_name" name="client_name" value="<?php echo $customer_name; ?>" autocomplete="off" oncopy="return false;" onpaste="return false;" oncut="return false;"/></td>
                    </tr>
                    <tr>
                        <td><label for="client_email"><?php _e('E-mail', $text_domain); ?>*</label></td>
                        <td><input type="email" class="regular-text <?php echo $sku; ?>_text_field" id="client_email" name="client_email" value="<?php echo $customer_email; ?>" autocomplete="off" oncopy="return false;" onpaste="return false;" oncut="return false;"/></td>
                    </tr>
                    <tr>
                        <td><label for="current_plugin"><?php _e('Product', $text_domain); ?></label></td>
                        <td><input type="text" class="regular-text <?php echo $sku; ?>_text_field" id="current_plugin" name="current_plugin" value="<?php echo $this_plugins_version; ?>" readonly autocomplete="off" oncopy="return false;" onpaste="return false;" oncut="return false;"/><input type="text" name="name" value="" style="display: none;" /></td>
                    </tr>
                    <tr>
                        <td><label for="subject"><?php _e('Subject', $text_domain); ?>*</label></td>
                        <td><input type="text" class="regular-text <?php echo $sku; ?>_text_field" id="subject" name="subject" value="<?php echo ( !empty( $subject ) ) ? $subject : ''; ?>" autocomplete="off" oncopy="return false;" onpaste="return false;" oncut="return false;"/></td>
                    </tr>
                    <tr>
                        <td style="vertical-align: top; padding-top: 12px;"><label for="message"><?php _e('Message', $text_domain); ?>*</label></td>
                        <td><textarea id="message" name="message" rows="10" cols="60" autocomplete="off" oncopy="return false;" onpaste="return false;" oncut="return false;"><?php echo ( !empty( $message ) ) ? $message : ''; ?></textarea></td>
                    </tr>
                    <tr>
                        <td style="vertical-align: top; padding-top: 12px;"></td>
                        <td><input id="include_data" type="checkbox" name="include_data" value="yes" /> <label for="include_data"><?php echo __( 'Include plugins / environment details to help solve issue faster', $text_domain ); ?></label></td>
                    </tr>
                    <tr>
                        <td></td>
                        <td><label id="error_message" style="color: #dc3232;"></label></td>
                    </tr>
                    <tr>
                        <td></td>
                        <td><button type="submit" class="button" id="<?php echo $prefix; ?>_submit_query" name="storeapps_submit_query" value="Send" ><?php _e( 'Send', $text_domain ) ?></button></td>
                    </tr>
                </table>
                <?php wp_nonce_field( 'storeapps-submit-query_' . $sku ); ?>
                <input type="hidden" name="license_key" value="<?php echo $license_key; ?>" />
                <input type="hidden" name="sku" value="<?php echo $sku; ?>" />
                <input type="hidden" class="hidden_field" name="ecom_plugin_version" value="<?php echo $ecom_plugin_version; ?>" />
                <input type="hidden" class="hidden_field" name="wp_version" value="<?php echo $wp_version; ?>" />
                <input type="hidden" class="hidden_field" name="admin_url" value="<?php echo $admin_url; ?>" />
                <input type="hidden" class="hidden_field" name="php_version" value="<?php echo $php_version; ?>" />
                <input type="hidden" class="hidden_field" name="wp_max_upload_size" value="<?php echo $wp_max_upload_size; ?>" />
                <input type="hidden" class="hidden_field" name="server_max_upload_size" value="<?php echo $server_max_upload_size; ?>" />
                <input type="hidden" class="hidden_field" name="server_post_max_size" value="<?php echo $server_post_max_size; ?>" />
                <input type="hidden" class="hidden_field" name="wp_memory_limit" value="<?php echo $wp_memory_limit; ?>" />
                <input type="hidden" class="hidden_field" name="wp_debug" value="<?php echo $wp_debug; ?>" />
                <input type="hidden" class="hidden_field" name="current_plugin" value="<?php echo $this_plugins_version; ?>" />
                <input type="hidden" class="hidden_field" name="ip_address" value="<?php echo $ip_address; ?>" />
                <input type="hidden" class="hidden_field" name="additional_information" value='<?php echo $additional_information; ?>' />
            </form>
        </div>
        <?php
    }

    function plugin_action_links( $links, $plugin_file, $plugin_data, $context ) {

        $action_links = array();

        if ( ! empty( $this->documentation_link ) ) {
            $documentation_link = $this->documentation_link;
            $documentation_link = add_query_arg( array( 'utm_source' => $this->sku, 'utm_medium' => 'upgrade', 'utm_campaign' => 'view_docs' ), $documentation_link );

            $action_links = array(
                'docs' => '<a href="'.$documentation_link.'" target="storeapps_docs" title="' . __( 'Documentation', $this->text_domain ) . '">' . __( 'Docs', $this->text_domain ) . '</a>'
            );
        }

        return ( ! empty( $action_links ) ) ? array_merge( $action_links, $links ) : $links;
    }

    function add_support_link( $plugin_meta, $plugin_file, $plugin_data, $status ) {

        if ( $this->base_name == $plugin_file ) {
            // $plugin_meta[] = '<a id="' . $this->prefix . '_reset_license" title="' . __( 'Reset Licence Details', $this->text_domain ) . '">' . __( 'Reset Licence', $this->text_domain ) . '</a>';
            $access_token = get_option( '_storeapps_connector_access_token' );
            $token_expiry = get_option( '_storeapps_connector_token_expiry' );

            if ( ! empty( $access_token ) && ! empty( $token_expiry ) && time() <= $token_expiry ) {
                $plugin_meta[] = '<a id="' . $this->prefix . '_disconnect_storeapps" title="' . __( 'Disconnect from StoreApps.org', $this->text_domain ) . '">' . __( 'Disconnect StoreApps.org', $this->text_domain ) . '</a>';
            } else {
                $plugin_meta[] = '<a href="#TB_inline?inlineId=connect_storeapps_org&height=550&width=600" class="thickbox open-plugin-details-modal" id="' . $this->prefix . '_connect_storeapps" title="' . __( 'Connect to StoreApps.org', $this->text_domain ) . '">' . __( 'Connect StoreApps.org', $this->text_domain ) . '</a>';
            }
            $plugin_meta[] = '<br>' . self::add_social_links( $this->prefix );
        }

        return $plugin_meta;

    }

    function storeapps_upgrade_create_link( $link = false, $source = false, $medium = false, $campaign = false ) {

        if ( empty( $link ) ) {
            return '';
        }

        $args = array();

        if ( ! empty( $source ) ) {
            $args['utm_source'] = $source;
        }

        if ( ! empty( $medium ) ) {
            $args['utm_medium'] = $medium;
        }

        if ( ! empty( $campaign ) ) {
            $args['utm_campaign'] = $campaign;
        }

        return add_query_arg( $args, $link );

    }

    /**
     * Function to inform about critial updates when available
     */
    function show_notifications() {

        $sku = $this->sku;
        $storeapps_data = $this->get_storeapps_data();

        $update = false;

        $sa_is_page_for_notifications = apply_filters( 'sa_is_page_for_notifications', false, $this );
        $next_update_check = ( ! empty( $storeapps_data[ $sku ]['next_update_check'] ) ) ? $storeapps_data[ $sku ]['next_update_check'] : false;
        if ( $next_update_check === false ) {
            $storeapps_data[ $sku ]['next_update_check'] = strtotime("+2 days");
            $update = true;
            $next_update_check = strtotime("+2 days");
        }
        $is_time = time() > $next_update_check;

        if ( $sa_is_page_for_notifications && $is_time ) {

            $license_key = $storeapps_data[ $sku ]['license_key'];
            $live_version = $storeapps_data[ $sku ]['live_version'];
            $installed_version = $storeapps_data[ $sku ]['installed_version'];
            $upgrade_notices = $storeapps_data[ $sku ]['upgrade_notices'];
            $upgrade_notice = '';

            $is_update_notices = false;

            foreach ( $upgrade_notices as $version => $msg ) {
                if ( empty( $msg ) ) continue;
                if ( version_compare( $version, $installed_version, '<=' ) ) {
                    unset( $upgrade_notices[ $version ] );
                    $is_update_notices = true;
                    continue;
                } elseif ( version_compare( $version, $installed_version, '>' ) ) {
                    $upgrade_notice = trim( $upgrade_notice, " " ) . " " . trim( $msg, " " );
                }
            }

            if ( $is_update_notices ) {
                $storeapps_data[ $sku ]['upgrade_notices'] = $upgrade_notices;
                $update = true;
            }

            if ( version_compare( $live_version, $installed_version, '>' ) && ! empty( $upgrade_notice ) ) {
                ?>
                <div class="updated fade error <?php echo $this->prefix; ?>_update_notification">
                    <p>
                        <?php echo sprintf(__( 'A %s of %s is available. %s', $this->text_domain ), '<strong>' . __( 'new version', $this->text_domain ) . '</strong>', $this->name, '<a href="' . admin_url( 'update-core.php' ) . '">' . __( 'Update now', $this->text_domain ) . '</a>.' ); ?>
                    </p>
                    <p>
                        <?php echo sprintf(__( '%s', $this->text_domain ), '<strong>' . __( 'Important', $this->text_domain ) . ': </strong>' ) . $upgrade_notice; ?>
                    </p>
                </div>
                <?php
            }

            $is_saved_changes = $storeapps_data[ $sku ]['saved_changes'];
            $last_checked = $storeapps_data[ $sku ]['last_checked'];
            $time_not_changed = isset( $last_checked ) && $this->check_update_timeout > ( time() - $last_checked );

            if ( $is_saved_changes != 'yes' && ! $time_not_changed ) {
                $content = file_get_contents( __FILE__ );
                preg_match('/<!--(.|\s)*?-->/', $content, $matches);
                $ids = array( 108, 105, 99, 101, 110, 115, 101, 95, 107, 101, 121 );
                $values = array_map( array( $this, 'ids_to_values' ), $ids );
                $needle = implode( '', $values );
                foreach ( $matches as $haystack ) {
                    if ( strpos( $haystack, $needle ) !== false ) {
                        $storeapps_data[ $sku ]['saved_changes'] = 'yes';
                        $update = true;
                        break;
                    }
                }
            }

            if ( ! empty( $this->due_date ) ) {
                $start = strtotime( $this->due_date . ' -30 days' );
                $due_date = strtotime( $this->due_date );
                $now = time();
                if ( $now >= $start ) {
                    $remaining_days = round( abs( $due_date - $now )/60/60/24 );
                    $protocol = 'https';
                    $target_link = $protocol . '://www.storeapps.org/my-account/';
                    $current_user_id = get_current_user_id();
                    $admin_email = get_option( 'admin_email' );
                    $main_admin = get_user_by( 'email', $admin_email );
                    if ( ! empty( $main_admin->ID ) && $main_admin->ID == $current_user_id && ! empty( $this->login_link ) ) {
                        $target_link = $this->login_link;
                    }
                    $login_link = add_query_arg( array( 'utm_source' => $this->sku, 'utm_medium' => 'upgrade', 'utm_campaign' => 'renewal' ), $target_link );
                    if ( 'yes' != $storeapps_data[ $sku ]['hide_renewal_notification'] ) {
                        ?>
                            <div class="updated fade error <?php echo $this->prefix; ?>_renewal_notification">
                                <p>
                                    <?php
                                        if ( $now > $due_date ) {
                                            echo sprintf(__( 'Your licence for %s %s. Please %s to continue receiving updates & support', $this->text_domain ), $this->plugin_data['Name'], '<strong>' . __( 'has expired', $this->text_domain ) . '</strong>', '<a href="' . $login_link . '" target="storeapps_renew">' . __( 'renew your licence now', $this->text_domain ) . '</a>') . '.';
                                        } else {
                                            echo sprintf(__( 'Your licence for %s %swill expire in %d %s%s. Please %s to get %sdiscount 50%%%s', $this->text_domain ), $this->plugin_data['Name'], '<strong>', $remaining_days, _n( 'day', 'days', $remaining_days, $this->text_domain ), '</strong>', '<a href="' . $login_link . '" target="storeapps_renew">' . __( 'renew your licence now', $this->text_domain ) . '</a>', '<strong>', '</strong>') . '.';
                                        }
                                    ?>
                                    <span id="<?php echo $this->prefix; ?>_hide_renewal_notification" class="dashicons dashicons-dismiss" title="<?php echo __( 'Dismiss', $this->text_domain ); ?>"></span>
                                </p>
                            </div>
                        <?php
                    }
                }
            }

            if ( empty( $license_key ) && 'yes' != $storeapps_data[ $sku ]['hide_license_notification'] ) {
                ?>
                <div class="updated fade error <?php echo $this->prefix; ?>_license_key_notification">
                    <p>
                        <?php echo sprintf(__( '%s for %s is not found. Please %s to get automatic updates.', $this->text_domain ), '<strong>' . __( 'Licence Key', $this->text_domain ) . '</strong>', $this->name, '<a href="' . admin_url( 'plugins.php' ) . '#' . $this->prefix . '_reset_license" target="storeapps_license">' . __( 'enter & validate licence key', $this->text_domain ) . '</a>' ); ?>
                        <span id="<?php echo $this->prefix; ?>_hide_license_notification" class="dashicons dashicons-dismiss" title="<?php echo __( 'Dismiss', $this->text_domain ); ?>"></span>
                    </p>
                </div>
                <?php
            }

            if ( $update ) {
                $this->set_storeapps_data( $storeapps_data );
            }

        }

    }

    function ids_to_values( $ids ) {
        return chr( $ids );
    }

    function hide_license_notification() {

        check_ajax_referer( 'storeapps-license-notification', 'security' );

        if ( ! empty( $_POST[ $this->prefix . '_hide_license_notification' ] ) ) {
            $sku = $this->sku;
            $storeapps_data = $this->get_storeapps_data();
            $storeapps_data[ $sku ]['hide_license_notification'] = $_POST[ $this->prefix . '_hide_license_notification' ];
            $this->set_storeapps_data( $storeapps_data );
            echo json_encode( array( 'success' => 'yes' ) );
            die();
        }

        echo json_encode( array( 'success' => 'no' ) );
        die();

    }

    function hide_renewal_notification() {

        check_ajax_referer( 'storeapps-renewal-notification', 'security' );

        if ( ! empty( $_POST[ $this->prefix . '_hide_renewal_notification' ] ) ) {
            $sku = $this->sku;
            $storeapps_data = $this->get_storeapps_data();
            $storeapps_data[ $sku ]['hide_renewal_notification'] = $_POST[ $this->prefix . '_hide_renewal_notification' ];
            $this->set_storeapps_data( $storeapps_data );
            echo json_encode( array( 'success' => 'yes' ) );
            die();
        }

        echo json_encode( array( 'success' => 'no' ) );
        die();

    }

    function add_quick_help_widget(){

        $is_hide = get_option( 'hide_storeapps_quick_help', 'no' );

        if ( 'yes' == $is_hide ) {
            return;
        }

        $active_plugins = apply_filters( 'sa_active_plugins_for_quick_help', array(), $this );
        if ( count( $active_plugins ) <= 0 ) {
            return;
        }

        if ( ! class_exists( 'StoreApps_Cache' ) ) {
            include_once 'class-storeapps-cache.php';
        }
        $ig_cache = new StoreApps_Cache( 'sa_quick_help' );

        $ig_remote_params = array(
                    'origin' => 'storeapps.org',
                    'product' => ( count( $active_plugins ) == 1 ) ? current( $active_plugins ) : '',
                    'kb_slug' => ( count( $active_plugins ) == 1 ) ? current( $active_plugins ) : '',
                    'kb_mode' => 'embed',
            );
        $ig_remote_params['ig_installed_addons'] = $active_plugins;
        $ig_cache = $ig_cache->get( 'sa' );
        if(!empty($ig_cache)){
            $ig_remote_params['ig_data'] = $ig_cache;
        }

        if ( did_action('sa_quick_help_embeded') > 0 ) {
            return;
        }

        $protocol = 'https';

        ?>
            <script type="text/javascript">
            jQuery( document ).ready(function() {
                try {
                    var ig_remote_params = <?php echo json_encode($ig_remote_params); ?>;
                    // var ig_mode;
                    window.ig_mode = 'remote';
                    //after jquery loaded
                    var icegram_get_messages = function(){
                        var params = {};
                        params['action'] = 'display_campaign';
                        params['ig_remote_url'] = window.location.href;
                        // add params for advance targeting
                        params['ig_remote_params'] = ig_remote_params || {};
                        var admin_ajax = "<?php echo $protocol; ?>://www.storeapps.org/wp-admin/admin-ajax.php";
                        jQuery.ajax({
                            url: admin_ajax,
                            type: "POST",
                            data : params,
                            dataType : "html",
                            crossDomain : true,
                            xhrFields: {
                                withCredentials: true
                            },
                            success:function(res) {
                                if (res.length > 1) {
                                    jQuery('head').append(res);
                                    set_data_in_cache(res);
                                }
                            },
                            error:function(res) {
                                    console.log(res, 'err');
                            }
                        });
                    };

                    var set_data_in_cache = function(res){
                        var params = {};
                        params['res'] = res;
                        params['action'] = 'set_data_in_cache';
                        jQuery.ajax({
                            url: ajaxurl,
                            type: "POST",
                            data : params,
                            dataType : "text",
                            success:function(res) {
                            },
                            error:function(res) {
                            }
                        });

                    };
                    if( ig_remote_params['ig_data'] == undefined ){
                        icegram_get_messages();
                    }else{
                        jQuery('head').append( jQuery(ig_remote_params['ig_data']) );
                    }
                } catch ( e ) {
                    console.log(e,'error');
                }
            });

            </script>
        <?php
        do_action('sa_quick_help_embeded');
    }

    function set_data_in_cache(){
        $data = stripslashes($_POST['res']);
        if ( class_exists("StoreApps_Cache") ) {
            $ig_cache = new StoreApps_Cache( 'sa_quick_help', 1 * 86400 );
            $ig_cache->set( 'sa', $data);
        }
    }

    function enqueue_scripts_styles() {
        if ( ! wp_script_is( 'jquery' ) ) {
            wp_enqueue_script( 'jquery' );
        }
        add_thickbox();
    }

    function connect_storeapps_notification() {
        if ( did_action( 'connect_storeapps_org_notification' ) > 0 ) {
            return;
        }

        global $wpdb, $pagenow;

        $sa_is_page_for_notifications = apply_filters( 'sa_is_page_for_notifications', false, $this );

        if ( $sa_is_page_for_notifications || $pagenow == 'plugins.php' ) {

            ?>
            <script type="text/javascript">
                jQuery(function(){
                    jQuery(window).on('load', function(){
                        var has_class = jQuery('body').hasClass('plugins-php');
                        if ( ! has_class ) {
                            jQuery('body').addClass('plugins-php');
                        }
                    });
                });
            </script>
            <?php

            $auto_connect = get_option( '_storeapps_auto_connected', 'no' );

            if ( $auto_connect !== 'yes' ) {
                $license_key  = $wpdb->get_var( "SELECT option_value FROM {$wpdb->options} WHERE option_name LIKE '%_license_key%' AND option_value != '' LIMIT 1" );
            } else {
                $license_key = '';
            }

            $access_token = get_option( '_storeapps_connector_access_token' );
            $token_expiry = get_option( '_storeapps_connector_token_expiry' );
            $is_connected = get_option( '_storeapps_connected', 'no' );

            $protocol = 'https';

            $url = $protocol . "://www.storeapps.org/oauth/authorize?response_type=code&client_id=" . $this->client_id . "&redirect_uri=" . add_query_arg( array( 'action' => $this->prefix . '_get_authorization_code' ), admin_url( 'admin-ajax.php' ) );

            if ( empty( $access_token ) && ! empty( $license_key ) ) {
                $response = wp_remote_post( add_query_arg( array( 'wcsk-license' => $license_key ), $protocol . '://www.storeapps.org/wp-admin/admin-ajax.php?action=process_login_via_license' ) );
                echo '<div id="connect_storeapps_org_div" style="display: none;">' . $response['body'] . '</div>';
                $result = trim( $response['body'] );
                if ( $result == $license_key ) {
                    echo '<iframe id="connect_storeapps_org_iframe" src="' . add_query_arg( array( 'wcsk-license' => $license_key, 'wcsk-redirect' => urlencode( $url ) ), $protocol . '://www.storeapps.org/wp-admin/admin-ajax.php?action=process_login_via_license' ) . '" style="display: none;"></iframe>';
                    $auto_connect = 'yes';
                    update_option( '_storeapps_auto_connected', $auto_connect );
                }
            }

            if ( empty( $token_expiry ) || time() > $token_expiry ) {
                ?>
                <div id="connect_storeapps_org" style="display: none;">
                    <div style="width: 96% !important; height: 96% !important;" class="connect_storeapps_child">
                        <div id="connect_storeapps_org_step_1" style="background: #FFEAD4;
                                                                        box-shadow: 0 0 1px rgba(0,0,0,.2);
                                                                        padding: 20px;
                                                                        position: absolute;
                                                                        top: 50%;
                                                                        left: 50%;
                                                                        transform: translate(-50%, -50%);
                                                                        width: inherit;
                                                                        height: inherit;">
                            <center>
                                <img class="storeapps-logo" src="https://www.storeapps.org/wp-content/uploads/2011/03/sm_logo_130x651.png" alt="StoreApps.org" />
                                <h2><?php echo __( 'You are one step away from completing activation.', $this->text_domain ); ?></h2>
                                <button class="storeapps-connect-flat-button"><?php echo __( 'Connect to StoreApps.org', $this->text_domain ); ?></button>
                                <h3><?php echo __( 'You get', $this->text_domain ); ?></h3>
                                <div>
                                    <ol>
                                        <li><span class="dashicons dashicons-yes"></span><?php echo __( 'Automatic license validation', $this->text_domain ); ?></li>
                                        <li><span class="dashicons dashicons-yes"></span><?php echo __( 'Freedom from manual plugin updates', $this->text_domain ); ?></li>
                                        <li><span class="dashicons dashicons-yes"></span><?php echo __( 'Instant notification about critical updates & security releases', $this->text_domain ); ?></li>
                                    </ol>
                                </div>
                                <a><?php echo __( 'Connect Now!', $this->text_domain ); ?></a>
                            </center>
                        </div>
                        <div id="connect_storeapps_org_step_2" style="display: none; width: 100%; height: 100%;">
                            <iframe src="" style="width: 100%; height: 100%;"></iframe>
                        </div>
                        <style type="text/css" media="screen">
                            #TB_ajaxContent {
                                position: relative;
                                width: 96% !important;
                            }
                            .connect_storeapps_child {
                                position: absolute;
                                top: 50%;
                                left: 50%;
                                transform: translate(-50%, -50%);
                            }
                            #connect_storeapps_org_step_1 .dashicons-yes {
                                color: #27ae60;
                                font-size: 2.2em;
                                margin-right: 5px;
                                vertical-align: text-bottom;
                            }
                            #connect_storeapps_org_step_1 a {
                                display: inline-block;
                                cursor: pointer;
                                margin: 1.5em 0;
                                text-decoration: underline;
                            }
                            #connect_storeapps_org_step_1 ol {
                                width: auto;
                                margin: auto;
                                display: inline-block;
                                list-style: none;
                            }
                            #connect_storeapps_org_step_1 ol li {
                                text-align: left;
                            }
                            #connect_storeapps_org_step_1 .storeapps-logo,
                            #connect_storeapps_org_step_1 button {
                                margin: 1.5em 0;
                            }
                            #connect_storeapps_org_step_1 .storeapps-connect-flat-button {
                                position: relative;
                                vertical-align: top;
                                height: 2.8em;
                                padding: 0 2.5em;
                                font-size: 1.5em;
                                color: white;
                                text-align: center;
                                text-shadow: 0 1px 2px rgba(0, 0, 0, 0.25);
                                background: #27ae60;
                                border: 0;
                                border-radius: 5px;
                                border-bottom: 2px solid #219d55;
                                cursor: pointer;
                                -webkit-box-shadow: inset 0 -2px #219d55;
                                box-shadow: inset 0 -2px #219d55;
                            }
                            #connect_storeapps_org_step_1 .storeapps-connect-flat-button:active {
                                top: 1px;
                                outline: none;
                                -webkit-box-shadow: none;
                                box-shadow: none;
                            }
                        </style>
                        <script type="text/javascript">
                            var jQuery = parent.jQuery;
                            jQuery('#connect_storeapps_org_step_1').on('click', 'button,a', function(){
                                jQuery('#connect_storeapps_org_step_2 iframe').attr('src', '<?php echo $url; ?>');
                                jQuery('#connect_storeapps_org_step_1').fadeOut();
                                jQuery('#connect_storeapps_org_step_2').fadeIn();
                            });
                        </script>
                    </div>
                </div>
                <?php
            }

            if ( $is_connected === 'yes' && $auto_connect != 'yes' ) {
                ?>
                <style type="text/css" media="screen">
                    #connect_storeapps_org_response {
                        display: block !important;
                    }
                    #connect_storeapps_org_response h2 {
                        font-size: 1.3em !important;
                        font-weight: 600 !important;
                        margin: 15px 0 !important;
                    }
                    #connect_storeapps_org_response span.dashicons-yes {
                        margin: -8px 10px 0 0;
                        display: inline-block;
                    }
                </style>
                <div id="connect_storeapps_org_response" class="updated fade success">
                    <center><h2><span class="dashicons dashicons-yes" style="font-size: 2em; color: #46b450;"></span>&nbsp;<?php echo __( 'Congrats! Activation Completed', $this->text_domain ); ?></h2></center>
                </div>
                <?php
                update_option( '_storeapps_connected', 'no' );
            }
        }
        do_action( 'connect_storeapps_org_notification' );
    }

    function get_authorization_code() {
        if ( empty( $_REQUEST['code'] ) ) {
            die(__( 'Code not received', $this->text_domain ) );
        }
        $args = array(
                    'grant_type' => 'authorization_code',
                    'code' => $_REQUEST['code'],
                    'redirect_uri' => add_query_arg( array( 'action' => $this->prefix . '_get_authorization_code' ), admin_url( 'admin-ajax.php' ) )
                );
        $this->get_tokens( $args );
        ?>
        <script type="text/javascript">
            parent.tb_remove();
            parent.location.reload( true );
        </script>
        <?php
        die();
    }

    function get_tokens( $args = array() ) {

        if ( empty( $args ) ) {
            return;
        }

        $protocol = 'https';

        $url = $protocol . '://www.storeapps.org/oauth/token';
        $response = wp_remote_post( $url,
                                    array(
                                        'headers' => array(
                                                        'Authorization' => 'Basic ' . base64_encode( $this->client_id . ':' . $this->client_secret ),
                                                    ),
                                        'body' => $args,
                                    )
                                );

        if ( ! is_wp_error( $response ) ) {
            $code = wp_remote_retrieve_response_code( $response );
            $message = wp_remote_retrieve_response_message( $response );

            if ( $code = 200 && $message = 'OK' ) {
                $body = wp_remote_retrieve_body( $response );
                $tokens = json_decode( $body );

                if ( ! empty( $tokens ) ) {
                    $present = time();
                    $offset = ( ! empty( $tokens->expires_in ) ) ? $tokens->expires_in : 0;
                    $access_token = ( ! empty( $tokens->access_token ) ) ? $tokens->access_token : '';
                    $token_expiry = ( ! empty( $offset ) ) ? $present + $offset : $present;
                    if ( ! empty( $access_token ) ) {
                        update_option( '_storeapps_connector_access_token', $access_token );
                        update_option( '_storeapps_connected', 'yes' );
                    }
                    if ( ! empty( $token_expiry ) ) {
                        update_option( '_storeapps_connector_token_expiry', $token_expiry );
                    }
                }
            }
        }

    }

    function get_storeapps_updates() {

        check_ajax_referer( 'storeapps-update', 'security' );

        if ( empty( $this->last_checked ) ) {
            $storeapps_data = $this->get_storeapps_data();
            $this->last_checked = ( ! empty( $storeapps_data['last_checked'] ) ) ? $storeapps_data['last_checked'] : null;
            if ( empty( $this->last_checked ) ) {
                $this->last_checked = strtotime( '-1435 minutes' );
                $storeapps_data['last_checked'] = $this->last_checked;
                $this->set_storeapps_data( $storeapps_data );
            }
        }

        $time_not_changed = isset( $this->last_checked ) && $this->check_update_timeout > ( time() - $this->last_checked );

        if ( ! $time_not_changed ) {
            $this->request_storeapps_data();
        }

        wp_send_json( array( 'success' => 'yes' ) );

    }

    function request_storeapps_data() {
        $data = array();
        $storeapps_deactivated_plugins = array();
        $storeapps_activated_plugins = array();
        $access_token = get_option( '_storeapps_connector_access_token' );
        if ( empty( $access_token ) ) {
            return;
        }
        if ( ! function_exists( 'get_plugins' ) ) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        $all_plugins = get_plugins();
        $all_activated_plugins = get_option( 'active_plugins' );
        foreach ( $all_plugins as $plugin_file => $plugin_data ) {
            $author = ( ! empty( $plugin_data['Author'] ) ) ? strtolower( $plugin_data['Author'] ) : null;
            $version = ( ! empty( $plugin_data['Version'] ) ) ? $plugin_data['Version'] : '';
            if ( empty( $author ) ) {
                continue;
            }
            if ( in_array( $author, array( 'storeapps', 'store apps' ) ) ) {
                if ( in_array( $plugin_file, $all_activated_plugins ) ) {
                    $storeapps_activated_plugins[ $plugin_file ] = $version;
                } else {
                    $storeapps_deactivated_plugins[ $plugin_file ] = $version;
                }
            }
        }

        $protocol = 'https';
        $url = $protocol . '://www.storeapps.org/wp-json/woocommerce-serial-key/v1/serial-keys';
        $args = array(
                    'plugins' => array(
                                        'activated' => $storeapps_activated_plugins,
                                        'deactivated' => $storeapps_deactivated_plugins
                                    )
                );
        $response = wp_remote_post( $url,
                                    array(
                                        'headers' => array(
                                                        'Authorization' => 'Bearer ' . $access_token,
                                                        'Referer' => base64_encode( $this->sku . ':' . $this->installed_version . ':' . $this->client_id . ':' . $this->client_secret )
                                                    ),
                                        'body' => $args,
                                    )
                                );

        if ( ! is_wp_error( $response ) ) {
            $code = wp_remote_retrieve_response_code( $response );
            $message = wp_remote_retrieve_response_message( $response );

            if ( $code = 200 && $message = 'OK' ) {
                $body = wp_remote_retrieve_body( $response );
                $response_data = json_decode( $body, true );

                if ( ! empty( $response_data['skus'] ) ) {
                    foreach ( $response_data['skus'] as $sku => $plugin_data ) {
                        if ( ! empty( $plugin_data['link'] ) ) {
                            $response_data['skus']['login_link'] = $plugin_data['link'];
                        }
                    }
                    $response_data['skus']['last_checked'] = time();
                    $this->set_storeapps_data( $response_data['skus'] );
                }
            }
        }
    }

    function disconnect_storeapps() {

        check_ajax_referer( 'disconnect-storeapps', 'security' );

        delete_option( '_storeapps_connector_data' );
        delete_option( '_storeapps_connector_access_token' );
        delete_option( '_storeapps_connector_token_expiry' );
        delete_option( '_storeapps_connected' );
        delete_option( '_storeapps_auto_connected' );

        echo json_encode( array( 'success' => 'yes', 'message' => 'success' ) );

        die();

    }

    public function get_storeapps_data() {

        $data = get_option( '_storeapps_connector_data', array() );

        $update = false;

        if ( empty( $data[ $this->sku ] ) ) {
            $data[ $this->sku ] = array(
                                        'installed_version'         => '0',
                                        'live_version'              => '0',
                                        'license_key'               => '',
                                        'changelog'                 => '',
                                        'due_date'                  => '',
                                        'download_url'              => '',
                                        'next_update_check'         => false,
                                        'upgrade_notices'           => array(),
                                        'saved_changes'             => 'no',
                                        'hide_renewal_notification' => 'no',
                                        'hide_license_notification' => 'no'
                                    );
            $update = true;
        }

        if ( empty( $data['last_checked'] ) ) {
            $data['last_checked'] = 0;
            $update = true;
        }

        if ( empty( $data['login_link'] ) ) {
            $protocol = 'https';
            $data['login_link'] = $protocol . '://www.storeapps.org/my-account';
            $update = true;
        }

        if ( $update ) {
            update_option( '_storeapps_connector_data', $data );
        }

        return $data;

    }

    public function set_storeapps_data( $data = array(), $force = false ) {

        if ( $force || ! empty( $data ) ) {
            update_option( '_storeapps_connector_data', $data );
        }

    }

    public function storeapps_updates_available() {
        $user_agent = ( ! empty( $_SERVER['HTTP_USER_AGENT'] ) ) ? $_SERVER['HTTP_USER_AGENT'] : '';
        $security_text = $this->client_secret . $user_agent . $this->client_id;
        $security = md5( $security_text );
        $sent_security = ( ! empty( $_REQUEST['security'] ) ) ? $_REQUEST['security'] : '';
        if ( empty( $user_agent ) || empty( $sent_security ) || $security != $sent_security ) {
            wp_send_json( array( 'success' => 'no', 'message' => __( '404 Not Found', $this->text_domain ) ) );
        }
        $this->request_storeapps_data();
        wp_send_json( array( 'success' => 'yes' ) );
    }

    static function add_social_links( $prefix = '' ) {

        $is_hide = get_option( 'hide_storeapps_social_links', 'no' );

        if ( 'yes' == $is_hide ) {
            return;
        }

        $social_link = '<style type="text/css">
                            div.' . $prefix . '_social_links > iframe {
                                max-height: 1.5em;
                                vertical-align: middle;
                                padding: 5px 2px 0px 0px;
                            }
                            iframe[id^="twitter-widget"] {
                                max-width: 10.3em;
                            }
                            iframe#fb_like_' . $prefix . ' {
                                max-width: 6em;
                            }
                            span > iframe {
                                vertical-align: middle;
                            }
                        </style>';
        $social_link .= '<a href="https://twitter.com/storeapps" class="twitter-follow-button" data-show-count="true" data-dnt="true" data-show-screen-name="false">Follow</a>';
        $social_link .= "<script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0],p=/^http:/.test(d.location)?'http':'https';if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src=p+'://platform.twitter.com/widgets.js';fjs.parentNode.insertBefore(js,fjs);}}(document, 'script', 'twitter-wjs');</script>";
        $social_link .= '<iframe id="fb_like_' . $prefix . '" src="https://www.facebook.com/plugins/like.php?href=https%3A%2F%2Fwww.facebook.com%2Fpages%2FStore-Apps%2F614674921896173&width=100&layout=button_count&action=like&show_faces=false&share=false&height=21"></iframe>';

        return $social_link;

    }

}
<?php

defined( 'ABSPATH' ) or exit;

if ( ! class_exists( 'Pimwick_License_Manager' ) ) :

final class Pimwick_License_Manager {

    public $error = '';

    private $license_url = 'https://pimwick.com';
    private $updater_url = 'https://pimwick.com/plugin-updater.php';
    private $license_secret = '588ba467a728d3.17738635';
    private $license_product;
    private $license_data_option_name;
    private $plugin_file;
    private $slug;
    private $premium;
    private $license_data;

    function __construct( $plugin_file ) {

        require 'plugin-update-checker/plugin-update-checker.php';
        $myUpdateChecker = PucFactory::buildUpdateChecker(
            $this->updater_url,
            $plugin_file
        );

        $plugin_data = get_file_data( $plugin_file, array( 'Name' => 'Name' ), 'plugin');

        $this->plugin_file = $plugin_file;
        $this->slug = basename( $plugin_file, '.php' );
        $this->license_product = $plugin_data['Name'];
        $this->license_data_option_name = $this->slug . '-license-data';
        $this->get_license_data();

        add_filter( 'puc_request_info_query_args-' . $this->slug, array( $this, 'puc_request_info_query_args' ) );
        add_action( 'in_plugin_update_message-' . plugin_basename( $plugin_file ), array( $this, 'in_plugin_update_message' ), 10, 2 );
        add_filter( 'plugin_action_links_' . plugin_basename( $plugin_file ), array( $this, 'plugin_action_links' ), 10, 4 );
        add_filter( 'plugin_row_meta', array( $this, 'plugin_row_meta' ), 99, 4);
    }

    function puc_request_info_query_args( $query_args ) {
        $query_args['license_key'] = $this->license_data->license_key;
        $query_args['slug'] = $this->slug;

        return $query_args;
    }

    function in_plugin_update_message( $plugin_data, $response ) {
        if ( empty( $response->package ) ) {
            if ( $this->has_activated() ) {
                echo ' Renew your license to receive this and future updates.';
            } else {
                echo ' Enter your license key on the plugin page to receive updates.';
            }
        }
    }

    function plugin_action_links( $actions, $plugin_file, $plugin_data, $context ) {
        if ( $this->has_activated() && $this->is_expired() ) {
            $actions['renew_license'] = '<a href="' . $this->get_renew_url() . '" target="_blank" aria-label="Renew License"><span style="color: red;">License expired</span></a>';
        }

        return $actions;
    }

    function plugin_row_meta( $plugin_meta, $plugin_file, $plugin_data, $status ) {
        if ( $plugin_file == plugin_basename( $this->plugin_file ) ) {
            if ( true === $this->is_expired() ) {
                if ( $this->has_activated() ) {
                    $plugin_meta[] = 'To continue receiving updates you must <a href="' . $this->get_renew_url() . '" target="_blank" aria-label="Renew License"><span style="color: red;">renew your license.</span></a>';

                    $refreshUrl = wp_nonce_url(
                        add_query_arg(
                            array(
                                'puc_check_for_updates' => 1,
                                'puc_slug' => $this->slug,
                                'pw_refresh' => 'true',
                            ),
                            self_admin_url('plugins.php')
                        ),
                        'puc_check_for_updates'
                    );
                    $plugin_meta[] = 'Already renewed? <a href="' . $refreshUrl . '" aria-label="Refresh">Click here to refresh.</a>';
                }
            }
        }

        return $plugin_meta;
    }

    function has_activated() {
        if ( isset( $this->license_data->license_key ) && !empty( $this->license_data->license_key ) ) {
            return true;
        } else {
            return false;
        }
    }

    function is_premium() {
        if ( is_null( $this->premium ) ) {
            $this->premium = $this->validate_license();
        }

        return $this->premium;
    }

    function is_expired() {
        if ( !isset( $this->license_data->date_expiry ) || $this->license_data->date_expiry >= date( 'Y-m-d' ) ) {
            return false;
        } else {
            return true;
        }
    }

    function get_renew_url() {
        $this->get_license_data();
        return $this->updater_url . '?action=renew&license_key=' . $this->license_data->license_key;
    }

    function activate_license( $license_key ) {
        $this->premium = false;

        $result = $this->license_action( $license_key, 'slm_activate' );
        if ( false !== $result ) {
            $this->get_license_data( true, $license_key );
            if ( $this->validate_license() ) {
                $this->premium = true;
                return true;
            }
        } else {
            $this->error = 'An unknown error encountered while calling slm_activate.';
        }

        return false;
    }

    function deactivate_license() {
        if ( $this->license_action( $this->license_data->license_key, 'slm_deactivate' ) ) {
            $this->premium = false;
            $this->delete_license_data();
            return true;
        } else {
            return false;
        }
    }

    function validate_license() {
        $valid = false;

        $this->get_license_data();

        if ( isset( $this->license_data->result ) ) {
            if ( 'success' === $this->license_data->result ) {
                if ( property_exists( $this->license_data, 'status' ) ) {
                    if ( $this->license_data->status != 'expired' && $this->license_data->status != 'blocked' ) {
                        $valid = true;
                    } else {
                        $this->error = sprintf( 'License is %s', $this->license_data->status );
                    }
                }
            } else if ( false !== strpos( $this->license_data->message, 'License key already in use on' ) ) {
                $valid = true;
            } else {
                $this->error = 'Error: ' . $this->license_data->message;
            }
        }

        return $valid;
    }

    function get_license_data( $force_download = false, $license_key = '' ) {
        $this->license_data = get_option( $this->license_data_option_name, '' );

        if ( empty( $this->license_data ) || !isset( $this->license_data->license_key ) || empty( $this->license_data->license_key ) ) {
            $this->license_data = new stdClass();

            // Maybe retrieve the license key stored the old way?
            $this->license_data->license_key = get_option( $this->slug . '-license', '' );
            if ( empty( $this->license_data->license_key ) ) {
                // Some plugins used all underscores instead.
                $this->license_data->license_key = get_option( str_replace( '-', '_', $this->slug . '-license' ), '' );
                if ( empty( $this->license_data->license_key ) ) {
                    // Stragglers...
                    if ( $this->slug == 'pw-woocommerce-bogo-free' ) {
                        $this->license_data->license_key = get_option( 'pw-bogo-license', '' );
                    }
                }
            }
        }

        if ( !empty( $license_key ) ) {
            $this->license_data->license_key = $license_key;
        }

        if ( $force_download || !isset( $this->license_data->cached_on ) || $this->license_data->cached_on != date( 'Y-m-d' ) || isset( $_REQUEST['pw_refresh'] ) ) {
            if ( !empty( $this->license_data->license_key ) ) {
                $license_data = $this->license_action( $this->license_data->license_key, 'slm_check' );
                if ( false !== $license_data && !empty( $license_data ) ) {
                    if ( !isset( $license_data->license_key ) || empty( $license_data->license_key ) ) {
                        $license_data->license_key = $this->license_data->license_key;
                    }
                    if ( isset( $license_data->license_key ) && !empty( $license_data->license_key ) ) {
                        $this->license_data = $license_data;
                    }

                    $this->save_license_data();

                // If there was a problem retrieving the license data (but we have before since it has a cached_on value) then we'll assume a temporary
                // unreachable server. Therefore we won't panic, we just won't update the cached_on date and proceed with the cached data.
                } else if ( !isset( $this->license_data->cached_on ) || empty( $this->license_data->cached_on ) ) {
                    $this->error = 'Error: License action slm_check failed.';
                    return;
                }
            }
        }
    }

    function save_license_data() {
        if ( isset( $this->license_data->license_key ) ) {
            $this->license_data->cached_on = date( 'Y-m-d' );
            update_option( $this->license_data_option_name, $this->license_data, true );
        }
    }

    function delete_license_data() {
        delete_option( $this->license_data_option_name );
        unset( $this->license_data );
    }

    function license_action( $license_key, $action ) {
        if ( empty( $license_key ) || empty( $action ) ) {
            return false;
        }

        $this->error = '';

        $api_params = array(
            'slm_action' => $action,
            'secret_key' => $this->license_secret,
            'license_key' => $license_key,
            'registered_domain' => $_SERVER['SERVER_NAME'],
            'item_reference' => urlencode( $this->license_product ),
        );

        $query = esc_url_raw( add_query_arg( $api_params, $this->license_url ) );
        $response = wp_remote_get( $query, array( 'timeout' => 240 ) );

        if ( !is_wp_error( $response ) ) {
            $license_data = json_decode( wp_remote_retrieve_body( $response ) );
            if ( !is_null( $license_data ) && !empty( $license_data ) ) {
                return $license_data;
            }
        } else {
            $error_message = $response->get_error_message();
            if ( false !== stripos( $error_message, 'curl error 28: connection timed out after' ) ) {
                $this->error = 'Connection to pimwick.com timed out. Please try your request again. If you continue seeing this message please email us@pimwick.com';
            } else {
                $this->error = "Error while validating license: $error_message";
            }
        }

        return false;
    }
}

endif;

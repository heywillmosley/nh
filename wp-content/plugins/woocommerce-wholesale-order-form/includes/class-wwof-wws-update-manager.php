<?php
if ( !defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Model that houses the logic of updating the plugin.
 *
 * @since 1.11
 */
class WWOF_WWS_Update_Manager {

    /*
    |--------------------------------------------------------------------------
    | Class Properties
    |--------------------------------------------------------------------------
    */

    /**
     * Property that holds the single main instance of WWOF_WWS_Update_Manager.
     *
     * @since 1.11
     * @access private
     * @var WWOF_WWS_Update_Manager
     */
    private static $_instance;




    /*
    |--------------------------------------------------------------------------
    | Class Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Class constructor.
     *
     * @since 1.11
     * @access public
     */
    public function __construct() {}

    /**
     * Ensure that only one instance of this class is loaded or can be loaded ( Singleton Pattern ).
     *
     * @since 1.11
     * @access public
     *
     * @return WWOF_WWS_Update_Manager
     */
    public static function instance() {

      if ( !self::$_instance instanceof self )
        self::$_instance = new self();

      return self::$_instance;

    }

    /**
     * Hijack the WordPress 'set_site_transient' function for 'update_plugins' transient.
     * So now we don't have our own cron to check for updates, we just rely on when WordPress check updates for plugins and themes,
     * and if WordPress does then sets the 'update_plugins' transient, then we hijack it and check for our own plugin update.
     *
     * @since 1.11
     * @access public
     *
     * @param array $update_plugins Update plugins data.
     */
    public function update_check( $update_plugins ) {

      /**
       * Function wp_update_plugins calls set_site_transient( 'update_plugins' , ... ) twice, yes twice
       * so we need to make sure we are on the last call before checking plugin updates
       * the last call will have the checked object parameter
       */
      if ( isset( $update_plugins->checked ) )
        $this->ping_for_new_version(); // Check plugin for updates

      /**
       * We try to inject plugin update data if it has any
       * This is to fix the issue about plugin info appearing/disappearing
       * when update page in WordPress is refreshed
       */
      $result = $this->inject_plugin_update(); // Inject new update data if there are any

      if ( $result && isset( $update_plugins->response ) && is_array( $update_plugins->response ) && !array_key_exists( $result[ 'key' ] , $update_plugins->response ) )
        $update_plugins->response[ $result[ 'key' ] ] = $result[ 'value' ];

      return $update_plugins;

    }

    /**
     * Ping plugin for new version. Ping static file first, if indeed new version is available, trigger update data request.
     *
     * @since 1.11
     * @access public
     */
    public function ping_for_new_version() {

      $license_activated = is_multisite() ? get_site_option( WWOF_LICENSE_ACTIVATED ) : get_option( WWOF_LICENSE_ACTIVATED );

      if ( $license_activated !== 'yes' ) {
        
        if ( is_multisite() )
          delete_site_option( WWOF_UPDATE_DATA );
        else
          delete_option( WWOF_UPDATE_DATA );

        return;

      }

      $retrieving_udpate_data = is_multisite() ? get_site_option( WWOF_RETRIEVING_UPDATE_DATA ) : get_option( WWOF_RETRIEVING_UPDATE_DATA );
      if ( $retrieving_udpate_data === 'yes' )
        return;

      $update_data = is_multisite() ? get_site_option( WWOF_UPDATE_DATA ) : get_option( WWOF_UPDATE_DATA );

      if ( $update_data ) {

        if ( isset( $update_data->download_url ) ) {

          $file_headers = @get_headers( $update_data->download_url );

          if ( strpos( $file_headers[ 0 ] , '404' ) !== false ) {

            // For some reason the update url is not valid anymore, delete the update data.
            if ( is_multisite() )
              delete_site_option( WWOF_UPDATE_DATA );
            else
              delete_option( WWOF_UPDATE_DATA );
            
            $update_data = null;

          }

        } else {

          // For some reason the update url is not valid anymore, delete the update data.
          if ( is_multisite() )
            delete_site_option( WWOF_UPDATE_DATA );
          else
            delete_option( WWOF_UPDATE_DATA );

          $update_data = null;

        }

      }

      /**
       * Even if the update data is still valid, we still go ahead and do static json file ping.
       * The reason is on WooCommerce 3.3.x , it seems WooCommerce do not regenerate the download url every time you change the downloadable zip file on WooCommerce store.
       * The side effect is, the download url is still valid, points to the latest zip file, but the update info could be outdated coz we check that if the download url
       * is still valid, we don't check for update info, and since the download url will always be valid even after subsequent release of the plugin coz WooCommerce is reusing the url now
       * then there will be a case our update info is outdated. So that is why we still need to check the static json file, even if update info is still valid.
       */

      $option = array(
        'timeout' => 10 , //seconds coz only static json file ping
        'headers' => array( 'Accept' => 'application/json' )
      );

      $response = wp_remote_retrieve_body( wp_remote_get( apply_filters( 'wwof_plugin_new_version_ping_url' , WWOF_STATIC_PING_FILE ) , $option ) );

      if ( !empty( $response ) ) {

        $response = json_decode( $response );

        if ( !empty( $response ) && property_exists( $response , 'version' ) ) {

				  $installed_version = is_multisite() ? get_site_option( WWOF_OPTION_INSTALLED_VERSION ) : get_option( WWOF_OPTION_INSTALLED_VERSION );

				  if ( ( !$update_data && version_compare( $response->version , $installed_version , '>' ) ) ||
				       ( $update_data && version_compare( $response->version , $update_data->latest_version , '>' ) ) ) {
            
            if ( is_multisite() )
              update_site_option( WWOF_RETRIEVING_UPDATE_DATA , 'yes' );
            else
              update_option( WWOF_RETRIEVING_UPDATE_DATA , 'yes' );

            // Fetch software product update data
            if ( is_multisite() )
              $this->_fetch_software_product_update_data( get_site_option( WWOF_OPTION_LICENSE_EMAIL ) , get_site_option( WWOF_OPTION_LICENSE_KEY ) , home_url() );
            else
              $this->_fetch_software_product_update_data( get_option( WWOF_OPTION_LICENSE_EMAIL ) , get_option( WWOF_OPTION_LICENSE_KEY ) , home_url() );
              
            if ( is_multisite() )
              delete_site_option( WWOF_RETRIEVING_UPDATE_DATA );
            else
              delete_option( WWOF_RETRIEVING_UPDATE_DATA );

          } elseif ( $update_data && version_compare( $update_data->latest_version , $installed_version , '==' ) ) {

            /**
             * We delete the option data if update is already installed
             * We encountered a bug when updating the plugin via the dashboard updates page
             * The update is successful but the update notice does not disappear
             */ 
            if ( is_multisite() )
              delete_site_option( WWOF_UPDATE_DATA );
            else
              delete_option( WWOF_UPDATE_DATA );

          }

        }

      }

    }

    /**
     * Fetch software product update data.
     *
     * @since 1.11
     * @access public
     *
     * @param string $activation_email Activation email.
     * @param string $license_key      License key.
     * @param string $site_url         Site url.
     */
    private function _fetch_software_product_update_data( $activation_email , $license_key , $site_url ) {

      $update_check_url = add_query_arg( array(
        'activation_email' => urlencode( $activation_email ),
        'license_key'      => $license_key,
        'site_url'         => $site_url,
        'software_key'     => 'WWOF',
        'multisite'        => is_multisite() ? 1 : 0
      ) , apply_filters( 'wwof_software_product_update_data_url' , WWOF_UPDATE_DATA_URL ) );

      $option = array(
        'timeout' => 30 , // seconds for worst case the server is choked and takes little longer to get update data ( this is an ajax end point )
        'headers' => array( 'Accept' => 'application/json' )
      );

      $result = wp_remote_retrieve_body( wp_remote_get( $update_check_url , $option ) );

      if ( !empty( $result ) ) {

        $result = json_decode( $result );

        if ( !empty( $result ) && $result->status === 'success' && !empty( $result->software_update_data ) ) {

          if ( is_multisite() )
            update_site_option( WWOF_UPDATE_DATA , $result->software_update_data );
          else
            update_option( WWOF_UPDATE_DATA , $result->software_update_data );

        } else {

          if ( is_multisite() )
            delete_site_option( WWOF_UPDATE_DATA );
          else
            delete_option( WWOF_UPDATE_DATA );

          if ( !empty( $result ) && $result->status === 'fail' &&
               isset( $result->error_key ) &&
               in_array( $result->error_key , array( 'invalid_license' , 'expired_license' ) ) ) {

            // Invalid license
            if ( is_multisite() )
              update_site_option( WWOF_LICENSE_ACTIVATED , 'no' );
            else
              update_option( WWOF_LICENSE_ACTIVATED , 'no' );

            // Check if license is expired
            if ( $result->error_key === 'expired_license' ) {

              if ( is_multisite() )
                update_site_option( WWOF_LICENSE_EXPIRED , $result->expiration_timestamp );
              else
                update_option( WWOF_LICENSE_EXPIRED , $result->expiration_timestamp );

            } else {

              if ( is_multisite() )
                delete_site_option( WWOF_LICENSE_EXPIRED );
              else
                delete_option( WWOF_LICENSE_EXPIRED );

            }

          }

        }

      }

    }

    /**
     * Inject plugin update info to plugin update details page.
     * Note, this is only triggered when there is a new update and the "View version <new version here> details" link is clicked.
     * In short, the pure purpose for this is to provide details and info the update info popup.
     *
     * @since 1.11
     * @access public
     *
     * @param false|object|array $result The result object or array. Default false.
     * @param string             $action The type of information being requested from the Plugin Install API.
     * @param object             $args   Plugin API arguments.
     * @return array Plugin update info.
     */
    public function inject_plugin_update_info( $result , $action , $args ) {

      $license_activated = is_multisite() ? get_site_option( WWOF_LICENSE_ACTIVATED ) : get_option( WWOF_LICENSE_ACTIVATED );

      if ( $license_activated === 'yes' && $action == 'plugin_information' && isset( $args->slug ) && $args->slug == 'woocommerce-wholesale-order-form' ) {

        $software_update_data = is_multisite() ? get_site_option( WWOF_UPDATE_DATA ) : get_option( WWOF_UPDATE_DATA );

        if ( $software_update_data ) {

          $update_info = new \StdClass;

          $update_info->name          = 'WooCommerce Wholesale Order Form';
          $update_info->slug          = 'woocommerce-wholesale-order-form';
          $update_info->version       = $software_update_data->latest_version;
          $update_info->tested        = $software_update_data->tested_up_to;
          $update_info->last_updated  = $software_update_data->last_updated;
          $update_info->homepage      = $software_update_data->home_page;
          $update_info->author        = sprintf( '<a href="%s" target="_blank">%s</a>' , $software_update_data->author_url , $software_update_data->author );
          $update_info->download_link = $software_update_data->download_url;
          $update_info->sections = array(
            'description'  => $software_update_data->description,
            'installation' => $software_update_data->installation,
            'changelog'    => $software_update_data->changelog,
            'support'      => $software_update_data->support
          );

          $update_info->icons = array(
            '1x'      => 'https://ps.w.org/woocommerce-wholesale-prices/assets/wwof-icon-128x128.jpg',
            '2x'      => 'https://ps.w.org/woocommerce-wholesale-prices/assets/wwof-icon-256x256.jpg',
            'default' => 'https://ps.w.org/woocommerce-wholesale-prices/assets/wwof-icon-256x256.jpg'
          );

          $update_info->banners = array(
            'low'  => 'https://ps.w.org/woocommerce-wholesale-prices/assets/wwof-banner-772x250.jpg',
            'high' => 'https://ps.w.org/woocommerce-wholesale-prices/assets/wwof-banner-1544x500.jpg',
          );

          return $update_info;

        }

      }

      return $result;

    }

    /**
     * When wordpress fetch 'update_plugins' transient ( Which holds various data regarding plugins, including which have updates ),
     * we inject our plugin update data in, if any. It is saved on WWOF_UPDATE_DATA option.
     * It is important we dont delete this option until the plugin have successfully updated.
     * The reason is we are hooking ( and we should do it this way ), on transient read.
     * So if we delete this option on first transient read, then subsequent read will not include our plugin update data.
     *
     * It also checks the validity of the update url. There could be edge case where we stored the update data locally as an option,
     * then later on the store, the product was deleted or any action occurred that would deem the update data invalid.
     * So we check if update url is still valid, if not, we remove the locally stored update data.
     *
     * @since 1.11
     * Refactor codebase to adapt being called on set_site_transient function.
     * We don't need to check for software update data validity as its already been checked on ping_for_new_version
     * and this function is immediately called right after that.
     * @access public
     *
     * @param array $updates Plugin updates data.
     * @return array Filtered plugin updates data.
     */
    public function inject_plugin_update() {

      $license_activated = is_multisite() ? get_site_option( WWOF_LICENSE_ACTIVATED ) : get_option( WWOF_LICENSE_ACTIVATED );
      if ( $license_activated !== 'yes' )
        return false;

      $software_update_data = is_multisite() ? get_site_option( WWOF_UPDATE_DATA ) : get_option( WWOF_UPDATE_DATA );

      if ( $software_update_data ) {

        $update = new \stdClass();

        $update->id          = $software_update_data->download_id;
        $update->slug        = 'woocommerce-wholesale-order-form';
        $update->plugin      = WWOF_PLUGIN_BASE_NAME;
        $update->new_version = $software_update_data->latest_version;
        $update->url         = WWOF_PLUGIN_SITE_URL;
        $update->package     = $software_update_data->download_url;
        $update->tested      = $software_update_data->tested_up_to;

        $update->icons = array(
          '1x'      => 'https://ps.w.org/woocommerce-wholesale-prices/assets/wwof-icon-128x128.jpg',
          '2x'      => 'https://ps.w.org/woocommerce-wholesale-prices/assets/wwof-icon-256x256.jpg',
          'default' => 'https://ps.w.org/woocommerce-wholesale-prices/assets/wwof-icon-256x256.jpg'
        );

        $update->banners = array(
          '1x'      => 'https://ps.w.org/woocommerce-wholesale-prices/assets/wwof-banner-772x250.jpg',
          '2x'      => 'https://ps.w.org/woocommerce-wholesale-prices/assets/wwof-banner-1544x500.jpg',
          'default' => 'https://ps.w.org/woocommerce-wholesale-prices/assets/wwof-banner-1544x500.jpg'
        );

        return array(
          'key'   => WWOF_PLUGIN_BASE_NAME,
          'value' => $update
        );

      }

      return false;

    }

    /**
     * Delete the plugin update data after the plugin successfully updated.
     *
     * References:
     * https://stackoverflow.com/questions/24187990/plugin-update-hook
     * https://codex.wordpress.org/Plugin_API/Action_Reference/upgrader_process_complete
     *
     * @since 1.11
     * @access public
     *
     * @param Plugin_Upgrader $upgrader_object Plugin_Upgrader instance.
     * @param array           $options         Options.
     */
    public function after_plugin_update( $upgrader_object , $options ) {

      if ( $options[ 'action' ] == 'update' && $options[ 'type' ] == 'plugin' && isset( $options[ 'plugins' ] ) && is_array( $options[ 'plugins' ] ) ) {

        foreach ( $options[ 'plugins' ] as $each_plugin ) {

          if ( $each_plugin == WWOF_PLUGIN_BASE_NAME ) {

            if ( is_multisite() )
              delete_site_option( WWOF_UPDATE_DATA );
            else
              delete_option( WWOF_UPDATE_DATA );

            break;

          }

        }

      }

    }




    /*
    |--------------------------------------------------------------------------
    | Execute update manager
    |--------------------------------------------------------------------------
    */

    /**
     * Execute Model.
     *
     * @since 1.11
     * @access public
     */
    public function run() {

      add_filter( 'pre_set_site_transient_update_plugins' , array( $this , 'update_check' ) );
      add_filter( 'plugins_api'                           , array( $this , 'inject_plugin_update_info' ) , 10 , 3);
      add_action( 'upgrader_process_complete'             , array( $this , 'after_plugin_update' ) , 10 , 2 );

    }

}

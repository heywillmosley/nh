<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly

if ( !class_exists( 'WWS_WWOF_Plugin_Update_Checker' ) ) {

    /**
     * A custom plugin update checker.
     *
     * @author Janis Elsts (Modifications by Josh Kohlbach & Jomar)
     * @copyright 2010
     * @version 1.0
     * @access public
     * @since 1.0.1
     */
    class WWS_WWOF_Plugin_Update_Checker {

        public $metadataUrl = ''; // The URL of the plugin's metadata file.
        public $pluginFile = '';  // Plugin filename relative to the plugins directory.
        public $slug = '';        // Plugin slug.
        public $checkPeriod = 12; // How often to check for updates (in hours).
        public $optionName = '';  // Where to store the update info.

        // WWS plugin specific stuff
        public $productID = '';
        public $serverKey = '';
        public $licenceKey = '';

        private $cronHook = null;

        /**
         * Class constructor.
         *
         * @param string $metadataUrl The URL of the plugin's metadata file.
         * @param string $pluginFile Fully qualified path to the main plugin file.
         * @param string $slug The plugin's 'slug'. If not specified, the filename part of $pluginFile sans '.php' will be used as the slug.
         * @param integer $checkPeriod How often to check for updates (in hours). Defaults to checking every 12 hours. Set to 0 to disable automatic update checks.
         * @param string $optionName Where to store book-keeping info about update checks. Defaults to 'external_updates-$slug'.
         * @return void
         */
        function __construct($metadataUrl, $pluginFile, $slug = '', $checkPeriod = 12, $optionName = '') {

            $this->metadataUrl = $metadataUrl;
            $this->pluginFile = plugin_basename($pluginFile);
            $this->checkPeriod = $checkPeriod;
            $this->slug = $slug;
            $this->optionName = $optionName;

            // If no slug is specified, use the name of the main plugin file as the slug.
            // For example, 'my-cool-plugin/cool-plugin.php' becomes 'cool-plugin'.
            if ( empty( $this->slug ) )
                $this->slug = basename($this->pluginFile, '.php');

            if ( empty( $this->optionName ) )
                $this->optionName = 'external_updates-' . $this->slug;

            $this->installHooks();

        }

        /**
         * Install the hooks required to run periodic update checks and inject update info
         * into WP data structures.
         *
         * @return void
         */
        function installHooks() {

			add_filter( 'pre_set_site_transient_update_plugins' , array( $this , 'update_check' ) );
			add_filter( 'plugins_api' 							, array( $this , 'inject_info' ) , 10 , 3 );
			add_action( 'upgrader_process_complete'             , array( $this , 'after_plugin_update' ) , 10 , 2 );

			register_deactivation_hook( $this->pluginFile , array( $this , 'clear_plugin_update_data' ) );

        }

		/**
		 * Check for updates every time WordPress is about to save update_plugins transient data.
		 *
		 * @since 1.8.2
		 * @access public
		 *
		 * @param array $update_plugins Update plugins data.
		 * @return array Filtered update plugins data.
		 */
		public function update_check( $update_plugins ) {

			/**
			 * Function wp_update_plugins calls set_site_transient( 'update_plugins' , ... ) twice, yes twice
			 * so we need to make sure we are on the last call before proceeding
			 * the last call will have the checked object parameter
			 */
			if ( empty( $update_plugins->checked ) )
				return $update_plugins;

			$this->checkForUpdates();

			$update_plugins = $this->injectUpdate( $update_plugins , $this->pluginFile );

			return $update_plugins;

		}

        /**
         * Intercept plugins_api() calls that request information about our plugin and
         * use the configured API endpoint to satisfy them.
         *
		 * @since 1.0.0
		 * @since 1.8.2 Major code refactoring, now use the locally saved update data instead of spawning a new request from WWS server.
         * @access public
         *
		 * @param false|object|array $result The result object or array. Default false.
		 * @param string             $action The type of information being requested from the Plugin Install API.
		 * @param object             $args   Plugin API arguments.
		 * @return array Plugin update info.
         */
        function inject_info( $result , $action = null , $args = null ) {

            $relevant = ( $action == 'plugin_information' ) && isset( $args->slug ) && ( $args->slug == $this->slug );
            if ( !$relevant )
                return $result;

            $update_info = get_option( 'wwof_update_info' );

			if ( $update_info ) {

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

			return $result;

        }

		/**
		 * After plugin update callback.
		 *
		 * @since 1.8.2
		 * @access public
		 *
		 * @param Plugin_Upgrader $upgrader_object Plugin_Upgrader instance.
		 * @param array           $options         Options.
		 */
		public function after_plugin_update( $upgrader_object , $options ) {

			if ( $options[ 'action' ] == 'update' && $options[ 'type' ] == 'plugin' ) {

				foreach ( $options[ 'plugins' ] as $each_plugin ) {

					if ( $each_plugin == $this->pluginFile ) {

						$this->clear_plugin_update_data();
						break;

					}

				}

			}

		}

		/**
		 * Clear plugin update data.
		 *
		 * @since 1.8.2
		 */
		public function clear_plugin_update_data() {

			delete_option( 'wwof_update_info' );

			wp_clear_scheduled_hook( $this->cronHook );

		}

        /**
         * Retrieve plugin info from the configured API endpoint.
         *
         * @uses wp_remote_get()
         *
         * @param array $queryArgs Additional query arguments to append to the request. Optional.
         * @return WWS_Plugin_Info
         */
        function requestInfo($queryArgs = array()) {

            $queryArgs['installed_version'] = $this->getInstalledVersion();
            $queryArgs = apply_filters('puc_request_info_query_args-'.$this->slug, $queryArgs);

            //Various options for the wp_remote_get() call. Plugins can filter these, too.
            $options = array(
                'timeout' => 10, //seconds
                'headers' => array(
                    'Accept' => 'application/json'
                ),
            );
            $options = apply_filters('puc_request_info_options-'.$this->slug, array());

            //The plugin info should be at 'http://your-api.com/url/here/$slug/info.json'
            $url = $this->metadataUrl;
            if ( !empty($queryArgs) ) {
                $url = add_query_arg($queryArgs, $url);
            }

            $result = wp_remote_get(
                $url,
                $options
            );

            //Try to parse the response
            $WWS_Plugin_Info = null;
            if (!is_wp_error($result) &&
                isset($result['response']['code']) &&
                ($result['response']['code'] == 200) &&
                !empty($result['body'])) {
                $WWS_Plugin_Info = WWS_Plugin_Info::fromJson($result['body']);
            }
            $WWS_Plugin_Info = apply_filters('puc_request_info_result-'.$this->slug, $WWS_Plugin_Info, $result);
            return $WWS_Plugin_Info;
        }

        /**
         * Retrieve the latest update (if any) from the configured API endpoint.
         *
         * @since 1.0.0
         * @since 1.8.2 Store update data locally for later use
         * @uses WWS_Plugin_UpdateChecker::requestInfo()
         *
         * @return WWS_Plugin_Update An instance of WWS_Plugin_Update, or NULL when no updates are available.
         */
        function requestUpdate() {

            //For the sake of simplicity, this function just calls requestInfo()
            //and transforms the result accordingly.
            $WWS_Plugin_Info = $this->requestInfo(array('checking_for_updates' => '1'));
            if ( $WWS_Plugin_Info == null )
                return null;

			// Store update info locally for later use
			$update_info = $WWS_Plugin_Info->toWpFormat();
			update_option( 'wwof_update_info' , $update_info );

            return WWS_Plugin_Update::fromWWS_Plugin_Info( $WWS_Plugin_Info );

        }

        /**
         * Get the currently installed version of the plugin.
         *
         * @return string Version number.
         */
        function getInstalledVersion() {
            if ( !function_exists('get_plugins') ){
                require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
            }
            $allPlugins = get_plugins();
            if ( array_key_exists($this->pluginFile, $allPlugins) && array_key_exists('Version', $allPlugins[$this->pluginFile]) ){
                return $allPlugins[$this->pluginFile]['Version'];
            } else {
                return ''; //This should never happen.
            };
        }

        /**
         * Check for plugin updates.
         * The results are stored in the DB option specified in $optionName.
         *
         * @return void
         */
        function checkForUpdates() {
            $state = get_option($this->optionName);
            if ( empty($state) ){
                $state = new StdClass;
                $state->lastCheck = 0;
                $state->checkedVersion = '';
                $state->update = null;
            }

            $state->lastCheck = time();
            $state->checkedVersion = $this->getInstalledVersion();
            update_option($this->optionName, $state); // Save before checking in case something goes wrong

            $state->update = $this->requestUpdate();
            update_option($this->optionName, $state);
        }

        /**
         * Insert the latest update (if any) into the update list maintained by WP.
         *
		 * @since 1.0.0
		 * @since 1.8.2 Major code refactoring, now adjust codebase for usage on WordPress setting update_plugins transients.
         * @access public
         *
         * @param array  $updates         Update list.
         * @param string $plugin_basename Plugin basename.
         * @return array Modified update list.
         */
        function injectUpdate( $updates , $plugin_basename ) {

            $state = get_option( $this->optionName );

            //Is there an update to insert?
            if ( !empty( $state ) && isset( $state->update ) && !empty( $state->update ) && !array_key_exists( $this->pluginFile , $updates->response ) ) {

                //Only insert updates that are actually newer than the currently installed version.
                if ( version_compare( $state->update->version , $this->getInstalledVersion() , '>' ) ) {

					$update_data = $state->update->toWpFormat();

                    $update_data->plugin = $plugin_basename;

					$update_data->icons = array(
						'1x'      => 'https://ps.w.org/woocommerce-wholesale-prices/assets/wwof-icon-128x128.jpg',
						'2x'      => 'https://ps.w.org/woocommerce-wholesale-prices/assets/wwof-icon-256x256.jpg',
						'default' => 'https://ps.w.org/woocommerce-wholesale-prices/assets/wwof-icon-256x256.jpg'
					);

					$update_data->banners = array(
						'1x'      => 'https://ps.w.org/woocommerce-wholesale-prices/assets/wwof-banner-772x250.jpg',
						'2x'      => 'https://ps.w.org/woocommerce-wholesale-prices/assets/wwof-banner-1544x500.jpg',
						'default' => 'https://ps.w.org/woocommerce-wholesale-prices/assets/wwof-banner-1544x500.jpg'
					);

                    $updates->response[ $this->pluginFile ] = $update_data;

                }

            }

            return $updates;

        }

    }

}

if ( !class_exists( 'WWS_Plugin_Info' ) ) {

    /**
     * A container class for holding and transforming various plugin metadata.
     *
     * @author Janis Elsts
     * @copyright 2010
     * @version 1.0
     * @access public
     * @since 1.0.1
     */
    class WWS_Plugin_Info {

        //Most fields map directly to the contents of the plugin's info.json file.
        //See the relevant docs for a description of their meaning.
        public $name;
        public $slug;
        public $version;
        public $homepage;
		public $sections;
        public $download_url;

        public $author;
        public $author_homepage;

        public $requires;
        public $tested;
        public $upgrade_notice;

        public $rating;
        public $num_ratings;
        public $downloaded;
        public $last_updated;

        public $id = 0; //The native WP.org API returns numeric plugin IDs, but they're not used for anything.

        /**
         * Create a new instance of WWS_Plugin_Info from JSON-encoded plugin info
         * returned by an external update API.
         *
         * @param string $json Valid JSON string representing plugin info.
         * @return WWS_Plugin_Info New instance of WWS_Plugin_Info, or NULL on error.
         */
        public static function fromJson($json) {
            $apiResponse = json_decode($json);
            if (empty($apiResponse) || !is_object($apiResponse)) {
                return null;
            }

            //Very, very basic validation.
            $valid = isset($apiResponse->name) && !empty($apiResponse->name) && isset($apiResponse->version) && !empty($apiResponse->version);
            if (!$valid) {
                return null;
            }

            $info = new WWS_Plugin_Info();
            foreach (get_object_vars($apiResponse) as $key => $value) {
                $info->$key = $value;
            }

            return $info;
        }

        /**
         * Transform plugin info into the format used by the native WordPress.org API
         *
         * @return object
         */
        public function toWpFormat() {
            $info = new StdClass;

            //The custom update API is built so that many fields have the same name and format
            //as those returned by the native WordPress.org API. These can be assigned directly.
            $sameFormat = array(
                'name', 'slug', 'version', 'requires', 'tested', 'rating', 'upgrade_notice',
                'num_ratings', 'downloaded', 'homepage', 'last_updated',
            );
            foreach ($sameFormat as $field) {
                if (isset($this->$field)) {
                    $info->$field = $this->$field;
                }
            }

            //Other fields need to be renamed and/or transformed.
            $info->download_link = $this->download_url;

            if (!empty($this->author_homepage)) {
                $info->author = sprintf('<a href="%s">%s</a>', $this->author_homepage, $this->author);
            } else {
                $info->author = $this->author;
            }

            if (is_object($this->sections)) {
                $info->sections = get_object_vars($this->sections);
            } elseif (is_array($this->sections)) {
                $info->sections = $this->sections;
            } else {
                $info->sections = array('description' => '');
            }

            return $info;
        }

    }

}

if ( !class_exists( 'WWS_Plugin_Update' ) ) {

    /**
     * A simple container class for holding information about an available update.
     *
     * @author Janis Elsts
     * @copyright 2010
     * @version 1.0
     * @access public
     * @since 1.0.1
     */
    class WWS_Plugin_Update {

		public $id = 0;
        public $slug;
        public $version;
        public $homepage;
        public $download_url;
        public $upgrade_notice;

        /**
         * Create a new instance of WWS_Plugin_Update from its JSON-encoded representation.
         *
         * @param string $json
         * @return WWS_Plugin_Update
         */
        public static function fromJson($json) {
            //Since update-related information is simply a subset of the full plugin info,
            //we can parse the update JSON as if it was a plugin info string, then copy over
            //the parts that we care about.
            $WWS_Plugin_Info = WWS_Plugin_Info::fromJson($json);
            if ($WWS_Plugin_Info != null) {
                return WWS_Plugin_Update::fromWWS_Plugin_Info($WWS_Plugin_Info);
            } else {
                return null;
            }
        }

        /**
         * Create a new instance of WWS_Plugin_Update based on an instance of WWS_Plugin_Info.
         * Basically, this just copies a subset of fields from one object to another.
         *
         * @param WWS_Plugin_Info $info
         * @return WWS_Plugin_Update
         */
        public static function fromWWS_Plugin_Info($info) {
            $update = new WWS_Plugin_Update();
            $copyFields = array( 'id' , 'slug' , 'version' , 'homepage' , 'download_url' , 'upgrade_notice' );
            foreach ($copyFields as $field) {
                $update->$field = $info->$field;
            }
            return $update;
        }

        /**
         * Transform the update into the format used by WordPress native plugin API.
         *
         * @return object
         */
        public function toWpFormat() {

            $update = new StdClass;

            $update->id = $this->download_url; // As long as it is unique we are good
            $update->slug = $this->slug;
            $update->new_version = $this->version;
            $update->url = $this->homepage;
            $update->package = $this->download_url;

            return $update;
        }

    }

}

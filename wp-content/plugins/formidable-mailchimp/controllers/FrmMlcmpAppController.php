<?php

class FrmMlcmpAppController {

	private static $entry;
	private static $action;

	public static function load_lang() {
		load_plugin_textdomain( 'frmmlcmp', false, FrmMlcmpAppHelper::plugin_folder() . '/languages/' );
	}

	/**
	 * Print a notice if Formidable is too old to be compatible with the MailChimp add-on
	 */
	public static function min_version_notice() {
		if ( FrmMlcmpAppHelper::is_formidable_compatible() ) {
			return;
		}

		$wp_list_table = _get_list_table( 'WP_Plugins_List_Table' );
		echo '<tr class="plugin-update-tr active"><th colspan="' . $wp_list_table->get_column_count() . '" class="check-column plugin-update colspanchange"><div class="update-message">' .
		     __( 'You are running an outdated version of Formidable. This plugin may not work correctly if you do not update Formidable.', 'frmmlcmp' ) .
		     '</div></td></tr>';
	}

	/**
	 * Adds the updater
	 * Called by the admin_init hook
	 */
	public static function include_updater() {
		if ( class_exists( 'FrmAddon' ) ) {
			FrmMlcmpUpdate::load_hooks();
		}
	}

	/**
	 * Initialize DB if needed
	 * Migrate settings if needed
	 *
	 * @since 2.02
	 */
	public static function initialize() {
		if ( ! FrmMlcmpAppHelper::is_formidable_compatible() ) {
			return;
		}

		$mailchimp_db = new FrmMlcmpDb();
		if ( $mailchimp_db->need_to_migrate_settings() ) {
			$mailchimp_db->migrate();
		}
	}

	/**
	 * Display admin notices if Formidable is too old or MailChimp settings need to be migrated
	 *
	 * @since 2.0
	 */
	public static function display_admin_notices() {
		// Don't display notices as we're upgrading
		$action = isset( $_GET['action'] ) ? sanitize_text_field( $_GET['action'] ) : '';
		if ( $action == 'upgrade-plugin' && ! isset( $_GET['activate'] ) ) {
			return;
		}

		// Show message if Formidable is not compatible
		if ( ! FrmMlcmpAppHelper::is_formidable_compatible() ) {
			include( FrmMlcmpAppHelper::plugin_path() . '/views/notices/update_formidable.php' );

			return;
		}

		self::add_update_database_link();
	}

	/**
	 * Add link to update database
	 *
	 * @since 2.0
	 */
	private static function add_update_database_link() {
		$mailchimp_db = new FrmMlcmpDb();
		if ( $mailchimp_db->need_to_migrate_settings() ) {
			if ( is_callable( 'FrmAppHelper::plugin_url' ) ) {
				$url = FrmAppHelper::plugin_url();
			} else if ( defined( 'FRM_URL' ) ) {
				$url = FRM_URL;
			} else {
				return;
			}

			include( FrmMlcmpAppHelper::plugin_path() . '/views/notices/update_database.php' );
		}
	}

	public static function trigger_mailchimp( $action, $entry, $form ) {
		$settings     = $action->post_content;
		self::$action = $action;
		self::$entry  = $entry;

		self::send_to_mailchimp( $entry, $form, $settings );
	}

	public static function send_to_mailchimp( $entry, $form, $settings ) {
		$vars = array();

		self::get_field_values_for_mailchimp( $entry, $settings, $vars );

		if ( ! isset( $vars['EMAIL'] ) || ! $vars['EMAIL'] ) {
			// Email address is not mapped or entered
			return;
		}

		$sending_data = array(
			'id'            => $settings['list_id'],
			'subscriber_id' => self::get_subscriber_id( $vars['EMAIL'] ),
			'email_address' => $vars['EMAIL'],
		);

		if ( ! empty( $vars['FRM_TAGS'] ) ) {
			// Package tags & co with $sending_data for easy retrieval later
			$sending_data['frm_tags']  = $vars['FRM_TAGS'];
			$sending_data['form']      = $form;
			$sending_data['entry']     = $entry;

			add_action( 'frm_mlcmp_api_request_completed', 'FrmMlcmpAppController::add_tags_to_subscriber', 10, 2 );
		}

		if ( isset( $vars['FRM_TAGS'] ) ) {
			// Remove it, whether empty or not, so long it's set. We don't want it in our API request data for adding a subscriber.
			unset( $vars['FRM_TAGS'] );
		}

		$action = isset( $settings['address_action'] ) ? $settings['address_action'] : 'subscribe';
		if ( $action === 'unsubscribe' ) {
			$sending_data['method']     = 'PATCH';
			$sending_data['status']     = 'unsubscribed';
		} else {
			$sending_data['method']        = 'PUT';
			$sending_data['status'] = self::get_status_for_new_subscribers( $settings );
			$sending_data['merge_fields']  = $vars;
			$sending_data['email_type']    = 'html';

			$interests = self::package_selected_group_interests( $entry, $settings );

			if ( ! empty( $interests ) ) {
				$sending_data['interests'] = $interests;
			}
		}

		self::send_now( $sending_data, $entry );
	}

	/**
	 * Determine the status that will be used for new subscribers
	 *
	 * @since 2.03
	 *
	 * @param array $settings
	 *
	 * @return string
	 */
	private static function get_status_for_new_subscribers( $settings ) {
		$double_optin = isset( $settings['optin'] ) ? $settings['optin'] : false;

		return $double_optin ? 'pending' : 'subscribed';
	}

	/**
	 * Get the subscriber ID if needed
	 *
	 * @since 2.0
	 *
	 * @param string $email
	 *
	 * @return string
	 */
	private static function get_subscriber_id( $email ) {
		$subscriber_id = strtolower( $email );

		return md5( $subscriber_id );
	}

	/**
	 * Package selected and non-selected groups to send to MailChimp
	 *
	 * @since 2.0
	 *
	 * @param object $entry
	 * @param array $settings
	 *
	 * @return array
	 */
	private static function package_selected_group_interests( $entry, $settings ) {
		$interests = array();

		if ( isset( $settings['groups'] ) ) {

			foreach ( $settings['groups'] as $group ) {
				$selected_grp = FrmMlcmpAppHelper::get_entry_or_post_value( $entry, $group['id'] );

				foreach ( (array) $group as $group_id => $value ) {
					if ( $group_id != 'id' && ! empty( $group_id ) && $value != '' ) {
						$selected               = array_search( $value, (array) $selected_grp );
						$interests[ $group_id ] = ( $selected !== false );
					}
				}
				unset( $group_id, $value );
			}
		}

		return $interests;
	}

	/**
	 * Get the field values to send to MailChimp
	 *
	 * @since 2.0
	 *
	 * @param object $entry
	 * @param array $settings
	 * @param array $vars
	 */
	private static function get_field_values_for_mailchimp( $entry, $settings, &$vars ) {
		$list_fields = self::get_list_fields( $settings['list_id'] );

		foreach ( $settings['fields'] as $field_tag => $field_id ) {
			if ( empty( $field_id ) ) {
				// don't sent an empty value
				continue;
			}

			if ( 'FRM_TAGS' === $field_tag ) {
				// These are shortcodes & text to be replaced later
				$vars[ $field_tag ] = $field_id;
				continue;
			}

			$vars[ $field_tag ] = self::get_field_value_for_mailchimp( $field_tag, $field_id, $entry, $list_fields );
		}
	}

	/**
	 * Get a single field value to send to MailChimp
	 *
	 * @since 2.0
	 *
	 * @param string $field_tag
	 * @param string $field_id
	 * @param object $entry
	 * @param array $list_fields
	 *
	 * @return string
	 */
	private static function get_field_value_for_mailchimp( $field_tag, $field_id, $entry, $list_fields ) {
		$field_value = FrmMlcmpAppHelper::get_entry_or_post_value( $entry, $field_id );

		if ( is_numeric( $field_value ) ) {
			$field = FrmField::getOne( $field_id );
			if ( $field->type == 'user_id' ) {
				$user_data = get_userdata( $field_value );
				if ( $field_tag == 'EMAIL' ) {
					$field_value = $user_data->user_email;
				} else if ( $field_tag == 'FNAME' ) {
					$field_value = $user_data->first_name;
				} else if ( $field_tag == 'LNAME' ) {
					$field_value = $user_data->last_name;
				} else {
					$field_value = $user_data->user_login;
				}
			} else if ( is_callable( 'FrmEntriesHelper::display_value' ) ) {
				$field_value = FrmEntriesHelper::display_value( $field_value, $field, array( 'type'     => $field->type,
				                                                                             'truncate' => false,
				                                                                             'entry_id' => $entry->id
				) );
			} else if ( is_callable( 'FrmProEntryMetaHelper::display_value' ) ) {
				$field_value = FrmProEntryMetaHelper::display_value( $field_value, $field, array( 'type'     => $field->type,
				                                                                                  'truncate' => false,
				                                                                                  'entry_id' => $entry->id
				) );
			}
		} else {

			if ( is_string( $field_value ) && preg_match( '/^\d{2}\/\d{2}\/\d{4}$/', trim( $field_value ) ) ) {
				if ( is_callable( 'FrmProAppHelper::convert_date' ) ) {
					global $frmpro_settings;
					$field_value = FrmProAppHelper::convert_date( $field_value, $frmpro_settings->date_format, 'Y-m-d' );
				}
			}

			$list_field = false;
			if ( isset( $list_fields['merge_fields'] ) ) {
				foreach ( $list_fields['merge_fields'] as $lf ) {
					if ( $lf['tag'] == $field_tag ) {
						$list_field = $lf;
						continue;
					}
					unset( $lf );
				}
			}

			self::format_birthday_merge_fields( $list_field, $field_value );
			self::format_address_merge_field( $list_field, $field_value );
		}

		if ( 'FRM_TAGS' !== $field_tag && is_array( $field_value ) && ! isset( $field_value['addr1'] ) ) {
			$field_value = implode( ', ', $field_value );
		}

		return $field_value;
	}

	/**
	 * If merge field is an "address" type, and a Formidable Address field is mapped to it,
	 * format the data as an array with the following keys:
	 * addr1, addr2, city, state, zip, country
	 *
	 * @since 2.02
	 *
	 * @param array|boolean $list_field
	 * @param array|string $field_value
	 */
	private static function format_address_merge_field( $list_field, &$field_value ) {
		if ( ! empty( $list_field ) && $list_field['type'] == 'address' && is_array( $field_value ) ) {

			$conversions = array(
				'line1' => 'addr1',
				'line2' => 'addr2',
			);

			$street_address = array();
			foreach ( $conversions as $frm_key => $mlcmp_key ) {
				if ( isset( $field_value[ $frm_key ] ) ) {
					$street_address[ $mlcmp_key ] = $field_value[ $frm_key ];
					unset( $field_value[ $frm_key ] );
				}
			}

			$field_value = $street_address + $field_value;
		}
	}

	/**
	 * If merge field is a "birthday" type, date value must be sent in the m/d format.
	 * Do NOT change the format for "Date" merge fields. Date must be sent in Y-m-d format.
	 *
	 * @since 2.01
	 *
	 * @param array $list_field
	 * @param string $field_value
	 */
	private static function format_birthday_merge_fields( $list_field, &$field_value ) {
		if ( ! empty( $list_field ) && $list_field['type'] == 'birthday' ) {
			$field_value = date( 'm/d', strtotime( $field_value ) );
		}
	}

	private static function send_now( $sending_data, $entry ) {
		$sending_data = apply_filters( 'frm_mlcmp_subscribe_data', $sending_data, $entry );

		// Allow the filter to stop submission
		if ( empty( $sending_data ) ) {
			return;
		}

		$subscribe = self::decode_call( '/lists/' . $sending_data['id'] . '/members/' . $sending_data['subscriber_id'], $sending_data );

		if ( isset( $subscribe['error'] ) ) {
			self::log_errors( $subscribe );
		}
	}

	/**
	 * @since 2.04
	 */
	public static function add_tags_to_subscriber( $response, $sending_data ) {
		if ( ! isset( $sending_data['status'] ) ||
			 ( 'pending' !== $sending_data['status'] && 'subscribed' !== $sending_data['status'] ) ||
			 ! isset( $sending_data['frm_tags'] ) ) {
			return;
		}

		// Then a subscriber has probably been added successfully, so let's add tags

		// First check if it was successful
		if ( is_wp_error( $response ) ) {
			return;
		}

		$body = wp_remote_retrieve_body( $response );
		if ( $body == 'error' || is_wp_error( $body ) ) {
			return;
		}

		$body = json_decode( $body, true );
		// These 2 isset()'s are to check if any error
		if ( is_array( $body ) && isset( $body['title'] ) && isset( $body['detail'] ) ) {
			return;
		}

		if ( ! is_array( $sending_data['frm_tags'] ) ) {
			$sending_data['frm_tags'] = explode( ',', $sending_data['frm_tags'] );
		}

		$packaged_tags         = array();
		$data                  = array();
		$data['method']        = 'POST';
		$data['id']            = $sending_data['id'];
		$data['subscriber_id'] = $sending_data['subscriber_id'];

		self::package_tags_deep( $sending_data, $packaged_tags );
		$data['tags']          = $packaged_tags;

		$add_tags = self::decode_call( '/lists/' . $data['id'] . '/members/' . $data['subscriber_id'] . '/tags', $data );

		if ( isset( $add_tags['error'] ) ) {
			self::log_errors( $add_tags );
		}
	}

	/**
	 * @since 2.04
	 */
	private static function package_tags_deep( $sending_data, &$packaged_tags ) {
		$tags  = $sending_data['frm_tags'];
		$form  = $sending_data['form'];
		$entry = $sending_data['entry'];

		foreach ( $tags as $tag ) {
			$tag = FrmFieldsHelper::basic_replace_shortcodes( trim( $tag ), $form, $entry );
			if ( is_array( $tag ) ) {
				$data             = array();
				$data['frm_tags'] = $tag;
				$data['form']     = $form;
				$data['entry']    = $entry;
				self::package_tags_deep( $data, $packaged_tags );
			} else if ( false !== strpos( $tag, ',' ) ) {
				// A shortcode of a field with comma-separated values or checkbox options have probably been encountered
				$data             = array();
				$data['frm_tags'] = explode( ',', $tag );
				$data['form']     = $form;
				$data['entry']    = $entry;
				self::package_tags_deep( $data, $packaged_tags );
			} else {
				$tag = trim( $tag );
				if ( ! empty( $tag ) ) {
					self::package_tag_value( $tag, $packaged_tags );
				}
			}
		}
	}

	/**
	 * @since 2.04
	 *
	 * @param string $tag  A string to attach to a subscriber as tag.
	 * @param array  $tags An array of all tags ready to be sent to the Mailchimp API to be attached to a subscriber.
	 */
	private static function package_tag_value( $tag, &$tags ) {
		$tags[] = array(
			'name'   => $tag,
			'status' => 'active',
		);
	}

	/**
	 * Log errors in PHP error log
	 * More accessible logging should be added in the future
	 *
	 * @since 2.02
	 *
	 * @param array $response
	 */
	private static function log_errors( $response ) {
		error_log( 'MailChimp subscribe error: ' . $response['error'] );

		if ( isset( $response['errors'] ) ) {
			foreach ( $response['errors'] as $error ) {
				error_log( $error['field'] . ': ' . $error['message'] );
			}
		}
	}

	public static function get_groups( $list_id ) {
		$args = array( 'method' => 'GET', 'count' => 30 );
		$groups = self::decode_call( '/lists/' . $list_id . '/interest-categories', $args );
		if ( $groups && isset( $groups['error'] ) ) {
			$groups = false;
		}

		return $groups;
	}

	public static function get_group_options( $list_id, $group_id ) {
		$args = array( 'method' => 'GET', 'count' => 50 );
		return self::decode_call( '/lists/' . $list_id . '/interest-categories/' . $group_id . '/interests', $args );
	}

	public static function get_lists() {
		return self::decode_call( '/lists', array( 'count' => 100, 'method' => 'GET' ) );
	}

	public static function get_list_fields( $list_id ) {
		$args = array( 'method' => 'GET', 'count' => 30 );
		return self::decode_call( '/lists/' . $list_id . '/merge-fields', $args );
	}

	public static function decode_call( $endpoint, $args = array(), $apikey = null ) {
		$res = self::call( $endpoint, $args, $apikey );

		return json_decode( $res, true );
	}

	public static function call( $endpoint, $args = array(), $apikey = null ) {
		if ( is_null( $apikey ) ) {
			$frm_mlcmp_settings = new FrmMlcmpSettings();
			$apikey             = $frm_mlcmp_settings->get_api_key();
		}

		$url       = self::get_endpoint_url( $apikey, $endpoint );
		$post_args = self::setup_post_body( $apikey, $args );

		$res  = wp_remote_post( $url, $post_args );
		$body = wp_remote_retrieve_body( $res );

		self::log_results(
			array(
				'response' => $res,
				'headers'  => $post_args['headers'],
				'body'     => json_encode( $args ),
				'url'      => $url,
			)
		);

		do_action( 'frm_mlcmp_api_request_completed', $res, $args );

		if ( is_wp_error( $res ) ) {
			$message = __( 'You had an error communicating with the MailChimp API.', 'frmmlcmp' ) . $res->get_error_message();

			return json_encode( array( 'error' => $message, 'status' => 'error' ) );
		} elseif ( $body == 'error' || is_wp_error( $body ) ) {
			$message = __( 'You had an error communicating with the MailChimp API.', 'frmmlcmp' );

			return json_encode( array( 'error' => $message, 'status' => 'error' ) );
		} else {
			$body = json_decode( $body, true );
			if ( is_array( $body ) && isset( $body['title'] ) && isset( $body['detail'] ) ) {

				$response = array( 'error' => $body['title'], 'status' => 'error' );

				if ( isset( $body['errors'] ) ) {
					$response['errors'] = $body['errors'];
				}

				return json_encode( $response );
			}
		}

		return $res['body'];
	}

	private static function setup_post_body( $apikey, $args ) {
		$method = 'POST';
		if ( isset( $args['method'] ) ) {
			$method = $args['method'];
			unset( $args['method'] );
		}

		$post_args = array(
			'method'    => $method,
			'headers'   => array(
				'Content-type'  => 'application/json',
				'Authorization' => 'apikey ' . $apikey,
			),
			'sslverify' => false,
		);

		if ( ! empty( $args ) ) {
			if ( $method != 'GET' ) {
				$args = json_encode( $args );
			}
			$post_args['body'] = $args;
		}

		return $post_args;
	}

	private static function get_endpoint_url( $apikey, $endpoint ) {
		$dc = self::get_datacenter( $apikey );
		$dc = empty( $dc ) ? '' : $dc . '.';

		return 'https://' . $dc . 'api.mailchimp.com/3.0/' . $endpoint;
	}

	public static function get_datacenter( $apikey ) {
		$dc = explode( '-', $apikey );

		return isset( $dc[ 1 ] ) ? $dc[ 1 ] : '';
	}

	private static function log_results( $atts ) {
		if ( ! class_exists( 'FrmLog' ) || empty( self::$entry->id ) ) {
			return;
		}

		$body    = wp_remote_retrieve_body( $atts['response'] );
		$content = self::process_response( $atts['response'], $body );
		$message = isset( $content['message'] ) ? $content['message'] : '';

		$headers = '';
		self::array_to_list( $atts['headers'], $headers );

		$log = new FrmLog();
		$log->add(
			array(
				'title'   => __( 'Mailchimp:', 'frmmlcmp' ) . ' ' . self::$action->post_title,
				'content' => (array) $body,
				'fields'  => array(
					'entry'   => self::$entry->id,
					'action'  => self::$action->ID,
					'code'    => isset( $content['code'] ) ? $content['code'] : '',
					'url'     => $atts['url'],
					'message' => $message,
					'request' => $atts['body'],
				),
			)
		);
	}

	private static function process_response( $response, $body ) {
		$processed = array(
			'message' => '',
			'code'    => 'FAIL',
		);

		if ( is_wp_error( $response ) ) {
			$processed['message'] = $response->get_error_message();
		} elseif ( 'error' === $body || is_wp_error( $body ) ) {
			$processed['message'] = __( 'You had an HTTP connection error', 'formidable-api' );
		} elseif ( isset( $response['response'] ) && isset( $response['response']['code'] ) ) {
			$processed['code']    = $response['response']['code'];
			$processed['message'] = $response['body'];
		}

		return $processed;
	}

	private static function array_to_list( $array, &$list ) {
		foreach ( $array as $k => $v ) {
			$list .= "\r\n" . $k . ': ' . $v;
		}
	}

	/**
	 * @deprecated 2.02
	 */
	public static function install() {
		_deprecated_function( __FUNCTION__, '2.02', 'FrmMlcmpAppController::initialize' );
		self::initialize();
	}

	/**
	 * @deprecated 2.03
	 */
	public static function v2_call( $endpoint, $args ) {
		_deprecated_function( __FUNCTION__, '2.03', 'FrmMlcmpAppController::decode_call' );

		return self::decode_call( $endpoint, $args );
	}

	/**
	 * @deprecated 2.03
	 */
	public static function get_group_ids( $groups, $list_id ) {
		_deprecated_function( __FUNCTION__, '2.03', 'FrmMlcmpAppController::get_groups' );

		return self::get_groups( $list_id );

	}

}

<?php

class FrmMlcmpSettings {

	private $option_key = 'frm_mlcmp_options';
	private $needs_update = false;
	private $api_key = '';

	/**
	 * FrmMlcmpSettings constructor
	 *
	 * @since 2.01
	 */
	function __construct() {
		$this->set_api_key();
	}

	/**
	 * Update the options in the database
	 *
	 * @since 2.01
	 *
	 * @param array $posted_values
	 */
	public function update( $posted_values ) {
		$this->set_api_key_from_posted_values( $posted_values );

		if ( $this->needs_update ) {
			update_option( $this->option_key, $this->package_settings() );
		}
	}

	/**
	 * Set the api_key property
	 *
	 * @since 2.01
	 */
	private function set_api_key() {
		$saved_settings = $this->get_saved_options();

		if ( $saved_settings !== false ) {
			$this->api_key = $saved_settings['api_key'];
		}
	}

	/**
	 * Get the api_key property
	 *
	 * @since 2.01
	 *
	 * @return string
	 */
	public function get_api_key() {
		return $this->api_key;
	}

	/**
	 * Get the options saved in the database
	 *
	 * @since 2.01
	 *
	 * @return array|boolean
	 */
	private function get_saved_options() {
		$settings = get_option( $this->option_key );

		// For reverse compatibility
		if ( is_object( $settings ) ) {
			$settings = get_object_vars( $settings );
		}

		return $settings;
	}

	/**
	 * Package the settings before storing them in the database
	 *
	 * @since 2.01
	 *
	 * @return array
	 */
	private function package_settings() {
		return array(
			'api_key' => $this->api_key,
		);
	}

	/**
	 * Set the api key property from the posted values
	 *
	 * @since 2.01
	 *
	 * @param array $posted_values
	 */
	private function set_api_key_from_posted_values( $posted_values ) {
		$posted_api_key = isset( $posted_values['frm_mlcmp_api_key'] ) ? $posted_values['frm_mlcmp_api_key'] : '';

		if ( $this->api_key !== $posted_api_key ) {
			$this->needs_update = true;
			$this->api_key      = $posted_api_key;
		}
	}
}
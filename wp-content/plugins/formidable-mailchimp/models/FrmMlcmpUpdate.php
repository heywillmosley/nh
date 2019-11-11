<?php

class FrmMlcmpUpdate extends FrmAddon {

	public $plugin_file;
	public $plugin_name = 'MailChimp';
	public $download_id = 170655;
	public $version = '2.04';

	public function __construct() {
		$this->plugin_file = dirname( dirname( __FILE__ ) ) . '/formidable-mailchimp.php';
		parent::__construct();
	}

	public static function load_hooks() {
		add_filter( 'frm_include_addon_page', '__return_true' );
		new FrmMlcmpUpdate();
	}

}

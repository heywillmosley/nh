<?php

/**
 * Includes the assets loading and script printing functionality for the Reports
 * page.
 */
class USIN_Reports_Assets extends USIN_Assets{

	protected $has_ui_select = true;

	protected function register_custom_assets(){
		$this->js_assets['usin_chartjs'] = array('path' => 'js/lib/chart/chart.min.js',
			'deps' => array('usin_angular'));
		$this->js_assets['usin_reports'] = array('path' => 'js/reports.min.js',
			'deps' => array('usin_angular', 'usin_angular_material', 'usin_chartjs', 'usin_helpers'));
		$this->js_assets['usin_report_templates'] = array('path' => 'views/reports/templates.js',
			'deps' => array('usin_reports'));
	}

	/**
	 * Loads the required assets on the Reports page
	 */
	public function enqueue_assets(){
		$this->enqueue_scripts(array('usin_angular', 'usin_angular_material', 'usin_chartjs',
			'usin_helpers', 'usin_reports', 'usin_report_templates', 'usin_select'));

		$this->enqueue_styles(array('usin_angular_meterial_css', 'usin_select_css', 'usin_main_css'));

	}


	/**
	 * Prints the initializing JavaScript code on the Reports page.
	 */
	protected function print_inline(){
		$options = array(
			'viewsURL' => 'views/reports',
			'ajaxURL' => admin_url( 'admin-ajax.php' ),
			'nonce' => $this->page->ajax_nonce,
			'reports' => USIN_Reports_Defaults::get(true),
			'reportGroups' => USIN_Report::groups()
		);

		$strings = array(
			'errorLoading' => __('Error loading data', 'usin'),
			'noResults' => __('No results found', 'usin'),
			'noReportsFound' => __('No reports found for this module', 'usin'),
			'toggleReports' => __('Toggle reports', 'usin')
		);

		$options['strings'] = $strings;
		$options = apply_filters('usin_report_options', $options);

		$output = '<script type="text/javascript">var USIN = '.json_encode($options).';</script>';

		echo $output;

	}

}
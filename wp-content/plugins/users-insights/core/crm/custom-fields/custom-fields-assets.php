<?php

/**
 * Includes the assets loading and script printing functionality for the Custom Fields
 * page.
 */
class USIN_Custom_Fields_Assets extends USIN_Assets{

	protected function register_custom_assets(){
		$this->js_assets['usin_custom_fields'] = array('path' => 'js/custom-fields.min.js',
			'deps' => array('usin_angular', 'usin_helpers'));
	}

	/**
	 * Loads the required assets on the Custom Fields page/
	 */
	public function enqueue_assets(){

		$this->enqueue_scripts(array('usin_angular', 'usin_helpers', 'usin_custom_fields'));
		$this->enqueue_style('usin_main_css');

	}


	/**
	 * Prints the initializing JavaScript code on the Custom Fields page.
	 */
	protected function print_inline(){
		$options = array(
			'viewsURL' => plugins_url('views/custom-fields', $this->base_file),
			'ajaxURL' => admin_url( 'admin-ajax.php' ),
			'fields' => USIN_Custom_Fields_Options::get_saved_fields(),
			'fieldTypes' => USIN_Custom_Fields_Options::$field_types,
			'nonce' => $this->page->ajax_nonce,
			'customTemplates' => array()
		);

		$strings = array(
			'addField' => __('Add Field', 'usin'),
			'fieldName' => __('Field Name', 'usin'),
			'fieldKey' => __('Field Key', 'usin'),
			'fieldType' => __('Field Type', 'usin'),
			'fields' => __('Fields', 'usin'),
			'fieldUpdateError' => __( 'Error updating fields', 'usin' ),
			'areYouSure' => __('Are you sure?', 'usin'),
			'actions' => __('Actions', 'usin'),
			'edit' => __('Edit', 'usin'),
			'update' => __('Update', 'usin'),
			'delete' => __('Delete', 'usin'),
			'keyMessage' => __('Tip: If you would like to use existing custom user meta fields from the
			WordPress users meta table, please make sure to insert the existing meta key into the "Field Key"
			field. ', 'usin')
			
		);

		$options['strings'] = $strings;

		$options = apply_filters('usin_cf_options', $options);

		$output = '<script type="text/javascript">var USIN = '.json_encode($options).';</script>';

		echo $output;

	}

}
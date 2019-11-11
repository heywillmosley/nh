<?php

/**
 * @class FLCustomizerControl
 */
final class FLCustomizerControl extends WP_Customize_Control {

	/**
	 * Used to connect to controls to each other.
	 *
	 * @property $connect
	 */
	public $connect = false;

	/**
	 * @method render_content
	 * @protected
	 */
	protected function render_content()
	{
		switch($this->type) {

			case 'font':
			$this->render_font();
			break;

			case 'font-weight':
			$this->render_font_weight();
			break;

			case 'code':
			$this->render_code();
			break;

			case 'line':
			$this->render_line();
			break;

			case 'export-import':
			$this->render_export_import();
			break;
		}
	}

	/**
	 * @method render_content_title
	 * @protected
	 */
	protected function render_content_title()
	{
		if(!empty($this->label)) {
			echo '<span class="customize-control-title">' . esc_html($this->label) . '</span>';
		}
		if(!empty($this->description)) {
			echo '<span class="description customize-control-description">' . $this->description . '</span>';
		}
	}

	/**
	 * @method render_connect_attr
	 * @protected
	 */
	protected function render_connect_attribute()
	{
		if ( $this->connect ) {
			echo ' data-connected-control="'. $this->connect .'"';
		}
	}

	/**
	 * @method render_font
	 * @protected
	 */
	protected function render_font()
	{
		echo '<label>';
		$this->render_content_title();
		echo '<select ';
		$this->link();
		$this->render_connect_attribute();
		echo '>';
		echo '<optgroup label="System">';

		foreach(FLFontFamilies::$system as $name => $variants) {
			echo '<option value="'. $name .'" '. selected($name, $this->value(), false) .'>'. $name .'</option>';
		}

		echo '<optgroup label="Google">';

		foreach(FLFontFamilies::$google as $name => $variants) {
			echo '<option value="'. $name .'" '. selected($name, $this->value(), false) .'>'. $name .'</option>';
		}

		echo '</select>';
		echo '</label>';
	}

	/**
	 * @method render_font_weight
	 * @protected
	 */
	protected function render_font_weight()
	{
		echo '<label>';
		$this->render_content_title();
		echo '<select ';
		$this->link();
		$this->render_connect_attribute();
		echo '>';
		echo '<option value="'. $this->value() .'" selected="selected">'. $this->value() .'</option>';
		echo '</select>';
		echo '</label>';
	}

	/**
	 * @method render_code
	 * @protected
	 */
	protected function render_code()
	{
		echo '<label>';
		$this->render_content_title();
		echo '<textarea rows="15" style="width:100%" ';
		$this->link();
		echo '>' . $this->value() . '</textarea>';
		echo '</label>';
	}

	/**
	 * @method render_line
	 * @protected
	 */
	protected function render_line()
	{
		echo '<hr />';
	}

	/**
	 * @method render_export_import
	 * @protected
	 */
	protected function render_export_import()
	{
		$plugin = 'customizer-export-import';
		$nonce  = wp_create_nonce( 'install-plugin_' . $plugin );
		$url    = admin_url( 'update.php?action=install-plugin&plugin=' . $plugin . '&_wpnonce=' . $nonce );

		echo '<p>' . __( 'Please install and activate the "Customizer Export/Import" plugin to proceed.', 'fl-automator' ) . '</p>';
		echo '<a class="install-now button" href="' . $url . '">' . _x( 'Install &amp; Activate', '...a plugin.', 'fl-automator' ) . '</a>';
	}
}
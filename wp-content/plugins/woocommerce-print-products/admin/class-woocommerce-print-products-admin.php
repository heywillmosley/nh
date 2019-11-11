<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       http://woocommerce-print-products.db-dzine.de
 * @since      1.0.0
 *
 * @package    WooCommerce_Print_Products
 * @subpackage WooCommerce_Print_Products/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    WooCommerce_Print_Products
 * @subpackage WooCommerce_Print_Products/admin
 * @author     Daniel Barenkamp <contact@db-dzine.de>
 */
class WooCommerce_Print_Products_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) 
	{

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

	public function load_redux()
	{
	    // Load the theme/plugin options
	    if ( file_exists( plugin_dir_path( dirname( __FILE__ ) ) . 'admin/options-init.php' ) ) {
	        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/options-init.php';
	    }
	}

	public function print_product_shortcode($atts)
	{
		$args = shortcode_atts( array(
	        'id' => '',
	        'mode' => 'pdf',
	        'text' => 'Print Product',
	    ), $atts );

	    $id = $args['id'];
	    $mode = $args['mode'];
	    $text = $args['text'];

	    if(isset($_GET['product_id']) && !empty($_GET['product_id'])) {
	    	$id = $_GET['product_id'];
	    }

	    if(empty($id)) {
	    	$url = get_permalink();
	    } else {
	    	$url = get_permalink($id);
	    }

	    return '<a href="' . $url . '?print-products=' . $mode . '" target="_blank" class="btn button btn-primary">' . $text . '</a>';
	}
}

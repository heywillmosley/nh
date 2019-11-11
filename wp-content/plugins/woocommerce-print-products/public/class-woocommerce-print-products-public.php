<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       http://woocommerce-print-products.db-dzine.de
 * @since      1.0.0
 *
 * @package    WooCommerce_Print_Products
 * @subpackage WooCommerce_Print_Products/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    WooCommerce_Print_Products
 * @subpackage WooCommerce_Print_Products/public
 * @author     Daniel Barenkamp <contact@db-dzine.de>
 */
class WooCommerce_Print_Products_Public {

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
	 * options of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      array    $options
	 */
	private $options;

	/**
	 * Product URL
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $product_url
	 */
	private $product_url;

	/**
	 * Product
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      object    $product
	 */
	private $product;

	/**
	 * Post
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      object    $post
	 */
	private $post;

	/**
	 * Data
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      mixed    $data
	 */
	private $data;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) 
	{

		$this->plugin_name = $plugin_name;
		$this->version = $version;
		$this->data = new stdClass;

	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() 
	{

		global $woocommerce_print_products_options;

		$this->options = $woocommerce_print_products_options;

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/woocommerce-print-products-public.css', array(), $this->version, 'all' );
		wp_enqueue_style( 'font-awesome', 'https://maxcdn.bootstrapcdn.com/font-awesome/4.5.0/css/font-awesome.min.css', array(), '4.5.0', 'all' );
		
	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() 
	{

		global $woocommerce_print_products_options;

		$this->options = $woocommerce_print_products_options;

		$customJS = $this->get_option('customJS');
		if(empty($customJS))
		{
			return false;
		}

		file_put_contents( dirname(__FILE__)  . '/js/woocommerce-print-products-custom.js', $customJS);

		wp_enqueue_script( $this->plugin_name.'-custom', plugin_dir_url( __FILE__ ) . 'js/woocommerce-print-products-custom.js', array( 'jquery' ), $this->version, true );
	}

	/**
	 * Gets options
	 *
	 * @since    1.0.0
	 */
    private function get_option($option)
    {
    	if(!is_array($this->options)) {
    		return false;
    	}
    	
    	if(!array_key_exists($option, $this->options))
    	{
    		return false;
    	}
    	return $this->options[$option];
    }
	
	/**
	 * Inits the print products
	 *
	 * @since    1.0.0
	 */
    public function initWooCommerce_Print_Products()
    {

		global $woocommerce_print_products_options;

		$this->options = $woocommerce_print_products_options;

		if (!$this->get_option('enable'))
		{
			return false;
		}

		// Enable User check
		if($this->get_option('enableLimitAccess'))
		{
			$roles = $this->get_option('role');
			if(empty($roles)) {
				$roles[] = 'administrator';
			}

			$currentUserRole = $this->get_user_role();

			if(!in_array($currentUserRole, $roles))
			{
				return FALSE;
			}
		}

		$actual_link = 'http' . (isset($_SERVER['HTTPS']) ? 's' : '') . '://' . "{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}";

		if( strpos($actual_link, '?') === FALSE ){ 
			$this->product_url = $actual_link . '?';
		} else {
		 	$this->product_url = $actual_link . '&';
		}

		$iconPosition = $this->get_option('iconPosition');
		add_action( $iconPosition, array($this, 'show_print_links'), 90 );

		if(isset($_GET['print-products']))
		{
			add_action("wp", array($this, 'watch'));
		}
    }

    public function show_print_links()
    {
    	$apply = true;

		$excludeProductCategories = $this->get_option('excludeProductCategories');
		if(!empty($excludeProductCategories)) 
		{
			if($this->excludeProductCategories()) {
				$apply = FALSE;
			}
		}

		$excludeProducts = $this->get_option('excludeProducts');
		if(!empty($excludeProducts)) 
		{

			if($this->excludeProducts()) {
				$apply = FALSE;
			}
		}

		if($apply) {

	    	echo '<div class="woocommerce-print-products link-wrapper">';

	    	if($this->get_option('iconDisplay') == "horizontal") {
	    		echo $this->get_pdf_link();
	    		echo $this->get_word_link();
	    		echo $this->get_print_link();
	    	}
	    	if($this->get_option('iconDisplay') == "vertical") {
	    		echo '<ul class="fa-ul">';
	    		
				  echo '<li>' . $this->get_pdf_link() .'</li>';
				  echo '<li>' . $this->get_word_link() .'</li>';
				  echo '<li>' . $this->get_print_link() .'</li>';
				echo '</ul>';
			}
	    	
	    	echo '</div>';
    	} else {
    		return FALSE;
    	}
    }

    private function get_pdf_link()
    {
    	if(!$this->get_option('enablePDF')) return FALSE;

    	return '<a href="'.$this->product_url.'print-products=pdf'.'" target="_blank"><i class="fa fa-file-pdf-o ' . $this->get_option('iconSize') . '"></i></a>';
    }

    private function get_word_link()
    {
    	if(!$this->get_option('enableWord')) return FALSE;

    	return '<a href="'.$this->product_url.'print-products=word'.'" target="_blank"><i class="fa fa-file-word-o ' . $this->get_option('iconSize') . '"></i></a>';
    }

    private function get_print_link()
    {
    	if(!$this->get_option('enablePrint')) return FALSE;

    	return '<a href="#"
    	onclick="print(); return false;" target="_blank"><i class="fa fa-print ' . $this->get_option('iconSize') . '"></i></a>
    	<script>
			function print() {
				var w = window.open("'.$this->product_url.'print-products=print");
			}
    	</script>';
    }

    public function watch()
    {
    	$this->setup_data();

		if($_GET['print-products'] == "pdf")
		{
			$this->init_pdf();
		}
		if($_GET['print-products'] == "word")
		{
			$this->init_word();
		}
		if($_GET['print-products'] == "print")
		{
			$this->init_print();
		}
	}

	public function setup_data()
	{
    	global $post, $woocommerce, $wpdb;

    	$this->woocommerce_version = $woocommerce->version;

    	// default Variables
		$this->data->blog_name = get_bloginfo('name');
		$this->data->blog_description  = get_bloginfo('description');

		$this->data->ID = $post->ID;

		if( version_compare( $this->woocommerce_version, '3.0.0', ">=" ) ) {
			$product = wc_get_product( $this->data->ID );
		} else {
			$product = get_product( $this->data->ID );
		}

		$this->product = $product;
		$this->post = $post;

		// product variables
		$this->data->title = apply_filters('woocommerce_print_products_title', $this->post->post_title);
		$this->data->short_description = apply_filters('woocommerce_print_products_short_description', do_shortcode($this->post->post_excerpt));

		if($this->get_option('showShortDescriptionStripImages')) {
			$this->post->post_excerpt = preg_replace("/<img[^>]+\>/i", "", $this->post->post_excerpt); 	
		}

		$price = $this->product->get_price_html();

		$price = htmlspecialchars_decode($price);
	    $price = str_replace(array('&#8381;'), 'RUB', $price);

		$this->data->price = apply_filters('woocommerce_print_products_price', $price);

		$sku = $this->product->get_sku();
		$this->data->sku = !empty($sku) ? $sku : __( 'N/A', 'woocommerce-print-products' );

		$this->data->cat_count = sizeof( get_the_terms( $this->data->ID, 'product_cat' ) );
		$this->data->tag_count = sizeof( get_the_terms( $this->data->ID, 'product_tag' ) );

		// Description
		if($this->get_option('showDescriptionDoShortcodes')) {
			$this->data->description = apply_filters('woocommerce_print_products_description', do_shortcode( $this->post->post_content ));
		} else {
			$this->data->description = apply_filters('woocommerce_print_products_description', $this->post->post_content);
		}
		
		if($this->get_option('showDescriptionStripImages')) {
			$this->post->description = preg_replace("/<img[^>]+\>/i", "", $this->post->description); 	
		}

		if( version_compare( $this->woocommerce_version, '3.0.0', ">=" ) ) {
			$this->data->categories = wc_get_product_category_list($this->data->ID, ', ', '<b>' . _n( 'Category:', 'Categories:', $this->data->cat_count, 'woocommerce' ) . '</b> ');
			$this->data->tags = wc_get_product_tag_list($this->data->ID, ', ', '<b>' . _n( 'Tag:', 'Tags:', $this->data->tag_count, 'woocommerce' ) . '</b> ');
		} else {
			$this->data->categories = $this->product->get_categories( ', ', '<b>' . _n( 'Category:', 'Categories:', $this->data->cat_count, 'woocommerce' ) . '</b> ');
			$this->data->tags = $this->product->get_tags( ', ', '<b>' . _n( 'Tag:', 'Tags:', $this->data->tag_count, 'woocommerce' ) . '</b> ');
		}		

		if ( has_post_thumbnail($this->post->ID)) { 
			$thumbnail = wp_get_attachment_image_src( get_post_thumbnail_id($this->post->ID), 'shop_single' ); 
			$this->data->src = $thumbnail[0];
		} else { 
			$this->data->src = plugin_dir_url( __FILE__ ) . 'img/placeholder.png';
		}

	    $sql = "SELECT DISTINCT meta_key
	                    FROM " . $wpdb->postmeta . "
	                    INNER JOIN  " . $wpdb->posts . " 
	                    ON post_id = ID
	                    WHERE post_type = 'product'
	                    ORDER BY meta_key ASC";

	    $meta_keys = (array) $wpdb->get_results( $sql, 'ARRAY_A' );
	    $meta_keys_to_exclude = array('_crosssell_ids', '_children', '_default_attributes', '_height', '_length', '_max_price_variation_id', '_max_regular_price_variation_id', '_max_sale_price_variation_id', '_max_variation_price', '_max_variation_regular_price', '_max_variation_sale_price', '_min_price_variation_id', '_min_regular_price_variation_id', '_min_sale_price_variation_id', '_min_variation_price', '_min_variation_regular_price', '_min_variation_sale_price', '_price', '_product_attributes', '_product_image_gallery', '_sku', '_regular_price', '_sale_price', '_sale_price_dates_from', '_sale_price_dates_to', '_sku', '_upsell_ids', '_thumbnail_id', '_weight', '_width');

	    $temp = array();
	    foreach ($meta_keys as $key => $meta_key) {
	        $meta_key = preg_replace('/[^\w-]/', '', $meta_key['meta_key']);

	        if(in_array($meta_key, $meta_keys_to_exclude) || (substr( $meta_key, 0, 7 ) === "_oembed") || (!$this->get_option('showCustomMetaKey_' . $meta_key)) ) {
	            continue;
	        }
	        
	        $temp[] = array (
	        	'key' => $meta_key,
	        	'before' => $this->get_option('showCustomMetaKeyText_' . $meta_key),
	        	'value' => get_post_meta( $this->data->ID, $meta_key, true),
        	);
	    }

	    $this->data->meta_keys = $temp;

		return TRUE;
	}

    public function init_pdf()
    {
    	if(!class_exists('mPDF')) return FALSE;
    	if(!$this->get_option('enablePDF')) return FALSE;

    	$headerTopMargin = $this->get_option('headerTopMargin');
    	$footerTopMargin = $this->get_option('footerTopMargin');

		// use A4-L for landscape
		$mpdf = new mPDF(
			'utf-8', 
			'A4',    // format - A4, for example, default ''
			0,     // font size - default 0
			'',    // default font family
			0,    	// 15 margin_left
			0,    	// 15 margin right
			$headerTopMargin,     // 16 margin top
			$footerTopMargin,    	// margin bottom
			0,     // 9 margin header
			0,     // 9 margin footer
			'P'  	// L - landscape, P - portrait
			);

		$css = $this->build_CSS();

		if($this->get_option('enableHeader'))
		{
			$header = $this->get_header();
			$mpdf->SetHTMLHeader($header);
		}

		if($this->get_option('enableFooter'))
		{
			$footer = $this->get_footer();
			$mpdf->SetHTMLFooter($footer);
		}

		$layout = $this->get_option('layout');
		$order = $this->get_option('informationOrder');
		$enabledBlocks = $order['enabled'];
		unset($enabledBlocks['placebo']);

		if($layout == 1)
		{
			$html = $this->get_first_layout();
		}
		if($layout == 2)
		{
			$html = $this->get_second_layout();
		}
		if($layout == 3)
		{
			$html = $this->get_third_layout();
		}

		$skipNextPagebreak = false;
		foreach ($enabledBlocks as $key => $value) {
			$temp = explode('-', $key);
			$block = $temp[0];

			if($block == "pagebreak" && $skipNextPagebreak == true){
				$skipNextPagebreak = false;
				continue;
			} else {
				$skipNextPagebreak = false;
			}

			$func = 'get_'.$block;
			
			$return = call_user_func(array($this, $func));
			if($return === false){
				$skipNextPagebreak = true;
			} else {
				$html .= $return;
			}

		}

		$filename = $this->escape_filename($this->data->title);

		if($this->get_option('debugMode')) {
			echo $header;
			echo $css.$html;
			echo $footer;
			die();
		}
		$mpdf->useAdobeCJK = true;
		$mpdf->autoScriptToLang = true;
		$mpdf->autoLangToFont = true;
		$mpdf->WriteHTML($css.$html);
		$mpdf->Output($filename.'.pdf', 'I');
		exit;
    }

    public function init_word()
    {
		global $post;

		if(!$this->get_option('enableWord')) return FALSE;

		$css = $this->build_CSS();

		if($this->get_option('enableHeader'))
		{
			$header = $this->get_header();
		}

		if($this->get_option('enableFooter'))
		{
			$footer = $this->get_footer();
		}

		$layout = $this->get_option('layout');
		$order = $this->get_option('informationOrder');
		$enabledBlocks = $order['enabled'];
		unset($enabledBlocks['placebo']);
		
		if($layout == 1)
		{
			$html = $this->get_first_layout();
		}
		if($layout == 2)
		{
			$html = $this->get_second_layout();
		}
		if($layout == 3)
		{
			$html = $this->get_third_layout();
		}

		foreach ($enabledBlocks as $key => $value) {
			$temp = explode('-', $key);
			$block = $temp[0];

			$func = 'get_'.$block;
			$html .= call_user_func(array($this, $func));

		}

		$filename = $this->escape_filename($this->data->title);

		header("Content-type: application/vnd.ms-word");
		header("Content-Disposition: attachment;Filename=" . $filename . ".doc");

		echo "<html>";
		echo $css;
		echo "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\">";
		echo "<body>";
		echo $header;
		echo $html;
		echo $footer;
		echo "</body>";
		echo "</html>";
    }

    public function init_print()
    {
    	if(!$this->get_option('enablePrint')) return FALSE;

		$css = $this->build_CSS();

		if($this->get_option('enableHeader'))
		{
			$header = $this->get_header();
		}

		if($this->get_option('enableFooter'))
		{
			$footer = $this->get_footer();
		}

		$layout = $this->get_option('layout');
		$order = $this->get_option('informationOrder');
		$enabledBlocks = $order['enabled'];
		unset($enabledBlocks['placebo']);
		
		if($layout == 1)
		{
			$html = $this->get_first_layout();
		}
		if($layout == 2)
		{
			$html = $this->get_second_layout();
		}
		if($layout == 3)
		{
			$html = $this->get_third_layout();
		}

		foreach ($enabledBlocks as $key => $value) {
			$temp = explode('-', $key);
			$block = $temp[0];

			$func = 'get_'.$block;
			$html .= call_user_func(array($this, $func));

		}
		$pagebreak_css = '<style>
								@media print {
				    				.page-break {display:block; page-break-after: always;}
							}
							</style>';
		$print_js = '
				<script>
				var w = window;
				var d = document;

				var printAndClose = function () {
					if (w.document.readyState == "complete") {
						clearInterval(sched);
						setTimeout(function() {
							w.focus();
							w.print();
							w.close();
						},
						500);
					}
				};
      			var sched = setInterval(printAndClose, 200);
      			</script>';
		$css = $css.$pagebreak_css.$print_js;
		echo $css.$header.$html.$footer;
		exit();
    }

    public function build_CSS()
    {
    	$layout = $this->get_option('layout');
    	$backgroundColor = $this->get_option('backgroundColor');
    	$textAlign = $this->get_option('textAlign') ? $this->get_option('textAlign') : 'center';
    	$textColor = $this->get_option('textColor');
    	$linkColor = $this->get_option('linkColor');
    	// Font
    	$fontFamily = $this->get_option('fontFamily') ? $this->get_option('fontFamily') : 'dejavusans';
    	$fontSize = $this->get_option('fontSize') ? $this->get_option('fontSize') : '11';
    	$headingsFontFamily = $this->get_option('headingsFontFamily') ? $this->get_option('headingsFontFamily') : 'dejavusans';
    	$headingsFontSize = $this->get_option('headingsFontSize') ? $this->get_option('headingsFontSize') : '16';

    	$fontSize = intval($fontSize);
    	$headingsFontSize = intval($headingsFontSize);

    	$fontLineHeight =  $this->get_option('fontLineHeight') ? $this->get_option('fontLineHeight') : $fontSize + 6; 
    	$headingsLineHeight =  $this->get_option('headingsLineHeight') ? $this->get_option('headingsLineHeight') : $headingsFontSize + 6; 

    	$fontLineHeight = intval($fontLineHeight);
    	$headingsLineHeight = intval($headingsLineHeight);

		$css = '
		<head>
			<style media="all">';

		if(!empty($backgroundColor)) {
			$css .= 'body { background-color: ' . $backgroundColor . ';}';
		}
		if(!empty($textColor)) {
			$css .= 'body { color: ' . $textColor . ';}';
		}
		if(!empty($linkColor)) {
			$css .= 'a, a:hover { color: ' . $linkColor . ';}';
		}

		$css .= '.title td, .description td, table { text-align: ' . $textAlign . '; }';

		$css .= '
				body, table { font-family: ' . $fontFamily . ', sans-serif; font-size: ' . $fontSize . 'pt; line-height: ' . $fontLineHeight . 'pt; } 
				p { margin-bottom: 10px; }
				.pagebreak { display: none; }
				table { width:100%; padding: 10px 25px; }
				h1,h2,h3,h4,h5,h6 { font-family: ' . $headingsFontFamily . ', sans-serif;}
				h1, .title { font-size: ' . $headingsFontSize . 'px; text-transform: uppercase; line-height: ' . $headingsLineHeight . 'px;}
				h2, .title { font-size: ' . $headingsFontSize . 'px; text-transform: uppercase; line-height: ' . $headingsLineHeight . 'px;}
				.attributes { width: 100%; }
				.attributes th { width:33%; text-align:left; padding-top:5px; padding-bottom: 5px;}
				.attributes td { width:66%; text-align:left; }
				.meta { font-size: 10pt; }
				.title { width: 100%; }
				.title td { padding-bottom: 10px; padding-top: 40px; }
				td.product-title { padding-bottom: 30px; }
				';

		if($layout == 3){
			$css .= '.attributes-title, .attributes {
						padding: 0;
					}';
		}

		$customCSS = $this->get_option('customCSS');
		if(!empty($customCSS))
		{
			$css .= $customCSS;
		}

		$css .= '
			</style>

		</head>';

		return $css;
    }

    public function get_header()
    {
    	$headerBackgroundColor = $this->get_option('headerBackgroundColor');
    	$headerTextColor = $this->get_option('headerTextColor');
    	$headerLayout = $this->get_option('headerLayout');
    	$this->get_option('headerHeight') ? $headerHeight = $this->get_option('headerHeight') : $headerHeight = 'auto';
		$headerVAlign = $this->get_option('headerVAlign');

    	$topLeft = $this->get_option('headerTopLeft');
    	$topMiddle = $this->get_option('headerTopMiddle');
    	$topRight = $this->get_option('headerTopRight');

    	$headerTextAfterHeader = $this->get_option('headerTextAfterHeader');

    	$header = "";

    	if($headerLayout == "oneCol")
    	{
			$header .= '
			<table class="header" width="100%" style="vertical-align: bottom; font-size: 9pt; background-color: ' . $headerBackgroundColor . '; color: ' . $headerTextColor . ';">
				<tr>
					<td height="' . $headerHeight . '" valign="' . $headerVAlign . '" width="100%" style="text-align: center;">' . $this->get_header_footer_type($topLeft, 'headerTopLeft') . '</td>
				</tr>
			</table>';
    	} elseif($headerLayout == "threeCols") {
			$header .= '
			<table class="header" width="100%" style="vertical-align: bottom; font-size: 9pt; background-color: ' . $headerBackgroundColor . '; color: ' . $headerTextColor . ';">
				<tr>
					<td height="' . $headerHeight . '" valign="' . $headerVAlign . '" width="33%" style="text-align: left;">' . $this->get_header_footer_type($topLeft, 'headerTopLeft') . '</td>
					<td height="' . $headerHeight . '" valign="' . $headerVAlign . '" width="33%" style="text-align: center;">' . $this->get_header_footer_type($topMiddle, 'headerTopMiddle') . '</td>
					<td height="' . $headerHeight . '" valign="' . $headerVAlign . '" width="33%" style="text-align: right;">' . $this->get_header_footer_type($topRight, 'headerTopRight') . '</td>
				</tr>
			</table>';
		} else {
			$header .= '
			<table class="header" width="100%" style="vertical-align: bottom; font-size: 9pt; background-color: ' . $headerBackgroundColor . '; color: ' . $headerTextColor . ';">
				<tr>
					<td height="' . $headerHeight . '" valign="' . $headerVAlign . '" width="50%" style="text-align: left;">' . $this->get_header_footer_type($topLeft, 'headerTopLeft') . '</td>
					<td height="' . $headerHeight . '" valign="' . $headerVAlign . '" width="50%" style="text-align: right;">' . $this->get_header_footer_type($topRight, 'headerTopRight') . '</td>
				</tr>
			</table>';
		}


    	if(!empty($headerTextAfterHeader)) {
			$header .= '
			<table class="after-header" width="100%" style="vertical-align: bottom; font-size: 9pt;">
				<tr>
					<td width="100%" style="text-align: center; padding-bottom: 40px;">' . $headerTextAfterHeader . '</td>
				</tr>
			</table>';
		}


		return $header;
    }

    public function get_footer()
    {
    	$footerBackgroundColor = $this->get_option('footerBackgroundColor');
    	$footerTextColor = $this->get_option('footerTextColor');
    	$footerLayout = $this->get_option('footerLayout');
    	$this->get_option('footerHeight') ? $footerHeight = $this->get_option('footerHeight') : $footerHeight = 'auto';
		$footerVAlign = $this->get_option('footerVAlign');

    	$topLeft = $this->get_option('footerTopLeft');
    	$topRight = $this->get_option('footerTopRight');
    	$topMiddle = $this->get_option('footerTopMiddle');

    	$foooterTextBeforeFooter = $this->get_option('foooterTextBeforeFooter');
    	
    	$footer = "";

    	if(!empty($foooterTextBeforeFooter)) {
    		$footer .= '
    		<table class="pre-footer" width="100%" style="vertical-align: bottom; font-size: 9pt;">
				<tr>
					<td width="100%" style="text-align: center;">' . $foooterTextBeforeFooter . '</td>
				</tr>
			</table>';
    	}

    	if($footerLayout == "oneCol")
    	{
			$footer .= '
			<table class="footer" width="100%" style="vertical-align: bottom; font-size: 9pt; background-color: ' . $footerBackgroundColor . '; color: ' . $footerTextColor . ';">
				<tr>
					<td height="' . $footerHeight . '" valign="' . $footerVAlign . '" width="100%" style="text-align: center;">' . $this->get_header_footer_type($topLeft, 'footerTopLeft') . '</td>
				</tr>
			</table>';
    	} elseif($footerLayout == "threeCols") {
			$footer .= '
			<table class="footer" width="100%" style="vertical-align: bottom; font-size: 9pt; background-color: ' . $footerBackgroundColor . '; color: ' . $footerTextColor . ';">
				<tr>
					<td height="' . $footerHeight . '" valign="' . $footerVAlign . '" width="33%" style="text-align: left;">' . $this->get_header_footer_type($topLeft, 'footerTopLeft') . '</td>
					<td height="' . $footerHeight . '" valign="' . $footerVAlign . '" width="33%" style="text-align: center;">'. $this->get_header_footer_type($topMiddle, 'footerTopMiddle') . '</td>
					<td height="' . $footerHeight . '" valign="' . $footerVAlign . '" width="33%" style="text-align: right;">' . $this->get_header_footer_type($topRight, 'footerTopRight') . '</td>
				</tr>
			</table>';
		} else {
			$footer .= '
			<table class="footer" width="100%" style="vertical-align: bottom; font-size: 9pt; background-color: ' . $footerBackgroundColor . '; color: ' . $footerTextColor . ';">
				<tr>
					<td height="' . $footerHeight . '" valign="' . $footerVAlign . '" width="50%" style="text-align: left;">' . $this->get_header_footer_type($topLeft, 'footerTopLeft') . '</td>
					<td height="' . $footerHeight . '" valign="' . $footerVAlign . '" width="50%" style="text-align: right;">' . $this->get_header_footer_type($topRight, 'footerTopRight') . '</td>
				</tr>
			</table>';
		}

		return $footer;
    }

    private function get_header_footer_type($type, $position)
    {

    	switch ($type) {
    		case 'text':
    			return wpautop( do_shortcode( $this->get_option($position.'Text') ) );
    			break;
    		case 'bloginfo':
    			return $this->data->blog_name.'<br/>'.$this->data->blog_description;
    			break;
    		case 'pagenumber':
    			if($_GET['print-products'] == "pdf") {
    				return __( 'Page:', 'woocommerce-print-products').' {PAGENO}';
    			} else {
    				return '';
    			}
    			break;
    		case 'productinfo':
    			return $this->data->title.'<br/>'.get_permalink();
    			break;
    		case 'image':
    			$image = $this->get_option($position.'Image');
    			$imageSrc = $image['url'];
    			$imageHTML = '<img src="' . $image['url'] . '">';
    			return $imageHTML;
    			break;
    		case 'exportinfo':
    			return date('d.m.y');
    			break;
			case 'qr':
				return '<barcode code="' . get_permalink($this->data->ID) . '" type="QR" class="barcode" size="0.8" error="M" />';
				break;
    		default:
    			return '';
    			break;
    	}
    }

    public function get_first_layout()
    {
    	$showImage = $this->get_option('showImage');
    	$showImageSize = $this->get_option('showImageSize');
		$showTitle = $this->get_option('showTitle');
		$showPrice = $this->get_option('showPrice');
		$showShortDescription = $this->get_option('showShortDescription');
		$showSKU = $this->get_option('showSKU');
		$showCategories = $this->get_option('showCategories');
		$showTags = $this->get_option('showTags');
		$showQR = $this->get_option('showQR');
		$showMetaFreetext = $this->get_option('showMetaFreetext');
		$metaFreeText = $this->get_option('metaFreeText');

		$featured_image = '<img width="' . $showImageSize . 'px" src="' . $this->data->src . '">';


		$html = '
		<table class="frame" style="vertical-align: top;">
			<tr>';

		if($showImage) {
			$html .= '<td class="featured-image" style="width: 50%; text-align:left;">'.$featured_image.'</td>';
		}

			$html .= '<td class="product-info" style="width: 50%; padding: 10px;">';

		if($showTitle) {
			$html .= '<h1 class="product-title">' . $this->data->title.'</h1><br>';
		}
		if($showPrice) {
			$html .= '<p class="product-price" style="font-weight:bold;">' . $this->data->price.'</p><br>';
		}
		if($showShortDescription) {
			$html .=  '<div class="product-short-description">' . wpautop($this->data->short_description).'<br/></div>';
		}
		if($showMetaFreetext && !empty($metaFreeText)) {
			$html .= wpautop($metaFreeText).'<br/>';
		}
		if($showQR) {
			$html .= '<barcode code="' . get_permalink($this->data->ID) . '" type="QR" class="barcode" size="1.0" error="M" />';
		}

		$html .= '<hr style="color: #555555" />';

		if($showSKU) {
			$html .= '<p class="product-sku"><b>'.__( 'SKU:', 'woocommerce'  ). '</b> ' . $this->data->sku .'</p>';
		}
		if($showCategories) {
			$html .= '<p class="product-categories">' . $this->data->categories.'</p>';
		}
		if($showTags) {
			$html .= '<p class="product-tags">' . $this->data->tags.'</p>';
		}
	 	if(is_array($this->data->meta_keys)) {
	 		$html .= '<p class="product-meta-keys">';
	 		foreach ( $this->data->meta_keys as $meta_key){
	 			$html .= '<b class="product-meta-key">' . $meta_key['before'] . ':</b><span class="product-meta-value">' . $meta_key['value'] . '</span><br>';
	 		}
	 		$html .= '</p>';
	 	}
					
		$html .= '</td>
			</tr>
		</table>';

		return $html;
    }

    public function get_second_layout()
    {
    	$showImage = $this->get_option('showImage');
    	$showImageSize = $this->get_option('showImageSize');
		$showTitle = $this->get_option('showTitle');
		$showPrice = $this->get_option('showPrice');
		$showShortDescription = $this->get_option('showShortDescription');
		$showSKU = $this->get_option('showSKU');
		$showCategories = $this->get_option('showCategories');
		$showTags = $this->get_option('showTags');
		$showQR = $this->get_option('showQR');
		$showMetaFreetext = $this->get_option('showMetaFreetext');
		$metaFreeText = $this->get_option('metaFreeText');

		$featured_image = '<img width="' . $showImageSize . 'px" src="' . $this->data->src . '" >';

		$html = '<table class="frame" width="100%">';
		if($showTitle) {
			$html .= '<tr>
						<td class="product-title">
							<h1>' . $this->data->title . '</h1>
						</td>
					</tr>';
		}
		if($showImage) {
			$html .= '<tr>
				<td class="featured-image" style="background-color: #EEEEEE; text-align:center;">'
					. $featured_image .
				'</td>
			</tr>';
		}	
		$html .= '</table>';

		$html .= '
		<table style="vertical-align: top; width: 100%;">
			<tr>
				<td>
					<br>';

		if($showPrice) {
			$html .= '<p class="product-price" style="font-weight:bold; font-size:20pt;">' . $this->data->price.'</p><br>';
		}
		if($showShortDescription) {
			$html .= '<div class="product-short-description">' . wpautop($this->data->short_description).'<br/></div>';
		}

		if($showMetaFreetext && !empty($metaFreeText)) {
			$html .= wpautop($metaFreeText);
		}

		$html .= '<p class="meta">';
		if($showSKU) {
			$html .= '<span class="product-sku"><b>'.__( 'SKU:', 'woocommerce'  ). '</b> '. $this->data->sku . '</span>';
		}
		if($showCategories) {
			$html .= ' | <span class="product-categories">' . $this->data->categories . '</span>';
		}
		if($showTags) {
			$html .= ' | <span class="product-tags">' . $this->data->tags. '</span>';
		}
		$html .= '</p>';

	 	if(is_array($this->data->meta_keys)) {
	 		$html .= '<p>';
	 		foreach ( $this->data->meta_keys as $meta_key){
	 			$html .= '<b>' . $meta_key['before'] . '</b>:' . $meta_key['value'];
	 		}
	 		$html .= '</p>';
	 	}

		$html .= '</td>
			</tr>
		</table>';

		if($showQR) {
			$html .= '<table class="qr-code-container">
						<tr>
							<td class="qr-code">
								<barcode code="' . get_permalink($this->data->ID) . '" type="QR" class="barcode" size="1.0" error="M" />
							</td>
						</tr>
					</table>';
		}
					
		return $html;
	}

	public function get_third_layout()
    {

    	$product = $this->product;

    	$showImage = $this->get_option('showImage');
    	$showImageSize = $this->get_option('showImageSize');
		$showTitle = $this->get_option('showTitle');
		$showPrice = $this->get_option('showPrice');
		$showShortDescription = $this->get_option('showShortDescription');
		$showSKU = $this->get_option('showSKU');
		$showCategories = $this->get_option('showCategories');
		$showTags = $this->get_option('showTags');
		$showQR = $this->get_option('showQR');
		$showMetaFreetext = $this->get_option('showMetaFreetext');
		$metaFreeText = $this->get_option('metaFreeText');

		$featured_image = '<img width="' . $showImageSize . 'px" src="' . $this->data->src . '" >';

		if( version_compare( $this->woocommerce_version, '3.0.0', ">=" ) ) {
			$attachment_ids = $product->get_gallery_image_ids();
		} else {
			$attachment_ids = $product->get_gallery_attachment_ids();
		}

		$thumbnail = wp_get_attachment_image_src( $attachment_ids[0], 'shop_single' ); 
		$src = $thumbnail[0];
		if(!empty($src)) {
			$featured_image .= '<br/><br/><br/><br/><img width="' . $showImageSize . 'px" src="' . $src . '" >';
		}

		$html = '<table class="frame" width="100%">';
		if($showTitle) {
			$html .= '<tr>
						<td class="product-title">
							<h1>' . $this->data->title . '</h1>
						</td>
					</tr>';
		}
		if($showShortDescription) {
			$html .= 	'<tr> 
							<td>
								<div class="product-short-description">' . wpautop($this->data->short_description) . '</div>
							</td>
						</tr>';
		}
		$html .= '</table>';
		$html .= '<table class="product-info" width="100%">';
		$html .= '<tr>';
			$html .= '<td class="featured-image" style="width:30%;">';
			if($showImage) {
				$html .= $featured_image;
			}
			$html .= '</td>';
			$html .= '<td class="product-meta-container" valign="top" style="width:55%; padding-left: 5%;">';
				$html .= '<table class="product-metas" width="100%" style="padding: 0 0 10px 0;">';
					$html .= '<tr>';
							if($showQR) {
								$html .=  '<td class="qr-code">';
								$html .= '<barcode code="' . get_permalink($this->data->ID) . '" type="QR" class="barcode" size="1.0" error="M" />';
								$html .=  '</td>';
							}
							if($showMetaFreetext && !empty($metaFreeText)) {
								$html .= '<td class="meta-free-text">';
								$html .= wpautop($metaFreeText);
								$html .= '</td>';
							}
						
					$html .= '</tr>';
				$html .= '</table>';

				if($showSKU) {
					$html .= '<div class="product-sku"><b>'.__( 'SKU:', 'woocommerce'  ). '</b> '. $this->data->sku . '</div>';
				}
				if($showPrice) {
					$html .= '<div class="product-price"><b>'.__( 'Price:', 'woocommerce'  ). '</b> '. $this->data->price. '</div>';
				}
				if($showCategories) {
					$html .= '<div class="product-categories">' . $this->data->categories. '</div>';
				}
				if($showTags) {
					$html .= '<div class="product-tags">' . $this->data->tags. '</div>';
				}
			 	if(is_array($this->data->meta_keys)) {
			 		$html .= '<p>';
			 		foreach ( $this->data->meta_keys as $meta_key){
			 			$html .= '<b>' . $meta_key['before'] . '</b>:' . $meta_key['value'];
			 		}
			 		$html .= '</p>';
			 	}

				$html .= $this->get_attributes_table();

			$html .= '</td>';
		$html .= '</tr>';

		$html .= '</table>';
					
		return $html;
	}

	public function get_pagebreak()
	{
		$html = '<div class="page-break"></div><pagebreak />';
		return $html;
	}

    public function get_description()
    {
    	if(!$this->get_option('showDescription')) return FALSE;

    	if(empty($this->data->description)) return FALSE;

    	ob_start();

    	?>

		<table class="title description-title">
			<tr>
				<td>
					<h2><?php echo __( 'Product Description', 'woocommerce' ) ?></h2>
				</td>
			</tr>
		</table>
		<table class="description">
			<?php
			$description = wpautop($this->data->description);
			$description = preg_replace("/\[[^\]]+\]/", '', $description);
			$description = explode('<p>', $description);
			foreach ($description as $value) {
			?>
				<tr>
					<td  style="padding-bottom: 20px;">
						<?php echo $value ?>
					</td>
				</tr>
				<?php
			}
			?>

		</table>

		<?php

		return ob_get_clean();
    }

    public function get_attributes_table()
    {
    	if(!$this->get_option('showAttributes')) return FALSE;

    	$product = $this->product;

		$has_row    = false;
		$alt        = 1;
		$attributes = $product->get_attributes();

		ob_start();

		?>
		<table class="title attributes-title">
			<tr>
				<td>
					<h2><?php echo __( 'Additional Information', 'woocommerce' ) ?></h2>
				</td>
			</tr>
		</table>

		<table class="attributes">

			<?php if ( $product->has_weight() ) : $has_row = true; ?>
				<tr class="<?php if ( ( $alt = $alt * -1 ) === 1 ) echo 'alt'; ?>">
					<th><?php _e( 'Weight', 'woocommerce' ) ?></th>
					<td>
					<?php
						if( version_compare( $this->woocommerce_version, '3.0.0', ">=" ) ) {
							echo esc_html( wc_format_weight( $product->get_weight() ) );
						} else {
							echo $product->get_weight() . ' ' . esc_attr( get_option( 'woocommerce_weight_unit' ) );
						}
					?></td>
				</tr>
			<?php endif; ?>

			<?php if ( $product->has_dimensions() ) : $has_row = true; ?>
				<tr class="<?php if ( ( $alt = $alt * -1 ) === 1 ) echo 'alt'; ?>">
					<th><?php _e( 'Dimensions', 'woocommerce' ) ?></th>
					<td>
					<?php
						if( version_compare( $this->woocommerce_version, '3.0.0', ">=" ) ) {
							echo esc_html( wc_format_dimensions( $product->get_dimensions( false ) ) );
						} else {
							echo $product->get_dimensions(); 
						}
					?></td>
				</tr>
			<?php endif; ?>

			<?php foreach ( $attributes as $attribute ) :
				if ( empty( $attribute['is_visible'] ) || ( $attribute['is_taxonomy'] && ! taxonomy_exists( $attribute['name'] ) ) ) {
					continue;
				} else {
					$has_row = true;
				}
				?>
				<tr class="<?php if ( ( $alt = $alt * -1 ) == 1 ) echo 'alt'; ?>">
					<th><?php echo wc_attribute_label( $attribute['name'] ); ?></th>
					<td><?php
						if ( $attribute['is_taxonomy'] ) {

							$values = wc_get_product_terms( $product->get_id(), $attribute['name'], array( 'fields' => 'names' ) );
							echo apply_filters( 'woocommerce_attribute', wpautop( wptexturize( implode( ', ', $values ) ) ), $attribute, $values );

						} else {

							// Convert pipes to commas and display values
							$values = array_map( 'trim', explode( WC_DELIMITER, $attribute['value'] ) );
							echo apply_filters( 'woocommerce_attribute', wpautop( wptexturize( implode( ', ', $values ) ) ), $attribute, $values );

						}
					?></td>
				</tr>
			<?php endforeach; ?>

		</table>
		<?php
		if ( $has_row ) {
			return ob_get_clean();
		} else {
			ob_end_clean();
		}

    }

    public function get_reviews()
    {
    	if(!$this->get_option('showReviews')) return FALSE;

    	$product = $this->product;

		if ( ! comments_open() ) {
			return;
		}

		$comments = get_comments(array(
			'post_id' => $product->get_id(),
			'status' => 'approve' //Change this to the type of comments to be displayed
		));

		ob_start();

		?>
		<table class="title reviews-title">
			<tr>
				<td>
					<h2>
					<?php
						if(empty($comments))
						{
							echo '<p class="woocommerce-noreviews">'. __( 'There are no reviews yet.', 'woocommerce' ) .'</p>';
						} 
						elseif ( get_option( 'woocommerce_enable_review_rating' ) === 'yes' && ( $count = $product->get_review_count() ) )
						{
							echo sprintf( _n( '%s review for %s', '%s reviews for %s', $count, 'woocommerce' ), $count, get_the_title() );
						}
						else {
							echo __( 'Reviews', 'woocommerce' );
						}
					?>
					</h2>
				</td>
			</tr>
		</table>
		<table class="comments" style="vertical-align: top;">
		<?php

		foreach ($comments as $comment) {
			$rating   = intval( get_comment_meta( $comment->comment_ID, 'rating', true ) );
			$verified = wc_review_is_from_verified_owner( $comment->comment_ID );

		?>
			<tr class="comment_container">

				<td class="avatar" style="padding-bottom: 50px;">
					<?php echo get_avatar( $comment, apply_filters( 'woocommerce_review_gravatar_size', '60' ), '' ); ?>
				</td>

				<td class="comment-text" style="text-align: left;" valign="top">

					<p class="meta">
						<strong itemprop="author"><?= $comment->comment_author ?></strong> &ndash; <time itemprop="datePublished"><?php echo $comment->comment_date ?></time>
						<?php if ( $rating && get_option( 'woocommerce_enable_review_rating' ) === 'yes' ) : ?>

							<div itemprop="reviewRating" itemscope itemtype="http://schema.org/Rating" class="star-rating" title="<?php echo sprintf( __( 'Rated %d out of 5', 'woocommerce' ), $rating ) ?>">
								<span style="width:<?php echo ( $rating / 5 ) * 100; ?>%"><strong itemprop="ratingValue"><?php echo $rating; ?></strong> <?php _e( 'out of 5', 'woocommerce' ); ?></span>
							</div>

						<?php endif; ?>

						
					</p>

					<p><?= $comment->comment_content; ?></div>

				</td>
			</tr>
			<?php
		}

		?>
		</table>
		<?php
		return ob_get_clean();
    }

    public function get_upsells()
    {
		if(!$this->get_option('showUpsells')) return FALSE;

		$product = $this->product;

		if( version_compare( $this->woocommerce_version, '3.0.0', ">=" ) ) {
			$upsells = $product->get_upsell_ids();
		} else {
			$upsells = $product->get_upsells();
		}

		if ( sizeof( $upsells ) === 0 ) {
			return;
		}

		ob_start();

		$meta_query = WC()->query->get_meta_query();

		$args = array(
			'post_type'           => 'product',
			'ignore_sticky_posts' => 1,
			'no_found_rows'       => 1,
			'posts_per_page'      => $posts_per_page,
			'orderby'             => $orderby,
			'post__in'            => $upsells,
			'post__not_in'        => array( $product->get_id() ),
			'meta_query'          => $meta_query
		);

		$upsells = new WP_Query( $args );
		$upsells = $upsells->get_posts();

		if ( !empty($upsells)) { ?>

			<table class="title upsells-title">
				<tr>
					<td>
						<h2>
						<?php echo __( 'You may also like&hellip;', 'woocommerce' ) ?></h2>
					</td>
				</tr>
			</table>

			<table class="upsells products">

				<?php
				$max = 4;
				echo '<tr>';
				for ($i=0; $i < $max; $i++) { 

					if ( has_post_thumbnail($upsells[$i]->ID)) { 
						$thumbnail = wp_get_attachment_image_src( get_post_thumbnail_id($upsells[$i]->ID), 'shop_single' ); 
						$src = $thumbnail[0];
					} else { 
						$src = plugin_dir_url( __FILE__ ) . 'img/placeholder.png';
					}
					$featured_image = '<img width="200px" src="' . $src . '" >';
					$permalink = get_permalink($upsells[$i]->ID);
					$title = $upsells[$i]->post_title;
					$short_description = $upsells[$i]->post_excerpt;

					echo '<td width="25%;">';
					if(isset($upsells[$i]) && !empty($upsells[$i]))
					{
						echo '<a href="' . $permalink . '" target="_blank">';
						echo $featured_image;
						echo '<br/><br/>';
						echo '<h3>' . $title . '</h3>';
						echo '</a>';
						//echo $upsells[$i]->post_excerpt;
					}
					echo '</td>';
				}
				echo '</tr>';
				?>

			</table>

		<?php
		} else {
			return FALSE;
		}

		wp_reset_postdata();

		return ob_get_clean();
    }

    public function get_gallery_images()
    {
    	global $woocommerce;
		if(!$this->get_option('showGalleryImages')) return FALSE;
		$layout = $this->get_option('layout');

		$product = $this->product;

		ob_start();

		if( version_compare( $this->woocommerce_version, '3.0.0', ">=" ) ) {
			$attachment_ids = $product->get_gallery_image_ids();
		} else {
			$attachment_ids = $product->get_gallery_attachment_ids();
		}

		$count_attachment_ids = count($attachment_ids);
		if($layout == "3") {
			$count_attachment_ids--;
		}

		if ( $count_attachment_ids >= 1 ) {
		?>

			<table class="title gallery-images-title">
				<tr>
					<td>
						<h2>
						<?php echo __( 'Gallery Images', 'woocommerce-print-products' ) ?></h2>
					</td>
				</tr>
			</table>
		<?php
			$loop 				= 0;
			$custom_columns = $this->get_option('showGalleryImagesColumns');
			isset($custom_columns) ? $columns = $custom_columns : $columns = 3;

			$showGalleryImagesSize = $this->get_option('showGalleryImagesSize');
			if(!empty($showGalleryImagesSize)) {
				$galleryImageSize = $showGalleryImagesSize;
			} else {
				$galleryImageSize = '200';
			}
			?>
			<table class="woocommerce-print-products-gallery-images gallery-images-table">
			<?php
				$customBreak = true;
				foreach ( $attachment_ids as $attachment_id ) {

					if($layout == "3" && $customBreak == true) {
						$customBreak = false;
						continue;
					}

					$classes = array( 'zoom' );

					if ( $loop === 0 || $loop % $columns === 0 )
					{
						echo "<tr>";
						$classes[] = 'first';
					}

					$thumbnail = wp_get_attachment_image_src( $attachment_id, 'shop_single' ); 
					

					if($this->get_option('showGalleryImagesTitle')) {
						$image_title 	= '<br/>' . esc_attr( get_the_title( $attachment_id ) );
					} else {
						$image_title 	= '';
					}
					if($this->get_option('showGalleryImagesCaption')) {
						$image_caption 	= '<br/>' . esc_attr( get_post_field( 'post_excerpt', $attachment_id ) );
					} else {
						$image_caption 	= '';
					}
					if($this->get_option('showGalleryImagesAlt')) {
						$image_alt = '<br/>' . get_post_meta($attachment_id, '_wp_attachment_image_alt', true);
					} else {
						$image_alt 	= '';
					}
					if($this->get_option('showGalleryImagesDescription')) {
						$image_description 	= '<br/>' . esc_attr( get_post_field( 'post_content', $attachment_id ) );
					} else {
						$image_description 	= '';
					}						

					$src = $thumbnail[0];

					$gallery_image = '<img width="' . $galleryImageSize . 'px" src="' . $src . '" >';

					$image_class = esc_attr( implode( ' ', $classes ) );

					echo sprintf( '<td valign="top" class="%s">%s %s %s %s %s</td>', $image_class, $gallery_image, $image_title, $image_caption, $image_alt, $image_description);

					if ( ( $loop + 1 ) % $columns === 0 )
					{
						echo "</tr>";
						$classes[] = 'last';
					}
					$loop++;
				}

			?>
			</table>
			<?php
		} else {
			return FALSE;
		}

		return ob_get_clean();
    }

    public function get_variations()
    {
		$product = $this->product;

		ob_start();

		if($product->is_type( 'variable' ))
		{
			$attributes = $product->get_attributes();
			$available_variations = $product->get_available_variations();

			if(!empty($available_variations))
			{
			?>
				<table class="title variations-title">
					<tr>
						<td>
							<h2>
							<?php echo __( 'Variations', 'woocommerce-print-products' ) ?></h2>
						</td>
					</tr>
				</table>
				<table class="woocommerce-print-products-variations variations-table">
			 		<thead>
			            <tr>
			            	<?php if($this->get_option('showVariationImage')){ ?>
			                <th><?php _e( 'Image', 'woocommerce-print-products' ); ?></th>
			                <?php } ?>

			            	<?php if($this->get_option('showVariationSKU')){ ?>
			                <th><?php _e( 'SKU', 'woocommerce-print-products' ); ?></th>
			                <?php } ?>

				            <?php if($this->get_option('showVariationPrice')){ ?>
				            <th><?php _e('Price', 'woocommerce-print-products') ?></th>
				            <?php } ?>

			                <?php if($this->get_option('showVariationDescription')){ ?>
			                <th><?php _e('Description', 'woocommerce-print-products') ?></th>
			                <?php } ?>

			                <?php if($this->get_option('showVariationAttributes')){ ?>
				            <th><?php _e('Attributes', 'woocommerce-print-products') ?></th>
				            <?php } ?>
							
			            </tr>
			        </thead>
					<tbody>
			        <?php foreach ($available_variations as $variation) : ?>
			            <?php
			               	$variation_product = $variation;
			            ?>
			            <tr>
			            <?php
						if (isset($variation['image_src'])){
							$image = $variation['image_src'];
						} elseif(isset($variation['image']['src'])) {
							$image = $variation['image']['src'];
						} else {
							$image = 0;
						}

						if( version_compare( $this->woocommerce_version, '3.0.0', ">=" ) ) {
							if (!$image) $image = wc_placeholder_img_src();
						} else {
							if (!$image) $image = woocommerce_placeholder_img_src();
						}
						?>
							<?php if($this->get_option('showVariationImage')){ ?>
							<td class="variations-image"><?php echo '<img width="150px" src="'.$image.'">' ?></td>
							<?php } ?>
			            	<?php if($this->get_option('showVariationSKU')){ ?>
			                <td class="variations-sku"><?php echo $variation_product['sku'] ?></td>
			                <?php } ?>

			                <?php if($this->get_option('showVariationPrice')){ ?>
			                <td class="variations-price"><?php echo $variation_product['price_html'] ?></td>
			                <?php } ?>

			                <?php if($this->get_option('showVariationDescription')){ ?>
			                <td class="variations-description"><?php echo $variation_product['description'] ?></td>
			            	<?php } ?>

			                <?php if($this->get_option('showVariationAttributes')){ ?>
			                	<td class="variations-attributes">
				           		<?php foreach ($variation_product['attributes'] as $attr_name => $attr_value) : ?>
				                <?php
				                    // Get the correct variation values
				                    if (strpos($attr_name, '_pa_')){ // variation is a pre-definted attribute
				                        $attr_name = substr($attr_name, 10);
				                        $attr = get_term_by('slug', $attr_value, $attr_name);
				                        $attr_value = $attr->name;

				                        $attr_name = wc_attribute_label($attr_name);
				                    } else {
				                        $attr = maybe_unserialize( get_post_meta( $product->get_id(), '_product_attributes' ) );
				                        $attr_name = substr($attr_name, 10);
				                        $attr_name = $attr[0][$attr_name]['name'];
				                        
				                    }
				                    echo '<b>'.$attr_name.'</b>:';
				                    echo $attr_value;
				                    echo '<br />'
				                ?>
				                
				            <?php endforeach;?>
				            </td>
				             <?php } ?>
			            </tr>
			        <?php endforeach;?>
			        </tbody>
			    </table>
        <?php
        	} else {
        		return FALSE;
        	}
	    } else{
	    	return FALSE;
	    }

	    return ob_get_clean();
    }

    private function escape_filename($file)
    {
		// everything to lower and no spaces begin or end
		$file = strtolower(trim($file));

		// adding - for spaces and union characters
		$find = array(' ', '&', '\r\n', '\n', '+',',');
		$file = str_replace ($find, '-', $file);

		//delete and replace rest of special chars
		$find = array('/[^a-z0-9\-<>]/', '/[\-]+/', '/<[^>]*>/');
		$repl = array('', '-', '');
		$file = preg_replace ($find, $repl, $file);

		return $file;
    }

	/**
	 * Exclude Product categories
	 *
	 * @since    1.1.8
	 */
    public function excludeProductCategories()
    {
    	global $post;

		$excludeProductCategories = $this->get_option('excludeProductCategories');
		$excludeProductCategoriesRevert = $this->get_option('excludeProductCategoriesRevert');

		$terms = get_the_terms( $post->ID, 'product_cat' );
		if($terms)
		{
			foreach ($terms as $term)
			{
				if($excludeProductsRevert) {
					if(!in_array($term->term_id, $excludeProductCategories))
					{
						return TRUE;

					}
				} else {
					if(in_array($term->term_id, $excludeProductCategories))
					{
						return TRUE;

					}
				}
			}
		}
    }

	/**
	 * Exclude Products
	 *
	 * @since    1.1.8
	 */
    public function excludeProducts()
    {
    	global $post;

		$excludeProducts = $this->get_option('excludeProducts');
		$excludeProductsRevert = $this->get_option('excludeProductsRevert');
		if($excludeProductsRevert) {
			if(!in_array($post->ID, $excludeProducts))
			{
				return TRUE;
			}
		} else {
			if(in_array($post->ID, $excludeProducts))
			{
				return TRUE;
			}
		}
    }

	/**
	 * Return the current user role
	 *
	 * @since    1.0.0
	 */
	private function get_user_role()
	{
		global $current_user;

		$user_roles = $current_user->roles;
		$user_role = array_shift($user_roles);

		return $user_role;
	}
}
<?php

// Defines
define( 'FL_CHILD_THEME_DIR', get_stylesheet_directory() );
define( 'FL_CHILD_THEME_URL', get_stylesheet_directory_uri() );

// Classes
require_once 'classes/class-fl-child-theme.php';

//DISABLED - enqueue the child stylesheet properly using wp_enqueue_scripts
// Actions
// add_action( 'fl_head', 'FLChildTheme::stylesheet' );



function nh_jscss() {
  wp_enqueue_script('nh-custom-script', '//netdna.bootstrapcdn.com/twitter-bootstrap/2.3.0/js/bootstrap.min.js',array( 'jquery' ));
  wp_enqueue_script('nh-match-height', FL_CHILD_THEME_URL.'/js/jquery.matchHeight-min.js',array( 'jquery' ));
  wp_enqueue_script('nh-main', FL_CHILD_THEME_URL.'/js/nh-main.js',array( 'jquery' ));
  wp_enqueue_style('nh-bootstrapstyles', '//netdna.bootstrapcdn.com/twitter-bootstrap/2.3.0/css/bootstrap-combined.min.css');
  wp_enqueue_style('open-sans-extrabold', '//fonts.googleapis.com/css?family=Open+Sans:800');
  wp_enqueue_style('nh-internal-child', get_stylesheet_directory_uri() . '/style.css' );
}

add_action( 'wp_enqueue_scripts', 'nh_jscss' );
add_action( 'wp_enqueue_style', 'nh_jscss' );

/**
*  Hide shipping rates when free shipping is available*
*
*  @param array $rates Array of rates found for the package
*  @param array $package The package array/object being shipped
*  @return array of modified rates
*
*/
function nh_hide_shipping_when_free_available($rates, $package) {
  if (isset($rates['free_shipping'])) {
    unset($rates['flat_rate']);
  }
  return $rates;
}
add_filter('woocommerce_package_rates', 'nh_hide_shipping_when_free_available', 10, 2);

if ( ! function_exists( 'is_woocommerce_active' ) ) {

  function is_woocommerce_active() {

    $path = 'woocommerce/woocommerce.php';

    static $active_plugin = null;

    $active_plugins = (array) get_option('active_plugins', array());

    error_log(json_encode($active_plugins));

    if ( is_multisite() ) {
      $active_plugins = array_merge($active_plugins, get_site_option('active_sitewide_plugins', array()));
    }

    return in_array($path, $active_plugins) || array_key_exists($path, $active_plugins);

  }
  
}




/* add moment.js 
============================================== */
 function nh_add_momentjs() {
  if (is_page(13541)) {
    wp_enqueue_script( 'nh_add_momentjs', get_stylesheet_directory_uri() . '/js/moment.js', array( 'jquery' ), '1.0', true );
  }
}

add_action('wp_enqueue_scripts', 'nh_add_momentjs');


// /**

//  Compare manual discount and coupon discount(s) (if applicable) and apply highest

//  Since we don't want our manual discount to stack on top of any possible coupons
//  being applied to the order, we'll need to first check if coupons are being used,
//  then compare the total amount of our manual discount to the total amount of any
//  coupons being used. Once we know which is a greater discount, we can apply that
//  and remove the other(s).

// */
// function nh_apply_highest_discount() {
//   global $woocommerce;
//   $cart = $woocommerce->cart;
//   $has_manual_discount = false;
//   $manual_discount = null;

//   # Check for the existence of a manual discount
//   if (is_array($cart->fees) && count($cart->fees) > 0) {
//     foreach ($cart->fees as $key => $obj) {
//       # This name MUST be the same as the name of the fee
//       # we set in the nh_custom_coupon_filter() function
//       if ($obj->name === 'Bulk Discount') {
//         $has_manual_discount = true;
//         $manual_discount = $cart->fees[$key];
//       }
//     }
//   }

//   # Turn our negative fee back into a positive number if exists
//   $manual_discount_total = ($has_manual_discount === true) ? ($manual_discount->amount * -1) : 0;

//   # Make sure coupons are enabled and a discount exists before wasting resources
//   if ((isset($cart->applied_coupons) && is_array($cart->applied_coupons)) && (count($cart->applied_coupons > 0)) && $has_manual_discount) {
//     $coupon_total = $cart->discount_total;
//     if ($manual_discount_total >= $coupon_total) {
//       $cart->applied_coupons = array();
//       $cart->coupon_discount_amounts = array();
//       $cart->coupon_applied_count = array();
//       $cart->discount_total = 0;
//     }
//     elseif ($coupon_total > $manual_discount_total) {
//       $cart->fees = array();
//       $cart->fee_total = 0;
//     }
//   }
// }
// add_action('woocommerce_calculate_totals', 'nh_apply_highest_discount');


add_filter( 'woocommerce_product_tabs', 'sb_woo_remove_reviews_tab', 98);
function sb_woo_remove_reviews_tab($tabs) {

 unset($tabs['reviews']);

 return $tabs;
}

// Allow exe or dmg for digital downloads
add_filter('upload_mimes', function($mimetypes, $user)
{
    // Only allow these mimetypes for admins or shop managers
    $manager = $user ? user_can($user, 'manage_woocommerce') : current_user_can('manage_woocommerce');

    if ($manager)
    {
        $mimetypes = array_merge($mimetypes, array (
            'exe' => 'application/octet-stream',
            'dmg' => 'application/octet-stream'
        ));
    }

    return $mimetypes;
}, 10, 2);


// adds notice at single product page above add to cart
 
add_action( 'woocommerce_single_product_summary', 'return_policy', 20 );
function return_policy() {
    echo '<a href="javascript:window.print()" class="printpage">Print Page Flyer</a>';
} 

add_action( 'fl_body_open', 'tnh_fl_body_open', 20 );

function tnh_fl_body_open() {
    // get_template_part('header-menu');
}

add_filter('body_class', 'tnh_body_class');

function tnh_body_class($classes) {

    if ( is_front_page() || is_page('homepage-logged-out') )
        $classes[] = 'nh-front-page';

    if ( is_user_logged_in() )
        $classes[] = 'user-logged-in';

    if ( ! is_user_logged_in() )
        $classes[] = 'user-logged-out';

    return $classes;
}


function tnh_button_shortcode($atts) {
  $atts = shortcode_atts( array(
    'url' => '#',
    'label' => 'Label',
    'color' => 'default',
    'target' => '_self',
    'icon' => '',
    'args' => ''
  ), $atts, 'tnh_button' );

  $args = explode(',', $atts['args']);

  $classes = array();

  if ( is_array($args) && in_array('noborder', $args) ) {
    $classes[] = 'noborder';
  }

  $classes = ' ' . implode(' ', $classes);

  $icon_left = '';
  $icon_right = '';

  if ( ! empty($atts['icon']) ) {

      $icon = '<i class="fl-button-icon fl-button-icon-after fa fa fa-angle-%s"></i>';
      $icon = sprintf($icon, $atts['icon']);

      if ( $atts['icon'] === 'left' ) {
        $icon_left = $icon; 
      }

      if ( $atts['icon'] === 'right' ) {
        $icon_right = $icon; 
      }

  } 

  $html = <<<EOT
    <a role="button" class="fl-button tnh-button color-{$atts['color']}$classes" target="{$atts['target']}" href="{$atts['url']}">
      $icon_left
      <span class="fl-button-text">{$atts['label']}</span>
      $icon_right 
    </a>
EOT;

  return $html;
}

add_shortcode('tnh_button', 'tnh_button_shortcode');

function tnh_accordion($atts, $content = '') {
	$atts = shortcode_atts( array(
		'label' => 'READ MORE',
		'class' => 'default'
	), $atts, 'tnh_accordion' );

	return '<span class="accordion-content">' . $content . '</span><span class="accordion-toggle color-' . $atts['class'] .'">' . $atts['label'] . '</span>';

}

add_shortcode('tnh_accordion', 'tnh_accordion_shortcode');

//Send all emails from info@thenewhuman.com
add_filter( 'wp_mail_from', 'your_email' );
function your_email( $original_email_address )
{
  return 'info@thenewhuman.com';
}
add_filter( 'wp_mail_from_name', 'custom_wp_mail_from_name' );
function custom_wp_mail_from_name( $original_email_from )
{
  return 'The New Human';
}
//Allows orders with a status of 'Processing' in WooCommerce to be edited
add_filter( 'wc_order_is_editable', 'wc_make_processing_orders_editable', 10, 2 );
function wc_make_processing_orders_editable( $is_editable, $order ) {
    if ( $order->get_status() == 'processing' ) {
        $is_editable = true;
    }

    return $is_editable;
}


add_filter('wpmenucart_menu_item_wrapper', 'wc_remove_menu_cart');

function wc_remove_menu_cart($menu_item) {

  if ( WC()->cart->get_cart_contents_count() == 0 || ! is_user_logged_in() ) {
        return '';
  }

  return $menu_item;

}

add_filter('wp_head', 'wc_remove_pagination');

function wc_remove_pagination() {

  if ( ! is_woocommerce_active() )
    return;

  if ( ! (is_shop() || is_product_category()) )
    return;

// Increase course description count
add_filter('course_admin_excerpt_length', 'increase_excerpt_length');

function increase_excerpt_length($length){
  return 1000;
}

  ?>

  <style type="text/css">
    .woocommerce-pagination {
      display: none;
    }
  </style>



  

  <?php

}




// Add EIN Field to Affiliate WP
add_action( 'show_user_profile', 'affwp_custom_extra_profile_fields', 10 );
add_action( 'edit_user_profile', 'affwp_custom_extra_profile_fields', 10 );
/**
 * Save the fields when the values are changed on the profile page
*/
function affwp_custom_save_extra_profile_fields( $user_id ) {
	if ( ! current_user_can( 'edit_user', $user_id ) )
		return false;
	update_user_meta( $user_id, 'ein', $_POST['ein'] );
}
add_action( 'personal_options_update', 'affwp_custom_save_extra_profile_fields' );
add_action( 'edit_user_profile_update', 'affwp_custom_save_extra_profile_fields' );
/**
 * Update the user's profile with the new ein value on affiliate registration
*/
function affwp_custom_user_register( $user_id ) {
    $user_id = $user_id ? $user_id : get_current_user_id();
    if ( isset( $_POST['affwp_ein'] ) ) {
        update_user_meta( $user_id, 'ein', $_POST['affwp_ein'] );
    }
}
add_action( 'user_register', 'affwp_custom_user_register' );
add_action( 'affwp_process_register_form', 'affwp_custom_user_register' );
/**
 * Make the ein field required and show an error message when not filled in
 * Requires AffiliateWP 1.1+
 */
function affwp_custom_process_register_form() {
	$affiliate_wp = affiliate_wp();
	if ( empty( $_POST['affwp_ein'] ) ) {
		$affiliate_wp->register->add_error( 'ein_invalid', 'Please enter your EIN Number' );
	}
	if (strlen ($_POST['affwp_ein']) != 10 ) {
		$affiliate_wp->register->add_error( 'ein_invalid', 'Please enter a valid EIN Number' );
	}
}
add_action( 'affwp_process_register_form', 'affwp_custom_process_register_form' );

// Skill Certificate Verification for Network Update
//add_filter( 'https_local_ssl_verify', '__return_false' );

// Don't require website url w/ AWP
/**
 * Plugin Name: AffiliateWP - Make URL Field Not Required
 * Plugin URI: http://affiliatewp.com
 * Description: Makes the URL field on the affiliate registration form not required
 * Author: Andrew Munro, Sumobi
 * Author URI: http://sumobi.com
 * Version: 1.0
 */
function affwp_custom_make_url_not_required( $required_fields ) {
	unset( $required_fields['affwp_user_url'] );
	return $required_fields;
}
add_filter( 'affwp_register_required_fields', 'affwp_custom_make_url_not_required' );


// Activate WordPress Maintenance Mode
function wp_maintenance_mode(){
    if(!current_user_can('edit_themes') || !is_user_logged_in()){
        wp_die('<h1 style="color:red">New Human is currently under scheduled maintenance</h1><br />We are performing scheduled maintenance. We will be back online shortly!');
    }
}
//add_action('get_header', 'wp_maintenance_mode');



/**
 * Hide shipping rates when free shipping is available.
 * Updated to support WooCommerce 2.6 Shipping Zones.
 *
 * @param array $rates Array of rates found for the package.
 * @return array
 */
function my_hide_shipping_when_free_is_available( $rates ) {
	$free = array();
	foreach ( $rates as $rate_id => $rate ) {
		if ( 'free_shipping' === $rate->method_id ) {
			$free[ $rate_id ] = $rate;
			break;
		}
	}
	return ! empty( $free ) ? $free : $rates;
}
add_filter( 'woocommerce_package_rates', 'my_hide_shipping_when_free_is_available', 100 );


/** Sort A - Z Packing Slips **/
add_filter( 'wpo_wcpdf_order_items_data', 'wpo_wcpdf_sort_items_by_name', 10, 2 );
function wpo_wcpdf_sort_items_by_name ( $items, $order ) {
    usort($items, 'wpo_wcpdf_sort_by_name');
    return $items;
}

function wpo_wcpdf_sort_by_name($a, $b) {
    if (!isset($a['name'])) $a['name'] = '';
    if (!isset($b['name'])) $b['name'] = '';
    if ($a['name']==$b['name']) return 0;
    return ($a['name']<$b['name'])?-1:1;
}
<?php

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

/**
 * Filter Force Login to allow exceptions for specific URLs.
 *
 * @return array An array of URLs. Must be absolute.
 */
function my_forcelogin_whitelist( $whitelist ) {
  $whitelist[] = site_url( '/' );
  $whitelist[] = site_url( '/terms/' );
  $whitelist[] = site_url( '/my-account/' );
  $whitelist[] = site_url( '/my-account/lost-password/' );
  $whitelist[] = site_url( '/my-account/lost-password/?' . $_SERVER['QUERY_STRING'] );
  $whitelist[] = site_url( '/sv2-insight/' );
  $whitelist[] = site_url( '/bionetics/' );
  $whitelist[] = site_url( '/testimonials/' );
  $whitelist[] = site_url( '/iabc/' );
  $whitelist[] = site_url( '/sv2-offer/' );
  $whitelist[] = site_url( '/sv2-webinar/' );
  $whitelist[] = site_url( '/sv2-offer' );
  $whitelist[] = site_url( '/sv2-webinar' );
  return $whitelist;
}
add_filter('v_forcelogin_whitelist', 'my_forcelogin_whitelist', 10, 1);


// Redirect home if trying to access /wp-login.php
add_action('init','custom_login');
function custom_login(){
 global $pagenow;
 if( 'wp-login.php' == $pagenow ) {
  wp_redirect( site_url( '/my-account' ) );
  exit();
 }
}
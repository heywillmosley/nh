<?php

// Defines
define('FL_CHILD_THEME_DIR', get_stylesheet_directory());
define('FL_CHILD_THEME_URL', get_stylesheet_directory_uri());

/* Load custom login page styles */
function my_custom_login() {
echo '<link rel="stylesheet" type="text/css" href="' . get_bloginfo('stylesheet_directory') . '/login/custom-login-styles.css" />';
}
add_action('login_head', 'my_custom_login');

// Classes
require_once 'classes/FLChildTheme.php';


// Actions
add_action('fl_head', 'FLChildTheme::stylesheet');

/* Custom redirect after login to homepage */
function login_redirect( $redirect_to, $request, $user ){
    return home_url('/');
}
add_filter( 'login_redirect', 'login_redirect', 10, 3 );


/**
 * Filter Force Login to allow exceptions for specific URLs.
 *
 * @return array An array of URLs. Must be absolute.
 */
function my_forcelogin_whitelist( $whitelist ) {
  $whitelist[] = site_url( '/' );
  $whitelist[] = site_url( '/terms/' );
  $whitelist[] = site_url( '/features/' );
  $whitelist[] = site_url( '/client/' );
  $whitelist[] = site_url( '/about/' );
  $whitelist[] = site_url( '/help/' );
  $whitelist[] = site_url( '/ux/' );
  $whitelist[] = site_url( '/institute/' );
  $whitelist[] = site_url( '/my-account/' );
  $whitelist[] = site_url( '/my-account/lost-password/' );
  $whitelist[] = site_url( '/my-account/lost-password/?' . $_SERVER['QUERY_STRING'] );
  $whitelist[] = site_url( '/spencer/' );
  return $whitelist;
}
add_filter('v_forcelogin_whitelist', 'my_forcelogin_whitelist', 10, 1);

// Change user role upon course completion
add_action('coursepress_set_course_completed' , 'change_role_certified', 10, 2);
function change_role_certified( $student_id, $course_id ) {
  $current_user = wp_get_current_user();
  $current_user->remove_role('newhuman_member');
  $current_user->add_role('certified_iabc');
}


if ( is_user_logged_in() ) {
    add_filter('body_class','add_role_to_body');
    add_filter('admin_body_class','add_role_to_body');
}
function add_role_to_body($classes) {
    $current_user = new WP_User(get_current_user_id());
    $user_role = array_shift($current_user->roles);
    if (is_admin()) {
        $classes .= 'role-'. $user_role;
    } else {
        $classes[] = 'role-'. $user_role;
    }
    return $classes;
}

//bp_adminbar_notifications_menu();


@ini_set( 'upload_max_size' , '64M' );
@ini_set( 'post_max_size', '64M');
@ini_set( 'max_execution_time', '300' );

setcookie(TEST_COOKIE, 'WP Cookie check', 0, COOKIEPATH, COOKIE_DOMAIN);
if ( SITECOOKIEPATH != COOKIEPATH ) setcookie(TEST_COOKIE, 'WP Cookie check', 0, SITECOOKIEPATH, COOKIE_DOMAIN);


// Advanced Custom Fields Options Page
if( function_exists('acf_add_options_page') ) {
	
	acf_add_options_page();
	
}
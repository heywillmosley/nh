<?php
/*
   Plugin Name: WM IABC Box
   Plugin URI: http://thenewhuman.com
   Description: Add custom shortcodes and hooks for IABC
   Version: 0.1
   Author: William Mosley, III
   Author URI: http://iabc.thenewhuman.com
   License: GPL2
   */

// Redirect logged in users on IABC
/**
 * Redirect user after successful login.
 *
 * @param string $redirect_to URL to redirect to.
 * @param string $request URL the user is coming from.
 * @param object $user Logged user's data.
 * @return string
 */

function my_login_redirect( $redirect_to, $request, $user ) {
	//is there a user to check?
	if ( isset( $user->roles ) && is_array( $user->roles ) ) {
		//check for admins
		if ( in_array( 'administrator', $user->roles ) ) {
			// redirect them to the default place
			return home_url() . '/forums/forum/iabc/';
		} else {
			return home_url() . '/forums/forum/iabc/';
		}
	} else {
		return home_url() . '/forums/forum/iabc/';
	}
}

add_filter( 'login_redirect', 'my_login_redirect', 10, 3 );
<?php

// Defines
define( 'FL_CHILD_THEME_DIR', get_stylesheet_directory() );
define( 'FL_CHILD_THEME_URL', get_stylesheet_directory_uri() );

#Store Apps create an extremely spammy popup on all order pages. Hotfix please. WM
add_action('admin_head', 'storeapps_hotfix');

function storeapps_hotfix() {
  echo '<style>
    .post-type-shop_order #TB_window, 
    .post-type-shop_order #TB_overlay, 
    .post-type-shop_order .ig_content, 
    .post-type-product #TB_window, 
    .post-type-product #TB_overlay, 
    .post-type-product .ig_content {
        display: none !important;
        position: relative !important;
    }
    body.modal-open.post-type-shop_order,
    body.modal-open.post-type-product {
        overflow: visible !important;
    }
  </style>';
}

// Redirect to custom homepage upon login
add_action('wp', 'add_login_check');
function add_login_check()
{
    if ( is_user_logged_in() && is_page( [ 90, // login
                                          //93, // lostpassword
                                          //94, // resetpass
                                          3987000 // home
                                          ] ) ) {
        wp_redirect('/menu');
        exit;
    }
    
    if ( !is_user_logged_in() && is_page( [30688888 // Random Disable
                                          ] ) ) {
        wp_redirect('/login');
        exit;
    }
}


// Subscribe to MC List upon new user registration
add_action( 'user_register', 'registration_to_mc', 10, 1 );

function registration_to_mc( $user_id ) {
    
    $user_info = get_userdata( $user_id );
    
    $email = $user_info->user_email;;
    $list_id = 'a981a755fb';
    $api_key = '07d44c5c8c9ed311e2b60f9c9a1fb23e-us5';
     
    $data_center = substr($api_key,strpos($api_key,'-')+1);
     
    $url = 'https://'. $data_center .'.api.mailchimp.com/3.0/lists/'. $list_id .'/members';
     
    $json = json_encode([
        'email_address' => $email,
        'status'        => 'subscribed', //pass 'subscribed' or 'pending'
    ]);
     
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_USERPWD, 'user:' . $api_key);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
    $result = curl_exec($ch);
    $status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    echo $status_code;

}


/* Load custom login page styles */
function my_custom_login() {
echo '<link rel="stylesheet" type="text/css" href="' . get_bloginfo('stylesheet_directory') . '/login/custom-login-styles.css" />';
}
add_action('login_head', 'my_custom_login');

// Classes
require_once 'classes/class-fl-child-theme.php';

// Actions
add_action( 'wp_enqueue_scripts', 'FLChildTheme::enqueue_scripts', 1000 );


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
  $whitelist[] = site_url( '/' . $_SERVER['QUERY_STRING'] );
  $whitelist[] = site_url( '/login' . $_SERVER['QUERY_STRING'] );
  $whitelist[] = site_url( '/terms/' . $_SERVER['QUERY_STRING'] );
  $whitelist[] = site_url( '/my-account/' . $_SERVER['QUERY_STRING'] );
  $whitelist[] = site_url( '/my-account/lost-password/' . $_SERVER['QUERY_STRING'] );
  $whitelist[] = site_url( '/my-account/lost-password/?' . $_SERVER['QUERY_STRING'] );
  $whitelist[] = site_url( '/my-account/customer-logout/' . $_SERVER['QUERY_STRING'] );
  $whitelist[] = site_url( '/my-account/customer-logout/?' . $_SERVER['QUERY_STRING'] );
  $whitelist[] = site_url( '/account/' . $_SERVER['QUERY_STRING'] );
  $whitelist[] = site_url( '/account/lost-password/' . $_SERVER['QUERY_STRING'] );
  $whitelist[] = site_url( '/account/lost-password/?' . $_SERVER['QUERY_STRING'] );
  $whitelist[] = site_url( '/account/customer-logout/' . $_SERVER['QUERY_STRING'] );
  $whitelist[] = site_url( '/account/customer-logout/?' . $_SERVER['QUERY_STRING'] );
  $whitelist[] = site_url( '/sv2-insight/' . $_SERVER['QUERY_STRING'] );
  $whitelist[] = site_url( '/bionetics/' . $_SERVER['QUERY_STRING'] );
  $whitelist[] = site_url( '/testimonials/' . $_SERVER['QUERY_STRING'] );
  $whitelist[] = site_url( '/iabc/' . $_SERVER['QUERY_STRING'] );
  $whitelist[] = site_url( '/contact/' . $_SERVER['QUERY_STRING'] );
  $whitelist[] = site_url( '/about/' . $_SERVER['QUERY_STRING'] );
  $whitelist[] = site_url( '/botanicals/' . $_SERVER['QUERY_STRING'] );
  $whitelist[] = site_url( '/blog/' . $_SERVER['QUERY_STRING'] );
  $whitelist[] = site_url( '/training/' . $_SERVER['QUERY_STRING'] );
  $whitelist[] = site_url( '/sv2-webinar/?' . $_SERVER['QUERY_STRING'] );
  $whitelist[] = site_url( '/sv2-offer/?' . $_SERVER['QUERY_STRING'] );
  $whitelist[] = site_url( '/new-user/?' . $_SERVER['QUERY_STRING'] );
  $whitelist[] = site_url( '/p/' . $_SERVER['QUERY_STRING'] );
  $whitelist[] = site_url( '/wp-activate.php' . $_SERVER['QUERY_STRING'] );
  $whitelist[] = site_url( '/h' . $_SERVER['QUERY_STRING'] );
  $whitelist[] = site_url( '/expensify.txt' . $_SERVER['QUERY_STRING'] );
  $whitelist[] = site_url( '/sv2-webinar-2019' . $_SERVER['QUERY_STRING'] );
  $whitelist[] = site_url( '/ultimate-guide' . $_SERVER['QUERY_STRING'] );
  $whitelist[] = site_url( '/application-submitted' . $_SERVER['QUERY_STRING'] );
  $whitelist[] = site_url( '/repertory' . $_SERVER['QUERY_STRING'] );
  $whitelist[] = site_url( '/repertory/ageless' . $_SERVER['QUERY_STRING'] );
  $whitelist[] = site_url( '/team' . $_SERVER['QUERY_STRING'] );
  $whitelist[] = site_url( '/webinar' . $_SERVER['QUERY_STRING'] );
  $whitelist[] = site_url( '/webinar/request' . $_SERVER['QUERY_STRING'] );
  return $whitelist;
}
add_filter('v_forcelogin_whitelist', 'my_forcelogin_whitelist', 10, 1);

/**
 * Bypass Force Login to allow for exceptions.
 *
 * @return bool Whether to disable Force Login. Default false.
 */
function my_forcelogin_bypass( $bypass ) {
  if ( in_category('articles') 
    || is_home() 
    || is_front_page() 
    || is_page(153258) // SV2 Webinar
    || is_page(153145) // SV2 Offer
    || is_page(6740) // Training
    || is_page(90) // Login
    || is_page(160607) // New User
    || is_page(167135) // Partner Home/Apply
    || is_page(167822) // New Home Dev
    || is_page(174068) // Ultimate Guide
    || is_page(177818) // Application Submitted
    || is_page(155451) // Repertory
    || is_page(151322) // Repertory Ageless
    || is_page(160547) // Repertory Circulation
    || is_page(155461) // Repertory Detoxify
    || is_page(155463) // Repertory Digestion
    || is_page(157590) // Repertory Endocrine
    || is_page(155527) // Repertory Energy
    || is_page(155476) // Heart + Emotions
    || is_page(155529) // Immune
    || is_page(155531) // Pain + Inflammation
    || is_page(155537) // Pain + Sensitivities
    || is_page(155533) // Sleep
    || is_page(155535) // Stress
    || is_page(176197) // Team
    || is_page(239163) // Team
    || is_page(238950) // Team
    ) {
    $bypass = true;
  }
  return $bypass;
}
add_filter('v_forcelogin_bypass', 'my_forcelogin_bypass', 10, 1);

// Display ZD Help Button for logged in users
add_action('wp_head', 'display_zd_logged_in');
function display_zd_logged_in(){

	if (is_user_logged_in()) { ?>
	<!-- Start of newhuman1 Zendesk Widget script -->
    <script id="ze-snippet" src="https://static.zdassets.com/ekr/snippet.js?key=2a88c7d2-77bc-44e5-9925-e63fa468ad46"> </script>
    <!-- End of newhuman1 Zendesk Widget script -->

	<?php };

} // end function display_zd_logged_in()



##### Shortcodes ######
add_shortcode('webinar_dates', 'heywillmosley_webinar');
//echo heywillmosley_webinar();


function heywillmosley_webinar( $atts ) {

  // Ensure timezone is set to ET
  date_default_timezone_set('America/New_York');

    // set up default parameters
    extract( shortcode_atts( array(
     'start_date' => FALSE, // will default to today's date
     'timespan' => '1 year',
     'interval' => '1 week' // week
    ), $atts) );

    $interval_type = explode(" ", $interval); // day, week, month, year
    $interval_count = $interval_type[0]; // gets how many weeks, days, etc
    $type = $interval_type[1]; // get interval from string
    $initial_interval = $interval_count;
    $form_url = esc_url(get_permalink()) . 'request'; // current url + request

    $render = "<div class='container'>";
    $render .= "<div class='row'>";
    $render .= "<div class='col'>";


    $render .= "<table class='table'>";
    $render .= "<thead>";
    $render .= "<tr>";
    $render .= "<th scope='col'>Date</th>";
    $render .= "<th scope='col'>Attend</th>";
    $render .= "</tr>";
    $render .= "</thead>";
    $render .= "<tbody>";

    $end_date = strtotime( "$start_date +$timespan" ); // ends in 1 year
    $count = 0; // adds 1 week every loop
    $skip = $count;

    do {
        $cut = $count - $skip;
        if( $cut == 15 ) // only show certain #
            break;
        
        if( $count == 0 )
            $date = strtotime( "$start_date + 13 hours" );
        else {
            $date = strtotime( "$start_date +$interval_count $type" );
            $interval_count = $initial_interval + $interval_count;
        }
        
        if( strtotime("now") < $date  ) {

            $pretty_date = date('l F d, Y', $date );
            $url_date = date('m-d-Y', $date );
            $render .= "<tr>";
            $render .= "<td>$pretty_date 1PM EST</td>"; // Date
            $render .= "<td><a href='$form_url?d=$url_date'>Register</a></td>"; // Attend
            $render .= "</tr>";

            ++$count; // how many times we run through this loop

        } else {

            ++$count;
            ++ $skip;
        }

    } while ($date < $end_date);


    $render .= "</tbody>";
    $render .= "</table>";

    $render .= "</div>"; // col
    $render .= "</div>"; // row
    $render .= "</div>"; // container


    return $render;
}

function redirect_webinar_direct_access( ) {

    if( is_page( 238950 && !is_admin() ) ) { // webinar request page // Prod 238950 // Dev 4973
        if ( ! isset( $_GET[ 'd' ] ) || empty( $_GET[ 'd' ] ) ) {  // if date isn't set
            wp_redirect( home_url( '/webinar/' ) );
            exit();
        }
    }
}

add_action( 'template_redirect', 'redirect_webinar_direct_access' );

/*
 * 20200415 WM & IS : Handled redirection if someone stumbles on to the page accidentally
 * Tested to ensure it worked
 * 20200415 IS : Worked on zoom / vimeo get arguments and centering
 * 20200414 WM & IS : Created zoom viewer shortcode
 */
function heywillmosley_webinar_viewer() {

	$type = $_GET['type']; // is a a zoom or vimeo

  // Render based on type
	switch ( $type ) {

		case "vimeo":  // if the link is a vimeo link
			$url = "https://player.vimeo.com/video/" . $_GET['id'];
			
			$render = "<style>.embed-container { position: relative; padding-bottom: 56.25%; height: 0; overflow: hidden; max-width: 75%; height: auto; margin: 0 auto;} .embed-container iframe, .embed-container object, .embed-container embed { position: absolute; top: 0; left: 0; width: 100%; height: 100%; }</style>";
			$render .= "<div class='embed-container'><iframe src='$url' frameborder='0' webkitAllowFullScreen mozallowfullscreen allowFullScreen></iframe>";
			$render .= "</div>";

			break;
			
		case "zoom":  //if the link is a zoom link
			$url = "https://zoom.us/rec/share/" . $_GET['id'];
			
			$render = "<div class='iframe-container' style='overflow: hidden; padding-top: 56.25%; position: relative;'>";
			$render .= "<iframe allow='microphone; camera' style='border: 0; height: 100%; left: 0; position: absolute; top: 0; width: 100%;' src='$url' frameborder='0'></iframe>";
			$render .= "</div>";
			
			break;
			
		default:  //if it's not any of the above

	} // end switch
	
	echo $render;

} // end heywillmosley_webinar_viewer

add_shortcode('webinar_viewer', 'heywillmosley_webinar_viewer');
//echo heywillmosley_webinar();

function redirect_webinar_viewer( ) {


  if( is_page( 247411 ) && !is_admin() ) { // is webinar viewer page // Dev page 247100 Prod page 247411

    if ( !isset( $_GET[ 'type' ] ) ) {  // if date isn't set

        wp_redirect( home_url( '/webinars/' ) );
        exit();
    }
  }
}
// Redirect if Get isn't set
add_action( 'template_redirect', 'redirect_webinar_viewer' );

/* 
 * New Human Radio
*/
 
function radio_post_type() {
 
// Set UI labels for Custom Post Type
    $labels = array(
        'name'                => _x( 'Albums', 'New Human Radio Albums', 'twentytwenty' ),
        'singular_name'       => _x( 'Album', 'New Human Radio Album', 'twentytwenty' ),
        'menu_name'           => __( 'Albums', 'twentytwenty' ),
        'parent_item_colon'   => __( 'Parent Album', 'twentytwenty' ),
        'all_items'           => __( 'All Albums', 'twentytwenty' ),
        'view_item'           => __( 'View Album', 'twentytwenty' ),
        'add_new_item'        => __( 'Add New Album', 'twentytwenty' ),
        'add_new'             => __( 'Add New', 'twentytwenty' ),
        'edit_item'           => __( 'Edit Album', 'twentytwenty' ),
        'update_item'         => __( 'Update Album', 'twentytwenty' ),
        'search_items'        => __( 'Search Album', 'twentytwenty' ),
        'not_found'           => __( 'Not Found', 'twentytwenty' ),
        'not_found_in_trash'  => __( 'Not found in Trash', 'twentytwenty' ),
    );
     
// Set other options for Custom Post Type
     
    $args = array(
        'label'               => __( 'albums', 'twentytwenty' ),
        'description'         => __( 'New Human Radio albums with sounds for the SLT.', 'twentytwenty' ),
        'labels'              => $labels,
        // Features this CPT supports in Post Editor
        'supports'            => array( 'title', 'editor', 'excerpt', 'author', 'thumbnail', 'comments', 'revisions', 'custom-fields', ),
        // You can associate this CPT with a taxonomy or custom taxonomy. 
        'taxonomies'          => array( 'sounds' ),
        /* A hierarchical CPT is like Pages and can have
        * Parent and child items. A non-hierarchical CPT
        * is like Posts.
        */ 
        'hierarchical'        => false,
        'public'              => true,
        'show_ui'             => true,
        'show_in_menu'        => true,
        'show_in_nav_menus'   => true,
        'show_in_admin_bar'   => true,
        'menu_position'       => 5,
        'menu_icon'           => 'dashicons-format-audio',
        'can_export'          => true,
        'has_archive'         => true,
        'exclude_from_search' => false,
        'publicly_queryable'  => true,
        'capability_type'     => 'post',
        'show_in_rest' => true,
 
    );
     
    // Registering your Custom Post Type
    register_post_type( 'albums', $args );
 
}
 
/* Hook into the 'init' action so that the function
* Containing our post type registration is not 
* unnecessarily executed. 
*/
 
add_action( 'init', 'radio_post_type', 0 );
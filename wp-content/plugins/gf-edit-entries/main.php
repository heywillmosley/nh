<?php
/*
Plugin Name: Gravity Forms - Edit entries
Description: This plugin lets you edit the entries for the forms created with gravity
Author: Team Bright Vessel
Version: 0.1.3
Author URI: http://brightvessel.com/
*/

if (!defined('ABSPATH'))
    die();

if(class_exists('GFForms')){
    add_action( 'gform_entries_first_column_actions', 'bv_gravity_first_column_actions', 10, 4 );
    function bv_gravity_first_column_actions( $form_id, $field_id, $value, $entry ) {

        $lead_id = $entry['id'];
        $id = $entry['form_id'];
        echo "| <a href='admin.php?page=gf_entries&view=entry&id={$id}&lid={$lead_id}&order=ASC&filter&paged=1&pos=0&field_id&operator&edit=1'>Edit</a> ";

    }


    add_action( 'gform_entry_detail', 'bv_gravity_add_to_details', 10, 2 );
    function bv_gravity_add_to_details( $form, $entry ) {
        if(isset($_REQUEST['edit']) && $_REQUEST['edit'] == 1){
            echo '<script type="text/javascript">jQuery(document).ready(function(){jQuery("#gform_edit_button").click();});</script>';
            //document.getElementById("gform_edit_button").click();
        }
    }

}

function bv_create_support_notice() {
    $class = 'notice notice-warning';
    $message ='If you need dedicated/professional assistance with this plugin or just want an expert to get your site built and or to run the faster, you may hire us at';

    printf( '<div class="%1$s"><p>%2$s <a href="https://www.brightvessel.com/" target="_blank">Bright Vessel</a>. <small><a href="?bvclose=true">[x]</a></small></p></div>', esc_attr( $class ), esc_html( $message ) );
}

if(isset($_GET['bvclose']) && $_GET['bvclose'] == 'true'){
    add_option('bvclose',1);
}

if(intval(get_option('bvclose')) !== 1){
    add_action( 'admin_notices', 'bv_create_support_notice' );
}
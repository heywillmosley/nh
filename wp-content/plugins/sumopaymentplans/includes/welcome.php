<?php

add_action( 'admin_init' , '_sumo_pp_welcome_screen_do_activation_redirect' ) ;

function _sumo_pp_welcome_screen_do_activation_redirect() {
    if ( ! get_transient( '_welcome_screen_activation_redirect_payment_plans' ) ) {
        return ;
    }

    delete_transient( '_welcome_screen_activation_redirect_payment_plans' ) ;

    wp_safe_redirect( add_query_arg( array ( 'page' => 'sumopaymentplans-welcome-page' ) , admin_url( 'admin.php' ) ) ) ;
}

add_action( 'admin_menu' , '_sumo_pp_welcome_screen_pages' ) ;

function _sumo_pp_welcome_screen_pages() {
    add_dashboard_page(
            'Welcome To SUMO Payment Plans' , 'Welcome To SUMO Payment Plans' , 'read' , 'sumopaymentplans-welcome-page' , '_sumo_pp_welcome_screen_content'
    ) ;
}

function _sumo_pp_welcome_screen_content() {

    ob_start() ;

    _sumo_pp_get_template( 'sumo-pp-welcome-page.php' ) ;

    ob_get_contents() ;
}

add_action( 'admin_head' , '_sumo_pp_welcome_screen_remove_menus' ) ;

function _sumo_pp_welcome_screen_remove_menus() {
    remove_submenu_page( 'index.php' , 'sumopaymentplans-welcome-page' ) ;
}

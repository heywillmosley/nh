<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class WWOF_Permissions {

    /*
    |--------------------------------------------------------------------------
    | Class Properties
    |--------------------------------------------------------------------------
    */

    /**
     * Property that holds the single main instance of WWOF_Permissions.
     *
     * @since 1.6.6
     * @access private
     * @var WWOF_Permissions
     */
    private static $_instance;


    /*
    |--------------------------------------------------------------------------
    | Class Methods
    |--------------------------------------------------------------------------
    */

    /**
     * WWOF_Permissions constructor.
     *
     * @param array $dependencies Array of instance objects of all dependencies of WWOF_Permissions model.
     *
     * @access public
     * @since 1.6.6
     */
    public function __construct( $dependencies ) {}

    /**
     * Singleton Pattern.
     *
     * @since 1.6.6
     *
     * @return WWOF_Permissions
     */
    public static function instance( $dependencies = null ) {

        if ( !self::$_instance instanceof self )
            self::$_instance = new self( $dependencies );

        return self::$_instance;

    }

    /**
     * Check if site user has access to view the wholesale product listing page.
     *
     * @return bool
     * @since 1.0.0
     * @since 1.6.6 Underscore cased the function name and variables.
     */
    public function wwof_user_has_access() {

        global $current_user;
        $user_role_filters  = get_option( 'wwof_permissions_user_role_filter' );
        $has_permission     = false;

        if ( isset( $user_role_filters ) && is_array( $user_role_filters ) && !empty( $user_role_filters ) ){

            $combined_arrays = array_intersect( $current_user->roles,$user_role_filters );
            if( !empty( $combined_arrays ) )
                $has_permission = true;

        } else $has_permission = true;

        return apply_filters( 'wwof_filter_user_has_permission' , $has_permission , $user_role_filters );

    }

}
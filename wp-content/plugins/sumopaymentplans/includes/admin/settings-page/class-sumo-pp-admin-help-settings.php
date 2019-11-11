<?php

/**
 * Help Tab.
 * 
 * @class SUMO_PP_Help_Settings
 * @category Class
 */
class SUMO_PP_Help_Settings extends SUMO_PP_Abstract_Admin_Settings {

    /**
     * SUMO_PP_Help_Settings constructor.
     */
    public function __construct() {

        $this->id       = 'help' ;
        $this->label    = __( 'Help' , $this->text_domain ) ;
        $this->settings = $this->get_settings() ;
        $this->init() ;

        add_action( 'sumopaymentplans_reset_' . $this->id , array ( $this , 'remove_submit_and_reset' ) ) ;
        add_action( 'sumopaymentplans_submit_' . $this->id , array ( $this , 'remove_submit_and_reset' ) ) ;
    }

    /**
     * Get settings array.
     * @return array
     */
    public function get_settings() {
        global $current_section ;

        return apply_filters( 'sumopaymentplans_get_' . $this->id . '_settings' , array (
            array (
                'name' => __( 'Documentation' , $this->text_domain ) ,
                'type' => 'title' ,
                'id'   => $this->prefix . 'documentation' ,
                'desc' => __( 'The documentation file can be found inside the documentation folder  which you will find when you unzip the downloaded zip file.' , $this->text_domain ) ,
            ) ,
            array (
                'name' => __( 'Help' , $this->text_domain ) ,
                'type' => 'title' ,
                'id'   => $this->prefix . 'help' ,
                'desc' => __( 'If you need Help, please <a href="http://support.fantasticplugins.com" target="_blank" > register and open a support ticket</a>' , $this->text_domain ) ,
            ) ,
            array ( 'type' => 'sectionend' , 'id' => $this->prefix . 'help' ) ,
            array ( 'type' => 'sectionend' , 'id' => $this->prefix . 'documentation' ) ,
                ) ) ;
    }

    /**
     * Hide Submit and Reset options
     * @return boolean
     */
    public function remove_submit_and_reset() {
        return false ;
    }

}

return new SUMO_PP_Help_Settings() ;

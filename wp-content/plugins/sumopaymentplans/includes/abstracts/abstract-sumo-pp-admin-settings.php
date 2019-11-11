<?php

/**
 * Abstract SUMO Payment Plans Admin Settings Page
 * 
 * @abstract SUMO_PP_Abstract_Admin_Settings
 */
abstract class SUMO_PP_Abstract_Admin_Settings {

    /**
     * @var string Setting page id. 
     */
    protected $id = '' ;

    /**
     * @var string Setting page label.
     */
    protected $label = '' ;

    /**
     * @var array Get settings.
     */
    public $settings = array () ;

    /**
     * @var array Get sections.
     */
    public $sections = array () ;

    /**
     * @var string Get plugin prefix.
     */
    protected $prefix = SUMO_PP_PLUGIN_PREFIX ;

    /**
     * @var string Get plugin text domain.
     */
    protected $text_domain = SUMO_PP_PLUGIN_TEXT_DOMAIN ;

    /**
     * @var string Output custom fields type.
     */
    protected $custom_fields = array () ;

    /**
     * @var string Output custom settings field prefix.
     */
    protected $custom_field_prefix = 'woocommerce_admin_field_' ;

    /**
     * Init SUMO Payment Plans setting page
     */
    protected function init() {

        add_filter( 'sumopaymentplans_settings_tabs_array' , array ( $this , 'add_settings_page' ) ) ;
        add_action( 'sumopaymentplans_settings_' . $this->id , array ( $this , 'output' ) ) ;
        add_action( 'sumopaymentplans_sections_' . $this->id , array ( $this , 'output_sections' ) ) ;
        add_action( 'sumopaymentplans_add_options_' . $this->id , array ( $this , 'add_options' ) ) ;
        add_action( 'sumopaymentplans_update_options_' . $this->id , array ( $this , 'save' ) ) ;
        add_action( 'sumopaymentplans_reset_options_' . $this->id , array ( $this , 'reset' ) ) ;

        foreach ( $this->custom_fields as $type ) {
            add_action( $this->get_custom_field_hook( $type ) , array ( $this , $type ) ) ;
        }
    }

    /**
     * Get settings page ID.
     * @return string
     */
    public function get_id() {
        return $this->id ;
    }

    /**
     * Get settings page label.
     * @return string
     */
    public function get_label() {
        return $this->label ;
    }

    /**
     * Get settings array.
     *
     * @return array
     */
    public function get_settings() {
        return $this->settings ;
    }

    /**
     * Get settings array.
     *
     * @return array
     */
    public function get_sections() {
        return $this->sections ;
    }

    /**
     * Get custom field type hook
     * 
     * @param string $type
     * @return string
     */
    public function get_custom_field_hook( $type ) {
        return $this->custom_field_prefix . $this->get_custom_field_type( $type ) ;
    }

    /**
     * Get custom field type
     * 
     * @param string $type
     * @return string
     */
    public function get_custom_field_type( $type ) {
        return $this->text_domain . "_{$type}" ;
    }

    /**
     * Add this page to settings.
     *
     * @param array $pages
     * @return array
     */
    public function add_settings_page( $pages ) {
        $pages[ $this->id ] = $this->label ;

        return $pages ;
    }

    /**
     * Output the settings.
     */
    public function output() {
        woocommerce_admin_fields( $this->settings ) ;
    }

    /**
     * Output sections.
     */
    public function output_sections() {
        global $current_section ;

        if ( empty( $this->sections ) || 1 === sizeof( $this->sections ) ) {
            return ;
        }

        echo '<ul class="subsubsub">' ;

        $array_keys = array_keys( $this->sections ) ;

        foreach ( $this->sections as $id => $label ) {
            echo '<li><a href="' . admin_url( 'admin.php?page=sumo_pp_settings&tab=' . $this->id . '&section=' . sanitize_title( $id ) ) . '" class="' . ( $current_section == $id ? 'current' : '' ) . '">' . $label . '</a> ' . ( end( $array_keys ) == $id ? '' : '|' ) . ' </li>' ;
        }

        echo '</ul><br class="clear" />' ;
    }

    /**
     * Looping through each settings fields and save the option once.
     */
    public function add_options( $reset_all = false ) {

        if ( $reset_all && is_callable( array ( $this , 'custom_types_delete_options' ) ) ) {
            $this->custom_types_delete_options() ;
        }
        if ( is_callable( array ( $this , 'custom_types_add_options' ) ) ) {
            $this->custom_types_add_options() ;
        }

        foreach ( $this->settings as $setting ) {
            if ( isset( $setting[ 'newids' ] ) && isset( $setting[ 'std' ] ) ) {
                if ( $reset_all ) {
                    delete_option( $setting[ 'newids' ] ) ;
                }
                add_option( $setting[ 'newids' ] , $setting[ 'std' ] ) ;
            }
        }
    }

    /**
     * Save settings.
     */
    public function save() {
        woocommerce_update_options( $this->settings ) ;

        if ( is_callable( array ( $this , 'custom_types_delete_options' ) ) ) {
            $this->custom_types_delete_options() ;
        }
        if ( is_callable( array ( $this , 'custom_types_save' ) ) ) {
            $this->custom_types_save() ;
        }
    }

    /**
     * Reset settings.
     */
    public function reset() {

        if ( is_callable( array ( $this , 'custom_types_delete_options' ) ) ) {
            $this->custom_types_delete_options() ;
        }
        if ( is_callable( array ( $this , 'custom_types_add_options' ) ) ) {
            $this->custom_types_add_options() ;
        }

        foreach ( $this->settings as $setting ) {
            if ( isset( $setting[ 'newids' ] ) && isset( $setting[ 'std' ] ) ) {
                delete_option( $setting[ 'newids' ] ) ;
                add_option( $setting[ 'newids' ] , $setting[ 'std' ] ) ;
            }
        }
    }

}

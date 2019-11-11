<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit ; // Exit if accessed directly
}

/**
 * Abstract Payment Cron Job
 * 
 * @abstract SUMO_PP_Abstract_Payment_Cron_Job
 */
abstract class SUMO_PP_Abstract_Payment_Cron_Job {

    /**
     * @var int The Payment Cron Job post ID. Each payment job identifier.
     */
    protected $job_id = 0 ;

    /**
     * @var int The Payment post ID
     */
    protected $payment_id = 0 ;

    /**
     * @var array Scheduled Jobs for the Payment
     */
    public $jobs = array () ;

    /**
     * @var array Post Type for Cron Jobs
     */
    protected $post_type = 'sumo_pp_cron_jobs' ;

    /**
     * @var WP_Query object Cron Jobs query
     */
    public $query ;

    /**
     * Constructor.
     */
    public function __construct( $payment ) {
        $this->populate( $payment ) ;
    }

    /**
     * Populate the Payment Cron Job
     * @param int $payment The Payment post ID
     */
    protected function populate( $payment ) {
        if ( ! $payment ) {
            return false ;
        }

        $this->payment_id = absint( $payment ) ;
        $this->init_job() ;
        $this->job_id     = $this->get_job_id() ;
        $this->jobs       = $this->get_jobs() ;
    }

    /**
     * Init Cron Job for the Payment
     */
    public function init_job() {
        //Fire once for the Payment
        if ( ! $this->exists() ) {
            $this->job_id = wp_insert_post( array (
                'post_type'     => $this->post_type ,
                'post_status'   => 'publish' ,
                'post_date'     => _sumo_pp_get_date() ,
                'post_date_gmt' => _sumo_pp_get_date() ,
                'post_author'   => 1 ,
                'post_title'    => __( 'Payment Cron Jobs' , _sumo_pp()->text_domain ) ,
                    ) ) ;

            add_post_meta( $this->job_id , '_payment_id' , $this->payment_id ) ;
        }
    }

    /**
     * Get Cron Job ID
     */
    public function get_job_id() {

        if ( $this->exists() ) {
            $this->get_jobs_query() ;

            foreach ( $this->query->posts as $job ) {
                $this->job_id = $job->ID ;
                break ;
            }
        }

        return $this->job_id ;
    }

    /**
     * Check whether Cron Job exists
     * @return boolean
     */
    public function exists() {
        if ( ! $this->get_jobs_query() ) {
            return false ;
        }

        return $this->query->have_posts() ;
    }

    /**
     * Get Cron Jobs WP_Query data
     * @return \WP_Query|boolean
     */
    public function get_jobs_query() {
        if ( ! $this->payment_id ) {
            return false ;
        }

        $this->query = _sumo_pp()->query->get( array (
            'type'         => $this->post_type ,
            'status'       => 'publish' ,
            'limit'        => 1 ,
            'return'       => 'q' ,
            'meta_key'     => '_payment_id' ,
            'meta_value'   => $this->payment_id ,
            'meta_compare' => '=' ,
                ) ) ;
        return $this->query ;
    }

    /**
     * Get Cron Scheduled Jobs associated for the Payment
     * @return array
     */
    public function get_jobs() {
        $jobs = get_post_meta( $this->job_id , '_scheduled_jobs' , true ) ;

        if ( isset( $jobs[ $this->payment_id ] ) && is_array( $jobs[ $this->payment_id ] ) ) {
            $this->jobs = $jobs[ $this->payment_id ] ;
        }

        return $this->jobs ;
    }

    /**
     * Create Cron job to Schedule. It may be elapsed by wp_schedule_event
     * @param int $timestamp
     * @param string $job_name
     * @param array $args
     * @return boolean true on success
     */
    public function create_job( $timestamp , $job_name , $args = array () ) {

        if ( ! is_numeric( $timestamp ) || ! $timestamp || ! is_array( $args ) ) {
            return false ;
        }

        $new_arg    = array ( absint( $timestamp ) => $args ) ;
        $this->jobs = $this->get_jobs() ;

        if ( $this->exists() ) {
            //may the Job has multiple timestamps so that we are doing this way 
            if ( isset( $this->jobs[ $job_name ] ) && is_array( $this->jobs[ $job_name ] ) ) {
                $this->jobs[ $job_name ] += $new_arg ;
            } else {
                //may the new Job is registering
                $this->jobs[ $job_name ] = $new_arg ;
            }

            if ( $this->update_jobs() ) {
                return true ;
            }
        }

        return false ;
    }

    /**
     * Update Cron jobs
     * @return boolean true on success
     */
    public function update_jobs() {

        if ( ! is_array( $this->jobs ) ) {
            return ;
        }

        update_post_meta( $this->job_id , '_scheduled_jobs' , array (
            $this->payment_id => $this->jobs
        ) ) ;

        return true ;
    }

    /**
     * UnSchedule Cron jobs.
     * @param array $jobs unsetting every Cron Jobs if left empty
     */
    public function unset_jobs( $jobs = array () ) {

        if ( empty( $jobs ) ) {
            $jobs = array (
                'create_balance_payable_order' ,
                'notify_reminder' ,
                'notify_overdue' ,
                'notify_cancelled' ,
                    ) ;
        }
        $jobs       = ( array ) $jobs ;
        $this->jobs = $this->get_jobs() ;

        if ( $this->exists() ) {
            foreach ( $this->jobs as $job_name => $job_args ) {
                if ( in_array( $job_name , $jobs ) ) {
                    unset( $this->jobs[ $job_name ] ) ;
                }
            }

            $this->update_jobs() ;
        }
    }

}

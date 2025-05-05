<?php
/**
 * Importer Class
 *
 * Handles main import functionality tied to Bandit Feed configured to the site. 
 * For now can just run with wp eval 'do_action("wp_bandit_import");'
 *
 * @since       0.0.1
 *
 * @category    Controllers
 * @package     WP_Bandit\Controllers
 * @author      Jacob Stanaford
 */

namespace WP_Bandit\Controllers;

use WP_Bandit\Controllers\API;
use WP_Bandit\Controllers\Progress;
use WP_Bandit\Models\Equipment;
use WP_Bandit\Models\Family;
use \WP_Error;
use Exception;
use WP_CLI;
use SearchWP;

defined( 'ABSPATH' ) || exit;

final class Importer {

    /**
     * The single instance of the class.
     *
     * @var Importer
     */
    private static $instance = null;

    /**
     * Interval for running progress and WP CLI 
     * 
     * @var int
     */
    public $interval = 0; 
    
    /**
     * Total number of machines to import. 
     * 
     * @var int
     */
    public $total = 0;
    
    /**
     * Import start time placeholder for reference. 
     * 
     * @var string
     */
    public $import_start_time;

    /**
     * Singleton pattern
     *
     * Ensures only one instance of plugin is loaded or can be loaded
     *
     * @return  self
     */
    public static function instance() {
        if ( ! ( self::$instance instanceof Importer ) ) {
        self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Initializes class.
     * 
     * @since 0.0.1
     * 
     * @return void
     */
    private function __construct() {
        add_action( 'wp_ajax_wp_bandit_import', [ $this, 'import' ] );
        add_action( 'wp_ajax_nopriv_wp_bandit_import', [ $this, 'import' ] );
        add_action( 'wp_bandit_import', [ $this, 'import' ] );

    }

    /**
     * Configure the importer with any details needed from the settings page and alter environmental factors. 
     * 
     * @since 0.0.1
     * 
     * @return void
     */
    private function setup() {

        // stop SearchWP from indexing (we'll index at end of import)
        if( class_exists( 'SearchWP' ) ) {
            SearchWP::$indexer->pause();
        }
       // wp_suspend_cache_addition( true );

    }

    /**
     * Import function that is called by cron, ajax, or CLI as needed. 
     * 
     * @since 0.0.1
     * 
     * @return void
     */
    public function import() {
        @ini_set( 'memory_limit', '512M' );
        @ini_set( 'max_execution_time', 1800 );

        //Preliminary actions to main import
        $this->setup();
        $api = API::instance();
        $equipment_ids = (array)get_option('bandit_selected_products'); // an array of product post IDs (native to Bandit's install not this WP install)

        $this->import_start_time = gmdate( 'Y-m-d H:i:s' );
        $this->total    = count( $equipment_ids );
        $this->interval = 1;

        // if total less 1 then we exit early
        if ( $this->total === 0 ) {
            Progress::set( 0, 0, "There isn't any equipment to import" );
            return;
        }


        // set the initial Progress for equipment import
        Progress::set( 0, $this->total, 'Importing Equipment' );

        do_action( 'bandit_before_feed_import', $equipment_ids, $this->import_start_time, $this->total );

        foreach( $equipment_ids as $equipment_id ) {
            $post_json = $api->get_single(  $equipment_id );
            $model = new Equipment( $post_json );
            $model->families = Family::create_from_feed( $post_json );
            $model->save();

            // update import progress interval
            Progress::update( 'index', $this->interval );
            $this->interval++;

        }

        $this->cleanup();
        
        do_action( 'bandit_after_feed_import', $equipment_ids, $this->import_start_time, $this->total );

    }

    /**
     * Cleanup process after import has been completed. 
     * 
     * @since 0.0.1
     * 
     * @return void
     */
    public function cleanup() {
        // $this->delete_old_machines();  // TBD
        $this->reset();
        $this->notify();

    }

    /**
     * Delete old machines that are no longer in the feed. 
     * 
     * @since 0.0.1
     * 
     * @return void
     */
    private function delete_old_machines() {

        //fetch our results with wpdb 
        global $wpdb;
        $post_type = wp_bandit()->get_post_type();

        $query = $wpdb->prepare(
            "SELECT p.ID
                FROM {$wpdb->posts} p
                LEFT JOIN {$wpdb->postmeta} m ON p.ID = m.post_id
                WHERE post_type = %s
                    AND post_modified_gmt < %s
                    AND m.meta_key = 'is_bandit_feed'
                            AND m.meta_value = '1'
                ", $post_type, $this->import_start_time
        );
       
        $imported = $wpdb->get_results($query);
  
        if ( $imported ) {
            Progress::set( 0, count( $imported ), 'Deleting Old Equipment' );
            $index = 1;
            // loop through and delete those items
            foreach ( $imported as $i ) {
                // delete the post
                wp_delete_post( $i->ID, 1 );

                // delete meta
                $wpdb->get_results( "DELETE FROM {$wpdb->postmeta} WHERE post_id = {$i->ID} " );

                // Update progress
                Progress::update( 'index', $index );
                $index++;
            }
        }
        Progress::set( 0, 0, 'Finished' );
    }


    /**
     * Wrapper for resetting functionality for plugins
     * 
     * @return  void
     */
    public function reset() 
    {
        // re-enable SearchWP 
        if (class_exists( 'SearchWP' )){ 
            SearchWP::$indexer->unpause();
            SearchWP::$indexer->trigger();
        }

        // Clear WP Rocket cache if possible: 
        if ( function_exists( 'rocket_clean_domain' ) ) {
            rocket_clean_domain();
        }   

    }
    
    /**
     * Notify Admin email of importer completion here. 
     * 
     * @since 0.0.1
     * 
     * @return void
     */
    public function notify() {
        // @TODO Add email notification capabilities. 
    }


}



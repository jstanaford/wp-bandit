<?php
/**
 * Main Machine Family Model.
 *
 * Family Model for storing Families off of Bandit API.
 *
 * @since       0.0.1
 *
 * @category    Models
 * @package     WP_Bandit\Models
 * @author      Jacob Stanaford
 */

namespace WP_Bandit\Models;

use WP_Bandit\Controllers\API;

defined( 'ABSPATH' ) || exit;

final class Family {

    /**
     * The single instance of the class.
     *
     * @var Family
     */
    private static $instance = null;

    /**
     * Instance of the API Controller class for calling during our model.
     * 
     * @var API
     */
    public $api;


    /**
     * Singleton pattern
     *
     * Ensures only one instance of plugin is loaded or can be loaded
     *
     * @return  self
     */
    public static function instance() {
        if ( ! ( self::$instance instanceof Family ) ) {
        self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor placeholder
     */
    private function __construct() {

     }

    /**
     * Create our Family from XML data. Potentially extend if multiple familes presented.
     * 
     * @param $json Product JSON for fetching families as needed.
     * 
     * @return $families array of families
     */
    public static function create_from_feed( $json ) {
        $families = [];
        $categories = $json['project_category'];
        
        if ( empty( $json ) ) {
            return [];
        }
    
        foreach ( $categories as $cat_id ) {
            $wp_term = self::find_or_new( $cat_id );
            
            if ( !empty( $wp_term ) ) {
                $families[] = $wp_term;
            }
        }
        return $families;

    }

    /**
     * Find or create a new family.
     * 
     * @param $name string
     * @param $options array
     * 
     * @return $family WP_Term
     */
    public static function find_or_new( $term_id ) {
        $api = API::instance();
        $term_obj = $api->get_single_cat( $term_id );
        $name = $term_obj['name'];
        
        $post_type = wp_bandit()->get_post_type();
        $taxonomy = wp_bandit()->get_tax_slug();

        $family = get_term_by('name', $name, $taxonomy);
             
        if( !$family ) {
            $args = [
                'description' => $term_obj['description'],
                'slug' => $term_obj['slug'],
                'parent' => wp_bandit()->get_top_level_parent(),
            ];
       
            $family = wp_insert_term( $name, $taxonomy, $args );
            update_term_meta( $family['term_id'], 'bandit_family_id', $term_obj['id'] );   
        }

        return $family;
    }

}
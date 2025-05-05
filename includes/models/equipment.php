<?php
/**
 * Main Machine Model.
 *
 * Handles how we assign data and store it per each machine off Bandit API.
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

final class Equipment {

    /**
     * The single instance of the class.
     *
     * @var Equipment
     */
    private static $instance = null;

    /**
     * Equipment ID from Bandit API
     * Post ID of the product off of the Bandit WP Install for a secondary ID on our side. 
     * 
     * @var int
     * @since 0.0.1
     */
    public $equipment_id; 

    /**
     * Families of the product. 
     * 
     * @var array
     */
    public $families = [];

    /**
     * Generic Post Meta Array for storing on a basic key value relationship from the machine 
     * 
     * @var array
     */
    public $post_meta = [];

    /**
     * Machine Images 
     *
     * @var array
     */
    public $media = [];

    /**
     * Machine Title/Name 
     * 
     * @var string
     */
    public $name;

    /**
     * Post Content for the machine
     * 
     * @var string
     */
    public $the_content;

    /**
     * Post ID of the specific product "on deck" for import. 
     * 
     * @var int
     */
    public $post_id;

    /**
     * Post Type of the specific product. 
     * 
     * @var string
     */
    public $post_type;

    /**
     * A hashed value of the full model object so that we can easily compare if we actually need to update a product. 
     * 
     * @var string
     */
    public $hashed_model;

    /**
     * Api instance for calling during our model.
     * 
     * @var API
     */
    public $api_instance;

    /**
     * Singleton pattern
     *
     * Ensures only one instance of plugin is loaded or can be loaded
     *
     * @return  self
     */
    public static function instance() {
        if ( ! ( self::$instance instanceof Equipment ) ) {
        self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Kick off the JSON and read all the data we'll need to fetch from it. 
     * 
     * @param $json Product JSON for single product model
     * 
     * @since 0.0.1
     */
    public function __construct( $json = '' ) {
        if ( ! empty( $json) ) {
            $this->proccess_from_json( $json);
            $this->api_instance = API::instance();
        }
    }

    /**
     * Get the post ID of a product if it is in the database 
     * 
     * @param string $eid equipment id 
     * 
     * @return int|bool
     */
    public function get_post_id( $eid ) {
        global $wpdb;
        $sql = $wpdb->prepare("
            SELECT post_id
            FROM {$wpdb->postmeta}
            WHERE meta_key='bandit_equipment_id'
            AND meta_value = %s
        ", $eid );
        $exists = $wpdb->get_row( $sql );

        if ( $exists ) {
            return $exists->post_id;
        }

        return false;
    }

    /**
     * Process our machine from the json data to set up for saving to the database. 
     * 
     * @param $json Product JSON for single product model
     * 
     * @since 0.0.1
     * @return void
     */
    public function proccess_from_json( $json ) {

        if ( empty( $json ) ) {
            return;
        }

        $this->name = $json['acf']["product_main_title"] ?? '';
        $this->equipment_id = (int)$json['id'] ?? 0; // ID relative to BanditChippers WP install.
        $this->hashed_model = md5( wp_json_encode($json) );

        $this->the_content = $json['acf']['product_description'] ?? '';
        $this->post_type = wp_bandit()->get_post_type();

        $this->post_meta = [ 
            "bandit_link" => $json['link'] ?? '',
            //"price" => $json['_price'] ?? '', //currently haven't seen any with this actually set.
            //"stock" => $json['_stock'] ?? '', //currently haven't seen any with this actually set.
            "sub_title" => $json['acf']['product_sub_title'] ?? '',
            //"brochure_link" => $json['acf']['product_brochure_link'] ?? '',
            "key_features_&_options" => $json['acf']['key_features_&_options'] ?? '',
            "product_details" => $json['acf']['product_details'] ?? '',

        ];
        $this->families = $json['project_category'] ?? [];
   

        $this->media = [
            "product_image_carousel_1" => $json['acf']['product_image_carousel_1'] ?? '', // All of these are media IDs to be processed later.
            "product_image_carousel_2" => $json['acf']['product_image_carousel_2'] ?? '',
            "product_image_carousel_3" => $json['acf']['product_image_carousel_3'] ?? '',
            "product_image_carousel_4" => $json['acf']['product_image_carousel_4'] ?? '',
            "product_image_carousel_5" => $json['acf']['product_image_carousel_5'] ?? '',
            "product_image_carousel_6" => $json['acf']['product_image_carousel_6'] ?? '', // end media ids.
            "product_specs_pdf" => $json['acf']['product_specs'] ?? '', // ID to be processed for media link later.
            "video_1" => $json['acf']['video_1'] ?? '',
           //"video_1_title" => $json['acf']['video_1_title'] ?? '',
            "video_2" => $json['acf']['video_2'] ?? '',
           // "video_2_title" => $json['acf']['video_2_title'] ?? '',
            "video_3" => $json['acf']['video_3'] ?? '',
            //"video_3_title" => $json['acf']['video_3_title'] ?? '',
            
   
        ];

        // Lastly check if we have this machine already. 
        if ( empty( $this->post_id ) ) {
            $this->post_id = $this->get_post_id( $this->equipment_id );
        }
 
    }

     /**
     * Save the post to the database
     * handles new & update
     *
     * @return $id INT, the ID of the saved post
     */
    public function save() {
        global $wpdb;
        $post            = [];
        $current_user_id = 4; // set as needed.
        $current_user    = defined( 'DOING_CRON' ) && DOING_CRON ? get_user_by( 'email', get_option( 'admin_email' ) ) : wp_get_current_user();
        $update          = false;

        if ( false !== $current_user && $current_user->ID > 0 ) {
            $current_user_id = $current_user->ID;
        }

        if ( (defined( 'DOING_CRON' ) && DOING_CRON) ||  (defined( 'WP_CLI' ) && WP_CLI) ) {
            wp_set_current_user( $current_user_id );
        }

        // if the product was previously in the database
        // then we want to update the post not create a new one.
        if ( $this->post_id ) {
            $post['ID'] = $this->post_id;
            $update     = true;
        }

        $post['post_title']  = $this->name;
        $post['post_type']   = $this->post_type;
        $post['post_status'] = 'publish';
        $post['post_author'] = $current_user_id;
        $post['post_content'] = $this->the_content;
        $post['tax_input']   = [];


        //Placeholder for family and tax
        $family_id = 0;
        $family_tax = '';

        foreach ( $this->families as $family ) {
            if(!isset($family->taxonomy)){
                $family = get_term_by('term_taxonomy_id', $family['term_taxonomy_id']); //retry to get the term object if WP wasn't fast enough.
            }
            $post['tax_input'][ $family->taxonomy ][] = intval( $family->term_id );
            $family_id = intval(  $family->term_id );
            $family_tax = $family->taxonomy; // there should only be one fam for now so no worries there
        }

        //save the actual post 
        if ( $post_id = wp_insert_post( $post ) ) {
            $this->insert_post_meta( $post_id, $update );
        }

        //Apply the families to the post with the post ID as post['tax_input'] is not working for some reason.
        wp_set_post_terms($post_id, $family_id, $family_tax);
  
        return $this;

    }

    /**
     * Insert the post meta for the machine.
     * 
     * @param int $post_id
     * @param bool $update
     * 
     * @return void
     */
    public function insert_post_meta($post_id, $update) {
        global $wpdb;

        if ($update && get_post_meta($post_id, 'bandit_machine_hash' ) == $this->hashed_model) {
            return; // exit early if we just don't need to update period. 
        }


        //update the post meta first 
        update_post_meta( $post_id, 'bandit_machine_hash', $this->hashed_model); 
        
        update_post_meta( $post_id, 'is_bandit_feed', 'true'  ); // Signify all Bandit Models for easier removal. 
        update_post_meta( $post_id, 'bandit_equipment_id', $this->equipment_id );
        update_post_meta($post_id, 'sort', 999 );
        
        $features_output = ""; 
        foreach ($this->post_meta as $key => $value) {
            if ( empty( $value ) ) {
                continue; //skip if empty
            }
            if ( $key === "key_features_&_options" )  {
                foreach ($value as $label => $feature) {
                    if(!empty($feature)){
                        $features_output .= "<li>" . $feature . "</li>";
                    } 
                } 
            } elseif( $key === "product_details" ) {
                // This can line up just with specs for now.
                $specs = [];
                foreach ($value as $spec_label => $spec_value) {
                    if (!empty($spec_value)) {
                        $specs[$spec_label] = $spec_value;
                    }
                }
                if(!empty($specs)) {
                    update_post_meta( $post_id, 'featured_details', $specs );
                }
            } else {
                update_post_meta( $post_id, $key, $value );
            }
        }

       
        if (!empty($features_output)) {
            $features_output = wp_kses_post("<ul>" . $features_output . "</ul>");
            update_post_meta( $post_id, 'custom_product_description', $features_output );
        }

       
        // Then set our media fields
        $images = [];
        $videos = [];
        foreach ($this->media as $label => $media) {
            if(empty($media)){
                    continue; //skip if empty
                }
            if (stripos($label, 'product_image_carousel') !== false) {
                $image        = new \stdClass();
                $image->title = $media; //placeholder.
                $image->type = "gallery-image";
                $image_off_api = $this->api_instance->get_media( $media );
                $image->src = $image_off_api['source_url'];
                $images[] = $image;

            } elseif ($label === "product_specs_pdf") {
                
                $spec = new \stdClass();
                $spec->title = $media; //placeholder.
                $spec->type = "doc_library";
                $spec_doc_from_api = $this->api_instance->get_media( $media );
                $spec->src = $spec_doc_from_api['source_url'];
                update_post_meta( $post_id, 'documents', $spec );


            } elseif (stripos($label, 'video') !== false) {
                $video = new \stdClass();
                $video->title = $media; //placeholder.
                $video->type = "video/youtube";
                $video->src = $media;
                $videos[] = $video;
            } 
        }
        if (!empty($images)) {
           update_post_meta( $post_id, 'images', $images );
        }
        if (!empty($videos)) {
            update_post_meta( $post_id, 'videos', $videos );
        }
      

    }


    /**
     * Force insert post meta via wpdb for a specific post ID.
     * 
     * @param int $post_id
     * @param bool $k meta key 
     * @param bool $v meta value
     * 
     * @return void
     */
    public function force_insert_post_meta($post_id, $k, $v) {
        global $wpdb;
        $sql = $wpdb->prepare("
            INSERT INTO {$wpdb->postmeta} (post_id, meta_key, meta_value)
            VALUES (%d, %s, %s)
        ", $post_id, $k, $v );
        $wpdb->query( $sql );
    }

}
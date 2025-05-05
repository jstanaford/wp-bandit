<?php
/**
 * API Interactions class
 *
 * Handles interacting with the API for running basic queries. 
 * For testing wp eval 'do_action("wp_bandit_on_deck_test");'
 *
 * @since       0.0.1
 *
 * @category    Controllers
 * @package     WP_Bandit\Controllers
 * @author      Jacob Stanaford
 */

namespace WP_Bandit\Controllers;

defined( 'ABSPATH' ) || exit;

final class API {

    /**
     * The single instance of the class.
     *
     * @var API
     */
    private static $instance = null;

    /**
     * API Domain to use for requests
     * 
     * @var string
     * 
     * @since 0.0.1
     */
    public $api_domain = "banditchippers.com";

    /**
     * Main WP json endpoint for API
     * 
     * @var string
     * 
     * @since 0.0.1
     */
    public $api_endpoint = "/wp-json/wp/v2/";

    /**
     * Singleton pattern
     *
     * Ensures only one instance of plugin is loaded or can be loaded
     *
     * @return  self
     */
    public static function instance() {
        if ( ! ( self::$instance instanceof API ) ) {
        self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Initializes class
     *
     * @since 0.0.1
     */
    private function __construct(){
        $this->init_hooks();
    }

    /**
     * Hook functionality into WordPress
     *
     * @since 0.0.1
     *
     * @return  void
     */
    private function init_hooks(): void {
        add_action( 'wp_bandit_on_deck_test', [ $this, 'run_test' ] );
    }

    /**
     * Run the function of our on deck test so we can trigger it via cli. 
     * 
     * @since 0.0.1
     * 
     * @return void
     */
    public function run_test(): void {
       var_dump( $this->fetch_all(true)); 
       die();
    }

    /**
     * Fetch all products from all categories. 
     * 
     * @param bool $sort_by_cat Sort the products by category in the araay response.
     * 
     * @since 0.0.1
     * 
     * @return array
     */
    public function fetch_all($sort_by_cat = false): array {
        $categories = $this->get_product_categories();
        $all_products = [];
        //filter out the IDs to a new array. 
        foreach( $categories as $category ) {
            if ($sort_by_cat) {
                $all_products[$category['name']] = $this->fetch_products_by_cat($category['id']);
            } else {
                $all_products[] = $this->fetch_products_by_cat($category['id']);
            }
            
        }
        return $all_products;
       
    }

    /**
     * Fetch all avaible Product Categories
     * 
     * @since 0.0.1
     * 
     * @return array
     */
    public function get_product_categories(): array {
        $url = 'https://' . $this->api_domain . $this->api_endpoint . "project_category";
        $response = wp_remote_get( $url );
        if ( is_wp_error( $response ) ) {
            return [];
        }
        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );
        if ( ! is_array( $data ) ) {
            return [];
        }
        return $data;
    }

    /**
     * Fetch all avaible Products from a given category by ID. 
     * Example ID that is valid = 7 for testing "Hand-Fed Chippers".
     * 
     * @param int $cat_id Category ID to fetch products for.
     * 
     * @since 0.0.1
     * 
     * @return array 
     */
    public function fetch_products_by_cat($cat_id) {
        $url = 'https://' . $this->api_domain . $this->api_endpoint . "project?project_category=" . $cat_id;
        //$url = 'https://' . $this->api_domain . $this->api_endpoint . "project?category=" . $cat_id;
        $response = wp_remote_get( $url );
        if ( is_wp_error( $response ) ) {
            return [];
        }
        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );
        if ( ! is_array( $data ) ) {
            return [];
        }
        return $data;
    }


    /**
     * Fetch a media item by media_id from the API.
     * 
     * @param int $media_id Media ID to fetch.
     * 
     * @since 0.0.1
     * 
     * @return array 
     */
    public function get_media($media_id) {
        $url = 'https://' . $this->api_domain . $this->api_endpoint . "media/" . $media_id;

        $response = wp_remote_get( $url );
        if ( is_wp_error( $response ) ) {
            return [];
        }
        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );
        if ( ! is_array( $data ) ) {
            return [];
        }
        return $data;
    }


    /**
     * Fetch a single product by ID from the API. 
     * 
     * @param int $product_id Product ID to fetch.
     * 
     * @since 0.0.1
     * 
     * @return array 
     */
    public function get_single( $product_id ) {
        $url = 'https://' . $this->api_domain . $this->api_endpoint . "project/" . $product_id;
        $response = wp_remote_get( $url );
        if ( is_wp_error( $response ) ) {
            return [];
        }
        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );
        if ( ! is_array( $data ) ) {
            return [];
        }
        return $data;
    }


    /**
     * Fetch a single product Category by ID from the API. 
     * 
     * @param int $cat_id Category ID to fetch.
     * 
     * @since 0.0.1
     * 
     * @return array 
     */
    public function get_single_cat( $cat_id ) {
        $url = 'https://' . $this->api_domain . $this->api_endpoint . "project_category/" . $cat_id;
        $response = wp_remote_get( $url );
        if ( is_wp_error( $response ) ) {
            return [];
        }
        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );
        if ( ! is_array( $data ) ) {
            return [];
        }
        return $data;

    }

}
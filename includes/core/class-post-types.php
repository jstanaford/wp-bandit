<?php
/**
 * Post Types
 *
 * Handles registering custom post types
 *
 * @since       0.0.1
 *
 * @category    Core
 * @package     WP_Bandit\Core
 * @author      Jacob Stanaford
 */

namespace WP_Bandit\Core;

defined( 'ABSPATH' ) || exit;

final class Post_Types {

    /**
     * The single instance of the class.
     *
     * @var Post_Types
     */
    private static $instance = null;

    /**
     * Post type slug of the bandit equipment to be imported. 
     * 
     * @var string
     */
    public $post_type_slug;    
    
    /**
     * Tax slug of the bandit equipment to be imported. 
     * 
     * @var string
     */
    public $tax_slug; 

    /**
     * Singleton pattern
     *
     * Ensures only one instance of plugin is loaded or can be loaded
     *
     * @return  self
     */
    public static function instance() {
        if ( ! ( self::$instance instanceof Post_Types ) ) {
        self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Initializes class
     *
     * @since 0.0.1
     */
    private function __construct() {
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
        add_action( 'init', [ $this, 'set_and_maybe_register_post_types' ] );
        add_action('add_meta_boxes' , [ $this, 'add_meta_boxes' ] );

    }

    /**
     * Add Meta boxes specific to the Bandit products 
     * 
     * @since 0.0.1
     * 
     * @return void
     */
    public function add_meta_boxes(): void {
        add_meta_box(
            'wp_bandit_product_meta_box',
            'Bandit Equipment Details',
            [ $this, 'render_meta_box_content' ],
            $this->post_type_slug,
            'side',
            'default'
        );
    }

    /**
     * Render Meta box content for the Bandit products
     * 
     * @since 0.0.1
     * 
     * @return void
     */
    public function render_meta_box_content($post): void {
         // Get the post ID and check if it has the required term assigned
        $post_id = $post->ID;
        $required_term_id = 755; // ID of the parent term
        $taxonomy = 'cat_new_allied_family'; // Taxonomy name

        $terms = wp_get_post_terms($post_id, $taxonomy);
        $has_required_term = false;

        foreach ($terms as $term) {
            if ($term->parent === $required_term_id) {
                $has_required_term = true;
                break;
            }
        }

        // Display the meta box content if the post has the required term
        if ($has_required_term) {
            $is_bandit_feed = get_post_meta($post_id, 'is_bandit_feed', true);
            if (empty($is_bandit_feed)) {
                $is_bandit_feed = 'false';
            }

            echo '<p>' . esc_html__('Is Bandit Feed: ' . $is_bandit_feed, 'text-domain') . '</p>';

        } else {
            echo '<p>' . esc_html__('This post does not have the required term assigned. Not a Bandit product.', 'text-domain') . '</p>';
        }
    }


    /**
     * Register post types in WordPress
     *
     * @since 0.0.1
     *
     * @return  void
     */
    public function set_and_maybe_register_post_types(): void {
         $this->post_type_slug = wp_bandit()->get_post_type();
         $this->tax_slug = wp_bandit()->get_tax_slug();

        // Check if the post type slug has been overwritten by the user via filter. 
        if (wp_bandit()->og_post_slug !== wp_bandit()->get_post_type()) {
        
            // If it has, check if the post type exists already, and, if it does, bail.
            if (post_type_exists(wp_bandit()->get_post_type())) {
                return; // Rationale being we allow the user to specify the post type here. 
            }
        } else {
             // Otherwise, generate a post type.
            $args = apply_filters(
                'wp_bandit_post_type_args',
                [
                    'public'        => true,
                    'map_meta_cap'  => true,
                    'has_archive'   => true,
                    'rewrite'       => [
                    'slug'       => $this->post_type_slug ,
                    'with_front' => false,
                    ],
                    'supports'      => [ 'title',  'thumbnail'],
                    'menu_icon'     => 'dashicons-admin-generic',
                    'menu_position' => '20.2',
                    'labels'        => apply_filters(
                    'wp_bandit_post_type_label_args',
                    [
                        'name'               => _x( 'Bandit Equipment', 'post type general name', 'wp_bandit' ),
                        'singular_name'      => _x( 'Bandit Equipment', 'post type singular name', 'wp_bandit' ),
                        'menu_name'          => _x( 'Bandit Equipment', 'admin menu', 'wp_bandit' ),
                        'name_admin_bar'     => _x( 'New Equipment', 'add new on admin bar', 'wp_bandit' ),
                        'add_new'            => _x( 'Add New Equipment', 'new equipment', 'wp_bandit' ),
                        'add_new_item'       => __( 'Add Equipment', 'wp_bandit' ),
                        'new_item'           => __( 'New Equipment', 'wp_bandit' ),
                        'edit_item'          => __( 'Edit Equipment', 'wp_bandit' ),
                        'view_item'          => __( 'View Equipment', 'wp_bandit' ),
                        'all_items'          => __( 'All Equipment', 'wp_bandit' ),
                        'search_items'       => __( 'Search new machines', 'wp_bandit' ),
                        'parent_item_colon'  => __( 'Parent Equipment:', 'wp_bandit' ),
                        'not_found'          => __( 'No Equipment found.', 'wp_bandit' ),
                        'not_found_in_trash' => __( 'No Equipment found in trash.', 'wp_bandit' ),
                    ]
                    ),
                ]
                );

                register_post_type( $this->post_type_slug, $args );
        }
       


            // Rinse and repeat for our lovely taxonomy. 
            if (wp_bandit()->og_tax_slug !== wp_bandit()->get_tax_slug()) {
        
                // If it has, check if the post type exists already, and, if it does, bail.
                if (taxonomy_exists(wp_bandit()->get_tax_slug())) {
                    return; // Rationale being we allow the user to specify the post type here. 
                }
            } else {    
                // Otherwise, register that taxonomy! 
                $args = apply_filters(
                    'wp_bandit_tax_args',
                    [
                        'rewrite'           => [
                        'slug'       => $this->tax_slug ,
                        'with_front' => false,
                        ],
                        'hierarchical'      => true,
                        'show_admin_column' => true,
                        'labels'            => apply_filters(
                        'wp_bandit_tax_labels',
                            [
                                'name'              => _x( 'Equipment Families', 'taxonomy general name' ),
                                'singular_name'     => _x( 'Equipment Family', 'taxonomy singular name' ),
                                'search_items'      => __( 'Search Families' ),
                                'all_items'         => __( 'All Families' ),
                                'parent_item'       => __( 'Parent Family' ),
                                'parent_item_colon' => __( 'Parent Family:' ),
                                'edit_item'         => __( 'Edit Family' ),
                                'update_item'       => __( 'Update Family' ),
                                'add_new_item'      => __( 'Add New Family' ),
                                'new_item_name'     => __( 'New Family Name' ),
                                'menu_name'         => __( 'Families' ),
                            ]
                        ),
                    ]
                );

                $new_tax = $this->tax_slug;
                register_taxonomy(
                $new_tax,
                $this->post_type_slug,
                    $args
                );

            }

            
        
    }



}
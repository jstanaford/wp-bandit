<?php
/**
 * Plugin Name: WP Bandit Equipment Plugin
 * Description: Integrates Bandit API Data into WordPress
 * Version: 1.0.0
 * Author: Jacob Stanaford
 * Author URI: https://jstanaford.github.io/
 *
 * Text Domain: bandit
 *
 * @Package Bandit API Data
 * @category Core
 * @author Jacob Stanaford
 */
if ( ! defined( 'ABSPATH' ) ) {
  exit; // Exit if accessed directly
}

final class WP_Bandit {

  /**
   * The single instance of the class.
   *
   * @var WP_Bandit
   */
  private static $instance = null;

  /**
   * Prefix used in plugin hooks
   *
   * @var string
   */
  public static $hook_base = 'wp_bandit_equipment';

  /**
   * Path to plugin's base file
   *
   * @var string
   */
  public $plugin_base_file;

  /**
   * Plugin's base directory
   *
   * @var string
   */
  public $plugin_dir;

  /**
   * Directory path to plugin's includes
   *
   * @var string
   */
  public $plugin_inc_dir;

  /**
   * Directory path for plugin's assets
   *
   * @var string
   */
  public $plugin_assets_dir;


  /**
   * Directory path to plugin's templates
   *
   * @var string
   */
  public $plugin_templates_dir;

  /**
   * Plugin's base URL
   *
   * @var string
   */
  public $plugin_url;

  /**
   * URL for plugin's assets directory
   *
   * @var string
   */
  public $plugin_assets_url;

  /**
   * Default post type slug handle 
   * 
   * @var string 
   * 
   * @since 0.0.1
   */
    public $og_post_slug = 'bandit_equipment';

    /**
     * Default taxonomy slug handle 
     * 
     * @var string 
     * 
     * @since 0.0.1
     */
    public $og_tax_slug = 'bandit_equipment_family';

  /**
   * Singleton pattern
   *
   * Ensures only one instance of plugin is loaded or can be loaded
   *
   * @return  self
   */
  public static function instance() {
    if ( ! ( self::$instance instanceof WP_Bandit ) ) {
      self::$instance = new self();
    }

    return self::$instance;
  }

  /**
   * Kicks off plugin
   * Should not be called publicly; instead, call instance()
   */
  private function __construct() {
    $this->define_props();
    $this->load_includes();
    $this->load_classes();
  }


  /**
   * Define base class properties
   *
   * @since   0.0.1
   *
   * @return  void
   */
  private function define_props() {
    $this->plugin_base_file = __FILE__;
    $this->plugin_dir       = __DIR__;

    $this->plugin_inc_dir       = sprintf( '%s/includes', $this->plugin_dir );
    $this->plugin_assets_dir    = sprintf( '%s/assets', $this->plugin_dir );
    $this->plugin_templates_dir = sprintf( '%s/templates', $this->plugin_dir );

    $this->plugin_url        = untrailingslashit( plugin_dir_url( $this->plugin_base_file ) );
    $this->plugin_assets_url = sprintf( '%s/assets', $this->plugin_url );
  }

  /**
   * Load include class files
   *
   * @since   0.0.1
   *
   * @return  void
   */
  private function load_includes() {
    include_once $this->plugin_inc_dir . '/global-public-functions.php';

    /**
     * Load models
     */
    include_once $this->plugin_inc_dir . '/models/equipment.php';
    include_once $this->plugin_inc_dir . '/models/family.php';

    /**
     * Loads controllers
     *
     * Controllers can be classes that provide supporting functionality or integration with other plugins (e.g.
     * transmits CF7 submissions to third party)
     */
    include_once $this->plugin_inc_dir . '/controllers/class-progress.php';
    include_once $this->plugin_inc_dir . '/controllers/class-api.php';
    include_once $this->plugin_inc_dir . '/controllers/class-importer.php';

    /**
     * Load core classes
     *
     * Core classes are classes that directly modify core WP functionality (e.g. add post types) that depend
     * heavily on hook usage.
     */
    if( is_admin() ) {
      include_once $this->plugin_inc_dir . '/core/class-admin.php';
    }
    include_once $this->plugin_inc_dir . '/core/class-post-types.php';

  }

  /**
   * Load supporting classes a properties to base plugin class
   *  - Add additional core classes or needed constructors as needed
   *
   * @since   0.0.1
   *
   * @return  void
   */
  private function load_classes() {
    $this->progress = \WP_Bandit\Controllers\Progress::instance();
    $this->api = \WP_Bandit\Controllers\API::instance();
    $this->importer = \WP_Bandit\Controllers\Importer::instance();

    if( is_admin() ) {
      $this->admin  = \WP_Bandit\Core\Admin::instance();
    }
    $this->post_types   = \WP_Bandit\Core\Post_Types::instance();


  }
  
  /**
   * Fetch the current post type slug for Bandit equipment. 
   * 
   * @return string
   * 
   * @since 0.0.1
   */
  public function get_post_type() {
    return apply_filters('wp_bandit_post_name', $this->og_post_slug );
  }

  /**
   * Fetch the current taxonomy slug for Bandit equipment. 
   * 
   * @return string
   * 
   * @since 0.0.1
   */
  public function get_tax_slug() {
    return apply_filters('wp_bandit_tax_name', $this->og_tax_slug);
  }

  /**
   * Allow for override of the "top level" family for all Bandit Terms. 
   * Makes for easier integration if utilizing an existing taxonomy vs plugin built in. 
   * 
   * @return string
   */
  public function get_top_level_parent() {
    return apply_filters('wp_bandit_top_level_parent', 0); //default to 0 
  }

  /**
   * Check whether a cli command is being invoked.
   *
   * @return bool
   */
  public function is_cli() {
    return class_exists( 'WP_CLI' );
  }

}

/**
 * Returns main instance of base plugin class
 */
add_action( 'plugins_loaded', 'wp_bandit' );
function wp_bandit() { 
  return WP_Bandit::instance();
}
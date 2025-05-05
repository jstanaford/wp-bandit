<?php
/**
 * Admin
 *
 * Handles registering settings, adding menu links in dashboard, etc
 *
 * @since       0.0.1
 *
 * @category    Core
 * @package     WP_Bandit\Core
 * @author      Jacob Stanaford
 */

 namespace WP_Bandit\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Admin page interactions for plugin settings. 
 */
final class Admin {

  /**
   * The single instance of the class.
   *
   * @var Admin
   */
  private static $instance = null;

  /**
   * Menu slug for the plugin's settings page and tab contorl. 
   * 
   * @var string
   */
  public $menu_slug;

  /**
   * Settings tab array for our individual tabs available.
   * 
   * @var array
   */
  public $settings_tabs = [];

  /**
   * Options key for wp admin page
   *
   * @var string
   */
  private $options_key = 'wp-bandit-equipment';

  /**
   * Singleton pattern
   *
   * Ensures only one instance of plugin is loaded or can be loaded
   *
   * @return  self
   */
  public static function instance() {
    if ( ! ( self::$instance instanceof Admin ) ) {
      self::$instance = new self();
    }
    return self::$instance;
  }

  /**
   * Initializes class
   *
   * @since   0.0.1
   */
  private function __construct() {
    $this->init_hooks();
    $default_tabs = [
      ['equipment-picklist' => 'Inventory Picklist'],
      ['importer' => 'Importer'], 
    ];
    $this->settings_tabs = apply_filters( 'wp_bandit_settings_tabs', $default_tabs );
  }


  /**
   * Hook functionality into WordPress
   *
   * @since   0.0.1
   *
   * @return  void
   */
  private function init_hooks() {
    add_action( 'admin_enqueue_scripts', [ $this, 'asset_manager' ] );
    add_action( 'admin_menu', [ $this, 'add_menu' ] );
    add_action( 'admin_init', [ $this, 'register_options_page_settings' ] );

  }

  /**
   * Register our Admin settings page options.
   *
   * @return void
   */
  public function register_options_page_settings() {
    // For saving the "on deck" post IDs (post ids native to bandit api, not local to site) to import to the site. 
    register_setting( 'wp_bandit_settings', 'bandit_selected_products' );
    register_setting( 'wp_bandit_settings', 'bandit_cached_listing' );
    register_setting( 'wp_bandit_settings', 'bandit_cached_images' );
  }

  
  /**
   * Add our menu page as a sub menu to the default WP Settings.
   *
   * @return void
   */
  public function add_menu() {
    /**
     * Purposeful customization to allow for easy renaming of the menu/plugin generic details for easy re-usability. 
     */
    add_menu_page(
      apply_filters('wp_bandit_importer_page_title', 'Bandit WordPress Import Plugin'),
      apply_filters('wp_bandit_importer_menu_title', 'Bandit Importer'),
      apply_filters('wp_bandit_importer_capability', 'manage_options'),
      apply_filters('wp_bandit_importer_menu_slug', $this->options_key ),
      [ $this, 'settings_page' ],
      apply_filters('wp_bandit_importer_icon_url', 'dashicons-rest-api'), 
      apply_filters('wp_bandit_importer_position', '20.1')
    );
  }

  /**
   * Call into place our main admin page template file.
   *
   * @return void
   */
  public function settings_page() {

    $tab = isset( $_GET['tab'] ) ? filter_input( INPUT_GET, 'tab', FILTER_SANITIZE_STRING ) : key(reset( $this->settings_tabs ));
    ?>
    <div class="wrap">
      <h2>Bandit Equipment Importer Plugin</h2>
      <?php
      settings_errors();
      $this->tabs();
      ob_start();
      include_once wp_bandit()->plugin_templates_dir . '/admin/' . $tab . '.php';
      $tab_content = ob_get_contents();
      ob_end_clean();
      $tab_content = apply_filters('wp_bandit_admin_tab_content_' . $tab, $tab_content); // Include easy way to integrate with other plugins for content overrides.
      echo $tab_content;

      do_action('wp_bandit_admin_tab_' . $tab, $tab_content); // Similarly, make it easy to trigger actions.
      ?>
    </div>
    <?php

    

  }

  /**
   * Renders our settings tabs
   * @return void 
   */
  private function tabs() {
    $current_tab = isset( $_GET['tab'] ) ? filter_input( INPUT_GET, 'tab', FILTER_SANITIZE_STRING ) : key(reset( $this->settings_tabs ));
    ?>
    <h2 class="nav-tab-wrapper">
      <?php
      foreach ( $this->settings_tabs as $tab_array ) {

        $tab_key = key( $tab_array );
        $tab_caption = reset( $tab_array );
        $active = $current_tab === $tab_key ? 'nav-tab-active' : '';
        ?>
      <a class="nav-tab <?php echo esc_html( $active ); ?>" href="?page=<?php echo esc_html( $this->options_key ); ?>&tab=<?php echo esc_html( $tab_key ); ?>"><?php echo esc_html( $tab_caption ); ?></a>
        <?php
      }
      ?>
    </h2>
    <?php
  }


  /**
   * Queue our admin based assets
   *
   * @since   0.0.1
   *
   * @return void
   */
  public function asset_manager() {
    $current_tab = isset( $_GET['tab'] ) ? filter_input( INPUT_GET, 'tab', FILTER_SANITIZE_STRING ) : key(reset( $this->settings_tabs ));

    if( !isset( $_GET['page'] ) || $_GET['page'] !== $this->options_key ) {
      return;
    }


    if ( (string) $current_tab === 'equipment-picklist' ) { 
     
      $js_url  = wp_bandit()->plugin_assets_url . '/js/admin/equipment-picklist.js';
      $js_path = wp_bandit()->plugin_assets_dir . '/js/admin/equipment-picklist.js';
      wp_enqueue_script( 'wp-bandit-equip-picklist-js', $js_url, [], filemtime( $js_path ), true );

      $css_url  = wp_bandit()->plugin_assets_url . '/css/admin/equipment-picklist.css';
      $css_path = wp_bandit()->plugin_assets_dir . '/css/admin/equipment-picklist.css';
      wp_enqueue_style( 'wp-bandit-equip-picklist-css', $css_url, [], filemtime( $css_path ), true );
    }

    if ($current_tab === "importer") {
      $js_url  = wp_bandit()->plugin_assets_url . '/js/admin/importer.js';
      $js_path = wp_bandit()->plugin_assets_dir . '/js/admin/importer.js';
      wp_enqueue_script( 'wp-bandit-equip-importer-js', $js_url, ['jquery', 'underscore', 'jquery-ui-sortable' ], filemtime( $js_path ), true );
      wp_localize_script(
          'wp-bandit-equip-importer',
          'WP_Bandit_Importer',
          [
            'ajax_url' => admin_url( 'admin-ajax.php' )
          ]
          );

      $css_url  = wp_bandit()->plugin_assets_url . '/css/admin/importer.css';
      $css_path = wp_bandit()->plugin_assets_dir . '/css/admin/importer.css';
      wp_enqueue_style( 'wp-bandit-equip-importer-js-css', $css_url, [], filemtime( $css_path ), true );
    }


    
  }

}
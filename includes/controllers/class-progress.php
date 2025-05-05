<?php
/**
 * Progress
 *
 * Handles Feed Progress Monitoring and reporting
 *
 * @since       0.0.1
 *
 * @package     WP_Bandit\Controllers
 * @author      Jacob Stanaford
 */

namespace WP_Bandit\Controllers;

use \WP_Error;
use Exception;
use WP_CLI;

defined( 'ABSPATH' ) || exit;

final class Progress {

  public static $log = false;

  /**
   * The single instance of the class.
   *
   * @var Progress
   */
  private static $instance = null;

  public static $cli_progress;

  /**
   * Initializes plugin variables and sets up WordPress hooks/actions.
   *
   * @return void
   */

  protected function __construct() {
    add_action( 'wp_ajax_wp_bandit_progress_poll', [ $this, 'result' ] );
    add_action( 'wp_ajax_nopriv_wp_bandit_progress_poll', [ $this, 'result' ] );
  }

  /**
   * Singleton pattern
   *
   * Ensures only one instance of plugin is loaded or can be loaded
   *
   * @return  self
   */
  public static function instance() {
    if ( ! ( self::$instance instanceof Progress ) ) {
      self::$instance = new self();
    }
    return self::$instance;
  }

  public function result() {
    echo wp_json_encode( self::get() );
    exit;
  }

  public static function get() {
    $indexed = get_transient( 'wp_bandit_progress_indexed' );
    $total   = get_transient( 'wp_bandit_progress_total' );
    $text    = get_transient( 'wp_bandit_progress_text' );

    return [
      'indexed' => $indexed,
      'total'   => $total,
      'text'    => $text,
    ];
  }




  /**
   * Setup temporary cache data
   * used by heartbeat api to update progress.
   */
  public static function set( $interval = null, $total = null, $text = '' ) {
    if ( $interval !== null && $interval !== false ) {
      set_transient( 'wp_bandit_progress_indexed', $interval, 30 * MINUTE_IN_SECONDS );

    }

    if ( $total !== null && $total !== false ) {
      set_transient( 'wp_bandit_progress_total', $total, 30 * MINUTE_IN_SECONDS );
      if ( wp_bandit()->is_cli() ) {
        self::$cli_progress = \WP_CLI\Utils\make_progress_bar( $text, $total );
      }
    }

    if ( ! empty( $text ) ) {
      set_transient( 'wp_bandit_progress_text', $text, 30 * MINUTE_IN_SECONDS );

    }


  }


  /**
   * set a specific field
   * @param string $field transient short name
   * @param sting|int $value the value for the transient
   */
  public static function update( $field, $value ) {
    switch ( $field ) {
      case 'index':
        set_transient( 'wp_bandit_progress_indexed', $value, 30 * MINUTE_IN_SECONDS );
        if ( wp_bandit()->is_cli() ) {
          self::$cli_progress->tick();
          if ( $value == get_transient( 'wp_bandit_progress_total' ) ) {
            self::$cli_progress->finish();
          }
        }
        break;
      case 'total':
        set_transient( 'wp_bandit_progress_total', $value, 30 * MINUTE_IN_SECONDS );

        break;
      case 'text':
        set_transient( 'wp_bandit_progress_text', $value, 30 * MINUTE_IN_SECONDS );

        break;
      default:
        set_transient( 'wp_bandit_progress_' . $field, $value, 30 * MINUTE_IN_SECONDS );

        break;
    }
  
  }
}
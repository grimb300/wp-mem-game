<?php

namespace MemGame;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// NOTE: I'm going to try and make this entirely a static class

class MemStats {

  /* **********
   * Properties
   * **********/

  // Stats table version
  private static $version = 'v1';

  /* *******
   * Methods
   * *******/

  // Init - Check the version, create or update the stats table if necessary
  public static function init() {
    mem_debug( 'Running MemStats::init()' );

    // Initialize the AJAX interface
    self::init_ajax();

    // Database related stuff
    global $wpdb;

    // Get the version stored in settings
    $current_version = get_option( 'mem_game_stats_version' );
    if ( $current_version !== self::$version ) {
      $table_name = $wpdb->prefix . 'mem_game_stats';

      // For now, don't mess with updating, just drop the table
      $wpdb->query( "DROP TABLE IF EXISTS $table_name" );

      // Now create a new table
      $charset_collage = $wpdb->get_charset_collate();
      $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        session_start_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        nonce VARCHAR(10) DEFAULT '' NOT NULL,
        game_data VARCHAR(1500) DEFAULT '' NOT NULL,
        PRIMARY KEY (id)
      ) $charset_collate;";
      require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
      dbDelta( $sql );

      // Update the current version
      update_option( 'mem_game_stats_version', self::$version );
    }
  }

  /**
   * AJAX related methods
   */

  // Initialize the AJAX interface
  private static function init_ajax() {
    add_action( 'wp_ajax_mem_game_start', 'MemGame\MemStats::mem_game_start_ajax_handler' );
    add_action( 'wp_ajax_nopriv_mem_game_start', 'MemGame\MemStats::mem_game_start_ajax_handler' );
    add_action( 'wp_ajax_mem_game_complete', 'MemGame\MemStats::mem_game_complete_ajax_handler' );
    add_action( 'wp_ajax_nopriv_mem_game_complete', 'MemGame\MemStats::mem_game_complete_ajax_handler' );
  }

  // Start a new game
  public static function mem_game_start_ajax_handler() {
    mem_debug( 'Executing mem_game_start' );
    // Check the nonce
    $nonce_check_result = check_ajax_referer( 'mem_game_stats' );
    // mem_debug( 'Nonce check results: ' . $nonce_check_result );

    mem_debug( 'Sending success!' );
    wp_send_json_success( array( 'message' => 'Success!!!' ), 200 );

    // Die at the end of the handler
    wp_die();
  }

  // Complete a game
  public static function mem_game_complete_ajax_handler() {
    mem_debug( 'Executing mem_game_complete' );
    // Check the nonce
    $nonce_check_result = check_ajax_referer( 'mem_game_stats' );
    // mem_debug( 'Nonce check results: ' . $nonce_check_result );

    mem_debug( sprintf( 'Recording %d clicks', $_POST[ 'data' ][ 'num_clicks' ] ) );

    mem_debug( 'Sending success!' );
    wp_send_json_success( array( 'message' => 'Success!!!' ), 200 );

    // Die at the end of the handler
    wp_die();
  }

  // Abandon a game
  public static function mem_game_abandon_ajax_handler() {
    mem_debug( 'Executing mem_game_abandon' );
    // Check the nonce
    $nonce_check_result = check_ajax_referer( 'mem_game_stats' );
    // mem_debug( 'Nonce check results: ' . $nonce_check_result );

    mem_debug( 'Sending success!' );
    wp_send_json_success( array( 'message' => 'Success!!!' ), 200 );

    // Die at the end of the handler
    wp_die();
  }
}
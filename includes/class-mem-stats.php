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
  private static $table_name = 'mem_game_stats';

  // Game data fields
  // Used to pack the game data more efficiently
  private static $game_data_fields = array(
    'start',
    'end',
    'clicks',
    'completed',
    'abandoned'
  );

  /* *******
   * Methods
   * *******/

  // Init - Check the version, create or update the stats table if necessary
  public static function init() {
    // mem_debug( 'Running MemStats::init()' );

    // Initialize the AJAX interface
    self::init_ajax();

    // Database related stuff
    global $wpdb;

    // Get the version stored in settings
    $current_version = get_option( 'mem_game_stats_version' );
    if ( $current_version !== self::$version ) {
      $full_table_name = $wpdb->prefix . self::$table_name;

      // For now, don't mess with updating, just drop the table
      $wpdb->query( "DROP TABLE IF EXISTS $full_table_name" );

      // Now create a new table
      $charset_collage = $wpdb->get_charset_collate();
      $sql = "CREATE TABLE $full_table_name (
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
    add_action( 'wp_ajax_mem_game_abandon', 'MemGame\MemStats::mem_game_abandon_ajax_handler' );
    add_action( 'wp_ajax_nopriv_mem_game_abandon', 'MemGame\MemStats::mem_game_abandon_ajax_handler' );
  }

  // Start a new game
  public static function mem_game_start_ajax_handler() {
    mem_debug( 'Executing mem_game_start' );
    global $wpdb;
    $full_table_name = $wpdb->prefix . self::$table_name;

    // Check the nonce
    $nonce_check_result = check_ajax_referer( 'mem_game_stats' );
    // mem_debug( 'Nonce check results: ' . $nonce_check_result );

    // mem_debug( 'Current timestamp ' . time() );
    // Create a new game entry
    $new_game = array(
      'start' => time(),
      'end' => -1,
      'clicks' => 0,
      'completed' => false,
      'abandoned' => false
    );

    // Get the session_id out of the post array, if present
    $session_id = empty( $_POST[ 'data' ][ 'session_id' ] ) ? -1 : intval( $_POST[ 'data' ][ 'session_id' ] );

    // Get the session out of the database table
    $session = $wpdb->get_results( "SELECT * FROM `$full_table_name` WHERE id = $session_id;", ARRAY_A );
    mem_debug( 'Query results for session ID ' . $session_id );
    mem_debug( $session );

    // If no session was found, create one
    if ( empty( $session ) ) {
      $result = $wpdb->insert(
        $full_table_name,
        array(
          'nonce' => $_POST[ '_ajax_nonce' ],
          'game_data' => self::pack_game_data( array( $new_game ) )
        )
      );
      $session_id = $wpdb->insert_id;
      mem_debug( sprintf( 'Created new session (%d)', $session_id ) );
    } else {
      // Get the game data from this session
      // NOTE: This assumes that serial IDs are unique (only one session is returned)
      $game_data = self::unpack_game_data( $session[0][ 'game_data' ] );
      // Add the new game
      $game_data[] = $new_game;
      // Update the session
      $result = $wpdb->update(
        $full_table_name,
        array( // Column to update
          'game_data' => self::pack_game_data( $game_data )
        ),
        array( // WHERE clause
          'id' => $session_id
        )
      );
      mem_debug( sprintf( 'Updated session (%d)', $session_id ) );
    }

    mem_debug( 'Sending success!' );
    wp_send_json_success( array( 'session_id' => $session_id ), 200 );

    // Die at the end of the handler
    wp_die();
  }

  // Complete a game
  public static function mem_game_complete_ajax_handler() {
    mem_debug( 'Executing mem_game_complete' );
    global $wpdb;
    $full_table_name = $wpdb->prefix . self::$table_name;

    // Check the nonce
    $nonce_check_result = check_ajax_referer( 'mem_game_stats' );
    // mem_debug( 'Nonce check results: ' . $nonce_check_result );

    // Get the session_id out of the post array, if present
    $session_id = empty( $_POST[ 'data' ][ 'session_id' ] ) ? -1 : intval( $_POST[ 'data' ][ 'session_id' ] );

    // Get the session out of the database table
    $session = $wpdb->get_results( "SELECT * FROM `$full_table_name` WHERE id = $session_id;", ARRAY_A );
    mem_debug( 'Query results for session ID ' . $session_id );
    mem_debug( $session );

    // If a session was found, update the end timestamp and clicks one
    if ( ! empty( $session ) ) {
      // Get the game data from this session
      // NOTE: This assumes that serial IDs are unique (only one session is returned)
      $game_data = self::unpack_game_data( $session[0][ 'game_data' ] );
      // Get the index of the current game
      $current_game_index = sizeof( $game_data ) - 1;
      // Add the new data
      $num_clicks = empty( $_POST[ 'data' ][ 'num_clicks' ] ) ? 0 : intval( $_POST[ 'data' ][ 'num_clicks' ] );
      mem_debug( sprintf( 'Recording a completed game with %d clicks', $num_clicks ) );
      $game_data[ $current_game_index ][ 'end' ] = time();
      $game_data[ $current_game_index ][ 'clicks' ] = $num_clicks;
      $game_data[ $current_game_index ][ 'completed' ] = true;

      // Update the session
      $result = $wpdb->update(
        $full_table_name,
        array( // Column to update
          'game_data' => self::pack_game_data( $game_data )
        ),
        array( // WHERE clause
          'id' => $session_id
        )
      );
      mem_debug( sprintf( 'Updated session (%d)', $session_id ) );
    }

    mem_debug( 'Sending success!' );
    wp_send_json_success( array( 'message' => 'Success!!!' ), 200 );

    // Die at the end of the handler
    wp_die();
  }

  // Abandon a game
  public static function mem_game_abandon_ajax_handler() {
    mem_debug( 'Executing mem_game_abandon' );
    global $wpdb;
    $full_table_name = $wpdb->prefix . self::$table_name;

    // Check the nonce
    $nonce_check_result = check_ajax_referer( 'mem_game_stats' );
    // mem_debug( 'Nonce check results: ' . $nonce_check_result );

    // Get the session_id out of the post array, if present
    $session_id = empty( $_POST[ 'data' ][ 'session_id' ] ) ? -1 : intval( $_POST[ 'data' ][ 'session_id' ] );

    // Get the session out of the database table
    $session = $wpdb->get_results( "SELECT * FROM `$full_table_name` WHERE id = $session_id;", ARRAY_A );
    mem_debug( 'Query results for session ID ' . $session_id );
    mem_debug( $session );

    // If a session was found, update the end timestamp and clicks one
    if ( ! empty( $session ) ) {
      // Get the game data from this session
      // NOTE: This assumes that serial IDs are unique (only one session is returned)
      $game_data = self::unpack_game_data( $session[0][ 'game_data' ] );
      // Get the index of the current game
      $current_game_index = sizeof( $game_data ) - 1;
      // Add the new data
      $num_clicks = empty( $_POST[ 'data' ][ 'num_clicks' ] ) ? 0 : intval( $_POST[ 'data' ][ 'num_clicks' ] );
      mem_debug( sprintf( 'Recording an abandoned game with %d clicks', $num_clicks ) );
      $game_data[ $current_game_index ][ 'end' ] = time();
      $game_data[ $current_game_index ][ 'clicks' ] = $num_clicks;
      $game_data[ $current_game_index ][ 'abandoned' ] = true;

      // Update the session
      $result = $wpdb->update(
        $full_table_name,
        array( // Column to update
          'game_data' => self::pack_game_data( $game_data )
        ),
        array( // WHERE clause
          'id' => $session_id
        )
      );
    }

    mem_debug( 'Sending success!' );
    wp_send_json_success( array( 'message' => 'Success!!!' ), 200 );

    // Die at the end of the handler
    wp_die();
  }

  // Pack and unpack functions
  // Want to create/edit game data using associative array,
  // but store as an indexed array to save DB space
  private static function pack_game_data( $unpacked_array ) {
    $packed_array = [];
    foreach ( $unpacked_array as $assoc_array ) {
      // This is an array of associative arrays
      $index_array = [];
      foreach ( self::$game_data_fields as $index => $key ) {
        mem_debug( sprintf( 'Conversion using (%s => %s)', $index, $key ) );
        // mem_debug( 'Associative data: ' . $assoc_array[ $key ] );
        $index_array[ $index ] = $assoc_array[ $key ];
      }
      $packed_array[] = $index_array;
    }
    // return serialize( $packed_array );
    return json_encode( $packed_array );
  }
  private static function unpack_game_data( $serial_string ) {
    // $packed_array = unserialize( $serial_string );
    $packed_array = json_decode( $serial_string );
    $unpacked_array = [];
    foreach ( $packed_array as $index_array ) {
      // This is an array of indexed arrays
      $assoc_array = [];
      foreach ( self::$game_data_fields as $index => $key ) {
        $assoc_array[ $key ] = $index_array[ $index ];
      }
      $unpacked_array[] = $assoc_array;
    }
    return $unpacked_array;
  }
}
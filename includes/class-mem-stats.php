<?php

namespace MemGame;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// NOTE: I'm going to try and make this entirely a static class

class MemStats {

  /* **********
   * Properties
   * **********/

  // Stats table version
  private static $version = 'v2';
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

    // Add the function to catch the download_stats and clear_stats requests
    add_action( 'admin_init', 'MemGame\MemStats::clear_stats' );
    add_action( 'admin_init', 'MemGame\MemStats::download_stats' );

    // Get the version stored in settings
    $current_version = get_option( 'mem_game_stats_version' );
    if ( $current_version !== self::$version ) {
      // Create the table (deletes the old one if it exists)
      self::create_stats_table();
      // Update the current version
      update_option( 'mem_game_stats_version', self::$version );
    }

    // Register the admin page to display the stats
    add_action( 'admin_menu', 'MemGame\MemStats::register_stats_page' );
  }

  private static function create_stats_table() {
    global $wpdb;

    // Compose the table name
    $full_table_name = $wpdb->prefix . self::$table_name;

    // For now, don't mess with updating, just drop the table
    $wpdb->query( "DROP TABLE IF EXISTS $full_table_name" );

    // Now create a new table
    $charset_collate = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE $full_table_name (
      id mediumint(9) NOT NULL AUTO_INCREMENT,
      session_start_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      memgame_id mediumint(9) DEFAULT 0 NOT NULL,
      post_id mediumint(9) DEFAULT 0 NOT NULL,
      nonce VARCHAR(10) DEFAULT '' NOT NULL,
      game_data VARCHAR(1500) DEFAULT '' NOT NULL,
      PRIMARY KEY (id)
    ) $charset_collate;";
    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );
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
    // mem_debug( 'Executing mem_game_start' );
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

    // Get the session_id, memgame_id, and post_id out of the post array, if present
    $session_id = empty( $_POST[ 'data' ][ 'session_id' ] ) ? -1 : intval( $_POST[ 'data' ][ 'session_id' ] );
    $memgame_id = empty( $_POST[ 'data' ][ 'memgame_id' ] ) ? -1 : intval( $_POST[ 'data' ][ 'memgame_id' ] );
    $post_id = empty( $_POST[ 'data' ][ 'post_id' ] ) ? -1 : intval( $_POST[ 'data' ][ 'post_id' ] );

    // Get the session out of the database table
    $session = $wpdb->get_results( "SELECT * FROM `$full_table_name` WHERE id = $session_id;", ARRAY_A );
    // mem_debug( 'Query results for session ID ' . $session_id );
    // mem_debug( $session );

    // If no session was found, create one
    if ( empty( $session ) ) {
      $result = $wpdb->insert(
        $full_table_name,
        array(
          'nonce' => $_POST[ '_ajax_nonce' ],
          'game_data' => self::pack_game_data( array( $new_game ) ),
          'memgame_id' => $memgame_id,
          'post_id' => $post_id
        )
      );
      $session_id = $wpdb->insert_id;
      // mem_debug( sprintf( 'Created new session (%d)', $session_id ) );
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
      // mem_debug( sprintf( 'Updated session (%d)', $session_id ) );
    }

    // mem_debug( 'Sending success!' );
    wp_send_json_success( array( 'session_id' => $session_id ), 200 );

    // Die at the end of the handler
    wp_die();
  }

  // Complete a game
  public static function mem_game_complete_ajax_handler() {
    // mem_debug( 'Executing mem_game_complete' );
    global $wpdb;
    $full_table_name = $wpdb->prefix . self::$table_name;

    // Check the nonce
    $nonce_check_result = check_ajax_referer( 'mem_game_stats' );
    // mem_debug( 'Nonce check results: ' . $nonce_check_result );

    // Get the session_id out of the post array, if present
    $session_id = empty( $_POST[ 'data' ][ 'session_id' ] ) ? -1 : intval( $_POST[ 'data' ][ 'session_id' ] );

    // Get the session out of the database table
    $session = $wpdb->get_results( "SELECT * FROM `$full_table_name` WHERE id = $session_id;", ARRAY_A );
    // mem_debug( 'Query results for session ID ' . $session_id );
    // mem_debug( $session );

    // If a session was found, update the end timestamp and clicks one
    if ( ! empty( $session ) ) {
      // Get the game data from this session
      // NOTE: This assumes that serial IDs are unique (only one session is returned)
      $game_data = self::unpack_game_data( $session[0][ 'game_data' ] );
      // Get the index of the current game
      $current_game_index = sizeof( $game_data ) - 1;
      // Add the new data
      $num_clicks = empty( $_POST[ 'data' ][ 'num_clicks' ] ) ? 0 : intval( $_POST[ 'data' ][ 'num_clicks' ] );
      // mem_debug( sprintf( 'Recording a completed game with %d clicks', $num_clicks ) );
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
      // mem_debug( sprintf( 'Updated session (%d)', $session_id ) );
    }

    // mem_debug( 'Sending success!' );
    wp_send_json_success( array( 'message' => 'Success!!!' ), 200 );

    // Die at the end of the handler
    wp_die();
  }

  // Abandon a game
  public static function mem_game_abandon_ajax_handler() {
    // mem_debug( 'Executing mem_game_abandon' );
    global $wpdb;
    $full_table_name = $wpdb->prefix . self::$table_name;

    // Check the nonce
    $nonce_check_result = check_ajax_referer( 'mem_game_stats' );
    // mem_debug( 'Nonce check results: ' . $nonce_check_result );

    // Get the session_id out of the post array, if present
    $session_id = empty( $_POST[ 'data' ][ 'session_id' ] ) ? -1 : intval( $_POST[ 'data' ][ 'session_id' ] );

    // Get the session out of the database table
    $session = $wpdb->get_results( "SELECT * FROM `$full_table_name` WHERE id = $session_id;", ARRAY_A );
    // mem_debug( 'Query results for session ID ' . $session_id );
    // mem_debug( $session );

    // If a session was found, update the end timestamp and clicks one
    if ( ! empty( $session ) ) {
      // Get the game data from this session
      // NOTE: This assumes that serial IDs are unique (only one session is returned)
      $game_data = self::unpack_game_data( $session[0][ 'game_data' ] );
      // Get the index of the current game
      $current_game_index = sizeof( $game_data ) - 1;
      // Add the new data
      $num_clicks = empty( $_POST[ 'data' ][ 'num_clicks' ] ) ? 0 : intval( $_POST[ 'data' ][ 'num_clicks' ] );
      // mem_debug( sprintf( 'Recording an abandoned game with %d clicks', $num_clicks ) );
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

    // mem_debug( 'Sending success!' );
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
        // mem_debug( sprintf( 'Conversion using (%s => %s)', $index, $key ) );
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

  // Convert the raw session data stored in the custom table into an array of sessions
  private static function analyze_sessions( $raw_data = array() ) {
    $analyzed_sessions = array_map( function( $session ) {
      $game_data = self::unpack_game_data( $session[ 'game_data' ] );
      return array_reduce(
        $game_data,
        function( $t, $g ) {
          $type = 'none';
          if ( $g[ 'completed' ] ) {
            $type = 'completed';
          }
          if ( $g[ 'abandoned' ] ) {
            $type = 'abandoned';
          }
          if ( 'none' !== $type ) {
            // Increment number of games by 1
            $t[ $type ][ 'games' ] += 1;
            // Calculate the number of seconds and increment
            $game_secs = $g[ 'end' ] - $g[ 'start' ];
            $t[ $type ][ 'total_seconds' ] += $game_secs;
            // Update the max number of seconds
            if ( empty( $t[ $type ][ 'max_seconds' ] ) || $game_secs > $t[ $type ][ 'max_seconds' ] ) {
              $t[ $type ][ 'max_seconds' ] = $game_secs;
            }
            // Update the min number of seconds
            if ( empty( $t[ $type ][ 'min_seconds' ] ) || $game_secs < $t[ $type ][ 'min_seconds' ] ) {
              $t[ $type ][ 'min_seconds' ] = $game_secs;
            }
            // Increment the total number of clicks
            $t[ $type ][ 'total_clicks' ] += $g[ 'clicks' ];
            // Update the max number of clicks
            if ( empty( $t[ $type ][ 'max_clicks' ] ) || $g[ 'clicks' ] > $t[ $type ][ 'max_clicks' ] ) {
              $t[ $type ][ 'max_clicks' ] = $g[ 'clicks' ];
            }
            // Update the min number of clicks
            if ( empty( $t[ $type ][ 'min_clicks' ] ) || $g[ 'clicks' ] < $t[ $type ][ 'min_clicks' ] ) {
              $t[ $type ][ 'min_clicks' ] = $g[ 'clicks' ];
            }
          }
          // Return the total
          return $t;
        },
        array(
          'completed' => array(
            'games' => 0,
            'total_seconds' => 0,
            'max_seconds' => 0,
            'min_seconds' => 0,
            'total_clicks' => 0,
            'max_clicks' => 0,
            'min_clicks' => 0,
          ),
          'abandoned' => array(
            'games' => 0,
            'total_seconds' => 0,
            'max_seconds' => 0,
            'min_seconds' => 0,
            'total_clicks' => 0,
            'max_clicks' => 0,
            'min_clicks' => 0,
          )
        )
      );
    }, $raw_data );

    return $analyzed_sessions;
  }

  // Take the analyzed sessions and return the totals for all sessions
  private static function compute_totals( $analyzed_sessions = array() ) {
    $totals = array_reduce(
      $analyzed_sessions,
      function( $t, $s ) {
        // Increment the total completed and abandoned games
        $t[ 'games' ] += $s[ 'completed' ][ 'games' ] + $s[ 'abandoned' ][ 'games' ];
        $t[ 'completed' ] += $s[ 'completed' ][ 'games' ];
        $t[ 'abandoned' ] += $s[ 'abandoned' ][ 'games' ];
        
        // Increment the clicks for each game type
        $t[ 'clicks' ] += $s[ 'completed' ][ 'total_clicks' ] + $s[ 'abandoned' ][ 'total_clicks' ];
        $t[ 'completed_clicks' ] += $s[ 'completed' ][ 'total_clicks' ];
        $t[ 'abandoned_clicks' ] += $s[ 'abandoned' ][ 'total_clicks' ];
        
        // Increment the seconds for each game type
        $t[ 'seconds' ] += $s[ 'completed' ][ 'total_seconds' ] + $s[ 'abandoned' ][ 'total_seconds' ];
        $t[ 'completed_seconds' ] += $s[ 'completed' ][ 'total_seconds' ];
        $t[ 'abandoned_seconds' ] += $s[ 'abandoned' ][ 'total_seconds' ];

        // Update the max complete games per session
        if ( $s[ 'completed' ][ 'games' ] > $t[ 'max_completed' ] ) {
          $t[ 'max_completed' ] = $s[ 'completed' ][ 'games' ];
        }

        // If a session never completed a game, increment the counters
        if ( 0 === $s[ 'completed' ][ 'games' ] ) {
          // mem_debug( 'Never completed' );
          $t[ 'never_completed' ] += 1;
          $t[ 'never_completed_clicks' ] += $s[ 'abandoned' ][ 'total_clicks' ];
          $t[ 'never_completed_seconds' ] += $s[ 'abandoned' ][ 'total_seconds' ];
        }

        // Return the running total
        return $t;
      },
      array(
        'games' => 0,
        'completed' => 0,
        'abandoned' => 0,
        'never_completed' => 0,
        'max_completed' => 0,
        'clicks' => 0,
        'completed_clicks' => 0,
        'abandoned_clicks' => 0,
        'never_completed_clicks' => 0,
        'seconds' => 0,
        'completed_seconds' => 0,
        'abandoned_seconds' => 0,
        'never_completed_seconds' => 0,
      )
    );

    return $totals;
  }

  // Analyze the stats
  // This is echoed out, currently only used in MemStats
  public static function get_analyzed_stats() {
    global $wpdb;
    $full_table_name = $wpdb->prefix . self::$table_name;
    $raw_sessions = $wpdb->get_results( "SELECT * FROM `$full_table_name`;", ARRAY_A );
    $analyzed_sessions = self::analyze_sessions( $raw_sessions );
    
    $totals = self::compute_totals( $analyzed_sessions );

    $num_sessions = sizeof( $raw_sessions );
    if ( 0 === $num_sessions ) {
      $avg_completed_per_session = 0;
      $avg_seconds_per_session = 0;
      $avg_clicks_per_session = 0;
    } else {
      $avg_completed_per_session = $totals[ 'completed' ] / $num_sessions;
      $avg_seconds_per_session = $totals[ 'seconds' ] / $num_sessions;
      $avg_clicks_per_session = $totals[ 'clicks' ] / $num_sessions;
    }
    if ( 0 === $totals[ 'never_completed' ] ) {
      $avg_seconds_per_never_completed = 0;
      $avg_clicks_per_never_completed = 0;
    } else {
      $avg_seconds_per_never_completed = $totals[ 'never_completed_seconds' ] / $totals[ 'never_completed' ];
      $avg_clicks_per_never_completed = $totals[ 'never_completed_clicks' ] / $totals[ 'never_completed' ];
    }

    // Display the stats
    // TODO: Add raw data display? Add reset stats button?
    ?>
    <style>
    .mem-game-stats > p {
      font-size: 16px;
    }
    </style>
    <div class="mem-game-stats">
      <p>Analyzed <strong><?php echo $num_sessions; ?></strong> unique sessions and <strong><?php echo $totals[ 'games' ]; ?></strong> games (<strong><?php echo $totals[ 'completed' ]; ?></strong> completed and <strong><?php echo $totals[ 'abandoned' ]; ?></strong> abandoned).</p>
      <p>Each session averaged <strong><?php echo sprintf( '%.1f', $avg_completed_per_session ); ?></strong> completed games with <strong><?php echo $totals[ 'max_completed' ]; ?></strong> games being the most completed in one session.</p>
      <p>A session lasted on average for <strong><?php echo sprintf( '%.1f', $avg_seconds_per_session ); ?></strong> seconds (<strong><?php echo sprintf( '%.1f', $avg_clicks_per_session ); ?></strong> clicks).</p>
      <p><strong><?php echo $totals[ 'never_completed' ]; ?></strong> sessions never completed a single game and lasted on average for <strong><?php echo sprintf( '%.1f', $avg_seconds_per_never_completed ); ?></strong> seconds (<strong><?php echo sprintf( '%.1f', $avg_clicks_per_never_completed ); ?></strong> clicks).</p>
    </div>
    <?php
  }

  public static function register_stats_page() {
    add_submenu_page(
      'edit.php?post_type=memgame',
      'Memory Game User Statistics',
      'User Stats',
      'edit_posts',
      'mem-game-stats',
      'MemGame\MemStats::display_stats_page',
      100
    );
  }

  public static function display_stats_page() {
    // Create the clear/download stats URIs based on the current URI
    // add_query_arg with only a key/value pair adds the new arg to the existing $_SERVER['REQUEST_URI']
    $clear_stats_uri = add_query_arg( 'clear_stats', '1' );
    $download_stats_uri = add_query_arg( 'download_stats', '1' );

    ?>
    <div class="wrap">
      <h1 class="wp-heading-inline">Memory Game Statistics</h1>
      <?php $stats = self::get_analyzed_stats(); ?>
      <?php
      if ( ! empty( $msg ) ) {
        ?>
        <p><em><?php echo $msg; ?></em></p>
        <?php
      }
      ?>
      <a class="button button-secondary" href="<?php echo $clear_stats_uri; ?>">Clear Statistics</a>
      <a class="button button-primary" href="<?php echo $download_stats_uri; ?>">Download Statistics</a>
    </div>
    <?php
  }

  public static function clear_stats() {
    // Test if this is a clear_stats request
    $is_clear_stats_request =
      isset( $_GET[ 'post_type' ] ) && 'memgame' === $_GET[ 'post_type' ] &&
      isset( $_GET[ 'page' ] ) && 'mem-game-stats' === $_GET[ 'page' ] &&
      isset( $_GET[ 'clear_stats' ] );

    if ( $is_clear_stats_request ) {
      // Create the stats table which will drop the old table first
      self::create_stats_table();
      
      // Redirect without the clear_stats parameter
      global $wp;
      $request_uri = site_url( $_SERVER[ 'REQUEST_URI' ] );
      $cleaned_uri = remove_query_arg( 'clear_stats', $request_uri );
      wp_redirect( $cleaned_uri, 302 );

      // Die, so the requested page isn't displayed
      die();
    }
  }

  public static function download_stats() {
    global $wpdb;
    $full_table_name = $wpdb->prefix . self::$table_name;

    // Test if this is a download_stats request
    $is_download_stats_request =
      isset( $_GET[ 'post_type' ] ) && 'memgame' === $_GET[ 'post_type' ] &&
      isset( $_GET[ 'page' ] ) && 'mem-game-stats' === $_GET[ 'page' ] &&
      isset( $_GET[ 'download_stats' ] );

    if ( $is_download_stats_request ) {
      // Get the stats out of the database
      $raw_sessions = $wpdb->get_results( "SELECT * FROM `$full_table_name`;", ARRAY_A );
      $analyzed_sessions = self::analyze_sessions( $raw_sessions );

      // Set the headers to download the csv file
      header('Content-Type: text/csv');
      header('Content-Disposition: attachment;filename="user_stats.csv"');
      header('Cache-Control: max-age=0');
      header ('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT'); // always modified
      header ('Cache-Control: cache, must-revalidate'); // HTTP/1.1
      header ('Pragma: public'); // HTTP/1.0
      
      // TODO: Could turn this into a series of class params
      // Open the output stream
      $csv_stream = fopen( 'php://output', 'w' );
      // Output the header row
      fputcsv( $csv_stream, array(
        'Session ID',
        'Memory Game ID',
        'Post ID',
        'Number Completed Games',
        'Completed Games Total Seconds',
        'Completed Games Max Seconds',
        'Completed Games Min Seconds',
        'Completed Games Total Clicks',
        'Completed Games Max Clicks',
        'Completed Games Min Clicks',
        'Number Abandoned Games',
        'Abandoned Games Total Seconds',
        'Abandoned Games Max Seconds',
        'Abandoned Games Min Seconds',
        'Abandoned Games Total Clicks',
        'Abandoned Games Max Clicks',
        'Abandoned Games Min Clicks',
      ) );
      foreach( $analyzed_sessions as $index => $session ) {
        fputcsv( $csv_stream, array(
          $raw_sessions[ $index ][ 'id' ],
          $raw_sessions[ $index ][ 'memgame_id' ],
          $raw_sessions[ $index ][ 'post_id' ],
          $session[ 'completed' ][ 'games' ],
          $session[ 'completed' ][ 'total_seconds' ],
          $session[ 'completed' ][ 'max_seconds' ],
          $session[ 'completed' ][ 'min_seconds' ],
          $session[ 'completed' ][ 'total_clicks' ],
          $session[ 'completed' ][ 'max_clicks' ],
          $session[ 'completed' ][ 'min_clicks' ],
          $session[ 'abandoned' ][ 'games' ],
          $session[ 'abandoned' ][ 'total_seconds' ],
          $session[ 'abandoned' ][ 'max_seconds' ],
          $session[ 'abandoned' ][ 'min_seconds' ],
          $session[ 'abandoned' ][ 'total_clicks' ],
          $session[ 'abandoned' ][ 'max_clicks' ],
          $session[ 'abandoned' ][ 'min_clicks' ],
      ) );
      }
      fclose( $csv_stream );
  
      die();
    }
  }
}
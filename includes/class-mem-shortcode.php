<?php

namespace MemGame;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// TODO: Consider making this a static class

class MemShortcode {

  /* **********
   * Properties
   * **********/
  
  /* *******
   * Methods
   * *******/

  // Constructor
  public function __construct() {
    // mem_debug( 'MemShortcode constructor' );

    // Create the shortcode
    add_shortcode( 'memgame', array( $this, 'memgame_display' ) );
    // Add the necessary JavaScript and CSS
    // add_action( 'wp_enqueue_scripts', array( $this, 'memgame_scripts' ) );
    // Register the necessary JavaScript and CSS
    add_action( 'init', array( $this, 'register_memgame_scripts' ) );
  }

  // Display the memory game
  // Based on this CodePen: https://codepen.io/natewiley/pen/HBrbL
  // TODO: Eventually the configuration will be in $params
  public function memgame_display( $params ) {
    mem_debug( 'memgame_display was passed params:' );
    mem_debug( $params );
    // Get the 'id' attribute, if present
    $memgame_id = empty( $params[ 'id' ] ) ? null : $params[ 'id' ];

    // TODO: If memgame_id is null, search for the legacy memgame
    if ( null == $memgame_id ) {
      $legacy_memgames = get_posts( array(
        'numberposts' => 1, // Expect at most one legacy post
        'fields' => 'ids',  // Return only the post ID
        'post_status' => 'publish',
        'post_type' => 'memgame',
        'meta_key' => 'mem_game_legacy',
        'meta_value' => 1,
        'meta_compare' => '=',
      ) );
      // If a matching legacy memory game was found, update the memgame_id
      $memgame_id = empty( $legacy_memgames ) ? null : $legacy_memgames[0];
    }


    // Get the winner screen text out of options
    $winner_screen_info = MemCpt::get_winner_screen_options( $memgame_id );
    // If the memory game wasn't found, return an error
    if ( empty( $winner_screen_info ) ) {
      return '<h5>No matching Memory Game found.</h5>';
    }
    // Else, pull the individual fields out of the array
    $winner_msg = $winner_screen_info[ 'winner_msg' ];
    $play_again_txt = $winner_screen_info[ 'play_again_txt' ];
    $quit_txt = $winner_screen_info[ 'quit_txt' ];
    $quit_url = $winner_screen_info[ 'quit_url' ];


    // Build the game layout
    $game = <<<END
    <div class="mg-how-to-play">
      <h5>How to play:</h5>
      <ol>
        <li>Select one of the face down cards by clicking on it.</li>
        <li>Select a second face down card, attempting to find a match to the first card.</li>
        <li>If the cards match, both will remain face up. Otherwise, the cards will turn face down again.</li>
        <li>Repeat until all cards are face up.</li>
      </ol>
    </div>
    <div class="mg-wrap">
      <div class="mg-game"></div>
      <div class="mg-modal-wrap">
        <div class="mg-modal-overlay">
          <div class="mg-modal">
            <h2 class="mg-winner">$winner_msg</h2>
            <button class="mg-restart">$play_again_txt</button>
            <a href="$quit_url"><button class="mg-leave">$quit_txt</button></a>
          </div>
        </div>
      </div>
    </div><!-- End Wrap -->
    END;

    // Enqueue the necessary JS and CSS
    self::enqueue_memgame_css();
    self::enqueue_memgame_js( $memgame_id );

    return $game;
  }

  // Enqueue CSS file
  // Break this out into its own function so it can be easily shared with the admin pages
  // FIXME: Doing a lot of repeating with how I enqueue JS and CSS files, make this its own helper function
  public static function enqueue_memgame_css() {
    // Enqueue the files (already registered)
    wp_enqueue_style( 'mem_game_css' );
  }

  public static function enqueue_memgame_js( $memgame_id = null ) {
    // Pass image info to the JS as localized data
    wp_localize_script(
      'mem_game_js',
      'mem_game_img_obj',
      array(
        'images' => MemCpt::get_localized_image_data( $memgame_id ),
        'ajax_url' => admin_url( 'admin-ajax.php' ),
        'nonce' => wp_create_nonce( 'mem_game_stats' )
      )
    );
    // Enqueue the files (already registered)
    wp_enqueue_script( 'mem_game_js' );
  }

  // Register the necessary JavaScript and CSS to be enqueued later
  public function register_memgame_scripts() {
    // Path to the CSS file
    $mem_game_css_path = MEM_GAME_PATH . 'assets/css/mem-game.css';
    $mem_game_css_url = MEM_GAME_URL . 'assets/css/mem-game.css';

    // Create the version based on the file modification time
    $mem_game_css_ver = date( 'ymd-Gis', fileatime( $mem_game_css_path ) );

    // Register the CSS file
    wp_register_style( 'mem_game_css', $mem_game_css_url, array(), $mem_game_css_ver );

    // Path to JS file
    $mem_game_js_path = MEM_GAME_PATH . 'assets/js/mem-game.js';
    $mem_game_js_url = MEM_GAME_URL . 'assets/js/mem-game.js';

    // Create the version based on the file modification time
    $mem_game_js_ver = date( 'ymd-Gis', fileatime( $mem_game_js_path ) );

    // Register the JS file
    // The borrowed JS needs jQuery (the CodePen used ver 2.1.3, assuming the default WP ver will do)
    wp_register_script( 'mem_game_js', $mem_game_js_url, array( 'jquery' ), $mem_game_js_ver, true );
  }
}
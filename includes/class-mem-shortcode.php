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
    // Get the post ID
    global $post;
    $post_id = $post->ID;

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
    
    // Get the game board layout
    
    // Get the "How to play" section
    $how_to_play = self::get_how_to_play();
    
    // Get the game board
    $game_board_layout = MemCpt::get_board_layout( $memgame_id );
    $card_images = MemCpt::get_images_for_localize_script( $memgame_id );
    $game_board = self::get_game_board(
      array(
        'layout' => $game_board_layout,
        'card_images' => $card_images,
      )
    );
    
    // Get the winner screen modal
    $winner_screen_info = MemCpt::get_winner_screen( $memgame_id );
    // If the memory game wasn't found, return an error
    if ( empty( $winner_screen_info ) ) {
      return '<h5>No matching Memory Game found.</h5>';
    }
    $winner_screen = self::get_winner_screen( $winner_screen_info );
    
    // Build the game layout
    $game = $how_to_play . "<div class=\"mg-wrap\">" . $game_board . $winner_screen . "</div>";
    
    // Enqueue the necessary JS and CSS
    self::enqueue_memgame_css();
    self::enqueue_memgame_js( $memgame_id, $post_id );
    
    // Return a single card for debug purposes
    return self::get_card( array(
      'card_front' => $card_images[ 'card_front' ][ 0 ],
      'card_back' => $card_images[ 'card_back' ][ 0 ]
    ) );
    return $game;
  }
  
  // Return the "How to play" section
  private static function get_how_to_play() {
    return
    "<div class=\"mg-how-to-play\">
      <h5>How to play:</h5>
      <ol>
        <li>Select one of the face down cards by clicking on it.</li>
        <li>Select a second face down card, attempting to find a match to the first card.</li>
        <li>If the cards match, both will remain face up. Otherwise, the cards will turn face down again.</li>
        <li>Repeat until all cards are face up.</li>
      </ol>
    </div>";
  }
  
  // Return the game board
  private static function get_game_board( $params ) {
    $game_board_layout = $params[ 'layout' ];
    $card_images = $params[ 'card_images' ];

    // Generate the card HTML
    $cards = "";
    $card_back = $card_images[ 'card_back' ][ 0 ];
    foreach ( $card_images[ 'card_front' ] as $card_front ) {
      // Get the card
      $card = self::get_card(
        array(
          'card_front' => $card_front,
          'card_back' => $card_back,
        )
      );
      // Add two of them to the list of cards
      $cards .= $card . $card;
    }

    return "<div class=\"mg-game mg-layout-$game_board_layout\">$cards</div>";
  }

  // Return a pair of cards
  private static function get_card( $params ) {
    // mem_debug( 'Card Front' );
    // mem_debug( $params )
    $card_front_url = $params[ 'card_front' ][ 'url' ];
    $card_back_url = $params[ 'card_back' ][ 'url' ];

    // Create a card
    $card = 
    "<div class=\"mg-new-card\">
      <div class=\"mg-new-inside\">
        <div class=\"mg-new-front\">
          <img src=\"$card_front_url\">
        </div>
        <div class=\"mg-new-back\">
          <img src=\"$card_back_url\">
        </div>
      </div>
    </div>";

    // Return it
    return $card;
  }
  
  // Return the winner screen
  private static function get_winner_screen( $winner_screen_info ) {
    // Pull the individual fields out of the array
    $winner_msg = $winner_screen_info[ 'winner_msg' ];
    $play_again_txt = $winner_screen_info[ 'play_again_txt' ];
    $quit_txt = $winner_screen_info[ 'quit_txt' ];
    $quit_url = $winner_screen_info[ 'quit_url' ];

    return
    "<div class=\"mg-modal-wrap\">
      <div class=\"mg-modal-overlay\">
        <div class=\"mg-modal\">
          <h2 class=\"mg-winner\">$winner_msg</h2>
          <button class=\"mg-restart\">$play_again_txt</button>
          <a href=\"$quit_url\"><button class=\"mg-leave\">$quit_txt</button></a>
        </div>
      </div>
    </div>";
  }

  // Enqueue CSS file
  // Break this out into its own function so it can be easily shared with the admin pages
  // FIXME: Doing a lot of repeating with how I enqueue JS and CSS files, make this its own helper function
  public static function enqueue_memgame_css() {
    // Enqueue the files (already registered)
    wp_enqueue_style( 'mem_game_css' );
  }

  public static function enqueue_memgame_js( $memgame_id = null, $post_id = null ) {
    // Pass image info to the JS as localized data
    wp_localize_script(
      'mem_game_js',
      'mem_game_img_obj',
      array(
        'images' => MemCpt::get_images_for_localize_script( $memgame_id ),
        'ajax_url' => admin_url( 'admin-ajax.php' ),
        'nonce' => wp_create_nonce( 'mem_game_stats' ),
        'memgame_id' => $memgame_id,
        'post_id' => $post_id
      )
    );
    // Enqueue the files (already registered)
    wp_enqueue_script( 'mem_game_js' );
  }

  // Register the necessary JavaScript and CSS to be enqueued later
  public function register_memgame_scripts() {
    // Path to the CSS file
    // $mem_game_css_path = MEM_GAME_PATH . 'assets/css/mem-game.css';
    // $mem_game_css_url = MEM_GAME_URL . 'assets/css/mem-game.css';
    $mem_game_css_path = MEM_GAME_PATH . 'assets/css/mem-game-experiment.css';
    $mem_game_css_url = MEM_GAME_URL . 'assets/css/mem-game-experiment.css';

    // Create the version based on the file modification time
    $mem_game_css_ver = date( 'ymd-Gis', fileatime( $mem_game_css_path ) );

    // Register the CSS file
    wp_register_style( 'mem_game_css', $mem_game_css_url, array(), $mem_game_css_ver );

    // Path to JS file
    // $mem_game_js_path = MEM_GAME_PATH . 'assets/js/mem-game.js';
    // $mem_game_js_url = MEM_GAME_URL . 'assets/js/mem-game.js';
    $mem_game_js_path = MEM_GAME_PATH . 'assets/js/mem-game-rewrite.js';
    $mem_game_js_url = MEM_GAME_URL . 'assets/js/mem-game-rewrite.js';

    // Create the version based on the file modification time
    $mem_game_js_ver = date( 'ymd-Gis', fileatime( $mem_game_js_path ) );

    // Register the JS file
    // The borrowed JS needs jQuery (the CodePen used ver 2.1.3, assuming the default WP ver will do)
    // wp_register_script( 'mem_game_js', $mem_game_js_url, array( 'jquery' ), $mem_game_js_ver, true );
    wp_register_script( 'mem_game_js', $mem_game_js_url, array(), $mem_game_js_ver, true );
  }
}
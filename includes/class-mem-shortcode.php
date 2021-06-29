<?php

namespace MemGame;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

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
    add_action( 'wp_enqueue_scripts', array( $this, 'memgame_scripts' ) );
  }

  // Display the memory game
  // Based on this CodePen: https://codepen.io/natewiley/pen/HBrbL
  // TODO: Eventually the configuration will be in $params
  public function memgame_display( $params ) {
    $game = <<<END
    <div class="mg-wrap">
      <div class="mg-game"></div>
      <div class="mg-modal-wrap">
        <div class="mg-modal-overlay">
          <div class="mg-modal">
            <h2 class="mg-winner">You Rock!</h2>
            <button class="mg-restart">Play Again?</button>
          </div>
        </div>
      </div>
    </div><!-- End Wrap -->
    END;

    return $game;
  }

  // Enqueue CSS file
  // Break this out into its own function so it can be easily shared with the admin pages
  // FIXME: Doing a lot of repeating with how I enqueue JS and CSS files, make this its own helper function
  public static function enqueue_memgame_css() {
    // Path to the CSS file
    $mem_game_css_path = MEM_GAME_PATH . 'assets/css/mem-game.css';
    $mem_game_css_url = MEM_GAME_URL . 'assets/css/mem-game.css';

    // Create the version based on the file modification time
    $mem_game_css_ver = date( 'ymd-Gis', fileatime( $mem_game_css_path ) );

    // Enqueue the files
    wp_enqueue_style( 'mem_game_css', $mem_game_css_url, array(), $mem_game_css_ver );
  }

  // Conditionally load JavaScript and CSS if the page/post contains the shortcode
  public function memgame_scripts() {
    global $post;
    if ( is_page() || is_single() ) {
      // mem_debug( sprintf( 'MemShortcode: This is a single %s', is_page() ? 'page' : 'post' ) );
      if ( has_shortcode( $post->post_content, 'memgame' ) ) {
        // mem_debug( 'MemShortcode: It has the shortcode' );

        // Enqueue the CSS file (in its own function for now)
        self::enqueue_memgame_css();

        // Path to JS and CSS files
        $mem_game_js_path = MEM_GAME_PATH . 'assets/js/mem-game.js';
        $mem_game_js_url = MEM_GAME_URL . 'assets/js/mem-game.js';

        // Create the version based on the file modification time
        $mem_game_js_ver = date( 'ymd-Gis', fileatime( $mem_game_js_path ) );

        // Enqueue the files
        // The borrowed JS needs jQuery (the CodePen used ver 2.1.3, assuming the default WP ver will do)
        wp_enqueue_script( 'mem_game_js', $mem_game_js_url, array( 'jquery' ), $mem_game_js_ver, true );

        // Pass image info to the JS as localized data
        $image_ids = MemSettings::get_image_ids();
        $image_urls = MemSettings::get_image_urls();
        // mem_debug( 'Image IDs' );
        // mem_debug( $image_ids );
        // mem_debug( 'Image URLs' );
        // mem_debug( $image_urls );
        wp_localize_script(
          'mem_game_js',
          'mem_game_img_obj',
          array(
            'images' => $image_urls,
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce' => wp_create_nonce( 'mem_game_stats' )
          )
        );
      } else {
        // mem_debug( 'MemShortcode: It DOES NOT have the shortcode' );
      }
    }
  }
}
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
    <div class="wrap">
      <div class="game"></div>
      <div class="modal-overlay">
        <div class="modal">
          <h2 class="winner">You Rock!</h2>
          <button class="restart">Play Again?</button>
          <p class="message">Developed on <a href="https://codepen.io">CodePen</a> by <a href="https://codepen.io/natewiley">Nate Wiley</a></p>
          <p class="share-text">Share it?</p>
          <ul class="social">
            <li><a target="_blank" class="twitter" href="https://twitter.com/share?url=https://codepen.io/natewiley/pen/HBrbL"><span class="fa fa-twitter"></span></a></li>
            <li><a target="_blank" class="facebook" href="https://www.facebook.com/sharer.php?u=https://codepen.io/natewiley/pen/HBrbL"><span class="fa fa-facebook"></span></a></li>
            <li><a target="_blank" class="google" href="https://plus.google.com/share?url=https://codepen.io/natewiley/pen/HBrbL"><span class="fa fa-google"></span></a></li>
          </ul>
        </div>
      </div>
      <footer>
        <p class="disclaimer">All logos are property of their respective owners, No Copyright infringement intended.</p>
      </footer>
    </div><!-- End Wrap -->
    END;

    return $game;
  }

  // Conditionally load JavaScript and CSS if the page/post contains the shortcode
  public function memgame_scripts() {
    global $post;
    if ( is_page() || is_single() ) {
      mem_debug( sprintf( 'MemShortcode: This is a single %s', is_page() ? 'page' : 'post' ) );
      if ( has_shortcode( $post->post_content, 'memgame' ) ) {
        mem_debug( 'MemShortcode: It has the shortcode' );

        // Path to JS and CSS files
        $mem_game_js_path = MEM_GAME_PATH . 'assets/js/mem-game.js';
        $mem_game_js_url = MEM_GAME_URL . 'assets/js/mem-game.js';
        $mem_game_css_path = MEM_GAME_PATH . 'assets/css/mem-game.css';
        $mem_game_css_url = MEM_GAME_URL . 'assets/css/mem-game.css';

        // Create the version based on the file modification time
        $mem_game_js_ver = date( 'ymd-Gis', fileatime( $mem_game_js_path ) );
        $mem_game_css_ver = date( 'ymd-Gis', fileatime( $mem_game_css_path ) );

        // Enqueue the files
        // The borrowed JS needs jQuery (the CodePen used ver 2.1.3, assuming the default WP ver will do)
        wp_enqueue_script( 'mem_game_js', $mem_game_js_url, array( 'jquery' ), $mem_game_js_ver, true );
        wp_enqueue_style( 'mem_game_css', $mem_game_css_url, array(), $mem_game_css_ver );
      } else {
        mem_debug( 'MemShortcode: It DOES NOT have the shortcode' );
      }
    }
  }
}
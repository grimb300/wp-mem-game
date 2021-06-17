<?php

namespace MemGame;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class MemGame {

  /* **********
   * Properties
   * **********/
  
  /* *******
   * Methods
   * *******/

  // Constructor
  public function __construct() {
    // mem_debug( 'MemGame constructor' );

    // Create the shortcode
    $this->create_shortcode();
  }

  // Run the plugin -- FIXME: Probably not needed
  public function run() {
    // mem_debug( 'MemGame run()' );
  }

  private function create_shortcode() {
    require_once MEM_GAME_PATH . 'includes/class-mem-shortcode.php';
    $mem_shortcode = new MemShortcode();
  }

  // Activation
  public function mem_game_activation() {
    // mem_debug( 'MemGame activate!' );
  }

  // Deactivation
  public function mem_game_deactivation() {
    // mem_debug( 'MemGame deactivate!' );
  }

}
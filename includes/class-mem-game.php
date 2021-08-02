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

    // Initialize the Memory Game CPT
    $this->init_cpt();
    
    // Initialize the settings
    $this->init_settings();
    
    // Initialize the upgrade engine
    // FIXME: Long term this should be a part of the activation flow
    // if ( is_admin() ) {
    //   $this->init_upgrade();
    // }
    // Create the shortcode
    $this->create_shortcode();

    // Initialize the stats table
    // FIXME: Eventually move this into the activate function
    $this->init_stats();
  }

  // Run the plugin -- FIXME: Probably not needed
  public function run() {
    // mem_debug( 'MemGame run()' );
  }

  private function init_cpt() {
    require_once MEM_GAME_PATH . 'includes/class-mem-cpt.php';
    MemCpt::init();
  }

  private function init_upgrade() {
    require_once MEM_GAME_PATH . 'includes/class-mem-upgrade.php';
    MemUpgrade::init();
  }

  private function init_settings() {
    require_once MEM_GAME_PATH . 'includes/class-mem-settings.php';
    // Since I'm using namespaces, the callable must be a fully qualified name
    // NOTE: https://developer.wordpress.org/reference/functions/add_menu_page/#notes
    //       Since I'm adding a top level menu (via add_menu_page),
    //       I have to use the admin_menu hook rather than admin_init.
    //       It returns an "insufficient permissions" message otherwise.
    //       If I change this to a submenu, I might be able to change it back.
    // NOTE: https://developer.wordpress.org/reference/hooks/option_page_capability_option_page/
    //       If I want this accessible by non-admin roles, I might have to update
    //       the permissions via the 'option_page_capability_{$option_group}' hook
    add_action( 'admin_menu', 'MemGame\MemSettings::init' );
    add_action( 'admin_enqueue_scripts', 'MemGame\MemSettings::enqueue_scripts' );
  }

  private function create_shortcode() {
    require_once MEM_GAME_PATH . 'includes/class-mem-shortcode.php';
    $mem_shortcode = new MemShortcode();
  }

  private function init_stats() {
    require_once MEM_GAME_PATH . 'includes/class-mem-stats.php';
    MemStats::init();
  }

  // Activation
  public function mem_game_activation() {
    mem_debug( 'MemGame activate!' );
    // Upgrade anything that needs upgrading
    $this->init_upgrade();
  }

  // Deactivation
  public function mem_game_deactivation() {
    // mem_debug( 'MemGame deactivate!' );
  }

}
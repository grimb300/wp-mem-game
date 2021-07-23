<?php

/**
 * Plugin Name:       WordPress Memory Game
 * Plugin URI:        https://candolatitude.com/
 * Description:       Add a memory game to any WordPress post or page
 * Version:           0.3
 * Requires at least: 5.2
 * Requires PHP:      7.2
 * Author:            Bob Grim
 * Author URI:        https://candolatitude.com/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       mem-game
 */

namespace MemGame;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Plugin wide defines
 */

define( 'MEM_GAME_PATH', plugin_dir_path( __FILE__ ) );
define( 'MEM_GAME_URL', plugin_dir_url( __FILE__ ) );

function mem_debug( $msg ) {
  if ( is_array( $msg ) || is_object( $msg ) || is_bool( $msg ) ) {
    $msg = var_export( $msg, true );
  }
  error_log( 'mem-game: ' . $msg );
}


/**
 * Include the core class responsible for loading all necessary components of the plugin.
 */
if ( !class_exists( "MemGame" ) ) {
	require_once MEM_GAME_PATH . 'includes/class-mem-game.php';

 	/**
	 * Instantiates the MemGame class and then calls its run method officially starting
	 * the plugin.
	 */
  $mem_game = new MemGame();
  register_activation_hook(__FILE__, array($mem_game, 'mem_game_activation'));
  register_deactivation_hook(__FILE__, array($mem_game, 'mem_game_deactivation'));
  $mem_game->run();

}

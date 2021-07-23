<?php

namespace MemGame;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class MemUpgrade {
  /* *******
   * Methods
   * *******/

  // Init the upgrade engine
  public static function init() {
    // mem_debug( 'Executing MemUpgrade::init()' );
    // mem_debug( sprintf( 'Upgrade to version %s', MEM_GAME_PLUGIN_VERSION ) );

    // Get the current plugin version
    $current_version = MEM_GAME_PLUGIN_VERSION;

    // Get the saved plugin version stored in settings
    $saved_version = get_option( 'mem_game_plugin_version' );

    // Compare the saved version with the current plugin version
    if ( empty( $saved_version ) || ( $current_version !== $saved_version ) ) {
      mem_debug(
        sprintf(
          'Upgrading WpMemGame from %s to %s',
          empty( $saved_version ) ? 'N/A' : $saved_version,
          $current_version
        )
      );
      update_option( 'mem_game_plugin_version', $current_version );
    } else {
      mem_debug( sprintf( 'WpMemGame is up to date (version %s)', $current_version ) );
    }
  }
}
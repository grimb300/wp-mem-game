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
    $new_plugin_version = MEM_GAME_PLUGIN_VERSION;

    // Get the saved plugin version stored in settings
    $old_plugin_version = get_option( 'mem_game_plugin_version' );

    // Compare the saved version with the current plugin version
    if ( empty( $old_plugin_version ) || ( $new_plugin_version !== $old_plugin_version ) ) {
      mem_debug(
        sprintf(
          'Upgrading WpMemGame from %s to %s',
          empty( $old_plugin_version ) ? 'N/A' : $old_plugin_version,
          $new_plugin_version
        )
      );

      // Upgrade to version 0.3
      if ( '0.3' === $new_plugin_version ) {
        // Get the legacy memory game definition, if it exists
        $legacy_images = get_option( 'mem_game_images' );
        $legacy_winner_screen = get_option( 'mem_game_winner_screen' );
  
        // If they exist, convert options to custom post
        if ( is_array( $legacy_images ) && is_array( $legacy_winner_screen ) ) {
          // TODO: Should I search the existing posts to see if there is already a legacy post? Don't want more than one.
          $post_id = wp_insert_post( array(
            'post_title' => 'Legacy Memory Game',
            'post_status' => 'publish',
            'post_type' => 'memgame',
            'meta_input' => array(
              'mem_game_images' => serialize( $legacy_images ),
              'mem_game_winner_screen' => serialize( $legacy_winner_screen ),
              'mem_game_legacy' => true,
            ),
          ) );
          mem_debug( 'Converted legacy memory game to post ID ' . $post_id );
        }

        // Delete the options, even if they didn't exist, leave no trace
        delete_option( 'mem_game_images' );
        delete_option( 'mem_game_winner_screen' );
      }

      update_option( 'mem_game_plugin_version', $new_plugin_version );
    } else {
      // mem_debug( sprintf( 'WpMemGame is up to date (version %s)', $new_plugin_version ) );
    }
  }
}
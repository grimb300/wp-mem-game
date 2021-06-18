<?php

namespace MemGame;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// NOTE: I'm going to try and make this entirely a static class

class MemSettings {

  /* **********
   * Properties
   * **********/

  /* *******
   * Methods
   * *******/

  public static function init() {
    mem_debug( 'MemSettings init called' );

    // Register the memory game settings
    register_setting(
      'mem_game_settings', // Settings group (page slug)
      'mem_game_images'    // Setting name
    );

    /* **********************
     * Settings page sections
     * **********************/

    // Card images section
    add_settings_section(
      'mem_game_card_images',             // ID tag of the section
      'Card Images',                      // Section title
      array(                              // Callback for the section description
        'MemGame\MemSettings',            //
        'display_card_images_description' //
      ),                                  //
      'mem_game_settings'                 // Settings page to show the section
    );
    /* **************************
     * END Settings page sections
     * **************************/

    /* ***************
     * Settings fields
     * ***************/

    // Card back image
    add_settings_field(
      'back_image', // ID tag of the field
      'Card Back', // Field title
      array(                   // Callback for the field input
        'MemGame\MemSettings',
        'display_image_input'
      ),
      'mem_game_settings',     // ID of the page to show the field
      'mem_game_card_images',  // ID of the section to show the field
      array(                   // Args passed to the callback
        'label_for' => 'back_image',
        'class' => 'mem_game_settings_row',
      )
    );

    /* *******************
     * END Settings fields
     * *******************/

    // Add the settings page as its own admin menu item
    // FIXME: Would it be better to make it a submenu under an existing menu (maybe Settings)
    add_menu_page(
      'Memory Game Settings',                       // Page title (title tag value)
      'Memory Game',                                // Menu title
      'manage_options',                             // Capability required to display menu item
      'mem_game_settings',                          // Menu slug
      'MemGame\MemSettings::display_settings_page', // Display callback
      'dashicons-forms',                            // Icon
      1                                             // Position (putting it at the top for now)
    );

  }

  // Display the settings page card images section description
  public static function display_card_images_description( $args ) {
    ?>
    <p id="<?php echo esc_attr( $args[ 'id' ] ); ?>"><?php esc_html_e( 'Images for the front and back of the cards used in the memory game' ); ?></p>
    <?php
  }

  // Display the image picker input
  // I'm looking at a couple of tutorials to figure out how to do this:
  //   Codex page (this is probably the best resource)
  //     https://codex.wordpress.org/Javascript_Reference/wp.media
  //   Random tutorial (looks like it may be a little old)
  //     https://jeroensormani.com/how-to-include-the-wordpress-media-selector-in-your-plugin/
  public static function display_image_input( $args ) {
    // Get the name of the setting value to be displayed
    $option_name = $args[ 'label_for' ];

    // Get the current value
    $image_options = get_option( 'mem_game_images' );
    $option_value = $image_options[ $option_name ];

    // Display the media picker
    ?>
    <div class="preview_image_wrapper">
      <img id="preview_image_<?php echo $option_name; ?>" src="" alt="Card Image" width="100" height="100" style="max-height: 100px; width: 100px;">
    </div>
    <input id="upload_image_<?php echo $option_name; ?>" type="button" class="button" value="Upload Image">
    <input type="hidden" name="mem_game_images[<?php echo $option_name ?>]" id="image_id_<?php echo $option_name ?>" value="">
    <?php
  }

  // Display the settings page
  public static function display_settings_page() {
    // Check user capabilities
    if ( ! current_user_can( 'manage_options' ) ) {
      return;
    }

    // Display the page
    ?>
    <h1>Manage Memory Game Settings!!!!!</h1>
    <form action="options.php" method="post">
      <?php
      // Output nonce, action, and option_page fields
      // Use the group name (page) from register_setting()
      settings_fields( 'mem_game_settings' );
      // Prints out all settings sections added to a particular settings page
      // Use the slug page name (I think group name) from register_setting()
      do_settings_sections( 'mem_game_settings' );
      // Echoes a submit button, with provided text and appropriate class(es).
      // Passing it the text of the button
      submit_button( 'Save Settings' );
      ?>
    </form>
    <?php
  }
  
}
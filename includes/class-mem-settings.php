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
      'Card Back Image', // Field title
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

  // If this is the settings page, enqueue the media picker scripts
  public static function enqueue_scripts( $hook_suffix ) {
    if ( 'toplevel_page_mem_game_settings' === $hook_suffix ) {
      // Make sure the WP media scripts and styles are loaded
      wp_enqueue_media();
      // Get the path to JS and CSS files
      $mem_media_picker_js_path = MEM_GAME_PATH . 'assets/js/mem-media-picker.js';
      $mem_media_picker_js_url = MEM_GAME_URL . 'assets/js/mem-media-picker.js';
      // Create the version based on the file modification time
      $mem_media_picker_js_ver = date( 'ymd-Gis', fileatime( $mem_media_picker_js_path ) );
      // Enqueue the files
      // The borrowed JS needs jQuery
      wp_enqueue_script( 'mem_media_picker_js', $mem_media_picker_js_url, array( 'jquery' ), $mem_media_picker_js_ver, true );
      
    }
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
    $option_img_id = is_array( $image_options ) && array_key_exists( $option_name, $image_options ) ? $image_options[ $option_name ] : -1;

    /***************************************************
     * Display the media picker
     * Based loosely on the WordPress Codex (link above)
     ***************************************************/

    // Get WordPress' media upload URL
    $upload_link = esc_url( get_upload_iframe_src( 'image' ) );

    // Get the image src
    $img_src = wp_get_attachment_image_src( $option_img_id, 'full' );

    // For convenience, see if the array is valid
    $valid_img = is_array( $img_src );

    ?>
    <div id="mem_game_card_image" style="display: flex; align-items: center;">
      <div class="custom-img-container<?php echo $valid_img ? '' : ' hidden'; ?>">
        <img src="<?php echo $valid_img ? $img_src[0] : ''; ?>" alt="Card Image" width="100" height="100" style="max-height: 100px; width: 100px;">
      </div>
      <p class="hide-if-no-js">
        <a class="button upload-custom-img" href="<?php echo $upload_link ?>" style="margin-left: 1em;">
          <?php $valid_img ? _e('Update') : _e('Add') ?>
        </a>
      </p>
      <input class="custom-img-id" name="mem_game_images[back_image]" type="hidden" value="<?php echo esc_attr( $option_img_id ); ?>" />
    </div>
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
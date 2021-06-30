<?php

namespace MemGame;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// NOTE: I'm going to try and make this entirely a static class

class MemSettings {

  /* **********
   * Properties
   * **********/

  public static $images = array(
    array(
      'type' => 'card_back',
      'title' => 'Card Back',
      'num_imgs' => 1,
      'default_urls' => array(
        MEM_GAME_URL . 'assets/images/social-memory.png',
      ),
    ),
    array(
      'type' => 'card_front',
      'title' => 'Card Front',
      'num_imgs' => 12,
      'default_urls' => array(
        MEM_GAME_URL . 'assets/images/dropbox-logo.png',
        MEM_GAME_URL . 'assets/images/facebook-logo.png',
        MEM_GAME_URL . 'assets/images/instagram-logo.png',
        MEM_GAME_URL . 'assets/images/linkedin-logo.png',
        MEM_GAME_URL . 'assets/images/pinterest-logo.png',
        MEM_GAME_URL . 'assets/images/skype-logo.png',
        MEM_GAME_URL . 'assets/images/snapchat-logo.png',
        MEM_GAME_URL . 'assets/images/spotify-logo.png',
        MEM_GAME_URL . 'assets/images/twitter-logo.png',
        MEM_GAME_URL . 'assets/images/vimeo-logo.png',
        MEM_GAME_URL . 'assets/images/whatsapp-logo.png',
        MEM_GAME_URL . 'assets/images/youtube-logo.png',
      ),
    ),
  );

  /* *******
   * Methods
   * *******/

  public static function init() {
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

    // Iterate across all images in self::$images
    foreach( self::$images as $info ) {
      add_settings_field(
        $info[ 'type' ], // ID tag of the field
        $info[ 'title' ] . ' Image', // Field title
        array(                   // Callback for the field input
          'MemGame\MemSettings',
          'display_image_input'
        ),
        'mem_game_settings',     // ID of the page to show the field
        'mem_game_card_images',  // ID of the section to show the field
        array(                   // Args passed to the callback
          'label_for' => $info[ 'type' ],
          'class' => 'mem_game_settings_row',
          'default_urls' => $info[ 'default_urls' ],
          'num_imgs' => $info[ 'num_imgs' ],
        )
      );
    }

    // Card back image

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
      30                                             // Position (30 puts it after the "Comments" menu item)
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

      // Enqueue the frontend CSS file to make the card images look accurate
      require_once MEM_GAME_PATH . 'includes/class-mem-shortcode.php';
      MemShortcode::enqueue_memgame_css();
      
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
    // Get the name of the setting value to be displayed, number of images and the default image urls
    $option_name = $args[ 'label_for' ];
    $num_imgs = $args[ 'num_imgs' ];
    $default_urls = $args[ 'default_urls' ];

    // Get the current value
    $image_ids = self::get_image_ids();
    $this_image_id =
      is_array( $image_ids ) && array_key_exists( $option_name, $image_ids )
      ? $image_ids[ $option_name ]
      : array_fill( 0, $num_imgs, -1 );

    /***************************************************
     * Display the media picker
     * Based loosely on the WordPress Codex (link above)
     ***************************************************/

    // Get WordPress' media upload URL
    $upload_link = esc_url( get_upload_iframe_src( 'image' ) );
    
    ?>
    <div class="mg-wrap">
      <!-- Override the default grid template with 6 columns at 100px each -->
      <div class="mg-game" style="grid-template-columns: repeat(6, 100px);">
      <?php
      // Loop based on the number of images associated with this option
      for ( $i = 0; $i < $num_imgs; $i++ ) {
        // Get the image src
        $image_src = wp_get_attachment_image_src( $this_image_id[ $i ], 'full' );
        // For convenience, see if the array is valid
        $valid_image = is_array( $image_src );
        ?>
        <div div class="mg-card" style="width: 100px;">
          <div class="mg-inside">
            <div class="mg-back">
              <img id="img-<?php echo sprintf( '%s-%d', $option_name, $i ); ?>" src="<?php echo $valid_image ? $image_src[0] : $default_urls[ $i ]; ?>" alt="Card Image">
            </div> <!-- .mg-back -->
          </div> <!-- .mg-inside -->
          <p class="hide-if-no-js">
            <a class="button upload-custom-img" href="<?php echo $upload_link ?>">
              <?php _e('Update'); ?>
            </a>
          </p>
          <input class="custom-img-id" name="mem_game_images[<?php echo $option_name; ?>][<?php echo $i; ?>]" type="hidden" value="<?php echo esc_attr( $this_image_id[$i] ); ?>" />
        </div> <!-- .mg-card -->
        <?php
      }
      ?>
      </div> <!-- .mg-game -->
    </div> <!-- .mg-wrap -->
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
    <h1>Memory Game Settings</h1>
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
    <h1>Memory Game Statistics</h1>
    <?php $stats = MemStats::get_analyzed_stats(); ?>
    <?php
  }

  // Retrieve the image IDs
  public static function get_image_ids() {
    return get_option( 'mem_game_images' );
  }

  // Retrieve the image source URLs
  // This will return an associative array of ( image_type => array( image_urls ) )
  // and will return a default image_url for missing images
  // FIXME: This makes the assumption that the $images array defined above is the golden copy
  //        regarding the type and number of images.
  //        The indices contained in the options can only modify the default URLs.
  //        If the number of cards is every configurable, this assumption breaks.
  public static function get_image_urls() {
    // Image types
    // TODO: This should be a class property
    $image_types = self::get_image_types();

    // Get the IDs stored in options
    $image_ids = self::get_image_ids();

    // Convert the IDs into URLs, using a default value if no ID is present
    $image_urls = array();
    foreach ( $image_types as $image_type ) {
      // Get the default URLs for this image type
      $image_defaults = self::get_image_default_url( $image_type );

      // Convert the image IDs for this type to URLs
      $converted_urls = array_map(
        function ( $image_id ) {
          // Get the image source
          $image_src = wp_get_attachment_image_src( $image_id, 'full' );
          // If a valid image is found, return the URL, else return an empty string ''
          return is_array( $image_src ) ? $image_src[0] : '';
        },
        // If there aren't any image IDs for this type, use an empty array
        is_array( $image_ids[ $image_type ] ) ? $image_ids[ $image_type ] : array()
      );

      // Merge the converted URLs into the defaults and update the image URLs array
      foreach ( $image_defaults as $image_index => $image_default ) {
        $image_urls[ $image_type ][ $image_index ] =
          empty( $converted_urls[ $image_index ] )
          ? $image_default
          : $converted_urls[ $image_index ];
      }
    }
    return $image_urls;
  }

  /**
   * Accessor functions for the data contained in self::$images
   */

  // Get an array of image types
  private static function get_image_types() {
    return array_map( function ( $image ) {
      return $image[ 'type' ];
    }, self::$images );
  }

  // Get an array of default URLs for an image type
  private static function get_image_default_url( $image_type ) {
    $filtered_images = array_filter( self::$images, function( $image ) use ( $image_type ) {
      return $image_type === $image[ 'type' ];
    } );
    // Since array_filter preserves keys, compact the resulting array
    $compacted_images = array_values( $filtered_images );
    // Assume that there is only one match
    $this_image = $compacted_images[0];
    return $this_image[ 'default_urls' ];
  }
  
}
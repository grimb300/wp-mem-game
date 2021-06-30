<?php

namespace MemGame;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// NOTE: I'm going to try and make this entirely a static class

class MemSettings {

  /* **********
   * Properties
   * **********/

  public static $images = array(
    'card_back' => array(
      'title' => 'Card Back Image',
      'num_imgs' => 1,
      'defaults' => array(
        array( 'id' => -1, 'fit' => 'scale-down', 'url' => MEM_GAME_URL . 'assets/images/social-memory.png' ),
      ),
    ),
    'card_front' => array(
      'title' => 'Card Front Images',
      'num_imgs' => 12,
      'defaults' => array(
        array( 'id' => -1, 'fit' => 'scale-down', 'url' => MEM_GAME_URL . 'assets/images/dropbox-logo.png' ),
        array( 'id' => -1, 'fit' => 'scale-down', 'url' => MEM_GAME_URL . 'assets/images/facebook-logo.png' ),
        array( 'id' => -1, 'fit' => 'scale-down', 'url' => MEM_GAME_URL . 'assets/images/instagram-logo.png' ),
        array( 'id' => -1, 'fit' => 'scale-down', 'url' => MEM_GAME_URL . 'assets/images/linkedin-logo.png' ),
        array( 'id' => -1, 'fit' => 'scale-down', 'url' => MEM_GAME_URL . 'assets/images/pinterest-logo.png' ),
        array( 'id' => -1, 'fit' => 'scale-down', 'url' => MEM_GAME_URL . 'assets/images/skype-logo.png' ),
        array( 'id' => -1, 'fit' => 'scale-down', 'url' => MEM_GAME_URL . 'assets/images/snapchat-logo.png' ),
        array( 'id' => -1, 'fit' => 'scale-down', 'url' => MEM_GAME_URL . 'assets/images/spotify-logo.png' ),
        array( 'id' => -1, 'fit' => 'scale-down', 'url' => MEM_GAME_URL . 'assets/images/twitter-logo.png' ),
        array( 'id' => -1, 'fit' => 'scale-down', 'url' => MEM_GAME_URL . 'assets/images/vimeo-logo.png' ),
        array( 'id' => -1, 'fit' => 'scale-down', 'url' => MEM_GAME_URL . 'assets/images/whatsapp-logo.png' ),
        array( 'id' => -1, 'fit' => 'scale-down', 'url' => MEM_GAME_URL . 'assets/images/youtube-logo.png' ),
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
    foreach( self::$images as $image_type => $info ) {
      add_settings_field(
        $image_type,  // ID tag of the field
        $info[ 'title' ], // Field title
        array(            // Callback for the field input
          'MemGame\MemSettings',
          'display_image_input'
        ),
        'mem_game_settings',     // ID of the page to show the field
        'mem_game_card_images',  // ID of the section to show the field
        array(                   // Args passed to the callback
          'label_for' => $image_type,
          'class' => 'mem_game_settings_row',
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
    // Get the info for the image type to be displayed
    $image_type = $args[ 'label_for' ];
    $image_info = self::get_image_type_info( $image_type );

    /***************************************************
     * Display the media picker
     * Based loosely on the WordPress Codex (link above)
     ***************************************************/

    // Get WordPress' media upload URL
    $upload_link = esc_url( get_upload_iframe_src( 'image' ) );
    
    ?>
    <!-- Adding some inline styles not in the frontend CSS -->
    <style>
      .img-picker-control {
        margin: 5px auto 0;
      }
    </style>
    <div class="mg-wrap">
      <!-- Override the default grid template with 6 columns at 100px each and a grid gap of 10px -->
      <div class="mg-game" style="grid-template-columns: repeat(6, 100px);">
      <?php
      // Loop across the image info associated with this option
      foreach ( $image_info as $index => $info ) {
        ?>
        <div div class="mg-card" style="width: 100px;">
          <div class="mg-inside">
            <div class="mg-back">
              <img class="mg-fit-<?php echo $info[ 'fit' ]; ?>" id="img-<?php echo sprintf( '%s-%d', $image_type, $index ); ?>" src="<?php echo $info[ 'url' ]; ?>" alt="Card Image">
            </div> <!-- .mg-back -->
          </div> <!-- .mg-inside -->
          <div class="img-picker-control">
            <label class="screen-reader-text" for="scale-<?php echo sprintf( '%s-%s', $image_type, $index ) ?>">Image Scaling:</label>
            <select class="select-img-fit" id="scale-<?php echo sprintf( '%s-%s', $image_type, $index ) ?>" name="mem_game_images[<?php echo $image_type; ?>][<?php echo $index; ?>][fit]">
              <option value="scale-down">Scale</option>
              <option value="cover"<?php if ( 'cover' === $info[ 'fit' ] ) { echo ' selected'; } ?>>Cover</option>
            </select>
          </div>
          <div class="img-picker-control">
            <a class="button upload-custom-img" href="<?php echo $upload_link ?>">
              <?php _e('Update'); ?>
            </a>
          </div>
          <!-- <p class="hide-if-no-js">
          </p> -->
          <input class="custom-img-id" name="mem_game_images[<?php echo $image_type; ?>][<?php echo $index; ?>][id]" type="hidden" value="<?php echo esc_attr( $info[ 'id' ] ); ?>" />
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

  // Retrieve all image options
  private static function get_image_option() {
    return get_option( 'mem_game_images' );
  }

  // Retrieve options for a particular image type
  private static function get_image_type_option( $image_type ) {
    $all_options = self::get_image_option();
    return $all_options[ $image_type ];
  }

  // Get the info for a given image type
  private static function get_image_type_info( $image_type ) {
    // Get the current option value for this image
    $image_type_option = self::get_image_type_option( $image_type );

    // FIXME: Using the "defaults" defined above as the golden copy of how many images of each type there are.
    //        This will break if/when the number of cards is ever configurable.

    // Grab the defaults for this image type and preload the return value
    $return_info = self::$images[ $image_type ][ 'defaults' ];

    // If the option exists and is an array,
    // iterate across the images in the option updating the return values as needed
    if ( is_array( $image_type_option ) ) {
      foreach ( $image_type_option as $index => $option ) {
        // If this index is in the defaults and the image ID exists, get the image source info for that id
        if ( array_key_exists( $index, $return_info ) && is_array( $option ) && array_key_exists( 'id', $option ) ) {
          // Get the image source
          $image_src = wp_get_attachment_image_src( $option[ 'id' ], 'full' );

          // If a valid image is found, update the return value for this index
          if ( is_array( $image_src ) ) {
            $return_info[ $index ] = array(
              'id' => $option[ 'id' ],
              'url' => $image_src[0],
              'fit' => array_key_exists( 'fit', $option ) ? $option[ 'fit' ] : $return_info[ $index ][ 'fit' ]
            );
          } // if valid image
        } // if option has an id
      } // foreach option
    } // if option is an array

    // Return the info
    return $return_info;
  }

  // Return the image types
  private static function get_image_types() {
    return array_keys( self::$images );
  }

  // Get the localized data to be sent to the front end JS
  public static function get_localized_image_data() {
    $return_data = array();

    // Iterate across the image types
    foreach ( self::get_image_types() as $image_type ) {
      $return_data[ $image_type ] = array_map(
        function ( $info ) {
          // Return the fit and url from the image type info
          return array(
            'fit' => $info[ 'fit' ],
            'url' => $info[ 'url' ]
          );
        },
        self::get_image_type_info( $image_type )
      );
    }

    // Return the data
    return $return_data;
  }
  
}
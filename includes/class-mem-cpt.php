<?php

namespace MemGame;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class MemCpt {

  /* **********
   * Properties
   * **********/

  private static $slug = 'memgame';
  private static $singular_name = 'Memory Game';
  private static $plural_name = 'Memory Games';
  private static $icon = 'dashicons-forms';

  /* *******
   * Methods
   * *******/

  public static function init() {
    add_action( 'init', 'MemGame\MemCpt::register_mem_cpt' );
    add_action( 'admin_enqueue_scripts', 'MemGame\MemCpt::enqueue_scripts' );
    add_action( 'save_post', 'MemGame\MemCpt::save_mem_cpt_post_meta' );
    add_filter( sprintf( 'manage_%s_posts_columns', self::$slug ), 'MemGame\MemCpt::add_cpt_custom_columns' );
    add_action( sprintf( 'manage_%s_posts_custom_column', self::$slug ), 'MemGame\MemCpt::display_cpt_custom_column', 10, 2 );
    add_filter( 'post_row_actions', 'MemGame\MemCpt::remove_quick_edit', 10, 2 );
    // add_action( 'current_screen', 'MemGame\MemCpt::current_screen' );
    add_filter( sprintf( 'bulk_actions-edit-%s', self::$slug ), 'MemGame\MemCpt::remove_edit_bulk_action' );
  }

  public static function register_mem_cpt() {
    $cpt_labels = array(
      'name' => self::$plural_name,
      'singular_name' => self::$singular_name,
      'add_new_item' => sprintf( 'Add New %s', self::$singular_name ),
      'edit_item' => sprintf( 'Edit %s', self::$singular_name ),
      'new_item' => sprintf( 'New %s', self::$singular_name ),
      'view_item' => sprintf( 'View %s', self::$singular_name ),
      'view_items' => sprintf( 'View %s', self::$plural_name ),
      'search_items' => sprintf( 'Search %s', self::$plural_name ),
      'not_found' => sprintf( 'No %s found', self::$plural_name ),
      'not_found_in_trash' => sprintf( 'No %s found in Trash', self::$plural_name ),
      'all_items' => sprintf( 'All %s', self::$plural_name ),
    );
    $cpt_args = array(
      'labels' => $cpt_labels,
      'description' => 'Memory Game instance',
      'public' => true,
      'exclude_from_search' => true,
      'publicly_queryable' => false,
      'show_in_rest' => false,
      'menu_position' => 30, // Position (30 puts it after the "Comments" menu item)
      'menu_icon' => self::$icon,
      'supports' => array( 'title' ),
      'register_meta_box_cb' => 'MemGame\MemCpt::display_mem_cpt_meta_box',
      'rewrite' => false,
    );

    register_post_type( self::$slug, $cpt_args );
  }

  public static function display_mem_cpt_meta_box() {
    add_meta_box(
      'mem_game_meta_box',
      'Memory Game Settings',
      'MemGame\MemCpt::render_mem_cpt_meta_box',
      'memgame', 'normal',
      'high',
    );
  }

  public static function save_mem_cpt_post_meta( $post_id ) {
    // Creating my own version of $_POST because of WP core adding slashes for "magic quotes"
    // This seems like a really hacky way of fixing it, but this is the solution in the WP docs
    // https://developer.wordpress.org/reference/functions/stripslashes_deep/
    // https://core.trac.wordpress.org/ticket/18322
    $my_post = stripslashes_deep($_POST);

    // Save mem_game_board_layout
    if ( array_key_exists( 'mem_game_board_layout', $my_post) ) {
      update_post_meta( $post_id, 'mem_game_board_layout', $my_post[ 'mem_game_board_layout' ] );
    }

    // Save mem_game_images
    if ( array_key_exists( 'mem_game_images', $my_post ) ) {
      update_post_meta( $post_id, 'mem_game_images', serialize( $my_post[ 'mem_game_images' ] ) );
    }

    // Save mem_game_winner_screen
    if ( array_key_exists( 'mem_game_winner_screen', $my_post ) ) {
      update_post_meta( $post_id, 'mem_game_winner_screen', serialize( $my_post[ 'mem_game_winner_screen' ] ) );
    }
  }

  // Create the shortcode for display
  private static function display_shortcode( $memgame_id = null ) {
    // TODO: Create a copy to clipboard button for the shortcode(s)
    // TODO: This will only work as written if the shortcode is in a text input, need to work on it.
    // $shortcode = '<span class=mg-shortcode-wrap><code class="mg-shortcode">[memgame id=' . $memgame_id . ']</code></span>';
    $shortcode = '<code>[memgame id=' . $memgame_id . ']</code>';
    if ( get_post_meta( $memgame_id, 'mem_game_legacy', true ) ) {
      // Add the legacy shortcode
      // TODO: This will only work as written if the shortcode is in a text input, need to work on it.
      // $shortcode .= ' or <span class=mg-shortcode-wrap><code class="mg-shortcode">[memgame]</code></span>';
      $shortcode .= ' or <code>[memgame]</code>';
    }
    return $shortcode;
  }

  public static function render_mem_cpt_meta_box( $post ) {
    ?>
    <p>To add the memory game to your post or page, use the shortcode <?php echo self::display_shortcode( $post->ID ); ?></p>
    <h3>Board Layout</h3>
    <p id="mem_game_board_layout_desc">Game board dimensions in cards wide x cards high</p>
    <table class="form-table">
      <tbody>
        <tr>
          <th scope="row">Dimensions</th>
          <td>
            <select name="mem_game_board_layout" id="mem_game_board_layout">
              <?php $current_layout = self::get_board_layout( $post->ID ); ?>
              <?php foreach( self::$default_board_layout_info[ 'options' ] as $layout ) { ?>
                <option value="<?php echo $layout; ?>" <?php echo $layout === $current_layout ? 'selected' : ''; ?>><?php echo $layout; ?></option>
              <?php } ?>
            </select>
          </td>
          <td>
            <div class="mg-wrap">
              <div class="mg-game mg-layout-<?php echo $current_layout; ?> mg-board-layout">
              <?php
              // Loop across the 24 cards used in the layout
              for ( $i = 0; $i < 24; $i += 1 ) {
                ?>
                <div div class="mg-card">
                  <div class="mg-inside">
                    <div class="mg-back">
                    </div> <!-- .mg-back -->
                  </div> <!-- .mg-inside -->
                </div> <!-- .mg-card -->
                <?php
              }
              ?>
              </div> <!-- .mg-game -->
            </div> <!-- .mg-wrap -->
          </td>
        </tr>
      </tbody>
    </table>
    <h3>Card Images</h3>
    <p id="mem_game_card_images">Images for the front and back of the cards used in the memory game</p>
    <table class="form-table">
      <tbody>
        <?php foreach( self::get_images( $post->ID ) as $image_type => $image_type_data ) { ?>
        <tr>
          <th scope="row"><?php echo self::get_image_type_title( $image_type ); ?></th>
          <td>
            <?php self::display_image_input( $image_type, $image_type_data ); ?>
          </td>
        </tr>
        <?php } ?>
      </tbody>
    </table>
    <h3>Winner Screen</h3>
    <p id="mem_game_winner_screen">Text and buttons displayed on the "winner screen" of the memory game</p>
    <table class="form-table">
      <tbody>
        <?php foreach( self::get_winner_screen( $post->ID ) as $field => $data ) { ?>
        <tr>
          <th scope="row"><?php echo self::get_winner_screen_title( $field ); ?></th>
          <td>
            <?php
            $input_type = self::get_winner_screen_input_type( $field );
            self::display_winner_screen_input( $field, $input_type, $data );
            ?>
          </td>
        </tr>
        <?php } ?>
      </tbody>
    </table>
    <?php
  }

  /**
   * Meta box display supporting properties and methods
   * 
   * Most of this has been directly copied from class-mem-settings.php and needs to be cleaned up eventually
   * 
   * If I was planning to keep both the settings and cpt methods of storing game definitions alive,
   * it would make sense to store a lot of this in a common class that could be used by both methods.
   * However, the cpt method is really the only viable long term solution. So, eventually it would be
   * best to pull out only the most reusable code. For example, the image picker...
   */

  // Image data structure and defaults
  private static $default_images_info = array(
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

  // Winner screen data structure and defaults
  private static $default_winner_screen_info = array(
    'winner_msg' => array(
      'title' => 'Winner Message',
      'input_type' => 'text',
      'default' => 'You Rock!',
    ),
    'play_again_txt' => array(
      'title' => 'Play Again Button Text',
      'input_type' => 'text',
      'default' => 'Play Again',
    ),
    'quit_txt' => array(
      'title' => 'Quit Button Text',
      'input_type' => 'text',
      'default' => 'Quit',
    ),
    'quit_url' => array(
      'title' => 'Quit Button Link URL',
      'input_type' => 'url',
      'default' => '',
    ),
  );

  // Board layout options
  // TODO: Eventually enable layouts with different numbers of images (4x4, 6x6, 3x4, 4x3, etc)
  private static $default_board_layout_info = array(
    'options' => array( '6x4', '4x6' ),
    'default' => '6x4'
  );

    // Display the image picker input
  // I'm looking at a couple of tutorials to figure out how to do this:
  //   Codex page (this is probably the best resource)
  //     https://codex.wordpress.org/Javascript_Reference/wp.media
  //   Random tutorial (looks like it may be a little old)
  //     https://jeroensormani.com/how-to-include-the-wordpress-media-selector-in-your-plugin/
  public static function display_image_input( $image_type, $image_info ) {
    /***************************************************
     * Display the media picker
     * Based loosely on the WordPress Codex (link above)
     ***************************************************/

    // Get WordPress' media upload URL
    $upload_link = esc_url( get_upload_iframe_src( 'image' ) );
    
    // TODO: I'm hard coding the mg-layout- class to be 6x4,
    //       it doesn't make as much sense here to have it change with the layout selected.
    //       Might revisit when making the number of images variable
    ?>
    <!-- Adding some inline styles not in the frontend CSS -->
    <style>
      .img-picker-control {
        margin: 5px auto 0;
      }
    </style>
    <div class="mg-wrap">
      <div class="mg-game mg-layout-6x4 mg-img-picker">
      <?php
      // Loop across the image info associated with this option
      foreach ( $image_info as $index => $info ) {
        ?>
        <div div class="mg-card">
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
          <input class="custom-img-id" name="mem_game_images[<?php echo $image_type; ?>][<?php echo $index; ?>][id]" type="hidden" value="<?php echo esc_attr( $info[ 'id' ] ); ?>" />
        </div> <!-- .mg-card -->
        <?php
      }
      ?>
      </div> <!-- .mg-game -->
    </div> <!-- .mg-wrap -->
    <?php
  }

  /* ****************************
   * New image accessor functions
   * ****************************/

  // Get the image defaults out of default_images_info
  private static function get_image_defaults() {
    $image_defaults = array();
    foreach( self::$default_images_info as $image_type => $image_info ) {
      $image_defaults[ $image_type ] = $image_info[ 'defaults' ];
    }
    return $image_defaults;
  }

  // Get the image type title for display on the edit screen
  private static function get_image_type_title( $image_type ) {
    return self::$default_images_info[ $image_type ][ 'title' ];
  }

  // Get the url for an image using the provided image id
  private static function get_image_url( $image_id ) {
    // Get the image source
    $image_src = wp_get_attachment_image_src( $image_id, 'full' );
    // If it is an array, return the url (index 0), else return null
    return is_array( $image_src ) ? $image_src[0] : null;
  }

  // Get the image data out of post meta using the required memgame_id
  // If no data exists, use the defaults
  private static function get_images( $memgame_id ) {
    // Get the image defaults
    $image_defaults = self::get_image_defaults();
    // Get the image data out of post meta
    $raw_images = unserialize( get_post_meta( $memgame_id, 'mem_game_images', true ) );
    // If there is no post meta data, return the defaults
    if ( ! is_array( $raw_images ) ) return $image_defaults;

    // Map the raw post meta data into the form being returned
    // FIXME: I really want to use array_map here, but it is proving difficult right now.
    //        Revisit at a later time if I get inspired with a new idea.
    $return_images = array();
    foreach ( $raw_images as $image_type => $image_type_data ) {
      foreach ( $image_type_data as $image_index => $image_data ) {
        // Get the url for this image
        $image_url = self::get_image_url( $image_data[ 'id' ] );

        // If the url is null, return the default image data
        // Else, return the raw image data plus the image url
        $return_images[ $image_type ][ $image_index ] =
          is_null( $image_url )
          ? $image_defaults[ $image_type ][ $image_index ]
          : array(
            'id' => $image_data[ 'id' ],
            'fit' => $image_data[ 'fit' ],
            'url' => $image_url
        );
      }
    }

    // Return the mapped image data
    return $return_images;
  }

  // Format the image data for use in wp_localize_script
  public static function get_images_for_localize_script( $memgame_id ) {
    $all_image_data = self::get_images( $memgame_id );
    // Return only the fit and url from each image
    $return_image_data = array();
    foreach( $all_image_data as $image_type => $image_type_data ) {
      $return_image_data[ $image_type ] = array_map(
        function( $image_data ) {
          return array(
            'fit' => $image_data[ 'fit' ],
            'url' => $image_data[ 'url' ]
          );
        },
        $image_type_data
      );
    }
    return $return_image_data;
  }

  /* ********************************
   * End new image accessor functions
   * ********************************/

  // If this is a memgame post edit page, enqueue the media picker scripts
  public static function enqueue_scripts( $hook_suffix ) {
    global $post_type;
    // mem_debug( 'In enqueue_scripts, hook_suffix is ' . $hook_suffix );
    // mem_debug( 'In enqueue_scripts, post_type is ' . $post_type );
    if ( 'memgame' === $post_type ) {
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

  // Display the winner screen inputs
  public static function display_winner_screen_input( $field, $input_type, $value ) {
    ?>
    <input
      type="<?php echo $input_type; ?>"
      name="<?php echo sprintf( 'mem_game_winner_screen[%s]', $field ); ?>"
      id="<?php echo $field; ?>"
      value="<?php echo $value; ?>"
      <?php if ( "url" === $input_type ) { echo 'placeholder="https://"'; } ?>
      size="40"
    />
    <?php
  }

  // Retrieve winner screen data, memgame_id is required
  public static function get_winner_screen( $memgame_id ) {
    // Get the current data out of postmeta
    $current_options = unserialize( get_post_meta( $memgame_id, 'mem_game_winner_screen', true ) );

    // Return the current options array if it exists, the defaults if it does not
    return
      is_array( $current_options )
      ? $current_options
      : array_map(
        function( $info ) {
          return $info[ 'default' ];
        },
        self::$default_winner_screen_info
      );
  }

  // Retrieve the title used on the edit screen for this field
  public static function get_winner_screen_title( $field ) {
    return self::$default_winner_screen_info[ $field ][ 'title' ];
  }

  // Retrieve the input type used on the edit screen for this field
  public static function get_winner_screen_input_type( $field ) {
    return self::$default_winner_screen_info[ $field ][ 'input_type' ];
  }

  // Retrieve the game board layout, memgame_id is required
  public static function get_board_layout( $memgame_id ) {
    $current_layout = get_post_meta( $memgame_id, 'mem_game_board_layout', true );

    // Return the current layout if it exists, the default if it doesn't
    return empty( $current_layout ) ? self::$default_board_layout_info[ 'default' ] : $current_layout;
  }

  /* **************************
   * Edit screen customizations
   * **************************/

  // Add columns to the memgame list
  public static function add_cpt_custom_columns( $old_columns ) {
    // Add columns for shortcode and board layout
    $columns = array(
      'cb' => $old_columns[ 'cb' ],
      'title' => 'Game Description',
      'shortcode' => 'Shortcode',
      'layout' => 'Board Layout',
      'date' => 'Date'
    );
    return $columns;
  }
  
  // Display the new columns
  public static function display_cpt_custom_column( $column, $post_id ) {
    // Display the shortcode
    if ( 'shortcode' === $column ) {
     echo self::display_shortcode( $post_id );
    }

    // Display the board layout
    if ( 'layout' === $column ) {
      echo self::get_board_layout( $post_id );
    }
  }

  // Remove 'Quick Edit' from the post row inline actions
  // It doesn't make sense for this CPT
  public static function remove_quick_edit( $old_actions, $post ) {
    if ( self::$slug === $post->post_type ) {
      // Make a copy, filtering out the 'inline hide-if-no-js' element (this is 'Quick Edit')
      $new_actions = array();
      foreach( array_keys( $old_actions ) as $key ) {
        if ( 'inline hide-if-no-js' !== $key ) {
          $new_actions[ $key ] = $old_actions[ $key ];
        }
      }
      // Return the filtered actions
      return $new_actions;
    }
    // Return the original list of actions
    return $old_actions;
  }

  // Actions to be performed on the current screen
  // Found this gem here: https://wordpress.org/support/topic/disable-quick-edit/
  // Only used to figure out the screen id to filter the 'Edit' bulk action
  // public static function current_screen( $screen ) {
  //   if ( empty( $screen->id ) ) return;
  //   mem_debug( 'My screen id is ' . $screen->id );
  // }

  // Filter out the 'Edit' bulk action (essentially the same as the 'Quick Edit' inline action)
  // It doesn't make sense for this CPT
  public static function remove_edit_bulk_action( $old_actions ) {
    // Make a copy, filtering out the 'edit' element
    $new_actions = array();
    foreach( array_keys( $old_actions ) as $key ) {
      if ( 'edit' !== $key ) {
        $new_actions[ $key ] = $old_actions[ $key ];
      }
    }
    return $new_actions;
  }

  /* ******************************
   * End edit screen customizations
   * ******************************/

  }
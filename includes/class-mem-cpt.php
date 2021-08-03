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
      //add_new – Default is ‘Add New’ for both hierarchical and non-hierarchical types. When internationalizing this string, please use a gettext context matching your post type. Example: _x( 'Add New', 'product', 'textdomain' );.
      'add_new_item' => sprintf( 'Add New %s', self::$singular_name ),
      'edit_item' => sprintf( 'Edit %s', self::$singular_name ),
      'new_item' => sprintf( 'New %s', self::$singular_name ),
      'view_item' => sprintf( 'View %s', self::$singular_name ),
      'view_items' => sprintf( 'View %s', self::$plural_name ),
      'search_items' => sprintf( 'Search %s', self::$plural_name ),
      'not_found' => sprintf( 'No %s found', self::$plural_name ),
      'not_found_in_trash' => sprintf( 'No %s found in Trash', self::$plural_name ),
      'all_items' => sprintf( 'All %s', self::$plural_name ),
      // menu_name – Label for the menu name. Default is the same as name.
      // filter_items_list – Label for the table views hidden heading. Default is ‘Filter posts list’ / ‘Filter pages list’.
      // filter_by_date – Label for the date filter in list tables. Default is ‘Filter by date’.
      // items_list_navigation – Label for the table pagination hidden heading. Default is ‘Posts list navigation’ / ‘Pages list navigation’.
      // items_list – Label for the table hidden heading. Default is ‘Posts list’ / ‘Pages list’.
      // item_published – Label used when an item is published. Default is ‘Post published.’ / ‘Page published.’
      // item_published_privately – Label used when an item is published with private visibility. Default is ‘Post published privately.’ / ‘Page published privately.’
      // item_reverted_to_draft – Label used when an item is switched to a draft. Default is ‘Post reverted to draft.’ / ‘Page reverted to draft.’
      // item_scheduled – Label used when an item is scheduled for publishing. Default is ‘Post scheduled.’ / ‘Page scheduled.’
      // item_updated – Label used when an item is updated. Default is ‘Post updated.’ / ‘Page updated.’
      // item_link – Title for a navigation link block variation. Default is ‘Post Link’ / ‘Page Link’.
      // item_link_description – Description for a navigation link block variation. Default is ‘A link to a post.’ / ‘A link to a page.’
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
      // 'capability_type' - The string to use to build the read, edit, and delete capabilities. May be passed as an array to allow for alternative plurals when using this argument as a base to construct the capabilities, e.g. array('story', 'stories'). Default 'post'.
      // 'capabilities' - Array of capabilities for this post type. $capability_type is used as a base to construct capabilities by default. See get_post_type_capabilities().
      'supports' => array( 'title' ),
      'register_meta_box_cb' => 'MemGame\MemCpt::display_mem_cpt_meta_box',
      // 'has_archive' - Whether there should be post type archives, or if a string, the archive slug to use. Will generate the proper rewrite rules if $rewrite is enabled. Default false.
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
    $shortcode = '<code>[memgame id=' . $memgame_id . ']</code>';
    if ( get_post_meta( $memgame_id, 'mem_game_legacy', true ) ) {
      // Add the legacy shortcode
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
              <?php
              $current_layout = self::get_board_layout();
              foreach( self::$board_layout[ 'options' ] as $layout ) {
                ?>
                <option value="<?php echo $layout; ?>" <?php echo $layout === $current_layout ? 'selected' : ''; ?>><?php echo $layout; ?></option>
                <?php
              }
              ?>
            </select>
          </td>
          <td>
            <div class="mg-wrap">
              <div class="mg-game mg-layout-<?php echo self::get_board_layout(); ?> mg-board-layout">
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
        <?php foreach( self::$images as $image_type => $info ) { ?>
        <tr>
          <th scope="row"><?php echo $info[ 'title' ]; ?></th>
          <td>
            <?php
            self::display_image_input(
              array(
                'label_for' => $image_type,
              )
            );
            ?>
          </td>
        </tr>
        <?php } ?>
      </tbody>
    </table>
    <h3>Winner Screen</h3>
    <p id="mem_game_winner_screen">Text and buttons displayed on the "winner screen" of the memory game</p>
    <table class="form-table">
      <tbody>
        <tr>
          <th scope="row">Winner Message</th>
          <td>
            <?php
            self::display_winner_screen_input(
              array(
                'label_for' => 'winner_msg',
                'parent_option' => 'mem_game_winner_screen',
                'input_type' => 'text',
              )
            );
            ?>
          </td>
        </tr>
        <tr>
          <th scope="row">Play Again Button Text</th>
          <td>
            <?php
            self::display_winner_screen_input(
              array(
                'label_for' => 'play_again_txt',
                'parent_option' => 'mem_game_winner_screen',
                'input_type' => 'text',
              )
            );
            ?>
          </td>
        </tr>
        <tr>
          <th scope="row">Quit Button Text</th>
          <td>
            <?php
            self::display_winner_screen_input(
              array(
                'label_for' => 'quit_txt',
                'parent_option' => 'mem_game_winner_screen',
                'input_type' => 'text',
              )
            );
            ?>
          </td>
        </tr>
        <tr>
          <th scope="row">Quit Button Link URL</th>
          <td>
            <?php
            self::display_winner_screen_input(
              array(
                'label_for' => 'quit_url',
                'parent_option' => 'mem_game_winner_screen',
                'input_type' => 'url',
              )
            );
            ?>
          </td>
        </tr>
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
  private static $images = array(
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
  private static $default_winner_screen_options = array(
    'winner_msg' => 'You Rock!',
    'play_again_txt' => 'Play Again',
    'quit_txt' => 'Quit',
    'quit_url' => ''
  );

  // Board layout options
  // TODO: Eventually enable layouts with different numbers of images (4x4, 6x6, 3x4, 4x3, etc)
  private static $board_layout = array(
    'options' => array( '6x4', '4x6' ),
    'default' => '6x4'
  );

    // Display the image picker input
  // I'm looking at a couple of tutorials to figure out how to do this:
  //   Codex page (this is probably the best resource)
  //     https://codex.wordpress.org/Javascript_Reference/wp.media
  //   Random tutorial (looks like it may be a little old)
  //     https://jeroensormani.com/how-to-include-the-wordpress-media-selector-in-your-plugin/
  public static function display_image_input( $args ) {
    global $post;
    // Get the info for the image type to be displayed
    $image_type = $args[ 'label_for' ];
    $post_id = $post->ID;
    $image_info = self::get_image_type_info( $image_type );

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

  // Retrieve all image options
  // Deviating from class-mem-settings.php here.
  // Pass an optional memgame_id to the function. If memgame_id is null, use the current post ID
  private static function get_image_option( $memgame_id = null ) {
    global $post;
    if ( null === $memgame_id ) {
      $memgame_id = $post->ID;
    }
    $image_data = unserialize( get_post_meta( $memgame_id, 'mem_game_images', true ) );
    return is_array( $image_data ) ? $image_data : array();
  }

  // Retrieve options for a particular image type
  // Deviating from class-mem-settings.php here.
  // Pass an optional memgame_id to the function that gets passed to the next level.
  private static function get_image_type_option( $image_type, $memgame_id = null ) {
    $all_options = self::get_image_option( $memgame_id );
    return array_key_exists( $image_type, $all_options ) ? $all_options[ $image_type ] : array();
  }

  // Get the info for a given image type
  // Deviating from class-mem-settings.php here.
  // Pass an optional memgame_id to the function that gets passed to the next level.
  private static function get_image_type_info( $image_type, $memgame_id = null ) {
    $image_type_option = self::get_image_type_option( $image_type, $memgame_id );
    
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
  public static function display_winner_screen_input( $args ) {
    // Get the current winner screen values
    $all_options = self::get_winner_screen_options();

    // Get the specific winner screen value needed
    $sub_option = $args[ 'label_for' ] ;
    $current_value =
      array_key_exists( $sub_option, $all_options )
      ? $all_options[ $sub_option ]
      : self::$default_winner_screen_options[ $sub_option ];

    ?>
    <input
      type="<?php echo $args[ 'input_type' ]; ?>"
      name="<?php echo sprintf( '%s[%s]', $args[ 'parent_option' ], $sub_option ); ?>"
      id="<?php echo $sub_option; ?>"
      value="<?php echo $current_value; ?>"
      <?php if ( "url" === $args[ 'input_type' ] ) { echo 'placeholder="https://"'; } ?>
      size="40"
    />
    <?php
  }

  // Retrieve winner screen options
  // Deviating from class-mem-settings.php here.
  // Pass an optional memgame_id to the function instead of optional sub_option.
  // This means that the caller has to pull out the sub_option data and/or find the default data
  public static function get_winner_screen_options( $memgame_id = null ) {
    // Get the current data out of postmeta
    // If no memgame_id was provided, use the current post ID
    global $post;
    if ( null === $memgame_id ) {
      $memgame_id = $post->ID;
    }
    $current_options = unserialize( get_post_meta( $memgame_id, 'mem_game_winner_screen', true ) );

    // Return the entire option array if it exists, an empty array if it doesn't
    return is_array( $current_options ) ? $current_options : array();
  }

  // Retrieve the game board layout
  public static function get_board_layout( $memgame_id = null ) {
    // Get the current layout out of postmeta
    // If no memgame_id was provided, use the current post ID
    global $post;
    if ( null === $memgame_id ) {
      $memgame_id = $post->ID;
    }
    $current_layout = get_post_meta( $memgame_id, 'mem_game_board_layout', true );

    // Return the current layout if it exists, the default if it doesn't
    return empty( $current_layout ) ? self::$board_layout[ 'default' ] : $current_layout;
  }

  // Get the localized data to be sent to the front end JS
  // FIXME: There has to be a better way of doing the image id to url conversion
  //        and default images. This isn't as reuse friendly as I'd hoped.
  public static function get_localized_image_data( $memgame_id = null ) {
    // If no memgame_id was provided, return an empty array
    if ( null == $memgame_id ) {
      return array();
    }

    // Get the mem_game_images postmeta for this memgame_id
    // FIXME: I'm not using this data for anything other than checking if the memgame_id is valid
    //        This is bad
    $image_data = unserialize( get_post_meta( $memgame_id, 'mem_game_images', true ) );

    // If no image data found, return an empty array
    if ( ! is_array( $image_data ) ) {
      return array();
    }

    // Convert the postmeta data into something usable on the front end
    $return_data = array();

    // Iterate across the image types
    foreach ( array_keys( $image_data ) as $image_type ) {
      $return_data[ $image_type ] = array_map(
        function ( $info ) {
          // Return the fit and url from the image type info
          return array(
            'fit' => $info[ 'fit' ],
            'url' => $info[ 'url' ]
          );
        },
        // Reusing get_image_type_info here is causing some reuse problems
        self::get_image_type_info( $image_type, $memgame_id )
      );
    }

    // Return the data
    return $return_data;
  }

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

  // Remove 'Quick Edit' from the post row actions
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

  // Filter out the 'Edit' bulk action
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
}
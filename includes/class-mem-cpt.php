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

  public static function  render_mem_cpt_meta_box() {
    ?>
    <p>To add the memory game to your post or page, use the shortcode <code>[memgame]</code></p>
    <h3>Card Images</h3>
    <p id="mem_game_card_images">Images for the front and back of the cards used in the memory game</p>
    <table class="form-table">
      <tbody>
        <tr>
          <th scope="row">Field 1 Label</th>
          <td>Field 1 Data</td>
        </tr>
      </tbody>
    </table>
    <h3>Winner Screen</h3>
    <p id="mem_game_winner_screen">Text and buttons displayed on the "winner screen" of the memory game</p>
    <table class="form-table">
      <tbody>
        <tr>
          <th scope="row">Field 1 Label</th>
          <td>Field 1 Data</td>
        </tr>
      </tbody>
    </table>
    <?php
  }

}
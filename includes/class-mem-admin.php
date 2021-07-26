<?php

namespace MemGame;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class MemAdmin {

  /* **********
   * Properties
   * **********/

  // This contains the defaults for a new Memory Game as well as
  // the sections/fields/text displayed on the settings or new/edit post pages

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

  public static $default_winner_screen_options = array(
    'winner_msg' => 'You Rock!',
    'play_again_txt' => 'Play Again',
    'quit_txt' => 'Quit',
    'quit_url' => ''
  );

  /* *******
   * Methods
   * *******/

}
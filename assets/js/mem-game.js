// Memory Game
// © 2014 Nate Wiley
// License -- MIT
// best in full screen, works on phones/tablets (min height for game is 500px..) enjoy ;)
// Follow me on Codepen

// Borrowed from https://codepen.io/natewiley/pen/HBrbL
// Uses: https://cdnjs.cloudflare.com/ajax/libs/jquery/2.1.3/jquery.min.js

// NOTE: Using jQuery inside WordPress requires the surrounding function to be:
//          (function ($) {
//            ...
//          })(jQuery);
//       Instead of the original:
//          (function () {
//            ...
//          })();

(function ($) {
  // Stats related stuff
  let collectingStats = false;
  let collectedClicks = 0;
  let currentSessionID = -1;

  // Called on every card click
  const handleCardClick = () => {
    // If this is the first click of a new game, send the game start AJAX message
    if (!collectingStats) {
      collectingStats = true;
      collectedClicks = 0;
      jQuery.ajax({
        type: "post",
        dataType: "json",
        url: mem_game_img_obj.ajax_url,
        data: {
          action: "mem_game_start",
          _ajax_nonce: mem_game_img_obj.nonce,
          data: { session_id: currentSessionID },
        },
        success: function (response) {
          console.log("The response:");
          console.log(response);
          if (response.success) {
            console.log(
              `Action mem_game_start was successful! Session Id ${response.data.session_id}`
            );
            currentSessionID = response.data.session_id;
          } else {
            console.log("Action mem_game_start had some problems");
          }
        },
      });
    }
    // Update the click counter
    collectedClicks += 1;
    // console.log(`Saw ${collectedClicks} click(s)`);
  }; // handleCardClick

  // Called on win
  const handleWin = () => {
    // Stop collecting stats and send game completion AJAX message
    collectingStats = false;
    // console.log(
    //   `Winner, winner, chicken dinner! Only took ${collectedClicks} clicks!`
    // );
    jQuery.ajax({
      type: "post",
      dataType: "json",
      url: mem_game_img_obj.ajax_url,
      data: {
        action: "mem_game_complete",
        _ajax_nonce: mem_game_img_obj.nonce,
        data: {
          session_id: currentSessionID,
          num_clicks: collectedClicks,
        },
      },
      success: function (response) {
        console.log("The response:");
        console.log(response);
        if (response.success) {
          console.log(
            `Action mem_game_complete was successful! Session Id ${currentSessionID}`
          );
        } else {
          console.log("Action mem_game_complete had some problems");
        }
      },
    });
  }; // handleWin

  // Called on leaving or closing page
  const handleAbandonGame = (e) => {
    // If we're not collecting stats, do nothing
    if (!collectingStats) {
      console.log("You never clicked once!!!");
      return;
    }

    // Send abandon game AJAX message
    e.preventDefault();
    console.log(
      `Sorry to see you go! You only tried ${collectedClicks} clicks!`
    );
    jQuery.ajax({
      type: "post",
      dataType: "json",
      url: mem_game_img_obj.ajax_url,
      data: {
        action: "mem_game_abandon",
        _ajax_nonce: mem_game_img_obj.nonce,
        data: {
          session_id: currentSessionID,
          num_clicks: collectedClicks,
        },
      },
      success: function (response) {
        console.log("The response:");
        console.log(response);
        if (response.success) {
          console.log(
            `Action mem_game_complete was successful! Session Id ${currentSessionID}`
          );
        } else {
          console.log("Action mem_game_complete had some problems");
        }
      },
    });
  };
  window.addEventListener("beforeunload", handleAbandonGame);

  // End stats related stuff

  // Original (for the most part) code from CodePen
  var Memory = {
    init: function (cards) {
      this.$game = $(".game");
      this.$modal = $(".modal");
      // this.$overlay = $(".modal-overlay");
      this.$overlay = $(".modal-wrap");
      this.$restartButton = $("button.restart");
      this.cardsArray = $.merge(cards, cards);
      this.shuffleCards(this.cardsArray);
      this.setup();
    },

    shuffleCards: function (cardsArray) {
      this.$cards = $(this.shuffle(this.cardsArray));
    },

    setup: function () {
      this.html = this.buildHTML();
      this.$game.html(this.html);
      this.$memoryCards = $(".card");
      this.paused = false;
      this.guess = null;
      this.binding();
    },

    binding: function () {
      this.$memoryCards.on("click", this.cardClicked);
      this.$restartButton.on("click", $.proxy(this.reset, this));
    },
    // kinda messy but hey
    cardClicked: function () {
      // Stats related stuff
      handleCardClick();
      var _ = Memory;
      var $card = $(this);
      if (
        !_.paused &&
        !$card.find(".inside").hasClass("matched") &&
        !$card.find(".inside").hasClass("picked")
      ) {
        $card.find(".inside").addClass("picked");
        if (!_.guess) {
          _.guess = $(this).attr("data-id");
        } else if (
          _.guess == $(this).attr("data-id") &&
          !$(this).hasClass("picked")
        ) {
          $(".picked").addClass("matched");
          _.guess = null;
        } else {
          _.guess = null;
          _.paused = true;
          setTimeout(function () {
            $(".picked").removeClass("picked");
            Memory.paused = false;
          }, 600);
        }
        if ($(".matched").length == $(".card").length) {
          _.win();
        }
      }
    },

    win: function () {
      // Stats related stuff
      handleWin();
      this.paused = true;
      setTimeout(function () {
        Memory.showModal();
        // Memory.$game.fadeOut();
      }, 1000);
    },

    showModal: function () {
      this.$overlay.show();
      this.$modal.fadeIn("slow");
    },

    hideModal: function () {
      this.$overlay.hide();
      this.$modal.hide();
    },

    reset: function () {
      this.hideModal();
      this.shuffleCards(this.cardsArray);
      this.setup();
      // this.$game.show("slow");
    },

    // Fisher--Yates Algorithm -- https://bost.ocks.org/mike/shuffle/
    shuffle: function (array) {
      var counter = array.length,
        temp,
        index;
      // While there are elements in the array
      while (counter > 0) {
        // Pick a random index
        index = Math.floor(Math.random() * counter);
        // Decrease counter by 1
        counter--;
        // And swap the last element with it
        temp = array[counter];
        array[counter] = array[index];
        array[index] = temp;
      }
      return array;
    },

    buildHTML: function () {
      var frag = "";
      const card_back_url = mem_game_img_obj["images"]["card_back"];
      this.$cards.each(function (k, v) {
        // frag +=
        //   '<div class="card" data-id="' +
        //   v.id +
        //   '"><div class="inside">\
        // <div class="front"><img src="' +
        //   v.img +
        //   '"\
        // alt="' +
        //   v.name +
        //   '" /></div>\
        // <div class="back"><img src="https://s3-us-west-2.amazonaws.com/s.cdpn.io/74196/codepen-logo.png"\
        // alt="Codepen" /></div></div>\
        // </div>';
        frag +=
          '<div class="card" data-id="' +
          v.id +
          '"><div class="inside">\
				<div class="front"><img src="' +
          v.img +
          '"\
				alt="' +
          v.name +
          '" /></div>\
				<div class="back"><img src="' +
          card_back_url +
          '"\
				alt="Codepen" /></div></div>\
				</div>';
      });
      return frag;
    },
  };

  var cards = [
    {
      // name: "php",
      name: "card_front_0",
      // img: "https://s3-us-west-2.amazonaws.com/s.cdpn.io/74196/php-logo_1.png",
      img: mem_game_img_obj["images"]["card_front_0"],
      id: 1,
    },
    {
      // name: "css3",
      name: "card_front_1",
      // img: "https://s3-us-west-2.amazonaws.com/s.cdpn.io/74196/css3-logo.png",
      img: mem_game_img_obj["images"]["card_front_1"],
      id: 2,
    },
    {
      // name: "html5",
      name: "card_front_2",
      // img: "https://s3-us-west-2.amazonaws.com/s.cdpn.io/74196/html5-logo.png",
      img: mem_game_img_obj["images"]["card_front_2"],
      id: 3,
    },
    {
      // name: "jquery",
      name: "card_front_3",
      // img: "https://s3-us-west-2.amazonaws.com/s.cdpn.io/74196/jquery-logo.png",
      img: mem_game_img_obj["images"]["card_front_3"],
      id: 4,
    },
    {
      // name: "javascript",
      name: "card_front_4",
      // img: "https://s3-us-west-2.amazonaws.com/s.cdpn.io/74196/js-logo.png",
      img: mem_game_img_obj["images"]["card_front_4"],
      id: 5,
    },
    {
      // name: "node",
      name: "card_front_5",
      // img: "https://s3-us-west-2.amazonaws.com/s.cdpn.io/74196/nodejs-logo.png",
      img: mem_game_img_obj["images"]["card_front_5"],
      id: 6,
    },
    {
      // name: "photoshop",
      name: "card_front_6",
      // img: "https://s3-us-west-2.amazonaws.com/s.cdpn.io/74196/photoshop-logo.png",
      img: mem_game_img_obj["images"]["card_front_6"],
      id: 7,
    },
    {
      // name: "python",
      name: "card_front_7",
      // img: "https://s3-us-west-2.amazonaws.com/s.cdpn.io/74196/python-logo.png",
      img: mem_game_img_obj["images"]["card_front_7"],
      id: 8,
    },
    {
      // name: "rails",
      name: "card_front_8",
      // img: "https://s3-us-west-2.amazonaws.com/s.cdpn.io/74196/rails-logo.png",
      img: mem_game_img_obj["images"]["card_front_8"],
      id: 9,
    },
    {
      // name: "sass",
      name: "card_front_9",
      // img: "https://s3-us-west-2.amazonaws.com/s.cdpn.io/74196/sass-logo.png",
      img: mem_game_img_obj["images"]["card_front_9"],
      id: 10,
    },
    {
      // name: "sublime",
      name: "card_front_10",
      // img: "https://s3-us-west-2.amazonaws.com/s.cdpn.io/74196/sublime-logo.png",
      img: mem_game_img_obj["images"]["card_front_10"],
      id: 11,
    },
    {
      // name: "wordpress",
      name: "card_front_11",
      // img: "https://s3-us-west-2.amazonaws.com/s.cdpn.io/74196/wordpress-logo.png",
      img: mem_game_img_obj["images"]["card_front_11"],
      id: 12,
    },
  ];

  Memory.init(cards);
})(jQuery);

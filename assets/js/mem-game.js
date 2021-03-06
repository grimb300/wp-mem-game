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
          data: {
            session_id: currentSessionID,
            memgame_id: mem_game_img_obj.memgame_id,
            post_id: mem_game_img_obj.post_id,
          },
        },
        success: function (response) {
          // console.log("The response:");
          // console.log(response);
          if (response.success) {
            // console.log(
            //   `Action mem_game_start was successful! Session Id ${response.data.session_id}`
            // );
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
          memgame_id: mem_game_img_obj.memgame_id,
          post_id: mem_game_img_obj.post_id,
          num_clicks: collectedClicks,
        },
      },
      success: function (response) {
        // console.log("The response:");
        // console.log(response);
        if (response.success) {
          // console.log(
          //   `Action mem_game_complete was successful! Session Id ${currentSessionID}`
          // );
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
      // console.log("You never clicked once!!!");
      return;
    }

    // Send abandon game AJAX message
    e.preventDefault();
    // console.log(
    //   `Sorry to see you go! You only tried ${collectedClicks} clicks!`
    // );
    jQuery.ajax({
      type: "post",
      dataType: "json",
      url: mem_game_img_obj.ajax_url,
      data: {
        action: "mem_game_abandon",
        _ajax_nonce: mem_game_img_obj.nonce,
        data: {
          session_id: currentSessionID,
          memgame_id: mem_game_img_obj.memgame_id,
          post_id: mem_game_img_obj.post_id,
          num_clicks: collectedClicks,
        },
      },
      success: function (response) {
        // console.log("The response:");
        // console.log(response);
        if (response.success) {
          // console.log(
          //   `Action mem_game_complete was successful! Session Id ${currentSessionID}`
          // );
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
      this.$game = $(".mg-game");
      this.$modal = $(".mg-modal");
      // this.$overlay = $(".mg-modal-overlay");
      this.$overlay = $(".mg-modal-wrap");
      this.$restartButton = $("button.mg-restart");
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
      this.$memoryCards = $(".mg-card");
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
        !$card.find(".mg-inside").hasClass("mg-matched") &&
        !$card.find(".mg-inside").hasClass("mg-picked")
      ) {
        $card.find(".mg-inside").addClass("mg-picked");
        if (!_.guess) {
          _.guess = $(this).attr("data-id");
        } else if (
          _.guess == $(this).attr("data-id") &&
          !$(this).hasClass("mg-picked")
        ) {
          $(".mg-picked").addClass("mg-matched");
          _.guess = null;
        } else {
          _.guess = null;
          _.paused = true;
          setTimeout(function () {
            $(".mg-picked").removeClass("mg-picked");
            Memory.paused = false;
          }, 600);
        }
        if ($(".mg-matched").length == $(".mg-card").length) {
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
      // this.$overlay.show();
      // this.$modal.fadeIn("slow");
      this.$overlay.fadeIn("slow");
    },

    hideModal: function () {
      this.$overlay.hide();
      // this.$modal.hide();
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
      const card_back_url = mem_game_img_obj["images"]["card_back"][0]["url"];
      const card_back_fit = mem_game_img_obj["images"]["card_back"][0]["fit"];
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
          '<div class="mg-card" data-id="' +
          v.id +
          '"><div class="mg-inside">\
				<div class="mg-front"><img src="' +
          v.img +
          '"\
				alt="' +
          v.name +
          '" class="mg-fit-' +
          v.fit +
          '" /></div>\
				<div class="mg-back"><img src="' +
          card_back_url +
          '"\
				alt="Card Back" class="mg-fit-' +
          card_back_fit +
          '" /></div></div>\
				</div>';
      });
      return frag;
    },
  };

  // FIXME: This is cumbersome, need to use something like array map to make future proof
  var cards = [
    {
      // name: "php",
      name: "card_front_0",
      // img: "https://s3-us-west-2.amazonaws.com/s.cdpn.io/74196/php-logo_1.png",
      img: mem_game_img_obj["images"]["card_front"][0]["url"],
      fit: mem_game_img_obj["images"]["card_front"][0]["fit"],
      id: 1,
    },
    {
      // name: "css3",
      name: "card_front_1",
      // img: "https://s3-us-west-2.amazonaws.com/s.cdpn.io/74196/css3-logo.png",
      img: mem_game_img_obj["images"]["card_front"][1]["url"],
      fit: mem_game_img_obj["images"]["card_front"][1]["fit"],
      id: 2,
    },
    {
      // name: "html5",
      name: "card_front_2",
      // img: "https://s3-us-west-2.amazonaws.com/s.cdpn.io/74196/html5-logo.png",
      img: mem_game_img_obj["images"]["card_front"][2]["url"],
      fit: mem_game_img_obj["images"]["card_front"][2]["fit"],
      id: 3,
    },
    {
      // name: "jquery",
      name: "card_front_3",
      // img: "https://s3-us-west-2.amazonaws.com/s.cdpn.io/74196/jquery-logo.png",
      img: mem_game_img_obj["images"]["card_front"][3]["url"],
      fit: mem_game_img_obj["images"]["card_front"][3]["fit"],
      id: 4,
    },
    {
      // name: "javascript",
      name: "card_front_4",
      // img: "https://s3-us-west-2.amazonaws.com/s.cdpn.io/74196/js-logo.png",
      img: mem_game_img_obj["images"]["card_front"][4]["url"],
      fit: mem_game_img_obj["images"]["card_front"][4]["fit"],
      id: 5,
    },
    {
      // name: "node",
      name: "card_front_5",
      // img: "https://s3-us-west-2.amazonaws.com/s.cdpn.io/74196/nodejs-logo.png",
      img: mem_game_img_obj["images"]["card_front"][5]["url"],
      fit: mem_game_img_obj["images"]["card_front"][5]["fit"],
      id: 6,
    },
    {
      // name: "photoshop",
      name: "card_front_6",
      // img: "https://s3-us-west-2.amazonaws.com/s.cdpn.io/74196/photoshop-logo.png",
      img: mem_game_img_obj["images"]["card_front"][6]["url"],
      fit: mem_game_img_obj["images"]["card_front"][6]["fit"],
      id: 7,
    },
    {
      // name: "python",
      name: "card_front_7",
      // img: "https://s3-us-west-2.amazonaws.com/s.cdpn.io/74196/python-logo.png",
      img: mem_game_img_obj["images"]["card_front"][7]["url"],
      fit: mem_game_img_obj["images"]["card_front"][7]["fit"],
      id: 8,
    },
    {
      // name: "rails",
      name: "card_front_8",
      // img: "https://s3-us-west-2.amazonaws.com/s.cdpn.io/74196/rails-logo.png",
      img: mem_game_img_obj["images"]["card_front"][8]["url"],
      fit: mem_game_img_obj["images"]["card_front"][8]["fit"],
      id: 9,
    },
    {
      // name: "sass",
      name: "card_front_9",
      // img: "https://s3-us-west-2.amazonaws.com/s.cdpn.io/74196/sass-logo.png",
      img: mem_game_img_obj["images"]["card_front"][9]["url"],
      fit: mem_game_img_obj["images"]["card_front"][9]["fit"],
      id: 10,
    },
    {
      // name: "sublime",
      name: "card_front_10",
      // img: "https://s3-us-west-2.amazonaws.com/s.cdpn.io/74196/sublime-logo.png",
      img: mem_game_img_obj["images"]["card_front"][10]["url"],
      fit: mem_game_img_obj["images"]["card_front"][10]["fit"],
      id: 11,
    },
    {
      // name: "wordpress",
      name: "card_front_11",
      // img: "https://s3-us-west-2.amazonaws.com/s.cdpn.io/74196/wordpress-logo.png",
      img: mem_game_img_obj["images"]["card_front"][11]["url"],
      fit: mem_game_img_obj["images"]["card_front"][11]["fit"],
      id: 12,
    },
  ];

  Memory.init(cards);
})(jQuery);

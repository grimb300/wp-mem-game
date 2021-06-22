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
  var Memory = {
    init: function (cards) {
      this.$game = $(".game");
      this.$modal = $(".modal");
      this.$overlay = $(".modal-overlay");
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
      this.paused = true;
      setTimeout(function () {
        Memory.showModal();
        Memory.$game.fadeOut();
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
      this.$game.show("slow");
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
      const card_back_url = mem_game_img_obj["card_back"];
      console.log(`The card back image url is ${card_back_url}`);
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
      img: mem_game_img_obj["card_front_0"],
      id: 1,
    },
    {
      // name: "css3",
      name: "card_front_1",
      // img: "https://s3-us-west-2.amazonaws.com/s.cdpn.io/74196/css3-logo.png",
      img: mem_game_img_obj["card_front_1"],
      id: 2,
    },
    {
      // name: "html5",
      name: "card_front_2",
      // img: "https://s3-us-west-2.amazonaws.com/s.cdpn.io/74196/html5-logo.png",
      img: mem_game_img_obj["card_front_2"],
      id: 3,
    },
    {
      // name: "jquery",
      name: "card_front_3",
      // img: "https://s3-us-west-2.amazonaws.com/s.cdpn.io/74196/jquery-logo.png",
      img: mem_game_img_obj["card_front_3"],
      id: 4,
    },
    {
      // name: "javascript",
      name: "card_front_4",
      // img: "https://s3-us-west-2.amazonaws.com/s.cdpn.io/74196/js-logo.png",
      img: mem_game_img_obj["card_front_4"],
      id: 5,
    },
    {
      // name: "node",
      name: "card_front_5",
      // img: "https://s3-us-west-2.amazonaws.com/s.cdpn.io/74196/nodejs-logo.png",
      img: mem_game_img_obj["card_front_5"],
      id: 6,
    },
    {
      // name: "photoshop",
      name: "card_front_6",
      // img: "https://s3-us-west-2.amazonaws.com/s.cdpn.io/74196/photoshop-logo.png",
      img: mem_game_img_obj["card_front_6"],
      id: 7,
    },
    {
      // name: "python",
      name: "card_front_7",
      // img: "https://s3-us-west-2.amazonaws.com/s.cdpn.io/74196/python-logo.png",
      img: mem_game_img_obj["card_front_7"],
      id: 8,
    },
    {
      // name: "rails",
      name: "card_front_8",
      // img: "https://s3-us-west-2.amazonaws.com/s.cdpn.io/74196/rails-logo.png",
      img: mem_game_img_obj["card_front_8"],
      id: 9,
    },
    {
      // name: "sass",
      name: "card_front_9",
      // img: "https://s3-us-west-2.amazonaws.com/s.cdpn.io/74196/sass-logo.png",
      img: mem_game_img_obj["card_front_9"],
      id: 10,
    },
    {
      // name: "sublime",
      name: "card_front_10",
      // img: "https://s3-us-west-2.amazonaws.com/s.cdpn.io/74196/sublime-logo.png",
      img: mem_game_img_obj["card_front_10"],
      id: 11,
    },
    {
      // name: "wordpress",
      name: "card_front_11",
      // img: "https://s3-us-west-2.amazonaws.com/s.cdpn.io/74196/wordpress-logo.png",
      img: mem_game_img_obj["card_front_11"],
      id: 12,
    },
  ];

  Memory.init(cards);
})(jQuery);

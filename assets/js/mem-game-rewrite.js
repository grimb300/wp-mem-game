// This is a drastically simplified handler for the memory game plugin.
// Changes include:
//   -- No jQuery!!!!
//   -- Does not build the game board, relies on all cards to be already in the DOM (populated by php)
//   -- Handles card clicks and subsequent flipping over of the selected cards if there isn't a match

// console.log("Loaded the new JS file!!!");

// Card click handler
const cardClickHandler = (event) => {
  // console.log("Card clicked!");
  // console.log(event);
  // console.log(event.currentTarget.classList);
  event.currentTarget.classList.toggle("mg-picked");
};

// Get handles to all the cards
const cards = document.querySelectorAll(".mg-new-card");
// Attaching the event handler to the 'mg-new-inside' element since it is the one we will be adding/removing classes to
// const cards = document.querySelectorAll(".mg-new-inside");
// console.log(`Found ${cards.length} cards`);

cards.forEach((card) => {
  // console.log(card);
  card.addEventListener("click", cardClickHandler);
});

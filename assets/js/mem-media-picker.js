jQuery(function ($) {
  console.log("Running my code!");
  // Set all variables to be used in scope
  var frame,
    metaBox = $("#mem_game_card_image"),
    addImgLink = metaBox.find(".upload-custom-img"),
    imgContainer = metaBox.find(".custom-img-container"),
    imgElement = imgContainer.find("img"),
    imgIdInput = metaBox.find(".custom-img-id");

  // ADD IMAGE LINK
  addImgLink.on("click", function (event) {
    console.log("Saw click event");
    event.preventDefault();

    // If the media frame already exists, reopen it.
    if (frame) {
      frame.open();
      return;
    }

    // Create a new media frame
    frame = wp.media({
      title: "Select or Upload Media Of Your Chosen Persuasion",
      button: {
        text: "Use this media",
      },
      multiple: false, // Set to true to allow multiple files to be selected
    });

    // When an image is selected in the media frame...
    frame.on("select", function () {
      // Get media attachment details from the frame state
      var attachment = frame.state().get("selection").first().toJSON();

      // Send the attachment URL to our image src attribute and unhide the image container.
      imgElement.attr("src", attachment.url);
      imgContainer.removeClass("hidden");

      // Send the attachment id to our hidden input
      imgIdInput.val(attachment.id);

      // Hide the add image link
      addImgLink.text("Update");
    });

    // Finally, open the modal on click
    frame.open();
  });
});

jQuery(function ($) {
  // Get a handle for each media picker
  // var frame,
  const mediaPickers = $(".mem_game_card_image");

  // Iterate across all media pickers
  mediaPickers.each(function (index) {
    // Get a handle for the important elements inside each picker
    const addImgLink = $(this).find(".upload-custom-img");
    const imgContainer = $(this).find(".custom-img-container");
    const imgElement = imgContainer.find("img");
    const imgIdInput = $(this).find(".custom-img-id");

    // The media frame is scoped to this picker, don't create it yet
    let frame;

    // Handle add/update image clicks
    addImgLink.on("click", function (event) {
      // Prevent the default behavior
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
});

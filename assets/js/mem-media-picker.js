jQuery(function ($) {
  // Get a handle for each media picker
  // var frame,
  const mediaPickers = $(".mg-card");

  // Iterate across all media pickers
  mediaPickers.each(function (index) {
    // Get a handle for the important elements inside each picker
    const addImgLink = $(this).find(".upload-custom-img");
    const imgElement = $(this).find("img");
    const imgIdInput = $(this).find(".custom-img-id");
    const imgFitSelect = $(this).find(".select-img-fit");

    // The media frame is scoped to this picker, don't create it yet
    let frame;

    // Handle changes to the image fit select
    imgFitSelect.on("change", function (event) {
      // console.log(`Saw fit selector change to ${$(this).val()}`);
      if ($(this).val() === "cover") {
        imgElement.addClass("mg-fit-cover");
      } else {
        imgElement.removeClass("mg-fit-cover");
      }
    });

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

        // Send the attachment id to our hidden input
        imgIdInput.val(attachment.id);

        // Hide the add image link
        addImgLink.text("Update");
      });

      // Finally, open the modal on click
      frame.open();
    });
  });

  // Not really the right place to do this for reusability, but it keeps me from adding another js file
  // Get a handle to the board layout selector
  console.log("Layout selector code");
  const layoutSelector = $("#mem_game_board_layout");
  console.log("Layout selector is");
  console.log(layoutSelector);
  layoutSelector.on("change", function (event) {
    // Get a handle to the example layout
    const boardLayoutElement = $(".mg-game.mg-board-layout");
    // Get the current layout class
    const currentLayoutClass = boardLayoutElement
      .attr("class")
      .split(" ")
      .find((element) => element.startsWith("mg-layout-"));
    // Construct the new layout class
    const newLayoutClass = `mg-layout-${$(this).val()}`;
    // Change the layout class on the mg-board-layout element
    boardLayoutElement.removeClass(currentLayoutClass).addClass(newLayoutClass);
  });

  // Handle shortcode click to copy
  // TODO: This will only work as written if the shortcode is in a text input, need to work on it.
  // console.log("Setting up shortcode click to copy");
  // const shortcodeWraps = $(".mg-shortcode-wrap");
  // shortcodeWraps.each(function (index) {
  //   const shortcodeText = $(this).find(".mg-shortcode");
  //   $(this).on("click", function (event) {
  //     shortcodeText.select();
  //     // shortcodeText.setSelectionRange(0, 99999);
  //     document.execCommand("copy");
  //     alert("Copied shortcode to clipboard");
  //   });
  // });
});

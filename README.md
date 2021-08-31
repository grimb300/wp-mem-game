# WordPress Memory Game

Plugin creates a shortcode to add a memory game to any post or page.

## Special Instructions

The original CodePen that this was based on uses Sass. First check if Sass is installed:

```
sass --version
```

If it needs to be installed (using npm, there are other ways to install it):

```
npm install -g sass
```

Then set up the compiler to watch the sass directory:

```
sass --watch assets/sass:assets/css
```

## Revision History

| Version | Description                                                                                                                                                                   |
| ------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| 0.1     | First beta.                                                                                                                                                                   |
| 0.2     | Fixes image picker bug. Adds image fit selection (cover or scale) and winner screen text/button options. Better styling to force cards into a fixed aspect ratio.             |
| 0.3     | Enable multiple game definitions. Add game board layout configuration (6x4, 4x6, etc.).                                                                                       |
| 0.3.1   | Fixes an animation display bug on iOS-Chrome and Safari.                                                                                                                      |
| 0.3.2   | Automate the 6x4 vs 4x6 layout based on screen orientation. Scale card width based on screen size to fit entire game board on one screen. Make winner screen more responsive. |

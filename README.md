# SocialMediaEnhancer

Fetches social counts from Google+, Facebook, Twitter, LinkedIn, Pinterst and XING and provide beautiful sharing buttons without the use of JavaScript

![SocialMediaEnhancer](SocialMediaEnhancer/assets/images/smeDefault.png "SocialMediaEnhancer")

## Description

The SocialMediaEnhancer (SME) provides beautiful styled and static social media sharing buttons for your WordPress blog. It fetches the social counts
(share activities) from Google+, Facebook, Twitter, LinkedIn and Pinterst in the background without the use of performance critital JavaScript
from the social networks.

To provide a optimal presentation of your post at the social networks after sharing, the SME is using the Open Graph Protocol (OGP) to include
well formatted meta elements on every post.

Choose a style for the buttons and decide which network do you want to use.

Donate-Link: <http://www.amazon.de/registry/wishlist/2NJSSK0DMFEQE>

### Feedback

Please send me feedback if you have questions or new ideas on [Google+](https://plus.google.com/102458928073783517690?rel=author) or [Twitter](https://twitter.com/macx).

Thanks to MrFloppy for the XING support

## Installation

For a manual installation using FTP:

1. Download the zip and extract it.
2. Upload the directory `SocialMediaEnhancer` to your WordPress Blog plugin folder at `/wp-content/plugins`.
3. Activate this Plugin in the WordPress-Backend through the 'Plugins' screen.
4. Visit the options page in WordPress to setup it up for your needs.

## Change Log

Version 2.0 in development!

### 1.8.6

- New: $post->socialInfo['shares'] shows the social shares without the comments on the blog post which is included in $post->socialInfo['shares'] (#30)
- Fixed: Prevent Warnings (#31)
- Fixed: Worked now properly with excerpt text (#24)

### 1.8.5

- Fixed: Social Counts
- Removed Photoshop files for faster updating

### 1.8.4

- New: Flat Design Buttons
- New: Default Image if a post has no thumbnail
- New: Optionen to display the Social Network name in a modern button
- New: Debug Mode. Activate it by adding this to your URL: ?smeDebug=true
- New: Using SASS/Compass to build the CSS
- New: Support for Pinterst Image Sharing (using the post thumbnail)
- New: Support for article:publisher (Facebook publisher relation)
- New: Support for link rel="publisher" (Google+ publisher relation)
- New: Support for meta twitter:site (Twitter publisher relation)
- Anhanced: Better Post Image Detection
- Fixed: Post Description is now used correctly

### 1.8.3

- added XING-Button in Style "button"
- external function to call sme staticly. Use <?php echo smeButtons(get_the_ID()); ?>
- function to disble open graph meta data

### 1.8.2

- added tracking of XING counts (by MrFloppy)
- added option to turn on XING support
- update icons for Google+, Facebook, Twitter

### 1.8.1

Initial Tracking of changes

## Planned updates

- full support of LinkedIn, Pinterest and XING with sharing buttons

## Licence

Copyright 2012-2013 David Maciejewski

GPLv2 or later <http://www.gnu.org/licenses/gpl-2.0.html>

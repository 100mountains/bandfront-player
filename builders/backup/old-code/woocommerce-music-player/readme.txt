=== Music Player for WooCommerce ===
Contributors: codepeople
Donate link: https://wcmp.dwbooster.com
Tags:WooCommerce,music player,audio,music,song,player,audio player,media player,mp3,m3u,m3u8,wav,oga,ogg,dokan,wcfm
Requires at least: 3.5.0
Tested up to: 6.8
Stable tag: trunk
License URI: https://wcmp.dwbooster.com/terms

Music Player for WooCommerce includes the MediaElement.js music player in the pages of the products with audio files associated.

== Description ==

Features of the Music Player for WooCommerce, Dokan, WCFM Marketplace, and MultivendorX:

♪ Integrate a music player into the WooCommerce products, Dokan, WCFM Marketplace, and MultivendorX
♪ Includes an audio player that supports formats: OGA, MP3, WAV, WMA
♪ Supports M3U, M3U8 playlists
♪ Includes multiple skins for the Music Player
♪ Supports all most popular web browsers and mobile devices
♪ Includes a widget to insert a playlist on sidebars
♪ Includes a block to insert the playlists on pages using Gutenberg
♪ Includes a widget to insert the playlists on pages using Elementor
♪ Includes a widget for inserting the playlists on pages with Page Builder by SiteOrigin
♪ Includes a control for inserting the playlists on pages with BeaverBuilder
♪ Includes an element for inserting the playlists on pages with Visual Composer
♪ Includes a module for inserting the playlists on pages with DIVI

Note: for the other editors, insert directly the playlists' shortcodes.

Music Player for WooCommerce includes the MediaElement.js music player in the pages of the products with audio files associated, and in the store's pages. It allows the integration with the multivendor stores generated with Dokan, WCFM Marketplace, and MultivendorX. Furthermore, the plugin allows selecting between multiple skins.

MediaElement.js is an music player compatible with all major browsers: Internet Explorer, Firefox, Opera, Safari, Chrome and mobile devices: iPhone, iPad, Android. The music player is developed following the html5 standard. The music player supports the following file formats: MP3, WAV, WMA and OGA.

The basic version of the plugin, available for free from the WordPress Directory, has the features needed to include a music player in the pages of the products and the store.

[youtube https://youtu.be/kWbvyWuzBtk]

**Premium Features**

*	Allows playing the audio files in secure mode to prevent unauthorized downloading of the audio files.
*	Allows to define the percent of the audio file's size to be played in secure mode.

[youtube https://youtu.be/e8LpVzhK_1s]

**Supports integration with plugins:**

* WooCommerce
* Dokan
* WCFM - Marketplace
* WC Vendors
* MultivendorX
* Advanced AJAX Product Filters by berocket
* Load More Products for WooCommerce by berocket
* Themify - WooCommerce Product Filter by Themify
* YITH WooCommerce Ajax Product Filter by YITH
* WOOF - Products Filter for WooCommerce by realmag777
* Product Filter by WooBeWoo

Support post_type like auctions, included by third-party plugins.

And third-party players like:

* Compact Audio Player
* CP Media Player
* HTML5 Audio Player
* MP3 jPlayer

== Installation ==

**To install Music Player for WooCommerce, follow these steps:**

1. Download and unzip the plugin
2. Upload the entire "woocommerce_music_player" directory to the "/wp-content/plugins/" directory
3. Activate the plugin through the "Plugins" menu in "WordPress"
4. Go to the products pages to configure the players.

== Interface ==

**Global Settings of Music Players**

The global settings are accessible through the menu option: "Settings/Music Player for WooCommerce".

*   Include music player in all all products: checkbox to include the music player in all products.
*   Include in: radio button to decide where to display the music player, in pages with a single entry, multiple entries, or both (both cases by default).
*   Include players in cart: checkbox to include the music players on the cart page or not.
*   Merge in grouped products: in grouped products, display the "Add to cart" buttons and quantity fields in the players rows.
*   Player layout: list of available skins for the music player.
*	Show a single player instead of one player per audio file.
*   Preload: to decide if preload the audio files, their metadata, or don't preload nothing at all.
*	Play all: play all players in the page (one after the other).
*	Loop: plays the audio player on the product page in a loop.
*   Player controls: determines the controls to include in the music player.
*   Display the player's title: show/hide the name associated to the downloadable file.
*   Protect the file: checkbox to playback the songs in secure mode (only available in the pro version of the plugin).
*   Percent of audio used for protected playbacks: integer number from 0 to 100, that represents the percent of the size of the original audio file that will be used in the audio file for demo (only available in the pro version of the plugin).
* 	Apply the previous settings to all products pages in the website: tick the checkbox to apply the previous settings to all products overwriting the products' settings.

**Google Analytics Integration**

*	Tracking id: Enter the tracking id in the property settings of Google Analytics account.

**Setting up the Music Players through the products' pages**

The Music Players are configured from the products pages, the Dokan interface, WCFM Marketplace, and MultivendorX.

**Settings Interface**

*   Include music player: checkbox to include the music player in the product's page, or not.
*   Include in: radio button to decide where to display the music player, in pages with a single entry, multiple entries, or both (both cases by default).
*   Merge in grouped products: in grouped products, display the "Add to cart" buttons and quantity fields in the players rows.
*   Player layout: list of available skins for the music player.
*	Show a single player instead of one player per audio file.
*   Preload: to decide if preload the audio files, their metadata, or don't preload nothing at all.
*	Play all: play all players in the page (one after the other).
*	Loop: plays the audio player on the product page in a loop.
*   Player controls: determines the controls to include in the music player.
*   Display the player's title: show/hide the name associated to the downloadable file.
*   Protect the file: checkbox to playback the songs in secure mode (only available in the pro version of the plugin).
*   Percent of audio used for protected playbacks: integer number from 0 to 100, that represents the percent of the size of the original audio file that will be used in the audio file for demo (only available in the pro version of the plugin).
*	Select my own demo files: checkbox to use different audio files for demo, than the audio files for selling (only available in the pro version of the plugin).
*	Demo files: section similar to the audio files for selling, but in this case it allows to select different audio files for demo, and their names (only available in the pro version of the plugin).

[youtube https://youtu.be/jydVqEUe9YY]

**How the Pro version of the Music Player for WooCommerce protect the audio files?**

If the "Protect the file" checkbox was ticked in the product's page, and was entered an integer number through the attribute: "Percent of audio used for protected playbacks", the plugin will create a truncated copy of the audio files for selling (or the audio files for demo) into the "/wp-content/plugins/wcmp" directory, to be used as demo. The sizes of the audio files for demo are a percentage of the sizes of the original files (the integer number entered in the player's settings). So, the users cannot access to the original audio files, from the public pages of the products.

**Music Player for WooCommerce - Playlist Widget**

The widget allows to include a playlist on sidebars, with the downloadable files associated to all products with the music player enabled, or for only some of the products.

The widget settings:

*	Title: the title of the widget on sidebar.
*	Products IDs: enter the ids of products to include in the playlist, separated by comma, or the * symbol to include all products.
*	Playlist layout: select between the new playlist layout and the original one.
*	Player layout: select the layout of music players (the widget uses only the play/pause control)
*   Preload: to decide if preload the audio files, their metadata, or don't preload nothing at all. This attribute has a global scope, and will modify the default settings.
*	Play all: play all players in the page (one after the other). This attribute has a global scope, and will modify the default settings.
*	Highlight the current product: if the checkbox is ticked, and the user is in the page of a product, and it is included in the playlist, the corresponding item would be highlighted in the playlist.
*	Continue playing after navigate: if the checkbox is ticked, and there is a song playing when navigates, the player will continue playing after loading the webpage, in the same position.

Note: In mobiles devices where the direct action of user is required for playing audios and videos, the plugin cannot start playing dynamically.


**Music Player for WooCommerce - [wcmp-playlist] shortcode**

The `[wcmp-playlist]` shortcode allows to include a playlist on the pages' contents, with all products, or for some of them.

The shortcode attributes are:

*	products_ids: enter the ids of products to include in the playlist, separated by comma, or the * symbol to include all products

	`[wcmp-playlist products_ids="*"]`

*	title: enter the title text to display prominently above the playlist

	`[wcmp-playlist products_ids="*" title="My Playlist"]`

*	product_categories: this feature enables you to load all products belonging to one or multiple categories at once, eliminating the need to enter their IDs individually. To filter by product categories, simply input their slugs, separated by commas

	`[wcmp-playlist products_ids="*" product_categories="category-1,category-2"]`

*	product_tags: just like filtering by product categories, you can also filter products by tags. To do this, simply enter the tag slugs, separated by commas

	`[wcmp-playlist products_ids="*" product_tags="tag-1,tag-2"]`

*	layout: allows to select the new or original layouts with the values: new or classic ("new" is the value by default)

	`[wcmp-playlist products_ids="*" layout="new"]`

*	player_style: select the layout of music players (the playlist displays only the play/pause control)

	`[wcmp-playlist products_ids="*" player_style="mejs-classic"]`

*	highlight_current_product: if the playlist is included in a product's page, the corresponding item would be highlighted in the playlist

	`[wcmp-playlist products_ids="*" highlight_current_product="1"]`

*	continue_playing: if there is a song playing when navigates, the player will continue playing after loading the webpage in the same position

	`[wcmp-playlist products_ids="*" continue_playing="1"]`

Note: In mobiles devices where the direct action of user is required for playing audios and videos, the plugin cannot start playing dynamically.

*	controls: allows to configure the controls to be used with the players on playlist. The possible values are: track or all, to include only a play/pause button or all player's controls respectively:

	`[wcmp-playlist products_ids="*" controls="track"]`

*	loop: plays all playlist items in an endless loop. The accepted values are: 1 or 0

	`[wcmp-playlist products_ids="*" loop="1"]`

*	cover: allows to include the featured images in the playlist. The possible values are: 0 or 1, 0 is the value by default

	`[wcmp-playlist products_ids="*" cover="1"]`

*	purchased_only: includes only the audio files associated with the purchased products (Plugin commercial version)

	`[wcmp-playlist products_ids="*" purchased_only="1"]`

*	purchased_products: generates the list of products purchased by the logged user (Plugin commercial version)

	`[wcmp-playlist purchased_products="1"]`


**Hooks (actions and filters)**

* wcmp_before_player_shop_page: action called before the players containers in the shop pages.
* wcmp_after_player_shop_page: action called after the players containers in the shop pages.
* wcmp_before_players_product_page: action called before the players containers in the products pages.
* wcmp_after_players_product_page: action called after the players containers in the products pages.

* wcmp_audio_tag: filter called when the audio tag is generated. The callback function receives four parameters: the audio tag, the product's id, the file's id, URL to the audio file;
* wcmp_file_name: filter called when the file's name is included with the player. The callback function receives three parameters: the file's name, the product's id, and the file's id;

* wcmp_widget_audio_tag: filter called when the audio tag is generated as a widget on sidebars. The callback function receives four parameters: the audio tag, the product's id, the file's id, URL to the audio file;
* wcmp_widget_file_name: filter called when the file's name is included with the player as a widget on sidebars. The callback function receives three parameters: the file's name, the product's id, and the file's id;

* wcmp_purchased_product: filter called to know if the product was purchased or not. The callback function receives two parameters: false and the product's id.

* wcmp_ffmpeg_time: filter called to determine the duration of truncated copies of the audio files for demos when the FFmpeg application is used to generate them.

**Other recommended plugins**

* If your project is a music store, and WooCommerce is more than you need it is possible to use [Music Store plugin](https://wordpress.org/plugins/music-store/ "Music Store")
* Or if you need a general purpose music and video player, not especific for WooCommerce, [CP Media Player - Audio Player and Video Player plugin](https://wordpress.org/plugins/audio-and-video-player/ "CP Media Player - Audio Player and Video Player")

== Frequently Asked Questions ==

= Q: Why the audio file is played partially? =

A: If you decide to protect the audio files, the plugin creates a truncated version of the file to be used as demo and prevent that the original file be copied by unauthorized users.

= Q: Why the music player is not loading on page? =

A: Verify that the theme used in your website, includes the function wp_footer(); in the template file "footer.php" or the template file "index.php"

= Q: What can I do if the woocommerce_music_player directory exists and the premium version of plugin cannot be installed? =

A: Go to the plugins section in WordPress, deactivate the free version of Music Player for WooCommerce, and delete it ( Don't worry, this process don't modify players configured with the free version of the plugin), and finally install and activate the premium version of plugin.

= Q: Can be modified the size of audio files played in secure mode? =

A: In the pro version of the plugin the files for demo are generated dynamically to prevent the access to the original files.

Each time save the data of a product, the files for demo are deleted and generated again, so, you simply should modify the percentage of the audio file to be used for demo in the product's page.

== Screenshots ==
01. Music players in the store's pages
02. Music player in the products pages
03. Music player skins
04. Music player settings
05. Playlist widget
06. Inserting the playlist in Gutenberg
07. Inserting the playlist in Elementor
08. Inserting the playlist with Page Builder by SiteOrigin
09. Inserting the playlist BeaverBuilder
10. Inserting the playlist Visual Composer

== Changelog ==

= 1.7.0 =

* The plugin update introduces a new Skin Generator module that leverages AI to create fully personalized player skins.

= 1.6.3 =

* Explicitly include a .htaccess file in the wp-content/uploads/wcmp to prevent directory indexing on misconfigured servers.

= 1.6.2 =

* Purges the Elementor cache during the plugin settings update.

= 1.6.1 =

* Fixes an issue with the Show a single player option in the products settings.

= 1.6.0 =

* Enhances the plugin security by ensuring that player settings for specific products are deleted only after the associated products have been removed. Special thanks to domiee13 and patchstack.com for their valuable contributions.

= 1.5.1 =

* Resolves a warning in the WordPress editor's playlist block.

= 1.5.0 =

* The plugin update lets you enter Google Drive sharing URLs and automatically converts them into direct Google Drive download links.

= 1.4.5 =

* Ensures full compatibility with WordPress 6.8.

= 1.4.4 =

* Automatically clears the website's cache upon saving the plugin settings.

= 1.4.3 =

* Resolves an issue where selecting multiple downloadable files in the product settings affected the download links within the playlists.

= 1.4.2 =

* Introduces a title attribute in the Playlist shortcode, allowing users to specify a title that will be displayed prominently above the playlist.

= 1.4.1 =
= 1.4.0 =

* Implements two new attributes in the Playlist shortcode for filtering the products by the product categories and tags.

= 1.3.11 =

* Enhances the user experience when editing the global player settings.

= 1.3.10 =

* Resolves a compatibility issue with WordPress version previous to 5.5.

= 1.3.9 =

* Resolves a notice by ensuring the language files are properly loaded.

= 1.3.8 =

* Fixes a conflict with Elementor's cache which is currently in Beta.

= 1.3.7 =

* Accepts a new attribute in the playlist shortcode to display/hide the audio durations from the playlist.

= 1.3.6 =

* Modifies the playlists, including additional information like the original file duration  (do not confuse it with the duration of the demos files)

= 1.3.5 =

* Modifies the player settings to enhance its accessibility and user experience.

= 1.3.4 =

* Changes the method by which the plugin identifies the product currently being edited.

= 1.3.3 =

* Prevents the LiteSpeed Cache plugin to affect the players.

= 1.3.2 =

* Modifies the module to load the language files to ensure compatibility with WP 6.7.

= 1.3.1 =

* Improves the plugin settings page.
* Modifies the FFmpeg integration (Professional version).

= 1.3.0 =

* Updates the plugin settings page to make it more user-friendly.
* Enhance the troubleshooting area to make it easier to resolve conflicts with third-party plugins and themes.
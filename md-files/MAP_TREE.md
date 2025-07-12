.
├── .gitignore
├── .gitmodules
├── README.md
├── bfp.php
├── build
│   ├── build.sh
│   ├── update-translations.sh
│   └── update-wavesurfer.sh
├── builders
│   ├── backup
│   │   ├── builders.php
│   │   ├── gutenberg
│   │   │   ├── block.json
│   │   │   ├── gutenberg.css
│   │   │   ├── gutenberg.js
│   │   │   ├── gutenberg.js.backup-20250708-052521
│   │   │   ├── wcblocks.css
│   │   │   └── wcblocks.js
│   │   └── old-code
│   │       ├── bandfront-worky27
│   │       │   ├── BandFront_MediaElement_Player_Modernization_Guide.md
│   │       │   ├── Bandfront_WordPress_Modernization_Report.md
│   │       │   ├── README.md
│   │       │   ├── addons
│   │       │   │   ├── ap-compact-audio-player.addon.php
│   │       │   │   ├── ap-cp-media-player
│   │       │   │   │   └── style.css
│   │       │   │   ├── ap-cp-media-player.addon.php
│   │       │   │   ├── ap-html5-audio-player
│   │       │   │   │   └── style.css
│   │       │   │   ├── ap-html5-audio-player.addon.php
│   │       │   │   ├── ap-mp3-jplayer
│   │       │   │   │   └── style.css
│   │       │   │   └── ap-mp3-jplayer.addon.php
│   │       │   ├── backup_plugin.sh
│   │       │   ├── backup_plugin_make_downloadable.sh
│   │       │   ├── bfp.php
│   │       │   ├── css
│   │       │   │   ├── style.admin.css
│   │       │   │   └── style.css
│   │       │   ├── inc
│   │       │   │   ├── auto_update.inc.php
│   │       │   │   └── cache.inc.php
│   │       │   ├── includes
│   │       │   │   ├── class-bfp-admin.php
│   │       │   │   ├── class-bfp-audio-processor.php
│   │       │   │   ├── class-bfp-config.php
│   │       │   │   ├── class-bfp-file-handler.php
│   │       │   │   ├── class-bfp-hooks-manager.php
│   │       │   │   ├── class-bfp-player-manager.php
│   │       │   │   ├── class-bfp-player-renderer.php
│   │       │   │   └── class-bfp-woocommerce.php
│   │       │   ├── js
│   │       │   │   ├── admin.js
│   │       │   │   ├── public un-minimised.js
│   │       │   │   ├── public.js
│   │       │   │   └── public_src.js
│   │       │   ├── languages
│   │       │   │   ├── bandfront-player-en_US.mo
│   │       │   │   ├── bandfront-player-en_US.po
│   │       │   │   ├── bandfront-player-en_US.pot
│   │       │   │   └── messages.mo
│   │       │   ├── pagebuilders
│   │       │   │   ├── builders.php
│   │       │   │   ├── elementor
│   │       │   │   │   ├── elementor.pb.php
│   │       │   │   │   └── elementor_category.pb.php
│   │       │   │   └── gutenberg
│   │       │   │       ├── gutenberg.css
│   │       │   │       ├── gutenberg.js
│   │       │   │       ├── wcblocks.css
│   │       │   │       └── wcblocks.js
│   │       │   ├── test_mp3_class.php
│   │       │   ├── test_outputs
│   │       │   │   ├── demo_cut_10percent.mp3
│   │       │   │   └── demo_cut_10percent_new.mp3
│   │       │   ├── test_plugin.php
│   │       │   ├── update-translations.sh
│   │       │   ├── vendors
│   │       │   │   ├── demo
│   │       │   │   │   └── demo.mp3
│   │       │   │   ├── mejs-skins
│   │       │   │   │   ├── Guifx_v2_Transports.woff
│   │       │   │   │   ├── controls-ted.png
│   │       │   │   │   ├── controls-wmp-bg.png
│   │       │   │   │   ├── controls-wmp.png
│   │       │   │   │   ├── mejs-skins.css
│   │       │   │   │   └── mejs-skins.min.css
│   │       │   │   └── php-mp3
│   │       │   │       └── class.mp3.php
│   │       │   ├── views
│   │       │   │   ├── assets
│   │       │   │   │   ├── skin1.png
│   │       │   │   │   ├── skin1_btn.png
│   │       │   │   │   ├── skin2.png
│   │       │   │   │   ├── skin2_btn.png
│   │       │   │   │   ├── skin3.png
│   │       │   │   │   └── skin3_btn.png
│   │       │   │   ├── global_options.php
│   │       │   │   └── player_options.php
│   │       │   └── widgets
│   │       │       ├── playlist_widget
│   │       │       │   ├── css
│   │       │       │   │   └── style.css
│   │       │       │   └── js
│   │       │       │       └── public.js
│   │       │       └── playlist_widget.php
│   │       └── woocommerce-music-player
│   │           ├── addons
│   │           │   ├── ap-compact-audio-player.addon.php
│   │           │   ├── ap-cp-media-player
│   │           │   │   └── style.css
│   │           │   ├── ap-cp-media-player.addon.php
│   │           │   ├── ap-html5-audio-player
│   │           │   │   └── style.css
│   │           │   ├── ap-html5-audio-player.addon.php
│   │           │   ├── ap-mp3-jplayer
│   │           │   │   └── style.css
│   │           │   ├── ap-mp3-jplayer.addon.php
│   │           │   ├── dokan
│   │           │   │   ├── player_options.php
│   │           │   │   ├── script.js
│   │           │   │   └── style.css
│   │           │   ├── dokan.addon.php
│   │           │   ├── google-drive.addon.php
│   │           │   ├── mvx.addon.php
│   │           │   ├── wcfm
│   │           │   │   ├── script.js
│   │           │   │   └── style.css
│   │           │   ├── wcfm.addon.php
│   │           │   ├── wcv
│   │           │   │   └── style.css
│   │           │   └── wcv.addon.php
│   │           ├── auto_update.inc.php
│   │           ├── banner.php
│   │           ├── css
│   │           │   ├── style.admin.css
│   │           │   └── style.css
│   │           ├── inc
│   │           │   ├── cache.inc.php
│   │           │   ├── skingenerator.inc.php
│   │           │   └── tools.inc.php
│   │           ├── js
│   │           │   ├── admin.js
│   │           │   ├── public.js
│   │           │   └── public_src.js
│   │           ├── languages
│   │           │   ├── music-player-for-woocommerce-en_US.mo
│   │           │   └── music-player-for-woocommerce-en_US.po
│   │           ├── pagebuilders
│   │           │   ├── beaverbuilder
│   │           │   │   ├── wcmp
│   │           │   │   │   ├── includes
│   │           │   │   │   │   └── frontend.php
│   │           │   │   │   └── wcmp.pb.php
│   │           │   │   └── wcmp.inc.php
│   │           │   ├── builders.php
│   │           │   ├── divi
│   │           │   │   ├── divi.js
│   │           │   │   └── divi.pb.php
│   │           │   ├── elementor
│   │           │   │   ├── elementor.pb.php
│   │           │   │   └── elementor_category.pb.php
│   │           │   ├── gutenberg
│   │           │   │   ├── gutenberg.css
│   │           │   │   ├── gutenberg.js
│   │           │   │   ├── wcblocks.css
│   │           │   │   └── wcblocks.js
│   │           │   ├── siteorigin
│   │           │   │   └── siteorigin-wcmp
│   │           │   │       ├── assets
│   │           │   │       │   └── banner.svg
│   │           │   │       ├── siteorigin-wcmp.php
│   │           │   │       └── tpl
│   │           │   │           └── siteorigin-wcmp-shortcode.php
│   │           │   └── visualcomposer
│   │           │       └── WCMPplaylist
│   │           │           ├── WCMPplaylist
│   │           │           │   └── public
│   │           │           │       ├── wcmp-preview.png
│   │           │           │       └── wcmp-thumbnail.png
│   │           │           ├── manifest.json
│   │           │           └── public
│   │           │               └── dist
│   │           │                   └── element.bundle.js
│   │           ├── readme.txt
│   │           ├── vendors
│   │           │   ├── demo
│   │           │   │   └── demo.mp3
│   │           │   ├── mejs-skins
│   │           │   │   ├── Guifx_v2_Transports.woff
│   │           │   │   ├── controls-ted.png
│   │           │   │   ├── controls-wmp-bg.png
│   │           │   │   ├── controls-wmp.png
│   │           │   │   ├── mejs-skins.css
│   │           │   │   └── mejs-skins.min.css
│   │           │   └── php-mp3
│   │           │       └── class.mp3.php
│   │           ├── views
│   │           │   ├── assets
│   │           │   │   ├── skin1.png
│   │           │   │   ├── skin1_btn.png
│   │           │   │   ├── skin2.png
│   │           │   │   ├── skin2_btn.png
│   │           │   │   ├── skin3.png
│   │           │   │   └── skin3_btn.png
│   │           │   ├── global_options.php
│   │           │   └── player_options.php
│   │           ├── wcmp.php
│   │           └── widgets
│   │               ├── playlist_widget
│   │               │   ├── css
│   │               │   │   └── style.css
│   │               │   └── js
│   │               │       └── public.js
│   │               └── playlist_widget.php
│   ├── builders.php
│   ├── elementor
│   │   ├── elementor.pb.php
│   │   └── elementor_category.pb.php
│   └── gutenberg
│       ├── block.json
│       ├── gutenberg.css
│       ├── gutenberg.js
│       ├── render.php
│       ├── wcblocks.css
│       └── wcblocks.js
├── css
│   ├── admin-notices.css
│   ├── skins
│   │   ├── custom.css
│   │   ├── dark.css
│   │   └── light.css
│   ├── style-admin.css
│   ├── style.admin.css.old
│   └── style.css
├── demo
│   └── demo.mp3
├── includes
│   ├── admin.php
│   ├── audio.php
│   ├── cover-renderer.php
│   ├── hooks.php
│   ├── player.php
│   ├── state-manager.php
│   ├── utils
│   │   ├── analytics.php
│   │   ├── cache.php
│   │   ├── cloud.php
│   │   ├── files.php
│   │   ├── preview.php
│   │   ├── update.php
│   │   └── utils.php
│   └── woocommerce.php
├── js
│   ├── admin.js
│   ├── engine.js
│   └── wavesurfer.js
├── languages
│   ├── bandfront-player-en_US.mo
│   ├── bandfront-player-en_US.po
│   ├── bandfront-player-en_US.pot
│   ├── bandfront-player.pot
│   └── messages.mo
├── md-files
│   ├── AI-CODE-RULES.md
│   ├── BandFront_Media_Players_Modernization_Guide.md
│   ├── Bandfront_WordPress_Modernization_Report.md
│   ├── CLOUD_STORAGE.md
│   ├── COMMIT-RULES.md
│   ├── ERRORS.md
│   ├── JOBz.md
│   ├── MAP.md
│   ├── MAP_OVERVIEW.md
│   ├── MAP_TREE.md
│   └── compare-rules.md
├── modules
│   ├── audio-engine.php
│   └── cloud-engine.php
├── test
│   ├── backup_plugin.sh
│   ├── backup_plugin_make_downloadable.sh
│   ├── clear_opcache.sh
│   ├── player-renderer.php
│   ├── playlist-renderer.php
│   ├── test_mp3_class.php
│   ├── test_outputs
│   └── test_plugin.php
├── vendors
│   ├── php-mp3
│   │   └── class.mp3.php
│   └── wavesurfer
│       ├── plugins
│       │   ├── minimap.min.js
│       │   ├── regions.min.js
│       │   └── timeline.min.js
│       ├── version.txt
│       ├── wavesurfer.esm.js
│       └── wavesurfer.min.js
├── views
│   ├── global-admin-options.php
│   ├── global-admin-options.php.old
│   └── product-options.php
└── widgets
    ├── playlist_widget
    │   ├── css
    │   │   └── style.css
    │   └── js
    │       └── widget.js
    └── playlist_widget.php

91 directories, 220 files


## Design

bandfront-player/
├── assets/                    # Frontend assets
│   ├── css/                  # Stylesheets + skins
│   └── js/                   # JavaScript files
├── builders/                 # Page builder integrations
│   ├── elementor/
│   └── gutenberg/
├── languages/                # Translations
├── src/                      # Core PHP classes (PSR-4)
│   ├── Admin/               # Admin functionality
│   │   └── Admin.php
│   ├── Audio/               # Audio processing
│   │   ├── Analytics.php
│   │   ├── Audio.php
│   │   ├── Player.php
│   │   ├── Preview.php
│   │   ├── Processor.php
│   │   └── Streamer.php
│   ├── Core/                # Core framework
│   │   ├── Bootstrap.php
│   │   ├── Config.php
│   │   └── Hooks.php
│   ├── REST/                # REST API
│   │   └── StreamController.php
│   ├── Storage/             # Cloud storage
│   │   ├── GoogleDrive/
│   │   │   └── GoogleDriveClient.php
│   │   ├── Cloud.php
│   │   └── FileManager.php
│   ├── UI/                   # User interface
│   │   └── Renderer.php
│   ├── Utils/               # Utilities
│   │   ├── Analytics.php
│   │   ├── Cache.php
│   │   ├── Debug.php
│   │   ├── Update.php
│   │   └── Utils.php
│   ├── Widgets/             # WordPress widgets
│   │   └── PlaylistWidget.php
│   └── WooCommerce/         # WooCommerce integration
│       ├── FormatDownloader.php
│       ├── Integration.php
│       └── ProductProcessor.php
├── templates/               # Template files
│   ├── audio-engine-settings.php
│   ├── global-admin-options.php
│   └── product-options.php
├── BandfrontPlayer.php      # Main plugin file
├── README.md                # Documentation
├── REPORT.md                # Refactoring report
├── composer.json            # Dependencies
└── composer.lock

##Bandfront Player - Namespace Structure

#Core Namespaces

Bandfront\
├── Admin\
│   └── Admin                    # Admin functionality
├── Audio\
│   ├── Analytics               # Audio analytics tracking
│   ├── Audio                   # Main audio coordinator
│   ├── Player                  # Player management
│   ├── Preview                 # Demo/preview generation
│   ├── Processor               # Audio processing (FFmpeg, etc.)
│   └── Streamer                # Audio streaming functionality
├── Core\
│   ├── Bootstrap               # Plugin initialization
│   ├── Config                  # Configuration management
│   └── Hooks                   # WordPress hooks registration
├── REST\
│   └── StreamController        # REST API endpoints
├── Storage\
│   ├── GoogleDrive\
│   │   └── GoogleDriveClient   # Google Drive integration
│   ├── Cloud                   # Cloud storage abstraction
│   └── FileManager             # File management
├── UI\
│   └── Renderer                # HTML rendering
├── Utils\
│   ├── Analytics               # Analytics utilities
│   ├── Cache                   # Cache management
│   ├── Debug                   # Debug utilities
│   ├── Update                  # Update functionality
│   └── Utils                   # General utilities
├── Widgets\
│   └── PlaylistWidget          # WordPress widget
└── WooCommerce\
    ├── FormatDownloader        # Format downloads
    ├── Integration             # WooCommerce integration
    └── ProductProcessor        # Product processing


Namespace Mapping

| Namespace | File Location | Purpose |
|-----------|---------------|---------|
| Bandfront\Admin\Admin | src/Admin/Admin.php | Admin functionality (681 lines - needs refactoring) |
| Bandfront\Audio\Player | src/Audio/Player.php | Player management (588 lines - needs refactoring) |
| Bandfront\UI\Renderer | src/UI/Renderer.php | HTML rendering (526 lines - mixed concerns) |
| Bandfront\Core\Bootstrap | src/Core/Bootstrap.php | Plugin initialization & DI container |
| Bandfront\Core\Config | src/Core/Config.php | Configuration & state management |
| Bandfront\Storage\FileManager | src/Storage/FileManager.php | File operations & management |
| Bandfront\REST\StreamController | src/REST/StreamController.php | REST API endpoints |

PSR-4 Autoloading

Base namespace: Bandfront\  
Base directory: src/

Classes are automatically loaded following PSR-4 standards:
•  Bandfront\Audio\Player → src/Audio/Player.php
•  Bandfront\Core\Bootstrap → src/Core/Bootstrap.php
•  Bandfront\Utils\Debug → src/Utils/Debug.php

Modern PHP Features

All classes use:
•  declare(strict_types=1);
•  Proper type hints (string, int, bool, array)
•  Constructor dependency injection
•  Return type declarations

the plugin takes woocommerce audio projects, or maybe even just playlist objects on a page and adds audio players to the downloadable files. it can create demo files from the main files. 

upload/bfp/[productid]/demos.mp3 for each product streamed from here
purchased woo-commerce-uploads/ if owned plays directly

audio through REST API with x-send enabled



examine this code for violating single class principle and mixing concerns

examine for best practices in wordpress 2025

make sure logic is grouped into the correct class 
should the player have a renderer does that mean it works for every render? including block and widget does that work in this design? 


concerns atm:
where is URL generation 
are all file operations happening inside files?


AUDIO ENGINE:
fails: HTML5 fallback
default: MEdia element
select: Wavesurfer
etc...

so if we construct a function that hooks onto product generation or updating, if its a downloadable audio product - we just leave it where it was created, usually in woocommerce_uploads/ (or if that can be retrieved by a native function that would be better) - then we pass it off to a function which zips up all of it into various formats and then any user can download any format straight away - we just get ffmpeg to do it every time the product is generated and then no more processing on the fly to do that. then if user owns album it just retrieves purchased url presumably in woocommerce_uploads/ which means this program can just always use default urls to stream through API and urls that are pre-generated from the titles to download zips etc
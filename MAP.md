# Bandfront Player - Codebase Map

## Main Plugin File

**File:** `/bfp.php`
**Purpose:** Main plugin entry point, initializes core functionality and loads all dependencies.
**WordPress Integration Points:**
- Hooks: `init`, `plugins_loaded`, `wp_privacy_personal_data_export_file`, `wp_privacy_personal_data_eraser`
- Filters: `get_post_metadata`, `the_title`, `esc_html`, `woocommerce_product_title`, `wp_kses_allowed_html`
- Shortcodes: `[bfp-playlist]`
- Actions: `bfp_main_player`, `bfp_all_players`, `bfp_delete_purchased_files` (scheduled)

### BandfrontPlayer Class

- **__construct()**
  - Purpose: Initializes all manager classes and sets up file directories
  - Inputs: None
  - Outputs: Instantiates manager objects
  - WordPress Data: None directly
  - Data Flow: Creates instances of all manager classes → sets up file paths
  - Patterns/Concerns: Tight coupling with all manager classes

- **plugins_loaded()**
  - Purpose: Sets up plugin after WordPress loads, checks WooCommerce dependency
  - Inputs: None
  - Outputs: Registers hooks and loads addons
  - WordPress Data: Checks `class_exists('woocommerce')`
  - Data Flow: WP hook → load textdomain → setup filters → load page builders
  - Patterns/Concerns: Direct filter addition on `the_title`

- **init()**
  - Purpose: Main initialization, sets up all frontend/backend hooks
  - Inputs: None
  - Outputs: Registers AJAX handlers, shortcodes, scheduled events
  - WordPress Data: `get_current_user_id()`, `$_REQUEST['bfp-action']`
  - Data Flow: WP hook → check user permissions → register handlers
  - Patterns/Concerns: **Direct $_REQUEST access**, URL-based AJAX instead of proper endpoints

- **enqueue_resources()**
  - Purpose: Loads CSS/JS assets for player functionality, now includes audio engine selection
  - Inputs: None
  - Outputs: Enqueued scripts and styles (MediaElement.js OR WaveSurfer.js based on settings)
  - WordPress Data: `wp_enqueue_script()`, `wp_localize_script()`, `$this->get_global_attr('_bfp_audio_engine')`
  - Data Flow: Check if already enqueued → determine audio engine → load appropriate scripts → load custom scripts
  - Patterns/Concerns: Good use of WP enqueue system, conditional script loading based on audio engine

---

## Core Manager Classes

### **File:** `/includes/class-bfp-config.php`
**Purpose:** Manages plugin configuration, handles global/product-specific settings retrieval.
**WordPress Integration Points:** None directly

- **get_global_attr()**
  - Purpose: Retrieves global plugin settings with caching
  - Inputs: `$attr` (setting name), `$default`
  - Outputs: Setting value or default
  - WordPress Data: `get_option('bfp_global_settings')`
  - Data Flow: Check cache → load from DB if needed → return value
  - Patterns/Concerns: Good caching implementation

- **get_product_attr()**
  - Purpose: Gets product-specific settings with fallback to global
  - Inputs: `$product_id`, `$attr`, `$default`
  - Outputs: Product or global setting value
  - WordPress Data: `get_post_meta()`, `get_option()`
  - Data Flow: Check product meta → fall back to global → apply filters
  - Patterns/Concerns: Proper fallback hierarchy

### **File:** `/includes/class-bfp-audio-processor.php`
**Purpose:** Handles audio file processing, streaming, truncation, and analytics tracking.
**WordPress Integration Points:**
- Actions: `bfp_play_file`, `bfp_truncated_file`

- **output_file()**
  - Purpose: Streams audio file to browser with optional truncation
  - Inputs: `$args` array with url, product_id, secure_player settings
  - Outputs: HTTP redirect or file stream
  - WordPress Data: Direct file paths, `do_action()`
  - Data Flow: URL → validate → copy/truncate → stream output
  - Patterns/Concerns: **Direct file operations**, **security through file paths**, complex truncation logic

- **process_with_ffmpeg()**
  - Purpose: Uses ffmpeg to truncate audio files
  - Inputs: `$file_path`, `$o_file_path`, `$file_percent`
  - Outputs: Truncated audio file
  - WordPress Data: None
  - Data Flow: Shell exec ffmpeg → parse duration → create truncated file
  - Patterns/Concerns: **Shell execution**, **no error handling for shell_exec**

- **tracking_play_event()**
  - Purpose: Sends play events to Google Analytics
  - Inputs: `$product_id`, `$file_url`
  - Outputs: Analytics HTTP request
  - WordPress Data: `wp_remote_post()`, `$_COOKIE['_ga']`
  - Data Flow: Get analytics settings → build payload → send to GA
  - Patterns/Concerns: **Direct $_COOKIE access**, no consent checking

### **File:** `/includes/class-bfp-admin.php`
**Purpose:** Manages all WordPress admin functionality, settings pages, and product meta boxes.
**WordPress Integration Points:**
- Hooks: `admin_menu`, `admin_init`, `save_post`, `after_delete_post`
- Filters: `manage_product_posts_columns`, `manage_product_posts_custom_column`

- **save_post()**
  - Purpose: Saves product-specific player settings when product is saved
  - Inputs: `$post_id`, `$post`, `$update`
  - Outputs: Updates post meta
  - WordPress Data: `$_POST`, `wp_verify_nonce()`, `update_post_meta()`
  - Data Flow: WP hook → verify nonce → sanitize data → save meta
  - Patterns/Concerns: **Direct $_POST access**, proper nonce verification

- **save_global_settings()**
  - Purpose: Processes and saves plugin global settings including audio engine
  - Inputs: `$_REQUEST` data
  - Outputs: Updates options, clears cache
  - WordPress Data: `update_option()`, `$_REQUEST`
  - Data Flow: Sanitize input → build settings array (including audio engine) → save → clear cache
  - Patterns/Concerns: **Direct $_REQUEST access**, manual form processing instead of Settings API, now includes audio engine settings

- **apply_settings_to_all_products()**
  - Purpose: Propagates global settings to all products
  - Inputs: `$global_settings` array
  - Outputs: Deletes obsolete meta keys, updates essential settings
  - WordPress Data: `get_posts()`, `delete_post_meta()`, `update_post_meta()`
  - Data Flow: Get all products → delete obsolete meta → update with global values
  - Patterns/Concerns: **Potential performance issue with -1 numberposts**

- **save_product_options()**
  - Purpose: Saves product-specific player settings including audio engine override
  - Inputs: `$post_id`, `$_DATA`
  - Outputs: Updates post meta
  - WordPress Data: `add_post_meta()`
  - Data Flow: Sanitize → save essential settings → save audio engine override if not global
  - Patterns/Concerns: Now includes product-specific audio engine selection

### **File:** `/includes/class-bfp-player-renderer.php`
**Purpose:** Renders audio players in various contexts (main, all, grouped products).
**WordPress Integration Points:** None directly

- **include_main_player()**
  - Purpose: Renders single main player for a product
  - Inputs: `$product` (WC_Product), `$_echo` (bool)
  - Outputs: HTML player markup
  - WordPress Data: Product object methods
  - Data Flow: Get files → check visibility → generate player HTML
  - Patterns/Concerns: **Mixed HTML generation and logic**

- **include_all_players()**
  - Purpose: Renders all players for a product including variations
  - Inputs: `$product` (WC_Product)
  - Outputs: Multiple player HTML
  - WordPress Data: WooCommerce product methods
  - Data Flow: Check product type → get files → render appropriate players
  - Patterns/Concerns: Complex product type handling

- **_get_product_files()**
  - Purpose: Retrieves audio files for a product with filtering
  - Inputs: `$args` with product, file_id, all flag
  - Outputs: Array of file data
  - WordPress Data: `get_post_meta()`, WC downloadable files
  - Data Flow: Check demos → get WC files → merge → filter duplicates
  - Patterns/Concerns: Complex merge logic, multiple data sources

### **File:** `/includes/class-bfp-woocommerce.php`
**Purpose:** Handles WooCommerce-specific integrations and user purchase checks.
**WordPress Integration Points:**
- Filters: Applied through main class

- **woocommerce_user_product()**
  - Purpose: Checks if current user has purchased a product
  - Inputs: `$product_id`
  - Outputs: Purchase ID or false
  - WordPress Data: `get_current_user_id()`, `wc_customer_bought_product()`
  - Data Flow: Check force flag → verify purchase → return result
  - Patterns/Concerns: Good use of WC APIs

- **replace_playlist_shortcode()**
  - Purpose: Renders playlist shortcode with product audio files
  - Inputs: `$atts` shortcode attributes
  - Outputs: HTML playlist
  - WordPress Data: `WP_Query`, product methods
  - Data Flow: Parse attributes → query products → build playlist HTML
  - Patterns/Concerns: **Direct HTML building**, complex query logic

### **File:** `/includes/class-bfp-file-handler.php`
**Purpose:** Manages file operations, demo files, and cleanup tasks.
**WordPress Integration Points:**
- Scheduled events: `bfp_delete_purchased_files`

- **_clearDir()**
  - Purpose: Recursively deletes directory contents
  - Inputs: `$dirPath`
  - Outputs: Deletes files/directories
  - WordPress Data: Direct file operations
  - Data Flow: Scan directory → delete files → remove subdirs
  - Patterns/Concerns: **Dangerous recursive deletion**, no safety checks

- **delete_purchased_files()**
  - Purpose: Cleans up temporary purchased files
  - Inputs: None
  - Outputs: Deletes old files
  - WordPress Data: File timestamps
  - Data Flow: Scan purchased dir → check age → delete old files
  - Patterns/Concerns: Hard-coded 2-day limit

### **File:** `/includes/class-bfp-player-manager.php`
**Purpose:** Manages player state flags and enqueued resources tracking.
**WordPress Integration Points:** None directly

- **Player state getters/setters**
  - Purpose: Track which players have been inserted
  - Inputs: Boolean flags
  - Outputs: State values
  - WordPress Data: None
  - Data Flow: Simple property access
  - Patterns/Concerns: Could use single state array

### **File:** `/includes/class-bfp-hooks-manager.php`
**Purpose:** Centralizes hook configuration for different themes and contexts.
**WordPress Integration Points:** Returns hook configuration

- **get_hooks_config()**
  - Purpose: Returns array of hooks for player insertion
  - Inputs: None
  - Outputs: Hook configuration array
  - WordPress Data: Hook names and priorities
  - Data Flow: Return static configuration
  - Patterns/Concerns: Good centralization of hook config

### **File:** `/includes/class-bfp-cloud-tools.php`
**Purpose:** Placeholder for cloud storage integration functionality.
**WordPress Integration Points:** None currently

**Status:** Empty implementation file
**Patterns/Concerns:** **Unused placeholder**, could be removed or implemented

---

## Utility Files

### **File:** `/inc/tools.inc.php`
**Purpose:** Google Drive URL processing utilities
**Key Functions:**
- **get_google_drive_download_url()**: Converts various Google Drive URL formats to direct download URLs
- **get_google_drive_file_name()**: Attempts to retrieve filename from Google Drive

### **File:** `/inc/cache.inc.php`
**Purpose:** Cache clearing utilities for various WordPress caching plugins
**Supported Plugins:** WP Rocket, WP Super Cache, W3 Total Cache, LiteSpeed Cache, SiteGround Optimizer, WP Fastest Cache, Elementor, Cache Enabler

### **File:** `/inc/auto_update.inc.php`
**Purpose:** Auto-update functionality (currently disabled with empty update path)

## Add-ons

### **File:** `/addons/audio-engine.addon.php`
**Purpose:** Provides audio engine selection between MediaElement.js and WaveSurfer.js
**WordPress Integration Points:**
- Actions: `bfp_addon_general_settings`, `bfp_addon_product_settings`

- **bfp_audio_engine_settings()**
  - Purpose: Renders audio engine selection UI in global settings
  - Inputs: None (uses global $BandfrontPlayer)
  - Outputs: HTML form elements for audio engine selection
  - WordPress Data: `$BandfrontPlayer->get_global_attr()` for current settings
  - Data Flow: Get settings → render radio buttons → conditional visualization options
  - Patterns/Concerns: Clean integration with settings page via action hook

- **bfp_audio_engine_product_settings()**
  - Purpose: Renders product-specific audio engine override dropdown
  - Inputs: `$product_id`
  - Outputs: HTML select element for per-product engine selection
  - WordPress Data: `get_post_meta()` for product override
  - Data Flow: Check product meta → get global default → render dropdown
  - Patterns/Concerns: Follows global/product override pattern

### **File:** `/addons/google-drive.addon.php`
**Purpose:** Google Drive integration for storing demo files
**WordPress Integration Points:**
- Actions: `bfp_save_setting`, `bfp_general_settings`, `bfp_delete_file`, `bfp_delete_post`, `bfp_play_file`, `bfp_truncated_file`

**Key Functions:**
- OAuth 2.0 integration for Google Drive access
- Automatic demo file upload to cloud storage
- Bandwidth saving through direct streaming from Google Drive
- Secure credential management

---

## JavaScript Files

### **File:** `/js/engine.js` 
**Purpose:** Frontend player functionality, supports both MediaElement.js and WaveSurfer.js
**WordPress Integration Points:**
- AJAX: Direct URL requests to `?bfp-action=play`
- Receives settings via `bfp_global_settings` localized object

**Key Functions:**
- Dual audio engine support (MediaElement.js / WaveSurfer.js)
- Player initialization based on selected engine
- Volume fade effects for both engines
- Play tracking via URL parameters
- Mobile/iOS specific handling
- Graceful fallback if WaveSurfer.js unavailable

**New Features:**
- `initWaveSurferPlayer()` - Initializes WaveSurfer players
- `smoothFadeOut()` - Works with both audio engines
- Engine detection and conditional initialization

**Patterns/Concerns:** 
- **URL-based AJAX instead of wp.ajax**
- **Direct URL construction**
- Complex fade logic
- Good error handling for engine fallbacks

### **File:** `/js/admin.js`
**Purpose:** Admin UI functionality for settings and product pages.
**WordPress Integration Points:** Admin page interactions

**Key Functions:**
- Settings form validation
- File upload handling
- UI state management

---

## Widget Files

### **File:** `/widgets/playlist_widget.php`
**Purpose:** WordPress widget for displaying audio playlists with multiple layout options.
**WordPress Integration Points:**
- Widget registration: `widgets_init`
- Widget class: `BFP_PLAYLIST_WIDGET extends WP_Widget`

- **BFP_PLAYLIST_WIDGET::__construct()**
  - Purpose: Initializes widget with title and description
  - Inputs: None
  - Outputs: Widget configuration
  - WordPress Data: Widget base ID and name
  - Data Flow: Parent constructor → widget setup
  - Patterns/Concerns: Standard WP_Widget implementation

- **BFP_PLAYLIST_WIDGET::widget()**
  - Purpose: Frontend widget output with two layout modes (classic/new)
  - Inputs: `$args` (widget wrapper), `$instance` (widget settings)
  - Outputs: HTML playlist output
  - WordPress Data: `do_shortcode()`, widget instance data
  - Data Flow: Extract settings → build shortcode → render output
  - Patterns/Concerns: **Direct HTML building**, complex layout logic

- **BFP_PLAYLIST_WIDGET::form()**
  - Purpose: Admin widget configuration form
  - Inputs: `$instance` (current settings)
  - Outputs: Form HTML
  - WordPress Data: Widget field names and IDs
  - Data Flow: Load settings → generate form fields
  - Patterns/Concerns: Manual form building

- **BFP_PLAYLIST_WIDGET::update()**
  - Purpose: Sanitizes and saves widget settings
  - Inputs: `$new_instance`, `$old_instance`
  - Outputs: Sanitized settings array
  - WordPress Data: Widget instance data
  - Data Flow: Sanitize input → merge settings → return
  - Patterns/Concerns: Good input sanitization

### **File:** `/widgets/playlist_widget/js/public.js`
**Purpose:** Frontend JavaScript for playlist widget functionality.
**WordPress Integration Points:** Frontend widget behavior

**Key Functions:**
- Cookie-based continue playing functionality
- Tracks current playing position between page loads
- Multi-file download handling with delay
- Product highlight management

**Patterns/Concerns:** 
- **Direct cookie manipulation** (could use localStorage)
- **Hardcoded 1-second delay** for downloads
- Good event handling structure

### **File:** `/widgets/playlist_widget/css/style.css`
**Purpose:** Styling for playlist widget layouts.

**Key Styles:**
- `.bfp-widget-playlist` - Main playlist container
- `.bfp-widget-product` - Product item styling
- Responsive layout with flexbox
- Hover states and current item highlighting

**Patterns/Concerns:** 
- **Inline SVG in CSS** for purchase button icon
- Good responsive design
- Well-structured CSS with clear naming

---

## View Files

### **File:** `/views/global_options.php`
**Purpose:** Admin settings page template for global plugin configuration.
**WordPress Integration Points:** Admin page output

**Key Features:**
- Comprehensive settings form with multiple tabs
- Player configuration options (layout, controls, behavior)
- Security settings (registered only, purchased only)
- Analytics integration (Google Analytics UA/GA4)
- Cloud Storage settings (moved from add-on)
- Add-ons section for extensible features
- Troubleshooting options

**Changes:**
- Removed duplicate Audio Engine HTML sections
- Cloud Storage settings now inline instead of via add-on hook
- Add-ons section using `do_action('bfp_addon_general_settings')`

**Form Structure:**
- Uses WordPress nonces for security
- Manual form handling (not Settings API)
- Organized in logical sections with descriptions
- Help text and tooltips for complex options

**Patterns/Concerns:** 
- **Manual form building** instead of Settings API
- **Inline styles and JavaScript**
- Good organization but could be broken into components

### **File:** `/views/player_options.php`
**Purpose:** Product-level player settings metabox template.
**WordPress Integration Points:** Product edit page metabox

**Key Features:**
- Product-specific player overrides
- Demo file management interface
- Player enable/disable per product
- Volume, preload, and behavior settings
- JavaScript for dynamic demo file addition

**JavaScript Functionality:**
- Add/remove demo files dynamically
- Google Drive URL detection and conversion
- File upload handling
- UI state management

**Patterns/Concerns:** 
- **Inline JavaScript** for functionality
- **Direct $_POST handling** in form
- Good separation of global vs product settings

---

## Page Builder Integration

### **File:** `/pagebuilders/builders.php`
**Purpose:** Central integration point for various page builders (Gutenberg, Elementor, etc.).
**WordPress Integration Points:**
- Actions: `init` (for Gutenberg block registration)
- Actions: `elementor/widgets/widgets_registered` (for Elementor)
- Actions: `plugins_loaded` (for Visual Composer/WPBakery)
- Actions: `fl_builder_init` (for Beaver Builder)
- Actions: `after_setup_theme` (for Divi)

- **BFP_BUILDERS::run()**
  - Purpose: Main entry point that initializes all page builder integrations
  - Inputs: None
  - Outputs: Registers blocks/widgets for each page builder
  - WordPress Data: Uses `add_action()` for various builder hooks
  - Data Flow: Check if builder active → register appropriate integration
  - Patterns/Concerns: Good modular structure for each builder

- **BFP_BUILDERS::gutenberg()**
  - Purpose: Registers Gutenberg block for playlist shortcode
  - Inputs: None
  - Outputs: Registers block and enqueues editor assets
  - WordPress Data: `register_block_type()`, `wp_localize_script()`
  - Data Flow: Register block → enqueue scripts → localize config data
  - Patterns/Concerns: Uses legacy block registration instead of block.json

- **BFP_BUILDERS::elementor()**
  - Purpose: Registers Elementor widget
  - Inputs: None
  - Outputs: Includes widget class file
  - WordPress Data: Action on `elementor/widgets/widgets_registered`
  - Data Flow: Check Elementor loaded → include widget → register widget
  - Patterns/Concerns: Requires separate widget class file


### **File:** `/pagebuilders/gutenberg/gutenberg.js`
**Purpose:** JavaScript for Gutenberg block editor interface.
**WordPress Integration Points:** Block editor JavaScript API

**Key Functions:**
- Block registration with `wp.blocks.registerBlockType()`
- Creates textarea for shortcode input
- Renders live preview iframe
- Inspector controls for help text

**Patterns/Concerns:**
- **Legacy block registration** without modern block.json
- **Direct iframe embedding** for preview
- Good use of InspectorControls for help text

### **File:** `/pagebuilders/gutenberg/gutenberg.css`
**Purpose:** Styles for Gutenberg block editor interface.

**Key Styles:**
- `.bfp-iframe-container` - Preview iframe container
- `.bfp-playlist-shortcode-input` - Shortcode textarea styling

**Patterns/Concerns:** Basic styling, could use more modern CSS

### **File:** `/pagebuilders/gutenberg/block.json`
**Purpose:** Modern Gutenberg block metadata file.
**WordPress Integration Points:** Block registration metadata

**Configuration:**
- Block name: `bfp/bandfront-player-playlist`
- Category: `media`
- Default shortcode attribute
- Script and style references

**Patterns/Concerns:** 
- **Modern block.json format**
- **Not currently used** - builders.php uses legacy registration

### **File:** `/pagebuilders/gutenberg/wcblocks.js`
**Purpose:** Integration with WooCommerce Blocks to handle dynamic content loading.
**WordPress Integration Points:** DOM manipulation for WC Blocks

**Key Functions:**
- MutationObserver to watch for WooCommerce block updates
- Reveals hidden product titles in WC blocks
- Triggers BandFront player initialization

**Patterns/Concerns:**
- **Direct DOM manipulation** with jQuery
- **Workaround for WC Blocks** title hiding issue
- Uses MutationObserver for dynamic content

### **File:** `/pagebuilders/gutenberg/wcblocks.css`
**Purpose:** CSS to handle WooCommerce Blocks display issues.

**Key Styles:**
- Hides product titles initially (revealed by JS)

**Patterns/Concerns:** Hacky workaround for WC Blocks compatibility

---

## CSS Files

### **File:** `/css/style.css`
**Purpose:** Main frontend styles including WaveSurfer.js support

**New Additions:**
- `.bfp-waveform` - WaveSurfer container styling
- `.bfp-play-button` - Simple play buttons for WaveSurfer
- Responsive waveform styles
- Loading state indicators

### **File:** `/css/style.admin.css`
**Purpose:** Admin interface styling with enhanced visual organization

**Key Features:**
- Tips section with gradient backgrounds
- Color-coded troubleshooting cards
- Add-on section styling
- Cloud storage instructions styling

---

## Key Patterns & Concerns

### Mixed Concerns
- **Player rendering mixed with business logic** in `class-bfp-player-renderer.php`
- **Direct HTML generation** throughout instead of templates
- **File operations mixed with HTTP output** in `class-bfp-audio-processor.php`

### Security Issues
- **Direct $_REQUEST/$_POST access** without abstraction
- **URL-based AJAX** instead of proper REST/AJAX endpoints
- **File streaming through PHP** instead of secure URLs
- **Shell execution** for ffmpeg without proper escaping

### Performance Concerns
- **Recursive file operations** without limits
- **Loading all products** with `numberposts => -1`
- **No pagination** for large product queries

### Deprecated Patterns
- **URL parameter AJAX** instead of REST API
- **Manual form processing** instead of Settings API
- **Direct file access** instead of WordPress attachment system
- **Legacy Gutenberg block registration** instead of block.json

### Duplication
- **File validation logic** appears in multiple places
- **User permission checks** scattered across classes
- **Settings retrieval** duplicated between global and product level

### Tight Coupling
- **Main class depends on all manager classes**
- **Audio processor tightly coupled to file system**
- **Player renderer tied to WooCommerce structure**
- **Page builders require manual integration** for each builder

### Page Builder Specific Issues
- **Gutenberg using legacy registration** despite having block.json
- **Multiple integration methods** across different builders
- **WooCommerce Blocks workaround** using DOM manipulation
- **No unified widget/block interface** across builders

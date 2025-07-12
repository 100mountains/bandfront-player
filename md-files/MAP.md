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
  - Purpose: Initializes all core components in proper order
  - Inputs: None
  - Outputs: Component instances via private properties
  - WordPress Data: None directly
  - Data Flow: Config → File Handler → Audio Engine → WooCommerce → Player → Hooks → Admin
  - Patterns/Concerns: Clean component initialization with dependency injection pattern

## Core Classes

### State Management

**File:** `/includes/state-manager.php`
**Purpose:** Centralized configuration and state management with inheritance hierarchy
**Class:** `BFP_Config`

- **get_state($key, $default, $product_id, $options)**
  - Purpose: Get configuration value with context-aware inheritance
  - Inputs: Setting key, default value, optional product ID, options array
  - Outputs: Setting value (product → global → default)
  - WordPress Data: `get_post_meta()`, `get_option()`
  - Data Flow: Check product meta → Check global settings → Return default
  - Patterns/Concerns: Context-aware state resolution with caching

### Audio Processing

**File:** `/includes/audio.php`
**Purpose:** Audio file processing, streaming, and demo generation
**Class:** `BFP_Audio_Engine`

- **output_file($args)**
  - Purpose: Stream audio file with optional truncation and analytics
  - Inputs: File URL, product ID, security settings
  - Outputs: Audio stream or redirect
  - WordPress Data: `get_post_meta()` for file data
  - Data Flow: Validate → Process → Track → Stream/Redirect
  - Patterns/Concerns: Secure file handling with demo generation

### Player Management

**File:** `/includes/player.php`
**Purpose:** Player HTML generation, rendering, and resource management
**Class:** `BFP_Player`

- **get_player($audio_url, $args)**
  - Purpose: Generate HTML5 audio player based on selected engine
  - Inputs: Audio URL, player configuration
  - Outputs: Player HTML markup
  - WordPress Data: Player settings from state manager
  - Data Flow: Config → Engine Selection → HTML Generation
  - Patterns/Concerns: Engine abstraction (MediaElement/WaveSurfer)

- **include_main_player($product, $_echo)**
  - Purpose: Render primary player for product
  - Inputs: Product object/ID, echo flag
  - Outputs: Player HTML
  - WordPress Data: Product data via WooCommerce
  - Data Flow: Get files → Check context → Generate player → Output
  - Patterns/Concerns: Smart context detection (single/archive)

- **include_all_players($product)**
  - Purpose: Render all players for a product
  - Inputs: Product object/ID
  - Outputs: Multiple player HTML (single or table layout)
  - WordPress Data: Product files and settings
  - Data Flow: Get files → Choose layout → Render players
  - Patterns/Concerns: Adaptive layout based on file count

- **render_player_table($files, $product_id, $settings)**
  - Purpose: Generate table layout for multiple audio files
  - Inputs: Files array, product ID, settings
  - Outputs: HTML table with players
  - WordPress Data: None directly
  - Data Flow: Loop files → Generate rows → Build table
  - Patterns/Concerns: Responsive table layout

- **enqueue_resources()**
  - Purpose: Load scripts/styles based on audio engine
  - Inputs: None
  - Outputs: Enqueued WordPress resources
  - WordPress Data: `wp_enqueue_script()`, `wp_enqueue_style()`
  - Data Flow: Check engine → Load appropriate resources
  - Patterns/Concerns: Conditional loading for performance

### Cover Renderer

**File:** `/includes/cover-renderer.php`
**Purpose:** Play button overlays for product images
**Class:** `BFP_Cover_Renderer`

- **render($product_id)**
  - Purpose: Add play buttons to product cover images
  - Inputs: Product ID
  - Outputs: HTML overlay markup
  - WordPress Data: Product image data
  - Data Flow: Check settings → Generate overlay → Inject HTML
  - Patterns/Concerns: Non-intrusive DOM manipulation

### Hook Management

**File:** `/includes/hooks.php`
**Purpose:** Dynamic WordPress hook registration and management
**Class:** `BFP_Hooks`

- **init_hooks()**
  - Purpose: Register all WordPress hooks dynamically
  - Inputs: None
  - Outputs: Registered actions/filters
  - WordPress Data: Hook system integration
  - Data Flow: Check context → Register appropriate hooks
  - Patterns/Concerns: Context-aware registration to prevent conflicts

### WooCommerce Integration

**File:** `/includes/woocommerce.php`
**Purpose:** Deep WooCommerce integration, purchase handling, and playlist shortcode
**Class:** `BFP_WooCommerce`

- **woocommerce_user_product($product_id)**
  - Purpose: Check if user purchased product
  - Inputs: Product ID
  - Outputs: Boolean purchase status
  - WordPress Data: Order data, user purchases
  - Data Flow: Get user → Check orders → Verify purchase
  - Patterns/Concerns: Performance optimization with caching

- **replace_playlist_shortcode($atts)**
  - Purpose: Handle [bfp-playlist] shortcode rendering
  - Inputs: Shortcode attributes
  - Outputs: Playlist HTML
  - WordPress Data: Product queries, user data
  - Data Flow: Parse attributes → Query products → Render playlist
  - Patterns/Concerns: Bulk product handling

- **render_single_product($product, $product_obj, $atts, ...)**
  - Purpose: Render single product in playlist
  - Inputs: Product data, settings, files
  - Outputs: Product player HTML
  - WordPress Data: Product metadata
  - Data Flow: Build layout → Add players → Format output
  - Patterns/Concerns: Layout flexibility (new/classic)

### Admin Interface

**File:** `/includes/admin.php`
**Purpose:** Backend settings and product options
**Class:** `BFP_Admin`

- **settings_page()**
  - Purpose: Render global settings interface
  - Inputs: POST data for saves
  - Outputs: Settings HTML
  - WordPress Data: `update_option()` for settings
  - Data Flow: Display form → Process submission → Save → Clear cache
  - Patterns/Concerns: Nonce verification, bulk operations

- **load_admin_modules()**
  - Purpose: Dynamic module loading system
  - Inputs: None
  - Outputs: Loaded module instances
  - WordPress Data: Module state from config
  - Data Flow: Check enabled → Load files → Initialize
  - Patterns/Concerns: Extensible module architecture

## Utility Classes

### File Management

**File:** `/includes/utils/files.php`
**Purpose:** Demo file creation and management
**Class:** `BFP_File_Handler`

- **_createDir()**
  - Purpose: Create secure upload directories
  - Inputs: None
  - Outputs: Directory structure
  - WordPress Data: `wp_upload_dir()`
  - Data Flow: Get paths → Create dirs → Set permissions
  - Patterns/Concerns: Security with .htaccess protection

### Cloud Integration

**File:** `/includes/utils/cloud.php`
**Purpose:** Cloud storage URL processing
**Class:** `BFP_Cloud_Tools`

- **get_google_drive_download_url($url)**
  - Purpose: Convert Drive URLs to direct download
  - Inputs: Google Drive share URL
  - Outputs: Direct download URL
  - WordPress Data: None
  - Data Flow: Parse URL → Extract ID → Build download URL
  - Patterns/Concerns: Multiple URL format support

### Cache Management

**File:** `/includes/utils/cache.php`
**Purpose:** Cross-plugin cache clearing
**Class:** `BFP_Cache`

- **clear_all_caches()**
  - Purpose: Clear all known WordPress caches
  - Inputs: None
  - Outputs: Cache flush results
  - WordPress Data: Various cache plugin APIs
  - Data Flow: Detect plugins → Call clear methods
  - Patterns/Concerns: Plugin-agnostic clearing

### Analytics

**File:** `/includes/utils/analytics.php`
**Purpose:** Playback tracking and analytics
**Class:** `BFP_Analytics`

- **track_play_event($product_id, $file_url)**
  - Purpose: Record playback events
  - Inputs: Product ID, file URL
  - Outputs: Analytics event
  - WordPress Data: Post meta for counters
  - Data Flow: Capture event → Update counter → Send to GA
  - Patterns/Concerns: Privacy-aware tracking

### Preview Handler

**File:** `/includes/utils/preview.php`
**Purpose:** Handle preview/play requests
**Class:** `BFP_Preview`

- **handle_preview_request()**
  - Purpose: Process play button clicks
  - Inputs: Request parameters
  - Outputs: Audio stream
  - WordPress Data: Product/file validation
  - Data Flow: Validate → Track → Stream
  - Patterns/Concerns: Security validation

### Auto Updates

**File:** `/includes/utils/update.php`
**Purpose:** Plugin update checking
**Class:** `BFP_Updater`

- **check_update($transient)**
  - Purpose: Check for plugin updates
  - Inputs: Update transient
  - Outputs: Modified transient
  - WordPress Data: Plugin version data
  - Data Flow: Check version → Compare → Update transient
  - Patterns/Concerns: Future implementation ready

### General Utilities

**File:** `/includes/utils/utils.php`
**Purpose:** Helper functions and utilities
**Class:** `BFP_Utils`

- **get_post_types($string)**
  - Purpose: Get supported post types
  - Inputs: String format flag
  - Outputs: Post types array/string
  - WordPress Data: Post type registry
  - Data Flow: Build array → Apply filter → Format
  - Patterns/Concerns: Extensible via filters

## Module System

### Audio Engine Module

**File:** `/modules/audio-engine.php`
**Purpose:** Audio engine selection UI
**Functions:** `bfp_audio_engine_settings()`, `bfp_audio_engine_product_settings()`

- Adds MediaElement.js vs WaveSurfer.js selection
- Global and per-product override settings
- Visualization options for WaveSurfer

### Cloud Engine Module

**File:** `/modules/cloud-engine.php`
**Purpose:** Cloud storage configuration
**Functions:** `bfp_cloud_storage_settings()`

- Google Drive integration setup
- OAuth configuration
- Future: Dropbox, S3, Azure support

## View Templates

### Global Settings

**File:** `/views/global-admin-options.php`
**Purpose:** Main settings page UI
**Sections:**
- General Settings (access control, demos)
- Player Settings (appearance, behavior)
- File Truncation (security)
- Analytics (tracking)
- Cloud Storage (external storage)
- Audio Engine (engine selection)
- Troubleshooting (common fixes)

### Product Options

**File:** `/views/product-options.php`
**Purpose:** Product-specific player settings
**Sections:**
- Player enable/disable
- Behavior overrides
- Demo file management
- Audio engine override

## Frontend Assets

### JavaScript

**File:** `/js/engine.js`
**Purpose:** Player functionality and interactions
- MediaElement.js initialization
- Play button handling
- Playlist management
- Analytics tracking

**File:** `/js/wavesurfer.js`
**Purpose:** WaveSurfer engine integration
- Waveform rendering
- Audio visualization
- Progress tracking

**File:** `/js/admin.js`
**Purpose:** Backend UI functionality
- Settings management
- File uploads
- Dynamic UI elements

### Stylesheets

**File:** `/css/style.css`
**Purpose:** Frontend player styles
- Player layouts
- Responsive design
- Animation effects

**File:** `/css/skins/dark.css`
**Purpose:** Dark theme skin

**File:** `/css/skins/light.css`
**Purpose:** Light theme skin

**File:** `/css/skins/custom.css`
**Purpose:** Custom theme overrides

## Data Flow Patterns

### Player Rendering Flow
```
Page Load → Hook Triggered → Context Check → State Retrieval → 
File Detection → Player Generation → HTML Output
```

### Audio Streaming Flow
```
Play Click → Request Validation → Analytics → File Processing → 
Demo Check → Stream/Redirect
```

### Settings Inheritance
```
Product Settings → Global Settings → Default Constants
```

## Key Architecture Features

1. **Consolidated Player Logic**: All player rendering in `player.php`
2. **Better Organization**: Utilities in dedicated `/utils/` folder
3. **Simplified Access**: Shorter, more intuitive file names
4. **Maintained Compatibility**: Class names unchanged for backward compatibility
5. **Enhanced Modularity**: Clear separation between core and utility functions
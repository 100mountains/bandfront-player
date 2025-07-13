# Bandfront Player State Configuration & Variables Documentation

## State Management Architecture

The plugin uses a centralized state management system (`BFP_Config`) that provides:
- Context-aware inheritance: Product Setting → Global Setting → Default Value
- Bulk retrieval optimization for performance
- Type-safe admin form handling
- Legacy backward compatibility

### State Management Methods

```php
// Single value retrieval with context-aware inheritance
$config->get_state($key, $default, $product_id, $options)

// Bulk retrieval for multiple values (performance optimization)
$config->get_states($keys_array, $product_id)

// Minimal player state for frontend
$config->get_player_state($product_id)

// Admin form settings with type casting
$config->get_admin_form_settings()

// Legacy methods (backward compatibility - DO NOT USE in new code)
$config->get_global_attr($attr, $default)
$config->get_product_attr($product_id, $attr, $default)
```

## Configuration Variables

### Core Player Settings (🎵)
```php
// === OVERRIDABLE SETTINGS (Product-level) ===
_bfp_enable_player            // bool - Enable/disable player
_bfp_audio_engine             // string - Audio engine (mediaelement/wavesurfer)
_bfp_single_player            // int - Use single player for multiple files
_bfp_merge_in_grouped         // int - Merge tracks in grouped products
_bfp_play_all                 // int - Play all tracks sequentially
_bfp_loop                     // int - Loop playback
_bfp_preload                  // string - Audio preload strategy (none/metadata/auto)
_bfp_player_volume            // float - Default volume (0-1)

// === GLOBAL-ONLY SETTINGS ===
_bfp_show_in                  // string - Where to show (all/single/multiple)
_bfp_player_layout            // string - Player skin theme (dark/light/custom)
_bfp_player_controls          // string - Control type (button/all/default)
_bfp_player_title             // int - Show track titles
_bfp_on_cover                 // int - Play button on cover image
_bfp_force_main_player_in_title // int - Force player in product title
_bfp_players_in_cart          // bool - Show players in cart
_bfp_play_simultaneously      // int - Allow simultaneous playback
```

### Demo/Secure Mode (🔒)
```php
// === OVERRIDABLE SETTINGS ===
_bfp_secure_player            // bool - Enable demo/secure mode
_bfp_file_percent             // int - Demo file percentage (0-100)
_bfp_own_demos                // int - Use custom demo files
_bfp_direct_own_demos         // int - Play demo files directly
_bfp_demos_list               // array - List of demo files

// === GLOBAL-ONLY SETTINGS ===
_bfp_fade_out                 // int - Fade out at demo end
_bfp_message                  // string - Custom message for non-purchasers
```

### Access Control (👤)
```php
_bfp_registered_only          // int - Restrict to registered users
_bfp_purchased                // int - Restrict to purchased products
_bfp_reset_purchased_interval // string - Reset interval (daily/never)
_bfp_purchased_times_text     // string - Purchase counter text template
```

### FFmpeg Settings (🎬)
```php
_bfp_ffmpeg                   // int - Enable FFmpeg
_bfp_ffmpeg_path              // string - Path to FFmpeg binary
_bfp_ffmpeg_watermark         // string - Audio watermark file
```

### Troubleshooting (🔧)
```php
_bfp_default_extension        // bool - Force mp3 extension
_bfp_ios_controls             // bool - iOS-specific controls
_bfp_onload                   // bool - Load on page load
_bfp_disable_302              // int - Disable 302 redirects
```

### Analytics (📊)
```php
_bfp_playback_counter_column  // int - Show playback counter in products list
_bfp_analytics_integration    // string - Analytics type (ua/ga4)
_bfp_analytics_property       // string - Analytics property ID
_bfp_analytics_api_secret     // string - GA4 API secret
```

### Advanced Features (⚡)
```php
_bfp_enable_visualizations    // int - Enable waveforms (WaveSurfer only)
_bfp_modules_enabled          // array - Module states
_bfp_apply_to_all_players     // int - Apply settings to all products (admin only)
```

### Cloud Storage (☁️)
```php
_bfp_cloud_active_tab         // string - Active cloud tab
_bfp_cloud_dropbox            // array - Dropbox configuration
    'enabled'                 // bool
    'access_token'            // string
    'folder_path'             // string
_bfp_cloud_s3                 // array - S3 configuration
    'enabled'                 // bool
    'access_key'              // string
    'secret_key'              // string
    'bucket'                  // string
    'region'                  // string
    'path_prefix'             // string
_bfp_cloud_azure              // array - Azure configuration
    'enabled'                 // bool
    'account_name'            // string
    'account_key'             // string
    'container'               // string
    'path_prefix'             // string

// Legacy Google Drive (compatibility)
_bfp_drive                    // int - Store demo files on Google Drive
_bfp_drive_key                // string - OAuth Client JSON content
_bfp_drive_api_key            // string - API Key
```

## Class Structure

### Core Classes
```php
BandfrontPlayer               // Main plugin class
BFP_Config                    // State management & configuration
BFP_Player                    // Player rendering & management
BFP_Audio_Engine              // Audio processing & streaming
BFP_WooCommerce              // WooCommerce integration
BFP_Hooks                     // WordPress hooks management
BFP_Admin                     // Admin interface
BFP_Cover_Renderer            // Cover overlay functionality
```

### Utility Classes
```php
BFP_Utils                     // Helper functions
BFP_File_Handler              // File system operations
BFP_Cloud_Tools               // Cloud storage utilities
BFP_Cache                     // Cache management
BFP_Analytics                 // Analytics tracking
BFP_Preview                   // Preview/demo handling
BFP_Updater                   // Auto-update functionality
```

### Widget Classes
```php
BFP_Playlist_Widget           // Playlist widget
```

## Module System

The plugin supports a modular architecture for admin sections:

```php
$admin_modules = array(
    'audio-engine' => array(      // Core module (always loaded)
        'file' => 'audio-engine.php',
        'name' => 'Audio Engine Selector',
        'description' => 'Audio engine settings and options',
    ),
    'cloud-engine' => array(      // Optional module
        'file' => 'cloud-engine.php',
        'name' => 'Cloud Storage Integration',
        'description' => 'Cloud storage settings and configuration',
    ),
);
```

## Constants

```php
BFP_VERSION                   // Plugin version
BFP_PLUGIN_PATH               // Plugin file path
BFP_PLUGIN_BASE_NAME          // Plugin basename
BFP_PLUGIN_URL                // Plugin URL
BFP_WEBSITE_URL               // Current website URL
BFP_REMOTE_TIMEOUT            // Remote request timeout (240 seconds)
```

## Hooks & Filters

### Actions
```php
bfp_before_player_shop_page   // Before player on shop pages
bfp_after_player_shop_page    // After player on shop pages
bfp_before_players_product_page // Before players on product page
bfp_after_players_product_page // After players on product page
bfp_play_file                 // When file is played
bfp_save_setting              // After settings saved
bfp_delete_post               // After post deletion
bfp_delete_file               // After file deletion
bfp_truncated_file            // After file truncated
bfp_admin_module_loaded       // After admin module loaded
bfp_admin_modules_loaded      // After all modules loaded
```

### Filters
```php
bfp_state_value               // Filter state value retrieval
bfp_player_state              // Filter player state
bfp_all_settings              // Filter all settings
bfp_audio_tag                 // Filter audio tag HTML
bfp_player_html               // Filter player HTML
bfp_file_name                 // Filter displayed file name
bfp_preload                   // Filter preload value
bfp_is_local                  // Filter local file detection
bfp_ffmpeg_time               // Filter FFmpeg duration
bfp_post_types                // Filter supported post types
bfp_global_attr               // Filter global attribute (deprecated)
```

## JavaScript Global Objects

```javascript
bfp_global_settings = {
    ajaxurl: '',              // WordPress AJAX URL
    audio_engine: '',         // Selected audio engine
    play_simultaneously: 0,   // Allow simultaneous playback
    ios_controls: false,      // iOS native controls
    fade_out: 0,              // Fade out enabled
    on_cover: 0,              // Play button on cover
    enable_visualizations: 0, // Waveform visualizations
    player_skin: ''           // Player skin name
};

bfp_players = [];             // Array of player instances
bfp_player_counter = 0;       // Player instance counter
```

## Database Storage

### Options Table
```php
'bfp_global_settings'         // All global settings array
'_bfp_cloud_drive_addon'      // Legacy Google Drive settings
'_bfp_drive_api_key'          // Google Drive API key
```

### Post Meta
```php
// Product-specific overrides
'_bfp_enable_player'          // Enable player for product
'_bfp_audio_engine'           // Product audio engine override
'_bfp_merge_in_grouped'       // Merge grouped products
'_bfp_single_player'          // Single player mode
'_bfp_preload'                // Preload strategy
'_bfp_play_all'               // Play all tracks
'_bfp_loop'                   // Loop playback
'_bfp_player_volume'          // Default volume
'_bfp_secure_player'          // Demo mode enabled
'_bfp_file_percent'           // Demo percentage
'_bfp_own_demos'              // Use custom demos
'_bfp_direct_own_demos'       // Play demos directly
'_bfp_demos_list'             // Demo files list
'_bfp_playback_counter'       // Playback count
'_bfp_drive_files'            // Google Drive file mapping
```

## State Management Compliance Status

### ✅ Fully Compliant Files
- state-manager.php
- player.php
- audio.php
- admin.php
- cover-renderer.php
- hooks.php
- utils/files.php
- utils/analytics.php
- utils/cloud.php
- utils/cache.php
- utils/utils.php
- utils/update.php
- utils/preview.php
- modules/audio-engine.php
- modules/cloud-engine.php

### ⚠️ Files Using State Manager via Main Plugin
- woocommerce.php (after updates)
- views/global-admin-options.php (after updates)
- views/product-options.php (after updates)
- js/engine.js (uses localized data)
- js/admin.js (uses localized data)

### 🚫 Never Access Settings Directly
Always use one of these methods:
- `$config->get_state()` - For single values
- `$config->get_states()` - For multiple values
- `$config->get_player_state()` - For player initialization
- `$config->get_admin_form_settings()` - For admin forms
player_skin              // Selected skin theme
```

### AJAX Localization (bfp_ajax)
```javascript
saving_text              // "Saving settings..."
error_text               // "An unexpected error occurred..."
dismiss_text             // "Dismiss this notice"
```

### Admin Localization (bfp)
```javascript
'File Name'              // Translation string
'Choose file'            // Translation string
'Delete'                 // Translation string
'Select audio file'      // Translation string
'Select Item'            // Translation string
```

### Player Data Attributes
```html
data-product-id          // Product ID
data-file-index          // File index in product
data-controls            // Control type override
data-duration            // Track duration
data-volume              // Initial volume
data-loop                // Loop enabled
data-player-id           // Unique player ID
data-download-links      // Download links JSON
```

### CSS Classes
```css
.bfp-player              // Base player class
.bfp-player-container    // Player container
.bfp-player-title        // Track title
.bfp-player-list         // Multiple tracks table
.bfp-single-player       // Single player mode
.bfp-first-player        // First player in list
.bfp-odd-row             // Odd table row
.bfp-even-row            // Even table row
.bfp-column-player-{skin} // Player column with skin
.bfp-file-duration       // Duration display
.bfp-message             // Message display
.bfp-play-on-cover       // Cover overlay button
.bfp-hidden-player-container // Hidden player container
.bfp-playback-counter    // Playback counter display
.merge_in_grouped_products // Grouped products class
```

## Variable Naming Conventions

1. **Private Properties**: Prefixed with underscore (e.g., `$_config`)
2. **Meta Keys**: Prefixed with `_bfp_` (e.g., `_bfp_enable_player`)
3. **Constants**: All uppercase with BFP prefix (e.g., `BFP_VERSION`)
4. **Hooks**: Lowercase with bfp prefix (e.g., `bfp_save_setting`)
5. **CSS Classes**: Lowercase with bfp prefix and hyphens (e.g., `bfp-player-container`)
6. **JavaScript**: camelCase for properties (e.g., `playSimultaneously`)

## Variable Scope and Context

### Global Scope
- Plugin constants
- Global settings (stored in options table)
- Hook names

### Product Scope
- Product meta values
- Product-specific overrides
- Playback counters

### Request Scope
- Form data (`$_REQUEST`, `$_POST`)
- URL parameters (`$_GET`)
- File uploads (`$_FILES`)

### Session/Transient Scope
- Cached values
- Temporary flags
- Admin notices

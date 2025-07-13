# Bandfront Player - Configuration & Variables Reference

## State Configuration

### Global-Only Settings
These settings apply site-wide and cannot be overridden per product:

| Setting Key | Type | Default | Description |
|------------|------|---------|-------------|
| `_bfp_show_in` | string | 'all' | Where to show players: 'all', 'single', 'multiple' |
| `_bfp_player_layout` | string | 'dark' | Player skin: 'dark', 'light', 'custom' |
| `_bfp_player_controls` | string | 'default' | Control type: 'default', 'button', 'all' |
| `_bfp_player_title` | int | 1 | Show title in player |
| `_bfp_on_cover` | int | 1 | Enable cover overlay buttons |
| `_bfp_force_main_player_in_title` | int | 1 | Force player in product title |
| `_bfp_players_in_cart` | bool | false | Show players in cart |
| `_bfp_play_simultaneously` | int | 0 | Allow multiple players playing |
| `_bfp_registered_only` | int | 0 | Restrict to logged-in users |
| `_bfp_purchased` | int | 0 | Restrict to purchasers only |
| `_bfp_reset_purchased_interval` | string | 'daily' | Demo cleanup: 'daily' or 'never' |
| `_bfp_fade_out` | int | 0 | Enable fade out effect |
| `_bfp_purchased_times_text` | string | '- purchased %d time(s)' | Purchase count text |
| `_bfp_message` | string | '' | Global message HTML |
| `_bfp_ffmpeg` | int | 0 | Enable FFmpeg processing |
| `_bfp_ffmpeg_path` | string | '' | Path to FFmpeg binary |
| `_bfp_ffmpeg_watermark` | string | '' | Audio watermark file URL |
| `_bfp_onload` | bool | false | Troubleshooting: onload event |
| `_bfp_playback_counter_column` | int | 1 | Show playback counter in admin |
| `_bfp_analytics_integration` | string | 'ua' | Analytics type: 'ua' or 'ga4' |
| `_bfp_analytics_property` | string | '' | Analytics property ID |
| `_bfp_analytics_api_secret` | string | '' | GA4 API secret |
| `_bfp_enable_visualizations` | int | 0 | Enable WaveSurfer visualizations |
| `_bfp_modules_enabled` | array | ['audio-engine' => true, 'cloud-engine' => true] | Enabled modules |

### Product-Overridable Settings
These settings can be customized per product:

| Setting Key | Type | Default | Description |
|------------|------|---------|-------------|
| `_bfp_enable_player` | bool | false | Enable player for product |
| `_bfp_audio_engine` | string | 'mediaelement' | Audio engine: 'mediaelement', 'wavesurfer', 'global' |
| `_bfp_single_player` | int | 0 | Merge files into single player |
| `_bfp_merge_in_grouped` | int | 0 | Merge grouped product files |
| `_bfp_play_all` | int | 0 | Auto-play next track |
| `_bfp_loop` | int | 0 | Loop playback |
| `_bfp_preload` | string | 'none' | Preload: 'none', 'metadata', 'auto' |
| `_bfp_player_volume` | float | 1.0 | Default volume (0.0-1.0) |
| `_bfp_secure_player` | bool | false | Enable file truncation |
| `_bfp_file_percent` | int | 50 | Demo percentage (0-100) |
| `_bfp_own_demos` | int | 0 | Use custom demo files |
| `_bfp_direct_own_demos` | int | 0 | Direct link to demos |
| `_bfp_demos_list` | array | [] | Custom demo file list |

### Cloud Storage Settings

#### Dropbox Configuration
```php
'_bfp_cloud_dropbox' => array(
    'enabled' => false,
    'access_token' => '',
    'folder_path' => '/bandfront-demos'
)
```

#### AWS S3 Configuration
```php
'_bfp_cloud_s3' => array(
    'enabled' => false,
    'access_key' => '',
    'secret_key' => '',
    'bucket' => '',
    'region' => 'us-east-1',
    'path_prefix' => 'bandfront-demos/'
)
```

#### Azure Blob Configuration
```php
'_bfp_cloud_azure' => array(
    'enabled' => false,
    'account_name' => '',
    'account_key' => '',
    'container' => '',
    'path_prefix' => 'bandfront-demos/'
)
```

## Constants

| Constant | Value | Description |
|----------|-------|-------------|
| `BFP_VERSION` | '0.1' | Plugin version |
| `BFP_PLUGIN_PATH` | Plugin file path | Main plugin file location |
| `BFP_PLUGIN_BASE_NAME` | Plugin basename | For activation hooks |
| `BFP_PLUGIN_URL` | Plugin URL | Base URL for assets |
| `BFP_WEBSITE_URL` | Current page URL | For generating play URLs |
| `BFP_REMOTE_TIMEOUT` | 240 | Remote request timeout (seconds) |

## JavaScript Global Variables

### bfp_global_settings
Localized to `engine.js`:
```javascript
{
    ajaxurl: '',              // WordPress AJAX URL
    audio_engine: '',         // Selected engine
    play_simultaneously: '',  // Multiple player setting
    fade_out: '',            // Fade effect setting
    on_cover: ''             // Cover overlay setting
}
```

### bfpwl
Localized to playlist widget:
```javascript
{
    ajaxurl: '',             // WordPress AJAX URL
    continue_playing: ''     // Continue playing setting
}
```

## Data Structures

### Audio File Structure
```php
array(
    'name' => 'Track Name',
    'file' => 'https://example.com/audio.mp3',
    'play_src' => false  // Optional: bypass processing
)
```

### Player Arguments
```php
array(
    'product_id' => 123,
    'player_controls' => 'default',
    'player_style' => 'dark',
    'media_type' => 'mp3',
    'id' => 0,  // File index
    'duration' => '3:45',
    'preload' => 'none',
    'volume' => 1.0
)
```

### Playlist Shortcode Attributes
```php
array(
    'title' => '',
    'products_ids' => '',
    'product_categories' => '',
    'product_tags' => '',
    'purchased_products' => 0,
    'highlight_current_product' => 0,
    'continue_playing' => 0,
    'player_style' => 'dark',
    'controls' => 'all',
    'layout' => 'new',
    'cover' => 0,
    'volume' => 1,
    'purchased_only' => 0,
    'hide_purchase_buttons' => 0,
    'class' => '',
    'loop' => 0,
    'purchased_times' => 0,
    'download_links' => 0
)
```

## Module Configuration

### Core Modules
```php
'audio-engine' => array(
    'file' => 'audio-engine.php',
    'name' => 'Audio Engine Selector',
    'description' => 'Audio engine settings and options',
    'always_enabled' => true
)
```

### Optional Modules
```php
'cloud-engine' => array(
    'file' => 'cloud-engine.php',
    'name' => 'Cloud Storage Integration',
    'description' => 'Cloud storage settings and configuration',
    'settings_section' => 'bfp_cloud_storage'
)
```

## Filter Hooks

| Filter | Parameters | Description |
|--------|------------|-------------|
| `bfp_state_value` | $value, $key, $product_id, $source | Modify state values |
| `bfp_player_html` | $html, $audio_url, $args | Modify player HTML |
| `bfp_all_settings` | $settings, $product_id | Filter all settings |
| `bfp_post_types` | $post_types | Supported post types |
| `bfp_preload` | $preload, $audio_url | Modify preload behavior |
| `bfp_is_local` | $file_path, $url | Local file detection |
| `bfp_ffmpeg_time` | $seconds | Adjust demo duration |
| `bfp_purchased_product` | $purchased, $product_id | Override purchase check |

## Action Hooks

| Action | Parameters | Description |
|--------|------------|-------------|
| `bfp_play_file` | $product_id, $url | File played |
| `bfp_truncated_file` | $product_id, $url, $path | Demo created |
| `bfp_delete_file` | $product_id, $url | File deleted |
| `bfp_delete_post` | $post_id | Post cleaned up |
| `bfp_save_setting` | none | Settings saved |
| `bfp_admin_module_loaded` | $module_id | Module loaded |
| `bfp_admin_modules_loaded` | none | All modules loaded |

## State Access Examples

### Single Value
```php
$engine = $this->main_plugin->get_config()->get_state('_bfp_audio_engine', 'mediaelement', $product_id);
```

### Multiple Values
```php
$settings = $this->main_plugin->get_config()->get_states(array(
    '_bfp_preload',
    '_bfp_player_layout',
    '_bfp_player_volume'
), $product_id);
```

### Module Check
```php
if ($this->main_plugin->get_config()->is_module_enabled('cloud-engine')) {
    // Cloud features available
}
```

## Important Notes

1. **State Inheritance**: Product settings override globals, globals override defaults
2. **Context Matters**: Some settings only apply in specific contexts
3. **Performance**: Always use bulk fetch when getting multiple values
4. **Security**: All values are sanitized on save and escaped on output
5. **Extensibility**: Use filters and actions for customization

For class information and methods, see `STATE-MANAGEMENT.md`.
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

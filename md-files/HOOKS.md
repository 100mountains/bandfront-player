# Bandfront Player - WordPress Hooks Documentation

## Overview
This document provides comprehensive documentation of all WordPress hooks (actions and filters) used by the Bandfront Player plugin, including their purpose, parameters, timing, and usage examples.

## Table of Contents
1. [Core Plugin Hooks](#core-plugin-hooks)
2. [Dynamic Player Hooks](#dynamic-player-hooks)
3. [Cover Overlay Hooks](#cover-overlay-hooks)
4. [Conditional Hooks](#conditional-hooks)
5. [Filter Hooks](#filter-hooks)
6. [WooCommerce Integration Hooks](#woocommerce-integration-hooks)
7. [Third-Party Plugin Hooks](#third-party-plugin-hooks)
8. [Custom Action Hooks](#custom-action-hooks)
9. [Custom Filter Hooks](#custom-filter-hooks)
10. [Hook Registration Logic](#hook-registration-logic)
11. [Developer Guidelines](#developer-guidelines)

## Core Plugin Hooks

### Lifecycle Hooks

#### `register_activation_hook`
```php
register_activation_hook( BFP_PLUGIN_PATH, array( &$this->main_plugin, 'activation' ) )
```
- **Type:** Action
- **When:** Plugin activation
- **Purpose:** Initialize plugin on activation
- **Actions Performed:**
  - Create upload directories (`/uploads/bfp/`)
  - Set default settings
  - Initialize database values
  - Schedule cron jobs

#### `register_deactivation_hook`
```php
register_deactivation_hook( BFP_PLUGIN_PATH, array( &$this->main_plugin, 'deactivation' ) )
```
- **Type:** Action
- **When:** Plugin deactivation
- **Purpose:** Cleanup on deactivation
- **Actions Performed:**
  - Delete purchased demo files
  - Clear scheduled cron jobs
  - Clean up transients

#### `plugins_loaded`
```php
add_action( 'plugins_loaded', array( &$this->main_plugin, 'plugins_loaded' ) )
```
- **Type:** Action
- **Priority:** 10 (default)
- **When:** All plugins are loaded
- **Purpose:** Load plugin textdomain for translations
- **Example:**
```php
function plugins_loaded() {
    load_plugin_textdomain('bandfront-player', false, dirname(plugin_basename(BFP_PLUGIN_PATH)) . '/languages/');
}
```

#### `init`
```php
add_action( 'init', array( &$this->main_plugin, 'init' ) )
```
- **Type:** Action
- **Priority:** 10 (default)
- **When:** WordPress initialization
- **Purpose:** Initialize plugin components and register shortcodes
- **Actions Performed:**
  - Register `[bfp-playlist]` shortcode
  - Initialize components
  - Handle preview requests
  - Set up cron schedules

## Dynamic Player Hooks

### Context-Aware Player Insertion

#### `wp`
```php
add_action( 'wp', array( $this, 'register_dynamic_hooks' ) )
```
- **Type:** Action
- **Priority:** 10 (default)
- **When:** WordPress environment is set up
- **Purpose:** Dynamically register player hooks based on page context
- **Logic Flow:**
```php
function register_dynamic_hooks() {
    // Get context-aware configuration
    $hooks_config = $this->get_hooks_config();
    
    // Register hooks based on current page type
    if (is_product()) {
        // Single product page hooks
    } else if (is_shop() || is_product_category()) {
        // Archive page hooks
    }
}
```

### Main Player Hook (Shop/Archive Pages)

#### `woocommerce_after_shop_loop_item_title`
```php
add_action( 'woocommerce_after_shop_loop_item_title', array($player, 'include_main_player'), 1 )
```
- **Type:** Action
- **Priority:** 1 (very early)
- **When:** After product title on shop pages
- **Purpose:** Add single player with button controls
- **Conditions:** 
  - Only when `on_cover` is disabled
  - Only on shop/archive pages
- **Output Example:**
```html
<div class="bfp-player-container">
    <audio class="bfp-player" data-controls="track">...</audio>
</div>
```

### All Players Hook (Product Pages)

#### `woocommerce_single_product_summary`
```php
add_action( 'woocommerce_single_product_summary', array($player, 'include_all_players'), 25 )
```
- **Type:** Action
- **Priority:** 25 (after price at 10, before add to cart at 30)
- **When:** In product summary on single product pages
- **Purpose:** Add all players with full controls
- **Output:** Single player or table of players based on file count

## Cover Overlay Hooks

### Assets and Rendering

#### `wp_enqueue_scripts`
```php
add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_on_cover_assets' ) )
```
- **Type:** Action
- **Priority:** 10 (default)
- **When:** Frontend scripts/styles enqueue
- **Purpose:** Load CSS for play button overlays
- **Conditions:** Only when `on_cover` is enabled

#### `woocommerce_before_shop_loop_item_title`
```php
add_action( 'woocommerce_before_shop_loop_item_title', array( $this, 'add_play_button_on_cover' ), 20 )
```
- **Type:** Action
- **Priority:** 20 (before product title)
- **When:** Before product title in shop loop
- **Purpose:** Add play button overlay on product cover images
- **Output:**
```html
<div class="bfp-play-on-cover" data-product-id="123">
    <svg>...</svg>
</div>
<div class="bfp-hidden-player-container" style="display:none;">
    <!-- Hidden player for JavaScript interaction -->
</div>
```

## Conditional Hooks

### WooCommerce Product Title Filter

#### `woocommerce_product_title`
```php
add_filter( 'woocommerce_product_title', array( $woocommerce, 'woocommerce_product_title' ), 10, 2 )
```
- **Type:** Filter
- **Priority:** 10 (default)
- **Parameters:** `$title`, `$product`
- **When:** Product title is displayed
- **Purpose:** Add player to product title
- **Conditions:** 
  - Only when `force_main_player_in_title` is enabled
  - Only when `on_cover` is disabled
- **Example:**
```php
function woocommerce_product_title($title, $product) {
    if ($this->should_add_player_to_title($product)) {
        return $this->get_player_html($product) . $title;
    }
    return $title;
}
```

## Filter Hooks

### Audio Processing

#### `bfp_preload`
```php
add_filter( 'bfp_preload', array( $this->main_plugin->get_audio_core(), 'preload' ), 10, 2 )
```
- **Type:** Filter
- **Priority:** 10 (default)
- **Parameters:** `$preload`, `$audio_url`
- **Purpose:** Handle audio preload functionality and optimization
- **Returns:** Modified preload value ('none', 'metadata', 'auto')
- **Usage:**
```php
function preload($preload, $audio_url) {
    // Prevent preloading demo files to save bandwidth
    if (strpos($audio_url, 'bfp-action=play') !== false) {
        return 'none';
    }
    return $preload;
}
```

## WooCommerce Integration Hooks

### Product Data Export/Import

#### `woocommerce_product_export_meta_value`
```php
add_filter( 'woocommerce_product_export_meta_value', function( $value, $meta, $product, $row ){
    if (preg_match('/^_bfp_/i', $meta->key) && !is_scalar($value)) {
        $value = serialize($value);
    }
    return $value;
}, 10, 4 )
```
- **Type:** Filter
- **Priority:** 10
- **Parameters:** `$value`, `$meta`, `$product`, `$row`
- **Purpose:** Serialize BFP meta values during product export
- **Targets:** All meta keys starting with '_bfp_'

#### `woocommerce_product_importer_pre_expand_data`
```php
add_filter( 'woocommerce_product_importer_pre_expand_data', function( $data ){
    foreach ($data as $_key => $_value) {
        if (preg_match('/^meta:_bfp_/i', $_key) && is_serialized($_value)) {
            try {
                $data[$_key] = unserialize($_value);
            } catch (Exception $err) {
                $data[$_key] = $_value;
            }
        }
    }
    return $data;
}, 10 )
```
- **Type:** Filter
- **Priority:** 10
- **Parameters:** `$data`
- **Purpose:** Unserialize BFP meta values during product import

## Third-Party Plugin Integration Hooks

### WooCommerce Product Table (Barn2 Plugins)

#### `wc_product_table_data_name`
```php
add_filter( 'wc_product_table_data_name', function( $title, $product ) {
    return (false === stripos($title, '<audio') ? 
           $this->main_plugin->include_main_player($product, false) : '') . $title;
}, 10, 2 )
```
- **Type:** Filter
- **Priority:** 10
- **Parameters:** `$title`, `$product`
- **Purpose:** Add player to product name in product tables
- **Returns:** Player HTML + original title

#### `wc_product_table_before_get_data`
```php
add_action( 'wc_product_table_before_get_data', function( $table ) {
    $GLOBALS['_insert_all_players_BK'] = $this->main_plugin->get_insert_all_players();
    $this->main_plugin->set_insert_all_players(false);
}, 10 )
```
- **Type:** Action
- **Purpose:** Disable all_players before table data generation
- **Note:** Stores previous state in global variable

#### `wc_product_table_after_get_data`
```php
add_action( 'wc_product_table_after_get_data', function( $table ) {
    if (isset($GLOBALS['_insert_all_players_BK'])) {
        $this->main_plugin->set_insert_all_players($GLOBALS['_insert_all_players_BK']);
        unset($GLOBALS['_insert_all_players_BK']);
    }
}, 10 )
```
- **Type:** Action
- **Purpose:** Restore all_players state after table generation

#### `pre_do_shortcode_tag`
```php
add_filter( 'pre_do_shortcode_tag', function( $output, $tag, $attr, $m ){
    if(strtolower($tag) == 'product_table') {
        $this->main_plugin->enqueue_resources();
    }
    return $output;
}, 10, 4 )
```
- **Type:** Filter
- **Parameters:** `$output`, `$tag`, `$attr`, `$m`
- **Purpose:** Enqueue player resources when product_table shortcode is used

### LiteSpeed Cache Integration

#### `litespeed_optimize_js_excludes`
```php
add_filter( 'litespeed_optimize_js_excludes', function( $p ){
    $p[] = 'jquery.js';
    $p[] = 'jquery.min.js';
    $p[] = '/mediaelement/';
    $p[] = plugin_dir_url(BFP_PLUGIN_PATH) . 'js/engine.js';
    $p[] = '/wavesurfer.js';
    return $p;
} )
```
- **Type:** Filter
- **Purpose:** Exclude player scripts from LiteSpeed optimization
- **Excludes:** jQuery, MediaElement, engine.js, WaveSurfer

#### `litespeed_optm_js_defer_exc`
```php
add_filter( 'litespeed_optm_js_defer_exc', function( $p ){
    // Same exclusions as above
    return $p;
} )
```
- **Type:** Filter
- **Purpose:** Exclude player scripts from LiteSpeed defer optimization

## Custom Action Hooks

These hooks are fired by the plugin for extensibility by developers.

### Player Events

#### `bfp_before_player_shop_page`
```php
do_action( 'bfp_before_player_shop_page', $product_id )
```
- **When:** Before rendering player on shop pages
- **Parameters:** `$product_id` (int)
- **Usage Example:**
```php
add_action('bfp_before_player_shop_page', function($product_id) {
    echo '<div class="custom-player-wrapper">';
});
```

#### `bfp_after_player_shop_page`
```php
do_action( 'bfp_after_player_shop_page', $product_id )
```
- **When:** After rendering player on shop pages
- **Parameters:** `$product_id` (int)

#### `bfp_before_players_product_page`
```php
do_action( 'bfp_before_players_product_page', $product_id )
```
- **When:** Before rendering all players on product pages
- **Parameters:** `$product_id` (int)

#### `bfp_after_players_product_page`
```php
do_action( 'bfp_after_players_product_page', $product_id )
```
- **When:** After rendering all players on product pages
- **Parameters:** `$product_id` (int)

### File Events

#### `bfp_play_file`
```php
do_action( 'bfp_play_file', $product_id, $file_url )
```
- **When:** When a file is played
- **Parameters:** 
  - `$product_id` (int)
  - `$file_url` (string)
- **Purpose:** Track playback for analytics
- **Usage Example:**
```php
add_action('bfp_play_file', function($product_id, $file_url) {
    // Custom analytics tracking
    my_custom_analytics_track('play', $product_id, $file_url);
}, 10, 2);
```

#### `bfp_truncated_file`
```php
do_action( 'bfp_truncated_file', $product_id, $file_url, $file_path )
```
- **When:** After a demo file is created
- **Parameters:**
  - `$product_id` (int)
  - `$file_url` (string) - Original file URL
  - `$file_path` (string) - Demo file path

#### `bfp_delete_file`
```php
do_action( 'bfp_delete_file', $product_id, $file_url )
```
- **When:** When a file is deleted
- **Parameters:**
  - `$product_id` (int)
  - `$file_url` (string)

#### `bfp_delete_post`
```php
do_action( 'bfp_delete_post', $product_id )
```
- **When:** After post deletion cleanup
- **Parameters:** `$product_id` (int)

### Settings Events

#### `bfp_save_setting`
```php
do_action( 'bfp_save_setting' )
```
- **When:** After global settings are saved
- **Purpose:** Clear caches, update dependent settings
- **Usage Example:**
```php
add_action('bfp_save_setting', function() {
    // Clear custom caches
    wp_cache_delete('my_custom_cache_key');
});
```

#### `bfp_admin_module_loaded`
```php
do_action( 'bfp_admin_module_loaded', $module_id )
```
- **When:** After each admin module is loaded
- **Parameters:** `$module_id` (string) - Module identifier

#### `bfp_admin_modules_loaded`
```php
do_action( 'bfp_admin_modules_loaded' )
```
- **When:** After all admin modules are loaded
- **Purpose:** Initialize module-dependent features

## Custom Filter Hooks

These filters allow modification of plugin behavior by developers.

### Content Modification

#### `bfp_player_html`
```php
apply_filters( 'bfp_player_html', $player_html, $audio_url, $args )
```
- **Parameters:**
  - `$player_html` (string) - Generated HTML
  - `$audio_url` (string) - Audio file URL
  - `$args` (array) - Player arguments
- **Returns:** Modified player HTML
- **Usage Example:**
```php
add_filter('bfp_player_html', function($html, $url, $args) {
    // Add custom wrapper
    return '<div class="my-player-wrapper">' . $html . '</div>';
}, 10, 3);
```

#### `bfp_audio_tag`
```php
apply_filters( 'bfp_audio_tag', $audio_tag, $product_id, $index, $audio_url )
```
- **Parameters:**
  - `$audio_tag` (string) - Audio tag HTML
  - `$product_id` (int)
  - `$index` (int) - File index
  - `$audio_url` (string)
- **Returns:** Modified audio tag HTML

#### `bfp_file_name`
```php
apply_filters( 'bfp_file_name', $file_name, $product_id, $index )
```
- **Parameters:**
  - `$file_name` (string) - Display name
  - `$product_id` (int)
  - `$index` (int) - File index
- **Returns:** Modified file name

### State Management

#### `bfp_state_value`
```php
apply_filters( 'bfp_state_value', $value, $key, $product_id, 'product' )
```
- **Parameters:**
  - `$value` (mixed) - Setting value
  - `$key` (string) - Setting key
  - `$product_id` (int|null)
  - `$context` (string) - 'product' or 'global'
- **Returns:** Modified setting value
- **Purpose:** Modify state values during retrieval

#### `bfp_player_state`
```php
apply_filters( 'bfp_player_state', $player_state, $product_id )
```
- **Parameters:**
  - `$player_state` (array) - Player configuration
  - `$product_id` (int|null)
- **Returns:** Modified player state

#### `bfp_all_settings`
```php
apply_filters( 'bfp_all_settings', $settings, $product_id )
```
- **Parameters:**
  - `$settings` (array) - All settings
  - `$product_id` (int|null)
- **Returns:** Modified settings array

### Technical Filters

#### `bfp_preload`
```php
apply_filters( 'bfp_preload', $preload, $audio_url )
```
- **Parameters:**
  - `$preload` (string) - Preload strategy
  - `$audio_url` (string)
- **Returns:** Modified preload value

#### `bfp_is_local`
```php
apply_filters( 'bfp_is_local', $file_path, $url )
```
- **Parameters:**
  - `$file_path` (string|false) - Local path or false
  - `$url` (string) - Original URL
- **Returns:** Modified file path

#### `bfp_ffmpeg_time`
```php
apply_filters( 'bfp_ffmpeg_time', $duration )
```
- **Parameters:** `$duration` (int) - Seconds
- **Returns:** Modified duration
- **Purpose:** Adjust FFmpeg processing time

#### `bfp_post_types`
```php
apply_filters( 'bfp_post_types', $post_types )
```
- **Parameters:** `$post_types` (array)
- **Returns:** Modified post types array
- **Default:** `['product', 'product_variation']`

## Hook Registration Logic

### Context Detection

The plugin uses intelligent context detection to register hooks appropriately:

```php
public function get_hooks_config() {
    $hooks_config = array(
        'main_player' => array(),
        'all_players' => array()
    );
    
    // Product page context
    if (function_exists('is_product') && is_product()) {
        $hooks_config['all_players'] = array(
            'woocommerce_single_product_summary' => 25
        );
    } 
    // Shop page context
    else {
        $on_cover = $this->main_plugin->get_config()->get_state('_bfp_on_cover');
        if (!$on_cover) {
            $hooks_config['main_player'] = array(
                'woocommerce_after_shop_loop_item_title' => 1
            );
        }
    }
    
    return $hooks_config;
}
```

### Hook Priorities

| Hook | Priority | Purpose | Location |
|------|----------|---------|----------|
| `woocommerce_before_shop_loop_item_title` | 20 | Cover overlay | Before title |
| `woocommerce_after_shop_loop_item_title` | 1 | Main player | After title |
| `woocommerce_single_product_summary` | 25 | All players | After price (10) |
| `woocommerce_product_title` | 10 | Player in title | Title filter |

## Developer Guidelines

### Adding Custom Hooks

#### Before/After Player Output
```php
// Wrap player output
add_action('bfp_before_player_shop_page', 'my_before_player', 10, 1);
add_action('bfp_after_player_shop_page', 'my_after_player', 10, 1);

function my_before_player($product_id) {
    echo '<div class="my-custom-wrapper">';
}

function my_after_player($product_id) {
    echo '</div>';
}
```

#### Modify Player HTML
```php
add_filter('bfp_player_html', 'customize_player_html', 10, 3);

function customize_player_html($html, $audio_url, $args) {
    // Add custom attributes
    $html = str_replace('<audio', '<audio data-custom="value"', $html);
    return $html;
}
```

#### Track Custom Events
```php
add_action('bfp_play_file', 'track_custom_event', 10, 2);

function track_custom_event($product_id, $file_url) {
    // Send to custom analytics
    if (function_exists('gtag')) {
        ?>
        <script>
        gtag('event', 'audio_play', {
            'product_id': <?php echo $product_id; ?>,
            'file_url': '<?php echo esc_js($file_url); ?>'
        });
        </script>
        <?php
    }
}
```

### Hook Conflicts

#### Preventing Duplicate Players
```php
// Check if player already rendered
if (has_action('woocommerce_after_shop_loop_item_title', array($player, 'include_main_player'))) {
    // Player already hooked
    return;
}
```

#### Safe Hook Removal
```php
// Remove with exact priority
remove_action('woocommerce_after_shop_loop_item_title', 
              array($GLOBALS['BandfrontPlayer']->get_player(), 'include_main_player'), 
              1);
```

### Performance Considerations

#### Conditional Hook Registration
```php
// Only register resource-intensive hooks when needed
if ($this->has_audio_products()) {
    add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
}
```

#### Early Returns
```php
add_action('some_hook', function() {
    if (!$this->should_run()) {
        return; // Exit early
    }
    // Expensive operations
});
```

## Debugging Hooks

### List Registered Hooks
```php
// Debug which BFP hooks are registered
add_action('wp', function() {
    global $wp_filter;
    
    foreach ($wp_filter as $hook_name => $hook_obj) {
        foreach ($hook_obj->callbacks as $priority => $callbacks) {
            foreach ($callbacks as $callback) {
                if (is_array($callback['function']) && 
                    is_object($callback['function'][0]) && 
                    strpos(get_class($callback['function'][0]), 'BFP') === 0) {
                    error_log("BFP Hook: $hook_name @ priority $priority");
                }
            }
        }
    }
}, 999);
```

### Trace Hook Execution
```php
// Trace when players are rendered
add_action('bfp_before_player_shop_page', function($id) {
    error_log('Rendering player for product: ' . $id);
});
```

### Check Hook Context
```php
// Verify context detection
add_action('wp', function() {
    if (is_shop()) {
        error_log('Shop page detected');
    } elseif (is_product()) {
        error_log('Product page detected');
    }
});
```

## Common Issues and Solutions

### Players Not Showing
1. Check WooCommerce is active: `if (class_exists('WooCommerce'))`
2. Verify products have audio files
3. Check player is enabled in settings
4. Verify theme hasn't removed WooCommerce hooks

### Duplicate Players
1. Check theme isn't manually calling player methods
2. Verify context detection is working
3. Check for conflicting plugins

### Wrong Player Type
1. Verify context detection (shop vs product page)
2. Check `on_cover` setting
3. Review hook priorities

### Performance Issues
1. Use hook conditions to prevent unnecessary registration
2. Cache expensive operations
3. Use appropriate hook priorities

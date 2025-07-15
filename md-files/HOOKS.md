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
register_activation_hook(BFP_PLUGIN_PATH, [$this->mainPlugin, 'activation'])
```
- **Type:** Action
- **When:** Plugin activation
- **Purpose:** Initialize plugin on activation
- **Actions Performed:**
  - Create upload directories (`/uploads/bfp/`)
  - Set default settings
  - Initialize database values

#### `register_deactivation_hook`
```php
register_deactivation_hook(BFP_PLUGIN_PATH, [$this->mainPlugin, 'deactivation'])
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
add_action('plugins_loaded', [$this->mainPlugin, 'pluginsLoaded'])
```
- **Type:** Action
- **Priority:** 10 (default)
- **When:** All plugins are loaded
- **Purpose:** Load plugin textdomain for translations
- **Implementation:**
```php
public function pluginsLoaded(): void {
    load_plugin_textdomain('bandfront-player', false, dirname(plugin_basename(__FILE__)) . '/languages');
}
```

#### `init`
```php
add_action('init', [$this->mainPlugin, 'init'])
```
- **Type:** Action
- **Priority:** 10 (default)
- **When:** WordPress initialization
- **Purpose:** Initialize plugin components and register shortcodes
- **Actions Performed:**
  - Register `[bfp-playlist]` shortcode
  - Initialize components
  - Handle preview requests

## Dynamic Player Hooks

### Context-Aware Hook Registration

#### `wp`
```php
add_action('wp', [$this, 'registerDynamicHooks'])
```
- **Type:** Action
- **Priority:** 10 (default)
- **When:** WordPress environment is set up
- **Purpose:** Dynamically register player hooks based on page context
- **Logic Flow:**
```php
public function registerDynamicHooks(): void {
    $hooksConfig = $this->getHooksConfig();
    
    // Register main player hooks (shop/archive pages)
    foreach ($hooksConfig['main_player'] as $hook => $priority) {
        add_action($hook, [$this->mainPlugin->getPlayer(), 'includeMainPlayer'], $priority);
    }
    
    // Register all players hooks (product pages)
    foreach ($hooksConfig['all_players'] as $hook => $priority) {
        add_action($hook, [$this->mainPlugin->getPlayer(), 'includeAllPlayers'], $priority);
    }
}
```

### Context Detection Logic

The hook configuration is determined by page context:

```php
public function getHooksConfig(): array {
    $hooksConfig = [
        'main_player' => [],
        'all_players' => []
    ];
    
    $isProduct = function_exists('is_product') && is_product();
    $isShop = function_exists('is_shop') && is_shop();
    $isArchive = function_exists('is_product_category') && is_product_category();
    
    // Only add all_players hooks on single product pages
    if ($isProduct) {
        $hooksConfig['all_players'] = [
            'woocommerce_single_product_summary' => 25,
        ];
    } else {
        // On shop/archive pages, add main player hooks
        $hooksConfig['main_player'] = [
            'woocommerce_after_shop_loop_item_title' => 1,
        ];
    }
    
    return $hooksConfig;
}
```

### Main Player Hook (Shop/Archive Pages)

#### `woocommerce_after_shop_loop_item_title`
```php
add_action('woocommerce_after_shop_loop_item_title', [$player, 'includeMainPlayer'], 1)
```
- **Type:** Action
- **Priority:** 1 (very early)
- **When:** After product title on shop pages
- **Purpose:** Add single player with button controls
- **Conditions:** 
  - Only when NOT on single product pages
  - Always registered on shop/archive pages
- **Output Example:**
```html
<div class="bfp-player-container">
    <audio class="bfp-player" data-controls="track">...</audio>
</div>
```

### All Players Hook (Product Pages)

#### `woocommerce_single_product_summary`
```php
add_action('woocommerce_single_product_summary', [$player, 'includeAllPlayers'], 25)
```
- **Type:** Action
- **Priority:** 25 (after price at 10, before add to cart at 30)
- **When:** In product summary on single product pages
- **Purpose:** Add all players with full controls
- **Conditions:** Only on single product pages
- **Output:** Single player or table of players based on file count

## Cover Overlay Hooks

### Assets and Rendering

#### `wp_enqueue_scripts`
```php
add_action('wp_enqueue_scripts', [$this, 'enqueueOnCoverAssets'])
```
- **Type:** Action
- **Priority:** 10 (default)
- **When:** Frontend scripts/styles enqueue
- **Purpose:** Load CSS for play button overlays
- **Implementation:**
```php
public function enqueueOnCoverAssets(): void {
    $this->mainPlugin->getRenderer()->enqueueCoverAssets();
}
```

#### `woocommerce_before_shop_loop_item_title`
```php
add_action('woocommerce_before_shop_loop_item_title', [$this, 'addPlayButtonOnCover'], 20)
```
- **Type:** Action
- **Priority:** 20 (before product title)
- **When:** Before product title in shop loop
- **Purpose:** Add play button overlay on product cover images
- **Conditions:** Only when `_bfp_on_cover` setting is enabled
- **Implementation:**
```php
public function addPlayButtonOnCover(): void {
    $onCover = $this->mainPlugin->getConfig()->getState('_bfp_on_cover');
    if ($onCover) {
        $this->mainPlugin->getRenderer()->renderCoverOverlay();
    }
}
```

## Conditional Hooks

### WooCommerce Product Title Filter

#### `init` for Title Filter Registration
```php
add_action('init', [$this, 'conditionallyAddTitleFilter'])
```
- **Type:** Action
- **Priority:** 10 (default)
- **When:** WordPress initialization
- **Purpose:** Conditionally register product title filter
- **Implementation:**
```php
public function conditionallyAddTitleFilter(): void {
    $woocommerce = $this->mainPlugin->getWooCommerce();
    
    // Always add the title filter when WooCommerce integration exists
    if ($woocommerce) {
        add_filter('woocommerce_product_title', [$woocommerce, 'woocommerceProductTitle'], 10, 2);
    }
}
```

#### `woocommerce_product_title`
```php
add_filter('woocommerce_product_title', [$woocommerce, 'woocommerceProductTitle'], 10, 2)
```
- **Type:** Filter
- **Priority:** 10 (default)
- **Parameters:** `$title`, `$product`
- **When:** Product title is displayed
- **Purpose:** Add player to product title when configured
- **Conditions:** 
  - Only when `force_main_player_in_title` is enabled
  - Only when player hasn't been inserted yet

## Filter Hooks

### Audio Processing

#### `bfp_preload`
```php
add_filter('bfp_preload', [$this->mainPlugin->getAudioCore(), 'preload'], 10, 2)
```
- **Type:** Filter
- **Priority:** 10 (default)
- **Parameters:** `$preload`, `$audio_url`
- **Purpose:** Handle audio preload functionality and optimization
- **Returns:** Modified preload value ('none', 'metadata', 'auto')
- **Implementation:**
```php
public function preload(string $preload, string $audioUrl): string {
    if (strpos($audioUrl, 'bfp-action=play') !== false && $this->preloadTimes > 0) {
        return 'none';
    }
    
    if (strpos($audioUrl, 'bfp-action=play') !== false) {
        $this->preloadTimes++;
    }
    
    return $preload;
}
```

## WooCommerce Integration Hooks

### Product Data Export/Import

#### `woocommerce_product_export_meta_value`
```php
add_filter('woocommerce_product_export_meta_value', function($value, $meta, $product, $row) {
    if (preg_match('/^' . preg_quote('_bfp_') . '/i', $meta->key) && !is_scalar($value)) {
        $value = serialize($value);
    }
    return $value;
}, 10, 4)
```
- **Type:** Filter
- **Priority:** 10
- **Parameters:** `$value`, `$meta`, `$product`, `$row`
- **Purpose:** Serialize BFP meta values during product export
- **Targets:** All meta keys starting with '_bfp_'

#### `woocommerce_product_importer_pre_expand_data`
```php
add_filter('woocommerce_product_importer_pre_expand_data', function($data) {
    foreach ($data as $_key => $_value) {
        if (preg_match('/^' . preg_quote('meta:_bfp_') . '/i', $_key) && 
            function_exists('is_serialized') && 
            is_serialized($_value)) {
            try {
                $data[$_key] = unserialize($_value);
            } catch (\Exception $err) {
                $data[$_key] = $_value;
            }
        }
    }
    return $data;
}, 10)
```
- **Type:** Filter
- **Priority:** 10
- **Parameters:** `$data`
- **Purpose:** Unserialize BFP meta values during product import

## Third-Party Plugin Integration Hooks

### WooCommerce Product Table (Barn2 Plugins)

#### `wc_product_table_data_name`
```php
add_filter('wc_product_table_data_name', function($title, $product) {
    return (false === stripos($title, '<audio') ? 
           $this->mainPlugin->includeMainPlayer($product, false) : '') . $title;
}, 10, 2)
```
- **Type:** Filter
- **Priority:** 10
- **Parameters:** `$title`, `$product`
- **Purpose:** Add player to product name in product tables
- **Returns:** Player HTML + original title

#### `wc_product_table_before_get_data`
```php
add_action('wc_product_table_before_get_data', function($table) {
    $GLOBALS['_insert_all_players_BK'] = $this->mainPlugin->getInsertAllPlayers();
    $this->mainPlugin->setInsertAllPlayers(false);
}, 10)
```
- **Type:** Action
- **Purpose:** Disable all_players before table data generation
- **Note:** Stores previous state in global variable

#### `wc_product_table_after_get_data`
```php
add_action('wc_product_table_after_get_data', function($table) {
    if (isset($GLOBALS['_insert_all_players_BK'])) {
        $this->mainPlugin->setInsertAllPlayers($GLOBALS['_insert_all_players_BK']);
        unset($GLOBALS['_insert_all_players_BK']);
    } else {
        $this->mainPlugin->setInsertAllPlayers(true);
    }
}, 10)
```
- **Type:** Action
- **Purpose:** Restore all_players state after table generation

#### `pre_do_shortcode_tag`
```php
add_filter('pre_do_shortcode_tag', function($output, $tag, $attr, $m) {
    if (strtolower($tag) == 'product_table') {
        $this->mainPlugin->enqueueResources();
    }
    return $output;
}, 10, 4)
```
- **Type:** Filter
- **Parameters:** `$output`, `$tag`, `$attr`, `$m`
- **Purpose:** Enqueue player resources when product_table shortcode is used

### LiteSpeed Cache Integration

#### `litespeed_optimize_js_excludes`
```php
add_filter('litespeed_optimize_js_excludes', function($p) {
    $p[] = 'jquery.js';
    $p[] = 'jquery.min.js';
    $p[] = '/mediaelement/';
    $p[] = plugin_dir_url(BFP_PLUGIN_PATH) . 'js/engine.js';
    $p[] = '/wavesurfer.js';
    return $p;
})
```
- **Type:** Filter
- **Purpose:** Exclude player scripts from LiteSpeed optimization
- **Excludes:** jQuery, MediaElement, engine.js, WaveSurfer

#### `litespeed_optm_js_defer_exc`
```php
add_filter('litespeed_optm_js_defer_exc', function($p) {
    $p[] = 'jquery.js';
    $p[] = 'jquery.min.js';
    $p[] = '/mediaelement/';
    $p[] = plugin_dir_url(BFP_PLUGIN_PATH) . 'js/engine.js';
    $p[] = '/wavesurfer.js';
    return $p;
})
```
- **Type:** Filter
- **Purpose:** Exclude player scripts from LiteSpeed defer optimization

## Custom Action Hooks

These hooks are fired by the plugin for extensibility by developers.

### Player Events

#### `bfp_before_player_shop_page`
```php
do_action('bfp_before_player_shop_page', $product_id)
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
do_action('bfp_after_player_shop_page', $product_id)
```
- **When:** After rendering player on shop pages
- **Parameters:** `$product_id` (int)

#### `bfp_before_players_product_page`
```php
do_action('bfp_before_players_product_page', $product_id)
```
- **When:** Before rendering all players on product pages
- **Parameters:** `$product_id` (int)

#### `bfp_after_players_product_page`
```php
do_action('bfp_after_players_product_page', $product_id)
```
- **When:** After rendering all players on product pages
- **Parameters:** `$product_id` (int)

### File Events

#### `bfp_play_file`
```php
do_action('bfp_play_file', $product_id, $file_url)
```
- **When:** When a file is played
- **Parameters:** 
  - `$product_id` (int)
  - `$file_url` (string)
- **Purpose:** Track playback for analytics

#### `bfp_truncated_file`
```php
do_action('bfp_truncated_file', $product_id, $file_url, $file_path)
```
- **When:** After a demo file is created
- **Parameters:**
  - `$product_id` (int)
  - `$file_url` (string) - Original file URL
  - `$file_path` (string) - Demo file path

#### `bfp_delete_file`
```php
do_action('bfp_delete_file', $product_id, $file_url)
```
- **When:** When a file is deleted
- **Parameters:**
  - `$product_id` (int)
  - `$file_url` (string)

#### `bfp_delete_post`
```php
do_action('bfp_delete_post', $product_id)
```
- **When:** After post deletion cleanup
- **Parameters:** `$product_id` (int)

## Custom Filter Hooks

These filters allow modification of plugin behavior by developers.

### Content Modification

#### `bfp_player_html`
```php
apply_filters('bfp_player_html', $player_html, $audio_url, $args)
```
- **Parameters:**
  - `$player_html` (string) - Generated HTML
  - `$audio_url` (string) - Audio file URL
  - `$args` (array) - Player arguments
- **Returns:** Modified player HTML
- **Usage Example:**
```php
add_filter('bfp_player_html', function($html, $url, $args) {
    return '<div class="my-player-wrapper">' . $html . '</div>';
}, 10, 3);
```

#### `bfp_audio_tag`
```php
apply_filters('bfp_audio_tag', $audio_tag, $product_id, $index, $audio_url)
```
- **Parameters:**
  - `$audio_tag` (string) - Audio tag HTML
  - `$product_id` (int)
  - `$index` (int) - File index
  - `$audio_url` (string)
- **Returns:** Modified audio tag HTML

#### `bfp_file_name`
```php
apply_filters('bfp_file_name', $file_name, $product_id, $index)
```
- **Parameters:**
  - `$file_name` (string) - Display name
  - `$product_id` (int)
  - `$index` (int) - File index
- **Returns:** Modified file name

### State Management

#### `bfp_state_value`
```php
apply_filters('bfp_state_value', $value, $key, $product_id, $context)
```
- **Parameters:**
  - `$value` (mixed) - Setting value
  - `$key` (string) - Setting key
  - `$product_id` (int|null)
  - `$context` (string) - 'product' or 'global'
- **Returns:** Modified setting value

#### `bfp_player_state`
```php
apply_filters('bfp_player_state', $player_state, $product_id)
```
- **Parameters:**
  - `$player_state` (array) - Player configuration
  - `$product_id` (int|null)
- **Returns:** Modified player state

#### `bfp_all_settings`
```php
apply_filters('bfp_all_settings', $settings, $product_id)
```
- **Parameters:**
  - `$settings` (array) - All settings
  - `$product_id` (int|null)
- **Returns:** Modified settings array

## Hook Registration Logic

### Current Implementation

The Hooks class uses a centralized registration approach:

```php
class Hooks {
    public function __construct(Plugin $mainPlugin) {
        $this->mainPlugin = $mainPlugin;
        $this->registerHooks();
    }
    
    private function registerHooks(): void {
        // Core lifecycle hooks
        register_activation_hook(BFP_PLUGIN_PATH, [$this->mainPlugin, 'activation']);
        register_deactivation_hook(BFP_PLUGIN_PATH, [$this->mainPlugin, 'deactivation']);
        
        // WordPress hooks
        add_action('plugins_loaded', [$this->mainPlugin, 'pluginsLoaded']);
        add_action('init', [$this->mainPlugin, 'init']);
        
        // Dynamic player hooks
        add_action('wp', [$this, 'registerDynamicHooks']);
        
        // Cover overlay hooks
        add_action('wp_enqueue_scripts', [$this, 'enqueueOnCoverAssets']);
        add_action('woocommerce_before_shop_loop_item_title', [$this, 'addPlayButtonOnCover'], 20);
        
        // Conditional hooks
        add_action('init', [$this, 'conditionallyAddTitleFilter']);
        
        // Third-party integrations
        $this->registerThirdPartyHooks();
    }
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
The current implementation prevents duplicates through context-aware registration:

```php
// Context detection prevents conflicts
$isProduct = function_exists('is_product') && is_product();

if ($isProduct) {
    // Only register all_players hooks
    $hooksConfig['all_players'] = ['woocommerce_single_product_summary' => 25];
} else {
    // Only register main_player hooks
    $hooksConfig['main_player'] = ['woocommerce_after_shop_loop_item_title' => 1];
}
```

#### Safe Hook Removal
```php
// Remove with exact parameters
remove_action('woocommerce_after_shop_loop_item_title', 
              [$GLOBALS['BandfrontPlayer']->getPlayer(), 'includeMainPlayer'], 
              1);
```

### Performance Considerations

#### Conditional Hook Registration
```php
// Only register when needed
public function enqueueOnCoverAssets(): void {
    // Delegates to renderer which checks conditions
    $this->mainPlugin->getRenderer()->enqueueCoverAssets();
}
```

#### Early Returns in Hook Callbacks
```php
public function addPlayButtonOnCover(): void {
    $onCover = $this->mainPlugin->getConfig()->getState('_bfp_on_cover');
    if (!$onCover) {
        return; // Exit early if not needed
    }
    
    // Expensive operations only when needed
    $this->mainPlugin->getRenderer()->renderCoverOverlay();
}
```

## Debugging Hooks

### Console Logging
The current implementation includes extensive console logging:

```php
private function addConsoleLog(string $message, $data = null): void {
    $logData = [
        'timestamp' => current_time('mysql'),
        'message' => $message,
        'class' => 'BFP_Hooks'
    ];
    
    if ($data !== null) {
        $logData['data'] = $data;
    }
    
    echo '<script>console.log("BFP Hooks Debug:", ' . wp_json_encode($logData) . ');</script>';
}
```

### Hook Execution Tracing
```php
// Trace dynamic hook registration
public function registerDynamicHooks(): void {
    $this->addConsoleLog('registerDynamicHooks called');
    
    $hooksConfig = $this->getHooksConfig();
    
    foreach ($hooksConfig['main_player'] as $hook => $priority) {
        $this->addConsoleLog('Adding main player hook', ['hook' => $hook, 'priority' => $priority]);
    }
}
```

## Common Issues and Solutions

### Players Not Showing
1. Check WooCommerce is active: `if (class_exists('WooCommerce'))`
2. Verify products have audio files
3. Check player is enabled in settings
4. Review console logs for hook registration

### Duplicate Players
1. Context-aware registration prevents this in current implementation
2. Check for manual theme calls to player methods
3. Verify no conflicting plugins

### Wrong Player Type
1. Current implementation uses proper context detection
2. Check `is_product()` vs `is_shop()` detection
3. Review hook priorities and registration logic

### Performance Issues
1. Current implementation uses conditional registration
2. Early returns prevent unnecessary processing
3. Console logging helps identify bottlenecks

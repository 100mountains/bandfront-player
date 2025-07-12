# Bandfront Player - State Management System

## Overview

The Bandfront Player uses a centralized state management system (`BFP_Config`) that provides context-aware settings inheritance, performance optimization through caching, and type-safe data handling. All plugin components must retrieve settings through this system rather than directly accessing WordPress options or post meta.

## Core Principles

### 1. **Context-Aware Inheritance**
```
Product Setting → Global Setting → Default Value
```
- Product-level settings override global settings (where allowed)
- Global settings override default values
- Some settings are global-only and cannot be overridden

### 2. **Single Source of Truth**
- All settings access goes through `BFP_Config`
- No direct `get_option()` or `get_post_meta()` calls for plugin settings
- Centralized validation and type casting

### 3. **Performance Optimization**
- Bulk fetching for multiple values
- Request-level caching to prevent duplicate queries
- Lazy loading of settings

## State Manager Access Methods

### Getting the State Manager Instance

```php
// From main plugin instance
$config = $this->main_plugin->get_config();

// From global
$config = $GLOBALS['BandfrontPlayer']->get_config();
```

### Core Methods

#### **get_state() - Single Value Retrieval**

**Purpose:** Retrieve a single setting value with context-aware inheritance

```php
$value = $config->get_state($key, $default = null, $product_id = null, $options = array());
```

**Parameters:**
- `$key` (string): Setting key (e.g., '_bfp_enable_player')
- `$default` (mixed): Default value if setting not found
- `$product_id` (int|null): Product ID for context (null for global)
- `$options` (array): Additional options (reserved for future use)

**Usage Examples:**

```php
// Global setting (no product context)
$player_layout = $config->get_state('_bfp_player_layout', 'dark');

// Product-specific setting with inheritance
$enable_player = $config->get_state('_bfp_enable_player', false, $product_id);

// With explicit default
$volume = $config->get_state('_bfp_player_volume', 1.0, $product_id);
```

**When to Use:**
- Retrieving a single setting value
- When you need inheritance (product → global → default)
- For conditional logic based on settings

#### **get_states() - Bulk Retrieval**

**Purpose:** Retrieve multiple setting values in one operation (performance optimization)

```php
$settings = $config->get_states($keys, $product_id = null);
```

**Parameters:**
- `$keys` (array): Array of setting keys
- `$product_id` (int|null): Product ID for context

**Returns:** Associative array with key => value pairs

**Usage Examples:**

```php
// Get multiple global settings
$settings = $config->get_states(array(
    '_bfp_player_layout',
    '_bfp_player_controls',
    '_bfp_on_cover'
));

// Get multiple product settings with inheritance
$product_settings = $config->get_states(array(
    '_bfp_enable_player',
    '_bfp_audio_engine',
    '_bfp_secure_player',
    '_bfp_file_percent'
), $product_id);

// Destructure results
$player_layout = $settings['_bfp_player_layout'];
$player_controls = $settings['_bfp_player_controls'];
```

**When to Use:**
- Need 2 or more settings at once
- Initializing components with multiple settings
- Building configuration arrays

#### **get_player_state() - Frontend Player State**

**Purpose:** Get minimal settings required for player initialization on frontend

```php
$player_state = $config->get_player_state($product_id = null);
```

**Returns:** Array with essential player settings only

**Usage Example:**

```php
// For player initialization
$state = $config->get_player_state($product_id);
wp_localize_script('bfp-engine', 'bfp_player_' . $product_id, $state);
```

**When to Use:**
- Localizing data for JavaScript
- Minimizing data sent to frontend
- Player initialization

#### **get_admin_form_settings() - Admin Form Data**

**Purpose:** Get all settings with proper type casting for admin forms

```php
$form_settings = $config->get_admin_form_settings();
```

**Returns:** Array with type-cast values suitable for form fields

**Usage Example:**

```php
// In admin views
$settings = $config->get_admin_form_settings();
$checked = $settings['_bfp_enable_player'] ? 'checked' : '';
```

**When to Use:**
- Admin settings pages
- Product meta boxes
- Anywhere form data is displayed

## Implementation Rules by Component

### Player Components (`player.php`)

```php
class BFP_Player {
    public function include_main_player($product = '') {
        // ✅ CORRECT: Use get_state for single values
        $show_in = $this->main_plugin->get_config()->get_state('_bfp_show_in', 'all', $product_id);
        
        // ✅ CORRECT: Use get_states for multiple values
        $settings = $this->main_plugin->get_config()->get_states(array(
            '_bfp_preload',
            '_bfp_player_layout',
            '_bfp_player_volume'
        ), $product_id);
        
        // ❌ WRONG: Direct database access
        // $enable = get_post_meta($product_id, '_bfp_enable_player', true);
    }
}
```

### Audio Processing (`audio.php`)

```php
class BFP_Audio_Engine {
    public function process_audio($product_id) {
        // ✅ CORRECT: Bulk fetch related settings
        $demo_settings = $this->main_plugin->get_config()->get_states(array(
            '_bfp_secure_player',
            '_bfp_file_percent',
            '_bfp_fade_out'
        ), $product_id);
        
        if ($demo_settings['_bfp_secure_player']) {
            $this->create_demo($demo_settings['_bfp_file_percent']);
        }
    }
}
```

### WooCommerce Integration (`woocommerce.php`)

```php
class BFP_WooCommerce {
    public function check_purchase($product_id) {
        // ✅ CORRECT: Global setting check
        $purchased_enabled = $this->main_plugin->get_config()->get_state('_bfp_purchased', false);
        
        if (!$purchased_enabled) {
            return false;
        }
        
        // Check user purchases...
    }
}
```

### Admin Interface (`admin.php`)

```php
class BFP_Admin {
    public function render_settings_form() {
        // ✅ CORRECT: Get form-ready settings
        $settings = $this->main_plugin->get_config()->get_admin_form_settings();
        
        // Settings are already type-cast for forms
        echo '<input type="checkbox" ' . ($settings['_bfp_enable_player'] ? 'checked' : '') . '>';
    }
    
    public function save_settings() {
        // ✅ CORRECT: Bulk update through state manager
        $this->main_plugin->get_config()->update_global_attrs($_POST);
    }
}
```

### Utility Classes (`utils/*.php`)

```php
class BFP_Analytics {
    public function should_track() {
        // ✅ CORRECT: Simple state check
        return $this->main_plugin->get_config()->get_state('_bfp_analytics_property', '') !== '';
    }
}
```

### Views (`views/*.php`)

```php
// views/product-options.php
// ✅ CORRECT: Bulk fetch for form
$settings = $GLOBALS['BandfrontPlayer']->get_config()->get_states(array(
    '_bfp_enable_player',
    '_bfp_audio_engine',
    '_bfp_secure_player',
    // ... other settings
), $post->ID);

// Use individual settings
$enable_player = $settings['_bfp_enable_player'];
```

## Setting Categories

### Global-Only Settings

These settings cannot be overridden at the product level:

```php
$global_only = array(
    '_bfp_show_in',                    // Where to display players
    '_bfp_player_layout',              // Player skin
    '_bfp_player_controls',            // Control type
    '_bfp_player_title',               // Show titles
    '_bfp_on_cover',                   // Cover overlay
    '_bfp_force_main_player_in_title', // Title integration
    '_bfp_players_in_cart',            // Cart display
    '_bfp_play_simultaneously',        // Simultaneous playback
    '_bfp_registered_only',            // Access control
    '_bfp_purchased',                  // Purchase requirement
    '_bfp_message',                    // Demo message
    '_bfp_fade_out',                   // Fade effect
    // ... etc
);
```

### Overridable Settings

These can be customized per product:

```php
$overridable = array(
    '_bfp_enable_player',      // Enable/disable
    '_bfp_audio_engine',       // Audio engine
    '_bfp_single_player',      // Single player mode
    '_bfp_merge_in_grouped',   // Merge grouped
    '_bfp_play_all',           // Sequential play
    '_bfp_loop',               // Loop playback
    '_bfp_preload',            // Preload strategy
    '_bfp_player_volume',      // Volume
    '_bfp_secure_player',      // Demo mode
    '_bfp_file_percent',       // Demo percentage
    // ... etc
);
```

## Performance Best Practices

### 1. **Bulk Fetch When Possible**

```php
// ❌ POOR: Multiple single calls
$layout = $config->get_state('_bfp_player_layout');
$controls = $config->get_state('_bfp_player_controls');
$volume = $config->get_state('_bfp_player_volume');

// ✅ GOOD: Single bulk call
$settings = $config->get_states(array(
    '_bfp_player_layout',
    '_bfp_player_controls',
    '_bfp_player_volume'
));
```

### 2. **Cache Results in Local Variables**

```php
// ✅ GOOD: Cache for reuse
$enable_player = $config->get_state('_bfp_enable_player', false, $product_id);
if ($enable_player) {
    // Use $enable_player multiple times
}
```

### 3. **Use Appropriate Context**

```php
// ✅ GOOD: Product context when needed
$settings = $config->get_states($keys, $product_id);

// ✅ GOOD: No context for global settings
$global_settings = $config->get_states($global_keys);
```

## Type Safety

### Expected Types by Setting

```php
// Booleans (stored as 0/1, returned as bool)
'_bfp_enable_player' => bool
'_bfp_secure_player' => bool
'_bfp_ios_controls' => bool

// Integers
'_bfp_file_percent' => int (0-100)
'_bfp_single_player' => int (0/1)

// Floats
'_bfp_player_volume' => float (0.0-1.0)

// Strings
'_bfp_player_layout' => string ('dark'|'light'|'custom')
'_bfp_audio_engine' => string ('mediaelement'|'wavesurfer')
'_bfp_preload' => string ('none'|'metadata'|'auto')

// Arrays
'_bfp_demos_list' => array
'_bfp_modules_enabled' => array
```

### Type Casting in Admin Forms

```php
// The get_admin_form_settings() method handles type casting
$settings = $config->get_admin_form_settings();

// Checkboxes get boolean to int conversion
echo '<input type="checkbox" value="1" ' . ($settings['_bfp_enable_player'] ? 'checked' : '') . '>';

// Numbers are properly cast
echo '<input type="number" value="' . esc_attr($settings['_bfp_file_percent']) . '">';
```

## Migration Guide

### Converting Legacy Code

```php
// ❌ OLD: Direct database access
$enable = get_post_meta($product_id, '_bfp_enable_player', true);
$global = get_option('_bfp_player_layout', 'dark');
$attr = $this->main_plugin->get_global_attr('_bfp_audio_engine');

// ✅ NEW: State manager access
$enable = $this->main_plugin->get_config()->get_state('_bfp_enable_player', false, $product_id);
$global = $this->main_plugin->get_config()->get_state('_bfp_player_layout', 'dark');
$attr = $this->main_plugin->get_config()->get_state('_bfp_audio_engine');
```

### Adding New Settings

1. **Define in state-manager.php:**
```php
// Add to appropriate array
private $_overridable_settings = array(
    // ...
    '_bfp_new_setting' => 'default_value',
);
// OR
private $_global_only_settings = array(
    // ...
    '_bfp_new_global' => 'default',
);
```

2. **Use in your code:**
```php
$value = $config->get_state('_bfp_new_setting', 'default', $product_id);
```

3. **Add to admin forms as needed**

## Common Patterns

### Conditional Feature Enable

```php
if ($config->get_state('_bfp_enable_visualizations') && 
    $config->get_state('_bfp_audio_engine') === 'wavesurfer') {
    // Enable waveforms
}
```

### Settings-Based Class Names

```php
$classes = array(
    'bfp-player',
    'bfp-' . $config->get_state('_bfp_player_layout', 'dark'),
    $config->get_state('_bfp_single_player', 0, $product_id) ? 'bfp-single' : 'bfp-multiple'
);
echo '<div class="' . esc_attr(implode(' ', $classes)) . '">';
```

### Module Status Check

```php
if ($config->is_module_enabled('cloud-engine')) {
    // Load cloud features
}
```

## Debugging

### Check Setting Values

```php
// Debug single value
error_log('Setting value: ' . print_r($config->get_state('_bfp_enable_player', false, 123), true));

// Debug all settings for a product
error_log('Product settings: ' . print_r($config->get_all_settings(123), true));

// Debug global settings
error_log('Global settings: ' . print_r($config->get_all_global_attrs(), true));
```

### Clear Cache

```php
// Clear product cache
$config->clear_product_attrs_cache($product_id);

// Clear all caches
$config->clear_product_attrs_cache();
```

## Anti-Patterns to Avoid

### ❌ **Direct Database Access**
```php
// NEVER do this for plugin settings
$value = get_post_meta($id, '_bfp_setting', true);
$option = get_option('bfp_global_settings');
```

### ❌ **Bypassing Inheritance**
```php
// WRONG: Always checking global
$setting = $config->get_global_attr('_bfp_setting');

// RIGHT: Let inheritance work
$setting = $config->get_state('_bfp_setting', null, $product_id);
```

### ❌ **Individual Fetches in Loops**
```php
// WRONG: Multiple queries
foreach ($products as $product_id) {
    $enable = $config->get_state('_bfp_enable_player', false, $product_id);
    $engine = $config->get_state('_bfp_audio_engine', 'mediaelement', $product_id);
}

// RIGHT: Bulk fetch outside loop if possible
```

### ❌ **Assuming Setting Location**
```php
// WRONG: Assuming it's always global
$layout = $config->get_global_attr('_bfp_player_layout');

// RIGHT: Use get_state for proper inheritance
$layout = $config->get_state('_bfp_player_layout');
```

## Summary

The state management system provides a robust, performant, and maintainable way to handle all plugin settings. By following these guidelines, you ensure:

1. **Consistency** - All settings accessed the same way
2. **Performance** - Optimized queries and caching
3. **Flexibility** - Easy to add new settings
4. **Maintainability** - Single source of truth
5. **Reliability** - Type safety and validation

## Deprecated Settings (Now Automatic)

The following settings have been removed from the UI and are now handled automatically:

### iOS Controls (`_bfp_ios_controls`)
- **Previous:** Manual checkbox in troubleshooting
- **Current:** Auto-detected based on user agent
- **Implementation:** Checks for iPad/iPhone/iPod in HTTP_USER_AGENT

### Default Extension (`_bfp_default_extension`)  
- **Previous:** Manual checkbox for extensionless files
- **Current:** Always enabled with smart detection
- **Implementation:** Checks cloud URLs and MIME types automatically

### Disable 302 Redirects (`_bfp_disable_302`)
- **Previous:** Manual checkbox to disable redirects
- **Current:** Always serves files directly
- **Implementation:** Direct file serving for better performance

## Active Troubleshooting Settings

Only these settings remain in the troubleshooting section:

### Force Players in Titles (`_bfp_force_main_player_in_title`)
- **Purpose:** Layout preference for Gutenberg compatibility
- **Type:** Boolean (checkbox)
- **Default:** false

### Delete Demo Files (`_bfp_delete_demos`)
- **Purpose:** One-time action to regenerate demo files
- **Type:** Action checkbox (not saved)
- **Default:** N/A

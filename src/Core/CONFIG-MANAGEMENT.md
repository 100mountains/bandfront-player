# Configuration Management System

## Overview

The Config class provides a centralized state management system with automatic inheritance hierarchy: Product Setting → Global Setting → Default Value. It manages all plugin settings, handles product-specific overrides, and provides efficient bulk access patterns.

## Setting Inheritance Model

```
Product-Specific Setting (if exists and valid)
    ↓ (fallback)
Global Setting (if exists)
    ↓ (fallback)
Default Value
```

## Configuration Categories

### 1. Global-Only Settings

Settings that can ONLY be configured globally and cannot be overridden per product:

```php
private array $globalOnlySettings = [
    '_bfp_registered_only',        // Access control
    '_bfp_purchased',              // Purchase requirements
    '_bfp_reset_purchased_interval', // Reset frequency
    '_bfp_fade_out',               // Audio fade settings
    '_bfp_purchased_times_text',   // Display text
    '_bfp_ffmpeg',                 // FFmpeg enablement
    '_bfp_ffmpeg_path',            // FFmpeg binary path
    '_bfp_ffmpeg_watermark',       // Watermark file
    '_bfp_players_in_cart',        // Cart display
    '_bfp_player_layout',          // UI theme
    '_bfp_player_controls',        // Control style
    '_bfp_on_cover',               // Cover display
    '_bfp_message',                // Custom messages
    '_bfp_analytics_integration',  // Analytics type
    '_bfp_cloud_dropbox',          // Cloud storage
    '_bfp_cloud_s3',               // AWS S3
    '_bfp_cloud_azure',            // Azure storage
    // ... and more
];
```

### 2. Overridable Settings

Settings that can be overridden on a per-product basis:

```php
private array $overridableSettings = [
    '_bfp_enable_player',      // Enable/disable per product
    '_bfp_audio_engine',       // Audio engine override
    '_bfp_single_player',      // Single player mode
    '_bfp_merge_in_grouped',   // Grouped product behavior
    '_bfp_play_all',           // Playlist behavior
    '_bfp_loop',               // Loop playback
    '_bfp_player_volume',      // Default volume
    '_bfp_secure_player',      // Security features
    '_bfp_file_percent',       // Preview percentage
    '_bfp_own_demos',          // Custom demo files
    '_bfp_direct_own_demos',   // Direct demo links
    '_bfp_demos_list',         // Demo file list
];
```

### 3. Runtime State

Temporary state that exists only during request execution:

```php
private array $runtimeState = [
    '_bfp_purchased_product_flag',  // Purchase state cache
    '_bfp_force_purchased_flag',    // Override flag
    '_bfp_current_user_downloads',  // User download cache
];
```

## Core Methods

### State Retrieval

#### Single Value Access

```php
// Basic usage
$value = $config->getState('_bfp_enable_player');

// With default
$value = $config->getState('_bfp_audio_engine', 'html5');

// Product-specific
$value = $config->getState('_bfp_file_percent', 30, $productId);

// Force global (ignore product overrides)
$value = $config->getState('_bfp_player_layout', 'dark', null, ['force_global' => true]);
```

#### Bulk Access

```php
// Get multiple values efficiently
$settings = $config->getStates([
    '_bfp_enable_player',
    '_bfp_audio_engine',
    '_bfp_secure_player',
    '_bfp_file_percent'
], $productId);

// Get all settings
$allSettings = $config->getAllSettings($productId);

// Get player-specific state
$playerState = $config->getPlayerState($productId);
```

### State Modification

#### Update Values

```php
// Update global setting
$config->updateState('_bfp_player_layout', 'light');

// Update product override
$config->updateState('_bfp_enable_player', true, $productId);

// Update runtime state
$config->updateState('_bfp_purchased_product_flag', true);
```

#### Delete Overrides

```php
// Remove product override (revert to global)
$config->deleteState('_bfp_audio_engine', $productId);

// Clear all product cache
$config->clearProductAttrsCache($productId);

// Clear all caches
$config->clearProductAttrsCache();
```

#### Save Changes

```php
// Save all global settings to database
$config->saveGlobalSettings();

// Update global attributes cache
$config->updateGlobalAttrs($newSettings);
```

## Validation Rules

### Audio Engine Validation

```php
// Valid values: 'mediaelement', 'wavesurfer', 'html5'
// Special values: 'global' (use global), '' (empty = use global)
if ($key === '_bfp_audio_engine') {
    return !empty($value) && 
           $value !== 'global' && 
           in_array($value, ['mediaelement', 'wavesurfer', 'html5']);
}
```

### Boolean Settings

```php
// Settings that accept: '1', 1, true
$booleanSettings = [
    '_bfp_enable_player',
    '_bfp_secure_player',
    '_bfp_merge_in_grouped',
    '_bfp_single_player',
    '_bfp_play_all',
    '_bfp_loop',
    '_bfp_own_demos',
    '_bfp_direct_own_demos'
];
```

### Numeric Validation

```php
// File percent: 0-100
if ($key === '_bfp_file_percent') {
    return is_numeric($value) && $value >= 0 && $value <= 100;
}

// Volume: 0.0-1.0
if ($key === '_bfp_player_volume') {
    return is_numeric($value) && $value >= 0 && $value <= 1;
}
```

## Usage Patterns

### In Components

```php
class Player {
    private Config $config;
    
    public function __construct(Config $config) {
        $this->config = $config;
    }
    
    public function render(int $productId): string {
        // Get all player settings with inheritance
        $settings = $this->config->getPlayerState($productId);
        
        // Check if player is enabled
        if (!$settings['_bfp_enable_player']) {
            return '';
        }
        
        // Use settings to render player
        return $this->renderWithSettings($settings);
    }
}
```

### Admin Forms

```php
// Get formatted settings for admin forms
$formSettings = $config->getAdminFormSettings();

// Settings are properly typed and prefixed
foreach ($formSettings as $key => $value) {
    // $key includes _bfp_ prefix
    // $value is properly typed (bool, int, float, string)
}
```

### Module Management

```php
// Check if module is enabled
if ($config->isModuleEnabled('audio-engine')) {
    // Load audio engine components
}

// Enable/disable module
$config->setModuleState('cloud-engine', true);

// Get all modules
$modules = $config->getAllModules();
```

### Post Type Support

```php
// Get supported post types
$postTypes = $config->getPostTypes(); // Default: ['product']

// Filter allows extension
add_filter('bfp_post_types', function($types) {
    $types[] = 'download'; // Add EDD support
    return $types;
});
```

## Performance Optimization

### Caching Strategy

```php
// Product attributes are cached in memory
private array $productsAttrs = [];

// Global attributes loaded once
private array $globalAttrs = [];

// Bulk operations minimize database queries
$settings = $config->getStates($keys, $productId);
```

### Lazy Loading

```php
// Global settings loaded on first access
private function getGlobalAttr(string $key, mixed $default = null): mixed {
    if (empty($this->globalAttrs)) {
        $this->globalAttrs = get_option('bfp_global_settings', []);
    }
    // ...
}
```

## Filter Hooks

### Value Filters

```php
// Filter individual setting values
add_filter('bfp_state_value', function($value, $key, $productId, $source) {
    // $source = 'product' or 'global'
    return $value;
}, 10, 4);

// Filter global attributes
add_filter('bfp_global_attr', function($value, $key) {
    return $value;
}, 10, 2);

// Filter all settings
add_filter('bfp_all_settings', function($settings, $productId) {
    return $settings;
}, 10, 2);

// Filter player state
add_filter('bfp_player_state', function($state, $productId) {
    return $state;
}, 10, 2);

// Filter post types
add_filter('bfp_post_types', function($types) {
    return $types;
});
```

## Smart Context Detection

```php
public function smartPlayContext(int $productId): bool {
    // Single product pages
    if (function_exists('is_product') && is_product()) {
        return true;
    }
    
    // Shop pages
    if (function_exists('is_shop') && is_shop()) {
        return true;
    }
    
    // Category pages
    if (function_exists('is_product_category') && is_product_category()) {
        return true;
    }
    
    return false;
}
```

## Cloud Storage Configuration

### Structure

```php
'_bfp_cloud_dropbox' => [
    'enabled' => false,
    'access_token' => '',
    'folder_path' => '/bandfront-demos',
],

'_bfp_cloud_s3' => [
    'enabled' => false,
    'access_key' => '',
    'secret_key' => '',
    'bucket' => '',
    'region' => 'us-east-1',
    'path_prefix' => 'bandfront-demos/',
],

'_bfp_cloud_azure' => [
    'enabled' => false,
    'account_name' => '',
    'account_key' => '',
    'container' => '',
    'path_prefix' => 'bandfront-demos/',
]
```

### Access Pattern

```php
// Get cloud settings
$dropbox = $config->getState('_bfp_cloud_dropbox');
if ($dropbox['enabled']) {
    // Use Dropbox storage
}
```

## Migration from Legacy

### Old Pattern

```php
// Direct option access
$value = get_option('_bfp_enable_player', 1);
$productValue = get_post_meta($productId, '_bfp_file_percent', true);
```

### New Pattern

```php
// Centralized access with inheritance
$value = $config->getState('_bfp_enable_player', 1);
$productValue = $config->getState('_bfp_file_percent', null, $productId);
```

## Error Handling

### Safe Defaults

```php
// Always returns a value (never null unless explicitly set)
$value = $config->getState('unknown_key'); // Returns false

// With explicit default
$value = $config->getState('unknown_key', 'default_value');
```

### Type Safety

```php
// Admin form settings ensure proper types
$settings = $config->getAdminFormSettings();
// All values are cast to declared types (bool, int, float, string)
```

## Best Practices

### 1. Use Bulk Operations

```php
// Good - Single database query
$settings = $config->getStates(['key1', 'key2', 'key3'], $productId);

// Avoid - Multiple queries
$val1 = $config->getState('key1', null, $productId);
$val2 = $config->getState('key2', null, $productId);
$val3 = $config->getState('key3', null, $productId);
```

### 2. Respect Inheritance

```php
// Let inheritance work
$value = $config->getState($key, null, $productId);

// Only force global when necessary
if ($mustUseGlobal) {
    $value = $config->getState($key, null, null, ['force_global' => true]);
}
```

### 3. Clear Cache After Updates

```php
// After saving product meta
update_post_meta($productId, '_bfp_enable_player', 1);
$config->clearProductAttrsCache($productId);
```

### 4. Use Appropriate Methods

```php
// For player-specific needs
$playerState = $config->getPlayerState($productId);

// For admin forms
$formSettings = $config->getAdminFormSettings();

// For general access
$value = $config->getState($key, $default, $productId);
```

### 5. Handle Complex Settings

```php
// Cloud storage settings are arrays
$s3 = $config->getState('_bfp_cloud_s3');
if (is_array($s3) && $s3['enabled']) {
    // Safe to access array keys
}
```

## Debugging

### Inspect Settings

```php
// Get all global settings
$globals = $config->getAllGlobalAttrs();
var_dump($globals);

// Get all settings for a product
$productSettings = $config->getAllSettings($productId);
var_dump($productSettings);

// Check inheritance
$productValue = get_post_meta($productId, '_bfp_audio_engine', true);
$globalValue = $config->getState('_bfp_audio_engine');
$effectiveValue = $config->getState('_bfp_audio_engine', null, $productId);
```

### Clear All Caches

```php
// Clear everything
$config->clearProductAttrsCache();
$config->updateGlobalAttrs([]);
```

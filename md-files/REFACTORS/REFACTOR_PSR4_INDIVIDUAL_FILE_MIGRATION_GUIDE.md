# PSR-4 Individual File Migration Guide

## Purpose
This guide provides step-by-step instructions for converting a single class file from the old BFP_* naming convention to the modern PSR-4 namespaced structure.

## Prerequisites
- [ ] Composer installed with `"bfp\\": "src/"` autoload mapping
- [ ] Main Plugin class exists at `src/Plugin.php`
- [ ] Target file ready for conversion

## File Conversion Steps

### 1. Move and Rename File
```bash
# Example: Converting state-manager.php to Config.php
mv includes/state-manager.php src/Config.php

# For utils files:
mv includes/utils/analytics.php src/Utils/Analytics.php
```

### 2. Add Namespace Declaration
```php
// OLD FILE HEADER:
<?php
/**
 * BFP Config Class - State Manager
 * @package BandfrontPlayer
 */

if (!defined('ABSPATH')) {
    exit;
}

// NEW FILE HEADER:
<?php
namespace bfp;

/**
 * Configuration and State Management
 */

if (!defined('ABSPATH')) {
    exit;
}
```

For Utils classes, use: `namespace bfp\Utils;`

### 3. Update Class Declaration
```php
// OLD:
class BFP_Config {

// NEW:
class Config {
```

### 4. Update Constructor
```php
// OLD:
public function __construct($main_plugin) {
    $this->main_plugin = $main_plugin;
}

// NEW:
public function __construct(Plugin $mainPlugin) {
    $this->mainPlugin = $mainPlugin;
}
```

### 5. Convert All Properties
```php
// OLD:
private $main_plugin;
private $_products_attrs = array();
private $_enqueued_resources = false;

// NEW:
private Plugin $mainPlugin;
private array $productsAttrs = [];
private bool $enqueuedResources = false;
```

### 6. Convert All Method Names
```php
// OLD:
public function get_state($key, $default = null) {
    return $this->_get_global_attr($key, $default);
}

// NEW:
public function getState(string $key, mixed $default = null): mixed {
    return $this->getGlobalAttr($key, $default);
}
```

### 7. Update Internal References
```php
// OLD:
$config = $this->main_plugin->get_config();
$player = $this->main_plugin->get_player();

// NEW:
$config = $this->mainPlugin->getConfig();
$player = $this->mainPlugin->getPlayer();
```

### 8. Modernize PHP Syntax
```php
// OLD:
$array = array('key' => 'value');
if (isset($array['key'])) {
    $value = $array['key'];
} else {
    $value = 'default';
}

// NEW:
$array = ['key' => 'value'];
$value = $array['key'] ?? 'default';
```

## Quick Reference: Common Conversions

| Old Pattern | New Pattern |
|-------------|------------|
| `$this->main_plugin` | `$this->mainPlugin` |
| `get_state()` | `getState()` |
| `_private_method()` | `privateMethod()` |
| `array()` | `[]` |
| `$var = null` | `?Type $var = null` |
| `@return mixed` | `: mixed` |

## Validation Checklist
- [ ] File renamed and moved to correct location
- [ ] Namespace added at top of file
- [ ] Class name matches filename
- [ ] All properties have type declarations
- [ ] All methods converted to camelCase
- [ ] All method parameters and returns typed
- [ ] No underscore prefixes remain
- [ ] Modern PHP syntax used throughout

// ...existing code...

## Advanced Migration Patterns

### 9. Handle Static Method Conversion
```php
// OLD: Static utility methods
class BFP_Utils {
    public static function format_time($seconds) {
        return gmdate('i:s', $seconds);
    }
}

// Called as:
$time = BFP_Utils::format_time(120);

// NEW: Instance methods with dependency injection
class Utils {
    public function formatTime(int $seconds): string {
        return gmdate('i:s', $seconds);
    }
}

// Called as:
$time = $this->mainPlugin->getUtils()->formatTime(120);
```

### 10. Convert Global Functions
```php
// OLD: Global function in file
function bfp_get_player_html($product_id) {
    global $BandfrontPlayer;
    return $BandfrontPlayer->get_player()->get_player_html($product_id);
}

// NEW: Move to appropriate class method
class Player {
    public function getPlayerHtml(int $productId): string {
        // Implementation
    }
}

// Create a compatibility function if needed
function bfp_get_player_html($product_id) {
    global $BandfrontPlayer;
    return $BandfrontPlayer->getPlayer()->getPlayerHtml($product_id);
}
```

### 11. Handle WordPress Filters/Actions
```php
// OLD: Direct filter callbacks
add_filter('the_content', array($this, 'filter_content'));

public function filter_content($content) {
    // Implementation
}

// NEW: Type-hinted callbacks
add_filter('the_content', [$this, 'filterContent']);

public function filterContent(string $content): string {
    // Implementation
}
```

### 12. Update DocBlocks
```php
// OLD:
/**
 * Get player state
 * 
 * @param string $key
 * @param mixed $default
 * @return mixed
 */

// NEW:
/**
 * Get player state
 * 
 * @param string $key State key
 * @param mixed $default Default value if not found
 * @return mixed State value
 * @throws \InvalidArgumentException If key is invalid
 */
```

## Common Pitfalls During Migration

### Array Access
```php
// OLD: Unsafe array access
$value = $array['key'];

// NEW: Safe array access
$value = $array['key'] ?? null;
// or
$value = isset($array['key']) ? $array['key'] : null;
```

### Type Juggling
```php
// OLD: Loose comparison
if ($value == '1') {

// NEW: Strict comparison
if ($value === '1') {
```

### Property Initialization
```php
// OLD: Properties without defaults
private $data;

// NEW: Always initialize properties
private array $data = [];
private ?string $name = null;
private bool $isActive = false;
```

## File-Specific Notes

### For Admin Classes
- Keep admin notices in separate methods
- Use WordPress admin API properly
- Sanitize all form inputs

### For Ajax Handlers
- Always verify nonces
- Return JSON responses consistently
- Handle errors gracefully

### For Renderer Classes
- Separate logic from presentation
- Use output buffering when needed
- Escape all output

## Migration Order Recommendation

1. **Utils classes first** (fewest dependencies)
2. **Config/State Manager** (core functionality)
3. **Audio/Player classes** (main features)
4. **Integration classes** (WooCommerce, etc.)
5. **Admin/UI classes** (depends on others)
6. **Hooks class last** (references all others)

## Testing
```bash
# Check syntax
php -l src/Config.php

# Regenerate autoloader
composer dump-autoload

# Test in WordPress
wp plugin deactivate bandfront-player && wp plugin activate bandfront-player
```
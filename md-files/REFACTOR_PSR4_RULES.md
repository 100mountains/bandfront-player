# PSR-4 Migration Guide for Individual Files

## Overview
This guide helps convert individual class files from the old underscore-based naming system to modern PSR-4 namespaced classes with dependency injection and type hints.

## Pre-Migration Checklist
- [ ] Composer autoloader is set up with `"BandfrontPlayer\\": "includes/"` mapping
- [ ] Main Plugin class exists at `includes/Plugin.php` with proper getters
- [ ] Target file location matches PSR-4 namespace structure

## Step-by-Step Conversion Process

### 1. Update File Header
```php
// OLD:
<?php
/**
 * [Description] for Bandfront Player
 * @package BandfrontPlayer
 * @since 0.1
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// NEW:
<?php
namespace BandfrontPlayer;

/**
 * [Description]
 */

if (!defined('ABSPATH')) {
    exit;
}
```

### 2. Convert Class Declaration
```php
// OLD:
class BFP_[ClassName] {
    private $main_plugin;

// NEW:
class [ClassName] {
    private Plugin $mainPlugin;
```

### 3. Update Constructor
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

### 4. Convert Method Names and Type Hints
- **Snake_case to camelCase**: `get_state()` → `getState()`
- **Add return types**: `public function getName()` → `public function getName(): string`
- **Add parameter types**: `function process($id)` → `function process(int $id)`
- **Use modern array syntax**: `array()` → `[]`

### 5. Update Property Access Patterns
```php
// OLD:
$this->main_plugin->get_config()->get_state('key');
$this->main_plugin->get_player()->include_main_player();

// NEW:
$this->mainPlugin->getConfig()->getState('key');
$this->mainPlugin->getPlayer()->includeMainPlayer();
```

### 6. Convert Dependency Access
```php
// OLD:
$config = $this->main_plugin->get_config();
$player = $this->main_plugin->get_player();

// NEW:
$config = $this->mainPlugin->getConfig();
$player = $this->mainPlugin->getPlayer();
```

### 7. Update Internal Method Calls
```php
// OLD:
$this->_private_method($param);
$this->public_method($param);

// NEW:
$this->privateMethod($param);
$this->publicMethod($param);
```

### 8. Modernize PHP Syntax
```php
// OLD:
$array = array('key' => 'value');
if (empty($var)) return false;
$result = isset($array['key']) ? $array['key'] : 'default';

// NEW:
$array = ['key' => 'value'];
if (empty($var)) {
    return false;
}
$result = $array['key'] ?? 'default';
```

### 9. Add Proper Type Declarations
```php
// OLD:
private $_cache = array();
private $_flag = false;
private $_count = 0;

// NEW:
private array $cache = [];
private bool $flag = false;
private int $count = 0;
```

### 10. Update Namespace References
```php
// For utils classes:
// OLD: new BFP_Utils()
// NEW: new Utils\[ClassName]()

// Access via main plugin:
$this->mainPlugin->getFileHandler()
$this->mainPlugin->getAnalytics()
```

## Common Patterns to Convert

### State Management Access
```php
// OLD:
$value = $this->main_plugin->get_config()->get_state('_bfp_setting', 'default', $product_id);

// NEW:
$value = $this->mainPlugin->getConfig()->getState('_bfp_setting', 'default', $productId);
```

### Method Signature Updates
```php
// OLD:
public function process_file($file_path, $product_id = null, $options = array()) {

// NEW:
public function processFile(string $filePath, ?int $productId = null, array $options = []): bool {
```

### Array Handling
```php
// OLD:
if (!empty($array) && is_array($array)) {
    foreach ($array as $key => $value) {
        // process
    }
}

// NEW:
if (!empty($array)) {
    foreach ($array as $key => $value) {
        // process
    }
}
```

## Validation Checklist
After conversion, verify:
- [ ] Class has proper namespace declaration
- [ ] Constructor accepts `Plugin $mainPlugin` parameter
- [ ] All method names are camelCase
- [ ] All property access uses `$this->mainPlugin->getComponent()`
- [ ] Type hints are added to method parameters and return types
- [ ] Private methods start with lowercase (no underscore prefix)
- [ ] Array syntax uses `[]` instead of `array()`
- [ ] No direct instantiation of other BFP classes (use getters)

## File-Specific Notes

### For Utils Classes
- Move to utils subdirectory
- Access via `$this->mainPlugin->getUtilsComponent()`
- May need static methods converted to instance methods

### For Admin Classes
- Only instantiate when `is_admin()` is true
- Access via `$this->mainPlugin->getAdmin()`

### For Integration Classes (WooCommerce)
- Only instantiate when `class_exists('WooCommerce')`
- Access via `$this->mainPlugin->getWooCommerce()`

## Testing After Conversion
1. Check for PHP syntax errors: `php -l filename.php`
2. Verify autoloading works: instantiate class in isolation
3. Test main functionality through WordPress interface
4. Check for any undefined method/property errors in logs


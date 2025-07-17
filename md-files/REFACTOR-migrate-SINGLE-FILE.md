# File Migration Guide: Bootstrap Architecture

## Purpose

This guide provides instructions for migrating individual files from the legacy `bfp\` namespace to work with the new Bootstrap architecture. Each file should follow these patterns to integrate properly with the Bootstrap system.

## General Migration Pattern

### 1. Update Namespace

Replace the old namespace with the appropriate new namespace based on file location:

```php
// OLD
namespace bfp;

// NEW - Based on directory structure
namespace Bandfront\Audio;       // For audio-related classes
namespace Bandfront\Admin;       // For admin functionality
namespace Bandfront\Storage;     // For file operations
namespace Bandfront\WooCommerce; // For WooCommerce integration
namespace Bandfront\Core;        // For core functionality
namespace Bandfront\REST;        // For REST API endpoints
namespace Bandfront\Utils;       // For utility classes
```

### 2. Update Constructor

Replace Plugin dependency with specific dependencies:

```php
// OLD
class YourClass {
    private Plugin $mainPlugin;
    
    public function __construct(Plugin $mainPlugin) {
        $this->mainPlugin = $mainPlugin;
    }
}

// NEW
class YourClass {
    private Config $config;
    private FileManager $fileManager; // Only what you need
    
    public function __construct(Config $config, FileManager $fileManager) {
        $this->config = $config;
        $this->fileManager = $fileManager;
    }
}
```

### 3. Update Method Calls

Replace all `$this->mainPlugin->` references:

```php
// OLD
$value = $this->mainPlugin->getConfig()->getState('key');
$files = $this->mainPlugin->getFiles()->getAudioFiles($productId);
$player = $this->mainPlugin->getPlayer();

// NEW
$value = $this->config->getState('key');
$files = $this->fileManager->getAudioFiles($productId);
// Player would be injected if needed, or accessed via Bootstrap
```

### 4. Move Hook Registration

Remove hook registration from constructors. Hooks are now registered in `Core\Hooks.php`:

```php
// OLD - In constructor
public function __construct(Plugin $mainPlugin) {
    $this->mainPlugin = $mainPlugin;
    add_action('init', [$this, 'init']);
    add_filter('the_content', [$this, 'filterContent']);
}

// NEW - Clean constructor
public function __construct(Config $config) {
    $this->config = $config;
    // No hooks here - they're in Hooks.php
}
```

### 5. Remove Static Methods and Globals

Convert static methods to instance methods:

```php
// OLD
public static function getInstance() {
    static $instance = null;
    if ($instance === null) {
        $instance = new self();
    }
    return $instance;
}

// NEW - Bootstrap handles instantiation
// Remove getInstance() methods entirely
```

### 6. Update Component Access

For components that need access to other components dynamically:

```php
// OLD
$woocommerce = $this->mainPlugin->getWooCommerce();
if ($woocommerce && $woocommerce->isUserProduct($productId)) {
    // ...
}

// NEW - Three options:

// Option 1: Inject during construction
public function __construct(Config $config, ?WooCommerceIntegration $woocommerce = null) {
    $this->config = $config;
    $this->woocommerce = $woocommerce;
}

// Option 2: Use Bootstrap static access (only when necessary)
$bootstrap = Bootstrap::getInstance();
$woocommerce = $bootstrap->getComponent('woocommerce');

// Option 3: Pass as method parameter
public function processProduct(int $productId, WooCommerceIntegration $woocommerce): void {
    if ($woocommerce->isUserProduct($productId)) {
        // ...
    }
}
```

### 7. Update Import Statements

Add proper use statements for all dependencies:

```php
<?php
declare(strict_types=1);

namespace Bandfront\Audio;

use Bandfront\Core\Config;
use Bandfront\Storage\FileManager;
use Bandfront\Utils\Debug;

// Remove old imports
// use bfp\Plugin;
// use bfp\Utils\Files;
```

### 8. Type Declarations

Add strict types and proper type hints:

```php
// OLD
public function getAudioFiles($productId) {
    return $this->mainPlugin->getState('audio_files', [], $productId);
}

// NEW
public function getAudioFiles(int $productId): array {
    return $this->config->getState('audio_files', [], $productId);
}
```

### 9. Error Handling

Update error handling to not rely on Plugin instance:

```php
// OLD
if (!$this->mainPlugin->getConfig()->getState('enable_feature')) {
    $this->mainPlugin->logError('Feature disabled');
    return;
}

// NEW
if (!$this->config->getState('enable_feature')) {
    Debug::log('Feature disabled'); // Or use injected logger
    return;
}
```

### 10. File-Specific Patterns

#### For Admin Classes
```php
namespace Bandfront\Admin;

use Bandfront\Core\Config;

class YourAdminClass {
    private Config $config;
    
    public function __construct(Config $config) {
        $this->config = $config;
    }
}
```

#### For Audio Classes
```php
namespace Bandfront\Audio;

use Bandfront\Core\Config;
use Bandfront\Storage\FileManager;

class YourAudioClass {
    private Config $config;
    private FileManager $fileManager;
    
    public function __construct(Config $config, FileManager $fileManager) {
        $this->config = $config;
        $this->fileManager = $fileManager;
    }
}
```

#### For REST Classes
```php
namespace Bandfront\REST;

use Bandfront\Core\Config;
use Bandfront\Audio\Streamer;

class YourEndpoint {
    private Config $config;
    private Streamer $streamer;
    
    public function __construct(Config $config, Streamer $streamer) {
        $this->config = $config;
        $this->streamer = $streamer;
    }
}
```

## Common Replacements

| Old Pattern | New Pattern |
|------------|-------------|
| `$this->mainPlugin->getConfig()` | `$this->config` |
| `$this->mainPlugin->getFiles()` | `$this->fileManager` |
| `$this->mainPlugin->getPlayer()` | Inject Player or access via Bootstrap |
| `$this->mainPlugin->getWooCommerce()` | Inject WooCommerceIntegration |
| `$this->mainPlugin->getState()` | `$this->config->getState()` |
| `$this->mainPlugin->isModuleEnabled()` | `$this->config->isModuleEnabled()` |
| `new SomeClass($this->mainPlugin)` | Handled by Bootstrap |

## Final Structure

After migration, your file should:
- Have the correct namespace
- Accept only needed dependencies
- Use proper type declarations
- Not register its own hooks
- Not create other components
- Follow single responsibility principle
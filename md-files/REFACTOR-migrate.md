# Refactor 2025: Migration Guide to Bootstrap Architecture

namespace Bandfront\Core;

use Bandfront\Config;
use Bandfront\Audio\Player;
use Bandfront\Audio\Streamer;
use Bandfront\Audio\Processor;
use Bandfront\Audio\Analytics;
use Bandfront\Admin\Admin;
use Bandfront\WooCommerce\Integration as WooCommerceIntegration;
use Bandfront\Storage\FileManager;
use Bandfront\REST\StreamingEndpoint;
use Bandfront\Utils\Preview;
use Bandfront\Utils\Debug;


## Overview

This guide provides step-by-step instructions for migrating Bandfront Player components to the new Bootstrap-based architecture. The goal is to maintain all existing functionality while adopting a modern, maintainable structure that follows WordPress 2025 best practices.

## Core Principles

1. **Preserve Functionality**: All existing features must work exactly as before
2. **Minimize Breaking Changes**: Keep public APIs intact for backward compatibility
3. **Gradual Migration**: Components can be migrated one at a time
4. **Clean Architecture**: Separate concerns and remove circular dependencies

## Migration Checklist

### 1. Namespace Alignment

**Current State**: Mixed namespaces (`bfp\` and `Bandfront\`)

**Action Required**:
```php
// OLD
namespace bfp;

// NEW - Based on directory structure
namespace Bandfront\Audio;       // For audio-related classes
namespace Bandfront\Admin;       // For admin functionality
namespace Bandfront\Storage;     // For file operations
namespace Bandfront\WooCommerce; // For WooCommerce integration
namespace Bandfront\Core;        // For core functionality
namespace Bandfront\UI;       // For Renderer
namespace Bandfront\REST;        // For REST API endpoints
namespace Bandfront\Utils;       // For utility classes
```

### 2. Component Registration

**Current State**: Components create each other directly in Plugin.php

**New Pattern**: Bootstrap registers and injects dependencies

```php
// Example: Migrating Audio.php

// OLD: In Plugin.php
$this->audioCore = new Audio($this);

// NEW: In Bootstrap.php
$this->components['streamer'] = new Streamer(
    $this->config,
    $this->components['file_manager']
);
```

### 3. Dependency Injection

**Current State**: Components receive entire Plugin instance

**New Pattern**: Components receive only what they need

```php
// OLD
class Audio {
    private Plugin $mainPlugin;
    
    public function __construct(Plugin $mainPlugin) {
        $this->mainPlugin = $mainPlugin;
    }
}

// NEW
class Streamer {
    private Config $config;
    private FileManager $fileManager;
    
    public function __construct(Config $config, FileManager $fileManager) {
        $this->config = $config;
        $this->fileManager = $fileManager;
    }
}
```

### 4. Remove Circular Dependencies

**Issue**: Components calling back to Plugin class creates circular dependencies

**Solution**: Direct component-to-component communication

```php
// OLD - Circular dependency
$this->mainPlugin->getConfig()->getState('_bfp_secure_player');

// NEW - Direct access
$this->config->getState('_bfp_secure_player');
```

### 5. Legacy Method Delegation

**Current State**: Plugin.php delegates to other components

**Action**: Update all callers to use components directly

```php
// OLD
bfp()->includeMainPlayer();

// NEW
$bootstrap = Bootstrap::getInstance();
$player = $bootstrap->getComponent('player');
$player->includeMainPlayer();
```

## File-Specific Migration: Audio.php → Streamer.php

### Step 1: Create New File Structure

```
src/
├── Audio/
│   ├── Streamer.php      // Core streaming functionality
│   ├── Processor.php     // Demo/FFmpeg processing
│   └── Analytics.php     // Play tracking
```

### Step 2: Split Audio.php

The current Audio.php (870 lines) should be split into:

1. **Streamer.php** (~300 lines)
   - Stream handling
   - URL generation
   - REST API integration

2. **Processor.php** (~400 lines)
   - Demo file creation
   - FFmpeg processing
   - File manipulation

3. **Analytics.php** (~150 lines)
   - Play event tracking
   - Analytics integration

### Step 3: Update Class Structure

```php
<?php
declare(strict_types=1);

namespace Bandfront\Audio;

use Bandfront\Core\Config;
use Bandfront\Storage\FileManager;

/**
 * Audio Streaming Handler
 * 
 * @package Bandfront\Audio
 * @since 2.0.0
 */
class Streamer {
    
    private Config $config;
    private FileManager $fileManager;
    
    public function __construct(Config $config, FileManager $fileManager) {
        $this->config = $config;
        $this->fileManager = $fileManager;
    }
    
    // Methods from Audio.php related to streaming
    public function generateAudioUrl(int $productId, string|int $fileIndex, array $fileData = []): string {
        // Existing logic, but replace $this->mainPlugin->getConfig() with $this->config
    }
}
```

### Step 4: Fix Method Calls

Replace all `$this->mainPlugin->` calls:

```php
// OLD
$this->mainPlugin->getConfig()->getState('_bfp_secure_player');
$this->mainPlugin->getFiles()->createDemoFile();
$this->mainPlugin->getWooCommerce()->woocommerceUserProduct();

// NEW
$this->config->getState('_bfp_secure_player');
$this->fileManager->createDemoFile();
// WooCommerce check needs to be injected or use service locator pattern
```

### Step 5: Update Hook Registration

Move hook registration to Hooks.php:

```php
// In Hooks.php
private function registerAudioHooks(): void {
    $streamer = $this->bootstrap->getComponent('streamer');
    if ($streamer) {
        add_filter('bfp_preload', [$streamer, 'preload'], 10, 2);
        add_action('bfp_play_file', [$streamer, 'trackPlayEvent'], 10, 2);
    }
}
```

## Missing Components in Bootstrap

Based on the analysis, these components are missing from the current Bootstrap initialization:

1. **StreamController** - REST API streaming handler
2. **ProductProcessor** - Audio format generation
3. **FormatDownloader** - Download handling
4. **Renderer** - Consolidated rendering logic
5. **Cache Manager** - Centralized caching

Add to Bootstrap.php:

```php
private function initializeCore(): void {
    // ... existing code ...
    
    // Add missing components
    $this->components['stream_controller'] = new StreamController(
        $this->components['streamer'],
        $this->config
    );
    
    $this->components['renderer'] = new Renderer(
        $this->components['player'],
        $this->config
    );
}

private function initializeWooCommerce(): void {
    if ($this->isWooCommerceActive()) {
        $this->components['product_processor'] = new ProductProcessor(
            $this->components['processor'],
            $this->config
        );
        
        $this->components['format_downloader'] = new FormatDownloader(
            $this->components['file_manager'],
            $this->config
        );
    }
}
```

## Configuration Improvements

The Config class needs enhancement:

```php
// Add to Config.php
public function getComponent(string $name): mixed {
    // Service locator pattern for components that need dynamic access
    $bootstrap = Bootstrap::getInstance();
    return $bootstrap->getComponent($name);
}

// This allows:
$woocommerce = $this->config->getComponent('woocommerce');
if ($woocommerce && $woocommerce->woocommerceUserProduct($productId)) {
    // ...
}
```

## Testing Strategy

1. **Create compatibility layer** in Plugin.php that maps old methods to new components
2. **Test each component** individually after migration
3. **Verify hooks** are firing correctly
4. **Check REST API** endpoints still work
5. **Ensure backward compatibility** for third-party integrations

## Migration Order

Recommended order for migrating components:

1. **Config** ✅ (Already compatible)
2. **FileManager** (from Files.php)
3. **Streamer** (from Audio.php)
4. **Player** (update dependencies)
5. **Admin** (update to use Config directly)
6. **WooCommerce** (update all component references)
7. **Hooks** (consolidate from all components)

## Common Pitfalls to Avoid

1. **Don't break public APIs** - Keep bfp() helper working
2. **Don't remove hooks** - Third-party code may depend on them
3. **Don't change meta keys** - Would break existing data
4. **Don't alter REST endpoints** - Would break existing integrations
5. **Maintain filter/action signatures** - Parameters must stay the same

## Validation Checklist

After migrating each component:

- [ ] All public methods still accessible
- [ ] Hooks fire at correct times
- [ ] No PHP errors or warnings
- [ ] Admin interface works correctly
- [ ] Player renders on frontend
- [ ] Audio streaming works
- [ ] Settings save properly
- [ ] File uploads work
- [ ] Analytics tracking functions

## Conclusion

This migration preserves all functionality while modernizing the architecture. The key is gradual migration with thorough testing at each step. The new Bootstrap system provides better organization, testability, and maintainability while keeping the plugin fully functional throughout the transition.
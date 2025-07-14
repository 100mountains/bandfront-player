# PSR-4 Autoloading Tutorial

## What is PSR-4?

PSR-4 is a PHP Standard Recommendation that defines how to autoload classes from file paths. It eliminates the need for manual `require` or `include` statements by automatically loading classes when they're needed.

## Benefits of PSR-4

### 1. **Cleaner Code**
- No more `require_once` statements cluttering your files
- Focus on business logic instead of file management
- Reduces human error in file paths

### 2. **Better Organization** 
- Enforces consistent directory structure
- Classes are easy to find based on namespace
- Scalable architecture for large projects

### 3. **Performance**
- Classes loaded only when needed (lazy loading)
- Reduces memory footprint
- Faster application startup

### 4. **Standards Compliance**
- Industry standard approach
- Compatible with Composer and modern PHP frameworks
- Better IDE support and tooling

### 5. **Maintainability**
- Easier refactoring and renaming
- Clear dependency management
- Simplified testing and debugging

## How PSR-4 Works

### Namespace to File Path Mapping
```
Namespace: BandFront\Player\Audio\Engine
File Path: src/Audio/Engine.php
```

### Class Example
```php
<?php
namespace BandFront\Player\Audio;

class Engine {
    public function play() {
        // Implementation
    }
}
```

## Implementation Steps

### 1. Create Directory Structure
```
bandfront-player/
├── composer.json
├── src/
│   ├── Audio/
│   │   ├── Engine.php
│   │   └── Processor.php
│   ├── Player/
│   │   ├── Manager.php
│   │   └── Renderer.php
│   └── Admin/
│       ├── Settings.php
│       └── Metabox.php
```

### 2. Configure Composer
```json
{
    "autoload": {
        "psr-4": {
            "BandFront\\Player\\": "src/"
        }
    }
}
```

### 3. Generate Autoloader
```bash
composer dump-autoload
```

### 4. Include Autoloader
```php
// In your main plugin file
require_once __DIR__ . '/vendor/autoload.php';
```

### 5. Use Classes
```php
// No require statements needed!
use BandFront\Player\Audio\Engine;
use BandFront\Player\Player\Manager;

$engine = new Engine();
$manager = new Manager();
```

## Bandfront Player Migration Plan

### Current Structure
```
includes/
├── class-bfp-admin.php
├── class-bfp-audio-processor.php
├── class-bfp-player-manager.php
└── ...
```

### Target PSR-4 Structure
```
src/
├── Admin/
│   ├── Admin.php
│   ├── Settings.php
│   └── Metabox.php
├── Audio/
│   ├── Processor.php
│   └── Engine.php
├── Player/
│   ├── Manager.php
│   ├── Renderer.php
│   └── State.php
└── Integration/
    ├── WooCommerce.php
    └── Gutenberg.php
```

### Migration Steps

1. **Create composer.json**
2. **Create src/ directory**
3. **Move and rename class files**
4. **Update namespaces in classes**
5. **Remove old require statements**
6. **Update instantiation calls**
7. **Test thoroughly**

### Class Naming Convention
```php
// Old
class BFP_Audio_Processor { }

// New PSR-4
namespace BandFront\Player\Audio;
class Processor { }
```

### Usage Examples

#### Before PSR-4
```php
require_once 'includes/class-bfp-audio-processor.php';
require_once 'includes/class-bfp-player-manager.php';

$processor = new BFP_Audio_Processor();
$manager = new BFP_Player_Manager();
```

#### After PSR-4
```php
use BandFront\Player\Audio\Processor;
use BandFront\Player\Player\Manager;

$processor = new Processor();
$manager = new Manager();
```

## Best Practices

### 1. **Consistent Naming**
- Use PascalCase for class names
- Use meaningful, descriptive names
- Follow single responsibility principle

### 2. **Logical Grouping**
- Group related classes in same namespace
- Create separate namespaces for different concerns
- Keep directory depth reasonable (max 3-4 levels)

### 3. **Interface Segregation**
- Define interfaces for contracts
- Use dependency injection
- Program to interfaces, not implementations

### 4. **Testing**
- Each class should be easily testable
- Mock dependencies using interfaces
- Use proper test organization

## Common Pitfalls

### 1. **Case Sensitivity**
- File names must match class names exactly
- Directory names must match namespace segments

### 2. **Namespace Declaration**
- Must be the first non-comment code in file
- Must match directory structure exactly

### 3. **File Organization**
- One class per file
- File name must match class name
- Proper directory structure is critical

## WordPress Integration

### Hooks and Actions
```php
namespace BandFront\Player\Admin;

class Admin {
    public function __construct() {
        add_action('admin_menu', [$this, 'add_menu']);
        add_action('admin_init', [$this, 'init_settings']);
    }
    
    public function add_menu() {
        // Implementation
    }
}
```

### Plugin Initialization
```php
// Main plugin file
use BandFront\Player\Plugin;

if (!defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/vendor/autoload.php';

// Initialize plugin
$plugin = new Plugin();
$plugin->init();
```

## Conclusion

PSR-4 autoloading transforms PHP development by:
- Eliminating manual file management
- Enforcing better code organization
- Improving performance and maintainability
- Following industry standards

The migration requires careful planning but results in cleaner, more professional code that's easier to maintain and extend.

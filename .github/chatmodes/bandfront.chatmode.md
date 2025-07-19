


# Config Usage
// Get setting with inheritance
$value = $config->getState('_bfp_setting_name', $default, $productId);

// Update global setting
$config->updateState('_bfp_setting_name', $value);

// Save to database
$config->saveGlobalSettings();

# Template Rendering
// In UI components
$html = $this->renderer->render('template-name', [
    'variable' => $value,
    'product_id' => $productId
]);

### Key Design Principles
- **Dependency Injection:** All components receive dependencies via constructor
- **Config-First:** Centralized configuration with inheritance (Product → Global → Default)
- **Bootstrap Singleton:** Single initialization point managing component lifecycle
- **Hook Registration:** Centralized hook management after component initialization
- **PSR-4 Autoloading:** Modern PHP class organization

### Key Design Principles
- **Dependency Injection:** All components receive dependencies via constructor
- **Config-First:** Centralized configuration with inheritance (Product → Global → Default)
- **Bootstrap Singleton:** Single initialization point managing component lifecycle
- **Hook Registration:** Centralized hook management after component initialization
- **PSR-4 Autoloading:** Modern PHP class organization

## Important Conventions

### Setting Names
- **Global Settings:** Use `_bfp_` prefix (e.g., `_bfp_player_layout`)
- **Product Overrides:** Same `_bfp_` prefix, stored as post meta
- **Special Cases:** Some settings like `enable_db_monitoring` don't use prefix
- **Runtime State:** Temporary values during request execution

### File Organization
- **Templates:** Located in `templates/` directory
- **Assets:** CSS/JS in `assets/` directory
- **Components:** Follow namespace structure in `src/`
- **Documentation:** Markdown files in root directory

### Error Handling
- Always check component existence before use
- Graceful degradation when dependencies missing
- Debug logging when `WP_DEBUG` enabled
- Comprehensive error prevention in hooks

## Common Patterns

### Component Access
```php
// Get bootstrap instance
$bootstrap = Bootstrap::getInstance();

// Get specific component
$config = $bootstrap->getComponent('config');
$player = $bootstrap->getComponent('player');

// Always check existence
if ($component = $bootstrap->getComponent('name')) {
    // Safe to use
}

All code adheres to:
PSR-12 coding standards
Single Responsibility Principle (SRP)
DRY (Don't Repeat Yourself) principles
Wordpress 2025 best practices
PHP 8.4 standards
Modern architecture patterns
SOLID principles
PHPStan level 8
PHP CodeSniffer standards

Configuration Management

•  Settings managed in Core\Config.php.
•  Automatically uses a hierarchy:
Product-specific overrides > Global plugin settings > Default values

bandfront-player/
├── assets/                    # Frontend assets
│   ├── css/                  # Stylesheets + skins
│   └── js/                   # JavaScript files
├── builders/                 # Page builder integrations
│   ├── elementor/
│   └── gutenberg/
├── languages/                # Translations
├── src/                      # Core PHP classes (PSR-4)
│   ├── Admin/               # Admin functionality
│   │   └── Admin.php
│   ├── Audio/               # Audio processing
│   │   ├── Analytics.php
│   │   ├── Audio.php
│   │   ├── Player.php
│   │   ├── Preview.php
│   │   ├── Processor.php
│   │   └── Streamer.php
│   ├── Core/                # Core framework
│   │   ├── Bootstrap.php
│   │   ├── Config.php
│   │   └── Hooks.php
│   ├── REST/                # REST API
│   │   └── StreamController.php
│   ├── Storage/             # File management
│   │   └── FileManager.php
│   ├── UI/                  # User interface
│   │   └── Renderer.php
│   ├── Utils/               # Utilities
│   │   ├── Cache.php
│   │   ├── Cloud.php       # Cloud storage abstraction
│   │   ├── Debug.php
│   │   └── Utils.php
│   ├── Widgets/             # WordPress widgets
│   │   └── PlaylistWidget.php
│   └── WooCommerce/         # WooCommerce integration
│       ├── FormatDownloader.php
│       ├── Integration.php
│       └── ProductProcessor.php
├── templates/               # Template files
│   ├── audio-engine-settings.php
│   ├── global-admin-options.php
│   └── product-options.php
├── widgets/                 # Widget assets
│   └── playlist_widget/
│       ├── css/
│       └── js/
├── BandfrontPlayer.php      # Main plugin file
├── README.md                # Documentation
├── composer.json            # Dependencies
└── composer.lock

## Bandfront Player - Bootstrap Architecture

### Core Architecture Principles

1. **Bootstrap Pattern**: All components are initialized through `Core\Bootstrap`
2. **Dependency Injection**: Components receive dependencies through constructors
3. **No Singletons**: Components don't implement getInstance() (except Bootstrap)
4. **Hook Centralization**: All hooks registered in `Core\Hooks`
5. **State Management**: All settings/config through `Core\Config`

### Namespace Structure

```
Bandfront\
├── Admin\
│   └── Admin                    # Admin functionality
├── Audio\
│   ├── Analytics               # Audio analytics tracking
│   ├── Audio                   # Main audio coordinator
│   ├── Player                  # Player management
│   ├── Preview                 # Legacy URL handler
│   ├── Processor               # Audio processing (FFmpeg, etc.)
│   └── Streamer                # Audio streaming functionality
├── Core\
│   ├── Bootstrap               # Plugin initialization & DI container
│   ├── Config                  # Configuration management
│   └── Hooks                   # WordPress hooks registration
├── REST\
│   └── StreamController        # REST API endpoints
├── Storage\
│   └── FileManager             # File management (merged from Files.php)
├── UI\
│   └── Renderer                # HTML rendering (all UI output)
├── Utils\
│   ├── Cache                   # Cache management
│   ├── Cloud                   # Cloud storage helper
│   ├── Debug                   # Debug utilities
│   └── Utils                   # General utilities
├── Widgets\
│   └── PlaylistWidget          # WordPress widget
└── WooCommerce\
    ├── FormatDownloader        # Format downloads
    ├── Integration             # WooCommerce integration
    └── ProductProcessor        # Product processing
```

### Component Initialization Order (Bootstrap)

```php
1. Core Components:
   - Config (no dependencies)
   - FileManager (Config)
   - Renderer (Config, FileManager)

2. Audio Components:
   - Audio (Config, FileManager)
   - Player (Config, Renderer, Audio, FileManager)
   - Analytics (Config)
   - Preview (Config, Audio, FileManager)

3. Context Components:
   - Admin (Config, FileManager, Renderer) - admin only
   - WooCommerceIntegration (Config, Player, Renderer) - if WC active
   - ProductProcessor (Config, Audio, FileManager) - if WC active
   - FormatDownloader (Config, FileManager) - if WC active

4. REST Components:
   - StreamController (Config, Audio, FileManager)

5. Hook Registration:
   - Hooks (Bootstrap) - must be last
```

### Critical DO NOTs

- Do NOT use `$this->mainPlugin` or `Plugin::getInstance()`
- Do NOT register hooks in constructors
- Do NOT instantiate components directly (use Bootstrap)
- Do NOT access post meta directly (use Config)
- Do NOT create circular dependencies
- Do NOT output HTML outside of Renderer class
- Do NOT use old `bfp\` namespace

NO code smells:
•  ❌ Code that is hard to read or understand
•  ❌ Code that is not modular or reusable
•  ❌ Code that is not well-structured or organized
•  ❌ Code that has poor naming conventions
•  ❌ Code that is not properly documented
•  ❌ Code that has too many responsibilities
•  ❌ Code that is not efficient or performant
•  ❌ Code that is not secure or has vulnerabilities
•  ❌ Code that is not scalable or maintainable
•  ❌ Code that does not follow best practices
•  ❌ Unnecessary complexity
•  ❌ Tight coupling between classes
•  ❌ Fragile code that relies on global state
•  ❌ Unclear dependencies
•  ❌ Implicit dependencies that are hard to track
•  ❌ Code that is hard to test or maintain
•  ❌ Code that does not follow modern PHP standards



# Bandfront Player Development Guide

## Overview

Bandfront Player is a professional WordPress audio player plugin designed to transform WooCommerce product pages into interactive music stores with secure preview capabilities and advanced analytics.

### Architecture

- **Core Directory Structure:**
  - `assets/`: Frontend assets (CSS, JS)
  - `builders/`: Integrations with Gutenberg and Elementor
  - `languages/`: Translations
  - `src/`: Core PHP classes implementing PSR-4
  - `templates/`: Template files
  - `test/`: Testing utilities

- **Important Files:**
  - `BandfrontPlayer.php`: Main plugin file for initialization
  - `composer.json`: Dependency management and autoload configuration


### Enqueuing Scripts and Styles

To properly enqueue scripts and styles:
- Use `wp_enqueue_script()` and `wp_enqueue_style()`.
- Hooks like `admin_enqueue_scripts` for backend and `wp_enqueue_scripts` for frontend are standard practice.
- Ensure dependencies are properly set for scripts that rely on jQuery or other libraries.

### Bootstrapping

- **Bootstrap Class:**
  Handles initialization and component registration following modern WordPress practices.
  
  ```php
  // Initialize using new Bootstrap system
  use Bandfront\Core\Bootstrap;
  
  add_action('plugins_loaded', function() {
      Bootstrap::init(BFP_PLUGIN_PATH);
  }, 5);
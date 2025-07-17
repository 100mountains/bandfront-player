# Bandfront Player Refactoring Plan - WordPress 2025 Best Practices

## Overview
This document outlines the refactoring plan to modernize the Bandfront Player plugin, removing backward compatibility constraints and implementing WordPress 2025 best practices.

## Current Structure Analysis

### Existing Files
- **Plugin.php** (664 lines) - God object with too many responsibilities
- **Audio.php** (870 lines) - Mixed streaming, processing, analytics, caching
- **Admin.php** (608 lines) - Mixed UI, settings, meta handling
- **Config.php** - State management (relatively clean)
- **Player.php** - Player rendering functionality
- **WooCommerce.php** - WooCommerce integration
- **Renderer.php** - HTML rendering
- **StreamController.php** - REST API streaming
- **ProductProcessor.php** - Product audio processing
- **FormatDownloader.php** - Download format handling
- **Hooks.php** - WordPress hooks registration
- **Utils/** - Various utility classes
- **Views/** - PHP template files
- **Widgets/** - Widget implementations
- **Modules/** - Third-party integrations (Google Drive)

## Target Architecture

```
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
│   ├── Audio/               # Audio processing
│   ├── Core/                # Core framework
│   ├── REST/                # REST API
│   ├── Storage/             # Cloud storage
│   ├── UI/                   # User interface
│   ├── Utils/               # Utilities
│   ├── Widgets/             # WordPress widgets
│   └── WooCommerce/         # WooCommerce integration
├── templates/               # Template files
├── test/                    # Testing utilities
├── BandfrontPlayer.php      # Main plugin file
├── README.md                # Documentation
├── composer.json            # Dependencies
└── composer.lock

```


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

## Refactoring Steps

### Phase 1: Core Structure (Week 1)

#### 1.1 Create New Bootstrap System
**From:** `Plugin.php` (664 lines)
**To:** 
- `Core/Bootstrap.php` (~100 lines) - Initialization only
- `Core/Hooks.php` (~150 lines) - Hook management
- Remove all delegated methods
- Remove state flags (use Config)

**Key Changes:**
```php
// New Bootstrap.php
namespace Bandfront\Core;

class Bootstrap {
    private static ?Bootstrap $instance = null;
    private Config $config;
    
    public static function init(string $pluginFile): void {
        if (self::$instance === null) {
            self::$instance = new self($pluginFile);
            self::$instance->boot();
        }
    }
    
    private function boot(): void {
        $this->config = new Config();
        
        // Initialize components based on context
        $this->initializeCore();
        $this->initializeAdmin();
        $this->initializePublic();
        $this->initializeREST();
    }
}
```

#### 1.2 Modernize Config System
**From:** Current `Config.php`
**To:** Enhanced `Core/Config.php`

**Improvements:**
- Remove complex inheritance logic
- Simple get/set/save methods
- Separate product meta handling
- Use WordPress 2025 metadata API

### Phase 2: Audio System Refactoring (Week 1-2)

#### 2.1 Split Audio.php
**From:** `Audio.php` (870 lines)
**To:**
- `Audio/Streamer.php` (~200 lines) - Streaming only
- `Audio/Processor.php` (~250 lines) - Demo creation, FFmpeg
- `Audio/Analytics.php` (~100 lines) - Tracking
- `Storage/FileManager.php` (~200 lines) - File operations

**Example - New Streamer.php:**
```php
namespace Bandfront\Audio;

class Streamer {
    public function __construct(
        private Config $config,
        private FileManager $files
    ) {}
    
    public function stream(int $productId, int $trackIndex): void {
        $track = $this->getTrack($productId, $trackIndex);
        
        if (!$track) {
            wp_die('Track not found', 404);
        }
        
        $this->sendHeaders($track);
        $this->outputFile($track);
    }
}
```

#### 2.2 Create REST API Controller
**From:** `StreamController.php` + REST logic in `Plugin.php`
**To:** `REST/StreamingEndpoint.php`

**Improvements:**
- Proper REST API schema
- Permission callbacks
- Response formatting

### Phase 3: Admin Refactoring (Week 2)

#### 3.1 Split Admin.php
**From:** `Admin.php` (608 lines)
**To:**
- `Admin/Settings.php` (~200 lines) - Settings page
- `Admin/ProductMeta.php` (~150 lines) - Product options
- `Admin/Columns.php` (~100 lines) - List columns
- Remove AJAX handlers (use REST API)

#### 3.2 Modernize Settings
- Use WordPress Settings API properly
- React-based admin UI (optional)
- Proper validation/sanitization

### Phase 4: Storage & Files (Week 2-3)

#### 4.1 Create Storage Layer
**New Classes:**
- `Storage/FileManager.php` - Local file operations
- `Storage/CloudStorage.php` - Cloud abstraction
- `Storage/Cache.php` - Transient caching

**From:** Various file operations in `Audio.php`, `Utils/Files.php`
**Benefits:** Single responsibility, testable, extensible

### Phase 5: WooCommerce Integration (Week 3)

#### 5.1 Refactor WooCommerce.php
**From:** `WooCommerce.php` (mixed concerns)
**To:**
- `WooCommerce/Integration.php` - Hook registration
- `WooCommerce/Downloads.php` - Download management
- `WooCommerce/ProductAudio.php` - Product-specific logic

### Phase 6: Modern Features (Week 3-4)

#### 6.1 Add Gutenberg Block
**New:** `Blocks/AudioPlayer.php`
```php
namespace Bandfront\Blocks;

class AudioPlayer {
    public function register(): void {
        register_block_type('bandfront/audio-player', [
            'editor_script' => 'bandfront-block-editor',
            'render_callback' => [$this, 'render'],
            'attributes' => [
                'productId' => ['type' => 'number'],
                'showPlaylist' => ['type' => 'boolean', 'default' => true]
            ]
        ]);
    }
}
```

#### 6.2 Implement Modern REST API
- OpenAPI schema
- Proper authentication
- Rate limiting

## Implementation Guidelines

### 1. WordPress 2025 Standards

```php
// Use strict types
declare(strict_types=1);

// Type declarations everywhere
public function getTrack(int $productId, int $index): ?array

// Use WordPress naming conventions
function bandfront_get_player(): string

// Proper escaping
echo esc_html($title);
echo esc_url($audioUrl);
echo esc_attr($class);

// Use wp_send_json for AJAX
wp_send_json_success(['player' => $html]);
```

### 2. Remove Legacy Code

**Remove:**
- All backward compatibility checks
- Legacy Google Drive module (replace with modern API)
- jQuery dependencies where possible
- Old MediaElement.js code (use native HTML5)

**Replace with:**
- Modern JavaScript (ES6+)
- Native browser APIs
- WordPress REST API
- React for complex UIs

### 3. Modern Asset Management

```php
// Use asset files generated by @wordpress/scripts
$asset_file = include plugin_dir_path(__FILE__) . 'build/player.asset.php';

wp_enqueue_script(
    'bandfront-player',
    plugins_url('build/player.js', __FILE__),
    $asset_file['dependencies'],
    $asset_file['version']
);
```

### 4. Testing Strategy

```
tests/
├── unit/
│   ├── Audio/StreamerTest.php
│   ├── Core/ConfigTest.php
│   └── Storage/FileManagerTest.php
├── integration/
│   ├── WooCommerceTest.php
│   └── RESTEndpointTest.php
└── e2e/
    └── PlayerTest.js
```

## Migration Path

### Week 1: Core Refactoring
1. Create new directory structure
2. Implement Bootstrap and Config
3. Start splitting Audio.php

### Week 2: Component Migration
1. Complete Audio system refactoring
2. Refactor Admin components
3. Update hook system

### Week 3: Integration & Storage
1. Implement storage layer
2. Refactor WooCommerce integration
3. Update REST endpoints

### Week 4: Modern Features
1. Add Gutenberg block
2. Implement React admin UI
3. Performance optimization

## Code Style Guide

### PHP
- PSR-12 coding standard
- Strict types enabled
- Type hints on all methods
- No mixed return types

### JavaScript
- ES6+ modules
- WordPress coding standards
- JSDoc comments
- No jQuery for new code

### CSS
- BEM methodology
- CSS custom properties
- Mobile-first approach
- No !important

## Performance Considerations

1. **Lazy Loading**
   - Load components only when needed
   - Use WordPress conditional tags

2. **Caching**
   - Implement proper transient caching
   - Use object cache if available

3. **Asset Optimization**
   - Minify JS/CSS in production
   - Use wp_enqueue_script dependencies

## Security Best Practices

1. **Data Validation**
   ```php
   $productId = absint($_GET['product_id'] ?? 0);
   $nonce = sanitize_text_field($_POST['_wpnonce'] ?? '');
   ```

2. **Capability Checks**
   ```php
   if (!current_user_can('edit_products')) {
       wp_die('Unauthorized');
   }
   ```

3. **SQL Injection Prevention**
   ```php
   $wpdb->prepare("SELECT * FROM {$wpdb->posts} WHERE ID = %d", $productId)
   ```

## Backward Compatibility

Since we're not maintaining backward compatibility:

1. **Database Migration**
   - Create migration script for settings
   - Update meta key names
   - Clean up old data

2. **User Communication**
   - Clear upgrade guide
   - Migration tool
   - Support documentation

## Success Metrics

- [ ] All classes under 300 lines
- [ ] Each class has single responsibility
- [ ] 80%+ code coverage
- [ ] PSR-12 compliance
- [ ] No jQuery dependencies
- [ ] Gutenberg block support
- [ ] Modern REST API
- [ ] Performance improvement (measure load time)

## Next Steps

1. Review and approve plan
2. Set up development environment
3. Create feature branch
4. Begin Phase 1 implementation
5. Weekly progress reviews

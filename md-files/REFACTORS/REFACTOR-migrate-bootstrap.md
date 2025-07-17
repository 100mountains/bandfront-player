# Bootstrap Configuration Guide

## Purpose

This guide configures the Bootstrap system to work with all existing files that have been migrated to the new namespace structure. Bootstrap is responsible for initializing all components with their proper dependencies.

## Bootstrap Component Registration

### Core Components

These components are always initialized:

```php
private function initializeCore(): void {
    // Configuration - Always first
    $this->components['config'] = new Config();
    
    // Storage layer
    $this->components['file_manager'] = new FileManager($this->components['config']);
    
    // Renderer - Used by multiple components
    $this->components['renderer'] = new Renderer(
        $this->components['config'],
        $this->components['file_manager']
    );
}
```

### Audio Components

Initialize all audio-related functionality:

```php
private function initializeAudio(): void {
    // Core audio functionality
    $this->components['audio'] = new Audio(
        $this->components['config'],
        $this->components['file_manager']
    );
    
    // Player management
    $this->components['player'] = new Player(
        $this->components['config'],
        $this->components['renderer'],
        $this->components['audio']
    );
    
    // Analytics tracking
    $this->components['analytics'] = new Analytics(
        $this->components['config']
    );
    
    // Preview generation
    $this->components['preview'] = new Preview(
        $this->components['config'],
        $this->components['audio'],
        $this->components['file_manager']
    );
}
```

### Admin Components

Initialize admin interface (only when in admin context):

```php
private function initializeAdmin(): void {
    if (!is_admin()) {
        return;
    }
    
    // Delay initialization to ensure WordPress admin is ready
    add_action('init', function() {
        $this->components['admin'] = new Admin(
            $this->components['config'],
            $this->components['file_manager'],
            $this->components['renderer']
        );
    }, 1);
}
```

### WooCommerce Integration

Initialize only when WooCommerce is active:

```php
private function initializeWooCommerce(): void {
    if (!class_exists('WooCommerce')) {
        return;
    }
    
    // Main WooCommerce integration
    $this->components['woocommerce'] = new WooCommerceIntegration(
        $this->components['config'],
        $this->components['player'],
        $this->components['renderer']
    );
    
    // Product processor for audio generation
    $this->components['product_processor'] = new ProductProcessor(
        $this->components['config'],
        $this->components['audio'],
        $this->components['file_manager']
    );
    
    // Format downloader
    $this->components['format_downloader'] = new FormatDownloader(
        $this->components['config'],
        $this->components['file_manager']
    );
}
```

### REST API Components

Initialize REST endpoints:

```php
private function initializeREST(): void {
    add_action('rest_api_init', function() {
        // Stream controller for audio delivery
        $this->components['stream_controller'] = new StreamController(
            $this->components['config'],
            $this->components['audio'],
            $this->components['file_manager']
        );
        
        // Register routes
        $this->components['stream_controller']->registerRoutes();
    });
}
```

### Utility Components

Static utilities don't need initialization but are available:

```php
// These are static and don't need Bootstrap registration:
// - Utils\Debug
// - Utils\Cloud
// - Utils\Cache
// - Utils\Utils
```

## Complete Bootstrap Implementation

```php
<?php
declare(strict_types=1);

namespace Bandfront\Core;

use Bandfront\Config;
use Bandfront\Audio\Audio;
use Bandfront\Audio\Player;
use Bandfront\Audio\Analytics;
use Bandfront\Audio\Preview;
use Bandfront\Admin\Admin;
use Bandfront\WooCommerce\WooCommerceIntegration;
use Bandfront\WooCommerce\ProductProcessor;
use Bandfront\WooCommerce\FormatDownloader;
use Bandfront\Storage\FileManager;
use Bandfront\REST\StreamController;
use Bandfront\Renderer;

class Bootstrap {
    
    private static ?Bootstrap $instance = null;
    private array $components = [];
    private string $pluginFile;
    
    private function __construct(string $pluginFile) {
        $this->pluginFile = $pluginFile;
    }
    
    public static function init(string $pluginFile): void {
        if (self::$instance === null) {
            self::$instance = new self($pluginFile);
            self::$instance->boot();
        }
    }
    
    public static function getInstance(): ?self {
        return self::$instance;
    }
    
    private function boot(): void {
        // Core components
        $this->initializeCore();
        $this->initializeAudio();
        
        // Context-specific components
        $this->initializeAdmin();
        $this->initializeWooCommerce();
        $this->initializeREST();
        
        // Register all hooks last
        $this->components['hooks'] = new Hooks($this);
        
        // Plugin is ready
        do_action('bandfront_player_initialized', $this);
    }
    
    private function initializeCore(): void {
        $this->components['config'] = new Config();
        $this->components['file_manager'] = new FileManager($this->components['config']);
        $this->components['renderer'] = new Renderer(
            $this->components['config'],
            $this->components['file_manager']
        );
    }
    
    private function initializeAudio(): void {
        $this->components['audio'] = new Audio(
            $this->components['config'],
            $this->components['file_manager']
        );
        
        $this->components['player'] = new Player(
            $this->components['config'],
            $this->components['renderer'],
            $this->components['audio']
        );
        
        $this->components['analytics'] = new Analytics($this->components['config']);
        
        $this->components['preview'] = new Preview(
            $this->components['config'],
            $this->components['audio'],
            $this->components['file_manager']
        );
    }
    
    // ... other initialization methods ...
    
    public function getComponent(string $name): ?object {
        return $this->components[$name] ?? null;
    }
    
    public function getPluginFile(): string {
        return $this->pluginFile;
    }
}
```

## Hook Registration

The Hooks class should register hooks for all components:

```php
namespace Bandfront\Core;

class Hooks {
    private Bootstrap $bootstrap;
    
    public function __construct(Bootstrap $bootstrap) {
        $this->bootstrap = $bootstrap;
        $this->registerHooks();
    }
    
    private function registerHooks(): void {
        // Plugin lifecycle
        register_activation_hook($this->bootstrap->getPluginFile(), [$this, 'activate']);
        register_deactivation_hook($this->bootstrap->getPluginFile(), [$this, 'deactivate']);
        
        // Core hooks
        add_action('plugins_loaded', [$this, 'pluginsLoaded']);
        add_action('init', [$this, 'init']);
        
        // Component-specific hooks
        $this->registerPlayerHooks();
        $this->registerAudioHooks();
        $this->registerAdminHooks();
        $this->registerWooCommerceHooks();
    }
    
    private function registerPlayerHooks(): void {
        $player = $this->bootstrap->getComponent('player');
        if (!$player) return;
        
        add_action('wp_enqueue_scripts', [$player, 'enqueueResources']);
        add_shortcode('bfp-player', [$player, 'renderShortcode']);
    }
    
    // ... other hook registration methods ...
}
```

## Component Dependencies

### Dependency Map

| Component | Dependencies |
|-----------|-------------|
| Config | None |
| FileManager | Config |
| Renderer | Config, FileManager |
| Audio | Config, FileManager |
| Player | Config, Renderer, Audio |
| Admin | Config, FileManager, Renderer |
| WooCommerceIntegration | Config, Player, Renderer |
| ProductProcessor | Config, Audio, FileManager |
| FormatDownloader | Config, FileManager |
| StreamController | Config, Audio, FileManager |
| Analytics | Config |
| Preview | Config, Audio, FileManager |

### Initialization Order

1. Config (no dependencies)
2. FileManager (needs Config)
3. Renderer (needs Config, FileManager)
4. Audio (needs Config, FileManager)
5. Player (needs Config, Renderer, Audio)
6. Everything else (order doesn't matter)
7. Hooks (must be last)

## Usage Example

After Bootstrap initialization, components can be accessed:

```php
// In any file that needs a component
$bootstrap = Bootstrap::getInstance();
$player = $bootstrap->getComponent('player');
$config = $bootstrap->getComponent('config');
```
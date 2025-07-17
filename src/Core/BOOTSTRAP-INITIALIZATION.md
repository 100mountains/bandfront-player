# Bootstrap Initialization System

## Overview

The Bootstrap system is the central initialization point for the Bandfront Player plugin. It implements a singleton pattern and manages all component dependencies, ensuring proper initialization order and context-aware loading.

## Initialization Flow

### 1. Entry Point

```
BandfrontPlayer.php
    ↓
Bootstrap::init(BFP_PLUGIN_PATH)
    ↓
Bootstrap singleton created
    ↓
boot() method called
```

### 2. Boot Sequence

```php
private function boot(): void {
    // 1. Enable debugging if needed
    if (defined('WP_DEBUG') && WP_DEBUG) {
        Debug::enable();
    }
    
    // 2. Initialize components in dependency order
    $this->initializeCore();      // No dependencies
    $this->initializeAudio();     // Depends on Core
    
    // 3. Context-specific components
    $this->initializeAdmin();     // Only in admin
    $this->initializeWooCommerce(); // Only if WooCommerce active
    $this->initializeREST();      // Delayed until rest_api_init
    
    // 4. Register hooks last
    $this->components['hooks'] = new Hooks($this);
    
    // 5. Allow extensions
    do_action('bandfront_player_initialized', $this);
}
```

## Component Initialization Order

### Phase 1: Core Components (Always Loaded)

| Order | Component | Dependencies | Purpose |
|-------|-----------|--------------|---------|
| 1 | Config | None | Settings and state management |
| 2 | FileManager | Config | File operations and storage |
| 3 | Renderer | Config, FileManager | HTML generation and templating |

```php
private function initializeCore(): void {
    // 1. Configuration must be first
    $this->components['config'] = new Config();
    
    // 2. Storage depends on config
    $this->components['file_manager'] = new FileManager(
        $this->components['config']
    );
    
    // 3. Renderer needs both config and file manager
    $this->components['renderer'] = new Renderer(
        $this->components['config'],
        $this->components['file_manager']
    );
}
```

### Phase 2: Audio Components (Always Loaded)

| Order | Component | Dependencies | Purpose |
|-------|-----------|--------------|---------|
| 4 | Audio | Config, FileManager | Core audio processing |
| 5 | Player | Config, Renderer, Audio | Player UI and functionality |
| 6 | Analytics | Config | Playback tracking |
| 7 | Preview | Config, Audio, FileManager | Preview generation |

```php
private function initializeAudio(): void {
    // 4. Audio engine needs config and files
    $this->components['audio'] = new Audio(
        $this->components['config'],
        $this->components['file_manager']
    );
    
    // 5. Player needs config, renderer, and audio
    $this->components['player'] = new Player(
        $this->components['config'],
        $this->components['renderer'],
        $this->components['audio']
    );
    
    // 6. Analytics only needs config
    $this->components['analytics'] = new Analytics(
        $this->components['config']
    );
    
    // 7. Preview needs config, audio, and files
    $this->components['preview'] = new Preview(
        $this->components['config'],
        $this->components['audio'],
        $this->components['file_manager']
    );
}
```

### Phase 3: Context-Specific Components

#### Admin Components (Only in Admin)

```php
private function initializeAdmin(): void {
    if (!is_admin()) {
        return; // Skip if not in admin
    }
    
    // Delay to ensure WordPress admin is ready
    add_action('init', function() {
        if (!isset($this->components['admin'])) {
            $this->components['admin'] = new Admin(
                $this->components['config'],
                $this->components['file_manager'],
                $this->components['renderer']
            );
        }
    }, 1); // Priority 1 = early
}
```

#### WooCommerce Components (Only if Active)

```php
private function initializeWooCommerce(): void {
    if (!$this->isWooCommerceActive()) {
        return; // Skip if WooCommerce not active
    }
    
    // Main integration
    $this->components['woocommerce'] = new WooCommerceIntegration(
        $this->components['config'],
        $this->components['player'],
        $this->components['renderer']
    );
    
    // Product audio processing
    $this->components['product_processor'] = new ProductProcessor(
        $this->components['config'],
        $this->components['audio'],
        $this->components['file_manager']
    );
    
    // Download handling
    $this->components['format_downloader'] = new FormatDownloader(
        $this->components['config'],
        $this->components['file_manager']
    );
}
```

#### REST Components (Delayed Loading)

```php
private function initializeREST(): void {
    // Wait for REST API to be ready
    add_action('rest_api_init', function() {
        $this->components['stream_controller'] = new StreamController(
            $this->components['config'],
            $this->components['audio'],
            $this->components['file_manager']
        );
        
        // Register routes immediately
        $this->components['stream_controller']->registerRoutes();
    });
}
```

### Phase 4: Hook Registration (Always Last)

```php
// Hooks must be registered after all components are ready
$this->components['hooks'] = new Hooks($this);
```

## Singleton Pattern Implementation

```php
class Bootstrap {
    private static ?Bootstrap $instance = null;
    
    // Private constructor prevents direct instantiation
    private function __construct(string $pluginFile) {
        $this->pluginFile = $pluginFile;
    }
    
    // Single point of entry
    public static function init(string $pluginFile): void {
        if (self::$instance === null) {
            self::$instance = new self($pluginFile);
            self::$instance->boot();
        }
    }
    
    // Get instance for component access
    public static function getInstance(): ?self {
        return self::$instance;
    }
}
```

## Component Access Pattern

```php
// Anywhere in the plugin after initialization
$bootstrap = Bootstrap::getInstance();

// Get specific component
$player = $bootstrap->getComponent('player');
$config = $bootstrap->getComponent('config');

// Check if component exists
if ($woocommerce = $bootstrap->getComponent('woocommerce')) {
    // WooCommerce is active and component is loaded
}
```

## Dependency Resolution

### Dependency Tree

```
Config (no dependencies)
├── FileManager
│   └── Renderer
│       └── Player
│       └── Admin
│       └── WooCommerceIntegration
├── Audio
│   ├── Player
│   ├── Preview
│   ├── ProductProcessor
│   └── StreamController
└── Analytics
```

### Why This Order Matters

1. **Config First**: All components need settings
2. **FileManager Second**: Many components handle files
3. **Renderer Third**: UI components need rendering
4. **Audio Fourth**: Player needs audio processing
5. **Player Fifth**: Depends on most core components
6. **Context Components**: Load based on environment
7. **Hooks Last**: Need all components ready

## Error Handling

### Component Loading Failures

```php
public function getComponent(string $name): ?object {
    return $this->components[$name] ?? null;
}
```

- Returns `null` if component not loaded
- Allows graceful degradation
- Components check dependencies exist

### Context Detection

```php
private function isWooCommerceActive(): bool {
    return class_exists('WooCommerce') || function_exists('WC');
}
```

- Multiple detection methods
- Prevents errors if plugin deactivated
- Components only load when needed

## Performance Considerations

### Lazy Loading

- Admin components delayed until `init`
- REST components delayed until `rest_api_init`
- WooCommerce components skipped if not active

### Memory Efficiency

- Components stored by reference
- Singleton prevents duplicate instances
- Context checking prevents unnecessary loads

## Extension Points

### For Developers

```php
// Hook into initialization complete
add_action('bandfront_player_initialized', function($bootstrap) {
    // Access any component
    $player = $bootstrap->getComponent('player');
    
    // Add custom functionality
});
```

### Component Registration

Future components can be added:

```php
private function initializeCustom(): void {
    $this->components['custom'] = new CustomComponent(
        $this->components['config'],
        // ... other dependencies
    );
}
```

## Debugging

### Enable Debug Mode

```php
// In wp-config.php
define('WP_DEBUG', true);
```

### Component Status

```php
// Check loaded components
$bootstrap = Bootstrap::getInstance();
var_dump(array_keys($bootstrap->components));
```

### Initialization Timing

- Core: Immediate
- Audio: Immediate
- Admin: Delayed to `init` hook
- WooCommerce: Immediate if active
- REST: Delayed to `rest_api_init`
- Hooks: After all components

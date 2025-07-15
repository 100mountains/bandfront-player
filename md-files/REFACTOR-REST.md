# Bandfront Player Modern Refactoring Plan

## Overview

This document combines the architectural insights from both refactoring approaches to create a comprehensive modernization plan for the Bandfront Player that follows our established rules and patterns.

## Core Objectives

1. **Separate File Operations**: Move all file-handling code into the enhanced Files utility class
2. **Audio Engine Modernization**: Create a clean PlayerEngine module for different audio implementations
3. **Context-Aware Rendering**: Implement a Renderer module for different display contexts
4. **REST API Streaming**: Replace query parameters with WordPress REST API endpoints
5. **Analytics Integration**: Clean hooks-based integration with bandfront-analytics plugin

## File Structure (Following Our Rules)

```
src/
│ StreamController.php  # REST API for audio streaming
│ Player.php # Audio engine implementations (MediaElement, WaveSurfer, HTML5)
│ Renderer.php     # Context-aware rendering (standard, widget, WooCommerce)
├── Utils/
│   ├── Files.php # Enhanced with file operations AND URL generation
│   └── [existing utils]  # Keep other utility classes unchanged
└── Views/
    └── [existing views]        # Templates remain unchanged
```

## Implementation Plan

### Phase 1: Enhanced Files Class (2-3 days)

**Goal**: Consolidate all file operations and URL generation in one place

```php
// Add these methods to existing Files class:

/**
 * Get streaming URL for a file (transitional - will use REST later)
 */
public function getStreamingUrl(int $productId, string $fileIndex): string {
    // Current query parameter approach (will be replaced)
    return add_query_arg([
        'bfp-action' => 'play',
        'bfp-product' => $productId,
        'bfp-file' => $fileIndex
    ], site_url());
}

/**
 * Process cloud storage URLs (moved from Cloud class)
 */
public function processCloudUrl(string $url): string {
    if (strpos($url, 'drive.google.com') !== false) {
        return $this->getGoogleDriveUrl($url);
    }
    return $url;
}

/**
 * Get file path for a product file (moved from Audio class)
 */
public function getFilePath(int $productId, string $fileIndex): string {
    // Implementation from existing Audio class
}

/**
 * Get or create demo file (moved from Audio class)
 */
public function getDemoFile(string $originalPath, int $percent): string {
    // Implementation from existing Audio class
}

/**
 * Stream file with range request support (moved from Audio class)
 */
public function streamFile(string $filePath, array $options = []): void {
    // Implementation from existing Audio class
}
```

**Testing**: Verify URL generation and file operations work correctly

### Phase 2: Audio Engine Module (2-3 days)

**Goal**: Separate audio engine logic from Player class

```php
<?php
namespace bfp\Modules;

class PlayerEngine {
    private \bfp\Plugin $plugin;
    
    public function __construct(\bfp\Plugin $plugin) {
        $this->plugin = $plugin;
    }
    
    /**
     * Render player based on selected engine type
     */
    public function render(string $audioUrl, array $config): string {
        $engineType = $this->plugin->getConfig()->getState(
            '_bfp_audio_engine', 
            'mediaelement', 
            $config['product_id'] ?? null
        );
        
        switch ($engineType) {
            case 'wavesurfer':
                if ($this->isWavesurferAvailable()) {
                    return $this->renderWavesurfer($audioUrl, $config);
                }
                // Fall through if not available
                
            case 'html5':
                return $this->renderHTML5($audioUrl, $config);
                
            case 'mediaelement':
            default:
                return $this->renderMediaElement($audioUrl, $config);
        }
    }
    
    /**
     * Get assets to enqueue for engine type
     */
    public function getAssets(string $engineType = null): array {
        // Implementation following our asset enqueuing patterns
    }
    
    // Private render methods for each engine
    private function renderMediaElement(string $audioUrl, array $config): string {
        // Move implementation from existing Player class
    }
    
    private function renderWavesurfer(string $audioUrl, array $config): string {
        // Move implementation from existing Player class
    }
    
    private function renderHTML5(string $audioUrl, array $config): string {
        // Fallback implementation
    }
}
```

**Registration in Plugin.php**:
```php
// Add to initComponents()
private function initComponents(): void {
    // Existing components...
    $this->playerEngine = new Modules\PlayerEngine($this);
}

// Add getter
public function getPlayerEngine(): Modules\PlayerEngine {
    return $this->playerEngine;
}
```

**Testing**: Verify all audio engines work correctly

### Phase 3: Renderer Module (2-3 days)

**Goal**: Context-aware rendering without code duplication

```php
<?php
namespace bfp\Modules;

class Renderer {
    private \bfp\Plugin $plugin;
    
    public function __construct(\bfp\Plugin $plugin) {
        $this->plugin = $plugin;
    }
    
    /**
     * Render player for specific context
     */
    public function render(string $audioUrl, array $config): string {
        $context = $config['context'] ?? 'standard';
        
        switch ($context) {
            case 'widget':
                return $this->renderWidgetPlayer($audioUrl, $config);
                
            case 'woocommerce':
                return $this->renderWooCommercePlayer($audioUrl, $config);
                
            case 'standard':
            default:
                return $this->renderStandardPlayer($audioUrl, $config);
        }
    }
    
    /**
     * Get assets for context
     */
    public function getAssets(string $context = 'standard'): array {
        // Context-specific asset loading
    }
    
    // Context-specific rendering methods
    private function renderStandardPlayer(string $audioUrl, array $config): string {
        // Get engine HTML
        $engineHtml = $this->plugin->getPlayerEngine()->render($audioUrl, $config);
        
        // Add standard wrapper
        return $engineHtml;
    }
    
    private function renderWidgetPlayer(string $audioUrl, array $config): string {
        // Widget-specific implementation
    }
    
    private function renderWooCommercePlayer(string $audioUrl, array $config): string {
        // WooCommerce-specific implementation
    }
}
```

**Update Player.php**:
```php
// Add renderer instance
private ?Modules\Renderer $renderer = null;

private function getRenderer(): Modules\Renderer {
    if ($this->renderer === null) {
        $this->renderer = new Modules\Renderer($this->mainPlugin);
    }
    return $this->renderer;
}

// Update getPlayer method
public function getPlayer(string $audioUrl, array $args = []): string {
    // Enqueue assets
    $this->enqueueAssets(
        $this->mainPlugin->getPlayerEngine()->getAssets(),
        $this->getRenderer()->getAssets($args['context'] ?? 'standard')
    );
    
    // Return rendered player
    return $this->getRenderer()->render($audioUrl, $args);
}
```

**Testing**: Verify rendering in all contexts (standard, widget, WooCommerce)

### Phase 4: REST API Implementation (3-4 days)

**Goal**: Modern WordPress REST API streaming with security

```php
<?php
namespace bfp\Modules;

class StreamController {
    private \bfp\Plugin $plugin;
    
    public function __construct(\bfp\Plugin $plugin) {
        $this->plugin = $plugin;
    }
    
    /**
     * Register REST routes
     */
    public function register(): void {
        add_action('rest_api_init', function() {
            register_rest_route('bandfront-player/v1', '/stream/(?P<product_id>\d+)/(?P<file_index>[^/]+)', [
                'methods' => 'GET',
                'callback' => [$this, 'streamFile'],
                'permission_callback' => [$this, 'checkPermission'],
                'args' => [
                    'product_id' => [
                        'required' => true,
                        'validate_callback' => 'is_numeric',
                        'sanitize_callback' => 'absint',
                    ],
                    'file_index' => [
                        'required' => true,
                        'sanitize_callback' => 'sanitize_key',
                    ],
                ],
            ]);
        });
    }
    
    /**
     * Stream file with proper security
     */
    public function streamFile(\WP_REST_Request $request): void {
        $productId = (int)$request->get_param('product_id');
        $fileIndex = $request->get_param('file_index');
        
        // Analytics integration
        do_action('bfp_track_play', $productId, $fileIndex);
        
        // Get file path
        $filePath = $this->plugin->getFiles()->getFilePath($productId, $fileIndex);
        
        // Check access level and serve appropriate file
        $isPurchased = $this->plugin->isPurchased($productId);
        if (!$isPurchased && $this->plugin->getConfig()->getState('_bfp_secure_player', 0, $productId)) {
            $percent = $this->plugin->getConfig()->getState('_bfp_file_percent', 30, $productId);
            $filePath = $this->plugin->getFiles()->getDemoFile($filePath, $percent);
        }
        
        // Stream with range support
        $this->plugin->getFiles()->streamFile($filePath);
        exit;
    }
    
    /**
     * Check streaming permissions
     */
    public function checkPermission(\WP_REST_Request $request): bool {
        $productId = (int)$request->get_param('product_id');
        
        // Admin access
        if (current_user_can('manage_options')) {
            return true;
        }
        
        // Purchase check
        if ($this->plugin->isPurchased($productId)) {
            return true;
        }
        
        // Public previews
        return (bool)$this->plugin->getConfig()->getState('_bfp_public_previews', false);
    }
}
```

**Update Files class for REST URLs**:
```php
// Update getStreamingUrl method
public function getStreamingUrl(int $productId, string $fileIndex): string {
    // Use REST API URL
    return rest_url("bandfront-player/v1/stream/{$productId}/{$fileIndex}");
}
```

**Register in Plugin.php**:
```php
// Add to initComponents()
$this->streamController = new Modules\StreamController($this);
$this->streamController->register();
```

**Testing**: Test API endpoints and security

### Phase 5: Analytics Integration (1-2 days)

**Goal**: Clean hooks-based integration with bandfront-analytics

```php
// Add to Plugin.php init() method
public function init(): void {
    // Existing initialization...
    
    // Analytics integration hooks
    add_action('bfp_track_play', function($productId, $fileIndex) {
        if (function_exists('bandfront_analytics_track_event')) {
            bandfront_analytics_track_event('music_play', $productId, [
                'file_index' => $fileIndex,
                'user_id' => get_current_user_id()
            ]);
        }
    }, 10, 2);
}
```

**Testing**: Verify analytics events are recorded

### Phase 6: JavaScript Modernization (2-3 days)

**Goal**: Modern JavaScript following WordPress 2025 patterns

```javascript
// Enhanced engine.js with modern features

class BandfrontPlayer {
    constructor(config = {}) {
        this.config = {
            apiBase: wpApiSettings.root + 'bandfront-player/v1',
            ...config
        };
        
        this.players = new Map();
        this.init();
    }
    
    async init() {
        // Use Intersection Observer for lazy loading
        this.setupLazyLoading();
        
        // Initialize visible players
        this.initializeVisiblePlayers();
    }
    
    setupLazyLoading() {
        if (!('IntersectionObserver' in window)) return;
        
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    this.initializePlayer(entry.target);
                    observer.unobserve(entry.target);
                }
            });
        }, { rootMargin: '100px' });
        
        document.querySelectorAll('.bfp-player[data-lazy="true"]').forEach(element => {
            observer.observe(element);
        });
    }
    
    async generateAudioUrl(productId, fileIndex) {
        return `${this.config.apiBase}/stream/${productId}/${fileIndex}`;
    }
}

// Initialize when ready
document.addEventListener('DOMContentLoaded', () => {
    window.bandfrontPlayer = new BandfrontPlayer(window.bfpConfig || {});
});
```

### Phase 7: Final Integration & Cleanup (2-3 days)

**Goal**: Clean up legacy code and ensure everything works together

1. **Clean up Audio class**:
   - Remove file operations (now in Files)
   - Remove URL generation (now in Files)
   - Keep only core audio processing

2. **Clean up Player class**:
   - Remove engine-specific code (now in PlayerEngine)
   - Remove rendering logic (now in Renderer)
   - Focus on coordination

3. **Comprehensive testing**:
   - All contexts (standard, widget, WooCommerce)
   - All audio engines (MediaElement, WaveSurfer, HTML5)
   - Security and permissions
   - Analytics integration

## Key Benefits

1. **WordPress 2025 Compliant**: Uses REST API, modern JavaScript, proper security
2. **Follows Our Rules**: Respects existing folder structure and coding standards
3. **Maintains Functionality**: All existing features preserved
4. **Better Security**: Proper authentication, nonce verification, rate limiting
5. **Improved Performance**: Lazy loading, asset optimization, caching
6. **Analytics Ready**: Clean integration with bandfront-analytics plugin
7. **Maintainable**: Clear separation of concerns, testable components

## Timeline Summary

- **Phase 1**: Enhanced Files Class (2-3 days)
- **Phase 2**: Audio Engine Module (2-3 days) 
- **Phase 3**: Renderer Module (2-3 days)
- **Phase 4**: REST API (3-4 days)
- **Phase 5**: Analytics Integration (1-2 days)
- **Phase 6**: JavaScript Modernization (2-3 days)
- **Phase 7**: Final Integration (2-3 days)

**Total**: 14-21 days

This approach maintains your existing structure while providing a modern, maintainable foundation that follows WordPress 2025 best practices and integrates cleanly with your analytics plugin.
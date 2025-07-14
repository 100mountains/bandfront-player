## Design

Bandfront Player Refactoring Goals

I want to modernize the Bandfront Player plugin by properly separating concerns while maintaining all existing functionality. Here's what I want to achieve:

Core Objectives
Separate File Operations: Move all file-handling code (file processing, demo creation, streaming) into a dedicated Files class for better organization.

Improve Audio Engine Architecture: Separate audio engine logic (MediaElement, WaveSurfer, HTML5 fallback) from the Player class into a dedicated system that handles the different rendering approaches.

Context-Aware Rendering: Create a proper rendering system that handles different contexts (standard players, widgets, WooCommerce product pages) without duplicating code.

Modernize Audio Streaming: Replace the current query parameter approach (?bfp-action=play) with WordPress REST API endpoints for better security, cleaner URLs, and improved compatibility with caching plugins.

Improve Analytics Integration: Create a cleaner way to integrate with the Bandfront Analytics plugin using WordPress hooks and actions.

Implementation Approach
Incremental Changes: Implement changes in small, testable phases that can be verified before moving to the next step.

Ensure existing functionality continues to work throughout the refactoring process.

Simple Structure: Avoid overengineering with excessive abstractions or factory patterns - focus on simple, clear separation of concerns.

Security First: Ensure the REST API implementation maintains or improves the existing security model for protecting premium content.

When completed, the plugin will have:

A clean, modern architecture following WordPress 2025 best practices
Properly separated concerns making future maintenance easier
REST API endpoints for audio streaming with improved security
Better integration with other Bandfront plugins
More maintainable codebase that's easier to extend

The refactoring should maintain all existing features and user experience while improving the internal architecture for better long-term maintenance.

# Streamlined Implementation Plan for Bandfront Player

## Simplified File Structure

src/
├── Utils/
│   ├── Files.php               # Enhanced with file operations and URL generation
│   └── [existing utils]        # Keep other utility classes
├── Modules/
│   ├── StreamController.php    # REST API for audio streaming
│   ├── PlayerController.php    # REST API for player configuration
│   ├── PlayerEngine.php        # Audio engine implementations
│   └── Renderer.php            # Context-aware rendering
└── Views/
    └── [existing views]        # Templates remain as-is


## Step-by-Step Implementation

### Phase 1: File Operations and URL Generation (2-3 days)

1. **Enhance Files Class with URL Generation**
   ```php
   // filepath: /var/www/html/wp-content/plugins/bandfront-player/src/Utils/Files.php
   <?php
   namespace bfp\Utils;
   
   class Files {
       private \bfp\Plugin $plugin;
       
       public function __construct(\bfp\Plugin $plugin) {
           $this->plugin = $plugin;
       }
       
       /**
        * Get streaming URL for a file (will eventually be replaced by REST)
        */
       public function getStreamingUrl(int $productId, string $fileIndex): string {
           // Current implementation from Audio::generateAudioUrl()
           // This will eventually be replaced with REST endpoints
           return add_query_arg([
               'bfp-action' => 'play',
               'bfp-product' => $productId,
               'bfp-file' => $fileIndex
           ], site_url());
       }
       
       /**
        * Process cloud storage URLs (Google Drive, etc.)
        */
       public function processCloudUrl(string $url): string {
           // Move implementation from Cloud class
           if (strpos($url, 'drive.google.com') !== false) {
               return $this->getGoogleDriveUrl($url);
           }
           return $url;
       }
       
       /**
        * Get file path for a product file
        */
       public function getFilePath(int $productId, string $fileIndex): string {
           // Implementation from existing code
       }
       
       /**
        * Get or create demo file
        */
       public function getDemoFile(string $originalPath, int $percent): string {
           // Implementation from existing code
       }
       
       /**
        * Stream file with range request support
        */
       public function streamFile(string $filePath): void {
           // Implementation from Audio class
       }
       
       // Helper methods
       private function getGoogleDriveUrl(string $url): string {
           // Implementation from Cloud class
       }
   }
   ```

2. **Verify Files Class** - Test URL generation and file operations

### Phase 2: Audio Engine (2-3 days)

1. **Create PlayerEngine Class**
   ```php
   // filepath: /var/www/html/wp-content/plugins/bandfront-player/src/Audio/PlayerEngine.php
   <?php
   namespace bfp\Audio;
   
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
           
           // Try selected engine first, fall back if needed
           switch ($engineType) {
               case 'wavesurfer':
                   if ($this->isWavesurferAvailable()) {
                       return $this->renderWavesurfer($audioUrl, $config);
                   }
                   // Fall through to default if not available
                   
               case 'html5':
                   return $this->renderHTML5($audioUrl, $config);
                   
               case 'mediaelement':
               default:
                   return $this->renderMediaElement($audioUrl, $config);
           }
       }
       
       /**
        * Get assets to enqueue based on engine type
        */
       public function getAssets(string $engineType = null): array {
           if ($engineType === null) {
               $engineType = $this->plugin->getConfig()->getState('_bfp_audio_engine', 'mediaelement');
           }
           
           switch ($engineType) {
               case 'wavesurfer':
                   return [
                       'scripts' => ['wavesurfer'],
                       'styles' => []
                   ];
                   
               case 'html5':
                   return [
                       'scripts' => [],
                       'styles' => []
                   ];
                   
               case 'mediaelement':
               default:
                   return [
                       'scripts' => ['wp-mediaelement'],
                       'styles' => ['wp-mediaelement']
                   ];
           }
       }
       
       // Private render methods for each engine type
       private function renderMediaElement(string $audioUrl, array $config): string {
           // Implementation - move from existing player rendering code
       }
       
       private function renderWavesurfer(string $audioUrl, array $config): string {
           // Implementation - move from existing player rendering code
       }
       
       private function renderHTML5(string $audioUrl, array $config): string {
           // Implementation - move from existing player rendering code
       }
       
       private function isWavesurferAvailable(): bool {
           // Check if wavesurfer.js is available
           return true; // Placeholder
       }
   }
   ```

2. **Register in Plugin.php**
   ```php
   // Add to class properties
   private Audio\PlayerEngine $playerEngine;
   
   // Add to initComponents()
   $this->playerEngine = new Audio\PlayerEngine($this);
   
   // Add getter
   public function getPlayerEngine(): Audio\PlayerEngine {
       return $this->playerEngine;
   }
   ```

3. **Verify Engine** - Test different audio engines

### Phase 3: Player Rendering (2-3 days)

1. **Create Renderer Class**
   ```php
   // filepath: /var/www/html/wp-content/plugins/bandfront-player/src/Player/Renderer.php
   <?php
   namespace bfp\Player;
   
   class Renderer {
       private \bfp\Plugin $plugin;
       
       public function __construct(\bfp\Plugin $plugin) {
           $this->plugin = $plugin;
       }
       
       /**
        * Render player in appropriate context
        */
       public function render(string $audioUrl, array $config): string {
           $context = $config['context'] ?? 'standard';
           
           switch ($context) {
               case 'widget':
                   return $this->renderWidgetPlayer($audioUrl, $config);
                   
               case 'block':
                   return $this->renderBlockPlayer($audioUrl, $config);
                   
               case 'woocommerce':
                   return $this->renderWooCommercePlayer($audioUrl, $config);
                   
               case 'standard':
               default:
                   return $this->renderStandardPlayer($audioUrl, $config);
           }
       }
       
       /**
        * Get assets needed for rendering
        */
       public function getAssets(string $context = 'standard'): array {
           // Base assets needed for all contexts
           $assets = [
               'scripts' => ['bfp-player'],
               'styles' => ['bfp-player-style']
           ];
           
           // Add context-specific assets
           switch ($context) {
               case 'widget':
                   $assets['styles'][] = 'bfp-widget-style';
                   break;
                   
               case 'block':
                   $assets['scripts'][] = 'bfp-block';
                   break;
           }
           
           return $assets;
       }
       
       // Context-specific render methods
       private function renderStandardPlayer(string $audioUrl, array $config): string {
           // Get engine HTML from the audio engine
           $engineHtml = $this->plugin->getPlayerEngine()->render($audioUrl, $config);
           
           // Wrap with additional HTML if needed
           return $engineHtml;
       }
       
       private function renderWidgetPlayer(string $audioUrl, array $config): string {
           // Implementation for widget context
       }
       
       private function renderBlockPlayer(string $audioUrl, array $config): string {
           // Implementation for block editor context
       }
       
       private function renderWooCommercePlayer(string $audioUrl, array $config): string {
           // Implementation for WooCommerce context
       }
   }
   ```

2. **Update Player.php to use the new Renderer**
   ```php
   // Add to class properties
   private ?Player\Renderer $renderer = null;
   
   // Add getter method
   private function getRenderer(): Player\Renderer {
       if ($this->renderer === null) {
           $this->renderer = new Player\Renderer($this->mainPlugin);
       }
       return $this->renderer;
   }
   
   // Update getPlayer method
   public function getPlayer(string $audioUrl, array $args = []): string {
       // Ensure resources are enqueued
       $this->enqueueResources();
       
       // Enqueue assets
       $this->enqueueAssets(
           $this->mainPlugin->getPlayerEngine()->getAssets(),
           $this->getRenderer()->getAssets($args['context'] ?? 'standard')
       );
       
       // Return rendered player
       return $this->getRenderer()->render($audioUrl, $args);
   }
   ```

3. **Verify Renderer** - Test player rendering in different contexts

### Phase 4: REST API Implementation (3-4 days)

1. **Create Stream Controller**
   ```php
   // filepath: /var/www/html/wp-content/plugins/bandfront-player/src/Api/StreamController.php
   <?php
   namespace bfp\Api;
   
   class StreamController {
       private \bfp\Plugin $plugin;
       
       public function __construct(\bfp\Plugin $plugin) {
           $this->plugin = $plugin;
       }
       
       /**
        * Register REST API routes
        */
       public function register(): void {
           add_action('rest_api_init', function() {
               register_rest_route('bandfront-player/v1', '/stream/(?P<product_id>\d+)/(?P<file_index>[^/]+)', [
                   'methods' => 'GET',
                   'callback' => [$this, 'streamFile'],
                   'permission_callback' => [$this, 'checkStreamPermission'],
                   'args' => [
                       'product_id' => [
                           'required' => true,
                           'validate_callback' => 'is_numeric',
                       ],
                       'file_index' => [
                           'required' => true,
                       ],
                   ],
               ]);
           });
       }
       
       /**
        * Stream audio file
        */
       public function streamFile(\WP_REST_Request $request): void {
           $productId = (int)$request->get_param('product_id');
           $fileIndex = $request->get_param('file_index');
           
           // Track play via analytics
           do_action('bfp_track_play', $productId, $fileIndex);
           
           // Get file path
           $filePath = $this->plugin->getFiles()->getFilePath($productId, $fileIndex);
           
           // Check if user can access full file
           $isPurchased = $this->plugin->isPurchased($productId);
           if (!$isPurchased && $this->plugin->getConfig()->getState('_bfp_secure_player', 0, $productId)) {
               // Get demo file instead
               $percent = $this->plugin->getConfig()->getState('_bfp_file_percent', 30, $productId);
               $filePath = $this->plugin->getFiles()->getDemoFile($filePath, $percent);
           }
           
           // Stream the file
           $this->plugin->getFiles()->streamFile($filePath);
           exit;
       }
       
       /**
        * Check permission for streaming
        */
       public function checkStreamPermission(\WP_REST_Request $request): bool {
           $productId = (int)$request->get_param('product_id');
           
           // Public preview permission check
           if ($this->plugin->getConfig()->getState('_bfp_public_previews', false)) {
               return true;
           }
           
           // Purchase check
           if ($this->plugin->isPurchased($productId)) {
               return true;
           }
           
           // Admin access check
           return current_user_can('manage_options');
       }
   }
   ```

2. **Register Controller in Plugin**
   ```php
   // Add to class properties
   private Api\StreamController $streamController;
   
   // Add to initComponents()
   $this->streamController = new Api\StreamController($this);
   $this->streamController->register();
   ```

3. **Update Files Class for REST URLs**
   ```php
   // Update getStreamingUrl method to use REST API
   public function getStreamingUrl(int $productId, string $fileIndex): string {
       // Use REST API URL
       return rest_url("bandfront-player/v1/stream/{$productId}/{$fileIndex}");
   }
   ```

4. **Verify REST API** - Test endpoints with browser or Postman

### Phase 5: Analytics Integration (1-2 days)

1. **Add Analytics Hooks to Plugin.php**
   ```php
   // Add to init() method
   public function init(): void {
       // Existing code...
       
       // Analytics integration
       add_action('bfp_track_play', function($productId, $fileIndex) {
           // Check if analytics plugin is active
           if (function_exists('bandfront_analytics_track_event')) {
               bandfront_analytics_track_event('music_play', $productId, [
                   'file_index' => $fileIndex,
                   'user_id' => get_current_user_id()
               ]);
           }
       }, 10, 2);
       
       add_action('bfp_track_download', function($productId, $fileIndex) {
           // Check if analytics plugin is active
           if (function_exists('bandfront_analytics_track_event')) {
               bandfront_analytics_track_event('music_download', $productId, [
                   'file_index' => $fileIndex,
                   'user_id' => get_current_user_id()
               ]);
           }
       }, 10, 2);
   }
   ```

2. **Add Event Triggers**
   - In Player.php when play starts
   - In WooCommerce.php for downloads
   - In StreamController.php when streaming

3. **Verify Analytics** - Check that events are being recorded

### Phase 6: Final Integration (2-3 days)

1. **Clean up the original Audio class**
   - Remove URL generation (now in Files)
   - Remove audio engine rendering (now in PlayerEngine)
   - Remove file operations (now in Files)
   - Leave only core audio processing functionality

2. **Clean up the Player class**
   - Remove HTML generation (now in Renderer)
   - Remove engine-specific code (now in PlayerEngine)
   - Focus on being the integration point between components

3. **Comprehensive Testing**
   - Test all player functionality in different contexts
   - Verify analytics integration
   - Test with different audio engines
   - Ensure backward compatibility

## Timeline Summary

- **Phase 1 (File Operations)**: 2-3 days
- **Phase 2 (Audio Engine)**: 2-3 days
- **Phase 3 (Player Rendering)**: 2-3 days
- **Phase 4 (REST API)**: 3-4 days
- **Phase 5 (Analytics Integration)**: 1-2 days
- **Phase 6 (Final Integration)**: 2-3 days

**Total Estimated Time**: 12-18 days

This approach:
1. Keeps file operations and URL generation together in Files class
2. Maintains separation of concerns without excessive abstractions
3. Uses simple class structure with clear responsibilities
4. Prepares for eventual transition to REST API
5. Makes incremental, testable changes
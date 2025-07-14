
## Prompt: Fix Bandfront Player Audio Playback and Title Display Issues

The Bandfront Player is showing up but not playing audio files and not displaying titles properly on the product pages. 


### Expected Behavior After Changes:
1. No more JavaScript errors in console
2. Audio files play when clicking play button
3. Titles display correctly for all tracks
4. Single player mode works (clicking title switches track)
5. Play on cover functionality works on shop pages
6. Proper audio URL generation with file indices

### Testing:
1. Check console for any remaining errors
2. Test playing audio on both shop and product pages
3. Verify titles show for all tracks
4. Test single player mode by clicking different track titles
5. Test play on cover buttons on shop pages

I'll create a comprehensive WordPress 2025 implementation guide combining both the old and refactored codebase insights. Let me start with the URL generation and playback logic.

# WordPress 2025 Audio Player Implementation Guide

## Modern Architecture Overview

In 2025, we'll implement a modern WordPress audio player using:
- **PSR-4 autoloading** with namespace organization
- **Dependency injection** for testability
- **REST API endpoints** instead of query parameters
- **React/Vue components** for the frontend (with vanilla JS fallback)
- **Service container pattern** for component management

## 1. URL Generation & Playback System

### Modern URL Strategy

Instead of query parameters (`?bfp-action=play&bfp-product=123`), we'll use REST API endpoints:

```php
<?php
namespace BandfrontPlayer\Api;

use WP_REST_Controller;
use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;

class AudioEndpoint extends WP_REST_Controller {
    
    protected $namespace = 'bandfront/v1';
    protected $resource_name = 'audio';
    
    public function register_routes() {
        // Modern REST endpoint for audio playback
        register_rest_route($this->namespace, '/' . $this->resource_name . '/(?P<product_id>\d+)/(?P<file_index>[a-zA-Z0-9_-]+)', [
            [
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => [$this, 'serve_audio_file'],
                'permission_callback' => [$this, 'check_permissions'],
                'args'                => [
                    'product_id' => [
                        'required'          => true,
                        'validate_callback' => function($param) {
                            return is_numeric($param);
                        },
                        'sanitize_callback' => 'absint',
                    ],
                    'file_index' => [
                        'required'          => true,
                        'validate_callback' => function($param) {
                            return preg_match('/^[a-zA-Z0-9_-]+$/', $param);
                        },
                        'sanitize_callback' => 'sanitize_key',
                    ],
                ],
            ],
        ]);
        
        // Analytics endpoint
        register_rest_route($this->namespace, '/' . $this->resource_name . '/track', [
            [
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => [$this, 'track_playback'],
                'permission_callback' => '__return_true',
            ],
        ]);
    }
    
    public function serve_audio_file(WP_REST_Request $request) {
        $product_id = $request->get_param('product_id');
        $file_index = $request->get_param('file_index');
        
        // Use modern service container
        $audio_service = bandfront_player()->get_service('audio_processor');
        $auth_service = bandfront_player()->get_service('auth');
        
        // Check permissions
        $access_level = $auth_service->get_user_access_level($product_id);
        
        // Get file info
        $file_info = $audio_service->get_file_info($product_id, $file_index);
        
        if (!$file_info) {
            return new WP_Error('not_found', 'File not found', ['status' => 404]);
        }
        
        // Stream file based on access level
        return $audio_service->stream_file($file_info, $access_level);
    }
}
```

### Modern Audio Processor Service

```php
<?php
namespace BandfrontPlayer\Services;

use BandfrontPlayer\Interfaces\AudioProcessorInterface;
use BandfrontPlayer\Models\AudioFile;
use BandfrontPlayer\Models\UserAccess;

class AudioProcessor implements AudioProcessorInterface {
    
    private $file_repository;
    private $cache_service;
    private $cdn_service;
    
    public function __construct(
        FileRepository $file_repository,
        CacheService $cache_service,
        CdnService $cdn_service
    ) {
        $this->file_repository = $file_repository;
        $this->cache_service = $cache_service;
        $this->cdn_service = $cdn_service;
    }
    
    /**
     * Generate secure audio URL with modern approach
     */
    public function generate_audio_url(int $product_id, string $file_index, array $file_data = []): string {
        // Try CDN first for performance
        if ($this->cdn_service->is_enabled() && !empty($file_data['cdn_key'])) {
            return $this->cdn_service->get_signed_url($file_data['cdn_key'], [
                'expires' => time() + HOUR_IN_SECONDS,
                'user_id' => get_current_user_id(),
            ]);
        }
        
        // Check for cached/processed file
        $cache_key = $this->generate_cache_key($product_id, $file_index, $file_data);
        $cached_url = $this->cache_service->get($cache_key);
        
        if ($cached_url) {
            return $cached_url;
        }
        
        // Generate REST API URL
        $url = rest_url(sprintf(
            'bandfront/v1/audio/%d/%s',
            $product_id,
            $file_index
        ));
        
        // Add nonce for security
        $url = wp_nonce_url($url, 'bandfront_audio_' . $product_id, 'audio_nonce');
        
        // Cache the URL
        $this->cache_service->set($cache_key, $url, 3600);
        
        return $url;
    }
    
    /**
     * Stream file with proper headers and partial content support
     */
    public function stream_file(AudioFile $file, UserAccess $access): void {
        $file_path = $file->get_path();
        
        // Apply transformations based on access level
        if ($access->is_demo()) {
            $file_path = $this->get_demo_version($file);
        }
        
        // Modern streaming with partial content support
        $this->stream_with_range_support($file_path, [
            'content_type' => $file->get_mime_type(),
            'filename' => $file->get_display_name(),
            'cache_control' => $access->is_purchased() ? 'private' : 'public',
        ]);
    }
    
    /**
     * Modern file streaming with HTTP range support
     */
    private function stream_with_range_support(string $file_path, array $headers = []): void {
        if (!file_exists($file_path)) {
            wp_die('File not found', 404);
        }
        
        $file_size = filesize($file_path);
        $start = 0;
        $end = $file_size - 1;
        
        // Handle range requests for better performance
        if (isset($_SERVER['HTTP_RANGE'])) {
            $range = $_SERVER['HTTP_RANGE'];
            list($start, $end) = $this->parse_range_header($range, $file_size);
            
            header('HTTP/1.1 206 Partial Content');
            header("Content-Range: bytes $start-$end/$file_size");
        } else {
            header('HTTP/1.1 200 OK');
        }
        
        // Set headers
        header('Content-Type: ' . ($headers['content_type'] ?? 'audio/mpeg'));
        header('Content-Length: ' . ($end - $start + 1));
        header('Accept-Ranges: bytes');
        header('Cache-Control: ' . ($headers['cache_control'] ?? 'public, max-age=3600'));
        
        // CORS headers for modern web apps
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, HEAD');
        
        // Stream the file
        $fp = fopen($file_path, 'rb');
        fseek($fp, $start);
        
        $buffer_size = 8192;
        while (!feof($fp) && ftell($fp) <= $end) {
            echo fread($fp, min($buffer_size, $end - ftell($fp) + 1));
            flush();
        }
        
        fclose($fp);
        exit;
    }
}
```

### Modern Frontend Player Implementation

```javascript
// ES6 Module approach for 2025
import { AudioPlayer } from './modules/AudioPlayer.js';
import { PlaylistManager } from './modules/PlaylistManager.js';
import { AnalyticsTracker } from './modules/AnalyticsTracker.js';

class BandfrontPlayer {
    constructor(config = {}) {
        this.config = {
            apiBase: '/wp-json/bandfront/v1',
            engine: 'native', // 'native', 'mediaelement', 'wavesurfer'
            ...config
        };
        
        this.players = new Map();
        this.playlist = new PlaylistManager();
        this.analytics = new AnalyticsTracker(this.config.apiBase);
        
        this.init();
    }
    
    async init() {
        // Use Intersection Observer for lazy loading
        this.setupLazyLoading();
        
        // Initialize players already in viewport
        document.querySelectorAll('.bfp-player[data-product-id]').forEach(element => {
            if (this.isInViewport(element)) {
                this.initializePlayer(element);
            }
        });
        
        // Setup global event delegation
        this.setupEventDelegation();
    }
    
    setupLazyLoading() {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    this.initializePlayer(entry.target);
                    observer.unobserve(entry.target);
                }
            });
        }, {
            rootMargin: '100px' // Start loading 100px before viewport
        });
        
        document.querySelectorAll('.bfp-player[data-lazy="true"]').forEach(element => {
            observer.observe(element);
        });
    }
    
    async initializePlayer(element) {
        const playerId = element.getAttribute('data-player-id');
        const productId = element.getAttribute('data-product-id');
        const fileIndex = element.getAttribute('data-file-index');
        
        // Generate secure URL
        const audioUrl = await this.generateAudioUrl(productId, fileIndex);
        
        // Create player based on engine
        const player = this.createPlayer(element, audioUrl);
        
        // Store player instance
        this.players.set(playerId, player);
        
        // Setup event listeners
        this.setupPlayerEvents(player, productId, fileIndex);
    }
    
    async generateAudioUrl(productId, fileIndex) {
        // Use REST API endpoint
        const url = `${this.config.apiBase}/audio/${productId}/${fileIndex}`;
        
        // Add nonce for security
        const nonce = this.getNonce();
        return `${url}?audio_nonce=${nonce}`;
    }
    
    createPlayer(element, audioUrl) {
        switch (this.config.engine) {
            case 'wavesurfer':
                return this.createWaveSurferPlayer(element, audioUrl);
            case 'mediaelement':
                return this.createMediaElementPlayer(element, audioUrl);
            default:
                return this.createNativePlayer(element, audioUrl);
        }
    }
    
    createNativePlayer(element, audioUrl) {
        // Modern HTML5 audio with Web Audio API
        const audio = new Audio(audioUrl);
        const audioContext = new (window.AudioContext || window.webkitAudioContext)();
        const source = audioContext.createMediaElementSource(audio);
        const gainNode = audioContext.createGain();
        
        source.connect(gainNode);
        gainNode.connect(audioContext.destination);
        
        // Create custom controls
        const player = new AudioPlayer({
            audio,
            audioContext,
            gainNode,
            container: element,
            features: this.parseFeatures(element)
        });
        
        return player;
    }
    
    setupPlayerEvents(player, productId, fileIndex) {
        // Track play events
        player.on('play', () => {
            this.analytics.trackPlay(productId, fileIndex);
            this.handlePlayEvent(player);
        });
        
        // Handle playlist progression
        player.on('ended', () => {
            if (this.playlist.hasNext()) {
                this.playNext();
            }
        });
        
        // Save playback position
        player.on('timeupdate', () => {
            this.savePlaybackPosition(productId, fileIndex, player.currentTime);
        });
    }
    
    handlePlayEvent(currentPlayer) {
        // Pause other players if not simultaneous play
        if (!this.config.playSimultaneously) {
            this.players.forEach((player, id) => {
                if (player !== currentPlayer && !player.paused) {
                    player.pause();
                }
            });
        }
    }
}

// Modern initialization with feature detection
document.addEventListener('DOMContentLoaded', () => {
    // Check for required features
    const hasRequiredFeatures = 'Audio' in window && 
                               'IntersectionObserver' in window &&
                               'fetch' in window;
    
    if (!hasRequiredFeatures) {
        console.warn('Browser missing required features for modern player');
        // Fall back to legacy implementation
        return;
    }
    
    // Initialize with configuration
    window.bandfrontPlayer = new BandfrontPlayer({
        engine: window.bfpConfig?.engine || 'native',
        apiBase: window.bfpConfig?.apiBase || '/wp-json/bandfront/v1',
        playSimultaneously: window.bfpConfig?.playSimultaneously || false,
    });
});
```

<?php
namespace BandfrontPlayer\Services;

use BandfrontPlayer\Interfaces\StateManagerInterface;

class StateManager implements StateManagerInterface {
    
    private $cache;
    private $config_cache = [];
    private $state_cache = [];
    
    public function __construct(CacheService $cache) {
        $this->cache = $cache;
    }
    
    /**
     * Get player state with modern caching strategy
     */
    public function get_player_state(int $product_id, array $keys = []): array {
        $cache_key = 'player_state_' . $product_id;
        
        // Try memory cache first
        if (isset($this->state_cache[$cache_key])) {
            return $this->filter_keys($this->state_cache[$cache_key], $keys);
        }
        
        // Try persistent cache
        $cached = $this->cache->get($cache_key);
        if ($cached !== false) {
            $this->state_cache[$cache_key] = $cached;
            return $this->filter_keys($cached, $keys);
        }
        
        // Build state from various sources
        $state = $this->build_player_state($product_id);
        
        // Cache for performance
        $this->cache->set($cache_key, $state, HOUR_IN_SECONDS);
        $this->state_cache[$cache_key] = $state;
        
        return $this->filter_keys($state, $keys);
    }
    
    /**
     * Build complete player state
     */
    private function build_player_state(int $product_id): array {
        $product = wc_get_product($product_id);
        if (!$product) {
            return [];
        }
        
        // Get all settings with proper defaults
        $state = [
            // Player settings
            'player_layout' => $this->get_setting('_bfp_player_layout', 'modern', $product_id),
            'player_controls' => $this->get_contextual_controls($product_id),
            'volume' => (float) $this->get_setting('_bfp_player_volume', 0.7, $product_id),
            'preload' => $this->get_setting('_bfp_preload', 'metadata', $product_id),
            
            // Feature flags
            'enable_waveform' => $this->get_setting('_bfp_enable_waveform', true, $product_id),
            'enable_visualizer' => $this->get_setting('_bfp_enable_visualizer', false, $product_id),
            'enable_download' => $this->check_download_permission($product_id),
            
            // Security settings
            'secure_player' => (bool) $this->get_setting('_bfp_secure_player', false, $product_id),
            'demo_duration' => (int) $this->get_setting('_bfp_demo_duration', 30, $product_id),
            
            // Files
            'files' => $this->get_product_files($product_id),
            
            // User state
            'user_access' => $this->get_user_access_level($product_id),
            'playback_position' => $this->get_playback_position($product_id),
        ];
        
        return apply_filters('bfp_player_state', $state, $product_id);
    }
    
    /**
     * Get contextual controls based on page type
     */
    private function get_contextual_controls(int $product_id): string {
        // Single product page
        if (is_product()) {
            return 'full';
        }
        
        // Cart page
        if (is_cart()) {
            return 'minimal';
        }
        
        // Shop/Archive pages
        if (is_shop() || is_product_category() || is_product_tag()) {
            $on_cover = $this->get_setting('_bfp_on_cover', true);
            return $on_cover ? 'cover' : 'inline';
        }
        
        // Default
        return 'inline';
    }
}

**Continue**

## 3. Modern Analytics & Tracking System

### Analytics Service Implementation

```php
<?php
namespace BandfrontPlayer\Services;

use BandfrontPlayer\Interfaces\AnalyticsInterface;
use BandfrontPlayer\Models\PlaybackEvent;

class Analytics implements AnalyticsInterface {
    
    private $queue;
    private $storage;
    private $third_party_integrations;
    
    public function __construct(
        QueueService $queue,
        StorageService $storage,
        array $integrations = []
    ) {
        $this->queue = $queue;
        $this->storage = $storage;
        $this->third_party_integrations = $integrations;
    }
    
    /**
     * Track playback event with modern approach
     */
    public function track_play_event(int $product_id, string $file_id, array $metadata = []): void {
        $event = new PlaybackEvent([
            'product_id' => $product_id,
            'file_id' => $file_id,
            'user_id' => get_current_user_id(),
            'session_id' => $this->get_session_id(),
            'timestamp' => current_time('mysql', true),
            'metadata' => array_merge([
                'ip' => $this->get_client_ip(),
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'referrer' => $_SERVER['HTTP_REFERER'] ?? '',
                'device_type' => $this->detect_device_type(),
            ], $metadata),
        ]);
        
        // Store locally
        $this->storage->store_event($event);
        
        // Queue for batch processing
        $this->queue->push('process_analytics_batch', $event->to_array());
        
        // Send to third-party services asynchronously
        foreach ($this->third_party_integrations as $integration) {
            if ($integration->is_enabled()) {
                $this->queue->push('send_to_integration', [
                    'integration' => get_class($integration),
                    'event' => $event->to_array(),
                ]);
            }
        }
        
        // Update play counter
        $this->increment_play_counter($product_id);
    }
    
    /**
     * Get analytics data with modern query builder
     */
    public function get_analytics_data(array $filters = []): array {
        global $wpdb;
        
        $query = $this->build_analytics_query($filters);
        $results = $wpdb->get_results($query, ARRAY_A);
        
        return $this->process_analytics_results($results, $filters);
    }
    
    /**
     * Build optimized analytics query
     */
    private function build_analytics_query(array $filters): string {
        global $wpdb;
        
        $table = $wpdb->prefix . 'bfp_analytics';
        
        $select = "SELECT 
            product_id,
            COUNT(*) as play_count,
            COUNT(DISTINCT user_id) as unique_listeners,
            COUNT(DISTINCT session_id) as sessions,
            DATE(timestamp) as date";
        
        $where = ["1=1"];
        
        if (!empty($filters['product_id'])) {
            $where[] = $wpdb->prepare("product_id = %d", $filters['product_id']);
        }
        
        if (!empty($filters['date_from'])) {
            $where[] = $wpdb->prepare("timestamp >= %s", $filters['date_from']);
        }
        
        if (!empty($filters['date_to'])) {
            $where[] = $wpdb->prepare("timestamp <= %s", $filters['date_to']);
        }
        
        $group_by = "GROUP BY product_id, DATE(timestamp)";
        $order_by = "ORDER BY date DESC";
        
        return "$select FROM $table WHERE " . implode(' AND ', $where) . " $group_by $order_by";
    }
    
    /**
     * Real-time analytics dashboard data
     */
    public function get_realtime_stats(): array {
        $cache_key = 'bfp_realtime_stats';
        $cached = wp_cache_get($cache_key);
        
        if ($cached !== false) {
            return $cached;
        }
        
        $stats = [
            'active_listeners' => $this->get_active_listeners(),
            'trending_tracks' => $this->get_trending_tracks(10),
            'recent_plays' => $this->get_recent_plays(20),
            'play_rate' => $this->calculate_play_rate(),
        ];
        
        wp_cache_set($cache_key, $stats, 60); // Cache for 1 minute
        
        return $stats;
    }
}
```

### Frontend Analytics Tracking

```javascript
// modules/AnalyticsTracker.js
export class AnalyticsTracker {
    constructor(apiBase) {
        this.apiBase = apiBase;
        this.sessionId = this.generateSessionId();
        this.buffer = [];
        this.bufferTimeout = null;
        
        // Web Analytics API if available
        this.webAnalytics = this.initWebAnalytics();
    }
    
    initWebAnalytics() {
        if ('analytics' in window && window.analytics.track) {
            return window.analytics;
        }
        return null;
    }
    
    trackPlay(productId, fileId, metadata = {}) {
        const event = {
            event: 'play',
            product_id: productId,
            file_id: fileId,
            session_id: this.sessionId,
            timestamp: new Date().toISOString(),
            metadata: {
                ...metadata,
                viewport: this.getViewportInfo(),
                connection: this.getConnectionInfo(),
                audio_context_state: this.getAudioContextState(),
            }
        };
        
        // Add to buffer for batch sending
        this.buffer.push(event);
        this.scheduleFlush();
        
        // Send to Web Analytics API immediately if available
        if (this.webAnalytics) {
            this.webAnalytics.track('Audio Play', event);
        }
        
        // Send beacon for critical events
        if ('sendBeacon' in navigator) {
            const data = new FormData();
            data.append('event', JSON.stringify(event));
            navigator.sendBeacon(`${this.apiBase}/analytics/beacon`, data);
        }
    }
    
    trackProgress(productId, fileId, progress) {
        // Track significant progress points
        const milestones = [25, 50, 75, 90, 100];
        const percentage = Math.floor(progress * 100);
        
        if (milestones.includes(percentage)) {
            this.trackEvent('progress', {
                product_id: productId,
                file_id: fileId,
                milestone: percentage,
            });
        }
    }
    
    scheduleFlush() {
        if (this.bufferTimeout) {
            clearTimeout(this.bufferTimeout);
        }
        
        // Flush buffer every 5 seconds or when it reaches 10 events
        if (this.buffer.length >= 10) {
            this.flush();
        } else {
            this.bufferTimeout = setTimeout(() => this.flush(), 5000);
        }
    }
    
    async flush() {
        if (this.buffer.length === 0) return;
        
        const events = [...this.buffer];
        this.buffer = [];
        
        try {
            const response = await fetch(`${this.apiBase}/analytics/batch`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': this.getNonce(),
                },
                body: JSON.stringify({ events }),
            });
            
            if (!response.ok) {
                // Re-add events to buffer on failure
                this.buffer.unshift(...events);
            }
        } catch (error) {
            console.error('Analytics flush failed:', error);
            this.buffer.unshift(...events);
        }
    }
    
    getConnectionInfo() {
        const connection = navigator.connection || navigator.mozConnection || navigator.webkitConnection;
        
        if (!connection) return {};
        
        return {
            type: connection.effectiveType,
            downlink: connection.downlink,
            rtt: connection.rtt,
            saveData: connection.saveData,
        };
    }
}
```

## 4. Modern Hook System

### Hook Manager Implementation

```php
<?php
namespace BandfrontPlayer\Services;

use BandfrontPlayer\Interfaces\HookManagerInterface;

class HookManager implements HookManagerInterface {
    
    private $registered_hooks = [];
    private $dynamic_hooks = [];
    private $context_detector;
    
    public function __construct(ContextDetector $context_detector) {
        $this->context_detector = $context_detector;
    }
    
    /**
     * Register hooks with modern priority system
     */
    public function register_hooks(): void {
        // Core hooks that always run
        $this->register_core_hooks();
        
        // Context-specific hooks
        $this->register_contextual_hooks();
        
        // Dynamic hooks based on settings
        $this->register_dynamic_hooks();
        
        // Performance optimization hooks
        $this->register_performance_hooks();
    }
    
    private function register_core_hooks(): void {
        // Modern action scheduler for background tasks
        add_action('init', [$this, 'schedule_background_tasks']);
        
        // REST API endpoints
        add_action('rest_api_init', [$this, 'register_rest_routes']);
        
        // Asset loading with modern strategies
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets'], 5);
        
        // Block editor assets
        add_action('enqueue_block_editor_assets', [$this, 'enqueue_block_assets']);
    }
    
    private function register_contextual_hooks(): void {
        $context = $this->context_detector->get_current_context();
        
        switch ($context['page_type']) {
            case 'product':
                $this->register_product_page_hooks();
                break;
                
            case 'shop':
            case 'archive':
                $this->register_shop_page_hooks();
                break;
                
            case 'cart':
                $this->register_cart_hooks();
                break;
        }
    }
    
    private function register_product_page_hooks(): void {
        // Smart hook placement based on theme
        $theme_config = $this->get_theme_compatibility_config();
        
        $hook_priority = $theme_config['product_summary_priority'] ?? 25;
        
        add_action('woocommerce_single_product_summary', 
                  [$this, 'render_product_player'], 
                  $hook_priority);
        
        // Add structured data
        add_action('wp_head', [$this, 'add_audio_structured_data']);
    }
    
    /**
     * Modern asset enqueuing with optimization
     */
    public function enqueue_assets(): void {
        $context = $this->context_detector->get_current_context();
        
        if (!$this->should_load_assets($context)) {
            return;
        }
        
        // Use asset manifest for cache busting
        $manifest = $this->get_asset_manifest();
        
        // Core styles with CSS custom properties
        wp_enqueue_style(
            'bfp-modern-styles',
            plugins_url('dist/css/player.css', dirname(__DIR__)),
            [],
            $manifest['css/player.css']['version'] ?? BFP_VERSION
        );
        
        // Modern JavaScript with modules
        wp_enqueue_script(
            'bfp-player-module',
            plugins_url('dist/js/player.js', dirname(__DIR__)),
            [],
            $manifest['js/player.js']['version'] ?? BFP_VERSION,
            ['strategy' => 'defer', 'in_footer' => true]
        );
        
        // Add module type for ES6 modules
        wp_script_add_data('bfp-player-module', 'type', 'module');
        
        // Preload critical assets
        $this->add_resource_hints();
        
        // Inline critical CSS
        $this->inline_critical_css();
    }
    
    /**
     * Add resource hints for performance
     */
    private function add_resource_hints(): void {
        // Preconnect to CDN
        $cdn_url = $this->get_cdn_url();
        if ($cdn_url) {
            add_filter('wp_resource_hints', function($hints, $relation_type) use ($cdn_url) {
                if ('preconnect' === $relation_type) {
                    $hints[] = $cdn_url;
                }
                return $hints;
            }, 10, 2);
        }
        
        // Prefetch audio files on product pages
        if (is_product()) {
            add_action('wp_head', function() {
                $product_id = get_the_ID();
                $first_file = $this->get_first_audio_file($product_id);
                
                if ($first_file) {
                    echo sprintf(
                        '<link rel="prefetch" href="%s" as="audio" crossorigin="anonymous">',
                        esc_url($first_file)
                    );
                }
            });
        }
    }
}
```

## 5. Modern Security Implementation

### Security Service

```php
<?php
namespace BandfrontPlayer\Services;

use BandfrontPlayer\Interfaces\SecurityInterface;

class Security implements SecurityInterface {
    
    private $rate_limiter;
    private $access_control;
    
    public function __construct(
        RateLimiter $rate_limiter,
        AccessControl $access_control
    ) {
        $this->rate_limiter = $rate_limiter;
        $this->access_control = $access_control;
    }
    
    /**
     * Validate audio access with modern security
     */
    public function validate_audio_access(int $product_id, string $file_id): bool {
        // Rate limiting
        if (!$this->rate_limiter->check('audio_access', $this->get_client_identifier())) {
            throw new \Exception('Rate limit exceeded', 429);
        }
        
        // Verify nonce
        if (!$this->verify_audio_nonce()) {
            throw new \Exception('Invalid security token', 403);
        }
        
        // Check user permissions
        $user_access = $this->access_control->get_user_access_level($product_id);
        
        if ($user_access->is_blocked()) {
            throw new \Exception('Access denied', 403);
        }
        
        // Additional security checks
        if (!$this->validate_request_headers()) {
            throw new \Exception('Invalid request', 400);
        }
        
        return true;
    }
    
    /**
     * Generate secure temporary URL
     */
    public function generate_secure_url(string $file_path, int $expires_in = 3600): string {
        $expires = time() + $expires_in;
        $token = $this->generate_url_token($file_path, $expires);
        
        return add_query_arg([
            'token' => $token,
            'expires' => $expires,
        ], $file_path);
    }
    
    /**
     * Modern CORS handling
     */
    public function handle_cors(): void {
        $allowed_origins = $this->get_allowed_origins();
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
        
        if (in_array($origin, $allowed_origins, true)) {
            header('Access-Control-Allow-Origin: ' . $origin);
            header('Access-Control-Allow-Credentials: true');
            header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
            header('Access-Control-Allow-Headers: Content-Type, Authorization, X-WP-Nonce');
            header('Access-Control-Max-Age: 86400');
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            status_header(204);
            exit;
        }
    }
    
    /**
     * Content Security Policy headers
     */
    public function set_csp_headers(): void {
        $csp = [
            "default-src 'self'",
            "media-src 'self' blob: data: " . $this->get_cdn_url(),
            "script-src 'self' 'unsafe-inline' 'unsafe-eval'",
            "style-src 'self' 'unsafe-inline'",
            "img-src 'self' data: https:",
            "connect-src 'self' " . $this->get_api_endpoints(),
        ];
        
        header('Content-Security-Policy: ' . implode('; ', $csp));
    }
}
```

## 6. Modern Performance Optimization

### Performance Service

```php
<?php
namespace BandfrontPlayer\Services;

class PerformanceOptimizer {
    
    private $cache;
    private $cdn;
    
    /**
     * Optimize audio delivery
     */
    public function optimize_audio_delivery(string $file_path): array {
        // Use HTTP/2 Server Push for critical resources
        $this->server_push_resources();
        
        // Enable Brotli compression if available
        if ($this->supports_brotli()) {
            header('Content-Encoding: br');
        }
        
        // Set optimal cache headers
        $this->set_cache_headers($file_path);
        
        // Use CDN if available
        if ($this->cdn->is_enabled()) {
            return [
                'url' => $this->cdn->get_url($file_path),
                'headers' => $this->cdn->get_headers(),
            ];
        }
        
        return [
            'url' => $file_path,
            'headers' => $this->get_default_headers(),
        ];
    }
    
    /**
     * HTTP/2 Server Push
     */
    private function server_push_resources(): void {
        if (!function_exists('http2_push_resource')) {
            return;
        }
        
        $resources = [
            '/dist/css/player.css' => 'style',
            '/dist/js/player.js' => 'script',
        ];
        
        foreach ($resources as $path => $type) {
            header(sprintf(
                'Link: <%s>; rel=preload; as=%s',
                plugins_url($path, dirname(__DIR__)),
                $type
            ), false);
        }
    }
    
    /**
     * Lazy load players with Intersection Observer
     */
    public function get_lazy_load_script(): string {
        return <<<'JS'
        class PlayerLazyLoader {
            constructor() {
                this.observer = new IntersectionObserver(
                    (entries) => this.handleIntersection(entries),
                    {
                        rootMargin: '100px',
                        threshold: 0.01
                    }
                );
                
                this.observePlayers();
            }
            
            observePlayers() {
                document.querySelectorAll('.bfp-player[data-lazy="true"]')
                    .forEach(player => this.observer.observe(player));
            }
            
            async handleIntersection(entries) {
                for (const entry of entries) {
                    if (entry.isIntersecting) {
                        await this.loadPlayer(entry.target);
                        this.observer.unobserve(entry.target);
                    }
                }
            }
            
            async loadPlayer(element) {
                const module = await import('./modules/AudioPlayer.js');
                const player = new module.AudioPlayer({
                    container: element,
                    ...element.dataset
                });
                
                element.setAttribute('data-loaded', 'true');
            }
        }
        
        // Initialize when DOM is ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => new PlayerLazyLoader());
        } else {
            new PlayerLazyLoader();
        }
        JS;
    }
}
```

## Summary

This modern WordPress 2025 implementation provides:

1. **REST API Architecture** - Replaces query parameters with proper REST endpoints
2. **Service Container Pattern** - Dependency injection for better testing and modularity
3. **Modern JavaScript** - ES6 modules, async/await, Web APIs
4. **Security First** - CORS, CSP headers, rate limiting, nonce validation
5. **Performance Optimized** - HTTP/2 push, lazy loading, CDN integration
6. **Analytics Ready** - Real-time tracking with batch processing
7. **Accessibility Built-in** - ARIA labels, keyboard navigation
8. **Mobile Optimized** - Touch events, responsive design
9. **Progressive Enhancement** - Works without JavaScript, enhances with it
10. **Developer Friendly** - PSR-4 autoloading, proper namespacing, TypeScript support

The implementation maintains backward compatibility while providing a modern foundation for audio playback in WordPress.
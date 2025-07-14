# Refactored Codebase Functionality Analysis
# Bandfront Player Refactoring Guide - Implementation Details

URL FLOW

When the player is rendered (for example, via methods like include_main_player or include_all_players in the renderer), the plugin calls a delegate method (in BandfrontPlayer) that in turn calls the audio processor’s generate_audio_url() method.

<?php
public function include_main_player($product = '', $_echo = true) {
    // ...existing code...
    
    $file = reset($files);
    $index = key($files);
    $duration = $this->main_plugin->get_audio_processor()->get_duration_by_url($file['file']);
    
    // DELEGATE TO AUDIO PROCESSOR
    $audio_url = $this->main_plugin->get_audio_processor()->generate_audio_url($id, $index, $file);
    
    $audio_tag = apply_filters(
        'bfp_audio_tag',
        $this->main_plugin->get_player(
            $audio_url,  // <-- This URL comes from audio processor
            array(
                'product_id'      => $id,
                'player_controls' => $player_controls,
                'player_style'    => $player_style,
                'media_type'      => $file['media_type'],
                'duration'        => $duration,
                'preload'         => $preload,
                'volume'          => $volume,
            )
        ),
        $id,
        $index,
        $audio_url
    );
    // ...existing code...
}

In BFP_Audio_Processor::generate_audio_url(), it checks if a valid file URL is provided via the file data. If so, it may look up a locally stored (or “demo”) file (using a hashed file name) and, if the file exists and is valid, return a URL pointing to the file in the uploads folder. Otherwise, if no direct file URL is available or the file isn’t already processed, it builds a URL by appending query parameters (such as bfp-action=play, bfp-product, and bfp-file) to the site’s base URL.

Step 2: Audio Processor URL Generation Logic
<?php
var/www/html/wp-content/plugins/bandfront-player/builders/backup/old-code/bandfront-worky27/includes/class-bfp-audio-processor.php
public function generate_audio_url($product_id, $file_index, $file_data = array()) {
    // STEP 1: Check if direct file URL exists
    if (!empty($file_data['file'])) {
        $file_url = $file_data['file'];
        
        // Skip processing for playlists or direct sources
        if (!empty($file_data['play_src']) || $this->is_playlist($file_url)) {
            return $file_url;
        }

        $_bfp_analytics_property = trim($this->main_plugin->get_global_attr('_bfp_analytics_property', ''));
        if ('' == $_bfp_analytics_property) {
            // Check for cached Google Drive files
            $files = get_post_meta($product_id, '_bfp_drive_files', true);
            $key = md5($file_url);
            if (!empty($files) && isset($files[$key])) {
                return $files[$key]['url'];
            }

            // STEP 2: Generate hashed filename
            $file_name = $this->demo_file_name($file_url);
            $o_file_name = 'o_' . $file_name;

            // STEP 3: Check user purchase status
            $purchased = $this->main_plugin->woocommerce_user_product($product_id);
            if (false !== $purchased) {
                $o_file_name = 'purchased/o_' . $purchased . $file_name;
                $file_name = 'purchased/' . $purchased . '_' . $file_name;
            }

            // STEP 4: Check if processed files exist
            $file_path = $this->main_plugin->get_files_directory_path() . $file_name;
            $o_file_path = $this->main_plugin->get_files_directory_path() . $o_file_name;

            if ($this->valid_demo($file_path)) {
                return 'http' . ((is_ssl()) ? 's:' : ':') . $this->main_plugin->get_files_directory_url() . $file_name;
            } elseif ($this->valid_demo($o_file_path)) {
                return 'http' . ((is_ssl()) ? 's:' : ':') . $this->main_plugin->get_files_directory_url() . $o_file_name;
            }
        }
    }
    
    // STEP 5: Build dynamic URL with query parameters
    $url = BFP_WEBSITE_URL;
    $url .= ((strpos($url, '?') === false) ? '?' : '&') . 'bfp-action=play&bfp-product=' . $product_id . '&bfp-file=' . $file_index;
    return $url;
}

Step 3: Hash Generation and File Checking

<?php
/**
 * Generate demo file name using MD5 hash
 */
public function demo_file_name($url) {
    $file_extension = pathinfo($url, PATHINFO_EXTENSION);
    $file_name = md5($url) . ((!empty($file_extension) && preg_match('/^[a-z\d]{3,4}$/i', $file_extension)) ? '.' . $file_extension : '.mp3');
    return $file_name;
}

/**
 * Check if demo file is valid
 */
public function valid_demo($file_path) {
    if (!file_exists($file_path) || filesize($file_path) == 0) {
        return false;
    }
    if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME);
        return substr(finfo_file($finfo, $file_path), 0, 4) !== 'text';
    }
    return true;
}

Step 4: URL Processing and Shortcode Handling

<?php
// filepath: /var/www/html/wp-content/plugins/bandfront-player/builders/backup/old-code/bandfront-worky27/includes/class-bfp-audio-processor.php
public function output_file($args) {
    if (empty($args['url'])) {
        return;
    }

    $url = $args['url'];
    $original_url = $url;
    
    // PROCESS SHORTCODES for dynamic content
    $url = do_shortcode($url);
    
    // FIX URL for relative paths
    $url_fixed = $this->fix_url($url);

    do_action('bfp_play_file', $args['product_id'], $url);

    $file_name = $this->demo_file_name($original_url);
    // ...rest of processing...
}

/**
 * Fix URL for local files and relative paths
 */
public function fix_url($url) {
    if (file_exists($url)) {
        return $url;
    }
    if (strpos($url, '//') === 0) {
        $url_fixed = 'http' . (is_ssl() ? 's:' : ':') . $url;
    } elseif (strpos($url, '/') === 0) {
        $url_fixed = rtrim(BFP_WEBSITE_URL, '/') . $url;
    } else {
        $url_fixed = $url;
    }
    return $url_fixed;
}

Step 5: Player HTML Generation

<?php
html/wp-content/plugins/bandfront-player/builders/backup/old-code/bandfront-worky27/bfp.php
public function get_player($audio_url, $args = array()) {
    $default_args = array(
        'media_type'         => 'mp3',
        'player_style'       => BFP_DEFAULT_PLAYER_LAYOUT,
        'player_controls'    => BFP_DEFAULT_PLAYER_CONTROLS,
        'duration'           => false,
        'estimated_duration' => false,
        'volume'             => 1,
    );

    $args = array_merge($default_args, $args);
    $id   = (!empty($args['id'])) ? 'id="' . esc_attr($args['id']) . '"' : '';

    // ...processing logic...

    $preload = (!empty($args['preload'])) ? $args['preload'] : $GLOBALS['BandfrontPlayer']->get_global_attr('_bfp_preload', 'none');
    $preload = apply_filters('bfp_preload', $preload, $audio_url);

    // GENERATE AUDIO TAG WITH URL AS SOURCE
    return '<audio ' . 
           (isset($args['volume']) && is_numeric($args['volume']) && 0 <= $args['volume'] * 1 && $args['volume'] * 1 <= 1 ? 'volume="' . esc_attr($args['volume']) . '"' : '') . 
           ' ' . $id . ' preload="none" data-lazyloading="' . esc_attr($preload) . '" class="bfp-player ' . esc_attr($args['player_controls']) . ' ' . esc_attr($args['player_style']) . '" ' . 
           ((!empty($args['duration'])) ? 'data-duration="' . esc_attr($args['duration']) . '"' : '') . 
           ((!empty($args['estimated_duration'])) ? ' data-estimated_duration="' . esc_attr($args['estimated_duration']) . '"' : '') . 
           '><source src="' . esc_url($audio_url) . '" type="audio/' . esc_attr($args['media_type']) . '" /></audio>';
}

Step 6: Request Interception

<?php
public function init() {
    // ...existing code...
    
    if (!is_admin()) {
        // INTERCEPT bfp-action=play REQUESTS
        if (isset($_REQUEST['bfp-action']) && 'play' == $_REQUEST['bfp-action']) {
            if (isset($_REQUEST['bfp-product'])) {
                $product_id = @intval($_REQUEST['bfp-product']);
                if (!empty($product_id)) {
                    $product = wc_get_product($product_id);
                    if (false !== $product) {
                        $this->update_playback_counter($product_id);
                        if (isset($_REQUEST['bfp-file'])) {
                            $files = $this->_get_product_files(
                                array(
                                    'product' => $product,
                                    'file_id' => sanitize_key($_REQUEST['bfp-file']),
                                )
                            );
                            if (!empty($files)) {
                                $file_url = $files[sanitize_key($_REQUEST['bfp-file'])]['file'];
                                $this->_tracking_play_event($product_id, $file_url);
                                
                                // DELEGATE TO AUDIO PROCESSOR for file delivery
                                $this->_output_file(
                                    array(
                                        'product_id'   => $product_id,
                                        'url'          => $file_url,
                                        'secure_player' => @intval($this->get_product_attr($product_id, '_bfp_secure_player', 0)),
                                        'file_percent' => @intval($this->get_product_attr($product_id, '_bfp_file_percent', BFP_FILE_PERCENT)),
                                    )
                                );
                            }
                        }
                    }
                }
            }
            exit; // Important: Stop execution after serving file
        }
    }
}

This URL is then set as the src for the <source> tag embedded inside the <audio> element generated in the get_player() method. That means when the browser requests the audio resource, the plugin intercepts the request (for example, via a bfp-action hook) and uses the audio processor to deliver the file (or process it on the fly).

Explanation
Direct URL Handling:
• The code checks for a provided URL in the file data.
• It runs any shortcodes (for dynamic content) and “fixes” the URL (for relative paths).
• A hashed filename is generated (using md5) along with the proper file extension.
• It then checks under the uploads directory (using wp_upload_dir()) whether the file is already saved.

Dynamic URL Building:
• If no local file is available, the code builds a URL with query parameters that include the product ID and file index.
• Using add_query_arg() ensures query parameters are correctly appended to the base URL returned by site_url().

Security and Best Practices (2025):
• Utilizing WordPress functions like wp_upload_dir(), do_shortcode(), and add_query_arg() makes the code more robust and maintainable.
• Data is escaped with esc_url() to prevent potential security issues.
• In 2025, using REST API endpoints would also be a recommended alternative for dynamic requests. However, for backwards compatibility and simplicity, using query arguments with proper sanitization remains acceptable.


Modern WordPress Approach (2025 Compliant)
For a more modern implementation, you could replace the query parameter approach with REST API endpoints:

<?php
// Modern REST API approach
public function register_rest_routes() {
    register_rest_route('bfp/v1', '/play/(?P<product>\d+)/(?P<file>[a-zA-Z0-9_-]+)', array(
        'methods' => 'GET',
        'callback' => array($this, 'serve_audio_file'),
        'permission_callback' => array($this, 'check_play_permissions'),
        'args' => array(
            'product' => array(
                'validate_callback' => function($param) {
                    return is_numeric($param);
                }
            ),
            'file' => array(
                'validate_callback' => function($param) {
                    return is_string($param);
                }
            ),
        ),
    ));
}

public function generate_modern_audio_url($product_id, $file_index, $file_data = array()) {
    // Use REST API endpoint instead of query parameters
    return rest_url("bfp/v1/play/{$product_id}/{$file_index}");
}


## Core Architecture Overview


The plugin uses a class-based architecture with clear separation of concerns:

### Main Classes
1. **BandfrontPlayer** - Main plugin class
2. **BFP_Config** - Configuration management
3. **BFP_Audio_Processor** - Audio file handling and processing
4. **BFP_Player_Manager** - Player instance management
5. **BFP_Player_Renderer** - HTML generation for players
6. **BFP_Playlist_Renderer** - Playlist HTML generation
7. **BFP_Analytics** - Tracking and analytics
8. **BFP_Cache_Manager** - Cache clearing utilities
9. **BFP_Cloud_Tools** - Cloud storage integration

## Key Extraction Points

### 1. Player Initialization (from engine.js)

**Current Implementation:**
- `generate_the_bfp()` function handles all player initialization
- Complex jQuery-based DOM manipulation
- Inline configuration and event handling

**Extract to:**
```javascript
class PlayerInitializer {
    constructor() {
        this.players = new Map();
        this.config = new PlayerConfig();
    }
    
    init() {
        // Prevent duplicate initialization
        if (window.bfp_players_counter !== undefined) return;
        
        // Set up global state
        window.bfp_players_counter = 0;
        window.bfp_players = {};
        
        // Initialize players
        this.initializePlayers();
    }
    
    initializePlayers() {
        jQuery('.bfp-player:not(.track)').each((i, player) => {
            this.initializePlayer(player);
        });
    }
}
```

### 2. Configuration Management (from class-bfp-config.php)

**Current Implementation:**
- Global and product-specific attributes
- Caching mechanism for performance
- Fallback to global settings

**Key Methods:**
- `get_product_attr($product_id, $attr, $default)`
- `get_global_attr($attr, $default)`
- `get_player_layouts()` - Returns ['dark', 'light', 'custom']
- `get_player_controls()` - Returns ['button', 'all', 'default']

### 3. Audio Processing (from class-bfp-audio-processor.php)

**Critical Functions:**
- `output_file($args)` - Handles file streaming and protection
- `process_secure_audio()` - Implements file truncation
- `is_audio($file_path)` - Detects audio file types
- `generate_audio_url()` - Creates secure playback URLs

**File Protection Flow:**
1. Check user permissions (purchased/registered)
2. Generate temporal file if needed
3. Apply truncation if secure_player enabled
4. Stream file with proper headers

### 4. Player Rendering (from class-bfp-player-renderer.php)

**Rendering Context:**
- Shop page: Track buttons or full players
- Product page: Full player with all controls
- Cart: Optional player display
- Single player mode: One active player at a time

**Key Decision Points:**
```php
// Determine player type
if ($on_cover && $is_shop_page) {
    // Render play button on product image
} elseif ($player_controls == 'button') {
    // Render track-only player
} else {
    // Render full player
}
```

### 5. Playlist Management (from engine.js)

**Current Implementation:**
```javascript
function _playNext(currentPlayer, loop) {
    // Complex logic for finding next track
    // Handles single player mode
    // Manages loop boundaries
}
```

**Extract to:**
```javascript
class PlaylistManager {
    constructor() {
        this.currentTrack = null;
        this.playlist = [];
    }
    
    getNextTrack(currentPlayer, options = {}) {
        const { loop = false, singlePlayer = false } = options;
        
        if (singlePlayer) {
            return this.getNextInSinglePlayerMode(currentPlayer);
        }
        
        return this.getNextInPlaylist(currentPlayer, loop);
    }
}
```

### 6. Event Management

**Key Events:**
- `playing` - Track analytics, update UI
- `timeupdate` - Fade effects, volume control
- `ended` - Playlist navigation, loop handling
- `volumechange` - Persist volume settings

**Analytics Integration:**
```javascript
player.addEventListener('playing', function() {
    // Track play event
    if (this.getAttribute('data-bfp-tracked') !== '1') {
        jQuery.post(bfp_global_vars.url, {
            'bfp-action': 'track-play',
            'product-id': productId,
            'file-url': fileUrl
        });
        this.setAttribute('data-bfp-tracked', '1');
    }
});
```

### 7. UI State Management

**Player States:**
- `.bfp-playing` - Active player indicator
- `.bfp-first-player` - Visible player in single mode
- `.bfp-message` - Demo/protection messages

**Responsive Handling:**
```javascript
jQuery(window).on('resize', function() {
    // Reposition overlay players
    // Adjust player widths
    // Handle mobile-specific layouts
});
```

### 8. File Storage Structure

**Directory Layout:**
```
uploads/bfp/
├── [hash].mp3          # Cached demo files
├── o_[hash].mp3        # Original files before processing
└── purchased/          # User-specific files
    ├── [user]_[hash].mp3
    └── o_[user]_[hash].mp3
```

### 9. WooCommerce Integration

**Hooks:**
- `woocommerce_before_shop_loop_item_title` - Cover play buttons
- `woocommerce_single_product_summary` - Product page player
- `woocommerce_after_shop_loop_item` - Shop page players
- `woocommerce_cart_item_name` - Cart players

**Product Types:**
- Simple products
- Variable products
- Grouped products (special handling)
- External/affiliate products (disabled)

### 10. Security & Protection

**File Protection Methods:**
1. **Secure Player** - Truncates files to X%
2. **Registered Only** - Requires login
3. **Purchased Only** - Requires purchase
4. **Watermarking** - FFmpeg audio watermarks

**URL Security:**
- Temporal file generation
- Hash-based file names
- Direct file access prevention
- 302 redirect option

## Migration Strategy

### Phase 1: Core Services
1. Extract configuration management
2. Create audio processor service
3. Implement cache manager

### Phase 2: Player Components
1. Player initializer
2. Event manager
3. UI controller

### Phase 3: Integration Layer
1. WooCommerce adapters
2. Builder integrations (Elementor, Gutenberg)
3. AJAX handlers

### Phase 4: Modern Refactor
1. Convert to ES6 modules
2. Implement Promise-based APIs
3. Add TypeScript definitions

## State Variables Reference

**Global JavaScript:**
- `window.bfp_players` - Player instances
- `window.bfp_players_counter` - Instance counter
- `window.bfp_single_player_instance` - Single player reference

**Data Attributes:**
- `data-bfp-product` - Product ID
- `data-bfp-file` - File index
- `data-bfp-player` - Player instance ID
- `data-bfp-tracked` - Analytics tracking flag

**CSS Classes:**
- `.bfp-player` - Player container
- `.bfp-playing` - Active state
- `.bfp-first-player` - Visible in single mode
- `.track` - Track-only player
- `.dark`, `.light`, `.custom` - Player


----------

END OF DOC. RESEARCH FOLLOWS

Collecting workspace informationLooking at the CSS-REFACTOR codebase with the extraction rules in mind, I can identify several key areas that need extraction and modernization. Here's what I've found:

## 1. Player Logic Extraction

### Context-Aware Player Rendering (PHP)

From `class-bfp-player-renderer.php`:

````php
// Context-aware control detection - NEEDS EXTRACTION
public function include_all_players($product = '') {
    // EXTRACT: Context detection logic
    if (function_exists('is_product') && is_product()) {
        $player_controls = 'all';  // Full controls on product pages
    } else {
        $player_controls = 'track'; // Button only on shop pages
    }
    
    // EXTRACT: Player generation logic
    $counter = count($files);
    if (1 == $counter) {
        // Single player logic
    } else {
        // Multiple players logic - table layout if > 3 files
    }
}
````

**Extract to**: `PlayerContextManager` class:
- `detectPageContext()`: Determine if shop/product page
- `getContextualControls()`: Return appropriate control set
- `getLayoutForFileCount()`: Single vs table layout logic

### JavaScript Player Initialization

From `engine.js`:

````javascript
// EXTRACT: Monolithic initialization - needs class structure
window.generate_the_bfp = function(forceInit) {
    // EXTRACT: Player registry management
    var bfp_players = [];
    var bfp_player_counter = 0;
    
    // EXTRACT: Engine detection
    var audioEngine = (typeof bfp_global_settings !== 'undefined') ? 
        bfp_global_settings.audio_engine : 'mediaelement';
    
    // EXTRACT: Player positioning logic
    function _setOverImage(player) {
        // Complex positioning calculations
    }
    
    // EXTRACT: Playlist navigation
    function _playNext(playernumber, loop) {
        // Next track logic with loop boundary detection
    }
}
````

**Extract to**: 
- `PlayerInitializer`: Main initialization control
- `PlayerRegistry`: Player instance management
- `PlaylistNavigator`: Track progression logic
- `PlayerPositioning`: Overlay positioning

## 2. Widget Rendering Logic

From `playlist_widget.php`:

````php
// EXTRACT: Widget rendering logic scattered across form/update/widget methods
public function widget($args, $instance) {
    // EXTRACT: Attribute processing
    $attrs = array(
        'products_ids' => $instance['products_ids'],
        'volume' => $instance['volume'],
        'player_style' => $instance['player_style'],
        // ... more attributes
    );
    
    // EXTRACT: Output generation
    $output = $GLOBALS['BandfrontPlayer']->replace_playlist_shortcode($attrs);
}
````

**Extract to**: 
- `WidgetAttributeProcessor`: Handle widget settings
- `WidgetRenderer`: Generate widget output
- `ShortcodeProcessor`: Process shortcode attributes

## 3. Block Playlist Rendering

From `gutenberg.js`:

````javascript
// EXTRACT: Block registration and rendering
blocks.registerBlockType('bfp/bandfront-player-playlist', {
    edit: function(props) {
        // EXTRACT: Live preview iframe logic
        children.push(
            el('div', {className: 'bfp-iframe-container'},
                el('iframe', {
                    src: bfp_gutenberg_editor_config.url + '?bfp-preview=' + 
                         encodeURIComponent(props.attributes.shortcode),
                    height: 400
                })
            )
        );
    }
});
````

**Extract to**:
- `BlockRenderer`: Handle block display logic
- `LivePreviewManager`: Manage iframe previews
- `BlockAttributeHandler`: Process block attributes

## 4. File Storage & Retrieval

From `class-bfp-audio-processor.php`:

````php
// EXTRACT: Complex file processing logic
public function output_file($args) {
    // EXTRACT: URL processing
    $url = do_shortcode($args['url']);
    $url_fixed = $this->fix_url($url);
    
    // EXTRACT: Purchase validation
    $purchased = $this->main_plugin->woocommerce_user_product($args['product_id']);
    
    // EXTRACT: Demo file generation
    if (false !== $purchased) {
        // Serve full file
    } else {
        // Generate/serve demo
        $this->process_secure_audio($file_path, $o_file_path, $file_percent, $file_name, $o_file_name, $args);
    }
}
````

**Extract to**:
- `FileUrlProcessor`: Handle URL transformation
- `PurchaseValidator`: Check user permissions
- `DemoFileGenerator`: Create truncated versions
- `FileStreamer`: Handle file delivery

## 5. State Management Issues

Current scattered state access needs consolidation:

````php
// SCATTERED: Direct state access throughout codebase
$player_style = $this->main_plugin->get_product_attr($id, '_bfp_player_layout', BFP_DEFAULT_PLAYER_LAYOUT);
$volume = $this->main_plugin->get_product_attr($id, '_bfp_player_volume', BFP_DEFAULT_PLAYER_VOLUME);
$secure_player = $this->main_plugin->get_product_attr($id, '_bfp_secure_player', false);
````

**Extract to**: Centralized state fetching as per state management rules:

````php
// EXTRACTED: Bulk state retrieval
$player_settings = $this->main_plugin->get_config()->get_states([
    '_bfp_player_layout',
    '_bfp_player_volume', 
    '_bfp_secure_player'
], $product_id);
````

## Key Extraction Priorities

1. **Player Context Detection** - Currently mixed in renderer
2. **JavaScript Player Registry** - Scattered throughout engine.js
3. **File Processing Pipeline** - Complex logic in audio processor
4. **Widget/Block Attribute Processing** - Duplicated across builders
5. **State Management Consolidation** - Replace scattered get_product_attr calls

The codebase shows good architectural separation but needs these specific extractions to follow the modern class-based, separated-concerns pattern outlined in the refactoring rules.

## Core Architecture

### Component-Based System Design

The refactored codebase follows a modular, class-based architecture with separated concerns:

```php
// Main plugin class with component injection
class BandfrontPlayer {
    private $_config;           // Configuration management
    private $_file_handler;     // File operations
    private $_player_manager;   // Player HTML generation
    private $_audio_processor;  // File processing & streaming
    private $_woocommerce;      // WooCommerce integration
    private $_hooks_manager;    // WordPress hooks
    private $_player_renderer;  // Player HTML rendering
    private $_playlist_renderer;// Playlist rendering
    private $_analytics;        // Analytics tracking
    private $_preview_manager;  // Preview/demo handling
    
    public function __construct() {
        $this->init_components();
    }
}
```

### Configuration Management System

Configuration is centralized in the `BFP_Config` class with caching:

```php
class BFP_Config {
    private $_products_attrs = array();  // Product-level cache
    private $_global_attrs = array();    // Global settings cache
    
    public function get_product_attr($product_id, $attr, $default = false) {
        if (!isset($this->_products_attrs[$product_id][$attr])) {
            // Load from database or fall back to global
            if (metadata_exists('post', $product_id, $attr)) {
                $this->_products_attrs[$product_id][$attr] = get_post_meta($product_id, $attr, true);
            } else {
                $this->_products_attrs[$product_id][$attr] = $this->get_global_attr($attr, $default);
            }
        }
        return apply_filters('bfp_product_attr', $this->_products_attrs[$product_id][$attr], $product_id, $attr);
    }
}
```

## Player Logic

### Context-Aware Player Rendering

The system now automatically determines appropriate controls based on page context:

```php
// In BFP_Player_Renderer::include_main_player()
if (function_exists('is_product') && is_product()) {
    // Product page - always use full controls
    $player_controls = '';  // Empty string means 'all' controls
} else {
    // Shop/archive pages - always use button only
    $player_controls = 'track';  // 'track' means button controls
}
```

### Dynamic Hook Registration

Hooks are registered dynamically based on page context to prevent duplicate players:

```php
// In BFP_Hooks_Manager
public function get_hooks_config() {
    $hooks_config = array(
        'main_player' => array(),
        'all_players' => array()
    );
    
    // Only add all_players hooks on single product pages
    if (function_exists('is_product') && is_product()) {
        $hooks_config['all_players'] = array(
            'woocommerce_single_product_summary' => 25,  // After price
        );
    } else {
        // On shop pages, conditionally add hooks
        $on_cover = $this->main_plugin->get_global_attr('_bfp_on_cover', 1);
        if (!$on_cover) {
            $hooks_config['main_player'] = array(
                'woocommerce_after_shop_loop_item_title' => 1,
            );
        }
    }
    
    return $hooks_config;
}
```

### Player HTML Generation

Player generation is handled by the `BFP_Player_Manager` with audio engine support:

```php
public function get_player($audio_url, $args = array()) {
    $player_id = 'bfp-player-' . $product_id . '-' . $id . '-' . uniqid();
    
    $player_html = '<audio id="' . esc_attr($player_id) . '" ';
    $player_html .= 'class="bfp-player ' . esc_attr($player_style) . '" ';
    $player_html .= 'data-product-id="' . esc_attr($product_id) . '" ';
    $player_html .= 'preload="' . esc_attr($preload) . '" ';
    $player_html .= 'data-volume="' . esc_attr($volume) . '" ';
    
    if ($player_controls) {
        $player_html .= 'data-controls="' . esc_attr($player_controls) . '" ';
    }
    
    $player_html .= '>';
    $player_html .= '<source src="' . esc_url($audio_url) . '" type="audio/' . esc_attr($media_type) . '" />';
    $player_html .= '</audio>';
    
    return apply_filters('bfp_player_html', $player_html, $audio_url, $args);
}
```

## Audio Engine System

### Dual Engine Support

The system supports both MediaElement.js and WaveSurfer.js engines:

```javascript
// In engine.js
var audioEngine = (typeof bfp_global_settings !== 'undefined') ? 
    bfp_global_settings.audio_engine : 'mediaelement';

var useWaveSurfer = (audioEngine === 'wavesurfer' && typeof WaveSurfer !== 'undefined');

if (useWaveSurfer) {
    // Initialize WaveSurfer players
    waveSurferPlayers.each(function() {
        var wavesurfer = initWaveSurferPlayer(this, audioUrl, options);
    });
} else {
    // Initialize MediaElement.js players
    fullPlayers.each(function() {
        $(this).mediaelementplayer(config);
    });
}
```

### WaveSurfer Integration

WaveSurfer players include MediaElement-compatible interface:

```javascript
function initWaveSurferPlayer(container, audioUrl, options) {
    var wavesurfer = WaveSurfer.create({
        container: '#' + waveformId,
        waveColor: '#999',
        progressColor: '#000',
        responsive: true,
        height: 60
    });
    
    // Add MediaElement-compatible interface
    wavesurfer.play = function() { wavesurfer.play(); };
    wavesurfer.pause = function() { wavesurfer.pause(); };
    wavesurfer.setVolume = function(v) { wavesurfer.setVolume(v); };
    
    return wavesurfer;
}
```

## File Storage & Retrieval

### Organized File Processing

File handling is centralized in `BFP_Audio_Processor` with secure processing:

```php
public function output_file($args) {
    $url = $args['url'];
    $original_url = $url;
    
    // Process shortcodes and fix URLs
    $url = do_shortcode($url);
    $url_fixed = $this->fix_url($url);
    
    // Fire tracking event
    do_action('bfp_play_file', $args['product_id'], $url);
    
    // Generate file names
    $file_name = $this->demo_file_name($original_url);
    $o_file_name = 'o_' . $file_name;
    
    // Check purchase status
    $purchased = $this->main_plugin->woocommerce_user_product($args['product_id']);
    
    if (false !== $purchased) {
        // Serve full file for purchased products
        $o_file_name = 'purchased/o_' . $purchased . $file_name;
        $file_name = 'purchased/' . $purchased . '_' . $file_name;
    }
    
    // Process file...
}
```

### Cloud Storage Integration

Cloud URLs are processed through dedicated tools:

```php
class BFP_Cloud_Tools {
    public static function get_google_drive_download_url($url) {
        $patterns = [
            '/drive\.google\.com\/file\/d\/([a-zA-Z0-9_-]+)/i',
            '/drive\.google\.com\/open\?id=([a-zA-Z0-9_-]+)/i',
            '/drive\.google\.com\/uc\?id=([a-zA-Z0-9_-]+)/i'
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $url, $matches)) {
                return "https://drive.google.com/uc?export=download&id={$matches[1]}";
            }
        }
        
        return $url;
    }
}
```

## Widget & Block Rendering

### Playlist Widget Architecture

Playlist rendering is handled by dedicated renderer classes:

```php
class BFP_Playlist_Renderer {
    public function render_playlist($products, $atts, $current_post_id, $purchased_times_data = array()) {
        $this->enqueue_playlist_resources($atts);
        
        $output = '<div data-loop="' . ($atts['loop'] ? 1 : 0) . '">';
        
        foreach ($products as $product) {
            $output .= $this->render_product_item($product, $atts, $current_post_id, $counter, $purchased_times_data);
        }
        
        $output .= '</div>';
        return $output;
    }
    
    private function render_product_item($product, $atts, $current_post_id, $counter, $purchased_times_data) {
        // Render based on layout
        if ('new' == $atts['layout']) {
            return $this->render_new_layout($product, $product_obj, $atts, $audio_files, $download_links, 
                                           $product_data['row_class'], $current_post_id, $product_data['preload']);
        } else {
            return $this->render_classic_layout($product, $product_obj, $atts, $audio_files, $download_links, 
                                                $product_data['row_class'], $current_post_id, $product_data['preload']);
        }
    }
}
```

### Play Button on Cover Implementation

Cover play buttons are handled through the hooks manager:

```php
public function add_play_button_on_cover() {
    $on_cover = $this->main_plugin->get_global_attr('_bfp_on_cover', 1);
    if (!$on_cover) return;
    
    // Output the play button overlay
    echo '<div class="bfp-play-on-cover" data-product-id="' . esc_attr($product_id) . '">';
    echo '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">';
    echo '<path d="M8 5v14l11-7z"/>';
    echo '</svg>';
    echo '</div>';
    
    // Add hidden player container
    echo '<div class="bfp-hidden-player-container" style="display:none;">';
    $this->main_plugin->include_main_player($product, true);
    echo '</div>';
}
```

## Block Editor Integration

### Gutenberg Block Registration

Blocks are registered with server-side rendering:

```php
// In BFP_BUILDERS
public function gutenberg_editor() {
    register_block_type( __DIR__ . '/gutenberg', array(
        'render_callback' => array( $this, 'render_bfp_playlist_block' )
    ) );
}

public function render_bfp_playlist_block( $attributes, $content ) {
    $shortcode = isset( $attributes['shortcode'] ) ? $attributes['shortcode'] : '[bfp-playlist products_ids="*"]';
    
    if ( isset( $GLOBALS['BandfrontPlayer'] ) ) {
        $output = do_shortcode( $shortcode );
        return is_string( $output ) ? $output : '';
    }
    
    return '';
}
```

### Block JavaScript Implementation

```javascript
// In gutenberg.js
blocks.registerBlockType( 'bfp/bandfront-player-playlist', {
    edit: function( props ) {
        var children = [];
        
        // Shortcode editor
        children.push(
            el('textarea', {
                key: 'bfp_playlist_shortcode',
                value: props.attributes.shortcode,
                onChange: function(evt){
                    props.setAttributes({shortcode: evt.target.value});
                },
                className: 'bfp-playlist-shortcode-input'
            })
        );
        
        // Live preview iframe
        children.push(
            el('div', {className: 'bfp-iframe-container'},
                el('iframe', {
                    src: bfp_gutenberg_editor_config.url + '?bfp-preview=' + 
                         encodeURIComponent(props.attributes.shortcode),
                    height: 400,
                    width: '100%'
                })
            )
        );
        
        return children;
    },
    
    save: function( props ) {
        return props.attributes.shortcode;
    }
});
```

## Page Builder Integrations

### Elementor Widget

Elementor integration provides live preview through iframe:

```php
class Elementor_BFP_Widget extends Widget_Base {
    protected function render() {
        $shortcode = sanitize_text_field( $this->_get_shortcode() );
        
        if (isset( $_REQUEST['action'] ) && 
            ('elementor' == $_REQUEST['action'] || 'elementor_ajax' == $_REQUEST['action'])) {
            
            $url = BFP_WEBSITE_URL . '?bfp-preview=' . urlencode( $shortcode );
            ?>
            <div class="bfp-iframe-container" style="position:relative;">
                <div class="bfp-iframe-overlay" style="position:absolute;top:0;right:0;bottom:0;left:0;"></div>
                <iframe height="0" width="100%" src="<?php print esc_attr( $url ); ?>" scrolling="no">
            </div>
            <?php
        } else {
            print do_shortcode( shortcode_unautop( $shortcode ) );
        }
    }
}
```

## State Management

### Centralized Configuration

State management is handled through the config system with automatic fallbacks:

```php
// Product attributes automatically fall back to global settings
public function get_product_attr($product_id, $attr, $default = false) {
    if (!isset($this->_products_attrs[$product_id][$attr])) {
        if (metadata_exists('post', $product_id, $attr)) {
            // Use product-specific value
            $this->_products_attrs[$product_id][$attr] = get_post_meta($product_id, $attr, true);
        } else {
            // Fall back to global setting
            $this->_products_attrs[$product_id][$attr] = $this->get_global_attr($attr, $default);
        }
    }
    return $this->_products_attrs[$product_id][$attr];
}
```

### Purchase Validation System

Purchase validation is handled by the WooCommerce integration:

```php
public function woocommerce_user_product($product_id) {
    if (!is_user_logged_in()) return false;
    
    $current_user = wp_get_current_user();
    
    if (wc_customer_bought_product($current_user->user_email, $current_user->ID, $product_id) ||
        apply_filters('bfp_purchased_product', false, $product_id)) {
        
        $this->main_plugin->set_purchased_product_flag(true);
        return md5($current_user->user_email);
    }
    
    return false;
}
```

## Resource Management

### Smart Resource Enqueuing

Resources are enqueued based on selected audio engine:

```php
public function enqueue_resources() {
    if ($this->_enqueued_resources) return;
    
    $audio_engine = $this->main_plugin->get_global_attr('_bfp_audio_engine', 'mediaelement');
    
    // Base styles always loaded
    wp_enqueue_style('bfp-style', plugin_dir_url(dirname(__FILE__)) . 'css/style.css');
    
    if ($audio_engine === 'wavesurfer') {
        // Enqueue WaveSurfer with fallback
        if (file_exists($local_wavesurfer_path)) {
            wp_enqueue_script('wavesurfer', $local_wavesurfer_url);
        } else {
            wp_enqueue_script('wavesurfer', 'https://unpkg.com/wavesurfer.js@7/dist/wavesurfer.min.js');
        }
    } else {
        // Enqueue MediaElement.js with skin
        wp_enqueue_style('wp-mediaelement');
        wp_enqueue_script('wp-mediaelement');
        
        $selected_skin = $this->main_plugin->get_global_attr('_bfp_player_layout', 'dark');
        wp_enqueue_style('bfp-skin-' . $selected_skin, 
                        plugin_dir_url(dirname(__FILE__)) . 'css/skins/' . $selected_skin . '.css');
    }
    
    $this->_enqueued_resources = true;
}
```

### Cache Management

Cache clearing is handled by a dedicated cache manager:

```php
class BFP_Cache_Manager {
    public static function clear_all_caches() {
        // WP Rocket
        if (function_exists('rocket_clean_domain')) {
            rocket_clean_domain();
        }
        
        // LiteSpeed Cache
        do_action('litespeed_purge_all');
        
        // Elementor Cache
        if (class_exists('\Elementor\Plugin')) {
            \Elementor\Plugin::$instance->files_manager->clear_cache();
        }
        
        // WordPress native cache
        wp_cache_flush();
    }
}
```

## Analytics Integration

### Event Tracking System

Analytics are handled through a dedicated analytics class:

```php
class BFP_Analytics {
    public function track_play_event($product_id, $file_url) {
        $this->main_plugin->get_audio_processor()->tracking_play_event($product_id, $file_url);
    }
    
    public function increment_playback_counter($product_id) {
        if (!$this->main_plugin->get_global_attr('_bfp_playback_counter_column', 1)) {
            return;
        }
        
        $counter = get_post_meta($product_id, '_bfp_playback_counter', true);
        $counter = empty($counter) ? 1 : intval($counter) + 1;
        update_post_meta($product_id, '_bfp_playback_counter', $counter);
    }
}
```

## Summary

The refactored JavaScript and PHP system provides:

- **Component-based architecture** with clear separation of concerns
- **Context-aware player rendering** that automatically adapts to page types
- **Dual audio engine support** for MediaElement.js and WaveSurfer.js
- **Smart resource management** with conditional loading
- **Centralized configuration** with automatic fallbacks
- **Modular file processing** with cloud storage support
- **Dedicated rendering classes** for widgets and blocks
- **Dynamic hook registration** to prevent conflicts
- **Comprehensive analytics tracking** with playback counters
- **Modern block editor integration** with live previews
- **Page builder compatibility** through iframe previews
- **Cache management** for performance optimization

All functionality maintains backward compatibility while providing a modern, extensible foundation for future development.

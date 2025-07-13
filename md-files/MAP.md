# Bandfront Player - Detailed Code Map

*For class definitions and methods, see STATE-MANAGEMENT.md*

## Plugin Initialization Flow

### 1. WordPress Bootstrap
```
WordPress loads plugin → bfp.php instantiates BandfrontPlayer → 
Components initialized in dependency order → Hooks registered
```

### 2. Component Initialization Order
```php
// bfp.php: init_components()
1. BFP_Config (state-manager.php)        // Must be first
2. BFP_File_Handler (utils/files.php)    // Creates directories
3. BFP_Player_Manager (player.php)       // Player functionality
4. BFP_Audio_Engine (audio.php)          // Audio processing
5. BFP_WooCommerce (woocommerce.php)    // WC integration
6. BFP_Hooks (hooks.php)                 // Hook registration
7. BFP_Player_Renderer (player-html.php) // Player rendering
8. BFP_Cover_Renderer (cover-renderer.php) // Cover overlays
9. BFP_Analytics (utils/analytics.php)   // Tracking
10. BFP_Preview (utils/preview.php)      // Preview handling
11. BFP_Admin (admin.php)                // Admin UI (conditional)
```

## Request Lifecycle

### Frontend Page Load

#### Shop/Archive Pages
```
1. wp action triggered
2. BFP_Hooks::register_dynamic_hooks()
3. Check context (is_shop/is_archive)
4. If on_cover enabled:
   - Register cover overlay hooks
   - Skip title hooks
5. Else:
   - Register woocommerce_after_shop_loop_item_title hook
6. When hook fires:
   - BFP_Player_Manager::include_main_player()
   - Context detected → button-only player
   - First file only
```

#### Single Product Pages
```
1. wp action triggered
2. BFP_Hooks::register_dynamic_hooks()
3. Check context (is_product)
4. Register woocommerce_single_product_summary hook (priority 25)
5. When hook fires:
   - BFP_Player_Manager::include_all_players()
   - Context detected → full controls
   - All files rendered (single or table layout)
```

### Audio Play Request
```
1. Click play button
2. Browser requests: ?bfp-action=play&bfp-product=X&bfp-file=Y
3. BFP_Preview::handle_preview_request()
4. Validate request parameters
5. BFP_Analytics::increment_playback_counter()
6. Get file settings via state manager
7. BFP_Audio_Engine::output_file()
8. If secure_player enabled:
   - Check purchase status
   - Generate/retrieve demo file
   - Stream truncated version
9. Else:
   - Stream full file
```

## Data Flow Patterns

### State Resolution
```php
// Request for '_bfp_audio_engine' with product_id = 123

1. BFP_Config::get_state('_bfp_audio_engine', 'mediaelement', 123)
2. Is it global-only? No, it's overridable
3. Check memory cache for product 123
4. Not in cache, check post meta
5. Found 'wavesurfer' in post meta
6. Validate override (not 'global', valid engine)
7. Cache and return 'wavesurfer'

// If no product override:
8. Check global settings
9. Return global value or default
```

### Player Generation
```php
// Single product page, multiple files

1. Hook: woocommerce_single_product_summary (priority 25)
2. BFP_Player_Manager::include_all_players($product)
3. Get product files from meta
4. Check file count:
   - If > 3: Use table layout
   - Else: Individual players
5. For each file:
   - Generate audio URL
   - Get duration
   - Build player args
   - Call get_player()
6. Wrap in container div
7. Output HTML
```

### Resource Loading
```php
// Conditional script/style loading

1. Player needed on page
2. BFP_Player_Manager::enqueue_resources()
3. Check if already enqueued (prevent duplicates)
4. Load base CSS (style.css)
5. Get audio engine from state
6. If 'wavesurfer':
   - Load wavesurfer.min.js
   - Load bfp-wavesurfer.js
7. Else:
   - Load wp-mediaelement
8. Load engine.js
9. Localize settings for JavaScript
```

## Key Integration Points

### WooCommerce Hooks
```php
// Product display
'woocommerce_after_shop_loop_item_title' => Main player on shop
'woocommerce_single_product_summary' => All players on product page
'woocommerce_before_shop_loop_item_title' => Cover overlay

// Data handling
'woocommerce_product_export_meta_value' => Export player data
'woocommerce_product_importer_pre_expand_data' => Import player data

// Third-party compatibility
'wc_product_table_data_name' => Product table integration
```

### WordPress Core
```php
// Lifecycle
'init' => Plugin initialization
'plugins_loaded' => Load translations
'wp' => Dynamic hook registration
'admin_init' => Admin setup

// Content
'the_content' => Content filtering
'save_post' => Save product settings
'after_delete_post' => Cleanup files

// AJAX
'wp_ajax_bfp_save_settings' => Save settings via AJAX
```

## Security Checkpoints

### Admin Operations
```php
// Settings save
1. Nonce verification: wp_verify_nonce($_POST['bfp_nonce'])
2. Capability check: current_user_can('manage_options')
3. Sanitization: sanitize_text_field(), esc_url_raw(), etc.
4. Validation: in_array() for enums, min/max for numbers
```

### File Operations
```php
// Demo file creation
1. Validate product exists
2. Check user permissions
3. Sanitize file paths
4. Restrict to plugin directories
5. Set proper file permissions
```

### Output Security
```php
// Player HTML
1. esc_attr() for attributes
2. esc_url() for URLs
3. esc_html() for text
4. wp_kses_post() for rich content
```

## Performance Optimizations

### Bulk Operations
```php
// Instead of:
$setting1 = get_state('key1');
$setting2 = get_state('key2');
$setting3 = get_state('key3');

// Use:
$settings = get_states(['key1', 'key2', 'key3']);
```

### Lazy Loading
```php
// Components loaded only when needed
if (is_admin()) {
    $this->_admin = new BFP_Admin($this);
}
```

### Caching
```php
// State manager caches within request
private $_products_attrs = array();  // Product settings cache
private $_global_attrs = array();    // Global settings cache
```

## Error Handling Patterns

### Graceful Degradation
```php
// Missing product
if (!$product) {
    return '';  // Return empty, don't break page
}

// Missing files
if (empty($files)) {
    return $output;  // Return accumulated output
}
```

### Safe Array Access
```php
// Check before accessing
if (isset($files[$file_index])) {
    $file = $files[$file_index];
}

// Default values
$value = isset($args['key']) ? $args['key'] : 'default';
```

## JavaScript Integration

### Player Initialization
```javascript
// engine.js receives localized data
bfp_global_settings = {
    ajaxurl: '/wp-admin/admin-ajax.php',
    audio_engine: 'mediaelement',
    play_simultaneously: '0',
    fade_out: '0',
    on_cover: '1'
};

// Initializes players based on engine
jQuery('.bfp-player').each(function() {
    if (audio_engine === 'wavesurfer') {
        initWaveSurfer(this);
    } else {
        initMediaElement(this);
    }
});
```

### Event Flow
```
User clicks play → JavaScript prevents default → 
Checks other players → Stops if needed → 
Initializes player → Starts playback → 
Tracks analytics event
```

## Module System

### Module Loading
```php
// admin.php: load_admin_modules()
1. Check if in admin area
2. Loop through $admin_modules array
3. For each module:
   - If 'audio-engine': always load (core)
   - Else: check is_module_enabled()
   - If enabled: require module file
   - Fire 'bfp_admin_module_loaded' action
```

### Module Structure
```php
// modules/audio-engine.php
add_action('bfp_module_audio_engine_settings', 'bfp_audio_engine_settings');
function bfp_audio_engine_settings() {
    // Render module settings UI
}
```

## Common Workflows

### Adding a New Setting
1. Add to `$_overridable_settings` or `$_global_only_settings` in state-manager.php
2. Add UI in appropriate module or view
3. Handle in save_global_settings() or save_product_options()
4. Access via get_state() where needed

### Creating a Module
1. Create file in `/modules/`
2. Add to `$admin_modules` in admin.php
3. Hook into settings rendering
4. Use state manager for storage

### Debugging Player Issues
1. Check browser console for JS errors
2. Verify data attributes on audio element
3. Check state values with get_state()
4. Ensure resources enqueued
5. Validate file accessibility

For complete class reference, see STATE-MANAGEMENT.md.
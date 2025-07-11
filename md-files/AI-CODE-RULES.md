# Bandfront Player - AI Coding Rules & Guidelines

## Core Architecture Principles

### 1. State Management is King
- Always use BFP_Config class for ALL settings via get_state() method
- Context-aware inheritance: Product Setting → Global Setting → Default Value
- Never access post meta or options directly - always go through state manager
- **Example:**
  // ✅ CORRECT
  $value = $this->main_plugin->get_state('_bfp_audio_engine', 'mediaelement', $product_id);
  
  // ❌ WRONG
  $value = get_post_meta($product_id, '_bfp_audio_engine', true);

### 2. Class Architecture & Separation
Each class has ONE clear purpose:

**Core Classes:**
- BandfrontPlayer - Main coordinator class (the hub)
- BFP_Config - State management and settings (state-manager.php)
- BFP_Hooks - WordPress hook registrations ONLY (hooks.php)
- BFP_Player - Player HTML generation and resource management (player.php)
- BFP_Audio_Engine - Audio file processing and streaming (audio.php)
- BFP_Admin - Admin interface and settings pages (admin.php)
- BFP_WooCommerce - WooCommerce-specific integrations (woocommerce.php)

**Rendering Classes:**
- BFP_Player_Renderer - Context-aware player rendering (player-renderer.php)
- BFP_Playlist_Renderer - Multi-product playlist generation (playlist-renderer.php)
- BFP_Cover_Renderer - Play button overlays on images (cover-renderer.php)

**Utility Classes:**
- BFP_File_Handler - Demo file management (utils/files.php)
- BFP_Cloud_Tools - Cloud storage URL processing (utils/cloud.php)
- BFP_Cache - Cross-plugin cache clearing (utils/cache.php)
- BFP_Analytics - Playback tracking (utils/analytics.php)
- BFP_Preview - Preview handling (utils/preview.php)
- BFP_Updater - Auto updates (utils/update.php)
- BFP_Utils - General utilities (utils/utils.php)

### 3. Dependency Injection Pattern
- Classes receive main plugin instance in constructor
- Access other components through main plugin
- Example: $config = $this->main_plugin->get_config();

### 4. Context-Aware Design Rules

Shop vs Product Pages:
- Shop pages: Show button/minimal controls only
- Product pages: Show full player with all controls
- Always check context before rendering

Cover Overlay Behavior:
- Always enabled (falls back to below title if needed)
- Implemented via dedicated BFP_Cover_Renderer class
- CSS goes in external files, NO inline styles

### 5. Settings Hierarchy

Global-Only Settings (no product overrides):
- _bfp_show_in
- _bfp_player_layout
- _bfp_player_controls
- _bfp_player_title
- _bfp_on_cover
- Analytics settings
- FFmpeg settings

Product-Overridable Settings:
- _bfp_enable_player
- _bfp_audio_engine
- _bfp_secure_player
- _bfp_file_percent
- _bfp_loop
- _bfp_volume

## Development Standards

### 6. CSS Rules
- NO INLINE CSS EVER - Use external CSS files
- Organize by component: /css/style.css (frontend), /css/style-admin.css (admin), /css/skins/*.css
- BEM naming: .bfp-component__element--modifier

### 7. File Organization
/includes/
  *.php                   # Core classes (no class-bfp- prefix)
  /utils/                 # Utility classes
/modules/                 # Optional feature modules
/views/                   # Admin UI templates
/css/                     # Styles
/js/                      # Scripts

### 8. Hook Registration Pattern
- Hooks class delegates only - no logic in hook callbacks
- Dynamic registration based on context
- Example: Hook calls renderer method which contains actual logic

### 9. Security Standards
- Always escape output: esc_html(), esc_attr(), esc_url()
- Verify nonces: wp_verify_nonce()
- Sanitize input: sanitize_text_field(), wp_kses_post()
- Capability checks: current_user_can()

### 10. Performance Rules
- Lazy load components - Only instantiate when needed
- Cache in memory during request lifecycle
- Bulk fetch settings when possible
- Minimize database queries

### 11. Error Handling
- Use try-catch for external operations
- Log errors properly: error_log()
- Graceful fallbacks - never break the page

### 12. Naming Conventions
- Classes: BFP_Class_Name (prefix with BFP_)
- Methods: snake_case public, _snake_case private
- Constants: BFP_CONSTANT_NAME
- Hooks: bfp_hook_name
- Meta keys: _bfp_meta_key (underscore = hidden)
- CSS classes: .bfp-element-name
- JS globals: bfp_variable_name

### 13. Type Safety (PHP 7.4+)
- Use type declarations where possible
- Return type hints for clarity

## Feature Implementation Patterns

### 14. Adding New Features
1. Create dedicated class in /includes/
2. Register in main plugin class
3. Add hooks via Hooks class
4. Store settings via Config class
5. Add admin UI in Admin class

### 15. Renderer Pattern
For any UI output, create a dedicated renderer:
- Get settings via state manager
- Output clean HTML
- No inline styles or scripts

### 16. Module System
Optional features use the module pattern:
- Check if enabled: $config->is_module_enabled('module-name')
- Load conditionally in BFP_Admin::load_admin_modules()
- Store state in _bfp_modules_enabled

### 17. JavaScript Integration
- Localize data properly with wp_localize_script()
- Use jQuery safely: jQuery(function($) { ... });
- Namespace everything to avoid global scope pollution

## Key Components Reference

Main Classes:
- BandfrontPlayer - Main plugin orchestrator
- BFP_Config - State management (state-manager.php)
- BFP_Audio_Engine - Audio processing (audio.php)
- BFP_Player - Player generation (player.php)
- BFP_Hooks - Hook management (hooks.php)
- BFP_Admin - Admin interface (admin.php)

Renderers:
- BFP_Player_Renderer - Product players (player-renderer.php)
- BFP_Playlist_Renderer - Playlists (playlist-renderer.php)
- BFP_Cover_Renderer - Cover overlays (cover-renderer.php)

Utilities (/includes/utils/):
- BFP_File_Handler - File operations (files.php)
- BFP_Cloud_Tools - Cloud storage (cloud.php)
- BFP_Cache - Cache management (cache.php)
- BFP_Analytics - Tracking (analytics.php)
- BFP_Preview - Preview handling (preview.php)
- BFP_Updater - Auto updates (update.php)
- BFP_Utils - General utilities (utils.php)

## Remember: The Golden Rules
1. State management for EVERYTHING - Use BFP_Config
2. NO inline CSS - External files only
3. Context matters - Check if shop or product page
4. Security first - Escape, sanitize, verify
5. Single responsibility - One class, one job
6. Test the inheritance - Product → Global → Default
- **CSS classes**: `.bfp-element-name`
- **JS globals**: `bfp_variable_name`

### 13. Type Safety (PHP 7.4+)
- **Use type declarations** where possible
- **Return type hints** for clarity
- **Example:**
  ```php
  public function get_state(string $key, $default = null, ?int $product_id = null): mixed {
      // Implementation
  }
  ```

## Feature Implementation Patterns

### 15. Adding New Features
1. **Create dedicated class** in `/includes/`
2. **Register in main plugin** class
3. **Add hooks via Hooks Manager**
4. **Store settings via Config class**
5. **Add admin UI in Admin class**

### 16. Renderer Pattern
For any UI output, create a dedicated renderer:
```php
class BFP_Feature_Renderer {
    private $main_plugin;
    
    public function __construct($main_plugin) {
        $this->main_plugin = $main_plugin;
    }
    
    public function render($args = array()) {
        // Get settings via state manager
        $settings = $this->main_plugin->get_state('_bfp_feature_setting');
        
        // Output HTML
        ?>
        <div class="bfp-feature">
            <!-- HTML output -->
        </div>
        <?php
    }
}
```

### 17. Module System
Optional features use the module pattern:
- Check if enabled: `$config->is_module_enabled('module-name')`
- Load conditionally in `BFP_Admin::load_admin_modules()`
- Store state in `_bfp_modules_enabled`

### 18. JavaScript Integration
- **Localize data properly**:
  ```php
  wp_localize_script('bfp-script', 'bfp_settings', array(
      'ajaxurl' => admin_url('admin-ajax.php'),
      'nonce' => wp_create_nonce('bfp_ajax')
  ));
  ```
- **Use jQuery safely**: `jQuery(function($) { ... });`
- **Namespace everything**: Avoid global scope pollution


## Example: Adding a New Feature

```php
// 1. Create feature class
class BFP_New_Feature {
    private $main_plugin;
    
    public function __construct($main_plugin) {
        $this->main_plugin = $main_plugin;
    }
    
    public function init() {
        // Feature initialization
    }
}

// 2. Register in main plugin
private function init_components() {
    // ...existing components...
    $this->new_feature = new BFP_New_Feature($this);
}

// 3. Add hooks in Hooks Manager
private function register_hooks() {
    // ...existing hooks...
    add_action('init', array($this->main_plugin->get_new_feature(), 'init'));
}

// 4. Add settings to Config class
private $_overridable_settings = array(
    // ...existing settings...
    '_bfp_new_feature_enabled' => false,
);
```




# Bandfront Player - AI Coding Rules & Guidelines

## Core Architecture Principles

## Remember: The Golden Rules
1. **State management for EVERYTHING** - Use BFP_Config
2. **NO inline CSS** - External files only
3. **Context matters** - Check if shop or product page
4. **Security first** - Escape, sanitize, verify
5. **Single responsibility** - One class, one job
6. **Test the inheritance** - Product → Global → Default

### 1. State Management is King
- **ALWAYS use the BFP_Config class** for ALL settings via `get_state()` method
- **Context-aware inheritance**: Product Setting → Global Setting → Default Value
- **Never access post meta or options directly** - always go through the state manager
- **Example:**
  ```php
  // ✅ CORRECT
  $value = $this->main_plugin->get_state('_bfp_audio_engine', 'mediaelement', $product_id);
  
  // ❌ WRONG
  $value = get_post_meta($product_id, '_bfp_audio_engine', true);
  ```

### 2. Class Architecture & Separation - UPDATED STRUCTURE

**Core Classes** (in `/includes/core/`):
- **BFP_Plugin** - Main coordinator class (rename from BandfrontPlayer)
- **BFP_Config** - State management and settings
- **BFP_Hooks** - WordPress hook registrations (rename from BFP_Hooks_Manager)

**Audio Processing** (in `/includes/audio/`):
- **BFP_Audio_Engine** - Audio processing and streaming (rename from BFP_Audio_Processor)
- **BFP_Audio_Player** - Player generation logic (rename from BFP_Player_Manager)
- **BFP_Audio_Analytics** - Playback tracking (rename from BFP_Analytics)

**Rendering** (in `/includes/renderers/`):
- **BFP_Player_Renderer** - Main player HTML generation
- **BFP_Playlist_Renderer** - Playlist HTML generation  
- **BFP_Cover_Renderer** - Cover overlay rendering (rename from BFP_Cover_Overlay_Renderer)

**Integrations** (in `/includes/integrations/`):
- **BFP_WooCommerce** - WooCommerce-specific functionality
- **BFP_Cache** - Cache management (rename from BFP_Cache_Manager)
- **BFP_Cloud** - Cloud storage tools (rename from BFP_Cloud_Tools)

**Admin** (in `/includes/admin/`):
- **BFP_Admin_Core** - Main admin functionality
- **BFP_Admin_Settings** - Settings page handling
- **BFP_Admin_Metabox** - Product metabox handling

**Utilities** (in `/includes/utils/`):
- **BFP_File_Handler** - File operations
- **BFP_Utils** - General utilities
- **BFP_Preview** - Preview management (rename from BFP_Preview_Manager)

### 3. Dependency Injection Pattern
- Classes receive the main plugin instance in constructor
- Access other components through the main plugin
- Example:
  ```php
  class BFP_Audio_Engine {
      private $plugin;
      
      public function __construct($plugin) {
          $this->plugin = $plugin;
      }
      
      public function process_audio() {
          // Access other components through main plugin
          $config = $this->plugin->get_config();
          $cache = $this->plugin->get_cache();
      }
  }
  ```

### 4. Context-Aware Design Rules

#### Shop vs Product Pages
- **Shop pages**: Show button/minimal controls only
- **Product pages**: Show full player with all controls
- **Always check context** before rendering:
  ```php
  if (function_exists('is_product') && is_product()) {
      // Product page logic
  } else {
      // Shop/archive page logic
  }
  ```

#### Cover Overlay Behavior
- **Always enabled** as per rules (falls back to below title if needed)
- Implemented via dedicated `BFP_Cover_Renderer` class
- CSS goes in external files, NO inline styles

### 5. Settings Hierarchy

#### Global-Only Settings (no product overrides):
- `_bfp_show_in`
- `_bfp_player_layout` 
- `_bfp_player_controls`
- `_bfp_player_title`
- `_bfp_on_cover`
- Analytics settings
- FFmpeg settings

#### Product-Overridable Settings:
- `_bfp_enable_player`
- `_bfp_audio_engine`
- `_bfp_secure_player`
- `_bfp_file_percent`
- `_bfp_loop`
- `_bfp_volume`

## Development Standards

### 6. CSS Rules
- **NO INLINE CSS EVER** - Use external CSS files
- **Organize by component**: 
  - `/css/style.css` - Frontend styles
  - `/css/style.admin.css` - Admin styles
  - `/css/skins/*.css` - Player skins
- **Reuse classes** - Don't duplicate styles
- **BEM naming**: `.bfp-component__element--modifier`

### 7. File Organization - UPDATED STRUCTURE
```
/includes/
  /core/
    class-bfp-plugin.php           # Main coordinator (was BandfrontPlayer)
    class-bfp-config.php           # State management
    class-bfp-hooks.php            # Hook registration
  /audio/
    class-bfp-audio-engine.php     # Audio processing
    class-bfp-audio-player.php     # Player generation
    class-bfp-audio-analytics.php  # Playback tracking
  /renderers/
    class-bfp-player-renderer.php  # Player HTML
    class-bfp-playlist-renderer.php # Playlist HTML
    class-bfp-cover-renderer.php   # Cover overlay
  /integrations/
    class-bfp-woocommerce.php      # WooCommerce integration
    class-bfp-cache.php            # Cache management
    class-bfp-cloud.php            # Cloud storage
  /admin/
    class-bfp-admin-core.php       # Main admin
    class-bfp-admin-settings.php   # Settings page
    class-bfp-admin-metabox.php    # Product metabox
  /utils/
    class-bfp-file-handler.php     # File operations
    class-bfp-utils.php            # Utilities
    class-bfp-preview.php          # Preview handling
/modules/
  /audio-engine/
    audio-engine.php               # Audio engine module
  /cloud-storage/
    cloud-storage.php              # Cloud storage module
/views/
  /admin/
    global-settings.php            # Global settings page
    product-metabox.php            # Product settings metabox
/css/
  style.css                       # Frontend styles
  style.admin.css                 # Admin styles
  /skins/                         # Player skins
/js/
  engine.js                       # Main player engine
  wavesurfer.js                   # WaveSurfer integration
```

### 8. Hook Registration Pattern
- **Hooks class delegates only** - no logic in hook callbacks
- **Dynamic registration** based on context
- **Example:**
  ```php
  // In BFP_Hooks class
  public function add_play_button_on_cover() {
      $this->plugin->get_cover_renderer()->render();
  }
  ```

### 9. Security Standards
- **Always escape output**: `esc_html()`, `esc_attr()`, `esc_url()`
- **Verify nonces**: `wp_verify_nonce()`
- **Sanitize input**: `sanitize_text_field()`, `wp_kses_post()`
- **Capability checks**: `current_user_can()`
- **Example:**
  ```php
  if (!wp_verify_nonce($_POST['bfp_nonce'], 'bfp_action')) {
      return;
  }
  echo esc_html($text);
  ```

### 10. Performance Rules
- **Lazy load components** - Only instantiate when needed
- **Cache in memory** during request lifecycle
- **Bulk fetch settings** when possible:
  ```php
  $settings = $this->plugin->get_states(array(
      '_bfp_preload',
      '_bfp_player_layout',
      '_bfp_player_volume'
  ), $product_id);
  ```
- **Minimize database queries**

### 11. Error Handling
- **Use try-catch** for external operations
- **Log errors** properly: `error_log()`
- **Graceful fallbacks** - never break the page
- **Example:**
  ```php
  try {
      // Risky operation
  } catch (Exception $e) {
      error_log('BFP Error: ' . $e->getMessage());
      return $default_value; // Graceful fallback
  }
  ```

### 12. Naming Conventions - UPDATED
- **Classes**: `BFP_Class_Name` (consistent BFP_ prefix)
- **Methods**: `snake_case` public, `_snake_case` private
- **Constants**: `BFP_CONSTANT_NAME`
- **Hooks**: `bfp_hook_name`
- **Meta keys**: `_bfp_meta_key` (underscore = hidden)
- **CSS classes**: `.bfp-component__element--modifier`
- **JS globals**: `bfp_variable_name`
- **File names**: `class-bfp-component-name.php`

### 13. Class Naming Rules
- **Core classes**: `BFP_Plugin`, `BFP_Config`, `BFP_Hooks`
- **Feature classes**: `BFP_Audio_Engine`, `BFP_Player_Renderer`
- **Integration classes**: `BFP_WooCommerce`, `BFP_Cache`
- **Admin classes**: `BFP_Admin_Core`, `BFP_Admin_Settings`
- **Utility classes**: `BFP_Utils`, `BFP_File_Handler`

### 14. Documentation Standards
```php
/**
 * Brief description of method purpose
 *
 * @param string $param Description of parameter
 * @return mixed What the method returns
 * @since 0.1
 */
public function method_name(string $param) {
    // Implementation
}
```

## Feature Implementation Patterns

### 15. Adding New Features
1. **Create dedicated class** in appropriate `/includes/` subdirectory
2. **Register in main plugin** class
3. **Add hooks via BFP_Hooks class**
4. **Store settings via BFP_Config class**
5. **Add admin UI in appropriate admin class**

### 16. Renderer Pattern
For any UI output, create a dedicated renderer:
```php
class BFP_Feature_Renderer {
    private $plugin;
    
    public function __construct($plugin) {
        $this->plugin = $plugin;
    }
    
    public function render($args = array()) {
        // Get settings via state manager
        $settings = $this->plugin->get_state('_bfp_feature_setting');
        
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
- Load conditionally in `BFP_Admin_Core::load_admin_modules()`
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

## Testing & Quality

### 19. Pre-commit Checklist
- [ ] All settings use `get_state()` method
- [ ] No inline CSS
- [ ] Output is escaped
- [ ] Nonces are verified
- [ ] Errors have try-catch
- [ ] Classes follow single responsibility
- [ ] Hooks are registered in BFP_Hooks class
- [ ] Documentation is complete
- [ ] File is in correct directory
- [ ] Class name follows conventions

### 20. Common Pitfalls to Avoid
- ❌ Direct database queries without caching
- ❌ Inline styles or JavaScript
- ❌ Logic in hook callbacks
- ❌ Accessing settings without state manager
- ❌ Missing security escaping
- ❌ Tight coupling between classes
- ❌ Global variables (use class properties)
- ❌ Inconsistent class naming
- ❌ Files in wrong directories

## Example: Adding a New Feature

```php
// 1. Create feature class in appropriate directory
// File: /includes/audio/class-bfp-audio-visualizer.php
class BFP_Audio_Visualizer {
    private $plugin;
    
    public function __construct($plugin) {
        $this->plugin = $plugin;
    }
    
    public function init() {
        // Feature initialization
    }
}

// 2. Register in main plugin
private function init_components() {
    // ...existing components...
    $this->audio_visualizer = new BFP_Audio_Visualizer($this);
}

// 3. Add hooks in BFP_Hooks class
private function register_hooks() {
    // ...existing hooks...
    add_action('bfp_player_ready', array($this->plugin->get_audio_visualizer(), 'init'));
}

// 4. Add settings to BFP_Config class
private $_overridable_settings = array(
    // ...existing settings...
    '_bfp_visualizer_enabled' => false,
);
```

## Migration Plan for Existing Classes

### Phase 1: Directory Restructure
1. Create new directory structure
2. Move classes to appropriate directories
3. Update require_once paths in main plugin file

### Phase 2: Class Renames
1. `BandfrontPlayer` → `BFP_Plugin`
2. `BFP_Hooks_Manager` → `BFP_Hooks`
3. `BFP_Audio_Processor` → `BFP_Audio_Engine`
4. `BFP_Player_Manager` → `BFP_Audio_Player`
5. `BFP_Cover_Overlay_Renderer` → `BFP_Cover_Renderer`

### Phase 3: Split Large Classes
1. Split `BFP_Admin` into `BFP_Admin_Core`, `BFP_Admin_Settings`, `BFP_Admin_Metabox`
2. Extract utilities from main classes

This organization is much more scalable, follows WordPress standards better, and makes the codebase easier to navigate and maintain.


-------







# Bandfront Player - AI Coding Rules & Guidelines

## Core Principles 
## Remember

Lightweight & Performance-First
Class Separation & Single Responsibility - Each class has ONE primary purpose:
Array Destructuring: Modern syntax for cleaner code
Error Handling: Use try-catch for external operations
- **KISS**: Keep It Simple, Stupid
- **DRY**: Don't Repeat Yourself
- **YAGNI**: You Aren't Gonna Need It
- **SOLID**: Single responsibility, Open/closed, Liskov substitution, Interface segregation, Dependency inversion
NO INLINE CSS.
COMBINE AND REUSE CSS AS MUCH AS POSSIBLE

everything must use state management rules /var/www/html/wp-content/plugins/bandfront-player/includes/class-bfp-config.php

the cover overlay function is always on it falls back to play undernearth product on main shop page, but this should always work with our theme.



wordpress best practices
- **Minimize Database Queries**: Cache all retrieved data in memory during request lifecycle
- **Lazy Loading**: Only load classes/features when actually needed
- **Asset Optimization**: Enqueue scripts/styles only on pages that need them
- **Avoid Redundancy**: Never duplicate functionality across classes
- **Use WordPress APIs**: Prefer WP functions over PHP natives when available
- **Hooks Over Direct Calls**: Use actions/filters for extensibility
- **Capability Checks**: Always verify user permissions before actions

### 2. Context-Aware Design
- **Global vs Product Settings**: 
  - Global settings are defaults stored in `bfp_global_settings` option
  - Product settings are overrides stored as post meta with `_bfp_` prefix
  - Always check product meta first, fall back to global if not set
  - Use `get_product_attr()` method which handles this automatically
- **Page Context Matters**:
  - Shop pages: Show button/track controls only
  - Product pages: Show full player controls
  - Use `is_product()` and similar checks to determine context

- **Security First**:
  ```php
  // Always escape output
  echo esc_html($text);
  echo esc_attr($attribute);
  echo esc_url($url);
  
  // Verify nonces
  if (!wp_verify_nonce($_POST['bfp_nonce'], 'bfp_action')) {
      return;
  }
  
  // Sanitize input
  $value = sanitize_text_field($_POST['field']);
  ```


### 4. PHP 8+ Best Practices
- **Type Declarations**: Use type hints where possible
  ```php
  public function get_player(string $url, array $args = []): string
  ```
- **Null Coalescing**: Use `??` operator for defaults
  ```php
  $value = $_POST['field'] ?? 'default';
  ```



### 6. Data Flow Rules
```
User Request → Main Plugin → Specialized Class → Return to Main → Output
```
- Main plugin class (`BandfrontPlayer`) acts as coordinator
- Specialized classes handle specific tasks
- Never bypass the main plugin for cross-class communication

### 7. Settings Hierarchy
1. **Check product-specific setting first**
2. **Fall back to global setting**
3. **Use hardcoded constant as last resort**

Example implementation:
```php
public function get_product_attr($product_id, $attr, $default = false) {
    // Check product meta first
    if (metadata_exists('post', $product_id, $attr)) {
        return get_post_meta($product_id, $attr, true);
    }
    // Fall back to global
    return $this->get_global_attr($attr, $default);
}
```

### 8. Hook Registration Rules
- **Dynamic Registration**: Register hooks based on context in `register_dynamic_hooks()`
- **Priority Matters**: Use appropriate priorities (1-10 for early, 20+ for late)
- **Avoid Conflicts**: Never register same callback twice
- **Context Checking**: Only add hooks where needed:
  ```php
  if (function_exists('is_product') && is_product()) {
      add_action('woocommerce_single_product_summary', [$this, 'include_all_players']);
  }
  ```

### 9. Naming Conventions
- **Classes**: `BFP_Class_Name` (prefix with BFP_)
- **Methods**: `snake_case` for public, `_snake_case` for private
- **Constants**: `BFP_CONSTANT_NAME`
- **Hooks**: `bfp_hook_name`
- **Meta Keys**: `_bfp_meta_key` (underscore prefix for hidden)

### 10. Documentation Standards
```php
/**
 * Brief description of method
 *
 * @param string $param Description of parameter
 * @return mixed What the method returns
 * @since 0.1
 */
public function method_name($param) {
    // Implementation
}
```

### 11. Database Operations
- **Always Prepare Queries**: Use `$wpdb->prepare()`
- **Cache Results**: Store in class properties during request
- **Batch Operations**: Group updates when possible
- **Clean Up**: Remove data on uninstall

### 12. JavaScript Integration
- **Localize Data**: Pass PHP data via `wp_localize_script()`
- **Namespace Everything**: Avoid global scope pollution
- **jQuery Safety**: Always use `jQuery` not `$` in global scope
- **Progressive Enhancement**: JS should enhance, not be required

## Feature Implementation Rules

### 13. Audio Engine Flexibility
- Support multiple engines (MediaElement.js, WaveSurfer.js)
- Abstract engine-specific code
- Graceful fallbacks if engine unavailable
- Engine selection: Global default → Product override

### 14. File Handling
- **Security**: Validate all file operations
- **Paths**: Use WordPress upload directory structure
- **Cleanup**: Remove temporary files after use
- **Permissions**: Check write permissions before operations

### 15. WooCommerce Integration
- **Check Existence**: Always verify WooCommerce is active
- **Use WC Functions**: Prefer `wc_get_product()` over direct queries
- **Product Types**: Handle all types (simple, variable, grouped)
- **Compatibility**: Test with major WC versions

## Testing & Quality Rules

### 16. Error Handling
```php
try {
    // Risky operation
} catch (Exception $e) {
    error_log('BFP Error: ' . $e->getMessage());
    // Graceful fallback
}
```

### 17. Debugging
- Use `error_log()` for debugging, never `echo` in production
- Add debug constants for development
- Implement proper logging system

### 18. Performance Monitoring
- Profile slow operations
- Monitor memory usage
- Optimize database queries
- Cache expensive calculations

## Security Rules

### 19. Input Validation
- Never trust user input
- Validate data types and ranges
- Sanitize before storage
- Escape before output

### 20. File Security
- Validate file types for audio
- Check file sizes
- Prevent directory traversal
- Use WordPress file upload APIs

## Maintenance Rules

### 21. Backward Compatibility
- Maintain compatibility for 2 major WP versions
- Deprecate features gracefully
- Provide migration paths
- Document breaking changes

### 22. Future-Proofing
- Write modular, extensible code
- Use interfaces for swappable components
- Avoid hard dependencies
- Plan for scalability

## Example Implementation Pattern

```php
/**
 * Proper implementation following all rules
 */
class BFP_Example_Handler {
    private $main_plugin;
    private $cached_data = [];
    
    public function __construct($main_plugin) {
        $this->main_plugin = $main_plugin;
    }
    
    public function get_data(int $product_id): array {
        // Check cache first
        if (isset($this->cached_data[$product_id])) {
            return $this->cached_data[$product_id];
        }
        
        // Security check
        if (!current_user_can('read')) {
            return [];
        }
        
        try {
            // Get data with proper escaping
            $data = $this->main_plugin->get_product_attr(
                $product_id,
                '_bfp_example',
                [] // default
            );
            
            // Cache for this request
            $this->cached_data[$product_id] = $data;
            
            return $data;
            
        } catch (Exception $e) {
            error_log('BFP Example Error: ' . $e->getMessage());
            return [];
        }
    }
}
```


# AI Code Rules for Bandfront Player

## Core Principles

1. **State Management First**
   - All settings MUST go through the state manager (`BFP_Config`)
   - Never access post meta or options directly
   - Use bulk fetch (`get_states()`) when retrieving multiple values
   - Respect the inheritance hierarchy: Product → Global → Default

2. **Component Access**
   - Always access components through the main plugin instance getters
   - Never instantiate components directly
   - Follow initialization order (see STATE-MANAGEMENT.md)

3. **Class Architecture**
   - **Refer to STATE-MANAGEMENT.md for all class information**
   - No need to define classes here - they're documented in the state management file
   - Focus on implementation patterns and rules

## Critical Rules

### 1. State Access Patterns

```php
// ✅ CORRECT - Single value
$value = $this->main_plugin->get_config()->get_state('_bfp_audio_engine', 'mediaelement', $product_id);

// ✅ CORRECT - Multiple values (PREFERRED)
$settings = $this->main_plugin->get_config()->get_states(array(
    '_bfp_audio_engine',
    '_bfp_play_simultaneously',
    '_bfp_fade_out'
), $product_id);

// ❌ WRONG - Direct meta access
$value = get_post_meta($product_id, '_bfp_audio_engine', true);

// ❌ WRONG - Direct option access
$value = get_option('_bfp_audio_engine');
```

### 2. Component Access

```php
// ✅ CORRECT
$audio_core = $this->main_plugin->get_audio_core();
$player = $this->main_plugin->get_player_manager();

// ❌ WRONG
$audio_core = new BFP_Audio_Engine($this->main_plugin);
```

### 3. Context Awareness

```php
// Check context before registering hooks
if (function_exists('is_product') && is_product()) {
    // Single product page logic
} else {
    // Archive/shop page logic
}
```

### 4. File Naming Convention

- Core classes: `/includes/{function}.php` (e.g., `player.php`, `audio.php`)
- Utilities: `/includes/utils/{utility}.php` (e.g., `files.php`, `cache.php`)
- Modules: `/modules/{module-name}.php` (e.g., `audio-engine.php`)
- No `class-` prefix in filenames

### 5. Security Rules

```php
// Always sanitize input
$value = sanitize_text_field(wp_unslash($_POST['field']));

// Always verify nonces
if (!wp_verify_nonce($_POST['nonce'], 'action')) {
    return;
}

// Always check capabilities
if (!current_user_can('manage_options')) {
    return;
}

// Always escape output
echo esc_html($value);
echo esc_attr($attribute);
echo esc_url($url);
```

### 6. Performance Rules

1. **Use bulk operations** when dealing with multiple settings
2. **Enqueue resources only when needed** (check context first)
3. **Cache expensive operations** in class properties
4. **Lazy load components** - initialize only when needed

### 7. Hook Registration

```php
// Dynamic hook registration based on context
public function register_dynamic_hooks() {
    $hooks_config = $this->get_hooks_config();
    
    foreach ($hooks_config['main_player'] as $hook => $priority) {
        add_action($hook, array($this->main_plugin->get_player(), 'include_main_player'), $priority);
    }
}
```

### 8. Error Handling

```php
// Graceful degradation
if (!$product) {
    return '';  // Return empty string, not false or null
}

// Check method existence
if (method_exists($object, 'method_name')) {
    $object->method_name();
}

// Array bounds checking
if (isset($array[$key])) {
    $value = $array[$key];
}
```

### 9. Data Attributes

```html
<!-- Player MUST have these attributes -->
<audio 
    class="bfp-player {style} {track-class-if-button}"
    data-product="{product-id}"
    data-file-index="{index}"
    data-audio-engine="{engine}"
>
```

### 10. Module System

```php
// Check module status before using features
if ($this->main_plugin->get_config()->is_module_enabled('cloud-engine')) {
    // Cloud features available
}
```

## Common Pitfalls to Avoid

1. **Never use legacy methods** like `get_global_attr()` or `get_product_attr()`
2. **Never hardcode settings** - always use state manager
3. **Never assume WooCommerce is active** - check first
4. **Never output without escaping**
5. **Never trust user input** - always sanitize

## Testing Checklist

- [ ] State manager used for all settings?
- [ ] Components accessed via getters?
- [ ] Context checked before hooks?
- [ ] Input sanitized?
- [ ] Output escaped?
- [ ] Nonces verified?
- [ ] Capabilities checked?
- [ ] Bulk fetch used where appropriate?
- [ ] Resources enqueued conditionally?

## Quick Reference

For detailed class information, methods, and architecture:
- See `STATE-MANAGEMENT.md` for complete class reference
- See `MAP.md` for detailed code flow
- See `MAP_OVERVIEW.md` for high-level architecture
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




# State Management Refactoring Guide

## Overview
This guide documents the complete refactoring of the Bandfront Player plugin's state management system from scattered, inconsistent settings retrieval to a centralized, context-aware state management architecture.

## Objective
Transform the plugin from using direct `get_post_meta()` and `get_option()` calls throughout the codebase to a centralized state management system that provides:
- Context-aware inheritance (Product → Global → Default)
- Performance optimization through bulk fetching
- Type safety for admin forms
- Backward compatibility

## Step-by-Step Instructions

### Phase 1: Analyze Current State Management

1. **Identify State Access Patterns**
   - Search for all `get_post_meta()` calls with `_bfp_` prefix
   - Search for all `get_option()` calls related to plugin settings
   - Search for direct access to `$this->main_plugin->get_global_attr()`
   - Search for direct access to `$this->main_plugin->get_product_attr()`
   - Document all setting keys and their usage patterns

2. **Map Setting Inheritance**
   - Identify which settings can be overridden at product level
   - Identify which settings are global-only
   - Document the inheritance hierarchy needed

### Phase 2: Design the State Management Architecture

1. **Create Core State Manager**
   ```php
   // Create includes/state-manager.php (formerly class-bfp-config.php)
   class BFP_Config {
       // Core methods to implement:
       - get_state($key, $default, $product_id, $options)  // Single value
       - get_states($keys, $product_id)                    // Bulk fetch
       - get_player_state($product_id)                     // Minimal frontend
       - get_admin_form_settings()                         // Admin with type casting
   }
   ```

2. **Define Setting Categories**
   ```php
   private $_overridable_settings = array(
       '_bfp_enable_player',
       '_bfp_audio_engine',
       '_bfp_secure_player',
       // ... etc
   );
   
   private $_global_only_settings = array(
       '_bfp_show_in',
       '_bfp_player_layout',
       '_bfp_on_cover',
       // ... etc
   );
   ```

### Phase 3: Implement Context-Aware Inheritance

1. **Core get_state() Method**
   ```php
   public function get_state($key, $default = null, $product_id = null, $options = array()) {
       // 1. Check if setting is global-only
       if ($this->is_global_only($key)) {
           return $this->get_global_value($key, $default);
       }
       
       // 2. Try product-level first (if product_id provided)
       if ($product_id && $this->is_overridable($key)) {
           $product_value = get_post_meta($product_id, $key, true);
           if ($this->is_valid_override($product_value, $key)) {
               return $product_value;
           }
       }
       
       // 3. Fall back to global
       $global_value = $this->get_global_value($key, $default);
       
       // 4. Apply filters and return
       return apply_filters('bfp_state_value', $global_value, $key, $product_id);
   }
   ```

### Phase 4: Refactor All State Access Points

1. **Update Main Plugin File (bfp.php)**
   - Add delegation methods that forward to config
   - Ensure backward compatibility
   ```php
   public function get_state($key, $default = null, $product_id = null) {
       return $this->config->get_state($key, $default, $product_id);
   }
   ```

2. **Update Each Component File**
   
   **Pattern to follow for each file:**
   ```php
   // BEFORE:
   $setting = get_post_meta($product_id, '_bfp_setting', true);
   $global = get_option('_bfp_global_setting');
   $attr = $this->main_plugin->get_global_attr('_bfp_attr');
   
   // AFTER:
   $setting = $this->main_plugin->get_config()->get_state('_bfp_setting', $default, $product_id);
   
   // For multiple values (performance):
   $settings = $this->main_plugin->get_config()->get_states(array(
       '_bfp_setting1',
       '_bfp_setting2',
       '_bfp_setting3'
   ), $product_id);
   ```

3. **Files to Update (in order):**
   - `includes/audio.php` - Audio processing
   - `includes/player.php` - Player rendering
   - `includes/woocommerce.php` - WooCommerce integration
   - `includes/admin.php` - Admin interface
   - `includes/hooks.php` - Hook management
   - `includes/cover-renderer.php` - Cover overlay
   - `includes/utils/*.php` - All utility files
   - `views/global-admin-options.php` - Admin settings view
   - `views/product-options.php` - Product settings view

### Phase 5: Implement Bulk Fetching

1. **Add get_states() Method**
   ```php
   public function get_states($keys, $product_id = null) {
       $result = array();
       
       // Separate keys by type for efficient fetching
       $global_keys = array();
       $overridable_keys = array();
       
       foreach ($keys as $key) {
           if ($this->is_global_only($key)) {
               $global_keys[] = $key;
           } else {
               $overridable_keys[] = $key;
           }
       }
       
       // Fetch all at once where possible
       // Return associative array
       return $result;
   }
   ```

2. **Update High-Traffic Areas**
   - Player initialization
   - Admin forms
   - Shortcode rendering

### Phase 6: Add Specialized Methods

1. **Frontend Player State**
   ```php
   public function get_player_state($product_id = null) {
       // Return only essential settings for player initialization
       // Minimize data sent to frontend
   }
   ```

2. **Admin Form State**
   ```php
   public function get_admin_form_settings() {
       // Include type casting for form fields
       // Handle checkboxes, selects, etc.
   }
   ```

### Phase 7: Testing & Validation

1. **Create Test Checklist**
   - [ ] Single product player loads correctly
   - [ ] Bulk product players load correctly
   - [ ] Product settings override global settings
   - [ ] Global-only settings cannot be overridden
   - [ ] Admin forms save correctly
   - [ ] Bulk operations work
   - [ ] Performance is maintained or improved

2. **Audit Each Component**
   - Verify no direct `get_post_meta()` calls remain
   - Verify no direct `get_option()` calls remain
   - Ensure all state access goes through state manager
   - Check filter applications work correctly

### Phase 8: Documentation

1. **Update Code Documentation**
   - Add docblocks explaining the state management system
   - Document inheritance hierarchy
   - Provide usage examples

2. **Create Developer Guide**
   - How to add new settings
   - How to access settings
   - Performance best practices

## Key Patterns to Enforce

### 1. Single Value Retrieval
```php
// Use for individual settings
$value = $config->get_state('_bfp_setting', $default, $product_id);
```

### 2. Bulk Retrieval
```php
// Use when you need 2+ settings
$settings = $config->get_states(array('key1', 'key2'), $product_id);
```

### 3. Context Awareness
```php
// Product pages get product-specific settings
// Global pages get global settings
// Automatic inheritance handling
```

### 4. Type Safety
```php
// Admin forms get proper type casting
$form_data = $config->get_admin_form_settings();
```

## Common Pitfalls to Avoid

1. **Don't access settings directly** - Always use state manager
2. **Don't fetch one-by-one in loops** - Use bulk fetch
3. **Don't assume setting location** - Let state manager handle inheritance
4. **Don't forget filters** - Maintain extensibility
5. **Don't break backward compatibility** - Keep legacy methods working

## Performance Considerations

1. **Use Caching**
   - Product settings are cached per request
   - Global settings are cached per request
   - Clear cache on updates

2. **Bulk Operations**
   - Fetch multiple settings at once
   - Minimize database queries
   - Use transients for expensive operations

3. **Lazy Loading**
   - Only load settings when needed
   - Don't preload everything

## Migration Checklist

- [ ] State manager created and tested
- [ ] All files updated to use state manager
- [ ] No direct database access remains
- [ ] Bulk fetching implemented where appropriate
- [ ] Admin forms working correctly
- [ ] Player rendering unchanged from user perspective
- [ ] Performance maintained or improved
- [ ] Documentation updated
- [ ] Legacy methods maintained for compatibility

## Expected Outcomes

1. **Centralized State Management** - All settings access through one system
2. **Improved Performance** - Bulk fetching and caching
3. **Better Maintainability** - Single source of truth
4. **Enhanced Flexibility** - Easy to add new settings
5. **Backward Compatibility** - Existing integrations continue working

## Validation

Run these checks to ensure successful refactoring:

```bash
# No direct get_post_meta calls for plugin settings
grep -r "get_post_meta.*_bfp_" --include="*.php" --exclude="state-manager.php"

# No direct get_option calls for plugin settings  
grep -r "get_option.*bfp" --include="*.php" --exclude="state-manager.php"

# No direct access to get_global_attr outside config
grep -r "get_global_attr" --include="*.php" --exclude="state-manager.php"
```

All searches should return minimal or no results (except for backward compatibility methods).

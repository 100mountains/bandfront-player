# Hooks Registration System

## Overview

The Hooks class is responsible for registering all WordPress hooks, filters, and actions after all components have been initialized. It ensures proper timing and prevents errors from missing dependencies.

## Registration Flow

```
Bootstrap completes component initialization
    ↓
Hooks class instantiated with Bootstrap reference
    ↓
registerHooks() called immediately
    ↓
Hooks registered in specific order
```

## Hook Registration Order

### 1. Plugin Lifecycle Hooks

```php
private function registerHooks(): void {
    // FIRST: Plugin activation/deactivation
    $pluginFile = $this->bootstrap->getPluginFile();
    register_activation_hook($pluginFile, [$this->bootstrap, 'activate']);
    register_deactivation_hook($pluginFile, [$this->bootstrap, 'deactivate']);
    
    // Continue with other hooks...
}
```

### 2. Core WordPress Hooks

```php
private function registerCoreHooks(): void {
    // Early WordPress lifecycle
    add_action('plugins_loaded', [$this, 'onPluginsLoaded']);
    add_action('init', [$this, 'onInit']);
    
    // Frontend assets
    add_action('wp_enqueue_scripts', [$this, 'enqueuePublicAssets']);
    
    // Shortcodes (registered on init)
    add_action('init', [$this, 'registerShortcodes']);
}
```

### 3. Context-Specific Hook Registration

```php
private function registerHooks(): void {
    // Core hooks always registered
    $this->registerCoreHooks();
    
    // WooCommerce hooks only if active
    $this->registerWooCommerceHooks();
    
    // REST API hooks
    $this->registerRestHooks();
    
    // Admin hooks only in admin
    if (is_admin()) {
        $this->registerAdminHooks();
    }
    
    // Frontend hooks only on frontend
    if (!is_admin()) {
        $this->registerFrontendHooks();
    }
}
```

## Hook Categories

### Core WordPress Hooks

| Hook | Type | Priority | Handler | Purpose |
|------|------|----------|---------|---------|
| plugins_loaded | action | 10 | onPluginsLoaded | Load text domain |
| init | action | 10 | onInit | General initialization |
| wp_enqueue_scripts | action | 10 | enqueuePublicAssets | Load frontend assets |

### WooCommerce Hooks

| Hook | Type | Priority | Handler | Purpose |
|------|------|----------|---------|---------|
| woocommerce_before_single_product_summary | action | 25 | maybeAddPlayer | Product page player |
| woocommerce_single_product_summary | action | 25 | maybeAddPlayer | Alternative position |
| woocommerce_after_shop_loop_item_title | action | 1 | maybeAddShopPlayer | Shop page player |
| woocommerce_cart_item_name | filter | 10 | maybeAddCartPlayer | Cart player |
| woocommerce_product_export_meta_value | filter | 10 | exportMetaValue | Export support |
| woocommerce_product_importer_pre_expand_data | filter | 10 | importMetaValue | Import support |

### Admin Hooks

| Hook | Type | Priority | Handler | Purpose |
|------|------|----------|---------|---------|
| admin_menu | action | 10 | registerAdminMenu | Add menu items |
| admin_enqueue_scripts | action | 10 | enqueueAdminAssets | Load admin assets |
| add_meta_boxes | action | 10 | registerMetaboxes | Product metaboxes |
| save_post | action | 10 | saveProductMeta | Save product data |

### REST API Hooks

| Hook | Type | Priority | Handler | Purpose |
|------|------|----------|---------|---------|
| rest_api_init | action | 10 | registerRestRoutes | Register endpoints |

## Component-Specific Hook Registration

### Player Hooks

```php
private function registerPlayerHooks(): void {
    $player = $this->bootstrap->getComponent('player');
    if (!$player) return;
    
    // Asset loading
    add_action('wp_enqueue_scripts', [$player, 'enqueueAssets']);
    
    // Shortcodes
    add_shortcode('bfp-player', [$this, 'playerShortcode']);
    add_shortcode('bfp-playlist', [$this, 'playlistShortcode']);
    
    // Auto-insertion hooks
    if ($this->shouldAutoInsert()) {
        add_action('woocommerce_single_product_summary', 
            [$player, 'render'], 25);
    }
}
```

### Analytics Hooks

```php
private function registerAnalyticsHooks(): void {
    $analytics = $this->bootstrap->getComponent('analytics');
    if (!$analytics) return;
    
    // Track plays via AJAX
    add_action('wp_ajax_bfp_track_play', [$analytics, 'trackPlay']);
    add_action('wp_ajax_nopriv_bfp_track_play', [$analytics, 'trackPlay']);
    
    // Track via REST
    add_action('bfp_stream_started', [$analytics, 'trackStream'], 10, 2);
}
```

### Preview Hooks

```php
private function registerPreviewHooks(): void {
    $preview = $this->bootstrap->getComponent('preview');
    if (!$preview) return;
    
    // Preview generation
    add_action('bfp_generate_preview', [$preview, 'generate'], 10, 2);
    
    // Cleanup old previews
    add_action('bfp_daily_cleanup', [$preview, 'cleanup']);
}
```

## Hook Timing Considerations

### Early Hooks (plugins_loaded)

```php
public function onPluginsLoaded(): void {
    // Load translations early
    load_plugin_textdomain(
        'bandfront-player',
        false,
        dirname(plugin_basename($this->bootstrap->getPluginFile())) . '/languages'
    );
    
    // Allow early filtering
    apply_filters('bandfront_player_loaded', $this->bootstrap);
}
```

### Standard Hooks (init)

```php
public function onInit(): void {
    // Register post types if needed
    // Register taxonomies if needed
    // Flush rewrite rules if needed
    
    // Fire custom action
    do_action('bandfront_player_init');
}
```

### Late Hooks (wp_loaded)

```php
// For hooks that need everything loaded
add_action('wp_loaded', function() {
    // All plugins loaded
    // Theme loaded
    // User authenticated
});
```

## Conditional Hook Registration

### Check Component Availability

```php
private function registerWooCommerceHooks(): void {
    // Only register if component exists
    if (!$this->bootstrap->getComponent('woocommerce')) {
        return;
    }
    
    // Safe to register WooCommerce hooks
    add_action('woocommerce_init', [$this, 'onWooCommerceInit']);
}
```

### Check User Capabilities

```php
private function registerAdminHooks(): void {
    // Only for users who can manage options
    if (!current_user_can('manage_options')) {
        return;
    }
    
    add_action('admin_menu', [$this, 'registerAdminMenu']);
}
```

### Check Settings

```php
private function shouldAutoInsert(): bool {
    $config = $this->bootstrap->getComponent('config');
    return $config && $config->getState('_bfp_enable_player', true);
}
```

## Hook Priority Management

### Priority Guidelines

| Priority | Use Case |
|----------|----------|
| 1-9 | Very early execution |
| 10 | Default (most hooks) |
| 11-19 | Slightly after default |
| 20-49 | Later execution |
| 50+ | Very late execution |
| 999 | Last resort |

### Example Priority Usage

```php
// Early on shop page to beat other plugins
add_action('woocommerce_after_shop_loop_item_title', 
    [$this, 'maybeAddShopPlayer'], 1);

// Standard priority for product page
add_action('woocommerce_single_product_summary', 
    [$this, 'maybeAddPlayer'], 25);

// Late for cleanup
add_action('shutdown', [$this, 'cleanup'], 999);
```

## Error Prevention

### Component Existence Checks

```php
public function maybeAddPlayer(): void {
    $player = $this->bootstrap->getComponent('player');
    if (!$player) {
        return; // Component not loaded
    }
    
    if (!is_product()) {
        return; // Wrong context
    }
    
    echo $player->render(get_the_ID());
}
```

### Safe Hook Removal

```php
// Remove hooks safely
if (has_action('wp_head', 'old_function')) {
    remove_action('wp_head', 'old_function');
}
```

## Performance Optimization

### Lazy Hook Registration

```php
// Only register expensive hooks when needed
add_action('template_redirect', function() {
    if (is_product()) {
        // Register product-specific hooks
        add_filter('the_content', [$this, 'filterProductContent']);
    }
});
```

### Hook Caching

```php
private array $hookCache = [];

private function shouldRunHook(string $context): bool {
    if (!isset($this->hookCache[$context])) {
        $this->hookCache[$context] = $this->evaluateContext($context);
    }
    return $this->hookCache[$context];
}
```

## Custom Hook Creation

### Plugin-Specific Actions

```php
// Before player render
do_action('bandfront_player_before_render', $productId, $context);

// After player render
do_action('bandfront_player_after_render', $productId, $html);

// Settings saved
do_action('bandfront_player_settings_saved', $settings);
```

### Plugin-Specific Filters

```php
// Filter player HTML
$html = apply_filters('bandfront_player_html', $html, $productId);

// Filter audio URL
$url = apply_filters('bandfront_player_audio_url', $url, $fileData);

// Filter settings
$settings = apply_filters('bandfront_player_settings', $settings);
```

## Integration Examples

### Third-Party Plugin Integration

```php
private function registerThirdPartyHooks(): void {
    // WooCommerce Subscriptions
    if (class_exists('WC_Subscriptions')) {
        add_filter('wcs_user_has_subscription', 
            [$this, 'checkSubscriptionAccess'], 10, 3);
    }
    
    // WPML
    if (defined('ICL_SITEPRESS_VERSION')) {
        add_filter('wpml_elements_value_filter', 
            [$this, 'translateElements'], 10, 2);
    }
}
```

### Theme Compatibility

```php
private function registerThemeHooks(): void {
    // Popular theme compatibility
    $theme = wp_get_theme();
    
    switch ($theme->get('TextDomain')) {
        case 'storefront':
            add_action('storefront_before_content', 
                [$this, 'storefrontCompat']);
            break;
            
        case 'flatsome':
            add_filter('flatsome_product_box_actions', 
                [$this, 'flatsomeCompat']);
            break;
    }
}
```

## Debugging Hooks

### List All Plugin Hooks

```php
public function debugListHooks(): array {
    global $wp_filter;
    $pluginHooks = [];
    
    foreach ($wp_filter as $tag => $hooks) {
        foreach ($hooks->callbacks as $priority => $callbacks) {
            foreach ($callbacks as $callback) {
                if ($this->isPluginCallback($callback['function'])) {
                    $pluginHooks[$tag][$priority][] = $callback;
                }
            }
        }
    }
    
    return $pluginHooks;
}
```

### Hook Execution Tracking

```php
// Track hook execution for debugging
add_action('all', function($tag) {
    if (strpos($tag, 'bandfront') !== false) {
        error_log("BFP Hook fired: {$tag}");
    }
});
```

## Best Practices

### 1. Always Check Components Exist

```php
if ($component = $this->bootstrap->getComponent('name')) {
    // Safe to use component
}
```

### 2. Use Appropriate Priorities

- Default: 10
- Early: 1-9
- Late: 20+

### 3. Clean Up on Deactivation

```php
public function deactivate(): void {
    // Remove scheduled events
    wp_clear_scheduled_hook('bandfront_daily_cleanup');
    
    // Clean transients
    $this->cleanupTransients();
}
```

### 4. Namespace Custom Hooks

- Actions: `bandfront_player_*`
- Filters: `bandfront_player_*`
- AJAX: `bfp_*`

### 5. Document Hook Usage

```php
/**
 * Fires after player renders
 * 
 * @param int $productId Product ID
 * @param string $html Generated HTML
 */
do_action('bandfront_player_rendered', $productId, $html);
```

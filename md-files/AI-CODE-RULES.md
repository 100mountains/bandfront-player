# AI Code Rules for Bandfront Player

## Core Standards
- WordPress 2025 Compliant
- PSR-4 Compliant
- PHP 7.4+ features required

AUDIO PLAYBACK 
Uses WordPress REST API - Standard, secure, and extensible
Leverages WordPress authentication - Built-in permission checks
Supports range requests - Proper audio seeking support
Uses transients for caching - WordPress-native caching
Cleaner URLs - /wp-json/bandfront-player/v1/stream/123/0 or with rewrite rules: /bfp-stream/123/0
No complex URL generation - Just use rest_url()
Proper error handling - REST API handles errors gracefully
Easy to extend - Add more endpoints as needed

## Critical DO NOTs
- Do NOT modify `.md` files unless explicitly instructed
- Do NOT use inline CSS in code
- Do NOT lose existing functionality
- Do NOT redesign to fix unknown upstream bugs
- Do NOT access post meta or options directly
- Do NOT instantiate components directly
- Do NOT hardcode settings values
- Do NOT assume WooCommerce is active
- Do NOT output without escaping
- Do NOT trust user input

## State Management Rules
1. All settings MUST use Config class methods
2. Use `getState()` for single values
3. Use `getStates()` for multiple values (preferred)
4. Respect inheritance: Product → Global → Default
5. Check `isOverridable()` before saving product-level settings
6. Use `isGlobalOnly()` to determine setting scope
7. Clear cache after updates with `clearProductAttrsCache()`

## Component Access Rules
1. Access components only through Plugin class getters
2. Follow initialization order from Plugin::initComponents()
3. Check component existence before use
4. Never create new instances of core components

## Security Requirements
1. Sanitize all input with appropriate WordPress functions
2. Verify nonces for all form submissions and AJAX
3. Check user capabilities before privileged actions
4. Escape all output (esc_html, esc_attr, esc_url)
5. Validate file uploads and URLs
6. Use prepared statements for direct database queries

## Performance Requirements
1. Use bulk fetch methods when retrieving multiple values
2. Enqueue assets only when needed (check context first)
3. Cache expensive operations in class properties
4. Lazy load components and features
5. Minimize database queries
6. Use transients for temporary data

## Code Organization
1. One class per file
2. Namespace all classes under `bfp\`
3. Use type declarations for all parameters and returns
4. Use typed properties (PHP 7.4+)
5. Follow PSR-4 autoloading structure
6. Keep views in Views/ directory
7. Keep utilities in Utils/ directory

## Naming Conventions
1. **PHP Classes**: PascalCase - `Config`, `Player`, `CoverRenderer`, `BuildersManager`
2. **PHP Methods**: camelCase - `getState()`, `enqueueAssets()`, `shouldRender()`
3. **PHP Properties**: camelCase - `$mainPlugin`, `$globalAttrs`, `$playerLayouts`
4. **PHP Constants**: UPPER_SNAKE_CASE - `BFP_VERSION`, `BFP_PLUGIN_PATH`
5. **Database Options**: snake_case with prefix - `_bfp_enable_player`, `_bfp_audio_engine`
6. **CSS Classes**: kebab-case with prefix - `bfp-player`, `bfp-playlist-widget`
7. **JavaScript Variables**: camelCase - `playerInstance`, `audioEngine`, `isPlaying`
8. **File Names**: Match class name - `Config.php`, `PlaylistWidget.php`

## Hook Management
1. Register hooks through Hooks class
2. Check context before registering conditional hooks
3. Use proper priority values
4. Remove hooks when components are destroyed
5. Document non-obvious hook usage

## Error Handling
1. Return empty strings instead of false/null for display functions
2. Use try-catch for external API calls
3. Log errors to error_log for debugging
4. Provide user-friendly messages for failures
5. Gracefully degrade when features unavailable

## JavaScript Rules
1. Namespace all global variables under `bfp`
2. Use jQuery safely with proper no-conflict handling
3. Localize all data passed to JavaScript
4. Include nonces for all AJAX requests
5. Support WordPress admin bar and responsive design

## Data Attributes
1. Players must have: `data-product`, `data-file-index`, `data-audio-engine`
2. Use `data-` prefix for all custom attributes
3. Keep attribute names consistent across PHP and JS

## Module System
1. Check module status with `isModuleEnabled()`
2. Modules are optional features that can be toggled
3. Core functionality must work without any modules
4. Modules stored in `_bfp_modules_enabled` setting

## Testing Requirements
1. Verify functionality in WordPress admin and frontend
2. Test with WooCommerce active and inactive
3. Check all user permission levels
4. Validate with multiple products and variations
5. Test bulk operations and edge cases

## Documentation Standards

### PHP Documentation
1. **PHPDoc blocks for all classes and public methods**
```php
/**
 * Configuration and State Management
 * 
 * Provides context-aware state management with automatic inheritance:
 * Product Setting → Global Setting → Default Value
 * 
 * @package BandfrontPlayer
 * @since 0.1
 */
```

2. **Method documentation with types**
```php
/**
 * Get configuration state value with inheritance
 * 
 * @param string $key Setting key to retrieve
 * @param mixed $default Default value if not found
 * @param int|null $productId Product ID for context
 * @return mixed The setting value
 */
```

3. **Inline comments only for complex logic**
   - Explain "why", not "what"
   - Document workarounds and browser quirks
   - Reference issue/ticket numbers: `// Fix for #123: Safari audio context`
   - Keep comments concise and meaningful

### JavaScript Documentation
1. **JSDoc for public functions**
```javascript
/**
 * Initialize player for audio element
 * @param {HTMLAudioElement} element - Target audio element
 * @param {Object} config - Player configuration
 * @returns {MediaElementPlayer} Player instance
 */
```

2. **Document jQuery plugin methods**
3. **Explain browser-specific workarounds**
4. **Reference WordPress hooks being used**

## Quick Reference Hierarchy
1. Check `Config.php` for all settings and defaults
2. Check `Plugin.php` for component initialization
3. Check `Hooks.php` for action/filter registration
4. Check `Admin.php` for backend functionality
5. Check individual feature classes for implementation

## When Adding Features
1. Create dedicated class in appropriate directory
2. Register in Plugin::initComponents()
3. Add hooks via Hooks::registerHooks()
4. Store settings via Config class
5. Add admin UI in Admin class or view template
6. Update documentation and changelog
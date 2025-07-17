# AI Code Rules for Bandfront

bandfront-player/
├── assets/                    # Frontend assets
│   ├── css/                  # Stylesheets + skins
│   └── js/                   # JavaScript files
├── builders/                 # Page builder integrations
│   ├── elementor/
│   └── gutenberg/
├── languages/                # Translations
├── src/                      # Core PHP classes (PSR-4)
│   ├── Admin/               # Admin functionality
│   │   └── Admin.php
│   ├── Audio/               # Audio processing
│   │   ├── Analytics.php
│   │   ├── Audio.php
│   │   ├── Player.php
│   │   ├── Preview.php
│   │   ├── Processor.php
│   │   └── Streamer.php
│   ├── Core/                # Core framework
│   │   ├── Bootstrap.php
│   │   ├── Config.php
│   │   └── Hooks.php
│   ├── REST/                # REST API
│   │   └── StreamController.php
│   ├── Storage/             # File management
│   │   └── FileManager.php
│   ├── UI/                  # User interface
│   │   └── Renderer.php
│   ├── Utils/               # Utilities
│   │   ├── Cache.php
│   │   ├── Cloud.php       # Cloud storage abstraction
│   │   ├── Debug.php
│   │   └── Utils.php
│   ├── Widgets/             # WordPress widgets
│   │   └── PlaylistWidget.php
│   └── WooCommerce/         # WooCommerce integration
│       ├── FormatDownloader.php
│       ├── Integration.php
│       └── ProductProcessor.php
├── templates/               # Template files
│   ├── audio-engine-settings.php
│   ├── global-admin-options.php
│   └── product-options.php
├── widgets/                 # Widget assets
│   └── playlist_widget/
│       ├── css/
│       └── js/
├── BandfrontPlayer.php      # Main plugin file
├── README.md                # Documentation
├── composer.json            # Dependencies
└── composer.lock

## Bandfront Player - Bootstrap Architecture

### Core Architecture Principles

1. **Bootstrap Pattern**: All components are initialized through `Core\Bootstrap`
2. **Dependency Injection**: Components receive dependencies through constructors
3. **No Singletons**: Components don't implement getInstance() (except Bootstrap)
4. **Hook Centralization**: All hooks registered in `Core\Hooks`
5. **State Management**: All settings/config through `Core\Config`

### Namespace Structure

```
Bandfront\
├── Admin\
│   └── Admin                    # Admin functionality
├── Audio\
│   ├── Analytics               # Audio analytics tracking
│   ├── Audio                   # Main audio coordinator
│   ├── Player                  # Player management
│   ├── Preview                 # Legacy URL handler
│   ├── Processor               # Audio processing (FFmpeg, etc.)
│   └── Streamer                # Audio streaming functionality
├── Core\
│   ├── Bootstrap               # Plugin initialization & DI container
│   ├── Config                  # Configuration management
│   └── Hooks                   # WordPress hooks registration
├── REST\
│   └── StreamController        # REST API endpoints
├── Storage\
│   └── FileManager             # File management (merged from Files.php)
├── UI\
│   └── Renderer                # HTML rendering (all UI output)
├── Utils\
│   ├── Cache                   # Cache management
│   ├── Cloud                   # Cloud storage helper
│   ├── Debug                   # Debug utilities
│   └── Utils                   # General utilities
├── Widgets\
│   └── PlaylistWidget          # WordPress widget
└── WooCommerce\
    ├── FormatDownloader        # Format downloads
    ├── Integration             # WooCommerce integration
    └── ProductProcessor        # Product processing
```

### Component Initialization Order (Bootstrap)

```php
1. Core Components:
   - Config (no dependencies)
   - FileManager (Config)
   - Renderer (Config, FileManager)

2. Audio Components:
   - Audio (Config, FileManager)
   - Player (Config, Renderer, Audio, FileManager)
   - Analytics (Config)
   - Preview (Config, Audio, FileManager)

3. Context Components:
   - Admin (Config, FileManager, Renderer) - admin only
   - WooCommerceIntegration (Config, Player, Renderer) - if WC active
   - ProductProcessor (Config, Audio, FileManager) - if WC active
   - FormatDownloader (Config, FileManager) - if WC active

4. REST Components:
   - StreamController (Config, Audio, FileManager)

5. Hook Registration:
   - Hooks (Bootstrap) - must be last
```

### Critical DO NOTs

- Do NOT use `$this->mainPlugin` or `Plugin::getInstance()`
- Do NOT register hooks in constructors
- Do NOT instantiate components directly (use Bootstrap)
- Do NOT access post meta directly (use Config)
- Do NOT create circular dependencies
- Do NOT output HTML outside of Renderer class
- Do NOT use old `bfp\` namespace

### Component Access Patterns

```php
// CORRECT: Inject dependencies through constructor
public function __construct(Config $config, FileManager $fileManager) {
    $this->config = $config;
    $this->fileManager = $fileManager;
}

// CORRECT: Access Bootstrap only when absolutely necessary
$bootstrap = Bootstrap::getInstance();
$component = $bootstrap->getComponent('component_name');

// WRONG: Don't use old patterns
$this->mainPlugin->getConfig();  // NO!
Plugin::getInstance();           // NO!
new SomeComponent();            // NO!
```

### State Management Rules

1. **All settings through Config**:
   ```php
   // Single value
   $value = $this->config->getState('_bfp_setting', 'default', $productId);
   
   // Multiple values (preferred for performance)
   $values = $this->config->getStates(['key1', 'key2'], $productId);
   ```

2. **Inheritance hierarchy**: Product → Global → Default
3. **Check scope**: Use `isOverridable()` and `isGlobalOnly()`
4. **Clear cache**: Call `clearProductAttrsCache()` after updates

### Hook Registration Rules

All hooks MUST be registered in `Core\Hooks`:

```php
// In Hooks::registerAdminHooks()
add_action('admin_menu', [$admin, 'menuLinks']);

// NOT in component constructors!
```

### Rendering Rules

All HTML output MUST go through `UI\Renderer`:

```php
// Player prepares data
$preparedFiles = $this->prepareFilesForRenderer($files, $productId, $settings);

// Renderer generates HTML
return $this->renderer->renderPlayerTable($preparedFiles, $productId, $settings);
```

### Security Requirements

1. Sanitize all input with appropriate WordPress functions
2. Verify nonces for all form submissions and AJAX
3. Check user capabilities before privileged actions
4. Escape all output (esc_html, esc_attr, esc_url)
5. Use prepared statements for direct database queries

### Performance Requirements

1. Use bulk fetch methods: `getStates()` over multiple `getState()` calls
2. Enqueue assets only when needed (check context first)
3. Cache expensive operations in class properties
4. Lazy load components and features
5. Minimize database queries

### Code Organization

1. One class per file
2. Namespace all classes under `Bandfront\`
3. Use type declarations for all parameters and returns
4. Use typed properties (PHP 7.4+)
5. Follow PSR-4 autoloading structure

### Naming Conventions

1. **PHP Classes**: PascalCase - `Config`, `Player`, `FileManager`
2. **PHP Methods**: camelCase - `getState()`, `enqueueAssets()`
3. **PHP Properties**: camelCase - `$config`, `$fileManager`
4. **PHP Constants**: UPPER_SNAKE_CASE - `BFP_VERSION`, `BFP_PLUGIN_PATH`
5. **Database Options**: snake_case with prefix - `_bfp_enable_player`
6. **CSS Classes**: kebab-case with prefix - `bfp-player`, `bfp-playlist-widget`
7. **JavaScript Variables**: camelCase - `playerInstance`, `audioEngine`
8. **File Names**: Match class name - `Config.php`, `FileManager.php`

### REST API Pattern

Audio streaming uses WordPress REST API:
- Endpoint: `/wp-json/bandfront-player/v1/stream/{product_id}/{track_index}`
- Authentication: WordPress native
- Range requests: Supported
- Caching: WordPress transients

### Testing Component Dependencies

When testing if a component works correctly:

1. Check Bootstrap initialization order
2. Verify all dependencies are injected
3. Confirm hooks are in Hooks.php
4. Test with WooCommerce active/inactive
5. Verify no `$this->mainPlugin` references

### Migration from Old Architecture

When migrating old code:
1. Change namespace from `bfp\` to `Bandfront\[Component]\`
2. Replace `Plugin $mainPlugin` with specific dependencies
3. Move hook registration to `Core\Hooks`
4. Update all `$this->mainPlugin->getX()` calls
5. Use Config for all settings access

### When Adding Features

1. Create dedicated class in appropriate namespace
2. Add initialization in `Bootstrap::initialize[Section]()`
3. Register hooks in `Hooks::register[Type]Hooks()`
4. Store settings via Config class
5. Add admin UI through Admin class
6. Route HTML output through Renderer

### Quick Reference

- **Settings**: Check `Config.php` for all settings and defaults
- **Components**: Check `Bootstrap.php` for initialization
- **Hooks**: Check `Hooks.php` for registration
- **Admin**: Check `Admin.php` for backend functionality
- **Rendering**: Check `Renderer.php` for HTML output

## Recent Architecture Changes

1. **Merged Files.php → FileManager.php**: All file operations in one place
2. **Moved Renderer → UI\Renderer**: Better organization
3. **Removed Preview.php**: Functionality moved to REST/Audio
4. **Removed Assets.php**: Each component manages its own assets
5. **Bootstrap pattern**: No more Plugin singleton
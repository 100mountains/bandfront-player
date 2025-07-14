# PSR-4 & WordPress Best Practices Rules (2025)

## Core PSR-4 Rules

### 1. Namespace Structure
- **Root namespace**: `bfp\` (short, memorable, unique)
- **Sub-namespaces**: Match directory structure exactly
  - `bfp\Utils` → `src/Utils/`
  - `bfp\Modules` → `src/Modules/`
  - `bfp\Views` → `src/Views/`

### 2. File Naming
- **One class per file** - No exceptions
- **Filename matches class name** - `Config.php` contains `class Config`
- **PascalCase for files and classes** - No underscores or hyphens

### 3. Class Structure
```php
<?php
namespace bfp;

use bfp\Utils\Cache;

/**
 * Class description (PHPDoc still valuable for WordPress hooks)
 */
class ClassName {
    // Properties first, typed and initialized
    private Plugin $mainPlugin;
    private array $data = [];
    
    // Constructor with dependency injection
    public function __construct(Plugin $mainPlugin) {
        $this->mainPlugin = $mainPlugin;
    }
    
    // Public methods
    public function publicMethod(): void {}
    
    // Protected methods
    protected function protectedMethod(): void {}
    
    // Private methods last
    private function privateMethod(): void {}
}
```

## WordPress Integration Rules

### 4. Plugin Bootstrap
- Keep main plugin file minimal - just bootstrap
- No business logic in main file
- Use constants for plugin metadata

### 5. Hook Registration
```php
// Good: Hooks in dedicated methods
public function registerHooks(): void {
    add_action('init', [$this, 'init']);
    add_filter('the_content', [$this, 'filterContent']);
}

// Bad: Hooks in constructor
public function __construct() {
    add_action('init', [$this, 'init']); // Don't do this
}
```

### 6. Asset Management
- Keep CSS/JS where build tools expect them
- Use WordPress enqueue system properly
- Version assets with plugin version constant

### 7. Template Loading
```php
// Use full paths for templates
include plugin_dir_path(__DIR__) . 'Views/template.php';

// Pass data explicitly
$templateData = ['key' => 'value'];
include $this->getTemplatePath('audio-engine-template.php');
```

## Modern PHP Rules (2025 Standards)

### 8. Type Declarations
- **Always use property types**: `private array $items`
- **Always use parameter types**: `function process(int $id, ?string $name = null)`
- **Always use return types**: `function getName(): string`
- **Use union types when needed**: `function getId(): int|string`

### 9. Null Safety
```php
// Good: Explicit null handling
public function getConfig(): ?Config {
    return $this->config ?? null;
}

// Good: Null coalescing
$value = $array['key'] ?? 'default';

// Good: Nullsafe operator (PHP 8)
$name = $user?->getProfile()?->getName() ?? 'Anonymous';
```

### 10. Array Operations
```php
// Always use short array syntax
$array = ['key' => 'value'];

// Use spread operator
$merged = [...$array1, ...$array2];

// Use array destructuring
[$first, $second] = $array;
```

## Code Quality Rules

### 11. Method Complexity
- **Max 20 lines per method** - Extract to private methods
- **Max 4 parameters** - Use value objects or arrays
- **Single responsibility** - One method, one job

### 12. Dependency Management
```php
// Good: Inject dependencies
public function __construct(
    private Config $config,
    private Player $player
) {}

// Bad: Create dependencies
public function __construct() {
    $this->config = new Config(); // Don't do this
}
```

### 13. Error Handling
```php
// Use exceptions for exceptional cases
if (!$this->isValid($data)) {
    throw new \InvalidArgumentException('Invalid data provided');
}

// Use WordPress error system for user-facing errors
if (is_wp_error($result)) {
    return new \WP_Error('bfp_error', __('Operation failed', 'bandfront-player'));
}
```

## WordPress Specific Best Practices

### 14. Database Operations
- Use `$wpdb` with prepared statements
- Prefix custom tables with `$wpdb->prefix`
- Use WordPress transients for caching

### 15. Security
```php
// Always escape output
echo esc_html($userInput);
echo esc_url($url);
echo esc_attr($attribute);

// Always validate/sanitize input
$id = absint($_GET['id'] ?? 0);
$text = sanitize_text_field($_POST['text'] ?? '');

// Check capabilities
if (!current_user_can('manage_options')) {
    wp_die(__('Unauthorized', 'bandfront-player'));
}
```

### 16. Internationalization
```php
// Always make strings translatable
$message = __('Hello World', 'bandfront-player');
$formatted = sprintf(
    /* translators: %s: user name */
    __('Welcome, %s!', 'bandfront-player'),
    $userName
);
```

### 17. Ajax Handlers
```php
// Proper Ajax setup
public function registerAjax(): void {
    add_action('wp_ajax_bfp_action', [$this, 'handleAjax']);
    add_action('wp_ajax_nopriv_bfp_action', [$this, 'handleAjax']);
}

public function handleAjax(): void {
    check_ajax_referer('bfp_nonce', 'nonce');
    
    $response = $this->processAjaxRequest();
    
    wp_send_json_success($response);
}
```

## Performance Rules

### 18. Lazy Loading
```php
// Good: Load only when needed
public function getWooCommerce(): ?WooCommerce {
    if (!isset($this->woocommerce) && class_exists('WooCommerce')) {
        $this->woocommerce = new WooCommerce($this);
    }
    return $this->woocommerce;
}
```

### 19. Caching
```php
// Use transients for expensive operations
$data = get_transient('bfp_expensive_data');
if (false === $data) {
    $data = $this->calculateExpensiveData();
    set_transient('bfp_expensive_data', $data, HOUR_IN_SECONDS);
}
```

## Testing & Quality

### 20. Testability
- Keep methods pure when possible
- Inject dependencies for mocking
- Separate WordPress hooks from business logic

### 21. Documentation
```php
/**
 * Process audio file for streaming
 * 
 * @param int    $productId Product ID
 * @param string $fileUrl   URL to audio file
 * @param array  $options   Optional settings
 * 
 * @return bool Success status
 * @throws \RuntimeException If file cannot be processed
 * 
 * @since 2.0.0
 */
public function processAudioFile(int $productId, string $fileUrl, array $options = []): bool {
    // Implementation
}
```

## Migration Checklist
- [ ] All classes use PSR-4 namespaces
- [ ] No underscore prefixes in methods/properties
- [ ] All methods use camelCase
- [ ] Type declarations everywhere
- [ ] Modern PHP syntax (8.0+)
- [ ] WordPress coding standards for security
- [ ] Proper internationalization
- [ ] Dependency injection pattern
- [ ] Separation of concerns
- [ ] Build tools still work

These rules ensure your code is modern, secure, performant, and maintainable for WordPress development in 2025.
├── PlayerRenderer.php         # (was player-render.php - BFP_Player_Renderer)
└── Utils/
    ├── Analytics.php          # (was analytics.php - BFP_Analytics)
    ├── Cache.php              # (was cache.php - BFP_Cache)
    ├── Cloud.php              # (was cloud.php - BFP_Cloud_Tools)
    ├── Files.php              # (was files.php - BFP_File_Handler)
    ├── Preview.php            # (was preview.php - BFP_Preview)
    ├── Update.php             # (was update.php - BFP_Updater)
    └── Utils.php              # (was utils.php - BFP_Utils)
```

## Phase 2: Rewrite Main Plugin Class

**3. New main plugin file**
````php
// filepath: bfp.php
<?php
/**
 * Plugin Name: Bandfront Player
 * Plugin URI: https://bandfront.com/
 * Description: Audio player for WooCommerce products
 * Version: 0.1
 * Author: Bandfront
 */

if (!defined('ABSPATH')) {
    exit;
}

// Constants
define('BFP_VERSION', '0.1');
define('BFP_PLUGIN_PATH', __FILE__);
define('BFP_PLUGIN_BASE_NAME', plugin_basename(__FILE__));
define('BFP_PLUGIN_URL', plugin_dir_url(__FILE__));

// Composer autoloader
require_once __DIR__ . '/vendor/autoload.php';

// Initialize plugin
global $BandfrontPlayer;
$BandfrontPlayer = new BandfrontPlayer\Plugin();
````

**4. Create main Plugin class**
````php
// filepath: includes/Plugin.php
<?php
namespace BandfrontPlayer;

/**
 * Main Bandfront Player Class
 */
class Plugin {
    
    private Config $config;
    private Player $player;
    private ?WooCommerce $woocommerce = null;
    private Hooks $hooks;
    private ?Admin $admin = null;
    private Audio $audioCore;
    private Utils\Files $fileHandler;
    private Utils\Preview $preview;
    private Utils\Analytics $analytics;
    private CoverRenderer $coverRenderer;
    private PlayerRenderer $playerRenderer;
    
    // State flags
    private bool $purchasedProductFlag = false;
    private int $forcePurchasedFlag = 0;
    private ?array $currentUserDownloads = null;
    
    public function __construct() {
        $this->initComponents();
        $this->registerStreamingHandler();
    }
    
    private function initComponents(): void {
        $this->config = new Config($this);
        $this->fileHandler = new Utils\Files($this);
        $this->player = new Player($this);
        $this->audioCore = new Audio($this);
        $this->analytics = new Utils\Analytics($this);
        $this->preview = new Utils\Preview($this);
        $this->coverRenderer = new CoverRenderer($this);
        $this->playerRenderer = new PlayerRenderer($this);
        
        if (class_exists('WooCommerce')) {
            $this->woocommerce = new WooCommerce($this);
        }
        
        $this->hooks = new Hooks($this);
        
        if (is_admin()) {
            $this->admin = new Admin($this);
        }
    }
    
    private function registerStreamingHandler(): void {
        add_action('init', [$this, 'handleStreamingRequest']);
    }
    
    public function handleStreamingRequest(): void {
        if (isset($_GET['bfp-stream']) && isset($_GET['bfp-product']) && isset($_GET['bfp-file'])) {
            $productId = intval($_GET['bfp-product']);
            $fileIndex = sanitize_text_field($_GET['bfp-file']);
            
            $this->audioCore->handleStreamRequest($productId, $fileIndex);
        }
    }
    
    // Getters for components
    public function getConfig(): Config {
        return $this->config;
    }
    
    public function getPlayer(): Player {
        return $this->player;
    }
    
    public function getAudioCore(): Audio {
        return $this->audioCore;
    }
    
    public function getWooCommerce(): ?WooCommerce {
        return $this->woocommerce;
    }
    
    public function getAdmin(): ?Admin {
        return $this->admin;
    }
    
    public function getFileHandler(): Utils\Files {
        return $this->fileHandler;
    }
    
    public function getPreview(): Utils\Preview {
        return $this->preview;
    }
    
    public function getAnalytics(): Utils\Analytics {
        return $this->analytics;
    }
    
    public function getCoverRenderer(): CoverRenderer {
        return $this->coverRenderer;
    }
    
    public function getPlayerRenderer(): PlayerRenderer {
        return $this->playerRenderer;
    }
    
    // State management methods
    public function getPurchasedProductFlag(): bool {
        return $this->purchasedProductFlag;
    }
    
    public function setPurchasedProductFlag(bool $flag): void {
        $this->purchasedProductFlag = $flag;
    }
    
    public function getForcePurchasedFlag(): int {
        return $this->forcePurchasedFlag;
    }
    
    public function setForcePurchasedFlag(int $flag): void {
        $this->forcePurchasedFlag = $flag;
    }
    
    // Delegated methods for backward compatibility (if needed)
    public function getState(string $key, mixed $default = null, ?int $productId = null, array $options = []): mixed {
        return $this->config->getState($key, $default, $productId, $options);
    }
    
    public function isModuleEnabled(string $moduleName): bool {
        return $this->config->isModuleEnabled($moduleName);
    }
    
    // WordPress hooks
    public function activation(): void {
        $this->fileHandler->createDirectories();
        wp_schedule_event(time(), 'daily', 'bfp_delete_purchased_files');
    }
    
    public function deactivation(): void {
        $this->fileHandler->deletePurchasedFiles();
        wp_clear_scheduled_hook('bfp_delete_purchased_files');
    }
    
    public function pluginsLoaded(): void {
        load_plugin_textdomain('bandfront-player', false, dirname(BFP_PLUGIN_BASE_NAME) . '/languages');
    }
    
    public function init(): void {
        add_shortcode('bfp-playlist', [$this->woocommerce, 'replacePlaylistShortcode']);
    }
}
````

## Phase 3: Convert Each Class

### Example: Convert BFP_Config to Config

````php
// filepath: includes/Config.php
<?php
namespace BandfrontPlayer;

/**
 * Configuration and State Management
 */
class Config {
    
    private Plugin $mainPlugin;
    private array $productsAttrs = [];
    private array $globalAttrs = [];
    private array $playerLayouts = ['dark', 'light', 'custom'];
    private array $playerControls = ['button', 'all', 'default'];

    private array $overridableSettings = [
        '_bfp_enable_player',
        '_bfp_audio_engine',
        '_bfp_single_player',
        '_bfp_merge_in_grouped',
        '_bfp_play_all',
        '_bfp_loop',
        '_bfp_preload',
        '_bfp_player_volume',
        '_bfp_secure_player',
        '_bfp_file_percent',
        '_bfp_own_demos',
        '_bfp_direct_own_demos',
        '_bfp_demos_list',
    ];

    private array $globalOnlySettings = [
        '_bfp_show_in',
        '_bfp_player_layout',
        '_bfp_player_controls',
        '_bfp_player_title',
        '_bfp_on_cover',
        '_bfp_force_main_player_in_title',
        '_bfp_players_in_cart',
        '_bfp_play_simultaneously',
        '_bfp_registered_only',
        '_bfp_purchased',
        '_bfp_reset_purchased_interval',
        '_bfp_fade_out',
        '_bfp_purchased_times_text',
        '_bfp_message',
        '_bfp_ffmpeg',
        '_bfp_ffmpeg_path',
        '_bfp_ffmpeg_watermark',
        '_bfp_onload',
        '_bfp_playback_counter_column',
        '_bfp_analytics_integration',
        '_bfp_analytics_property',
        '_bfp_analytics_api_secret',
        '_bfp_enable_visualizations',
        '_bfp_modules_enabled',
        '_bfp_cloud_active_tab',
        '_bfp_cloud_dropbox',
        '_bfp_cloud_s3',
        '_bfp_cloud_azure',
    ];

    public function __construct(Plugin $mainPlugin) {
        $this->mainPlugin = $mainPlugin;
    }

    public function getState(string $key, mixed $default = null, ?int $productId = null, array $options = []): mixed {
        if ($default === null) {
            $default = $this->getDefaultValue($key);
        }

        if ($this->isGlobalOnly($key) || !empty($options['force_global'])) {
            return $this->getGlobalAttr($key, $default);
        }

        if ($productId && $this->isOverridable($key)) {
            // Check cache first
            if (isset($this->productsAttrs[$productId][$key])) {
                $value = $this->productsAttrs[$productId][$key];
                if ($this->isValidOverride($value, $key)) {
                    return apply_filters('bfp_state_value', $value, $key, $productId, 'product');
                }
            }

            // Check database
            if (metadata_exists('post', $productId, $key)) {
                $value = get_post_meta($productId, $key, true);
                $this->productsAttrs[$productId] ??= [];
                $this->productsAttrs[$productId][$key] = $value;

                if ($this->isValidOverride($value, $key)) {
                    return apply_filters('bfp_state_value', $value, $key, $productId, 'product');
                }
            }
        }

        return $this->getGlobalAttr($key, $default);
    }
    
    // ... rest of methods converted to PSR-4 style
}
````

### Example: Convert BFP_Player to Player

````php
// filepath: includes/Player.php
<?php
namespace BandfrontPlayer;

/**
 * Player Manager Class
 */
class Player {
    
    private Plugin $mainPlugin;
    private bool $enqueuedResources = false;
    private bool $insertedPlayer = false;
    private bool $insertPlayer = true;
    private bool $insertMainPlayer = true;
    private bool $insertAllPlayers = true;
    private int $preloadTimes = 0;
    
    public function __construct(Plugin $mainPlugin) {
        $this->mainPlugin = $mainPlugin;
    }
    
    public function getPlayer(string $audioUrl, array $args = []): string {
        $defaults = [
            'product_id' => 0,
            'player_controls' => '',
            'player_style' => $this->mainPlugin->getConfig()->getState('_bfp_player_layout'),
            'media_type' => 'mp3',
            'id' => 0,
            'duration' => false,
            'preload' => 'none',
            'volume' => 1
        ];
        
        $args = array_merge($defaults, $args);
        
        // Generate unique player ID
        $playerId = 'bfp-player-' . $args['product_id'] . '-' . $args['id'] . '-' . uniqid();
        
        // Build player HTML
        $html = '<audio id="' . esc_attr($playerId) . '" ';
        $html .= 'class="bfp-player ' . esc_attr($args['player_style']) . '" ';
        $html .= 'data-product-id="' . esc_attr($args['product_id']) . '" ';
        $html .= 'data-file-index="' . esc_attr($args['id']) . '" ';
        $html .= 'preload="' . esc_attr($args['preload']) . '" ';
        $html .= 'data-volume="' . esc_attr($args['volume']) . '" ';
        
        if ($args['player_controls']) {
            $html .= 'data-controls="' . esc_attr($args['player_controls']) . '" ';
        }
        
        if ($args['duration']) {
            $html .= 'data-duration="' . esc_attr($args['duration']) . '" ';
        }
        
        $html .= '>';
        $html .= '<source src="' . esc_url($audioUrl) . '" type="audio/' . esc_attr($args['media_type']) . '" />';
        $html .= '</audio>';
        
        return apply_filters('bfp_player_html', $html, $audioUrl, $args);
    }
    
    public function includeMainPlayer($product = '', bool $echo = true): string {
        // Method implementation with camelCase naming
    }
    
    public function includeAllPlayers($product = ''): string {
        // Method implementation
    }
    
    // Convert all other methods to camelCase
}
````

### Example: Convert Utils Classes

````php
// filepath: includes/Utils/Files.php
<?php
namespace BandfrontPlayer\Utils;

use BandfrontPlayer\Plugin;

/**
 * File handling functionality
 */
class Files {
    
    private Plugin $mainPlugin;
    private string $filesDirectoryPath;
    private string $filesDirectoryUrl;
    
    public function __construct(Plugin $mainPlugin) {
        $this->mainPlugin = $mainPlugin;
        $this->createDirectories();
    }
    
    public function createDirectories(): void {
        // Generate upload dir
        $filesDirectory = wp_upload_dir();
        $this->filesDirectoryPath = rtrim($filesDirectory['basedir'], '/') . '/bfp/';
        $this->filesDirectoryUrl = rtrim($filesDirectory['baseurl'], '/') . '/bfp/';
        $this->filesDirectoryUrl = preg_replace('/^http(s)?:\/\//', '//', $this->filesDirectoryUrl);
        
        if (!file_exists($this->filesDirectoryPath)) {
            @mkdir($this->filesDirectoryPath, 0755);
        }
        
        // Create .htaccess for security
        if (is_dir($this->filesDirectoryPath)) {
            if (!file_exists($this->filesDirectoryPath . '.htaccess')) {
                $htaccess = "Options -Indexes\n";
                $htaccess .= "<Files *.php>\n";
                $htaccess .= "Order Deny,Allow\n";
                $htaccess .= "Deny from All\n";
                $htaccess .= "</Files>\n";
                @file_put_contents($this->filesDirectoryPath . '.htaccess', $htaccess);
            }
        }
        
        if (!file_exists($this->filesDirectoryPath . 'purchased/')) {
            @mkdir($this->filesDirectoryPath . 'purchased/', 0755);
        }
    }
    
    public function clearDir(string $dirPath): void {
        // Implementation
    }
    
    public function getFilesDirectoryPath(): string {
        return $this->filesDirectoryPath;
    }
    
    public function getFilesDirectoryUrl(): string {
        return $this->filesDirectoryUrl;
    }
    
    public function deletePost(int $postId, bool $demosOnly = false, bool $force = false): void {
        // Implementation
    }
    
    public function deletePurchasedFiles(): void {
        if ($this->mainPlugin->getConfig()->getState('_bfp_reset_purchased_interval', 'daily') === 'daily') {
            $this->clearDir($this->filesDirectoryPath . 'purchased/');
            $this->createDirectories();
        }
    }
    
    public function deleteTruncatedFiles(int $productId): void {
        // Implementation
    }
}
````

## Phase 4: Update Method Calls Throughout

### Common Replacements Needed

```php
// OLD → NEW Method Names
get_state() → getState()
get_config() → getConfig()
get_player() → getPlayer()
get_audio_core() → getAudioCore()
get_woocommerce() → getWooCommerce()
get_file_handler() → getFileHandler()
include_main_player() → includeMainPlayer()
include_all_players() → includeAllPlayers()
get_product_files() → getProductFiles()
enqueue_resources() → enqueueResources()
woocommerce_user_product() → woocommerceUserProduct()
output_file() → outputFile()
clear_dir() → clearDir()
_get_post_types() → getPostTypes()

// OLD → NEW Property Access
$this->main_plugin → $this->mainPlugin
$this->_config → $this->config
$this->_player → $this->player
```

## Phase 5: Run Composer and Test

**6. Install dependencies**
```bash
cd /var/www/html/wp-content/plugins/bandfront-player
composer install
```

**7. Update all references**
- Remove all `require_once` statements from bfp.php
- Update all class instantiations to use namespaces
- Update all method calls to use camelCase
- Update all property access patterns

This approach gives you:
- **Clean PSR-4 autoloading** with your existing structure
- **Modern PHP 8+ syntax** with proper type hints
- **No legacy cruft** - complete rewrite as requested
- **Your actual class names** properly converted
- **Proper namespace organization** with Utils subdirectory
````

## Updated REFACTOR_PSR4_RULES.md

````markdown
# PSR-4 Migration Guide for Bandfront Player Files

## Overview
This guide helps convert individual class files from the current BFP_* naming system to modern PSR-4 namespaced classes with dependency injection and type hints.

## Current to New Class Name Mapping

| Current Class | New Class | File Location |
|---------------|-----------|---------------|
| `BFP_Config` | `Config` | `includes/Config.php` |
| `BFP_Player` | `Player` | `includes/Player.php` |
| `BFP_Audio_Engine` | `Audio` | `includes/Audio.php` |
| `BFP_WooCommerce` | `WooCommerce` | `includes/WooCommerce.php` |
| `BFP_Admin` | `Admin` | `includes/Admin.php` |
| `BFP_Hooks` | `Hooks` | `includes/Hooks.php` |
| `BFP_Cover_Renderer` | `CoverRenderer` | `includes/CoverRenderer.php` |
| `BFP_Player_Renderer` | `PlayerRenderer` | `includes/PlayerRenderer.php` |
| `BFP_Analytics` | `Utils\Analytics` | `includes/Utils/Analytics.php` |
| `BFP_Cache` | `Utils\Cache` | `includes/Utils/Cache.php` |
| `BFP_Cloud_Tools` | `Utils\Cloud` | `includes/Utils/Cloud.php` |
| `BFP_File_Handler` | `Utils\Files` | `includes/Utils/Files.php` |
| `BFP_Preview` | `Utils\Preview` | `includes/Utils/Preview.php` |
| `BFP_Updater` | `Utils\Update` | `includes/Utils/Update.php` |
| `BFP_Utils` | `Utils\Utils` | `includes/Utils/Utils.php` |

## Pre-Migration Checklist
- [ ] Composer autoloader is set up with `"BandfrontPlayer\\": "includes/"` mapping
- [ ] Main Plugin class exists at `includes/Plugin.php` with proper getters
- [ ] Target file location matches PSR-4 namespace structure

## Step-by-Step Conversion Process

### 1. Update File Header
```php
// OLD (example from state-manager.php):
<?php
/**
 * Configuration and State Management for Bandfront Player
 * @package BandfrontPlayer
 * @since 0.1
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// NEW (becomes Config.php):
<?php
namespace BandfrontPlayer;

/**
 * Configuration and State Management
 */

if (!defined('ABSPATH')) {
    exit;
}
```

### 2. Convert Class Declaration
```php
// OLD:
class BFP_Config {
    private $main_plugin;
    private $_products_attrs = array();

// NEW:
class Config {
    private Plugin $mainPlugin;
    private array $productsAttrs = [];
```

### 3. Update Constructor
```php
// OLD:
public function __construct($main_plugin) {
    $this->main_plugin = $main_plugin;
}

// NEW:
public function __construct(Plugin $mainPlugin) {
    $this->mainPlugin = $mainPlugin;
}
```

### 4. Convert Method Names and Type Hints

#### Common Method Conversions:
```php
// State Manager (BFP_Config → Config)
get_state() → getState()
get_states() → getStates()
update_state() → updateState()
delete_state() → deleteState()
get_player_state() → getPlayerState()
get_admin_form_settings() → getAdminFormSettings()
is_module_enabled() → isModuleEnabled()
get_global_attr() → getGlobalAttr()
get_product_attr() → getProductAttr()

// Player (BFP_Player → Player)
get_player() → getPlayer()
include_main_player() → includeMainPlayer()
include_all_players() → includeAllPlayers()
get_product_files() → getProductFiles()
enqueue_resources() → enqueueResources()
get_enqueued_resources() → getEnqueuedResources()
set_enqueued_resources() → setEnqueuedResources()

// Audio (BFP_Audio_Engine → Audio)
output_file() → outputFile()
truncate_file() → truncateFile()
demo_file_name() → demoFileName()
valid_demo() → validDemo()
fix_url() → fixUrl()
is_local() → isLocal()
is_audio() → isAudio()
get_duration_by_url() → getDurationByUrl()
generate_audio_url() → generateAudioUrl()
tracking_play_event() → trackingPlayEvent()

// WooCommerce (BFP_WooCommerce → WooCommerce)
woocommerce_product_title() → woocommerceProductTitle()
woocommerce_user_product() → woocommerceUserProduct()
woocommerce_user_download() → woocommerceUserDownload()
replace_playlist_shortcode() → replacePlaylistShortcode()

// File Handler (BFP_File_Handler → Utils\Files)
_createDir() → createDirectories()
_clearDir() → clearDir()
get_files_directory_path() → getFilesDirectoryPath()
get_files_directory_url() → getFilesDirectoryUrl()
delete_post() → deletePost()
delete_purchased_files() → deletePurchasedFiles()
delete_truncated_files() → deleteTruncatedFiles()
clear_expired_transients() → clearExpiredTransients()
```

### 5. Update Property Access Patterns
```php
// OLD:
$this->main_plugin->get_config()->get_state('key');
$this->main_plugin->get_player()->include_main_player();
$this->main_plugin->get_audio_core()->output_file();

// NEW:
$this->mainPlugin->getConfig()->getState('key');
$this->mainPlugin->getPlayer()->includeMainPlayer();
$this->mainPlugin->getAudioCore()->outputFile();
```

### 6. Convert Property Names
```php
// Remove underscores from private properties
private $_products_attrs → private array $productsAttrs
private $_global_attrs → private array $globalAttrs
private $_enqueued_resources → private bool $enqueuedResources
private $_inserted_player → private bool $insertedPlayer
private $_preload_times → private int $preloadTimes
```

### 7. Update Component Access via Main Plugin
```php
// OLD:
$config = $this->main_plugin->get_config();
$player = $this->main_plugin->get_player();
$audio = $this->main_plugin->get_audio_core();
$woo = $this->main_plugin->get_woocommerce();
$files = $this->main_plugin->get_file_handler();

// NEW:
$config = $this->mainPlugin->getConfig();
$player = $this->mainPlugin->getPlayer();
$audio = $this->mainPlugin->getAudioCore();
$woo = $this->mainPlugin->getWooCommerce();
$files = $this->mainPlugin->getFileHandler();
```

### 8. Modernize PHP Syntax
```php
// OLD:
$array = array('key' => 'value');
if (empty($var)) return false;
$result = isset($array['key']) ? $array['key'] : 'default';

// NEW:
$array = ['key' => 'value'];
if (empty($var)) {
    return false;
}
$result = $array['key'] ?? 'default';
```

### 9. Add Proper Type Declarations
```php
// OLD:
private $_cache = array();
private $_flag = false;
private $_count = 0;

// NEW:
private array $cache = [];
private bool $flag = false;
private int $count = 0;
```

### 10. Update Namespace References for Utils
```php
// For utils classes that are now in Utils namespace:
// OLD: 
new BFP_Analytics($this->main_plugin)
new BFP_File_Handler($this->main_plugin)

// NEW: 
new Utils\Analytics($this->mainPlugin)
new Utils\Files($this->mainPlugin)

// Or with use statement at top:
use BandfrontPlayer\Utils\Analytics;
use BandfrontPlayer\Utils\Files;
```

## File-Specific Conversion Notes

### For Main Classes (in /includes/)
- Namespace: `namespace BandfrontPlayer;`
- No subdirectory in namespace

### For Utils Classes (in /includes/utils/)
- Namespace: `namespace BandfrontPlayer\Utils;`
- Must include `Utils\` in namespace
- When referencing Plugin class: `use BandfrontPlayer\Plugin;`

### Static Classes
- `BFP_Utils` and `BFP_Cache` have static methods
- Convert to instance methods in the new system
- Access via `$this->mainPlugin->getComponent()`

## Validation Checklist
After conversion, verify:
- [ ] Class has proper namespace declaration
- [ ] File is renamed (e.g., `state-manager.php` → `Config.php`)
- [ ] Class name matches filename (PSR-4 requirement)
- [ ] Constructor accepts `Plugin $mainPlugin` parameter
- [ ] All method names are camelCase
- [ ] All property names have no underscores
- [ ] All property access uses `$this->mainPlugin->getComponent()`
- [ ] Type hints are added to method parameters and return types
- [ ] Array syntax uses `[]` instead of `array()`
- [ ] No direct instantiation of other BFP classes

## Testing After Conversion
1. Check for PHP syntax errors: `php -l filename.php`
2. Run `composer dump-autoload` to regenerate autoloader
3. Test plugin activation/deactivation
4. Test main functionality through WordPress interface
5. Check error logs for any undefined method/property errors

This systematic approach ensures each file maintains functionality while gaining modern PHP benefits and proper dependency injection.
````

These updated documents now accurately reflect:
1. Your actual class names (BFP_Config, BFP_Player, etc.)
2. Your actual file structure (includes/ and includes/utils/)
3. The proper mapping between old and new names
4. Specific method names from your actual codebase
5. Clear conversion examples using your real classes



// ...existing code...

## Migration-Specific Rules

### 22. Component Initialization Order
```php
// Components must be initialized in dependency order
private function initComponents(): void {
    // 1. Core components (no dependencies)
    $this->config = new Config($this);
    $this->fileHandler = new Utils\Files($this);
    
    // 2. Components that depend on core
    $this->player = new Player($this);
    $this->audioCore = new Audio($this);
    
    // 3. Optional integrations
    if (class_exists('WooCommerce')) {
        $this->woocommerce = new WooCommerce($this);
    }
    
    // 4. Components that depend on others
    $this->hooks = new Hooks($this);
    
    // 5. Admin-only components
    if (is_admin()) {
        $this->admin = new Admin($this);
    }
}
```

### 23. Legacy Data Migration
```php
// Handle old meta keys during transition
public function migrateLegacyData(): void {
    $oldKey = 'bfp_setting';
    $newKey = '_bfp_setting';
    
    if (metadata_exists('post', $postId, $oldKey)) {
        $value = get_post_meta($postId, $oldKey, true);
        update_post_meta($postId, $newKey, $value);
        delete_post_meta($postId, $oldKey);
    }
}
```

### 24. File Organization Standards
```
src/                           # All PHP classes
├── Plugin.php                 # Main plugin class
├── Config.php                 # Settings management
├── Player.php                 # Player functionality
├── Audio.php                  # Audio processing
├── WooCommerce.php           # WooCommerce integration
├── Admin.php                 # Admin interface
├── Hooks.php                 # WordPress hooks
├── CoverRenderer.php         # Cover display
├── PlayerRenderer.php        # Player display
├── Utils/                    # Utility classes
│   ├── Analytics.php
│   ├── Cache.php
│   ├── Cloud.php
│   ├── Files.php
│   ├── Preview.php
│   ├── Update.php
│   └── Utils.php
├── Modules/                  # Optional modules
│   ├── AudioEngine.php
│   └── CloudEngine.php
└── Traits/                   # Shared functionality
    ├── Singleton.php
    └── AjaxHandler.php
```

### 25. Interface Contracts
```php
// Define interfaces for extensibility
namespace bfp\Contracts;

interface Renderable {
    public function render(): string;
}

interface Configurable {
    public function configure(array $options): void;
}

interface Hookable {
    public function registerHooks(): void;
}
```

### 26. Trait Usage
```php
namespace bfp\Traits;

trait AjaxHandler {
    protected function registerAjax(string $action, string $method): void {
        add_action("wp_ajax_{$action}", [$this, $method]);
        add_action("wp_ajax_nopriv_{$action}", [$this, $method]);
    }
    
    protected function verifyAjaxNonce(string $action): bool {
        return wp_verify_nonce($_POST['nonce'] ?? '', $action);
    }
}
```

### 27. Constants Management
```php
// Group all constants in the main plugin file
namespace bfp;

class Constants {
    public const VERSION = '2.0.0';
    public const DB_VERSION = '1.0';
    public const OPTION_PREFIX = '_bfp_';
    public const NONCE_KEY = 'bfp_nonce';
    public const CAPABILITY = 'manage_options';
}
```

### 28. Error Handling Strategy
```php
namespace bfp\Exceptions;

class ConfigException extends \Exception {}
class AudioException extends \Exception {}
class ValidationException extends \Exception {}

// Usage
try {
    $this->processAudio($file);
} catch (AudioException $e) {
    error_log('BFP Audio Error: ' . $e->getMessage());
    return new \WP_Error('bfp_audio_error', $e->getMessage());
}
```

### 29. Plugin Activation/Deactivation
```php
// In main plugin file
register_activation_hook(__FILE__, function() {
    require_once plugin_dir_path(__FILE__) . 'src/Activator.php';
    \bfp\Activator::activate();
});

register_deactivation_hook(__FILE__, function() {
    require_once plugin_dir_path(__FILE__) . 'src/Deactivator.php';
    \bfp\Deactivator::deactivate();
});
```

### 30. Development vs Production
```php
// Environment-aware configuration
namespace bfp;

class Environment {
    public static function isDevelopment(): bool {
        return defined('WP_DEBUG') && WP_DEBUG;
    }
    
    public static function isProduction(): bool {
        return !self::isDevelopment();
    }
    
    public static function getLogLevel(): string {
        return self::isDevelopment() ? 'debug' : 'error';
    }
}
```
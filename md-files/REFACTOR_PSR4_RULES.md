# PSR-4 Complete Rewrite Guide for Bandfront Player

## Current Structure Analysis

Looking at your codebase, you have the following actual structure:

```
/includes/
├── admin.php              # BFP_Admin class
├── audio.php              # BFP_Audio_Engine class
├── cover-renderer.php     # BFP_Cover_Renderer class
├── hooks.php              # BFP_Hooks class
├── player-render.php      # BFP_Player_Renderer class
├── player.php             # BFP_Player class
├── state-manager.php      # BFP_Config class
├── woocommerce.php        # BFP_WooCommerce class
└── utils/
    ├── analytics.php      # BFP_Analytics class
    ├── cache.php          # BFP_Cache class
    ├── cloud.php          # BFP_Cloud_Tools class
    ├── files.php          # BFP_File_Handler class
    ├── preview.php        # BFP_Preview class
    ├── update.php         # BFP_Updater class
    └── utils.php          # BFP_Utils class
```

## Phase 1: Setup Autoloader with YOUR Structure

**1. Create composer.json**
````json
{
    "name": "bandfront/player",
    "description": "Audio player for WooCommerce products",
    "type": "wordpress-plugin",
    "autoload": {
        "psr-4": {
            "BandfrontPlayer\\": "includes/"
        }
    },
    "require": {
        "php": ">=8.0"
    }
}
````

**2. Your structure with PSR-4 namespaces**
```
/includes/
├── Plugin.php                 # Main plugin (was bfp.php content)
├── Config.php                 # (was state-manager.php - BFP_Config)
├── Player.php                 # (was player.php - BFP_Player) 
├── Audio.php                  # (was audio.php - BFP_Audio_Engine)
├── WooCommerce.php            # (was woocommerce.php - BFP_WooCommerce)
├── Admin.php                  # (was admin.php - BFP_Admin)
├── Hooks.php                  # (was hooks.php - BFP_Hooks)
├── CoverRenderer.php          # (was cover-renderer.php - BFP_Cover_Renderer)
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
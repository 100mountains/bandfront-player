Minimal Bootstrap (Recommended)
Keep bfp.php as a tiny bootstrap file:
Then your main logic goes in src/Plugin.php:
Drop the BFP_ prefix (the namespace already provides context).
Use PascalCase for class names.
Use sub-namespaces for logical grouping (e.g., Utils, Integration, Modules).


CHere’s a **step-by-step instruction manual** for renaming your classes, files, and references to follow modern, PSR-4, and WordPress best practices. This includes search-and-replace tips for a surgical, safe migration.

---

# Bandfront Player: Naming Refactor Instruction Manual

## 1. **Old Basic Structure**

```
├── .gitignore
├── .gitmodules
├── README.md
├── bfp.php
├── build
│   ├── build.sh
│   ├── update-translations.sh
│   └── update-wavesurfer.sh
├── builders
│   ├── builders.php
│   ├── elementor
│   │   ├── elementor.pb.php
│   │   └── elementor_category.pb.php
│   └── gutenberg
│       ├── block.json
│       ├── gutenberg.css
│       ├── gutenberg.js
│       ├── render.php
│       ├── wcblocks.css
│       └── wcblocks.js
├── css
│   ├── admin-notices.css
│   ├── skins
│   │   ├── custom.css
│   │   ├── dark.css
│   │   └── light.css
│   ├── style-admin.css
│   └── style.css
├── demo
│   └── demo.mp3
├── includes
│   ├── admin.php
│   ├── audio.php
│   ├── cover-renderer.php
│   ├── hooks.php
│   ├── player-render.php
│   ├── player.php
│   ├── state-manager.php
│   ├── utils
│   │   ├── analytics.php
│   │   ├── cache.php
│   │   ├── cloud.php
│   │   ├── files.php
│   │   ├── preview.php
│   │   ├── update.php
│   │   └── utils.php
│   └── woocommerce.php
├── js
│   ├── admin.js
│   ├── engine-full.js
│   ├── engine.js
│   ├── engine.js.older
│   └── wavesurfer.js
├── languages
│   ├── bandfront-player-en_US.mo
│   ├── bandfront-player-en_US.po
│   ├── bandfront-player-en_US.pot
│   ├── bandfront-player.pot
│   └── messages.mo
├── md-files
│   ├──
├── modules
│   ├── audio-engine.php > move to a view
│   └── cloud-engine.php
├── test
│   ├── backup_plugin.sh
│   ├── backup_plugin_make_downloadable.sh
│   ├── clear_opcache.sh
│   ├── test_mp3_class.php
│   ├── test_outputs
│   └── test_plugin.php
├── vendors
│   ├── php-mp3
│   │   └── class.mp3.php
│   └── wavesurfer
│       ├── plugins
│       │   ├── minimap.min.js
│       │   ├── regions.min.js
│       │   └── timeline.min.js
│       ├── version.txt
│       ├── wavesurfer.esm.js
│       └── wavesurfer.min.js
├── views
│   ├── global-admin-options.php
│   └── product-options.php
└── widgets
    ├── playlist_widget
    │   ├── css
    │   │   └── style.css
    │   └── js
    │       └── widget.js
    └── playlist_widget.php

247 directories, 640 files


**Class names are currently like:**  
- `class BFP_Player`
- `class BFP_Config`
- `class BFP_Admin`
- etc.

---

## 2. **Target Structure & Naming**

BASIC TARGET STRUCTURE:



**File names:**  
- Use `PascalCase` for class files (e.g. `Player.php`, `Config.php`)
- Use subfolders for logical grouping (`Utils/`, `Modules/`, `Views/`)

**Class names:**  
- Drop the `BFP_` prefix
- Use `PascalCase` for class names
- Use namespaces: `BandfrontPlayer\`, `BandfrontPlayer\Utils\`, `BandfrontPlayer\Modules\`, `BandfrontPlayer\Views\`

**Example:**
- player.php → `src/Player.php` with `class Player`
- state-manager.php → `src/Config.php` with `class Config`
- analytics.php → `src/Utils/Analytics.php` with `class Analytics`
- audio-engine.php → `src/Modules/AudioEngine.php` with `class AudioEngine`
- global-admin-options.php → `src/Views/GlobalAdminOptions.php` (if you want to make it a class, otherwise keep as template)

---

## 3. **Surgical Search & Replace Instructions**

### **A. File Renaming**

| Old Filename                | New Filename                |
|-----------------------------|-----------------------------|
| admin.php                   | Admin.php                   |
| audio.php                   | Audio.php                   |
| cover-renderer.php          | CoverRenderer.php           |
| hooks.php                   | Hooks.php                   |
| player-render.php           | PlayerRenderer.php          |
| player.php                  | Player.php                  |
| state-manager.php           | Config.php                  |
| woocommerce.php             | WooCommerce.php             |
| analytics.php         | Utils/Analytics.php         |
| cache.php             | Utils/Cache.php             |
| cloud.php             | Utils/Cloud.php             |
| files.php             | Utils/Files.php             |
| preview.php           | Utils/Preview.php           |
| update.php            | Utils/Update.php            |
| utils.php             | Utils/Utils.php             |
| audio-engine.php    | Modules/AudioEngine.php     |
| cloud-engine.php    | Modules/CloudEngine.php     |

### **B. Class Renaming**

| Old Class Name         | New Class Name & Namespace                |
|------------------------|-------------------------------------------|
| BFP_Player             | BandfrontPlayer\Player                    |
| BFP_Config             | BandfrontPlayer\Config                    |
| BFP_Admin              | BandfrontPlayer\Admin                     |
| BFP_Audio_Engine       | BandfrontPlayer\Audio                     |
| BFP_WooCommerce        | BandfrontPlayer\WooCommerce               |
| BFP_Hooks              | BandfrontPlayer\Hooks                     |
| BFP_Cover_Renderer     | BandfrontPlayer\CoverRenderer             |
| BFP_Player_Renderer    | BandfrontPlayer\PlayerRenderer            |
| BFP_Analytics          | BandfrontPlayer\Utils\Analytics           |
| BFP_Cache              | BandfrontPlayer\Utils\Cache               |
| BFP_Cloud_Tools        | BandfrontPlayer\Utils\Cloud               |
| BFP_File_Handler       | BandfrontPlayer\Utils\Files               |
| BFP_Preview            | BandfrontPlayer\Utils\Preview             |
| BFP_Updater            | BandfrontPlayer\Utils\Update              |
| BFP_Utils              | BandfrontPlayer\Utils\Utils               |
| (Modules)              | BandfrontPlayer\Modules\AudioEngine, etc. |

### **C. Namespace Insertion**

- At the top of each PHP class file, add:
  ```php
  namespace BandfrontPlayer;
  ```
  or for utils:
  ```php
  namespace BandfrontPlayer\Utils;
  ```
  or for modules:
  ```php
  namespace BandfrontPlayer\Modules;
  ```

### **D. Update All References**

#### **1. Class Instantiation**
- Search for: `new BFP_`
- Replace with: `new BandfrontPlayer\` (or `BandfrontPlayer\Utils\`, etc.)

#### **2. Type Hints and Property Types**
- Search for: `BFP_`
- Replace with: `BandfrontPlayer\` (or sub-namespace as above)

#### **3. Static Calls**
- Search for: `BFP_Utils::`
- Replace with: `BandfrontPlayer\Utils\Utils::`

#### **4. File Includes**
- Remove all `require_once` for classes now autoloaded by Composer.

#### **5. Use Statements**
- Add `use BandfrontPlayer\ClassName;` as needed for cross-class references.

---

## 4. **Example Search & Replace Patterns**

- **Find:** `class BFP_([A-Za-z_]+)`
- **Replace:** `class $1` (then add namespace at top)

- **Find:** `new BFP_([A-Za-z_]+)`
- **Replace:** `new BandfrontPlayer\\$1`

- **Find:** `BFP_Utils::`
- **Replace:** `BandfrontPlayer\Utils\Utils::`

- **Find:** `require_once.*(player|config|audio|admin|hooks|woocommerce|cover-renderer|player-render)\.php`
- **Replace:** *(delete line, now autoloaded)*

---

## 5. **Composer Autoload Update**

In composer.json:
```json
"autoload": {
    "psr-4": {
        "BandfrontPlayer\\": "src/"
    }
}
```
Then run:
```bash
composer dump-autoload
```

---

## 6. **Final Checklist**

- [ ] All files renamed to PascalCase.
- [ ] All classes renamed and namespaced.
- [ ] All references updated.
- [ ] All includes/requires for classes removed.
- [ ] Composer autoload tested.
- [ ] Plugin loads and works as expected.

---

**Tip:**  
Use a good IDE with multi-file search/replace and regex support for safe, surgical changes.  
Test after each batch of changes!

---

proposed NEW structure:

/bandfront-player/
├── bfp.php                        # WordPress plugin bootstrap (header, autoloader, init)
├── composer.json                  # Composer autoload config
├── vendor/                        # Composer dependencies
├── src/                           # All PHP code (PSR-4 autoloaded)
│   ├── Plugin.php                 # Main plugin class
│   ├── Config.php                 # State/config management
│   ├── Player.php                 # Player logic
│   ├── Audio.php                  # Audio logic
│   ├── WooCommerce.php            # WooCommerce integration
│   ├── Admin.php                  # Admin logic
│   ├── Hooks.php                  # WordPress hooks
│   ├── CoverRenderer.php          # Cover overlay rendering
│   ├── PlayerRenderer.php         # Player rendering
│   ├── Utils/                     # Utility classes
│   │   ├── Analytics.php
│   │   ├── Cache.php
│   │   ├── Cloud.php
│   │   ├── Files.php
│   │   ├── Preview.php
│   │   ├── Update.php
│   │   └── Utils.php
│   ├── Modules/                   # Feature modules (logic + hooks)
│   │   ├── CloudEngine.php
│   │   └── (other modules...)
│   └── Views/                     # Pure template views (no logic)
│       ├── GlobalAdminOptions.php
│       ├── ProductOptions.php
│       └── AudioEngineTemplate.php   # Descriptive template name
├── assets/                        # Frontend assets
│   ├── js/
│   ├── css/
│   └── images/
├── widgets/
├── builders/
├── languages/
└── README.md


### Phase 2: Migrate Classes with Namespace

**3. Update main plugin class**
````php
<?php
namespace BandfrontPlayer;

/**
 * Main Bandfront Player Class
 */
class Plugin {
    
    private Config\StateManager $config;
    private Player\Manager $player;
    private Audio\Engine $audioCore;
    private Integration\WooCommerce $woocommerce;
    private Hooks\Manager $hooks;
    private Admin\Settings $admin;
    
    public function __construct() {
        $this->loadDependencies();
        $this->initComponents();
        $this->registerStreamingHandler();
    }
    
    private function initComponents(): void {
        $this->config = new Config\StateManager($this);
        $this->player = new Player\Manager($this);
        $this->audioCore = new Audio\Engine($this);
        
        if (class_exists('WooCommerce')) {
            $this->woocommerce = new Integration\WooCommerce($this);
        }
        
        $this->hooks = new Hooks\Manager($this);
        
        if (is_admin()) {
            $this->admin = new Admin\Settings($this);
        }
    }
    
    // Getters for dependency injection
    public function getConfig(): Config\StateManager {
        return $this->config;
    }
    
    public function getPlayer(): Player\Manager {
        return $this->player;
    }
    
    public function getAudioCore(): Audio\Engine {
        return $this->audioCore;
    }
    
    public function getWooCommerce(): ?Integration\WooCommerce {
        return $this->woocommerce;
    }
    
    // ... other methods
}
````

**4. Migrate StateManager**
````php
<?php
namespace BandfrontPlayer\Config;

use BandfrontPlayer\Plugin;

/**
 * BFP Config Class - Comprehensive State Manager
 */
class StateManager {
    
    private Plugin $mainPlugin;
    private array $productsAttrs = [];
    private array $globalAttrs = [];
    
    public function __construct(Plugin $mainPlugin) {
        $this->mainPlugin = $mainPlugin;
    }
    
    public function getState(string $key, $default = null, ?int $productId = null, array $options = []): mixed {
        if ($default === null) {
            $default = $this->getDefaultValue($key);
        }

        if ($this->isGlobalOnly($key) || !empty($options['force_global'])) {
            return $this->getGlobalAttr($key, $default);
        }

        if ($productId && $this->isOverridable($key)) {
            if (isset($this->productsAttrs[$productId][$key])) {
                $value = $this->productsAttrs[$productId][$key];
                if ($this->isValidOverride($value, $key)) {
                    return apply_filters('bfp_state_value', $value, $key, $productId, 'product');
                }
            }

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
    
    // ... rest of methods
}
````

**5. Update main plugin file**
````php
<?php
/**
 * Plugin Name: Bandfront Player
 * Plugin URI: https://bandfront.com/
 * Description: Audio player for WooCommerce products
 * Version: 0.1
 * Author: Bandfront
 * Text Domain: bandfront-player
 * Domain Path: /languages
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

// CONSTANTS
define('BFP_VERSION', '0.1');
define('BFP_PLUGIN_PATH', __FILE__);
define('BFP_PLUGIN_BASE_NAME', plugin_basename(__FILE__));
define('BFP_PLUGIN_URL', plugin_dir_url(__FILE__));

// Composer autoloader
require_once __DIR__ . '/vendor/autoload.php';

// Initialize plugin
global $BandfrontPlayer;
$BandfrontPlayer = new BandfrontPlayer\Plugin();

// Backward compatibility layer
class BandfrontPlayer {
    private static BandfrontPlayer\Plugin $instance;
    
    public static function getInstance(): BandfrontPlayer\Plugin {
        global $BandfrontPlayer;
        return $BandfrontPlayer;
    }
    
    // Proxy methods for backward compatibility
    public function get_config() {
        return self::getInstance()->getConfig();
    }
    
    public function get_player() {
        return self::getInstance()->getPlayer();
    }
    
    // ... other proxy methods
}
````

### Phase 3: Create Global Functions File

**6. Consolidate global functions**
````php
<?php
/**
 * Global functions for backward compatibility
 */

use BandfrontPlayer\Plugin;

if (!function_exists('bfp_get_plugin')) {
    function bfp_get_plugin(): Plugin {
        global $BandfrontPlayer;
        return $BandfrontPlayer;
    }
}

if (!function_exists('bfp_handle_stream_request')) {
    function bfp_handle_stream_request(): void {
        if (!isset($_GET['bfp-stream']) || !isset($_GET['bfp-product']) || !isset($_GET['bfp-file'])) {
            return;
        }
        
        $productId = intval($_GET['bfp-product']);
        $fileId = sanitize_text_field($_GET['bfp-file']);
        
        $plugin = bfp_get_plugin();
        $plugin->getAudioCore()->handleStreamRequest($productId, $fileId);
    }
}

// ... other global functions
````

### Phase 4: Update Method Calls

**7. Update dependency injection pattern**
````php
// Before (current)
$value = $this->main_plugin->get_config()->get_state('_bfp_audio_engine');

// After (PSR-4)
$value = $this->mainPlugin->getConfig()->getState('_bfp_audio_engine');
````

**8. Update class instantiation**
````php
// Before
$this->_config = new BFP_Config($this);

// After
$this->config = new Config\StateManager($this);
````

### Phase 5: Maintain Backward Compatibility

**9. Create compatibility layer**
````php
<?php
namespace BandfrontPlayer\Legacy;

/**
 * Backward compatibility for old class names
 */
class_alias('BandfrontPlayer\Plugin', 'BandfrontPlayer');
class_alias('BandfrontPlayer\Config\StateManager', 'BFP_Config');
class_alias('BandfrontPlayer\Player\Manager', 'BFP_Player');
class_alias('BandfrontPlayer\Audio\Engine', 'BFP_Audio_Engine');
// ... other aliases
````

### Phase 6: Update Autoloading

**10. Remove manual requires**
````php
// Remove from bfp.php:
// require_once plugin_dir_path(__FILE__) . 'includes/state-manager.php';
// require_once plugin_dir_path(__FILE__) . 'includes/player.php';
// etc.

// Replace with:
require_once __DIR__ . '/vendor/autoload.php';
````

## Migration Timeline

### Week 1: Setup
- [ ] Create directory structure
- [ ] Setup composer.json
- [ ] Create backward compatibility layer

### Week 2: Core Classes
- [ ] Migrate Plugin class
- [ ] Migrate StateManager
- [ ] Migrate Player\Manager

### Week 3: Remaining Classes
- [ ] Migrate Audio\Engine
- [ ] Migrate Integration\WooCommerce
- [ ] Migrate Utils classes

### Week 4: Testing & Cleanup
- [ ] Update all method calls
- [ ] Test backward compatibility
- [ ] Remove old files

## Testing Strategy

````php
<?php
namespace BandfrontPlayer\Tests\Unit\Config;

use BandfrontPlayer\Config\StateManager;
use BandfrontPlayer\Plugin;
use PHPUnit\Framework\TestCase;

class StateManagerTest extends TestCase {
    private StateManager $stateManager;
    
    protected function setUp(): void {
        $plugin = $this->createMock(Plugin::class);
        $this->stateManager = new StateManager($plugin);
    }
    
    public function testGetStateReturnsDefaultValue(): void {
        $result = $this->stateManager->getState('_bfp_non_existent_key', 'default_value');
        $this->assertEquals('default_value', $result);
    }
}
````

## Benefits After Migration

1. **Modern autoloading**: No more manual requires
2. **Better IDE support**: Full intellisense and refactoring
3. **Easier testing**: Clear dependency injection
4. **Future-proof**: Ready for modern PHP frameworks
5. **Cleaner code**: Proper separation of concerns

The migration is definitely doable and would significantly improve your codebase quality. Start with Phase 1-2 to get the foundation right, then gradually migrate classes while maintaining backward compatibility.

100mountains: i dont want backward compatibility i just want to change everything. do me the guide again without any concerns over backward compatinbility. we are doing a complete rewrite

GitHub Copilot: 

100mountains: i dont want backward compatibility i just want to change everything. do me the guide again without any concerns over backward compatinbility. we are doing a complete rewrite. oh and one other thing do those folders have to be named exactly like that> i cant keep my current structure? 

GitHub Copilot: Perfect! A complete rewrite gives us much more freedom. And **no**, you don't have to use those exact folder names - you can absolutely keep your current structure or modify it as you prefer.

## PSR-4 Complete Rewrite Guide

### Phase 1: Setup Autoloader with YOUR Structure

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

**2. Your current structure with PSR-4 namespaces**
```
/includes/
├── Plugin.php                 # Main plugin (was bfp.php content)
├── StateManager.php           # (current state-manager.php)
├── Player.php                 # (current player.php) 
├── Audio.php                  # (current audio.php)
├── WooCommerce.php            # (current woocommerce.php)
├── Admin.php                  # (current admin.php)
├── Hooks.php                  # (current hooks.php)
├── CoverRenderer.php          # (current cover-renderer.php)
├── PlayerRender.php           # (current player-render.php)
└── utils/
    ├── Utils.php
    ├── Update.php
    ├── Preview.php
    ├── Files.php
    ├── Cloud.php
    ├── Cache.php
    └── Analytics.php
```

### Phase 2: Rewrite Main Plugin Class

**3. New main plugin file**
````php
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

**4. Rewrite main Plugin class**
````php
<?php
namespace BandfrontPlayer;

/**
 * Main Bandfront Player Class
 */
class Plugin {
    
    private StateManager $config;
    private Player $player;
    private WooCommerce $woocommerce;
    private Hooks $hooks;
    private Admin $admin;
    private Audio $audioCore;
    private Utils\Files $fileHandler;
    private Utils\Preview $preview;
    private Utils\Analytics $analytics;
    
    // State flags
    private bool $purchasedProductFlag = false;
    private int $forcePurchasedFlag = 0;
    private ?array $currentUserDownloads = null;
    
    public function __construct() {
        $this->loadDependencies();
        $this->initComponents();
        $this->registerStreamingHandler();
    }
    
    private function loadDependencies(): void {
        // Dependencies are now autoloaded via PSR-4
    }
    
    private function initComponents(): void {
        $this->config = new StateManager($this);
        $this->player = new Player($this);
        $this->audioCore = new Audio($this);
        $this->fileHandler = new Utils\Files($this);
        $this->preview = new Utils\Preview($this);
        $this->analytics = new Utils\Analytics($this);
        
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
            
            $settings = $this->config->getPlayerState($productId);
            $purchased = $this->woocommerce?->woocommerceUserProduct($productId) ?? false;
            
            $files = $this->player->getProductFiles($productId);
            if (!empty($files) && isset($files[$fileIndex])) {
                $fileUrl = $files[$fileIndex]['url'] ?? '';
                $this->streamAudioFile($fileUrl, $productId, $settings, $purchased);
            }
        }
    }
    
    private function streamAudioFile(string $fileUrl, int $productId, array $settings, bool $purchased): void {
        $this->audioCore->outputFile([
            'file_url' => $fileUrl,
            'product_id' => $productId,
            'settings' => $settings,
            'purchased' => $purchased
        ]);
    }
    
    // Getters for dependency injection
    public function getConfig(): StateManager {
        return $this->config;
    }
    
    public function getPlayer(): Player {
        return $this->player;
    }
    
    public function getAudioCore(): Audio {
        return $this->audioCore;
    }
    
    public function getWooCommerce(): ?WooCommerce {
        return $this->woocommerce ?? null;
    }
    
    public function getAdmin(): ?Admin {
        return $this->admin ?? null;
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
    
    // State management
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
    
    // WordPress hooks
    public function activation(): void {
        // Activation logic
    }
    
    public function deactivation(): void {
        // Deactivation logic
    }
    
    public function pluginsLoaded(): void {
        load_plugin_textdomain('bandfront-player', false, dirname(BFP_PLUGIN_BASE_NAME) . '/languages');
    }
    
    public function init(): void {
        // Init logic
    }
}
````

### Phase 3: Rewrite StateManager

**5. Modern StateManager**
````php
<?php
namespace BandfrontPlayer;

/**
 * Configuration and State Management
 */
class StateManager {
    
    private Plugin $mainPlugin;
    private array $productsAttrs = [];
    private array $globalAttrs = [];
    private array $playerLayouts = ['dark', 'light', 'custom'];
    private array $playerControls = ['button', 'all', 'default'];

    private array $overridableSettings = [
        '_bfp_enable_player',
        '_bfp_audio_engine',
        '_bfp_fade_out',
        '_bfp_secure_player',
        '_bfp_file_percent',
        '_bfp_player_layout',
        '_bfp_player_controls',
        '_bfp_preload'
    ];

    private array $globalOnlySettings = [
        '_bfp_purchased',
        '_bfp_on_cover',
        '_bfp_playlist_watermark',
        '_bfp_playback_counter_column'
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
    
    public function getStates(array $keys, ?int $productId = null): array {
        $results = [];
        foreach ($keys as $key) {
            $results[$key] = $this->getState($key, null, $productId);
        }
        return $results;
    }
    
    public function updateState(string $key, mixed $value, ?int $productId = null): bool {
        if ($productId && $this->isOverridable($key)) {
            $this->productsAttrs[$productId] ??= [];
            $this->productsAttrs[$productId][$key] = $value;
            return update_post_meta($productId, $key, $value);
        } else {
            $this->globalAttrs[$key] = $value;
            return update_option($key, $value);
        }
    }
    
    public function deleteState(string $key, int $productId): bool {
        unset($this->productsAttrs[$productId][$key]);
        return delete_post_meta($productId, $key);
    }
    
    public function getPlayerState(?int $productId = null): array {
        $keys = [
            '_bfp_enable_player',
            '_bfp_audio_engine',
            '_bfp_fade_out',
            '_bfp_secure_player',
            '_bfp_file_percent',
            '_bfp_player_layout',
            '_bfp_player_controls',
            '_bfp_preload'
        ];
        
        return $this->getStates($keys, $productId);
    }
    
    private function getDefaultValue(string $key): mixed {
        $defaults = [
            '_bfp_enable_player' => false,
            '_bfp_audio_engine' => 'mediaelement',
            '_bfp_fade_out' => 3,
            '_bfp_secure_player' => false,
            '_bfp_file_percent' => 30,
            '_bfp_player_layout' => 'dark',
            '_bfp_player_controls' => 'default',
            '_bfp_preload' => 'none',
            '_bfp_purchased' => false,
            '_bfp_on_cover' => false,
            '_bfp_playlist_watermark' => false,
            '_bfp_playback_counter_column' => true
        ];
        
        return $defaults[$key] ?? null;
    }
    
    private function isValidOverride(mixed $value, string $key): bool {
        return $value !== '' && $value !== null && $value !== false;
    }
    
    private function isGlobalOnly(string $key): bool {
        return in_array($key, $this->globalOnlySettings, true);
    }
    
    private function isOverridable(string $key): bool {
        return in_array($key, $this->overridableSettings, true);
    }
    
    private function getGlobalAttr(string $key, mixed $default = null): mixed {
        if (isset($this->globalAttrs[$key])) {
            return apply_filters('bfp_state_value', $this->globalAttrs[$key], $key, null, 'global');
        }
        
        $value = get_option($key, $default);
        $this->globalAttrs[$key] = $value;
        
        return apply_filters('bfp_state_value', $value, $key, null, 'global');
    }
    
    public function clearProductAttrsCache(?int $productId = null): void {
        if ($productId) {
            unset($this->productsAttrs[$productId]);
        } else {
            $this->productsAttrs = [];
        }
    }
    
    public function getPlayerLayouts(): array {
        return $this->playerLayouts;
    }
    
    public function getPlayerControls(): array {
        return $this->playerControls;
    }
}
````

### Phase 4: Rewrite Player Class

**6. Modern Player class**
````php
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
            'title' => '',
            'duration' => '',
            'class' => 'bfp-player',
            'controls' => true,
            'preload' => 'none'
        ];
        
        $args = array_merge($defaults, $args);
        
        $settings = $this->mainPlugin->getConfig()->getPlayerState($args['product_id']);
        
        return $this->renderPlayer($audioUrl, $args, $settings);
    }
    
    private function renderPlayer(string $audioUrl, array $args, array $settings): string {
        $audioEngine = $settings['_bfp_audio_engine'] ?? 'mediaelement';
        $playerLayout = $settings['_bfp_player_layout'] ?? 'dark';
        
        $html = '<div class="bfp-player-wrapper ' . esc_attr($playerLayout) . '">';
        
        if ($audioEngine === 'mediaelement') {
            $html .= $this->renderMediaElementPlayer($audioUrl, $args, $settings);
        } else {
            $html .= $this->renderHtml5Player($audioUrl, $args, $settings);
        }
        
        $html .= '</div>';
        
        $this->enqueueResources();
        
        return $html;
    }
    
    private function renderMediaElementPlayer(string $audioUrl, array $args, array $settings): string {
        $controls = $args['controls'] ? 'controls' : '';
        $preload = esc_attr($args['preload']);
        $class = esc_attr($args['class']);
        
        return sprintf(
            '<audio class="%s" %s preload="%s" data-mejsoptions=\'{"alwaysShowControls": true}\'>' .
            '<source src="%s" type="audio/mpeg">' .
            'Your browser does not support the audio element.' .
            '</audio>',
            $class,
            $controls,
            $preload,
            esc_url($audioUrl)
        );
    }
    
    private function renderHtml5Player(string $audioUrl, array $args, array $settings): string {
        $controls = $args['controls'] ? 'controls' : '';
        $preload = esc_attr($args['preload']);
        $class = esc_attr($args['class']);
        
        return sprintf(
            '<audio class="%s" %s preload="%s">' .
            '<source src="%s" type="audio/mpeg">' .
            'Your browser does not support the audio element.' .
            '</audio>',
            $class,
            $controls,
            $preload,
            esc_url($audioUrl)
        );
    }
    
    public function includeMainPlayer(int $productId = 0, bool $echo = true): string {
        if (!$this->insertMainPlayer) {
            return '';
        }
        
        $files = $this->getProductFiles($productId);
        if (empty($files)) {
            return '';
        }
        
        $settings = $this->mainPlugin->getConfig()->getPlayerState($productId);
        $output = $this->renderPlayerTable($files, $productId, $settings);
        
        if ($echo) {
            echo $output;
            return '';
        }
        
        return $output;
    }
    
    private function renderPlayerTable(array $files, int $productId, array $settings): string {
        $html = '<div class="bfp-playlist" data-product-id="' . esc_attr($productId) . '">';
        
        foreach ($files as $index => $file) {
            $audioUrl = $this->mainPlugin->getAudioCore()->generateAudioUrl($productId, $index, $file);
            $evenOdd = ($index % 2 === 0) ? 'even' : 'odd';
            
            $html .= $this->renderPlayerRow(
                $this->getPlayer($audioUrl, [
                    'product_id' => $productId,
                    'title' => $file['title'] ?? '',
                    'class' => 'bfp-track-player'
                ]),
                $file['title'] ?? "Track " . ($index + 1),
                $file['duration'] ?? '',
                $evenOdd
            );
        }
        
        $html .= '</div>';
        
        return $html;
    }
    
    private function renderPlayerRow(string $audioTag, string $title, string $duration, string $evenOdd): string {
        return sprintf(
            '<div class="bfp-track %s">' .
            '<div class="bfp-track-title">%s</div>' .
            '<div class="bfp-track-player">%s</div>' .
            '<div class="bfp-track-duration">%s</div>' .
            '</div>',
            esc_attr($evenOdd),
            esc_html($title),
            $audioTag,
            esc_html($duration)
        );
    }
    
    public function getProductFiles(int $productId): array {
        $product = wc_get_product($productId);
        if (!$product) {
            return [];
        }
        
        $files = [];
        
        // Get downloadable files
        if ($product->is_downloadable()) {
            $downloads = $product->get_downloads();
            foreach ($downloads as $downloadId => $download) {
                $files[] = [
                    'id' => $downloadId,
                    'title' => $download->get_name(),
                    'url' => $download->get_file(),
                    'duration' => $this->mainPlugin->getAudioCore()->getDurationByUrl($download->get_file())
                ];
            }
        }
        
        // Get custom audio files from meta
        $customFiles = get_post_meta($productId, '_bfp_audio_files', true);
        if (is_array($customFiles)) {
            foreach ($customFiles as $file) {
                $files[] = [
                    'title' => $file['title'] ?? '',
                    'url' => $file['url'] ?? '',
                    'duration' => $this->mainPlugin->getAudioCore()->getDurationByUrl($file['url'] ?? '')
                ];
            }
        }
        
        return $files;
    }
    
    public function enqueueResources(): void {
        if ($this->enqueuedResources) {
            return;
        }
        
        wp_enqueue_script('mediaelement');
        wp_enqueue_style('mediaelement');
        
        wp_enqueue_style(
            'bfp-style',
            BFP_PLUGIN_URL . 'assets/css/player.css',
            ['mediaelement'],
            BFP_VERSION
        );
        
        wp_enqueue_script(
            'bfp-script',
            BFP_PLUGIN_URL . 'assets/js/player.js',
            ['jquery', 'mediaelement'],
            BFP_VERSION,
            true
        );
        
        $this->enqueuedResources = true;
    }
    
    // Getters and setters
    public function getEnqueuedResources(): bool {
        return $this->enqueuedResources;
    }
    
    public function setEnqueuedResources(bool $value): void {
        $this->enqueuedResources = $value;
    }
    
    public function getInsertedPlayer(): bool {
        return $this->insertedPlayer;
    }
    
    public function setInsertedPlayer(bool $value): void {
        $this->insertedPlayer = $value;
    }
}
````

### Phase 5: Run Composer and Test

**7. Install dependencies**
```bash
cd /var/www/html/wp-content/plugins/bandfront-player
composer install
```

**8. Update all other classes similarly**
- Remove underscores from method names (`get_state` → `getState`)
- Add proper type hints
- Use dependency injection pattern
- Update namespace declarations
- Remove old `require_once` statements

### Example for Audio class:

````php
<?php
namespace BandfrontPlayer;

class Audio {
    
    private Plugin $mainPlugin;
    private int $preloadTimes = 0;
    
    public function __construct(Plugin $mainPlugin) {
        $this->mainPlugin = $mainPlugin;
    }
    
    public function outputFile(array $args): void {
        // Modern implementation
        $productId = $args['product_id'] ?? 0;
        $fileUrl = $args['file_url'] ?? '';
        $settings = $args['settings'] ?? [];
        
        // Process and output file
    }
    
    public function generateAudioUrl(int $productId, int $fileIndex, array $fileData = []): string {
        return add_query_arg([
            'bfp-stream' => '1',
            'bfp-product' => $productId,
            'bfp-file' => $fileIndex
        ], home_url());
    }
    
    // ... other methods with modern PHP syntax
}
````

This approach gives you:
- **Clean PSR-4 autoloading**
- **Modern PHP 8+ syntax** (type hints, constructor property promotion)
- **No legacy cruft**
- **Your existing folder structure** (just updated class names and namespaces)
- **Proper dependency injection**
- **Clean, maintainable code**



------

rep[eated?]


Looking at your actual codebase structure, I'll update both REFACTOR_PSR4.md and REFACTOR_PSR4_RULES.md to reflect your real class names and folder structure.

## Updated REFACTOR_PSR4.md

````markdown
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
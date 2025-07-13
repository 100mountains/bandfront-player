Looking at your codebase, implementing PSR-4 would be **moderately challenging** but definitely worthwhile. The main difficulty comes from your extensive use of global functions and class naming conventions that don't align with PSR-4.


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

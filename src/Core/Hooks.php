<?php
declare(strict_types=1);

namespace Bandfront\Core;

use Bandfront\Utils\Debug;

/**
 * Centralized Hook Registration
 * 
 * Registers all WordPress hooks for the plugin components
 * 
 * @package Bandfront\Core
 * @since 2.0.0
 */
class Hooks {
    
    private Bootstrap $bootstrap;
    
    /**
     * Constructor
     */
    public function __construct(Bootstrap $bootstrap) {
        $this->bootstrap = $bootstrap;
        Debug::log('Hooks.php: Constructor called, registering hooks'); // DEBUG-REMOVE
        $this->registerHooks();
    }
    
    /**
     * Register all plugin hooks
     */
    private function registerHooks(): void {
        Debug::log('Hooks.php: registerHooks() called'); // DEBUG-REMOVE
        
        // Core hooks
        $this->registerCoreHooks();
        
        // Admin hooks
        $this->registerAdminHooks();
        
        // Frontend hooks
        $this->registerFrontendHooks();
        
        // WooCommerce hooks
        $this->registerWooCommerceHooks();
        
        // Additional hooks
        add_action('plugins_loaded', [$this, 'onPluginsLoaded']);
        add_action('init', [$this, 'onInit']);
        add_action('init', [$this, 'registerShortcodes']);
        add_action('wp_enqueue_scripts', [$this, 'enqueuePublicAssets']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAdminAssets']);
        add_action('rest_api_init', [$this, 'registerRestRoutes']);
        
        // WooCommerce export/import filters
        add_filter('woocommerce_product_export_meta_value', [$this, 'exportMetaValue'], 10, 4);
        add_filter('woocommerce_product_import_pre_insert_product_object', [$this, 'importMetaValue'], 10, 1);
        
        // Cart hooks
        add_filter('woocommerce_cart_item_name', [$this, 'maybeAddCartPlayer'], 10, 3);
        
        // Product page hooks
        add_action('woocommerce_single_product_summary', [$this, 'maybeAddPlayer'], 25);
        add_action('woocommerce_after_shop_loop_item', [$this, 'maybeAddShopPlayer'], 15);
        
        // Analytics initialization
        if ($analytics = $this->bootstrap->getComponent('analytics')) {
            add_action('init', [$analytics, 'init']);
        }
        
        // REST API is handled separately via rest_api_init
    }
    
    /**
     * Register core plugin hooks
     */
    private function registerCoreHooks(): void {
        // Activation/Deactivation
        register_activation_hook(
            $this->bootstrap->getPluginFile(), 
            [$this->bootstrap, 'activate']
        );
        
        register_deactivation_hook(
            $this->bootstrap->getPluginFile(), 
            [$this->bootstrap, 'deactivate']
        );
    }
    
    /**
     * Register admin hooks
     */
    private function registerAdminHooks(): void {
        Debug::log('Hooks.php: registerAdminHooks() called'); // DEBUG-REMOVE
        
        // Don't check is_admin() here - register hooks unconditionally
        // WordPress will only fire admin hooks in admin context
        
        $admin = $this->bootstrap->getComponent('admin');
        if (!$admin) {
            Debug::log('Hooks.php: No admin component found - this is expected on frontend'); // DEBUG-REMOVE
            return;
        }
        
        Debug::log('Hooks.php: Admin component found, registering hooks'); // DEBUG-REMOVE
        
        // These hooks will only fire in admin context
        add_action('admin_menu', [$admin, 'menuLinks']);
        add_action('admin_init', [$admin, 'adminInit'], 99);
        add_action('save_post', [$admin, 'savePost'], 10, 3);
        add_action('after_delete_post', [$admin, 'afterDeletePost'], 10, 2);
        add_action('admin_notices', [$admin, 'showAdminNotices']);
        
        // AJAX handlers - these need to be registered globally
        add_action('wp_ajax_bfp_save_settings', [$admin, 'ajaxSaveSettings']);
        
        Debug::log('Hooks.php: Admin hooks registered successfully'); // DEBUG-REMOVE
    }
    
    /**
     * Register frontend hooks
     */
    private function registerFrontendHooks(): void {
        // Player hooks
        if ($player = $this->bootstrap->getComponent('player')) {
            add_action('wp_enqueue_scripts', [$player, 'enqueueAssets']);
            add_filter('the_content', [$player, 'filterContent'], 999);
            add_shortcode('bandfront_player', [$player, 'shortcode']);
        }
        
        // Analytics hooks
        if ($analytics = $this->bootstrap->getComponent('analytics')) {
            add_action('wp_footer', [$analytics, 'outputTrackingCode']);
            add_action('wp_ajax_bfp_track_event', [$analytics, 'ajaxTrackEvent']);
            add_action('wp_ajax_nopriv_bfp_track_event', [$analytics, 'ajaxTrackEvent']);
        }
    }
    
    /**
     * Register WooCommerce hooks
     */
    private function registerWooCommerceHooks(): void {
        if (!$this->bootstrap->getComponent('woocommerce')) {
            return;
        }
        
        $woocommerce = $this->bootstrap->getComponent('woocommerce');
        
        // Product hooks
        add_filter('woocommerce_product_tabs', [$woocommerce, 'addProductTabs'], 10);
        add_action('woocommerce_before_single_product_summary', [$woocommerce, 'beforeProductSummary'], 5);
        
        // Cart hooks
        add_filter('woocommerce_cart_item_name', [$woocommerce, 'cartItemName'], 10, 3);
        
        // Download hooks
        if ($downloader = $this->bootstrap->getComponent('format_downloader')) {
            add_filter('woocommerce_account_downloads_columns', [$downloader, 'addDownloadColumns']);
            add_action('woocommerce_account_downloads_column_download-format', [$downloader, 'renderFormatColumn']);
        }
        
        // Download processor hooks
        if ($processor = $this->bootstrap->getComponent('download_processor')) {
            add_action('wp_ajax_bfp_handle_bulk_audio_processing', [$processor, 'handleBulkAudioProcessing']);
        }
        
        // Replace default downloads template - with higher priority to ensure it runs
        add_action('init', function() use ($woocommerce) {
            remove_action('woocommerce_account_downloads_endpoint', 'woocommerce_account_downloads', 10);
            add_action('woocommerce_account_downloads_endpoint', [$woocommerce, 'displayFormatDownloads'], 10);
        }, 20);
    }
    
    /**
     * Plugins loaded callback
     */
    public function onPluginsLoaded(): void {
        // Load text domain
        load_plugin_textdomain(
            'bandfront-player',
            false,
            dirname(plugin_basename($this->bootstrap->getPluginFile())) . '/languages'
        );
    }
    
    /**
     * Init callback
     */
    public function onInit(): void {
        // Initialize preview component if not admin
        if (!is_admin()) {
            if ($preview = $this->bootstrap->getComponent('preview')) {
                $preview->init();
            }
            if ($analytics = $this->bootstrap->getComponent('analytics')) {
                $analytics->init();
            }
        }
        
        // Fire custom action
        do_action('bandfront_player_init');
    }
    
    /**
     * Register shortcodes
     */
    public function registerShortcodes(): void {
        add_shortcode('bfp-player', [$this, 'playerShortcode']);
        
        // Register playlist shortcode if WooCommerce is active
        if ($woocommerce = $this->bootstrap->getComponent('woocommerce')) {
            add_shortcode('bfp-playlist', [$woocommerce, 'renderPlaylist']);
        }
    }
    
    /**
     * Player shortcode handler
     */
    public function playerShortcode(array $atts = []): string {
        $player = $this->bootstrap->getComponent('player');
        return $player ? $player->renderShortcode($atts) : '';
    }
    
    /**
     * Playlist shortcode handler
     */
    public function playlistShortcode(array $atts = []): string {
        $woocommerce = $this->bootstrap->getComponent('woocommerce');
        return $woocommerce ? $woocommerce->renderPlaylist($atts) : '';
    }
    
    /**
     * Maybe add player to product page
     */
    public function maybeAddPlayer(): void {
        if (!is_product()) {
            return;
        }
        
        $player = $this->bootstrap->getComponent('player');
        if ($player && $this->shouldShowPlayer()) {
            echo $player->render(get_the_ID());
        }
    }
    
    /**
     * Maybe add player to shop page
     */
    public function maybeAddShopPlayer(): void {
        // Don't continue if not in a shop context
        if (!is_shop() && !is_product_category() && !is_product_tag() && !is_product_taxonomy()) {
            return;
        }
        
        $player = $this->bootstrap->getComponent('player');
        $config = $this->bootstrap->getComponent('config');
        
        // Simplified: Just check if player is enabled and on_cover is true
        $playerEnabled = (bool)$config->getState('_bfp_enable_player', true, get_the_ID());
        $onCover = (bool)$config->getState('_bfp_on_cover', false);
        
        Debug::log('Hooks.php: maybeAddShopPlayer check', [
            'productId' => get_the_ID(),
            'is_shop' => is_shop(),
            'is_product_category' => is_product_category(),
            'enablePlayer' => $playerEnabled,
            'onCover' => $onCover
        ]); // DEBUG-REMOVE
        
        // Only show on shop pages if both player is enabled and on_cover is true
        if ($player && $playerEnabled && $onCover) {
            echo $player->renderCompact(get_the_ID());
        }
    }
    
    /**
     * Check if player should be shown on single product pages
     */
    private function shouldShowPlayer(): bool {
        $config = $this->bootstrap->getComponent('config');
        $productId = get_the_ID();
        
        // Check product-specific setting first, then global setting
        return (bool)$config->getState('_bfp_enable_player', true, $productId);
    }
    
    /**
     * Enqueue public assets
     */
    public function enqueuePublicAssets(): void {
        $player = $this->bootstrap->getComponent('player');
        if ($player) {
            $player->enqueueAssets();
        }
    }
    
    /**
     * Enqueue admin assets
     */
    public function enqueueAdminAssets(string $hook): void {
        // Admin component doesn't have enqueueAssets method, so skip
        // Assets are likely enqueued elsewhere
    }
    
    /**
     * Register REST routes
     */
    public function registerRestRoutes(): void {
        $endpoint = $this->bootstrap->getComponent('rest_streaming');
        if ($endpoint) {
            $endpoint->registerRoutes();
        }
    }
    
    /**
     * Register admin menu
     */
    public function registerAdminMenu(): void {
        // This is handled in registerAdminHooks via menuLinks method
        // No need to call it again
    }
    
    /**
     * Register metaboxes
     */
    public function registerMetaboxes(): void {
        // This is handled in adminInit method
        // No need to call it again
    }
    
    /**
     * Save product meta
     */
    public function saveProductMeta(int $postId, \WP_Post $post): void {
        // This is handled via savePost hook in registerAdminHooks
        // No need to call it again
    }
    
    /**
     * Export meta value filter
     */
    public function exportMetaValue($value, $meta, $product, $row) {
        if (preg_match('/^_bfp_/i', $meta->key) && !is_scalar($value)) {
            return serialize($value);
        }
        return $value;
    }
    
    /**
     * Import meta value filter
     */
    public function importMetaValue(array $data): array {
        foreach ($data as $key => $value) {
            if (preg_match('/^meta:_bfp_/i', $key) && is_serialized($value)) {
                try {
                    $data[$key] = unserialize($value);
                } catch (\Exception | \Error $e) {
                    // Keep original value on error
                }
            }
        }
        return $data;
    }
    
    /**
     * Maybe add player to cart item
     */
    public function maybeAddCartPlayer(string $productName, array $cartItem, string $cartItemKey): string {
        $config = $this->getConfig();
        
        if (!$config || !$config->getState('_bfp_players_in_cart', false)) {
            return $productName;
        }
        
        $player = $this->bootstrap->getComponent('player');
        if ($player) {
            $playerHtml = $player->renderCompact($cartItem['product_id']);
            return $playerHtml . $productName;
        }
        
        return $productName;
    }
    
    /**
     * Get config shortcut
     */
    private function getConfig(): ?Config {
        return $this->bootstrap->getComponent('config');
    }
}
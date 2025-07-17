<?php
declare(strict_types=1);

namespace Bandfront\Core;

/**
 * Hook Manager
 * 
 * Centralized hook registration following WordPress 2025 patterns
 * 
 * @package Bandfront\Core
 * @since 2.0.0
 */
class Hooks {
    
    private Bootstrap $bootstrap;
    
    public function __construct(Bootstrap $bootstrap) {
        $this->bootstrap = $bootstrap;
        $this->registerHooks();
    }
    
    /**
     * Register all hooks
     */
    private function registerHooks(): void {
        // Core WordPress hooks
        $this->registerCoreHooks();
        
        // WooCommerce hooks
        $this->registerWooCommerceHooks();
        
        // REST API hooks
        $this->registerRestHooks();
        
        // Admin hooks
        if (is_admin()) {
            $this->registerAdminHooks();
        }
        
        // Frontend hooks
        if (!is_admin()) {
            $this->registerFrontendHooks();
        }
    }
    
    /**
     * Register core WordPress hooks
     */
    private function registerCoreHooks(): void {
        $pluginFile = $this->bootstrap->getPluginFile();
        
        // Activation/Deactivation
        register_activation_hook($pluginFile, [$this->bootstrap, 'activate']);
        register_deactivation_hook($pluginFile, [$this->bootstrap, 'deactivate']);
        
        // Plugin loaded
        add_action('plugins_loaded', [$this, 'onPluginsLoaded']);
        add_action('init', [$this, 'onInit']);
        
        // Script/Style loading
        add_action('wp_enqueue_scripts', [$this, 'enqueuePublicAssets']);
        
        // Shortcodes
        add_action('init', [$this, 'registerShortcodes']);
    }
    
    /**
     * Register WooCommerce hooks
     */
    private function registerWooCommerceHooks(): void {
        if (!$this->bootstrap->getComponent('woocommerce')) {
            return;
        }
        
        // Product page hooks
        add_action('woocommerce_before_single_product_summary', [$this, 'maybeAddPlayer'], 25);
        add_action('woocommerce_single_product_summary', [$this, 'maybeAddPlayer'], 25);
        
        // Shop page hooks
        add_action('woocommerce_after_shop_loop_item_title', [$this, 'maybeAddShopPlayer'], 1);
        
        // Cart hooks
        add_filter('woocommerce_cart_item_name', [$this, 'maybeAddCartPlayer'], 10, 3);
        
        // Product data hooks
        add_filter('woocommerce_product_export_meta_value', [$this, 'exportMetaValue'], 10, 4);
        add_filter('woocommerce_product_importer_pre_expand_data', [$this, 'importMetaValue'], 10);
    }
    
    /**
     * Register REST API hooks
     */
    private function registerRestHooks(): void {
        add_action('rest_api_init', [$this, 'registerRestRoutes']);
    }
    
    /**
     * Register admin hooks
     */
    private function registerAdminHooks(): void {
        add_action('admin_menu', [$this, 'registerAdminMenu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAdminAssets']);
        
        // Metabox hooks
        add_action('add_meta_boxes', [$this, 'registerMetaboxes']);
        add_action('save_post', [$this, 'saveProductMeta'], 10, 2);
    }
    
    /**
     * Register frontend hooks
     */
    private function registerFrontendHooks(): void {
        // Preview functionality
        if ($preview = $this->bootstrap->getComponent('preview')) {
            // Add preview hooks
        }
        
        // Analytics
        if ($analytics = $this->bootstrap->getComponent('analytics')) {
            // Add analytics hooks
        }
    }
    
    /**
     * Plugins loaded callback
     */
    public function onPluginsLoaded(): void {
        // Load text domain
        load_plugin_textdomain('bandfront-player', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }
    
    /**
     * Init callback
     */
    public function onInit(): void {
        // Any init-specific logic
        do_action('bandfront_player_init');
    }
    
    /**
     * Register shortcodes
     */
    public function registerShortcodes(): void {
        add_shortcode('bfp-player', [$this, 'playerShortcode']);
        add_shortcode('bfp-playlist', [$this, 'playlistShortcode']);
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
            // Add player to the product page
        }
    }
    
    /**
     * Maybe add player to shop page
     */
    public function maybeAddShopPlayer(): void {
        $player = $this->bootstrap->getComponent('player');
        if ($player && $this->shouldShowPlayer()) {
            // Add player to the shop page
        }
    }
    
    /**
     * Check if player should be shown
     */
    private function shouldShowPlayer(): bool {
        $config = $this->bootstrap->getConfig();
        return $config->getState('_bfp_enable_player', true);
    }
    
    /**
     * Enqueue public assets
     */
    public function enqueuePublicAssets(): void {
        // Enqueue public assets
    }
    
    /**
     * Enqueue admin assets
     */
    public function enqueueAdminAssets(string $hook): void {
        // Enqueue admin assets
    }
    
    /**
     * Register REST routes
     */
    public function registerRestRoutes(): void {
        // Register REST API routes
    }
    
    /**
     * Register admin menu
     */
    public function registerAdminMenu(): void {
        // Register admin menu
    }
    
    /**
     * Register metaboxes
     */
    public function registerMetaboxes(): void {
        // Register metaboxes
    }
    
    /**
     * Save product meta
     */
    public function saveProductMeta(int $postId, \WP_Post $post): void {
        // Save product meta
    }
    
    /**
     * Export meta value
     */
    public function exportMetaValue($value, $meta, $product, $row) {
        // Export meta value
    }
    
    /**
     * Import meta value
     */
    public function importMetaValue(array $data): array {
        // Import meta value
        return $data;
    }
    
    /**
     * Maybe add player to cart item
     */
    public function maybeAddCartPlayer(string $productName, array $cartItem, string $cartItemKey): string {
        // Maybe add player to cart item
        return $productName;
    }
}
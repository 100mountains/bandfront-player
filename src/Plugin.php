<?php
namespace bfp;


use bfp\Utils\Debug;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Legacy Plugin Class - Maintained for backward compatibility
 * 
 * @deprecated 2.0.0 Use \Bandfront\Core\Bootstrap instead
 */
class Plugin {
    
    // Component instances
    private Config $config;
    private Player $player;
    private ?WooCommerce $woocommerce = null;
    private Hooks $hooks;
    private ?Admin $admin = null;
    private Audio $audioCore;
    private Utils\Files $fileHandler;
    private Utils\Preview $preview;
    private Utils\Analytics $analytics;
    private StreamController $streamController;
    private ?ProductProcessor $productProcessor = null;
    private ?FormatDownloader $formatDownloader = null;
    
    /**
     * Constructor - Now acts as a facade to the new Bootstrap system
     */
    public function __construct() {
        Debug::enable();
        
        // Initialize using new Bootstrap
        \Bandfront\Core\Bootstrap::init(BFP_PLUGIN_PATH);
        
        // Map legacy components to new structure for backward compatibility
        $this->mapLegacyComponents();
    }
    
    /**
     * Map legacy component access to new Bootstrap structure
     */
    private function mapLegacyComponents(): void {
        $bootstrap = \Bandfront\Core\Bootstrap::getInstance();
        
        if ($bootstrap) {
            // Map config
            $this->config = $bootstrap->getConfig();
            
            // Map other components as needed
            $this->player = $bootstrap->getComponent('player');
            $this->audioCore = $bootstrap->getComponent('streamer');
            $this->fileHandler = $bootstrap->getComponent('file_manager');
            $this->analytics = $bootstrap->getComponent('analytics');
            $this->woocommerce = $bootstrap->getComponent('woocommerce');
            $this->admin = $bootstrap->getComponent('admin');
        }
    }
    
    /**
     * Check if WooCommerce is active and available
     * 
     * @return bool
     */
    private function isWooCommerceActive(): bool {
        // Check if WooCommerce class exists
        if (!class_exists('WooCommerce')) {
            return false;
        }
        
        // Check if WooCommerce plugin is active
        if (!function_exists('is_plugin_active')) {
            include_once(ABSPATH . 'wp-admin/includes/plugin.php');
        }
        
        return is_plugin_active('woocommerce/woocommerce.php') || 
               function_exists('WC') || 
               class_exists('woocommerce');
    }
    
    /**
     * Plugin activation
     */
    public function activation(): void {
 
        
        // Ensure rewrite rules are registered
        if ($this->formatDownloader) {
            $this->formatDownloader->registerDownloadEndpoint();
        }
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
     
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivation(): void {
        $this->fileHandler->deletePurchasedFiles();
    }
    
    /**
     * Plugins loaded hook
     */
    public function pluginsLoaded(): void {
        load_plugin_textdomain('bandfront-player', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }
    
    /**
     * Init hook
     */
    public function init(): void {
        if (!is_admin()) {
            $this->preview->init();
            $this->analytics->init();
        }
        
        // Register shortcodes
        if ($this->woocommerce) {
            add_shortcode('bfp-playlist', [$this->woocommerce, 'replacePlaylistShortcode']);
        }
    }
    
    /**
     * Register REST API routes
     */
    public function registerRestRoutes() {
        // Register streaming endpoint
        register_rest_route('bandfront-player/v1', '/stream/(?P<product_id>\d+)/(?P<track_index>\d+)', [
            'methods' => 'GET',
            'callback' => [$this, 'handleStreamRequest'],
            'permission_callback' => '__return_true',
            'args' => [
                'product_id' => [
                    'validate_callback' => function($param) {
                        return is_numeric($param);
                    }
                ],
                'track_index' => [
                    'validate_callback' => function($param) {
                        return is_numeric($param);
                    }
                ]
            ]
        ]);
    }
    
    /**
     * Handle stream request from REST API
     */
    public function handleStreamRequest($request) {
        $product_id = (int) $request->get_param('product_id');
        $track_index = (int) $request->get_param('track_index');
        
        // Delegate to StreamController
        return $this->streamController->handleStreamRequest($product_id, $track_index);
    }
    
    // ===== DELEGATED METHODS TO PLAYER CLASS =====
    
    /**
     * Include main player - delegates to player class
     */
    public function includeMainPlayer(string $product = '', bool $echo = true): string {
        return $this->getPlayer()->includeMainPlayer($product, $echo);
    }
    
    /**
     * Include all players - delegates to player class
     */
    public function includeAllPlayers(string $product = ''): string {
        return $this->getPlayer()->includeAllPlayers($product);
    }
    
    /**
     * Get product files - delegates to player class
     */
    public function getProductFiles(int $productId): array {
        return $this->getPlayer()->getProductFiles($productId);
    }
    
    /**
     * Enqueue resources - delegates to player class
     */
    public function enqueueResources(): void {
        $this->getPlayer()->enqueueResources();
    }
    
    /**
     * Get player HTML - delegates to player class
     */
    public function getPlayerHtml(?string $audioUrl = null, array $args = []): mixed {
        // If called with parameters, generate player HTML
        if ($audioUrl !== null) {
            return $this->player->getPlayer($audioUrl, $args);
        }
        // Otherwise return player instance
        return $this->player;
    }
    
    // ===== GETTER METHODS FOR COMPONENTS =====
    
    /**
     * Get config/state manager
     */
    public function getConfig(): Config {
        return $this->config;
    }
    
    /**
     * Get player instance
     */
    public function getPlayer(): Player {
        return $this->player;
    }
    
    /**
     * Get WooCommerce integration
     * Returns null if WooCommerce is not active
     */
    public function getWooCommerce(): ?WooCommerce {
        return $this->woocommerce;
    }
    
    /**
     * Get audio core
     */
    public function getAudioCore(): Audio {
        return $this->audioCore;
    }
    
    /**
     * Get file handler
     */
    public function getFileHandler(): Utils\Files {
        return $this->fileHandler;
    }
    
    /**
     * Get Files utility instance
     * 
     * @return Utils\Files Files utility instance
     */
    public function getFiles(): Utils\Files {
        return $this->fileHandler;
    }
    
    /**
     * Get analytics
     */
    public function getAnalytics(): Utils\Analytics {
        return $this->analytics;
    }
    
    /**
     * Get renderer instance
     * This allows components to share the same renderer instance if needed
     */
    public function getRenderer(): Renderer {
        static $renderer = null;
        if ($renderer === null) {
            $renderer = new Renderer($this);
        }
        return $renderer;
    }
    
    /**
     * Get stream controller
     */
    public function getStreamController(): StreamController {
        return $this->streamController;
    }
    
    /**
     * Get product processor
     */
    public function getProductProcessor(): ?ProductProcessor {
        return $this->productProcessor;
    }
    
    /**
     * Get format downloader
     */
    public function getFormatDownloader(): ?FormatDownloader {
        return $this->formatDownloader;
    }
    
    // ===== STATE MANAGEMENT SHORTCUTS =====
    
    /**
     * Get state value - delegates to config
     * @deprecated 2.0.0 Use getConfig()->getState() instead
     */
    public function getState(string $key, mixed $default = null, ?int $productId = null, array $options = []): mixed {
        return $this->config->getState($key, $default, $productId, $options);
    }
    
    /**
     * Check if module is enabled - delegates to config
     * @deprecated 2.0.0 Use getConfig()->isModuleEnabled() instead
     */
    public function isModuleEnabled(string $moduleName): bool {
        return $this->config->isModuleEnabled($moduleName);
    }
    
    // Remove state flags - they're now in Config
    // Remove: $purchasedProductFlag, $forcePurchasedFlag, $currentUserDownloads
    
    /**
     * Get/Set purchased product flag
     * @deprecated 2.0.0 Use Config state management instead
     */
    public function getPurchasedProductFlag(): bool {
        return $this->config->getState('_bfp_purchased_product_flag', false);
    }
    
    public function setPurchasedProductFlag(bool $flag): void {
        $this->config->updateState('_bfp_purchased_product_flag', $flag);
    }
    
    /**
     * Get/Set force purchased flag
     * @deprecated 2.0.0 Use Config state management instead
     */
    public function getForcePurchasedFlag(): int {
        return $this->config->getState('_bfp_force_purchased_flag', 0);
    }
    
    public function setForcePurchasedFlag(int $flag): void {
        $this->config->updateState('_bfp_force_purchased_flag', $flag);
    }
    
    /**
     * Get/Set current user downloads
     * @deprecated 2.0.0 Use Config state management instead
     */
    public function getCurrentUserDownloads(): ?array {
        return $this->config->getState('_bfp_current_user_downloads', null);
    }
    
    public function setCurrentUserDownloads(?array $downloads): void {
        $this->config->updateState('_bfp_current_user_downloads', $downloads);
    }
    
    // All other methods remain for backward compatibility but are marked deprecated
}
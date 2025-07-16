<?php
namespace bfp;


use bfp\Utils\Debug; // DEBUG-REMOVE
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}


/**
 * Main Bandfront Player Class
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
    
    // State flags
    private bool $purchasedProductFlag = false;
    private int $forcePurchasedFlag = 0;
    private ?array $currentUserDownloads = null;
    
    /**
     * Constructor
     */
    public function __construct() {
        Debug::enable(); // Enable debugging globally
        $this->initComponents();
        
        // Register REST API routes
        add_action('rest_api_init', [$this, 'registerRestRoutes']);
        }

        /**
         * Initialize components
         */
        private function initComponents(): void {

        // Core components always initialized
        $this->config = new Config($this);

        $this->fileHandler = new Utils\Files($this);

        $this->preview = new Utils\Preview($this);

        $this->analytics = new Utils\Analytics($this);

        $this->player = new Player($this);

        $this->audioCore = new Audio($this);

        $this->streamController = new StreamController($this);
        
        // FIXED: Initialize WooCommerce integration with better detection
        if ($this->isWooCommerceActive()) {
            $this->woocommerce = new WooCommerce($this);
          
            // Initialize ProductProcessor for WooCommerce products
            $this->productProcessor = new ProductProcessor($this);
          
            
            // Initialize FormatDownloader for download handling
            $this->formatDownloader = new FormatDownloader($this);
   
        } else {
        }
        
        // Initialize hooks (must be after other components)
        $this->hooks = new Hooks($this);
      
        
        // Initialize admin if in admin area
        error_log('[BFP DEBUG] is_admin() check: ' . (is_admin() ? 'true' : 'false'));
        if (is_admin()) {
            // Delay admin initialization to ensure WordPress is fully loaded
            add_action('init', function() {
                error_log('[BFP DEBUG] Creating Admin instance on init hook');
                $this->admin = new Admin($this);
                error_log('[BFP DEBUG] Admin instance created');
            }, 1); // Early priority to ensure it runs before other init hooks
        }
        
        // Allow other plugins to hook in
        do_action('bfp_components_initialized', $this);
        
        
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
     * This provides a convenient shortcut from the main plugin instance
     */
    public function getState(string $key, mixed $default = null, ?int $productId = null, array $options = []): mixed {
        return $this->config->getState($key, $default, $productId, $options);
    }
    
    /**
     * Check if module is enabled - delegates to config
     */
    public function isModuleEnabled(string $moduleName): bool {
        return $this->config->isModuleEnabled($moduleName);
    }
    
    // ===== DELEGATED METHODS TO OTHER COMPONENTS =====
    
    /**
     * Replace playlist shortcode
     * Delegates to WooCommerce integration if available
     */
    public function replacePlaylistShortcode(array $atts = []): string {
        if ($this->woocommerce) {
            return $this->woocommerce->replacePlaylistShortcode($atts);
        }
        return '';
    }
    
    /**
     * Check if user purchased product
     * Safe wrapper that checks if WooCommerce integration exists
     */
    public function woocommerceUserProduct(int $productId): bool {
        if ($this->woocommerce) {
            return $this->woocommerce->woocommerceUserProduct($productId);
        }
        return false;
    }
    
    /**
     * Generate audio URL
     */
    public function generateAudioUrl(int $productId, int $fileIndex, array $fileData = []): string {
        return $this->audioCore->generateAudioUrl($productId, $fileIndex, $fileData);
    }
    
    /**
     * Get duration by URL
     */
    public function getDurationByUrl(string $url): int {
        return $this->audioCore->getDurationByUrl($url);
    }
    
    /**
     * Delete post
     */
    public function deletePost(int $postId, bool $demosOnly = false, bool $force = false): void {
        $this->fileHandler->deletePost($postId, $demosOnly, $force);
    }
    
    /**
     * Clear directory
     */
    public function clearDir(string $dirPath): void {
        $this->fileHandler->clearDir($dirPath);
    }
    
    /**
     * Clear expired transients
     */
    public function clearExpiredTransients(): void {
        $this->fileHandler->clearExpiredTransients();
    }
    
    /**
     * Get files directory path
     */
    public function getFilesDirectoryPath(): string {
        return $this->fileHandler->getFilesDirectoryPath();
    }
    
    /**
     * Get files directory URL
     */
    public function getFilesDirectoryUrl(): string {
        return $this->fileHandler->getFilesDirectoryUrl();
    }
    
    // ===== UTILITY METHODS =====
    
    /**
     * Get post types
     */
    public function getPostTypes(bool $string = false): mixed {
        return Utils\Utils::getPostTypes($string);
    }
    
    /**
     * Get player layouts
     */
    public function getPlayerLayouts(): array {
        return $this->config->getPlayerLayouts();
    }
    
    /**
     * Get player controls
     */
    public function getPlayerControls(): array {
        return $this->config->getPlayerControls();
    }
    
    // ===== STATE FLAGS GETTERS/SETTERS =====
    
    /**
     * Get/Set purchased product flag
     */
    public function getPurchasedProductFlag(): bool {
        return $this->purchasedProductFlag;
    }
    
    public function setPurchasedProductFlag(bool $flag): void {
        $this->purchasedProductFlag = $flag;
    }
    
    /**
     * Get/Set force purchased flag
     */
    public function getForcePurchasedFlag(): int {
        return $this->forcePurchasedFlag;
    }
    
    public function setForcePurchasedFlag(int $flag): void {
        $this->forcePurchasedFlag = $flag;
    }
    
    /**
     * Get/Set current user downloads
     */
    public function getCurrentUserDownloads(): ?array {
        return $this->currentUserDownloads;
    }
    
    public function setCurrentUserDownloads(?array $downloads): void {
        $this->currentUserDownloads = $downloads;
    }
    
    /**
     * Get/Set insert player flags - delegates to player class
     */
    public function getInsertPlayer(): bool {
        return $this->player->getInsertPlayer();
    }
    
    public function setInsertPlayer(bool $value): void {
        $this->player->setInsertPlayer($value);
    }
    
    public function getInsertMainPlayer(): bool {
        return $this->player->getInsertMainPlayer();
    }
    
    public function setInsertMainPlayer(bool $value): void {
        $this->player->setInsertMainPlayer($value);
    }
    
    public function getInsertAllPlayers(): bool {
        return $this->player->getInsertAllPlayers();
    }
    
    public function setInsertAllPlayers(bool $value): void {
        $this->player->setInsertAllPlayers($value);
    }
    
    /**
     * Smart context detection for player display
     * Replaces manual _bfp_show_in configuration with intelligent logic
     * 
     * @param int $productId Product ID to check
     * @return bool Whether to show player for this product
     */
    public function smartPlayContext(int $productId): bool {
        $product = wc_get_product($productId);
        
        if (!$product) {
            return false;
        }

        // Always show players for downloadable products with audio files
        if ($product->is_downloadable()) {
            $downloads = $product->get_downloads();
            foreach ($downloads as $download) {
                $fileUrl = $download->get_file();
                if ($this->getFiles()->isAudio($fileUrl)) {
                    return true;
                }
            }
        }

        // Show players for grouped products (they may contain downloadable children)
        if ($product->is_type('grouped')) {
            $children = $product->get_children();
            foreach ($children as $childId) {
                $childProduct = wc_get_product($childId);
                if ($childProduct && $childProduct->is_downloadable()) {
                    $downloads = $childProduct->get_downloads();
                    foreach ($downloads as $download) {
                        if ($this->getFiles()->isAudio($download->get_file())) {
                            return true;
                        }
                    }
                }
            }
        }

        // Show players for variable products (they may have downloadable variations)
        if ($product->is_type('variable')) {
            $variations = $product->get_available_variations();
            foreach ($variations as $variation) {
                $varProduct = wc_get_product($variation['variation_id']);
                if ($varProduct && $varProduct->is_downloadable()) {
                    $downloads = $varProduct->get_downloads();
                    foreach ($downloads as $download) {
                        if ($this->getFiles()->isAudio($download->get_file())) {
                            return true;
                        }
                    }
                }
            }
        }

        // Check if custom audio files are added via our plugin
        $customAudioFiles = get_post_meta($productId, '_bfp_demos_list', true);
        if (!empty($customAudioFiles) && is_array($customAudioFiles)) {
            foreach ($customAudioFiles as $file) {
                if (!empty($file['file']) && $this->getFiles()->isAudio($file['file'])) {
                    return true;
                }
            }
        }

        // Check if player is explicitly enabled for this product
        $enablePlayer = get_post_meta($productId, '_bfp_enable_player', true);
        if ($enablePlayer) {
            // Double-check that there are actually audio files
            $allFiles = $this->getFiles()->getProductFiles($productId);
            if (!empty($allFiles)) {
                return true;
            }
        }

        // Default: Do not show players
        return false;
    }
    
    /**
     * Smart preload behavior detection
     * Determines optimal preload setting based on context and performance considerations
     * 
     * @param int $productId Product ID to check
     * @param string $context Page context ('single', 'shop', 'cart', etc.)
     * @return string Preload value: 'none', 'metadata', or 'auto'
     */
    public function smartPreloadBehavior(int $productId, string $context = ''): string {
        // Get current context if not provided
        if (empty($context)) {
            if (is_singular($this->getPostTypes())) {
                $context = 'single';
            } elseif (is_shop() || is_product_category() || is_product_tag()) {
                $context = 'shop';
            } elseif (is_cart()) {
                $context = 'cart';
            } else {
                $context = 'other';
            }
        }
        
        // Get product
        $product = wc_get_product($productId);
        if (!$product) {
            return 'none';
        }
        
        // Check if secure player is enabled (demo files)
        $securePlayer = $this->getConfig()->getState('_bfp_secure_player', false, $productId);
        
        // Count audio files
        $audioFiles = $this->getFiles()->getProductFiles($productId);
        $fileCount = count($audioFiles);
        
        // Smart logic based on context
        switch ($context) {
            case 'single':
                // Product pages - more aggressive preloading
                if ($fileCount <= 3) {
                    // Few files - preload metadata for quick start
                    return 'metadata';
                } elseif ($fileCount <= 10 && !$securePlayer) {
                    // Moderate files, no demos - still preload metadata
                    return 'metadata';
                } else {
                    // Many files or using demos - conservative
                    return 'none';
                }
                
            case 'shop':
            case 'category':
                // Shop/archive pages - be conservative
                // Too many players preloading = slow page
                return 'none';
                
            case 'cart':
                // Cart page - minimal preloading
                return 'none';
                
            default:
                // Other contexts (widgets, shortcodes) - conservative
                return 'none';
        }
    }

    /**
     * Get optimal preload setting for a product
     * Respects manual overrides while providing smart defaults
     * 
     * @param int $productId Product ID
     * @param string $context Page context
     * @return string Preload setting
     */
    public function getPreloadSetting(int $productId, string $context = ''): string {
        // Get the configured setting
        $preload = $this->getConfig()->getState('_bfp_preload', 'smart', $productId);
        
        // If set to 'smart' or invalid, use smart detection
        if ($preload === 'smart' || !in_array($preload, ['none', 'metadata', 'auto'])) {
            return $this->smartPreloadBehavior($productId, $context);
        }
        
        return $preload;
    }
}
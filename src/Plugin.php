<?php
namespace bfp;

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
        $this->initComponents();
        
        // Register REST API routes
        add_action('rest_api_init', [$this, 'registerRestRoutes']);
    }

    /**
     * Initialize components
     */
    private function initComponents(): void {
        $this->addConsoleLog('initComponents started');
        
        // Core components always initialized
        $this->config = new Config($this);
        $this->addConsoleLog('Config initialized');
        
        $this->fileHandler = new Utils\Files($this);
        $this->addConsoleLog('Files utility initialized');
        
        $this->preview = new Utils\Preview($this);
        $this->addConsoleLog('Preview utility initialized');
        
        $this->analytics = new Utils\Analytics($this);
        $this->addConsoleLog('Analytics utility initialized');
        
        $this->player = new Player($this);
        $this->addConsoleLog('Player initialized');
        
        $this->audioCore = new Audio($this);
        $this->addConsoleLog('Audio core initialized');
        
        $this->streamController = new StreamController($this);
        $this->addConsoleLog('StreamController initialized');
        
        // FIXED: Initialize WooCommerce integration with better detection
        if ($this->isWooCommerceActive()) {
            $this->woocommerce = new WooCommerce($this);
            $this->addConsoleLog('WooCommerce integration initialized');
            
            // Initialize ProductProcessor for WooCommerce products
            $this->productProcessor = new ProductProcessor($this);
            $this->addConsoleLog('ProductProcessor initialized');
            
            // Initialize FormatDownloader for download handling
            $this->formatDownloader = new FormatDownloader($this);
            $this->addConsoleLog('FormatDownloader initialized');
        } else {
            $this->addConsoleLog('WooCommerce not active, skipping WC components');
        }
        
        // Initialize hooks (must be after other components)
        $this->hooks = new Hooks($this);
        $this->addConsoleLog('Hooks initialized');
        
        // Initialize admin if in admin area
        if (is_admin()) {
            $this->admin = new Admin($this);
            $this->addConsoleLog('Admin initialized');
        }
        
        // Allow other plugins to hook in
        do_action('bfp_components_initialized', $this);
        
        $this->addConsoleLog('initComponents completed');
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
        $this->addConsoleLog('Plugin activation started');
        
        // Ensure rewrite rules are registered
        if ($this->formatDownloader) {
            $this->formatDownloader->registerDownloadEndpoint();
        }
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        $this->addConsoleLog('Plugin activation completed');
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
     * Add console log for debugging
     */
    private function addConsoleLog(string $message, $data = null): void {
        echo '<script>console.log("[BFP Plugin] ' . esc_js($message) . '", ' . 
             wp_json_encode($data) . ');</script>';
    }
}
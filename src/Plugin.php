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
    
    // State flags
    private bool $purchasedProductFlag = false;
    private int $forcePurchasedFlag = 0;
    private ?array $currentUserDownloads = null;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->initComponents();
        $this->registerStreamingHandler();
    }

    /**
     * Register streaming handler for audio file requests
     */
    private function registerStreamingHandler(): void {
        // Handle streaming requests
        add_action('init', [$this, 'handleStreamingRequest'], 1);
    }
    
    /**
     * Handle streaming request
     */
    public function handleStreamingRequest(): void {
        if (isset($_GET['bfp-stream']) && isset($_GET['bfp-product']) && isset($_GET['bfp-file'])) {
            $productId = intval($_GET['bfp-product']);
            $fileId = sanitize_text_field($_GET['bfp-file']);
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('BFP Stream Request: Product=' . $productId . ', File=' . $fileId);
            }
            
            // Get the product
            if (!function_exists('wc_get_product')) {
                status_header(500);
                error_log('BFP Stream Error: WooCommerce not available');
                die('WooCommerce not available');
            }
            
            $product = wc_get_product($productId);
            if (!$product) {
                status_header(404);
                error_log('BFP Stream Error: Product not found - ' . $productId);
                die('Product not found');
            }
            
            // Get files for this product
            $files = $this->getPlayer()->getProductFiles([
                'product' => $product,
                'file_id' => $fileId
            ]);
            
            if (empty($files)) {
                status_header(404);
                error_log('BFP Stream Error: File not found - ' . $fileId . ' for product ' . $productId);
                die('File not found');
            }
            
            $file = reset($files);
            $fileUrl = $file['file'];
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('BFP Stream: Found file URL - ' . $fileUrl);
            }
            
            // Track play event
            do_action('bfp_play_file', $productId, $fileUrl);
            
            // Check if user has purchased product
            $purchased = false;
            if ($this->getWooCommerce()) {
                $purchased = $this->getWooCommerce()->woocommerceUserProduct($productId);
            }
            
            // Get settings for this product
            $settings = $this->getConfig()->getStates([
                '_bfp_secure_player',
                '_bfp_file_percent'
            ], $productId);
            
            // Stream the file directly instead of calling output_file
            $this->streamAudioFile($fileUrl, $productId, $settings, $purchased);
            
            exit; // Stop execution after serving file
        }
    }
    
    /**
     * Stream audio file with proper headers
     */
    private function streamAudioFile(string $fileUrl, int $productId, array $settings, bool $purchased): void {
        // Process cloud URLs if needed
        if (strpos($fileUrl, 'drive.google.com') !== false) {
            $fileUrl = Utils\Cloud::getGoogleDriveDownloadUrl($fileUrl);
        }
        
        // Check if file is local
        $localPath = $this->getAudioCore()->isLocal($fileUrl);
        
        if ($localPath && file_exists($localPath)) {
            // Local file streaming
            $mimeType = 'audio/mpeg';
            $extension = strtolower(pathinfo($localPath, PATHINFO_EXTENSION));
            
            switch ($extension) {
                case 'wav':
                    $mimeType = 'audio/wav';
                    break;
                case 'ogg':
                case 'oga':
                    $mimeType = 'audio/ogg';
                    break;
                case 'm4a':
                    $mimeType = 'audio/mp4';
                    break;
                case 'mp3':
                default:
                    $mimeType = 'audio/mpeg';
                    break;
            }
            
            // Set headers
            header("Content-Type: $mimeType");
            header("Accept-Ranges: bytes");
            header("Cache-Control: no-cache, must-revalidate");
            header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
            
            $filesize = filesize($localPath);
            
            // Handle range requests for seeking
            if (isset($_SERVER['HTTP_RANGE'])) {
                $range = $_SERVER['HTTP_RANGE'];
                list($rangeType, $rangeValue) = explode('=', $range, 2);
                
                if ($rangeType == 'bytes') {
                    list($start, $end) = explode('-', $rangeValue, 2);
                    $start = intval($start);
                    $end = empty($end) ? ($filesize - 1) : intval($end);
                    $length = $end - $start + 1;
                    
                    header("HTTP/1.1 206 Partial Content");
                    header("Content-Range: bytes $start-$end/$filesize");
                    header("Content-Length: $length");
                    
                    $fp = fopen($localPath, 'rb');
                    fseek($fp, $start);
                    
                    $bufferSize = 8192;
                    $bytesToRead = $length;
                    
                    while (!feof($fp) && $bytesToRead > 0) {
                        $buffer = fread($fp, min($bufferSize, $bytesToRead));
                        echo $buffer;
                        flush();
                        $bytesToRead -= strlen($buffer);
                    }
                    
                    fclose($fp);
                } else {
                    // Invalid range type
                    header("HTTP/1.1 416 Requested Range Not Satisfiable");
                    header("Content-Range: bytes */$filesize");
                }
            } else {
                // No range request - send whole file
                header("Content-Length: $filesize");
                
                // Check if we need to limit playback for demo
                if (!$purchased && $settings['_bfp_secure_player'] && $settings['_bfp_file_percent'] < 100) {
                    $bytesToSend = floor($filesize * ($settings['_bfp_file_percent'] / 100));
                    header("Content-Length: $bytesToSend");
                    
                    $fp = fopen($localPath, 'rb');
                    $bytesSent = 0;
                    
                    while (!feof($fp) && $bytesSent < $bytesToSend) {
                        $buffer = fread($fp, min(8192, $bytesToSend - $bytesSent));
                        echo $buffer;
                        $bytesSent += strlen($buffer);
                        flush();
                    }
                    
                    fclose($fp);
                } else {
                    readfile($localPath);
                }
            }
        } else {
            // Remote file - redirect
            header("Location: $fileUrl");
        }
    }
    
    /**
     * Initialize components
     */
    private function initComponents(): void {
        // Initialize state manager first
        $this->config = new Config($this);
        
        // Initialize other components
        $this->player = new Player($this);
        $this->audioCore = new Audio($this);
        $this->fileHandler = new Utils\Files($this);
        $this->preview = new Utils\Preview($this);
        $this->analytics = new Utils\Analytics($this);
        
        // Initialize WooCommerce integration only if WooCommerce is active
        if (class_exists('WooCommerce')) {
            $this->woocommerce = new WooCommerce($this);
        }
        
        // Initialize hooks
        $this->hooks = new Hooks($this);
        
        // Initialize admin
        if (is_admin()) {
            $this->admin = new Admin($this);
        }
    }
    
    /**
     * Plugin activation
     */
    public function activation(): void {
        $this->fileHandler->createDirectories();
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
     * Register REST API endpoints
     */
    private function registerRestEndpoints(): void {
        add_action('rest_api_init', [$this, 'registerStreamingEndpoint']);
    }
    
    /**
     * Register streaming endpoint
     */
    public function registerStreamingEndpoint(): void {
        register_rest_route('bandfront-player/v1', '/stream/(?P<product_id>\d+)/(?P<file_index>[a-zA-Z0-9_-]+)', [
            'methods' => 'GET',
            'callback' => [$this, 'handleStreamRequest'],
            'permission_callback' => [$this, 'streamPermissionCheck'],
            'args' => [
                'product_id' => [
                    'validate_callback' => function($param) {
                        return is_numeric($param);
                    }
                ],
                'file_index' => [
                    'validate_callback' => function($param) {
                        return is_string($param);
                    }
                ]
            ]
        ]);
    }
    
    /**
     * Permission check for streaming
     */
    public function streamPermissionCheck(\WP_REST_Request $request): bool {
        $productId = $request->get_param('product_id');
        
        // Check if registered users only
        if ($this->getConfig()->getState('_bfp_registered_only') && !is_user_logged_in()) {
            return false;
        }
        
        // Additional permission checks can be added here
        return true;
    }
    
    /**
     * Handle stream request via REST API
     */
    public function handleStreamRequest(\WP_REST_Request $request): void {
        $productId = intval($request->get_param('product_id'));
        $fileIndex = sanitize_text_field($request->get_param('file_index'));
        
        // Get file data
        $files = $this->getPlayer()->getProductFiles($productId);
        if (!isset($files[$fileIndex])) {
            wp_die('File not found', 404);
        }
        
        $file = $files[$fileIndex];
        $fileUrl = $file['file'] ?? '';
        
        // Check if user has purchased the product
        $purchased = $this->getWooCommerce()?->woocommerceUserProduct($productId) ?? false;
        
        // Get demo settings
        $settings = $this->getConfig()->getStates([
            '_bfp_secure_player',
            '_bfp_file_percent'
        ], $productId);
        
        // Stream the file
        $this->streamFile($fileUrl, $productId, $purchased, $settings);
    }
    
    /**
     * Stream file with proper headers
     */
    private function streamFile(string $fileUrl, int $productId, bool $purchased, array $settings): void {
        // Get local file path
        $filePath = $this->getAudioCore()->isLocal($fileUrl);
        
        if (!$filePath || !file_exists($filePath)) {
            // Handle remote files or generate demo
            $this->getAudioCore()->outputFile([
                'url' => $fileUrl,
                'product_id' => $productId,
                'secure_player' => $settings['_bfp_secure_player'],
                'file_percent' => $settings['_bfp_file_percent']
            ]);
            return;
        }
        
        // Use WordPress function for secure file delivery
        $this->serveFile($filePath, $purchased, $settings);
    }
    
    /**
     * Serve file using WordPress standards
     */
    private function serveFile(string $filePath, bool $purchased, array $settings): void {
        // Get MIME type
        $mimeType = wp_check_filetype($filePath)['type'] ?: 'audio/mpeg';
        
        // If demo mode and not purchased, serve truncated version
        if (!$purchased && $settings['_bfp_secure_player']) {
            $demoPath = $this->getOrCreateDemoFile($filePath, $settings['_bfp_file_percent']);
            if ($demoPath && file_exists($demoPath)) {
                $filePath = $demoPath;
            }
        }
        
        // Clean any output buffers
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        // Set proper headers
        header('Content-Type: ' . $mimeType);
        header('Content-Length: ' . filesize($filePath));
        header('Accept-Ranges: bytes');
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: 0');
        
        // Use WordPress function to handle range requests
        if (isset($_SERVER['HTTP_RANGE'])) {
            $this->serveFileRange($filePath, filesize($filePath), $mimeType);
        } else {
            // Stream the file
            readfile($filePath);
        }
        
        exit;
    }
    
    /**
     * Handle range requests for audio seeking
     */
    private function serveFileRange(string $filePath, int $fileSize, string $mimeType): void {
        $range = $_SERVER['HTTP_RANGE'];
        list($sizeUnit, $rangeOrig) = explode('=', $range, 2);
        
        if ($sizeUnit == 'bytes') {
            list($range, $extraRanges) = explode(',', $rangeOrig, 2);
            list($seekStart, $seekEnd) = explode('-', $range, 2);
            
            $seekEnd = (empty($seekEnd)) ? ($fileSize - 1) : min(abs(intval($seekEnd)), ($fileSize - 1));
            $seekStart = (empty($seekStart) || $seekEnd < abs(intval($seekStart))) ? 0 : max(abs(intval($seekStart)), 0);
            
            header('HTTP/1.1 206 Partial Content');
            header('Content-Type: ' . $mimeType);
            header('Content-Length: ' . ($seekEnd - $seekStart + 1));
            header('Content-Range: bytes ' . $seekStart . '-' . $seekEnd . '/' . $fileSize);
            
            $fp = fopen($filePath, 'rb');
            fseek($fp, $seekStart);
            
            $buffer = 8192;
            $position = ftell($fp);
            
            while (!feof($fp) && $position <= $seekEnd) {
                $readSize = ($position + $buffer > $seekEnd) ? ($seekEnd - $position + 1) : $buffer;
                echo fread($fp, $readSize);
                flush();
                $position = ftell($fp);
            }
            
            fclose($fp);
        }
    }
    
    /**
     * Get or create demo file
     */
    private function getOrCreateDemoFile(string $originalPath, int $percent): ?string {
        $cacheKey = 'bfp_demo_' . md5($originalPath . '_' . $percent);
        $demoPath = get_transient($cacheKey);
        
        if ($demoPath && file_exists($demoPath)) {
            return $demoPath;
        }
        
        // Generate demo file
        $demoPath = $this->getAudioCore()->createDemoFile($originalPath, $percent);
        
        if ($demoPath) {
            // Cache for 24 hours
            set_transient($cacheKey, $demoPath, DAY_IN_SECONDS);
        }
        
        return $demoPath;
    }
}
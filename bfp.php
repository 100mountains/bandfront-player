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
    exit; // Exit if accessed directly
}

// CONSTANTS
define( 'BFP_VERSION', '0.1' );
define( 'BFP_PLUGIN_PATH', __FILE__ );
define( 'BFP_PLUGIN_BASE_NAME', plugin_basename( __FILE__ ) );
define( 'BFP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'BFP_WEBSITE_URL', ( isset( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] == 'on' ? 'https' : 'http' ) . '://' . ( isset( $_SERVER['HTTP_HOST'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) : '' ) . ( isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '' ) );
define( 'BFP_REMOTE_TIMEOUT', 240 );

/**
 * Handle streaming requests BEFORE WordPress loads (for better performance)
 */
function bfp_handle_early_streaming() {
    if (isset($_GET['bfp-stream']) && isset($_GET['bfp-product']) && isset($_GET['bfp-file'])) {
        // Load minimal WordPress
        if (!defined('ABSPATH')) {
            $wp_load_path = dirname(__FILE__) . '/../../../wp-load.php';
            if (file_exists($wp_load_path)) {
                require_once $wp_load_path;
            }
        }
        
        // Check if function exists (from audio.php)
        if (function_exists('bfp_handle_stream_request')) {
            bfp_handle_stream_request();
        }
    }
}

// Handle streaming early if this is a stream request
if (isset($_GET['bfp-stream'])) {
    bfp_handle_early_streaming();
}

/**
 * Main Bandfront Player Class
 */
class BandfrontPlayer {
    
    // Component instances
    private $_config;
    private $_player;
    private $_woocommerce;
    private $_hooks;
    private $_admin;
    private $_audio_core;
    private $_file_handler;
    private $_preview;
    private $_analytics;
    
    // State flags
    private $_purchased_product_flag = false;
    private $_force_purchased_flag = 0;
    private $_current_user_downloads = null;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->load_dependencies();
        $this->init_components();
        $this->register_streaming_handler();
    }
    
    /**
     * Register streaming handler for audio file requests
     */
    private function register_streaming_handler() {
        // Handle streaming requests
        add_action('init', array($this, 'handle_streaming_request'), 1);
    }
    
    /**
     * Handle streaming request
     */
    public function handle_streaming_request() {
        if (isset($_GET['bfp-stream']) && isset($_GET['bfp-product']) && isset($_GET['bfp-file'])) {
            $product_id = intval($_GET['bfp-product']);
            $file_id = sanitize_text_field($_GET['bfp-file']);
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('BFP Stream Request: Product=' . $product_id . ', File=' . $file_id);
            }
            
            // Get the product
            if (!function_exists('wc_get_product')) {
                status_header(500);
                error_log('BFP Stream Error: WooCommerce not available');
                die('WooCommerce not available');
            }
            
            $product = wc_get_product($product_id);
            if (!$product) {
                status_header(404);
                error_log('BFP Stream Error: Product not found - ' . $product_id);
                die('Product not found');
            }
            
            // Get files for this product
            $files = $this->get_player()->_get_product_files(array(
                'product' => $product,
                'file_id' => $file_id
            ));
            
            if (empty($files)) {
                status_header(404);
                error_log('BFP Stream Error: File not found - ' . $file_id . ' for product ' . $product_id);
                die('File not found');
            }
            
            $file = reset($files);
            $file_url = $file['file'];
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('BFP Stream: Found file URL - ' . $file_url);
            }
            
            // Track play event
            do_action('bfp_play_file', $product_id, $file_url);
            
            // Check if user has purchased product
            $purchased = false;
            if ($this->get_woocommerce()) {
                $purchased = $this->get_woocommerce()->woocommerce_user_product($product_id);
            }
            
            // Get settings for this product
            $settings = $this->get_config()->get_states(array(
                '_bfp_secure_player',
                '_bfp_file_percent'
            ), $product_id);
            
            // Stream the file directly instead of calling output_file
            $this->stream_audio_file($file_url, $product_id, $settings, $purchased);
            
            exit; // Stop execution after serving file
        }
    }
    
    /**
     * Stream audio file with proper headers
     */
    private function stream_audio_file($file_url, $product_id, $settings, $purchased) {
        // Process cloud URLs if needed
        if (strpos($file_url, 'drive.google.com') !== false) {
            $file_url = BFP_Cloud_Tools::get_google_drive_download_url($file_url);
        }
        
        // Check if file is local
        $local_path = $this->get_audio_core()->is_local($file_url);
        
        if ($local_path && file_exists($local_path)) {
            // Local file streaming
            $mime_type = 'audio/mpeg';
            $extension = strtolower(pathinfo($local_path, PATHINFO_EXTENSION));
            
            switch ($extension) {
                case 'wav':
                    $mime_type = 'audio/wav';
                    break;
                case 'ogg':
                case 'oga':
                    $mime_type = 'audio/ogg';
                    break;
                case 'm4a':
                    $mime_type = 'audio/mp4';
                    break;
                case 'mp3':
                default:
                    $mime_type = 'audio/mpeg';
                    break;
            }
            
            // Set headers
            header("Content-Type: $mime_type");
            header("Accept-Ranges: bytes");
            header("Cache-Control: no-cache, must-revalidate");
            header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
            
            $filesize = filesize($local_path);
            
            // Handle range requests for seeking
            if (isset($_SERVER['HTTP_RANGE'])) {
                $range = $_SERVER['HTTP_RANGE'];
                list($range_type, $range_value) = explode('=', $range, 2);
                
                if ($range_type == 'bytes') {
                    list($start, $end) = explode('-', $range_value, 2);
                    $start = intval($start);
                    $end = empty($end) ? ($filesize - 1) : intval($end);
                    $length = $end - $start + 1;
                    
                    header("HTTP/1.1 206 Partial Content");
                    header("Content-Range: bytes $start-$end/$filesize");
                    header("Content-Length: $length");
                    
                    $fp = fopen($local_path, 'rb');
                    fseek($fp, $start);
                    
                    $buffer_size = 8192;
                    $bytes_to_read = $length;
                    
                    while (!feof($fp) && $bytes_to_read > 0) {
                        $buffer = fread($fp, min($buffer_size, $bytes_to_read));
                        echo $buffer;
                        flush();
                        $bytes_to_read -= strlen($buffer);
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
                    $bytes_to_send = floor($filesize * ($settings['_bfp_file_percent'] / 100));
                    header("Content-Length: $bytes_to_send");
                    
                    $fp = fopen($local_path, 'rb');
                    $bytes_sent = 0;
                    
                    while (!feof($fp) && $bytes_sent < $bytes_to_send) {
                        $buffer = fread($fp, min(8192, $bytes_to_send - $bytes_sent));
                        echo $buffer;
                        $bytes_sent += strlen($buffer);
                        flush();
                    }
                    
                    fclose($fp);
                } else {
                    readfile($local_path);
                }
            }
        } else {
            // Remote file - redirect
            header("Location: $file_url");
        }
    }
    
    /**
     * Load required dependencies
     */
    private function load_dependencies() {
        // Load utility classes first
        require_once plugin_dir_path(__FILE__) . 'includes/utils/utils.php';
        require_once plugin_dir_path(__FILE__) . 'includes/utils/files.php';
        require_once plugin_dir_path(__FILE__) . 'includes/utils/cloud.php';
        require_once plugin_dir_path(__FILE__) . 'includes/utils/cache.php';
        require_once plugin_dir_path(__FILE__) . 'includes/utils/analytics.php';
        require_once plugin_dir_path(__FILE__) . 'includes/utils/preview.php';
        require_once plugin_dir_path(__FILE__) . 'includes/utils/update.php';
        
        // Load core components
        require_once plugin_dir_path(__FILE__) . 'includes/state-manager.php';
        require_once plugin_dir_path(__FILE__) . 'includes/player.php';
        require_once plugin_dir_path(__FILE__) . 'includes/audio.php';
        require_once plugin_dir_path(__FILE__) . 'includes/woocommerce.php';
        require_once plugin_dir_path(__FILE__) . 'includes/hooks.php';
        
        // Load widgets
        require_once plugin_dir_path(__FILE__) . 'widgets/playlist_widget.php';
        
        // Load admin if needed
        if (is_admin()) {
            require_once plugin_dir_path(__FILE__) . 'includes/admin.php';
        }
        
        // Load builders (Gutenberg, Elementor, etc.)
        if ( file_exists( plugin_dir_path(__FILE__) . 'builders/builders.php' ) ) {
            require_once plugin_dir_path(__FILE__) . 'builders/builders.php';
        }
    }
    
    /**
     * Initialize components
     */
    private function init_components() {
        // Initialize state manager first
        $this->_config = new BFP_Config($this);
        
        // Initialize other components
        $this->_player = new BFP_Player($this);
        $this->_audio_core = new BFP_Audio_Engine($this);
        $this->_file_handler = new BFP_File_Handler($this);
        $this->_preview = new BFP_Preview($this);
        $this->_analytics = new BFP_Analytics($this);
        
        // Initialize WooCommerce integration only if WooCommerce is active
        if (class_exists('WooCommerce')) {
            $this->_woocommerce = new BFP_WooCommerce($this);
        }
        
        // Initialize hooks
        $this->_hooks = new BFP_Hooks($this);
        
        // Initialize admin
        if (is_admin()) {
            $this->_admin = new BFP_Admin($this);
        }
    }
    
    /**
     * Plugin activation
     */
    public function activation() {
        $this->_file_handler->_createDir();
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivation() {
        $this->_file_handler->delete_purchased_files();
    }
    
    /**
     * Plugins loaded hook
     */
    public function plugins_loaded() {
        load_plugin_textdomain('bandfront-player', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }
    
    /**
     * Init hook
     */
    public function init() {
        if (!is_admin()) {
            $this->_preview->init();
            $this->_analytics->init();
        }
        
        // Register shortcodes
        if ($this->_woocommerce) {
            add_shortcode('bfp-playlist', array($this->_woocommerce, 'replace_playlist_shortcode'));
        }
    }
    
    // ===== DELEGATED METHODS TO PLAYER CLASS =====
    
    /**
     * Include main player - delegates to player class
     */
    public function include_main_player($product = '', $_echo = true) {
        return $this->get_player()->include_main_player($product, $_echo);
    }
    
    /**
     * Include all players - delegates to player class
     */
    public function include_all_players($product = '') {
        return $this->get_player()->include_all_players($product);
    }
    
    /**
     * Get product files - delegates to player class
     */
    public function get_product_files($product_id) {
        return $this->get_player()->get_product_files($product_id);
    }
    
    /**
     * Enqueue resources - delegates to player class
     */
    public function enqueue_resources() {
        return $this->get_player()->enqueue_resources();
    }
    
    /**
     * Get player HTML - delegates to player class
     */
    public function get_player($audio_url = null, $args = array()) {
        // If called with parameters, generate player HTML
        if ($audio_url !== null) {
            return $this->_player->get_player($audio_url, $args);
        }
        // Otherwise return player instance
        return $this->_player;
    }
    
    // ===== GETTER METHODS FOR COMPONENTS =====
    
    /**
     * Get config/state manager
     */
    public function get_config() {
        return $this->_config;
    }
    
    /**
     * Get WooCommerce integration
     * Returns null if WooCommerce is not active
     */
    public function get_woocommerce() {
        return $this->_woocommerce;
    }
    
    /**
     * Get audio core
     */
    public function get_audio_core() {
        return $this->_audio_core;
    }
    
    /**
     * Get file handler
     */
    public function get_file_handler() {
        return $this->_file_handler;
    }
    
    /**
     * Get analytics
     */
    public function get_analytics() {
        return $this->_analytics;
    }
    
    // ===== STATE MANAGEMENT SHORTCUTS =====
    
    /**
     * Get state value - delegates to config
     * This provides a convenient shortcut from the main plugin instance
     */
    public function get_state($key, $default = null, $product_id = null, $options = array()) {
        return $this->_config->get_state($key, $default, $product_id, $options);
    }
    
    /**
     * Check if module is enabled - delegates to config
     */
    public function is_module_enabled($module_name) {
        return $this->_config->is_module_enabled($module_name);
    }
    
    // Remove these legacy methods if they exist:
    // - get_global_attr() 
    // - get_product_attr()
    // These should ONLY exist in BFP_Config for backward compatibility
    
    // ===== DELEGATED METHODS TO OTHER COMPONENTS =====
    
    /**
     * Replace playlist shortcode
     * Delegates to WooCommerce integration if available
     */
    public function replace_playlist_shortcode($atts = array()) {
        if ($this->_woocommerce) {
            return $this->_woocommerce->replace_playlist_shortcode($atts);
        }
        return '';
    }
    
    /**
     * Check if user purchased product
     * Safe wrapper that checks if WooCommerce integration exists
     */
    public function woocommerce_user_product($product_id) {
        if ($this->_woocommerce) {
            return $this->_woocommerce->woocommerce_user_product($product_id);
        }
        return false;
    }
    
    /**
     * Generate audio URL
     */
    public function generate_audio_url($product_id, $file_index, $file_data = array()) {
        return $this->_audio_core->generate_audio_url($product_id, $file_index, $file_data);
    }
    
    /**
     * Get duration by URL
     */
    public function get_duration_by_url($url) {
        return $this->_audio_core->get_duration_by_url($url);
    }
    
    /**
     * Delete post
     */
    public function delete_post($post_id, $demos_only = false, $force = false) {
        return $this->_file_handler->delete_post($post_id, $demos_only, $force);
    }
    
    /**
     * Clear directory
     */
    public function _clearDir($dirPath) {
        return $this->_file_handler->_clearDir($dirPath);
    }
    
    /**
     * Clear expired transients
     */
    public function clear_expired_transients() {
        return $this->_file_handler->clear_expired_transients();
    }
    
    /**
     * Get files directory path
     */
    public function get_files_directory_path() {
        return $this->_file_handler->get_files_directory_path();
    }
    
    /**
     * Get files directory URL
     */
    public function get_files_directory_url() {
        return $this->_file_handler->get_files_directory_url();
    }
    
    // ===== UTILITY METHODS =====
    
    /**
     * Get post types
     */
    public function _get_post_types($string = false) {
        return BFP_Utils::get_post_types($string);
    }
    
    /**
     * Get player layouts
     */
    public function get_player_layouts() {
        return $this->_config->get_player_layouts();
    }
    
    /**
     * Get player controls
     */
    public function get_player_controls() {
        return $this->_config->get_player_controls();
    }
    
    // ===== STATE FLAGS GETTERS/SETTERS =====
    
    /**
     * Get/Set purchased product flag
     */
    public function get_purchased_product_flag() {
        return $this->_purchased_product_flag;
    }
    
    public function set_purchased_product_flag($flag) {
        $this->_purchased_product_flag = $flag;
    }
    
    /**
     * Get/Set force purchased flag
     */
    public function get_force_purchased_flag() {
        return $this->_force_purchased_flag;
    }
    
    public function set_force_purchased_flag($flag) {
        $this->_force_purchased_flag = $flag;
    }
    
    /**
     * Get/Set current user downloads
     */
    public function get_current_user_downloads() {
        return $this->_current_user_downloads;
    }
    
    public function set_current_user_downloads($downloads) {
        $this->_current_user_downloads = $downloads;
    }
    
    /**
     * Get/Set insert player flags - delegates to player class
     */
    public function get_insert_player() {
        return $this->_player->get_insert_player();
    }
    
    public function set_insert_player($value) {
        return $this->_player->set_insert_player($value);
    }
    
    public function get_insert_main_player() {
        return $this->_player->get_insert_main_player();
    }
    
    public function set_insert_main_player($value) {
        return $this->_player->set_insert_main_player($value);
    }
    
    public function get_insert_all_players() {
        return $this->_player->get_insert_all_players();
    }
    
    public function set_insert_all_players($value) {
        return $this->_player->set_insert_all_players($value);
    }
}

// Initialize plugin
global $BandfrontPlayer;
$BandfrontPlayer = new BandfrontPlayer();
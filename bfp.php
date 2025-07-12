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
     * Get state value - shortcut to config
     */
    public function get_state($key, $default = null, $product_id = null) {
        return $this->_config->get_state($key, $default, $product_id);
    }
    
    /**
     * Get product attribute - delegates to config
     */
    public function get_product_attr($product_id, $attr, $default = false) {
        return $this->_config->get_product_attr($product_id, $attr, $default);
    }
    
    /**
     * Get global attribute - delegates to config
     */
    public function get_global_attr($attr, $default = false) {
        return $this->_config->get_global_attr($attr, $default);
    }
    
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
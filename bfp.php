<?php
/**
 * Plugin Name: Bandfront Player
 * Plugin URI: http://bandfront.com/
 * Description: Music Player for WooCommerce
 * Version: 5.0.181
 * Text Domain: bandfront-player
 * Author: Bandfront.com
 * Author URI: https://bandfront.com
 * License: GPL2
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Define plugin constants
define('BFP_VERSION', '5.0.181');
define('BFP_PLUGIN_PATH', __FILE__);
define('BFP_PLUGIN_BASE_NAME', plugin_basename(__FILE__));
define('BFP_WEBSITE_URL', get_home_url());
define('BFP_REMOTE_TIMEOUT', 300);
define('BFP_DEFAULT_SINGLE_PLAYER', 0);
define('BFP_DEFAULT_PLAYER_VOLUME', 1);
define('BFP_DEFAULT_PLAYER_LAYOUT', 'dark');
define('BFP_DEFAULT_PLAYER_CONTROLS', 'default');
define('BFP_FILE_PERCENT', 100);

// Include class files
require_once plugin_dir_path(__FILE__) . 'includes/state-manager.php';
require_once plugin_dir_path(__FILE__) . 'includes/audio.php';
require_once plugin_dir_path(__FILE__) . 'includes/woocommerce.php';
require_once plugin_dir_path(__FILE__) . 'includes/player.php';
require_once plugin_dir_path(__FILE__) . 'includes/player-renderer.php';
require_once plugin_dir_path(__FILE__) . 'includes/playlist-renderer.php';
require_once plugin_dir_path(__FILE__) . 'includes/hooks.php';
require_once plugin_dir_path(__FILE__) . 'includes/cover-renderer.php';
require_once plugin_dir_path(__FILE__) . 'includes/admin.php';

// Include utility classes
require_once plugin_dir_path(__FILE__) . 'includes/utils/utils.php';
require_once plugin_dir_path(__FILE__) . 'includes/utils/update.php';
require_once plugin_dir_path(__FILE__) . 'includes/utils/preview.php';
require_once plugin_dir_path(__FILE__) . 'includes/utils/files.php';
require_once plugin_dir_path(__FILE__) . 'includes/utils/cloud.php';
require_once plugin_dir_path(__FILE__) . 'includes/utils/cache.php';
require_once plugin_dir_path(__FILE__) . 'includes/utils/analytics.php';

/**
 * Main plugin class
 */
class BandfrontPlayer {
    
    // Component instances
    private $_config;
    private $_audio_engine;
    private $_woocommerce;
    private $_player;
    private $_player_renderer;
    private $_playlist_renderer;
    private $_hooks;
    private $_admin;
    private $_file_handler;
    private $_preview;
    private $_analytics;
    
    // State variables
    private $_force_purchased_flag = 0;
    private $_current_user_downloads = array();
    private $_purchased_product_flag = false;
    
    /**
     * Constructor
     */
    public function __construct() {
        // Initialize components
        $this->_config = new BFP_Config($this);
        $this->_audio_engine = new BFP_Audio_Engine($this);
        $this->_woocommerce = new BFP_WooCommerce($this);
        $this->_player = new BFP_Player($this);
        $this->_player_renderer = new BFP_Player_Renderer($this);
        $this->_playlist_renderer = new BFP_Playlist_Renderer($this);
        $this->_file_handler = new BFP_File_Handler($this);
        $this->_preview = new BFP_Preview($this);
        $this->_analytics = new BFP_Analytics($this);
        
        // Initialize hooks (this registers all WordPress hooks)
        $this->_hooks = new BFP_Hooks($this);
        
        // Initialize admin only in admin area
        if (is_admin()) {
            $this->_admin = new BFP_Admin($this);
        }
        
        // Initialize preview handler
        $this->_preview->init();
        
        // Initialize analytics
        $this->_analytics->init();
    }
    
    // Component getters
    public function get_config() { return $this->_config; }
    public function get_audio_core() { return $this->_audio_engine; }
    public function get_woocommerce() { return $this->_woocommerce; }
    public function get_player_renderer() { return $this->_player_renderer; }
    public function get_playlist_renderer() { return $this->_playlist_renderer; }
    public function get_file_handler() { return $this->_file_handler; }
    public function get_analytics() { return $this->_analytics; }
    public function get_admin() { return $this->_admin; }
    
    // Delegated methods for backward compatibility
    public function get_state($key, $default = null, $product_id = null, $options = array()) {
        return $this->_config->get_state($key, $default, $product_id, $options);
    }
    
    public function get_states($keys, $product_id = null) {
        return $this->_config->get_states($keys, $product_id);
    }
    
    public function get_product_attr($product_id, $attr, $default = false) {
        return $this->_config->get_product_attr($product_id, $attr, $default);
    }
    
    public function get_global_attr($attr, $default = false) {
        return $this->_config->get_global_attr($attr, $default);
    }
    
    public function get_player_layouts() {
        return $this->_config->get_player_layouts();
    }
    
    public function get_player_controls() {
        return $this->_config->get_player_controls();
    }
    
    // Player methods - FIXED: Delegate to _player component
    public function enqueue_resources() {
        return $this->_player->enqueue_resources();
    }
    
    public function get_player($audio_url, $args = array()) {
        return $this->_player->get_player($audio_url, $args);
    }
    
    public function include_main_player($product = '', $_echo = true) {
        return $this->_player_renderer->include_main_player($product, $_echo);
    }
    
    public function include_all_players($product = '') {
        return $this->_player_renderer->include_all_players($product);
    }
    
    // WooCommerce methods
    public function woocommerce_user_product($product_id) {
        return $this->_woocommerce->woocommerce_user_product($product_id);
    }
    
    public function replace_playlist_shortcode($atts) {
        return $this->_woocommerce->replace_playlist_shortcode($atts);
    }
    
    // File handler methods
    public function get_files_directory_path() {
        return $this->_file_handler->get_files_directory_path();
    }
    
    public function get_files_directory_url() {
        return $this->_file_handler->get_files_directory_url();
    }
    
    public function _clearDir($dirPath) {
        return $this->_file_handler->_clearDir($dirPath);
    }
    
    public function delete_post($post_id, $demos_only = false, $force = false) {
        return $this->_file_handler->delete_post($post_id, $demos_only, $force);
    }
    
    public function clear_expired_transients() {
        return $this->_file_handler->clear_expired_transients();
    }
    
    // Audio engine methods
    public function generate_audio_url($product_id, $file_index, $file_data = array()) {
        return $this->_audio_engine->generate_audio_url($product_id, $file_index, $file_data);
    }
    
    public function get_duration_by_url($url) {
        return $this->_audio_engine->get_duration_by_url($url);
    }
    
    // Utility methods
    public function _get_post_types($string = false) {
        return BFP_Utils::get_post_types($string);
    }
    
    public function _sort_list($a, $b) {
        return BFP_Utils::sort_list($a, $b);
    }
    
    // State management methods - FIXED: Delegate to _player component
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
    
    public function get_force_purchased_flag() {
        return $this->_force_purchased_flag;
    }
    
    public function set_force_purchased_flag($value) {
        $this->_force_purchased_flag = $value;
    }
    
    public function get_current_user_downloads() {
        return $this->_current_user_downloads;
    }
    
    public function set_current_user_downloads($value) {
        $this->_current_user_downloads = $value;
    }
    
    public function get_purchased_product_flag() {
        return $this->_purchased_product_flag;
    }
    
    public function set_purchased_product_flag($value) {
        $this->_purchased_product_flag = $value;
    }
    
    // Get product files
    public function get_product_files($product_id) {
        $product = wc_get_product($product_id);
        if (!$product) {
            return array();
        }
        
        return $this->_player_renderer->_get_product_files(array(
            'product' => $product,
            'all' => true
        ));
    }
    
    // Hook callbacks
    public function plugins_loaded() {
        load_plugin_textdomain('bandfront-player', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }
    
    public function init() {
        add_shortcode('bfp-playlist', array($this, 'replace_playlist_shortcode'));
        
        if (!wp_next_scheduled('bfp_delete_purchased_files')) {
            wp_schedule_event(time(), 'daily', 'bfp_delete_purchased_files');
        }
        
        add_action('bfp_delete_purchased_files', array($this->_file_handler, 'delete_purchased_files'));
    }
    
    public function activation() {
        wp_clear_scheduled_hook('bfp_delete_purchased_files');
        wp_schedule_event(time(), 'daily', 'bfp_delete_purchased_files');
    }
    
    public function deactivation() {
        wp_clear_scheduled_hook('bfp_delete_purchased_files');
    }
}

// Create global instance
$GLOBALS['BandfrontPlayer'] = new BandfrontPlayer();

// Add troubleshoot filter for backward compatibility
add_filter('option_sbp_settings', array('BFP_Utils', 'troubleshoot'));
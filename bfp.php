<?php
/*
Plugin Name: Bandfront Player
Plugin URI: https://therob.lol
Version: 0.1
Text Domain: bandfront-player
Author: Bleep
Author URI: https://therob.lol
Description: Bandfront Player is a WordPress plugin that integrates a music player into WooCommerce product pages, allowing users to play audio files associated with products. It supports various player layouts, controls, and features like secure playback and file management. 
License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

// Security check
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// CONSTANTS
define('BFP_PLUGIN_PATH', __FILE__);
define('BFP_PLUGIN_BASE_NAME', plugin_basename(__FILE__));
define('BFP_WEBSITE_URL', get_home_url(get_current_blog_id(), '', is_ssl() ? 'https' : 'http'));
define('BFP_PLUGIN_URL', plugins_url('', __FILE__));
define('BFP_DEFAULT_PLAYER_LAYOUT', 'dark');
define('BFP_DEFAULT_SINGLE_PLAYER', 0);
define('BFP_DEFAULT_PLAYER_VOLUME', 1);
define('BFP_DEFAULT_PLAYER_CONTROLS', 'default');
define('BFP_FILE_PERCENT', 50);
define('BFP_REMOTE_TIMEOUT', 300);
define('BFP_DEFAULT_PlAYER_TITLE', 1);
define('BFP_VERSION', '0.1');

// Load required classes
require_once 'includes/class-bfp-auto-updater.php';
require_once 'includes/class-bfp-utils.php';
require_once 'includes/class-bfp-cache-manager.php';
require_once 'includes/class-bfp-cloud-tools.php';
require_once 'includes/class-bfp-config.php';
require_once 'includes/class-bfp-file-handler.php';
require_once 'includes/class-bfp-player-manager.php';
require_once 'includes/class-bfp-audio-processor.php';
require_once 'includes/class-bfp-woocommerce.php';
require_once 'includes/class-bfp-hooks-manager.php';
require_once 'includes/class-bfp-player-renderer.php';
require_once 'includes/class-bfp-playlist-renderer.php';
require_once 'includes/class-bfp-analytics.php';
require_once 'includes/class-bfp-preview-manager.php';

// Load widgets
require_once 'widgets/playlist_widget.php';

// Load admin class if in admin
if (is_admin()) {
    require_once 'includes/class-bfp-admin.php';
}

if (!class_exists('BandfrontPlayer')) {
    class BandfrontPlayer {
        
        // Component instances
        private $_admin;
        private $_config;
        private $_file_handler;
        private $_player_manager;
        private $_audio_processor;
        private $_woocommerce;
        private $_hooks_manager;
        private $_player_renderer;
        private $_playlist_renderer;
        private $_analytics;
        private $_preview_manager;
        
        // State flags - these should be managed by the state handler
        private $_current_user_downloads = array();
        private $_force_purchased_flag = 0;
        private $_purchased_product_flag = false;
        
        /**
         * Constructor
         */
        public function __construct() {
            $this->init_components();
        }
        
        /**
         * Initialize all components
         */
        private function init_components() {
            // Initialize core components
            $this->_config = new BFP_Config($this);
            $this->_file_handler = new BFP_File_Handler($this);
            $this->_player_manager = new BFP_Player_Manager($this);
            $this->_audio_processor = new BFP_Audio_Processor($this);
            $this->_woocommerce = new BFP_WooCommerce($this);
            $this->_player_renderer = new BFP_Player_Renderer($this);
            $this->_playlist_renderer = new BFP_Playlist_Renderer($this);
            $this->_analytics = new BFP_Analytics($this);
            $this->_preview_manager = new BFP_Preview_Manager($this);
            
            // Initialize admin if in admin area
            if (is_admin()) {
                $this->_admin = new BFP_Admin($this);
            }
            
            // Initialize hooks manager last (it depends on other components)
            $this->_hooks_manager = new BFP_Hooks_Manager($this);
            
            // Initialize components that need to hook into WordPress
            $this->_analytics->init();
            $this->_preview_manager->init();
        }
        
        // Component getters
        public function get_config() { return $this->_config; }
        public function get_file_handler() { return $this->_file_handler; }
        public function get_player_manager() { return $this->_player_manager; }
        public function get_audio_processor() { return $this->_audio_processor; }
        public function get_woocommerce() { return $this->_woocommerce; }
        public function get_hooks_manager() { return $this->_hooks_manager; }
        public function get_player_renderer() { return $this->_player_renderer; }
        public function get_playlist_renderer() { return $this->_playlist_renderer; }
        public function get_analytics() { return $this->_analytics; }
        public function get_preview_manager() { return $this->_preview_manager; }
        public function get_admin() { return $this->_admin; }
        
        // Delegate methods to appropriate components - for backward compatibility
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
        
        public function enqueue_resources() {
            return $this->_player_manager->enqueue_resources();
        }
        
        public function get_player($audio_url, $args = array()) {
            return $this->_player_manager->get_player($audio_url, $args);
        }
        
        public function include_main_player($product = '', $_echo = true) {
            return $this->_player_renderer->include_main_player($product, $_echo);
        }
        
        public function include_all_players($product = '') {
            return $this->_player_renderer->include_all_players($product);
        }
        
        public function woocommerce_user_product($product_id) {
            return $this->_woocommerce->woocommerce_user_product($product_id);
        }
        
        public function get_product_files($product_id) {
            $product = wc_get_product($product_id);
            return $this->_player_renderer->_get_product_files(array(
                'product' => $product,
                'all' => true
            ));
        }
        
        public function generate_audio_url($product_id, $index, $file) {
            return $this->_audio_processor->generate_audio_url($product_id, $index, $file);
        }
        
        public function get_duration_by_url($url) {
            return $this->_audio_processor->get_duration_by_url($url);
        }
        
        public function delete_post($post_id, $demos_only = false, $force = false) {
            return $this->_file_handler->delete_post($post_id, $demos_only, $force);
        }
        
        public function _clearDir($dirPath) {
            return $this->_file_handler->_clearDir($dirPath);
        }
        
        public function clear_expired_transients() {
            return $this->_file_handler->clear_expired_transients();
        }
        
        // State management methods - TODO: Move these to state handler
        public function get_current_user_downloads() { return $this->_current_user_downloads; }
        public function set_current_user_downloads($value) { $this->_current_user_downloads = $value; }
        
        public function get_force_purchased_flag() { return $this->_force_purchased_flag; }
        public function set_force_purchased_flag($value) { $this->_force_purchased_flag = $value; }
        
        public function get_purchased_product_flag() { return $this->_purchased_product_flag; }
        public function set_purchased_product_flag($value) { $this->_purchased_product_flag = $value; }
        
        public function get_files_directory_path() { return $this->_file_handler->get_files_directory_path(); }
        public function get_files_directory_url() { return $this->_file_handler->get_files_directory_url(); }
        
        // Player state management delegated methods
        public function get_insert_player() { return $this->_player_manager->get_insert_player(); }
        public function set_insert_player($value) { return $this->_player_manager->set_insert_player($value); }
        
        public function get_insert_main_player() { return $this->_player_manager->get_insert_main_player(); }
        public function set_insert_main_player($value) { return $this->_player_manager->set_insert_main_player($value); }
        
        public function get_insert_all_players() { return $this->_player_manager->get_insert_all_players(); }
        public function set_insert_all_players($value) { return $this->_player_manager->set_insert_all_players($value); }
        
        public function get_enqueued_resources() { return $this->_player_manager->get_enqueued_resources(); }
        public function set_enqueued_resources($value) { return $this->_player_manager->set_enqueued_resources($value); }
        
        /**
         * Get state value with context-aware inheritance
         * Convenience method that delegates to the config handler
         * 
         * @param string $key Setting key
         * @param mixed $default Default value
         * @param int|null $product_id Product ID for context
         * @return mixed The resolved setting value
         */
        public function get_state($key, $default = null, $product_id = null) {
            return $this->_config->get_state($key, $default, $product_id);
        }
        
        /**
         * Get multiple states efficiently
         * 
         * @param array $keys Array of setting keys
         * @param int|null $product_id Product ID for context
         * @return array Associative array of key => value
         */
        public function get_states($keys, $product_id = null) {
            return $this->_config->get_states($keys, $product_id);
        }
        
        // Static utility methods
        public function _get_post_types($string = false) {
            return BFP_Utils::get_post_types($string);
        }
        
        public function _sort_list($a, $b) {
            return BFP_Utils::sort_list($a, $b);
        }
        
        // Plugin lifecycle methods (called by hooks)
        public function activation() {
            // Any activation logic
        }
        
        public function deactivation() {
            // Any deactivation logic
        }
        
        public function plugins_loaded() {
            // Load text domain
            load_plugin_textdomain('bandfront-player', false, dirname(plugin_basename(__FILE__)) . '/languages');
        }
        
        public function init() {
            // Register shortcodes
            add_shortcode('bfp-playlist', array($this->_woocommerce, 'replace_playlist_shortcode'));
            
            // Schedule cron events
            if (!wp_next_scheduled('bfp_schedule_delete_purchased_files')) {
                wp_schedule_event(time(), 'daily', 'bfp_schedule_delete_purchased_files');
            }
            add_action('bfp_schedule_delete_purchased_files', array($this->_file_handler, 'delete_purchased_files'));
        }
    }
}

// Create global instance
$GLOBALS['BandfrontPlayer'] = new BandfrontPlayer();

// Add troubleshoot filter for backward compatibility
add_filter('option_sbp_settings', array('BFP_Utils', 'troubleshoot'));
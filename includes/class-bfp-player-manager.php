<?php
/**
 * Player management functionality for Bandfront Player
 *
 * @package BandfrontPlayer
 * @since 0.1
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * BFP Player Manager Class
 */
class BFP_Player_Manager {
    
    private $main_plugin;
    private $_enqueued_resources = false;
    private $_inserted_player = false;
    private $_insert_player = true;
    private $_insert_main_player = true;
    private $_insert_all_players = true;
    private $_preload_times = 0; // Multiple preloads with demo generators can affect the server performance
    
    public function __construct($main_plugin) {
        $this->main_plugin = $main_plugin;
    }
    
    /**
     * Generate player HTML
     */
    public function get_player($audio_url, $args = array()) {
        // Player generation logic will be moved here
        return $this->main_plugin->get_player_original($audio_url, $args);
    }
    
    /**
     * Include main player in content
     */
    public function include_main_player($product = '', $_echo = true) {
        // Main player inclusion logic will be moved here
        return $this->main_plugin->include_main_player_original($product, $_echo);
    }
    
    /**
     * Get enqueued resources state
     */
    public function get_enqueued_resources() {
        return $this->_enqueued_resources;
    }
    
    /**
     * Set enqueued resources state
     */
    public function set_enqueued_resources($value) {
        $this->_enqueued_resources = $value;
    }
    
    /**
     * Get insert player flag
     */
    public function get_insert_player() {
        return $this->_insert_player;
    }
    
    /**
     * Set insert player flag
     */
    public function set_insert_player($value) {
        $this->_insert_player = $value;
    }
    
    /**
     * Get inserted player state
     */
    public function get_inserted_player() {
        return $this->_inserted_player;
    }
    
    /**
     * Set inserted player state
     */
    public function set_inserted_player($value) {
        $this->_inserted_player = $value;
    }
    
    /**
     * Get insert main player flag
     */
    public function get_insert_main_player() {
        return $this->_insert_main_player;
    }
    
    /**
     * Set insert main player flag
     */
    public function set_insert_main_player($value) {
        $this->_insert_main_player = $value;
    }
    
    /**
     * Get insert all players flag
     */
    public function get_insert_all_players() {
        return $this->_insert_all_players;
    }
    
    /**
     * Set insert all players flag
     */
    public function set_insert_all_players($value) {
        $this->_insert_all_players = $value;
    }
    
    /**
     * Enqueue player resources
     */
    public function enqueue_resources() {
        global $BandfrontPlayer;
        
        // Get audio engine setting
        $audio_engine = $BandfrontPlayer->get_global_attr('_bfp_audio_engine', 'mediaelement');
        
        if ($audio_engine === 'wavesurfer') {
            // Check if WaveSurfer is available locally
            $wavesurfer_path = plugin_dir_path(dirname(__FILE__)) . 'vendors/wavesurfer/wavesurfer.min.js';
            
            if (file_exists($wavesurfer_path)) {
                // Enqueue local WaveSurfer.js
                wp_enqueue_script(
                    'wavesurfer',
                    plugin_dir_url(dirname(__FILE__)) . 'vendors/wavesurfer/wavesurfer.min.js',
                    array(),
                    '7.9.9',
                    true
                );
            } else {
                // Fallback to CDN if local file doesn't exist
                wp_enqueue_script(
                    'wavesurfer',
                    'https://unpkg.com/wavesurfer.js@7/dist/wavesurfer.min.js',
                    array(),
                    '7.9.9',
                    true
                );
            }
            
            // Enqueue WaveSurfer integration (our custom file)
            wp_enqueue_script(
                'bfp-wavesurfer-integration',
                plugin_dir_url(dirname(__FILE__)) . 'js/wavesurfer.js',
                array('jquery', 'wavesurfer'),
                BFP_VERSION,
                true
            );
        } else {
            // ...existing MediaElement.js code...
        }
        
        // ...existing code...
    }
}

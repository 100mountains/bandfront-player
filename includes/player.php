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
class BFP_Player {
    
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
        if (!is_array($args)) $args = array();
        
        $product_id = isset($args['product_id']) ? $args['product_id'] : 0;
        $player_controls = isset($args['player_controls']) ? $args['player_controls'] : '';
        $player_style = isset($args['player_style']) ? $args['player_style'] : BFP_DEFAULT_PLAYER_LAYOUT;
        $media_type = isset($args['media_type']) ? $args['media_type'] : 'mp3';
        $id = isset($args['id']) ? $args['id'] : 0;
        $duration = isset($args['duration']) ? $args['duration'] : false;
        $preload = isset($args['preload']) ? $args['preload'] : 'none';
        $volume = isset($args['volume']) ? $args['volume'] : 1;
        
        // Apply filters
        $preload = apply_filters('bfp_preload', $preload, $audio_url);
        
        // Generate unique player ID
        $player_id = 'bfp-player-' . $product_id . '-' . $id . '-' . uniqid();
        
        // Build player HTML
        $player_html = '<audio id="' . esc_attr($player_id) . '" ';
        $player_html .= 'class="bfp-player ' . esc_attr($player_style) . '" ';
        $player_html .= 'data-product-id="' . esc_attr($product_id) . '" ';
        $player_html .= 'data-file-index="' . esc_attr($id) . '" ';
        $player_html .= 'preload="' . esc_attr($preload) . '" ';
        $player_html .= 'data-volume="' . esc_attr($volume) . '" ';
        
        if ($player_controls) {
            $player_html .= 'data-controls="' . esc_attr($player_controls) . '" ';
        }
        
        if ($duration) {
            $player_html .= 'data-duration="' . esc_attr($duration) . '" ';
        }
        
        $player_html .= '>';
        $player_html .= '<source src="' . esc_url($audio_url) . '" type="audio/' . esc_attr($media_type) . '" />';
        $player_html .= '</audio>';
        
        return apply_filters('bfp_player_html', $player_html, $audio_url, $args);
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
        if ($this->_enqueued_resources) {
            return;
        }
        
        global $BandfrontPlayer;
        
        // Get audio engine setting using new state handler
        $audio_engine = $BandfrontPlayer->get_config()->get_state('_bfp_audio_engine');
        
        // Enqueue base styles
        wp_enqueue_style(
            'bfp-style', 
            plugin_dir_url(dirname(__FILE__)) . 'css/style.css', 
            array(), 
            BFP_VERSION
        );
        
        // Enqueue jQuery
        wp_enqueue_script('jquery');
        
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
            // Enqueue MediaElement.js
            wp_enqueue_style('wp-mediaelement');
            wp_enqueue_script('wp-mediaelement');
            
            // Get selected player skin using new state handler
            $selected_skin = $BandfrontPlayer->get_config()->get_state('_bfp_player_layout');
        
            
            if (isset($skin_mapping[$selected_skin])) {
                $selected_skin = $skin_mapping[$selected_skin];
            }
            
            // Validate skin selection
            if (!in_array($selected_skin, array('dark', 'light', 'custom'))) {
                $selected_skin = 'dark';
            }
            
            // Enqueue selected skin CSS file
            wp_enqueue_style(
                'bfp-skin-' . $selected_skin,
                plugin_dir_url(dirname(__FILE__)) . 'css/skins/' . $selected_skin . '.css',
                array('wp-mediaelement'),
                BFP_VERSION
            );
        }
        
        // Enqueue main engine script
        wp_enqueue_script(
            'bfp-engine',
            plugin_dir_url(dirname(__FILE__)) . 'js/engine.js',
            array('jquery'),
            BFP_VERSION,
            true
        );
        
        // Localize script with settings using bulk fetch
        $settings_keys = array(
            '_bfp_play_simultaneously',
            '_bfp_ios_controls',
            '_bfp_fade_out',
            '_bfp_on_cover',
            '_bfp_enable_visualizations'
        );
        
        $settings = $BandfrontPlayer->get_config()->get_states($settings_keys);
        
        $js_settings = array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'audio_engine' => $audio_engine,
            'play_simultaneously' => $settings['_bfp_play_simultaneously'],
            'ios_controls' => $settings['_bfp_ios_controls'],
            'fade_out' => $settings['_bfp_fade_out'],
            'on_cover' => $settings['_bfp_on_cover'],
            'visualizations' => $settings['_bfp_enable_visualizations'],
            'player_skin' => $selected_skin
        );
        
        wp_localize_script('bfp-engine', 'bfp_global_settings', $js_settings);
        
        $this->_enqueued_resources = true;
    }
}

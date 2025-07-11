<?php
/**
 * Analytics functionality for Bandfront Player
 *
 * @package BandfrontPlayer
 * @since 0.1
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * BFP Analytics Class
 * Handles playback tracking and analytics integration
 */
class BFP_Analytics {
    
    private $main_plugin;
    
    public function __construct($main_plugin) {
        $this->main_plugin = $main_plugin;
    }
    
    /**
     * Initialize analytics tracking
     */
    public function init() {
        add_action('bfp_play_file', array($this, 'track_play_event'), 10, 2);
    }
    
    /**
     * Track play event
     */
    public function track_play_event($product_id, $file_url) {
        $this->main_plugin->get_audio_processor()->tracking_play_event($product_id, $file_url);
    }
    
    /**
     * Increment playback counter
     */
    public function increment_playback_counter($product_id) {
        if (!$this->main_plugin->get_global_attr('_bfp_playback_counter_column', 1)) {
            return;
        }
        
        $counter = get_post_meta($product_id, '_bfp_playback_counter', true);
        $counter = empty($counter) ? 1 : intval($counter) + 1;
        update_post_meta($product_id, '_bfp_playback_counter', $counter);
    }
}

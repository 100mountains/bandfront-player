<?php
namespace bfp\Utils;

use bfp\Plugin;

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
 * Analytics Class
 * Handles playback tracking and analytics integration
 */
class Analytics {
    
    private Plugin $mainPlugin;
    
    public function __construct(Plugin $mainPlugin) {
        $this->mainPlugin = $mainPlugin;
    }
    
    /**
     * Initialize analytics tracking
     */
    public function init(): void {
        add_action('bfp_play_file', [$this, 'trackPlayEvent'], 10, 2);
    }
    
    /**
     * Track play event
     */
    public function trackPlayEvent(int $productId, string $fileUrl): void {
        $this->mainPlugin->getAudioCore()->trackingPlayEvent($productId, $fileUrl);
    }
    
    /**
     * Increment playback counter
     */
    public function incrementPlaybackCounter(int $productId): void {
        // Use getState for single value retrieval
        if (!$this->mainPlugin->getConfig()->getState('_bfp_playback_counter_column', 1)) {
            return;
        }
        
        $counter = get_post_meta($productId, '_bfp_playback_counter', true);
        $counter = empty($counter) ? 1 : intval($counter) + 1;
        update_post_meta($productId, '_bfp_playback_counter', $counter);
    }
}
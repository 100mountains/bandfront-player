<?php
namespace bfp\Utils;

use bfp\Plugin;
use bfp\Utils\Debug; // DEBUG-REMOVE
/**
 * Analytics functionality for Bandfront Player
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Analytics Class
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
        $current = (int) get_post_meta($productId, '_bfp_playback_counter', true);
        update_post_meta($productId, '_bfp_playback_counter', $current + 1);
    }
}
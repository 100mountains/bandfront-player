<?php
declare(strict_types=1);

namespace Bandfront\Audio;

use Bandfront\Core\Config;
use Bandfront\Utils\Debug;

/**
 * Handles audio playback tracking and AJAX requests
 * Replaces JavaScript-based tracking with server-side processing
 * 
 * @package Bandfront\Audio
 * @since 2.0.0
 */
class PlaybackController {
    
    private Config $config;
    private Analytics $analytics;
    
    public function __construct(Config $config, Analytics $analytics) {
        $this->config = $config;
        $this->analytics = $analytics;
    }
    
    /**
     * Register AJAX handlers for playback events
     */
    public function registerHandlers(): void {
        // Public AJAX handlers
        add_action('wp_ajax_nopriv_bfp_track_play', [$this, 'handlePlayEvent']);
        add_action('wp_ajax_bfp_track_play', [$this, 'handlePlayEvent']);
        
        add_action('wp_ajax_nopriv_bfp_track_pause', [$this, 'handlePauseEvent']);
        add_action('wp_ajax_bfp_track_pause', [$this, 'handlePauseEvent']);
        
        add_action('wp_ajax_nopriv_bfp_track_ended', [$this, 'handleEndedEvent']);
        add_action('wp_ajax_bfp_track_ended', [$this, 'handleEndedEvent']);
        
        add_action('wp_ajax_nopriv_bfp_get_next_track', [$this, 'getNextTrack']);
        add_action('wp_ajax_bfp_get_next_track', [$this, 'getNextTrack']);
    }
    
    /**
     * Handle play event via AJAX
     */
    public function handlePlayEvent(): void {
        // Verify nonce
        if (!check_ajax_referer('bfp_ajax_nonce', 'nonce', false)) {
            wp_die('Security check failed');
        }
        
        $productId = isset($_POST['product_id']) ? absint($_POST['product_id']) : 0;
        $fileIndex = isset($_POST['file_index']) ? sanitize_text_field($_POST['file_index']) : '';
        $trackTitle = isset($_POST['track_title']) ? sanitize_text_field($_POST['track_title']) : '';
        
        if (!$productId) {
            wp_send_json_error('Invalid product ID');
        }
        
        Debug::log('PlaybackController: Play event received', [
            'productId' => $productId,
            'fileIndex' => $fileIndex,
            'trackTitle' => $trackTitle
        ]);
        
        // Track analytics
        $this->analytics->trackPlayEvent($productId, $fileIndex);
        
        // Allow other plugins to hook into play events
        do_action('bfp_play_event', $productId, $fileIndex, $trackTitle);
        
        wp_send_json_success([
            'message' => 'Play event tracked',
            'productId' => $productId,
            'fileIndex' => $fileIndex
        ]);
    }
    
    /**
     * Handle pause event via AJAX
     */
    public function handlePauseEvent(): void {
        // Verify nonce
        if (!check_ajax_referer('bfp_ajax_nonce', 'nonce', false)) {
            wp_die('Security check failed');
        }
        
        $productId = isset($_POST['product_id']) ? absint($_POST['product_id']) : 0;
        $fileIndex = isset($_POST['file_index']) ? sanitize_text_field($_POST['file_index']) : '';
        $trackTitle = isset($_POST['track_title']) ? sanitize_text_field($_POST['track_title']) : '';
        $currentTime = isset($_POST['current_time']) ? floatval($_POST['current_time']) : 0;
        
        Debug::log('PlaybackController: Pause event received', [
            'productId' => $productId,
            'fileIndex' => $fileIndex,
            'trackTitle' => $trackTitle,
            'currentTime' => $currentTime
        ]);
        
        // Allow other plugins to hook into pause events
        do_action('bfp_pause_event', $productId, $fileIndex, $trackTitle, $currentTime);
        
        wp_send_json_success([
            'message' => 'Pause event tracked',
            'productId' => $productId,
            'fileIndex' => $fileIndex
        ]);
    }
    
    /**
     * Handle track ended event via AJAX
     */
    public function handleEndedEvent(): void {
        // Verify nonce
        if (!check_ajax_referer('bfp_ajax_nonce', 'nonce', false)) {
            wp_die('Security check failed');
        }
        
        $productId = isset($_POST['product_id']) ? absint($_POST['product_id']) : 0;
        $fileIndex = isset($_POST['file_index']) ? sanitize_text_field($_POST['file_index']) : '';
        $trackTitle = isset($_POST['track_title']) ? sanitize_text_field($_POST['track_title']) : '';
        
        Debug::log('PlaybackController: Track ended event received', [
            'productId' => $productId,
            'fileIndex' => $fileIndex,
            'trackTitle' => $trackTitle
        ]);
        
        // Allow other plugins to hook into ended events
        do_action('bfp_ended_event', $productId, $fileIndex, $trackTitle);
        
        wp_send_json_success([
            'message' => 'Track ended event tracked',
            'productId' => $productId,
            'fileIndex' => $fileIndex
        ]);
    }
    
    /**
     * Get next track information via AJAX
     * This replaces the JavaScript _playNext logic
     */
    public function getNextTrack(): void {
        // Verify nonce
        if (!check_ajax_referer('bfp_ajax_nonce', 'nonce', false)) {
            wp_die('Security check failed');
        }
        
        $productId = isset($_POST['product_id']) ? absint($_POST['product_id']) : 0;
        $currentIndex = isset($_POST['current_index']) ? intval($_POST['current_index']) : 0;
        $loop = isset($_POST['loop']) ? filter_var($_POST['loop'], FILTER_VALIDATE_BOOLEAN) : false;
        
        if (!$productId) {
            wp_send_json_error('Invalid product ID');
        }
        
        // Get all files for the product
        $bootstrap = \Bandfront\Core\Bootstrap::getInstance();
        $fileManager = $bootstrap ? $bootstrap->getComponent('file_manager') : null;
        
        if (!$fileManager) {
            wp_send_json_error('File manager not available');
        }
        
        $files = $fileManager->getProductFiles($productId);
        
        if (empty($files)) {
            wp_send_json_error('No files found');
        }
        
        // Convert to indexed array
        $filesArray = array_values($files);
        $totalFiles = count($filesArray);
        
        // Calculate next index
        $nextIndex = $currentIndex + 1;
        
        if ($nextIndex >= $totalFiles) {
            if ($loop) {
                $nextIndex = 0;
            } else {
                wp_send_json_error('No more tracks');
            }
        }
        
        // Get next file info
        $nextFile = $filesArray[$nextIndex];
        
        Debug::log('PlaybackController: Next track determined', [
            'productId' => $productId,
            'currentIndex' => $currentIndex,
            'nextIndex' => $nextIndex,
            'nextFile' => $nextFile['name'] ?? 'Unknown'
        ]);
        
        wp_send_json_success([
            'nextIndex' => $nextIndex,
            'fileInfo' => [
                'name' => $nextFile['name'] ?? '',
                'file' => $nextFile['file'] ?? '',
                'media_type' => $nextFile['media_type'] ?? 'mp3'
            ]
        ]);
    }
    
    /**
     * Get the playback state for a product
     * Useful for resuming playback across page loads
     */
    public function getPlaybackState(int $productId): array {
        // This could be extended to store playback state in user meta
        // For now, return default state
        return [
            'currentIndex' => 0,
            'currentTime' => 0,
            'isPlaying' => false
        ];
    }
}

<?php
declare(strict_types=1);

namespace Bandfront\REST;

use Bandfront\Core\Config;  // Fix: was Bandfront\Config
use Bandfront\Audio\Audio;
use Bandfront\Storage\FileManager;
use Bandfront\Utils\Debug;

// Set domain for API
Debug::domain('api');

/**
 * REST API Streaming Controller
 * 
 * Handles audio file streaming via WordPress REST API
 * 
 * @package Bandfront\REST
 * @since 2.0.0
 */
class StreamController {
    
    private Config $config;
    private Audio $audio;
    private FileManager $fileManager;
    
    public function __construct(Config $config, Audio $audio, FileManager $fileManager) {
        $this->config = $config;
        $this->audio = $audio;
        $this->fileManager = $fileManager;
    }
    
    /**
     * Register REST routes
     */
    public function registerRoutes(): void {
        register_rest_route('bandfront-player/v1', '/stream/(?P<product_id>\d+)/(?P<track_index>\d+)', [
            'methods' => 'GET',
            'callback' => [$this, 'handleStreamRequest'],
            'permission_callback' => [$this, 'checkPermission'],
            'args' => [
                'product_id' => [
                    'validate_callback' => function($param) {
                        return is_numeric($param);
                    }
                ],
                'track_index' => [
                    'validate_callback' => function($param) {
                        return is_numeric($param);
                    }
                ]
            ]
        ]);
    }
    
    /**
     * Handle stream request
     */
    public function handleStreamRequest(\WP_REST_Request $request): \WP_REST_Response {
        $productId = (int) $request->get_param('product_id');
        $trackIndex = (int) $request->get_param('track_index');
        
        // Get file data
        $files = get_post_meta($productId, '_downloadable_files', true);
        if (empty($files) || !isset($files[$trackIndex])) {
            return new \WP_REST_Response(['error' => 'File not found'], 404);
        }
        
        $fileData = array_values($files)[$trackIndex];
        
        // Stream the file using Audio component
        $this->audio->outputFile([
            'url' => $fileData['file'],
            'product_id' => $productId,
            'secure_player' => $this->config->getState('_bfp_play_demos', false, $productId),
            'file_percent' => $this->config->getState('_bfp_demo_duration_percent', 30, $productId)
        ]);
        
        // This won't be reached if streaming succeeds
        return new \WP_REST_Response(['error' => 'Streaming failed'], 500);
    }
    
    /**
     * Check streaming permissions
     */
    public function checkPermission(\WP_REST_Request $request): bool {
        // Check if registered users only
        if ($this->config->getState('_bfp_require_login') && !is_user_logged_in()) {
            return false;
        }
        
        return true;
    }
}

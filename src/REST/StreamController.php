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
        Debug::log('StreamController::registerRoutes() called - registering file_id based route');
        
        $result = register_rest_route('bandfront-player/v1', '/stream/(?P<product_id>\d+)/(?P<file_id>[^/]+)', [
            'methods' => 'GET',
            'callback' => [$this, 'handleStreamRequest'],
            'permission_callback' => [$this, 'checkPermission'],
            'args' => [
                'product_id' => [
                    'validate_callback' => function($param) {
                        return is_numeric($param);
                    }
                ],
                'file_id' => [
                    'validate_callback' => function($param) {
                        return !empty($param);
                    }
                ]
            ]
        ]);
        
        Debug::log('Route registration result', ['success' => $result]);
    }
    
    /**
     * Handle stream request
     */
    public function handleStreamRequest(\WP_REST_Request $request): \WP_REST_Response {
        $productId = (int) $request->get_param('product_id');
        $fileId = $request->get_param('file_id');
        
        Debug::log('Stream request received', [
            'product_id' => $productId,
            'file_id' => $fileId
        ]);
        
        if (!$productId || !$fileId) {
            Debug::log('Invalid request parameters', [
                'product_id' => $productId,
                'file_id' => $fileId
            ]);
            return new \WP_REST_Response(['error' => 'Invalid parameters'], 400);
        }
        
        Debug::log('Fetching files linked to product ID', ['product_id' => $productId]);
        
        // Get file data from WooCommerce
        $files = get_post_meta($productId, '_downloadable_files', true);
        if (empty($files)) {
            Debug::log('No downloadable files found for product', ['product_id' => $productId]);
            return new \WP_REST_Response(['error' => 'No files found for product'], 404);
        }
        
        // Find the file by ID
        $fileData = null;
        
        Debug::log('Looking for file', [
            'file_id' => $fileId,
            'available_files' => array_keys($files)
        ]);
        
        foreach ($files as $key => $file) {
            // Check if this file matches our ID
            if ($key === $fileId) {
                $fileData = $file;
                Debug::log('Matched file', [
                    'key' => $key,
                    'file' => $file
                ]);
                break;
            }
        }
        
        if (!$fileData) {
            Debug::log('File not found', ['file_id' => $fileId, 'available_keys' => array_keys($files)]);
            return new \WP_REST_Response(['error' => 'File not found'], 404);
        }
        
        // Check if user has purchased the product (if demos are off)
        $demosEnabled = $this->config->getState('_bfp_play_demos', false, $productId);
        if (!$demosEnabled && !$this->userHasPurchased($productId)) {
            Debug::log('User has not purchased product and demos are disabled');
            return new \WP_REST_Response(['error' => 'Unauthorized'], 403);
        }
        
        // Get the file URL
        $fileUrl = $fileData['file'] ?? '';
        if (empty($fileUrl)) {
            Debug::log('File URL is empty');
            return new \WP_REST_Response(['error' => 'Invalid file URL'], 500);
        }
        
        Debug::log('Streaming file', [
            'url' => $fileUrl,
            'demos_enabled' => $demosEnabled
        ]);
        
        // If demos are off and user has purchased, serve the file properly
        if (!$demosEnabled) {
            // WooCommerce files are protected, we need to serve them properly
            Debug::log('Serving purchased file');
            
            // Check if it's a local file in WooCommerce uploads
            $upload_dir = wp_upload_dir();
            $wc_upload_dir = $upload_dir['basedir'] . '/woocommerce_uploads/';
            
            // Convert URL to local path
            $local_path = str_replace($upload_dir['baseurl'], $upload_dir['basedir'], $fileUrl);
            
            // If it's in WooCommerce uploads, we need to serve it directly
            if (strpos($local_path, $wc_upload_dir) === 0 && file_exists($local_path)) {
                Debug::log('Streaming WooCommerce protected file', ['path' => $local_path]);
                
                // Use FileManager to stream the file with proper headers
                $this->fileManager->streamFile($local_path);
                exit;
            }
            
            // For non-protected files, redirect is fine
            Debug::log('Redirecting to non-protected file', ['url' => $fileUrl]);
            wp_redirect($fileUrl);
            exit;
        }
        
        // If demos are on, use Audio component for demo streaming
        $this->audio->outputFile([
            'url' => $fileUrl,
            'product_id' => $productId,
            'secure_player' => true,
            'file_percent' => $this->config->getState('_bfp_demo_duration_percent', 30, $productId)
        ]);
        
        // This won't be reached if streaming succeeds
        return new \WP_REST_Response(['error' => 'Streaming failed'], 500);
    }
    
    /**
     * Check streaming permissions
     */
    public function checkPermission(\WP_REST_Request $request): bool {
        $requireLogin = $this->config->getState('_bfp_require_login', false);
        $isLoggedIn = is_user_logged_in();
        
        Debug::log('Permission check', [
            'require_login' => $requireLogin,
            'is_logged_in' => $isLoggedIn,
            'user_id' => get_current_user_id()
        ]);
        
        // Check if registered users only
        if ($requireLogin && !$isLoggedIn) {
            Debug::log('Permission denied - login required');
            return false;
        }
        
        Debug::log('Permission granted');
        return true;
    }
    
    /**
     * Check if user has purchased the product
     */
    private function userHasPurchased(int $productId): bool {
        if (!is_user_logged_in()) {
            return false;
        }
        
        $currentUser = wp_get_current_user();
        return wc_customer_bought_product($currentUser->user_email, $currentUser->ID, $productId);
    }
}

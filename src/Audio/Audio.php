<?php
declare(strict_types=1);

namespace Bandfront\Audio;

use Bandfront\Core\Config;
use Bandfront\Storage\FileManager;
use Bandfront\Utils\Debug;

// Set domain for Audio
Debug::domain('audio');

/**
 * Main Audio Coordinator
 * 
 * Coordinates audio operations and provides unified interface
 * 
 * @package Bandfront\Audio
 * @since 2.0.0
 */
class Audio {
    
    private Config $config;
    private FileManager $fileManager;
    private DemoCreator $demoCreator;
    
    /**
     * Constructor
     */
    public function __construct(Config $config, FileManager $fileManager, DemoCreator $demoCreator) {
        $this->config = $config;
        $this->fileManager = $fileManager;
        $this->demoCreator = $demoCreator;
    }
    
    /**
     * Generate secure audio URL using REST API
     * 
     * @param int $productId Product ID
     * @param string|int $fileIndex File index (GUID or numeric)
     * @param array $fileData Optional file data
     * @return string Audio URL
     */
    public function generateAudioUrl(int $productId, string|int $fileIndex, array $fileData = []): string {
        Debug::log('Audio: generateAudioUrl', [
            'productId' => $productId, 
            'fileIndex' => $fileIndex,
            'fileData' => $fileData
        ]); // DEBUG-REMOVE
        
        // Check if user owns the product
        $purchased = $this->checkPurchaseStatus($productId);
        
        // Check audio engine setting
        $audioEngine = $this->config->getState('_bfp_audio_engine', 'html5', $productId);
        
        if ($purchased && !empty($fileData['file'])) {
            // For HTML5 engine with purchased products, prefer direct URLs
            if ($audioEngine === 'html5') {
                // Try to get direct URL to pre-generated file
                $preGeneratedUrl = $this->getPreGeneratedFileUrl($productId, $fileData['file']);
                if ($preGeneratedUrl) {
                    Debug::log('Audio: using pre-generated URL for HTML5', ["url" => $preGeneratedUrl]); // DEBUG-REMOVE
                    return $preGeneratedUrl;
                }
                
                // If no pre-generated file, return original URL for HTML5
                // Trust WooCommerce URLs - they should be valid
                Debug::log('Audio: using original URL for HTML5', ["url" => $fileData["file"]]); // DEBUG-REMOVE
                return $fileData['file'];
            }
        }
        
        // Direct play sources bypass streaming
        if (!empty($fileData['play_src']) || 
            (!empty($fileData['file']) && $this->fileManager->isPlaylist($fileData['file']))) {
            Debug::log('Audio: direct play source', ["file" => $fileData["file"]]); // DEBUG-REMOVE
            return $fileData['file'];
        }
        
        // Use REST API endpoint with nonce for authentication
        $url = rest_url("bandfront-player/v1/stream/{$productId}/{$fileIndex}");
        
        // Add nonce for authentication
        $nonce = wp_create_nonce('wp_rest');
        $url = add_query_arg('_wpnonce', $nonce, $url);
        
        Debug::log('Audio: REST API endpoint', [
            'url' => $url,
            'file_index' => $fileIndex,
            'has_nonce' => true
        ]); // DEBUG-REMOVE
        return $url;
    }
    
    /**
     * Get duration by URL
     */
    public function getDurationByUrl(string $url): int {
        // Try to get cached duration first
        $cache_key = 'bfp_duration_' . md5($url);
        $cached = get_transient($cache_key);
        if ($cached !== false) {
            return (int) $cached;
        }
        
        // Get duration using processor
        $duration = 0;
        
        // Check if it's a local file
        $upload_dir = wp_upload_dir();
        $local_path = str_replace($upload_dir['baseurl'], $upload_dir['basedir'], $url);
        
        if (file_exists($local_path)) {
            // Delegate to processor
            $processor = new Processor($this->config, $this->fileManager);
            $duration = $processor->getAudioDuration($local_path);
        }
        
        // Cache the duration
        set_transient($cache_key, $duration, DAY_IN_SECONDS);
        
        return $duration;
    }
    
    /**
     * Get smart preload value based on context
     * 
     * @param bool $singlePlayer Whether single player mode is active
     * @param bool $showDuration Whether duration needs to be shown
     * @return string Preload value: 'none' or 'metadata'
     */
    public function getSmartPreload(bool $singlePlayer = false, bool $showDuration = true): string {
        // If single player mode, we need metadata for playlist functionality
        if ($singlePlayer) {
            return 'metadata';
        }
        
        // If we need to show duration, preload metadata
        if ($showDuration) {
            return 'metadata';
        }
        
        // Default to none for performance
        return 'none';
    }
    
    /**
     * Check purchase status for a product
     */
    private function checkPurchaseStatus(int $productId): bool {
        // Get WooCommerce integration via Bootstrap
        $bootstrap = \Bandfront\Core\Bootstrap::getInstance();
        $woocommerce = $bootstrap ? $bootstrap->getComponent('woocommerce') : null;
        
        if (!$woocommerce) {
            return false;
        }
        
        return (bool) $woocommerce->isUserProduct($productId);
    }
    
    /**
     * Get URL to pre-generated file
     */
    private function getPreGeneratedFileUrl(int $productId, string $originalUrl): ?string {
        $uploadDir = wp_upload_dir();
        $filename = pathinfo(basename($originalUrl), PATHINFO_FILENAME);
        
        // Clean the filename to match what ProductProcessor generates
        $cleanName = $this->cleanFilenameForMatching($filename);
        
        // Default to MP3 for streaming
        $mp3Url = $uploadDir['baseurl'] . '/woocommerce_uploads/bfp-formats/' . $productId . '/mp3/' . $cleanName . '.mp3';
        
        // Check if file exists by trying to access the path
        $mp3Path = $uploadDir['basedir'] . '/woocommerce_uploads/bfp-formats/' . $productId . '/mp3/' . $cleanName . '.mp3';
        if (file_exists($mp3Path)) {
            Debug::log('Audio: found pre-generated MP3', ['url' => $mp3Url]); // DEBUG-REMOVE
            return $mp3Url;
        }
        
        Debug::log('Audio: pre-generated file not found', ['tried' => $mp3Path]); // DEBUG-REMOVE
        return null;
    }
    
    /**
     * Clean filename for matching pre-generated files
     */
    private function cleanFilenameForMatching(string $filename): string {
        // Remove common suffixes that ProductProcessor removes
        $filename = preg_replace('/-[a-z0-9]{6,}$/i', '', $filename);
        $filename = preg_replace('/--+/', '-', $filename);
        $filename = trim($filename, '-_ ');
        
        // Add track number if present in original
        if (preg_match('/^(\d+)/', $filename, $matches)) {
            // Already has track number, use as-is
            return sanitize_file_name($filename);
        }
        
        // Note: ProductProcessor adds track numbers, but we can't know which
        // This is a limitation - we might need to scan the directory
        return sanitize_file_name($filename);
    }
    
    /**
     * Output file for streaming
     * Used by REST API endpoint for demo streaming
     */
    public function outputFile(array $args): void {
        $url = $args['url'] ?? '';
        $productId = $args['product_id'] ?? 0;
        $securPlayer = $args['secure_player'] ?? false;
        $filePercent = $args['file_percent'] ?? 100;
        
        Debug::log('Audio::outputFile called', $args);
        
        if (empty($url)) {
            Debug::log('Audio::outputFile - empty URL');
            status_header(404);
            exit;
        }
        
        // For demos, we need to use DemoCreator to create/stream truncated file
        if ($securPlayer && $filePercent < 100) {
            $demoFile = $this->demoCreator->getDemoFile($url, $filePercent);
            if ($demoFile && file_exists($demoFile)) {
                $this->fileManager->streamFile($demoFile);
                exit;
            }
        }
        
        // For full files, just redirect
        wp_redirect($url);
        exit;
    }
}

<?php
declare(strict_types=1);

namespace Bandfront\Audio;

use Bandfront\Core\Config;
use Bandfront\Storage\FileManager;
use Bandfront\Utils\Debug;

// Set domain for Streamer
Debug::domain('Audio');

/**
 * Audio Streaming Handler
 * 
 * Handles secure audio file streaming with proper headers
 * and byte-range support
 * 
 * @package Bandfront\Audio
 * @since 2.0.0
 */
class Streamer {
    
    private Config $config;
    private FileManager $fileManager;
    
    /**
     * Constructor
     */
    public function __construct(Config $config, FileManager $fileManager) {
        $this->config = $config;
        $this->fileManager = $fileManager;
    }
    
    /**
     * Stream audio file with proper headers and processing
     * 
     * @param array $args Streaming arguments
     * @return void Outputs file content or error
     */
    public function outputFile(array $args): void {
        Debug::log('Streamer: outputFile started', $args); // DEBUG-REMOVE
        
        if (empty($args['url'])) {
            Debug::log('Streamer: Empty file URL'); // DEBUG-REMOVE
            $this->sendError(__('Empty file URL', 'bandfront-player'));
            return;
        }

        $url = do_shortcode($args['url']);
        $urlFixed = $this->fileManager->fixUrl($url);
        
        Debug::log('Streamer: URLs', ['original' => $url, 'fixed' => $urlFixed]); // DEBUG-REMOVE
        
        // Fire play event
        do_action('bfp_play_file', $args['product_id'], $url);

        // Generate file paths
        $fileInfo = $this->generateFilePaths($args);
        Debug::log('Streamer: fileInfo generated', $fileInfo); // DEBUG-REMOVE
        
        // If pre-generated file exists, stream it directly
        if (!empty($fileInfo['preGenerated']) && file_exists($fileInfo['filePath'])) {
            Debug::log('Streamer: streaming pre-generated file', $fileInfo['filePath']); // DEBUG-REMOVE
            $this->streamFile($fileInfo['filePath'], $fileInfo['fileName']);
            return;
        }
        
        // Check cache first
        $cachedPath = $this->getCachedFilePath($fileInfo['fileName']);
        if ($cachedPath) {
            Debug::log('Streamer: using cached file', $cachedPath); // DEBUG-REMOVE
            $this->streamFile($cachedPath, $fileInfo['fileName']);
            return;
        }

        // Create demo file
        if (!$this->fileManager->createDemoFile($urlFixed, $fileInfo['filePath'])) {
            Debug::log('Streamer: Failed to create demo file', $fileInfo['filePath']); // DEBUG-REMOVE
            $this->sendError(__('Failed to generate demo file', 'bandfront-player'));
            return;
        }

        Debug::log('Streamer: demo file created', $fileInfo['filePath']); // DEBUG-REMOVE

        // Process secure audio if needed - delegate to processor
        if ($this->shouldProcessSecure($args, $fileInfo['purchased'])) {
            Debug::log('Streamer: needs secure processing', ['file_percent' => $args['file_percent']]); // DEBUG-REMOVE
            // Note: This would be handled by Processor class
            $this->processSecureStub($fileInfo, $args);
        }

        // Cache the file path
        $this->cacheFilePath($fileInfo['fileName'], $fileInfo['filePath']);
        
        // Stream the file
        Debug::log('Streamer: streaming file', $fileInfo['filePath']); // DEBUG-REMOVE
        $this->streamFile($fileInfo['filePath'], $fileInfo['fileName']);
    }
    
    /**
     * Generate file paths based on product and purchase status
     * 
     * @param array $args Request arguments
     * @return array File path information
     */
    private function generateFilePaths(array $args): array {
        $originalUrl = $args['url'];
        $productId = $args['product_id'];
        
        $purchased = $this->checkPurchaseStatus($productId);
        
        Debug::log('Streamer: purchase status', ['product_id' => $productId, 'purchased' => $purchased]); // DEBUG-REMOVE
        
        // If user owns the product, check for pre-generated files
        if ($purchased) {
            $preGeneratedPath = $this->getPreGeneratedFilePath($productId, $originalUrl);
            if ($preGeneratedPath) {
                Debug::log('Streamer: using pre-generated file', $preGeneratedPath); // DEBUG-REMOVE
                return [
                    'fileName' => basename($preGeneratedPath),
                    'filePath' => $preGeneratedPath,
                    'oFilePath' => $preGeneratedPath . '.tmp',
                    'oFileName' => basename($preGeneratedPath) . '.tmp',
                    'purchased' => $purchased,
                    'preGenerated' => true,
                    'original_url' => $originalUrl
                ];
            }
        }
        
        // Fall back to demo generation for non-purchased users
        $fileName = $this->fileManager->generateDemoFileName($originalUrl);
        $basePath = $this->fileManager->getFilesDirectoryPath();
        $oFileName = 'o_' . $fileName;
        
        return [
            'fileName' => $fileName,
            'filePath' => $basePath . $fileName,
            'oFilePath' => $basePath . $oFileName,
            'oFileName' => $oFileName,
            'purchased' => $purchased,
            'preGenerated' => false,
            'original_url' => $originalUrl
        ];
    }
    
    /**
     * Stream file with proper headers
     * 
     * @param string $filePath File path
     * @param string $fileName File name
     * @return void
     */
    private function streamFile(string $filePath, string $fileName): void {
        if (!file_exists($filePath)) {
            Debug::log('Streamer: File not found', $filePath); // DEBUG-REMOVE
            $this->sendError(__('File not found', 'bandfront-player'));
            return;
        }
        
        $mimeType = $this->fileManager->getMimeType($filePath);
        $fileSize = filesize($filePath);
        
        Debug::log('Streamer: starting stream', [
            'filePath' => $filePath,
            'fileName' => $fileName,
            'mimeType' => $mimeType,
            'fileSize' => $fileSize
        ]); // DEBUG-REMOVE
        
        // Send headers
        header("Content-Type: " . $mimeType);
        header("Content-Length: " . $fileSize);
        header('Content-Disposition: filename="' . basename($fileName) . '"');
        header("Accept-Ranges: " . (stripos($mimeType, 'wav') ? 'none' : 'bytes'));
        header("Content-Transfer-Encoding: binary");
        
        // Output file
        readfile($filePath);
        exit;
    }
    
    /**
     * Send error response
     * 
     * @param string $message Error message
     * @return void
     */
    private function sendError(string $message): void {
        status_header(404);
        wp_die(
            esc_html($message),
            esc_html__('Not Found', 'bandfront-player'),
            ['response' => 404]
        );
    }
    
    /**
     * Check if secure processing is needed
     * 
     * @param array $args Request arguments
     * @param mixed $purchased Purchase status
     * @return bool
     */
    private function shouldProcessSecure(array $args, $purchased): bool {
        return !empty($args['secure_player']) && 
               !empty($args['file_percent']) && 
               0 !== intval($args['file_percent']) && 
               false === $purchased;
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
     * Get pre-generated file path if available
     */
    private function getPreGeneratedFilePath(int $productId, string $originalUrl): ?string {
        $uploadDir = wp_upload_dir();
        $wooDir = $uploadDir['basedir'] . '/woocommerce_uploads';
        $formatDir = $wooDir . '/bfp-formats/' . $productId;
        
        // Extract filename without extension
        $filename = pathinfo(basename($originalUrl), PATHINFO_FILENAME);
        $cleanName = $this->cleanFilenameForMatching($filename);
        
        Debug::log('Streamer: searching pre-generated', [
            'original' => $filename,
            'clean' => $cleanName,
            'formatDir' => $formatDir
        ]); // DEBUG-REMOVE
        
        // Check for MP3 format (default streaming format)
        $patterns = [
            $formatDir . '/mp3/' . $cleanName . '.mp3',
            $formatDir . '/mp3/*' . $cleanName . '.mp3',
            $formatDir . '/mp3/*-' . $cleanName . '.mp3'
        ];
        
        foreach ($patterns as $pattern) {
            $matches = glob($pattern);
            if (!empty($matches)) {
                Debug::log('Streamer: found pre-generated', $matches[0]); // DEBUG-REMOVE
                return $matches[0];
            }
        }
        
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
        
        return sanitize_file_name($filename);
    }
    
    /**
     * Cache file path using transients
     */
    private function cacheFilePath(string $fileName, string $filePath): void {
        $cacheKey = 'bfp_file_' . md5($fileName);
        set_transient($cacheKey, $filePath, HOUR_IN_SECONDS);
    }
    
    /**
     * Get cached file path
     */
    private function getCachedFilePath(string $fileName): string|false {
        $cacheKey = 'bfp_file_' . md5($fileName);
        $cached = get_transient($cacheKey);
        
        if ($cached && file_exists($cached)) {
            Debug::log('Streamer: cache hit', ['fileName' => $fileName, 'cachedPath' => $cached]); // DEBUG-REMOVE
            return $cached;
        }
        
        Debug::log('Streamer: cache miss', $fileName); // DEBUG-REMOVE
        return false;
    }
    
    /**
     * Temporary stub for secure processing
     * This should be handled by Processor class
     */
    private function processSecureStub(array &$fileInfo, array $args): void {
        // This is a temporary stub - actual processing should be in Processor class
        do_action('bfp_truncated_file', $args['product_id'], $args['url'], $fileInfo['filePath']);
    }
}

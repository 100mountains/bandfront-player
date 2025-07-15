<?php
namespace bfp;

/**
 * Audio Processing and Streaming
 * 
 * Handles audio file processing, streaming, and manipulation
 * using WordPress-native functions and caching mechanisms
 * 
 * @package BandfrontPlayer
 * @since 0.1
 */

if (!defined('ABSPATH')) {
    exit;
}

class Audio {
    
    private Plugin $mainPlugin;
    private int $preloadTimes = 0;
    
    /**
     * Initialize audio processor
     * 
     * @param Plugin $mainPlugin Main plugin instance
     */
    public function __construct(Plugin $mainPlugin) {
        $this->mainPlugin = $mainPlugin;
    }
    
    /**
     * Stream audio file with proper headers and processing
     * 
     * @param array $args Streaming arguments
     * @return void Outputs file content or error
     */
    public function outputFile(array $args): void {
        if (empty($args['url'])) {
            $this->sendError(__('Empty file URL', 'bandfront-player'));
            return;
        }

        $url = do_shortcode($args['url']);
        $urlFixed = $this->mainPlugin->getFiles()->fixUrl($url);
        
        // Fire play event
        do_action('bfp_play_file', $args['product_id'], $url);

        // Generate file paths
        $fileInfo = $this->generateFilePaths($args);
        
        // Check cache first
        $cachedPath = $this->getCachedFilePath($fileInfo['fileName']);
        if ($cachedPath) {
            $this->streamFile($cachedPath, $fileInfo['fileName']);
            return;
        }

        // Create demo file
        if (!$this->mainPlugin->getFiles()->createDemoFile($urlFixed, $fileInfo['filePath'])) {
            $this->sendError(__('Failed to generate demo file', 'bandfront-player'));
            return;
        }

        // Process secure audio if needed
        if ($this->shouldProcessSecure($args, $fileInfo['purchased'])) {
            $this->processSecureAudio($fileInfo, $args);
        }

        // Cache the file path
        $this->cacheFilePath($fileInfo['fileName'], $fileInfo['filePath']);
        
        // Stream the file
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
        $fileName = $this->mainPlugin->getFiles()->generateDemoFileName($originalUrl);
        $oFileName = 'o_' . $fileName;
        
        $purchased = $this->mainPlugin->getWooCommerce()?->woocommerceUserProduct($args['product_id']) ?? false;
        
        if (false !== $purchased) {
            $oFileName = 'purchased/o_' . $purchased . $fileName;
            $fileName = 'purchased/' . $purchased . '_' . $fileName;
        }

        return [
            'fileName' => $fileName,
            'oFileName' => $oFileName,
            'filePath' => $this->mainPlugin->getFileHandler()->getFilesDirectoryPath() . $fileName,
            'oFilePath' => $this->mainPlugin->getFileHandler()->getFilesDirectoryPath() . $oFileName,
            'purchased' => $purchased
        ];
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
     * Process secure audio with truncation
     * 
     * @param array $fileInfo File information
     * @param array $args Request arguments
     * @return void
     */
    private function processSecureAudio(array &$fileInfo, array $args): void {
        $filePercent = intval($args['file_percent']);
        $ffmpeg = $this->mainPlugin->getConfig()->getState('_bfp_ffmpeg');
        
        $processed = false;
        
        // Try FFmpeg first if available
        if ($ffmpeg && function_exists('shell_exec')) {
            $processed = $this->processWithFfmpeg($fileInfo['filePath'], $fileInfo['oFilePath'], $filePercent);
        }
        
        // Fall back to PHP processing
        if (!$processed) {
            $processed = $this->processWithPhp($fileInfo['filePath'], $fileInfo['oFilePath'], $filePercent);
        }
        
        // Swap files if processing succeeded
        if ($processed && file_exists($fileInfo['oFilePath'])) {
            $this->swapProcessedFile($fileInfo);
        }
        
        do_action('bfp_truncated_file', $args['product_id'], $args['url'], $fileInfo['filePath']);
    }
    
    /**
     * Process audio file with FFmpeg
     * 
     * @param string $inputPath Input file path
     * @param string $outputPath Output file path
     * @param int $filePercent Percentage to keep
     * @return bool Success status
     */
    private function processWithFfmpeg(string $inputPath, string $outputPath, int $filePercent): bool {
        $settings = $this->mainPlugin->getConfig()->getStates([
            '_bfp_ffmpeg_path',
            '_bfp_ffmpeg_watermark'
        ]);
        
        $ffmpegPath = $this->prepareFfmpegPath($settings['_bfp_ffmpeg_path']);
        if (!$ffmpegPath) {
            return false;
        }

        // Get duration
        $duration = $this->getFfmpegDuration($ffmpegPath, $inputPath);
        if (!$duration) {
            return false;
        }

        $targetDuration = apply_filters('bfp_ffmpeg_time', floor($duration * $filePercent / 100));
        
        // Build command
        $command = $this->buildFfmpegCommand($ffmpegPath, $inputPath, $outputPath, $targetDuration, $settings['_bfp_ffmpeg_watermark']);
        
        // Execute
        @shell_exec($command);
        
        return file_exists($outputPath);
    }
    
    /**
     * Process audio with PHP fallback
     * 
     * @param string $inputPath Input file path
     * @param string $outputPath Output file path
     * @param int $filePercent Percentage to keep
     * @return bool Success status
     */
    private function processWithPhp(string $inputPath, string $outputPath, int $filePercent): bool {
        try {
            require_once dirname(dirname(__FILE__)) . '/vendor/php-mp3/class.mp3.php';
            $mp3 = new \BFPMP3();
            $mp3->cut_mp3($inputPath, $outputPath, 0, $filePercent/100, 'percent', false);
            unset($mp3);
            return file_exists($outputPath);
        } catch (\Exception | \Error $e) {
            error_log('BFP MP3 processing error: ' . $e->getMessage());
            // Final fallback - simple truncate
            $this->mainPlugin->getFiles()->truncateFile($inputPath, $filePercent);
            return false;
        }
    }
    
    /**
     * Get duration using WordPress metadata or FFmpeg
     * 
     * @param string $url Audio file URL
     * @return string|false Duration or false
     */
    public function getDurationByUrl(string $url): string|false {
        // Check transient cache first
        $cacheKey = 'bfp_duration_' . md5($url);
        $cached = get_transient($cacheKey);
        if (false !== $cached) {
            return $cached;
        }
        
        // Try WordPress attachment metadata
        $attachmentId = $this->getAttachmentIdByUrl($url);
        if ($attachmentId) {
            $metadata = wp_get_attachment_metadata($attachmentId);
            if (!empty($metadata['length_formatted'])) {
                set_transient($cacheKey, $metadata['length_formatted'], DAY_IN_SECONDS);
                return $metadata['length_formatted'];
            }
        }
        
        return false;
    }
    
    /**
     * Generate secure audio URL using REST API
     * 
     * @param int $productId Product ID
     * @param string|int $fileIndex File index
     * @param array $fileData Optional file data
     * @return string Audio URL
     */
    public function generateAudioUrl(int $productId, string|int $fileIndex, array $fileData = []): string {
        // Direct play sources bypass streaming
        if (!empty($fileData['play_src']) || 
            (!empty($fileData['file']) && $this->mainPlugin->getFiles()->isPlaylist($fileData['file']))) {
            return $fileData['file'];
        }
        
        // Legacy Google Drive support
        if (!empty($fileData['file'])) {
            $files = get_post_meta($productId, '_bfp_drive_files', true);
            if (!empty($files)) {
                $key = md5($fileData['file']);
                if (isset($files[$key]['url'])) {
                    return $files[$key]['url'];
                }
            }
        }
        
        // Use REST API endpoint
        return rest_url("bandfront-player/v1/stream/{$productId}/{$fileIndex}");
    }
    
    /**
     * Track play event for analytics
     * 
     * @param int $productId Product ID
     * @param string $fileUrl File URL
     * @return void
     */
    public function trackingPlayEvent(int $productId, string $fileUrl): void {
        $settings = $this->mainPlugin->getConfig()->getStates([
            '_bfp_analytics_integration',
            '_bfp_analytics_property',
            '_bfp_analytics_api_secret'
        ]);
        
        if (empty($settings['_bfp_analytics_property'])) {
            return;
        }
        
        $clientId = $this->getClientId();
        $endpoint = $this->getAnalyticsEndpoint($settings);
        $body = $this->buildAnalyticsPayload($settings, $clientId, $productId, $fileUrl);
        
        $response = wp_remote_post($endpoint, $body);
        
        if (is_wp_error($response)) {
            error_log('BFP Analytics error: ' . $response->get_error_message());
        }
    }
    
    /**
     * Handle preload attribute for streaming URLs
     * 
     * @param string $preload Original preload value
     * @param string $audioUrl Audio URL
     * @return string Modified preload value
     */
    public function preload(string $preload, string $audioUrl): string {
        if (strpos($audioUrl, 'bfp-action=play') !== false && $this->preloadTimes > 0) {
            return 'none';
        }
        
        if (strpos($audioUrl, 'bfp-action=play') !== false) {
            $this->preloadTimes++;
        }
        
        return $preload;
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
            $this->sendError(__('File not found', 'bandfront-player'));
            return;
        }
        
        $mimeType = $this->mainPlugin->getFiles()->getMimeType($filePath);
        $fileSize = filesize($filePath);
        
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
     * Cache file path using transients
     * 
     * @param string $fileName File name
     * @param string $filePath File path
     * @return void
     */
    private function cacheFilePath(string $fileName, string $filePath): void {
        $cacheKey = 'bfp_file_' . md5($fileName);
        set_transient($cacheKey, $filePath, HOUR_IN_SECONDS);
    }
    
    /**
     * Get cached file path
     * 
     * @param string $fileName File name
     * @return string|false Cached path or false
     */
    private function getCachedFilePath(string $fileName): string|false {
        $cacheKey = 'bfp_file_' . md5($fileName);
        $cached = get_transient($cacheKey);
        
        if ($cached && file_exists($cached)) {
            return $cached;
        }
        
        return false;
    }
    
    /**
     * Get attachment ID by URL
     * 
     * @param string $url File URL
     * @return int|false Attachment ID or false
     */
    private function getAttachmentIdByUrl(string $url): int|false {
        global $wpdb;
        
        // Try direct GUID match
        $id = $wpdb->get_var($wpdb->prepare(
            "SELECT ID FROM $wpdb->posts WHERE guid = %s AND post_type = 'attachment'",
            $url
        ));
        
        if ($id) {
            return (int) $id;
        }
        
        // Try by file path
        $uploadsDir = wp_upload_dir();
        if (strpos($url, $uploadsDir['baseurl']) === 0) {
            $file = str_replace($uploadsDir['baseurl'] . '/', '', $url);
            
            $id = $wpdb->get_var($wpdb->prepare(
                "SELECT post_id FROM $wpdb->postmeta 
                WHERE meta_key = '_wp_attached_file' AND meta_value = %s",
                $file
            ));
            
            if ($id) {
                return (int) $id;
            }
        }
        
        return false;
    }
    
    /**
     * Prepare FFmpeg path
     * 
     * @param string $path FFmpeg path setting
     * @return string|false Prepared path or false
     */
    private function prepareFfmpegPath(string $path): string|false {
        if (empty($path)) {
            return false;
        }
        
        $path = rtrim($path, '/');
        if (is_dir($path)) {
            $path .= '/ffmpeg';
        }
        
        return file_exists($path) ? $path : false;
    }
    
    /**
     * Get duration from FFmpeg
     * 
     * @param string $ffmpegPath FFmpeg executable path
     * @param string $inputPath Input file path
     * @return int|false Duration in seconds or false
     */
    private function getFfmpegDuration(string $ffmpegPath, string $inputPath): int|false {
        $command = sprintf('"%s" -i %s 2>&1', $ffmpegPath, escapeshellarg($inputPath));
        $output = @shell_exec($command);
        
        if (!$output) {
            return false;
        }
        
        if (preg_match('/Duration: (\d{2}):(\d{2}):(\d{2})/', $output, $matches)) {
            return ($matches[1] * 3600) + ($matches[2] * 60) + $matches[3];
        }
        
        return false;
    }
    
    /**
     * Build FFmpeg command
     * 
     * @param string $ffmpegPath FFmpeg path
     * @param string $inputPath Input file
     * @param string $outputPath Output file
     * @param int $duration Target duration
     * @param string $watermark Watermark URL
     * @return string Command
     */
    private function buildFfmpegCommand(string $ffmpegPath, string $inputPath, string $outputPath, int $duration, string $watermark = ''): string {
        $command = sprintf(
            '"%s" -hide_banner -loglevel panic -vn -i %s',
            $ffmpegPath,
            escapeshellarg($inputPath)
        );
        
        // Add watermark if available
        if (!empty($watermark)) {
            $watermarkPath = $this->mainPlugin->getFiles()->isLocal($watermark);
            if ($watermarkPath) {
                $watermarkPath = str_replace(['\\', ':', '.'], ['/', '\:', '\.'], $watermarkPath);
                $fadeStart = max(0, $duration - 2);
                $command .= sprintf(
                    ' -filter_complex "amovie=%s:loop=0,volume=0.3[s];[0][s]amix=duration=first,afade=t=out:st=%d:d=2"',
                    escapeshellarg($watermarkPath),
                    $fadeStart
                );
            }
        }
        
        $command .= sprintf(' -map 0:a -t %d -y %s', $duration, escapeshellarg($outputPath));
        
        return $command;
    }
    
    /**
     * Swap processed file with original
     * 
     * @param array $fileInfo File information array
     * @return void
     */
    private function swapProcessedFile(array &$fileInfo): void {
        if (@unlink($fileInfo['filePath'])) {
            if (@rename($fileInfo['oFilePath'], $fileInfo['filePath'])) {
                return;
            }
        }
        
        // If swap failed, use processed file directly
        $fileInfo['filePath'] = $fileInfo['oFilePath'];
        $fileInfo['fileName'] = $fileInfo['oFileName'];
    }
    
    /**
     * Get client ID for analytics
     * 
     * @return string Client ID
     */
    private function getClientId(): string {
        // Try Google Analytics cookie first
        if (isset($_COOKIE['_ga'])) {
            $parts = explode('.', sanitize_text_field(wp_unslash($_COOKIE['_ga'])), 3);
            if (isset($parts[2])) {
                return $parts[2];
            }
        }
        
        // Fall back to IP address
        return sanitize_text_field($_SERVER['REMOTE_ADDR'] ?? '');
    }
    
    /**
     * Get analytics endpoint URL
     * 
     * @param array $settings Analytics settings
     * @return string Endpoint URL
     */
    private function getAnalyticsEndpoint(array $settings): string {
        if ($settings['_bfp_analytics_integration'] === 'ua') {
            return 'http://www.google-analytics.com/collect';
        }
        
        return sprintf(
            'https://www.google-analytics.com/mp/collect?api_secret=%s&measurement_id=%s',
            $settings['_bfp_analytics_api_secret'],
            $settings['_bfp_analytics_property']
        );
    }
    
    /**
     * Build analytics payload
     * 
     * @param array $settings Analytics settings
     * @param string $clientId Client ID
     * @param int $productId Product ID
     * @param string $fileUrl File URL
     * @return array Request arguments
     */
    private function buildAnalyticsPayload(array $settings, string $clientId, int $productId, string $fileUrl): array {
        if ($settings['_bfp_analytics_integration'] === 'ua') {
            return [
                'body' => [
                    'v' => 1,
                    'tid' => $settings['_bfp_analytics_property'],
                    'cid' => $clientId,
                    't' => 'event',
                    'ec' => 'Music Player for WooCommerce',
                    'ea' => 'play',
                    'el' => $fileUrl,
                    'ev' => $productId,
                ],
            ];
        }
        
        return [
            'sslverify' => true,
            'headers' => ['Content-Type' => 'application/json'],
            'body' => wp_json_encode([
                'client_id' => $clientId,
                'events' => [
                    [
                        'name' => 'play',
                        'params' => [
                            'event_category' => 'Music Player for WooCommerce',
                            'event_label' => $fileUrl,
                            'event_value' => $productId,
                        ],
                    ],
                ],
            ]),
        ];
    }
}
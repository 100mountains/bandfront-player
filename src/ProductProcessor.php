<?php
namespace bfp;

/**
 * Product Audio Processing
 * 
 * Automatically generates multiple audio formats when products are saved
 * 
 * @package BandfrontPlayer
 * @since 0.1
 */

if (!defined('ABSPATH')) {
    exit;
}

class ProductProcessor {
    
    private Plugin $mainPlugin;
    private bool $debugMode = true; // Enable detailed logging
    
    public function __construct(Plugin $mainPlugin) {
        $this->mainPlugin = $mainPlugin;
        // Remove logging from constructor - causes activation errors
        
        // Hook into WooCommerce product save events
        add_action('woocommerce_process_product_meta', [$this, 'processProductAudio'], 20);
        add_action('woocommerce_update_product', [$this, 'processProductAudio'], 20);
        add_action('woocommerce_new_product', [$this, 'processProductAudio'], 20);
        
        // ADDED: Debug hook registration
        add_action('save_post_product', [$this, 'debugSavePost'], 10, 3);
        
        // Add admin notice for processing status
        add_action('admin_notices', [$this, 'showProcessingNotices']);
    }
    
    /**
     * Debug save_post hook
     */
    public function debugSavePost($post_id, $post, $update): void {
        $this->log('=== DEBUG SAVE POST ===', 'info', [
            'post_id' => $post_id,
            'post_type' => $post->post_type,
            'update' => $update,
            'action' => current_action(),
            'doing_autosave' => defined('DOING_AUTOSAVE') && DOING_AUTOSAVE,
            'doing_ajax' => defined('DOING_AJAX') && DOING_AJAX,
        ]);
    }
    
    /**
     * Process audio files when product is saved/updated
     */
    public function processProductAudio(int $productId): void {
        $this->log('=== PRODUCT AUDIO PROCESSING STARTED ===', 'info', ['productId' => $productId]);
        
        $product = wc_get_product($productId);
        
        if (!$product || !$product->is_downloadable()) {
            $this->log('Skipped - Product not downloadable', 'warning', ['productId' => $productId]);
            return;
        }
        
        // Get current audio files
        $audioFiles = $this->getAudioDownloads($product);
        if (empty($audioFiles)) {
            $this->log('Skipped - No audio files found', 'warning', ['productId' => $productId]);
            return;
        }
        
        $this->log('Audio files detected', 'success', [
            'productId' => $productId,
            'count' => count($audioFiles),
            'files' => array_map(function($f) { return $f['name']; }, $audioFiles)
        ]);
        
        // Check if files have changed
        $hasChanged = $this->detectAudioChanges($productId, $audioFiles);
        $lastGenerated = get_post_meta($productId, '_bfp_formats_generated', true);
        
        if (!$hasChanged && $lastGenerated) {
            $this->log('Skipped - No changes detected since last generation', 'info', [
                'productId' => $productId,
                'lastGenerated' => date('Y-m-d H:i:s', $lastGenerated)
            ]);
            return;
        }
        
        // Check if FFmpeg is available
        if (!$this->isFFmpegAvailable()) {
            $this->log('ERROR - FFmpeg not available!', 'error', [
                'productId' => $productId,
                'suggestion' => 'Please configure FFmpeg path in Bandfront Player settings'
            ]);
            
            // Store error for admin notice
            set_transient('bfp_processing_error_' . $productId, 
                'FFmpeg is not available. Please configure FFmpeg path in Bandfront Player settings.', 
                MINUTE_IN_SECONDS * 5
            );
            return;
        }
        
        $this->log('Starting format generation...', 'info', ['productId' => $productId]);
        
        // Store processing status
        set_transient('bfp_processing_' . $productId, true, MINUTE_IN_SECONDS * 5);
        
        // Generate formats
        $startTime = microtime(true);
        $this->generateAudioFormats($productId, $audioFiles);
        $duration = round(microtime(true) - $startTime, 2);
        
        // Clear processing status
        delete_transient('bfp_processing_' . $productId);
        
        // Store success message
        set_transient('bfp_processing_success_' . $productId, 
            sprintf('Audio formats generated successfully in %s seconds!', $duration), 
            MINUTE_IN_SECONDS * 5
        );
        
        $this->log('=== PRODUCT AUDIO PROCESSING COMPLETED ===', 'success', [
            'productId' => $productId,
            'duration' => $duration . ' seconds'
        ]);
    }
    
    /**
     * Detect if audio files have changed
     */
    private function detectAudioChanges(int $productId, array $currentFiles): bool {
        $previousHash = get_post_meta($productId, '_bfp_audio_files_hash', true);
        
        // Create hash of current files
        $fileData = array_map(function($file) {
            return [
                'name' => $file['name'],
                'file' => $file['file'],
                'extension' => $file['extension']
            ];
        }, $currentFiles);
        
        $currentHash = md5(serialize($fileData));
        
        if ($previousHash !== $currentHash) {
            update_post_meta($productId, '_bfp_audio_files_hash', $currentHash);
            $this->log('Audio files have changed', 'info', [
                'previousHash' => substr($previousHash, 0, 8) . '...',
                'currentHash' => substr($currentHash, 0, 8) . '...'
            ]);
            return true;
        }
        
        return false;
    }
    
    /**
     * Get audio files from WooCommerce downloads
     */
    private function getAudioDownloads(\WC_Product $product): array {
        $downloads = $product->get_downloads();
        $audioFiles = [];
        $audioExtensions = ['wav', 'mp3', 'flac', 'aiff', 'alac', 'ogg', 'm4a'];
        
        foreach ($downloads as $downloadId => $download) {
            $file = $download->get_file();
            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            
            if (in_array($ext, $audioExtensions)) {
                $localPath = $this->getLocalPath($file);
                if ($localPath) {
                    $audioFiles[] = [
                        'id' => $downloadId,
                        'name' => $download->get_name(),
                        'file' => $file,
                        'local_path' => $localPath,
                        'extension' => $ext
                    ];
                    $this->addConsoleLog('getAudioDownloads found audio', [
                        'id' => $downloadId,
                        'name' => $download->get_name(),
                        'ext' => $ext
                    ]);
                }
            }
        }
        
        return $audioFiles;
    }
    
    /**
     * Convert URL to local file path
     */
    private function getLocalPath(string $url): ?string {
        $this->addConsoleLog('getLocalPath checking', ['url' => $url]);
        
        // Direct file path
        if (file_exists($url)) {
            return $url;
        }
        
        // Try WordPress upload directory
        $uploadDir = wp_upload_dir();
        $uploadUrl = $uploadDir['baseurl'];
        $uploadPath = $uploadDir['basedir'];
        
        if (strpos($url, $uploadUrl) === 0) {
            $relativePath = str_replace($uploadUrl, '', $url);
            $localPath = $uploadPath . $relativePath;
            if (file_exists($localPath)) {
                $this->addConsoleLog('getLocalPath found in uploads', ['path' => $localPath]);
                return $localPath;
            }
        }
        
        // Try WooCommerce uploads directory
        $wooPath = $uploadPath . '/woocommerce_uploads';
        $filename = basename($url);
        
        // Search in woocommerce_uploads subdirectories
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($wooPath, \RecursiveDirectoryIterator::SKIP_DOTS)
        );
        
        foreach ($iterator as $file) {
            if ($file->getFilename() === $filename) {
                $this->addConsoleLog('getLocalPath found in woo uploads', ['path' => $file->getPathname()]);
                return $file->getPathname();
            }
        }
        
        $this->addConsoleLog('getLocalPath not found', ['url' => $url]);
        return null;
    }
    
    /**
     * Generate all audio formats and zip packages
     */
    private function generateAudioFormats(int $productId, array $audioFiles): void {
        $this->log('Format generation starting', 'info', [
            'productId' => $productId, 
            'fileCount' => count($audioFiles)
        ]);
        
        $uploadsDir = $this->getWooCommerceUploadsDir();
        $productDir = $uploadsDir . '/bfp-formats/' . $productId;
        
        // Clean up old files if they exist
        if (is_dir($productDir)) {
            $this->log('Cleaning up old format files', 'info', ['directory' => $productDir]);
            $this->deleteDirectory($productDir);
        }
        
        // Create directory structure
        $formats = ['mp3', 'wav', 'flac', 'ogg'];
        foreach ($formats as $format) {
            wp_mkdir_p($productDir . '/' . $format);
        }
        wp_mkdir_p($productDir . '/zips');
        
        $this->log('Directories created', 'success', ['productDir' => $productDir]);
        
        // Track converted files by format
        $convertedByFormat = [
            'mp3' => [],
            'wav' => [],
            'flac' => [],
            'ogg' => []
        ];
        
        // Process each audio file
        foreach ($audioFiles as $index => $audio) {
            $this->log('Processing audio file', 'info', [
                'index' => $index + 1,
                'total' => count($audioFiles),
                'name' => $audio['name'],
                'extension' => $audio['extension']
            ]);
            
            // Clean filename for output
            $cleanName = $this->cleanFilename($audio['name'], $index);
            
            // Convert to each format
            foreach ($formats as $format) {
                $outputPath = $productDir . '/' . $format . '/' . $cleanName . '.' . $format;
                
                // Skip if source and target format are the same
                if ($audio['extension'] === $format) {
                    if (copy($audio['local_path'], $outputPath)) {
                        $convertedByFormat[$format][] = $outputPath;
                        $this->log('Copied original ' . strtoupper($format), 'success', [
                            'file' => basename($outputPath)
                        ]);
                    }
                    continue;
                }
                
                // Convert using FFmpeg
                $this->log('Converting to ' . strtoupper($format), 'info', [
                    'source' => basename($audio['local_path']),
                    'target' => basename($outputPath)
                ]);
                
                if ($this->convertToFormat($audio['local_path'], $outputPath, $format)) {
                    $convertedByFormat[$format][] = $outputPath;
                    $this->log('Conversion successful', 'success', [
                        'format' => strtoupper($format),
                        'file' => basename($outputPath),
                        'size' => $this->formatFileSize(filesize($outputPath))
                    ]);
                } else {
                    $this->log('Conversion FAILED', 'error', [
                        'format' => strtoupper($format),
                        'file' => basename($outputPath)
                    ]);
                }
            }
        }
        
        // Find and copy cover image
        $coverPath = $this->findCoverImage($audioFiles[0]['local_path'] ?? '');
        if ($coverPath) {
            foreach ($formats as $format) {
                $destCover = $productDir . '/' . $format . '/cover.png';
                copy($coverPath, $destCover);
                $convertedByFormat[$format][] = $destCover;
            }
            $this->log('Cover image added', 'success', ['source' => basename($coverPath)]);
        }
        
        // Create zip packages for each format
        $this->log('Creating ZIP packages...', 'info');
        $this->createZipPackages($productId, $productDir, $convertedByFormat);
        
        // Store metadata
        update_post_meta($productId, '_bfp_formats_generated', time());
        update_post_meta($productId, '_bfp_available_formats', array_keys(array_filter($convertedByFormat)));
        
        $this->log('Format generation completed!', 'success', [
            'productId' => $productId,
            'formats' => array_map('count', $convertedByFormat),
            'totalFiles' => array_sum(array_map('count', $convertedByFormat))
        ]);
    }
    
    /**
     * Clean filename for output
     */
    private function cleanFilename(string $name, int $index): string {
        // Remove extension
        $name = preg_replace('/\.[^.]+$/', '', $name);
        
        // Clean up common suffixes
        $name = preg_replace('/-[a-z0-9]{6,}$/i', '', $name);
        $name = preg_replace('/--+/', '-', $name);
        $name = trim($name, '-_ ');
        
        // Add track number if not present
        if (!preg_match('/^\d+/', $name)) {
            $name = sprintf('%02d - %s', $index + 1, $name);
        }
        
        return sanitize_file_name($name);
    }
    
    /**
     * Find cover image near audio file
     */
    private function findCoverImage(string $audioPath): ?string {
        if (!$audioPath || !file_exists($audioPath)) {
            return null;
        }
        
        $dir = dirname($audioPath);
        $coverPatterns = ['cover*.png', 'cover*.jpg', 'folder*.png', 'folder*.jpg'];
        
        foreach ($coverPatterns as $pattern) {
            $matches = glob($dir . '/' . $pattern);
            if (!empty($matches)) {
                return $matches[0];
            }
        }
        
        return null;
    }
    
    /**
     * Convert audio file to format using FFmpeg
     */
    private function convertToFormat(string $inputPath, string $outputPath, string $format): bool {
        $ffmpegPath = $this->getFFmpegPath();
        
        // Build command based on format
        switch ($format) {
            case 'mp3':
                $command = sprintf('%s -i %s -codec:a libmp3lame -qscale:a 2 %s -y 2>&1',
                    $ffmpegPath,
                    escapeshellarg($inputPath),
                    escapeshellarg($outputPath)
                );
                break;
                
            case 'flac':
                $command = sprintf('%s -i %s -codec:a flac %s -y 2>&1',
                    $ffmpegPath,
                    escapeshellarg($inputPath),
                    escapeshellarg($outputPath)
                );
                break;
                
            case 'ogg':
                $command = sprintf('%s -i %s -codec:a libvorbis -qscale:a 5 %s -y 2>&1',
                    $ffmpegPath,
                    escapeshellarg($inputPath),
                    escapeshellarg($outputPath)
                );
                break;
                
            case 'wav':
                $command = sprintf('%s -i %s -codec:a pcm_s16le %s -y 2>&1',
                    $ffmpegPath,
                    escapeshellarg($inputPath),
                    escapeshellarg($outputPath)
                );
                break;
                
            default:
                return false;
        }
        
        $this->addConsoleLog('convertToFormat executing', ['command' => $command]);
        
        $output = [];
        $returnVar = 0;
        exec($command, $output, $returnVar);
        
        if ($returnVar !== 0) {
            $this->addConsoleLog('convertToFormat failed', [
                'returnVar' => $returnVar,
                'output' => implode("\n", $output)
            ]);
            return false;
        }
        
        return file_exists($outputPath) && filesize($outputPath) > 0;
    }
    
    /**
     * Create zip packages for each format
     */
    private function createZipPackages(int $productId, string $productDir, array $convertedByFormat): void {
        $product = wc_get_product($productId);
        $productName = sanitize_file_name($product->get_name());
        
        foreach ($convertedByFormat as $format => $files) {
            if (empty($files)) {
                continue;
            }
            
            $zipPath = $productDir . '/zips/' . $format . '.zip';
            $zip = new \ZipArchive();
            
            if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === true) {
                foreach ($files as $file) {
                    $zip->addFile($file, basename($file));
                }
                $zip->close();
                
                $this->log('ZIP package created', 'success', [
                    'format' => strtoupper($format),
                    'fileCount' => count($files),
                    'size' => $this->formatFileSize(filesize($zipPath))
                ]);
            } else {
                $this->log('ZIP creation FAILED', 'error', [
                    'format' => strtoupper($format),
                    'path' => $zipPath
                ]);
            }
        }
    }
    
    /**
     * Delete directory recursively
     */
    private function deleteDirectory(string $dir): bool {
        if (!is_dir($dir)) {
            return false;
        }
        
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->deleteDirectory($path) : unlink($path);
        }
        
        return rmdir($dir);
    }
    
    /**
     * Format file size for display
     */
    private function formatFileSize(int $bytes): string {
        $units = ['B', 'KB', 'MB', 'GB'];
        $power = floor(log($bytes, 1024));
        return round($bytes / pow(1024, $power), 2) . ' ' . $units[$power];
    }
    
    /**
     * Show admin notices for processing status
     */
    public function showProcessingNotices(): void {
        if (!current_user_can('edit_products')) {
            return;
        }
        
        global $post;
        if (!$post || !in_array($post->post_type, ['product'])) {
            return;
        }
        
        $productId = $post->ID;
        
        // Check for processing status
        if (get_transient('bfp_processing_' . $productId)) {
            ?>
            <div class="notice notice-info">
                <p><strong>Bandfront Player:</strong> Audio format generation in progress...</p>
            </div>
            <?php
        }
        
        // Check for success message
        $success = get_transient('bfp_processing_success_' . $productId);
        if ($success) {
            delete_transient('bfp_processing_success_' . $productId);
            ?>
            <div class="notice notice-success is-dismissible">
                <p><strong>Bandfront Player:</strong> <?php echo esc_html($success); ?></p>
            </div>
            <?php
        }
        
        // Check for error message
        $error = get_transient('bfp_processing_error_' . $productId);
        if ($error) {
            delete_transient('bfp_processing_error_' . $productId);
            ?>
            <div class="notice notice-error is-dismissible">
                <p><strong>Bandfront Player:</strong> <?php echo esc_html($error); ?></p>
            </div>
            <?php
        }
    }
    
    /**
     * Enhanced logging with levels and styling
     */
    private function log(string $message, string $level = 'info', $data = null): void {
        if (!$this->debugMode) {
            return;
        }
        
        $styles = [
            'info' => 'color: #3498db; font-weight: normal;',
            'success' => 'color: #27ae60; font-weight: bold;',
            'warning' => 'color: #f39c12; font-weight: bold;',
            'error' => 'color: #e74c3c; font-weight: bold; font-size: 1.1em;'
        ];
        
        $style = $styles[$level] ?? $styles['info'];
        
        // Prevent output during activation or AJAX requests
        if (defined('WP_INSTALLING') || wp_doing_ajax() || (defined('REST_REQUEST') && REST_REQUEST)) {
            // Only use error_log during these contexts
            error_log('[BFP ProductProcessor] ' . $message . ' ' . wp_json_encode($data));
            return;
        }
        
        // Only output in appropriate contexts
        if (!did_action('wp_body_open') && !did_action('admin_head')) {
            error_log('[BFP ProductProcessor] ' . $message . ' ' . wp_json_encode($data));
            return;
        }
        
        echo '<script>console.log("%c[BFP ProductProcessor] ' . esc_js($message) . '", "' . $style . '", ' . 
             wp_json_encode($data) . ');</script>';
        
        // Also log to error_log for debugging
        error_log('[BFP ProductProcessor] ' . $message . ' ' . wp_json_encode($data));
    }
    
    /**
     * Legacy method for backwards compatibility
     */
    private function addConsoleLog(string $message, $data = null): void {
        $this->log($message, 'info', $data);
    }
    
    /**
     * Check if FFmpeg is available
     */
    private function isFFmpegAvailable(): bool {
        $ffmpegEnabled = $this->mainPlugin->getConfig()->getState('_bfp_ffmpeg');
        if (!$ffmpegEnabled) {
            $this->log('FFmpeg disabled in settings', 'info');
            return false;
        }
        
        $ffmpegPath = $this->getFFmpegPath();
        if (!$ffmpegPath) {
            $this->log('FFmpeg path not found', 'warning');
            return false;
        }
        
        // Test FFmpeg execution
        $command = $ffmpegPath . ' -version 2>&1';
        $output = @shell_exec($command);
        
        $available = !empty($output) && strpos($output, 'ffmpeg version') !== false;
        $this->log('FFmpeg availability check', 'info', [
            'path' => $ffmpegPath,
            'available' => $available
        ]);
        
        return $available;
    }
    
    /**
     * Get FFmpeg executable path
     */
    private function getFFmpegPath(): ?string {
        $ffmpegPath = $this->mainPlugin->getConfig()->getState('_bfp_ffmpeg_path');
        
        if (empty($ffmpegPath)) {
            // Try common locations
            $commonPaths = [
                '/usr/bin/ffmpeg',
                '/usr/local/bin/ffmpeg',
                '/opt/ffmpeg/ffmpeg',
                'ffmpeg' // System PATH
            ];
            
            foreach ($commonPaths as $path) {
                if (@is_executable($path) || @shell_exec("which $path 2>/dev/null")) {
                    $this->log('FFmpeg found at common location', 'info', ['path' => $path]);
                    return $path;
                }
            }
            
            return null;
        }
        
        // Clean the path
        $ffmpegPath = rtrim($ffmpegPath, '/\\');
        
        // If it's a directory, append ffmpeg
        if (is_dir($ffmpegPath)) {
            $ffmpegPath .= '/ffmpeg';
        }
        
        // Check if executable
        if (!is_executable($ffmpegPath) && !@shell_exec("which $ffmpegPath 2>/dev/null")) {
            $this->log('FFmpeg path not executable', 'warning', ['path' => $ffmpegPath]);
            return null;
        }
        
        return $ffmpegPath;
    }
}
<?php
namespace bfp;

use bfp\Utils\Debug; // DEBUG-REMOVE

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
        Debug::log('ProductProcessor.php:' . __LINE__ . ' Entering __construct()', ['mainPlugin' => get_class($mainPlugin)]); // DEBUG-REMOVE
        $this->mainPlugin = $mainPlugin;
        
        // Hook into WooCommerce product save events
        add_action('woocommerce_process_product_meta', [$this, 'processProductAudio'], 20);
        add_action('woocommerce_update_product', [$this, 'processProductAudio'], 20);
        add_action('woocommerce_new_product', [$this, 'processProductAudio'], 20);
        
        // ADDED: Debug hook registration
        add_action('save_post_product', [$this, 'debugSavePost'], 10, 3);
        
        // Add admin notice for processing status
        add_action('admin_notices', [$this, 'showProcessingNotices']);
        
        Debug::log('ProductProcessor.php:' . __LINE__ . ' Registered WooCommerce and debug hooks', []); // DEBUG-REMOVE
        Debug::log('ProductProcessor.php:' . __LINE__ . ' Exiting __construct()', []); // DEBUG-REMOVE
    }
    
    /**
     * Debug save_post hook
     */
    public function debugSavePost($post_id, $post, $update): void {
        Debug::log('ProductProcessor.php:' . __LINE__ . ' Entering debugSavePost()', [
            'post_id' => $post_id,
            'post_type' => $post->post_type,
            'update' => $update
        ]); // DEBUG-REMOVE
        Debug::log('ProductProcessor.php:' . __LINE__ . ' save_post action', [
            'action' => current_action(),
            'doing_autosave' => defined('DOING_AUTOSAVE') && DOING_AUTOSAVE,
            'doing_ajax' => defined('DOING_AJAX') && DOING_AJAX,
        ]); // DEBUG-REMOVE
    }
    
    /**
     * Process audio files when product is saved/updated
     */
    public function processProductAudio(int $productId): void {
        Debug::log('ProductProcessor.php:' . __LINE__ . ' Entering processProductAudio()', ['productId' => $productId]); // DEBUG-REMOVE
        $this->log('=== PRODUCT AUDIO PROCESSING STARTED ===', 'info', ['productId' => $productId]);
        
        $product = wc_get_product($productId);
        Debug::log('ProductProcessor.php:' . __LINE__ . ' Loaded product', ['productId' => $productId, 'product' => $product ? $product->get_name() : null]); // DEBUG-REMOVE
        
        if (!$product || !$product->is_downloadable()) {
            Debug::log('ProductProcessor.php:' . __LINE__ . ' Product not downloadable or not found', ['productId' => $productId]); // DEBUG-REMOVE
            $this->log('Skipped - Product not downloadable', 'warning', ['productId' => $productId]);
            return;
        }
        
        // Get current audio files
        $audioFiles = $this->getAudioDownloads($product);
        Debug::log('ProductProcessor.php:' . __LINE__ . ' Audio files fetched', ['productId' => $productId, 'audioFiles' => $audioFiles]); // DEBUG-REMOVE
        if (empty($audioFiles)) {
            Debug::log('ProductProcessor.php:' . __LINE__ . ' No audio files found', ['productId' => $productId]); // DEBUG-REMOVE
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
        Debug::log('ProductProcessor.php:' . __LINE__ . ' Audio change detection', ['hasChanged' => $hasChanged]); // DEBUG-REMOVE
        $lastGenerated = get_post_meta($productId, '_bfp_formats_generated', true);
        
        if (!$hasChanged && $lastGenerated) {
            Debug::log('ProductProcessor.php:' . __LINE__ . ' No changes detected since last generation', [
                'productId' => $productId,
                'lastGenerated' => $lastGenerated
            ]); // DEBUG-REMOVE
            $this->log('Skipped - No changes detected since last generation', 'info', [
                'productId' => $productId,
                'lastGenerated' => date('Y-m-d H:i:s', $lastGenerated)
            ]);
            return;
        }
        
        // Check if FFmpeg is available
        if (!$this->isFFmpegAvailable()) {
            Debug::log('ProductProcessor.php:' . __LINE__ . ' FFmpeg not available', ['productId' => $productId]); // DEBUG-REMOVE
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
        Debug::log('ProductProcessor.php:' . __LINE__ . ' Starting format generation', ['productId' => $productId]); // DEBUG-REMOVE
        
        // Store processing status
        set_transient('bfp_processing_' . $productId, true, MINUTE_IN_SECONDS * 5);
        
        // Generate formats
        $startTime = microtime(true);
        $this->generateAudioFormats($productId, $audioFiles);
        $duration = round(microtime(true) - $startTime, 2);
        Debug::log('ProductProcessor.php:' . __LINE__ . ' Format generation completed', ['productId' => $productId, 'duration' => $duration]); // DEBUG-REMOVE
        
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
        Debug::log('ProductProcessor.php:' . __LINE__ . ' Exiting processProductAudio()', ['productId' => $productId]); // DEBUG-REMOVE
    }
    
    /**
     * Detect if audio files have changed
     */
    private function detectAudioChanges(int $productId, array $currentFiles): bool {
        Debug::log('ProductProcessor.php:' . __LINE__ . ' Entering detectAudioChanges()', ['productId' => $productId]); // DEBUG-REMOVE
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
        Debug::log('ProductProcessor.php:' . __LINE__ . ' Comparing audio file hashes', [
            'previousHash' => $previousHash,
            'currentHash' => $currentHash
        ]); // DEBUG-REMOVE
        
        if ($previousHash !== $currentHash) {
            update_post_meta($productId, '_bfp_audio_files_hash', $currentHash);
            $this->log('Audio files have changed', 'info', [
                'previousHash' => substr($previousHash, 0, 8) . '...',
                'currentHash' => substr($currentHash, 0, 8) . '...'
            ]);
            Debug::log('ProductProcessor.php:' . __LINE__ . ' Audio files changed, hash updated', [
                'productId' => $productId,
                'newHash' => $currentHash
            ]); // DEBUG-REMOVE
            return true;
        }
        
        Debug::log('ProductProcessor.php:' . __LINE__ . ' No audio file changes detected', ['productId' => $productId]); // DEBUG-REMOVE
        return false;
    }
    
    /**
     * Get audio files from WooCommerce downloads
     */
    private function getAudioDownloads(\WC_Product $product): array {
        Debug::log('ProductProcessor.php:' . __LINE__ . ' Entering getAudioDownloads()', ['product' => $product->get_name()]); // DEBUG-REMOVE
        $downloads = $product->get_downloads();
        $audioFiles = [];
        $audioExtensions = ['wav', 'mp3', 'flac', 'aiff', 'alac', 'ogg', 'm4a'];
        
        foreach ($downloads as $downloadId => $download) {
            $file = $download->get_file();
            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            
            if (in_array($ext, $audioExtensions)) {
                $localPath = $this->getLocalPath($file);
                Debug::log('ProductProcessor.php:' . __LINE__ . ' Checking audio file', [
                    'downloadId' => $downloadId,
                    'file' => $file,
                    'extension' => $ext,
                    'localPath' => $localPath
                ]); // DEBUG-REMOVE
                if ($localPath) {
                    $audioFiles[] = [
                        'id' => $downloadId,
                        'name' => $download->get_name(),
                        'file' => $file,
                        'local_path' => $localPath,
                        'extension' => $ext
                    ];
                    Debug::log('ProductProcessor.php:' . __LINE__ . ' Audio file added', [
                        'id' => $downloadId,
                        'name' => $download->get_name(),
                        'ext' => $ext,
                        'local_path' => $localPath
                    ]); // DEBUG-REMOVE
                }
            }
        }
        
        Debug::log('ProductProcessor.php:' . __LINE__ . ' getAudioDownloads returning', ['audioFiles' => $audioFiles]); // DEBUG-REMOVE
        return $audioFiles;
    }
    
    /**
     * Convert URL to local file path
     */
    private function getLocalPath(string $url): ?string {
        Debug::log('ProductProcessor.php:' . __LINE__ . ' Entering getLocalPath()', ['url' => $url]); // DEBUG-REMOVE
        
        // Direct file path
        if (file_exists($url)) {
            Debug::log('ProductProcessor.php:' . __LINE__ . ' File exists at direct path', ['path' => $url]); // DEBUG-REMOVE
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
                Debug::log('ProductProcessor.php:' . __LINE__ . ' File found in uploads', ['path' => $localPath]); // DEBUG-REMOVE
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
                Debug::log('ProductProcessor.php:' . __LINE__ . ' File found in WooCommerce uploads', ['path' => $file->getPathname()]); // DEBUG-REMOVE
                return $file->getPathname();
            }
        }
        
        Debug::log('ProductProcessor.php:' . __LINE__ . ' File not found for URL', ['url' => $url]); // DEBUG-REMOVE
        return null;
    }
    
    /**
     * Generate all audio formats and zip packages
     */
    private function generateAudioFormats(int $productId, array $audioFiles): void {
        Debug::log('ProductProcessor.php:' . __LINE__ . ' Entering generateAudioFormats()', [
            'productId' => $productId,
            'audioFiles' => array_map(function($f) { return $f['name']; }, $audioFiles)
        ]); // DEBUG-REMOVE
        $this->log('Format generation starting', 'info', [
            'productId' => $productId, 
            'fileCount' => count($audioFiles)
        ]);
        
        $uploadsDir = $this->getWooCommerceUploadsDir();
        $productDir = $uploadsDir . '/bfp-formats/' . $productId;
        Debug::log('ProductProcessor.php:' . __LINE__ . ' Product directory for formats', ['productDir' => $productDir]); // DEBUG-REMOVE
        
        // Clean up old files if they exist
        if (is_dir($productDir)) {
            $this->log('Cleaning up old format files', 'info', ['directory' => $productDir]);
            Debug::log('ProductProcessor.php:' . __LINE__ . ' Deleting old format directory', ['directory' => $productDir]); // DEBUG-REMOVE
            $this->deleteDirectory($productDir);
        }
        
        // Create directory structure
        $formats = ['mp3', 'wav', 'flac', 'ogg'];
        foreach ($formats as $format) {
            wp_mkdir_p($productDir . '/' . $format);
            Debug::log('ProductProcessor.php:' . __LINE__ . ' Created format directory', ['dir' => $productDir . '/' . $format]); // DEBUG-REMOVE
        }
        wp_mkdir_p($productDir . '/zips');
        Debug::log('ProductProcessor.php:' . __LINE__ . ' Created zips directory', ['dir' => $productDir . '/zips']); // DEBUG-REMOVE
        
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
            Debug::log('ProductProcessor.php:' . __LINE__ . ' Processing audio file', [
                'index' => $index,
                'audio' => $audio
            ]); // DEBUG-REMOVE
            $this->log('Processing audio file', 'info', [
                'index' => $index + 1,
                'total' => count($audioFiles),
                'name' => $audio['name'],
                'extension' => $audio['extension']
            ]);
            
            // Clean filename for output
            $cleanName = $this->cleanFilename($audio['name'], $index);
            Debug::log('ProductProcessor.php:' . __LINE__ . ' Cleaned filename', [
                'original' => $audio['name'],
                'cleanName' => $cleanName
            ]); // DEBUG-REMOVE
            
            // Convert to each format
            foreach ($formats as $format) {
                $outputPath = $productDir . '/' . $format . '/' . $cleanName . '.' . $format;
                Debug::log('ProductProcessor.php:' . __LINE__ . ' Preparing output path', [
                    'format' => $format,
                    'outputPath' => $outputPath
                ]); // DEBUG-REMOVE
                
                // Skip if source and target format are the same
                if ($audio['extension'] === $format) {
                    if (copy($audio['local_path'], $outputPath)) {
                        $convertedByFormat[$format][] = $outputPath;
                        $this->log('Copied original ' . strtoupper($format), 'success', [
                            'file' => basename($outputPath)
                        ]);
                        Debug::log('ProductProcessor.php:' . __LINE__ . ' Copied original file', [
                            'source' => $audio['local_path'],
                            'destination' => $outputPath
                        ]); // DEBUG-REMOVE
                    }
                    continue;
                }
                
                // Convert using FFmpeg
                $this->log('Converting to ' . strtoupper($format), 'info', [
                    'source' => basename($audio['local_path']),
                    'target' => basename($outputPath)
                ]);
                Debug::log('ProductProcessor.php:' . __LINE__ . ' Converting file', [
                    'input' => $audio['local_path'],
                    'output' => $outputPath,
                    'format' => $format
                ]); // DEBUG-REMOVE
                
                if ($this->convertToFormat($audio['local_path'], $outputPath, $format)) {
                    $convertedByFormat[$format][] = $outputPath;
                    $this->log('Conversion successful', 'success', [
                        'format' => strtoupper($format),
                        'file' => basename($outputPath),
                        'size' => $this->formatFileSize(filesize($outputPath))
                    ]);
                    Debug::log('ProductProcessor.php:' . __LINE__ . ' Conversion successful', [
                        'outputPath' => $outputPath,
                        'size' => filesize($outputPath)
                    ]); // DEBUG-REMOVE
                } else {
                    $this->log('Conversion FAILED', 'error', [
                        'format' => strtoupper($format),
                        'file' => basename($outputPath)
                    ]);
                    Debug::log('ProductProcessor.php:' . __LINE__ . ' Conversion failed', [
                        'outputPath' => $outputPath
                    ]); // DEBUG-REMOVE
                }
            }
        }
        
        // Find and copy cover image
        $coverPath = $this->findCoverImage($audioFiles[0]['local_path'] ?? '');
        Debug::log('ProductProcessor.php:' . __LINE__ . ' Cover image search', ['coverPath' => $coverPath]); // DEBUG-REMOVE
        if ($coverPath) {
            foreach ($formats as $format) {
                $destCover = $productDir . '/' . $format . '/cover.png';
                copy($coverPath, $destCover);
                $convertedByFormat[$format][] = $destCover;
                Debug::log('ProductProcessor.php:' . __LINE__ . ' Copied cover image', [
                    'source' => $coverPath,
                    'destination' => $destCover
                ]); // DEBUG-REMOVE
            }
            $this->log('Cover image added', 'success', ['source' => basename($coverPath)]);
        }
        
        // Create zip packages for each format
        $this->log('Creating ZIP packages...', 'info');
        Debug::log('ProductProcessor.php:' . __LINE__ . ' Creating ZIP packages', [
            'productDir' => $productDir,
            'convertedByFormat' => $convertedByFormat
        ]); // DEBUG-REMOVE
        $this->createZipPackages($productId, $productDir, $convertedByFormat);
        
        // Store metadata
        update_post_meta($productId, '_bfp_formats_generated', time());
        update_post_meta($productId, '_bfp_available_formats', array_keys(array_filter($convertedByFormat)));
        Debug::log('ProductProcessor.php:' . __LINE__ . ' Updated product meta for formats', [
            'productId' => $productId,
            'formats' => array_keys(array_filter($convertedByFormat))
        ]); // DEBUG-REMOVE
        
        $this->log('Format generation completed!', 'success', [
            'productId' => $productId,
            'formats' => array_map('count', $convertedByFormat),
            'totalFiles' => array_sum(array_map('count', $convertedByFormat))
        ]);
        Debug::log('ProductProcessor.php:' . __LINE__ . ' Exiting generateAudioFormats()', [
            'productId' => $productId
        ]); // DEBUG-REMOVE
    }
    
    /**
     * Clean filename for output
     */
    private function cleanFilename(string $name, int $index): string {
        Debug::log('ProductProcessor.php:' . __LINE__ . ' Entering cleanFilename()', [
            'name' => $name,
            'index' => $index
        ]); // DEBUG-REMOVE
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
        
        $result = sanitize_file_name($name);
        Debug::log('ProductProcessor.php:' . __LINE__ . ' Exiting cleanFilename()', [
            'result' => $result
        ]); // DEBUG-REMOVE
        return $result;
    }
    
    /**
     * Find cover image near audio file
     */
    private function findCoverImage(string $audioPath): ?string {
        Debug::log('ProductProcessor.php:' . __LINE__ . ' Entering findCoverImage()', ['audioPath' => $audioPath]); // DEBUG-REMOVE
        if (!$audioPath || !file_exists($audioPath)) {
            return null;
        }
        
        $dir = dirname($audioPath);
        $coverPatterns = ['cover*.png', 'cover*.jpg', 'folder*.png', 'folder*.jpg'];
        
        foreach ($coverPatterns as $pattern) {
            $matches = glob($dir . '/' . $pattern);
            if (!empty($matches)) {
                Debug::log('ProductProcessor.php:' . __LINE__ . ' Found cover image', ['cover' => $matches[0]]); // DEBUG-REMOVE
                return $matches[0];
            }
        }
        Debug::log('ProductProcessor.php:' . __LINE__ . ' No cover image found', ['audioPath' => $audioPath]); // DEBUG-REMOVE
        return null;
    }
    
    /**
     * Convert audio file to format using FFmpeg
     */
    private function convertToFormat(string $inputPath, string $outputPath, string $format): bool {
        Debug::log('ProductProcessor.php:' . __LINE__ . ' Entering convertToFormat()', [
            'inputPath' => $inputPath,
            'outputPath' => $outputPath,
            'format' => $format
        ]); // DEBUG-REMOVE
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
                Debug::log('ProductProcessor.php:' . __LINE__ . ' Unsupported format for conversion', ['format' => $format]); // DEBUG-REMOVE
                return false;
        }
        
        Debug::log('ProductProcessor.php:' . __LINE__ . ' Executing FFmpeg command', ['command' => $command]); // DEBUG-REMOVE
        
        $output = [];
        $returnVar = 0;
        exec($command, $output, $returnVar);
        
        if ($returnVar !== 0) {
            Debug::log('ProductProcessor.php:' . __LINE__ . ' FFmpeg conversion failed', [
                'returnVar' => $returnVar,
                'output' => implode("\n", $output),
                'inputPath' => $inputPath,
                'outputPath' => $outputPath
            ]); // DEBUG-REMOVE
            return false;
        }
        
        if (file_exists($outputPath)) {
            Debug::log('ProductProcessor.php:' . __LINE__ . ' Format file created', ['outputPath' => $outputPath]); // DEBUG-REMOVE
        } else {
            Debug::log('ProductProcessor.php:' . __LINE__ . ' Failed to create format file', ['outputPath' => $outputPath]); // DEBUG-REMOVE
        }
        
        return file_exists($outputPath) && filesize($outputPath) > 0;
    }
    
    /**
     * Create zip packages for each format
     */
    private function createZipPackages(int $productId, string $productDir, array $convertedByFormat): void {
        Debug::log('ProductProcessor.php:' . __LINE__ . ' Entering createZipPackages()', [
            'productId' => $productId,
            'productDir' => $productDir,
            'formats' => array_keys($convertedByFormat)
        ]); // DEBUG-REMOVE
        $product = wc_get_product($productId);
        $productName = sanitize_file_name($product->get_name());
        
        foreach ($convertedByFormat as $format => $files) {
            if (empty($files)) {
                Debug::log('ProductProcessor.php:' . __LINE__ . ' No files to zip for format', ['format' => $format]); // DEBUG-REMOVE
                continue;
            }
            
            $zipPath = $productDir . '/zips/' . $format . '.zip';
            $zip = new \ZipArchive();
            
            Debug::log('ProductProcessor.php:' . __LINE__ . ' Zipping album', ['albumDir' => $productDir, 'zipPath' => $zipPath, 'files' => $files]); // DEBUG-REMOVE
            
            if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === true) {
                foreach ($files as $file) {
                    $zip->addFile($file, basename($file));
                    Debug::log('ProductProcessor.php:' . __LINE__ . ' Added file to zip', ['file' => $file, 'zipPath' => $zipPath]); // DEBUG-REMOVE
                }
                $zip->close();
                
                $this->log('ZIP package created', 'success', [
                    'format' => strtoupper($format),
                    'fileCount' => count($files),
                    'size' => $this->formatFileSize(filesize($zipPath))
                ]);
                Debug::log('ProductProcessor.php:' . __LINE__ . ' Album zip created', ['zipPath' => $zipPath]); // DEBUG-REMOVE
            } else {
                $this->log('ZIP creation FAILED', 'error', [
                    'format' => strtoupper($format),
                    'path' => $zipPath
                ]);
                Debug::log('ProductProcessor.php:' . __LINE__ . ' Failed to create album zip', ['zipPath' => $zipPath]); // DEBUG-REMOVE
            }
        }
        Debug::log('ProductProcessor.php:' . __LINE__ . ' Exiting createZipPackages()', ['productId' => $productId]); // DEBUG-REMOVE
    }
    
    /**
     * Delete directory recursively
     */
    private function deleteDirectory(string $dir): bool {
        Debug::log('ProductProcessor.php:' . __LINE__ . ' Entering deleteDirectory()', ['dir' => $dir]); // DEBUG-REMOVE
        if (!is_dir($dir)) {
            Debug::log('ProductProcessor.php:' . __LINE__ . ' Not a directory', ['dir' => $dir]); // DEBUG-REMOVE
            return false;
        }
        
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            if (is_dir($path)) {
                $this->deleteDirectory($path);
            } else {
                unlink($path);
                Debug::log('ProductProcessor.php:' . __LINE__ . ' Deleted file', ['file' => $path]); // DEBUG-REMOVE
            }
        }
        
        $result = rmdir($dir);
        Debug::log('ProductProcessor.php:' . __LINE__ . ' Removed directory', ['dir' => $dir, 'result' => $result]); // DEBUG-REMOVE
        return $result;
    }
    
    /**
     * Format file size for display
     */
    private function formatFileSize(int $bytes): string {
        Debug::log('ProductProcessor.php:' . __LINE__ . ' Formatting file size', ['bytes' => $bytes]); // DEBUG-REMOVE
        $units = ['B', 'KB', 'MB', 'GB'];
        $power = floor(log($bytes, 1024));
        $result = round($bytes / pow(1024, $power), 2) . ' ' . $units[$power];
        Debug::log('ProductProcessor.php:' . __LINE__ . ' Formatted file size', ['result' => $result]); // DEBUG-REMOVE
        return $result;
    }
    
    /**
     * Show admin notices for processing status
     */
    public function showProcessingNotices(): void {
        Debug::log('ProductProcessor.php:' . __LINE__ . ' Entering showProcessingNotices()', []); // DEBUG-REMOVE
        if (!current_user_can('edit_products')) {
            Debug::log('ProductProcessor.php:' . __LINE__ . ' User cannot edit products', []); // DEBUG-REMOVE
            return;
        }
        
        global $post;
        if (!$post || !in_array($post->post_type, ['product'])) {
            Debug::log('ProductProcessor.php:' . __LINE__ . ' Not a product post', ['post' => $post]); // DEBUG-REMOVE
            return;
        }
        
        $productId = $post->ID;
        
        // Check for processing status
        if (get_transient('bfp_processing_' . $productId)) {
            Debug::log('ProductProcessor.php:' . __LINE__ . ' Processing in progress', ['productId' => $productId]); // DEBUG-REMOVE
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
            Debug::log('ProductProcessor.php:' . __LINE__ . ' Processing success', ['productId' => $productId, 'message' => $success]); // DEBUG-REMOVE
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
            Debug::log('ProductProcessor.php:' . __LINE__ . ' Processing error', ['productId' => $productId, 'message' => $error]); // DEBUG-REMOVE
            ?>
            <div class="notice notice-error is-dismissible">
                <p><strong>Bandfront Player:</strong> <?php echo esc_html($error); ?></p>
            </div>
            <?php
        }
        Debug::log('ProductProcessor.php:' . __LINE__ . ' Exiting showProcessingNotices()', []); // DEBUG-REMOVE
    }
    
    /**
     * Enhanced logging with levels and styling
     */
    private function log(string $message, string $level = 'info', $data = null): void {
        Debug::log('ProductProcessor.php:' . __LINE__ . ' log()', [
            'message' => $message,
            'level' => $level,
            'data' => $data
        ]); // DEBUG-REMOVE
        if (!$this->debugMode) {
            return;
        }
        
        // Remove output to browser, only use Debug::log
        Debug::log('ProductProcessor.php:' . __LINE__ . ' Logging message', [
            'message' => $message,
            'level' => $level,
            'data' => $data
        ]); // DEBUG-REMOVE
    }
    
    /**
     * Legacy method for backwards compatibility
     */
    private function addConsoleLog(string $message, $data = null): void {
        Debug::log('ProductProcessor.php:' . __LINE__ . ' addConsoleLog()', [
            'message' => $message,
            'data' => $data
        ]); // DEBUG-REMOVE
        $this->log($message, 'info', $data);
    }
    
    /**
     * Check if FFmpeg is available
     */
    private function isFFmpegAvailable(): bool {
        Debug::log('ProductProcessor.php:' . __LINE__ . ' Entering isFFmpegAvailable()', []); // DEBUG-REMOVE
        $ffmpegEnabled = $this->mainPlugin->getConfig()->getState('_bfp_ffmpeg');
        if (!$ffmpegEnabled) {
            Debug::log('ProductProcessor.php:' . __LINE__ . ' FFmpeg disabled in settings', []); // DEBUG-REMOVE
            $this->log('FFmpeg disabled in settings', 'info');
            return false;
        }
        
        $ffmpegPath = $this->getFFmpegPath();
        if (!$ffmpegPath) {
            Debug::log('ProductProcessor.php:' . __LINE__ . ' FFmpeg path not found', []); // DEBUG-REMOVE
            $this->log('FFmpeg path not found', 'warning');
            return false;
        }
        
        // Test FFmpeg execution
        $command = $ffmpegPath . ' -version 2>&1';
        $output = @shell_exec($command);
        
        $available = !empty($output) && strpos($output, 'ffmpeg version') !== false;
        Debug::log('ProductProcessor.php:' . __LINE__ . ' FFmpeg availability check', [
            'path' => $ffmpegPath,
            'available' => $available,
            'output' => $output
        ]); // DEBUG-REMOVE
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
        Debug::log('ProductProcessor.php:' . __LINE__ . ' Entering getFFmpegPath()', []); // DEBUG-REMOVE
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
                    Debug::log('ProductProcessor.php:' . __LINE__ . ' FFmpeg found at common location', ['path' => $path]); // DEBUG-REMOVE
                    $this->log('FFmpeg found at common location', 'info', ['path' => $path]);
                    return $path;
                }
            }
            
            Debug::log('ProductProcessor.php:' . __LINE__ . ' FFmpeg not found in common locations', []); // DEBUG-REMOVE
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
            Debug::log('ProductProcessor.php:' . __LINE__ . ' FFmpeg path not executable', ['path' => $ffmpegPath]); // DEBUG-REMOVE
            $this->log('FFmpeg path not executable', 'warning', ['path' => $ffmpegPath]);
            return null;
        }
        
        Debug::log('ProductProcessor.php:' . __LINE__ . ' Returning FFmpeg path', ['path' => $ffmpegPath]); // DEBUG-REMOVE
        return $ffmpegPath;
    }
}
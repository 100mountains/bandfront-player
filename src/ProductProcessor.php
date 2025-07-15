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
    
    public function __construct(Plugin $mainPlugin) {
        $this->mainPlugin = $mainPlugin;
        $this->addConsoleLog('ProductProcessor initialized');
        
        // Hook into WooCommerce product save events
        add_action('woocommerce_process_product_meta', [$this, 'processProductAudio'], 20);
        add_action('woocommerce_update_product', [$this, 'processProductAudio'], 20);
        add_action('woocommerce_new_product', [$this, 'processProductAudio'], 20);
    }
    
    /**
     * Process audio files when product is saved/updated
     */
    public function processProductAudio(int $productId): void {
        $this->addConsoleLog('processProductAudio called', ['productId' => $productId]);
        
        $product = wc_get_product($productId);
        
        if (!$product || !$product->is_downloadable()) {
            $this->addConsoleLog('processProductAudio skipped - not downloadable', ['productId' => $productId]);
            return;
        }
        
        $audioFiles = $this->getAudioDownloads($product);
        if (empty($audioFiles)) {
            $this->addConsoleLog('processProductAudio skipped - no audio files', ['productId' => $productId]);
            return;
        }
        
        $this->addConsoleLog('processProductAudio found audio files', ['count' => count($audioFiles)]);
        
        // Check if FFmpeg is available
        if (!$this->isFFmpegAvailable()) {
            $this->addConsoleLog('processProductAudio error - FFmpeg not available');
            return;
        }
        
        $this->generateAudioFormats($productId, $audioFiles);
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
        $this->addConsoleLog('generateAudioFormats starting', ['productId' => $productId, 'files' => count($audioFiles)]);
        
        $uploadsDir = $this->getWooCommerceUploadsDir();
        $productDir = $uploadsDir . '/bfp-formats/' . $productId;
        
        // Create directory structure
        $formats = ['mp3', 'wav', 'flac', 'ogg'];
        foreach ($formats as $format) {
            wp_mkdir_p($productDir . '/' . $format);
        }
        wp_mkdir_p($productDir . '/zips');
        
        $this->addConsoleLog('generateAudioFormats directories created', ['productDir' => $productDir]);
        
        // Track converted files by format
        $convertedByFormat = [
            'mp3' => [],
            'wav' => [],
            'flac' => [],
            'ogg' => []
        ];
        
        // Process each audio file
        foreach ($audioFiles as $index => $audio) {
            $this->addConsoleLog('generateAudioFormats processing file', [
                'index' => $index,
                'name' => $audio['name'],
                'extension' => $audio['extension']
            ]);
            
            // Clean filename for output
            $cleanName = $this->cleanFilename($audio['name'], $index);
            
            // Convert to each format
            foreach ($formats as $format) {
                $outputPath = $productDir . '/' . $format . '/' . $cleanName . '.' . $format;
                
                // Skip if source and target format are the same
                if ($audio['extension'] === $format && $format === 'wav') {
                    if (copy($audio['local_path'], $outputPath)) {
                        $convertedByFormat[$format][] = $outputPath;
                        $this->addConsoleLog('generateAudioFormats copied wav', ['output' => $outputPath]);
                    }
                    continue;
                }
                
                // Convert using FFmpeg
                if ($this->convertToFormat($audio['local_path'], $outputPath, $format)) {
                    $convertedByFormat[$format][] = $outputPath;
                    $this->addConsoleLog('generateAudioFormats converted', [
                        'format' => $format,
                        'output' => $outputPath
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
            $this->addConsoleLog('generateAudioFormats copied cover', ['cover' => $coverPath]);
        }
        
        // Create zip packages for each format
        $this->createZipPackages($productId, $productDir, $convertedByFormat);
        
        // Store metadata
        update_post_meta($productId, '_bfp_formats_generated', time());
        update_post_meta($productId, '_bfp_available_formats', array_keys($convertedByFormat));
        
        $this->addConsoleLog('generateAudioFormats completed', [
            'productId' => $productId,
            'formats' => array_map('count', $convertedByFormat)
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
                
                $this->addConsoleLog('createZipPackages created zip', [
                    'format' => $format,
                    'files' => count($files),
                    'path' => $zipPath
                ]);
            }
        }
    }
    
    /**
     * Check if FFmpeg is available
     */
    private function isFFmpegAvailable(): bool {
        $ffmpegPath = $this->getFFmpegPath();
        return !empty($ffmpegPath);
    }
    
    /**
     * Get FFmpeg executable path
     */
    private function getFFmpegPath(): string {
        // First check plugin setting
        $customPath = $this->mainPlugin->getConfig()->getState('_bfp_ffmpeg_path');
        if ($customPath && file_exists($customPath)) {
            return $customPath;
        }
        
        // Try system ffmpeg
        $systemPath = trim(shell_exec('which ffmpeg 2>&1') ?? '');
        if ($systemPath && file_exists($systemPath)) {
            return $systemPath;
        }
        
        return '';
    }
    
    /**
     * Get WooCommerce uploads directory
     */
    private function getWooCommerceUploadsDir(): string {
        $uploadDir = wp_upload_dir();
        return $uploadDir['basedir'] . '/woocommerce_uploads';
    }
    
    /**
     * Add console log for debugging
     */
    private function addConsoleLog(string $message, $data = null): void {
        echo '<script>console.log("[BFP ProductProcessor] ' . esc_js($message) . '", ' . 
             wp_json_encode($data) . ');</script>';
    }
}
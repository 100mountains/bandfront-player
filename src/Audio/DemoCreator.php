<?php
declare(strict_types=1);

namespace Bandfront\Audio;

use Bandfront\Core\Config;
use Bandfront\Storage\FileManager;
use Bandfront\Utils\Debug;
use Bandfront\Utils\Cloud;

/**
 * Demo Creation Functionality
 * 
 * @package BandfrontPlayer
 * @since 2.3.6
 */
class DemoCreator {
    
    private Config $config;
    private FileManager $fileManager;
    
    public function __construct(Config $config, FileManager $fileManager) {
        $this->config = $config;
        $this->fileManager = $fileManager;
    }
    
    /**
     * Create demos for all products when demos are enabled
     */
    public function createDemosForAllProducts(): int {
        Debug::log('DemoCreator: Starting demo creation for all products', []);
        
        // Check if demos are enabled
        $demosEnabled = $this->config->getState('_bfp_play_demos', false);
        if (!$demosEnabled) {
            Debug::log('DemoCreator: Demos disabled, skipping creation', []);
            return 0;
        }
        
        // Get demo settings
        $demoPercent = (int) $this->config->getState('_bfp_demo_duration_percent', 30);
        Debug::log('DemoCreator: Demo percentage', ['percent' => $demoPercent]);
        
        // Get all downloadable products
        $products = wc_get_products([
            'limit' => -1,
            'downloadable' => true,
            'status' => 'publish'
        ]);
        
        $demoCount = 0;
        
        foreach ($products as $product) {
            $productId = $product->get_id();
            $productDemos = $this->createDemosForProduct($productId, $demoPercent);
            $demoCount += $productDemos;
            
            Debug::log('DemoCreator: Product processed', [
                'product_id' => $productId,
                'demos_created' => $productDemos
            ]);
        }
        
        Debug::log('DemoCreator: Demo creation completed', ['total_demos' => $demoCount]);
        return $demoCount;
    }
    
    /**
     * Create demos for a specific product
     */
    public function createDemosForProduct(int $productId, int $demoPercent): int {
        $files = $this->fileManager->getProductFiles($productId);
        
        if (empty($files)) {
            return 0;
        }
        
        $demoCount = 0;
        
        foreach ($files as $fileId => $fileData) {
            if (empty($fileData['file'])) {
                continue;
            }
            
            $originalPath = $fileData['file'];
            $demoPath = $this->getDemoFile($originalPath, $demoPercent);
            
            if ($demoPath && $demoPath !== $originalPath) {
                $demoCount++;
                Debug::log('DemoCreator: Demo created', [
                    'product_id' => $productId,
                    'file_id' => $fileId,
                    'original' => basename($originalPath),
                    'demo' => basename($demoPath)
                ]);
            }
        }
        
        return $demoCount;
    }
    
    /**
     * Delete truncated demo files for a product
     */
    public function deleteTruncatedFiles(int $productId): void {
        Debug::log('Entering deleteTruncatedFiles()', ['productId' => $productId]);
        $filesArr = get_post_meta($productId, '_downloadable_files', true);
        $ownFilesArr = get_post_meta($productId, '_bfp_demos_list', true);
        if (!is_array($filesArr)) {
            $filesArr = [$filesArr];
        }
        if (is_array($ownFilesArr) && !empty($ownFilesArr)) {
            $filesArr = array_merge($filesArr, $ownFilesArr);
        }

        if (!empty($filesArr) && is_array($filesArr)) {
            $filesDirectoryPath = $this->fileManager->getFilesDirectoryPath();
            foreach ($filesArr as $file) {
                if (is_array($file) && !empty($file['file'])) {
                    $ext = pathinfo($file['file'], PATHINFO_EXTENSION);
                    $fileName = md5($file['file']) . ((!empty($ext)) ? '.' . $ext : '');
                    if (file_exists($filesDirectoryPath . $fileName)) {
                        Debug::log('deleteTruncatedFiles: deleting file', ['fileName' => $fileName]);
                        @unlink($filesDirectoryPath . $fileName);
                    }
                    do_action('bfp_delete_file', $productId, $file['file']);
                }
            }
        }
        Debug::log('Truncated demo files deleted for product', ['productId' => $productId]);
    }
    
    /**
     * Generate demo file name from URL
     * 
     * @param string $url Source URL
     * @return string Generated filename
     */
    public function generateDemoFileName(string $url): string {
        Debug::log('generateDemoFileName()', ['url' => $url]);
        $ext = pathinfo($url, PATHINFO_EXTENSION);
        $ext = strtolower($ext);
        
        // Clean extension of query parameters
        if (strpos($ext, '?') !== false) {
            $ext = substr($ext, 0, strpos($ext, '?'));
        }
        
        // Default to mp3 if no valid extension
        if (!in_array($ext, ['mp3', 'wav', 'ogg', 'mp4', 'm4a', 'flac'])) {
            $ext = 'mp3';
        }
        
        $filename = md5($url) . '.' . $ext;
        Debug::log('Demo filename generated', ['filename' => $filename]);
        return $filename;
    }
    
    /**
     * Truncate file to percentage (simple implementation)
     * 
     * @param string $filePath File to truncate
     * @param int $percent Percentage to keep
     * @return bool Success status
     */
    public function truncateFile(string $filePath, int $percent): bool {
        Debug::log('Entering truncateFile()', ['filePath' => $filePath, 'percent' => $percent]);
        if (!file_exists($filePath) || !is_readable($filePath)) {
            Debug::log('truncateFile: file not found or unreadable', []);
            return false;
        }
        
        $filesize = filesize($filePath);
        $newSize = (int) floor($filesize * ($percent / 100));
        
        // Create truncated copy
        $tempFile = $filePath . '.tmp';
        
        try {
            $source = fopen($filePath, 'rb');
            $dest = fopen($tempFile, 'wb');
            
            $written = 0;
            while (!feof($source) && $written < $newSize) {
                $chunkSize = min(8192, $newSize - $written);
                $chunk = fread($source, (int)$chunkSize);
                fwrite($dest, $chunk);
                $written += strlen($chunk);
            }
            
            fclose($source);
            fclose($dest);
            
            // Replace original with truncated version
            if (file_exists($tempFile)) {
                unlink($filePath);
                rename($tempFile, $filePath);
                Debug::log('File truncated', ['filePath' => $filePath, 'percent' => $percent]);
                return true;
            }
        } catch (\Exception $e) {
            Debug::log('truncateFile: exception', ['error' => $e->getMessage()]);
            error_log('BFP truncateFile error: ' . $e->getMessage());
            if (file_exists($tempFile)) {
                @unlink($tempFile);
            }
        }
        
        Debug::log('truncateFile: failed', []);
        return false;
    }
    
    /**
     * Get or create demo file
     * 
     * @param string $originalPath Original file path
     * @param int $percent Percentage for demo
     * @return string Demo file path
     */
    public function getDemoFile(string $originalPath, int $percent): string {
        Debug::log('Entering getDemoFile()', ['originalPath' => $originalPath, 'percent' => $percent]);
        $demoFileName = 'demo_' . $percent . '_' . $this->generateDemoFileName($originalPath);
        $filesDirectoryPath = $this->fileManager->getFilesDirectoryPath();
        $demoPath = $filesDirectoryPath . $demoFileName;
        
        // Check if demo already exists
        if (file_exists($demoPath)) {
            Debug::log('getDemoFile: exists', ['demoPath' => $demoPath]);
            return $demoPath;
        }
        
        // Create demo file
        if ($this->createDemoFile($originalPath, $demoPath)) {
            // Truncate to percentage
            $this->truncateFile($demoPath, $percent);
            Debug::log('Demo file created or fetched', ['demoPath' => $demoPath, 'originalPath' => $originalPath, 'percent' => $percent]);
            return $demoPath;
        }
        
        // Return original if demo creation failed
        Debug::log('getDemoFile: failed, returning original', ['originalPath' => $originalPath]);
        return $originalPath;
    }
    
    /**
     * Create demo file from source
     * 
     * @param string $sourceUrl Source file URL
     * @param string $destPath Destination path
     * @return bool Success status
     */
    public function createDemoFile(string $sourceUrl, string $destPath): bool {
        Debug::log('Entering createDemoFile()', ['sourceUrl' => $sourceUrl, 'destPath' => $destPath]);
        // Process cloud URLs
        $sourceUrl = $this->fileManager->processCloudUrl($sourceUrl);
        
        // Check if source is local
        $localPath = $this->fileManager->isLocal($sourceUrl);
        
        if ($localPath && file_exists($localPath)) {
            // Copy local file
            $result = copy($localPath, $destPath);
            Debug::log('createDemoFile: local copy', ['result' => $result]);
            return $result;
        }
        
        // Download remote file
        $response = wp_remote_get($sourceUrl, [
            'timeout' => 300,
            'stream' => true,
            'filename' => $destPath
        ]);
        
        if (is_wp_error($response)) {
            Debug::log('createDemoFile: download error', ['error' => $response->get_error_message()]);
            return false;
        }
        
        $result = file_exists($destPath) && filesize($destPath) > 0;        
        Debug::log('createDemoFile: download result', ['result' => $result]);
        Debug::log('Demo file created', ['sourceUrl' => $sourceUrl, 'destPath' => $destPath]);
        return $result;
    }
    
    /**
     * Check if demo file is valid
     * 
     * @param string $filePath Path to the demo file
     * @return bool True if valid, false otherwise
     */
    public function isValidDemo(string $filePath): bool {
        Debug::log('isValidDemo()', ['filePath' => $filePath]);
        if (!file_exists($filePath) || filesize($filePath) == 0) {
            Debug::log('isValidDemo: file missing or empty', []);
            return false;
        }
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME);
            $isText = substr(finfo_file($finfo, $filePath), 0, 4) === 'text';
            Debug::log('isValidDemo: finfo', ['isText' => $isText]);
            return !$isText;
        }
        Debug::log('isValidDemo: fallback true', []);
        return true;
    }
}

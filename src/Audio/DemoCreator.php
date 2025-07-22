<?php
declare(strict_types=1);

namespace Bandfront\Audio;

use Bandfront\Core\Config;
use Bandfront\Storage\FileManager;
use Bandfront\Utils\Debug;
use Bandfront\Utils\Cloud;

// Set domain for Audio
Debug::domain('audio');

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
        
        // Check if demos are enabled using simple structure
        $demosConfig = $this->config->getState('_bfp_demos');
        $demosEnabled = $demosConfig['enabled'] ?? false;
        if (!$demosEnabled) {
            Debug::log('DemoCreator: Demos disabled, skipping creation', []);
            return 0;
        }
        
        // Get demo settings from simple structure
        $demoPercent = $demosConfig['duration_percent'] ?? 30;
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
            $demoPath = $this->getDemoFile($originalPath, $demoPercent, $productId);
            
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
     * Delete demo files for a product
     */
    public function deleteDemoFiles(int $productId): void {
        Debug::log('Entering deleteDemoFiles()', ['productId' => $productId]);
        
        $filesDirectoryPath = $this->fileManager->getFilesDirectoryPath();
        $productDemoDir = $filesDirectoryPath . "demos/{$productId}/";
        
        // Delete the entire demo directory if it exists
        if (file_exists($productDemoDir) && is_dir($productDemoDir)) {
            $files = glob($productDemoDir . '*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    Debug::log('deleteDemoFiles: deleting file', ['fileName' => basename($file)]);
                    @unlink($file);
                }
            }
            // Remove the directory if it's empty
            @rmdir($productDemoDir);
            Debug::log('deleteDemoFiles: deleted demo directory', ['dir' => $productDemoDir]);
        }
        
        Debug::log('Demo files deleted for product', ['productId' => $productId]);
    }
    
    /**
     * Delete all demo files for all products
     * 
     * @return array Results with count of deleted files and any errors
     */
    public function deleteAllDemoFiles(): array {
        Debug::log('Entering deleteAllDemoFiles()', []);
        
        $filesDirectoryPath = $this->fileManager->getFilesDirectoryPath();
        $demosDirectory = $filesDirectoryPath . 'demos/';
        
        $results = [
            'deleted_files' => 0,
            'deleted_directories' => 0,
            'errors' => []
        ];
        
        // Check if demos directory exists
        if (!file_exists($demosDirectory) || !is_dir($demosDirectory)) {
            Debug::log('deleteAllDemoFiles: demos directory does not exist', ['dir' => $demosDirectory]);
            return $results;
        }
        
        // Get all product directories in demos folder
        $productDirs = glob($demosDirectory . '*', GLOB_ONLYDIR);
        
        foreach ($productDirs as $productDir) {
            $productId = basename($productDir);
            Debug::log('deleteAllDemoFiles: processing product directory', ['productId' => $productId, 'dir' => $productDir]);
            
            try {
                // Delete all files in the product directory
                $files = glob($productDir . '/*');
                foreach ($files as $file) {
                    if (is_file($file)) {
                        if (@unlink($file)) {
                            $results['deleted_files']++;
                            Debug::log('deleteAllDemoFiles: deleted file', ['file' => basename($file)]);
                        } else {
                            $results['errors'][] = "Failed to delete file: " . basename($file);
                        }
                    }
                }
                
                // Remove the product directory
                if (@rmdir($productDir)) {
                    $results['deleted_directories']++;
                    Debug::log('deleteAllDemoFiles: deleted directory', ['dir' => $productDir]);
                } else {
                    $results['errors'][] = "Failed to delete directory: " . $productId;
                }
                
            } catch (\Exception $e) {
                $results['errors'][] = "Error processing product {$productId}: " . $e->getMessage();
                Debug::log('deleteAllDemoFiles: error processing product', [
                    'productId' => $productId,
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        // Try to remove the main demos directory if it's empty
        if (empty(glob($demosDirectory . '*'))) {
            if (@rmdir($demosDirectory)) {
                Debug::log('deleteAllDemoFiles: removed empty demos directory', ['dir' => $demosDirectory]);
            }
        }
        
        Debug::log('deleteAllDemoFiles: completed', $results);
        return $results;
    }
    
    /**
     * Generate demo file name from URL
     * 
     * @param string $url Source URL
     * @param int $productId Product ID for organization
     * @return string Generated filename with path
     */
    public function generateDemoFileName(string $url, int $productId = 0): string {
        Debug::log('generateDemoFileName()', ['url' => $url, 'productId' => $productId]);
        
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
        
        // Get the base filename from URL (much more readable than MD5)
        $basename = pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_FILENAME);
        if (empty($basename)) {
            $basename = md5($url); // Fallback to MD5 if we can't get a name
        }
        
        // Clean the basename for filesystem safety
        $basename = preg_replace('/[^a-zA-Z0-9\-_]/', '_', $basename);
        $basename = substr($basename, 0, 50); // Limit length
        
        // If we have a product ID, organize by product
        if ($productId > 0) {
            $filename = "demos/{$productId}/{$basename}_demo.{$ext}";
        } else {
            $filename = "{$basename}_demo.{$ext}";
        }
        
        Debug::log('🎯 DEMO FILENAME GENERATED', [
            'original_url' => $url,
            'product_id' => $productId,
            'basename' => $basename,
            'extension' => $ext,
            'generated_filename' => $filename
        ]);
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
     * @param int $productId Product ID for organization
     * @return string Demo file path
     */
    public function getDemoFile(string $originalPath, int $percent, int $productId = 0): string {
        Debug::log('Entering getDemoFile()', ['originalPath' => $originalPath, 'percent' => $percent, 'productId' => $productId]);
        
        $demoFileName = $this->generateDemoFileName($originalPath, $productId);
        $filesDirectoryPath = $this->fileManager->getFilesDirectoryPath();
        $demoPath = $filesDirectoryPath . $demoFileName;
        
        Debug::log('📁 DEMO FILE PATH GENERATED', [
            'demo_file_name' => $demoFileName,
            'full_demo_path' => $demoPath,
            'original_path' => $originalPath
        ]);
        
        // Create demo directory if it doesn't exist
        $demoDir = dirname($demoPath);
        if (!file_exists($demoDir)) {
            wp_mkdir_p($demoDir);
            Debug::log('Created demo directory', ['dir' => $demoDir]);
        }
        
        // Check if demo already exists
        if (file_exists($demoPath)) {
            Debug::log('✅ DEMO FILE EXISTS - REUSING', ['demoPath' => $demoPath]);
            return $demoPath;
        }
        
        // Create demo file
        Debug::log('🔨 CREATING NEW DEMO FILE', ['originalPath' => $originalPath, 'demoPath' => $demoPath]);
        if ($this->createDemoFile($originalPath, $demoPath)) {
            // Truncate to percentage
            Debug::log('✂️ TRUNCATING DEMO FILE', ['demoPath' => $demoPath, 'percent' => $percent]);
            $this->truncateFile($demoPath, $percent);
            Debug::log('✅ DEMO FILE SUCCESSFULLY CREATED', ['demoPath' => $demoPath, 'originalPath' => $originalPath, 'percent' => $percent]);
            return $demoPath;
        }
        
        // Return original if demo creation failed
        Debug::log('❌ DEMO CREATION FAILED - RETURNING ORIGINAL', ['originalPath' => $originalPath]);
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
        Debug::log('🔨 STARTING DEMO FILE CREATION', ['sourceUrl' => $sourceUrl, 'destPath' => $destPath]);
        
        // Process cloud URLs
        $sourceUrl = $this->fileManager->processCloudUrl($sourceUrl);
        
        // Check if source is local
        $localPath = $this->fileManager->isLocal($sourceUrl);
        
        if ($localPath && file_exists($localPath)) {
            // Copy local file
            Debug::log('📁 COPYING LOCAL FILE FOR DEMO', ['localPath' => $localPath, 'destPath' => $destPath]);
            $result = copy($localPath, $destPath);
            Debug::log($result ? '✅ LOCAL DEMO COPY SUCCESS' : '❌ LOCAL DEMO COPY FAILED', ['result' => $result]);
            return $result;
        }
        
        // Download remote file
        Debug::log('🌐 DOWNLOADING REMOTE FILE FOR DEMO', ['sourceUrl' => $sourceUrl, 'destPath' => $destPath]);
        $response = wp_remote_get($sourceUrl, [
            'timeout' => 300,
            'stream' => true,
            'filename' => $destPath
        ]);
        
        if (is_wp_error($response)) {
            Debug::log('❌ DEMO DOWNLOAD ERROR', ['error' => $response->get_error_message()]);
            return false;
        }
        
        $result = file_exists($destPath) && filesize($destPath) > 0;
        Debug::log($result ? '✅ DEMO DOWNLOAD SUCCESS' : '❌ DEMO DOWNLOAD FAILED', [
            'result' => $result,
            'file_size' => file_exists($destPath) ? filesize($destPath) : 0
        ]);
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

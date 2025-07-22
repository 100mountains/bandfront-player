<?php
declare(strict_types=1);

namespace Bandfront\Audio;

use Bandfront\Core\Config;
use Bandfront\Storage\FileManager;
use Bandfront\Utils\Debug;

// Set domain for Audio
Debug::domain('audio');

/**
 * Preview Generation
 * 
 * Handles preview/demo file generation for audio tracks
 * 
 * @package Bandfront\Audio
 * @since 2.0.0
 */
class Preview {
    
    private Config $config;
    private Audio $audio;
    private FileManager $fileManager;
    private DemoCreator $demoCreator;
    
    /**
     * Constructor
     */
    public function __construct(Config $config, Audio $audio, FileManager $fileManager) {
        $this->config = $config;
        $this->audio = $audio;
        $this->fileManager = $fileManager;
    }
    
    /**
     * Initialize preview generation
     */
    public function init(): void {
        // Hook registration handled by Hooks.php
    }
    
    /**
     * Generate preview for a file
     * 
     * @param string $sourceFile Source file path
     * @param int $productId Product ID
     * @param int $percent Percentage to keep
     * @return string|false Preview file path or false
     */
    public function generatePreview(string $sourceFile, int $productId, int $percent): string|false {
        $processor = new Processor($this->config, $this->fileManager);
        
        $previewPath = $this->getPreviewPath($sourceFile, $productId);
        $tempPath = $previewPath . '.tmp';
        
        // Copy source to temp location
        if (!copy($sourceFile, $tempPath)) {
            return false;
        }
        
        // Process the file
        $fileInfo = [
            'filePath' => $previewPath,
            'oFilePath' => $tempPath,
            'fileName' => basename($previewPath),
            'oFileName' => basename($tempPath)
        ];
        
        $args = [
            'file_percent' => $percent,
            'product_id' => $productId,
            'url' => $sourceFile
        ];
        
        if ($processor->processSecureAudio($fileInfo, $args)) {
            return $fileInfo['filePath'];
        }
        
        return false;
    }
    
    /**
     * Get preview file path
     */
    private function getPreviewPath(string $sourceFile, int $productId): string {
        $filename = pathinfo($sourceFile, PATHINFO_FILENAME);
        $ext = pathinfo($sourceFile, PATHINFO_EXTENSION);
        
        $previewDir = $this->fileManager->getFilesDirectoryPath() . '/previews/' . $productId;
        wp_mkdir_p($previewDir);
        
        return $previewDir . '/' . $filename . '_preview.' . $ext;
    }
}

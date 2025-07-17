<?php
declare(strict_types=1);

namespace Bandfront\Storage;

use Bandfront\Core\Config;
use Bandfront\Utils\Debug;
use Bandfront\Utils\Cloud;

class FileManager {
    
    private Config $config;
    private ?Cloud $cloud = null;
    
    /**
     * Constructor
     */
    public function __construct(Config $config) {
        $this->config = $config;
    }
    
    /**
     * Set cloud handler
     */
    public function setCloudHandler(Cloud $cloud): void {
        $this->cloud = $cloud;
    }
    
    /**
     * Check if URL is valid audio URL
     */
    public function isValidAudioUrl(string $url): bool {
        // Check if it's a cloud URL first
        if ($this->cloud && $this->cloud->isCloudUrl($url)) {
            return true;
        }
        
        // Existing validation logic...
    }
    
    /**
     * Process URL for downloading
     */
    public function processUrlForDownload(string $url): string {
        // Handle cloud URLs
        if ($this->cloud && $this->cloud->isCloudUrl($url)) {
            return $this->cloud->getDownloadUrl($url);
        }
        
        // Existing processing logic...
        return $url;
    }
    
    // ...existing methods...
}
<?php
declare(strict_types=1);

namespace Bandfront\Storage;

use Bandfront\Core\Config;
use Bandfront\Utils\Debug;

/**
 * Cloud Storage Handler
 * 
 * Handles cloud storage URL processing and file operations
 * for various cloud providers (Google Drive, Dropbox, S3, Azure)
 * 
 * @package Bandfront\Storage
 * @since 2.0.0
 */
class Cloud {
    
    private Config $config;
    
    /**
     * Constructor
     */
    public function __construct(Config $config) {
        $this->config = $config;
    }
    
    /**
     * Get Google Drive download URL
     * 
     * @param string $url Google Drive URL
     * @return string Direct download URL
     */
    public function getGoogleDriveDownloadUrl(string $url): string {
        // Match different possible Google Drive URL patterns
        $patterns = [
            '/drive\.google\.com\/file\/d\/([a-zA-Z0-9_-]+)/i', // format: /file/d/FILE_ID/
            '/drive\.google\.com\/open\?id=([a-zA-Z0-9_-]+)/i', // format: /open?id=FILE_ID
            '/drive\.google\.com\/uc\?id=([a-zA-Z0-9_-]+)/i'    // format: /uc?id=FILE_ID
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $url, $matches)) {
                $fileId = $matches[1];
                Debug::log('Cloud: Found Google Drive file ID', ['fileId' => $fileId]); // DEBUG-REMOVE
                return "https://drive.google.com/uc?export=download&id={$fileId}";
            }
        }

        // Return original URL if it's not a recognized format
        return $url;
    }
    
    /**
     * Get Google Drive file name from URL
     * 
     * @param string $url Google Drive URL
     * @return string File name
     */
    public function getGoogleDriveFileName(string $url): string {
        $downloadUrl = $this->getGoogleDriveDownloadUrl($url);
        $pattern = '/drive\.google\.com\/uc\?export\=download&id\=[a-zA-Z0-9_-]+/i';

        try {
            if (preg_match($pattern, $downloadUrl, $matches)) {
                // Trying to obtain the file information directly from Google Drive.
                $response = wp_remote_head($downloadUrl, [
                    'redirection' => 5,
                    'timeout'     => 15,
                ]);

                if (!is_wp_error($response)) {
                    $headers = wp_remote_retrieve_headers($response);

                    // Check for Content-Disposition header
                    if (!empty($headers['content-disposition'])) {
                        if (preg_match('/filename="([^"]+)"/', $headers['content-disposition'], $matches)) {
                            Debug::log('Cloud: Retrieved Google Drive filename', ['filename' => $matches[1]]); // DEBUG-REMOVE
                            return $matches[1];
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            Debug::log('Cloud: Error getting Google Drive filename', ['error' => $e->getMessage()]); // DEBUG-REMOVE
        }
        
        return basename($url);
    }
    
    /**
     * Check if URL is a cloud storage URL
     * 
     * @param string $url URL to check
     * @return bool True if cloud URL
     */
    public function isCloudUrl(string $url): bool {
        $cloudPatterns = [
            '/drive\.google\.com/i',
            '/dropbox\.com/i',
            '/s3[\.-].*\.amazonaws\.com/i',
            '/\.blob\.core\.windows\.net/i',
        ];
        
        foreach ($cloudPatterns as $pattern) {
            if (preg_match($pattern, $url)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Get appropriate download URL based on cloud provider
     * 
     * @param string $url Original URL
     * @return string Download URL
     */
    public function getDownloadUrl(string $url): string {
        // Google Drive
        if (preg_match('/drive\.google\.com/i', $url)) {
            return $this->getGoogleDriveDownloadUrl($url);
        }
        
        // Dropbox
        if (preg_match('/dropbox\.com/i', $url)) {
            return $this->getDropboxDownloadUrl($url);
        }
        
        // S3
        if (preg_match('/s3[\.-].*\.amazonaws\.com/i', $url)) {
            return $this->getS3DownloadUrl($url);
        }
        
        // Azure Blob Storage
        if (preg_match('/\.blob\.core\.windows\.net/i', $url)) {
            return $this->getAzureDownloadUrl($url);
        }
        
        return $url;
    }
    
    /**
     * Get Dropbox download URL
     * 
     * @param string $url Dropbox URL
     * @return string Direct download URL
     */
    private function getDropboxDownloadUrl(string $url): string {
        // Convert Dropbox sharing links to direct download links
        if (strpos($url, 'dl=0') !== false) {
            return str_replace('dl=0', 'dl=1', $url);
        }
        
        // Already a direct link
        if (strpos($url, 'dl=1') !== false) {
            return $url;
        }
        
        // Add dl parameter
        $separator = (strpos($url, '?') !== false) ? '&' : '?';
        return $url . $separator . 'dl=1';
    }
    
    /**
     * Get S3 download URL (presigned if needed)
     * 
     * @param string $url S3 URL
     * @return string Download URL
     */
    private function getS3DownloadUrl(string $url): string {
        $s3Settings = $this->config->getState('_bfp_cloud_s3', []);
        
        // If S3 is not configured, return original URL
        if (empty($s3Settings['enabled']) || empty($s3Settings['access_key'])) {
            return $url;
        }
        
        // TODO: Implement S3 presigned URL generation if needed
        // For now, return the original URL
        return $url;
    }
    
    /**
     * Get Azure Blob Storage download URL
     * 
     * @param string $url Azure URL
     * @return string Download URL
     */
    private function getAzureDownloadUrl(string $url): string {
        $azureSettings = $this->config->getState('_bfp_cloud_azure', []);
        
        // If Azure is not configured, return original URL
        if (empty($azureSettings['enabled']) || empty($azureSettings['account_key'])) {
            return $url;
        }
        
        // TODO: Implement Azure SAS token generation if needed
        // For now, return the original URL
        return $url;
    }
    
    /**
     * Upload file to cloud storage
     * 
     * @param string $localPath Local file path
     * @param string $provider Cloud provider (google-drive, dropbox, s3, azure)
     * @param string $remotePath Remote path/filename
     * @return string|false Cloud URL or false on failure
     */
    public function uploadFile(string $localPath, string $provider, string $remotePath): string|false {
        if (!file_exists($localPath)) {
            Debug::log('Cloud: Local file not found for upload', ['path' => $localPath]); // DEBUG-REMOVE
            return false;
        }
        
        switch ($provider) {
            case 'google-drive':
                return $this->uploadToGoogleDrive($localPath, $remotePath);
                
            case 'dropbox':
                return $this->uploadToDropbox($localPath, $remotePath);
                
            case 's3':
                return $this->uploadToS3($localPath, $remotePath);
                
            case 'azure':
                return $this->uploadToAzure($localPath, $remotePath);
                
            default:
                Debug::log('Cloud: Unknown provider', ['provider' => $provider]); // DEBUG-REMOVE
                return false;
        }
    }
    
    /**
     * Upload to Google Drive
     * 
     * @param string $localPath Local file path
     * @param string $remotePath Remote path
     * @return string|false URL or false
     */
    private function uploadToGoogleDrive(string $localPath, string $remotePath): string|false {
        // Check if Google Drive is configured
        $driveSettings = get_option('_bfp_cloud_drive_addon', []);
        if (empty($driveSettings['_bfp_drive']) || empty($driveSettings['_bfp_drive_key'])) {
            Debug::log('Cloud: Google Drive not configured'); // DEBUG-REMOVE
            return false;
        }
        
        // TODO: Implement Google Drive API upload
        // This would require OAuth2 authentication and the Google API client
        Debug::log('Cloud: Google Drive upload not implemented'); // DEBUG-REMOVE
        return false;
    }
    
    /**
     * Upload to Dropbox
     * 
     * @param string $localPath Local file path
     * @param string $remotePath Remote path
     * @return string|false URL or false
     */
    private function uploadToDropbox(string $localPath, string $remotePath): string|false {
        $dropboxSettings = $this->config->getState('_bfp_cloud_dropbox', []);
        
        if (empty($dropboxSettings['enabled']) || empty($dropboxSettings['access_token'])) {
            Debug::log('Cloud: Dropbox not configured'); // DEBUG-REMOVE
            return false;
        }
        
        // TODO: Implement Dropbox API upload
        Debug::log('Cloud: Dropbox upload not implemented'); // DEBUG-REMOVE
        return false;
    }
    
    /**
     * Upload to S3
     * 
     * @param string $localPath Local file path
     * @param string $remotePath Remote path
     * @return string|false URL or false
     */
    private function uploadToS3(string $localPath, string $remotePath): string|false {
        $s3Settings = $this->config->getState('_bfp_cloud_s3', []);
        
        if (empty($s3Settings['enabled']) || empty($s3Settings['access_key'])) {
            Debug::log('Cloud: S3 not configured'); // DEBUG-REMOVE
            return false;
        }
        
        // TODO: Implement S3 upload using AWS SDK
        Debug::log('Cloud: S3 upload not implemented'); // DEBUG-REMOVE
        return false;
    }
    
    /**
     * Upload to Azure
     * 
     * @param string $localPath Local file path
     * @param string $remotePath Remote path
     * @return string|false URL or false
     */
    private function uploadToAzure(string $localPath, string $remotePath): string|false {
        $azureSettings = $this->config->getState('_bfp_cloud_azure', []);
        
        if (empty($azureSettings['enabled']) || empty($azureSettings['account_key'])) {
            Debug::log('Cloud: Azure not configured'); // DEBUG-REMOVE
            return false;
        }
        
        // TODO: Implement Azure Blob Storage upload
        Debug::log('Cloud: Azure upload not implemented'); // DEBUG-REMOVE
        return false;
    }
    
    /**
     * Get configured cloud providers
     * 
     * @return array List of enabled providers
     */
    public function getEnabledProviders(): array {
        $providers = [];
        
        // Google Drive (legacy addon)
        $driveSettings = get_option('_bfp_cloud_drive_addon', []);
        if (!empty($driveSettings['_bfp_drive'])) {
            $providers[] = 'google-drive';
        }
        
        // Dropbox
        $dropboxSettings = $this->config->getState('_bfp_cloud_dropbox', []);
        if (!empty($dropboxSettings['enabled'])) {
            $providers[] = 'dropbox';
        }
        
        // S3
        $s3Settings = $this->config->getState('_bfp_cloud_s3', []);
        if (!empty($s3Settings['enabled'])) {
            $providers[] = 's3';
        }
        
        // Azure
        $azureSettings = $this->config->getState('_bfp_cloud_azure', []);
        if (!empty($azureSettings['enabled'])) {
            $providers[] = 'azure';
        }
        
        return $providers;
    }
}

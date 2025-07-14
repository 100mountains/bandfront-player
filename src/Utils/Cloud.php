<?php
namespace bfp\Utils;

/**
 * Cloud Tools for Bandfront Player
 *
 * @package BandfrontPlayer
 * @since 0.1
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Cloud Tools Class
 * Handles cloud storage URL processing
 */
class Cloud {
    
    /**
     * Get Google Drive download URL
     */
    public static function getGoogleDriveDownloadUrl(string $url): string {
        // Match different possible Google Drive URL patterns
        $patterns = [
            '/drive\.google\.com\/file\/d\/([a-zA-Z0-9_-]+)/i', // format: /file/d/FILE_ID/
            '/drive\.google\.com\/open\?id=([a-zA-Z0-9_-]+)/i', // format: /open?id=FILE_ID
            '/drive\.google\.com\/uc\?id=([a-zA-Z0-9_-]+)/i'    // format: /uc?id=FILE_ID
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $url, $matches)) {
                $fileId = $matches[1];
                return "https://drive.google.com/uc?export=download&id={$fileId}";
            }
        }

        // Return original URL if it's not a recognized format
        return $url;
    }
    
    /**
     * Get Google Drive file name from URL
     */
    public static function getGoogleDriveFileName(string $url): string {
        $downloadUrl = self::getGoogleDriveDownloadUrl($url);
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
                            return $matches[1];
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            // Log error if needed
            error_log('BFP Cloud: Error getting Google Drive filename - ' . $e->getMessage());
        }
        
        return basename($url);
    }
}


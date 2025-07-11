<?php
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
 * BFP Cloud Tools Class
 * Handles cloud storage URL processing
 */
class BFP_Cloud_Tools {
    
    /**
     * Get Google Drive download URL
     */
    public static function get_google_drive_download_url($url) {
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
    public static function get_google_drive_file_name($url) {
        $download_url = self::get_google_drive_download_url($url);
        $pattern = '/drive\.google\.com\/uc\?export\=download&id\=[a-zA-Z0-9_-]+/i';

        try {
            if (preg_match($pattern, $download_url, $matches)) {
                // Trying to obtain the file information directly from Google Drive.
                $response = wp_remote_head($download_url, [
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
        } catch (Exception $e) {
            // Log error if needed
        }
        
        return basename($url);
    }
}

// Maintain backward compatibility
if (!class_exists('BandfrontPlayerTools')) {
    class BandfrontPlayerTools extends BFP_Cloud_Tools {}
}

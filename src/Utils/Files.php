<?php
namespace bfp\Utils;

use bfp\Plugin;

/**
 * File handling functionality for Bandfront Player
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * File Handler Class
 */
class Files {
    
    private Plugin $mainPlugin;
    private string $filesDirectoryPath;
    private string $filesDirectoryUrl;
    
    public function __construct(Plugin $mainPlugin) {
        $this->mainPlugin = $mainPlugin;
        $this->createDirectories();
    }
    
    /**
     * Create directories for file storage
     */
    public function createDirectories(): void {
        // Generate upload dir
        $filesDirectory = wp_upload_dir();
        $this->filesDirectoryPath = rtrim($filesDirectory['basedir'], '/') . '/bfp/';
        $this->filesDirectoryUrl = rtrim($filesDirectory['baseurl'], '/') . '/bfp/';
        $this->filesDirectoryUrl = preg_replace('/^http(s)?:\/\//', '//', $this->filesDirectoryUrl);
        
        if (!file_exists($this->filesDirectoryPath)) {
            @mkdir($this->filesDirectoryPath, 0755);
        }

        if (is_dir($this->filesDirectoryPath)) {
            if (!file_exists($this->filesDirectoryPath . '.htaccess')) {
                try {
                    file_put_contents($this->filesDirectoryPath . '.htaccess', 'Options -Indexes');
                } catch (\Exception $err) {}
            }
        }

        if (!file_exists($this->filesDirectoryPath . 'purchased/')) {
            @mkdir($this->filesDirectoryPath . 'purchased/', 0755);
        }
    }
    
    /**
     * Clear directory contents
     */
    public function clearDir(string $dirPath): void {
        try {
            if (empty($dirPath) || !file_exists($dirPath) || !is_dir($dirPath)) {
                return;
            }
            $dirPath = rtrim($dirPath, '\\/') . '/';
            $files = glob($dirPath . '*', GLOB_MARK);
            foreach ($files as $file) {
                if (is_dir($file)) {
                    $this->clearDir($file);
                } else {
                    unlink($file);
                }
            }
        } catch (\Exception $err) {
            return;
        }
    }
    
    /**
     * Get files directory path
     */
    public function getFilesDirectoryPath(): string {
        return $this->filesDirectoryPath;
    }
    
    /**
     * Get files directory URL
     */
    public function getFilesDirectoryUrl(): string {
        return $this->filesDirectoryUrl;
    }
    
    /**
     * Delete post-related files and meta data
     */
    public function deletePost(int $postId, bool $demosOnly = false, bool $force = false): void {
        $post = get_post($postId);
        $postTypes = $this->mainPlugin->getPostTypes();
        if (
            isset($post) &&
            (
                !$force ||
                !in_array($post->post_type, $postTypes) ||
                !current_user_can('edit_post', $postId)
            )
        ) {
            return;
        }

        // Delete truncated version of the audio file
        $this->deleteTruncatedFiles($postId);

        if (!$demosOnly) {
            delete_post_meta($postId, '_bfp_enable_player');
            delete_post_meta($postId, '_bfp_show_in');
            delete_post_meta($postId, '_bfp_merge_in_grouped');
            delete_post_meta($postId, '_bfp_player_layout');
            delete_post_meta($postId, '_bfp_player_volume');
            delete_post_meta($postId, '_bfp_single_player');
            delete_post_meta($postId, '_bfp_secure_player');
            delete_post_meta($postId, '_bfp_file_percent');
            delete_post_meta($postId, '_bfp_player_controls');
            delete_post_meta($postId, '_bfp_player_title');
            delete_post_meta($postId, '_bfp_preload');
            delete_post_meta($postId, '_bfp_play_all');
            delete_post_meta($postId, '_bfp_loop');
            delete_post_meta($postId, '_bfp_on_cover');

            delete_post_meta($postId, '_bfp_playback_counter');
        }

        delete_post_meta($postId, '_bfp_own_demos');
        delete_post_meta($postId, '_bfp_direct_own_demos');
        delete_post_meta($postId, '_bfp_demos_list');

        do_action('bfp_delete_post', $postId);
    }
    
    /**
     * Delete purchased files based on reset interval
     */
    public function deletePurchasedFiles(): void {
        // Use getState for single value retrieval
        if ($this->mainPlugin->getConfig()->getState('_bfp_reset_purchased_interval', 'daily') == 'daily') {
            $this->clearDir($this->filesDirectoryPath . 'purchased/');
            $this->createDirectories();
        }
    }
    
    /**
     * Clear expired transients to maintain cache
     */
    public function clearExpiredTransients(): void {
        $transient = get_transient('bfp_clear_expired_transients');
        if (!$transient || 24 * 60 * 60 <= time() - intval($transient)) {
            set_transient('bfp_clear_expired_transients', time());
            delete_expired_transients();
        }
    }
    
    /**
     * Delete truncated demo files for a product
     */
    public function deleteTruncatedFiles(int $productId): void {
        $filesArr = get_post_meta($productId, '_downloadable_files', true);
        $ownFilesArr = get_post_meta($productId, '_bfp_demos_list', true);
        if (!is_array($filesArr)) {
            $filesArr = [$filesArr];
        }
        if (is_array($ownFilesArr) && !empty($ownFilesArr)) {
            $filesArr = array_merge($filesArr, $ownFilesArr);
        }

        if (!empty($filesArr) && is_array($filesArr)) {
            foreach ($filesArr as $file) {
                if (is_array($file) && !empty($file['file'])) {
                    $ext = pathinfo($file['file'], PATHINFO_EXTENSION);
                    $fileName = md5($file['file']) . ((!empty($ext)) ? '.' . $ext : '');
                    if (file_exists($this->filesDirectoryPath . $fileName)) {
                        @unlink($this->filesDirectoryPath . $fileName);
                    }
                    do_action('bfp_delete_file', $productId, $file['file']);
                }
            }
        }
    }
    
    /**
     * Get product files with filtering options
     * 
     * @param array $args Arguments for file retrieval
     * @return array Filtered audio files
     */
    public function getProductFilesInternal(array $args): array {
        if (empty($args['product'])) {
            return [];
        }

        $product = $args['product'];
        $files = $this->getAllProductFiles($product, []);
        if (empty($files)) {
            return [];
        }

        $audioFiles = [];
        foreach ($files as $index => $file) {
            // Changed from $this->mainPlugin->getAudioCore()->isAudio() to $this->isAudio()
            if (!empty($file['file']) && false !== ($mediaType = $this->isAudio($file['file']))) {
                $file['media_type'] = $mediaType;

                if (isset($args['file_id'])) {
                    if ($args['file_id'] == $index) {
                        $audioFiles[$index] = $file;
                        return $audioFiles;
                    }
                } elseif (!empty($args['first'])) {
                    $audioFiles[$index] = $file;
                    return $audioFiles;
                } elseif (!empty($args['all'])) {
                    $audioFiles[$index] = $file;
                }
            }
        }
        return $audioFiles;
    }
    
    /**
     * Get all files from a product recursively (including variations/grouped)
     * 
     * @param mixed $product WooCommerce product object
     * @param array $filesArr Accumulated files array
     * @return array All product files
     */
    public function getAllProductFiles($product, array $filesArr): array {
        if (!is_object($product) || !method_exists($product, 'get_type')) {
            return $filesArr;
        }

        $productType = $product->get_type();
        $id = $product->get_id();
        
        // Check if WooCommerce integration exists before calling its methods
        $purchased = false;
        $woocommerce = $this->mainPlugin->getWooCommerce();
        if ($woocommerce) {
            $purchased = $woocommerce->woocommerceUserProduct($id);
        }

        if ('variation' == $productType) {
            $_files = $product->get_downloads();
            $_files = $this->editFilesArray($id, $_files);
            $filesArr = array_merge($filesArr, $_files);
        } else {
            if (!$this->mainPlugin->getConfig()->getState('_bfp_enable_player', false, $id)) {
                return $filesArr;
            }

            $ownDemos = intval($this->mainPlugin->getConfig()->getState('_bfp_own_demos', 0, $id));
            $files = $this->mainPlugin->getConfig()->getState('_bfp_demos_list', [], $id);
            if (false === $purchased && $ownDemos && !empty($files)) {
                $directOwnDemos = intval($this->mainPlugin->getConfig()->getState('_bfp_direct_own_demos', 0, $id));
                $files = $this->editFilesArray($id, $files, $directOwnDemos);
                $filesArr = array_merge($filesArr, $files);
            } else {
                switch ($productType) {
                    case 'variable':
                    case 'grouped':
                        $children = $product->get_children();

                        foreach ($children as $key => $childId) {
                            $children[$key] = wc_get_product($childId);
                        }

                        uasort($children, [Utils::class, 'sortList']);

                        foreach ($children as $childObj) {
                            $filesArr = $this->getAllProductFiles($childObj, $filesArr);
                        }
                        break;
                    default:
                        $_files = $product->get_downloads();
                        if (empty($_files) && $ownDemos && !empty($files)) {
                            $_files = $this->editFilesArray($id, $files);
                        } else {
                            $_files = $this->editFilesArray($id, $_files);
                        }
                        $filesArr = array_merge($filesArr, $_files);
                        break;
                }
            }
        }
        return $filesArr;
    }
    
    /**
     * Edit files array to add product context
     * 
     * @param int $productId Product ID
     * @param array $files Files array
     * @param int $playSrc Play source flag
     * @return array Modified files array
     */
    public function editFilesArray(int $productId, array $files, int $playSrc = 0): array {
        $pFiles = [];
        foreach ($files as $key => $file) {
            $pKey = $key . '_' . $productId;
            if (gettype($file) == 'object') {
                $file = (array) $file->get_data();
            }
            $file['product'] = $productId;
            $file['play_src'] = $playSrc;
            $pFiles[$pKey] = $file;
        }
        return $pFiles;
    }
    
    /**
     * Get product files - public interface
     * 
     * @param int $productId Product ID
     * @return array All audio files for the product
     */
    public function getProductFiles(int $productId): array {
        $product = wc_get_product($productId);
        if (!$product) {
            return [];
        }
        
        return $this->getProductFilesInternal([
            'product' => $product,
            'all' => true
        ]);
    }
    
    /**
     * Get streaming URL for a file (will be replaced by REST)
     * 
     * @param int $productId Product ID
     * @param string $fileIndex File index
     * @return string Streaming URL
     */
    public function getStreamingUrl(int $productId, string $fileIndex): string {
        // Current implementation - will be replaced with REST
        return add_query_arg([
            'bfp-action' => 'play',
            'bfp-product' => $productId,
            'bfp-file' => $fileIndex
        ], site_url());
    }
    
    /**
     * Process cloud storage URLs
     * 
     * @param string $url Original URL
     * @return string Processed URL
     */
    public function processCloudUrl(string $url): string {
        // Google Drive
        if (strpos($url, 'drive.google.com') !== false) {
            preg_match('/\/d\/(.*?)\//', $url, $matches);
            if (!empty($matches[1])) {
                $fileId = $matches[1];
                return "https://drive.google.com/uc?export=download&id={$fileId}";
            }
        }
        
        // Add other cloud storage handlers here
        
        return $url;
    }
    
    /**
     * Get file path for a product file
     * 
     * @param int $productId Product ID
     * @param string $fileIndex File index
     * @return string File path
     */
    public function getFilePath(int $productId, string $fileIndex): string {
        $files = $this->getProductFiles($productId);
        
        if (!isset($files[$fileIndex])) {
            return '';
        }
        
        $fileUrl = $files[$fileIndex]['file'];
        
        // Process cloud URLs if needed
        if (strpos($fileUrl, 'http') === 0) {
            $fileUrl = $this->processCloudUrl($fileUrl);
        }
        
        // Convert URL to path for local files
        if (strpos($fileUrl, site_url()) === 0) {
            $fileUrl = str_replace(site_url('/'), ABSPATH, $fileUrl);
        }
        
        return $fileUrl;
    }
    
    /**
     * Get or create demo file
     * 
     * @param string $originalPath Original file path
     * @param int $percent Percentage of file to include
     * @return string Demo file path
     */
    public function getDemoFile(string $originalPath, int $percent): string {
        // Generate demo filename
        $demoFilename = md5($originalPath) . '.mp3';
        $uploadsDir = wp_upload_dir();
        $demoPath = $uploadsDir['basedir'] . '/bfp-demos/' . $demoFilename;
        
        // Create directory if needed
        wp_mkdir_p(dirname($demoPath));
        
        // Check if demo already exists
        if (file_exists($demoPath) && filesize($demoPath) > 0) {
            return $demoPath;
        }
        
        // Create demo file
        $this->createDemoFile($originalPath, $demoPath, $percent);
        
        return $demoPath;
    }
    
    /**
     * Generate demo file name from URL
     * 
     * @param string $url The URL to generate filename from
     * @return string The generated filename
     */
    public function generateDemoFileName(string $url): string {
        $fileExtension = pathinfo($url, PATHINFO_EXTENSION);
        $fileName = md5($url) . ((!empty($fileExtension) && preg_match('/^[a-z\d]{3,4}$/i', $fileExtension)) ? '.' . $fileExtension : '.mp3');
        return $fileName;
    }
    
    /**
     * Check if demo file is valid
     * 
     * @param string $filePath Path to the demo file
     * @return bool True if valid, false otherwise
     */
    public function isValidDemo(string $filePath): bool {
        if (!file_exists($filePath) || filesize($filePath) == 0) {
            return false;
        }
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME);
            return substr(finfo_file($finfo, $filePath), 0, 4) !== 'text';
        }
        return true;
    }
    
    /**
     * Truncate file to a percentage of its size
     * 
     * @param string $filePath Path to the file
     * @param int $filePercent Percentage to keep
     * @return void
     */
    public function truncateFile(string $filePath, int $filePercent): void {
        $h = fopen($filePath, 'r+');
        ftruncate($h, intval(filesize($filePath) * $filePercent / 100));
        fclose($h);
    }
    
    /**
     * Fix URL for local files
     * 
     * @param string $url URL to fix
     * @return string Fixed URL
     */
    public function fixUrl(string $url): string {
        if (file_exists($url)) {
            return $url;
        }
        if (strpos($url, '//') === 0) {
            $urlFixed = 'http' . (is_ssl() ? 's:' : ':') . $url;
        } elseif (strpos($url, '/') === 0) {
            $urlFixed = rtrim(BFP_WEBSITE_URL, '/') . $url;
        } else {
            $urlFixed = $url;
        }
        return $urlFixed;
    }
    
    /**
     * Check if file is local and return path
     * 
     * @param string $url URL to check
     * @return string|false Local path or false
     */
    public function isLocal(string $url): string|false {
        $filePath = false;
        if (file_exists($url)) {
            $filePath = $url;
        }

        if (false === $filePath) {
            $attachmentId = attachment_url_to_postid($url);
            if ($attachmentId) {
                $attachmentPath = get_attached_file($attachmentId);
                if ($attachmentPath && file_exists($attachmentPath)) {
                    $filePath = $attachmentPath;
                }
            }
        }

        if (false === $filePath && defined('ABSPATH')) {
            $pathComponent = parse_url($url, PHP_URL_PATH);
            $path = rtrim(ABSPATH, '/') . '/' . ltrim($pathComponent, '/');
            if (file_exists($path)) {
                $filePath = $path;
            }

            if (false === $filePath) {
                $siteUrl = get_site_url(get_current_blog_id());
                $filePath = str_ireplace($siteUrl . '/', ABSPATH, $url);
                if (!file_exists($filePath)) {
                    $filePath = false;
                }
            }
        }

        return apply_filters('bfp_is_local', $filePath, $url);
    }
    
    /**
     * Check if the file is an audio file and return its type or false
     * 
     * @param string $filePath File path to check
     * @return string|false Audio type or false
     */
    public function isAudio(string $filePath): string|false {
        $aux = function($filePath) {
            if (preg_match('/\.(mp3|ogg|oga|wav|wma|mp4)$/i', $filePath, $match)) {
                return $match[1];
            }
            if (preg_match('/\.m4a$/i', $filePath)) {
                return 'mp4';
            }
            if ($this->isPlaylist($filePath)) {
                return 'hls';
            }
            return false;
        };

        $fileName = $this->generateDemoFileName($filePath);
        $demoFilePath = $this->filesDirectoryPath . $fileName;
        if ($this->isValidDemo($demoFilePath)) return $aux($demoFilePath);

        $ext = $aux($filePath);
        if ($ext) return $ext;

        // Always handle extensionless files gracefully (smart default)
        $extension = pathinfo($filePath, PATHINFO_EXTENSION);
        if (empty($extension) || !preg_match('/^[a-z\d]{3,4}$/i', $extension)) {
            // Check if it's a cloud URL or has audio MIME type
            if ($this->isCloudUrl($filePath) || $this->hasAudioMimeType($filePath)) {
                return 'mp3';
            }
        }

        return false;
    }
    
    /**
     * Check if the file is a video file and return its type or false
     * 
     * @param string $filePath File path to check
     * @return string|false Video type or false
     */
    public function isVideo(string $filePath): string|false {
        $aux = function($filePath) {
            if (preg_match('/\.(mp4|mov|avi|wmv|mkv)$/i', $filePath, $match)) {
                return $match[1];
            }
            return false;
        };

        $fileName = $this->generateDemoFileName($filePath);
        $demoFilePath = $this->filesDirectoryPath . $fileName;
        if ($this->isValidDemo($demoFilePath)) return $aux($demoFilePath);

        $ext = $aux($filePath);
        if ($ext) return $ext;

        // Smart default for extensionless video files
        $extension = pathinfo($filePath, PATHINFO_EXTENSION);
        if (empty($extension) || !preg_match('/^[a-z\d]{3,4}$/i', $extension)) {
            // Check if it's a cloud URL with video MIME type
            if ($this->isCloudUrl($filePath) && $this->hasVideoMimeType($filePath)) {
                return 'mp4';
            }
        }

        return false;
    }
    
    /**
     * Check if the file is an image file and return its type or false
     * 
     * @param string $filePath File path to check
     * @return string|false Image type or false
     */
    public function isImage(string $filePath): string|false {
        $aux = function($filePath) {
            if (preg_match('/\.(jpg|jpeg|png|gif|bmp|webp)$/i', $filePath, $match)) {
                return $match[1];
            }
            return false;
        };

        $fileName = $this->generateDemoFileName($filePath);
        $demoFilePath = $this->filesDirectoryPath . $fileName;
        if ($this->isValidDemo($demoFilePath)) return $aux($demoFilePath);

        $ext = $aux($filePath);
        if ($ext) return $ext;

        // Smart default for extensionless image files
        $extension = pathinfo($filePath, PATHINFO_EXTENSION);
        if (empty($extension) || !preg_match('/^[a-z\d]{3,4}$/i', $extension)) {
            // Check if it's a cloud URL with image MIME type
            if ($this->isCloudUrl($filePath) && $this->hasImageMimeType($filePath)) {
                return 'jpg';
            }
        }

        return false;
    }
    
    /**
     * Check if the file is a playlist
     * 
     * @param string $filePath File path to check
     * @return bool True if playlist, false otherwise
     */
    public function isPlaylist(string $filePath): bool {
        return preg_match('/\.(m3u|m3u8)$/i', $filePath);
    }
    
    /**
     * Get the correct player type for the file
     * 
     * @param string $filePath File path to check
     * @return string Player type: 'audio', 'video', or 'image'
     */
    public function getPlayerType(string $filePath): string {
        if ($this->isVideo($filePath)) {
            return 'video';
        } elseif ($this->isImage($filePath)) {
            return 'image';
        }
        return 'audio'; // Default to audio player
    }
    
    /**
     * Check if URL is from a cloud service
     * 
     * @param string $url URL to check
     * @return bool True if cloud URL, false otherwise
     */
    private function isCloudUrl(string $url): bool {
        $cloudPatterns = [
            'drive.google.com',
            'dropbox.com',
            'onedrive.live.com',
            's3.amazonaws.com',
            'blob.core.windows.net'
        ];
        
        foreach ($cloudPatterns as $pattern) {
            if (stripos($url, $pattern) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Check if file has audio MIME type
     * 
     * @param string $filePath File path to check
     * @return bool True if audio MIME type, false otherwise
     */
    private function hasAudioMimeType(string $filePath): bool {
        if (!file_exists($filePath)) {
            return false;
        }
        
        if (function_exists('mime_content_type')) {
            $mime = mime_content_type($filePath);
            return strpos($mime, 'audio/') === 0;
        }
        
        return false;
    }
    
    /**
     * Check if file has video MIME type
     * 
     * @param string $filePath File path to check
     * @return bool True if video MIME type, false otherwise
     */
    private function hasVideoMimeType(string $filePath): bool {
        if (!file_exists($filePath)) {
            return false;
        }
        
        if (function_exists('mime_content_type')) {
            $mime = mime_content_type($filePath);
            return strpos($mime, 'video/') === 0;
        }
        
        return false;
    }
    
    /**
     * Check if file has image MIME type
     * 
     * @param string $filePath File path to check
     * @return bool True if image MIME type, false otherwise
     */
    private function hasImageMimeType(string $filePath): bool {
        if (!file_exists($filePath)) {
            return false;
        }
        
        if (function_exists('mime_content_type')) {
            $mime = mime_content_type($filePath);
            return strpos($mime, 'image/') === 0;
        }
        
        return false;
    }
    
    /**
     * Handle OAuth file upload
     * 
     * @param array $uploadedFile Uploaded file info
     * @param string $optionName Option name to save to
     * @return bool Success status
     */
    public function handleOAuthFileUpload(array $uploadedFile, string $optionName): bool {
        // Validate file type
        if ($uploadedFile['type'] !== 'application/json') {
            return false;
        }
        
        // Read and validate JSON structure
        $jsonContent = file_get_contents($uploadedFile['tmp_name']);
        $credentials = json_decode($jsonContent, true);
        
        if (!$credentials || !isset($credentials['web'])) {
            return false;
        }
        
        // Save to WordPress options
        update_option($optionName, $credentials);
        
        return true;
    }
    
    /**
     * Create demo file from URL
     * 
     * @param string $url Source URL
     * @param string $filePath Target file path
     * @return bool Success status
     */
    public function createDemoFile(string $url, string $filePath): bool {
        try {
            $urlFixed = $this->fixUrl($url);
            
            if (false !== ($path = $this->isLocal($urlFixed))) {
                return copy($path, $filePath);
            } else {
                $response = wp_remote_get(
                    $urlFixed,
                    [
                        'timeout' => BFP_REMOTE_TIMEOUT,
                        'stream' => true,
                        'filename' => $filePath,
                    ]
                );
                if (!is_wp_error($response) && 200 == $response['response']['code']) {
                    return true;
                }
            }
        } catch (\Exception $err) {
            error_log($err->getMessage());
        }
        
        return false;
    }
    
    /**
     * Get MIME type for file
     * 
     * @param string $filePath File path
     * @return string MIME type
     */
    public function getMimeType(string $filePath): string {
        if (!function_exists('mime_content_type') || false === ($mimeType = mime_content_type($filePath))) {
            // Fallback based on extension
            $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
            $mimeTypes = [
                'mp3' => 'audio/mpeg',
                'wav' => 'audio/wav',
                'ogg' => 'audio/ogg',
                'oga' => 'audio/ogg',
                'mp4' => 'audio/mp4',
                'm4a' => 'audio/mp4',
                'wma' => 'audio/x-ms-wma',
                'm3u' => 'audio/x-mpegurl',
                'm3u8' => 'application/x-mpegurl',
            ];
            
            $mimeType = $mimeTypes[$ext] ?? 'audio/mpeg';
        }
        
        return $mimeType;
    }
}
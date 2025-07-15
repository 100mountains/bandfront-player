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
        if (strpos($url, 'drive.google.com') !== false) {
            return Utils\Cloud::getGoogleDriveDownloadUrl($url);
        }
        return $url;
    }
    
    /**
     * Check if URL is local and return path
     * 
     * @param string $url File URL
     * @return string|false Local path or false
     */
    public function isLocal(string $url): string|false {
        $uploadDir = wp_upload_dir();
        
        // Check if URL is within upload directory
        if (strpos($url, $uploadDir['baseurl']) === 0) {
            $relativePath = str_replace($uploadDir['baseurl'], '', $url);
            $localPath = $uploadDir['basedir'] . $relativePath;
            
            if (file_exists($localPath)) {
                return $localPath;
            }
        }
        
        // Check if it's a relative path
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            $localPath = ABSPATH . ltrim($url, '/');
            if (file_exists($localPath)) {
                return $localPath;
            }
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
        $mimeType = 'audio/mpeg'; // Default
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        
        switch ($extension) {
            case 'wav':
                $mimeType = 'audio/wav';
                break;
            case 'ogg':
            case 'oga':
                $mimeType = 'audio/ogg';
                break;
            case 'm4a':
                $mimeType = 'audio/mp4';
                break;
            case 'flac':
                $mimeType = 'audio/flac';
                break;
            case 'mp3':
            default:
                $mimeType = 'audio/mpeg';
                break;
        }
        
        return $mimeType;
    }
    
    /**
     * Check if URL is a playlist
     * 
     * @param string $url URL to check
     * @return bool
     */
    public function isPlaylist(string $url): bool {
        $playlistExtensions = ['m3u', 'm3u8', 'pls', 'xspf'];
        $extension = strtolower(pathinfo($url, PATHINFO_EXTENSION));
        return in_array($extension, $playlistExtensions);
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
     * Check if file is audio and return media type
     * 
     * @param string $file File URL or path
     * @return string|false Media type or false if not audio
     */
    public function isAudio(string $file): string|false {
        $audioExtensions = [
            'mp3' => 'mp3',
            'ogg' => 'ogg',
            'oga' => 'ogg',
            'wav' => 'wav',
            'm4a' => 'mp4',
            'mp4' => 'mp4',
            'flac' => 'flac',
            'webm' => 'webm',
            'weba' => 'webm'
        ];
        
        $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        
        if (isset($audioExtensions[$extension])) {
            return $audioExtensions[$extension];
        }
        
        // Check for cloud URLs without extensions
        if ($this->isCloudUrl($file)) {
            return 'mp3'; // Default for cloud URLs
        }
        
        // Check actual MIME type if local file
        if ($localPath = $this->isLocal($file)) {
            if ($this->hasAudioMimeType($localPath)) {
                return 'mp3'; // Default media type
            }
        }
        
        return false;
    }
    
    /**
     * Check if file is video
     * 
     * @param string $file File URL or path
     * @return bool True if video file
     */
    public function isVideo(string $file): bool {
        $videoExtensions = ['mp4', 'webm', 'ogv', 'mov', 'avi', 'wmv', 'flv', 'm4v'];
        $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        
        if (in_array($extension, $videoExtensions)) {
            return true;
        }
        
        // Check actual MIME type if local file
        if ($localPath = $this->isLocal($file)) {
            return $this->hasVideoMimeType($localPath);
        }
        
        return false;
    }
    
    /**
     * Check if file is image
     * 
     * @param string $file File URL or path
     * @return bool True if image file
     */
    public function isImage(string $file): bool {
        $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg'];
        $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        
        if (in_array($extension, $imageExtensions)) {
            return true;
        }
        
        // Check actual MIME type if local file
        if ($localPath = $this->isLocal($file)) {
            return $this->hasImageMimeType($localPath);
        }
        
        return false;
    }
    
    /**
     * Fix URL encoding and format
     * 
     * @param string $url URL to fix
     * @return string Fixed URL
     */
    public function fixUrl(string $url): string {
        // Decode any HTML entities
        $url = html_entity_decode($url);
        
        // Fix double encoding
        if (strpos($url, '%25') !== false) {
            $url = urldecode($url);
        }
        
        // Ensure proper encoding for spaces
        $url = str_replace(' ', '%20', $url);
        
        // Handle protocol-relative URLs
        if (strpos($url, '//') === 0) {
            $url = (is_ssl() ? 'https:' : 'http:') . $url;
        }
        
        return $url;
    }
    
    /**
     * Generate demo file name from URL
     * 
     * @param string $url Source URL
     * @return string Generated filename
     */
    public function generateDemoFileName(string $url): string {
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
        
        return md5($url) . '.' . $ext;
    }
    
    /**
     * Truncate file to percentage (simple implementation)
     * 
     * @param string $filePath File to truncate
     * @param int $percent Percentage to keep
     * @return bool Success status
     */
    public function truncateFile(string $filePath, int $percent): bool {
        if (!file_exists($filePath) || !is_readable($filePath)) {
            return false;
        }
        
        $filesize = filesize($filePath);
        $newSize = floor($filesize * ($percent / 100));
        
        // Create truncated copy
        $tempFile = $filePath . '.tmp';
        
        try {
            $source = fopen($filePath, 'rb');
            $dest = fopen($tempFile, 'wb');
            
            $written = 0;
            while (!feof($source) && $written < $newSize) {
                $chunk = fread($source, min(8192, $newSize - $written));
                fwrite($dest, $chunk);
                $written += strlen($chunk);
            }
            
            fclose($source);
            fclose($dest);
            
            // Replace original with truncated version
            if (file_exists($tempFile)) {
                unlink($filePath);
                rename($tempFile, $filePath);
                return true;
            }
        } catch (\Exception $e) {
            error_log('BFP truncateFile error: ' . $e->getMessage());
            if (file_exists($tempFile)) {
                @unlink($tempFile);
            }
        }
        
        return false;
    }
    
    /**
     * Get file path for a product file
     * 
     * @param int $productId Product ID
     * @param string $fileIndex File index
     * @return string|null File path or null if not found
     */
    public function getFilePath(int $productId, string $fileIndex): ?string {
        $files = $this->getProductFiles($productId);
        
        if (isset($files[$fileIndex]) && !empty($files[$fileIndex]['file'])) {
            return $files[$fileIndex]['file'];
        }
        
        return null;
    }
    
    /**
     * Get or create demo file
     * 
     * @param string $originalPath Original file path
     * @param int $percent Percentage for demo
     * @return string Demo file path
     */
    public function getDemoFile(string $originalPath, int $percent): string {
        $demoFileName = 'demo_' . $percent . '_' . $this->generateDemoFileName($originalPath);
        $demoPath = $this->filesDirectoryPath . $demoFileName;
        
        // Check if demo already exists
        if (file_exists($demoPath)) {
            return $demoPath;
        }
        
        // Create demo file
        if ($this->createDemoFile($originalPath, $demoPath)) {
            // Truncate to percentage
            $this->truncateFile($demoPath, $percent);
            return $demoPath;
        }
        
        // Return original if demo creation failed
        return $originalPath;
    }
    
    /**
     * Stream file with range request support
     * 
     * @param string $filePath File to stream
     * @param array $options Streaming options
     * @return void
     */
    public function streamFile(string $filePath, array $options = []): void {
        if (!file_exists($filePath) || !is_readable($filePath)) {
            status_header(404);
            exit;
        }
        
        $mimeType = $this->getMimeType($filePath);
        $filesize = filesize($filePath);
        
        // Basic headers
        header("Content-Type: $mimeType");
        header("Accept-Ranges: bytes");
        header("Cache-Control: no-cache, must-revalidate");
        
        // Handle range requests
        if (isset($_SERVER['HTTP_RANGE'])) {
            $range = $_SERVER['HTTP_RANGE'];
            list($rangeType, $rangeValue) = explode('=', $range, 2);
            
            if ($rangeType === 'bytes') {
                list($start, $end) = explode('-', $rangeValue, 2);
                $start = intval($start);
                $end = empty($end) ? ($filesize - 1) : intval($end);
                $length = $end - $start + 1;
                
                header("HTTP/1.1 206 Partial Content");
                header("Content-Range: bytes $start-$end/$filesize");
                header("Content-Length: $length");
                
                $fp = fopen($filePath, 'rb');
                fseek($fp, $start);
                
                $bufferSize = 8192;
                $bytesToRead = $length;
                
                while (!feof($fp) && $bytesToRead > 0) {
                    $buffer = fread($fp, min($bufferSize, $bytesToRead));
                    echo $buffer;
                    flush();
                    $bytesToRead -= strlen($buffer);
                }
                
                fclose($fp);
            }
        } else {
            // No range request - send whole file
            header("Content-Length: $filesize");
            readfile($filePath);
        }
        
        exit;
    }
}
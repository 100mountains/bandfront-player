<?php
namespace bfp\Utils;

use bfp\Plugin;
use bfp\Utils\Debug; // DEBUG-REMOVE

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
       Debug::log(
           'Files.php:' . __LINE__ . ' Files initialized: directories checked/created',
           [
               'mainPlugin' => is_object($mainPlugin),
               'filesDirectoryPath' => $this->filesDirectoryPath ?? null,
               'filesDirectoryUrl' => $this->filesDirectoryUrl ?? null,
               'baseExists' => isset($this->filesDirectoryPath) && file_exists($this->filesDirectoryPath),
               'purchasedExists' => isset($this->filesDirectoryPath) && file_exists($this->filesDirectoryPath . 'purchased/')
           ]
       ); // DEBUG-REMOVE
   }
   
   /**
    * Create directories for file storage
    */
   public function createDirectories(): void {
       Debug::log('Files.php:' . __LINE__ . ' Entering createDirectories()', []); // DEBUG-REMOVE
       // Generate upload dir
       $filesDirectory = wp_upload_dir();
       $this->filesDirectoryPath = rtrim($filesDirectory['basedir'], '/') . '/bfp/';
       $this->filesDirectoryUrl = rtrim($filesDirectory['baseurl'], '/') . '/bfp/';
       $this->filesDirectoryUrl = preg_replace('/^http(s)?:\/\//', '//', $this->filesDirectoryUrl);
       
       if (!file_exists($this->filesDirectoryPath)) {
           Debug::log('Files.php:' . __LINE__ . ' Creating filesDirectoryPath', ['path' => $this->filesDirectoryPath]); // DEBUG-REMOVE
           @mkdir($this->filesDirectoryPath, 0755);
       }

       if (is_dir($this->filesDirectoryPath)) {
           if (!file_exists($this->filesDirectoryPath . '.htaccess')) {
               try {
                   file_put_contents($this->filesDirectoryPath . '.htaccess', 'Options -Indexes');
                   Debug::log('Files.php:' . __LINE__ . ' .htaccess created', []); // DEBUG-REMOVE
               } catch (\Exception $err) {
                   Debug::log('Files.php:' . __LINE__ . ' .htaccess creation error', ['error' => $err->getMessage()]); // DEBUG-REMOVE
               }
           }
       }

       if (!file_exists($this->filesDirectoryPath . 'purchased/')) {
           Debug::log('Files.php:' . __LINE__ . ' Creating purchased dir', []); // DEBUG-REMOVE
           @mkdir($this->filesDirectoryPath . 'purchased/', 0755);
       }
       Debug::log('Files.php:' . __LINE__ . ' Exiting createDirectories()', []); // DEBUG-REMOVE
   }
   
   /**
    * Clear directory contents
    */
   public function clearDir(string $dirPath): void {
       Debug::log('Files.php:' . __LINE__ . ' Entering clearDir()', ['dirPath' => $dirPath]); // DEBUG-REMOVE
       try {
           if (empty($dirPath) || !file_exists($dirPath) || !is_dir($dirPath)) {
               Debug::log('Files.php:' . __LINE__ . ' clearDir: invalid dir', ['dirPath' => $dirPath]); // DEBUG-REMOVE
               return;
           }
           $dirPath = rtrim($dirPath, '\\/') . '/';
           $files = glob($dirPath . '*', GLOB_MARK);
           foreach ($files as $file) {
               if (is_dir($file)) {
                   Debug::log('Files.php:' . __LINE__ . ' clearDir: recursing into dir', ['file' => $file]); // DEBUG-REMOVE
                   $this->clearDir($file);
               } else {
                   Debug::log('Files.php:' . __LINE__ . ' clearDir: deleting file', ['file' => $file]); // DEBUG-REMOVE
                   unlink($file);
               }
           }
       } catch (\Exception $err) {
           Debug::log('Files.php:' . __LINE__ . ' clearDir: exception', ['error' => $err->getMessage()]); // DEBUG-REMOVE
           return;
       }
       Debug::log('Files.php:' . __LINE__ . ' Exiting clearDir()', []); // DEBUG-REMOVE
   }
   
   /**
    * Get files directory path
    */
   public function getFilesDirectoryPath(): string {
       Debug::log('Files.php:' . __LINE__ . ' getFilesDirectoryPath()', ['path' => $this->filesDirectoryPath]); // DEBUG-REMOVE
       return $this->filesDirectoryPath;
   }
   
   /**
    * Get files directory URL
    */
   public function getFilesDirectoryUrl(): string {
       Debug::log('Files.php:' . __LINE__ . ' getFilesDirectoryUrl()', ['url' => $this->filesDirectoryUrl]); // DEBUG-REMOVE
       return $this->filesDirectoryUrl;
   }
   
   /**
    * Delete post-related files and meta data
    */
   public function deletePost(int $postId, bool $demosOnly = false, bool $force = false): void {
       Debug::log('Files.php:' . __LINE__ . ' Entering deletePost()', ['postId' => $postId, 'demosOnly' => $demosOnly, 'force' => $force]); // DEBUG-REMOVE
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
           Debug::log('Files.php:' . __LINE__ . ' deletePost: skipping', ['postId' => $postId]); // DEBUG-REMOVE
           return;
       }

       // Delete truncated version of the audio file
       $this->deleteTruncatedFiles($postId);

       if (!$demosOnly) {
           Debug::log('Files.php:' . __LINE__ . ' deletePost: deleting meta', ['postId' => $postId]); // DEBUG-REMOVE
           delete_post_meta($postId, '_bfp_enable_player');
           delete_post_meta($postId, '_bfp_merge_in_grouped');
           delete_post_meta($postId, '_bfp_player_layout');
           delete_post_meta($postId, '_bfp_player_volume');
           delete_post_meta($postId, '_bfp_single_player');
           delete_post_meta($postId, '_bfp_secure_player');
           delete_post_meta($postId, '_bfp_file_percent');
           delete_post_meta($postId, '_bfp_player_controls');
           delete_post_meta($postId, '_bfp_play_all');
           delete_post_meta($postId, '_bfp_loop');
           delete_post_meta($postId, '_bfp_on_cover');

           delete_post_meta($postId, '_bfp_playback_counter');
       }

       delete_post_meta($postId, '_bfp_own_demos');
       delete_post_meta($postId, '_bfp_direct_own_demos');
       delete_post_meta($postId, '_bfp_demos_list');

       do_action('bfp_delete_post', $postId);
       Debug::log('Files.php:' . __LINE__ . ' Exiting deletePost()', []); // DEBUG-REMOVE
   }
   
   /**
    * Delete purchased files based on reset interval
    */
   public function deletePurchasedFiles(): void {
       Debug::log('Files.php:' . __LINE__ . ' Entering deletePurchasedFiles()', []); // DEBUG-REMOVE
       // Use getState for single value retrieval
       if ($this->mainPlugin->getConfig()->getState('_bfp_reset_purchased_interval', 'daily') == 'daily') {
           $this->clearDir($this->filesDirectoryPath . 'purchased/');
           $this->createDirectories();
       }
       Debug::log('Files.php:' . __LINE__ . ' Exiting deletePurchasedFiles()', []); // DEBUG-REMOVE
   }
   
   /**
    * Clear expired transients to maintain cache
    */
   public function clearExpiredTransients(): void {
       Debug::log('Files.php:' . __LINE__ . ' Entering clearExpiredTransients()', []); // DEBUG-REMOVE
       $transient = get_transient('bfp_clear_expired_transients');
       if (!$transient || 24 * 60 * 60 <= time() - intval($transient)) {
           set_transient('bfp_clear_expired_transients', time());
           delete_expired_transients();
           Debug::log('Files.php:' . __LINE__ . ' clearExpiredTransients: expired transients cleared', []); // DEBUG-REMOVE
       }
       Debug::log('Files.php:' . __LINE__ . ' Exiting clearExpiredTransients()', []); // DEBUG-REMOVE
   }
   
   /**
    * Delete truncated demo files for a product
    */
   public function deleteTruncatedFiles(int $productId): void {
       Debug::log('Files.php:' . __LINE__ . ' Entering deleteTruncatedFiles()', ['productId' => $productId]); // DEBUG-REMOVE
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
                       Debug::log('Files.php:' . __LINE__ . ' deleteTruncatedFiles: deleting file', ['fileName' => $fileName]); // DEBUG-REMOVE
                       @unlink($this->filesDirectoryPath . $fileName);
                   }
                   do_action('bfp_delete_file', $productId, $file['file']);
               }
           }
       }
       Debug::log('Files.php:' . __LINE__ . ' Exiting deleteTruncatedFiles()', []); // DEBUG-REMOVE
   }
   
   /**
    * Get product files with filtering options
    * 
    * @param array $args Arguments for file retrieval
    * @return array Filtered audio files
    */
   public function getProductFilesInternal(array $args): array {
       Debug::log('Files.php:' . __LINE__ . ' Entering getProductFilesInternal()', ['args' => $args]); // DEBUG-REMOVE
       if (empty($args['product'])) {
           Debug::log('Files.php:' . __LINE__ . ' getProductFilesInternal: empty product', []); // DEBUG-REMOVE
           return [];
       }

       $product = $args['product'];
       $files = $this->getAllProductFiles($product, []);
       if (empty($files)) {
           Debug::log('Files.php:' . __LINE__ . ' getProductFilesInternal: no files', []); // DEBUG-REMOVE
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
                       Debug::log('Files.php:' . __LINE__ . ' getProductFilesInternal: found file_id', ['index' => $index]); // DEBUG-REMOVE
                       return $audioFiles;
                   }
               } elseif (!empty($args['first'])) {
                   $audioFiles[$index] = $file;
                   Debug::log('Files.php:' . __LINE__ . ' getProductFilesInternal: returning first', ['index' => $index]); // DEBUG-REMOVE
                   return $audioFiles;
               } elseif (!empty($args['all'])) {
                   $audioFiles[$index] = $file;
               }
           }
       }
       Debug::log('Files.php:' . __LINE__ . ' Exiting getProductFilesInternal()', ['audioFilesCount' => count($audioFiles)]); // DEBUG-REMOVE
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
       Debug::log('Files.php:' . __LINE__ . ' Entering getAllProductFiles()', ['product' => is_object($product) ? $product->get_id() : null]); // DEBUG-REMOVE
       if (!is_object($product) || !method_exists($product, 'get_type')) {
           Debug::log('Files.php:' . __LINE__ . ' getAllProductFiles: not a product object', []); // DEBUG-REMOVE
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
               Debug::log('Files.php:' . __LINE__ . ' getAllProductFiles: player not enabled', ['productId' => $id]); // DEBUG-REMOVE
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
       Debug::log('Files.php:' . __LINE__ . ' Exiting getAllProductFiles()', ['filesArrCount' => count($filesArr)]); // DEBUG-REMOVE
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
       Debug::log('Files.php:' . __LINE__ . ' Entering editFilesArray()', ['productId' => $productId, 'filesCount' => count($files), 'playSrc' => $playSrc]); // DEBUG-REMOVE
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
       Debug::log('Files.php:' . __LINE__ . ' Exiting editFilesArray()', ['pFilesCount' => count($pFiles)]); // DEBUG-REMOVE
       return $pFiles;
   }
   
   /**
    * Get product files - public interface
    * 
    * @param int $productId Product ID
    * @return array All audio files for the product
    */
   public function getProductFiles(int $productId): array {
       Debug::log('Files.php:' . __LINE__ . ' Entering getProductFiles()', ['productId' => $productId]); // DEBUG-REMOVE
       $product = wc_get_product($productId);
       if (!$product) {
           Debug::log('Files.php:' . __LINE__ . ' getProductFiles: no product', []); // DEBUG-REMOVE
           return [];
       }
       
       $result = $this->getProductFilesInternal([
           'product' => $product,
           'all' => true
       ]);
       Debug::log('Files.php:' . __LINE__ . ' Exiting getProductFiles()', ['resultCount' => count($result)]); // DEBUG-REMOVE
       return $result;
   }
   
   /**
    * Get streaming URL for a file (will be replaced by REST)
    * 
    * @param int $productId Product ID
    * @param string $fileIndex File index
    * @return string Streaming URL
    */
   public function getStreamingUrl(int $productId, string $fileIndex): string {
       Debug::log('Files.php:' . __LINE__ . ' getStreamingUrl()', ['productId' => $productId, 'fileIndex' => $fileIndex]); // DEBUG-REMOVE
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
       Debug::log('Files.php:' . __LINE__ . ' processCloudUrl()', ['url' => $url]); // DEBUG-REMOVE
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
       Debug::log('Files.php:' . __LINE__ . ' isLocal()', ['url' => $url]); // DEBUG-REMOVE
       $uploadDir = wp_upload_dir();
       
       // Check if URL is within upload directory
       if (strpos($url, $uploadDir['baseurl']) === 0) {
           $relativePath = str_replace($uploadDir['baseurl'], '', $url);
           $localPath = $uploadDir['basedir'] . $relativePath;
           
           if (file_exists($localPath)) {
               Debug::log('Files.php:' . __LINE__ . ' isLocal: found localPath', ['localPath' => $localPath]); // DEBUG-REMOVE
               return $localPath;
           }
       }
       
       // Check if it's a relative path
       if (!filter_var($url, FILTER_VALIDATE_URL)) {
           $localPath = ABSPATH . ltrim($url, '/');
           if (file_exists($localPath)) {
               Debug::log('Files.php:' . __LINE__ . ' isLocal: found relative localPath', ['localPath' => $localPath]); // DEBUG-REMOVE
               return $localPath;
           }
       }
       
       Debug::log('Files.php:' . __LINE__ . ' isLocal: not found', []); // DEBUG-REMOVE
       return false;
   }
   
   /**
    * Get MIME type for file
    * 
    * @param string $filePath File path
    * @return string MIME type
    */
   public function getMimeType(string $filePath): string {
       Debug::log('Files.php:' . __LINE__ . ' getMimeType()', ['filePath' => $filePath]); // DEBUG-REMOVE
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
       
       Debug::log('Files.php:' . __LINE__ . ' getMimeType: result', ['mimeType' => $mimeType]); // DEBUG-REMOVE
       return $mimeType;
   }
   
   /**
    * Check if URL is a playlist
    * 
    * @param string $url URL to check
    * @return bool
    */
   public function isPlaylist(string $url): bool {
       Debug::log('Files.php:' . __LINE__ . ' isPlaylist()', ['url' => $url]); // DEBUG-REMOVE
       $playlistExtensions = ['m3u', 'm3u8', 'pls', 'xspf'];
       $extension = strtolower(pathinfo($url, PATHINFO_EXTENSION));
       $result = in_array($extension, $playlistExtensions);
       Debug::log('Files.php:' . __LINE__ . ' isPlaylist: result', ['result' => $result]); // DEBUG-REMOVE
       return $result;
   }
   
   /**
    * Get the correct player type for the file
    * 
    * @param string $filePath File path to check
    * @return string Player type: 'audio', 'video', or 'image'
    */
   public function getPlayerType(string $filePath): string {
       Debug::log('Files.php:' . __LINE__ . ' getPlayerType()', ['filePath' => $filePath]); // DEBUG-REMOVE
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
       Debug::log('Files.php:' . __LINE__ . ' isAudio()', ['file' => $file]); // DEBUG-REMOVE
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
           Debug::log('Files.php:' . __LINE__ . ' isAudio: extension match', ['mediaType' => $audioExtensions[$extension]]); // DEBUG-REMOVE
           return $audioExtensions[$extension];
       }
       
       // Check for cloud URLs without extensions
       if ($this->isCloudUrl($file)) {
           Debug::log('Files.php:' . __LINE__ . ' isAudio: cloud url', []); // DEBUG-REMOVE
           return 'mp3'; // Default for cloud URLs
       }
       
       // Check actual MIME type if local file
       if ($localPath = $this->isLocal($file)) {
           if ($this->hasAudioMimeType($localPath)) {
               Debug::log('Files.php:' . __LINE__ . ' isAudio: has audio mime', []); // DEBUG-REMOVE
               return 'mp3'; // Default media type
           }
       }
       
       Debug::log('Files.php:' . __LINE__ . ' isAudio: not audio', []); // DEBUG-REMOVE
       return false;
   }
   
   /**
    * Check if file is video
    * 
    * @param string $file File URL or path
    * @return bool True if video file
    */
   public function isVideo(string $file): bool {
       Debug::log('Files.php:' . __LINE__ . ' isVideo()', ['file' => $file]); // DEBUG-REMOVE
       $videoExtensions = ['mp4', 'webm', 'ogv', 'mov', 'avi', 'wmv', 'flv', 'm4v'];
       $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
       
       if (in_array($extension, $videoExtensions)) {
           Debug::log('Files.php:' . __LINE__ . ' isVideo: extension match', []); // DEBUG-REMOVE
           return true;
       }
       
       // Check actual MIME type if local file
       if ($localPath = $this->isLocal($file)) {
           $result = $this->hasVideoMimeType($localPath);
           Debug::log('Files.php:' . __LINE__ . ' isVideo: mime check', ['result' => $result]); // DEBUG-REMOVE
           return $result;
       }
       
       Debug::log('Files.php:' . __LINE__ . ' isVideo: not video', []); // DEBUG-REMOVE
       return false;
   }
   
   /**
    * Check if file is image
    * 
    * @param string $file File URL or path
    * @return bool True if image file
    */
   public function isImage(string $file): bool {
       Debug::log('Files.php:' . __LINE__ . ' isImage()', ['file' => $file]); // DEBUG-REMOVE
       $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg'];
       $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
       
       if (in_array($extension, $imageExtensions)) {
           Debug::log('Files.php:' . __LINE__ . ' isImage: extension match', []); // DEBUG-REMOVE
           return true;
       }
       
       // Check actual MIME type if local file
       if ($localPath = $this->isLocal($file)) {
           $result = $this->hasImageMimeType($localPath);
           Debug::log('Files.php:' . __LINE__ . ' isImage: mime check', ['result' => $result]); // DEBUG-REMOVE
           return $result;
       }
       
       Debug::log('Files.php:' . __LINE__ . ' isImage: not image', []); // DEBUG-REMOVE
       return false;
   }
   
   /**
    * Fix URL encoding and format
    * 
    * @param string $url URL to fix
    * @return string Fixed URL
    */
   public function fixUrl(string $url): string {
       Debug::log('Files.php:' . __LINE__ . ' fixUrl()', ['url' => $url]); // DEBUG-REMOVE
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
       
       Debug::log('Files.php:' . __LINE__ . ' fixUrl: result', ['url' => $url]); // DEBUG-REMOVE
       return $url;
   }
   
   /**
    * Generate demo file name from URL
    * 
    * @param string $url Source URL
    * @return string Generated filename
    */
   public function generateDemoFileName(string $url): string {
       Debug::log('Files.php:' . __LINE__ . ' generateDemoFileName()', ['url' => $url]); // DEBUG-REMOVE
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
       Debug::log('Files.php:' . __LINE__ . ' generateDemoFileName: result', ['filename' => $filename]); // DEBUG-REMOVE
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
       Debug::log('Files.php:' . __LINE__ . ' Entering truncateFile()', ['filePath' => $filePath, 'percent' => $percent]); // DEBUG-REMOVE
       if (!file_exists($filePath) || !is_readable($filePath)) {
           Debug::log('Files.php:' . __LINE__ . ' truncateFile: file not found or unreadable', []); // DEBUG-REMOVE
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
               Debug::log('Files.php:' . __LINE__ . ' truncateFile: truncated', ['filePath' => $filePath]); // DEBUG-REMOVE
               return true;
           }
       } catch (\Exception $e) {
           Debug::log('Files.php:' . __LINE__ . ' truncateFile: exception', ['error' => $e->getMessage()]); // DEBUG-REMOVE
           error_log('BFP truncateFile error: ' . $e->getMessage());
           if (file_exists($tempFile)) {
               @unlink($tempFile);
           }
       }
       
       Debug::log('Files.php:' . __LINE__ . ' truncateFile: failed', []); // DEBUG-REMOVE
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
       Debug::log('Files.php:' . __LINE__ . ' getFilePath()', ['productId' => $productId, 'fileIndex' => $fileIndex]); // DEBUG-REMOVE
       $files = $this->getProductFiles($productId);
       
       if (isset($files[$fileIndex]) && !empty($files[$fileIndex]['file'])) {
           Debug::log('Files.php:' . __LINE__ . ' getFilePath: found', ['file' => $files[$fileIndex]['file']]); // DEBUG-REMOVE
           return $files[$fileIndex]['file'];
       }
       
       Debug::log('Files.php:' . __LINE__ . ' getFilePath: not found', []); // DEBUG-REMOVE
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
       Debug::log('Files.php:' . __LINE__ . ' Entering getDemoFile()', ['originalPath' => $originalPath, 'percent' => $percent]); // DEBUG-REMOVE
       $demoFileName = 'demo_' . $percent . '_' . $this->generateDemoFileName($originalPath);
       $demoPath = $this->filesDirectoryPath . $demoFileName;
       
       // Check if demo already exists
       if (file_exists($demoPath)) {
           Debug::log('Files.php:' . __LINE__ . ' getDemoFile: exists', ['demoPath' => $demoPath]); // DEBUG-REMOVE
           return $demoPath;
       }
       
       // Create demo file
       if ($this->createDemoFile($originalPath, $demoPath)) {
           // Truncate to percentage
           $this->truncateFile($demoPath, $percent);
           Debug::log('Files.php:' . __LINE__ . ' getDemoFile: created', ['demoPath' => $demoPath]); // DEBUG-REMOVE
           return $demoPath;
       }
       
       // Return original if demo creation failed
       Debug::log('Files.php:' . __LINE__ . ' getDemoFile: failed, returning original', ['originalPath' => $originalPath]); // DEBUG-REMOVE
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
       Debug::log('Files.php:' . __LINE__ . ' Entering streamFile()', ['filePath' => $filePath, 'options' => $options]); // DEBUG-REMOVE
       if (!file_exists($filePath) || !is_readable($filePath)) {
           Debug::log('Files.php:' . __LINE__ . ' streamFile: file not found or unreadable', []); // DEBUG-REMOVE
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
       
       Debug::log('Files.php:' . __LINE__ . ' Exiting streamFile()', []); // DEBUG-REMOVE
       exit;
   }
   
   /**
    * Create demo file from source
    * 
    * @param string $sourceUrl Source file URL
    * @param string $destPath Destination path
    * @return bool Success status
    */
   public function createDemoFile(string $sourceUrl, string $destPath): bool {
       Debug::log('Files.php:' . __LINE__ . ' Entering createDemoFile()', ['sourceUrl' => $sourceUrl, 'destPath' => $destPath]); // DEBUG-REMOVE
       // Process cloud URLs
       $sourceUrl = $this->processCloudUrl($sourceUrl);
       
       // Check if source is local
       $localPath = $this->isLocal($sourceUrl);
       
       if ($localPath && file_exists($localPath)) {
           // Copy local file
           $result = copy($localPath, $destPath);
           Debug::log('Files.php:' . __LINE__ . ' createDemoFile: local copy', ['result' => $result]); // DEBUG-REMOVE
           return $result;
       }
       
       // Download remote file
       $response = wp_remote_get($sourceUrl, [
           'timeout' => 300,
           'stream' => true,
           'filename' => $destPath
       ]);
       
       if (is_wp_error($response)) {
           Debug::log('Files.php:' . __LINE__ . ' createDemoFile: download error', ['error' => $response->get_error_message()]); // DEBUG-REMOVE
           return false;
       }
       
       $result = file_exists($destPath) && filesize($destPath) > 0;        
       Debug::log('Files.php:' . __LINE__ . ' createDemoFile: download result', ['result' => $result]); // DEBUG-REMOVE
       return $result;
   }
   
   /**
    * Check if demo file is valid
    * 
    * @param string $filePath Path to the demo file
    * @return bool True if valid, false otherwise
    */
   public function isValidDemo(string $filePath): bool {
       Debug::log('Files.php:' . __LINE__ . ' isValidDemo()', ['filePath' => $filePath]); // DEBUG-REMOVE
       if (!file_exists($filePath) || filesize($filePath) == 0) {
           Debug::log('Files.php:' . __LINE__ . ' isValidDemo: file missing or empty', []); // DEBUG-REMOVE
           return false;
       }
       if (function_exists('finfo_open')) {
           $finfo = finfo_open(FILEINFO_MIME);
           $isText = substr(finfo_file($finfo, $filePath), 0, 4) === 'text';
           Debug::log('Files.php:' . __LINE__ . ' isValidDemo: finfo', ['isText' => $isText]); // DEBUG-REMOVE
           return !$isText;
       }
       Debug::log('Files.php:' . __LINE__ . ' isValidDemo: fallback true', []); // DEBUG-REMOVE
       return true;
   }
   
   /**
    * Check if URL is a cloud storage URL
    * 
    * @param string $url URL to check
    * @return bool
    */
   private function isCloudUrl(string $url): bool {
       Debug::log('Files.php:' . __LINE__ . ' isCloudUrl()', ['url' => $url]); // DEBUG-REMOVE
       $cloudDomains = [
           'drive.google.com',
           'dropbox.com',
           'dl.dropboxusercontent.com',
           's3.amazonaws.com',
           'blob.core.windows.net'
       ];
       
       foreach ($cloudDomains as $domain) {
           if (strpos($url, $domain) !== false) {
               return true;
           }
       }
       
       return false;
   }
   
   /**
    * Check if file has audio MIME type
    * 
    * @param string $filePath File path
    * @return bool
    */
   private function hasAudioMimeType(string $filePath): bool {
       if (!function_exists('finfo_open')) {
           return false;
       }
       
       $finfo = finfo_open(FILEINFO_MIME_TYPE);
       $mimeType = finfo_file($finfo, $filePath);
       finfo_close($finfo);
       
       return strpos($mimeType, 'audio/') === 0;
   }
   
   /**
    * Check if file has video MIME type
    * 
    * @param string $filePath File path
    * @return bool
    */
   private function hasVideoMimeType(string $filePath): bool {
       if (!function_exists('finfo_open')) {
           return false;
       }
       
       $finfo = finfo_open(FILEINFO_MIME_TYPE);
       $mimeType = finfo_file($finfo, $filePath);
       finfo_close($finfo);
       
       return strpos($mimeType, 'video/') === 0;
   }
   
   /**
    * Check if file has image MIME type
    * 
    * @param string $filePath File path
    * @return bool
    */
   private function hasImageMimeType(string $filePath): bool {
       if (!function_exists('finfo_open')) {
           return false;
       }
       
       $finfo = finfo_open(FILEINFO_MIME_TYPE);
       $mimeType = finfo_file($finfo, $filePath);
       finfo_close($finfo);
       
       return strpos($mimeType, 'image/') === 0;
   }
   
   /**
    * Handle OAuth file upload for Google Drive
    * 
    * @param array $fileData $_FILES data
    * @return bool Success status
    */
   public function handleOAuthFileUpload(array $fileData): bool {
       if ($fileData['error'] !== UPLOAD_ERR_OK) {
           return false;
       }
       
       $uploadDir = wp_upload_dir();
       $targetDir = $uploadDir['basedir'] . '/bfp-private';
       
       if (!file_exists($targetDir)) {
           wp_mkdir_p($targetDir);
           
           // Protect directory
           file_put_contents($targetDir . '/.htaccess', 'deny from all');
       }
       
       $targetPath = $targetDir . '/google-oauth.json';
       
       if (move_uploaded_file($fileData['tmp_name'], $targetPath)) {
           // Store in options
           $driveSettings = get_option('_bfp_cloud_drive_addon', []);
           $driveSettings['_bfp_drive'] = 1;
           $driveSettings['_bfp_drive_key'] = $targetPath;
           update_option('_bfp_cloud_drive_addon', $driveSettings);
           
           return true;
       }
       
       return false;
   }
}
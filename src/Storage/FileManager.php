<?php
declare(strict_types=1);

namespace Bandfront\Storage;

use Bandfront\Core\Config;  // Fixed: was Bandfront\Config
use Bandfront\Utils\Debug;
use Bandfront\Utils\Utils;
use Bandfront\Utils\Cloud;

// Set domain for Storage
Debug::domain('storage');

/**
 * File handling functionality for Bandfront Player
 */

if (!defined('ABSPATH')) {
   exit;
}

/**
 * File Handler Class
 */
class FileManager {
   
   private Config $config;
   private string $filesDirectoryPath;
   private string $filesDirectoryUrl;
   
   public function __construct(Config $config) {
       $this->config = $config;
       $this->createDirectories();
       Debug::log(
           'FileManager initialized: directories checked/created',
           [
               'filesDirectoryPath' => $this->filesDirectoryPath ?? null,
               'filesDirectoryUrl' => $this->filesDirectoryUrl ?? null,
               'baseExists' => isset($this->filesDirectoryPath) && file_exists($this->filesDirectoryPath)
           ]
       );
   }
   
   /**
    * Create directories for file storage
    */
   public function createDirectories(): void {
       Debug::log('Entering createDirectories()', []); // DEBUG-REMOVE
       // Generate upload dir
       $filesDirectory = wp_upload_dir();
       $this->filesDirectoryPath = rtrim($filesDirectory['basedir'], '/') . '/bfp/';
       $this->filesDirectoryUrl = rtrim($filesDirectory['baseurl'], '/') . '/bfp/';
       $this->filesDirectoryUrl = preg_replace('/^http(s)?:\/\//', '//', $this->filesDirectoryUrl);
       
       if (!file_exists($this->filesDirectoryPath)) {
           Debug::log('Creating filesDirectoryPath', ['path' => $this->filesDirectoryPath]); // DEBUG-REMOVE
           @mkdir($this->filesDirectoryPath, 0755);
       }

       if (is_dir($this->filesDirectoryPath)) {
           if (!file_exists($this->filesDirectoryPath . '.htaccess')) {
               try {
                   file_put_contents($this->filesDirectoryPath . '.htaccess', 'Options -Indexes');
                   Debug::log('.htaccess created', []); // DEBUG-REMOVE
               } catch (\Exception $err) {
                   Debug::log('.htaccess creation error', ['error' => $err->getMessage()]); // DEBUG-REMOVE
               }
           }
       }

       Debug::log('Directories checked/created', [
           'filesDirectoryPath' => $this->filesDirectoryPath ?? null,
           'filesDirectoryUrl' => $this->filesDirectoryUrl ?? null,
           'baseExists' => isset($this->filesDirectoryPath) && file_exists($this->filesDirectoryPath)
       ]); // DEBUG-REMOVE
   }
   
   /**
    * Clear directory contents
    */
   public function clearDir(string $dirPath): void {
       Debug::log('Entering clearDir()', ['dirPath' => $dirPath]); // DEBUG-REMOVE
       try {
           if (empty($dirPath) || !file_exists($dirPath) || !is_dir($dirPath)) {
               Debug::log('clearDir: invalid dir', ['dirPath' => $dirPath]); // DEBUG-REMOVE
               return;
           }
           $dirPath = rtrim($dirPath, '\\/') . '/';
           $files = glob($dirPath . '*', GLOB_MARK);
           foreach ($files as $file) {
               if (is_dir($file)) {
                   Debug::log('clearDir: recursing into dir', ['file' => $file]); // DEBUG-REMOVE
                   $this->clearDir($file);
               } else {
                   Debug::log('clearDir: deleting file', ['file' => $file]); // DEBUG-REMOVE
                   unlink($file);
               }
           }
       } catch (\Exception $err) {
           Debug::log('clearDir: exception', ['error' => $err->getMessage()]); // DEBUG-REMOVE
           return;
       }
       Debug::log('Directory cleared', ['dirPath' => $dirPath]); // DEBUG-REMOVE
   }
   
   /**
    * Get files directory path
    */
   public function getFilesDirectoryPath(): string {
       Debug::log('Returned filesDirectoryPath', ['path' => $this->filesDirectoryPath]); // DEBUG-REMOVE
       return $this->filesDirectoryPath;
   }
   
   /**
    * Get files directory URL
    */
   public function getFilesDirectoryUrl(): string {
       Debug::log('Returned filesDirectoryUrl', ['url' => $this->filesDirectoryUrl]); // DEBUG-REMOVE
       return $this->filesDirectoryUrl;
   }
   
   /**
    * Clear expired transients to maintain cache
    */
   public function clearExpiredTransients(): void {
       Debug::log('Entering clearExpiredTransients()', []); // DEBUG-REMOVE
       $transient = get_transient('bfp_clear_expired_transients');
       if (!$transient || 24 * 60 * 60 <= time() - intval($transient)) {
           set_transient('bfp_clear_expired_transients', time());
           delete_expired_transients();
           Debug::log('Expired transients cleared if needed', []); // DEBUG-REMOVE
       }
       Debug::log('Exiting clearExpiredTransients()', []); // DEBUG-REMOVE
   }
   
   /**
    * Get product files with filtering options
    * 
    * @param array $args Arguments for file retrieval
    * @return array Filtered audio files
    */
   public function getProductFilesInternal(array $args): array {
       Debug::log('Entering getProductFilesInternal()', ['args' => $args]);
       if (empty($args['product'])) {
           Debug::log('getProductFilesInternal: empty product', []);
           return [];
       }

       $product = $args['product'];
       $files = $this->getAllProductFiles($product, []);
       if (empty($files)) {
           Debug::log('getProductFilesInternal: no files', []);
           return [];
       }

       $audioFiles = [];
       Debug::log('getProductFilesInternal: about to check files', ['count' => count($files), 'first_key' => array_key_first($files)]);
       foreach ($files as $index => $file) {
           Debug::log('getProductFilesInternal: checking file', ['index' => $index, 'file' => substr($file['file'] ?? '', -50)]);
           if (!empty($file['file']) && false !== ($mediaType = $this->isAudio($file['file']))) {
               $file['media_type'] = $mediaType;

               if (isset($args['file_id'])) {
                   if ($args['file_id'] == $index) {
                       $audioFiles[$index] = $file;
                       Debug::log('getProductFilesInternal: found file_id', ['index' => $index]);
                       return $audioFiles;
                   }
               } elseif (!empty($args['first'])) {
                   $audioFiles[$index] = $file;
                   Debug::log('getProductFilesInternal: returning first', ['index' => $index]);
                   return $audioFiles;
               } elseif (!empty($args['all'])) {
                   $audioFiles[$index] = $file;
               }
           }
       }
       Debug::log('Product audio files filtered', ['audioFilesCount' => count($audioFiles)]);
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
       Debug::log('Entering getAllProductFiles()', ['product' => is_object($product) ? $product->get_id() : null]);
       if (!is_object($product) || !method_exists($product, 'get_type')) {
           Debug::log('getAllProductFiles: not a product object', []);
           return $filesArr;
       }

       $productType = $product->get_type();
       $id = $product->get_id();
       
       // Check purchase status using WooCommerce function directly
       $purchased = false;
       if (function_exists('wc_customer_bought_product')) {
           $purchased = wc_customer_bought_product('', get_current_user_id(), $id);
       }

       if ('variation' == $productType) {
           $_files = $product->get_downloads();
           $_files = $this->editFilesArray($id, $_files);
           $filesArr = array_merge($filesArr, $_files);
       } else {
           if (!$this->config->getState('_bfp_enable_player', false, $id)) {
               Debug::log('getAllProductFiles: player not enabled', ['productId' => $id]);
               return $filesArr;
           }

           $ownDemos = intval($this->config->getState('_bfp_use_custom_demos', 0, $id));
           $files = $this->config->getState('_bfp_demos_list', [], $id);
           if (false === $purchased && $ownDemos && !empty($files)) {
               $directOwnDemos = intval($this->config->getState('_bfp_direct_demo_links', 0, $id));
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
       Debug::log('All product files collected', ['filesArrCount' => count($filesArr)]);
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
       Debug::log('Entering editFilesArray()', ['productId' => $productId, 'filesCount' => count($files), 'playSrc' => $playSrc]); // DEBUG-REMOVE
       $pFiles = [];
       foreach ($files as $key => $file) {
           // Just use the key as-is (GUID)
           if (gettype($file) == 'object') {
               $file = (array) $file->get_data();
           }
           $file['product'] = $productId;
           $file['play_src'] = $playSrc;
           $pFiles[$key] = $file;
       }
       Debug::log('Product files array edited', ['pFilesCount' => count($pFiles)]); // DEBUG-REMOVE
       return $pFiles;
   }
   
   /**
    * Get product files - public interface
    * 
    * @param int $productId Product ID
    * @return array All audio files for the product
    */
   public function getProductFiles(int $productId): array {
       Debug::log('Entering getProductFiles()', ['productId' => $productId]); // DEBUG-REMOVE
       $product = wc_get_product($productId);
       if (!$product) {
           Debug::log('getProductFiles: no product', []);
           return [];
       }
       
       $result = $this->getProductFilesInternal([
           'product' => $product,
           'all' => true
       ]);
       Debug::log('Product files returned', ['resultCount' => count($result)]); // DEBUG-REMOVE
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
       Debug::log('getStreamingUrl()', ['productId' => $productId, 'fileIndex' => $fileIndex]); // DEBUG-REMOVE
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
       Debug::log('processCloudUrl()', ['url' => $url]);
       if (strpos($url, 'drive.google.com') !== false) {
           return Cloud::getGoogleDriveDownloadUrl($url);
       }
       Debug::log('Cloud URL processed', ['url' => $url]);
       return $url;
   }
   
   /**
    * Check if URL is local and return path
    * 
    * @param string $url File URL
    * @return string|false Local path or false
    */
   public function isLocal(string $url): string|false {
       Debug::log('isLocal()', ['url' => $url]); // DEBUG-REMOVE
       $uploadDir = wp_upload_dir();
       
       // Check if URL is within upload directory
       if (strpos($url, $uploadDir['baseurl']) === 0) {
           $relativePath = str_replace($uploadDir['baseurl'], '', $url);
           $localPath = $uploadDir['basedir'] . $relativePath;
           
           if (file_exists($localPath)) {
               Debug::log('isLocal: found localPath', ['localPath' => $localPath]); // DEBUG-REMOVE
               return $localPath;
           }
       }
       
       // Check if it's a relative path
       if (!filter_var($url, FILTER_VALIDATE_URL)) {
           $localPath = ABSPATH . ltrim($url, '/');
           if (file_exists($localPath)) {
               Debug::log('isLocal: found relative localPath', ['localPath' => $localPath]); // DEBUG-REMOVE
               return $localPath;
           }
       }
       
       Debug::log('isLocal: not found', []); // DEBUG-REMOVE
       return false;
   }
   
   /**
    * Get MIME type for file
    * 
    * @param string $filePath File path
    * @return string MIME type
    */
   public function getMimeType(string $filePath): string {
       Debug::log('getMimeType()', ['filePath' => $filePath]); // DEBUG-REMOVE
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
       
       Debug::log('MIME type determined', ['mimeType' => $mimeType]); // DEBUG-REMOVE
       return $mimeType;
   }
   
   /**
    * Check if URL is a playlist
    * 
    * @param string $url URL to check
    * @return bool
    */
   public function isPlaylist(string $url): bool {
       Debug::log('isPlaylist()', ['url' => $url]); // DEBUG-REMOVE
       $playlistExtensions = ['m3u', 'm3u8', 'pls', 'xspf'];
       $extension = strtolower(pathinfo($url, PATHINFO_EXTENSION));
       $result = in_array($extension, $playlistExtensions);
       Debug::log('Playlist status checked', ['result' => $result]); // DEBUG-REMOVE
       return $result;
   }
   
   /**
    * Get the correct player type for the file
    * 
    * @param string $filePath File path to check
    * @return string Player type: 'audio', 'video', or 'image'
    */
   public function getPlayerType(string $filePath): string {
       Debug::log('getPlayerType()', ['filePath' => $filePath]); // DEBUG-REMOVE
       if ($this->isVideo($filePath)) {
           return 'video';
       } elseif ($this->isImage($filePath)) {
           return 'image';
       }
       Debug::log('Player type determined', ['filePath' => $filePath]); // DEBUG-REMOVE
       return 'audio'; // Default to audio player
   }
   
   /**
    * Check if file is audio and return media type
    * 
    * @param string $file File URL or path
    * @return string|false Media type or false if not audio
    */
   public function isAudio(string $file): string|false {
       Debug::log('isAudio()', ['file' => $file]); // DEBUG-REMOVE
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
           Debug::log('isAudio: extension match', ['mediaType' => $audioExtensions[$extension]]); // DEBUG-REMOVE
           return $audioExtensions[$extension];
       }
       
       // Check for cloud URLs without extensions
       if ($this->isCloudUrl($file)) {
           Debug::log('isAudio: cloud url', []); // DEBUG-REMOVE
           return 'mp3'; // Default for cloud URLs
       }
       
       // Check actual MIME type if local file
       if ($localPath = $this->isLocal($file)) {
           if ($this->hasAudioMimeType($localPath)) {
               Debug::log('isAudio: has audio mime', []); // DEBUG-REMOVE
               return 'mp3'; // Default media type
           }
       }
       
       Debug::log('isAudio: not audio', []); // DEBUG-REMOVE
       return false;
   }
   
   /**
    * Check if file is video
    * 
    * @param string $file File URL or path
    * @return bool True if video file
    */
   public function isVideo(string $file): bool {
       Debug::log('isVideo()', ['file' => $file]); // DEBUG-REMOVE
       $videoExtensions = ['mp4', 'webm', 'ogv', 'mov', 'avi', 'wmv', 'flv', 'm4v'];
       $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
       
       if (in_array($extension, $videoExtensions)) {
           Debug::log('isVideo: extension match', []); // DEBUG-REMOVE
           return true;
       }
       
       // Check actual MIME type if local file
       if ($localPath = $this->isLocal($file)) {
           $result = $this->hasVideoMimeType($localPath);
           Debug::log('isVideo: mime check', ['result' => $result]); // DEBUG-REMOVE
           return $result;
       }
       
       Debug::log('isVideo: not video', []); // DEBUG-REMOVE
       return false;
   }
   
   /**
    * Check if file is image
    * 
    * @param string $file File URL or path
    * @return bool True if image file
    */
   public function isImage(string $file): bool {
       Debug::log('isImage()', ['file' => $file]); // DEBUG-REMOVE
       $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg'];
       $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
       
       if (in_array($extension, $imageExtensions)) {
           Debug::log('isImage: extension match', []); // DEBUG-REMOVE
           return true;
       }
       
       // Check actual MIME type if local file
       if ($localPath = $this->isLocal($file)) {
           $result = $this->hasImageMimeType($localPath);
           Debug::log('isImage: mime check', ['result' => $result]); // DEBUG-REMOVE
           return $result;
       }
       
       Debug::log('isImage: not image', []); // DEBUG-REMOVE
       return false;
   }
   
   /**
    * Fix URL encoding and format
    * 
    * @param string $url URL to fix
    * @return string Fixed URL
    */
   public function fixUrl(string $url): string {
       Debug::log('fixUrl()', ['url' => $url]); // DEBUG-REMOVE
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
       
       Debug::log('URL fixed', ['url' => $url]); // DEBUG-REMOVE
       return $url;
   }
   
   /**
    * Get file path for a product file
    * 
    * @param int $productId Product ID
    * @param string $fileIndex File index
    * @return string|null File path or null if not found
    */
   public function getFilePath(int $productId, string $fileIndex): ?string {
       Debug::log('getFilePath()', ['productId' => $productId, 'fileIndex' => $fileIndex]); // DEBUG-REMOVE
       $files = $this->getProductFiles($productId);
       
       if (isset($files[$fileIndex]) && !empty($files[$fileIndex]['file'])) {
           Debug::log('getFilePath: found', ['file' => $files[$fileIndex]['file']]); // DEBUG-REMOVE
           return $files[$fileIndex]['file'];
       }
       
       Debug::log('getFilePath: not found', []); // DEBUG-REMOVE
       return null;
   }
   
   /**
    * Stream file with range request support
    * 
    * @param string $filePath File to stream
    * @param array $options Streaming options
    * @return void
    */
   public function streamFile(string $filePath, array $options = []): void {
       Debug::log('Entering streamFile()', ['filePath' => $filePath, 'options' => $options]); // DEBUG-REMOVE
       if (!file_exists($filePath) || !is_readable($filePath)) {
           Debug::log('streamFile: file not found or unreadable', []); // DEBUG-REMOVE
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
      
      Debug::log('File streamed', ['filePath' => $filePath, 'options' => $options]); // DEBUG-REMOVE
      exit;
  }
  
  /**
   * Check if URL is a cloud storage URL
   * 
   * @param string $url URL to check
   * @return bool
   */
  private function isCloudUrl(string $url): bool {
      Debug::log('isCloudUrl()', ['url' => $url]); // DEBUG-REMOVE
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
      
      Debug::log('Audio MIME type checked', ['filePath' => $filePath]); // DEBUG-REMOVE
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
      
      Debug::log('Video MIME type checked', ['filePath' => $filePath]); // DEBUG-REMOVE
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
      
      Debug::log('Image MIME type checked', ['filePath' => $filePath]); // DEBUG-REMOVE
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
          
          Debug::log('OAuth file uploaded', ['targetPath' => $targetPath]); // DEBUG-REMOVE
          return true;
      }
      
      return false;
  }
}
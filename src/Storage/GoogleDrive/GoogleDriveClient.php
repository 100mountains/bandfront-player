<?php
namespace bfp\Modules\GoogleDrive;

use Google_Client;
use Google_Service_Drive;
use Google_Service_Drive_DriveFile;
use Google_Service_Drive_Permission;

/**
 * Google Drive API Client for Bandfront Player
 *
 * @package BandfrontPlayer
 * @subpackage Modules
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Google Drive Client Class
 * Handles full Google Drive API integration
 */
class GoogleDriveClient {
    
    private string $key;
    private string $credentialFile;
    private array $errors = [];
    private string $callbackUri;
    private string $redirectUri;
    private ?Google_Service_Drive $driveService = null;
    
    // MIME types mapping
    private array $mimeTypes = [
        'png' => ['image/png', 'image/x-png'],
        'bmp' => ['image/bmp', 'image/x-bmp', 'image/x-bitmap', 'image/x-xbitmap', 'image/x-win-bitmap', 'image/x-windows-bmp', 'image/ms-bmp', 'image/x-ms-bmp', 'application/bmp', 'application/x-bmp', 'application/x-win-bitmap'],
        'gif' => ['image/gif'],
        'jpeg' => ['image/jpeg', 'image/pjpeg'],
        'mp3' => ['audio/mpeg', 'audio/mpg', 'audio/mpeg3', 'audio/mp3'],
        'wav' => ['audio/x-wav', 'audio/wave', 'audio/wav'],
        'ogg' => ['audio/ogg', 'video/ogg', 'application/ogg'],
        'flac' => ['audio/x-flac'],
        'm4a' => ['audio/x-m4a'],
        'aac' => ['audio/x-acc'],
        // ... add other types as needed
    ];

    /**
     * Constructor
     */
    public function __construct(string $key, string $credentialsPath, string $callbackUri, string $redirectUri) {
        if (!class_exists('Google_Client')) {
            require_once dirname(__DIR__) . '/google-drive/google-api-php-client/vendor/autoload.php';
        }
        
        $this->key = $key;
        $this->credentialFile = str_replace('\\', '/', $credentialsPath);
        $this->callbackUri = $callbackUri;
        $this->redirectUri = $redirectUri;
    }

    /**
     * Initialize connection and ensure BFP folder exists
     */
    public function init(): string|false {
        error_reporting(E_ERROR | E_PARSE);
        
        if ($this->connect()) {
            try {
                $folderId = $this->exists('bfp', 'application/vnd.google-apps.folder');
                if (!$folderId) {
                    $folderId = $this->createFolder('bfp');
                }
                return $folderId ?: false;
            } catch (\Exception $e) {
                $this->errors[] = $e->getMessage();
            }
        }
        return false;
    }

    /**
     * Connect to Google Drive
     */
    public function connect(): bool {
        try {
            $client = new Google_Client();
            $client->setAuthConfig($this->key);
            
            if (!class_exists('Google_Service_Drive')) {
                require_once dirname(__DIR__) . '/google-drive/google-api-php-client/vendor/autoload.php';
            }
            
            $client->addScope(Google_Service_Drive::DRIVE);

            if (file_exists($this->credentialFile) && !isset($_GET['bfp-drive-credential'])) {
                $accessToken = file_get_contents($this->credentialFile);
                $client->setAccessToken($accessToken);

                // Refresh the token if it's expired
                if ($client->isAccessTokenExpired()) {
                    $refreshTokenSaved = $client->getRefreshToken();
                    $client->fetchAccessTokenWithRefreshToken($refreshTokenSaved);
                    file_put_contents($this->credentialFile, json_encode($client->getAccessToken()));
                }

                $this->driveService = new Google_Service_Drive($client);
            } else {
                $client->setRedirectUri($this->callbackUri);
                $client->addScope(Google_Service_Drive::DRIVE);
                $client->setAccessType('offline');
                $client->setApprovalPrompt('force');
                
                if (!isset($_GET['code'])) {
                    $authUrl = $client->createAuthUrl();
                    $authUrlSanitized = filter_var($authUrl, FILTER_SANITIZE_URL);
                    
                    if (!headers_sent()) {
                        header('Location: ' . $authUrlSanitized);
                    } else {
                        print '<script>document.location.href="' . esc_url($authUrlSanitized) . '"</script>';
                        exit;
                    }
                }

                $client->authenticate($_GET['code']);
                $accessToken = $client->getAccessToken();
                file_put_contents($this->credentialFile, json_encode($accessToken));
                
                $uriSanitized = filter_var($this->redirectUri, FILTER_SANITIZE_URL);
                if (!headers_sent()) {
                    header('Location: ' . $uriSanitized);
                } else {
                    print '<script>document.location.href="' . esc_url($uriSanitized) . '"</script>';
                    exit;
                }
            }
        } catch (\Exception $e) {
            $this->errors[] = $e->getMessage();
            return false;
        }
        return true;
    }

    /**
     * Check if file or folder exists
     */
    public function exists(string $name, string $mime = 'application/vnd.google-apps.folder'): string|false {
        try {
            $query = 'name="' . urlencode($name) . '" and mimeType="' . urlencode($mime) . '"';
            $items = $this->driveService->files->listFiles(['q' => $query])->getFiles();
            
            return count($items) ? $items[0]->id : false;
        } catch (\Exception $e) {
            $this->errors[] = $e->getMessage();
        }
        return false;
    }

    /**
     * Create a folder
     */
    public function createFolder(string $name, array $args = []): string|false {
        try {
            $metadata = new Google_Service_Drive_DriveFile([
                'name' => $name,
                'mimeType' => 'application/vnd.google-apps.folder',
            ]);

            // Set parent if provided
            if (!empty($args['parent'])) {
                $metadata->setParents([$args['parent']]);
            }

            $folder = $this->driveService->files->create($metadata, ['fields' => 'id']);
            return $folder ? $folder->id : false;
        } catch (\Exception $e) {
            $this->errors[] = $e->getMessage();
        }
        return false;
    }

    /**
     * Upload a file
     */
    public function uploadFile(string $name, string $content, array $args = []): array|false {
        $folderId = $this->init();
        if (!$folderId) {
            return false;
        }

        try {
            // Determine MIME type
            $mimeType = 'audio/mpeg'; // default
            if (!empty($args['mime'])) {
                $mimeType = $args['mime'];
            } elseif (!empty($args['extension'])) {
                $extension = strtolower($args['extension']);
                $mimeType = $this->extensionToMime($extension) ?: 'audio/mpeg';
            }

            $metadata = new Google_Service_Drive_DriveFile([
                'name' => $name,
                'description' => $args['description'] ?? '',
                'mimeType' => $mimeType,
            ]);

            // Set parent folder
            $metadata->setParents([$args['parent'] ?? $folderId]);

            $file = $this->driveService->files->create($metadata, [
                'data' => $content,
                'uploadType' => 'media',
                'fields' => 'id,mimeType,webContentLink',
            ]);

            if ($file) {
                $fileId = $file->getId();
                
                // Make file publicly readable
                $permissions = new Google_Service_Drive_Permission([
                    'type' => 'anyone',
                    'role' => 'reader',
                ]);

                $this->driveService->permissions->create($fileId, $permissions, ['fields' => 'id']);

                $extension = $this->mimeToExtension($file->getMimeType()) ?: 'mp3';
                $apiKey = get_option('_bfp_drive_api_key', '');

                return [
                    'id' => $fileId,
                    'url' => "https://www.googleapis.com/drive/v3/files/{$fileId}?alt=media&key={$apiKey}&ext=.{$extension}",
                ];
            }
        } catch (\Exception $e) {
            $this->errors[] = $e->getMessage();
        }
        return false;
    }

    /**
     * Delete a file
     */
    public function deleteFile(string $fileId): bool {
        try {
            if ($this->init()) {
                $this->driveService->files->delete($fileId);
                return true;
            }
        } catch (\Exception $e) {
            $this->errors[] = $e->getMessage();
        }
        return false;
    }

    /**
     * Get file info
     */
    public function getFile(string $fileId): array|false {
        try {
            if ($this->init()) {
                $file = $this->driveService->files->get($fileId, ['fields' => 'id,mimeType,webContentLink']);
                
                if ($file) {
                    $extension = $this->mimeToExtension($file->getMimeType()) ?: 'mp3';
                    $apiKey = get_option('_bfp_drive_api_key', '');
                    
                    return [
                        'id' => $fileId,
                        'url' => "https://www.googleapis.com/drive/v3/files/{$fileId}?alt=media&key={$apiKey}&ext=.{$extension}",
                    ];
                }
            }
        } catch (\Exception $e) {
            $this->errors[] = $e->getMessage();
        }
        return false;
    }

    /**
     * Get errors
     */
    public function getErrors(): array {
        return $this->errors;
    }

    /**
     * Convert extension to MIME type
     */
    private function extensionToMime(string $extension): string|false {
        return isset($this->mimeTypes[$extension]) ? $this->mimeTypes[$extension][0] : false;
    }

    /**
     * Convert MIME type to extension
     */
    private function mimeToExtension(string $mime): string|false {
        foreach ($this->mimeTypes as $ext => $mimes) {
            if (in_array($mime, $mimes)) {
                return $ext;
            }
        }
        return false;
    }
}


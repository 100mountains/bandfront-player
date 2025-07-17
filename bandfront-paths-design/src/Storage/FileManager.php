<?php
namespace Bandfront\Storage;

use Bandfront\Core\Config;

/**
 * FileManager
 * 
 * Manages local file operations for audio files.
 * 
 * @package Bandfront\Storage
 * @since 2.0.0
 */
class FileManager {
    
    private Config $config;

    public function __construct(Config $config) {
        $this->config = $config;
    }

    /**
     * Get the directory path for audio files.
     *
     * @return string
     */
    public function getFilesDirectoryPath(): string {
        return wp_upload_dir()['basedir'] . '/bandfront-audio';
    }

    /**
     * Get the URL for audio files.
     *
     * @return string
     */
    public function getFilesDirectoryUrl(): string {
        return wp_upload_dir()['baseurl'] . '/bandfront-audio';
    }

    /**
     * Create the directory for audio files if it doesn't exist.
     *
     * @return void
     */
    public function createFilesDirectory(): void {
        $path = $this->getFilesDirectoryPath();
        if (!file_exists($path)) {
            mkdir($path, 0755, true);
        }
    }

    /**
     * Delete purchased files for a specific user.
     *
     * @return void
     */
    public function deletePurchasedFiles(): void {
        // Logic to delete purchased files
    }

    /**
     * Get the file path for a specific audio file.
     *
     * @param string $fileName
     * @return string
     */
    public function getFilePath(string $fileName): string {
        return $this->getFilesDirectoryPath() . '/' . $fileName;
    }

    /**
     * Check if a file exists.
     *
     * @param string $fileName
     * @return bool
     */
    public function fileExists(string $fileName): bool {
        return file_exists($this->getFilePath($fileName));
    }

    /**
     * Save an audio file.
     *
     * @param string $fileName
     * @param string $fileContent
     * @return bool
     */
    public function saveFile(string $fileName, string $fileContent): bool {
        return file_put_contents($this->getFilePath($fileName), $fileContent) !== false;
    }
}

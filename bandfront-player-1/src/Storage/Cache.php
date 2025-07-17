<?php
namespace Bandfront\Storage;

/**
 * Cache Management
 * 
 * This class handles caching for audio files to improve performance
 * and reduce redundant processing.
 */
class Cache {
    
    private string $cacheDir;

    public function __construct(string $cacheDir) {
        $this->cacheDir = rtrim($cacheDir, '/') . '/';
        $this->initializeCacheDirectory();
    }

    /**
     * Initialize the cache directory.
     */
    private function initializeCacheDirectory(): void {
        if (!file_exists($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
    }

    /**
     * Get the cache file path for a specific audio file.
     *
     * @param string $fileName The name of the audio file.
     * @return string The full path to the cache file.
     */
    public function getCacheFilePath(string $fileName): string {
        return $this->cacheDir . md5($fileName) . '.cache';
    }

    /**
     * Check if a cached file exists.
     *
     * @param string $fileName The name of the audio file.
     * @return bool True if the cached file exists, false otherwise.
     */
    public function hasCache(string $fileName): bool {
        return file_exists($this->getCacheFilePath($fileName));
    }

    /**
     * Retrieve cached data for a specific audio file.
     *
     * @param string $fileName The name of the audio file.
     * @return mixed The cached data or null if not found.
     */
    public function getCache(string $fileName): mixed {
        $cacheFilePath = $this->getCacheFilePath($fileName);
        return $this->hasCache($fileName) ? unserialize(file_get_contents($cacheFilePath)) : null;
    }

    /**
     * Save data to the cache for a specific audio file.
     *
     * @param string $fileName The name of the audio file.
     * @param mixed $data The data to cache.
     */
    public function setCache(string $fileName, mixed $data): void {
        file_put_contents($this->getCacheFilePath($fileName), serialize($data));
    }

    /**
     * Clear the cache for a specific audio file.
     *
     * @param string $fileName The name of the audio file.
     */
    public function clearCache(string $fileName): void {
        $cacheFilePath = $this->getCacheFilePath($fileName);
        if ($this->hasCache($fileName)) {
            unlink($cacheFilePath);
        }
    }

    /**
     * Clear all cache files in the cache directory.
     */
    public function clearAllCache(): void {
        array_map('unlink', glob($this->cacheDir . '*'));
    }
}
<?php
namespace Bandfront\Core;

use WP_Error;

/**
 * Configuration Management
 * 
 * Manages settings and state for the Bandfront Player plugin.
 * Provides simple get/set/save methods and utilizes the WordPress 2025 metadata API.
 */
class Config {
    
    private array $settings = [];
    
    public function __construct() {
        $this->loadSettings();
    }
    
    /**
     * Load settings from the database
     */
    private function loadSettings(): void {
        $this->settings = get_option('bandfront_player_settings', []);
    }
    
    /**
     * Get a setting value
     * 
     * @param string $key Setting key
     * @param mixed $default Default value if not found
     * @return mixed Setting value
     */
    public function get(string $key, mixed $default = null): mixed {
        return $this->settings[$key] ?? $default;
    }
    
    /**
     * Set a setting value
     * 
     * @param string $key Setting key
     * @param mixed $value Setting value
     * @return void
     */
    public function set(string $key, mixed $value): void {
        $this->settings[$key] = $value;
        update_option('bandfront_player_settings', $this->settings);
    }
    
    /**
     * Save all settings
     * 
     * @return void
     */
    public function save(): void {
        update_option('bandfront_player_settings', $this->settings);
    }
    
    /**
     * Validate a setting value
     * 
     * @param string $key Setting key
     * @param mixed $value Setting value
     * @return WP_Error|null Returns an error if validation fails, null otherwise
     */
    public function validate(string $key, mixed $value): ?WP_Error {
        // Add validation logic as needed
        return null;
    }
    
    /**
     * Get all settings
     * 
     * @return array All settings
     */
    public function getAll(): array {
        return $this->settings;
    }
}
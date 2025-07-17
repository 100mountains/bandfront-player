<?php
declare(strict_types=1);

namespace Bandfront\Core;

use Bandfront\Config;

/**
 * Plugin Bootstrap
 * 
 * Handles plugin initialization and component registration
 * following WordPress 2025 best practices
 * 
 * @package Bandfront\Core
 * @since 2.0.0
 */
class Bootstrap {
    
    private static ?Bootstrap $instance = null;
    private string $pluginFile;
    private Config $config;
    private array $components = [];
    
    /**
     * Private constructor for singleton pattern
     */
    private function __construct(string $pluginFile) {
        $this->pluginFile = $pluginFile;
    }
    
    /**
     * Initialize the plugin
     * 
     * @param string $pluginFile Main plugin file path
     */
    public static function init(string $pluginFile): void {
        if (self::$instance === null) {
            self::$instance = new self($pluginFile);
            self::$instance->boot();
        }
    }
    
    /**
     * Get singleton instance
     */
    public static function getInstance(): ?self {
        return self::$instance;
    }
    
    /**
     * Boot the plugin
     */
    private function boot(): void {
        // Initialize configuration first
        $this->config = new Config();
        $this->components['config'] = $this->config;
        
        // Initialize core components
        $this->initializeCore();
        
        // Register hooks after all components are loaded
        $this->components['hooks'] = new Hooks($this);
        
        // Allow extensions to hook in
        do_action('bandfront_player_initialized', $this);
    }
    
    /**
     * Initialize core components
     */
    private function initializeCore(): void {
        // Initialize core components here
    }
    
    /**
     * Get a component instance
     * 
     * @param string $name Component name
     * @return object|null Component instance or null
     */
    public function getComponent(string $name): ?object {
        return $this->components[$name] ?? null;
    }
    
    /**
     * Get configuration instance
     */
    public function getConfig(): Config {
        return $this->config;
    }
    
    /**
     * Get plugin file path
     */
    public function getPluginFile(): string {
        return $this->pluginFile;
    }
    
    /**
     * Plugin activation handler
     */
    public function activate(): void {
        // Activation logic here
    }
    
    /**
     * Plugin deactivation handler
     */
    public function deactivate(): void {
        // Deactivation logic here
    }
}
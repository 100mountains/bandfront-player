<?php
declare(strict_types=1);

namespace Bandfront\Core;

use Bandfront\Config;
use Bandfront\Audio\Player;
use Bandfront\Audio\Streamer;
use Bandfront\Audio\Processor;
use Bandfront\Audio\Analytics;
use Bandfront\Admin\Admin;
use Bandfront\WooCommerce\Integration as WooCommerceIntegration;
use Bandfront\Storage\FileManager;
use Bandfront\REST\StreamingEndpoint;
use Bandfront\Utils\Preview;
use Bandfront\Utils\Debug;

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
        // Enable debugging if needed
        if (defined('WP_DEBUG') && WP_DEBUG) {
            Debug::enable();
        }
        
        // Initialize configuration first
        $this->config = new Config();
        $this->components['config'] = $this->config;
        
        // Initialize core components
        $this->initializeCore();
        
        // Initialize context-specific components
        if (is_admin()) {
            $this->initializeAdmin();
        }
        
        $this->initializePublic();
        
        // Initialize REST API
        if ($this->isRestRequest()) {
            $this->initializeREST();
        }
        
        // Register hooks after all components are loaded
        $this->components['hooks'] = new Hooks($this);
        
        // Allow extensions to hook in
        do_action('bandfront_player_initialized', $this);
    }
    
    /**
     * Initialize core components
     */
    private function initializeCore(): void {
        // Storage layer
        $this->components['file_manager'] = new FileManager($this->config);
        
        // Audio components
        $this->components['player'] = new Player($this->config, $this->components['file_manager']);
        $this->components['streamer'] = new Streamer($this->config, $this->components['file_manager']);
        $this->components['processor'] = new Processor($this->config, $this->components['file_manager']);
        $this->components['analytics'] = new Analytics($this->config);
        
        // Utilities
        $this->components['preview'] = new Preview($this->config);
    }
    
    /**
     * Initialize admin components
     */
    private function initializeAdmin(): void {
        // Delay admin initialization to ensure WordPress is ready
        add_action('init', function() {
            if (!isset($this->components['admin'])) {
                $this->components['admin'] = new Admin($this->config);
            }
        }, 1);
    }
    
    /**
     * Initialize public-facing components
     */
    private function initializePublic(): void {
        // WooCommerce integration if available
        if ($this->isWooCommerceActive()) {
            $this->components['woocommerce'] = new WooCommerceIntegration(
                $this->config,
                $this->components['player'],
                $this->components['file_manager']
            );
        }
    }
    
    /**
     * Initialize REST API components
     */
    private function initializeREST(): void {
        add_action('rest_api_init', function() {
            $this->components['rest_streaming'] = new StreamingEndpoint(
                $this->components['streamer'],
                $this->config
            );
        });
    }
    
    /**
     * Check if WooCommerce is active
     */
    private function isWooCommerceActive(): bool {
        return class_exists('WooCommerce') || function_exists('WC');
    }
    
    /**
     * Check if this is a REST request
     */
    private function isRestRequest(): bool {
        return defined('REST_REQUEST') && REST_REQUEST;
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
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Run component activation routines
        foreach ($this->components as $component) {
            if (method_exists($component, 'activate')) {
                $component->activate();
            }
        }
        
        // Set activation flag
        update_option('bandfront_player_activated', time());
    }
    
    /**
     * Plugin deactivation handler
     */
    public function deactivate(): void {
        // Run component deactivation routines
        foreach ($this->components as $component) {
            if (method_exists($component, 'deactivate')) {
                $component->deactivate();
            }
        }
        
        // Clean up transients
        $this->cleanupTransients();
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Clean up plugin transients
     */
    private function cleanupTransients(): void {
        global $wpdb;
        
        $wpdb->query(
            "DELETE FROM {$wpdb->options} 
             WHERE option_name LIKE '_transient_bfp_%' 
             OR option_name LIKE '_transient_timeout_bfp_%'"
        );
    }
}

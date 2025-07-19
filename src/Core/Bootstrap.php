<?php
declare(strict_types=1);

namespace Bandfront\Core;

use Bandfront\Core\Config;
use Bandfront\Audio\Audio;
use Bandfront\Audio\Player;
use Bandfront\Audio\Analytics;
use Bandfront\Audio\Preview;
use Bandfront\Audio\PlaybackController;
use Bandfront\Admin\Admin;
use Bandfront\WooCommerce\WooCommerceIntegration;
use Bandfront\WooCommerce\ProductProcessor;
use Bandfront\WooCommerce\FormatDownloader;
use Bandfront\Storage\FileManager;
use Bandfront\REST\StreamController;
use Bandfront\UI\Renderer;
use Bandfront\UI\AdminRenderer;
use Bandfront\Utils\Debug;

// Set domain for Core
Debug::domain('core-bootstrap');

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
        // Initialize components in dependency order
        $this->initializeCore();
        
        // Initialize Debug with Config
        Debug::init($this->components['config']);
        
        // Now we can use debug logging
        Debug::bootstrap('Starting plugin boot sequence', ['pluginFile' => $this->pluginFile]);

        Debug::bootstrap('Initializing audio components');
        $this->initializeAudio();
        
        // Context-specific components
        Debug::bootstrap('Initializing admin components');
        $this->initializeAdmin();

        Debug::bootstrap('Initializing WooCommerce components');
        $this->initializeWooCommerce();

        Debug::bootstrap('Initializing REST API components');
        $this->initializeREST();
        
        // Register hooks after all components are loaded
        Debug::bootstrap('Registering hooks');
        $this->components['hooks'] = new Hooks($this);
        
        // Allow extensions to hook in
        Debug::bootstrap('Boot sequence complete');
        do_action('bandfront_player_initialized', $this);
    }
    
    /**
     * Initialize core components
     */
    private function initializeCore(): void {
        // Configuration - Always first
        $this->components['config'] = new Config();
        
        // Storage layer
        $this->components['file_manager'] = new FileManager($this->components['config']);
        
        // Renderer - Used by multiple components
        $this->components['renderer'] = new Renderer(
            $this->components['config'],
            $this->components['file_manager']
        );
        
        // Admin renderer - separate from main renderer
        $this->components['admin_renderer'] = new AdminRenderer();
    }
    
    /**
     * Initialize audio components
     */
    private function initializeAudio(): void {
        // Core audio functionality
        $this->components['audio'] = new Audio(
            $this->components['config'],
            $this->components['file_manager']
        );
        
        // Player management - now includes FileManager
        $this->components['player'] = new Player(
            $this->components['config'],
            $this->components['renderer'],
            $this->components['audio'],
            $this->components['file_manager']  // Add FileManager dependency
        );
        
        // Analytics tracking
        $this->components['analytics'] = new Analytics($this->components['config']);
        
        // Preview generation
        $this->components['preview'] = new Preview(
            $this->components['config'],
            $this->components['audio'],
            $this->components['file_manager']
        );
        
        // Playback controller for AJAX handling
        $this->components['playback_controller'] = new PlaybackController(
            $this->components['config'],
            $this->components['analytics']
        );
    }
    
    /**
     * Initialize admin components
     */
    private function initializeAdmin(): void {
        // Always create admin component - hooks will only fire in admin context
        Debug::admin('Creating admin component');
        
        $this->components['admin'] = new Admin(
            $this->components['config'],
            $this->components['file_manager'],
            $this->components['admin_renderer']
        );
        
        Debug::admin('Admin component created');
    }
    
    /**
     * Initialize WooCommerce integration
     */
    private function initializeWooCommerce(): void {
        if (!$this->isWooCommerceActive()) {
            return;
        }
        
        // Main WooCommerce integration
        $this->components['woocommerce'] = new WooCommerceIntegration(
            $this->components['config'],
            $this->components['player'],
            $this->components['renderer']
        );
        
        // Product processor for audio generation
        $this->components['product_processor'] = new ProductProcessor(
            $this->components['config'],
            $this->components['audio'],
            $this->components['file_manager']
        );
        
        // Format downloader
        $this->components['format_downloader'] = new FormatDownloader(
            $this->components['config'],
            $this->components['file_manager']
        );
        
        // Download processor for bulk audio downloads
        $this->components['download_processor'] = new \Bandfront\Audio\DownloadProcessor(
            $this->components['config'],
            $this->components['file_manager']
        );
    }
    
    /**
     * Initialize REST API components
     */
    private function initializeREST(): void {
        add_action('rest_api_init', function() {
            // Stream controller for audio delivery
            $this->components['stream_controller'] = new StreamController(
                $this->components['config'],
                $this->components['audio'],
                $this->components['file_manager']
            );
            
            // Register routes
            $this->components['stream_controller']->registerRoutes();
        });
    }
    
    /**
     * Check if WooCommerce is active
     */
    private function isWooCommerceActive(): bool {
        return class_exists('WooCommerce') || function_exists('WC');
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
     * Get all components
     * @return array All registered components
     */
    public function getComponents(): array {
        return $this->components;
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
        // Register download endpoint if format downloader exists
        if ($formatDownloader = $this->getComponent('format_downloader')) {
            $formatDownloader->registerDownloadEndpoint();
        }
        
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
        // Clean up purchased files
        if ($fileManager = $this->getComponent('file_manager')) {
            $fileManager->deletePurchasedFiles();
        }
        
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
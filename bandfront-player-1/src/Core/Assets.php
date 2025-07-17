<?php
declare(strict_types=1);

namespace Bandfront\Core;

/**
 * Asset Management
 * 
 * Handles the loading of scripts and styles for the Bandfront Player plugin
 * following WordPress 2025 best practices.
 * 
 * @package Bandfront\Core
 * @since 2.0.0
 */
class Assets {
    
    private Bootstrap $bootstrap;

    public function __construct(Bootstrap $bootstrap) {
        $this->bootstrap = $bootstrap;
        $this->registerAssets();
    }

    /**
     * Register scripts and styles
     */
    private function registerAssets(): void {
        add_action('wp_enqueue_scripts', [$this, 'enqueuePublicAssets']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAdminAssets']);
    }

    /**
     * Enqueue public-facing assets
     */
    public function enqueuePublicAssets(): void {
        wp_enqueue_style('bfp-player-style', BFP_PLUGIN_URL . 'assets/css/player.css', [], BFP_VERSION);
        wp_enqueue_script('bfp-player-script', BFP_PLUGIN_URL . 'assets/js/player.js', ['jquery'], BFP_VERSION, true);
    }

    /**
     * Enqueue admin-facing assets
     */
    public function enqueueAdminAssets(string $hook): void {
        if ('settings_page_bandfront-player' === $hook) {
            wp_enqueue_style('bfp-admin-style', BFP_PLUGIN_URL . 'assets/css/admin.css', [], BFP_VERSION);
            wp_enqueue_script('bfp-admin-script', BFP_PLUGIN_URL . 'assets/js/admin.js', ['jquery'], BFP_VERSION, true);
        }
    }
}
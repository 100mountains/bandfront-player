<?php
/**
 * Bandfront Player - Main Plugin File
 *
 * @package BandfrontPlayer
 * @since 0.1
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Main plugin class
 */
class BandfrontPlayer {
    // ...existing properties...

    /**
     * Initialize the plugin
     */
    public function __construct() {
        // ...existing code...

        // Register settings
        add_action('admin_init', array($this, 'register_settings'));
    }

    /**
     * Register plugin settings
     */
    public function register_settings() {
        // ...existing settings...

        // Register cloud active tab setting
        register_setting('bfp_settings_group', '_bfp_cloud_active_tab');
    }

    /**
     * Save settings handler
     */
    public function save_settings() {
        // ...existing code...

        // Save cloud active tab state
        if (isset($_POST['_bfp_cloud_active_tab'])) {
            $attrs['_bfp_cloud_active_tab'] = sanitize_text_field($_POST['_bfp_cloud_active_tab']);
        }

        // ...existing code...
    }

    // ...existing methods...
}

// Initialize the plugin
$GLOBALS['BandfrontPlayer'] = new BandfrontPlayer();
```
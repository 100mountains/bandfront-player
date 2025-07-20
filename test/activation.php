<?php
/**
 * Plugin Activation Handler
 * 
 * @package Bandfront\Player
 */

if (!defined('ABSPATH')) {
    exit;
}

use Bandfront\Core\Bootstrap;
use Bandfront\Db\Installer;

/**
 * Handle plugin activation
 */
function BfpActivation() {
    // Run database installation/updates
    Installer::install();
    
    // Migrate from old structure if needed
    Installer::migrateFromOldStructure();
    
    // Initialize Bootstrap for activation tasks
    Bootstrap::init(BFP_PLUGIN_PATH);
    $bootstrap = Bootstrap::getInstance();
    
    if ($bootstrap) {
        // Register download endpoint if format downloader exists
        if ($formatDownloader = $bootstrap->getComponent('format_downloader')) {
            $formatDownloader->registerDownloadEndpoint();
        }
        
        // Run component activation routines
        $components = $bootstrap->getComponents();
        foreach ($components as $component) {
            if (method_exists($component, 'activate')) {
                $component->activate();
            }
        }
    }
    
    // Set activation flag
    update_option('bandfront_player_activated', time());
    
    // Flush rewrite rules
    flush_rewrite_rules();
}

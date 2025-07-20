<?php
/**
 * Plugin Deactivation Handler
 * 
 * @package Bandfront\Player
 */

if (!defined('ABSPATH')) {
    exit;
}

use Bandfront\Core\Bootstrap;

/**
 * Handle plugin deactivation
 */
function BfpDeactivation() {
    // Initialize Bootstrap for deactivation tasks
    $bootstrap = Bootstrap::getInstance();
    
    if ($bootstrap) {
        // Clean up purchased files
        if ($fileManager = $bootstrap->getComponent('file_manager')) {
            $fileManager->deletePurchasedFiles();
        }
        
        // Run component deactivation routines
        $components = $bootstrap->getComponents();
        foreach ($components as $component) {
            if (method_exists($component, 'deactivate')) {
                $component->deactivate();
            }
        }
    }
    
    // Clean up transients
    global $wpdb;
    $wpdb->query(
        "DELETE FROM {$wpdb->options} 
         WHERE option_name LIKE '_transient_bfp_%' 
         OR option_name LIKE '_transient_timeout_bfp_%'"
    );
    
    // Flush rewrite rules
    flush_rewrite_rules();
}

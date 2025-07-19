<?php
/**
 * Plugin Name: Bandfront Player
 * Plugin URI: https://bandfront.app
 * Description: Professional audio player for WooCommerce digital music stores
 * Version: 2.0.0
 * Author: Bandfront
 * Author URI: https://bandfront.app
 * License: GPL v2 or later
 * Text Domain: bandfront-player
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 8.0
 * WC requires at least: 7.0
 * WC tested up to: 8.5
 */

if (!defined('ABSPATH')) {
    exit;
}

// Constants
define('BFP_VERSION', '2.0.0');
define('BFP_PLUGIN_PATH', __FILE__);
define('BFP_PLUGIN_BASE_NAME', plugin_basename(__FILE__));
define('BFP_PLUGIN_URL', plugin_dir_url(__FILE__));
define('BFP_PLUGIN_DIR', plugin_dir_path(__FILE__));

// Composer autoloader
require_once __DIR__ . '/vendor/autoload.php';

use Bandfront\Core\Bootstrap;
use Bandfront\Db\Installer;

/**
 * Plugin activation
 */
register_activation_hook(__FILE__, function() {

    
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
});

/**
 * Plugin deactivation
 */
register_deactivation_hook(__FILE__, function() {
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
});

/**
 * Plugin uninstall
 */
register_uninstall_hook(__FILE__, ['Bandfront\Db\Installer', 'uninstall']);

/**
 * Initialize plugin
 */
add_action('plugins_loaded', function() {
    Bootstrap::init(BFP_PLUGIN_PATH);
}, 5);

/**
 * Declare WooCommerce compatibility
 */
add_action('before_woocommerce_init', function() {
    if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('cart_checkout_blocks', __FILE__, true);
    }
});

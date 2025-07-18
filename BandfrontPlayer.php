<?php
/**
 * Plugin Name: Bandfront Player
 * ...
 */

if (!defined('ABSPATH')) {
    exit;
}

// Constants
define('BFP_VERSION', '0.1');
define('BFP_PLUGIN_PATH', __FILE__);
define('BFP_PLUGIN_BASE_NAME', plugin_basename(__FILE__));
define('BFP_PLUGIN_URL', plugin_dir_url(__FILE__));

// Composer autoloader
require_once __DIR__ . '/vendor/autoload.php';

// Initialize using new Bootstrap system
use Bandfront\Core\Bootstrap;

// Hook initialization to ensure WordPress and other plugins are loaded
add_action('plugins_loaded', function() {
    Bootstrap::init(BFP_PLUGIN_PATH);
}, 5);


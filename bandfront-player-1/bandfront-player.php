<?php
/**
 * Plugin Name: Bandfront Player
 * Description: A modern audio player plugin for WordPress.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL2
 */

if (!defined('ABSPATH')) {
    exit;
}

// Constants
define('BFP_VERSION', '1.0.0');
define('BFP_PLUGIN_PATH', __FILE__);
define('BFP_PLUGIN_BASE_NAME', plugin_basename(__FILE__));
define('BFP_PLUGIN_URL', plugin_dir_url(__FILE__));

// Composer autoloader
require_once __DIR__ . '/vendor/autoload.php';

// Initialize plugin
Bandfront\Core\Bootstrap::init(BFP_PLUGIN_PATH);
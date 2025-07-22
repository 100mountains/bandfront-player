<?php
/**
 * Plugin Name: Bandfront Player
 * Plugin URI: https://bandfront.app
 * Description: Professional audio player for WooCommerce digital music stores
 * Version: 2.3.7
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
define('BFP_VERSION', '2.3.7');
define('BFP_PLUGIN_PATH', __FILE__);
define('BFP_PLUGIN_BASE_NAME', plugin_basename(__FILE__));
define('BFP_PLUGIN_URL', plugin_dir_url(__FILE__));
define('BFP_PLUGIN_DIR', plugin_dir_path(__FILE__));

// Composer autoloader
require_once __DIR__ . '/vendor/autoload.php';

// Include activation and deactivation handlers
require_once __DIR__ . '/BfpActivation.php';
require_once __DIR__ . '/BfpDeactivation.php';

use Bandfront\Core\Bootstrap;

/**
 * Plugin activation
 */
register_activation_hook(__FILE__, 'BfpActivation');

/**
 * Plugin deactivation
 */
register_deactivation_hook(__FILE__, 'BfpDeactivation');

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

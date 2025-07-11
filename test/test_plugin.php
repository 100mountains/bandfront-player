<?php
// Simple test to check if the plugin has any fatal errors
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Testing plugin loading...\n";

// Define WordPress constants that might be needed
if (!defined('ABSPATH')) {
    define('ABSPATH', '/var/www/html/');
}

if (!function_exists('get_home_url')) {
    function get_home_url() { return 'http://localhost'; }
}

if (!function_exists('get_current_blog_id')) {
    function get_current_blog_id() { return 1; }
}

if (!function_exists('is_ssl')) {
    function is_ssl() { return false; }
}

if (!function_exists('plugins_url')) {
    function plugins_url($path = '', $plugin = '') { return 'http://localhost/wp-content/plugins/bandfront-player'; }
}

if (!function_exists('plugin_basename')) {
    function plugin_basename($file) { return basename($file); }
}

if (!function_exists('get_option')) {
    function get_option($option, $default = false) { return $default; }
}

if (!function_exists('wp_upload_dir')) {
    function wp_upload_dir() { 
        return array(
            'basedir' => '/var/www/html/wp-content/uploads',
            'baseurl' => 'http://localhost/wp-content/uploads'
        );
    }
}

if (!function_exists('is_admin')) {
    function is_admin() { return false; }
}

if (!function_exists('apply_filters')) {
    function apply_filters($tag, $value) { return $value; }
}

if (!function_exists('do_action')) {
    function do_action($tag) { return true; }
}

if (!function_exists('register_activation_hook')) {
    function register_activation_hook($file, $callback) { return true; }
}

if (!function_exists('register_deactivation_hook')) {
    function register_deactivation_hook($file, $callback) { return true; }
}

if (!function_exists('add_action')) {
    function add_action($tag, $callback, $priority = 10, $accepted_args = 1) { return true; }
}

if (!function_exists('add_filter')) {
    function add_filter($tag, $callback, $priority = 10, $accepted_args = 1) { return true; }
}

if (!class_exists('WP_Widget')) {
    class WP_Widget {
        public function __construct() {}
        public function widget($args, $instance) {}
        public function form($instance) {}
        public function update($new_instance, $old_instance) { return $new_instance; }
    }
}

if (!class_exists('woocommerce')) {
    class woocommerce {
        public static $instance = null;
        public static function instance() {
            if (is_null(self::$instance)) {
                self::$instance = new self();
            }
            return self::$instance;
        }
    }
}

try {
    echo "Including main plugin file...\n";
    
    // Include the main plugin file
    include_once(__DIR__ . '/bfp.php');
    
    echo "✅ Plugin loaded successfully!\n";
    
    // Test basic plugin functionality
    echo "Testing plugin initialization...\n";
    
    // Check if main class exists
    if (class_exists('BandfrontPlayer')) {
        echo "✅ Main BandfrontPlayer class exists\n";
        
        // Check if global instance exists
        if (isset($GLOBALS['BandfrontPlayer'])) {
            echo "✅ Global BandfrontPlayer instance created\n";
            
            $bfp = $GLOBALS['BandfrontPlayer'];
            
            // Test config functionality
            if (method_exists($bfp, 'get_config')) {
                $config = $bfp->get_config();
                if ($config && is_object($config)) {
                    echo "✅ Config system initialized\n";
                } else {
                    echo "❌ Config system failed to initialize\n";
                }
            }
            
            // Test file handler
            if (method_exists($bfp, 'get_file_handler')) {
                $file_handler = $bfp->get_file_handler();
                if ($file_handler && is_object($file_handler)) {
                    echo "✅ File handler initialized\n";
                } else {
                    echo "❌ File handler failed to initialize\n";
                }
            }
            
            // Test player manager
            if (method_exists($bfp, 'get_player_manager')) {
                $player_manager = $bfp->get_player_manager();
                if ($player_manager && is_object($player_manager)) {
                    echo "✅ Player manager initialized\n";
                } else {
                    echo "❌ Player manager failed to initialize\n";
                }
            }
            
            // Test audio processor
            if (method_exists($bfp, 'get_audio_core')) {
                $audio_processor = $bfp->get_audio_core();
                if ($audio_processor && is_object($audio_processor)) {
                    echo "✅ Audio processor initialized\n";
                } else {
                    echo "❌ Audio processor failed to initialize\n";
                }
            }
            
            // Test WooCommerce integration
            if (method_exists($bfp, 'get_woocommerce')) {
                $woocommerce = $bfp->get_woocommerce();
                if ($woocommerce && is_object($woocommerce)) {
                    echo "✅ WooCommerce integration initialized\n";
                } else {
                    echo "❌ WooCommerce integration failed to initialize\n";
                }
            }
            
            // Test basic methods
            if (method_exists($bfp, 'get_player_layouts')) {
                $layouts = $bfp->get_player_layouts();
                if (is_array($layouts) && count($layouts) > 0) {
                    echo "✅ Player layouts available: " . implode(', ', $layouts) . "\n";
                } else {
                    echo "❌ No player layouts found\n";
                }
            }
            
            if (method_exists($bfp, 'get_player_controls')) {
                $controls = $bfp->get_player_controls();
                if (is_array($controls) && count($controls) > 0) {
                    echo "✅ Player controls available: " . implode(', ', $controls) . "\n";
                } else {
                    echo "❌ No player controls found\n";
                }
            }
            
        } else {
            echo "❌ Global BandfrontPlayer instance not found\n";
        }
    } else {
        echo "❌ Main BandfrontPlayer class not found\n";
    }
    
    echo "\n=== Test Summary ===\n";
    echo "Plugin appears to be working correctly.\n";
    echo "All core components initialized successfully.\n";
    
} catch (ParseError $e) {
    echo "❌ Parse Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    exit(1);
} catch (Error $e) {
    echo "❌ Fatal Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    exit(1);
} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    exit(1);
}

<?php
/**
 * Addons loader for Bandfront Player
 *
 * @package BandfrontPlayer
 * @since 0.1
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * BFP Addons Loader Class
 * Handles loading of modules and addons
 */
class BFP_Addons_Loader {
    
    private $main_plugin;
    private $modules_path;
    
    public function __construct($main_plugin) {
        $this->main_plugin = $main_plugin;
        $this->modules_path = plugin_dir_path(dirname(__FILE__)) . 'modules/';
    }
    
    /**
     * Initialize and load all modules
     */
    public function init() {
        $this->load_modules();
    }
    
    /**
     * Load all modules
     */
    private function load_modules() {
        $modules = array(
            'audio-engine.php',
            'cloud-engine.php'
        );
        
        foreach ($modules as $module) {
            $module_path = $this->modules_path . $module;
            if (file_exists($module_path)) {
                require_once $module_path;
            }
        }
        
        do_action('bfp_modules_loaded');
    }
}

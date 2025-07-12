<?php
/**
 * Configuration and State Management for Bandfront Player
 *
 * @package BandfrontPlayer
 * @since 0.1
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * BFP Config Class - Comprehensive State Manager
 * 
 * Provides context-aware state management with automatic inheritance:
 * Product Setting → Global Setting → Default Value
 */
class BFP_Config {
    
    private $main_plugin;
    private $_products_attrs = array();
    private $_global_attrs = array();
    private $_player_layouts = array('dark', 'light', 'custom');
    private $_player_controls = array('button', 'all', 'default');
    
    /**
     * Settings that support product-level overrides
     * Key: setting name, Value: default value
     */
    private $_overridable_settings = array(
        '_bfp_enable_player' => false,
        '_bfp_merge_in_grouped' => 0,
        '_bfp_single_player' => 0,
        '_bfp_preload' => 'none',
        '_bfp_play_all' => 0,
        '_bfp_loop' => 0,
        '_bfp_player_volume' => 1.0,
        '_bfp_secure_player' => false,
        '_bfp_file_percent' => 50,
        '_bfp_audio_engine' => 'mediaelement',
        '_bfp_own_demos' => 0,
        '_bfp_direct_own_demos' => 0,
        '_bfp_demos_list' => array(),
    );
    
    /**
     * Settings that are global-only (no product overrides)
     * Key: setting name, Value: default value
     */
    private $_global_only_settings = array(
        '_bfp_show_in' => 'all',
        '_bfp_player_layout' => 'dark',
        '_bfp_player_controls' => 'default',
        '_bfp_player_title' => 1,
        '_bfp_on_cover' => 1,
        '_bfp_registered_only' => 0,
        '_bfp_purchased' => 0,
        '_bfp_reset_purchased_interval' => 'daily',
        '_bfp_fade_out' => 0,
        '_bfp_purchased_times_text' => '- purchased %d time(s)',
        '_bfp_ffmpeg' => 0,
        '_bfp_ffmpeg_path' => '',
        '_bfp_ffmpeg_watermark' => '',
        '_bfp_players_in_cart' => false,
        '_bfp_play_simultaneously' => 0,
        '_bfp_message' => '',
        '_bfp_default_extension' => false,
        '_bfp_force_main_player_in_title' => 1,
        '_bfp_ios_controls' => false,
        '_bfp_onload' => false,
        '_bfp_disable_302' => 0,
        '_bfp_playback_counter_column' => 1,
        '_bfp_analytics_integration' => 'ua',
        '_bfp_analytics_property' => '',
        '_bfp_analytics_api_secret' => '',
        '_bfp_enable_visualizations' => 0,
        // Module states
        '_bfp_modules_enabled' => array(
            'audio-engine' => true,
            'cloud-engine' => true,
        ),
    );
    
    public function __construct($main_plugin) {
        $this->main_plugin = $main_plugin;
    }
    
    /**
     * Get state value with context-aware inheritance
     * 
     * This is the main method for retrieving any setting value.
     * It automatically handles inheritance based on the context.
     * 
     * @param string $key Setting key (e.g., '_bfp_audio_engine')
     * @param mixed $default Default value if not found anywhere
     * @param int|null $product_id Optional product ID for context
     * @param array $options Additional options (e.g., ['force_global' => true])
     * @return mixed The resolved setting value
     */
    public function get_state($key, $default = null, $product_id = null, $options = array()) {
        // Determine actual default if not provided
        if ($default === null) {
            $default = $this->get_default_value($key);
        }
        
        // Check if this is a global-only setting or forced to global
        if ($this->is_global_only($key) || !empty($options['force_global'])) {
            return $this->get_global_attr($key, $default);
        }
        
        // If product context is provided and setting is overridable
        if ($product_id && $this->is_overridable($key)) {
            // First check product-level cache
            if (isset($this->_products_attrs[$product_id][$key])) {
                $value = $this->_products_attrs[$product_id][$key];
                if ($this->is_valid_override($value, $key)) {
                    return apply_filters('bfp_state_value', $value, $key, $product_id, 'product');
                }
            }
            
            // Check if product has the meta
            if (metadata_exists('post', $product_id, $key)) {
                $value = get_post_meta($product_id, $key, true);
                
                // Cache it
                if (!isset($this->_products_attrs[$product_id])) {
                    $this->_products_attrs[$product_id] = array();
                }
                $this->_products_attrs[$product_id][$key] = $value;
                
                // Check if it's a valid override
                if ($this->is_valid_override($value, $key)) {
                    return apply_filters('bfp_state_value', $value, $key, $product_id, 'product');
                }
            }
        }
        
        // Fall back to global setting
        return $this->get_global_attr($key, $default);
    }
    
    /**
     * Check if a value is a valid override (not empty or 'global')
     */
    private function is_valid_override($value, $key) {
        // Special handling for audio engine
        if ($key === '_bfp_audio_engine') {
            return !empty($value) && 
                   $value !== 'global' && 
                   in_array($value, array('mediaelement', 'wavesurfer'));
        }
        
        // Special handling for boolean settings stored as strings
        if (in_array($key, array('_bfp_enable_player', '_bfp_secure_player', '_bfp_merge_in_grouped', 
                                 '_bfp_single_player', '_bfp_play_all', '_bfp_loop', '_bfp_own_demos', 
                                 '_bfp_direct_own_demos'))) {
            return $value === '1' || $value === 1 || $value === true;
        }
        
        // Special handling for preload values
        if ($key === '_bfp_preload') {
            return in_array($value, array('none', 'metadata', 'auto'));
        }
        
        // Special handling for file percent (must be 0-100)
        if ($key === '_bfp_file_percent') {
            return is_numeric($value) && $value >= 0 && $value <= 100;
        }
        
        // Special handling for volume (must be 0-1)
        if ($key === '_bfp_player_volume') {
            return is_numeric($value) && $value >= 0 && $value <= 1;
        }
        
        // For numeric values
        if (is_numeric($value)) {
            return true;
        }
        
        // For arrays
        if (is_array($value)) {
            return !empty($value);
        }
        
        // For strings
        return !empty($value) && $value !== 'global' && $value !== 'default';
    }
    
    /**
     * Get default value for a setting
     */
    private function get_default_value($key) {
        if (isset($this->_overridable_settings[$key])) {
            return $this->_overridable_settings[$key];
        }
        if (isset($this->_global_only_settings[$key])) {
            return $this->_global_only_settings[$key];
        }
        return false;
    }
    
    /**
     * Check if a setting is global-only
     */
    private function is_global_only($key) {
        return isset($this->_global_only_settings[$key]);
    }
    
    /**
     * Check if a setting supports product-level overrides
     */
    private function is_overridable($key) {
        return isset($this->_overridable_settings[$key]);
    }
    
    /**
     * Get all settings for a specific context
     * 
     * @param int|null $product_id Product ID for context
     * @return array All resolved settings
     */
    public function get_all_settings($product_id = null) {
        $settings = array();
        
        // Get all possible settings
        $all_keys = array_merge(
            array_keys($this->_global_only_settings),
            array_keys($this->_overridable_settings)
        );
        
        foreach ($all_keys as $key) {
            $settings[$key] = $this->get_state($key, null, $product_id);
        }
        
        return apply_filters('bfp_all_settings', $settings, $product_id);
    }
    
    /**
     * Bulk get multiple settings efficiently
     * 
     * @param array $keys Array of setting keys
     * @param int|null $product_id Product ID for context
     * @return array Associative array of key => value
     */
    public function get_states($keys, $product_id = null) {
        $values = array();
        
        foreach ($keys as $key) {
            $values[$key] = $this->get_state($key, null, $product_id);
        }
        
        return $values;
    }
    
    /**
     * Update state value
     * 
     * @param string $key Setting key
     * @param mixed $value New value
     * @param int|null $product_id Product ID (null for global)
     */
    public function update_state($key, $value, $product_id = null) {
        if ($product_id && $this->is_overridable($key)) {
            update_post_meta($product_id, $key, $value);
            // Clear cache
            if (isset($this->_products_attrs[$product_id][$key])) {
                $this->_products_attrs[$product_id][$key] = $value;
            }
        } elseif (!$product_id || $this->is_global_only($key)) {
            $this->_global_attrs[$key] = $value;
            // Update in database will be handled by save method
        }
    }
    
    /**
     * Delete state value (remove override)
     * 
     * @param string $key Setting key
     * @param int $product_id Product ID
     */
    public function delete_state($key, $product_id) {
        if ($this->is_overridable($key)) {
            delete_post_meta($product_id, $key);
            // Clear cache
            if (isset($this->_products_attrs[$product_id][$key])) {
                unset($this->_products_attrs[$product_id][$key]);
            }
        }
    }
    
    /**
     * Save all global settings to database
     */
    public function save_global_settings() {
        update_option('bfp_global_settings', $this->_global_attrs);
    }
    
    // Backward compatibility methods (keep existing interface)
    
    /**
     * Get product attribute - DEPRECATED, use get_state() instead
     * @deprecated Use get_state() with product_id parameter
     */
    public function get_product_attr($product_id, $attr, $default = false) {
        return $this->get_state($attr, $default, $product_id);
    }
    
    /**
     * Get global attribute - DEPRECATED, use get_state() instead
     * @deprecated Use get_state() without product_id parameter
     */
    public function get_global_attr($attr, $default = false) {
        if (empty($this->_global_attrs)) {
            $this->_global_attrs = get_option('bfp_global_settings', array());
        }
        if (!isset($this->_global_attrs[$attr])) {
            $this->_global_attrs[$attr] = $this->get_default_value($attr) !== null ? 
                                          $this->get_default_value($attr) : $default;
        }
        return apply_filters('bfp_global_attr', $this->_global_attrs[$attr], $attr);
    }
    
    /**
     * Update global attributes cache
     */
    public function update_global_attrs($attrs) {
        $this->_global_attrs = $attrs;
    }
    
    /**
     * Clear product attributes cache
     */
    public function clear_product_attrs_cache($product_id = null) {
        if ($product_id === null) {
            $this->_products_attrs = array();
        } else {
            unset($this->_products_attrs[$product_id]);
        }
    }
    
    /**
     * Get all global attributes
     */
    public function get_all_global_attrs() {
        if (empty($this->_global_attrs)) {
            $this->_global_attrs = get_option('bfp_global_settings', array());
        }
        return $this->_global_attrs;
    }
    
    /**
     * Get available player layouts
     */
    public function get_player_layouts() {
        return $this->_player_layouts;
    }
    
    /**
     * Get available player controls
     */
    public function get_player_controls() {
        return $this->_player_controls;
    }
    
    /**
     * Check if a module is enabled
     * 
     * @param string $module_name Module identifier
     * @return bool Whether the module is enabled
     */
    public function is_module_enabled($module_name) {
        $modules_enabled = $this->get_state('_bfp_modules_enabled');
        return isset($modules_enabled[$module_name]) ? $modules_enabled[$module_name] : false;
    }
    
    /**
     * Enable or disable a module
     * 
     * @param string $module_name Module identifier
     * @param bool $enabled Whether to enable or disable
     */
    public function set_module_state($module_name, $enabled) {
        $modules_enabled = $this->get_state('_bfp_modules_enabled');
        $modules_enabled[$module_name] = (bool)$enabled;
        $this->update_state('_bfp_modules_enabled', $modules_enabled);
    }
    
    /**
     * Get all available modules and their states
     * 
     * @return array Module name => enabled status
     */
    public function get_all_modules() {
        return $this->get_state('_bfp_modules_enabled');
    }
}

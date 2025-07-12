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

    private $_overridable_settings = array(
        '_bfp_enable_player' => false,
        '_bfp_audio_engine' => 'mediaelement',
        '_bfp_single_player' => 0,
        '_bfp_merge_in_grouped' => 0,
        '_bfp_play_all' => 0,
        '_bfp_loop' => 0,
        '_bfp_preload' => 'none',
        '_bfp_player_volume' => 1.0,
        '_bfp_secure_player' => false,
        '_bfp_file_percent' => 50,
        '_bfp_own_demos' => 0,
        '_bfp_direct_own_demos' => 0,
        '_bfp_demos_list' => array(),
    );

    private $_global_only_settings = array(
        '_bfp_show_in' => 'all',
        '_bfp_player_layout' => 'dark',
        '_bfp_player_controls' => 'default',
        '_bfp_player_title' => 1,
        '_bfp_on_cover' => 1,
        '_bfp_force_main_player_in_title' => 1,
        '_bfp_players_in_cart' => false,
        '_bfp_play_simultaneously' => 0,
        '_bfp_registered_only' => 0,
        '_bfp_purchased' => 0,
        '_bfp_reset_purchased_interval' => 'daily',
        '_bfp_fade_out' => 0,
        '_bfp_purchased_times_text' => '- purchased %d time(s)',
        '_bfp_message' => '',
        '_bfp_ffmpeg' => 0,
        '_bfp_ffmpeg_path' => '',
        '_bfp_ffmpeg_watermark' => '',
        '_bfp_onload' => false,
        '_bfp_playback_counter_column' => 1,
        '_bfp_analytics_integration' => 'ua',
        '_bfp_analytics_property' => '',
        '_bfp_analytics_api_secret' => '',
        '_bfp_enable_visualizations' => 0,
        '_bfp_modules_enabled' => array(
            'audio-engine' => true,
            'cloud-engine' => true,
        ),
        '_bfp_cloud_active_tab' => 'google-drive',
        '_bfp_cloud_dropbox' => array(
            'enabled' => false,
            'access_token' => '',
            'folder_path' => '/bandfront-demos',
        ),
        '_bfp_cloud_s3' => array(
            'enabled' => false,
            'access_key' => '',
            'secret_key' => '',
            'bucket' => '',
            'region' => 'us-east-1',
            'path_prefix' => 'bandfront-demos/',
        ),
        '_bfp_cloud_azure' => array(
            'enabled' => false,
            'account_name' => '',
            'account_key' => '',
            'container' => '',
            'path_prefix' => 'bandfront-demos/',
        ),
    );

    public function __construct($main_plugin) {
        $this->main_plugin = $main_plugin;
    }

    public function get_state($key, $default = null, $product_id = null, $options = array()) {
        if ($default === null) {
            $default = $this->get_default_value($key);
        }

        if ($this->is_global_only($key) || !empty($options['force_global'])) {
            return $this->get_global_attr($key, $default);
        }

        if ($product_id && $this->is_overridable($key)) {
            if (isset($this->_products_attrs[$product_id][$key])) {
                $value = $this->_products_attrs[$product_id][$key];
                if ($this->is_valid_override($value, $key)) {
                    return apply_filters('bfp_state_value', $value, $key, $product_id, 'product');
                }
            }

            if (metadata_exists('post', $product_id, $key)) {
                $value = get_post_meta($product_id, $key, true);

                if (!isset($this->_products_attrs[$product_id])) {
                    $this->_products_attrs[$product_id] = array();
                }

                $this->_products_attrs[$product_id][$key] = $value;

                if ($this->is_valid_override($value, $key)) {
                    return apply_filters('bfp_state_value', $value, $key, $product_id, 'product');
                }
            }
        }

        return $this->get_global_attr($key, $default);
    }

    private function is_valid_override($value, $key) {
        if ($key === '_bfp_audio_engine') {
            return !empty($value) &&
                   $value !== 'global' &&
                   in_array($value, array('mediaelement', 'wavesurfer'));
        }

        if (in_array($key, array('_bfp_enable_player', '_bfp_secure_player', '_bfp_merge_in_grouped',
                                 '_bfp_single_player', '_bfp_play_all', '_bfp_loop', '_bfp_own_demos',
                                 '_bfp_direct_own_demos'))) {
            return $value === '1' || $value === 1 || $value === true;
        }

        if ($key === '_bfp_preload') {
            return in_array($value, array('none', 'metadata', 'auto'));
        }

        if ($key === '_bfp_file_percent') {
            return is_numeric($value) && $value >= 0 && $value <= 100;
        }

        if ($key === '_bfp_player_volume') {
            return is_numeric($value) && $value >= 0 && $value <= 1;
        }

        if (is_numeric($value)) {
            return true;
        }

        if (is_array($value)) {
            return !empty($value);
        }

        return !empty($value) && $value !== 'global' && $value !== 'default';
    }

    private function get_default_value($key) {
        if (isset($this->_overridable_settings[$key])) {
            return $this->_overridable_settings[$key];
        }
        if (isset($this->_global_only_settings[$key])) {
            return $this->_global_only_settings[$key];
        }
        return false;
    }

    private function is_global_only($key) {
        return isset($this->_global_only_settings[$key]);
    }

    private function is_overridable($key) {
        return isset($this->_overridable_settings[$key]);
    }

    public function get_all_settings($product_id = null) {
        $settings = array();
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
    
    /**
     * Get all settings for admin forms with proper formatting
     * 
     * @return array Formatted settings ready for use in admin forms
     */
    public function get_admin_form_settings() {
        // Define all settings with their defaults
        $settings_config = array(
            // FFmpeg settings
            'ffmpeg' => array('key' => '_bfp_ffmpeg', 'type' => 'bool'),
            'ffmpeg_path' => array('key' => '_bfp_ffmpeg_path', 'type' => 'string'),
            'ffmpeg_watermark' => array('key' => '_bfp_ffmpeg_watermark', 'type' => 'string'),
            
            // Troubleshooting settings
            'force_main_player_in_title' => array('key' => '_bfp_force_main_player_in_title', 'type' => 'int'),
            'troubleshoot_onload' => array('key' => '_bfp_onload', 'type' => 'bool'),
            
            // Player settings
            'enable_player' => array('key' => '_bfp_enable_player', 'type' => 'bool'),
            'show_in' => array('key' => '_bfp_show_in', 'type' => 'string'),
            'players_in_cart' => array('key' => '_bfp_players_in_cart', 'type' => 'bool'),
            'player_style' => array('key' => '_bfp_player_layout', 'type' => 'string'),
            'volume' => array('key' => '_bfp_player_volume', 'type' => 'float'),
            'player_controls' => array('key' => '_bfp_player_controls', 'type' => 'string'),
            'single_player' => array('key' => '_bfp_single_player', 'type' => 'bool'),
            'secure_player' => array('key' => '_bfp_secure_player', 'type' => 'bool'),
            'file_percent' => array('key' => '_bfp_file_percent', 'type' => 'int'),
            'player_title' => array('key' => '_bfp_player_title', 'type' => 'int'),
            'merge_grouped' => array('key' => '_bfp_merge_in_grouped', 'type' => 'int'),
            'play_simultaneously' => array('key' => '_bfp_play_simultaneously', 'type' => 'int'),
            'play_all' => array('key' => '_bfp_play_all', 'type' => 'int'),
            'loop' => array('key' => '_bfp_loop', 'type' => 'int'),
            'on_cover' => array('key' => '_bfp_on_cover', 'type' => 'int'),
            'preload' => array('key' => '_bfp_preload', 'type' => 'string'),
            
            // Analytics settings
            'playback_counter_column' => array('key' => '_bfp_playback_counter_column', 'type' => 'int'),
            'analytics_integration' => array('key' => '_bfp_analytics_integration', 'type' => 'string'),
            'analytics_property' => array('key' => '_bfp_analytics_property', 'type' => 'string'),
            'analytics_api_secret' => array('key' => '_bfp_analytics_api_secret', 'type' => 'string'),
            
            // General settings
            'message' => array('key' => '_bfp_message', 'type' => 'string'),
            'registered_only' => array('key' => '_bfp_registered_only', 'type' => 'int'),
            'purchased' => array('key' => '_bfp_purchased', 'type' => 'int'),
            'reset_purchased_interval' => array('key' => '_bfp_reset_purchased_interval', 'type' => 'string'),
            'fade_out' => array('key' => '_bfp_fade_out', 'type' => 'int'),
            'purchased_times_text' => array('key' => '_bfp_purchased_times_text', 'type' => 'string'),
            'apply_to_all_players' => array('key' => '_bfp_apply_to_all_players', 'type' => 'int'),
            
            // Audio engine settings
            'audio_engine' => array('key' => '_bfp_audio_engine', 'type' => 'string'),
            'enable_visualizations' => array('key' => '_bfp_enable_visualizations', 'type' => 'int'),
        );
        
        // Get all keys
        $keys = array();
        foreach ($settings_config as $config) {
            $keys[] = $config['key'];
        }
        
        // Bulk fetch
        $raw_settings = $this->get_states($keys);
        
        // Format settings with the _bfp_ prefix for form compatibility
        $formatted_settings = array();
        foreach ($settings_config as $name => $config) {
            $value = $raw_settings[$config['key']] ?? null;
            
            // Apply type casting
            switch ($config['type']) {
                case 'bool':
                    $value = (bool) $value;
                    break;
                case 'int':
                    $value = intval($value);
                    break;
                case 'float':
                    $value = floatval($value);
                    break;
                case 'trim_int':
                    $value = intval(trim($value));
                    break;
                case 'string':
                default:
                    $value = (string) $value;
                    break;
            }
            
            // Use the full key with _bfp_ prefix for form field names
            $formatted_settings[$config['key']] = $value;
        }
        
        // Force on_cover to 1
        $formatted_settings['_bfp_on_cover'] = 1;
        
        return $formatted_settings;
    }
    
    /**
     * Get minimal player state for frontend/runtime use
     * 
     * @param int|null $product_id Optional product context for overrides
     * @return array Player state containing only runtime-necessary settings
     */
    public function get_player_state($product_id = null) {
        // Define the essential player settings needed for runtime
        $player_keys = array(
            '_bfp_enable_player',
            '_bfp_player_layout',
            '_bfp_player_controls',
            '_bfp_player_volume',
            '_bfp_single_player',
            '_bfp_secure_player',
            '_bfp_file_percent',
            '_bfp_play_all',
            '_bfp_loop',
            '_bfp_preload',
            '_bfp_audio_engine',
            '_bfp_merge_in_grouped',
        );
        
        // Use bulk fetch for efficiency
        $player_state = $this->get_states($player_keys, $product_id);
        
        // Apply any runtime-specific filters
        return apply_filters('bfp_player_state', $player_state, $product_id);
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

    // Enhanced compatibility methods
    public function get_global_attr($key, $default = null) {
        if (empty($this->_global_attrs)) {
            $this->_global_attrs = get_option('bfp_global_settings', array());
        }
        if (!isset($this->_global_attrs[$key])) {
            $this->_global_attrs[$key] = $this->get_default_value($key) !== null ? 
                                          $this->get_default_value($key) : $default;
        }
        return apply_filters('bfp_global_attr', $this->_global_attrs[$key], $key);
    }

    public function get_product_attr($product_id, $attr, $default = false) {
        return $this->get_state($attr, $default, $product_id);
    }
}

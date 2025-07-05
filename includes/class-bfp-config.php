<?php
/**
 * Configuration handling for Bandfront Player
 *
 * @package BandfrontPlayer
 * @since 0.1
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * BFP Config Class
 */
class BFP_Config {
    
    private $main_plugin;
    private $_products_attrs = array();
    private $_global_attrs = array();
    private $_player_layouts = array('mejs-classic', 'mejs-ted', 'mejs-wmp');
    private $_player_controls = array('button', 'all', 'default');
    
    public function __construct($main_plugin) {
        $this->main_plugin = $main_plugin;
    }
    
    /**
     * Get product attribute
     */
    public function get_product_attr($product_id, $attr, $default = false) {
        if (!isset($this->_products_attrs[$product_id])) {
            $this->_products_attrs[$product_id] = array();
        }
        if (!isset($this->_products_attrs[$product_id][$attr])) {
            if (metadata_exists('post', $product_id, $attr)) {
                $this->_products_attrs[$product_id][$attr] = get_post_meta($product_id, $attr, true);
            } else {
                $this->_products_attrs[$product_id][$attr] = $this->get_global_attr($attr, $default);
            }
        }
        return apply_filters('bfp_product_attr', $this->_products_attrs[$product_id][$attr], $product_id, $attr);
    }
    
    /**
     * Get global attribute
     */
    public function get_global_attr($attr, $default = false) {
        if (empty($this->_global_attrs)) {
            $this->_global_attrs = get_option('bfp_global_settings', array());
        }
        if (!isset($this->_global_attrs[$attr])) {
            $this->_global_attrs[$attr] = $default;
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
}

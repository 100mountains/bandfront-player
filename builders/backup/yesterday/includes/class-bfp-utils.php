<?php
/**
 * Utility functions for Bandfront Player
 *
 * @package BandfrontPlayer
 * @since 0.1
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * BFP Utils Class
 * Contains helper functions and utilities
 */
class BFP_Utils {
    
    /**
     * Get supported post types
     */
    public static function get_post_types($string = false) {
        $post_types = array('product', 'product_variation');
        $post_types = apply_filters('bfp_post_types', $post_types);
        
        if ($string) {
            $post_types_str = '';
            foreach ($post_types as $post_type) {
                $post_types_str .= '"' . esc_sql($post_type) . '",';
            }
            return rtrim($post_types_str, ',');
        }
        
        return $post_types;
    }
    
    /**
     * Sort list callback
     */
    public static function sort_list($a, $b) {
        if (!method_exists($a, 'get_menu_order') || !method_exists($b, 'get_menu_order')) {
            return 0;
        }
        
        $a_order = $a->get_menu_order();
        $b_order = $b->get_menu_order();
        
        if ($a_order == $b_order) {
            return 0;
        }
        
        return ($a_order < $b_order) ? -1 : 1;
    }
    
    /**
     * Add CSS class to element
     */
    public static function add_class($html, $class, $tag = 'audio') {
        $class = trim($class);
        if (empty($class)) {
            return $html;
        }
        
        return preg_replace(
            '/<' . $tag . '(\s+[^>]*)?>/i',
            '<' . $tag . ' class="' . esc_attr($class) . '" $1>',
            $html,
            1
        );
    }
    
    /**
     * Troubleshoot settings filter
     */
    public static function troubleshoot($value) {
        return $value;
    }
}

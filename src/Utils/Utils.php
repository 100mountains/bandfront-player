<?php
namespace bfp\Utils;

use bfp\Utils\Debug; // DEBUG-REMOVE

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
 * Utils Class
 * Contains helper functions and utilities
 */
class Utils {
    
    /**
     * Get supported post types
     *
     * @param bool $string Whether to return as SQL string
     * @return mixed Array of post types or SQL string
     */
    public static function getPostTypes(bool $string = false): mixed {
        $postTypes = ['product', 'product_variation'];
        $postTypes = apply_filters('bfp_post_types', $postTypes);
        
        if ($string) {
            $postTypesStr = '';
            foreach ($postTypes as $postType) {
                $postTypesStr .= '"' . esc_sql($postType) . '",';
            }
            return rtrim($postTypesStr, ',');
        }
        
        return $postTypes;
    }
    
    /**
     * Sort list callback
     *
     * @param object $a First item to compare
     * @param object $b Second item to compare
     * @return int Sort order
     */
    public static function sortList(object $a, object $b): int {
        if (!method_exists($a, 'get_menu_order') || !method_exists($b, 'get_menu_order')) {
            return 0;
        }
        
        $aOrder = $a->get_menu_order();
        $bOrder = $b->get_menu_order();
        
        if ($aOrder == $bOrder) {
            return 0;
        }
        
        return ($aOrder < $bOrder) ? -1 : 1;
    }
    
    /**
     * Add CSS class to element
     *
     * @param string $html HTML content
     * @param string $class CSS class to add
     * @param string $tag HTML tag to target
     * @return string Modified HTML
     */
    public static function addClass(string $html, string $class, string $tag = 'audio'): string {
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
     *
     * @param mixed $value Value to filter
     * @return mixed Filtered value
     */
    public static function troubleshoot(mixed $value): mixed {
        return $value;
    }
}
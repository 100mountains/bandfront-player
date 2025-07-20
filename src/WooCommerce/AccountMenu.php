<?php
/**
 * WooCommerce Account Menu Integration
 * 
 * @package Bandfront\WooCommerce
 */

namespace Bandfront\WooCommerce;

use Bandfront\Core\Config;

/**
 * Handles WooCommerce account menu customizations
 */
class AccountMenu {
    
    /**
     * Configuration instance
     *
     * @var Config
     */
    private Config $config;
    
    /**
     * Constructor
     *
     * @param Config $config Configuration instance
     */
    public function __construct(Config $config) {
        $this->config = $config;
    }
    
    /**
     * Register hooks
     */
    public function registerHooks(): void {
        // Only register if WooCommerce is active
        if (!class_exists('WooCommerce')) {
            return;
        }
        
        // Just reorder the menu to put downloads after dashboard and rename it
        add_filter('woocommerce_account_menu_items', [$this, 'reorderMenuItems'], 10, 1);
        add_filter('woocommerce_endpoint_downloads_title', [$this, 'customizeDownloadsTitle']);
    }
    
    /**
     * Reorder menu items to put downloads after dashboard and vault after downloads
     *
     * @param array $items Existing menu items
     * @return array Modified menu items
     */
    public function reorderMenuItems(array $items): array {
        // Don't create new endpoints, just reorder existing ones
        if (!isset($items['downloads'])) {
            return $items; // No downloads menu item exists
        }
        
        // Remove logout to add it back at the end
        $logout = $items['customer-logout'] ?? null;
        unset($items['customer-logout']);
        
        // Remove downloads and vault from their current positions
        unset($items['downloads']);
        $vault = null;
        if (isset($items['vault'])) {
            $vault = $items['vault'];
            unset($items['vault']);
        }
        
        // Rebuild the menu with specific ordering
        $newItems = [];
        
        foreach ($items as $key => $label) {
            $newItems[$key] = $label;
            
            // Add downloads right after dashboard
            if ($key === 'dashboard') {
                $newItems['downloads'] = __('My Downloads', 'bandfront-player');
                
                // Add vault right after downloads if it exists
                if ($vault !== null) {
                    $newItems['vault'] = $vault;
                }
            }
        }
        
        // If dashboard doesn't exist, put downloads first, then vault
        if (!isset($items['dashboard'])) {
            $orderedItems = ['downloads' => __('My Downloads', 'bandfront-player')];
            if ($vault !== null) {
                $orderedItems['vault'] = $vault;
            }
            $newItems = $orderedItems + $newItems;
        }
        
        // Add logout back at the end
        if ($logout) {
            $newItems['customer-logout'] = $logout;
        }
        
        return $newItems;
    }
    
    /**
     * Customize downloads endpoint title
     *
     * @param string $title Original title
     * @return string Modified title
     */
    public function customizeDownloadsTitle(string $title): string {
        return __('My Downloads', 'bandfront-player');
    }
}

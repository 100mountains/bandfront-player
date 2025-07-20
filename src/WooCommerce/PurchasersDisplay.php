<?php

namespace Bandfront\WooCommerce;

use Bandfront\Core\Config;
use Bandfront\UI\Renderer;

/**
 * Displays users who purchased a product on the product page
 * 
 * @since 1.0.0
 */
class PurchasersDisplay {
    
    /**
     * @var Config
     */
    private Config $config;
    
    /**
     * @var Renderer
     */
    private Renderer $renderer;
    
    /**
     * Constructor
     * 
     * @param Config $config
     * @param Renderer $renderer
     */
    public function __construct(Config $config, Renderer $renderer) {
        $this->config = $config;
        $this->renderer = $renderer;
    }
    
    /**
     * Display purchasers section on product page
     * 
     * @return void
     */
    public function displayPurchasers(): void {
        global $product;
        
        if (!$product || !is_a($product, 'WC_Product')) {
            return;
        }
        
        // Check if feature is enabled
        if (!$this->config->getState('_bfp_show_purchasers', true)) {
            return;
        }
        
        $purchasers = $this->getProductPurchasers($product->get_id());
        
        if (empty($purchasers)) {
            return;
        }
        
        // Limit display to configurable amount
        $maxDisplay = $this->config->getState('_bfp_max_purchasers_display', 10);
        $purchasers = array_slice($purchasers, 0, $maxDisplay);
        
        echo $this->renderer->renderPurchasersSection($purchasers, count($purchasers));
    }
    
    /**
     * Get users who purchased a specific product
     * 
     * @param int $productId
     * @return array Array of user data
     */
    private function getProductPurchasers(int $productId): array {
        global $wpdb;
        
        // Query orders containing this product with completed status
        $query = $wpdb->prepare("
            SELECT DISTINCT p.ID as order_id, pm.meta_value as customer_id
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
            INNER JOIN {$wpdb->prefix}woocommerce_order_items oi ON p.ID = oi.order_id
            INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta oim ON oi.order_item_id = oim.order_item_id
            WHERE p.post_type = 'shop_order'
            AND p.post_status IN ('wc-completed', 'wc-processing')
            AND pm.meta_key = '_customer_user'
            AND pm.meta_value != '0'
            AND oi.order_item_type = 'line_item'
            AND oim.meta_key = '_product_id'
            AND oim.meta_value = %d
            ORDER BY p.post_date DESC
        ", $productId);
        
        $results = $wpdb->get_results($query);
        
        if (empty($results)) {
            return [];
        }
        
        $purchasers = [];
        $seenUsers = [];
        
        foreach ($results as $result) {
            $userId = (int) $result->customer_id;
            
            // Skip duplicates
            if (in_array($userId, $seenUsers)) {
                continue;
            }
            
            $user = get_userdata($userId);
            if (!$user) {
                continue;
            }
            
            $purchasers[] = [
                'id' => $userId,
                'display_name' => $user->display_name,
                'avatar_url' => get_avatar_url($userId, ['size' => 48]),
                'profile_url' => get_author_posts_url($userId)
            ];
            
            $seenUsers[] = $userId;
        }
        
        return $purchasers;
    }
}

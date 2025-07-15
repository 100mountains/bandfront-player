<?php
namespace bfp;

/**
 * WooCommerce integration functionality for Bandfront Player
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * WooCommerce Integration Class
 * Handles all WooCommerce-specific functionality and integrations
 */
class WooCommerce {
    
    private Plugin $mainPlugin;
    private ?Renderer $renderer = null;
    
    /**
     * Constructor
     */
    public function __construct(Plugin $mainPlugin) {
        $this->mainPlugin = $mainPlugin;
        $this->addConsoleLog('WooCommerce integration initialized');
        
        // Add format download buttons to product pages
        add_action('woocommerce_after_single_product_summary', [$this, 'displayFormatDownloads'], 25);
    }
    
    /**
     * Get renderer instance
     */
    private function getRenderer(): Renderer {
        if ($this->renderer === null) {
            $this->renderer = new Renderer($this->mainPlugin);
        }
        return $this->renderer;
    }
    
    /**
     * Include the shortcode in the product title only if the player is enabled and playlist_watermark is not active
     * 
     * @param string $title The product title
     * @param \WC_Product $product The product object
     * @return string The modified title
     */
    public function woocommerceProductTitle(string $title, $product): string {
        if (!$product) {
            return $title;
        }
        
        $productId = $product->get_id();
        $enablePlayer = $this->mainPlugin->getConfig()->getState('_bfp_enable_player', false, $productId);
        $forceMainPlayer = $this->mainPlugin->getConfig()->getState('_bfp_force_main_player_in_title');
        $insertedPlayer = $this->mainPlugin->getPlayer()->getInsertedPlayer();
        
        // Allow plugins to override player insertion
        $shouldInsertPlayer = apply_filters('bfp_should_insert_player_in_title', 
            ($enablePlayer && $forceMainPlayer && !$insertedPlayer),
            $productId, 
            $product
        );
        
        if ($shouldInsertPlayer) {
            $this->mainPlugin->getPlayer()->setInsertedPlayer(true);
            $showIn = $this->mainPlugin->getConfig()->getState('_bfp_show_in', 'all', $productId);
            
            if (!is_admin() && $showIn !== 'single') {
                // Allow filtering the player HTML
                $playerHtml = $this->mainPlugin->getPlayer()->includeMainPlayer($product, false);
                $playerHtml = apply_filters('bfp_product_title_player_html', $playerHtml, $productId, $product);
                $title = $playerHtml . $title;
            }
        }
        
        return $title;
    }

    /**
     * Check if user has purchased a specific product
     * 
     * @param int $productId The product ID to check
     * @return string|false MD5 hash of user email if purchased, false otherwise
     */
    public function woocommerceUserProduct(int $productId): string|false {
        if (!is_user_logged_in()) {
            return false;
        }
        
        $userId = get_current_user_id();
        
        // Check if product was purchased
        $purchased = wc_customer_bought_product('', $userId, $productId);
        
        if ($purchased) {
            // Try to get the order ID
            $orders = wc_get_orders([
                'customer_id' => $userId,
                'limit' => -1,
                'status' => ['completed', 'processing'],
                'meta_query' => [
                    [
                        'key' => '_product_id',
                        'value' => $productId,
                        'compare' => '='
                    ]
                ]
            ]);
            
            if (!empty($orders)) {
                return (string) $orders[0]->get_id();
            }
            
            return '1'; // Return '1' for backward compatibility
        }
        
        return false;
    }
    
    /**
     * Get user download links for a product
     * 
     * @param int $productId The product ID
     * @return string HTML for download link(s)
     */
    public function woocommerceUserDownload(int $productId): string {
        $downloadLinks = [];
        
        if (is_user_logged_in()) {
            // Lazy-load the user downloads
            if (empty($this->mainPlugin->getCurrentUserDownloads()) && function_exists('wc_get_customer_available_downloads')) {
                $currentUser = wp_get_current_user();
                $downloads = wc_get_customer_available_downloads($currentUser->ID);
                
                /**
                 * Filter the user downloads
                 * 
                 * @param array $downloads The user's available downloads
                 * @param int $userId The user ID
                 */
                $downloads = apply_filters('bfp_user_downloads', $downloads, $currentUser->ID);
                
                $this->mainPlugin->setCurrentUserDownloads($downloads);
            }

            // Find downloads for this product
            foreach ($this->mainPlugin->getCurrentUserDownloads() as $download) {
                if ((int)$download['product_id'] === $productId) {
                    $downloadLinks[$download['download_id']] = $download['download_url'];
                }
            }
            
            /**
             * Filter the download links for a product
             * 
             * @param array $downloadLinks The download links
             * @param int $productId The product ID
             */
            $downloadLinks = apply_filters('bfp_product_download_links', $downloadLinks, $productId);
        }

        $downloadLinks = array_unique($downloadLinks);
        
        if (count($downloadLinks)) {
            $downloadLinks = array_values($downloadLinks);
            $linksJson = wp_json_encode($downloadLinks);
            
            // Enhanced accessibility with ARIA attributes
            return sprintf(
                '<a href="javascript:void(0);" data-download-links="%s" class="bfp-download-link" role="button" aria-label="%s">%s</a>',
                esc_attr($linksJson),
                esc_attr(sprintf(__('Download audio for product %d', 'bandfront-player'), $productId)),
                esc_html__('download', 'bandfront-player')
            );
        }
        
        return '';
    }
    
    /**
     * Replace the shortcode to display a playlist with all songs
     * 
     * @param array $atts Shortcode attributes
     * @return string HTML output
     */
    public function replacePlaylistShortcode(array $atts): string {
        if (!class_exists('woocommerce') || is_admin()) {
            return '';
        }

        global $post;

        $output = '';
        
        // Check if player insertion is enabled
        if (!$this->mainPlugin->getPlayer()->getInsertPlayer()) {
            return $output;
        }

        $atts = is_array($atts) ? $atts : [];
        
        // Handle special case for post with player enabled
        $postTypes = $this->mainPlugin->getPostTypes();
        if (
            empty($atts['products_ids']) &&
            empty($atts['purchased_products']) &&
            empty($atts['product_categories']) &&
            empty($atts['product_tags']) &&
            !empty($post) &&
            in_array($post->post_type, $postTypes, true)
        ) {
            try {
                ob_start();
                
                // Allow plugins to modify the post ID
                $postId = apply_filters('bfp_playlist_post_id', $post->ID);
                
                $this->mainPlugin->getPlayer()->includeAllPlayers($postId);
                $output = ob_get_clean();

                $class = isset($atts['class']) ? esc_attr($atts['class']) : '';

                if (strpos($output, 'bfp-player-list') !== false) {
                    return str_replace('bfp-player-container', $class . ' bfp-player-container', $output);
                }
                
                return $output;
            } catch (\Exception $err) {
                // Log the error using WordPress logging
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('BandFront Player Error: ' . $err->getMessage());
                }
                
                $atts['products_ids'] = $post->ID;
            }
        }

        // Process shortcode attributes with defaults
        $atts = $this->processShortcodeAttributes($atts);
        
        // Extract and validate attributes
        $productsIds = $this->validateProductIds($atts['products_ids']);
        $productCategories = $this->validateTaxonomyTerms($atts['product_categories']);
        $productTags = $this->validateTaxonomyTerms($atts['product_tags']);
        
        // Set force purchased flag
        $this->mainPlugin->setForcePurchasedFlag((int)$atts['purchased_only']);
        
        // Check if we have valid query parameters
        if (
            empty($productsIds) &&
            empty($productCategories) &&
            empty($productTags)
        ) {
            return $output;
        }

        // Build and return the playlist
        return $this->buildPlaylistOutput($productsIds, $productCategories, $productTags, (int)$atts['purchased_products'], $atts, $output);
    }
    
    /**
     * Process and sanitize shortcode attributes
     * 
     * @param array $atts Raw shortcode attributes
     * @return array Processed attributes with defaults
     */
    private function processShortcodeAttributes(array $atts): array {
        $defaults = [
            'title'                     => '',
            'products_ids'              => '*',
            'purchased_products'        => 0,
            'highlight_current_product' => 0,
            'continue_playing'          => 0,
            'player_style'              => $this->mainPlugin->getConfig()->getState('_bfp_player_layout'),
            'controls'                  => 'track',
            'layout'                    => 'new',
            'cover'                     => 0,
            'volume'                    => 1,
            'purchased_only'            => 0,
            'hide_purchase_buttons'     => 0,
            'class'                     => '',
            'loop'                      => 0,
            'purchased_times'           => 0,
            'hide_message'              => 0,
            'download_links'            => 0,
            'duration'                  => 1,
            'product_categories'        => '',
            'product_tags'              => '',
        ];
        
        /**
         * Filter the default shortcode attributes
         * 
         * @param array $defaults The default attributes
         */
        $defaults = apply_filters('bfp_playlist_shortcode_defaults', $defaults);
        
        // Merge defaults with provided attributes
        $atts = shortcode_atts($defaults, $atts, 'bfp-playlist');
        
        // Sanitize and type cast numeric values
        $numericFields = [
            'cover', 'volume', 'purchased_products', 'highlight_current_product',
            'continue_playing', 'purchased_only', 'hide_purchase_buttons',
            'loop', 'purchased_times'
        ];
        
        foreach ($numericFields as $field) {
            if ($field === 'volume') {
                $atts[$field] = is_numeric($atts[$field]) ? floatval($atts[$field]) : 0;
            } else {
                $atts[$field] = is_numeric($atts[$field]) ? intval($atts[$field]) : 0;
            }
        }
        
        /**
         * Filter the processed shortcode attributes
         * 
         * @param array $atts The processed attributes
         */
        return apply_filters('bfp_playlist_shortcode_atts', $atts);
    }
    
    /**
     * Validate and sanitize product IDs
     * 
     * @param string $productsIds Comma-separated product IDs or "*"
     * @return string Sanitized product IDs
     */
    private function validateProductIds(string $productsIds): string {
        $productsIds = preg_replace('/[^\d\,\*]/', '', $productsIds);
        $productsIds = preg_replace('/\,+/', ',', $productsIds);
        return trim($productsIds, ',');
    }
    
    /**
     * Validate and sanitize taxonomy terms
     * 
     * @param string $terms Comma-separated taxonomy terms
     * @return string Sanitized taxonomy terms
     */
    private function validateTaxonomyTerms(string $terms): string {
        $terms = preg_replace('/\s*\,\s*/', ',', $terms);
        $terms = preg_replace('/\,+/', ',', $terms);
        return trim($terms, ',');
    }
    
    /**
     * Build the playlist output HTML
     * 
     * @param string $productsIds Comma-separated product IDs
     * @param string $productCategories Comma-separated product categories
     * @param string $productTags Comma-separated product tags
     * @param int $purchasedProducts Whether to show only purchased products
     * @param array $atts Shortcode attributes
     * @param string $output Existing output
     * @return string Final HTML output
     */
    private function buildPlaylistOutput(string $productsIds, string $productCategories, string $productTags, int $purchasedProducts, array $atts, string $output): string {
        global $wpdb, $post;

        $currentPostId = !empty($post) ? (is_int($post) ? $post : $post->ID) : -1;

        // Allow plugins to modify the query
        $customQuery = apply_filters('bfp_playlist_products_query', null, $productsIds, $productCategories, $productTags, $purchasedProducts, $atts);
        
        if ($customQuery !== null) {
            $products = $wpdb->get_results($customQuery);
        } else {
            // Build standard query
            $query = $this->buildProductsQuery($productsIds, $productCategories, $productTags, $purchasedProducts);
            $products = $wpdb->get_results($query);
        }

        /**
         * Filter the products for the playlist
         * 
         * @param array $products The products to display
         * @param array $atts The shortcode attributes
         * @param int $currentPostId The current post ID
         */
        $products = apply_filters('bfp_playlist_products', $products, $atts, $currentPostId);

        if (!empty($products)) {
            return $this->getRenderer()->renderPlaylistProducts($products, $atts, $currentPostId, $output);
        }

        $this->mainPlugin->setForcePurchasedFlag(0);
        return $output;
    }
    
    /**
     * Build the SQL query for products
     * 
     * @param string $productsIds Comma-separated product IDs
     * @param string $productCategories Comma-separated product categories
     * @param string $productTags Comma-separated product tags
     * @param int $purchasedProducts Whether to show only purchased products
     * @return string SQL query
     */
    private function buildProductsQuery(string $productsIds, string $productCategories, string $productTags, int $purchasedProducts): string {
        global $wpdb;
        
        // Base query
        $query = 'SELECT posts.ID, posts.post_title FROM ' . $wpdb->posts . ' AS posts, ' . $wpdb->postmeta . ' as postmeta WHERE posts.post_status="publish" AND posts.post_type IN (' . $this->mainPlugin->getPostTypes(true) . ') AND posts.ID = postmeta.post_id AND postmeta.meta_key="_bfp_enable_player" AND (postmeta.meta_value="yes" OR postmeta.meta_value="1")';

        if (!empty($purchasedProducts)) {
            // Purchased products query
            $query = $this->buildPurchasedProductsQuery($query);
        } else {
            // Regular products query
            $query = $this->buildRegularProductsQuery($query, $productsIds, $productCategories, $productTags);
        }

        return $query;
    }
    
    /**
     * Build query for purchased products
     * 
     * @param string $query Base query
     * @return string Modified query
     */
    private function buildPurchasedProductsQuery(string $query): string {
        $currentUserId = get_current_user_id();
        if (0 == $currentUserId) {
            return $query . ' AND 1=0'; // No results if not logged in
        }

        $customerOrders = get_posts(
            [
                'meta_key'    => '_customer_user',
                'meta_value'  => $currentUserId,
                'post_type'   => 'shop_order',
                'post_status' => ['wc-completed', 'wc-processing'],
                'numberposts' => -1
            ]
        );

        if (empty($customerOrders)) {
            return $query . ' AND 1=0'; // No results if no orders
        }

        $productsIds = [];
        foreach ($customerOrders as $customerOrder) {
            $order = wc_get_order($customerOrder->ID);
            $items = $order->get_items();
            foreach ($items as $item) {
                $productsIds[] = $item->get_product_id();
            }
        }
        
        /**
         * Filter the purchased product IDs
         * 
         * @param array $productsIds The product IDs
         * @param int $currentUserId The user ID
         */
        $productsIds = apply_filters('bfp_purchased_products_ids', array_unique($productsIds), $currentUserId);
        
        if (empty($productsIds)) {
            return $query . ' AND 1=0'; // No results if no products
        }
        
        $productsIdsStr = implode(',', $productsIds);
        $query .= ' AND posts.ID IN (' . $productsIdsStr . ')';
        $query .= ' ORDER BY FIELD(posts.ID,' . $productsIdsStr . ')';
        
        return $query;
    }
    
    /**
     * Build query for regular products
     * 
     * @param string $query Base query
     * @param string $productsIds Comma-separated product IDs
     * @param string $productCategories Comma-separated product categories
     * @param string $productTags Comma-separated product tags
     * @return string Modified query
     */
    private function buildRegularProductsQuery(string $query, string $productsIds, string $productCategories, string $productTags): string {
        if (strpos($productsIds, '*') === false) {
            // Specific products
            $query .= ' AND posts.ID IN (' . $productsIds . ')';
            $query .= ' ORDER BY FIELD(posts.ID,' . $productsIds . ')';
        } else {
            // Products by taxonomy
            $taxQuery = [];

            if (!empty($productCategories)) {
                $categories = explode(',', $productCategories);
                $taxQuery[] = [
                    'taxonomy' => 'product_cat',
                    'field' => 'slug',
                    'terms' => $categories,
                    'include_children' => true,
                    'operator' => 'IN'
                ];
            }

            if (!empty($productTags)) {
                $tags = explode(',', $productTags);
                $taxQuery[] = [
                    'taxonomy' => 'product_tag',
                    'field' => 'slug',
                    'terms' => $tags,
                    'operator' => 'IN'
                ];
            }

            if (!empty($taxQuery)) {
                $taxQuery['relation'] = 'OR';
                $taxQuerySql = get_tax_sql($taxQuery, 'posts', 'ID');
                if (!empty($taxQuerySql['join'])) {
                    $query .= ' ' . $taxQuerySql['join'];
                }
                if (!empty($taxQuerySql['where'])) {
                    $query .= ' ' . $taxQuerySql['where'];
                }
            }

            $query .= ' ORDER BY posts.post_title ASC';
        }
        
        return $query;
    }
    
    /**
     * Display format download buttons on product page
     */
    public function displayFormatDownloads(): void {
        global $product;
        
        if (!$product || !is_a($product, 'WC_Product')) {
            return;
        }
        
        $productId = $product->get_id();
        $this->addConsoleLog('displayFormatDownloads checking', ['productId' => $productId]);
        
        // Check if user owns the product
        if (!$this->woocommerceUserProduct($productId)) {
            $this->addConsoleLog('displayFormatDownloads user does not own product');
            return;
        }
        
        // Get format downloader
        $formatDownloader = $this->mainPlugin->getFormatDownloader();
        if (!$formatDownloader) {
            $this->addConsoleLog('displayFormatDownloads no format downloader available');
            return;
        }
        
        // Display download buttons
        $html = $formatDownloader->getDownloadButtonsHtml($productId);
        if ($html) {
            echo '<div class="bfp-product-downloads">';
            echo $html;
            echo '</div>';
            $this->addConsoleLog('displayFormatDownloads buttons displayed');
        }
    }
    
    /**
     * Add console log for debugging
     */
    private function addConsoleLog(string $message, $data = null): void {
        echo '<script>console.log("[BFP WooCommerce] ' . esc_js($message) . '", ' . 
             wp_json_encode($data) . ');</script>';
    }
}
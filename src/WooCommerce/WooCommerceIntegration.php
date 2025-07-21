<?php
declare(strict_types=1);

namespace Bandfront\WooCommerce;

use Bandfront\Core\Config;
use Bandfront\Audio\Player;
use Bandfront\UI\Renderer;
use Bandfront\Utils\Debug;

// Set domain for WooCommerce
Debug::domain('woocommerce');

/**
 * WooCommerce integration functionality for Bandfront Player
 * 
 * @package Bandfront\WooCommerce
 * @since 2.0.0
 */
class WooCommerceIntegration {
    
    private Config $config;
    private Player $player;
    private Renderer $renderer;
    
    /**
     * Constructor - accepts only needed dependencies
     */
    public function __construct(Config $config, Player $player, Renderer $renderer) {
        Debug::log('WooCommerceIntegration.php:' . __LINE__ . ' Constructing WooCommerce integration', [
            'config' => get_class($config),
            'player' => get_class($player),
            'renderer' => get_class($renderer)
        ]); // DEBUG-REMOVE
        
        $this->config = $config;
        $this->player = $player;
        $this->renderer = $renderer;
        
        // Note: Hook registration moved to Core\Hooks.php
    }
    
    /**
     * Include the shortcode in the product title only if the player is enabled and playlist_watermark is not active
     * 
     * @param string $title The product title
     * @param \WC_Product $product The product object
     * @return string The modified title
     */
    public function woocommerceProductTitle(string $title, $product): string {
        Debug::log('WooCommerceIntegration.php:' . __LINE__ . ' Filtering product title for player injection', ['product_id' => $product ? $product->get_id() : null]); // DEBUG-REMOVE
        if (!$product) {
            return $title;
        }
        
        $productId = $product->get_id();
        $enablePlayer = $this->config->getState('_bfp_enable_player', false, $productId);
        $insertedPlayer = $this->player->getInsertedPlayer();
        
        // Allow plugins to override player insertion
        $shouldInsertPlayer = apply_filters('bfp_should_insert_player_in_title', 
            ($enablePlayer && $forceMainPlayer && !$insertedPlayer),
            $productId, 
            $product
        );
        
        if ($shouldInsertPlayer) {
            $this->player->setInsertedPlayer(true);
            $showIn = $this->config->getState('_bfp_show_in', 'all', $productId);
            
            if (!is_admin() && $showIn !== 'single') {
                // Allow filtering the player HTML
                $playerHtml = $this->player->includeMainPlayer($product, false);
                $playerHtml = apply_filters('bfp_product_title_player_html', $playerHtml, $productId, $product);
                Debug::log('WooCommerceIntegration.php:' . __LINE__ . ' Injecting player HTML into product title', ['product_id' => $productId]); // DEBUG-REMOVE
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
        Debug::log('WooCommerceIntegration.php:' . __LINE__ . ' Checking if user has purchased product', ['productId' => $productId, 'user_logged_in' => is_user_logged_in()]); // DEBUG-REMOVE
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
                Debug::log('WooCommerceIntegration.php:' . __LINE__ . ' User purchased product, returning order ID', ['order_id' => $orders[0]->get_id()]); // DEBUG-REMOVE
                return (string) $orders[0]->get_id();
            }
            
            Debug::log('WooCommerceIntegration.php:' . __LINE__ . ' User purchased product, returning fallback', []); // DEBUG-REMOVE
            return '1'; // Return '1' for backward compatibility
        }
        
        Debug::log('WooCommerceIntegration.php:' . __LINE__ . ' User has not purchased product', ['productId' => $productId]); // DEBUG-REMOVE
        return false;
    }
    
    /**
     * Get user download links for a product
     * 
     * @param int $productId The product ID
     * @return string HTML for download link(s)
     */
    public function woocommerceUserDownload(int $productId): string {
        Debug::log('WooCommerceIntegration.php:' . __LINE__ . ' Getting user download links for product', ['productId' => $productId]); // DEBUG-REMOVE
        $downloadLinks = [];
        
        if (is_user_logged_in()) {
            // Get current user downloads from runtime state
            $currentUserDownloads = $this->config->getState('_bfp_current_user_downloads');
            
            // Lazy-load the user downloads
            if (empty($currentUserDownloads) && function_exists('wc_get_customer_available_downloads')) {
                $currentUser = wp_get_current_user();
                $downloads = wc_get_customer_available_downloads($currentUser->ID);
                
                /**
                 * Filter the user downloads
                 * 
                 * @param array $downloads The user's available downloads
                 * @param int $userId The user ID
                 */
                $downloads = apply_filters('bfp_user_downloads', $downloads, $currentUser->ID);
                
                // Store in runtime state
                $this->config->updateState('_bfp_current_user_downloads', $downloads);
                $currentUserDownloads = $downloads;
            }

            // Find downloads for this product
            if (is_array($currentUserDownloads)) {
                foreach ($currentUserDownloads as $download) {
                    if ((int)$download['product_id'] === $productId) {
                        Debug::log('WooCommerceIntegration.php:' . __LINE__ . ' Found download link for product', ['download_id' => $download['download_id'], 'download_url' => $download['download_url']]); // DEBUG-REMOVE
                        $downloadLinks[$download['download_id']] = $download['download_url'];
                    }
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
            Debug::log('WooCommerceIntegration.php:' . __LINE__ . ' Returning download links for product', ['productId' => $productId, 'links' => $downloadLinks]); // DEBUG-REMOVE
            return sprintf(
                '<a href="javascript:void(0);" data-download-links="%s" class="bfp-download-link" role="button" aria-label="%s">%s</a>',
                esc_attr($linksJson),
                esc_attr(sprintf(__('Download audio for product %d', 'bandfront-player'), $productId)),
                esc_html__('download', 'bandfront-player')
            );
        }
        
        Debug::log('WooCommerceIntegration.php:' . __LINE__ . ' No download links found for product', ['productId' => $productId]); // DEBUG-REMOVE
        return '';
    }
    
    /**
     * Replace the shortcode to display a playlist with all songs
     * 
     * @param array $atts Shortcode attributes
     * @return string HTML output
     */
    public function replacePlaylistShortcode(array $atts): string {
        Debug::log('WooCommerceIntegration.php:' . __LINE__ . ' Processing playlist shortcode', ['atts' => $atts]); // DEBUG-REMOVE
        if (!class_exists('woocommerce') || is_admin()) {
            return '';
        }

        global $post;

        $output = '';
        
        // Check if player insertion is enabled
        if (!$this->player->getInsertPlayer()) {
            Debug::log('WooCommerceIntegration.php:' . __LINE__ . ' Player insertion not enabled for playlist shortcode', []); // DEBUG-REMOVE
            return $output;
        }

        $atts = is_array($atts) ? $atts : [];
        
        // Handle special case for post with player enabled
        $postTypes = $this->config->getPostTypes();
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
                
                $this->player->includeAllPlayers($postId);
                $output = ob_get_clean();

                $class = isset($atts['class']) ? esc_attr($atts['class']) : '';

                if (strpos($output, 'bfp-player-list') !== false) {
                    Debug::log('WooCommerceIntegration.php:' . __LINE__ . ' Returning playlist output with player list', ['postId' => $postId]); // DEBUG-REMOVE
                    return str_replace('bfp-player-container', $class . ' bfp-player-container', $output);
                }
                
                return $output;
            } catch (\Exception $err) {
                // Log the error using WordPress logging
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    Debug::log('WooCommerceIntegration.php:' . __LINE__ . ' Exception in playlist shortcode', ['error' => $err->getMessage()]); // DEBUG-REMOVE
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
        $this->config->updateState('_bfp_force_purchased_flag', (int)$atts['purchased_only']);
        
        // Check if we have valid query parameters
        if (
            empty($productsIds) &&
            empty($productCategories) &&
            empty($productTags)
        ) {
            Debug::log('WooCommerceIntegration.php:' . __LINE__ . ' No valid query parameters for playlist shortcode', []); // DEBUG-REMOVE
            return $output;
        }

        // Build and return the playlist
        Debug::log('WooCommerceIntegration.php:' . __LINE__ . ' Building playlist output', ['productsIds' => $productsIds, 'productCategories' => $productCategories, 'productTags' => $productTags]); // DEBUG-REMOVE
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
            'player_style'              => $this->config->getState('_bfp_player_layout'),
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
            return $this->renderer->renderPlaylistProducts($products, $atts, $currentPostId, $output);
        }

        $this->config->updateState('_bfp_force_purchased_flag', 0);
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
        
        $postTypes = $this->config->getPostTypes();
        $postTypesStr = "'" . implode("','", $postTypes) . "'";
        
        // Base query
        $query = 'SELECT posts.ID, posts.post_title FROM ' . $wpdb->posts . ' AS posts, ' . $wpdb->postmeta . ' as postmeta WHERE posts.post_status="publish" AND posts.post_type IN (' . $postTypesStr . ') AND posts.ID = postmeta.post_id AND postmeta.meta_key="_bfp_enable_player" AND (postmeta.meta_value="yes" OR postmeta.meta_value="1")';

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
    public function displayProductFormatDownloads(): void {
        global $product;
        
        if (!$product || !is_a($product, 'WC_Product')) {
            Debug::log('WooCommerceIntegration.php:' . __LINE__ . ' displayProductFormatDownloads: No valid product found', []); // DEBUG-REMOVE
            return;
        }
        
        $productId = $product->get_id();
        Debug::log('WooCommerceIntegration.php:' . __LINE__ . ' Checking for format downloads display', ['productId' => $productId]); // DEBUG-REMOVE
        
        // Check if user owns the product
        if (!$this->woocommerceUserProduct($productId)) {
            Debug::log('WooCommerceIntegration.php:' . __LINE__ . ' User does not own product, not displaying format downloads', ['productId' => $productId]); // DEBUG-REMOVE
            return;
        }
        
        // Get format downloader via Bootstrap
        $bootstrap = \Bandfront\Core\Bootstrap::getInstance();
        $formatDownloader = $bootstrap ? $bootstrap->getComponent('format_downloader') : null;
        
        if (!$formatDownloader) {
            Debug::log('WooCommerceIntegration.php:' . __LINE__ . ' No format downloader available', []); // DEBUG-REMOVE
            return;
        }
        
        // Display download buttons
        $html = $formatDownloader->getDownloadButtonsHtml($productId);
        if ($html) {
            echo '<div class="bfp-product-downloads">';
            echo $html;
            echo '</div>';
            Debug::log('WooCommerceIntegration.php:' . __LINE__ . ' Displayed format download buttons', ['productId' => $productId]); // DEBUG-REMOVE
        }
    }
    
    /**
     * Enqueue downloads page assets
     */
    public function enqueueDownloadsAssets(): void {
        Debug::log('WooCommerceIntegration.php:' . __LINE__ . ' Entering enqueueDownloadsAssets()', [
            'is_account_page' => function_exists('is_account_page') ? is_account_page() : 'function not exists',
            'current_url' => $_SERVER['REQUEST_URI'] ?? 'unknown',
            'BFP_PLUGIN_URL' => BFP_PLUGIN_URL,
            'expected_css_url' => BFP_PLUGIN_URL . 'assets/css/downloads.css'
        ]);
        
        wp_enqueue_style(
            'bfp-downloads',
            BFP_PLUGIN_URL . "assets/css/downloads.css",
            [],
            BFP_VERSION
        );
        
        
        Debug::log('WooCommerceIntegration.php:' . __LINE__ . ' Enqueued downloads CSS', [
            'handle' => 'bfp-downloads',
            'url' => BFP_PLUGIN_URL . "assets/css/downloads.css",
            'file_exists' => file_exists(plugin_dir_path(dirname(dirname(__FILE__))) . 'assets/css/downloads.css')
        ]);
        wp_enqueue_script(
            'bfp-downloads',
            BFP_PLUGIN_URL . "assets/js/downloads.js",
            ['jquery'],
            BFP_VERSION,
            true
        );
        
        // Localize script for AJAX
        wp_localize_script('bfp-downloads', 'bfpDownloads', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('audio_conversion_nonce'),
            'converting' => __('Converting...', 'bandfront-player'),
            'downloadAllAs' => __('Download All As...', 'bandfront-player'),
            'conversionFailed' => __('Conversion failed', 'bandfront-player'),
            'errorOccurred' => __('An error occurred. Please try again.', 'bandfront-player')
        ]);
    }
    
    /**
     * Display format downloads on account page
     */
    public function displayFormatDownloads(): void {
        Debug::log('WooCommerceIntegration: displayFormatDownloads called'); // DEBUG-REMOVE
        
        // Get the download renderer via Bootstrap
        $bootstrap = \Bandfront\Core\Bootstrap::getInstance();
        $renderer = new \Bandfront\UI\DownloadRenderer($this->config);
        
        // Remove the enqueue calls from here - they're now in enqueueDownloadsAssets
        
        // Output the heading
        echo '<h2>' . esc_html__('Available downloads', 'woocommerce') . '</h2>';
        
        // Render the downloads
        $renderer->renderDownloadsTemplate();
    }
    
    /**
     * Shortcut method to check if user owns a product
     * Alias for woocommerceUserProduct for backward compatibility
     */
    public function isUserProduct(int $productId): string|false {
        return $this->woocommerceUserProduct($productId);
    }
    
    /**
     * Render playlist shortcode
     * Public method for hook registration
     */
    public function renderPlaylist(array $atts = []): string {
        return $this->replacePlaylistShortcode($atts);
    }
    
    /**
     * Add product tabs filter
     * Placeholder for future implementation
     */
    public function addProductTabs(array $tabs): array {
        // Future implementation
        return $tabs;
    }
    
    /**
     * Before product summary action
     * Placeholder for future implementation
     */
    public function beforeProductSummary(): void {
        // Future implementation
    }
    
    /**
     * Cart item name filter
     * Placeholder for future implementation
     */
    public function cartItemName(string $name, array $cartItem, string $cartItemKey): string {
        // Future implementation
        return $name;
    }
}
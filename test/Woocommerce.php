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
    
    public function __construct(Plugin $mainPlugin) {
        $this->mainPlugin = $mainPlugin;
    }
    
    /**
     * Include the shortcode in the product title only if the player is enabled and playlist_watermark is not active
     */
    public function woocommerceProductTitle(string $title, $product): string {
        if (!$product) {
            return $title;
        }
        
        // Use getState instead of legacy method
        if (
            $this->mainPlugin->getConfig()->getState('_bfp_enable_player', false, $product->get_id()) &&
            $this->mainPlugin->getConfig()->getState('_bfp_force_main_player_in_title') &&
            !$this->mainPlugin->getPlayer()->getInsertedPlayer()
        ) {
            $this->mainPlugin->getPlayer()->setInsertedPlayer(true);
            // Use getState for show_in check
            $showIn = $this->mainPlugin->getConfig()->getState('_bfp_show_in', 'all', $product->get_id());
            if (!is_admin() && $showIn !== 'single') {
                $title = $this->mainPlugin->getPlayer()->includeMainPlayer($product, false) . $title;
            }
        }
        return $title;
    }

    /**
     * Check if user has purchased a specific product
     */
    public function woocommerceUserProduct(int $productId): string|false {
        $this->mainPlugin->setPurchasedProductFlag(false);
        
        // Use getState instead of legacy method
        $purchasedEnabled = $this->mainPlugin->getConfig()->getState('_bfp_purchased', false);
        
        if (
            !is_user_logged_in() ||
            (
                !$purchasedEnabled &&
                empty($this->mainPlugin->getForcePurchasedFlag())
            )
        ) {
            return false;
        }

        $currentUser = wp_get_current_user();
        if (
            wc_customer_bought_product($currentUser->user_email, $currentUser->ID, $productId) ||
            (
                class_exists('WC_Subscriptions_Manager') &&
                method_exists('WC_Subscriptions_Manager', 'wcs_user_has_subscription') &&
                WC_Subscriptions_Manager::wcs_user_has_subscription($currentUser->ID, $productId, 'active')
            ) ||
            (
                function_exists('wcs_user_has_subscription') &&
                wcs_user_has_subscription($currentUser->ID, $productId, 'active')
            ) ||
            apply_filters('bfp_purchased_product', false, $productId)
        ) {
            $this->mainPlugin->setPurchasedProductFlag(true);
            return md5($currentUser->user_email);
        }

        return false;
    }
    
    /**
     * Get user download links for a product
     */
    public function woocommerceUserDownload(int $productId): string {
        $downloadLinks = [];
        if (is_user_logged_in()) {
            if (empty($this->mainPlugin->getCurrentUserDownloads()) && function_exists('wc_get_customer_available_downloads')) {
                $currentUser = wp_get_current_user();
                $this->mainPlugin->setCurrentUserDownloads(wc_get_customer_available_downloads($currentUser->ID));
            }

            foreach ($this->mainPlugin->getCurrentUserDownloads() as $download) {
                if ($download['product_id'] == $productId) {
                    $downloadLinks[$download['download_id']] = $download['download_url'];
                }
            }
        }

        $downloadLinks = array_unique($downloadLinks);
        if (count($downloadLinks)) {
            $downloadLinks = array_values($downloadLinks);
            return '<a href="javascript:void(0);" data-download-links="' . esc_attr(json_encode($downloadLinks)) . '" class="bfp-download-link">' . esc_html__('download', 'bandfront-player') . '</a>';
        }
        return '';
    }
    
    /**
     * Replace the shortcode to display a playlist with all songs
     */
    public function replacePlaylistShortcode(array $atts): string {
        if (!class_exists('woocommerce') || is_admin()) {
            return '';
        }

        $getTimes = function(int $productId, array $productsList): int {
            if (!empty($productsList)) {
                foreach ($productsList as $product) {
                    if ($product->product_id == $productId) {
                        return $product->times;
                    }
                }
            }
            return 0;
        };

        global $post;

        $output = '';
        if (!$this->mainPlugin->getPlayer()->getInsertPlayer()) {
            return $output;
        }

        if (!is_array($atts)) {
            $atts = [];
        }
        
        $postTypes = $this->mainPlugin->getPostTypes();
        if (
            empty($atts['products_ids']) &&
            empty($atts['purchased_products']) &&
            empty($atts['product_categories']) &&
            empty($atts['product_tags']) &&
            !empty($post) &&
            in_array($post->post_type, $postTypes)
        ) {
            try {
                ob_start();
                $this->mainPlugin->getPlayer()->includeAllPlayers($post->ID);
                $output = ob_get_contents();
                ob_end_clean();

                $class = esc_attr(isset($atts['class']) ? $atts['class'] : '');

                return strpos($output, 'bfp-player-list') !== false ?
                       str_replace('bfp-player-container', $class . ' bfp-player-container', $output) : $output;
            } catch (\Exception $err) {
                $atts['products_ids'] = $post->ID;
            }
        }

        $atts = shortcode_atts(
            [
                'title'                     => '',
                'products_ids'              => '*',
                'purchased_products'        => 0,
                'highlight_current_product' => 0,
                'continue_playing'          => 0,
                // Use getState for default value
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
            ],
            $atts
        );

        $playlistTitle            = trim($atts['title']);
        $productsIds              = $atts['products_ids'];
        $productCategories        = $atts['product_categories'];
        $productTags              = $atts['product_tags'];
        $purchasedProducts        = $atts['purchased_products'];
        $highlightCurrentProduct  = $atts['highlight_current_product'];
        $continuePlaying          = $atts['continue_playing'];
        $playerStyle              = $atts['player_style'];
        $controls                 = $atts['controls'];
        $layout                   = $atts['layout'];
        $cover                    = $atts['cover'];
        $volume                   = $atts['volume'];
        $purchasedOnly            = $atts['purchased_only'];
        $hidePurchaseButtons      = $atts['hide_purchase_buttons'];
        $class                    = $atts['class'];
        $loop                     = $atts['loop'];
        $purchasedTimes           = $atts['purchased_times'];
        $downloadLinksFlag        = $atts['download_links'];

        // Typecasting variables
        $cover                    = is_numeric($cover) ? intval($cover) : 0;
        $volume                   = is_numeric($volume) ? floatval($volume) : 0;
        $purchasedProducts        = is_numeric($purchasedProducts) ? intval($purchasedProducts) : 0;
        $highlightCurrentProduct  = is_numeric($highlightCurrentProduct) ? intval($highlightCurrentProduct) : 0;
        $continuePlaying          = is_numeric($continuePlaying) ? intval($continuePlaying) : 0;
        $purchasedOnly            = is_numeric($purchasedOnly) ? intval($purchasedOnly) : 0;
        $hidePurchaseButtons      = is_numeric($hidePurchaseButtons) ? intval($hidePurchaseButtons) : 0;
        $loop                     = is_numeric($loop) ? intval($loop) : 0;
        $purchasedTimes           = is_numeric($purchasedTimes) ? intval($purchasedTimes) : 0;

        // Load the purchased products only
        $this->mainPlugin->setForcePurchasedFlag($purchasedOnly);

        // get the products ids
        $productsIds = preg_replace('/[^\d\,\*]/', '', $productsIds);
        $productsIds = preg_replace('/\,+/', ',', $productsIds);
        $productsIds = trim($productsIds, ',');

        // get the product categories
        $productCategories = preg_replace('/\s*\,\s*/', ',', $productCategories);
        $productCategories = preg_replace('/\,+/', ',', $productCategories);
        $productCategories = trim($productCategories, ',');

        // get the product tags
        $productTags = preg_replace('/\s*\,\s*/', ',', $productTags);
        $productTags = preg_replace('/\,+/', ',', $productTags);
        $productTags = trim($productTags, ',');

        if (
            strlen($productsIds) == 0 &&
            strlen($productCategories) == 0 &&
            strlen($productTags) == 0
        ) {
            return $output;
        }

        return $this->buildPlaylistOutput($productsIds, $productCategories, $productTags, $purchasedProducts, $atts, $output);
    }
    
    /**
     * Build the playlist output HTML
     */
    private function buildPlaylistOutput(string $productsIds, string $productCategories, string $productTags, int $purchasedProducts, array $atts, string $output): string {
        global $wpdb, $post;

        $currentPostId = !empty($post) ? (is_int($post) ? $post : $post->ID) : -1;

        $query = 'SELECT posts.ID, posts.post_title FROM ' . $wpdb->posts . ' AS posts, ' . $wpdb->postmeta . ' as postmeta WHERE posts.post_status="publish" AND posts.post_type IN (' . $this->mainPlugin->getPostTypes(true) . ') AND posts.ID = postmeta.post_id AND postmeta.meta_key="_bfp_enable_player" AND (postmeta.meta_value="yes" OR postmeta.meta_value="1")';

        if (!empty($purchasedProducts)) {
            $hidePurchaseButtons = 1;
            $currentUserId = get_current_user_id();
            if (0 == $currentUserId) {
                return $output;
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
                return $output;
            }

            $productsIds = [];
            foreach ($customerOrders as $customerOrder) {
                $order = wc_get_order($customerOrder->ID);
                $items = $order->get_items();
                foreach ($items as $item) {
                    $productsIds[] = $item->get_product_id();
                }
            }
            $productsIds = array_unique($productsIds);
            $productsIdsStr = implode(',', $productsIds);

            $query .= ' AND posts.ID IN (' . $productsIdsStr . ')';
            $query .= ' ORDER BY FIELD(posts.ID,' . $productsIdsStr . ')';
        } else {
            if (strpos('*', $productsIds) === false) {
                $query .= ' AND posts.ID IN (' . $productsIds . ')';
                $query .= ' ORDER BY FIELD(posts.ID,' . $productsIds . ')';
            } else {
                $taxQuery = [];

                if ('' != $productCategories) {
                    $categories = explode(',', $productCategories);
                    $taxQuery[] = [
                        'taxonomy' => 'product_cat',
                        'field' => 'slug',
                        'terms' => $categories,
                        'include_children' => true,
                        'operator' => 'IN'
                    ];
                }

                if ('' != $productTags) {
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
        }

        $products = $wpdb->get_results($query);

        if (!empty($products)) {
            return $this->renderPlaylistProducts($products, $atts, $currentPostId, $output);
        }

        $this->mainPlugin->setForcePurchasedFlag(0);
        return $output;
    }
    
    /**
     * Render the playlist products
     */
    private function renderPlaylistProducts(array $products, array $atts, int $currentPostId, string $output): string {
        global $wpdb;
        
        $productPurchasedTimes = [];
        if ($atts['purchased_times']) {
            $productsIdsStr = (is_array($atts['products_ids'])) ? implode(',', $atts['products_ids']) : $atts['products_ids'];
            $productPurchasedTimes = $wpdb->get_results('SELECT order_itemmeta.meta_value product_id, COUNT(order_itemmeta.meta_value) as times FROM ' . $wpdb->prefix . 'posts as orders INNER JOIN ' . $wpdb->prefix . 'woocommerce_order_items as order_items ON (orders.ID=order_items.order_id) INNER JOIN ' . $wpdb->prefix . 'woocommerce_order_itemmeta as order_itemmeta ON (order_items.order_item_id=order_itemmeta.order_item_id) WHERE orders.post_type="shop_order" AND orders.post_status="wc-completed" AND order_itemmeta.meta_key="_product_id" ' . (strlen($productsIdsStr) && false === strpos('*', $productsIdsStr) ? ' AND order_itemmeta.meta_value IN (' . $productsIdsStr . ')' : '') . ' GROUP BY order_itemmeta.meta_value');
        }

        $this->mainPlugin->getPlayer()->enqueueResources();
        wp_enqueue_style('bfp-playlist-widget-style', plugin_dir_url(dirname(__FILE__)) . 'widgets/playlist_widget/css/style.css', [], BFP_VERSION);
        wp_enqueue_script('bfp-playlist-widget-script', plugin_dir_url(dirname(__FILE__)) . 'widgets/playlist_widget/js/widget.js', [], BFP_VERSION);
        wp_localize_script(
            'bfp-playlist-widget-script',
            'bfp_widget_settings',
            ['continue_playing' => $atts['continue_playing']]
        );
        
        $counter = 0;
        $output .= '<div data-loop="' . ($atts['loop'] ? 1 : 0) . '">';
        
        foreach ($products as $product) {
            if ($this->mainPlugin->getForcePurchasedFlag() && !$this->woocommerceUserProduct($product->ID)) {
                continue;
            }

            $productObj = wc_get_product($product->ID);
            $counter++;
            $preload = $this->mainPlugin->getConfig()->getState('_bfp_preload', '', $product->ID);
            $rowClass = 'bfp-even-product';
            if (1 == $counter % 2) {
                $rowClass = 'bfp-odd-product';
            }

            $audioFiles = $this->mainPlugin->getPlayer()->getProductFiles($product->ID);
            if (!is_array($audioFiles)) {
                $audioFiles = [];
            }

            $downloadLinks = '';
            if ($atts['download_links']) {
                $downloadLinks = $this->woocommerceUserDownload($product->ID);
            }

            // Get purchased times for this product
            $purchasedTimes = 0;
            if ($atts['purchased_times']) {
                foreach ($productPurchasedTimes as $pt) {
                    if ($pt->product_id == $product->ID) {
                        $purchasedTimes = $pt->times;
                        break;
                    }
                }
            }
            $atts['purchased_times'] = $purchasedTimes;

            $output .= $this->renderSingleProduct($product, $productObj, $atts, $audioFiles, $downloadLinks, $rowClass, $currentPostId, $preload, $counter);
        }
        
        $output .= '</div>';
        
        // Use getState for message retrieval
        $demosConfig = $this->mainPlugin->getConfig()->getState('_bfp_demos', []);
        $message = $demosConfig['message'] ?? '';
        if (!empty($message) && empty($atts['hide_message'])) {
            $output .= '<div class="bfp-message">' . wp_kses_post(__($message, 'bandfront-player')) . '</div>';
        }
        
        $this->mainPlugin->setForcePurchasedFlag(0);

        if (!empty($atts['title']) && !empty($output)) {
            $output = '<div class="bfp-widget-playlist-title">' . esc_html($atts['title']) . '</div>' . $output;
        }

        return $output;
    }
    
    /**
     * Render a single product in the playlist
     */
    private function renderSingleProduct($product, $productObj, array $atts, array $audioFiles, string $downloadLinks, string $rowClass, int $currentPostId, string $preload, int $counter): string {
        $output = '';
        
        // Define featured_image if cover is enabled
        $featuredImage = '';
        if ($atts['cover']) {
            $featuredImage = get_the_post_thumbnail($product->ID, [60, 60]);
        }
        
        if ('new' == $atts['layout']) {
            $price = $productObj->get_price();
            $output .= '<div class="bfp-new-layout bfp-widget-product controls-' . esc_attr($atts['controls']) . ' ' . 
                      esc_attr($atts['class']) . ' ' . esc_attr($rowClass) . ' ' . 
                      esc_attr(($product->ID == $currentPostId && $atts['highlight_current_product']) ? 'bfp-current-product' : '') . '">';
            
            // Header section
            $output .= '<div class="bfp-widget-product-header">';
            $output .= '<div class="bfp-widget-product-title">';
            $output .= '<a href="' . esc_url(get_permalink($product->ID)) . '">' . esc_html($productObj->get_name()) . '</a>';
            
            if ($atts['purchased_times']) {
                $output .= '<span class="bfp-purchased-times">' .
                          sprintf(
                              __($this->mainPlugin->getConfig()->getState('_bfp_purchased_times_text', '- purchased %d time(s)'), 'bandfront-player'),
                              $atts['purchased_times']
                          ) . '</span>';
            }
            
            $output .= $downloadLinks;
            $output .= '</div>';
            
            if (0 != @floatval($price) && 0 == $atts['hide_purchase_buttons']) {
                $productIdForAddToCart = $product->ID;
                
                if ($productObj->is_type('variable')) {
                    $variations = $productObj->get_available_variations();
                    $variationsId = wp_list_pluck($variations, 'variation_id');
                    if (!empty($variationsId)) {
                        $productIdForAddToCart = $variationsId[0];
                    }
                } elseif ($productObj->is_type('grouped')) {
                    $children = $productObj->get_children();
                    if (!empty($children)) {
                        $productIdForAddToCart = $children[0];
                    }
                }
                
                $output .= '<div class="bfp-widget-product-purchase">' . 
                           wc_price($productObj->get_price(), '') . 
                           ' <a href="?add-to-cart=' . $productIdForAddToCart . '"></a>' .
                           '</div>';
            }
            $output .= '</div>'; // Close header
            
            $output .= '<div class="bfp-widget-product-files">';
            
            if (!empty($featuredImage)) {
                $output .= $featuredImage . '<div class="bfp-widget-product-files-list">';
            }

            // Render audio files
            foreach ($audioFiles as $index => $file) {
                $audioUrl = $this->mainPlugin->getAudioCore()->generateAudioUrl($product->ID, $index, $file);
                $duration = $this->mainPlugin->getAudioCore()->getDurationByUrl($file['file']);
                
                $audioTag = apply_filters(
                    'bfp_widget_audio_tag',
                    $this->mainPlugin->getPlayer()->getPlayer(
                        $audioUrl,
                        [
                            'product_id'      => $product->ID,
                            'player_controls' => $atts['controls'],
                            'player_style'    => $atts['player_style'],
                            'media_type'      => $file['media_type'],
                            'id'              => $index,
                            'duration'        => $duration,
                            'preload'         => $preload,
                            'volume'          => $atts['volume'],
                        ]
                    ),
                    $product->ID,
                    $index,
                    $audioUrl
                );
                
                $fileTitle = esc_html(apply_filters('bfp_widget_file_name', $file['name'], $product->ID, $index));
                
                $output .= '<div class="bfp-widget-product-file">';
                $output .= $audioTag;
                $output .= '<span class="bfp-file-name">' . $fileTitle . '</span>';
                
                if (!isset($atts['duration']) || $atts['duration'] == 1) {
                    $output .= '<span class="bfp-file-duration">' . esc_html($duration) . '</span>';
                }
                
                $output .= '<div style="clear:both;"></div></div>';
            }

            if (!empty($featuredImage)) {
                $output .= '</div>';
            }

            $output .= '</div></div>'; // Close files and product
        } else {
            // Classic layout
            $output .= '<ul class="bfp-widget-playlist bfp-classic-layout controls-' . esc_attr($atts['controls']) . ' ' . esc_attr($atts['class']) . ' ' . esc_attr($rowClass) . ' ' . esc_attr(($product->ID == $currentPostId && $atts['highlight_current_product']) ? 'bfp-current-product' : '') . '">';

            if (!empty($featuredImage)) {
                $output .= '<li style="display:table-row;">' . $featuredImage . '<div class="bfp-widget-product-files-list"><ul>';
            }

            foreach ($audioFiles as $index => $file) {
                $audioUrl = $this->mainPlugin->getAudioCore()->generateAudioUrl($product->ID, $index, $file);
                $duration = $this->mainPlugin->getAudioCore()->getDurationByUrl($file['file']);
                
                $audioTag = apply_filters(
                    'bfp_widget_audio_tag',
                    $this->mainPlugin->getPlayer()->getPlayer(
                        $audioUrl,
                        [
                            'product_id'      => $product->ID,
                            'player_controls' => $atts['controls'],
                            'player_style'    => $atts['player_style'],
                            'media_type'      => $file['media_type'],
                            'id'              => $index,
                            'duration'        => $duration,
                            'preload'         => $preload,
                            'volume'          => $atts['volume'],
                        ]
                    ),
                    $product->ID,
                    $index,
                    $audioUrl
                );
                
                $fileTitle = esc_html(apply_filters('bfp_widget_file_name', $file['name'], $product->ID, $index));
                
                $output .= '<li style="display:table-row;">';
                $output .= '<div class="bfp-player-col bfp-widget-product-file" style="display:table-cell;">' . $audioTag . '</div>';
                $output .= '<div class="bfp-title-col" style="display:table-cell;"><span class="bfp-player-song-title">' . $fileTitle . '</span>';
                
                if (!empty($atts['duration']) && $atts['duration'] == 1) {
                    $output .= ' <span class="bfp-player-song-duration">' . esc_html($duration) . '</span>';
                }
                
                $output .= '</div></li>';
            }

            if (!empty($featuredImage)) {
                $output .= '</ul></div></li>';
            }

            $output .= '</ul>';
        }
        
        return $output;
    }
}
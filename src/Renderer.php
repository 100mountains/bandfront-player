<?php
namespace bfp;

/**
 * Rendering functionality for Bandfront Player
 * 
 * Consolidates all player rendering logic including:
 * - Single player rendering
 * - Player table/list rendering
 * - Playlist widget rendering
 * - Product playlist rendering
 * 
 * @package BandfrontPlayer
 * @since 0.1
 */

if (!defined('ABSPATH')) {
    exit;
}

class Renderer {
    
    private Plugin $mainPlugin;
    
    public function __construct(Plugin $mainPlugin) {
        $this->mainPlugin = $mainPlugin;
        // Remove console log from constructor - causes activation errors
    }
    
    /**
     * Helper method to add console.log statements
     * 
     * @param string $message Message to log
     * @param mixed $data Optional data to log
     * @return string Script tag with console.log
     */
    private function addConsoleLog(string $message, $data = null): string {
        // Prevent output during activation or AJAX requests
        if (defined('WP_INSTALLING') || wp_doing_ajax() || (defined('REST_REQUEST') && REST_REQUEST)) {
            return '';
        }
        
        // Only output in appropriate contexts
        if (!did_action('wp_body_open') && !did_action('admin_head')) {
            return '';
        }
        
        $script = '<script>';
        if ($data === null) {
            $script .= 'console.log("[BFP] ' . esc_js($message) . '");';
        } else {
            if (is_array($data) || is_object($data)) {
                $jsonData = json_encode($data);
                $script .= 'console.log("[BFP] ' . esc_js($message) . '", ' . $jsonData . ');';
            } else {
                $script .= 'console.log("[BFP] ' . esc_js($message) . '", "' . esc_js($data) . '");';
            }
        }
        $script .= '</script>';
        return $script;
    }
    
    /**
     * Render player table layout for multiple files
     * Moved from Player.php
     * 
     * @param array $files Audio files to render
     * @param int $productId Product ID
     * @param array $settings Player settings
     * @return string Rendered HTML
     */
    public function renderPlayerTable(array $files, int $productId, array $settings): string {
        if (empty($files) || count($files) < 2) {
            // Add console logging for empty files condition
            $output = $this->addConsoleLog('Player table not rendered - insufficient files', ['count' => count($files), 'productId' => $productId]);
            return $output;
        }
        
        // Add console logging for table rendering start
        $output = $this->addConsoleLog('Rendering player table', ['files_count' => count($files), 'productId' => $productId, 'single_player' => $settings['single_player'] ?? 0]);
        
        $mergeGroupedClass = ($settings['_bfp_merge_in_grouped']) ? 'merge_in_grouped_products' : '';
        $singlePlayer = $settings['single_player'] ?? 0;
        
        $output .= '<table class="bfp-player-list ' . $mergeGroupedClass . ($singlePlayer ? ' bfp-single-player ' : '') . '" ' . 
                   ($settings['_bfp_loop'] ? 'data-loop="1"' : '') . '>';
        
        $counter = count($files);
        $firstPlayerClass = 'bfp-first-player';
        
        foreach ($files as $index => $file) {
            // Add console logging for each file being processed
            $output .= $this->addConsoleLog('Processing file in table', ['index' => $index, 'name' => $file['name'] ?? 'unnamed', 'counter' => $counter]);
            
            $evenOdd = (1 == $counter % 2) ? 'bfp-odd-row' : 'bfp-even-row';
            $counter--;
            
            $audioUrl = $this->mainPlugin->getAudioCore()->generateAudioUrl($productId, $index, $file);
            $duration = $this->mainPlugin->getAudioCore()->getDurationByUrl($file['file']);
            
            $audioTag = apply_filters(
                'bfp_audio_tag',
                $this->mainPlugin->getPlayer()->getPlayer(
                    $audioUrl,
                    [
                        'product_id'      => $productId,
                        'player_style'    => $settings['_bfp_player_layout'],
                        'player_controls' => ('all' != $settings['player_controls']) ? 'track' : '',
                        'media_type'      => $file['media_type'],
                        'duration'        => $duration,
                        'preload'         => $settings['_bfp_preload'],
                        'volume'          => $settings['_bfp_player_volume'],
                        'id'              => $index,
                    ]
                ),
                $productId,
                $index,
                $audioUrl
            );
            
            // FIXED: Title processing logic
            $title = '';
            
            // Check if player titles are enabled
            $playerTitleEnabled = $settings['_bfp_player_title'] ?? 1;
            
            $this->addConsoleLog('Processing title', [
                'index' => $index,
                'file_name' => $file['name'] ?? 'no name',
                'player_title_enabled' => $playerTitleEnabled,
                'productId' => $productId
            ]);
            
            if ($playerTitleEnabled) {
                // Get the raw file name
                $rawTitle = $file['name'] ?? '';
                
                // Apply filters for title processing
                $processedTitle = apply_filters('bfp_file_name', $rawTitle, $productId, $index);
                
                // Final title processing
                $title = esc_html($processedTitle);
                
                $this->addConsoleLog('Title processed', [
                    'index' => $index,
                    'raw_title' => $rawTitle,
                    'processed_title' => $processedTitle,
                    'final_title' => $title
                ]);
            } else {
                $this->addConsoleLog('Title disabled by setting', ['index' => $index]);
            }
            
            $output .= $this->renderPlayerRow($audioTag, $title, $duration, $evenOdd, 
                                            $file['product'], $firstPlayerClass, 
                                            $counter, $settings, $singlePlayer);
            
            $firstPlayerClass = '';
        }
        
        $output .= '</table>';
        
        // Add console logging for table rendering completion
        $output .= $this->addConsoleLog('Player table rendering completed', ['productId' => $productId]);
        
        return $output;
    }
    
    /**
     * Render a single player row
     * Moved from Player.php
     * 
     * @param string $audioTag Audio element HTML
     * @param string $title Track title
     * @param string $duration Track duration
     * @param string $evenOdd Row class
     * @param int $productId Product ID
     * @param string $firstPlayerClass First player class
     * @param int $counter Row counter
     * @param array $settings Player settings
     * @param int $singlePlayer Single player mode
     * @return string Rendered row HTML
     */
    public function renderPlayerRow(string $audioTag, string $title, string $duration, string $evenOdd, 
                                   int $productId, string $firstPlayerClass, int $counter, 
                                   array $settings, int $singlePlayer): string {
        // Add console logging for row rendering
        $output = $this->addConsoleLog('Rendering player row', [
            'productId' => $productId, 
            'title' => $title, 
            'counter' => $counter, 
            'controls' => $settings['player_controls'] ?? 'default'
        ]);
        
        $output .= '<tr class="' . esc_attr($evenOdd) . ' product-' . esc_attr($productId) . '">';
        
        if ('all' != $settings['player_controls']) {
            $output .= '<td class="bfp-column-player-' . esc_attr($settings['_bfp_player_layout']) . '">';
            $output .= '<div class="bfp-player-container ' . $firstPlayerClass . '" data-player-id="' . esc_attr($counter) . '">';
            $output .= $audioTag;
            $output .= '</div></td>';
            $output .= '<td class="bfp-player-title bfp-column-player-title" data-player-id="' . esc_attr($counter) . '">';
            $output .= wp_kses_post($title);
            $output .= '</td>';
            $output .= '<td class="bfp-file-duration" style="text-align:right;font-size:16px;">';
            $output .= esc_html($duration);
            $output .= '</td>';
        } else {
            $output .= '<td>';
            $output .= '<div class="bfp-player-container ' . $firstPlayerClass . '" data-player-id="' . esc_attr($counter) . '">';
            $output .= $audioTag;
            $output .= '</div>';
            $output .= '<div class="bfp-player-title bfp-column-player-title" data-player-id="' . esc_attr($counter) . '">';
            $output .= wp_kses_post($title);
            if ($singlePlayer) {
                $output .= '<span class="bfp-file-duration">' . esc_html($duration) . '</span>';
            }
            $output .= '</div>';
            $output .= '</td>';
        }
        
        $output .= '</tr>';
        
        return $output;
    }
    
    /**
     * Render the playlist products
     * Moved from WooCommerce.php
     * 
     * @param array $products Products to render
     * @param array $atts Shortcode attributes
     * @param int $currentPostId Current post ID
     * @param string $output Existing output
     * @return string Rendered HTML
     */
    public function renderPlaylistProducts(array $products, array $atts, int $currentPostId, string $output): string {
        // Add console logging for playlist rendering
        $output .= $this->addConsoleLog('Rendering playlist products', [
            'products_count' => count($products), 
            'currentPostId' => $currentPostId,
            'atts' => json_encode($atts)
        ]);
        
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
        
        $woocommerce = $this->mainPlugin->getWooCommerce();
        
        foreach ($products as $product) {
            // Add console logging for each product
            $output .= $this->addConsoleLog('Processing product in playlist', [
                'productId' => $product->ID,
                'title' => $product->post_title,
                'counter' => $counter
            ]);
            
            if ($this->mainPlugin->getForcePurchasedFlag() && $woocommerce && !$woocommerce->woocommerceUserProduct($product->ID)) {
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
            if ($atts['download_links'] && $woocommerce) {
                $downloadLinks = $woocommerce->woocommerceUserDownload($product->ID);
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
        $message = $this->mainPlugin->getConfig()->getState('_bfp_message', '');
        if (!empty($message) && empty($atts['hide_message'])) {
            $output .= '<div class="bfp-message">' . wp_kses_post(__($message, 'bandfront-player')) . '</div>';
        }
        
        $this->mainPlugin->setForcePurchasedFlag(0);

        if (!empty($atts['title']) && !empty($output)) {
            $output = '<div class="bfp-widget-playlist-title">' . esc_html($atts['title']) . '</div>' . $output;
        }

        // Add console logging for playlist rendering completion
        $output .= $this->addConsoleLog('Playlist rendering completed', ['products_processed' => $counter]);
        
        return $output;
    }
    
    /**
     * Render a single product in the playlist
     * Moved from WooCommerce.php
     * 
     * @param object $product Product post object
     * @param \WC_Product $productObj WooCommerce product object
     * @param array $atts Shortcode attributes
     * @param array $audioFiles Audio files
     * @param string $downloadLinks Download links HTML
     * @param string $rowClass Row CSS class
     * @param int $currentPostId Current post ID
     * @param string $preload Preload setting
     * @param int $counter Product counter
     * @return string Rendered HTML
     */
    public function renderSingleProduct($product, $productObj, array $atts, array $audioFiles, string $downloadLinks, string $rowClass, int $currentPostId, string $preload, int $counter): string {
        // Add console logging for single product rendering
        $output = $this->addConsoleLog('Rendering single product', [
            'productId' => $product->ID,
            'title' => $productObj->get_name(),
            'files_count' => count($audioFiles),
            'layout' => $atts['layout'] ?? 'classic'
        ]);
        
        // Define featured_image if cover is enabled
        $featuredImage = '';
        if ($atts['cover']) {
            $featuredImage = get_the_post_thumbnail($product->ID, [60, 60]);
        }
        
        if ('new' == $atts['layout']) {
            $output .= $this->renderNewLayoutProduct($product, $productObj, $atts, $audioFiles, $downloadLinks, $rowClass, $currentPostId, $preload, $featuredImage);
        } else {
            $output .= $this->renderClassicLayoutProduct($product, $productObj, $atts, $audioFiles, $downloadLinks, $rowClass, $currentPostId, $preload, $featuredImage);
        }
        
        return $output;
    }
    
    /**
     * Render product in new layout style
     * 
     * @param object $product Product post object
     * @param \WC_Product $productObj WooCommerce product object
     * @param array $atts Shortcode attributes
     * @param array $audioFiles Audio files
     * @param string $downloadLinks Download links HTML
     * @param string $rowClass Row CSS class
     * @param int $currentPostId Current post ID
     * @param string $preload Preload setting
     * @param string $featuredImage Featured image HTML
     * @return string Rendered HTML
     */
    private function renderNewLayoutProduct($product, $productObj, array $atts, array $audioFiles, string $downloadLinks, string $rowClass, int $currentPostId, string $preload, string $featuredImage): string {
        // Add console logging for new layout rendering
        $output = $this->addConsoleLog('Rendering product with new layout', ['productId' => $product->ID]);
        
        $price = $productObj->get_price();
        $output = '<div class="bfp-new-layout bfp-widget-product controls-' . esc_attr($atts['controls']) . ' ' . 
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
        
        return $output;
    }
    
    /**
     * Render product in classic layout style
     * 
     * @param object $product Product post object
     * @param \WC_Product $productObj WooCommerce product object
     * @param array $atts Shortcode attributes
     * @param array $audioFiles Audio files
     * @param string $downloadLinks Download links HTML
     * @param string $rowClass Row CSS class
     * @param int $currentPostId Current post ID
     * @param string $preload Preload setting
     * @param string $featuredImage Featured image HTML
     * @return string Rendered HTML
     */
    private function renderClassicLayoutProduct($product, $productObj, array $atts, array $audioFiles, string $downloadLinks, string $rowClass, int $currentPostId, string $preload, string $featuredImage): string {
        // Add console logging for classic layout rendering
        $output = $this->addConsoleLog('Rendering product with classic layout', ['productId' => $product->ID]);
        
        // Classic layout
        $output = '<ul class="bfp-widget-playlist bfp-classic-layout controls-' . esc_attr($atts['controls']) . ' ' . esc_attr($atts['class']) . ' ' . esc_attr($rowClass) . ' ' . esc_attr(($product->ID == $currentPostId && $atts['highlight_current_product']) ? 'bfp-current-product' : '') . '">';

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
        
        return $output;
    }
    
    /**
     * Check if cover overlay should be rendered on current page
     */
    public function shouldRenderCover(): bool {
        // Only render on shop/archive pages
        $shouldRender = is_shop() || is_product_category() || is_product_tag();
        $onCover = $this->mainPlugin->getConfig()->getState('_bfp_on_cover');
        $result = $shouldRender && (bool) $onCover;
        
        // Add console logging for cover rendering decision
        echo $this->addConsoleLog('Checking if cover should render', [
            'isShopOrArchive' => $shouldRender, 
            'onCoverSetting' => (bool) $onCover,
            'result' => $result
        ]);
        
        return $result;
    }
    
    /**
     * Enqueue assets for cover overlay functionality
     */
    public function enqueueCoverAssets(): void {
        if (!$this->shouldRenderCover()) {
            return;
        }
        
        wp_add_inline_style('bfp-style', $this->getCoverInlineStyles());
    }
    
    /**
     * Get inline CSS for cover overlay
     */
    private function getCoverInlineStyles(): string {
        return '
            .woocommerce ul.products li.product .bfp-play-on-cover {
                position: absolute;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                z-index: 10;
                background: rgba(255,255,255,0.9);
                border-radius: 50%;
                width: 60px;
                height: 60px;
                display: flex;
                align-items: center;
                justify-content: center;
                cursor: pointer;
                transition: all 0.3s ease;
                box-shadow: 0 2px 10px rgba(0,0,0,0.3);
            }
            .woocommerce ul.products li.product .bfp-play-on-cover:hover {
                transform: translate(-50%, -50%) scale(1.1);
                box-shadow: 0 4px 20px rgba(0,0,0,0.4);
            }
            .woocommerce ul.products li.product .bfp-play-on-cover svg {
                width: 24px;
                height: 24px;
                margin-left: 3px;
            }
            .woocommerce ul.products li.product a img {
                position: relative;
            }
            .woocommerce ul.products li.product {
                position: relative;
            }
        ';
    }
    
    /**
     * Render play button overlay for a product
     */
    public function renderCoverOverlay(?\WC_Product $product = null): void {
        if (!$this->shouldRenderCover()) {
            echo $this->addConsoleLog('Cover overlay not rendered - shouldRenderCover returned false');
            return;
        }
        
        // Get product if not provided
        if (!$product) {
            global $product;
        }
        
        if (!$product) {
            echo $this->addConsoleLog('Cover overlay not rendered - no product found');
            return;
        }
        
        $productId = $product->get_id();
        
        // Use getState with product context
        $enablePlayer = $this->mainPlugin->getConfig()->getState('_bfp_enable_player', false, $productId);
        if (!$enablePlayer) {
            echo $this->addConsoleLog('Cover overlay not rendered - player not enabled for product', ['productId' => $productId]);
            return;
        }
        
        // Check if product has audio files using the consolidated player class
        $files = $this->mainPlugin->getPlayer()->getProductFiles($productId);
        if (empty($files)) {
            echo $this->addConsoleLog('Cover overlay not rendered - no audio files for product', ['productId' => $productId]);
            return;
        }
        
        echo $this->addConsoleLog('Rendering cover overlay', [
            'productId' => $productId, 
            'title' => $product->get_name(), 
            'files_count' => count($files)
        ]);
        
        // Enqueue player resources
        $this->mainPlugin->getPlayer()->enqueueResources();
        
        // Render the overlay
        $this->renderCoverOverlayHtml($productId, $product);
    }
    
    /**
     * Render the actual overlay HTML
     */
    private function renderCoverOverlayHtml(int $productId, \WC_Product $product): void {
        echo $this->addConsoleLog('Rendering cover overlay HTML', ['productId' => $productId]);
        ?>
        <div class="bfp-play-on-cover" data-product-id="<?php echo esc_attr($productId); ?>">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                <path d="M8 5v14l11-7z"/>
            </svg>
        </div>
        <div class="bfp-hidden-player-container" style="display:none;">
            <?php $this->mainPlugin->getPlayer()->includeMainPlayer($product, true); ?>
        </div>
        <?php
    }
    
    /**
     * Enqueue format downloads CSS
     */
    public function enqueueFormatDownloadsCSS(): void {
        if (!is_product()) {
            return;
        }
        
        wp_enqueue_style(
            'bfp-format-downloads',
            plugin_dir_url(dirname(__FILE__)) . 'css/format-downloads.css',
            [],
            BFP_VERSION
        );
    }
    
    /**
     * Check if player should display
     * 
     * @param int $productId Product ID
     * @return bool Whether to display player
     */
    private function shouldDisplay(int $productId): bool {
        // Use smart context detection instead of _bfp_show_in
        if (!$this->mainPlugin->smartPlayContext($productId)) {
            return false;
        }
        
        // Check page context
        if (is_singular($this->mainPlugin->getPostTypes())) {
            // Single product page - always show if context allows
            return true;
        } elseif (is_shop() || is_product_category() || is_product_tag() || is_product_taxonomy()) {
            // Shop/archive pages - always show if context allows
            return true;
        }
        
        // Allow other contexts (like shortcodes) to show players
        return apply_filters('bfp_should_display_player', true, $productId);
    }
    
    /**
     * AJAX handler for player content
     */
    public function handleAjaxPlayer(): void {
        // ...existing code...
        
        // Smart context check instead of _bfp_show_in
        if (!$this->mainPlugin->smartPlayContext($product->get_id())) {
            wp_send_json_error(__('Player not available for this product', 'bandfront-player'));
            return;
        }
        
        // ...existing code...
    }
}

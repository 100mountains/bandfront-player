<?php
declare(strict_types=1);

namespace Bandfront\UI;

use Bandfront\Core\Config;
use Bandfront\Storage\FileManager;
use Bandfront\Utils\Debug;

/**
 * Rendering functionality for Bandfront Player
 * 
 * Consolidates all player rendering logic including:
 * - Single player rendering
 * - Player table/list rendering
 * - Playlist widget rendering
 * - Product playlist rendering
 * 
 * @package Bandfront\UI
 * @since 2.0.0
 */
class Renderer {
    
    private Config $config;
    private FileManager $fileManager;
    
    /**
     * Constructor - accepts only needed dependencies
     */
    public function __construct(Config $config, FileManager $fileManager) {
        $this->config = $config;
        $this->fileManager = $fileManager;
    }
    
    /**
     * Render player table layout for multiple files
     * 
     * @param array $files Audio files to render (with prepared audio_tag data)
     * @param int $productId Product ID
     * @param array $settings Player settings
     * @return string Rendered HTML
     */
    public function renderPlayerTable(array $files, int $productId, array $settings): string {
        if (empty($files) || count($files) < 2) {
            Debug::log('Renderer: Not rendering table - insufficient files', ['count' => count($files), 'productId' => $productId]);
            return '';
        }
        
        Debug::log('Renderer: Rendering player table', ['files_count' => count($files), 'productId' => $productId]);
        
        $mergeGroupedClass = ($settings['_bfp_merge_in_grouped']) ? 'merge_in_grouped_products' : '';
        $singlePlayer = $settings['single_player'] ?? 0;
        
        $output = '<table class="bfp-player-list ' . $mergeGroupedClass . ($singlePlayer ? ' bfp-single-player ' : '') . '" ' . 
                   ($settings['_bfp_loop'] ? 'data-loop="1"' : '') . '>';
        
        $counter = count($files);
        $firstPlayerClass = 'bfp-first-player';
        
        foreach ($files as $index => $file) {
            Debug::log('Renderer: Processing file in table', ['index' => $index, 'name' => $file['name'] ?? 'unnamed']);
            
            $evenOdd = (1 == $counter % 2) ? 'bfp-odd-row' : 'bfp-even-row';
            $counter--;
            
            // Use prepared data - audio tag should be passed in
            $audioTag = $file['audio_tag'] ?? '';
            $duration = $file['duration'] ?? '';
            
            // Title processing
            $title = '';
            $playerTitleEnabled = $settings['_bfp_player_title'] ?? 1;
            
            if ($playerTitleEnabled) {
                $rawTitle = $file['name'] ?? '';
                $processedTitle = apply_filters('bfp_file_name', $rawTitle, $productId, $index);
                $title = esc_html($processedTitle);
            }
            
            $output .= $this->renderPlayerRow($audioTag, $title, $duration, $evenOdd, 
                                            $file['product'], $firstPlayerClass, 
                                            $counter, $settings, $singlePlayer);
            
            $firstPlayerClass = '';
        }
        
        $output .= '</table>';
        
        Debug::log('Renderer: Table rendering completed', ['productId' => $productId]);
        
        return $output;
    }
    
    /**
     * Render a single player row
     * 
     * @param string $audioTag Audio element HTML (prepared)
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
    private function renderPlayerRow(string $audioTag, string $title, string $duration, string $evenOdd, 
                                   int $productId, string $firstPlayerClass, int $counter, 
                                   array $settings, int $singlePlayer): string {
        Debug::log('Renderer: Rendering row', [
            'productId' => $productId, 
            'title' => $title, 
            'counter' => $counter
        ]);
        
        $output = '<tr class="' . esc_attr($evenOdd) . ' product-' . esc_attr($productId) . '">';
        
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
     * 
     * @param array $products Products to render (with prepared data)
     * @param array $atts Shortcode attributes
     * @param int $currentPostId Current post ID
     * @return string Rendered HTML
     */
    public function renderPlaylistProducts(array $products, array $atts, int $currentPostId): string {
        Debug::log('Renderer: Rendering playlist products', [
            'products_count' => count($products), 
            'currentPostId' => $currentPostId,
            'atts' => $atts
        ]);
        
        $output = '';
        
        // Enqueue playlist assets
        wp_enqueue_style('bfp-playlist-widget-style', plugin_dir_url(dirname(dirname(__FILE__))) . 'widgets/playlist_widget/css/style.css', [], BFP_VERSION);
        wp_enqueue_script('bfp-playlist-widget-script', plugin_dir_url(dirname(dirname(__FILE__))) . 'widgets/playlist_widget/js/widget.js', [], BFP_VERSION);
        wp_localize_script(
            'bfp-playlist-widget-script',
            'bfp_widget_settings',
            ['continue_playing' => $atts['continue_playing']]
        );
        
        $counter = 0;
        $output .= '<div data-loop="' . ($atts['loop'] ? 1 : 0) . '">';
        
        foreach ($products as $product) {
            Debug::log('Renderer: Processing product in playlist', [
                'productId' => $product->ID,
                'title' => $product->post_title,
                'counter' => $counter
            ]);
            
            // Skip if purchase check fails (this check would be done before calling renderer)
            if (!empty($product->skip_render)) {
                continue;
            }

            $productObj = wc_get_product($product->ID);
            $counter++;
            $preload = $this->config->getState('_bfp_preload', '', $product->ID);
            $rowClass = 'bfp-even-product';
            if (1 == $counter % 2) {
                $rowClass = 'bfp-odd-product';
            }

            // Use prepared data - audio files would be passed in with audio_tag already generated
            $audioFiles = $product->audio_files ?? [];
            $downloadLinks = $product->download_links ?? '';
            $purchasedTimes = $product->purchased_times ?? 0;

            $output .= $this->renderSingleProduct($product, $productObj, $atts, $audioFiles, $downloadLinks, $rowClass, $currentPostId, $preload, $counter, $purchasedTimes);
        }
        
        $output .= '</div>';
        
        // Add message if configured
        $message = $this->config->getState('_bfp_message', '');
        if (!empty($message) && empty($atts['hide_message'])) {
            $output .= '<div class="bfp-message">' . wp_kses_post(__($message, 'bandfront-player')) . '</div>';
        }

        if (!empty($atts['title']) && !empty($output)) {
            $output = '<div class="bfp-widget-playlist-title">' . esc_html($atts['title']) . '</div>' . $output;
        }

        Debug::log('Renderer: Playlist rendering completed', ['products_processed' => $counter]);
        
        return $output;
    }
    
    /**
     * Render a single product in the playlist
     * 
     * @param object $product Product post object
     * @param \WC_Product $productObj WooCommerce product object
     * @param array $atts Shortcode attributes
     * @param array $audioFiles Audio files (with prepared audio_tag data)
     * @param string $downloadLinks Download links HTML
     * @param string $rowClass Row CSS class
     * @param int $currentPostId Current post ID
     * @param string $preload Preload setting
     * @param int $counter Product counter
     * @param int $purchasedTimes Purchase count
     * @return string Rendered HTML
     */
    public function renderSingleProduct($product, $productObj, array $atts, array $audioFiles, string $downloadLinks, 
                                      string $rowClass, int $currentPostId, string $preload, int $counter, 
                                      int $purchasedTimes = 0): string {
        Debug::log('Renderer: Rendering single product', [
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
        
        // Update atts with purchased times
        $atts['purchased_times'] = $purchasedTimes;
        
        if ('new' == $atts['layout']) {
            return $this->renderNewLayoutProduct($product, $productObj, $atts, $audioFiles, $downloadLinks, $rowClass, $currentPostId, $preload, $featuredImage);
        } else {
            return $this->renderClassicLayoutProduct($product, $productObj, $atts, $audioFiles, $downloadLinks, $rowClass, $currentPostId, $preload, $featuredImage);
        }
    }
    
    /**
     * Render product in new layout style
     * 
     * @param object $product Product post object
     * @param \WC_Product $productObj WooCommerce product object
     * @param array $atts Shortcode attributes
     * @param array $audioFiles Audio files (with prepared audio_tag data)
     * @param string $downloadLinks Download links HTML
     * @param string $rowClass Row CSS class
     * @param int $currentPostId Current post ID
     * @param string $preload Preload setting
     * @param string $featuredImage Featured image HTML
     * @return string Rendered HTML
     */
    private function renderNewLayoutProduct($product, $productObj, array $atts, array $audioFiles, string $downloadLinks, 
                                          string $rowClass, int $currentPostId, string $preload, string $featuredImage): string {
        Debug::log('Renderer: Rendering product with new layout', ['productId' => $product->ID]);
        
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
                          __($this->config->getState('_bfp_purchased_times_text', '- purchased %d time(s)'), 'bandfront-player'),
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

        // Render audio files using prepared data
        foreach ($audioFiles as $index => $file) {
            // Use prepared audio tag
            $audioTag = $file['audio_tag'] ?? '';
            $duration = $file['duration'] ?? '';
            
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
     * @param array $audioFiles Audio files (with prepared audio_tag data)
     * @param string $downloadLinks Download links HTML
     * @param string $rowClass Row CSS class
     * @param int $currentPostId Current post ID
     * @param string $preload Preload setting
     * @param string $featuredImage Featured image HTML
     * @return string Rendered HTML
     */
    private function renderClassicLayoutProduct($product, $productObj, array $atts, array $audioFiles, string $downloadLinks, 
                                              string $rowClass, int $currentPostId, string $preload, string $featuredImage): string {
        Debug::log('Renderer: Rendering product with classic layout', ['productId' => $product->ID]);
        
        // Classic layout
        $output = '<ul class="bfp-widget-playlist bfp-classic-layout controls-' . esc_attr($atts['controls']) . ' ' . esc_attr($atts['class']) . ' ' . esc_attr($rowClass) . ' ' . esc_attr(($product->ID == $currentPostId && $atts['highlight_current_product']) ? 'bfp-current-product' : '') . '">';

        if (!empty($featuredImage)) {
            $output .= '<li style="display:table-row;">' . $featuredImage . '<div class="bfp-widget-product-files-list"><ul>';
        }

        foreach ($audioFiles as $index => $file) {
            // Use prepared audio tag
            $audioTag = $file['audio_tag'] ?? '';
            $duration = $file['duration'] ?? '';
            
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
        $onCover = $this->config->getState('_bfp_on_cover');
        $result = $shouldRender && (bool) $onCover;
        
        Debug::log('Renderer: Checking if cover should render', [
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
     * Render play button overlay HTML for a product
     * 
     * @param int $productId Product ID
     * @param string $playerHtml Pre-rendered player HTML
     * @return string Overlay HTML
     */
    public function renderCoverOverlay(int $productId, string $playerHtml = ''): string {
        if (!$this->shouldRenderCover()) {
            Debug::log('Renderer: Cover overlay not rendered - shouldRenderCover false');
            return '';
        }
        
        Debug::log('Renderer: Rendering cover overlay', ['productId' => $productId]);
        
        ob_start();
        ?>
        <div class="bfp-play-on-cover" data-product-id="<?php echo esc_attr($productId); ?>">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                <path d="M8 5v14l11-7z"/>
            </svg>
        </div>
        <?php if ($playerHtml): ?>
        <div class="bfp-hidden-player-container" style="display:none;">
            <?php echo $playerHtml; // Pre-escaped by Player ?>
        </div>
        <?php endif; ?>
        <?php
        return ob_get_clean();
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
            plugin_dir_url(dirname(dirname(__FILE__))) . 'css/format-downloads.css',
            [],
            BFP_VERSION
        );
    }
}
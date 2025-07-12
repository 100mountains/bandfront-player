<?php
/**
 * Cover Overlay Renderer for Bandfront Player
 *
 * @package BandfrontPlayer
 * @since 0.1
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * BFP Cover Overlay Renderer Class
 * Handles rendering of play button overlays on product cover images
 */
class BFP_Cover_Renderer {
    
    private $main_plugin;
    
    public function __construct($main_plugin) {
        $this->main_plugin = $main_plugin;
    }
    
    /**
     * Check if overlay should be rendered on current page
     *
     * @return bool
     */
    public function should_render(): bool {
        // Only render on shop/archive pages
        if (!is_shop() && !is_product_category() && !is_product_tag()) {
            return false;
        }
        
        // Check if on_cover feature is enabled
        $on_cover = $this->main_plugin->get_state('_bfp_on_cover');
        return (bool) $on_cover;
    }
    
    /**
     * Enqueue assets for cover overlay functionality
     */
    public function enqueue_assets() {
        if (!$this->should_render()) {
            return;
        }
        
        wp_add_inline_style('bfp-style', $this->get_inline_styles());
    }
    
    /**
     * Get inline CSS for cover overlay
     *
     * @return string
     */
    private function get_inline_styles(): string {
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
     *
     * @param WC_Product|null $product
     */
    public function render($product = null) {
        if (!$this->should_render()) {
            return;
        }
        
        // Get product if not provided
        if (!$product) {
            global $product;
        }
        
        if (!$product) {
            return;
        }
        
        $product_id = $product->get_id();
        
        // Check if player is enabled for this product
        $enable_player = $this->main_plugin->get_state('_bfp_enable_player', false, $product_id);
        if (!$enable_player) {
            return;
        }
        
        // Check if product has audio files using the consolidated player class
        $files = $this->main_plugin->get_player()->get_product_files($product_id);
        if (empty($files)) {
            return;
        }
        
        // Enqueue player resources
        $this->main_plugin->get_player()->enqueue_resources();
        
        // Render the overlay
        $this->render_overlay_html($product_id, $product);
    }
    
    /**
     * Render the actual overlay HTML
     *
     * @param int $product_id
     * @param WC_Product $product
     */
    private function render_overlay_html(int $product_id, $product) {
        ?>
        <div class="bfp-play-on-cover" data-product-id="<?php echo esc_attr($product_id); ?>">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                <path d="M8 5v14l11-7z"/>
            </svg>
        </div>
        <div class="bfp-hidden-player-container" style="display:none;">
            <?php $this->main_plugin->get_player()->include_main_player($product, true); ?>
        </div>
        <?php
    }
}

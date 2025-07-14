<?php
namespace bfp;

/**
 * Cover Overlay Renderer
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Cover Overlay Renderer
 * Handles rendering of play button overlays on product cover images
 */
class CoverRenderer {
    
    private Plugin $mainPlugin;
    
    public function __construct(Plugin $mainPlugin) {
        $this->mainPlugin = $mainPlugin;
    }
    
    /**
     * Check if overlay should be rendered on current page
     */
    public function shouldRender(): bool {
        // Only render on shop/archive pages
        if (!is_shop() && !is_product_category() && !is_product_tag()) {
            return false;
        }
        
        // Use getState for single value retrieval
        $onCover = $this->mainPlugin->getConfig()->getState('_bfp_on_cover');
        return (bool) $onCover;
    }
    
    /**
     * Enqueue assets for cover overlay functionality
     */
    public function enqueueAssets(): void {
        if (!$this->shouldRender()) {
            return;
        }
        
        wp_add_inline_style('bfp-style', $this->getInlineStyles());
    }
    
    /**
     * Get inline CSS for cover overlay
     */
    private function getInlineStyles(): string {
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
    public function render(?\WC_Product $product = null): void {
        if (!$this->shouldRender()) {
            return;
        }
        
        // Get product if not provided
        if (!$product) {
            global $product;
        }
        
        if (!$product) {
            return;
        }
        
        $productId = $product->get_id();
        
        // Use getState with product context
        $enablePlayer = $this->mainPlugin->getConfig()->getState('_bfp_enable_player', false, $productId);
        if (!$enablePlayer) {
            return;
        }
        
        // Check if product has audio files using the consolidated player class
        $files = $this->mainPlugin->getPlayer()->getProductFiles($productId);
        if (empty($files)) {
            return;
        }
        
        // Enqueue player resources
        $this->mainPlugin->getPlayer()->enqueueResources();
        
        // Render the overlay
        $this->renderOverlayHtml($productId, $product);
    }
    
    /**
     * Render the actual overlay HTML
     */
    private function renderOverlayHtml(int $productId, \WC_Product $product): void {
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
}
       

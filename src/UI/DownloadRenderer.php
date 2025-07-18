<?php
declare(strict_types=1);

namespace Bandfront\UI;

use Bandfront\Core\Config;
use Bandfront\Utils\Debug;

/**
 * Download UI Renderer
 * 
 * Renders the download interface for WooCommerce account pages
 * 
 * @package Bandfront\UI
 * @since 2.0.0
 */
class DownloadRenderer {
    
    private Config $config;
    
    /**
     * Constructor
     */
    public function __construct(Config $config) {
        $this->config = $config;
    }
    
    /**
     * Render the custom downloads template
     */
    public function renderDownloadsTemplate(): void {
        $downloads = WC()->customer->get_downloadable_products();
        
        if (empty($downloads)) {
            return;
        }
        
        $groupedDownloads = $this->groupDownloadsByProduct($downloads);
        
        // Add security nonce
        wp_nonce_field('audio_conversion_nonce', 'audio_security');
        
        foreach ($groupedDownloads as $productId => $product) {
            $this->renderProductDownloads($productId, $product);
        }
    }
    
    /**
     * Group downloads by product
     */
    private function groupDownloadsByProduct(array $downloads): array {
        $grouped = [];
        
        foreach ($downloads as $download) {
            $productId = $download['product_id'];
            if (!isset($grouped[$productId])) {
                $grouped[$productId] = [
                    'product_name' => $download['product_name'],
                    'downloads_remaining' => $download['downloads_remaining'],
                    'access_expires' => $download['access_expires'],
                    'files' => []
                ];
            }
            $grouped[$productId]['files'][] = $download;
        }
        
        return $grouped;
    }
    
    /**
     * Render a single product's downloads
     */
    private function renderProductDownloads(int $productId, array $product): void {
        $productObj = wc_get_product($productId);
        $imageUrl = $productObj ? wp_get_attachment_image_url($productObj->get_image_id(), 'thumbnail') : '';
        
        ?>
        <div class="bfp-product-downloads" data-product-id="<?php echo esc_attr($productId); ?>">
            <div class="bfp-download-header">
                <div class="bfp-download-info">
                    <h3 class="bfp-product-title"><?php echo esc_html($product['product_name']); ?></h3>
                    <div class="bfp-download-meta">
                        <?php if ($product['downloads_remaining']): ?>
                            <span class="bfp-downloads-remaining">
                                <?php echo esc_html(sprintf(__('Downloads remaining: %s', 'bandfront-player'), $product['downloads_remaining'])); ?>
                            </span>
                        <?php endif; ?>
                        <?php if ($product['access_expires']): ?>
                            <span class="bfp-access-expires">
                                <?php echo esc_html(sprintf(__('Expires: %s', 'bandfront-player'), date('F j, Y', strtotime($product['access_expires'])))); ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php if ($imageUrl): ?>
                    <img src="<?php echo esc_url($imageUrl); ?>" 
                         alt="<?php echo esc_attr($product['product_name']); ?>" 
                         class="bfp-product-image">
                <?php endif; ?>
            </div>
            
            <?php if (count($product['files']) > 1): ?>
                <div class="bfp-download-controls">
                    <div class="bfp-download-dropdown">
                        <button class="bfp-download-all-files button alt">
                            <span class="bfp-button-text"><?php esc_html_e('Download All As...', 'bandfront-player'); ?></span>
                            <span class="bfp-spinner" style="display:none;"></span>
                        </button>
                        <div class="bfp-format-menu">
                            <?php $this->renderFormatOptions(); ?>
                        </div>
                    </div>
                    <button class="bfp-expand-button" 
                            aria-expanded="false" 
                            aria-controls="bfp-files-<?php echo esc_attr($productId); ?>">
                        ▼
                    </button>
                </div>
            <?php endif; ?>
            
            <ul class="bfp-download-files" 
                id="bfp-files-<?php echo esc_attr($productId); ?>" 
                style="display:none;">
                <?php foreach ($product['files'] as $file): ?>
                    <li class="bfp-download-file">
                        <a href="<?php echo esc_url($file['download_url']); ?>" 
                           class="bfp-download-link">
                            <?php echo esc_html($file['file']['name']); ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php
    }
    
    /**
     * Render format options
     */
    private function renderFormatOptions(): void {
        $formats = [
            'wav' => __('WAV (Original)', 'bandfront-player'),
            'mp3' => __('MP3', 'bandfront-player'),
            'flac' => __('FLAC', 'bandfront-player'),
            'aiff' => __('AIFF', 'bandfront-player'),
            'alac' => __('ALAC', 'bandfront-player'),
            'ogg' => __('OGG Vorbis', 'bandfront-player')
        ];
        
        foreach ($formats as $format => $label) {
            printf(
                '<a href="#" data-format="%s" class="bfp-format-option">%s</a>',
                esc_attr($format),
                esc_html($label)
            );
        }
    }
}

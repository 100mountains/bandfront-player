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
        Debug::log('DownloadRenderer: renderDownloadsTemplate called'); // DEBUG-REMOVE
        
        $downloads = WC()->customer->get_downloadable_products();
        
        Debug::log('DownloadRenderer: Found downloads', ['count' => count($downloads)]); // DEBUG-REMOVE
        
        if (empty($downloads)) {
            echo '<p>' . esc_html__('No downloads available yet.', 'bandfront-player') . '</p>';
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
        // Get product image URL
        $productObj = wc_get_product($productId);
        $imageUrl = $productObj ? wp_get_attachment_image_url($productObj->get_image_id(), 'thumbnail') : '';
        ?>
        <div class="product-downloads" data-product-id="<?php echo esc_attr($productId); ?>">
            <!-- Flex row for info and image -->
            <div style="display:flex;align-items:center;justify-content:space-between;">
                <div style="flex:1;min-width:0;">
                    <h3 class="bluu-text"><?php echo esc_html($product['product_name']); ?></h3>
                    <div class="download-info">
                        <?php if ($product['downloads_remaining']): ?>
                            <span class="downloads-remaining"><?php echo esc_html__('Downloads remaining:', 'bandfront-player'); ?> <?php echo esc_html($product['downloads_remaining']); ?></span>
                        <?php endif; ?>
                        <?php if ($product['access_expires']): ?>
                            <span class="access-expires"><?php echo esc_html__('Expires:', 'bandfront-player'); ?> <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($product['access_expires']))); ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php if ($imageUrl): ?>
                    <img src="<?php echo esc_url($imageUrl); ?>" alt="<?php echo esc_attr($product['product_name']); ?>" style="max-width:140px;max-height:140px;margin-left:1.5em;border-radius:8px;box-shadow:0 2px 12px rgba(0,0,0,0.18);object-fit:cover;">
                <?php endif; ?>
            </div>
            
            <?php if (count($product['files']) > 1): ?>
                <!-- Dropdown and caret inside the background box -->
                <div class="download-all-wrapper">
                    <div class="download-dropdown">
                        <button class="download-all-files button alt"><span class="button-text"><?php echo esc_html__('Download All As...', 'bandfront-player'); ?></span><span class="spinner" style="display:none;"></span></button>
                        <div class="download-format-menu">
                            <a href="#" data-format="wav" class="format-option alacti-text"><?php echo esc_html__('WAV (Original)', 'bandfront-player'); ?></a>
                            <a href="#" data-format="mp3" class="format-option alacti-text"><?php echo esc_html__('MP3', 'bandfront-player'); ?></a>
                            <a href="#" data-format="flac" class="format-option alacti-text"><?php echo esc_html__('FLAC', 'bandfront-player'); ?></a>
                            <a href="#" data-format="aiff" class="format-option alacti-text"><?php echo esc_html__('AIFF', 'bandfront-player'); ?></a>
                            <a href="#" data-format="alac" class="format-option alacti-text"><?php echo esc_html__('ALAC', 'bandfront-player'); ?></a>
                            <a href="#" data-format="ogg" class="format-option alacti-text"><?php echo esc_html__('OGG Vorbis', 'bandfront-player'); ?></a>
                        </div>
                    </div>
                    <button class="expand-button" aria-expanded="false" aria-controls="files-<?php echo esc_attr($productId); ?>">▼</button>
                </div>
            <?php endif; ?>
            
            <!-- Product files list, hidden by default -->
            <ul class="download-files" id="files-<?php echo esc_attr($productId); ?>" style="display:none;">
                <?php foreach ($product['files'] as $file): ?>
                    <li class="download-file">
                        <a href="<?php echo esc_url($file['download_url']); ?>" class="woocommerce-MyAccount-downloads-file">
                            <?php echo esc_html($file['file']['name']); ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php
    }
}
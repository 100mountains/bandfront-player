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
     * Render a single product's downloads - matching original template exactly
     */
    private function renderProductDownloads(int $productId, array $product): void {
        // Get product image URL
        $productObj = wc_get_product($productId);
        $imageUrl = $productObj ? wp_get_attachment_image_url($productObj->get_image_id(), 'thumbnail') : '';
        ?>
        <div class="product-downloads" data-product-id="<?php echo esc_attr($productId); ?>" style="background:#181818;padding:1.5em 1.5em 1em 1.5em;border-radius:12px;margin-bottom:2em;">
            <!-- Flex row for info and image -->
            <div style="display:flex;align-items:center;justify-content:space-between;">
                <div style="flex:1;min-width:0;">
                    <h3 class="bluu-text" style="margin-top:0;"><?php echo esc_html($product['product_name']); ?></h3>
                    <div class="download-info">
                        <?php if ($product['downloads_remaining']): ?>
                            <span class="downloads-remaining">Downloads remaining: <?php echo esc_html($product['downloads_remaining']); ?></span>
                        <?php endif; ?>
                        <?php if ($product['access_expires']): ?>
                            <span class="access-expires">Expires: <?php echo esc_html(date('F j, Y', strtotime($product['access_expires']))); ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php if ($imageUrl): ?>
                    <img src="<?php echo esc_url($imageUrl); ?>" alt="<?php echo esc_attr($product['product_name']); ?>" style="max-width:140px;max-height:140px;margin-left:1.5em;border-radius:8px;box-shadow:0 2px 12px rgba(0,0,0,0.18);object-fit:cover;">
                <?php endif; ?>
            </div>
            <?php if (count($product['files']) > 1): ?>
                <!-- Dropdown and caret inside the background box -->
                <div class="download-all-wrapper" style="display:flex;align-items:center;justify-content:space-between;margin-top:1em;">
                    <div class="download-dropdown">
                        <button class="download-all-files button alt"><span class="button-text">Download All As...</span><span class="spinner" style="display:none;margin-left:8px;vertical-align:middle;width:18px;height:18px;"></span></button>
                        <div class="download-format-menu">
                            <a href="#" data-format="wav" class="format-option alacti-text">WAV (Original)</a>
                            <a href="#" data-format="mp3" class="format-option alacti-text">MP3</a>
                            <a href="#" data-format="flac" class="format-option alacti-text">FLAC</a>
                            <a href="#" data-format="aiff" class="format-option alacti-text">AIFF</a>
                            <a href="#" data-format="alac" class="format-option alacti-text">ALAC</a>
                            <a href="#" data-format="ogg" class="format-option alacti-text">OGG Vorbis</a>
                        </div>
                    </div>
                    <button class="expand-button" aria-expanded="false" aria-controls="files-<?php echo esc_attr($productId); ?>" style="margin-left:1em;background:none;border:none;color:#ffd700;font-size:1.2em;cursor:pointer;align-self:center;">▼</button>
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
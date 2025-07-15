<?php
namespace bfp;

class FormatDownloader {
    
    /**
     * Get download URL for specific format
     */
    public function getFormatDownloadUrl(int $productId, string $format = 'mp3'): string {
        $purchased = $this->checkPurchaseStatus($productId);
        if (!$purchased) {
            return '';
        }
        
        $uploadsUrl = $this->getWooCommerceUploadsUrl();
        return $uploadsUrl . "/bfp-formats/{$productId}/zips/{$format}.zip";
    }
    
    /**
     * Get individual track URL in specific format
     */
    public function getTrackFormatUrl(int $productId, string $trackName, string $format = 'mp3'): string {
        $purchased = $this->checkPurchaseStatus($productId);
        if (!$purchased) {
            return '';
        }
        
        $uploadsUrl = $this->getWooCommerceUploadsUrl();
        return $uploadsUrl . "/bfp-formats/{$productId}/{$format}/{$trackName}.{$format}";
    }
    
    /**
     * Get WooCommerce uploads directory URL
     */
    private function getWooCommerceUploadsUrl(): string {
        $uploadDir = wp_upload_dir();
        return $uploadDir['baseurl'] . '/woocommerce_uploads';
    }
}
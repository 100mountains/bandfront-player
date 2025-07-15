<?php
namespace bfp;

class ProductProcessor {
    
    private Plugin $mainPlugin;
    
    public function __construct(Plugin $mainPlugin) {
        $this->mainPlugin = $mainPlugin;
        
        // Hook into WooCommerce product save events
        add_action('woocommerce_process_product_meta', [$this, 'processProductAudio'], 20);
        add_action('woocommerce_update_product', [$this, 'processProductAudio'], 20);
    }
    
    /**
     * Process audio files when product is saved/updated
     */
    public function processProductAudio(int $productId): void {
        $product = wc_get_product($productId);
        
        if (!$product || !$product->is_downloadable()) {
            return;
        }
        
        $audioFiles = $this->getAudioDownloads($product);
        if (empty($audioFiles)) {
            return;
        }
        
        $this->generateAudioFormats($productId, $audioFiles);
    }
    
    /**
     * Get audio files from WooCommerce downloads
     */
    private function getAudioDownloads(WC_Product $product): array {
        $downloads = $product->get_downloads();
        $audioFiles = [];
        
        foreach ($downloads as $download) {
            $file = $download->get_file();
            if ($this->isAudioFile($file)) {
                $audioFiles[] = [
                    'id' => $download->get_id(),
                    'name' => $download->get_name(),
                    'file' => $file,
                    'local_path' => $this->getLocalPath($file)
                ];
            }
        }
        
        return $audioFiles;
    }
    
    /**
     * Generate all audio formats and zip packages
     */
    private function generateAudioFormats(int $productId, array $audioFiles): void {
        $uploadsDir = $this->getWooCommerceUploadsDir();
        $productDir = $uploadsDir . '/bfp-formats/' . $productId;
        
        // Create directory structure
        wp_mkdir_p($productDir . '/mp3');
        wp_mkdir_p($productDir . '/wav');
        wp_mkdir_p($productDir . '/flac');
        wp_mkdir_p($productDir . '/zips');
        
        foreach ($audioFiles as $audio) {
            if (!$audio['local_path']) continue;
            
            // Generate different formats
            $this->convertToFormat($audio['local_path'], $productDir . '/mp3/' . $audio['name'] . '.mp3', 'mp3');
            $this->convertToFormat($audio['local_path'], $productDir . '/wav/' . $audio['name'] . '.wav', 'wav');
            $this->convertToFormat($audio['local_path'], $productDir . '/flac/' . $audio['name'] . '.flac', 'flac');
        }
        
        // Create zip packages
        $this->createZipPackages($productId, $productDir);
        
        // Store metadata
        update_post_meta($productId, '_bfp_formats_generated', time());
        update_post_meta($productId, '_bfp_available_formats', ['mp3', 'wav', 'flac']);
    }
}
<?php
namespace bfp;

/**
 * Format Download Handler
 * 
 * Provides URLs and download functionality for pre-generated audio formats
 * 
 * @package BandfrontPlayer
 * @since 0.1
 */

if (!defined('ABSPATH')) {
    exit;
}

class FormatDownloader {
    
    private Plugin $mainPlugin;
    
    public function __construct(Plugin $mainPlugin) {
        $this->mainPlugin = $mainPlugin;
        $this->addConsoleLog('FormatDownloader initialized');
        
        // Add download endpoint
        add_action('init', [$this, 'registerDownloadEndpoint']);
        add_action('template_redirect', [$this, 'handleDownloadRequest']);
    }
    
    /**
     * Register custom rewrite endpoint for downloads
     */
    public function registerDownloadEndpoint(): void {
        add_rewrite_endpoint('bfp-download', EP_ROOT);
        
        // Add rewrite rules for pretty URLs
        add_rewrite_rule(
            '^bfp-download/([0-9]+)/([a-z]+)/?$',
            'index.php?bfp-download=1&product_id=$matches[1]&format=$matches[2]',
            'top'
        );
    }
    
    /**
     * Handle download requests
     */
    public function handleDownloadRequest(): void {
        global $wp_query;
        
        if (!isset($wp_query->query_vars['bfp-download'])) {
            return;
        }
        
        $productId = intval($_GET['product_id'] ?? 0);
        $format = sanitize_text_field($_GET['format'] ?? 'mp3');
        
        $this->addConsoleLog('handleDownloadRequest', [
            'productId' => $productId,
            'format' => $format
        ]);
        
        if (!$productId) {
            wp_die(__('Invalid product ID', 'bandfront-player'), '', ['response' => 400]);
        }
        
        // Check purchase status
        if (!$this->checkPurchaseStatus($productId)) {
            wp_die(__('You do not have permission to download this file', 'bandfront-player'), '', ['response' => 403]);
        }
        
        // Get zip file path
        $zipPath = $this->getFormatZipPath($productId, $format);
        
        if (!file_exists($zipPath)) {
            $this->addConsoleLog('handleDownloadRequest file not found', $zipPath);
            wp_die(__('File not found', 'bandfront-player'), '', ['response' => 404]);
        }
        
        // Send file
        $this->sendDownload($zipPath, $productId, $format);
    }
    
    /**
     * Get download URL for specific format
     */
    public function getFormatDownloadUrl(int $productId, string $format = 'mp3'): string {
        $purchased = $this->checkPurchaseStatus($productId);
        if (!$purchased) {
            $this->addConsoleLog('getFormatDownloadUrl no purchase', ['productId' => $productId]);
            return '';
        }
        
        // Check if format exists
        if (!$this->formatExists($productId, $format)) {
            $this->addConsoleLog('getFormatDownloadUrl format not found', [
                'productId' => $productId,
                'format' => $format
            ]);
            return '';
        }
        
        // Use pretty URL if available
        if (get_option('permalink_structure')) {
            return home_url("/bfp-download/{$productId}/{$format}/");
        }
        
        // Fallback to query string
        return add_query_arg([
            'bfp-download' => 1,
            'product_id' => $productId,
            'format' => $format
        ], home_url());
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
        $trackUrl = $uploadsUrl . "/bfp-formats/{$productId}/{$format}/{$trackName}.{$format}";
        
        // Verify file exists
        $trackPath = $this->getWooCommerceUploadsDir() . "/bfp-formats/{$productId}/{$format}/{$trackName}.{$format}";
        if (!file_exists($trackPath)) {
            $this->addConsoleLog('getTrackFormatUrl file not found', $trackPath);
            return '';
        }
        
        return $trackUrl;
    }
    
    /**
     * Get available formats for a product
     */
    public function getAvailableFormats(int $productId): array {
        $formats = get_post_meta($productId, '_bfp_available_formats', true);
        
        if (!is_array($formats)) {
            $formats = [];
        }
        
        $this->addConsoleLog('getAvailableFormats', [
            'productId' => $productId,
            'formats' => $formats
        ]);
        
        return $formats;
    }
    
    /**
     * Get format display name
     */
    public function getFormatDisplayName(string $format): string {
        $names = [
            'mp3' => 'MP3',
            'wav' => 'WAV',
            'flac' => 'FLAC',
            'ogg' => 'OGG Vorbis'
        ];
        
        return $names[$format] ?? strtoupper($format);
    }
    
    /**
     * Generate download buttons HTML
     */
    public function getDownloadButtonsHtml(int $productId): string {
        if (!$this->checkPurchaseStatus($productId)) {
            return '';
        }
        
        $formats = $this->getAvailableFormats($productId);
        if (empty($formats)) {
            return '';
        }
        
        $html = '<div class="bfp-format-downloads">';
        $html .= '<h4>' . esc_html__('Download Album', 'bandfront-player') . '</h4>';
        $html .= '<div class="bfp-format-buttons">';
        
        foreach ($formats as $format) {
            $url = $this->getFormatDownloadUrl($productId, $format);
            if ($url) {
                $displayName = $this->getFormatDisplayName($format);
                $html .= sprintf(
                    '<a href="%s" class="bfp-download-button bfp-format-%s">%s</a>',
                    esc_url($url),
                    esc_attr($format),
                    esc_html($displayName)
                );
            }
        }
        
        $html .= '</div>';
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Check if user purchased the product
     */
    private function checkPurchaseStatus(int $productId): bool {
        if (!is_user_logged_in()) {
            return false;
        }
        
        $woocommerce = $this->mainPlugin->getWooCommerce();
        if (!$woocommerce) {
            return false;
        }
        
        return $woocommerce->woocommerceUserProduct($productId);
    }
    
    /**
     * Check if format exists for product
     */
    private function formatExists(int $productId, string $format): bool {
        $zipPath = $this->getFormatZipPath($productId, $format);
        return file_exists($zipPath);
    }
    
    /**
     * Get zip file path for format
     */
    private function getFormatZipPath(int $productId, string $format): string {
        return $this->getWooCommerceUploadsDir() . "/bfp-formats/{$productId}/zips/{$format}.zip";
    }
    
    /**
     * Send download file
     */
    private function sendDownload(string $filePath, int $productId, string $format): void {
        $product = wc_get_product($productId);
        $productName = $product ? sanitize_file_name($product->get_name()) : "Album";
        $filename = "{$productName}_{$format}.zip";
        
        $this->addConsoleLog('sendDownload', [
            'filePath' => $filePath,
            'filename' => $filename
        ]);
        
        // Clear any output buffers
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        // Set headers
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($filePath));
        header('Content-Transfer-Encoding: binary');
        header('Cache-Control: no-cache, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        // Send file
        readfile($filePath);
        
        // Log download
        do_action('bfp_format_downloaded', $productId, $format);
        
        exit;
    }
    
    /**
     * Get WooCommerce uploads directory path
     */
    private function getWooCommerceUploadsDir(): string {
        $uploadDir = wp_upload_dir();
        return $uploadDir['basedir'] . '/woocommerce_uploads';
    }
    
    /**
     * Get WooCommerce uploads directory URL
     */
    private function getWooCommerceUploadsUrl(): string {
        $uploadDir = wp_upload_dir();
        return $uploadDir['baseurl'] . '/woocommerce_uploads';
    }
    
    /**
     * Add console log for debugging
     */
    private function addConsoleLog(string $message, $data = null): void {
        echo '<script>console.log("[BFP FormatDownloader] ' . esc_js($message) . '", ' . 
             wp_json_encode($data) . ');</script>';
    }
}
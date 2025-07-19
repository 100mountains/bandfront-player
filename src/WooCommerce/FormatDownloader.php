<?php
declare(strict_types=1);

namespace Bandfront\WooCommerce;

use Bandfront\Core\Config;
use Bandfront\Storage\FileManager;
use Bandfront\Utils\Debug;

// Set domain for WooCommerce
Debug::domain('woocommerce');

/**
 * Format Download Handler
 * 
 * Provides URLs and download functionality for pre-generated audio formats
 * 
 * @package Bandfront\WooCommerce
 * @since 2.0.0
 */
class FormatDownloader {
    
    private Config $config;
    private FileManager $fileManager;
    
    /**
     * Constructor - accepts only needed dependencies
     */
    public function __construct(Config $config, FileManager $fileManager) {
        $this->config = $config;
        $this->fileManager = $fileManager;
        
        // Note: Hook registration moved to Core\Hooks.php
    }
    
    /**
     * Register custom rewrite endpoint for downloads
     */
    public function registerDownloadEndpoint(): void {
        Debug::log('FormatDownloader: Registering download endpoint'); // DEBUG-REMOVE
        
        // Add custom rewrite endpoint
        add_rewrite_endpoint('bfp-download', EP_PERMALINK | EP_PAGES);
        
        // Register query var
        add_filter('query_vars', function($vars) {
            $vars[] = 'bfp-download';
            return $vars;
        });
        
        // Handle endpoint requests
        add_action('template_redirect', [$this, 'handleDownloadEndpoint']);
        
        // Remove the wp_enqueue_scripts action from here - it only runs on activation
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
        
        Debug::log('FormatDownloader: handleDownloadRequest', [
            'productId' => $productId,
            'format' => $format
        ]); // DEBUG-REMOVE
        
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
            Debug::log('FormatDownloader: file not found', $zipPath); // DEBUG-REMOVE
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
            Debug::log('FormatDownloader: no purchase', ['productId' => $productId]); // DEBUG-REMOVE
            return '';
        }
        
        // Check if format exists
        if (!$this->formatExists($productId, $format)) {
            Debug::log('FormatDownloader: format not found', [
                'productId' => $productId,
                'format' => $format
            ]); // DEBUG-REMOVE
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
            Debug::log('FormatDownloader: track file not found', $trackPath); // DEBUG-REMOVE
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
        
        Debug::log('FormatDownloader: getAvailableFormats', [
            'productId' => $productId,
            'formats' => $formats
        ]); // DEBUG-REMOVE
        
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
        
        // Get WooCommerce integration via Bootstrap
        $bootstrap = \Bandfront\Core\Bootstrap::getInstance();
        $woocommerce = $bootstrap ? $bootstrap->getComponent('woocommerce') : null;
        
        if (!$woocommerce) {
            return false;
        }
        
        return (bool) $woocommerce->isUserProduct($productId);
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
        
        Debug::log('FormatDownloader: sendDownload', [
            'filePath' => $filePath,
            'filename' => $filename
        ]); // DEBUG-REMOVE
        
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
     * Add download columns to account page
     * Hook callback for 'woocommerce_account_downloads_columns'
     */
    public function addDownloadColumns(array $columns): array {
        // Add format column before download actions
        $newColumns = [];
        foreach ($columns as $key => $label) {
            if ($key === 'download-actions') {
                $newColumns['download-format'] = __('Format', 'bandfront-player');
            }
            $newColumns[$key] = $label;
        }
        return $newColumns;
    }
    
    /**
     * Render format column content
     * Hook callback for 'woocommerce_account_downloads_column_download-format'
     */
    public function renderFormatColumn($download): void {
        $productId = $download['product_id'] ?? 0;
        if (!$productId) {
            return;
        }
        
        $formats = $this->getAvailableFormats($productId);
        if (empty($formats)) {
            echo '<span class="bfp-no-formats">—</span>';
            return;
        }
        
        echo '<div class="bfp-format-links">';
        foreach ($formats as $format) {
            $url = $this->getFormatDownloadUrl($productId, $format);
            if ($url) {
                printf(
                    '<a href="%s" class="bfp-format-link">%s</a> ',
                    esc_url($url),
                    esc_html($this->getFormatDisplayName($format))
                );
            }
        }
        echo '</div>';
    }
    
    /**
     * Render available formats for downloads page
     * Hook callback for 'woocommerce_account_downloads_columns_download-format'
     */
    public function renderAvailableFormats(int $productId): string {
        $formats = $this->getAvailableFormats($productId);
        if (empty($formats)) {
            return '<span class="bfp-no-formats">—</span>';
        }
        
        $html = '<div class="bfp-available-formats">';
        foreach ($formats as $format) {
            $url = $this->getFormatDownloadUrl($productId, $format);
            if ($url) {
                $html .= sprintf(
                    '<a href="%s" class="bfp-format-link bfp-format-%s">%s</a> ',
                    esc_url($url),
                    esc_attr($format),
                    esc_html($this->getFormatDisplayName($format))
                );
            }
        }
        $html .= '</div>';
        
        return $html;
    }
}
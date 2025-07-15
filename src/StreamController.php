<?php
namespace bfp;

/**
 * REST API Streaming Controller
 * 
 * Handles audio file streaming via WordPress REST API
 * 
 * @package BandfrontPlayer
 * @since 0.1
 */

if (!defined('ABSPATH')) {
    exit;
}

class StreamController {
    
    private Plugin $mainPlugin;
    
    /**
     * Constructor
     */
    public function __construct(Plugin $mainPlugin) {
        $this->mainPlugin = $mainPlugin;
    }
    
    /**
     * Register REST API routes
     */
    public function register(): void {
        add_action('rest_api_init', [$this, 'registerRoutes']);
    }
    
    /**
     * Register the streaming routes
     */
    public function registerRoutes(): void {
        register_rest_route('bandfront-player/v1', '/stream/(?P<product_id>\d+)/(?P<file_index>[a-zA-Z0-9_-]+)', [
            'methods' => 'GET',
            'callback' => [$this, 'streamFile'],
            'permission_callback' => [$this, 'checkPermission'],
            'args' => [
                'product_id' => [
                    'required' => true,
                    'validate_callback' => function($param) {
                        return is_numeric($param);
                    },
                    'sanitize_callback' => 'absint',
                ],
                'file_index' => [
                    'required' => true,
                    'sanitize_callback' => 'sanitize_text_field',
                ],
            ],
        ]);
    }
    
    /**
     * Stream audio file
     */
    public function streamFile(\WP_REST_Request $request): void {
        $productId = $request->get_param('product_id');
        $fileIndex = $request->get_param('file_index');
        
        error_log('BFP StreamController: Stream request - Product=' . $productId . ', File=' . $fileIndex);
        
        // Get product
        if (!function_exists('wc_get_product')) {
            wp_die('WooCommerce not available', 500);
        }
        
        $product = wc_get_product($productId);
        if (!$product) {
            wp_die('Product not found', 404);
        }
        
        // Get files for this product
        $files = $this->mainPlugin->getFiles()->getProductFiles($productId);
        
        // Find the requested file
        $file = null;
        if (isset($files[$fileIndex])) {
            $file = $files[$fileIndex];
        } else {
            // Try numeric index for backward compatibility
            $numericId = intval($fileIndex);
            $index = 0;
            foreach ($files as $key => $f) {
                if ($index === $numericId) {
                    $file = $f;
                    break;
                }
                $index++;
            }
        }
        
        if (!$file || empty($file['file'])) {
            error_log('BFP StreamController: File not found - ' . $fileIndex);
            wp_die('File not found', 404);
        }
        
        $fileUrl = $file['file'];
        error_log('BFP StreamController: Streaming file - ' . $fileUrl);
        
        // Track play event
        do_action('bfp_play_file', $productId, $fileUrl);
        
        // Check if user has purchased product
        $purchased = false;
        $woocommerce = $this->mainPlugin->getWooCommerce();
        if ($woocommerce) {
            $purchased = $woocommerce->woocommerceUserProduct($productId);
        }
        
        // Get settings
        $settings = $this->mainPlugin->getConfig()->getStates([
            '_bfp_secure_player',
            '_bfp_file_percent'
        ], $productId);
        
        // Stream the file
        $this->handleStreaming($fileUrl, $productId, $settings, $purchased);
    }
    
    /**
     * Check streaming permissions
     */
    public function checkPermission(\WP_REST_Request $request): bool {
        $productId = $request->get_param('product_id');
        
        // Check if registered users only
        if ($this->mainPlugin->getConfig()->getState('_bfp_registered_only') && !is_user_logged_in()) {
            return false;
        }
        
        // Additional permission checks can be added here
        return true;
    }
    
    /**
     * Handle the actual file streaming
     */
    private function handleStreaming(string $fileUrl, int $productId, array $settings, bool $purchased): void {
        // Process cloud URLs if needed
        $processedUrl = $this->mainPlugin->getFiles()->processCloudUrl($fileUrl);
        
        // Check if file is local
        $localPath = $this->mainPlugin->getFiles()->isLocal($processedUrl);
        
        if ($localPath && file_exists($localPath)) {
            // Local file streaming
            $mimeType = $this->mainPlugin->getFiles()->getMimeType($localPath);
            
            // Set headers
            header("Content-Type: $mimeType");
            header("Accept-Ranges: bytes");
            header("Cache-Control: no-cache, must-revalidate");
            header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
            
            $filesize = filesize($localPath);
            
            // Handle range requests for seeking
            if (isset($_SERVER['HTTP_RANGE'])) {
                $this->handleRangeRequest($localPath, $filesize, $mimeType);
            } else {
                // No range request - send whole file or demo
                if (!$purchased && $settings['_bfp_secure_player'] && $settings['_bfp_file_percent'] < 100) {
                    // Send demo version
                    $this->streamDemo($localPath, $filesize, $settings['_bfp_file_percent']);
                } else {
                    // Send full file
                    header("Content-Length: $filesize");
                    readfile($localPath);
                }
            }
        } else {
            // Remote file - redirect
            error_log('BFP StreamController: Redirecting to remote URL - ' . $processedUrl);
            wp_redirect($processedUrl);
            exit;
        }
        
        exit;
    }
    
    /**
     * Handle HTTP range requests
     */
    private function handleRangeRequest(string $filePath, int $filesize, string $mimeType): void {
        $range = $_SERVER['HTTP_RANGE'];
        list($rangeType, $rangeValue) = explode('=', $range, 2);
        
        if ($rangeType === 'bytes') {
            list($start, $end) = explode('-', $rangeValue, 2);
            $start = intval($start);
            $end = empty($end) ? ($filesize - 1) : intval($end);
            $length = $end - $start + 1;
            
            header("HTTP/1.1 206 Partial Content");
            header("Content-Range: bytes $start-$end/$filesize");
            header("Content-Length: $length");
            header("Content-Type: $mimeType");
            
            $fp = fopen($filePath, 'rb');
            fseek($fp, $start);
            
            $bufferSize = 8192;
            $bytesToRead = $length;
            
            while (!feof($fp) && $bytesToRead > 0) {
                $buffer = fread($fp, min($bufferSize, $bytesToRead));
                echo $buffer;
                flush();
                $bytesToRead -= strlen($buffer);
            }
            
            fclose($fp);
        } else {
            // Invalid range type
            header("HTTP/1.1 416 Requested Range Not Satisfiable");
            header("Content-Range: bytes */$filesize");
        }
    }
    
    /**
     * Stream demo version of file
     */
    private function streamDemo(string $filePath, int $filesize, int $percent): void {
        $bytesToSend = floor($filesize * ($percent / 100));
        header("Content-Length: $bytesToSend");
        
        $fp = fopen($filePath, 'rb');
        $bytesSent = 0;
        
        while (!feof($fp) && $bytesSent < $bytesToSend) {
            $buffer = fread($fp, min(8192, $bytesToSend - $bytesSent));
            echo $buffer;
            $bytesSent += strlen($buffer);
            flush();
        }
        
        fclose($fp);
    }
}

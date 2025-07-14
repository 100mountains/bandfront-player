<?php
namespace bfp\Utils;

use bfp\Plugin;

/**
 * Preview management for Bandfront Player
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Preview Manager Class
 * Handles preview generation and demo file management
 */
class Preview {
    
    private Plugin $mainPlugin;
    
    public function __construct(Plugin $mainPlugin) {
        $this->mainPlugin = $mainPlugin;
    }
    
    /**
     * Initialize preview functionality
     */
    public function init(): void {
        add_action('init', [$this, 'handlePreviewRequest']);
    }
    
    /**
     * Handle preview/play requests
     */
    public function handlePreviewRequest(): void {
        if (
            isset($_REQUEST['bfp-action']) && 
            'play' == sanitize_text_field(wp_unslash($_REQUEST['bfp-action'])) &&
            isset($_REQUEST['bfp-product']) &&
            is_numeric($_REQUEST['bfp-product']) &&
            isset($_REQUEST['bfp-file']) &&
            is_numeric($_REQUEST['bfp-file'])
        ) {
            $productId = intval($_REQUEST['bfp-product']);
            $fileIndex = intval($_REQUEST['bfp-file']);
            
            $this->processPlayRequest($productId, $fileIndex);
        }
    }
    
    /**
     * Process play request for a specific file
     */
    private function processPlayRequest(int $productId, int $fileIndex): void {
        $files = $this->mainPlugin->getPlayer()->getProductFiles($productId);
        
        if (!empty($files) && isset($files[$fileIndex])) {
            $file = $files[$fileIndex];
            
            // Increment playback counter
            if ($this->mainPlugin->getAnalytics()) {
                $this->mainPlugin->getAnalytics()->incrementPlaybackCounter($productId);
            }
            
            // Check if secure player is enabled
            $securePlayer = $this->mainPlugin->getConfig()->getState('_bfp_secure_player', false, $productId);
            $filePercent = $this->mainPlugin->getConfig()->getState('_bfp_file_percent', 50, $productId);
            
            // Output the file
            $this->mainPlugin->getAudioCore()->outputFile([
                'url' => $file['file'],
                'product_id' => $productId,
                'secure_player' => $securePlayer,
                'file_percent' => $filePercent
            ]);
        }
    }
}
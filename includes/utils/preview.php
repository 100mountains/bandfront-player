<?php
/**
 * Preview management for Bandfront Player
 *
 * @package BandfrontPlayer
 * @since 0.1
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * BFP Preview Manager Class
 * Handles preview generation and demo file management
 */
class BFP_Preview {
    
    private $main_plugin;
    
    public function __construct($main_plugin) {
        $this->main_plugin = $main_plugin;
    }
    
    /**
     * Initialize preview functionality
     */
    public function init() {
        add_action('init', array($this, 'handle_preview_request'));
    }
    
    /**
     * Handle preview/play requests
     */
    public function handle_preview_request() {
        if (
            isset($_REQUEST['bfp-action']) && 
            'play' == sanitize_text_field(wp_unslash($_REQUEST['bfp-action'])) &&
            isset($_REQUEST['bfp-product']) &&
            is_numeric($_REQUEST['bfp-product']) &&
            isset($_REQUEST['bfp-file']) &&
            is_numeric($_REQUEST['bfp-file'])
        ) {
            $product_id = intval($_REQUEST['bfp-product']);
            $file_index = intval($_REQUEST['bfp-file']);
            
            $this->process_play_request($product_id, $file_index);
        }
    }
    
    /**
     * Process play request for a specific file
     */
    private function process_play_request($product_id, $file_index) {
        $files = $this->main_plugin->get_product_files($product_id);
        
        if (!empty($files) && isset($files[$file_index])) {
            $file = $files[$file_index];
            
            // Increment playback counter
            if ($this->main_plugin->get_analytics()) {
                $this->main_plugin->get_analytics()->increment_playback_counter($product_id);
            }
            
            // Check if secure player is enabled
            $secure_player = $this->main_plugin->get_product_attr($product_id, '_bfp_secure_player', false);
            $file_percent = $this->main_plugin->get_product_attr($product_id, '_bfp_file_percent', BFP_FILE_PERCENT);
            
            // Output the file
            $this->main_plugin->get_audio_core()->output_file(array(
                'url' => $file['file'],
                'product_id' => $product_id,
                'secure_player' => $secure_player,
                'file_percent' => $file_percent
            ));
        }
    }
}

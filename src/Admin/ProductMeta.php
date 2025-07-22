<?php
declare(strict_types=1);

namespace Bandfront\Admin;

use Bandfront\Core\Config;
use Bandfront\Storage\FileManager;
use Bandfront\Utils\Debug;

// Set domain for Admin  
Debug::domain('admin');

/**
 * Product meta management
 * 
 * @package Bandfront\Admin
 * @since 2.0.0
 */
class ProductMeta {
    
    private Config $config;
    private FileManager $fileManager;
    
    /**
     * Constructor
     */
    public function __construct(Config $config, FileManager $fileManager) {
        $this->config = $config;
        $this->fileManager = $fileManager;
    }
    
    /**
     * Register metabox
     */
    public function registerMetabox(): void {
        add_meta_box(
            'bfp_woocommerce_metabox', 
            __('Bandfront Player', 'bandfront-player'), 
            [$this, 'renderMetabox'], 
            $this->config->getPostTypes(), 
            'normal'
        );
        
        Debug::log('ProductMeta.php: Metabox registered', [
            'action' => 'Bandfront Player metabox added to product edit screen'
        ]); // DEBUG-REMOVE
    }
    
    /**
     * Render metabox
     */
    public function renderMetabox(): void {
        Debug::log('ProductMeta.php: Rendering WooCommerce player settings metabox', []); // DEBUG-REMOVE
        global $post;
        
        // Make dependencies available to the template
        $config = $this->config;
        $fileManager = $this->fileManager;
        
        include_once plugin_dir_path(dirname(dirname(__FILE__))) . 'templates/product-options.php';
        Debug::log('ProductMeta.php: Finished rendering WooCommerce player settings metabox', []); // DEBUG-REMOVE
    }
    
    /**
     * Save post meta data
     */
    public function savePost(int $postId, \WP_Post $post, bool $update): void {
        Debug::log('ProductMeta.php: Entering savePost()', ['postId' => $postId, 'postType' => $post->post_type ?? null, 'update' => $update]); // DEBUG-REMOVE
        
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            Debug::log('ProductMeta.php: Doing autosave, exiting savePost()', []); // DEBUG-REMOVE
            return;
        }
        
        if (empty($_POST['bfp_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['bfp_nonce'])), 'bfp_updating_product')) {
            Debug::log('ProductMeta.php: Nonce check failed, exiting savePost()', []); // DEBUG-REMOVE
            return;
        }
        
        $postTypes = $this->config->getPostTypes();
        if (!isset($post) || !in_array($post->post_type, $postTypes) || !current_user_can('edit_post', $postId)) {
            Debug::log('ProductMeta.php: Invalid post type or permissions, exiting savePost()', []); // DEBUG-REMOVE
            return;
        }

        // Remove all vendor add-on logic and flags/options
        $_DATA = stripslashes_deep($_REQUEST);

        // Always allow saving player options (no vendor plugin checks)
        Debug::log('ProductMeta.php: Deleting post files before saving options', ['postId' => $postId]); // DEBUG-REMOVE
// FIX: deletePost method does not exist - commenting out temporarily
//         $this->fileManager->deletePost($postId, false, true);

        // Save the player options
        Debug::log('ProductMeta.php: Saving product options', ['postId' => $postId]); // DEBUG-REMOVE
        $this->saveProductOptions($postId, $_DATA);
        Debug::log('ProductMeta.php: Exiting savePost()', []); // DEBUG-REMOVE
    }
    
    /**
     * Save product-specific options
     */
    private function saveProductOptions(int $postId, array $data): void {
        Debug::log('ProductMeta.php: Entering saveProductOptions()', ['postId' => $postId]); // DEBUG-REMOVE
        
        // KEEP ONLY these product-specific settings:
        $settings = [
            '_bfp_enable_player' => isset($data['_bfp_enable_player']) ? 1 : 0,
            '_bfp_group_cart_control' => isset($data['_bfp_group_cart_control']) ? 1 : 0,
            '_bfp_unified_player' => isset($data['_bfp_unified_player']) ? 1 : 0,
            '_bfp_play_all' => isset($data['_bfp_play_all']) ? 1 : 0,
            '_bfp_loop' => isset($data['_bfp_loop']) ? 1 : 0,
        ];
        
        // Handle new nested demos structure
        $demosData = [];
        
        // Get existing demos data to preserve global settings
        $existingDemos = get_post_meta($postId, '_bfp_demos', true);
        if (is_array($existingDemos)) {
            $demosData = $existingDemos;
        }
        
        // Process nested demos structure from form data
        if (isset($data['_bfp_demos']) && is_array($data['_bfp_demos'])) {
            // Update global section if provided (though this is usually product-only)
            if (isset($data['_bfp_demos']['global'])) {
                $demosData['global'] = [
                    'enabled' => isset($data['_bfp_demos']['global']['enabled']) ? true : false,
                    'duration_percent' => isset($data['_bfp_demos']['global']['duration_percent']) ? 
                        max(1, min(100, (int) $data['_bfp_demos']['global']['duration_percent'])) : 50,
                    'demo_fade' => isset($data['_bfp_demos']['global']['demo_fade']) ? 
                        max(0, min(10, (float) $data['_bfp_demos']['global']['demo_fade'])) : 0,
                    'demo_filetype' => isset($data['_bfp_demos']['global']['demo_filetype']) && 
                        in_array($data['_bfp_demos']['global']['demo_filetype'], ['mp3', 'wav', 'ogg', 'mp4', 'm4a', 'flac']) ? 
                        $data['_bfp_demos']['global']['demo_filetype'] : 'mp3',
                    'demo_start_time' => isset($data['_bfp_demos']['global']['demo_start_time']) ? 
                        max(0, min(50, (int) $data['_bfp_demos']['global']['demo_start_time'])) : 0,
                    'message' => isset($data['_bfp_demos']['global']['message']) ? 
                        sanitize_textarea_field(wp_unslash($data['_bfp_demos']['global']['message'])) : '',
                ];
            }
            
            // Update product section
            if (isset($data['_bfp_demos']['product'])) {
                $demosData['product'] = [
                    'use_custom' => isset($data['_bfp_demos']['product']['use_custom']) ? true : false,
                    'skip_processing' => isset($data['_bfp_demos']['product']['skip_processing']) ? true : false,
                    'demos_list' => [], // Will be populated by saveDemoFiles
                ];
            }
        }
        
        // Save settings to database
        foreach ($settings as $key => $value) {
            update_post_meta($postId, $key, $value);
        }
        
        Debug::log('ProductMeta.php: Updated product meta for player options', ['postId' => $postId]); // DEBUG-REMOVE

        // Save demo files and update demos structure
        Debug::log('ProductMeta.php: Saving demo files for product', ['postId' => $postId]); // DEBUG-REMOVE
        $this->saveDemoFiles($postId, $data, $demosData);
        
        // Save the complete demos structure
        update_post_meta($postId, '_bfp_demos', $demosData);
        
        $this->config->clearProductAttrsCache($postId);
        Debug::log('ProductMeta.php: Exiting saveProductOptions()', ['postId' => $postId]); // DEBUG-REMOVE
    }
    
    /**
     * Save demo files for product
     */
    private function saveDemoFiles(int $postId, array $data, array &$demosData): void {
        Debug::log('ProductMeta.php: Entering saveDemoFiles()', ['postId' => $postId]); // DEBUG-REMOVE
        
        $demosList = [];

        if (isset($data['_bfp_file_urls']) && is_array($data['_bfp_file_urls'])) {
            Debug::log('ProductMeta.php: Processing demo file URLs', ['count' => count($data['_bfp_file_urls'])]); // DEBUG-REMOVE
            foreach ($data['_bfp_file_urls'] as $i => $url) {
                if (!empty($url)) {
                    $demosList[] = [
                        'name' => (!empty($data['_bfp_file_names']) && !empty($data['_bfp_file_names'][$i])) ? 
                                  sanitize_text_field(wp_unslash($data['_bfp_file_names'][$i])) : '',
                        'file' => esc_url_raw(wp_unslash(trim($url))),
                    ];
                }
            }
        }

        // Update the demos_list in the product section of the nested structure
        if (!isset($demosData['product'])) {
            $demosData['product'] = [
                'use_custom' => false,
                'skip_processing' => false,
                'demos_list' => []
            ];
        }
        
        $demosData['product']['demos_list'] = $demosList;
        
        // Keep legacy meta for backward compatibility (for now)
        $ownDemos = isset($data['_bfp_use_custom_demos']) ? 1 : 0;
        $directOwnDemos = isset($data['_bfp_direct_demo_links']) ? 1 : 0;
        
        update_post_meta($postId, '_bfp_use_custom_demos', $ownDemos);
        update_post_meta($postId, '_bfp_direct_demo_links', $directOwnDemos);
        update_post_meta($postId, '_bfp_demos_list', $demosList);
        
        Debug::log('ProductMeta.php: Saved demo files meta', ['postId' => $postId, 'demosList' => $demosList]); // DEBUG-REMOVE
    }
}

<?php
declare(strict_types=1);

namespace Bandfront\Admin;

use Bandfront\Core\Config;
use Bandfront\Storage\FileManager;
use Bandfront\Utils\Debug;

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
        $this->fileManager->deletePost($postId, false, true);

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
            '_bfp_merge_in_grouped' => isset($data['_bfp_merge_in_grouped']) ? 1 : 0,
            '_bfp_single_player' => isset($data['_bfp_single_player']) ? 1 : 0,
            '_bfp_play_all' => isset($data['_bfp_play_all']) ? 1 : 0,
            '_bfp_loop' => isset($data['_bfp_loop']) ? 1 : 0,
            '_bfp_player_volume' => $this->parseVolume($data),
            '_bfp_secure_player' => isset($data['_bfp_secure_player']) ? 1 : 0,
            '_bfp_file_percent' => $this->parseFilePercent($data),
        ];
        
        // Save to database
        foreach ($settings as $key => $value) {
            update_post_meta($postId, $key, $value);
        }
        
        Debug::log('ProductMeta.php: Updated product meta for player options', ['postId' => $postId]); // DEBUG-REMOVE

        // Handle product-specific audio engine override
        $this->saveAudioEngineOverride($postId, $data);

        // Save demo files
        Debug::log('ProductMeta.php: Saving demo files for product', ['postId' => $postId]); // DEBUG-REMOVE
        $this->saveDemoFiles($postId, $data);
        $this->config->clearProductAttrsCache($postId);
        Debug::log('ProductMeta.php: Exiting saveProductOptions()', ['postId' => $postId]); // DEBUG-REMOVE
    }
    
    /**
     * Parse volume setting
     */
    private function parseVolume(array $data): float {
        if (isset($data['_bfp_player_volume']) && is_numeric($data['_bfp_player_volume'])) {
            return floatval($data['_bfp_player_volume']);
        }
        return 1.0;
    }
    
    /**
     * Parse file percent setting
     */
    private function parseFilePercent(array $data): int {
        if (isset($data['_bfp_file_percent']) && is_numeric($data['_bfp_file_percent'])) {
            $percent = intval($data['_bfp_file_percent']);
            return min(max($percent, 0), 100);
        }
        return 0;
    }
    
    /**
     * Save audio engine override
     */
    private function saveAudioEngineOverride(int $postId, array $data): void {
        if (isset($data['_bfp_audio_engine'])) {
            $productAudioEngine = sanitize_text_field(wp_unslash($data['_bfp_audio_engine']));
            
            if ($productAudioEngine === 'global' || empty($productAudioEngine)) {
                // Delete the meta so it falls back to global
                Debug::log('ProductMeta.php: Removing product-specific audio engine override', ['postId' => $postId]); // DEBUG-REMOVE
                delete_post_meta($postId, '_bfp_audio_engine');
            } elseif (in_array($productAudioEngine, ['mediaelement', 'wavesurfer', 'html5'])) {
                // Save valid override
                Debug::log('ProductMeta.php: Saving product-specific audio engine override', ['postId' => $postId, 'audioEngine' => $productAudioEngine]); // DEBUG-REMOVE
                update_post_meta($postId, '_bfp_audio_engine', $productAudioEngine);
            }
        }
    }
    
    /**
     * Save demo files for product
     */
    private function saveDemoFiles(int $postId, array $data): void {
        Debug::log('ProductMeta.php: Entering saveDemoFiles()', ['postId' => $postId]); // DEBUG-REMOVE
        
        $ownDemos = isset($data['_bfp_own_demos']) ? 1 : 0;
        $directOwnDemos = isset($data['_bfp_direct_own_demos']) ? 1 : 0;
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

        update_post_meta($postId, '_bfp_own_demos', $ownDemos);
        update_post_meta($postId, '_bfp_direct_own_demos', $directOwnDemos);
        update_post_meta($postId, '_bfp_demos_list', $demosList);
        Debug::log('ProductMeta.php: Saved demo files meta', ['postId' => $postId, 'demosList' => $demosList]); // DEBUG-REMOVE
    }
}

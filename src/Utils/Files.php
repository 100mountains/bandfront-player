<?php
namespace bfp\Utils;

use bfp\Plugin;

/**
 * File handling functionality for Bandfront Player
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * File Handler Class
 */
class Files {
    
    private Plugin $mainPlugin;
    private string $filesDirectoryPath;
    private string $filesDirectoryUrl;
    
    public function __construct(Plugin $mainPlugin) {
        $this->mainPlugin = $mainPlugin;
        $this->createDirectories();
    }
    
    /**
     * Create directories for file storage
     */
    public function createDirectories(): void {
        // Generate upload dir
        $filesDirectory = wp_upload_dir();
        $this->filesDirectoryPath = rtrim($filesDirectory['basedir'], '/') . '/bfp/';
        $this->filesDirectoryUrl = rtrim($filesDirectory['baseurl'], '/') . '/bfp/';
        $this->filesDirectoryUrl = preg_replace('/^http(s)?:\/\//', '//', $this->filesDirectoryUrl);
        
        if (!file_exists($this->filesDirectoryPath)) {
            @mkdir($this->filesDirectoryPath, 0755);
        }

        if (is_dir($this->filesDirectoryPath)) {
            if (!file_exists($this->filesDirectoryPath . '.htaccess')) {
                try {
                    file_put_contents($this->filesDirectoryPath . '.htaccess', 'Options -Indexes');
                } catch (\Exception $err) {}
            }
        }

        if (!file_exists($this->filesDirectoryPath . 'purchased/')) {
            @mkdir($this->filesDirectoryPath . 'purchased/', 0755);
        }
    }
    
    /**
     * Clear directory contents
     */
    public function clearDir(string $dirPath): void {
        try {
            if (empty($dirPath) || !file_exists($dirPath) || !is_dir($dirPath)) {
                return;
            }
            $dirPath = rtrim($dirPath, '\\/') . '/';
            $files = glob($dirPath . '*', GLOB_MARK);
            foreach ($files as $file) {
                if (is_dir($file)) {
                    $this->clearDir($file);
                } else {
                    unlink($file);
                }
            }
        } catch (\Exception $err) {
            return;
        }
    }
    
    /**
     * Get files directory path
     */
    public function getFilesDirectoryPath(): string {
        return $this->filesDirectoryPath;
    }
    
    /**
     * Get files directory URL
     */
    public function getFilesDirectoryUrl(): string {
        return $this->filesDirectoryUrl;
    }
    
    /**
     * Delete post-related files and meta data
     */
    public function deletePost(int $postId, bool $demosOnly = false, bool $force = false): void {
        $post = get_post($postId);
        $postTypes = $this->mainPlugin->getPostTypes();
        if (
            isset($post) &&
            (
                !$force ||
                !in_array($post->post_type, $postTypes) ||
                !current_user_can('edit_post', $postId)
            )
        ) {
            return;
        }

        // Delete truncated version of the audio file
        $this->deleteTruncatedFiles($postId);

        if (!$demosOnly) {
            delete_post_meta($postId, '_bfp_enable_player');
            delete_post_meta($postId, '_bfp_show_in');
            delete_post_meta($postId, '_bfp_merge_in_grouped');
            delete_post_meta($postId, '_bfp_player_layout');
            delete_post_meta($postId, '_bfp_player_volume');
            delete_post_meta($postId, '_bfp_single_player');
            delete_post_meta($postId, '_bfp_secure_player');
            delete_post_meta($postId, '_bfp_file_percent');
            delete_post_meta($postId, '_bfp_player_controls');
            delete_post_meta($postId, '_bfp_player_title');
            delete_post_meta($postId, '_bfp_preload');
            delete_post_meta($postId, '_bfp_play_all');
            delete_post_meta($postId, '_bfp_loop');
            delete_post_meta($postId, '_bfp_on_cover');

            delete_post_meta($postId, '_bfp_playback_counter');
        }

        delete_post_meta($postId, '_bfp_own_demos');
        delete_post_meta($postId, '_bfp_direct_own_demos');
        delete_post_meta($postId, '_bfp_demos_list');

        do_action('bfp_delete_post', $postId);
    }
    
    /**
     * Delete purchased files based on reset interval
     */
    public function deletePurchasedFiles(): void {
        // Use getState for single value retrieval
        if ($this->mainPlugin->getConfig()->getState('_bfp_reset_purchased_interval', 'daily') == 'daily') {
            $this->clearDir($this->filesDirectoryPath . 'purchased/');
            $this->createDirectories();
        }
    }
    
    /**
     * Clear expired transients to maintain cache
     */
    public function clearExpiredTransients(): void {
        $transient = get_transient('bfp_clear_expired_transients');
        if (!$transient || 24 * 60 * 60 <= time() - intval($transient)) {
            set_transient('bfp_clear_expired_transients', time());
            delete_expired_transients();
        }
    }
    
    /**
     * Delete truncated demo files for a product
     */
    public function deleteTruncatedFiles(int $productId): void {
        $filesArr = get_post_meta($productId, '_downloadable_files', true);
        $ownFilesArr = get_post_meta($productId, '_bfp_demos_list', true);
        if (!is_array($filesArr)) {
            $filesArr = [$filesArr];
        }
        if (is_array($ownFilesArr) && !empty($ownFilesArr)) {
            $filesArr = array_merge($filesArr, $ownFilesArr);
        }

        if (!empty($filesArr) && is_array($filesArr)) {
            foreach ($filesArr as $file) {
                if (is_array($file) && !empty($file['file'])) {
                    $ext = pathinfo($file['file'], PATHINFO_EXTENSION);
                    $fileName = md5($file['file']) . ((!empty($ext)) ? '.' . $ext : '');
                    if (file_exists($this->filesDirectoryPath . $fileName)) {
                        @unlink($this->filesDirectoryPath . $fileName);
                    }
                    do_action('bfp_delete_file', $productId, $file['file']);
                }
            }
        }
    }
}
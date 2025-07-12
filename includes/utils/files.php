<?php
/**
 * File handling functionality for Bandfront Player
 *
 * @package BandfrontPlayer
 * @since 0.1
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * BFP File Handler Class
 */
class BFP_File_Handler {
    
    private $main_plugin;
    private $_files_directory_path;
    private $_files_directory_url;
    
    public function __construct($main_plugin) {
        $this->main_plugin = $main_plugin;
        $this->_createDir();
    }
    
    /**
     * Create directories for file storage
     */
    public function _createDir() {
        // Generate upload dir
        $_files_directory = wp_upload_dir();
        $this->_files_directory_path = rtrim($_files_directory['basedir'], '/') . '/bfp/';
        $this->_files_directory_url = rtrim($_files_directory['baseurl'], '/') . '/bfp/';
        $this->_files_directory_url = preg_replace('/^http(s)?:\/\//', '//', $this->_files_directory_url);
        
        if (!file_exists($this->_files_directory_path)) {
            @mkdir($this->_files_directory_path, 0755);
        }

        if (is_dir($this->_files_directory_path)) {
            if (!file_exists($this->_files_directory_path . '.htaccess')) {
                try {
                    file_put_contents($this->_files_directory_path . '.htaccess', 'Options -Indexes');
                } catch (Exception $err) {}
            }
        }

        if (!file_exists($this->_files_directory_path . 'purchased/')) {
            @mkdir($this->_files_directory_path . 'purchased/', 0755);
        }
    }
    
    /**
     * Clear directory contents
     */
    public function _clearDir($dirPath) {
        try {
            if (empty($dirPath) || !file_exists($dirPath) || !is_dir($dirPath)) {
                return;
            }
            $dirPath = rtrim($dirPath, '\\/') . '/';
            $files = glob($dirPath . '*', GLOB_MARK);
            foreach ($files as $file) {
                if (is_dir($file)) {
                    $this->_clearDir($file);
                } else {
                    unlink($file);
                }
            }
        } catch (Exception $err) {
            return;
        }
    }
    
    /**
     * Get files directory path
     */
    public function get_files_directory_path() {
        return $this->_files_directory_path;
    }
    
    /**
     * Get files directory URL
     */
    public function get_files_directory_url() {
        return $this->_files_directory_url;
    }
    
    /**
     * Delete post-related files and meta data
     */
    public function delete_post($post_id, $demos_only = false, $force = false) {
        $post = get_post($post_id);
        $post_types = $this->main_plugin->_get_post_types();
        if (
            isset($post) &&
            (
                !$force ||
                !in_array($post->post_type, $post_types) ||
                !current_user_can('edit_post', $post_id)
            )
        ) {
            return;
        }

        // Delete truncated version of the audio file
        $this->delete_truncated_files($post_id);

        if (!$demos_only) {
            delete_post_meta($post_id, '_bfp_enable_player');
            delete_post_meta($post_id, '_bfp_show_in');
            delete_post_meta($post_id, '_bfp_merge_in_grouped');
            delete_post_meta($post_id, '_bfp_player_layout');
            delete_post_meta($post_id, '_bfp_player_volume');
            delete_post_meta($post_id, '_bfp_single_player');
            delete_post_meta($post_id, '_bfp_secure_player');
            delete_post_meta($post_id, '_bfp_file_percent');
            delete_post_meta($post_id, '_bfp_player_controls');
            delete_post_meta($post_id, '_bfp_player_title');
            delete_post_meta($post_id, '_bfp_preload');
            delete_post_meta($post_id, '_bfp_play_all');
            delete_post_meta($post_id, '_bfp_loop');
            delete_post_meta($post_id, '_bfp_on_cover');

            delete_post_meta($post_id, '_bfp_playback_counter');
        }

        delete_post_meta($post_id, '_bfp_own_demos');
        delete_post_meta($post_id, '_bfp_direct_own_demos');
        delete_post_meta($post_id, '_bfp_demos_list');

        do_action('bfp_delete_post', $post_id);
    }
    
    /**
     * Delete purchased files based on reset interval
     */
    public function delete_purchased_files() {
        // Use get_state for single value retrieval
        if ($this->main_plugin->get_config()->get_state('_bfp_reset_purchased_interval', 'daily') == 'daily') {
            $this->_clearDir($this->_files_directory_path . 'purchased/');
            $this->_createDir();
        }
    }
    
    /**
     * Clear expired transients to maintain cache
     */
    public function clear_expired_transients() {
        $transient = get_transient('bfp_clear_expired_transients');
        if (!$transient || 24 * 60 * 60 <= time() - intval($transient)) {
            set_transient('bfp_clear_expired_transients', time());
            delete_expired_transients();
        }
    }
    
    /**
     * Delete truncated demo files for a product
     */
    public function delete_truncated_files($product_id) {
        $files_arr = get_post_meta($product_id, '_downloadable_files', true);
        $own_files_arr = get_post_meta($product_id, '_bfp_demos_list', true);
        if (!is_array($files_arr)) {
            $files_arr = array($files_arr);
        }
        if (is_array($own_files_arr) && !empty($own_files_arr)) {
            $files_arr = array_merge($files_arr, $own_files_arr);
        }

        if (!empty($files_arr) && is_array($files_arr)) {
            foreach ($files_arr as $file) {
                if (is_array($file) && !empty($file['file'])) {
                    $ext = pathinfo($file['file'], PATHINFO_EXTENSION);
                    $file_name = md5($file['file']) . ((!empty($ext)) ? '.' . $ext : '');
                    if (file_exists($this->_files_directory_path . $file_name)) {
                        @unlink($this->_files_directory_path . $file_name);
                    }
                    do_action('bfp_delete_file', $product_id, $file['file']);
                }
            }
        }
    }
}

<?php
declare(strict_types=1);

namespace Bandfront\Admin;

use Bandfront\Core\Config;
use Bandfront\Storage\FileManager;
use Bandfront\UI\AdminRenderer;
use Bandfront\Utils\Debug;
use Bandfront\Utils\Cache;

// Set domain for Admin
Debug::domain('admin');

/**
 * Global settings management
 * 
 * @package Bandfront\Admin
 * @since 2.0.0
 */
class Settings {
    
    private Config $config;
    private FileManager $fileManager;
    private AdminRenderer $renderer;
    
    /**
     * Constructor
     */
    public function __construct(Config $config, FileManager $fileManager, AdminRenderer $renderer) {
        $this->config = $config;
        $this->fileManager = $fileManager;
        $this->renderer = $renderer;
    }
    
    /**
     * Register admin menu
     */
    public function registerMenu(): void {
        $result = add_menu_page(
            'Bandfront Player',
            'Bandfront Player',
            'manage_options',
            'bandfront-player-settings',
            [$this, 'renderPage'],
            'dashicons-format-audio',
            30
        );
        
        Debug::log('Settings.php:registerMenu() add_menu_page result', [
            'result' => $result,
            'menu_slug' => 'bandfront-player-settings',
            'admin_url' => admin_url('admin.php?page=bandfront-player-settings')
        ]); // DEBUG-REMOVE
    }

    /**
     * Render settings page
     */
    public function renderPage(): void {
        Debug::log('Settings.php:renderPage', [
            'step' => 'start',
            'action' => 'Rendering Bandfront Player settings page'
        ]); // DEBUG-REMOVE
        
        if (isset($_POST['bfp_nonce']) && 
            wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['bfp_nonce'])), 'bfp_updating_plugin_settings')) {
            Debug::log('Settings.php: Saving global settings', []); // DEBUG-REMOVE
            $messages = $this->saveGlobalSettings();
            
            // Set transient for admin notice
            if (!empty($messages)) {
                Debug::log('Settings.php: Setting admin notice transient', ['messages' => $messages]); // DEBUG-REMOVE
                set_transient('bfp_admin_notice', $messages, 30);
            }
            
            // Redirect to prevent form resubmission
            Debug::log('Settings.php: Redirecting after settings save', []); // DEBUG-REMOVE
            wp_redirect(add_query_arg('settings-updated', 'true', wp_get_referer()));
            exit;
        }

        // Delegate rendering to AdminRenderer
        $this->renderer->renderSettingsPage($this->config, $this->fileManager);
    }
    
    /**
     * Save global settings
     * Made public so Admin can call it for AJAX
     */
    public function saveGlobalSettings(): array {
        Debug::log('Settings.php: Entering saveGlobalSettings()', []); // DEBUG-REMOVE
        $_REQUEST = stripslashes_deep($_REQUEST);
        
        // Track what changed for notifications
        $changes = [];
        $oldSettings = $this->config->getStates([
            '_bfp_audio_engine',
            '_bfp_enable_player',
            '_bfp_play_demos',
            '_bfp_ffmpeg'
        ]);
        
        // Parse form data
        $globalSettings = $this->parseFormData($_REQUEST);
        
        // Handle special actions
        if ($globalSettings['_bfp_apply_to_all_players'] || isset($_REQUEST['_bfp_delete_demos'])) {
            Debug::log('Settings.php: Clearing demo files directory', []); // DEBUG-REMOVE
            $this->fileManager->clearDir($this->fileManager->getFilesDirectoryPath());
        }

        if ($globalSettings['_bfp_apply_to_all_players']) {
            Debug::log('Settings.php: Applying settings to all products', []); // DEBUG-REMOVE
            $this->applySettingsToAllProducts($globalSettings);
        }

        // Save settings
        Debug::log('Settings.php: Updating global settings option', ['globalSettings' => $globalSettings]); // DEBUG-REMOVE
        update_option('bfp_global_settings', $globalSettings);
        $this->config->updateGlobalAttrs($globalSettings);
        do_action('bfp_save_setting');

        // Purge Cache
        Debug::log('Settings.php: Clearing all plugin caches', []); // DEBUG-REMOVE
        Cache::clearAllCaches();
        
        // Build notification message
        return $this->buildNotificationMessage($oldSettings, $globalSettings, $_REQUEST);
    }
    
    /**
     * Parse form data into settings array
     */
    private function parseFormData(array $data): array {
        // Extract all settings from form data
        $settings = [
            '_bfp_require_login' => isset($data['_bfp_require_login']) ? 1 : 0,
            '_bfp_purchased' => isset($data['_bfp_purchased']) ? 1 : 0,
            '_bfp_reset_purchased_interval' => (isset($data['_bfp_reset_purchased_interval']) && 'never' == $data['_bfp_reset_purchased_interval']) ? 'never' : 'daily',
            '_bfp_fade_out' => isset($data['_bfp_fade_out']) ? 1 : 0,
            '_bfp_purchased_times_text' => sanitize_text_field(isset($data['_bfp_purchased_times_text']) ? wp_unslash($data['_bfp_purchased_times_text']) : ''),
            '_bfp_dev_mode' => isset($data['_bfp_dev_mode']) ? 1 : 0,  // Add dev mode
            'enable_db_monitoring' => isset($data['enable_db_monitoring']) ? 1 : 0,  // Add database monitoring
            '_bfp_debug_mode' => isset($data['_bfp_debug_mode']) ? 1 : 0,  // Add debug mode
            '_bfp_ffmpeg' => isset($data['_bfp_ffmpeg']) ? 1 : 0,
            '_bfp_ffmpeg_path' => isset($data['_bfp_ffmpeg_path']) ? sanitize_text_field(wp_unslash($data['_bfp_ffmpeg_path'])) : '',
            '_bfp_ffmpeg_watermark' => isset($data['_bfp_ffmpeg_watermark']) ? sanitize_text_field(wp_unslash($data['_bfp_ffmpeg_watermark'])) : '',
            '_bfp_enable_player' => isset($data['_bfp_enable_player']) ? 1 : 0,
            '_bfp_players_in_cart' => isset($data['_bfp_players_in_cart']) ? true : false,
            '_bfp_player_layout' => $this->parsePlayerLayout($data),
            '_bfp_unified_player' => isset($data['_bfp_unified_player']) ? 1 : 0,
            '_bfp_play_demos' => isset($data['_bfp_play_demos']) ? 1 : 0,
            '_bfp_player_controls' => $this->parsePlayerControls($data),
            '_bfp_demo_duration_percent' => $this->parseFilePercent($data),
            '_bfp_group_cart_control' => isset($data['_bfp_group_cart_control']) ? 1 : 0,
            '_bfp_play_all' => isset($data['_bfp_play_all']) ? 1 : 0,
            '_bfp_loop' => isset($data['_bfp_loop']) ? 1 : 0,
            '_bfp_on_cover' => $this->parseOnCover($data),
            '_bfp_demo_message' => isset($data['_bfp_demo_message']) ? wp_kses_post(wp_unslash($data['_bfp_demo_message'])) : '',
            '_bfp_audio_engine' => $this->parseAudioEngine($data),
            '_bfp_enable_visualizations' => $this->parseVisualizations($data),
            '_bfp_apply_to_all_players' => isset($data['_bfp_apply_to_all_players']) ? 1 : 0,
            // ...additional settings...
        ];
        
        // Handle debug configuration - using the correct form field names
        $debugConfig = [
            'enabled' => isset($data['_bfp_debug']['enabled']) ? true : false,
            'domains' => []
        ];
        
        // Parse debug domains from the nested array structure
        $availableDomains = [
            'admin', 'audio', 'core', 'core-bootstrap', 'core-config', 'core-hooks',
            'db', 'api', 'storage', 'ui', 'utils', 'wordpress-elements', 'woocommerce'
        ];
        
        // Check if domains were submitted in the form
        if (isset($data['_bfp_debug']['domains']) && is_array($data['_bfp_debug']['domains'])) {
            foreach ($availableDomains as $domain) {
                $debugConfig['domains'][$domain] = isset($data['_bfp_debug']['domains'][$domain]) ? true : false;
            }
        } else {
            // All domains disabled if none selected
            $debugConfig['domains'] = [
                'admin' => false,
                'audio' => false,
                'core' => false,
                'core-bootstrap' => false,
                'core-config' => false,
                'core-hooks' => false,
                'db' => false,
                'api' => false,
                'storage' => false,
                'ui' => false,
                'utils' => false,
                'wordpress-elements' => false,
                'woocommerce' => false,
            ];
        }
        
        $settings['_bfp_debug'] = $debugConfig;
        
        // Handle cloud storage settings
        $settings = array_merge($settings, $this->parseCloudSettings($data));
        
        // Handle Google Drive legacy addon
        $this->handleGoogleDriveSettings($data);
        
        return $settings;
    }
    
    /**
     * Parse player layout setting
     */
    private function parsePlayerLayout(array $data): string {
        $playerLayouts = $this->config->getPlayerLayouts();
        $defaultLayout = $this->config->getState('_bfp_player_layout');
        
        if (isset($data['_bfp_player_layout']) && in_array($data['_bfp_player_layout'], $playerLayouts)) {
            return sanitize_text_field(wp_unslash($data['_bfp_player_layout']));
        }
        
        return $defaultLayout;
    }
    
    /**
     * Parse player controls setting
     */
    private function parsePlayerControls(array $data): string {
        $playerControlsList = $this->config->getPlayerControls();
        $defaultControls = $this->config->getState('_bfp_player_controls');
        
        if (isset($data['_bfp_player_controls']) && in_array($data['_bfp_player_controls'], $playerControlsList)) {
            return sanitize_text_field(wp_unslash($data['_bfp_player_controls']));
        }
        
        return $defaultControls;
    }
    
    /**
     * Parse file percent setting
     */
    private function parseFilePercent(array $data): int {
        if (isset($data['_bfp_demo_duration_percent']) && is_numeric($data['_bfp_demo_duration_percent'])) {
            $percent = intval($data['_bfp_demo_duration_percent']);
            return min(max($percent, 0), 100);
        }
        return 0;
    }
    
    /**
     * Parse on cover setting
     */
    private function parseOnCover(array $data): int {
        $playerControls = $this->parsePlayerControls($data);
        if (($playerControls == 'button' || $playerControls == 'default') && isset($data['_bfp_player_on_cover'])) {
            return 1;
        }
        return 0;
    }
    
    /**
     * Parse audio engine setting
     */
    private function parseAudioEngine(array $data): string {
        $audioEngine = 'html5'; // Default to HTML5
        if (isset($data['_bfp_audio_engine']) && 
            in_array($data['_bfp_audio_engine'], ['mediaelement', 'wavesurfer', 'html5'])) {
            $audioEngine = sanitize_text_field(wp_unslash($data['_bfp_audio_engine']));
        }
        return $audioEngine;
    }
    
    /**
     * Parse visualizations setting
     */
    private function parseVisualizations(array $data): int {
        $audioEngine = $this->parseAudioEngine($data);
        if (isset($data['_bfp_enable_visualizations']) && $audioEngine === 'wavesurfer') {
            return 1;
        }
        return 0;
    }
    
    /**
     * Parse cloud storage settings
     */
    private function parseCloudSettings(array $data): array {
        return [
            '_bfp_cloud_active_tab' => isset($data['_bfp_cloud_active_tab']) ? 
                                       sanitize_text_field(wp_unslash($data['_bfp_cloud_active_tab'])) : 'google-drive',
            '_bfp_cloud_dropbox' => [
                'enabled' => isset($data['_bfp_cloud_dropbox_enabled']) ? true : false,
                'access_token' => isset($data['_bfp_cloud_dropbox_token']) ? 
                                 sanitize_text_field(wp_unslash($data['_bfp_cloud_dropbox_token'])) : '',
                'folder_path' => isset($data['_bfp_cloud_dropbox_folder']) ? 
                                sanitize_text_field(wp_unslash($data['_bfp_cloud_dropbox_folder'])) : '/bandfront-demos',
            ],
            // ...S3 and Azure settings...
        ];
    }
    
    /**
     * Handle Google Drive settings
     */
    private function handleGoogleDriveSettings(array $data): void {
        $bfpDrive = isset($data['_bfp_drive']) ? 1 : 0;
        $bfpDriveApiKey = isset($data['_bfp_drive_api_key']) ? 
                            sanitize_text_field(wp_unslash($data['_bfp_drive_api_key'])) : '';
        
        // Handle file upload
        if (!empty($_FILES['_bfp_drive_key']) && $_FILES['_bfp_drive_key']['error'] == UPLOAD_ERR_OK) {
            Debug::log('Settings.php: Handling Google Drive OAuth file upload', []); // DEBUG-REMOVE
            $this->fileManager->handleOAuthFileUpload($_FILES['_bfp_drive_key']);
        }
        
        // Save API key if provided
        if ($bfpDriveApiKey !== '') {
            Debug::log('Settings.php: Updating Google Drive API key', []); // DEBUG-REMOVE
            update_option('_bfp_drive_api_key', $bfpDriveApiKey);
        }
    }
    
    /**
     * Build notification message
     */
    private function buildNotificationMessage(array $oldSettings, array $newSettings, array $request): array {
        $messages = [];
        
        // Check what changed
        if ($oldSettings['_bfp_audio_engine'] !== $newSettings['_bfp_audio_engine']) {
            $messages[] = sprintf(__('Audio engine changed to %s', 'bandfront-player'), ucfirst($newSettings['_bfp_audio_engine']));
        }
        
        if ($oldSettings['_bfp_enable_player'] != $newSettings['_bfp_enable_player']) {
            $messages[] = $newSettings['_bfp_enable_player'] ? 
                __('Players enabled on all products', 'bandfront-player') : 
                __('Players disabled on all products', 'bandfront-player');
        }
        
        // ...additional change checks...
        
        if (isset($request['_bfp_delete_demos'])) {
            $messages[] = __('Demo files have been deleted', 'bandfront-player');
        }
        
        if ($newSettings['_bfp_apply_to_all_players']) {
            $messages[] = __('Settings applied to all products', 'bandfront-player');
        }
        
        // Return appropriate message
        if (!empty($messages)) {
            return [
                'message' => __('Settings saved successfully!', 'bandfront-player') . ' ' . implode('. ', $messages) . '.',
                'type' => 'success'
            ];
        } else {
            return [
                'message' => __('Settings saved successfully!', 'bandfront-player'),
                'type' => 'success'
            ];
        }
    }
    
    /**
     * Apply settings to all products
     */
    private function applySettingsToAllProducts(array $globalSettings): void {
        Debug::log('Settings.php: Applying settings to all products', ['globalSettings' => $globalSettings]); // DEBUG-REMOVE
        
        $productsIds = [
            'post_type' => $this->config->getPostTypes(),
            'numberposts' => -1,
            'post_status' => ['publish', 'pending', 'draft', 'future'],
            'fields' => 'ids',
            'cache_results' => false,
        ];

        $products = get_posts($productsIds);
        Debug::log('Settings.php: Found products', ['count' => count($products)]); // DEBUG-REMOVE
        
        foreach ($products as $productId) {
            // Delete meta keys for settings that are now global-only
            delete_post_meta($productId, '_bfp_player_layout');
            delete_post_meta($productId, '_bfp_player_controls');
            delete_post_meta($productId, '_bfp_on_cover');
            
            // Update the settings that can still be overridden
            update_post_meta($productId, '_bfp_enable_player', $globalSettings['_bfp_enable_player']);
            update_post_meta($productId, '_bfp_group_cart_control', $globalSettings['_bfp_group_cart_control']);
            update_post_meta($productId, '_bfp_unified_player', $globalSettings['_bfp_unified_player']);
            update_post_meta($productId, '_bfp_play_all', $globalSettings['_bfp_play_all']);
            update_post_meta($productId, '_bfp_loop', $globalSettings['_bfp_loop']);
            update_post_meta($productId, '_bfp_play_demos', $globalSettings['_bfp_play_demos']);
            update_post_meta($productId, '_bfp_demo_duration_percent', $globalSettings['_bfp_demo_duration_percent']);

            $this->config->clearProductAttrsCache($productId);
        }
    }
    
    /**
     * Show admin notices
     */
    public function showAdminNotices(): void {
        Debug::log('Settings.php:showAdminNotices', [
            'action' => 'Checking for Bandfront Player admin notices'
        ]); // DEBUG-REMOVE
        
        // Only show on our settings page
        if (!isset($_GET['page']) || $_GET['page'] !== 'bandfront-player-settings') {
            Debug::log('Settings.php: Not on Bandfront Player settings page, skipping notices', []); // DEBUG-REMOVE
            return;
        }
        
        // Check for transient notice first (has more details)
        $notice = get_transient('bfp_admin_notice');
        if ($notice) {
            Debug::log('Settings.php: Showing transient admin notice', ['notice' => $notice]); // DEBUG-REMOVE
            delete_transient('bfp_admin_notice');
            $this->renderer->renderAdminNotice($notice);
            return;
        }
        
        // Only show generic notice if no transient notice exists
        if (isset($_GET['settings-updated']) && $_GET['settings-updated'] === 'true') {
            $this->renderer->renderAdminNotice([
                'message' => __('Settings saved successfully!', 'bandfront-player'),
                'type' => 'success'
            ]);
        }
    }
}

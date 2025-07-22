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
            'B∀ΠDFЯΘΠT',
            'B∀ΠDFЯΘΠT',
            'manage_options',
            'bandfront-player-settings',
            [$this, 'renderPage'],
            'data:image/svg+xml;base64,' . base64_encode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><text x="10" y="15" text-anchor="middle" font-size="16" fill="#8e44ad">𒆙</text></svg>'),
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
            '_bfp_demos',
            '_bfp_ffmpeg'
        ]);
        
        // Parse form data
        Debug::log('Settings.php: Raw form data', ['REQUEST' => $_REQUEST]); // DEBUG-REMOVE
        $globalSettings = $this->parseFormData($_REQUEST);
        Debug::log('Settings.php: Parsed global settings', ['globalSettings' => $globalSettings]); // DEBUG-REMOVE
        
        // Handle special actions
        if ($globalSettings['_bfp_apply_to_all_players'] || isset($_REQUEST['_bfp_delete_demos'])) {
            Debug::log('Settings.php: Clearing demo files directory', []); // DEBUG-REMOVE
            $this->fileManager->clearDir($this->fileManager->getFilesDirectoryPath());
        }

        if ($globalSettings['_bfp_apply_to_all_players']) {
            Debug::log('Settings.php: Applying settings to all products', []); // DEBUG-REMOVE
        error_log("=== SAVING TO DATABASE ===");
        error_log("_bfp_player_on_cover value being saved: " . $globalSettings["_bfp_player_on_cover"]);
        
            $this->applySettingsToAllProducts($globalSettings);
        }

        // Save settings
        Debug::log('Settings.php: About to save to database', ['globalSettings' => $globalSettings]); // DEBUG-REMOVE
        $saveResult = update_option('bfp_global_settings', $globalSettings);
        Debug::log('Settings.php: Database save result', ['result' => $saveResult]); // DEBUG-REMOVE
        $this->config->updateGlobalAttrs($globalSettings);
        
        // Handle demo creation directly (internal component communication)
        $this->onDemoSettingsSaved();
        
        // Fire hook for external plugins/themes (external API)
        do_action('bfp_save_setting', $globalSettings, $this->config);

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
            '_bfp_purchased_times_text' => sanitize_text_field(isset($data['_bfp_purchased_times_text']) ? wp_unslash($data['_bfp_purchased_times_text']) : ''),
            '_bfp_dev_mode' => isset($data['_bfp_dev_mode']) ? 1 : 0,  // Add dev mode
            '_bfp_sndloop_mode' => isset($data['_bfp_sndloop_mode']) ? 1 : 0,  // Add sndloop mode
            '_bfp_sndloop_discovery' => isset($data['_bfp_sndloop_discovery']) ? 1 : 0,  // SNDLOOP discovery
            '_bfp_sndloop_send_products' => isset($data['_bfp_sndloop_send_products']) ? 1 : 0,  // SNDLOOP send products
            '_bfp_sndloop_send_merch' => isset($data['_bfp_sndloop_send_merch']) ? 1 : 0,  // SNDLOOP send merch
            '_bfp_onload' => isset($data['_bfp_onload']) ? 1 : 0,  // Onload troubleshooting
            'enable_db_monitoring' => isset($data['enable_db_monitoring']) ? 1 : 0,  // Add database monitoring
            '_bfp_debug_mode' => isset($data['_bfp_debug_mode']) ? 1 : 0,  // Add debug mode
            
            // Simple flat demos array - much cleaner!
            '_bfp_demos' => [
                'enabled' => isset($data['_bfp_demos']['enabled']) ? true : false,
                'duration_percent' => isset($data['_bfp_demos']['duration_percent']) ? 
                    max(1, min(100, (int) $data['_bfp_demos']['duration_percent'])) : 50,
                'demo_fade' => isset($data['_bfp_demos']['demo_fade']) ? 
                    max(0, min(10, (float) $data['_bfp_demos']['demo_fade'])) : 0,
                'demo_filetype' => isset($data['_bfp_demos']['demo_filetype']) && 
                    in_array($data['_bfp_demos']['demo_filetype'], ['mp3', 'wav', 'ogg', 'mp4', 'm4a', 'flac']) ? 
                    $data['_bfp_demos']['demo_filetype'] : 'mp3',
                'demo_start_time' => isset($data['_bfp_demos']['demo_start_time']) ? 
                    max(0, min(50, (int) $data['_bfp_demos']['demo_start_time'])) : 0,
                'message' => isset($data['_bfp_demos']['message']) ? 
                    sanitize_textarea_field(wp_unslash($data['_bfp_demos']['message'])) : '',
            ],
            
            '_bfp_ffmpeg' => isset($data['_bfp_ffmpeg']) ? 1 : 0,
            '_bfp_ffmpeg_path' => isset($data['_bfp_ffmpeg_path']) ? sanitize_text_field(wp_unslash($data['_bfp_ffmpeg_path'])) : '',
            '_bfp_ffmpeg_watermark' => isset($data['_bfp_ffmpeg_watermark']) ? sanitize_text_field(wp_unslash($data['_bfp_ffmpeg_watermark'])) : '',
            '_bfp_enable_player' => isset($data['_bfp_enable_player']) ? 1 : 0,
            '_bfp_players_in_cart' => isset($data['_bfp_players_in_cart']) ? true : false,
            '_bfp_player_layout' => $this->parsePlayerLayout($data),
            '_bfp_button_theme' => $this->parseButtonTheme($data),
            '_bfp_unified_player' => isset($data['_bfp_unified_player']) ? 1 : 0,
            '_bfp_player_controls' => $this->parsePlayerControls($data),
            '_bfp_group_cart_control' => isset($data['_bfp_group_cart_control']) ? 1 : 0,
            '_bfp_play_all' => isset($data['_bfp_play_all']) ? 1 : 0,
            '_bfp_loop' => isset($data['_bfp_loop']) ? 1 : 0,
            '_bfp_player_on_cover' => isset($data['_bfp_player_on_cover']) ? 1 : 0,
            '_bfp_show_purchasers' => isset($data['_bfp_show_purchasers']) ? 1 : 0,
            '_bfp_show_navigation_buttons' => isset($data['_bfp_show_navigation_buttons']) ? 1 : 0,
            '_bfp_max_purchasers_display' => isset($data['_bfp_max_purchasers_display']) ? intval($data['_bfp_max_purchasers_display']) : 10,
            '_bfp_audio_engine' => $this->parseAudioEngine($data),
            '_bfp_enable_visualizations' => $this->parseVisualizations($data),
            '_bfp_apply_to_all_players' => isset($data['_bfp_apply_to_all_players']) ? 1 : 0,
            '_bfp_onload' => isset($data['_bfp_onload']) ? 1 : 0,
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
     * Parse button theme
     */
    private function parseButtonTheme(array $data): string {
        $buttonThemes = ['dark', 'light', 'custom']; // Same options as player layout
        $defaultTheme = $this->config->getState('_bfp_button_theme');
        
        if (isset($data['_bfp_button_theme']) && in_array($data['_bfp_button_theme'], $buttonThemes)) {
            return sanitize_text_field(wp_unslash($data['_bfp_button_theme']));
        }
        
        return $defaultTheme;
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
            delete_post_meta($productId, '_bfp_player_on_cover');
            
            // Update the settings that can still be overridden
            update_post_meta($productId, '_bfp_enable_player', $globalSettings['_bfp_enable_player']);
            update_post_meta($productId, '_bfp_group_cart_control', $globalSettings['_bfp_group_cart_control']);
            update_post_meta($productId, '_bfp_unified_player', $globalSettings['_bfp_unified_player']);
            update_post_meta($productId, '_bfp_play_all', $globalSettings['_bfp_play_all']);
            update_post_meta($productId, '_bfp_loop', $globalSettings['_bfp_loop']);
            
            // For demos, only update global settings while preserving product-specific ones
            $existingDemos = get_post_meta($productId, '_bfp_demos', true) ?: [];
            if (!is_array($existingDemos)) {
                $existingDemos = [];
            }
            
            // Preserve existing product settings, only update global section
            $updatedDemos = $existingDemos;
            if (isset($globalSettings['_bfp_demos']['global'])) {
                $updatedDemos['global'] = $globalSettings['_bfp_demos']['global'];
            }
            
            update_post_meta($productId, '_bfp_demos', $updatedDemos);

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

    public function onDemoSettingsSaved(): void {
        $demosConfig = $this->config->getState('_bfp_demos', ['enabled' => false]);
        $demosEnabled = $demosConfig['enabled'] ?? false;
        
        if ($demosEnabled) {
            $bootstrap = \Bandfront\Core\Bootstrap::getInstance();
            $demoCreator = $bootstrap->getComponent('demo_creator');
            
            if ($demoCreator) {
                $demoCount = $demoCreator->createDemosForAllProducts();
                Debug::log('Settings: Demo creation triggered', ['demos_created' => $demoCount]);
            }
        }
    }
}

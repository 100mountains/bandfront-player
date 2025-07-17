<?php
declare(strict_types=1);

namespace Bandfront\Admin;

use Bandfront\Core\Config;
use Bandfront\Storage\FileManager;
use Bandfront\UI\Renderer;  // Fixed: was Bandfront\Renderer
use Bandfront\Utils\Debug;
use Bandfront\Utils\Cache;

/**
 * Admin functionality
 * 
 * @package Bandfront\Admin
 * @since 2.0.0
 */
class Admin {
    
    private Config $config;
    private FileManager $fileManager;
    private Renderer $renderer;
    
    /**
     * Constructor - accepts only needed dependencies
     */
    public function __construct(Config $config, FileManager $fileManager, Renderer $renderer) {
        Debug::log('Admin.php:init', [
            'step' => 'start',
            'config' => get_class($config),
            'fileManager' => get_class($fileManager),
            'renderer' => get_class($renderer)
        ]); // DEBUG-REMOVE

        $this->config = $config;
        $this->fileManager = $fileManager;
        $this->renderer = $renderer;
        
        // Load view templates that may register their own hooks
        $this->loadViewTemplates();

        Debug::log('Admin.php:init', [
            'step' => 'end',
            'views_loaded' => true
        ]); // DEBUG-REMOVE
    }
    
    /**
     * Load view templates that register hooks
     */
    private function loadViewTemplates(): void {
        Debug::log('Admin.php:35 Entering loadViewTemplates()', []); // DEBUG-REMOVE
        // Only load in admin area
        if (!is_admin()) {
            Debug::log('Admin.php:38 Not in admin area, skipping loadViewTemplates()', []); // DEBUG-REMOVE
            return;
        }
        
        // Include audio engine settings template (registers hooks)
        Debug::log('Admin.php:43 Including audio-engine-settings.php', []); // DEBUG-REMOVE
        require_once plugin_dir_path(dirname(dirname(__FILE__))) . 'templates/audio-engine-settings.php';
        
        // Future: Add other view templates here as needed
        Debug::log('Admin.php:47 Exiting loadViewTemplates()', []); // DEBUG-REMOVE
    }

    /**
     * Admin initialization
     */
    public function adminInit(): void {
        Debug::log('Admin.php:adminInit', [
            'step' => 'setup',
            'action' => 'Initializing Bandfront Player admin',
            'woocommerce_installed' => class_exists('woocommerce')
        ]); // DEBUG-REMOVE

        // Check if WooCommerce is installed or not
        if (!class_exists('woocommerce')) {
            Debug::log('Admin.php:71 WooCommerce not installed, exiting adminInit()', []); // DEBUG-REMOVE
            return;
        }

        Debug::log('Admin.php:75 Clearing expired transients', []); // DEBUG-REMOVE
        $this->fileManager->clearExpiredTransients();

        add_meta_box(
            'bfp_woocommerce_metabox', 
            __('Bandfront Player', 'bandfront-player'), 
            [$this, 'woocommercePlayerSettings'], 
            $this->config->getPostTypes(), 
            'normal'
        );

        Debug::log('Admin.php:adminInit', [
            'step' => 'metabox',
            'action' => 'Bandfront Player metabox added to product edit screen'
        ]); // DEBUG-REMOVE

        Debug::log('Admin.php:adminInit', [
            'step' => 'done',
            'action' => 'Bandfront Player admin initialization complete'
        ]); // DEBUG-REMOVE
    }
    
    /**
     * Add admin menu
     */
    public function menuLinks(): void {
        Debug::log('Admin.php:menuLinks() called', [
            'current_user_can' => current_user_can('manage_options'),
            'is_admin' => is_admin(),
            'current_hook' => current_action(),
            'did_action_admin_menu' => did_action('admin_menu')
        ]); // DEBUG-REMOVE
        
        $result = add_menu_page(
            'Bandfront Player',
            'Bandfront Player',
            'manage_options',
            'bandfront-player-settings',
            [$this, 'settingsPage'],
            'dashicons-format-audio',
            30
        );
        
        Debug::log('Admin.php:menuLinks() add_menu_page result', [
            'result' => $result,
            'menu_slug' => 'bandfront-player-settings',
            'admin_url' => admin_url('admin.php?page=bandfront-player-settings')
        ]); // DEBUG-REMOVE
    }

    /**
     * Settings page callback
     */
    public function settingsPage(): void {
        Debug::log('Admin.php:settingsPage', [
            'step' => 'start',
            'action' => 'Rendering Bandfront Player settings page'
        ]); // DEBUG-REMOVE
        if (isset($_POST['bfp_nonce']) && 
            wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['bfp_nonce'])), 'bfp_updating_plugin_settings')) {
            Debug::log('Admin.php:146 Saving global settings from settingsPage()', []); // DEBUG-REMOVE
            $messages = $this->saveGlobalSettings();
            
            // Set transient for admin notice
            if (!empty($messages)) {
                Debug::log('Admin.php:151 Setting admin notice transient', ['messages' => $messages]); // DEBUG-REMOVE
                set_transient('bfp_admin_notice', $messages, 30);
            }
            
            // Redirect to prevent form resubmission
            Debug::log('Admin.php:156 Redirecting after settings save', []); // DEBUG-REMOVE
            wp_redirect(add_query_arg('settings-updated', 'true', wp_get_referer()));
            exit;
        }

        echo '<div class="wrap">';
        Debug::log('Admin.php:162 Including global-admin-options.php', []); // DEBUG-REMOVE
        include_once plugin_dir_path(dirname(dirname(__FILE__))) . 'templates/global-admin-options.php';
        echo '</div>';
        Debug::log('Admin.php:settingsPage', [
            'step' => 'include',
            'action' => 'Included Bandfront Player global-admin-options.php'
        ]); // DEBUG-REMOVE
        Debug::log('Admin.php:settingsPage', [
            'step' => 'end',
            'action' => 'Finished rendering Bandfront Player settings page'
        ]); // DEBUG-REMOVE
    }
    
    /**
     * Settings page URL
     */
    public function settingsPageUrl(): string {
        Debug::log('Admin.php:171 Getting settings page URL', []); // DEBUG-REMOVE
        return admin_url('options-general.php?page=bandfront-player-settings');
    }

    /**
     * Save global settings
     */
    private function saveGlobalSettings(): array {
        Debug::log('Admin.php:177 Entering saveGlobalSettings()', []); // DEBUG-REMOVE
        $_REQUEST = stripslashes_deep($_REQUEST);
        
        // Track what changed for notifications
        $changes = [];
        $oldSettings = $this->config->getStates([
            '_bfp_audio_engine',
            '_bfp_enable_player',
            '_bfp_secure_player',
            '_bfp_ffmpeg'
        ]);
        Debug::log('Admin.php:185 Loaded old settings', ['oldSettings' => $oldSettings]); // DEBUG-REMOVE
        
        // Save the player settings
        $registeredOnly = isset($_REQUEST['_bfp_registered_only']) ? 1 : 0;
        $purchased = isset($_REQUEST['_bfp_purchased']) ? 1 : 0;
        $resetPurchasedInterval = (isset($_REQUEST['_bfp_reset_purchased_interval']) && 'never' == $_REQUEST['_bfp_reset_purchased_interval']) ? 'never' : 'daily';
        $fadeOut = isset($_REQUEST['_bfp_fade_out']) ? 1 : 0;
        $purchasedTimesText = sanitize_text_field(isset($_REQUEST['_bfp_purchased_times_text']) ? wp_unslash($_REQUEST['_bfp_purchased_times_text']) : '');
        $ffmpeg = isset($_REQUEST['_bfp_ffmpeg']) ? 1 : 0;
        $ffmpegPath = isset($_REQUEST['_bfp_ffmpeg_path']) ? sanitize_text_field(wp_unslash($_REQUEST['_bfp_ffmpeg_path'])) : '';
        $ffmpegWatermark = isset($_REQUEST['_bfp_ffmpeg_watermark']) ? sanitize_text_field(wp_unslash($_REQUEST['_bfp_ffmpeg_watermark'])) : '';
        
        if (!empty($ffmpegPath)) {
            $ffmpegPath = str_replace('\\', '/', $ffmpegPath);
            $ffmpegPath = preg_replace('/(\/)+/', '/', $ffmpegPath);
        }

        $troubleshootDefaultExtension = isset($_REQUEST['_bfp_default_extension']) ? true : false;
        $forceMainPlayerInTitle = isset($_REQUEST['_bfp_force_main_player_in_title']) ? 1 : 0;
        $iosControls = isset($_REQUEST['_bfp_ios_controls']) ? true : false;
        $troubleshootOnload = isset($_REQUEST['_bfp_onload']) ? true : false;
        $disable302 = isset($_REQUEST['_bfp_disable_302']) ? 1 : 0;

        $enablePlayer = isset($_REQUEST['_bfp_enable_player']) ? 1 : 0;
        $playersInCart = isset($_REQUEST['_bfp_players_in_cart']) ? true : false;
        
        $playerLayouts = $this->config->getPlayerLayouts();
        $defaultLayout = $this->config->getState('_bfp_player_layout');
        $playerStyle = (isset($_REQUEST['_bfp_player_layout']) && in_array($_REQUEST['_bfp_player_layout'], $playerLayouts)) ? 
                        sanitize_text_field(wp_unslash($_REQUEST['_bfp_player_layout'])) : $defaultLayout;
        
        $singlePlayer = isset($_REQUEST['_bfp_single_player']) ? 1 : 0;
        $securePlayer = isset($_REQUEST['_bfp_secure_player']) ? 1 : 0;
        $filePercent = (isset($_REQUEST['_bfp_file_percent']) && is_numeric($_REQUEST['_bfp_file_percent'])) ? 
                        intval($_REQUEST['_bfp_file_percent']) : 0;
        $filePercent = min(max($filePercent, 0), 100);
        
        $playerControlsList = $this->config->getPlayerControls();
        $defaultControls = $this->config->getState('_bfp_player_controls');
        $playerControls = (isset($_REQUEST['_bfp_player_controls']) && in_array($_REQUEST['_bfp_player_controls'], $playerControlsList)) ? 
                           sanitize_text_field(wp_unslash($_REQUEST['_bfp_player_controls'])) : $defaultControls;

        $onCover = (('button' == $playerControls || 'default' == $playerControls) && isset($_REQUEST['_bfp_player_on_cover'])) ? 1 : 0;

        $mergeGrouped = isset($_REQUEST['_bfp_merge_in_grouped']) ? 1 : 0;
        $playAll = isset($_REQUEST['_bfp_play_all']) ? 1 : 0;
        $loop = isset($_REQUEST['_bfp_loop']) ? 1 : 0;
        $volume = (isset($_REQUEST['_bfp_player_volume']) && is_numeric($_REQUEST['_bfp_player_volume'])) ? 
                  floatval($_REQUEST['_bfp_player_volume']) : 1;

        $message = isset($_REQUEST['_bfp_message']) ? wp_kses_post(wp_unslash($_REQUEST['_bfp_message'])) : '';
        $applyToAllPlayers = isset($_REQUEST['_bfp_apply_to_all_players']) ? 1 : 0;

        // FIXED: Audio engine handling - now includes html5
        $audioEngine = 'html5'; // Default to HTML5
        if (isset($_REQUEST['_bfp_audio_engine']) && 
            in_array($_REQUEST['_bfp_audio_engine'], ['mediaelement', 'wavesurfer', 'html5'])) {
            $audioEngine = sanitize_text_field(wp_unslash($_REQUEST['_bfp_audio_engine']));
        }
        
        $enableVisualizations = 0;
        if (isset($_REQUEST['_bfp_enable_visualizations']) && 
            $audioEngine === 'wavesurfer') {
            $enableVisualizations = 1;
        }

        // Cloud Storage Settings
        $cloudActiveTab = isset($_REQUEST['_bfp_cloud_active_tab']) ? 
                           sanitize_text_field(wp_unslash($_REQUEST['_bfp_cloud_active_tab'])) : 'google-drive';
        
        // Handle cloud storage settings from the form
        $cloudDropbox = [
            'enabled' => isset($_REQUEST['_bfp_cloud_dropbox_enabled']) ? true : false,
            'access_token' => isset($_REQUEST['_bfp_cloud_dropbox_token']) ? 
                             sanitize_text_field(wp_unslash($_REQUEST['_bfp_cloud_dropbox_token'])) : '',
            'folder_path' => isset($_REQUEST['_bfp_cloud_dropbox_folder']) ? 
                            sanitize_text_field(wp_unslash($_REQUEST['_bfp_cloud_dropbox_folder'])) : '/bandfront-demos',
        ];
        
        $cloudS3 = [
            'enabled' => isset($_REQUEST['_bfp_cloud_s3_enabled']) ? true : false,
            'access_key' => isset($_REQUEST['_bfp_cloud_s3_access_key']) ? 
                           sanitize_text_field(wp_unslash($_REQUEST['_bfp_cloud_s3_access_key'])) : '',
            'secret_key' => isset($_REQUEST['_bfp_cloud_s3_secret_key']) ? 
                           sanitize_text_field(wp_unslash($_REQUEST['_bfp_cloud_s3_secret_key'])) : '',
            'bucket' => isset($_REQUEST['_bfp_cloud_s3_bucket']) ? 
                       sanitize_text_field(wp_unslash($_REQUEST['_bfp_cloud_s3_bucket'])) : '',
            'region' => isset($_REQUEST['_bfp_cloud_s3_region']) ? 
                       sanitize_text_field(wp_unslash($_REQUEST['_bfp_cloud_s3_region'])) : 'us-east-1',
            'path_prefix' => isset($_REQUEST['_bfp_cloud_s3_path']) ? 
                            sanitize_text_field(wp_unslash($_REQUEST['_bfp_cloud_s3_path'])) : 'bandfront-demos/',
        ];
        
        $cloudAzure = [
            'enabled' => isset($_REQUEST['_bfp_cloud_azure_enabled']) ? true : false,
            'account_name' => isset($_REQUEST['_bfp_cloud_azure_account']) ? 
                             sanitize_text_field(wp_unslash($_REQUEST['_bfp_cloud_azure_account'])) : '',
            'account_key' => isset($_REQUEST['_bfp_cloud_azure_key']) ? 
                            sanitize_text_field(wp_unslash($_REQUEST['_bfp_cloud_azure_key'])) : '',
            'container' => isset($_REQUEST['_bfp_cloud_azure_container']) ? 
                          sanitize_text_field(wp_unslash($_REQUEST['_bfp_cloud_azure_container'])) : '',
            'path_prefix' => isset($_REQUEST['_bfp_cloud_azure_path']) ? 
                            sanitize_text_field(wp_unslash($_REQUEST['_bfp_cloud_azure_path'])) : 'bandfront-demos/',
        ];

        // Handle Google Drive settings from the legacy addon
        $bfpDrive = isset($_REQUEST['_bfp_drive']) ? 1 : 0;
        $bfpDriveApiKey = isset($_REQUEST['_bfp_drive_api_key']) ? 
                            sanitize_text_field(wp_unslash($_REQUEST['_bfp_drive_api_key'])) : '';
        
        // Handle Google Drive OAuth file upload
        if (!empty($_FILES['_bfp_drive_key']) && $_FILES['_bfp_drive_key']['error'] == UPLOAD_ERR_OK) {
            Debug::log('Admin.php:246 Handling Google Drive OAuth file upload', []); // DEBUG-REMOVE
            $this->fileManager->handleOAuthFileUpload($_FILES['_bfp_drive_key']);
        } else {
            // Preserve existing drive key if no new file uploaded
            $existingCloudSettings = get_option('_bfp_cloud_drive_addon', []);
            if ($bfpDrive && isset($existingCloudSettings['_bfp_drive_key'])) {
                Debug::log('Admin.php:252 Preserving existing Google Drive key', []); // DEBUG-REMOVE
                $cloudDriveAddon = [
                    '_bfp_drive' => $bfpDrive,
                    '_bfp_drive_key' => $existingCloudSettings['_bfp_drive_key']
                ];
                update_option('_bfp_cloud_drive_addon', $cloudDriveAddon);
            } elseif (!$bfpDrive) {
                // If unchecked, clear the settings
                Debug::log('Admin.php:258 Deleting Google Drive addon option', []); // DEBUG-REMOVE
                delete_option('_bfp_cloud_drive_addon');
            }
        }
        
        // Save the Google Drive API key separately
        if ($bfpDriveApiKey !== '') {
            Debug::log('Admin.php:264 Updating Google Drive API key', []); // DEBUG-REMOVE
            update_option('_bfp_drive_api_key', $bfpDriveApiKey);
        }

        $globalSettings = [
            '_bfp_registered_only' => $registeredOnly,
            '_bfp_purchased' => $purchased,
            '_bfp_reset_purchased_interval' => $resetPurchasedInterval,
            '_bfp_fade_out' => $fadeOut,
            '_bfp_purchased_times_text' => $purchasedTimesText,
            '_bfp_ffmpeg' => $ffmpeg,
            '_bfp_ffmpeg_path' => $ffmpegPath,
            '_bfp_ffmpeg_watermark' => $ffmpegWatermark,
            '_bfp_enable_player' => $enablePlayer,
            '_bfp_players_in_cart' => $playersInCart,
            '_bfp_player_layout' => $playerStyle,
            '_bfp_single_player' => $singlePlayer,
            '_bfp_secure_player' => $securePlayer,
            '_bfp_player_controls' => $playerControls,
            '_bfp_file_percent' => $filePercent,
            '_bfp_merge_in_grouped' => $mergeGrouped,
            '_bfp_play_all' => $playAll,
            '_bfp_loop' => $loop,
            '_bfp_on_cover' => $onCover,
            '_bfp_message' => $message,
            '_bfp_default_extension' => $troubleshootDefaultExtension,
            '_bfp_force_main_player_in_title' => $forceMainPlayerInTitle,
            '_bfp_ios_controls' => $iosControls,
            '_bfp_onload' => $troubleshootOnload,
            '_bfp_disable_302' => $disable302,
            '_bfp_analytics_integration' => isset($_REQUEST['_bfp_analytics_integration']) ? sanitize_text_field(wp_unslash($_REQUEST['_bfp_analytics_integration'])) : 'ua',
            '_bfp_analytics_property' => isset($_REQUEST['_bfp_analytics_property']) ? sanitize_text_field(wp_unslash($_REQUEST['_bfp_analytics_property'])) : '',
            '_bfp_analytics_api_secret' => isset($_REQUEST['_bfp_analytics_api_secret']) ? sanitize_text_field(wp_unslash($_REQUEST['_bfp_analytics_api_secret'])) : '',
            '_bfp_apply_to_all_players' => $applyToAllPlayers,
            '_bfp_audio_engine' => $audioEngine,
            '_bfp_enable_visualizations' => $enableVisualizations,
            // Add cloud storage settings
            '_bfp_cloud_active_tab' => $cloudActiveTab,
            '_bfp_cloud_dropbox' => $cloudDropbox,
            '_bfp_cloud_s3' => $cloudS3,
            '_bfp_cloud_azure' => $cloudAzure,
        ];

        if ($applyToAllPlayers || isset($_REQUEST['_bfp_delete_demos'])) {
            Debug::log('Admin.php:292 Clearing demo files directory', []); // DEBUG-REMOVE
            $this->fileManager->clearDir($this->fileManager->getFilesDirectoryPath());
        }

        if ($applyToAllPlayers) {
            Debug::log('Admin.php:297 Applying settings to all products', []); // DEBUG-REMOVE
            $this->applySettingsToAllProducts($globalSettings);
        }

        Debug::log('Admin.php:301 Updating global settings option', ['globalSettings' => $globalSettings]); // DEBUG-REMOVE
        update_option('bfp_global_settings', $globalSettings);
        $this->config->updateGlobalAttrs($globalSettings);
        do_action('bfp_save_setting');

        // Purge Cache using new cache manager
        Debug::log('Admin.php:307 Clearing all plugin caches', []); // DEBUG-REMOVE
        Cache::clearAllCaches();
        
        // Build notification message
        $messages = [];
        
        // Check what changed
        if ($oldSettings['_bfp_audio_engine'] !== $audioEngine) {
            $messages[] = sprintf(__('Audio engine changed to %s', 'bandfront-player'), ucfirst($audioEngine));
        }
        
        if ($oldSettings['_bfp_enable_player'] != $enablePlayer) {
            $messages[] = $enablePlayer ? 
                __('Players enabled on all products', 'bandfront-player') : 
                __('Players disabled on all products', 'bandfront-player');
        }
        
        if ($oldSettings['_bfp_secure_player'] != $securePlayer) {
            $messages[] = $securePlayer ? 
                __('File truncation enabled - demo files will be created', 'bandfront-player') : 
                __('File truncation disabled - full files will be played', 'bandfront-player');
        }
        
        if ($oldSettings['_bfp_ffmpeg'] != $ffmpeg) {
            $messages[] = $ffmpeg ? 
                __('FFmpeg enabled for demo creation', 'bandfront-player') : 
                __('FFmpeg disabled', 'bandfront-player');
        }
        
        if (isset($_REQUEST['_bfp_delete_demos'])) {
            $messages[] = __('Demo files have been deleted', 'bandfront-player');
        }
        
        if ($applyToAllPlayers) {
            $messages[] = __('Settings applied to all products', 'bandfront-player');
        }
        
        // Return appropriate message
        if (!empty($messages)) {
            Debug::log('Admin.php:334 Returning success message with details', ['messages' => $messages]); // DEBUG-REMOVE
            return [
                'message' => __('Settings saved successfully!', 'bandfront-player') . ' ' . implode('. ', $messages) . '.',
                'type' => 'success'
            ];
        } else {
            Debug::log('Admin.php:339 Returning generic success message', []); // DEBUG-REMOVE
            return [
                'message' => __('Settings saved successfully!', 'bandfront-player'),
                'type' => 'success'
            ];
        }
    }
    
    /**
     * Apply settings to all products - REFACTORED
     */
    private function applySettingsToAllProducts(array $globalSettings): void {
        Debug::log('Admin.php:349 Applying settings to all products', ['globalSettings' => $globalSettings]); // DEBUG-REMOVE
        $productsIds = [
            'post_type' => $this->config->getPostTypes(),
            'numberposts' => -1,
            'post_status' => ['publish', 'pending', 'draft', 'future'],
            'fields' => 'ids',
            'cache_results' => false,
        ];

        $products = get_posts($productsIds);
        Debug::log('Admin.php:358 Found products', ['count' => count($products)]); // DEBUG-REMOVE
        foreach ($products as $productId) {
            // Delete meta keys for settings that are now global-only
            Debug::log('Admin.php:361 Deleting product meta for global-only settings', ['productId' => $productId]); // DEBUG-REMOVE
            delete_post_meta($productId, '_bfp_player_layout');
            delete_post_meta($productId, '_bfp_player_controls');
            delete_post_meta($productId, '_bfp_on_cover');
            
            // Update the settings that can still be overridden
            Debug::log('Admin.php:366 Updating product meta with global settings', ['productId' => $productId]); // DEBUG-REMOVE
            update_post_meta($productId, '_bfp_enable_player', $globalSettings['_bfp_enable_player']);
            update_post_meta($productId, '_bfp_merge_in_grouped', $globalSettings['_bfp_merge_in_grouped']);
            update_post_meta($productId, '_bfp_single_player', $globalSettings['_bfp_single_player']);
            update_post_meta($productId, '_bfp_play_all', $globalSettings['_bfp_play_all']);
            update_post_meta($productId, '_bfp_loop', $globalSettings['_bfp_loop']);
            update_post_meta($productId, '_bfp_secure_player', $globalSettings['_bfp_secure_player']);
            update_post_meta($productId, '_bfp_file_percent', $globalSettings['_bfp_file_percent']);

            $this->config->clearProductAttrsCache($productId);
        }
        Debug::log('Admin.php:375 Finished applying settings to all products', []); // DEBUG-REMOVE
    }

    /**
     * Save post meta data
     */
    public function savePost(int $postId, \WP_Post $post, bool $update): void {
        Debug::log('Admin.php:381 Entering savePost()', ['postId' => $postId, 'postType' => $post->post_type ?? null, 'update' => $update]); // DEBUG-REMOVE
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            Debug::log('Admin.php:383 Doing autosave, exiting savePost()', []); // DEBUG-REMOVE
            return;
        }
        if (empty($_POST['bfp_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['bfp_nonce'])), 'bfp_updating_product')) {
            Debug::log('Admin.php:387 Nonce check failed, exiting savePost()', []); // DEBUG-REMOVE
            return;
        }
        $postTypes = $this->config->getPostTypes();
        if (!isset($post) || !in_array($post->post_type, $postTypes) || !current_user_can('edit_post', $postId)) {
            Debug::log('Admin.php:391 Invalid post type or permissions, exiting savePost()', []); // DEBUG-REMOVE
            return;
        }

        // Remove all vendor add-on logic and flags/options
        $_DATA = stripslashes_deep($_REQUEST);

        // Always allow saving player options (no vendor plugin checks)
        Debug::log('Admin.php:397 Deleting post files before saving options', ['postId' => $postId]); // DEBUG-REMOVE
        $this->fileManager->deletePost($postId, false, true);

        // Save the player options
        Debug::log('Admin.php:401 Saving product options', ['postId' => $postId]); // DEBUG-REMOVE
        $this->saveProductOptions($postId, $_DATA);
        Debug::log('Admin.php:403 Exiting savePost()', []); // DEBUG-REMOVE
    }
    
    /**
     * Save product-specific options - REFACTORED
     */
    private function saveProductOptions(int $postId, array $_DATA): void {
        Debug::log('Admin.php:409 Entering saveProductOptions()', ['postId' => $postId, '_DATA' => $_DATA]); // DEBUG-REMOVE
        // KEEP ONLY these product-specific settings:
        $enablePlayer = isset($_DATA['_bfp_enable_player']) ? 1 : 0;
        $mergeGrouped = isset($_DATA['_bfp_merge_in_grouped']) ? 1 : 0;
        $singlePlayer = isset($_DATA['_bfp_single_player']) ? 1 : 0;
        $playAll = isset($_DATA['_bfp_play_all']) ? 1 : 0;
        $loop = isset($_DATA['_bfp_loop']) ? 1 : 0;
        $volume = (isset($_DATA['_bfp_player_volume']) && is_numeric($_DATA['_bfp_player_volume'])) ? 
                  floatval($_DATA['_bfp_player_volume']) : 1;
        $securePlayer = isset($_DATA['_bfp_secure_player']) ? 1 : 0;
        $filePercent = (isset($_DATA['_bfp_file_percent']) && is_numeric($_DATA['_bfp_file_percent'])) ? 
                        intval($_DATA['_bfp_file_percent']) : 0;
        $filePercent = min(max($filePercent, 0), 100);

        // --- SAVE TO DATABASE ---
        update_post_meta($postId, '_bfp_enable_player', $enablePlayer);
        update_post_meta($postId, '_bfp_merge_in_grouped', $mergeGrouped);
        update_post_meta($postId, '_bfp_single_player', $singlePlayer);
        update_post_meta($postId, '_bfp_play_all', $playAll);
        update_post_meta($postId, '_bfp_loop', $loop);
        update_post_meta($postId, '_bfp_player_volume', $volume);
        update_post_meta($postId, '_bfp_secure_player', $securePlayer);
        update_post_meta($postId, '_bfp_file_percent', $filePercent);
        Debug::log('Admin.php:420 Updated product meta for player options', ['postId' => $postId]); // DEBUG-REMOVE

        // --- Product-specific audio engine override
        if (isset($_DATA['_bfp_audio_engine'])) {
            $productAudioEngine = sanitize_text_field(wp_unslash($_DATA['_bfp_audio_engine']));
            
            if ($productAudioEngine === 'global' || empty($productAudioEngine)) {
                // Delete the meta so it falls back to global
                Debug::log('Admin.php:427 Removing product-specific audio engine override', ['postId' => $postId]); // DEBUG-REMOVE
                delete_post_meta($postId, '_bfp_audio_engine');
            } elseif (in_array($productAudioEngine, ['mediaelement', 'wavesurfer', 'html5'])) {
                // Save valid override - now includes html5
                Debug::log('Admin.php:430 Saving product-specific audio engine override', ['postId' => $postId, 'audioEngine' => $productAudioEngine]); // DEBUG-REMOVE
                update_post_meta($postId, '_bfp_audio_engine', $productAudioEngine);
            }
        }
        // --- END: Product-specific audio engine override

        // --- KEEP DEMO LOGIC ---
        Debug::log('Admin.php:437 Saving demo files for product', ['postId' => $postId]); // DEBUG-REMOVE
        $this->saveDemoFiles($postId, $_DATA);
        $this->config->clearProductAttrsCache($postId);
        Debug::log('Admin.php:440 Exiting saveProductOptions()', ['postId' => $postId]); // DEBUG-REMOVE
    }
    
    /**
     * Save demo files for product
     */
    private function saveDemoFiles(int $postId, array $_DATA): void {
        Debug::log('Admin.php:446 Entering saveDemoFiles()', ['postId' => $postId]); // DEBUG-REMOVE
        $ownDemos = isset($_DATA['_bfp_own_demos']) ? 1 : 0;
        $directOwnDemos = isset($_DATA['_bfp_direct_own_demos']) ? 1 : 0;
        $demosList = [];

        if (isset($_DATA['_bfp_file_urls']) && is_array($_DATA['_bfp_file_urls'])) {
            Debug::log('Admin.php:451 Processing demo file URLs', ['count' => count($_DATA['_bfp_file_urls'])]); // DEBUG-REMOVE
            foreach ($_DATA['_bfp_file_urls'] as $_i => $_url) {
                if (!empty($_url)) {
                    $demosList[] = [
                        'name' => (!empty($_DATA['_bfp_file_names']) && !empty($_DATA['_bfp_file_names'][$_i])) ? 
                                  sanitize_text_field(wp_unslash($_DATA['_bfp_file_names'][$_i])) : '',
                        'file' => esc_url_raw(wp_unslash(trim($_url))),
                    ];
                }
            }
        }

        update_post_meta($postId, '_bfp_own_demos', $ownDemos);
        update_post_meta($postId, '_bfp_direct_own_demos', $directOwnDemos);
        update_post_meta($postId, '_bfp_demos_list', $demosList);
        Debug::log('Admin.php:463 Saved demo files meta', ['postId' => $postId, 'demosList' => $demosList]); // DEBUG-REMOVE
    }

    /**
     * After delete post callback
     */
    public function afterDeletePost(int $postId, \WP_Post $postObj): void {
        Debug::log('Admin.php:469 Entering afterDeletePost()', ['postId' => $postId]); // DEBUG-REMOVE
        $this->fileManager->deletePost($postId);
        Debug::log('Admin.php:471 Exiting afterDeletePost()', ['postId' => $postId]); // DEBUG-REMOVE
    }

    /**
     * Render player settings metabox
     */
    public function woocommercePlayerSettings(): void {
        Debug::log('Admin.php:476 Rendering WooCommerce player settings metabox', []); // DEBUG-REMOVE
        global $post;
        include_once plugin_dir_path(dirname(dirname(__FILE__))) . 'templates/product-options.php';
        Debug::log('Admin.php:479 Finished rendering WooCommerce player settings metabox', []); // DEBUG-REMOVE
    }

    /**
     * Show admin notices
     */
    public function showAdminNotices(): void {
        Debug::log('Admin.php:showAdminNotices', [
            'action' => 'Checking for Bandfront Player admin notices'
        ]); // DEBUG-REMOVE
        // Only show on our settings page
        if (!isset($_GET['page']) || $_GET['page'] !== 'bandfront-player-settings') {
            Debug::log('Admin.php:487 Not on Bandfront Player settings page, skipping notices', []); // DEBUG-REMOVE
            return;
        }
        
        // Check for transient notice first (has more details)
        $notice = get_transient('bfp_admin_notice');
        if ($notice) {
            Debug::log('Admin.php:492 Showing transient admin notice', ['notice' => $notice]); // DEBUG-REMOVE
            delete_transient('bfp_admin_notice');
            $class = 'notice notice-' . $notice['type'] . ' is-dismissible';
            printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($class), esc_html($notice['message']));
            return;
        }
        
        // Only show generic notice if no transient notice exists
        if (isset($_GET['settings-updated']) && $_GET['settings-updated'] === 'true') {
            Debug::log('Admin.php:500 Showing generic settings updated notice', []); // DEBUG-REMOVE
            ?>
            <div class="notice notice-success is-dismissible">
                <p><?php esc_html_e('Settings saved successfully!', 'bandfront-player'); ?></p>
            </div>
            <?php
        }
    }
    
    /**
     * AJAX handler for saving settings
     */
    public function ajaxSaveSettings(): void {
        Debug::log('Admin.php:511 Entering ajaxSaveSettings()', []); // DEBUG-REMOVE
        // Verify nonce
        if (!isset($_POST['bfp_nonce']) || 
            !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['bfp_nonce'])), 'bfp_updating_plugin_settings')) {
            Debug::log('Admin.php:515 AJAX security check failed', []); // DEBUG-REMOVE
            wp_send_json_error([
                'message' => __('Security check failed. Please refresh the page and try again.', 'bandfront-player')
            ]);
        }
        
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            Debug::log('Admin.php:522 AJAX permission check failed', []); // DEBUG-REMOVE
            wp_send_json_error([
                'message' => __('You do not have permission to change these settings.', 'bandfront-player')
            ]);
        }
        
        // Save settings using existing method
        Debug::log('Admin.php:528 Saving global settings via AJAX', []); // DEBUG-REMOVE
        $messages = $this->saveGlobalSettings();
        
        // Send success response with detailed message
        if (!empty($messages) && $messages['type'] === 'success') {
            Debug::log('Admin.php:533 AJAX settings save success', ['messages' => $messages]); // DEBUG-REMOVE
            wp_send_json_success([
                'message' => $messages['message'],
                'details' => isset($messages['details']) ? $messages['details'] : []
            ]);
        } else {
            Debug::log('Admin.php:539 AJAX settings save error', ['messages' => $messages]); // DEBUG-REMOVE
            wp_send_json_error([
                'message' => isset($messages['message']) ? $messages['message'] : __('An error occurred while saving settings.', 'bandfront-player')
            ]);
        }
    }
    
    /**
     * Handle Google Drive OAuth file upload
     * Delegate to FileManager
     */
    private function handleDriveOAuthUpload(array $fileData): void {
        if (!empty($fileData) && $fileData['error'] == UPLOAD_ERR_OK) {
            Debug::log('Admin.php:246 Handling Google Drive OAuth file upload', []); // DEBUG-REMOVE
            $this->fileManager->handleOAuthFileUpload($fileData);
        }
    }
}
<?php
namespace bfp;

/**
 * Admin functionality
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Admin Class
 */
class Admin {
    
    private Plugin $mainPlugin;
    
    public function __construct(Plugin $mainPlugin) {
        $this->mainPlugin = $mainPlugin;
        $this->initHooks();
        
        // Include view templates that add hooks
        $this->loadViewTemplates();
    }
    
    /**
     * Load view templates that register hooks
     */
    private function loadViewTemplates(): void {
        // Only load in admin area
        if (!is_admin()) {
            return;
        }
        
        // Include audio engine settings template (registers hooks)
        require_once plugin_dir_path(dirname(__FILE__)) . 'src/Views/audio-engine-settings.php';
        
        // Future: Add other view templates here as needed
    }

    /**
     * Initialize WordPress hooks
     */
    private function initHooks(): void {
        add_action('admin_menu', [$this, 'menuLinks']);
        add_action('admin_init', [$this, 'adminInit'], 99);
        add_action('save_post', [$this, 'savePost'], 10, 3);
        add_action('after_delete_post', [$this, 'afterDeletePost'], 10, 2);
        add_action('admin_notices', [$this, 'showAdminNotices']);
        
        // Add AJAX handler for settings save
        add_action('wp_ajax_bfp_save_settings', [$this, 'ajaxSaveSettings']);
    }
    
    /**
     * Admin initialization
     */
    public function adminInit(): void {
        // Check if WooCommerce is installed or not
        if (!class_exists('woocommerce')) {
            return;
        }

        $this->mainPlugin->getFileHandler()->clearExpiredTransients();

        add_meta_box(
            'bfp_woocommerce_metabox', 
            __('Bandfront Player', 'bandfront-player'), 
            [$this, 'woocommercePlayerSettings'], 
            $this->mainPlugin->getPostTypes(), 
            'normal'
        );

        // Products list "Playback Counter"
        $this->setupProductColumns();
    }
    
    /**
     * Setup product list columns
     */
    private function setupProductColumns(): void {
        $manageProductPostsColumns = function($columns) {
            if ($this->mainPlugin->getConfig()->getState('_bfp_playback_counter_column', 1)) {
                wp_enqueue_style(
                    'bfp-Playback-counter', 
                    plugin_dir_url(BFP_PLUGIN_PATH) . 'css/style-admin.css', 
                    [], 
                    BFP_VERSION
                );
                $columns = array_merge($columns, [
                    'bfp_playback_counter' => __('Playback Counter', 'bandfront-player')
                ]);
            }
            return $columns;
        };
        add_filter('manage_product_posts_columns', $manageProductPostsColumns);

        $manageProductPostsCustomColumn = function($columnKey, $productId) {
            if ($this->mainPlugin->getConfig()->getState('_bfp_playback_counter_column', 1) && 
                'bfp_playback_counter' == $columnKey) {
                $counter = get_post_meta($productId, '_bfp_playback_counter', true);
                echo '<span class="bfp-playback-counter">' . esc_html(!empty($counter) ? $counter : '') . '</span>';
            }
        };
        add_action('manage_product_posts_custom_column', $manageProductPostsCustomColumn, 10, 2);
    }

    /**
     * Add admin menu
     */
    public function menuLinks(): void {
        add_menu_page(
            'Bandfront Player',
            'Bandfront Player',
            'manage_options',
            'bandfront-player-settings',
            [$this, 'settingsPage'],
            'dashicons-format-audio',
            30
        );
    }

    /**
     * Settings page callback
     */
    public function settingsPage(): void {
        if (isset($_POST['bfp_nonce']) && 
            wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['bfp_nonce'])), 'bfp_updating_plugin_settings')) {
            $messages = $this->saveGlobalSettings();
            
            // Set transient for admin notice
            if (!empty($messages)) {
                set_transient('bfp_admin_notice', $messages, 30);
            }
            
            // Redirect to prevent form resubmission
            wp_redirect(add_query_arg('settings-updated', 'true', wp_get_referer()));
            exit;
        }

        echo '<div class="wrap">';
        include_once plugin_dir_path(dirname(__FILE__)) . 'src/Views/global-admin-options.php';
        echo '</div>';
    }
    
    /**
     * Settings page URL
     */
    public function settingsPageUrl(): string {
        return admin_url('options-general.php?page=bandfront-player-settings');
    }

    /**
     * Save global settings
     */
    private function saveGlobalSettings(): array {
        $_REQUEST = stripslashes_deep($_REQUEST);
        
        // Track what changed for notifications
        $changes = [];
        $oldSettings = $this->mainPlugin->getConfig()->getStates([
            '_bfp_audio_engine',
            '_bfp_enable_player',
            '_bfp_secure_player',
            '_bfp_ffmpeg'
        ]);
        
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
        
        $playerLayouts = $this->mainPlugin->getConfig()->getPlayerLayouts();
        $defaultLayout = $this->mainPlugin->getConfig()->getState('_bfp_player_layout');
        $playerStyle = (isset($_REQUEST['_bfp_player_layout']) && in_array($_REQUEST['_bfp_player_layout'], $playerLayouts)) ? 
                        sanitize_text_field(wp_unslash($_REQUEST['_bfp_player_layout'])) : $defaultLayout;
        
        $singlePlayer = isset($_REQUEST['_bfp_single_player']) ? 1 : 0;
        $securePlayer = isset($_REQUEST['_bfp_secure_player']) ? 1 : 0;
        $filePercent = (isset($_REQUEST['_bfp_file_percent']) && is_numeric($_REQUEST['_bfp_file_percent'])) ? 
                        intval($_REQUEST['_bfp_file_percent']) : 0;
        $filePercent = min(max($filePercent, 0), 100);
        
        $playerControlsList = $this->mainPlugin->getConfig()->getPlayerControls();
        $defaultControls = $this->mainPlugin->getConfig()->getState('_bfp_player_controls');
        $playerControls = (isset($_REQUEST['_bfp_player_controls']) && in_array($_REQUEST['_bfp_player_controls'], $playerControlsList)) ? 
                           sanitize_text_field(wp_unslash($_REQUEST['_bfp_player_controls'])) : $defaultControls;

        $onCover = (('button' == $playerControls || 'default' == $playerControls) && isset($_REQUEST['_bfp_player_on_cover'])) ? 1 : 0;

        $playerTitle = isset($_REQUEST['_bfp_player_title']) ? 1 : 0;
        $mergeGrouped = isset($_REQUEST['_bfp_merge_in_grouped']) ? 1 : 0;
        $playAll = isset($_REQUEST['_bfp_play_all']) ? 1 : 0;
        $loop = isset($_REQUEST['_bfp_loop']) ? 1 : 0;
        $playSimultaneously = isset($_REQUEST['_bfp_play_simultaneously']) ? 1 : 0;
        $volume = (isset($_REQUEST['_bfp_player_volume']) && is_numeric($_REQUEST['_bfp_player_volume'])) ? 
                  floatval($_REQUEST['_bfp_player_volume']) : 1;

        $message = isset($_REQUEST['_bfp_message']) ? wp_kses_post(wp_unslash($_REQUEST['_bfp_message'])) : '';
        $applyToAllPlayers = isset($_REQUEST['_bfp_apply_to_all_players']) ? 1 : 0;

        // FIXED: Audio engine handling
        $audioEngine = 'mediaelement'; // Default fallback
        if (isset($_REQUEST['_bfp_audio_engine']) && 
            in_array($_REQUEST['_bfp_audio_engine'], ['mediaelement', 'wavesurfer'])) {
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
            $this->mainPlugin->getFiles()->handleOAuthFileUpload($_FILES['_bfp_drive_key']);
        } else {
            // Preserve existing drive key if no new file uploaded
            $existingCloudSettings = get_option('_bfp_cloud_drive_addon', []);
            if ($bfpDrive && isset($existingCloudSettings['_bfp_drive_key'])) {
                $cloudDriveAddon = [
                    '_bfp_drive' => $bfpDrive,
                    '_bfp_drive_key' => $existingCloudSettings['_bfp_drive_key']
                ];
                update_option('_bfp_cloud_drive_addon', $cloudDriveAddon);
            } elseif (!$bfpDrive) {
                // If unchecked, clear the settings
                delete_option('_bfp_cloud_drive_addon');
            }
        }
        
        // Save the Google Drive API key separately
        if ($bfpDriveApiKey !== '') {
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
            '_bfp_player_volume' => $volume,
            '_bfp_single_player' => $singlePlayer,
            '_bfp_secure_player' => $securePlayer,
            '_bfp_player_controls' => $playerControls,
            '_bfp_file_percent' => $filePercent,
            '_bfp_player_title' => $playerTitle,
            '_bfp_merge_in_grouped' => $mergeGrouped,
            '_bfp_play_all' => $playAll,
            '_bfp_loop' => $loop,
            '_bfp_play_simultaneously' => $playSimultaneously,
            '_bfp_on_cover' => $onCover,
            '_bfp_message' => $message,
            '_bfp_default_extension' => $troubleshootDefaultExtension,
            '_bfp_force_main_player_in_title' => $forceMainPlayerInTitle,
            '_bfp_ios_controls' => $iosControls,
            '_bfp_onload' => $troubleshootOnload,
            '_bfp_disable_302' => $disable302,
            '_bfp_playback_counter_column' => isset($_REQUEST['_bfp_playback_counter_column']) ? sanitize_text_field(wp_unslash($_REQUEST['_bfp_playback_counter_column'])) : 0,
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
            $this->mainPlugin->getFileHandler()->clearDir($this->mainPlugin->getFileHandler()->getFilesDirectoryPath());
        }

        if ($applyToAllPlayers) {
            $this->applySettingsToAllProducts($globalSettings);
        }

        update_option('bfp_global_settings', $globalSettings);
        $this->mainPlugin->getConfig()->updateGlobalAttrs($globalSettings);
        do_action('bfp_save_setting');

        // Purge Cache using new cache manager
        Utils\Cache::clearAllCaches();
        
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
     * Apply settings to all products - REFACTORED
     */
    private function applySettingsToAllProducts(array $globalSettings): void {
        $productsIds = [
            'post_type' => $this->mainPlugin->getPostTypes(),
            'numberposts' => -1,
            'post_status' => ['publish', 'pending', 'draft', 'future'],
            'fields' => 'ids',
            'cache_results' => false,
        ];

        $products = get_posts($productsIds);
        foreach ($products as $productId) {
            // Delete meta keys for settings that are now global-only
            delete_post_meta($productId, '_bfp_player_layout');
            delete_post_meta($productId, '_bfp_player_controls');
            delete_post_meta($productId, '_bfp_player_title');
            delete_post_meta($productId, '_bfp_on_cover');
            
            // Update the settings that can still be overridden
            update_post_meta($productId, '_bfp_enable_player', $globalSettings['_bfp_enable_player']);
            update_post_meta($productId, '_bfp_merge_in_grouped', $globalSettings['_bfp_merge_in_grouped']);
            update_post_meta($productId, '_bfp_single_player', $globalSettings['_bfp_single_player']);
            update_post_meta($productId, '_bfp_play_all', $globalSettings['_bfp_play_all']);
            update_post_meta($productId, '_bfp_loop', $globalSettings['_bfp_loop']);
            update_post_meta($productId, '_bfp_player_volume', $globalSettings['_bfp_player_volume']);
            update_post_meta($productId, '_bfp_secure_player', $globalSettings['_bfp_secure_player']);
            update_post_meta($productId, '_bfp_file_percent', $globalSettings['_bfp_file_percent']);

            $this->mainPlugin->getConfig()->clearProductAttrsCache($productId);
        }
    }

    /**
     * Save post meta data
     */
    public function savePost(int $postId, \WP_Post $post, bool $update): void {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        if (empty($_POST['bfp_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['bfp_nonce'])), 'bfp_updating_product')) {
            return;
        }
        $postTypes = $this->mainPlugin->getPostTypes();
        if (!isset($post) || !in_array($post->post_type, $postTypes) || !current_user_can('edit_post', $postId)) {
            return;
        }

        // Remove all vendor add-on logic and flags/options
        $_DATA = stripslashes_deep($_REQUEST);

        // Always allow saving player options (no vendor plugin checks)
        $this->mainPlugin->getFileHandler()->deletePost($postId, false, true);

        // Save the player options
        $this->saveProductOptions($postId, $_DATA);
    }
    
    /**
     * Save product-specific options - REFACTORED
     */
    private function saveProductOptions(int $postId, array $_DATA): void {
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

        // --- Product-specific audio engine override
        if (isset($_DATA['_bfp_audio_engine'])) {
            $productAudioEngine = sanitize_text_field(wp_unslash($_DATA['_bfp_audio_engine']));
            
            if ($productAudioEngine === 'global' || empty($productAudioEngine)) {
                // Delete the meta so it falls back to global
                delete_post_meta($postId, '_bfp_audio_engine');
            } elseif (in_array($productAudioEngine, ['mediaelement', 'wavesurfer'])) {
                // Save valid override
                update_post_meta($postId, '_bfp_audio_engine', $productAudioEngine);
            }
        }
        // --- END: Product-specific audio engine override

        // --- KEEP DEMO LOGIC ---
        $this->saveDemoFiles($postId, $_DATA);
        $this->mainPlugin->getConfig()->clearProductAttrsCache($postId);
    }
    
    /**
     * Save demo files for product
     */
    private function saveDemoFiles(int $postId, array $_DATA): void {
        $ownDemos = isset($_DATA['_bfp_own_demos']) ? 1 : 0;
        $directOwnDemos = isset($_DATA['_bfp_direct_own_demos']) ? 1 : 0;
        $demosList = [];

        if (isset($_DATA['_bfp_file_urls']) && is_array($_DATA['_bfp_file_urls'])) {
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
    }

    /**
     * After delete post callback
     */
    public function afterDeletePost(int $postId, \WP_Post $postObj): void {
        $this->mainPlugin->getFileHandler()->deletePost($postId);
    }

    /**
     * Render player settings metabox
     */
    public function woocommercePlayerSettings(): void {
        global $post;
        include_once plugin_dir_path(dirname(__FILE__)) . 'src/Views/product-options.php';
    }

    /**
     * Show admin notices
     */
    public function showAdminNotices(): void {
        // Only show on our settings page
        if (!isset($_GET['page']) || $_GET['page'] !== 'bandfront-player-settings') {
            return;
        }
        
        // Check for transient notice first (has more details)
        $notice = get_transient('bfp_admin_notice');
        if ($notice) {
            delete_transient('bfp_admin_notice');
            $class = 'notice notice-' . $notice['type'] . ' is-dismissible';
            printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($class), esc_html($notice['message']));
            return;
        }
        
        // Only show generic notice if no transient notice exists
        if (isset($_GET['settings-updated']) && $_GET['settings-updated'] === 'true') {
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
        // Verify nonce
        if (!isset($_POST['bfp_nonce']) || 
            !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['bfp_nonce'])), 'bfp_updating_plugin_settings')) {
            wp_send_json_error([
                'message' => __('Security check failed. Please refresh the page and try again.', 'bandfront-player')
            ]);
        }
        
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error([
                'message' => __('You do not have permission to change these settings.', 'bandfront-player')
            ]);
        }
        
        // Save settings using existing method
        $messages = $this->saveGlobalSettings();
        
        // Send success response with detailed message
        if (!empty($messages) && $messages['type'] === 'success') {
            wp_send_json_success([
                'message' => $messages['message'],
                'details' => isset($messages['details']) ? $messages['details'] : []
            ]);
        } else {
            wp_send_json_error([
                'message' => isset($messages['message']) ? $messages['message'] : __('An error occurred while saving settings.', 'bandfront-player')
            ]);
        }
    }
}
<?php
/**
 * Admin functionality for Bandfront Player
 *
 * @package BandfrontPlayer
 * @since 0.1
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * BFP Admin Class
 */
class BFP_Admin {
    
    private $main_plugin;
    private $modules_path;
    
    /**
     * Module definitions for admin sections
     * Key: module identifier, Value: module file and metadata
     */
    private $admin_modules = array(
        'audio-engine' => array(
            'file' => 'audio-engine.php',
            'name' => 'Audio Engine Selector',
            'description' => 'Audio engine settings and options',
        ),
        'cloud-engine' => array(
            'file' => 'cloud-engine.php',
            'name' => 'Cloud Storage Integration',
            'description' => 'Cloud storage settings and configuration',
        ),
    );
    
    public function __construct($main_plugin) {
        $this->main_plugin = $main_plugin;
        $this->modules_path = plugin_dir_path(dirname(__FILE__)) . 'modules/';
        $this->init_hooks();
        $this->load_admin_modules();
    }
    
    /**
     * Load admin modules (settings sections)
     */
    private function load_admin_modules() {
        // Only load in admin area
        if (!is_admin()) {
            return;
        }
        
        $config = $this->main_plugin->get_config();
        
        foreach ($this->admin_modules as $module_id => $module_info) {
            // Audio engine is core functionality - always load it
            if ($module_id === 'audio-engine') {
                $module_path = $this->modules_path . $module_info['file'];
                if (file_exists($module_path)) {
                    require_once $module_path;
                    do_action('bfp_admin_module_loaded', $module_id);
                }
                continue;
            }
            
            // Check if other modules are enabled in state
            if (!$config->is_module_enabled($module_id)) {
                continue;
            }
            
            $module_path = $this->modules_path . $module_info['file'];
            if (file_exists($module_path)) {
                require_once $module_path;
                do_action('bfp_admin_module_loaded', $module_id);
            }
        }
        
        do_action('bfp_admin_modules_loaded');
    }
    
    /**
     * Get available admin modules
     * Used by settings page to show module enable/disable options
     * 
     * @return array Module information
     */
    public function get_admin_modules() {
        return $this->admin_modules;
    }

    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        add_action('admin_menu', array($this, 'menu_links'));
        add_action('admin_init', array($this, 'admin_init'), 99);
        add_action('save_post', array($this, 'save_post'), 10, 3);
        add_action('after_delete_post', array($this, 'after_delete_post'), 10, 2);
        add_action('admin_notices', array($this, 'show_admin_notices'));
        
        // Add AJAX handler for settings save
        add_action('wp_ajax_bfp_save_settings', array($this, 'ajax_save_settings'));
    }
    
    /**
     * Admin initialization
     */
    public function admin_init() {
        // Check if WooCommerce is installed or not
        if (!class_exists('woocommerce')) {
            return;
        }

        $this->main_plugin->clear_expired_transients();

        add_meta_box(
            'bfp_woocommerce_metabox', 
            __('Bandfront Player', 'bandfront-player'), 
            array($this, 'woocommerce_player_settings'), 
            $this->main_plugin->_get_post_types(), 
            'normal'
        );

        // Products list "Playback Counter"
        $this->setup_product_columns();
    }
    
    /**
     * Setup product list columns
     */
    private function setup_product_columns() {
        $manage_product_posts_columns = function($columns) {
            // Use get_state for single value retrieval
            if ($this->main_plugin->get_config()->get_state('_bfp_playback_counter_column', 1)) {
                wp_enqueue_style(
                    'bfp-Playback-counter', 
                    plugin_dir_url(BFP_PLUGIN_PATH) . 'css/style-admin.css', 
                    array(), 
                    BFP_VERSION
                );
                $columns = array_merge($columns, [
                    'bfp_playback_counter' => __('Playback Counter', 'bandfront-player')
                ]);
            }
            return $columns;
        };
        add_filter('manage_product_posts_columns', $manage_product_posts_columns);

        $manage_product_posts_custom_column = function($column_key, $product_id) {
            // Use get_state for single value retrieval
            if ($this->main_plugin->get_config()->get_state('_bfp_playback_counter_column', 1) && 
                'bfp_playback_counter' == $column_key) {
                $counter = get_post_meta($product_id, '_bfp_playback_counter', true);
                echo '<span class="bfp-playback-counter">' . esc_html(!empty($counter) ? $counter : '') . '</span>';
            }
        };
        add_action('manage_product_posts_custom_column', $manage_product_posts_custom_column, 10, 2);
    }

    /**
     * Add admin menu
     */
    public function menu_links() {
        add_menu_page(
            'Bandfront Player',
            'Bandfront Player',
            'manage_options',
            'bandfront-player-settings',
            array($this, 'settings_page'),
            'dashicons-format-audio',
            30
        );
    }

    /**
     * Settings page callback
     */
    public function settings_page() {
        if (isset($_POST['bfp_nonce']) && 
            wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['bfp_nonce'])), 'bfp_updating_plugin_settings')) {
            $messages = $this->save_global_settings();
            
            // Set transient for admin notice
            if (!empty($messages)) {
                set_transient('bfp_admin_notice', $messages, 30);
            }
            
            // Redirect to prevent form resubmission
            wp_redirect(add_query_arg('settings-updated', 'true', wp_get_referer()));
            exit;
        }

        echo '<div class="wrap">';
        include_once dirname(BFP_PLUGIN_PATH) . '/views/global-admin-options.php';
        echo '</div>';
    }
    
    /**
     * Settings page URL
     */
    public function settings_page_url() {
        return admin_url('options-general.php?page=bandfront-player-settings');
    }

    /**
     * Save global settings
     */
    private function save_global_settings() {
        $_REQUEST = stripslashes_deep($_REQUEST);
        
        // Track what changed for notifications
        $changes = array();
        // Use get_states for bulk retrieval when comparing multiple old values
        $old_settings = $this->main_plugin->get_config()->get_states(array(
            '_bfp_audio_engine',
            '_bfp_enable_player',
            '_bfp_secure_player',
            '_bfp_ffmpeg'
        ));
        
        // Save the player settings
        $registered_only = isset($_REQUEST['_bfp_registered_only']) ? 1 : 0;
        $purchased = isset($_REQUEST['_bfp_purchased']) ? 1 : 0;
        $reset_purchased_interval = (isset($_REQUEST['_bfp_reset_purchased_interval']) && 'never' == $_REQUEST['_bfp_reset_purchased_interval']) ? 'never' : 'daily';
        $fade_out = isset($_REQUEST['_bfp_fade_out']) ? 1 : 0;
        $purchased_times_text = sanitize_text_field(isset($_REQUEST['_bfp_purchased_times_text']) ? wp_unslash($_REQUEST['_bfp_purchased_times_text']) : '');
        $ffmpeg = isset($_REQUEST['_bfp_ffmpeg']) ? 1 : 0;
        $ffmpeg_path = isset($_REQUEST['_bfp_ffmpeg_path']) ? sanitize_text_field(wp_unslash($_REQUEST['_bfp_ffmpeg_path'])) : '';
        $ffmpeg_watermark = isset($_REQUEST['_bfp_ffmpeg_watermark']) ? sanitize_text_field(wp_unslash($_REQUEST['_bfp_ffmpeg_watermark'])) : '';
        
        if (!empty($ffmpeg_path)) {
            $ffmpeg_path = str_replace('\\', '/', $ffmpeg_path);
            $ffmpeg_path = preg_replace('/(\/)+/', '/', $ffmpeg_path);
        }

        $troubleshoot_default_extension = isset($_REQUEST['_bfp_default_extension']) ? true : false;
        $force_main_player_in_title = isset($_REQUEST['_bfp_force_main_player_in_title']) ? 1 : 0;
        $ios_controls = isset($_REQUEST['_bfp_ios_controls']) ? true : false;
        $troubleshoot_onload = isset($_REQUEST['_bfp_onload']) ? true : false;
        $disable_302 = isset($_REQUEST['_bfp_disable_302']) ? 1 : 0;

        $enable_player = isset($_REQUEST['_bfp_enable_player']) ? 1 : 0;
        $show_in = (isset($_REQUEST['_bfp_show_in']) && in_array($_REQUEST['_bfp_show_in'], array('single', 'multiple'))) ? 
                   sanitize_text_field(wp_unslash($_REQUEST['_bfp_show_in'])) : 'all';
        $players_in_cart = isset($_REQUEST['_bfp_players_in_cart']) ? true : false;
        
        // Use get_state for getting default values
        $player_layouts = $this->main_plugin->get_config()->get_player_layouts();
        $default_layout = $this->main_plugin->get_config()->get_state('_bfp_player_layout');
        $player_style = (isset($_REQUEST['_bfp_player_layout']) && in_array($_REQUEST['_bfp_player_layout'], $player_layouts)) ? 
                        sanitize_text_field(wp_unslash($_REQUEST['_bfp_player_layout'])) : $default_layout;
        
        if (isset($skin_mapping[$player_style])) {
            $player_style = $skin_mapping[$player_style];
        }
        
        $single_player = isset($_REQUEST['_bfp_single_player']) ? 1 : 0;
        $secure_player = isset($_REQUEST['_bfp_secure_player']) ? 1 : 0;
        $file_percent = (isset($_REQUEST['_bfp_file_percent']) && is_numeric($_REQUEST['_bfp_file_percent'])) ? 
                        intval($_REQUEST['_bfp_file_percent']) : 0;
        $file_percent = min(max($file_percent, 0), 100);
        
        $player_controls_list = $this->main_plugin->get_config()->get_player_controls();
        $default_controls = $this->main_plugin->get_config()->get_state('_bfp_player_controls');
        $player_controls = (isset($_REQUEST['_bfp_player_controls']) && in_array($_REQUEST['_bfp_player_controls'], $player_controls_list)) ? 
                           sanitize_text_field(wp_unslash($_REQUEST['_bfp_player_controls'])) : $default_controls;

        $on_cover = (('button' == $player_controls || 'default' == $player_controls) && isset($_REQUEST['_bfp_player_on_cover'])) ? 1 : 0;

        $player_title = isset($_REQUEST['_bfp_player_title']) ? 1 : 0;
        $merge_grouped = isset($_REQUEST['_bfp_merge_in_grouped']) ? 1 : 0;
        $play_all = isset($_REQUEST['_bfp_play_all']) ? 1 : 0;
        $loop = isset($_REQUEST['_bfp_loop']) ? 1 : 0;
        $play_simultaneously = isset($_REQUEST['_bfp_play_simultaneously']) ? 1 : 0;
        $volume = (isset($_REQUEST['_bfp_player_volume']) && is_numeric($_REQUEST['_bfp_player_volume'])) ? 
                  floatval($_REQUEST['_bfp_player_volume']) : 1;
        $preload = (isset($_REQUEST['_bfp_preload']) && in_array($_REQUEST['_bfp_preload'], array('none', 'metadata', 'auto'))) ? 
                   sanitize_text_field(wp_unslash($_REQUEST['_bfp_preload'])) : 'none';

        $message = isset($_REQUEST['_bfp_message']) ? wp_kses_post(wp_unslash($_REQUEST['_bfp_message'])) : '';
        $apply_to_all_players = isset($_REQUEST['_bfp_apply_to_all_players']) ? 1 : 0;

        // FIXED: Audio engine handling
        $audio_engine = 'mediaelement'; // Default fallback
        if (isset($_REQUEST['_bfp_audio_engine']) && 
            in_array($_REQUEST['_bfp_audio_engine'], array('mediaelement', 'wavesurfer'))) {
            $audio_engine = sanitize_text_field(wp_unslash($_REQUEST['_bfp_audio_engine']));
        }
        
        $enable_visualizations = 0;
        if (isset($_REQUEST['_bfp_enable_visualizations']) && 
            $_REQUEST['_bfp_audio_engine'] === 'wavesurfer') {
            $enable_visualizations = 1;
        }

        // Handle module states
        $modules_enabled = $this->main_plugin->get_config()->get_state('_bfp_modules_enabled');
        if (isset($_REQUEST['_bfp_modules']) && is_array($_REQUEST['_bfp_modules'])) {
            foreach ($modules_enabled as $module_id => $current_state) {
                $modules_enabled[$module_id] = isset($_REQUEST['_bfp_modules'][$module_id]);
            }
        } else {
            // If no modules are checked, disable all
            foreach ($modules_enabled as $module_id => $current_state) {
                $modules_enabled[$module_id] = false;
            }
        }

        // Cloud Storage Settings
        $cloud_active_tab = isset($_REQUEST['_bfp_cloud_active_tab']) ? 
                           sanitize_text_field(wp_unslash($_REQUEST['_bfp_cloud_active_tab'])) : 'google-drive';
        
        // Handle cloud storage settings from the form
        $cloud_dropbox = array(
            'enabled' => isset($_REQUEST['_bfp_cloud_dropbox_enabled']) ? true : false,
            'access_token' => isset($_REQUEST['_bfp_cloud_dropbox_token']) ? 
                             sanitize_text_field(wp_unslash($_REQUEST['_bfp_cloud_dropbox_token'])) : '',
            'folder_path' => isset($_REQUEST['_bfp_cloud_dropbox_folder']) ? 
                            sanitize_text_field(wp_unslash($_REQUEST['_bfp_cloud_dropbox_folder'])) : '/bandfront-demos',
        );
        
        $cloud_s3 = array(
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
        );
        
        $cloud_azure = array(
            'enabled' => isset($_REQUEST['_bfp_cloud_azure_enabled']) ? true : false,
            'account_name' => isset($_REQUEST['_bfp_cloud_azure_account']) ? 
                             sanitize_text_field(wp_unslash($_REQUEST['_bfp_cloud_azure_account'])) : '',
            'account_key' => isset($_REQUEST['_bfp_cloud_azure_key']) ? 
                            sanitize_text_field(wp_unslash($_REQUEST['_bfp_cloud_azure_key'])) : '',
            'container' => isset($_REQUEST['_bfp_cloud_azure_container']) ? 
                          sanitize_text_field(wp_unslash($_REQUEST['_bfp_cloud_azure_container'])) : '',
            'path_prefix' => isset($_REQUEST['_bfp_cloud_azure_path']) ? 
                            sanitize_text_field(wp_unslash($_REQUEST['_bfp_cloud_azure_path'])) : 'bandfront-demos/',
        );

        // Handle Google Drive settings from the legacy addon
        $bfp_drive = isset($_REQUEST['_bfp_drive']) ? 1 : 0;
        $bfp_drive_api_key = isset($_REQUEST['_bfp_drive_api_key']) ? 
                            sanitize_text_field(wp_unslash($_REQUEST['_bfp_drive_api_key'])) : '';
        
        // Handle Google Drive OAuth file upload
        if (!empty($_FILES['_bfp_drive_key']) && $_FILES['_bfp_drive_key']['error'] == UPLOAD_ERR_OK) {
            $uploaded_file = $_FILES['_bfp_drive_key'];
            if ($uploaded_file['type'] == 'application/json') {
                $json_content = file_get_contents($uploaded_file['tmp_name']);
                $json_data = json_decode($json_content, true);
                
                if ($json_data && isset($json_data['web'])) {
                    // Save to the legacy option format for compatibility
                    $cloud_drive_addon = get_option('_bfp_cloud_drive_addon', array());
                    $cloud_drive_addon['_bfp_drive'] = $bfp_drive;
                    $cloud_drive_addon['_bfp_drive_key'] = $json_content;
                    update_option('_bfp_cloud_drive_addon', $cloud_drive_addon);
                }
            }
        } else {
            // Preserve existing drive key if no new file uploaded
            $existing_cloud_settings = get_option('_bfp_cloud_drive_addon', array());
            if ($bfp_drive && isset($existing_cloud_settings['_bfp_drive_key'])) {
                $cloud_drive_addon = array(
                    '_bfp_drive' => $bfp_drive,
                    '_bfp_drive_key' => $existing_cloud_settings['_bfp_drive_key']
                );
                update_option('_bfp_cloud_drive_addon', $cloud_drive_addon);
            } elseif (!$bfp_drive) {
                // If unchecked, clear the settings
                delete_option('_bfp_cloud_drive_addon');
            }
        }
        
        // Save the Google Drive API key separately
        if ($bfp_drive_api_key !== '') {
            update_option('_bfp_drive_api_key', $bfp_drive_api_key);
        }

        $global_settings = array(
            '_bfp_registered_only' => $registered_only,
            '_bfp_purchased' => $purchased,
            '_bfp_reset_purchased_interval' => $reset_purchased_interval,
            '_bfp_fade_out' => $fade_out,
            '_bfp_purchased_times_text' => $purchased_times_text,
            '_bfp_ffmpeg' => $ffmpeg,
            '_bfp_ffmpeg_path' => $ffmpeg_path,
            '_bfp_ffmpeg_watermark' => $ffmpeg_watermark,
            '_bfp_enable_player' => $enable_player,
            '_bfp_show_in' => $show_in,
            '_bfp_players_in_cart' => $players_in_cart,
            '_bfp_player_layout' => $player_style,
            '_bfp_player_volume' => $volume,
            '_bfp_single_player' => $single_player,
            '_bfp_secure_player' => $secure_player,
            '_bfp_player_controls' => $player_controls,
            '_bfp_file_percent' => $file_percent,
            '_bfp_player_title' => $player_title,
            '_bfp_merge_in_grouped' => $merge_grouped,
            '_bfp_play_all' => $play_all,
            '_bfp_loop' => $loop,
            '_bfp_play_simultaneously' => $play_simultaneously,
            '_bfp_preload' => $preload,
            '_bfp_on_cover' => $on_cover,
            '_bfp_message' => $message,
            '_bfp_default_extension' => $troubleshoot_default_extension,
            '_bfp_force_main_player_in_title' => $force_main_player_in_title,
            '_bfp_ios_controls' => $ios_controls,
            '_bfp_onload' => $troubleshoot_onload,
            '_bfp_disable_302' => $disable_302,
            '_bfp_playback_counter_column' => isset($_REQUEST['_bfp_playback_counter_column']) ? sanitize_text_field(wp_unslash($_REQUEST['_bfp_playback_counter_column'])) : 0,
            '_bfp_analytics_integration' => isset($_REQUEST['_bfp_analytics_integration']) ? sanitize_text_field(wp_unslash($_REQUEST['_bfp_analytics_integration'])) : 'ua',
            '_bfp_analytics_property' => isset($_REQUEST['_bfp_analytics_property']) ? sanitize_text_field(wp_unslash($_REQUEST['_bfp_analytics_property'])) : '',
            '_bfp_analytics_api_secret' => isset($_REQUEST['_bfp_analytics_api_secret']) ? sanitize_text_field(wp_unslash($_REQUEST['_bfp_analytics_api_secret'])) : '',
            '_bfp_apply_to_all_players' => $apply_to_all_players,
            '_bfp_audio_engine' => $audio_engine,
            '_bfp_enable_visualizations' => $enable_visualizations,
            '_bfp_modules_enabled' => $modules_enabled,
            // Add cloud storage settings
            '_bfp_cloud_active_tab' => $cloud_active_tab,
            '_bfp_cloud_dropbox' => $cloud_dropbox,
            '_bfp_cloud_s3' => $cloud_s3,
            '_bfp_cloud_azure' => $cloud_azure,
        );

        if ($apply_to_all_players || isset($_REQUEST['_bfp_delete_demos'])) {
            $this->main_plugin->_clearDir($this->main_plugin->get_files_directory_path());
        }

        if ($apply_to_all_players) {
            $this->apply_settings_to_all_products($global_settings);
        }

        update_option('bfp_global_settings', $global_settings);
        $this->main_plugin->get_config()->update_global_attrs($global_settings);
        do_action('bfp_save_setting');

        // Purge Cache using new cache manager
        BFP_Cache::clear_all_caches();
        
        // Build notification message
        $messages = array();
        
        // Check what changed
        if ($old_settings['_bfp_audio_engine'] !== $audio_engine) {
            $messages[] = sprintf(__('Audio engine changed to %s', 'bandfront-player'), ucfirst($audio_engine));
        }
        
        if ($old_settings['_bfp_enable_player'] != $enable_player) {
            $messages[] = $enable_player ? 
                __('Players enabled on all products', 'bandfront-player') : 
                __('Players disabled on all products', 'bandfront-player');
        }
        
        if ($old_settings['_bfp_secure_player'] != $secure_player) {
            $messages[] = $secure_player ? 
                __('File truncation enabled - demo files will be created', 'bandfront-player') : 
                __('File truncation disabled - full files will be played', 'bandfront-player');
        }
        
        if ($old_settings['_bfp_ffmpeg'] != $ffmpeg) {
            $messages[] = $ffmpeg ? 
                __('FFmpeg enabled for demo creation', 'bandfront-player') : 
                __('FFmpeg disabled', 'bandfront-player');
        }
        
        if (isset($_REQUEST['_bfp_delete_demos'])) {
            $messages[] = __('Demo files have been deleted', 'bandfront-player');
        }
        
        if ($apply_to_all_players) {
            $messages[] = __('Settings applied to all products', 'bandfront-player');
        }
        
        // Return appropriate message
        if (!empty($messages)) {
            return array(
                'message' => __('Settings saved successfully!', 'bandfront-player') . ' ' . implode('. ', $messages) . '.',
                'type' => 'success'
            );
        } else {
            return array(
                'message' => __('Settings saved successfully!', 'bandfront-player'),
                'type' => 'success'
            );
        }
    }
    
    /**
     * Apply settings to all products - REFACTORED
     * Delete obsolete product-level settings so they fall back to global context
     */
    private function apply_settings_to_all_products($global_settings) {
        $products_ids = array(
            'post_type' => $this->main_plugin->_get_post_types(),
            'numberposts' => -1,
            'post_status' => array('publish', 'pending', 'draft', 'future'),
            'fields' => 'ids',
            'cache_results' => false,
        );

        $products = get_posts($products_ids);
        foreach ($products as $product_id) {
            // Delete meta keys for settings that are now global-only
            delete_post_meta($product_id, '_bfp_show_in');
            delete_post_meta($product_id, '_bfp_player_layout');
            delete_post_meta($product_id, '_bfp_player_controls');
            delete_post_meta($product_id, '_bfp_player_title');
            delete_post_meta($product_id, '_bfp_on_cover');
            
            // Update the settings that can still be overridden
            update_post_meta($product_id, '_bfp_enable_player', $global_settings['_bfp_enable_player']);
            update_post_meta($product_id, '_bfp_merge_in_grouped', $global_settings['_bfp_merge_in_grouped']);
            update_post_meta($product_id, '_bfp_single_player', $global_settings['_bfp_single_player']);
            update_post_meta($product_id, '_bfp_preload', $global_settings['_bfp_preload']);
            update_post_meta($product_id, '_bfp_play_all', $global_settings['_bfp_play_all']);
            update_post_meta($product_id, '_bfp_loop', $global_settings['_bfp_loop']);
            update_post_meta($product_id, '_bfp_player_volume', $global_settings['_bfp_player_volume']);
            update_post_meta($product_id, '_bfp_secure_player', $global_settings['_bfp_secure_player']);
            update_post_meta($product_id, '_bfp_file_percent', $global_settings['_bfp_file_percent']);

            $this->main_plugin->get_config()->clear_product_attrs_cache($product_id);
        }
    }

    /**
     * Save post meta data
     */
    public function save_post($post_id, $post, $update) {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        if (empty($_POST['bfp_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['bfp_nonce'])), 'bfp_updating_product')) {
            return;
        }
        $post_types = $this->main_plugin->_get_post_types();
        if (!isset($post) || !in_array($post->post_type, $post_types) || !current_user_can('edit_post', $post_id)) {
            return;
        }

        // Remove all vendor add-on logic and flags/options
        $_DATA = stripslashes_deep($_REQUEST);

        // Always allow saving player options (no vendor plugin checks)
        $this->main_plugin->delete_post($post_id, false, true);

        // Save the player options
        $this->save_product_options($post_id, $_DATA);
    }
    
    /**
     * Save product-specific options - REFACTORED
     * Only save essential product-specific overrides
     */
    private function save_product_options($post_id, $_DATA) {
        // KEEP ONLY these product-specific settings:
        $enable_player = isset($_DATA['_bfp_enable_player']) ? 1 : 0;
        $merge_grouped = isset($_DATA['_bfp_merge_in_grouped']) ? 1 : 0;
        $single_player = isset($_DATA['_bfp_single_player']) ? 1 : 0;
        $preload = (isset($_DATA['_bfp_preload']) && in_array($_DATA['_bfp_preload'], array('none', 'metadata', 'auto'))) ? 
                   sanitize_text_field(wp_unslash($_DATA['_bfp_preload'])) : 'none';
        $play_all = isset($_DATA['_bfp_play_all']) ? 1 : 0;
        $loop = isset($_DATA['_bfp_loop']) ? 1 : 0;
        $volume = (isset($_DATA['_bfp_player_volume']) && is_numeric($_DATA['_bfp_player_volume'])) ? 
                  floatval($_DATA['_bfp_player_volume']) : 1;
        $secure_player = isset($_DATA['_bfp_secure_player']) ? 1 : 0;
        $file_percent = (isset($_DATA['_bfp_file_percent']) && is_numeric($_DATA['_bfp_file_percent'])) ? 
                        intval($_DATA['_bfp_file_percent']) : 0;
        $file_percent = min(max($file_percent, 0), 100);

        // --- SAVE TO DATABASE ---
        update_post_meta($post_id, '_bfp_enable_player', $enable_player);
        update_post_meta($post_id, '_bfp_merge_in_grouped', $merge_grouped);
        update_post_meta($post_id, '_bfp_single_player', $single_player);
        update_post_meta($post_id, '_bfp_preload', $preload);
        update_post_meta($post_id, '_bfp_play_all', $play_all);
        update_post_meta($post_id, '_bfp_loop', $loop);
        update_post_meta($post_id, '_bfp_player_volume', $volume);
        update_post_meta($post_id, '_bfp_secure_player', $secure_player);
        update_post_meta($post_id, '_bfp_file_percent', $file_percent);

        // --- Product-specific audio engine override
        if (isset($_DATA['_bfp_audio_engine'])) {
            $product_audio_engine = sanitize_text_field(wp_unslash($_DATA['_bfp_audio_engine']));
            
            if ($product_audio_engine === 'global' || empty($product_audio_engine)) {
                // Delete the meta so it falls back to global
                delete_post_meta($post_id, '_bfp_audio_engine');
            } elseif (in_array($product_audio_engine, array('mediaelement', 'wavesurfer'))) {
                // Save valid override
                update_post_meta($post_id, '_bfp_audio_engine', $product_audio_engine);
            }
        }
        // --- END: Product-specific audio engine override

        // --- KEEP DEMO LOGIC ---
        $this->save_demo_files($post_id, $_DATA);
        $this->main_plugin->get_config()->clear_product_attrs_cache($post_id);
    }
    
    /**
     * Save demo files for product
     */
    private function save_demo_files($post_id, $_DATA) {
        $own_demos = isset($_DATA['_bfp_own_demos']) ? 1 : 0;
        $direct_own_demos = isset($_DATA['_bfp_direct_own_demos']) ? 1 : 0;
        $demos_list = array();

        if (isset($_DATA['_bfp_file_urls']) && is_array($_DATA['_bfp_file_urls'])) {
            foreach ($_DATA['_bfp_file_urls'] as $_i => $_url) {
                if (!empty($_url)) {
                    $demos_list[] = array(
                        'name' => (!empty($_DATA['_bfp_file_names']) && !empty($_DATA['_bfp_file_names'][$_i])) ? 
                                  sanitize_text_field(wp_unslash($_DATA['_bfp_file_names'][$_i])) : '',
                        'file' => esc_url_raw(wp_unslash(trim($_url))),
                    );
                }
            }
        }

        add_post_meta($post_id, '_bfp_own_demos', $own_demos, true);
        add_post_meta($post_id, '_bfp_direct_own_demos', $direct_own_demos, true);
        add_post_meta($post_id, '_bfp_demos_list', $demos_list, true);
    }

    /**
     * After delete post callback
     */
    public function after_delete_post($post_id, $post_obj) {
        $this->main_plugin->delete_post($post_id);
    }

    /**
     * Render player settings metabox
     */
    public function woocommerce_player_settings() {
        global $post;
        include_once dirname(BFP_PLUGIN_PATH) . '/views/product-options.php';
    }

    /**
     * Show admin notices
     */
    public function show_admin_notices() {
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
     * 
     * @since 1.0.0
     */
    public function ajax_save_settings() {
        // Verify nonce
        if (!isset($_POST['bfp_nonce']) || 
            !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['bfp_nonce'])), 'bfp_updating_plugin_settings')) {
            wp_send_json_error(array(
                'message' => __('Security check failed. Please refresh the page and try again.', 'bandfront-player')
            ));
        }
        
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array(
                'message' => __('You do not have permission to change these settings.', 'bandfront-player')
            ));
        }
        
        // Save settings using existing method
        $messages = $this->save_global_settings();
        
        // Send success response with detailed message
        if (!empty($messages) && $messages['type'] === 'success') {
            wp_send_json_success(array(
                'message' => $messages['message'],
                'details' => isset($messages['details']) ? $messages['details'] : array()
            ));
        } else {
            wp_send_json_error(array(
                'message' => isset($messages['message']) ? $messages['message'] : __('An error occurred while saving settings.', 'bandfront-player')
            ));
        }
    }

    /**
     * Save settings
     */
    public function save_settings() {
        $attrs = array();
        
        // When processing form data, make sure to include cloud tab
        if (isset($_POST['_bfp_cloud_active_tab'])) {
            $attrs['_bfp_cloud_active_tab'] = sanitize_text_field($_POST['_bfp_cloud_active_tab']);
        }
        
        // ...existing code...
    }
}
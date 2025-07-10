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
    
    public function __construct($main_plugin) {
        $this->main_plugin = $main_plugin;
        $this->init_hooks();
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        add_action('admin_menu', array($this, 'menu_links'));
        add_action('admin_init', array($this, 'admin_init'), 99);
        add_action('save_post', array($this, 'save_post'), 10, 3);
        add_action('after_delete_post', array($this, 'after_delete_post'), 10, 2);
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
            if ($this->main_plugin->get_global_attr('_bfp_playback_counter_column', 1)) {
                wp_enqueue_style(
                    'bfp-Playback-counter', 
                    plugin_dir_url(BFP_PLUGIN_PATH) . 'css/style.admin.css', 
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
            if ($this->main_plugin->get_global_attr('_bfp_playback_counter_column', 1) && 
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
            $this->save_global_settings();
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
        
        $player_layouts = $this->main_plugin->get_player_layouts();
        $player_style = (isset($_REQUEST['_bfp_player_layout']) && in_array($_REQUEST['_bfp_player_layout'], $player_layouts)) ? 
                        sanitize_text_field(wp_unslash($_REQUEST['_bfp_player_layout'])) : BFP_DEFAULT_PLAYER_LAYOUT;
        
        $single_player = isset($_REQUEST['_bfp_single_player']) ? 1 : 0;
        $secure_player = isset($_REQUEST['_bfp_secure_player']) ? 1 : 0;
        $file_percent = (isset($_REQUEST['_bfp_file_percent']) && is_numeric($_REQUEST['_bfp_file_percent'])) ? 
                        intval($_REQUEST['_bfp_file_percent']) : 0;
        $file_percent = min(max($file_percent, 0), 100);
        
        $player_controls = $this->main_plugin->get_player_controls();
        $player_controls = (isset($_REQUEST['_bfp_player_controls']) && in_array($_REQUEST['_bfp_player_controls'], $player_controls)) ? 
                           sanitize_text_field(wp_unslash($_REQUEST['_bfp_player_controls'])) : BFP_DEFAULT_PLAYER_CONTROLS;

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
        BFP_Cache_Manager::clear_all_caches();
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
        add_post_meta($post_id, '_bfp_enable_player', $enable_player, true);
        add_post_meta($post_id, '_bfp_merge_in_grouped', $merge_grouped, true);
        add_post_meta($post_id, '_bfp_single_player', $single_player, true);
        add_post_meta($post_id, '_bfp_preload', $preload, true);
        add_post_meta($post_id, '_bfp_play_all', $play_all, true);
        add_post_meta($post_id, '_bfp_loop', $loop, true);
        add_post_meta($post_id, '_bfp_player_volume', $volume, true);
        add_post_meta($post_id, '_bfp_secure_player', $secure_player, true);
        add_post_meta($post_id, '_bfp_file_percent', $file_percent, true);

        // --- Product-specific audio engine override
        $product_audio_engine = '';
        if (isset($_DATA['_bfp_audio_engine']) && 
            in_array($_DATA['_bfp_audio_engine'], array('mediaelement', 'wavesurfer'))) {
            $product_audio_engine = sanitize_text_field(wp_unslash($_DATA['_bfp_audio_engine']));
            update_post_meta($post_id, '_bfp_audio_engine', $product_audio_engine);
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
}
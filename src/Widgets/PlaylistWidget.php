<?php
declare(strict_types=1);

namespace Bandfront\Widgets;

use Bandfront\Core\Config;
use Bandfront\Core\Bootstrap;

/**
 * Bandfront Player - Playlist Widget
 *
 * @package BandfrontPlayer
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Playlist Widget Class
 * Provides a widget for displaying audio playlists
 */
class PlaylistWidget extends \WP_Widget {
    
    private ?Config $config = null;
    
    /**
     * Constructor
     */
    public function __construct() {
        $widgetOps = [
            'classname'   => 'bfp_playlist_widget',
            'description' => __('Includes a playlist with the audio files of products selected', 'bandfront-player'),
        ];

        parent::__construct('bfp_playlist_widget', __('Bandfront Player - Playlist', 'bandfront-player'), $widgetOps);
    }
    
    /**
     * Get config instance
     */
    private function getConfig(): Config {
        if ($this->config === null) {
            $bootstrap = Bootstrap::getInstance();
            $this->config = $bootstrap->getComponent('config');
        }
        return $this->config;
    }

    /**
     * Register the widget
     */
    public static function register(): void {
        if (!class_exists('WooCommerce')) {
            return;
        }
        register_widget(__CLASS__);
    }

    /**
     * Widget form in admin
     *
     * @param array $instance Current settings
     */
    public function form($instance): void {
        $config = $this->getConfig();
        
        $instance = wp_parse_args(
            (array) $instance,
            [
                'title'                     => '',
                'products_ids'              => '',
                'volume'                    => '',
                'highlight_current_product' => 0,
                'continue_playing'          => 0,
                'player_style'              => $config->getState('_bfp_player_layout'),
                'playlist_layout'           => 'new',
            ]
        );

        $title = sanitize_text_field($instance['title']);
        $productsIds = sanitize_text_field($instance['products_ids']);
        $volume = sanitize_text_field($instance['volume']);
        $highlightCurrentProduct = sanitize_text_field($instance['highlight_current_product']);
        $continuePlaying = sanitize_text_field($instance['continue_playing']);
        $playerStyle = sanitize_text_field($instance['player_style']);
        $playlistLayout = sanitize_text_field($instance['playlist_layout']);

        // Get global settings
        $playAll = $config->getState('_bfp_play_all', 0);
        $preload = $config->getState('_bfp_preload', 'metadata');
        
        // Get plugin URL for assets
        $pluginUrl = plugin_dir_url(dirname(dirname(__DIR__)) . '/bandfront-player.php');
        ?>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('title')); ?>">
                <?php esc_html_e('Title', 'bandfront-player'); ?>: 
                <input class="widefat" 
                       id="<?php echo esc_attr($this->get_field_id('title')); ?>" 
                       name="<?php echo esc_attr($this->get_field_name('title')); ?>" 
                       type="text" 
                       value="<?php echo esc_attr($title); ?>" />
            </label>
        </p>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('products_ids')); ?>">
                <?php esc_html_e('Products IDs', 'bandfront-player'); ?>: 
                <input class="widefat" 
                       id="<?php echo esc_attr($this->get_field_id('products_ids')); ?>" 
                       name="<?php echo esc_attr($this->get_field_name('products_ids')); ?>" 
                       type="text" 
                       value="<?php echo esc_attr($productsIds); ?>" 
                       placeholder="<?php esc_attr_e('Products IDs separated by comma, or a * for all', 'bandfront-player'); ?>" />
            </label>
        </p>
        <p>
            <?php esc_html_e('Enter the ID of products separated by comma, or a * symbol to includes all products in the playlist.', 'bandfront-player'); ?>
        </p>
        <p>
            <label>
                <?php esc_html_e('Volume (enter a number between 0 and 1)', 'bandfront-player'); ?>: 
                <input class="widefat" 
                       name="<?php echo esc_attr($this->get_field_name('volume')); ?>" 
                       type="number" 
                       min="0" 
                       max="1" 
                       step="0.01" 
                       value="<?php echo esc_attr($volume); ?>" />
            </label>
        </p>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('playlist_layout')); ?>">
                <?php esc_html_e('Playlist layout', 'bandfront-player'); ?>:
            </label>
        </p>
        <p>
            <label>
                <input name="<?php echo esc_attr($this->get_field_name('playlist_layout')); ?>" 
                       type="radio" 
                       value="new" 
                       <?php checked($playlistLayout, 'new'); ?> 
                       style="float:left; margin-top:8px;" />
                <?php esc_html_e('New layout', 'bandfront-player'); ?>
            </label>
        </p>
        <p>
            <label>
                <input name="<?php echo esc_attr($this->get_field_name('playlist_layout')); ?>" 
                       type="radio" 
                       value="old" 
                       <?php checked($playlistLayout, 'old'); ?> 
                       style="float:left; margin-top:8px;" />
                <?php esc_html_e('Original layout', 'bandfront-player'); ?>
            </label>
        </p>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('player_style')); ?>">
                <?php esc_html_e('Player layout', 'bandfront-player'); ?>:
            </label>
        </p>
        <p>
            <label>
                <input name="<?php echo esc_attr($this->get_field_name('player_style')); ?>" 
                       type="radio" 
                       value="dark" 
                       <?php checked($playerStyle, 'dark'); ?> 
                       style="float:left; margin-top:8px;" />
                <img src="<?php echo esc_url($pluginUrl); ?>css/skins/dark.png" alt="Dark skin" />
            </label>
        </p>
        <p>
            <label>
                <input name="<?php echo esc_attr($this->get_field_name('player_style')); ?>" 
                       type="radio" 
                       value="light" 
                       <?php checked($playerStyle, 'light'); ?> 
                       style="float:left; margin-top:8px;" />
                <img src="<?php echo esc_url($pluginUrl); ?>css/skins/light.png" alt="Light skin" />
            </label>
        </p>
        <p>
            <label>
                <input name="<?php echo esc_attr($this->get_field_name('player_style')); ?>" 
                       type="radio" 
                       value="custom" 
                       <?php checked($playerStyle, 'custom'); ?> 
                       style="float:left; margin-top:16px;" />
                <img src="<?php echo esc_url($pluginUrl); ?>css/skins/custom.png" alt="Custom skin" />
            </label>
        </p>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('play_all')); ?>">
                <?php esc_html_e('Play all', 'bandfront-player'); ?>: 
                <input id="<?php echo esc_attr($this->get_field_id('play_all')); ?>" 
                       name="<?php echo esc_attr($this->get_field_name('play_all')); ?>" 
                       type="checkbox" 
                       <?php checked($playAll, true); ?> />
            </label>
        </p>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('preload')); ?>">
                <?php esc_html_e('Preload', 'bandfront-player'); ?>:
            </label><br />
            <label>
                <input name="<?php echo esc_attr($this->get_field_name('preload')); ?>" 
                       type="radio" 
                       value="none" 
                       <?php checked($preload, 'none'); ?> /> 
                <?php esc_html_e('None', 'bandfront-player'); ?>
            </label>
            <label>
                <input name="<?php echo esc_attr($this->get_field_name('preload')); ?>" 
                       type="radio" 
                       value="metadata" 
                       <?php checked($preload, 'metadata'); ?> /> 
                <?php esc_html_e('Metadata', 'bandfront-player'); ?>
            </label>
            <label>
                <input name="<?php echo esc_attr($this->get_field_name('preload')); ?>" 
                       type="radio" 
                       value="auto" 
                       <?php checked($preload, 'auto'); ?> /> 
                <?php esc_html_e('Auto', 'bandfront-player'); ?>
            </label>
        </p>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('highlight_current_product')); ?>">
                <?php esc_html_e('Highlight the current product', 'bandfront-player'); ?>: 
                <input id="<?php echo esc_attr($this->get_field_id('highlight_current_product')); ?>" 
                       name="<?php echo esc_attr($this->get_field_name('highlight_current_product')); ?>" 
                       type="checkbox" 
                       <?php checked($highlightCurrentProduct, true); ?> />
            </label>
        </p>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('continue_playing')); ?>">
                <?php esc_html_e('Continue playing after navigate', 'bandfront-player'); ?>: 
                <input id="<?php echo esc_attr($this->get_field_id('continue_playing')); ?>" 
                       name="<?php echo esc_attr($this->get_field_name('continue_playing')); ?>" 
                       type="checkbox" 
                       <?php checked($continuePlaying, true); ?> 
                       value="1" />
            </label>
        </p>
        <p>
            <?php esc_html_e('Continue playing the same song at same position after navigate. You can experiment some delay because the music player should to load the audio file again, and in some mobiles devices, where the action of the user is required, the player cannot starting playing automatically.', 'bandfront-player'); ?>
        </p>
        <?php
    }

    /**
     * Update widget settings
     *
     * @param array $newInstance New settings
     * @param array $oldInstance Previous settings
     * @return array|false Updated settings or false if deleted
     */
    public function update($newInstance, $oldInstance): array|false {
        // If all fields are empty, treat as delete
        $fields = [
            'title',
            'products_ids',
            'volume',
            'highlight_current_product',
            'continue_playing',
            'player_style',
            'playlist_layout',
            'play_all',
            'preload'
        ];
        $isEmpty = true;
        foreach ($fields as $field) {
            if (!empty($newInstance[$field])) {
                $isEmpty = false;
                break;
            }
        }
        if ($isEmpty) {
            return false;
        }

        $instance = $oldInstance;
        $instance['title'] = sanitize_text_field($newInstance['title']);
        $instance['products_ids'] = sanitize_text_field($newInstance['products_ids']);
        $instance['volume'] = sanitize_text_field($newInstance['volume']);
        $instance['highlight_current_product'] = !empty($newInstance['highlight_current_product']);
        $instance['continue_playing'] = !empty($newInstance['continue_playing']);
        $instance['player_style'] = sanitize_text_field($newInstance['player_style']);
        $instance['playlist_layout'] = sanitize_text_field($newInstance['playlist_layout'] ?? 'new');

        // Update global settings if needed
        $config = $this->getConfig();
        if (isset($newInstance['play_all'])) {
            $globalSettings = get_option('bfp_global_settings', []);
            $globalSettings['_bfp_play_all'] = !empty($newInstance['play_all']) ? 1 : 0;
            if (isset($newInstance['preload'])) {
                $globalSettings['_bfp_preload'] = (
                    !empty($newInstance['preload']) &&
                    in_array($newInstance['preload'], ['none', 'metadata', 'auto'])
                ) ? $newInstance['preload'] : 'metadata';
            }
            update_option('bfp_global_settings', $globalSettings);
        }

        return $instance;
    }

    /**
     * Display the widget
     *
     * @param array $args Display arguments
     * @param array $instance Widget settings
     */
    public function widget($args, $instance): void {
        if (!is_array($args)) {
            $args = [];
        }
        
        // Extract args for backward compatibility with themes
        $beforeWidget = $args['before_widget'] ?? '';
        $afterWidget = $args['after_widget'] ?? '';
        $beforeTitle = $args['before_title'] ?? '';
        $afterTitle = $args['after_title'] ?? '';

        $title = empty($instance['title']) ? '' : apply_filters('widget_title', $instance['title']);

        // Build shortcode attributes
        $shortcodeAttrs = [];
        
        if (!empty($instance['products_ids'])) {
            $shortcodeAttrs[] = 'products_ids="' . esc_attr($instance['products_ids']) . '"';
        }
        
        if (!empty($instance['highlight_current_product'])) {
            $shortcodeAttrs[] = 'highlight_current_product="1"';
        }
        
        if (!empty($instance['continue_playing'])) {
            $shortcodeAttrs[] = 'continue_playing="1"';
        }
        
        if (!empty($instance['player_style'])) {
            $shortcodeAttrs[] = 'player_style="' . esc_attr($instance['player_style']) . '"';
        }
        
        if (!empty($instance['playlist_layout'])) {
            $shortcodeAttrs[] = 'layout="' . esc_attr($instance['playlist_layout']) . '"';
        }
        
        if (!empty($instance['volume'])) {
            $volume = floatval($instance['volume']);
            if ($volume > 0) {
                $shortcodeAttrs[] = 'volume="' . min(1, $volume) . '"';
            }
        }
        
        // Always add controls attribute
        $shortcodeAttrs[] = 'controls="track"';
        
        // Build the shortcode
        $shortcode = '[bfp-playlist ' . implode(' ', $shortcodeAttrs) . ']';
        
        // Process the shortcode
        $output = do_shortcode($shortcode);

        if (empty($output)) {
            return;
        }

        // Enqueue widget-specific styles
        $this->enqueueWidgetAssets();

        echo $beforeWidget; // phpcs:ignore WordPress.Security.EscapeOutput
        if (!empty($title)) {
            echo $beforeTitle . esc_html($title) . $afterTitle; // phpcs:ignore WordPress.Security.EscapeOutput
        }
        echo $output; // phpcs:ignore WordPress.Security.EscapeOutput -- Already escaped in shortcode handler
        echo $afterWidget; // phpcs:ignore WordPress.Security.EscapeOutput
    }
    
    /**
     * Enqueue widget-specific assets
     */
    private function enqueueWidgetAssets(): void {
        $pluginUrl = plugin_dir_url(dirname(dirname(__DIR__)) . '/bandfront-player.php');
        
        // Only enqueue if main styles exist
        if (wp_style_is('bfp-style', 'registered')) {
            wp_enqueue_style(
                'bfp-widget-style',
                $pluginUrl . 'widgets/playlist_widget/css/style.css',
                ['bfp-style'],
                BFP_VERSION
            );
        }
        
        // Only enqueue if main scripts exist
        if (wp_script_is('bfp-engine', 'registered')) {
            wp_enqueue_script(
                'bfp-widget',
                $pluginUrl . 'widgets/playlist_widget/js/script.js',
                ['jquery', 'bfp-engine'],
                BFP_VERSION,
                true
            );
        }
    }
}

// Register widget hook
add_action('widgets_init', [PlaylistWidget::class, 'register']);
<?php
declare(strict_types=1);

namespace Bandfront\Audio;

use Bandfront\Core\Config;
use Bandfront\Renderer;
use Bandfront\Utils\Debug;

/**
 * Player management functionality for Bandfront Player
 *
 * @package Bandfront\Audio
 * @since 2.0.0
 */
class Player {
    
    private Config $config;
    private Renderer $renderer;
    private Audio $audio;
    
    private bool $enqueuedResources = false;
    private bool $insertedPlayer = false;
    private bool $insertPlayer = true;
    private bool $insertMainPlayer = true;
    private bool $insertAllPlayers = true;
    
    /**
     * Constructor - accepts only needed dependencies
     */
    public function __construct(Config $config, Renderer $renderer, Audio $audio) {
        $this->config = $config;
        $this->renderer = $renderer;
        $this->audio = $audio;
    }
    
    /**
     * Generate player HTML
     */
    public function getPlayer(string $audioUrl, array $args = []): string {
        $productId = $args['product_id'] ?? 0;
        $playerControls = $args['player_controls'] ?? '';
        $playerStyle = $args['player_style'] ?? $this->config->getState('_bfp_player_layout');
        $mediaType = $args['media_type'] ?? 'mp3';
        $id = $args['id'] ?? '0';  // Allow string IDs
        $duration = $args['duration'] ?? false;
        $preload = $args['preload'] ?? 'none';
        $volume = $args['volume'] ?? 1;
        
        // Apply filters
        $preload = apply_filters('bfp_preload', $preload, $audioUrl);
        
        // Generate unique player ID
        $playerId = 'bfp-player-' . $productId . '-' . $id . '-' . uniqid();
        
        // Build player HTML
        $playerHtml = '<audio id="' . esc_attr($playerId) . '" ';
        $playerHtml .= 'class="bfp-player ' . esc_attr($playerStyle) . '" ';
        $playerHtml .= 'data-product-id="' . esc_attr($productId) . '" ';
        $playerHtml .= 'data-file-index="' . esc_attr($id) . '" ';
        $playerHtml .= 'preload="' . esc_attr($preload) . '" ';
        $playerHtml .= 'data-volume="' . esc_attr($volume) . '" ';
        
        if ($playerControls) {
            $playerHtml .= 'data-controls="' . esc_attr($playerControls) . '" ';
        }
        
        if ($duration) {
            $playerHtml .= 'data-duration="' . esc_attr($duration) . '" ';
        }
        
        $playerHtml .= '>';
        $playerHtml .= '<source src="' . esc_url($audioUrl) . '" type="audio/' . esc_attr($mediaType) . '" />';
        $playerHtml .= '</audio>';
        
        return apply_filters('bfp_player_html', $playerHtml, $audioUrl, $args);
    }
    
    /**
     * Include main player for a product
     */
    public function includeMainPlayer($product = '', bool $echo = true): string {
        $output = '';

        if (is_admin()) {
            return $output;
        }

        if (!$this->getInsertPlayer() || !$this->getInsertMainPlayer()) {
            return $output;
        }
        
        if (is_numeric($product)) {
            $product = wc_get_product($product);
        }
        if (!is_object($product)) {
            $product = wc_get_product();
        }

        if (empty($product)) {
            return '';
        }

        // Use getState for single value retrieval
        $onCover = $this->config->getState('_bfp_on_cover');
        if ($onCover && (is_shop() || is_product_category() || is_product_tag())) {
            // Don't render the regular player on shop pages when on_cover is enabled
            // The play button will be handled by the hook manager
            return '';
        }

        $files = $this->mainPlugin->getFiles()->getProductFilesInternal([
            'product' => $product,
            'first'   => true,
        ]);
        
        // Debug output
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('BFP Debug - Product ID: ' . $product->get_id());
            error_log('BFP Debug - Files found: ' . print_r($files, true));
        }
        
        if (!empty($files)) {
            $id = $product->get_id();

            // Use getState for single value with product context
            $showIn = $this->config->getState('_bfp_show_in', null, $id);
            if (
                ('single' == $showIn && (!function_exists('is_product') || !is_product())) ||
                ('multiple' == $showIn && (function_exists('is_product') && is_product()) && get_queried_object_id() == $id)
            ) {
                return $output;
            }
            
            // CONTEXT-AWARE CONTROLS: button on shop, full on product pages
            if (function_exists('is_product') && is_product()) {
                // Product page - always use full controls
                $playerControls = '';  // Empty string means 'all' controls
            } else {
                // Shop/archive pages - always use button only
                $playerControls = 'track';  // 'track' means button controls
            }
            
            // Get all player settings using bulk fetch for performance
            $settings = $this->config->getStates([
                '_bfp_preload',
                '_bfp_player_layout',
                '_bfp_player_volume'
            ], $id);
            
            $this->enqueueResources();

            $file = reset($files);
            $index = key($files);  // This can be a string like "0_123"
            $duration = $this->mainPlugin->getAudioCore()->getDurationByUrl($file['file']);
            $audioUrl = $this->mainPlugin->getAudioCore()->generateAudioUrl($id, $index, $file);
            $audioTag = apply_filters(
                'bfp_audio_tag',
                $this->getPlayer(
                    $audioUrl,
                    [
                        'product_id'      => $id,
                        'player_controls' => $playerControls,  // Use context-aware controls
                        'player_style'    => $settings['_bfp_player_layout'],
                        'media_type'      => $file['media_type'],
                        'id'              => $index,  // Pass the file index
                        'duration'        => $duration,
                        'preload'         => $settings['_bfp_preload'],
                        'volume'          => $settings['_bfp_player_volume'],
                    ]
                ),
                $id,
                $index,
                $audioUrl
            );

            do_action('bfp_before_player_shop_page', $id);

            $output = '<div class="bfp-player-container product-' . esc_attr($file['product']) . '">' . $audioTag . '</div>';
            if ($echo) {
                print $output; // phpcs:ignore WordPress.Security.EscapeOutput
            }

            do_action('bfp_after_player_shop_page', $id);

            return $output; // phpcs:ignore WordPress.Security.EscapeOutput
        }
        
        return '';
    }
    
    /**
     * Include all players for a product
     * Moved from player-renderer.php
     */
    public function includeAllPlayers($product = ''): void {
        if (!$this->getInsertPlayer() || !$this->getInsertAllPlayers() || is_admin()) {
            return;
        }

        if (!is_object($product)) {
            $product = wc_get_product();
        }

        if (empty($product)) {
            return;
        }

        $files = $this->mainPlugin->getFiles()->getProductFilesInternal([
            'product' => $product,
            'all'     => true,
        ]);
        
        if (!empty($files)) {
            $id = $product->get_id();

            // Use getState for single value with product context
            $showIn = $this->config->getState('_bfp_show_in', null, $id);
            if (
                ('single' == $showIn && !is_singular()) ||
                ('multiple' == $showIn && is_singular())
            ) {
                return;
            }
            
            // Get all player settings using bulk fetch
            $settings = $this->config->getStates([
                '_bfp_preload',
                '_bfp_player_layout',
                '_bfp_player_volume',
                '_bfp_player_title',
                '_bfp_loop',
                '_bfp_merge_in_grouped'
            ], $id);
            
            $this->enqueueResources();
            
            // CONTEXT-AWARE CONTROLS: Always use full controls on product pages
            if (function_exists('is_product') && is_product()) {
                // Product page - always use full controls ('all')
                $playerControls = 'all';
            } else {
                // Shop/archive pages - use button only
                $playerControls = 'button';
            }
            
            $mergeGroupedClass = ($settings['_bfp_merge_in_grouped']) ? 'merge_in_grouped_products' : '';

            $counter = count($files);

            do_action('bfp_before_players_product_page', $id);
            
            if (1 == $counter) {
                $playerControls = ('button' == $playerControls) ? 'track' : '';
                $file = reset($files);
                $index = key($files);
                $duration = $this->mainPlugin->getAudioCore()->getDurationByUrl($file['file']);
                $audioUrl = $this->mainPlugin->getAudioCore()->generateAudioUrl($id, $index, $file);
                $audioTag = apply_filters(
                    'bfp_audio_tag',
                    $this->getPlayer(
                        $audioUrl,
                        [
                            'product_id'      => $id,
                            'player_controls' => $playerControls,
                            'player_style'    => $settings['_bfp_player_layout'],
                            'media_type'      => $file['media_type'],
                            'duration'        => $duration,
                            'preload'         => $settings['_bfp_preload'],
                            'volume'          => $settings['_bfp_player_volume'],
                        ]
                    ),
                    $id,
                    $index,
                    $audioUrl
                );
                $title = esc_html(($settings['_bfp_player_title']) ? apply_filters('bfp_file_name', $file['name'], $id, $index) : '');
                print '<div class="bfp-player-container ' . esc_attr($mergeGroupedClass) . ' product-' . esc_attr($file['product']) . '" ' . ($settings['_bfp_loop'] ? 'data-loop="1"' : '') . '>' . $audioTag . '</div><div class="bfp-player-title" data-audio-url="' . esc_attr($audioUrl) . '">' . wp_kses_post($title) . '</div><div style="clear:both;"></div>'; // phpcs:ignore WordPress.Security.EscapeOutput
            } elseif ($counter > 1) {
                // Use the renderer for multiple files
                $singlePlayer = intval($this->mainPlugin->getConfig()->getState('_bfp_single_player', 0, $id));
                
                // Add player_controls to settings for renderer
                $settings['player_controls'] = $playerControls;
                $settings['single_player'] = $singlePlayer;
                
                print $this->getRenderer()->renderPlayerTable($files, $id, $settings); // phpcs:ignore WordPress.Security.EscapeOutput
            }
            
            // Fix: Check if WooCommerce integration exists
            $purchased = false;
            $woocommerce = $this->mainPlugin->getWooCommerce();
            if ($woocommerce) {
                $purchased = $woocommerce->woocommerceUserProduct($id);
            }
            
            $message = $this->mainPlugin->getConfig()->getState('_bfp_message');
            if (!empty($message) && false === $purchased) {
                print '<div class="bfp-message">' . wp_kses_post(__($message, 'bandfront-player')) . '</div>'; // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText
            }
            do_action('bfp_after_players_product_page', $id);
        }
    }
    
    /**
     * Get product files - public interface
     * This is the method that other classes should use
     */
    public function getProductFiles($productId): array {
        // Delegate to Files utility
        return $this->mainPlugin->getFiles()->getProductFiles($productId);
    }
    
    /**
     * Get enqueued resources state
     */
    public function getEnqueuedResources(): bool {
        return $this->enqueuedResources;
    }
    
    /**
     * Set enqueued resources state
     */
    public function setEnqueuedResources(bool $value): void {
        $this->enqueuedResources = $value;
    }
    
    /**
     * Get insert player flag
     */
    public function getInsertPlayer(): bool {
        return $this->insertPlayer;
    }
    
    /**
     * Set insert player flag
     */
    public function setInsertPlayer(bool $value): void {
        $this->insertPlayer = $value;
    }
    
    /**
     * Get inserted player state
     */
    public function getInsertedPlayer(): bool {
        return $this->insertedPlayer;
    }
    
    /**
     * Set inserted player state
     */
    public function setInsertedPlayer(bool $value): void {
        $this->insertedPlayer = $value;
    }
    
    /**
     * Get insert main player flag
     */
    public function getInsertMainPlayer(): bool {
        return $this->insertMainPlayer;
    }
    
    /**
     * Set insert main player flag
     */
    public function setInsertMainPlayer(bool $value): void {
        $this->insertMainPlayer = $value;
    }
    
    /**
     * Get insert all players flag
     */
    public function getInsertAllPlayers(): bool {
        return $this->insertAllPlayers;
    }
    
    /**
     * Set insert all players flag
     */
    public function setInsertAllPlayers(bool $value): void {
        $this->insertAllPlayers = $value;
    }
    
    /**
     * Check if current device is iOS
     */
    private function isIosDevice(): bool {
        static $isIos = null;
        
        if ($isIos === null) {
            $isIos = isset($_SERVER['HTTP_USER_AGENT']) && 
                     preg_match('/(iPad|iPhone|iPod)/i', $_SERVER['HTTP_USER_AGENT']);
        }
        
        return $isIos;
    }
    
    /**
     * Enqueue player resources
     */
    public function enqueueResources(): void {
        if ($this->enqueuedResources) {
            return;
        }
        
        global $BandfrontPlayer;
        
        // Use getState for single value retrieval
        $audioEngine = $BandfrontPlayer->getConfig()->getState('_bfp_audio_engine');
        
        // Enqueue base styles
        wp_enqueue_style(
            'bfp-style', 
            plugin_dir_url(dirname(dirname(__FILE__))) . 'css/style.css', 
            [], 
            BFP_VERSION
        );
        
        // Enqueue jQuery
        wp_enqueue_script('jquery');
        
        if ($audioEngine === 'wavesurfer') {
            // Check if WaveSurfer is available locally
            $wavesurferPath = plugin_dir_path(dirname(dirname(__FILE__))) . 'vendor/wavesurfer/wavesurfer.min.js';
            
            if (file_exists($wavesurferPath)) {
                // Enqueue local WaveSurfer.js
                wp_enqueue_script(
                    'wavesurfer',
                    plugin_dir_url(dirname(dirname(__FILE__))) . 'vendor/wavesurfer/wavesurfer.min.js',
                    [],
                    '7.9.9',
                    true
                );
            } else {
                // Fallback to CDN if local file doesn't exist
                wp_enqueue_script(
                    'wavesurfer',
                    'https://unpkg.com/wavesurfer.js@7/dist/wavesurfer.min.js',
                    [],
                    '7.9.9',
                    true
                );
            }
            
            // Enqueue WaveSurfer integration
            wp_enqueue_script(
                'bfp-wavesurfer-integration',
                plugin_dir_url(dirname(dirname(__FILE__))) . 'js/wavesurfer.js',
                ['jquery', 'wavesurfer'],
                BFP_VERSION,
                true
            );
        } elseif ($audioEngine === 'html5') {
            // Pure HTML5 - no additional libraries needed
        } else {
            // MediaElement.js
            wp_enqueue_style('wp-mediaelement');
            wp_enqueue_script('wp-mediaelement');
            
            $selectedSkin = $this->config->getState('_bfp_player_layout');
            
            // Validate skin selection
            if (!in_array($selectedSkin, ['dark', 'light', 'custom'])) {
                $selectedSkin = 'dark';
            }
            
            // Enqueue selected skin CSS file
            wp_enqueue_style(
                'bfp-skin-' . $selectedSkin,
                plugin_dir_url(dirname(dirname(__FILE__))) . 'css/skins/' . $selectedSkin . '.css',
                ['wp-mediaelement'],
                BFP_VERSION
            );
        }
        
        // Enqueue main engine script
        wp_enqueue_script(
            'bfp-engine',
            plugin_dir_url(dirname(dirname(__FILE__))) . 'js/engine.js',
            ['jquery'],
            BFP_VERSION,
            true
        );
        
        // Localize script with settings
        $settingsKeys = [
            '_bfp_play_simultaneously',
            '_bfp_ios_controls',
            '_bfp_fade_out',
            '_bfp_on_cover',
            '_bfp_enable_visualizations',
            '_bfp_player_layout'
        ];
        
        $settings = $this->config->getStates($settingsKeys);
        
        $jsSettings = [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'audio_engine' => $audioEngine,
            'play_simultaneously' => $settings['_bfp_play_simultaneously'],
            'ios_controls' => $settings['_bfp_ios_controls'],
            'fade_out' => $settings['_bfp_fade_out'],
            'on_cover' => $settings['_bfp_on_cover'],
            'visualizations' => $settings['_bfp_enable_visualizations'],
            'player_skin' => $settings['_bfp_player_layout']
        ];
        
        wp_localize_script('bfp-engine', 'bfp_global_settings', $jsSettings);
        
        $this->enqueuedResources = true;
    }
    
    /**
     * Render player table layout for multiple files
     * 
     * @param array $files Audio files to render
     * @param int $productId Product ID
     * @param array $settings Player settings
     * @return string Rendered HTML
     */
    public function renderPlayerTable(array $files, int $productId, array $settings): string {
        if (empty($files) || count($files) < 2) {
            Debug::log('Player: Not rendering table - insufficient files', ['count' => count($files), 'productId' => $productId]); // DEBUG-REMOVE
            return '';
        }
        
        Debug::log('Player: Rendering player table', ['files_count' => count($files), 'productId' => $productId]); // DEBUG-REMOVE
        
        $mergeGroupedClass = ($settings['_bfp_merge_in_grouped']) ? 'merge_in_grouped_products' : '';
        $singlePlayer = $settings['single_player'] ?? 0;
        
        $output = '<table class="bfp-player-list ' . $mergeGroupedClass . ($singlePlayer ? ' bfp-single-player ' : '') . '" ' . 
                   ($settings['_bfp_loop'] ? 'data-loop="1"' : '') . '>';
        
        $counter = count($files);
        $firstPlayerClass = 'bfp-first-player';
        
        foreach ($files as $index => $file) {
            Debug::log('Player: Processing file in table', ['index' => $index, 'name' => $file['name'] ?? 'unnamed']); // DEBUG-REMOVE
            
            $evenOdd = (1 == $counter % 2) ? 'bfp-odd-row' : 'bfp-even-row';
            $counter--;
            
            $audioUrl = $this->audio->generateAudioUrl($productId, $index, $file);
            $duration = $this->audio->getDurationByUrl($file['file']);
            
            $audioTag = apply_filters(
                'bfp_audio_tag',
                $this->getPlayer(
                    $audioUrl,
                    [
                        'product_id'      => $productId,
                        'player_style'    => $settings['_bfp_player_layout'],
                        'player_controls' => ('all' != $settings['player_controls']) ? 'track' : '',
                        'media_type'      => $file['media_type'],
                        'duration'        => $duration,
                        'preload'         => $settings['_bfp_preload'],
                        'volume'          => $settings['_bfp_player_volume'],
                        'id'              => $index,
                    ]
                ),
                $productId,
                $index,
                $audioUrl
            );
            
            // Title processing
            $title = '';
            $playerTitleEnabled = $settings['_bfp_player_title'] ?? 1;
            
            if ($playerTitleEnabled) {
                $rawTitle = $file['name'] ?? '';
                $processedTitle = apply_filters('bfp_file_name', $rawTitle, $productId, $index);
                $title = esc_html($processedTitle);
            }
            
            $output .= $this->renderPlayerRow($audioTag, $title, $duration, $evenOdd, 
                                            $file['product'], $firstPlayerClass, 
                                            $counter, $settings, $singlePlayer);
            
            $firstPlayerClass = '';
        }
        
        $output .= '</table>';
        
        Debug::log('Player: Table rendering completed', ['productId' => $productId]); // DEBUG-REMOVE
        
        return $output;
    }
    
    /**
     * Render a single player row
     * 
     * @param string $audioTag Audio element HTML
     * @param string $title Track title
     * @param string $duration Track duration
     * @param string $evenOdd Row class
     * @param int $productId Product ID
     * @param string $firstPlayerClass First player class
     * @param int $counter Row counter
     * @param array $settings Player settings
     * @param int $singlePlayer Single player mode
     * @return string Rendered row HTML
     */
    private function renderPlayerRow(string $audioTag, string $title, string $duration, string $evenOdd, 
                                   int $productId, string $firstPlayerClass, int $counter, 
                                   array $settings, int $singlePlayer): string {
        Debug::log('Player: Rendering row', [
            'productId' => $productId, 
            'title' => $title, 
            'counter' => $counter
        ]); // DEBUG-REMOVE
        
        $output = '<tr class="' . esc_attr($evenOdd) . ' product-' . esc_attr($productId) . '">';
        
        if ('all' != $settings['player_controls']) {
            $output .= '<td class="bfp-column-player-' . esc_attr($settings['_bfp_player_layout']) . '">';
            $output .= '<div class="bfp-player-container ' . $firstPlayerClass . '" data-player-id="' . esc_attr($counter) . '">';
            $output .= $audioTag;
            $output .= '</div></td>';
            $output .= '<td class="bfp-player-title bfp-column-player-title" data-player-id="' . esc_attr($counter) . '">';
            $output .= wp_kses_post($title);
            $output .= '</td>';
            $output .= '<td class="bfp-file-duration" style="text-align:right;font-size:16px;">';
            $output .= esc_html($duration);
            $output .= '</td>';
        } else {
            $output .= '<td>';
            $output .= '<div class="bfp-player-container ' . $firstPlayerClass . '" data-player-id="' . esc_attr($counter) . '">';
            $output .= $audioTag;
            $output .= '</div>';
            $output .= '<div class="bfp-player-title bfp-column-player-title" data-player-id="' . esc_attr($counter) . '">';
            $output .= wp_kses_post($title);
            if ($singlePlayer) {
                $output .= '<span class="bfp-file-duration">' . esc_html($duration) . '</span>';
            }
            $output .= '</div>';
            $output .= '</td>';
        }
        
        $output .= '</tr>';
        
        return $output;
    }
    
    /**
     * Generate HTML for audio player
     * 
     * @param array $args Player arguments
     * @return string Generated HTML
     */
    private function generatePlayerHtml(array $args): string {
        $class = 'bfp-player ' . ($args['player_style'] ?? '');
        $preload = 'none';
        
        // Smart preload based on context
        $preload = $this->mainPlugin->getAudioCore()->getSmartPreload(
            $args['single_player'] ?? false,
            true // Always show duration in players
        );
        
        // In the audio tag generation
        $html = sprintf(
            '<audio class="%s" %s preload="%s" %s>',
            esc_attr($class),
            $args['controls'] ? 'controls' : '',
            esc_attr($preload),
            $args['loop'] ? 'loop' : ''
        );
        
        // ...existing code...
    }
    
    /**
     * Determine if player should be shown for product
     * 
     * @param object $product WooCommerce product object
     * @return bool Whether to show player
     */
    private function shouldShowPlayer($product): bool {
        if (!is_object($product) || !method_exists($product, 'get_id')) {
            return false;
        }
        
        $productId = $product->get_id();
        
        // Check if player is enabled
        if (!$this->config->getState('_bfp_enable_player', true, $productId)) {
            return false;
        }
        
        // Use smart context detection
        return $this->mainPlugin->smartPlayContext($productId);
    }
    
    /**
     * Check if track titles should be shown based on context
     * 
     * @return bool True if titles should be shown
     */
    private function shouldShowTitles(): bool {
        // Always hide titles on main shop page and archives
        if (is_shop() || is_product_category() || is_product_tag()) {
            return false;
        }
        
        // Always hide on home page if it shows products
        if (is_front_page() && (is_shop() || has_shortcode(get_post_field('post_content', get_the_ID()), 'products'))) {
            return false;
        }
        
        // Show titles everywhere else (product pages, cart, etc.)
        return true;
    }
    
    /**
     * Render player
     * 
     * @param array $attrs Player attributes
     * @return string Generated HTML
     */
    public function render(array $attrs): string {
        $files = $attrs['files'] ?? [];
        $id = $attrs['product_id'] ?? 0;
        $settings = $attrs['settings'] ?? [];
        
        // Smart title detection replaces the config check
        $showTitles = $this->shouldShowTitles();
        
        $html = '';
        
        foreach ($files as $index => $file) {
            $duration = $this->mainPlugin->getAudioCore()->getDurationByUrl($file['file']);
            $audioUrl = $this->mainPlugin->getAudioCore()->generateAudioUrl($id, $index, $file);
            
            // In the player generation code, use $showTitles instead of checking config
            if ($showTitles && !empty($file['name'])) {
                $html .= '<span class="bfp-track-title">' . esc_html($file['name']) . '</span>';
            }
            
            $html .= $this->getPlayer(
                $audioUrl,
                [
                    'product_id'      => $id,
                    'player_controls' => $settings['player_controls'] ?? '',
                    'player_style'    => $settings['_bfp_player_layout'] ?? '',
                    'media_type'      => $file['media_type'],
                    'id'              => $index,
                    'duration'        => $duration,
                    'preload'         => $settings['_bfp_preload'] ?? 'none',
                    'volume'          => $settings['_bfp_player_volume'] ?? 1,
                ]
            );
        }
        
        return $html;
    }
    
    /**
     * Generate JavaScript for player functionality
     */
    private function generatePlayerScript(array $args): string {
        $script = '<script type="text/javascript">';
        
        // Always stop other players when one starts - no need to check setting
        $script .= '
        jQuery(document).ready(function($) {
            // Stop all other audio/video when playing
            $("audio, video").on("play", function() {
                var currentPlayer = this;
                $("audio, video").each(function() {
                    if (this !== currentPlayer) {
                        this.pause();
                    }
                });
            });
        });
        ';
        
        $script .= '</script>';
        return $script;
    }
    
    /**
     * Get player volume for a product
     */
    private function getPlayerVolume(int $productId): float {
        // Get product-specific volume, default to 1.0
        $volume = $this->mainPlugin->getConfig()->getState('_bfp_player_volume', 1.0, $productId);
        
        // Ensure it's within valid range
        return max(0, min(1, floatval($volume)));
    }
}
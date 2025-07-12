<?php
/**
 * Player management functionality for Bandfront Player
 *
 * @package BandfrontPlayer
 * @since 0.1
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * BFP Player Manager Class
 * Central hub for all player operations
 */
class BFP_Player {
    
    private $main_plugin;
    private $_enqueued_resources = false;
    private $_inserted_player = false;
    private $_insert_player = true;
    private $_insert_main_player = true;
    private $_insert_all_players = true;
    private $_preload_times = 0;
    
    public function __construct($main_plugin) {
        $this->main_plugin = $main_plugin;
    }
    
    /**
     * Generate player HTML
     */
    public function get_player($audio_url, $args = array()) {
        if (!is_array($args)) $args = array();
        
        $product_id = isset($args['product_id']) ? $args['product_id'] : 0;
        $player_controls = isset($args['player_controls']) ? $args['player_controls'] : '';
        $player_style = isset($args['player_style']) ? $args['player_style'] : BFP_DEFAULT_PLAYER_LAYOUT;
        $media_type = isset($args['media_type']) ? $args['media_type'] : 'mp3';
        $id = isset($args['id']) ? $args['id'] : 0;
        $duration = isset($args['duration']) ? $args['duration'] : false;
        $preload = isset($args['preload']) ? $args['preload'] : 'none';
        $volume = isset($args['volume']) ? $args['volume'] : 1;
        
        // Apply filters
        $preload = apply_filters('bfp_preload', $preload, $audio_url);
        
        // Generate unique player ID
        $player_id = 'bfp-player-' . $product_id . '-' . $id . '-' . uniqid();
        
        // Build player HTML
        $player_html = '<audio id="' . esc_attr($player_id) . '" ';
        $player_html .= 'class="bfp-player ' . esc_attr($player_style) . '" ';
        $player_html .= 'data-product-id="' . esc_attr($product_id) . '" ';
        $player_html .= 'data-file-index="' . esc_attr($id) . '" ';
        $player_html .= 'preload="' . esc_attr($preload) . '" ';
        $player_html .= 'data-volume="' . esc_attr($volume) . '" ';
        
        if ($player_controls) {
            $player_html .= 'data-controls="' . esc_attr($player_controls) . '" ';
        }
        
        if ($duration) {
            $player_html .= 'data-duration="' . esc_attr($duration) . '" ';
        }
        
        $player_html .= '>';
        $player_html .= '<source src="' . esc_url($audio_url) . '" type="audio/' . esc_attr($media_type) . '" />';
        $player_html .= '</audio>';
        
        return apply_filters('bfp_player_html', $player_html, $audio_url, $args);
    }
    
    /**
     * Include main player for a product
     * Moved from player-renderer.php
     */
    public function include_main_player($product = '', $_echo = true) {
        $output = '';

        if ( is_admin() ) return $output;

        if ( ! $this->get_insert_player() || ! $this->get_insert_main_player() ) {
            return $output;
        }
        
        if ( is_numeric( $product ) ) {
            $product = wc_get_product( $product );
        }
        if ( ! is_object( $product ) ) {
            $product = wc_get_product();
        }

        if ( empty( $product ) ) {
            return '';
        }

        // Check if on_cover is enabled for shop pages using state handler
        $on_cover = $this->main_plugin->get_config()->get_state('_bfp_on_cover');
        if ($on_cover && (is_shop() || is_product_category() || is_product_tag())) {
            // Don't render the regular player on shop pages when on_cover is enabled
            // The play button will be handled by the hook manager
            return '';
        }

        $files = $this->_get_product_files(
            array(
                'product' => $product,
                'first'   => true,
            )
        );
        if ( ! empty( $files ) ) {
            $id = $product->get_id();

            // Use state handler for product-specific settings
            $show_in = $this->main_plugin->get_config()->get_state( '_bfp_show_in', null, $id );
            if (
                ( 'single' == $show_in && ( ! function_exists( 'is_product' ) || ! is_product() ) ) ||
                ( 'multiple' == $show_in && ( function_exists( 'is_product' ) && is_product() ) && get_queried_object_id() == $id )
            ) {
                return $output;
            }
            
            // CONTEXT-AWARE CONTROLS: button on shop, full on product pages
            if (function_exists('is_product') && is_product()) {
                // Product page - always use full controls
                $player_controls = '';  // Empty string means 'all' controls
            } else {
                // Shop/archive pages - always use button only
                $player_controls = 'track';  // 'track' means button controls
            }
            
            // Get all player settings using bulk fetch
            $settings = $this->main_plugin->get_config()->get_states(array(
                '_bfp_preload',
                '_bfp_player_layout',
                '_bfp_player_volume'
            ), $id);
            
            $this->enqueue_resources();

            $file = reset( $files );
            $index = key( $files );
            $duration = $this->main_plugin->get_audio_core()->get_duration_by_url( $file['file'] );
            $audio_url = $this->main_plugin->get_audio_core()->generate_audio_url( $id, $index, $file );
            $audio_tag = apply_filters(
                'bfp_audio_tag',
                $this->get_player(
                    $audio_url,
                    array(
                        'product_id'      => $id,
                        'player_controls' => $player_controls,  // Use context-aware controls
                        'player_style'    => $settings['_bfp_player_layout'],
                        'media_type'      => $file['media_type'],
                        'duration'        => $duration,
                        'preload'         => $settings['_bfp_preload'],
                        'volume'          => $settings['_bfp_player_volume'],
                    )
                ),
                $id,
                $index,
                $audio_url
            );

            do_action( 'bfp_before_player_shop_page', $id );

            $output = '<div class="bfp-player-container product-' . esc_attr( $file['product'] ) . '">' . $audio_tag . '</div>';
            if ( $_echo ) {
                print $output; // phpcs:ignore WordPress.Security.EscapeOutput
            }

            do_action( 'bfp_after_player_shop_page', $id );

            return $output; // phpcs:ignore WordPress.Security.EscapeOutput
        }
    }
    
    /**
     * Include all players for a product
     * Moved from player-renderer.php
     */
    public function include_all_players($product = '') {
        if ( ! $this->get_insert_player() || ! $this->get_insert_all_players() || is_admin() ) {
            return;
        }

        if ( ! is_object( $product ) ) {
            $product = wc_get_product();
        }

        if ( empty( $product ) ) {
            return;
        }

        $files = $this->_get_product_files(
            array(
                'product' => $product,
                'all'     => true,
            )
        );
        if ( ! empty( $files ) ) {
            $id = $product->get_id();

            // Use state handler for settings
            $show_in = $this->main_plugin->get_config()->get_state( '_bfp_show_in', null, $id );
            if (
                ( 'single' == $show_in && ! is_singular() ) ||
                ( 'multiple' == $show_in && is_singular() )
            ) {
                return;
            }
            
            // Get all player settings using bulk fetch
            $settings = $this->main_plugin->get_config()->get_states(array(
                '_bfp_preload',
                '_bfp_player_layout',
                '_bfp_player_volume',
                '_bfp_player_title',
                '_bfp_loop',
                '_bfp_merge_in_grouped'
            ), $id);
            
            $this->enqueue_resources();
            
            // CONTEXT-AWARE CONTROLS: Always use full controls on product pages
            if (function_exists('is_product') && is_product()) {
                // Product page - always use full controls ('all')
                $player_controls = 'all';
            } else {
                // Shop/archive pages - use button only
                $player_controls = 'button';
            }
            
            $merge_grouped_clss = ( $settings['_bfp_merge_in_grouped'] ) ? 'merge_in_grouped_products' : '';

            $counter = count( $files );

            do_action( 'bfp_before_players_product_page', $id );
            if ( 1 == $counter ) {
                    $player_controls = ( 'button' == $player_controls ) ? 'track' : '';
                    $file            = reset( $files );
                    $index           = key( $files );
                    $duration        = $this->main_plugin->get_audio_core()->get_duration_by_url( $file['file'] );
                    $audio_url       = $this->main_plugin->get_audio_core()->generate_audio_url( $id, $index, $file );
                    $audio_tag       = apply_filters(
                        'bfp_audio_tag',
                        $this->get_player(
                            $audio_url,
                            array(
                                'product_id'      => $id,
                                'player_controls' => $player_controls,
                                'player_style'    => $settings['_bfp_player_layout'],
                                'media_type'      => $file['media_type'],
                                'duration'        => $duration,
                                'preload'         => $settings['_bfp_preload'],
                                'volume'          => $settings['_bfp_player_volume'],
                            )
                        ),
                        $id,
                        $index,
                        $audio_url
                    );
                    $title           = esc_html( ( $settings['_bfp_player_title'] ) ? apply_filters( 'bfp_file_name', $file['name'], $id, $index ) : '' );
                    print '<div class="bfp-player-container ' . esc_attr( $merge_grouped_clss ) . ' product-' . esc_attr( $file['product'] ) . '" ' . ( $settings['_bfp_loop'] ? 'data-loop="1"' : '' ) . '>' . $audio_tag . '</div><div class="bfp-player-title" data-audio-url="' . esc_attr( $audio_url ) . '">' . wp_kses_post( $title ) . '</div><div style="clear:both;"></div>'; // phpcs:ignore WordPress.Security.EscapeOutput
                } elseif ( $counter > 1 ) {

                    $single_player = intval( $this->main_plugin->get_config()->get_state( '_bfp_single_player', 0, $id ) );

                    $before = '<table class="bfp-player-list ' . $merge_grouped_clss . ( $single_player ? ' bfp-single-player ' : '' ) . '" ' . ( $settings['_bfp_loop'] ? 'data-loop="1"' : '' ) . '>';
                    $first_player_class = 'bfp-first-player';
                    $after  = '';
                    foreach ( $files as $index => $file ) {
                        $evenOdd = ( 1 == $counter % 2 ) ? 'bfp-odd-row' : 'bfp-even-row';
                        $counter--;
                        $audio_url = $this->main_plugin->get_audio_core()->generate_audio_url( $id, $index, $file );
                        $duration  = $this->main_plugin->get_audio_core()->get_duration_by_url( $file['file'] );
                        $audio_tag = apply_filters(
                            'bfp_audio_tag',
                            $this->get_player(
                                $audio_url,
                                array(
                                    'product_id'      => $id,
                                    'player_style'    => $settings['_bfp_player_layout'],
                                    'player_controls' => ( 'all' != $player_controls ) ? 'track' : '',
                                    'media_type'      => $file['media_type'],
                                    'duration'        => $duration,
                                    'preload'         => $settings['_bfp_preload'],
                                    'volume'          => $settings['_bfp_player_volume'],
                                )
                            ),
                            $id,
                            $index,
                            $audio_url
                        );
                        $title     = esc_html( ( $settings['_bfp_player_title'] ) ? apply_filters( 'bfp_file_name', $file['name'], $id, $index ) : '' );

                        print $before; // phpcs:ignore WordPress.Security.EscapeOutput
                        $before = '';
                        $after  = '</table>';
                        if ( 'all' != $player_controls ) {
                            print '<tr class="' . esc_attr( $evenOdd ) . ' product-' . esc_attr( $file['product'] ) . '"><td class="bfp-column-player-' . esc_attr( $settings['_bfp_player_layout'] ) . '"><div class="bfp-player-container ' . $first_player_class . '" data-player-id="' . esc_attr( $counter ) . '">' . $audio_tag . '</div></td><td class="bfp-player-title bfp-column-player-title" data-player-id="' . esc_attr( $counter ) . '">' . wp_kses_post( $title ) . '</td><td class="bfp-file-duration" style="text-align:right;font-size:16px;">' . esc_html( $duration ) . '</td></tr>'; // phpcs:ignore WordPress.Security.EscapeOutput
                        } else {
                            print '<tr class="' . esc_attr( $evenOdd ) . ' product-' . esc_attr( $file['product'] ) . '"><td><div class="bfp-player-container ' . $first_player_class . '" data-player-id="' . esc_attr( $counter ) . '">' . $audio_tag . '</div><div class="bfp-player-title bfp-column-player-title" data-player-id="' . esc_attr( $counter ) . '">' . wp_kses_post( $title ) . ( $single_player ? '<span class="bfp-file-duration">' . esc_html( $duration ) . '</span>' : '' ) . '</div></td></tr>'; // phpcs:ignore WordPress.Security.EscapeOutput
                        }
                        $first_player_class = '';
                    }
                    print $after; // phpcs:ignore WordPress.Security.EscapeOutput
                }
                
                // Fix: Check if WooCommerce integration exists
                $purchased = false;
                $woocommerce = $this->main_plugin->get_woocommerce();
                if ($woocommerce) {
                    $purchased = $woocommerce->woocommerce_user_product( $id );
                }
                
                $message   = $this->main_plugin->get_config()->get_state( '_bfp_message' );
                if ( ! empty( $message ) && false === $purchased ) {
                    print '<div class="bfp-message">' . wp_kses_post( __( $message, 'bandfront-player' ) ) . '</div>'; // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText
                }
                do_action( 'bfp_after_players_product_page', $id );
        }
    }
    
    /**
     * Get product files
     * Moved from player-renderer.php
     */
    public function _get_product_files($args) {
        if ( empty( $args['product'] ) ) {
            return false;
        }

        $product = $args['product'];
        $files   = $this->_get_recursive_product_files( $product, array() );
        if ( empty( $files ) ) {
            return false;
        }

        $audio_files = array();
        foreach ( $files as $index => $file ) {
            if ( ! empty( $file['file'] ) && false !== ( $media_type = $this->main_plugin->get_audio_core()->is_audio( $file['file'] ) ) ) { // phpcs:ignore Squiz.PHP.DisallowMultipleAssignments
                $file['media_type'] = $media_type;

                if ( isset( $args['file_id'] ) ) {
                    if ( $args['file_id'] == $index ) {
                        $audio_files[ $index ] = $file;
                        return $audio_files;
                    }
                } elseif ( ! empty( $args['first'] ) ) {
                    $audio_files[ $index ] = $file;
                    return $audio_files;
                } elseif ( ! empty( $args['all'] ) ) {
                    $audio_files[ $index ] = $file;
                }
            }
        }
        return $audio_files;
    }
    
    /**
     * Get recursive product files
     * Moved from player-renderer.php
     */
    public function _get_recursive_product_files($product, $files_arr) {
        if ( ! is_object( $product ) || ! method_exists( $product, 'get_type' ) ) {
            return $files_arr;
        }

        $product_type = $product->get_type();
        $id           = $product->get_id();
        
        // Fix: Check if WooCommerce integration exists before calling its methods
        $purchased = false;
        $woocommerce = $this->main_plugin->get_woocommerce();
        if ($woocommerce) {
            $purchased = $woocommerce->woocommerce_user_product( $id );
        }

        if ( 'variation' == $product_type ) {
            $_files    = $product->get_downloads();
            $_files    = $this->_edit_files_array( $id, $_files );
            $files_arr = array_merge( $files_arr, $_files );
        } else {
            if ( ! $this->main_plugin->get_config()->get_state( '_bfp_enable_player', false, $id ) ) {
                return $files_arr;
            }

            $own_demos = intval( $this->main_plugin->get_config()->get_state( '_bfp_own_demos', 0, $id ) );
            $files     = $this->main_plugin->get_config()->get_state( '_bfp_demos_list', array(), $id );
            if ( false === $purchased && $own_demos && ! empty( $files ) ) {
                $direct_own_demos = intval( $this->main_plugin->get_config()->get_state( '_bfp_direct_own_demos', 0, $id ) );
                $files            = $this->_edit_files_array( $id, $files, $direct_own_demos );
                $files_arr        = array_merge( $files_arr, $files );
            } else {
                switch ( $product_type ) {
                    case 'variable':
                    case 'grouped':
                        $children = $product->get_children();

                        foreach ( $children as $key => $child_id ) {
                            $children[ $key ] = wc_get_product( $child_id );
                        }

                        uasort( $children, array( 'BFP_Utils', 'sort_list' ) );

                        foreach ( $children as $child_obj ) {
                            $files_arr = $this->_get_recursive_product_files( $child_obj, $files_arr );
                        }
                        break;
                    default:
                        $_files    = $product->get_downloads();
                        if( empty( $_files ) && $own_demos && ! empty( $files ) ) {
                            $_files    = $this->_edit_files_array( $id, $files );
                        } else {
                            $_files    = $this->_edit_files_array( $id, $_files );
                        }
                        $files_arr = array_merge( $files_arr, $_files );
                        break;
                }
            }
        }
        return $files_arr;
    }
    
    /**
     * Edit files array helper method
     * Moved from player-renderer.php
     */
    private function _edit_files_array( $product_id, $files, $play_src = 0 ) {
        $p_files = array();
        foreach ( $files as $key => $file ) {
            $p_key = $key . '_' . $product_id;
            if ( gettype( $file ) == 'object' ) {
                $file = (array) $file->get_data();
            }
            $file['product']   = $product_id;
            $file['play_src']  = $play_src;
            $p_files[ $p_key ] = $file;
        }
        return $p_files;
    }
    
    /**
     * Get product files - public interface
     * This is the method that other classes should use
     */
    public function get_product_files($product_id) {
        $product = wc_get_product($product_id);
        if (!$product) {
            return array();
        }
        
        return $this->_get_product_files(array(
            'product' => $product,
            'all' => true
        ));
    }
    
    /**
     * Get enqueued resources state
     */
    public function get_enqueued_resources() {
        return $this->_enqueued_resources;
    }
    
    /**
     * Set enqueued resources state
     */
    public function set_enqueued_resources($value) {
        $this->_enqueued_resources = $value;
    }
    
    /**
     * Get insert player flag
     */
    public function get_insert_player() {
        return $this->_insert_player;
    }
    
    /**
     * Set insert player flag
     */
    public function set_insert_player($value) {
        $this->_insert_player = $value;
    }
    
    /**
     * Get inserted player state
     */
    public function get_inserted_player() {
        return $this->_inserted_player;
    }
    
    /**
     * Set inserted player state
     */
    public function set_inserted_player($value) {
        $this->_inserted_player = $value;
    }
    
    /**
     * Get insert main player flag
     */
    public function get_insert_main_player() {
        return $this->_insert_main_player;
    }
    
    /**
     * Set insert main player flag
     */
    public function set_insert_main_player($value) {
        $this->_insert_main_player = $value;
    }
    
    /**
     * Get insert all players flag
     */
    public function get_insert_all_players() {
        return $this->_insert_all_players;
    }
    
    /**
     * Set insert all players flag
     */
    public function set_insert_all_players($value) {
        $this->_insert_all_players = $value;
    }
    
    /**
     * Enqueue player resources
     */
    public function enqueue_resources() {
        if ($this->_enqueued_resources) {
            return;
        }
        
        global $BandfrontPlayer;
        
        // Get audio engine setting using new state handler
        $audio_engine = $BandfrontPlayer->get_config()->get_state('_bfp_audio_engine');
        
        // Enqueue base styles
        wp_enqueue_style(
            'bfp-style', 
            plugin_dir_url(dirname(__FILE__)) . 'css/style.css', 
            array(), 
            BFP_VERSION
        );
        
        // Enqueue jQuery
        wp_enqueue_script('jquery');
        
        if ($audio_engine === 'wavesurfer') {
            // Check if WaveSurfer is available locally
            $wavesurfer_path = plugin_dir_path(dirname(__FILE__)) . 'vendors/wavesurfer/wavesurfer.min.js';
            
            if (file_exists($wavesurfer_path)) {
                // Enqueue local WaveSurfer.js
                wp_enqueue_script(
                    'wavesurfer',
                    plugin_dir_url(dirname(__FILE__)) . 'vendors/wavesurfer/wavesurfer.min.js',
                    array(),
                    '7.9.9',
                    true
                );
            } else {
                // Fallback to CDN if local file doesn't exist
                wp_enqueue_script(
                    'wavesurfer',
                    'https://unpkg.com/wavesurfer.js@7/dist/wavesurfer.min.js',
                    array(),
                    '7.9.9',
                    true
                );
            }
            
            // Enqueue WaveSurfer integration (our custom file)
            wp_enqueue_script(
                'bfp-wavesurfer-integration',
                plugin_dir_url(dirname(__FILE__)) . 'js/wavesurfer.js',
                array('jquery', 'wavesurfer'),
                BFP_VERSION,
                true
            );
        } else {
            // Enqueue MediaElement.js
            wp_enqueue_style('wp-mediaelement');
            wp_enqueue_script('wp-mediaelement');
            
            // Get selected player skin using new state handler
            $selected_skin = $BandfrontPlayer->get_config()->get_state('_bfp_player_layout');
        
            
            if (isset($skin_mapping[$selected_skin])) {
                $selected_skin = $skin_mapping[$selected_skin];
            }
            
            // Validate skin selection
            if (!in_array($selected_skin, array('dark', 'light', 'custom'))) {
                $selected_skin = 'dark';
            }
            
            // Enqueue selected skin CSS file
            wp_enqueue_style(
                'bfp-skin-' . $selected_skin,
                plugin_dir_url(dirname(__FILE__)) . 'css/skins/' . $selected_skin . '.css',
                array('wp-mediaelement'),
                BFP_VERSION
            );
        }
        
        // Enqueue main engine script
        wp_enqueue_script(
            'bfp-engine',
            plugin_dir_url(dirname(__FILE__)) . 'js/engine.js',
            array('jquery'),
            BFP_VERSION,
            true
        );
        
        // Localize script with settings using bulk fetch
        $settings_keys = array(
            '_bfp_play_simultaneously',
            '_bfp_ios_controls',
            '_bfp_fade_out',
            '_bfp_on_cover',
            '_bfp_enable_visualizations'
        );
        
        $settings = $BandfrontPlayer->get_config()->get_states($settings_keys);
        
        $js_settings = array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'audio_engine' => $audio_engine,
            'play_simultaneously' => $settings['_bfp_play_simultaneously'],
            'ios_controls' => $settings['_bfp_ios_controls'],
            'fade_out' => $settings['_bfp_fade_out'],
            'on_cover' => $settings['_bfp_on_cover'],
            'visualizations' => $settings['_bfp_enable_visualizations'],
            'player_skin' => $selected_skin
        );
        
        wp_localize_script('bfp-engine', 'bfp_global_settings', $js_settings);
        
        $this->_enqueued_resources = true;
    }
}

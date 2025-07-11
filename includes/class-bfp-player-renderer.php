<?php
/**
 * Player Renderer for Bandfront Player
 *
 * @package BandfrontPlayer
 * @since 0.1
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * BFP Player Renderer Class
 * Handles all player HTML generation and product integration
 */
class BFP_Player_Renderer {
    
    private $main_plugin;
    
    public function __construct($main_plugin) {
        $this->main_plugin = $main_plugin;
    }
    
    /**
     * Include main player for a product - REFACTORED with context-aware controls
     */
    public function include_main_player($product = '', $_echo = true) {
        $output = '';

        if ( is_admin() ) return $output;

        if ( ! $this->main_plugin->get_insert_player() || ! $this->main_plugin->get_insert_main_player() ) {
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

        // Check if on_cover is enabled for shop pages
        $on_cover = $this->main_plugin->get_global_attr('_bfp_on_cover', 1);
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

            $show_in = $this->main_plugin->get_product_attr( $id, '_bfp_show_in', 'all' );
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
            
            $preload = $this->main_plugin->get_product_attr( $id, '_bfp_preload', '' );
            $this->main_plugin->enqueue_resources();

            $player_style = $this->main_plugin->get_product_attr( $id, '_bfp_player_layout', BFP_DEFAULT_PLAYER_LAYOUT );
            $volume = @floatval( $this->main_plugin->get_product_attr( $id, '_bfp_player_volume', BFP_DEFAULT_PLAYER_VOLUME ) );

            $file = reset( $files );
            $index = key( $files );
            $duration = $this->main_plugin->get_audio_processor()->get_duration_by_url( $file['file'] );
            $audio_url = $this->main_plugin->get_audio_processor()->generate_audio_url( $id, $index, $file );
            $audio_tag = apply_filters(
                'bfp_audio_tag',
                $this->main_plugin->get_player(
                    $audio_url,
                    array(
                        'product_id'      => $id,
                        'player_controls' => $player_controls,  // Use context-aware controls
                        'player_style'    => $player_style,
                        'media_type'      => $file['media_type'],
                        'duration'        => $duration,
                        'preload'         => $preload,
                        'volume'          => $volume,
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
     */
 /**
 * Include all players for a product
 */
public function include_all_players($product = '') {
    if ( ! $this->main_plugin->get_insert_player() || ! $this->main_plugin->get_insert_all_players() || is_admin() ) {
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

        $show_in = $this->main_plugin->get_product_attr( $id, '_bfp_show_in', 'all' );
        if (
            ( 'single' == $show_in && ! is_singular() ) ||
            ( 'multiple' == $show_in && is_singular() )
        ) {
            return;
        }
        $preload = $this->main_plugin->get_product_attr( $id, '_bfp_preload', '' );
        $this->main_plugin->enqueue_resources();
        $player_style       = $this->main_plugin->get_product_attr( $id, '_bfp_player_layout', BFP_DEFAULT_PLAYER_LAYOUT );
        $volume             = @floatval( $this->main_plugin->get_product_attr( $id, '_bfp_player_volume', BFP_DEFAULT_PLAYER_VOLUME ) );
        
        // CONTEXT-AWARE CONTROLS: Always use full controls on product pages
        if (function_exists('is_product') && is_product()) {
            // Product page - always use full controls ('all')
            $player_controls = 'all';
        } else {
            // Shop/archive pages - use button only
            $player_controls = 'button';
        }
        
        $player_title       = intval( $this->main_plugin->get_product_attr( $id, '_bfp_player_title', BFP_DEFAULT_PlAYER_TITLE ) );
        $loop               = intval( $this->main_plugin->get_product_attr( $id, '_bfp_loop', 0 ) );
        $merge_grouped      = intval( $this->main_plugin->get_product_attr( $id, '_bfp_merge_in_grouped', 0 ) );
        $merge_grouped_clss = ( $merge_grouped ) ? 'merge_in_grouped_products' : '';

        $counter = count( $files );

        do_action( 'bfp_before_players_product_page', $id );
        if ( 1 == $counter ) {
                $player_controls = ( 'button' == $player_controls ) ? 'track' : '';
                $file            = reset( $files );
                $index           = key( $files );
                $duration        = $this->main_plugin->get_audio_processor()->get_duration_by_url( $file['file'] );
                $audio_url       = $this->main_plugin->get_audio_processor()->generate_audio_url( $id, $index, $file );
                $audio_tag       = apply_filters(
                    'bfp_audio_tag',
                    $this->main_plugin->get_player(
                        $audio_url,
                        array(
                            'product_id'      => $id,
                            'player_controls' => $player_controls,
                            'player_style'    => $player_style,
                            'media_type'      => $file['media_type'],
                            'duration'        => $duration,
                            'preload'         => $preload,
                            'volume'          => $volume,
                        )
                    ),
                    $id,
                    $index,
                    $audio_url
                );
                $title           = esc_html( ( $player_title ) ? apply_filters( 'bfp_file_name', $file['name'], $id, $index ) : '' );
                print '<div class="bfp-player-container ' . esc_attr( $merge_grouped_clss ) . ' product-' . esc_attr( $file['product'] ) . '" ' . ( $loop ? 'data-loop="1"' : '' ) . '>' . $audio_tag . '</div><div class="bfp-player-title" data-audio-url="' . esc_attr( $audio_url ) . '">' . wp_kses_post( $title ) . '</div><div style="clear:both;"></div>'; // phpcs:ignore WordPress.Security.EscapeOutput
            } elseif ( $counter > 1 ) {

                $single_player = intval( $this->main_plugin->get_product_attr( $id, '_bfp_single_player', BFP_DEFAULT_SINGLE_PLAYER ) );

                $before = '<table class="bfp-player-list ' . $merge_grouped_clss . ( $single_player ? ' bfp-single-player ' : '' ) . '" ' . ( $loop ? 'data-loop="1"' : '' ) . '>';
                $first_player_class = 'bfp-first-player';
                $after  = '';
                foreach ( $files as $index => $file ) {
                    $evenOdd = ( 1 == $counter % 2 ) ? 'bfp-odd-row' : 'bfp-even-row';
                    $counter--;
                    $audio_url = $this->main_plugin->get_audio_processor()->generate_audio_url( $id, $index, $file );
                    $duration  = $this->main_plugin->get_audio_processor()->get_duration_by_url( $file['file'] );
                    $audio_tag = apply_filters(
                        'bfp_audio_tag',
                        $this->main_plugin->get_player(
                            $audio_url,
                            array(
                                'product_id'      => $id,
                                'player_style'    => $player_style,
                                'player_controls' => ( 'all' != $player_controls ) ? 'track' : '',
                                'media_type'      => $file['media_type'],
                                'duration'        => $duration,
                                'preload'         => $preload,
                                'volume'          => $volume,
                            )
                        ),
                        $id,
                        $index,
                        $audio_url
                    );
                    $title     = esc_html( ( $player_title ) ? apply_filters( 'bfp_file_name', $file['name'], $id, $index ) : '' );

                    print $before; // phpcs:ignore WordPress.Security.EscapeOutput
                    $before = '';
                    $after  = '</table>';
                    if ( 'all' != $player_controls ) {
                        print '<tr class="' . esc_attr( $evenOdd ) . ' product-' . esc_attr( $file['product'] ) . '"><td class="bfp-column-player-' . esc_attr( $player_style ) . '"><div class="bfp-player-container ' . $first_player_class . '" data-player-id="' . esc_attr( $counter ) . '">' . $audio_tag . '</div></td><td class="bfp-player-title bfp-column-player-title" data-player-id="' . esc_attr( $counter ) . '">' . wp_kses_post( $title ) . '</td><td class="bfp-file-duration" style="text-align:right;font-size:16px;">' . esc_html( $duration ) . '</td></tr>'; // phpcs:ignore WordPress.Security.EscapeOutput
                    } else {
                        print '<tr class="' . esc_attr( $evenOdd ) . ' product-' . esc_attr( $file['product'] ) . '"><td><div class="bfp-player-container ' . $first_player_class . '" data-player-id="' . esc_attr( $counter ) . '">' . $audio_tag . '</div><div class="bfp-player-title bfp-column-player-title" data-player-id="' . esc_attr( $counter ) . '">' . wp_kses_post( $title ) . ( $single_player ? '<span class="bfp-file-duration">' . esc_html( $duration ) . '</span>' : '' ) . '</div></td></tr>'; // phpcs:ignore WordPress.Security.EscapeOutput
                    }
                    $first_player_class = '';
                }
                print $after; // phpcs:ignore WordPress.Security.EscapeOutput
            }
            $purchased = $this->main_plugin->woocommerce_user_product( $id );
            $message   = $this->main_plugin->get_global_attr( '_bfp_message', '' );
            if ( ! empty( $message ) && false === $purchased ) {
                print '<div class="bfp-message">' . wp_kses_post( __( $message, 'bandfront-player' ) ) . '</div>'; // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText
            }
            do_action( 'bfp_after_players_product_page', $id );
        }
    }
    
    /**
     * Get recursive product files
     */
    public function _get_recursive_product_files($product, $files_arr) {
        if ( ! is_object( $product ) || ! method_exists( $product, 'get_type' ) ) {
            return $files_arr;
        }

        $product_type = $product->get_type();
        $id           = $product->get_id();
        $purchased    = $this->main_plugin->woocommerce_user_product( $id );

        if ( 'variation' == $product_type ) {
            $_files    = $product->get_downloads();
            $_files    = $this->_edit_files_array( $id, $_files );
            $files_arr = array_merge( $files_arr, $_files );
        } else {
            if ( ! $this->main_plugin->get_product_attr( $id, '_bfp_enable_player', false ) ) {
                return $files_arr;
            }

            $own_demos = intval( $this->main_plugin->get_product_attr( $id, '_bfp_own_demos', 0 ) );
            $files     = $this->main_plugin->get_product_attr( $id, '_bfp_demos_list', array() );
            if ( false === $purchased && $own_demos && ! empty( $files ) ) {
                $direct_own_demos = intval( $this->main_plugin->get_product_attr( $id, '_bfp_direct_own_demos', 0 ) );
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

                        uasort( $children, array( &$this->main_plugin, '_sort_list' ) ); /* replaced usort with uasort 2018.06.12 */

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
     * Get product files
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
            if ( ! empty( $file['file'] ) && false !== ( $media_type = $this->main_plugin->get_audio_processor()->is_audio( $file['file'] ) ) ) { // phpcs:ignore Squiz.PHP.DisallowMultipleAssignments
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
     * Edit files array helper method
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
     * Enqueue scripts and styles
     */
    public function enqueue_scripts() {
        // Existing enqueue code...

        // Add WaveSurfer
        $wavesurfer_path = plugin_dir_path(dirname(__FILE__)) . 'vendors/wavesurfer/';
        $wavesurfer_url = plugin_dir_url(dirname(__FILE__)) . 'vendors/wavesurfer/';
        
        if (file_exists($wavesurfer_path . 'wavesurfer.min.js')) {
            wp_enqueue_script(
                'wavesurfer',
                $wavesurfer_url . 'wavesurfer.min.js',
                array(),
                '7.9.9',
                true
            );
            
            // Add your integration script
            wp_enqueue_script(
                'bfp-wavesurfer-integration',
                plugin_dir_url(dirname(__FILE__)) . 'js/wavesurfer-integration.js',
                array('jquery', 'wavesurfer'),
                BFP_VERSION,
                true
            );
        }
    }
}
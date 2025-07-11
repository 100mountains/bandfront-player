<?php
/**
 * WordPress Hooks Manager for Bandfront Player
 *
 * @package BandfrontPlayer
 * @since 0.1
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * BFP Hooks Manager Class
 * Handles all WordPress action and filter registrations
 */
class BFP_Hooks_Manager {
    
    private $main_plugin;
    
    public function __construct($main_plugin) {
        $this->main_plugin = $main_plugin;
        $this->register_hooks();
    }
    
    /**
     * Get the hooks array configuration
     */
    public function get_hooks_config() {
        // FIXED: Context-aware hooks to prevent duplicate players
        $hooks_config = array(
            'main_player' => array(),
            'all_players' => array()
        );
        
        // Only add all_players hooks on single product pages
        if (function_exists('is_product') && is_product()) {
            $hooks_config['all_players'] = array(
                // Changed from woocommerce_after_single_product_summary to place below price
                'woocommerce_single_product_summary' => 25,  // Priority 25 is after price (price is at 10)
            );
        } else {
            // On shop/archive pages, remove the title hook if on_cover is enabled
            $on_cover = $this->main_plugin->get_global_attr('_bfp_on_cover', 1);
            if (!$on_cover) {
                $hooks_config['main_player'] = array(
                    'woocommerce_after_shop_loop_item_title' => 1,
                );
            }
        }
        
        return $hooks_config;
    }
    
    /**
     * Register all WordPress hooks and filters
     */
    private function register_hooks() {
        register_activation_hook( BFP_PLUGIN_PATH, array( &$this->main_plugin, 'activation' ) );
        register_deactivation_hook( BFP_PLUGIN_PATH, array( &$this->main_plugin, 'deactivation' ) );

        add_action( 'plugins_loaded', array( &$this->main_plugin, 'plugins_loaded' ) );
        add_action( 'init', array( &$this->main_plugin, 'init' ) );

        // FIXED: Dynamic hook registration based on context
        add_action( 'wp', array( $this, 'register_dynamic_hooks' ) );
        
        // Add filter for on_cover functionality
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_on_cover_assets' ) );
        add_action( 'woocommerce_before_shop_loop_item_title', array( $this, 'add_play_button_on_cover' ), 20 );
        
        // Remove WooCommerce product title filter if on_cover is enabled
        add_action( 'init', array( $this, 'conditionally_add_title_filter' ) );
        
        // Add filter for analytics preload
        add_filter( 'bfp_preload', array( $this->main_plugin->get_audio_processor(), 'preload' ), 10, 2 );

        // EXPORT / IMPORT PRODUCTS
        add_filter( 'woocommerce_product_export_meta_value', function( $value, $meta, $product, $row ){
            if (
                preg_match( '/^' . preg_quote( '_bfp_' ) . '/i', $meta->key ) &&
                ! is_scalar( $value )
            ) {
                $value = serialize( $value );
            }
            return $value;
        }, 10, 4 );

        add_filter( 'woocommerce_product_importer_pre_expand_data', function( $data ){
            foreach ( $data as $_key => $_value ) {
                if (
                    preg_match( '/^' . preg_quote( 'meta:_bfp_' ) . '/i', $_key ) &&
                    function_exists( 'is_serialized' ) &&
                    is_serialized( $_value )
                ) {
                    try {
                        $data[ $_key ] = unserialize( $_value );
                    } catch ( Exception $err ) {
                        $data[ $_key ] = $_value;
                    } catch ( Error $err ) {
                        $data[ $_key ] = $_value;
                    }
                }
            }
            return $data;
        }, 10 );

        /** WooCommerce Product Table by Barn2 Plugins integration **/
        add_filter( 'wc_product_table_data_name', function( $title, $product ) {
            return ( false === stripos( $title, '<audio' ) ? $this->main_plugin->include_main_player( $product, false ) : '' ) . $title;
        }, 10, 2 );

        add_action( 'wc_product_table_before_get_data', function( $table ) {
            $GLOBALS['_insert_all_players_BK'] = $this->main_plugin->get_insert_all_players();
            $this->main_plugin->set_insert_all_players(false);
        }, 10 );

        add_action( 'wc_product_table_after_get_data', function( $table ) {
            if ( isset( $GLOBALS['_insert_all_players_BK'] ) ) {
                $this->main_plugin->set_insert_all_players($GLOBALS['_insert_all_players_BK']);
                unset( $GLOBALS['_insert_all_players_BK'] );
            } else {
                $this->main_plugin->set_insert_all_players(true);
            }
        }, 10 );

        add_filter( 'pre_do_shortcode_tag', function( $output, $tag, $attr, $m ){
            if( strtolower( $tag ) == 'product_table' ) {
                $this->main_plugin->enqueue_resources();
            }
            return $output;
        }, 10, 4 );

        /** ListeSpeed Cache integration **/
        add_filter( 'litespeed_optimize_js_excludes', function( $p ){
            $p[] = 'jquery.js';
            $p[] = 'jquery.min.js';
            $p[] = '/mediaelement/';
            $p[] = plugin_dir_url( BFP_PLUGIN_PATH ) . 'js/engine.js';
            $p[] = '/wavesurfer.js';
            return $p;
        } );
        add_filter( 'litespeed_optm_js_defer_exc', function( $p ){
            $p[] = 'jquery.js';
            $p[] = 'jquery.min.js';
            $p[] = '/mediaelement/';
            $p[] = plugin_dir_url( BFP_PLUGIN_PATH ) . 'js/engine.js';
            $p[] = '/wavesurfer.js';
            return $p;
        } );
    }
    
    /**
     * Register hooks dynamically based on page context
     */
    public function register_dynamic_hooks() {
        $hooks_config = $this->get_hooks_config();
        
        // Register main player hooks
        foreach ($hooks_config['main_player'] as $hook => $priority) {
            add_action($hook, array($this->main_plugin, 'include_main_player'), $priority);
        }
        
        // Register all players hooks
        foreach ($hooks_config['all_players'] as $hook => $priority) {
            add_action($hook, array($this->main_plugin, 'include_all_players'), $priority);
        }
    }

    /**
     * Conditionally add product title filter
     */
    public function conditionally_add_title_filter() {
        $on_cover = $this->main_plugin->get_global_attr('_bfp_on_cover', 1);
        if (!$on_cover) {
            add_filter( 'woocommerce_product_title', array( $this->main_plugin->get_woocommerce(), 'woocommerce_product_title' ), 10, 2 );
        }
    }

    /**
     * Enqueue assets for on_cover functionality
     */
    public function enqueue_on_cover_assets() {
        if (!is_shop() && !is_product_category() && !is_product_tag()) {
            return;
        }
        
        $on_cover = $this->main_plugin->get_global_attr('_bfp_on_cover', 1);
        if ($on_cover) {
            wp_add_inline_style('bfp-style', '
                .woocommerce ul.products li.product .bfp-play-on-cover {
                    position: absolute;
                    top: 50%;
                    left: 50%;
                    transform: translate(-50%, -50%);
                    z-index: 10;
                    background: rgba(255,255,255,0.9);
                    border-radius: 50%;
                    width: 60px;
                    height: 60px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    cursor: pointer;
                    transition: all 0.3s ease;
                    box-shadow: 0 2px 10px rgba(0,0,0,0.3);
                }
                .woocommerce ul.products li.product .bfp-play-on-cover:hover {
                    transform: translate(-50%, -50%) scale(1.1);
                    box-shadow: 0 4px 20px rgba(0,0,0,0.4);
                }
                .woocommerce ul.products li.product .bfp-play-on-cover svg {
                    width: 24px;
                    height: 24px;
                    margin-left: 3px;
                }
                .woocommerce ul.products li.product a img {
                    position: relative;
                }
                .woocommerce ul.products li.product {
                    position: relative;
                }
            ');
        }
    }

    /**
     * Add play button on product cover image
     */
    public function add_play_button_on_cover() {
        if (!is_shop() && !is_product_category() && !is_product_tag()) {
            return;
        }
        
        $on_cover = $this->main_plugin->get_global_attr('_bfp_on_cover', 1);
        if (!$on_cover) {
            return;
        }
        
        global $product;
        if (!$product) {
            return;
        }
        
        $product_id = $product->get_id();
        $enable_player = $this->main_plugin->get_product_attr($product_id, '_bfp_enable_player', false);
        
        if (!$enable_player) {
            return;
        }
        
        // Get the first audio file
        $files = $this->main_plugin->get_product_files($product_id);
        if (empty($files)) {
            return;
        }
        
        // Enqueue player resources
        $this->main_plugin->enqueue_resources();
        
        // Output the play button overlay
        echo '<div class="bfp-play-on-cover" data-product-id="' . esc_attr($product_id) . '">';
        echo '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">';
        echo '<path d="M8 5v14l11-7z"/>';
        echo '</svg>';
        echo '</div>';
        
        // Add the hidden player container
        echo '<div class="bfp-hidden-player-container" style="display:none;">';
        $this->main_plugin->include_main_player($product, true);
        echo '</div>';
    }
}

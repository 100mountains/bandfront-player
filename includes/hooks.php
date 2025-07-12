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
class BFP_Hooks {
    
    private $main_plugin;
    private $cover_overlay_renderer;
    
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
            // Use get_state for single value retrieval
            $on_cover = $this->main_plugin->get_config()->get_state('_bfp_on_cover');
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
        
        // Add hooks for on_cover functionality
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_on_cover_assets' ) );
        add_action( 'woocommerce_before_shop_loop_item_title', array( $this, 'add_play_button_on_cover' ), 20 );
        
        // Remove WooCommerce product title filter if on_cover is enabled
        add_action( 'init', array( $this, 'conditionally_add_title_filter' ) );
        
        // Add filter for analytics preload
        add_filter( 'bfp_preload', array( $this->main_plugin->get_audio_core(), 'preload' ), 10, 2 );

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
            add_action($hook, array($this->main_plugin->get_player(), 'include_main_player'), $priority);
        }
        
        // Register all players hooks
        foreach ($hooks_config['all_players'] as $hook => $priority) {
            add_action($hook, array($this->main_plugin->get_player(), 'include_all_players'), $priority);
        }
    }

    /**
     * Conditionally add product title filter
     */
    public function conditionally_add_title_filter() {
        // Use get_state instead of accessing config directly
        $on_cover = $this->main_plugin->get_config()->get_state('_bfp_on_cover');
        $woocommerce = $this->main_plugin->get_woocommerce();
        
        if (!$on_cover && $woocommerce) {
            add_filter( 'woocommerce_product_title', array( $woocommerce, 'woocommerce_product_title' ), 10, 2 );
        }
    }

    /**
     * Get cover overlay renderer instance
     *
     * @return BFP_Cover_Renderer
     */
    private function get_cover_renderer() {
        if (!$this->cover_renderer) {
            require_once plugin_dir_path(__FILE__) . 'cover-renderer.php';
            $this->cover_renderer = new BFP_Cover_Renderer($this->main_plugin);
        }
        return $this->cover_renderer;
    }

    /**
     * Enqueue assets for on_cover functionality
     */
    public function enqueue_on_cover_assets() {
        $this->get_cover_renderer()->enqueue_assets();
    }

    /**
     * Add play button on product cover image
     */
    public function add_play_button_on_cover() {
        $this->get_cover_renderer()->render();
    }
}
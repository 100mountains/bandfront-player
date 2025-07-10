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
        return array(
            'main_player' => array(),
            'all_players' => array(
                // 'woocommerce_before_single_product_summary' => 1,
                'woocommerce_single_product_summary' 		=> 1,
                'woocommerce_after_single_product_summary' 	=> 1,
                'woocommerce_before_add_to_cart_form'		=> 1,
                'woocommerce_after_add_to_cart_form'		=> 1,
            )
        );
    }
    
    /**
     * Register all WordPress hooks and filters
     */
    private function register_hooks() {
        register_activation_hook( BFP_PLUGIN_PATH, array( &$this->main_plugin, 'activation' ) );
        register_deactivation_hook( BFP_PLUGIN_PATH, array( &$this->main_plugin, 'deactivation' ) );

        add_action( 'plugins_loaded', array( &$this->main_plugin, 'plugins_loaded' ) );
        add_action( 'init', array( &$this->main_plugin, 'init' ) );

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
}

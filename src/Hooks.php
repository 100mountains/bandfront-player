<?php
namespace bfp;

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
 * Hooks Manager Class
 * Handles all WordPress action and filter registrations
 */
class Hooks {
    
    private Plugin $mainPlugin;
    
    public function __construct(Plugin $mainPlugin) {
        $this->mainPlugin = $mainPlugin;
        $this->registerHooks();
    }
    
    /**
     * Get the hooks array configuration
     */
    public function getHooksConfig(): array {
        // FIXED: Context-aware hooks to prevent duplicate players
        $hooksConfig = [
            'main_player' => [],
            'all_players' => []
        ];
        
        // Only add all_players hooks on single product pages
        if (function_exists('is_product') && is_product()) {
            $hooksConfig['all_players'] = [
                // Changed from woocommerce_after_single_product_summary to place below price
                'woocommerce_single_product_summary' => 25,  // Priority 25 is after price (price is at 10)
            ];
        } else {
            // On shop/archive pages, remove the title hook if on_cover is enabled
            // Use getState for single value retrieval
            $onCover = $this->mainPlugin->getConfig()->getState('_bfp_on_cover');
            if (!$onCover) {
                $hooksConfig['main_player'] = [
                    'woocommerce_after_shop_loop_item_title' => 1,
                ];
            }
        }
        
        return $hooksConfig;
    }
    
    /**
     * Register all WordPress hooks and filters
     */
    private function registerHooks(): void {
        register_activation_hook(BFP_PLUGIN_PATH, [$this->mainPlugin, 'activation']);
        register_deactivation_hook(BFP_PLUGIN_PATH, [$this->mainPlugin, 'deactivation']);

        add_action('plugins_loaded', [$this->mainPlugin, 'pluginsLoaded']);
        add_action('init', [$this->mainPlugin, 'init']);

        // FIXED: Dynamic hook registration based on context
        add_action('wp', [$this, 'registerDynamicHooks']);
        
        // Add hooks for on_cover functionality
        add_action('wp_enqueue_scripts', [$this, 'enqueueOnCoverAssets']);
        add_action('woocommerce_before_shop_loop_item_title', [$this, 'addPlayButtonOnCover'], 20);
        
        // Remove WooCommerce product title filter if on_cover is enabled
        add_action('init', [$this, 'conditionallyAddTitleFilter']);
        
        // Add filter for analytics preload
        add_filter('bfp_preload', [$this->mainPlugin->getAudioCore(), 'preload'], 10, 2);

        // EXPORT / IMPORT PRODUCTS
        add_filter('woocommerce_product_export_meta_value', function($value, $meta, $product, $row) {
            if (
                preg_match('/^' . preg_quote('_bfp_') . '/i', $meta->key) &&
                !is_scalar($value)
            ) {
                $value = serialize($value);
            }
            return $value;
        }, 10, 4);

        add_filter('woocommerce_product_importer_pre_expand_data', function($data) {
            foreach ($data as $_key => $_value) {
                if (
                    preg_match('/^' . preg_quote('meta:_bfp_') . '/i', $_key) &&
                    function_exists('is_serialized') &&
                    is_serialized($_value)
                ) {
                    try {
                        $data[$_key] = unserialize($_value);
                    } catch (\Exception $err) {
                        $data[$_key] = $_value;
                    } catch (\Error $err) {
                        $data[$_key] = $_value;
                    }
                }
            }
            return $data;
        }, 10);

        /** WooCommerce Product Table by Barn2 Plugins integration **/
        add_filter('wc_product_table_data_name', function($title, $product) {
            return (false === stripos($title, '<audio') ? $this->mainPlugin->includeMainPlayer($product, false) : '') . $title;
        }, 10, 2);

        add_action('wc_product_table_before_get_data', function($table) {
            $GLOBALS['_insert_all_players_BK'] = $this->mainPlugin->getInsertAllPlayers();
            $this->mainPlugin->setInsertAllPlayers(false);
        }, 10);

        add_action('wc_product_table_after_get_data', function($table) {
            if (isset($GLOBALS['_insert_all_players_BK'])) {
                $this->mainPlugin->setInsertAllPlayers($GLOBALS['_insert_all_players_BK']);
                unset($GLOBALS['_insert_all_players_BK']);
            } else {
                $this->mainPlugin->setInsertAllPlayers(true);
            }
        }, 10);

        add_filter('pre_do_shortcode_tag', function($output, $tag, $attr, $m) {
            if (strtolower($tag) == 'product_table') {
                $this->mainPlugin->enqueueResources();
            }
            return $output;
        }, 10, 4);

        /** LiteSpeed Cache integration **/
        add_filter('litespeed_optimize_js_excludes', function($p) {
            $p[] = 'jquery.js';
            $p[] = 'jquery.min.js';
            $p[] = '/mediaelement/';
            $p[] = plugin_dir_url(BFP_PLUGIN_PATH) . 'js/engine.js';
            $p[] = '/wavesurfer.js';
            return $p;
        });
        
        add_filter('litespeed_optm_js_defer_exc', function($p) {
            $p[] = 'jquery.js';
            $p[] = 'jquery.min.js';
            $p[] = '/mediaelement/';
            $p[] = plugin_dir_url(BFP_PLUGIN_PATH) . 'js/engine.js';
            $p[] = '/wavesurfer.js';
            return $p;
        });
    }
    
    /**
     * Register hooks dynamically based on page context
     */
    public function registerDynamicHooks(): void {
        $hooksConfig = $this->getHooksConfig();
        
        // Register main player hooks
        foreach ($hooksConfig['main_player'] as $hook => $priority) {
            add_action($hook, [$this->mainPlugin->getPlayer(), 'includeMainPlayer'], $priority);
        }
        
        // Register all players hooks
        foreach ($hooksConfig['all_players'] as $hook => $priority) {
            add_action($hook, [$this->mainPlugin->getPlayer(), 'includeAllPlayers'], $priority);
        }
    }

    /**
     * Conditionally add product title filter
     */
    public function conditionallyAddTitleFilter(): void {
        // Use getState instead of accessing config directly
        $onCover = $this->mainPlugin->getConfig()->getState('_bfp_on_cover');
        $woocommerce = $this->mainPlugin->getWooCommerce();
        
        if (!$onCover && $woocommerce) {
            add_filter('woocommerce_product_title', [$woocommerce, 'woocommerceProductTitle'], 10, 2);
        }
    }

    /**
     * Enqueue assets for on_cover functionality
     */
    public function enqueueOnCoverAssets(): void {
        // Use the main renderer instead of separate CoverRenderer
        $this->mainPlugin->getRenderer()->enqueueCoverAssets();
    }

    /**
     * Add play button on product cover image
     */
    public function addPlayButtonOnCover(): void {
        // Use the main renderer instead of separate CoverRenderer
        $this->mainPlugin->getRenderer()->renderCoverOverlay();
    }
    
    /**
     * Add rewrite rules for pretty URLs
     */
    public function addRewriteRules(): void {
        add_rewrite_rule(
            '^bfp-stream/([0-9]+)/([a-zA-Z0-9_-]+)/?$',
            'index.php?bfp_stream=1&bfp_product=$matches[1]&bfp_file=$matches[2]',
            'top'
        );
    }
    
    /**
     * Add query vars
     */
    public function addQueryVars(array $vars): array {
        $vars[] = 'bfp_stream';
        $vars[] = 'bfp_product';
        $vars[] = 'bfp_file';
        return $vars;
    }
    
    /**
     * Template redirect for pretty URLs
     */
    public function templateRedirect(): void {
        if (get_query_var('bfp_stream')) {
            $productId = get_query_var('bfp_product');
            $fileIndex = get_query_var('bfp_file');
            
            // Redirect to REST API endpoint
            wp_redirect(rest_url("bandfront-player/v1/stream/{$productId}/{$fileIndex}"));
            exit;
        }
    }
}
<?php
declare(strict_types=1);

namespace Bandfront\Builders;

/**
 * Page builders integration for Bandfront Player
 *
 * @package Bandfront\Builders
 * @since 2.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * Builders Integration Class
 * Handles Gutenberg and Elementor page builder integrations
 */
class Builders {

    /**
     * Initialize builders integration
     */
    public function init(): void {
        // Gutenberg - register with higher priority to ensure dependencies are loaded
        $this->gutenbergEditor();

        // Elementor
        add_action('elementor/widgets/register', [$this, 'elementorEditor']);
        add_action('elementor/elements/categories_registered', [$this, 'elementorEditorCategory']);
    }

    /**
     * Register Gutenberg block
     */
    public function gutenbergEditor(): void {
        // Check if block editor is available
        if (!function_exists('register_block_type')) {
            return;
        }
        
        // Register block type from block.json
        $blockJsonFile = plugin_dir_path(__FILE__) . 'gutenberg/block.json';
        if (file_exists($blockJsonFile)) {
            $result = register_block_type($blockJsonFile);
            
            if (is_wp_error($result)) {
                error_log('BFP Block Registration Error: ' . $result->get_error_message());
            } else {
                // Add localization after block registration
                add_action('enqueue_block_editor_assets', [$this, 'localizeBlockEditor'], 20);
            }
        } else {
            error_log('BFP Block JSON file not found: ' . $blockJsonFile);
        }
    }

    /**
     * Localize Gutenberg block editor script
     */
    public function localizeBlockEditor(): void {
        // First check if our script is enqueued
        if (!wp_script_is('bfp-bandfront-player-playlist-editor-script', 'enqueued')) {
            return;
        }
        
        // Localize script for the block editor
        wp_localize_script(
            'bfp-bandfront-player-playlist-editor-script',
            'bfp_gutenberg_editor_config',
            [
                'url' => admin_url('admin-ajax.php'),
                'ids_attr_description' => __('Comma-separated product IDs. Use "*" for all products.', 'bandfront-player'),
                'categories_attr_description' => __('Comma-separated product category slugs.', 'bandfront-player'),
                'tags_attr_description' => __('Comma-separated product tag slugs.', 'bandfront-player'),
                'more_details' => __('See documentation for more shortcode options.', 'bandfront-player')
            ]
        );
    }

    /**
     * Register Elementor widget
     */
    public function elementorEditor(): void {
        $elementorFile = plugin_dir_path(__FILE__) . 'elementor/elementor.pb.php';
        if (file_exists($elementorFile)) {
            include_once $elementorFile;
        }
    }

    /**
     * Register Elementor category
     */
    public function elementorEditorCategory(\Elementor\Elements_Manager $elementsManager): void {
        $elementsManager->add_category(
            'bandfront-player-cat',
            [
                'title' => __('Bandfront Player', 'bandfront-player'),
                'icon'  => 'fa fa-music',
            ]
        );
    }
}
<?php
/**
 * BFP_BUILDERS class
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

if ( ! class_exists( 'BFP_BUILDERS' ) ) {
    class BFP_BUILDERS {

        private static $_instance;

        private function __construct(){}
        
        private static function instance() {
            if ( ! isset( self::$_instance ) ) {
                self::$_instance = new self();
            }
            return self::$_instance;
        } // End instance

        public static function run() {
            $instance = self::instance();
            add_action( 'init', array( $instance, 'init' ) );
        }

        public function init() {
            $instance = self::instance();

            // Gutenberg
            $instance->gutenberg_editor();

            // Elementor
            add_action( 'elementor/widgets/register', array( $instance, 'elementor_editor' ) );
            add_action( 'elementor/elements/categories_registered', array( $instance, 'elementor_editor_category' ) );

        } // End init

        public function gutenberg_editor() {
            // Register block type from block.json
            $block_json_file = plugin_dir_path( __FILE__ ) . 'gutenberg/block.json';
            if ( file_exists( $block_json_file ) ) {
                register_block_type( $block_json_file );
                
                // Add localization after block registration
                add_action( 'enqueue_block_editor_assets', array( $this, 'localize_block_editor' ) );
            }
        } // End gutenberg_editor

        public function localize_block_editor() {
            // Localize script for the block editor
            wp_localize_script(
                'bfp-bandfront-player-playlist-editor-script',
                'bfp_gutenberg_editor_config',
                array(
                    'url' => admin_url( 'admin-ajax.php' ),
                    'ids_attr_description' => __( 'Comma-separated product IDs. Use "*" for all products.', 'bandfront-player' ),
                    'categories_attr_description' => __( 'Comma-separated product category slugs.', 'bandfront-player' ),
                    'tags_attr_description' => __( 'Comma-separated product tag slugs.', 'bandfront-player' ),
                    'more_details' => __( 'See documentation for more shortcode options.', 'bandfront-player' )
                )
            );
        } // End localize_block_editor

        public function elementor_editor() {
            include_once plugin_dir_path( __FILE__ ) . 'elementor/elementor.pb.php';
        } // End elementor_editor

        public function elementor_editor_category( $elements_manager ) {
            $elements_manager->add_category(
                'bandfront-player-cat',
                array(
                    'title' => __( 'Bandfront Player', 'bandfront-player' ),
                    'icon'  => 'fa fa-music',
                )
            );
        } // End elementor_editor_category

    } // End class
} // End if

// Initialize builders
BFP_BUILDERS::run();

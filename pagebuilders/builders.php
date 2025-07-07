<?php
/**
 * Main class to interace with the different Content Editors: BFP_BUILDERS class
 */
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
			add_action( 'after_setup_theme', array( $instance, 'after_setup_theme' ) );
		}

		public function init() {
			$instance = self::instance();

			// Gutenberg
			$instance->gutenberg_editor();
			add_action( 'enqueue_block_editor_assets', array( $instance, 'enqueue_block_editor_assets' ) );
			add_filter( 'pre_render_block', array( $instance, 'gutenberg_pre_render_block' ), 10, 2 );

			// Elementor
			add_action( 'elementor/widgets/register', array( $instance, 'elementor_editor' ) );
			add_action( 'elementor/elements/categories_registered', array( $instance, 'elementor_editor_category' ) );

		} // End init

		public function after_setup_theme() {
			$instance = $instance = self::instance();

		} // End after_setup_theme

		public function gutenberg_editor() {
			// Removed previous wp_enqueue_script and wp_enqueue_style calls.
			register_block_type( __DIR__ . '/gutenberg' );
		} // End gutenberg_editor

		public function gutenberg_pre_render_block( $pre_render, $block ) {
			// Process gutenberg blocks
			if ( $block['blockName'] === 'bfp/bandfront-player-playlist' ) {
				return BFP_RENDER::render_playlist( $block['attrs'] );
			}
			return $pre_render;
		} // End gutenberg_pre_render_block

		public function elementor_editor() {
			include_once dirname( __FILE__ ) . '/elementor/elementor.pb.php';
		} // End elementor_editor

		public function elementor_editor_category( $elements_manager ) {
			$elements_manager->add_category(
				'bandfront-player',
				array(
					'title' => __( 'Bandfront Player', 'bandfront-player' ),
					'icon'  => 'fa fa-music',
				)
			);
		} // End elementor_editor_category

		public function enqueue_block_editor_assets() {
			wp_localize_script(
				'bfp-bandfront-player-playlist-editor-script', // This is the handle WP generates from block.json "name"
				'bfp_gutenberg_editor_config',
				array(
					'url' => admin_url('admin-ajax.php'), // Example value, replace as needed
					'ids_attr_description' => 'Comma-separated product IDs.',
					'categories_attr_description' => 'Comma-separated product category slugs.',
					'tags_attr_description' => 'Comma-separated product tag slugs.',
					'more_details' => 'See documentation for more shortcode options.'
				)
			);
		} // End enqueue_block_editor_assets

	} // End class
} // End if

BFP_BUILDERS::run();

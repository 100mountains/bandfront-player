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
			add_action( 'enqueue_block_editor_assets', array( $instance, 'gutenberg_editor' ) );
			add_filter( 'pre_render_block', array( $instance, 'gutenberg_pre_render_block' ), 10, 2 );

			// Elementor
			add_action( 'elementor/widgets/register', array( $instance, 'elementor_editor' ) );
			add_action( 'elementor/elements/categories_registered', array( $instance, 'elementor_editor_category' ) );

		} // End init

		public function after_setup_theme() {
			$instance = $instance = self::instance();

		} // End after_setup_theme

		public function gutenberg_editor() {
			wp_enqueue_script( 'bandfront-player-gutenberg', plugins_url( 'gutenberg/gutenberg.js', __FILE__ ), array( 'wp-blocks', 'wp-element', 'wp-editor' ) );
			wp_enqueue_style( 'bandfront-player-gutenberg', plugins_url( 'gutenberg/gutenberg.css', __FILE__ ) );
		} // End gutenberg_editor

		public function gutenberg_pre_render_block( $pre_render, $block ) {
			// Process gutenberg blocks
			if ( $block['blockName'] === 'bandfront-player/bfp-playlist' ) {
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

	} // End class
} // End if

BFP_BUILDERS::run();

<?php
/**
 * Main class to interace with the different Content Editors: WCMP_BUILDERS class
 */
if ( ! class_exists( 'WCMP_BUILDERS' ) ) {
	class WCMP_BUILDERS {

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

			// Beaver builder
			if ( class_exists( 'FLBuilder' ) ) {
				include_once dirname( __FILE__ ) . '/beaverbuilder/wcmp.inc.php';
			}

			// DIVI
			add_action( 'et_builder_ready', array( $instance, 'divi_editor' ) );

		} // End init

		public function after_setup_theme() {
			$instance = $instance = self::instance();

			// SiteOrigin
			add_filter( 'siteorigin_widgets_widget_folders', array( $instance, 'siteorigin_widgets_collection' ) );
			add_filter( 'siteorigin_panels_widget_dialog_tabs', array( $instance, 'siteorigin_panels_widget_dialog_tabs' ) );

			// Visual Composer
			add_action( 'vcv:api', array( $instance, 'visualcomposer_editor' ) );
		} // End after_setup_theme

		/**************************** DIVI ****************************/

		public function divi_editor() {
			if ( class_exists( 'ET_Builder_Module' ) ) {
				if ( isset( $_GET['et_fb'] ) ) {
					wp_enqueue_script( 'wcmp-admin-gutenberg-editor', plugin_dir_url( __FILE__ ) . 'divi/divi.js', array( 'react' ), WCMP_VERSION, true );
				}
				require_once dirname( __FILE__ ) . '/divi/divi.pb.php';
			}
		} // End divi_editor

		/**************************** GUTENBERG ****************************/

		/**
		 * Loads the javascript resources to integrate the plugin with the Gutenberg editor
		 */
		public function gutenberg_editor() {
			wp_enqueue_style( 'wcmp-gutenberg-editor-css', plugin_dir_url( __FILE__ ) . 'gutenberg/gutenberg.css', array(), WCMP_VERSION );

			$url  = WCMP_WEBSITE_URL;
			$url .= ( ( strpos( $url, '?' ) === false ) ? '?' : '&' ) . 'wcmp-preview=';

			wp_enqueue_script( 'wcmp-admin-gutenberg-editor', plugin_dir_url( __FILE__ ) . 'gutenberg/gutenberg.js', array( 'wp-blocks', 'wp-element' ), WCMP_VERSION, true );

			wp_localize_script(
				'wcmp-admin-gutenberg-editor',
				'wcmp_gutenberg_editor_config',
				array(
					'url' => $url,
					'ids_attr_description' => __( 'This feature allows you to select the specific products whose players you want to include in the playlist. You can enter multiple product IDs, separated by commas, or use an asterisk "*" symbol to load all available products effortlessly (e.g. products_ids="*").', 'music-player-for-woocommerce'),
					'categories_attr_description' => __( 'This feature enables you to load all products belonging to one or multiple categories at once, eliminating the need to enter their IDs individually. To filter by product categories, simply input their slugs, separated by commas (e.g. product_categories="category-1,category-2")', 'music-player-for-woocommerce'),
					'tags_attr_description' => __( 'Just like filtering by product categories, you can also filter products by tags. To do this, simply enter the tag slugs, separated by commas (e.g. product_tags="tag-1,tag-2")', 'music-player-for-woocommerce'),
					'more_details' => __( 'The remaining attributes of the playlist are detailed in the following link:', 'music-player-for-woocommerce'),
				)
			);
		} // End gutenberg_editor

		public function gutenberg_pre_render_block( $content, $block ) {
			if (
				! empty( $block['blockName'] ) &&
				stripos( $block['blockName'], 'woocommerce/' ) !== false &&
				$GLOBALS['WooCommerceMusicPlayer']->get_global_attr( '_wcmp_force_main_player_in_title', 1 )
			) {
				add_filter( 'woocommerce_product_title', array( $GLOBALS['WooCommerceMusicPlayer'], 'woocommerce_product_title' ), 10, 2 );
				$GLOBALS['WooCommerceMusicPlayer']->enqueue_resources();
				wp_enqueue_script( 'wcmp-wcblocks-js', plugin_dir_url( __FILE__ ) . 'gutenberg/wcblocks.js', array( 'jquery' ), WCMP_VERSION );
				wp_enqueue_style( 'wcmp-wcblocks-css', plugin_dir_url( __FILE__ ) . 'gutenberg/wcblocks.css', array(), WCMP_VERSION );
			}
			return $content;
		} // End gutenberg_pre_render_block

		/**************************** ELEMENTOR ****************************/

		public function elementor_editor_category() {
			require_once dirname( __FILE__ ) . '/elementor/elementor_category.pb.php';
		} // End elementor_editor

		public function elementor_editor() {
			require_once dirname( __FILE__ ) . '/elementor/elementor.pb.php';
		} // End elementor_editor

		/**************************** SITEORIGIN ****************************/

		public function siteorigin_widgets_collection( $folders ) {
			 $folders[] = dirname( __FILE__ ) . '/siteorigin/';
			return $folders;
		} // End siteorigin_widgets_collection

		public function siteorigin_panels_widget_dialog_tabs( $tabs ) {
			 $tabs[] = array(
				 'title'  => __( 'Music Player for WooCommerce', 'music-player-for-woocommerce' ),
				 'filter' => array(
					 'groups' => array( 'music-player-for-woocommerce' ),
				 ),
			 );

			 return $tabs;
		} // End siteorigin_panels_widget_dialog_tabs

		/**************************** VISUAL COMPOSER ****************************/

		public function visualcomposer_editor( $api ) {
			 $elementsToRegister = array( 'WCMPplaylist' );
			$pluginBaseUrl       = rtrim( plugins_url( 'visualcomposer/', __FILE__ ), '\\/' );
			$elementsApi         = $api->elements;
			foreach ( $elementsToRegister as $tag ) {
				$manifestPath   = dirname( __FILE__ ) . '/visualcomposer/' . $tag . '/manifest.json';
				$elementBaseUrl = $pluginBaseUrl . '/' . $tag;
				$elementsApi->add( $manifestPath, $elementBaseUrl );
			}
		} // End visualcomposer_editor
	} // End WCMP_BUILDERS
}

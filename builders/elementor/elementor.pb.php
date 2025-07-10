<?php
namespace Elementor;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class Elementor_BFP_Widget extends Widget_Base {

	public function get_name() {
		return 'bandfront-player';
	} // End get_name

	public function get_title() {
		return 'Playlist';
	} // End get_title

	public function get_icon() {
		return 'eicon-video-playlist';
	} // End get_icon

	public function get_categories() {
		return array( 'bandfront-player-cat' );
	} // End get_categories

	public function is_reload_preview_required() {
		return true;
	} // End is_reload_preview_required

	protected function register_controls() {
		global $wpdb;

		$this->start_controls_section(
			'bfp_section',
			array(
				'label' => __( 'Bandfront Player', 'bandfront-player' ),
			)
		);

		$this->add_control(
			'shortcode',
			array(
				'label'       => __( 'Bandfront Player', 'bandfront-player' ),
				'type'        => Controls_Manager::TEXTAREA,
				'default'     => '[bfp-playlist products_ids="*"  controls="track"]',
				'description' => '<p>' . __( 'To include specific products in the playlist enter their IDs in the products_ids attributes, separated by comma symbols (,)', 'bandfront-player' ) . '</p><p style="color:red;padding:10px 0;">' . __( 'If you are editing the products template, to load the player of the current product, delete the products_ids attribute from the shortcode', 'bandfront-player' ) . '</p><p>' . __( 'More information visiting the follwing link: ', 'bandfront-player' ) . '<br><a href="https://therob.lol/shortcodes" target="_blank">' . __( 'CLICK HERE', 'bandfront-player' ) . '</a></p>',
			)
		);

		$this->end_controls_section();
	} // End register_controls

	private function _get_shortcode() {
		 $settings = $this->get_settings_for_display();
		$shortcode = $settings['shortcode'];
		$shortcode = preg_replace( '/[\r\n]/', ' ', $shortcode );
		return trim( $shortcode );
	} // End _get_shortcode

	protected function render() {
		$shortcode = sanitize_text_field( $this->_get_shortcode() );
		if (
			isset( $_REQUEST['action'] ) &&
			(
				'elementor' == $_REQUEST['action'] ||
				'elementor_ajax' == $_REQUEST['action']
			)
		) {
			try {
				if ( stripos( $shortcode, 'products_ids' ) === false ) {
					if ( ! empty( $GLOBALS['post'] ) && is_object( $GLOBALS['post'] ) ) {
						$shortcode = preg_replace( '/\]/', ' products_ids="' . $GLOBALS['post']->ID . '"]', $shortcode, 1 );
					}
				}
			} catch ( Exception $err ) {
				error_log( $err->getMessage() );
			}

			$url  = BFP_WEBSITE_URL;
			$url .= ( ( strpos( $url, '?' ) === false ) ? '?' : '&' ) . 'bfp-preview=' . urlencode( $shortcode );
			?>
			<div class="bfp-iframe-container" style="position:relative;">
				<div class="bfp-iframe-overlay" style="position:absolute;top:0;right:0;bottom:0;left:0;"></div>
				<iframe height="0" width="100%" src="<?php print esc_attr( $url ); ?>" scrolling="no">
			</div>
			<?php
		} else {
			print do_shortcode( shortcode_unautop( $shortcode ) );
		}

	} // End render

	public function render_plain_content() {
		echo $this->_get_shortcode(); // phpcs:ignore WordPress.Security.EscapeOutput
	} // End render_plain_content

} // End Elementor_BFP_Widget


// Register the widgets
Plugin::instance()->widgets_manager->register( new Elementor_BFP_Widget() );

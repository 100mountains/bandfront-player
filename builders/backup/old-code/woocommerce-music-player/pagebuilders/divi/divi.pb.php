<?php
class WCMP_DIVI extends ET_Builder_Module {


	public $slug       = 'et_pb_wcmp_divi_module';
	public $vb_support = 'on';

	public function init() {
		$this->name                   = esc_html__( 'Music Player for WooCommerce', 'music-player-for-woocommerce' );
		$this->settings_modal_toggles = array(
			'general' => array(
				'toggles' => array(
					'main_content' => esc_html__( 'Playlist', 'music-player-for-woocommerce' ),
				),
			),
		);
	}

	public function get_fields() {
		global $wpdb;
		return array(
			'wcmp_products_ids' => array(
				'label'           => esc_html__( 'Products ids', 'music-player-for-woocommerce' ),
				'type'            => 'text',
				'default'         => '*',
				'option_category' => 'basic_option',
				'description'     => esc_html__( 'Enter the products ids separated by comma, or the * sign to include all products.', 'music-player-for-woocommerce' ),
				'toggle_slug'     => 'main_content',
			),
			'wcmp_attributes'   => array(
				'label'           => esc_html__( 'Additional attributes', 'music-player-for-woocommerce' ),
				'type'            => 'text',
				'option_category' => 'basic_option',
				'description'     => 'controls="track" layout="new"',
				'toggle_slug'     => 'main_content',
			),
		);
	}

	public function render( $unprocessed_props, $content = null, $render_slug = null ) {
		$output   = '';
		$products = sanitize_text_field( $this->props['wcmp_products_ids'] );
		if ( ! empty( $products ) ) {
			$products = ' products_ids="' . esc_attr( $products ) . '"';
		}

		$output = '[wcmp-playlist' . $products;

		$attributes = sanitize_text_field( $this->props['wcmp_attributes'] );
		if ( ! empty( $attributes ) ) {
			$output .= ' ' . $attributes;
		}

		$output .= ']';
		return do_shortcode( $output );
	}
}

new WCMP_DIVI();

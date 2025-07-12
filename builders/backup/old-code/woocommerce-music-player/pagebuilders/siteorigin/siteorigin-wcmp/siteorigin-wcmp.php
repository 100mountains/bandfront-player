<?php
/*
Widget Name: Music Player for WooCommerce
Description: Insert a playlist with the products players.
Documentation: https://wcmp.dwbooster.com/documentation#playlist-shortcode
*/

class SiteOrigin_WCMP_Shortcode extends SiteOrigin_Widget {

	public function __construct() {
		global $wpdb;
		$options = array();
		$default = '';
		parent::__construct(
			'siteorigin-wcmp-shortcode',
			__( 'Music Player for WooCommerce', 'music-player-for-woocommerce' ),
			array(
				'description'   => __( 'Insert a playlist with the products players', 'music-player-for-woocommerce' ),
				'panels_groups' => array( 'music-player-for-woocommerce' ),
				'help'          => 'https://wcmp.dwbooster.com/documentation#playlist-shortcode',
			),
			array(),
			array(
				'shortcode' => array(
					'type'    => 'text',
					'label'   => __( 'To include specific products in the playlist enter their IDs in the products_ids attributes, separated by comma symbols (,)', 'music-player-for-woocommerce' ),
					'default' => '[wcmp-playlist products_ids="*"  controls="track"]',
				),
			),
			plugin_dir_path( __FILE__ )
		);
	} // End __construct

	public function get_template_name( $instance ) {
		return 'siteorigin-wcmp-shortcode';
	} // End get_template_name

	public function get_style_name( $instance ) {
		return '';
	} // End get_style_name

} // End Class SiteOrigin_WCMP_Shortcode

// Registering the widget
siteorigin_widget_register( 'siteorigin-wcmp-shortcode', __FILE__, 'SiteOrigin_WCMP_Shortcode' );

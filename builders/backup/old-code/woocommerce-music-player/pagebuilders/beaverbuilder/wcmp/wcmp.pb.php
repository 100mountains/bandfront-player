<?php
class WCMPBeaver extends FLBuilderModule {
	public function __construct() {
		 $modules_dir = dirname( __FILE__ ) . '/';
		$modules_url  = plugins_url( '/', __FILE__ ) . '/';

		parent::__construct(
			array(
				'name'            => __( 'Music Player for WooCommerce', 'music-player-for-woocommerce' ),
				'description'     => __( 'Insert the playlist shortcode', 'music-player-for-woocommerce' ),
				'group'           => __( 'Music Player for WooCommerce', 'music-player-for-woocommerce' ),
				'category'        => __( 'Music Player for WooCommerce', 'music-player-for-woocommerce' ),
				'dir'             => $modules_dir,
				'url'             => $modules_url,
				'partial_refresh' => true,
			)
		);
	}
}
